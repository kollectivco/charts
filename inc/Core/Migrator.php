<?php

namespace Charts\Core;

/**
 * Handle bulk migration from legacy SQL to Native CPT architecture.
 */
class Migrator {

	public static function promote_batch( $type, $limit = 100 ) {
		global $wpdb;
		$table = $wpdb->prefix . ( $type === 'artist' ? 'charts_artists' : ( ($type === 'video') ? 'charts_videos' : 'charts_tracks' ) );
		
		// Find items without a CPT shadow
		$items = $wpdb->get_results( $wpdb->prepare( "
			SELECT id FROM $table 
			WHERE id NOT IN (
				SELECT meta_value FROM $wpdb->postmeta 
				WHERE meta_key = '_kcharts_legacy_id'
			)
			LIMIT %d
		", $limit ) );

		$count = 0;
		foreach ( $items as $item ) {
			if ( EntityManager::promote_to_native( $type, $item->id ) ) {
				$count++;
			}
		}

		return $count;
	}

	public static function get_migration_stats() {
		global $wpdb;
		$stats = array();
		$types = array( 'chart', 'artist', 'track', 'video' );

		foreach ( $types as $type ) {
			$table = $wpdb->prefix . ( $type === 'chart' ? 'charts_definitions' : ( $type === 'artist' ? 'charts_artists' : ( ($type==='video') ? 'charts_videos' : 'charts_tracks' ) ) );
			if ( ! $wpdb->get_var("SHOW TABLES LIKE '$table'") ) {
				$stats[$type] = array('total' => 0, 'promoted' => 0, 'percent' => 100);
				continue;
			}
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
			$promoted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s", $type ) );
			
			$stats[$type] = array(
				'total' => (int) $total,
				'promoted' => (int) $promoted,
				'percent' => $total > 0 ? round( (min($promoted, $total) / $total) * 100, 1 ) : 100
			);
		}

		return $stats;
	}

	/**
	 * Backfill Franco (Arabizi) auto values for existing records.
	 */
	public static function backfill_franco($limit = 500) {
		global $wpdb;
		$count = 0;

		// 1. Backfill Entries
		$entries_tbl = $wpdb->prefix . 'charts_entries';
		$rows = $wpdb->get_results($wpdb->prepare("
			SELECT id, track_name, artist_names 
			FROM $entries_tbl 
			WHERE (track_name_franco_auto IS NULL AND (track_name REGEXP '[\\\\x{0600}-\\\\x{06FF}]'))
			   OR (artist_names_franco_auto IS NULL AND (artist_names REGEXP '[\\\\x{0600}-\\\\x{06FF}]'))
			LIMIT %d", $limit));

		foreach ($rows as $row) {
			$track_franco = Transliteration::to_franco($row->track_name);
			$artist_franco = Transliteration::to_franco($row->artist_names);
			
			$wpdb->update($entries_tbl, [
				'track_name_franco_auto' => ($track_franco !== $row->track_name) ? $track_franco : null,
				'artist_names_franco_auto' => ($artist_franco !== $row->artist_names) ? $artist_franco : null
			], ['id' => $row->id]);
			$count++;
		}

		// 2. Backfill Artists
		$artists_tbl = $wpdb->prefix . 'charts_artists';
		$artists = $wpdb->get_results($wpdb->prepare("
			SELECT id, display_name FROM $artists_tbl 
			WHERE display_name_franco_auto IS NULL 
			AND (display_name REGEXP '[\\\\x{0600}-\\\\x{06FF}]')
			LIMIT %d", $limit));
		
		foreach ($artists as $a) {
			$franco = Transliteration::to_franco($a->display_name);
			if ($franco !== $a->display_name) {
				$wpdb->update($artists_tbl, ['display_name_franco_auto' => $franco], ['id' => $a->id]);
				$count++;
			}
		}

		// 3. Backfill Tracks
		$tracks_tbl = $wpdb->prefix . 'charts_tracks';
		$tracks = $wpdb->get_results($wpdb->prepare("
			SELECT id, title FROM $tracks_tbl 
			WHERE title_franco_auto IS NULL 
			AND (title REGEXP '[\\\\x{0600}-\\\\x{06FF}]')
			LIMIT %d", $limit));
		
		foreach ($tracks as $t) {
			$franco = Transliteration::to_franco($t->title);
			if ($franco !== $t->title) {
				$wpdb->update($tracks_tbl, ['title_franco_auto' => $franco], ['id' => $t->id]);
				$count++;
			}
		}

		return $count;
	}
}
