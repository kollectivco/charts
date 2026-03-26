<?php

namespace Charts\Services;

/**
 * Importer for YouTube chart CSV files.
 *
 * Supports chart_type-aware item creation:
 *   top-songs    → track entries
 *   top-artists  → artist entries
 *   top-videos   → video entries
 */
class YouTubeCsvImporter {

	private $import_flow;
	private $csv_parser;
	private $enrichment;

	public function __construct() {
		$this->import_flow = new ImportFlow();
		$this->csv_parser  = new \Charts\Parsers\YouTubeCsvParser();
		$this->enrichment  = new YouTubeEnrichmentService();
	}

	/**
	 * Run an import from CSV content.
	 * @param string $csv_content Raw CSV bytes.
	 * @param array  $meta        country, chart_type, frequency, period_date, source_name.
	 * @return array|\WP_Error
	 */
	public function run( $csv_content, array $meta ) {
		global $wpdb;

		// 1. Parse
		$rows = $this->csv_parser->parse( $csv_content );
		if ( is_wp_error( $rows ) ) return $rows;
		$rows = array_values( (array) $rows );
		if ( empty( $rows ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );

		// 2. Enrich from YouTube API (truth for metadata, file is truth for rank)
		$enriched_count = 0;
		if ( $this->enrichment->is_configured() ) {
			$initial_rows = $rows;
			$rows = $this->enrichment->enrich_batch( $rows, $chart_type );
			
			// Count how many were actually enriched (had api_meta added)
			foreach ( $rows as $r ) {
				if ( ! empty( $r['api_meta'] ) ) {
					$enriched_count++;
				}
			}
		}

		// 3. Source
		$source_id = $this->ensure_source( $meta );
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', __( 'Could not create or find a YouTube source.', 'charts' ) );
		}

		// 4. Run record
		$run_id = $this->start_run( $source_id, count( $rows ) );

		// 5. Period
		$period_id = $this->import_flow->ensure_period(
			strtolower( trim( $meta['frequency'] ?? 'weekly' ) ),
			$meta['period_date'] ?? null
		);
		if ( ! $period_id ) {
			return new \WP_Error( 'period_failed', __( 'Could not create or find a matching period.', 'charts' ) );
		}

		$saved          = 0;
		$parse_errors   = 0;
		$missing_titles = 0;

		// 6. Process rows
		foreach ( $rows as $row ) {
			$title       = $row['item_title'] ?? '';
			$artist_str  = $row['artist_names'] ?? '';
			$artist_arr  = $row['artist_arr'] ?? array();
			$primary_name = ! empty( $artist_arr[0] ) ? $artist_arr[0] : ( $artist_str !== '' ? $artist_str : 'Unknown Artist' );

			if ( empty( $title ) || $title === $row['youtube_id'] ) {
				if ( ! empty( $row['api_meta']['api_title'] ) ) {
					$title = $row['api_meta']['api_title'];
				}
			}

			if ( empty( $title ) ) {
				$missing_titles++;
				$title = 'Unknown YouTube Item';
			}

			// Resolve item based on chart_type
			if ( $chart_type === 'top-artists' ) {
				$item_type = 'artist';
				// For top-artists, the item_title IS the artist name in many YouTube exports
				$artist_name = ! empty( $artist_str ) && $artist_str !== 'Unknown Artist' ? $artist_str : $title;
				$item_id     = $this->ensure_artist( $artist_name );
			} elseif ( $chart_type === 'top-videos' ) {
				$item_type = 'video';
				$primary_artist_id = $this->ensure_artist( $primary_name );
				$item_id   = $this->ensure_video( $title, $primary_artist_id, $row['youtube_id'] ?? null, $row['image'] ?? null, $row['source_url'] ?? null );
				
				// Link ALL artists
				$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
				foreach ( $all_artists as $a_name ) {
					$a_id = $this->ensure_artist( trim($a_name) );
					if ( $item_id && $a_id ) $this->link_video_artist( $item_id, $a_id );
				}
			} else {
				// default: top-songs / tracks
				$item_type = 'track';
				$primary_artist_id = $this->ensure_artist( $primary_name );
				$item_id   = $this->ensure_track( $title, $primary_artist_id, $row['youtube_id'] ?? null, $row['image'] ?? null );

				// Link ALL artists
				$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
				foreach ( $all_artists as $a_name ) {
					$a_id = $this->ensure_artist( trim($a_name) );
					if ( $item_id && $a_id ) $this->link_track_artist( $item_id, $a_id );
				}
			}

			if ( ! $item_id ) {
				$parse_errors++;
				continue;
			}

			// Flat columns for direct frontend queries
			$flat = array(
				'track_name'   => $title,
				'artist_names' => $artist_str,
				'cover_image'  => $row['image'] ?? null,
				'spotify_id'   => null,
				'youtube_id'   => $row['youtube_id'] ?? null,
				'source_url'   => $row['source_url'] ?? null,
				'streams'      => 0,
				'views_count'  => $row['views_count'] ?? 0,
			);

			// Remap row keys to ImportFlow::upsert_entry expected keys
			$entry_row = array(
				'rank'           => $row['rank'],
				'rank_position'  => $row['rank'],
				'previous_rank'  => $row['previous_rank'] ?? null,
				'peak_rank'      => $row['peak_rank'] ?? $row['rank'],
				'weeks_on_chart' => $row['weeks_on_chart'] ?? 1,
				'streams'        => 0,
				'views_count'    => $row['views_count'] ?? 0,
				'source_url'     => $row['source_url'] ?? null,
			);

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $entry_row, $flat );

			if ( $entry_id ) {
				try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
				$saved++;
			} else {
				$parse_errors++;
			}
		}

