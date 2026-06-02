<?php
require_once( __DIR__ . '/../wp-load.php' );
global $wpdb;

echo "Running migrations/schema install...\n";
$schema = new \Charts\Database\Schema();
$schema->install();

echo "Running calculations...\n";
try {
	\Charts\Core\Intelligence::recalculate_all();
	echo "Recalculation successful!\n";

	echo "--- Tracks Prediction Info ---\n";
	$intel = $wpdb->get_results("SELECT entity_id, momentum_score, viral_score, stability_score, longevity_score, predicted_peak, predicted_next_week, predicted_next_month, confidence_score FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'track' LIMIT 5");
	foreach ($intel as $row) {
		$track = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}charts_tracks WHERE id = %d", $row->entity_id));
		echo "Track: " . ($track ? $track->title : 'Unknown') . " | Momentum: {$row->momentum_score} | Viral: {$row->viral_score} | Peak: #{$row->predicted_peak} | Conf: {$row->confidence_score}%\n";
	}

	echo "--- Artists Prediction Info ---\n";
	$intel_art = $wpdb->get_results("SELECT entity_id, artist_power_score, predicted_next_week, predicted_next_month, predicted_peak FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'artist' LIMIT 5");
	foreach ($intel_art as $row) {
		$artist = $wpdb->get_row($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $row->entity_id));
		echo "Artist: " . ($artist ? $artist->display_name : 'Unknown') . " | Power: {$row->artist_power_score} | Pred Rank: #{$row->predicted_peak} | New Entries: {$row->predicted_next_week}\n";
	}
} catch (\Exception $e) {
	echo "Recalculate Error: " . $e->getMessage() . "\n";
}
