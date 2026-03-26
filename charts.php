<?php
/**
 * Plugin Name: Charts
 * Plugin URI: https://github.com/kollectivco/charts
 * Description: Production-grade chart intelligence engine for WordPress. Scrapes, normalizes, and analyzes global music and video charts.
 * Version: 1.3.0
 * Author: Kollectiv
 * Author URI: https://kollectiv.co
 * License: GPL2
 * Text Domain: charts
 * Update URI: https://github.com/kollectivco/charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'CHARTS_VERSION', '1.3.0' );
define( 'CHARTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHARTS_URL', plugin_dir_url( __FILE__ ) );
define( 'CHARTS_BASENAME', plugin_basename( __FILE__ ) );
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
	}

	/**
	 * Activation hook
	 */
	public function activate() {
		$this->migrate();
		
		// Flush rewrite rules
		\Charts\Core\Router::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Migration logic
	 */
	public function migrate() {
		// 1. Create/Update tables
		$schema = new \Charts\Database\Schema();
		$schema->install();
		
		// 2. Seed/Update sources
		$sources = new \Charts\Admin\SourceManager();
		$sources->seed_defaults();

		update_option( 'charts_db_version', CHARTS_VERSION );
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
		// Version check for migrations
		$db_version = get_option( 'charts_db_version' );
		if ( version_compare( $db_version, CHARTS_VERSION, '<' ) ) {
			$this->migrate();
		}

		// Initialize core components
		\Charts\Core\Bootstrap::init();

		// Handle updates
		new \Charts\Core\Updater( CHARTS_PATH . 'charts.php' );
		
		// Initialize Admin if we are in admin
		if ( is_admin() ) {
			\Charts\Admin\Bootstrap::init();
		}

		// Initialize Frontend
		\Charts\Frontend\Bootstrap::init();
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
