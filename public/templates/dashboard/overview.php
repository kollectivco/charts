<?php
/**
 * Kontentainment Charts — Bento Overview Dashboard
 */
global $wpdb;

$stats = array(
    'charts_total'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions" ),
    'charts_published' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_definitions WHERE is_public = 1" ),
    'tracks'           => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks" ),
    'artists'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists" ),
    'pending'          => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries WHERE item_id = 0" ),
    'imports'          => $wpdb->get_results( "SELECT i.*, s.source_name FROM {$wpdb->prefix}charts_import_runs i JOIN {$wpdb->prefix}charts_sources s ON s.id = i.source_id ORDER BY i.started_at DESC LIMIT 6" ),
);
?>

<div class="bento-grid">
    <!-- 1. TOTAL CHARTS (KPI) -->
    <div class="bento-card">
        <label class="kpi-title">Active Charts</label>
        <span class="kpi-val"><?php echo $stats['charts_total']; ?></span>
        <span class="kpi-trend">+<?php echo $stats['charts_published']; ?> Published</span>
    </div>

    <!-- 2. TOTAL ARTISTS (KPI) -->
    <div class="bento-card">
        <label class="kpi-title">Indexed Artists</label>
        <span class="kpi-val"><?php echo number_format($stats['artists']); ?></span>
        <span class="kpi-trend" style="color:var(--db-secondary);">Managed Entities</span>
    </div>

    <!-- 3. DATA HEALTH (SPAN 2) -->
    <div class="bento-card span-2">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <label class="kpi-title">Data Accuracy & Matching</label>
            <span class="status-pill status-active">Good Health</span>
        </div>
        <div style="display:flex; gap:40px;">
            <div>
                <span style="font-size:24px; font-weight:900; color:var(--db-text);"><?php echo $stats['pending']; ?></span>
                <p style="font-size:11px; font-weight:700; color:var(--db-text-muted); margin-top:4px;">Unmatched Entries</p>
            </div>
            <div style="flex-grow:1; background:var(--db-bg); height:12px; border-radius:6px; margin-top:14px; position:relative;">
                <div style="width:<?php echo max(0, 100 - ($stats['pending'] / 10)); ?>%; background:var(--db-accent); height:100%; border-radius:6px;"></div>
            </div>
        </div>
        <p style="margin-top:20px; font-size:12px; color:var(--db-text-dim);">Run the Matching Center periodically to resolve unmatched tracks and artists.</p>
    </div>

    <!-- 4. RECENT IMPORTS (SPAN 3 / ROW 2) -->
    <div class="bento-card span-3 row-2">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
            <h3 style="margin:0; font-size:18px; font-weight:900;">Operational Activity</h3>
            <a href="<?php echo home_url('/charts-dashboard/import'); ?>" class="db-btn">Run New Import</a>
        </div>
        
        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Entries</th>
                        <th>Started At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $stats['imports'] ) ) : ?>
                        <tr><td colspan="4">No recent import runs recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ( $stats['imports'] as $run ) : ?>
                            <tr>
                                <td style="font-weight:700;"><?php echo esc_html($run->source_name); ?></td>
                                <td><span class="status-pill <?php echo $run->status === 'completed' ? 'status-active' : 'status-pending'; ?>"><?php echo esc_html($run->status); ?></span></td>
                                <td style="font-weight:700;"><?php echo (int)($run->parsed_rows ?: $run->created_items); ?></td>
                                <td style="color:var(--db-text-muted);"><?php echo date('M j, Y H:i', strtotime($run->started_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. SYNC STATUS -->
    <div class="bento-card">
        <label class="kpi-title">Sync Logic</label>
        <div style="margin-top:20px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                <div style="width:8px; height:8px; background:#2ecc71; border-radius:50%;"></div>
                <span style="font-size:13px; font-weight:700;">Worker Active</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:8px; height:8px; background:var(--db-secondary); border-radius:50%;"></div>
                <span style="font-size:13px; font-weight:700;">Auto-Recalc On</span>
            </div>
        </div>
    </div>

    <!-- 6. EXTERNAL SITE STATS -->
    <div class="bento-card">
        <label class="kpi-title">Intelligence View</label>
        <span class="kpi-val"><?php echo number_format($stats['tracks']); ?></span>
        <span class="kpi-trend" style="color:var(--db-accent);">Total Tracks Indexed</span>
    </div>

</div>
