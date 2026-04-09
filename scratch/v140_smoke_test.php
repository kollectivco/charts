<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

use Charts\Core\EntityManager;
use Charts\Core\Router;

echo "--- V1.4.0 FINAL SMOKE PASS ---\n";

try {
    // 1. Homepage
    $index = Router::load_template('', 'index');
    echo "• Homepage: " . basename($index) . " ... OK\n";

    // 2. Chart
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'charts_definitions', array('title' => 'Smoke 1.4', 'slug' => 'smoke-1-4', 'is_public' => 1));
    $chart_id = $wpdb->insert_id;
    $chart_tpl = Router::load_template('', 'single-chart');
    echo "• Chart Page: " . basename($chart_tpl) . " ... OK\n";

    // 3-5. Entities
    $artist_id = EntityManager::ensure_artist("Smoke Artist 1.4");
    echo "• Artist Native Create ... OK\n";
    
    $track_id = EntityManager::ensure_track("Smoke Track 1.4", $artist_id);
    echo "• Track Native Create ... OK\n";

    // 6. Promote
    $wpdb->insert($wpdb->prefix . 'charts_videos', array('title' => 'Smoke Clip 1.4', 'slug' => 'smoke-clip-1-4'));
    $clip_sql_id = $wpdb->insert_id;
    $clip_post_id = EntityManager::promote_to_native('video', $clip_sql_id);
    echo "• Promotion Logic ... OK\n";

    // 7. Import
    $import = new \Charts\Services\ImportFlow();
    $period_id = $import->ensure_period('weekly', date('Y-m-d'));
    $import->upsert_entry(1, $period_id, 'track', $track_id, array('rank' => 1, 'track_name' => 'Hit 1.4', 'artist_names' => 'Artist 1.4'));
    echo "• Import Pipeline ... OK\n";

    // 8. Admin Stability
    ob_start();
    \Charts\Admin\Bootstrap::render_dashboard();
    ob_end_clean();
    echo "• Admin UI Stability ... OK\n";

    echo "\n--- SMOKE PASS SUCCESS ---\n";

} catch (\Exception $e) {
    echo "SMOKE FAIL: " . $e->getMessage();
    exit(1);
}
