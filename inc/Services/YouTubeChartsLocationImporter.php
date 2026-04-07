<?php

namespace Charts\Services;

/**
 * Importer for single YouTube Charts Location URLs.
 * Extracts location data and the top artist/track rankings shown on the page.
 */
class YouTubeChartsLocationImporter {

    private $yt_api;

    public function __construct() {
        $this->yt_api = new YouTubeApiClient();
    }

    /**
     * Run the import process for a given Location URL.
     */
    public function import_by_url($url) {
        // 1. Validate & Extract ID
        $location_id = $this->extract_location_id($url);
        if (!$location_id) {
            return new \WP_Error('invalid_url', __('The provided URL is not a valid YouTube Charts location page.', 'charts'));
        }

        // 2. Fetch & Parse Page
        $data = $this->fetch_location_page_data($url);
        if (is_wp_error($data)) {
            return $data;
        }

        // 3. Save Location and Rankings
        return $this->process_location_data($data, $url, $location_id);
    }

    /**
     * Extract the unique location hex string from the URL.
     */
    private function extract_location_id($url) {
        $url = trim($url);
        // Handle encoded URLs and extract the long hex sequence (sometimes containing :)
        if (preg_match('/location\/(0x[a-f0-9%:]+)/i', $url, $matches)) {
            $id = $matches[1];
            // If it's a long sequence like 0x3e...:0x17..., we take it all but decode if needed
            return rawurldecode($id);
        }
        return false;
    }

    /**
     * Scrape the location page for metadata and rankings.
     */
    private function fetch_location_page_data($url) {
        $response = wp_remote_get($url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return new \WP_Error('empty_response', __('Could not retrieve content from the location URL.', 'charts'));
        }

        $data = [
            'name'            => '',
            'image'           => '',
            'timeframe'       => '',
            'date_range'      => '',
            'artist_rankings' => [],
            'track_rankings'  => []
        ];

        // 1. Scrape Location Name (Standard TITLE tag or Header)
        if (preg_match('/<title>(.*?) - YouTube Charts<\/title>/i', $html, $m)) {
            $data['name'] = htmlspecialchars_decode($m[1], ENT_QUOTES);
        } elseif (preg_match('/"header":\{"title":"(.*?)"/i', $html, $m)) {
             $data['name'] = htmlspecialchars_decode($m[1], ENT_QUOTES);
        }

        // 2. Scrape Timeframe/Date (Look for patterns like "Last 28 days" and date ranges)
        if (preg_match('/"subtitle":"(Last \d+ days)"/i', $html, $m)) {
            $data['timeframe'] = $m[1];
        } elseif (preg_match('/"subtitleLabel":"(.*?)"/i', $html, $m)) {
            $data['timeframe'] = $m[1];
        }

        if (preg_match('/(\w+ \d+ - \w+ \d+, \d{4})/i', $html, $m)) {
            $data['date_range'] = $m[0];
        }

        // 3. Scrape OpenGraph Image
        if (preg_match('/property="og:image"\s+content="(.*?)"/i', $html, $m)) {
            $data['image'] = $m[1];
        }

        // 4. Resilience: Try to find structured list data in initial state JSON
        $data['artist_rankings'] = $this->parse_rankings_json($html, 'artist');
        $data['track_rankings']  = $this->parse_rankings_json($html, 'track');

        if (empty($data['name'])) {
            // Fallback for name from URL if parsing failed
            $data['name'] = __('Location: ', 'charts') . substr(rawurldecode($url), -20);
        }

        return $data;
    }

