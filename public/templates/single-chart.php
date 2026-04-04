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
				SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), t.cover_image, v.thumbnail, a.image) AS resolved_image 
				FROM {$wpdb->prefix}charts_entries e
				LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
				LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
				LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
				WHERE e.source_id IN ($placeholders) AND e.period_id = %d
				ORDER BY e.rank_position ASC
			", ...$query_params ) );
		} else {
			$page_state = 'empty';
		}
	}
}

\Charts\Core\PublicIntegration::get_header();
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
				<h1 class="kc-page-title"><?php echo esc_html($definition->title); ?></h1>
				<?php if ( ! empty($definition->title_ar) ) : ?>
					<p class="kc-page-subtitle" style="font-family: inherit;"><?php echo esc_html($definition->title_ar); ?></p>
				<?php endif; ?>
				
				<?php if ( ! empty($definition->chart_summary) ) : ?>
					<p style="font-size: 13px; color: var(--k-text-dim); margin-top: 24px; max-width: 600px; font-weight: 500; font-family: inherit;"><?php echo esc_html($definition->chart_summary); ?></p>
				<?php endif; ?>
			</header>

			<!-- #1 FEATURED TRACK -->
			<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; ?>
				<div class="kc-card" style="padding: 0; overflow: hidden; height: 320px; display: flex; position: relative; margin-bottom: 60px;">
					<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.15; filter: blur(60px); transform: scale(1.5);">
					<div style="position: relative; z-index: 10; display: flex; align-items: center; width: 100%; padding: 40px 60px; gap: 40px;">
						<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 240px; height: 240px; border-radius: 12px; object-fit: cover; box-shadow: var(--k-shadow-md);">
						<div style="flex-grow: 1;">
							<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
								<span style="background: var(--k-accent); color: #fff; font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">#1 This Week</span>
								<?php if ( $top->movement_direction === 'up' && ! empty($top->movement_value) ) : ?>
									<span style="font-size: 12px; font-weight: 800; color: #2ecc71;">+<?php echo intval($top->movement_value); ?></span>
								<?php endif; ?>
							</div>
							<h2 style="font-size: 48px; font-weight: 950; margin: 0; line-height: 1;"><?php echo esc_html($top->track_name); ?></h2>
							<h3 style="font-size: 24px; font-weight: 700; color: var(--k-text-muted); margin-top: 8px;"><?php echo esc_html($top->artist_names); ?></h3>
							
							<div style="display: flex; align-items: center; gap: 32px; margin-top: 32px; font-size: 11px; font-weight: 800; color: var(--k-text-dim);">
								<?php if ( ! empty($top->views_count) ) : ?>
									<span>▶ <?php echo number_format($top->views_count / 1000000, 1); ?>M Views</span>
								<?php elseif ( ! empty($top->streams_count) ) : ?>
									<span>▶ <?php echo number_format($top->streams_count / 1000000, 1); ?>M Streams</span>
								<?php endif; ?>
								<span>Peak #<?php echo intval($top->peak_rank ?: 1); ?></span>
								<span><?php echo intval($top->weeks_on_chart ?: 1); ?> wks on chart</span>
							</div>
						</div>
						<div style="width: 80px; height: 80px; background: var(--k-surface-alt); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- RANKINGS TABLE -->
			<section class="kc-section" style="padding-top: 0; padding-bottom: 120px;">
				<div class="kc-section-header" style="justify-content: flex-start; gap: 12px;">
					<h2 class="kc-section-title" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-muted);">Full Rankings</h2>
					<?php if ( $period ) : ?>
						<span style="font-size: 11px; font-weight: 600; color: var(--k-text-muted); opacity: 0.4;">Week of <?php echo date('M j, Y', strtotime($period->period_start)); ?></span>
					<?php endif; ?>
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
										<img src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 48px; height: 48px; border-radius: 6px; object-fit: cover;">
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
										<div class="kc-details-grid" style="grid-template-columns: repeat(4, 1fr); gap: 24px;">
											<div class="kc-details-item">
												<label>Current Rank</label>
												<span>#<?php echo $e->rank_position; ?></span>
											</div>
											<?php if ( ! empty($e->peak_rank) ) : ?>
											<div class="kc-details-item">
												<label>Peak Position</label>
												<span>#<?php echo intval($e->peak_rank); ?></span>
											</div>
											<?php endif; ?>
											<?php if ( ! empty($e->previous_rank) ) : ?>
											<div class="kc-details-item">
												<label>Previous Week</label>
												<span>#<?php echo intval($e->previous_rank); ?></span>
											</div>
											<?php endif; ?>
											<div class="kc-details-item">
												<label>Weeks on Chart</label>
												<span><?php echo intval($e->weeks_on_chart ?: 1); ?></span>
											</div>
											<?php if ( ! empty($e->views_count) ) : ?>
											<div class="kc-details-item">
												<label>Total Views</label>
												<span><?php echo number_format($e->views_count); ?></span>
											</div>
											<?php elseif ( ! empty($e->streams_count) ) : ?>
											<div class="kc-details-item">
												<label>Total Streams</label>
												<span><?php echo number_format($e->streams_count); ?></span>
											</div>
											<?php endif; ?>
											<?php if ( ! empty($e->release_date) ) : ?>
											<div class="kc-details-item">
												<label>Release Date</label>
												<span><?php echo date('M j, Y', strtotime($e->release_date)); ?></span>
											</div>
											<?php endif; ?>
											<div class="kc-details-item" style="text-align: right; grid-column: span <?php echo (!empty($e->release_date)) ? 2 : 3; ?>;">
												<a href="<?php echo home_url('/charts/' . ( $e->item_type ?: 'track' ) . '/' . $e->item_slug . '/'); ?>" class="kc-view-all" style="font-size: 12px; margin-top: 12px;">Full Breakdown &rarr;</a>
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
<?php \Charts\Core\PublicIntegration::get_footer(); ?>
