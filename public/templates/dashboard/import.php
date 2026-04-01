<?php
/**
 * Kontentainment Charts — Bento Import Module (External)
 */
global $wpdb;

$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( false );
$pre_source  = $_GET['source'] ?? 'spotify';
?>

<div class="bento-grid">
    <!-- 1. IMPORT WORKFLOW (SPAN 3) -->
    <div class="bento-card span-3">
        <h3 style="margin:0 0 32px; font-size:18px; font-weight:900;">Intelligence Sync Workflow</h3>
        
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field( 'charts_admin_action' ); ?>
            <input type="hidden" name="charts_action" value="unified_import">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:40px;">
                <!-- Platform -->
                <div>
                    <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">Data Platform</label>
                    <div style="display:flex; gap:12px;">
                        <label class="platform-option">
                            <input type="radio" name="platform" value="spotify" <?php checked($pre_source, 'spotify'); ?> style="display:none;">
                            <div class="platform-box">Spotify</div>
                        </label>
                        <label class="platform-option">
                            <input type="radio" name="platform" value="youtube" <?php checked($pre_source, 'youtube'); ?> style="display:none;">
                            <div class="platform-box">YouTube</div>
                        </label>
                    </div>
                </div>

                <!-- Entity Type -->
                <div>
                    <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">Entity Model</label>
                    <select name="item_type" id="item_type" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:10px; background:var(--db-bg); color:var(--db-text); font-weight:700;">
                        <option value="track">Tracks (Audio)</option>
                        <option value="artist">Artists</option>
                        <option value="video">Clips & Videos</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:40px;">
                <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">Chart Definition Target</label>
                <select name="chart_id" id="chart_id" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:12px; background:var(--db-bg); color:var(--db-text); font-weight:700;">
                    <option value="">— Select Target Chart —</option>
                    <?php foreach ( $definitions as $definition ) : ?>
                        <option value="<?php echo (int) $definition->id; ?>"><?php echo esc_html( $definition->title ); ?> (<?php echo strtoupper($definition->country_code); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:40px; padding:40px; border:2px dashed var(--db-border); border-radius:12px; text-align:center;">
                <label for="import_file" style="cursor:pointer;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--db-accent); margin-bottom:12px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <p style="margin:0; font-weight:700; font-size:14px;">Upload Intelligence CSV</p>
                    <p style="margin:4px 0 0; font-size:11px; color:var(--db-text-dim);">Drop file here or click to browse</p>
                </label>
                <input type="file" name="import_file" id="import_file" accept=".csv" required style="display:none;">
            </div>

            <button type="submit" class="db-btn db-btn-primary" style="width:100%; padding:16px;">Initiate Secure Import</button>
        </form>
    </div>

    <!-- 2. RECENT RUNS (KPI) -->
    <div class="bento-card">
        <label class="kpi-title">Intelligence Flow</label>
        <div style="margin-top:20px;">
            <?php 
            $recent = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_import_runs ORDER BY started_at DESC LIMIT 3" );
            foreach ( $recent as $r ) :
            ?>
                <div style="margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid var(--db-border);">
                    <span style="display:block; font-size:12px; font-weight:800;"><?php echo date('M j, H:i', strtotime($r->started_at)); ?></span>
                    <span style="font-size:11px; color:var(--db-text-muted);"><?php echo (int)($r->parsed_rows ?: $r->created_items); ?> rows &middot; <?php echo $r->status; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.platform-box {
    padding: 10px 20px;
    background: var(--db-bg);
    border: 1px solid var(--db-border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
input:checked + .platform-box {
    background: var(--db-accent);
    color: white;
    border-color: var(--db-accent);
}
</style>
