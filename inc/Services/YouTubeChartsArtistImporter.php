<?php

namespace Charts\Services;

/**
 * Importer for single YouTube Charts Artist URLs.
 * Handles fetching, scraping (if needed), and API-driven enrichment.
 */
class YouTubeChartsArtistImporter {

    private $wp_api;

    public function __construct() {
        $this->wp_api = new YouTubeApiClient();
    }

    /**
     * Run the import process for a given URL.
     */
    public function import_by_url($url) {
        // 1. Validate & Parse URL
        $artist_id = $this->extract_artist_id($url);
        if (!$artist_id) {
            return new \WP_Error('invalid_url', __('The provided URL is not a valid YouTube Charts artist page.', 'charts'));
        }

        // 2. Fetch Data (Try API first if possible, otherwise scrape)
        // Note: YouTube Charts IDs like /m/01239 often map to Knowledge Graph or Channel IDs.
        // For now, we perform a fetch of the page to get the Artist Name and any embedded IDs.
        $data = $this->fetch_artist_page_data($url);
        if (is_wp_error($data)) {
            return $data;
        }

        // 3. Resolve to WordPress Entity
        return $this->process_artist_data($data, $url);
    }

    /**
     * Extract the unique identifier from the YouTube Charts URL.
     * Example: https://charts.youtube.com/artist/%2Fm%2F01239 -> /m/01239
     */
    private function extract_artist_id($url) {
        $url = trim($url);
        if (preg_match('/charts\.youtube\.com\/artist\/(.+)/i', $url, $matches)) {
            return urldecode($matches[1]);
        }
        return false;
    }

    /**
     * Fetch the HTML and extract basic metadata.
     * YouTube Charts is a JS-heavy SPA, so we look for initial data or OpenGraph tags.
     */
    private function fetch_artist_page_data($url) {
        $response = wp_remote_get($url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return new \WP_Error('empty_response', __('Could not retrieve content from the provided URL.', 'charts'));
        }

        $data = [
            'name'       => '',
            'image'      => '',
            'subscribers'=> 0,
            'id_path'    => $this->extract_artist_id($url)
        ];

        // Scrape Title (Usually "Artist Name - YouTube Charts")
        if (preg_match('/<title>(.*?) - YouTube Charts<\/title>/i', $html, $m)) {
            $data['name'] = htmlspecialchars_decode($m[1], ENT_QUOTES);
        } elseif (preg_match('/"name":"(.*?)"/i', $html, $m)) { // Initial data JSON
            $data['name'] = $m[1];
        }

        // Scrape OpenGraph Image
        if (preg_match('/property="og:image"\s+content="(.*?)"/i', $html, $m)) {
            $data['image'] = $m[1];
        }

        if (empty($data['name'])) {
            return new \WP_Error('parse_failed', __('Could not extract artist name from the page. The page might be protected or the format has changed.', 'charts'));
        }

        return $data;
    }

    /**
     * Create or Update the artist in the DB.
     */
    private function process_artist_data($data, $url) {
        $matcher = new Matcher();
        $artist_id = $matcher->match_artist($data['name']);

        if (!$artist_id) {
            return new \WP_Error('save_failed', __('Failed to create artist canonical record.', 'charts'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'charts_artists';
        $artist = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $artist_id));

        $metadata = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : [];
        $metadata['yt_charts_url'] = $url;
        $metadata['yt_charts_id']  = $data['id_path'];
        $metadata['last_scraped_at'] = current_time('mysql');
        
        $payload = [
            'display_name'  => $data['name'],
            'metadata_json' => json_encode($metadata)
        ];

        // Only update image if not already set or specifically provided
        if (!empty($data['image']) && (empty($artist->image) || strpos($artist->image, 'placeholder') !== false)) {
            $payload['image'] = $data['image'];
        }

        $wpdb->update($table, $payload, ['id' => $artist_id]);

        return [
            'id'     => $artist_id,
            'name'   => $data['name'],
            'status' => (strtotime($artist->created_at) > (time() - 30)) ? 'created' : 'updated',
            'image'  => $payload['image'] ?? $artist->image
        ];
    }
}
