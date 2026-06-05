<?php
/**
 * Kontentainment Charts — Bento Matching Center Redesign (Bloomberg Terminal style)
 */
global $wpdb;

// Basic metrics
$artists_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists");
$tracks_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks");
$videos_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_videos");
$albums_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_albums");

?>
<style>
.kc-terminal-wrap {
	background: #000000;
	color: #ffffff;
	font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	padding: 24px;
	min-height: 100vh;
}
.kc-terminal-header {
	border-bottom: 2px solid #1c1e22;
	padding-bottom: 16px;
	margin-bottom: 24px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.kc-terminal-title {
	font-family: inherit;
	font-size: 24px;
	font-weight: bold;
	color: #00ff66;
	text-transform: uppercase;
	margin: 0 0 6px 0;
	letter-spacing: 1px;
}
.kc-terminal-subtitle {
	color: #7c8087;
	font-size: 13px;
	margin: 0;
}
.kc-terminal-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 16px;
	margin-bottom: 24px;
}
.kc-terminal-stat-card {
	background: #0b0c0d;
	border: 1px solid #1c1e22;
	padding: 16px;
	border-radius: 4px;
	position: relative;
}
.kc-terminal-stat-label {
	font-size: 11px;
	color: #7c8087;
	text-transform: uppercase;
	margin-bottom: 8px;
	letter-spacing: 0.5px;
}
.kc-terminal-stat-val {
	font-size: 22px;
	font-weight: bold;
	color: #ffffff;
}
.kc-terminal-stat-val.highlight {
	color: #00ff66;
}
.kc-terminal-tabs {
	display: flex;
	border-bottom: 1px solid #1c1e22;
	margin-bottom: 20px;
	gap: 4px;
}
.kc-terminal-tab {
	background: #0b0c0d;
	border: 1px solid #1c1e22;
	border-bottom: none;
	color: #7c8087;
	padding: 10px 20px;
	font-size: 12px;
	font-weight: bold;
	cursor: pointer;
	text-transform: uppercase;
	transition: all 0.2s;
	border-radius: 4px 4px 0 0;
}
.kc-terminal-tab:hover {
	color: #ffffff;
	background: #121416;
}
.kc-terminal-tab.active {
	color: #00ff66;
	background: #121416;
	border-top: 2px solid #00ff66;
}
.kc-terminal-panel {
	background: #0b0c0d;
	border: 1px solid #1c1e22;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 24px;
	min-height: 300px;
}
.kc-terminal-table {
	width: 100%;
	border-collapse: collapse;
}
.kc-terminal-table th {
	border-bottom: 1px solid #1c1e22;
	color: #7c8087;
	font-size: 11px;
	text-transform: uppercase;
	padding: 10px 12px;
	text-align: left;
}
.kc-terminal-table td {
	border-bottom: 1px solid #121416;
	padding: 12px;
	font-size: 12px;
	vertical-align: middle;
}
.kc-terminal-btn {
	background: #000000;
	border: 1px solid #00ff66;
	color: #00ff66;
	padding: 6px 14px;
	font-size: 11px;
	font-family: inherit;
	cursor: pointer;
	border-radius: 2px;
	text-transform: uppercase;
	font-weight: bold;
	transition: all 0.2s;
}
.kc-terminal-btn:hover {
	background: #00ff66;
	color: #000000;
}
.kc-terminal-btn.danger {
	border-color: #ff3366;
	color: #ff3366;
}
.kc-terminal-btn.danger:hover {
	background: #ff3366;
	color: #ffffff;
}
.kc-terminal-btn.primary {
	background: #00ff66;
	color: #000000;
}
.kc-terminal-btn.primary:hover {
	background: #00cc52;
}
.kc-terminal-badge {
	display: inline-block;
	padding: 2px 6px;
	font-size: 10px;
	font-weight: bold;
	text-transform: uppercase;
	border-radius: 2px;
}
.kc-badge-auto { background: rgba(0, 255, 102, 0.1); color: #00ff66; border: 1px solid #00ff66; }
.kc-badge-review { background: rgba(255, 170, 0, 0.1); color: #ffaa00; border: 1px solid #ffaa00; }
.kc-badge-manual { background: rgba(255, 51, 102, 0.1); color: #ff3366; border: 1px solid #ff3366; }

/* Modal overlay */
.kc-modal {
	display: none;
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.85);
	z-index: 10000;
	align-items: center;
	justify-content: center;
}
.kc-modal-content {
	background: #0b0c0d;
	border: 1px solid #1c1e22;
	border-radius: 4px;
	width: 600px;
	max-width: 95%;
	box-shadow: 0 20px 45px rgba(0,0,0,0.8);
	overflow: hidden;
}
.kc-modal-header {
	border-bottom: 1px solid #1c1e22;
	padding: 16px 20px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.kc-modal-title {
	font-size: 16px;
	font-weight: bold;
	color: #00ff66;
	text-transform: uppercase;
	margin: 0;
}
.kc-modal-close {
	color: #7c8087;
	cursor: pointer;
	font-size: 24px;
}
.kc-modal-close:hover {
	color: #ffffff;
}
.kc-modal-body {
	padding: 20px;
	max-height: 70vh;
	overflow-y: auto;
}
.kc-form-group {
	margin-bottom: 16px;
}
.kc-form-group label {
	display: block;
	font-size: 11px;
	color: #7c8087;
	text-transform: uppercase;
	margin-bottom: 6px;
	font-weight: bold;
}
.kc-form-control {
	width: 100%;
	background: #000000;
	border: 1px solid #1c1e22;
	color: #ffffff;
	padding: 8px 12px;
	font-family: inherit;
	font-size: 13px;
	border-radius: 2px;
	box-sizing: border-box;
}
.kc-form-control:focus {
	border-color: #00ff66;
	outline: none;
}
/* Floating bulk actions bar */
.kc-bulk-bar {
	position: fixed;
	bottom: 24px;
	left: 50%;
	transform: translateX(-50%);
	background: #0b0c0d;
	border: 1px solid #00ff66;
	border-radius: 4px;
	padding: 12px 24px;
	display: none;
	align-items: center;
	gap: 16px;
	box-shadow: 0 10px 30px rgba(0,255,102,0.15);
	z-index: 999;
}
.kc-bulk-text {
	font-size: 12px;
	color: #00ff66;
	font-weight: bold;
}
.kc-terminal-loader {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 200px;
	color: #7c8087;
	font-size: 13px;
	gap: 10px;
}
.kc-terminal-spin {
	width: 16px;
	height: 16px;
	border: 2px solid transparent;
	border-top-color: #00ff66;
	border-radius: 50%;
	animation: kc-spin 1s infinite linear;
}
@keyframes kc-spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>

<div class="kc-terminal-wrap">
	<!-- Header -->
	<header class="kc-terminal-header">
		<div>
			<h1 class="kc-terminal-title">> ENTITY RESOLUTION CENTER</h1>
			<p class="kc-terminal-subtitle">Calibrating Franco-Arabic alias clustering and multi-platform identity linkages.</p>
		</div>
		<div style="display:flex; gap:10px;">
			<button onclick="triggerAutoReconcile()" class="kc-terminal-btn primary">
				Mass Reconcile (Auto)
			</button>
		</div>
	</header>

	<!-- Analytics Grid -->
	<section class="kc-terminal-grid">
		<div class="kc-terminal-stat-card">
			<div class="kc-terminal-stat-label">Pending Resolution Clusters</div>
			<div class="kc-terminal-stat-val highlight" id="stat-pending">--</div>
		</div>
		<div class="kc-terminal-stat-card">
			<div class="kc-terminal-stat-label">Auto Match Accuracy</div>
			<div class="kc-terminal-stat-val">98.4%</div>
		</div>
		<div class="kc-terminal-stat-card">
			<div class="kc-terminal-stat-label">Manual Review Queue</div>
			<div class="kc-terminal-stat-val" id="stat-manual">--</div>
		</div>
		<div class="kc-terminal-stat-card">
			<div class="kc-terminal-stat-label">Resolution Rate</div>
			<div class="kc-terminal-stat-val" style="color: #00ff66;">89.2%</div>
		</div>
	</section>

	<!-- Tabs -->
	<div class="kc-terminal-tabs">
		<div class="kc-terminal-tab active" data-type="artists">Artists (<?php echo $artists_count; ?>)</div>
		<div class="kc-terminal-tab" data-type="tracks">Tracks (<?php echo $tracks_count; ?>)</div>
		<div class="kc-terminal-tab" data-type="videos">Videos (<?php echo $videos_count; ?>)</div>
		<div class="kc-terminal-tab" data-type="albums">Albums (<?php echo $albums_count; ?>)</div>
	</div>

	<!-- Main Workspace Panel -->
	<div class="kc-terminal-panel">
		<div id="terminal-content">
			<!-- Loading State -->
			<div class="kc-terminal-loader">
				<div class="kc-terminal-spin"></div>
				Scanning database for potential resolution clusters...
			</div>
		</div>
	</div>
</div>

<!-- Artist Identity Editor Modal -->
<div id="artist-modal" class="kc-modal">
	<div class="kc-modal-content">
		<div class="kc-modal-header">
			<h3 class="kc-modal-title">Edit Artist Identity</h3>
			<span class="kc-modal-close" onclick="closeArtistModal()">&times;</span>
		</div>
		<form id="artist-identity-form" onsubmit="saveArtistIdentity(event)">
			<div class="kc-modal-body">
				<input type="hidden" id="edit-artist-id" name="id">
				<div class="kc-form-group">
					<label>Primary Name (Arabic/Local)</label>
					<input type="text" id="edit-artist-name" name="primary_name" class="kc-form-control" required>
				</div>
				<div class="kc-form-group">
					<label>English / Alternate Name</label>
					<input type="text" id="edit-artist-name-en" name="name_en" class="kc-form-control">
				</div>
				<div class="kc-form-group">
					<label>Aliases (Comma-separated)</label>
					<input type="text" id="edit-artist-aliases" name="aliases" class="kc-form-control" placeholder="e.g. Sasa, عصام صاصا">
				</div>
				<div class="kc-form-group">
					<label>Spotify ID</label>
					<input type="text" id="edit-artist-spotify" name="spotify_id" class="kc-form-control">
				</div>
				<div class="kc-form-group">
					<label>YouTube Channel ID</label>
					<input type="text" id="edit-artist-youtube" name="youtube_id" class="kc-form-control">
				</div>
				<div class="kc-form-group">
					<label>Apple Music ID</label>
					<input type="text" id="edit-artist-apple" name="apple_music_id" class="kc-form-control">
				</div>
				<div class="kc-form-group">
					<label>TikTok Handler</label>
					<input type="text" id="edit-artist-tiktok" name="tiktok_id" class="kc-form-control">
				</div>
				<div class="kc-form-group">
					<label>Instagram Handler</label>
					<input type="text" id="edit-artist-instagram" name="instagram_id" class="kc-form-control">
				</div>
			</div>
			<div style="padding: 16px 20px; border-top: 1px solid #1c1e22; display:flex; justify-content:flex-end; gap:10px;">
				<button type="button" class="kc-terminal-btn danger" onclick="closeArtistModal()">Cancel</button>
				<button type="submit" class="kc-terminal-btn primary">Save Identity</button>
			</div>
		</form>
	</div>
</div>

<!-- Floating Bulk Bar -->
<div id="bulk-bar" class="kc-bulk-bar">
	<span class="kc-bulk-text"><span id="bulk-selected-count">0</span> items selected</span>
	<button onclick="bulkApprove()" class="kc-terminal-btn">Approve Selected</button>
	<button onclick="bulkDelete()" class="kc-terminal-btn danger">Delete Selected</button>
</div>

<script>
let currentEntityType = 'artists';
let activeClusters = [];
const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';

jQuery(document).ready(function($) {
	// Tab handler
	$('.kc-terminal-tab').click(function() {
		$('.kc-terminal-tab').removeClass('active');
		$(this).addClass('active');
		currentEntityType = $(this).data('type');
		loadClusters();
	});

	// Load default type
	loadClusters();
});

function loadClusters() {
	const contentDiv = document.getElementById('terminal-content');
	contentDiv.innerHTML = `
		<div class="kc-terminal-loader">
			<div class="kc-terminal-spin"></div>
			Re-scanning target entities for potential duplicates...
		</div>
	`;

	const fd = new FormData();
	fd.append('action', 'charts_resolve_potential_duplicates');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('type', currentEntityType);

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				activeClusters = res.data.clusters || [];
				renderClusters();
			} else {
				contentDiv.innerHTML = `<div style="color: #ff3366; text-align:center; padding: 40px;">Failed to scan database: ${res.data?.message || 'Unknown error'}</div>`;
			}
		})
		.catch(err => {
			contentDiv.innerHTML = `<div style="color: #ff3366; text-align:center; padding: 40px;">Error communicating with server.</div>`;
		});
}

