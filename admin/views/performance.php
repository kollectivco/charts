<?php
/**
 * System Performance & Migration Hub
 * Provides tools for convergence to the Native Production Model.
 */

use Charts\Core\Migrator;

$stats = Migrator::get_migration_stats();
?>

<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Performance & Migration', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Transitioning your library from Legacy SQL to Native Production Model.', 'charts' ); ?></p>
		</div>
	</header>

	<div class="kc-cards-grid" style="margin-top: 24px;">
		<?php foreach ( $stats as $type => $data ) : ?>
			<div class="kc-card" style="position: relative; overflow: hidden;">
				<div class="kc-label"><?php echo ucfirst($type); ?> Migration</div>
				<div class="kc-value"><?php echo $data['percent']; ?>%</div>
				<p style="font-size: 12px; color: #666; margin-top: 5px;">
					<?php printf( __( '%d of %d localized', 'charts' ), $data['promoted'], $data['total'] ); ?>
				</p>
				<div style="height: 4px; background: #eee; border-radius: 2px; margin-top: 15px;">
					<div style="height: 100%; width: <?php echo $data['percent']; ?>%; background: <?php echo $data['percent'] == 100 ? '#22c55e' : '#6366f1'; ?>; transition: width 0.5s;"></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="charts-grid" style="margin-top: 24px;">
		<div class="charts-card" style="grid-column: span 8;">
			<h2 style="margin-top:0;"><?php _e( 'Nexus Migration Engine', 'charts' ); ?></h2>
			<p style="color: #666; font-size: 14px;">
				<?php _e( 'Legacy records are being shadow-indexed. Use the buttons below to bulk-localize entities into the Native Production Model. This process is idempotent and safe to run multiple times.', 'charts' ); ?>
			</p>

			<div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
				<div style="padding: 20px; border: 1px solid #eee; border-radius: 12px; background: #fafafa;">
					<h3 style="margin-top: 0; font-size: 16px;"><?php _e( '1. Entity Localization', 'charts' ); ?></h3>
					<p style="font-size: 13px; color: #666;"><?php _e( 'Create shadow CPTs for all legacy Artists, Tracks, and Clips. This establishes the Native Production Model.', 'charts' ); ?></p>
					<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
						<button class="charts-btn-primary migration-trigger" data-action="promote" data-type="artist">Promote Artists</button>
						<button class="charts-btn-primary migration-trigger" data-action="promote" data-type="track">Promote Tracks</button>
						<button class="charts-btn-primary migration-trigger" data-action="promote" data-type="video">Promote Clips</button>
					</div>
				</div>

				<div style="padding: 20px; border: 1px solid #eee; border-radius: 12px; background: #fafafa;">
					<h3 style="margin-top: 0; font-size: 16px;"><?php _e( '2. Data Architecture', 'charts' ); ?></h3>
					<p style="font-size: 13px; color: #666;"><?php _e( 'Analytics data remains in high-performance SQL tables while frontend profiles are served from native CPTs.', 'charts' ); ?></p>
					<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
						<div style="font-weight: 700; color: #22c55e;">System Synchronized</div>
					</div>
				</div>
			</div>

			<!-- Migration Log -->
			<div id="migration-log" style="margin-top: 30px; background: #1e293b; color: #cbd5e1; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 12px; height: 200px; overflow-y: auto; display: none;">
				<div style="color: #22c55e;">[SYSTEM] Migration session initialized...</div>
			</div>
		</div>

		<div class="charts-card" style="grid-column: span 4;">
			<h2 style="margin-top:0;"><?php _e( 'System Status', 'charts' ); ?></h2>
			<div style="padding: 15px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
				<span><?php _e( 'Operating Model', 'charts' ); ?></span>
				<b style="color: #6366f1;"><?php _e( 'Native-First', 'charts' ); ?></b>
			</div>
			<div style="padding: 15px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
				<span><?php _e( 'Data Bridge', 'charts' ); ?></span>
				<b style="color: #22c55e;"><?php _e( 'Active (Synchronized)', 'charts' ); ?></b>
			</div>
			<div style="padding: 15px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
				<span><?php _e( 'Cache Layer', 'charts' ); ?></span>
				<b style="color: #666;"><?php _ex( 'SQL Table', 'cache layer', 'charts' ); ?></b>
			</div>
			
			<div style="margin-top: 20px; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; font-size: 13px; color: #92400e;">
				<span class="dashicons dashicons-warning" style="margin-right: 5px; vertical-align: middle;"></span>
				<?php _e( 'Native-First mode is now the primary operating layer. Legacy SQL persists as a compatibility bridge and high-bandwidth cache.', 'charts' ); ?>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const log = document.getElementById('migration-log');
	const triggers = document.querySelectorAll('.migration-trigger');
	
	function addToLog(msg, color = '#cbd5e1') {
		log.style.display = 'block';
		const div = document.createElement('div');
		div.style.color = color;
		div.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
		log.appendChild(div);
		log.scrollTop = log.scrollHeight;
	}

	triggers.forEach(btn => {
		btn.addEventListener('click', function() {
			const action = this.dataset.action;
			const type = this.dataset.type;
			
			this.disabled = true;
			addToLog(`Starting ${action} for ${type}...`);
			
			runBatchMigration(action, type, 0);
		});
	});

	function runBatchMigration(action, type, offset) {
		const formData = new FormData();
		formData.append('action', 'charts_migration_step');
		formData.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
		formData.append('migration_action', action);
		formData.append('entity_type', type);
		
		fetch(ajaxurl, { method: 'POST', body: formData })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				if (res.data.count > 0) {
					addToLog(`Progress: ${res.data.count} items processed.`, '#22c55e');
					runBatchMigration(action, type, offset + res.data.count);
				} else {
					addToLog(`Migration complete for ${type}.`, '#6366f1');
					location.reload(); // Refresh to update states
				}
			} else {
				addToLog('Error: ' + res.data.message, '#ef4444');
			}
		});
	}
});
</script>
