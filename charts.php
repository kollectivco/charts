<?php
/**
 * Plugin Name: Kontentainment Charts
 * Plugin URI: https://github.com/kollectivco/charts
 * Description: Music charts intelligence platform.
 * Version:           1.27.0
 * Author: Kollectiv
 * Author URI: https://kollectiv.net
 * Update URI: https://github.com/kollectivco/charts
 * Text Domain: kontentainment-charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'CHARTS_VERSION', '1.27.0' );
define( 'CHARTS_PLUGIN_SLUG', 'kontentainment-charts' ); // Canonical Slug
define( 'CHARTS_PLUGIN_FILE', __FILE__ );
define( 'CHARTS_PLUGIN_BASENAME', 'kontentainment-charts/charts.php' ); // Hardcoded for identity stability
define( 'CHARTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHARTS_URL', plugin_dir_url( __FILE__ ) );
define( 'CHARTS_GITHUB_OWNER', 'kollectivco' );
define( 'CHARTS_GITHUB_REPO', 'charts' );

/**
 * Autoloader for Charts Plugin
 */
spl_autoload_register( function ( $class ) {
	$prefix   = 'Charts\\';
	$base_dir = CHARTS_PATH . 'inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $relative_class;
	// Convert namespace to path
	$file = str_replace( '\\', '/', $file ) . '.php';
	$file = $base_dir . $file;

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Main Plugin Class
 */
final class Charts {

	/**
	 * Instance of this class.
	 *
	 * @var Charts
	 */
	private static $instance;

	/**
	 * Get the instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Custom Update Link in Plugins List - Use ACTUAL basename for filter registration
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_updater_link' ) );

		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
		add_action( 'admin_notices', array( $this, 'show_update_notice' ) );
	}

	/**
	 * Show success notice after update check.
	 */
	public function show_update_notice() {
		if ( isset( $_GET['charts_updated_checked'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Charts update cache cleared. Checking GitHub...', 'charts' ) . '</p></div>';
		}
	}

	/**
	 * Handle manual update check action.
	 */
	public function handle_manual_update_check() {
		if ( ! is_admin() || ! isset( $_GET['charts_action'] ) || $_GET['charts_action'] !== 'check_updates' ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Clear local GitHub check cache
		delete_transient( 'charts_github_update_check' );
		
		// Force WP to check for updates
		delete_site_transient( 'update_plugins' );

		// Redirect back with a flag
		$redirect_url = remove_query_arg( array( 'charts_action', '_wpnonce' ), admin_url( 'plugins.php' ) );
		$redirect_url = add_query_arg( 'charts_updated_checked', '1', $redirect_url );
		
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Add "Check for updates" link to plugins list.
	 */
	public function add_updater_link( $links ) {
		$url = add_query_arg( 'charts_action', 'check_updates', admin_url( 'plugins.php' ) );
		$update_link = '<a href="' . esc_url( $url ) . '" style="font-weight:700;color:#6366f1;">Check for Update</a>';
		array_unshift( $links, $update_link );
		return $links;
	}

	/**
	 * Activation hook
	 */
	public function activate() {
		$this->run_migrations();
		
		// Flush rewrite rules
		\Charts\Core\Router::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Database Migration & Versioning Logic
	 */
	public function run_migrations() {
		// 1. Ensure Table Structures are up to date
		$schema = new \Charts\Database\Schema();
		$schema->install();
		
		$current_db_version = get_option( 'kcharts_db_version', '0.0.0' );

		// 2. Data Migration (Historical)

		// 3. One-time Legacy Cleanup (Force remove the hardcoded mocks once and for all)
		if ( ! get_option( 'kcharts_mock_cleaned' ) ) {
			$sources = new \Charts\Admin\SourceManager();
			$sources->cleanup_mock_data();
			update_option( 'kcharts_mock_cleaned', '1' );
		}

		// 4. Update the stored DB version
		if ( version_compare( $current_db_version, CHARTS_VERSION, '<' ) ) {
			update_option( 'kcharts_db_version', CHARTS_VERSION );
		}
	}

	/**
	 * Deactivation hook
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Run versioned migrations
		$db_version = get_option( 'kcharts_db_version', '1.0.0' );
		if ( version_compare( $db_version, CHARTS_VERSION, '<' ) ) {
			$this->run_migrations();
			
			// Force flush rewrite rules on version update
			\Charts\Core\Router::add_rewrite_rules();
			flush_rewrite_rules();
		}
		\Charts\Core\Bootstrap::init();
		\Charts\Core\PostTypes::init();
		\Charts\Core\Router::init();

		// Handle installation integrity (folder parity)
		\Charts\Core\Integrity::init();

		// Initialize Update Checker (GitHub)
		if ( file_exists( CHARTS_PATH . 'inc/Integrations/plugin-update-checker/plugin-update-checker.php' ) ) {
			require_once CHARTS_PATH . 'inc/Integrations/plugin-update-checker/plugin-update-checker.php';
			$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/' . CHARTS_GITHUB_OWNER . '/' . CHARTS_GITHUB_REPO . '/',
				CHARTS_PLUGIN_FILE,
				CHARTS_PLUGIN_SLUG
			);
			// Enable checking for release assets (zips)
			$update_checker->getVcsApi()->enableReleaseAssets();
		}
		
		// Initialize Admin if we are in admin
		if ( is_admin() ) {
			\Charts\Admin\Bootstrap::init();
		}

		// Initialize Frontend
		\Charts\Frontend\Bootstrap::init();

		// Initialize Elementor Integration
		\Charts\Integrations\Elementor\Bootstrap::init();
	}
}

/**
 * Global function to access the plugin instance
 */
function charts() {
	return Charts::get_instance();
}

// Kick off the plugin
charts();
