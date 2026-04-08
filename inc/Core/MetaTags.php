<?php

namespace Charts\Core;

/**
 * Handle dynamic entry meta tags for charts entities.
 */
class MetaTags {

	/**
	 * Registry of all supported tags.
	 */
	private static $registry = null;

	/**
	 * Initialize the meta tags system.
	 */
	public static function init() {
		add_filter( 'foxiz_entry_meta_tags', array( self::class, 'integrate_with_foxiz' ) );
		add_shortcode( 'chart_meta', array( self::class, 'handle_shortcode' ) );
	}

	/**
	 * Get the full registry of tags.
	 */
	public static function get_registry() {
		if ( self::$registry !== null ) return self::$registry;

		self::$registry = array(
			'chart' => array(
				'chart_title'          => array( 'label' => __( 'Chart Title', 'charts' ), 'desc' => __( 'The display name of the chart.', 'charts' ) ),
				'chart_slug'           => array( 'label' => __( 'Chart Slug', 'charts' ), 'desc' => __( 'The URL slug of the chart.', 'charts' ) ),
				'chart_type'           => array( 'label' => __( 'Chart Type', 'charts' ), 'desc' => __( 'e.g. Top Songs, Viral.', 'charts' ) ),
				'chart_source'         => array( 'label' => __( 'Source Name', 'charts' ), 'desc' => __( 'Platform name (Spotify, YouTube).', 'charts' ) ),
				'chart_market'         => array( 'label' => __( 'Market/Country', 'charts' ), 'desc' => __( 'Country code or region.', 'charts' ) ),
				'chart_frequency'      => array( 'label' => __( 'Frequency', 'charts' ), 'desc' => __( 'Weekly or Daily.', 'charts' ) ),
				'chart_reporting_date' => array( 'label' => __( 'Reporting Date', 'charts' ), 'desc' => __( 'The date of the current segment.', 'charts' ) ),
				'chart_color'          => array( 'label' => __( 'Source Color', 'charts' ), 'desc' => __( 'Hex color code associated with the source.', 'charts' ) ),
				'chart_description'    => array( 'label' => __( 'Description', 'charts' ), 'desc' => __( 'Chart description or rules.', 'charts' ) ),
				'chart_entries_count'  => array( 'label' => __( 'Entry Count', 'charts' ), 'desc' => __( 'Number of items currently in the chart.', 'charts' ) ),
			),
			'artist' => array(
				'artist_name'       => array( 'label' => __( 'Artist Name', 'charts' ), 'desc' => __( 'Full stage name.', 'charts' ) ),
				'artist_slug'       => array( 'label' => __( 'Artist Slug', 'charts' ), 'desc' => __( 'URL identifier.', 'charts' ) ),
				'artist_image'      => array( 'label' => __( 'Artist Image', 'charts' ), 'desc' => __( 'URL of the profile image.', 'charts' ) ),
				'artist_spotify_id' => array( 'label' => __( 'Spotify ID', 'charts' ), 'desc' => __( 'Canonical Spotify URI.', 'charts' ) ),
				'artist_youtube_id' => array( 'label' => __( 'YouTube Channel ID', 'charts' ), 'desc' => __( 'Official channel ID.', 'charts' ) ),
				'artist_popularity' => array( 'label' => __( 'Popularity Score', 'charts' ), 'desc' => __( '0-100 metric from Spotify.', 'charts' ) ),
				'artist_followers'  => array( 'label' => __( 'Followers Count', 'charts' ), 'desc' => __( 'Total follower count across platforms.', 'charts' ) ),
				'artist_genres'     => array( 'label' => __( 'Genres', 'charts' ), 'desc' => __( 'Comma-separated genre labels.', 'charts' ) ),
				'artist_country'    => array( 'label' => __( 'Country', 'charts' ), 'desc' => __( 'Artist origin country.', 'charts' ) ),
				'artist_source_url' => array( 'label' => __( 'Official URL', 'charts' ), 'desc' => __( 'Link to primary source page.', 'charts' ) ),
			),
			'track' => array(
				'track_title'            => array( 'label' => __( 'Track Title', 'charts' ), 'desc' => __( 'The title of the song.', 'charts' ) ),
				'track_slug'             => array( 'label' => __( 'Track Slug', 'charts' ), 'desc' => __( 'Link identifier.', 'charts' ) ),
				'track_artwork'          => array( 'label' => __( 'Track Artwork', 'charts' ), 'desc' => __( 'Cover art image URL.', 'charts' ) ),
				'track_spotify_id'       => array( 'label' => __( 'Spotify ID', 'charts' ), 'desc' => __( 'Spotify track identifier.', 'charts' ) ),
				'track_youtube_url'      => array( 'label' => __( 'YouTube URL', 'charts' ), 'desc' => __( 'Official video link.', 'charts' ) ),
				'track_views'            => array( 'label' => __( 'Stream/View Count', 'charts' ), 'desc' => __( 'Raw performance metric.', 'charts' ) ),
				'track_growth'           => array( 'label' => __( 'Growth Rate (%)', 'charts' ), 'desc' => __( 'Percentage change vs previous.', 'charts' ) ),
				'track_periods_on_chart' => array( 'label' => __( 'Total Weeks', 'charts' ), 'desc' => __( 'Total appearances in charts.', 'charts' ) ),
				'track_primary_artist'   => array( 'label' => __( 'Main Artist', 'charts' ), 'desc' => __( 'The primary artist name.', 'charts' ) ),
				'track_artist_names'     => array( 'label' => __( 'All Artists', 'charts' ), 'desc' => __( 'Full list of collaborators.', 'charts' ) ),
			),
			'video' => array(
				'video_title'            => array( 'label' => __( 'Video Title', 'charts' ), 'desc' => __( 'The title of the clip/music video.', 'charts' ) ),
				'video_slug'             => array( 'label' => __( 'Video Slug', 'charts' ), 'desc' => __( 'URL slug.', 'charts' ) ),
				'video_thumbnail'        => array( 'label' => __( 'Thumbnail', 'charts' ), 'desc' => __( 'High-res YouTube thumbnail URL.', 'charts' ) ),
				'video_youtube_id'       => array( 'label' => __( 'YouTube ID', 'charts' ), 'desc' => __( '11-character video ID.', 'charts' ) ),
				'video_url'              => array( 'label' => __( 'Video URL', 'charts' ), 'desc' => __( 'Direct watch link.', 'charts' ) ),
				'video_views'            => array( 'label' => __( 'Views', 'charts' ), 'desc' => __( 'Total lifetime views cached.', 'charts' ) ),
				'video_growth'           => array( 'label' => __( 'Weekly Growth', 'charts' ), 'desc' => __( 'Growth delta.', 'charts' ) ),
				'video_artist_names'     => array( 'label' => __( 'Artists', 'charts' ), 'desc' => __( 'Names of musicians featured.', 'charts' ) ),
				'video_periods_on_chart' => array( 'label' => __( 'Retention', 'charts' ), 'desc' => __( 'Total chart weeks.', 'charts' ) ),
			)
		);

		return self::$registry;
	}

