<?php
/**
 * Market Insights View
 * Powered by Charts Intelligence Engine
 */
global $wpdb;

$entries_table = $wpdb->prefix . 'charts_entries';
$intel_table = $wpdb->prefix . 'charts_intelligence';
$imports_table = $wpdb->prefix . 'charts_import_runs';

// 1. Audit Check: Do we have enough data?
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $entries_table");
$has_data = ($total_entries > 0);

// 2. Fetch Aggregates if data exists
if ($has_data) {
    // A. Market Freshness (New Entries in last 30 days)
    $new_entries_count = $wpdb->get_var("SELECT COUNT(*) FROM $entries_table WHERE is_new_entry = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // B. Market Volume (Total Streams/Views)
    $market_volume = $wpdb->get_var("SELECT SUM(streams_count + views_count) FROM $entries_table");
    
    // C. Top Gainers (Biggest rank improvement)
    $top_gainers = $wpdb->get_results("
        SELECT track_name, artist_names, cover_image, movement_value, rank_position
        FROM $entries_table
        WHERE movement_direction = 'up'
        ORDER BY movement_value DESC
        LIMIT 5
    ");

    // D. Top Losers (Biggest rank drop)
    $top_losers = $wpdb->get_results("
        SELECT track_name, artist_names, cover_image, movement_value, rank_position
        FROM $entries_table
        WHERE movement_direction = 'down'
        ORDER BY movement_value DESC
        LIMIT 5
    ");

    // E. Artist Market Share (Most appearances in Top 100)
    $artist_share = $wpdb->get_results("
        SELECT artist_names, COUNT(*) as appearance_count, MIN(rank_position) as best_peak
        FROM $entries_table
        WHERE rank_position <= 100
        GROUP BY artist_names
        ORDER BY appearance_count DESC
        LIMIT 5
    ");

    // F. Chart Longevity (Average weeks on chart)
    $avg_longevity = $wpdb->get_var("SELECT AVG(weeks_on_chart) FROM $entries_table");

    // G. Last Updated
    $last_update = $wpdb->get_var("SELECT finished_at FROM $imports_table WHERE status = 'completed' ORDER BY finished_at DESC LIMIT 1");
}

?>

<div class="charts-admin-wrap">
    <header class="charts-header">
        <div>
            <h1><?php _e( 'Market Insights', 'charts' ); ?></h1>
            <p class="subtitle"><?php _e( 'Advanced market intelligence and historical trend analysis.', 'charts' ); ?></p>
        </div>
        <div class="charts-actions">
            <?php if ($has_data) : ?>
                <button class="charts-btn-secondary" onclick="recalculateInsights()">
                    <span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e( 'Refresh Analytics', 'charts' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$has_data) : ?>
        <div class="charts-card" style="padding: 100px; text-align: center; background: #fff; border-radius: 12px; margin-top: 20px;">
            <span class="dashicons dashicons-chart-line" style="font-size: 64px; width: 64px; height: 64px; color: #ccc;"></span>
            <h2 style="margin-top: 30px;"><?php _e( 'Waiting for Data Accumulation', 'charts' ); ?></h2>
            <p style="color: #666; max-width: 400px; margin: 10px auto;">
                <?php _e( 'Market insights require at least one successful chart import to generate analytics and trend signals.', 'charts' ); ?>
            </p>
            <div style="margin-top: 30px;">
                <a href="<?php echo admin_url('admin.php?page=charts-import'); ?>" class="charts-badge charts-badge-neutral" style="text-decoration: none;">
                    <?php _e( 'Start an Import Run &rarr;', 'charts' ); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        
        <!-- KPI Row -->
        <div class="charts-grid" style="margin-top: 24px;">
            <div class="charts-card stats-card" style="grid-column: span 3;">
                <div class="label"><?php _e( 'Market Freshness', 'charts' ); ?></div>
                <div class="value"><?php echo number_format($new_entries_count); ?></div>
                <div class="trend" style="color: #22c55e;"><?php _e( 'New entries (30d)', 'charts' ); ?></div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3;">
                <div class="label"><?php _e( 'Avg. Longevity', 'charts' ); ?></div>
                <div class="value"><?php echo number_format($avg_longevity, 1); ?></div>
                <div class="trend"><?php _e( 'Weeks on chart', 'charts' ); ?></div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3;">
                <div class="label"><?php _e( 'Market Volume', 'charts' ); ?></div>
                <div class="value"><?php echo ($market_volume > 1000000) ? number_format($market_volume / 1000000, 1) . 'M' : number_format($market_volume); ?></div>
                <div class="trend"><?php _e( 'Total interactions recorded', 'charts' ); ?></div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3;">
                <div class="label"><?php _e( 'Last Updated', 'charts' ); ?></div>
                <div class="value" style="font-size: 1.2rem; margin-top: 15px;">
                    <?php echo $last_update ? date('M d, H:i', strtotime($last_update)) : '—'; ?>
                </div>
                <div class="trend"><?php _e( 'Intelligence sync', 'charts' ); ?></div>
            </div>
        </div>

        <div class="charts-grid" style="margin-top: 24px;">
            <!-- Top Movers -->
            <div class="charts-card" style="grid-column: span 6; padding: 0;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 16px;"><?php _e( 'Momentum: Top Gainers', 'charts' ); ?></h3>
                    <span class="charts-badge charts-badge-success"><?php _e( 'Rising Fast', 'charts' ); ?></span>
                </div>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th><?php _e( 'Jump', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px;"><?php _e( 'Current', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_gainers as $t) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($t->cover_image) : ?>
                                            <img src="<?php echo esc_url($t->cover_image); ?>" style="width: 32px; height: 32px; border-radius: 4px;">
                                        <?php endif; ?>
                                        <div style="font-weight: 600; line-height: 1.2;">
                                            <?php echo esc_html($t->track_name); ?>
                                            <div style="font-size: 11px; opacity: 0.6; font-weight: 400;"><?php echo esc_html($t->artist_names); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="color: #22c55e; font-weight: 700;">+<?php echo (int)$t->movement_value; ?></span></td>
                                <td style="text-align: right; padding-right: 24px; font-weight: 700;">#<?php echo (int)$t->rank_position; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Losers -->
            <div class="charts-card" style="grid-column: span 6; padding: 0;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 16px;"><?php _e( 'Trend: Falling Tracks', 'charts' ); ?></h3>
                    <span class="charts-badge charts-badge-danger"><?php _e( 'Cooling Down', 'charts' ); ?></span>
                </div>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th><?php _e( 'Drop', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px;"><?php _e( 'Current', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_losers as $t) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($t->cover_image) : ?>
                                            <img src="<?php echo esc_url($t->cover_image); ?>" style="width: 32px; height: 32px; border-radius: 4px;">
                                        <?php endif; ?>
                                        <div style="font-weight: 600; line-height: 1.2;">
                                            <?php echo esc_html($t->track_name); ?>
                                            <div style="font-size: 11px; opacity: 0.6; font-weight: 400;"><?php echo esc_html($t->artist_names); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="color: #ef4444; font-weight: 700;">-<?php echo (int)$t->movement_value; ?></span></td>
                                <td style="text-align: right; padding-right: 24px; font-weight: 700;">#<?php echo (int)$t->rank_position; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Artist Dominance -->
            <div class="charts-card" style="grid-column: span 12; padding: 0;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #eee;">
                    <h3 style="margin: 0; font-size: 16px;"><?php _e( 'Artist Dominance: Market Share', 'charts' ); ?></h3>
                </div>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px;"><?php _e( 'Artist', 'charts' ); ?></th>
                            <th><?php _e( 'Top 100 Appearances', 'charts' ); ?></th>
                            <th><?php _e( 'Best Peak', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px;"><?php _e( 'Performance', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artist_share as $a) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="font-weight: 700;"><?php echo esc_html($a->artist_names); ?></div>
                                </td>
                                <td style="font-weight: 600;"><?php echo (int)$a->appearance_count; ?></td>
                                <td>#<?php echo (int)$a->best_peak; ?></td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <div style="width: 100px; height: 6px; background: #eee; border-radius: 3px; display: inline-block; overflow: hidden; vertical-align: middle;">
                                        <div style="width: <?php echo min(100, $a->appearance_count * 5); ?>%; height: 100%; background: #6366f1;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function recalculateInsights() {
    const btn = event.currentTarget;
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<?php _e( "Calculating...", "charts" ); ?>';
    
    jQuery.post(ajaxurl, {
        action: 'charts_recalculate_intel',
        nonce: '<?php echo wp_create_nonce("charts_intel"); ?>'
    }, function(res) {
        location.reload();
    }).fail(function() {
        alert('Recalculation failed. Please check network logs.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
