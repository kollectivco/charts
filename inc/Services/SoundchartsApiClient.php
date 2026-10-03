<?php

namespace Charts\Services;

/** Server-side client for the Soundcharts API. */
class SoundchartsApiClient {
	const API_BASE   = 'https://customer.api.soundcharts.com';
	const TOKEN_URL  = 'https://account.soundcharts.com/oauth/token';
	const TOKEN_TTL  = 3600;

	private $client_id;
	private $client_secret;
	private $team_id;
	private $token_cache_key;

	public function __construct() {
		$this->client_id     = trim( (string) \Charts\Core\Settings::get( 'api.soundcharts_client_id', '' ) );
		$this->client_secret = trim( (string) \Charts\Core\Settings::get( 'api.soundcharts_client_secret', '' ) );
		$this->team_id       = trim( (string) \Charts\Core\Settings::get( 'api.soundcharts_team_id', '' ) );
		$this->token_cache_key = 'charts_soundcharts_token_' . md5( $this->client_id . '|' . $this->team_id );
	}

	/** Return true when credentials are configured. */
	public function is_configured() {
		return $this->client_id !== '' && $this->client_secret !== '';
	}

	/** Get the chart platforms Soundcharts exposes for songs or albums. */
	public function get_platforms( $entity_type ) {
		$entity_type = $this->normalize_entity_type( $entity_type );
		if ( is_wp_error( $entity_type ) ) return $entity_type;

		$cache_key = 'charts_soundcharts_platforms_' . $entity_type;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) return $cached;

		$data = $this->request( '/api/v2/chart/' . $entity_type . '/platforms' );
		if ( is_wp_error( $data ) ) return $data;

		$platforms = array();
		foreach ( (array) ( $data['items'] ?? array() ) as $platform ) {
			$code = sanitize_key( $platform['code'] ?? $platform['platform'] ?? $platform['slug'] ?? '' );
			$name = sanitize_text_field( $platform['name'] ?? $platform['label'] ?? $code );
			if ( $code !== '' ) $platforms[] = array( 'code' => $code, 'name' => $name );
		}

