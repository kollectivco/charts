<?php

namespace Charts\Services;

/**
 * Service to fetch, sync, and export Billboard Arabia Hot 100 data.
 * Directly communicates with Billboard Arabia's official REST API:
 * - sys.billboardarabia.com/api/list-weeks
 * - sys.billboardarabia.com/api/songs-charts?type=1&week={weekID}
 * - sys.billboardarabia.com/storage/songs/{image}
 */
class BillboardService {

	const API_BASE = 'https://sys.billboardarabia.com/api/';
	const IMG_BASE = 'https://sys.billboardarabia.com/storage/songs/';
	const ARTIST_IMG_BASE = 'https://sys.billboardarabia.com/storage/artists/';

	/** Supported Billboard Arabia lists, keyed by Billboard's API chart ID. */
	public static function get_chart_catalog() {
		return array(
			1  => array( 'label' => 'Billboard Arabia Hot 100', 'item_type' => 'track', 'endpoint' => 'songs-charts', 'target_slug' => 'billboard-arabia-hot-100' ),
			2  => array( 'label' => 'Billboard Arabia 100 Artists', 'item_type' => 'artist', 'endpoint' => 'artists-charts', 'target_slug' => 'billboard-arabia-100-artists' ),
			8  => array( 'label' => 'Top 50 Indie', 'item_type' => 'track', 'endpoint' => 'songs-charts', 'target_slug' => 'top-50-indie' ),
			10 => array( 'label' => 'Top 50 Mahraganat', 'item_type' => 'track', 'endpoint' => 'songs-charts', 'target_slug' => 'top-50-mahraganat' ),
		);
	}