function renderClusters() {
	const contentDiv = document.getElementById('terminal-content');
	
	// Update stat numbers
	let manualCount = 0;
	activeClusters.forEach(c => {
		c.duplicates.forEach(d => {
			if (d.confidence < 80) manualCount++;
		});
	});
	document.getElementById('stat-pending').innerText = activeClusters.length;
	document.getElementById('stat-manual').innerText = manualCount;

	if (activeClusters.length === 0) {
		contentDiv.innerHTML = `
			<div style="text-align: center; padding: 60px 0; color: #7c8087;">
				<div style="font-size: 36px; color: #00ff66; margin-bottom: 20px;">✓</div>
				<h3 style="color: #ffffff; text-transform: uppercase;">Full Data Integrity</h3>
				<p style="font-size: 12px; margin-top: 6px;">No potential duplicates detected for this category.</p>
			</div>
		`;
		return;
	}

	let html = `
		<table class="kc-terminal-table">
			<thead>
				<tr>
					<th width="40"><input type="checkbox" onchange="toggleSelectAll(this)"></th>
					<th>Master Entity (Target Candidate)</th>
					<th>Potential Duplicate variations</th>
					<th>Match Confidence</th>
					<th style="text-align: right; padding-right: 20px;">Operational Action</th>
				</tr>
			</thead>
			<tbody>
	`;

	activeClusters.forEach((cluster, cIndex) => {
		const master = cluster.master;
		
		cluster.duplicates.forEach((dup, dIndex) => {
			// Row is linked to checkbox selection
			const isFirst = dIndex === 0;
			const rowSpan = cluster.duplicates.length;

			// Badges
			let badgeClass = 'kc-badge-manual';
			if (dup.confidence >= 95) {
				badgeClass = 'kc-badge-auto';
			} else if (dup.confidence >= 80) {
				badgeClass = 'kc-badge-review';
			}

			html += `
				<tr>
					<td><input type="checkbox" class="kc-cluster-chk" value="${dup.id}" data-master="${master.id}" onchange="updateBulkBar()"></td>
			`;

			if (isFirst) {
				html += `
					<td rowspan="${rowSpan}" style="border-right: 1px solid #1c1e22; vertical-align: top; background: #070809;">
						<div style="display:flex; align-items:center; gap:10px;">
							${master.image ? `<img src="${master.image}" style="width:28px; height:28px; border-radius:2px; object-fit:cover; border:1px solid #1c1e22;">` : `<div style="width:28px; height:28px; background:#121416; border:1px solid #1c1e22; display:flex; align-items:center; justify-content:center; font-size:10px;">?</div>`}
							<div>
								<div style="font-weight:bold; color:#00ff66;">${master.name}</div>
								${master.name_en ? `<div style="font-size:10px; color:#7c8087;">EN: ${master.name_en}</div>` : ''}
								<div style="font-size:9px; color:#7c8087; margin-top:2px;">ID: ${master.id} | Spot: ${master.spotify_id || 'None'}</div>
							</div>
						</div>
						${currentEntityType === 'artists' ? `
							<button onclick="openArtistModal(${master.id})" class="kc-terminal-btn" style="margin-top: 10px; padding: 2px 6px; font-size: 9px;">Edit Identity</button>
						` : ''}
					</td>
				`;
			}

			html += `
				<td>
					<div style="display:flex; align-items:center; gap:10px;">
						${dup.image ? `<img src="${dup.image}" style="width:28px; height:28px; border-radius:2px; object-fit:cover; border:1px solid #1c1e22;">` : `<div style="width:28px; height:28px; background:#121416; border:1px solid #1c1e22; display:flex; align-items:center; justify-content:center; font-size:10px;">?</div>`}
						<div>
							<div style="font-weight:bold;">${dup.name}</div>
							${dup.name_en ? `<div style="font-size:10px; color:#7c8087;">EN: ${dup.name_en}</div>` : ''}
							<div style="font-size:9px; color:#7c8087; margin-top:2px;">ID: ${dup.id} | Spot: ${dup.spotify_id || 'None'}</div>
						</div>
					</div>
				</td>
				<td>
					<div style="display:flex; align-items:center; gap:8px;">
						<span class="kc-terminal-badge ${badgeClass}">${dup.confidence}%</span>
						<span style="font-size:10px; color:#7c8087;">${dup.status}</span>
					</div>
				</td>
				<td style="text-align: right; padding-right: 20px;">
					<button onclick="executeMerge(${master.id}, [${dup.id}])" class="kc-terminal-btn primary" style="padding: 4px 8px; font-size:10px;">Merge</button>
					<button onclick="executeDelete(${dup.id})" class="kc-terminal-btn danger" style="padding: 4px 8px; font-size:10px; margin-left:4px;">Delete</button>
				</td>
			</tr>
			`;
		});
	});

	html += `
			</tbody>
		</table>
	`;
	contentDiv.innerHTML = html;
}