		set_transient( $cache_key, $platforms, 6 * HOUR_IN_SECONDS );
		return $platforms;
	}

	/** Get the chart catalogue for a platform and country. */
	public function get_charts( $entity_type, $platform, $country_code = '' ) {
		$entity_type = $this->normalize_entity_type( $entity_type );
		if ( is_wp_error( $entity_type ) ) return $entity_type;

		$platform = sanitize_key( $platform );
		if ( $platform === '' ) return new \WP_Error( 'soundcharts_platform_required', __( 'Choose a Soundcharts platform.', 'charts' ) );

		$country_code = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $country_code ) );
		$cache_key    = 'charts_soundcharts_catalog_' . md5( $entity_type . '|' . $platform . '|' . $country_code );
		$cached       = get_transient( $cache_key );
		if ( is_array( $cached ) ) return $cached;

		$charts = array();
		$offset = 0;
		$limit  = 100;
		for ( $page = 0; $page < 10; $page++ ) {
			$path  = '/api/v2/chart/' . $entity_type . '/by-platform/' . rawurlencode( $platform );
			$query = array( 'offset' => $offset, 'limit' => $limit );
			if ( $country_code !== '' ) $query['countryCode'] = $country_code;
			$data = $this->request( $path, $query );
			if ( is_wp_error( $data ) ) return $data;

			$items = (array) ( $data['items'] ?? array() );
			foreach ( $items as $chart ) {
				$slug = sanitize_text_field( $chart['slug'] ?? '' );
				if ( $slug === '' ) continue;
				$charts[] = array(
					'slug'        => $slug,
					'name'        => sanitize_text_field( $chart['name'] ?? $slug ),
					'type'        => $entity_type,
					'platform'    => sanitize_key( $chart['platform'] ?? $platform ),
					'frequency'   => $this->normalize_frequency( $chart['frequency'] ?? 'weekly' ),
					'countryCode' => strtoupper( sanitize_text_field( $chart['countryCode'] ?? '' ) ),
					'countryName' => sanitize_text_field( $chart['countryName'] ?? '' ),
					'maxResults'  => absint( $chart['maxResultsCount'] ?? 100 ),
					'webUrl'      => esc_url_raw( $chart['webUrl'] ?? '' ),
				);
			}

			$total = absint( $data['page']['total'] ?? count( $charts ) );
			if ( empty( $items ) || count( $charts ) >= $total || count( $items ) < $limit ) break;
			$offset += $limit;
		}

		set_transient( $cache_key, $charts, 15 * MINUTE_IN_SECONDS );
		return $charts;
	}

	/** Get the latest ranking for one Soundcharts song or album chart. */
	public function get_latest_ranking( $entity_type, $slug, $limit = 100, $offset = 0 ) {
		$entity_type = $this->normalize_entity_type( $entity_type );
		if ( is_wp_error( $entity_type ) ) return $entity_type;
		$version = $entity_type === 'song' ? 'v2.14' : 'v2.26';
		$limit   = min( 100, max( 1, absint( $limit ) ) );
		return $this->request( '/api/' . $version . '/chart/' . $entity_type . '/' . rawurlencode( $slug ) . '/ranking/latest', array( 'offset' => max( 0, absint( $offset ) ), 'limit' => $limit ) );
	}

	/** Make a JSON GET request, retrying once after an expired bearer token. */
	private function request( $path, array $query = array(), $retry = true ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) return $token;

		$url = self::API_BASE . $path;
		if ( ! empty( $query ) ) $url = add_query_arg( $query, $url );
		$response = wp_safe_remote_get( $url, array(
			'timeout' => 35,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
			),
		) );
		if ( is_wp_error( $response ) ) return $response;

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status === 401 && $retry ) {
			delete_transient( $this->token_cache_key );
			return $this->request( $path, $query, false );
		}
		if ( $status < 200 || $status >= 300 ) {
			$message = $body['errors'][0]['message'] ?? $body['message'] ?? sprintf( __( 'Soundcharts API returned HTTP %d.', 'charts' ), $status );
			if ( $status === 403 ) $message = __( 'Soundcharts denied this endpoint for the current plan or credentials.', 'charts' );
			if ( $status === 429 ) $message = __( 'Soundcharts request quota or rate limit was reached. Try again later.', 'charts' );
			return new \WP_Error( 'soundcharts_http_' . $status, sanitize_text_field( $message ) );
		}
		if ( ! is_array( $body ) ) return new \WP_Error( 'soundcharts_invalid_json', __( 'Soundcharts returned invalid JSON.', 'charts' ) );
		return $body;
	}

	/** Generate/cache a short-lived OAuth access token. */
	private function get_access_token() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'soundcharts_credentials_missing', __( 'Add Soundcharts Client ID and Client Secret in Charts → Settings → Service Nexus.', 'charts' ) );
		}

		$cached = get_transient( $this->token_cache_key );
		if ( is_string( $cached ) && $cached !== '' ) return $cached;

		$body = array( 'grant_type' => 'client_credentials' );
		if ( $this->team_id !== '' ) $body['team_id'] = $this->team_id;
		$response = wp_safe_remote_post( self::TOKEN_URL, array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Accept'        => 'application/json',
			),
			'body' => $body,
		) );
		if ( is_wp_error( $response ) ) return $response;

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || empty( $data['access_token'] ) ) {
			$message = $data['errors'][0]['message'] ?? $data['error_description'] ?? __( 'Could not authenticate with Soundcharts. Check the Client ID and Client Secret.', 'charts' );
			return new \WP_Error( 'soundcharts_auth_failed', sanitize_text_field( $message ) );
		}

		$expires = isset( $data['expires_in'] ) ? absint( $data['expires_in'] ) : self::TOKEN_TTL;
		set_transient( $this->token_cache_key, sanitize_text_field( $data['access_token'] ), max( 60, $expires - 60 ) );
		return sanitize_text_field( $data['access_token'] );
	}

	private function normalize_entity_type( $entity_type ) {
		$entity_type = sanitize_key( $entity_type );
		return in_array( $entity_type, array( 'song', 'album' ), true )
			? $entity_type
			: new \WP_Error( 'soundcharts_entity_invalid', __( 'Soundcharts import supports song and album charts.', 'charts' ) );
	}

	private function normalize_frequency( $frequency ) {
		$frequency = strtolower( sanitize_text_field( $frequency ) );
		if ( strpos( $frequency, 'day' ) !== false ) return 'daily';
		if ( strpos( $frequency, 'month' ) !== false ) return 'monthly';
		return 'weekly';
	}
}
