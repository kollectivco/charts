<?php
/**
 * Kontentainment Charts — Artist Intelligence Profile
 * Matches Reference #1
 */

global $wpdb;

$slug = get_query_var( 'charts_artist_slug' );
$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s", $slug ) );

if ( ! $artist ) {
	\Charts\Core\StandaloneLayout::get_header();
	echo '<div class="kc-root"><h1>Artist Not Found</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	return;
}

// Data fetching
$entries_table = $wpdb->prefix . 'charts_entries';
$sources_table = $wpdb->prefix . 'charts_sources';

// Charting tracks
$charting_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, MAX(p.period_start) as latest_period
	FROM $entries_table e
	JOIN {$wpdb->prefix}charts_periods p ON p.id = e.period_id
	WHERE e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d)
	GROUP BY e.item_id
	ORDER BY e.rank_position ASC LIMIT 4
", $artist->id ) );

// Popular tracks (Simulation based on highest rank)
$popular_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.* FROM $entries_table e
	WHERE e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d)
	ORDER BY e.rank_position ASC LIMIT 2
", $artist->id ) );

// Chart Rankings
$chart_rankings = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, d.title as definition_title 
	FROM $entries_table e
	JOIN $sources_table s ON s.id = e.source_id
	LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
	WHERE (e.item_id = %d AND e.item_type = 'artist')
	ORDER BY e.rank_position ASC LIMIT 2
", $artist->id ) );

\Charts\Core\StandaloneLayout::get_header();
?>

