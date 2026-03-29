<?php
/**
 * Kontentainment Charts — Bento Data Sources Module
 */
global $wpdb;
$sources = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_sources ORDER BY id DESC" );
?>

<div class="bento-grid">
    <!-- 1. SOURCE CARDS -->
    <?php if ( empty( $sources ) ) : ?>
        <div class="bento-card span-4" style="text-align:center; padding:80px; border:2px dashed var(--db-border);">
            <h3>No Active Connectors</h3>
            <p style="margin-bottom:24px; color:var(--db-text-muted);">Please add a data source to begin indexing chart rankings.</p>
            <a href="<?php echo admin_url('admin.php?page=charts-sources&action=add'); ?>" class="db-btn db-btn-primary">Add New Source</a>
        </div>
    <?php else : ?>
        <?php foreach ( $sources as $source ) : 
            $status = $source->is_active ? 'Active' : 'Inactive';
            $status_class = $source->is_active ? 'status-active' : 'status-pending';
            $platform_icon = strpos(strtolower($source->source_name), 'spotify') !== false ? 'Spotify' : (strpos(strtolower($source->source_name), 'youtube') !== false ? 'YouTube' : 'External');
        ?>
            <div class="bento-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                    <div style="width:40px; height:40px; background:var(--db-bg); border-radius:10px; display:flex; align-items:center; justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--db-accent);"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </div>
                    <span class="status-pill <?php echo $status_class; ?>"><?php echo $status; ?></span>
                </div>
                <h3 style="font-size:16px; font-weight:900; margin:0 0 8px;"><?php echo esc_html($source->source_name); ?></h3>
                <span class="kpi-title" style="margin-bottom:24px;"><?php echo $platform_icon; ?> &middot; <?php echo strtoupper($source->country_code); ?></span>
                
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--db-border); padding-top:16px;">
                    <span style="font-size:11px; font-weight:700; opacity:0.6; text-transform:uppercase;"><?php echo esc_html($source->chart_type); ?></span>
                    <a href="<?php echo admin_url('admin.php?page=charts-sources&action=edit&id='.$source->id); ?>" style="text-decoration:none; font-size:12px; font-weight:800; color:var(--db-secondary);">Config &rarr;</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- 2. TOTAL REACH (KPI) -->
    <div class="bento-card">
        <label class="kpi-title">Active Connectors</label>
        <span class="kpi-val"><?php echo count($sources); ?></span>
        <span class="kpi-trend" style="color:var(--db-secondary);">Multi-Platform Sync</span>
    </div>

    <!-- 3. HEALTH (SPAN 2) -->
    <div class="bento-card span-2">
        <label class="kpi-title">Connector Topology</label>
        <div style="margin-top:20px; display:flex; gap:20px;">
            <div style="flex-grow:1; background:var(--db-bg); padding:16px; border-radius:12px; text-align:center;">
                <span style="display:block; font-size:18px; font-weight:900;">100%</span>
                <span style="font-size:9px; font-weight:850; text-transform:uppercase; opacity:0.6;">Uptime</span>
            </div>
            <div style="flex-grow:1; background:var(--db-bg); padding:16px; border-radius:12px; text-align:center;">
                <span style="display:block; font-size:18px; font-weight:900;">Stable</span>
                <span style="font-size:9px; font-weight:850; text-transform:uppercase; opacity:0.6;">API State</span>
            </div>
        </div>
    </div>
</div>
