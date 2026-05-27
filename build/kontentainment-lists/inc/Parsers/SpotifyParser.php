<?php

namespace Charts\Parsers;

/**
 * Spotify Chart Parser
 */
class SpotifyParser {

	/**
	 * Parse Spotify chart content.
	 */
	public function parse( $content ) {
		// Attempt to extract JSON from __NEXT_DATA__
		if ( preg_match( '/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $content, $matches ) ) {
			$json = json_decode( $matches[1], true );
			
			// Explore different potential paths for chart data in Spotify's NEXT_DATA
			$entries = null;
			if ( isset( $json['props']['pageProps']['chartData']['entries'] ) ) {
				$entries = $json['props']['pageProps']['chartData']['entries'];
			} elseif ( isset( $json['props']['pageProps']['initialDehydratedState']['queries'][0]['state']['data']['chartData']['entries'] ) ) {
				$entries = $json['props']['pageProps']['initialDehydratedState']['queries'][0]['state']['data']['chartData']['entries'];
			} elseif ( isset( $json['props']['pageProps']['data']['chartData']['entries'] ) ) {
				$entries = $json['props']['pageProps']['data']['chartData']['entries'];
			}

			if ( $entries ) {
				return $this->extract_entries( $entries );
			}
		}

		// If real parsing fails, return a controlled dummy set for development parity
		// In a real production scrape, this would return an empty array or throw an error
		return array();
	}

	/**
	 * Extract entries from Spotify JSON data.
	 */
	private function extract_entries( $entries ) {
		$rows = array();
		foreach ( $entries as $entry ) {
			// Extract primary fields with safety
			$metadata = $entry['trackMetadata'] ?? $entry['artistMetadata'] ?? array();
			$chart_data = $entry['chartEntryData'] ?? array();

			$rows[] = array(
				'rank'           => $chart_data['currentRank'] ?? null,
				'previous_rank'  => $chart_data['previousRank'] ?? null,
				'peak_rank'      => $chart_data['peakRank'] ?? null,
				'weeks_on_chart' => $chart_data['appearancesOnChart'] ?? 1,
				'title'          => $metadata['trackName'] ?? $metadata['artistName'] ?? 'Unknown',
				'artists'        => array_map( function( $artist ) {
					return $artist['name'];
				}, $metadata['artists'] ?? (isset($metadata['artistName']) ? array(array('name' => $metadata['artistName'])) : array()) ),
				'album'          => $metadata['albumName'] ?? null,
				'image'          => $metadata['displayImageUri'] ?? null,
				'streams'        => $chart_data['streams'] ?? 0,
				'source_url'     => isset($metadata['trackUri']) ? "https://open.spotify.com/track/" . str_replace('spotify:track:', '', $metadata['trackUri']) : null,
			);
		}
		return array_filter( $rows, function($r) { return !empty($r['rank']); } );
	}
}
