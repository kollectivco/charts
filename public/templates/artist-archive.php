<?php
/**
 * Kontentainment Charts — Artist Archive (Light Mode)
 */

global $wpdb;

// Fetch all artists with a count of their chart appearances from legacy SQL baseline
$artists = $wpdb->get_results( "
	SELECT a.id, a.display_name, a.slug, a.image,
	       i.total_streams, i.weeks_on_chart, i.momentum_score, i.trend_status,
	       (SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = a.id AND e.item_type = 'artist') as appearance_count,
	       (SELECT MIN(rank_position) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = a.id AND e.item_type = 'artist') as peak_rank
	FROM {$wpdb->prefix}charts_artists a
	LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = a.id AND i.entity_type = 'artist'
	GROUP BY a.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, a.display_name ASC
" );

// 1. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-artists-archive.php'; exit;
    return;
}

if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
?>

<div class="kc-root">
	
	<div class="kc-container">
		

		<header class="kc-page-hero" style="padding: 40px 0 20px;">
			<div class="kc-eyebrow">Discovery</div>
			<h1 class="kc-page-title">Top Artists</h1>
			<p style="font-size: 13px; color: var(--k-text-dim); max-width: 600px; font-weight: 500;">
				Browse the most influential voices currently shaping the regional music charts.
			</p>
		</header>

		<main class="kc-section" style="padding-top: 40px; padding-bottom: 120px;">
			
			<?php if ( empty( $artists ) ) : ?>
				<div style="padding: 80px; text-align: center; border: 2px dashed var(--k-border); border-radius: 24px;">
					<p style="font-weight: 800; color: var(--k-text-muted);">No artists found with active rankings.</p>
				</div>
			<?php else : ?>
				<div class="kc-rows-list">
					<?php foreach ( $artists as $artist ) : 
						$url  = home_url( '/charts/artist/' . $artist->slug . '/' );
						$img  = !empty($artist->image) ? $artist->image : CHARTS_URL . 'public/assets/img/placeholder.png';
					?>
					<div class="kc-row-item">
						<header class="kc-row-header">
							<img src="<?php echo esc_url( $img ); ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; box-shadow: var(--k-shadow-sm);">
							<div style="flex-grow: 1;">
								<h3 style="font-size: 18px; font-weight: 950; color: var(--k-text); margin: 0;" class="<?php echo \Charts\Core\Typography::get_font_class( $artist->display_name ); ?>"><?php echo esc_html( $artist->display_name ); ?></h3>
								<span style="display: block; font-size: 11px; font-weight: 850; color: var(--k-text-dim); margin-top: 4px; text-transform: uppercase;">Indexed Entity</span>
							</div>
							<div class="kc-row-meta" style="display: flex; gap: 40px; margin-right: 40px; text-align: right;">
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Top Peak</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-text);">#<?php echo $artist->peak_rank ?: '—'; ?></span>
								</div>
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Chart Entries</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-accent);"><?php echo number_format( $artist->appearance_count ); ?></span>
								</div>
							</div>
							<div class="kc-chevron-toggle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
							</div>
						</header>

						<!-- EXTANDABLE DETAILS PANEL -->
						<div class="kc-details-inner">
							<div class="kc-details-grid" style="grid-template-columns: repeat(4, 1fr); gap: 24px;">
								<div class="kc-details-item">
									<label>Lifetime Peak</label>
									<span>#<?php echo $artist->peak_rank ?: '—'; ?></span>
								</div>
								<div class="kc-details-item">
									<label>Weeks on Chart</label>
									<span><?php echo intval($artist->weeks_on_chart ?: 1); ?></span>
								</div>
								<div class="kc-details-item">
									<label>Momentum Score</label>
									<span><?php echo number_format($artist->momentum_score ?: 0, 1); ?> / 100</span>
								</div>
								<div class="kc-details-item" style="text-align: right;">
									<a href="<?php echo esc_url( $url ); ?>" class="kc-view-all" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Insight Report &rarr;</a>
								</div>
							</div>
							
							<?php if ( ! empty($artist->total_streams) ) : ?>
							<div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--k-border); display: flex; gap: 40px;">
								<div class="kc-details-item">
									<label>Total Market Coverage</label>
									<span><?php echo number_format($artist->total_streams); ?> Calculated Views</span>
								</div>
								<div class="kc-details-item">
									<label>Trend Status</label>
									<span style="text-transform: capitalize; color: var(--k-accent); font-weight: 800;"><?php echo esc_html($artist->trend_status ?: 'Stable'); ?></span>
								</div>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</main>

	</div>
</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); } ?>