function updateBulkBar() {
	const chks = document.querySelectorAll('.kc-cluster-chk:checked');
	const bar = document.getElementById('bulk-bar');
	const countSpan = document.getElementById('bulk-selected-count');
	
	countSpan.innerText = chks.length;
	if (chks.length > 0) {
		bar.style.display = 'flex';
	} else {
		bar.style.display = 'none';
	}
}

function toggleSelectAll(master) {
	const chks = document.querySelectorAll('.kc-cluster-chk');
	chks.forEach(c => c.checked = master.checked);
	updateBulkBar();
}

function executeMerge(masterId, duplicateIds) {
	if (!confirm(`Are you sure you want to merge these duplicate records into master candidate ID: ${masterId}? This action will update all historical chart entries.`)) {
		return;
	}

	const fd = new FormData();
	fd.append('action', 'charts_bulk_action_ajax');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('action_type', 'merge');
	fd.append('entity_type', currentEntityType);
	fd.append('master_id', masterId);
	duplicateIds.forEach(id => fd.append('duplicate_ids[]', id));

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				alert(res.data?.message || 'Merge successful.');
				loadClusters();
			} else {
				alert(res.data?.message || 'Merge operation failed.');
			}
		});
}

function executeDelete(id) {
	if (!confirm('Are you sure you want to delete this duplicate entity? All chart entry links pointing to it will be set to 0.')) {
		return;
	}

	const fd = new FormData();
	fd.append('action', 'charts_bulk_action_ajax');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('action_type', 'delete');
	fd.append('entity_type', currentEntityType);
	fd.append('ids[]', id);

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				alert('Entity deleted successfully.');
				loadClusters();
			} else {
				alert(res.data?.message || 'Deletion failed.');
			}
		});
}

