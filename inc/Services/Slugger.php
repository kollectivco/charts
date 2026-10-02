<?php

namespace Charts\Services;

/** Build readable, ASCII-only English slugs for entity and chart names. */
class Slugger {

	public static function make( $value, $fallback = 'item' ) {
		$value = trim( (string) $value );
		if ( function_exists( 'transliterator_transliterate' ) ) {
			$latin = transliterator_transliterate( 'Any-Latin; Latin-ASCII', $value );
			if ( is_string( $latin ) && $latin !== '' ) $value = $latin;
		} else {
			$value = self::transliterate_arabic( $value );
		}
		$slug = sanitize_title( remove_accents( $value ) );
		return $slug !== '' ? $slug : sanitize_title( $fallback );
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

	private static function transliterate_arabic( $value ) {
		$value = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value );
		return strtr( $value, array(
			'ا'=>'a','أ'=>'a','إ'=>'e','آ'=>'a','ء'=>'a','ؤ'=>'o','ئ'=>'y',
			'ب'=>'b','ت'=>'t','ث'=>'th','ج'=>'j','ح'=>'h','خ'=>'kh','د'=>'d','ذ'=>'dh',
			'ر'=>'r','ز'=>'z','س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'d','ط'=>'t','ظ'=>'z',
			'ع'=>'a','غ'=>'gh','ف'=>'f','ق'=>'q','ك'=>'k','ل'=>'l','م'=>'m','ن'=>'n',
			'ه'=>'h','ة'=>'a','و'=>'w','ى'=>'a','ي'=>'y','پ'=>'p','چ'=>'ch','ژ'=>'zh','گ'=>'g',
		) );
	}
}
