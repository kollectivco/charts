global $wpdb;

/**
 * 1. DATA RESOLUTION (Identify the Chart BEFORE printing anything)
 */
$manager         = new \Charts\Admin\SourceManager();
$definition_id   = get_query_var( 'charts_definition_id' );
$definition_slug = get_query_var( 'charts_definition_slug' );
$definition      = null;

if ( $definition_id ) {
	$definition = $manager->get_definition( $definition_id );
} elseif ( $definition_slug ) {
	$definition = $manager->get_definition_by_slug( $definition_slug );
} else {
	// Fallback to query parameters
	$type      = get_query_var( 'charts_type' );
	$country   = get_query_var( 'charts_country' ) ?: 'eg';
	$frequency = get_query_var( 'charts_frequency' ) ?: 'weekly';
	
	$definition = $wpdb->get_row( $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}charts_definitions 
		WHERE chart_type = %s AND country_code = %s AND frequency = %s LIMIT 1
	", $type, $country, $frequency ) );
}

/**
 * 2. PREMIUM RENDER GATES (Handle logic checks BEFORE header)
 */
function kc_render_empty_state($title, $desc, $cta_text = 'Return Home', $cta_url = '/charts', $is_processing = false) {
	\Charts\Core\StandaloneLayout::get_header();
	echo '<div class="kc-root"><main class="kc-container" style="padding: 160px 0; text-align: center; color: white;">';
	if ($is_processing) {
		echo '<div class="kc-processing-spinner" style="width: 64px; height: 64px; border: 4px solid rgba(255,255,255,0.1); border-top-color: var(--k-accent, #6366f1); border-radius: 50%; margin: 0 auto 32px; animation: kc-spin 1s linear infinite;"></div>';
	} else {
		echo '<div style="font-size: 120px; margin-bottom: 24px; opacity: 0.1; font-weight: 100;">⌬</div>';
	}
	echo '<h1 style="font-size: 48px; font-weight: 850; margin-bottom: 16px; letter-spacing: -0.02em;">' . esc_html($title) . '</h1>';
	echo '<p style="opacity: 0.6; font-size: 18px; max-width: 540px; margin: 0 auto 48px; line-height: 1.6;">' . esc_html($desc) . '</p>';
	echo '<div style="display: flex; gap: 16px; justify-content: center;">';
	echo '<a href="' . esc_url($cta_url) . '" style="display: inline-block; padding: 14px 32px; background: white; color: black; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 13px;">' . esc_html($cta_text) . '</a>';
	if (current_user_can('manage_options')) {
		echo '<a href="' . admin_url('admin.php?page=charts-import') . '" style="display: inline-block; padding: 14px 32px; background: rgba(255,255,255,0.1); color: white; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 13px; border: 1px solid rgba(255,255,255,0.1);">Operator: Import Data</a>';
	}
	echo '</div>';
	echo '</main></div>';
	echo '<style>@keyframes kc-spin { to { transform: rotate(360deg); } }</style>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit; // Force termination of entire WordPress render to prevent leak
}

if ( ! $definition ) {
	kc_render_empty_state(
		'Chart Not Found', 
		'The requested chart intelligence is not available in our current catalog. It may have been retired or the URL has changed.',
		'Return to Directory'
	);
	return;
}

