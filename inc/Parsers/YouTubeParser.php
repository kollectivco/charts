<?php

namespace Charts\Parsers;

/**
 * YouTube Chart Parser
 */
class YouTubeParser {

	/**
	 * Parse YouTube chart content.
	 */
	public function parse( $content ) {
		// Attempt to extract JSON from ytInitialData
		if ( preg_match( '/var ytInitialData = (.*?);<\/script>/s', $content, $matches ) ) {
			$json = json_decode( $matches[1], true );
			
			// Explore dynamic structure in YouTube initial data
			try {
				$tab_content = $json['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content'] ?? array();
				$section_list = $tab_content['sectionListRenderer']['contents'] ?? array();
				
				foreach ( $section_list as $section ) {
					$item_section = $section['itemSectionRenderer']['contents'][0] ?? array();
					$chart_renderer = $item_section['musicChartRenderer'] ?? $item_section['shelfRenderer']['content']['musicChartRenderer'] ?? null;
					
					if ( $chart_renderer && isset( $chart_renderer['contents'] ) ) {
						return $this->extract_entries( $chart_renderer['contents'] );
					}
				}
			} catch ( \Exception $e ) {
				// Log or handle error
			}
		}

		return array();
	}

	/**
	 * Extract entries from YouTube JSON data.
	 */
	private function extract_entries( $entries ) {
		$rows = array();
		foreach ( $entries as $index => $entry ) {
			$item = $entry['musicChartItemRenderer'] ?? array();
			if ( empty( $item ) ) continue;

			$rows[] = array(
				'rank'           => $index + 1,
				'title'          => $item['title']['runs'][0]['text'] ?? 'Unknown',
				'artists'        => array_map( function($r) { return $r['text']; }, $item['subtitle']['runs'] ?? array() ),
				'views'          => 0, // YouTube views often hidden in secondary subtexts
				'image'          => $item['thumbnail']['thumbnails'][0]['url'] ?? null,
				'source_url'     => isset($item['navigationEndpoint']['watchEndpoint']['videoId']) ? "https://www.youtube.com/watch?v=" . $item['navigationEndpoint']['watchEndpoint']['videoId'] : null,
			);
		}
		return $rows;
	}
}
