<?php

namespace Charts\Services;

/**
 * Central accessors for Soundcharts configuration.
 */
class SoundchartsSettings {

	const OPTION_APP_ID          = 'charts_soundcharts_app_id';
	const OPTION_API_KEY         = 'charts_soundcharts_api_key';
	const OPTION_MODE            = 'charts_soundcharts_mode';
	const OPTION_TIMEOUT         = 'charts_soundcharts_timeout';
	const OPTION_ENABLE_LOGGING  = 'charts_soundcharts_enable_logging';
	const OPTION_ENDPOINTS       = 'charts_soundcharts_endpoints';

	/**
	 * Return normalized settings.
	 */
	public static function get() {
		return array(
			'app_id'         => trim( (string) get_option( self::OPTION_APP_ID, '' ) ),
			'api_key'        => trim( (string) get_option( self::OPTION_API_KEY, '' ) ),
			'mode'           => self::get_mode(),
			'timeout'        => max( 5, (int) get_option( self::OPTION_TIMEOUT, 20 ) ),
			'enable_logging' => (bool) get_option( self::OPTION_ENABLE_LOGGING, 1 ),
			'endpoints'      => self::get_endpoint_map(),
		);
	}

	/**
	 * Return current API mode.
	 */
	public static function get_mode() {
		$mode = strtolower( (string) get_option( self::OPTION_MODE, 'production' ) );
		return in_array( $mode, array( 'sandbox', 'production' ), true ) ? $mode : 'production';
	}

	/**
	 * Base URL for REST calls.
	 */
	public static function get_base_url() {
		// Soundcharts uses the same customer base host; mode is preserved for auth/preset labeling.
		return 'https://customer.api.soundcharts.com';
	}

	/**
	 * Connection is configured only when both secrets exist.
	 */
	public static function is_configured() {
		$settings = self::get();
		return $settings['app_id'] !== '' && $settings['api_key'] !== '';
	}

	/**
	 * UI-safe masked key.
	 */
	public static function get_masked_api_key() {
		$key = trim( (string) get_option( self::OPTION_API_KEY, '' ) );
		if ( $key === '' ) {
			return '';
		}

		$len = strlen( $key );
		if ( $len <= 6 ) {
			return str_repeat( '*', $len );
		}

		return substr( $key, 0, 3 ) . str_repeat( '*', max( 4, $len - 6 ) ) . substr( $key, -3 );
	}

	/**
	 * Persist endpoint presets used by Import Center mappings.
	 */
	public static function get_endpoint_map() {
		$defaults = array(
			'top-songs'   => '/api/v2/charts/track',
			'top-artists' => '/api/v2/charts/artist',
			'top-videos'  => '/api/v2/charts/video',
			'viral'       => '/api/v2/charts/track',
			'connection_probe' => '/api/v2/me',
		);

		$saved = get_option( self::OPTION_ENDPOINTS, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return array_merge( $defaults, $saved );
	}

	/**
	 * Sanitize settings payload from admin forms.
	 */
	public static function sanitize_posted_settings( array $posted ) {
		$current_api_key = (string) get_option( self::OPTION_API_KEY, '' );
		$incoming_key    = isset( $posted['soundcharts_api_key'] ) ? trim( (string) wp_unslash( $posted['soundcharts_api_key'] ) ) : '';

		if ( $incoming_key === '' || $incoming_key === self::get_masked_api_key() ) {
			$incoming_key = $current_api_key;
		}

		return array(
			self::OPTION_APP_ID         => sanitize_text_field( $posted['soundcharts_app_id'] ?? '' ),
			self::OPTION_API_KEY        => sanitize_text_field( $incoming_key ),
			self::OPTION_MODE           => sanitize_text_field( $posted['soundcharts_mode'] ?? 'production' ),
			self::OPTION_TIMEOUT        => max( 5, (int) ( $posted['soundcharts_timeout'] ?? 20 ) ),
			self::OPTION_ENABLE_LOGGING => isset( $posted['soundcharts_enable_logging'] ) ? 1 : 0,
		);
	}
}
