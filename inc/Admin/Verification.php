<?php

namespace Charts\Admin;

/**
 * Temporary CPT Migration Verification tool.
 */
class Verification {

	public static function init() {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 99 );
	}

	public static function add_menu() {
		add_submenu_page(
			'charts-dashboard',
			__( 'CPT Verification', 'charts' ),
			__( 'CPT Verification', 'charts' ),
			'manage_options',
			'charts-verification',
			array( self::class, 'render_page' )
		);
	}

	public static function render_page() {
		global $wpdb;
		
		$types = array(
			'chart'  => 'charts_definitions',
			'artist' => 'charts_artists',
			'track'  => 'charts_tracks',
			'video'  => 'charts_videos'
		);

		$report = array();
		foreach ( $types as $post_type => $table_name ) {
			$table = $wpdb->prefix . $table_name;
			
			// SQL Count
			$sql_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
			
			// CPT Count
			$cpt_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != 'trash'", $post_type ) );
			
			// Mapped Count (has _old_id)
			$mapped_count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} pm
				JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND pm.meta_key = %s
			", $post_type, '_old_' . $post_type . '_id' ) );

			// Unmapped SQL IDs (Remaining)
			$remaining = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(*) FROM $table t
				LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_key = %s AND pm.meta_value = CAST(t.id AS CHAR)
				WHERE pm.post_id IS NULL
			", '_old_' . $post_type . '_id' ) );

			// Duplicate Check (Same name CPTs)
			$duplicates = $wpdb->get_results( $wpdb->prepare( "
				SELECT post_title, COUNT(*) as c FROM {$wpdb->posts} 
				WHERE post_type = %s AND post_status = 'publish'
				GROUP BY post_title HAVING c > 1
			", $post_type ) );

			$report[$post_type] = array(
				'sql'        => $sql_count,
				'cpt'        => $cpt_count,
				'mapped'     => $mapped_count,
				'remaining'  => $remaining,
				'duplicates' => count($duplicates)
			);
		}

		// Relationship Check
		$rel_report = array();
		foreach ( array('track', 'video') as $type ) {
			$table = $wpdb->prefix . "charts_{$type}_artists";
			$total_rels = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
			$offset = (int) get_option( "charts_migrated_rels_{$type}_offset", 0 );
			$rel_report[$type] = array(
				'total' => $total_rels,
				'migrated' => $offset,
				'pending' => $total_rels - $offset
			);
		}

		// Render UI
		include CHARTS_PATH . 'admin/views/verification.php';
	}
}
