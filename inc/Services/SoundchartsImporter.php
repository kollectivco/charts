<?php

namespace Charts\Services;

/** Imports a Soundcharts ranking into a local chart definition. */
class SoundchartsImporter {
	private $api;
	private $flow;

	public function __construct() {
		$this->api  = new SoundchartsApiClient();
		$this->flow = new ImportFlow();
	}

	/**
	 * Import the latest ranking for the selected chart.
	 *
	 * @param array $args entity_type (song|album), platform, country_code, chart_slug, chart_id.
	 * @return array|\WP_Error
	 */
	public function run( array $args ) {
		global $wpdb;

		$entity_type = sanitize_key( $args['entity_type'] ?? '' );
		$platform    = sanitize_key( $args['platform'] ?? '' );
		$country     = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $args['country_code'] ?? '' ) ) );
		$chart_slug  = sanitize_text_field( $args['chart_slug'] ?? '' );
		$chart_id    = absint( $args['chart_id'] ?? 0 );
		$definition  = $chart_id ? ( new \Charts\Admin\SourceManager() )->get_definition( $chart_id ) : null;

		if ( ! $definition ) return new \WP_Error( 'soundcharts_chart_target_missing', __( 'Choose a valid destination chart.', 'charts' ) );
		if ( ! in_array( $entity_type, array( 'song', 'album' ), true ) || $chart_slug === '' || $platform === '' || $country === '' ) {
			return new \WP_Error( 'soundcharts_selection_missing', __( 'Choose a Soundcharts chart, platform, and market.', 'charts' ) );
		}

		$item_type = $entity_type === 'album' ? 'album' : 'track';
		if ( ( $definition->item_type ?? 'track' ) !== $item_type ) {
			return new \WP_Error( 'soundcharts_target_type_mismatch', __( 'The destination chart must use the same entity type as the selected Soundcharts chart.', 'charts' ) );
		}
		$definition_platform = sanitize_key( $definition->platform ?? 'all' );
		if ( ! in_array( $definition_platform, array( 'all', $platform ), true ) ) {
			return new \WP_Error( 'soundcharts_target_platform_mismatch', __( 'The destination chart is configured for a different data platform.', 'charts' ) );
		}
		if ( strtolower( (string) $definition->country_code ) !== strtolower( $country ) ) {
			return new \WP_Error( 'soundcharts_target_country_mismatch', __( 'The destination chart must use the same market as the selected Soundcharts chart.', 'charts' ) );
		}
		// Validate the submitted slug against the current API catalogue before importing it.
		$catalog = $this->api->get_charts( $entity_type, $platform, $country );
		if ( is_wp_error( $catalog ) ) return $catalog;
		$chart = null;
		foreach ( $catalog as $candidate ) {
			if ( hash_equals( (string) $candidate['slug'], $chart_slug ) ) {
				$chart = $candidate;
				break;
			}
		}
		if ( ! $chart ) return new \WP_Error( 'soundcharts_chart_not_found', __( 'That Soundcharts chart is no longer available for the selected market and platform.', 'charts' ) );
		if ( sanitize_key( $definition->frequency ?? 'weekly' ) !== sanitize_key( $chart['frequency'] ?? 'weekly' ) ) {
			return new \WP_Error( 'soundcharts_target_frequency_mismatch', __( 'The destination chart must use the same reporting frequency as the selected Soundcharts chart.', 'charts' ) );
		}

		$ranking = array();
		$offset  = 0;
		$total   = min( 1000, max( 1, absint( $chart['maxResults'] ?? 100 ) ) );
		for ( $page = 0; $page < 10 && $offset < $total; $page++ ) {
			$response = $this->api->get_latest_ranking( $entity_type, $chart_slug, 100, $offset );
			if ( is_wp_error( $response ) ) return $response;
			$items = (array) ( $response['items'] ?? array() );
			if ( empty( $items ) ) break;
			$ranking = array_merge( $ranking, $items );
			$total   = min( 1000, max( $total, absint( $response['page']['total'] ?? 0 ) ) );
			$offset += count( $items );
			if ( count( $items ) < 100 ) break;
		}
		if ( empty( $ranking ) ) return new \WP_Error( 'soundcharts_empty_ranking', __( 'Soundcharts returned no entries for this chart.', 'charts' ) );

		// Chart definitions consume sources through their stable cid-{id} binding.
		$chart_type  = 'cid-' . absint( $definition->id );
		$source_table = $wpdb->prefix . 'charts_sources';
		$source_id    = absint( $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $source_table WHERE platform = %s AND parser_key = 'soundcharts' AND chart_type = %s AND country_code = %s LIMIT 1",
			$platform,
			$chart_type,
			strtolower( $country )
		) ) );
		$source_data = array(
			'source_name' => 'Soundcharts — ' . $chart['name'],
			'platform'    => $platform,
			'source_type' => 'api_sync',
			'country_code'=> strtolower( $country ),
			'chart_type'  => $chart_type,
			'frequency'   => $chart['frequency'],
			'source_url'  => $chart['webUrl'] ?: '',
			'parser_key'  => 'soundcharts',
			'is_active'   => 1,
			'updated_at'  => current_time( 'mysql' ),
		);
		if ( $source_id ) {
			$wpdb->update( $source_table, $source_data, array( 'id' => $source_id ) );
		} else {
			$source_data['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $source_table, $source_data );
			$source_id = absint( $wpdb->insert_id );
		}
		if ( ! $source_id ) return new \WP_Error( 'soundcharts_source_create_failed', __( 'Could not create the Soundcharts import source.', 'charts' ) );

		$api_date = $response['related']['date'] ?? '';
		$period_date = $api_date ? gmdate( 'Y-m-d', strtotime( $api_date ) ) : current_time( 'Y-m-d' );
		$period_id = $this->flow->ensure_period( $chart['frequency'], $period_date );
		if ( ! $period_id ) return new \WP_Error( 'soundcharts_period_failed', __( 'Could not create a chart period for this Soundcharts snapshot.', 'charts' ) );

		$wpdb->insert( $wpdb->prefix . 'charts_import_runs', array(
			'source_id'   => $source_id,
			'run_type'    => 'csv',
			'status'      => 'processing',
			'fetched_rows'=> count( $ranking ),
			'parsed_rows' => count( $ranking ),
			'started_at'  => current_time( 'mysql' ),
		) );
		$run_id = absint( $wpdb->insert_id );
		if ( ! $run_id ) return new \WP_Error( 'soundcharts_run_failed', __( 'Could not initialize the Soundcharts import run.', 'charts' ) );

		$saved    = 0;
		$created  = 0;
		$skipped  = 0;
		$metric   = strtolower( sanitize_text_field( $response['related']['chart']['metric']['type'] ?? '' ) );
		foreach ( $ranking as $item ) {
			$entity     = $item['song'] ?? $item['album'] ?? $item['item'] ?? array();
			$title      = trim( sanitize_text_field( $entity['name'] ?? $entity['title'] ?? '' ) );
			$credits    = trim( sanitize_text_field( $entity['creditName'] ?? $entity['artistName'] ?? $entity['artist'] ?? '' ) );
			$image      = esc_url_raw( $entity['imageUrl'] ?? $entity['image'] ?? '' );
			$rank       = absint( $item['position'] ?? 0 );
			if ( $title === '' || $rank < 1 ) { $skipped++; continue; }

			$artist_names = \Charts\Services\Normalizer::split_artists( $credits );
			if ( empty( $artist_names ) && $credits !== '' ) $artist_names = array( $credits );
			$artist_ids = array();
			foreach ( $artist_names as $artist_name ) {
				$artist_id = \Charts\Core\EntityManager::ensure_artist( $artist_name );
				if ( $artist_id ) $artist_ids[] = $artist_id;
			}
			$primary_artist_id = $artist_ids[0] ?? 0;
			if ( ! $primary_artist_id ) { $skipped++; continue; }

			if ( $item_type === 'album' ) {
				$existing_item = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}charts_albums WHERE normalized_title = %s AND primary_artist_id = %d LIMIT 1",
					mb_strtolower( $title ),
					$primary_artist_id
				) );
				$item_id = $this->ensure_album( $title, $primary_artist_id, $image );
				$item_slug = $item_id ? $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_albums WHERE id = %d", $item_id ) ) : '';
			} else {
				$existing_item = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}charts_tracks WHERE normalized_title = %s AND primary_artist_id = %d LIMIT 1",
					mb_strtolower( $title ),
					$primary_artist_id
				) );
				$item_id = \Charts\Core\EntityManager::ensure_track( $title, $primary_artist_id, array( 'cover_image' => $image ) );
				if ( $item_id && count( $artist_ids ) > 1 ) \Charts\Core\EntityManager::link_artists( $item_id, $artist_ids, 'track' );
				$item_slug = $item_id ? $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $item_id ) ) : '';
			}
			if ( ! $item_id ) { $skipped++; continue; }
			if ( ! $existing_item ) $created++;

			$metric_value = absint( $item['metric'] ?? 0 );
			$row = array(
				'rank'           => $rank,
				'previous_rank'  => ! empty( $item['oldPosition'] ) ? absint( $item['oldPosition'] ) : null,
				'peak_rank'      => $rank,
				'weeks_on_chart' => ( stripos( (string) ( $item['timeOnChartUnit'] ?? '' ), 'week' ) !== false ) ? max( 1, absint( $item['timeOnChart'] ?? 1 ) ) : 1,
				'raw_payload'    => $item,
			);
			$flat = array(
				'track_name'   => $title,
				'artist_names' => $credits,
				'cover_image'  => $image,
				'item_slug'    => $item_slug,
				'streams'      => ( strpos( $metric, 'stream' ) !== false ) ? $metric_value : 0,
				'views_count'  => ( strpos( $metric, 'view' ) !== false ) ? $metric_value : 0,
			);
			$entry_id = $this->flow->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat );
			if ( $entry_id ) {
				$saved++;
			} else {
				$skipped++;
			}
		}

		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'        => $saved > 0 ? 'completed' : 'failed',
			'fetched_rows'  => count( $ranking ),
			'parsed_rows'   => count( $ranking ),
			'created_items' => $created,
			'matched_items' => $saved,
			'error_message' => $skipped ? sprintf( __( '%d ranking item(s) were skipped because required data was missing or could not be saved.', 'charts' ), $skipped ) : '',
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );
		$wpdb->update( $source_table, array( 'last_run_at' => current_time( 'mysql' ), 'last_success_at' => $saved ? current_time( 'mysql' ) : null ), array( 'id' => $source_id ) );

		if ( $saved ) {
			try { ( new Analyzer() )->analyze_period( $period_id, $source_id ); } catch ( \Throwable $e ) {}
			\Charts\Admin\Bootstrap::clear_frontend_caches();
		}

		if ( ! $saved ) return new \WP_Error( 'soundcharts_nothing_imported', __( 'The Soundcharts ranking was retrieved, but no entries could be imported.', 'charts' ), array( 'run_id' => $run_id ) );
		return array( 'saved' => $saved, 'parsed' => count( $ranking ), 'created' => $created, 'run_id' => $run_id, 'source_id' => $source_id, 'period_id' => $period_id );
	}

	private function ensure_album( $title, $artist_id, $image ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_albums';
		$normalized = mb_strtolower( trim( $title ) );
		$id         = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d LIMIT 1", $normalized, $artist_id ) );
		if ( $id ) {
			if ( $image ) $wpdb->update( $table, array( 'cover_image' => $image ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
			return (int) $id;
		}
		$slug = \Charts\Services\Slugger::unique( $table, $title . '-' . $artist_id, 'album-' . $artist_id );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'cover_image'       => $image ?: null,
			'created_at'        => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}
}
