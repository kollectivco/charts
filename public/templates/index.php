<?php
/**
 * Kontentainment Charts — Modern Intelligence Dashboard
 * Light-Mode Bento System
 */
\Charts\Core\StandaloneLayout::get_header();

global $wpdb;
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( true ); // Fetch only public definitions

// Helper for row data
function get_latest_chart_preview( $def ) {
	global $wpdb;
	
	// Find latest period for matching sources
	$sources = $wpdb->get_results( $wpdb->prepare( "
		SELECT id FROM {$wpdb->prefix}charts_sources 
		WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1
	", $def->chart_type, $def->country_code, $def->frequency ) );
	
	if ( empty( $sources ) ) return array();
	
	$source_ids = array_column( $sources, 'id' );
	$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );
	
	$latest_period_id = $wpdb->get_var( $wpdb->prepare( "
		SELECT period_id FROM {$wpdb->prefix}charts_entries 
		WHERE source_id IN ($placeholders)
		ORDER BY created_at DESC LIMIT 1
	", ...$source_ids ) );
	
	if ( ! $latest_period_id ) return array();
	
	return $wpdb->get_results( $wpdb->prepare( "
		SELECT * FROM {$wpdb->prefix}charts_entries 
		WHERE source_id IN ($placeholders) AND period_id = %d
		ORDER BY rank_position ASC LIMIT 5
	", ...$source_ids, $latest_period_id ) );
}

?>

<div class="kc-root animate-fade-in-up">
	
	<!-- Premium Hero Section -->
	<section class="kc-hero" style="padding: 120px 0 100px; background: linear-gradient(to bottom, #fff, #f8fafc);">
		<div class="kc-container" style="text-align: center;">
			<div class="kc-brand-name" style="margin-bottom: 24px;"><?php _e( 'Global Intelligence Engine', 'charts' ); ?></div>
			<h1 class="kc-hero-title">Discover <em>The Sound</em> of Every Market.</h1>
			<p style="font-size: 1.25rem; color: var(--k-text-dim); font-weight: 500; max-width: 800px; margin: 0 auto 48px;">
				<?php _e( 'High-fidelity chart intelligence across Spotify, YouTube, and global platforms. Built for industry leaders and data nerds.', 'charts' ); ?>
			</p>
			<div class="kc-hero-actions">
				<a href="#explore" class="kc-btn large"><?php _e( 'Explore Markets', 'charts' ); ?> &darr;</a>
			</div>
		</div>
	</section>

	<main class="kc-container" id="explore" style="padding-bottom: 120px;">
		
		<div class="kc-section-header" style="margin-bottom: 48px;">
			<h2 class="kc-section-title"><?php _e( 'Active Intelligence Products', 'charts' ); ?></h2>
		</div>

		<!-- Bento Grid of Dynamic Charts -->
		<div class="kc-bento-grid">
			<?php if ( empty( $definitions ) ) : ?>
				<div class="kc-card kc-card-wide" style="text-align: center; padding: 100px;">
					<h3 style="font-size: 2rem; font-weight: 850;"><?php _e( 'The deck is empty.', 'charts' ); ?></h3>
					<p style="color: var(--k-text-dim); font-size: 1.1rem;"><?php _e( 'No chart products have been defined yet. Visit the admin area to launch your first intelligence product.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<?php foreach ( $definitions as $idx => $def ) : 
					$rows = get_latest_chart_preview( $def );
					
					// Determine card style
					$card_class = 'kc-card-small';
					if ( $def->is_featured ) $card_class = 'kc-card-medium';
					if ( $idx === 0 ) $card_class = 'kc-card-large';
					
					$platform_badge = ($def->platform === 'all') ? '' : 'platform-' . strtolower($def->platform);
				?>
					<div class="kc-card <?php echo $card_class; ?> kc-chart-list-card animate-fade-in-up" style="animation-delay: <?php echo ($idx * 0.1); ?>s;">
						<div class="kc-list-header">
							<div class="kc-list-title">
								<span class="kc-brand-name" style="font-size: 10px;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->frequency); ?></span>
								<h3><?php echo esc_html( $def->title ); ?></h3>
							</div>
							<div class="kc-list-meta">
								<span class="kc-badge kc-badge-accent"><?php echo strtoupper($def->chart_type); ?></span>
							</div>
						</div>

						<div class="kc-list-content">
							<?php if ( empty( $rows ) ) : ?>
								<div style="padding: 60px 32px; text-align: center; color: var(--k-text-dim); font-size: 13px; font-weight: 600;">
									<?php _e( 'Waiting for the next data cycle...', 'charts' ); ?>
								</div>
							<?php else : ?>
								<?php foreach ( $rows as $ridx => $row ) : ?>
									<div class="kc-preview-row">
										<span class="kc-preview-rank"><?php echo $row->rank_position; ?></span>
										<div class="kc-preview-info">
											<span class="kc-preview-name"><?php echo esc_html( $row->track_name ); ?></span>
											<span class="kc-preview-artist"><?php echo esc_html( $row->artist_names ); ?></span>
										</div>
										<div style="text-align: right;">
											<span class="kc-badge" style="font-size: 9px; padding: 3px 6px;"><?php echo strtoupper($row->movement_direction); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<div class="kc-card-footer" style="padding: 24px 32px; background: #fafafa; border-top: 1px solid var(--k-border); display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
							<div style="font-size: 12px; color: var(--k-text-dim); font-weight: 700;">
								<?php echo count($rows); ?> Positions
							</div>
							<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-btn" style="padding: 8px 20px; font-size: 12px;">
								<?php _e( 'Full Intelligence', 'charts' ); ?> &rarr;
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<!-- Insights Section -->
		<section style="margin-top: 120px;">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><?php _e( 'Trending Insights', 'charts' ); ?></h2>
			</div>
			<div class="kc-bento-grid">
				<div class="kc-card kc-card-medium" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none;">
					<h4 class="kc-brand-name" style="color: rgba(255,255,255,0.7);"><?php _e( 'Market Peak', 'charts' ); ?></h4>
					<h3 style="font-size: 2rem; font-weight: 850; margin: 16px 0;"><?php _e( 'Egypt is heating up.', 'charts' ); ?></h3>
					<p style="opacity: 0.8; font-weight: 500; font-size: 1.1rem; line-height: 1.6;">
						<?php _e( 'Local viral tracks are outperforming international releases for the third week running. High velocity detected in MENA region.', 'charts' ); ?>
					</p>
					<div style="margin-top: auto; padding-top: 32px;">
						<a href="#" style="background: white; color: var(--k-accent); font-weight: 800; padding: 12px 24px; border-radius: 100px; text-decoration: none; display: inline-block;">
							<?php _e( 'Read Analysis', 'charts' ); ?>
						</a>
					</div>
				</div>
				<div class="kc-card kc-card-small">
					<h4 class="kc-brand-name"><?php _e( 'Top Mover', 'charts' ); ?></h4>
					<div style="margin: 24px 0;">
						<div style="font-size: 2rem; font-weight: 950;">+45</div>
						<div style="font-size: 14px; font-weight: 700; color: var(--k-text-dim);"><?php _e( 'Positions gain by "Tarek" this week.', 'charts' ); ?></div>
					</div>
				</div>
				<div class="kc-card kc-card-small">
					<h4 class="kc-brand-name"><?php _e( 'New Era', 'charts' ); ?></h4>
					<div style="margin: 24px 0;">
						<div style="font-size: 2rem; font-weight: 950;">14</div>
						<div style="font-size: 14px; font-weight: 700; color: var(--k-text-dim);"><?php _e( 'New entries found in YouTube Top Videos.', 'charts' ); ?></div>
					</div>
				</div>
			</div>
		</section>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
