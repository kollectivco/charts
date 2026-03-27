<?php
/**
 * Import Runs View
 */
global $wpdb;
$runs_table   = $wpdb->prefix . 'charts_import_runs';
$sources_table = $wpdb->prefix . 'charts_sources';

$runs = $wpdb->get_results( "
	SELECT r.*, s.source_name, s.platform, s.country_code, s.chart_type, s.frequency
	FROM {$runs_table} r
	LEFT JOIN {$sources_table} s ON s.id = r.source_id
	ORDER BY r.started_at DESC
	LIMIT 100
" );
?>
<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Synchronization History', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php printf( __( 'Audit trail of the last %d intelligent ingestion runs.', 'charts' ), count( $runs ) ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-import' ); ?>" class="charts-btn-create">
				<span class="dashicons dashicons-upload" style="margin-right:8px;"></span>
				<?php _e( 'Initiate New Sync', 'charts' ); ?>
			</a>
		</div>
	</header>

	<div class="charts-bento-grid" style="grid-template-columns: 1fr;">
		<div class="charts-table-card">
			<header class="table-header">
				<h2 class="table-title"><?php _e( 'Audit Pipeline History', 'charts' ); ?></h2>
				<div style="font-size:11px; color:var(--charts-text-dim); font-weight:700;">
					<?php _e( 'Real-time ingestion logs', 'charts' ); ?>
				</div>
			</header>
			<?php if ( empty( $runs ) ) : ?>
				<div style="padding: 60px; text-align: center; color: var(--charts-text-dim);">
					<div class="dashicons dashicons-database" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 24px; opacity: 0.15;"></div>
					<h3><?php _e( 'The pipeline is currently empty.', 'charts' ); ?></h3>
					<p><?php _e( 'Initiate a CSV or API import to record the first historical entry.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<table class="charts-table">
					<thead>
						<tr>
							<th><?php _e( 'Ingestion Source', 'charts' ); ?></th>
							<th><?php _e( 'Method', 'charts' ); ?></th>
							<th><?php _e( 'Status', 'charts' ); ?></th>
							<th><?php _e( 'Volume', 'charts' ); ?></th>
							<th><?php _e( 'Efficiency', 'charts' ); ?></th>
							<th><?php _e( 'Terminal Timestamp', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $runs as $run ) :
							$status_class = ( $run->status === 'completed' ) ? 'status-success' : ( ( $run->status === 'failed' ) ? 'status-error' : 'status-pending' );
						?>
							<tr>
								<td>
									<div style="font-weight: 800; color: var(--charts-primary);"><?php echo esc_html( $run->source_name ?? 'Unknown Source' ); ?></div>
									<div style="font-size: 10px; color: var(--charts-text-dim); text-transform: uppercase; margin-top: 2px;">
										<?php echo esc_html( $run->platform ?? '' ); ?> · <?php echo esc_html( $run->country_code ?? '' ); ?> · <?php echo esc_html( $run->frequency ?? '' ); ?>
									</div>
								</td>
								<td>
									<span class="charts-badge charts-badge-neutral"><?php echo esc_html( strtoupper( $run->run_type ?? 'csv' ) ); ?></span>
								</td>
								<td>
									<span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html( $run->status ?? '' ); ?></span>
								</td>
								<td>
									<div style="font-weight: 700; color: var(--charts-primary);"><?php echo number_format( $run->parsed_rows ?? 0 ); ?> <?php _e( 'rows', 'charts' ); ?></div>
									<div style="font-size:10px; color:var(--charts-text-dim);"><?php echo number_format( $run->matched_items ?? 0 ); ?> <?php _e( 'entities matched', 'charts' ); ?></div>
								</td>
								<td>
									<div style="color: var(--charts-text-dim); font-size:12px; font-weight:600;">
										<?php 
										if ($run->status === 'completed' && $run->started_at && $run->finished_at) {
											$diff = strtotime($run->finished_at) - strtotime($run->started_at);
											echo sprintf( __( '%.2fs', 'charts' ), $diff );
										} else {
											echo '—';
										}
										?>
									</div>
								</td>
								<td>
									<div style="color: var(--charts-primary); font-weight:700; font-size:13px;"><?php echo $run->started_at ? date( 'M j, H:i', strtotime( $run->started_at ) ) : '–'; ?></div>
									<div style="font-size:10px; color:var(--charts-text-dim);"><?php echo date( 'Y', strtotime( $run->started_at ) ); ?></div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
