<?php

namespace Charts\Services;

use Charts\Admin\SourceManager;
use Charts\Core\EntityManager;
use Charts\Core\Intelligence;
use Charts\Core\Transliteration;

/**
 * Service to fetch and sync Spotify Charts & Curated Playlists directly into the Charts Engine.
 * Supports:
 * Official Charts:
 * - Top Songs Weekly Egypt (https://charts.spotify.com/charts/view/regional-eg-weekly/latest)
 * - Top Artists Weekly Egypt (https://charts.spotify.com/charts/view/artist-eg-weekly/latest)
 * - Top Songs Daily Egypt (https://charts.spotify.com/charts/view/regional-eg-daily/latest)
 * - Top Artists Daily Egypt (https://charts.spotify.com/charts/view/artist-eg-daily/latest)
 * 
 * Curated Playlists:
 * - جديد السين (https://open.spotify.com/playlist/37i9dQZF1DWVyuCU4h3DLu)
 * - ملوك السين (https://open.spotify.com/playlist/37i9dQZF1DWZyonhntyFxW)
 * - التوب (https://open.spotify.com/playlist/37i9dQZF1DXd3AhRYJnfcl)
 * - أقوى المهرجانات (https://open.spotify.com/playlist/37i9dQZF1DX4qF0846GNk8)
 * - أغاني تريند (https://open.spotify.com/playlist/37i9dQZF1DXaGui85Swluy)
 * - توب مصر (https://open.spotify.com/playlist/4EHXh4VxKexUJlGRu9aQw5)
 * - توب إندي (https://open.spotify.com/playlist/1GUn9u8EPuGNMGNh0eMl3K)
 * - أغاني قديمة (https://open.spotify.com/playlist/4RtMN0jjUY0TeMIZTJ0tRt)
 */
class SpotifySyncService {

	const SPOTIFY_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

	/**
	 * Supported Spotify catalog list definitions (Charts & Playlists).
	 *
	 * @return array
	 */
	public static function get_catalog() {
		return array(
			// --- Official Spotify Charts ---
			'regional-eg-weekly' => array(
				'id'          => 'regional-eg-weekly',
				'label'       => 'Spotify Top Songs Egypt (Weekly) — ويك إيجيبت',
				'url'         => 'https://charts.spotify.com/charts/view/regional-eg-weekly/latest',
				'category'    => 'charts',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-top-songs-weekly',
			),
			'artist-eg-weekly' => array(
				'id'          => 'artist-eg-weekly',
				'label'       => 'Spotify Top Artists Egypt (Weekly) — توب أرتيست',
				'url'         => 'https://charts.spotify.com/charts/view/artist-eg-weekly/latest',
				'category'    => 'charts',
				'item_type'   => 'artist',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-top-artists-weekly',
			),
			'regional-eg-daily' => array(
				'id'          => 'regional-eg-daily',
				'label'       => 'Spotify Top Songs Egypt (Daily) — ديلي توب سونج',
				'url'         => 'https://charts.spotify.com/charts/view/regional-eg-daily/latest',
				'category'    => 'charts',
				'item_type'   => 'track',
				'frequency'   => 'daily',
				'country'     => 'eg',
				'target_slug' => 'spotify-top-songs-daily',
			),
			'artist-eg-daily' => array(
				'id'          => 'artist-eg-daily',
				'label'       => 'Spotify Top Artists Egypt (Daily) — ديلي توب أرتيست',
				'url'         => 'https://charts.spotify.com/charts/view/artist-eg-daily/latest',
				'category'    => 'charts',
				'item_type'   => 'artist',
				'frequency'   => 'daily',
				'country'     => 'eg',
				'target_slug' => 'spotify-top-artists-daily',
			),

			// --- Curated Spotify Playlists ---
			'playlist-37i9dQZF1DWVyuCU4h3DLu' => array(
				'id'          => 'playlist-37i9dQZF1DWVyuCU4h3DLu',
				'label'       => 'جديد السين — Jadeed El Scene (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DWVyuCU4h3DLu',
				'playlist_id' => '37i9dQZF1DWVyuCU4h3DLu',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-jadeed-el-scene',
			),
			'playlist-37i9dQZF1DWZyonhntyFxW' => array(
				'id'          => 'playlist-37i9dQZF1DWZyonhntyFxW',
				'label'       => 'ملوك السين — Molouk El Scene (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DWZyonhntyFxW',
				'playlist_id' => '37i9dQZF1DWZyonhntyFxW',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-molouk-el-scene',
			),
			'playlist-37i9dQZF1DXd3AhRYJnfcl' => array(
				'id'          => 'playlist-37i9dQZF1DXd3AhRYJnfcl',
				'label'       => 'التوب — El Top (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DXd3AhRYJnfcl',
				'playlist_id' => '37i9dQZF1DXd3AhRYJnfcl',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-el-top',
			),
			'playlist-37i9dQZF1DX4qF0846GNk8' => array(
				'id'          => 'playlist-37i9dQZF1DX4qF0846GNk8',
				'label'       => 'أقوى المهرجانات — Aqwa El Mahraganat (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DX4qF0846GNk8',
				'playlist_id' => '37i9dQZF1DX4qF0846GNk8',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-aqwa-el-mahraganat',
			),
			'playlist-37i9dQZF1DXaGui85Swluy' => array(
				'id'          => 'playlist-37i9dQZF1DXaGui85Swluy',
				'label'       => 'أغاني تريند — Aghani Trend (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/37i9dQZF1DXaGui85Swluy',
				'playlist_id' => '37i9dQZF1DXaGui85Swluy',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-aghani-trend',
			),
			'playlist-4EHXh4VxKexUJlGRu9aQw5' => array(
				'id'          => 'playlist-4EHXh4VxKexUJlGRu9aQw5',
				'label'       => 'توب مصر — Top Egyptian Songs (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/4EHXh4VxKexUJlGRu9aQw5',
				'playlist_id' => '4EHXh4VxKexUJlGRu9aQw5',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-top-egypt',
			),
			'playlist-1GUn9u8EPuGNMGNh0eMl3K' => array(
				'id'          => 'playlist-1GUn9u8EPuGNMGNh0eMl3K',
				'label'       => 'توب إندي — Arabic Indie & Alternative (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/1GUn9u8EPuGNMGNh0eMl3K',
				'playlist_id' => '1GUn9u8EPuGNMGNh0eMl3K',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-top-indie',
			),
			'playlist-4RtMN0jjUY0TeMIZTJ0tRt' => array(
				'id'          => 'playlist-4RtMN0jjUY0TeMIZTJ0tRt',
				'label'       => 'أغاني قديمة — Arabic 90s & 00s (Spotify Playlist)',
				'url'         => 'https://open.spotify.com/playlist/4RtMN0jjUY0TeMIZTJ0tRt',
				'playlist_id' => '4RtMN0jjUY0TeMIZTJ0tRt',
				'category'    => 'playlist',
				'item_type'   => 'track',
				'frequency'   => 'weekly',
				'country'     => 'eg',
				'target_slug' => 'spotify-playlist-aghani-qadima',
			),
		);
	}

