<?php
/**
 * Kontentainment Charts — Single Chart Intelligence
 * Dynamic Light-Mode Bento System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;
$manager      = new \Charts\Admin\SourceManager();
$definition_id = get_query_var( 'charts_definition_id' );

if ( ! $definition_id ) {
	// Fallback for legacy URLs or direct access
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
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Chart Not Found</h1><p>Intelligence record not available.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch matches sources
$sources = $wpdb->get_results( $wpdb->prepare( "
	SELECT id FROM {$wpdb->prefix}charts_sources 
	WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1
", $definition->chart_type, $definition->country_code, $definition->frequency ) );

if ( empty( $sources ) ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>No Data Sources</h1><p>This chart has no active data sources linked.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

$source_ids = array_column( $sources, 'id' );
$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

// Fetch most recent period
$period = $wpdb->get_row( $wpdb->prepare( "
	SELECT p.* FROM {$wpdb->prefix}charts_periods p
	JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
	WHERE e.source_id IN ($placeholders)
	ORDER BY p.period_start DESC LIMIT 1
", ...$source_ids ) );

if ( ! $period ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Awaiting Intelligence</h1><p>No data has been imported for this chart cycle yet.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch all entries for this period
$entries = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM {$wpdb->prefix}charts_entries 
	WHERE source_id IN ($placeholders) AND period_id = %d
	ORDER BY rank_position ASC
", ...$source_ids, $period->id ) );

?>

<div class="kc-root">
	
	<!-- Premium Header -->
	<header class="kc-chart-header" style="padding: 100px 0 60px; background: linear-gradient(to bottom, #fff, var(--k-bg));">
		<div class="kc-container">
			<nav class="sc-breadcrumb" style="margin-bottom: 24px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
				<a href="<?php echo home_url('/charts/'); ?>" style="color: var(--k-accent);">Intelligence Dashboard</a> / 
				<span><?php echo esc_html(strtoupper($definition->country_code)); ?></span>
			</nav>
			
			<div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 40px;">
				<div style="flex: 1;">
					<span class="kc-brand-name" style="margin-bottom: 12px; display: block;"><?php echo strtoupper($definition->frequency); ?> • <?php echo strtoupper($definition->chart_type); ?></span>
					<h1 class="kc-hero-title" style="margin-bottom: 16px;"><?php echo esc_html( $definition->title ); ?></h1>
					<p style="font-size: 1.15rem; font-weight: 500; color: var(--k-text-dim); max-width: 700px;">
						<?php echo esc_html( $definition->chart_summary ); ?>
					</p>
				</div>
				<div class="kc-chart-meta-box" style="text-align: right;">
					<div class="kc-badge kc-badge-accent" style="margin-bottom: 12px;">
						PERIOD: <?php echo date('M d, Y', strtotime($period->period_start)); ?>
					</div>
					<div style="font-size: 12px; font-weight: 700; color: var(--k-text-muted);">
						UPDATED: <?php echo date('F j, Y', strtotime($period->created_at)); ?>
					</div>
				</div>
			</div>
		</div>
	</header>

	<main class="kc-container" style="padding-bottom: 120px;">
		
		<div class="kc-chart-table">
			<?php if ( empty( $entries ) ) : ?>
				<div style="padding: 100px; text-align: center; color: var(--k-text-dim);">
					<p><?php _e( 'The intelligence core is currently processing data for this timeframe.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<?php foreach ( $entries as $idx => $row ) : 
					$is_first = ($idx === 0);
					$is_new = ($row->movement_direction === 'new');
					$is_up = ($row->movement_direction === 'up');
					$is_down = ($row->movement_direction === 'down');
					$is_reentry = ($row->movement_direction === 're-entry');
					
					$primary = $row->track_name;
					$secondary = $row->artist_names;
					$image = $row->cover_image;
				?>
					<div class="kc-row-item <?php echo $is_first ? 'kc-row-featured' : ''; ?>">
						<details class="sc-details" <?php echo $is_first ? 'open' : ''; ?>>
							<summary class="kc-row-summary">
								<div class="kc-row-rank">
									<?php echo $row->rank_position; ?>
								</div>
								
								<div class="kc-row-img-wrap">
									<img src="<?php echo esc_url($image); ?>" class="kc-row-art" alt="<?php echo esc_attr($primary); ?>">
								</div>

								<div class="kc-row-info">
									<h4 class="kc-row-title"><?php echo esc_html($primary); ?></h4>
									<p class="kc-row-subtitle"><?php echo esc_html($secondary); ?></p>
								</div>

								<div class="kc-row-movement stat-opt">
									<?php if ($is_up): ?>
										<span style="color: var(--k-success); font-weight: 850;">▲ <?php echo $row->movement_value; ?></span>
									<?php elseif ($is_down): ?>
										<span style="color: var(--k-error); font-weight: 850;">▼ <?php echo $row->movement_value; ?></span>
									<?php elseif ($is_new): ?>
										<span class="kc-badge kc-badge-accent">NEW</span>
									<?php elseif ($is_reentry): ?>
										<span class="kc-badge" style="background: #fefce8; color: #854d0e;">RE-ENTRY</span>
									<?php else: ?>
										<span style="color: var(--k-text-muted);">&minus;</span>
									<?php endif; ?>
								</div>

								<div class="kc-row-vitals stat-opt">
									<div style="display: flex; gap: 24px;">
										<div class="vital-item">
											<span class="kc-stat-lbl">PEAK</span>
											<span style="font-size: 14px; font-weight: 800;">#<?php echo $row->peak_rank ?: $row->rank_position; ?></span>
										</div>
										<div class="vital-item">
											<span class="kc-stat-lbl">WKS</span>
											<span style="font-size: 14px; font-weight: 800;"><?php echo $row->weeks_on_chart ?: 1; ?></span>
										</div>
									</div>
								</div>
							</summary>

							<div class="kc-row-expanded" style="padding: 40px; background: #fafafa; border-top: 1px solid var(--k-border);">
								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px;">
									<div class="expanded-intel">
										<h5 class="kc-brand-name" style="margin-bottom: 24px;"><?php _e( 'Historical Intelligence', 'charts' ); ?></h5>
										<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
											<div class="kc-stat-item">
												<span class="kc-stat-lbl">Last Week</span>
												<span class="kc-stat-val" style="font-size: 1.25rem;">#<?php echo $row->previous_rank ?: '—'; ?></span>
											</div>
											<div class="kc-stat-item">
												<span class="kc-stat-lbl">Highest Rank</span>
												<span class="kc-stat-val" style="font-size: 1.25rem;">#<?php echo $row->peak_rank ?: $row->rank_position; ?></span>
											</div>
											<div class="kc-stat-item">
												<span class="kc-stat-lbl">Velocity</span>
												<span class="kc-stat-val" style="font-size: 1.25rem;"><?php echo ($is_up ? '+' : ($is_down ? '-' : '')) . $row->movement_value; ?></span>
											</div>
										</div>
									</div>
									<div class="expanded-actions" style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; gap: 16px;">
										<a href="<?php echo home_url('/charts/track/' . sanitize_title($primary) . '/'); ?>" class="kc-btn">
											<?php _e( 'Full Intelligence Explorer', 'charts' ); ?> &rarr;
										</a>
										<div style="display: flex; gap: 12px;">
											<?php if ( $row->spotify_id ) : ?>
												<a href="https://open.spotify.com/track/<?php echo esc_attr($row->spotify_id); ?>" target="_blank" class="kc-badge" style="color: #1DB954; font-weight: 900;">SPOTIFY</a>
											<?php endif; ?>
											<?php if ( $row->youtube_id ) : ?>
												<a href="https://youtube.com/watch?v=<?php echo esc_attr($row->youtube_id); ?>" target="_blank" class="kc-badge" style="color: #FF0000; font-weight: 900;">YOUTUBE</a>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
						</details>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
