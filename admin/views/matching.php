<?php
/**
 * Matching Center - Manual Entity Resolution
 * Identifies and lists unmatched tracks/artists from chart entries.
 */
global $wpdb;

$entries_table = $wpdb->prefix . 'charts_entries';

// 1. Fetch Unmatched Entities (Tracks and Artists with item_id = 0)
// We group by track_name/artist_names to show unique candidates
$unmatched_candidates = $wpdb->get_results("
	SELECT track_name, artist_names, item_type, spotify_id, youtube_id, COUNT(*) as occurrence_count
	FROM $entries_table
	WHERE item_id = 0
	GROUP BY track_name, artist_names, item_type
	ORDER BY occurrence_count DESC
	LIMIT 100
");

$total_unmatched = count($unmatched_candidates);
?>

<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Matching Center', 'charts' ); ?></h1>
			<p class="subtitle"><?php printf( __( 'Showing %d unique entities that require manual or automated matching.', 'charts' ), $total_unmatched ); ?></p>
		</div>
		<div class="charts-actions">
			<form method="post" action="">
				<?php wp_nonce_field( 'charts_admin_action' ); ?>
				<input type="hidden" name="charts_action" value="run_integrity_check">
				<button type="submit" class="charts-btn-secondary">
					<span class="dashicons dashicons-admin-tools" style="font-size: 16px; margin-right: 5px; vertical-align: middle;"></span>
					<?php _e( 'Force Reconciliation', 'charts' ); ?>
				</button>
			</form>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<?php if ( empty( $unmatched_candidates ) ) : ?>
		<div class="charts-card" style="padding: 100px; text-align: center; background: #fff; border-radius: 12px; margin-top: 20px;">
			<span class="dashicons dashicons-yes-alt" style="font-size: 64px; width: 64px; height: 64px; color: #22c55e;"></span>
			<h2 style="margin-top: 30px;"><?php _e( 'Full Data Integrity', 'charts' ); ?></h2>
			<p style="color: #666; max-width: 400px; margin: 10px auto;"><?php _e( 'All chart entries are correctly matched to canonical entities. No orphaned records found.', 'charts' ); ?></p>
		</div>
	<?php else : ?>
		<div class="charts-grid" style="margin-top: 24px;">
			<div class="charts-card" style="grid-column: span 12; padding: 0;">
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;"><?php _e( 'Raw Name', 'charts' ); ?></th>
							<th><?php _e( 'Type', 'charts' ); ?></th>
							<th><?php _e( 'Occurrences', 'charts' ); ?></th>
							<th><?php _e( 'Identity Check', 'charts' ); ?></th>
							<th style="text-align: right; padding-right: 24px;"><?php _e( 'Action', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $unmatched_candidates as $item ) : ?>
							<?php 
							$label = ( $item->item_type === 'artist' ) ? $item->artist_names : $item->track_name;
							$sublabel = ( $item->item_type === 'track' ) ? $item->artist_names : '';
							?>
							<tr>
								<td style="padding-left: 24px;">
									<div style="font-weight: 700;"><?php echo esc_html( $label ); ?></div>
									<?php if ( $sublabel ) : ?>
										<div style="font-size: 11px; color: #666;"><?php echo esc_html( $sublabel ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<span class="charts-badge charts-badge-neutral"><?php echo strtoupper( $item->item_type ); ?></span>
								</td>
								<td><strong><?php echo (int) $item->occurrence_count; ?></strong> <?php _e( 'times', 'charts' ); ?></td>
								<td>
									<?php if ( $item->spotify_id ) : ?>
										<span title="Spotify ID Found" class="dashicons dashicons-external" style="color: #1DB954; font-size: 16px;"></span>
									<?php endif; ?>
									<?php if ( $item->youtube_id ) : ?>
										<span title="YouTube ID Found" class="dashicons dashicons-video-alt3" style="color: #FF0000; font-size: 16px;"></span>
									<?php endif; ?>
									<?php if ( ! $item->spotify_id && ! $item->youtube_id ) : ?>
										<span style="opacity: 0.3;">—</span>
									<?php endif; ?>
								</td>
								<td style="text-align: right; padding-right: 24px;">
									<a href="<?php echo admin_php_url('page=charts-entities&s=' . urlencode($label)); ?>" class="charts-badge charts-badge-neutral" style="text-decoration: none;"><?php _e( 'Research', 'charts' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Helper to generate admin URL for research
 */
if ( ! function_exists( 'admin_php_url' ) ) {
	function admin_php_url( $path ) {
		return admin_url( 'admin.php?' . $path );
	}
}
?>