<div class="kc-root">
	<div class="kc-container">
		
		<!-- ARTIST HEADER -->
		<header class="kc-profile-header" style="margin-top: 60px;">
			<img src="<?php echo esc_url($artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-profile-avatar">
			<div class="kc-profile-info">
				<div class="kc-eyebrow">Artist</div>
				<h1 class="kc-page-title"><?php echo esc_html($artist->display_name); ?></h1>
				<?php if ( ! empty($artist->display_name_franko) ) : ?>
					<div style="font-size: 18px; font-weight: 700; color: var(--k-text-muted); margin-top: 4px; opacity: 0.5;"><?php echo esc_html($artist->display_name_franko); ?></div>
				<?php endif; ?>
			</div>
		</header>

		<div class="kc-breadcrumb" style="margin-top: -20px;">
			<a href="<?php echo home_url('/charts'); ?>">Home</a> <span>/</span> <a href="<?php echo home_url('/charts'); ?>">Top Artists</a> <span>/</span> <?php echo esc_html($artist->display_name); ?>
		</div>

		<!-- STATS STRIP (Only if available in DB) -->
		<?php 
		$stats_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'artist' AND entity_id = %d", $artist->id ) );
		if ( $stats_obj && ( ! empty($stats_obj->weeks_on_chart) || ! empty($stats_obj->total_streams) ) ) : 
		?>
		<div class="kc-stats-grid" style="margin-top: 40px;">
			<?php if ( ! empty($stats_obj->weeks_on_chart) ) : ?>
			<div class="kc-stat-pill">
				<label>Weeks on Chart</label>
				<div style="display: flex; align-items: baseline; gap: 4px;">
					<span class="val"><?php echo intval($stats_obj->weeks_on_chart); ?></span>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( ! empty($stats_obj->total_streams) ) : ?>
			<div class="kc-stat-pill">
				<label>Total Views</label>
				<div style="display: flex; align-items: baseline; gap: 4px;">
					<span class="val"><?php echo number_format($stats_obj->total_streams / 1000000, 1); ?>M</span>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<!-- ABOUT (Conditional) -->
		<?php 
		$metadata = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : array();
		$bio = $metadata['bio'] ?? $metadata['description'] ?? '';
		if ( ! empty($bio) ) : 
		?>
		<section class="kc-card" style="margin-bottom: 60px; padding: 40px;">
			<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 20px;">About</h3>
			<p style="font-size: 15px; line-height: 1.7; color: var(--k-text-dim);">
				<?php echo wp_kses_post($bio); ?>
			</p>
		</section>
		<?php endif; ?>

		<!-- MAIN GRID -->
		<div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 60px;">
			
			<!-- COL 1 -->
			<div>
				<!-- CHARTING TRACKS -->
				<section style="margin-bottom: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Charting Tracks</h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php if ( empty($charting_tracks) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No current charting tracks.</p>
						<?php else : ?>
							<?php foreach ( $charting_tracks as $ct ) : ?>
								<a href="<?php echo home_url('/charts/track/' . $ct->item_slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo $ct->rank_position; ?></span>
										<img src="<?php echo esc_url($ct->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 44px; height: 44px; border-radius: 6px;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);"><?php echo esc_html($ct->track_name); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);"><?php echo esc_html($artist->display_name); ?></span>
										</div>
									</div>
									<div style="display: flex; align-items: center; gap: 20px;">
										<?php if ( ! empty($ct->peak_rank) ) : ?>
										<div style="text-align: right;">
											<span style="display: block; font-size: 9px; color: var(--k-text-muted);">Peak #<?php echo intval($ct->peak_rank); ?></span>
										</div>
										<?php endif; ?>
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><polyline points="9 18 15 12 9 6"></polyline></svg>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>

				<!-- POPULAR TRACKS -->
				<section>
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Popular Tracks</h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php if ( empty($popular_tracks) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No popular tracks data.</p>
						<?php else : ?>
							<?php foreach ( $popular_tracks as $pt ) : ?>
								<a href="<?php echo home_url('/charts/track/' . $pt->item_slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo $pt->rank_position; ?></span>
										<img src="<?php echo esc_url($pt->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 44px; height: 44px; border-radius: 6px;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);"><?php echo esc_html($pt->track_name); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);"><?php echo esc_html($artist->display_name); ?></span>
										</div>
									</div>
									<div style="display: flex; align-items: center; gap: 20px;">
										<?php if ( ! empty($pt->views_count) ) : ?>
										<span style="font-size: 12px; font-weight: 700; color: var(--k-text-muted);"><?php echo number_format($pt->views_count / 1000000, 1); ?>M</span>
										<?php endif; ?>
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><polyline points="9 18 15 12 9 6"></polyline></svg>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<!-- COL 2 (WIDGETS) -->
			<div>
				<!-- CHART RANKINGS -->
				<section style="margin-bottom: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Chart Rankings</h3>
					<div style="display: flex; flex-direction: column; gap: 16px;">
						<?php if ( empty($chart_rankings) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No current rankings found.</p>
						<?php else : ?>
							<?php foreach ( $chart_rankings as $cr ) : ?>
								<div class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px;">
									<div style="display: flex; align-items: center; gap: 12px;">
										<img src="<?php echo esc_url($artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
										<span style="font-size: 13px; font-weight: 800;"><?php echo esc_html($cr->definition_title ?: 'Top Artists'); ?></span>
									</div>
									<div style="text-align: right;">
										<div style="font-size: 24px; font-weight: 950; color: var(--k-text);">#<?php echo $cr->rank_position; ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>

				<!-- ALBUMS (Conditional) -->
				<?php 
				$albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_albums WHERE primary_artist_id = %d LIMIT 2", $artist->id ) );
				if ( ! empty($albums) ) : 
				?>
				<section>
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Albums</h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php foreach ( $albums as $album ) : ?>
						<div class="kc-card" style="display: flex; align-items: center; gap: 16px;">
							<img src="<?php echo esc_url($album->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover;">
							<div>
								<h4 style="font-size: 14px; font-weight: 900; margin: 0;"><?php echo esc_html($album->title); ?></h4>
								<?php if ( ! empty($album->release_date) ) : ?>
								<span style="display: block; font-size: 11px; color: var(--k-text-muted); margin-top: 4px;"><?php echo date('Y', strtotime($album->release_date)); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>
			</div>

		</div>

		<!-- MORE CHARTS -->
		<section class="kc-section" style="padding-top: 100px;">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> More Charts</h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all">View All Charts &rarr;</a>
			</div>
			
			<div class="kc-grid kc-grid-3">
				<?php 
				$mdefs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_definitions LIMIT 3" );
				foreach ( $mdefs as $mdef ) : 
					$mentries = $wpdb->get_results( $wpdb->prepare( "SELECT e.* FROM {$wpdb->prefix}charts_entries e JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id WHERE s.chart_type = %s AND s.country_code = %s ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3", $mdef->chart_type, $mdef->country_code ) );
				?>
					<article class="kc-chart-card">
						<div class="kc-card-accent-dot" style="background: <?php echo $mdef->accent_color ?: '#fe025b'; ?>;"></div>
						<div class="kc-card-header">
							<img src="<?php echo esc_url($mentries[0]->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
							<div class="kc-card-header-overlay"></div>
							<span class="kc-card-label">Weekly Chart</span>
							<h3 class="kc-card-title"><?php echo esc_html($mdef->title); ?></h3>
						</div>
						<div class="kc-card-list">
							<?php foreach ( $mentries as $me ) : ?>
								<div class="kc-card-entry">
									<span class="kc-entry-rank"><?php echo $me->rank_position; ?></span>
									<img class="kc-entry-art" src="<?php echo esc_url($me->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
									<div class="kc-entry-info">
										<span class="kc-entry-name"><?php echo esc_html($me->track_name); ?></span>
										<span class="kc-entry-artist"><?php echo esc_html($me->artist_names); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="kc-card-footer">
							<span class="kc-card-week">Week of <?php echo date('M j, Y'); ?></span>
							<a href="<?php echo home_url('/charts/'.$mdef->slug.'/'); ?>" class="kc-card-cta">See Full Chart</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
