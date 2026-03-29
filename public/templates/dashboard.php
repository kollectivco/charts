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

<div class="kc-db-content">
	<?php
	// Load the specific module view
	// We check if we have a specialized external view, otherwise we fall back to admin views (wrapped)
	$external_view = CHARTS_PATH . "public/templates/dashboard/{$module}.php";
	$admin_view    = CHARTS_PATH . "admin/views/{$module}.php";

	if ( file_exists( $external_view ) ) {
		include $external_view;
	} elseif ( file_exists( $admin_view ) ) {
		// Prepare data for admin views that expect specific variables
		global $wpdb;
		if ( $module === 'overview' ) {
			$stats = array(
				'charts_total'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions" ),
				'charts_published' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions WHERE is_public = 1" ),
				'charts_draft'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions WHERE is_public = 0" ),
				'tracks'           => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks" ),
				'artists'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists" ),
				'albums'           => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_albums" ),
				'sources_active'   => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_sources WHERE is_active = 1" ),
				'pending'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries WHERE item_id = 0" ),
				'imports'          => $wpdb->get_results( "SELECT i.*, s.source_name FROM {$wpdb->prefix}charts_import_runs i JOIN {$wpdb->prefix}charts_sources s ON s.id = i.source_id ORDER BY i.started_at DESC LIMIT 5" ),
			);
			extract( $stats );
		}
		
		// Map some admin view filenames to modules if they differ
		if ($module === 'entities') {
			include CHARTS_PATH . "admin/views/entities.php";
		} else {
			include $admin_view;
		}
	} else {
		echo '<div class="bento-card"><h3>Module Not Found</h3><p>The requested module "' . esc_html($module) . '" is under development or does not exist.</p></div>';
	}
	?>
</div>

</main> <!-- .kc-db-main -->

<script src="<?php echo CHARTS_URL . 'public/assets/js/dashboard.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
