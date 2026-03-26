<?php
/**
 * Settings View
 */
$menus = wp_get_nav_menus();
$selected_menu = get_option( 'charts_header_menu_id' );
$logo_id = get_option( 'charts_logo_id' );
$logo_url = '';
if ( $logo_id ) {
	$logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
}
?>
<div class="wrap charts-admin-wrap">
	<h1><?php esc_html_e( 'Charts Settings', 'charts' ); ?></h1>
	
	<?php settings_errors( 'charts' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">

		<!-- Standalone Layout Section -->
		<div class="charts-card">
			<section class="settings-section">
				<h2><?php esc_html_e( '獨立佈局 (Standalone Layout)', 'charts' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Configure Charts to run as an independent product with its own header, footer, and navigation.', 'charts' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Standalone Mode', 'charts' ); ?></th>
						<td>
							<label class="switch">
								<input type="checkbox" name="standalone_layout" value="1" <?php checked( 1, get_option( 'charts_standalone_layout' ) ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'If enabled, Charts pages will bypass the theme header/footer and use the plugin-controlled layout.', 'charts' ); ?></p>
						</td>
					</tr>
				</table>
			</section>
		</div>

		<!-- Header Branding Section -->
		<div class="charts-card" style="margin-top: 30px;">
			<section class="settings-section">
				<h2><?php esc_html_e( 'Header Branding', 'charts' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Custom Header', 'charts' ); ?></th>
						<td>
							<input type="checkbox" name="custom_header" value="1" <?php checked( 1, get_option( 'charts_custom_header' ) ); ?>>
						</td>
					</tr>
					
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo', 'charts' ); ?></th>
						<td>
							<div class="logo-preview-wrapper" style="margin-bottom: 10px;">
								<img id="logo-preview" src="<?php echo esc_url( $logo_url ); ?>" style="max-height: 100px; display: <?php echo $logo_url ? 'block' : 'none'; ?>;">
							</div>
							<input type="hidden" name="logo_id" id="logo_id" value="<?php echo esc_attr( $logo_id ); ?>">
							<button type="button" class="button" id="upload_logo_btn"><?php esc_html_e( 'Select Logo', 'charts' ); ?></button>
							<button type="button" class="button" id="remove_logo_btn" style="color: #d63638;"><?php esc_html_e( 'Remove', 'charts' ); ?></button>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="wordmark"><?php esc_html_e( 'Wordmark Fallback', 'charts' ); ?></label></th>
						<td>
							<input type="text" name="wordmark" id="wordmark" value="<?php echo esc_attr( get_option( 'charts_wordmark' ) ); ?>" class="regular-text" placeholder="Kontentainment">
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Logo Alt Text', 'charts' ); ?></th>
						<td>
							<input type="text" name="logo_alt" value="<?php echo esc_attr( get_option( 'charts_logo_alt' ) ); ?>" class="regular-text">
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Show/Hide Elements', 'charts' ); ?></th>
						<td>
							<label><input type="checkbox" name="show_logo" value="1" <?php checked( 1, get_option( 'charts_show_logo' ) ); ?>> <?php esc_html_e( 'Logo/Wordmark', 'charts' ); ?></label><br>
							<label><input type="checkbox" name="show_nav" value="1" <?php checked( 1, get_option( 'charts_show_nav' ) ); ?>> <?php esc_html_e( 'Navigation Menu', 'charts' ); ?></label><br>
							<label><input type="checkbox" name="show_country_selector" value="1" <?php checked( 1, get_option( 'charts_show_country_selector' ) ); ?>> <?php esc_html_e( 'Country Selector', 'charts' ); ?></label><br>
							<label><input type="checkbox" name="show_search" value="1" <?php checked( 1, get_option( 'charts_show_search' ) ); ?>> <?php esc_html_e( 'Search Icon', 'charts' ); ?></label>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="header_menu_id"><?php esc_html_e( 'Header Menu', 'charts' ); ?></label></th>
						<td>
							<select name="header_menu_id" id="header_menu_id">
								<option value="0"><?php esc_html_e( '— Select Menu —', 'charts' ); ?></option>
								<?php if ( ! empty( $menus ) ) : foreach ( $menus as $menu ) : ?>
									<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $selected_menu, $menu->term_id ); ?>>
										<?php echo esc_html( $menu->name ); ?>
									</option>
								<?php endforeach; endif; ?>
							</select>
						</td>
					</tr>
				</table>
			</section>
		</div>

		<!-- Footer Section -->
		<div class="charts-card" style="margin-top: 30px;">
			<section class="settings-section">
				<h2><?php esc_html_e( 'Footer Settings', 'charts' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Custom Footer', 'charts' ); ?></th>
						<td>
							<input type="checkbox" name="custom_footer" value="1" <?php checked( 1, get_option( 'charts_custom_footer' ) ); ?>>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="footer_description"><?php esc_html_e( 'Footer Description', 'charts' ); ?></label></th>
						<td>
							<textarea name="footer_description" id="footer_description" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'charts_footer_description' ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="footer_copyright"><?php esc_html_e( 'Copyright Text', 'charts' ); ?></label></th>
						<td>
							<input type="text" name="footer_copyright" id="footer_copyright" value="<?php echo esc_attr( get_option( 'charts_footer_copyright' ) ); ?>" class="regular-text">
						</td>
					</tr>
				</table>
			</section>
		</div>

		<!-- API Section -->
		<div class="charts-card" style="margin-top: 30px;">
			<section class="settings-section">
				<h2><?php esc_html_e( 'API Credentials', 'charts' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">Spotify Client ID</th>
						<td><input type="text" name="spotify_client_id" value="<?php echo esc_attr( get_option( 'charts_spotify_client_id' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row">YouTube API Key</th>
						<td><input type="password" name="youtube_api_key" value="<?php echo esc_attr( get_option( 'charts_youtube_api_key' ) ); ?>" class="regular-text"></td>
					</tr>
				</table>
			</section>
		</div>

		<?php submit_button( __( 'Save All Settings', 'charts' ) ); ?>
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
		});
		frame.open();
	});

	$('#remove_logo_btn').on('click', function(e){
		e.preventDefault();
		$('#logo_id').val('');
		$('#logo-preview').hide();
	});
});
</script>

<style>
.charts-admin-wrap { max-width: 1000px; margin-top: 20px; }
.charts-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
.settings-section h2 { margin-top: 0; border-bottom: 2px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 25px; font-weight: 900; }
.form-table th { font-weight: 700; width: 220px; }

/* Switch Toggle */
.switch { position: relative; display: inline-block; width: 60px; height: 34px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
input:checked + .slider { background-color: #6366f1; }
input:focus + .slider { box-shadow: 0 0 1px #6366f1; }
input:checked + .slider:before { transform: translateX(26px); }
.slider.round { border-radius: 34px; }
.slider.round:before { border-radius: 50%; }
</style>
