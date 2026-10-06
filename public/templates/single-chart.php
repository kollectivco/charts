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

		$requested_period_id = isset( $_GET['period'] ) ? absint( $_GET['period'] ) : 0;
		if ( $requested_period_id ) {
			$req_params = array_values( $source_ids );
			$req_params[] = $requested_period_id;
			$period = $wpdb->get_row( $wpdb->prepare( "
				SELECT p.* FROM {$wpdb->prefix}charts_periods p
				JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
				WHERE e.source_id IN ($placeholders) AND p.id = %d
				LIMIT 1
			", ...$req_params ) );
		}
		if ( ! $period ) {
			$period = $wpdb->get_row( $wpdb->prepare( "
				SELECT p.* FROM {$wpdb->prefix}charts_periods p
				JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
				WHERE e.source_id IN ($placeholders)
				ORDER BY p.period_start DESC LIMIT 1
			", ...$source_ids ) );
		}

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
				$e->resolved_image = \Charts\Core\PublicIntegration::resolve_artwork($e, $e->item_type);

                // Healing: If slug is generic or missing, resolve from relational table
                if ( empty($e->item_slug) || $e->item_slug === 'unknown-youtube-item' ) {
                    $table = ($e->item_type === 'artist') ? 'artists' : (($e->item_type === 'video') ? 'videos' : (($e->item_type === 'album') ? 'albums' : 'tracks'));
                    $e->item_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $e->item_id));
                }
			}
			unset($e); // Critical fix: break reference to avoid last item duplication in subsequent foreach
		} else {
			$page_state = 'empty';
		}

		// 5. Ranking Integrity Guard & Rendering Validation
		if ( ! empty($entries) ) {
			$found_ranks = array_column( $entries, 'rank_position' );
			$duplicates  = array_unique( array_diff_assoc( $found_ranks, array_unique( $found_ranks ) ) );
			$expected    = range( 1, count( $entries ) );
			$missing     = array_diff( $expected, $found_ranks );
            
            // Validate final array for object reference corruption (duplicate identity)
            $obj_ids = array();
            foreach ($entries as $item) {
                if (isset($obj_ids[$item->id])) {
                    error_log("Chart Render Error: Duplicate object ID {$item->id} detected in final set.");
                    $duplicates[] = 'RefDupe:'.$item->id;
                }
                $obj_ids[$item->id] = true;
            }

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


// -------------------------------------------------------------------
// CUSTOM ELEMENTOR TEMPLATE INJECTION
// -------------------------------------------------------------------
$custom_template_id = (int) \Charts\Core\Settings::get('single.elementor_template', 0);
if ( $page_state === 'ready' && $custom_template_id > 0 && class_exists('\Elementor\Plugin') && ! $is_mobile ) {
    $elementor = \Elementor\Plugin::instance();

    // Ensure Elementor frontend is fully initialized
    if ( ! $elementor->frontend->has_elementor_in_page() ) {
        $elementor->frontend->init();
    }

    add_action('wp_enqueue_scripts', function() use ($custom_template_id) {
        if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
            $css_file = new \Elementor\Core\Files\CSS\Post( $custom_template_id );
            $css_file->enqueue();
        }
    }, 500);

    \Charts\Core\PublicIntegration::get_header();
    echo $elementor->frontend->get_builder_content_for_display( $custom_template_id, true );
    \Charts\Core\PublicIntegration::get_footer();
    exit;
}
// -------------------------------------------------------------------

if ( ! $is_mobile ) {
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
}
?>

