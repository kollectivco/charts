<?php

namespace Charts\Services;

/**
 * Handle entity matching logic.
 */
class Matcher {

	/**
	 * Find or Create an Artist.
	 */
	public function match_artist( $display_name ) {
		global $wpdb;

		$normalized_name = Normalizer::normalize_artist( $display_name );
		$table = $wpdb->prefix . 'charts_artists';

		// 1. Try Exact Match
		$artist_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized_name ) );
		if ( $artist_id ) {
			return $artist_id;
		}

		// 2. Try Alias Match
		$alias_table = $wpdb->prefix . 'charts_aliases';
		$artist_id = $wpdb->get_var( $wpdb->prepare( "SELECT entity_id FROM $alias_table WHERE entity_type = 'artist' AND normalized_alias = %s", $normalized_name ) );
		if ( $artist_id ) {
			return $artist_id;
		}

		// 2b. Try Franko Match
		$franko = Normalizer::to_franko( $display_name );
		if ( $franko !== $display_name ) {
			$norm_franko = mb_strtolower( $franko );
			$artist_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE display_name_franko = %s OR normalized_name = %s", $franko, $norm_franko ) );
			if ( $artist_id ) {
				return $artist_id;
			}
		}

		// 3. Create fresh
		$franko = Normalizer::to_franko( $display_name );
		$slug = sanitize_title( $display_name );
		$slug = $this->ensure_unique_slug( $table, $slug );

		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'display_name_franko' => $franko !== $display_name ? $franko : null,
			'normalized_name' => $normalized_name,
			'slug'            => $slug,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Find or Create a Track.
	 */
	public function match_track( $title, $primary_artist_id, $metadata = array() ) {
		global $wpdb;

		$normalized_title = Normalizer::normalize_title( $title );
		$table = $wpdb->prefix . 'charts_tracks';

		// 1. Try Match
		$track_id = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", 
			$normalized_title, 
			$primary_artist_id 
		) );
		
		if ( $track_id ) {
			return $track_id;
		}

		// 1b. Try Franko Match
		$franko = Normalizer::to_franko( $title );
		if ( $franko !== $title ) {
			$track_id = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $table WHERE (title_franko = %s OR normalized_title = %s) AND primary_artist_id = %d", 
				$franko, mb_strtolower($franko), $primary_artist_id 
			) );
			if ( $track_id ) return $track_id;
		}

		// 2. Create fresh
		$franko = Normalizer::to_franko( $title );
		$slug_base = $title . ' ' . $primary_artist_id;
		$slug = sanitize_title( $slug_base );
		$slug = $this->ensure_unique_slug( $table, $slug );

		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized_title,
			'slug'              => $slug,
			'primary_artist_id' => $primary_artist_id,
			'cover_image'       => $metadata['image'] ?? null,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Find or Create a Video.
	 */
	public function match_video( $title, $primary_artist_id, $track_id = null, $metadata = array() ) {
		global $wpdb;

		$normalized_title = Normalizer::normalize_title( $title );
		$table = $wpdb->prefix . 'charts_videos';

		$video_id = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", 
			$normalized_title, 
			$primary_artist_id 
		) );

		if ( $video_id ) {
			return $video_id;
		}

		$franko = Normalizer::to_franko( $title );
		$slug = sanitize_title( $title . ' ' . $primary_artist_id );
		$slug = $this->ensure_unique_slug( $table, $slug );

		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized_title,
			'slug'              => $slug,
			'primary_artist_id' => $primary_artist_id,
			'related_track_id'  => $track_id,
			'thumbnail'         => $metadata['image'] ?? null,
			'video_url'         => $metadata['source_url'] ?? null,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Find or Create an Artist with extended matching (Spotify ID).
	 */
	public function match_artist_extended( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_artists';

		// 1. Try Spotify ID Match
		if ( ! empty( $data['spotify_id'] ) ) {
			$artist_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
			if ( $artist_id ) {
				return $artist_id;
			}
		}

		// 2. Try Name Match (Normalizer handles aliases internally in real production, here we use simple normalize)
		$normalized_name = Normalizer::normalize_artist( $data['name'] );
		$artist_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized_name ) );

		if ( $artist_id ) {
			// Update with Spotify ID if missing
			if ( ! empty( $data['spotify_id'] ) ) {
				$wpdb->update( $table, array( 'spotify_id' => $data['spotify_id'] ), array( 'id' => $artist_id ) );
			}
			return $artist_id;
		}

		// 3. Create fresh
		return $this->match_artist( $data['name'] );
	}

	/**
	 * Find or Create an Album with extended matching.
	 */
	public function match_album_extended( $data, $primary_artist_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_albums';

		if ( ! empty( $data['spotify_id'] ) ) {
			$album_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
			if ( $album_id ) {
				return $album_id;
			}
		}

		$normalized_title = Normalizer::normalize_title( $data['title'] );
		$album_id = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", 
			$normalized_title, $primary_artist_id 
		) );

		if ( $album_id ) {
			if ( ! empty( $data['spotify_id'] ) ) {
				$wpdb->update( $table, array( 'spotify_id' => $data['spotify_id'] ), array( 'id' => $album_id ) );
			}
			return $album_id;
		}

		// Create
		$slug = $this->ensure_unique_slug( $table, sanitize_title( $data['title'] . ' ' . $primary_artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $data['title'],
			'normalized_title'  => $normalized_title,
			'slug'              => $slug,
			'spotify_id'        => $data['spotify_id'] ?? null,
			'primary_artist_id' => $primary_artist_id,
			'cover_image'       => $data['cover_image'] ?? null,
			'release_date'      => $data['release_date'] ?? null,
			'created_at'        => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Find or Create a Track with extended matching.
	 */
	public function match_track_extended( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_tracks';

		if ( ! empty( $data['spotify_id'] ) ) {
			$track_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE spotify_id = %s", $data['spotify_id'] ) );
			if ( $track_id ) {
				return $track_id;
			}
		}

		$normalized_title = Normalizer::normalize_title( $data['title'] );
		$track_id = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d", 
			$normalized_title, $data['primary_artist_id'] 
		) );

		if ( $track_id ) {
			if ( ! empty( $data['spotify_id'] ) ) {
				$wpdb->update( $table, array( 'spotify_id' => $data['spotify_id'] ), array( 'id' => $track_id ) );
			}
			return $track_id;
		}

		// Create
		$slug = $this->ensure_unique_slug( $table, sanitize_title( $data['title'] . ' ' . $data['primary_artist_id'] ) );
		$wpdb->insert( $table, array(
			'title'             => $data['title'],
			'normalized_title'  => $normalized_title,
			'slug'              => $slug,
			'spotify_id'        => $data['spotify_id'] ?? null,
			'primary_artist_id' => $data['primary_artist_id'],
			'album_id'          => $data['album_id'] ?? null,
			'cover_image'       => $data['cover_image'] ?? null,
			'created_at'        => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	/**
	 * Ensure slug uniqueness.
	 */
	private function ensure_unique_slug( $table, $slug ) {
		global $wpdb;
		$original_slug = $slug;
		$i = 1;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) ) ) {
			$slug = $original_slug . '-' . $i;
			$i++;
		}
		return $slug;
	}
}