	/**
	 * Parse a string and replace tags with dynamic entity data.
	 * 
	 * @param string $format The input string with placeholders (e.g. "By {artist_name}")
	 * @param int|WP_Post|null $context Optional specific entity. Auto-detected if null.
	 */
	public static function parse( $format, $context = null ) {
		if ( empty( $format ) ) return '';

		$post = get_post( $context );
		if ( ! $post ) return $format;

		$type = $post->post_type;
		$registry = self::get_registry();

		// Normalize type (clips used 'video' internally but post_type is 'video')
		if ( ! isset( $registry[$type] ) ) return $format;

		$tags = $registry[$type];
		$replacements = array();

		foreach ( $tags as $tag => $data ) {
			$val = self::resolve_tag( $tag, $post );
			$replacements['{' . $tag . '}'] = $val;
		}

		return strtr( $format, $replacements );
	}

	/**
	 * Resolve a specific tag for a given post.
	 */
	public static function resolve_tag( $tag, $post ) {
		$post = get_post( $post );
		if ( ! $post ) return '';

		switch ( $tag ) {
			// CHARTS
			case 'chart_title': return $post->post_title;
			case 'chart_slug': return $post->post_name;
			case 'chart_type': return get_post_meta( $post->ID, '_chart_type', true );
			case 'chart_source': return get_post_meta( $post->ID, '_platform', true );
			case 'chart_market': return get_post_meta( $post->ID, '_country', true );
			case 'chart_frequency': return get_post_meta( $post->ID, '_frequency', true );
			case 'chart_color': return get_post_meta( $post->ID, '_color', true );
			case 'chart_description': return $post->post_content;
			
			// ARTISTS
			case 'artist_name': return $post->post_title;
			case 'artist_slug': return $post->post_name;
			case 'artist_image': return \Charts\Core\PublicIntegration::resolve_artwork( $post, 'artist' );
			case 'artist_spotify_id': return get_post_meta( $post->ID, '_spotify_id', true );
			case 'artist_youtube_id': return get_post_meta( $post->ID, '_channel_id', true );
			case 'artist_popularity': return get_post_meta( $post->ID, '_popularity', true );
			case 'artist_followers': return get_post_meta( $post->ID, '_followers_count', true );
			case 'artist_genres': 
				$genres = get_post_meta( $post->ID, '_genres', true );
				return is_array($genres) ? implode(', ', $genres) : $genres;
			case 'artist_country': return get_post_meta( $post->ID, '_country', true );

			// TRACKS
			case 'track_title': return $post->post_title;
			case 'track_slug': return $post->post_name;
			case 'track_artwork': return \Charts\Core\PublicIntegration::resolve_artwork( $post, 'track' );
			case 'track_spotify_id': return get_post_meta( $post->ID, '_spotify_id', true );
			case 'track_youtube_url': return get_post_meta( $post->ID, '_youtube_url', true );
			case 'track_artist_names': return get_post_meta( $post->ID, '_artist_names', true );
			case 'track_primary_artist': 
				$names = get_post_meta( $post->ID, '_artist_names', true );
				$parts = explode(',', $names);
				return trim($parts[0]);

			// VIDEOS
			case 'video_title': return $post->post_title;
			case 'video_slug': return $post->post_name;
			case 'video_thumbnail': return \Charts\Core\PublicIntegration::resolve_artwork( $post, 'video' );
			case 'video_youtube_id': return get_post_meta( $post->ID, '_youtube_id', true );
			case 'video_url': return 'https://www.youtube.com/watch?v=' . get_post_meta( $post->ID, '_youtube_id', true );
			case 'video_artist_names': return get_post_meta( $post->ID, '_artist_names', true );

			// SHARED / PERFORMANCE
			case 'track_views':
			case 'video_views':
				return get_post_meta( $post->ID, '_view_count_cached', true );
			case 'track_growth':
			case 'video_growth':
				return get_post_meta( $post->ID, '_growth_rate', true ) . '%';
			case 'track_periods_on_chart':
			case 'video_periods_on_chart':
				return get_post_meta( $post->ID, '_total_periods', true );
		}

		return '';
	}

	/**
	 * Handle the [chart_meta] shortcode.
	 */
	public static function handle_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'tag'     => '',
			'post_id' => null,
		), $atts, 'chart_meta' );

		if ( empty( $atts['tag'] ) ) return '';

		$post = get_post( $atts['post_id'] );
		if ( ! $post ) return '';

		return self::resolve_tag( $atts['tag'], $post );
	}

	/**
	 * Integration with theme/widget builders (e.g. Foxiz).
	 */
	public static function integrate_with_foxiz( $meta_tags ) {
		$registry = self::get_registry();
		foreach ( $registry as $type => $tags ) {
			foreach ( $tags as $tag => $data ) {
				$meta_tags[$tag] = array(
					'title'   => $data['label'],
					'replace' => '{' . $tag . '}',
				);
			}
		}
		return $meta_tags;
	}
}
