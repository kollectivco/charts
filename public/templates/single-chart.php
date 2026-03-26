<?php
/**
 * Kontentainment Charts — Single Chart Intelligence Explorer
 * 1:1 Reference Match - High-Fidelity Design System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;

$manager      = new \Charts\Admin\SourceManager();
$definition_id = get_query_var( 'charts_definition_id' );

// Data retrieval
if ( ! $definition_id ) {
	$type      = get_query_var( 'charts_type' );
	$country   = get_query_var( 'charts_country' ) ?: 'eg';
	$frequency = get_query_var( 'charts_frequency' ) ?: 'weekly';
	
	$definition = $wpdb->get_row( $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}charts_definitions 
		WHERE chart_type = %s AND country_code = %s AND frequency = %s LIMIT 1
	", $type, $country, $frequency ) );
} else {
	$definition = $manager->get_definition( $definition_id );
}

if ( ! $definition ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Chart Not Found</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch matches sources
$sources = $wpdb->get_results( $wpdb->prepare( "
	SELECT id FROM {$wpdb->prefix}charts_sources 
	WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1
", $definition->chart_type, $definition->country_code, $definition->frequency ) );

if ( empty( $sources ) ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Awaiting Intelligence</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

$source_ids = array_column( $sources, 'id' );
$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

// Fetch period
$period = $wpdb->get_row( $wpdb->prepare( "
	SELECT p.* FROM {$wpdb->prefix}charts_periods p
	JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
	WHERE e.source_id IN ($placeholders)
	ORDER BY p.period_start DESC LIMIT 1
", ...$source_ids ) );

if ( ! $period ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Awaiting Imports</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch all entries
$entries = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM {$wpdb->prefix}charts_entries 
	WHERE source_id IN ($placeholders) AND period_id = %d
	ORDER BY rank_position ASC
", ...$source_ids, $period->id ) );

$featured = !empty($entries) ? $entries[0] : null;
$rankings = array_slice($entries, 1);

// Helper for large numbers
function kc_fmt($n) {
	if ($n >= 1000000) return number_format($n/1000000, 1) . 'M';
	return number_format($n);
}

// Arabic subtitle map or default
$arabic_subtitle = "أفضل ١٠٠ أغنية";
?>

<div class="kc-root">
	<main class="kc-container">
		
		<!-- 2. BREADCRUMBS -->
		<nav style="padding: 40px 0 20px; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: var(--k-text-muted);">
			<a href="/charts" style="color: inherit; text-decoration: none;">HOME</a> &nbsp; / &nbsp; 
			<a href="/charts" style="color: inherit; text-decoration: none;">CHARTS</a> &nbsp; / &nbsp; 
			<span style="color: white;"><?php echo strtoupper($definition->title); ?></span>
		</nav>

		<!-- 3. PAGE HEADER / TITLE BLOCK -->
		<header class="kc-page-intro" style="display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 10;">
			<div>
				<div class="kc-label-bar">
					<?php echo strtoupper($definition->frequency); ?> &bull; <?php echo strtoupper($definition->chart_type); ?>
					<?php if ($definition->accent_color): ?>
						<span class="kc-dot" style="background: <?php echo esc_attr($definition->accent_color); ?>; display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-left: 8px;"></span>
					<?php endif; ?>
				</div>
				<h1 class="kc-page-title"><?php echo esc_html($definition->title); ?></h1>
				<?php if (!empty($definition->title_ar)): ?>
					<div class="kc-page-subtitle" style="font-family: inherit; margin-top: 5px; opacity: 0.8;"><?php echo esc_html($definition->title_ar); ?></div>
				<?php else: ?>
					<div class="kc-page-subtitle"><?php echo $arabic_subtitle; ?></div>
				<?php endif; ?>
				<p class="kc-page-desc"><?php echo esc_html($definition->chart_summary); ?></p>
			</div>
			<div style="display: flex; gap: 32px; font-size: 11px; font-weight: 850; opacity: 0.6; text-transform: uppercase; margin-bottom: 24px;">
				<span>Week of <?php echo date('M j, Y', strtotime($period->period_start)); ?></span>
				<span>Updated <?php echo $definition->frequency; ?></span>
				<span><?php echo count($entries); ?> entries</span>
			</div>
		</header>

		<!-- 4. FEATURED #1 HERO CARD -->
		<?php if ($featured) : ?>
		<section class="kc-rank-hero-box">
			<img src="<?php echo esc_url($featured->cover_image); ?>" class="kc-rank-hero-bg" alt="Blur">
			<div class="kc-rank-hero-content">
				<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-rank-hero-art" alt="Art">
				<div style="flex: 1;">
					<div class="kc-hero-badge-row">
						<span class="kc-hero-tag">#1 THIS WEEK</span>
						<span class="kc-hero-move" style="color: var(--k-accent-green);">▲ <?php echo $featured->movement_value ?: 0; ?></span>
					</div>
					<h3 class="kc-hero-title-big"><?php echo esc_html($featured->track_name); ?></h3>
					<div class="kc-hero-artist-sub"><?php echo esc_html($featured->artist_names); ?></div>
					
					<div class="kc-hero-stats-row">
						<div class="kc-hero-stat"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5;"><path d="M8 5v14l11-7z"/></svg> <strong><?php echo kc_fmt($featured->views_count ?: 280000000); ?></strong> Total Streams</div>
						<div class="kc-hero-stat"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> Peak <strong>#<?php echo $featured->peak_rank ?: 1; ?></strong></div>
						<div class="kc-hero-stat"><strong><?php echo $featured->weeks_on_chart ?: 1; ?></strong> wks on chart</div>
						<div class="kc-hero-stat">3:42</div>
					</div>
				</div>
				<div style="text-align: right;">
					<a href="#" style="background: rgba(255,255,255,0.1); border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; color: white;">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
					</a>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- 5. FULL RANKINGS TABLE -->
		<section class="kc-full-rankings">
			<header class="kc-section-header">
				<h2 class="kc-header-title" style="font-size: 11px; font-weight: 850; letter-spacing: 0.15em; color: var(--k-text-dim);">FULL RANKINGS</h2>
				<span style="font-size: 11px; font-weight: 700; opacity: 0.4;">Week of <?php echo date('F j, Y', strtotime($period->period_start)); ?></span>
			</header>

			<div class="kc-rankings-panel">
				<div class="kc-table-head">
					<div>Rank</div>
					<div>Move</div>
					<div>Cover</div>
					<div style="padding-left: 20px;">Title</div>
					<div style="text-align: right;">Last Wk</div>
					<div style="text-align: right;">Peak</div>
					<div style="text-align: right;">Wks On Chart</div>
				</div>

				<?php foreach ($rankings as $row) : 
					// Fetch Intelligence
					$intel = $wpdb->get_row($wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = %s AND entity_id = %d",
						$row->item_type, $row->item_id
					));

					$val_move = $row->movement_value ?: '';
					$dir_color = 'var(--k-text-dim)';
					$dir_icon = '—';
					$growth_str = '';

					if ($intel) {
						if ($intel->trend_status === 'rising') { $dir_color = 'var(--k-accent-green)'; $dir_icon = '▲'; }
						elseif ($intel->trend_status === 'falling') { $dir_color = 'var(--k-accent-red)'; $dir_icon = '▼'; }
						elseif ($intel->trend_status === 'new') { $dir_color = 'var(--k-accent-yellow)'; $dir_icon = 'NEW'; $val_move = ''; }
						
						if ($intel->growth_rate > 0) $growth_str = ' <span style="font-size:9px; opacity:0.5;">+' . number_format($intel->growth_rate, 0) . '%</span>';
					} else {
						// Fallback to entry movement
						if ($row->movement_direction === 'up') { $dir_color = 'var(--k-accent-green)'; $dir_icon = '▲'; }
						elseif ($row->movement_direction === 'down') { $dir_color = 'var(--k-accent-red)'; $dir_icon = '▼'; }
						elseif ($row->movement_direction === 'new') { $dir_color = 'var(--k-accent-yellow)'; $dir_icon = 'NEW'; $val_move = ''; }
					}

					// Build item slug URL
					$item_slug = $row->item_slug ?: sanitize_title($row->track_name);
					$item_url = home_url('/charts/' . ($row->item_type === 'video' ? 'video' : 'track') . '/' . $item_slug);
				?>
					<a href="<?php echo esc_url($item_url); ?>" class="kc-rank-row">
						<div class="kc-rank-val"><?php echo $row->rank_position; ?></div>
						<div class="kc-move-val" style="color: <?php echo $dir_color; ?>;">
							<?php echo $dir_icon; ?> <?php echo $val_move; ?>
							<?php echo $growth_str; ?>
						</div>
						<div><img src="<?php echo esc_url($row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-row-art" alt="Art"></div>
						<div style="padding-left: 20px; min-width: 0;">
							<span class="kc-row-name"><?php echo esc_html($row->track_name); ?></span>
							<span class="kc-row-artist"><?php echo esc_html($row->artist_names); ?></span>
						</div>
						<div class="kc-stat-val muted"><?php echo $row->previous_rank ?: '—'; ?></div>
						<div class="kc-stat-val muted">#<?php echo $row->peak_rank ?: $row->rank_position; ?></div>
						<div class="kc-stat-val"><?php echo $row->weeks_on_chart ?: 1; ?></div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<div style="padding: 100px 0;"></div>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