<div class="kc-root">
	<div class="kc-container">
		
		<?php if ( $page_state === 'not_found' ) : ?>
			<section class="kc-page-hero" style="text-align: center;"><h1>Chart Not Found</h1><p>The requested chart definition does not exist.</p></section>
		<?php else : ?>

			<header class="kc-page-hero" style="padding: 40px 0 60px;">
				<h1 class="kc-page-title <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($definition->title)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($definition->title)); ?></h1>
				<?php if ( ! empty($definition->title_ar) ) : ?>
					<p class="kc-page-subtitle k-font-ar"><?php echo esc_html($definition->title_ar); ?></p>
				<?php endif; ?>
				
				<?php if ( ! empty($definition->chart_summary) ) : ?>
					<p style="font-size: 13px; color: var(--k-text-dim); margin-top: 24px; max-width: 600px; font-weight: 500; font-family: inherit;"><?php echo esc_html($definition->chart_summary); ?></p>
				<?php endif; ?>

				<?php if ( ! empty($period) && ! empty($period->period_start) ) : ?>
					<div class="kc-period-badge" style="display:inline-flex; align-items:center; gap:6px; margin-top:16px; padding:6px 14px; background:rgba(0,0,0,0.04); border-radius:999px; font-size:12px; font-weight:600; color:var(--k-text-dim);">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
						<span><?php echo esc_html( date_i18n( 'd F Y', strtotime( $period->period_start ) ) ); ?></span>
					</div>
				<?php endif; ?>
			</header>

			<?php
			// Detect Chart Item Mode
			$is_artist_chart = ( 
				($definition->item_type ?? '') === 'artist' || 
				strpos(strtolower($definition->chart_type ?? ''), 'artist') !== false || 
				strpos(strtolower($definition_slug), 'artist') !== false 
			);
			$is_album_chart = ( 
				($definition->item_type ?? '') === 'album' || 
				strpos(strtolower($definition->chart_type ?? ''), 'album') !== false || 
				strpos(strtolower($definition_slug), 'album') !== false 
			);
			?>

			<div class="kc-slider-container" style="max-width: 1400px; margin: 0 auto; padding: 0; margin-bottom: 60px;">
				<!-- #1 FEATURED TRACK / ALBUM / ARTIST -->
				<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; 
					$chart_color = $definition->accent_color ?: 'var(--k-accent)';
				?>
					<div class="kc-card kc-featured-hero" style="padding: 0; overflow: hidden; position: relative;">
					<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.15; filter: blur(60px); transform: scale(1.5);">
					<?php 
						$display_mode = $definition->name_display_mode ?? 'original';
						$resolved = \Charts\Core\Transliteration::resolve_entry_display($top, $display_mode);
						$top_track = $resolved['track'];
						$top_artist = $resolved['artist'];
					?>
					<div class="kc-featured-inner">
						<div class="kc-featured-media">
							<div class="kc-featured-rank-num" style="color: <?php echo esc_attr($chart_color); ?>;">١</div>
							<img class="kc-featured-img" src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="<?php echo esc_attr($top_artist ?: $top_track); ?>">
						</div>
						<div class="kc-featured-info">
							<div class="kc-featured-badge-row">
								<span class="kc-featured-badge" style="background: <?php echo esc_attr($chart_color); ?>;">#١ الأسبوع ده</span>
								<?php if ( $top->movement_direction === 'up' && ! empty($top->movement_value) ) : ?>
									<span class="kc-featured-movement">+<?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($top->movement_value)); ?></span>
								<?php elseif ( $top->movement_direction === 'down' && ! empty($top->movement_value) ) : ?>
									<span class="kc-featured-movement" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">-<?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($top->movement_value)); ?></span>
								<?php elseif ( $top->movement_direction === 're-entry' || ! empty($top->is_reentry) ) : ?>
									<span class="kc-featured-movement" style="background: rgba(217, 119, 6, 0.15); color: #d97706;"><?php echo \Charts\Core\Translation::get('RE-ENTRY'); ?></span>
								<?php elseif ( $top->movement_direction === 'new' || ! empty($top->is_new_entry) ) : ?>
									<span class="kc-featured-movement" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;"><?php echo \Charts\Core\Translation::get('NEW'); ?></span>
								<?php endif; ?>
							</div>
							<?php 
                                // Rule: In artist mode, main title is Artist Name. In song/album mode, it's Track/Album Name.
                                $display_title = $is_artist_chart ? ($top_artist ?: $top_track) : $top_track;
                                
                                // Auto-healing for stale "Unknown" data
                                if ( $display_title === 'Unknown YouTube Item' && ! empty($top_artist) ) {
                                    $display_title = $top_artist;
                                }
                            ?>
                            <h2 class="kc-featured-title <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($display_title)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($display_title)); ?></h2>
							
                            <?php 
                            // Rule: Disable subtitle for Artist Charts to prevent duplication
                            if ( ! $is_artist_chart && ! empty($top_artist) && strtolower($display_title) !== strtolower($top_artist) ) : ?>
								<h3 class="kc-featured-subtitle <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($top_artist)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($top_artist)); ?></h3>
							<?php endif; ?>
							
							<div class="kc-featured-stats">
								<span><?php echo \Charts\Core\Translation::get('Peak #'); ?><?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($top->peak_rank ?: 1)); ?></span>
								<span><?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($top->weeks_on_chart ?: 1)); ?> <?php echo \Charts\Core\Translation::get('wks on chart'); ?></span>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- RANKINGS TABLE -->
			<section class="kc-section kc-rankings-section">

				<table class="kc-rankings-table">
					<thead class="kc-table-head">
						<tr>
							<th class="kc-col-rank"><?php echo \Charts\Core\Translation::get('Rank'); ?></th>
							<th class="kc-col-movement"><?php echo \Charts\Core\Translation::get('Movement'); ?></th>
							<th class="kc-col-main"><?php echo $is_artist_chart ? \Charts\Core\Translation::get('Artist') : ( $is_album_chart ? 'الألبوم' : \Charts\Core\Translation::get('Track') ); ?></th>
							<th class="kc-col-prev-rank"><?php echo \Charts\Core\Translation::get('Previous Rank'); ?></th>
							<th class="kc-col-peak-rank">أعلى مركز</th>
							<th class="kc-col-weeks"><?php echo \Charts\Core\Translation::get('wks on chart'); ?></th>
							<th class="kc-col-action"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $e ) : ?>
							<tr class="kc-rank-row">
								<td class="kc-rank-num">#<?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position); ?></td>
								<td class="kc-col-movement">
									<div class="kc-rank-move">
										<?php 
										$dir = $e->movement_direction ?: '';
										$val = intval( $e->movement_value ?: 0 );
										$prev = ( isset($e->previous_rank) && $e->previous_rank !== null && $e->previous_rank !== '' ) ? intval($e->previous_rank) : 0;
										if ( empty($val) && $prev > 0 ) {
											$val = abs($e->rank_position - $prev);
										}
										if ( empty($dir) ) {
											if ( $prev > 0 ) {
												if ( $e->rank_position < $prev ) $dir = 'up';
												elseif ( $e->rank_position > $prev ) $dir = 'down';
												else $dir = 'same';
											} else {
												$dir = ! empty($e->is_reentry) ? 're-entry' : 'new';
											}
										}
										?>
										<?php if ( $dir === 'up' && $val > 0 ) : ?>
											<span class="kc-move-up">▲ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($val); ?></span>
										<?php elseif ( $dir === 'down' && $val > 0 ) : ?>
											<span class="kc-move-down">▼ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($val); ?></span>
										<?php elseif ( $dir === 're-entry' || ! empty($e->is_reentry) ) : ?>
											<span class="kc-move-reentry" style="color:#d97706; font-size:10px; font-weight:800;"><?php echo \Charts\Core\Translation::get('RE-ENTRY'); ?></span>
										<?php elseif ( $dir === 'new' || ! empty($e->is_new_entry) || $prev === 0 ) : ?>
											<span class="kc-move-new"><?php echo \Charts\Core\Translation::get('NEW'); ?></span>
										<?php else : ?>
											<span style="opacity: 0.3;">–</span>
										<?php endif; ?>
									</div>
								</td>
								<td class="kc-col-main">
									<div class="kc-entry-info-cell">
										<img class="kc-rank-thumb" src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="<?php echo esc_attr($e->item_title); ?>">
										<div class="kc-entry-meta">
											<?php 
												$display_mode = $definition->name_display_mode ?? 'original';
												$resolved = \Charts\Core\Transliteration::resolve_entry_display($e, $display_mode);
												$row_track = $resolved['track'];
												$row_artist = $resolved['artist'];

                                                // Rule: In artist mode, main title is Artist Name. In song mode, it's Track Name.
                                                $row_title = $is_artist_chart ? ($row_artist ?: $row_track) : $row_track;

                                                // Auto-healing for stale "Unknown" data
                                                if ( $row_title === 'Unknown YouTube Item' && ! empty($row_artist) ) {
                                                    $row_title = $row_artist;
                                                }
                                             ?>
                                             <span class="kc-track-name <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($row_title)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($row_title)); ?></span>
  											
                                            <?php 
                                            // Rule: Disable subtitle for Artist Charts to prevent duplication
                                            if ( ! $is_artist_chart && ! empty($row_artist) && strtolower($row_title) !== strtolower($row_artist) ) : ?>
  												<span class="kc-artist-name <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($row_artist)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($row_artist)); ?></span>
  											<?php endif; ?>
  										</div>
 									</div>
 								</td>
								<td class="kc-col-prev-rank kc-col-stat"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->previous_rank ?: '—'); ?></td>
								<td class="kc-col-peak-rank kc-col-stat">#<?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->peak_rank ?: $e->rank_position); ?></td>
								<td class="kc-col-weeks kc-col-stat"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->weeks_on_chart ?: 1); ?></td>
								<td class="kc-col-action">
									<div class="kc-chevron-toggle">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
									</div>
								</td>
							</tr>
							<tr class="kc-details-row">
								<td colspan="7" style="padding: 0;">
									<div class="kc-details-inner">
										<div class="kc-details-grid">
											<div class="kc-details-item">
												<label><?php echo \Charts\Core\Translation::get('Current Rank'); ?></label>
												<span>#<?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position); ?></span>
											</div>
											<?php if ( ! empty($e->peak_rank) ) : ?>
											<div class="kc-details-item">
												<label>أعلى مركز</label>
												<span>#<?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($e->peak_rank)); ?></span>
											</div>
											<?php endif; ?>
											<?php if ( ! empty($e->previous_rank) ) : ?>
											<div class="kc-details-item">
												<label><?php echo \Charts\Core\Translation::get('Previous Rank'); ?></label>
												<span>#<?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($e->previous_rank)); ?></span>
											</div>
											<?php endif; ?>
											<div class="kc-details-item">
												<label><?php echo \Charts\Core\Translation::get('wks on chart'); ?></label>
												<span><?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($e->weeks_on_chart ?: 1)); ?></span>
											</div>
											<div class="kc-details-item kc-details-cta">
												<?php $resolved_type = ! empty( $e->item_type ) ? $e->item_type : ( $is_album_chart ? 'album' : ( $is_artist_chart ? 'artist' : 'track' ) ); ?>
												<a href="<?php echo home_url('/charts/' . $resolved_type . '/' . $e->item_slug . '/'); ?>" class="kc-view-all"><?php echo \Charts\Core\Translation::get('Details'); ?></a>
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
