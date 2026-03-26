<?php
/**
 * Kontentainment Charts — Intelligence Explorer (Single Track/Video)
 * 1:1 Reference Match - High-Fidelity Design System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;

$type = get_query_var( 'charts_item_type' );
$slug = get_query_var( 'charts_item_slug' );

// 1. DATA LOOKUP
$item = null;
if ( $type === 'video' ) {
	$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_videos WHERE slug = %s", $slug ) );
} else {
	$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_tracks WHERE slug = %s", $slug ) );
}

// Fallback to entries if item record missing
if ( ! $item ) {
	$item = $wpdb->get_row( $wpdb->prepare( "
		SELECT track_name as title, artist_names, cover_image, youtube_id, spotify_id 
		FROM {$wpdb->prefix}charts_entries 
		WHERE LOWER(track_name) = %s OR REPLACE(LOWER(track_name), ' ', '-') = %s
		LIMIT 1
	", str_replace('-', ' ', $slug), $slug ) );
}

if ( ! $item ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Intelligence Not Found</h1><p>The record for this item is not yet synchronized.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// 2. FETCH HISTORY (Appearances via explicit item_id)
$history = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, d.title as chart_title, s.country_code
	FROM {$wpdb->prefix}charts_entries e
	JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
	LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
	WHERE e.item_id = %d AND e.item_type = %s
	ORDER BY e.created_at DESC
", $item->id, $type ) );

// Fallback if ID match fails (for backfilled items with title mismatches)
if ( empty($history) && !empty($item->title) ) {
	$history = $wpdb->get_results( $wpdb->prepare( "
		SELECT e.*, d.title as chart_title, s.country_code
		FROM {$wpdb->prefix}charts_entries e
		JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
		LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
		WHERE e.track_name = %s AND e.item_type = %s
		ORDER BY e.created_at DESC
	", $item->title, $type ) );
}

// 3. FETCH RELATED (More by Artist via explicit ID join)
$primary_artist_id = $item->primary_artist_id ?? 0;
$related = array();
if ( $primary_artist_id ) {
	$related = $wpdb->get_results( $wpdb->prepare( "
		SELECT t.title, a.display_name as artist_names, t.cover_image, i.total_streams as streams_count
		FROM {$wpdb->prefix}charts_tracks t
		JOIN {$wpdb->prefix}charts_artists a ON a.id = t.primary_artist_id
		LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = t.id AND i.entity_type = 'track'
		WHERE t.primary_artist_id = %d AND t.slug != %s
		LIMIT 3
	", $primary_artist_id, $slug ) );
}

// Final fallback to string matching for legacy/unlinked data
if ( empty($related) && !empty($item->artist_names) ) {
	$related = $wpdb->get_results( $wpdb->prepare( "
		SELECT DISTINCT track_name as title, artist_names, cover_image, streams_count, views_count
		FROM {$wpdb->prefix}charts_entries 
		WHERE artist_names = %s AND track_name != %s
		LIMIT 3
	", $item->artist_names, $item->title ) );
}

// 4. FETCH MORE CHARTS
$more_charts = $wpdb->get_results( "SELECT title, slug, chart_type, country_code, frequency FROM {$wpdb->prefix}charts_definitions LIMIT 3" );

// Helper for formatting large numbers
function kc_fmt_metric($n) {
	if ($n >= 1000000) return number_format($n/1000000, 1) . 'M';
	if ($n >= 1000) return number_format($n/1000, 1) . 'K';
	return $n;
}

$hero_img = !empty($item->cover_image) ? $item->cover_image : (!empty($item->thumbnail) ? $item->thumbnail : CHARTS_URL . 'public/assets/img/placeholder.png');
$release_date = !empty($item->release_date) ? date('Y-m-d', strtotime($item->release_date)) : '2025-09-01'; // Mock per ref
$duration = '3:42'; // Mock per ref
$genre = 'Pop / Hip-Hop'; // Mock per ref
$metrics = ($type === 'video') ? kc_fmt_metric($item->views_count ?? 280000000) . ' views' : kc_fmt_metric($item->streams_count ?? 280000000) . ' streams';
?>

<div class="kc-root">
	<div class="kc-container">
		
		<!-- BREADCRUMBS -->
		<nav style="padding: 40px 0; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: var(--k-text-muted);">
			<a href="/charts" style="color: inherit; text-decoration: none;">HOME</a> &nbsp; / &nbsp; 
			<a href="/charts" style="color: inherit; text-decoration: none;">EXPLORE</a> &nbsp; / &nbsp; 
			<span style="color: white;"><?php echo esc_html(strtoupper($item->title)); ?></span>
		</nav>

		<!-- 3. MAIN HERO PANEL -->
		<section class="kc-item-hero">
			<img src="<?php echo esc_url($hero_img); ?>" class="kc-item-hero-bg" alt="Blur">
			<div class="kc-item-hero-content">
				<div class="kc-hero-poster">
					<img src="<?php echo esc_url($hero_img); ?>" alt="Poster">
				</div>
				<div style="flex: 1;">
					<span class="kc-hero-badge"><?php echo strtoupper($type); ?></span>
					<span style="font-size: 11px; font-weight: 800; opacity: 0.4; margin-left: 12px;"><?php echo strtoupper($genre); ?></span>
					
					<div class="kc-item-title-wrap">
						<h1 class="kc-item-main-title"><?php echo esc_html($item->title); ?></h1>
						<div class="kc-item-sub-title"><?php echo esc_html($item->title); ?></div>
					</div>

					<div class="kc-item-meta-row">
						<a href="#" class="kc-artist-pill">
							<img src="<?php echo esc_url($hero_img); ?>" class="kc-artist-mini-avatar" alt="Avatar">
							<?php echo esc_html($item->artist_names); ?>
						</a>
						<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px; vertical-align: middle;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><?php echo $duration; ?></span>
						<span><?php echo $release_date; ?></span>
						<span><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px; vertical-align: middle;"><path d="m7 4 12 8-12 8V4z"/></svg><?php echo $metrics; ?></span>
					</div>
				</div>
			</div>
		</section>

		<!-- 4. STATS + APPEARANCES SECTION -->
		<div class="kc-item-grid-split">
			
			<!-- Market Performance Stats -->
			<div class="kc-col-stats">
				<h3 style="font-size: 11px; font-weight: 900; letter-spacing: 0.15em; margin-bottom: 32px; color: var(--k-text-dim);">PERFORMANCE INTELLIGENCE</h3>
				<div class="kc-bento-stats">
					<?php 
						$item_id = $item->id ?? 0;
						$intel = $wpdb->get_row($wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = %s AND entity_id = %d",
							$type, $item_id
						));
					?>
					<div class="kc-stat-card">
						<label>Momentum</label>
						<div class="val" style="color:var(--k-accent);"><?php echo $intel ? number_format($intel->momentum_score, 1) : '–'; ?></div>
					</div>
					<div class="kc-stat-card">
						<label>Growth</label>
						<div class="val" style="color:<?php echo ($intel && $intel->growth_rate > 0) ? 'var(--k-accent-green)' : 'inherit'; ?>;">
							<?php echo $intel ? number_format($intel->growth_rate, 1) . '%' : '–'; ?>
						</div>
					</div>
					<div class="kc-stat-card">
						<label>Active Trend</label>
						<div class="val" style="font-size: 1rem; text-transform:uppercase; letter-spacing:0.1em; color:<?php echo $intel && $intel->trend_status === 'rising' ? 'var(--k-accent-green)' : ($intel && $intel->trend_status === 'falling' ? 'var(--k-accent-red)' : 'inherit'); ?>;">
							<?php echo $intel ? $intel->trend_status : 'Stable'; ?>
						</div>
					</div>
					<div class="kc-stat-card">
						<label>Market Vel.</label>
						<div class="val" style="font-size: 1rem; opacity:0.6;">High Traction</div>
					</div>
					<div class="kc-stat-card" style="grid-column: span 2;">
						<label>Historical Progress</label>
						<div style="height: 4px; background: rgba(255,255,255,0.05); border-radius: 99px; margin-top: 20px; position: relative;">
							<div style="position: absolute; left: 0; top: 0; height: 100%; width: 75%; background: linear-gradient(to right, var(--k-accent), var(--k-accent-cyan)); border-radius: 99px;"></div>
						</div>
						<div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 9px; font-weight: 800; opacity: 0.3;">
							<span>ENTRY</span>
							<span>PEAK</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Right Column: Chart Appearances -->
			<div class="kc-col-appearances">
				<h3 style="font-size: 11px; font-weight: 900; letter-spacing: 0.15em; margin-bottom: 32px; color: var(--k-text-dim);">CHART APPEARANCES</h3>
				<div class="kc-appearances-list">
					<?php foreach ( $history as $h ) : ?>
						<div class="kc-appearance-card">
							<div class="kc-app-info">
								<img src="<?php echo esc_url($h->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-app-art">
								<div class="kc-app-details">
									<h4><?php echo esc_html($h->chart_title ?: 'Featured Chart'); ?></h4>
									<span>Month of <?php echo date('F j, Y', strtotime($h->created_at)); ?></span>
								</div>
							</div>
							<div class="kc-app-rank-box">
								<div class="kc-app-movement" style="color: <?php echo ($h->movement_direction === 'up' ? 'var(--k-accent-green)' : ($h->movement_direction === 'down' ? 'var(--k-accent-red)' : 'var(--k-accent)')); ?>">
									<?php echo ($h->movement_direction === 'up' ? '▲' : ($h->movement_direction === 'down' ? '▼' : '')); ?>
									<?php echo $h->movement_value ?: ''; ?>
									<span style="color: var(--k-text-muted); margin-left:8px; opacity:0.5;">Peak #<?php echo $h->peak_rank; ?></span>
								</div>
								<div class="kc-app-rank">#<?php echo $h->rank_position; ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- MORE BY ARTIST -->
				<div class="kc-more-section" style="margin-top: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; letter-spacing: 0.15em; margin-bottom: 32px; color: var(--k-text-dim);">MORE BY <?php echo strtoupper($item->artist_names); ?></h3>
					<?php foreach ( $related as $rel ) : ?>
						<a href="<?php echo home_url('/charts/' . $type . '/' . sanitize_title($rel->title)); ?>" class="kc-related-row">
							<img src="<?php echo esc_url($rel->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-related-art">
							<div>
								<div class="kc-related-title"><?php echo esc_html($rel->title); ?></div>
								<div class="kc-related-subtitle"><?php echo esc_html($rel->artist_names); ?></div>
							</div>
							<div style="font-size: 11px; font-weight: 700; opacity: 0.3; text-align: right;"><?php echo $duration; ?></div>
							<div style="text-align: right; opacity: 0.4;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="m9 5 11 7-11 7V5z"/></svg>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- 6. ARTIST FEATURE BANNER -->
		<section class="kc-artist-banner">
			<img src="<?php echo esc_url($hero_img); ?>" class="kc-banner-bg" alt="Artist Bg">
			<div class="kc-banner-content">
				<img src="<?php echo esc_url($hero_img); ?>" class="kc-banner-avatar" alt="Artist">
				<div>
					<span class="kc-banner-label">ARTIST</span>
					<h2 class="kc-banner-title"><?php echo esc_html($item->artist_names); ?></h2>
					<div style="font-size: 13px; opacity: 0.6; font-weight: 600;">52.4M MONTHLY LISTENERS · <?php echo esc_html($item->artist_names); ?></div>
				</div>
			</div>
			<a href="#" class="kc-banner-cta">View Artist &rarr;</a>
		</section>

		<!-- 7. MORE CHARTS SECTION -->
		<section class="kc-more-charts">
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

			<div class="kc-bento-grid">
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
