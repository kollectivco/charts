<?php
/**
 * Kontentainment Charts — Intelligence Explorer (Single Track/Video)
 * Matches Reference #3
 */

global $wpdb;

$type = get_query_var( 'charts_item_type' ) ?: 'track';
$slug = get_query_var( 'charts_item_slug' );

$item = \Charts\Core\EntityManager::get_entity_by_slug( $type, $slug );

// 2. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-item-single.php'; exit;
    return;
}

if ( ! $item ) {
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
	echo '<div class="kc-root"><h1>Item Not Found</h1></div>';
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); }
	return;
}

// Map SQL to object for template compatibility
if ( $type === 'video' ) {
	$item->cover_image = $item->thumbnail;
}

// Fetch Appearances
$entries_table = $wpdb->prefix . 'charts_entries';
$sources_table = $wpdb->prefix . 'charts_sources';
$periods_table = $wpdb->prefix . 'charts_periods';

$title_escaped = '%' . $wpdb->esc_like( $item->title ) . '%';
$appearances = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, s.chart_type, s.country_code, s.source_name, p.period_start
	FROM $entries_table e
	INNER JOIN (
		SELECT MAX(e2.id) as max_id 
		FROM $entries_table e2
		WHERE (e2.item_id = %d AND e2.item_type = %s)
		   OR (e2.track_name LIKE %s AND e2.item_type = %s)
		GROUP BY e2.source_id
	) latest ON latest.max_id = e.id
	JOIN $sources_table s ON s.id = e.source_id
	JOIN $periods_table p ON p.id = e.period_id
	ORDER BY p.period_start DESC
", $item->id, $type, $title_escaped, $type ) );

$valid_appearances = array();
foreach($appearances as $app) {
	if ( strpos($app->chart_type, 'cid-') === 0 ) {
		$def_id = (int) str_replace('cid-', '', $app->chart_type);
		$def = $wpdb->get_row($wpdb->prepare("SELECT title, accent_color FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $def_id));
	} else {
		$def = $wpdb->get_row($wpdb->prepare("SELECT title, accent_color FROM {$wpdb->prefix}charts_definitions WHERE chart_type = %s AND country_code = %s", $app->chart_type, $app->country_code));
	}
	
	if ($def) {
		$app->definition_title = $def->title;
		$app->accent_color = $def->accent_color;
		$valid_appearances[] = $app;
	}
}
$appearances = $valid_appearances;

// Fetch Artist info
$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item->primary_artist_id ) );