	/**
	 * Get list of available published weeks.
	 *
	 * @param int  $chart_id Billboard API chart ID.
	 * @param bool $force_refresh
	 * @return array
	 */
	public static function get_weeks( $chart_id = 1, $force_refresh = false ) {
		if ( is_bool( $chart_id ) ) {
			$force_refresh = $chart_id;
			$chart_id = 1;
		}
		$chart_id = isset( self::get_chart_catalog()[ (int) $chart_id ] ) ? (int) $chart_id : 1;
		$cache_key = 'charts_bb_weeks_' . $chart_id;
		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( ! empty( $cached ) && is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get( self::API_BASE . 'list-weeks?chart_id=' . $chart_id, array(
			'timeout'    => 15,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return array();
		}

		$weeks = array();
		foreach ( $body['data'] as $w ) {
			if ( empty( $w['weekID'] ) ) continue;

			$pub_date = ! empty( $w['published_at'] ) ? $w['published_at'] : '';
			$formatted_date = $pub_date;
			if ( $pub_date ) {
				$ts = strtotime( $pub_date );
				$formatted_date = date_i18n( 'd F Y', $ts );
			}

			$weeks[] = array(
				'week_id'      => intval( $w['weekID'] ),
				'published_at' => $pub_date,
				'label'        => $formatted_date ? "{$formatted_date} (Week {$w['weekID']})" : "Week {$w['weekID']}",
			);
		}

		// Cache for 2 hours
		set_transient( $cache_key, $weeks, 7200 );
		return $weeks;
	}

	/**
	 * Fetch raw chart entries from Billboard API for a given week.
	 *
	 * @param int|null $week_id
	 * @return array
	 */
	public static function fetch_chart_data( $week_id = null, $chart_id = 1 ) {
		$catalog = self::get_chart_catalog();
		$chart_id = isset( $catalog[ (int) $chart_id ] ) ? (int) $chart_id : 1;
		$chart = $catalog[ $chart_id ];
		if ( empty( $week_id ) ) {
			$weeks = self::get_weeks( $chart_id );
			$week_id = ! empty( $weeks[0]['week_id'] ) ? $weeks[0]['week_id'] : 202639;
		}

		$api_type = $chart['item_type'] === 'artist' ? 2 : $chart_id;
		$url = self::API_BASE . $chart['endpoint'] . '?type=' . $api_type . '&week=' . intval( $week_id );
		$response = wp_remote_get( $url, array(
			'timeout'    => 25,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return array();
		}

		$items = array();
		foreach ( $body['data'] as $item ) {
			if ( $chart['item_type'] === 'artist' ) {
				$name = trim( $item['arabic_name'] ?? $item['english_name'] ?? '' );
				if ( $name === '' ) continue;
				$image_name = $item['artist_details']['image'] ?? $item['image'] ?? '';
				if ( $image_name === '' && ! empty( $item['english_name'] ) ) $image_name = $item['english_name'] . '.jpg';
				$image_url = $image_name ? self::sideload_image( self::ARTIST_IMG_BASE . ltrim( $image_name, '/' ) ) : '';
				$stats = $item['artist_stats'] ?? array();
				$items[] = array(
					'item_type' => 'artist',
					'rank' => intval( $item['rank'] ?? 0 ),
					'title' => $name,
					'arabic_title' => $item['arabic_name'] ?? $name,
					'english_title' => $item['english_name'] ?? '',
					'primary_artist' => $name,
					'arabic_artist' => $item['arabic_name'] ?? $name,
					'english_artist' => $item['english_name'] ?? '',
					'artists' => array( $name ),
					'image' => $image_url,
					'previous_rank' => isset( $stats['last_week_rank'] ) && intval( $stats['last_week_rank'] ) > 0 ? intval( $stats['last_week_rank'] ) : null,
					'peak_rank' => intval( $stats['peak_rank'] ?? $item['rank'] ?? 0 ),
					'weeks_on_chart' => intval( $stats['weeks_in_chart'] ?? 1 ),
					'streams' => intval( $item['total'] ?? 0 ),
					'nb_top_one_weeks' => intval( $stats['nb_top_one_weeks'] ?? 0 ),
					'published_at' => $item['published_at'] ?? '',
				);
				continue;
			}
			$arabic_title  = trim( $item['arabic_title'] ?? '' );
			$english_title = trim( $item['english_title'] ?? '' );
			$title         = ! empty( $arabic_title ) ? $arabic_title : $english_title;

			$arabic_artist  = trim( $item['arabic_artist_name'] ?? '' );
			$english_artist = trim( $item['english_artist_name'] ?? '' );
			$primary_artist = ! empty( $arabic_artist ) ? $arabic_artist : $english_artist;

			// Split artists if comma or '،' separated
			$artists = array();
			if ( ! empty( $item['artists'] ) && is_array( $item['artists'] ) ) {
				foreach ( $item['artists'] as $art ) {
					$name = trim( $art['arabic_name'] ?? $art['english_name'] ?? '' );
					if ( $name ) $artists[] = $name;
				}
			}
			if ( empty( $artists ) ) {
				$artists = preg_split( '/[،,]\s*/u', $primary_artist );
			}

			// Image URL
			$image_url = '';
			if ( ! empty( $item['image'] ) ) {
				$image_url = self::sideload_image( self::IMG_BASE . ltrim( $item['image'], '/' ) );
			}

			// Stats
			$stats = $item['song_stats'] ?? array();
			$last_week_rank = isset( $stats['last_week_rank'] ) && is_numeric( $stats['last_week_rank'] ) && $stats['last_week_rank'] > 0
				? intval( $stats['last_week_rank'] ) : null;
			$peak_rank = isset( $stats['peak_rank'] ) && is_numeric( $stats['peak_rank'] )
				? intval( $stats['peak_rank'] ) : intval( $item['rank'] );
			$weeks_in_chart = isset( $stats['weeks_in_chart'] ) && is_numeric( $stats['weeks_in_chart'] )
				? intval( $stats['weeks_in_chart'] ) : 1;

			$items[] = array(
				'item_type'        => 'track',
				'rank'             => intval( $item['rank'] ),
				'title'            => $title,
				'arabic_title'     => $arabic_title,
				'english_title'    => $english_title,
				'primary_artist'   => $primary_artist,
				'arabic_artist'    => $arabic_artist,
				'english_artist'   => $english_artist,
				'artists'          => $artists,
				'image'            => $image_url,
				'previous_rank'    => $last_week_rank,
				'peak_rank'        => $peak_rank,
				'weeks_on_chart'   => $weeks_in_chart,
				'streams'          => intval( $item['total'] ?? 0 ),
				'nb_top_one_weeks' => intval( $stats['nb_top_one_weeks'] ?? 0 ),
				'published_at'     => $item['published_at'] ?? '',
			);
		}

		return $items;
	}

	public static function sideload_image( $url ) {
		if ( empty( $url ) ) return '';
		
		// If it's already a local URL, skip
		$path = parse_url( $url, PHP_URL_PATH );
		if ( ! $path || strpos( $url, 'https://sys.billboardarabia.com/storage/' ) !== 0 ) return $url;

		$upload_dir = wp_upload_dir();
		$charts_dir = $upload_dir['basedir'] . '/charts-media/kcharts';
		if ( ! file_exists( $charts_dir ) ) {
			wp_mkdir_p( $charts_dir );
		}

		$filename = basename( $path );
		if ( empty( $filename ) ) $filename = md5( $url ) . '.jpg';

		// Avoid redownloading if exists
		$filepath = $charts_dir . '/' . $filename;
		$fileurl  = $upload_dir['baseurl'] . '/charts-media/kcharts/' . $filename;
		if ( file_exists( $filepath ) ) {
			return $fileurl;
		}

		$response = wp_remote_get( $url, array(
			'timeout'    => 15,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
		) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = wp_remote_retrieve_body( $response );
			if ( $body ) {
				file_put_contents( $filepath, $body );
				return $fileurl;
			}
		}

		return $url;
	}

	/**
	 * Perform Direct 1-Click Sync of Billboard Arabia data into plugin database.
	 *
	 * @param int      $week_id
	 * @param int      $chart_id Local Charts definition ID (destination).
	 * @param int      $billboard_chart_id Billboard API chart ID (source).
	 * @return array|\WP_Error
	 */
	public static function sync_to_chart( $week_id, $chart_id = 0, $billboard_chart_id = 1 ) {
		global $wpdb;

		$catalog = self::get_chart_catalog();
		$billboard_chart_id = isset( $catalog[ (int) $billboard_chart_id ] ) ? (int) $billboard_chart_id : 1;
		$chart = $catalog[ $billboard_chart_id ];
		$items = self::fetch_chart_data( $week_id, $billboard_chart_id );
		if ( empty( $items ) ) {
			return new \WP_Error( 'fetch_failed', __( 'Could not retrieve data from Billboard Arabia API. Please check your connection or week ID.', 'charts' ) );
		}

		$published_at = ! empty( $items[0]['published_at'] ) ? $items[0]['published_at'] : current_time( 'Y-m-d' );

		// 1. Ensure Billboard Source exists
		$source_table = $wpdb->prefix . 'charts_sources';
		$definition = $chart_id ? ( new \Charts\Admin\SourceManager() )->get_definition( $chart_id ) : null;
		if ( $chart_id && ( ! $definition || $definition->item_type !== $chart['item_type'] ) ) {
			return new \WP_Error( 'billboard_target_mismatch', __( 'Choose a destination chart with the same item type as the selected Billboard list.', 'charts' ) );
		}
		$target_chart_type = $definition ? 'cid-' . (int) $definition->id : ( $chart['item_type'] === 'artist' ? 'top-artists' : 'top-songs' );
		$source_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $source_table WHERE platform = 'billboard' AND chart_type = %s LIMIT 1",
			$target_chart_type
		) );
		$source_name = $definition ? 'Billboard Arabia — ' . $definition->title : $chart['label'];
		$source_url = 'https://www.billboardarabia.com/' . ( $chart['item_type'] === 'artist' ? 'artist-view' : ( $billboard_chart_id === 8 ? 'top-50-indie' : ( $billboard_chart_id === 10 ? 'top-50-mahraganat' : 'charts/' ) ) );

		if ( ! $source_id ) {
			$wpdb->insert( $source_table, array(
				'source_name'  => $source_name,
				'platform'     => 'billboard',
				'source_type'  => 'api',
				'source_url'   => $source_url,
				'country_code' => $definition ? $definition->country_code : 'global',
				'frequency'    => 'weekly',
				'chart_type'   => $target_chart_type,
				'parser_key'   => 'billboard-' . $billboard_chart_id,
				'is_active'    => 1,
			) );
			$source_id = $wpdb->insert_id;
		} else {
			$wpdb->update( $source_table, array(
				'source_name' => $source_name,
				'source_type' => 'api',
				'source_url' => $source_url,
				'country_code' => $definition ? $definition->country_code : 'global',
				'frequency' => 'weekly',
				'parser_key' => 'billboard-' . $billboard_chart_id,
				'is_active' => 1,
			), array( 'id' => $source_id ) );
		}
		if ( ! $source_id ) return new \WP_Error( 'billboard_source_failed', __( 'Could not create the Billboard source.', 'charts' ) );

		// 2. Ensure Period for this week
		$import_flow = new ImportFlow();
		$period_id = $import_flow->ensure_period( 'weekly', $published_at );

		// 3. Process entries
		$imported_count = 0;
		foreach ( $items as $row ) {
			if ( $row['item_type'] === 'artist' ) {
				$artist_id = \Charts\Core\EntityManager::ensure_artist( $row['title'], array( 'image' => $row['image'] ) );
				if ( ! $artist_id ) continue;
				if ( ! empty( $row['image'] ) ) {
					$artist_image = $wpdb->get_var( $wpdb->prepare( "SELECT image FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
					if ( empty( $artist_image ) ) $wpdb->update( $wpdb->prefix . 'charts_artists', array( 'image' => $row['image'] ), array( 'id' => $artist_id ) );
				}
				$flat = array( 'track_name' => $row['title'], 'artist_names' => $row['title'], 'cover_image' => $row['image'] );
				$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'artist', $artist_id, $row, $flat );
				if ( $entry_id ) $imported_count++;
				continue;
			}
			$title = $row['title'];
			$primary_artist = ! empty( $row['artists'][0] ) ? $row['artists'][0] : $row['primary_artist'];

			// Ensure Artist
			$artist_id = \Charts\Core\EntityManager::ensure_artist( $primary_artist, array(
				'image' => $row['image']
			) );

			// Ensure Track
			$track_id = \Charts\Core\EntityManager::ensure_track( $title, $artist_id, array(
				'cover_image' => $row['image']
			) );

			// If track exists but has no cover_image, update it with Billboard HD image
			if ( $track_id && ! empty( $row['image'] ) ) {
				$curr_img = $wpdb->get_var( $wpdb->prepare(
					"SELECT cover_image FROM {$wpdb->prefix}charts_tracks WHERE id = %d",
					$track_id
				) );
				if ( empty( $curr_img ) ) {
					$wpdb->update(
						$wpdb->prefix . 'charts_tracks',
						array( 'cover_image' => $row['image'] ),
						array( 'id' => $track_id )
					);
				}
			}

			// Link all secondary artists
			if ( $track_id && ! empty( $row['artists'] ) && count( $row['artists'] ) > 1 ) {
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

			$flat = array(
				'track_name'   => $row['title'],
				'artist_names' => implode( ', ', $row['artists'] ),
				'cover_image'  => $row['image'],
				'spotify_id'   => null,
				'youtube_id'   => null,
				'streams'      => $row['streams'],
			);

			$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'track', $track_id, $row, $flat );
			if ( $entry_id ) {
				try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
				$imported_count++;
			}
		}

		// 4. Recalculate Intelligence
		\Charts\Core\Intelligence::recalculate_all();

		return array(
			'success'        => true,
			'imported_count' => $imported_count,
			'total_items'    => count( $items ),
			'week_id'        => $week_id,
			'billboard_chart_id' => $billboard_chart_id,
			'target_chart_id' => $chart_id,
			'item_type'      => $chart['item_type'],
			'published_at'   => $published_at,
		);
	}

	/**
	 * Stream-download Billboard Arabia Chart as UTF-8 CSV with Excel BOM.
	 *
	 * @param int $week_id
	 * @param int $billboard_chart_id
	 */
	public static function download_csv( $week_id, $billboard_chart_id = 1 ) {
		$catalog = self::get_chart_catalog();
		$billboard_chart_id = isset( $catalog[ (int) $billboard_chart_id ] ) ? (int) $billboard_chart_id : 1;
		$chart = $catalog[ $billboard_chart_id ];
		$items = self::fetch_chart_data( $week_id, $billboard_chart_id );
		if ( empty( $items ) ) {
			wp_die( 'Could not fetch Billboard Arabia data for this week.' );
		}

		$filename = sanitize_file_name( 'billboard-arabia-' . strtolower( str_replace( ' ', '-', $chart['label'] ) ) . '-' . $week_id . '.csv' );

		// Clean output buffer
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM for Arabic text in Excel
		fwrite( $output, "\xEF\xBB\xBF" );

		// CSV Header
		fputcsv( $output, $chart['item_type'] === 'artist' ? array(
			'rank', 'artist', 'streams', 'peak_rank', 'previous_rank', 'weeks_on_chart', 'image', 'arabic_artist', 'english_artist',
		) : array(
			'rank',
			'track_name',
			'artist_names',
			'cover_image',
			'peak_rank',
			'previous_rank',
			'weeks_on_chart',
			'streams',
			'arabic_title',
			'english_title',
			'arabic_artist',
			'english_artist',
		) );

		foreach ( $items as $row ) {
			fputcsv( $output, $chart['item_type'] === 'artist' ? array(
				$row['rank'], $row['title'], $row['streams'], $row['peak_rank'], $row['previous_rank'] !== null ? $row['previous_rank'] : '', $row['weeks_on_chart'], $row['image'], $row['arabic_artist'], $row['english_artist'],
			) : array(
				$row['rank'],
				$row['title'],
				implode( ', ', $row['artists'] ),
				$row['image'],
				$row['peak_rank'],
				$row['previous_rank'] !== null ? $row['previous_rank'] : '',
				$row['weeks_on_chart'],
				$row['streams'],
				$row['arabic_title'],
				$row['english_title'],
				$row['arabic_artist'],
				$row['english_artist'],
			) );
		}

		fclose( $output );
		exit;
	}
}
