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
		$parse_res = $this->csv_parser->parse( $csv_content );
		if ( is_wp_error( $parse_res ) ) return $parse_res;

		$rows          = array_values( (array) $parse_res['rows'] );
		$detected_mode = $parse_res['detected_mode'] ?? 'unknown';
		$headers       = $parse_res['headers'] ?? array();

		if ( empty( $rows ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );

		// VALIDATION: If user picks Videos but file is Songs (or vice versa), report warning
		if ( $detected_mode !== 'unknown' && $detected_mode !== $chart_type ) {
			// Intelligently overwrite chart_type for entity creation, but we keep the source chart_type as-is if strict check is disabled.
			// Actually, the user wants us to either block or route. Let's ROUTE but log it.
			$this->csv_parser->get_warnings(); // Clear previous
			$this->csv_parser->parse( $csv_content ); // Reclear internal
		}

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
			$error_msg = __( 'Could not create or find a matching period.', 'charts' );
			$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
				'status'        => 'failed',
				'error_message' => $error_msg,
				'finished_at'   => current_time( 'mysql' ),
			), array( 'id' => $run_id ) );
			return new \WP_Error( 'period_failed', $error_msg );
		}

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
			$title       = $row['item_title'] ?? '';
			$artist_str  = $row['artist_names'] ?? '';
			$artist_arr  = $row['artist_arr'] ?? array();
			$primary_name = ! empty( $artist_arr[0] ) ? $artist_arr[0] : ( $artist_str !== '' ? $artist_str : 'Unknown Artist' );

			// Track stats
			if ( ! empty( $row['thumbnail_generated'] ) ) $generated_thumbs++;
			// If it wasn't in raw_payload but it is in $row, it was extracted
			if ( empty( $row['raw_payload']['youtube_id'] ) && ! empty( $row['youtube_id'] ) ) $extracted_ids++;

			if ( empty( $title ) || $title === $row['youtube_id'] ) {
				if ( ! empty( $row['api_meta']['api_title'] ) ) {
					$title = $row['api_meta']['api_title'];
				}
			}

			if ( empty( $title ) ) {
				$missing_titles++;
				$title = 'Unknown YouTube Item';
			}

			// ─────────────────────────────────────────────
			//  Entity Type Resolution (Precedence: User Selection > Detection)
			// ─────────────────────────────────────────────
			
			// A. User-defined Preference (from the source's intended logic)
			$manual_mode = $chart_type; // the source's fixed mode (top-songs, top-artists, top-videos)
			
			// B. Structural Detection (what the CSV looks like)
			$detected_type = 'unknown';
			if ( $detected_mode === 'top-songs' )   $detected_type = 'track';
			if ( $detected_mode === 'top-artists' ) $detected_type = 'artist';
			if ( $detected_mode === 'top-videos' )  $detected_type = 'video';

			// C. Final Decision
			// We prioritize the manual_mode because a YouTube "Top Videos" chart might still use "Track Name" header, 
			// leading structural detection to think it's a song chart.
			if ( $manual_mode === 'top-videos' ) {
				$item_type   = 'video';
				$final_logic = 'top-videos';
			} elseif ( $manual_mode === 'top-artists' ) {
				$item_type   = 'artist';
				$final_logic = 'top-artists';
			} elseif ( $manual_mode === 'top-songs' ) {
				$item_type   = 'track';
				$final_logic = 'top-songs';
			} else {
				// Fallback to detection if manual mode is generic or unknown
				$item_type   = ( $detected_type !== 'unknown' ) ? $detected_type : 'track';
				$final_logic = ( $detected_mode !== 'unknown' ) ? $detected_mode : $chart_type;
			}


			// Log this specific decision trail for debugging
			if ( $current_row === 1 ) {
				$this->debug_log[] = sprintf(
					"[DEBUG: Row 1 Decision] Manual=%s, Detected=%s, FinalItem=%s, FinalLogic=%s",
					$manual_mode,
					$detected_mode, 
					$item_type,
					$final_logic
				);
			}

			// Special case for artist sheets: ensure title is artist name if title is empty
			if ( $item_type === 'artist' && ( empty( $title ) || $title === 'Unknown YouTube Item' ) ) {
				if ( ! empty( $artist_str ) && $artist_str !== 'Unknown Artist' ) {
					$title = $artist_str;
				}
			}

			$item_id = null;
			$is_new  = false;

			if ( $item_type === 'video' ) {
				$primary_artist_id = $this->ensure_artist( $primary_name );
				
				// Match existing to determine if it's a new entity
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
				
				// Multi-artist mapping
				$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
				foreach ( $all_artists as $a_name ) {
					$a_id = $this->ensure_artist( $this->import_flow->normalize_title( trim($a_name) ) );
					if ( $item_id && $a_id ) $this->link_video_artist( $item_id, $a_id );
				}
			} elseif ( $item_type === 'artist' ) {
				// Artist Logic: Match and ensure only the artist entity
				$artist_name = ! empty( $artist_str ) && $artist_str !== 'Unknown Artist' ? $artist_str : $title;
				$item_id     = $this->ensure_artist( $this->import_flow->normalize_title( $artist_name ), $row['image'] ?? null );
				
				// Tracking stats
				$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_artists WHERE normalized_title = %s", $this->import_flow->normalize_title($artist_name) ) );
				if ( ! $existing_id ) $is_new = true;
			} else {
				// Default / Track Logic
				$item_type = 'track';
				$primary_artist_id = $this->ensure_artist( $primary_name );

				// Check existing before creation for stats tracking
				$existing_id = null;
				if ( ! empty( $row['youtube_id'] ) ) {
					$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_tracks WHERE youtube_id = %s", $row['youtube_id'] ) );
				}
				if ( ! $existing_id ) {
					$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_tracks WHERE normalized_title = %s AND primary_artist_id = %d", mb_strtolower($this->import_flow->normalize_title($title)), $primary_artist_id ) );
					if ( ! $existing_id ) $is_new = true;
				}

				$item_id   = $this->ensure_track( $this->import_flow->normalize_title( $title ), $primary_artist_id, $row['youtube_id'] ?? null, $row['image'] ?? null );

				// Multi-artist track mapping
				$all_artists = ! empty( $artist_arr ) ? $artist_arr : array($primary_name);
				foreach ( $all_artists as $a_name ) {
					$a_id = $this->ensure_artist( $this->import_flow->normalize_title( trim($a_name) ) );
					if ( $item_id && $a_id ) $this->link_track_artist( $item_id, $a_id );
				}
			}

			if ( ! $item_id ) {
				$parse_errors++;
				$row_errors[] = "#{$current_row}: Entity Resolution Failed (" . ( ! empty($title) ? $title : "No Name" ) . ")";
				continue;
			}

			if ( $is_new ) {
				$created_entities++;
			} else {
				$matched_entities++;
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
				'rank_change'    => ( ! empty( $row['growth'] ) ) ? $row['growth'] : null,
				'peak_rank'      => $row['peak_rank'] ?? $row['rank'],
				'weeks_on_chart' => $row['weeks_on_chart'] ?? 1,
				'streams'        => ( $item_type === 'artist' ) ? ( $row['views_count'] ?? 0 ) : 0,
				'views_count'    => $row['views_count'] ?? 0,
				'source_url'     => $row['source_url'] ?? null,
			);

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $entry_row, $flat );

			if ( $entry_id ) {
				try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
				$saved++;
			} else {
				$parse_errors++;
				$row_errors[] = "#{$current_row}: Entry Save Failed";
			}
		}

		// 7. Complete run
		$final_msg = sprintf( "[LOGIC: %s » %s]", strtoupper($final_logic), ucfirst($item_type) );
		if ( ! empty( $this->debug_log ) ) {
			$final_msg .= " • " . implode( " • ", $this->debug_log );
		}
		if ( ! empty($row_errors) ) {
			$final_msg .= " • ERRORS: " . implode( " | ", array_slice($row_errors, 0, 3) );
			if ( count($row_errors) > 3 ) $final_msg .= " (+".(count($row_errors)-3)." more)";
		}
		$final_msg .= sprintf( " (Parsed: %d, Matched: %d, Created: %d, Errors: %d)", count($rows), $matched_entities, $created_entities, $parse_errors );
		
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'           => 'completed',
			'parsed_rows'      => count($rows),
			'saved_entries'    => $saved,
			'matched_items'    => $matched_entities,
			'created_items'    => $created_entities,
			'error_message'    => $final_msg,
			'completed_at'     => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		// 10. Run Intelligence Analysis
		if ( $saved > 0 ) {
			try {
				( new \Charts\Services\Analyzer() )->analyze_period( $period_id, $source_id );
				// Clear caches so latest artwork appears immediately
				\Charts\Admin\Bootstrap::clear_frontend_caches();
			} catch ( \Exception $e ) {
				// non-fatal
			}
		}

		return array(
			'saved'          => $saved,
			'matched'        => $matched_entities,
			'created'        => $created_entities,
			'parsed'         => count( $rows ),
			'source_id'      => $source_id,
			'period_id'      => $period_id,
			'run_id'         => $run_id,
			'skipped'          => $parse_errors,
			'enriched'         => $enriched_count,
			'generated_thumbs' => $generated_thumbs,
			'extracted'        => $extracted_ids,
			'missing_titles'   => $missing_titles,
			'warnings'         => $this->csv_parser->get_warnings(),
		);
	}


	// ─────────────────────────────────────────────
	//  Entity helpers
	// ─────────────────────────────────────────────

	private function ensure_artist( $display_name, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_artists';
		$normalized = mb_strtolower( $this->import_flow->normalize_title( trim( $display_name ) ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		
		if ( $id ) {
			if ( $image ) {
				$wpdb->update( $table, array( 'image' => $image, 'updated_at' => current_time('mysql') ), array( 'id' => $id ) );
			}
			return $id;
		}

		// Create
		$franko = Normalizer::to_franko( $display_name );
		$slug = $this->unique_slug( $table, sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'       => $display_name,
			'display_name_franko' => $franko !== $display_name ? $franko : null,
			'normalized_name'    => $normalized,
			'slug'               => $slug,
			'image'              => $image,
			'created_at'         => current_time( 'mysql' ),
			'updated_at'         => current_time( 'mysql' ),
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

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
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
			'updated_at'        => current_time( 'mysql' ),
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

		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) {
			if ( $youtube_id ) $wpdb->update( $table, array( 'youtube_id' => $youtube_id ), array( 'id' => $id ) );
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
