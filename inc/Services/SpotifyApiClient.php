<?php

namespace Charts\Services;

/**
 * Client for Spotify Web API.
 */
class SpotifyApiClient {

	private $client_id;
	private $client_secret;
	private $access_token;

	public function __construct() {
		$this->client_id     = get_option( 'charts_spotify_client_id' );
		$this->client_secret = get_option( 'charts_spotify_client_secret' );
	}

	/**
	 * Get or refresh access token using Client Credentials flow.
	 */
	public function get_access_token() {
		if ( ! empty( $this->access_token ) ) {
			return $this->access_token;
		}

		// Check cache
		$cached_token = get_transient( 'charts_spotify_token' );
		if ( $cached_token ) {
			$this->access_token = $cached_token;
			return $cached_token;
		}

		if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
			return new \WP_Error( 'missing_credentials', __( 'Spotify API credentials not configured.', 'charts' ) );
		}

		$response = wp_remote_post( 'https://accounts.spotify.com/api/token', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
			),
			'body' => array(
				'grant_type' => 'client_credentials',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'auth_failed', $body['error_description'] ?? __( 'Failed to retrieve access token.', 'charts' ) );
		}

		$this->access_token = $body['access_token'];
		set_transient( 'charts_spotify_token', $this->access_token, $body['expires_in'] - 60 );

		return $this->access_token;
	}

	/**
	 * Fetch track metadata.
	 */
	public function get_track( $track_id ) {
		return $this->request( "tracks/{$track_id}" );
	}

	/**
	 * Fetch artist metadata.
	 */
	public function get_artist( $artist_id ) {
		return $this->request( "artists/{$artist_id}" );
	}

	/**
	 * Search for artists.
	 */
	public function search_artist( $query, $limit = 5 ) {
		$data = $this->request( 'search?q=' . urlencode($query) . '&type=artist&limit=' . $limit );
		if ( is_wp_error( $data ) ) return $data;
		return $data['artists']['items'] ?? array();
	}

	/**
	 * Fetch top tracks for an artist.
	 */
	public function get_artist_top_tracks( $artist_id, $market = 'US' ) {
		return $this->request( "artists/{$artist_id}/top-tracks?market={$market}" );
	}

	/**
	 * Fetch multiple tracks in one request.
	 */
	public function get_tracks( array $track_ids ) {
		if ( empty( $track_ids ) ) {
			return array();
		}
		$ids = implode( ',', $track_ids );
		$data = $this->request( "tracks?ids={$ids}" );
		return $data['tracks'] ?? array();
	}

	/**
	 * Fetch multiple artists in one request.
	 */
	public function get_artists( array $artist_ids ) {
		if ( empty( $artist_ids ) ) {
			return array();
		}
		$ids = implode( ',', $artist_ids );
		$data = $this->request( "artists?ids={$ids}" );
		return $data['artists'] ?? array();
	}

	/**
	 * Private helper for API requests.
	 */
	private function request( $endpoint ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$url = "https://api.spotify.com/v1/" . ltrim( $endpoint, '/' );
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = $body['error']['message'] ?? sprintf( __( 'Spotify API returned HTTP %d', 'charts' ), $code );
			return new \WP_Error( "spotify_http_{$code}", $msg, $body );
		}

		return $body;
	}

	/**
	 * Run a health check to verify current credentials.
	 */
	public function test_connection() {
		// 1. Test Token Generation (Client Credentials Flow)
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		// 2. Test Metadata Access (Search for a known stable artist: The Weeknd)
		// This avoids issues with deleted hardcoded track IDs
		$response = $this->request( 'search?q=The+Weeknd&type=track&limit=1' );

		if ( is_wp_error( $response ) ) {
			// If we got a token but failed here, it's likely a scope or endpoint issue
			return new \WP_Error( 
				'test_search_failed', 
				sprintf( __( 'Authentication Succeeded, but Search Metadata Failed: %s', 'charts' ), $response->get_error_message() )
			);
		}

		if ( empty( $response['tracks']['items'] ) ) {
			return new \WP_Error( 'malformed_response', __( 'API responded but was unable to find metadata for search query.', 'charts' ) );
		}

		return true;
	}
}
