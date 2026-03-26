<?php
/**
 * Kontentainment Charts — Premium Editorial Landing
 * 1:1 Reference Match - High-Fidelity Design System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;

// Fetch all active chart definitions
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( true );

// Separate out Top Artists for the featured strip if possible
$artists_chart = null;
foreach ( $definitions as $def ) {
	if ( stripos( $def->title, 'Artist' ) !== false ) {
		$artists_chart = $def;
		break;
	}
}

// Accent palette for grid cards per reference
$accents = array( '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#22c55e', '#ec4899', '#eab308' );
?>

<div class="kc-root">
	
	<!-- 3. HERO SECTION -->
	<section class="kc-hero-section">
		<!-- Atmospheric blurred background could go here per ref -->
		<div class="kc-hero-content kc-container">
			<h1>Intelligence Explorer</h1>
		</div>
	</section>

	<!-- TRENDING NOW BLOCK -->
	<?php 
		$trending = $wpdb->get_results("
			SELECT i.*, e.track_name, e.artist_names, e.cover_image, e.item_type
			FROM {$wpdb->prefix}charts_intelligence i
			JOIN {$wpdb->prefix}charts_entries e ON e.item_id = i.entity_id AND e.item_type = i.entity_type
			WHERE i.entity_type IN ('track','video')
			GROUP BY i.entity_id
			ORDER BY i.momentum_score DESC LIMIT 5
		");
	?>
	<?php if ($trending): ?>
	<section style="background: var(--k-surface); border-bottom: 1px solid var(--k-border); padding: 50px 0; margin-bottom: 60px;">
		<div class="kc-container">
			<header class="kc-section-header" style="padding-top: 0; margin-bottom: 24px;">
				<div>
					<div class="kc-header-label" style="color: var(--k-accent-red);">
						<span class="kc-dot" style="background: var(--k-accent-red); margin-right: 8px;"></span>
						LIVE MOMENTUM
					</div>
					<h2 class="kc-header-title" style="font-size: 24px;">Trending Now</h2>
				</div>
			</header>
			<div style="display: flex; gap: 40px; overflow-x: auto; padding-bottom: 15px; scrollbar-width: none;">
				<?php foreach ($trending as $t): ?>
					<a href="<?php echo home_url('/charts/' . ($t->item_type==='video' ? 'video' : 'track') . '/' . sanitize_title($t->track_name)); ?>" style="display: flex; align-items: center; gap: 16px; text-decoration: none; color: inherit; flex-shrink: 0; min-width: 250px; background: rgba(255,255,255,0.02); padding: 16px; border-radius: 16px; border: 1px solid var(--k-border);">
						<img src="<?php echo esc_url($t->cover_image); ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 20px rgba(0,0,0,0.3);">
						<div style="min-width: 0;">
							<div style="font-size: 13px; font-weight: 850; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($t->track_name); ?></div>
							<div style="font-size: 11px; opacity: 0.4; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($t->artist_names); ?></div>
						</div>
						<div style="margin-left: auto; font-size: 10px; font-weight: 950; color: var(--k-accent-red); padding-left: 10px;">
							↑ <?php echo number_format($t->momentum_score, 0); ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<div class="kc-container">
		
		<!-- 4. FEATURED TOP STRIP (TOP ARTISTS) -->
		<?php if ( $artists_chart ) : 
			// Fetch top 5 artists
			$top_artists = $wpdb->get_results( $wpdb->prepare( "
				SELECT e.* FROM {$wpdb->prefix}charts_entries e
				JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
				WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
				ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 5
			", $artists_chart->chart_type, $artists_chart->country_code ) );
		?>
		<section class="kc-featured-strip">
			<header class="kc-section-header">
				<div>
					<div class="kc-header-label">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
						CHART
					</div>
					<h2 class="kc-header-title">Top Artists</h2>
				</div>
				<a href="<?php echo home_url('/charts/' . $artists_chart->slug . '/'); ?>" class="kc-header-link">Full Chart &rarr;</a>
			</header>

			<div class="kc-artists-strip">
				<?php foreach ( $top_artists as $idx => $art ) : ?>
					<div class="kc-artist-card">
						<span class="kc-artist-rank">#<?php echo $art->rank_position; ?></span>
						<img src="<?php echo esc_url($art->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="<?php echo esc_attr($art->artist_names); ?>">
						<div class="kc-artist-info">
							<span class="kc-artist-name"><?php echo esc_html($art->artist_names); ?></span>
							<span class="kc-artist-genre">MEDITERRANEAN POP</span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- 5. EXPLORE / ALL CHARTS (4-COLUMN GRID) -->
		<section class="kc-all-charts">
			<header class="kc-section-header">
				<div>
					<div class="kc-header-label" style="color: var(--k-text-muted);">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
						EXPLORE
					</div>
					<h2 class="kc-header-title">All Charts</h2>
				</div>
				<a href="#" class="kc-header-link" style="color: var(--k-text-muted);">Browse All &rarr;</a>
			</header>

			<div class="kc-bento-grid">
				<?php foreach ( $definitions as $idx => $def ) : 
					$accent_color = !empty($def->accent_color) ? $def->accent_color : '#6366f1';
					// Fetch top 3 preview
					$entries = $wpdb->get_results( $wpdb->prepare( "
						SELECT e.* FROM {$wpdb->prefix}charts_entries e
						JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
						WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
						ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3
					", $def->chart_type, $def->country_code ) );

					$hero_img = ! empty( $def->cover_image_url ) ? $def->cover_image_url : (! empty( $entries[0]->cover_image ) ? $entries[0]->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png');
					$period_date = ! empty( $entries[0]->created_at ) ? date('M j, Y', strtotime($entries[0]->created_at)) : 'Latest Record';
				?>
					<article class="kc-chart-card">
						
						<!-- Hero Card Top -->
						<div class="kc-card-hero">
							<img src="<?php echo esc_url($hero_img); ?>" class="kc-card-hero-img" alt="Background">
							<div style="position: relative; z-index: 10;">
								<span class="kc-card-meta"><?php echo strtoupper($def->frequency); ?> CHART</span>
								<h2 class="kc-card-title">
									<?php echo esc_html($def->title); ?>
									<?php if (!empty($def->title_ar)): ?>
										<span style="font-size: 0.7em; opacity: 0.5; font-weight: 500; display: block; margin-top: 4px; letter-spacing: 0;"><?php echo esc_html($def->title_ar); ?></span>
									<?php endif; ?>
									<span class="kc-dot" style="background: <?php echo esc_attr($accent_color); ?>;"></span>
								</h2>
							</div>
						</div>

						<!-- Top 3 List -->
						<section class="kc-card-list">
							<?php if ( empty( $entries ) ) : ?>
								<div style="padding: 40px; text-align: center; color: var(--k-text-muted); font-size: 11px; font-weight: 850;">
									AWAITING DATA SYNC
								</div>
							<?php else : ?>
								<?php foreach ( $entries as $row ) : ?>
									<div class="kc-card-item">
										<div class="kc-item-rank"><?php echo $row->rank_position; ?></div>
										<img src="<?php echo esc_url( $row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png' ); ?>" class="kc-item-art" alt="Art">
										<div class="kc-item-info">
											<span class="kc-item-name"><?php echo esc_html($row->track_name); ?></span>
											<span class="kc-item-artist"><?php echo esc_html($row->artist_names); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</section>

						<!-- Card Footer -->
						<footer class="kc-card-footer">
							<span class="kc-card-date">Week of <?php echo $period_date; ?></span>
							<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta" style="color: <?php echo $accent_color; ?>;">
								See Full Chart &rarr;
							</a>
						</footer>

					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
