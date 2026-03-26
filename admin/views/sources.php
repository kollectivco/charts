<?php
/**
 * Sources View
 */
$source_manager = new \Charts\Admin\SourceManager();
$action = $_GET['action'] ?? 'list';
$edit_source = null;

if ( $action === 'edit' && ! empty( $_GET['id'] ) ) {
	$edit_source = $source_manager->get_source( intval( $_GET['id'] ) );
}

$sources = $source_manager->get_sources();
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php echo $action === 'edit' ? __( 'Edit Source', 'charts' ) : ( $action === 'add' ? __( 'Add New Source', 'charts' ) : __( 'Data Sources', 'charts' ) ); ?></h1>
			<p class="subtitle"><?php _e( 'Manage Spotify & YouTube chart sources', 'charts' ); ?></p>
		</div>
		<div class="charts-actions">
			<?php if ( $action === 'list' ) : ?>
				<a href="<?php echo admin_url( 'admin.php?page=charts-sources&action=add' ); ?>" class="charts-btn charts-btn-primary"><?php _e( 'Add New Source', 'charts' ); ?></a>
			<?php else : ?>
				<a href="<?php echo admin_url( 'admin.php?page=charts-sources' ); ?>" class="charts-btn charts-btn-secondary"><?php _e( 'Back to List', 'charts' ); ?></a>
			<?php endif; ?>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<div class="charts-grid">
		<?php if ( $action === 'list' ) : ?>
			<div class="charts-card" style="grid-column: span 12; padding: 0; position: relative; overflow: hidden;">
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;"><?php _e( 'Source Name', 'charts' ); ?></th>
							<th><?php _e( 'Platform', 'charts' ); ?></th>
							<th><?php _e( 'Type', 'charts' ); ?></th>
							<th><?php _e( 'Country', 'charts' ); ?></th>
							<th><?php _e( 'Frequency', 'charts' ); ?></th>
							<th><?php _e( 'Status', 'charts' ); ?></th>
							<th><?php _e( 'Last Run', 'charts' ); ?></th>
							<th style="text-align: right; padding-right: 24px;"><?php _e( 'Actions', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $sources ) ) : ?>
							<tr>
								<td colspan="8" style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
									<div style="font-size: 14px; margin-bottom: 10px;"><?php _e( 'No sources found in the registry.', 'charts' ); ?></div>
									<button class="charts-btn charts-btn-secondary" onclick="location.reload();"><?php _e( 'Refresh registry', 'charts' ); ?></button>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $sources as $source ) : ?>
								<tr data-source-id="<?php echo esc_attr( $source->id ); ?>">
									<td style="padding-left: 24px;">
										<div style="font-weight: 800; color: #000;"><?php echo esc_html( $source->source_name ); ?></div>
										<div style="font-size: 11px; font-weight: 500; color: var(--charts-gray-500); margin-top: 2px;">
											<?php echo $source->source_type === 'manual_import' ? '<em>' . esc_html__('Manual CSV Import', 'charts') . '</em>' : esc_html( $source->source_url ); ?>
										</div>
									</td>
									<td>
										<span class="source-icon source-<?php echo strtolower($source->platform); ?>" style="width: 20px; height: 20px; font-size: 9px; border-radius: 4px; display: inline-flex; align-items: center; justify-self: center;">
											<?php echo substr($source->platform, 0, 1); ?>
										</span>
										<span style="font-size: 12px; font-weight: 600; text-transform: uppercase; margin-left: 6px;"><?php echo esc_html( $source->platform ); ?></span>
									</td>
									<td>
										<span class="charts-badge <?php echo $source->source_type === 'manual_import' ? 'charts-badge-neutral' : 'charts-badge-success'; ?>" style="font-size: 9px; border-radius: 20px;">
											<?php echo str_replace('_', ' ', strtoupper( esc_html( $source->source_type ) ) ); ?>
										</span>
									</td>
									<td><span style="font-weight: 800;"><?php echo strtoupper( esc_html( $source->country_code ) ); ?></span></td>
									<td><span class="charts-badge charts-badge-neutral"><?php echo strtoupper( esc_html( $source->frequency ) ); ?></span></td>
									<td>
										<span class="charts-badge charts-badge-<?php echo ($source->is_active) ? 'success' : 'neutral'; ?>">
											<?php echo ($source->is_active) ? 'ACTIVE' : 'DISABLED'; ?>
										</span>
									</td>
									<td>
										<div style="font-size: 12px; font-weight: 600; color: #000;"><?php echo $source->last_run_at ? date('M j, H:i', strtotime($source->last_run_at)) : '–'; ?></div>
									</td>
									<td style="text-align: right; padding-right: 24px;">
										<div style="display: flex; gap: 8px; justify-content: flex-end;">
											<?php if ( $source->source_type === 'live_scrape' ) : ?>
												<button class="charts-btn charts-btn-primary handle-run-import" style="padding: 6px 12px; font-size: 11px;"><?php _e( 'Fetch Now', 'charts' ); ?></button>
											<?php endif; ?>
											<a href="<?php echo admin_url( 'admin.php?page=charts-sources&action=edit&id=' . $source->id ); ?>" class="charts-btn charts-btn-secondary" style="padding: 6px 12px; font-size: 11px; text-decoration: none;"><?php _e( 'Edit', 'charts' ); ?></a>
											<form method="post" action="" onsubmit="return confirm('Delete this source?');" style="display:inline;">
												<?php wp_nonce_field('charts_admin_action'); ?>
												<input type="hidden" name="charts_action" value="delete_source">
												<input type="hidden" name="id" value="<?php echo $source->id; ?>">
												<button type="submit" class="charts-btn" style="padding: 6px 12px; font-size: 11px; color: red; border-color: rgba(255,0,0,0.1); background: transparent;"><?php _e( 'Delete', 'charts' ); ?></button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="charts-card" style="grid-column: span 12;">
				<form method="post" action="<?php echo admin_url('admin.php?page=charts-sources'); ?>">
					<?php wp_nonce_field( 'charts_admin_action' ); ?>
					<input type="hidden" name="charts_action" value="save_source">
					<?php if ( $edit_source ) : ?>
						<input type="hidden" name="id" value="<?php echo $edit_source->id; ?>">
					<?php endif; ?>

					<table class="form-table">
						<tr>
							<th scope="row"><label for="source_name"><?php _e( 'Source Name', 'charts' ); ?></label></th>
							<td><input type="text" name="source_name" id="source_name" value="<?php echo $edit_source ? esc_attr( $edit_source->source_name ) : ''; ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="platform"><?php _e( 'Platform', 'charts' ); ?></label></th>
							<td>
								<select name="platform" id="platform" required>
									<option value="spotify" <?php selected( $edit_source ? $edit_source->platform : '', 'spotify' ); ?>>Spotify</option>
									<option value="youtube" <?php selected( $edit_source ? $edit_source->platform : '', 'youtube' ); ?>>YouTube</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="source_type"><?php _e( 'Source Type', 'charts' ); ?></label></th>
							<td>
								<select name="source_type" id="source_type" required>
									<option value="live_scrape" <?php selected( $edit_source ? $edit_source->source_type : '', 'live_scrape' ); ?>>Live Scrape</option>
									<option value="manual_import" <?php selected( $edit_source ? $edit_source->source_type : '', 'manual_import' ); ?>>Manual Import (CSV)</option>
									<option value="metadata_only" <?php selected( $edit_source ? $edit_source->source_type : '', 'metadata_only' ); ?>>Metadata Only</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="source_url"><?php _e( 'Source URL', 'charts' ); ?></label></th>
							<td>
								<input type="url" name="source_url" id="source_url" value="<?php echo $edit_source ? esc_url( $edit_source->source_url ) : ''; ?>" class="regular-text" placeholder="https://...">
								<p class="description"><?php _e( 'Required for Live Scrape sources. For Spotify manual imports, this can be "manual".', 'charts' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="country_code"><?php _e( 'Country Code', 'charts' ); ?></label></th>
							<td><input type="text" name="country_code" id="country_code" value="<?php echo $edit_source ? esc_attr( $edit_source->country_code ) : 'eg'; ?>" class="small-text" maxlength="10" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="frequency"><?php _e( 'Frequency', 'charts' ); ?></label></th>
							<td>
								<select name="frequency" id="frequency" required>
									<option value="daily" <?php selected( $edit_source ? $edit_source->frequency : '', 'daily' ); ?>>Daily</option>
									<option value="weekly" <?php selected( $edit_source ? $edit_source->frequency : '', 'weekly' ); ?>>Weekly</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="chart_type"><?php _e( 'Chart Type', 'charts' ); ?></label></th>
							<td>
								<select name="chart_type" id="chart_type" required>
									<option value="top-songs" <?php selected( $edit_source ? $edit_source->chart_type : '', 'top-songs' ); ?>>Top Songs</option>
									<option value="top-artists" <?php selected( $edit_source ? $edit_source->chart_type : '', 'top-artists' ); ?>>Top Artists</option>
									<option value="top-videos" <?php selected( $edit_source ? $edit_source->chart_type : '', 'top-videos' ); ?>>Top Videos</option>
									<option value="viral-50" <?php selected( $edit_source ? $edit_source->chart_type : '', 'viral-50' ); ?>>Viral 50</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="parser_key"><?php _e( 'Parser Key', 'charts' ); ?></label></th>
							<td><input type="text" name="parser_key" id="parser_key" value="<?php echo $edit_source ? esc_attr( $edit_source->parser_key ) : ''; ?>" class="regular-text" placeholder="e.g. spotify-csv, youtube-v1"></td>
						</tr>
						<tr>
							<th scope="row"><label for="is_active"><?php _e( 'Is Active?', 'charts' ); ?></label></th>
							<td><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked( $edit_source ? $edit_source->is_active : 1 ); ?>></td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large"><?php _e( 'Save Source', 'charts' ); ?></button>
						<a href="<?php echo admin_url( 'admin.php?page=charts-sources' ); ?>" class="button button-secondary button-large"><?php _e( 'Cancel', 'charts' ); ?></a>
					</p>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
