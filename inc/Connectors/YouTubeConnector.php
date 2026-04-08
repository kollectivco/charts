<?php

namespace Charts\Connectors;

/**
 * YouTube Chart Connector
 */
class YouTubeConnector extends BaseConnector {

	/**
	 * Run the YouTube connector.
	 */
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
		$response = wp_remote_get( $source->source_url, array( 'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36' ) );
		$status_code = wp_remote_retrieve_response_code( $response );
		$content = wp_remote_retrieve_body( $response );
		$size = strlen( $content );

		$diagnostics = array(
			'http_status' => $status_code,
			'size'        => $size,
			'final_url'   => $source->source_url,
		);

		if ( is_wp_error( $response ) ) {
			$this->fail_run( $run_id, $response->get_error_message(), $diagnostics );
			return $response;
		}

		if ( $status_code !== 200 ) {
			$msg = sprintf( __( 'YouTube returned status %d', 'charts' ), $status_code );
			$this->fail_run( $run_id, $msg, $diagnostics );
			return new \WP_Error( 'http_error', $msg );
		}

		// 4. Parse content
		try {
			$parser = new \Charts\Parsers\YouTubeParser();
			$rows   = $parser->parse( $content );
			
			$diagnostics['strategy'] = 'ytInitialData';
			$diagnostics['rows_found'] = count( $rows );

			if ( empty( $rows ) ) {
				$msg = __( 'No rows extracted. Structure might have changed or auth is required.', 'charts' );
				
				// Added detailed reason to diagnostic logs
				$diagnostics['error_hint'] = 'Empty result from all parsing strategies';
				$diagnostics['body_preview'] = substr($content, 0, 500); 
				
				$this->fail_run( $run_id, $msg, $diagnostics );
				return new \WP_Error( 'no_rows', $msg );
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

	/**
	 * Log the start of an import run.
	 */
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

	/**
	 * Fail an import run.
	 */
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

	/**
	 * Update an import run.
	 */
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