// Fetch More by Artist
$more_items = $wpdb->get_results( $wpdb->prepare( "
	SELECT * FROM $table 
	WHERE primary_artist_id = %d AND id != %d 
	LIMIT 2
", $item->primary_artist_id, $item->id ) );

foreach ( $more_items as $mi ) {
	if ( $type === 'video' ) {
		$mi->cover_image = $mi->thumbnail;
	}
}

if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
?>

<div class="kc-root" style="background: var(--k-bg); color: var(--k-text);">
	<div class="kc-container">
		

		<!-- TRACK HERO CARD -->
		<section class="kc-card" style="padding: 0; overflow: hidden; position: relative; margin: 40px 0 60px;">
			<img src="<?php echo esc_url($item->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.1; filter: blur(80px); transform: scale(1.5);">
			<div style="position: relative; z-index: 10; display: flex; align-items: center; padding: 60px; gap: 60px;">
				<div style="position: relative; width: 280px; height: 280px;">
					<img src="<?php echo esc_url($item->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px; box-shadow: var(--k-shadow-md);">
					<div style="position: absolute; inset: 0; background: rgba(0,0,0,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
						<svg width="60" height="60" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
					</div>
				</div>
				<div style="flex-grow: 1;">
					<div style="display: flex; gap: 8px; margin-bottom: 16px;">
						<span style="background: var(--k-accent); color: #fff; font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;"><?php echo strtoupper($type); ?></span>
					</div>
					<?php 
						$resolved = \Charts\Core\PublicIntegration::resolve_display_name($item); 
					?>
					<h1 style="font-size: 72px; font-weight: 950; margin: 0; line-height: 1; letter-spacing: -0.04em;" class="<?php echo \Charts\Core\Typography::get_font_class($resolved['title']); ?>"><?php echo esc_html($resolved['title']); ?></h1>
					
						<div style="display: flex; align-items: center; gap: 20px; margin-top: 28px; flex-wrap: wrap;">
							<?php 
							// Link all artists using legacy junction tables
							$j_table = ( $type === 'track' ) ? "{$wpdb->prefix}charts_track_artists" : "{$wpdb->prefix}charts_video_artists";
							$id_col  = ( $type === 'track' ) ? 'track_id' : 'video_id';
							$artist_ids = $wpdb->get_col( $wpdb->prepare( "SELECT artist_id FROM $j_table WHERE $id_col = %d", $item->id ) ) ?: array();
							
							if ( empty($artist_ids) && !empty($item->primary_artist_id) ) $artist_ids = array($item->primary_artist_id);
							
							foreach ( $artist_ids as $a_id ) :
								$artist_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $a_id ) );
								if ( $artist_row ) :
							?>
								<?php 
									$a_resolved = \Charts\Core\PublicIntegration::resolve_display_name($artist_row);
								?>
								<a href="<?php echo home_url('/charts/artist/' . $artist_row->slug); ?>" style="display: flex; align-items: center; gap: 10px; color: var(--k-text); text-decoration: none; font-weight: 800; font-size: 14px;" class="<?php echo \Charts\Core\Typography::get_font_class($a_resolved['title']); ?>">
									<img src="<?php echo esc_url($artist_row->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
									<?php echo esc_html($a_resolved['title']); ?>
								</a>
							<?php 
								endif;
							endforeach; 
							?>
						</div>
						<?php if ( ! empty($item->release_date) ) : ?>
							<span style="font-size: 13px; font-weight: 700; color: var(--k-text-muted);"><?php echo esc_html($item->release_date); ?></span>
						<?php endif; ?>
				</div>
			</div>
		</section>

		<!-- CONTENT GRID -->
		<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 60px;">
			<!-- stats (left) -->
			<div>
				<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 24px;"><?php echo \Charts\Core\Translation::get('Track Stats'); ?></h3>
				<style>
					@keyframes pulse-red {
						0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
						70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
						100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
					}
				</style>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<?php 
					$item_stats = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = %s AND entity_id = %d", $type, $item->id ) );
					if ( $item_stats ) : 
						$meta_data = !empty($item_stats->metadata_json) ? json_decode($item_stats->metadata_json, true) : [];
						$trend_dir = $meta_data['trend_direction'] ?? 'Stable';
						$viral_status = $meta_data['viral_status'] ?? 'None';
						$top_10_prob = $meta_data['top_10_prob'] ?? 0;
						$top_5_prob = $meta_data['top_5_prob'] ?? 0;
						$no_1_prob = $meta_data['no_1_prob'] ?? 0;
						$current_rank = 0;
						
						// Find latest rank position
						$latest_rank = $wpdb->get_var($wpdb->prepare("SELECT rank_position FROM $entries_table WHERE item_type = %s AND item_id = %d ORDER BY id DESC LIMIT 1", $type, $item->id));
						if ($latest_rank) {
							$current_rank = intval($latest_rank);
						}
					?>
						<?php if ( ! empty($item_stats->weeks_on_chart) ) : ?>
						<div class="kc-stat-pill" style="grid-column: span 2;">
							<label><?php echo \Charts\Core\Translation::get('wks on chart'); ?></label>
							<span class="val"><?php echo \Charts\Core\Transliteration::to_arabic_numerals(intval($item_stats->weeks_on_chart)); ?></span>
						</div>
						<?php endif; ?>

						<!-- PREMIUM PREDICTION WIDGETS -->
						<div class="forecast-nexus-card" style="grid-column: span 2; background: #fff; border: 1px solid var(--k-border); border-radius: 20px; padding: 24px; margin-top: 10px; box-shadow: var(--k-shadow-sm); position: relative; overflow: hidden;">
							<div class="forecast-badge" style="background: linear-gradient(90deg, #6366f1, #fe025b); color: #fff; font-size: 9px; font-weight: 900; text-transform: uppercase; padding: 4px 10px; border-radius: 30px; display: inline-block; margin-bottom: 15px; letter-spacing: 0.05em;">
								FORECAST MATRIX
							</div>

							<!-- 1. Prediction Card -->
							<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 24px; background: #fafafa; padding: 15px; border-radius: 12px; border: 1px solid #f1f5f9;">
								<div>
									<span style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Predicted Peak</span>
									<span style="display:block; font-size:32px; font-weight:950; color:#6366f1; line-height: 1.1; margin-top: 4px;">#<?php echo intval($item_stats->predicted_peak ?: $peak_rank); ?></span>
								</div>
								<div style="text-align: right;">
									<span style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Confidence</span>
									<span style="display:block; font-size:20px; font-weight:900; color:#1e293b; margin-top: 4px;"><?php echo round($item_stats->confidence_score); ?>%</span>
								</div>
							</div>

							<!-- 2. Trend Gauge & Viral Alert Status -->
							<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; font-size:13px; font-weight:700;">
								<div>
									<span style="font-size:10px; color:#94a3b8; text-transform:uppercase; display:block; margin-bottom: 4px;">Trend Direction</span>
									<span style="color: <?php echo $trend_dir === 'Strong Upward' || $trend_dir === 'Upward' ? '#10b981' : ($trend_dir === 'Declining' ? '#ef4444' : '#64748b'); ?>;">
										<?php echo esc_html($trend_dir); ?>
									</span>
								</div>
								<?php if ($viral_status !== 'None') : ?>
									<div style="text-align: right;">
										<span style="font-size:10px; color:#94a3b8; text-transform:uppercase; display:block; margin-bottom: 4px;">Viral Alert</span>
										<span style="background:#fef2f2; color:#ef4444; font-size:10px; font-weight:900; padding:3px 8px; border-radius:5px; display: inline-block; animation: pulse-red 2s infinite;">
											<?php echo esc_html(strtoupper($viral_status)); ?>
										</span>
									</div>
								<?php endif; ?>
							</div>

							<!-- 3. Meters Group -->
							<div style="margin-bottom: 25px;">
								<div style="margin-bottom: 12px;">
									<div style="display:flex; justify-content:space-between; font-size:11px; font-weight:700; margin-bottom: 4px;">
										<span style="color:#64748b;">Momentum Meter</span>
										<span style="color:#6366f1; font-weight:800;"><?php echo round($item_stats->momentum_score); ?>/100</span>
									</div>
									<div style="width:100%; height:6px; background:#f1f5f9; border-radius:3px;">
										<div style="width:<?php echo round($item_stats->momentum_score); ?>%; height:100%; background:#6366f1; border-radius:3px;"></div>
									</div>
								</div>
								<div>
									<div style="display:flex; justify-content:space-between; font-size:11px; font-weight:700; margin-bottom: 4px;">
										<span style="color:#64748b;">Viral Meter</span>
										<span style="color:#fe025b; font-weight:800;"><?php echo round($item_stats->viral_score); ?>/100</span>
									</div>
									<div style="width:100%; height:6px; background:#f1f5f9; border-radius:3px;">
										<div style="width:<?php echo round($item_stats->viral_score); ?>%; height:100%; background:#fe025b; border-radius:3px;"></div>
									</div>
								</div>
							</div>

							<!-- 4. Forecast Timeline -->
							<div style="margin-bottom: 25px; background: #fafafa; padding: 15px; border-radius: 12px; border: 1px solid #f1f5f9;">
								<span style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block; margin-bottom: 12px;">Forecast Timeline</span>
								<div style="display:flex; justify-content:space-between; position:relative; align-items:center;">
									<div style="position:absolute; left:15%; right:15%; height:2px; background:#cbd5e1; z-index: 1;"></div>
									<div style="z-index: 2; text-align:center; background:#fafafa; padding: 0 5px;">
										<span style="font-size:10px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">Current</span>
										<span style="font-size:14px; font-weight:900; color:#1e293b; margin-top:2px; display:block;">#<?php echo intval($current_rank); ?></span>
									</div>
									<div style="z-index: 2; text-align:center; background:#fafafa; padding: 0 5px;">
										<span style="font-size:10px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">Next Week</span>
										<span style="font-size:14px; font-weight:900; color:#10b981; margin-top:2px; display:block;">#<?php echo intval($item_stats->predicted_next_week); ?></span>
									</div>
									<div style="z-index: 2; text-align:center; background:#fafafa; padding: 0 5px;">
										<span style="font-size:10px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">Next Month</span>
										<span style="font-size:14px; font-weight:900; color:#6366f1; margin-top:2px; display:block;">#<?php echo intval($item_stats->predicted_next_month); ?></span>
									</div>
								</div>
							</div>

							<!-- 5. Probability sigmoids -->
							<div>
								<span style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; display:block; margin-bottom: 12px;">Probability Metrics</span>
								<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align:center;">
									<div style="background:#f8fafc; padding:10px; border-radius:10px; border:1px solid #f1f5f9;">
										<span style="font-size:8px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">Top 10 Chance</span>
										<strong style="font-size:16px; color:#1e293b; display:block; margin-top:4px; font-weight:950;"><?php echo $top_10_prob; ?>%</strong>
									</div>
									<div style="background:#f8fafc; padding:10px; border-radius:10px; border:1px solid #f1f5f9;">
										<span style="font-size:8px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">Top 5 Chance</span>
										<strong style="font-size:16px; color:#6366f1; display:block; margin-top:4px; font-weight:950;"><?php echo $top_5_prob; ?>%</strong>
									</div>
									<div style="background:#f8fafc; padding:10px; border-radius:10px; border:1px solid #f1f5f9;">
										<span style="font-size:8px; color:#94a3b8; display:block; font-weight:700; text-transform:uppercase;">#1 Chance</span>
										<strong style="font-size:16px; color:#fe025b; display:block; margin-top:4px; font-weight:950;"><?php echo $no_1_prob; ?>%</strong>
									</div>
								</div>
							</div>
						</div>
					<?php else : ?>
						<p style="font-size: 11px; color: var(--k-text-muted); grid-column: span 2;"><?php echo \Charts\Core\Translation::get('Analytics still processing for this item.'); ?></p>
					<?php endif; ?>
				</div>

			</div>

			<!-- appearances (right) -->
			<div>
				<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted);"><?php echo \Charts\Core\Translation::get('Chart Appearances'); ?></h3>
				</div>

				<div style="display: flex; flex-direction: column; gap: 16px;">
					<?php if ( empty($appearances) ) : ?>
						<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);"><?php echo \Charts\Core\Translation::get('No chart appearances recorded yet.'); ?></p>
					<?php else : ?>
						<?php foreach ( $appearances as $app ) : ?>
							<a href="<?php echo home_url('/charts/' . sanitize_title($app->definition_title) . '/'); ?>" class="kc-card" style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; padding: 20px 32px; border-radius: 12px; transition: transform 0.2s;">
								<div style="display: flex; align-items: center; gap: 20px;">
									<div style="width: 44px; height: 44px; background: <?php echo !empty($app->accent_color) ? $app->accent_color : '#fe025b'; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
									</div>
									<div>
										<h4 style="font-size: 16px; font-weight: 900; margin: 0; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($app->definition_title ?: 'Standard Chart')); ?>"><?php echo esc_html(\Charts\Core\Translation::get($app->definition_title ?: 'Standard Chart')); ?></h4>
										<span style="font-size: 11px; font-weight: 600; color: var(--k-text-muted);"><?php echo \Charts\Core\Translation::get('Week of'); ?> <?php echo date('M j, Y', strtotime($app->period_start)); ?></span>
									</div>
								</div>
								<div style="display: flex; align-items: center; gap: 24px;">
									<div style="text-align: right;">
										<div style="font-size: 28px; font-weight: 950; color: var(--k-text);">#<?php echo $app->rank_position; ?></div>
										<div style="font-size: 10px; font-weight: 900; color: <?php echo $app->movement_direction === 'up' ? 'var(--k-accent)' : ($app->movement_direction === 'down' ? '#ef4444' : 'var(--k-text-muted)'); ?>;">
											<?php if ( $app->movement_direction === 'up' ) echo '▲ '; elseif ( $app->movement_direction === 'down' ) echo '▼ '; ?>
											<?php echo $app->movement_value ? intval($app->movement_value) : ''; ?>
											<?php echo !empty($app->peak_rank) ? ' ' . \Charts\Core\Translation::get('Peak #') . intval($app->peak_rank) : ''; ?>
										</div>
									</div>
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="color: var(--k-accent); opacity: 0.5;"><polyline points="9 18 15 12 9 6"></polyline></svg>
								</div>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- MORE BY ARTIST -->
		<?php if ( ! empty($more_items) ) : ?>
		<section class="kc-section" style="padding: 100px 0 80px;">
			<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;"><?php echo \Charts\Core\Translation::get('More by'); ?> <?php echo esc_html(\Charts\Core\Translation::get($artist->display_name)); ?></h3>
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
						<?php foreach ( $more_items as $mi ) : 
							$mi_resolved = \Charts\Core\PublicIntegration::resolve_display_name($mi);
						?>
							<a href="<?php echo home_url('/charts/' . $type . '/' . $mi->slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; border-radius: 12px; text-decoration: none;">
								<div style="display: flex; align-items: center; gap: 20px;">
									<img src="<?php echo esc_url($mi->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 10px;">
									<div>
										<h4 style="font-size: 16px; font-weight: 900; margin: 0; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($mi_resolved['title']); ?>"><?php echo esc_html($mi_resolved['title']); ?></h4>
									</div>
								</div>
						<div style="width: 32px; height: 32px; border: 1px solid var(--k-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--k-accent); border-color: var(--k-accent);">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- ARTIST PROMO BAR -->
		<?php if ( ! empty($artist_ids) ) : ?>
			<?php 
				$promo_layout = (count($artist_ids) > 1) ? 'grid-template-columns: 1fr 1fr;' : 'grid-template-columns: 1fr;';
			?>
			<div style="display: grid; <?php echo $promo_layout; ?> gap: 24px; margin-top: 60px; margin-bottom: 120px;">
			<?php 
				foreach ( $artist_ids as $a_id ) :
					$artist_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $a_id ) );
					if ( $artist_row ) :
						$ar_resolved = \Charts\Core\PublicIntegration::resolve_display_name($artist_row);
			?>
				<section class="kc-card" style="padding: 0; overflow: hidden; position: relative; height: 120px;">
					<img src="<?php echo esc_url($artist_row->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 1;">
					<div style="position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.95), transparent);"></div>
					<div style="position: relative; z-index: 10; display: flex; align-items: center; height: 100%; padding: 0 40px; justify-content: space-between;">
						<div style="display: flex; align-items: center; gap: 20px;">
							<img src="<?php echo esc_url($artist_row->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid white;">
							<div>
								<span style="font-size: 9px; font-weight: 950; color: var(--k-accent); text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 4px;"><?php echo \Charts\Core\Translation::get('Artist'); ?></span>
								<h3 style="font-size: 28px; font-weight: 950; margin: 0; color: #fff;" class="<?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($ar_resolved['title'])); ?>"><?php echo esc_html(\Charts\Core\Translation::get($ar_resolved['title'])); ?></h3>
							</div>
						</div>
						<a href="<?php echo home_url('/charts/artist/' . $artist_row->slug); ?>" class="kc-btn" style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff;">
							<?php echo \Charts\Core\Translation::get('View Artist'); ?> &larr;
						</a>
					</div>
				</section>
			<?php 
					endif;
				endforeach; 
			?>
			</div>
		<?php endif; ?>

		<!-- MORE CHARTS -->
		<section class="kc-section">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> <?php echo \Charts\Core\Translation::get('More Charts'); ?></h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all"><?php echo \Charts\Core\Translation::get('View All Charts'); ?> &larr;</a>
			</div>
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php 
				$other_defs = \Charts\Core\PublicIntegration::get_eligible_definitions( 4 );
				foreach ( $other_defs as $odef ) : 
					$oentries = \Charts\Core\PublicIntegration::get_preview_entries( $odef, 4 );
				?>
					<article class="kc-chart-card">
						<div class="kc-card-accent-dot" style="background: <?php echo $odef->accent_color ?: '#fe025b'; ?>;"></div>
						<div class="kc-card-header">
							<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_chart_image($odef, $oentries)); ?>">
							<div class="kc-card-header-overlay"></div>
							<span class="kc-card-label">قائمة الأسبوع</span>
							<h3 class="kc-card-title"><?php echo esc_html(\Charts\Core\Translation::get($odef->title)); ?></h3>
						</div>
						<div class="kc-card-list">
							<?php foreach ( $oentries as $oe ) : ?>
								<div class="kc-card-entry">
									<span class="kc-entry-rank"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($oe->rank_position); ?></span>
									<img class="kc-entry-art" src="<?php echo esc_url($oe->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
									<div class="kc-entry-info">
										<span class="kc-entry-name <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($oe->track_name)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($oe->track_name)); ?></span>
										<span class="kc-entry-artist <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($oe->artist_names)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($oe->artist_names)); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="kc-card-footer" style="justify-content: center;">
							<a href="<?php echo home_url('/charts/'.$odef->slug.'/'); ?>" class="kc-card-cta">عرض القائمة كاملة</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); } ?>
