<?php

namespace Charts\Admin;

/**
 * Handle chart sources administration and default seeding.
 */
class SourceManager {

	/**
	 * Seed default sources if they don't exist.
	 */
	public function seed_defaults() {
		$this->seed_sources();
		$this->seed_definitions();
	}

	/**
	 * Seed default sources.
	 */
	private function seed_sources() {
		global $wpdb;

		$table = $wpdb->prefix . 'charts_sources';

		$sources = array(
			// Spotify Egypt
			array(
				'source_name'  => 'Spotify Egypt Weekly Top Songs',
				'platform'     => 'spotify',
				'source_type'  => 'manual_import',
				'country_code' => 'eg',
				'chart_type'   => 'top-songs',
				'frequency'    => 'weekly',
				'source_url'   => 'manual',
				'parser_key'   => 'spotify-csv',
			),
			array(
				'source_name'  => 'Spotify Egypt Daily Top Songs',
				'platform'     => 'spotify',
				'source_type'  => 'manual_import',
				'country_code' => 'eg',
				'chart_type'   => 'top-songs',
				'frequency'    => 'daily',
				'source_url'   => 'manual',
				'parser_key'   => 'spotify-csv',
			),
			// YouTube Egypt
			array(
				'source_name'  => 'YouTube Egypt Weekly Top Songs',
				'platform'     => 'youtube',
				'source_type'  => 'live_scrape',
				'country_code' => 'eg',
				'chart_type'   => 'top-songs',
				'frequency'    => 'weekly',
				'source_url'   => 'https://charts.youtube.com/charts/TopSongs/eg/weekly',
				'parser_key'   => 'youtube-v1',
			),
			array(
				'source_name'  => 'YouTube Egypt Daily Top Music Videos',
				'platform'     => 'youtube',
				'source_type'  => 'live_scrape',
				'country_code' => 'eg',
				'chart_type'   => 'top-videos',
				'frequency'    => 'daily',
				'source_url'   => 'https://charts.youtube.com/charts/TopVideos/eg/daily',
				'parser_key'   => 'youtube-v1',
			),
		);

		foreach ( $sources as $source ) {
			// Check if source exists by Core parameters to ensure migration
			$exists = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $table WHERE platform = %s AND country_code = %s AND chart_type = %s AND frequency = %s", 
				$source['platform'], $source['country_code'], $source['chart_type'], $source['frequency']
			) );

