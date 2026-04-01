<?php
/**
 * Kontentainment Charts — Bento Data Sources Module
 */
global $wpdb;

$action = $_GET['action'] ?? 'list';
$edit_id = $_GET['id'] ?? 0;
$edit_source = null;

if ($edit_id) {
    $edit_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}charts_sources WHERE id = %d", $edit_id));
}

$sources = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_sources ORDER BY id DESC" );
?>

<div class="bento-grid">
    <?php if ($action === 'list') : ?>
        <!-- 1. SOURCE CARDS -->
        <?php if ( empty( $sources ) ) : ?>
            <div class="bento-card span-4" style="text-align:center; padding:80px; border:2px dashed var(--db-border);">
                <h3>No Active Connectors</h3>
                <p style="margin-bottom:24px; color:var(--db-text-muted);">Please add a data source to begin indexing chart rankings.</p>
                <a href="<?php echo esc_url(\Charts\Core\Router::get_dashboard_url('sources', array('action' => 'add'))); ?>" class="db-btn db-btn-primary">Add New Source</a>
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
                        <div style="display:flex; gap:12px;">
                            <a href="<?php echo esc_url(\Charts\Core\Router::get_dashboard_url('sources', array('action' => 'edit', 'id' => $source->id))); ?>" style="text-decoration:none; font-size:12px; font-weight:800; color:var(--db-secondary);">Config</a>
                            <form method="post" action="" onsubmit="return confirm('Delete source?');" style="display:inline;">
                                <?php wp_nonce_field('charts_admin_action'); ?>
                                <input type="hidden" name="charts_action" value="delete_source">
                                <input type="hidden" name="id" value="<?php echo $source->id; ?>">
                                <button type="submit" style="background:none; border:none; padding:0; color:#e74c3c; font-size:11px; font-weight:800; cursor:pointer;">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="bento-card" style="display:flex; align-items:center; justify-content:center; border:2px dashed var(--db-border); opacity:0.6;">
                 <a href="<?php echo esc_url(\Charts\Core\Router::get_dashboard_url('sources', array('action' => 'add'))); ?>" style="text-decoration:none; text-align:center; color:var(--db-text-dim);">
                    <span style="display:block; font-size:24px; font-weight:900;">+</span>
                    <span style="font-size:12px; font-weight:800;">Add Connector</span>
                 </a>
            </div>
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
    <?php else : ?>
        <!-- ADD / EDIT FORM (SPAN 4) -->
        <div class="bento-card span-4">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
                <h3 style="margin:0; font-size:20px; font-weight:900;"><?php echo ($action === 'edit') ? 'Modify Connector' : 'Provision New Connector'; ?></h3>
                <a href="<?php echo esc_url(\Charts\Core\Router::get_dashboard_url('sources')); ?>" class="db-btn">Cancel</a>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('charts_admin_action'); ?>
                <input type="hidden" name="charts_action" value="save_source">
                <?php if ($edit_source) : ?>
                    <input type="hidden" name="id" value="<?php echo (int) $edit_source->id; ?>">
                <?php endif; ?>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px;">
                    <div>
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Identity Name</label>
                            <input type="text" name="source_name" value="<?php echo $edit_source ? esc_attr($edit_source->source_name) : ''; ?>" class="db-input" placeholder="e.g. Egypt Daily High" required>
                        </div>
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Platform Surface</label>
                            <select name="platform" class="db-input" required>
                                <option value="spotify" <?php selected($edit_source ? $edit_source->platform : '', 'spotify'); ?>>Spotify</option>
                                <option value="youtube" <?php selected($edit_source ? $edit_source->platform : '', 'youtube'); ?>>YouTube</option>
                            </select>
                        </div>
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Engine Type</label>
                            <select name="source_type" class="db-input" required>
                                <option value="live_scrape" <?php selected($edit_source ? $edit_source->source_type : '', 'live_scrape'); ?>>Live Scrape</option>
                                <option value="manual_import" <?php selected($edit_source ? $edit_source->source_type : '', 'manual_import'); ?>>Manual Import (CSV)</option>
                                <option value="metadata_only" <?php selected($edit_source ? $edit_source->source_type : '', 'metadata_only'); ?>>Metadata Only</option>
                            </select>
                        </div>
                         <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Market Coverage (ISO)</label>
                            <input type="text" name="country_code" value="<?php echo $edit_source ? esc_attr($edit_source->country_code) : 'eg'; ?>" class="db-input" maxlength="10" required>
                        </div>
                    </div>
                    <div>
                         <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Discovery URL / Endpoint</label>
                            <input type="url" name="source_url" value="<?php echo $edit_source ? esc_url($edit_source->source_url) : ''; ?>" class="db-input" placeholder="https://...">
                            <p style="font-size:10px; color:var(--db-text-muted); margin-top:8px;">Target URL for live ingestion. Use 'manual' for CSV uploads.</p>
                        </div>
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Intelligence Frequency</label>
                            <select name="frequency" class="db-input" required>
                                <option value="daily" <?php selected($edit_source ? $edit_source->frequency : '', 'daily'); ?>>Daily</option>
                                <option value="weekly" <?php selected($edit_source ? $edit_source->frequency : '', 'weekly'); ?>>Weekly</option>
                            </select>
                        </div>
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Classification</label>
                            <select name="chart_type" class="db-input" required>
                                <option value="top-songs" <?php selected($edit_source ? $edit_source->chart_type : '', 'top-songs'); ?>>Top Songs</option>
                                <option value="top-artists" <?php selected($edit_source ? $edit_source->chart_type : '', 'top-artists'); ?>>Top Artists</option>
                                <option value="top-videos" <?php selected($edit_source ? $edit_source->chart_type : '', 'top-videos'); ?>>Top Videos</option>
                                <option value="viral-50" <?php selected($edit_source ? $edit_source->chart_type : '', 'viral-50'); ?>>Viral 50</option>
                            </select>
                        </div>
                         <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6; margin-bottom:8px;">Pipeline Status</label>
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:700;">
                                <input type="checkbox" name="is_active" value="1" <?php checked($edit_source ? $edit_source->is_active : 1); ?>> Enabled for Production
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-top:40px; border-top:1px solid var(--db-border); padding-top:32px;">
                    <button type="submit" class="db-btn db-btn-primary" style="padding:14px 40px;">Provision Connector</button>
                    <input type="hidden" name="parser_key" value="<?php echo $edit_source ? esc_attr($edit_source->parser_key) : 'spotify-v1'; ?>">
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
