<?php

namespace Charts\Services;

/**
 * Minimal YouTube Data API v3 Client.
 */
class YouTubeApiClient {

	private $api_key;
	private $base_url = 'https://www.googleapis.com/youtube/v3/';

	public function __construct() {
		$this->api_key = get_option( 'charts_youtube_api_key' );
	}

	public function is_configured() {
		return ! empty( $this->api_key );
	}

	/**
	 * Get video details for a list of IDs.
	 */
	public function get_videos( array $ids ) {
		if ( ! $this->is_configured() || empty( $ids ) ) {
			return array();
		}

		$params = array(
			'part' => 'snippet,statistics',
			'id'   => implode( ',', $ids ),
			'key'  => $this->api_key,
		);

		$url      = add_query_arg( $params, $this->base_url . 'videos' );
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['items'] ?? array();
	}

	/**
	 * Get channel details.
	 */
	public function get_channels( array $ids ) {
		if ( ! $this->is_configured() || empty( $ids ) ) {
			return array();
		}

		$params = array(
			'part' => 'snippet,statistics',
			'id'   => implode( ',', $ids ),
			'key'  => $this->api_key,
		);

		$url      = add_query_arg( $params, $this->base_url . 'channels' );
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body['items'] ?? array();
	}
}
