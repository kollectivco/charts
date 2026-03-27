<?php

namespace Charts\Core;

/**
 * Handle standalone layout overrides for Charts.
 */
class StandaloneLayout {

	/**
	 * Initialize the standalone layout system.
	 */
	public static function init() {
		// Register footer widget area
		add_action( 'widgets_init', array( self::class, 'register_sidebars' ) );
		
		// Dequeue theme styles/scripts on charts pages for isolation
		add_action( 'wp_enqueue_scripts', array( self::class, 'isolation_pass' ), 999 );
	}

	/**
	 * Dequeue everything except core and plugin assets on charts routes.
	 */
	public static function isolation_pass() {
		if ( ! self::is_charts_page() ) return;

		global $wp_styles, $wp_scripts;

		// 1. STYLE ISOLATION
		if ( $wp_styles instanceof \WP_Styles ) {
			// Assets we MUST keep for core functionality and plugin UI
			$keep_styles = array( 'charts-public', 'admin-bar', 'dashicons', 'wp-block-library' );

			foreach ( $wp_styles->queue as $handle ) {
				if ( in_array( $handle, $keep_styles ) ) continue;

				$src = $wp_styles->registered[$handle]->src ?? '';
				// Strict but safe: Only dequeue if it clearly belongs to a theme
				if ( stripos( $src, '/themes/' ) !== false ) {
					wp_dequeue_style( $handle );
				}
			}
		}

		// 2. SCRIPT ISOLATION
		if ( $wp_scripts instanceof \WP_Scripts ) {
			// Keep core jquery and our own assets
			$keep_scripts = array( 'jquery', 'jquery-core', 'jquery-migrate', 'admin-bar' );
			
			foreach ( $wp_scripts->queue as $handle ) {
				if ( in_array( $handle, $keep_scripts ) ) continue;

				$src = $wp_scripts->registered[$handle]->src ?? '';
				// Only dequeue scripts inside the themes folder
				if ( stripos( $src, '/themes/' ) !== false ) {
					wp_dequeue_script( $handle );
				}
			}
		}
	}

	/**
	 * Register the Charts Footer Widget Area.
	 */
	public static function register_sidebars() {
		register_sidebar( array(
			'name'          => __( 'Charts Footer Widget Area', 'charts' ),
			'id'            => 'charts-footer-widgets',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		) );
	}

	/**
	 * Check if the current page should use the standalone layout.
	 */
	public static function is_charts_page() {
		if ( ! is_main_query() ) return false;
		
		$vars = array(
			'charts_page',
			'charts_platform',
			'charts_country',
			'charts_frequency',
			'charts_type',
			'charts_artist_slug',
			'charts_item_slug',
			'charts_item_type'
		);

		foreach ( $vars as $v ) {
			if ( get_query_var( $v ) ) {
				return true;
			}
		}

		// Also check the root /charts if it's hit manually or via path
		$path = trim( $_SERVER['REQUEST_URI'], '/' );
		if ( $path === 'charts' || strpos( $path, 'charts/' ) === 0 ) {
            return true;
        }

		return false;
	}

	public static function get_header() {
		include CHARTS_PATH . 'public/templates/parts/header.php';
	}

	public static function get_footer() {
		include CHARTS_PATH . 'public/templates/parts/footer.php';
	}
}
