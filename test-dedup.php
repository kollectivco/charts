<?php
require_once 'wp-load.php';
global $wpdb;

$definition_slug = 'spotify-top-songs-egypt'; // Let's try to find a chart
$manager = new \Charts\Admin\SourceManager();
$definition = $manager->get_definition_by_slug( 'viral-50-egypt' );
if (!$definition) {
    $definition = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}charts_definitions LIMIT 1");
}

if ($definition) {
	$sources = $wpdb->get_results( $wpdb->prepare( "
		SELECT id FROM {$wpdb->prefix}charts_sources 
		WHERE chart_type = %s AND is_active = 1
	", "cid-{$definition->id}" ) );

	$source_ids = array_column( $sources, 'id' );
	$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

	$period = $wpdb->get_row( $wpdb->prepare( "
		SELECT p.* FROM {$wpdb->prefix}charts_periods p
		JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id
		WHERE e.source_id IN ($placeholders)
		ORDER BY p.period_start DESC LIMIT 1
	", ...$source_ids ) );

	$query_params = array_values( $source_ids );
	$query_params[] = $period->id;
	$max_depth = 500;
	$query_params[] = $max_depth;
	
	$entries = $wpdb->get_results( $wpdb->prepare( "
		SELECT e.* 
		FROM {$wpdb->prefix}charts_entries e
		INNER JOIN (
			SELECT MAX(id) as max_id, rank_position
			FROM {$wpdb->prefix}charts_entries
			WHERE source_id IN ($placeholders) AND period_id = %d
			GROUP BY rank_position
		) dedup ON dedup.max_id = e.id
		ORDER BY e.rank_position ASC
		LIMIT %d
	", ...$query_params ) );
	
	echo "Found " . count($entries) . " entries.\n";
	$ranks = array_column($entries, 'rank_position');
	$dupes = array_diff_key($ranks, array_unique($ranks));
	echo "Duplicates: " . print_r($dupes, true) . "\n";
    if (count($entries) > 0) {
        $last_entries = array_slice($entries, -3);
        foreach($last_entries as $e) {
            echo "Rank: {$e->rank_position}, ID: {$e->id}, Track: {$e->track_name}\n";
        }
    }
} else {
    echo "No chart found\n";
}
