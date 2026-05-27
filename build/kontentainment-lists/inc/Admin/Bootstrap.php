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
		add_action( 'wp_ajax_charts_soundcharts_test_connection', array( self::class, 'handle_soundcharts_test_connection' ) );
		add_action( 'wp_ajax_charts_soundcharts_preview_import', array( self::class, 'handle_soundcharts_preview_import' ) );
		add_action( 'wp_ajax_charts_soundcharts_run_now', array( self::class, 'handle_soundcharts_run_now' ) );
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
				foreach ( \Charts\Services\SoundchartsSettings::sanitize_posted_settings( $_POST ) as $option_key => $option_value ) {
					update_option( $option_key, $option_value );
				}

				// Standalone Layout & Shell Settings
				update_option( 'charts_standalone_layout', isset( $_POST['standalone_layout'] ) ? 1 : 0 );
				update_option( 'charts_custom_header', isset( $_POST['custom_header'] ) ? 1 : 0 );
				update_option( 'charts_custom_footer', isset( $_POST['custom_footer'] ) ? 1 : 0 );
				
				// Branding
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
				update_option( 'lists_editor_pick_slugs', sanitize_text_field( $_POST['lists_editor_pick_slugs'] ?? '' ) );
				update_option( 'lists_featured_artist_slugs', sanitize_text_field( $_POST['lists_featured_artist_slugs'] ?? '' ) );
				update_option( 'lists_featured_list_slugs', sanitize_text_field( $_POST['lists_featured_list_slugs'] ?? '' ) );

				add_settings_error( 'kontentainment-lists', 'settings_saved', __( 'Settings saved.', 'kontentainment-lists' ), 'success' );
				break;

			case 'save_source':
				$manager = new SourceManager();
				$result = $manager->save_source( $_POST );
				if ( $result ) {
					add_settings_error( 'kontentainment-lists', 'source_saved', __( 'Source saved successfully.', 'kontentainment-lists' ), 'success' );
				} else {
					add_settings_error( 'kontentainment-lists', 'source_error', __( 'Failed to save source.', 'kontentainment-lists' ), 'error' );
				}
				break;

			case 'delete_source':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_source( $id );
				add_settings_error( 'kontentainment-lists', 'source_deleted', __( 'Source deleted.', 'kontentainment-lists' ), 'success' );
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
					add_settings_error( 'kontentainment-lists', 'def_saved', __( 'List definition saved.', 'kontentainment-lists' ), 'success' );
				} else {
					add_settings_error( 'kontentainment-lists', 'def_error', __( 'Failed to save list definition.', 'kontentainment-lists' ), 'error' );
				}
				break;
			
			case 'delete_definition':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_definition( $id );
				add_settings_error( 'kontentainment-lists', 'def_deleted', __( 'List definition deleted.', 'kontentainment-lists' ), 'success' );
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
				add_settings_error( 'kontentainment-lists', 'entity_deleted', __( 'Entity deleted and relationships unlinked.', 'kontentainment-lists' ), 'success' );
				break;
		}
	}

	/**
	 * Register the main admin menu and submenus.
	 */
	public static function register_menu() {
		$icon = 'dashicons-chart-bar';

		add_menu_page(
			__( 'Lists', 'kontentainment-lists' ),
			__( 'Lists', 'kontentainment-lists' ),
			'manage_options',
			'charts-dashboard',
			array( self::class, 'render_dashboard' ),
			$icon,
			30
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Manage Lists', 'kontentainment-lists' ),
			__( 'Manage Lists', 'kontentainment-lists' ),
			'manage_options',
			'charts-definitions',
			array( self::class, 'render_definitions' )
		);

		add_submenu_page(
			'charts-dashboard',
				__( 'Overview', 'kontentainment-lists' ),
			__( 'Overview', 'kontentainment-lists' ),
			'manage_options',
			'charts-dashboard',
			array( self::class, 'render_dashboard' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Sources', 'kontentainment-lists' ),
			__( 'Sources', 'kontentainment-lists' ),
			'manage_options',
			'charts-sources',
			array( self::class, 'render_sources' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Import Center', 'kontentainment-lists' ),
			__( 'Import Center', 'kontentainment-lists' ),
			'manage_options',
			'charts-import',
			array( self::class, 'render_import_center' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Import Runs', 'kontentainment-lists' ),
			__( 'Import Runs', 'kontentainment-lists' ),
			'manage_options',
			'charts-imports',
			array( self::class, 'render_imports' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Matching Center', 'kontentainment-lists' ),
			__( 'Matching Center', 'kontentainment-lists' ),
			'manage_options',
			'charts-matching',
			array( self::class, 'render_matching' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Artists', 'kontentainment-lists' ),
			__( 'Artists', 'kontentainment-lists' ),
			'manage_options',
			'charts-artists',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Tracks', 'kontentainment-lists' ),
			__( 'Tracks', 'kontentainment-lists' ),
			'manage_options',
			'charts-tracks',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Metadata Center', 'kontentainment-lists' ),
			__( 'Advanced Entities', 'kontentainment-lists' ),
			'manage_options',
			'charts-entities',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Intelligence', 'kontentainment-lists' ),
			__( 'Intelligence', 'kontentainment-lists' ),
			'manage_options',
			'charts-intelligence',
			array( self::class, 'render_intelligence' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Insights', 'kontentainment-lists' ),
			__( 'Insights', 'kontentainment-lists' ),
			'manage_options',
			'charts-insights',
			array( self::class, 'render_insights' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Lists Settings', 'kontentainment-lists' ),
			__( 'Settings', 'kontentainment-lists' ),
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
		if ( strpos( $hook, 'kontentainment-lists' ) === false && strpos( $hook, 'charts' ) === false ) {
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

		if ( $platform === 'soundcharts' ) {
			self::process_soundcharts_import();
			return;
		}

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			add_settings_error( 'kontentainment-lists', 'no_file', __( 'Please select a data file to upload.', 'kontentainment-lists' ), 'error' );
			return;
		}

		$_POST['country'] = sanitize_text_field( $_POST['country'] ?? 'eg' );

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
	 * Process Soundcharts API import.
	 */
	private static function process_soundcharts_import() {
		$meta = array(
			'chart_id'      => intval( $_POST['chart_id'] ?? 0 ),
			'country'       => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'    => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'     => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date'   => sanitize_text_field( $_POST['period_date'] ?? current_time( 'Y-m-d' ) ),
			'source_name'   => sanitize_text_field( $_POST['source_name'] ?? '' ),
			'preset_key'    => sanitize_text_field( $_POST['preset_key'] ?? '' ),
			'dry_run'       => isset( $_POST['dry_run'] ) ? 1 : 0,
		);

		try {
			$importer = new \Charts\Services\SoundchartsImporter();
			$result   = $importer->run( $meta );

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'kontentainment-lists', 'soundcharts_import_error', $result->get_error_message(), 'error' );
				return;
			}

			if ( empty( $result['dry_run'] ) ) {
				\Charts\Core\Intelligence::recalculate_all();
			}

			$message = sprintf(
				__( 'Soundcharts %6$s completed: %1$d new rows, %2$d updated entries, %3$d artists created, %4$d tracks created, %5$d errors.', 'kontentainment-lists' ),
				(int) $result['imported_rows'],
				(int) $result['updated_entries'],
				(int) $result['created_artists'],
				(int) $result['created_tracks'],
				(int) $result['errors'],
				! empty( $result['dry_run'] ) ? __( 'dry run', 'kontentainment-lists' ) : __( 'import', 'kontentainment-lists' )
			);
			add_settings_error( 'kontentainment-lists', 'soundcharts_import_success', $message, 'success' );
		} catch ( \Exception $e ) {
			add_settings_error( 'kontentainment-lists', 'soundcharts_import_exception', $e->getMessage(), 'error' );
		}
	}

	/**
	 * Process Spotify CSV Upload.
	 */
	private static function process_spotify_csv_upload() {
		if ( empty( $_FILES['spotify_csv']['tmp_name'] ) ) {
			add_settings_error( 'kontentainment-lists', 'no_file', __( 'Please select a CSV file.', 'kontentainment-lists' ), 'error' );
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
			add_settings_error( 'kontentainment-lists', 'read_error', __( 'Failed to read CSV file.', 'kontentainment-lists' ), 'error' );
			return;
		}

		try {
			$importer = new \Charts\Services\SpotifyCsvImporter();
			$result   = $importer->run( $csv_content, $meta );
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'kontentainment-lists', 'import_error', $result->get_error_message(), 'error' );
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/lists/spotify/' . rawurlencode( $meta['country'] ) . '/' . rawurlencode( $meta['frequency'] ) . '/' . rawurlencode( $meta['chart_type'] ) . '/' );
				$msg = sprintf( __( 'Import complete: %1$d entries saved from %2$d rows. Source ID: %3$d, Period ID: %4$d. %5$d skipped. <a href="%6$s" target="_blank">View List</a>', 'kontentainment-lists' ), $result['saved'], $result['parsed'], $result['source_id'], $result['period_id'], $result['skipped'], esc_url( $chart_url ) );
				add_settings_error( 'kontentainment-lists', 'import_success', $msg, 'success' );
			} else {
				add_settings_error( 'kontentainment-lists', 'import_success', sprintf( __( 'Import complete: %d entries.', 'kontentainment-lists' ), intval( $result ) ), 'success' );
			}
		} catch ( \Exception $e ) {
			add_settings_error( 'kontentainment-lists', 'exception', $e->getMessage(), 'error' );
		}
	}

	/**
	 * Process YouTube CSV Upload.
	 */
	private static function process_youtube_csv_upload() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		check_admin_referer( 'charts_admin_action' );

		if ( empty( $_FILES['youtube_csv']['tmp_name'] ) ) {
			add_settings_error( 'kontentainment-lists', 'no_file', __( 'Please select a CSV file to upload.', 'kontentainment-lists' ), 'error' );
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
			add_settings_error( 'kontentainment-lists', 'read_error', __( 'Failed to read CSV file.', 'kontentainment-lists' ), 'error' );
			return;
		}

		try {
			$importer = new \Charts\Services\YouTubeCsvImporter();
			$result   = $importer->run( $csv_content, $meta );

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'kontentainment-lists', 'import_error', $result->get_error_message(), 'error' );
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/lists/' );
				
				$msg = sprintf(
					__( 'YouTube import complete: <strong>%d entries saved</strong> from %d rows.', 'kontentainment-lists' ),
					$result['saved'],
					$result['parsed']
				);

				if ( ! empty( $result['extracted'] ) ) {
					$msg .= ' ' . sprintf( __( 'Extracted %d IDs from URLs.', 'kontentainment-lists' ), $result['extracted'] );
				}

				if ( ! empty( $result['enriched'] ) ) {
					$msg .= ' ' . sprintf( __( 'Enriched %d rows via API.', 'kontentainment-lists' ), $result['enriched'] );
				}

				if ( ! empty( $result['generated_thumbs'] ) ) {
					$msg .= ' ' . sprintf( __( 'Generated %d thumbnails.', 'kontentainment-lists' ), $result['generated_thumbs'] );
				}

				if ( ! empty( $result['missing_titles'] ) ) {
					$msg .= ' ' . sprintf( __( 'Warning: %d rows had missing titles.', 'kontentainment-lists' ), $result['missing_titles'] );
				}

				if ( ! empty( $result['skipped'] ) ) {
					$msg .= ' ' . sprintf( __( '%d rows skipped due to errors.', 'kontentainment-lists' ), $result['skipped'] );
				}

				$msg .= sprintf( ' <a href="%s" target="_blank">%s &rarr;</a>', esc_url( $chart_url ), __( 'View Lists', 'kontentainment-lists' ) );

				add_settings_error( 'kontentainment-lists', 'import_success', $msg, 'success' );

				if ( ! empty( $result['warnings'] ) ) {
					foreach ( $result['warnings'] as $warn ) {
						add_settings_error( 'kontentainment-lists', 'import_warning', $warn, 'warning' );
					}
				}
			}
		} catch ( \Exception $e ) {
			add_settings_error( 'kontentainment-lists', 'exception', $e->getMessage(), 'error' );
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
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'kontentainment-lists' ) ) );
		}

		$source_id = isset( $_POST['source_id'] ) ? intval( $_POST['source_id'] ) : 0;
		if ( ! $source_id ) {
			wp_send_json_error( array( 'message' => __( 'Source ID missing.', 'kontentainment-lists' ) ) );
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
				'message' => sprintf( __( 'Successfully imported %d entries.', 'kontentainment-lists' ), $result ),
				'count'   => $result
			) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function handle_recalculate_intel() {
		check_ajax_referer( 'charts_intel', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'kontentainment-lists' ) ) );
		}

		try {
			\Charts\Core\Intelligence::recalculate_all();
			wp_send_json_success( array( 'message' => __( 'Intelligence recalculation successful.', 'kontentainment-lists' ) ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: test Soundcharts connection.
	 */
	public static function handle_soundcharts_test_connection() {
		check_ajax_referer( 'charts_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'kontentainment-lists' ) ) );
		}

		$current_settings = \Charts\Services\SoundchartsSettings::get();
		$overrides = array(
			'app_id'         => sanitize_text_field( $_POST['app_id'] ?? '' ),
			'api_key'        => sanitize_text_field( $_POST['api_key'] ?? '' ),
			'mode'           => sanitize_text_field( $_POST['mode'] ?? 'production' ),
			'timeout'        => max( 5, (int) ( $_POST['timeout'] ?? 20 ) ),
			'enable_logging' => isset( $_POST['enable_logging'] ) ? (bool) $_POST['enable_logging'] : true,
		);
		if ( empty( $overrides['app_id'] ) ) {
			$overrides['app_id'] = $current_settings['app_id'];
		}
		if ( empty( $overrides['api_key'] ) ) {
			$overrides['api_key'] = $current_settings['api_key'];
		}

		$result = ( new \Charts\Services\SoundchartsApiClient( $overrides ) )->test_connection();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: preview Soundcharts import payload.
	 */
	public static function handle_soundcharts_preview_import() {
		check_ajax_referer( 'charts_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'kontentainment-lists' ) ) );
		}

		$meta = array(
			'chart_id'      => intval( $_POST['chart_id'] ?? 0 ),
			'country'       => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'    => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'     => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date'   => sanitize_text_field( $_POST['period_date'] ?? current_time( 'Y-m-d' ) ),
			'preset_key'    => sanitize_text_field( $_POST['preset_key'] ?? '' ),
		);

		$result = ( new \Charts\Services\SoundchartsImporter() )->preview( $meta );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: manually trigger a chart auto-sync target.
	 */
	public static function handle_soundcharts_run_now() {
		check_ajax_referer( 'charts_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'kontentainment-lists' ) ) );
		}

		$chart_id = intval( $_POST['chart_id'] ?? 0 );
		$definition = ( new SourceManager() )->get_definition( $chart_id );
		if ( ! $definition ) {
			wp_send_json_error( array( 'message' => __( 'List definition not found.', 'kontentainment-lists' ) ) );
		}

		$settings = ! empty( $definition->display_settings_json ) ? json_decode( $definition->display_settings_json, true ) : array();
		$meta = array(
			'chart_id'    => $definition->id,
			'country'     => $definition->country_code,
			'chart_type'  => $definition->chart_type,
			'frequency'   => $definition->frequency,
			'period_date' => current_time( 'Y-m-d' ),
			'preset_key'  => $settings['soundcharts_preset'] ?? '',
			'source_name' => $definition->title . ' Manual Sync',
		);

		$result = ( new \Charts\Services\SoundchartsImporter() )->run( $meta );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'List sync completed.', 'kontentainment-lists' ),
			'result'  => $result,
		) );
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
			echo '<div class="wrap"><div class="notice notice-error"><p>' . sprintf( __( 'Critical: View file not found: %s', 'kontentainment-lists' ), esc_html( $name ) ) . '</p></div></div>';
			return;
		}

		if ( ! empty( $data ) ) {
			extract( $data );
		}

		include $file;
	}
}
