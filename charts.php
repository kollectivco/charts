<?php
/**
 * Plugin Name: Kontentainment Charts
 * Plugin URI: https://github.com/kollectivco/charts
 * Description: Music charts intelligence platform.
 * Version:           2.4.3
 * Author: Kollectiv
 * Author URI: https://kollectiv.net
 * Update URI: https://github.com/kollectivco/charts
 * Text Domain: kontentainment-charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHARTS_VERSION', '2.4.3' );
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
		add_action( 'plugins_loaded', array( $this, 'check_version' ) );

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
	 * Check version on load to ensure migrations run even if activation hook is skipped
	 */
	public function check_version() {
		$current_db_version = get_option( 'kcharts_db_version', '0.0.0' );
		if ( version_compare( $current_db_version, CHARTS_VERSION, '<' ) ) {
			$this->run_migrations();
		}
	}

	/**
	 * Database Migration & Versioning Logic
	 */
	public function run_migrations() {
		// 1. Ensure Table Structures are up to date
		$schema = new \Charts\Database\Schema();
		$schema->install();
		
		$current_db_version = get_option( 'kcharts_db_version', '0.0.0' );

		// 5. Force Arabic Translation for known Artists (v2.1.7)
		if ( version_compare( $current_db_version, '2.1.7', '<' ) ) {
			global $wpdb;
			$table_artists = $wpdb->prefix . 'charts_artists';
			$table_entries = $wpdb->prefix . 'charts_entries';
			
			$artist_translations = [
				'Essam Saasa'       => 'عصام صاصا',
				'Amr Diab'          => 'عمرو دياب',
				'Ahmed Saad'        => 'أحمد سعد',
				'Rahma Mohsen'      => 'رحمة محسن',
				'Hamou Al-Murshidi' => 'حمو المرشدي',
				'Angham'            => 'أنغام',
				'Houda Bondok'      => 'حودة بندق',
				'Lege-Cy'           => 'ليجي-سي',
			];

			if ( $wpdb->get_var("SHOW TABLES LIKE '$table_artists'") === $table_artists ) {
				foreach ($artist_translations as $en_name => $ar_name) {
					// Safe translation: change display name, keep slug intact
					$wpdb->update(
						$table_artists,
						['display_name' => $ar_name],
						['display_name' => $en_name]
					);
				}
			}

			if ( $wpdb->get_var("SHOW TABLES LIKE '$table_entries'") === $table_entries ) {
				foreach ($artist_translations as $en_name => $ar_name) {
					// Also update the cached artist names in entries
					$wpdb->query( $wpdb->prepare(
						"UPDATE $table_entries SET artist_names = %s WHERE artist_names = %s",
						$ar_name, $en_name
					) );
				}
			}
		}

		// 4. Force Arabic Translation for Chart Titles (v2.1.6)
		if ( version_compare( $current_db_version, '2.1.6', '<' ) ) {
			global $wpdb;
			$table = $wpdb->prefix . 'charts_definitions';
			
			// Only update if table exists
			if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table ) {
				$title_translations = [
					'Top 100 Songs' => 'أفضل 100 أغنية',
					'Top Videos'    => 'أفضل الفيديوهات',
					'Top Artists'   => 'أفضل الفنانين'
				];
				
				foreach ($title_translations as $en_title => $ar_title) {
					$wpdb->update(
						$table,
						['title' => $ar_title],
						['title' => $en_title]
					);
				}
			}
		}

		// 3. Force Arabic Translation for Labels (v2.1.5)
		if ( version_compare( $current_db_version, '2.1.5', '<' ) ) {
			$saved_settings = get_option('kcharts_settings', []);
			if ( isset($saved_settings['labels']) ) {
				$translations = [
					'View Full Chart' => 'عرض السباق كاملاً',
					'Trending Artist' => 'فنان تريند',
					'Top Artists'     => 'أفضل الفنانين',
					'All Charts'      => 'كل السباقات'
				];
				$updated = false;
				foreach ($saved_settings['labels'] as $key => $val) {
					if (isset($translations[$val])) {
						$saved_settings['labels'][$key] = $translations[$val];
						$updated = true;
					}
				}
				if ( $updated ) {
					update_option('kcharts_settings', $saved_settings);
				}
			}
		}

		// 2. Structural Sovereignty Migration (v1.30.0)
		if ( version_compare( $current_db_version, '1.30.0', '<' ) ) {
			global $wpdb;
			$defs = $wpdb->get_results( "SELECT id, chart_type, country_code, title FROM {$wpdb->prefix}charts_definitions" );
			foreach ( $defs as $def ) {
				$lookup_type = "cid-{$def->id}";
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s", $lookup_type ) );
				
				if ( ! $exists ) {
					$legacy_sources = $wpdb->get_results( $wpdb->prepare( 
						"SELECT id, platform FROM {$wpdb->prefix}charts_sources 
						 WHERE chart_type = %s AND country_code = %s", 
						$def->chart_type, $def->country_code 
					) );

					foreach ( $legacy_sources as $l_source ) {
						$new_name = ( $l_source->platform === 'spotify' ? 'Spotify → ' : 'YouTube → ' ) . $def->title;
						$wpdb->update( 
							"{$wpdb->prefix}charts_sources", 
							array( 'chart_type' => $lookup_type, 'source_name' => $new_name ), 
							array( 'id' => $l_source->id ) 
						);
					}
				}
			}
			// 3. Data Healing: Ensure max_rows/ordering_mode are populated
			$wpdb->query( "UPDATE {$wpdb->prefix}charts_definitions SET max_rows = 100 WHERE max_rows IS NULL OR max_rows = 0" );
			$wpdb->query( "UPDATE {$wpdb->prefix}charts_definitions SET ordering_mode = 'import' WHERE ordering_mode IS NULL OR ordering_mode = ''" );

			update_option( 'kcharts_db_version', '1.30.0' );
		}

		// 3. One-time Legacy Cleanup
		if ( ! get_option( 'kcharts_mock_cleaned' ) ) {
			$sources = new \Charts\Admin\SourceManager();
			$sources->cleanup_mock_data();
			update_option( 'kcharts_mock_cleaned', '1' );
		}

		// 4. Update the stored DB version
		if ( version_compare( $current_db_version, CHARTS_VERSION, '<' ) ) {
			// Always bump DB version to match plugin version
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

		// Initialize Update Checker (GitHub) - Temporarily disabled for stability testing
		if ( file_exists( CHARTS_PATH . 'inc/Integrations/plugin-update-checker/plugin-update-checker.php' ) ) {
			require_once CHARTS_PATH . 'inc/Integrations/plugin-update-checker/plugin-update-checker.php';
			$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/' . CHARTS_GITHUB_OWNER . '/' . CHARTS_GITHUB_REPO . '/',
				CHARTS_PLUGIN_FILE,
				CHARTS_PLUGIN_SLUG
			);
			
			// Authorized Request Support (Fixes 403 Forbidden errors)
			$token = \Charts\Core\Settings::get('advanced.github_access_token');
			if ( ! empty($token) ) {
				$update_checker->setAuthentication($token);
			}

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
