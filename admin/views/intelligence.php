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

<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Market Insights', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Advanced market intelligence and historical trend analysis.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<?php if ($has_data) : ?>
				<button class="charts-btn-back" onclick="recalculateInsights()">
					<span class="dashicons dashicons-update" style="margin-right:8px;"></span>
					<?php _e( 'Refresh Analytics', 'charts' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

			</header>
			<table class="charts-table">
				<thead>
					<tr>
						<th><?php _e( 'Track', 'charts' ); ?></th>
						<th><?php _e( 'Growth', 'charts' ); ?></th>
						<th><?php _e( 'Trend', 'charts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($fastest_risers as $t): ?>
					<tr>
						<td>
							<div style="display:flex; align-items:center; gap:10px;">
								<img src="<?php echo esc_url($t->cover_image); ?>" style="width:30px; height:30px; border-radius:4px; border:1px solid var(--charts-border);">
								<div style="font-weight:800; line-height:1.2; font-size:12px; color:var(--charts-primary);">
									<?php echo esc_html($t->track_name); ?><br>
									<span style="opacity:0.5; font-weight:600; font-size:10px;"><?php echo esc_html($t->artist_names); ?></span>
								</div>
							</div>
						</td>
						<td style="font-weight:700; color:var(--charts-success);">+<?php echo number_format($t->growth_rate, 1); ?>%</td>
						<td><span class="status-badge <?php echo ( $t->trend_status === 'rising' || $t->trend_status === 'new' ) ? 'status-success' : 'status-pending'; ?>"><?php echo strtoupper($t->trend_status); ?></span></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Hot Artists -->
		<div class="charts-table-card">
			<header class="table-header">
				<h2 class="table-title"><?php _e( 'Hot Artists', 'charts' ); ?></h2>
				<div style="font-size:11px; color:var(--charts-text-dim); font-weight:700;">
					<?php _e( 'Market Authority', 'charts' ); ?>
				</div>
			</header>
			<table class="charts-table">
				<thead>
					<tr>
						<th><?php _e( 'Artist', 'charts' ); ?></th>
						<th><?php _e( 'Hotness', 'charts' ); ?></th>
						<th><?php _e( 'Peak', 'charts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($hot_artists as $a): ?>
					<tr>
						<td>
							<div style="display:flex; align-items:center; gap:10px;">
								<img src="<?php echo esc_url($a->image); ?>" style="width:30px; height:30px; border-radius:50%; border:1px solid var(--charts-border);">
								<div style="font-weight:800; line-height:1.2; font-size:12px; color:var(--charts-primary);">
									<?php echo esc_html($a->display_name); ?>
								</div>
							</div>
						</td>
						<td style="font-weight:700; color:var(--charts-accent-purple);"><?php echo number_format($a->momentum_score, 1); ?></td>
						<td style="font-weight:700; color:var(--charts-primary);">#<?php echo $a->peaks_count; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
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
