<?php
/**
 * Kontentainment Charts — Forecast Dashboard View
 * Displays rule-based weekly & monthly forecasting metrics.
 */

global $wpdb;

$intel_table = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';
$tracks_table = $wpdb->prefix . 'charts_tracks';
$artists_table = $wpdb->prefix . 'charts_artists';

// Filters
$filter_market = sanitize_text_field($_GET['forecast_market'] ?? 'all');

// Build query conditions
$where_track = "WHERE i.entity_type = 'track'";
$where_artist = "WHERE i.entity_type = 'artist'";

if ($filter_market !== 'all') {
	$market_condition = $wpdb->prepare(" AND i.entity_id IN (
		SELECT DISTINCT e.item_id FROM $entries_table e 
		JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id 
		WHERE s.country_code = %s AND e.item_type = i.entity_type
	)", $filter_market);
	
	$where_track .= $market_condition;
	$where_artist .= $market_condition;
}

// 1. Get Songs to Watch (predicted to rise)
$songs_to_watch = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	$where_track AND i.predicted_next_week < (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	)
	ORDER BY ( (SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1) - i.predicted_next_week ) DESC, i.confidence_score DESC
	LIMIT 5
");

// 2. Get Viral Alerts (Emerging, Rising, Exploding)
$viral_alerts = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	$where_track AND i.viral_score >= 65
	ORDER BY i.viral_score DESC LIMIT 6
");

// 3. Get Forecast List (Tracks)
$forecast_tracks = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	$where_track
	ORDER BY current_rank ASC LIMIT 25
");