		// 7. Complete run
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'        => 'completed',
			'parsed_rows'   => count( $rows ),
			'enrichment_attempts' => count( $rows ),
			'matched_items' => $saved,
			'error_message' => $parse_errors > 0 ? sprintf( __( '%d errors, %d missing titles.', 'charts' ), $parse_errors, $missing_titles ) : ( $missing_titles > 0 ? sprintf( __( '%d missing titles.', 'charts' ), $missing_titles ) : null ),
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		// 10. Run Intelligence Analysis
		if ( $saved > 0 ) {
			try {
				( new \Charts\Services\Analyzer() )->analyze_period( $period_id, $source_id );
			} catch ( \Exception $e ) {
				// non-fatal
			}
		}

		return array(
			'saved'          => $saved,
			'parsed'         => count( $rows ),
			'source_id'      => $source_id,
			'period_id'      => $period_id,
			'run_id'         => $run_id,
			'skipped'        => $parse_errors,
			'enriched'       => $enriched_count,
			'missing_titles' => $missing_titles,
			'warnings'       => $this->csv_parser->get_warnings(),
		);
	}


	// ─────────────────────────────────────────────
	//  Entity helpers
	// ─────────────────────────────────────────────

	private function ensure_artist( $display_name ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_artists';
		$normalized = mb_strtolower( trim( $display_name ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( $id ) return $id;
		$slug = $this->unique_slug( $table, sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'normalized_name' => $normalized,
			'slug'            => $slug,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_track( $title, $artist_id, $youtube_id = null, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_tracks';
		$normalized = mb_strtolower( trim( $title ) );

		if ( $youtube_id ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $youtube_id ) );
			if ( $id ) return $id;
		}

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) {
			if ( $youtube_id ) $wpdb->update( $table, array( 'youtube_id' => $youtube_id ), array( 'id' => $id ) );
			if ( $image ) $wpdb->update( $table, array( 'cover_image' => $image ), array( 'id' => $id ) );
			return $id;
		}

		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $youtube_id,
			'cover_image'       => $image,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_video( $title, $artist_id, $youtube_id = null, $thumbnail = null, $video_url = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_videos';
		$normalized = mb_strtolower( trim( $title ) );

		if ( $youtube_id ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $youtube_id ) );
			if ( $id ) return $id;
		}

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) {
			if ( $youtube_id ) $wpdb->update( $table, array( 'youtube_id' => $youtube_id ), array( 'id' => $id ) );
			return $id;
		}

		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $youtube_id,
			'thumbnail'         => $thumbnail,
			'video_url'         => $video_url,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_source( $meta ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_sources';
		$country    = strtolower( trim( $meta['country'] ?? 'eg' ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? 'weekly' ) );

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE platform = 'youtube' AND country_code = %s AND chart_type = %s AND frequency = %s",
			$country, $chart_type, $frequency
		) );

		if ( ! $id ) {
			$name = ! empty( $meta['source_name'] )
				? sanitize_text_field( $meta['source_name'] )
				: "YouTube " . strtoupper( $chart_type ) . " · " . strtoupper( $country ) . " · " . ucfirst( $frequency );

			$wpdb->insert( $table, array(
				'source_name'  => $name,
				'platform'     => 'youtube',
				'source_type'  => 'manual_import',
				'country_code' => $country,
				'chart_type'   => $chart_type,
				'frequency'    => $frequency,
				'source_url'   => 'manual',
				'parser_key'   => 'youtube-csv',
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
			'run_type'    => 'youtube_csv',
			'status'      => 'processing',
			'parsed_rows' => $count,
			'started_at'  => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
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

	private function unique_slug( $table, $slug ) {
		global $wpdb;
		$orig = $slug;
		$i    = 1;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) ) ) {
			$slug = $orig . '-' . $i++;
		}
		return $slug;
	}
}
