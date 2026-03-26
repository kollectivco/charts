<?php
/**
 * Dashboard View
 */
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Charts Dashboard', 'charts' ); ?></h1>
			<p class="subtitle"><?php _e( 'Global Chart Intelligence Engine', 'charts' ); ?></p>
		</div>
		<div class="charts-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-imports' ); ?>" class="charts-btn charts-btn-primary">
				<?php _e( 'Run Manual Import', 'charts' ); ?>
			</a>
		</div>
	</header>

	<div class="charts-grid">
		<!-- Stats Cards -->
		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Total Sources', 'charts' ); ?></div>
			<div class="charts-stat-value">9</div>
			<div class="charts-stat-label"><?php _e( 'Spotify & YouTube', 'charts' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Latest Runs', 'charts' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'In the last 24 hours', 'charts' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Pending Reviews', 'charts' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'Unmatched entities', 'charts' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Total Tracks', 'charts' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'Unique tracks in database', 'charts' ); ?></div>
		</div>

		<!-- Latest Run Logs -->
		<div class="charts-card" style="grid-column: span 8;">
			<div class="charts-card-title"><?php _e( 'Latest Import Runs', 'charts' ); ?></div>
			<table class="charts-table">
				<thead>
					<tr>
						<th><?php _e( 'Source', 'charts' ); ?></th>
						<th><?php _e( 'Status', 'charts' ); ?></th>
						<th><?php _e( 'Fetched', 'charts' ); ?></th>
						<th><?php _e( 'Parsed', 'charts' ); ?></th>
						<th><?php _e( 'Runtime', 'charts' ); ?></th>
						<th><?php _e( 'Started', 'charts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="6" style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
							<?php _e( 'No import runs recorded yet.', 'charts' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Insights -->
		<div class="charts-card" style="grid-column: span 4;">
			<div class="charts-card-title"><?php _e( 'Market Insights', 'charts' ); ?></div>
			<div style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
				<?php _e( 'Insights will appear here once data starts flowing.', 'charts' ); ?>
			</div>
		</div>
	</div>
</div>
