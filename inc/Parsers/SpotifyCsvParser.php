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
		$lines = str_getcsv( trim( $csv_content ), "\n" );
		if ( empty( $lines ) ) {
			return new \WP_Error( 'empty_csv', __( 'The CSV file is empty.', 'charts' ) );
		}

		// Look for the header line. Spotify CSVs often have multiple comment lines at the start.
		$headers = null;
		$row_start = 0;
		foreach ( $lines as $index => $line ) {
			$cols = str_getcsv( $line );
			if ( in_array( 'rank', $cols ) && in_array( 'uri', $cols ) ) {
				$headers = $cols;
				$row_start = $index + 1;
				break;
			}
		}

		if ( ! $headers ) {
			return new \WP_Error( 'invalid_format', __( 'Could not find required columns (rank, uri) in CSV.', 'charts' ) );
		}

		$rows = array();
		for ( $i = $row_start; $i < count( $lines ); $i++ ) {
			$cols = str_getcsv( $lines[$i] );
			if ( count( $cols ) < count( $headers ) ) {
				continue;
			}

			$data = array_combine( $headers, array_slice( $cols, 0, count( $headers ) ) );
			$rows[] = $this->transform_row( $data );
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
