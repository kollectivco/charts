<?php

namespace Charts\Services;

/**
 * Core import pipeline: period management, entry creation, YouTube imports.
 */
class ImportFlow {

	/**
	 * Run a live-scrape import (YouTube only now; Spotify is manual CSV).
	 */
	public function run( $source_id ) {
		global $wpdb;

		$source_manager = new \Charts\Admin\SourceManager();
		$source         = $source_manager->get_source( $source_id );

		if ( ! $source ) {
			return new \WP_Error( 'source_not_found', 'Source not found.' );
		}
		if ( in_array( $source->source_type, array( 'manual_import', 'metadata_only' ), true ) ) {
			return new \WP_Error( 'manual_only', __( 'This source requires manual file import.', 'charts' ) );
		}

		// Only YouTube live scrape
		if ( $source->platform !== 'youtube' ) {
			return new \WP_Error( 'not_implemented', 'Live scrape only supported for YouTube.' );
		}

		$connector = new \Charts\Connectors\YouTubeConnector();

		try {
			$result = $connector->run( $source_id );
			if ( is_wp_error( $result ) ) return $result;

			$run_id = $result['run_id'];
			$rows   = $result['rows'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'import_failed', $e->getMessage() );
		}

		$period_id     = $this->ensure_period( $source->frequency );
		$matched_items = 0;

		foreach ( $rows as $row ) {
			$title = $row['title'] ?? 'Unknown';
			$artists = $row['artists'] ?? array();
			$primary_artist = isset( $artists[0] ) ? $artists[0] : 'Unknown Artist';
			
			$artist_id = $this->quick_artist( $this->normalize_title( $primary_artist ) );

			$item_type = $source->chart_type === 'top-artists' ? 'artist'
				: ( $source->chart_type === 'top-videos' ? 'video' : 'track' );

			if ( $item_type === 'artist' ) {
				$item_id = $artist_id;
			} elseif ( $item_type === 'video' ) {
				$item_id = $this->quick_video( $this->normalize_title( $title ), $artist_id, $row['youtube_id'] ?? null, $row['image'] ?? null );
				// Link all artists for video
				$all_yt_artists = \Charts\Services\Normalizer::split_artists( implode( ', ', $row['artists'] ?? array() ) );
				foreach ( $all_yt_artists as $a_name ) {
					$a_id = $this->quick_artist( $a_name );
					if ( $item_id && $a_id ) {
						$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->prefix}charts_video_artists (video_id, artist_id) VALUES (%d, %d)", $item_id, $a_id ) );
					}
				}
			} else {
				$item_id = $this->quick_track( $this->normalize_title( $title ), $artist_id, $row['image'] ?? null );
				// Link all artists for track
				$all_yt_artists = \Charts\Services\Normalizer::split_artists( implode( ', ', $row['artists'] ?? array() ) );
				foreach ( $all_yt_artists as $a_name ) {
					$a_id = $this->quick_artist( $a_name );
					if ( $item_id && $a_id ) {
						$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->prefix}charts_track_artists (track_id, artist_id) VALUES (%d, %d)", $item_id, $a_id ) );
					}
				}
			}

			if ( ! $item_id ) continue;

			$flat = array(
				'track_name'   => $row['title'] ?? '',
				'artist_names' => implode( ', ', $row['artists'] ?? array() ),
				'cover_image'  => $row['image'] ?? null,
				'spotify_id'   => null,
				'youtube_id'   => $row['youtube_id'] ?? null,
				'streams'      => 0,
			);

