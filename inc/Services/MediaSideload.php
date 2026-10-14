<?php

namespace Charts\Services;

/**
 * MediaSideload Service
 * Automatically downloads and stores external media assets locally into
 * wp-content/uploads/charts/ to eliminate hotlinking, prevent referrer blocking,
 * improve page performance, and ensure 100% brand privacy.
 */
class MediaSideload {

	/**
	 * In-memory cache for resolved local URLs in current request.
	 */
	private static $local_cache = array();

	/**
	 * Sideload a remote image URL to the local WordPress uploads directory.
	 *
	 * @param string $remote_url Remote image URL (Spotify, Soundcharts, YouTube, etc.)
	 * @param string $folder     Subfolder (e.g. 'tracks', 'artists', 'albums', 'videos')
	 * @param string $slug       Entity slug for clean human-readable naming
	 * @return string Local URL (e.g. https://kontentainment.com/wp-content/uploads/charts/tracks/tamly-maak-a1b2c3d4.jpg)
	 */
	public static function sideload( $remote_url, $folder = 'tracks', $slug = '' ) {
		if ( empty( $remote_url ) || ! is_string( $remote_url ) ) {
			return $remote_url;
		}

		$remote_url = trim( $remote_url );

		// Skip placeholders or broken links
		if ( strpos( $remote_url, 'placeholder.png' ) !== false || $remote_url === 'Placeholder' || $remote_url === 'N/A' ) {
			return $remote_url;
		}

		// If already local to this site, return immediately
		if ( self::is_local_url( $remote_url ) ) {
			return $remote_url;
		}

		// In-memory cache
		if ( isset( self::$local_cache[ $remote_url ] ) ) {
			return self::$local_cache[ $remote_url ];
		}

		// Maximize resolution before downloading (1080p / 640x640 / 1200x1200bb)
		$clean_remote_url = class_exists( '\Charts\Core\ImageEnhancer' )
			? \Charts\Core\ImageEnhancer::maximize( $remote_url )
			: $remote_url;

		// Determine destination directory and URL
		$upload = wp_upload_dir();
		$sub = sanitize_key( $folder ?: 'tracks' );
		$target_dir = trailingslashit( $upload['basedir'] ) . 'charts/' . $sub . '/';
		$target_url = trailingslashit( $upload['baseurl'] ) . 'charts/' . $sub . '/';

		// Generate a deterministic filename based on URL hash + slug
		$ext = self::detect_extension( $clean_remote_url );
		$url_hash = substr( md5( $clean_remote_url ), 0, 10 );
		$clean_slug = sanitize_title( $slug );
		$filename = ( $clean_slug ? substr( $clean_slug, 0, 40 ) . '-' : '' ) . $url_hash . '.' . $ext;

		$target_file = $target_dir . $filename;
		$file_url    = $target_url . $filename;

		// If file already exists on disk and is non-empty, use it immediately
		if ( file_exists( $target_file ) && filesize( $target_file ) > 100 ) {
			self::$local_cache[ $remote_url ] = $file_url;
			return $file_url;
		}

		// Ensure directory exists
		if ( ! is_dir( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		// Download file
		$response = wp_safe_remote_get( $clean_remote_url, array(
			'timeout'     => 12,
			'redirection' => 5,
			'headers'     => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept'     => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $remote_url;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return $remote_url;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) || strlen( $body ) < 100 ) {
			return $remote_url;
		}

		// Validate content type
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! empty( $content_type ) && strpos( $content_type, 'image' ) === false && strpos( $content_type, 'octet-stream' ) === false ) {
			return $remote_url;
		}

		// Save file
		$saved = @file_put_contents( $target_file, $body );
		if ( ! $saved ) {
			return $remote_url;
		}

		self::$local_cache[ $remote_url ] = $file_url;
		return $file_url;
	}

	/**
	 * Check if file has already been downloaded to disk; if so, return its local URL.
	 */
	public static function get_local_if_exists( $remote_url, $folder = 'tracks', $slug = '' ) {
		if ( empty( $remote_url ) || self::is_local_url( $remote_url ) ) {
			return $remote_url;
		}

		$clean_remote_url = class_exists( '\Charts\Core\ImageEnhancer' )
			? \Charts\Core\ImageEnhancer::maximize( $remote_url )
			: $remote_url;

		$upload = wp_upload_dir();
		$sub = sanitize_key( $folder ?: 'tracks' );
		$target_dir = trailingslashit( $upload['basedir'] ) . 'charts/' . $sub . '/';
		$target_url = trailingslashit( $upload['baseurl'] ) . 'charts/' . $sub . '/';

		$ext = self::detect_extension( $clean_remote_url );
		$url_hash = substr( md5( $clean_remote_url ), 0, 10 );
		$clean_slug = sanitize_title( $slug );
		$filename = ( $clean_slug ? substr( $clean_slug, 0, 40 ) . '-' : '' ) . $url_hash . '.' . $ext;

		$target_file = $target_dir . $filename;
		if ( file_exists( $target_file ) && filesize( $target_file ) > 100 ) {
			return $target_url . $filename;
		}

		return null;
	}

	/**
	 * Check if a URL is already hosted locally on this domain.
	 */
	public static function is_local_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) return false;
		if ( strpos( $url, 'wp-content/uploads' ) !== false ) return true;

		$home = home_url();
		$site = site_url();
		if ( strpos( $url, $home ) === 0 || strpos( $url, $site ) === 0 ) return true;

		// Relative path
		if ( substr( $url, 0, 1 ) === '/' && substr( $url, 0, 2 ) !== '//' ) return true;

		return false;
	}

