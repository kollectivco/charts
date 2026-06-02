<?php
/**
 * Matching Center - Manual Entity Resolution
 * Identifies and lists unmatched tracks/artists from chart entries.
 */
global $wpdb;

$unmatched_candidates = $wpdb->get_results("
	SELECT 'artist' as item_type, id, display_name as track_name, '' as artist_names, spotify_id, NULL as youtube_id 
	FROM {$wpdb->prefix}charts_artists 
	WHERE spotify_id IS NULL OR spotify_id = ''
	UNION ALL
	SELECT 'track' as item_type, id, title as track_name, '' as artist_names, spotify_id, NULL as youtube_id 
	FROM {$wpdb->prefix}charts_tracks 
	WHERE (spotify_id IS NULL OR spotify_id = '') AND (youtube_id IS NULL OR youtube_id = '')
	LIMIT 100
");

$total_unmatched = count($unmatched_candidates);
?>

<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Matching Center', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php printf( __( 'Audit and resolve %d unique entities that require intelligent reconciliation.', 'charts' ), $total_unmatched ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<form method="post" action="">
				<?php wp_nonce_field( 'charts_admin_action' ); ?>
				<input type="hidden" name="charts_action" value="run_integrity_check">
				<button type="submit" class="charts-btn-create">
					<span class="dashicons dashicons-admin-tools" style="margin-right:8px;"></span>
					<?php _e( 'Force Reconciliation', 'charts' ); ?>
				</button>
			</form>
		</div>
	</header>


	<?php if ( empty( $unmatched_candidates ) ) : ?>
		<div class="charts-table-card" style="padding: 100px; text-align: center;">
			<div class="dashicons dashicons-yes-alt" style="font-size: 64px; width: 64px; height: 64px; color: var(--charts-success); opacity:0.2;"></div>
			<h2 style="margin-top: 30px; font-weight:850; color:var(--charts-primary);"><?php _e( 'Full Data Integrity', 'charts' ); ?></h2>
			<p style="color: var(--charts-text-dim); max-width: 400px; margin: 10px auto; font-size:15px; line-height:1.6;"><?php _e( 'All chart entries are correctly matched to canonical entities. No orphaned records found in the current buffer.', 'charts' ); ?></p>
		</div>
	<?php else : ?>
		<div class="charts-bento-grid" style="grid-template-columns: 1fr;">
			<div class="charts-table-card">
				<header class="table-header">
					<h2 class="table-title"><?php _e( 'Entity Conflict Monitor', 'charts' ); ?></h2>
					<div style="font-size:11px; color:var(--charts-text-dim); font-weight:700;">
						<?php _e( 'Records requiring canonical linkage', 'charts' ); ?>
					</div>
				</header>
				<table class="charts-table">
					<thead>
						<tr>
							<th><?php _e( 'Raw Discovery Name', 'charts' ); ?></th>
							<th><?php _e( 'Entity Class', 'charts' ); ?></th>
							<th><?php _e( 'Cluster Volume', 'charts' ); ?></th>
							<th><?php _e( 'Identity Vectors', 'charts' ); ?></th>
							<th style="text-align: right; padding-right: 24px;"><?php _e( 'Operational Task', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $unmatched_candidates as $item ) : ?>
							<?php 
							$label = ( $item->item_type === 'artist' ) ? $item->artist_names : $item->track_name;
							$sublabel = ( $item->item_type === 'track' ) ? $item->artist_names : '';
							?>
							<tr>
								<td>
									<div style="font-weight: 800; color: var(--charts-primary);"><?php echo esc_html( $label ); ?></div>
									<?php if ( $sublabel ) : ?>
										<div style="font-size: 10px; color: var(--charts-text-dim); text-transform:uppercase; margin-top:2px;"><?php echo esc_html( $sublabel ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<span class="charts-badge charts-badge-neutral"><?php echo strtoupper( $item->item_type ); ?></span>
								</td>
								<td>
									<div style="font-weight:700; color:var(--charts-primary);"><?php _e( 'Orphaned', 'charts' ); ?></div>
									<div style="font-size:10px; color:var(--charts-text-dim);"><?php _e( 'Needs Sync', 'charts' ); ?></div>
								</td>
								<td>
									<div style="display:flex; gap:10px; align-items:center;">
										<input type="text" id="manual_id_<?php echo esc_attr($item->item_type); ?>_<?php echo esc_attr($item->id); ?>" placeholder="Paste Spotify ID..." style="width: 150px; font-size: 11px; padding: 4px 8px; border: 1px solid var(--charts-border);">
										<button onclick="saveManualId('<?php echo esc_attr($item->item_type); ?>', <?php echo esc_attr($item->id); ?>)" class="charts-btn" style="padding: 4px 10px; font-size: 11px; background:var(--charts-surface); color:var(--charts-text-dim); border:1px solid var(--charts-border); cursor:pointer;">Save</button>
									</div>
								</td>
								<td style="text-align: right; padding-right: 24px;">
									<button onclick="openSmartMatchModal('<?php echo esc_attr($item->item_type); ?>', <?php echo esc_attr($item->id); ?>, '<?php echo esc_attr($label); ?>')" class="charts-btn-create" style="padding: 4px 12px; font-size: 12px; background:#1DB954; border-color:#1DB954;">
										<span class="dashicons dashicons-search" style="font-size:14px; margin-top:2px;"></span> <?php _e( 'Auto-Find', 'charts' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Smart Match Modal -->
<div id="smart-match-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
	<div style="background: var(--charts-surface); width: 600px; max-width: 90%; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
		<div style="padding: 20px; border-bottom: 1px solid var(--charts-border); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-weight: 800; color: var(--charts-primary);">Spotify Smart Search</h3>
			<span onclick="document.getElementById('smart-match-modal').style.display='none'" style="cursor: pointer; opacity: 0.5; font-size: 20px;">&times;</span>
		</div>
		<div style="padding: 20px; text-align:center;">
			<div style="font-size: 12px; color: var(--charts-text-dim); margin-bottom:10px;">Searching for: <strong id="smart-match-query" style="color:var(--charts-primary);"></strong></div>
			<div id="smart-match-results" style="min-height: 150px; display:flex; flex-direction:column; gap:10px;">
				<div style="padding: 20px; color: #666;">Searching Spotify API...</div>
			</div>
		</div>
	</div>
</div>

<script>
let currentMatchTargetId = null;
let currentMatchTargetType = null;

function saveManualId(type, id) {
	const input = document.getElementById('manual_id_' + type + '_' + id);
	const spotifyId = input.value.trim();
	if (!spotifyId) return alert('Please enter a Spotify ID');

	input.disabled = true;
	const fd = new FormData();
	fd.append('action', 'charts_save_matching_id');
	fd.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('type', type);
	fd.append('id', id);
	fd.append('spotify_id', spotifyId);

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				input.style.borderColor = 'green';
				setTimeout(() => window.location.reload(), 1000);
			} else {
				input.disabled = false;
				alert(res.data?.message || 'Error saving ID');
			}
		});
}

