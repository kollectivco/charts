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
		// Use theme's native header
		get_header();
		
		// Injected CSS variables for charts content
		$variables = [
			'--k-primary'      => Settings::get('color_primary'),
			'--k-font-heading' => Settings::get('font_heading'),
			'--k-font-body'    => Settings::get('font_body'),
		];

		echo '<style id="kc-design-tokens">';
		echo ':root {';
		foreach ( $variables as $key => $val ) {
			if ( ! empty($val) ) {
				echo esc_html($key) . ': ' . esc_attr($val) . ';';
			}
		}
		echo '}';
		echo '</style>';
	}

	public static function get_footer() {
		// Use theme's native footer
		get_footer();
	}
}
