<?php
/**
 * Kontentainment Charts — Bento Forecast Module (External)
 */
global $wpdb;

$intel_table = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';
$tracks_table = $wpdb->prefix . 'charts_tracks';
$artists_table = $wpdb->prefix . 'charts_artists';

// Load values
$songs_to_watch = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track' AND i.predicted_next_week < (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	)
	ORDER BY ( (SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1) - i.predicted_next_week ) DESC, i.confidence_score DESC
	LIMIT 4
");

$viral_alerts = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track' AND i.viral_score >= 65
	ORDER BY i.viral_score DESC LIMIT 5
");

$forecast_tracks = $wpdb->get_results("
	SELECT i.*, t.title as track_name, art.display_name as artist_name, t.cover_image, (
		SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1
	) as current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track'
	ORDER BY current_rank ASC LIMIT 15
");

$forecast_artists = $wpdb->get_results("
	SELECT i.*, a.display_name, a.image
	FROM $intel_table i
	JOIN $artists_table a ON a.id = i.entity_id
	WHERE i.entity_type = 'artist'
	ORDER BY i.artist_power_score DESC LIMIT 5
");

$editorial_insights = \Charts\Services\PredictionEngine::generate_editorial_insights();
?>

<div class="bento-grid">
	<!-- 1. EDITORIAL INSIGHTS STRIP (SPAN 4) -->
	<div class="bento-card span-4" style="background: linear-gradient(135deg, #1e1b4b, #2e1065); color: #fff; padding: 25px;">
		<div style="display:flex; align-items:center; gap: 15px; justify-content:space-between; width:100%;">
			<div style="display:flex; align-items:center; gap:15px;">
				<div style="background:rgba(255,255,255,0.1); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
					<span class="dashicons dashicons-analytics" style="color:#c084fc; font-size:20px; width:20px; height:20px;"></span>
				</div>
				<div>
					<h4 style="margin:0; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#c084fc;">Prediction Insights</h4>
					<p style="margin:4px 0 0; font-size:14px; font-weight:600; color:#e2e8f0;"><?php echo esc_html($editorial_insights[0] ?? ''); ?></p>
				</div>
			</div>
			<div style="font-size: 13px; color: #a78bfa; border-left: 1px solid rgba(255,255,255,0.15); padding-left: 20px; display:none; md-display:block; max-width:40%;">
				<?php echo esc_html($editorial_insights[1] ?? ''); ?>
			</div>
		</div>
	</div>

	<!-- 2. SONGS TO WATCH (SPAN 2) -->
	<div class="bento-card span-2">
		<label class="kpi-title">🔥 Songs To Watch</label>
		<div style="margin-top:24px;">
			<?php if (empty($songs_to_watch)) : ?>
				<p style="color:var(--db-text-muted); font-size:13px;">No velocity songs detected yet.</p>
			<?php else : ?>
				<?php foreach ($songs_to_watch as $song) : 
					$growth_pct = round((($song->current_rank - $song->predicted_next_week) / max(1, $song->current_rank)) * 100);
				?>
					<div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
						<img src="<?php echo esc_url($song->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:44px; height:44px; border-radius:8px; object-fit:cover; border:1px solid var(--db-border);">
						<div style="flex-grow:1; overflow:hidden;">
							<strong style="display:block; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($song->track_name); ?></strong>
							<span style="display:block; font-size:11px; color:var(--db-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($song->artist_name); ?></span>
						</div>
						<div style="text-align:right; flex-shrink:0;">
							<span style="display:block; font-size:13px; font-weight:900; color:var(--db-accent);">#<?php echo intval($song->predicted_next_week); ?></span>
							<span class="status-pill status-active" style="font-size:9px; padding:1px 5px;"><?php echo '+' . $growth_pct . '%'; ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- 3. VIRAL ALERTS (SPAN 2) -->
	<div class="bento-card span-2">
		<label class="kpi-title">⚡ Viral Alert System</label>
		<div style="margin-top:24px;">
			<?php if (empty($viral_alerts)) : ?>
				<p style="color:var(--db-text-muted); font-size:13px;">No active viral signals.</p>
			<?php else : ?>
				<?php foreach ($viral_alerts as $alert) : 
					$meta_data = !empty($alert->metadata_json) ? json_decode($alert->metadata_json, true) : [];
					$status = $meta_data['viral_status'] ?? 'Viral Emerging';
					$pill_class = 'status-active';
					if ($status === 'Viral Exploding') $pill_class = 'status-suspended'; // red
					elseif ($status === 'Viral Rising') $pill_class = 'status-active'; // green
				?>
					<div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
						<img src="<?php echo esc_url($alert->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:44px; height:44px; border-radius:8px; object-fit:cover; border:1px solid var(--db-border);">
						<div style="flex-grow:1; overflow:hidden;">
							<strong style="display:block; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($alert->track_name); ?></strong>
							<span style="display:block; font-size:11px; color:var(--db-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($alert->artist_name); ?></span>
						</div>
						<div style="text-align:right; flex-shrink:0;">
							<span class="status-pill <?php echo $pill_class; ?>" style="font-size:9px; padding:2px 6px; font-weight:800;"><?php echo strtoupper(str_replace('Viral ', '', $status)); ?></span>
							<span style="display:block; font-size:10px; font-weight:800; color:var(--db-text-dim); margin-top:3px;"><?php echo round($alert->viral_score); ?> pts</span>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- 4. TRACK FORECAST LIST (SPAN 4) -->
	<div class="bento-card span-4">
		<label class="kpi-title">Forecasting Matrix</label>
		<div style="margin-top:24px; overflow-x:auto;">
			<table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
				<thead>
					<tr style="border-bottom: 2px solid var(--db-border); color:var(--db-text-muted); font-weight:800; font-size:11px; text-transform:uppercase;">
						<th style="padding:10px 0;">Track</th>
						<th style="padding:10px;">Current</th>
						<th style="padding:10px;">Next Week</th>
						<th style="padding:10px;">Next Month</th>
						<th style="padding:10px;">Peak</th>
						<th style="padding:10px;" class="text-right">Confidence</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($forecast_tracks)) : ?>
						<tr><td colspan="6" style="padding:30px 0; text-align:center; color:var(--db-text-muted);">No tracks forecasted yet.</td></tr>
					<?php else : ?>
						<?php foreach ($forecast_tracks as $t) : ?>
							<tr style="border-bottom: 1px solid var(--db-border);">
								<td style="padding:12px 0; display:flex; align-items:center; gap:10px;">
									<img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:32px; height:32px; border-radius:6px; object-fit:cover;">
									<div style="overflow:hidden; max-width: 150px;">
										<strong style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($t->track_name); ?></strong>
										<span style="font-size:10px; color:var(--db-text-muted); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($t->artist_name); ?></span>
									</div>
								</td>
								<td style="padding:12px; font-weight:800;">#<?php echo intval($t->current_rank); ?></td>
								<td style="padding:12px; font-weight:800; color:#10b981;">
									#<?php echo intval($t->predicted_next_week); ?>
								</td>
								<td style="padding:12px; font-weight:800; color:#6366f1;">#<?php echo intval($t->predicted_next_month); ?></td>
								<td style="padding:12px; font-weight:800; color:var(--db-accent);">#<?php echo intval($t->predicted_peak); ?></td>
								<td style="padding:12px; font-weight:800;" class="text-right"><?php echo round($t->confidence_score); ?>%</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- 5. ARTIST AUTHORITY FORECASTS (SPAN 4) -->
	<div class="bento-card span-4">
		<label class="kpi-title">Artist Ranks & Expected Entries</label>
		<div style="margin-top:24px; overflow-x:auto;">
			<table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
				<thead>
					<tr style="border-bottom: 2px solid var(--db-border); color:var(--db-text-muted); font-weight:800; font-size:11px; text-transform:uppercase;">
						<th style="padding:10px 0;">Artist</th>
						<th style="padding:10px;">Predicted Rank</th>
						<th style="padding:10px;">Power Score</th>
						<th style="padding:10px;">Expected New Entries</th>
						<th style="padding:10px;" class="text-right">Expected Growth</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($forecast_artists)) : ?>
						<tr><td colspan="5" style="padding:30px 0; text-align:center; color:var(--db-text-muted);">No artists found.</td></tr>
					<?php else : ?>
						<?php foreach ($forecast_artists as $art) : 
							$meta_data = !empty($art->metadata_json) ? json_decode($art->metadata_json, true) : [];
							$pred_rank = $meta_data['predicted_artist_rank'] ?? $art->predicted_peak;
						?>
							<tr style="border-bottom: 1px solid var(--db-border);">
								<td style="padding:12px 0; display:flex; align-items:center; gap:10px;">
									<img src="<?php echo esc_url($art->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
									<strong style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($art->display_name); ?></strong>
								</td>
								<td style="padding:12px; font-weight:800;">#<?php echo intval($pred_rank); ?></td>
								<td style="padding:12px; font-weight:800; color:#6366f1;"><?php echo round($art->artist_power_score); ?></td>
								<td style="padding:12px; font-weight:800;"><?php echo intval($art->predicted_next_week); ?> new tracks</td>
								<td style="padding:12px; font-weight:800; color:#10b981;" class="text-right"><?php echo round($art->predicted_next_month); ?>%</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
