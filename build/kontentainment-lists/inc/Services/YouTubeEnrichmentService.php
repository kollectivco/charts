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
}
