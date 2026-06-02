<?php

namespace Charts\Services;

class BillboardImporter {

	private $import_flow;

	public function __construct() {
		$this->import_flow = new ImportFlow();
	}

	public function run( $url, $definition_id, $period_date ) {
		global $wpdb;

		// 1. Validate Definition
		$def = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $definition_id ) );
		if ( ! $def ) {
			return new \WP_Error( 'invalid_definition', 'Invalid chart definition selected.' );
		}

		// 2. Ensure Source
		$source_id = $this->ensure_source( $def );
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', 'Could not initialize Billboard Arabia source.' );
		}

		// 3. Ensure Period
		$period_id = $this->import_flow->ensure_period( 'weekly', $period_date );
		if ( ! $period_id ) {
			return new \WP_Error( 'period_failed', 'Could not initialize chart period.' );
		}

		// 4. Fetch HTML
        // Billboard Arabia now embeds all chart data into the homepage JS state.
        // We override the URL to fetch the homepage directly to ensure we get the JSON data.
        $fetch_url = 'https://www.billboardarabia.com/';
		$response = wp_remote_get( $fetch_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
			)
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return new \WP_Error( 'empty_response', 'Received empty response from Billboard Arabia.' );
		}

		// 5. Start Run
		$run_id = $this->start_run( $source_id );

		// 6. Scrape Data
		$entries = $this->scrape_entries( $html, $def->chart_type );
		if ( empty( $entries ) ) {
			$this->fail_run( $run_id, 'Failed to extract chart data. Format might have changed.' );
			return new \WP_Error( 'parse_failed', 'Could not find chart entries in the provided URL.' );
		}

		$this->update_run( $run_id, count($entries), "Found " . count($entries) . " entries. Processing..." );

		// 7. Process Entries (Without YouTube/Spotify)
		$saved = 0;
		$created = 0;
		$matched = 0;
		
		foreach ( $entries as $entry ) {
			$item_type = ( strpos($def->chart_type, 'artist') !== false ) ? 'artist' : 'track';

			if ( $item_type === 'artist' ) {
				$item_id = $this->ensure_artist( $entry['artist_name'], $entry['cover_image'] );
			} else {
				$primary_artist_id = $this->ensure_artist( $entry['artist_name'] );
				$item_id = $this->ensure_track( $entry['track_name'], $primary_artist_id, $entry['cover_image'] );
			}

			if ( ! $item_id ) continue;
			
			// Flat meta
			$flat = array(
				'track_name'   => $entry['track_name'],
				'artist_names' => $entry['artist_name'],
				'cover_image'  => $entry['cover_image'],
			);

			// Entry row
			$entry_row = array(
				'rank'           => $entry['rank'],
				'rank_position'  => $entry['rank'],
				'previous_rank'  => $entry['previous_rank'],
				'peak_rank'      => $entry['peak_rank'],
				'weeks_on_chart' => $entry['weeks_on_chart'],
				'source_url'     => $url,
			);

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $entry_row, $flat );
			if ( $entry_id ) {
				$saved++;
			}
		}

		// 8. Complete run
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'           => 'completed',
			'parsed_rows'      => count($entries),
			'error_message'    => "Successfully imported {$saved} items.",
			'finished_at'      => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		// 9. Analytics trigger
		if ( $saved > 0 ) {
			try {
				( new Analyzer() )->analyze_period( $period_id, $source_id );
				\Charts\Admin\Bootstrap::clear_frontend_caches();
			} catch ( \Exception $e ) {}
		}

		return array(
			'saved' => $saved,
			'count' => count($entries),
		);
	}

	private function scrape_entries( $html, $chart_type ) {
		$entries = array();
		
		$is_artist = (strpos($chart_type, 'artist') !== false);

        if ($is_artist) {
            $pattern = '/\\\\?"rank\\\\?":(\d+).*?\\\\?"arabic_name\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"english_name\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"image\\\\?":(?:\\\\?"([^\\\\"]*)\\\\?"|null)/ui';
        } else {
            $pattern = '/\\\\?"rank\\\\?":(\d+).*?\\\\?"arabic_artist_name\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"english_artist_name\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"arabic_title\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"english_title\\\\?":\\\\?"([^\\\\"]*)\\\\?".*?\\\\?"image\\\\?":(?:\\\\?"([^\\\\"]*)\\\\?"|null)/ui';
        }

		if ( preg_match_all($pattern, $html, $j_matches, PREG_SET_ORDER) ) {
            $chart_entries = array();
            $current_chart = array();
            $expected_rank = 1;
            
            foreach ( $j_matches as $jm ) {
                $rank = intval($jm[1]);
                if ($rank === 1) {
                    if (!empty($current_chart)) {
                        $chart_entries[] = $current_chart;
                    }
                    $current_chart = array();
                    $expected_rank = 1;
                }
                
                if ($rank < $expected_rank && $rank !== 1) {
                    if (!empty($current_chart)) {
                        $chart_entries[] = $current_chart;
                    }
                    $current_chart = array();
                    $expected_rank = $rank;
                }
                
                if ($is_artist) {
                    $current_chart[] = array(
                        'rank' => $rank,
                        'track_name' => '',
                        'artist_name' => str_replace('\\"', '"', !empty($jm[2]) ? $jm[2] : $jm[3]),
                        'cover_image' => isset($jm[4]) ? stripslashes($jm[4]) : '',
                        'previous_rank' => null,
                        'peak_rank' => $rank,
                        'weeks_on_chart' => 1,
                    );
                } else {
                    $current_chart[] = array(
                        'rank' => $rank,
                        'track_name' => str_replace('\\"', '"', !empty($jm[4]) ? $jm[4] : $jm[5]),
                        'artist_name' => str_replace('\\"', '"', !empty($jm[2]) ? $jm[2] : $jm[3]),
                        'cover_image' => isset($jm[6]) ? stripslashes(str_replace('\u002F', '/', $jm[6])) : '',
                        'previous_rank' => null,
                        'peak_rank' => $rank,
                        'weeks_on_chart' => 1,
                    );
                }
                $expected_rank = $rank + 1;
            }
            if (!empty($current_chart)) {
                $chart_entries[] = $current_chart;
            }
            
            $expected_count = (strpos($chart_type, '100') !== false) ? 100 : 50;
            
            foreach ($chart_entries as $ce) {
                if (count($ce) >= $expected_count - 2 && count($ce) <= $expected_count + 5) {
                    $entries = $ce;
                    break;
                }
            }
            
            if (empty($entries) && !empty($chart_entries)) {
                usort($chart_entries, function($a, $b) {
                    return count($b) - count($a);
                });
                $entries = $chart_entries[0];
            }
            
            return $entries;
        }

		return $entries;
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
		$slug = sanitize_title( $display_name );
		if ( empty( $slug ) ) $slug = 'artist-' . wp_generate_password( 8, false );

		$wpdb->insert( $table, array(
			'display_name'        => $display_name,
			'normalized_name'     => $normalized,
			'slug'                => $slug,
			'image'               => $image,
			'created_at'          => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_track( $title, $artist_id, $cover_image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_tracks';
		$normalized = mb_strtolower( $this->import_flow->normalize_title( trim( $title ) ) );
		
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) {
			if ( $cover_image ) $wpdb->update( $table, array( 'cover_image' => $cover_image ), array( 'id' => $id ) );
			return $id;
		}
		
		$slug = sanitize_title( $title . '-' . $artist_id );
		if ( empty( $slug ) ) $slug = 'track-' . wp_generate_password( 8, false );

		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'cover_image'       => $cover_image,
			'created_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function ensure_source( $def ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';
		$lookup_type = "cid-{$def->id}";
		
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE platform = 'billboard' AND chart_type = %s", $lookup_type ) );
		if ( ! $id ) {
			$name = "Billboard Arabia → " . $def->title;
			$wpdb->insert( $table, array( 
				'source_name' => $name, 
				'platform' => 'billboard', 
				'source_type' => 'manual_import', 
				'country_code' => $def->country_code, 
				'chart_type' => $lookup_type, 
				'frequency' => 'weekly', 
				'source_url' => 'https://www.billboardarabia.com/', 
				'parser_key' => 'billboard-scraper', 
				'is_active' => 1, 
				'created_at' => current_time( 'mysql' ) 
			) );
			$id = $wpdb->insert_id;
		}
		return $id;
	}

	private function start_run( $source_id ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'charts_import_runs', array(
			'source_id'   => $source_id,
			'run_type'    => 'billboard_scrape',
			'status'      => 'processing',
			'started_at'  => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function update_run( $run_id, $count, $msg ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array( 'parsed_rows' => $count, 'error_message' => $msg ), array( 'id' => $run_id ) );
	}

	private function fail_run( $run_id, $msg ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array( 'status' => 'failed', 'error_message' => $msg, 'finished_at' => current_time('mysql') ), array( 'id' => $run_id ) );
	}
}
