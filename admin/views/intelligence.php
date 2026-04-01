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
    // We join with a subquery that picks the latest entry id for each item_id/item_type 
    // to ensure we don't have ambiguous GROUP BY data.
    $trending_tracks = $wpdb->get_results("
        SELECT i.*, e.track_name, e.artist_names, e.cover_image, e.rank_position
        FROM $intel_table i
        JOIN $entries_table e ON e.id = (
            SELECT MAX(id) FROM $entries_table 
            WHERE item_id = i.entity_id AND item_type = i.entity_type
        )
        WHERE i.entity_type = 'track'
        ORDER BY i.momentum_score DESC LIMIT 5
    ");

    // 2. Fastest Risers (Velocity)
    $fastest_risers = $wpdb->get_results("
        SELECT i.*, e.track_name, e.artist_names, e.cover_image
        FROM $intel_table i
        JOIN $entries_table e ON e.id = (
            SELECT MAX(id) FROM $entries_table 
            WHERE item_id = i.entity_id AND item_type = i.entity_type
        )
        WHERE i.entity_type = 'track'
        ORDER BY i.growth_rate DESC LIMIT 5
    ");

    // 3. Hot Artists (Market Authority)
    $hot_artists = $wpdb->get_results("
        SELECT i.*, a.display_name, a.image, (
            SELECT COUNT(DISTINCT track_name) FROM $entries_table WHERE artist_names LIKE CONCAT('%', a.display_name, '%')
        ) as unique_entries
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
        <div class="charts-card" style="padding: 100px; text-align: center; background: #fff; border-radius: 12px; margin-top: 24px; border: 1px solid var(--charts-border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <span class="dashicons dashicons-visibility" style="font-size: 64px; width: 64px; height: 64px; color: #ccc;"></span>
            <h2 style="margin-top: 30px; font-weight: 800;"><?php _e( 'No Signals Detected', 'charts' ); ?></h2>
            <p style="color: #666; max-width: 400px; margin: 10px auto; font-weight: 500;">
                <?php _e( 'Run a recalculation to scan your data for momentum and trends.', 'charts' ); ?>
            </p>
            <div style="margin-top: 30px;">
                <button onclick="recalculateIntelligence()" class="charts-btn-primary">
                    <?php _e( 'Initialize Intelligence Engine', 'charts' ); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <div class="charts-grid" style="margin-top: 32px; gap: 32px;">
            
            <!-- Momentum Column -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0; background: #fff; border-radius: 16px; border: 1px solid var(--charts-border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <header class="table-header" style="padding: 28px 32px; border-bottom: 1px solid var(--charts-border); background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:14px; font-weight:900; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Momentum: Top Trending', 'charts' ); ?></h3>
                    <span class="charts-badge charts-badge-success" style="font-size: 10px; font-weight: 900; padding: 6px 12px; border-radius: 8px;"><?php _e( 'Live Signals', 'charts' ); ?></span>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Score', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Trend', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trending_tracks as $t): ?>
                        <tr>
                            <td style="padding-left:32px; padding-top: 16px; padding-bottom: 16px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:40px; height:40px; border-radius:8px; border:1px solid var(--charts-border);">
                                    <div style="font-weight:700; line-height:1.2; font-size:13px; color: var(--charts-primary);">
                                        <?php echo esc_html($t->track_name); ?>
                                        <div style="opacity:0.5; font-weight:600; font-size:11px; margin-top: 2px;"><?php echo esc_html($t->artist_names); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:900; color:var(--charts-primary); font-size: 15px;"><?php echo number_format($t->momentum_score, 1); ?></td>
                            <td style="text-align:right; padding-right:32px;">
                                <span class="kc-status-pill <?php echo esc_attr($t->trend_status); ?>" style="font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;">
                                    <?php echo esc_html($t->trend_status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Velocity Column -->
            <div class="charts-table-card" style="grid-column: span 6; padding: 0; background: #fff; border-radius: 16px; border: 1px solid var(--charts-border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <header class="table-header" style="padding: 28px 32px; border-bottom: 1px solid var(--charts-border); background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:14px; font-weight:900; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Velocity: Fastest Risers', 'charts' ); ?></h3>
                    <span class="charts-badge charts-badge-purple" style="font-size: 10px; font-weight: 900; padding: 6px 12px; border-radius: 8px; background: #f3f4ff; color: #6366f1; border: 1px solid #e0e7ff;"><?php _e( 'Growth Acceleration', 'charts' ); ?></span>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Track', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Growth', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Signal', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fastest_risers as $t): ?>
                        <tr>
                            <td style="padding-left:32px; padding-top: 16px; padding-bottom: 16px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:40px; height:40px; border-radius:8px; border:1px solid var(--charts-border);">
                                    <div style="font-weight:700; line-height:1.2; font-size:13px; color: var(--charts-primary);">
                                        <?php echo esc_html($t->track_name); ?>
                                        <div style="opacity:0.5; font-weight:600; font-size:11px; margin-top: 2px;"><?php echo esc_html($t->artist_names); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:900; color:var(--charts-success); font-size: 15px;">+<?php echo number_format($t->growth_rate, 1); ?>%</td>
                            <td style="text-align:right; padding-right:32px;">
                                <div style="width: 60px; height: 6px; background: #f1f5f9; border-radius: 3px; display: inline-block; position: relative; border: 1px solid rgba(0,0,0,0.03);">
                                    <div style="width: <?php echo min(100, $t->growth_rate * 4); ?>%; height: 100%; background: linear-gradient(90deg, #22c55e, #10b981); border-radius: 3px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Artist Authority -->
            <div class="charts-table-card" style="grid-column: span 12; padding: 0; margin-top:32px; background: #fff; border-radius: 16px; border: 1px solid var(--charts-border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <header class="table-header" style="padding: 28px 32px; border-bottom: 1px solid var(--charts-border); background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin:0; font-size:14px; font-weight:900; text-transform: uppercase; letter-spacing: 0.05em; color: var(--charts-primary);"><?php _e( 'Authority: Hot Artists', 'charts' ); ?></h3>
                        <p style="margin: 4px 0 0; font-size: 11px; color: var(--charts-text-dim); font-weight: 600;">Overall market authority based on catalog performance.</p>
                    </div>
                </header>
                <table class="charts-table">
                    <thead>
                        <tr>
                            <th style="padding-left:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Artist', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Momentum Score', 'charts' ); ?></th>
                            <th style="font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Unique Entries', 'charts' ); ?></th>
                            <th style="text-align:right; padding-right:32px; font-size: 10px; font-weight: 900; text-transform: uppercase; color: var(--charts-text-dim); opacity: 0.5;"><?php _e( 'Market Status', 'charts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hot_artists as $a): ?>
                        <tr>
                            <td style="padding-left:32px; padding-top: 20px; padding-bottom: 20px;">
                                <div style="display:flex; align-items:center; gap:16px;">
                                    <div style="position: relative;">
                                        <img src="<?php echo esc_url($a->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:48px; height:48px; border-radius:50%; border:2px solid var(--charts-border); background: #f8fafc;">
                                        <?php if($a->momentum_score > 70): ?>
                                            <div style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #6366f1; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center;">
                                                <span class="dashicons dashicons-star-filled" style="font-size: 8px; width: 8px; height: 8px; color: white;"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-weight:900; font-size:15px; color:var(--charts-primary);">
                                        <?php echo esc_html($a->display_name); ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:900; color:var(--charts-accent-purple); font-size: 18px;"><?php echo number_format($a->momentum_score, 1); ?></td>
                            <td style="font-weight:700; font-size: 14px;"><?php echo (int)$a->unique_entries; ?></td>
                            <td style="text-align:right; padding-right:32px;">
                                <span class="charts-badge <?php echo $a->momentum_score > 60 ? 'charts-badge-success' : 'charts-badge-neutral'; ?>" style="font-size: 10px; font-weight: 900; padding: 6px 14px; border-radius: 99px;">
                                    <?php echo $a->momentum_score > 60 ? 'Dominant' : ($a->momentum_score > 30 ? 'High Momentum' : 'Rising'); ?>
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
                alert('Connection error during recalculation.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    </script>
</div>
