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
		// Only load if on a charts route/page
		$is_charts = get_query_var( 'charts_route' ) || get_query_var( 'charts_page' ) || get_query_var( 'charts_platform' ) || is_singular(['artist', 'chart', 'track', 'video']);
		if ( ! $is_charts ) return;

		$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);

		if ( $is_mobile ) {
			// [MODE: MOBILE/APP]
			wp_enqueue_style( 'charts-mobile', CHARTS_URL . 'public/assets/css/mobile.css', array(), CHARTS_VERSION );
		} else {
			// [MODE: DESKTOP/WEB]
			wp_enqueue_style( 'charts-public', CHARTS_URL . 'public/assets/css/public.css', array(), CHARTS_VERSION );
		}

		// Shared JS Logic
		wp_enqueue_script( 'charts-public', CHARTS_URL . 'public/assets/js/public.js', array('jquery'), CHARTS_VERSION, true );
	}
}
