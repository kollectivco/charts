<?php
/**
 * YouTube CSV Import View
 */
?>
<div class="wrap charts-admin-wrap">
	<h1><?php esc_html_e( 'YouTube CSV Import', 'charts' ); ?></h1>

	<?php 
	if ( empty( \Charts\Core\Settings::get( 'api.youtube_api_key' ) ) ) : 
	?>
		<div class="notice notice-warning is-dismissible" style="margin: 20px 0; border-left: 4px solid #f59e0b; background: #fffbeb;">
			<p>
				<strong><?php esc_html_e( 'Notice: Metadata Enrichment Disabled', 'charts' ); ?></strong><br>
				<?php printf( 
					__( 'You have not configured a YouTube API Key. While the import will still work using the CSV data, the system cannot fetch official titles, channel names, or high-quality artwork via the YouTube API. <a href="%s">Configure YouTube API Key &rarr;</a>', 'charts' ),
					admin_url('admin.php?page=charts-settings')
				); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php settings_errors( 'charts' ); ?>

	<div class="charts-card">
		<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="import_youtube_csv">

			<div class="import-form-grid">
				<section class="file-upload-section">
					<h2>1. <?php esc_html_e( 'Select CSV File', 'charts' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Upload your YouTube chart CSV. Any column layout is supported — headers are mapped automatically.', 'charts' ); ?></p>

					<div class="file-input-wrapper" style="margin: 20px 0;">
						<input type="file" name="youtube_csv" id="youtube_csv" accept=".csv,.tsv,.txt" required>
					</div>

					<div class="help-box" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #FF0000; border-radius: 4px; font-size: 12px;">
						<strong><?php esc_html_e( 'Recognized Column Aliases:', 'charts' ); ?></strong>
						<table style="margin-top: 8px; width: 100%; border-collapse: collapse;">
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">rank</td><td style="color: #6b7280;">rank, position, #</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">title</td><td style="color: #6b7280;">title, track_name, video_title, song, name</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">artist</td><td style="color: #6b7280;">artist, artist_name, artist_names, channel</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">views</td><td style="color: #6b7280;">views, weekly_views, view_count, streams</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">thumbnail</td><td style="color: #6b7280;">thumbnail, image, cover, cover_image</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">video_url</td><td style="color: #6b7280;">youtube_url, video_url, url, link</td></tr>
							<tr><td style="padding: 2px 0; color: #374151; font-weight: 600;">youtube_id</td><td style="color: #6b7280;">youtube_id, video_id, id, yt_id</td></tr>
						</table>
					</div>
				</section>

				<section class="meta-section">
					<h2>2. <?php esc_html_e( 'Import Options', 'charts' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row"><label for="yt_country">Country</label></th>
							<td>
								<select name="country" id="yt_country" required>
									<option value="eg">Egypt (EG)</option>
									<option value="sa">Saudi Arabia (SA)</option>
									<option value="ae">United Arab Emirates (AE)</option>
									<option value="ma">Morocco (MA)</option>
									<option value="tn">Tunisia (TN)</option>
									<option value="jo">Jordan (JO)</option>
									<option value="lb">Lebanon (LB)</option>
									<option value="global">Global</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yt_chart_type">Chart Type</label></th>
							<td>
								<select name="chart_type" id="yt_chart_type" required>
									<option value="top-songs">Top Songs (Tracks)</option>
									<option value="top-artists">Top Artists</option>
									<option value="top-videos">Top Videos</option>
								</select>
								<p class="description" style="margin-top: 6px;">
									This controls which entity type is created per row.
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yt_frequency">Frequency</label></th>
							<td>
								<select name="frequency" id="yt_frequency" required>
									<option value="weekly">Weekly</option>
									<option value="daily">Daily</option>
									<option value="monthly">Monthly</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yt_period_date">Chart Date</label></th>
							<td>
								<input type="date" name="period_date" id="yt_period_date" value="<?php echo date('Y-m-d'); ?>" required>
								<p class="description">For weekly charts, select the week's start or end date — the system aligns to Monday automatically.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yt_source_name">Display Label (Optional)</label></th>
							<td>
								<input type="text" name="source_name" id="yt_source_name" value="" placeholder="e.g. YouTube Egypt Weekly Top Songs" class="regular-text">
							</td>
						</tr>
					</table>
				</section>
			</div>

			<div class="submit-footer" style="padding-top: 20px; border-top: 1px solid #eee; margin-top: 30px;">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Import YouTube CSV', 'charts' ); ?></button>
			</div>
		</form>
	</div><!-- .charts-card -->
</div><!-- .wrap -->

<style>
.charts-admin-wrap { max-width: 1000px; margin-top: 20px; }
.charts-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,.06); }
.import-form-grid { display: grid; grid-template-columns: 380px 1fr; gap: 40px; }
.import-form-grid h2 { font-size: 1.2rem; margin-top: 0; }
.submit-footer button { min-width: 200px; }
.form-table th { width: 180px; }
</style>
