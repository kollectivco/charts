<?php
/**
 * Hardened YouTube Data API v3 Client
 */

namespace Charts\Services;

class YouTubeApiClient {

	private $api_key;
	private $base_url = 'https://www.googleapis.com/youtube/v3/';

	public function __construct() {
		$this->api_key = \Charts\Core\Settings::get( 'api.youtube_api_key' );
	}

	public function is_configured() {
		return ! empty( $this->api_key );
	}

	/**
	 * Private helper for API requests.
	 */
	private function request( $endpoint, $params = array() ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'missing_key', __( 'YouTube API key not configured.', 'charts' ) );
		}

		$params['key'] = $this->api_key;
		$url           = add_query_arg( $params, $this->base_url . $endpoint );
		
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$error_data = $body['error']['errors'][0] ?? array();
			$reason     = $error_data['reason'] ?? 'unknown_error';
			$msg        = $body['error']['message'] ?? sprintf( __( 'YouTube API returned HTTP %d', 'charts' ), $code );

			// Map common YouTube error reasons to descriptive internal codes
			$error_code = "youtube_{$reason}";
			return new \WP_Error( $error_code, $msg, $body );
		}

		return $body;
	}

	/**
	 * Get video details for a list of IDs.
	 */
	public function get_videos( array $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}

		$data = $this->request( 'videos', array(
			'part' => 'snippet,statistics',
			'id'   => implode( ',', $ids ),
		) );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data['items'] ?? array();
	}

	/**
	 * Search for channels.
	 */
	public function search_channels( $query, $limit = 5 ) {
		$data = $this->request( 'search', array(
			'part'       => 'snippet',
			'q'          => $query,
			'type'       => 'channel',
			'maxResults' => $limit,
		) );
		if ( is_wp_error( $data ) ) return $data;
		return $data['items'] ?? array();
	}

	/**
	 * Get channel details.
	 */
	public function get_channels( array $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}

		$data = $this->request( 'channels', array(
			'part' => 'snippet,statistics',
			'id'   => implode( ',', $ids ),
		) );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data['items'] ?? array();
	}

	/**
	 * Run a health check to verify current API key.
	 */
	public function test_connection() {
		// Test lightweight metadata request (A known public YouTube video ID as a smoke test)
		$test_video_id = 'jNQXAC9IVRw'; // Me at the zoo (first video ever)
		$items = $this->get_videos( array( $test_video_id ) );

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		if ( empty( $items ) ) {
			return new \WP_Error( 'malformed_response', __( 'API responded but video metadata was empty.', 'charts' ) );
		}

		return true;
	}
}
