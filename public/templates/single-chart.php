<?php
/**
 * Kontentainment Charts — Single Chart Intelligence Explorer
 * Dynamic Light-Mode Bento System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;
$manager      = new \Charts\Admin\SourceManager();
$definition_id = get_query_var( 'charts_definition_id' );

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
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Awaiting Intelligence</h1><p>No data imported for this chart cycle.</p></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch entries
$entries = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM {$wpdb->prefix}charts_entries 
	WHERE source_id IN ($placeholders) AND period_id = %d
	ORDER BY rank_position ASC
", ...$source_ids, $period->id ) );

if ( empty( $entries ) ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;"><h1>Empty Data Set</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

$featured = $entries[0];
$rankings = array_slice( $entries, 1 );

?>

<div class="kc-root">
	
	<!-- Landing Hero Section -->
	<header class="kc-single-header">
		<div class="kc-container">
			<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 40px;">
				<div style="flex-grow: 1;">
					<nav class="kc-hero-meta">
						<span><?php echo strtoupper($definition->frequency); ?></span>
						<span>•</span>
						<span><?php echo strtoupper($definition->chart_type); ?></span>
					</nav>
					<h1 class="kc-hero-primary"><?php echo esc_html($definition->title); ?></h1>
					<h2 class="kc-hero-secondary">أفضل ١٠٠ أغنية</h2>
					<p style="color: var(--k-text-dim); font-size: 1.15rem; max-width: 700px; font-weight: 500;">
						<?php echo esc_html($definition->chart_summary); ?>
					</p>
				</div>
				<div class="kc-hero-info-grid" style="text-align: right; display: grid; gap: 12px; font-size: 11px; font-weight: 850; opacity: 0.6; text-transform: uppercase;">
					<span>Week of <?php echo date('F j, Y', strtotime($period->period_start)); ?></span>
					<span>Updated <?php echo $definition->frequency; ?></span>
					<span><?php echo count($entries); ?> Entries</span>
				</div>
			</div>
		</div>
	</header>

	<section class="kc-container">
		
		<!-- Featured #1 Spotlight -->
		<article class="kc-featured-card">
			<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-featured-bg" alt="Background">
			<div class="kc-featured-content">
				<div class="kc-featured-art-wrap">
					<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-featured-art" alt="Track Art">
				</div>
				<div class="kc-featured-info">
					<div class="kc-featured-badge">#1 This Week</div>
					<h3 class="kc-featured-title"><?php echo esc_html($featured->track_name); ?></h3>
					<p class="kc-featured-artist"><?php echo esc_html($featured->artist_names); ?></p>
					
					<div class="kc-featured-vital-bar">
						<div class="kc-vital-item">
							<span class="kc-vital-lbl">Total Views</span>
							<span class="kc-vital-val"><?php echo number_format($featured->views_count / 1000000, 1); ?>M</span>
						</div>
						<div class="kc-vital-item">
							<span class="kc-vital-lbl">Peak</span>
							<span class="kc-vital-val">#<?php echo $featured->peak_rank ?: 1; ?></span>
						</div>
						<div class="kc-vital-item">
							<span class="kc-vital-lbl">Wks on Chart</span>
							<span class="kc-vital-val"><?php echo $featured->weeks_on_chart ?: 1; ?></span>
						</div>
					</div>
				</div>
			</div>
		</article>

		<!-- Full Rankings Table -->
		<div style="background: white; border-top: 1px solid var(--k-border); padding-top: 40px; margin-bottom: 120px;">
			<h4 style="font-size: 10px; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase; color: var(--k-text-dim); margin-bottom: 32px;">FULL RANKINGS</h4>
			
			<table class="kc-rankings-table">
				<thead>
					<tr>
						<th>Rank</th>
						<th style="text-align: center;">Move</th>
						<th>Cover</th>
						<th>Title</th>
						<th class="kc-stat-cell-hide" style="text-align: right;">Last Wk</th>
						<th class="kc-stat-cell-hide" style="text-align: right;">Peak</th>
						<th class="kc-stat-cell-hide" style="text-align: right;">Wks On Chart</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rankings as $row ) : 
						$is_up = ($row->movement_direction === 'up');
						$is_down = ($row->movement_direction === 'down');
						$is_new = ($row->movement_direction === 'new');
					?>
						<tr class="kc-ranking-row">
							<td>
								<span class="kc-rank-num"><?php echo $row->rank_position; ?></span>
							</td>
							<td class="kc-rank-move">
								<?php if ($is_up) : ?>
									<span class="kc-up">▲ <?php echo $row->movement_value; ?></span>
								<?php elseif ($is_down) : ?>
									<span class="kc-down">▼ <?php echo $row->movement_value; ?></span>
								<?php elseif ($is_new) : ?>
									<span class="kc-new">NEW</span>
								<?php else : ?>
									<span style="opacity: 0.3;">−</span>
								<?php endif; ?>
							</td>
							<td class="kc-rank-art-cell">
								<img src="<?php echo esc_url($row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="kc-rank-art" alt="Art">
							</td>
							<td class="kc-rank-info-cell">
								<span class="kc-rank-name"><?php echo esc_html($row->track_name); ?></span>
								<span class="kc-rank-artist"><?php echo esc_html($row->artist_names); ?></span>
							</td>
							<td class="kc-stat-cell kc-stat-cell-hide">
								<span class="kc-stat-val-sub"><?php echo $row->previous_rank ?: '—'; ?></span>
							</td>
							<td class="kc-stat-cell kc-stat-cell-hide">
								<span class="kc-stat-val-sub">#<?php echo $row->peak_rank ?: $row->rank_position; ?></span>
							</td>
							<td class="kc-stat-cell kc-stat-cell-hide">
								<span class="kc-stat-val-main"><?php echo $row->weeks_on_chart ?: 1; ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	</section>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
