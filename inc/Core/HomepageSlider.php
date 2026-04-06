<?php
namespace Charts\Core;

class HomepageSlider {

    public static function get_premium_settings() {
        return [
            'enable'               => Settings::get('slider.enable'),
            'source_mode'          => Settings::get('slider.source_mode'),
            'selected_charts'      => (array)Settings::get('slider.selected_charts', []),
            'manual_slides_json'   => Settings::get('slider.manual_slides_json', '[]'),
            'height_vh'            => Settings::get('slider.height_vh'),
            'mobile_height_vh'     => Settings::get('slider.mobile_height_vh'),
            'width'                => Settings::get('slider.max_width_px'),
            'radius'               => Settings::get('slider.border_radius_px'),
            'overlay'              => Settings::get('slider.overlay_opacity_pct'),
            'autoplay'             => Settings::get('slider.autoplay'),
            'delay'                => Settings::get('slider.delay_ms'),
            'speed'                => Settings::get('slider.speed_ms'),
            'loop'                 => Settings::get('slider.loop'),
            'pause'                => Settings::get('slider.pause_on_hover'),
        ];
    }

    /**
     * Resolve slides based on the active source mode.
     */
    public static function get_slides_data($count = 10) {
        $premium = self::get_premium_settings();
        $mode    = $premium['source_mode'];
        $slides  = [];

        switch ( $mode ) {
            case 'latest':
                $slides = self::get_slides_from_charts('latest', $count);
                break;
            case 'selected':
                $slides = self::get_slides_from_charts('selected', $count);
                break;
            case 'selection_top':
                $slides = self::get_slides_from_charts('selection_top', $count);
                break;
            case 'artists':
                $slides = self::get_slides_from_artists((array)$premium['selected_artists'], $count);
                break;
            case 'tracks':
                $slides = self::get_slides_from_tracks((array)$premium['selected_tracks'], $count);
                break;
            case 'manual':
                $slides = self::get_slides_from_manual($premium['manual_slides_json']);
                break;
        }

        return $slides;
    }

    private static function get_slides_from_charts($submode, $count) {
        $premium = self::get_premium_settings();
        $chart_ids = ($submode === 'latest') ? self::get_latest_chart_ids($count) : (array)$premium['selected_charts'];
        
        if (empty($chart_ids)) return [];

        global $wpdb;
        $slides = [];
        $manager = new \Charts\Admin\SourceManager();

        foreach ( array_slice($chart_ids, 0, $count) as $id ) {
            $def = $manager->get_definition($id);
            if (!$def) continue;

            if ($submode === 'selection_top' || $submode === 'latest') {
                // Fetch #1 item
                $row = $wpdb->get_row($wpdb->prepare("
                    SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), t.cover_image, v.thumbnail, a.image) as resolved_thumb 
                    FROM {$wpdb->prefix}charts_entries e
                    JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
                    LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
                    LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
                    LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
                    WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
                    ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
                ", $def->chart_type, $def->country_code));

                if ($row) {
                    $slides[] = [
                        'title'     => $row->track_name,
                        'desc'      => $row->artist_names,
                        'badge'     => '#1 Trending',
                        'image_url' => $row->resolved_thumb ?: $def->cover_image_url,
                        'btn1_text' => 'View Chart',
                        'btn1_link' => home_url('/charts/' . $def->slug . '/'),
                        'btn2_text' => 'Play Song',
                        'btn2_link' => '#', 
                    ];
                    continue;
                }
            }

            // Default to Chart Slide
            $slides[] = [
                'title'     => $def->title,
                'desc'      => $def->chart_summary,
                'badge'     => 'Featured Chart',
                'image_url' => $def->cover_image_url ?: CHARTS_URL . 'public/assets/img/placeholder.png',
                'btn1_text' => 'Browse Chart',
                'btn1_link' => home_url('/charts/' . $def->slug . '/'),
            ];
        }
        return $slides;
    }

    private static function get_slides_from_artists($artist_ids, $count) {
        if (empty($artist_ids)) return [];
        global $wpdb;
        $slides = [];

        $ids_string = implode(',', array_map('intval', $artist_ids));
        $artists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}charts_artists WHERE id IN ($ids_string) LIMIT $count");

        foreach ($artists as $a) {
            $slides[] = [
                'title'     => $a->display_name,
                'desc'      => 'Trending Artist Profile',
                'badge'     => 'Hot Artist',
                'image_url' => $a->image ?: CHARTS_URL . 'public/assets/img/placeholder.png',
                'btn1_text' => 'View Profile',
                'btn1_link' => home_url('/charts/artist/' . ($a->slug ?? sanitize_title($a->display_name)) . '/'),
            ];
        }
        return $slides;
    }

    private static function get_slides_from_tracks($track_ids, $count) {
        if (empty($track_ids)) return [];
        global $wpdb;
        $slides = [];

        $ids_string = implode(',', array_map('intval', $track_ids));
        $tracks = $wpdb->get_results("
            SELECT t.*, a.display_name as artist_name 
            FROM {$wpdb->prefix}charts_tracks t
            LEFT JOIN {$wpdb->prefix}charts_artists a ON a.id = t.primary_artist_id
            WHERE t.id IN ($ids_string) LIMIT $count
        ");

        foreach ($tracks as $t) {
            $slides[] = [
                'title'     => $t->title,
                'desc'      => $t->artist_name,
                'badge'     => 'Featured Track',
                'image_url' => $t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png',
                'btn1_text' => 'Listen Now',
                'btn1_link' => '#',
            ];
        }
        return $slides;
    }

    private static function get_slides_from_manual($json) {
        $data = json_decode($json, true);
        if (empty($data) || !is_array($data)) return [];
        
        $slides = [];
        foreach ($data as $m) {
            $slides[] = [
                'title'     => $m['title'] ?? 'Featured',
                'desc'      => $m['subtitle'] ?? '',
                'badge'     => $m['badge'] ?? 'Featured',
                'image_url' => $m['image'] ?? CHARTS_URL . 'public/assets/img/placeholder.png',
                'btn1_text' => $m['btn_text'] ?? 'Learn More',
                'btn1_link' => $m['url'] ?? '#',
            ];
        }
        return $slides;
    }

    private static function get_latest_chart_ids($count) {
        global $wpdb;
        return $wpdb->get_col("SELECT id FROM {$wpdb->prefix}charts_definitions WHERE is_active = 1 ORDER BY id DESC LIMIT $count");
    }
}
