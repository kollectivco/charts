<?php

namespace Charts\Database;

/**
 * Handle migration from custom tables to Custom Post Types.
 * Designed for safety with batching and state tracking.
 */
class Migration {

	private $batch_size = 200;

	/**
	 * Run the migration process.
	 * Triggered on admin_init or from the main plugin file.
	 */
	public function run() {
		// Prevent multiple full runs
		if ( get_option( 'charts_migration_v3_fully_completed' ) ) return;

		// 1. Core Entities
		$this->migrate_entity( 'chart', 'charts_definitions' );
		$this->migrate_entity( 'artist', 'charts_artists' );
		$this->migrate_entity( 'track', 'charts_tracks' );
		$this->migrate_entity( 'video', 'charts_videos' );
		
		// 2. Resolve relationships once entities are created
		// Note: we only do this once per batch run to catch up
		$this->resolve_relationships();
		$this->migrate_relationship_tables();
		$this->update_all_table_links();

		// check if we are actually done
		if ( $this->is_migration_complete() ) {
			update_option( 'charts_migration_v3_fully_completed', current_time( 'mysql' ) );
			update_option( 'charts_migration_completed_v3', current_time( 'mysql' ) ); // Legacy compat
		}
	}

	/**
	 * Generic batched entity migration.
	 */
	private function migrate_entity( $post_type, $table_name ) {
		global $wpdb;
		$table = $wpdb->prefix . $table_name;
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === null ) return;

		// Get total count of unmigrated records
		// We use a LEFT JOIN to posts meta to find who is NOT migrated
		$records = $wpdb->get_results( $wpdb->prepare( "
			SELECT t.* FROM $table t
			LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_key = %s AND pm.meta_value = CAST(t.id AS CHAR)
			WHERE pm.post_id IS NULL
			LIMIT %d
		", '_old_' . $post_type . '_id', $this->batch_size ) );

		if ( empty($records) ) return;

		foreach ( $records as $row ) {
			// Try to preserve ID to prevent settings/lookup breakage
			$post_data = array(
				'import_id'    => $row->id,
				'post_title'   => $row->title ?? $row->display_name ?? 'Unnamed',
				'post_name'    => $row->slug,
				'post_status'  => (isset($row->is_public) && !$row->is_public) ? 'draft' : 'publish',
				'post_type'    => $post_type,
				'post_date'     => $row->created_at ?? current_time('mysql'),
			);

			if ( $post_type === 'chart' ) {
				$post_data['post_content'] = $row->chart_summary ?? '';
				$post_data['menu_order']   = $row->menu_order ?? 0;
			}

			// Insert post
			$post_id = wp_insert_post( $post_data );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_old_' . $post_type . '_id', $row->id );
				
				// Map metadata based on type
				$this->map_metadata( $post_id, $post_type, $row );
			}
		}
	}

