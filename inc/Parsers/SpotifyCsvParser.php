<?php

namespace Charts\Parsers;

/**
 * Specifically parses the Spotify Chart CSV format.
 */
class SpotifyCsvParser {

	/**
	 * Parse CSV data.
	 */
	public function parse( $csv_content ) {
		// 1. Strip UTF-8 BOM if present
		$csv_content = preg_replace( '/^\xEF\xBB\xBF/', '', $csv_content );
		$csv_content = trim( $csv_content );

		if ( empty( $csv_content ) ) {
			return new \WP_Error( 'empty_csv', __( 'The CSV file is empty.', 'charts' ) );
		}

		// 2. Explode by newline characters (supports LF and CRLF)
		$lines = preg_split( '/\r\n|\r|\n/', $csv_content );

		// 3. Look for the header line. Spotify CSVs often have multiple comment lines at the start.
		$headers = null;
		$row_start = 0;
		foreach ( $lines as $index => $line ) {
			$cols = str_getcsv( $line );
			
			// Normalize headers for cross-platform robustness
			$normalized_cols = array_map( function( $header ) {
				$header = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header ); // extra safety per-col
				return strtolower( trim( $header ) );
			}, $cols );

			if ( in_array( 'rank', $normalized_cols, true ) && in_array( 'uri', $normalized_cols, true ) ) {
				$headers = $normalized_cols;
				$row_start = $index + 1;
				break;
			}
		}

		if ( ! $headers ) {
			return new \WP_Error( 'invalid_format', __( 'Could not find required columns (rank, uri) in CSV.', 'charts' ) );
		}

		$rows = array();
		$header_count = count( $headers );
		for ( $i = $row_start; $i < count( $lines ); $i++ ) {
			$cols = str_getcsv( $lines[$i] );
			if ( count( $cols ) < $header_count ) {
				continue;
			}

			// Slice to match header count but pad if needed (Spotify format can vary slightly)
			$row_data = array_combine( $headers, array_slice( $cols, 0, $header_count ) );
			$rows[] = $this->transform_row( $row_data );
		}

		return array_filter( $rows );
	}

	/**
	 * Transform a raw CSV row into a normalized structure.
	 */
	private function transform_row( $raw ) {
		$uri = $raw['uri'] ?? '';
		$track_id = str_replace( 'spotify:track:', '', $uri );

		// Handle artist names list
		$artist_string = $raw['artist_names'] ?? '';
		$artist_array  = array_map( 'trim', explode( ',', $artist_string ) );

		return array(
			'rank'             => intval( $raw['rank'] ?? 0 ),
			'spotify_uri'      => $uri,
			'spotify_track_id' => $track_id,
			'track_name'       => $raw['track_name'] ?? 'Unknown',
			'artist_names_raw' => $artist_string,
			'artist_names_arr' => $artist_array,
			'source'           => $raw['source'] ?? '', // actually label/distributor
			'peak_rank'        => isset($raw['peak_rank']) ? intval($raw['peak_rank']) : null,
			'previous_rank'    => isset($raw['previous_rank']) ? intval($raw['previous_rank']) : null,
			'weeks_on_chart'   => isset($raw['weeks_on_chart']) ? intval($raw['weeks_on_chart']) : 1,
			'streams'          => isset($raw['streams']) ? intval($raw['streams']) : 0,
			'raw_payload'      => $raw,
		);
	}
}
