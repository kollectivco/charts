<?php
/**
 * System Performance & Migration Hub
 * Provides tools for convergence to the Native Production Model.
 */

use Charts\Core\Migrator;

$stats = Migrator::get_migration_stats();
?>
<style>
/* BENTO UI SYSTEM */
.bento-wrap { background: #f8fafc; padding: 24px; min-height: 100vh; font-family: 'Inter', -apple-system, sans-serif; box-sizing: border-box; }
.bento-wrap * { box-sizing: border-box; }
.bento-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.bento-title-group h1 { margin: 0 0 8px 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.bento-title-group p { margin: 0; color: #64748b; font-size: 14px; }
.bento-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px; }
.bento-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.bento-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.bento-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 12px; }
.bento-kpi-value { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 8px; color: #0f172a; }
.bento-kpi-desc { font-size: 12px; color: #64748b; margin-bottom: 16px; }
.bento-progress-track { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
.bento-progress-fill { height: 100%; transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.bento-card-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
.bento-card-desc { font-size: 13px; color: #64748b; margin: 0 0 20px 0; line-height: 1.5; }
.bento-btn { appearance: none; border: none; background: #e2e8f0; color: #334155; font-weight: 600; font-size: 13px; padding: 10px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.bento-btn:hover:not(:disabled) { background: #cbd5e1; }
.bento-btn-primary { background: #6366f1; color: #fff; }
.bento-btn-primary:hover:not(:disabled) { background: #4f46e5; }
.bento-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.bento-log-panel { background: #0f172a; color: #e2e8f0; font-family: 'Fira Code', monospace; font-size: 12px; padding: 16px; border-radius: 12px; height: 240px; overflow-y: auto; display: none; margin-top: 24px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); }
.bento-log-entry { margin-bottom: 6px; line-height: 1.4; }
.bento-log-time { color: #64748b; margin-right: 8px; }
.bento-list-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
.bento-list-item:last-child { border-bottom: none; padding-bottom: 0; }
.bento-list-label { color: #475569; font-weight: 500; }
.bento-list-val { font-weight: 600; }
.bento-alert { padding: 16px; border-radius: 12px; font-size: 13px; display: flex; gap: 12px; align-items: flex-start; margin-top: 24px; }
.bento-alert-warning { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; }
.bento-alert-icon { font-size: 18px; line-height: 1; }
</style>

<div class="bento-wrap">
	<header class="bento-header">
		<div class="bento-title-group">
			<h1>⚡ Performance & Migration</h1>
			<p>Transitioning your library from Legacy SQL to Native Production Model.</p>
		</div>
	</header>

	<div class="bento-grid-4">
		<?php 
		$colors = ['chart' => '#8b5cf6', 'artist' => '#3b82f6', 'track' => '#10b981', 'video' => '#f59e0b'];
		foreach ( $stats as $type => $data ) : 
			$color = isset($colors[$type]) ? $colors[$type] : '#6366f1';
			$is_done = ($data['percent'] == 100);
			$fill_color = $is_done ? '#22c55e' : $color;
		?>
			<div class="bento-card" style="border-top: 3px solid <?php echo $fill_color; ?>;">
				<div class="bento-kpi-label"><?php echo ucfirst($type); ?> Migration</div>
				<div class="bento-kpi-value"><?php echo $data['percent']; ?>%</div>
				<div class="bento-kpi-desc"><?php printf( __( '%s of %s localized', 'charts' ), number_format($data['promoted']), number_format($data['total']) ); ?></div>
				<div class="bento-progress-track">
					<div class="bento-progress-fill" style="width: <?php echo $data['percent']; ?>%; background: <?php echo $fill_color; ?>;"></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="bento-grid-2">
		<!-- Left Col -->
		<div style="display: flex; flex-direction: column; gap: 24px;">
			<div class="bento-card">
				<h2 class="bento-card-title">Nexus Migration Engine</h2>
				<p class="bento-card-desc">Legacy records are being shadow-indexed. Use the buttons below to bulk-localize entities into the Native Production Model. This process is idempotent and safe to run multiple times.</p>
				
				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
					<h3 style="font-size: 14px; font-weight: 700; margin: 0 0 8px 0; color: #0f172a;">1. Entity Localization</h3>
					<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">Create shadow CPTs for all legacy Artists, Tracks, and Clips. This establishes the Native Production Model.</p>
					<div style="display: flex; gap: 12px; flex-wrap: wrap;">
						<button class="bento-btn bento-btn-primary migration-trigger" data-action="promote" data-type="artist">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
							Promote Artists
						</button>
						<button class="bento-btn bento-btn-primary migration-trigger" data-action="promote" data-type="track">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
							Promote Tracks
						</button>
						<button class="bento-btn bento-btn-primary migration-trigger" data-action="promote" data-type="video">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
							Promote Clips
						</button>
					</div>
				</div>

				<div id="migration-log" class="bento-log-panel">
					<div class="bento-log-entry"><span class="bento-log-time">[SYSTEM]</span> <span style="color: #22c55e;">Migration session initialized... Ready.</span></div>
				</div>
			</div>
		</div>

		<!-- Right Col -->
		<div>
			<div class="bento-card">
				<h2 class="bento-card-title">System Status</h2>
				<p class="bento-card-desc">Current operating parameters and data bridging architecture.</p>
				
				<div class="bento-list-item">
					<span class="bento-list-label">Operating Model</span>
					<span class="bento-list-val" style="color: #6366f1;">Native-First</span>
				</div>
				<div class="bento-list-item">
					<span class="bento-list-label">Data Bridge</span>
					<span class="bento-list-val" style="color: #22c55e; display: flex; align-items: center; gap: 6px;">
						<div style="width:8px; height:8px; border-radius:50%; background:#22c55e; box-shadow: 0 0 6px #22c55e;"></div>
						Active (Synchronized)
					</span>
				</div>
				<div class="bento-list-item">
					<span class="bento-list-label">Cache Layer</span>
					<span class="bento-list-val" style="color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-size: 12px;">SQL Table</span>
				</div>
				<div class="bento-list-item" style="border-bottom: none;">
					<span class="bento-list-label">Architecture</span>
					<span class="bento-list-val" style="color: #10b981;">Hybrid (High-Performance)</span>
				</div>

				<div class="bento-alert bento-alert-warning">
					<div class="bento-alert-icon">⚠️</div>
					<div>
						<strong>Notice:</strong> Native-First mode is now the primary operating layer. Legacy SQL persists as a compatibility bridge and high-bandwidth cache.
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const log = document.getElementById('migration-log');
	const triggers = document.querySelectorAll('.migration-trigger');
	
	function addToLog(msg, color = '#e2e8f0') {
		log.style.display = 'block';
		const div = document.createElement('div');
		div.className = 'bento-log-entry';
		
		const time = new Date().toLocaleTimeString([], {hour12:false, hour:'2-digit', minute:'2-digit', second:'2-digit'});
		div.innerHTML = `<span class="bento-log-time">[${time}]</span> <span style="color: ${color};">${msg}</span>`;
		
		log.appendChild(div);
		log.scrollTop = log.scrollHeight;
	}

	triggers.forEach(btn => {
		btn.addEventListener('click', function() {
			const action = this.dataset.action;
			const type = this.dataset.type;
			
			// Disable all buttons during run
			triggers.forEach(b => b.disabled = true);
			
			let label = type;
			if (type === 'video') label = 'clip';
			addToLog(`Starting batch promotion for ${label}s...`, '#38bdf8');
			
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
					addToLog(`Progress: +${res.data.count} items processed.`, '#22c55e');
					runBatchMigration(action, type, offset + res.data.count);
				} else {
					addToLog(`Migration complete for ${type}s. Synced.`, '#818cf8');
					// Re-enable buttons
					triggers.forEach(b => b.disabled = false);
					setTimeout(() => { location.reload(); }, 1500);
				}
			} else {
				addToLog('Error: ' + (res.data.message || 'Unknown error occurred'), '#ef4444');
				triggers.forEach(b => b.disabled = false);
			}
		})
		.catch(err => {
			addToLog('Network error. Check console.', '#ef4444');
			triggers.forEach(b => b.disabled = false);
		});
	}
});
</script>
