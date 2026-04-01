<?php

namespace Charts\Core;

/**
 * Handle Search Engine Optimization (SEO) for public Charts pages.
 */
class SEO {

	/**
	 * Initialize SEO module.
	 */
	public static function init() {
		// Document Titles
		add_filter( 'pre_get_document_title', array( self::class, 'generate_title' ), 15 );
		
		// Meta Tags & Social Support
		add_action( 'wp_head', array( self::class, 'inject_meta_tags' ), 5 );
		add_action( 'wp_head', array( self::class, 'inject_structured_data' ), 30 );
		
		// Robots handling (noindex for dashboard)
		add_filter( 'wp_robots', array( self::class, 'handle_robots' ) );
		
		// Rank Math Integration
		add_filter( 'rank_math/frontend/title', array( self::class, 'generate_title' ) );
		add_filter( 'rank_math/frontend/description', array( self::class, 'generate_description' ) );
		add_filter( 'rank_math/frontend/canonical', array( self::class, 'generate_canonical' ) );
		
		// Rank Math OpenGraph Filters
		add_filter( 'rank_math/opengraph/facebook/title', array( self::class, 'generate_title' ) );
		add_filter( 'rank_math/opengraph/facebook/description', array( self::class, 'generate_description' ) );
		add_filter( 'rank_math/opengraph/twitter/title', array( self::class, 'generate_title' ) );
		add_filter( 'rank_math/opengraph/twitter/description', array( self::class, 'generate_description' ) );
	}

	/**
	 * Generate dynamic page titles.
	 */
	public static function generate_title( $title ) {
		$route = get_query_var( 'charts_route' );
		if ( ! $route ) return $title;

		$site_name = get_bloginfo( 'name' );

		switch ( $route ) {
			case 'index':
				return "Music Charts & Intelligence | $site_name";
			
			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				$chart = self::get_chart_definition( $slug );
				return $chart ? "{$chart->title} Top Rankings | $site_name" : "Music Charts | $site_name";
			
			case 'artist-archive':
				return "Top Artists Intelligence Index | $site_name";
			
			case 'track-archive':
				return "Top Tracks & Trending Songs | $site_name";
			
			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				return $artist ? "{$artist->display_name} Music Stats & Chart History | $site_name" : "Artist Profile | $site_name";
			
			case 'item-single':
				$type = get_query_var( 'charts_item_type' );
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				$type_label = ucfirst( $type );
				return $item ? "{$item->title} - Chart Insights & Global Stats | $site_name" : "$type_label Profile | $site_name";
			
			case 'dashboard':
				return "Charts Dashboard | Operation Center";
		}

		return $title;
	}

