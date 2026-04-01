<?php
/**
 * Kontentainment Charts — Market Insights Dashboard
 * Focus: Historical Trends, Market Share, and Longitudinal Performance.
 */
global $wpdb;

$entries_table = $wpdb->prefix . 'charts_entries';
$intel_table = $wpdb->prefix . 'charts_intelligence';
$imports_table = $wpdb->prefix . 'charts_import_runs';

// 1. Audit Check: Do we have enough data?
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $entries_table");
$has_data = ($total_entries > 0);

// Data retrieval
$new_entries_count = 0;
$market_volume     = 0;
$top_gainers       = [];
$top_losers        = [];
$artist_share      = [];
$avg_longevity     = 0;
$last_update       = null;

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

<div class="charts-admin-wrap premium-light">
    <header class="charts-admin-header">
        <div>
            <h1 class="charts-admin-title"><?php _e( 'Market Insights', 'charts' ); ?></h1>
            <p class="charts-admin-subtitle"><?php _e( 'Historical trend analysis and longitudinal market performance.', 'charts' ); ?></p>
        </div>
        <div class="charts-admin-actions">
            <?php if ($has_data) : ?>
                <button class="charts-btn-secondary" onclick="recalculateAnalytics()" id="analytics-refresh-btn">
                    <span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e( 'Sync Analytics', 'charts' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$has_data) : ?>
        <div class="charts-card" style="padding: 100px; text-align: center; background: #fff; border-radius: 12px; margin-top: 20px; border: 1px solid var(--charts-border);">
            <span class="dashicons dashicons-chart-pie" style="font-size: 64px; width: 64px; height: 64px; color: #ccc;"></span>
            <h2 style="margin-top: 30px;"><?php _e( 'Historical Data Pending', 'charts' ); ?></h2>
            <p style="color: #666; max-width: 400px; margin: 10px auto;">
                <?php _e( 'Insights are generated from your accumulated chart history. Complete at least one import run to unlock historical trend analysis.', 'charts' ); ?>
            </p>
            <div style="margin-top: 30px;">
                <a href="<?php echo admin_url('admin.php?page=charts-import'); ?>" class="charts-btn-primary" style="text-decoration: none;">
                    <?php _e( 'Go to Import Center &rarr;', 'charts' ); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        
        <!-- Premium Analytics KPIs -->
        <div class="charts-grid" style="margin-top: 32px; gap: 24px;">
            <div class="charts-card stats-card" style="grid-column: span 3; background: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border: 1px solid var(--charts-border); padding: 32px; transition: transform 0.2s ease; cursor: default;">
                <div class="label" style="text-transform: uppercase; font-size: 11px; font-weight: 800; letter-spacing: 0.1em; color: var(--charts-text-dim); margin-bottom: 12px;"><?php _e( 'Market Freshness', 'charts' ); ?></div>
                <div class="value" style="font-size: 36px; font-weight: 900; color: var(--charts-primary); margin: 0;"><?php echo number_format($new_entries_count); ?></div>
                <div class="trend" style="color: #22c55e; font-size: 13px; font-weight: 700; margin-top: 12px; display: flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-arrow-up-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    +<?php _e( 'New entries (30d)', 'charts' ); ?>
                </div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3; background: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border: 1px solid var(--charts-border); padding: 32px; transition: transform 0.2s ease; cursor: default;">
                <div class="label" style="text-transform: uppercase; font-size: 11px; font-weight: 800; letter-spacing: 0.1em; color: var(--charts-text-dim); margin-bottom: 12px;"><?php _e( 'Avg. Longevity', 'charts' ); ?></div>
                <div class="value" style="font-size: 36px; font-weight: 900; color: var(--charts-primary); margin: 0;"><?php echo number_format($avg_longevity, 1); ?></div>
                <div class="trend" style="color: #6366f1; font-size: 13px; font-weight: 700; margin-top: 12px;"><?php _e( 'Avg. Weeks on chart', 'charts' ); ?></div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3; background: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border: 1px solid var(--charts-border); padding: 32px; transition: transform 0.2s ease; cursor: default;">
                <div class="label" style="text-transform: uppercase; font-size: 11px; font-weight: 800; letter-spacing: 0.1em; color: var(--charts-text-dim); margin-bottom: 12px;"><?php _e( 'Market Volume', 'charts' ); ?></div>
                <div class="value" style="font-size: 36px; font-weight: 900; color: var(--charts-primary); margin: 0;"><?php echo ($market_volume > 1000000) ? number_format($market_volume / 1000000, 1) . 'M' : number_format($market_volume); ?></div>
                <div class="trend" style="color: grey; font-size: 13px; font-weight: 700; margin-top: 12px;"><?php _e( 'Total interactions recorded', 'charts' ); ?></div>
            </div>
            <div class="charts-card stats-card" style="grid-column: span 3; background: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border: 1px solid var(--charts-border); padding: 32px; transition: transform 0.2s ease; cursor: default;">
                <div class="label" style="text-transform: uppercase; font-size: 11px; font-weight: 800; letter-spacing: 0.1em; color: var(--charts-text-dim); margin-bottom: 12px;"><?php _e( 'Last Updated', 'charts' ); ?></div>
                <div class="value" style="font-size: 24px; font-weight: 900; color: var(--charts-primary); margin: 0; height: 36px; display: flex; align-items: center;">
                    <?php echo $last_update ? date('M d, H:i', strtotime($last_update)) : '—'; ?>
                </div>
                <div class="trend" style="color: var(--charts-accent-purple); font-size: 13px; font-weight: 700; margin-top: 12px;"><?php _e( 'Data Sync Status', 'charts' ); ?></div>
            </div>
        </div>

        <div class="charts-grid" style="margin-top: 40px; gap: 32px;">
            
            <!-- Performance: Top Gainers -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0; background: #fff; border-radius: 16px; border: 1px solid var(--charts-border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <header class="table-header" style="padding: 28px 32px; border-bottom: 1px solid var(--charts-border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                    <div>
                        <h3 style="margin: 0; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Momentum: Market Gainers', 'charts' ); ?></h3>
                        <p style="margin: 4px 0 0; font-size: 11px; color: var(--charts-text-dim); font-weight: 600;">Biggest rank improvements this period.</p>
                    </div>
                    <span class="charts-badge charts-badge-success" style="font-size: 10px; font-weight: 900; padding: 6px 12px; border-radius: 8px;"><?php _e( 'Rising Fast', 'charts' ); ?></span>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Jump', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Rank', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_gainers as $t) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 36px; height: 36px; border-radius: 6px; border: 1px solid var(--charts-border);">
                                        <div style="font-weight: 700; line-height: 1.2;">
                                            <?php echo esc_html($t->track_name); ?>
                                            <div style="font-size: 11px; opacity: 0.5; font-weight: 600;"><?php echo esc_html($t->artist_names); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="color: #22c55e; font-weight: 800; font-size: 14px;">+<?php echo (int)$t->movement_value; ?></span></td>
                                <td style="text-align: right; padding-right: 24px; font-weight: 900; font-size: 14px; color: var(--charts-primary);">#<?php echo (int)$t->rank_position; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Performance: Falling Tracks -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0; background: #fff; border-radius: 12px; border: 1px solid var(--charts-border); overflow: hidden;">
                <header class="table-header" style="padding: 24px; border-bottom: 1px solid var(--charts-border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Trends: Cooling Down', 'charts' ); ?></h3>
                    <span class="charts-badge charts-badge-danger" style="font-size: 9px; font-weight: 900;"><?php _e( 'Losing Velocity', 'charts' ); ?></span>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Drop', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Rank', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_losers as $t) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 36px; height: 36px; border-radius: 6px; border: 1px solid var(--charts-border);">
                                        <div style="font-weight: 700; line-height: 1.2;">
                                            <?php echo esc_html($t->track_name); ?>
                                            <div style="font-size: 11px; opacity: 0.5; font-weight: 600;"><?php echo esc_html($t->artist_names); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="color: #ef4444; font-weight: 800; font-size: 14px;">-<?php echo (int)$t->movement_value; ?></span></td>
                                <td style="text-align: right; padding-right: 24px; font-weight: 900; font-size: 14px; color: var(--charts-primary);">#<?php echo (int)$t->rank_position; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Artist Dominance: LONGITUDINAL ANALYSIS -->
            <div class="charts-table-card" style="grid-column: span 12; padding: 0; background: #fff; border-radius: 12px; border: 1px solid var(--charts-border); overflow: hidden; margin-top: 32px;">
                <header class="table-header" style="padding: 24px; border-bottom: 1px solid var(--charts-border); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Artist Dominance: Cumulative Market Share', 'charts' ); ?></h3>
                        <div style="font-size: 11px; color: var(--charts-text-dim); font-weight: 600; margin-top: 4px;"><?php _e( 'Analyzed across all historical Top 100 entries.', 'charts' ); ?></div>
                    </div>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Artist', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Catalog Appearances', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Best Recorded Peak', 'charts' ); ?></th>
                            <th style="text-align: right; padding-right: 24px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Market Authority', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artist_share as $a) : ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <div style="font-weight: 800; font-size: 14px; color: var(--charts-primary);"><?php echo esc_html($a->artist_names); ?></div>
                                </td>
                                <td style="font-weight: 800; color: var(--charts-primary); font-size: 14px;"><?php echo (int)$a->appearance_count; ?></td>
                                <td style="font-weight: 800; color: var(--charts-accent-purple); font-size: 14px;">#<?php echo (int)$a->best_peak; ?></td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                                        <div style="width: 140px; height: 8px; background: #f1f5f9; border-radius: 4px; position: relative; overflow: hidden; border: 1px solid rgba(0,0,0,0.03);">
                                            <div style="width: <?php echo min(100, $a->appearance_count * 5); ?>%; height: 100%; background: linear-gradient(90deg, var(--charts-primary), #6366f1); border-radius: 4px;"></div>
                                        </div>
                                        <span style="font-size: 11px; font-weight: 800; color: var(--charts-text-dim); min-width: 30px;"><?php echo min(100, $a->appearance_count * 5); ?>%</span>
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
function recalculateAnalytics() {
    const btn = document.getElementById('analytics-refresh-btn');
    if (!btn) return;
    
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle; animation: charts-spin 1s linear infinite;"></span> Calculating...';
    
    jQuery.post(ajaxurl, {
        action: 'charts_recalculate_intel',
        nonce: '<?php echo wp_create_nonce("charts_admin_action"); ?>'
    }, function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert('Error: ' + (res.data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }).fail(function() {
        alert('Connection error during sync.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
