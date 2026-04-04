<?php
namespace Charts\Core;

class HomepageSlider {

    public static function get_global_settings() {
        return [
            'slider_enable'      => Settings::get('slider_enable'),
            'slider_style'       => Settings::get('slider_style'),
            'slider_count'       => Settings::get('slider_count'),
            'slider_autoplay'    => Settings::get('slider_autoplay'),
            'slider_delay'       => Settings::get('slider_delay'),
            'slider_speed'       => Settings::get('slider_speed'),
            'slider_depth'       => Settings::get('slider_depth'),
            'slider_rotation'    => Settings::get('slider_rotation'),
            'slider_opacity'     => Settings::get('slider_opacity'),
            'slider_scale'       => Settings::get('slider_scale'),
            'slider_shadow'      => Settings::get('slider_shadow'),
            'slider_glow'        => Settings::get('slider_glow'),
            'slider_max_width'   => Settings::get('slider_max_width'),
            'slider_min_height'  => Settings::get('slider_min_height'),
            'slider_aspect_ratio'=> Settings::get('slider_aspect_ratio'),
            'slider_align'       => Settings::get('slider_align'),
            'slider_overlay'     => Settings::get('slider_overlay'),
            'slider_radius'      => Settings::get('slider_radius'),
            'slider_mobile_mode' => Settings::get('slider_mobile_mode'),
            'slider_show_label'  => Settings::get('slider_show_label'),
            'slider_show_meta'   => Settings::get('slider_show_meta'),
            'slider_show_cta'    => Settings::get('slider_show_cta'),
            'slider_cta_text'    => Settings::get('slider_cta_text'),
            
            // Fixed mappings for logic flags
            'slider_loop'        => Settings::get('slider_autoplay'), // Tied to autoplay for simplicity in UI
            'slider_arrows'      => 1,
            'slider_pagination'  => 1,
            'slider_swipe'       => 1,
            'slider_keyboard'    => 1,
            'slider_center'      => 1,
            'slider_spacing'     => 50,
            'slider_easing'      => 'cubic-bezier(0.25, 1, 0.5, 1)',
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
        $source_mode = Settings::get('slider_source_mode');
        if ( $source_mode === 'manual' ) {
            $manual_json = Settings::get('slider_manual_slides');
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
