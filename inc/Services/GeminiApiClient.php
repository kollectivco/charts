<?php
/**
 * Google Gemini Generative AI Client for KCharts.
 * Handles entity transliteration/translation, semantic search expansion, and editorial music intelligence.
 */

namespace Charts\Services;

use Charts\Core\Settings;

class GeminiApiClient {

	private $api_key;
	private $model;
	private $base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
	private $_in_fallback = false;

	public function __construct( $api_key = null, $model = null ) {
		$this->api_key = $api_key ?: Settings::get( 'api.gemini_api_key' );
		$this->model   = $model ?: Settings::get( 'api.gemini_model', 'gemini-3.1-pro-preview' );
		if ( empty( $this->model ) ) {
			$this->model = 'gemini-3.1-pro-preview';
		}
	}

	public function is_configured(): bool {
		return ! empty( $this->api_key );
	}

	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Retrieve all active models that support generateContent directly from Google for this API key.
	 */
	public function fetch_available_models() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'gemini_not_configured', __( 'API key is missing.', 'charts' ) );
		}

		$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode( $this->api_key );
		$res = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$status = wp_remote_retrieve_response_code( $res );
		$body   = json_decode( wp_remote_retrieve_body( $res ), true );

		if ( $status !== 200 ) {
			$msg = $body['error']['message'] ?? sprintf( __( 'HTTP %d error listing models.', 'charts' ), $status );
			return new \WP_Error( 'gemini_list_error', $msg, $body );
		}

		$models = array();
		if ( ! empty( $body['models'] ) && is_array( $body['models'] ) ) {
			foreach ( $body['models'] as $m ) {
				$methods = $m['supportedGenerationMethods'] ?? array();
				if ( in_array( 'generateContent', $methods, true ) ) {
					$clean_name = str_replace( 'models/', '', $m['name'] );
					$models[] = array(
						'name'        => $clean_name,
						'displayName' => $m['displayName'] ?? $clean_name,
					);
				}
			}
		}

		return $models;
	}

	/**
	 * Send request to Gemini generateContent endpoint with automatic fallback.
	 */
	public function generate_content( string $prompt, string $system_instruction = '', float $temperature = 0.2, bool $json_mode = false ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'gemini_not_configured', __( 'Google Gemini API key is not configured in Settings.', 'charts' ) );
		}

		$endpoint = $this->base_url . $this->model . ':generateContent?key=' . urlencode( $this->api_key );

		$body_data = array(
			'contents' => array(
				array(
					'parts' => array(
						array( 'text' => $prompt ),
					),
				),
			),
			'generationConfig' => array(
				'temperature' => $temperature,
			),
		);

		if ( $json_mode ) {
			$body_data['generationConfig']['responseMimeType'] = 'application/json';
		}

		if ( ! empty( $system_instruction ) ) {
			$body_data['systemInstruction'] = array(
				'parts' => array(
					array( 'text' => $system_instruction ),
				),
			);
		}

		$response = wp_remote_post( $endpoint, array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $body_data ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status   = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true );

		if ( $status !== 200 ) {
			$err_msg = $data['error']['message'] ?? sprintf( __( 'Gemini API error (HTTP %d)', 'charts' ), $status );

			// Auto-fallback if the current model is deprecated or not available to user
			if ( ( $status === 404 || stripos( $err_msg, 'not available' ) !== false || stripos( $err_msg, 'not found' ) !== false ) && empty( $this->_in_fallback ) ) {
				$this->_in_fallback = true;
				
				// Dynamically ask Google for available models
				$available = $this->fetch_available_models();
				if ( ! is_wp_error( $available ) && ! empty( $available ) ) {
					foreach ( $available as $candidate ) {
						if ( $candidate['name'] === $this->model ) continue;
						$this->model = $candidate['name'];
						$retry = $this->generate_content( $prompt, $system_instruction, $temperature, $json_mode );
						if ( ! is_wp_error( $retry ) ) {
							Settings::set( 'api.gemini_model', $candidate['name'] );
							$this->_in_fallback = false;
							return $retry;
						}
					}
				}
				$this->_in_fallback = false;
			}

			return new \WP_Error( 'gemini_api_error', $err_msg, $data );
		}

		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
		if ( empty( $text ) ) {
			return new \WP_Error( 'gemini_empty_response', __( 'Gemini returned an empty response.', 'charts' ), $data );
		}

		return trim( $text );
	}

	/**
	 * Test connection by dynamically discovering the active model from Google.
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'gemini_not_configured', __( 'Google Gemini API key is not configured in Settings.', 'charts' ) );
		}

		$available = $this->fetch_available_models();
		if ( is_wp_error( $available ) ) {
			return $available;
		}

		if ( empty( $available ) ) {
			return new \WP_Error( 'gemini_no_models', __( 'No models supporting generateContent were found for this API key.', 'charts' ) );
		}

		$names = array_column( $available, 'name' );

		// Preferred priority order
		$priority = array(
			$this->model,
			'gemini-3.1-pro-preview',
			'gemini-2.5-flash',
			'gemini-2.0-flash',
		);

		$candidates = array_unique( array_merge(
			array_intersect( $priority, $names ),
			$names
		) );

		$last_error = null;
		foreach ( $candidates as $candidate_model ) {
			$this->model = $candidate_model;
			$result = $this->generate_content( 'Respond with exact word: PONG' );
			if ( ! is_wp_error( $result ) ) {
				Settings::set( 'api.gemini_model', $candidate_model );
				return array(
					'success' => true,
					'model'   => $candidate_model,
					'message' => sprintf( __( 'Handshake successful using model %s!', 'charts' ), $candidate_model ),
				);
			}
			$last_error = $result;
		}

		return $last_error;
	}

	/**
	 * Translate or transliterate Arabic music entities to standard English names and slugs.
	 */
	public function translate_entities_batch( array $items ) {
		if ( empty( $items ) ) {
			return array();
		}

		$system = "You are a professional music metadata curator for Middle Eastern and global charts.
Your goal is to provide accurate, industry-standard English display names and URL slugs for Arabic artists and tracks.
RULES:
1. For Artists: Transliterate using standard phonetic English spelling as recognized on Spotify, Apple Music, and Billboard (e.g. 'عمرو دياب' -> 'Amr Diab', 'أحمد سعد' -> 'Ahmed Saad', 'عصام صاصا' -> 'Essam Sasa', 'شيرين' -> 'Sherine').
2. NEVER use Arabizi or Franco-Arabic numbers (like 7, 3, 2, 5). Use strictly proper Latin letters.
3. For Tracks: If the track has a globally recognized English release title, use it. Otherwise, provide standard romanized title (e.g. 'تملي معاك' -> 'Tamally Maak').
4. The slug must be clean lowercase alphanumeric with hyphens only.
5. Return a strict JSON array of objects with keys: 'id', 'english_name', 'slug'.";

		$prompt = "Translate and transliterate these music entities:\n" . wp_json_encode( $items, JSON_UNESCAPED_UNICODE );

		$json_str = $this->generate_content( $prompt, $system, 0.1, true );
		if ( is_wp_error( $json_str ) ) {
			return $json_str;
		}

		$decoded = json_decode( $json_str, true );
		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'gemini_json_parse_error', __( 'Could not parse Gemini structured translation output.', 'charts' ) );
		}

		return $decoded;
	}

	/**
	 * Generate an authoritative editorial music journalism brief based on chart signals.
	 */
	public function generate_editorial_insights( array $context_data ) {
		$system = "You are an elite music analyst and editor for Billboard Arabia and Rolling Stone MENA.
Write a concise, high-impact weekly editorial brief in professional Arabic analyzing current music movements, standout hits, rising stars, and predictions.
Keep tone analytical, journalistic, insightful, and exciting.
Structure your brief into 3 distinct sections with short headings:
1. 🚀 تريندات الأسبوع وتحركات الصدارة (Weekly Trends & Top Movers)
2. ⭐ فنان تحت الأضواء (Artist Spotlight & Momentum)
3. 🔮 توقعات السباق القادم (Next Week Chart Projections)";

		$prompt = "Analyze this weekly music chart intelligence data and write the weekly editorial brief:\n" . wp_json_encode( $context_data, JSON_UNESCAPED_UNICODE );

		return $this->generate_content( $prompt, $system, 0.6, false );
	}

	/**
	 * Expand an entity search query with popular nicknames, aliases, and spelling variations.
	 */
	public function expand_search_query( string $query ) {
		$system = "You are a music search assistant for Arab and Middle Eastern music.
Given a search term (which could be an artist nickname like 'الهضبة' or 'الكينج', a slang abbreviation, a song snippet, or a misspelled name),
return a JSON array of up to 4 search queries / official artist names or track titles.
If it is a known nickname, include the real artist name in Arabic and English.
Return JSON array of strings only.";

		$prompt = "Search query: " . $query;
		$json_str = $this->generate_content( $prompt, $system, 0.1, true );
		if ( is_wp_error( $json_str ) ) {
			return array( $query );
		}

		$decoded = json_decode( $json_str, true );
		if ( is_array( $decoded ) && ! empty( $decoded ) ) {
			return array_values( array_unique( array_merge( array( $query ), $decoded ) ) );
		}

		return array( $query );
	}
}
