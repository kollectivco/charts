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

		// Render Floating Popup
		add_action( 'wp_footer', array( self::class, 'render_floating_popup' ) );
	}

	/**
	 * Add specific body classes to aid styling within themes.
	 */
	public static function add_body_classes( $classes ) {
		$route     = get_query_var( 'charts_route' );
		$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);

		if ( $route ) {
			$classes[] = 'kc-charts-route';
			$classes[] = 'kc-route-' . $route;
		}

		if ( $is_mobile ) {
			$classes[] = 'kc-mode-mobile';
		} else {
			$classes[] = 'kc-mode-desktop';
		}

		if ( is_singular( ['artist', 'chart', 'track', 'video'] ) ) {
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
		$primary = Settings::get('design.primary_color');
		$secondary = Settings::get('design.accent_color');

		// Override with Chart-Specific Brand Accent Color if on a single chart
		$def_slug = get_query_var( 'charts_definition_slug' );
		if ( $def_slug ) {
			$manager = new \Charts\Admin\SourceManager();
			$def = $manager->get_definition_by_slug( $def_slug );
			if ( $def && !empty($def->accent_color) ) {
				$primary = $def->accent_color;
				$secondary = $def->accent_color;
			}
		}

		$variables = [
			'--k-primary'            => $primary,
			'--k-secondary'          => $secondary,
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

	/**
	 * Render the Floating Popup (Bottom Right)
	 */
	public static function render_floating_popup() {
		if ( ! Settings::get('popup.enable') ) return;

		$rules = Settings::get('popup.display_rules', 'all');
		if ( $rules === 'charts_only' && ! self::is_charts_page() ) return;

		$slug = Settings::get('popup.source_chart_slug');
		$manager = new \Charts\Admin\SourceManager();
		$def = null;

		if ( ! empty($slug) ) {
			$def = $manager->get_definition_by_slug($slug);
		} else {
			$current_slug = get_query_var('charts_definition_slug');
			if ( $current_slug ) {
				$def = $manager->get_definition_by_slug($current_slug);
			} else {
				$eligible = self::get_eligible_definitions(1);
				if ( ! empty($eligible) ) {
					$def = $eligible[0];
				}
			}
		}

		if ( ! $def ) return;

		$entries = self::get_preview_entries($def, 1);
		if ( empty($entries) ) return;
		$top = $entries[0];

		$franco_mode = $def->franco_mode ?? 'original';
		$resolved = Transliteration::resolve_entry_display($top, $franco_mode);
		$title = $resolved['track'];
		$subtitle = $resolved['artist'];

		$image_pref = Settings::get('popup.image_source', 'item');
		$image = '';
		if ( $image_pref === 'chart' && ! empty($def->cover_image) ) {
			$image = $def->cover_image;
		} else {
			$image = self::resolve_artwork($top, $top->item_type);
		}
		if ( empty($image) ) $image = CHARTS_URL . 'public/assets/img/placeholder.png';

		$delay_ms = intval(Settings::get('popup.delay_ms', 3000));
		$show_close = Settings::get('popup.show_close', 1);
		$cta_text = Settings::get('popup.cta_text', 'View Full Chart');
		$chart_url = home_url('/charts/' . $def->slug);
		$accent = !empty($def->accent_color) ? $def->accent_color : Settings::get('design.primary_color', '#fe025b');

		?>
		<style>
		.kc-floating-popup {
			position: fixed;
			bottom: 30px;
			right: 30px;
			width: 340px;
			background: var(--k-surface, #fff);
			border-radius: 16px;
			box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
			z-index: 999999;
			transform: translateY(100px);
			opacity: 0;
			pointer-events: none;
			transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
			overflow: hidden;
			text-decoration: none;
			display: flex;
			flex-direction: column;
		}
		.kc-floating-popup.is-visible {
			transform: translateY(0);
			opacity: 1;
			pointer-events: auto;
		}
		.kc-popup-close {
			position: absolute;
			top: 12px;
			right: 12px;
			width: 28px;
			height: 28px;
			background: rgba(0,0,0,0.5);
			color: #fff;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			z-index: 10;
			border: none;
			backdrop-filter: blur(4px);
			transition: background 0.2s;
		}
		.kc-popup-close:hover { background: rgba(0,0,0,0.8); }
		.kc-popup-header {
			height: 140px;
			position: relative;
			overflow: hidden;
		}
		.kc-popup-bg {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
		.kc-popup-overlay {
			position: absolute;
			inset: 0;
			background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
		}
		.kc-popup-badge {
			position: absolute;
			bottom: 16px;
			left: 16px;
			background: <?php echo esc_attr($accent); ?>;
			color: #fff;
			font-size: 10px;
			font-weight: 900;
			padding: 4px 10px;
			border-radius: 6px;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.kc-popup-body {
			padding: 20px;
			display: flex;
			flex-direction: column;
			gap: 4px;
		}
		.kc-popup-title {
			font-size: 18px;
			font-weight: 900;
			color: var(--k-text, #111);
			margin: 0;
			line-height: 1.2;
		}
		.kc-popup-subtitle {
			font-size: 13px;
			font-weight: 600;
			color: var(--k-text-muted, #666);
			margin: 0;
		}
		.kc-popup-cta {
			margin-top: 12px;
			font-size: 12px;
			font-weight: 800;
			color: <?php echo esc_attr($accent); ?>;
			display: flex;
			align-items: center;
			gap: 4px;
		}
		@media (max-width: 768px) {
			.kc-floating-popup {
				bottom: 20px; right: 20px; left: 20px; width: auto;
			}
		}
		</style>
		
		<a href="<?php echo esc_url($chart_url); ?>" class="kc-floating-popup" id="kc-floating-popup">
			<?php if ( $show_close ) : ?>
			<button class="kc-popup-close" id="kc-popup-close" aria-label="Close">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
			</button>
			<?php endif; ?>
			
			<div class="kc-popup-header">
				<img src="<?php echo esc_url($image); ?>" class="kc-popup-bg" alt="Cover">
				<div class="kc-popup-overlay"></div>
				<div class="kc-popup-badge">#1 &middot; <?php echo esc_html($def->title); ?></div>
			</div>
			<div class="kc-popup-body">
				<h4 class="kc-popup-title"><?php echo esc_html($title); ?></h4>
				<p class="kc-popup-subtitle"><?php echo esc_html($subtitle); ?></p>
				<div class="kc-popup-cta">
					<?php echo esc_html($cta_text); ?>
					<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</div>
			</div>
		</a>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var popup = document.getElementById('kc-floating-popup');
			if (!popup) return;

			var defId = 'kc_popup_<?php echo esc_js($def->id); ?>';
			if (localStorage.getItem(defId) === 'closed') return;

			setTimeout(function() {
				popup.classList.add('is-visible');
			}, <?php echo intval($delay_ms); ?>);

			var closeBtn = document.getElementById('kc-popup-close');
			if (closeBtn) {
				closeBtn.addEventListener('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					popup.classList.remove('is-visible');
					localStorage.setItem(defId, 'closed');
				});
			}
		});
		</script>
		<?php
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
		unset($e); // Critical fix: break reference to avoid duplicating last row when array is iterated again

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
