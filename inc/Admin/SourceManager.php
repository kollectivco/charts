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
		// Seeding disabled - maintain for API compatibility if called elsewhere
	}

	/**
	 * Seed default sources.
	 */
	private function seed_sources() {
		// Seeding disabled
	}

	/**
	 * Seed default chart definitions.
	 */
	public function seed_definitions() {
		// Seeding disabled
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
			'title_ar'        => sanitize_text_field( $data['title_ar'] ?? '' ),
			'slug'            => sanitize_title( $data['slug'] ),
			'chart_summary'   => sanitize_textarea_field( $data['chart_summary'] ),
			'chart_type'      => sanitize_text_field( $data['chart_type'] ),
			'item_type'       => sanitize_text_field( $data['item_type'] ),
			'country_code'    => strtolower( sanitize_text_field( $data['country_code'] ) ),
			'frequency'       => sanitize_text_field( $data['frequency'] ),
			'platform'        => sanitize_text_field( $data['platform'] ?? 'all' ),
			'cover_image_url' => esc_url_raw( $data['cover_image_url'] ?? '' ),
			'accent_color'    => !empty($data['accent_color']) ? (strpos($data['accent_color'], '#') === 0 ? sanitize_text_field($data['accent_color']) : '#' . sanitize_text_field($data['accent_color'])) : '#6366f1',
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
	/**
	 * Safely remove legacy seeded records that match known demo signatures.
	 * This protects user-created charts while cleaning up the DP 'Egypt' presets.
	 */
	public function cleanup_mock_data() {
		global $wpdb;

		// 1. Target legacy definitions
		$mock_slugs = array( 'top-songs', 'top-videos', 'top-artists', 'viral' );
		$mock_titles = array( 
			'Top Songs Egypt', 
			'Top Music Videos Egypt', 
			'Top Artists Egypt', 
			'Viral 50 Egypt' 
		);

		foreach ( $mock_slugs as $idx => $slug ) {
			$title = $mock_titles[$idx];
			// Delete ONLY if BOTH slug and title match the legacy seed exactly
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}charts_definitions WHERE slug = %s AND title = %s", 
				$slug, $title 
			) );
		}

		// 2. Target legacy sources
		$mock_sources = array(
			'Spotify Egypt Weekly Top Songs',
			'Spotify Egypt Daily Top Songs',
			'YouTube Egypt Weekly Top Songs',
			'YouTube Egypt Daily Top Music Videos'
		);

		foreach ( $mock_sources as $source_name ) {
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}charts_sources WHERE source_name = %s", 
				$source_name 
			) );
		}
	}
}
