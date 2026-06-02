<?php

namespace Charts\Connectors;

/**
 * Billboard Arabia Chart Connector
 */
class BillboardConnector extends BaseConnector {

	public function run( $source_id ) {
		global $wpdb;

		// 1. Get source details
		$table_sources = $wpdb->prefix . 'charts_sources';
		$source = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_sources WHERE id = %d", $source_id ) );

		if ( ! $source ) {
			return new \WP_Error( 'source_not_found', __( 'Source not found.', 'charts' ) );
		}

		// 2. Start import run
		$run_id = $this->start_run( $source_id );

		// 3. Fetch content
		$response = wp_remote_get( $source->source_url, array( 'user-agent' => 'Mozilla/5.0' ) );
		$status_code = wp_remote_retrieve_response_code( $response );
		$content = wp_remote_retrieve_body( $response );

		$diagnostics = array(
			'http_status' => $status_code,
			'size'        => strlen( $content ),
			'final_url'   => $source->source_url,
		);

		if ( is_wp_error( $response ) ) {
			$this->fail_run( $run_id, $response->get_error_message(), $diagnostics );
			return $response;
		}

		if ( $status_code !== 200 ) {
			$msg = sprintf( __( 'Billboard returned status %d', 'charts' ), $status_code );
			$this->fail_run( $run_id, $msg, $diagnostics );
			return new \WP_Error( 'http_error', $msg );
		}

		// 4. Parse content
		try {
			$chart_type = $source->chart_type; // e.g. "top-artists" or "hot-100"
			$is_artist = (strpos($chart_type, 'artist') !== false);
			$entries = $this->scrape_entries( $content, $is_artist );
			
			$diagnostics['strategy'] = 'billboardRegex';
			$diagnostics['rows_found'] = count( $entries );

			if ( empty( $entries ) ) {
				$msg = __( 'No rows extracted. Billboard HTML structure might have changed.', 'charts' );
				$diagnostics['error_hint'] = 'Empty result from regex scraping';
				$this->fail_run( $run_id, $msg, $diagnostics );
				return new \WP_Error( 'no_rows', $msg );
			}

			// Format for ImportFlow
			$rows = array();
			foreach ( $entries as $e ) {
				$rows[] = array(
					'rank'           => $e['rank'],
					'title'          => $e['track_name'] ?: $e['artist_name'],
					'artists'        => array( $e['artist_name'] ), // ImportFlow will split it further
					'image'          => $e['cover_image'],
					'previous_rank'  => $e['previous_rank'],
					'peak_rank'      => $e['peak_rank'],
					'weeks_on_chart' => $e['weeks_on_chart'],
				);
			}

			// 5. Update run with fetched/parsed counts
			$this->update_run( $run_id, count( $rows ), count( $rows ), $diagnostics );

			// 6. Return rows for processing
			return array(
				'run_id' => $run_id,
				'rows'   => $rows,
			);

		} catch ( \Exception $e ) {
			$this->fail_run( $run_id, $e->getMessage(), $diagnostics );
			return new \WP_Error( 'parse_failed', $e->getMessage() );
		}
	}

	private function scrape_entries( $html, $is_artist ) {
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
					if (!empty($current_chart)) $chart_entries[] = $current_chart;
					$current_chart = array();
					$expected_rank = 1;
				}
				
				if ($rank < $expected_rank && $rank !== 1) {
					if (!empty($current_chart)) $chart_entries[] = $current_chart;
					$current_chart = array();
					$expected_rank = $rank;
				}
				
				$raw_image = $is_artist ? (isset($jm[4]) ? stripslashes(str_replace('\u002F', '/', $jm[4])) : '') : (isset($jm[6]) ? stripslashes(str_replace('\u002F', '/', $jm[6])) : '');
				
				$cover_image = $raw_image;
				if (!empty($raw_image) && strpos($raw_image, 'http') !== 0) {
					if (strpos($raw_image, 'SG') === 0) {
						$cover_image = 'https://sys.billboardarabia.com/storage/songs/' . ltrim($raw_image, '/');
					} else {
						$cover_image = 'https://sys.billboardarabia.com/storage/artists/portrait/' . ltrim($raw_image, '/');
					}
				}

				if ($is_artist) {
					$current_chart[] = array(
						'rank'           => $rank,
						'track_name'     => '',
						'artist_name'    => str_replace('\\"', '"', !empty($jm[2]) ? $jm[2] : $jm[3]),
						'cover_image'    => $cover_image,
						'previous_rank'  => null,
						'peak_rank'      => $rank,
						'weeks_on_chart' => 1,
					);
				} else {
					$current_chart[] = array(
						'rank'           => $rank,
						'track_name'     => str_replace('\\"', '"', !empty($jm[4]) ? $jm[4] : $jm[5]),
						'artist_name'    => str_replace('\\"', '"', !empty($jm[2]) ? $jm[2] : $jm[3]),
						'cover_image'    => $cover_image,
						'previous_rank'  => null,
						'peak_rank'      => $rank,
						'weeks_on_chart' => 1,
					);
				}
				$expected_rank = $rank + 1;
			}
			if (!empty($current_chart)) $chart_entries[] = $current_chart;
			
			usort($chart_entries, function($a, $b) {
				return count($b) - count($a);
			});
			return !empty($chart_entries) ? $chart_entries[0] : array();
		}

		return array();
	}

	protected function start_run( $source_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->insert( $table, array(
			'source_id'  => $source_id,
			'run_type'   => 'manual',
			'status'     => 'started',
			'started_at' => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	protected function fail_run( $run_id, $error, $diagnostics = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->update( $table, array(
			'status'        => 'failed',
			'error_message' => $error,
			'finished_at'   => current_time( 'mysql' ),
			'logs_json'     => wp_json_encode( $diagnostics ),
		), array( 'id' => $run_id ) );
	}

	protected function update_run( $run_id, $fetched, $parsed, $diagnostics = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->update( $table, array(
			'fetched_rows' => $fetched,
			'parsed_rows'  => $parsed,
			'status'       => 'processing',
			'logs_json'    => wp_json_encode( $diagnostics ),
		), array( 'id' => $run_id ) );
	}
}
