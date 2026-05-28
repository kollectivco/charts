<?php
namespace Charts\Core;

/**
 * Typography - Intelligent Language-Aware Typography System
 */
class Typography {

	/**
	 * Detect if a string contains Arabic characters.
	 */
	public static function is_arabic( $string ) {
		return preg_match( '/[\x{0600}-\x{06FF}]/u', $string );
	}

	/**
	 * Get CSS class based on text content language.
	 */
	public static function get_font_class( $string ) {
		return self::is_arabic( $string ) ? 'k-font-ar' : 'k-font-en';
	}

	/**
	 * Wrap text in a span with the appropriate font class.
	 */
	public static function apply( $string, $extra_classes = '' ) {
		if ( empty( $string ) ) return '';
		$class = self::get_font_class( $string );
		if ( ! empty( $extra_classes ) ) {
			$class .= ' ' . $extra_classes;
		}
		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $string ) );
	}

    /**
     * Utility to output the font-face declarations to public.css via filter or direct append.
     */
    public static function get_font_face_css() {
        $css = "
/* --- KCHARTS TYPOGRAPHY SYSTEM --- */
:root {
    --k-font-ar: inherit;
    --k-font-en: inherit;
}

.k-font-ar { font-family: var(--k-font-ar) !important; }
.k-font-en { font-family: var(--k-font-en) !important; }
";
        return $css;
    }
}
