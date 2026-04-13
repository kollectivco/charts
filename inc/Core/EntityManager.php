<?php

namespace Charts\Core;

/**
 * Entity Manager: SQL-Baseline Architecture (Phase 1)
 * 
 * Handles resolution and bridge promotion for Artists, Tracks, and Videos.
 * Legacy SQL tables are the primary source of truth.
 * Native CPTs are manual opt-in shadows.
 */
class EntityManager {

	/**
	 * Get entity by slug, prioritizing SQL baseline for stability.
	 */
	public static function get_entity_by_slug( $type, $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . ( $type === 'artist' ? 'charts_artists' : ( ($type === 'video') ? 'charts_videos' : 'charts_tracks' ) );
		
		if ( ! $wpdb->get_var("SHOW TABLES LIKE '$table'") ) {
			return null;
		}

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
		
		if ( $row ) {
			// Find native bridge but only for Charts (other CPTs are rolled back)
			$row->native_post_id = ( $type === 'chart' ) ? self::get_post_id_by_legacy_id( $type, $row->id ) : 0;
			return $row;
		}

		return null;
	}

	/**
	 * Map a CPT post to a legacy-compatible object.
	 */
	public static function map_post_to_entity( $post ) {
		if ( ! $post ) return null;
		
		$type = $post->post_type;
		$obj = new \stdClass();
		$obj->id            = $post->ID;
		$obj->legacy_id     = get_post_meta( $post->ID, '_kcharts_legacy_id', true );
		$obj->slug          = $post->post_name;
		$obj->spotify_id    = get_post_meta( $post->ID, '_spotify_id', true );
		$obj->youtube_id    = get_post_meta( $post->ID, '_youtube_id', true );
		
		if ( $type === 'artist' ) {
			$obj->display_name = $post->post_title;
			$obj->image        = get_post_meta( $post->ID, '_legacy_image', true ) ?: get_the_post_thumbnail_url( $post->ID, 'full' );
		} else {
			$obj->title        = $post->post_title;
			$obj->cover_image  = get_post_meta( $post->ID, '_legacy_image', true ) ?: get_the_post_thumbnail_url( $post->ID, 'full' );
			$obj->thumbnail    = $obj->cover_image;
			$obj->primary_artist_id = get_post_meta( $post->ID, '_primary_artist_id', true );
		}
		
		return $obj;
	}

