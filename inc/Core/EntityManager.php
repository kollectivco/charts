<?php

namespace Charts\Core;

/**
 * Handle creation and resolution of core entities (Artists, Tracks, Clips, Charts).
 * Restored legacy architecture using custom SQL tables.
 */
class EntityManager {

	/**
	 * Register hooks for CPT synchronization.
	 */
	public static function init() {
		add_action( 'save_post_artist', array( self::class, 'sync_cpt_to_table_on_save' ), 10, 2 );
		add_action( 'save_post_track', array( self::class, 'sync_cpt_to_table_on_save' ), 10, 2 );
		add_action( 'save_post_video', array( self::class, 'sync_cpt_to_table_on_save' ), 10, 2 );
	}

	public static function get_entity_by_slug( $type, $slug ) {
		// 1. Try Native CPT
		$posts = get_posts( array(
			'post_type'  => $type,
			'name'       => $slug,
			'posts_per_page' => 1,
			'post_status' => 'any'
		) );
		
		if ( ! empty( $posts ) ) {
			return self::map_post_to_entity( $posts[0] );
		}

		// 2. Fallback to SQL Bridge
		global $wpdb;
		$table = $wpdb->prefix . ( $type === 'artist' ? 'charts_artists' : ( ($type === 'video') ? 'charts_videos' : 'charts_tracks' ) );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
		
		if ( $row ) {
			// Check if it has a native shadow we missed (unlikely if name matched, but possible)
			$post_id = self::get_post_id_by_legacy_id( $type, $row->id );
			if ( $post_id ) {
				return self::map_post_to_entity( get_post( $post_id ) );
			}
			return $row;
		}

		return null;
	}

	private static function map_post_to_entity( $post ) {
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

	public static function sync_cpt_to_table_on_save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		self::sync_to_table( $post->post_type, $post_id );
	}

	/**
	 * Bridge: Get CPT Post ID by Legacy SQL ID.
	 */
	public static function get_post_id_by_legacy_id( $type, $legacy_id ) {
		$cache_key = "kcharts_legacy_{$type}_{$legacy_id}";
		$cached = wp_cache_get( $cache_key, 'kcharts' );
		if ( $cached !== false ) return $cached;

		$posts = get_posts( array(
			'post_type'  => $type,
			'meta_key'   => '_kcharts_legacy_id',
			'meta_value' => $legacy_id,
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields' => 'ids'
		) );
		$id = ! empty( $posts ) ? $posts[0] : false;
		wp_cache_set( $cache_key, $id, 'kcharts' );
		return $id;
	}

