<?php

namespace Charts\Services;

/**
 * Importer for Spotify CSV files.
 * Canonical CSV columns: rank, uri, artist_names, track_name, source (label), peak_rank, previous_rank, weeks_on_chart, streams
 */
class SpotifyCsvImporter {

	private $import_flow;
	private $csv_parser;
	private $enricher;

	public function __construct() {
		$this->import_flow = new ImportFlow();
		$this->csv_parser  = new \Charts\Parsers\SpotifyCsvParser();
		$this->enricher    = new SpotifyEnrichmentService();
	}

	/**
	 * Run an import from CSV content.
	 */
	public function run( $csv_content, array $meta ) {
		global $wpdb;

		// 1. Parse CSV
		$rows = $this->csv_parser->parse( $csv_content );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		$rows = array_values( (array) $rows ); // re-index after array_filter
		if ( empty( $rows ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		// 2. Ensure Source
		$source_id = $this->ensure_source( $meta );
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', __( 'Could not create or find a matching source.', 'charts' ) );
		}

		// 3. Start Import Run
		$run_id = $this->start_run( $source_id, count( $rows ) );

		// 4. Ensure Period
		$period_id = $this->import_flow->ensure_period(
			strtolower( trim( $meta['frequency'] ?? 'weekly' ) ),
			$meta['period_date'] ?? null
		);
		if ( ! $period_id ) {
			$error_msg = __( 'Could not create or find a matching period.', 'charts' );
			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'        => 'failed',
				'error_message' => $error_msg,
				'finished_at'   => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );
			return new \WP_Error( 'period_failed', $error_msg );
		}

		// 5. Optionally enrich (best-effort)
		$enriched_count = 0;
		try {
			$enriched_count = (int) $this->enricher->enrich_rows( $rows );
		} catch ( \Exception $e ) {
			// swallow
		}

		// 6. Process rows
		$saved        = 0;
		$parse_errors = 0;

		foreach ( $rows as $row ) {
			/*
			 * CANONICAL FIELD MAPPING:
			 *   track_name      = track title (NOT "source" which is label/distributor)
			 *   artist_names_raw = comma-separated artist string
			 *   spotify_track_id = extracted from uri column
			 *   source (label)  = label/distributor – stored in raw_payload only
			 */
			$track_name  = trim( $row['track_name'] ?? '' );
			$artist_raw  = trim( $row['artist_names_raw'] ?? '' );
			$artists_arr = $row['artist_names_arr'] ?? array();

			// Guard: skip completely empty rows
			if ( $track_name === '' && $artist_raw === '' ) {
				$parse_errors++;
				continue;
			}

			// Resolve / create primary artist
			$primary_artist_id = $this->ensure_artist( $primary_name, $row['enrichment']['artists'][0] ?? null );

			// Link ALL artists (multi-artist support)
			$all_artists = ! empty( $artists_arr ) ? $artists_arr : \Charts\Services\Normalizer::split_artists( $artist_raw );
			if ( empty( $all_artists ) && ! empty( $primary_name ) ) {
				$all_artists = array( $primary_name );
			}

			// Resolve / create track
			$enrichment   = $row['enrichment'] ?? array();
			$spotify_id   = ! empty( $enrichment['spotify_id'] ) ? $enrichment['spotify_id'] : ( $row['spotify_track_id'] ?? null );
			$cover_image  = $enrichment['album']['cover_image'] ?? null;
			$official_name = ! empty( $enrichment['official_name'] ) ? $enrichment['official_name'] : $track_name;
			$official_name = $this->import_flow->normalize_title( $official_name );

			$track_id = $this->ensure_track( $official_name, $primary_artist_id, $spotify_id, $cover_image );

			if ( ! $track_id ) {
				$parse_errors++;
				continue;
			}

			foreach ( $all_artists as $a_name ) {
				$a_name = trim($a_name);
				if ( empty($a_name) ) continue;
				$a_id = $this->ensure_artist( $a_name );
				if ( $track_id && $a_id ) {
					$this->link_track_artist( $track_id, $a_id );
				}
			}

			$item_type = $meta['item_type'] ?? 'track';

			if ( $item_type === 'artist' ) {
				$item_id = $primary_artist_id;
				$flat['track_name'] = $primary_name; // For artists, name is title
			} elseif ( $item_type === 'video' ) {
				// Use video helper
				$item_id = $this->ensure_video( $official_name, $primary_artist_id, $row['youtube_id'] ?? null, $cover_image );
				// Link video artists
				foreach ( $all_artists as $a_name ) {
					$a_id = $this->ensure_artist( trim($a_name) );
					if ( $item_id && $a_id ) $this->link_video_artist( $item_id, $a_id );
				}
			} else {
				$item_id = $track_id;
			}

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat );

			if ( $entry_id ) {
				// Best-effort movement analysis
				try {
					( new Analyzer() )->analyze_entry( $entry_id );
				} catch ( \Exception $e ) {
					// non-fatal
				}
				$saved++;
			} else {
				$parse_errors++;
			}
		}

		// 7. Complete run record
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'              => 'completed',
			'parsed_rows'         => count( $rows ),
			'matched_items'       => $saved,
			'enrichment_attempts' => count( $rows ),
			'enrichment_failures' => count( $rows ) - $enriched_count,
			'error_message'       => $parse_errors > 0
				? "{$parse_errors} row(s) skipped (blank track/artist or DB error)."
				: null,
			'finished_at'         => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		// 9. Run Intelligence Analysis
		if ( $saved > 0 ) {
			try {
				( new Analyzer() )->analyze_period( $period_id, $source_id );
				// Clear caches so latest artwork appears immediately
				\Charts\Admin\Bootstrap::clear_frontend_caches();
			} catch ( \Exception $e ) {
				// non-fatal
			}
		}