	/**
	 * Promote an SQL record to a Native CPT (Shadow).
	 */
	public static function promote_to_native( $type, $legacy_id ) {
		global $wpdb;
		
		$existing = self::get_post_id_by_legacy_id( $type, $legacy_id );
		if ( $existing ) return $existing;

		$table = $wpdb->prefix . ( $type === 'artist' ? 'charts_artists' : ( ($type==='video') ? 'charts_videos' : 'charts_tracks' ) );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $legacy_id ) );
		if ( ! $row ) return false;

		$post_data = array(
			'post_title'   => ( $type === 'artist' ? $row->display_name : $row->title ),
			'post_name'    => $row->slug,
			'post_type'    => $type,
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error($post_id) ) return false;

		// Link bridge
		update_post_meta( $post_id, '_kcharts_legacy_id', $legacy_id );

		// Sync core metadata
		if ( $type === 'artist' ) {
			if ( ! empty( $row->spotify_id ) ) update_post_meta( $post_id, '_spotify_id', $row->spotify_id );
			if ( ! empty( $row->image ) ) update_post_meta( $post_id, '_legacy_image', $row->image );
		} else {
			if ( ! empty( $row->primary_artist_id ) ) update_post_meta( $post_id, '_primary_artist_id', $row->primary_artist_id );
			$img = ( $type === 'video' ? $row->thumbnail : $row->cover_image );
			if ( ! empty( $img ) ) update_post_meta( $post_id, '_legacy_image', $img );
			if ( $type === 'video' && ! empty( $row->youtube_id ) ) update_post_meta( $post_id, '_youtube_id', $row->youtube_id );
		}

		return $post_id;
	}

	/**
	 * Find a Post ID by its mapped Legacy ID.
	 */
	public static function get_post_id_by_legacy_id( $type, $legacy_id ) {
		$posts = get_posts( array(
			'post_type'  => $type,
			'meta_key'   => '_kcharts_legacy_id',
			'meta_value' => $legacy_id,
			'posts_per_page' => 1,
			'fields'     => 'ids',
			'post_status' => 'any'
		) );
		return ! empty( $posts ) ? $posts[0] : 0;
	}

	/**
	 * SQL-Baseline: Resolve or create an Artist.
	 */
	public static function ensure_artist( $display_name, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $display_name ) );
		$slug = sanitize_title( $display_name );
		$table = $wpdb->prefix . 'charts_artists';

		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( ! $existing_id && ! empty( $data['spotify_id'] ) ) {
			$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
		}

		if ( $existing_id ) return (int) $existing_id;

		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'normalized_name' => $normalized,
			'slug'            => $slug,
			'spotify_id'      => $data['spotify_id'] ?? null,
			'image'           => $data['image'] ?? null,
			'created_at'      => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * SQL-Baseline: Resolve or create a Track.
	 */
	public static function ensure_track( $title, $artist_id, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $title ) );
		$table = $wpdb->prefix . 'charts_tracks';

		$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( ! $sql_id && ! empty( $data['spotify_id'] ) ) {
			$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
		}

		if ( $sql_id ) return (int) $sql_id;

		$slug = sanitize_title( $title . '-' . $artist_id );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'spotify_id'        => $data['spotify_id'] ?? null,
			'cover_image'       => $data['cover_image'] ?? null,
			'created_at'        => current_time( 'mysql' ),
		) );
		
		$track_id = $wpdb->insert_id;
		if ( $track_id ) {
			self::link_artist_to_item( 'track', $track_id, $artist_id );
		}
		return (int) $track_id;
	}

	/**
	 * SQL-Baseline: Resolve or create a Video.
	 */
	public static function ensure_video( $title, $artist_id, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $title ) );
		$table = $wpdb->prefix . 'charts_videos';

		if ( ! empty( $data['youtube_id'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $data['youtube_id'] ) );
			if ( $id ) return (int) $id;
		}

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) return (int) $id;

		$slug = sanitize_title( $title . '-' . $artist_id );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $data['youtube_id'] ?? null,
			'thumbnail'         => $data['thumbnail'] ?? null,
			'created_at'        => current_time( 'mysql' ),
		) );

		$video_id = $wpdb->insert_id;
		if ( $video_id ) {
			self::link_artist_to_item( 'video', $video_id, $artist_id );
		}
		return (int) $video_id;
	}

	/**
	 * Link an artist to a track or video in the junction table.
	 */
	public static function link_artist_to_item( $type, $item_id, $artist_id ) {
		global $wpdb;
		$table = ( $type === 'track' ) ? "{$wpdb->prefix}charts_track_artists" : "{$wpdb->prefix}charts_video_artists";
		$col   = ( $type === 'track' ) ? 'track_id' : 'video_id';

		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO $table ($col, artist_id) VALUES (%d, %d)",
			$item_id, $artist_id
		) );
	}

	/**
	 * Link multiple artists to a single item (batch alias used by ImportFlow).
	 * Signature: link_artists( $item_id, $artist_ids_array, $type = 'track' )
	 */
	public static function link_artists( $item_id, array $artist_ids, $type = 'track' ) {
		foreach ( $artist_ids as $artist_id ) {
			self::link_artist_to_item( $type, (int) $item_id, (int) $artist_id );
		}
	}

	/**
	 * Search entities for manual row management.
	 */
	public static function search_entities( $type, $query, $limit = 20 ) {
		global $wpdb;
		$suffix = ( $type === 'artist' ? 'artists' : ( ($type === 'video') ? 'videos' : 'tracks' ) );
		$table  = $wpdb->prefix . 'charts_' . $suffix;
		
		$col    = ( $type === 'artist' ? 'display_name' : 'title' );
		$search = '%' . $wpdb->esc_like( $query ) . '%';
		
		$results = $wpdb->get_results( $wpdb->prepare( "
			SELECT id, $col as title, slug, " . ( $type === 'artist' ? "image" : "cover_image" ) . " as image 
			FROM $table 
			WHERE $col LIKE %s 
			ORDER BY $col ASC
			LIMIT %d
		", $search, $limit ) );
		
		// If track or video, also try to find the artist name for subtitle
		if ( $type !== 'artist' ) {
			foreach ( $results as &$r ) {
				$r->subtitle = $wpdb->get_var( $wpdb->prepare( "
					SELECT a.display_name FROM {$wpdb->prefix}charts_artists a
					JOIN {$wpdb->prefix}charts_" . $type . "_artists ja ON ja.artist_id = a.id
					WHERE ja." . $type . "_id = %d LIMIT 1
				", $r->id ) ) ?: '';
			}
		}
		
		return $results;
	}
}
