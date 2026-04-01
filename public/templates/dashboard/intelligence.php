<?php
/**
 * Kontentainment Charts — Bento Intelligence Module (External)
 */
global $wpdb;

$intel_table = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';

$total_intel = $wpdb->get_var("SELECT COUNT(*) FROM $intel_table");
$has_data = ($total_intel > 0);

$trending_tracks = [];
$fastest_risers  = [];
$hot_artists      = [];

if ($has_data) {
    try {
        $trending_tracks = $wpdb->get_results($wpdb->prepare("
            SELECT i.*, e.track_name, e.artist_names, e.cover_image, e.rank_position
            FROM $intel_table i
            JOIN $entries_table e ON e.id = (
                SELECT MAX(id) FROM $entries_table 
                WHERE item_id = i.entity_id AND item_type = i.entity_type
            )
            WHERE i.entity_type = %s
            ORDER BY i.momentum_score DESC LIMIT 5
        ", 'track'));

        $fastest_risers = $wpdb->get_results($wpdb->prepare("
            SELECT i.*, e.track_name, e.artist_names, e.cover_image
            FROM $intel_table i
            JOIN $entries_table e ON e.id = (
                SELECT MAX(id) FROM $entries_table 
                WHERE item_id = i.entity_id AND item_type = i.entity_type
            )
            WHERE i.entity_type = %s
            ORDER BY i.growth_rate DESC LIMIT 5
        ", 'track'));

        $hot_artists = $wpdb->get_results($wpdb->prepare("
            SELECT i.*, a.display_name, a.image, (
                SELECT COUNT(DISTINCT track_name) FROM $entries_table WHERE artist_names LIKE CONCAT('%%', a.display_name, '%%')
            ) as unique_entries
            FROM $intel_table i
            JOIN {$wpdb->prefix}charts_artists a ON a.id = i.entity_id
            WHERE i.entity_type = %s
            ORDER BY i.momentum_score DESC LIMIT 5
        ", 'artist'));
    } catch (\Exception $e) {
        $has_data = false;
        error_log('Charts Intelligence Error: ' . $e->getMessage());
    }
}
?>

<div class="bento-grid">
    <!-- 1. HEADER / ACTIONS -->
    <div class="bento-card span-4" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h3 style="margin:0; font-size:20px; font-weight:900;">Intelligence Engine</h3>
            <p style="margin:4px 0 0; color:var(--db-text-muted); font-size:13px;">Live momentum signals and catalog velocity.</p>
        </div>
        <button class="db-btn db-btn-primary" onclick="recalculateIntelligence()" id="intel-recalc-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Full Recalculation
        </button>
    </div>

    <?php if (!$has_data) : ?>
        <div class="bento-card span-4" style="text-align:center; padding:100px;">
            <h3 style="font-size:24px; font-weight:900; margin-bottom:12px;">No Signals Detected</h3>
            <p style="color:var(--db-text-muted); margin-bottom:32px;">Initialize the intelligence engine to scan your data for trends.</p>
            <button onclick="recalculateIntelligence()" class="db-btn db-btn-primary">Initialize Engine</button>
        </div>
    <?php else : ?>
        <!-- 2. TRENDING TRACKS (SPAN 2) -->
        <div class="bento-card span-2">
            <label class="kpi-title">Momentum: Top Trending</label>
            <div style="margin-top:24px;">
                <?php foreach ($trending_tracks as $t) : ?>
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                        <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                        <div style="flex-grow:1;">
                            <span style="display:block; font-size:13px; font-weight:800;"><?php echo esc_html($t->track_name); ?></span>
                            <span style="display:block; font-size:11px; color:var(--db-text-dim);"><?php echo esc_html($t->artist_names); ?></span>
                        </div>
                        <div style="text-align:right;">
                            <span style="display:block; font-size:14px; font-weight:900; color:var(--db-accent);"><?php echo number_format($t->momentum_score, 1); ?></span>
                            <span class="status-pill status-active" style="font-size:9px; padding:2px 6px;"><?php echo strtoupper($t->trend_status); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. FASTEST RISERS (SPAN 2) -->
        <div class="bento-card span-2">
            <label class="kpi-title">Velocity: Growth Signals</label>
            <div style="margin-top:24px;">
                <?php foreach ($fastest_risers as $t) : ?>
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                        <img src="<?php echo esc_url($t->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                        <div style="flex-grow:1;">
                            <span style="display:block; font-size:13px; font-weight:800;"><?php echo esc_html($t->track_name); ?></span>
                            <span style="display:block; font-size:11px; color:var(--db-text-dim);"><?php echo esc_html($t->artist_names); ?></span>
                        </div>
                        <div style="text-align:right;">
                            <span style="display:block; font-size:14px; font-weight:900; color:var(--db-secondary);">+<?php echo number_format($t->growth_rate, 1); ?>%</span>
                            <div style="width: 40px; height: 4px; background: var(--db-bg); border-radius: 2px; margin-top:4px;">
                                <div style="width: <?php echo min(100, $t->growth_rate * 5); ?>%; height: 100%; background: var(--db-secondary); border-radius: 2px;"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 4. HOT ARTISTS (SPAN 4) -->
        <div class="bento-card span-4">
            <label class="kpi-title">Market Authority: Hot Artists</label>
            <div style="margin-top:24px; display:grid; grid-template-columns: repeat(5, 1fr); gap:20px;">
                <?php foreach ($hot_artists as $a) : ?>
                    <div style="text-align:center;">
                        <img src="<?php echo esc_url($a->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:64px; height:64px; border-radius:50%; border:3px solid var(--db-border); margin-bottom:12px; object-fit:cover;">
                        <span style="display:block; font-size:12px; font-weight:800;"><?php echo esc_html($a->display_name); ?></span>
                        <span style="font-size:14px; font-weight:900; color:var(--db-accent);"><?php echo number_format($a->momentum_score, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function recalculateIntelligence() {
    const btn = document.getElementById('intel-recalc-btn');
    if (!btn) return;
    
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = 'Recalculating...';
    
    jQuery.post(window.charts_admin.ajax_url, {
        action: 'charts_recalculate_intel',
        nonce: window.charts_admin.nonce
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
