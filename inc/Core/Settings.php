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
			// General
			'theme_mode'           => 'light',
			
			// Branding
			'wordmark'             => 'KCharts',
			'logo_id_light'        => '',
			'logo_id_dark'         => '',
			'logo_alt'             => '',
			'show_logo'            => 1,
			
			// Header
			'header_menu_id'       => '0',
			'show_nav'             => 1,
			'show_search'          => 1,
			
			// Footer
			'footer_left'          => '',
			'footer_right'         => '',
			
			// Design System
			'color_primary'        => '#3b82f6',
			'color_bg_light'       => '#ffffff',
			'color_bg_dark'        => '#0f172a',
			'font_heading'         => 'Inter, sans-serif',
			'font_body'            => 'Inter, sans-serif',
			
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
			'slider_show_cta'      => 1,
			'slider_cta_text'      => 'VIEW CHART',
		];
	}

	/**
	 * Main getter with automatic prefixing and fail-safe defaults
	 */
	public static function get( $key, $fallback = null ) {
		$defaults = self::get_defaults();
		$default = $fallback !== null ? $fallback : ($defaults[$key] ?? '');
		
		return get_option( 'charts_' . $key, $default );
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
