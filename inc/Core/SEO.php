<?php

namespace Charts\Core;

/**
 * Handle Search Engine Optimization (SEO) for public Charts pages.
 * Supports WordPress native SEO, Rank Math, Yoast SEO, All in One SEO,
 * OpenGraph, Twitter Cards, Canonical URLs, and JSON-LD Structured Data.
 */
class SEO {

	/**
	 * Initialize SEO module.
	 */
	public static function init() {
		// 1. Prevent WordPress from treating virtual Charts routes as 404
		add_filter( 'pre_handle_404', array( self::class, 'prevent_404_status' ), 10, 2 );
		add_action( 'template_redirect', array( self::class, 'fix_query_status' ), 1 );
		add_action( 'parse_query', array( self::class, 'fix_query_status_early' ), 1 );

		// 2. Document Titles (Native WordPress)
		add_filter( 'pre_get_document_title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'document_title_parts', array( self::class, 'filter_document_title_parts' ), 99 );
		add_filter( 'wp_title', array( self::class, 'generate_title' ), 99 );

		// 3. Meta Tags & Social Support
		add_action( 'wp_head', array( self::class, 'inject_meta_tags' ), 5 );
		add_action( 'wp_head', array( self::class, 'inject_structured_data' ), 30 );

		// 4. Robots handling (Allow indexing for public charts, noindex for dashboard)
		add_filter( 'wp_robots', array( self::class, 'handle_robots' ), 99 );

		// 5. Rank Math Integration
		add_filter( 'rank_math/frontend/title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'rank_math/frontend/description', array( self::class, 'generate_description' ), 99 );
		add_filter( 'rank_math/frontend/canonical', array( self::class, 'generate_canonical' ), 99 );
		add_filter( 'rank_math/frontend/robots', array( self::class, 'filter_rank_math_robots' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/description', array( self::class, 'generate_description' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/image', array( self::class, 'generate_og_image' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/url', array( self::class, 'generate_canonical' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/type', array( self::class, 'generate_og_type' ), 99 );
		add_filter( 'rank_math/opengraph/twitter/title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'rank_math/opengraph/twitter/description', array( self::class, 'generate_description' ), 99 );
		add_filter( 'rank_math/opengraph/twitter/image', array( self::class, 'generate_og_image' ), 99 );
		add_filter( 'rank_math/json_ld', array( self::class, 'filter_rank_math_json_ld' ), 99, 2 );

		// 6. Yoast SEO Integration
		add_filter( 'wpseo_title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'wpseo_metadesc', array( self::class, 'generate_description' ), 99 );
		add_filter( 'wpseo_canonical', array( self::class, 'generate_canonical' ), 99 );
		add_filter( 'wpseo_robots', array( self::class, 'filter_yoast_robots' ), 99 );
		add_filter( 'wpseo_opengraph_title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'wpseo_opengraph_desc', array( self::class, 'generate_description' ), 99 );
		add_filter( 'wpseo_opengraph_image', array( self::class, 'generate_og_image' ), 99 );
		add_filter( 'wpseo_opengraph_url', array( self::class, 'generate_canonical' ), 99 );
		add_filter( 'wpseo_opengraph_type', array( self::class, 'generate_og_type' ), 99 );
		add_filter( 'wpseo_twitter_title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'wpseo_twitter_description', array( self::class, 'generate_description' ), 99 );
		add_filter( 'wpseo_twitter_image', array( self::class, 'generate_og_image' ), 99 );

		// 7. All in One SEO (AIOSEO) Integration
		add_filter( 'aioseo_title', array( self::class, 'generate_title' ), 99 );
		add_filter( 'aioseo_description', array( self::class, 'generate_description' ), 99 );
		add_filter( 'aioseo_canonical_url', array( self::class, 'generate_canonical' ), 99 );

		// 8. Register XML Sitemaps for Charts
		add_action( 'init', array( self::class, 'register_sitemaps' ) );
	}

	/**
	 * Detect active Charts route safely from query vars or URI path.
	 */
	public static function get_current_route() {
		$route = get_query_var( 'charts_route' );
		if ( $route ) return $route;

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$path = trim( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
			if ( $path === 'charts' ) return 'index';
			if ( preg_match( '#^charts/artist/([^/]+)#', $path, $m ) ) {
				if ( ! get_query_var( 'charts_artist_slug' ) ) set_query_var( 'charts_artist_slug', $m[1] );
				return 'artist-single';
			}
			if ( preg_match( '#^charts/(track|album|video|clip)/([^/]+)#', $path, $m ) ) {
				$type = $m[1] === 'clip' ? 'video' : $m[1];
				if ( ! get_query_var( 'charts_item_type' ) ) set_query_var( 'charts_item_type', $type );
				if ( ! get_query_var( 'charts_item_slug' ) ) set_query_var( 'charts_item_slug', $m[2] );
				return 'item-single';
			}
			if ( $path === 'charts/artists' ) return 'artist-archive';
			if ( $path === 'charts/tracks' ) return 'track-archive';
			if ( $path === 'charts/videos' ) return 'video-archive';
			if ( preg_match( '#^charts/([^/]+)#', $path, $m ) ) {
				if ( ! get_query_var( 'charts_definition_slug' ) ) set_query_var( 'charts_definition_slug', $m[1] );
				return 'single-chart';
			}
			if ( strpos( $path, 'charts-dashboard' ) === 0 ) return 'dashboard';
		}

		return '';
	}

	/**
	 * Prevent WordPress from sending a 404 header for virtual Charts endpoints.
	 */
	public static function prevent_404_status( $handled, $wp_query ) {
		$route = self::get_current_route();
		if ( $route ) {
			if ( is_object( $wp_query ) ) {
				$wp_query->is_404 = false;
			}
			status_header( 200 );
			return true;
		}
		return $handled;
	}

	/**
	 * Clear 404 flag on main query early.
	 */
	public static function fix_query_status_early( $wp_query ) {
		if ( ! is_object( $wp_query ) || ! $wp_query->is_main_query() ) return;
		$route = self::get_current_route();
		if ( $route ) {
			$wp_query->is_404 = false;
		}
	}

	/**
	 * Clear 404 flag on template redirect and force 200 OK.
	 */
	public static function fix_query_status() {
		$route = self::get_current_route();
		if ( $route ) {
			global $wp_query;
			if ( is_object( $wp_query ) ) {
				$wp_query->is_404 = false;
			}
			status_header( 200 );
		}
	}

	/**
	 * Generate dynamic page titles.
	 */
	public static function generate_title( $title = '' ) {
		$route = self::get_current_route();
		if ( ! $route ) return $title;

		$brand_ar = 'قوائم كونتينتيمنت';
		$brand_en = 'Kontentainment Charts';
		$full_brand = "{$brand_en} | {$brand_ar}";

		switch ( $route ) {
			case 'index':
				return "Kontentainment Charts | قوائم كونتينتيمنت";

			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				$chart = self::get_chart_definition( $slug );
				if ( $chart ) {
					$chart_title = ! empty( $chart->title_ar ) ? $chart->title_ar : ( class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $chart->title ) : $chart->title );
					return "{$chart_title} | {$full_brand}";
				}
				return "قوائم الموسيقى الرسمية | {$full_brand}";

			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				if ( $artist ) {
					$artist_name = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $artist->display_name ) : $artist->display_name;
					return "{$artist_name} - إحصائيات وترتيب القوائم | {$full_brand}";
				}
				return "ملف الفنان | {$full_brand}";

			case 'item-single':
				$type = get_query_var( 'charts_item_type' ) ?: 'track';
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				if ( $item ) {
					$item_title  = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->title ) : $item->title;
					$artist_name = ! empty( $item->artist_names ) ? ( class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->artist_names ) : $item->artist_names ) : '';
					$by_artist   = $artist_name ? " - {$artist_name}" : "";

					if ( $type === 'album' ) {
						return "ألبوم {$item_title}{$by_artist} | {$full_brand}";
					} elseif ( $type === 'video' ) {
						return "فيديو كليب {$item_title}{$by_artist} | {$full_brand}";
					} else {
						return "{$item_title}{$by_artist} | {$full_brand}";
					}
				}
				$label = ( $type === 'album' ) ? 'الألبوم' : ( ( $type === 'video' ) ? 'الفيديو' : 'الأغنية' );
				return "{$label} | {$full_brand}";

			case 'artist-archive':
				return "دليل الفنانين وإحصائيات القوائم | {$full_brand}";

			case 'track-archive':
				return "أفضل الأغاني والموسيقى الأكثر استماعاً | {$full_brand}";

			case 'video-archive':
				return "أفضل الكليبات والفيديوهات الموسيقية | {$full_brand}";

			case 'dashboard':
				return "Charts Dashboard | Kontentainment";
		}

		return $title;
	}

	/**
	 * Override WordPress document title parts.
	 */
	public static function filter_document_title_parts( $parts ) {
		$route = self::get_current_route();
		if ( ! $route ) return $parts;

		$title = self::generate_title( '' );
		if ( $title ) {
			return array( 'title' => $title );
		}
		return $parts;
	}

	/**
	 * Generate rich, Arabic-first Meta Descriptions.
	 */
	public static function generate_description( $desc = '' ) {
		$route = self::get_current_route();
		if ( ! $route ) return $desc;

		global $wpdb;

		switch ( $route ) {
			case 'index':
				return 'تابع أحدث قوائم الموسيقى والأغاني الأكثر استماعاً ورواجاً في مصر والوطن العربي على Kontentainment Charts | قوائم كونتينتيمنت. تحديثات أسبوعية وإحصائيات دقيقة لسباق الأغاني والألبومات والفنانين.';

			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				$chart = self::get_chart_definition( $slug );
				if ( $chart ) {
					$chart_title = ! empty( $chart->title_ar ) ? $chart->title_ar : ( class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $chart->title ) : $chart->title );
					
					$top_track = $wpdb->get_var( $wpdb->prepare( "
						SELECT track_name FROM {$wpdb->prefix}charts_entries e
						JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
						WHERE s.chart_type = %s AND e.rank_position = 1
						ORDER BY e.created_at DESC LIMIT 1
					", "cid-{$chart->id}" ) );

					if ( ! $top_track ) {
						$top_track = $wpdb->get_var( $wpdb->prepare( "
							SELECT track_name FROM {$wpdb->prefix}charts_entries e
							JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
							WHERE s.chart_type = %s AND s.country_code = %s AND e.rank_position = 1
							ORDER BY e.created_at DESC LIMIT 1
						", $chart->chart_type, $chart->country_code ) );
					}

					if ( $top_track && class_exists( '\Charts\Core\Translation' ) ) {
						$top_track = \Charts\Core\Translation::get( $top_track );
					}

					$extra = $top_track ? "، مع تصدر أغنية \"{$top_track}\" للمركز الأول" : "";
					$summary = ! empty( $chart->chart_summary ) ? " " . $chart->chart_summary : "";
					return "قائمة {$chart_title} على قوائم كونتينتيمنت. اكتشف ترتيب الأغاني الأكثر استماعاً وتحديثات القوائم أسبوعياً{$extra}.{$summary}";
				}
				break;

			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				if ( $artist ) {
					$artist_name = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $artist->display_name ) : $artist->display_name;
					$meta = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
					$bio  = $meta['bio'] ?? '';
					if ( ! empty( $bio ) ) {
						return wp_trim_words( $bio, 30 );
					}
					return "الصفحة الرسمية لإحصائيات الفنان {$artist_name} على Kontentainment Charts | قوائم كونتينتيمنت. استكشف جميع أغانيه وألبوماته في القوائم، وأعلى المراكز المحققة.";
				}
				break;

			case 'item-single':
				$type = get_query_var( 'charts_item_type' ) ?: 'track';
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				if ( $item ) {
					$item_title  = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->title ) : $item->title;
					$artist_name = ! empty( $item->artist_names ) ? ( class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->artist_names ) : $item->artist_names ) : '';
					$by_artist   = $artist_name ? " للفنان {$artist_name}" : "";

					if ( $type === 'album' ) {
						return "تفاصيل وترتيب ألبوم \"{$item_title}\"{$by_artist} في القوائم الموسيقية على Kontentainment Charts | قوائم كونتينتيمنت. إحصائيات الأداء والمراكز التاريخية.";
					} elseif ( $type === 'video' ) {
						return "مشاهدة وترتيب كليب \"{$item_title}\"{$by_artist} على قوائم كونتينتيمنت الموسيقية. إحصائيات المشاهدات والأداء الأسبوعي.";
					} else {
						return "استمع وتعرف على ترتيب أغنية \"{$item_title}\"{$by_artist} على Kontentainment Charts | قوائم كونتينتيمنت. إحصائيات الاستماع وأعلى مركز حققته الأغنية.";
					}
				}
				break;

			case 'artist-archive':
				return 'دليل الفنانين الأكثر استماعاً ونجاحاً على قوائم كونتينتيمنت الموسيقية. تصفح أداء نجوم الموسيقى العربية والعالمية وإحصائياتهم الرسمية.';

			case 'track-archive':
				return 'أرشيف الأغاني والموسيقى الأكثر استماعاً ورواجاً في الشرق الأوسط والعالم عبر Kontentainment Charts | قوائم كونتينتيمنت.';

			case 'video-archive':
				return 'أفضل الفيديوهات والكليبات الموسيقية الأكثر مشاهدة ورواجاً في سباق الأغاني على قوائم كونتينتيمنت.';
		}

		return $desc;
	}

	/**
	 * Generate canonical clean URLs.
	 */
	public static function generate_canonical( $url = '' ) {
		$route = self::get_current_route();
		if ( ! $route ) return $url;

		switch ( $route ) {
			case 'index':
				return home_url( '/charts/' );
			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				return home_url( '/charts/' . $slug . '/' );
			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				return home_url( '/charts/artist/' . $slug . '/' );
			case 'item-single':
				$type = get_query_var( 'charts_item_type' ) ?: 'track';
				$slug = get_query_var( 'charts_item_slug' );
				return home_url( '/charts/' . $type . '/' . $slug . '/' );
			case 'artist-archive':
				return home_url( '/charts/artists/' );
			case 'track-archive':
				return home_url( '/charts/tracks/' );
			case 'video-archive':
				return home_url( '/charts/videos/' );
			case 'dashboard':
				return home_url( '/charts-dashboard/' );
		}

		return home_url( '/charts/' );
	}

	/**
	 * Generate high-resolution OpenGraph and Twitter image.
	 */
	public static function generate_og_image( $img = '' ) {
		$route = self::get_current_route();
		if ( ! $route ) return $img;

		$fallback = CHARTS_URL . 'public/assets/img/placeholder.png';
		$site_icon = get_site_icon_url( 512 );
		if ( $site_icon ) $fallback = $site_icon;

		if ( $route === 'artist-single' ) {
			$slug = get_query_var( 'charts_artist_slug' );
			$artist = self::get_artist_by_slug( $slug );
			if ( $artist && ! empty( $artist->image ) ) return $artist->image;
		} elseif ( $route === 'item-single' ) {
			$type = get_query_var( 'charts_item_type' );
			$slug = get_query_var( 'charts_item_slug' );
			$item = self::get_item_by_slug( $type, $slug );
			if ( $item ) {
				if ( ! empty( $item->cover_image ) ) return $item->cover_image;
				if ( ! empty( $item->thumbnail ) ) return $item->thumbnail;
			}
		} elseif ( $route === 'single-chart' ) {
			$slug = get_query_var( 'charts_definition_slug' );
			$chart = self::get_chart_definition( $slug );
			if ( $chart ) {
				if ( ! empty( $chart->cover_image_url ) ) return $chart->cover_image_url;
				
				global $wpdb;
				$art = $wpdb->get_var( $wpdb->prepare( "
					SELECT cover_image FROM {$wpdb->prefix}charts_entries e
					JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
					WHERE s.chart_type = %s AND e.rank_position = 1 AND e.cover_image IS NOT NULL
					ORDER BY e.created_at DESC LIMIT 1
				", "cid-{$chart->id}" ) );
				if ( $art ) return $art;
			}
		}

		$custom_logo = Settings::get( 'branding.logo' );
		if ( $custom_logo ) return $custom_logo;

		return $fallback;
	}

	/**
	 * Generate OpenGraph type.
	 */
	public static function generate_og_type( $type = 'website' ) {
		$route = self::get_current_route();
		if ( ! $route ) return $type;

		if ( $route === 'item-single' ) {
			$item_type = get_query_var( 'charts_item_type' );
			if ( $item_type === 'album' ) return 'music.album';
			if ( $item_type === 'video' ) return 'video.other';
			return 'music.song';
		} elseif ( $route === 'artist-single' ) {
			return 'profile';
		} elseif ( $route === 'single-chart' ) {
			return 'music.playlist';
		}

		return 'website';
	}

	/**
	 * WordPress Robots Meta handling.
	 */
	public static function handle_robots( $robots ) {
		$route = self::get_current_route();
		if ( ! $route ) return $robots;

		if ( $route === 'dashboard' ) {
			return array( 'noindex' => true, 'nofollow' => true );
		}

		return array(
			'index'                   => true,
			'follow'                  => true,
			'max-image-preview:large' => true,
			'max-snippet:-1'          => true,
			'max-video-preview:-1'    => true,
		);
	}

	/**
	 * Rank Math Robots Meta handling.
	 */
	public static function filter_rank_math_robots( $robots ) {
		$route = self::get_current_route();
		if ( ! $route ) return $robots;

		if ( $route === 'dashboard' ) {
			return array( 'noindex' => 'noindex', 'nofollow' => 'nofollow' );
		}

		return array(
			'index'             => 'index',
			'follow'            => 'follow',
			'max-snippet'       => 'max-snippet:-1',
			'max-video-preview' => 'max-video-preview:-1',
			'max-image-preview' => 'max-image-preview:large',
		);
	}

	/**
	 * Yoast Robots Meta handling.
	 */
	public static function filter_yoast_robots( $robots ) {
		$route = self::get_current_route();
		if ( ! $route ) return $robots;

		if ( $route === 'dashboard' ) {
			return 'noindex, nofollow';
		}

		return 'index, follow';
	}

	/**
	 * Inject Meta Tags & Social Graph for clean rooms, mobile mode, or when no SEO plugin is active.
	 */
	public static function inject_meta_tags() {
		$route = self::get_current_route();
		if ( ! $route ) return;

		$has_active_seo_plugin = class_exists( 'RankMath' ) || defined( 'WPSEO_VERSION' ) || function_exists( 'aioseo' );
		$is_mobile = get_query_var( 'mobile_view' ) || isset( $_GET['mobile_view'] );

		// Desktop with an SEO plugin delegates to our filter hooks above
		if ( $has_active_seo_plugin && ! $is_mobile ) {
			return;
		}

		$title = self::generate_title( '' );
		$desc  = self::generate_description( '' );
		$url   = self::generate_canonical( '' );
		$img   = self::generate_og_image( '' );
		$type  = self::generate_og_type( 'website' );

		?>
		<meta name="description" content="<?php echo esc_attr( $desc ); ?>">
		<link rel="canonical" href="<?php echo esc_url( $url ); ?>">

		<!-- Open Graph -->
		<meta property="og:locale" content="ar_AR">
		<meta property="og:locale:alternate" content="en_US">
		<meta property="og:type" content="<?php echo esc_attr( $type ); ?>">
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
		<meta property="og:description" content="<?php echo esc_attr( $desc ); ?>">
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>">
		<meta property="og:image" content="<?php echo esc_url( $img ); ?>">
		<meta property="og:site_name" content="Kontentainment Charts | قوائم كونتينتيمنت">

		<!-- Twitter -->
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
		<meta name="twitter:description" content="<?php echo esc_attr( $desc ); ?>">
		<meta name="twitter:image" content="<?php echo esc_url( $img ); ?>">
		<?php
	}

	/**
	 * Build Schema.org JSON-LD structured data graph.
	 */
	public static function build_schema_data() {
		$route = self::get_current_route();
		if ( ! $route || $route === 'dashboard' ) return array();

		$url   = self::generate_canonical();
		$title = self::generate_title();
		$desc  = self::generate_description();
		$img   = self::generate_og_image();

		$schemas = array();

		// Breadcrumbs (Common for all)
		$breadcrumbs = array(
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type' => 'ListItem',
					'position' => 1,
					'name' => 'Kontentainment Charts | قوائم كونتينتيمنت',
					'item' => home_url( '/charts/' )
				)
			)
		);

		switch ( $route ) {
			case 'index':
				$schemas[] = array(
					'@context' => 'https://schema.org',
					'@type' => 'WebSite',
					'name' => 'Kontentainment Charts | قوائم كونتينتيمنت',
					'url' => home_url( '/charts/' ),
					'description' => $desc,
					'inLanguage' => 'ar-EG',
					'publisher' => array(
						'@type' => 'Organization',
						'name' => 'Kontentainment',
						'url' => home_url(),
						'logo' => array(
							'@type' => 'ImageObject',
							'url' => $img
						)
					)
				);
				$schemas[] = array(
					'@context' => 'https://schema.org',
					'@type' => 'CollectionPage',
					'name' => 'Kontentainment Charts | قوائم كونتينتيمنت',
					'url' => $url,
					'description' => $desc,
					'inLanguage' => 'ar-EG'
				);
				break;

			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				$chart = self::get_chart_definition( $slug );
				if ( $chart ) {
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 2,
						'name' => $chart->title_ar ?: $chart->title,
						'item' => $url
					);

					global $wpdb;
					$entries = $wpdb->get_results( $wpdb->prepare( "
						SELECT e.rank_position, e.track_name, e.artist_names, e.cover_image, e.item_slug, e.item_type
						FROM {$wpdb->prefix}charts_entries e
						JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
						WHERE s.chart_type = %s
						ORDER BY e.rank_position ASC
						LIMIT 20
					", "cid-{$chart->id}" ) );

					$item_list_elements = array();
					$tracks_schema = array();

					foreach ( $entries as $e ) {
						$track_name  = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $e->track_name ) : $e->track_name;
						$artist_name = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $e->artist_names ) : $e->artist_names;
						$entry_url   = $e->item_slug ? home_url( '/charts/' . ( $e->item_type ?: 'track' ) . '/' . $e->item_slug . '/' ) : $url;

						$item_list_elements[] = array(
							'@type' => 'ListItem',
							'position' => (int) $e->rank_position,
							'name' => "{$track_name} - {$artist_name}",
							'url' => $entry_url,
							'image' => $e->cover_image ?: $img
						);

						$tracks_schema[] = array(
							'@type' => 'MusicRecording',
							'name' => $track_name,
							'byArtist' => array(
								'@type' => 'MusicGroup',
								'name' => $artist_name
							),
							'position' => (int) $e->rank_position,
							'url' => $entry_url,
							'image' => $e->cover_image ?: $img
						);
					}

					$schemas[] = array(
						'@context' => 'https://schema.org',
						'@type' => 'MusicPlaylist',
						'name' => $chart->title_ar ?: $chart->title,
						'url' => $url,
						'description' => $desc,
						'image' => $img,
						'numTracks' => count( $tracks_schema ),
						'track' => $tracks_schema
					);

					if ( ! empty( $item_list_elements ) ) {
						$schemas[] = array(
							'@context' => 'https://schema.org',
							'@type' => 'ItemList',
							'name' => $chart->title_ar ?: $chart->title,
							'url' => $url,
							'itemListElement' => $item_list_elements
						);
					}
				}
				break;

			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				if ( $artist ) {
					$artist_name = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $artist->display_name ) : $artist->display_name;
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 2,
						'name' => 'الفنانين',
						'item' => home_url( '/charts/artists/' )
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 3,
						'name' => $artist_name,
						'item' => $url
					);

					$schemas[] = array(
						'@context' => 'https://schema.org',
						'@type' => 'MusicGroup',
						'name' => $artist_name,
						'url' => $url,
						'image' => $artist->image ?: $img,
						'description' => $desc
					);
				}
				break;

			case 'item-single':
				$type = get_query_var( 'charts_item_type' ) ?: 'track';
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				if ( $item ) {
					$item_title  = class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->title ) : $item->title;
					$artist_name = ! empty( $item->artist_names ) ? ( class_exists( '\Charts\Core\Translation' ) ? \Charts\Core\Translation::get( $item->artist_names ) : $item->artist_names ) : '';
					
					$category_name = ( $type === 'album' ) ? 'الألبومات' : ( ( $type === 'video' ) ? 'الفيديوهات' : 'الأغاني' );
					$category_url  = ( $type === 'album' ) ? home_url( '/charts/' ) : ( ( $type === 'video' ) ? home_url( '/charts/videos/' ) : home_url( '/charts/tracks/' ) );

					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 2,
						'name' => $category_name,
						'item' => $category_url
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 3,
						'name' => $item_title,
						'item' => $url
					);

					if ( $type === 'album' ) {
						$schemas[] = array(
							'@context' => 'https://schema.org',
							'@type' => 'MusicAlbum',
							'name' => $item_title,
							'url' => $url,
							'image' => $item->cover_image ?: $img,
							'byArtist' => array(
								'@type' => 'MusicGroup',
								'name' => $artist_name
							),
							'description' => $desc
						);
					} elseif ( $type === 'video' ) {
						$schemas[] = array(
							'@context' => 'https://schema.org',
							'@type' => 'VideoObject',
							'name' => $item_title,
							'url' => $url,
							'thumbnailUrl' => array( $item->thumbnail ?: $img ),
							'description' => $desc,
							'uploadDate' => ! empty( $item->created_at ) ? date( 'c', strtotime( $item->created_at ) ) : date( 'c' )
						);
					} else {
						$schemas[] = array(
							'@context' => 'https://schema.org',
							'@type' => 'MusicRecording',
							'name' => $item_title,
							'url' => $url,
							'image' => $item->cover_image ?: $img,
							'byArtist' => array(
								'@type' => 'MusicGroup',
								'name' => $artist_name
							),
							'description' => $desc
						);
					}
				}
				break;

			default:
				$schemas[] = array(
					'@context' => 'https://schema.org',
					'@type' => 'CollectionPage',
					'name' => $title,
					'description' => $desc,
					'url' => $url
				);
		}

