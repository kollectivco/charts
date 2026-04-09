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
}
