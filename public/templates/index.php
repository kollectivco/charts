<?php
/**
 * Kontentainment Charts — Home (Light Mode)
 * Matches Reference #4
 */

global $wpdb;

// 1. DATA LOOKUP
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( true ); // Only active/public charts

// Helper to fetch top 3 preview for a definition from the most recent period
function kc_get_preview_entries($def) {
	global $wpdb;
	$cache_key = 'kc_preview_' . $def->id;
	$entries = get_transient( $cache_key );
	
	if ( false === $entries ) {
		$entries = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			JOIN {$wpdb->prefix}charts_periods p ON p.id = e.period_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY p.period_start DESC, e.rank_position ASC LIMIT 3
		", $def->chart_type, $def->country_code ), ARRAY_A );
		
		set_transient( $cache_key, $entries, HOUR_IN_SECONDS );
	}
	
	return array_map(function($e){ return (object)$e; }, (array)$entries);
}

// Helper to check if a chart is REALLY syncing (has an active/started run in the last 15 min)
function kc_is_syncing_active($def) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(*) FROM {$wpdb->prefix}charts_import_runs r
		JOIN {$wpdb->prefix}charts_sources s ON s.id = r.source_id
		WHERE s.chart_type = %s AND s.country_code = %s 
		AND r.status IN ('started', 'processing')
		AND r.started_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
	", $def->chart_type, $def->country_code ) );
}

// Fetch Top Artists for the hero row
$top_artists_chart = null;
foreach ($definitions as $def) {
    if ($def->chart_type === 'top-artists') {
        $top_artists_chart = $def;
        break;
    }
}
$featured_artists = $top_artists_chart ? $wpdb->get_results( $wpdb->prepare( "
    SELECT e.* FROM {$wpdb->prefix}charts_entries e
    JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
    WHERE s.chart_type = 'top-artists' AND s.country_code = %s AND s.is_active = 1
    ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 5
", $top_artists_chart->country_code ) ) : array();

\Charts\Core\StandaloneLayout::get_header();
?>

<div class="kc-root">
	
	<!-- 1. HERO HEADER -->
	<section class="kc-page-hero" style="text-align: center; padding: 120px 0;">
		<div class="kc-container">
			<h1 class="kc-page-title" style="font-size: 110px; margin-bottom: 0;">Kontentrainment Charts</h1>
		</div>
	</section>

	<div class="kc-container">
		
		<!-- 2. TOP ARTISTS STRIP -->
		<?php if ( ! empty( $featured_artists ) ) : ?>
		<section class="kc-section">
			<div class="kc-section-header">
				<h2 class="kc-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
					Top Artists
				</h2>
				<a href="<?php echo $top_artists_chart ? home_url('/charts/' . $top_artists_chart->slug . '/') : '#'; ?>" class="kc-view-all">Full Chart &rarr;</a>
			</div>

			<div class="kc-grid kc-grid-4" style="grid-template-columns: repeat(5, 1fr); gap: 20px;">
				<?php foreach ( $featured_artists as $idx => $art ) : ?>
					<a href="<?php echo home_url('/charts/artist/' . $art->item_slug); ?>" class="kc-card" style="padding: 0; overflow: hidden; position: relative; height: 320px; text-decoration: none; display: block;">
						<img src="<?php echo esc_url($art->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
						<div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);"></div>
						<div style="position: absolute; top: 16px; left: 16px; width: 32px; height: 32px; background: var(--k-accent-purple); color: #fff; font-size: 10px; font-weight: 900; display: flex; align-items: center; justify-content: center; border-radius: 4px;">#<?php echo $art->rank_position; ?></div>
						<div style="position: absolute; bottom: 24px; left: 24px; right: 24px;">
							<h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 900;"><?php echo esc_html($art->track_name); ?></h3>
							<p style="margin: 4px 0 0; color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 700;">Trending Artist</p>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- 3. ALL CHARTS SECTION -->
		<section class="kc-section">
			<div class="kc-section-header">
				<h2 class="kc-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
					All Charts
				</h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all">Browse All &rarr;</a>
			</div>

			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php if ( empty( $definitions ) ) : ?>
					<div style="grid-column: 1 / -1; padding: 80px; text-align: center; border: 2px dashed var(--k-border); border-radius: 24px;">
						<p style="font-weight: 800; color: var(--k-text-muted);">No active charts found.</p>
					</div>
				<?php else : ?>
					<?php foreach ( $definitions as $def ) : 
						$entries = kc_get_preview_entries($def);
						$accent  = !empty($def->accent_color) ? $def->accent_color : '#fe025b';
					?>
						<article class="kc-chart-card">
							<div class="kc-card-accent-dot" style="background: <?php echo $accent; ?>;"></div>
							<div class="kc-card-header">
								<img src="<?php echo esc_url(!empty($def->cover_image_url) ? $def->cover_image_url : (!empty($entries[0]->cover_image) ? $entries[0]->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png')); ?>">
								<div class="kc-card-header-overlay"></div>
								<span class="kc-card-label">Weekly Chart</span>
								<h3 class="kc-card-title"><?php echo esc_html($def->title); ?></h3>
							</div>

							<div class="kc-card-list">
								<?php if ( empty($entries) ) : ?>
									<?php if ( kc_is_syncing_active($def) ) : ?>
										<div style="padding: 24px; font-size: 12px; font-weight: 600; color: var(--k-text-muted);">
											<svg class="kc-spinner" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 8px; vertical-align: middle; animation: kc-spin 1s linear infinite;"><path d="M21 12a9 9 0 11-6.219-8.56"></path></svg>
											Synchronizing...
										</div>
									<?php else : ?>
										<div style="padding: 24px; font-size: 12px; font-weight: 600; color: var(--k-text-muted); opacity: 0.5;">No entry data available yet.</div>
									<?php endif; ?>
								<?php else : ?>
									<?php foreach ( $entries as $e ) : ?>
										<div class="kc-card-entry">
											<span class="kc-entry-rank"><?php echo $e->rank_position; ?></span>
											<img class="kc-entry-art" src="<?php echo esc_url($e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
											<div class="kc-entry-info">
												<span class="kc-entry-name"><?php echo esc_html($e->track_name); ?></span>
												<span class="kc-entry-artist"><?php echo esc_html($e->artist_names); ?></span>
											</div>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>

							<div class="kc-card-footer">
								<?php 
								$week_date = !empty($entries[0]->created_at) ? date('M j, Y', strtotime($entries[0]->created_at)) : date('M j, Y');
								?>
								<span class="kc-card-week">Week of <?php echo $week_date; ?></span>
								<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta">See Full Chart</a>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
