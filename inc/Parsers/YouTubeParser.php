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
		// Strategy 1: ytInitialData (Standard Chart Page)
		$rows = $this->try_yt_initial_data( $content );
		if ( ! empty( $rows ) ) {
			return $rows;
		}

		// Strategy 2: ytInitialPlayerResponse (For some redirected views)
		$rows = $this->try_yt_initial_player_response( $content );
		if ( ! empty( $rows ) ) {
			return $rows;
		}

		return array();
	}

	/**
	 * Strategy 1: Extract from ytInitialData block.
	 */
	private function try_yt_initial_data( $content ) {
		if ( ! preg_match( '/var ytInitialData = (.*?);<\/script>/s', $content, $matches ) ) {
			return array();
		}

		$json = json_decode( $matches[1], true );
		if ( ! $json ) return array();

		// Robust path traversal
		$contents = $json['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'] ?? array();
		
		foreach ( $contents as $section ) {
			$sub_contents = $section['itemSectionRenderer']['contents'] ?? array();
			foreach ( $sub_contents as $item ) {
				$renderer = $item['musicChartRenderer'] ?? $item['shelfRenderer']['content']['musicChartRenderer'] ?? null;
				if ( $renderer && ! empty( $renderer['contents'] ) ) {
					return $this->extract_entries( $renderer['contents'] );
				}
			}
		}

		return array();
	}

	/**
	 * Strategy 2: Extract from ytInitialPlayerResponse (Fallback).
	 */
	private function try_yt_initial_player_response( $content ) {
		if ( ! preg_match( '/var ytInitialPlayerResponse = (.*?);<\/script>/s', $content, $matches ) ) {
			return array();
		}
		// Minimal implementation if needed, usually ytInitialData is enough for charts.
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

			$title = $item['title']['runs'][0]['text'] ?? 'Unknown';
			$subtitle_runs = $item['subtitle']['runs'] ?? array();
			
			$artists = array();
			foreach ( $subtitle_runs as $run ) {
				if ( ! empty( $run['navigationEndpoint'] ) ) {
					$artists[] = $run['text'];
				}
			}
			
			// If no linked artists, take common text blocks
			if ( empty( $artists ) && ! empty( $subtitle_runs ) ) {
				$artists[] = $subtitle_runs[0]['text'];
			}

			$rows[] = array(
				'rank'           => $index + 1,
				'title'          => $title,
				'artists'        => $artists,
				'views_label'    => $item['subtitle']['runs'][count($subtitle_runs)-1]['text'] ?? '', 
				'image'          => $item['thumbnail']['thumbnails'][count($item['thumbnail']['thumbnails'])-1]['url'] ?? null,
				'source_url'     => isset($item['navigationEndpoint']['watchEndpoint']['videoId']) ? "https://www.youtube.com/watch?v=" . $item['navigationEndpoint']['watchEndpoint']['videoId'] : null,
				'spotify_id'     => null, // enrichment will handle
				'youtube_id'     => $item['navigationEndpoint']['watchEndpoint']['videoId'] ?? null,
			);
		}
		return array_filter( $rows );
	}
}
