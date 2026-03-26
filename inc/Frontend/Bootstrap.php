<?php

namespace Charts\Frontend;

/**
 * Handle frontend initialization.
 */
class Bootstrap {

	/**
	 * Initialize the frontend module.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets() {
		// Only load if on a charts page
		if ( get_query_var( 'charts_page' ) || get_query_var( 'charts_platform' ) ) {
			wp_enqueue_style( 'charts-public', CHARTS_URL . 'public/assets/css/public.css', array(), CHARTS_VERSION );
		}
	}
}
