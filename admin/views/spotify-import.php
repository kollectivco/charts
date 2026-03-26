<?php
/**
 * Spotify CSV Import View
 */
?>
<div class="wrap charts-admin-wrap">
	<h1><?php esc_html_e( 'Spotify CSV Import', 'charts' ); ?></h1>
	
	<?php settings_errors( 'charts' ); ?>

	<div class="charts-card">
		<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="import_spotify_csv">

			<div class="import-form-grid">
				<section class="file-upload-section">
					<h2>1. <?php esc_html_e( 'Select CSV File', 'charts' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Upload the official Spotify chart export CSV.', 'charts' ); ?></p>
					
					<div class="file-input-wrapper" style="margin: 20px 0;">
						<input type="file" name="spotify_csv" id="spotify_csv" accept=".csv" required>
					</div>

					<div class="help-box" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #3366ff; border-radius: 4px;">
						<strong><?php esc_html_e( 'Expected Columns:', 'charts' ); ?></strong>
						<p style="font-size: 11px; margin-bottom: 0;">rank, uri, artist_names, track_name, source, peak_rank, previous_rank, weeks_on_chart, streams</p>
					</div>
				</section>

				<section class="meta-section">
					<h2>2. <?php esc_html_e( 'Import Options', 'charts' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><label for="country">Country</label></th>
							<td>
								<select name="country" id="country" required>
									<option value="eg">Egypt (EG)</option>
									<option value="sa">Saudi Arabia (SA)</option>
									<option value="ae">United Arab Emirates (AE)</option>
									<option value="ma">Morocco (MA)</option>
									<option value="global">Global</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="chart_type">Chart Type</label></th>
							<td>
								<select name="chart_type" id="chart_type" required>
									<option value="top-songs">Top Songs</option>
									<option value="top-artists">Top Artists</option>
									<option value="viral-50">Viral 50</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="frequency">Frequency</label></th>
							<td>
								<select name="frequency" id="frequency" required>
									<option value="weekly">Weekly</option>
									<option value="daily">Daily</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="period_date">Chart Date</label></th>
							<td>
								<input type="date" name="period_date" id="period_date" value="<?php echo date('Y-m-d'); ?>" required>
								<p class="description">For weekly charts, please select the end date of the week.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="source_name">Display Label (Optional)</label></th>
							<td>
								<input type="text" name="source_name" id="source_name" value="" placeholder="Leave blank for default" class="regular-text">
							</td>
						</tr>
					</table>
				</section>
			</div>

			<div class="submit-footer" style="padding-top: 20px; border-top: 1px solid #eee; margin-top: 30px;">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Import and Enrich', 'charts' ); ?></button>
			</div>
		</form>
	</div>
</div>

<style>
.charts-admin-wrap { max-width: 1000px; margin-top: 20px; }
.charts-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.06); }
.import-form-grid { display: grid; grid-template-columns: 350px 1fr; gap: 40px; }
.import-form-grid h2 { font-size: 1.2rem; margin-top: 0; }
.submit-footer button { min-width: 200px; }
.form-table th { width: 180px; }
</style>