	/**
	 * Detect file extension from URL or fallback to jpg.
	 */
	private static function detect_extension( $url ) {
		$path = parse_url( $url, PHP_URL_PATH );
		if ( $path ) {
			$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ), true ) ) {
				return ( $ext === 'jpeg' ) ? 'jpg' : $ext;
			}
		}
		return 'jpg';
	}

	/**
	 * Batch process and download remote images for existing database records.
	 *
	 * @param int $limit Number of records to process per batch
	 * @return array Stats on processed, updated, and remaining items
	 */
	public static function sideload_batch( $limit = 30 ) {
		global $wpdb;

		$updated   = 0;
		$processed = 0;

		// 1. Process Tracks
		$tracks_table = $wpdb->prefix . 'charts_tracks';
		$tracks = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, title, slug, cover_image FROM $tracks_table
			 WHERE cover_image IS NOT NULL AND cover_image != ''
			   AND cover_image NOT LIKE %s AND cover_image NOT LIKE %s
			 LIMIT %d",
			'%wp-content/uploads%',
			'%' . parse_url( home_url(), PHP_URL_HOST ) . '%',
			$limit
		) );

		foreach ( $tracks as $t ) {
			$processed++;
			$local = self::sideload( $t->cover_image, 'tracks', $t->slug ?: $t->title );
			if ( $local && $local !== $t->cover_image && self::is_local_url( $local ) ) {
				$wpdb->update( $tracks_table, array( 'cover_image' => $local ), array( 'id' => $t->id ) );
				// Also update entries table
				$wpdb->update(
					$wpdb->prefix . 'charts_entries',
					array( 'cover_image' => $local ),
					array( 'item_id' => $t->id, 'item_type' => 'track' )
				);
				$updated++;
			}
		}

		// 2. Process Artists (if batch has room)
		$remaining_limit = $limit - $processed;
		if ( $remaining_limit > 0 ) {
			$artists_table = $wpdb->prefix . 'charts_artists';
			$artists = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, display_name, slug, image FROM $artists_table
				 WHERE image IS NOT NULL AND image != ''
				   AND image NOT LIKE %s AND image NOT LIKE %s
				 LIMIT %d",
				'%wp-content/uploads%',
				'%' . parse_url( home_url(), PHP_URL_HOST ) . '%',
				$remaining_limit
			) );

			foreach ( $artists as $a ) {
				$processed++;
				$local = self::sideload( $a->image, 'artists', $a->slug ?: $a->display_name );
				if ( $local && $local !== $a->image && self::is_local_url( $local ) ) {
					$wpdb->update( $artists_table, array( 'image' => $local ), array( 'id' => $a->id ) );
					$updated++;
				}
			}
		}

		// 3. Process Albums (if batch has room)
		$remaining_limit = $limit - $processed;
		if ( $remaining_limit > 0 ) {
			$albums_table = $wpdb->prefix . 'charts_albums';
			$albums = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, title, slug, cover_image FROM $albums_table
				 WHERE cover_image IS NOT NULL AND cover_image != ''
				   AND cover_image NOT LIKE %s AND cover_image NOT LIKE %s
				 LIMIT %d",
				'%wp-content/uploads%',
				'%' . parse_url( home_url(), PHP_URL_HOST ) . '%',
				$remaining_limit
			) );

			foreach ( $albums as $al ) {
				$processed++;
				$local = self::sideload( $al->cover_image, 'albums', $al->slug ?: $al->title );
				if ( $local && $local !== $al->cover_image && self::is_local_url( $local ) ) {
					$wpdb->update( $albums_table, array( 'cover_image' => $local ), array( 'id' => $al->id ) );
					$wpdb->update(
						$wpdb->prefix . 'charts_entries',
						array( 'cover_image' => $local ),
						array( 'item_id' => $al->id, 'item_type' => 'album' )
					);
					$updated++;
				}
			}
		}

		// 4. Count remaining remote images across all tables
		$host = '%' . parse_url( home_url(), PHP_URL_HOST ) . '%';
		$uploads = '%wp-content/uploads%';
		$remaining_tracks = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks WHERE cover_image IS NOT NULL AND cover_image != '' AND cover_image NOT LIKE %s AND cover_image NOT LIKE %s",
			$uploads, $host
		) );
		$remaining_artists = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists WHERE image IS NOT NULL AND image != '' AND image NOT LIKE %s AND image NOT LIKE %s",
			$uploads, $host
		) );
		$remaining_albums = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}charts_albums WHERE cover_image IS NOT NULL AND cover_image != '' AND cover_image NOT LIKE %s AND cover_image NOT LIKE %s",
			$uploads, $host
		) );
		$remaining = $remaining_tracks + $remaining_artists + $remaining_albums;

		return array(
			'processed' => $processed,
			'updated'   => $updated,
			'remaining' => $remaining,
		);
	}
}
