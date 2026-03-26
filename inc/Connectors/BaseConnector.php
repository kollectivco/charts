<?php

namespace Charts\Connectors;

/**
 * Base abstract connector class.
 */
abstract class BaseConnector {

	/**
	 * Fetch source content from URL.
	 */
	protected function fetch_content( $url ) {
		$args = array(
			'timeout'    => 30,
			'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
		);

		$response = wp_safe_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		
		if ( empty( $body ) ) {
			return new \WP_Error( 'empty_response', __( 'The source returned an empty response.', 'charts' ) );
		}

		return $body;
	}

	/**
	 * Unified method to run the connector process.
	 */
	abstract public function run( $source_id );
}
