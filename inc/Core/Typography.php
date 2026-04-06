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
        $font_path = CHARTS_URL . 'public/assets/fonts/';
        
        $css = "
/* --- KCHARTS TYPOGRAPHY SYSTEM --- */

/* Arabic: Noto Sans Arabic */
@font-face {
    font-family: 'KChartsArabic';
    src: url('{$font_path}noto-sans-arabic/Regular.woff2') format('woff2'),
         url('{$font_path}noto-sans-arabic/Regular.woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsArabic';
    src: url('{$font_path}noto-sans-arabic/Medium.woff2') format('woff2'),
         url('{$font_path}noto-sans-arabic/Medium.woff') format('woff');
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsArabic';
    src: url('{$font_path}noto-sans-arabic/SemiBold.woff2') format('woff2'),
         url('{$font_path}noto-sans-arabic/SemiBold.woff') format('woff');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsArabic';
    src: url('{$font_path}noto-sans-arabic/Bold.woff2') format('woff2'),
         url('{$font_path}noto-sans-arabic/Bold.woff') format('woff');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsArabic';
    src: url('{$font_path}noto-sans-arabic/Black.woff2') format('woff2'),
         url('{$font_path}noto-sans-arabic/Black.woff') format('woff');
    font-weight: 900;
    font-style: normal;
    font-display: swap;
}

/* English: Spotify Mix */
@font-face {
    font-family: 'KChartsEnglish';
    src: url('{$font_path}spotify-mix/SpotifyMix-Regular.woff2') format('woff2'),
         url('{$font_path}spotify-mix/SpotifyMix-Regular.woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsEnglish';
    src: url('{$font_path}spotify-mix/SpotifyMix-Medium.woff2') format('woff2'),
         url('{$font_path}spotify-mix/SpotifyMix-Medium.woff') format('woff');
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsEnglish';
    src: url('{$font_path}spotify-mix/SpotifyMix-Bold.woff2') format('woff2'),
         url('{$font_path}spotify-mix/SpotifyMix-Bold.woff') format('woff');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KChartsEnglish';
    src: url('{$font_path}spotify-mix/SpotifyMix-Black.woff2') format('woff2'),
         url('{$font_path}spotify-mix/SpotifyMix-Black.woff') format('woff');
    font-weight: 900;
    font-style: normal;
    font-display: swap;
}

:root {
    --k-font-ar: 'KChartsArabic', sans-serif;
    --k-font-en: 'KChartsEnglish', system-ui, -apple-system, sans-serif;
}

.k-font-ar { font-family: var(--k-font-ar) !important; }
.k-font-en { font-family: var(--k-font-en) !important; }
";
        return $css;
    }
}