// 4. Get Artist Forecast List
$forecast_artists = $wpdb->get_results("
	SELECT i.*, a.display_name, a.image
	FROM $intel_table i
	JOIN $artists_table a ON a.id = i.entity_id
	$where_artist
	ORDER BY i.artist_power_score DESC LIMIT 10
");

// 5. Generate insights dynamically
$editorial_insights = \Charts\Services\PredictionEngine::generate_editorial_insights();
?>

<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header intel-nexus-header">
		<div class="header-main">
			<div class="intel-badge"><?php _e( 'Intelligence → Prediction Engine', 'charts' ); ?></div>
			<h1 class="charts-admin-title"><?php _e( 'Chart Performance Forecast', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Bloomberg-style predictive indicators, weekly/monthly targets, and artist authority forecasting.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<form method="get" action="" class="filter-nexus-form" style="display:inline-block; margin-right: 15px;">
				<input type="hidden" name="page" value="charts-forecast">
				<select name="forecast_market" onchange="this.form.submit()" class="kb-input" style="height: 40px; line-height: 1; padding: 0 15px; font-weight:800; background:#fff; border-radius:8px;">
					<option value="all"><?php _e( 'Global Market Signal', 'charts' ); ?></option>
					<?php 
					$markets = get_option('charts_markets', []);
					foreach ($markets as $m) : ?>
						<option value="<?php echo esc_attr($m['code']); ?>" <?php selected($filter_market, $m['code']); ?>>
							<?php echo esc_html($m['name']); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
			<button class="charts-btn-secondary premium-pulse" onclick="recalculateIntelligence()" id="intel-recalc-btn">
				<span class="dashicons dashicons-update"></span>
				<?php _e( 'Re-polarize Engine', 'charts' ); ?>
			</button>
		</div>
	</header>

	<!-- EDITORIAL INSIGHTS STRIP -->
	<div class="editorial-insights-strip" style="background: linear-gradient(135deg, #1e1b4b, #311042); color: #fff; padding: 20px 30px; border-radius: 16px; margin-bottom: 32px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(49, 16, 66, 0.15);">
		<div class="insights-icon" style="background: rgba(255,255,255,0.1); width: 44px; height: 44px; border-radius: 12px; display:flex; align-items:center; justify-content:center; flex-shrink: 0;">
			<span class="dashicons dashicons-text-page" style="font-size:22px; width:22px; height:22px; color: #a5b4fc;"></span>
		</div>
		<div class="insights-ticker" style="flex-grow: 1;">
			<strong style="display:block; text-transform: uppercase; font-size:11px; letter-spacing:0.1em; color: #a5b4fc; margin-bottom: 2px;">Editorial Insights</strong>
			<div class="insights-text-container" style="font-size: 14px; font-weight: 600;">
				<?php echo esc_html($editorial_insights[0] ?? ''); ?>
			</div>
		</div>
		<div class="insights-secondary" style="font-size: 13px; color: #cbd5e1; border-left: 1px solid rgba(255,255,255,0.15); padding-left: 20px; display:none; md-display:block;">
			<?php echo esc_html($editorial_insights[1] ?? ''); ?>
		</div>
	</div>

	<div class="intel-workspace">
		<div class="intel-main-grid">

			<!-- SONGS TO WATCH SLIDER (SPAN 8) -->
			<div class="intel-block-card span-8">
				<header class="block-header">
					<div class="block-title-wrap">
						<span class="block-tag purple"><?php _e( '🔥 Songs To Watch', 'charts' ); ?></span>
						<h3><?php _e( 'Next Period High-Velocity Rising Songs', 'charts' ); ?></h3>
					</div>
				</header>
				<div class="block-body" style="padding: 24px;">
					<?php if (empty($songs_to_watch)) : ?>
						<div style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?php _e( 'No rising songs detected yet. Try importing more historical cycles.', 'charts' ); ?></div>
					<?php else : ?>
						<div class="songs-to-watch-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
							<?php foreach ($songs_to_watch as $song) : 
								$growth_pct = round((($song->current_rank - $song->predicted_next_week) / max(1, $song->current_rank)) * 100);
							?>
								<div class="watch-card" style="background: #fafafa; border: 1px solid #f1f5f9; border-radius: 14px; padding: 15px; position: relative; overflow: hidden; transition: all 0.3s ease;">
									<div class="watch-rank-badge" style="position: absolute; top: 12px; right: 12px; background: #6366f1; color: #fff; font-size:10px; font-weight: 900; padding: 2px 6px; border-radius: 4px;">
										<?php echo '+' . $growth_pct . '%'; ?>
									</div>
									<img src="<?php echo esc_url($song->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100%; height: 140px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; border: 1px solid #e2e8f0;">
									<strong style="display:block; font-size:14px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--charts-primary);"><?php echo esc_html($song->track_name); ?></strong>
									<span style="display:block; font-size:11px; color:#64748b; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($song->artist_name); ?></span>
									
									<div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:10px; margin-top:5px; font-size:11px; font-weight:700;">
										<div>
											<span style="color:#94a3b8; display:block; font-size:9px; text-transform:uppercase;">Current</span>
											<span style="font-size:13px; color:#1e293b;">#<?php echo intval($song->current_rank); ?></span>
										</div>
										<div style="text-align: right;">
											<span style="color:#94a3b8; display:block; font-size:9px; text-transform:uppercase;">Predicted</span>
											<span style="font-size:13px; color:#10b981;">#<?php echo intval($song->predicted_next_week); ?></span>
										</div>
									</div>
									
									<div style="margin-top: 10px; display:flex; align-items:center; justify-content:space-between; font-size:10px; color:#64748b; font-weight: 700;">
										<span>Confidence:</span>
										<span style="color:#6366f1; font-weight:800;"><?php echo round($song->confidence_score); ?>%</span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- VIRAL ALERT SYSTEM (SPAN 4) -->
			<div class="intel-block-card span-4">
				<header class="block-header">
					<div class="block-title-wrap">
						<span class="block-tag" style="color: #fe025b;"><?php _e( '⚡ Viral Alert System', 'charts' ); ?></span>
						<h3><?php _e( 'Real-time Signal Spikes', 'charts' ); ?></h3>
					</div>
				</header>
				<div class="block-body no-padding">
					<div class="viral-alerts-list" style="padding: 10px 24px 24px;">
						<?php if (empty($viral_alerts)) : ?>
							<div style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?php _e( 'No active viral alerts in this market.', 'charts' ); ?></div>
						<?php else : ?>
							<?php foreach ($viral_alerts as $alert) : 
								$meta_data = !empty($alert->metadata_json) ? json_decode($alert->metadata_json, true) : [];
								$status = $meta_data['viral_status'] ?? 'Viral Emerging';
								$class = 'is-stable';
								if ($status === 'Viral Exploding') $class = 'is-falling'; // red
								elseif ($status === 'Viral Rising') $class = 'is-new'; // blue
								elseif ($status === 'Viral Emerging') $class = 'is-rising'; // green
							?>
								<div class="viral-alert-item" style="display:flex; align-items:center; gap: 12px; padding: 15px 0; border-bottom: 1px solid #f8fafc;">
									<img src="<?php echo esc_url($alert->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 42px; height: 42px; border-radius: 8px; object-fit: cover;">
									<div style="flex-grow: 1; overflow:hidden;">
										<strong style="display:block; font-size:13px; color: var(--charts-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($alert->track_name); ?></strong>
										<span style="font-size:10px; color:#94a3b8; font-weight:700;"><?php echo esc_html($alert->artist_name); ?></span>
									</div>
									<div style="text-align: right; flex-shrink: 0;">
										<span class="intel-status-tag <?php echo $class; ?>" style="font-size:8px; padding:3px 6px; font-weight:900;"><?php echo esc_html(strtoupper($status)); ?></span>
										<span style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-top:3px;"><?php echo round($alert->viral_score); ?> pts</span>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- CORE FORECAST LIST (SPAN 12) -->
			<div class="intel-block-card span-12">
				<header class="block-header">
					<div class="block-title-wrap">
						<span class="block-tag success"><?php _e( 'Forecasting Matrix', 'charts' ); ?></span>
						<h3><?php _e( 'Historical Stability & Future Expected Ranks', 'charts' ); ?></h3>
					</div>
				</header>
				<div class="block-body no-padding">
					<table class="intel-mini-table is-wide">
						<thead>
							<tr>
								<th><?php _e( 'Track Detail', 'charts' ); ?></th>
								<th><?php _e( 'Current Rank', 'charts' ); ?></th>
								<th><?php _e( 'Expected Next Week', 'charts' ); ?></th>
								<th><?php _e( 'Expected Next Month', 'charts' ); ?></th>
								<th><?php _e( 'Peak Position', 'charts' ); ?></th>
								<th><?php _e( 'Trend Direction', 'charts' ); ?></th>
								<th><?php _e( 'Confidence', 'charts' ); ?></th>
								<th class="text-right"><?php _e( 'Momentum / Viral Meters', 'charts' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($forecast_tracks)) : ?>
								<tr><td colspan="8" class="text-center" style="padding: 50px; color: #94a3b8;"><?php _e( 'Run calculations or import CSV charts to populate the forecasting matrix.', 'charts' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ($forecast_tracks as $t) : 
									$meta_data = !empty($t->metadata_json) ? json_decode($t->metadata_json, true) : [];
									$trend_dir = $meta_data['trend_direction'] ?? 'Stable';
									
									$dir_class = 'is-stable';
									if ($trend_dir === 'Strong Upward') $dir_class = 'is-rising';
									elseif ($trend_dir === 'Upward') $dir_class = 'is-new';
									elseif ($trend_dir === 'Declining') $dir_class = 'is-falling';
								?>
									<tr>
										<td>
											<div class="asset-flex">
												<img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="asset-thumb">
												<div class="asset-meta">
													<span class="asset-name" style="font-weight: 800; font-size:13px;"><?php echo esc_html($t->track_name); ?></span>
													<span class="asset-sub"><?php echo esc_html($t->artist_name); ?></span>
												</div>
											</div>
										</td>
										<td style="font-weight: 900; font-size:14px; color:#1e293b;">#<?php echo intval($t->current_rank); ?></td>
										<td style="font-weight: 900; font-size:14px; color:#10b981;">
											#<?php echo intval($t->predicted_next_week); ?>
											<?php if ($t->predicted_next_week < $t->current_rank) : ?>
												<span class="dashicons dashicons-arrow-up-alt2" style="font-size:14px; width:14px; height:14px; color:#10b981; vertical-align:middle;"></span>
											<?php elseif ($t->predicted_next_week > $t->current_rank) : ?>
												<span class="dashicons dashicons-arrow-down-alt2" style="font-size:14px; width:14px; height:14px; color:#ef4444; vertical-align:middle;"></span>
											<?php endif; ?>
										</td>
										<td style="font-weight: 800; font-size:14px; color:#6366f1;">#<?php echo intval($t->predicted_next_month); ?></td>
										<td>
											<span style="background: rgba(99, 102, 241, 0.1); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.2); font-size: 11px; font-weight: 900; padding: 4px 8px; border-radius: 6px;">
												PEAK #<?php echo intval($t->predicted_peak); ?>
											</span>
										</td>
										<td>
											<span class="intel-status-tag <?php echo $dir_class; ?>" style="font-size:9px; padding:4px 8px;">
												<?php echo esc_html(strtoupper($trend_dir)); ?>
											</span>
										</td>
										<td style="font-weight: 800; font-size: 13px; color:#1e293b;">
											<?php echo round($t->confidence_score); ?>%
										</td>
										<td class="text-right">
											<div style="display:flex; flex-direction:column; align-items:flex-end; gap: 4px;">
												<div style="display:flex; align-items:center; gap:8px;">
													<span style="font-size:9px; color:#94a3b8; font-weight:700;">Momentum</span>
													<div class="v-track-bg" style="width: 60px; height: 4px; background:#f1f5f9;">
														<div class="v-track-fill" style="width: <?php echo min(100, $t->momentum_score); ?>%; height:100%; background:#6366f1;"></div>
													</div>
												</div>
												<div style="display:flex; align-items:center; gap:8px;">
													<span style="font-size:9px; color:#94a3b8; font-weight:700;">Viral</span>
													<div class="v-track-bg" style="width: 60px; height: 4px; background:#f1f5f9;">
														<div class="v-track-fill" style="width: <?php echo min(100, $t->viral_score); ?>%; height:100%; background:#fe025b;"></div>
													</div>
												</div>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- ARTIST FORECASTS PANEL (SPAN 12) -->
			<div class="intel-block-card span-12">
				<header class="block-header">
					<div class="block-title-wrap">
						<span class="block-tag" style="color: #6366f1;"><?php _e( 'Verification & Ranks', 'charts' ); ?></span>
						<h3><?php _e( 'Artist Authority Rank & Expected New Entries', 'charts' ); ?></h3>
					</div>
				</header>
				<div class="block-body no-padding">
					<table class="intel-mini-table is-wide">
						<thead>
							<tr>
								<th><?php _e( 'Artist Profile', 'charts' ); ?></th>
								<th><?php _e( 'Predicted Rank', 'charts' ); ?></th>
								<th><?php _e( 'Artist Power Score', 'charts' ); ?></th>
								<th><?php _e( 'Expected New Entries', 'charts' ); ?></th>
								<th class="text-right"><?php _e( 'Expected Growth %', 'charts' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($forecast_artists)) : ?>
								<tr><td colspan="5" class="text-center" style="padding: 50px; color: #94a3b8;"><?php _e( 'Calculate intelligence data to forecast artist dominance.', 'charts' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ($forecast_artists as $art) : 
									$meta_data = !empty($art->metadata_json) ? json_decode($art->metadata_json, true) : [];
									$pred_rank = $meta_data['predicted_artist_rank'] ?? $art->predicted_peak;
								?>
									<tr>
										<td>
											<div class="asset-flex">
												<img src="<?php echo esc_url($art->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
												<div class="asset-meta">
													<span class="asset-name" style="font-weight:800; font-size:13px;"><?php echo esc_html($art->display_name); ?></span>
												</div>
											</div>
										</td>
										<td style="font-weight:900; font-size:14px; color:#1e293b;">#<?php echo intval($pred_rank); ?></td>
										<td>
											<div style="display:flex; align-items:center; gap:10px;">
												<span style="font-weight: 900; font-size:14px; color:#6366f1; min-width:30px;"><?php echo round($art->artist_power_score); ?></span>
												<div class="v-track-bg" style="width: 100px; height: 6px; background:#f1f5f9; border-radius:3px;">
													<div class="v-track-fill" style="width: <?php echo min(100, $art->artist_power_score); ?>%; height:100%; background:linear-gradient(90deg, #6366f1, #fe025b); border-radius:3px;"></div>
												</div>
											</div>
										</td>
										<td style="font-weight:800; font-size:13px; color:#1e293b;">
											<?php echo intval($art->predicted_next_week); ?> new entries
										</td>
										<td class="text-right" style="font-weight:900; font-size:14px; color:#10b981;">
											<?php echo round($art->predicted_next_month); ?>%
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>
</div>
