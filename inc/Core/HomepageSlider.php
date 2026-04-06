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

    public static function get_premium_settings() {
        return [
            'enable'      => Settings::get('slider_premium_enable'),
            'slides'      => Settings::get('slider_premium_slides'),
            'height'      => Settings::get('slider_premium_height'),
            'radius'      => Settings::get('slider_premium_radius'),
            'overlay'     => Settings::get('slider_premium_overlay'),
            'autoplay'    => Settings::get('slider_premium_autoplay'),
            'delay'       => Settings::get('slider_premium_delay'),
            'speed'       => Settings::get('slider_premium_speed'),
            'pause'       => Settings::get('slider_premium_pause'),
            'mobile_height' => Settings::get('slider_premium_mobile_height'),
            'width'       => Settings::get('slider_premium_width'),
        ];
    }

    /**
     * Get slide data based on the chosen source mode and chart selection.
     */
    public static function get_slides_data($chart_ids = [], $count = 5) {
        $source_mode = Settings::get('slider_premium_source', 'latest');
        $selected_charts = (array)Settings::get('slider_premium_charts', []);
        
        // Contextually resolve chart IDs
        if ( $source_mode === 'latest' ) {
            $manager = new \Charts\Admin\SourceManager();
            $defs = $manager->get_definitions( true );
            $chart_ids = array_map( function($d){ return $d->id; }, $defs );
        } else {
            $chart_ids = $selected_charts;
        }

        if ( empty($chart_ids) ) return [];

        $slides = [];
        $manager = new \Charts\Admin\SourceManager();
        global $wpdb;

        foreach ( array_slice((array)$chart_ids, 0, $count) as $id ) {
            $def = $manager->get_definition( $id );
            if ( ! $def ) continue;

            // Mode: Top Item From Each Chart
            if ( $source_mode === 'selection_top' || $source_mode === 'latest' ) {
                $row = $wpdb->get_row( $wpdb->prepare( "
                    SELECT e.*, COALESCE(NULLIF(e.cover_image, ''), t.cover_image, v.thumbnail, a.image) as resolved_thumb 
                    FROM {$wpdb->prefix}charts_entries e
                    JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
                    LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
                    LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
                    LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
                    WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
                    ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
                ", $def->chart_type, $def->country_code ) );

                $slides[] = [
                    'id'            => $def->id,
                    'title'         => $row->track_name ?? $def->title,
                    'desc'          => $row->artist_names ?? $def->chart_summary,
                    'badge'         => '#1 Trending',
                    'image_url'     => $row->resolved_thumb ?? $def->cover_image_url ?? CHARTS_URL . 'public/assets/img/placeholder.png',
                    'btn1_text'     => Settings::get('label_breakdown', 'View Chart'),
                    'btn1_link'     => home_url('/charts/' . $def->slug . '/'),
                    'accent'        => $def->accent_color ?: '#fe025b'
                ];
            } 
            // Mode: Selected Charts Only (The charts themselves)
            else {
                $slides[] = [
                    'id'            => $def->id,
                    'title'         => $def->title,
                    'desc'          => $def->chart_summary,
                    'badge'         => 'Selection',
                    'image_url'     => $def->cover_image_url ?? CHARTS_URL . 'public/assets/img/placeholder.png',
                    'btn1_text'     => 'View Chart',
                    'btn1_link'     => home_url('/charts/' . $def->slug . '/'),
                    'accent'        => $def->accent_color ?: '#fe025b'
                ];
            }
        }

        // --- MANUAL SLIDES OVERLAY (Legacy/Manual Support) ---
        $manual_json = Settings::get('slider_premium_slides');
        $manual_data = json_decode($manual_json, true);
        if ( !empty($manual_data) && is_array($manual_data) ) {
            foreach ( $manual_data as $m ) {
                $slides[] = [
                    'title'         => $m['title'] ?? 'Featured',
                    'desc'          => $m['subtitle'] ?? '',
                    'badge'         => 'Featured',
                    'image_url'     => !empty($m['image']) ? (is_numeric($m['image']) ? wp_get_attachment_image_url($m['image'], 'full') : $m['image']) : CHARTS_URL . 'public/assets/img/placeholder.png',
                    'btn1_text'     => 'View More',
                    'btn1_link'     => $m['url'] ?? '#',
                    'accent'        => $m['accent'] ?? '#fe025b'
                ];
            }
        }

        return $slides;
    }

    public static function get_premium_slides_data() {
        $slides_json = Settings::get('slider_premium_slides');
        $slides = json_decode($slides_json, true) ?: [];
        
        foreach ( $slides as &$slide ) {
            if ( !empty($slide['image']) && is_numeric($slide['image']) ) {
                $slide['image_url'] = wp_get_attachment_image_url($slide['image'], 'full');
            } else {
                $slide['image_url'] = !empty($slide['image']) ? $slide['image'] : CHARTS_URL . 'public/assets/img/placeholder.png';
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

    public static function render_premium($slides, $settings) {
        if ( empty($slides) ) return;

        $height = esc_attr($settings['height'] . 'vh');
        $mobile_height = esc_attr($settings['mobile_height'] . 'vh');
        $radius = esc_attr($settings['radius'] . 'px');
        $alignment_class = $settings['alignment'] === 'center' ? 'kc-bb-centered' : '';
        $btn_class = 'kb-' . $settings['btn_style'];
        $overlay_op = floatval($settings['overlay'] / 100);

        echo '<section class="kc-billboard-slider '.esc_attr($alignment_class).'" style="--kb-height: '.esc_attr($settings['height']).'; --kb-mobile-height: '.esc_attr($settings['mobile_height']).'; --kb-radius: '.esc_attr($radius).'; display:block;">';
        echo '<div class="kc-billboard-wrapper">';
        
        foreach ( $slides as $index => $slide ) {
            $active = $index === 0 ? 'is-active' : '';
            echo '<div class="kc-billboard-slide '.esc_attr($active).'" data-index="'.esc_attr($index).'">';
            echo '<img src="'.esc_url($slide['image_url']).'" class="kc-bb-bg" alt="">';
            echo '<div class="kc-bb-overlay" style="background: linear-gradient('.($settings['alignment'] === 'center' ? '0deg' : '90deg').', rgba(0,0,0,'.esc_attr($overlay_op).') 0%, rgba(0,0,0,'.esc_attr($overlay_op * 0.5).') 60%, rgba(0,0,0,0) 100%);"></div>';
            echo '<div class="kc-bb-content">';
            if ( !empty($slide['badge']) ) echo '<span class="kc-bb-badge">'.esc_html($slide['badge']).'</span>';
            echo '<h2 class="kc-bb-title">'.esc_html($slide['title']).'</h2>';
            if ( !empty($slide['desc']) ) echo '<p class="kc-bb-desc">'.esc_html($slide['desc']).'</p>';
            echo '<div class="kc-bb-actions">';
            if ( !empty($slide['btn1_text']) ) echo '<a href="'.esc_url($slide['btn1_link']).'" class="kc-btn-p '.esc_attr($btn_class).'"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> '.esc_html($slide['btn1_text']).'</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        echo '<div class="kc-bb-nav kc-bb-prev" onclick="this.closest(\'.kc-billboard-slider\').ChartsBillboard.prev()"><span class="dashicons dashicons-arrow-left-alt2"></span></div>';
        echo '<div class="kc-bb-nav kc-bb-next" onclick="this.closest(\'.kc-billboard-slider\').ChartsBillboard.next()"><span class="dashicons dashicons-arrow-right-alt2"></span></div>';
        
        echo '<div class="kc-bb-dots">';
        foreach ( $slides as $index => $slide ) {
            $active = $index === 0 ? 'is-active' : '';
            echo '<div class="kc-bb-dot '.esc_attr($active).'" data-index="'.esc_attr($index).'" onclick="this.closest(\'.kc-billboard-slider\').ChartsBillboard.goTo('.$index.')"></div>';
        }
        echo '</div>';

        echo '</div>';
        
        $config = json_encode([
            'autoplay' => (bool)$settings['autoplay'],
            'delay'    => intval($settings['delay']),
            'speed'    => intval($settings['speed']),
            'loop'     => (bool)$settings['loop'],
            'pause'    => (bool)$settings['pause']
        ]);
        echo '<script>document.addEventListener("DOMContentLoaded", () => { const el = document.querySelector(".kc-billboard-slider"); if(el) el.ChartsBillboard = new BillboardEngine(el, '.$config.'); });</script>';
        echo '</section>';
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
