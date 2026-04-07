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
			SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), t.cover_image, v.thumbnail, a.image) AS resolved_image FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			JOIN {$wpdb->prefix}charts_periods p ON p.id = e.period_id
			LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
			LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
			LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY p.period_start DESC, e.rank_position ASC LIMIT 4
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
    SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), a.image) AS resolved_image FROM {$wpdb->prefix}charts_entries e
    JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
    LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
    WHERE s.chart_type = 'top-artists' AND s.country_code = %s AND s.is_active = 1
    ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 5
", $top_artists_chart->country_code ) ) : array();

use Charts\Core\Settings;
\Charts\Core\PublicIntegration::get_header();

$homepage_show_artists = Settings::get('homepage.show_artists_row');
$homepage_show_more    = Settings::get('homepage.show_charts_grid');
$section_order         = explode(',', Settings::get('homepage.section_order'));
?>

<div class="kc-root" style="background: var(--k-bg); color: var(--k-text);">
	
	<div class="kc-container" style="display: flex; flex-direction: column; gap: <?php echo esc_attr(Settings::get('homepage.section_spacing', 80)); ?>px;">
		
		<?php 
		$order = explode(',', Settings::get('homepage.section_order', 'slider,artists,charts'));
		foreach ( $order as $section_key ) :
			$section_key = trim($section_key);

			// 1. PREMIUM HERO SLIDER (The Only Homepage Slider)
			if ( $section_key === 'slider' ) :
				$s_settings = \Charts\Core\HomepageSlider::get_premium_settings();
				$slides     = \Charts\Core\HomepageSlider::get_slides_data();
				$config = [
					'autoplay' => (bool)$s_settings['autoplay'],
					'delay' => (int)$s_settings['delay'],
					'speed' => (int)$s_settings['speed'],
					'loop' => (bool)$s_settings['loop'],
					'pause_on_hover' => (bool)$s_settings['pause'],
					'show_arrows' => true,
					'show_dots' => true,
				];

				if ( ! empty( $slides ) && ! empty( $s_settings['enable'] ) ) : ?>
					<section class="kc-hero-slider-section" style="padding-top: <?php echo esc_attr(Settings::get('homepage.padding_top', 40)); ?>px;">
						<div class="kc-slider-container" style="max-width: <?php echo esc_attr($s_settings['width'] ?? 1400); ?>px; margin: 0 auto; padding: 0 40px;">
							<?php include CHARTS_PATH . 'public/templates/parts/premium-slider.php'; ?>
						</div>
					</section>
				<?php elseif ( empty($s_settings['enable']) ) : ?>
					<!-- Slider is administratively disabled -->
				<?php else : ?>
					<section class="kc-hero-slider-section" style="padding-top: 40px; text-align: center;">
						<p style="color:var(--k-text-muted);">Kontentainment Billboard: Currently no slide data to display.</p>
					</section>
				<?php endif;
			endif; 
			
			// 2. TOP ARTISTS STRIP
			if ( $section_key === 'artists' && $homepage_show_artists && ! empty( $featured_artists ) ) : ?>
			<section class="kc-section">
				<div class="kc-section-header">
					<h2 class="kc-section-title">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
						<?php echo esc_html(Settings::get('labels.top_artists_title', 'Top Artists')); ?>
					</h2>
					<a href="<?php echo $top_artists_chart ? home_url('/charts/' . $top_artists_chart->slug . '/') : '#'; ?>" class="kc-view-all">Full Chart &rarr;</a>
				</div>

				<div class="kc-top-artists-grid">
					<?php foreach ( $featured_artists as $idx => $art ) : ?>
						<a href="<?php echo home_url('/charts/artist/' . $art->item_slug . '/'); ?>" class="kc-card" style="padding: 0; overflow: hidden; position: relative; height: 320px; text-decoration: none; display: block; border-radius: var(--k-radius-md); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
							<img src="<?php echo esc_url($art->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
							<div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 60%);"></div>
							<div style="position: absolute; top: 16px; left: 16px; width: 32px; height: 32px; background: var(--k-accent-purple); color: #fff; font-size: 10px; font-weight: 900; display: flex; align-items: center; justify-content: center; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">#<?php echo $art->rank_position; ?></div>
							<div style="position: absolute; bottom: 24px; left: 24px; right: 24px;">
								<h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 900; letter-spacing: -0.02em;" class="<?php echo \Charts\Core\Typography::get_font_class($art->track_name); ?>"><?php echo esc_html($art->track_name); ?></h3>
								<p style="margin: 4px 0 0; color: rgba(255,255,255,0.6); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;" class="<?php echo \Charts\Core\Typography::get_font_class(Settings::get('labels.trending_artist_tag', 'Trending Artist')); ?>"><?php echo esc_html(Settings::get('labels.trending_artist_tag', 'Trending Artist')); ?></p>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif;

			// 3. ALL CHARTS SECTION
			if ( $section_key === 'charts' && $homepage_show_more ) : ?>
			<section class="kc-section">
				<div class="kc-section-header">
					<h2 class="kc-section-title">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
						<?php echo esc_html(Settings::get('labels.all_charts_title', 'All Charts')); ?>
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
									<img src="<?php echo esc_url(!empty($def->cover_image_url) ? $def->cover_image_url : (!empty($entries[0]->resolved_image) ? $entries[0]->resolved_image : CHARTS_URL . 'public/assets/img/placeholder.png')); ?>">
									<div class="kc-card-header-overlay" style="background: linear-gradient(to top, <?php echo $accent; ?>dd, transparent);"></div>
									<span class="kc-card-label">Weekly Chart</span>
									<h3 class="kc-card-title"><?php echo \Charts\Core\Typography::apply($def->title); ?></h3>
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
												<img class="kc-entry-art" src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
												<div class="kc-entry-info">
													<span class="kc-entry-name <?php echo \Charts\Core\Typography::get_font_class($e->track_name); ?>"><?php echo esc_html($e->track_name); ?></span>
													<span class="kc-entry-artist <?php echo \Charts\Core\Typography::get_font_class($e->artist_names); ?>"><?php echo esc_html($e->artist_names); ?></span>
												</div>
											</div>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>

								<div class="kc-card-footer" style="justify-content: center;">
									<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta"><?php echo esc_html(Settings::get('labels.chart_cta_text', 'See Full Chart')); ?></a>
								</div>
							</article>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</section>
			<?php endif;
		endforeach; ?>

	</div>
</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php \Charts\Core\PublicIntegration::get_footer(); ?>
