<?php
/**
 * Kontentainment Charts — Bento Insights Module (External)
 */
global $wpdb;

$entries_table = $wpdb->prefix . 'charts_entries';
$intel_table = $wpdb->prefix . 'charts_intelligence';
$imports_table = $wpdb->prefix . 'charts_import_runs';

$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $entries_table");
$has_data = ($total_entries > 0);

$new_entries_count = 0;
$market_volume     = 0;
$top_gainers       = [];
$top_losers        = [];
$artist_share      = [];
$avg_longevity     = 0;
$last_update       = null;

if ($has_data) {
    $new_entries_count = $wpdb->get_var("SELECT COUNT(*) FROM $entries_table WHERE is_new_entry = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $market_volume = $wpdb->get_var("SELECT SUM(streams_count + views_count) FROM $entries_table");
    
    $top_gainers = $wpdb->get_results("
        SELECT track_name, artist_names, cover_image, movement_value, rank_position
        FROM $entries_table
        WHERE movement_direction = 'up'
        ORDER BY movement_value DESC
        LIMIT 5
    ");

    $top_losers = $wpdb->get_results("
        SELECT track_name, artist_names, cover_image, movement_value, rank_position
        FROM $entries_table
        WHERE movement_direction = 'down'
        ORDER BY movement_value DESC
        LIMIT 5
    ");

    $artist_share = $wpdb->get_results("
        SELECT artist_names, COUNT(*) as appearance_count, MIN(rank_position) as best_peak
        FROM $entries_table
        WHERE rank_position <= 100
        GROUP BY artist_names
        ORDER BY appearance_count DESC
        LIMIT 5
    ");

    $avg_longevity = $wpdb->get_var("SELECT AVG(weeks_on_chart) FROM $entries_table");
    $last_update = $wpdb->get_var("SELECT finished_at FROM $imports_table WHERE status = 'completed' ORDER BY finished_at DESC LIMIT 1");
}
?>

<div class="bento-grid">
    <!-- 1. MARKET FRESHNESS -->
    <div class="bento-card">
        <label class="kpi-title">Market Freshness</label>
        <span class="kpi-val"><?php echo number_format($new_entries_count); ?></span>
        <span class="kpi-trend" style="color:var(--db-accent);">New Entries (30d)</span>
    </div>

    <!-- 2. AVERAGE LONGEVITY -->
    <div class="bento-card">
        <label class="kpi-title">Catalog Longevity</label>
        <span class="kpi-val"><?php echo number_format($avg_longevity, 1); ?></span>
        <span class="kpi-trend" style="color:var(--db-secondary);">Avg. Weeks on Chart</span>
    </div>

    <!-- 3. MARKET VOLUME -->
    <div class="bento-card">
        <label class="kpi-title">Consumption Volume</label>
        <span class="kpi-val"><?php echo ($market_volume > 1000000) ? number_format($market_volume / 1000000, 1) . 'M' : number_format($market_volume); ?></span>
        <span class="kpi-trend" style="color:var(--db-text-muted);">Signals Recorded</span>
    </div>

    <!-- 4. LAST UPDATE -->
    <div class="bento-card">
        <label class="kpi-title">Last Intelligence Sync</label>
        <span class="kpi-val" style="font-size:20px;"><?php echo $last_update ? date('M j, H:i', strtotime($last_update)) : '—'; ?></span>
        <span class="kpi-trend" style="color:var(--db-accent);">System State: Active</span>
    </div>

    <!-- 5. TOP GAINERS (SPAN 2) -->
    <div class="bento-card span-2">
        <label class="kpi-title">Momentum: Rising Fast</label>
        <div style="margin-top:24px;">
            <?php foreach ($top_gainers as $t) : ?>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:36px; height:36px; border-radius:6px; object-fit:cover;">
                    <div style="flex-grow:1;">
                        <span style="display:block; font-size:13px; font-weight:800;"><?php echo esc_html($t->track_name); ?></span>
                        <span style="display:block; font-size:11px; color:var(--db-text-dim);"><?php echo esc_html($t->artist_names); ?></span>
                    </div>
                    <div style="text-align:right;">
                        <span style="display:block; font-size:14px; font-weight:900; color:var(--db-accent);">+<?php echo (int)$t->movement_value; ?></span>
                        <span style="display:block; font-size:11px; font-weight:800; opacity:0.6;">PEAK #<?php echo (int)$t->rank_position; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 6. TOP LOSERS (SPAN 2) -->
    <div class="bento-card span-2">
        <label class="kpi-title">Trends: Cooling Down</label>
        <div style="margin-top:24px;">
            <?php foreach ($top_losers as $t) : ?>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:36px; height:36px; border-radius:6px; object-fit:cover;">
                    <div style="flex-grow:1;">
                        <span style="display:block; font-size:13px; font-weight:800;"><?php echo esc_html($t->track_name); ?></span>
                        <span style="display:block; font-size:11px; color:var(--db-text-dim);"><?php echo esc_html($t->artist_names); ?></span>
                    </div>
                    <div style="text-align:right;">
                        <span style="display:block; font-size:14px; font-weight:900; color:var(--db-secondary);">-<?php echo (int)$t->movement_value; ?></span>
                        <span style="display:block; font-size:11px; font-weight:800; opacity:0.6;">RANK #<?php echo (int)$t->rank_position; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 7. ARTIST DOMINANCE (SPAN 4) -->
    <div class="bento-card span-4">
        <label class="kpi-title">Artist Dominance: Cumulative Market Share</label>
        <div class="db-table-wrap" style="margin-top:24px;">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Artist</th>
                        <th>Appearances</th>
                        <th>Best Peak</th>
                        <th style="text-align:right;">Market Authority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artist_share as $a) : ?>
                        <tr>
                            <td style="font-weight:800;"><?php echo esc_html($a->artist_names); ?></td>
                            <td style="font-weight:800;"><?php echo (int)$a->appearance_count; ?></td>
                            <td style="font-weight:900; color:var(--db-accent);">#<?php echo (int)$a->best_peak; ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:12px;">
                                    <div style="width:80px; height:6px; background:var(--db-bg); border-radius:3px; position:relative; overflow:hidden; border:1px solid rgba(0,0,0,0.03);">
                                        <div style="width: <?php echo min(100, $a->appearance_count * 5); ?>%; height:100%; background:var(--db-accent);"></div>
                                    </div>
                                    <span style="font-size:11px; font-weight:800; color:var(--db-text-dim);"><?php echo min(100, $a->appearance_count * 5); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
