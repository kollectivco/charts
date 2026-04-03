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
		'rank'           => array( 'rank', 'position', '#', 'no', 'chart_position', 'current_position' ),
		'item_title'     => array( 'title', 'track_name', 'video_title', 'song', 'name', 'item_title', 'track', 'video_name', 'video' ),
		'artist_names'   => array( 'artist', 'artist_name', 'artist_names', 'artists', 'channel', 'channel_name', 'performer' ),
		'views_count'    => array( 'views', 'weekly_views', 'view_count', 'streams', 'plays', 'views_count', 'count', 'view' ),
		'image'          => array( 'thumbnail', 'image', 'cover', 'cover_image', 'thumbnail_url', 'artwork' ),
		'source_url'     => array( 'youtube_url', 'video_url', 'url', 'source_url', 'link', 'video_link', 'youtube_link', 'uri', 'source' ),
		'youtube_id'     => array( 'youtube_id', 'video_id', 'id', 'yt_id' ),
		'peak_rank'      => array( 'peak_rank', 'peak', 'best_rank', 'highest_rank' ),
		'previous_rank'  => array( 'previous_rank', 'last_rank', 'prev_rank', 'last_position' ),
		'weeks_on_chart' => array( 'weeks_on_chart', 'weeks', 'chart_weeks', 'run', 'periods_on_chart', 'periods' ),
		'growth'         => array( 'growth', 'delta', 'change', 'rank_change', 'trend' ),
	);

	private $warnings = array();
	private $stats = array(
		'with_id'      => 0,
		'with_url'     => 0,
		'missing_both' => 0,
	);

	/**
	 * Parse CSV content into normalized rows.
	 * @return array|\WP_Error
	 */
	public function parse( $csv_content ) {
		$this->warnings = array();
		$this->stats = array(
			'with_id'      => 0,
			'with_url'     => 0,
			'missing_both' => 0,
		);
		
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
					$h = preg_replace( '/^\xEF\xBB\xBF/', '', (string)$h ); // Strip BOM
					$h = strtolower( trim( $h ) );
					$h = preg_replace( '/[\s\-\.]+/', '_', $h ); // Normalize spaces, hyphens, dots to a single underscore
					return $h;
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

		// Extra mapping for common variations
		$alias_reverse['video_id'] = 'youtube_id';
		$alias_reverse['yt_id']    = 'youtube_id';
		$alias_reverse['video_url'] = 'source_url';
		$alias_reverse['youtube_url'] = 'source_url';

		$col_map = array(); // column_index → canonical_key
		$mapped_canonicals = array();
		$unmapped_headers  = array();

		foreach ( $headers as $i => $h ) {
			if ( isset( $alias_reverse[ $h ] ) ) {
				$col_map[ $i ] = $alias_reverse[ $h ];
				$mapped_canonicals[] = $alias_reverse[ $h ];
			} else {
				$unmapped_headers[] = $h;
			}
		}

		if ( ! empty( $unmapped_headers ) ) {
			$this->warnings[] = sprintf( 
				__( 'Ignored headers: %s (stored in raw data only).', 'charts' ), 
				implode( ', ', $unmapped_headers ) 
			);
		}

		// Must have at minimum one of these to identify the entity
		if ( ! in_array( 'item_title', $mapped_canonicals, true ) && 
		     ! in_array( 'artist_names', $mapped_canonicals, true ) &&
		     ! in_array( 'youtube_id', $mapped_canonicals, true ) && 
		     ! in_array( 'source_url', $mapped_canonicals, true ) ) {
			return new \WP_Error( 'bad_headers',
				sprintf( __( 'Could not map required columns (Title, Artist, ID, or URL). Found headers: %s', 'charts' ), implode( ', ', $headers ) )
			);
		}

		// Parse rows
		$rows = array();
		for ( $i = $row_start; $i < count( $lines ); $i++ ) {
			$line = trim( $lines[ $i ] );
			if ( $line === '' ) continue;

			$cols = str_getcsv( $line );
			$raw  = array();
			
			// Fill raw data
			foreach ( $headers as $idx => $h ) {
				$raw_val = isset( $cols[ $idx ] ) ? trim( $cols[ $idx ] ) : '';
				if ( isset( $col_map[ $idx ] ) ) {
					// Handle multiple columns mapping to same canonical (priority: first seen wins for raw, but we keep all in raw_payload)
					if ( ! isset( $raw[ $col_map[ $idx ] ] ) || $raw[ $col_map[ $idx ] ] === '' ) {
						$raw[ $col_map[ $idx ] ] = $raw_val;
					}
				}
				// Original header names for reference
				$raw['__csv_original_' . $h] = $raw_val;
			}

			// Capture values for priority extraction if they aren't already mapped
			foreach (array('uri', 'source', 'link', 'url', 'video_id', 'youtube_id', 'id') as $key) {
				if (isset($raw['__csv_original_' . $key]) && !isset($raw[$key])) {
					$raw[$key] = $raw['__csv_original_' . $key];
				}
			}

			$row = $this->normalize_row( $raw, $i - $row_start + 1 );
			if ( $row ) {
				$rows[] = $row;
			}
		}

		if ( empty( $rows ) ) {
			return new \WP_Error( 'no_rows', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		// Detect chart logic/mode from headers
		$detected_mode = $this->detect_mode( $headers );

		// Add summary warnings if needed
		if ( $this->stats['missing_both'] > 0 ) {
			$this->warnings[] = sprintf(
				__( 'Extraction summary: %d rows with IDs, %d rows with URLs, %d rows missing both identification fields.', 'charts' ),
				$this->stats['with_id'],
				$this->stats['with_url'],
				$this->stats['missing_both']
			);
		}

		return array(
			'rows' => $rows,
			'detected_mode' => $detected_mode,
			'headers' => $headers
		);
	}

	/**
	 * Detect if the CSV looks like a Songs, Videos, or Artists chart.
	 */
	private function detect_mode( $headers ) {
		$has_track_keys = array_intersect($headers, array('track_name', 'song', 'title', 'item_title', 'track'));
		$has_artist_names = array_intersect($headers, array('artist_names', 'artist_name', 'artist', 'performer'));
		$has_video_keys = array_intersect($headers, array('video_title', 'video_id', 'clip', 'mv', 'music_video', 'video_name'));

		// 1. YouTube Artist Chart
		// Usually contains Artist Name (singular) and NO track/video specific titles
		if ( (in_array('artist_name', $headers) || in_array('artist', $headers)) && empty($has_track_keys) && empty($has_video_keys) ) {
			return 'top-artists';
		}

		// 2. YouTube Track Chart (Song Chart)
		// Usually contains Track Name AND Artist Names (plural)
		if ( ! empty($has_track_keys) && (in_array('artist_names', $headers) || in_array('artist', $headers)) ) {
			return 'top-songs';
		}

		// 3. YouTube Music Video Chart
		// Contains Video Title or specific video keys
		if ( ! empty($has_video_keys) ) {
			return 'top-videos';
		}

		// 4. Broad fallbacks based on dominant column counts
		if ( ! empty($has_track_keys) ) return 'top-songs';
		if ( in_array('artist_name', $headers) || in_array('artist', $headers) || in_array('artist_names', $headers) ) return 'top-artists';

		return 'unknown';
	}

	public function get_warnings() {
		return $this->warnings;
	}

	/**
	 * Normalize a raw mapped row into canonical structure.
	 */
	private function normalize_row( $raw, $line_num ) {
		$title  = $raw['item_title'] ?? '';
		$rank   = isset( $raw['rank'] ) ? intval( preg_replace( '/[^0-9]/', '', $raw['rank'] ) ) : $line_num;

		// 1. YouTube ID Extraction Priority
		// Priority order: explicit youtube_id > extract from source_url > extract from uri > extract from source
		$yt_id = null;
		$url   = null;

		$candidates = array(
			'youtube_id' => $raw['youtube_id'] ?? '',
			'source_url' => $raw['source_url'] ?? '',
			'uri'        => $raw['uri'] ?? $raw['__csv_original_uri'] ?? '',
			'source'     => $raw['source'] ?? $raw['__csv_original_source'] ?? '',
		);

		// Try explicit ID first
		if ( ! empty( $candidates['youtube_id'] ) ) {
			$yt_id = $this->extract_youtube_id( $candidates['youtube_id'] );
		}

		// If no ID yet, try extracting from URL candidates in order
		if ( empty( $yt_id ) ) {
			foreach ( array( 'source_url', 'uri', 'source' ) as $key ) {
				if ( ! empty( $candidates[ $key ] ) ) {
					$extracted = $this->extract_youtube_id( $candidates[ $key ] );
					if ( $extracted ) {
						$yt_id = $extracted;
						// If we found an ID in a URL field, also use that field as our canonical URL if URL is missing
						if ( empty( $url ) && filter_var( $candidates[ $key ], FILTER_VALIDATE_URL ) ) {
							$url = $candidates[ $key ];
						}
						break;
					}
				}
			}
		}

		// Ensure we have a URL if possible
		if ( empty( $url ) ) {
			if ( ! empty( $candidates['source_url'] ) && filter_var( $candidates['source_url'], FILTER_VALIDATE_URL ) ) {
				$url = $candidates['source_url'];
			} elseif ( ! empty( $candidates['uri'] ) && filter_var( $candidates['uri'], FILTER_VALIDATE_URL ) ) {
				$url = $candidates['uri'];
			} elseif ( ! empty( $candidates['source'] ) && filter_var( $candidates['source'], FILTER_VALIDATE_URL ) ) {
				$url = $candidates['source'];
			}
		}

		// 2. Stats and Warnings
		if ( ! empty( $yt_id ) ) {
			$this->stats['with_id']++;
		} elseif ( ! empty( $url ) ) {
			$this->stats['with_url']++;
		} else {
			$this->stats['missing_both']++;
		}

		// 3. Thumbnail Generation
		$image = $raw['image'] ?? '';
		if ( empty( $image ) && ! empty( $yt_id ) ) {
			// Generate hqdefault thumbnail URL
			$image = "https://img.youtube.com/vi/{$yt_id}/hqdefault.jpg";
			$raw['thumbnail_generated'] = true;
		}

		// Fallback: use youtube_id as title if blank
		if ( empty( $title ) && ! empty( $yt_id ) ) {
			$title = $yt_id;
		}

		if ( empty( $title ) && $rank < 1 ) {
			return null;
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
			'image'          => $image ?: null,
			'source_url'     => $url ?: null,
			'youtube_id'     => $yt_id ?: null,
			'growth'         => $raw['growth'] ?? null,
			'peak_rank'      => isset( $raw['peak_rank'] ) && $raw['peak_rank'] !== '' ? intval( $raw['peak_rank'] ) : $rank,
			'previous_rank'  => isset( $raw['previous_rank'] ) && $raw['previous_rank'] !== '' ? intval( $raw['previous_rank'] ) : null,
			'weeks_on_chart' => isset( $raw['weeks_on_chart'] ) && $raw['weeks_on_chart'] !== '' ? intval( $raw['weeks_on_chart'] ) : 1,
			'raw_payload'    => $raw,
			'thumbnail_generated' => $raw['thumbnail_generated'] ?? false,
		);
	}

	/**
	 * Extracts YouTube ID from various string formats (URLs, short URLs, or raw IDs).
	 */
	private function extract_youtube_id( $string ) {
		$string = trim( (string) $string );
		if ( empty( $string ) ) return null;

		// 1. Check if it's already a raw ID (11 chars, alphanumeric + _ -)
		// We allow some flexibility but must be exactly 11 characters
		if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $string ) ) {
			return $string;
		}

		// 2. Extract from various URL formats
		// watch?v=..., youtu.be/..., shorts/..., embed/..., v/..., etc.
		// Added support for mobile, screen-reader, and parameter-heavy URLs
		$patterns = array(
			'/(?:v=|\/shorts\/|\/embed\/|\/v\/|\.be\/|vi\/|user\/\S+\/u\/\d+\/)([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
			'/youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]{11})/'
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $string, $m ) ) {
				return $m[1];
			}
		}

		return null;
	}
}
