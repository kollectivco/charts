<?php

namespace Charts\Core;

/**
 * Global Flash-Notification & Result System.
 * Manages cross-redirect feedback messages (Toasts/Notices) using transient storage.
 */
class Notify {

	/**
	 * Flash a notification to the user.
	 * 
	 * @param string $type    success, error, warning, info
	 * @param string $message The primary message content
	 * @param string $title   Optional headline for use in Toasts
	 */
	public static function flash( $type, $message, $title = '' ) {
		$msgs = self::get_all();
		$msgs[] = [
			'type'    => $type,
			'message' => $message,
			'title'   => $title,
			'time'    => current_time( 'timestamp' )
		];
		self::save( $msgs );
	}

	/**
	 * Helper for SUCCESS.
	 */
	public static function success( $message, $title = 'Action Successful' ) {
		self::flash( 'success', $message, $title );
	}

	/**
	 * Helper for ERROR.
	 */
	public static function error( $message, $title = 'Sync Failure' ) {
		self::flash( 'error', $message, $title );
	}

	/**
	 * Helper for WARNING.
	 */
	public static function warning( $message, $title = 'Partial Results' ) {
		self::flash( 'warning', $message, $title );
	}

	/**
	 * Helper for INFO.
	 */
	public static function info( $message, $title = 'Information' ) {
		self::flash( 'info', $message, $title );
	}

	/**
	 * Retrieve all active flash messages and clear them.
	 */
	public static function get_and_clear() {
		$msgs = self::get_all();
		self::clear();
		return $msgs;
	}

	/**
	 * Internal: Get raw message array.
	 */
	private static function get_all() {
		$key = self::get_key();
		$msgs = get_transient( $key );
		return is_array( $msgs ) ? $msgs : [];
	}

	/**
	 * Internal: Save message array to persistent storage.
	 */
	private static function save( $msgs ) {
		$key = self::get_key();
		set_transient( $key, $msgs, 300 ); // 5-minute TTL
	}

	/**
	 * Internal: Wipe all stored messages.
	 */
	private static function clear() {
		delete_transient( self::get_key() );
	}

	/**
	 * Get a unique key per user to avoid collision.
	 */
	private static function get_key() {
		$user_id = get_current_user_id();
		return "kcharts_flash_notices_{$user_id}";
	}

	/**
	 * Render notifications as native WordPress admin notices.
	 * Used as a fallback or for non-JS environments.
	 */
	public static function display_admin_notices() {
		$msgs = self::get_all();
		if ( empty( $msgs ) ) return;

		foreach ( $msgs as $m ) {
			$type = $m['type'] === 'error' ? 'error' : ( $m['type'] === 'warning' ? 'warning' : 'success' );
			$title = ! empty( $m['title'] ) ? '<strong>' . esc_html( $m['title'] ) . ':</strong> ' : '';
			printf( 
				'<div class="notice notice-%s is-dismissible" data-kcharts-notice="1"><p>%s%s</p></div>',
				$type,
				$title,
				esc_html( $m['message'] )
			);
		}
		
		// Note: We don't clear here because JS might still want to consume them.
		// However, to avoid double display, we usually either use JS OR PHP.
		// We'll let JS clear them via get_and_clear.
	}

}
