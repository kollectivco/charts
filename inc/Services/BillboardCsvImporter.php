<?php
namespace Charts\Services;

class BillboardCsvImporter {
	public function run($csv_content, $meta) {
		global $wpdb;
		$lines = explode("\n", str_replace("\r", "", trim($csv_content)));
		if (count($lines) < 2) return new \WP_Error('empty_csv', 'CSV is empty or invalid.');

		$source_table = $wpdb->prefix . 'charts_sources';
		$runs_table   = $wpdb->prefix . 'charts_import_runs';
		
		$chart_id = $meta['chart_id'] ?? 0;
		$source_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $source_table WHERE platform = 'billboard' AND chart_type = %s LIMIT 1", $chart_id ? "cid-{$chart_id}" : 'top-songs'));
		
		if (!$source_id) {
			$wpdb->insert($source_table, [
				'source_name' => 'Billboard Arabia', 'platform' => 'billboard', 'source_type' => 'manual',
				'country_code' => 'global', 'frequency' => 'weekly', 'chart_type' => $chart_id ? "cid-{$chart_id}" : 'top-songs', 'is_active' => 1
			]);
			$source_id = $wpdb->insert_id;
		}

		// Create run record
		$wpdb->insert( $runs_table, [
			'source_id'  => $source_id,
			'status'     => 'processing',
			'total_rows' => count($lines),
			'started_at' => current_time( 'mysql' ),
		] );
		$run_id = $wpdb->insert_id;

		$import_flow = new \Charts\Services\ImportFlow();
		$period_id = $import_flow->ensure_period('weekly', current_time('Y-m-d'));

		$headers = str_getcsv(array_shift($lines));
		if (strpos($headers[0], "\xEF\xBB\xBF") === 0) $headers[0] = substr($headers[0], 3);
		$headers = array_map('trim', $headers);
		
		// Find columns dynamically
		$idx_rank = -1; $idx_title = -1; $idx_artist = -1; $idx_image = -1;
		foreach ($headers as $i => $h) {
			$h_low = strtolower($h);
			if (strpos($h_low, 'rank') !== false || $h_low === '#') $idx_rank = $i;
			if (strpos($h_low, 'track') !== false || strpos($h_low, 'song') !== false || strpos($h_low, 'title') !== false) $idx_title = $i;
			if (strpos($h_low, 'artist') !== false) $idx_artist = $i;
			if (strpos($h_low, 'image') !== false || strpos($h_low, 'art') !== false) $idx_image = $i;
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

			if (!$title || !$artist_str || !$rank) {
				// Fallback generic index if headers are totally wrong
				if (!$rank) $rank = intval($row[0]);
				if (!$title) $title = trim($row[1]);
				if (!$artist_str) $artist_str = trim($row[2]);
				if (!$title || !$artist_str) continue;
			}

			$artists = explode(',', $artist_str);
			$primary_artist = trim($artists[0]);

			$artist_id = \Charts\Core\EntityManager::ensure_artist($primary_artist);
			$track_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}charts_tracks WHERE title = %s AND primary_artist_id = %d LIMIT 1", $title, $artist_id));
			$track_id = \Charts\Core\EntityManager::ensure_track($title, $artist_id, ['cover_image' => $image]);

			if ($track_id) {
				if (!$track_exists) $created++;

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
		$wpdb->update( $runs_table, [
			'status'        => 'completed',
			'matched_items' => $imported,
			'created_items' => $created,
			'error_log'     => empty($errors) ? null : implode(" | ", array_slice($errors, 0, 50)),
			'completed_at'  => current_time( 'mysql' ),
		], ['id' => $run_id] );

		return [ 'saved' => $imported, 'parsed' => count($lines), 'source_id' => $source_id, 'period_id' => $period_id, 'run_id' => $run_id, 'skipped' => count($lines) - $imported ];
	}
}
