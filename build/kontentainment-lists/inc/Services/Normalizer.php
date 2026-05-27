<?php

namespace Charts\Services;

/**
 * Handle data normalization and cleaning.
 */
class Normalizer {

	/**
	 * Normalize a title (track, album, video).
	 */
	public static function normalize_title( $title ) {
		// 1. Trim whitespace
		$normalized = trim( $title );

		// 2. Normalize Arabic/English mixed spacing and punctuation
		$normalized = self::clean_mixed_text( $normalized );

		// 3. Remove common suffixes like (Official Video), [Official Audio], etc.
		$patterns = array(
			'/\(official (video|audio|music video|lyric video|visualizer)\)/i',
			'/\[official (video|audio|music video|lyric video|visualizer)\]/i',
			'/\(lyrics\)/i',
			'/\[lyrics\]/i',
			'/\((ft\.|feat\.|featuring)(.*?)\)/i',
			'/\[(ft\.|feat\.|featuring)(.*?)\]/i',
			'/- official (video|audio)/i',
		);
		$normalized = preg_replace( $patterns, '', $normalized );

		// 4. Handle feat/ft/featuring variations in the main string
		$normalized = preg_replace( '/\s+(ft\.|feat\.|featuring)\s+/i', ' ', $normalized );

		// 5. Unify separators and punctuation
		$normalized = preg_replace( '/[\s\-_\|\.]+/', ' ', $normalized );
		
		// 6. Convert to lowercase
		$normalized = mb_strtolower( trim( $normalized ), 'UTF-8' );

		return $normalized;
	}

	/**
	 * Normalize an artist name.
	 */
	public static function normalize_artist( $name ) {
		// Clean the artist name similarly to title but with focus on name parts
		$normalized = trim( $name );
		$normalized = preg_replace( '/\s+(ft\.|feat\.|featuring)\s+/i', ' ', $normalized );
		$normalized = preg_replace( '/[\s\-_\|\.]+/', ' ', $normalized );
		$normalized = mb_strtolower( trim( $normalized ), 'UTF-8' );

		return $normalized;
	}

	/**
	 * Clean mixed Arabic/English text.
	 */
	private static function clean_mixed_text( $text ) {
		// Remove zero-width spaces or special characters that often appear in scraped data
		$text = str_replace( array( "\xe2\x80\x8b", "\xe2\x80\x8c", "\xe2\x80\x8d" ), '', $text );
		return $text;
	}
}