	/**
	 * Fetch data for a specific Spotify item (Chart or Playlist).
	 *
	 * @param string $item_key
	 * @return array|\WP_Error
	 */
	public static function fetch_item_data( $item_key ) {
		$catalog = self::get_catalog();
		if ( ! isset( $catalog[ $item_key ] ) ) {
			return new \WP_Error( 'invalid_spotify_key', __( 'Selected Spotify item is not recognized.', 'charts' ) );
		}

		$item = $catalog[ $item_key ];

		if ( $item['category'] === 'playlist' ) {
			return self::fetch_playlist_chart( $item['playlist_id'], $item['label'] );
		}

		return self::fetch_official_chart( $item['id'] );
	}

	/**
	 * Fetch tracks from a Spotify curated playlist using embed JSON.
	 *
	 * @param string $playlist_id
	 * @param string $playlist_label
	 * @return array|\WP_Error
	 */
	public static function fetch_playlist_chart( $playlist_id, $playlist_label = '' ) {
		$url = "https://open.spotify.com/embed/playlist/{$playlist_id}";
		$response = wp_remote_get( $url, array(
			'timeout' => 25,
			'headers' => array(
				'User-Agent' => self::SPOTIFY_USER_AGENT,
				'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! preg_match( '/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $body, $matches ) ) {
			return new \WP_Error( 'parse_error', __( 'Could not parse playlist JSON structure from Spotify embed.', 'charts' ) );
		}

		$json = json_decode( $matches[1], true );
		if ( empty( $json ) || ! is_array( $json ) ) {
			return new \WP_Error( 'invalid_json', __( 'Invalid playlist JSON returned by Spotify.', 'charts' ) );
		}

		$entity = $json['props']['pageProps']['state']['data']['entity'] ?? array();
		$track_list = $entity['trackList'] ?? array();

		if ( empty( $track_list ) ) {
			return new \WP_Error( 'empty_playlist', __( 'No tracks found in the selected Spotify playlist.', 'charts' ) );
		}

		// Fallback playlist cover image
		$playlist_cover = '';
		if ( ! empty( $entity['visualIdentity']['image'] ) && is_array( $entity['visualIdentity']['image'] ) ) {
			$imgs = $entity['visualIdentity']['image'];
			$last_img = end( $imgs );
			$playlist_cover = $last_img['url'] ?? '';
		} elseif ( ! empty( $entity['coverArt']['sources'][0]['url'] ) ) {
			$playlist_cover = $entity['coverArt']['sources'][0]['url'];
		}

		$results = array();
		$rank = 1;

		// Cap import at 100 items maximum as per specifications
		$capped_tracks = array_slice( $track_list, 0, 100 );

		foreach ( $capped_tracks as $t ) {
			$raw_title = trim( $t['title'] ?? '' );
			if ( empty( $raw_title ) ) continue;

			// Parse artists from subtitle (handles commas and non-breaking spaces)
			$raw_subtitle = $t['subtitle'] ?? '';
			$artists = preg_split( '/(?:\s|\xc2\xa0)*[,،](?:\s|\xc2\xa0)*/u', $raw_subtitle );
			$artists = array_values( array_filter( array_map( 'trim', (array) $artists ) ) );

			$primary_artist_raw = ! empty( $artists[0] ) ? $artists[0] : 'Various Artists';
			$primary_artist = Transliteration::arabize_text( $primary_artist_raw, 'artist' );
			$primary_artist_en = ( $primary_artist !== $primary_artist_raw ) ? $primary_artist_raw : null;

			// Arabize track title
			$ar_title = Transliteration::arabize_text( $raw_title, 'track' );
			$title_en = ( $ar_title !== $raw_title ) ? $raw_title : null;

			// Arabize all artist names for display
			$ar_artist_names = array();
			foreach ( $artists as $a_name ) {
				$ar_artist_names[] = Transliteration::arabize_text( $a_name, 'artist' );
			}
			$artist_names_str = ! empty( $ar_artist_names ) ? implode( '، ', $ar_artist_names ) : $primary_artist;
			$artist_names_en_str = ! empty( $artists ) ? implode( ', ', $artists ) : $primary_artist_raw;

			// Spotify Track ID from uri (e.g. spotify:track:0ux9ahOLCr8BVREw1637ss)
			$spotify_id = null;
			if ( ! empty( $t['uri'] ) && strpos( $t['uri'], 'spotify:track:' ) === 0 ) {
				$spotify_id = str_replace( 'spotify:track:', '', $t['uri'] );
			}

			// Duration ms
			$duration_ms = intval( $t['duration'] ?? 0 );

			// Audio preview URL
			$preview_url = $t['audioPreview']['url'] ?? null;

			// Image: try to resolve track specific thumbnail or fallback to playlist cover
			$cover_image = self::resolve_track_thumbnail( $spotify_id, $playlist_cover, ( $rank <= 10 ) );

			// Engagement score (Rank 1 = 100.0, decreases linearly)
			$score = round( max( 1.0, 100.0 - ( ( $rank - 1 ) * 0.95 ) ), 2 );

			$results[] = array(
				'rank'               => $rank,
				'current_rank'       => $rank,
				'previous_rank'      => null,
				'peak_rank'          => $rank,
				'weeks_on_chart'     => 1,
				'movement_direction' => 'new',
				'movement_value'     => 0,
				'is_new_entry'       => 1,
				'is_reentry'         => 0,
				'item_type'          => 'track',
				'title'              => $ar_title,
				'title_en'           => $title_en,
				'primary_artist'     => $primary_artist,
				'primary_artist_en'  => $primary_artist_en,
				'artists'            => $ar_artist_names,
				'artist_names'       => $artist_names_str,
				'artist_names_en'    => $artist_names_en_str,
				'spotify_id'         => $spotify_id,
				'image'              => $cover_image,
				'duration_ms'        => $duration_ms,
				'preview_url'        => $preview_url,
				'streams'            => 0,
				'views_count'        => 0,
				'score'              => $score,
			);

			$rank++;
		}

		return $results;
	}

	/**
	 * Fetch official Spotify charts (regional or artist, weekly or daily).
	 * Uses real Spotify official chart mirror with positions, movements, peaks, and streams.
	 *
	 * @param string $chart_key
	 * @return array|\WP_Error
	 */
	public static function fetch_official_chart( $chart_key ) {
		$is_weekly = ( strpos( $chart_key, 'weekly' ) !== false );
		$is_artist = ( strpos( $chart_key, 'artist' ) !== false );

		$kworb_url = $is_weekly
			? 'https://kworb.net/spotify/country/eg_weekly.html'
			: 'https://kworb.net/spotify/country/eg_daily.html';

		$response = wp_remote_get( $kworb_url, array(
			'timeout' => 25,
			'headers' => array(
				'User-Agent' => self::SPOTIFY_USER_AGENT,
				'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return new \WP_Error( 'empty_chart_response', __( 'Failed to retrieve official Spotify chart data.', 'charts' ) );
		}

		$dom = new \DOMDocument();
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		$xpath = new \DOMXPath( $dom );

		$table_id = $is_weekly ? 'spotifyweekly' : 'spotifydaily';
		$rows = $xpath->query( "//table[@id='{$table_id}']/tbody/tr" );

		if ( ! $rows || $rows->length === 0 ) {
			// Fallback: any table row in tbody
			$rows = $xpath->query( "//table[contains(@class, 'sortable')]/tbody/tr" );
		}

		if ( ! $rows || $rows->length === 0 ) {
			return new \WP_Error( 'chart_parse_failed', __( 'Could not locate chart rows in Spotify official data stream.', 'charts' ) );
		}

		if ( $is_artist ) {
			return self::aggregate_artists_from_chart( $rows, $xpath, $is_weekly );
		}

		return self::parse_tracks_from_chart( $rows, $xpath, $is_weekly );
	}

	/**
	 * Parse track entries from official Spotify chart table.
	 *
	 * @param \DOMNodeList $rows
	 * @param \DOMXPath $xpath
	 * @param bool $is_weekly
	 * @return array
	 */
	private static function parse_tracks_from_chart( $rows, $xpath, $is_weekly ) {
		$results = array();
		$limit = min( 100, $rows->length ); // Capped at 100 entries

		for ( $i = 0; $i < $limit; $i++ ) {
			$row = $rows->item( $i );
			$cols = $xpath->query( 'td', $row );
			if ( $cols->length < 5 ) continue;

			$pos = intval( trim( $cols->item( 0 )->textContent ) );
			$move_raw = trim( $cols->item( 1 )->textContent );

			$move_dir = 'same';
			$move_val = 0;
			$is_new   = 0;
			$is_reentry = 0;

			if ( $move_raw === '=' ) {
				$move_dir = 'same';
			} elseif ( strpos( $move_raw, '+' ) === 0 ) {
				$move_dir = 'up';
				$move_val = intval( substr( $move_raw, 1 ) );
			} elseif ( strpos( $move_raw, '-' ) === 0 ) {
				$move_dir = 'down';
				$move_val = intval( substr( $move_raw, 1 ) );
			} elseif ( stripos( $move_raw, 'new' ) !== false ) {
				$move_dir = 'new';
				$is_new   = 1;
			} elseif ( stripos( $move_raw, 're' ) !== false ) {
				$move_dir = 're-entry';
				$is_reentry = 1;
			}

			$title_td = $cols->item( 2 );
			$artist_nodes = $xpath->query( ".//a[contains(@href, '../artist/')]", $title_td );
			$track_nodes  = $xpath->query( ".//a[contains(@href, '../track/')]", $title_td );

			$primary_artist_raw = $artist_nodes->length > 0 ? trim( $artist_nodes->item( 0 )->textContent ) : '';
			$primary_artist_url = $artist_nodes->length > 0 ? $artist_nodes->item( 0 )->getAttribute( 'href' ) : '';
			preg_match( '/artist\/([a-zA-Z0-9]+)\.html/', $primary_artist_url, $m_a );
			$spotify_artist_id = $m_a[1] ?? null;

			$track_name_raw = $track_nodes->length > 0 ? trim( $track_nodes->item( 0 )->textContent ) : '';
			$track_url      = $track_nodes->length > 0 ? $track_nodes->item( 0 )->getAttribute( 'href' ) : '';
			preg_match( '/track\/([a-zA-Z0-9]+)\.html/', $track_url, $m_t );
			$spotify_track_id = $m_t[1] ?? null;

			if ( empty( $track_name_raw ) ) continue;

			// Featured / Collaborator artists
			$all_artists_raw = array();
			foreach ( $artist_nodes as $an ) {
				$all_artists_raw[] = trim( $an->textContent );
			}

			// Arabize names
			$primary_artist = Transliteration::arabize_text( $primary_artist_raw, 'artist' );
			$primary_artist_en = ( $primary_artist !== $primary_artist_raw ) ? $primary_artist_raw : null;

			$ar_title = Transliteration::arabize_text( $track_name_raw, 'track' );
			$title_en = ( $ar_title !== $track_name_raw ) ? $track_name_raw : null;

			$ar_artists = array();
			foreach ( $all_artists_raw as $a_raw ) {
				$ar_artists[] = Transliteration::arabize_text( $a_raw, 'artist' );
			}
			$artist_names_str    = ! empty( $ar_artists ) ? implode( '، ', $ar_artists ) : $primary_artist;
			$artist_names_en_str = ! empty( $all_artists_raw ) ? implode( ', ', $all_artists_raw ) : $primary_artist_raw;

			// Metrics
			$weeks = intval( trim( $cols->item( 3 )->textContent ) );
			$peak  = intval( trim( $cols->item( 4 )->textContent ) );
			if ( ! $peak ) $peak = $pos;

			// Streams
			$streams_text = trim( $cols->item( 6 )->textContent ?? '0' );
			$streams = intval( str_replace( ',', '', $streams_text ) );

			// Compute previous rank
			$previous_rank = null;
			if ( $move_dir === 'same' ) {
				$previous_rank = $pos;
			} elseif ( $move_dir === 'up' ) {
				$previous_rank = $pos + $move_val;
			} elseif ( $move_dir === 'down' ) {
				$previous_rank = max( 1, $pos - $move_val );
			}

			$cover_image = self::resolve_track_thumbnail( $spotify_track_id, '', ( $pos <= 10 ) );

			// Score calculation
			$score = round( max( 1.0, 100.0 - ( ( $pos - 1 ) * 0.95 ) ), 2 );

			$results[] = array(
				'rank'               => $pos,
				'current_rank'       => $pos,
				'previous_rank'      => $previous_rank,
				'peak_rank'          => $peak,
				'weeks_on_chart'     => max( 1, $weeks ),
				'movement_direction' => $move_dir,
				'movement_value'     => $move_val,
				'is_new_entry'       => $is_new,
				'is_reentry'         => $is_reentry,
				'item_type'          => 'track',
				'title'              => $ar_title,
				'title_en'           => $title_en,
				'primary_artist'     => $primary_artist,
				'primary_artist_en'  => $primary_artist_en,
				'artists'            => $ar_artists,
				'artist_names'       => $artist_names_str,
				'artist_names_en'    => $artist_names_en_str,
				'spotify_id'         => $spotify_track_id,
				'spotify_artist_id'  => $spotify_artist_id,
				'image'              => $cover_image,
				'streams'            => $streams,
				'views_count'        => 0,
				'score'              => $score,
			);
		}

		return $results;
	}

	/**
	 * Aggregate artists ranking from chart rows sorted descending by total streaming volume.
	 *
	 * @param \DOMNodeList $rows
	 * @param \DOMXPath $xpath
	 * @param bool $is_weekly
	 * @return array
	 */
	private static function aggregate_artists_from_chart( $rows, $xpath, $is_weekly ) {
		$artist_map = array();
		$limit = min( 200, $rows->length );

		for ( $i = 0; $i < $limit; $i++ ) {
			$row = $rows->item( $i );
			$cols = $xpath->query( 'td', $row );
			if ( $cols->length < 5 ) continue;

			$title_td = $cols->item( 2 );
			$artist_nodes = $xpath->query( ".//a[contains(@href, '../artist/')]", $title_td );
			if ( $artist_nodes->length === 0 ) continue;

			$streams_text = trim( $cols->item( 6 )->textContent ?? '0' );
			$streams = intval( str_replace( ',', '', $streams_text ) );

			$primary_artist_raw = trim( $artist_nodes->item( 0 )->textContent );
			$primary_artist_url = $artist_nodes->item( 0 )->getAttribute( 'href' );
			preg_match( '/artist\/([a-zA-Z0-9]+)\.html/', $primary_artist_url, $m_a );
			$sp_id = $m_a[1] ?? '';

			if ( empty( $primary_artist_raw ) ) continue;

			$key = mb_strtolower( $primary_artist_raw );
			if ( ! isset( $artist_map[ $key ] ) ) {
				$artist_map[ $key ] = array(
					'raw_name'     => $primary_artist_raw,
					'spotify_id'   => $sp_id,
					'streams'      => 0,
					'tracks_count' => 0,
					'best_track_rank' => $i + 1,
				);
			}

			$artist_map[ $key ]['streams'] += $streams;
			$artist_map[ $key ]['tracks_count']++;
		}

		// Sort artists by streams descending
		uasort( $artist_map, function( $a, $b ) {
			return $b['streams'] <=> $a['streams'];
		} );

		$results = array();
		$rank = 1;
		$capped_artists = array_slice( $artist_map, 0, 100 ); // Capped at 100 artists

		foreach ( $capped_artists as $a ) {
			$raw_name = $a['raw_name'];
			$ar_name  = Transliteration::arabize_text( $raw_name, 'artist' );
			$name_en  = ( $ar_name !== $raw_name ) ? $raw_name : null;

			$score = round( max( 1.0, 100.0 - ( ( $rank - 1 ) * 0.95 ) ), 2 );
			$image = self::resolve_artist_thumbnail( $a['spotify_id'], $ar_name );

			$results[] = array(
				'rank'               => $rank,
				'current_rank'       => $rank,
				'previous_rank'      => null,
				'peak_rank'          => $rank,
				'weeks_on_chart'     => 1,
				'movement_direction' => 'new',
				'movement_value'     => 0,
				'is_new_entry'       => 1,
				'is_reentry'         => 0,
				'item_type'          => 'artist',
				'title'              => $ar_name,
				'name_en'            => $name_en,
				'primary_artist'     => $ar_name,
				'primary_artist_en'  => $name_en,
				'spotify_id'         => $a['spotify_id'],
				'image'              => $image,
				'streams'            => $a['streams'],
				'views_count'        => 0,
				'score'              => $score,
			);

			$rank++;
		}

		return $results;
	}

	/**
	 * Resolve track cover image using database cache or Spotify oEmbed.
	 *
	 * @param string|null $spotify_id
	 * @param string $fallback
	 * @param bool $allow_remote_oembed
	 * @return string
	 */
	public static function resolve_track_thumbnail( $spotify_id, $fallback = '', $allow_remote_oembed = true ) {
		if ( empty( $spotify_id ) ) {
			return $fallback;
		}

		global $wpdb;
		// 1. Check existing cover image in database
		if ( ! empty( $wpdb ) && method_exists( $wpdb, 'get_var' ) ) {
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT cover_image FROM {$wpdb->prefix}charts_tracks WHERE spotify_id = %s AND cover_image IS NOT NULL AND cover_image != '' LIMIT 1",
				$spotify_id
			) );
			if ( ! empty( $existing ) ) {
				return $existing;
			}
		}

		// 2. Check transient cache
		$transient_key = 'sp_img_' . substr( md5( $spotify_id ), 0, 16 );
		if ( function_exists( 'get_transient' ) ) {
			$cached = get_transient( $transient_key );
			if ( false !== $cached ) {
				return $cached ?: $fallback;
			}
		}

		// Skip network round-trips if remote oEmbed is disabled for this rank
		if ( ! $allow_remote_oembed ) {
			return $fallback;
		}

		// 3. Fast oEmbed resolution
		$oembed_url = "https://open.spotify.com/oembed?url=https://open.spotify.com/track/{$spotify_id}";
		$resp = wp_remote_get( $oembed_url, array(
			'timeout' => 4,
			'headers' => array( 'User-Agent' => self::SPOTIFY_USER_AGENT ),
		) );

		if ( ! is_wp_error( $resp ) && ( function_exists( 'wp_remote_retrieve_response_code' ) ? wp_remote_retrieve_response_code( $resp ) === 200 : true ) ) {
			$body = function_exists( 'wp_remote_retrieve_body' ) ? wp_remote_retrieve_body( $resp ) : ( $resp['body'] ?? '' );
			$data = json_decode( $body, true );
			if ( ! empty( $data['thumbnail_url'] ) ) {
				$img = esc_url_raw( $data['thumbnail_url'] );
				if ( function_exists( 'set_transient' ) ) {
					set_transient( $transient_key, $img, 7 * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
				}
				return $img;
			}
		}

		if ( function_exists( 'set_transient' ) ) {
			set_transient( $transient_key, '', 2 * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
		}
		return $fallback;
	}

	/**
	 * Resolve artist profile image.
	 *
	 * @param string|null $spotify_id
	 * @param string $artist_name
	 * @return string
	 */
	public static function resolve_artist_thumbnail( $spotify_id, $artist_name = '' ) {
		global $wpdb;

		if ( ! empty( $wpdb ) && method_exists( $wpdb, 'get_var' ) ) {
			if ( ! empty( $spotify_id ) ) {
				$img = $wpdb->get_var( $wpdb->prepare(
					"SELECT image FROM {$wpdb->prefix}charts_artists WHERE spotify_id = %s AND image IS NOT NULL AND image != '' LIMIT 1",
					$spotify_id
				) );
				if ( ! empty( $img ) ) return $img;
			}

			if ( ! empty( $artist_name ) ) {
				$img = $wpdb->get_var( $wpdb->prepare(
					"SELECT image FROM {$wpdb->prefix}charts_artists WHERE display_name = %s AND image IS NOT NULL AND image != '' LIMIT 1",
					$artist_name
				) );
				if ( ! empty( $img ) ) return $img;
			}
		}

		return '';
	}

	/**
	 * Sync Spotify Chart or Playlist to database destination chart.
	 *
	 * @param string $item_key
	 * @param int $chart_id
	 * @return array|\WP_Error
	 */
	public static function sync_to_chart( $item_key, $chart_id = 0 ) {
		global $wpdb;

		$catalog = self::get_catalog();
		if ( ! isset( $catalog[ $item_key ] ) ) {
			return new \WP_Error( 'invalid_spotify_key', __( 'Selected Spotify item is not recognized.', 'charts' ) );
		}

		$item = $catalog[ $item_key ];

		// Fetch items
		$items = self::fetch_item_data( $item_key );
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		if ( empty( $items ) ) {
			return new \WP_Error( 'no_items_fetched', __( 'No entries were retrieved from Spotify.', 'charts' ) );
		}

		// 1. Ensure Destination Chart Definition exists
		$source_table = $wpdb->prefix . 'charts_sources';
		$source_mgr   = new SourceManager();
		$definition   = $chart_id ? $source_mgr->get_definition( $chart_id ) : null;
		if ( ! $definition && ! empty( $item['target_slug'] ) ) {
			$definition = $source_mgr->get_definition_by_slug( $item['target_slug'] );
			if ( $definition ) {
				$chart_id = (int) $definition->id;
			}
		}

		// Auto-create definition if none exists yet for this Spotify chart/playlist
		if ( ! $definition ) {
			$def_data = array(
				'title'          => $item['label'],
				'title_ar'       => $item['label'],
				'slug'           => $item['target_slug'],
				'item_type'      => $item['item_type'],
				'chart_type'     => $item['item_type'] === 'artist' ? 'top-artists' : 'top-songs',
				'platform'       => 'spotify',
				'country_code'   => 'eg',
				'frequency'      => $item['frequency'],
				'is_public'      => 1,
				'accent_color'   => '#1DB954',
				'chart_summary'  => ( $item['category'] === 'charts' ? 'Official ' : 'Curated ' ) . $item['label'] . ' on Spotify',
			);
			$new_def_id = $source_mgr->save_definition( $def_data );
			if ( $new_def_id ) {
				$definition = $source_mgr->get_definition( $new_def_id );
				$chart_id   = (int) $new_def_id;
			}
		}

		if ( $definition && $definition->item_type !== $item['item_type'] ) {
			return new \WP_Error( 'target_mismatch', __( 'Choose a destination chart with the same item type as the selected Spotify chart.', 'charts' ) );
		}

		$target_chart_type = $definition ? 'cid-' . (int) $definition->id : ( $item['item_type'] === 'artist' ? 'top-artists' : 'top-songs' );
		$source_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $source_table WHERE platform = 'spotify' AND chart_type = %s LIMIT 1",
			$target_chart_type
		) );
		$source_name = $definition ? 'Spotify — ' . $definition->title : $item['label'];
		$source_url  = $item['url'];

		if ( ! $source_id ) {
			$wpdb->insert( $source_table, array(
				'source_name'  => $source_name,
				'platform'     => 'spotify',
				'source_type'  => 'api',
				'source_url'   => $source_url,
				'country_code' => 'eg',
				'frequency'    => $item['frequency'],
				'chart_type'   => $target_chart_type,
				'parser_key'   => 'spotify-live-' . $item_key,
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
				'frequency'    => $item['frequency'],
				'parser_key'   => 'spotify-live-' . $item_key,
				'is_active'    => 1,
			), array( 'id' => $source_id ) );
		}
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', __( 'Could not create the Spotify source.', 'charts' ) );
		}

		// Deactivate any other active sources bound to this same definition
		if ( $definition ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE $source_table SET is_active = 0 WHERE chart_type = %s AND id != %d",
				$target_chart_type, $source_id
			) );
		}

		// 2. Ensure Period
		$import_flow  = new ImportFlow();
		$published_at = current_time( 'Y-m-d' );
		$period_id    = $import_flow->ensure_period( $item['frequency'], $published_at );
		if ( ! $period_id ) {
			return new \WP_Error( 'period_failed', __( 'Could not create chart period.', 'charts' ) );
		}

		// Clean slate wipe so no leftover ghost items
		$import_flow->wipe_period( $source_id, $period_id );

		// 3. Process entries
		$imported_count = 0;
		foreach ( $items as $row ) {
			if ( $row['item_type'] === 'artist' ) {
				$artist_id = EntityManager::ensure_artist( $row['title'], array(
					'image'           => $row['image'] ?? null,
					'display_name_en' => ! empty( $row['name_en'] ) ? $row['name_en'] : null,
					'spotify_id'      => $row['spotify_id'] ?? null,
				) );
				if ( ! $artist_id ) continue;

				if ( ! empty( $row['image'] ) ) {
					$artist_image = $wpdb->get_var( $wpdb->prepare( "SELECT image FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
					if ( empty( $artist_image ) ) {
						$wpdb->update( $wpdb->prefix . 'charts_artists', array( 'image' => $row['image'] ), array( 'id' => $artist_id ) );
					}
				}

				if ( ! empty( $row['spotify_id'] ) ) {
					$wpdb->update( $wpdb->prefix . 'charts_artists', array( 'spotify_id' => $row['spotify_id'] ), array( 'id' => $artist_id ) );
				}

				$artist_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist_id ) );
				$flat = array(
					'track_name'      => $row['title'],
					'track_name_en'   => $row['name_en'] ?? '',
					'artist_names'    => $row['title'],
					'artist_names_en' => $row['name_en'] ?? '',
					'cover_image'     => $row['image'] ?? null,
					'item_slug'       => $artist_slug,
					'spotify_id'      => $row['spotify_id'] ?? null,
					'streams'         => $row['streams'],
					'views_count'     => 0,
					'score'           => $row['score'],
				);
				$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'artist', $artist_id, $row, $flat );
				if ( $entry_id ) $imported_count++;
				continue;
			}

			// Track mode
			$primary_artist_id = EntityManager::ensure_artist( $row['primary_artist'], array(
				'display_name_en' => ! empty( $row['primary_artist_en'] ) ? $row['primary_artist_en'] : null,
				'spotify_id'      => $row['spotify_artist_id'] ?? null,
			) );

			$track_id = EntityManager::ensure_track( $row['title'], $primary_artist_id, array(
				'cover_image' => $row['image'] ?? null,
				'title_en'    => ! empty( $row['title_en'] ) ? $row['title_en'] : null,
				'spotify_id'  => $row['spotify_id'] ?? null,
			) );

			if ( $track_id && ! empty( $row['spotify_id'] ) ) {
				$curr_sp = $wpdb->get_var( $wpdb->prepare( "SELECT spotify_id FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $track_id ) );
				if ( empty( $curr_sp ) ) {
					$wpdb->update( $wpdb->prefix . 'charts_tracks', array( 'spotify_id' => $row['spotify_id'] ), array( 'id' => $track_id ) );
				}
			}

			if ( ! empty( $row['artists'] ) && count( $row['artists'] ) > 1 ) {
				$a_ids = array();
				foreach ( $row['artists'] as $a_name ) {
					$a_id = EntityManager::ensure_artist( trim( $a_name ) );
					if ( $a_id ) $a_ids[] = $a_id;
				}
				if ( ! empty( $a_ids ) ) {
					EntityManager::link_artists( $track_id, $a_ids, 'track' );
				}
			}

			if ( ! $track_id ) continue;

			$track_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $track_id ) );
			$flat = array(
				'track_name'      => $row['title'],
				'track_name_en'   => $row['title_en'] ?? '',
				'artist_names'    => $row['artist_names'],
				'artist_names_en' => $row['artist_names_en'] ?? '',
				'cover_image'     => $row['image'] ?? null,
				'item_slug'       => $track_slug,
				'spotify_id'      => $row['spotify_id'] ?? null,
				'streams'         => $row['streams'],
				'views_count'     => 0,
				'score'           => $row['score'],
			);
			$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'track', $track_id, $row, $flat );
			if ( $entry_id ) $imported_count++;
		}

		// Recalculate Intelligence
		Intelligence::recalculate_all();

		return array(
			'success'         => true,
			'imported_count'  => $imported_count,
			'total_items'     => count( $items ),
			'item_key'        => $item_key,
			'target_chart_id' => $chart_id,
			'item_type'       => $item['item_type'],
			'published_at'    => $published_at,
		);
	}
}