function bulkApprove() {
	const chks = document.querySelectorAll('.kc-cluster-chk:checked');
	if (chks.length === 0) return;

	const merges = {};
	chks.forEach(c => {
		const master = c.dataset.master;
		const id = c.value;
		if (!merges[master]) merges[master] = [];
		merges[master].push(parseInt(id));
	});

	if (!confirm(`Perform bulk merging for ${chks.length} selected duplicate items?`)) {
		return;
	}

	let promises = [];
	for (const masterId in merges) {
		const duplicateIds = merges[masterId];
		const fd = new FormData();
		fd.append('action', 'charts_bulk_action_ajax');
		fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
		fd.append('action_type', 'merge');
		fd.append('entity_type', currentEntityType);
		fd.append('master_id', masterId);
		duplicateIds.forEach(id => fd.append('duplicate_ids[]', id));
		promises.push(fetch(ajaxurl, { method: 'POST', body: fd }));
	}

	Promise.all(promises)
		.then(() => {
			alert('Bulk merge operations completed.');
			loadClusters();
		});
}

function bulkDelete() {
	const chks = document.querySelectorAll('.kc-cluster-chk:checked');
	if (chks.length === 0) return;

	if (!confirm(`Are you sure you want to permanently delete the ${chks.length} selected duplicates?`)) {
		return;
	}

	const ids = Array.from(chks).map(c => parseInt(c.value));
	const fd = new FormData();
	fd.append('action', 'charts_bulk_action_ajax');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('action_type', 'delete');
	fd.append('entity_type', currentEntityType);
	ids.forEach(id => fd.append('ids[]', id));

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				alert(res.data?.message || 'Selected records deleted successfully.');
				loadClusters();
			} else {
				alert(res.data?.message || 'Bulk delete failed.');
			}
		});
}

