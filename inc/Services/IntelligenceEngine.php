<?php

namespace Charts\Services;

/**
 * Intelligence Engine Service.
 * Centralizes calculations for tracks, artists, and market health indices.
 */
class IntelligenceEngine {

	/**
	 * Run intelligence recalculation.
	 */
	public static function calculate_all() {
		global $wpdb;

		// 1. Calculate Track & Video metrics
		self::calculate_item_metrics('track');
		self::calculate_item_metrics('video');

		// 2. Calculate Artist metrics
		self::calculate_artist_metrics();

		// 3. Calculate Market Health metrics
		self::calculate_market_health();
	}

	/**
	 * Calculate velocity, acceleration, retention, volatility, engagement for tracks/videos.
	 */
	private static function calculate_item_metrics($type = 'track') {
		global $wpdb;

		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$videos_table = $wpdb->prefix . 'charts_videos';

		// Get all unique items that have entries
		$items = $wpdb->get_results($wpdb->prepare("
			SELECT DISTINCT item_id 
			FROM $entries_table 
			WHERE item_type = %s AND item_id > 0
		", $type));

		foreach ($items as $item) {
			$item_id = intval($item->item_id);

			// Fetch historical entries
			$entries = $wpdb->get_results($wpdb->prepare("
				SELECT rank_position, streams_count, views_count, created_at, movement_value, movement_direction
				FROM $entries_table
				WHERE item_type = %s AND item_id = %d
				ORDER BY id ASC
			", $type, $item_id));

			if (empty($entries)) continue;

			$total_weeks = count($entries);
			$latest = $entries[$total_weeks - 1];
			$current_rank = intval($latest->rank_position);

			// 1. Velocity (Weekly rank rate of change)
			$velocity = 0;
			if ($total_weeks > 1) {
				$prev = $entries[$total_weeks - 2];
				$velocity = intval($prev->rank_position) - $current_rank; // positive if rank improved (decreased)
			}

			// 2. Acceleration
			$acceleration = 0;
			if ($total_weeks > 2) {
				$prev_1 = $entries[$total_weeks - 2];
				$prev_2 = $entries[$total_weeks - 3];
				$prev_velocity = intval($prev_2->rank_position) - intval($prev_1->rank_position);
				$acceleration = $velocity - $prev_velocity;
			}

			// 3. Retention (weighted weeks on chart based on rank)
			$rank_sum = 0;
			foreach ($entries as $e) {
				$rank_sum += (101 - intval($e->rank_position));
			}
			$retention = $total_weeks > 0 ? round(($rank_sum / ($total_weeks * 100)) * 100, 2) : 0;

			// 4. Volatility (Standard deviation of rank positions)
			$ranks = array_map(function($e) { return intval($e->rank_position); }, $entries);
			$avg_rank = array_sum($ranks) / count($ranks);
			$variance = 0;
			foreach ($ranks as $r) {
				$variance += pow($r - $avg_rank, 2);
			}
			$volatility = count($ranks) > 1 ? sqrt($variance / (count($ranks) - 1)) : 0;

			// 5. Engagement Score (0-100 scale based on streams/views count and spotify popularity)
			$total_vol = 0;
			foreach ($entries as $e) {
				$total_vol += (floatval($e->streams_count) + floatval($e->views_count));
			}
			$avg_vol = $total_weeks > 0 ? $total_vol / $total_weeks : 0;

			// Load Spotify popularity if available
			$popularity = 50;
			if ($type === 'track') {
				$meta_json = $wpdb->get_var($wpdb->prepare("SELECT metadata_json FROM $tracks_table WHERE id = %d", $item_id));
			} else {
				$meta_json = $wpdb->get_var($wpdb->prepare("SELECT metadata_json FROM $videos_table WHERE id = %d", $item_id));
			}
			$meta = !empty($meta_json) ? json_decode($meta_json, true) : [];
			if (isset($meta['popularity'])) {
				$popularity = intval($meta['popularity']);
			}

			// Normalized engagement: Log-scale average volume + popularity
			$vol_factor = min(50, ($avg_vol > 0 ? log($avg_vol, 1.2) : 0));
			$engagement = min(100, round($vol_factor + ($popularity * 0.5), 2));

			// Fetch existing intelligence metadata
			$intel = $wpdb->get_row($wpdb->prepare("
				SELECT id, metadata_json FROM $intel_table 
				WHERE entity_type = %s AND entity_id = %d
			", $type, $item_id));

			$intel_meta = [];
			if ($intel && !empty($intel->metadata_json)) {
				$intel_meta = json_decode($intel->metadata_json, true);
			}

			// Add our new metrics
			$intel_meta['velocity'] = $velocity;
			$intel_meta['acceleration'] = $acceleration;
			$intel_meta['retention'] = $retention;
			$intel_meta['volatility'] = round($volatility, 2);
			$intel_meta['engagement'] = $engagement;
			$intel_meta['calculated_at'] = current_time('mysql');

			if ($intel) {
				$wpdb->update(
					$intel_table,
					array(
						'volatility_score' => round($volatility, 2),
						'metadata_json' => json_encode($intel_meta),
						'last_calculated_at' => current_time('mysql')
					),
					array('id' => $intel->id)
				);
			} else {
				// Insert a fallback row if it doesn't exist
				$wpdb->insert(
					$intel_table,
					array(
						'entity_type' => $type,
						'entity_id' => $item_id,
						'volatility_score' => round($volatility, 2),
						'metadata_json' => json_encode($intel_meta),
						'last_calculated_at' => current_time('mysql')
					)
				);
			}
		}
	}

	/**
	 * Calculate Market Share, Authority, Power Score for Artists.
	 */
	private static function calculate_artist_metrics() {
		global $wpdb;

		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$artists_table = $wpdb->prefix . 'charts_artists';

		// Get total chart slots in entries
		$total_slots = intval($wpdb->get_var("SELECT COUNT(*) FROM $entries_table WHERE item_type = 'track'"));
		if ($total_slots <= 0) $total_slots = 1;

		$artists = $wpdb->get_results("SELECT id, display_name FROM $artists_table");

		foreach ($artists as $artist) {
			$artist_id = intval($artist->id);

			// Market Share Score: appearances in Top 100 / total slots
			$artist_appearances = intval($wpdb->get_var($wpdb->prepare("
				SELECT COUNT(*) FROM $entries_table 
				WHERE item_type = 'track' AND artist_names LIKE CONCAT('%', %s, '%')
			", $artist->display_name)));

			$market_share = round(($artist_appearances / $total_slots) * 100, 4);

			// Fetch artist intelligence row
			$intel = $wpdb->get_row($wpdb->prepare("
				SELECT id, longevity_score, peaks_count, metadata_json FROM $intel_table 
				WHERE entity_type = 'artist' AND entity_id = %d
			", $artist_id));

			$longevity = $intel ? floatval($intel->longevity_score) : 20;
			$peak = $intel ? intval($intel->peaks_count) : 100;

			// Authority Score: blend of longevity, peak rank performance, and market share
			$peak_factor = $peak > 0 ? (101 - $peak) : 0;
			$authority = round(($longevity * 0.4) + ($peak_factor * 0.4) + (min(100, $market_share * 500) * 0.2), 2);

			$intel_meta = [];
			if ($intel && !empty($intel->metadata_json)) {
				$intel_meta = json_decode($intel->metadata_json, true);
			}

			$intel_meta['market_share'] = $market_share;
			$intel_meta['authority'] = $authority;
			$intel_meta['calculated_at'] = current_time('mysql');

			if ($intel) {
				$wpdb->update(
					$intel_table,
					array(
						'metadata_json' => json_encode($intel_meta),
						'last_calculated_at' => current_time('mysql')
					),
					array('id' => $intel->id)
				);
			} else {
				$wpdb->insert(
					$intel_table,
					array(
						'entity_type' => 'artist',
						'entity_id' => $artist_id,
						'metadata_json' => json_encode($intel_meta),
						'last_calculated_at' => current_time('mysql')
					)
				);
			}
		}
	}

	/**
	 * Calculate Global/Chart Level Market Health Indices.
	 */
	public static function calculate_market_health() {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'charts_entries';
		$def_table = $wpdb->prefix . 'charts_definitions';

		$charts = $wpdb->get_results("SELECT id, title, chart_type FROM $def_table");

		foreach ($charts as $chart) {
			$chart_id = intval($chart->id);

			// Retrieve source IDs bound to this chart
			$sources = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s", "cid-{$chart_id}"));
			if (empty($sources)) continue;

			$source_ids_str = implode(',', array_map('intval', $sources));

			// Fetch entries in the latest period
			$latest_period_id = $wpdb->get_var("
				SELECT MAX(period_id) FROM $entries_table 
				WHERE source_id IN ($source_ids_str)
			");

			if (!$latest_period_id) continue;

			$prev_period_id = $wpdb->get_var($wpdb->prepare("
				SELECT MAX(period_id) FROM $entries_table 
				WHERE source_id IN ($source_ids_str) AND period_id < %d
			", $latest_period_id));

			$curr_entries = $wpdb->get_results($wpdb->prepare("
				SELECT item_id, rank_position, streams_count, views_count, is_new_entry, movement_value
				FROM $entries_table 
				WHERE source_id IN ($source_ids_str) AND period_id = %d
			", $latest_period_id));

			if (empty($curr_entries)) continue;

			$total_entries = count($curr_entries);

			// 1. Competition Index: Ratio of unique tracks to total chart slots (closer to 1 = higher competition)
			$unique_items = count(array_unique(array_column($curr_entries, 'item_id')));
			$competition_idx = round(($unique_items / $total_entries) * 100, 2);

			// 2. Volatility Index: Mean absolute movement value across all songs
			$moves = array_map(function($e) { return abs(intval($e->movement_value)); }, $curr_entries);
			$volatility_idx = round(array_sum($moves) / $total_entries, 2);

			// 3. Retention Index: Percent of recurring songs vs new entries
			$new_entries_count = 0;
			foreach ($curr_entries as $e) {
				if ($e->is_new_entry) $new_entries_count++;
			}
			$retention_idx = round((($total_entries - $new_entries_count) / $total_entries) * 100, 2);

			// 4. Discovery Index: Rate of new entries entering the Top 50
			$new_in_top_50 = 0;
			foreach ($curr_entries as $e) {
				if ($e->is_new_entry && intval($e->rank_position) <= 50) {
					$new_in_top_50++;
				}
			}
			$discovery_idx = round(($new_in_top_50 / 50) * 100, 2);

			// 5. Growth Index: WoW volume change
			$growth_idx = 0;
			if ($prev_period_id) {
				$curr_vol = floatval($wpdb->get_var($wpdb->prepare("
					SELECT SUM(streams_count + views_count) FROM $entries_table 
					WHERE source_id IN ($source_ids_str) AND period_id = %d
				", $latest_period_id)));

				$prev_vol = floatval($wpdb->get_var($wpdb->prepare("
					SELECT SUM(streams_count + views_count) FROM $entries_table 
					WHERE source_id IN ($source_ids_str) AND period_id = %d
				", $prev_period_id)));

				if ($prev_vol > 0) {
					$growth_idx = round((($curr_vol - $prev_vol) / $prev_vol) * 100, 2);
				}
			}

			// Store in option cache for rapid dashboard loading
			$health_data = [
				'competition' => $competition_idx,
				'volatility' => $volatility_idx,
				'retention' => $retention_idx,
				'discovery' => $discovery_idx,
				'growth' => $growth_idx,
				'calculated_at' => current_time('mysql'),
			];

			update_option("kcharts_market_health_{$chart_id}", $health_data);
		}
	}

	/**
	 * Retrieve cache of market health indices.
	 */
	public static function get_market_health($chart_id) {
		$default = [
			'competition' => 75.0,
			'volatility' => 3.2,
			'retention' => 85.0,
			'discovery' => 10.0,
			'growth' => 4.5,
			'calculated_at' => current_time('mysql'),
		];
		return get_option("kcharts_market_health_{$chart_id}", $default);
	}
}
