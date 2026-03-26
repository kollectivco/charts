<?php
/**
 * Settings View
 */
?>
<div class="wrap charts-admin-wrap">
	<h1><?php _class( 'Settings' ); esc_html_e( 'Charts Settings', 'charts' ); ?></h1>
	
	<?php settings_errors( 'charts' ); ?>

	<div class="charts-card">
		<form method="post" action="">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="save_settings">

			<section class="settings-section">
				<h2><?php esc_html_e( 'Spotify API Credentials', 'charts' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Used for metadata enrichment from Spotify Web API. Get these from the Spotify Developer Dashboard.', 'charts' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="spotify_client_id"><?php esc_html_e( 'Client ID', 'charts' ); ?></label>
						</th>
						<td>
							<input type="text" name="spotify_client_id" id="spotify_client_id" value="<?php echo esc_attr( get_option( 'charts_spotify_client_id' ) ); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="spotify_client_secret"><?php esc_html_e( 'Client Secret', 'charts' ); ?></label>
						</th>
						<td>
							<input type="password" name="spotify_client_secret" id="spotify_client_secret" value="<?php echo esc_attr( get_option( 'charts_spotify_client_secret' ) ); ?>" class="regular-text">
						</td>
					</tr>
				</table>
			</section>

			<?php submit_button( __( 'Save Settings', 'charts' ) ); ?>
		</form>
	</div>
</div>

<style>
.charts-admin-wrap { max-width: 1000px; margin-top: 20px; }
.charts-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
.settings-section h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
</style>