	/**
	 * Promote a legacy entity to a native CPT.
	 */
	public static function promote_to_native( $type, $legacy_id ) {
		global $wpdb;
		$existing = self::get_post_id_by_legacy_id( $type, $legacy_id );
		if ( $existing ) return $existing;

		$table = $wpdb->prefix . ( $type === 'artist' ? 'charts_artists' : ( ($type==='video') ? 'charts_videos' : 'charts_tracks' ) );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $legacy_id ) );
		if ( ! $row ) return false;

		$post_data = array(
			'post_title'   => ( $type === 'artist' ) ? $row->display_name : $row->title,
			'post_name'    => $row->slug,
			'post_type'    => $type,
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post_data );
		if ( $post_id ) {
			update_post_meta( $post_id, '_kcharts_legacy_id', $legacy_id );
			
			if ( $type === 'artist' ) {
				update_post_meta( $post_id, '_spotify_id', $row->spotify_id );
				if ( ! empty( $row->image ) ) update_post_meta( $post_id, '_legacy_image', $row->image );
			} elseif ( $type === 'video' ) {
				update_post_meta( $post_id, '_youtube_id', $row->youtube_id );
				update_post_meta( $post_id, '_primary_artist_id', $row->primary_artist_id );
				if ( ! empty( $row->thumbnail ) ) update_post_meta( $post_id, '_legacy_image', $row->thumbnail );
			} else {
				update_post_meta( $post_id, '_spotify_id', $row->spotify_id );
				update_post_meta( $post_id, '_youtube_id', $row->youtube_id );
				update_post_meta( $post_id, '_primary_artist_id', $row->primary_artist_id );
				if ( ! empty( $row->cover_image ) ) update_post_meta( $post_id, '_legacy_image', $row->cover_image );
			}

			update_post_meta( $post_id, '_kcharts_is_native', 1 );
		}

		return $post_id;
	}

	/**
	 * Native-First Resolution: Resolve or create an Artist.
	 */
	public static function ensure_artist( $display_name, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $display_name ) );
		$slug = sanitize_title( $display_name );

		// 1. Try Native CPT Lookup
		$query_args = array(
			'post_type'  => 'artist',
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields' => 'ids'
		);

		if ( ! empty( $data['spotify_id'] ) ) {
			$query_args['meta_key'] = '_spotify_id';
			$query_args['meta_value'] = $data['spotify_id'];
		} else {
			$query_args['name'] = $slug;
		}

		$posts = get_posts( $query_args );
		if ( ! empty( $posts ) ) return $posts[0];

		// 2. Fallback to Legacy SQL
		$table = $wpdb->prefix . 'charts_artists';
		$sql_id = 0;
		if ( ! empty( $data['spotify_id'] ) ) {
			$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
		}
		if ( ! $sql_id ) {
			$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		}

		// 3. Create CPT (Native-First Architecture)
		$post_data = array(
			'post_title'  => $display_name,
			'post_name'   => $slug,
			'post_type'   => 'artist',
			'post_status' => 'publish',
		);
		$post_id = wp_insert_post( $post_data );

		if ( $post_id ) {
			if ( ! empty( $data['spotify_id'] ) ) update_post_meta( $post_id, '_spotify_id', $data['spotify_id'] );
			if ( ! empty( $data['image'] ) ) update_post_meta( $post_id, '_legacy_image', $data['image'] );
			
			// Bridge to SQL if it was already there
			if ( $sql_id ) {
				update_post_meta( $post_id, '_kcharts_legacy_id', $sql_id );
			} else {
				// Create legacy placeholder to maintain junction stability
				$wpdb->insert( $table, array(
					'display_name'    => $display_name,
					'normalized_name' => $normalized,
					'slug'            => $slug,
					'spotify_id'      => $data['spotify_id'] ?? null,
					'image'           => $data['image'] ?? null,
					'created_at'      => current_time( 'mysql' ),
				) );
				update_post_meta( $post_id, '_kcharts_legacy_id', $wpdb->insert_id );
			}
		}

		return $post_id;
	}

	/**
	 * Native-First Resolution: Resolve or create a Track.
	 */
	public static function ensure_track( $title, $artist_id, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $title ) );
		$slug = sanitize_title( $title . '-' . $artist_id );

		// 1. Try Native CPT Lookup
		$query_args = array(
			'post_type'  => 'track',
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields' => 'ids'
		);

		if ( ! empty( $data['spotify_id'] ) ) {
			$query_args['meta_key'] = '_spotify_id';
			$query_args['meta_value'] = $data['spotify_id'];
		} else {
			$query_args['name'] = $slug;
		}

		$posts = get_posts( $query_args );
		if ( ! empty( $posts ) ) return $posts[0];

		// 2. Fallback to Legacy SQL
		$table = $wpdb->prefix . 'charts_tracks';
		$sql_id = 0;
		if ( ! empty( $data['spotify_id'] ) ) {
			$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
		}
		if ( ! $sql_id ) {
			$sql_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		}

		// 3. Create CPT
		$post_id = wp_insert_post( array(
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_type'   => 'track',
			'post_status' => 'publish',
		) );

		if ( $post_id ) {
			update_post_meta( $post_id, '_primary_artist_id', $artist_id );
			if ( ! empty( $data['spotify_id'] ) ) update_post_meta( $post_id, '_spotify_id', $data['spotify_id'] );
			if ( ! empty( $data['cover_image'] ) ) update_post_meta( $post_id, '_legacy_image', $data['cover_image'] );

			if ( $sql_id ) {
				update_post_meta( $post_id, '_kcharts_legacy_id', $sql_id );
			} else {
				$wpdb->insert( $table, array(
					'title'             => $title,
					'normalized_title'  => $normalized,
					'slug'              => $slug,
					'primary_artist_id' => $artist_id,
					'spotify_id'        => $data['spotify_id'] ?? null,
					'cover_image'       => $data['cover_image'] ?? null,
					'created_at'        => current_time( 'mysql' ),
				) );
				update_post_meta( $post_id, '_kcharts_legacy_id', $wpdb->insert_id );
			}
			
			// Junction linking (Legacy table compatibility)
			self::link_artist_to_item( 'track', get_post_meta( $post_id, '_kcharts_legacy_id', true ), $artist_id );
		}

		return $post_id;
	}

	/**
	 * Resolve or create a Video/Clip in the legacy tables.
	 */
	public static function ensure_video( $title, $artist_id, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $title ) );
		$table = $wpdb->prefix . 'charts_videos';

		// 1. Check by youtube_id
		if ( ! empty( $data['youtube_id'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE youtube_id = %s", $data['youtube_id'] ) );
			if ( $id ) return (int) $id;
		}

		// 2. Check by normalized title and artist
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) return (int) $id;

		// 3. Create
		$slug = self::unique_slug( 'charts_videos', sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $data['youtube_id'] ?? null,
			'thumbnail'         => $data['thumbnail'] ?? null,
			'related_track_id'  => $data['track_id'] ?? null,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' )
		) );

		$video_id = $wpdb->insert_id;
		if ( $video_id ) {
			self::link_artist_to_item( 'video', $video_id, $artist_id );
		}

		return $video_id;
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
	 * Helper for bulk artist linking.
	 */
	public static function link_artists( $id, array $artist_ids, $type = 'track' ) {
		foreach ( $artist_ids as $a_id ) {
			self::link_artist_to_item( $type, $id, $a_id );
		}
	}

	/**
	 * Generate a unique slug for a custom table.
	 */
	private static function unique_slug( $table_name_no_prefix, $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . $table_name_no_prefix;
		$original_slug = $slug;
		$count = 1;

		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) ) ) {
			$slug = $original_slug . '-' . $count;
			$count++;
		}
		return $slug;
	}
}
