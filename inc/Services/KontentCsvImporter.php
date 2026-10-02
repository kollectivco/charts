<?php
namespace Charts\Services;

class KontentCsvImporter {
	public function run($csv_content, $meta) {
		global $wpdb;
		$lines = explode("\n", str_replace("\r", "", trim($csv_content)));
		if (count($lines) < 2) return new \WP_Error('empty_csv', 'CSV is empty or invalid.');

		// Ensure Kontent Source
		$source_table = $wpdb->prefix . 'charts_sources';
		
		$chart_id = $meta['chart_id'] ?? 0;
		$chart_def = null;
		if ($chart_id) {
			$chart_def = (new \Charts\Admin\SourceManager())->get_definition($chart_id);
		}
		
		// Auto-detect item type from headers if possible
		$headers = str_getcsv(array_shift($lines));
		if (strpos($headers[0], "\xEF\xBB\xBF") === 0) $headers[0] = substr($headers[0], 3);
		$headers = array_map('trim', $headers);
		
		$item_type = in_array('Artist', $headers) && !in_array('Song', $headers) ? 'artist' : 'track';

		$source_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $source_table WHERE platform = 'kontent' AND chart_type = %s LIMIT 1", $item_type === 'artist' ? 'top-artists' : 'top-songs'));
		if (!$source_id) {
			$wpdb->insert($source_table, [
				'source_name' => 'Kontent Analytics (' . ucfirst($item_type) . ')',
				'platform' => 'kontent',
				'source_type' => 'manual',
				'country_code' => $meta['country'] ?? 'global',
				'frequency' => $meta['period_type'] ?? 'weekly',
				'chart_type' => $item_type === 'artist' ? 'top-artists' : 'top-songs',
				'is_active' => 1
			]);
			$source_id = $wpdb->insert_id;
		}

		$import_flow = new \Charts\Services\ImportFlow();
		$period_id = $import_flow->ensure_period($meta['period_type'] ?? 'weekly', $meta['period_date'] ?? current_time('Y-m-d'));

		$imported = 0;
		foreach ($lines as $line) {
			if (empty(trim($line))) continue;
			$row = str_getcsv($line);
			if (count($row) < 3) continue;

			$data = array_combine(array_slice($headers, 0, count($row)), $row);
			
			$rank = intval($data['#'] ?? 0);
			if (!$rank) continue;

			if ($item_type === 'artist') {
				$title = trim($data['Artist'] ?? '');
				if (!$title) continue;
				$streams = $this->parse_number($data['Followers'] ?? '');

				$artist_id = \Charts\Core\EntityManager::ensure_artist($title);
				if ($artist_id) {
					$flat = [ 'track_name' => $title, 'artist_names' => $title, 'streams' => $streams ];
					$entry_id = $import_flow->upsert_entry($source_id, $period_id, 'artist', $artist_id, $data, $flat);
					if ($entry_id) {
						$wpdb->update($wpdb->prefix . 'charts_entries', ['rank_position' => $rank], ['id' => $entry_id]);
						$imported++;
					}
				}
			} else {
				$title = trim($data['Song'] ?? '');
				$artist_str = trim($data['Artist(s)'] ?? '');
				if (!$title || !$artist_str) continue;
				$streams = $this->parse_number($data['Streams'] ?? '');

				$artists = explode('،', str_replace(',', '،', $artist_str));
				$primary_artist = trim($artists[0]);

				$artist_id = \Charts\Core\EntityManager::ensure_artist($primary_artist);
				$track_id = \Charts\Core\EntityManager::ensure_track($title, $artist_id);

				if ($track_id) {
					$flat = [ 'track_name' => $title, 'artist_names' => $artist_str, 'streams' => $streams ];
					$entry_id = $import_flow->upsert_entry($source_id, $period_id, 'track', $track_id, $data, $flat);
					if ($entry_id) {
						$wpdb->update($wpdb->prefix . 'charts_entries', ['rank_position' => $rank], ['id' => $entry_id]);
						$imported++;
					}
				}
			}
		}

		\Charts\Core\Intelligence::recalculate_all();

		return [ 'saved' => $imported, 'parsed' => count($lines), 'source_id' => $source_id, 'period_id' => $period_id, 'skipped' => count($lines) - $imported ];
	}

	private function parse_number($str) {
		$str = strtoupper(trim($str));
		if (empty($str) || $str === '--') return 0;
		$mult = 1;
		if (strpos($str, 'K') !== false) { $mult = 1000; $str = str_replace('K', '', $str); }
		if (strpos($str, 'M') !== false) { $mult = 1000000; $str = str_replace('M', '', $str); }
		if (strpos($str, 'B') !== false) { $mult = 1000000000; $str = str_replace('B', '', $str); }
		return intval(floatval($str) * $mult);
	}
}
