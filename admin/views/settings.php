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
$slider_style = get_option( 'charts_homepage_slider_style', 'style-1' );
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

	<!-- Tab Navigation -->
	<div class="charts-tabs-nav">
		<button type="button" class="tab-link active" data-tab="general">General</button>
		<button type="button" class="tab-link" data-tab="header">Header</button>
		<button type="button" class="tab-link" data-tab="footer">Footer</button>
		<button type="button" class="tab-link" data-tab="homepage">Homepage</button>
		<button type="button" class="tab-link" data-tab="markets">Markets</button>
		<button type="button" class="tab-link" data-tab="apis">APIs</button>
		<button type="button" class="tab-link" data-tab="maintenance">Maintenance</button>
	</div>

	<form method="post" action="" id="charts-settings-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">

		<!-- TAB: GENERAL -->
		<div id="tab-general" class="tab-content active">
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
						<label for="theme_mode">Chart Theme Mode</label>
						<select name="theme_mode" id="theme_mode" class="form-control">
							<option value="light" <?php selected( get_option('charts_theme_mode', 'light'), 'light' ); ?>>Light Mode</option>
							<option value="dark" <?php selected( get_option('charts_theme_mode', 'light'), 'dark' ); ?>>Dark Mode</option>
						</select>
						<span class="input-helper">Determine the base color palette for all public chart interfaces.</span>
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
		</div>

		<!-- TAB: HEADER -->
		<div id="tab-header" class="tab-content">
			<!-- 2. Header Configuration -->
			<div class="premium-form-card">
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
		</div>

		<!-- TAB: FOOTER -->
		<div id="tab-footer" class="tab-content">
			<!-- 3. Footer Configuration -->
			<div class="premium-form-card">
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
		</div>

		<!-- TAB: HOMEPAGE -->
		<div id="tab-homepage" class="tab-content">
			<!-- 3.5 Homepage Configuration -->
			<div class="premium-form-card">
				<div class="card-header">
					<h3>Homepage Slider Engine</h3>
					<p>Configure the motion, layout, and content of the cinematic hero slider on the charts homepage.</p>
				</div>
				
				<?php 
				$s_style = get_option('charts_slider_style', 'coverflow');
				$s_enable = get_option('charts_slider_enable', 1);
				$s_count = get_option('charts_slider_count', 5);
				$s_loop = get_option('charts_slider_loop', 1);
				$s_auto = get_option('charts_slider_autoplay', 1);
				$s_delay = get_option('charts_slider_delay', 3000);
				$s_arrows = get_option('charts_slider_arrows', 1);
				$s_dots = get_option('charts_slider_pagination', 1);
				$s_swipe = get_option('charts_slider_swipe', 1);
				$s_kb = get_option('charts_slider_keyboard', 1);

				$s_speed = get_option('charts_slider_speed', 600);
				$s_ease = get_option('charts_slider_easing', 'cubic-bezier(0.25, 1, 0.5, 1)');
				$s_center = get_option('charts_slider_center', 1);
				$s_depth = get_option('charts_slider_depth', 150);
				$s_rot = get_option('charts_slider_rotation', 45);
				$s_opac = get_option('charts_slider_opacity', 0.6);
				$s_scale = get_option('charts_slider_scale', 0.8);
				$s_space = get_option('charts_slider_spacing', 50);
				$s_shadow = get_option('charts_slider_shadow', 0.3);
				$s_glow = get_option('charts_slider_glow', 1);

				$s_max = get_option('charts_slider_max_width', '1440px');
				$s_min = get_option('charts_slider_min_height', '500px');
				$s_ratio = get_option('charts_slider_aspect_ratio', '16/9');
				$s_align = get_option('charts_slider_align', 'center');
				$s_over = get_option('charts_slider_overlay', 0.5);
				$s_rad = get_option('charts_slider_radius', '16px');
				$s_mob = get_option('charts_slider_mobile_mode', 'stack');

				$s_slabel = get_option('charts_slider_show_label', 1);
				$s_smeta = get_option('charts_slider_show_meta', 1);
				$s_scta = get_option('charts_slider_show_cta', 1);
				$s_cta = get_option('charts_slider_cta_text', 'VIEW CHART');
				?>

				<div class="premium-form-grid" style="margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 30px;">
					<h4 style="grid-column: 1 / -1; margin: 0 0 10px; font-size: 15px; font-weight: 800; color: #0f172a;">General Settings</h4>
					
					<div class="form-group form-group-full">
						<div class="toggle-item" style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
							<label class="switch"><input type="checkbox" name="slider_enable" value="1" <?php checked(1, $s_enable); ?>><span class="slider"></span></label>
							<label style="font-weight: 700;">Enable Homepage Slider</label>
						</div>
					</div>

					<div class="form-group">
						<label for="slider_style">Default Slider Style</label>
						<select name="slider_style" id="slider_style" class="form-control">
							<option value="coverflow" <?php selected('coverflow', $s_style); ?>>Coverflow 3D</option>
							<option value="stacked" <?php selected('stacked', $s_style); ?>>Stacked Cards</option>
							<option value="minimal" <?php selected('minimal', $s_style); ?>>Minimal Motion</option>
						</select>
						<span class="input-helper">Master architecture applied to the root shell homepage.</span>
					</div>
					
					<div class="form-group">
						<label for="slider_count">Number of Slides</label>
						<input type="number" name="slider_count" id="slider_count" class="form-control" value="<?php echo esc_attr($s_count); ?>">
					</div>

					<div class="form-group">
						<label for="slider_delay">Autoplay Delay (ms)</label>
						<input type="number" name="slider_delay" id="slider_delay" class="form-control" value="<?php echo esc_attr($s_delay); ?>">
					</div>

					<div class="form-group form-group-full">
						<div class="toggle-row" style="grid-template-columns: repeat(3, 1fr);">
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_loop" value="1" <?php checked(1, $s_loop); ?>><span class="slider"></span></label><label>Infinite Loop</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_autoplay" value="1" <?php checked(1, $s_auto); ?>><span class="slider"></span></label><label>Autoplay</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_arrows" value="1" <?php checked(1, $s_arrows); ?>><span class="slider"></span></label><label>Navigation Arrows</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_pagination" value="1" <?php checked(1, $s_dots); ?>><span class="slider"></span></label><label>Pagination Dots</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_swipe" value="1" <?php checked(1, $s_swipe); ?>><span class="slider"></span></label><label>Touch Swipe</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_keyboard" value="1" <?php checked(1, $s_kb); ?>><span class="slider"></span></label><label>Keyboard Nav</label></div>
						</div>
					</div>
				</div>

				<div class="premium-form-grid" style="margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 30px;">
					<h4 style="grid-column: 1 / -1; margin: 0 0 10px; font-size: 15px; font-weight: 800; color: #0f172a;">Motion & Physics</h4>
					
					<div class="form-group"><label>Animation Speed (ms)</label><input type="number" name="slider_speed" class="form-control" value="<?php echo esc_attr($s_speed); ?>"></div>
					<div class="form-group"><label>Easing</label><input type="text" name="slider_easing" class="form-control" value="<?php echo esc_attr($s_ease); ?>"></div>
					
					<div class="form-group"><label>Side Card Depth (Z)</label><input type="number" name="slider_depth" class="form-control" value="<?php echo esc_attr($s_depth); ?>"></div>
					<div class="form-group"><label>Rotation Angle</label><input type="number" name="slider_rotation" class="form-control" value="<?php echo esc_attr($s_rot); ?>"></div>
					
					<div class="form-group"><label>Side Opacity (0-1)</label><input type="number" step="0.1" name="slider_opacity" class="form-control" value="<?php echo esc_attr($s_opac); ?>"></div>
					<div class="form-group"><label>Side Scale (0-1)</label><input type="number" step="0.1" name="slider_scale" class="form-control" value="<?php echo esc_attr($s_scale); ?>"></div>
					
					<div class="form-group"><label>Card Spacing (px)</label><input type="number" name="slider_spacing" class="form-control" value="<?php echo esc_attr($s_space); ?>"></div>
					<div class="form-group"><label>Shadow Intensity (0-1)</label><input type="number" step="0.1" name="slider_shadow" class="form-control" value="<?php echo esc_attr($s_shadow); ?>"></div>

					<div class="form-group form-group-full">
						<div class="toggle-row" style="grid-template-columns: repeat(2, 1fr);">
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_center" value="1" <?php checked(1, $s_center); ?>><span class="slider"></span></label><label>Center Mode</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_glow" value="1" <?php checked(1, $s_glow); ?>><span class="slider"></span></label><label>Active Card Glow</label></div>
						</div>
					</div>
				</div>

				<div class="premium-form-grid" style="margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 30px;">
					<h4 style="grid-column: 1 / -1; margin: 0 0 10px; font-size: 15px; font-weight: 800; color: #0f172a;">Layout Constraints</h4>
					
					<div class="form-group"><label>Hero Max Width</label><input type="text" name="slider_max_width" class="form-control" value="<?php echo esc_attr($s_max); ?>"></div>
					<div class="form-group"><label>Hero Min Height</label><input type="text" name="slider_min_height" class="form-control" value="<?php echo esc_attr($s_min); ?>"></div>

					<div class="form-group"><label>Image Aspect Ratio</label><input type="text" name="slider_aspect_ratio" class="form-control" value="<?php echo esc_attr($s_ratio); ?>"></div>
					<div class="form-group"><label>Text Alignment</label>
						<select name="slider_align" class="form-control">
							<option value="left" <?php selected('left', $s_align); ?>>Left</option>
							<option value="center" <?php selected('center', $s_align); ?>>Center</option>
						</select>
					</div>

					<div class="form-group"><label>Border Radius</label><input type="text" name="slider_radius" class="form-control" value="<?php echo esc_attr($s_rad); ?>"></div>
					<div class="form-group"><label>Overlay Strength (0-1)</label><input type="number" step="0.1" name="slider_overlay" class="form-control" value="<?php echo esc_attr($s_over); ?>"></div>
					
					<div class="form-group form-group-full"><label>Mobile Layout Mode</label>
						<select name="slider_mobile_mode" class="form-control">
							<option value="stack" <?php selected('stack', $s_mob); ?>>Standard Flow</option>
							<option value="swipe" <?php selected('swipe', $s_mob); ?>>Horizontal Swipe / Snapping</option>
						</select>
					</div>
				</div>

				<div class="premium-form-grid">
					<h4 style="grid-column: 1 / -1; margin: 0 0 10px; font-size: 15px; font-weight: 800; color: #0f172a;">Content Injection</h4>
					
					<div class="form-group"><label>CTA Label Text</label><input type="text" name="slider_cta_text" class="form-control" value="<?php echo esc_attr($s_cta); ?>"></div>
					<div class="form-group"><label>Element Toggles</label>
						<div style="display:flex; flex-direction:column; gap:10px;">
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_show_label" value="1" <?php checked(1, $s_slabel); ?>><span class="slider"></span></label><label>Show Chart Label / Badge</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_show_meta" value="1" <?php checked(1, $s_smeta); ?>><span class="slider"></span></label><label>Show Meta / Artist Data</label></div>
							<div class="toggle-item"><label class="switch"><input type="checkbox" name="slider_show_cta" value="1" <?php checked(1, $s_scta); ?>><span class="slider"></span></label><label>Show CTA Button</label></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- TAB: MARKETS -->
		<div id="tab-markets" class="tab-content">
			<div class="premium-form-card">
				<div class="card-header">
					<h3>Managed Markets & Territories</h3>
					<p>Define the regions where your charts are active. These will appear in the Import Center.</p>
				</div>
				
				<div class="kb-repeater-wrap" id="markets-repeater">
					<div class="repeater-header" style="display:grid; grid-template-columns: 2fr 1fr 1fr 60px; gap: 15px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">
						<span>Market Name (e.g. Egypt)</span>
						<span>Code (e.g. EG)</span>
						<span>Slug (lower-case)</span>
						<span></span>
					</div>
					<div class="repeater-items" id="markets-container">
						<?php 
						$markets = get_option('charts_markets', []);
						if (!empty($markets)) :
							foreach ($markets as $idx => $market) : ?>
								<div class="repeater-item" style="display:grid; grid-template-columns: 2fr 1fr 1fr 60px; gap: 15px; margin-bottom: 10px;">
									<input type="text" name="markets[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($market['name'] ?? ''); ?>" class="form-control" placeholder="Saudi Arabia">
									<input type="text" name="markets[<?php echo $idx; ?>][code]" value="<?php echo esc_attr($market['code'] ?? ''); ?>" class="form-control" placeholder="SA">
									<input type="text" name="markets[<?php echo $idx; ?>][slug]" value="<?php echo esc_attr($market['slug'] ?? ''); ?>" class="form-control" placeholder="saudi-arabia">
									<button type="button" class="remove-row" style="background:#fee2e2; color:#ef4444; border:none; border-radius:8px; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
								</div>
							<?php endforeach;
						endif; ?>
					</div>
					<button type="button" id="add-market-btn" class="charts-btn-back" style="margin-top:20px; width:100%; border-style:dashed;">
						<span class="dashicons dashicons-plus" style="margin-right:8px;"></span> Add New Territory
					</button>
				</div>
			</div>
		</div>

		<!-- TAB: APIs -->
		<div id="tab-apis" class="tab-content">
			<!-- 4. Intelligence API Credentials -->
			<div class="premium-form-card" style="border-left: 4px solid var(--charts-primary);">
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
				</div>
			</div>
		</div>

		<!-- TAB: MAINTENANCE -->
		<div id="tab-maintenance" class="tab-content">
			<!-- 4. Asset Diagnostics -->
			<div class="premium-form-card">
				<div class="card-header">
					<h3>Asset Diagnostics & Repair</h3>
					<p>Technical auditing and binary data recovery.</p>
				</div>
				<div class="premium-form-grid">
					<div class="form-group form-group-full">
						<label>Incomplete Record Analysis</label>
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
			<div class="premium-form-card" style="margin-top:40px; border: 1px solid #fee2e2; background: #fffcfc; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.05);">
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
		</div>

		<div class="charts-admin-footer-bar" style="margin-top:60px; padding-top:30px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
			<button type="submit" class="charts-btn-create">
				Save System Configuration
			</button>
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	// Tab Switching Logic
	const switchTab = (tabId) => {
		$('.tab-link').removeClass('active');
		$('.tab-content').removeClass('active');
		
		$('[data-tab="' + tabId + '"]').addClass('active');
		$('#tab-' + tabId).addClass('active');
		
		// Update URL hash without jumping
		if (history.pushState) {
			history.pushState(null, null, '#' + tabId);
		} else {
			location.hash = '#' + tabId;
		}
	};

	// Click Handler
	$('.tab-link').on('click', function() {
		const tab = $(this).data('tab');
		switchTab(tab);
	});

	// Handle initial tab from URL hash
	const hash = window.location.hash.substring(1);
	if (hash && $('#tab-' + hash).length) {
		switchTab(hash);
	}

	// Logo Media Uploader
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

	// Danger Zone Visibility & Logic
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
	// Markets Repeater Logic
	$('#add-market-btn').on('click', function(){
		const idx = $('#markets-container .repeater-item').length;
		const html = `
			<div class="repeater-item" style="display:grid; grid-template-columns: 2fr 1fr 1fr 60px; gap: 15px; margin-bottom: 10px;">
				<input type="text" name="markets[${idx}][name]" class="form-control" placeholder="Country Name">
				<input type="text" name="markets[${idx}][code]" class="form-control" placeholder="Code (e.g. EG)">
				<input type="text" name="markets[${idx}][slug]" class="form-control" placeholder="slug">
				<button type="button" class="remove-row" style="background:#fee2e2; color:#ef4444; border:none; border-radius:8px; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>
			</div>
		`;
		$('#markets-container').append(html);
	});

	$(document).on('click', '.remove-row', function(){
		$(this).closest('.repeater-item').remove();
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

/* Tabs Styling */
.charts-tabs-nav { display: flex; gap: 5px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 1px; }
.tab-link { padding: 12px 24px; background: transparent; border: none; font-size: 13px; font-weight: 700; color: #64748b; cursor: pointer; border-radius: 8px 8px 0 0; position: relative; transition: all 0.2s; }
.tab-link:hover { color: #0f172a; background: #f1f5f9; }
.tab-link.active { color: #0f172a; background: #fff; }
.tab-link.active:after { content: ""; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: #0f172a; }

.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeInTab 0.3s ease; }
@keyframes fadeInTab { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

.premium-form-card { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
.card-header { margin-bottom: 32px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
.card-header h3 { margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0f172a; }
.card-header p { margin: 0; font-size: 14px; color: #64748b; }

.charts-admin-header { margin-bottom: 40px; }
.charts-admin-wrap { padding: 40px; background: #f8fafc; min-height: 100vh; }
</style>
