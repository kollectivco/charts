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
									<div style="display:flex; gap:10px;">
										<span style="opacity: 0.3; font-size:10px; color:var(--charts-text-dim); font-weight:700;">NO_ID_DISCOVERED</span>
									</div>
								</td>
								<td style="text-align: right; padding-right: 24px;">
									<button onclick="window.location.href='<?php echo esc_url( \Charts\Core\Router::get_dashboard_url( $item->item_type === 'artist' ? 'artists' : 'tracks', array( 's' => urlencode( $label ) ) ) ); ?>'" class="charts-btn-create" style="padding: 4px 12px; font-size: 12px;"><?php _e( 'Sync Now', 'charts' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>