		$schemas[] = $breadcrumbs;
		return $schemas;
	}

	/**
	 * Rank Math JSON-LD Schema filter.
	 */
	public static function filter_rank_math_json_ld( $data, $jsonld ) {
		$schemas = self::build_schema_data();
		if ( ! empty( $schemas ) ) {
			foreach ( $schemas as $i => $s ) {
				$key = 'Charts_' . ( $s['@type'] ?? 'Item_' . $i );
				$data[ $key ] = $s;
			}
		}
		return $data;
	}

	/**
	 * Inject Structured Data (JSON-LD) when Rank Math is not active or on mobile mode.
	 */
	public static function inject_structured_data() {
		$is_mobile = get_query_var( 'mobile_view' ) || isset( $_GET['mobile_view'] );
		if ( class_exists( 'RankMath' ) && ! $is_mobile ) {
			return;
		}

		$schemas = self::build_schema_data();
		if ( empty( $schemas ) ) return;

		foreach ( $schemas as $schema ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
		}
	}

	/**
	 * Register XML Sitemaps Provider for WordPress Core & Rank Math.
	 */
	public static function register_sitemaps() {
		if ( class_exists( '\WP_Sitemaps_Provider' ) && function_exists( 'wp_register_sitemap_provider' ) ) {
			wp_register_sitemap_provider( 'charts', new ChartsSitemapProvider() );
		}

		// Rank Math Sitemap Integration
		add_filter( 'rank_math/sitemap/index', function( $xml ) {
			$sitemap_url = home_url( '/wp-sitemap-charts-1.xml' );
			$xml .= '  <sitemap>' . "\n";
			$xml .= '    <loc>' . esc_url( $sitemap_url ) . '</loc>' . "\n";
			$xml .= '    <lastmod>' . date( 'c' ) . '</lastmod>' . "\n";
			$xml .= '  </sitemap>' . "\n";
			return $xml;
		} );
	}

	// -------------------------------------------------------------------------
	//  Data Helpers
	// -------------------------------------------------------------------------

	public static function get_chart_definition( $slug ) {
		if ( empty( $slug ) ) return null;
		$manager = new \Charts\Admin\SourceManager();
		return $manager->get_definition_by_slug( $slug );
	}

	public static function get_artist_by_slug( $slug ) {
		if ( empty( $slug ) ) return null;
		return \Charts\Core\EntityManager::get_entity_by_slug( 'artist', $slug );
	}

	public static function get_item_by_slug( $type, $slug ) {
		if ( empty( $slug ) ) return null;
		$item = \Charts\Core\EntityManager::get_entity_by_slug( $type, $slug );
		if ( $item && ! empty( $item->primary_artist_id ) ) {
			global $wpdb;
			$artist_name = $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item->primary_artist_id ) );
			$item->artist_names = $artist_name;
		}
		return $item;
	}
}

