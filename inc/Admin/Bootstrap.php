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
		add_action( 'admin_notices', array( \Charts\Core\Notify::class, 'display_admin_notices' ) );
		
		// One-Time Migrations & Cleanup
		self::run_one_time_migrations();
		
		// AJAX Handlers
		add_action( 'wp_ajax_charts_run_import', array( self::class, 'handle_run_import' ) );
		add_action( 'wp_ajax_charts_recalculate_intel', array( self::class, 'handle_recalculate_intel' ) );
		add_action( 'wp_ajax_charts_sync_artists', array( self::class, 'handle_sync_artists' ) );
		add_action( 'wp_ajax_charts_sync_tracks', array( self::class, 'handle_sync_tracks' ) );
		add_action( 'wp_ajax_charts_sync_videos', array( self::class, 'handle_sync_videos' ) );
		add_action( 'wp_ajax_charts_sync_albums', array( self::class, 'handle_sync_albums' ) );
		add_action( 'wp_ajax_charts_migration_step', array( self::class, 'handle_migration_step' ) );
		add_action( 'wp_ajax_charts_search_entities', array( self::class, 'handle_search_entities' ) );
		add_action( 'wp_ajax_charts_manage_manual_row', array( self::class, 'handle_manage_manual_row' ) );
		add_action( 'wp_ajax_charts_save_manual_order', array( self::class, 'handle_save_manual_order' ) );
		add_action( 'wp_ajax_charts_process_merge', array( self::class, 'handle_process_merge' ) );
		add_action( 'wp_ajax_charts_save_matching_id', array( self::class, 'handle_save_matching_id' ) );
		add_action( 'wp_ajax_charts_search_spotify_matching', array( self::class, 'handle_search_spotify_matching' ) );
		add_action( 'wp_ajax_charts_export_intelligence', array( self::class, 'handle_export_intelligence' ) );
		add_action( 'wp_ajax_charts_scan_duplicates', array( self::class, 'handle_scan_duplicates' ) );
		add_action( 'wp_ajax_charts_resolve_potential_duplicates', array( self::class, 'handle_resolve_potential_duplicates' ) );
		add_action( 'wp_ajax_charts_bulk_action_ajax', array( self::class, 'handle_bulk_action_ajax' ) );
		add_action( 'wp_ajax_charts_auto_reconcile', array( self::class, 'handle_auto_reconcile' ) );
		add_action( 'wp_ajax_charts_force_english_slugs', array( self::class, 'handle_force_english_slugs' ) );
		add_action( 'wp_ajax_charts_update_artist_identity', array( self::class, 'handle_update_artist_identity' ) );
		add_action( 'wp_ajax_kc_recalculate_forecast', array( self::class, 'handle_recalculate_forecast' ) );
		add_action( 'wp_ajax_charts_billboard_sync', array( self::class, 'handle_billboard_sync' ) );
		add_action( 'wp_ajax_charts_billboard_download_csv', array( self::class, 'handle_billboard_download_csv' ) );
		add_action( 'wp_ajax_charts_billboard_get_weeks', array( self::class, 'handle_billboard_get_weeks' ) );
		add_action( 'wp_ajax_charts_soundcharts_catalog', array( self::class, 'handle_soundcharts_catalog' ) );
		add_action( 'wp_ajax_charts_youtube_sync', array( self::class, 'handle_youtube_sync' ) );
		
		// Nav Menu Integration
		add_action( 'admin_init', array( self::class, 'register_nav_menu_metabox' ) );
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
		if ( ! isset( $_REQUEST['charts_action'] ) ) {
			return;
		}

		// Ensure user has capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Unified Nonce & Integrity Check
		$nonce = $_POST['_wpnonce'] ?? ($_REQUEST['_wpnonce'] ?? '');
		$action = $_REQUEST['charts_action'] ?? '';

		// Verify Nonce against expected contexts
		$is_valid_v2 = wp_verify_nonce( $nonce, 'kcharts_save_v2' );
		$is_valid_v1 = wp_verify_nonce( $nonce, 'charts_admin_action' );

		if ( ! $is_valid_v1 && ! $is_valid_v2 ) {
			if ( defined('WP_DEBUG') && WP_DEBUG ) {
				error_log("Charts Sync Failure: Invalid Nonce for action=$action. Provided Nonce=$nonce");
			}
			// Don't just return silently if we were expecting an action
			if ( ! empty( $action ) ) {
				\Charts\Core\Notify::error( __( 'Action security verification failed (Nonce mismatch). Please refresh the page.', 'charts' ), __( 'Security Failure', 'charts' ) );
			}
			return;
		}

		$processed = false;

		$processed = false;

		switch ( $action ) {
			case 'save_entity':
				$result = self::persist_entity_record();
				if ( is_wp_error( $result ) ) {
					\Charts\Core\Notify::error( $result->get_error_message(), __( 'Entity Save Failed', 'charts' ) );
				} else {
					\Charts\Core\Notify::success( __( 'Entity saved successfully.', 'charts' ), __( 'Changes Saved', 'charts' ) );
				}
				$processed = true;
				break;

			case 'save_clip_track_mappings':
				$result = self::persist_clip_track_mappings();
				if ( is_wp_error( $result ) ) {
					\Charts\Core\Notify::error( $result->get_error_message(), __( 'Clip linking failed', 'charts' ) );
				} else {
					\Charts\Core\Notify::success( sprintf( __( 'Saved track links for %d clips.', 'charts' ), (int) $result ), __( 'Clip links saved', 'charts' ) );
				}
				$processed = true;
				break;

			case 'auto_link_clips_to_tracks':
				$result = self::auto_link_clips_to_tracks();
				if ( is_wp_error( $result ) ) {
					\Charts\Core\Notify::error( $result->get_error_message(), __( 'Automatic linking failed', 'charts' ) );
				} else {
					\Charts\Core\Notify::success( sprintf( __( 'Automatically linked %d clips with a unique track match.', 'charts' ), (int) $result ), __( 'Automatic linking complete', 'charts' ) );
				}
				$processed = true;
				break;

			case 'save_settings_v2':
				if ( ! current_user_can( 'manage_options' ) ) return;
				check_admin_referer( 'kcharts_save_v2' );

				if ( isset( $_POST['kc_opt'] ) && is_array( $_POST['kc_opt'] ) ) {
					\Charts\Core\Settings::update_all( $_POST['kc_opt'] );
				}

				if ( isset( $_POST['charts_markets_raw'] ) ) {
					$raw = sanitize_textarea_field( wp_unslash( $_POST['charts_markets_raw'] ) );
					$lines = explode( "\n", str_replace("\r", "", $raw) );
					$markets = [];
					foreach ($lines as $line) {
						$line = trim($line);
						if (empty($line)) continue;
						
						$parts = explode(':', $line, 2);
						if (count($parts) === 2) {
							$markets[] = [
								'code' => trim($parts[0]),
								'name' => trim($parts[1])
							];
						} else {
							$markets[] = [
								'code' => strtoupper(substr(sanitize_title($line), 0, 3)),
								'name' => trim($line)
							];
						}
					}
					update_option('charts_markets', $markets);
				}

				\Charts\Core\Notify::success( __( 'Global settings nexus synchronized successfully.', 'charts' ), __( 'Configuration Saved', 'charts' ) );
				$processed = true;
				break;

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
							$posted_val = isset( $_POST[ $key ] ) ? $_POST[ $key ] : null;
							
							if ( $posted_val === null ) {
								// If it's a switch/checkbox, it won't be in POST if off
								if ( $type === 'chk' ) $val = 0;
								else continue; // Skip others if not posted
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
						\Charts\Core\Settings::update_all($settings_to_update);
						\Charts\Core\Notify::success( __( 'Dynamic configuration segments synchronized successfully.', 'charts' ), __( 'Settings Updated', 'charts' ) );
					}
				}
				$processed = true;
				break;

			case 'save_translations':
				if ( isset( $_POST['kc_trans'] ) && is_array( $_POST['kc_trans'] ) ) {
					$translations = array_map( 'sanitize_text_field', wp_unslash( $_POST['kc_trans'] ) );
					// Filter out empty strings so it falls back to defaults properly
					$translations = array_filter( $translations, function($val) {
						return trim($val) !== '';
					});
					update_option( 'kcharts_translations', $translations );
					\Charts\Core\Notify::success( __( 'Translations updated successfully.', 'charts' ), __( 'Settings Saved', 'charts' ) );
				}
				$processed = true;
				break;

			case 'export_name_sync':
				self::process_name_sync_export();
				exit;

			case 'name_sync_upload':
				$result = self::process_name_sync();
				$run_id = time();
				set_transient( 'charts_name_sync_result_' . $run_id, $result, HOUR_IN_SECONDS );
				\Charts\Core\Notify::success(
					sprintf( __( 'Name Sync complete. Artists: %d, Tracks: %d, Clips: %d, Albums: %d, Slugs: %d, Not found: %d.', 'charts' ), $result['artists_updated'], $result['tracks_updated'], $result['videos_updated'], $result['albums_updated'], $result['slugs_updated'], $result['not_found'] ),
					__( 'Sync Done', 'charts' )
				);
				$processed = true;
				// Redirect to sync page with run ID to display results
				wp_safe_redirect( admin_url( 'admin.php?page=charts-name-sync&sync_run_id=' . $run_id ) );
				exit;

			case 'factory_reset_data':
				if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');
				$report = self::wipe_all_data( false ); // False = Do not wipe definitions/sources
				$summary = sprintf( 
					__( 'Factory Reset successful. Purged: %d entries, %d tracks, %d artists. Your configuration and definitions were preserved.', 'charts' ),
					$report['entries'], $report['tracks'], $report['artists']
				);
				\Charts\Core\Notify::success( $summary, __( 'Data Reset Complete', 'charts' ) );
				$processed = true;
				break;

			case 'run_integrity_check_v2':
				\Charts\Core\Integrity::recalculate_entity_links();
				$redundant = \Charts\Core\Integrity::detect_redundant_sources();
				
				$msg = __( 'Data integrity check complete. Unmatched entities have been reconciled.', 'charts' );
				if ( ! empty( $redundant ) ) {
					$msg .= ' ' . sprintf( __( 'WARNING: %d redundant active sources detected (IDs: %s). These may cause duplicate ranking rows on front-facing charts.', 'charts' ), count($redundant), implode(', ', array_column($redundant, 'source_ids')) );
				}
				
				\Charts\Core\Notify::success( $msg, __( 'Integrity Restored', 'charts' ) );
				$processed = true;
				break;

			case 'backfill_media':
			case 'backfill_media_v2':
				$manager = new \Charts\Services\AssetManager();
				$results = $manager->backfill_all();
				$summary = sprintf( 
					__( 'Media backfill complete. Tracks: %d/%d updated. Artists: %d/%d updated. Videos: %d/%d updated.', 'charts' ),
					$results['tracks']['updated'], $results['tracks']['processed'],
					$results['artists']['updated'], $results['artists']['processed'],
					$results['videos']['updated'], $results['videos']['processed']
				);
				\Charts\Core\Notify::success( $summary, __( 'Asset Backfill Complete', 'charts' ) );
				$processed = true;
				break;

			case 'reset_plugin_v2':
				if ( $_POST['confirm_reset'] !== 'RESET CHARTS' ) {
					\Charts\Core\Notify::error( __( 'Confirmation failed. Please type exactly: RESET CHARTS', 'charts' ), __( 'Security Access Denied', 'charts' ) );
				} else {
					$wipe_settings = isset( $_POST['wipe_settings'] ) ? (bool)$_POST['wipe_settings'] : false;
					$report = self::wipe_all_data( $wipe_settings );
					
					$summary = sprintf( 
						__( 'Plugin reset successful. Purged: %d entries, %d tracks, %d artists, %d definitions. %s', 'charts' ),
						$report['entries'], $report['tracks'], $report['artists'], $report['definitions'],
						$wipe_settings ? __( 'Interface configuration logic also purged.', 'charts' ) : ''
					);
					\Charts\Core\Notify::success( $summary, __( 'Nexus Data Purge Complete', 'charts' ) );
				}
				$processed = true;
				break;

			case 'save_source':
				$manager = new SourceManager();
				$result = $manager->save_source( $_POST );
				if ( $result ) {
					\Charts\Core\Notify::success( __( 'Data source successfully synchronized.', 'charts' ), __( 'Source Saved', 'charts' ) );
				} else {
					\Charts\Core\Notify::error( __( 'Failed to save data source configuration.', 'charts' ), __( 'Configuration Error', 'charts' ) );
				}
				$processed = true;
				break;

			case 'delete_source':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_source( $id );
				\Charts\Core\Notify::success( __( 'Data source removed from the nexus.', 'charts' ), __( 'Source Deleted', 'charts' ) );
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
				if ( is_numeric($run_id) || (is_array($run_id) && isset($run_id['run_id'])) ) {
					\Charts\Core\Notify::success( __( 'Unified segment ingest complete. Live signals are being calibrated in the nexus.', 'charts' ), __( 'Nexus Sync Complete', 'charts' ) );
					wp_redirect( admin_url( 'admin.php?page=charts-import&sync_complete=1&run_id=' . (is_array($run_id) ? $run_id['run_id'] : $run_id) ) );
					exit;
				}
				$processed = true;
				break;
			
			case 'save_definition':
				$manager = new SourceManager();
				$result = $manager->save_definition( $_POST );
				if ( is_wp_error( $result ) ) {
					\Charts\Core\Notify::error( sprintf( __( 'Save Failed: %s', 'charts' ), $result->get_error_message() ), __( 'Definition Error', 'charts' ) );
				} elseif ( $result ) {
					\Charts\Core\Notify::success( __( 'Chart definition saved successfully.', 'charts' ), __( 'Definition Updated', 'charts' ) );
				} else {
					\Charts\Core\Notify::warning( __( 'Save completed, but no changes were detected.', 'charts' ), __( 'Definition Unchanged', 'charts' ) );
				}
				$processed = true;
				break;
			
			case 'delete_definition':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$manager->delete_definition( $id );
				\Charts\Core\Notify::success( __( 'Chart definition purged from systems.', 'charts' ), __( 'Definition Purged', 'charts' ) );
				$processed = true;
				break;

			case 'promote_chart':
				$manager = new SourceManager();
				$id = intval( $_POST['id'] );
				$post_id = $manager->promote_to_native( $id );
				if ( $post_id ) {
					\Charts\Core\Notify::success( __( 'Chart localized into native CPT nexus. You can now use Elementor or native templates to customize this layout.', 'charts' ), __( 'Promotion Successful', 'charts' ) );
				} else {
					\Charts\Core\Notify::error( __( 'Failed to localize chart. The entity may already be native or the ID is invalid.', 'charts' ), __( 'Promotion Failure', 'charts' ) );
				}
				$processed = true;
				break;

			case 'promote_entity':
				$id   = intval( $_POST['id'] );
				$type = sanitize_text_field( $_POST['type'] );
				$post_id = \Charts\Core\EntityManager::promote_to_native( $type, $id );
				if ( $post_id ) {
					\Charts\Core\Notify::success( sprintf( __( '%s localized into native CPT nexus.', 'charts' ), ucfirst($type) ), __( 'Promotion Successful', 'charts' ) );
				} else {
					\Charts\Core\Notify::error( __( 'Failed to localize entity.', 'charts' ), __( 'Promotion Failure', 'charts' ) );
				}
				$processed = true;
				break;

			case 'bulk_promote':
				$ids  = array_map( 'intval', (array)($_POST['item_ids'] ?? array()) );
				$type = sanitize_text_field( $_POST['type'] ?? 'artist' );
				$count = 0;
				if ( ! empty( $ids ) ) {
					foreach ( $ids as $id ) {
						if ( \Charts\Core\EntityManager::promote_to_native( $type, $id ) ) $count++;
					}
					\Charts\Core\Notify::success( sprintf( __( 'Bulk localization complete. %d %s entities migrated to native production model.', 'charts' ), $count, $type ), __( 'Nexus Migration Success', 'charts' ) );
				}
				$processed = true;
				break;

			case 'delete_entity':
				global $wpdb;
				$id    = intval( $_POST['id'] );
				$type  = sanitize_text_field( $_POST['type'] );
				self::delete_single_entity( $id, $type );
				\Charts\Core\Notify::success( __( 'Canonical entity deleted and all historical relationships unlinked.', 'charts' ), __( 'Entity Decoupled', 'charts' ) );
				$processed = true;
				break;

			case 'bulk_action':
				global $wpdb;
				$action_type = sanitize_text_field( $_POST['bulk_action_type'] );
				$ids    = isset( $_POST['item_ids'] ) ? array_map( 'intval', $_POST['item_ids'] ) : array();
				$type   = sanitize_text_field( $_POST['entity_type'] );

				if ( empty( $ids ) ) {
					\Charts\Core\Notify::warning( __( 'No items were selected for the bulk operation.', 'charts' ), __( 'Selection Empty', 'charts' ) );
				} else if ( $action_type === 'delete' ) {
					foreach ( $ids as $id ) {
						self::delete_single_entity( $id, $type );
					}
					\Charts\Core\Notify::success( sprintf( __( '%d entities successfully purged from the system.', 'charts' ), count( $ids ) ), __( 'Bulk Purge Complete', 'charts' ) );
				} else if ( $action_type === 'bulk_promote' ) {
					$count = 0;
					foreach ( $ids as $id ) {
						if ( \Charts\Core\EntityManager::promote_to_native( $type, $id ) ) $count++;
					}
					\Charts\Core\Notify::success( sprintf( __( '%d entities reached native production maturity.', 'charts' ), $count ), __( 'Migration Success', 'charts' ) );
				}
				$processed = true;
				break;


				

			case 'test_spotify_api':
				$client = new \Charts\Services\SpotifyApiClient();
				$result = $client->test_connection();
				
				if ( is_wp_error( $result ) ) {
					$msg = sprintf( __( 'Spotify API Test Failed: %s (%s)', 'charts' ), $result->get_error_message(), $result->get_error_code() );
					\Charts\Core\Notify::error( $msg, __( 'API Connection Failure', 'charts' ) );
				} else {
					\Charts\Core\Notify::success( __( 'Spotify API Connection Successful! Token generated and metadata retrieved.', 'charts' ), __( 'API Handshake Success', 'charts' ) );
				}
				$processed = true;
				break;

			case 'test_youtube_api':
				$client = new \Charts\Services\YouTubeApiClient();
				$result = $client->test_connection();

				if ( is_wp_error( $result ) ) {
					$msg = sprintf( __( 'YouTube API Test Failed: %s (%s)', 'charts' ), $result->get_error_message(), $result->get_error_code() );
					\Charts\Core\Notify::error( $msg, __( 'API Connection Failure', 'charts' ) );
				} else {
					\Charts\Core\Notify::success( __( 'YouTube API Connection Successful! Metadata retrieved from video jNQXAC9IVRw.', 'charts' ), __( 'API Handshake Success', 'charts' ) );
				}
				$processed = true;
				break;


			case 'reset_plugin':
				$wipe_settings = isset( $_POST['wipe_settings'] ) ? (bool)$_POST['wipe_settings'] : false;
				self::wipe_all_data( $wipe_settings );
				\Charts\Core\Notify::success( __( 'Plugin has been successfully reset. All records cleared.', 'charts' ), __( 'System Reset', 'charts' ) );
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
			} elseif ( in_array( $action, array( 'promote_entity', 'bulk_promote', 'delete_entity', 'save_entity', 'bulk_action' ), true ) ) {
				// Route back to the correct entity screen based on submitted type
				$entity_type = sanitize_text_field( $_POST['entity_type'] ?? ( $_POST['type'] ?? '' ) );
				if ( $entity_type === 'track' ) {
					$module = 'tracks';
				} elseif ( $entity_type === 'video' ) {
					$module = 'clips';
				} elseif ( $entity_type === 'album' ) {
					$module = 'albums';
				} else {
					$module = 'artists';
				}
			} elseif ( in_array( $action, array( 'save_clip_track_mappings', 'auto_link_clips_to_tracks' ), true ) ) {
				$module = 'clips';
			} elseif ( strpos( $action, 'definition' ) !== false ) {
				$module = 'definitions';
			} elseif ( strpos( $action, 'import' ) !== false || strpos( $action, 'run' ) !== false ) {
				$module = 'import';
			} elseif ( strpos( $action, 'intel' ) !== false ) {
				$module = 'intelligence';
			} elseif ( strpos( $action, 'match' ) !== false || strpos( $action, 'integrity' ) !== false ) {
				$module = 'matching';
			} elseif ( strpos( $action, 'location' ) !== false ) {
				$module = 'locations';
			}

			// 3. Construct target URL
			// We prioritize the referer IF it matches our surface, otherwise we use the clean module URL
			$target_url = '';
			if ( $action !== 'save_entity' && $referer && ! ( stripos( $referer, '/charts/' ) !== false && stripos( $referer, '/charts-dashboard' ) === false ) ) {
				// Referer is safe (it's either admin or dashboard)
				$target_url = $referer;
			} else {
				// Fallback to clean module URL
				$target_url = in_array( $action, array( 'save_clip_track_mappings', 'auto_link_clips_to_tracks' ), true )
					? admin_url( 'admin.php?page=charts-clip-track-linker' )
					: \Charts\Core\Router::get_dashboard_url( $module );
				
				// Ensure surface consistency in fallback
				if ( $is_external_surface && stripos( $target_url, '/wp-admin/' ) !== false ) {
					$target_url = home_url( '/charts-dashboard/' . $module . '/' );
				}
			}

			// Clean the target URL of the action triggers to prevent loops if referer was dirty
			$target_url = remove_query_arg( array( 'charts_action', '_wpnonce' ), $target_url );

			// 4. Append persistent notices if necessary
			if ( $action === 'save_settings' || $action === 'save_translations' ) {
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

	/** Validate and persist artist, track, or clip edits from the entity editor. */
	private static function persist_entity_record() {
		global $wpdb;
		$type = sanitize_key( wp_unslash( $_POST['entity_type'] ?? '' ) );
		$id   = absint( $_POST['entity_id'] ?? 0 );
		$map  = array( 'artist' => 'artists', 'track' => 'tracks', 'video' => 'videos', 'album' => 'albums' );
		if ( ! isset( $map[ $type ] ) ) return new \WP_Error( 'invalid_entity_type', __( 'Unsupported entity type.', 'charts' ) );

		$table = $wpdb->prefix . 'charts_' . $map[ $type ];
		$existing = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) ) : null;
		if ( $id && ! $existing ) return new \WP_Error( 'entity_not_found', __( 'This record no longer exists.', 'charts' ) );

		$name = sanitize_text_field( wp_unslash( $_POST['entity_name'] ?? '' ) );
		if ( $name === '' ) return new \WP_Error( 'entity_name_required', __( 'Name or title is required.', 'charts' ) );

		$slug_input = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
		$slug = \Charts\Services\Slugger::make( $slug_input !== '' ? $slug_input : $name, $type . '-' . ( $id ?: 'item' ) );
		$base_slug = $slug;
		$suffix = 2;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s AND id != %d LIMIT 1", $slug, $id ) ) ) {
			$slug = $base_slug . '-' . $suffix++;
		}

		$data = array( 'slug' => $slug, 'updated_at' => current_time( 'mysql' ) );
		if ( $type === 'artist' ) {
			$data['display_name'] = $name;
			$data['normalized_name'] = \Charts\Services\Normalizer::normalize_artist( $name );
			$data['display_name_en'] = sanitize_text_field( wp_unslash( $_POST['name_en'] ?? '' ) ) ?: null;
			$raw_spotify = sanitize_text_field( wp_unslash( $_POST['spotify_id'] ?? '' ) );
			$data['spotify_id'] = self::normalize_matching_identifier( $raw_spotify, 'spotify' ) ?: ( $raw_spotify ?: null );
			$data['image'] = esc_url_raw( wp_unslash( $_POST['image'] ?? '' ) ) ?: null;
		} elseif ( $type === 'album' ) {
			$artist_id = absint( $_POST['primary_artist_id'] ?? 0 );
			if ( $artist_id && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) ) ) {
				$artist_id = 0;
			}
			$data['title'] = $name;
			$data['title_en'] = sanitize_text_field( wp_unslash( $_POST['name_en'] ?? '' ) ) ?: null;
			$data['normalized_title'] = \Charts\Services\Normalizer::normalize_title( $name );
			$data['primary_artist_id'] = $artist_id ?: null;
			$raw_spotify = sanitize_text_field( wp_unslash( $_POST['spotify_id'] ?? '' ) );
			$clean_spotify = self::normalize_matching_identifier( $raw_spotify, 'spotify' ) ?: ( $raw_spotify ?: null );
			$data['spotify_id'] = $clean_spotify;
			$data['cover_image'] = esc_url_raw( wp_unslash( $_POST['image'] ?? '' ) ) ?: null;
			$release_date = sanitize_text_field( wp_unslash( $_POST['release_date'] ?? '' ) );
			if ( ! empty( $release_date ) ) {
				$data['release_date'] = substr( $release_date, 0, 10 );
			}
		} else {
			$artist_id = absint( $_POST['primary_artist_id'] ?? 0 );
			if ( ! $artist_id || ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) ) ) {
				return new \WP_Error( 'primary_artist_required', __( 'Choose a valid primary artist.', 'charts' ) );
			}
			$data['title'] = $name;
			$data['normalized_title'] = \Charts\Services\Normalizer::normalize_title( $name );
			$data['primary_artist_id'] = $artist_id;
			$artist_name = $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
			if ( $type === 'track' ) {
				$raw_spotify = sanitize_text_field( wp_unslash( $_POST['spotify_id'] ?? '' ) );
				$data['spotify_id'] = self::normalize_matching_identifier( $raw_spotify, 'spotify' ) ?: ( $raw_spotify ?: null );
				$data['youtube_id'] = sanitize_text_field( wp_unslash( $_POST['youtube_id'] ?? '' ) ) ?: null;
				$data['cover_image'] = esc_url_raw( wp_unslash( $_POST['image'] ?? '' ) ) ?: null;
			} else {
				$data['youtube_id'] = sanitize_text_field( wp_unslash( $_POST['youtube_id'] ?? '' ) ) ?: null;
				$data['video_url'] = esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) ) ?: null;
				$data['thumbnail'] = esc_url_raw( wp_unslash( $_POST['image'] ?? '' ) ) ?: null;
				$related_track_id = absint( $_POST['related_track_id'] ?? 0 );
				$data['related_track_id'] = $related_track_id ?: null;
			}
		}

		// Self-healing: verify table and columns exist in active database
		$cols = $wpdb->get_col( "DESCRIBE `$table`", 0 );
		if ( empty( $cols ) ) {
			$schema = new \Charts\Database\Schema();
			$schema->install();
			$cols = $wpdb->get_col( "DESCRIBE `$table`", 0 );
		}

		// Ensure title_en exists in table if saving title_en
		if ( isset( $data['title_en'] ) && ! in_array( 'title_en', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE `$table` ADD COLUMN `title_en` VARCHAR(255) DEFAULT NULL" );
			$cols[] = 'title_en';
		}

		// Ensure release_date exists if saving release_date
		if ( isset( $data['release_date'] ) && ! in_array( 'release_date', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE `$table` ADD COLUMN `release_date` DATE DEFAULT NULL" );
			$cols[] = 'release_date';
		}

		// Filter $data to columns that actually exist in the table
		$safe_data = array();
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $cols, true ) ) {
				$safe_data[ $k ] = $v;
			}
		}

		if ( $existing ) {
			$saved = $wpdb->update( $table, $safe_data, array( 'id' => $id ) );
		} else {
			$safe_data['created_at'] = current_time( 'mysql' );
			$saved = $wpdb->insert( $table, $safe_data );
			$id = (int) $wpdb->insert_id;
		}

		if ( $saved === false ) {
			$db_err = ! empty( $wpdb->last_error ) ? ' (' . $wpdb->last_error . ')' : '';
			return new \WP_Error( 'entity_save_failed', sprintf( __( 'The record could not be saved%s. Check that the slug is unique.', 'charts' ), $db_err ) );
		}

		// If Spotify ID was set for album, auto-enrich cover and tracks
		if ( $type === 'album' && ! empty( $data['spotify_id'] ) ) {
			$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
			$spotify_service->enrich_album( $id );
		} elseif ( $type === 'track' && ! empty( $data['spotify_id'] ) && empty( $data['cover_image'] ) ) {
			$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
			$spotify_service->enrich_track( $id );
		}

		// A clip can belong to one track. Keep the editor usable from either side
		// by syncing the selected clips when a track is saved.
		if ( $type === 'track' ) {
			$video_table = $wpdb->prefix . 'charts_videos';
			$related_video_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['related_video_ids'] ?? array() ) ) ) ) );
			$wpdb->update( $video_table, array( 'related_track_id' => null ), array( 'related_track_id' => $id ) );
			foreach ( $related_video_ids as $related_video_id ) {
				$video_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $video_table WHERE id = %d", $related_video_id ) );
				if ( $video_exists ) {
					$wpdb->update( $video_table, array( 'related_track_id' => $id ), array( 'id' => $related_video_id ) );
				}
			}
		}

		self::sync_entity_chart_entries( $type, $id, $type === 'artist' ? ( $existing->display_name ?? '' ) : '', $type === 'artist' ? ( $existing->display_name_en ?? '' ) : '' );
		if ( $type !== 'artist' ) {
			$artist_name = $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", (int) $data['primary_artist_id'] ) );
			$wpdb->update( $wpdb->prefix . 'charts_entries', array( 'artist_names' => $artist_name ), array( 'item_type' => $type === 'video' ? 'video' : ( $type === 'album' ? 'album' : 'track' ), 'item_id' => $id ) );
		}

		self::clear_frontend_caches();
		return $id;
	}

	/**
	 * Deletes a single entity and cleans up relationships safely.
	 */
	private static function delete_single_entity( $id, $type ) {
		global $wpdb;
		$suffix = ( $type === 'artist' ) ? 'artists' : ( ( $type === 'track' ) ? 'tracks' : ( ( $type === 'album' ) ? 'albums' : 'videos' ) );
		$table  = $wpdb->prefix . 'charts_' . $suffix;
		
		// 1. Delete the canonical metadata
		$wpdb->delete( $table, array( 'id' => $id ) );
		
		// 2. Eradicate completely from charts history
		$wpdb->delete( 
			$wpdb->prefix . 'charts_entries', 
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

		$menus = array(
			array( 'title' => 'Overview', 'slug' => 'charts-dashboard', 'callback' => 'render_dashboard' ),
			array( 'title' => 'Charts', 'slug' => 'charts-definitions', 'callback' => 'render_definitions' ),
			array( 'title' => 'Artists', 'slug' => 'charts-artists', 'callback' => 'render_entities' ),
			array( 'title' => 'Tracks', 'slug' => 'charts-tracks', 'callback' => 'render_entities' ),
			array( 'title' => 'Clips', 'slug' => 'charts-clips', 'callback' => 'render_entities' ),
			array( 'title' => 'Albums', 'slug' => 'charts-albums', 'callback' => 'render_entities' ),
			array( 'title' => 'Clip-Track Linking', 'slug' => 'charts-clip-track-linker', 'callback' => 'render_clip_track_linker' ),
			array( 'title' => 'Sources', 'slug' => 'charts-sources', 'callback' => 'render_sources' ),
			array( 'title' => 'Import Center', 'slug' => 'charts-import', 'callback' => 'render_import_center' ),
			array( 'title' => 'Import Runs', 'slug' => 'charts-imports', 'callback' => 'render_results_history' ),
			array( 'title' => 'Matching Center', 'slug' => 'charts-matching', 'callback' => 'render_matching' ),
			array( 'title' => 'Intelligence', 'slug' => 'charts-intelligence', 'callback' => 'render_intelligence' ),
			array( 'title' => 'Quick Translation', 'slug' => 'charts-translations', 'callback' => 'render_translations' ),
		array( 'title' => 'Name Sync', 'slug' => 'charts-name-sync', 'callback' => 'render_name_sync' ),
			array( 'title' => 'Performance', 'slug' => 'charts-performance', 'callback' => 'render_performance' ),
			array( 'title' => 'Settings', 'slug' => 'charts-settings', 'callback' => 'render_settings' ),
		);

		foreach ( $menus as $m ) {
			add_submenu_page(
				'charts-dashboard',
				__( $m['title'], 'charts' ),
				__( $m['title'], 'charts' ),
				'manage_options',
				$m['slug'],
				array( self::class, $m['callback'] )
			);
		}
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

		// Synchronize persistent flash notifications with the JS engine
		wp_localize_script( 'charts-admin', 'kcharts_toasts', \Charts\Core\Notify::get_and_clear() );

		wp_localize_script( 'charts-admin', 'kcharts_theme_options', \Charts\Core\Settings::get_defaults() );
	}

	/**
	 * Render the Performance & Migration view.
	 */
	public static function render_performance() {
		include CHARTS_PATH . 'admin/views/performance.php';
	}

	/**
	 * Render the Quick Translation view.
	 */
	public static function render_translations() {
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		try {
			include CHARTS_PATH . 'admin/views/translations.php';
		} catch ( \Throwable $e ) {
			echo '<div class="wrap" style="padding:40px; background:#fff; border:2px solid red;">';
			echo '<h1>Fatal Error in Translations Page</h1>';
			echo '<p><strong>' . esc_html( $e->getMessage() ) . '</strong></p>';
			echo '<pre>' . esc_html( $e->getTraceAsString() ) . '</pre>';
			echo '</div>';
		}
	}

	public static function render_name_sync() {
		include CHARTS_PATH . 'admin/views/name-sync.php';
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
			'clips'            => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_videos" ),
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


	public static function render_import_center() {
		self::render_view( 'import-center' );
	}



	public static function render_results_history() {
		self::render_view( 'results' );
	}


	/**
	 * Process Unified Import.
	 * Routes to the correct platform handler based on selection.
	 */
	public static function render_youtube_import() {
		include CHARTS_PATH . 'admin/views/youtube-import.php';
	}

	private static function process_unified_import() {
		$platform = sanitize_text_field( $_POST['platform'] ?? 'spotify' );
		if ( ! in_array( $platform, array( 'spotify', 'youtube', 'kontent', 'billboard', 'soundcharts' ), true ) ) {
			\Charts\Core\Notify::error( __( 'Choose a supported import platform.', 'charts' ), __( 'Invalid Import Platform', 'charts' ) );
			return false;
		}

		$chart_id = absint( $_POST['chart_id'] ?? 0 );
		$definition = $chart_id ? ( new SourceManager() )->get_definition( $chart_id ) : null;
		if ( ! $definition ) {
			\Charts\Core\Notify::error( __( 'Choose a valid destination chart.', 'charts' ), __( 'Invalid Chart', 'charts' ) );
			return false;
		}
		$definition_platform = sanitize_key( $definition->platform ?? 'all' );
		$data_platform = $platform === 'soundcharts' ? sanitize_key( $_POST['soundcharts_platform'] ?? '' ) : $platform;
		if ( $definition_platform !== 'all' && $definition_platform !== $data_platform ) {
			\Charts\Core\Notify::error( __( 'The selected chart is configured for a different platform.', 'charts' ), __( 'Chart Platform Mismatch', 'charts' ) );
			return false;
		}
		// The selected chart profile is authoritative for the item type.
		$_POST['item_type'] = $definition->item_type ?: ( $_POST['item_type'] ?? 'track' );

		if ( $platform === 'soundcharts' ) {
			$importer = new \Charts\Services\SoundchartsImporter();
			$result = $importer->run( array(
				'entity_type' => sanitize_key( $_POST['soundcharts_entity_type'] ?? '' ),
				'platform'    => $data_platform,
				'country_code'=> sanitize_text_field( $_POST['country'] ?? '' ),
				'chart_slug'  => sanitize_text_field( $_POST['soundcharts_chart_slug'] ?? '' ),
				'chart_id'    => $chart_id,
			) );
			if ( is_wp_error( $result ) ) {
				\Charts\Core\Notify::error( $result->get_error_message(), __( 'Soundcharts Import Failed', 'charts' ) );
				return false;
			}
			\Charts\Core\Notify::success( sprintf( __( 'Soundcharts imported %1$d entries (%2$d new records) from %3$d ranking rows.', 'charts' ), $result['saved'], $result['created'], $result['parsed'] ), __( 'Soundcharts Sync Complete', 'charts' ) );
			return $result;
		}
		
		if ( $platform === 'billboard' && empty( $_FILES['import_file']['tmp_name'] ) ) {
			// Billboard API Direct Sync
			$week_id = intval( $_POST['billboard_week_id'] ?? 0 );
			$bb_chart_id = absint( $_POST['billboard_chart_id'] ?? 0 );
			if ( ! $week_id || ! $bb_chart_id ) {
				\Charts\Core\Notify::error( __( 'Billboard API requires a selected chart and week.', 'charts' ), __( 'Missing Parameters', 'charts' ) );
				return false;
			}
			$result = \Charts\Services\BillboardService::sync_to_chart( $week_id, $chart_id, $bb_chart_id );
			if ( is_wp_error( $result ) ) {
				\Charts\Core\Notify::error( $result->get_error_message(), __( 'Billboard API Failed', 'charts' ) );
				return false;
			}
			\Charts\Core\Notify::success( sprintf( __( 'Billboard API imported %d entries.', 'charts' ), $result['imported_count'] ), __( 'Billboard Sync Complete', 'charts' ) );
			return array( 'run_id' => $result['run_id'] ?? time() );
		}

		if ( $platform === 'youtube' && empty( $_FILES['import_file']['tmp_name'] ) ) {
			// YouTube Live Sync
			$yt_chart_key = sanitize_text_field( $_POST['youtube_chart_key'] ?? 'top-songs-weekly' );
			$result = \Charts\Services\YouTubeChartsService::sync_to_chart( $yt_chart_key, $chart_id );
			if ( is_wp_error( $result ) ) {
				\Charts\Core\Notify::error( $result->get_error_message(), __( 'YouTube Sync Failed', 'charts' ) );
				return false;
			}
			\Charts\Core\Notify::success( sprintf( __( 'YouTube Charts imported %d entries directly from YouTube live streams.', 'charts' ), $result['imported_count'] ), __( 'YouTube Sync Complete', 'charts' ) );
			return array( 'run_id' => time() );
		}
		
		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			\Charts\Core\Notify::warning( __( 'No valid segment file was detected for the unified import stream.', 'charts' ), __( 'Upload Required', 'charts' ) );
			return false;
		}

		// Inject the correct file name into the expected $_FILES location for compatibility
		if ( $platform === 'spotify' ) {
			$_FILES['spotify_csv'] = $_FILES['import_file'];
			return self::process_spotify_csv_upload();
		} elseif ( $platform === 'billboard' ) {
			$_FILES['billboard_csv'] = $_FILES['import_file'];
			return self::process_billboard_csv_upload();
		} elseif ( $platform === 'kontent' ) {
			$_FILES['kontent_csv'] = $_FILES['import_file'];
			return self::process_kontent_csv_upload();
		} else {
			$_FILES['youtube_csv'] = $_FILES['import_file'];
			return self::process_youtube_csv_upload();
		}
	}

	/** AJAX endpoint for Soundcharts platform and chart selection. */
	public static function handle_soundcharts_catalog() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to access Soundcharts data.', 'charts' ) ), 403 );
		}

		$entity_type = sanitize_key( $_POST['entity_type'] ?? 'song' );
		$platform    = sanitize_key( $_POST['soundcharts_platform'] ?? '' );
		$country     = sanitize_text_field( $_POST['country'] ?? '' );
		$client      = new \Charts\Services\SoundchartsApiClient();
		$result      = $platform === '' ? $client->get_platforms( $entity_type ) : $client->get_charts( $entity_type, $platform, $country );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success( array( 'items' => $result ) );
	}

	private static function process_kontent_csv_upload() {
		global $wpdb;
		if ( empty( $_FILES['kontent_csv']['tmp_name'] ) ) {
			\Charts\Core\Notify::error( __( 'The Kontent CSV is missing.', 'charts' ), __( 'Input Failure', 'charts' ) );
			return;
		}

		$csv_content = file_get_contents( $_FILES['kontent_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			\Charts\Core\Notify::error( __( 'Failed to read the uploaded CSV.', 'charts' ), __( 'I/O Failure', 'charts' ) );
			return;
		}

		$meta = array(
			'chart_id'    => intval( $_POST['chart_id'] ?? 0 ),
			'country'     => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'period_type' => sanitize_text_field( $_POST['period_type'] ?? 'weekly' ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? current_time('Y-m-d') ),
			'item_type'   => sanitize_text_field( $_POST['item_type'] ?? 'track' ),
			'filename'    => sanitize_text_field( $_FILES['kontent_csv']['name'] ?? '' ),
		);

		$importer = new \Charts\Services\KontentCsvImporter();
		$result = $importer->run( $csv_content, $meta );

		if ( is_wp_error( $result ) ) {
			\Charts\Core\Notify::error( $result->get_error_message(), __( 'Import Failure', 'charts' ) );
		} else {
			return $result['run_id'] ?? $result['period_id'];
		}
	}

	private static function process_billboard_csv_upload() {
		global $wpdb;
		if ( empty( $_FILES['billboard_csv']['tmp_name'] ) || (int) ( $_FILES['billboard_csv']['error'] ?? UPLOAD_ERR_OK ) !== UPLOAD_ERR_OK ) {
			\Charts\Core\Notify::error( __( 'The Billboard CSV is missing.', 'charts' ), __( 'Input Failure', 'charts' ) );
			return;
		}

		$csv_content = file_get_contents( $_FILES['billboard_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			\Charts\Core\Notify::error( __( 'Failed to read the uploaded CSV.', 'charts' ), __( 'I/O Failure', 'charts' ) );
			return;
		}

		$meta = array(
			'chart_id'    => absint( $_POST['chart_id'] ?? 0 ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? current_time( 'Y-m-d' ) ),
		);
		$importer = new \Charts\Services\BillboardCsvImporter();
		$result = $importer->run( $csv_content, $meta );

		if ( is_wp_error( $result ) ) {
			\Charts\Core\Notify::error( $result->get_error_message(), __( 'Import Failure', 'charts' ) );
		} else {
			return $result['run_id'] ?? $result['period_id'];
		}
	}

	/**
	 * Process Spotify CSV Upload.
	 */
	private static function process_spotify_csv_upload() {
		global $wpdb;
		if ( empty( $_FILES['spotify_csv']['tmp_name'] ) ) {
			\Charts\Core\Notify::error( __( 'The Spotify CSV segment is missing or corrupt in the upload buffer.', 'charts' ), __( 'Input Failure', 'charts' ) );
			return;
		}

		$meta = array(
			'chart_id'    => intval( $_POST['chart_id'] ?? 0 ),
			'country'     => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'  => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'   => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? '' ),
			'source_name' => sanitize_text_field( $_POST['source_name'] ?? '' ),
			'item_type'   => sanitize_text_field( $_POST['item_type'] ?? 'track' ),
		);

		$csv_content = file_get_contents( $_FILES['spotify_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			\Charts\Core\Notify::error( __( 'Failed to read the uploaded CSV stream. The segment may be corrupted or blocked by the server filesystem.', 'charts' ), __( 'Critical I/O Failure', 'charts' ) );
			return;
		}

		try {
			$importer = new \Charts\Services\SpotifyCsvImporter();
			$result   = $importer->run( $csv_content, $meta );
			if ( is_wp_error( $result ) ) {
				\Charts\Core\Notify::error( $result->get_error_message(), __( 'Import Pipeline Failure', 'charts' ) );
				return false;
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/charts/' );
				if ( ! empty($meta['chart_id']) ) {
					$c_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $meta['chart_id'] ) );
					if ( $c_slug ) $chart_url = home_url( '/charts/' . $c_slug . '/' );
				}

				$msg = sprintf( __( 'Spotify segment ingested: %1$d entries crystallized from %2$d raw rows. [ <a href="%6$s" target="_blank">View Chart</a> ]', 'charts' ), $result['saved'], $result['parsed'], $result['source_id'], $result['period_id'], $result['skipped'], esc_url( $chart_url ) );
				\Charts\Core\Notify::success( $msg, __( 'Sync Sequence Complete', 'charts' ) );
				return $result['run_id'] ?? true;
			} else {
				\Charts\Core\Notify::success( sprintf( __( 'Segment imported: %d entries merged into the nexus.', 'charts' ), intval( $result ) ), __( 'Partial Sync Successful', 'charts' ) );
				return true;
			}
		} catch ( \Exception $e ) {
			\Charts\Core\Notify::error( $e->getMessage(), __( 'Internal Engine Exception', 'charts' ) );
			return false;
		}
	}

	/**
	 * Process YouTube CSV Upload.
	 */
	private static function process_youtube_csv_upload() {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) return;
		check_admin_referer( 'charts_admin_action' );

		if ( empty( $_FILES['youtube_csv']['tmp_name'] ) ) {
			\Charts\Core\Notify::warning( __( 'No file detected for YouTube ingestion. Please source a valid CSV segment.', 'charts' ), __( 'Input Required', 'charts' ) );
			return;
		}

		$meta = array(
			'chart_id'    => intval( $_POST['chart_id'] ?? 0 ),
			'country'     => sanitize_text_field( $_POST['country'] ?? 'eg' ),
			'chart_type'  => sanitize_text_field( $_POST['chart_type'] ?? 'top-songs' ),
			'frequency'   => sanitize_text_field( $_POST['frequency'] ?? 'weekly' ),
			'period_date' => sanitize_text_field( $_POST['period_date'] ?? '' ),
			'source_name' => sanitize_text_field( $_POST['source_name'] ?? '' ),
			'item_type'   => sanitize_text_field( $_POST['item_type'] ?? 'track' ),
			'filename'    => sanitize_text_field( $_FILES['youtube_csv']['name'] ?? '' ),
		);

		$csv_content = file_get_contents( $_FILES['youtube_csv']['tmp_name'] );
		if ( ! $csv_content ) {
			\Charts\Core\Notify::error( __( 'Failed to read the YouTube segment stream from temp storage.', 'charts' ), __( 'Critical I/O Failure', 'charts' ) );
			return;
		}

		try {
			$importer = new \Charts\Services\YouTubeCsvImporter();
			$result   = $importer->run( $csv_content, $meta );

			if ( is_wp_error( $result ) ) {
				\Charts\Core\Notify::error( $result->get_error_message(), __( 'Import Pipeline Failure', 'charts' ) );
			} elseif ( is_array( $result ) ) {
				// Recalculate Intelligence
				\Charts\Core\Intelligence::recalculate_all();

				$chart_url = home_url( '/charts/' );
				if ( ! empty($meta['chart_id']) ) {
					$c_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $meta['chart_id'] ) );
					if ( $c_slug ) $chart_url = home_url( '/charts/' . $c_slug . '/' );
				}
				
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
					$msg .= ' ' . sprintf( __( '%d items excluded by filters.', 'charts' ), $result['skipped'] );
				}

				$msg .= sprintf( ' <a href="%s" target="_blank">%s &rarr;</a>', esc_url( $chart_url ), __( 'View Nexus', 'charts' ) );

				\Charts\Core\Notify::success( $msg, __( 'YouTube Sync Successful', 'charts' ) );

				if ( ! empty( $result['warnings'] ) ) {
					foreach ( $result['warnings'] as $warn ) {
						\Charts\Core\Notify::warning( $warn, __( 'Pipeline Warning', 'charts' ) );
					}
				}
				return $result['run_id'] ?? true;
			}
			return false;
		} catch ( \Exception $e ) {
			\Charts\Core\Notify::error( $e->getMessage(), __( 'Internal Engine Exception', 'charts' ) );
			return false;
		}
	}







	public static function render_entities() {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			self::render_view( 'entity-edit' );
		} else {
			self::render_view( 'entities' );
		}
	}

	/** Render the bulk clip-to-track linking workspace. */
	public static function render_clip_track_linker() {
		self::render_view( 'clip-track-linker' );
	}

	/** Save the track selected for each clip on the current linking page. */
	private static function persist_clip_track_mappings() {
		global $wpdb;
		$submitted = wp_unslash( $_POST['clip_track_ids'] ?? array() );
		if ( ! is_array( $submitted ) ) {
			return new \WP_Error( 'invalid_clip_mappings', __( 'The submitted clip links were invalid.', 'charts' ) );
		}

		$clips_table = $wpdb->prefix . 'charts_videos';
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$validated = array();
		foreach ( $submitted as $clip_id => $track_id ) {
			$clip_id = absint( $clip_id );
			$track_id = absint( $track_id );
			if ( ! $clip_id || ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $clips_table WHERE id = %d", $clip_id ) ) ) {
				return new \WP_Error( 'clip_not_found', __( 'A clip in this page no longer exists. Reload and try again.', 'charts' ) );
			}
			if ( $track_id && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $tracks_table WHERE id = %d", $track_id ) ) ) {
				return new \WP_Error( 'track_not_found', __( 'One of the selected tracks no longer exists. Search and select it again.', 'charts' ) );
			}
			$validated[ $clip_id ] = $track_id ?: null;
		}

		foreach ( $validated as $clip_id => $track_id ) {
			$updated = $wpdb->update( $clips_table, array( 'related_track_id' => $track_id ), array( 'id' => $clip_id ) );
			if ( $updated === false ) {
				return new \WP_Error( 'clip_link_save_failed', __( 'Some clip links could not be saved. Please retry this page.', 'charts' ) );
			}
		}

		return count( $validated );
	}

	/** Link currently unlinked clips only when their matching track is unique. */
	private static function auto_link_clips_to_tracks() {
		global $wpdb;
		$matches = self::get_auto_clip_track_matches();
		if ( ! $matches ) return 0;

		$clips_table = $wpdb->prefix . 'charts_videos';
		$by_track = array();
		foreach ( $matches as $match ) {
			$by_track[ (int) $match->track_id ][] = (int) $match->clip_id;
		}

		$linked = 0;
		foreach ( $by_track as $track_id => $clip_ids ) {
			foreach ( array_chunk( $clip_ids, 200 ) as $chunk ) {
				$ids = implode( ',', array_map( 'absint', $chunk ) );
				$result = $wpdb->query( $wpdb->prepare( "UPDATE $clips_table SET related_track_id = %d WHERE id IN ($ids) AND (related_track_id IS NULL OR related_track_id = 0)", $track_id ) );
				if ( $result === false ) {
					return new \WP_Error( 'clip_auto_link_failed', __( 'The automatic links could not be saved. Please retry.', 'charts' ) );
				}
				$linked += (int) $result;
			}
		}

		return $linked;
	}

	/** Return safe, unique candidates by matching YouTube ID or exact normalized title plus primary artist. */
	public static function count_auto_clip_track_matches() {
		return self::get_auto_clip_track_matches( true );
	}

	private static function get_auto_clip_track_matches( $count_only = false ) {
		global $wpdb;
		$clips_table = $wpdb->prefix . 'charts_videos';
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$from_sql = "
			FROM $clips_table v
			LEFT JOIN (
				SELECT youtube_id, MIN(id) AS track_id
				FROM $tracks_table
				WHERE youtube_id IS NOT NULL AND youtube_id != ''
				GROUP BY youtube_id
				HAVING COUNT(*) = 1
			) y ON y.youtube_id = v.youtube_id AND v.youtube_id IS NOT NULL AND v.youtube_id != ''
			LEFT JOIN (
				SELECT normalized_title, primary_artist_id, MIN(id) AS track_id
				FROM $tracks_table
				WHERE normalized_title IS NOT NULL AND normalized_title != '' AND primary_artist_id IS NOT NULL AND primary_artist_id > 0
				GROUP BY normalized_title, primary_artist_id
				HAVING COUNT(*) = 1
			) n ON n.normalized_title = v.normalized_title AND n.primary_artist_id = v.primary_artist_id
			WHERE (v.related_track_id IS NULL OR v.related_track_id = 0)
				AND (y.track_id IS NOT NULL OR n.track_id IS NOT NULL)
		";
		if ( $count_only ) return (int) $wpdb->get_var( "SELECT COUNT(*) $from_sql" );
		return $wpdb->get_results( "SELECT v.id AS clip_id, COALESCE(y.track_id, n.track_id) AS track_id $from_sql ORDER BY v.id ASC" );
	}

	public static function render_insights() {
		wp_safe_redirect( admin_url( 'admin.php?page=charts-intelligence&tab=insights' ) );
		exit;
	}

	public static function render_intelligence() {
		$tab = sanitize_key( $_GET['tab'] ?? 'signals' );
		if ( $tab === 'forecast' ) {
			self::render_view( 'forecast' );
		} elseif ( $tab === 'insights' ) {
			self::render_view( 'insights' );
		} else {
			self::render_view( 'intelligence' );
		}
	}

	public static function render_forecast() {
		wp_safe_redirect( admin_url( 'admin.php?page=charts-intelligence&tab=forecast' ) );
		exit;
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

			if ($result > 0) {
				wp_send_json_success( array( 
					'message' => sprintf( __( 'Pipeline sync complete: %d segments crystallized in the nexus.', 'charts' ), $result ),
					'count'   => $result
				) );
			} else {
				wp_send_json_success( array( 
					'message' => __( 'Sync window complete: No new segments required matching or creation.', 'charts' ),
					'count'   => 0
				) );
			}

		} catch ( \Exception $e ) {
			error_log( 'Charts Import Error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public static function handle_recalculate_forecast() {
		$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
		if ( !empty($nonce) && ! wp_verify_nonce( $nonce, 'charts_admin_action' ) ) {
			// JS might be passing a different nonce, but we rely on capability check
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'charts' ) ) );
		}

		try {
			\Charts\Services\PredictionEngine::calculate_all();
			wp_send_json_success( array( 'message' => __( 'Forecast recalibrated successfully.', 'charts' ) ) );
		} catch ( \Exception $e ) {
			error_log( 'Charts Forecast Recalculate Error: ' . $e->getMessage() );
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
		$table = $wpdb->prefix . 'charts_artists';

		$limit  = 20;
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$mode   = $_POST['mode'] ?? 'missing';
		$ids    = isset($_POST['ids']) ? array_map('intval', explode(',', $_POST['ids'])) : [];

		if ( $mode === 'selected' && ! empty( $ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$artists = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE id IN ($placeholders) LIMIT $limit", ...$ids ) );
		} elseif ( $mode === 'missing' ) {
			$artists = $wpdb->get_results( "SELECT * FROM $table WHERE (spotify_id IS NULL OR spotify_id = '') OR (image IS NULL OR image = '') ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		} else {
			$artists = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		}

		if ( empty( $artists ) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
		$spotify_client  = new \Charts\Services\SpotifyApiClient();
		$youtube_service = new \Charts\Services\YouTubeEnrichmentService();
		$youtube_client  = new \Charts\Services\YouTubeApiClient();

		$updated         = 0;
		$spotify_linked  = 0;
		$youtube_linked  = 0;

		foreach ( $artists as $artist ) {
			$has_update = false;
			$s_id       = $artist->spotify_id;

			// 1. Spotify Resolution
			if ( empty( $s_id ) ) {
				$results = $spotify_client->search_artist( $artist->display_name, 1 );
				if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
					$s_id = $results[0]['id'];
					$wpdb->update( $table, array( 'spotify_id' => $s_id ), array( 'id' => $artist->id ) );
					$spotify_linked++;
					$has_update = true;
				}
			}

			// 2. Spotify Enrichment
			if ( ! empty( $s_id ) ) {
				$res = $spotify_service->enrich_artist( $artist->id );
				if ( ! is_wp_error( $res ) && $res ) {
					$has_update = true;
				}
			}

			// 3. YouTube Resolution (Optional but helpful)
			$meta = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
			$y_id = $meta['youtube_channel_id'] ?? null;
			if ( empty( $y_id ) ) {
				$yt_results = $youtube_client->search_channels( $artist->display_name, 1 );
				if ( ! empty( $yt_results ) && ! is_wp_error( $yt_results ) ) {
					$y_id = $yt_results[0]['id']['channelId'] ?? null;
					if ( $y_id ) {
						$meta['youtube_channel_id'] = $y_id;
						$wpdb->update( $table, array( 'metadata_json' => json_encode( $meta ) ), array( 'id' => $artist->id ) );
						$youtube_linked++;
						$has_update = true;
					}
				}
			}

			if ( ! empty( $y_id ) ) {
				$youtube_service->enrich_artist( $artist->id );
				$has_update = true;
			}

			if ( $has_update ) $updated++;
		}

		wp_send_json_success( array(
			'complete'       => false,
			'processed'      => count( $artists ),
			'updated'        => $updated,
			'spotify_linked' => $spotify_linked,
			'youtube_linked' => $youtube_linked,
			'next_offset'    => $offset + count( $artists )
		) );
	}

	public static function handle_sync_tracks() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );

		global $wpdb;
		$table         = $wpdb->prefix . 'charts_tracks';
		$artists_table = $wpdb->prefix . 'charts_artists';

		$limit  = 20;
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$mode   = $_POST['mode'] ?? 'missing';
		$ids    = isset($_POST['ids']) ? array_map('intval', explode(',', $_POST['ids'])) : [];

		if ( $mode === 'selected' && ! empty( $ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$tracks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE id IN ($placeholders) LIMIT $limit", ...$ids ) );
		} elseif ( $mode === 'missing' ) {
			$tracks = $wpdb->get_results( "SELECT * FROM $table WHERE (spotify_id IS NULL OR spotify_id = '') OR (cover_image IS NULL OR cover_image = '') ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		} else {
			$tracks = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		}

		if ( empty( $tracks ) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
		$spotify_client  = new \Charts\Services\SpotifyApiClient();

		$updated         = 0;
		$spotify_linked  = 0;
		$covers_updated  = 0;

		foreach ( $tracks as $track ) {
			$has_update = false;
			$s_id       = $track->spotify_id;

			// 1. Spotify Resolution
			if ( empty( $s_id ) ) {
				$art_name = $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM $artists_table WHERE id = %d", $track->primary_artist_id ) );
				$query    = $track->title . ' ' . ($art_name ?? '');
				$results  = $spotify_client->search_track( $query, 1 );
				if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
					$s_id = $results[0]['id'];
					$wpdb->update( $table, array( 'spotify_id' => $s_id ), array( 'id' => $track->id ) );
					$spotify_linked++;
					$has_update = true;
				}
			}

			// 2. Spotify Enrichment
			if ( ! empty( $s_id ) ) {
				$res = $spotify_service->enrich_track( $track->id );
				if ( ! is_wp_error( $res ) && $res ) {
					$has_update = true;
					$covers_updated++;
				}
			}

			if ( $has_update ) $updated++;
		}

		wp_send_json_success( array(
			'complete'       => false,
			'processed'      => count( $tracks ),
			'updated'        => $updated,
			'spotify_linked' => $spotify_linked,
			'covers_updated' => $covers_updated,
			'next_offset'    => $offset + count( $tracks )
		) );
	}

	/** Sync YouTube IDs and metadata for selected clips or a paged clip batch. */
	public static function handle_sync_videos() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'charts' ) ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'charts' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'charts_videos';
		$limit = 20;
		$offset = max( 0, intval( $_POST['offset'] ?? 0 ) );
		$mode = sanitize_text_field( $_POST['mode'] ?? 'missing' );
		$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'intval', explode( ',', $_POST['ids'] ) ) ) : array();

		if ( $mode === 'selected' && ! empty( $ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$videos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE id IN ($placeholders) LIMIT $limit", ...$ids ) );
		} elseif ( $mode === 'missing' ) {
			$videos = $wpdb->get_results( "SELECT * FROM $table WHERE youtube_id IS NULL OR youtube_id = '' OR thumbnail IS NULL OR thumbnail = '' ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		} else {
			$videos = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		}

		if ( empty( $videos ) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$youtube = new \Charts\Services\YouTubeApiClient();
		if ( ! $youtube->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Configure a YouTube API key before syncing clips.', 'charts' ) ) );
		}

		$updated = 0;
		$youtube_linked = 0;
		$covers_updated = 0;
		foreach ( $videos as $video ) {
			$youtube_id = trim( (string) $video->youtube_id );
			if ( $youtube_id === '' ) {
				$artist_name = $video->primary_artist_id
					? $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $video->primary_artist_id ) )
					: '';
				$query = trim( $video->title . ' ' . $artist_name );
				$matches = $youtube->search_videos( $query, 1 );
				if ( ! is_wp_error( $matches ) && ! empty( $matches[0]['id']['videoId'] ) ) {
					$youtube_id = sanitize_text_field( $matches[0]['id']['videoId'] );
					$youtube_linked++;
				}
			}
			if ( $youtube_id === '' ) {
				continue;
			}

			$details = $youtube->get_videos( array( $youtube_id ) );
			if ( is_wp_error( $details ) || empty( $details[0] ) ) {
				continue;
			}

			$snippet = $details[0]['snippet'] ?? array();
			$statistics = $details[0]['statistics'] ?? array();
			$thumbnails = $snippet['thumbnails'] ?? array();
			$thumbnail = $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? $thumbnails['default']['url'] ?? '';
			$metadata = ! empty( $video->metadata_json ) ? json_decode( $video->metadata_json, true ) : array();
			if ( ! is_array( $metadata ) ) $metadata = array();
			$metadata['youtube_views'] = intval( $statistics['viewCount'] ?? 0 );
			$metadata['youtube_channel_title'] = $snippet['channelTitle'] ?? '';
			$metadata['youtube_last_sync'] = current_time( 'mysql' );
			$metadata['sync_status'] = 'synced';

			$update = array( 'youtube_id' => $youtube_id, 'metadata_json' => wp_json_encode( $metadata ) );
			if ( $thumbnail !== '' ) {
				$update['thumbnail'] = esc_url_raw( $thumbnail );
				$covers_updated++;
			}
			if ( empty( $video->video_url ) ) {
				$update['video_url'] = 'https://www.youtube.com/watch?v=' . rawurlencode( $youtube_id );
			}
			if ( $wpdb->update( $table, $update, array( 'id' => $video->id ) ) !== false ) {
				$updated++;
			}
		}

		wp_send_json_success( array(
			'complete' => false,
			'processed' => count( $videos ),
			'updated' => $updated,
			'youtube_linked' => $youtube_linked,
			'covers_updated' => $covers_updated,
			'next_offset' => $offset + count( $videos ),
		) );
	}

	/**
	 * Sync Spotify IDs, covers, release dates, and tracklists for albums.
	 */
	public static function handle_sync_albums() {
		if ( ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );

		global $wpdb;
		$table         = $wpdb->prefix . 'charts_albums';
		$artists_table = $wpdb->prefix . 'charts_artists';

		$limit  = 20;
		$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$mode   = $_POST['mode'] ?? 'missing';
		$ids    = isset( $_POST['ids'] ) ? array_map( 'intval', explode( ',', $_POST['ids'] ) ) : array();

		if ( $mode === 'selected' && ! empty( $ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE id IN ($placeholders) LIMIT $limit", ...$ids ) );
		} elseif ( $mode === 'missing' ) {
			$albums = $wpdb->get_results( "SELECT * FROM $table WHERE (spotify_id IS NULL OR spotify_id = '') OR (cover_image IS NULL OR cover_image = '') ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		} else {
			$albums = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC LIMIT $limit OFFSET $offset" );
		}

		if ( empty( $albums ) ) {
			wp_send_json_success( array( 'complete' => true, 'processed' => 0 ) );
		}

		$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
		$spotify_client  = new \Charts\Services\SpotifyApiClient();

		$updated        = 0;
		$spotify_linked = 0;
		$covers_updated = 0;

		foreach ( $albums as $album ) {
			$has_update = false;
			$s_id       = $album->spotify_id;

			// 1. Spotify Resolution
			if ( empty( $s_id ) ) {
				$art_name = ! empty( $album->primary_artist_id ) ? $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM $artists_table WHERE id = %d", $album->primary_artist_id ) ) : '';
				$query    = $album->title . ( $art_name ? ' ' . $art_name : '' );
				$results  = $spotify_client->search_album( $query, 1 );
				if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
					$s_id = $results[0]['id'];
					$wpdb->update( $table, array( 'spotify_id' => $s_id ), array( 'id' => $album->id ) );
					$spotify_linked++;
					$has_update = true;
				}
			}

			// 2. Spotify Enrichment
			if ( ! empty( $s_id ) ) {
				$res = $spotify_service->enrich_album( $album->id );
				if ( ! is_wp_error( $res ) && $res ) {
					$has_update = true;
					$covers_updated++;
				}
			}

			if ( $has_update ) $updated++;
		}

		wp_send_json_success( array(
			'complete'       => false,
			'processed'      => count( $albums ),
			'updated'        => $updated,
			'spotify_linked' => $spotify_linked,
			'covers_updated' => $covers_updated,
			'next_offset'    => $offset + count( $albums )
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
		$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_elementor_%' OR option_name LIKE '_transient_timeout_elementor_%'" );

		// 1. WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		
		// 2. LiteSpeed Cache
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		// 3. W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// 4. WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// 5. Autoptimize
		if ( class_exists( 'autoptimizeCache' ) ) {
			autoptimizeCache::clearall();
		}

		// 6. Redis Object Cache
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		
		// 7. SG Optimizer (SiteGround)
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
	}

	/** Keep the denormalized chart rows in sync with the canonical entity record. */
	private static function sync_entity_chart_entries( $type, $id, $old_name = '', $old_name_en = '' ) {
		global $wpdb;
		$id = absint( $id );
		if ( ! $id ) return;

		if ( $type === 'artist' ) {
			$artist = $wpdb->get_row( $wpdb->prepare( "SELECT display_name, display_name_en, slug, spotify_id FROM {$wpdb->prefix}charts_artists WHERE id = %d", $id ) );
			if ( ! $artist ) return;
			$wpdb->update( "{$wpdb->prefix}charts_entries", array(
				'artist_names' => $artist->display_name,
				'artist_names_en' => $artist->display_name_en,
				'track_name' => $artist->display_name,
				'item_slug' => $artist->slug,
				'spotify_id' => $artist->spotify_id,
			), array( 'item_type' => 'artist', 'item_id' => $id ) );

			// Update the cached display string on charted tracks and videos that use this artist.
			if ( ( $old_name !== '' && $old_name !== $artist->display_name ) || ( $old_name_en !== '' && $old_name_en !== $artist->display_name_en ) ) {
				$related = $wpdb->get_results( $wpdb->prepare(
					"SELECT e.id, e.artist_names, e.artist_names_en FROM {$wpdb->prefix}charts_entries e
					 LEFT JOIN {$wpdb->prefix}charts_tracks t ON e.item_type = 'track' AND t.id = e.item_id
					 LEFT JOIN {$wpdb->prefix}charts_videos v ON e.item_type = 'video' AND v.id = e.item_id
					 LEFT JOIN {$wpdb->prefix}charts_track_artists ta ON ta.track_id = t.id AND ta.artist_id = %d
					 LEFT JOIN {$wpdb->prefix}charts_video_artists va ON va.video_id = v.id AND va.artist_id = %d
					 WHERE (t.primary_artist_id = %d OR v.primary_artist_id = %d OR ta.artist_id = %d OR va.artist_id = %d)
					 GROUP BY e.id",
					$id, $id, $id, $id, $id, $id
				) );
				foreach ( $related as $entry ) {
					$entry_data = array();
					if ( $old_name !== '' && $old_name !== $artist->display_name ) {
						$updated_names = preg_replace( '/(?<![\\p{L}\\p{N}])' . preg_quote( $old_name, '/' ) . '(?![\\p{L}\\p{N}])/iu', $artist->display_name, (string) $entry->artist_names );
						if ( $updated_names !== null && $updated_names !== $entry->artist_names ) $entry_data['artist_names'] = $updated_names;
					}
					if ( $old_name_en !== '' && $old_name_en !== $artist->display_name_en ) {
						$updated_names_en = preg_replace( '/(?<![\\p{L}\\p{N}])' . preg_quote( $old_name_en, '/' ) . '(?![\\p{L}\\p{N}])/iu', (string) $artist->display_name_en, (string) $entry->artist_names_en );
						if ( $updated_names_en !== null && $updated_names_en !== $entry->artist_names_en ) $entry_data['artist_names_en'] = $updated_names_en;
					}
					if ( $entry_data ) {
						$wpdb->update( "{$wpdb->prefix}charts_entries", $entry_data, array( 'id' => $entry->id ) );
					}
				}
			}
			return;
		}

		if ( $type === 'track' ) {
			$entity = $wpdb->get_row( $wpdb->prepare( "SELECT title, slug, cover_image, primary_artist_id, spotify_id, youtube_id FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $id ) );
			$entry_type = 'track';
			$image = $entity ? $entity->cover_image : null;
		} elseif ( $type === 'video' ) {
			$entity = $wpdb->get_row( $wpdb->prepare( "SELECT title, slug, thumbnail AS cover_image, primary_artist_id, youtube_id FROM {$wpdb->prefix}charts_videos WHERE id = %d", $id ) );
			$entry_type = 'video';
			$image = $entity ? $entity->cover_image : null;
		} else {
			return;
		}
		if ( ! $entity ) return;
		$data = array( 'track_name' => $entity->title, 'item_slug' => $entity->slug, 'cover_image' => $image, 'youtube_id' => $entity->youtube_id );
		if ( $type === 'track' ) $data['spotify_id'] = $entity->spotify_id;
		$wpdb->update( "{$wpdb->prefix}charts_entries", $data, array( 'item_type' => $entry_type, 'item_id' => $id ) );
	}

	/**
	 * Process the Name Sync CSV upload.
	 * Reads arabic_artist, english_artist, arabic_title, english_title from a CSV
	 * and updates matching records in charts_artists / charts_tracks.
	 */
	private static function process_name_sync(): array {
		global $wpdb;

		$result = [
			'total'           => 0,
			'artists_updated' => 0,
			'tracks_updated'  => 0,
			'videos_updated'  => 0,
			'albums_updated'  => 0,
			'slugs_updated'   => 0,
			'not_found'       => 0,
			'log'             => [],
		];

		// ── File validation ──────────────────────────────────────────────────
		if ( empty( $_FILES['name_sync_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['name_sync_file']['tmp_name'] ) ) {
			$result['log'][] = 'ERROR: No file uploaded.';
			return $result;
		}

		$csv_content = file_get_contents( $_FILES['name_sync_file']['tmp_name'] );
		if ( ! $csv_content ) {
			$result['log'][] = 'ERROR: Could not read file.';
			return $result;
		}

		// ── Parse CSV ────────────────────────────────────────────────────────
		$lines = array_filter( explode( "\n", str_replace( "\r\n", "\n", str_replace( "\r", "\n", $csv_content ) ) ) );
		if ( empty( $lines ) ) {
			$result['log'][] = 'ERROR: Empty CSV.';
			return $result;
		}

		$header_line = array_shift( $lines );
		$headers     = array_map( 'strtolower', array_map( 'trim', str_getcsv( $header_line ) ) );
		// Normalise header keys (strip BOM, spaces)
		$headers = array_map( function( $h ) { return preg_replace('/[^a-z0-9_]/', '_', trim( $h, "\xEF\xBB\xBF " ) ); }, $headers );

		// ── Options ──────────────────────────────────────────────────────────
		$sync_artists      = ! empty( $_POST['sync_artists'] );
		$sync_tracks       = ! empty( $_POST['sync_tracks'] );
		$sync_videos       = ! empty( $_POST['sync_videos'] );
		$sync_albums       = ! empty( $_POST['sync_albums'] );
		$overwrite_en      = ! empty( $_POST['overwrite_existing'] );
		$refresh_slugs     = ! empty( $_POST['refresh_slugs'] );

		// ── Helper: slug from english name ───────────────────────────────────
		$make_slug = function( string $en_name, string $table, ?int $exclude_id = null ): string {
			global $wpdb;
			$base   = \Charts\Services\Slugger::make( $en_name, 'entity' );
			$slug   = $base;
			$suffix = 2;
			while ( true ) {
				$exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $table WHERE slug = %s" . ( $exclude_id ? " AND id != %d" : "" ),
					...( $exclude_id ? [ $slug, $exclude_id ] : [ $slug ] )
				) );
				if ( ! $exists ) break;
				$slug = $base . '-' . $suffix++;
			}
			return $slug;
		};

		// ── Row loop ─────────────────────────────────────────────────────────
		foreach ( $lines as $raw_line ) {
			$raw_line = trim( $raw_line );
			if ( $raw_line === '' ) continue;

			$cols     = str_getcsv( $raw_line );
			$row      = array_combine( $headers, array_pad( array_slice( $cols, 0, count( $headers ) ), count( $headers ), '' ) );
			$result['total']++;

			$ar_artist = trim( $row['arabic_artist']  ?? '' );
			$en_artist = trim( $row['english_artist'] ?? '' );
			$ar_title  = trim( $row['arabic_title']   ?? '' );
			$en_title  = trim( $row['english_title']  ?? '' );

			// ── ARTIST sync ──────────────────────────────────────────────────
			if ( $sync_artists && $ar_artist !== '' ) {
				$a_table     = $wpdb->prefix . 'charts_artists';
				$artist_row  = $wpdb->get_row( $wpdb->prepare(
					"SELECT id, display_name_en, slug FROM $a_table WHERE display_name = %s OR normalized_name = %s LIMIT 1",
					$ar_artist, mb_strtolower( $ar_artist )
				) );

				if ( ! $artist_row ) {
					// Fallback: try English name in display_name_en
					if ( $en_artist !== '' ) {
						$artist_row = $wpdb->get_row( $wpdb->prepare(
							"SELECT id, display_name_en, slug FROM $a_table WHERE display_name_en = %s LIMIT 1",
							$en_artist
						) );
					}
				}

				if ( $artist_row ) {
					$updates = [];
					// Update English name
					if ( $en_artist !== '' && ( $overwrite_en || empty( $artist_row->display_name_en ) ) ) {
						$updates['display_name_en'] = $en_artist;
					}
					// Refresh slug based on english name
					$slug_base = $updates['display_name_en'] ?? $artist_row->display_name_en ?? '';
					if ( $refresh_slugs && $slug_base !== '' ) {
						$new_slug = $make_slug( $slug_base, $a_table, (int) $artist_row->id );
						if ( $new_slug !== $artist_row->slug ) {
							$updates['slug'] = $new_slug;
							$result['slugs_updated']++;
							$result['log'][] = "Artist [{$ar_artist}]: slug {$artist_row->slug} → {$new_slug}";
						}
					}
					if ( ! empty( $updates ) ) {
						$wpdb->update( $a_table, $updates, [ 'id' => $artist_row->id ] );
						$result['artists_updated']++;
						$result['log'][] = "Artist [{$ar_artist}] → en: " . ( $updates['display_name_en'] ?? '(no change)' );
					}
				} else {
					$result['not_found']++;
					$result['log'][] = "NOT FOUND — Artist: {$ar_artist}";
				}
			}

			// ── TRACK sync ───────────────────────────────────────────────────
			if ( $sync_tracks && $ar_title !== '' ) {
				$t_table   = $wpdb->prefix . 'charts_tracks';
				$track_row = $wpdb->get_row( $wpdb->prepare(
					"SELECT id, title_en, slug FROM $t_table WHERE title = %s OR normalized_title = %s LIMIT 1",
					$ar_title, mb_strtolower( $ar_title )
				) );

				if ( ! $track_row && $en_title !== '' ) {
					$track_row = $wpdb->get_row( $wpdb->prepare(
						"SELECT id, title_en, slug FROM $t_table WHERE title_en = %s LIMIT 1",
						$en_title
					) );
				}

				if ( $track_row ) {
					$updates = [];
					if ( $en_title !== '' && ( $overwrite_en || empty( $track_row->title_en ) ) ) {
						$updates['title_en'] = $en_title;
					}
					$slug_base = $updates['title_en'] ?? $track_row->title_en ?? '';
					if ( $refresh_slugs && $slug_base !== '' ) {
						$new_slug = $make_slug( $slug_base, $t_table, (int) $track_row->id );
						if ( $new_slug !== $track_row->slug ) {
							$updates['slug'] = $new_slug;
							$result['slugs_updated']++;
							$result['log'][] = "Track [{$ar_title}]: slug {$track_row->slug} → {$new_slug}";
						}
					}
					if ( ! empty( $updates ) ) {
						$wpdb->update( $t_table, $updates, [ 'id' => $track_row->id ] );
						$result['tracks_updated']++;
						$result['log'][] = "Track [{$ar_title}] → en: " . ( $updates['title_en'] ?? '(no change)' );
					}
				} else {
					$result['not_found']++;
					$result['log'][] = "NOT FOUND — Track: {$ar_title}";
				}
			}
			// ── VIDEO (CLIP) sync ────────────────────────────────────────────
			if ( $sync_videos && $ar_title !== '' ) {
				$v_table   = $wpdb->prefix . 'charts_videos';
				$video_row = $wpdb->get_row( $wpdb->prepare(
					"SELECT id, title_en, slug FROM $v_table WHERE title = %s OR normalized_title = %s LIMIT 1",
					$ar_title, mb_strtolower( $ar_title )
				) );

				if ( ! $video_row && $en_title !== '' ) {
					$video_row = $wpdb->get_row( $wpdb->prepare(
						"SELECT id, title_en, slug FROM $v_table WHERE title_en = %s LIMIT 1",
						$en_title
					) );
				}

				if ( $video_row ) {
					$updates = [];
					if ( $en_title !== '' && ( $overwrite_en || empty( $video_row->title_en ) ) ) {
						$updates['title_en'] = $en_title;
					}
					$slug_base = $updates['title_en'] ?? $video_row->title_en ?? '';
					if ( $refresh_slugs && $slug_base !== '' ) {
						$new_slug = $make_slug( $slug_base, $v_table, (int) $video_row->id );
						if ( $new_slug !== $video_row->slug ) {
							$updates['slug'] = $new_slug;
							$result['slugs_updated']++;
							$result['log'][] = "Clip [{$ar_title}]: slug {$video_row->slug} → {$new_slug}";
						}
					}
					if ( ! empty( $updates ) ) {
						$wpdb->update( $v_table, $updates, [ 'id' => $video_row->id ] );
						$result['videos_updated']++;
						$result['log'][] = "Clip [{$ar_title}] → en: " . ( $updates['title_en'] ?? '(no change)' );
					}
				} else {
					$result['not_found']++;
					$result['log'][] = "NOT FOUND — Clip: {$ar_title}";
				}
			}

			// ── ALBUM sync ───────────────────────────────────────────────────
			if ( $sync_albums && $ar_title !== '' ) {
				$al_table   = $wpdb->prefix . 'charts_albums';
				$album_row = $wpdb->get_row( $wpdb->prepare(
					"SELECT id, title_en, slug FROM $al_table WHERE title = %s OR normalized_title = %s LIMIT 1",
					$ar_title, mb_strtolower( $ar_title )
				) );

				if ( ! $album_row && $en_title !== '' ) {
					$album_row = $wpdb->get_row( $wpdb->prepare(
						"SELECT id, title_en, slug FROM $al_table WHERE title_en = %s LIMIT 1",
						$en_title
					) );
				}

				if ( $album_row ) {
					$updates = [];
					if ( $en_title !== '' && ( $overwrite_en || empty( $album_row->title_en ) ) ) {
						$updates['title_en'] = $en_title;
					}
					$slug_base = $updates['title_en'] ?? $album_row->title_en ?? '';
					if ( $refresh_slugs && $slug_base !== '' ) {
						$new_slug = $make_slug( $slug_base, $al_table, (int) $album_row->id );
						if ( $new_slug !== $album_row->slug ) {
							$updates['slug'] = $new_slug;
							$result['slugs_updated']++;
							$result['log'][] = "Album [{$ar_title}]: slug {$album_row->slug} → {$new_slug}";
						}
					}
					if ( ! empty( $updates ) ) {
						$wpdb->update( $al_table, $updates, [ 'id' => $album_row->id ] );
						$result['albums_updated']++;
						$result['log'][] = "Album [{$ar_title}] → en: " . ( $updates['title_en'] ?? '(no change)' );
					}
				} else {
					$result['not_found']++;
					$result['log'][] = "NOT FOUND — Album: {$ar_title}";
				}
			}
		}

		// Clear frontend caches after mass update
		if ( $result['artists_updated'] > 0 || $result['tracks_updated'] > 0 ) {
			self::clear_frontend_caches();
		}

		return $result;
	}

	/**
	 * Export entities for Name Sync.
	 */
	private static function process_name_sync_export() {
		global $wpdb;
		$type = sanitize_key( $_REQUEST['export_type'] ?? 'artists' );
		
		$filename = "charts_name_sync_{$type}_" . date('Y-m-d') . ".csv";
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		
		// Output BOM for Excel UTF-8 compatibility
		echo "\xEF\xBB\xBF";
		
		$output = fopen('php://output', 'w');
		fputcsv($output, ['arabic_artist', 'english_artist', 'arabic_title', 'english_title', 'slug']);
		
		if ( $type === 'artists' ) {
			$table = $wpdb->prefix . 'charts_artists';
			$results = $wpdb->get_results( "SELECT display_name, display_name_en, slug FROM $table" );
			foreach ( $results as $r ) {
				fputcsv($output, [
					$r->display_name,
					$r->display_name_en,
					'',
					'',
					$r->slug
				]);
			}
		} elseif ( in_array( $type, ['tracks', 'videos', 'albums'] ) ) {
			$table = $wpdb->prefix . "charts_{$type}";
			$artist_table = $wpdb->prefix . 'charts_artists';
			
			$col_title = $type === 'tracks' ? 'title' : 'title'; // wait, what about videos/albums?
			// Let's dynamically check:
			$title_col = ($type === 'videos') ? 'title' : 'title';
			$title_en_col = ($type === 'videos') ? 'title_en' : 'title_en';
			if ($type === 'albums') {
				$title_en_col = 'title_en';
			}

			// In current schema, charts_tracks has title, title_en, slug
			// charts_videos has title, title_en, slug
			// charts_albums has title, title_en, slug
			
			$query = "
				SELECT e.title, e.title_en, e.slug, a.display_name as artist_ar, a.display_name_en as artist_en
				FROM $table e
				LEFT JOIN $artist_table a ON e.primary_artist_id = a.id
			";
			
			$results = $wpdb->get_results( $query );
			foreach ( $results as $r ) {
				fputcsv($output, [
					$r->artist_ar,
					$r->artist_en,
					$r->title,
					$r->title_en,
					$r->slug
				]);
			}
		}
		
		fclose($output);
		exit;
	}

	/**
	 * DESTRUCTIVE: Wipes all plugin data from the database.
	 */
	private static function wipe_all_data( $wipe_settings = false ) {
		global $wpdb;

		$report = [
			'entries'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries" ),
			'tracks'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks" ),
			'artists'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists" ),
			'definitions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions" ),
		];

		// 1. Tables to truncate
		$tables = array(
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
			'charts_intelligence',
		);

		if ( $wipe_settings ) {
			$tables[] = 'charts_sources';
			$tables[] = 'charts_definitions';
		}

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
				'charts_soundcharts_client_id',
				'charts_soundcharts_client_secret',
				'charts_soundcharts_team_id',
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
				'kcharts_settings_v2',
				'kcharts_theme_options'
			);
			foreach ( $options as $opt ) {
				delete_option( $opt );
			}

			// Clear all plugin transients
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_kc_%' OR option_name LIKE '_transient_timeout_kc_%'" );
		}

		// Ensure we trigger a re-setup if needed
		delete_option( 'charts_setup_complete' );

		return $report;
	}

	/**
	 * Helper to safely render an admin view.
	 */
	/**
	 * AJAX logic to search for entities to add to manual charts.
	 */
	public static function handle_search_entities() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$type  = sanitize_text_field( $_POST['type'] ?? 'track' );
		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$id    = intval( $_POST['id'] ?? 0 );

		if ( strlen($query) < 2 && !$id ) wp_send_json_success( array() );

		if ( $id ) $query = (string) $id;

		try {
			$results = \Charts\Core\EntityManager::search_entities( $type, $query );
			wp_send_json_success( $results );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX logic to add or delete a row from a manual chart.
	 */
	public static function handle_manage_manual_row() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$chart_id = intval( $_POST['chart_id'] );
		$item_id  = intval( $_POST['item_id'] );
		$type     = sanitize_text_field( $_POST['type'] );
		$mode     = sanitize_text_field( $_POST['mode'] ); // 'add' or 'delete'

		$manager = new SourceManager();
		$entries = $manager->get_manual_entries( $chart_id );
		
		$new_entries = array();
		foreach ( $entries as $e ) {
			if ( $mode === 'delete' && (int)$e->item_id === $item_id && $e->item_type === $type ) continue;
			$new_entries[] = array( 'id' => $e->item_id, 'type' => $e->item_type );
		}

		if ( $mode === 'add' ) {
			// Check if already exists
			$exists = false;
			foreach ( $new_entries as $ne ) {
				if ( (int)$ne['id'] === $item_id && $ne['type'] === $type ) { $exists = true; break; }
			}
			if ( ! $exists ) {
				$new_entries[] = array( 'id' => $item_id, 'type' => $type );
			}
		}

		$result = $manager->save_manual_entries( $chart_id, $new_entries );
		
		if ( $result !== false ) {
			self::clear_frontend_caches();
			wp_send_json_success( array( 'count' => $result ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to synchronize manual entries.' ) );
		}
	}

	/**
	 * AJAX logic to save a new manual sort order.
	 */
	public static function handle_save_manual_order() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$chart_id = intval( $_POST['chart_id'] );
		$order    = array_map( function($i){ 
			return array(
				'id'        => (int)$i['id'], 
				'type'      => sanitize_text_field($i['type']),
				'title_en'  => sanitize_text_field($i['title_en'] ?? ''),
				'artist_en' => sanitize_text_field($i['artist_en'] ?? '')
			); 
		}, (array)($_POST['order'] ?? array()) );

		$manager = new SourceManager();
		$result = $manager->save_manual_entries( $chart_id, $order );

		if ( $result !== false ) {
			self::clear_frontend_caches();
			wp_send_json_success( array( 'count' => $result ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to persist manual ranking order.' ) );
		}
	}

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

	/**
	 * AJAX: Handle background migration steps.
	 */
	public static function handle_migration_step() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );

		$action = sanitize_text_field( $_POST['migration_action'] );
		$type   = sanitize_text_field( $_POST['entity_type'] );
		$count  = 0;

		if ( $action === 'promote' ) {
			$count = \Charts\Core\Migrator::promote_batch( $type );
		}

		wp_send_json_success( array( 'count' => $count ) );
	}

	/**
	 * Register the custom Nav Menu metabox for active charts.
	 */
	public static function register_nav_menu_metabox() {
		add_meta_box(
			'charts-definitions-nav-box',
			__( 'Active Charts', 'charts' ),
			array( self::class, 'render_nav_menu_metabox' ),
			'nav-menus',
			'side',
			'default'
		);
	}

	/**
	 * Render the custom Nav Menu metabox.
	 */
	public static function render_nav_menu_metabox() {
		$definitions = \Charts\Core\PublicIntegration::get_eligible_definitions( 100 );
		?>
		<div id="charts-definitions-nav" class="posttypediv">
			<div id="tabs-panel-charts-definitions-recent" class="tabs-panel tabs-panel-active">
				<ul id="charts-definitions-checklist-recent" class="categorychecklist form-no-clear">
					<?php foreach ( $definitions as $def ) : 
						$url = home_url( '/charts/' . $def->slug . '/' );
					?>
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="<?php echo esc_attr( $def->id ); ?>"> <?php echo esc_html( $def->title ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="custom">
							<input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="<?php echo esc_attr( $def->title ); ?>">
							<input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="<?php echo esc_url( $url ); ?>">
							<input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" value="kc-nav-chart">
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-post-type-menu-item" id="submit-charts-definitions-nav">
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX: Process Merge Operations
	 */
	public static function handle_process_merge() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$type = sanitize_text_field( $_POST['type'] ?? '' );
		require_once CHARTS_PATH . 'inc/Core/MergeEngine.php';

		// Support batch merge in handle_process_merge
		$batch = array();
		if ( ! empty( $_POST['batch'] ) ) {
			$raw_batch = wp_unslash( $_POST['batch'] );
			$decoded = is_string( $raw_batch ) ? json_decode( $raw_batch, true ) : $raw_batch;
			if ( is_array( $decoded ) ) {
				$batch = $decoded;
			}
		}

		if ( ! empty( $batch ) ) {
			$merged_count = 0;
			$errors = array();

			foreach ( $batch as $item ) {
				$m_id = intval( $item['master_id'] ?? 0 );
				$d_ids = isset( $item['duplicate_ids'] ) ? array_map( 'intval', (array) $item['duplicate_ids'] ) : array();
				if ( ! $m_id || empty( $d_ids ) ) {
					continue;
				}

				if ( $type === 'artists' ) {
					$res = \Charts\Core\MergeEngine::merge_artists( $m_id, $d_ids, false );
				} elseif ( $type === 'tracks' ) {
					$res = \Charts\Core\MergeEngine::merge_tracks( $m_id, $d_ids, false );
				} elseif ( $type === 'videos' ) {
					$res = \Charts\Core\MergeEngine::merge_videos( $m_id, $d_ids, false );
				} elseif ( $type === 'albums' ) {
					$res = \Charts\Core\MergeEngine::merge_albums( $m_id, $d_ids, false );
				} else {
					wp_send_json_error( array( 'message' => 'Invalid merge type.' ) );
					return;
				}

				if ( ! empty( $res['success'] ) ) {
					$merged_count += count( $d_ids );
				} else {
					$errors[] = $res['message'] ?? 'Merge error';
				}
			}

			if ( $merged_count > 0 ) {
				\Charts\Core\Intelligence::recalculate_all();
			}
			self::clear_frontend_caches();

			if ( $merged_count > 0 ) {
				wp_send_json_success( array(
					'message' => sprintf( 'Successfully merged %d duplicates across %d clusters.', $merged_count, count( $batch ) ),
					'merged_count' => $merged_count,
					'errors' => $errors,
				) );
			} else {
				wp_send_json_error( array(
					'message' => ! empty( $errors ) ? implode( '; ', $errors ) : 'No entities merged.',
				) );
			}
			return;
		}

		$master_id = intval( $_POST['master_id'] ?? 0 );
		$duplicate_ids = isset( $_POST['duplicate_ids'] ) ? array_map( 'intval', (array) $_POST['duplicate_ids'] ) : array();

		if ( ! $master_id || empty( $duplicate_ids ) ) {
			wp_send_json_error( array( 'message' => 'Missing data.' ) );
		}

		if ( $type === 'artists' ) {
			$result = \Charts\Core\MergeEngine::merge_artists( $master_id, $duplicate_ids );
		} elseif ( $type === 'tracks' ) {
			$result = \Charts\Core\MergeEngine::merge_tracks( $master_id, $duplicate_ids );
		} elseif ( $type === 'videos' ) {
			$result = \Charts\Core\MergeEngine::merge_videos( $master_id, $duplicate_ids );
		} elseif ( $type === 'albums' ) {
			$result = \Charts\Core\MergeEngine::merge_albums( $master_id, $duplicate_ids );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid merge type.' ) );
			return;
		}

		if ( $result['success'] ) {
			self::clear_frontend_caches();
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Save manually inputted Spotify ID from Matching Center.
	 */
	public static function handle_save_matching_id() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		global $wpdb;
		$type       = sanitize_text_field( $_POST['type'] ?? '' );
		$id         = intval( $_POST['id'] ?? 0 );
		$spotify_id = sanitize_text_field( $_POST['spotify_id'] ?? '' );

		if ( ! $id || empty( $spotify_id ) || ! in_array( $type, array( 'artist', 'track', 'album' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters provided.' ) );
		}

		$table = $wpdb->prefix . 'charts_' . $type . 's';
		$wpdb->update( $table, array( 'spotify_id' => $spotify_id ), array( 'id' => $id ) );
		$wpdb->update( "{$wpdb->prefix}charts_entries", array( 'spotify_id' => $spotify_id ), array( 'item_type' => $type, 'item_id' => $id ) );

		// Auto-enrich metadata and artwork
		if ( $type === 'album' ) {
			$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
			$spotify_service->enrich_album( $id );
		} elseif ( $type === 'track' ) {
			$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
			$spotify_service->enrich_track( $id );
		} elseif ( $type === 'artist' ) {
			$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
			$spotify_service->enrich_artist( $id );
		}

		self::clear_frontend_caches();

		wp_send_json_success( array( 'message' => 'Linked successfully.' ) );
	}

	/**
	 * Search Spotify API directly for Matching Center.
	 */
	public static function handle_search_spotify_matching() {
		check_ajax_referer( 'charts_admin_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$type  = sanitize_text_field( $_POST['type'] ?? 'track' );

		if ( empty( $query ) ) {
			wp_send_json_error( array( 'message' => 'Query is required.' ) );
		}

		require_once CHARTS_PATH . 'inc/Connectors/SpotifyConnector.php';
		
		$spotify_id = self::normalize_matching_identifier( $query, 'spotify' );

		try {
			// Get token
			$token = \Charts\Connectors\SpotifyConnector::get_access_token();
			if ( ! $token || is_wp_error( $token ) ) {
				throw new \Exception( 'Failed to authenticate with Spotify API.' );
			}

			$results = array();

			// 1. If user supplied a direct Spotify URL or ID (e.g. open.spotify.com/album/0cC0RG32ZJeRWEUoOQFXO8)
			if ( ! empty( $spotify_id ) ) {
				// Detect endpoint from URL or fallback to $type
				$endpoint_type = $type;
				if ( preg_match( '~/album/([A-Za-z0-9]+)~i', $query ) || $type === 'album' ) {
					$endpoint_type = 'album';
				} elseif ( preg_match( '~/track/([A-Za-z0-9]+)~i', $query ) || $type === 'track' ) {
					$endpoint_type = 'track';
				} elseif ( preg_match( '~/artist/([A-Za-z0-9]+)~i', $query ) || $type === 'artist' ) {
					$endpoint_type = 'artist';
				}

				$direct_url = "https://api.spotify.com/v1/{$endpoint_type}s/{$spotify_id}";
				$direct_resp = wp_remote_get( $direct_url, array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 15,
				) );

				if ( ! is_wp_error( $direct_resp ) && wp_remote_retrieve_response_code( $direct_resp ) === 200 ) {
					$item = json_decode( wp_remote_retrieve_body( $direct_resp ), true );
					if ( ! empty( $item['id'] ) ) {
						$img = ! empty( $item['images'][0]['url'] ) ? $item['images'][0]['url'] : ( ! empty( $item['album']['images'][0]['url'] ) ? $item['album']['images'][0]['url'] : '' );
						$results[] = array(
							'id'      => $item['id'],
							'name'    => $item['name'] ?? '',
							'image'   => \Charts\Core\ImageEnhancer::maximize( $img ),
							'url'     => $item['external_urls']['spotify'] ?? '',
							'artists' => isset( $item['artists'] ) ? implode( ', ', array_column( $item['artists'], 'name' ) ) : '',
							'type'    => $endpoint_type,
						);
						wp_send_json_success( $results );
						return;
					}
				}
			}

			// 2. Standard Search query
			$search_type = in_array( $type, array( 'album', 'artist', 'track' ), true ) ? $type : 'track';
			$url = 'https://api.spotify.com/v1/search?q=' . urlencode( $query ) . '&type=' . $search_type . '&limit=4';
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 15,
			);

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( empty( $data ) || isset( $data['error'] ) ) {
				throw new \Exception( 'Invalid response from Spotify API.' );
			}

			$key = $search_type . 's';
			
			if ( ! empty( $data[$key]['items'] ) ) {
				foreach ( $data[$key]['items'] as $item ) {
					$img = ! empty( $item['images'][0]['url'] ) ? $item['images'][0]['url'] : ( ! empty( $item['album']['images'][0]['url'] ) ? $item['album']['images'][0]['url'] : '' );
					$results[] = array(
						'id'      => $item['id'],
						'name'    => $item['name'] ?? '',
						'image'   => \Charts\Core\ImageEnhancer::maximize( $img ),
						'url'     => $item['external_urls']['spotify'] ?? '',
						'artists' => isset( $item['artists'] ) ? implode( ', ', array_column( $item['artists'], 'name' ) ) : '',
						'type'    => $search_type,
					);
				}
			}

			wp_send_json_success( $results );

		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Export Intelligence Data as CSV.
	 */
	public static function handle_export_intelligence() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'charts_export_intelligence' ) ) {
			wp_die( 'Security check failed.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		global $wpdb;
		$intel_table = $wpdb->prefix . 'charts_intelligence';

		$data = $wpdb->get_results("
			SELECT entity_type, entity_id, momentum_score, growth_rate, trend_status, weeks_on_chart, last_calculated_at 
			FROM $intel_table 
			ORDER BY momentum_score DESC 
			LIMIT 1000
		", ARRAY_A);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=charts_intelligence_' . date('Y-m-d') . '.csv');

		$output = fopen('php://output', 'w');
		fputcsv($output, array('Entity Type', 'Entity ID', 'Momentum Score', 'Growth Rate (%)', 'Trend Status', 'Weeks On Chart', 'Calculated At'));

		if (!empty($data)) {
			foreach ($data as $row) {
				fputcsv($output, $row);
			}
		}

		fclose($output);
		exit;
	}

	/**
	 * Scans database for potential duplicate clusters.
	 */
	public static function handle_scan_duplicates() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'artists';
		$table = ($type === 'artists') ? $wpdb->prefix . 'charts_artists' : $wpdb->prefix . 'charts_tracks';
		$entries_table = $wpdb->prefix . 'charts_entries';
		$is_artist = ($type === 'artists');

		// Fetch all entities
		if ($is_artist) {
			$entities = $wpdb->get_results("SELECT id, display_name as name, slug FROM $table");
		} else {
			$entities = $wpdb->get_results("SELECT id, title as name, slug FROM $table");
		}

		$clusters = [];
		$groups = [];

		// Group by normalized name
		foreach ($entities as $e) {
			$norm = \Charts\Services\Normalizer::normalize_title($e->name);
			$norm = strtolower(preg_replace('/[^a-zA-Z0-9\x{0600}-\x{06FF}\s]/u', '', $norm)); // remove punctuation
			$norm = trim(preg_replace('/\s+/', ' ', $norm)); // clean spaces
			
			if (empty($norm)) continue;
			
			if (!isset($groups[$norm])) {
				$groups[$norm] = [];
			}
			
			// Count entries for master selection logic
			$entries_count = 0;
			if ($is_artist) {
				$entries_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $entries_table WHERE item_type='artist' AND item_id=%d", $e->id));
			} else {
				$entries_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $entries_table WHERE item_type='track' AND item_id=%d", $e->id));
			}

			$groups[$norm][] = [
				'id' => $e->id,
				'name' => $e->name,
				'slug' => $e->slug,
				'entries' => (int)$entries_count,
				'is_master' => false
			];
		}

		// Filter groups with > 1 entity
		foreach ($groups as $norm => $items) {
			if (count($items) > 1) {
				// Select master (highest entries)
				usort($items, function($a, $b) {
					return $b['entries'] <=> $a['entries'];
				});
				
				// In case of tie, use ID (oldest = master)
				$max_entries = $items[0]['entries'];
				$potential_masters = array_filter($items, function($i) use ($max_entries) { return $i['entries'] == $max_entries; });
				
				usort($potential_masters, function($a, $b) {
					return $a['id'] <=> $b['id']; // lowest ID first
				});
				
				$master_id = $potential_masters[0]['id'];
				
				foreach ($items as &$item) {
					if ($item['id'] == $master_id) {
						$item['is_master'] = true;
					}
				}

				$clusters[] = [
					'normalized_name' => $norm,
					'entities' => $items
				];
			}
		}

		wp_send_json_success(array('clusters' => array_slice($clusters, 0, 100))); // Limit to 100 to avoid huge payload
	}

	/**
	 * Helper: Compute similarity between two strings.
	 */
	private static function get_similarity_pct( $str1, $str2 ) {
		$str1 = mb_strtolower( trim( $str1 ) );
		$str2 = mb_strtolower( trim( $str2 ) );
		if ( $str1 === $str2 ) return 100;
		
		$len1 = mb_strlen( $str1 );
		$len2 = mb_strlen( $str2 );
		if ( $len1 === 0 || $len2 === 0 ) return 0;
		
		// levenshtein() has a 255 character limit in PHP < 8.0, and can be slow for very long strings.
		if ($len1 > 250) { $str1 = mb_substr($str1, 0, 250); $len1 = 250; }
		if ($len2 > 250) { $str2 = mb_substr($str2, 0, 250); $len2 = 250; }
		
		$lev = levenshtein( $str1, $str2 );
		$max_len = max( $len1, $len2 );
		$similarity = ( 1 - $lev / $max_len ) * 100;
		return round( $similarity, 2 );
	}

	/**
	 * Helper: Generate blocking key for duplicate matching.
	 */
	private static function generate_blocking_key( $name ) {
		$clean = \Charts\Services\Normalizer::normalize_title( $name );
		// Strip punctuation and reduce duplicate characters
		$clean = preg_replace( '/[^\p{L}\p{N}]/u', '', $clean );
		$clean = preg_replace( '/(.)\1+/u', '$1', $clean );
		return trim( $clean );
	}

	/** Normalize provider URLs and URIs to the canonical external ID used for matching. */
	private static function normalize_matching_identifier( $value, $provider ) {
		$value = trim( html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' ) );
		if ( $value === '' || strtolower( $value ) === 'null' || $value === '0' ) {
			return '';
		}

		if ( $provider === 'spotify' ) {
			if ( preg_match( '~spotify:(?:track|album|artist):([A-Za-z0-9]+)~i', $value, $matches ) ) {
				return $matches[1];
			}
			if ( preg_match( '~open\\.spotify\\.com/(?:intl-[^/]+/)?(?:track|album|artist)/([A-Za-z0-9]+)~i', $value, $matches ) ) {
				return $matches[1];
			}
			return preg_match( '/^[A-Za-z0-9]{10,}$/', $value ) ? $value : '';
		}

		if ( $provider === 'youtube' ) {
			if ( preg_match( '~youtu\\.be/([A-Za-z0-9_-]{6,})~i', $value, $matches ) ) {
				return $matches[1];
			}
			if ( preg_match( '~youtube\\.com/(?:watch\\?(?:[^#]*?&)?v=|embed/|shorts/|live/)([A-Za-z0-9_-]{6,})~i', $value, $matches ) ) {
				return $matches[1];
			}
			return preg_match( '/^[A-Za-z0-9_-]{6,}$/', $value ) ? $value : '';
		}

		return '';
	}

	/**
	 * AJAX: Smart Entity Resolution & Matching Center clustering scanner.
	 */
	public static function handle_resolve_potential_duplicates() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'artists';
		
		if ( $type === 'artists' ) {
			$table = $wpdb->prefix . 'charts_artists';
			$entities = $wpdb->get_results("SELECT id, display_name as name, display_name_en as name_en, spotify_id, metadata_json FROM $table");
		} elseif ( $type === 'tracks' ) {
			$table = $wpdb->prefix . 'charts_tracks';
			$entities = $wpdb->get_results("SELECT id, title as name, title_en as name_en, spotify_id, youtube_id, cover_image as image, metadata_json FROM $table");
		} elseif ( $type === 'videos' ) {
			$table = $wpdb->prefix . 'charts_videos';
			$entities = $wpdb->get_results("SELECT id, title as name, thumbnail as image, youtube_id, metadata_json FROM $table");
		} elseif ( $type === 'albums' ) {
			$table = $wpdb->prefix . 'charts_albums';
			$entities = $wpdb->get_results("SELECT id, title as name, spotify_id, cover_image as image, metadata_json FROM $table");
		} else {
			wp_send_json_error( array( 'message' => 'Invalid entity type.' ) );
		}

		if (empty($entities)) {
			wp_send_json_success(array('clusters' => []));
			return;
		}

		$groups = [];
		$clusters = [];

		// Connected Components Clustering (Matches by Spotify ID, YouTube ID, OR Name)
		$nodes = [];
		$edges = [];
		$index_s = [];
		$index_y = [];
		$index_n = [];

		foreach ($entities as $e) {
			$nodes[$e->id] = $e;
			$edges[$e->id] = [];
			$s_id = self::normalize_matching_identifier( $e->spotify_id ?? '', 'spotify' );
			$y_id = self::normalize_matching_identifier( $e->youtube_id ?? '', 'youtube' );
			$b_key = self::generate_blocking_key($e->name);
			$b_key_en = self::generate_blocking_key($e->name_en ?? '');
			
			if (!empty($s_id)) $index_s[$s_id][] = $e->id;
			if (!empty($y_id)) $index_y[$y_id][] = $e->id;
			if (!empty($b_key)) $index_n[$b_key][] = $e->id;
			if (!empty($b_key_en)) $index_n[$b_key_en][] = $e->id;
		}

		foreach ([$index_s, $index_y, $index_n] as $index) {
			foreach ($index as $ids) {
				if (count($ids) > 1) {
					$first = $ids[0];
					for ($i = 1; $i < count($ids); $i++) {
						$edges[$first][] = $ids[$i];
						$edges[$ids[$i]][] = $first;
					}
				}
			}
		}

		$visited = [];
		$groups = [];
		foreach ($nodes as $id => $e) {
			if (isset($visited[$id])) continue;
			$group = [];
			$queue = [$id];
			$visited[$id] = true;
			while (!empty($queue)) {
				$curr = array_shift($queue);
				$group[] = $nodes[$curr];
				foreach ($edges[$curr] as $neighbor) {
					if (!isset($visited[$neighbor])) {
						$visited[$neighbor] = true;
						$queue[] = $neighbor;
					}
				}
			}
			if (count($group) > 1) $groups[] = $group;
		}

		// Process groups to build clusters with confidence scores
		foreach ($groups as $key => $items) {
			if (count($items) < 2) continue;

			// Sort to find master
			usort($items, function($a, $b) {
				$a_spot = self::normalize_matching_identifier( $a->spotify_id ?? '', 'spotify' );
				$a_tube = self::normalize_matching_identifier( $a->youtube_id ?? '', 'youtube' );
				$b_spot = self::normalize_matching_identifier( $b->spotify_id ?? '', 'spotify' );
				$b_tube = self::normalize_matching_identifier( $b->youtube_id ?? '', 'youtube' );

				$scoreA = (!empty($a_spot) || !empty($a_tube)) ? 2 : 0;
				$scoreB = (!empty($b_spot) || !empty($b_tube)) ? 2 : 0;
				if ($scoreA !== $scoreB) return $scoreB <=> $scoreA;
				return $a->id <=> $b->id;
			});

			$master = $items[0];
			$cluster_duplicates = [];

			for ($i = 1; $i < count($items); $i++) {
				$dup = $items[$i];
				$sim = self::get_similarity_pct($master->name, $dup->name);
				
				$m_spot = self::normalize_matching_identifier( $master->spotify_id ?? '', 'spotify' );
				$m_tube = self::normalize_matching_identifier( $master->youtube_id ?? '', 'youtube' );
				$d_spot = self::normalize_matching_identifier( $dup->spotify_id ?? '', 'spotify' );
				$d_tube = self::normalize_matching_identifier( $dup->youtube_id ?? '', 'youtube' );

				$exact_id_match = false;
				if (!empty($m_spot) && !empty($d_spot) && $m_spot === $d_spot) {
					$exact_id_match = true;
				}
				if (!empty($m_tube) && !empty($d_tube) && $m_tube === $d_tube) {
					$exact_id_match = true;
				}

				if ($exact_id_match) {
					$confidence = 100;
				} else {
					$confidence = $sim;
					$m_en = isset($master->name_en) ? $master->name_en : '';
					$d_en = isset($dup->name_en) ? $dup->name_en : '';
					if (!empty($m_en) && !empty($d_en) && mb_strtolower($m_en) === mb_strtolower($d_en)) {
						$confidence = max($confidence, 95);
					}
					$master_trans = \Charts\Core\Translation::get($master->name);
					$dup_trans = \Charts\Core\Translation::get($dup->name);
					if ($master_trans === $dup->name || $dup_trans === $master->name || ($master_trans !== $master->name && $master_trans === $dup_trans)) {
						$confidence = max($confidence, 98);
					}
				}

				$status = 'Manual Review';
				if ($confidence >= 95) {
					$status = 'Auto Merge Candidate';
				} elseif ($confidence >= 80) {
					$status = 'Review Required';
				}

				$cluster_duplicates[] = [
					'id' => $dup->id,
					'name' => $dup->name,
					'name_en' => $dup->name_en ?? '',
					'image' => $dup->image ?? ($dup->thumbnail ?? ($dup->cover_image ?? '')),
					'spotify_id' => $dup->spotify_id ?? '',
					'youtube_id' => $dup->youtube_id ?? '',
					'similarity' => $sim,
					'confidence' => $confidence,
					'status' => $status
				];
			}

			$clusters[] = [
				'master' => [
					'id' => $master->id,
					'name' => $master->name,
					'name_en' => $master->name_en ?? '',
					'image' => $master->image ?? ($master->thumbnail ?? ($master->cover_image ?? '')),
					'spotify_id' => $master->spotify_id ?? '',
					'youtube_id' => $master->youtube_id ?? '',
				],
				'duplicates' => $cluster_duplicates
			];
		}

		wp_send_json_success(array('clusters' => array_slice($clusters, 0, 50)));
	}

	/**
	 * AJAX: Bulk resolve center operations (Approve, Ignore, Merge, Delete)
	 */
	public static function handle_bulk_action_ajax() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$action_type = sanitize_text_field( $_POST['action_type'] ?? '' );
		$entity_type = sanitize_text_field( $_POST['entity_type'] ?? '' );
		
		if ( $action_type === 'merge' ) {
			require_once CHARTS_PATH . 'inc/Core/MergeEngine.php';
			$recalculate = ! empty( $_POST['recalculate'] );

			// Check if a batch of merges was sent: $_POST['batch'] as JSON string or array
			$batch = array();
			if ( ! empty( $_POST['batch'] ) ) {
				$raw_batch = wp_unslash( $_POST['batch'] );
				$decoded = is_string( $raw_batch ) ? json_decode( $raw_batch, true ) : $raw_batch;
				if ( is_array( $decoded ) ) {
					$batch = $decoded;
				}
			}

			if ( ! empty( $batch ) ) {
				$merged_count = 0;
				$errors = array();

				foreach ( $batch as $item ) {
					$m_id = intval( $item['master_id'] ?? 0 );
					$d_ids = isset( $item['duplicate_ids'] ) ? array_map( 'intval', (array) $item['duplicate_ids'] ) : array();
					if ( ! $m_id || empty( $d_ids ) ) {
						continue;
					}

					if ( $entity_type === 'artists' ) {
						$res = \Charts\Core\MergeEngine::merge_artists( $m_id, $d_ids, false );
					} elseif ( $entity_type === 'tracks' ) {
						$res = \Charts\Core\MergeEngine::merge_tracks( $m_id, $d_ids, false );
					} elseif ( $entity_type === 'videos' ) {
						$res = \Charts\Core\MergeEngine::merge_videos( $m_id, $d_ids, false );
					} elseif ( $entity_type === 'albums' ) {
						$res = \Charts\Core\MergeEngine::merge_albums( $m_id, $d_ids, false );
					} else {
						wp_send_json_error( array( 'message' => 'Invalid entity type for merge.' ) );
						return;
					}

					if ( ! empty( $res['success'] ) ) {
						$merged_count += count( $d_ids );
					} else {
						$errors[] = $res['message'] ?? 'Merge error';
					}
				}

				if ( $recalculate && $merged_count > 0 ) {
					\Charts\Core\Intelligence::recalculate_all();
				}
				self::clear_frontend_caches();

				if ( $merged_count > 0 ) {
					wp_send_json_success( array(
						'message' => sprintf( 'Successfully merged %d duplicates.', $merged_count ),
						'merged_count' => $merged_count,
						'errors' => $errors,
					) );
				} else {
					wp_send_json_error( array(
						'message' => ! empty( $errors ) ? implode( '; ', $errors ) : 'No entities merged.',
					) );
				}
				return;
			}

			$master_id = intval( $_POST['master_id'] ?? 0 );
			$duplicate_ids = isset( $_POST['duplicate_ids'] ) ? array_map( 'intval', (array) $_POST['duplicate_ids'] ) : array();

			if ( !$master_id || empty($duplicate_ids) ) {
				wp_send_json_error( array( 'message' => 'Missing master or duplicate IDs.' ) );
			}

			// Single merge operation
			$run_recalc = isset( $_POST['recalculate'] ) ? (bool) $_POST['recalculate'] : true;

			if ( $entity_type === 'artists' ) {
				$result = \Charts\Core\MergeEngine::merge_artists( $master_id, $duplicate_ids, $run_recalc );
			} elseif ( $entity_type === 'tracks' ) {
				$result = \Charts\Core\MergeEngine::merge_tracks( $master_id, $duplicate_ids, $run_recalc );
			} elseif ( $entity_type === 'videos' ) {
				$result = \Charts\Core\MergeEngine::merge_videos( $master_id, $duplicate_ids, $run_recalc );
			} elseif ( $entity_type === 'albums' ) {
				$result = \Charts\Core\MergeEngine::merge_albums( $master_id, $duplicate_ids, $run_recalc );
			} else {
				wp_send_json_error( array( 'message' => 'Invalid entity type for merge.' ) );
				return;
			}

			if ($result['success']) {
				self::clear_frontend_caches();
				wp_send_json_success( array( 'message' => $result['message'] ) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} elseif ( $action_type === 'delete' ) {
			$ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array();
			if ( empty($ids) ) {
				wp_send_json_error( array( 'message' => 'No IDs specified.' ) );
			}

			$singular_type = rtrim($entity_type, 's');
			if ($singular_type === 'clip') $singular_type = 'video';

			foreach ( $ids as $id ) {
				self::delete_single_entity( $id, $singular_type );
			}
			self::clear_frontend_caches();

			wp_send_json_success( array( 'message' => sprintf('Successfully deleted %d entities.', count($ids)) ) );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid action type.' ) );
		}
	}

	/**
	 * AJAX: Perform Auto Reconciliation for confident matches.
	 */
	public static function handle_auto_reconcile() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$entity_types = ['artists', 'tracks', 'videos', 'albums'];
		$total_merged = 0;

		require_once CHARTS_PATH . 'inc/Core/MergeEngine.php';

		foreach ($entity_types as $type) {
			if ( $type === 'artists' ) {
				$table = $wpdb->prefix . 'charts_artists';
				$entities = $wpdb->get_results("SELECT id, display_name as name, display_name_en as name_en, spotify_id, metadata_json FROM $table");
			} elseif ( $type === 'tracks' ) {
				$table = $wpdb->prefix . 'charts_tracks';
				$entities = $wpdb->get_results("SELECT id, title as name, title_en as name_en, spotify_id, youtube_id, metadata_json FROM $table");
			} elseif ( $type === 'videos' ) {
				$table = $wpdb->prefix . 'charts_videos';
				$entities = $wpdb->get_results("SELECT id, title as name, youtube_id, metadata_json FROM $table");
			} elseif ( $type === 'albums' ) {
				$table = $wpdb->prefix . 'charts_albums';
				$entities = $wpdb->get_results("SELECT id, title as name, spotify_id, metadata_json FROM $table");
			}

			if (empty($entities)) continue;

			// Connected Components Clustering (Matches by Spotify ID, YouTube ID, OR Name)
			$nodes = [];
			$edges = [];
			$index_s = [];
			$index_y = [];
			$index_n = [];

			foreach ($entities as $e) {
				$nodes[$e->id] = $e;
				$edges[$e->id] = [];
				$s_id = self::normalize_matching_identifier( $e->spotify_id ?? '', 'spotify' );
				$y_id = self::normalize_matching_identifier( $e->youtube_id ?? '', 'youtube' );
				$b_key = self::generate_blocking_key($e->name);
				
				if (!empty($s_id)) $index_s[$s_id][] = $e->id;
				if (!empty($y_id)) $index_y[$y_id][] = $e->id;
				if (!empty($b_key)) $index_n[$b_key][] = $e->id;
			}

			foreach ([$index_s, $index_y, $index_n] as $index) {
				foreach ($index as $ids) {
					if (count($ids) > 1) {
						$first = $ids[0];
						for ($i = 1; $i < count($ids); $i++) {
							$edges[$first][] = $ids[$i];
							$edges[$ids[$i]][] = $first;
						}
					}
				}
			}

			$visited = [];
			$groups = [];
			foreach ($nodes as $id => $e) {
				if (isset($visited[$id])) continue;
				$group = [];
				$queue = [$id];
				$visited[$id] = true;
				while (!empty($queue)) {
					$curr = array_shift($queue);
					$group[] = $nodes[$curr];
					foreach ($edges[$curr] as $neighbor) {
						if (!isset($visited[$neighbor])) {
							$visited[$neighbor] = true;
							$queue[] = $neighbor;
						}
					}
				}
				if (count($group) > 1) $groups[] = $group;
			}

			// Find auto-merge groups (confidence >= 95%)
			foreach ($groups as $key => $items) {
				if (count($items) < 2) continue;

				// Sort to find master
				usort($items, function($a, $b) {
					$scoreA = (self::normalize_matching_identifier( $a->spotify_id ?? '', 'spotify' ) !== '' || self::normalize_matching_identifier( $a->youtube_id ?? '', 'youtube' ) !== '') ? 2 : 0;
					$scoreB = (self::normalize_matching_identifier( $b->spotify_id ?? '', 'spotify' ) !== '' || self::normalize_matching_identifier( $b->youtube_id ?? '', 'youtube' ) !== '') ? 2 : 0;
					if ($scoreA !== $scoreB) return $scoreB <=> $scoreA;
					return $a->id <=> $b->id;
				});

				$master = $items[0];
				$to_merge = [];

				for ($i = 1; $i < count($items); $i++) {
					$dup = $items[$i];
					$sim = self::get_similarity_pct($master->name, $dup->name);
					
					$exact_id_match = false;
					$master_spotify_id = self::normalize_matching_identifier( $master->spotify_id ?? '', 'spotify' );
					$duplicate_spotify_id = self::normalize_matching_identifier( $dup->spotify_id ?? '', 'spotify' );
					$master_youtube_id = self::normalize_matching_identifier( $master->youtube_id ?? '', 'youtube' );
					$duplicate_youtube_id = self::normalize_matching_identifier( $dup->youtube_id ?? '', 'youtube' );
					if ( $master_spotify_id !== '' && $master_spotify_id === $duplicate_spotify_id ) {
						$exact_id_match = true;
					}
					if ( $master_youtube_id !== '' && $master_youtube_id === $duplicate_youtube_id ) {
						$exact_id_match = true;
					}

					$confidence = $sim;
					if ($exact_id_match) {
						$confidence = 100;
					} else {
						if (!empty($master->name_en) && !empty($dup->name_en) && mb_strtolower($master->name_en) === mb_strtolower($dup->name_en)) {
							$confidence = max($confidence, 95);
						}
						$master_trans = \Charts\Core\Translation::get($master->name);
						$dup_trans = \Charts\Core\Translation::get($dup->name);
						if ($master_trans === $dup->name || $dup_trans === $master->name || ($master_trans !== $master->name && $master_trans === $dup_trans)) {
							$confidence = max($confidence, 98);
						}
					}

					if ($confidence >= 95) {
						$to_merge[] = $dup->id;
					}
				}

				if (!empty($to_merge)) {
					if ( $type === 'artists' ) {
						$res = \Charts\Core\MergeEngine::merge_artists( $master->id, $to_merge, false );
					} elseif ( $type === 'tracks' ) {
						$res = \Charts\Core\MergeEngine::merge_tracks( $master->id, $to_merge, false );
					} elseif ( $type === 'videos' ) {
						$res = \Charts\Core\MergeEngine::merge_videos( $master->id, $to_merge, false );
					} elseif ( $type === 'albums' ) {
						$res = \Charts\Core\MergeEngine::merge_albums( $master->id, $to_merge, false );
					}
					if (isset($res['success']) && $res['success']) {
						$total_merged += count($to_merge);
					}
				}
			}
		}

		if ($total_merged > 0) {
			\Charts\Core\Intelligence::recalculate_all();
			self::clear_frontend_caches();
		}

		wp_send_json_success( array( 'message' => sprintf( 'Auto-reconciliation complete. Merged %d duplicate records.', $total_merged ) ) );
	}

	/**
	 * AJAX: Save metadata configuration profile for Artist Identity Center.
	 */
	public static function handle_update_artist_identity() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		global $wpdb;
		$id = intval( $_POST['id'] ?? 0 );
		$entity_type = sanitize_text_field( $_POST['entity_type'] ?? 'artist' );
		$primary_name = sanitize_text_field( $_POST['primary_name'] ?? '' );
		$name_en = sanitize_text_field( $_POST['name_en'] ?? '' );
		$spotify_id = sanitize_text_field( $_POST['spotify_id'] ?? '' );
		$youtube_id = sanitize_text_field( $_POST['youtube_id'] ?? '' );
		
		if ( !$id || empty($primary_name) ) {
			wp_send_json_error( array( 'message' => 'ID and Name are required.' ) );
		}

		$normalized = mb_strtolower($primary_name);

		if ( $entity_type === 'artist' || $entity_type === 'artists' ) {
			$apple_music_id = sanitize_text_field( $_POST['apple_music_id'] ?? '' );
			$tiktok_id = sanitize_text_field( $_POST['tiktok_id'] ?? '' );
			$instagram_id = sanitize_text_field( $_POST['instagram_id'] ?? '' );
			$aliases_str = sanitize_text_field( $_POST['aliases'] ?? '' );

			$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $id ) );
			if ( ! $artist ) wp_send_json_error( array( 'message' => 'Artist not found.' ) );
			$meta = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : [];
			$aliases = array_filter(array_map('trim', explode(',', $aliases_str)));
			$meta['aliases'] = $aliases;
			$meta['youtube_id'] = $youtube_id;
			$meta['apple_music_id'] = $apple_music_id;
			$meta['tiktok_id'] = $tiktok_id;
			$meta['instagram_id'] = $instagram_id;

			$wpdb->update( "{$wpdb->prefix}charts_artists", array(
				'display_name' => $primary_name, 'display_name_en' => $name_en,
				'normalized_name' => $normalized, 'spotify_id' => $spotify_id,
				'metadata_json' => json_encode($meta), 'updated_at' => current_time('mysql')
			), array( 'id' => $id ) );

			self::sync_entity_chart_entries( 'artist', $id, $artist->display_name, $artist->display_name_en ?? '' );
			\Charts\Core\Intelligence::recalculate_all();
			self::clear_frontend_caches();
			wp_send_json_success( array( 'message' => 'Artist updated.' ) );
		} 
		else if ( $entity_type === 'track' || $entity_type === 'tracks' ) {
			$wpdb->update( "{$wpdb->prefix}charts_tracks", array(
				'title' => $primary_name, 'title_en' => $name_en,
				'normalized_title' => $normalized, 'spotify_id' => $spotify_id, 'youtube_id' => $youtube_id
			), array( 'id' => $id ) );

			self::sync_entity_chart_entries( 'track', $id );
			$wpdb->update( "{$wpdb->prefix}charts_entries", array( 'track_name_en' => $name_en ), array( 'item_type' => 'track', 'item_id' => $id ) );
			\Charts\Core\Intelligence::recalculate_all();
			self::clear_frontend_caches();
			wp_send_json_success( array( 'message' => 'Track updated.' ) );
		}
		else if ( $entity_type === 'video' || $entity_type === 'videos' || $entity_type === 'clip' || $entity_type === 'clips' ) {
			$wpdb->update( "{$wpdb->prefix}charts_videos", array(
				'title' => $primary_name, 'normalized_title' => $normalized, 'youtube_id' => $youtube_id
			), array( 'id' => $id ) );

			self::sync_entity_chart_entries( 'video', $id );
			\Charts\Core\Intelligence::recalculate_all();
			self::clear_frontend_caches();
			wp_send_json_success( array( 'message' => 'Video updated.' ) );
		}
		else if ( $entity_type === 'album' || $entity_type === 'albums' ) {
			$album_data = array(
				'title'            => $primary_name,
				'title_en'         => $name_en,
				'normalized_title' => $normalized,
				'spotify_id'       => $spotify_id,
			);
			$wpdb->update( "{$wpdb->prefix}charts_albums", $album_data, array( 'id' => $id ) );

			if ( ! empty( $spotify_id ) ) {
				$spotify_service = new \Charts\Services\SpotifyEnrichmentService();
				$spotify_service->enrich_album( $id );
			}

			self::clear_frontend_caches();

			wp_send_json_success( array( 'message' => 'Album updated and enriched from Spotify.' ) );
		}

		wp_send_json_error( array( 'message' => 'Invalid entity type.' ) );
	}

	/**
	 * AJAX: Get Billboard Arabia available weeks list
	 */
	public static function handle_force_english_slugs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$updated = 0;
		
		// Map tables to their respective Arabic and English columns
		$tables = [
			'charts_artists' => ['id', 'display_name as title', 'display_name_en as title_en', 'slug'],
			'charts_tracks' => ['id', 'title', 'title_en', 'slug'],
			'charts_videos' => ['id', 'title', 'NULL as title_en', 'slug'],
			'charts_albums' => ['id', 'title', 'NULL as title_en', 'slug']
		];

		foreach ($tables as $table_suffix => $cols) {
			$table = $wpdb->prefix . $table_suffix;
			$items = $wpdb->get_results("SELECT {$cols[0]} as id, {$cols[1]}, {$cols[2]}, {$cols[3]} FROM $table");
			
			foreach ($items as $item) {
				// We want to force a new slug if the current one has non-ASCII OR if it was purely Franco-generated and we now have a real English title
				$slug_base = ! empty( $item->title_en ) ? $item->title_en : $item->title;
				$expected = \Charts\Services\Slugger::unique($table, $slug_base);
				
				if ( $item->slug !== $expected && ( preg_match('/[^\x20-\x7e]/', urldecode($item->slug)) || urldecode($item->slug) !== $item->slug || ! empty($item->title_en) ) ) {
					$wpdb->update($table, ['slug' => $expected], ['id' => $item->id]);
					$updated++;
				}
			}
		}

		$chart_table = $wpdb->prefix . 'charts_definitions';
		$charts = $wpdb->get_results("SELECT id, title, slug FROM $chart_table");
		foreach ($charts as $chart) {
			if (preg_match('/[^\x20-\x7e]/', urldecode($chart->slug)) || urldecode($chart->slug) !== $chart->slug) {
				$expected = \Charts\Services\Slugger::unique($chart_table, $chart->title, 'chart');
				$wpdb->update($chart_table, ['slug' => $expected], ['id' => $chart->id]);
				$native_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'charts_definition_id' AND meta_value = %d", $chart->id));
				if ($native_id) {
					wp_update_post(['ID' => $native_id, 'post_name' => $expected]);
				}
				$updated++;
			}
		}

		wp_send_json_success( array( 'message' => "Updated $updated slugs." ) );
	}


	public static function handle_billboard_get_weeks() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) && ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$billboard_chart_id = absint( $_POST['billboard_chart_id'] ?? 1 );
		$weeks = \Charts\Services\BillboardService::get_weeks( $billboard_chart_id );
		wp_send_json_success( array( 'weeks' => $weeks ) );
	}

	/**
	 * AJAX: Direct 1-Click Sync Billboard Arabia Chart to Database
	 */
	public static function handle_billboard_sync() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) && ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		@set_time_limit(300);
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$week_id  = intval( $_POST['week_id'] ?? 0 );
		$chart_id = intval( $_POST['chart_id'] ?? 0 );
		$billboard_chart_id = absint( $_POST['billboard_chart_id'] ?? 1 );

		$result = \Charts\Services\BillboardService::sync_to_chart( $week_id, $chart_id, $billboard_chart_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				$result['item_type'] === 'artist' ? __( 'تم استيراد %d فنان بنجاح من قائمة Billboard Arabia.', 'charts' ) : __( 'تم استيراد %d أغنية بنجاح من قائمة Billboard Arabia.', 'charts' ),
				$result['imported_count']
			),
			'data'    => $result,
		) );
	}

	/**
	 * Download Billboard Arabia CSV Sheet directly
	 */
	public static function handle_billboard_download_csv() {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'charts_admin_action' ) ) {
			wp_die( 'Security check failed.' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized.' );
		}
		@set_time_limit(300);

		$week_id = intval( $_GET['week_id'] ?? 0 );
		$billboard_chart_id = absint( $_GET['chart_id'] ?? 1 );
		\Charts\Services\BillboardService::download_csv( $week_id, $billboard_chart_id );
	}

	/**
	 * AJAX: Direct 1-Click Sync YouTube Chart to Database
	 */
	public static function handle_youtube_sync() {
		if ( ! check_ajax_referer( 'charts_admin_action', '_wpnonce', false ) && ! check_ajax_referer( 'charts_admin_action', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}
		@set_time_limit(300);
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$chart_key = sanitize_text_field( $_POST['youtube_chart_key'] ?? 'top-songs-weekly' );
		$chart_id  = intval( $_POST['chart_id'] ?? 0 );

		$result = \Charts\Services\YouTubeChartsService::sync_to_chart( $chart_key, $chart_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				$result['item_type'] === 'artist' ? __( 'تم استيراد %d فنان بنجاح من قوائم YouTube الرسمية.', 'charts' ) : __( 'تم استيراد %d عنصر بنجاح من قوائم YouTube الرسمية.', 'charts' ),
				$result['imported_count']
			),
			'data'    => $result,
		) );
	}

}
