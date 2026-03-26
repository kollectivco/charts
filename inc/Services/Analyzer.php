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

		if ( $previous ) {
			// Movement
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

			// Peaks & Weeks
			$update_data['peak_rank']      = min( $entry->rank_position, $previous->peak_rank ?: 1000 );
			$update_data['weeks_on_chart'] = $previous->weeks_on_chart + 1;
			$update_data['is_new_entry']   = 0;
			
			// Re-entry detection (if previous was far in the past - simplified for Phase 1)
			$update_data['is_reentry'] = 0; 
		} else {
			// Brand new entry
			$update_data['movement_direction'] = 'new';
			$update_data['movement_value']     = 0;
			$update_data['peak_rank']          = $entry->rank_position;
			$update_data['weeks_on_chart']     = 1;
			$update_data['is_new_entry']       = 1;
			$update_data['is_reentry']         = 0;
		}

		$wpdb->update( $table, $update_data, array( 'id' => $entry_id ) );
	}

	/**
	 * Run full period analysis to generate insights.
	 */
	public function analyze_period( $period_id, $source_id ) {
		global $wpdb;
		// More advanced insight logic for Phase 2
	}
}
