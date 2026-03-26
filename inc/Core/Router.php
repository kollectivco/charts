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
		// 1. Base /charts/
		add_rewrite_rule( '^charts/?$', 'index.php?charts_page=index', 'top' );
		
		// 2. Canonical /charts/{platform}/{country}/{frequency}/{type}
		add_rewrite_rule( 
			'^charts/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$', 
			'index.php?charts_platform=$matches[1]&charts_country=$matches[2]&charts_frequency=$matches[3]&charts_type=$matches[4]', 
			'top' 
		);
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
		return $vars;
	}

	/**
	 * Load custom templates for charts.
	 */
	public static function load_template( $template ) {
		$charts_page = get_query_var( 'charts_page' );
		$platform    = get_query_var( 'charts_platform' );

		if ( $charts_page === 'index' ) {
			$new_template = CHARTS_PATH . 'public/templates/index.php';
			if ( file_exists( $new_template ) ) {
				return $new_template;
			}
		}

		if ( $platform ) {
			$new_template = CHARTS_PATH . 'public/templates/single-chart.php';
			if ( file_exists( $new_template ) ) {
				return $new_template;
			}
		}

		return $template;
	}
}
