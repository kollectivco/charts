<?php
namespace Charts\Services;

use Charts\Core\Settings;
use Charts\Core\Translation;

class TranslationApiService {

    private $api_key;
    private $api_url = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct() {
        $this->api_key = Settings::get('api.google_translate_key');
    }

    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Translates an array of strings to Arabic
     *
     * @param array $strings Array of English strings
     * @return array Array of translated strings mapped to their original english string
     */
    public function translate_batch(array $strings) {
        if (!$this->is_configured() || empty($strings)) {
            return [];
        }

        // Filter out empty strings
        $strings = array_values(array_filter($strings, function($s) { return trim($s) !== ''; }));
        
        if (empty($strings)) {
            return [];
        }

        // Limit to 100 strings per request (Google Cloud API limit)
        $chunks = array_chunk($strings, 100);
        $results = [];

        foreach ($chunks as $chunk) {
            $body = [
                'q' => $chunk,
                'target' => 'ar',
                'source' => 'en',
                'format' => 'text'
            ];

            $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => wp_json_encode($body),
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!empty($data['data']['translations'])) {
                foreach ($data['data']['translations'] as $index => $translation) {
                    $original = $chunk[$index];
                    $results[$original] = $translation['translatedText'];
                }
            }
        }

        // Save translations to the database if we got any results
        if (!empty($results)) {
            $saved = get_option('kcharts_translations', []);
            if (!is_array($saved)) $saved = [];
            
            $updated = false;
            foreach ($results as $original => $translated) {
                // Keep existing translation if it exists, or update if it's new
                if (empty($saved[$original])) {
                    $saved[$original] = $translated;
                    $updated = true;
                }
            }
            
            if ($updated) {
                update_option('kcharts_translations', $saved);
            }
        }

        return $results;
    }

    /**
     * Gets all untranslated track and video titles from the database
     *
     * @return array
     */
    public function get_untranslated_strings() {
        global $wpdb;

        // Fetch track titles
        $tracks = $wpdb->get_col("SELECT title FROM {$wpdb->prefix}charts_tracks");
        // Fetch video titles
        $videos = $wpdb->get_col("SELECT title FROM {$wpdb->prefix}charts_videos");

        $all_strings = array_merge($tracks, $videos);
        $all_strings = array_unique(array_filter($all_strings));

        // Get currently translated strings
        $saved = get_option('kcharts_translations', []);
        if (!is_array($saved)) $saved = [];

        $default_strings = Translation::$default_strings;

        $untranslated = [];
        foreach ($all_strings as $str) {
            if (!isset($saved[$str]) && !isset($default_strings[$str])) {
                $untranslated[] = $str;
            }
        }

        return $untranslated;
    }
}
