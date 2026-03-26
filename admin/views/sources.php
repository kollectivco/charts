<?php
/**
 * Sources View
 */
$source_manager = new \Charts\Admin\SourceManager();
$sources = $source_manager->get_sources();
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Data Sources', 'charts' ); ?></h1>
			<p class="subtitle"><?php _e( 'Manage Spotify & YouTube chart sources', 'charts' ); ?></p>
		</div>
		<div class="charts-actions">
			<button class="charts-btn charts-btn-primary"><?php _e( 'Add New Source', 'charts' ); ?></button>
		</div>
	</header>

	<div class="charts-grid">
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
									<?php if ( $source->last_error_at ) : ?>
										<div style="font-size: 10px; color: red;"><?php _e( 'Failed last run', 'charts' ); ?></div>
									<?php endif; ?>
								</td>
								<td style="text-align: right; padding-right: 24px;">
									<div style="display: flex; gap: 8px; justify-content: flex-end;">
										<?php if ( $source->source_type === 'live_scrape' ) : ?>
											<button class="charts-btn charts-btn-primary handle-run-import" style="padding: 6px 12px; font-size: 11px;"><?php _e( 'Fetch Now', 'charts' ); ?></button>
										<?php else : ?>
											<a href="<?php echo admin_url('admin.php?page=charts-spotify-import'); ?>" class="charts-btn charts-btn-primary" style="padding: 6px 12px; font-size: 11px; text-decoration: none;"><?php _e( 'Import CSV', 'charts' ); ?></a>
										<?php endif; ?>
										<button class="charts-btn charts-btn-secondary" style="padding: 6px 12px; font-size: 11px;"><?php _e( 'Edit', 'charts' ); ?></button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