			if ( ! $exists ) {
				$wpdb->insert( $table, $source );
			} else {
				// Update existing records with new metadata (type, url, parser)
				$wpdb->update( $table, array( 
					'source_type' => $source['source_type'], 
					'source_url'  => $source['source_url'],
					'parser_key'  => $source['parser_key']
				), array( 'id' => $exists ) );
			}
		}
	}

	/**
	 * Seed default chart definitions.
	 */
	public function seed_definitions() {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';

		$definitions = array(
			array(
				'title'         => 'Top Songs Egypt',
				'slug'          => 'top-songs',
				'chart_summary' => 'The most streamed tracks in Egypt across all platforms.',
				'chart_type'    => 'top-songs',
				'item_type'     => 'track',
				'country_code'  => 'eg',
				'frequency'     => 'weekly',
				'is_featured'   => 1,
				'menu_order'    => 1,
			),
			array(
				'title'         => 'Top Music Videos Egypt',
				'slug'          => 'top-videos',
				'chart_summary' => 'Trending music videos and visual hits in the Egyptian market.',
				'chart_type'    => 'top-videos',
				'item_type'     => 'video',
				'country_code'  => 'eg',
				'frequency'     => 'weekly',
				'is_featured'   => 1,
				'menu_order'    => 2,
			),
			array(
				'title'         => 'Top Artists Egypt',
				'slug'          => 'top-artists',
				'chart_summary' => 'The most popular performers and creators in Egypt.',
				'chart_type'    => 'top-artists',
				'item_type'     => 'artist',
				'country_code'  => 'eg',
				'frequency'     => 'weekly',
				'is_featured'   => 1,
				'menu_order'    => 3,
			),
			array(
				'title'         => 'Viral 50 Egypt',
				'slug'          => 'viral',
				'chart_summary' => 'The tracks gaining the most social traction and velocity right now.',
				'chart_type'    => 'viral',
				'item_type'     => 'track',
				'country_code'  => 'eg',
				'frequency'     => 'daily',
				'is_featured'   => 0,
				'menu_order'    => 4,
			),
		);

		foreach ( $definitions as $def ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $def['slug'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table, $def );
			}
		}
	}

	/**
	 * Get all sources.
	 */
	public function get_sources() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_sources ORDER BY platform ASC, country_code ASC" );
	}

	/**
	 * Get a single source.
	 */
	public function get_source( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_sources WHERE id = %d", $id ) );
	}

	/**
	 * Delete a source.
	 */
	public function delete_source( $id ) {
		global $wpdb;
		return $wpdb->delete( "{$wpdb->prefix}charts_sources", array( 'id' => $id ) );
	}

	/**
	 * Save or update a source.
	 */
	public function save_source( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';

		$fields = array(
			'source_name'  => sanitize_text_field( $data['source_name'] ),
			'platform'     => sanitize_text_field( $data['platform'] ),
			'source_type'  => sanitize_text_field( $data['source_type'] ),
			'source_url'   => esc_url_raw( $data['source_url'] ),
			'country_code' => strtolower( sanitize_text_field( $data['country_code'] ) ),
			'frequency'    => sanitize_text_field( $data['frequency'] ),
			'chart_type'   => sanitize_text_field( $data['chart_type'] ),
			'parser_key'   => sanitize_text_field( $data['parser_key'] ),
			'is_active'    => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
		);

		// Special case for manual imports
		if ( $fields['source_type'] === 'manual_import' && empty( $fields['source_url'] ) ) {
			$fields['source_url'] = 'manual';
		}

		if ( ! empty( $data['id'] ) ) {
			$id = intval( $data['id'] );
			$wpdb->update( $table, $fields, array( 'id' => $id ) );
			return $id;
		}

		$wpdb->insert( $table, $fields );
		return $wpdb->insert_id;
	}

	/**
	 * Toggle source status.
	 */
	public function toggle_status( $id ) {
		global $wpdb;
		$source = $this->get_source( $id );
		if ( ! $source ) return false;
		
		$new_status = $source->is_active ? 0 : 1;
		return $wpdb->update( "{$wpdb->prefix}charts_sources", array( 'is_active' => $new_status ), array( 'id' => $id ) );
	}

	// ─────────────────────────────────────────────
	//  Chart Definitions (Dynamic Charts)
	// ─────────────────────────────────────────────

	public function get_definitions( $only_active = false ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}charts_definitions";
		if ( $only_active ) $sql .= " WHERE is_public = 1";
		$sql .= " ORDER BY menu_order ASC, title ASC";
		return $wpdb->get_results( $sql );
	}

	public function get_definition( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $id ) );
	}

	public function get_definition_by_slug( $slug ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE slug = %s", $slug ) );
	}

	public function delete_definition( $id ) {
		global $wpdb;
		return $wpdb->delete( "{$wpdb->prefix}charts_definitions", array( 'id' => $id ) );
	}

	public function save_definition( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';

		$fields = array(
			'title'           => sanitize_text_field( $data['title'] ),
			'slug'            => sanitize_title( $data['slug'] ),
			'chart_summary'   => sanitize_textarea_field( $data['chart_summary'] ),
			'chart_type'      => sanitize_text_field( $data['chart_type'] ),
			'item_type'       => sanitize_text_field( $data['item_type'] ),
			'country_code'    => strtolower( sanitize_text_field( $data['country_code'] ) ),
			'frequency'       => sanitize_text_field( $data['frequency'] ),
			'platform'        => sanitize_text_field( $data['platform'] ?? 'all' ),
			'cover_image_url' => esc_url_raw( $data['cover_image_url'] ?? '' ),
			'accent_color'    => sanitize_text_field( $data['accent_color'] ?? '#6366f1' ),
			'is_public'       => isset( $data['is_public'] ) ? (int) $data['is_public'] : 1,
			'is_featured'     => isset( $data['is_featured'] ) ? (int) $data['is_featured'] : 0,
			'archive_enabled' => isset( $data['archive_enabled'] ) ? (int) $data['archive_enabled'] : 1,
			'menu_order'      => isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0,
		);

		if ( ! empty( $data['id'] ) ) {
			$id = intval( $data['id'] );
			$wpdb->update( $table, $fields, array( 'id' => $id ) );
			return $id;
		}

		$wpdb->insert( $table, $fields );
		return $wpdb->insert_id;
	}
}
