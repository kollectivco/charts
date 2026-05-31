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

if ( ! $is_mobile ) {
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
}
?>

<div class="kc-root kc-theme-dark kc-layout-rtl" style="background: #121212; min-height: 100vh; color: #ffffff; direction: rtl;">
	<div class="kc-container" style="max-width: 100%; padding: 0;">
		
		<?php if ( $page_state === 'not_found' ) : ?>
			<section class="kc-page-hero" style="text-align: center; padding: 100px;"><h1>Chart Not Found</h1><p>The requested chart definition does not exist.</p></section>
		<?php else : ?>

			<?php
			// Detect Artist-Chart Mode
			$is_artist_chart = ( 
				($definition->item_type ?? '') === 'artist' || 
				strpos(strtolower($definition->chart_type ?? ''), 'artist') !== false || 
				strpos(strtolower($definition_slug), 'artist') !== false 
			);
			?>

			<div class="kc-dark-hero" style="position: relative; padding: 80px 120px; background: linear-gradient(to bottom, #2a2a2a, #121212); display: flex; align-items: center; justify-content: space-between; overflow: hidden; border-bottom: 1px solid rgba(255,255,255,0.05);">
				<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; ?>
					<div class="kc-hero-bg-glow" style="position: absolute; left: 0; top: 0; width: 600px; height: 600px; background: url('<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>') center/cover; opacity: 0.15; filter: blur(100px); border-radius: 50%; pointer-events: none;"></div>
				<?php endif; ?>
				
				<div class="kc-hero-content" style="position: relative; z-index: 10; max-width: 600px;">
					<span style="font-size: 14px; font-weight: 500; color: #a0a0a0; text-transform: uppercase; letter-spacing: 1px; font-family: sans-serif;">Egypt</span>
					<h1 class="kc-page-title <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($definition->title)); ?>" style="font-size: 64px; font-weight: 950; margin: 10px 0 20px; line-height: 1.1; color: #fff; text-shadow: 0 4px 20px rgba(0,0,0,0.5);"><?php echo esc_html(\Charts\Core\Translation::get($definition->title)); ?></h1>
					
					<?php if ( ! empty($definition->title_ar) && \Charts\Core\Translation::get($definition->title) !== $definition->title_ar ) : ?>
						<p class="kc-page-subtitle k-font-ar" style="font-size: 24px; color: #a0a0a0; margin-bottom: 20px;"><?php echo esc_html($definition->title_ar); ?></p>
					<?php endif; ?>
					
					<p style="font-size: 16px; color: #a0a0a0; margin-bottom: 40px; font-weight: 500;"><?php echo esc_html($definition->chart_summary ?: "Ranking of this week's most popular artists."); ?></p>
					
					<div class="kc-hero-actions" style="display: flex; gap: 16px;">
						<div style="border: 1px solid rgba(255,255,255,0.2); border-radius: 30px; padding: 8px 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; cursor: pointer; color: #e0e0e0; transition: background 0.2s; hover:background: rgba(255,255,255,0.1);">
							تاريخ الأسبوع <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</div>
						<div style="border: 1px solid rgba(255,255,255,0.2); border-radius: 30px; padding: 8px 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; cursor: pointer; color: #e0e0e0; transition: background 0.2s;">
							الأكثر شهرة <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</div>
					</div>
				</div>
				
				<?php if ( ! empty( $entries[0] ) ) : $top = $entries[0]; ?>
					<div class="kc-hero-image" style="position: relative; z-index: 10;">
						<div style="width: 320px; height: 320px; border-radius: 50%; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5); position: relative; background: #222;">
							<img src="<?php echo esc_url($top->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
							<div style="position: absolute; inset: 0; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1); border-radius: 50%;"></div>
						</div>
						<div style="position: absolute; bottom: 10px; left: 10px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- RANKINGS TABLE -->
			<section class="kc-section" style="padding: 40px 120px 120px; background: #0a0a0a;">

				<table class="kc-rankings-table kc-dark-table" style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
					<thead class="kc-table-head" style="font-size: 13px; color: #888; text-transform: uppercase;">
						<tr>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: right; width: 80px; border-bottom: 1px solid rgba(255,255,255,0.05);"><?php echo \Charts\Core\Translation::get('Rank'); ?></th>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05);"><?php echo \Charts\Core\Translation::get('Artist'); ?></th>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: right; width: 140px; border-bottom: 1px solid rgba(255,255,255,0.05);"><?php echo \Charts\Core\Translation::get('Previous Rank'); ?></th>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: right; width: 160px; border-bottom: 1px solid rgba(255,255,255,0.05);"><?php echo \Charts\Core\Translation::get('wks on chart'); ?></th>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: right; width: 160px; border-bottom: 1px solid rgba(255,255,255,0.05);">أعلى مركز</th>
							<th style="padding: 0 24px 16px; font-weight: 500; text-align: center; width: 60px; border-bottom: 1px solid rgba(255,255,255,0.05);"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $e ) : ?>
							<tr class="kc-rank-row" style="background: #1a1a1a; transition: background 0.2s; border-radius: 12px; cursor: pointer;" onmouseover="this.style.background='#2a2a2a';" onmouseout="this.style.background='#1a1a1a';">
								<td class="kc-rank-num" style="padding: 16px 24px; font-size: 28px; font-weight: 900; color: #fff; width: 80px; border-top-right-radius: 12px; border-bottom-right-radius: 12px; position: relative;">
									<?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position); ?>
									<?php if ( $e->rank_position < $e->previous_rank ) : ?>
										<span style="display: block; font-size: 10px; color: #2ecc71; margin-top: 4px;">▲ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->previous_rank - $e->rank_position); ?></span>
									<?php elseif ( $e->rank_position > $e->previous_rank && $e->previous_rank > 0 ) : ?>
										<span style="display: block; font-size: 10px; color: #e74c3c; margin-top: 4px;">▼ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position - $e->previous_rank); ?></span>
									<?php elseif ( $e->previous_rank == 0 ) : ?>
										<span style="display: block; font-size: 10px; color: #f1c40f; margin-top: 4px;"><?php echo \Charts\Core\Translation::get('NEW'); ?></span>
									<?php else : ?>
										<span style="display: block; font-size: 10px; color: #555; margin-top: 4px;">–</span>
									<?php endif; ?>
								</td>
								<td style="padding: 16px 24px;">
										<div style="display: flex; align-items: center; gap: 20px;">
										<img src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover;">
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
                                             <span style="display: block; font-size: 18px; font-weight: 800; color: #fff;" class="<?php echo \Charts\Core\Typography::get_font_class($row_title); ?>"><?php echo esc_html($row_title); ?></span>
  											
                                            <?php 
                                            // Rule: Disable subtitle for Artist Charts to prevent duplication
                                            if ( ! $is_artist_chart && ! empty($row_artist) && strtolower($row_title) !== strtolower($row_artist) ) : ?>
  												<span style="font-size: 13px; font-weight: 500; color: #a0a0a0; display: block; margin-top: 4px;" class="<?php echo \Charts\Core\Typography::get_font_class($row_artist); ?>"><?php echo esc_html($row_artist); ?></span>
  											<?php endif; ?>
  										</div>
 									</div>
 								</td>
								<td style="padding: 16px 24px; text-align: right; font-weight: 700; color: #fff; font-size: 15px;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->previous_rank ?: '—'); ?></td>
								<td style="padding: 16px 24px; text-align: right; font-weight: 700; color: #fff; font-size: 15px;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->weeks_on_chart ?: 1); ?></td>
								<td style="padding: 16px 24px; text-align: right; font-weight: 700; color: #fff; font-size: 15px;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($e->peak_rank ?: $e->rank_position); ?></td>
								<td style="padding: 16px 24px; text-align: center; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
									<div class="kc-share-toggle" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; cursor: pointer;">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
									</div>
								</td>
							</tr>
							<tr class="kc-details-row" style="display: none;">
								<td colspan="6" style="padding: 0;">
									<!-- Keep structure hidden for now, or you can drop it. Keeping it invisible for backwards compat with JS if needed -->
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