    /**
     * Attempt to find ranking data in the embedded JSON payload.
     */
    private function parse_rankings_json($html, $type) {
        $rankings = [];
        
        // This is a simplified regex targeting common JSON structures in YT Charts payloads
        // In a real scenario, we'd look for "rankings":{"items":[...]}
        // For this implementation, we'll collect title/artist/rank patterns
        $regex = ($type === 'artist') 
            ? '/{"rank":(\d+),"title":"(.*?)","image":{"url":"(.*?)"}(?:,"subtitle":"(.*?)"|)/i'
            : '/{"rank":(\d+),"title":"(.*?)","subtitle":"(.*?)","image":{"url":"(.*?)"}/i';

        if (preg_match_all($regex, $html, $matches, PREG_SET_ORDER)) {
            $count = 0;
            foreach ($matches as $m) {
                if ($count >= 20) break; // Limit to top 20
                if ($type === 'artist') {
                    $rankings[] = [
                        'rank'   => (int)$m[1],
                        'name'   => $m[2],
                        'image'  => $m[3],
                        'views'  => $m[4] ?? ''
                    ];
                } else {
                    $rankings[] = [
                        'rank'   => (int)$m[1],
                        'title'  => $m[2],
                        'artist' => $m[3],
                        'image'  => $m[4]
                    ];
                }
                $count++;
            }
        }
        return $rankings;
    }

    /**
     * Save or Update the Location in the custom table.
     */
    private function process_location_data($data, $url, $external_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'charts_locations';

        // Check for existing
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE external_id = %s",
            $external_id
        ));

        $payload = [
            'name'                 => $data['name'],
            'image'                => $data['image'],
            'source_url'           => $url,
            'external_id'          => $external_id,
            'timeframe_label'      => $data['timeframe'],
            'date_range'           => $data['date_range'],
            'artist_rankings_json' => json_encode($data['artist_rankings']),
            'track_rankings_json'  => json_encode($data['track_rankings']),
            'last_scraped_at'      => current_time('mysql')
        ];

        if ($location) {
            $result = $wpdb->update($table, $payload, ['id' => $location->id]);
            if ( $result === false ) {
                return new \WP_Error('save_failed', __('Database update failed for location.', 'charts') . ' ' . $wpdb->last_error);
            }
            $id = $location->id;
            $status = 'updated';
        } else {
            $payload['slug'] = sanitize_title($data['name'] . '-' . substr($external_id, 0, 8));
            $result = $wpdb->insert($table, $payload);
            if ( $result === false ) {
                 return new \WP_Error('save_failed', __('Database insertion failed for location.', 'charts') . ' ' . $wpdb->last_error);
            }
            $id = $wpdb->insert_id;
            $status = 'created';
        }

        // Logic for auto-matching Artists/Tracks into entities can be added here
        $data = $this->match_entities($data);

        // Re-save with updated JSON containing matched IDs
        $wpdb->update($table, [
            'artist_rankings_json' => json_encode($data['artist_rankings']),
            'track_rankings_json'  => json_encode($data['track_rankings']),
        ], ['id' => $id]);

        return [
            'id'             => $id,
            'name'           => $data['name'],
            'status'         => $status,
            'artist_count'   => count($data['artist_rankings']),
            'track_count'    => count($data['track_rankings'])
        ];
    }

    /**
     * Best-effort entity matching for parsed rankings.
     */
    private function match_entities($data) {
        $matcher = new Matcher();

        // Match Artists
        if (!empty($data['artist_rankings'])) {
            foreach ($data['artist_rankings'] as &$ranking) {
                $artist_id = $matcher->match_artist($ranking['name']);
                if ($artist_id) {
                    $ranking['local_artist_id'] = $artist_id;
                    
                    // Update artist image if we have one and they don't
                    if (!empty($ranking['image'])) {
                        global $wpdb;
                        $wpdb->query($wpdb->prepare(
                            "UPDATE {$wpdb->prefix}charts_artists SET image = %s WHERE id = %d AND (image IS NULL OR image = '')",
                            $ranking['image'], $artist_id
                        ));
                    }
                }
            }
        }

        // Match Tracks
        if (!empty($data['track_rankings'])) {
            foreach ($data['track_rankings'] as &$ranking) {
                // 1. Resolve Primary Artist first
                $artist_id = $matcher->match_artist($ranking['artist']);
                if ($artist_id) {
                    $ranking['local_artist_id'] = $artist_id;
                    
                    // 2. Resolve Track
                    $track_id = $matcher->match_track($ranking['title'], $artist_id, ['image' => $ranking['image']]);
                    if ($track_id) {
                        $ranking['local_track_id'] = $track_id;
                    }
                }
            }
        }

        return $data;
    }
}