function openSmartMatchModal(type, id, query) {
	currentMatchTargetId = id;
	currentMatchTargetType = type;
	
	document.getElementById('smart-match-query').innerText = query;
	const resultsDiv = document.getElementById('smart-match-results');
	resultsDiv.innerHTML = '<div style="padding: 20px; color: #666;"><span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Searching Spotify...</div>';
	document.getElementById('smart-match-modal').style.display = 'flex';

	const fd = new FormData();
	fd.append('action', 'charts_search_spotify_matching');
	fd.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	fd.append('type', type);
	fd.append('query', query);

	fetch(ajaxurl, { method: 'POST', body: fd })
		.then(res => res.json())
		.then(res => {
			if (res.success && res.data && res.data.length > 0) {
				resultsDiv.innerHTML = res.data.map(item => `
					<div style="display:flex; align-items:center; gap:15px; padding:15px; background:var(--charts-surface-alt); border-radius:8px; border:1px solid var(--charts-border);">
						<div style="width:50px; height:50px; border-radius:4px; background-image:url('${item.image}'); background-size:cover; background-position:center; background-color:#222;"></div>
						<div style="flex:1; text-align:left;">
							<div style="font-weight:700; color:var(--charts-primary); font-size:14px;">${item.name}</div>
							<div style="font-size:11px; color:var(--charts-text-dim); margin-top:2px;">${item.artists ? item.artists + ' | ' : ''}ID: ${item.id}</div>
						</div>
						<div>
							<button onclick="selectSmartMatch('${item.id}')" class="charts-btn" style="background:#1DB954; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:700; cursor:pointer;">Select</button>
						</div>
					</div>
				`).join('');
			} else {
				resultsDiv.innerHTML = '<div style="padding: 20px; color: #ff5555;">No results found on Spotify. Try manually entering an ID.</div>';
			}
		})
		.catch(err => {
			resultsDiv.innerHTML = '<div style="padding: 20px; color: #ff5555;">Error contacting API.</div>';
		});
}

function selectSmartMatch(spotifyId) {
	const input = document.getElementById('manual_id_' + currentMatchTargetType + '_' + currentMatchTargetId);
	input.value = spotifyId;
	document.getElementById('smart-match-modal').style.display = 'none';
	saveManualId(currentMatchTargetType, currentMatchTargetId);
}
</script>
