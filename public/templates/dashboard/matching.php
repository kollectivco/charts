<?php
/**
 * Kontentainment Charts — Bento Matching Center
 */
global $wpdb;

$pending_entries = $wpdb->get_results( "
    SELECT e.*, s.source_name, d.title as definition_title
    FROM {$wpdb->prefix}charts_entries e
    JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
    LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
    WHERE e.item_id = 0
    ORDER BY e.created_at DESC LIMIT 50
" );

$stats = array(
    'total_unmatched' => count($pending_entries),
);
?>

<div class="bento-grid">
    <!-- 1. SUMMARY -->
    <div class="bento-card">
        <label class="kpi-title">Matching Health</label>
        <span class="kpi-val"><?php echo number_format($stats['total_unmatched']); ?></span>
        <span class="kpi-trend" style="color:var(--db-accent);">Pending Resolution</span>
    </div>

    <!-- 2. AUTO-MATCH WORKER -->
    <div class="bento-card">
        <label class="kpi-title">Intelligence Worker</label>
        <div style="margin-top:20px;">
            <p style="font-size:12px; color:var(--db-text-dim); margin-bottom:16px;">The worker automatically reconciles entities based on canonical metadata.</p>
            <form method="post" action="">
                <?php wp_nonce_field( 'charts_admin_action' ); ?>
                <input type="hidden" name="charts_action" value="run_integrity_check">
                <button type="submit" class="db-btn db-btn-primary" style="width:100%;">Run Auto-Matcher</button>
            </form>
        </div>
    </div>

    <!-- 3. UNMATCHED ENTRIES (SPAN 4 / ROW 2) -->
    <div class="bento-card span-4 row-2">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
            <h3 style="margin:0; font-size:18px; font-weight:900;">Entities Requiring Manual Resolution</h3>
            <div class="db-actions">
                <span class="status-pill status-pending"><?php echo count($pending_entries); ?> Blocked Records</span>
            </div>
        </div>

        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Track / Artist</th>
                        <th>Source Chart</th>
                        <th>Rank</th>
                        <th>Confidence</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $pending_entries ) ) : ?>
                        <tr><td colspan="5" style="padding:80px; text-align:center; color:var(--db-text-muted);">Excellent! Local metadata is 100% reconciled.</td></tr>
                    <?php else: ?>
                        <?php foreach ( $pending_entries as $e ) : ?>
                            <tr>
                                <td style="font-weight:700;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="width:36px; height:36px; border-radius:6px; background:#eee; flex-shrink:0;">
                                            <img src="<?php echo esc_url($e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">
                                        </div>
                                        <div>
                                            <span style="display:block; font-size:13px;"><?php echo esc_html($e->track_name); ?></span>
                                            <span style="display:block; font-size:11px; opacity:0.6; font-weight:600;"><?php echo esc_html($e->artist_names); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="font-size:11px; font-weight:700; opacity:0.6;"><?php echo esc_html($e->definition_title ?: $e->source_name); ?></span></td>
                                <td style="font-weight:900; color:var(--db-secondary);">#<?php echo $e->rank_position; ?></td>
                                <td><span class="status-pill status-pending" style="background:rgba(231, 76, 60, 0.1); color:#e74c3c;">Low Match</span></td>
                                <td style="text-align:right;">
                                    <?php 
                                    $search_label = $e->track_name ?: $e->artist_names;
                                    $search_type = $e->item_type === 'artist' ? 'artist' : 'track';
                                    ?>
                                    <a href="<?php echo esc_url(\Charts\Core\Router::get_dashboard_url('entities', array('s' => urlencode($search_label), 'charts_type' => $search_type))); ?>" class="db-btn" style="padding:6px 12px; font-size:11px;">Manual Resolve</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
