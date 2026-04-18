<?php
/**
 * Kontentainment Charts — Video Archive (Light Mode)
 */

global $wpdb;

// Fetch all videos with their chart appearance counts
$videos = $wpdb->get_results( "
	SELECT v.id, v.title, v.slug, v.thumbnail as cover_image, a.display_name as artist_name, a.slug as artist_slug,
	       (SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = v.id AND e.item_type = 'video') as appearance_count,
	       (SELECT MIN(rank_position) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = v.id AND e.item_type = 'video') as peak_rank,
	       (SELECT MIN(period_id) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = v.id AND e.item_type = 'video') as first_period_id
	FROM {$wpdb->prefix}charts_videos v
	LEFT JOIN {$wpdb->prefix}charts_video_artists va ON va.video_id = v.id
	LEFT JOIN {$wpdb->prefix}charts_artists a ON a.id = va.artist_id
	GROUP BY v.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, v.title ASC
	LIMIT 100
" );

// 1. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-videos-archive.php';
    return;
}

\Charts\Core\PublicIntegration::get_header();
?>

<div class="kc-container">
	

	<header class="kc-page-hero" style="padding: 40px 0 20px;">
			<div class="kc-eyebrow">Intelligence</div>
			<h1 class="kc-page-title">Top Videos</h1>
			<p style="font-size: 13px; color: var(--k-text-dim); max-width: 600px; font-weight: 500;">
				Explore the most trending video clips currently capturing the market's attention.
			</p>
		</header>

		<main class="kc-section" style="padding-top: 40px; padding-bottom: 120px;">
			
			<?php if ( empty( $videos ) ) : ?>
				<div style="padding: 80px; text-align: center; border: 2px dashed var(--k-border); border-radius: 24px;">
					<p style="font-weight: 800; color: var(--k-text-muted);">No videos found with active rankings.</p>
				</div>
			<?php else : ?>
				<div class="kc-rows-list">
					<?php foreach ( $videos as $video ) : 
						$url  = home_url( '/charts/video/' . $video->slug . '/' );
						$img  = !empty($video->cover_image) ? $video->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png';
						
						$first_entry_date = $video->first_period_id ? $wpdb->get_var($wpdb->prepare("SELECT period_start FROM {$wpdb->prefix}charts_periods WHERE id = %d", $video->first_period_id)) : '—';
					?>
					<div class="kc-row-item">
						<header class="kc-row-header">
							<div style="width: 48px; flex-shrink: 0; font-size: 14px; font-weight: 900; color: var(--k-text-dim); text-align: center; border-right: 1px solid var(--k-border); margin-right: 24px; padding-right: 24px;">
								#<?php echo str_pad( $video->id, 2, '0', STR_PAD_LEFT ); ?>
							</div>
							<img src="<?php echo esc_url( $img ); ?>" style="width: 64px; height: 64px; border-radius: 8px; object-fit: cover;">
							<div style="flex-grow: 1;">
								<h3 style="font-size: 18px; font-weight: 950; color: var(--k-text); margin: 0; line-height: 1.2;" class="<?php echo \Charts\Core\Typography::get_font_class($video->title); ?>"><?php echo esc_html( $video->title ); ?></h3>
								<span style="display: block; font-size: 12px; font-weight: 700; color: var(--k-text-muted); margin-top: 4px;" class="<?php echo \Charts\Core\Typography::get_font_class($video->artist_name); ?>"><?php echo esc_html( $video->artist_name ); ?></span>
							</div>
							<div class="kc-row-meta" style="display: flex; gap: 40px; margin-right: 40px; text-align: right;">
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Appearances</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-accent);"><?php echo number_format( $video->appearance_count ); ?></span>
								</div>
								<div>
									<label style="display: block; font-size: 9px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted); margin-bottom: 4px;">Peak</label>
									<span style="font-size: 14px; font-weight: 950; color: var(--k-text);">#<?php echo $video->peak_rank ?: '—'; ?></span>
								</div>
							</div>
							<div class="kc-chevron-toggle">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
							</div>
						</header>

						<div class="kc-details-inner">
							<div class="kc-details-grid" style="grid-template-columns: repeat(4, 1fr); gap: 24px;">
								<div class="kc-details-item">
									<label>Peak Position</label>
									<span>#<?php echo $video->peak_rank ?: '—'; ?></span>
								</div>
								<div class="kc-details-item">
									<label>First Chart Entry</label>
									<span><?php echo $first_entry_date !== '—' ? date('M j, Y', strtotime($first_entry_date)) : '—'; ?></span>
								</div>
								<div class="kc-details-item" style="text-align: right; grid-column: span 2;">
									<a href="<?php echo esc_url( $url ); ?>" class="kc-view-all" style="font-size: 13px; margin-top: 12px; display: inline-block;">View Clip Detail &rarr;</a>
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
<?php \Charts\Core\PublicIntegration::get_footer(); ?>
