<?php

namespace Charts\Services;

/**
 * Thin REST client for the Soundcharts customer API.
 */
class SoundchartsApiClient {

	/**
	 * Cached settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct( array $overrides = array() ) {
		$this->settings = array_merge( SoundchartsSettings::get(), $overrides );
	}

	/**
	 * Determine whether client credentials exist.
	 */
	public function is_configured() {
		return ! empty( $this->settings['app_id'] ) && ! empty( $this->settings['api_key'] );
	}

	/**
	 * Probe API availability with authenticated requests.
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'soundcharts_not_configured', __( 'Soundcharts credentials are missing.', 'kontentainment-lists' ) );
		}

		$endpoint_map = SoundchartsSettings::get_endpoint_map();
		$paths        = array_unique(
			array_filter(
				array(
					$endpoint_map['connection_probe'] ?? '',
					'/api/v2/me',
					'/api/v2/charts/track',
				)
			)
		);

		foreach ( $paths as $path ) {
			$response = $this->request( $path, array(), array( 'perPage' => 1 ) );

			if ( ! is_wp_error( $response ) ) {
				return array(
					'ok'      => true,
					'path'    => $path,
					'mode'    => $this->settings['mode'],
					'message' => __( 'Authenticated response received from Soundcharts.', 'kontentainment-lists' ),
				);
			}

			if ( ! in_array( $response->get_error_code(), array( 'soundcharts_http_404', 'soundcharts_empty_response' ), true ) ) {
				return $response;
			}
		}

		return new \WP_Error(
			'soundcharts_probe_failed',
			__( 'Could not confirm the Soundcharts connection. Check the endpoint mapping or credentials.', 'kontentainment-lists' )
		);
	}

	/**
	 * Perform a GET request.
	 */
	public function request( $path, array $query = array(), array $fallback_query = array() ) {
		$url      = trailingslashit( untrailingslashit( SoundchartsSettings::get_base_url() ) ) . ltrim( $path, '/' );
		$query    = array_filter( $query, array( $this, 'is_not_null' ) );
		$url      = add_query_arg( $query, $url );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $this->settings['timeout'],
				'headers' => array(
					'x-app-id'  => $this->settings['app_id'],
					'x-api-key' => $this->settings['api_key'],
					'Accept'    => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'request_error', array( 'path' => $path, 'message' => $response->get_error_message() ) );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = $body !== '' ? json_decode( $body, true ) : null;

		if ( $code === 429 ) {
			$this->log( 'rate_limited', array( 'path' => $path, 'code' => $code ) );
			return new \WP_Error( 'soundcharts_http_429', __( 'Soundcharts rate limit reached.', 'kontentainment-lists' ) );
		}

		if ( $code === 401 || $code === 403 ) {
			$this->log( 'auth_failed', array( 'path' => $path, 'code' => $code ) );
			return new \WP_Error( 'soundcharts_auth_failed', __( 'Soundcharts authentication failed.', 'kontentainment-lists' ) );
		}

		if ( $code < 200 || $code >= 300 ) {
			if ( ! empty( $fallback_query ) && empty( $query ) ) {
				return $this->request( $path, $fallback_query );
			}

			$this->log( 'http_error', array( 'path' => $path, 'code' => $code ) );
			return new \WP_Error(
				'soundcharts_http_' . $code,
				sprintf( __( 'Soundcharts request failed with HTTP %d.', 'kontentainment-lists' ), $code ),
				array( 'body' => is_array( $data ) ? $data : $body )
			);
		}

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'soundcharts_empty_response', __( 'Soundcharts returned an empty or invalid JSON payload.', 'kontentainment-lists' ) );
		}

		return $data;
	}

	/**
	 * Iterate paginated results until exhausted or limited.
	 */
	public function get_paginated( $path, array $query = array(), $max_pages = 10 ) {
		$all_rows     = array();
		$page         = 1;
		$request_count = 0;
		$last_payload = array();

		while ( $page <= $max_pages ) {
			$current_query              = $query;
			$current_query['page']      = $page;
			$current_query['perPage']   = isset( $current_query['perPage'] ) ? (int) $current_query['perPage'] : 100;
			$payload                    = $this->request( $path, $current_query );
			$request_count++;

			if ( is_wp_error( $payload ) ) {
				return $payload;
			}

			$rows = $this->extract_rows( $payload );
			$all_rows = array_merge( $all_rows, $rows );
			$last_payload = $payload;

			if ( empty( $rows ) || ! $this->has_next_page( $payload, $page ) ) {
				break;
			}

			$page++;
		}

		return array(
			'rows'          => $all_rows,
			'request_count' => $request_count,
			'last_payload'  => $last_payload,
		);
	}

	/**
	 * Extract candidate item rows from heterogeneous payload shapes.
	 */
	public function extract_rows( array $payload ) {
		$candidates = array( 'items', 'results', 'data', 'entries', 'tracks', 'artists', 'kontentainment-lists' );
		foreach ( $candidates as $candidate ) {
			if ( isset( $payload[ $candidate ] ) && is_array( $payload[ $candidate ] ) ) {
				return array_values( $payload[ $candidate ] );
			}
		}

		if ( isset( $payload[0] ) ) {
			return array_values( $payload );
		}

		return array();
	}

	/**
	 * Check whether pagination metadata indicates another page.
	 */
	private function has_next_page( array $payload, $page ) {
		if ( isset( $payload['next'] ) && ! empty( $payload['next'] ) ) {
			return true;
		}
		if ( isset( $payload['pagination']['nextPage'] ) ) {
			return (int) $payload['pagination']['nextPage'] > $page;
		}
		if ( isset( $payload['pagination']['pageCount'] ) ) {
			return (int) $payload['pagination']['pageCount'] > $page;
		}
		if ( isset( $payload['totalPages'] ) ) {
			return (int) $payload['totalPages'] > $page;
		}

		return false;
	}

	/**
	 * Record debug events without leaking secrets.
	 */
	private function log( $event, array $context ) {
		if ( empty( $this->settings['enable_logging'] ) ) {
			return;
		}

		$context['mode'] = $this->settings['mode'];
		error_log( '[Kontentainment Charts][Soundcharts] ' . $event . ' ' . wp_json_encode( $context ) );
	}

	/**
	 * Callback for filtering query args.
	 */
	private function is_not_null( $value ) {
		return $value !== null && $value !== '';
	}
}
