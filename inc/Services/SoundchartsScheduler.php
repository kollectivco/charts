<?php

namespace Charts\Services;

/**
 * Schedules and executes automated Soundcharts syncs.
 */
class SoundchartsScheduler {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'charts_soundcharts_auto_sync', array( self::class, 'run_due_syncs' ) );
		add_filter( 'cron_schedules', array( self::class, 'register_schedule' ) );
	}

	/**
	 * Add a 15-minute schedule for control-room responsiveness.
	 */
	public static function register_schedule( $schedules ) {
		if ( ! isset( $schedules['charts_every_fifteen_minutes'] ) ) {
			$schedules['charts_every_fifteen_minutes'] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 15 Minutes', 'kontentainment-lists' ),
			);
		}

		return $schedules;
	}

	/**
	 * Ensure recurring event exists.
	 */
	public static function ensure_event() {
		if ( ! wp_next_scheduled( 'charts_soundcharts_auto_sync' ) ) {
			wp_schedule_event( time() + 300, 'charts_every_fifteen_minutes', 'charts_soundcharts_auto_sync' );
		}
	}

	/**
	 * Run all due chart syncs.
	 */
	public static function run_due_syncs() {
		global $wpdb;

		$definitions = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}charts_definitions
			 WHERE auto_sync_enabled = 1
			 AND next_sync_at IS NOT NULL
			 AND next_sync_at <= '" . esc_sql( current_time( 'mysql' ) ) . "'"
		);

		if ( empty( $definitions ) ) {
			return;
		}

		$importer = new SoundchartsImporter();

		foreach ( $definitions as $definition ) {
			$settings = ! empty( $definition->display_settings_json ) ? json_decode( $definition->display_settings_json, true ) : array();
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$meta = array(
				'chart_id'    => (int) $definition->id,
				'country'     => $definition->country_code,
				'chart_type'  => $definition->chart_type,
				'frequency'   => $definition->frequency,
				'period_date' => current_time( 'Y-m-d' ),
				'source_name' => $definition->title . ' Auto Sync',
				'dry_run'     => false,
			);

			if ( ! empty( $settings['soundcharts_preset'] ) ) {
				$meta['preset_key'] = $settings['soundcharts_preset'];
			}

			$importer->run( $meta );
			self::update_schedule_metadata( $definition, $settings );
		}
	}

	/**
	 * Recompute next run timestamp for a chart.
	 */
	public static function update_schedule_metadata( $definition, array $settings ) {
		global $wpdb;

		$day  = strtoupper( $settings['auto_sync_day'] ?? 'MONDAY' );
		$time = $settings['auto_sync_time'] ?? '08:00';
		$next = self::compute_next_sync_datetime( $day, $time );

		$wpdb->update(
			$wpdb->prefix . 'charts_definitions',
			array(
				'last_sync_at' => current_time( 'mysql' ),
				'next_sync_at' => $next,
			),
			array( 'id' => $definition->id )
		);
	}

	/**
	 * Compute the next weekly run in site local time.
	 */
	public static function compute_next_sync_datetime( $day, $time ) {
		$timestamp = strtotime( 'next ' . strtolower( $day ) . ' ' . $time, current_time( 'timestamp' ) );
		if ( ! $timestamp ) {
			$timestamp = current_time( 'timestamp' ) + WEEK_IN_SECONDS;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
	}
}
