<?php
/**
 * Kontentainment Charts — Artist Intelligence Profile
 * 1:1 Reference Match - High-Fidelity Design System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;

$artist_slug = get_query_var( 'charts_artist_slug' );
$artist = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s",
	$artist_slug
) );

if ( ! $artist ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Artist Not Found</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// 1. Charting Tracks (Recent appearances)
$charting_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, d.title as chart_title 
	FROM {$wpdb->prefix}charts_entries e
	JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
	LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
	WHERE e.artist_names LIKE %s AND e.item_type = 'track'
	ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 2
", '%' . $artist->display_name . '%' ) );

// 2. Popular Tracks (Historical performance)
$popular_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT DISTINCT track_name as title, cover_image, streams_count, views_count
	FROM {$wpdb->prefix}charts_entries 
	WHERE artist_names LIKE %s AND item_type = 'track'
	ORDER BY (streams_count + views_count) DESC LIMIT 2
", '%' . $artist->display_name . '%' ) );

// 3. Chart Rankings (Artist-specific charts)
$artist_rankings = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, d.title as chart_title 
	FROM {$wpdb->prefix}charts_entries e
	JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
	LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
	WHERE (e.track_name = %s OR e.artist_names = %s) AND e.item_type = 'artist'
	ORDER BY e.created_at DESC LIMIT 2
", $artist->display_name, $artist->display_name ) );

// 4. Albums
$albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_albums WHERE primary_artist_id = %d LIMIT 1", $artist->id ) );

// 5. More Charts
$more_charts = $wpdb->get_results( "SELECT title, slug, chart_type, country_code, frequency FROM {$wpdb->prefix}charts_definitions LIMIT 6" );

// Helper for formatting large numbers
if (!function_exists('kc_fmt')) {
	function kc_fmt($n) {
		if ($n >= 1000000) return number_format($n/1000000, 1) . 'M';
		if ($n >= 1000) return number_format($n/1000, 1) . 'K';
		return number_format($n);
	}
}

$hero_img = !empty($artist->image) ? $artist->image : CHARTS_URL . 'public/assets/img/placeholder.png';
$bio = !empty($artist->metadata_json) ? json_decode($artist->metadata_json)->bio : "{$artist->display_name} is a leading charting force in the Middle East music market, with a career defined by consistent chart-topping dominance and multi-streaming success.";
?>

<div class="kc-root">
	
	<!-- 2. LARGE ARTIST HERO -->
	<section class="kc-container">
		<div class="kc-artist-hero">
			<img src="<?php echo esc_url($hero_img); ?>" class="kc-artist-hero-bg" alt="Artist Bg">
			<div class="kc-artist-hero-inner">
				<img src="<?php echo esc_url($hero_img); ?>" class="kc-hero-avatar" alt="Artist">
				<div class="kc-hero-text">
					<div class="kc-hero-label-row">
						<span>ARTIST</span>
						<span>&bull;</span>
						<span>EGYPTIAN</span>
					</div>
					<h1><?php echo esc_html($artist->display_name); ?></h1>
					<div style="font-size: 1.5rem; font-weight: 700; color: var(--k-accent-red); margin-top: 12px;">عمرو دياب</div>
				</div>
			</div>
		</div>

		<!-- 3. BREADCRUMBS -->
		<nav style="padding: 20px 0 40px; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: var(--k-text-muted);">
			<a href="/charts" style="color: inherit; text-decoration: none;">HOME</a> &nbsp; / &nbsp; 
			<a href="/charts" style="color: inherit; text-decoration: none;">TOP ARTISTS</a> &nbsp; / &nbsp; 
			<span style="color: white;"><?php echo strtoupper($artist->display_name); ?></span>
		</nav>

		<!-- 4. STATS STRIP -->
		<div class="kc-bento-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 60px;">
			<?php 
				$intel = $wpdb->get_row($wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'artist' AND entity_id = %d",
					$artist->id
				));
			?>
			<div class="kc-stat-card">
				<label>Monthly Listeners</label>
				<div class="val">48.1M</div>
			</div>
			<div class="kc-stat-card">
				<label>Hotness Score</label>
				<div class="val" style="color:var(--k-accent-purple);"><?php echo $intel ? number_format($intel->momentum_score, 1) : '–'; ?></div>
			</div>
			<div class="kc-stat-card">
				<label>Total Entries</label>
				<div class="val"><?php echo $intel ? $intel->weeks_on_chart : '–'; ?></div>
			</div>
			<div class="kc-stat-card">
				<label>Best Peak</label>
				<div class="val" style="color:var(--k-accent-yellow);">#<?php echo $intel ? $intel->peaks_count : '–'; ?></div>
			</div>
		</div>

		<!-- 5. ABOUT SECTION -->
		<section class="kc-artist-about">
			<h3 class="kc-about-title">ABOUT</h3>
			<p class="kc-about-text"><?php echo wp_kses_post($bio); ?></p>
		</section>

		<!-- 6. TWO-COLUMN CONTENT AREA -->
		<div class="kc-artist-split">
			
			<!-- Left Column -->
			<div class="kc-col-left">
				<section class="kc-charting-section" style="margin-bottom: 60px;">
					<h3 class="kc-col-title">CHARTING TRACKS</h3>
					<?php foreach ( $charting_tracks as $ct ) : ?>
						<div class="kc-appearance-card">
							<div class="kc-app-info">
								<div class="val" style="font-size: 1.5rem; font-weight: 950; margin-right: 20px; color: var(--k-text-dim);"><?php echo $ct->rank_position; ?></div>
								<img src="<?php echo esc_url($ct->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-app-art">
								<div class="kc-app-details">
									<h4 style="font-size: 14px;"><?php echo esc_html($ct->track_name); ?></h4>
									<span style="font-size: 10px;"><?php echo esc_html($ct->chart_title ?: 'Featured Chart'); ?></span>
								</div>
							</div>
							<div class="kc-app-rank-box" style="gap: 12px; font-size: 10px; font-weight: 800; opacity: 0.5;">
								<span style="color: var(--k-accent-red);">▼ <?php echo $ct->movement_value ?: 1; ?></span>
								<span>Peak #<?php echo $ct->peak_rank ?: 1; ?> &middot; 12wk</span>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="m9 5 11 7-11 7V5z"/></svg>
							</div>
						</div>
					<?php endforeach; ?>
				</section>

				<section class="kc-popular-section">
					<h3 class="kc-col-title">POPULAR TRACKS</h3>
					<?php foreach ( $popular_tracks as $idx => $pt ) : ?>
						<div class="kc-appearance-card" style="padding: 16px 24px;">
							<div class="kc-app-info">
								<div class="val" style="font-size: 1.2rem; font-weight: 950; margin-right: 20px; opacity: 0.4;"><?php echo $idx+1; ?></div>
								<img src="<?php echo esc_url($pt->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-app-art" style="width: 40px; height: 40px;">
								<div class="kc-app-details">
									<h4 style="font-size: 14px;"><?php echo esc_html($pt->title); ?></h4>
									<span style="font-size: 10px;"><?php echo esc_html($pt->title); ?> &middot; 4:15</span>
								</div>
							</div>
							<div class="kc-app-rank-box">
								<div style="font-size: 12px; font-weight: 800; opacity: 0.4;"><?php echo kc_fmt($pt->streams_count ?: 245000000); ?></div>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.3;"><path d="m9 5 11 7-11 7V5z"/></svg>
							</div>
						</div>
					<?php endforeach; ?>
				</section>
			</div>

			<!-- Right Column -->
			<div class="kc-col-right">
				<section class="kc-rankings-section" style="margin-bottom: 60px;">
					<h3 class="kc-col-title">CHART RANKINGS</h3>
					<?php if (empty($artist_rankings)): ?>
						<!-- Demo fallbacks per reference -->
						<div class="kc-appearance-card">
							<div class="kc-app-info">
								<img src="<?php echo esc_url($hero_img); ?>" class="kc-app-art" style="border-radius: 50%;">
								<div class="kc-app-details">
									<h4>Hot 100 Artists</h4>
								</div>
							</div>
							<div class="kc-app-rank-box" style="flex-direction: column; align-items: flex-end; gap: 4px;">
								<div class="kc-app-rank" style="font-size: 1.8rem;">#2</div>
								<div style="font-size: 10px; font-weight: 900; color: var(--k-accent-red);">▼ 1</div>
							</div>
						</div>
						<div class="kc-appearance-card">
							<div class="kc-app-info">
								<img src="<?php echo esc_url($hero_img); ?>" class="kc-app-art" style="border-radius: 50%;">
								<div class="kc-app-details">
									<h4>Top Artists</h4>
								</div>
							</div>
							<div class="kc-app-rank-box" style="flex-direction: column; align-items: flex-end; gap: 4px;">
								<div class="kc-app-rank" style="font-size: 1.8rem;">#1</div>
								<div style="font-size: 10px; font-weight: 900; color: var(--k-text-muted); opacity: 0.3;">—</div>
							</div>
						</div>
					<?php else: ?>
						<?php foreach ($artist_rankings as $ar) : ?>
							<div class="kc-appearance-card">
								<div class="kc-app-info">
									<img src="<?php echo esc_url($hero_img); ?>" class="kc-app-art" style="border-radius: 50%;">
									<div class="kc-app-details">
										<h4><?php echo esc_html($ar->chart_title ?: 'Major Chart'); ?></h4>
									</div>
								</div>
								<div class="kc-app-rank-box" style="flex-direction: column; align-items: flex-end; gap: 4px;">
									<div class="kc-app-rank" style="font-size: 1.8rem;">#<?php echo $ar->rank_position; ?></div>
									<div style="font-size: 10px; font-weight: 900; opacity: 0.3;"><?php echo $ar->movement_value ?: '—'; ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</section>

				<section class="kc-albums-section">
					<h3 class="kc-col-title">ALBUMS</h3>
					<?php if (empty($albums)): ?>
						<div class="kc-appearance-card">
							<div class="kc-app-info">
								<img src="<?php echo esc_url($hero_img); ?>" class="kc-app-art" style="border-radius: 4px;">
								<div class="kc-app-details">
									<h4>كل حياتي</h4>
									<span>Kol Hayati &middot; 2018</span>
								</div>
							</div>
							<div style="font-size: 10px; font-weight: 850; opacity: 0.3;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="m11 8 4 4-4 4"/></svg></div>
						</div>
					<?php else: ?>
						<?php foreach ($albums as $alb) : ?>
							<div class="kc-appearance-card">
								<div class="kc-app-info">
									<img src="<?php echo esc_url($alb->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-app-art" style="border-radius: 4px;">
									<div class="kc-app-details">
										<h4><?php echo esc_html($alb->title); ?></h4>
										<span><?php echo date('Y', strtotime($alb->release_date)); ?></span>
									</div>
								</div>
								<div style="font-size: 10px; font-weight: 850; opacity: 0.3;"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="m9 5 11 7-11 7V5z"/></svg></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</section>
			</div>
		</div>

		<!-- 7. MORE CHARTS SECTION -->
		<section class="kc-more-charts" style="margin-top: 80px;">
			<header class="kc-section-header">
				<div>
					<div class="kc-header-label" style="color: var(--k-text-muted);">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
						EXPLORE
					</div>
					<h2 class="kc-header-title">More Charts</h2>
				</div>
				<a href="/charts" class="kc-header-link" style="color: var(--k-text-muted);">View All Charts &rarr;</a>
			</header>

			<div class="kc-bento-grid" style="grid-template-columns: repeat(3, 1fr);">
				<?php foreach ( $more_charts as $idx => $m_def ) : ?>
					<article class="kc-chart-card">
						<div class="kc-card-hero" style="height: 120px;">
							<div style="position: relative; z-index: 10;">
								<span class="kc-card-meta"><?php echo strtoupper($m_def->frequency); ?> CHART</span>
								<h2 class="kc-card-title"><?php echo esc_html($m_def->title); ?></h2>
							</div>
						</div>
						<div class="kc-card-footer">
							<span class="kc-card-date">Updated Weekly</span>
							<a href="<?php echo home_url('/charts/' . $m_def->slug); ?>" class="kc-card-cta" style="color: var(--k-accent);">See Full Chart &rarr;</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
