<?php

namespace Charts\Connectors;

use Charts\Services\BillboardService;

/**
 * Billboard Arabia Chart Connector
 * Powered by direct Billboard Arabia REST API.
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

		// 3. Fetch data via BillboardService
		try {
			$weeks = BillboardService::get_weeks();
			$latest_week_id = ! empty( $weeks[0]['week_id'] ) ? $weeks[0]['week_id'] : 202639;

			$items = BillboardService::fetch_chart_data( $latest_week_id );

			$diagnostics = array(
				'strategy'       => 'billboard_rest_api',
				'week_id'        => $latest_week_id,
				'rows_extracted' => count( $items ),
			);

			if ( empty( $items ) ) {
				$msg = __( 'No data returned from Billboard Arabia API.', 'charts' );
				$this->fail_run( $run_id, $msg, $diagnostics );
				return new \WP_Error( 'no_rows', $msg );
			}

			// Format rows for ImportFlow
			$rows = array();
			foreach ( $items as $e ) {
				$rows[] = array(
					'rank'           => $e['rank'],
					'title'          => $e['title'],
					'artists'        => $e['artists'],
					'image'          => $e['image'],
					'previous_rank'  => $e['previous_rank'],
					'peak_rank'      => $e['peak_rank'],
					'weeks_on_chart' => $e['weeks_on_chart'],
					'streams'        => $e['streams'],
				);
			}

			// 4. Update run with fetched/parsed counts
			$this->update_run( $run_id, count( $rows ), count( $rows ), $diagnostics );

			return array(
				'run_id' => $run_id,
				'rows'   => $rows,
			);

		} catch ( \Exception $e ) {
			$this->fail_run( $run_id, $e->getMessage() );
			return new \WP_Error( 'parse_failed', $e->getMessage() );
		}
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
