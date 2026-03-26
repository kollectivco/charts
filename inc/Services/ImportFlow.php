<?php

namespace Charts\Services;

/**
 * Handle the overall import flow from fetching to entry creation.
 */
class ImportFlow {

	/**
	 * Run an import for a given source ID.
	 */
	public function run( $source_id ) {
		global $wpdb;

		// 1. Get Source
		$source_manager = new \Charts\Admin\SourceManager();
		$source = $source_manager->get_source( $source_id );

		if ( ! $source ) {
			return new \WP_Error( 'source_not_found', 'Source not found.' );
		}

		if ( $source->source_type === 'manual_import' ) {
			return new \WP_Error( 'manual_only', __( 'This source requires manual CSV import.', 'charts' ) );
		}

		// 2. Instantiate Connector
		$connector = null;
		if ( $source->platform === 'spotify' ) {
			// Historically scraping, now deprecated but left for structure parity
			$connector = new \Charts\Connectors\SpotifyConnector();
		} elseif ( $source->platform === 'youtube' ) {
			$connector = new \Charts\Connectors\YouTubeConnector();
		}

		if ( ! $connector ) {
			return new \WP_Error( 'connector_not_implemented', 'Connector not implemented.' );
		}

		// 3. Run Connector (Fetches and Parses)
		try {
			$result = $connector->run( $source_id );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$run_id = $result['run_id'];
			$rows   = $result['rows'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'import_failed', $e->getMessage() );
		}

		// 4. Determine Period (Phase 1: simplified today-based period)
		$period_id = $this->ensure_period( $source->frequency );

		// 5. Process Rows
		$matched_items = 0;
		$created_items = 0;
		$matcher = new \Charts\Services\Matcher();
		$analyzer = new \Charts\Services\Analyzer();

		foreach ( $rows as $row ) {
			// A. Match Artist (Primary)
			$primary_artist_name = $row['artists'][0] ?? 'Unknown Artist';
			$primary_artist_id   = $matcher->match_artist( $primary_artist_name );

			// B. Match Item (Track, Artist, or Video)
			$item_type = 'track';
			$item_id   = null;

			if ( $source->chart_type === 'top-artists' ) {
				$item_type = 'artist';
				$item_id   = $primary_artist_id;
			} elseif ( $source->chart_type === 'top-videos' ) {
				$item_type = 'video';
				$item_id   = $matcher->match_video( $row['title'], $primary_artist_id, null, $row );
			} else {
				$item_type = 'track';
				$item_id   = $matcher->match_track( $row['title'], $primary_artist_id, $row );
			}

			// C. Create/Update Entry
			if ( $item_id ) {
				$entry_id = $this->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row );
				
				// D. Analyze Entry
				if ( $entry_id ) {
					$analyzer->analyze_entry( $entry_id );
					$matched_items++;
				}
			}
		}

		// 6. Complete Run Record
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'        => 'completed',
			'parsed_rows'   => count( $rows ),
			'matched_items' => $matched_items,
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		// 7. Update Source
		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		return $matched_items;
	}

	/**
	 * Ensure period exists.
	 */
	public function ensure_period( $frequency, $custom_date = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_periods';

		$start_date = $custom_date ?: current_time( 'Y-m-d' );
		
		// For weekly, we align to the start of the week (Monday)
		if ( $frequency === 'weekly' ) {
			$start_date = date( 'Y-m-d', strtotime( 'last monday', strtotime( $start_date ) ) );
		}

		$period_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE frequency = %s AND period_start = %s", $frequency, $start_date ) );
		
		if ( ! $period_id ) {
			$wpdb->insert( $table, array(
				'frequency'    => $frequency,
				'period_start' => $start_date,
				'period_end'   => $start_date, // simplified
				'label'        => "Period: $start_date",
				'created_at'   => current_time( 'mysql' ),
			) );
			$period_id = $wpdb->insert_id;
		}

		return $period_id;
	}

	/**
	 * Create or update entry.
	 */
	public function upsert_entry( $source_id, $period_id, $item_type, $item_id, $row ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_entries';

		$existing_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE source_id = %d AND period_id = %d AND item_type = %s AND item_id = %d",
			$source_id, $period_id, $item_type, $item_id
		) );

		$data = array(
			'source_id'      => $source_id,
			'period_id'      => $period_id,
			'item_type'      => $item_type,
			'item_id'        => $item_id,
			'rank_position'  => $row['rank'] ?? 0,
			'previous_rank'  => $row['previous_rank'] ?? null,
			'peak_rank'      => $row['peak_rank'] ?? null,
			'weeks_on_chart' => $row['weeks_on_chart'] ?? 1,
			'streams_count'  => $row['streams'] ?? 0,
			'views_count'    => $row['views'] ?? 0,
			'updated_at'     => current_time( 'mysql' ),
		);

		if ( $existing_id ) {
			$wpdb->update( $table, $data, array( 'id' => $existing_id ) );
			return $existing_id;
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $table, $data );
			return $wpdb->insert_id;
		}
	}
}
