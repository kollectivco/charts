<?php

namespace Charts\Services;

/**
 * Service to fetch and sync YouTube Charts data directly from YouTube Music / Charts endpoints.
 * Supports:
 * - Top Videos Daily (https://charts.youtube.com/charts/TopVideos/eg/daily)
 * - Top Songs Weekly (https://charts.youtube.com/charts/TopSongs/eg/weekly)
 * - Top Artists Weekly (https://charts.youtube.com/charts/TopArtists/eg/weekly)
 * - Top Videos Weekly (https://charts.youtube.com/charts/TopVideos/eg/weekly)
 */
class YouTubeChartsService {

	const YTM_BROWSE_URL = 'https://music.youtube.com/youtubei/v1/browse';

	/**
	 * Supported YouTube Charts list definitions.
	 */
	public static function get_chart_catalog() {
		return array(
			'top-videos-daily' => array(
				'id'          => 'top-videos-daily',
				'label'       => 'YouTube Top Videos Egypt (Daily)',
				'url'         => 'https://charts.youtube.com/charts/TopVideos/eg/daily',
				'item_type'   => 'video',
				'frequency'   => 'daily',
				'country'     => 'eg',
				'playlist_id' => 'VLPL4fGSI1pDJn7_GnLswAnmeMCCeShoAy2w',
				'target_slug' => 'youtube-top-videos-daily',
			),
			'top-songs-weekly' => array(
				'id'          => 'top-songs-weekly',
				'label'       => 'YouTube Top Songs Egypt (Weekly)',
				'url'         => 'https://charts.youtube.com/charts/TopSongs/eg/weekly',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'playlist_id' => 'VLPL4fGSI1pDJn510j-1L8bMgKTyeRwPrXWY',
				'target_slug' => 'youtube-top-songs-weekly',
			),
			'top-artists-weekly' => array(
				'id'          => 'top-artists-weekly',
				'label'       => 'YouTube Top Artists Egypt (Weekly)',
				'url'         => 'https://charts.youtube.com/charts/TopArtists/eg/weekly',
				'item_type'   => 'artist',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'browse_id'   => 'FEmusic_charts',
				'target_slug' => 'youtube-top-artists-weekly',
			),
			'top-videos-weekly' => array(
				'id'          => 'top-videos-weekly',
				'label'       => 'YouTube Top Videos Egypt (Weekly)',
				'url'         => 'https://charts.youtube.com/charts/TopVideos/eg/weekly',
				'item_type'   => 'video',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'playlist_id' => 'VLPL4fGSI1pDJn4EhpZkSSpdyWUet73FalVU',
				'target_slug' => 'youtube-top-videos-weekly',
			),
		);
	}

