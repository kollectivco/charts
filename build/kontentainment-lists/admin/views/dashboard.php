<?php
/**
 * Dashboard View
 */
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Lists Dashboard', 'kontentainment-lists' ); ?></h1>
			<p class="subtitle"><?php _e( 'Global List Intelligence Engine', 'kontentainment-lists' ); ?></p>
		</div>
		<div class="charts-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-imports' ); ?>" class="charts-btn charts-btn-primary">
				<?php _e( 'Run Manual Import', 'kontentainment-lists' ); ?>
			</a>
		</div>
	</header>

	<div class="charts-grid">
		<!-- Stats Cards -->
		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Total Sources', 'kontentainment-lists' ); ?></div>
			<div class="charts-stat-value">9</div>
			<div class="charts-stat-label"><?php _e( 'Spotify & YouTube', 'kontentainment-lists' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Latest Runs', 'kontentainment-lists' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'In the last 24 hours', 'kontentainment-lists' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Pending Reviews', 'kontentainment-lists' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'Unmatched entities', 'kontentainment-lists' ); ?></div>
		</div>

		<div class="charts-card" style="grid-column: span 3;">
			<div class="charts-card-title"><?php _e( 'Total Tracks', 'kontentainment-lists' ); ?></div>
			<div class="charts-stat-value">0</div>
			<div class="charts-stat-label"><?php _e( 'Unique tracks in database', 'kontentainment-lists' ); ?></div>
		</div>

		<!-- Latest Run Logs -->
		<div class="charts-card" style="grid-column: span 8;">
			<div class="charts-card-title"><?php _e( 'Latest Import Runs', 'kontentainment-lists' ); ?></div>
			<table class="charts-table">
				<thead>
					<tr>
						<th><?php _e( 'Source', 'kontentainment-lists' ); ?></th>
						<th><?php _e( 'Status', 'kontentainment-lists' ); ?></th>
						<th><?php _e( 'Fetched', 'kontentainment-lists' ); ?></th>
						<th><?php _e( 'Parsed', 'kontentainment-lists' ); ?></th>
						<th><?php _e( 'Runtime', 'kontentainment-lists' ); ?></th>
						<th><?php _e( 'Started', 'kontentainment-lists' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="6" style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
							<?php _e( 'No import runs recorded yet.', 'kontentainment-lists' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Insights -->
		<div class="charts-card" style="grid-column: span 4;">
			<div class="charts-card-title"><?php _e( 'Market Insights', 'kontentainment-lists' ); ?></div>
			<div style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
				<?php _e( 'Insights will appear here once data starts flowing.', 'kontentainment-lists' ); ?>
			</div>
		</div>
	</div>
</div>
