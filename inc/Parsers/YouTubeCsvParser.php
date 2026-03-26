<?php

namespace Charts\Parsers;

/**
 * Parses YouTube chart CSV exports with flexible header aliasing.
 *
 * Canonical internal fields after parsing:
 *   rank, item_title, artist_names, views_count, image,
 *   source_url, youtube_id, peak_rank, previous_rank, weeks_on_chart
 *
 * Supported CSV header aliases (case-insensitive):
 *   rank           : rank, position, #
 *   item_title     : title, track_name, video_title, song, name
 *   artist_names   : artist, artist_name, artist_names, artists, channel
 *   views_count    : views, weekly_views, view_count, streams
 *   image          : thumbnail, image, cover, cover_image
 *   source_url     : youtube_url, video_url, url, source_url, link
 *   youtube_id     : youtube_id, video_id, id
 *   peak_rank      : peak_rank, peak, best_rank
 *   previous_rank  : previous_rank, last_rank, prev_rank
 *   weeks_on_chart : weeks_on_chart, weeks, chart_weeks
 */
class YouTubeCsvParser {

	/** Map from normalized alias → canonical key */
	private static $ALIASES = array(
		'rank'           => array( 'rank', 'position', '#', 'no', 'chart_position' ),
		'item_title'     => array( 'title', 'track_name', 'video_title', 'song', 'name', 'item_title', 'track', 'video_name' ),
		'artist_names'   => array( 'artist', 'artist_name', 'artist_names', 'artists', 'channel', 'channel_name' ),
		'views_count'    => array( 'views', 'weekly_views', 'view_count', 'streams', 'plays', 'views_count' ),
		'image'          => array( 'thumbnail', 'image', 'cover', 'cover_image', 'thumbnail_url' ),
		'source_url'     => array( 'youtube_url', 'video_url', 'url', 'source_url', 'link', 'video_link' ),
		'youtube_id'     => array( 'youtube_id', 'video_id', 'id', 'yt_id' ),
		'peak_rank'      => array( 'peak_rank', 'peak', 'best_rank', 'highest_rank' ),
		'previous_rank'  => array( 'previous_rank', 'last_rank', 'prev_rank', 'last_position' ),
		'weeks_on_chart' => array( 'weeks_on_chart', 'weeks', 'chart_weeks', 'run' ),
	);

	/**
	 * Parse CSV content into normalized rows.
	 * @return array|\WP_Error
	 */
	public function parse( $csv_content ) {
		// Strip BOM
		$csv_content = preg_replace( '/^\xEF\xBB\xBF/', '', $csv_content );
		$csv_content = trim( $csv_content );

		if ( empty( $csv_content ) ) {
			return new \WP_Error( 'empty_csv', __( 'The CSV file is empty.', 'charts' ) );
		}

		$lines = preg_split( '/\r\n|\r|\n/', $csv_content );

		// Find header row (first row with at least 2 columns)
		$headers   = null;
		$row_start = 0;
		foreach ( $lines as $idx => $line ) {
			$cols = str_getcsv( $line );
			if ( count( $cols ) >= 2 ) {
				$headers   = array_map( function( $h ) {
					return strtolower( trim( preg_replace( '/^\xEF\xBB\xBF/', '', (string)$h ) ) );
				}, $cols );
				$row_start = $idx + 1;
				break;
			}
		}

		if ( ! $headers ) {
			return new \WP_Error( 'no_header', __( 'Could not detect a header row in the CSV.', 'charts' ) );
		}

		// Build header → canonical key map
		$alias_reverse = array(); // normalized_header → canonical_key
		foreach ( self::$ALIASES as $canonical => $aliases ) {
			foreach ( $aliases as $alias ) {
				$alias_reverse[ $alias ] = $canonical;
			}
		}

		$col_map = array(); // column_index → canonical_key
		foreach ( $headers as $i => $h ) {
			if ( isset( $alias_reverse[ $h ] ) ) {
				$col_map[ $i ] = $alias_reverse[ $h ];
			}
		}

		// Must have at minimum rank + item_title
		$canonical_found = array_values( $col_map );
		if ( ! in_array( 'rank', $canonical_found, true ) && ! in_array( 'item_title', $canonical_found, true ) ) {
			return new \WP_Error( 'bad_headers',
				sprintf( __( 'Could not map required columns. Found headers: %s', 'charts' ), implode( ', ', $headers ) )
			);
		}

		// Parse rows
		$rows = array();
		for ( $i = $row_start; $i < count( $lines ); $i++ ) {
			$line = trim( $lines[ $i ] );
			if ( $line === '' ) continue;

			$cols = str_getcsv( $line );
			$raw  = array();
			foreach ( $col_map as $ci => $key ) {
				$raw[ $key ] = isset( $cols[ $ci ] ) ? trim( $cols[ $ci ] ) : '';
			}

			$row = $this->normalize_row( $raw, $i + 1 );
			if ( $row ) {
				$rows[] = $row;
			}
		}

		if ( empty( $rows ) ) {
			return new \WP_Error( 'no_rows', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		return $rows;
	}

	/**
	 * Normalize a raw mapped row into canonical structure.
	 */
	private function normalize_row( $raw, $line_num ) {
		$title  = $raw['item_title'] ?? '';
		$rank   = isset( $raw['rank'] ) ? intval( $raw['rank'] ) : $line_num;

		if ( $title === '' && $rank === 0 ) {
			return null;
		}

		// If rank wasn't in CSV, use line number as rank
		if ( $rank === 0 ) {
			$rank = $line_num;
		}

		// Extract YouTube ID from URL if not given directly
		$yt_id = $raw['youtube_id'] ?? '';
		if ( $yt_id === '' && ! empty( $raw['source_url'] ) ) {
			if ( preg_match( '/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $raw['source_url'], $m ) ) {
				$yt_id = $m[1];
			}
		}

		// Artist string
		$artist_str = $raw['artist_names'] ?? '';
		$artist_arr = array_filter( array_map( 'trim', explode( ',', $artist_str ) ) );

		return array(
			'rank'           => $rank,
			'item_title'     => $title,
			'artist_names'   => $artist_str,
			'artist_arr'     => array_values( $artist_arr ),
			'views_count'    => isset( $raw['views_count'] ) ? intval( str_replace( array(',', ' '), '', $raw['views_count'] ) ) : 0,
			'image'          => $raw['image'] ?? null,
			'source_url'     => $raw['source_url'] ?? null,
			'youtube_id'     => $yt_id ?: null,
			'peak_rank'      => isset( $raw['peak_rank'] ) && $raw['peak_rank'] !== '' ? intval( $raw['peak_rank'] ) : $rank,
			'previous_rank'  => isset( $raw['previous_rank'] ) && $raw['previous_rank'] !== '' ? intval( $raw['previous_rank'] ) : null,
			'weeks_on_chart' => isset( $raw['weeks_on_chart'] ) && $raw['weeks_on_chart'] !== '' ? intval( $raw['weeks_on_chart'] ) : 1,
			'raw_payload'    => $raw,
		);
	}
}
