<?php
namespace Charts\Core;

/**
 * ImageEnhancer
 * Centralized HD/Ultra-Resolution Image Enhancer for Charts Intelligence.
 * Automatically upscales and upgrades compressed or thumbnail URLs from:
 * - Spotify (b273 640x640 HD)
 * - YouTube (maxresdefault.jpg 1080p / sddefault.jpg 640p)
 * - Apple Music / iTunes (1200x1200bb)
 * - Deezer (1000x1000-000000-80-0-0)
 * - Soundcharts & CDN providers
 * - Billboard Arabia API Storage
 * - WordPress Media Library (full original uncropped images)
 */
class ImageEnhancer {

	/**
	 * Force maximum quality images by removing thumbnail sizes and requesting highest CDN resolution.
	 *
	 * @param string $url Original image URL.
	 * @return string High-definition upgraded image URL.
	 */
	public static function maximize( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		if ( strpos( $url, 'placeholder.png' ) !== false || $url === 'Placeholder' || $url === 'N/A' ) {
			return $url;
		}

		// 1. YouTube Thumbnails (Upgrade hqdefault, mqdefault, default, sddefault -> maxresdefault)
		if ( strpos( $url, 'ytimg.com' ) !== false || strpos( $url, 'youtube.com' ) !== false ) {
			// Upgrade standard thumbnail resolutions to maxresdefault (1280x720 / 1080p)
			$url = preg_replace( '#/(default|mqdefault|hqdefault|sddefault)\.jpg#i', '/maxresdefault.jpg', $url );
		}

		// 2. Spotify CDN (Replace small 64px / 300px hashes with large 640px HD 'b273')
		elseif ( strpos( $url, 'i.scdn.co' ) !== false || strpos( $url, 'spotifycdn.com' ) !== false ) {
			// Spotify image IDs start with ab67616d0000b273 (large 640x640), 1e02 (300x300), 4851 (64x64)
			$url = str_replace( array( '1e02', '4851' ), 'b273', $url );
		}

		// 3. Apple Music / iTunes CDN (Force 1200x1200bb or 1000x1000)
		elseif ( strpos( $url, 'mzstatic.com' ) !== false ) {
			// Matches patterns like 100x100bb.jpg, 300x300bb.webp, 600x600bf-60.jpg
			$url = preg_replace( '/[0-9]+x[0-9]+([a-zA-Z\-_0-9]*)\.(jpg|jpeg|png|webp)/i', '1200x1200$1.$2', $url );
		}

		// 4. Deezer CDN (Upgrade 250x250, 500x500 to 1000x1000)
		elseif ( strpos( $url, 'dzcdn.net' ) !== false || strpos( $url, 'deezer.com' ) !== false ) {
			$url = preg_replace( '#/[0-9]+x[0-9]+(-000000-[0-9]+-[0-9]+-[0-9]+)\.(jpg|png|webp)#i', '/1000x1000$1.$2', $url );
		}

		// 5. Google / YouTube User Avatars / Googleusercontent
		elseif ( strpos( $url, 'googleusercontent.com' ) !== false ) {
			// Patterns like =s88-c-k-c0x00ffffff-no-rj -> =s1000-c-k-c0x00ffffff-no-rj
			$url = preg_replace( '/=s[0-9]+(-c)?/i', '=s1200$1', $url );
			// Or /w80-h80/ -> /w1000-h1000/
			$url = preg_replace( '/\/w[0-9]+-h[0-9]+(-[a-z0-9]+)?\//i', '/w1200-h1200$1/', $url );
		}

		// 6. WordPress Media Library (Strip generated thumbnail dimensions e.g. -150x150, -300x300, -768x768)
		elseif ( strpos( $url, 'wp-content/uploads' ) !== false ) {
			$url = preg_replace( '/-[0-9]{2,4}x[0-9]{2,4}\.(jpg|jpeg|png|webp)$/i', '.$1', $url );
		}

		return $url;
	}
}
