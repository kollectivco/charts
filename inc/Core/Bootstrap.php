<?php

namespace Charts\Core;

/**
 * Handle core plugin initialization.
 */
class Bootstrap {

	/**
	 * Initialize the core module.
	 */
	public static function init() {
		// Initialize Public Routing
		\Charts\Core\Router::init();

		// Initialize Standalone Layout system
		\Charts\Core\StandaloneLayout::init();

		// Force Standalone Render Intercept
		add_action( 'template_redirect', array( self::class, 'force_standalone_render' ), 1 );

		// Register any core hooks
		add_action( 'init', array( self::class, 'register_cron' ) );
	}

	/**
	 * Force Standalone Render for plugin routes.
	 * This exits before the theme can render its header/footer.
	 */
	public static function force_standalone_render() {
		$route = get_query_var( 'charts_route' );
		if ( ! $route ) return;

		// Clean the environment
		status_header( 200 );

		$template_path = '';
		switch ( $route ) {
			case 'index':
				$template_path = CHARTS_PATH . 'public/templates/index.php';
				break;
			case 'single':
				$template_path = CHARTS_PATH . 'public/templates/single-chart.php';
				break;
			case 'artist-archive':
				$template_path = CHARTS_PATH . 'public/templates/artist-archive.php';
				break;
			case 'artist-single':
				$template_path = CHARTS_PATH . 'public/templates/artist-single.php';
				break;
			case 'item-single':
				$template_path = CHARTS_PATH . 'public/templates/item-single.php';
				break;
		}

		if ( $template_path && file_exists( $template_path ) ) {
			// Ensure StandaloneLayout isolation pass has run
			\Charts\Core\StandaloneLayout::isolation_pass();
			\Charts\Core\StandaloneLayout::suppress_theme_hooks();
			
			include $template_path;
			exit;
		}
	}

	/**
	 * Register any required CRON jobs.
	 */
	public static function register_cron() {
		// Daily Imports
		if ( ! wp_next_scheduled( 'charts_daily_import' ) ) {
			wp_schedule_event( time(), 'daily', 'charts_daily_import' );
		}
		add_action( 'charts_daily_import', array( self::class, 'run_automated_imports' ) );
		
		// Weekly Imports
		if ( ! wp_next_scheduled( 'charts_weekly_import' ) ) {
			wp_schedule_event( time(), 'weekly', 'charts_weekly_import' );
		}
		add_action( 'charts_weekly_import', array( self::class, 'run_automated_imports' ) );
	}

	/**
	 * Run automated imports for active sources.
	 */
	public static function run_automated_imports() {
		global $wpdb;
		
		$sources_table = $wpdb->prefix . 'charts_sources';
		$active_sources = $wpdb->get_results( "SELECT id FROM $sources_table WHERE is_active = 1" );
		
		if ( empty( $active_sources ) ) {
			return;
		}

		$import_flow = new \Charts\Services\ImportFlow();

		foreach ( $active_sources as $source ) {
			// In a real production environment, we might want to space these out or use an async worker
			// For Phase 1, we run them sequentially
			$import_flow->run( $source->id );
		}
	}
}
