<?php
/**
 * Phase 1 Validation Script
 */
require_once 'charts.php';

function verify_phase1() {
    global $wpdb;
    $manager = new \Charts\Admin\SourceManager();

    echo "=== PHASE 1 VALIDATION START ===\n\n";

    // 1. Setup a Test Legacy Chart
    $legacy_slug = 'test-legacy-chart-' . time();
    $wpdb->insert($wpdb->prefix . 'charts_definitions', [
        'title' => 'Test Legacy Chart',
        'slug' => $legacy_slug,
        'chart_type' => 'top-songs',
        'country_code' => 'eg',
        'frequency' => 'weekly',
        'is_public' => 1
    ]);
    $def_id = $wpdb->insert_id;
    echo "1. Created Legacy Chart: ID $def_id, Slug: $legacy_slug\n";

    // 2. Test Promotion Behavior
    echo "2. Testing Promotion Action...\n";
    $post_id1 = $manager->promote_to_native($def_id);
    if ($post_id1) {
        echo "   [SUCCESS] Promoted to CPT. Post ID: $post_id1\n";
    } else {
        echo "   [FAILURE] Promotion failed.\n";
    }

    // 3. Test Duplicate Prevention
    echo "3. Testing Duplicate Prevention...\n";
    $post_id2 = $manager->promote_to_native($def_id);
    if ($post_id1 === $post_id2) {
        echo "   [SUCCESS] Duplicate prevention works. ID remains $post_id2\n";
    } else {
        echo "   [FAILURE] Duplicate created! New ID: $post_id2\n";
    }

    // 4. Test SourceManager Dual Mode
    echo "4. Testing SourceManager Data Integrity...\n";
    $def = $manager->get_definition_by_slug($legacy_slug);
    if (isset($def->post_type) && $def->post_type === 'chart') {
        echo "   [SUCCESS] SourceManager resolved CPT for promoted chart.\n";
    } else {
        echo "   [FAILURE] SourceManager failed to resolve CPT.\n";
    }

    // 5. Test Legacy Route Stability
    echo "5. Testing Legacy Data Flow (Mocking Router query vars)...\n";
    set_query_var('charts_route', 'single-chart');
    set_query_var('charts_definition_slug', $legacy_slug);
    
    // We expect SourceManager to give us the definition
    $frontend_def = $manager->get_definition_by_slug(get_query_var('charts_definition_slug'));
    if ($frontend_def && $frontend_def->id == $post_id1) {
        echo "   [SUCCESS] Frontend lookup resolves to native record after promotion.\n";
    } else {
         echo "   [FAILURE] Frontend lookup failed.\n";
    }

    // 6. Test SEO Consistency
    echo "6. Testing SEO Output Consistency...\n";
    $seo_title = \Charts\Core\SEO::generate_title('');
    if (strpos($seo_title, 'Test Legacy Chart') !== false) {
        echo "   [SUCCESS] SEO title rendered correctly: $seo_title\n";
    } else {
        echo "   [FAILURE] SEO title mismatch: $seo_title\n";
    }

    // 7. Test CPT Mapping metadata
    $meta_def_id = get_post_meta($post_id1, '_kcharts_definition_id', true);
    if ($meta_def_id == $def_id) {
        echo "7. Metadata verification: CPT $post_id1 links back to SQL $meta_def_id [VERIFIED]\n";
    } else {
        echo "7. Metadata verification: FAILED (Post $post_id1 links to $meta_def_id, expected $def_id)\n";
    }

    echo "\n=== PHASE 1 VALIDATION END ===\n";
}

verify_phase1();
