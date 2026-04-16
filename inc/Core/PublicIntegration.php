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
		$route = get_query_var( 'charts_route' );
		if ( $route ) {
			$classes[] = 'kc-charts-route';
			$classes[] = 'kc-route-' . $route;
		}

		if ( is_singular( 'artist' ) || is_singular( 'chart' ) || is_singular( 'track' ) || is_singular( 'video' ) ) {
			$classes[] = 'kc-charts-route';
			$classes[] = 'kc-route-singular';
			$classes[] = 'kc-route-' . get_post_type() . '-single';
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
	 * Centralized resolver for entity/entry names with Franco support.
	 */
	public static function resolve_display_name( $obj, $definition = null ) {
		$mode = $definition ? ($definition->franco_mode ?? 'original') : Settings::get('design.franco_mode', 'original');
		
		// 1. If it's a Chart Entry object (has track_name, artist_names)
		if ( isset($obj->track_name) || isset($obj->artist_names) ) {
			$resolved = Transliteration::resolve_entry_display($obj, $mode);
			return [
				'title'    => $resolved['track'],
				'subtitle' => $resolved['artist']
			];
		}

		// 2. If it's an Artist entity object
		if ( isset($obj->display_name) ) {
			$english = $obj->display_name_en ?? ($obj->display_name_franco_manual ?? ($obj->display_name_franco_auto ?? ''));
			return [
				'title'    => Transliteration::resolve_display($obj->display_name, $english, $mode),
				'subtitle' => ''
			];
		}

		// 3. If it's a Track entity object
		if ( isset($obj->title) ) {
			$english = $obj->title_en ?? ($obj->title_franco_manual ?? ($obj->title_franco_auto ?? ''));
			return [
				'title'    => Transliteration::resolve_display($obj->title, $english, $mode),
				'subtitle' => ''
			];
		}

		return ['title' => '', 'subtitle' => ''];
	}

	/**
	 * Centralized resolver for track/video/artist artwork.
	 * Priorities: Enriched Canonical > Entry-level Metadata > Source-specific Thumbs > Placeholder
	 */
	public static function resolve_artwork( $item, $type = 'track' ) {
		global $wpdb;

		// Utility to detect and skip placeholders
		$is_placeholder = function($url) {
			if ( empty($url) ) return true;
			if ( strpos($url, 'placeholder.png') !== false ) return true;
			if ( $url === 'Placeholder' || $url === 'N/A' ) return true;
			return false;
		};

		// 1. Relational Lookup (If numeric ID OR entry object with item_id)
		$target_id   = is_numeric( $item ) ? $item : ( isset($item->item_id) ? $item->item_id : null );
		$target_type = is_numeric( $item ) ? $type : ( isset($item->item_type) ? $item->item_type : $type );

		if ( ! empty($target_id) ) {
			$table = ( $target_type === 'artist' ) ? 'artists' : ( ( $target_type === 'video' ) ? 'videos' : 'tracks' );
			$col   = ( $target_type === 'artist' ) ? 'image' : ( ( $target_type === 'video' ) ? 'thumbnail' : 'cover_image' );
			$img = $wpdb->get_var( $wpdb->prepare( "SELECT $col FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $target_id ) );
			if ( ! $is_placeholder($img) ) return $img;
		}

		// 2. Fallback to legacy object properties
		if ( isset( $item->resolved_image ) && ! $is_placeholder($item->resolved_image) ) return $item->resolved_image;
		if ( isset( $item->cover_image ) && ! $is_placeholder($item->cover_image) ) return $item->cover_image;
		if ( isset( $item->image ) && ! $is_placeholder($item->image) ) return $item->image;

		// 3. Final JSON fallback if it exists
		if ( isset($item->metadata_json) && ! empty($item->metadata_json) ) {
			$meta = ! is_array($item->metadata_json) ? json_decode($item->metadata_json, true) : $item->metadata_json;
			if ( ! empty($meta) ) {
				if ( ! empty($meta['spotify_image']) && ! $is_placeholder($meta['spotify_image']) ) return $meta['spotify_image'];
				if ( ! empty($meta['youtube_thumbnail']) && ! $is_placeholder($meta['youtube_thumbnail']) ) return $meta['youtube_thumbnail'];
				if ( ! empty($meta['image']) && ! $is_placeholder($meta['image']) ) return $meta['image'];
				if ( ! empty($meta['artwork_url']) && ! $is_placeholder($meta['artwork_url']) ) return $meta['artwork_url'];
				if ( ! empty($meta['cover_url']) && ! $is_placeholder($meta['cover_url']) ) return $meta['cover_url'];
			}
		}

		return CHARTS_URL . 'public/assets/img/placeholder.png';
	}

	/**
	 * Centralized resolver for chart card images.
	 * Priorities: Chart-level cover > First item artwork > Placeholder
	 */
	public static function resolve_chart_image( $def, $entries = array() ) {
		// 1. Convert to unified object if array
		$obj = (object) $def;

		// 2. Chart-level explicit cover (check both naming conventions)
		if ( ! empty( $obj->cover_image_url ) ) {
			return $obj->cover_image_url;
		}
		if ( ! empty( $obj->cover_image ) ) {
			return $obj->cover_image;
		}

		// 3. Auto-fetch entries if missing
		if ( empty($entries) ) {
			$entries = self::get_preview_entries($obj, 1);
		}

		// 4. First preview entry's artwork
		if ( ! empty( $entries ) ) {
			$first = is_array( $entries ) ? $entries[0] : null;
			if ( $first ) {
				return self::resolve_artwork( $first, $first->item_type ?? 'track' );
			}
		}

		return CHARTS_URL . 'public/assets/img/placeholder.png';
	}

	/**
	 * Centralized resolver to fetch all sources belonging to a specific chart definition.
	 * Enforces the strict 'cid-{id}' identity lock.
	 */
	public static function get_sources_for_chart( $definition ) {
		global $wpdb;
		if ( empty($definition) || empty($definition->id) ) return array();

		// Use ONLY the strict Profile ID binding ('cid-').
		$sources = $wpdb->get_results( $wpdb->prepare( "
			SELECT * FROM {$wpdb->prefix}charts_sources 
			WHERE chart_type = %s AND is_active = 1
		", "cid-{$definition->id}" ) );

		return (array) $sources;
	}

	/**
	 * Fetch the latest N preview entries for a chart.
	 * Matches the logic in single-chart.php to ensure consistency.
	 */
	public static function get_preview_entries( $definition, $limit = 4 ) {
		global $wpdb;
		if ( empty($definition) ) return array();

		$sources = self::get_sources_for_chart( $definition );
		if ( empty($sources) ) return array();

		$source_ids = array_column( $sources, 'id' );
		$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

		// 1. Identify the LATEST period for these sources
		$period_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT p.id FROM {$wpdb->prefix}charts_periods p
			JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
			WHERE e.source_id IN ($placeholders)
			ORDER BY p.period_start DESC LIMIT 1
		", ...$source_ids ) );

		if ( ! $period_id ) return array();

		// 2. Resolve Max Depth (Pipeline depth removed, defaulting to safe high limit)
		$final_limit = ($limit > 0) ? $limit : 500;

		// 2. Fetch deduped entries for this period
		$query_params = array_values( $source_ids );
		$query_params[] = $period_id;
		$query_params[] = $final_limit;

		$entries = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* 
			FROM {$wpdb->prefix}charts_entries e
			INNER JOIN (
				SELECT MAX(id) as max_id, rank_position
				FROM {$wpdb->prefix}charts_entries
				WHERE source_id IN ($placeholders) AND period_id = %d
				GROUP BY rank_position
			) dedup ON dedup.max_id = e.id
			ORDER BY e.rank_position ASC LIMIT %d
		", ...$query_params ) );

		// 3. Resolve images and typography flags
		foreach ( $entries as &$e ) {
			$e->resolved_image = self::resolve_artwork( $e, $e->item_type );
			
			// Healing: Resolve missing slugs or titles
			if ( empty($e->item_slug) || $e->item_slug === 'unknown-youtube-item' ) {
				$table = ( $e->item_type === 'artist' ) ? 'artists' : ( ( $e->item_type === 'video' ) ? 'videos' : 'tracks' );
				$e->item_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $e->item_id ) );
			}
		}

		return $entries;
	}

	/**
	 * Fetch ONLY definitions that are public AND have real entry data.
	 * Excludes uninitialized or empty charts.
	 */
	public static function get_eligible_definitions( $limit = 4 ) {
		global $wpdb;
		
		$query = $wpdb->prepare( "
			SELECT d.* FROM {$wpdb->prefix}charts_definitions d
			WHERE d.is_public = 1
			AND EXISTS (
				SELECT 1 FROM {$wpdb->prefix}charts_sources s
				JOIN {$wpdb->prefix}charts_entries e ON e.source_id = s.id
				WHERE s.chart_type = CONCAT('cid-', d.id)
				LIMIT 1
			)
			ORDER BY d.id DESC
			LIMIT %d
		", $limit );

		$results = $wpdb->get_results( $query );
		$manager = new \Charts\Admin\SourceManager();
		$definitions = array();

		foreach ( $results as $row ) {
			// Resolve the "Rich" definition (handles CPT promotion and metadata)
			$definitions[] = $manager->get_definition( $row->id );
		}

		return $definitions;
	}
}
