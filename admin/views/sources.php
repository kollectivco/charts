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
				<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'sources', array( 'action' => 'add' ) ); ?>" class="charts-btn charts-btn-primary"><?php _e( 'Add New Source', 'charts' ); ?></a>
			<?php else : ?>
				<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'sources' ); ?>" class="charts-btn charts-btn-secondary"><?php _e( 'Back to List', 'charts' ); ?></a>
			<?php endif; ?>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<div class="charts-bento-grid" style="grid-template-columns: 1fr;">
		<?php if ( $action === 'list' ) : ?>
			<div class="charts-table-card">
				<header class="table-header">
					<h2 class="table-title"><?php _e( 'Active Ingestion Registry', 'charts' ); ?></h2>
					<div style="font-size:11px; color:var(--charts-text-dim); font-weight:700;">
						<?php printf( __( '%d sources configured', 'charts' ), count( $sources ) ); ?>
					</div>
				</header>
				<table class="charts-table">
					<thead>
						<tr>
							<th><?php _e( 'Source Name', 'charts' ); ?></th>
							<th><?php _e( 'Intelligence Profile', 'charts' ); ?></th>
							<th><?php _e( 'Market', 'charts' ); ?></th>
							<th><?php _e( 'Status', 'charts' ); ?></th>
							<th><?php _e( 'Ingestion Health', 'charts' ); ?></th>
							<th style="text-align: right; padding-right: 24px;"><?php _e( 'Operations', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $sources ) ) : ?>
							<tr>
								<td colspan="6" style="text-align: center; padding: 60px 20px;">
									<div class="dashicons dashicons-database" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.15; color: var(--charts-text-dim);"></div>
									<div style="font-weight: 600; font-size:15px; color: var(--charts-text-dim);"><?php _e( 'No data sources defined.', 'charts' ); ?></div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $sources as $source ) : 
								$status_class = ($source->is_active) ? 'status-success' : 'status-error';
							?>
								<tr data-source-id="<?php echo esc_attr( $source->id ); ?>">
									<td>
										<div style="font-weight: 800; color: var(--charts-primary);"><?php echo esc_html( $source->source_name ); ?></div>
										<div style="font-size: 10px; font-weight: 600; color: var(--charts-text-dim); margin-top: 2px; text-transform: uppercase;">
											<?php echo esc_html( $source->platform ); ?> — <?php echo str_replace('_', ' ', esc_html( $source->source_type ) ); ?>
										</div>
									</td>
									<td>
										<div style="font-weight:600; font-size:13px;"><?php echo strtoupper( esc_html( $source->frequency ) ); ?></div>
										<div style="font-size:10px; color:var(--charts-text-dim);"><?php echo esc_html( $source->chart_type ); ?></div>
									</td>
									<td>
										<span class="charts-badge charts-badge-neutral"><?php echo strtoupper( esc_html( $source->country_code ) ); ?></span>
									</td>
									<td>
										<span class="status-badge <?php echo $status_class; ?>">
											<?php echo ($source->is_active) ? 'ACTIVE' : 'DISABLED'; ?>
										</span>
									</td>
									<td>
										<div style="font-size: 12px; font-weight: 600; color: var(--charts-primary);"><?php echo $source->last_run_at ? date('M j, H:i', strtotime($source->last_run_at)) : '–'; ?></div>
										<div style="font-size:10px; color:var(--charts-text-dim);"><?php _e( 'Last sync attempt', 'charts' ); ?></div>
									</td>
									<td style="text-align: right; padding-right: 24px;">
										<div style="display: flex; gap: 8px; justify-content: flex-end;">
											<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'sources', array( 'action' => 'edit', 'id' => $source->id ) ); ?>" class="charts-badge charts-badge-neutral" style="text-decoration: none;"><?php _e( 'Configure', 'charts' ); ?></a>
											<form method="post" action="" onsubmit="return confirm('Delete this source?');" style="display:inline;">
												<?php wp_nonce_field('charts_admin_action'); ?>
												<input type="hidden" name="charts_action" value="delete_source">
												<input type="hidden" name="id" value="<?php echo $source->id; ?>">
												<button type="submit" class="charts-badge charts-badge-danger" style="border:none; cursor:pointer;"><?php _e( 'Remove', 'charts' ); ?></button>
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
				<form method="post" action="<?php echo \Charts\Core\Router::get_dashboard_url('sources'); ?>">
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
						<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'sources' ); ?>" class="button button-secondary button-large"><?php _e( 'Cancel', 'charts' ); ?></a>
					</p>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
