<?php
namespace Charts\Core;

/**
 * Settings Helper - Centralized logic for retrieving Theme Options
 */
class Settings {

	/**
	 * Default values for all settings
	 */
	public static function get_defaults() {
		return [
			// Branded Shell options removed
			
			// Design System
			// Design System
			'design_mode'          => 'light', // light, dark, system
			'color_primary'        => '#3b82f6',
			'color_secondary'      => '#6366f1',
			'color_bg_light'       => '#f6f6f6',
			'color_surface_light'  => '#ffffff',
			'color_text_light'     => '#262626',
			'color_bg_dark'        => '#0f0f0f',
			'color_surface_dark'   => '#141414',
			'color_text_dark'      => '#ffffff',
			
			// Typography - English
			'font_en_heading'      => 'Inter, sans-serif',
			'font_en_body'         => 'Inter, sans-serif',
			// Typography - Arabic
			'font_ar_heading'      => 'Inter, sans-serif',
			'font_ar_body'         => 'Inter, sans-serif',
			
			'font_meta'            => 'Inter, sans-serif',
			'custom_fonts_json'    => '[]',
			'custom_fonts_data'    => '[]', // Detailed font objects with weighting
			
			// Homepage
			'homepage_layout'      => 'standard',
			'homepage_show_featured' => 1,
			'homepage_show_artists'  => 1,
			'homepage_show_more'     => 1,
			'homepage_section_order' => 'slider,artists,charts',
			
			// Slider
			'slider_enable'        => 1,
			'slider_source_mode'   => 'dynamic',
			'slider_manual_slides' => '[]',
			'slider_style'         => 'coverflow',
			'slider_radius'        => '16px',
			'slider_overlay'       => 0.5,
			'slider_count'         => 5,
			'slider_autoplay'      => 1,
			'slider_delay'         => 3000,
			'slider_speed'         => 600,
			'slider_depth'         => 150,
			'slider_rotation'      => 45,
			'slider_opacity'       => 0.6,
			'slider_scale'         => 0.8,
			'slider_shadow'        => 0.3,
			'slider_glow'          => 1,
			'slider_max_width'     => '1440px',
			'slider_min_height'    => '500px',
			'slider_aspect_ratio'  => '16/9',
			'slider_align'         => 'center',
			'slider_mobile_mode'   => 'stack',
			'slider_show_label'    => 1,
			'slider_show_meta'     => 1,
			'slider_cta_text'      => 'VIEW CHART',
			
			// Premium Hero Slider (Billboard style)
			'slider_premium_enable'    => 0,
			'slider_premium_slides'    => '[]',
			'slider_premium_height'    => 70,
			'slider_premium_radius'    => 20,
			'slider_premium_overlay'   => 75,
			'slider_premium_alignment' => 'left',
			'slider_premium_autoplay'  => 1,
			'slider_premium_delay'     => 5000,
			'slider_premium_speed'     => 800,
			'slider_premium_loop'      => 1,
			'slider_premium_pause'     => 1,
			'slider_premium_btn_style' => 'pill',
			'slider_premium_font_scale' => 100,
			'slider_premium_mobile_height' => 50,
			'slider_premium_hide_secondary_mobile' => 1,
			'label_breakdown'      => 'More Details',
		];
	}

	/**
	 * Main getter with automatic prefixing and fail-safe defaults
	 */
	public static function get( $key, $fallback = null ) {
		$defaults = self::get_defaults();
		$default = $fallback !== null ? $fallback : ($defaults[$key] ?? '');
		
		$val = get_option( 'charts_' . $key, $default );

		// Specific handling for legacy font_heading/font_body mapping to English versions if not set
		if ( ($key === 'font_en_heading' || $key === 'font_heading') && empty(get_option('charts_font_en_heading')) ) {
			return get_option('charts_font_heading', $default);
		}
		if ( ($key === 'font_en_body' || $key === 'font_body') && empty(get_option('charts_font_en_body')) ) {
			return get_option('charts_font_body', $default);
		}

		return $val;
	}

	/**
	 * Specifically retrieve the active logo based on theme mode
	 */
	public static function get_active_logo_id() {
		$mode = self::get('theme_mode');
		$dark = self::get('logo_id_dark');
		$light = self::get('logo_id_light');
		
		if ( $mode === 'dark' ) {
			return $dark ?: $light;
		}
		return $light ?: $dark;
	}
}
