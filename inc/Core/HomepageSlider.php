<?php
namespace Charts\Core;

class HomepageSlider {

    public static function get_global_settings() {
        return [
            'slider_enable' => get_option('charts_slider_enable', 1),
            'slider_style' => get_option('charts_slider_style', 'coverflow'),
            'slider_count' => get_option('charts_slider_count', 5),
            'slider_loop' => get_option('charts_slider_loop', 1),
            'slider_autoplay' => get_option('charts_slider_autoplay', 1),
            'slider_delay' => get_option('charts_slider_delay', 3000),
            'slider_arrows' => get_option('charts_slider_arrows', 1),
            'slider_pagination' => get_option('charts_slider_pagination', 1),
            'slider_swipe' => get_option('charts_slider_swipe', 1),
            'slider_keyboard' => get_option('charts_slider_keyboard', 1),
            
            'slider_speed' => get_option('charts_slider_speed', 600),
            'slider_easing' => get_option('charts_slider_easing', 'cubic-bezier(0.25, 1, 0.5, 1)'),
            'slider_center' => get_option('charts_slider_center', 1),
            'slider_depth' => get_option('charts_slider_depth', 150),
            'slider_rotation' => get_option('charts_slider_rotation', 45),
            'slider_opacity' => get_option('charts_slider_opacity', 0.6),
            'slider_scale' => get_option('charts_slider_scale', 0.8),
            'slider_spacing' => get_option('charts_slider_spacing', 50),
            'slider_shadow' => get_option('charts_slider_shadow', 0.3),
            'slider_glow' => get_option('charts_slider_glow', 1),

            'slider_max_width' => get_option('charts_slider_max_width', '1440px'),
            'slider_min_height' => get_option('charts_slider_min_height', '500px'),
            'slider_aspect_ratio' => get_option('charts_slider_aspect_ratio', '16/9'),
            'slider_align' => get_option('charts_slider_align', 'center'),
            'slider_overlay' => get_option('charts_slider_overlay', 0.5),
            'slider_radius' => get_option('charts_slider_radius', '16px'),
            'slider_mobile_mode' => get_option('charts_slider_mobile_mode', 'stack'),

            'slider_show_label' => get_option('charts_slider_show_label', 1),
            'slider_show_meta' => get_option('charts_slider_show_meta', 1),
            'slider_show_cta' => get_option('charts_slider_show_cta', 1),
            'slider_cta_text' => get_option('charts_slider_cta_text', 'VIEW CHART'),
        ];
    }

    public static function get_slides_data($chart_ids, $count = 5) {
        if ( empty( $chart_ids ) ) {
            $manager = new \Charts\Admin\SourceManager();
            $defs = $manager->get_definitions( true );
            $chart_ids = array_map( function($d){ return $d->id; }, $defs );
        }

        $slides = [];
        $manager = new \Charts\Admin\SourceManager();
        global $wpdb;

        foreach ( array_slice((array)$chart_ids, 0, $count) as $id ) {
            $def = $manager->get_definition( $id );
            if ( ! $def ) continue;

            $row = $wpdb->get_row( $wpdb->prepare( "
                SELECT e.* FROM {$wpdb->prefix}charts_entries e
                JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
                WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
                ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
            ", $def->chart_type, $def->country_code ) );

            $slides[] = [
                'id'            => $def->id,
                'title'         => $def->title_ar ?: $def->title,
                'subtitle'      => $def->chart_summary,
                'leader_name'   => $row->track_name ?? 'Trending Now',
                'leader_artist' => $row->artist_names ?? 'Global Charts',
                'image'         => $row->cover_image ?? $def->cover_image_url ?? CHARTS_URL . 'public/assets/img/placeholder.png',
                'url'           => home_url('/charts/' . $def->slug . '/'),
                'accent'        => $def->accent_color ?: '#fe025b',
                'platform'      => $def->platform ?? 'Global',
                'region'        => $def->country_name ?? 'Global'
            ];
        }

        // --- MANUAL SLIDES OVERLAY ---
        $source_mode = get_option('charts_slider_source_mode', 'dynamic');
        if ( $source_mode === 'manual' ) {
            $manual_json = get_option('charts_slider_manual_slides', '[]');
            $manual_data = json_decode($manual_json, true);
            if ( !empty($manual_data) && is_array($manual_data) ) {
                $manual_slides = [];
                foreach ( $manual_data as $m ) {
                    $manual_slides[] = [
                        'title'         => $m['title'] ?? 'Featured',
                        'subtitle'      => $m['subtitle'] ?? '',
                        'leader_name'   => $m['title'] ?? 'Featured',
                        'leader_artist' => $m['subtitle'] ?? '',
                        'image'         => $m['image'] ?? CHARTS_URL . 'public/assets/img/placeholder.png',
                        'url'           => $m['url'] ?? '#',
                        'accent'        => $m['accent'] ?? '#fe025b',
                        'platform'      => 'Selection',
                        'region'        => 'Global'
                    ];
                }
                return $manual_slides;
            }
        }

        return $slides;
    }

    public static function render($slides, $settings, $context = 'widget') {
        if (empty($slides)) {
            echo '<div style="padding:40px; text-align:center; border:2px dashed #eee;">No chart data found for slider.</div>';
            return;
        }

        $style = $settings['slider_style'] ?? 'coverflow';
        
        $template = CHARTS_PATH . "public/templates/parts/slider-{$style}.php";
        if ( !file_exists($template) ) {
            $template = CHARTS_PATH . "public/templates/parts/slider-coverflow.php";
            $style = 'coverflow';
        }

        $config = self::build_config($settings, $style);

        // Safe rendering guards
        $max_width = esc_attr($settings['slider_max_width'] ?? '1440px');
        $min_height = esc_attr($settings['slider_min_height'] ?? '500px');
        $radius = esc_attr($settings['slider_radius'] ?? '16px');
        $mobile_mode = esc_attr($settings['slider_mobile_mode'] ?? 'stack');

        echo "<div class=\"kc-slider-system kc-slider-{$style} kc-mobile-{$mobile_mode}\" data-config='" . esc_attr(json_encode($config)) . "' style=\"--kc-max-width: {$max_width}; --kc-min-height: {$min_height}; --kc-radius: {$radius}; overflow: hidden; position: relative;\">";
        
        include $template;

        echo "</div>";
    }

    private static function build_config($settings, $style) {
        $config = [
            'style' => $style,
            'speed' => intval($settings['slider_speed'] ?? 600),
            'easing' => $settings['slider_easing'] ?? 'ease',
            'autoplay' => !empty($settings['slider_autoplay']),
            'delay' => intval($settings['slider_delay'] ?? 3000),
            'loop' => !empty($settings['slider_loop']),
            'arrows' => !empty($settings['slider_arrows']),
            'pagination' => !empty($settings['slider_pagination']),
            'swipe' => !empty($settings['slider_swipe']),
            'keyboard' => !empty($settings['slider_keyboard']),
            'rotation' => intval($settings['slider_rotation'] ?? 45),
            'depth' => intval($settings['slider_depth'] ?? 150),
            'opacity' => floatval($settings['slider_opacity'] ?? 0.6),
            'scale' => floatval($settings['slider_scale'] ?? 0.8),
            'shadow' => floatval($settings['slider_shadow'] ?? 0.3),
            'spacing' => intval($settings['slider_spacing'] ?? 50),
            'center' => !empty($settings['slider_center'])
        ];

        return $config;
    }
}
