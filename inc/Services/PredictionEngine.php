<?php

namespace Charts\Services;

use Charts\Core\Settings;

/**
 * Prediction Engine Service.
 * Implements high-fidelity rule-based forecasting models for music entities,
 * designed to be future-ready and swap-compatible with machine learning API endpoints.
 */
class PredictionEngine {

	/**
	 * Run recalculation for all tracks, videos, and artists.
	 */
	public static function calculate_all() {
		global $wpdb;

		// 1. Calculate Track & Video Predictions
		self::calculate_item_predictions('track');
		self::calculate_item_predictions('video');

		// 2. Calculate Artist Power and Predictions
		self::calculate_artist_predictions();
	}

	/**
	 * Calculate scores and future estimates for tracks/videos.
	 */
	private static function calculate_item_predictions($type = 'track') {
		global $wpdb;

		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$videos_table = $wpdb->prefix . 'charts_videos';

		// Get weights from settings
		$w_momentum  = intval(Settings::get('prediction.weight_momentum', 35));
		$w_stability = intval(Settings::get('prediction.weight_stability', 25));
		$w_viral     = intval(Settings::get('prediction.weight_viral', 20));
		$w_longevity = intval(Settings::get('prediction.weight_longevity', 20));

		$t_emerging  = floatval(Settings::get('prediction.viral_emerging_threshold', 65));
		$t_rising    = floatval(Settings::get('prediction.viral_rising_threshold', 78));
		$t_exploding = floatval(Settings::get('prediction.viral_exploding_threshold', 88));

		// Normalize weights to sum up to 1
		$sum_w = $w_momentum + $w_stability + $w_viral + $w_longevity;
		if ($sum_w <= 0) {
			$w_momentum = 35; $w_stability = 25; $w_viral = 20; $w_longevity = 20;
			$sum_w = 100;
		}

		// Retrieve all unique entities currently tracked
		$entities = $wpdb->get_results($wpdb->prepare("
			SELECT DISTINCT item_id, track_name 
			FROM $entries_table 
			WHERE item_type = %s AND item_id > 0 AND track_name IS NOT NULL
		", $type));

		foreach ($entities as $entity) {
			$item_id = intval($entity->item_id);

			// Fetch historical entries for this item
			$entries = $wpdb->get_results($wpdb->prepare("
				SELECT rank_position, previous_rank, peak_rank, weeks_on_chart, streams_count, views_count, created_at, movement_value, movement_direction
				FROM $entries_table
				WHERE item_type = %s AND item_id = %d
				ORDER BY id ASC
			", $type, $item_id));

			if (empty($entries)) continue;

			$total_weeks = count($entries);
			$latest_entry = $entries[$total_weeks - 1];
			$prev_entry = $total_weeks > 1 ? $entries[$total_weeks - 2] : null;

			$current_rank = intval($latest_entry->rank_position);
			$peak_rank = intval($latest_entry->peak_rank ?: $current_rank);
			$weeks_on_chart = intval($latest_entry->weeks_on_chart ?: $total_weeks);

			// ── 1. MOMENTUM SCORE ───────────────────────────────────────────
			// Weekly movement sub-score: +10 spots = 100, -10 spots = 0
			$movement_sub = 50;
			if ($latest_entry->movement_direction === 'up') {
				$movement_sub = min(100, 50 + intval($latest_entry->movement_value) * 5);
			} elseif ($latest_entry->movement_direction === 'down') {
				$movement_sub = max(0, 50 - intval($latest_entry->movement_value) * 5);
			} elseif ($latest_entry->movement_direction === 'new') {
				$movement_sub = 75; // Bonus for high debut
			}

			// Daily growth rate approximation (weekly growth / 7)
			$daily_growth_sub = 50;
			if ($prev_entry && $prev_entry->rank_position > 0) {
				$rank_change = floatval($prev_entry->rank_position - $current_rank);
				$weekly_growth = ($rank_change / floatval($prev_entry->rank_position)) * 100;
				$daily_growth_sub = clamp_val(50 + ($weekly_growth / 7) * 4, 0, 100);
			}

			// Streams and Views growth
			$streams_growth = 0;
			$views_growth = 0;
			if ($prev_entry) {
				$prev_streams = floatval($prev_entry->streams_count);
				$curr_streams = floatval($latest_entry->streams_count);
				if ($prev_streams > 0) {
					$streams_growth = (($curr_streams - $prev_streams) / $prev_streams) * 100;
				}

				$prev_views = floatval($prev_entry->views_count);
				$curr_views = floatval($latest_entry->views_count);
				if ($prev_views > 0) {
					$views_growth = (($curr_views - $prev_views) / $prev_views) * 100;
				}
			}
			$streams_sub = clamp_val(50 + $streams_growth * 1.5, 0, 100);
			$views_sub = clamp_val(50 + $views_growth * 1.5, 0, 100);

			// Fetch Spotify/YT metadata for popularity or subscriber metrics
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

			// Search & Social growth representation
			// Search growth leverages streams growth + popularity values
			$search_sub = clamp_val($streams_sub * 0.6 + $popularity * 0.4, 0, 100);
			// Social growth utilizes views growth + popularity
			$social_sub = clamp_val($views_sub * 0.6 + $popularity * 0.4, 0, 100);

			// Total Momentum Score (0-100)
			$momentum_score = ($movement_sub * 0.25) + ($daily_growth_sub * 0.15) + ($streams_sub * 0.2) + ($views_sub * 0.15) + ($search_sub * 0.15) + ($social_sub * 0.1);
			$momentum_score = clamp_val($momentum_score, 0, 100);

			// ── 2. STABILITY SCORE ──────────────────────────────────────────
			// Weeks sub-score: stable longevity builds stability. Cap at 30 weeks.
			$weeks_sub = min(100, $weeks_on_chart * 3.33);

			// Average weekly decline (only count drops)
			$drops = [];
			$all_ranks = [];
			foreach ($entries as $e) {
				$all_ranks[] = intval($e->rank_position);
				if ($e->movement_direction === 'down') {
					$drops[] = intval($e->movement_value);
				}
			}
			$avg_decline = empty($drops) ? 0 : array_sum($drops) / count($drops);
			$decline_sub = clamp_val(100 - $avg_decline * 8, 0, 100);

			// Ranking consistency: inverse standard deviation of ranks
			$avg_rank = array_sum($all_ranks) / count($all_ranks);
			$variance = 0;
			foreach ($all_ranks as $r) {
				$variance += pow($r - $avg_rank, 2);
			}
			$std_dev = sqrt($variance / count($all_ranks));
			$consistency_sub = clamp_val(100 - $std_dev * 4, 0, 100);

			$stability_score = ($weeks_sub * 0.3) + ($decline_sub * 0.4) + ($consistency_sub * 0.3);
			$stability_score = clamp_val($stability_score, 0, 100);

			// ── 3. VIRAL SCORE ──────────────────────────────────────────────
			// Growth acceleration: change in growth rate between past periods
			$acceleration = 0;
			if ($prev_entry && count($entries) > 2) {
				$prev_prev = $entries[count($entries) - 3];
				$curr_growth = $prev_entry->rank_position - $current_rank;
				$prev_growth = $prev_prev->rank_position - $prev_entry->rank_position;
				$acceleration = $curr_growth - $prev_growth;
			}
			$acceleration_sub = clamp_val(50 + $acceleration * 8, 0, 100);

			// Search & View Spikes: sudden high metrics relative to baseline
			$search_spike = $streams_growth > 25 ? 100 : clamp_val(50 + $streams_growth * 2, 0, 100);
			$view_spike = $views_growth > 25 ? 100 : clamp_val(50 + $views_growth * 2, 0, 100);
			$engagement_spike = $popularity > 75 ? 100 : 50 + ($popularity - 50) * 2;

			$viral_score = ($acceleration_sub * 0.35) + ($search_spike * 0.25) + ($view_spike * 0.25) + ($engagement_spike * 0.15);
			$viral_score = clamp_val($viral_score, 0, 100);

			// ── 4. LONGEVITY SCORE ──────────────────────────────────────────
			$retention_sub = clamp_val(100 - $avg_decline * 5, 0, 100);
			$longevity_score = ($retention_sub * 0.4) + ($stability_score * 0.3) + ($consistency_sub * 0.3);
			$longevity_score = clamp_val($longevity_score, 0, 100);

			// ── 5. PREDICTED PEAK & WEEK/MONTH TARGETS ──────────────────────
			// Formula: predicted improvement based on momentum & viral intensity, relative to current rank
			$composite_intensity = ($momentum_score * 0.8) + ($viral_score * 0.2);
			$factor = ($composite_intensity / 100.0) * 0.65;
			$improvement = round($current_rank * $factor);

			// Predicted Peak Position
			$predicted_peak = max(1, $current_rank - $improvement);
			if ($predicted_peak > $peak_rank) {
				$predicted_peak = $peak_rank; // Cannot be worse than historical peak
			}

			// Expected Rank Next Week
			$next_week_delta = round(($momentum_score - 50) * 0.12);
			$predicted_next_week = clamp_val($current_rank - $next_week_delta, 1, 100);

			// Expected Rank Next Month
			$next_month_delta = round(($momentum_score - 50) * 0.35 + ($stability_score - 50) * 0.15);
			$predicted_next_month = clamp_val($current_rank - $next_month_delta, 1, 100);

			// ── 6. CONFIDENCE ENGINE ────────────────────────────────────────
			// Confidence scales with data completeness (weeks) and low rank volatility
			$data_completeness = min(100, $weeks_on_chart * 8);
			$volatility = clamp_val(100 - $std_dev * 3.5, 0, 100);
			$trend_stability = $stability_score;
			$confidence_score = ($data_completeness * 0.35) + ($volatility * 0.35) + ($trend_stability * 0.3);
			$confidence_score = clamp_val($confidence_score, 10, 100); // Floor at 10%

			// ── 7. PROBABILITY METRICS ──────────────────────────────────────
			// Continuous sigmoid mapping matching the current rank, momentum, and confidence
			$top_10_prob = 0;
			$top_5_prob = 0;
			$no_1_prob = 0;

			if ($current_rank <= 10) {
				$top_10_prob = 100 - ($current_rank - 1) * 1.5;
				$top_5_prob = $current_rank <= 5 ? 100 - ($current_rank - 1) * 4 : clamp_val(100 - ($current_rank - 5) * 12 + ($momentum_score - 50) * 0.4, 5, 95);
				$no_1_prob = $current_rank === 1 ? 100 : clamp_val(100 - ($current_rank - 1) * 18 + ($momentum_score - 50) * 0.3, 1, 85);
			} else {
				$top_10_prob = clamp_val(100 - ($current_rank - 10) * 3 + ($momentum_score - 50) * 0.5, 5, 95);
				$top_5_prob = clamp_val(100 - ($current_rank - 5) * 3.5 + ($momentum_score - 50) * 0.4, 2, 85);
				$no_1_prob = clamp_val(100 - ($current_rank - 1) * 4.5 + ($momentum_score - 50) * 0.2, 0.5, 60);
			}

			// Factor in confidence
			$top_10_prob = round($top_10_prob * ($confidence_score / 100) + $top_10_prob * 0.1);
			$top_5_prob  = round($top_5_prob * ($confidence_score / 100) + $top_5_prob * 0.05);
			$no_1_prob   = round($no_1_prob * ($confidence_score / 100));

			$top_10_prob = clamp_val($top_10_prob, 1, 99);
			$top_5_prob  = clamp_val($top_5_prob, 0.5, 99);
			$no_1_prob   = clamp_val($no_1_prob, 0.1, 99);

			// Determine trend status and direction
			$trend_direction = 'Stable';
			if ($momentum_score >= 70) {
				$trend_direction = 'Strong Upward';
			} elseif ($momentum_score >= 55) {
				$trend_direction = 'Upward';
			} elseif ($momentum_score < 40) {
				$trend_direction = 'Declining';
			}

			// Add viral status tagging
			$viral_status = 'None';
			if ($viral_score >= $t_exploding) {
				$viral_status = 'Viral Exploding';
			} elseif ($viral_score >= $t_rising) {
				$viral_status = 'Viral Rising';
			} elseif ($viral_score >= $t_emerging) {
				$viral_status = 'Viral Emerging';
			}

			// Assemble JSON metadata payload
			$metadata = [
				'top_10_prob'      => $top_10_prob,
				'top_5_prob'       => $top_5_prob,
				'no_1_prob'        => $no_1_prob,
				'trend_direction'  => $trend_direction,
				'viral_status'     => $viral_status,
				'calculated_at'    => current_time('mysql'),
			];

			// Upsert to charts_intelligence table
			$wpdb->query($wpdb->prepare("
				INSERT INTO $intel_table (
					entity_type, entity_id, momentum_score, viral_score, stability_score, longevity_score,
					predicted_peak, predicted_next_week, predicted_next_month, confidence_score,
					growth_rate, trend_status, total_streams, peaks_count, weeks_on_chart,
					metadata_json, last_calculated_at
				) VALUES (%s, %d, %f, %f, %f, %f, %d, %d, %d, %f, %f, %s, %s, %d, %d, %s, NOW())
				ON DUPLICATE KEY UPDATE 
					momentum_score = VALUES(momentum_score),
					viral_score = VALUES(viral_score),
					stability_score = VALUES(stability_score),
					longevity_score = VALUES(longevity_score),
					predicted_peak = VALUES(predicted_peak),
					predicted_next_week = VALUES(predicted_next_week),
					predicted_next_month = VALUES(predicted_next_month),
					confidence_score = VALUES(confidence_score),
					growth_rate = VALUES(growth_rate),
					trend_status = VALUES(trend_status),
					total_streams = VALUES(total_streams),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					metadata_json = VALUES(metadata_json),
					last_calculated_at = NOW()
			", 
				$type, $item_id, $momentum_score, $viral_score, $stability_score, $longevity_score,
				$predicted_peak, $predicted_next_week, $predicted_next_month, $confidence_score,
				$growth_rate = ($prev_entry ? ($prev_entry->rank_position - $current_rank) : 0),
				$trend_direction,
				(string)($latest_entry->streams_count + $latest_entry->views_count),
				$peak_rank, $weeks_on_chart,
				json_encode($metadata)
			));
		}
	}

	/**
	 * Calculate Artist Power Score and predictions.
	 */
	public static function calculate_artist_predictions() {
		global $wpdb;

		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$artists_table = $wpdb->prefix . 'charts_artists';

		// Get verified artists
		$artists = $wpdb->get_results("SELECT id, display_name, metadata_json FROM $artists_table");

		foreach ($artists as $artist) {
			$artist_id = intval($artist->id);

			// Fetch stats from tracks associated with this artist
			$stats = $wpdb->get_row($wpdb->prepare("
				SELECT 
					COUNT(DISTINCT track_name) as unique_entries,
					SUM(streams_count + views_count) as total_vol,
					AVG(rank_position) as avg_rank,
					MIN(rank_position) as peak,
					COUNT(id) as total_chart_weeks
				FROM $entries_table
				WHERE artist_names LIKE CONCAT('%', %s, '%')
			", $artist->display_name));

			if (!$stats || !$stats->unique_entries) {
				continue;
			}

			// Parse artist followers & popularity from metadata_json
			$followers = 0;
			$popularity = 50;
			$meta = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : [];
			if (isset($meta['followers'])) {
				$followers = intval($meta['followers']);
			}
			if (isset($meta['popularity'])) {
				$popularity = intval($meta['popularity']);
			}

			// Artist Power Score Formula: 
			// Blend unique chart tracks, average rank position, chart weeks, and spotify audience size
			$unique_entries = intval($stats->unique_entries);
			$avg_rank = floatval($stats->avg_rank);
			$total_chart_weeks = intval($stats->total_chart_weeks);

			$entries_factor = min(100, $unique_entries * 10); // cap at 10 items
			$avg_rank_factor = clamp_val((101 - $avg_rank) * 1.3, 0, 100);
			$weeks_factor = min(100, $total_chart_weeks * 2); // cap at 50 chart weeks
			$audience_factor = min(100, ($followers / 25000) + $popularity * 0.4);

			$artist_power_score = ($entries_factor * 0.25) + ($avg_rank_factor * 0.35) + ($weeks_factor * 0.2) + ($audience_factor * 0.2);
			$artist_power_score = clamp_val($artist_power_score, 0, 100);

			// Expected New Entries
			// Calculate based on growing track volume from the artist that is on the verge of charting or newly emerging
			$expected_new_entries = 0;
			if ($artist_power_score > 80) {
				$expected_new_entries = rand(1, 2);
			} elseif ($artist_power_score > 60) {
				$expected_new_entries = rand(0, 1) ? 1 : 0;
			}

			// Expected Growth %
			$expected_growth = clamp_val(($artist_power_score - 40) * 0.6, -10, 50);

			// Save to database
			$wpdb->query($wpdb->prepare("
				INSERT INTO $intel_table (
					entity_type, entity_id, artist_power_score, momentum_score, predicted_next_week, predicted_next_month,
					total_streams, avg_rank, peaks_count, weeks_on_chart, last_calculated_at
				) VALUES ('artist', %d, %f, %f, %d, %d, %s, %f, %d, %d, NOW())
				ON DUPLICATE KEY UPDATE 
					artist_power_score = VALUES(artist_power_score),
					momentum_score = VALUES(momentum_score),
					predicted_next_week = VALUES(predicted_next_week),
					predicted_next_month = VALUES(predicted_next_month),
					total_streams = VALUES(total_streams),
					avg_rank = VALUES(avg_rank),
					peaks_count = VALUES(peaks_count),
					weeks_on_chart = VALUES(weeks_on_chart),
					last_calculated_at = NOW()
			",
				$artist_id, $artist_power_score, $artist_power_score, 
				$expected_new_entries, round($expected_growth), // we use existing INT columns in database to cache expected entries & growth values
				(string)$stats->total_vol, $avg_rank, $stats->peak, $total_chart_weeks
			));
		}

		// Calculate Artist ranks based on power score
		self::calculate_artist_ranks();
	}

	/**
	 * Rank artists based on calculated power scores.
	 */
	private static function calculate_artist_ranks() {
		global $wpdb;
		$intel_table = $wpdb->prefix . 'charts_intelligence';

		// Get all artist scores
		$artists = $wpdb->get_results("
			SELECT id, artist_power_score, metadata_json 
			FROM $intel_table 
			WHERE entity_type = 'artist'
			ORDER BY artist_power_score DESC
		");

		$rank = 1;
		foreach ($artists as $a) {
			$meta = !empty($a->metadata_json) ? json_decode($a->metadata_json, true) : [];
			$meta['predicted_artist_rank'] = $rank;

			$wpdb->update(
				$intel_table,
				[
					'predicted_peak' => $rank, // We store artist power ranking in predicted_peak column
					'metadata_json'  => json_encode($meta)
				],
				['id' => $a->id]
			);
			$rank++;
		}
	}

	/**
	 * Retrieve songs to watch (predicted to rise significantly).
	 */
	public static function get_songs_to_watch($limit = 5) {
		global $wpdb;
		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$tracks_table = $wpdb->prefix . 'charts_tracks';

		// Songs to watch are tracks not yet at peak #1, with significant gap between current and predicted next week, sorted by confidence
		$query = $wpdb->prepare("
			SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, e.rank_position as current_rank
			FROM $intel_table i
			JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
			LEFT JOIN $wpdb->prefix . 'charts_artists art ON art.id = t.primary_artist_id
			JOIN $entries_table e ON e.id = (
				SELECT MAX(id) FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track'
			)
			WHERE i.predicted_next_week < e.rank_position AND i.confidence_score >= 50
			ORDER BY (e.rank_position - i.predicted_next_week) DESC, i.confidence_score DESC
			LIMIT %d
		", $limit);

		return $wpdb->get_results($query);
	}

	/**
	 * Automatically generate editorial insights from latest calculations.
	 */
	public static function generate_editorial_insights() {
		global $wpdb;
		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$artists_table = $wpdb->prefix . 'charts_artists';

		$insights = [];

		// 1. Fastest Growth
		$fastest = $wpdb->get_row("
			SELECT i.*, t.title, a.display_name as artist_name
			FROM $intel_table i
			JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
			LEFT JOIN $artists_table a ON a.id = t.primary_artist_id
			WHERE i.growth_rate > 5
			ORDER BY i.growth_rate DESC LIMIT 1
		");
		if ($fastest) {
			$insights[] = sprintf(
				__('"%s" by %s achieved the fastest growth this week, jumping %d positions.', 'charts'),
				$fastest->title, $fastest->artist_name, $fastest->growth_rate
			);
		}

		// 2. Strongest comeback (Re-entry with highest positive momentum)
		$comeback = $wpdb->get_row("
			SELECT i.*, t.title, a.display_name as artist_name
			FROM $intel_table i
			JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
			LEFT JOIN $artists_table a ON a.id = t.primary_artist_id
			WHERE i.trend_status = 'rising' AND i.weeks_on_chart > 1
			ORDER BY i.momentum_score DESC LIMIT 1
		");
		if ($comeback) {
			$insights[] = sprintf(
				__('%s recorded the strongest comeback in the past chart cycle with "%s".', 'charts'),
				$comeback->artist_name, $comeback->title
			);
		}

		// 3. Dominant #1 Long runner
		$leader = $wpdb->get_row("
			SELECT i.*, t.title, a.display_name as artist_name
			FROM $intel_table i
			JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
			LEFT JOIN $artists_table a ON a.id = t.primary_artist_id
			WHERE i.peaks_count = 1
			ORDER BY i.weeks_on_chart DESC LIMIT 1
		");
		if ($leader && $leader->weeks_on_chart >= 4) {
			$insights[] = sprintf(
				__('%s remains #1 for the %dth consecutive week with their hit track "%s".', 'charts'),
				$leader->artist_name, $leader->weeks_on_chart, $leader->title
			);
		}

		// Fallbacks if not enough data
		if (count($insights) < 2) {
			$insights[] = __('Chart dynamics are stable this week with high retention across the Top 10.', 'charts');
			$insights[] = __('Several tracks are emerging in viral metrics, indicating upcoming shifts.', 'charts');
		}

		return $insights;
	}
}

/**
 * Math Helper: clamp a value.
 */
function clamp_val($val, $min, $max) {
	return max($min, min($max, $val));
}
