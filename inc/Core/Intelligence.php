<?php

namespace Charts\Core;

/**
 * Music Intelligence Engine
 * Handles post-import calculations for tracks, artists, and charts.
 */
class Intelligence {

	/**
	 * Main entry point: Calculate everything.
	 * Triggered after successful import runs.
	 */
	public static function recalculate_all() {
		global $wpdb;

		// 1. Process Tracks & Videos
		self::calculate_item_intelligence('track');
		self::calculate_item_intelligence('video');

		// 2. Process Artists
		self::calculate_artist_intelligence();

		// 3. Process Chart Definitions
		self::calculate_chart_intelligence();
	}

	/**
	 * Calculate Track/Video metrics: Peak, Weeks, Growth, Momentum.
	 */
	public static function calculate_item_intelligence($type = 'track') {
		global $wpdb;

		$table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';

		// Get all unique items of this type that appeared in entries
		$items = $wpdb->get_results($wpdb->prepare("
			SELECT DISTINCT track_name, item_id 
			FROM $entries_table 
			WHERE item_type = %s AND track_name IS NOT NULL
		", $type));

		foreach ($items as $item) {
			// 1. Basic Aggregates
			$stats = $wpdb->get_row($wpdb->prepare("
				SELECT 
					MIN(rank_position) as peak,
					COUNT(id) as weeks,
					SUM(streams_count + views_count) as total_vol
				FROM $entries_table
				WHERE track_name = %s AND item_type = %s
			", $item->track_name, $type));

			// 2. Get Last 2 entries for growth calculation (Movement)
			$recent = $wpdb->get_results($wpdb->prepare("
				SELECT rank_position, created_at
				FROM $entries_table
				WHERE track_name = %s AND item_type = %s
				ORDER BY created_at DESC LIMIT 2
			", $item->track_name, $type));

			$growth = 0;
			$trend = 'stable';
			$momentum = 0;

			if (count($recent) >= 1) {
				$curr = $recent[0]->rank_position;
				$prev = count($recent) > 1 ? $recent[1]->rank_position : 101; // Assume outside top 100 if new

				// Growth: positive if rank improved (decreased)
				$diff = $prev - $curr;
				if ($prev > 0) {
					$growth = ($diff / $prev) * 100;
				}

				if ($diff > 3) $trend = 'rising';
				elseif ($diff < -3) $trend = 'falling';
				if (count($recent) == 1) $trend = 'new';

				// Momentum Score: weighted logic
				// (101 - rank) gives higher score to lower ranks
				$rank_power = (101 - $curr);
				$momentum = ($rank_power * 0.7) + ($diff * 2);
			}

			// Upsert to intelligence table
			$wpdb->query($wpdb->prepare("
				INSERT INTO $table (entity_type, entity_id, momentum_score, growth_rate, trend_status, total_streams, peaks_count, weeks_on_chart, last_calculated_at)
				VALUES (%s, %d, %f, %f, %s, %L, %d, %d, NOW())
				ON DUPLICATE KEY UPDATE 
					momentum_score = VALUES(momentum_score),
					growth_rate = VALUES(growth_rate),
					trend_status = VALUES(trend_status),
					total_streams = VALUES(total_streams),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					last_calculated_at = NOW()
			", $type, $item->item_id, $momentum, $growth, $trend, $stats->total_vol, $stats->peak, $stats->weeks));
		}
	}

	/**
	 * Calculate Artist metrics.
	 */
	public static function calculate_artist_intelligence() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';

		$artists = $wpdb->get_results("SELECT DISTINCT artist_names FROM $entries_table WHERE artist_names IS NOT NULL");

		foreach ($artists as $artist) {
			$stats = $wpdb->get_row($wpdb->prepare("
				SELECT 
					COUNT(DISTINCT track_name) as unique_entries,
					SUM(streams_count + views_count) as total_vol,
					AVG(rank_position) as avg_rank,
					MIN(rank_position) as peak
				FROM $entries_table
				WHERE artist_names = %s
			", $artist->artist_names));

			$hotness = ($stats->unique_entries * 10) + ( (101 - $stats->avg_rank) * 2 );

			// Try to find actual artist ID from artists table
			$artist_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_artists WHERE display_name = %s", $artist->artist_names));
			
			if (!$artist_id) continue;

			$wpdb->query($wpdb->prepare("
				INSERT INTO $table (entity_type, entity_id, momentum_score, total_streams, avg_rank, peaks_count, weeks_on_chart, last_calculated_at)
				VALUES ('artist', %d, %f, %L, %f, %d, %d, NOW())
				ON DUPLICATE KEY UPDATE 
					momentum_score = VALUES(momentum_score),
					total_streams = VALUES(total_streams),
					avg_rank = VALUES(avg_rank),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					last_calculated_at = NOW()
			", $artist_id, $hotness, $stats->total_vol, $stats->avg_rank, $stats->peak, $stats->unique_entries));
		}
	}

	/**
	 * Calculate Chart-level metrics: volatility, dominant artist.
	 */
	public static function calculate_chart_intelligence() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$def_table = $wpdb->prefix . 'charts_definitions';

		$charts = $wpdb->get_results("SELECT id, chart_type, country_code FROM $def_table");

		foreach ($charts as $chart) {
			// Find linked sources
			$sources = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s AND country_code = %s", $chart->chart_type, $chart->country_code));
			if (empty($sources)) continue;

			$source_ids = implode(',', array_map('intval', $sources));

			// Volatility: average absolute rank change
			$volatility = $wpdb->get_var("SELECT AVG(ABS(movement_value)) FROM $entries_table WHERE source_id IN ($source_ids)");

			$wpdb->query($wpdb->prepare("
				INSERT INTO $table (entity_type, entity_id, volatility_score, last_calculated_at)
				VALUES ('chart', %d, %f, NOW())
				ON DUPLICATE KEY UPDATE 
					volatility_score = VALUES(volatility_score),
					last_calculated_at = NOW()
			", $chart->id, $volatility));
		}
	}
}