	/**
	 * Map entity-specific metadata.
	 */
	private function map_metadata( $post_id, $type, $row ) {
		global $wpdb;
		
		if ( $type === 'chart' ) {
			update_post_meta( $post_id, '_title_ar', $row->title_ar ?? '' );
			update_post_meta( $post_id, '_chart_type', $row->chart_type );
			update_post_meta( $post_id, '_item_type', $row->item_type );
			update_post_meta( $post_id, '_country_code', $row->country_code );
			update_post_meta( $post_id, '_frequency', $row->frequency );
			update_post_meta( $post_id, '_platform', $row->platform ?? 'all' );
			update_post_meta( $post_id, '_cover_image_url', $row->cover_image_url ?? '' );
			update_post_meta( $post_id, '_accent_color', $row->accent_color ?? '#6366f1' );
			update_post_meta( $post_id, '_is_featured', $row->is_featured ?? 0 );
		} elseif ( $type === 'artist' ) {
			update_post_meta( $post_id, '_normalized_name', $row->normalized_name );
			update_post_meta( $post_id, '_spotify_id', $row->spotify_id );
			update_post_meta( $post_id, '_artist_image_url', $row->image );
			if ( !empty($row->metadata_json) ) {
				$meta = json_decode($row->metadata_json, true);
				if (is_array($meta)) {
					foreach ($meta as $k => $v) update_post_meta($post_id, '_artist_' . $k, $v);
				}
			}
		} elseif ( $type === 'track' ) {
			update_post_meta( $post_id, '_normalized_title', mb_strtolower($row->title) );
			update_post_meta( $post_id, '_spotify_id', $row->spotify_id );
			update_post_meta( $post_id, '_youtube_id', $row->youtube_id );
			update_post_meta( $post_id, '_cover_image_url', $row->cover_image );
			update_post_meta( $post_id, '_old_primary_artist_id', $row->primary_artist_id );
			
			// Denormalize artist name
			$artist_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $row->primary_artist_id));
			if($artist_name) update_post_meta($post_id, '_artist_names_denormalized', $artist_name);
		} elseif ( $type === 'video' ) {
			update_post_meta( $post_id, '_normalized_title', mb_strtolower($row->title) );
			update_post_meta( $post_id, '_youtube_id', $row->youtube_id );
			update_post_meta( $post_id, '_thumbnail_url', $row->thumbnail );
			update_post_meta( $post_id, '_old_primary_artist_id', $row->primary_artist_id );
			update_post_meta( $post_id, '_old_related_track_id', $row->related_track_id );
			
			$artist_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $row->primary_artist_id));
			if($artist_name) update_post_meta($post_id, '_artist_names_denormalized', $artist_name);
		}
	}

	public function resolve_relationships() {
		// Batched relationship resolution
		$this->resolve_id_links( 'track', '_old_primary_artist_id', '_primary_artist_id', 'artist' );
		$this->resolve_id_links( 'video', '_old_primary_artist_id', '_primary_artist_id', 'artist' );
		$this->resolve_id_links( 'video', '_old_related_track_id', '_related_track_id', 'track' );
	}

	private function resolve_id_links( $post_type, $old_key, $new_key, $target_post_type ) {
		$posts = get_posts( array(
			'post_type' => $post_type,
			'posts_per_page' => 100,
			'meta_query' => array(
				array( 'key' => $old_key, 'compare' => 'EXISTS' ),
				array( 'key' => $new_key, 'compare' => 'NOT EXISTS' ),
			),
			'post_status' => 'any'
		) );

		foreach ( $posts as $p ) {
			$old_id = get_post_meta( $p->ID, $old_key, true );
			$new_id = $this->get_post_id_by_old_id( $target_post_type, $old_id );
			if ( $new_id ) update_post_meta( $p->ID, $new_key, $new_id );
		}
	}

	public function migrate_relationship_tables() {
		global $wpdb;
		$batch = 500;
		
		foreach ( array('track', 'video') as $type ) {
			$table = $wpdb->prefix . "charts_{$type}_artists";
			if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) continue;

			$option_key = "charts_migrated_rels_{$type}_offset";
			$offset = (int) get_option( $option_key, 0 );

			$rels = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY id ASC LIMIT %d OFFSET %d", $batch, $offset ) );
			if ( empty($rels) ) continue;

			foreach ( $rels as $rel ) {
				$target_id = ($type === 'track') ? $rel->track_id : $rel->video_id;
				$post_id = $this->get_post_id_by_old_id( $type, $target_id );
				$artist_id = $this->get_post_id_by_old_id( 'artist', $rel->artist_id );
				
				if ( $post_id && $artist_id ) {
					$existing = (array) get_post_meta( $post_id, '_artist_ids', true );
					if ( ! in_array( $artist_id, $existing ) ) {
						$existing[] = $artist_id;
						update_post_meta( $post_id, '_artist_ids', array_values(array_unique(array_filter($existing))) );
					}
				}
			}

			update_option( $option_key, $offset + count($rels) );
		}
	}

	public function update_all_table_links() {
		$types = array( 'chart', 'artist', 'track', 'video' );
		foreach ( $types as $type ) {
			$this->batch_update_table_links( $type );
		}
	}

	private function batch_update_table_links( $type ) {
		global $wpdb;
		
		$mapping = get_posts( array(
			'post_type'      => $type,
			'posts_per_page' => 200,
			'meta_key'       => '_old_' . $type . '_id',
			'meta_query' => array(
				array( 'key' => '_table_links_synced', 'compare' => 'NOT EXISTS' )
			),
			'post_status'    => 'any',
			'fields'         => 'ids'
		) );

		foreach ( $mapping as $post_id ) {
			$old_id = get_post_meta( $post_id, '_old_' . $type . '_id', true );
			if ( ! $old_id ) continue;

			$wpdb->update( "{$wpdb->prefix}charts_entries", array( 'item_id' => $post_id ), array( 'item_id' => $old_id, 'item_type' => $type ) );
			$wpdb->update( "{$wpdb->prefix}charts_intelligence", array( 'entity_id' => $post_id ), array( 'entity_id' => $old_id, 'entity_type' => $type ) );
			$wpdb->update( "{$wpdb->prefix}charts_insights", array( 'entity_id' => $post_id ), array( 'entity_id' => $old_id, 'entity_type' => $type ) );
			$wpdb->update( "{$wpdb->prefix}charts_aliases", array( 'entity_id' => $post_id ), array( 'entity_id' => $old_id, 'entity_type' => $type ) );

			if ( $type === 'artist' ) {
				$wpdb->update( "{$wpdb->prefix}charts_track_artists", array( 'artist_id' => $post_id ), array( 'artist_id' => $old_id ) );
				$wpdb->update( "{$wpdb->prefix}charts_video_artists", array( 'artist_id' => $post_id ), array( 'artist_id' => $old_id ) );
			} elseif ( $type === 'track' ) {
				$wpdb->update( "{$wpdb->prefix}charts_track_artists", array( 'track_id' => $post_id ), array( 'track_id' => $old_id ) );
			}

			update_post_meta( $post_id, '_table_links_synced', '1' );
		}
	}

	private function is_migration_complete() {
		$cache = get_transient( 'charts_migration_status_check' );
		if ( $cache === 'complete' ) return true;
		if ( $cache === 'in_progress' ) return false;

		global $wpdb;
		$types = array( 'charts_definitions' => 'chart', 'charts_artists' => 'artist', 'charts_tracks' => 'track', 'charts_videos' => 'video' );
		
		foreach ( $types as $table_name => $post_type ) {
			$table = $wpdb->prefix . $table_name;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) {
				$unmigrated = $wpdb->get_var( $wpdb->prepare( "
					SELECT COUNT(*) FROM $table t
					LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_key = %s AND pm.meta_value = CAST(t.id AS CHAR)
					WHERE pm.post_id IS NULL
				", '_old_' . $post_type . '_id' ) );
				if ( $unmigrated > 0 ) {
					set_transient( 'charts_migration_status_check', 'in_progress', HOUR_IN_SECONDS );
					return false;
				}
			}
		}

		// Check junction tables
		foreach ( array('track', 'video') as $type ) {
			$table = $wpdb->prefix . "charts_{$type}_artists";
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) {
				$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
				$offset = (int) get_option( "charts_migrated_rels_{$type}_offset", 0 );
				if ( $offset < $total ) {
					set_transient( 'charts_migration_status_check', 'in_progress', HOUR_IN_SECONDS );
					return false;
				}
			}
		}

		set_transient( 'charts_migration_status_check', 'complete', DAY_IN_SECONDS );
		return true;
	}

	private function get_post_id_by_old_id( $type, $old_id ) {
		if ( ! $old_id ) return null;
		$res = get_posts( array(
			'post_type'  => $type,
			'meta_key'   => '_old_' . $type . '_id',
			'meta_value' => $old_id,
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields'     => 'ids'
		) );
		return ! empty($res) ? $res[0] : null;
	}
}
