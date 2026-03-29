<?php
/**
 * Kontentainment Charts — Single Chart (Light Mode)
 * Matches Reference #2
 */

global $wpdb;

// 1. DATA LOOKUP
$definition_slug = get_query_var( 'charts_definition_slug' );
$definition      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE slug = %s", $definition_slug ) );

$page_state = 'not_found';
$sources    = array();
$entries    = array();
$period     = null;

if ( $definition ) {
	$page_state = 'ready';
	$sources    = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s AND country_code = %s AND is_active = 1", $definition->chart_type, $definition->country_code ) );

	if ( empty( $sources ) ) {
		$page_state = 'disconnected';
	} else {
		$source_ids = array_column( $sources, 'id' );
		$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

		$period = $wpdb->get_row( $wpdb->prepare( "
			SELECT p.* FROM {$wpdb->prefix}charts_periods p
			JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
			WHERE e.source_id IN ($placeholders)
			ORDER BY p.period_start DESC LIMIT 1
		", ...$source_ids ) );

		if ( $period ) {
			$query_params = array_values( $source_ids );
			$query_params[] = $period->id;
			$entries = $wpdb->get_results( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}charts_entries 
				WHERE source_id IN ($placeholders) AND period_id = %d
				ORDER BY rank_position ASC
			", ...$query_params ) );
		} else {
			$page_state = 'empty';
		}
	}
}

\Charts\Core\StandaloneLayout::get_header();
?>

<div class="kc-root">
	<div class="kc-container">
		
		<?php if ( $page_state === 'not_found' ) : ?>
			<section class="kc-page-hero" style="text-align: center;"><h1>Chart Not Found</h1><p>The requested chart definition does not exist.</p></section>
		<?php else : ?>
			
			<div class="kc-breadcrumb">
				<a href="<?php echo home_url('/charts'); ?>">Home</a> <span>/</span> <a href="<?php echo home_url('/charts'); ?>">Charts</a> <span>/</span> <?php echo esc_html($definition->title); ?>
			</div>

			<header class="kc-page-hero" style="padding: 20px 0 60px;">
				<div class="kc-eyebrow">
					<svg width="6" height="6" viewBox="0 0 6 6" fill="currentColor" style="color: var(--k-accent);"><circle cx="3" cy="3" r="3"></circle></svg>
					Weekly : <?php echo ucwords($definition->item_type ?: 'Tracks'); ?>
				</div>
				<h1 class="kc-page-title"><?php echo esc_html($definition->title); ?></h1>
				<p class="kc-page-subtitle" style="font-family: inherit;">افضل ١٠٠ اغنية</p>
				
				<div class="kc-page-meta">
					<span class="kc-meta-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
						Week of <?php echo $period ? date('M j, Y', strtotime($period->period_start)) : date('M j, Y'); ?>
					</span>
					<span class="kc-meta-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
						Updated Weekly
					</span>
					<span class="kc-meta-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						<?php echo count($entries); ?> entries
					</span>
				</div>
				<p style="font-size: 13px; color: var(--k-text-dim); margin-top: 24px; max-width: 600px; font-weight: 500;">The week's most popular songs across all platforms, tallied by streaming, display, and sales data.</p>
			</header>

			<!-- #1 FEATURED TRACK -->
			<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; ?>
				<div class="kc-card" style="padding: 0; overflow: hidden; height: 320px; display: flex; position: relative; margin-bottom: 60px;">
					<img src="<?php echo esc_url($top->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.15; filter: blur(60px); transform: scale(1.5);">
					<div style="position: relative; z-index: 10; display: flex; align-items: center; width: 100%; padding: 40px 60px; gap: 40px;">
						<img src="<?php echo esc_url($top->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 240px; height: 240px; border-radius: 12px; object-fit: cover; box-shadow: var(--k-shadow-md);">
						<div style="flex-grow: 1;">
							<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
								<span style="background: var(--k-accent); color: #fff; font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">#1 This Week</span>
								<span style="font-size: 12px; font-weight: 800; color: #2ecc71;">+2</span>
							</div>
							<h2 style="font-size: 48px; font-weight: 950; margin: 0; line-height: 1;"><?php echo esc_html($top->track_name); ?></h2>
							<h3 style="font-size: 24px; font-weight: 700; color: var(--k-text-muted); margin-top: 8px;"><?php echo esc_html($top->artist_names); ?></h3>
							
							<div style="display: flex; align-items: center; gap: 32px; margin-top: 32px; font-size: 11px; font-weight: 800; color: var(--k-text-dim);">
								<span>▶ 220,0M Total Streams</span>
								<span>Peak #1</span>
								<span>8 wks on chart</span>
								<span>3:42</span>
							</div>
						</div>
						<div style="width: 80px; height: 80px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- RANKINGS TABLE -->
			<section class="kc-section" style="padding-top: 0; padding-bottom: 120px;">
				<div class="kc-section-header" style="justify-content: flex-start; gap: 12px;">
					<h2 class="kc-section-title" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted);">Full Rankings</h2>
					<span style="font-size: 11px; font-weight: 600; color: var(--k-text-muted); opacity: 0.4;">Week of <?php echo date('M j, Y'); ?></span>
				</div>

				<table class="kc-rankings-table">
					<thead class="kc-table-head">
						<tr>
							<th style="width: 80px;">Rank</th>
							<th style="width: 100px;">Move</th>
							<th>Cover Title</th>
							<th style="text-align: right;">Last Wk</th>
							<th style="text-align: right;">Peak</th>
							<th style="text-align: right; width: 120px;">Wks On Chart</th>
							<th style="width: 60px;"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $e ) : ?>
							<tr class="kc-rank-row">
								<td class="kc-rank-num">#<?php echo $e->rank_position; ?></td>
								<td>
									<div class="kc-rank-move">
										<?php if ( $e->rank_position < $e->previous_rank ) : ?>
											<span class="kc-move-up">▲ <?php echo ($e->previous_rank - $e->rank_position); ?></span>
										<?php elseif ( $e->rank_position > $e->previous_rank && $e->previous_rank > 0 ) : ?>
											<span class="kc-move-down">▼ <?php echo ($e->rank_position - $e->previous_rank); ?></span>
										<?php elseif ( $e->previous_rank == 0 ) : ?>
											<span class="kc-move-new">NEW</span>
										<?php else : ?>
											<span style="opacity: 0.3;">–</span>
										<?php endif; ?>
									</div>
								</td>
								<td>
									<div style="display: flex; align-items: center; gap: 16px;">
										<img src="<?php echo esc_url($e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 48px; height: 48px; border-radius: 6px; object-fit: cover;">
										<div>
											<span style="display: block; font-size: 16px; font-weight: 800; color: var(--k-text);"><?php echo esc_html($e->track_name); ?></span>
											<span style="font-size: 12px; font-weight: 500; color: var(--k-text-muted);"><?php echo esc_html($e->artist_names); ?></span>
										</div>
									</div>
								</td>
								<td style="text-align: right; font-weight: 700; color: var(--k-text-dim);"><?php echo $e->previous_rank ?: '—'; ?></td>
								<td style="text-align: right; font-weight: 700; color: var(--k-text-dim);">#<?php echo $e->peak_rank ?: $e->rank_position; ?></td>
								<td style="text-align: right; font-weight: 700; color: var(--k-text-dim);"><?php echo $e->weeks_on_chart ?: 1; ?></td>
								<td style="text-align: right;">
									<div class="kc-chevron-toggle" style="width: 24px; height: 24px; margin-left: auto;">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
									</div>
								</td>
							</tr>
							<tr class="kc-details-row">
								<td colspan="7" style="padding: 0;">
									<div class="kc-details-inner">
										<div class="kc-details-grid">
											<div class="kc-details-item">
												<label>Release Date</label>
												<span><?php echo !empty($e->release_date) ? date('M j, Y', strtotime($e->release_date)) : '—'; ?></span>
											</div>
											<div class="kc-details-item">
												<label>Peak Position</label>
												<span>#<?php echo $e->peak_rank ?: $e->rank_position; ?></span>
											</div>
											<div class="kc-details-item">
												<label>Source / Primary Label</label>
												<span><?php echo esc_html($e->label_names ?: 'Distribution Network'); ?></span>
											</div>
											<div class="kc-details-item" style="text-align: right;">
												<a href="<?php echo home_url('/charts/track/'.$e->item_slug.'/'); ?>" class="kc-view-all" style="font-size: 12px;">Full Breakdown &rarr;</a>
											</div>
										</div>
										<div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--k-border); display: flex; gap: 40px;">
											<div class="kc-details-item">
												<label>Writer / Composers</label>
												<span><?php echo esc_html($e->composer_names ?: 'Credits Protected'); ?></span>
											</div>
											<div class="kc-details-item">
												<label>Total Market Reach</label>
												<span>Regional Index Tracking Active</span>
											</div>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>

		<?php endif; ?>

	</div>
</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
