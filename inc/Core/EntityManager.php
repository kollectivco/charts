<?php

namespace Charts\Core;

/**
 * Handle creation and resolution of core entities (Artists, Tracks, Clips, Charts).
 * Bridges Custom Post Types with historical Custom Tables.
 */
class EntityManager {

	/**
	 * Initialize hooks for synchronization.
	 */
	public static function init() {
		add_action( 'save_post', array( self::class, 'handle_post_sync' ), 20, 2 );
	}

	/**
	 * Sync CPT updates back to custom tables.
	 */
	public static function handle_post_sync( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		
		$allowed_types = array( 'chart', 'artist', 'track', 'video' );
		if ( ! in_array( $post->post_type, $allowed_types ) ) return;

		// Perform sync
		self::sync_to_custom_table( $post->post_type, $post_id );
	}

	/**
	 * Resolve or create an Artist.
	 */
	public static function ensure_artist( $display_name, $data = array() ) {
		// 1. Check CPT first
		$normalized = mb_strtolower( trim( $display_name ) );
		$spotify_id = $data['spotify_id'] ?? null;

		$args = array(
			'post_type'  => 'artist',
			'meta_key'   => '_normalized_name',
			'meta_value' => $normalized,
			'posts_per_page' => 1,
			'post_status' => 'publish'
		);

		if ( $spotify_id ) {
			$spotify_query = get_posts( array(
				'post_type'  => 'artist',
				'meta_key'   => '_spotify_id',
				'meta_value' => $spotify_id,
				'posts_per_page' => 1
			) );
			if ( ! empty( $spotify_query ) ) return $spotify_query[0]->ID;
		}

		$existing = get_posts( $args );
		if ( ! empty( $existing ) ) return $existing[0]->ID;

		// 1.5 Check legacy table before creating new to prevent race conditions
		global $wpdb;
		$legacy_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_artists WHERE normalized_name = %s", $normalized ) );
		
		// 2. Create if not exists
		$slug = sanitize_title( $display_name );
		$post_id = wp_insert_post( array(
			'import_id'   => $legacy_id, // Preserve ID if it exists in legacy table
			'post_title'  => $display_name,
			'post_name'   => self::unique_slug( 'artist', $slug ),
			'post_type'   => 'artist',
			'post_status' => 'publish',
		) );

		if ( $post_id ) {
			if ( $legacy_id ) update_post_meta( $post_id, '_old_artist_id', $legacy_id );
			update_post_meta( $post_id, '_normalized_name', $normalized );
			if ( $spotify_id ) update_post_meta( $post_id, '_spotify_id', $spotify_id );
			if ( ! empty( $data['image'] ) ) update_post_meta( $post_id, '_artist_image_url', $data['image'] );
			
			$franko = \Charts\Services\Normalizer::to_franko( $display_name );
			if ( $franko !== $display_name ) update_post_meta( $post_id, '_display_name_franko', $franko );

			// Legacy sync (optional, for safety during migration phase)
			self::sync_to_custom_table( 'artist', $post_id );
		}

		return $post_id;
	}

