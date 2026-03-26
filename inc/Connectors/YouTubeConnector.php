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
		$content = $this->fetch_content( $source->source_url );
		if ( is_wp_error( $content ) ) {
			$this->fail_run( $run_id, $content->get_error_message() );
			return $content;
		}

		// 4. Parse content
		try {
			$parser = new \Charts\Parsers\YouTubeParser();
			$rows   = $parser->parse( $content );
			
			if ( empty( $rows ) ) {
				throw new \Exception( __( 'No rows extracted from YouTube chart.', 'charts' ) );
			}

			// 5. Update run with fetched/parsed counts
			$this->update_run( $run_id, count( $rows ), count( $rows ) );

			// 6. Return rows for processing
			return array(
				'run_id' => $run_id,
				'rows'   => $rows,
			);

		} catch ( \Exception $e ) {
			$this->fail_run( $run_id, $e->getMessage() );
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
	protected function fail_run( $run_id, $error ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->update( $table, array(
			'status'        => 'failed',
			'error_message' => $error,
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );
	}

	/**
	 * Update an import run.
	 */
	protected function update_run( $run_id, $fetched, $parsed ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->update( $table, array(
			'fetched_rows' => $fetched,
			'parsed_rows'  => $parsed,
			'status'       => 'processing',
		), array( 'id' => $run_id ) );
	}
}
