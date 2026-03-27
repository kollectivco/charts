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

		if ( $source !== $canonical_path ) {
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

		// 1. Repair Artists with missing item_id
		$orphan_artists = $wpdb->get_results("SELECT DISTINCT artist_names FROM $entries_tbl WHERE item_id = 0");
		foreach ( $orphan_artists as $row ) {
			if ( empty($row->artist_names) ) continue;
			$name = trim($row->artist_names);
			$normalized = mb_strtolower($name);
			
			$id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $artists_tbl WHERE normalized_name = %s", $normalized));
			if ( !$id ) {
				$wpdb->insert($artists_tbl, array(
					'display_name'    => $name,
					'normalized_name' => $normalized,
					'slug'            => sanitize_title($name),
					'created_at'      => current_time('mysql')
				));
				$id = $wpdb->insert_id;
			}
			if ( $id ) {
				$wpdb->update($entries_tbl, array('item_id' => $id), array('artist_names' => $name, 'item_id' => 0));
			}
		}

		// 2. Repair Tracks with missing item_id
		$orphan_tracks = $wpdb->get_results("SELECT DISTINCT track_name, artist_names FROM $entries_tbl WHERE item_type = 'track' AND item_id = 0 LIMIT 1000");
		foreach ( $orphan_tracks as $row ) {
			if ( empty($row->track_name) ) continue;
			$title = trim($row->track_name);
			$artists = explode(',', $row->artist_names);
			$primary_artist = trim($artists[0]);
			
			// Resolve Artist first
			$normalized_artist = mb_strtolower($primary_artist);
			$artist_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $artists_tbl WHERE normalized_name = %s", $normalized_artist));
			if ( !$artist_id ) {
				$wpdb->insert($artists_tbl, array(
					'display_name'    => $primary_artist,
					'normalized_name' => $normalized_artist,
					'slug'            => sanitize_title($primary_artist),
					'created_at'      => current_time('mysql')
				));
				$artist_id = $wpdb->insert_id;
			}

			// Resolve Track
			$normalized_track = mb_strtolower($title);
			$track_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $tracks_tbl WHERE normalized_title = %s AND primary_artist_id = %d", $normalized_track, $artist_id));
			if ( !$track_id ) {
				$wpdb->insert($tracks_tbl, array(
					'title'             => $title,
					'normalized_title'  => $normalized_track,
					'slug'              => sanitize_title($title . '-' . $artist_id),
					'primary_artist_id' => $artist_id,
					'created_at'        => current_time('mysql')
				));
				$track_id = $wpdb->insert_id;
			}

			if ( $track_id ) {
				$wpdb->update($entries_tbl, array('item_id' => $track_id), array(
					'item_type'  => 'track', 
					'track_name' => $row->track_name,
					'item_id'    => 0
				));
			}
		}
	}
}
