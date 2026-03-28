<?php
/**
 * Single Chart Template - High Fidelity Integration
 * Rebuilt from scratch to resolve PHP parsing corruption.
 */

global $wpdb;

// 1. DATA LOOKUP & STATE RESOLUTION
$manager         = new \Charts\Admin\SourceManager();
$definition_id   = get_query_var( 'charts_definition_id' );
$definition_slug = get_query_var( 'charts_definition_slug' );
$definition      = null;

// Resolve Chart Definition
if ( $definition_id ) {
	$definition = $manager->get_definition( $definition_id );
} elseif ( $definition_slug ) {
	$definition = $manager->get_definition_by_slug( $definition_slug );
}

// Initial State Logic
$page_state = 'ready'; // default
$sources    = array();
$period     = null;
$entries    = array();

if ( ! $definition ) {
	$page_state = 'not_found';
} else {
	// Resolved, now check sources
	$sources = $wpdb->get_results( $wpdb->prepare( "
		SELECT id FROM {$wpdb->prefix}charts_sources 
		WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1
	", $definition->chart_type, $definition->country_code, $definition->frequency ) );

	if ( empty( $sources ) ) {
		$page_state = 'disconnected';
	} else {
		// Sources found, check for data
		$source_ids = array_column( $sources, 'id' );
		$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

		// Period resolution
		$period = $wpdb->get_row( $wpdb->prepare( "
			SELECT p.* FROM {$wpdb->prefix}charts_periods p
			JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
			WHERE e.source_id IN ($placeholders)
			ORDER BY p.period_start DESC LIMIT 1
		", ...$source_ids ) );

		if ( ! $period ) {
			// Check if import is running
			$is_processing = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*) FROM {$wpdb->prefix}charts_import_runs 
				WHERE source_id IN ($placeholders) AND status IN ('started', 'processing')
			", ...$source_ids ) );

			$page_state = $is_processing ? 'processing' : 'empty';
		} else {
			// Final check: entries
			$entries = $wpdb->get_results( $wpdb->prepare( "
				SELECT * FROM {$wpdb->prefix}charts_entries 
				WHERE source_id IN ($placeholders) AND period_id = %d
				ORDER BY rank_position ASC
			", ...$source_ids, $period->id ) );

			if ( empty( $entries ) ) {
				$page_state = 'empty_entries';
			}
		}
	}
}

// 2. TEMPLATE RENDER
get_header();
?>

<!-- KC-ROOT START -->
<div class="kc-root kc-integrated" <?php if ($definition && !empty($definition->accent_color)) echo 'style="--k-accent: ' . esc_attr($definition->accent_color) . ';"'; ?>>
	
	<!-- ADMIN DIAGNOSTIC PANEL (Admin Only) -->
	<?php if ( current_user_can('manage_options') ) : ?>
		<div style="background: rgba(255,100,0,0.05); border: 1px dashed rgba(255,100,0,0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px; font-family: monospace; font-size: 11px; color: #ffaa00;">
			<div style="font-weight: 900; margin-bottom: 10px; text-transform: uppercase;">Operator Pipeline Diagnostic</div>
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
				<div>Slug: <?php echo esc_html($definition_slug ?: 'NULL'); ?></div>
				<div>ID: <?php echo esc_html($definition_id ?: 'NULL'); ?></div>
				<div>Definition: <?php echo $definition ? 'RESOLVED' : 'MISSING'; ?></div>
				<div>State: <?php echo strtoupper($page_state); ?></div>
				<div>Sources: <?php echo count($sources); ?></div>
				<div>Entries: <?php echo count($entries); ?></div>
			</div>
		</div>
	<?php endif; ?>

	<!-- STATE SWITCHING -->
	<?php if ( $page_state === 'not_found' ) : ?>
		<section class="kc-empty-state">
			<div class="kc-placeholder-icon">⌬</div>
			<h2>Chart Not Found</h2>
			<p>The requested chart definition is missing or the directory slug is invalid.</p>
			<a href="/charts" class="kc-btn-home">Return to Directory</a>
		</section>

	<?php elseif ( $page_state === 'disconnected' ) : ?>
		<section class="kc-empty-state">
			<div class="kc-placeholder-icon" style="color: var(--k-accent);">×</div>
			<h2>Pipeline Disconnected</h2>
			<p>A definition for "<?php echo esc_html($definition->title); ?>" exists, but no active data sources are feeding this configuration.</p>
			<?php if (current_user_can('manage_options')): ?>
				<a href="<?php echo admin_url('admin.php?page=charts-sources'); ?>" class="kc-btn-home">Map Data Sources</a>
			<?php endif; ?>
		</section>

	<?php elseif ( $page_state === 'processing' ) : ?>
		<section class="kc-empty-state">
			<div class="kc-spinner-large"></div>
			<h2>Intelligence Incoming</h2>
			<p>Our data crawlers are currently processing the market signals for the newest rankings.</p>
			<button onclick="window.location.reload();" class="kc-btn-home">Refresh Explorer</button>
		</section>

	<?php elseif ( $page_state === 'empty' || $page_state === 'empty_entries' ) : ?>
		<section class="kc-empty-state">
			<div class="kc-placeholder-icon">!</div>
			<h2>Awaiting Initial Load</h2>
			<p>The definitions for "<?php echo esc_html($definition->title); ?>" are active, but no historical periods have been imported for this configuration.</p>
			<?php if (current_user_can('manage_options')): ?>
				<a href="<?php echo admin_url('admin.php?page=charts-import'); ?>" class="kc-btn-home">Operator: Run Import</a>
			<?php endif; ?>
		</section>

	<?php else : 
		// FULL READY STATE RENDER
		$featured = $entries[0];
		$rankings = array_slice($entries, 1);
	?>
		<!-- BREADCRUMBS -->
		<nav class="kc-breadcrumbs">
			<a href="/charts">CHARTS</a> &nbsp;/&nbsp; <span style="color: white;"><?php echo strtoupper($definition->title); ?></span>
		</nav>

		<!-- HEADER -->
		<header class="kc-page-intro" style="margin-bottom: 60px;">
			<div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 40px;">
				<div style="flex: 1; min-width: 320px;">
					<div class="kc-label-bar" style="margin-bottom: 20px; color: var(--k-accent); font-size: 11px; font-weight: 900; letter-spacing: 0.15em; text-transform: uppercase;">
						<?php echo esc_html($definition->frequency); ?> &bull; <?php echo esc_html($definition->chart_type); ?> &bull; <?php echo strtoupper($definition->country_code); ?>
					</div>
					<h1 class="kc-page-title" style="font-size: clamp(3rem, 6vw, 5rem); font-weight: 950; letter-spacing: -0.05em; line-height: 0.9; margin: 0 0 16px; color: white;">
						<?php echo esc_html($definition->title); ?>
					</h1>
					<?php if (!empty($definition->title_ar)): ?>
						<div class="kc-page-subtitle" style="font-size: 24px; font-weight: 700; opacity: 0.6; margin-bottom: 24px;"><?php echo esc_html($definition->title_ar); ?></div>
					<?php endif; ?>
					<p class="kc-page-desc" style="font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.4); max-width: 680px; margin: 0;"><?php echo esc_html($definition->chart_summary); ?></p>
				</div>
				<div class="kc-page-meta-box" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 32px; border-radius: 20px; display: flex; gap: 40px;">
					<div class="kc-meta-stat">
						<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 8px;">Week of</label>
						<span style="font-size: 13px; font-weight: 800; color: white;"><?php echo date('M j, Y', strtotime($period->period_start)); ?></span>
					</div>
					<div class="kc-meta-stat">
						<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 8px;">Status</label>
						<span style="font-size: 13px; font-weight: 800; color: #22c55e;">Live &bull; <?php echo count($entries); ?> Items</span>
					</div>
				</div>
			</div>
		</header>

		<!-- HERO ITEM #1 -->
		<section class="kc-item-hero" style="position: relative; overflow: hidden; border-radius: 32px; padding: 60px; margin-bottom: 80px; background: #111;">
			<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.15; filter: blur(40px);">
			
			<div style="position: relative; z-index: 10; display: flex; gap: 60px; align-items: center; flex-wrap: wrap;">
				<img src="<?php echo esc_url($featured->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 320px; height: 320px; border-radius: 20px; filter: drop-shadow(0 30px 60px rgba(0,0,0,0.8));">
				<div style="flex: 1;">
					<div style="background: var(--k-accent); color: white; display: inline-block; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">#1 This Week</div>
					<h2 style="font-size: 64px; font-weight: 900; letter-spacing: -0.04em; margin-bottom: 8px; color: white;"><?php echo esc_html($featured->track_name); ?></h2>
					<p style="font-size: 24px; font-weight: 700; opacity: 0.5; margin-bottom: 40px;"><?php echo esc_html($featured->artist_names); ?></p>
					
					<div style="display: flex; gap: 32px;">
						<div>
							<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 4px;">Peak</label>
							<span style="font-size: 18px; font-weight: 900; color: var(--k-accent);">#<?php echo $featured->peak_rank ?: 1; ?></span>
						</div>
						<div>
							<label style="display: block; font-size: 9px; font-weight: 900; opacity: 0.4; text-transform: uppercase; margin-bottom: 4px;">Longevity</label>
							<span style="font-size: 18px; font-weight: 900; color: white;"><?php echo $featured->weeks_on_chart ?: 1; ?> wks</span>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- RANKINGS LIST -->
		<section class="kc-rankings-list">
			<h3 style="font-size: 11px; font-weight: 900; letter-spacing: 0.2em; opacity: 0.3; margin-bottom: 32px; text-transform: uppercase;">Full Rankings</h3>
			
			<div class="kc-table-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; overflow: hidden;">
				<?php foreach($rankings as $row): 
					$item_slug = $row->item_slug ?: sanitize_title($row->track_name);
					$item_url  = home_url('/charts/' . ($row->item_type==='video' ? 'video' : 'track') . '/' . $item_slug);
				?>
					<a href="<?php echo esc_url($item_url); ?>" class="kc-row-item" style="display: grid; grid-template-columns: 80px 100px 64px 1fr 100px 100px; padding: 20px 40px; border-bottom: 1px solid rgba(255,255,255,0.03); align-items: center; text-decoration: none; color: inherit; transition: background 0.2s;">
						<div style="font-size: 24px; font-weight: 950; letter-spacing: -0.05em; color: white;"><?php echo (int)$row->rank_position; ?></div>
						<div style="font-size: 11px; font-weight: 900; color: rgba(255,255,255,0.4);">
							<?php if ($row->movement_direction === 'up') echo '<span style="color: #22c55e;">▲</span> ' . $row->movement_value; ?>
							<?php if ($row->movement_direction === 'down') echo '<span style="color: #ef4444;">▼</span> ' . $row->movement_value; ?>
							<?php if ($row->movement_direction === 'new') echo '<span style="color: #eab308;">NEW</span>'; ?>
							<?php if ($row->movement_direction === 'static') echo '—'; ?>
						</div>
						<img src="<?php echo esc_url($row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover;">
						<div style="padding-left: 24px; min-width: 0;">
							<div style="font-size: 16px; font-weight: 800; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($row->track_name); ?></div>
							<div style="font-size: 13px; opacity: 0.4; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($row->artist_names); ?></div>
						</div>
						<div style="text-align: right; opacity: 0.4; font-weight: 700; font-size: 13px;">#<?php echo (int)$row->peak_rank; ?></div>
						<div style="text-align: right; font-weight: 800; font-size: 13px; color: white;"><?php echo $row->weeks_on_chart ?: 1; ?> wks</div>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<div style="height: 120px;"></div>
	<?php endif; ?>

</div> <!-- /kc-root -->

<!-- PUBLIC STYLES (Inline to ensure isolation) -->
<style>
.kc-root.kc-integrated { background: #000; color: #fff; min-height: 800px; }
.kc-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; }
.kc-breadcrumbs { padding: 40px 0 20px; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: rgba(255,255,255,0.4); }
.kc-breadcrumbs a { color: inherit; text-decoration: none; }
.kc-empty-state { text-align: center; padding: 160px 40px; }
.kc-placeholder-icon { font-size: 100px; opacity: 0.1; font-weight: 100; margin-bottom: 24px; }
.kc-btn-home { display: inline-block; padding: 16px 36px; background: #fff; color: #000; border-radius: 50px; text-decoration: none; font-weight: 900; font-size: 13px; margin-top: 40px; }
.kc-row-item:hover { background: rgba(255,255,255,0.04) !important; }
.kc-spinner-large { width: 64px; height: 64px; border: 4px solid rgba(255,255,255,0.1); border-top-color: var(--k-accent, #6366f1); border-radius: 50%; margin: 0 auto 32px; animation: kc-spin 1s linear infinite; }
@keyframes kc-spin { to { transform: rotate(360deg); } }
</style>

<?php get_footer(); ?>
