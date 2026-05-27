<?php

namespace Charts\Services;

/**
 * Prepares insight widgets for admin and frontend.
 */
class InsightsDashboardService {

	/**
	 * Return dashboard widgets with optional filters.
	 */
	public function get_dashboard_data( array $filters = array() ) {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'charts_entries';
		$sources_table = $wpdb->prefix . 'charts_sources';
		$periods_table = $wpdb->prefix . 'charts_periods';
		$intel_table   = $wpdb->prefix . 'charts_intelligence';

		$where = array( '1=1' );
		if ( ! empty( $filters['chart'] ) ) {
			$where[] = $wpdb->prepare( 'e.chart_type = %s', $filters['chart'] );
		}
		if ( ! empty( $filters['country'] ) ) {
			$where[] = $wpdb->prepare( 'e.country_code = %s', $filters['country'] );
		}
		if ( ! empty( $filters['week'] ) ) {
			$where[] = $wpdb->prepare( 'p.period_start = %s', $filters['week'] );
		}
		$where_sql = implode( ' AND ', $where );

		$base_select = "
			SELECT e.*, s.source_name, p.period_start, i.momentum_score, i.velocity_score, i.trend_type
			FROM {$entries_table} e
			LEFT JOIN {$sources_table} s ON s.id = e.source_id
			LEFT JOIN {$periods_table} p ON p.id = e.period_id
			LEFT JOIN {$intel_table} i ON i.entity_id = e.item_id AND i.entity_type = e.item_type
			WHERE {$where_sql}
		";

		return array(
			'top_rising' => $this->cache_query( 'top_rising', $filters, $base_select . ' ORDER BY e.movement_value DESC, i.momentum_score DESC LIMIT 8' ),
			'biggest_drops' => $this->cache_query( 'biggest_drops', $filters, $base_select . ' ORDER BY e.movement_value ASC, i.velocity_score ASC LIMIT 8' ),
			'new_entries' => $this->cache_query( 'new_entries', $filters, $base_select . " AND e.movement_direction = 'new' ORDER BY e.rank_position ASC LIMIT 8" ),
			're_entries' => $this->cache_query( 're_entries', $filters, $base_select . " AND e.movement_direction = 're-entry' ORDER BY e.rank_position ASC LIMIT 8" ),
			'consistent' => $this->cache_query( 'consistent', $filters, $base_select . ' ORDER BY ABS(i.velocity_score) ASC, i.avg_rank ASC LIMIT 8' ),
			'highest_momentum' => $this->cache_query( 'highest_momentum', $filters, $base_select . ' ORDER BY i.momentum_score DESC LIMIT 8' ),
		);
	}

	/**
	 * Run cached insight query.
	 */
	private function cache_query( $key, array $filters, $sql ) {
		global $wpdb;
		$cache_key = 'lists_insight_' . $key . '_' . md5( wp_json_encode( $filters ) );
		$cached = get_transient( $cache_key );
		if ( false === $cached ) {
			$cached = get_transient( 'charts_insight_' . $key . '_' . md5( wp_json_encode( $filters ) ) );
		}
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = $wpdb->get_results( $sql );
		set_transient( $cache_key, $rows, 15 * MINUTE_IN_SECONDS );
		return $rows;
	}
}
