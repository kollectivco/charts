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
	 * Toggle source status.
	 */
	public function toggle_status( $id ) {
		global $wpdb;
		$source = $this->get_source( $id );
		if ( ! $source ) return false;
		
		$new_status = $source->is_active ? 0 : 1;
		return $wpdb->update( "{$wpdb->prefix}charts_sources", array( 'is_active' => $new_status ), array( 'id' => $id ) );
	}
}
