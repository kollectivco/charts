<?php

namespace Charts\Services;

/**
 * Handle backfilling and repairing missing media assets for existing records.
 */
class AssetManager {

	private $spotify;
	private $youtube;

	public function __construct() {
		$this->spotify = new SpotifyApiClient();
		$this->youtube = new YouTubeApiClient();
	}

	/**
	 * Backfill missing assets across all supported entity types.
	 */
	public function backfill_all() {
		$results = array(
			'tracks'  => $this->backfill_tracks(),
			'artists' => $this->backfill_artists(),
			'videos'  => $this->backfill_videos(),
		);

		return $results;
	}

	/**
	 * Repair missing track cover images using Spotify API.
	 */
	public function backfill_tracks() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_tracks';

		// Find tracks missing covers that have a Spotify ID
		$tracks = $wpdb->get_results( "SELECT id, spotify_id FROM $table WHERE (cover_image IS NULL OR cover_image = '') AND spotify_id IS NOT NULL AND spotify_id != '' LIMIT 500" );

		if ( empty( $tracks ) ) {
			return array( 'processed' => 0, 'updated' => 0 );
		}

		$ids = array_map( function($t) { return $t->spotify_id; }, $tracks );
		$id_to_record_id = array();
		foreach ( $tracks as $t ) {
			$id_to_record_id[$t->spotify_id] = $t->id;
		}

		$batches = array_chunk( $ids, 50 );
		$updated = 0;

		foreach ( $batches as $batch ) {
			$metadata = $this->spotify->get_tracks( $batch );
			if ( is_wp_error( $metadata ) ) continue;

			foreach ( $metadata as $item ) {
				$sp_id = $item['id'] ?? null;
				$image = $item['album']['images'][0]['url'] ?? null;

				if ( $sp_id && $image && isset( $id_to_record_id[$sp_id] ) ) {
					$wpdb->update( $table, array( 'cover_image' => $image ), array( 'id' => $id_to_record_id[$sp_id] ) );
					$updated++;
				}
			}
		}

		return array( 'processed' => count( $tracks ), 'updated' => $updated );
	}

	/**
	 * Repair missing artist images using Spotify API.
	 */
	public function backfill_artists() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_artists';

		// Find artists missing images that have a Spotify ID
		$artists = $wpdb->get_results( "SELECT id, spotify_id FROM $table WHERE (image IS NULL OR image = '') AND spotify_id IS NOT NULL AND spotify_id != '' LIMIT 500" );

		if ( empty( $artists ) ) {
			return array( 'processed' => 0, 'updated' => 0 );
		}

		$ids = array_map( function($a) { return $a->spotify_id; }, $artists );
		$id_to_record_id = array();
		foreach ( $artists as $a ) {
			$id_to_record_id[$a->spotify_id] = $a->id;
		}

		$batches = array_chunk( $ids, 50 );
		$updated = 0;

		foreach ( $batches as $batch ) {
			$metadata = $this->spotify->get_artists( $batch );
			if ( is_wp_error( $metadata ) ) continue;

			foreach ( $metadata as $item ) {
				$sp_id = $item['id'] ?? null;
				$image = $item['images'][0]['url'] ?? null;

				if ( $sp_id && $image && isset( $id_to_record_id[$sp_id] ) ) {
					$wpdb->update( $table, array( 'image' => $image ), array( 'id' => $id_to_record_id[$sp_id] ) );
					$updated++;
				}
			}
		}

		return array( 'processed' => count( $artists ), 'updated' => $updated );
	}

	/**
	 * Repair missing video thumbnails using YouTube API.
	 */
	public function backfill_videos() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_videos';

		// Find videos missing thumbnails that have a YouTube ID
		$videos = $wpdb->get_results( "SELECT id, youtube_id FROM $table WHERE (thumbnail IS NULL OR thumbnail = '') AND youtube_id IS NOT NULL AND youtube_id != '' LIMIT 500" );

		if ( empty( $videos ) ) {
			return array( 'processed' => 0, 'updated' => 0 );
		}

		$ids = array_map( function($v) { return $v->youtube_id; }, $videos );
		$id_to_record_id = array();
		foreach ( $videos as $v ) {
			$id_to_record_id[$v->youtube_id] = $v->id;
		}

		$batches = array_chunk( $ids, 50 );
		$updated = 0;

		foreach ( $batches as $batch ) {
			$metadata = $this->youtube->get_videos( $batch );
			if ( is_wp_error( $metadata ) ) continue;

			foreach ( $metadata as $item ) {
				$yt_id = $item['id'] ?? null;
				$image = $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'] ?? null;

				if ( $yt_id && $image && isset( $id_to_record_id[$yt_id] ) ) {
					$wpdb->update( $table, array( 'thumbnail' => $image ), array( 'id' => $id_to_record_id[$yt_id] ) );
					$updated++;
				}
			}
		}

		return array( 'processed' => count( $videos ), 'updated' => $updated );
	}
}
