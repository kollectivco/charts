<?php
/**
 * Kontentainment Charts — Intelligence Dashboard
 * Dynamic Light-Mode Bento System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;

// Fetch all active chart definitions
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( true );

if ( empty( $definitions ) ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Awaiting Intelligence</h1><p>No chart definitions have been initialized yet.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

?>

<div class="kc-root">
	<main class="kc-container">
		
		<!-- Landing Header -->
		<header class="kc-landing-header">
			<div class="kc-landing-title">
				<span>EXPLORE</span>
				<h1>All Charts</h1>
			</div>
			<div class="kc-landing-nav">
				<a href="#" class="kc-card-cta" style="font-size: 14px; opacity: 0.8;">Browse All &rarr;</a>
			</div>
		</header>

		<!-- Bento Grid -->
		<div class="kc-bento-grid" style="grid-template-columns: repeat(3, 1fr); padding-bottom: 120px;">
			<?php foreach ( $definitions as $idx => $def ) : 
				// Fetch top 3 entries for preview
				$entries = $wpdb->get_results( $wpdb->prepare( "
					SELECT e.* FROM {$wpdb->prefix}charts_entries e
					JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
					WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
					ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3
				", $def->chart_type, $def->country_code ) );

				$hero_img = ! empty( $entries[0]->cover_image ) ? $entries[0]->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png';
				$period_date = ! empty( $entries[0]->created_at ) ? date('F j, Y', strtotime($entries[0]->created_at)) : 'Latest Market Record';
			?>
				<div style="grid-column: span 1;">
					<article class="kc-chart-card">
						
						<!-- Card Hero Area -->
						<div class="kc-card-hero">
							<img src="<?php echo esc_url($hero_img); ?>" class="kc-card-hero-img" alt="Background">
							<div style="position: relative; z-index: 10;">
								<span class="kc-card-meta"><?php echo strtoupper($def->frequency); ?> CHART</span>
								<h2 class="kc-card-title">
									<?php echo esc_html($def->title); ?>
									<span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo ($idx % 2 === 0) ? '#ef4444' : '#6366f1'; ?>; display: inline-block;"></span>
								</h2>
							</div>
						</div>

						<!-- Card Intelligence List -->
						<section class="kc-card-list">
							<?php if ( empty( $entries ) ) : ?>
								<div style="padding: 40px; text-align: center; color: var(--k-text-muted); font-size: 12px; font-weight: 600;">
									AWAITING DATA SYNC
								</div>
							<?php else : ?>
								<?php foreach ( $entries as $row ) : ?>
									<div class="kc-card-item">
										<div class="kc-item-rank"><?php echo $row->rank_position; ?></div>
										<img src="<?php echo esc_url( $row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png' ); ?>" class="kc-item-art" alt="Track Art">
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
							<span class="kc-card-date"><?php echo $period_date; ?></span>
							<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta">
								See Full Chart &rarr;
							</a>
						</footer>

					</article>
				</div>
			<?php endforeach; ?>
		</div>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
