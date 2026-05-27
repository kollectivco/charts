<?php
/**
 * Kontentainment Charts — Intelligence Dashboard
 */
global $wpdb;

$intel_table = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';

// 1. Top Trending Tracks
$trending_tracks = $wpdb->get_results("
	SELECT i.*, e.track_name, e.artist_names, e.cover_image, e.rank_position
	FROM $intel_table i
	JOIN $entries_table e ON e.item_id = i.entity_id AND e.item_type = i.entity_type
	WHERE i.entity_type = 'track'
	GROUP BY i.entity_id
	ORDER BY i.momentum_score DESC LIMIT 5
");

// 2. Fastest Risers
$fastest_risers = $wpdb->get_results("
	SELECT i.*, e.track_name, e.artist_names, e.cover_image
	FROM $intel_table i
	JOIN $entries_table e ON e.item_id = i.entity_id AND e.item_type = i.entity_type
	WHERE i.entity_type = 'track'
	GROUP BY i.entity_id
	ORDER BY i.growth_rate DESC LIMIT 5
");

// 3. Hot Artists
$hot_artists = $wpdb->get_results("
	SELECT i.*, a.display_name, a.image
	FROM $intel_table i
	JOIN {$wpdb->prefix}charts_artists a ON a.id = i.entity_id
	WHERE i.entity_type = 'artist'
	ORDER BY i.momentum_score DESC LIMIT 5
");
?>

<div class="wrap kc-admin-wrap">
	<h1 class="wp-heading-inline">Music Intelligence & Analytics</h1>
	<hr class="wp-header-end">

	<div class="kc-intel-dashboard" style="margin-top: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
		
		<!-- Trending Tracks -->
		<div class="kc-intel-col postbox">
			<h2 class="hndle"><span>Top Trending Tracks</span></h2>
			<div class="inside">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Track</th>
							<th>Momentum</th>
							<th>Growth</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($trending_tracks as $t): ?>
						<tr>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<img src="<?php echo esc_url($t->cover_image); ?>" style="width:30px; height:30px; border-radius:4px;">
									<div style="font-weight:600; line-height:1.2; font-size:12px;">
										<?php echo esc_html($t->track_name); ?><br>
										<span style="opacity:0.5; font-weight:400;"><?php echo esc_html($t->artist_names); ?></span>
									</div>
								</div>
							</td>
							<td style="font-weight:700; color: #6366f1;"><?php echo number_format($t->momentum_score, 1); ?></td>
							<td>+<?php echo number_format($t->growth_rate, 1); ?>%</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Fastest Risers -->
		<div class="kc-intel-col postbox">
			<h2 class="hndle"><span>Fastest Risers</span></h2>
			<div class="inside">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Track</th>
							<th>Growth</th>
							<th>Trend</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($fastest_risers as $t): ?>
						<tr>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<img src="<?php echo esc_url($t->cover_image); ?>" style="width:30px; height:30px; border-radius:4px;">
									<div style="font-weight:600; line-height:1.2; font-size:12px;">
										<?php echo esc_html($t->track_name); ?><br>
										<span style="opacity:0.5; font-weight:400;"><?php echo esc_html($t->artist_names); ?></span>
									</div>
								</div>
							</td>
							<td style="font-weight:700; color:#22c55e;">+<?php echo number_format($t->growth_rate, 1); ?>%</td>
							<td><span class="kc-status-pill <?php echo $t->trend_status; ?>"><?php echo strtoupper($t->trend_status); ?></span></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Hot Artists -->
		<div class="kc-intel-col postbox">
			<h2 class="hndle"><span>Hot Artists</span></h2>
			<div class="inside">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Artist</th>
							<th>Hotness</th>
							<th>Peak</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($hot_artists as $a): ?>
						<tr>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<img src="<?php echo esc_url($a->image); ?>" style="width:30px; height:30px; border-radius:50%;">
									<div style="font-weight:600; line-height:1.2; font-size:12px;">
										<?php echo esc_html($a->display_name); ?>
									</div>
								</div>
							</td>
							<td style="font-weight:700; color:#8b5cf6;"><?php echo number_format($a->momentum_score, 1); ?></td>
							<td>#<?php echo $a->peaks_count; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

	</div>

	<div class="kc-intel-actions" style="margin-top: 40px;">
		<button class="button button-secondary" onclick="recalculateIntelligence()">Recalculate All Intelligence</button>
	</div>

	<style>
		.kc-status-pill { font-size: 8px; font-weight: 800; padding: 2px 6px; border-radius: 4px; color: white; }
		.kc-status-pill.rising { background: #22c55e; }
		.kc-status-pill.falling { background: #ef4444; }
		.kc-status-pill.new { background: #6366f1; }
		.kc-status-pill.stable { background: #64748b; }
	</style>

	<script>
		function recalculateIntelligence() {
			const btn = event.target;
			btn.disabled = true;
			btn.innerText = 'Calculating...';
			
			jQuery.post(ajaxurl, {
				action: 'charts_recalculate_intel',
				nonce: '<?php echo wp_create_nonce("charts_intel"); ?>'
			}, function(res) {
				alert('Intelligence recalculation finished.');
				location.reload();
			});
		}
	</script>
</div>
