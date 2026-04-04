<?php

namespace Charts\Services;

/**
 * Handle YouTube metadata enrichment for chart rows.
 */
class YouTubeEnrichmentService {

	private $api_client;

	public function __construct() {
		$this->api_client = new YouTubeApiClient();
	}

	/**
	 * Is enrichment configured?
	 */
	public function is_configured() {
		return $this->api_client->is_configured();
	}

	/**
	 * Batch enrich rows with YouTube API data.
	 *
	 * @param array  $rows       The rows parsed from CSV.
	 * @param string $chart_type The type of chart (top-songs, top-artists, top-videos).
	 * @return array             Enriched rows.
	 */
	public function enrich_batch( array $rows, $chart_type ) {
		if ( ! $this->is_configured() ) {
			return $rows;
		}

		$ids = array();
		foreach ( $rows as $row ) {
			if ( ! empty( $row['youtube_id'] ) ) {
				$ids[] = $row['youtube_id'];
			}
		}

		if ( empty( $ids ) ) {
			return $rows;
		}

		// YouTube API only allows 50 IDs per request
		$chunks       = array_chunk( $ids, 50 );
		$metadata_map = array();

		foreach ( $chunks as $chunk ) {
			if ( $chart_type === 'top-artists' ) {
				$items = $this->api_client->get_channels( $chunk );
			} else {
				$items = $this->api_client->get_videos( $chunk );
			}

			foreach ( (array) $items as $item ) {
				$id         = $item['id'];
				$snippet    = $item['snippet'] ?? array();
				$stats      = $item['statistics'] ?? array();
				$thumbnails = $snippet['thumbnails'] ?? array();

				// Pick best image
				$image = $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? $thumbnails['default']['url'] ?? null;

				$metadata_map[ $id ] = array(
					'api_title'        => $snippet['title'] ?? '',
					'api_artist_names' => $snippet['channelTitle'] ?? '',
					'api_image'        => $image,
					'api_views'        => intval( $stats['viewCount'] ?? 0 ),
				);
			}
		}

		// Merge metadata back into rows
		foreach ( $rows as &$row ) {
			$id = $row['youtube_id'] ?? null;
			if ( $id && isset( $metadata_map[ $id ] ) ) {
				$meta = $metadata_map[ $id ];

				// Prefer API for missing or placeholder-like file data
				if ( empty( $row['item_title'] ) || $row['item_title'] === 'Unknown' || $row['item_title'] === '—' ) {
					$row['item_title'] = $meta['api_title'];
				}
				if ( empty( $row['artist_names'] ) || $row['artist_names'] === 'Unknown' || $row['artist_names'] === '—' ) {
					$row['artist_names'] = $meta['api_artist_names'];
					$row['artist_arr']   = array( $meta['api_artist_names'] );
				}
				if ( empty( $row['image'] ) ) {
					$row['image'] = $meta['api_image'];
				}
				if ( ( $row['views_count'] ?? 0 ) === 0 ) {
					$row['views_count'] = $meta['api_views'];
				}

				// Always merge for reference
				$row['api_meta'] = $meta;
			}
		}

		return $rows;
	}

	/**
	 * Enrich an individual artist's YouTube channel metadata.
	 */
	public function enrich_artist( $artist_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_artists';
		
		$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $artist_id ) );
		if ( ! $artist ) return false;
		
		$meta = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
		$channel_id = $meta['youtube_channel_id'] ?? null;
		
		if ( empty( $channel_id ) ) return false;

		$channels = $this->api_client->get_channels( array( $channel_id ) );
		if ( is_wp_error( $channels ) || empty( $channels ) ) return false;

		$channel = $channels[0];
		
		$snippet = $channel['snippet'] ?? array();
		$stats = $channel['statistics'] ?? array();

		$meta['youtube_subscribers'] = intval($stats['subscriberCount'] ?? 0);
		$meta['youtube_video_count'] = intval($stats['videoCount'] ?? 0);
		$meta['youtube_thumbnail']   = $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['medium']['url'] ?? null;
		$meta['youtube_url']         = 'https://www.youtube.com/channel/' . $channel_id;
		$meta['youtube_last_sync']   = current_time( 'mysql' );
		
		$update = array( 'metadata_json' => json_encode( $meta ) );
		if ( empty($artist->image) && !empty($meta['youtube_thumbnail']) ) {
			$update['image'] = $meta['youtube_thumbnail'];
		}

		return $wpdb->update( $table, $update, array( 'id' => $artist_id ) );
	}
}
