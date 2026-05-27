<?php

namespace Charts\Services;

/**
 * Normalizes and imports Soundcharts API data into existing plugin tables.
 */
class SoundchartsImporter {

	/**
	 * API client.
	 *
	 * @var SoundchartsApiClient
	 */
	private $client;

	/**
	 * Import flow.
	 *
	 * @var ImportFlow
	 */
	private $import_flow;

	/**
	 * Matcher service.
	 *
	 * @var Matcher
	 */
	private $matcher;

	/**
	 * Scoring service.
	 *
	 * @var ChartScoringService
	 */
	private $scoring;

	/**
	 * Counters.
	 *
	 * @var array
	 */
	private $stats = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client      = new SoundchartsApiClient();
		$this->import_flow = new ImportFlow();
		$this->matcher     = new Matcher();
		$this->scoring     = new ChartScoringService();
		$this->reset_stats();
	}

	/**
	 * Preview rows before import.
	 */
	public function preview( array $meta ) {
		$fetched = $this->fetch_rows( $meta );
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}

		$normalized = array();
		foreach ( array_slice( $fetched['rows'], 0, 10 ) as $row ) {
			$normalized_row = $this->normalize_soundcharts_row( $row, $meta );
			$normalized_row['existing'] = $this->lookup_existing_entry_preview( $normalized_row, $meta );
			$normalized[] = $normalized_row;
		}

		return array(
			'rows'          => $normalized,
			'total_rows'    => count( $fetched['rows'] ),
			'request_count' => $fetched['request_count'],
			'endpoint_path' => $fetched['endpoint_path'],
		);
	}

	/**
	 * Execute an import run.
	 */
	public function run( array $meta ) {
		global $wpdb;

		$is_dry_run = ! empty( $meta['dry_run'] );
		$this->reset_stats();

		$definition = $this->get_definition( $meta['chart_id'] ?? 0 );
		if ( ! $definition ) {
			return new \WP_Error( 'missing_chart_definition', __( 'A target chart definition is required.', 'kontentainment-lists' ) );
		}

		$source_id = $this->ensure_soundcharts_source( $definition, $meta );
		if ( ! $source_id ) {
			return new \WP_Error( 'soundcharts_source_failed', __( 'Could not create or resolve a Soundcharts source.', 'kontentainment-lists' ) );
		}

		$period_id = $this->import_flow->ensure_period( $meta['frequency'] ?? $definition->frequency, $meta['period_date'] ?? null );
		if ( ! $period_id ) {
			return new \WP_Error( 'period_failed', __( 'Could not create or resolve the requested period.', 'kontentainment-lists' ) );
		}

		$run_id   = $this->start_run( $source_id, $definition, $period_id, $meta );
		$batch_id = $this->start_batch( $run_id, $source_id, $definition, $meta );

		$fetched = $this->fetch_rows( $meta );
		if ( is_wp_error( $fetched ) ) {
			$this->fail_run( $run_id, $batch_id, $fetched );
			return $fetched;
		}

		$this->stats['request_count'] = (int) $fetched['request_count'];
		$this->stats['fetched_rows']  = count( $fetched['rows'] );
		$seen_keys = array();

		foreach ( $fetched['rows'] as $raw_row ) {
			$normalized = $this->normalize_soundcharts_row( $raw_row, $meta );
			if ( empty( $normalized['artist_name'] ) && empty( $normalized['track_title'] ) ) {
				$this->stats['error_count']++;
				continue;
			}
			if ( ! empty( $normalized['source_item_key'] ) && isset( $seen_keys[ $normalized['source_item_key'] ] ) ) {
				$this->stats['skipped_duplicates']++;
				continue;
			}
			$seen_keys[ $normalized['source_item_key'] ] = true;

			$item = $this->resolve_entities( $normalized, $definition );
			if ( is_wp_error( $item ) ) {
				$this->stats['error_count']++;
				continue;
			}
			$previous_rank = $this->lookup_previous_rank( $source_id, $period_id, $item['item_type'], $item['item_id'] );
			$normalized['previous_rank'] = $previous_rank;
			$normalized['movement'] = $previous_rank ? ( (int) $previous_rank - (int) $normalized['rank_position'] ) : 0;
			$normalized['trend_label'] = $this->calculate_trend_label( $normalized );
			$normalized['is_new_entry'] = $normalized['trend_label'] === 'NEW';

			$score = $this->scoring->score_row( $normalized, $definition );
			$flat  = array(
				'track_name'    => $normalized['track_title'],
				'artist_names'  => $normalized['artist_name'],
				'cover_image'   => $normalized['cover_image'],
				'spotify_id'    => $normalized['spotify_id'],
				'item_slug'     => $item['item_slug'],
				'source_url'    => '',
				'streams'       => (int) $normalized['streams'],
				'views_count'   => (int) $normalized['engagement'],
				'album_title'   => $normalized['album_title'],
				'source_platform' => $normalized['source_platform'],
				'source_provider' => 'soundcharts',
				'country_code'  => $normalized['country_code'],
				'territory_code' => $normalized['territory_code'],
				'chart_type'    => $normalized['chart_type'],
				'source_item_id' => $normalized['source_item_id'],
				'source_item_key' => $normalized['source_item_key'],
				'import_batch_id' => $batch_id,
				'final_score'   => $score['final_score'],
				'score_components_json' => wp_json_encode( $score ),
			);

			$entry_row = array(
				'rank'           => $normalized['rank_position'],
				'rank_position'  => $normalized['rank_position'],
				'previous_rank'  => $normalized['previous_rank'],
				'peak_rank'      => $normalized['peak_rank'],
				'weeks_on_chart' => $normalized['weeks_on_chart'],
				'movement_value' => $normalized['movement'],
				'movement_direction' => strtolower( $normalized['trend_label'] === 'RE' ? 're-entry' : $normalized['trend_label'] ),
				'is_new_entry'   => $normalized['trend_label'] === 'NEW',
				'is_reentry'     => $normalized['trend_label'] === 'RE',
				'streams'        => (int) $normalized['streams'],
				'views_count'    => (int) $normalized['engagement'],
				'raw_payload_json' => wp_json_encode( $raw_row ),
			);

			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}charts_entries WHERE source_id = %d AND period_id = %d AND item_type = %s AND item_id = %d",
					$source_id,
					$period_id,
					$item['item_type'],
					$item['item_id']
				)
			);

			if ( $is_dry_run ) {
				if ( $existing ) {
					$this->stats['updated_entries']++;
				} else {
					$this->stats['imported_rows']++;
				}
				continue;
			}

			$entry_id = $this->import_flow->upsert_entry( $source_id, $period_id, $item['item_type'], $item['item_id'], $entry_row, $flat );

			if ( $entry_id ) {
				if ( $existing ) {
					$this->stats['updated_entries']++;
				} else {
					$this->stats['imported_rows']++;
				}
				( new Analyzer() )->analyze_entry( $entry_id );
			} else {
				$this->stats['error_count']++;
			}
		}

		if ( ! $is_dry_run ) {
			$wpdb->update(
				$wpdb->prefix . 'charts_sources',
				array(
					'last_run_at'     => current_time( 'mysql' ),
					'last_success_at' => current_time( 'mysql' ),
				),
				array( 'id' => $source_id )
			);

			( new Analyzer() )->analyze_period( $period_id, $source_id );
			$this->complete_run( $run_id, $batch_id, $definition, $period_id, $fetched['endpoint_path'] );
		} else {
			$this->complete_dry_run( $run_id, $batch_id, $definition, $period_id, $fetched['endpoint_path'] );
		}

		return array(
			'run_id'          => $run_id,
			'batch_id'        => $batch_id,
			'source_id'       => $source_id,
			'period_id'       => $period_id,
			'imported_rows'   => $this->stats['imported_rows'],
			'updated_entries' => $this->stats['updated_entries'],
			'created_artists' => $this->stats['created_artists'],
			'created_tracks'  => $this->stats['created_tracks'],
			'skipped'         => $this->stats['skipped_duplicates'],
			'errors'          => $this->stats['error_count'],
			'request_count'   => $this->stats['request_count'],
			'fetched_rows'    => $this->stats['fetched_rows'],
			'dry_run'         => $is_dry_run,
		);
	}

	/**
	 * Fetch rows from Soundcharts.
	 */
	private function fetch_rows( array $meta ) {
		$endpoint_path = $this->resolve_endpoint_path( $meta );
		$preset = ! empty( $meta['preset_key'] ) ? SoundchartsPresetRegistry::get( $meta['preset_key'] ) : null;
		$cache_key = 'lists_soundcharts_' . md5( wp_json_encode( array(
			$endpoint_path,
			$meta['country'] ?? ( $preset['default_country'] ?? '' ),
			$meta['period_date'] ?? '',
			$meta['chart_type'] ?? ( $preset['default_chart_type'] ?? '' ),
		) ) );
		$cached = get_transient( $cache_key );
		if ( ! is_array( $cached ) ) {
			$cached = get_transient( 'charts_soundcharts_' . md5( wp_json_encode( array(
				$endpoint_path,
				$meta['country'] ?? ( $preset['default_country'] ?? '' ),
				$meta['period_date'] ?? '',
				$meta['chart_type'] ?? ( $preset['default_chart_type'] ?? '' ),
			) ) ) );
		}
		if ( is_array( $cached ) ) {
			$cached['endpoint_path'] = $endpoint_path;
			return $cached;
		}
		$query = array(
			'country'   => $meta['country'] ?? ( $preset['default_country'] ?? '' ),
			'territory' => $meta['country'] ?? ( $preset['default_country'] ?? '' ),
			'date'      => $meta['period_date'] ?? current_time( 'Y-m-d' ),
			'chartType' => $meta['chart_type'] ?? ( $preset['default_chart_type'] ?? '' ),
			'type'      => $meta['chart_type'] ?? ( $preset['default_chart_type'] ?? '' ),
		);

		$response = $this->client->get_paginated( $endpoint_path, $query, 5 );
		if ( is_wp_error( $response ) ) {
			$response = $this->client->get_paginated( $endpoint_path, $query, 5 );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		$response['endpoint_path'] = $endpoint_path;
		set_transient( $cache_key, $response, 15 * MINUTE_IN_SECONDS );
		return $response;
	}

	/**
	 * Normalize heterogeneous Soundcharts payloads.
	 */
	private function normalize_soundcharts_row( array $row, array $meta ) {
		$artist_name = $this->extract_value( $row, array(
			'artist.name',
			'artistName',
			'artist',
			'performer.name',
			'metadata.artist',
		) );

		$track_title = $this->extract_value( $row, array(
			'track.name',
			'track.title',
			'title',
			'name',
			'metadata.title',
		) );

		$album_title = $this->extract_value( $row, array(
			'album.name',
			'album.title',
			'metadata.album',
		) );

		$rank = (int) $this->extract_value( $row, array( 'rank', 'position', 'currentRank' ) );
		$track_title = $track_title ? $track_title : $album_title;

		$normalized = array(
			'rank_position'  => $rank > 0 ? $rank : 999,
			'previous_rank'  => $this->extract_int_value( $row, array( 'previousRank', 'previous_rank' ) ),
			'peak_rank'      => $this->extract_int_value( $row, array( 'peakRank', 'peak_rank' ) ),
			'weeks_on_chart' => max( 1, (int) $this->extract_int_value( $row, array( 'weeksOnChart', 'weeks_on_chart' ) ) ),
			'artist_name'    => trim( (string) $artist_name ),
			'track_title'    => trim( (string) $track_title ),
			'album_title'    => trim( (string) $album_title ),
			'country_code'   => strtolower( (string) ( $meta['country'] ?? 'eg' ) ),
			'territory_code' => strtolower( (string) ( $meta['country'] ?? 'eg' ) ),
			'chart_type'     => (string) ( $meta['chart_type'] ?? 'top-songs' ),
			'source_platform' => (string) $this->extract_value( $row, array( 'platform', 'source.platform', 'sourcePlatform' ) ),
			'streams'        => (int) $this->extract_int_value( $row, array( 'streams', 'streamCount', 'metrics.streams' ) ),
			'popularity'     => (float) $this->extract_numeric_value( $row, array( 'popularity', 'metrics.popularity' ) ),
			'trend'          => (float) $this->extract_numeric_value( $row, array( 'trend', 'metrics.trend' ) ),
			'engagement'     => (float) $this->extract_numeric_value( $row, array( 'engagement', 'metrics.engagement', 'views', 'viewCount' ) ),
			'cover_image'    => $this->extract_value( $row, array( 'imageUrl', 'coverImage', 'artwork.url', 'thumbnail' ) ),
			'spotify_id'     => $this->extract_value( $row, array( 'spotifyId', 'track.spotifyId' ) ),
			'soundcharts_artist_id' => $this->extract_value( $row, array( 'artist.id', 'artistId', 'soundchartsArtistId' ) ),
			'soundcharts_track_id'  => $this->extract_value( $row, array( 'track.id', 'trackId', 'soundchartsTrackId', 'id' ) ),
		);

		$normalized['source_item_id']  = $normalized['soundcharts_track_id'] ? $normalized['soundcharts_track_id'] : $normalized['soundcharts_artist_id'];
		$normalized['source_item_key'] = sanitize_title( $normalized['artist_name'] . ' ' . $normalized['track_title'] . ' ' . $normalized['rank_position'] );
		$normalized['movement'] = 0;
		$normalized['trend_label'] = 'STABLE';
		$normalized['is_new_entry'] = false;

		return $normalized;
	}

	/**
	 * Resolve artists/tracks against existing entities.
	 */
	private function resolve_entities( array $normalized, $definition ) {
		global $wpdb;

		$item_type = $definition->item_type ? $definition->item_type : 'track';
		if ( $item_type === 'artist' ) {
			$artist_before = $this->lookup_artist_id( $normalized );
			$item_id       = $this->matcher->match_artist_extended(
				array(
					'name'         => $normalized['artist_name'],
					'spotify_id'   => '',
					'soundcharts_id' => $normalized['soundcharts_artist_id'],
				)
			);
			if ( ! $artist_before ) {
				$this->stats['created_artists']++;
			}
			$item_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item_id ) );

			if ( ! empty( $normalized['soundcharts_artist_id'] ) ) {
				$wpdb->update(
					$wpdb->prefix . 'charts_artists',
					array( 'soundcharts_artist_id' => $normalized['soundcharts_artist_id'] ),
					array( 'id' => $item_id )
				);
			}

			return array(
				'item_type' => 'artist',
				'item_id'   => $item_id,
				'item_slug' => $item_slug,
			);
		}

		if ( $item_type === 'video' ) {
			$artist_before = $this->lookup_artist_id( $normalized );
			$artist_id     = $this->matcher->match_artist_extended(
				array(
					'name'           => $normalized['artist_name'],
					'spotify_id'     => '',
					'soundcharts_id' => $normalized['soundcharts_artist_id'],
				)
			);
			if ( ! $artist_before ) {
				$this->stats['created_artists']++;
			}

			$item_id = $this->matcher->match_video(
				$normalized['track_title'],
				$artist_id,
				null,
				array(
					'image'      => $normalized['cover_image'],
					'source_url' => '',
				)
			);
			$item_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_videos WHERE id = %d", $item_id ) );

			return array(
				'item_type' => 'video',
				'item_id'   => $item_id,
				'item_slug' => $item_slug,
			);
		}

		$artist_before = $this->lookup_artist_id( $normalized );
		$artist_id     = $this->matcher->match_artist_extended(
			array(
				'name'           => $normalized['artist_name'],
				'spotify_id'     => '',
				'soundcharts_id' => $normalized['soundcharts_artist_id'],
			)
		);
		if ( ! $artist_before ) {
			$this->stats['created_artists']++;
		}

		if ( ! empty( $normalized['soundcharts_artist_id'] ) ) {
			$wpdb->update(
				$wpdb->prefix . 'charts_artists',
				array( 'soundcharts_artist_id' => $normalized['soundcharts_artist_id'] ),
				array( 'id' => $artist_id )
			);
		}

		$track_before = $this->lookup_track_id( $normalized, $artist_id );
		$item_id      = $this->matcher->match_track_extended(
			array(
				'title'             => $normalized['track_title'],
				'primary_artist_id' => $artist_id,
				'spotify_id'        => $normalized['spotify_id'],
				'soundcharts_id'    => $normalized['soundcharts_track_id'],
				'cover_image'       => $normalized['cover_image'],
			)
		);

		if ( ! $track_before ) {
			$this->stats['created_tracks']++;
		}

		if ( ! empty( $normalized['soundcharts_track_id'] ) ) {
			$wpdb->update(
				$wpdb->prefix . 'charts_tracks',
				array( 'soundcharts_track_id' => $normalized['soundcharts_track_id'] ),
				array( 'id' => $item_id )
			);
		}

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}charts_track_artists (track_id, artist_id) VALUES (%d, %d)",
				$item_id,
				$artist_id
			)
		);

		$item_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $item_id ) );
		return array(
			'item_type' => 'track',
			'item_id'   => $item_id,
			'item_slug' => $item_slug,
		);
	}

	/**
	 * Determine endpoint path from posted form or presets.
	 */
	private function resolve_endpoint_path( array $meta ) {
		if ( ! empty( $meta['preset_key'] ) ) {
			$preset = SoundchartsPresetRegistry::get( $meta['preset_key'] );
			if ( ! empty( $preset['endpoint_path'] ) ) {
				return $preset['endpoint_path'];
			}
		}

		$map        = SoundchartsSettings::get_endpoint_map();
		$chart_type = $meta['chart_type'] ?? 'top-songs';

		return isset( $map[ $chart_type ] ) ? $map[ $chart_type ] : '/api/v2/charts/track';
	}

	/**
	 * Create or update a Soundcharts source record.
	 */
	private function ensure_soundcharts_source( $definition, array $meta ) {
		global $wpdb;

		$table      = $wpdb->prefix . 'charts_sources';
		$country    = strtolower( trim( $meta['country'] ?? $definition->country_code ) );
		$chart_type = strtolower( trim( $meta['chart_type'] ?? $definition->chart_type ) );
		$frequency  = strtolower( trim( $meta['frequency'] ?? $definition->frequency ) );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE platform = 'soundcharts' AND country_code = %s AND chart_type = %s AND frequency = %s",
				$country,
				$chart_type,
				$frequency
			)
		);

		$fields = array(
			'source_name'  => ! empty( $meta['source_name'] ) ? sanitize_text_field( $meta['source_name'] ) : $definition->title . ' · Soundcharts',
			'platform'     => 'soundcharts',
			'source_type'  => 'api_import',
			'country_code' => $country,
			'chart_type'   => $chart_type,
			'frequency'    => $frequency,
			'source_url'   => SoundchartsSettings::get_base_url(),
			'parser_key'   => 'soundcharts-api',
			'is_active'    => 1,
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ) );
			return (int) $id;
		}

		$fields['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $fields );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Begin import run record.
	 */
	private function start_run( $source_id, $definition, $period_id, array $meta ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'charts_import_runs',
			array(
				'source_id'           => $source_id,
				'run_type'            => 'manual',
				'import_source'       => 'soundcharts_api',
				'chart_definition_id' => $definition->id,
				'period_id'           => $period_id,
				'status'              => 'processing',
				'started_at'          => current_time( 'mysql' ),
				'context_json'        => wp_json_encode( $meta ),
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Begin import batch record.
	 */
	private function start_batch( $run_id, $source_id, $definition, array $meta ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'charts_import_batches',
			array(
				'run_id'              => $run_id,
				'source_id'           => $source_id,
				'chart_definition_id' => $definition->id,
				'provider'            => 'soundcharts',
				'source_type'         => 'api',
				'country_code'        => strtolower( trim( $meta['country'] ?? $definition->country_code ) ),
				'territory_code'      => strtolower( trim( $meta['country'] ?? $definition->country_code ) ),
				'chart_type'          => strtolower( trim( $meta['chart_type'] ?? $definition->chart_type ) ),
				'frequency'           => strtolower( trim( $meta['frequency'] ?? $definition->frequency ) ),
				'target_date'         => $meta['period_date'] ?? current_time( 'Y-m-d' ),
				'status'              => 'processing',
				'started_at'          => current_time( 'mysql' ),
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Mark run failure.
	 */
	private function fail_run( $run_id, $batch_id, \WP_Error $error ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'charts_import_runs',
			array(
				'status'        => 'failed',
				'error_message' => $error->get_error_message(),
				'finished_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $run_id )
		);

		$wpdb->update(
			$wpdb->prefix . 'charts_import_batches',
			array(
				'status'      => 'failed',
				'error_count' => 1,
				'finished_at' => current_time( 'mysql' ),
				'notes_json'  => wp_json_encode( array( 'error' => $error->get_error_message() ) ),
			),
			array( 'id' => $batch_id )
		);
	}

	/**
	 * Finalize run and batch.
	 */
	private function complete_run( $run_id, $batch_id, $definition, $period_id, $endpoint_path ) {
		global $wpdb;

		$notes = array(
			'definition_id' => $definition->id,
			'period_id'     => $period_id,
			'endpoint_path' => $endpoint_path,
		);

		$run_update = array(
			'status'              => 'completed',
			'fetched_rows'        => $this->stats['fetched_rows'],
			'parsed_rows'         => $this->stats['fetched_rows'],
			'matched_items'       => $this->stats['imported_rows'],
			'updated_entries'     => $this->stats['updated_entries'],
			'created_artists'     => $this->stats['created_artists'],
			'created_tracks'      => $this->stats['created_tracks'],
			'skipped_duplicates'  => $this->stats['skipped_duplicates'],
			'error_count'         => $this->stats['error_count'],
			'request_count'       => $this->stats['request_count'],
			'batch_id'            => $batch_id,
			'finished_at'         => current_time( 'mysql' ),
			'logs_json'           => wp_json_encode( $notes ),
		);
		$wpdb->update( $wpdb->prefix . 'charts_import_runs', $run_update, array( 'id' => $run_id ) );

		$batch_update = array(
			'status'             => 'completed',
			'fetched_rows'       => $this->stats['fetched_rows'],
			'imported_rows'      => $this->stats['imported_rows'],
			'updated_entries'    => $this->stats['updated_entries'],
			'created_artists'    => $this->stats['created_artists'],
			'created_tracks'     => $this->stats['created_tracks'],
			'skipped_duplicates' => $this->stats['skipped_duplicates'],
			'error_count'        => $this->stats['error_count'],
			'request_count'      => $this->stats['request_count'],
			'endpoint_path'      => $endpoint_path,
			'finished_at'        => current_time( 'mysql' ),
			'notes_json'         => wp_json_encode( $notes ),
		);
		$wpdb->update( $wpdb->prefix . 'charts_import_batches', $batch_update, array( 'id' => $batch_id ) );
	}

	/**
	 * Finalize a dry run without persisting chart changes.
	 */
	private function complete_dry_run( $run_id, $batch_id, $definition, $period_id, $endpoint_path ) {
		global $wpdb;

		$notes = array(
			'definition_id' => $definition->id,
			'period_id'     => $period_id,
			'endpoint_path' => $endpoint_path,
			'dry_run'       => true,
		);

		$wpdb->update(
			$wpdb->prefix . 'charts_import_runs',
			array(
				'status'             => 'completed',
				'fetched_rows'       => $this->stats['fetched_rows'],
				'parsed_rows'        => $this->stats['fetched_rows'],
				'matched_items'      => $this->stats['imported_rows'],
				'updated_entries'    => $this->stats['updated_entries'],
				'created_artists'    => $this->stats['created_artists'],
				'created_tracks'     => $this->stats['created_tracks'],
				'skipped_duplicates' => $this->stats['skipped_duplicates'],
				'error_count'        => $this->stats['error_count'],
				'request_count'      => $this->stats['request_count'],
				'finished_at'        => current_time( 'mysql' ),
				'logs_json'          => wp_json_encode( $notes ),
			),
			array( 'id' => $run_id )
		);

		$wpdb->update(
			$wpdb->prefix . 'charts_import_batches',
			array(
				'status'             => 'completed',
				'preview_rows'       => $this->stats['fetched_rows'],
				'fetched_rows'       => $this->stats['fetched_rows'],
				'imported_rows'      => $this->stats['imported_rows'],
				'updated_entries'    => $this->stats['updated_entries'],
				'created_artists'    => $this->stats['created_artists'],
				'created_tracks'     => $this->stats['created_tracks'],
				'skipped_duplicates' => $this->stats['skipped_duplicates'],
				'error_count'        => $this->stats['error_count'],
				'request_count'      => $this->stats['request_count'],
				'endpoint_path'      => $endpoint_path,
				'finished_at'        => current_time( 'mysql' ),
				'notes_json'         => wp_json_encode( $notes ),
			),
			array( 'id' => $batch_id )
		);
	}

	/**
	 * Reset counters.
	 */
	private function reset_stats() {
		$this->stats = array(
			'fetched_rows'        => 0,
			'imported_rows'       => 0,
			'updated_entries'     => 0,
			'created_artists'     => 0,
			'created_tracks'      => 0,
			'skipped_duplicates'  => 0,
			'error_count'         => 0,
			'request_count'       => 0,
		);
	}

	/**
	 * Extract nested value from payload.
	 */
	private function extract_value( array $row, array $keys ) {
		foreach ( $keys as $key ) {
			$value = $this->dig_value( $row, $key );
			if ( $value !== null && $value !== '' ) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * Extract nested int value from payload.
	 */
	private function extract_int_value( array $row, array $keys ) {
		$value = $this->extract_value( $row, $keys );
		return $value === null || $value === '' ? null : (int) $value;
	}

	/**
	 * Extract nested float-compatible value from payload.
	 */
	private function extract_numeric_value( array $row, array $keys ) {
		$value = $this->extract_value( $row, $keys );
		return $value === null || $value === '' ? 0 : (float) $value;
	}

	/**
	 * Resolve dot-path values from nested arrays.
	 */
	private function dig_value( array $row, $path ) {
		$segments = explode( '.', $path );
		$current  = $row;

		foreach ( $segments as $segment ) {
			if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
				return null;
			}
			$current = $current[ $segment ];
		}

		return $current;
	}

	/**
	 * Lookup a chart definition.
	 */
	private function get_definition( $chart_id ) {
		if ( ! $chart_id ) {
			return null;
		}
		return ( new \Charts\Admin\SourceManager() )->get_definition( (int) $chart_id );
	}

	/**
	 * Lookup existing artist for counters.
	 */
	private function lookup_artist_id( array $normalized ) {
		global $wpdb;

		if ( ! empty( $normalized['soundcharts_artist_id'] ) ) {
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}charts_artists WHERE soundcharts_artist_id = %s",
					$normalized['soundcharts_artist_id']
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}

		$name = Normalizer::normalize_artist( $normalized['artist_name'] );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}charts_artists WHERE normalized_name = %s",
				$name
			)
		);
	}

	/**
	 * Lookup existing track for counters.
	 */
	private function lookup_track_id( array $normalized, $artist_id ) {
		global $wpdb;

		if ( ! empty( $normalized['soundcharts_track_id'] ) ) {
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}charts_tracks WHERE soundcharts_track_id = %s",
					$normalized['soundcharts_track_id']
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}

		$title = Normalizer::normalize_title( $normalized['track_title'] );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}charts_tracks WHERE normalized_title = %s AND primary_artist_id = %d",
				$title,
				$artist_id
			)
		);
	}

	/**
	 * Build a lightweight preview of an existing entry for diff UI.
	 */
	private function lookup_existing_entry_preview( array $normalized, array $meta ) {
		global $wpdb;

		$source_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}charts_sources WHERE platform = 'soundcharts' AND country_code = %s AND chart_type = %s AND frequency = %s LIMIT 1",
				$meta['country'] ?? 'eg',
				$meta['chart_type'] ?? 'top-songs',
				$meta['frequency'] ?? 'weekly'
			)
		);
		if ( ! $source_id ) {
			return null;
		}

		$period_id = $this->import_flow->ensure_period( $meta['frequency'] ?? 'weekly', $meta['period_date'] ?? null );
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT rank_position, previous_rank, peak_rank, weeks_on_chart FROM {$wpdb->prefix}charts_entries WHERE source_id = %d AND period_id = %d AND source_item_key = %s LIMIT 1",
				$source_id,
				$period_id,
				$normalized['source_item_key']
			),
			ARRAY_A
		);

		return $entry ?: null;
	}

	/**
	 * Compare against previous chart period.
	 */
	private function lookup_previous_rank( $source_id, $period_id, $item_type, $item_id ) {
		global $wpdb;

		$previous = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT rank_position, period_id FROM {$wpdb->prefix}charts_entries WHERE source_id = %d AND item_type = %s AND item_id = %d AND period_id <> %d ORDER BY period_id DESC LIMIT 1",
				$source_id,
				$item_type,
				$item_id,
				$period_id
			)
		);

		if ( ! $previous ) {
			return null;
		}

		return (int) $previous->rank_position;
	}

	/**
	 * Convert movement context into editorial trend labels.
	 */
	private function calculate_trend_label( array $normalized ) {
		if ( empty( $normalized['previous_rank'] ) ) {
			return 'NEW';
		}
		if ( ! empty( $normalized['is_reentry'] ) ) {
			return 'RE';
		}
		if ( $normalized['movement'] > 0 ) {
			return 'UP';
		}
		if ( $normalized['movement'] < 0 ) {
			return 'DOWN';
		}
		return 'STABLE';
	}
}
