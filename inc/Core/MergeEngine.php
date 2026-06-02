<?php
namespace Charts\Core;

/**
 * Handles database operations for merging duplicated entities (Artists and Tracks).
 */
class MergeEngine {

	/**
	 * Merge duplicate artists into a single master artist.
	 *
	 * @param int   $master_id     The canonical artist ID to keep.
	 * @param array $duplicate_ids Array of artist IDs to merge into the master.
	 * @return array
	 */
	public static function merge_artists( $master_id, $duplicate_ids ) {
		global $wpdb;
		
		$master_id = intval( $master_id );
		if ( ! $master_id || empty( $duplicate_ids ) ) {
			return array( 'success' => false, 'message' => 'Invalid parameters' );
		}

		$duplicate_ids = array_map( 'intval', $duplicate_ids );
		$duplicate_ids = array_filter( $duplicate_ids, function($id) use ($master_id) {
			return $id > 0 && $id !== $master_id;
		});

		if ( empty( $duplicate_ids ) ) {
			return array( 'success' => false, 'message' => 'No valid duplicates provided' );
		}

		$ids_in = implode( ',', $duplicate_ids );

		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1. Move primary artist on tracks
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}charts_tracks SET primary_artist_id = %d WHERE primary_artist_id IN ($ids_in)",
				$master_id
			) );

			// 2. Move primary artist on videos
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}charts_videos SET primary_artist_id = %d WHERE primary_artist_id IN ($ids_in)",
				$master_id
			) );

			// 3. Move primary artist on albums
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}charts_albums SET primary_artist_id = %d WHERE primary_artist_id IN ($ids_in)",
				$master_id
			) );

			// 4. Move track_artists links
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_track_artists SET artist_id = %d WHERE artist_id IN ($ids_in)",
				$master_id
			) );
			// Delete any remaining duplicates that were ignored due to unique key conflicts
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_track_artists WHERE artist_id IN ($ids_in)" );

			// 5. Move video_artists links
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_video_artists SET artist_id = %d WHERE artist_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_video_artists WHERE artist_id IN ($ids_in)" );

			// 6. Update chart entries pointing to the duplicate artists directly
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_entries SET item_id = %d WHERE item_type = 'artist' AND item_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_entries WHERE item_type = 'artist' AND item_id IN ($ids_in)" );

			// 7. Update Intelligence entries
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_intelligence SET entity_id = %d WHERE entity_type = 'artist' AND entity_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'artist' AND entity_id IN ($ids_in)" );

			// 7.5. Transfer missing metadata to master
			$master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $master_id ) );
			$duplicates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id IN ($ids_in)" );
			
			$update_data = array();
			if ( empty( $master->spotify_id ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->spotify_id ) ) {
						$update_data['spotify_id'] = $dup->spotify_id;
						break;
					}
				}
			}
			if ( empty( $master->image ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->image ) ) {
						$update_data['image'] = $dup->image;
						break;
					}
				}
			}
			if ( empty( $master->display_name_en ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->display_name_en ) ) {
						$update_data['display_name_en'] = $dup->display_name_en;
						break;
					}
				}
			}

			if ( ! empty( $update_data ) ) {
				$wpdb->update( "{$wpdb->prefix}charts_artists", $update_data, array( 'id' => $master_id ) );
			}

			// 8. Delete the duplicate artist records
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_artists WHERE id IN ($ids_in)" );

			$wpdb->query( 'COMMIT' );
			return array( 'success' => true, 'message' => sprintf( 'Successfully merged %d artists into master ID %d.', count($duplicate_ids), $master_id ) );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'success' => false, 'message' => 'Merge failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Merge duplicate tracks into a single master track.
	 *
	 * @param int   $master_id     The canonical track ID to keep.
	 * @param array $duplicate_ids Array of track IDs to merge into the master.
	 * @return array
	 */
	public static function merge_tracks( $master_id, $duplicate_ids ) {
		global $wpdb;
		
		$master_id = intval( $master_id );
		if ( ! $master_id || empty( $duplicate_ids ) ) {
			return array( 'success' => false, 'message' => 'Invalid parameters' );
		}

		$duplicate_ids = array_map( 'intval', $duplicate_ids );
		$duplicate_ids = array_filter( $duplicate_ids, function($id) use ($master_id) {
			return $id > 0 && $id !== $master_id;
		});

		if ( empty( $duplicate_ids ) ) {
			return array( 'success' => false, 'message' => 'No valid duplicates provided' );
		}

		$ids_in = implode( ',', $duplicate_ids );

		$wpdb->query( 'START TRANSACTION' );

		try {
			// 1. Move related videos
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}charts_videos SET related_track_id = %d WHERE related_track_id IN ($ids_in)",
				$master_id
			) );

			// 2. Move track_artists links to master track
			// (We just reassign track_id. If artist is already linked to master track, it will be ignored by UNIQUE index)
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_track_artists SET track_id = %d WHERE track_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_track_artists WHERE track_id IN ($ids_in)" );

			// 3. Update chart entries
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_entries SET item_id = %d WHERE item_type = 'track' AND item_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_entries WHERE item_type = 'track' AND item_id IN ($ids_in)" );

			// 4. Update Intelligence entries
			$wpdb->query( $wpdb->prepare(
				"UPDATE IGNORE {$wpdb->prefix}charts_intelligence SET entity_id = %d WHERE entity_type = 'track' AND entity_id IN ($ids_in)",
				$master_id
			) );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'track' AND entity_id IN ($ids_in)" );

			// 5. Transfer missing metadata to master (Spotify/YouTube IDs, Image)
			$master = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $master_id ) );
			$duplicates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_tracks WHERE id IN ($ids_in)" );
			
			$update_data = array();
			
			if ( empty( $master->spotify_id ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->spotify_id ) ) {
						$update_data['spotify_id'] = $dup->spotify_id;
						break;
					}
				}
			}
			if ( empty( $master->youtube_id ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->youtube_id ) ) {
						$update_data['youtube_id'] = $dup->youtube_id;
						break;
					}
				}
			}
			if ( empty( $master->cover_image ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->cover_image ) ) {
						$update_data['cover_image'] = $dup->cover_image;
						break;
					}
				}
			}
			if ( empty( $master->title_en ) ) {
				foreach ( $duplicates as $dup ) {
					if ( ! empty( $dup->title_en ) ) {
						$update_data['title_en'] = $dup->title_en;
						break;
					}
				}
			}

			if ( ! empty( $update_data ) ) {
				$wpdb->update( "{$wpdb->prefix}charts_tracks", $update_data, array( 'id' => $master_id ) );
			}

			// 6. Delete duplicate tracks
			$wpdb->query( "DELETE FROM {$wpdb->prefix}charts_tracks WHERE id IN ($ids_in)" );

			$wpdb->query( 'COMMIT' );
			return array( 'success' => true, 'message' => sprintf( 'Successfully merged %d tracks into master ID %d.', count($duplicate_ids), $master_id ) );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'success' => false, 'message' => 'Merge failed: ' . $e->getMessage() );
		}
	}
}
