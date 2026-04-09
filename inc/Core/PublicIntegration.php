<?php
namespace Charts\Core;

/**
 * Handle native theme integration and public UI refinements.
 */
class PublicIntegration {

	/**
	 * Initialize theme integration.
	 */
	public static function init() {
		// Add body classes for charts routes
		add_filter( 'body_class', array( self::class, 'add_body_classes' ) );

		// Inject design tokens into head
		add_action( 'wp_head', array( self::class, 'render_design_tokens' ), 100 );
	}

	/**
	 * Add specific body classes to aid styling within themes.
	 */
	public static function add_body_classes( $classes ) {
		if ( self::is_charts_page() ) {
			$classes[] = 'kc-charts-route';
		}
		return $classes;
	}

	// Sidebars removed

	/**
	 * Check if the current page should be handled by the plugin's public logic.
	 */
	public static function is_charts_page() {
		if ( ! is_main_query() ) return false;
		
		// If we are on a dashboard route, keep it isolated
		if ( get_query_var( 'charts_route' ) === 'dashboard' ) return false;

		$vars = array(
			'charts_route',
			'charts_module',
			'charts_page',
			'charts_platform',
			'charts_country',
			'charts_frequency',
			'charts_type',
			'charts_artist_slug',
			'charts_item_slug',
			'charts_item_type',
			'charts_definition_id',
			'charts_definition_slug'
		);

		foreach ( $vars as $v ) {
			if ( get_query_var( $v ) ) {
				return true;
			}
		}

		$path = trim( $_SERVER['REQUEST_URI'], '/' );
		if ( $path === 'charts' || strpos( $path, 'charts/' ) === 0 ) {
            return true;
        }

		return false;
	}

	public static function get_header() {
		get_header();
	}

	/**
	 * Inject dynamic CSS variables into the head.
	 */
	public static function render_design_tokens() {
		if ( ! self::is_charts_page() ) return;

		$mode = Settings::get('design.mode', 'light');
		
		$variables = [
			'--k-primary'            => Settings::get('design.primary_color'),
			'--k-secondary'          => Settings::get('design.accent_color'),
			'--k-radius-md'          => Settings::get('design.card_radius_px', 24) . 'px',
			'--kb-height'            => Settings::get('slider.height_vh', 60) . 'vh',
			'--kb-mobile-height'     => Settings::get('slider.mobile_height_vh', 50) . 'vh',
			'--kb-radius'            => Settings::get('slider.border_radius_px', 28) . 'px',
			'--kb-overlay-opacity'   => (Settings::get('slider.overlay_opacity_pct', 80) / 100),
		];

		// Mode-specific Surface/Text
		if ( $mode === 'dark' ) {
			$variables['--k-bg']      = Settings::get('design.bg_color_dark', '#0f0f0f');
			$variables['--k-surface'] = Settings::get('design.surface_color_dark', '#141414');
			$variables['--k-text']    = Settings::get('design.text_color_dark', '#ffffff');
		} else {
			$variables['--k-bg']      = Settings::get('design.bg_color_light', '#f6f6f6');
			$variables['--k-surface'] = Settings::get('design.surface_color_light', '#ffffff');
			$variables['--k-text']    = Settings::get('design.text_color_light', '#262626');
		}

		echo '<style id="kc-design-tokens">';
		
		// 1. Intelligent Typography System
		echo \Charts\Core\Typography::get_font_face_css();

		echo ':root {';
		foreach ( $variables as $key => $val ) {
			if ( ! empty($val) ) {
				echo esc_html($key) . ': ' . esc_attr($val) . ';';
			}
		}
		echo '}';
		
		echo '.kc-charts-route {';
		echo 'font-family: var(--k-font-en);';
		echo 'background: var(--k-bg) !important;';
		echo 'color: var(--k-text) !important;';
		echo '}';
		
        // Arabic Context Specific Overrides
        echo '.kc-charts-route .is-arabic { font-family: var(--k-font-ar) !important; }';

		if ( $mode === 'system' ) {
			echo '@media (prefers-color-scheme: dark) {';
			echo ':root {';
			echo '--k-bg-override: ' . esc_attr(Settings::get('design.bg_color_dark')) . ';';
			echo '--k-surface-override: ' . esc_attr(Settings::get('design.surface_color_dark')) . ';';
			echo '--k-text-override: ' . esc_attr(Settings::get('design.text_color_dark')) . ';';
			echo '}';
			echo '}';
		}

		echo '</style>';
	}

	public static function get_footer() {
		// Use theme's native footer
		get_footer();
	}

	/**
	 * Centralized resolver for track/video/artist artwork.
	 * Priorities: Enriched Canonical > Entry-level Metadata > Source-specific Thumbs > Placeholder
	 */
	public static function resolve_artwork( $item, $type = 'track' ) {
		global $wpdb;

		// 1. If we have a numeric ID, it's a SQL ID now. Detect type and fetch.
		if ( is_numeric( $item ) ) {
			$table = ( $type === 'artist' ) ? 'artists' : ( ( $type === 'video' ) ? 'videos' : 'tracks' );
			$col   = ( $type === 'artist' ) ? 'image' : ( ( $type === 'video' ) ? 'thumbnail' : 'cover_image' );
			$img = $wpdb->get_var( $wpdb->prepare( "SELECT $col FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $item ) );
			if ( ! empty( $img ) ) return $img;
		}

		// 2. Fallback to legacy object properties
		if ( isset( $item->resolved_image ) && ! empty( $item->resolved_image ) ) return $item->resolved_image;
		if ( isset( $item->cover_image ) && ! empty( $item->cover_image ) ) return $item->cover_image;
		if ( isset( $item->image ) && ! empty( $item->image ) ) return $item->image;

		// 3. Final JSON fallback if it exists
		if ( isset($item->metadata_json) ) {
			$meta = json_decode($item->metadata_json, true);
			if ( !empty($meta['youtube_thumbnail']) ) return $meta['youtube_thumbnail'];
			if ( !empty($meta['image']) ) return $meta['image'];
		}

		return CHARTS_URL . 'public/assets/img/placeholder.png';
	}
}
