<?php
/**
 * Kontentainment Charts — Bento Settings Module (External)
 */
global $wpdb;

$spotify_id = \Charts\Core\Settings::get('api.spotify_client_id');
$spotify_secret = \Charts\Core\Settings::get('api.spotify_client_secret');
$youtube_key = \Charts\Core\Settings::get('api.youtube_api_key');

$wordmark = \Charts\Core\Settings::get( 'labels.chart_cta_text', 'KCharts' ); // Fallback as wordmark doesn't exist natively.
$show_logo = 1;
$show_nav = 1;

// Re-using same form action as admin
?>

<div class="bento-grid">
    <!-- 1. API CONNECTORS (SPAN 2) -->
    <div class="bento-card span-2 row-2">
        <h3 style="margin:0 0 32px; font-size:18px; font-weight:900;">Intelligence Connectors</h3>
        
        <form method="post" action="">
            <?php wp_nonce_field( 'charts_admin_action' ); ?>
            <input type="hidden" name="charts_action" value="save_settings">

            <div style="margin-bottom:32px;">
                <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">Spotify Intelligence API</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <input type="text" name="spotify_client_id" value="<?php echo esc_attr($spotify_id); ?>" placeholder="Client ID" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:10px; background:var(--db-bg); color:var(--db-text);">
                    <input type="password" name="spotify_client_secret" value="<?php echo esc_attr($spotify_secret); ?>" placeholder="Client Secret" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:10px; background:var(--db-bg); color:var(--db-text);">
                </div>
            </div>

            <div style="margin-bottom:32px;">
                <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">YouTube Data API v3</label>
                <input type="password" name="youtube_api_key" value="<?php echo esc_attr($youtube_key); ?>" placeholder="API Key" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:10px; background:var(--db-bg); color:var(--db-text);">
            </div>

            <div style="margin-bottom:32px; padding-top:20px; border-top:1px solid var(--db-border);">
                <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:var(--db-text-muted); margin-bottom:12px;">Brand Wordmark</label>
                <input type="text" name="wordmark" value="<?php echo esc_attr($wordmark); ?>" style="width:100%; border-radius:8px; border:1px solid var(--db-border); padding:10px; background:var(--db-bg); color:var(--db-text);">
            </div>

            <button type="submit" class="db-btn db-btn-primary" style="width:100%;">Commit System Changes</button>
        </form>
    </div>

    <!-- 2. DIAGNOSTICS -->
    <div class="bento-card">
        <label class="kpi-title">Core Health</label>
        <div style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span style="font-size:13px; font-weight:700;">DB Version</span>
                <span class="status-pill status-active">1.9.5</span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:13px; font-weight:700;">Isolation</span>
                <span class="status-pill status-active">Active</span>
            </div>
        </div>
    </div>

    <!-- 3. BRAND CONFIG -->
    <div class="bento-card">
        <label class="kpi-title">Control Flags</label>
        <div style="margin-top:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span style="font-size:12px; font-weight:650;">Branded Header</span>
                <span class="status-pill <?php echo $show_logo ? 'status-active' : 'status-pending'; ?>"><?php echo $show_logo ? 'On' : 'Off'; ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:12px; font-weight:650;">Product Nav</span>
                <span class="status-pill <?php echo $show_nav ? 'status-active' : 'status-pending'; ?>"><?php echo $show_nav ? 'On' : 'Off'; ?></span>
            </div>
        </div>
    </div>

    <!-- 4. MAINTENANCE TOOLS (SPAN 2) -->
    <div class="bento-card span-2" style="background:#0f172a; color:white; border:none;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0 0 8px; font-size:16px; font-weight:900;">Intelligence Recovery</h3>
                <p style="margin:0; font-size:12px; opacity:0.7;">Repair records with missing media or empty cover metadata.</p>
            </div>
            <form method="post" action="">
                <?php wp_nonce_field( 'charts_admin_action' ); ?>
                <button type="submit" name="charts_action" value="backfill_media" class="db-btn" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:white;">
                    Sync Missing Media
                </button>
            </form>
        </div>
    </div>

    <!-- 5. DANGER ZONE -->
    <div class="bento-card span-2" style="border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.03);">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0 0 8px; font-size:16px; font-weight:900; color:#ef4444;">Danger Zone</h3>
                <p style="margin:0; font-size:12px; color:#ef4444; font-weight:600; opacity:0.8;">Purge all local data and return the system to absolute zero.</p>
            </div>
            
            <form method="post" action="" style="display:flex; align-items:center; gap:24px;">
                <?php wp_nonce_field( 'charts_admin_action' ); ?>
                <div style="display:flex; align-items:center; gap:12px;">
                    <label class="switch"><input type="checkbox" name="wipe_settings" value="1"><span class="slider" style="background-color:#fca5a5;"></span></label>
                    <span style="font-size:11px; font-weight:800; color:#ef4444;">Wipe API Keys</span>
                </div>
                <div style="position:relative;">
                    <input type="text" id="db_reset_confirm" placeholder="Type RESET CHARTS" style="border-radius:8px; border:1px solid #fca5a5; padding:8px 12px; background:white; color:#ef4444; font-weight:800; font-size:11px; text-align:center; min-width:180px;">
                </div>
                <button type="submit" name="charts_action" value="reset_plugin" id="db_reset_btn" class="db-btn" style="background:#ef4444; color:white; border:none; opacity:0.3; cursor:not-allowed;" disabled>
                    Destructive Reset
                </button>
            </form>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($){
    $('#db_reset_confirm').on('input', function(){
        if ($(this).val().trim() === 'RESET CHARTS') {
            $('#db_reset_btn').prop('disabled', false).css({'opacity':'1', 'cursor':'pointer'});
        } else {
            $('#db_reset_btn').prop('disabled', true).css({'opacity':'0.3', 'cursor':'not-allowed'});
        }
    });

    $('#db_reset_btn').on('click', function(e){
        if (!confirm('EXTREME WARNING: This will permanently delete all charts data. Are you absolutely sure?')) {
            e.preventDefault();
        }
    });
});
</script>