	public static function generate_description( $desc ) {
		$route = get_query_var( 'charts_route' );
		if ( ! $route ) return $desc;

		global $wpdb;

		switch ( $route ) {
			case 'index':
				return "Experience the definitive music intelligence platform. Discover trending tracks, rising artists, and verified historical chart performance across global and regional markets.";
			
			case 'single-chart':
				$slug = get_query_var( 'charts_definition_slug' );
				$chart = self::get_chart_definition( $slug );
				if ( $chart ) {
					// Get the top track for a more dynamic description
					$top_track = $wpdb->get_var( $wpdb->prepare( "
						SELECT track_name FROM {$wpdb->prefix}charts_entries e
						JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
						WHERE s.chart_type = %s AND s.country_code = %s AND e.rank_position = 1
						ORDER BY e.created_at DESC LIMIT 1
					", $chart->chart_type, $chart->country_code ) );
					
					$extra = $top_track ? " featuring #1 track \"$top_track\"" : "";
					return "Weekly intelligence report for the {$chart->title}{$extra}. View the full list of trending tracks, peaks, movements, and audience insights.";
				}
				break;
			
			case 'artist-archive':
				return "Browse the global intelligence directory of musical artists. Analyze market performance, chart history, and audience growth across all integrated platforms.";
			
			case 'track-archive':
				return "Explore the master index of trending musical works. Search and filter through thousands of tracks making impact across regional and global music charts.";
			
			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				if ( $artist ) {
					$meta = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : array();
					$bio  = $meta['bio'] ?? '';
					if ( ! empty( $bio ) ) {
						return wp_trim_words( $bio, 25 );
					}
					return "Complete chart history and intelligence profile for {$artist->display_name}. View peak positions, total chart stay, and global platform performance data.";
				}
				break;
			
			case 'item-single':
				$type = get_query_var( 'charts_item_type' );
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				if ( $item ) {
					$artist_name = $item->artist_names ?? '';
					$extra = $artist_name ? " by $artist_name" : "";
					return "Detailed insight report for \"{$item->title}\"{$extra}. Analyze historical rankings, market penetration, and long-term chart trends.";
				}
				break;
		}

		return $desc;
	}

	/**
	 * Generate canonical URLs.
	 */
	public static function generate_canonical( $url ) {
		$route = get_query_var( 'charts_route' );
		if ( ! $route ) return $url;

		global $wp;
		return home_url( $wp->request );
	}

	/**
	 * Inject Meta Tags & Social Graph.
	 */
	public static function inject_meta_tags() {
		$route = get_query_var( 'charts_route' );
		if ( ! $route ) return;

		$title = self::generate_title( '' );
		$desc  = self::generate_description( '' );
		$url   = self::generate_canonical( '' );
		$img   = CHARTS_URL . 'public/assets/img/og-fallback.png'; // Fallback

		// Resolve specific images
		if ( $route === 'artist-single' ) {
			$slug = get_query_var( 'charts_artist_slug' );
			$artist = self::get_artist_by_slug( $slug );
			if ( $artist && !empty($artist->image) ) $img = $artist->image;
		} elseif ( $route === 'item-single' ) {
			$type = get_query_var( 'charts_item_type' );
			$slug = get_query_var( 'charts_item_slug' );
			$item = self::get_item_by_slug( $type, $slug );
			if ( $item && !empty($item->cover_image) ) $img = $item->cover_image;
		}

		?>
		<meta name="description" content="<?php echo esc_attr( $desc ); ?>">
		<link rel="canonical" href="<?php echo esc_url( $url ); ?>">

		<!-- Open Graph -->
		<meta property="og:type" content="website">
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
		<meta property="og:description" content="<?php echo esc_attr( $desc ); ?>">
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>">
		<meta property="og:image" content="<?php echo esc_url( $img ); ?>">
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">

		<!-- Twitter -->
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
		<meta name="twitter:description" content="<?php echo esc_attr( $desc ); ?>">
		<meta name="twitter:image" content="<?php echo esc_url( $img ); ?>">
		<?php
	}

	/**
	 * Inject Structured Data (JSON-LD).
	 */
	public static function inject_structured_data() {
		$route = get_query_var( 'charts_route' );
		if ( ! $route || $route === 'dashboard' ) return;

		$schema = array();
		$url    = self::generate_canonical( '' );

		// Breadcrumbs (Common for all)
		$breadcrumbs = array(
			'@type' => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type' => 'ListItem',
					'position' => 1,
					'name' => 'Music Charts',
					'item' => home_url( '/charts' )
				)
			)
		);

		switch ( $route ) {
			case 'artist-single':
				$slug = get_query_var( 'charts_artist_slug' );
				$artist = self::get_artist_by_slug( $slug );
				if ( $artist ) {
					$schema = array(
						'@context' => 'https://schema.org',
						'@type' => 'MusicGroup',
						'name' => $artist->display_name,
						'url' => $url,
						'image' => $artist->image ?: null,
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 2,
						'name' => 'Artists',
						'item' => home_url( '/charts/artists' )
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 3,
						'name' => $artist->display_name,
						'item' => $url
					);
				}
				break;

			case 'item-single':
				$type = get_query_var( 'charts_item_type' );
				$slug = get_query_var( 'charts_item_slug' );
				$item = self::get_item_by_slug( $type, $slug );
				if ( $item ) {
					$schema = array(
						'@context' => 'https://schema.org',
						'@type' => 'MusicRecording',
						'name' => $item->title,
						'url' => $url,
						'image' => $item->cover_image ?: null,
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 2,
						'name' => 'Tracks',
						'item' => home_url( '/charts/tracks' )
					);
					$breadcrumbs['itemListElement'][] = array(
						'@type' => 'ListItem',
						'position' => 3,
						'name' => $item->title,
						'item' => $url
					);
				}
				break;
			
			default:
				$schema = array(
					'@context' => 'https://schema.org',
					'@type' => 'CollectionPage',
					'name' => self::generate_title( '' ),
					'description' => self::generate_description( '' ),
					'url' => $url,
				);
		}

		if ( ! empty( $schema ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array( $breadcrumbs ) ) ) . '</script>';
	}

	/**
	 * Handle Robots Meta (noindex for private routes).
	 */
	public static function handle_robots( $robots ) {
		$route = get_query_var( 'charts_route' );
		if ( $route === 'dashboard' ) {
			return array( 'noindex' => true, 'nofollow' => true );
		}
		return $robots;
	}

	// -------------------------------------------------------------------------
	//  Data Helpers
	// -------------------------------------------------------------------------

	private static function get_chart_definition( $slug ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE slug = %s", $slug ) );
	}

	private static function get_artist_by_slug( $slug ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s", $slug ) );
	}

	private static function get_item_by_slug( $type, $slug ) {
		global $wpdb;
		$table = ( $type === 'video' ) ? $wpdb->prefix . 'charts_videos' : $wpdb->prefix . 'charts_tracks';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
	}
}
