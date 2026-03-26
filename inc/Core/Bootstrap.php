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

		// Register any core hooks
		add_action( 'init', array( self::class, 'register_cron' ) );
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
