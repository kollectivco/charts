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
		add_action( 'template_redirect', array( self::class, 'redirect_legacy_routes' ) );
		add_action( 'template_redirect', array( self::class, 'maybe_render_share_card' ) );
	}

	/**
	 * Add custom rewrite rules for the /lists endpoint.
	 */
	public static function add_rewrite_rules() {
		// 1. Artist Archive
		add_rewrite_rule( '^lists/trending/?$', 'index.php?charts_page=trending', 'top' );
		add_rewrite_rule( '^charts/trending/?$', 'index.php?charts_page=trending', 'top' );
		add_rewrite_rule( '^lists/artists/?$', 'index.php?charts_page=artist-archive', 'top' );
		add_rewrite_rule( '^charts/artists/?$', 'index.php?charts_page=artist-archive', 'top' );

		// Share cards
		add_rewrite_rule( '^lists/share/([^/]+)/([^/]+)/?$', 'index.php?charts_page=share-card&charts_share_type=$matches[1]&charts_share_slug=$matches[2]', 'top' );
		add_rewrite_rule( '^charts/share/([^/]+)/([^/]+)/?$', 'index.php?charts_page=share-card&charts_share_type=$matches[1]&charts_share_slug=$matches[2]', 'top' );

		// 2. Artist Single
		add_rewrite_rule( '^lists/artist/([^/]+)/?$', 'index.php?charts_page=artist-single&charts_artist_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^charts/artist/([^/]+)/?$', 'index.php?charts_page=artist-single&charts_artist_slug=$matches[1]', 'top' );

		// 3. Track Single
		add_rewrite_rule( '^lists/track/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=track&charts_item_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^charts/track/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=track&charts_item_slug=$matches[1]', 'top' );

		// 4. Video Single
		add_rewrite_rule( '^lists/video/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=video&charts_item_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^charts/video/([^/]+)/?$', 'index.php?charts_page=item-single&charts_item_type=video&charts_item_slug=$matches[1]', 'top' );

		// 5. Dynamic List Definitions (e.g., /lists/top-songs)
		$manager = new \Charts\Admin\SourceManager();
		$definitions = $manager->get_definitions( true );
		foreach ( $definitions as $def ) {
			add_rewrite_rule(
				'^lists/' . preg_quote($def->slug) . '/?$',
				'index.php?charts_page=single-chart&charts_definition_id=' . $def->id,
				'top'
			);
			add_rewrite_rule( 
				'^charts/' . preg_quote($def->slug) . '/?$', 
				'index.php?charts_page=single-chart&charts_definition_id=' . $def->id, 
				'top' 
			);
		}

		// 5b. Legacy long-form list routes used by import/log CTA links.
		add_rewrite_rule( '^lists/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?charts_page=single-chart&charts_platform=$matches[1]&charts_country=$matches[2]&charts_frequency=$matches[3]&charts_type=$matches[4]', 'top' );
		add_rewrite_rule( '^charts/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?charts_page=single-chart&charts_platform=$matches[1]&charts_country=$matches[2]&charts_frequency=$matches[3]&charts_type=$matches[4]', 'top' );

		// 6. Base /lists/
		add_rewrite_rule( '^lists/?$', 'index.php?charts_page=index', 'top' );
		add_rewrite_rule( '^charts/?$', 'index.php?charts_page=index', 'top' );
		add_rewrite_rule( '^lists-dashboard/?$', 'index.php?charts_page=index', 'top' );
		add_rewrite_rule( '^charts-dashboard/?$', 'index.php?charts_page=index', 'top' );
		add_rewrite_rule( '^music-lists/?$', 'index.php?charts_page=index', 'top' );
		add_rewrite_rule( '^music-charts/?$', 'index.php?charts_page=index', 'top' );
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
		$vars[] = 'charts_share_type';
		$vars[] = 'charts_share_slug';
		return $vars;
	}

	/**
	 * Load custom templates for lists.
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

		if ( $charts_page === 'trending' ) {
			return CHARTS_PATH . 'public/templates/trending.php';
		}

		// Support both long and short chart URLs
		if ( $platform || $type ) {
			return CHARTS_PATH . 'public/templates/single-chart.php';
		}

		return $template;
	}

	public static function maybe_render_share_card() {
		if ( get_query_var( 'charts_page' ) !== 'share-card' ) {
			return;
		}

		( new \Charts\Services\EditorialService() )->render_share_card(
			get_query_var( 'charts_share_type' ),
			get_query_var( 'charts_share_slug' )
		);
	}

	/**
	 * Redirect legacy public chart routes to list equivalents.
	 */
	public static function redirect_legacy_routes() {
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( $request_uri === '' ) {
			return;
		}

		$path = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return;
		}

		$target = '';
		if ( strpos( $path, '/charts/' ) === 0 ) {
			$target = '/lists/' . ltrim( substr( $path, strlen( '/charts/' ) ), '/' );
		} elseif ( $path === '/charts' ) {
			$target = '/lists';
		} elseif ( strpos( $path, '/charts-dashboard' ) === 0 ) {
			$target = '/lists-dashboard' . substr( $path, strlen( '/charts-dashboard' ) );
		} elseif ( strpos( $path, '/music-charts' ) === 0 ) {
			$target = '/music-lists' . substr( $path, strlen( '/music-charts' ) );
		}

		if ( $target && $target !== $path ) {
			$query = wp_parse_url( $request_uri, PHP_URL_QUERY );
			$redirect_url = home_url( $target . ( $query ? '?' . $query : '' ) );
			wp_redirect( $redirect_url, 301 );
			exit;
		}
	}

	/**
	 * Canonical public lists base path.
	 */
	public static function get_public_base() {
		return '/lists';
	}
}
