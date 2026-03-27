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
		add_action( 'wp_ajax_charts_recalculate_intel', array( self::class, 'handle_recalculate_intel' ) );
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
				update_option( 'charts_youtube_api_key', sanitize_text_field( $_POST['youtube_api_key'] ) );

				// Branding Settings
				update_option( 'charts_logo_id', intval( $_POST['logo_id'] ?? 0 ) );
				update_option( 'charts_logo_alt', sanitize_text_field( $_POST['logo_alt'] ?? '' ) );
				update_option( 'charts_wordmark', sanitize_text_field( $_POST['wordmark'] ?? '' ) );
				
				// Element Visibility
				update_option( 'charts_show_logo', isset( $_POST['show_logo'] ) ? 1 : 0 );
				update_option( 'charts_show_nav', isset( $_POST['show_nav'] ) ? 1 : 0 );
				update_option( 'charts_show_search', isset( $_POST['show_search'] ) ? 1 : 0 );
				update_option( 'charts_header_menu_id', intval( $_POST['header_menu_id'] ?? 0 ) );
				
				// Footer Content
				update_option( 'charts_footer_description', sanitize_textarea_field( $_POST['footer_description'] ?? '' ) );
				update_option( 'charts_footer_copyright', sanitize_text_field( $_POST['footer_copyright'] ?? '' ) );

				add_settings_error( 'charts', 'settings_saved', __( 'Settings saved.', 'charts' ), 'success' );
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

			case 'import_youtube_csv':
				self::process_youtube_csv_upload();
				break;
			
			case 'unified_import':
				self::process_unified_import();
				break;
			
			case 'save_definition':
				$manager = new SourceManager();
				$result = $manager->save_definition( $_POST );
				if ( $result ) {
					add_settings_error( 'charts', 'def_saved', __( 'Chart definition saved.', 'charts' ), 'success' );
				} else {
					add_settings_error( 'charts', 'def_error', __( 'Failed to save chart definition.', 'charts' ), 'error' );
				}
				break;
			
			case 'delete_definition':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_definition( $id );
				add_settings_error( 'charts', 'def_deleted', __( 'Chart definition deleted.', 'charts' ), 'success' );
				break;

			case 'delete_entity':
				global $wpdb;
				$id    = intval( $_POST['id'] );
				$type  = sanitize_text_field( $_POST['type'] );
				$table = $wpdb->prefix . 'charts_' . ( $type === 'artist' ? 'artists' : 'tracks' );
				
				// 1. Delete the canonical metadata
				$wpdb->delete( $table, array( 'id' => $id ) );
				
				// 2. Prevent orphaned relationships in historical entries
				$wpdb->update( 
					$wpdb->prefix . 'charts_entries', 
					array( 'item_id' => 0 ), 
					array( 'item_id' => $id, 'item_type' => $type ) 
				);
				
				delete_transient( 'charts_intel_last_calc' );
				add_settings_error( 'charts', 'entity_deleted', __( 'Entity deleted and relationships unlinked.', 'charts' ), 'success' );
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
			__( 'Manage Charts', 'charts' ),
			__( 'Manage Charts', 'charts' ),
			'manage_options',
			'charts-definitions',
			array( self::class, 'render_definitions' )
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
			__( 'Import Center', 'charts' ),
			__( 'Import Center', 'charts' ),
			'manage_options',
			'charts-import',
			array( self::class, 'render_import_center' )
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
			__( 'Artists', 'charts' ),
			__( 'Artists', 'charts' ),
			'manage_options',
			'charts-artists',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts',
			__( 'Tracks', 'charts' ),
			__( 'Tracks', 'charts' ),
			'manage_options',
			'charts-tracks',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts',
			__( 'Metadata Center', 'charts' ),
			__( 'Advanced Entities', 'charts' ),
			'manage_options',
			'charts-entities',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts',
			__( 'Intelligence', 'charts' ),
			__( 'Intelligence', 'charts' ),
			'manage_options',
			'charts-intelligence',
			array( self::class, 'render_intelligence' )
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
		
		// Enqueue WordPress Media for Logo Upload
		wp_enqueue_media();

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

	public static function render_definitions() {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			self::render_view( 'definition-edit' );
		} else {
			self::render_view( 'definitions' );
		}
	}

	public static function render_spotify_import() {
		wp_redirect( admin_url( 'admin.php?page=charts-import&source=spotify' ) );
		exit;
	}

	public static function render_youtube_import() {
		wp_redirect( admin_url( 'admin.php?page=charts-import&source=youtube' ) );
		exit;
	}

	public static function render_import_center() {
		self::render_view( 'import-center' );
	}

	/**
	 * Process Unified Import.
	 * Routes to the correct platform handler based on selection.
	 */
	private static function process_unified_import() {
		$platform = sanitize_text_field( $_POST['platform'] ?? 'spotify' );
		
		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			add_settings_error( 'charts', 'no_file', __( 'Please select a data file to upload.', 'charts' ), 'error' );
			return;
		}

		// Inject the correct file name into the expected $_FILES location for compatibility
		if ( $platform === 'spotify' ) {
			$_FILES['spotify_csv'] = $_FILES['import_file'];
			self::process_spotify_csv_upload();
		} else {
			$_FILES['youtube_csv'] = $_FILES['import_file'];
			self::process_youtube_csv_upload();
		}
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
			$result   = $importer->run( $csv_content, $meta );
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'charts', 'import_error', $result->get_error_message(), 'error' );
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/charts/spotify/' . rawurlencode( $meta['country'] ) . '/' . rawurlencode( $meta['frequency'] ) . '/' . rawurlencode( $meta['chart_type'] ) . '/' );
				$msg = sprintf( __( 'Import complete: %1$d entries saved from %2$d rows. Source ID: %3$d, Period ID: %4$d. %5$d skipped. <a href="%6$s" target="_blank">View Chart</a>', 'charts' ), $result['saved'], $result['parsed'], $result['source_id'], $result['period_id'], $result['skipped'], esc_url( $chart_url ) );
				add_settings_error( 'charts', 'import_success', $msg, 'success' );
			} else {
				add_settings_error( 'charts', 'import_success', sprintf( __( 'Import complete: %d entries.', 'charts' ), intval( $result ) ), 'success' );
			}
		} catch ( \Exception $e ) {
			add_settings_error( 'charts', 'exception', $e->getMessage(), 'error' );
		}
	}

	/**
	 * Process YouTube CSV Upload.
	 */
	private static function process_youtube_csv_upload() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		check_admin_referer( 'charts_admin_action' );

		if ( empty( $_FILES['youtube_csv']['tmp_name'] ) ) {
			add_settings_error( 'charts', 'no_file', __( 'Please select a CSV file to upload.', 'charts' ), 'error' );
			return;
		}

		$meta = array(
			'country'     => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'  => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'   => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? '' ),
			'source_name' => sanitize_text_field( $_POST['source_name'] ?? '' ),
		);

		$csv_content = file_get_contents( $_FILES['youtube_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			add_settings_error( 'charts', 'read_error', __( 'Failed to read CSV file.', 'charts' ), 'error' );
			return;
		}

		try {
			$importer = new \Charts\Services\YouTubeCsvImporter();
			$result   = $importer->run( $csv_content, $meta );

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'charts', 'import_error', $result->get_error_message(), 'error' );
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/charts/' );
				
				$msg = sprintf(
					__( 'YouTube import complete: <strong>%d entries saved</strong> from %d rows.', 'charts' ),
					$result['saved'],
					$result['parsed']
				);

				if ( ! empty( $result['extracted'] ) ) {
					$msg .= ' ' . sprintf( __( 'Extracted %d IDs from URLs.', 'charts' ), $result['extracted'] );
				}

				if ( ! empty( $result['enriched'] ) ) {
					$msg .= ' ' . sprintf( __( 'Enriched %d rows via API.', 'charts' ), $result['enriched'] );
				}

				if ( ! empty( $result['generated_thumbs'] ) ) {
					$msg .= ' ' . sprintf( __( 'Generated %d thumbnails.', 'charts' ), $result['generated_thumbs'] );
				}

				if ( ! empty( $result['missing_titles'] ) ) {
					$msg .= ' ' . sprintf( __( 'Warning: %d rows had missing titles.', 'charts' ), $result['missing_titles'] );
				}

				if ( ! empty( $result['skipped'] ) ) {
					$msg .= ' ' . sprintf( __( '%d rows skipped due to errors.', 'charts' ), $result['skipped'] );
				}

				$msg .= sprintf( ' <a href="%s" target="_blank">%s &rarr;</a>', esc_url( $chart_url ), __( 'View Charts', 'charts' ) );

				add_settings_error( 'charts', 'import_success', $msg, 'success' );

				if ( ! empty( $result['warnings'] ) ) {
					foreach ( $result['warnings'] as $warn ) {
						add_settings_error( 'charts', 'import_warning', $warn, 'warning' );
					}
				}
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

	public static function render_entities() {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			self::render_view( 'entity-edit' );
		} else {
			self::render_view( 'entities' );
		}
	}

	public static function render_insights() {
		self::render_view( 'insights' );
	}

	public static function render_intelligence() {
		self::render_view( 'intelligence' );
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

			// Recalculate Intelligence
			\Charts\Core\Intelligence::recalculate_all();

			wp_send_json_success( array( 
				'message' => sprintf( __( 'Successfully imported %d entries.', 'charts' ), $result ),
				'count'   => $result
			) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function handle_recalculate_intel() {
		check_ajax_referer( 'charts_intel', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'charts' ) ) );
		}

		try {
			\Charts\Core\Intelligence::recalculate_all();
			wp_send_json_success( array( 'message' => __( 'Intelligence recalculation successful.', 'charts' ) ) );
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
