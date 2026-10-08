<?php

namespace Charts\Services;

/** Build native slugs for entity and chart names (supports Arabic and English). */
class Slugger {

	public static function make( $value, $fallback = 'item' ) {
		$value = trim( (string) $value );
		$slug  = urldecode( sanitize_title( $value ) );
		return $slug !== '' ? $slug : urldecode( sanitize_title( $fallback ) );
	}

	public static function unique( $table, $value, $fallback = 'item' ) {
		global $wpdb;
		$base = self::make( $value, $fallback );
		$slug = $base;
		$suffix = 2;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s LIMIT 1", $slug ) ) ) {
			$slug = $base . '-' . $suffix++;
		}
		return $slug;
	}
}
