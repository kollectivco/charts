<?php

namespace Charts\Admin;

/**
 * Handle admin initialization.
 */
class Bootstrap {

	/**
	 * Initialize the admin module.
	 */
	public static function init() {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'admin_init', array( self::class, 'process_admin_actions' ) );
		
		// AJAX Handlers
		add_action( 'wp_ajax_charts_run_import', array( self::class, 'handle_run_import' ) );
	}

	/**
	 * Process POST actions for settings and imports.
	 */
	public static function process_admin_actions() {
		if ( ! is_admin() || ! isset( $_POST['charts_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'charts_admin_action' );

		switch ( $_POST['charts_action'] ) {
			case 'save_settings':
				update_option( 'charts_spotify_client_id', sanitize_text_field( $_POST['spotify_client_id'] ) );
				update_option( 'charts_spotify_client_secret', sanitize_text_field( $_POST['spotify_client_secret'] ) );
				add_settings_error( 'charts', 'settings_saved', __( 'Settings saved successfully.', 'charts' ), 'success' );
				break;

			case 'save_source':
				$manager = new SourceManager();
				$result = $manager->save_source( $_POST );
				if ( $result ) {
					add_settings_error( 'charts', 'source_saved', __( 'Source saved successfully.', 'charts' ), 'success' );
				} else {
					add_settings_error( 'charts', 'source_error', __( 'Failed to save source.', 'charts' ), 'error' );
				}
				break;

			case 'delete_source':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_source( $id );
				add_settings_error( 'charts', 'source_deleted', __( 'Source deleted.', 'charts' ), 'success' );
				break;

			case 'import_spotify_csv':
				self::process_spotify_csv_upload();
				break;
		}
	}

	/**
	 * Register the main admin menu and submenus.
	 */
	public static function register_menu() {
		$icon = 'dashicons-chart-bar';

		add_menu_page(
			__( 'Charts', 'charts' ),
			__( 'Charts', 'charts' ),
			'manage_options',
			'charts',
			array( self::class, 'render_dashboard' ),
			$icon,
			30
		);

		add_submenu_page(
			'charts',
			__( 'Overview', 'charts' ),
			__( 'Overview', 'charts' ),
			'manage_options',
			'charts',
			array( self::class, 'render_dashboard' )
		);

		add_submenu_page(
			'charts',
			__( 'Sources', 'charts' ),
			__( 'Sources', 'charts' ),
			'manage_options',
			'charts-sources',
			array( self::class, 'render_sources' )
		);

		add_submenu_page(
			'charts',
			__( 'Spotify Import', 'charts' ),
			__( 'Spotify Import', 'charts' ),
			'manage_options',
			'charts-spotify-import',
			array( self::class, 'render_spotify_import' )
		);

		add_submenu_page(
			'charts',
			__( 'Import Runs', 'charts' ),
			__( 'Import Runs', 'charts' ),
			'manage_options',
			'charts-imports',
			array( self::class, 'render_imports' )
		);

		add_submenu_page(
			'charts',
			__( 'Matching Center', 'charts' ),
			__( 'Matching Center', 'charts' ),
			'manage_options',
			'charts-matching',
			array( self::class, 'render_matching' )
		);

		add_submenu_page(
			'charts',
			__( 'Entities', 'charts' ),
			__( 'Entities', 'charts' ),
			'manage_options',
			'charts-entities',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts',
			__( 'Insights', 'charts' ),
			__( 'Insights', 'charts' ),
			'manage_options',
			'charts-insights',
			array( self::class, 'render_insights' )
		);

		add_submenu_page(
			'charts',
			__( 'Settings', 'charts' ),
			__( 'Settings', 'charts' ),
			'manage_options',
			'charts-settings',
			array( self::class, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin-specific assets.
	 */
	public static function enqueue_assets( $hook ) {
		// Only load on our pages
		if ( strpos( $hook, 'charts' ) === false ) {
			return;
		}

		wp_enqueue_style( 'charts-admin', CHARTS_URL . 'admin/assets/css/admin.css', array(), CHARTS_VERSION );
		wp_enqueue_script( 'charts-admin', CHARTS_URL . 'admin/assets/js/admin.js', array( 'jquery' ), CHARTS_VERSION, true );
		
		wp_localize_script( 'charts-admin', 'charts_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'charts_admin' ),
		) );
	}

	/**
	 * Render the Dashboard.
	 */
	public static function render_dashboard() {
		self::render_view( 'dashboard' );
	}

	public static function render_sources() {
		self::render_view( 'sources' );
	}

	public static function render_spotify_import() {
		self::render_view( 'import-sheet' );
	}

	/**
	 * Process Spotify CSV Upload.
	 */
	private static function process_spotify_csv_upload() {
		if ( empty( $_FILES['spotify_csv']['tmp_name'] ) ) {
			add_settings_error( 'charts', 'no_file', __( 'Please select a CSV file.', 'charts' ), 'error' );
			return;
		}

		$meta = array(
			'country'     => sanitize_text_field( $_POST['country'] ),
			'chart_type'  => sanitize_text_field( $_POST['chart_type'] ),
			'frequency'   => sanitize_text_field( $_POST['frequency'] ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ),
			'source_name' => sanitize_text_field( $_POST['source_name'] ),
		);

		$csv_content = file_get_contents( $_FILES['spotify_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			add_settings_error( 'charts', 'read_error', __( 'Failed to read CSV file.', 'charts' ), 'error' );
			return;
		}

		try {
			$importer = new \Charts\Services\SpotifyCsvImporter();
			$result = $importer->run( $csv_content, $meta );

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'charts', 'import_error', $result->get_error_message(), 'error' );
			} else {
				add_settings_error( 'charts', 'import_success', sprintf( __( 'Imported %d entries successfully.', 'charts' ), $result ), 'success' );
			}
		} catch ( \Exception $e ) {
			add_settings_error( 'charts', 'exception', $e->getMessage(), 'error' );
		}
	}

	/**
	 * Render the Import Runs.
	 */
	public static function render_imports() {
		self::render_view( 'results' );
	}

	public static function render_matching() {
		self::render_view( 'matching' );
	}

	public static function render_entities() {
		self::render_view( 'entities' );
	}

	public static function render_insights() {
		self::render_view( 'insights' );
	}

	/**
	 * AJAX logic to run an import.
	 */
	public static function handle_run_import() {
		check_ajax_referer( 'charts_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'charts' ) ) );
		}

		$source_id = isset( $_POST['source_id'] ) ? intval( $_POST['source_id'] ) : 0;
		if ( ! $source_id ) {
			wp_send_json_error( array( 'message' => __( 'Source ID missing.', 'charts' ) ) );
		}

		try {
			$import_flow = new \Charts\Services\ImportFlow();
			$result = $import_flow->run( $source_id );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 
				'message' => sprintf( __( 'Successfully imported %d entries.', 'charts' ), $result ),
				'count'   => $result
			) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Render the Settings.
	 */
	public static function render_settings() {
		self::render_view( 'settings' );
	}

	/**
	 * Helper to safely render an admin view.
	 */
	private static function render_view( $name, $data = [] ) {
		$file = CHARTS_PATH . "admin/views/{$name}.php";

		if ( ! file_exists( $file ) ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . sprintf( __( 'Critical: View file not found: %s', 'charts' ), esc_html( $name ) ) . '</p></div></div>';
			return;
		}

		if ( ! empty( $data ) ) {
			extract( $data );
		}

		include $file;
	}
}