		return array(
			'saved'     => $saved,
			'parsed'    => count( $rows ),
			'source_id' => $source_id,
			'period_id' => $period_id,
			'run_id'    => $run_id,
			'skipped'   => $parse_errors,
			'enriched'  => $enriched_count,
		);
	}

	// -------------------------------------------------------------------------
	//  Internal helpers
	// -------------------------------------------------------------------------

	private function ensure_artist( $display_name, $enrichment_data = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_artists';

		// Try Spotify ID match first (if enriched)
		if ( ! empty( $enrichment_data['spotify_id'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $enrichment_data['spotify_id'] ) );
			if ( $id ) return $id;
		}

		// Normalize name for matching
		$normalized = mb_strtolower( $this->import_flow->normalize_title( $display_name ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( $id ) {
			// Backfill spotify_id and image if now available
			if ( ! empty( $enrichment_data['spotify_id'] ) ) {
				$wpdb->update( $table, array( 
					'spotify_id' => $enrichment_data['spotify_id'],
					'image' => $enrichment_data['image'] ?? $enrichment_data['images'][0]['url'] ?? null
				), array( 'id' => $id ) );
			}
			return $id;
		}

		// Create
		$franko = Normalizer::to_franko( $display_name );
		$slug = $this->unique_slug( $table, sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'display_name_franko' => $franko !== $display_name ? $franko : null,
			'normalized_name' => $normalized,
			'slug'            => $slug,
			'spotify_id'      => $enrichment_data['spotify_id'] ?? null,
			'image'           => $enrichment_data['image'] ?? $enrichment_data['images'][0]['url'] ?? null,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_track( $title, $artist_id, $spotify_id = null, $cover_image = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_tracks';

		// Try Spotify ID
		if ( $spotify_id ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $spotify_id ) );
			if ( $id ) {
				// Update cover if we now have it
				if ( $cover_image ) {
					$wpdb->update( $table, array( 'cover_image' => $cover_image ), array( 'id' => $id ) );
				}
				return $id;
			}
		}

		// Normalized title match
		$normalized = mb_strtolower( $this->import_flow->normalize_title( $title ) );
		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) {
			if ( $spotify_id ) $wpdb->update( $table, array( 'spotify_id' => $spotify_id ), array( 'id' => $id ) );
			if ( $cover_image ) $wpdb->update( $table, array( 'cover_image' => $cover_image ), array( 'id' => $id ) );
			return $id;
		}

		// Create
		$franko = Normalizer::to_franko( $title );
		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'spotify_id'        => $spotify_id,
			'cover_image'       => $cover_image,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function normalize_name( $str ) {
		return mb_strtolower( trim( preg_replace( '/\s+/', ' ', $str ) ) );
	}

	private function link_track_artist( $track_id, $artist_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->prefix}charts_track_artists (track_id, artist_id) VALUES (%d, %d)",
			$track_id, $artist_id
		) );
	}

	private function link_video_artist( $video_id, $artist_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->prefix}charts_video_artists (video_id, artist_id) VALUES (%d, %d)",
			$video_id, $artist_id
		) );
	}

	private function ensure_video( $title, $artist_id, $youtube_id = null, $cover_image = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_videos';

		$normalized = mb_strtolower( $this->import_flow->normalize_title( $title ) );
		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );

		if ( $id ) {
			if ( $cover_image ) $wpdb->update( $table, array( 'cover_image' => $cover_image ), array( 'id' => $id ) );
			return $id;
		}

		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $youtube_id,
			'cover_image'       => $cover_image,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function unique_slug( $table, $slug ) {
		global $wpdb;
		$orig = $slug;
		$i    = 1;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) ) ) {
			$slug = $orig . '-' . $i++;
		}
		return $slug;
	}

	private function ensure_source( $meta ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_sources';
		$country    = strtolower( trim( $meta['country'] ?? 'eg' ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? 'weekly' ) );

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE platform = 'spotify' AND country_code = %s AND chart_type = %s AND frequency = %s",
			$country, $chart_type, $frequency
		) );

		if ( ! $id ) {
			$name = ! empty( $meta['source_name'] )
				? sanitize_text_field( $meta['source_name'] )
				: "Spotify " . strtoupper( $chart_type ) . " · " . strtoupper( $country ) . " · " . ucfirst( $frequency );
			$wpdb->insert( $table, array(
				'source_name'  => $name,
				'platform'     => 'spotify',
				'source_type'  => 'manual_import',
				'country_code' => $country,
				'chart_type'   => $chart_type,
				'frequency'    => $frequency,
				'source_url'   => 'manual',
				'parser_key'   => 'spotify-csv',
				'is_active'    => 1,
				'created_at'   => current_time( 'mysql' ),
			) );
			$id = $wpdb->insert_id;
		} else {
			$wpdb->update( $table, array( 'is_active' => 1, 'source_type' => 'manual_import' ), array( 'id' => $id ) );
		}
		return $id;
	}

	private function start_run( $source_id, $count ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'charts_import_runs', array(
			'source_id'   => $source_id,
			'run_type'    => 'csv',
			'status'      => 'processing',
			'parsed_rows' => $count,
			'started_at'  => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}
}