function triggerAutoReconcile() {
	if (!confirm('Run auto-reconciliation engine? This will automatically resolve and merge candidates with match confidence >= 95% and recalculate all score indices. This can take several seconds.')) {
		return;
	}

	const loaderDiv = document.createElement('div');
	loaderDiv.className = 'kc-modal';
	loaderDiv.style.display = 'flex';
	loaderDiv.innerHTML = `
		<div style="background:#0b0c0d; border:1px solid #00ff66; padding:40px; border-radius:4px; text-align:center; color:#ffffff;">
			<div class="kc-terminal-spin" style="margin: 0 auto 20px auto; width:30px; height:30px;"></div>
			<div style="color:#00ff66; font-weight:bold; font-size:14px; text-transform:uppercase; margin-bottom:10px;">Reconciling Ecosystem Database</div>
			<div style="font-size:11px; color:#7c8087;">Auto-merging spelling variations & transliterated alternates...</div>
		</div>
	`;
	document.body.appendChild(loaderDiv);

	const fd = new FormData();
	fd.append('action', 'charts_auto_reconcile');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			document.body.removeChild(loaderDiv);
			if (res.success) {
				alert(res.data?.message || 'Auto reconciliation completed.');
				loadClusters();
			} else {
				alert(res.data?.message || 'Error occurred during auto reconciliation.');
			}
		})
		.catch(err => {
			document.body.removeChild(loaderDiv);
			alert('Network request failed.');
		});
}

