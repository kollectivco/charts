<?php

namespace Charts\Services;

/**
 * Handle the overall import flow from fetching to entry creation.
 */
class ImportFlow {

	/**
	 * Run an import for a given source ID (YouTube / live scrape).
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
		if ( $source->platform === 'youtube' ) {
			$connector = new \Charts\Connectors\YouTubeConnector();
		}

		if ( ! $connector ) {
			return new \WP_Error( 'connector_not_implemented', 'Connector not implemented for platform: ' . $source->platform );
		}

		// 3. Run Connector
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

		// 4. Determine Period
		$period_id = $this->ensure_period( $source->frequency );

		// 5. Process Rows
		$matched_items = 0;
		$matcher  = new \Charts\Services\Matcher();
		$analyzer = new \Charts\Services\Analyzer();

		foreach ( $rows as $row ) {
			$primary_artist_name = isset($row['artists'][0]) ? $row['artists'][0] : 'Unknown Artist';
			$primary_artist_id   = $matcher->match_artist( $primary_artist_name );

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

			if ( $item_id ) {
				// Flat columns for direct frontend query
				$flat = array(
					'track_name'   => $row['title']  ?? '',
					'artist_names' => implode( ', ', $row['artists'] ?? array() ),
					'cover_image'  => $row['image']  ?? null,
					'spotify_id'   => $row['spotify_id'] ?? null,
					'youtube_id'   => $row['youtube_id'] ?? null,
					'streams'      => 0,
				);

				$entry_id = $this->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat );

				if ( $entry_id ) {
					$analyzer->analyze_entry( $entry_id );
					$matched_items++;
				}
			}
		}

		// 6. Complete Run
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'        => 'completed',
			'parsed_rows'   => count( $rows ),
			'matched_items' => $matched_items,
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		return $matched_items;
	}

	/**
	 * Ensure period exists, return period_id.
	 */
	public function ensure_period( $frequency, $custom_date = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_periods';

		$start_date = $custom_date ?: current_time( 'Y-m-d' );

		if ( $frequency === 'weekly' ) {
			// Align to the Monday of the provided date's week
			$ts = strtotime( $start_date );
			$dow = (int) date( 'N', $ts ); // 1=Mon … 7=Sun
			$monday_ts = $ts - ( ( $dow - 1 ) * 86400 );
			$start_date = date( 'Y-m-d', $monday_ts );
		}

		$period_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE frequency = %s AND period_start = %s",
			$frequency, $start_date
		) );

		if ( ! $period_id ) {
			$end_date = $frequency === 'weekly'
				? date( 'Y-m-d', strtotime( $start_date . ' +6 days' ) )
				: $start_date;

			$wpdb->insert( $table, array(
				'frequency'    => $frequency,
				'period_start' => $start_date,
				'period_end'   => $end_date,
				'label'        => ucfirst( $frequency ) . ': ' . $start_date,
				'created_at'   => current_time( 'mysql' ),
			) );
			$period_id = $wpdb->insert_id;
		}

		return $period_id;
	}

	/**
	 * Create or update an entry row.
	 * Stores both relational item_id and denormalized flat columns for frontend queries.
	 */
	public function upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_entries';

		$existing_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE source_id = %d AND period_id = %d AND rank_position = %d",
			$source_id, $period_id, intval( $row['rank'] ?? $row['rank_position'] ?? 0 )
		) );

		$data = array(
			'source_id'        => $source_id,
			'period_id'        => $period_id,
			'item_type'        => $item_type,
			'item_id'          => $item_id,
			'rank_position'    => intval( $row['rank'] ?? $row['rank_position'] ?? 0 ),
			'previous_rank'    => isset( $row['previous_rank'] ) && $row['previous_rank'] ? intval( $row['previous_rank'] ) : null,
			'peak_rank'        => isset( $row['peak_rank'] ) && $row['peak_rank'] ? intval( $row['peak_rank'] ) : intval( $row['rank'] ?? 0 ),
			'weeks_on_chart'   => intval( $row['weeks_on_chart'] ?? 1 ),
			'streams'          => intval( $row['streams'] ?? $flat['streams'] ?? 0 ),
			'streams_count'    => intval( $row['streams'] ?? $flat['streams'] ?? 0 ),
			// Denormalized flat columns for direct frontend queries (no JOIN needed)
			'track_name'       => $flat['track_name'] ?? $row['track_name'] ?? $row['title'] ?? '',
			'artist_names'     => $flat['artist_names'] ?? $row['artist_names_raw'] ?? ( isset($row['artist_names_arr']) ? implode( ', ', $row['artist_names_arr'] ) : '' ),
			'cover_image'      => $flat['cover_image'] ?? $row['image'] ?? null,
			'spotify_id'       => $flat['spotify_id'] ?? $row['spotify_track_id'] ?? null,
			'youtube_id'       => $flat['youtube_id'] ?? $row['youtube_id'] ?? null,
			'raw_payload_json' => json_encode( $row ),
			'updated_at'       => current_time( 'mysql' ),
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
