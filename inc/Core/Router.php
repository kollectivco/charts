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
		
		// Remove template_include override as we now handle this via template_redirect + exit
	}

	/**
	 * Add custom rewrite rules for the /charts endpoint.
	 */
	public static function add_rewrite_rules() {
		// 1. Single Chart (Catch-all for /charts/{slug})
		add_rewrite_rule( '^charts/([^/]+)/?$', 'index.php?charts_route=single-chart&charts_definition_slug=$matches[1]', 'top' );

		// 2. Artist Archive
		add_rewrite_rule( '^charts/artists/?$', 'index.php?charts_route=artist-archive', 'top' );

		// 3. Artist Single
		add_rewrite_rule( '^charts/artist/([^/]+)/?$', 'index.php?charts_route=artist-single&charts_artist_slug=$matches[1]', 'top' );

		// 4. Track Single
		add_rewrite_rule( '^charts/track/([^/]+)/?$', 'index.php?charts_route=item-single&charts_item_type=track&charts_item_slug=$matches[1]', 'top' );

		// 5. Video Single
		add_rewrite_rule( '^charts/video/([^/]+)/?$', 'index.php?charts_route=item-single&charts_item_type=video&charts_item_slug=$matches[1]', 'top' );

		// 6. Base /charts/
		add_rewrite_rule( '^charts/?$', 'index.php?charts_route=index', 'top' );
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
		return $vars;
	}
}
