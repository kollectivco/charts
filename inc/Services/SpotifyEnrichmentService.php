<?php

namespace Charts\Services;

/**
 * Service for enriching chart rows with official Spotify metadata.
 */
class SpotifyEnrichmentService {

	private $api;

	public function __construct() {
		$this->api = new SpotifyApiClient();
	}

	/**
	 * Enrich a batch of parsed CSV rows.
	 */
	public function enrich_rows( array &$rows ) {
		$track_ids = array_filter( array_map( function($r) { return $r['spotify_track_id'] ?? null; }, $rows ) );
		if ( empty( $track_ids ) ) {
			return 0;
		}

		// Spotify allows fetching up to 50 tracks in one request
		$batches = array_chunk( $track_ids, 50 );
		$enriched_count = 0;

		$track_metadata_cache = array();

		foreach ( $batches as $batch ) {
			$tracks = $this->api->get_tracks( $batch );
			if ( is_wp_error( $tracks ) ) {
				continue;
			}

			foreach ( $tracks as $track ) {
				if ( ! empty( $track['id'] ) ) {
					$track_metadata_cache[$track['id']] = $track;
				}
			}
		}

		foreach ( $rows as &$row ) {
			$tid = $row['spotify_track_id'] ?? null;
			if ( $tid && isset( $track_metadata_cache[$tid] ) ) {
				$row['enrichment'] = $this->transform_metadata( $track_metadata_cache[$tid] );
				$enriched_count++;
			}
		}

		return $enriched_count;
	}

	/**
	 * Transform raw API metadata into normalized object.
	 */
	private function transform_metadata( $data ) {
		$track_info = array(
			'spotify_id'   => $data['id'] ?? null,
			'official_name' => $data['name'] ?? null,
			'external_url' => $data['external_urls']['spotify'] ?? null,
			'popularity'   => $data['popularity'] ?? 0,
			'preview_url'  => $data['preview_url'] ?? null,
		);

		// Album
		$album = $data['album'] ?? array();
		$track_info['album'] = array(
			'spotify_id'   => $album['id'] ?? null,
			'title'        => $album['name'] ?? null,
			'cover_image'  => $album['images'][0]['url'] ?? null,
			'release_date' => $album['release_date'] ?? null,
			'external_url' => $album['external_urls']['spotify'] ?? null,
		);

		// Artists
		$artists = $data['artists'] ?? array();
		$track_info['artists'] = array_map( function($a) {
			return array(
				'spotify_id'   => $a['id'] ?? null,
				'name'         => $a['name'] ?? null,
				'external_url' => $a['external_urls']['spotify'] ?? null,
			);
		}, $artists );

		return $track_info;
	}

	/**
	 * Enrich a specific artist record with full profile data.
	 */
	public function enrich_artist( $artist_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_artists';
		
		$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $artist_id ) );
		if ( ! $artist || empty( $artist->spotify_id ) ) return false;

		$data = $this->api->get_artist( $artist->spotify_id );
		if ( is_wp_error( $data ) ) return $data;

		$top_tracks_data = $this->api->get_artist_top_tracks( $artist->spotify_id, 'US' );
		$top_tracks = array();
		if ( ! is_wp_error($top_tracks_data) && !empty($top_tracks_data['tracks']) ) {
			foreach (array_slice($top_tracks_data['tracks'], 0, 10) as $t) {
				$top_tracks[] = array(
					'id' => $t['id'],
					'name' => $t['name'],
					'preview_url' => $t['preview_url'] ?? null,
					'image' => $t['album']['images'][0]['url'] ?? null
				);
			}
		}

		$meta = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
		$meta['genres']       = $data['genres'] ?? array();
		$meta['followers']    = $data['followers']['total'] ?? 0;
		$meta['popularity']   = $data['popularity'] ?? 0;
		$meta['external_url'] = $data['external_urls']['spotify'] ?? null;
		$meta['spotify_top_tracks'] = $top_tracks;
		$meta['spotify_id']   = $artist->spotify_id;
		$meta['last_sync']    = current_time( 'mysql' );

		$update = array(
			'metadata_json' => json_encode( $meta ),
			'updated_at'    => current_time( 'mysql' )
		);

		if ( ! empty( $data['images'][0]['url'] ) ) {
			$update['image'] = $data['images'][0]['url'];
			$meta['spotify_image'] = $data['images'][0]['url'];
			$update['metadata_json'] = json_encode( $meta );
		}

		return $wpdb->update( $table, $update, array( 'id' => $artist_id ) );
	}
}