	/**
	 * Resolve or create a Track.
	 */
	public static function ensure_track( $title, $artist_id, $data = array() ) {
		$normalized = mb_strtolower( trim( $title ) );
		$spotify_id = $data['spotify_id'] ?? null;

		if ( $spotify_id ) {
			$spotify_query = get_posts( array(
				'post_type'  => 'track',
				'meta_key'   => '_spotify_id',
				'meta_value' => $spotify_id,
				'posts_per_page' => 1,
				'fields' => 'ids'
			) );
			if ( ! empty( $spotify_query ) ) return $spotify_query[0];
		}

		$existing = get_posts( array(
			'post_type'  => 'track',
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => '_normalized_title', 'value' => $normalized ),
				array( 'key' => '_primary_artist_id', 'value' => $artist_id ),
			),
			'posts_per_page' => 1,
			'fields' => 'ids'
		) );
		if ( ! empty( $existing ) ) return $existing[0];

		// check legacy
		global $wpdb;
		$legacy_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_tracks WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );

		$post_id = wp_insert_post( array(
			'import_id'   => $legacy_id,
			'post_title'  => $title,
			'post_name'   => self::unique_slug( 'track', sanitize_title( $title . '-' . $artist_id ) ),
			'post_type'   => 'track',
			'post_status' => 'publish',
		) );

		if ( $post_id ) {
			if ( $legacy_id ) update_post_meta( $post_id, '_old_track_id', $legacy_id );
			update_post_meta( $post_id, '_normalized_title', $normalized );
			update_post_meta( $post_id, '_primary_artist_id', $artist_id );
			
			// Multi-artist support
			$artist_ids = array_unique( array_filter( array( $artist_id ) ) );
			update_post_meta( $post_id, '_artist_ids', $artist_ids );

			if ( $spotify_id ) update_post_meta( $post_id, '_spotify_id', $spotify_id );
			if ( ! empty( $data['youtube_id'] ) ) update_post_meta( $post_id, '_youtube_id', $data['youtube_id'] );
			if ( ! empty( $data['cover_image'] ) ) update_post_meta( $post_id, '_cover_image_url', $data['cover_image'] );
			
			if ( $franko !== $title ) update_post_meta( $post_id, '_title_franko', $franko );

			// Denormalize artist names for SEO speed
			$artist_post = get_post( $artist_id );
			if ( $artist_post ) {
				update_post_meta( $post_id, '_artist_names_denormalized', $artist_post->post_title );
			}

			// Legacy sync kept for side-effects if needed, but results in table insert
			self::sync_to_custom_table( 'track', $post_id );
		}

		return $post_id;
	}

	/**
	 * Resolve or create a Video/Clip.
	 */
	public static function ensure_video( $title, $artist_id, $data = array() ) {
		$normalized = mb_strtolower( trim( $title ) );
		$youtube_id = $data['youtube_id'] ?? null;

		if ( $youtube_id ) {
			$yt_query = get_posts( array(
				'post_type'  => 'video',
				'meta_key'   => '_youtube_id',
				'meta_value' => $youtube_id,
				'posts_per_page' => 1,
				'fields' => 'ids'
			) );
			if ( ! empty( $yt_query ) ) return $yt_query[0];
		}

		$existing = get_posts( array(
			'post_type'  => 'video',
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => '_normalized_title', 'value' => $normalized ),
				array( 'key' => '_primary_artist_id', 'value' => $artist_id ),
			),
			'posts_per_page' => 1,
			'fields' => 'ids'
		) );
		if ( ! empty( $existing ) ) return $existing[0];

		// check legacy
		global $wpdb;
		$legacy_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_videos WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );

		$post_id = wp_insert_post( array(
			'import_id'   => $legacy_id,
			'post_title'  => $title,
			'post_name'   => self::unique_slug( 'video', sanitize_title( $title . '-' . $artist_id ) ),
			'post_type'   => 'video',
			'post_status' => 'publish',
		) );

		if ( $post_id ) {
			if ( $legacy_id ) update_post_meta( $post_id, '_old_video_id', $legacy_id );
			update_post_meta( $post_id, '_normalized_title', $normalized );
			update_post_meta( $post_id, '_primary_artist_id', $artist_id );

			// Multi-artist support
			$artist_ids = array_unique( array_filter( array( $artist_id ) ) );
			update_post_meta( $post_id, '_artist_ids', $artist_ids );

			if ( $youtube_id ) update_post_meta( $post_id, '_youtube_id', $youtube_id );
			if ( ! empty( $data['thumbnail'] ) ) update_post_meta( $post_id, '_thumbnail_url', $data['thumbnail'] );
			
			if ( ! empty( $data['track_id'] ) ) {
				update_post_meta( $post_id, '_related_track_id', $data['track_id'] );
			}

			// Denormalize artist names for SEO speed
			$artist_post = get_post( $artist_id );
			if ( $artist_post ) {
				update_post_meta( $post_id, '_artist_names_denormalized', $artist_post->post_title );
			}

			self::sync_to_custom_table( 'video', $post_id );
		}

		return $post_id;
	}

	/**
	 * Helper to link multiple artists to a track or video.
	 */
	public static function link_artists( $post_id, array $artist_ids ) {
		$existing = (array) get_post_meta( $post_id, '_artist_ids', true );
		$merged = array_unique( array_merge( $existing, $artist_ids ) );
		$merged = array_values( array_filter( $merged ) );
		update_post_meta( $post_id, '_artist_ids', $merged );
		
		// Legacy sync to custom tables
		global $wpdb;
		$type = get_post_type( $post_id );
		$table = ( $type === 'track' ) ? "{$wpdb->prefix}charts_track_artists" : "{$wpdb->prefix}charts_video_artists";
		$id_col = ( $type === 'track' ) ? 'track_id' : 'video_id';
		
		foreach ( $artist_ids as $a_id ) {
			$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table ($id_col, artist_id) VALUES (%d, %d)", $post_id, $a_id ) );
		}
	}

	/**
	 * Ensures a slug is unique for a post type.
	 */
	private static function unique_slug( $post_type, $slug ) {
		$original_slug = $slug;
		$count = 1;
		while ( get_page_by_path( $slug, OBJECT, $post_type ) ) {
			$slug = $original_slug . '-' . $count;
			$count++;
		}
		return $slug;
	}

	/**
	 * Maintain backward compatibility with custom tables if requested.
	 * This ensures that older queries still find the records.
	 */
	private static function sync_to_custom_table( $type, $post_id ) {
		global $wpdb;
		$post = get_post( $post_id );
		if ( ! $post ) return;

		$table = '';
		$data  = array();

		if ( $type === 'chart' ) {
			$table = $wpdb->prefix . 'charts_definitions';
			$data = array(
				'id'              => $post_id,
				'title'           => $post->post_title,
				'title_ar'        => get_post_meta( $post_id, '_title_ar', true ),
				'slug'            => $post->post_name,
				'chart_summary'   => $post->post_content,
				'chart_type'      => get_post_meta( $post_id, '_chart_type', true ),
				'item_type'       => get_post_meta( $post_id, '_item_type', true ),
				'country_code'    => get_post_meta( $post_id, '_country_code', true ),
				'frequency'       => get_post_meta( $post_id, '_frequency', true ),
				'platform'        => get_post_meta( $post_id, '_platform', true ),
				'cover_image_url' => get_post_meta( $post_id, '_cover_image_url', true ),
				'accent_color'    => get_post_meta( $post_id, '_accent_color', true ),
				'is_public'       => ( $post->post_status === 'publish' ? 1 : 0 ),
				'is_featured'     => (int) get_post_meta( $post_id, '_is_featured', true ),
				'archive_enabled' => (int) get_post_meta( $post_id, '_archive_enabled', true ),
				'menu_order'      => $post->menu_order,
				'created_at'      => $post->post_date,
				'updated_at'      => current_time( 'mysql' )
			);
		} elseif ( $type === 'artist' ) {
			$table = $wpdb->prefix . 'charts_artists';
			$data = array(
				'id'              => $post_id,
				'display_name'    => $post->post_title,
				'normalized_name' => get_post_meta( $post_id, '_normalized_name', true ),
				'slug'            => $post->post_name,
				'spotify_id'      => get_post_meta( $post_id, '_spotify_id', true ),
				'image'           => get_post_meta( $post_id, '_artist_image_url', true ),
				'created_at'      => $post->post_date
			);
		} elseif ( $type === 'track' ) {
			$table = $wpdb->prefix . 'charts_tracks';
			$data = array(
				'id'                => $post_id,
				'title'             => $post->post_title,
				'normalized_title'  => get_post_meta( $post_id, '_normalized_title', true ),
				'slug'              => $post->post_name,
				'primary_artist_id' => get_post_meta( $post_id, '_primary_artist_id', true ),
				'spotify_id'        => get_post_meta( $post_id, '_spotify_id', true ),
				'cover_image'       => get_post_meta( $post_id, '_cover_image_url', true ),
				'created_at'        => $post->post_date
			);
		} elseif ( $type === 'video' ) {
			$table = $wpdb->prefix . 'charts_videos';
			$data = array(
				'id'                => $post_id,
				'title'             => $post->post_title,
				'normalized_title'  => get_post_meta( $post_id, '_normalized_title', true ),
				'slug'              => $post->post_name,
				'primary_artist_id' => get_post_meta( $post_id, '_primary_artist_id', true ),
				'youtube_id'        => get_post_meta( $post_id, '_youtube_id', true ),
				'thumbnail'         => get_post_meta( $post_id, '_thumbnail_url', true ),
				'created_at'        => $post->post_date
			);
		}

		if ( $table && ! empty( $data ) ) {
			$wpdb->replace( $table, $data );
		}
	}
}
