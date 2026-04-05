<?php

namespace Charts\Services;

/**
 * Handle ranking analysis and insight generation.
 */
class Analyzer {

	/**
	 * Calculate ranking changes for a specific entry.
	 */
	public function analyze_entry( $entry_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'charts_entries';
		$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $entry_id ) );
		if ( ! $entry ) return;

		// 1. Find the most recent previous entry for this item in this source
		$previous = $wpdb->get_row( $wpdb->prepare(
			"SELECT rank_position, weeks_on_chart, peak_rank, streak 
			 FROM $table 
			 WHERE source_id = %d AND item_type = %s AND item_id = %d AND id < %d 
			 ORDER BY id DESC LIMIT 1",
			$entry->source_id, $entry->item_type, $entry->item_id, $entry_id
		) );

		$update_data = array();

		// 1. Determine Movement
		if ( $previous ) {
			if ( ! isset( $entry->movement_direction ) || empty( $entry->movement_direction ) ) {
				if ( $entry->rank_position < $previous->rank_position ) {
					$update_data['movement_direction'] = 'up';
					$update_data['movement_value']     = $previous->rank_position - $entry->rank_position;
				} elseif ( $entry->rank_position > $previous->rank_position ) {
					$update_data['movement_direction'] = 'down';
					$update_data['movement_value']     = $entry->rank_position - $previous->rank_position;
				} else {
					$update_data['movement_direction'] = 'same';
					$update_data['movement_value']     = 0;
				}
			}

			// 2. Peaks & Weeks (Respect CSV if non-zero/not null)
			if ( empty( $entry->peak_rank ) ) {
				$update_data['peak_rank'] = min( $entry->rank_position, $previous->peak_rank ?: 1000 );
			}
			
			if ( empty( $entry->weeks_on_chart ) || $entry->weeks_on_chart <= 1 ) {
				$update_data['weeks_on_chart'] = $previous->weeks_on_chart + 1;
			}
			
			$update_data['is_new_entry'] = 0;
			
			// Re-entry detection
			if ( ! isset( $entry->is_reentry ) ) {
				$update_data['is_reentry'] = 0; // Simplified re-entry
			}
		} else {
			// Brand new entry
			if ( ! isset( $entry->movement_direction ) || empty( $entry->movement_direction ) ) {
				$update_data['movement_direction'] = 'new';
				$update_data['movement_value']     = 0;
			}
			
			if ( empty( $entry->peak_rank ) ) {
				$update_data['peak_rank'] = $entry->rank_position;
			}
			
			if ( empty( $entry->weeks_on_chart ) ) {
				$update_data['weeks_on_chart'] = 1;
			}
			
			$update_data['is_new_entry'] = 1;
			$update_data['is_reentry']   = 0;
		}

		$wpdb->update( $table, $update_data, array( 'id' => $entry_id ) );
	}

	/**
	 * Run full period analysis to generate insights.
	 */
	public function analyze_period( $period_id, $source_id ) {
		global $wpdb;
		$entries_table  = $wpdb->prefix . 'charts_entries';
		$insights_table = $wpdb->prefix . 'charts_insights';

		// Clear existing insights for this period/source to avoid duplicates
		$wpdb->delete( $insights_table, array( 'period_id' => $period_id, 'source_id' => $source_id ) );

		$entries = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $entries_table WHERE period_id = %d AND source_id = %d",
			$period_id, $source_id
		) );

		if ( empty( $entries ) ) return;

		// 1. Highest Debut
		$highest_debut = null;
		foreach ( $entries as $e ) {
			if ( $e->is_new_entry ) {
				if ( ! $highest_debut || $e->rank_position < $highest_debut->rank_position ) {
					$highest_debut = $e;
				}
			}
		}
		if ( $highest_debut ) {
			$this->save_insight( $period_id, $source_id, 'highest_debut', 
				__( 'Highest Debut', 'charts' ),
				sprintf( __( '%s by %s debuts at #%d.', 'charts' ), $highest_debut->track_name, $highest_debut->artist_names, $highest_debut->rank_position ),
				$highest_debut
			);
		}

		// 2. Biggest Climber
		$biggest_climber = null;
		$max_climb = 0;
		foreach ( $entries as $e ) {
			if ( $e->movement_direction === 'up' && $e->movement_value > $max_climb ) {
				$max_climb = $e->movement_value;
				$biggest_climber = $e;
			}
		}
		if ( $biggest_climber ) {
			$this->save_insight( $period_id, $source_id, 'biggest_climber',
				__( 'Biggest Climber', 'charts' ),
				sprintf( __( '%s jumps %d spots to #%d.', 'charts' ), $biggest_climber->track_name, $max_climb, $biggest_climber->rank_position ),
				$biggest_climber
			);
		}

		// 3. Longest Streak (Powerhouse)
		$powerhouse = null;
		$max_weeks = 0;
		foreach ( $entries as $e ) {
			if ( $e->weeks_on_chart > $max_weeks ) {
				$max_weeks = $e->weeks_on_chart;
				$powerhouse = $e;
			}
		}
		if ( $powerhouse && $max_weeks > 5 ) {
			$this->save_insight( $period_id, $source_id, 'longest_streak',
				__( 'Chart Powerhouse', 'charts' ),
				sprintf( __( '%s has spent %d weeks on the charts.', 'charts' ), $powerhouse->track_name, $max_weeks ),
				$powerhouse
			);
		}

		$artist_counts = array();
		foreach ( $entries as $e ) {
			$artists = Normalizer::split_artists( $e->artist_names );
			foreach ( $artists as $a ) {
				if ( empty( $a ) ) continue;
				$artist_counts[ $a ] = ( $artist_counts[ $a ] ?? 0 ) + 1;
			}
		}
		arsort( $artist_counts );
		$top_artist = key( $artist_counts );
		$count      = current( $artist_counts );
		
		if ( $top_artist && $count > 1 ) {
			// Try to find the artist object for a better link
			$norm_top = mb_strtolower( $top_artist );
			$artist_obj = $wpdb->get_row( $wpdb->prepare( "SELECT id, slug FROM {$wpdb->prefix}charts_artists WHERE normalized_name = %s", $norm_top ) );
			
			$this->save_insight( $period_id, $source_id, 'most_entries',
				__( 'Dominating the Chart', 'charts' ),
				sprintf( __( '%s currently has %d tracks on the chart.', 'charts' ), $top_artist, $count ),
				array( 
					'artist' => $top_artist, 
					'count' => $count,
					'item_id' => $artist_obj ? $artist_obj->id : 0,
					'item_slug' => $artist_obj ? $artist_obj->slug : '',
					'item_type' => 'artist'
				)
			);
		}
	}

	private function save_insight( $period_id, $source_id, $type, $title, $summary, $payload ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'charts_insights', array(
			'period_id'    => $period_id,
			'source_id'    => $source_id,
			'insight_type' => $type,
			'title'        => $title,
			'summary'      => $summary,
			'payload_json' => wp_json_encode( $payload ),
			'created_at'   => current_time( 'mysql' ),
		) );
	}

	/**
	 * Get latest insights for the frontend/admin.
	 */
	public function get_latest_insights( $limit = 6 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT i.*, p.period_start, s.source_name, s.platform
			 FROM {$wpdb->prefix}charts_insights i
			 JOIN {$wpdb->prefix}charts_periods p ON p.id = i.period_id
			 JOIN {$wpdb->prefix}charts_sources s ON s.id = i.source_id
			 ORDER BY i.created_at DESC LIMIT %d",
			$limit
		) );
	}
}