// Fetch matched sources
$sources = $wpdb->get_results( $wpdb->prepare( "
	SELECT id FROM {$wpdb->prefix}charts_sources 
	WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1
", $definition->chart_type, $definition->country_code, $definition->frequency ) );

if ( empty( $sources ) ) {
	kc_render_empty_state(
		'Awaiting Intelligence', 
		'We are currently bridging data sources for this region and market. Our crawlers are initializing the pipeline.',
		'Back to Charts',
		'/charts'
	);
	return;
}

$source_ids = array_column( $sources, 'id' );
$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

// Check for ongoing processing
$processing_run = $wpdb->get_row( $wpdb->prepare( "
	SELECT id FROM {$wpdb->prefix}charts_import_runs 
	WHERE source_id IN ($placeholders) AND status IN ('started', 'processing')
	ORDER BY started_at DESC LIMIT 1
", ...$source_ids ) );

// Fetch period
$period = $wpdb->get_row( $wpdb->prepare( "
	SELECT p.* FROM {$wpdb->prefix}charts_periods p
	JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
	WHERE e.source_id IN ($placeholders)
	ORDER BY p.period_start DESC LIMIT 1
", ...$source_ids ) );

if ( ! $period ) {
	if ($processing_run) {
		kc_render_empty_state(
			'Intelligence Incoming', 
			'Our data pipeline is currently processing the latest market signals. The vault will be active in a few moments.',
			'Refresh Intelligence',
			'',
			true
		);
	} else {
		kc_render_empty_state(
			'Awaiting Initial Load', 
			'No historical records found for the latest period. Data initialization is required to activate this explorer.',
			'Initialize Pipeline',
			admin_url('admin.php?page=charts-import')
		);
	}
/**
 * 3. MAIN RENDER PATH
 */
\Charts\Core\StandaloneLayout::get_header();

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

<div class="kc-root" <?php if (!empty($definition->accent_color)) echo 'style="--k-accent: ' . esc_attr($definition->accent_color) . '; --k-accent-yellow: ' . esc_attr($definition->accent_color) . ';"'; ?>>
	<main class="kc-container">
		
		<!-- 2. BREADCRUMBS -->
		<nav style="padding: 40px 0 20px; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: var(--k-text-muted);">
			<a href="/charts" style="color: inherit; text-decoration: none;">HOME</a> &nbsp; / &nbsp; 
			<a href="/charts" style="color: inherit; text-decoration: none;">CHARTS</a> &nbsp; / &nbsp; 
			<span style="color: white;"><?php echo strtoupper($definition->title); ?></span>
		</nav>

		<!-- 3. PAGE HEADER / TITLE BLOCK -->
		<header class="kc-page-intro" style="margin-bottom: 60px;">
			<div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 40px;">
				<div style="flex: 1; min-width: 320px;">
					<div class="kc-label-bar" style="margin-bottom: 20px; color: var(--k-accent-yellow); font-size: 11px; font-weight: 900; letter-spacing: 0.15em; text-transform: uppercase;">
						<?php echo esc_html($definition->frequency); ?> &bull; <?php echo esc_html($definition->chart_type); ?> &bull; <?php echo strtoupper($definition->country_code); ?>
					</div>
					<h1 class="kc-page-title" style="font-size: clamp(3rem, 6vw, 5.5rem); font-weight: 950; letter-spacing: -0.05em; line-height: 0.9; margin: 0 0 16px; color: white;">
						<?php echo esc_html($definition->title); ?>
					</h1>
					<?php if (!empty($definition->title_ar)): ?>
						<div class="kc-page-subtitle" style="font-size: 24px; font-weight: 700; opacity: 0.6; margin-bottom: 24px;"><?php echo esc_html($definition->title_ar); ?></div>
					<?php endif; ?>
					<p class="kc-page-desc" style="font-size: 16px; line-height: 1.6; color: var(--k-text-dim); max-width: 680px; margin: 0;"><?php echo esc_html($definition->chart_summary); ?></p>
				</div>
				<div class="kc-page-meta-box" style="background: rgba(255,255,255,0.03); border: 1px solid var(--k-border); padding: 32px; border-radius: 20px; display: flex; gap: 40px;">
					<div class="kc-meta-stat">
						<label style="display: block; font-size: 9px; font-weight: 900; color: var(--k-text-muted); text-transform: uppercase; margin-bottom: 8px;">Week of</label>
						<span style="font-size: 13px; font-weight: 800; color: white;"><?php echo date('M j, Y', strtotime($period->period_start)); ?></span>
					</div>
					<div class="kc-meta-stat">
						<label style="display: block; font-size: 9px; font-weight: 900; color: var(--k-text-muted); text-transform: uppercase; margin-bottom: 8px;">Status</label>
						<span style="font-size: 13px; font-weight: 800; color: var(--k-accent-green);">Live &bull; <?php echo count($entries); ?> Items</span>
					</div>
				</div>
			</div>
		</header>

		<!-- 4. FEATURED #1 HERO CARD -->
		<?php if ($featured) : ?>
		<section class="kc-item-hero" style="margin-top: 20px; margin-bottom: 80px;">
			<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-item-hero-bg" alt="Glow" style="opacity: 0.5;">
			<div class="kc-item-hero-content">
				<div class="kc-hero-poster">
					<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="Rank 1 Art">
				</div>
				<div style="flex: 1;">
					<div class="kc-hero-badge">#1 This Week</div>
					<div class="kc-item-title-wrap">
						<h3 class="kc-item-main-title"><?php echo esc_html($featured->track_name); ?></h3>
						<?php if (!empty($featured->artist_names)): ?>
							<a href="#" class="kc-artist-pill" style="margin-top: 16px;">
								<div class="kc-artist-mini-avatar" style="background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 8px;">A</div>
								<span><?php echo esc_html($featured->artist_names); ?></span>
							</a>
						<?php endif; ?>
					</div>
					
					<div class="kc-item-meta-row" style="margin-top: 40px;">
						<div class="kc-stat-pill" style="background: rgba(255,255,255,0.05); padding: 12px 24px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
							<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 4px;">Peak Rank</label>
							<span style="font-size: 18px; font-weight: 900; color: var(--k-accent-yellow);">#<?php echo $featured->peak_rank ?: 1; ?></span>
						</div>
						<div class="kc-stat-pill" style="background: rgba(255,255,255,0.05); padding: 12px 24px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
							<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 4px;">Longevity</label>
							<span style="font-size: 18px; font-weight: 900; color: white;"><?php echo $featured->weeks_on_chart ?: 1; ?> Weeks</span>
						</div>
						<div class="kc-stat-pill" style="background: rgba(255,255,255,0.05); padding: 12px 24px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
							<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 4px;">Market Signal</label>
							<span style="font-size: 18px; font-weight: 900; color: var(--k-accent-green);">▲ NEW</span>
						</div>
					</div>
				</div>
				<div class="kc-hero-actions" style="display: flex; flex-direction: column; gap: 16px;">
					<a href="#" style="background: white; border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; color: black; box-shadow: 0 10px 40px rgba(255,255,255,0.2);">
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

			<div class="kc-rankings-panel" style="background: var(--k-surface); border: 1px solid var(--k-border); border-radius: 24px; overflow: hidden; margin-top: 32px;">
				<div class="kc-table-head" style="display: grid; grid-template-columns: 80px 100px 64px 1fr 100px 100px 120px; padding: 24px 40px; border-bottom: 1px solid var(--k-border); font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted);">
					<div>Rank</div>
					<div>Signal</div>
					<div>Cover</div>
					<div style="padding-left: 20px;">Title / Artist</div>
					<div style="text-align: right;">Last Week</div>
					<div style="text-align: right;">Peak</div>
					<div style="text-align: right;">Longevity</div>
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
					<a href="<?php echo esc_url($item_url); ?>" class="kc-rank-row" style="display: grid; grid-template-columns: 80px 100px 64px 1fr 100px 100px 120px; padding: 20px 40px; border-bottom: 1px solid rgba(255,255,255,0.03); align-items: center; text-decoration: none; color: inherit; transition: background 0.2s;">
						<div class="kc-rank-val" style="font-size: 24px; font-weight: 950; letter-spacing: -0.05em;"><?php echo (int)$row->rank_position; ?></div>
						<div class="kc-move-val" style="color: <?php echo $dir_color; ?>; font-size: 11px; font-weight: 900; letter-spacing: 0.05em;">
							<?php echo $dir_icon; ?> <?php echo $val_move; ?>
							<?php echo $growth_str; ?>
						</div>
						<div><img src="<?php echo esc_url($row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-row-art" alt="Art" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover;"></div>
						<div style="padding-left: 20px; min-width: 0;">
							<span class="kc-row-name" style="display: block; font-size: 16px; font-weight: 800; color: white; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($row->track_name); ?></span>
							<span class="kc-row-artist" style="display: block; font-size: 13px; font-weight: 600; opacity: 0.5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($row->artist_names); ?></span>
						</div>
						<div class="kc-stat-val muted" style="text-align: right; opacity: 0.4; font-weight: 700;"><?php echo (int)($row->previous_rank ?: 0) ?: '—'; ?></div>
						<div class="kc-stat-val muted" style="text-align: right; opacity: 0.4; font-weight: 700;">#<?php echo (int)($row->peak_rank ?: $row->rank_position); ?></div>
						<div class="kc-stat-val" style="text-align: right; font-weight: 800;"><?php echo $row->weeks_on_chart ?: 1; ?> wks</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<div style="padding: 100px 0;"></div>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
