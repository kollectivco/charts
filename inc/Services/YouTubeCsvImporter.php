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
	private $debug_log = array();

	public function __construct() {
		$this->import_flow = new ImportFlow();
		$this->csv_parser  = new \Charts\Parsers\YouTubeCsvParser();
		$this->enrichment  = new YouTubeEnrichmentService();
		$this->debug_log   = array();
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
		$parse_res = $this->csv_parser->parse( $csv_content, $meta['filename'] ?? '' );
		if ( is_wp_error( $parse_res ) ) return $parse_res;

		$rows          = array_values( (array) $parse_res['rows'] );
		$detected_mode = $parse_res['detected_mode'] ?? 'unknown';
		
		if ( empty( $rows ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );

		// 2. Enrich from YouTube API 
		$enriched_count = 0;
		if ( $this->enrichment->is_configured() ) {
			$rows = $this->enrichment->enrich_batch( $rows, $chart_type );
			foreach ( $rows as $r ) {
				if ( ! empty( $r['api_meta'] ) ) $enriched_count++;
			}
		}

		// 3. Source
		$source_id = $this->ensure_source( $meta );
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', __( 'Could not create or find a YouTube source.', 'charts' ) );
		}

		// 4. Run record (Atomic entry)
		$run_id = $this->start_run( $source_id, count( $rows ) );
		if ( ! $run_id ) {
			return new \WP_Error( 'run_failed', 'Could not initialize import run track.' );
		}

		try {
			// 5. Period
			$period_id = $this->import_flow->ensure_period(
				strtolower( trim( $meta['frequency'] ?? 'weekly' ) ),
				$meta['period_date'] ?? null
			);
			if ( ! $period_id ) {
				throw new \Exception( 'Could not create or find a matching period.' );
			}

			// Diagnostic: Pipeline Start
			$this->log_to_run( $run_id, sprintf( "Starting pipeline for %d rows. Mode: %s. Chart: %s.", count($rows), $detected_mode, $chart_type ) );

			$saved             = 0;
			$created_entities  = 0;
			$matched_entities  = 0;
			$parse_errors      = 0;
			$missing_titles    = 0;
			$generated_thumbs  = 0;
			$extracted_ids     = 0;
			$row_errors        = array();
			$current_row       = 0;

			// 6. Process rows
			foreach ( $rows as $row ) {
				$current_row++;
				
				// Keep run from timing out
				if ( $current_row % 50 === 0 ) {
					$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
						'matched_items' => $matched_entities,
						'created_items' => $created_entities,
						'error_message' => "Processing row {$current_row}...",
					), array( 'id' => $run_id ) );
				}

				$title       = $row['item_title'] ?? '';
				$artist_str  = $row['artist_names'] ?? '';
				$artist_arr  = $row['artist_arr'] ?? array();
				$primary_name = ! empty( $artist_arr[0] ) ? $artist_arr[0] : ( $artist_str !== '' ? $artist_str : 'Unknown Artist' );

				if ( ! empty( $row['thumbnail_generated'] ) ) $generated_thumbs++;
				if ( empty( $row['raw_payload']['youtube_id'] ) && ! empty( $row['youtube_id'] ) ) $extracted_ids++;

				if ( empty( $title ) || $title === $row['youtube_id'] ) {
					if ( ! empty( $row['api_meta']['api_title'] ) ) $title = $row['api_meta']['api_title'];
				}

				if ( empty( $title ) ) {
					$missing_titles++;
					$title = 'Unknown YouTube Item';
				}

				// Entity Type Resolution
				$manual_item_type = $meta['item_type'] ?? 'unknown';
				$manual_mode      = $chart_type; 
				$detected_type    = 'unknown';
				if ( $detected_mode === 'top-songs' )   $detected_type = 'track';
				if ( $detected_mode === 'top-artists' ) $detected_type = 'artist';
				if ( $detected_mode === 'top-videos' )  $detected_type = 'video';

				if ( $manual_item_type === 'video' || $manual_mode === 'top-videos' ) {
					$item_type   = 'video';
					$final_logic = 'top-videos';
				} elseif ( $manual_item_type === 'artist' || $manual_mode === 'top-artists' ) {
					$item_type   = 'artist';
					$final_logic = 'top-artists';
				} elseif ( $manual_item_type === 'track' || $manual_mode === 'top-songs' ) {
					$item_type   = 'track';
					$final_logic = 'top-songs';
				} else {
					$item_type   = ( $detected_type !== 'unknown' ) ? $detected_type : 'track';
					$final_logic = ( $detected_mode !== 'unknown' ) ? $detected_mode : $chart_type;
				}

				$item_id = null;
				$is_new  = false;

				if ( $item_type === 'video' ) {
					$primary_artist_id = $this->ensure_artist( $primary_name );
					
					// Match existing for stats
					$existing_id = null;
					if ( ! empty( $row['youtube_id'] ) ) {
						$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_videos WHERE youtube_id = %s", $row['youtube_id'] ) );
					}
					if ( ! $existing_id ) {
						$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_videos WHERE normalized_title = %s AND primary_artist_id = %d", mb_strtolower($this->import_flow->normalize_title($title)), $primary_artist_id ) );
						if ( ! $existing_id ) $is_new = true;
					}

					$item_id   = $this->ensure_video( 
						$this->import_flow->normalize_title( $title ), 
						$primary_artist_id, 
						$row['youtube_id'] ?? null, 
						$row['image'] ?? null, 
						$row['source_url'] ?? null 
					);
					
					$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
					foreach ( $all_artists as $a_name ) {
						$a_id = $this->ensure_artist( $this->import_flow->normalize_title( trim($a_name) ) );
						if ( $item_id && $a_id ) $this->link_video_artist( $item_id, $a_id );
					}
				} elseif ( $item_type === 'artist' ) {
					$artist_name = ! empty( $artist_str ) && $artist_str !== 'Unknown Artist' ? $artist_str : $title;
					
					$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_artists WHERE normalized_name = %s", $this->import_flow->normalize_title($artist_name) ) );
					if ( ! $existing_id ) $is_new = true;

					$item_id     = $this->ensure_artist( $this->import_flow->normalize_title( $artist_name ), $row['image'] ?? null );
				} else {
					$item_type = 'track';
					$primary_artist_id = $this->ensure_artist( $primary_name );

					$existing_id = null;
					if ( ! empty( $row['youtube_id'] ) ) {
						$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_tracks WHERE youtube_id = %s", $row['youtube_id'] ) );
					}
					if ( ! $existing_id ) {
						$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_tracks WHERE normalized_title = %s AND primary_artist_id = %d", mb_strtolower($this->import_flow->normalize_title($title)), $primary_artist_id ) );
						if ( ! $existing_id ) $is_new = true;
					}

					$item_id   = $this->ensure_track( $this->import_flow->normalize_title( $title ), $primary_artist_id, $row['youtube_id'] ?? null, $row['image'] ?? null );

					$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
					foreach ( $all_artists as $a_name ) {
						$a_id = $this->ensure_artist( $this->import_flow->normalize_title( trim($a_name) ) );
						if ( $item_id && $a_id ) $this->link_track_artist( $item_id, $a_id );
					}
				}

				if ( ! $item_id ) {
					$parse_errors++;
					$row_errors[] = "#{$current_row}: Entity Failure (" . ( ! empty($title) ? $title : "No Name" ) . ")";
					continue;
				}

				if ( $is_new ) $created_entities++; else $matched_entities++;

				$flat = array(
					'track_name'   => $title,
					'artist_names' => $artist_str,
					'cover_image'  => $row['image'] ?? null,
					'youtube_id'   => $row['youtube_id'] ?? null,
					'source_url'   => $row['source_url'] ?? null,
					'views_count'  => $row['views_count'] ?? 0,
				);

				$entry_row = array(
					'rank'           => $row['rank'],
					'rank_position'  => $row['rank'],
					'previous_rank'  => $row['previous_rank'] ?? null,
					'rank_change'    => ( ! empty( $row['growth'] ) ) ? $row['growth'] : null,
					'peak_rank'      => $row['peak_rank'] ?? $row['rank'],
					'weeks_on_chart' => $row['weeks_on_chart'] ?? 1,
					'dreams'         => 0,
					'views_count'    => $row['views_count'] ?? 0,
					'source_url'     => $row['source_url'] ?? null,
				);

				$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $entry_row, $flat );

				if ( $entry_id ) {
					try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
					$saved++;
				} else {
					$parse_errors++;
					$row_errors[] = "#{$current_row}: Save Failure";
				}
			}

			// 7. Complete run
			$final_msg = sprintf( "Logic: %s (%s). Matched: %d, Created: %d.", strtoupper($final_logic), $item_type, $matched_entities, $created_entities );
			if ( ! empty($row_errors) ) {
				$final_msg .= " Errors: " . implode( " | ", array_slice($row_errors, 0, 2) );
			}
			
			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'           => 'completed',
				'parsed_rows'      => count($rows),
				'saved_entries'    => $saved,
				'matched_items'    => $matched_entities,
				'created_items'    => $created_entities,
				'error_message'    => $final_msg,
				'finished_at'      => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );

			$wpdb->update( $wpdb->prefix . 'charts_sources', array(
				'last_run_at'     => current_time( 'mysql' ),
				'last_success_at' => current_time( 'mysql' ),
			), array( 'id' => $source_id ) );

			if ( $saved > 0 ) {
				try {
					( new Analyzer() )->analyze_period( $period_id, $source_id );
					\Charts\Admin\Bootstrap::clear_frontend_caches();
				} catch ( \Exception $e ) {}
			}

			return array(
				'saved'   => $saved,
				'matched' => $matched_entities,
				'created' => $created_entities,
				'parsed'  => count( $rows ),
				'run_id'  => $run_id,
			);

		} catch ( \Exception $e ) {
			// Fail the run gracefully
			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'        => 'failed',
				'error_message' => 'Pipeline Exception: ' . $e->getMessage(),
				'finished_at'   => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );
			
			return new \WP_Error( 'pipeline_crash', $e->getMessage() );
		}
	}

	private function ensure_artist( $display_name, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_artists';
		$normalized = mb_strtolower( $this->import_flow->normalize_title( trim( $display_name ) ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( $id ) {
			if ( $image ) $wpdb->update( $table, array( 'image' => $image ), array( 'id' => $id ) );
			return $id;
		}
		$franko = Normalizer::to_franko( $display_name );
		$slug = $this->unique_slug( $table, sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'       => $display_name,
			'display_name_franko' => $franko !== $display_name ? $franko : null,
			'normalized_name'    => $normalized,
			'slug'               => $slug,
			'image'              => $image,
			'created_at'         => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_track( $title, $artist_id, $youtube_id = null, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_tracks';
		$normalized = mb_strtolower( $this->import_flow->normalize_title( trim( $title ) ) );
		if ( $youtube_id ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $youtube_id ) );
			if ( $id ) return $id;
		}
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) {
			if ( $youtube_id ) $wpdb->update( $table, array( 'youtube_id' => $youtube_id ), array( 'id' => $id ) );
			if ( $image ) $wpdb->update( $table, array( 'cover_image' => $image ), array( 'id' => $id ) );
			return $id;
		}
		$franko = Normalizer::to_franko( $title );
		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $youtube_id,
			'cover_image'       => $image,
			'created_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_video( $title, $artist_id, $youtube_id = null, $thumbnail = null, $video_url = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_videos';
		$normalized = mb_strtolower( $this->import_flow->normalize_title( trim( $title ) ) );
		if ( $youtube_id ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $youtube_id ) );
			if ( $id ) return $id;
		}
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) {
			if ( $youtube_id ) $wpdb->update( $table, array( 'youtube_id' => $youtube_id ), array( 'id' => $id ) );
			return $id;
		}
		$franko = Normalizer::to_franko( $title );
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
		) );
		return $wpdb->insert_id;
	}

	private function ensure_source( $meta ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_sources';
		$country    = strtolower( trim( $meta['country'] ?? 'eg' ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? 'weekly' ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE platform = 'youtube' AND country_code = %s AND chart_type = %s AND frequency = %s", $country, $chart_type, $frequency ) );
		if ( ! $id ) {
			$name = ! empty( $meta['source_name'] ) ? sanitize_text_field( $meta['source_name'] ) : "YouTube " . strtoupper( $chart_type ) . " · " . strtoupper( $country );
			$wpdb->insert( $table, array( 'source_name' => $name, 'platform' => 'youtube', 'source_type' => 'manual_import', 'country_code' => $country, 'chart_type' => $chart_type, 'frequency' => $frequency, 'source_url' => 'manual', 'parser_key' => 'youtube-csv', 'is_active' => 1, 'created_at' => current_time( 'mysql' ) ) );
			$id = $wpdb->insert_id;
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

	private function log_to_run( $run_id, $msg ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array( 'error_message' => $msg ), array( 'id' => $run_id ) );
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
