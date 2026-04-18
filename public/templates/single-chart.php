<?php
/**
 * Kontentainment Charts — Single Chart (Light Mode)
 * Matches Reference #2
 */

global $wpdb;

// 1. DATA LOOKUP
$definition_slug = get_query_var( 'charts_definition_slug' );
$manager = new \Charts\Admin\SourceManager();
$definition = $manager->get_definition_by_slug( $definition_slug );

// 2. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-chart-single.php'; exit;
    exit;
}

$page_state = 'not_found';
$sources    = array();
$entries    = array();
$period     = null;

if ( $definition ) {
	$page_state = 'ready';
	
	$platform_filter = (!empty($definition->platform) && $definition->platform !== 'all') ? $wpdb->prepare(" AND platform = %s", $definition->platform) : "";
	
	// 1. Strict Lookup: Require Specific Binding (cid-ID)
	$sources = $wpdb->get_results( $wpdb->prepare( "
		SELECT id FROM {$wpdb->prefix}charts_sources 
		WHERE chart_type = %s AND is_active = 1 $platform_filter
	", "cid-{$definition->id}" ) );

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
			
			$max_depth = 500; // Pipeline depth removed, using safe high limit
			$query_params[] = $max_depth;
			
			$entries = $wpdb->get_results( $wpdb->prepare( "
				SELECT e.* 
				FROM {$wpdb->prefix}charts_entries e
				INNER JOIN (
					SELECT MAX(id) as max_id, rank_position
					FROM {$wpdb->prefix}charts_entries
					WHERE source_id IN ($placeholders) AND period_id = %d
					GROUP BY rank_position
				) dedup ON dedup.max_id = e.id
				ORDER BY e.rank_position ASC
				LIMIT %d
			", ...$query_params ) );
			
			// Resolve images and slugs from custom tables
			foreach($entries as &$e) {
				if ( ! empty($e->cover_image) ) {
					$e->resolved_image = $e->cover_image;
				} else {
					$table = ($e->item_type === 'artist') ? 'artists' : (($e->item_type === 'video') ? 'videos' : 'tracks');
					$col = ($e->item_type === 'artist') ? 'image' : (($e->item_type === 'video') ? 'thumbnail' : 'cover_image');
					$e->resolved_image = $wpdb->get_var($wpdb->prepare("SELECT $col FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $e->item_id));
				}

                // Healing: If slug is generic or missing, resolve from relational table
                if ( empty($e->item_slug) || $e->item_slug === 'unknown-youtube-item' ) {
                    $table = ($e->item_type === 'artist') ? 'artists' : (($e->item_type === 'video') ? 'videos' : 'tracks');
                    $e->item_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $e->item_id));
                }
			}
		} else {
			$page_state = 'empty';
		}

		// 5. Ranking Integrity Guard
		if ( ! empty($entries) ) {
			$found_ranks = array_column( $entries, 'rank_position' );
			$duplicates  = array_unique( array_diff_assoc( $found_ranks, array_unique( $found_ranks ) ) );
			$expected    = range( 1, count( $entries ) );
			$missing     = array_diff( $expected, $found_ranks );

			if ( ! empty( $duplicates ) || ! empty( $missing ) ) {
				error_log( sprintf( 
					"Chart Integrity Alert [%s]: Found %d rows. Duplicates: %s | Missing: %s", 
					$definition->slug, 
					count( $entries ),
					!empty($duplicates) ? implode(',', $duplicates) : 'None',
					!empty($missing) ? implode(',', $missing) : 'None'
				) );
			}
		}
	}
}

if ( ! $is_mobile ) {
	if ( ! $is_mobile ) { PublicIntegration::get_header(); }
}
?>

<div class="kc-root">
	<div class="kc-container">
		
		<?php if ( $page_state === 'not_found' ) : ?>
			<section class="kc-page-hero" style="text-align: center;"><h1>Chart Not Found</h1><p>The requested chart definition does not exist.</p></section>
		<?php else : ?>

			<header class="kc-page-hero" style="padding: 40px 0 60px;">
				<h1 class="kc-page-title <?php echo \Charts\Core\Typography::get_font_class($definition->title); ?>"><?php echo esc_html($definition->title); ?></h1>
				<?php if ( ! empty($definition->title_ar) ) : ?>
					<p class="kc-page-subtitle k-font-ar"><?php echo esc_html($definition->title_ar); ?></p>
				<?php endif; ?>
				
				<?php if ( ! empty($definition->chart_summary) ) : ?>
					<p style="font-size: 13px; color: var(--k-text-dim); margin-top: 24px; max-width: 600px; font-weight: 500; font-family: inherit;"><?php echo esc_html($definition->chart_summary); ?></p>
				<?php endif; ?>
			</header>

			<?php
			// Detect Artist-Chart Mode
			$is_artist_chart = ( 
				($definition->item_type ?? '') === 'artist' || 
				strpos(strtolower($definition->chart_type ?? ''), 'artist') !== false || 
				strpos(strtolower($definition_slug), 'artist') !== false 
			);
			?>

			<div class="kc-slider-container" style="max-width: 1400px; margin: 0 auto; padding: 0 40px; margin-bottom: 100px;">
				<!-- #1 FEATURED TRACK -->
				<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; 
					$chart_color = $definition->accent_color ?: 'var(--k-accent)';
				?>
					<div class="kc-card" style="padding: 0; overflow: hidden; height: 320px; display: flex; position: relative;">
					<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.15; filter: blur(60px); transform: scale(1.5);">
					<?php 
						$franco_mode = $definition->franco_mode ?? 'original';
						$resolved = \Charts\Core\Transliteration::resolve_entry_display($top, $franco_mode);
						$top_track = $resolved['track'];
						$top_artist = $resolved['artist'];
					?>
					<div style="position: relative; z-index: 10; display: flex; align-items: center; width: 100%; padding: 40px 60px; gap: 40px;">
						<div style="position: relative; display: flex; align-items: center; gap: 10px;">
							<div style="font-size: 140px; font-weight: 950; color: <?php echo esc_attr($chart_color); ?>; line-height: 1; opacity: 1; text-shadow: 0 10px 40px rgba(0,0,0,0.1); letter-spacing: -0.05em; margin-bottom: -10px; margin-right: 10px;">1</div>
							<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 240px; height: 240px; border-radius: 12px; object-fit: cover; box-shadow: var(--k-shadow-md);">
						</div>
						<div style="flex-grow: 1;">
							<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
								<span style="background: <?php echo esc_attr($chart_color); ?>; color: #fff; font-size: 11px; font-weight: 900; padding: 6px 14px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em;">#1 This Week</span>
								<?php if ( $top->movement_direction === 'up' && ! empty($top->movement_value) ) : ?>
									<span style="font-size: 14px; font-weight: 800; color: #2ecc71;">+<?php echo intval($top->movement_value); ?></span>
								<?php endif; ?>
							</div>
							<?php 
                                // Rule: In artist mode, main title is Artist Name. In song mode, it's Track Name.
                                $display_title = $is_artist_chart ? ($top_artist ?: $top_track) : $top_track;
                                
                                // Auto-healing for stale "Unknown" data
                                if ( $display_title === 'Unknown YouTube Item' && ! empty($top_artist) ) {
                                    $display_title = $top_artist;
                                }
                            ?>
                            <h2 style="font-size: 54px; font-weight: 950; margin: 0; line-height: 1.1;" class="<?php echo \Charts\Core\Typography::get_font_class($display_title); ?>"><?php echo esc_html($display_title); ?></h2>
							
                            <?php 
                            // Rule: Disable subtitle for Artist Charts to prevent duplication
                            if ( ! $is_artist_chart && ! empty($top_artist) && strtolower($display_title) !== strtolower($top_artist) ) : ?>
								<h3 style="font-size: 28px; font-weight: 700; color: var(--k-text-muted); margin-top: 12px;" class="<?php echo \Charts\Core\Typography::get_font_class($top_artist); ?>"><?php echo esc_html($top_artist); ?></h3>
							<?php endif; ?>
							
							<div style="display: flex; align-items: center; gap: 40px; margin-top: 40px; font-size: 14px; font-weight: 800; color: var(--k-text-dim);">
								<span>Peak #<?php echo intval($top->peak_rank ?: 1); ?></span>
								<span><?php echo intval($top->weeks_on_chart ?: 1); ?> wks on chart</span>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- RANKINGS TABLE -->
			<section class="kc-section" style="padding-top: 40px; padding-bottom: 120px;">

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
											<?php 
												$franco_mode = $definition->franco_mode ?? 'original';
												$resolved = \Charts\Core\Transliteration::resolve_entry_display($e, $franco_mode);
												$row_track = $resolved['track'];
												$row_artist = $resolved['artist'];

                                                // Rule: In artist mode, main title is Artist Name. In song mode, it's Track Name.
                                                $row_title = $is_artist_chart ? ($row_artist ?: $row_track) : $row_track;

                                                // Auto-healing for stale "Unknown" data
                                                if ( $row_title === 'Unknown YouTube Item' && ! empty($row_artist) ) {
                                                    $row_title = $row_artist;
                                                }
                                             ?>
                                             <span style="display: block; font-size: 16px; font-weight: 800; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($row_title); ?>"><?php echo esc_html($row_title); ?></span>
  											
                                            <?php 
                                            // Rule: Disable subtitle for Artist Charts to prevent duplication
                                            if ( ! $is_artist_chart && ! empty($row_artist) && strtolower($row_title) !== strtolower($row_artist) ) : ?>
  												<span style="font-size: 12px; font-weight: 500; color: var(--k-text-muted);" class="<?php echo \Charts\Core\Typography::get_font_class($row_artist); ?>"><?php echo esc_html($row_artist); ?></span>
  											<?php endif; ?>
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
											<div class="kc-details-item" style="text-align: right; grid-column: span <?php echo (!empty($e->release_date)) ? 1 : 2; ?>;">
												<?php $label = apply_filters('kcharts_more_details_label', \Charts\Core\Settings::get('label_breakdown', 'More Details') . ' &rarr;'); ?>
												<a href="<?php echo home_url('/charts/' . ( $e->item_type ?: 'track' ) . '/' . $e->item_slug . '/'); ?>" class="kc-view-all" style="font-size: 12px; margin-top: 12px;"><?php echo $label; ?></a>
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

<?php if ( ! $is_mobile ) : ?>
<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); } ?>
<?php endif; ?>
