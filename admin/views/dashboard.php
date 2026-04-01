<?php
/**
 * Premium Charts Dashboard — Light Mode Bento (Expanded v1.6.6)
 */
?>
<div class="charts-admin-wrap premium-light">
	
	<!-- 1. ACTION BAR -->
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Intelligence Dashboard', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Real-time performance metrics and system health monitoring.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions" style="display:flex; gap:12px;">
			<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'import' ); ?>" class="charts-btn-create">
				<span class="dashicons dashicons-upload" style="margin-right:8px;"></span>
				<?php _e( 'New Data Import', 'charts' ); ?>
			</a>
		</div>
	</header>

	<!-- 2. PRIMARY KPI ROW (6-COLUMN BENTO) -->
	<div class="charts-bento-grid" style="grid-template-columns: repeat(4, 1fr);">
		<div class="kpi-card">
			<div class="kpi-label"><?php _e( 'Tracks in Library', 'charts' ); ?></div>
			<div class="kpi-value"><?php echo number_format( $tracks ); ?></div>
			<div style="font-size:11px; margin-top:8px; color:var(--charts-text-dim);">
				<span style="font-weight:700; color:var(--charts-accent);"><?php echo number_format( $albums ); ?></span> 
				<?php _e( 'albums indexed', 'charts' ); ?>
			</div>
		</div>
		<div class="kpi-card">
			<div class="kpi-label"><?php _e( 'Verified Artists', 'charts' ); ?></div>
			<div class="kpi-value"><?php echo number_format( $artists ); ?></div>
			<div style="font-size:11px; margin-top:8px; color:var(--charts-text-dim);">
				<span style="font-weight:700; color:var(--charts-success);"><?php echo number_format( $sources_active ); ?></span> 
				<?php _e( 'active data sources', 'charts' ); ?>
			</div>
		</div>
		<div class="kpi-card">
			<div class="kpi-label"><?php _e( 'Chart Definitions', 'charts' ); ?></div>
			<div class="kpi-value"><?php echo number_format( $charts_total ); ?></div>
			<div style="font-size:11px; margin-top:8px; display:flex; gap:12px;">
				<span style="color:var(--charts-success); font-weight:700;">● <?php echo (int) $charts_published; ?> Public</span>
				<span style="color:var(--charts-text-dim); font-weight:700;">○ <?php echo (int) $charts_draft; ?> Draft</span>
			</div>
		</div>
		<div class="kpi-card" style="<?php echo $pending > 0 ? 'border-color: var(--charts-error); background: #fffafb;' : 'border-color: var(--charts-success);'; ?>">
			<div class="kpi-label"><?php _e( 'Data Purity Health', 'charts' ); ?></div>
			<div class="kpi-value" style="<?php echo $pending > 0 ? 'color: var(--charts-error);' : 'color: var(--charts-success);'; ?>">
				<?php echo $pending > 0 ? number_format( $pending ) : '100%'; ?>
			</div>
			<div style="font-size:11px; margin-top:8px; color:var(--charts-text-dim);">
				<?php if ( $pending > 0 ) : ?>
					<span style="font-weight:700; color:var(--charts-error);"><?php _e( 'Action Required:', 'charts' ); ?></span> 
					<?php _e( 'Unmatched entities', 'charts' ); ?>
				<?php else : ?>
					<span style="font-weight:700; color:var(--charts-success);"><?php _e( 'Optimized:', 'charts' ); ?></span> 
					<?php _e( 'All records linked', 'charts' ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- 3. MAIN ACTIVITY HUB -->
	<div class="charts-table-card" style="margin-top:20px;">
		<header class="table-header">
			<h2 class="table-title"><?php _e( 'Intelligence Pipeline Activity', 'charts' ); ?></h2>
			<div style="display:flex; gap:16px; align-items:center;">
				<div style="font-size:11px; color:var(--charts-text-dim); font-weight:700;">
					<?php _e( 'Last 5 synchronization runs', 'charts' ); ?>
				</div>
				<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'imports' ); ?>" style="font-size:12px; font-weight:700; color:var(--charts-accent); text-decoration:none;">
					<?php _e( 'Logs', 'charts' ); ?> &rarr;
				</a>
			</div>
		</header>
		
		<table class="charts-table">
			<thead>
				<tr>
					<th><?php _e( 'Integration Source', 'charts' ); ?></th>
					<th><?php _e( 'Pipeline Status', 'charts' ); ?></th>
					<th><?php _e( 'Volume', 'charts' ); ?></th>
					<th><?php _e( 'Efficiency', 'charts' ); ?></th>
					<th><?php _e( 'Terminal Timestamp', 'charts' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $imports ) ) : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 60px 20px; color: var(--charts-text-dim);">
							<div class="dashicons dashicons-database" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.15;"></div>
							<div style="font-weight: 600; font-size:15px;"><?php _e( 'The pipeline is currently empty.', 'charts' ); ?></div>
							<p style="margin:8px 0 0; font-size:13px;"><?php _e( 'Initiate a CSV or API import to populate the dashboard.', 'charts' ); ?></p>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $imports as $run ) : 
						$status_class = ( $run->status === 'completed' ) ? 'status-success' : ( ( $run->status === 'failed' ) ? 'status-error' : 'status-pending' );
					?>
						<tr>
							<td>
								<div style="font-weight:800; color:var(--charts-primary);"><?php echo esc_html( $run->source_name ); ?></div>
								<div style="font-size:10px; color:var(--charts-text-dim); text-transform:uppercase; margin-top:2px;"><?php echo esc_html( $run->run_type ); ?></div>
							</td>
							<td><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html( $run->status ); ?></span></td>
							<td>
								<div style="font-weight:700; color:var(--charts-primary);"><?php echo number_format( $run->parsed_rows ); ?> rows</div>
								<div style="font-size:10px; color:var(--charts-text-dim);"><?php echo (int)$run->created_items; ?> entities created</div>
							</td>
							<td style="color: var(--charts-text-dim); font-size:12px; font-weight:600;">
								<?php 
								if ($run->status === 'completed' && $run->started_at && $run->finished_at) {
									$diff = strtotime($run->finished_at) - strtotime($run->started_at);
									echo sprintf( __( '%.2fs', 'charts' ), $diff );
								} else {
									echo '—';
								}
								?>
							</td>
							<td style="color: var(--charts-text-dim); font-weight:500;">
								<?php echo date('M j, Y — H:i', strtotime($run->started_at)); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- 4. OPERATIONAL WORKFLOWS -->
	<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:40px;">
		<a href="<?php echo \Charts\Core\Router::get_dashboard_url('matching'); ?>" class="charts-bento-card" style="text-decoration:none;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
				<div style="width:40px; height:40px; background:#f1f5f9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--charts-accent);">
					<span class="dashicons dashicons-admin-users"></span>
				</div>
				<span class="dashicons dashicons-arrow-right-alt2" style="color:var(--charts-text-dim);"></span>
			</div>
			<div style="font-weight:850; font-size:16px; margin-bottom:6px; color:var(--charts-primary);"><?php _e( 'Entity Matching', 'charts' ); ?></div>
			<div style="font-size:12px; color:var(--charts-text-dim); line-height:1.6;">Automate or manually resolve data ingestions to canonical artist and track records.</div>
		</a>
		
		<a href="<?php echo \Charts\Core\Router::get_dashboard_url('intelligence'); ?>" class="charts-bento-card" style="text-decoration:none;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
				<div style="width:40px; height:40px; background:#f5f3ff; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--charts-accent-purple);">
					<span class="dashicons dashicons-chart-line"></span>
				</div>
				<span class="dashicons dashicons-arrow-right-alt2" style="color:var(--charts-text-dim);"></span>
			</div>
			<div style="font-weight:850; font-size:16px; margin-bottom:6px; color:var(--charts-primary);"><?php _e( 'Market Intelligence', 'charts' ); ?></div>
			<div style="font-size:12px; color:var(--charts-text-dim); line-height:1.6;">View AI-driven momentum scores, trend vectors, and cross-platform performance.</div>
		</a>

		<a href="<?php echo \Charts\Core\Router::get_dashboard_url('settings'); ?>" class="charts-bento-card" style="text-decoration:none;">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
				<div style="width:40px; height:40px; background:#fefce8; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#854d0e;">
					<span class="dashicons dashicons-admin-generic"></span>
				</div>
				<span class="dashicons dashicons-arrow-right-alt2" style="color:var(--charts-text-dim);"></span>
			</div>
			<div style="font-weight:850; font-size:16px; margin-bottom:6px; color:var(--charts-primary);"><?php _e( 'Global Configuration', 'charts' ); ?></div>
			<div style="font-size:12px; color:var(--charts-text-dim); line-height:1.6;">Manage branding, cinematic shell settings, API credentials, and navigation.</div>
		</a>
	</div>

</div>
