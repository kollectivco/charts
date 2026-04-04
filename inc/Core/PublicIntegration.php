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
			'--k-font-heading'   => Settings::get('font_heading'),
			'--k-font-body'      => Settings::get('font_body'),
			'--k-font-meta'      => Settings::get('font_meta'),
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
		echo ':root {';
		foreach ( $variables as $key => $val ) {
			if ( ! empty($val) ) {
				echo esc_html($key) . ': ' . esc_attr($val) . ';';
			}
		}
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
