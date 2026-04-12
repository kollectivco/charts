<?php
namespace Charts\Core;

/**
 * Settings Architecture - Production-Grade Configuration Engine.
 * Handles storage, migration, validation, and retrieval with a canonical nested model.
 */
class Settings {

    /**
     * Canonical option key for all plugin settings.
     * All settings are stored as a single structured array in this option.
     */
    private static $option_key = 'kcharts_settings';
    
    /**
     * Static cache for performance.
     */
    private static $cache = null;

    /**
     * Define the complete settings schema and default values.
     * Organize by logic domain for clarity.
     */
    public static function get_defaults() {
        return [
            // Homepage Presence
            'homepage' => [
                'layout'               => 'standard', // standard, minimal
                'show_featured_row'    => 1,
                'show_artists_row'     => 1,
                'show_charts_grid'     => 1,
                'section_order'        => 'slider,artists,charts',
                'padding_top'          => 40,
                'section_spacing'      => 80,
            ],
            
            // Hero Billboard (Premium Slider)
            'slider' => [
                'enable'               => 1,
                'source_mode'          => 'latest', // latest, selected, selection_top
                'selected_charts'      => [],
                'selected_artists'     => [],
                'selected_tracks'      => [],
                'manual_slides_json'   => '[]',
                'height_vh'            => 60,
                'mobile_height_vh'     => 50,
                'max_width_px'         => 1400,
                'border_radius_px'     => 28,
                'overlay_opacity_pct'  => 75,
                'alignment'            => 'left',
                'autoplay'             => 1,
                'delay_ms'             => 5000,
                'speed_ms'             => 800,
                'loop'                 => 1,
                'pause_on_hover'       => 1,
            ],
            
            // Design System (Colors & Aesthetics)
            'design' => [
                'mode'                 => 'light', // light, dark, system
                'primary_color'        => '#fe025b',
                'accent_color'         => '#5d00fe',
                'bg_color_light'       => '#f6f6f6',
                'surface_color_light'  => '#ffffff',
                'text_color_light'     => '#262626',
                'bg_color_dark'        => '#0f0f0f',
                'surface_color_dark'   => '#141414',
                'text_color_dark'      => '#ffffff',
                'card_radius_px'       => 24,
            ],
            
            // External Integrations
            'api' => [
                'spotify_client_id'    => '',
                'spotify_client_secret' => '',
                'youtube_api_key'      => '',
            ],
            
            // Labels & Localization
            'labels' => [
                'chart_cta_text'       => 'View Full Chart',
                'trending_artist_tag'  => 'Trending Artist',
                'top_artists_title'    => 'Top Artists',
                'all_charts_title'     => 'All Charts',
                'footer_wordmark'      => 'KCharts',
                'footer_left'          => '&copy; ' . date('Y') . ' Kontentainment. All rights reserved.',
                'footer_right'         => 'Powered by Kontentainment Intelligence',
                'header_wordmark'      => 'Kontentainment',
            ],

            // Branding & Navigation
            'branding' => [
                'logo_id'              => 0,
                'logo_alt'             => '',
                'show_logo'            => 1,
                'show_nav'             => 1,
                'show_search'          => 1,
                'header_menu_id'       => 0,
            ],

            // Performance & Advanced
            'advanced' => [
                'cache_previews'       => 1,
                'enable_debug_logs'    => 0,
                'github_access_token'  => '', // Added for authorized update checks
            ]
        ];
    }

    /**
     * Helper to get the active logo ID.
     */
    public static function get_active_logo_id() {
        return self::get('branding.logo_id', 0);
    }