	/**
	 * Send authenticated-like request to YouTube Music browse endpoint.
	 *
	 * @param array $payload
	 * @return array|\WP_Error
	 */
	public static function send_browse_request( array $payload ) {
		$body_json = wp_json_encode( $payload );
		$response = wp_remote_post( self::YTM_BROWSE_URL, array(
			'timeout' => 25,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Origin'       => 'https://music.youtube.com',
				'Referer'      => 'https://music.youtube.com/',
			),
			'body'    => $body_json,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new \WP_Error( 'ytm_http_error', sprintf( 'YouTube API responded with HTTP %d', $code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'ytm_parse_error', 'Invalid JSON returned by YouTube API.' );
		}

		return $data;
	}

	/**
	 * Fetch data for a specific YouTube chart.
	 *
	 * @param string $chart_key
	 * @return array|\WP_Error
	 */
	public static function fetch_chart_data( $chart_key = 'top-songs-weekly' ) {
		$catalog = self::get_chart_catalog();
		if ( ! isset( $catalog[ $chart_key ] ) ) {
			return new \WP_Error( 'invalid_chart_key', 'Selected YouTube chart is not recognized.' );
		}

		$chart = $catalog[ $chart_key ];

		if ( $chart['item_type'] === 'artist' ) {
			return self::fetch_top_artists();
		}

		return self::fetch_playlist_chart( $chart['playlist_id'], $chart['item_type'] );
	}

	/**
	 * Fetch Top Artists list from FEmusic_charts.
	 */
	public static function fetch_top_artists() {
		$payload = array(
			'context' => array(
				'client' => array(
					'clientName'    => 'WEB_REMIX',
					'clientVersion' => '1.20231016.01.00',
					'hl'            => 'ar',
					'gl'            => 'EG',
				),
			),
			'browseId' => 'FEmusic_charts',
		);

		$res = self::send_browse_request( $payload );
		if ( is_wp_error( $res ) ) return $res;

		$tabs = $res['contents']['singleColumnBrowseResultsRenderer']['tabs'] ?? array();
		if ( empty( $tabs ) ) {
			return new \WP_Error( 'empty_artists_tab', 'Could not locate chart sections in YouTube Music response.' );
		}

		$sections = $tabs[0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? array();
		$artists_shelf = null;

		foreach ( $sections as $sec ) {
			$carousel = $sec['musicCarouselShelfRenderer'] ?? array();
			$title = '';
			if ( ! empty( $carousel['header']['musicCarouselShelfBasicHeaderRenderer']['title']['runs'] ) ) {
				foreach ( $carousel['header']['musicCarouselShelfBasicHeaderRenderer']['title']['runs'] as $r ) {
					$title .= $r['text'] ?? '';
				}
			}
			if ( strpos( $title, 'الفنّانين' ) !== false || stripos( $title, 'artist' ) !== false ) {
				$artists_shelf = $carousel['contents'] ?? array();
				break;
			}
		}

		if ( empty( $artists_shelf ) ) {
			return new \WP_Error( 'artists_not_found', 'Top Artists section was not found on YouTube Charts.' );
		}

		$items = array();
		foreach ( $artists_shelf as $index => $item_row ) {
			$item = $item_row['musicResponsiveListItemRenderer'] ?? array();
			if ( empty( $item ) ) continue;

			$flex = $item['flexColumns'] ?? array();
			$name = '';
			if ( ! empty( $flex[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ) ) {
				foreach ( $flex[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] as $r ) {
					$name .= $r['text'] ?? '';
				}
			}
			$name = trim( $name );
			if ( empty( $name ) ) continue;

			// Arabize artist name if in Latin/Franco
			$ar_name = class_exists( '\Charts\Core\Transliteration' ) ? \Charts\Core\Transliteration::arabize_text( $name, 'artist' ) : $name;
			$effective_name = ( $ar_name && \Charts\Core\Transliteration::has_arabic( $ar_name ) ) ? $ar_name : $name;
			$name_en = ( $effective_name !== $name ) ? $name : '';

			$sub_text = '';
			if ( ! empty( $flex[1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ) ) {
				foreach ( $flex[1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] as $r ) {
					$sub_text .= $r['text'] ?? '';
				}
			}

			$subscribers = self::parse_subscribers_count( $sub_text );

			$thumbs = $item['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? array();
			$thumb_url = ! empty( $thumbs ) ? end( $thumbs )['url'] : '';

			$rank = $index + 1;

			$items[] = array(
				'rank'               => $rank,
				'title'              => $effective_name,
				'name_en'            => $name_en,
				'artist_names'       => $effective_name,
				'artist_names_en'    => $name_en,
				'image'              => $thumb_url,
				'subscribers'        => $subscribers,
				'score'              => (float) $subscribers,
				'streams'            => $subscribers,
				'views_count'        => $subscribers,
				'youtube_id'         => null,
				'item_type'          => 'artist',
				'movement_direction' => 'same',
				'movement_value'     => 0,
			);
		}

		return $items;
	}

	/**
	 * Fetch track or video items from an official YouTube Chart playlist.
	 *
	 * @param string $playlist_id
	 * @param string $item_type 'track' or 'video'
	 * @return array|\WP_Error
	 */
	public static function fetch_playlist_chart( $playlist_id, $item_type = 'track' ) {
		$payload = array(
			'context' => array(
				'client' => array(
					'clientName'    => 'WEB_REMIX',
					'clientVersion' => '1.20231016.01.00',
					'hl'            => 'ar',
					'gl'            => 'EG',
				),
			),
			'browseId' => $playlist_id,
		);

		$res = self::send_browse_request( $payload );
		if ( is_wp_error( $res ) ) return $res;

		$secondary = $res['contents']['twoColumnBrowseResultsRenderer']['secondaryContents']['sectionListRenderer']['contents'] ?? array();
		if ( empty( $secondary ) ) {
			return new \WP_Error( 'empty_playlist', 'Could not locate tracks playlist in YouTube Music response.' );
		}

		$raw_items = $secondary[0]['musicPlaylistShelfRenderer']['contents'] ?? array();
		if ( empty( $raw_items ) ) {
			return new \WP_Error( 'no_items', 'No chart items found in YouTube playlist.' );
		}

		$items = array();
		foreach ( $raw_items as $index => $item_row ) {
			$item = $item_row['musicResponsiveListItemRenderer'] ?? array();
			if ( empty( $item ) ) continue;

			$flex = $item['flexColumns'] ?? array();
			if ( empty( $flex ) ) continue;

			// 1. Title
			$title = '';
			if ( ! empty( $flex[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ) ) {
				foreach ( $flex[0]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] as $r ) {
					$title .= $r['text'] ?? '';
				}
			}
			$title = trim( $title );
			if ( empty( $title ) ) continue;

			// Arabize title if Latin/Franco
			$ar_title = class_exists( '\Charts\Core\Transliteration' ) ? \Charts\Core\Transliteration::arabize_text( $title, ( $item_type === 'video' ? 'track' : 'track' ) ) : $title;
			$effective_title = ( $ar_title && \Charts\Core\Transliteration::has_arabic( $ar_title ) ) ? $ar_title : $title;
			$title_en = ( $effective_title !== $title ) ? $title : '';

			// 2. Artists from col 1
			$col1_runs = $flex[1]['musicResponsiveListItemFlexColumnRenderer']['text']['runs'] ?? array();
			$artist_names = array();
			foreach ( $col1_runs as $r ) {
				$nav = $r['navigationEndpoint']['browseEndpoint'] ?? array();
				$page_type = $nav['browseEndpointContextSupportedConfigs']['browseEndpointContextMusicConfig']['pageType'] ?? '';
				if ( $page_type === 'MUSIC_PAGE_TYPE_ARTIST' || $page_type === 'MUSIC_PAGE_TYPE_USER_CHANNEL' ) {
					$a_txt = trim( $r['text'] ?? '' );
					if ( ! empty( $a_txt ) && $a_txt !== '•' && $a_txt !== '&' && $a_txt !== 'و' ) {
						$artist_names[] = $a_txt;
					}
				}
			}

			if ( empty( $artist_names ) ) {
				$col1_raw = '';
				foreach ( $col1_runs as $r ) {
					$col1_raw .= $r['text'] ?? '';
				}
				$col1_parts = explode( '•', $col1_raw );
				$raw_artist = trim( $col1_parts[0] ?? '' );
				$artist_names = \Charts\Services\Normalizer::split_artists( $raw_artist );
				if ( empty( $artist_names ) && $raw_artist !== '' ) {
					$artist_names = array( $raw_artist );
				}
			}

			// Arabize each artist name
			$ar_artists = array();
			$en_artists = array();
			foreach ( $artist_names as $a_name ) {
				$ar_a = class_exists( '\Charts\Core\Transliteration' ) ? \Charts\Core\Transliteration::arabize_text( $a_name, 'artist' ) : $a_name;
				$effective_a = ( $ar_a && \Charts\Core\Transliteration::has_arabic( $ar_a ) ) ? $ar_a : $a_name;
				$ar_artists[] = $effective_a;
				if ( $effective_a !== $a_name ) $en_artists[] = $a_name;
			}
			$primary_artist = $ar_artists[0] ?? 'Unknown Artist';
			$artist_str     = implode( ', ', $ar_artists );
			$artist_en_str  = implode( ', ', $en_artists );

			// 3. Video ID
			$play_nav = $item['overlay']['musicItemThumbnailOverlayRenderer']['content']['musicPlayButtonRenderer']['playNavigationEndpoint'] ?? array();
			$video_id = $play_nav['watchEndpoint']['videoId'] ?? '';

			// 4. Thumbnail
			$thumbs = $item['thumbnail']['musicThumbnailRenderer']['thumbnail']['thumbnails'] ?? array();
			$thumb_url = ! empty( $thumbs ) ? end( $thumbs )['url'] : '';

			// 5. Rank and Movement
			$rank = $index + 1;
			$custom_col = $item['customIndexColumn']['musicCustomIndexColumnRenderer'] ?? array();
			$icon_type  = $custom_col['icon']['iconType'] ?? 'ARROW_CHART_NEUTRAL';
			
			$movement_dir = 'same';
			if ( $icon_type === 'ARROW_DROP_UP' ) {
				$movement_dir = 'up';
			} elseif ( $icon_type === 'ARROW_DROP_DOWN' ) {
				$movement_dir = 'down';
			}

			$items[] = array(
				'rank'               => $rank,
				'title'              => $effective_title,
				'title_en'           => $title_en,
				'primary_artist'     => $primary_artist,
				'artists'            => $ar_artists,
				'artist_names'       => $artist_str,
				'artist_names_en'    => $artist_en_str,
				'youtube_id'         => $video_id ?: null,
				'image'              => $thumb_url,
				'item_type'          => $item_type,
				'movement_direction' => $movement_dir,
				'movement_value'     => 0,
				'peak_rank'          => $rank,
				'previous_rank'      => null,
				'weeks_on_chart'     => 1,
				'streams'            => 0,
				'views_count'        => 0,
				'score'              => 0,
			);
		}

		return $items;
	}

	/**
	 * Direct 1-Click Sync of YouTube Chart into plugin database.
	 *
	 * @param string $chart_key Key from get_chart_catalog()
	 * @param int    $chart_id  Destination definition ID
	 * @return array|\WP_Error
	 */
	public static function sync_to_chart( $chart_key, $chart_id = 0 ) {
		global $wpdb;

		$catalog = self::get_chart_catalog();
		if ( ! isset( $catalog[ $chart_key ] ) ) {
			return new \WP_Error( 'invalid_yt_chart', __( 'Select a valid YouTube chart list.', 'charts' ) );
		}

		$chart = $catalog[ $chart_key ];
		$items = self::fetch_chart_data( $chart_key );
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		if ( empty( $items ) ) {
			return new \WP_Error( 'no_items_fetched', __( 'No entries were retrieved from YouTube Charts.', 'charts' ) );
		}

		// 1. Ensure Destination Chart Definition exists
		$source_table = $wpdb->prefix . 'charts_sources';
		$source_mgr   = new \Charts\Admin\SourceManager();
		$definition   = $chart_id ? $source_mgr->get_definition( $chart_id ) : null;
		if ( ! $definition && ! empty( $chart['target_slug'] ) ) {
			$definition = $source_mgr->get_definition_by_slug( $chart['target_slug'] );
			if ( $definition ) {
				$chart_id = (int) $definition->id;
			}
		}

		// Auto-create definition if none exists yet for this YouTube chart
		if ( ! $definition ) {
			$def_data = array(
				'title'          => $chart['label'],
				'title_ar'       => $chart['label'],
				'slug'           => $chart['target_slug'],
				'item_type'      => $chart['item_type'],
				'chart_type'     => $chart['item_type'] === 'artist' ? 'top-artists' : ( $chart['item_type'] === 'video' ? 'top-videos' : 'top-songs' ),
				'platform'       => 'youtube',
				'country_code'   => 'eg',
				'frequency'      => $chart['frequency'],
				'is_public'      => 1,
				'accent_color'   => '#FF0000',
				'chart_summary'  => 'Official ' . $chart['label'] . ' by YouTube Charts',
			);
			$new_def_id = $source_mgr->save_definition( $def_data );
			if ( $new_def_id ) {
				$definition = $source_mgr->get_definition( $new_def_id );
				$chart_id   = (int) $new_def_id;
			}
		}

		if ( $definition && $definition->item_type !== $chart['item_type'] ) {
			return new \WP_Error( 'target_mismatch', __( 'Choose a destination chart with the same item type as the selected YouTube chart.', 'charts' ) );
		}

		$target_chart_type = $definition ? 'cid-' . (int) $definition->id : ( $chart['item_type'] === 'artist' ? 'top-artists' : 'top-songs' );
		$source_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $source_table WHERE platform = 'youtube' AND chart_type = %s LIMIT 1",
			$target_chart_type
		) );
		$source_name = $definition ? 'YouTube — ' . $definition->title : $chart['label'];
		$source_url  = $chart['url'];

		if ( ! $source_id ) {
			$wpdb->insert( $source_table, array(
				'source_name'  => $source_name,
				'platform'     => 'youtube',
				'source_type'  => 'api',
				'source_url'   => $source_url,
				'country_code' => 'eg',
				'frequency'    => $chart['frequency'],
				'chart_type'   => $target_chart_type,
				'parser_key'   => 'youtube-live-' . $chart_key,
				'is_active'    => 1,
				'created_at'   => current_time( 'mysql' ),
			) );
			$source_id = $wpdb->insert_id;
		} else {
			$wpdb->update( $source_table, array(
				'source_name'  => $source_name,
				'source_type'  => 'api',
				'source_url'   => $source_url,
				'country_code' => 'eg',
				'frequency'    => $chart['frequency'],
				'parser_key'   => 'youtube-live-' . $chart_key,
				'is_active'    => 1,
			), array( 'id' => $source_id ) );
		}
		if ( ! $source_id ) return new \WP_Error( 'source_failed', __( 'Could not create the YouTube source.', 'charts' ) );

		// Deactivate any other active sources bound to this same definition
		if ( $definition ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE $source_table SET is_active = 0 WHERE chart_type = %s AND id != %d",
				$target_chart_type, $source_id
			) );
		}

		// 2. Ensure Period
		$import_flow = new ImportFlow();
		$published_at = current_time( 'Y-m-d' );
		$period_id = $import_flow->ensure_period( $chart['frequency'], $published_at );
		if ( ! $period_id ) return new \WP_Error( 'period_failed', __( 'Could not create chart period.', 'charts' ) );

		// Clean slate wipe so no leftover items
		$import_flow->wipe_period( $source_id, $period_id );

		// 3. Process entries
		$imported_count = 0;
		foreach ( $items as $row ) {
			if ( $row['item_type'] === 'artist' ) {
				$artist_id = \Charts\Core\EntityManager::ensure_artist( $row['title'], array(
					'image'           => $row['image'],
					'display_name_en' => ! empty( $row['name_en'] ) ? $row['name_en'] : null,
				) );
				if ( ! $artist_id ) continue;

				if ( ! empty( $row['image'] ) ) {
					$artist_image = $wpdb->get_var( $wpdb->prepare( "SELECT image FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
					if ( empty( $artist_image ) ) $wpdb->update( $wpdb->prefix . 'charts_artists', array( 'image' => $row['image'] ), array( 'id' => $artist_id ) );
				}

				$artist_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
				$flat = array(
					'track_name'      => $row['title'],
					'track_name_en'   => $row['name_en'] ?? '',
					'artist_names'    => $row['title'],
					'artist_names_en' => $row['name_en'] ?? '',
					'cover_image'     => $row['image'],
					'item_slug'       => $artist_slug,
					'streams'         => $row['streams'],
					'views_count'     => $row['views_count'],
					'score'           => $row['score'],
				);
				$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'artist', $artist_id, $row, $flat );
				if ( $entry_id ) $imported_count++;
				continue;
			}

			if ( $row['item_type'] === 'video' ) {
				$primary_artist_id = \Charts\Core\EntityManager::ensure_artist( $row['primary_artist'] );
				$video_id = \Charts\Core\EntityManager::ensure_video( $row['title'], $primary_artist_id, array(
					'thumbnail'  => $row['image'],
					'youtube_id' => $row['youtube_id'] ?? null,
				) );

				if ( ! empty( $row['artists'] ) && count( $row['artists'] ) > 1 ) {
					$a_ids = array();
					foreach ( $row['artists'] as $a_name ) {
						$a_id = \Charts\Core\EntityManager::ensure_artist( trim( $a_name ) );
						if ( $a_id ) $a_ids[] = $a_id;
					}
					if ( ! empty( $a_ids ) ) {
						\Charts\Core\EntityManager::link_artists( $video_id, $a_ids, 'video' );
					}
				}

				if ( ! $video_id ) continue;

				$video_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_videos WHERE id = %d", $video_id ) );
				$flat = array(
					'track_name'      => $row['title'],
					'track_name_en'   => $row['title_en'] ?? '',
					'artist_names'    => $row['artist_names'],
					'artist_names_en' => $row['artist_names_en'] ?? '',
					'cover_image'     => $row['image'],
					'item_slug'       => $video_slug,
					'youtube_id'      => $row['youtube_id'] ?? null,
					'streams'         => $row['streams'],
					'views_count'     => $row['views_count'],
					'score'           => $row['score'],
				);
				$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'video', $video_id, $row, $flat );
				if ( $entry_id ) $imported_count++;
				continue;
			}

			// Track mode
			$primary_artist_id = \Charts\Core\EntityManager::ensure_artist( $row['primary_artist'], array(
				'image' => $row['image'],
			) );

			$track_id = \Charts\Core\EntityManager::ensure_track( $row['title'], $primary_artist_id, array(
				'cover_image' => $row['image'],
				'title_en'    => ! empty( $row['title_en'] ) ? $row['title_en'] : null,
			) );

			if ( $track_id && ! empty( $row['youtube_id'] ) ) {
				$curr_yt = $wpdb->get_var( $wpdb->prepare( "SELECT youtube_id FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $track_id ) );
				if ( empty( $curr_yt ) ) {
					$wpdb->update( $wpdb->prefix . 'charts_tracks', array( 'youtube_id' => $row['youtube_id'] ), array( 'id' => $track_id ) );
				}
			}

			if ( ! empty( $row['artists'] ) && count( $row['artists'] ) > 1 ) {
				$a_ids = array();
				foreach ( $row['artists'] as $a_name ) {
					$a_id = \Charts\Core\EntityManager::ensure_artist( trim( $a_name ) );
					if ( $a_id ) $a_ids[] = $a_id;
				}
				if ( ! empty( $a_ids ) ) {
					\Charts\Core\EntityManager::link_artists( $track_id, $a_ids, 'track' );
				}
			}

			if ( ! $track_id ) continue;

			$track_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $track_id ) );
			$flat = array(
				'track_name'      => $row['title'],
				'track_name_en'   => $row['title_en'] ?? '',
				'artist_names'    => $row['artist_names'],
				'artist_names_en' => $row['artist_names_en'] ?? '',
				'cover_image'     => $row['image'],
				'item_slug'       => $track_slug,
				'youtube_id'      => $row['youtube_id'] ?? null,
				'streams'         => $row['streams'],
				'views_count'     => $row['views_count'],
				'score'           => $row['score'],
			);
			$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'track', $track_id, $row, $flat );
			if ( $entry_id ) $imported_count++;
		}

		// Recalculate Intelligence
		\Charts\Core\Intelligence::recalculate_all();

		return array(
			'success'        => true,
			'imported_count' => $imported_count,
			'total_items'    => count( $items ),
			'chart_key'      => $chart_key,
			'target_chart_id'=> $chart_id,
			'item_type'      => $chart['item_type'],
			'published_at'   => $published_at,
		);
	}

	/**
	 * Parse string subscriber count to integer.
	 */
	public static function parse_subscribers_count( $text ) {
		$text = str_replace( ',', '.', trim( $text ) );
		if ( preg_match( '/([\d.]+)\s*(M|K|مليون|ألف)?/ui', $text, $matches ) ) {
			$val  = floatval( $matches[1] );
			$unit = mb_strtolower( $matches[2] ?? '' );
			if ( in_array( $unit, array( 'm', 'مليون' ), true ) ) {
				return (int) ( $val * 1000000 );
			} elseif ( in_array( $unit, array( 'k', 'ألف' ), true ) ) {
				return (int) ( $val * 1000 );
			}
			return (int) $val;
		}
		return 0;
	}
}
