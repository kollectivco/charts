<?php

namespace Charts\Core;

/**
 * Handle creation and resolution of core entities (Artists, Tracks, Clips, Charts).
 * Restored legacy architecture using custom SQL tables.
 */
class EntityManager {

	/**
	 * No hooks needed for legacy SQL architecture.
	 */
	public static function init() {
		// No-op
	}

	/**
	 * Resolve or create an Artist in the legacy tables.
	 */
	public static function ensure_artist( $display_name, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $display_name ) );
		$table = $wpdb->prefix . 'charts_artists';

		// 1. Check by spotify_id if provided
		if ( ! empty( $data['spotify_id'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
			if ( $id ) return (int) $id;
		}

		// 2. Check by normalized name
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( $id ) return (int) $id;

		// 3. Create if not found
		$slug = self::unique_slug( 'charts_artists', sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'normalized_name' => $normalized,
			'slug'            => $slug,
			'spotify_id'      => $data['spotify_id'] ?? null,
			'image'           => $data['image'] ?? null,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' )
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Resolve or create a Track in the legacy tables.
	 */
	public static function ensure_track( $title, $artist_id, $data = array() ) {
		global $wpdb;
		$normalized = mb_strtolower( trim( $title ) );
		$table = $wpdb->prefix . 'charts_tracks';

		// 1. Check by spotify_id
		if ( ! empty( $data['spotify_id'] ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
			if ( $id ) return (int) $id;
		}

		// 2. Check by normalized title and artist
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", $normalized, $artist_id ) );
		if ( $id ) return (int) $id;

		// 3. Create
		$slug = self::unique_slug( 'charts_tracks', sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'spotify_id'        => $data['spotify_id'] ?? null,
			'youtube_id'        => $data['youtube_id'] ?? null,
			'cover_image'       => $data['cover_image'] ?? null,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' )
		) );

		$track_id = $wpdb->insert_id;
		if ( $track_id ) {
			self::link_artist_to_item( 'track', $track_id, $artist_id );
		}

		return $track_id;
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
