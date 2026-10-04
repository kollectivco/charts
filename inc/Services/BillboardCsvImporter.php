<?php
namespace Charts\Services;

class BillboardCsvImporter {
	public function run( $csv_content, $meta = array() ) {
		global $wpdb;
		$lines = explode("\n", str_replace("\r", "", trim($csv_content)));
		if (count($lines) < 2) return new \WP_Error('empty_csv', 'CSV is empty or invalid.');

		$source_table = $wpdb->prefix . 'charts_sources';
		$runs_table   = $wpdb->prefix . 'charts_import_runs';
		
		$chart_id = absint( $meta['chart_id'] ?? 0 );
		$definition = $chart_id ? ( new \Charts\Admin\SourceManager() )->get_definition( $chart_id ) : null;
		if ( $chart_id && ! $definition ) return new \WP_Error( 'invalid_chart', __( 'Choose a valid destination chart.', 'charts' ) );
		if ( $definition && ! in_array( $definition->platform ?? 'all', array( 'all', 'billboard' ), true ) ) {
			return new \WP_Error( 'billboard_platform_mismatch', __( 'Choose a chart configured for Billboard Arabia or all platforms.', 'charts' ) );
		}
		$item_type = $definition && $definition->item_type === 'artist' ? 'artist' : 'track';
		$chart_type = $chart_id ? 'cid-' . $chart_id : 'top-songs';
		$country = $definition ? strtolower( $definition->country_code ) : 'global';
		$source_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $source_table WHERE platform = 'billboard' AND chart_type = %s LIMIT 1", $chart_type ) );
		
		if (!$source_id) {
			$wpdb->insert( $source_table, array(
				'source_name' => $definition ? 'Billboard Arabia — ' . $definition->title : 'Billboard Arabia Hot 100',
				'platform' => 'billboard',
				'source_type' => 'manual_import',
				'country_code' => $country,
				'frequency' => 'weekly',
				'chart_type' => $chart_type,
				'source_url' => 'https://www.billboardarabia.com/charts/',
				'parser_key' => 'billboard-csv',
				'is_active' => 1,
			) );
			$source_id = $wpdb->insert_id;
		} else {
			// An explicit import means this source is in use and should be visible on its chart.
			$wpdb->update( $source_table, array( 'is_active' => 1 ), array( 'id' => $source_id ) );
		}
		if ( ! $source_id ) return new \WP_Error( 'billboard_source_failed', __( 'Could not create the Billboard import source.', 'charts' ) );

		// Create run record
		$wpdb->insert( $runs_table, array(
			'source_id'  => $source_id,
			'run_type'   => 'csv',
			'status'     => 'processing',
			'fetched_rows' => max( 0, count( $lines ) - 1 ),
			'parsed_rows' => max( 0, count( $lines ) - 1 ),
			'started_at' => current_time( 'mysql' ),
		) );
		$run_id = (int) $wpdb->insert_id;

		$import_flow = new \Charts\Services\ImportFlow();
		$period_date = sanitize_text_field( $meta['period_date'] ?? current_time( 'Y-m-d' ) );
		$period_id = $import_flow->ensure_period( 'weekly', $period_date );

		$headers = str_getcsv(array_shift($lines));
		if (strpos($headers[0], "\xEF\xBB\xBF") === 0) $headers[0] = substr($headers[0], 3);
		$headers = array_map('trim', $headers);
		
		// Find columns dynamically
		$idx_rank = -1; $idx_title = -1; $idx_artist = -1; $idx_image = -1;
		$idx_artist_en = -1; $idx_title_en = -1;
		$title_priority = $item_type === 'artist'
			? array( 'arabic_artist', 'arabic_artist_name', 'artist', 'artist_name', 'name' )
			: array( 'arabic_title', 'track_name', 'song_title', 'title', 'track', 'song' );
		$artist_priority = $item_type === 'artist'
			? array( 'arabic_artist', 'arabic_artist_name', 'artist', 'artist_name', 'name' )
			: array( 'artist_names', 'arabic_artist', 'artist', 'primary_artist', 'artist_name' );
		$image_priority = array( 'image', 'image_url', 'cover_image', 'thumbnail', 'cover' );
		$title_score = PHP_INT_MAX; $artist_score = PHP_INT_MAX; $image_score = PHP_INT_MAX;
		foreach ($headers as $i => $h) {
			$h_low = strtolower($h);
			if (strpos($h_low, 'rank') !== false || $h_low === '#') $idx_rank = $i;
			if (in_array($h_low, ['english_artist', 'english_artist_name', 'artist_en', 'artist_english', 'name_en'], true)) $idx_artist_en = $i;
			if (in_array($h_low, ['english_title', 'track_en', 'song_en', 'title_en'], true)) $idx_title_en = $i;

			$score = array_search( $h_low, $title_priority, true );
			if ( $score === false && ( strpos( $h_low, 'track' ) !== false || strpos( $h_low, 'song' ) !== false || strpos( $h_low, 'title' ) !== false ) && strpos( $h_low, 'en' ) === false ) $score = 50;
			if ( $score !== false && $score < $title_score ) { $title_score = $score; $idx_title = $i; }
			$score = array_search( $h_low, $artist_priority, true );
			if ( $score === false && ( strpos( $h_low, 'artist' ) !== false || ( $item_type === 'artist' && in_array( $h_low, array( 'name', 'artist_name' ), true ) ) ) && strpos( $h_low, 'en' ) === false ) $score = 50;
			if ( $score !== false && $score < $artist_score ) { $artist_score = $score; $idx_artist = $i; }
			$score = array_search( $h_low, $image_priority, true );
			if ( $score === false && ( strpos( $h_low, 'image' ) !== false || strpos( $h_low, 'cover' ) !== false || strpos( $h_low, 'thumbnail' ) !== false ) ) $score = 50;
			if ( $score !== false && $score < $image_score ) { $image_score = $score; $idx_image = $i; }
		}

		$imported = 0; $created = 0; $errors = [];
		foreach ($lines as $line) {
			if (empty(trim($line))) continue;
			$row = str_getcsv($line);
			if (count($row) < 3) continue;

			$rank = $idx_rank > -1 ? intval($row[$idx_rank] ?? 0) : 0;
			$title = $idx_title > -1 ? trim($row[$idx_title] ?? '') : '';
			$artist_str = $idx_artist > -1 ? trim($row[$idx_artist] ?? '') : '';
			$image = $idx_image > -1 ? trim($row[$idx_image] ?? '') : '';
			$artist_en = $idx_artist_en > -1 ? trim($row[$idx_artist_en] ?? '') : '';
			$title_en = $idx_title_en > -1 ? trim($row[$idx_title_en] ?? '') : '';

			if ( $item_type === 'artist' ) {
				$artist_name = $artist_str ?: $title;
				if ( ! $rank || ! $artist_name ) continue;
				$safe_image = \Charts\Services\BillboardService::sideload_image( $image );
				$artist_id = \Charts\Core\EntityManager::ensure_artist( $artist_name, array(
					'image'           => $safe_image,
					'display_name_en' => $artist_en ?: null,
				) );
				if ( ! $artist_id ) continue;
				$entry_id = $import_flow->upsert_entry( $source_id, $period_id, 'artist', $artist_id, array( 'rank' => $rank ), array( 'track_name' => $artist_name, 'artist_names' => $artist_name, 'cover_image' => $safe_image ) );
				if ( $entry_id ) { $imported++; } else { $errors[] = "Entity Failure ($artist_name)"; }
				continue;
			}

			if (!$title || !$artist_str || !$rank) {
				// Fallback generic index if headers are totally wrong
				if (!$rank) $rank = intval($row[0]);
				if (!$title) $title = trim($row[1]);
				if (!$artist_str) $artist_str = trim($row[2]);
				if (!$title || !$artist_str) continue;
			}

			$artists = \Charts\Services\Normalizer::split_artists($artist_str);
			$primary_artist = trim($artists[0] ?? '');
			
			$artists_en = \Charts\Services\Normalizer::split_artists($artist_en);
			$primary_artist_en = trim($artists_en[0] ?? '');

			if (!$primary_artist) continue;

			$artist_id = \Charts\Core\EntityManager::ensure_artist( $primary_artist, array(
				'image'           => $image,
				'display_name_en' => $primary_artist_en ?: null,
			) );
			$track_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_tracks WHERE title = %s AND primary_artist_id = %d LIMIT 1", $title, $artist_id));
			$track_id = \Charts\Core\EntityManager::ensure_track( $title, $artist_id, array(
				'cover_image' => $image,
				'title_en'    => $title_en ?: null,
			) );

			if ($track_id) {
				if (!$track_exists) $created++;

				// Link all secondary artists
				if ( count( $artists ) > 1 ) {
					$a_ids = array();
					foreach ( $artists as $index => $a_name ) {
						$a_name = trim( $a_name );
						if ( empty( $a_name ) ) continue;
						
						$a_meta = array();
						if ( ! empty( $artists_en[$index] ) ) {
							$a_meta['display_name_en'] = trim( $artists_en[$index] );
						}
						
						$a_id = \Charts\Core\EntityManager::ensure_artist( $a_name, $a_meta );
						if ( $a_id ) $a_ids[] = $a_id;
					}
					if ( ! empty( $a_ids ) ) {
						\Charts\Core\EntityManager::link_artists( $track_id, $a_ids, 'track' );
					}
				}

				// Download billboard image safely
				$safe_image = \Charts\Services\BillboardService::sideload_image($image);

				$flat = [ 'track_name' => $title, 'artist_names' => $artist_str, 'cover_image' => $safe_image ];
				$raw = [ 'rank' => $rank ];
				
				$entry_id = $import_flow->upsert_entry($source_id, $period_id, 'track', $track_id, $raw, $flat);
				if ($entry_id) {
					$wpdb->update($wpdb->prefix . 'charts_entries', ['rank_position' => $rank], ['id' => $entry_id]);
					$imported++;
				} else {
					$errors[] = "Entity Failure ($title)";
				}
			}
		}

		\Charts\Core\Intelligence::recalculate_all();

		// Update run record
		$wpdb->update( $runs_table, array(
			'status'        => ( $imported > 0 || empty( $lines ) ) ? 'completed' : 'failed',
			'matched_items' => $imported,
			'created_items' => $created,
			'error_message' => empty( $errors ) ? null : implode( ' | ', array_slice( $errors, 0, 50 ) ),
			'logs_json'     => wp_json_encode( array( 'provider' => 'billboard', 'skipped' => max( 0, count( $lines ) - $imported ) ) ),
			'finished_at'   => current_time( 'mysql' ),
		), array( 'id' => $run_id ) );

		return [ 'saved' => $imported, 'parsed' => count($lines), 'source_id' => $source_id, 'period_id' => $period_id, 'run_id' => $run_id, 'skipped' => count($lines) - $imported ];
	}
}
