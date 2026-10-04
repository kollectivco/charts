<?php

namespace Charts\Core;

/**
 * Handle plugin integrity, folder parity, and installation cleanup.
 */
class Integrity {

	/**
	 * Initialize integrity hooks.
	 */
	public static function init() {
		add_filter( 'upgrader_source_selection', array( self::class, 'force_canonical_folder' ), 10, 4 );
	}

	/**
	 * Force the plugin into its canonical slug directory during install/update.
	 * This prevents the "2 plugins" issue if the ZIP folder name is non-standard.
	 */
	public static function force_canonical_folder( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		// 1. Identification: Is this the Charts plugin?
		$is_this_plugin = false;

		// Standard update flow
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === CHARTS_PLUGIN_BASENAME ) {
			$is_this_plugin = true;
		}

		// Manual upload flow (lookup by main registry file)
		if ( ! $is_this_plugin && isset( $source ) ) {
			if ( file_exists( $source . '/charts.php' ) ) {
				$is_this_plugin = true;
			}
		}

		if ( ! $is_this_plugin ) {
			return $source;
		}

		// 2. Cleanup: Purge packaging junk if present (MacOS artifacts)
		if ( $wp_filesystem->exists( $source . '/__MACOSX' ) ) {
			$wp_filesystem->delete( $source . '/__MACOSX', true );
		}

		// 3. Normalization: Force move to canonical slug
		$canonical_path = trailingslashit( $remote_source ) . CHARTS_PLUGIN_SLUG;
		$canonical_path = trailingslashit( $canonical_path );

		if ( trailingslashit( $source ) !== $canonical_path ) {
			// If destination exists, clear it first to ensure clean replacement
			if ( $wp_filesystem->exists( $canonical_path ) ) {
				$wp_filesystem->delete( $canonical_path, true );
			}

			if ( $wp_filesystem->move( $source, $canonical_path ) ) {
				return $canonical_path;
			}
		}

		return $source;
	}

	/**
	 * Scans chart entries with missing item_id and creates/links canonical entities.
	 * This logic was ported from Schema backfill to be manually triggerable.
	 */
	public static function recalculate_entity_links() {
		global $wpdb;
		$entries_tbl = $wpdb->prefix . 'charts_entries';
		$artists_tbl = $wpdb->prefix . 'charts_artists';
		$tracks_tbl  = $wpdb->prefix . 'charts_tracks';
		$clips_tbl   = $wpdb->prefix . 'charts_videos';

		// 1. Purge explicit ghost entries (where the entity was unlinked but entry left behind)
		$wpdb->query("DELETE FROM $entries_tbl WHERE item_id = 0");

		// 2. Purge orphaned artist entries
		$wpdb->query("DELETE e FROM $entries_tbl e LEFT JOIN $artists_tbl a ON e.item_id = a.id AND e.item_type = 'artist' WHERE e.item_type = 'artist' AND a.id IS NULL");

		// 3. Purge orphaned track entries
		$wpdb->query("DELETE e FROM $entries_tbl e LEFT JOIN $tracks_tbl t ON e.item_id = t.id AND e.item_type = 'track' WHERE e.item_type = 'track' AND t.id IS NULL");

		// 4. Purge orphaned video entries
		$wpdb->query("DELETE e FROM $entries_tbl e LEFT JOIN $clips_tbl c ON e.item_id = c.id AND e.item_type = 'video' WHERE e.item_type = 'video' AND c.id IS NULL");
	}

	/**
	 * Scans for multiple active sources targeting the same chart context.
	 */
	public static function detect_redundant_sources() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';
		
		$results = $wpdb->get_results("
			SELECT chart_type, country_code, platform, frequency, COUNT(*) as source_count, GROUP_CONCAT(id) as source_ids
			FROM $table
			WHERE is_active = 1
			GROUP BY chart_type, country_code, platform, frequency
			HAVING COUNT(*) > 1
		");

		return $results;
	}
}
