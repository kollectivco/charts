<?php
/**
 * Settings View - High-Fidelity Refactor
 */
$menus = wp_get_nav_menus();
$selected_menu = get_option( 'charts_header_menu_id' );
$logo_id = get_option( 'charts_logo_id' );
$logo_url = '';
if ( $logo_id ) {
	$logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
}

// Defaults
$wordmark = get_option( 'charts_wordmark', 'KCharts' );
$footer_desc = get_option( 'charts_footer_description' );
$footer_copy = get_option( 'charts_footer_copyright', 'Kontentainment Charts.' );
?>
<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'System Configuration', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Global architecture, branding identity, and third-party API connectivity.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<button type="submit" class="charts-btn-create" form="charts-settings-form">
				<span class="dashicons dashicons-saved" style="margin-right:8px;"></span>
				<?php _e( 'Save System Configuration', 'charts' ); ?>
			</button>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<form method="post" action="" id="charts-settings-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">

		<!-- 1. Branding & Shell -->
		<div class="premium-form-card">
			<div class="card-header">
				<h3>Branding & Shell</h3>
				<p>Core identity and standalone rendering behavior.</p>
			</div>
			<div class="premium-form-grid">
				<div class="form-group">
					<label for="wordmark">Product Wordmark</label>
					<input type="text" name="wordmark" id="wordmark" value="<?php echo esc_attr($wordmark); ?>" class="form-control" placeholder="KCharts">
					<span class="input-helper">Used when no logo is selected or as high-fidelity fallback.</span>
				</div>
				<div class="form-group">
					<label>Shell Configuration</label>
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:15px; font-size:12px; color:#64748b; line-height:1.5;">
						<div style="display:flex; align-items:center; gap:10px; color:#0f172a; font-weight:700; margin-bottom:5px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
							Standalone Architecture Enabled
						</div>
						Charts routes automatically use a plugin-controlled cinematic shell for maximum performance and design fidelity. The main site remains unaffected.
					</div>
				</div>
				
				<div class="form-group">
					<label>Logo Identity</label>
					<div class="logo-preview-wrapper" style="margin-bottom: 15px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; display:flex; align-items:center; justify-content:center; min-height:120px;">
						<img id="logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="max-height: 80px; display: <?php echo $logo_url ? 'block' : 'none'; ?>;">
						<?php if(!$logo_url): ?><span style="color:#94a3b8; font-size:12px; font-weight:600;">No Logo Selected</span><?php endif; ?>
					</div>
					<input type="hidden" name="logo_id" id="logo_id" value="<?php echo esc_attr( $logo_id ); ?>">
					<div style="display:flex; gap:10px;">
						<button type="button" class="charts-btn-back" id="upload_logo_btn" style="flex:1;">Select Logo</button>
						<button type="button" class="charts-btn-back" id="remove_logo_btn" style="color:#ef4444; border-color:#fee2e2;">Remove</button>
					</div>
				</div>
				<div class="form-group">
					<label for="logo_alt">Logo Alt Text</label>
					<input type="text" name="logo_alt" id="logo_alt" value="<?php echo esc_attr( get_option( 'charts_logo_alt' ) ); ?>" class="form-control">
					<span class="input-helper">Accessibility label for the branding image.</span>
				</div>
			</div>
		</div>

		<!-- 2. Header Configuration -->
		<div class="premium-form-card" style="margin-top:40px;">
			<div class="card-header">
				<h3>Header Configuration</h3>
				<p>Controls visibility and navigation for the standalone header.</p>
			</div>
			<div class="premium-form-grid">
				<div class="form-group">
					<label>Header Context</label>
					<div style="font-size:13px; font-weight:600; color:#64748b;">
						The cinematic navigation bar is forced on all Charts-owned routes to ensure data-driven navigation remains functional.
					</div>
				</div>
				<div class="form-group">
					<label for="header_menu_id"><?php esc_html_e( 'Assigned Navigation Menu', 'charts' ); ?></label>
					<select name="header_menu_id" id="header_menu_id" class="form-control">
						<option value="0"><?php esc_html_e( '— Select WordPress Menu —', 'charts' ); ?></option>
						<?php if ( ! empty( $menus ) ) : foreach ( $menus as $menu ) : ?>
							<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $selected_menu, $menu->term_id ); ?>>
								<?php echo esc_html( $menu->name ); ?>
							</option>
						<?php endforeach; endif; ?>
					</select>
					<span class="input-helper">Primary navigation links for chart pages.</span>
				</div>
				<div class="form-group form-group-full">
					<label>Visible Elements</label>
					<div class="toggle-row" style="margin-top:15px; grid-template-columns: repeat(3, 1fr);">
						<div class="toggle-item">
							<label class="switch"><input type="checkbox" name="show_logo" value="1" <?php checked( 1, get_option( 'charts_show_logo', 1 ) ); ?>><span class="slider"></span></label>
							<label>Logotype</label>
						</div>
						<div class="toggle-item">
							<label class="switch"><input type="checkbox" name="show_nav" value="1" <?php checked( 1, get_option( 'charts_show_nav', 1 ) ); ?>><span class="slider"></span></label>
							<label>Navigation</label>
						</div>
						<div class="toggle-item">
							<label class="switch"><input type="checkbox" name="show_search" value="1" <?php checked( 1, get_option( 'charts_show_search', 1 ) ); ?>><span class="slider"></span></label>
							<label>Search Trigger</label>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- 3. Footer Configuration -->
		<div class="premium-form-card" style="margin-top:40px;">
			<div class="card-header">
				<h3>Footer Configuration</h3>
				<p>Manage the editorial imprint and copyright metadata.</p>
			</div>
			<div class="premium-form-grid">
				<div class="form-group">
					<label>Footer Context</label>
					<div style="font-size:13px; font-weight:600; color:#64748b;">
						The cinematic 4-column footer is forced on all Charts-owned routes to provide platform consistency and data attribution.
					</div>
				</div>
				<div class="form-group">
					<label for="footer_copyright">Copyright Label</label>
					<input type="text" name="footer_copyright" id="footer_copyright" value="<?php echo esc_attr($footer_copy); ?>" class="form-control" placeholder="Kontentainment Charts.">
					<span class="input-helper">Displayed in the bottom strip of the footer.</span>
				</div>
				<div class="form-group form-group-full">
					<label for="footer_description">Platform Description</label>
					<textarea name="footer_description" id="footer_description" class="form-control" rows="3"><?php echo esc_textarea($footer_desc); ?></textarea>
					<span class="input-helper">Brief editorial summary displayed in the brand column.</span>
				</div>
			</div>
		</div>

		<!-- 4. Intelligence API Credentials -->
		<div class="premium-form-card" style="margin-top:40px; border-left: 4px solid var(--charts-primary);">
			<div class="card-header">
				<h3>Intelligence API Credentials</h3>
				<p>Required for manual enrichment and real-time metadata discovery.</p>
			</div>
			<div class="premium-form-grid">
				<?php 
					$spotify_id = get_option( 'charts_spotify_client_id' );
					$spotify_secret = get_option( 'charts_spotify_client_secret' );
					$yt_key = get_option( 'charts_youtube_api_key' );

					// Masking helpers
					$mask = function($str) {
						if (!$str) return '';
						if (strlen($str) <= 8) return '********';
						return substr($str, 0, 4) . '...' . substr($str, -4);
					};
				?>
				<div class="form-group">
					<label for="spotify_client_id">Spotify Client ID</label>
					<input type="text" name="spotify_client_id" id="spotify_client_id" value="<?php echo esc_attr( $spotify_id ); ?>" class="form-control" style="font-family:monospace;" placeholder="Enter Client ID">
				</div>
				<div class="form-group">
					<label for="spotify_client_secret">Spotify Client Secret</label>
					<input type="password" name="spotify_client_secret" id="spotify_client_secret" value="<?php echo esc_attr( $spotify_secret ); ?>" class="form-control" placeholder="Enter Client Secret">
					<span class="input-helper">Used for OAuth2 server-to-server communication.</span>
				</div>
				<div class="form-group">
					<label for="youtube_api_key">YouTube Data API (v3) Key</label>
					<input type="password" name="youtube_api_key" id="youtube_api_key" value="<?php echo esc_attr( $yt_key ); ?>" class="form-control" placeholder="Enter API Key">
					<span class="input-helper">Required for automatic YouTube metadata enrichment.</span>
				</div>
				<div class="form-group">
					<label>API Connectivity Diagnostics</label>
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px;">
						<div style="display:grid; gap:12px; font-size:12px;">
							<div style="display:flex; align-items:center; justify-content:space-between;">
								<span style="font-weight:700; color:#475569;">Spotify Client:</span>
								<span class="charts-badge <?php echo $spotify_id ? 'charts-badge-success' : 'charts-badge-neutral'; ?>" style="font-size:10px;">
									<?php echo $spotify_id ? 'CONFIGURED (' . $mask($spotify_id) . ')' : 'MISSING'; ?>
								</span>
							</div>
							<div style="display:flex; align-items:center; justify-content:space-between;">
								<span style="font-weight:700; color:#475569;">YouTube Key:</span>
								<span class="charts-badge <?php echo $yt_key ? 'charts-badge-success' : 'charts-badge-neutral'; ?>" style="font-size:10px;">
									<?php echo $yt_key ? 'CONFIGURED (' . $mask($yt_key) . ')' : 'MISSING'; ?>
								</span>
							</div>
						</div>
						<div style="display:flex; gap:10px; margin-top:20px;">
							<button type="submit" name="charts_action" value="test_spotify_api" class="charts-btn-back" style="flex:1; font-size:11px; font-weight:700;">Test Spotify</button>
							<button type="submit" name="charts_action" value="test_youtube_api" class="charts-btn-back" style="flex:1; font-size:11px; font-weight:700;">Test YouTube</button>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label>Asset Diagnostics & Repair</label>
					<div style="background:#fefce8; border:1px solid #fef08a; border-radius:12px; padding:20px;">
						<p style="font-size:12px; color:#a16207; margin:0 0 15px; font-weight:500;">
							If Spotify or YouTube APIs were unavailable during import, some records may be missing cover art or thumbnails. 
							Run this tool to re-enrich incomplete records with missing media assets.
						</p>
						<button type="submit" name="charts_action" value="backfill_media" class="charts-btn-back" style="width:100%; border-color:#fde047; background:#fff; color:#854d0e;">
							<span class="dashicons dashicons-image-rotate" style="font-size:16px; margin-right:8px; vertical-align:middle;"></span>
							Backfill Missing Assets
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- 5. Danger Zone -->
		<div class="premium-form-card" style="margin-top:60px; border: 1px solid #fee2e2; background: #fffcfc; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.05);">
			<div class="card-header" style="border-bottom-color: #fee2e2;">
				<h3 style="color: #ef4444; display: flex; align-items: center; gap: 10px;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					Danger Zone
				</h3>
				<p style="color: #991b1b; font-weight: 500;">Reset plugin data and return to a fresh state.</p>
			</div>
			
			<div class="premium-form-grid" style="grid-template-columns: 1.5fr 1fr;">
				<div style="font-size: 13px; color: #7f1d1d; line-height: 1.6;">
					<p style="margin: 0 0 15px;">
						This action is <strong>irreversible</strong>. It will permanently delete all:
					</p>
					<ul style="margin: 0 0 20px; padding-left: 20px;">
						<li>Chart definitions and data sources</li>
						<li>Imported tracks, artists, and videos</li>
						<li>All chart entries and historical periods</li>
						<li>Intelligence scores and insights</li>
						<li>Import logs and system transients</li>
					</ul>
					<div class="toggle-item" style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #fee2e2;">
						<label class="switch"><input type="checkbox" name="wipe_settings" value="1"><span class="slider" style="background-color: #fecaca;"></span></label>
						<label style="color: #ef4444; font-weight: 700;">Also wipe API keys and branding settings</label>
					</div>
				</div>
				
				<div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #fee2e2; display: flex; flex-direction: column; gap: 15px;">
					<label style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #ef4444; letter-spacing: 0.05em;">Type "RESET CHARTS" to confirm</label>
					<input type="text" id="reset_confirm_input" class="form-control" placeholder="RESET CHARTS" style="border-color: #fca5a5; text-align: center; font-weight: 800; letter-spacing: 0.05em;">
					<button type="submit" name="charts_action" value="reset_plugin" id="reset_plugin_btn" class="charts-btn-create" style="width: 100%; background: #ef4444; opacity: 0.3; cursor: not-allowed;" disabled>
						Reset Plugin to Zero
					</button>
				</div>
			</div>
		</div>

		<div class="charts-admin-footer-bar" style="margin-top:60px; padding-top:30px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
			<button type="submit" class="charts-btn-create">
				Save System Configuration
			</button>
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($){
	var frame;
	$('#upload_logo_btn').on('click', function(e){
		e.preventDefault();
		if (frame) { frame.open(); return; }
		frame = wp.media({
			title: 'Select or Upload Logo',
			button: { text: 'Use this logo' },
			multiple: false
		});
		frame.on('select', function(){
			var attachment = frame.state().get('selection').first().toJSON();
			$('#logo_id').val(attachment.id);
			$('#logo-preview').attr('src', attachment.url).show();
			$('.logo-preview-wrapper span').hide();
		});
		frame.open();
	});

	$('#remove_logo_btn').on('click', function(e){
		e.preventDefault();
		$('#logo_id').val('');
		$('#logo-preview').hide();
		$('.logo-preview-wrapper span').show();
	});

	// Danger Zone: Confirmation Phrasing
	$('#reset_confirm_input').on('input', function() {
		var val = $(this).val().trim();
		if (val === 'RESET CHARTS') {
			$('#reset_plugin_btn').prop('disabled', false).css({
				'opacity': '1',
				'cursor': 'pointer'
			});
		} else {
			$('#reset_plugin_btn').prop('disabled', true).css({
				'opacity': '0.3',
				'cursor': 'not-allowed'
			});
		}
	});

	$('#reset_plugin_btn').on('click', function(e) {
		if (!confirm('EXTREME WARNING: You are about to permanently delete all charts data. This cannot be undone. Are you absolutely sure?')) {
			e.preventDefault();
		}
	});
});
</script>

<style>
.premium-form-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
.card-header { margin-bottom: 32px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
.card-header h3 { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0f172a; }
.card-header p { margin: 0; font-size: 14px; color: #64748b; }

.charts-admin-header { margin-bottom: 40px; }
.charts-admin-wrap { padding: 40px; background: #f8fafc; min-height: 100vh; }

/* Mirror existing premium-form-grid if not global */
.premium-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
.form-group-full { grid-column: span 2; }
.form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; }
.form-control { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
.form-control:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
.input-helper { display: block; font-size: 12px; color: #94a3b8; margin-top: 8px; }

.toggle-row { display: grid; gap: 20px; }
.toggle-item { display: flex; align-items: center; gap: 15px; }
.toggle-item label { margin-bottom: 0; cursor: pointer; }

/* Switch Toggle - High Fidelity */
.switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e2e8f0; transition: .3s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
input:checked + .slider { background-color: #6366f1; }
input:checked + .slider:before { transform: translateX(20px); }

.charts-btn-create { background: #0f172a; color: white; border: none; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s; }
.charts-btn-create:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.charts-btn-back { background: white; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
.charts-btn-back:hover { background: #f8fafc; border-color: #cbd5e1; }
</style>
