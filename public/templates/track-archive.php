<?php
/**
 * Kontentainment Charts — Track Archive (Light Mode)
 */

global $wpdb;

// Fetch all tracks with their chart appearance counts from legacy SQL baseline
$tracks = $wpdb->get_results( "
	SELECT t.id, t.title, t.slug, t.cover_image, a.display_name as artist_name, a.slug as artist_slug,
	       (SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = t.id AND e.item_type = 'track') as appearance_count,
	       (SELECT MIN(rank_position) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = t.id AND e.item_type = 'track') as peak_rank,
	       (SELECT MIN(period_id) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = t.id AND e.item_type = 'track') as first_period_id
	FROM {$wpdb->prefix}charts_tracks t
	LEFT JOIN {$wpdb->prefix}charts_artists a ON a.id = t.primary_artist_id
	GROUP BY t.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, t.title ASC
	LIMIT 100
" );

// 1. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-tracks-archive.php'; exit;
    return;
}

if ( ! $is_mobile ) { if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); } }
?>

<div class="kc-container">
	

	<header class="kc-page-hero" style="padding: 40px 0 20px;">
			<div class="kc-eyebrow">Intelligence</div>
			<h1 class="kc-page-title">Top Tracks</h1>
			<p style="font-size: 13px; color: var(--k-text-dim); max-width: 600px; font-weight: 500;">
				Explore the most successful tracks currently making waves across regional music charts.
			</p>
		</header>

		<main class="kc-section" style="padding-top: 40px; padding-bottom: 120px;">
			
			<?php if ( empty( $tracks ) ) : ?>
				<div style="padding: 80px; text-align: center; border: 2px dashed var(--k-border); border-radius: 24px;">
					<p style="font-weight: 800; color: var(--k-text-muted);">No tracks found with active rankings.</p>
				</div>
			<?php else : ?>
				<div class="kc-rows-list">
					<?php foreach ( $tracks as $track ) : 
						$url  = home_url( '/charts/track/' . $track->slug . '/' );
						$img  = !empty($track->cover_image) ? $track->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png';
						
						// Fetch additional details if needed, or use existing row data
						$first_entry_date = $track->first_period_id ? $wpdb->get_var($wpdb->prepare("SELECT period_start FROM {$wpdb->prefix}charts_periods WHERE id = %d", $track->first_period_id)) : '—';
					?>
					<div class="kc-row-item">
						<header class="kc-row-header">
							<div style="width: 48px; flex-shrink: 0; font-size: 14px; font-weight: 900; color: var(--k-text-dim); text-align: center; border-right: 1px solid var(--k-border); margin-right: 24px; padding-right: 24px;">
								#<?php echo str_pad( $track->id, 2, '0', STR_PAD_LEFT ); ?>
							</div>
							<img src="<?php echo esc_url( $img ); ?>" style="width: 64px; height: 64px; border-radius: 8px; object-fit: cover;">
							<div style="flex-grow: 1;">
								<h3 style="font-size: 18px; font-weight: 950; color: var(--k-text); margin: 0; line-height: 1.2;" class="<?php echo \Charts\Core\Typography::get_font_class($track->title); ?>"><?php echo esc_html( $track->title ); ?></h3>
								<span style="display: block; font-size: 12px; font-weight: 700; color: var(--k-text-muted); margin-top: 4px;" class="<?php echo \Charts\Core\Typography::get_font_class($track->artist_name); ?>"><?php echo esc_html( $track->artist_name ); ?></span>
							</div>
							<div class="kc-row-meta" style="display: flex; gap: 40px; margin-right: 40px; text-align: right;">
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Appearances</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-accent);"><?php echo number_format( $track->appearance_count ); ?></span>
								</div>
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Peak</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-text);">#<?php echo $track->peak_rank ?: '—'; ?></span>
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
									<label>Peak Position</label>
									<span>#<?php echo $track->peak_rank ?: '—'; ?></span>
								</div>
								<div class="kc-details-item">
									<label>First Chart Entry</label>
									<span><?php echo $first_entry_date !== '—' ? date('M j, Y', strtotime($first_entry_date)) : '—'; ?></span>
								</div>
								<div class="kc-details-item">
									<label>Lifetime Weeks</label>
									<span><?php echo intval($track->weeks_on_chart ?: 1); ?></span>
								</div>
								<div class="kc-details-item" style="text-align: right;">
									<a href="<?php echo esc_url( $url ); ?>" class="kc-view-all" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Insight Report &rarr;</a>
								</div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</main>

	</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php if ( ! $is_mobile ) { if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); } } ?>
