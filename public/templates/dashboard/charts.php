<?php
/**
 * Kontentainment Charts — Bento Manage Charts Module
 */
global $wpdb;
$definitions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_definitions ORDER BY id DESC" );
?>

<div class="bento-grid">
    <!-- 1. MANAGE CHART LIST (SPAN 3 / ROW 2) -->
    <div class="bento-card span-3 row-2">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
            <h3 style="margin:0; font-size:18px; font-weight:900;">Active Chart Definitions</h3>
            <button class="db-btn db-btn-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=charts-definitions&action=new'); ?>'">Register New Chart</button>
        </div>

        <div class="db-table-wrap">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $definitions ) ) : ?>
                        <tr><td colspan="5">No chart definitions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ( $definitions as $def ) : ?>
                            <tr>
                                <td style="font-weight:700;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="width:8px; height:8px; border-radius:50%; background:<?php echo $def->accent_color ?: '#fe025b'; ?>;"></div>
                                        <?php echo esc_html($def->title); ?>
                                    </div>
                                </td>
                                <td><span style="font-size:11px; font-weight:700; opacity:0.6; text-transform:uppercase;"><?php echo esc_html($def->chart_type); ?></span></td>
                                <td><span style="font-size:11px; font-weight:700; opacity:0.6; text-transform:uppercase;"><?php echo esc_html($def->frequency); ?></span></td>
                                <td><span class="status-pill <?php echo $def->is_public ? 'status-active' : 'status-pending'; ?>"><?php echo $def->is_public ? 'Published' : 'Draft'; ?></span></td>
                                <td style="text-align:right;">
                                    <a href="<?php echo admin_url('admin.php?page=charts-definitions&action=edit&id=' . $def->id); ?>" class="db-btn" style="padding:6px 12px; font-size:11px;">Edit Configuration</a>
                                    <a href="<?php echo home_url('/charts/' . $def->slug); ?>" target="_blank" class="db-btn" style="padding:6px 12px; font-size:11px;">View Public</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. TOTAL STATS (KPIs) -->
    <div class="bento-card">
        <label class="kpi-title">Global Chart Reach</label>
        <span class="kpi-val"><?php echo count($definitions); ?></span>
        <span class="kpi-trend" style="color:var(--db-secondary);">Managed Indexes</span>
    </div>

    <!-- 3. PUBLIC REACH -->
    <div class="bento-card">
        <label class="kpi-title">Public Status</label>
        <?php 
        $public_count = count(array_filter($definitions, function($d) { return $d->is_public; })); 
        ?>
        <span class="kpi-val"><?php echo $public_count; ?></span>
        <span class="kpi-trend">+<?php echo count($definitions) - $public_count; ?> Under Review</span>
    </div>
</div>
