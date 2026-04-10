<?php
require_once( 'wp-load.php' );
global $wpdb;

echo "--- CHART DEFINITIONS ---\n";
$defs = $wpdb->get_results("SELECT id, title, chart_type, item_type FROM {$wpdb->prefix}charts_definitions LIMIT 5");
foreach($defs as $def) {
    echo "ID: {$def->id} | Title: {$def->title} | Type: {$def->chart_type} | ItemType: {$def->item_type}\n";
}

echo "\n--- LATEST ENTRIES ---\n";
$entries = $wpdb->get_results("SELECT e.id, e.item_type, e.track_name, e.artist_names, s.chart_type 
                               FROM {$wpdb->prefix}charts_entries e
                               JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
                               ORDER BY e.id DESC LIMIT 10");
foreach($entries as $e) {
    echo "ID: {$e->id} | Type: {$e->item_type} | TrackName: {$e->track_name} | Artists: {$e->artist_names} | ChartType: {$e->chart_type}\n";
}