    /**
     * Retrieve all settings, merging defaults with saved options.
     */
    public static function get_all() {
        if ( self::$cache !== null ) return self::$cache;

        $saved = get_option( self::$option_key, [] );
        if ( ! is_array( $saved ) ) $saved = [];

        $defaults = self::get_defaults();
        
        // Deep merge to ensure nested keys are preserved
        self::$cache = self::deep_merge( $defaults, $saved );
        
        // Handle migration once if new option is empty but old exists
        if ( empty($saved) ) {
            if ( get_option('kcharts_settings_v2') ) {
                $saved = get_option('kcharts_settings_v2', []);
                update_option(self::$option_key, $saved);
                self::$cache = self::deep_merge($defaults, $saved);
            } elseif ( get_option('kcharts_theme_options') ) {
                self::migrate_legacy_settings();
            }
        }

        return self::$cache;
    }

    /**
     * Main getter with dot notation support.
     * Example: Settings::get('slider.height_vh')
     */
    public static function get( $path, $fallback = null ) {
        $settings = self::get_all();
        $keys = explode( '.', $path );

        foreach ( $keys as $key ) {
            if ( isset( $settings[ $key ] ) ) {
                $settings = $settings[ $key ];
            } else {
                return $fallback ?? self::get_default_val($path);
            }
        }

        return $settings;
    }

    /**
     * Update the settings option.
     */
    public static function update_all( $new_settings ) {
        $clean_settings = self::sanitize_settings( $new_settings );
        update_option( self::$option_key, $clean_settings );
        self::$cache = $clean_settings;
    }

    /**
     * Update a single section or key.
     */
    public static function update( $key, $val ) {
        $all = self::get_all();
        $all[$key] = $val;
        self::update_all($all);
    }

    /**
     * Helper to get a default value for a Dot Path.
     */
    private static function get_default_val($path) {
        $defaults = self::get_defaults();
        $keys = explode( '.', $path );
        foreach ( $keys as $key ) {
            if ( isset( $defaults[ $key ] ) ) {
                $defaults = $defaults[ $key ];
            } else {
                return '';
            }
        }
        return $defaults;
    }

    /**
     * Recursive merge to protect structure.
     */
    private static function deep_merge( $defaults, $saved ) {
        $merged = $defaults;
        foreach ( $saved as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = self::deep_merge( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }
        return $merged;
    }

    /**
     * Sanitize settings based on expected types.
     */
    private static function sanitize_settings( $data ) {
        $clean = [];
        $defaults = self::get_defaults();

        foreach ( $defaults as $section => $fields ) {
            if ( ! isset( $data[$section] ) ) {
                $clean[$section] = $fields;
                continue;
            }

            foreach ( $fields as $key => $default ) {
                $val = $data[$section][$key] ?? $default;

                if ( is_int( $default ) ) {
                    $clean[$section][$key] = intval( $val );
                } elseif ( is_array( $default ) ) {
                    $clean[$section][$key] = (array) $val;
                } elseif ( $key === 'manual_slides_json' ) {
                    $clean[$section][$key] = wp_kses_post( wp_unslash( $val ) );
                } else {
                    $clean[$section][$key] = sanitize_text_field( $val );
                }
            }
        }
        return $clean;
    }

    /**
     * One-time migration from broken legacy keys.
     */
    private static function migrate_legacy_settings() {
        $legacy = get_option('kcharts_theme_options', []);
        if ( ! is_array($legacy) ) return;

        $new = self::get_defaults();

        // Mapping map [Legacy Key] => [New Path]
        $map = [
            'homepage_layout'       => 'homepage.layout',
            'homepage_show_artists'  => 'homepage.show_artists_row',
            'slider_premium_enable'  => 'slider.enable',
            'slider_premium_height'  => 'slider.height_vh',
            'color_primary'          => 'design.primary_color',
            'color_bg_dark'          => 'design.bg_color_dark',
            'spotify_client_id'      => 'api.spotify_client_id',
            'label_breakdown'        => 'labels.chart_cta_text',
        ];

        foreach ( $map as $old_key => $new_path ) {
            if ( isset($legacy[$old_key]) ) {
                $keys = explode('.', $new_path);
                $new[$keys[0]][$keys[1]] = $legacy[$old_key];
            }
        }

        self::update_all($new);
    }
}