/**
 * Native WordPress XML Sitemap Provider for Charts.
 */
if ( class_exists( '\WP_Sitemaps_Provider' ) ) {
	class ChartsSitemapProvider extends \WP_Sitemaps_Provider {
		public function __construct() {
			$this->name = 'charts';
			$this->object_type = 'charts';
		}

		public function get_url_list( $page_num, $object_subtype = '' ) {
			global $wpdb;
			$urls = array();

			if ( $page_num === 1 ) {
				// 1. Static Archive & Main Pages
				$urls[] = array( 'loc' => home_url( '/charts/' ) );
				$urls[] = array( 'loc' => home_url( '/charts/artists/' ) );
				$urls[] = array( 'loc' => home_url( '/charts/tracks/' ) );
				$urls[] = array( 'loc' => home_url( '/charts/videos/' ) );

				// 2. Active Chart Definitions
				$definitions = $wpdb->get_results( "SELECT slug, updated_at FROM {$wpdb->prefix}charts_definitions WHERE is_active = 1" );
				foreach ( $definitions as $def ) {
					$urls[] = array(
						'loc' => home_url( '/charts/' . $def->slug . '/' ),
						'lastmod' => ! empty( $def->updated_at ) ? date( 'c', strtotime( $def->updated_at ) ) : date( 'c' )
					);
				}

				// 3. Top Artists
				$artists = $wpdb->get_results( "SELECT slug, updated_at FROM {$wpdb->prefix}charts_artists ORDER BY id DESC LIMIT 500" );
				foreach ( $artists as $art ) {
					$urls[] = array(
						'loc' => home_url( '/charts/artist/' . $art->slug . '/' ),
						'lastmod' => ! empty( $art->updated_at ) ? date( 'c', strtotime( $art->updated_at ) ) : date( 'c' )
					);
				}

				// 4. Top Tracks
				$tracks = $wpdb->get_results( "SELECT slug, updated_at FROM {$wpdb->prefix}charts_tracks ORDER BY id DESC LIMIT 500" );
				foreach ( $tracks as $trk ) {
					$urls[] = array(
						'loc' => home_url( '/charts/track/' . $trk->slug . '/' ),
						'lastmod' => ! empty( $trk->updated_at ) ? date( 'c', strtotime( $trk->updated_at ) ) : date( 'c' )
					);
				}
			}

			return $urls;
		}

		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
}
