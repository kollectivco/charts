<?php

namespace Charts\Services;

/**
 * Importer for Spotify CSV files.
 */
class SpotifyCsvImporter {

	private $importer_flow;
	private $csv_parser;
	private $enricher;
	private $matcher;
	private $analyzer;

	public function __construct() {
		$this->importer_flow = new ImportFlow();
		$this->csv_parser    = new \Charts\Parsers\SpotifyCsvParser();
		$this->enricher      = new SpotifyEnrichmentService();
		$this->matcher       = new Matcher();
		$this->analyzer      = new Analyzer();
	}

	/**
	 * Run an import from CSV content.
	 */
	public function run( $csv_content, array $meta ) {
		// 1. Parse CSV
		$rows = $this->csv_parser->parse( $csv_content );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		// 2. Fetch or Create Source
		$source_id = $this->ensure_source( $meta );

		// 3. Create Import Run
		global $wpdb;
		$run_id = $this->start_run( $source_id, count( $rows ) );

		// 4. Enrich Rows via API
		$enriched_count = $this->enricher->enrich_rows( $rows );

		// 5. Process Rows
		$matched_items = 0;
		$period_id = $this->importer_flow->ensure_period( $meta['frequency'], $meta['period_date'] );

		foreach ( $rows as $row ) {
			$item_id = $this->process_row( $row );
			if ( $item_id ) {
				$entry_id = $this->importer_flow->upsert_entry( $source_id, $period_id, 'track', $item_id, $row );
				if ( $entry_id ) {
					$this->analyzer->analyze_entry( $entry_id );
					$matched_items++;
				}
			}
		}

		// 6. Complete Run Record
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'              => 'completed',
			'parsed_rows'         => count( $rows ),
			'matched_items'       => $matched_items,
			'enrichment_attempts' => count( $rows ),
			'enrichment_failures' => count( $rows ) - $enriched_count,
			'finished_at'         => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		// 7. Update Source
		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		return $matched_items;
	}

	/**
	 * Ensure the source exists or create it.
	 */
	private function ensure_source( $meta ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';

		$source_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE platform = %s AND country_code = %s AND chart_type = %s AND frequency = %s",
			'spotify', $meta['country'], $meta['chart_type'], $meta['frequency']
		) );

		if ( ! $source_id ) {
			$wpdb->insert( $table, array(
				'source_name'  => $meta['source_name'] ?? "Spotify CSV Import: {$meta['country']} - {$meta['chart_type']}",
				'platform'     => 'spotify',
				'source_type'  => 'manual_import',
				'country_code' => $meta['country'],
				'chart_type'   => $meta['chart_type'],
				'frequency'    => $meta['frequency'],
				'source_url'   => 'manual',
				'parser_key'   => 'spotify-csv',
				'created_at'   => current_time( 'mysql' ),
			) );
			$source_id = $wpdb->insert_id;
		} else {
			// Update source type if needed
			$wpdb->update( $table, array( 'source_type' => 'manual_import' ), array( 'id' => $source_id ) );
		}

		return $source_id;
	}

	/**
	 * Process a single row: match/create entities.
	 */
	private function process_row( $row ) {
		$metadata = $row['enrichment'] ?? array();
		
		// 1. Primary Artist
		$primary_artist_data = $metadata['artists'][0] ?? array( 'name' => $row['artist_names_arr'][0] ?? 'Unknown' );
		$artist_id = $this->matcher->match_artist_extended( $primary_artist_data );

		// 2. Album (if track)
		$album_id = null;
		if ( ! empty( $metadata['album'] ) ) {
			$album_id = $this->matcher->match_album_extended( $metadata['album'], $artist_id );
		}

		// 3. Track
		$track_data = array(
			'title'             => $metadata['official_name'] ?? $row['track_name'],
			'spotify_id'        => $metadata['spotify_id'] ?? $row['spotify_track_id'] ?? null,
			'primary_artist_id' => $artist_id,
			'album_id'          => $album_id,
			'cover_image'       => $metadata['album']['cover_image'] ?? null,
		);

		return $this->matcher->match_track_extended( $track_data );
	}

	/**
	 * Log the start of an import run.
	 */
	private function start_run( $source_id, $count ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_import_runs';
		$wpdb->insert( $table, array(
			'source_id'  => $source_id,
			'run_type'   => 'csv',
			'status'     => 'processing',
			'parsed_rows' => $count,
			'started_at' => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}
}
