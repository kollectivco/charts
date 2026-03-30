<?php

namespace Charts\Core;

/**
 * Handle public routing and template loading.
 */
class Router {

	/**
	 * Initialize routing.
	 */
	public static function init() {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'add_query_vars' ) );
		
		// Priority 999 to ensure we override any other plugin or theme interference
		add_filter( 'template_include', array( self::class, 'load_template' ), 999 );
	}

	/**
	 * Add custom rewrite rules for the /charts endpoint.
	 */
	public static function add_rewrite_rules() {
		// 1. Generic Single Chart (lowest specificity, but still top)
		add_rewrite_rule( '^charts/([^/]+)/?$', 'index.php?charts_route=single-chart&charts_definition_slug=$matches[1]', 'top' );

		// 2. Specific Entity Routes (higher specificity, added last so they stay above generic)
		add_rewrite_rule( '^charts/artist/([^/]+)/?$', 'index.php?charts_route=artist-single&charts_artist_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^charts/track/([^/]+)/?$', 'index.php?charts_route=item-single&charts_item_type=track&charts_item_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^charts/video/([^/]+)/?$', 'index.php?charts_route=item-single&charts_item_type=video&charts_item_slug=$matches[1]', 'top' );

		// 3. Static Archive Routes
		add_rewrite_rule( '^charts/artists/?$', 'index.php?charts_route=artist-archive', 'top' );
		add_rewrite_rule( '^charts/tracks/?$', 'index.php?charts_route=track-archive', 'top' );

		// 4. Base /charts/
		add_rewrite_rule( '^charts/?$', 'index.php?charts_route=index', 'top' );

		// 5. External Dashboard
		add_rewrite_rule( '^charts-dashboard/([^/]+)/?$', 'index.php?charts_route=dashboard&charts_module=$matches[1]', 'top' );
		add_rewrite_rule( '^charts-dashboard/?$', 'index.php?charts_route=dashboard&charts_module=overview', 'top' );
	}

	/**
	 * Register custom query variables.
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'charts_route';
		$vars[] = 'charts_platform';
		$vars[] = 'charts_country';
		$vars[] = 'charts_frequency';
		$vars[] = 'charts_type';
		$vars[] = 'charts_definition_id';
		$vars[] = 'charts_definition_slug';
		$vars[] = 'charts_artist_slug';
		$vars[] = 'charts_item_slug';
		$vars[] = 'charts_item_type';
		$vars[] = 'charts_module';
		return $vars;
	}

	/**
	 * Load custom templates for charts.
	 */
	public static function load_template( $template ) {
		$route = get_query_var( 'charts_route' );

		if ( ! $route ) return $template;

		switch ( $route ) {
			case 'index':
				return CHARTS_PATH . 'public/templates/index.php';
			case 'single-chart':
				return CHARTS_PATH . 'public/templates/single-chart.php';
			case 'artist-archive':
				return CHARTS_PATH . 'public/templates/artist-archive.php';
			case 'track-archive':
				return CHARTS_PATH . 'public/templates/track-archive.php';
			case 'artist-single':
				return CHARTS_PATH . 'public/templates/artist-single.php';
			case 'item-single':
				return CHARTS_PATH . 'public/templates/item-single.php';
			case 'dashboard':
				return CHARTS_PATH . 'public/templates/dashboard.php';
		}

		return $template;
	}
}