			$entry_id = $this->upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat );
			if ( $entry_id ) {
				try { ( new Analyzer() )->analyze_entry( $entry_id ); } catch ( \Exception $e ) {}
				$matched_items++;
			}
		}

		$wpdb->update( $wpdb->prefix . 'charts_import_runs', array(
			'status'        => 'completed',
			'parsed_rows'   => count( $rows ),
			'matched_items' => $matched_items,
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		$wpdb->update( $wpdb->prefix . 'charts_sources', array(
			'last_run_at'     => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
		), array( 'id' => $source_id ) );

		return $matched_items;
	}

	/**
	 * Ensure period exists for a given frequency + date (create if needed).
	 * Returns period_id.
	 */
	public function ensure_period( $frequency, $custom_date = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_periods';

		$frequency  = strtolower( trim( $frequency ?? 'weekly' ) );
		$start_date = $custom_date ? trim( $custom_date ) : current_time( 'Y-m-d' );

		// Validate date format; fall back to today
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			$start_date = current_time( 'Y-m-d' );
		}

		// For weekly, align to Monday of that week
		if ( $frequency === 'weekly' ) {
			$ts  = strtotime( $start_date );
			$dow = (int) date( 'N', $ts ); // 1=Mon, 7=Sun
			if ( $dow > 1 ) {
				$ts = $ts - ( ( $dow - 1 ) * 86400 );
			}
			$start_date = date( 'Y-m-d', $ts );
		}

		$period_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE frequency = %s AND period_start = %s",
			$frequency, $start_date
		) );

		if ( ! $period_id ) {
			$end_date = $frequency === 'weekly'
				? date( 'Y-m-d', strtotime( $start_date . ' +6 days' ) )
				: $start_date;

			$wpdb->insert( $table, array(
				'frequency'    => $frequency,
				'period_start' => $start_date,
				'period_end'   => $end_date,
				'label'        => ucfirst( $frequency ) . ': ' . $start_date,
				'created_at'   => current_time( 'mysql' ),
			) );
			$period_id = $wpdb->insert_id;
		}

		return $period_id;
	}

	/**
	 * Upsert a chart entry row.
	 * Unique key: (source_id, period_id, rank_position).
	 * Stores both relational item_id and flat denormalized columns for direct frontend queries.
	 */
	public function upsert_entry( $source_id, $period_id, $item_type, $item_id, $row, $flat = array() ) {
		global $wpdb;
		$table        = $wpdb->prefix . 'charts_entries';
		$rank         = intval( $row['rank'] ?? $row['rank_position'] ?? 0 );
		$track_name   = $this->normalize_title( $flat['track_name'] ?? $row['track_name'] ?? $row['item_title'] ?? $row['title'] ?? '' );
		$artist_names = $this->normalize_title( $flat['artist_names'] ?? $row['artist_names_raw'] ?? $row['artist_names'] ?? ( isset( $row['artists'] ) ? ( is_array( $row['artists'] ) ? implode( ', ', $row['artists'] ) : $row['artists'] ) : '' ) );
		$cover_image  = esc_url_raw( $flat['cover_image'] ?? $row['image'] ?? '' );
		$spotify_id   = sanitize_text_field( $flat['spotify_id'] ?? $row['spotify_track_id'] ?? '' );
		$youtube_id   = sanitize_text_field( $flat['youtube_id'] ?? $row['youtube_id'] ?? '' );
		$streams      = intval( $flat['streams'] ?? $row['streams'] ?? 0 );
		$views_count  = intval( $flat['views_count'] ?? $row['views_count'] ?? 0 );
		$source_url   = esc_url_raw( $flat['source_url'] ?? $row['source_url'] ?? '' );

		// Franko Transliteration
		$track_franko  = Normalizer::to_franko( $track_name );
		$artist_franko = Normalizer::to_franko( $artist_names );

		// Resolve canonical slug if missing
		$item_slug = $flat['item_slug'] ?? null;
		if ( ! $item_slug && $item_id ) {
			$entity_table = $wpdb->prefix . 'charts_' . ( $item_type === 'video' ? 'videos' : ($item_type === 'artist' ? 'artists' : 'tracks') );
			$item_slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM $entity_table WHERE id = %d", $item_id ) );
		}

		$existing_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE source_id = %d AND period_id = %d AND rank_position = %d",
			$source_id, $period_id, $rank
		) );

		$data = array(
			'source_id'        => intval( $source_id ),
			'period_id'        => intval( $period_id ),
			'item_type'        => $item_type ?: 'track',
			'item_id'          => intval( $item_id ),
			'rank_position'    => $rank,
			'previous_rank'    => ( isset( $row['previous_rank'] ) && $row['previous_rank'] !== '' ) ? intval( $row['previous_rank'] ) : null,
			'peak_rank'        => ( isset( $row['peak_rank'] ) && $row['peak_rank'] !== '' ) ? intval( $row['peak_rank'] ) : $rank,
			'weeks_on_chart'   => intval( $row['weeks_on_chart'] ?? 1 ),
			'streams'          => $streams,
			'streams_count'    => $streams,
			'views_count'      => $views_count,
			// Flat display columns — no JOIN needed on frontend
			'track_name'       => $track_name,
			'track_name_franko' => $track_franko !== $track_name ? $track_franko : null,
			'artist_names'     => $artist_names,
			'artist_names_franko' => $artist_franko !== $artist_names ? $artist_franko : null,
			'cover_image'      => $cover_image ?: null,
			'item_slug'        => $item_slug,
			'spotify_id'       => $spotify_id ?: null,
			'youtube_id'       => $youtube_id ?: null,
			'source_url'       => $source_url ?: null,
			'raw_payload_json' => wp_json_encode( $row ),
			'updated_at'       => current_time( 'mysql' ),
		);

		if ( $existing_id ) {
			$wpdb->update( $table, $data, array( 'id' => $existing_id ) );
			return $existing_id;
		}

		$data['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id ?: false;
	}

	/**
	 * Canonical name normalization.
	 * Strips platform branding and extraneous noise.
	 */
	public function normalize_title( $str ) {
		if ( empty($str) ) return '';
		
		// Remove platform suffixes
		$noise = array(
			'/\(Spotify\)/i',
			'/\(Official Audio\)/i',
			'/\(Official Music Video\)/i',
			'/\(Music Video\)/i',
			'/\(Official Video\)/i',
			'/\[Official\]/i',
			'/\[MV\]/i',
			'/\(Lyric Video\)/i',
			'/\(Audio\)/i',
		);
		
		$str = preg_replace( $noise, '', $str );
		return trim( preg_replace( '/\s+/', ' ', $str ) );
	}

	/**
	 * Canonical slug normalization.
	 */
	public function normalize_slug_name( $str ) {
		return mb_strtolower( $this->normalize_title( $str ) );
	}

	// -------------------------------------------------------------------------
	// Quick entity helpers (for YouTube rows — no enrichment)
	// -------------------------------------------------------------------------

	private function quick_artist( $display_name ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_artists';
		$normalized = mb_strtolower( $this->normalize_title( trim( $display_name ) ) );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE normalized_name = %s", $normalized ) );
		if ( $id ) return $id;

		$franko = Normalizer::to_franko( $display_name );
		$slug = $this->unique_slug( $table, sanitize_title( $display_name ) );
		$wpdb->insert( $table, array(
			'display_name'    => $display_name,
			'display_name_franko' => $franko !== $display_name ? $franko : null,
			'normalized_name' => $normalized,
			'slug'            => $slug,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function quick_track( $title, $artist_id, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_tracks';
		$normalized = mb_strtolower( $this->normalize_title( trim( $title ) ) );
		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) return $id;

		$franko = Normalizer::to_franko( $title );
		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'cover_image'       => $image,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function quick_video( $title, $artist_id, $youtube_id = null, $image = null ) {
		global $wpdb;
		$table      = $wpdb->prefix . 'charts_videos';
		$normalized = mb_strtolower( $this->normalize_title( trim( $title ) ) );
		$id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE normalized_title = %s AND primary_artist_id = %d",
			$normalized, $artist_id
		) );
		if ( $id ) return $id;

		$franko = Normalizer::to_franko( $title );
		$slug = $this->unique_slug( $table, sanitize_title( $title . '-' . $artist_id ) );
		$wpdb->insert( $table, array(
			'title'             => $title,
			'title_franko'      => $franko !== $title ? $franko : null,
			'normalized_title'  => $normalized,
			'slug'              => $slug,
			'primary_artist_id' => $artist_id,
			'youtube_id'        => $youtube_id,
			'cover_image'       => $image,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		) );
		return $wpdb->insert_id;
	}

	private function unique_slug( $table, $slug ) {
		global $wpdb;
		$orig = $slug;
		$i    = 1;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) ) ) {
			$slug = $orig . '-' . $i++;
		}
		return $slug;
	}
}
