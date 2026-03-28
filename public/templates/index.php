<?php
/**
 * Charts Directory Template - High Fidelity Integration
 * Rebuilt from scratch to resolve PHP parsing corruption.
 */

global $wpdb;

// 1. DATA LOOKUP
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( true ); // Only active/public charts

// Helper to fetch top 3 preview for a definition
function kc_get_preview_entries($def) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( "
		SELECT e.* FROM {$wpdb->prefix}charts_entries e
		JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
		WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
		ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3
	", $def->chart_type, $def->country_code ) );
}

get_header();
?>

<div class="kc-root kc-integrated">
	<div class="kc-container">
		
		<!-- DIRECTORY HEADER -->
		<header class="kc-dir-header" style="padding: 100px 0 60px;">
			<div style="font-size: 11px; font-weight: 900; color: #6366f1; letter-spacing: 0.2em; text-transform: uppercase; margin-bottom: 20px;">
				<span style="display:inline-block; width: 8px; height: 8px; background: #6366f1; border-radius: 50%; margin-right: 12px;"></span>
				Intelligence Explorer
			</div>
			<h1 style="font-size: clamp(3rem, 6vw, 6rem); font-weight: 950; letter-spacing: -0.05em; line-height: 0.85; margin: 0; color: white;">Charts Directory</h1>
		</header>

		<!-- CHART GRID -->
		<div class="kc-chart-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 40px; margin-bottom: 120px;">
			
			<?php if ( empty( $definitions ) ) : ?>
				<div class="kc-empty-grid" style="grid-column: 1 / -1; padding: 120px 40px; text-align: center; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 32px;">
					<h2 style="font-size: 24px; font-weight: 800; color: white;">No active charts found.</h2>
					<p style="opacity: 0.4;">Register chart definitions and map sources in the dashboard to begin.</p>
				</div>
			<?php else : ?>
				
				<?php foreach ( $definitions as $def ) : 
					$entries = kc_get_preview_entries($def);
					$accent  = !empty($def->accent_color) ? $def->accent_color : '#6366f1';
					$hero    = !empty($def->cover_image_url) ? $def->cover_image_url : (!empty($entries[0]->cover_image) ? $entries[0]->cover_image : CHARTS_URL . 'public/assets/img/placeholder.png');
				?>
					<article class="kc-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 32px; overflow: hidden; transition: transform 0.3s ease;">
						
						<!-- Card Hero -->
						<div style="position: relative; height: 260px; overflow: hidden;">
							<img src="<?php echo esc_url($hero); ?>" style="width: 100%; height: 100%; object-fit: cover;">
							<div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 100%);"></div>
							<div style="position: absolute; bottom: 32px; left: 32px; right: 32px;">
								<span style="font-size: 10px; font-weight: 900; letter-spacing: 0.1em; color: rgba(255,255,255,0.6); text-transform: uppercase;"><?php echo strtoupper($def->frequency); ?> CHART</span>
								<h2 style="font-size: 28px; font-weight: 900; margin: 8px 0 0; color: white; line-height: 1;"><?php echo esc_html($def->title); ?></h2>
							</div>
						</div>

						<!-- Card Preview List -->
						<div style="padding: 32px;">
							<?php if ( empty($entries) ) : ?>
								<div style="padding: 20px 0; font-size: 13px; opacity: 0.3; font-weight: 600;">Data synchronizing...</div>
							<?php else : ?>
								<div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
									<?php foreach ( $entries as $e ) : ?>
										<div style="display: flex; align-items: center; gap: 16px;">
											<span style="font-size: 14px; font-weight: 900; color: <?php echo $accent; ?>; width: 20px;"><?php echo $e->rank_position; ?></span>
											<div style="flex: 1; min-width: 0;">
												<div style="font-size: 14px; font-weight: 800; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($e->track_name); ?></div>
												<div style="font-size: 12px; opacity: 0.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($e->artist_names); ?></div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<!-- CTA -->
							<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" style="display: block; width: 100%; text-align: center; padding: 14px; background: rgba(255,255,255,0.05); color: white; text-decoration: none; border-radius: 16px; font-size: 13px; font-weight: 800; transition: background 0.2s;">
								Explore Rankings &rarr;
							</a>
						</div>

					</article>
				<?php endforeach; ?>

			<?php endif; ?>

		</div>

	</div>
</div>

<style>
.kc-root.kc-integrated { background: #000; color: #fff; min-height: 1000px; }
.kc-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; }
.kc-card:hover { transform: translateY(-10px); background: rgba(255,255,255,0.04) !important; }
.kc-card a:hover { background: rgba(255,255,255,0.1) !important; }
</style>

<?php get_footer(); ?>
