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
		add_action( 'init', array( self::class, 'process_admin_actions' ) );
		
		// One-Time Migrations & Cleanup
		self::run_one_time_migrations();
		
		// AJAX Handlers
		add_action( 'wp_ajax_charts_run_import', array( self::class, 'handle_run_import' ) );
		add_action( 'wp_ajax_charts_recalculate_intel', array( self::class, 'handle_recalculate_intel' ) );
		add_action( 'wp_ajax_charts_sync_artists', array( self::class, 'handle_sync_artists' ) );
		add_action( 'wp_ajax_charts_sync_tracks', array( self::class, 'handle_sync_tracks' ) );
	}

	/**
	 * Handle one-time database migrations and legacy cleanup.
	 * Unified with charts.php versioning.
	 */
	private static function run_one_time_migrations() {
		// Consolidate migration tracking to a single source of truth
		$v = get_option( 'kcharts_db_version', '0.0.0' );
		
		if ( version_compare( $v, '1.8.0', '<' ) ) {
			$manager = new SourceManager();
			$manager->cleanup_mock_data();
			// Note: kcharts_db_version is updated by charts.php after this finishes
		}
	}

	/**
	 * Process POST actions for settings and imports.
	 * Works for both wp-admin and the external dashboard.
	 */
	public static function process_admin_actions() {
		if ( ! isset( $_POST['charts_action'] ) ) {
			return;
		}

		// Ensure user has capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Nonce check
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'charts_admin_action' ) ) {
			return;
		}

		$action = $_POST['charts_action'];
		$processed = false;

		switch ( $action ) {
			case 'save_settings':
				// Handle dynamic settings registration
				if ( isset( $_POST['charts_registered_fields'] ) ) {
					$fields = explode( ',', sanitize_text_field( $_POST['charts_registered_fields'] ) );
					$settings_to_update = [];

					foreach ( $fields as $field_def ) {
						$field_def = trim( $field_def );
						if ( empty( $field_def ) ) continue;

						$parts = explode( ':', $field_def );
						$type = 'text';
						$key = $parts[0];
						
						if ( count( $parts ) > 1 ) {
							$type = $parts[0];
							$key = $parts[1];
						}

						if ( empty( $key ) ) continue;

						$val = '';
						if ( $type === 'chk' ) {
							$val = isset( $_POST[ $key ] ) ? 1 : 0;
						} elseif ( $type === 'int' ) {
							$val = isset( $_POST[ $key ] ) ? intval( $_POST[ $key ] ) : 0;
						} elseif ( $type === 'flt' ) {
							$val = isset( $_POST[ $key ] ) ? floatval( $_POST[ $key ] ) : 0;
						} elseif ( $type === 'raw' || $type === 'textarea' ) {
							$val = isset( $_POST[ $key ] ) ? wp_kses_post( wp_unslash( $_POST[ $key ] ) ) : '';
						} elseif ( $type === 'med' ) {
							$val = isset( $_POST[ $key ] ) ? sanitize_text_field( $_POST[ $key ] ) : '';
						} elseif ( $type === 'slides' ) {
							$val = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '[]';
						} else {
							$posted_val = isset( $_POST[ $key ] ) ? $_POST[ $key ] : (isset($_POST['charts_'.$key]) ? $_POST['charts_'.$key] : '');
							
							// Handle manual font override if present
							if ( isset($_POST[$key . '_manual']) && !empty($_POST[$key . '_manual']) ) {
								$posted_val = $_POST[$key . '_manual'];
							}

							if ( is_array( $posted_val ) ) {
								$val = array_map( 'sanitize_text_field', wp_unslash( $posted_val ) );
							} else {
								$val = sanitize_text_field( wp_unslash( $posted_val ) );
							}
						}
						$settings_to_update[$key] = $val;
					}
					
					if ( !empty($settings_to_update) ) {
						\Charts\Core\Settings::update($settings_to_update);
					}
				}

				// Markets Management
				if ( isset( $_POST['markets'] ) && is_array( $_POST['markets'] ) ) {
					$markets = [];
					foreach ( $_POST['markets'] as $m ) {
						if ( empty($m['name']) || empty($m['code']) ) continue;
						$markets[] = [
							'name' => sanitize_text_field($m['name']),
							'code' => strtoupper(sanitize_text_field($m['code'])),
							'slug' => sanitize_title($m['slug'] ?: $m['name']),
						];
					}
					update_option( 'charts_markets', $markets );
				}

				add_settings_error( 'charts', 'settings_saved', __( 'Settings saved.', 'charts' ), 'success' );
				$processed = true;
				break;

			case 'save_source':
				$manager = new SourceManager();
				$result = $manager->save_source( $_POST );
				if ( $result ) {
					add_settings_error( 'charts', 'source_saved', __( 'Source saved successfully.', 'charts' ), 'success' );
				} else {
					add_settings_error( 'charts', 'source_error', __( 'Failed to save source.', 'charts' ), 'error' );
				}
				$processed = true;
				break;

			case 'delete_source':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_source( $id );
				add_settings_error( 'charts', 'source_deleted', __( 'Source deleted.', 'charts' ), 'success' );
				$processed = true;
				break;

			case 'import_spotify_csv':
				self::process_spotify_csv_upload();
				$processed = true;
				break;

			case 'import_youtube_csv':
				self::process_youtube_csv_upload();
				$processed = true;
				break;
			
			case 'unified_import':
				$run_id = self::process_unified_import();
				if ( is_numeric($run_id) ) {
					wp_redirect( admin_url( 'admin.php?page=charts-import&sync_complete=1&run_id=' . $run_id ) );
					exit;
				}
				$processed = true;
				break;
			
			case 'save_definition':
				$manager = new SourceManager();
				$result = $manager->save_definition( $_POST );
				if ( $result ) {
					add_settings_error( 'charts', 'def_saved', __( 'Chart definition saved.', 'charts' ), 'success' );
				} else {
					add_settings_error( 'charts', 'def_error', __( 'Failed to save chart definition.', 'charts' ), 'error' );
				}
				$processed = true;
				break;
			
			case 'delete_definition':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_definition( $id );
				add_settings_error( 'charts', 'def_deleted', __( 'Chart definition deleted.', 'charts' ), 'success' );
				$processed = true;
				break;

			case 'delete_entity':
				global $wpdb;
				$id    = intval( $_POST['id'] );
				$type  = sanitize_text_field( $_POST['type'] );
				self::delete_single_entity( $id, $type );
				add_settings_error( 'charts', 'entity_deleted', __( 'Entity deleted and relationships unlinked.', 'charts' ), 'success' );
				$processed = true;
				break;

			case 'bulk_action':
				global $wpdb;
				$action_type = sanitize_text_field( $_POST['bulk_action_type'] );
				$ids    = isset( $_POST['item_ids'] ) ? array_map( 'intval', $_POST['item_ids'] ) : array();
				$type   = sanitize_text_field( $_POST['entity_type'] );

				if ( empty( $ids ) ) {
					add_settings_error( 'charts', 'no_ids', __( 'No items selected.', 'charts' ), 'error' );
				} else if ( $action_type === 'delete' ) {
					foreach ( $ids as $id ) {
						self::delete_single_entity( $id, $type );
					}
					add_settings_error( 'charts', 'bulk_deleted', sprintf( __( '%d entities deleted successfully.', 'charts' ), count( $ids ) ), 'success' );
				}
				$processed = true;
				break;

			case 'run_integrity_check':
				\Charts\Core\Integrity::recalculate_entity_links();
				add_settings_error( 'charts', 'integrity_ran', __( 'Data integrity check complete. Unmatched entities have been reconciled.', 'charts' ), 'success' );
				$processed = true;
				break;

			case 'test_spotify_api':
				$client = new \Charts\Services\SpotifyApiClient();
				$result = $client->test_connection();
				
				if ( is_wp_error( $result ) ) {
					$msg = sprintf( __( 'Spotify API Test Failed: %s (%s)', 'charts' ), $result->get_error_message(), $result->get_error_code() );
					add_settings_error( 'charts', 'spotify_test_error', $msg, 'error' );
				} else {
					add_settings_error( 'charts', 'spotify_test_success', __( 'Spotify API Connection Successful! Token generated and metadata retrieved.', 'charts' ), 'success' );
				}
				$processed = true;
				break;

			case 'test_youtube_api':
				$client = new \Charts\Services\YouTubeApiClient();
				$result = $client->test_connection();

				if ( is_wp_error( $result ) ) {
					$msg = sprintf( __( 'YouTube API Test Failed: %s (%s)', 'charts' ), $result->get_error_message(), $result->get_error_code() );
					add_settings_error( 'charts', 'youtube_test_error', $msg, 'error' );
				} else {
					add_settings_error( 'charts', 'youtube_test_success', __( 'YouTube API Connection Successful! Metadata retrieved from video jNQXAC9IVRw.', 'charts' ), 'success' );
				}
				$processed = true;
				break;

			case 'backfill_media':
				$manager = new \Charts\Services\AssetManager();
				$results = $manager->backfill_all();
				
				$summary = sprintf( 
					__( 'Media backfill complete. Tracks: %d/%d updated. Artists: %d/%d updated. Videos: %d/%d updated.', 'charts' ),
					$results['tracks']['updated'], $results['tracks']['processed'],
					$results['artists']['updated'], $results['artists']['processed'],
					$results['videos']['updated'], $results['videos']['processed']
				);
				
				add_settings_error( 'charts', 'backfill_success', $summary, 'success' );
				$processed = true;
				break;

			case 'reset_plugin':
				$wipe_settings = isset( $_POST['wipe_settings'] ) ? (bool)$_POST['wipe_settings'] : false;
				self::wipe_all_data( $wipe_settings );

				add_settings_error( 'charts', 'reset_success', __( 'Plugin has been successfully reset to zero. All data has been purged.', 'charts' ), 'success' );
				$processed = true;
				break;
		}

		if ( $processed ) {
			self::clear_frontend_caches();
			
			// 1. Detect origin surface (admin vs external)
			// At 'init' hook, get_query_var isn't ready, so we check the URI or referer
			$referer = wp_get_referer();
			$is_external_surface = ( stripos( $_SERVER['REQUEST_URI'], '/charts-dashboard' ) !== false || ( $referer && stripos( $referer, '/charts-dashboard' ) !== false ) );
			
			// 2. Resolve target module based on action
			$module = 'overview';
			if ( strpos( $action, 'settings' ) !== false || strpos( $action, 'api' ) !== false || strpos( $action, 'media' ) !== false || strpos( $action, 'reset' ) !== false ) {
				$module = 'settings';
			} elseif ( strpos( $action, 'source' ) !== false ) {
				$module = 'sources';
			} elseif ( strpos( $action, 'definition' ) !== false || strpos( $action, 'entity' ) !== false ) {
				$module = 'definitions';
			} elseif ( strpos( $action, 'import' ) !== false || strpos( $action, 'run' ) !== false ) {
				$module = 'import';
			} elseif ( strpos( $action, 'intel' ) !== false ) {
				$module = 'intelligence';
			} elseif ( strpos( $action, 'match' ) !== false || strpos( $action, 'integrity' ) !== false ) {
				$module = 'matching';
			}

			// 3. Construct target URL
			// We prioritize the referer IF it matches our surface, otherwise we use the clean module URL
			$target_url = '';
			if ( $referer && ! ( stripos( $referer, '/charts/' ) !== false && stripos( $referer, '/charts-dashboard' ) === false ) ) {
				// Referer is safe (it's either admin or dashboard)
				$target_url = $referer;
			} else {
				// Fallback to clean module URL
				$target_url = \Charts\Core\Router::get_dashboard_url( $module );
				
				// Ensure surface consistency in fallback
				if ( $is_external_surface && stripos( $target_url, '/wp-admin/' ) !== false ) {
					$target_url = home_url( '/charts-dashboard/' . $module . '/' );
				}
			}

			// 4. Append persistent notices if necessary
			if ( $action === 'save_settings' ) {
				$target_url = add_query_arg( 'settings-updated', '1', $target_url );
			}

			wp_safe_redirect( $target_url );
			exit;
		}
	}

	/**
	 * Helper to get the base charts admin URL.
	 */
	private static function get_charts_admin_url() {
		return admin_url( 'admin.php?page=charts-dashboard' );
	}

	/**
	 * Deletes a single entity and cleans up relationships safely.
	 */
	private static function delete_single_entity( $id, $type ) {
		global $wpdb;
		$suffix = ( $type === 'artist' ) ? 'artists' : ( ( $type === 'track' ) ? 'tracks' : 'videos' );
		$table  = $wpdb->prefix . 'charts_' . $suffix;
		
		// 1. Delete the canonical metadata
		$wpdb->delete( $table, array( 'id' => $id ) );
		
		// 2. Prevent orphaned relationships in historical entries
		$wpdb->update( 
			$wpdb->prefix . 'charts_entries', 
			array( 'item_id' => 0 ), 
			array( 'item_id' => $id, 'item_type' => $type ) 
		);
		
		delete_transient( 'charts_intel_last_calc' );
	}

	/**
	 * Register the main admin menu and submenus.
	 * Ensures the admin menu remains internal to wp-admin.
	 */
	public static function register_menu() {
		$icon = 'dashicons-chart-bar';

		add_menu_page(
			__( 'Charts', 'charts' ),
			__( 'Charts', 'charts' ),
			'manage_options',
			'charts-dashboard',
			array( self::class, 'render_dashboard' ),
			$icon,
			3
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Overview', 'charts' ),
			__( 'Overview', 'charts' ),
			'manage_options',
			'charts-dashboard',
			array( self::class, 'render_dashboard' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Manage Charts', 'charts' ),
			__( 'Manage Charts', 'charts' ),
			'manage_options',
			'charts-definitions',
			array( self::class, 'render_definitions' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Sources', 'charts' ),
			__( 'Sources', 'charts' ),
			'manage_options',
			'charts-sources',
			array( self::class, 'render_sources' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Import Center', 'charts' ),
			__( 'Import Center', 'charts' ),
			'manage_options',
			'charts-import',
			array( self::class, 'render_import_center' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Import Runs', 'charts' ),
			__( 'Import Runs', 'charts' ),
			'manage_options',
			'charts-imports',
			array( self::class, 'render_imports' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Matching Center', 'charts' ),
			__( 'Matching Center', 'charts' ),
			'manage_options',
			'charts-matching',
			array( self::class, 'render_matching' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Artists', 'charts' ),
			__( 'Artists', 'charts' ),
			'manage_options',
			'charts-artists',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Tracks', 'charts' ),
			__( 'Tracks', 'charts' ),
			'manage_options',
			'charts-tracks',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Clips', 'charts' ),
			__( 'Clips', 'charts' ),
			'manage_options',
			'charts-clips',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Metadata Center', 'charts' ),
			__( 'Advanced Entities', 'charts' ),
			'manage_options',
			'charts-entities',
			array( self::class, 'render_entities' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Intelligence', 'charts' ),
			__( 'Intelligence', 'charts' ),
			'manage_options',
			'charts-intelligence',
			array( self::class, 'render_intelligence' )
		);

		add_submenu_page(
			'charts-dashboard',
			__( 'Insights', 'charts' ),
			__( 'Insights', 'charts' ),
			'manage_options',
			'charts-insights',
			array( self::class, 'render_insights' )
		);

		add_submenu_page(
			'charts-dashboard',
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
			'nonce'    => wp_create_nonce( 'charts_admin_action' ),
		) );

		wp_localize_script( 'charts-admin', 'kcharts_theme_options', \Charts\Core\Settings::get_defaults() );
	}

	/**
	 * Render the Dashboard.
	 */
	public static function render_dashboard() {
		global $wpdb;

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

		self::render_view( 'dashboard', $stats );
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
			return false;
		}

		// Inject the correct file name into the expected $_FILES location for compatibility
		if ( $platform === 'spotify' ) {
			$_FILES['spotify_csv'] = $_FILES['import_file'];
			return self::process_spotify_csv_upload();
		} else {
			$_FILES['youtube_csv'] = $_FILES['import_file'];
			return self::process_youtube_csv_upload();
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
			'country'     => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'  => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'   => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? '' ),
			'source_name' => sanitize_text_field( $_POST['source_name'] ?? '' ),
			'item_type'   => sanitize_text_field( $_POST['item_type'] ?? 'track' ),
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
				return false;
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/charts/spotify/' . rawurlencode( $meta['country'] ) . '/' . rawurlencode( $meta['frequency'] ) . '/' . rawurlencode( $meta['chart_type'] ) . '/' );
				$msg = sprintf( __( 'Import complete: %1$d entries saved from %2$d rows. Source ID: %3$d, Period ID: %4$d. %5$d skipped. <a href="%6$s" target="_blank">View Chart</a>', 'charts' ), $result['saved'], $result['parsed'], $result['source_id'], $result['period_id'], $result['skipped'], esc_url( $chart_url ) );
				add_settings_error( 'charts', 'import_success', $msg, 'success' );
				return $result['run_id'] ?? true;
			} else {
				add_settings_error( 'charts', 'import_success', sprintf( __( 'Import complete: %d entries.', 'charts' ), intval( $result ) ), 'success' );
				return true;
			}
		} catch ( \Exception $e ) {
			add_settings_error( 'charts', 'exception', $e->getMessage(), 'error' );
			return false;
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
			'item_type'   => sanitize_text_field( $_POST['item_type'] ?? 'track' ),
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
					__( 'YouTube import complete: <strong>%d entries saved</strong> from %d rows. (%d matched, %d created).', 'charts' ),
					$result['saved'],
					$result['parsed'],
					$result['matched'],
					$result['created']
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
				return $result['run_id'] ?? true;
			}
			return false;
		} catch ( \Exception $e ) {
			add_settings_error( 'charts', 'exception', $e->getMessage(), 'error' );
			return false;
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

	public static function render_matching() {
		self::render_view( 'matching' );
	}

	/**
	 * AJAX logic to run an import.
	 */
	public static function handle_run_import() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'charts' ) ) );
		}

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

			self::clear_frontend_caches();

			wp_send_json_success( array( 
				'message' => sprintf( __( 'Successfully imported %d entries.', 'charts' ), $result ),
				'count'   => $result
			) );

		} catch ( \Exception $e ) {
			error_log( 'Charts Import Error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function handle_recalculate_intel() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'charts' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'charts' ) ) );
		}

		try {
			\Charts\Core\Intelligence::recalculate_all();
			self::clear_frontend_caches();
			wp_send_json_success( array( 'message' => __( 'Intelligence recalculation successful.', 'charts' ) ) );
		} catch ( \Exception $e ) {
			error_log( 'Charts Recalculate Error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function handle_sync_artists() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );

		global $wpdb;
		$limit = 20;
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$mode   = $_POST['mode'] ?? 'missing';
		$ids    = isset($_POST['ids']) ? array_map('intval', explode(',', $_POST['ids'])) : [];

		$table  = $wpdb->prefix . 'charts_artists';
		$where  = "WHERE 1=1";
		
		if ( $mode === 'selected' && !empty($ids) ) {
			$ids_str = implode(',', $ids);
			$where .= " AND id IN ($ids_str)";
		} elseif ( $mode === 'missing' ) {
			$where .= " AND (spotify_id IS NULL OR spotify_id = '' OR image IS NULL OR image = '')";
		}

		$artists = $wpdb->get_results( "SELECT id, display_name, spotify_id, metadata_json FROM $table $where ORDER BY id ASC LIMIT $limit" );
		
		if ( empty($artists) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
		$spotify_client = new \Charts\Services\SpotifyApiClient();
		$youtube_service = new \Charts\Services\YouTubeEnrichmentService();
		$youtube_client = new \Charts\Services\YouTubeApiClient();

		$updated = 0;
		$spotify_linked = 0;
		$youtube_linked = 0;

		foreach ( $artists as $artist ) {
			$has_update = false;
			$meta = json_decode($artist->metadata_json, true) ?: [];

			// 1. Spotify Logic
			if ( empty($artist->spotify_id) ) {
				$results = $spotify_client->search_artist($artist->display_name, 1);
				if ( !empty($results) && !is_wp_error($results) ) {
					$wpdb->update($table, ['spotify_id' => $results[0]['id']], ['id' => $artist->id]);
					$artist->spotify_id = $results[0]['id'];
					$spotify_linked++;
					$has_update = true;
				}
			}

			if ( !empty($artist->spotify_id) ) {
				$spotify_service->enrich_artist($artist->id);
				$has_update = true;
			}

			// 2. YouTube Logic
			$channel_id = $meta['youtube_channel_id'] ?? null;
			if ( empty($channel_id) ) {
				$results = $youtube_client->search_channels($artist->display_name, 1);
				if ( !empty($results) && !is_wp_error($results) ) {
					$channel_id = $results[0]['id']['channelId'] ?? null;
					if ( $channel_id ) {
						$meta['youtube_channel_id'] = $channel_id;
						$wpdb->update($table, ['metadata_json' => json_encode($meta)], ['id' => $artist->id]);
						$youtube_linked++;
						$has_update = true;
					}
				}
			}

			if ( !empty($channel_id) ) {
				$youtube_service->enrich_artist($artist->id);
				$has_update = true;
			}

			if ($has_update) $updated++;
		}

		wp_send_json_success( array(
			'complete' => false,
			'processed' => count($artists),
			'updated' => $updated,
			'spotify_linked' => $spotify_linked,
			'youtube_linked' => $youtube_linked,
			'next_offset' => $offset + count($artists)
		) );
	}

	public static function handle_sync_tracks() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );

		global $wpdb;
		$limit = 20;
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$mode   = $_POST['mode'] ?? 'missing';
		$ids    = isset($_POST['ids']) ? array_map('intval', explode(',', $_POST['ids'])) : [];

		$table_tracks = $wpdb->prefix . 'charts_tracks';
		$table_artists = $wpdb->prefix . 'charts_artists';
		
		$where = "WHERE 1=1";
		if ( $mode === 'selected' && !empty($ids) ) {
			$ids_str = implode(',', $ids);
			$where .= " AND t.id IN ($ids_str)";
		} elseif ( $mode === 'missing' ) {
			$where .= " AND (t.spotify_id IS NULL OR t.spotify_id = '' OR t.cover_image IS NULL OR t.cover_image = '')";
		}
		
		$tracks = $wpdb->get_results( "
			SELECT t.id, t.title, t.spotify_id, a.display_name as artist_name 
			FROM $table_tracks t 
			LEFT JOIN $table_artists a ON a.id = t.primary_artist_id 
			$where 
			ORDER BY t.id ASC LIMIT $limit
		" );
		
		if ( empty($tracks) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
		$spotify_client = new \Charts\Services\SpotifyApiClient();

		$updated = 0;
		$spotify_linked = 0;
		$covers_updated = 0;

		foreach ( $tracks as $track ) {
			$has_update = false;

			// 1. Spotify Logic
			if ( empty($track->spotify_id) ) {
				$query = $track->title . ' ' . ($track->artist_name ?: '');
				$results = $spotify_client->search_track($query, 1);
				if ( !empty($results) && !is_wp_error($results) ) {
					$wpdb->update($table_tracks, ['spotify_id' => $results[0]['id']], ['id' => $track->id]);
					$track->spotify_id = $results[0]['id'];
					$spotify_linked++;
					$has_update = true;
				}
			}

			if ( !empty($track->spotify_id) ) {
				$spotify_service->enrich_track($track->id);
				$has_update = true;
				$covers_updated++;
			}

			if ($has_update) $updated++;
		}

		wp_send_json_success( array(
			'complete' => false,
			'processed' => count($tracks),
			'updated' => $updated,
			'spotify_linked' => $spotify_linked,
			'covers_updated' => $covers_updated,
			'next_offset' => $offset + count($tracks)
		) );
	}

	/**
	 * Render the Settings.
	 */
	public static function render_settings() {
		self::render_view( 'settings' );
	}

	/**
	 * Force clear all frontend-related transients to ensure data parity.
	 */
	public static function clear_frontend_caches() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_kc_preview_%' OR option_name LIKE '_transient_timeout_kc_preview_%'" );
	}

	/**
	 * DESTRUCTIVE: Wipes all plugin data from the database.
	 */
	private static function wipe_all_data( $wipe_settings = false ) {
		global $wpdb;

		// 1. Tables to truncate
		$tables = array(
			'charts_sources',
			'charts_periods',
			'charts_artists',
			'charts_albums',
			'charts_tracks',
			'charts_videos',
			'charts_track_artists',
			'charts_video_artists',
			'charts_entries',
			'charts_aliases',
			'charts_import_runs',
			'charts_insights',
			'charts_definitions',
			'charts_intelligence',
		);

		foreach ( $tables as $table ) {
			$fullname = $wpdb->prefix . $table;
			$wpdb->query( "TRUNCATE TABLE `$fullname`" );
		}

		// 2. Clear Transients
		self::clear_frontend_caches();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_kc_%' OR option_name LIKE '_transient_timeout_kc_%'" );

		// 3. Clear Settings (Optional)
		if ( $wipe_settings ) {
			$options = array(
				'charts_spotify_client_id',
				'charts_spotify_client_secret',
				'charts_youtube_api_key',
				'charts_logo_id_light',
				'charts_logo_id_dark',
				'charts_logo_alt',
				'charts_wordmark',
				'charts_show_logo',
				'charts_show_nav',
				'charts_show_search',
				'charts_header_menu_id',
				'charts_footer_description',
				'charts_footer_copyright',
				'charts_theme_mode',
				'charts_slider_enable',
				'charts_slider_style',
				'charts_slider_count',
				'charts_slider_loop',
				'charts_slider_autoplay',
				'charts_slider_delay',
				'charts_slider_arrows',
				'charts_slider_pagination',
				'charts_slider_swipe',
				'charts_slider_keyboard',
				'charts_slider_speed',
				'charts_slider_easing',
				'charts_slider_center',
				'charts_slider_depth',
				'charts_slider_rotation',
				'charts_slider_opacity',
				'charts_slider_scale',
				'charts_slider_spacing',
				'charts_slider_shadow',
				'charts_slider_glow',
				'charts_slider_max_width',
				'charts_slider_min_height',
				'charts_slider_aspect_ratio',
				'charts_slider_align',
				'charts_slider_overlay',
				'charts_slider_radius',
				'charts_slider_mobile_mode',
				'charts_slider_show_label',
				'charts_slider_show_meta',
				'charts_slider_show_cta',
				'charts_slider_cta_text',
				'charts_homepage_layout',
				'charts_homepage_section_order',
				'charts_homepage_show_more',
				'charts_homepage_show_featured',
				'charts_homepage_show_artists',
				'charts_homepage_show_tracks',
				'charts_slider_source_mode',
				'charts_slider_manual_slides',
				'charts_color_primary',
				'charts_color_bg_light',
				'charts_color_bg_dark',
				'charts_font_heading',
				'charts_font_body',
				'charts_seo_title_suffix',
				'kcharts_db_version',
			);
			foreach ( $options as $opt ) {
				delete_option( $opt );
			}
		}

		// Ensure we trigger a re-setup if needed
		delete_option( 'charts_setup_complete' );
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
