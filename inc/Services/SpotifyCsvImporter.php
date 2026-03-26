<?php

namespace Charts\Services;

/**
 * Importer for Spotify CSV files.
 */
class SpotifyCsvImporter {

	private $import_flow;
	private $csv_parser;
	private $enricher;
	private $matcher;
	private $analyzer;

	public function __construct() {
		$this->import_flow  = new ImportFlow();
		$this->csv_parser   = new \Charts\Parsers\SpotifyCsvParser();
		$this->enricher     = new SpotifyEnrichmentService();
		$this->matcher      = new Matcher();
		$this->analyzer     = new Analyzer();
	}

	/**
	 * Run an import from CSV content.
	 * @param string $csv_content Raw CSV string.
	 * @param array  $meta        country, chart_type, frequency, period_date, source_name.
	 * @return int|WP_Error Number of entries saved, or error.
	 */
	public function run( $csv_content, array $meta ) {
		global $wpdb;

		// 1. Parse CSV -> array of normalized rows
		$rows = $this->csv_parser->parse( $csv_content );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		if ( empty( $rows ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV parsed but contained no valid data rows.', 'charts' ) );
		}

		// 2. Ensure Source exists (match by platform/country/chart_type/frequency)
		$source_id = $this->ensure_source( $meta );
		if ( ! $source_id ) {
			return new \WP_Error( 'source_failed', __( 'Could not create or find a matching source.', 'charts' ) );
		}

		// 3. Create Import Run record
		$run_id = $this->start_run( $source_id, count( $rows ) );

		// 4. Ensure Period
		$period_id = $this->import_flow->ensure_period( $meta['frequency'], $meta['period_date'] );
		if ( ! $period_id ) {
			return new \WP_Error( 'period_failed', __( 'Could not create or find a matching period.', 'charts' ) );
		}

		// 5. Optionally enrich via Spotify API (best-effort; don't fail if API down)
		try {
			$enriched_count = $this->enricher->enrich_rows( $rows );
		} catch ( \Exception $e ) {
			$enriched_count = 0;
		}

		// 6. Process each row
		$saved       = 0;
		$parse_errors = 0;

		foreach ( $rows as $row ) {
			$track_name  = $row['track_name'] ?? '';
			$artists_arr = $row['artist_names_arr'] ?? array( 'Unknown' );
			$artist_str  = $row['artist_names_raw'] ?? implode( ', ', $artists_arr );
			$enrichment  = $row['enrichment'] ?? array();

			// Resolve artist
			$primary_artist_data = ! empty( $enrichment['artists'][0] )
				? $enrichment['artists'][0]
				: array( 'name' => $artists_arr[0] ?? 'Unknown' );
			$artist_id = $this->matcher->match_artist_extended( $primary_artist_data );

			if ( ! $artist_id ) {
				$parse_errors++;
				continue;
			}

			// Resolve track
			$track_data = array(
				'title'             => ! empty( $enrichment['official_name'] ) ? $enrichment['official_name'] : $track_name,
				'spotify_id'        => ! empty( $enrichment['spotify_id'] ) ? $enrichment['spotify_id'] : $row['spotify_track_id'] ?? null,
				'primary_artist_id' => $artist_id,
				'album_id'          => null,
				'cover_image'       => $enrichment['album']['cover_image'] ?? null,
			);

			// Album (if enriched)
			if ( ! empty( $enrichment['album'] ) ) {
				$track_data['album_id'] = $this->matcher->match_album_extended( $enrichment['album'], $artist_id );
			}

			$track_id = $this->matcher->match_track_extended( $track_data );
			if ( ! $track_id ) {
				$parse_errors++;
				continue;
			}

			// Flat denormalized columns stored directly on the entry row
			$flat = array(
				'track_name'   => $track_data['title'],
				'artist_names' => $artist_str,
				'cover_image'  => $track_data['cover_image'],
				'spotify_id'   => $track_data['spotify_id'],
				'youtube_id'   => null,
				'streams'      => intval( $row['streams'] ?? 0 ),
			);

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, 'track', $track_id, $row, $flat );

			if ( $entry_id ) {
				try {
					$this->analyzer->analyze_entry( $entry_id );
				} catch ( \Exception $e ) {
					// Analyzer errors should not block saving
				}
				$saved++;
			} else {
				$parse_errors++;
			}
		}

		// 7. Complete Run
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'              => 'completed',
			'parsed_rows'         => count( $rows ),
			'matched_items'       => $saved,
			'enrichment_attempts' => count( $rows ),
			'enrichment_failures' => count( $rows ) - intval( $enriched_count ),
			'error_message'       => $parse_errors > 0 ? "{$parse_errors} rows skipped due to missing artist/track data." : null,
			'finished_at'         => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		// 8. Update Source timestamps
		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		// Return rich result array for the admin notice
		return array(
			'saved'       => $saved,
			'parsed'      => count( $rows ),
			'source_id'   => $source_id,
			'period_id'   => $period_id,
			'run_id'      => $run_id,
			'skipped'     => $parse_errors,
			'enriched'    => intval( $enriched_count ),
		);
	}

	/**
	 * Find or create source matching import metadata.
	 */
	private function ensure_source( $meta ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';

		// Normalize keys to lowercase
		$country    = strtolower( trim( $meta['country'] ?? '' ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? 'top-songs' ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? 'weekly' ) );

		$source_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE platform = 'spotify' AND country_code = %s AND chart_type = %s AND frequency = %s",
			$country, $chart_type, $frequency
		) );

		if ( ! $source_id ) {
			$source_name = ! empty( $meta['source_name'] )
				? $meta['source_name']
				: "Spotify {$chart_type} ({$country}, {$frequency})";

			$wpdb->insert( $table, array(
				'source_name'  => $source_name,
				'platform'     => 'spotify',
				'source_type'  => 'manual_import',
				'country_code' => $country,
				'chart_type'   => $chart_type,
				'frequency'    => $frequency,
				'source_url'   => 'manual',
				'parser_key'   => 'spotify-csv',
				'is_active'    => 1,
				'created_at'   => current_time( 'mysql' ),
			) );
			$source_id = $wpdb->insert_id;
		} else {
			// Ensure it's marked active
			$wpdb->update( $table, array( 'is_active' => 1, 'source_type' => 'manual_import' ), array( 'id' => $source_id ) );
		}

		return $source_id;
	}

	/**
	 * Log the start of an import run.
	 */
	private function start_run( $source_id, $count ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->insert( $table, array(
			'source_id'   => $source_id,
			'run_type'    => 'csv',
			'status'      => 'processing',
			'parsed_rows' => $count,
			'started_at'  => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}
}
