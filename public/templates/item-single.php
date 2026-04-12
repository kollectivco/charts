<?php
/**
 * Kontentainment Charts — Intelligence Explorer (Single Track/Video)
 * Matches Reference #3
 */

global $wpdb;

$type = get_query_var( 'charts_item_type' ) ?: 'track';
$slug = get_query_var( 'charts_item_slug' );

$item = \Charts\Core\EntityManager::get_entity_by_slug( $type, $slug );

if ( ! $item ) {
	\Charts\Core\PublicIntegration::get_header();
	echo '<div class="kc-root"><h1>Item Not Found</h1></div>';
	\Charts\Core\PublicIntegration::get_footer();
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
	}
}

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

\Charts\Core\PublicIntegration::get_header();
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
					<h1 style="font-size: 72px; font-weight: 950; margin: 0; line-height: 1; letter-spacing: -0.04em;" class="<?php echo \Charts\Core\Typography::get_font_class($item->title); ?>"><?php echo esc_html($item->title); ?></h1>
					<?php if ( ! empty($item->title_franko) ) : ?>
						<div style="font-size: 24px; font-weight: 700; color: var(--k-text-dim); margin-top: 8px; opacity: 0.6; letter-spacing: -0.02em;"><?php echo esc_html($item->title_franko); ?></div>
					<?php endif; ?>
					
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
								<a href="<?php echo home_url('/charts/artist/' . $artist_row->slug); ?>" style="display: flex; align-items: center; gap: 10px; color: var(--k-text); text-decoration: none; font-weight: 800; font-size: 14px;" class="<?php echo \Charts\Core\Typography::get_font_class($artist_row->display_name); ?>">
									<img src="<?php echo esc_url($artist_row->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
									<?php echo esc_html($artist_row->display_name); ?>
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
				<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 24px;">Track Stats</h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<?php 
					$item_stats = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = %s AND entity_id = %d", $type, $item->id ) );
					if ( $item_stats ) : 
					?>
						<?php if ( ! empty($item_stats->weeks_on_chart) ) : ?>
						<div class="kc-stat-pill">
							<label>Weeks on Chart</label>
							<span class="val"><?php echo intval($item_stats->weeks_on_chart); ?></span>
						</div>
						<?php endif; ?>
					<?php else : ?>
						<p style="font-size: 11px; color: var(--k-text-muted); grid-column: span 2;">Analytics still processing for this item.</p>
					<?php endif; ?>
				</div>

				<div class="kc-card" style="margin-top: 40px; display: flex; align-items: center; gap: 24px; padding: 24px 32px;">
					<img src="<?php echo esc_url($artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover;">
					<div>
						<span style="display: block; font-size: 9px; font-weight: 950; color: var(--k-accent); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px;">Primary Artist</span>
						<span style="font-size: 16px; font-weight: 900; color: var(--k-text);"><?php echo esc_html($artist->display_name); ?></span>
					</div>
				</div>
			</div>

			<!-- appearances (right) -->
			<div>
				<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted);">Chart Appearances</h3>
				</div>

				<div style="display: flex; flex-direction: column; gap: 16px;">
					<?php if ( empty($appearances) ) : ?>
						<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No chart appearances recorded yet.</p>
					<?php else : ?>
						<?php foreach ( $appearances as $app ) : ?>
							<a href="<?php echo home_url('/charts/' . sanitize_title($app->definition_title) . '/'); ?>" class="kc-card" style="display: flex; justify-content: space-between; align-items: center; text-decoration: none; padding: 20px 32px; border-radius: 12px; transition: transform 0.2s;">
								<div style="display: flex; align-items: center; gap: 20px;">
									<div style="width: 44px; height: 44px; background: <?php echo !empty($app->accent_color) ? $app->accent_color : '#fe025b'; ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
									</div>
									<div>
										<h4 style="font-size: 16px; font-weight: 900; margin: 0; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($app->definition_title); ?>"><?php echo esc_html($app->definition_title ?: 'Standard Chart'); ?></h4>
										<span style="font-size: 11px; font-weight: 600; color: var(--k-text-muted);">Week of <?php echo date('M j, Y', strtotime($app->period_start)); ?></span>
									</div>
								</div>
								<div style="display: flex; align-items: center; gap: 24px;">
									<div style="text-align: right;">
										<div style="font-size: 28px; font-weight: 950; color: var(--k-text);">#<?php echo $app->rank_position; ?></div>
										<div style="font-size: 10px; font-weight: 900; color: <?php echo $app->movement_direction === 'up' ? 'var(--k-accent)' : ($app->movement_direction === 'down' ? '#ef4444' : 'var(--k-text-muted)'); ?>;">
											<?php if ( $app->movement_direction === 'up' ) echo '▲ '; elseif ( $app->movement_direction === 'down' ) echo '▼ '; ?>
											<?php echo $app->movement_value ? intval($app->movement_value) : ''; ?>
											<?php echo !empty($app->peak_rank) ? ' Peak #' . intval($app->peak_rank) : ''; ?>
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
			<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">More by <?php echo esc_html($artist->display_name); ?></h3>
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php foreach ( $more_items as $mi ) : ?>
					<a href="<?php echo home_url('/charts/' . $type . '/' . $mi->slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; border-radius: 12px; text-decoration: none;">
						<div style="display: flex; align-items: center; gap: 20px;">
							<img src="<?php echo esc_url($mi->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 10px;">
							<div>
								<h4 style="font-size: 16px; font-weight: 900; margin: 0; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($mi->title); ?>"><?php echo esc_html($mi->title); ?></h4>
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
		<?php if ( $artist ) : ?>
			<section class="kc-card" style="padding: 0; overflow: hidden; position: relative; margin-top: 60px; margin-bottom: 120px; height: 120px;">
				<img src="<?php echo esc_url($artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 1;">
				<div style="position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.95), transparent);"></div>
				<div style="position: relative; z-index: 10; display: flex; align-items: center; height: 100%; padding: 0 40px; justify-content: space-between;">
					<div style="display: flex; align-items: center; gap: 20px;">
						<img src="<?php echo esc_url($artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid white;">
						<div>
							<span style="font-size: 9px; font-weight: 950; color: var(--k-accent); text-transform: uppercase; letter-spacing: 0.1em; display: block; margin-bottom: 4px;">Artist</span>
							<h3 style="font-size: 24px; font-weight: 900; color: white; margin: 0;"><?php echo esc_html($artist->display_name); ?></h3>
						</div>
					</div>
					<a href="<?php echo home_url('/charts/artist/' . $artist->slug); ?>" class="kc-view-all" style="color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 24px; border-radius: 99px; text-decoration: none;">View Artist &rarr;</a>
				</div>
			</section>
		<?php endif; ?>

		<!-- MORE CHARTS -->
		<section class="kc-section">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> More Charts</h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all">View All Charts &rarr;</a>
			</div>
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php 
				$other_defs = \Charts\Core\PublicIntegration::get_eligible_definitions( 4 );
				foreach ( $other_defs as $odef ) : 
					$sources = \Charts\Core\PublicIntegration::get_sources_for_chart($odef);
					$oentries = array();
					if ( ! empty($sources) ) {
						$s_ids = array_column($sources, 'id');
						$phs = implode(',', array_fill(0, count($s_ids), '%d'));
						$oentries = $wpdb->get_results( $wpdb->prepare( "
							SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), t.cover_image, v.thumbnail, a.image) AS resolved_image 
							FROM {$wpdb->prefix}charts_entries e 
							LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
							LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
							LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
							WHERE e.source_id IN ($phs)
							ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 4"
						, ...$s_ids ) );
					}
				?>
					<article class="kc-chart-card">
						<div class="kc-card-accent-dot" style="background: <?php echo $odef->accent_color ?: '#fe025b'; ?>;"></div>
						<div class="kc-card-header">
							<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_chart_image($odef, $oentries)); ?>">
							<div class="kc-card-header-overlay"></div>
							<span class="kc-card-label">Weekly Chart</span>
							<h3 class="kc-card-title"><?php echo esc_html($odef->title); ?></h3>
						</div>
						<div class="kc-card-list">
							<?php foreach ( $oentries as $oe ) : ?>
								<div class="kc-card-entry">
									<span class="kc-entry-rank"><?php echo $oe->rank_position; ?></span>
									<img class="kc-entry-art" src="<?php echo esc_url($oe->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>">
									<div class="kc-entry-info">
										<span class="kc-entry-name"><?php echo esc_html($oe->track_name); ?></span>
										<span class="kc-entry-artist"><?php echo esc_html($oe->artist_names); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="kc-card-footer" style="justify-content: center;">
							<a href="<?php echo home_url('/charts/'.$odef->slug.'/'); ?>" class="kc-card-cta">See Full Chart</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\PublicIntegration::get_footer(); ?>
