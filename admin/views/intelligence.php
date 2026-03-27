<?php
/**
 * Kontentainment Charts — Intelligence Dashboard
 * Focus: Live Signals, Momentum, and Operational Recalculation.
 */
global $wpdb;

$intel_table = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';

// 1. Audit Check: Do we have enough data?
$total_intel = $wpdb->get_var("SELECT COUNT(*) FROM $intel_table");
$has_data = ($total_intel > 0);

// Data retrieval
$trending_tracks = [];
$fastest_risers  = [];
$hot_artists      = [];

if ($has_data) {
    // 1. Top Momentum Tracks (Hot This Week)
    $trending_tracks = $wpdb->get_results("
        SELECT i.*, e.track_name, e.artist_names, e.cover_image, e.rank_position
        FROM $intel_table i
        JOIN $entries_table e ON e.item_id = i.entity_id AND e.item_type = i.entity_type
        WHERE i.entity_type = 'track'
        GROUP BY i.entity_id
        ORDER BY i.momentum_score DESC LIMIT 5
    ");

    // 2. Fastest Risers (Velocity)
    $fastest_risers = $wpdb->get_results("
        SELECT i.*, e.track_name, e.artist_names, e.cover_image
        FROM $intel_table i
        JOIN $entries_table e ON e.item_id = i.entity_id AND e.item_type = i.entity_type
        WHERE i.entity_type = 'track'
        GROUP BY i.entity_id
        ORDER BY i.growth_rate DESC LIMIT 5
    ");

    // 3. Hot Artists (Market Authority)
    $hot_artists = $wpdb->get_results("
        SELECT i.*, a.display_name, a.image
        FROM $intel_table i
        JOIN {$wpdb->prefix}charts_artists a ON a.id = i.entity_id
        WHERE i.entity_type = 'artist'
        ORDER BY i.momentum_score DESC LIMIT 5
    ");
}
?>

<div class="charts-admin-wrap premium-light">
    <header class="charts-admin-header">
        <div>
            <h1 class="charts-admin-title"><?php _e( 'Charts Intelligence', 'charts' ); ?></h1>
            <p class="charts-admin-subtitle"><?php _e( 'Live momentum signals, item velocity, and operational analytics.', 'charts' ); ?></p>
        </div>
        <div class="charts-admin-actions">
            <button class="charts-btn-secondary" onclick="recalculateIntelligence()" id="intel-recalc-btn">
                <span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle;"></span>
                <?php _e( 'Full Recalculation', 'charts' ); ?>
            </button>
        </div>
    </header>

    <?php if (!$has_data) : ?>
        <div class="charts-card" style="padding: 100px; text-align: center; background: #fff; border-radius: 12px; margin-top: 20px; border: 1px solid var(--charts-border);">
            <span class="dashicons dashicons-visibility" style="font-size: 64px; width: 64px; height: 64px; color: #ccc;"></span>
            <h2 style="margin-top: 30px;"><?php _e( 'No Signals Detected', 'charts' ); ?></h2>
            <p style="color: #666; max-width: 400px; margin: 10px auto;">
                <?php _e( 'Run a recalculation to scan your data for momentum and trends.', 'charts' ); ?>
            </p>
            <div style="margin-top: 30px;">
                <button onclick="recalculateIntelligence()" class="charts-btn-primary">
                    <?php _e( 'Initialize Intelligence Engine', 'charts' ); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <div class="charts-grid" style="margin-top: 24px;">
            
            <!-- Momentum Column -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0;">
                <header class="table-header" style="padding: 20px 24px; border-bottom: 1px solid var(--charts-border);">
                    <h3 style="margin:0; font-size:15px; font-weight:800;"><?php _e( 'Momentum: Top Trending', 'charts' ); ?></h3>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:24px;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th><?php _e( 'Score', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:24px;"><?php _e( 'Trend', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trending_tracks as $t): ?>
                        <tr>
                            <td style="padding-left:24px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:32px; height:32px; border-radius:4px; border:1px solid var(--charts-border);">
                                    <div style="font-weight:700; line-height:1.2; font-size:12px;">
                                        <?php echo esc_html($t->track_name); ?>
                                        <div style="opacity:0.5; font-weight:500; font-size:10px;"><?php echo esc_html($t->artist_names); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:800; color:var(--charts-primary);"><?php echo number_format($t->momentum_score, 1); ?></td>
                            <td style="text-align:right; padding-right:24px;">
                                <span class="kc-status-pill <?php echo esc_attr($t->trend_status); ?>">
                                    <?php echo strtoupper($t->trend_status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Velocity Column -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0;">
                <header class="table-header" style="padding: 20px 24px; border-bottom: 1px solid var(--charts-border);">
                    <h3 style="margin:0; font-size:15px; font-weight:800;"><?php _e( 'Velocity: Fastest Risers', 'charts' ); ?></h3>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:24px;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th><?php _e( 'Growth', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:24px;"><?php _e( 'Signal', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fastest_risers as $t): ?>
                        <tr>
                            <td style="padding-left:24px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:32px; height:32px; border-radius:4px; border:1px solid var(--charts-border);">
                                    <div style="font-weight:700; line-height:1.2; font-size:12px;">
                                        <?php echo esc_html($t->track_name); ?>
                                        <div style="opacity:0.5; font-weight:500; font-size:10px;"><?php echo esc_html($t->artist_names); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:800; color:var(--charts-success);">+<?php echo number_format($t->growth_rate, 1); ?>%</td>
                            <td style="text-align:right; padding-right:24px;">
                                <div style="width: 40px; height: 4px; background: #eee; border-radius: 2px; display: inline-block; position: relative;">
                                    <div style="width: <?php echo min(100, $t->growth_rate * 5); ?>%; height: 100%; background: var(--charts-success); border-radius: 2px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Artist Authority -->
            <div class="charts-table-card" style="grid-column: span 12; padding: 0; margin-top:24px;">
                <header class="table-header" style="padding: 20px 24px; border-bottom: 1px solid var(--charts-border);">
                    <h3 style="margin:0; font-size:15px; font-weight:800;"><?php _e( 'Authority: Hot Artists', 'charts' ); ?></h3>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:24px;"><?php _e( 'Artist', 'charts' ); ?></th>
                            <th><?php _e( 'Momentum Score', 'charts' ); ?></th>
                            <th><?php _e( 'Unique Entries', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:24px;"><?php _e( 'Market Status', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hot_artists as $a): ?>
                        <tr>
                            <td style="padding-left:24px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?php echo esc_url($a->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:36px; height:36px; border-radius:50%; border:1px solid var(--charts-border);">
                                    <div style="font-weight:800; font-size:13px; color:var(--charts-primary);">
                                        <?php echo esc_html($a->display_name); ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:800; color:var(--charts-accent-purple);"><?php echo number_format($a->momentum_score, 1); ?></td>
                            <td style="font-weight:700;"><?php echo (int)$a->weeks_on_chart; ?></td>
                            <td style="text-align:right; padding-right:24px;">
                                <span class="charts-badge <?php echo $a->momentum_score > 50 ? 'charts-badge-success' : 'charts-badge-neutral'; ?>">
                                    <?php echo $a->momentum_score > 50 ? 'Dominant' : 'Rising'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <style>
        .kc-status-pill { font-size: 8px; font-weight: 900; padding: 2px 6px; border-radius: 4px; color: white; display: inline-block; min-width: 40px; text-align: center; }
        .kc-status-pill.rising { background: #22c55e; }
        .kc-status-pill.falling { background: #ef4444; }
        .kc-status-pill.new { background: #6366f1; }
        .kc-status-pill.stable { background: #334155; }
    </style>

    <script>
        function recalculateIntelligence() {
            const btn = document.getElementById('intel-recalc-btn');
            if (!btn) return;
            
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle; animation: charts-spin 1s linear infinite;"></span> Calculating...';
            
            jQuery.post(ajaxurl, {
                action: 'charts_recalculate_intel',
                nonce: '<?php echo wp_create_nonce("charts_intel"); ?>'
            }, function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }).fail(function() {
                alert('Connection error during recalculation.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    </script>
</div>
