<?php
namespace Charts\Core;

/**
 * Handle native theme integration and public UI refinements.
 */
class PublicIntegration {

	/**
	 * Initialize theme integration.
	 */
	public static function init() {
		// Add body classes for charts routes
		add_filter( 'body_class', array( self::class, 'add_body_classes' ) );

		// Inject design tokens into head
		add_action( 'wp_head', array( self::class, 'render_design_tokens' ), 100 );
	}

	/**
	 * Add specific body classes to aid styling within themes.
	 */
	public static function add_body_classes( $classes ) {
		if ( self::is_charts_page() ) {
			$classes[] = 'kc-charts-route';
		}
		return $classes;
	}

	// Sidebars removed

	/**
	 * Check if the current page should be handled by the plugin's public logic.
	 */
	public static function is_charts_page() {
		if ( ! is_main_query() ) return false;
		
		// If we are on a dashboard route, keep it isolated
		if ( get_query_var( 'charts_route' ) === 'dashboard' ) return false;

		$vars = array(
			'charts_route',
			'charts_module',
			'charts_page',
			'charts_platform',
			'charts_country',
			'charts_frequency',
			'charts_type',
			'charts_artist_slug',
			'charts_item_slug',
			'charts_item_type',
			'charts_definition_id',
			'charts_definition_slug'
		);

		foreach ( $vars as $v ) {
			if ( get_query_var( $v ) ) {
				return true;
			}
		}

		$path = trim( $_SERVER['REQUEST_URI'], '/' );
		if ( $path === 'charts' || strpos( $path, 'charts/' ) === 0 ) {
            return true;
        }

		return false;
	}

	public static function get_header() {
		get_header();
	}

	/**
	 * Inject dynamic CSS variables into the head.
	 */
	public static function render_design_tokens() {
		if ( ! self::is_charts_page() ) return;

		$mode = Settings::get('design_mode', 'light');
		
		$variables = [
			'--k-primary'        => Settings::get('color_primary'),
			'--k-secondary'      => Settings::get('color_secondary'),
		];

		// Mode-specific Surface/Text
		if ( $mode === 'dark' ) {
			$variables['--k-bg-override']      = Settings::get('color_bg_dark');
			$variables['--k-surface-override'] = Settings::get('color_surface_dark');
			$variables['--k-text-override']    = Settings::get('color_text_dark');
		} else {
			$variables['--k-bg-override']      = Settings::get('color_bg_light');
			$variables['--k-surface-override'] = Settings::get('color_surface_light');
			$variables['--k-text-override']    = Settings::get('color_text_light');
		}

		echo '<style id="kc-design-tokens">';
		
		// 1. Fixed Typography Definitions
		$font_path = CHARTS_URL . 'public/assets/fonts/';
		
		echo "
		@font-face {
			font-family: 'KChartsArabic';
			src: url('{$font_path}CircularSpotifyTxT-Bold.ttf') format('truetype');
			font-weight: 700;
			font-style: normal;
			font-display: swap;
		}
		@font-face {
			font-family: 'KChartsEnglish';
			src: url('{$font_path}spotify-mix/SpotifyMix-Regular.woff2') format('woff2'),
			     url('{$font_path}spotify-mix/SpotifyMix-Regular.woff') format('woff'),
			     url('{$font_path}spotify-mix/SpotifyMix-Regular.ttf') format('truetype');
			font-weight: 400;
			font-style: normal;
			font-display: swap;
		}
		@font-face {
			font-family: 'KChartsEnglish';
			src: url('{$font_path}spotify-mix/SpotifyMix-Medium.woff2') format('woff2'),
			     url('{$font_path}spotify-mix/SpotifyMix-Medium.woff') format('woff'),
			     url('{$font_path}spotify-mix/SpotifyMix-Medium.ttf') format('truetype');
			font-weight: 500;
			font-style: normal;
			font-display: swap;
		}
		@font-face {
			font-family: 'KChartsEnglish';
			src: url('{$font_path}spotify-mix/SpotifyMix-Bold.woff2') format('woff2'),
			     url('{$font_path}spotify-mix/SpotifyMix-Bold.woff') format('woff'),
			     url('{$font_path}spotify-mix/SpotifyMix-Bold.ttf') format('truetype');
			font-weight: 700;
			font-style: normal;
			font-display: swap;
		}
		@font-face {
			font-family: 'KChartsEnglish';
			src: url('{$font_path}spotify-mix/SpotifyMix-Black.woff2') format('woff2'),
			     url('{$font_path}spotify-mix/SpotifyMix-Black.woff') format('woff'),
			     url('{$font_path}spotify-mix/SpotifyMix-Black.ttf') format('truetype');
			font-weight: 900;
			font-style: normal;
			font-display: swap;
		}
		";

		echo ':root {';
		foreach ( $variables as $key => $val ) {
			if ( ! empty($val) ) {
				echo esc_html($key) . ': ' . esc_attr($val) . ';';
			}
		}
		// Fixed typography tokens
		echo '--k-f-en: "KChartsEnglish", system-ui, sans-serif;';
		echo '--k-f-ar: "KChartsArabic", "KChartsEnglish", sans-serif;';
		echo '}';
		
		echo '.kc-charts-route {';
		echo 'font-family: var(--k-f-en);';
		echo '--k-font-heading: var(--k-f-en);';
		echo '--k-font-body: var(--k-f-en);';
		echo '--k-font-meta: var(--k-f-en);';
		echo '}';
		
        // Arabic/RTL Context Override
        echo '.kc-charts-route[dir="rtl"], .rtl .kc-charts-route, .kc-charts-route .is-arabic {';
        echo 'font-family: var(--k-f-ar) !important;';
		echo '--k-font-heading: var(--k-f-ar) !important;';
        echo '--k-font-body: var(--k-f-ar) !important;';
		echo '--k-font-meta: var(--k-f-ar) !important;';
        echo '}';

		if ( $mode === 'system' ) {
			echo '@media (prefers-color-scheme: dark) {';
			echo ':root {';
			echo '--k-bg-override: ' . esc_attr(Settings::get('color_bg_dark')) . ';';
			echo '--k-surface-override: ' . esc_attr(Settings::get('color_surface_dark')) . ';';
			echo '--k-text-override: ' . esc_attr(Settings::get('color_text_dark')) . ';';
			echo '}';
			echo '}';
		}

		echo '</style>';
	}

	public static function get_footer() {
		// Use theme's native footer
		get_footer();
	}

	/**
	 * Centralized resolver for track/video/artist artwork.
	 * Priorities: Enriched Canonical > Entry-level Metadata > Source-specific Thumbs > Placeholder
	 */
	public static function resolve_artwork( $item, $type = 'track' ) {
		// 1. Check if we have an object with a direct resolved_image or cover_image
		if ( ! empty( $item->resolved_image ) ) return $item->resolved_image;
		
		// 2. Try canonical table data based on type
		if ( $type === 'track' && ! empty( $item->track_cover ) ) return $item->track_cover;
		if ( $type === 'video' && ! empty( $item->video_thumb ) ) return $item->video_thumb;
		if ( $type === 'artist' && ! empty( $item->artist_image ) ) return $item->artist_image;

		// 3. Fallback to entry stored image
		if ( ! empty( $item->cover_image ) ) return $item->cover_image;

		// 4. Default placeholder
		return CHARTS_URL . 'public/assets/img/placeholder.png';
	}
}