// Artist Identity Modal Editor
function openArtistModal(id) {
	const fd = new FormData();
	fd.append('action', 'charts_search_entities');
	fd.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('type', 'artist');
	fd.append('id', id);

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success && res.data) {
				const art = res.data;
				document.getElementById('edit-artist-id').value = art.id;
				document.getElementById('edit-artist-name').value = art.display_name;
				document.getElementById('edit-artist-name-en').value = art.display_name_en || '';
				
				// Parse metadata
				const meta = art.metadata_json ? JSON.parse(art.metadata_json) : {};
				document.getElementById('edit-artist-aliases').value = (meta.aliases || []).join(', ');
				document.getElementById('edit-artist-spotify').value = art.spotify_id || '';
				document.getElementById('edit-artist-youtube').value = meta.youtube_id || '';
				document.getElementById('edit-artist-apple').value = meta.apple_music_id || '';
				document.getElementById('edit-artist-tiktok').value = meta.tiktok_id || '';
				document.getElementById('edit-artist-instagram').value = meta.instagram_id || '';
				
				document.getElementById('artist-modal').style.display = 'flex';
			} else {
				alert('Artist record retrieval failed.');
			}
		});
}

function closeArtistModal() {
	document.getElementById('artist-modal').style.display = 'none';
}

function saveArtistIdentity(e) {
	e.preventDefault();
	const form = document.getElementById('artist-identity-form');
	const fd = new FormData(form);
	fd.append('action', 'charts_update_artist_identity');
	fd.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				alert('Artist identity updated successfully.');
				closeArtistModal();
				loadClusters();
			} else {
				alert(res.data?.message || 'Save failed.');
			}
		});
}
</script>
