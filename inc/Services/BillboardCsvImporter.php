<?php
namespace Charts\Services;

class BillboardCsvImporter {
	public function run($csv_content, $meta) {
		global $wpdb;
		$lines = explode("\n", str_replace("\r", "", trim($csv_content)));
		if (count($lines) < 2) return new \WP_Error('empty_csv', 'CSV is empty or invalid.');

		// Ensure Billboard Source
		$source_table = $wpdb->prefix . 'charts_sources';
		$source_id = $wpdb->get_var("SELECT id FROM $source_table WHERE platform = 'billboard' LIMIT 1");
		if (!$source_id) {
			$wpdb->insert($source_table, [
				'source_name' => 'Billboard Arabia Hot 100', 'platform' => 'billboard', 'source_type' => 'manual',
				'country_code' => 'global', 'frequency' => 'weekly', 'chart_type' => 'top-songs', 'is_active' => 1
			]);
			$source_id = $wpdb->insert_id;
		}

		$import_flow = new \Charts\Services\ImportFlow();
		$period_id = $import_flow->ensure_period('weekly', current_time('Y-m-d'));

		$headers = str_getcsv(array_shift($lines));
		// Remove BOM
		if (strpos($headers[0], "\xEF\xBB\xBF") === 0) $headers[0] = substr($headers[0], 3);
		$headers = array_map('trim', $headers);

		$imported = 0;
		foreach ($lines as $line) {
			if (empty(trim($line))) continue;
			$row = str_getcsv($line);
			if (count($row) < 3) continue;

			$data = array_combine(array_slice($headers, 0, count($row)), $row);
			
			$rank = intval($data['rank'] ?? 0);
			$title = trim($data['track_name'] ?? '');
			$artist_str = trim($data['artist_names'] ?? '');
			$image = trim($data['cover_image'] ?? '');
			$streams = intval($data['streams'] ?? 0);

			if (!$title || !$artist_str) continue;

			$artists = explode(',', $artist_str);
			$primary_artist = trim($artists[0]);

			$artist_id = \Charts\Core\EntityManager::ensure_artist($primary_artist);
			$track_id = \Charts\Core\EntityManager::ensure_track($title, $artist_id, ['cover_image' => $image]);

			if ($track_id) {
				$flat = [ 'track_name' => $title, 'artist_names' => $artist_str, 'cover_image' => $image, 'streams' => $streams ];
				$raw = [ 'rank' => $rank, 'peak_rank' => $data['peak_rank'] ?? '', 'previous_rank' => $data['previous_rank'] ?? '', 'weeks_on_chart' => $data['weeks_on_chart'] ?? '' ];
				
				$entry_id = $import_flow->upsert_entry($source_id, $period_id, 'track', $track_id, $raw, $flat);
				if ($entry_id) {
					// Update actual rank
					$wpdb->update($wpdb->prefix . 'charts_entries', ['rank_position' => $rank], ['id' => $entry_id]);
					$imported++;
				}
			}
		}

		\Charts\Core\Intelligence::recalculate_all();

		return [ 'saved' => $imported, 'parsed' => count($lines), 'source_id' => $source_id, 'period_id' => $period_id, 'skipped' => count($lines) - $imported ];
	}
}
