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
		if ( is_wp_error( $rows ) ) return $rows;
		
		$rows = array_values( (array) $rows ); 
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
		if ( ! $run_id ) {
			return new \WP_Error( 'run_failed', 'Could not initialize import run track.' );
		}

		try {
			// 4. Ensure Period
			$period_id = $this->import_flow->ensure_period(
				strtolower( trim( $meta['frequency'] ?? 'weekly' ) ),
				$meta['period_date'] ?? null
			);
			if ( ! $period_id ) {
				throw new \Exception( 'Could not create or find a matching period.' );
			}

			// 5. Optionally enrich
			$enriched_count = 0;
			try {
				$enriched_count = (int) $this->enricher->enrich_rows( $rows );
			} catch ( \Exception $e ) {}

			// 6. Process rows
			$saved        = 0;
			$parse_errors = 0;
			$current_row  = 0;
			$error_reasons = array();

			foreach ( $rows as $row ) {
				$current_row++;
				
				$track_name  = trim( $row['track_name'] ?? '' );
				$artist_raw  = trim( $row['artist_names_raw'] ?? '' );
				$artists_arr = $row['artist_names_arr'] ?? array();
				$primary_name = ! empty($artists_arr[0]) ? $artists_arr[0] : (\Charts\Services\Normalizer::split_artists($artist_raw)[0] ?? 'Unknown Artist');

				if ( $track_name === '' && $artist_raw === '' ) {
					$parse_errors++;
					$error_reasons[] = "Row {$current_row}: Missing both track_name and artist_names";
					continue;
				}

				// Resolve / create primary artist
				$primary_artist_id = \Charts\Core\EntityManager::ensure_artist( $primary_name, $row['enrichment']['artists'][0] ?? null );

				if ( ! $primary_artist_id ) {
					global $wpdb;
					$parse_errors++;
					$error_reasons[] = "Row {$current_row}: Failed to ensure primary artist ($primary_name) - DB: {$wpdb->last_error}";
					continue;
				}

				// Link ALL artists
				$all_artists = \Charts\Services\Normalizer::split_artists( $artist_raw );
				if ( empty( $all_artists ) && ! empty( $primary_name ) ) $all_artists = array( $primary_name );

				// Resolve / create track
				$enrichment   = $row['enrichment'] ?? array();
				$spotify_id   = ! empty( $enrichment['spotify_id'] ) ? $enrichment['spotify_id'] : ( $row['spotify_track_id'] ?? null );
				$cover_image  = $enrichment['album']['cover_image'] ?? null;
				$official_name = ! empty( $enrichment['official_name'] ) ? $enrichment['official_name'] : $track_name;
				$official_name = $this->import_flow->normalize_title( $official_name );

				$item_type = $meta['item_type'] ?? 'track';

				$track_id = null;
				if ( $item_type !== 'artist' ) {
					$track_id = \Charts\Core\EntityManager::ensure_track( $official_name, $primary_artist_id, array(
						'spotify_id' => $spotify_id,
						'cover_image' => $cover_image
					) );

					if ( ! $track_id ) {
						global $wpdb;
						$parse_errors++;
						$error_reasons[] = "Row {$current_row}: Failed to ensure track ($official_name) - DB: {$wpdb->last_error}";
						continue;
					}
				}

				$artist_ids = array();
				foreach ( $all_artists as $a_name ) {
					$a_name = trim($a_name);
					if ( empty($a_name) ) continue;
					$a_id = \Charts\Core\EntityManager::ensure_artist( $a_name );
					if ( $a_id ) $artist_ids[] = $a_id;
				}

				if ( $item_type === 'artist' ) {
					$item_id = $primary_artist_id;
				} elseif ( $item_type === 'video' ) {
					$item_id = \Charts\Core\EntityManager::ensure_video( $official_name, $primary_artist_id, array(
						'youtube_id' => $row['youtube_id'] ?? null,
						'thumbnail' => $cover_image
					) );
					if ( $item_id && ! empty( $artist_ids ) ) {
						\Charts\Core\EntityManager::link_artists( $item_id, $artist_ids );
					}
				} else {
					$item_id = $track_id;
					if ( $item_id && ! empty( $artist_ids ) ) {
						\Charts\Core\EntityManager::link_artists( $item_id, $artist_ids );
					}
				}

				$flat = array(
					'track_name'   => $official_name,
					'artist_names' => $artist_raw,
					'cover_image'  => $cover_image,
					'spotify_id'   => $spotify_id,
				);

				$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat );

				if ( $entry_id ) {
					try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
					$saved++;
				} else {
					global $wpdb;
					$parse_errors++;
					$error_reasons[] = "Row {$current_row}: Upsert entry failed - DB: {$wpdb->last_error}";
				}
			}

			// 7. Complete run record
			$err_msg = $parse_errors > 0 ? "Processed with {$parse_errors} row(s) skipped." : "Import completed successfully.";
			if ( ! empty( $error_reasons ) ) {
				$unique_reasons = array_unique( $error_reasons );
				$err_msg .= ' Reasons: ' . implode( ' | ', array_slice( $unique_reasons, 0, 3 ) );
			}

			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'              => 'completed',
				'parsed_rows'         => count( $rows ),
				'matched_items'       => $saved,
				'enrichment_attempts' => count( $rows ),
				'enrichment_failures' => count( $rows ) - $enriched_count,
				'error_message'       => $err_msg,
				'finished_at'         => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );

			if ( $saved > 0 ) {
				try {
					( new Analyzer() )->analyze_period( $period_id, $source_id );
					\Charts\Admin\Bootstrap::clear_frontend_caches();
				} catch ( \Exception $e ) {}
			}

			return array(
				'saved'     => $saved,
				'parsed'    => count( $rows ),
				'run_id'    => $run_id,
				'enriched'  => $enriched_count,
			);

		} catch ( \Exception $e ) {
			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'        => 'failed',
				'error_message' => 'Pipeline Exception: ' . $e->getMessage(),
				'finished_at'   => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );
			return new \WP_Error( 'pipeline_crash', $e->getMessage() );
		}
	}

	/* Deprecated: using Core\EntityManager */

	private function ensure_source( $meta ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_sources';
		$country    = strtolower( trim( $meta['country'] ?? 'eg' ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? 'weekly' ) );
		$chart_id   = intval( $meta['chart_id'] ?? 0 );

		// Strict Binding: Use the chart_id as the ONLY identifier
		if ( $chart_id > 0 ) {
			$lookup_type = "cid-{$chart_id}";
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE platform = 'spotify' AND chart_type = %s AND frequency = %s", $lookup_type, $frequency ) );
			
			// If no specific binding exists, check for a legacy generic source to RECLAIM/MIGRATE
			if ( ! $id ) {
				$legacy_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE platform = 'spotify' AND country_code = %s AND chart_type = %s AND frequency = %s", $country, $chart_type, $frequency ) );
				if ( $legacy_id ) {
					$def = $wpdb->get_row( $wpdb->prepare("SELECT title FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $chart_id) );
					$clean_name = $def ? "Spotify → " . $def->title : "Spotify " . strtoupper( $chart_type ) . " (Locked)";
					$wpdb->update( $table, array( 'chart_type' => $lookup_type, 'source_name' => $clean_name ), array( 'id' => $legacy_id ) );
					$id = $legacy_id;
				}
			}
		} else {
			// FALLBACK REMOVED: Ingestion must bind to a chart profile.
			return false;
		}

		if ( ! $id ) {
			$name = ! empty( $meta['source_name'] ) ? sanitize_text_field( $meta['source_name'] ) : "Spotify " . strtoupper( $chart_type ) . " · " . strtoupper( $country );
			
			// If we have a chart_id, append it to the name for clarity and use cid- binding
			if ( $chart_id > 0 ) {
				$chart_type = "cid-{$chart_id}";
				$def = $wpdb->get_row( $wpdb->prepare("SELECT title FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $chart_id) );
				if ( $def ) $name = "Spotify → " . $def->title;
			}

			$wpdb->insert( $table, array( 
				'source_name' => $name, 
				'platform' => 'spotify', 
				'source_type' => 'manual_import', 
				'country_code' => $country, 
				'chart_type' => $chart_type, 
				'frequency' => $frequency, 
				'source_url' => 'manual', 
				'parser_key' => 'spotify-csv', 
				'is_active' => 1, 
				'created_at' => current_time( 'mysql' ) 
			) );
			$id = $wpdb->insert_id;
		}
		return $id;
	}

	private function start_run( $source_id, $count ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'charts_import_runs', array( 'source_id' => $source_id, 'run_type' => 'csv', 'status' => 'processing', 'parsed_rows' => $count, 'started_at' => current_time( 'mysql' ) ) );
		return $wpdb->insert_id;
	}

	private function link_track_artist( $track_id, $artist_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->prefix}charts_track_artists (track_id, artist_id) VALUES (%d, %d)", $track_id, $artist_id ) );
	}

	private function link_video_artist( $video_id, $artist_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->prefix}charts_video_artists (video_id, artist_id) VALUES (%d, %d)", $video_id, $artist_id ) );
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
