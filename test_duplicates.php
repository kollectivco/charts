<?php
require_once('../../../wp-load.php');
global $wpdb;
$res = $wpdb->get_results("SELECT e.id, e.source_id, e.item_id, e.rank_position, e.created_at FROM {$wpdb->prefix}charts_entries e JOIN {$wpdb->prefix}charts_artists a ON e.item_id = a.id WHERE a.display_name LIKE '%جوج وسوف%' OR a.display_name LIKE '%جورج وسوف%' ORDER BY e.created_at DESC LIMIT 10");
print_r($res);
