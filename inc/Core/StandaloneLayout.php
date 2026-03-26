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
			'charts_platform',
			'charts_country',
			'charts_frequency',
			'charts_type',
			'charts_artist_slug'
		);

		foreach ( $vars as $v ) {
			if ( get_query_var( $v ) ) {
				return true;
			}
		}

		// Also check the root /charts if it's hit
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/charts/' ) || $_SERVER['REQUEST_URI'] === '/charts' ) {
            return true;
        }

		return false;
	}

	/**
	 * Load the custom Charts header.
	 */
	public static function get_header() {
		include CHARTS_PATH . 'public/templates/parts/header.php';
	}

	/**
	 * Load the custom Charts footer.
	 */
	public static function get_footer() {
		include CHARTS_PATH . 'public/templates/parts/footer.php';
	}
}
