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
			
			// Layout
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
			'slider_premium_enable'    => 1,
			'slider_premium_source'    => 'latest', // latest, selected, selection_top
			'slider_premium_charts'    => [],
			'slider_premium_slides'    => '[]',
			'slider_premium_height'    => 60,
			'slider_premium_width'     => 1400,
			'slider_premium_radius'    => 28,
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
			'label_breakdown'      => 'View Chart',
		];
	}

	/**
	 * Canonical option key for all theme settings
	 */
	private static $option_name = 'kcharts_theme_options';

	/**
	 * Cached settings data to prevent multiple get_option calls
	 */
	private static $cache = null;

	/**
	 * Main getter with dot notation support and fail-safe defaults
	 */
	public static function get( $key, $fallback = null ) {
		if ( self::$cache === null ) {
			self::$cache = get_option( self::$option_name, [] );
			if ( ! is_array( self::$cache ) ) {
				self::$cache = json_decode( self::$cache, true ) ?: [];
			}
		}

		$defaults = self::get_defaults();
		
		// If key not in cache, try defaults. If not in defaults, use fallback.
		if ( !isset(self::$cache[$key]) ) {
			return $fallback !== null ? $fallback : ($defaults[$key] ?? '');
		}

		return self::$cache[$key];
	}

	/**
	 * Update the unified settings array
	 */
	public static function update( $new_data ) {
		$current = get_option( self::$option_name, [] );
		if ( ! is_array( $current ) ) {
			$current = json_decode( $current, true ) ?: [];
		}

		$updated = array_merge( $current, $new_data );
		update_option( self::$option_name, $updated );
		self::$cache = $updated;
	}

	/**
	 * Specifically retrieve the active logo based on theme mode
	 */
	public static function get_active_logo_id() {
		return self::get('logo_id');
	}
}
