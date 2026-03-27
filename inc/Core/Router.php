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
		add_filter( 'template_include', array( self::class, 'load_template' ) );
	}

	/**
	 * Add custom rewrite rules for the /charts endpoint.
	 */
	public static function add_rewrite_rules() {
		// 1. Artist Archive
		add_rewrite_rule( '^charts/artists/?$', 'index.php?charts_page=artist-archive', 'top' );

		// 2. Artist Single
		add_rewrite_rule( '^charts/artist/([^/]+)/?$', 'index.php?charts_page=artist-single&charts_artist_slug=$matches[1]', 'top' );

		// 3. Track Single
		add_rewrite_rule( '^charts/track/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=track&charts_item_slug=$matches[1]', 'top' );

		// 4. Video Single
		add_rewrite_rule( '^charts/video/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=video&charts_item_slug=$matches[1]', 'top' );

		// 5. Dynamic Chart Definitions (e.g., /charts/top-songs)
		$manager = new \Charts\Admin\SourceManager();
		$definitions = $manager->get_definitions( true );
		foreach ( $definitions as $def ) {
			add_rewrite_rule( 
				'^charts/' . preg_quote($def->slug) . '/?$', 
				'index.php?charts_page=single-chart&charts_definition_id=' . $def->id, 
				'top' 
			);
		}

		// 6. Base /charts/
		add_rewrite_rule( '^charts/?$', 'index.php?charts_page=index', 'top' );
	}

	/**
	 * Register custom query variables.
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'charts_page';
		$vars[] = 'charts_platform';
		$vars[] = 'charts_country';
		$vars[] = 'charts_frequency';
		$vars[] = 'charts_type';
		$vars[] = 'charts_definition_id';
		$vars[] = 'charts_artist_slug';
		$vars[] = 'charts_item_slug';
		$vars[] = 'charts_item_type';
		return $vars;
	}

	/**
	 * Load custom templates for charts.
	 */
	public static function load_template( $template ) {
		$charts_page = get_query_var( 'charts_page' );
		$platform    = get_query_var( 'charts_platform' );
		$type        = get_query_var( 'charts_type' );

		if ( $charts_page === 'index' ) {
			return CHARTS_PATH . 'public/templates/index.php';
		}

		if ( $charts_page === 'artist-archive' ) {
			return CHARTS_PATH . 'public/templates/artist-archive.php';
		}

		if ( $charts_page === 'artist-single' ) {
			return CHARTS_PATH . 'public/templates/artist-single.php';
		}

		if ( $charts_page === 'item-single' ) {
			return CHARTS_PATH . 'public/templates/item-single.php';
		}

		if ( $charts_page === 'single-chart' ) {
			return CHARTS_PATH . 'public/templates/single-chart.php';
		}

		// Support both long and short chart URLs
		if ( $platform || $type ) {
			return CHARTS_PATH . 'public/templates/single-chart.php';
		}

		return $template;
	}
}
