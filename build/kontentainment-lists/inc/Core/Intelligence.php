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
			$trend_type = 'STABLE';
			$momentum = 0;
			$velocity = 0;
			$current_rank = null;
			$previous_rank = null;
			$predicted_rank = null;
			$prediction_confidence = 0;

			if (count($recent) >= 1) {
				$curr = $recent[0]->rank_position;
				$prev = count($recent) > 1 ? $recent[1]->rank_position : 101; // Assume outside top 100 if new
				$current_rank = (int) $curr;
				$previous_rank = count($recent) > 1 ? (int) $prev : null;

				// Growth: positive if rank improved (decreased)
				$diff = $prev - $curr;
				if ($prev > 0) {
					$growth = ($diff / $prev) * 100;
				}
				$velocity = $diff;

				if ($diff > 3) $trend = 'rising';
				elseif ($diff < -3) $trend = 'falling';
				if (count($recent) == 1) $trend = 'new';

				// Momentum Score: weighted logic
				// (101 - rank) gives higher score to lower ranks
				$rank_power = (101 - $curr);
				$momentum = ($rank_power * 0.7) + ($diff * 2);

				if ( count($recent) == 1 && $momentum > 50 ) {
					$trend_type = 'BREAKOUT';
				} elseif ( $diff >= 5 && $momentum > 40 ) {
					$trend_type = 'HOT';
				} elseif ( $diff <= -8 ) {
					$trend_type = 'FALLING FAST';
				} elseif ( $diff < 0 ) {
					$trend_type = 'COOLING';
				} elseif ( abs( $diff ) <= 1 ) {
					$trend_type = 'STABLE';
				}

				$predicted_rank = max( 1, (int) round( $curr - ( $momentum * 0.05 ) + ( max( 1, $stats->weeks ) * 0.02 ) ) );
				$prediction_confidence = min( 100, max( 20, abs( $diff ) * 8 ) );
			}

			$history = $wpdb->get_results($wpdb->prepare("
				SELECT rank_position, final_score, created_at
				FROM $entries_table
				WHERE item_id = %d AND item_type = %s
				ORDER BY created_at ASC LIMIT 12
			", $item->item_id, $type), ARRAY_A);

			// Upsert to intelligence table
			$wpdb->query($wpdb->prepare("
				INSERT INTO $table (entity_type, entity_id, momentum_score, growth_rate, trend_status, trend_type, velocity_score, total_streams, peaks_count, weeks_on_chart, current_rank, previous_rank, predicted_rank, prediction_confidence, chart_history_json, last_calculated_at)
				VALUES (%s, %d, %f, %f, %s, %s, %f, %d, %d, %d, %d, %d, %d, %f, %s, NOW())
				ON DUPLICATE KEY UPDATE 
					momentum_score = VALUES(momentum_score),
					growth_rate = VALUES(growth_rate),
					trend_status = VALUES(trend_status),
					trend_type = VALUES(trend_type),
					velocity_score = VALUES(velocity_score),
					total_streams = VALUES(total_streams),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					current_rank = VALUES(current_rank),
					previous_rank = VALUES(previous_rank),
					predicted_rank = VALUES(predicted_rank),
					prediction_confidence = VALUES(prediction_confidence),
					chart_history_json = VALUES(chart_history_json),
					last_calculated_at = NOW()
			", $type, $item->item_id, $momentum, $growth, $trend, $trend_type, $velocity, (int) $stats->total_vol, (int) $stats->peak, (int) $stats->weeks, $current_rank, $previous_rank, $predicted_rank, $prediction_confidence, wp_json_encode( $history )));
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
			$current_positions = $wpdb->get_col($wpdb->prepare("
				SELECT rank_position FROM $entries_table
				WHERE artist_names = %s
				ORDER BY created_at DESC LIMIT 5
			", $artist->artist_names));
			$metadata = array(
				'total_chart_appearances' => (int) $stats->unique_entries,
				'current_chart_positions' => array_map( 'intval', $current_positions ),
				'peak_position' => (int) $stats->peak,
				'average_rank' => round( (float) $stats->avg_rank, 2 ),
				'total_tracks_charted' => (int) $stats->unique_entries,
			);

			// Try to find actual artist ID from artists table
			$artist_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_artists WHERE display_name = %s", $artist->artist_names));
			
			if (!$artist_id) continue;

			$wpdb->query($wpdb->prepare("
				INSERT INTO $table (entity_type, entity_id, momentum_score, total_streams, avg_rank, peaks_count, weeks_on_chart, metadata_json, last_calculated_at)
				VALUES ('artist', %d, %f, %d, %f, %d, %d, %s, NOW())
				ON DUPLICATE KEY UPDATE 
					momentum_score = VALUES(momentum_score),
					total_streams = VALUES(total_streams),
					avg_rank = VALUES(avg_rank),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					metadata_json = VALUES(metadata_json),
					last_calculated_at = NOW()
			", $artist_id, $hotness, (int) $stats->total_vol, $stats->avg_rank, (int) $stats->peak, (int) $stats->unique_entries, wp_json_encode( $metadata )));
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
