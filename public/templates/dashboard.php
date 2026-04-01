<?php
/**
 * Kontentainment Charts — External Dashboard Router
 */

include CHARTS_PATH . 'public/templates/parts/dashboard-shell.php';

$module = get_query_var( 'charts_module', 'overview' );
$title = 'Dashboard';

switch ( $module ) {
	case 'overview':
		$title = 'Overview';
		break;
	case 'charts':
		$title = 'Manage Charts';
		break;
	case 'sources':
		$title = 'Data Sources';
		break;
	case 'import':
		$title = 'Import Center';
		break;
	case 'matching':
		$title = 'Matching Center';
		break;
	case 'entities':
	case 'artists':
	case 'tracks':
	case 'clips':
		$title = 'Metadata Center';
		break;
	case 'insights':
	case 'intelligence':
		$title = 'Intelligence & Insights';
		break;
	case 'settings':
		$title = 'Platform Settings';
		break;
}
?>

<div class="kc-db-header">
	<h1 class="kc-db-title"><?php echo esc_html( $title ); ?></h1>
	
	<div class="db-actions">
		<a href="<?php echo home_url('/charts'); ?>" target="_blank" class="db-btn">View Public Site</a>
		<button class="db-btn db-btn-primary" onclick="location.reload();">Refresh Data</button>
	</div>
</div>

<div class="kc-db-notifications">
	<?php settings_errors( 'charts' ); ?>
</div>

<div class="kc-db-content">
	<?php
	// Load the specific module view from public/templates/dashboard/
	// We no longer fall back to admin views to maintain strict UI separation.
	$external_view = CHARTS_PATH . "public/templates/dashboard/{$module}.php";

	if ( file_exists( $external_view ) ) {
		// Module data prep
		global $wpdb;
		
		// Map specific data for known modules if not already handled in the view
		if ( $module === 'overview' ) {
			$stats = array(
				'charts_total'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions" ),
				'charts_published' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions WHERE is_public = 1" ),
				'tracks'           => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks" ),
				'artists'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists" ),
				'pending'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries WHERE item_id = 0" ),
				'imports'          => $wpdb->get_results( "SELECT i.*, s.source_name FROM {$wpdb->prefix}charts_import_runs i JOIN {$wpdb->prefix}charts_sources s ON s.id = i.source_id ORDER BY i.started_at DESC LIMIT 6" ),
			);
			extract( $stats );
		}
		
		include $external_view;
	} else {
		echo '<div class="bento-card">
				<div style="text-align:center; padding:60px;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.2; margin-bottom:20px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
					<h3 style="font-size:20px; font-weight:900; margin-bottom:12px;">Module Under Development</h3>
					<p style="color:var(--db-text-muted);">The requested module "'.esc_html($module).'" is not yet available in the external dashboard.</p>
					<div style="margin-top:32px;">
						<a href="' . admin_url('admin.php?page=charts-' . $module) . '" class="db-btn db-btn-primary">View in WP-Admin</a>
					</div>
				</div>
			  </div>';
	}
	?>
</div>

</main> <!-- .kc-db-main -->

<script src="<?php echo CHARTS_URL . 'public/assets/js/dashboard.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
