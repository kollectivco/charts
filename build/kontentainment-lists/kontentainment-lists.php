<?php
/**
 * Plugin Name: Kontentainment Lists
 * Plugin URI: https://github.com/kollectivco/kontentainment-lists
 * Description: Premium music lists intelligence and editorial publishing platform.
 * Version: 1.7.0
 * Author: Kollectiv
 * Author URI: https://kollectiv.net
 * Update URI: https://github.com/kollectivco/kontentainment-lists
 * Text Domain: kontentainment-lists
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define canonical constants for the rebrand.
define( 'KONTENTAINMENT_LISTS_VERSION', '1.7.0' );
define( 'KONTENTAINMENT_LISTS_PLUGIN_SLUG', 'kontentainment-lists' );
define( 'KONTENTAINMENT_LISTS_PLUGIN_FILE', __FILE__ );
define( 'KONTENTAINMENT_LISTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_LEGACY_PLUGIN_BASENAME', 'kontentainment-charts/charts.php' );
define( 'KONTENTAINMENT_LISTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_URL', plugin_dir_url( __FILE__ ) );
define( 'KONTENTAINMENT_LISTS_GITHUB_OWNER', 'kollectivco' );
define( 'KONTENTAINMENT_LISTS_GITHUB_REPO', 'kontentainment-lists' );

// Legacy aliases preserved for backward compatibility with the existing codebase.
defined( 'CHARTS_VERSION' ) || define( 'CHARTS_VERSION', KONTENTAINMENT_LISTS_VERSION );
defined( 'CHARTS_PLUGIN_SLUG' ) || define( 'CHARTS_PLUGIN_SLUG', KONTENTAINMENT_LISTS_PLUGIN_SLUG );
defined( 'CHARTS_PLUGIN_FILE' ) || define( 'CHARTS_PLUGIN_FILE', KONTENTAINMENT_LISTS_PLUGIN_FILE );
defined( 'CHARTS_PLUGIN_BASENAME' ) || define( 'CHARTS_PLUGIN_BASENAME', KONTENTAINMENT_LISTS_PLUGIN_BASENAME );
defined( 'CHARTS_PATH' ) || define( 'CHARTS_PATH', KONTENTAINMENT_LISTS_PATH );
defined( 'CHARTS_URL' ) || define( 'CHARTS_URL', KONTENTAINMENT_LISTS_URL );
defined( 'CHARTS_GITHUB_OWNER' ) || define( 'CHARTS_GITHUB_OWNER', KONTENTAINMENT_LISTS_GITHUB_OWNER );
defined( 'CHARTS_GITHUB_REPO' ) || define( 'CHARTS_GITHUB_REPO', KONTENTAINMENT_LISTS_GITHUB_REPO );

/**
 * Autoloader for Lists plugin.
 */
spl_autoload_register( function ( $class ) {
	$prefix   = 'Charts\\';
	$base_dir = CHARTS_PATH . 'inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = str_replace( '\\', '/', $relative_class ) . '.php';
	$file           = $base_dir . $file;

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Main plugin class.
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

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_updater_link' ) );
		add_filter( 'plugin_action_links_' . KONTENTAINMENT_LISTS_LEGACY_PLUGIN_BASENAME, array( $this, 'add_updater_link' ) );

		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
		add_action( 'admin_notices', array( $this, 'show_update_notice' ) );
	}

	/**
	 * Show success notice after update check.
	 */
	public function show_update_notice() {
		if ( isset( $_GET['lists_updated_checked'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Lists update cache cleared. Checking GitHub...', 'kontentainment-lists' ) . '</p></div>';
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

		delete_transient( 'lists_github_update_check' );
		delete_transient( 'charts_github_update_check' );
		delete_site_transient( 'update_plugins' );

		$redirect_url = remove_query_arg( array( 'charts_action', '_wpnonce' ), admin_url( 'plugins.php' ) );
		$redirect_url = add_query_arg( 'lists_updated_checked', '1', $redirect_url );

		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Add "Check for updates" link to plugins list.
	 */
	public function add_updater_link( $links ) {
		$url = add_query_arg( 'charts_action', 'check_updates', admin_url( 'plugins.php' ) );
		$update_link = '<a href="' . esc_url( $url ) . '" style="font-weight:700;color:#6366f1;">' . esc_html__( 'Check for Update', 'kontentainment-lists' ) . '</a>';
		array_unshift( $links, $update_link );
		return $links;
	}

	/**
	 * Activation hook
	 */
	public function activate() {
		$this->migrate();
		\Charts\Core\Router::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Migration logic
	 */
	public function migrate() {
		$schema = new \Charts\Database\Schema();
		$schema->install();

		$sources = new \Charts\Admin\SourceManager();
		$sources->seed_defaults();

		update_option( 'charts_db_version', CHARTS_VERSION );
		update_option( 'lists_db_version', KONTENTAINMENT_LISTS_VERSION );
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
		load_plugin_textdomain(
			'kontentainment-lists',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		$db_version = get_option( 'lists_db_version', get_option( 'charts_db_version' ) );
		if ( version_compare( (string) $db_version, KONTENTAINMENT_LISTS_VERSION, '<' ) ) {
			$this->migrate();
			\Charts\Core\Router::add_rewrite_rules();
			flush_rewrite_rules( false );
		}

		\Charts\Core\Bootstrap::init();
		new \Charts\Core\Updater( KONTENTAINMENT_LISTS_PLUGIN_FILE );

		if ( is_admin() ) {
			\Charts\Admin\Bootstrap::init();
		}

		\Charts\Frontend\Bootstrap::init();
		\Charts\Integrations\Elementor\Bootstrap::init();
	}
}

/**
 * Global accessors.
 */
function charts() {
	return Charts::get_instance();
}

function lists() {
	return Charts::get_instance();
}

charts();
