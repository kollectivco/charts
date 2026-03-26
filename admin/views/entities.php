<?php
/**
 * Universal Entity Explorer View
 * Handles Artists, Tracks, and Advanced Entities (Denormalized)
 */
global $wpdb;

$page = $_GET['page'] ?? 'charts-entities';
$type = ( $page === 'charts-artists' ) ? 'artist' : ( ( $page === 'charts-tracks' ) ? 'track' : 'advanced' );

$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// 1. Initialize Query Vars
$items = array();
$total = 0;

if ( $type === 'artist' ) {
	$table = $wpdb->prefix . 'charts_artists';
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (display_name LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY display_name ASC LIMIT 200" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
	$title = __( 'Artists', 'charts' );
} elseif ( $type === 'track' ) {
	$table = $wpdb->prefix . 'charts_tracks';
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (title LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT t.*, a.display_name AS artist_name 
		FROM {$table} t 
		LEFT JOIN {$wpdb->prefix}charts_artists a ON a.id = t.primary_artist_id
		{$where} 
		ORDER BY t.title ASC LIMIT 200
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
	$title = __( 'Tracks', 'charts' );
} else {
	// Advanced / Denormalized View from charts_entries
	$entries_table = $wpdb->prefix . 'charts_entries';
	$where = "WHERE track_name != '' AND track_name IS NOT NULL";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (track_name LIKE %s OR artist_names LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT track_name, artist_names, spotify_id, cover_image,
		       MIN(rank_position) AS best_rank,
		       MAX(weeks_on_chart) AS max_weeks,
		       COUNT(*) AS appearances
		FROM {$entries_table}
		{$where}
		GROUP BY track_name, artist_names
		ORDER BY appearances DESC
		LIMIT 200
	" );
	$total = $wpdb->get_var( "SELECT COUNT(DISTINCT track_name) FROM {$entries_table} {$where}" );
	$title = __( 'Entities (Advanced Explorer)', 'charts' );
}

?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="subtitle"><?php printf( _n( '%s record found', '%s records found', $total, 'charts' ), number_format( $total ) ); ?></p>
		</div>
		<div class="charts-actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
				<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php _e( 'Search...', 'charts' ); ?>" class="charts-search-input">
			</form>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<div class="charts-grid">
		<div class="charts-card" style="grid-column: span 12; padding: 0; overflow: hidden;">
			<?php if ( empty( $items ) ) : ?>
				<div style="padding: 60px; text-align: center; color: #6b7280;">
					<span class="dashicons dashicons-database" style="font-size: 48px; width: 48px; height: 48px; color: #d1d5db;"></span>
					<h3 style="margin-top: 20px;"><?php _e( 'No records matching criteria', 'charts' ); ?></h3>
				</div>
			<?php else : ?>
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;"><?php _e( 'Title / Name', 'charts' ); ?></th>
							<?php if ( $type === 'track' || $type === 'advanced' ) : ?>
								<th><?php _e( 'Artist', 'charts' ); ?></th>
							<?php endif; ?>
							<?php if ( $type === 'advanced' ) : ?>
								<th><?php _e( 'Best Rank', 'charts' ); ?></th>
								<th><?php _e( 'Longevity', 'charts' ); ?></th>
							<?php else : ?>
								<th><?php _e( 'Slug', 'charts' ); ?></th>
								<th><?php _e( 'Spotify ID', 'charts' ); ?></th>
							<?php endif; ?>
							<th style="text-align: right; padding-right: 24px;"><?php _e( 'Actions', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td style="padding-left: 24px;">
									<div style="display: flex; align-items: center; gap: 10px;">
										<?php 
										$img = ( $type === 'artist' ) ? ($item->image ?? '') : ($item->cover_image ?? '');
										$label = ( $type === 'artist' ) ? $item->display_name : ($item->title ?? $item->track_name);
										?>
										<?php if ( $img ) : ?>
											<img src="<?php echo esc_url( $img ); ?>" style="width: 32px; height: 32px; border-radius: <?php echo $type === 'artist' ? '50%' : '4px'; ?>; object-fit: cover;">
										<?php else : ?>
											<div style="width: 32px; height: 32px; border-radius: 4px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;"><?php echo esc_html( strtoupper( substr( $label, 0, 1 ) ) ); ?></div>
										<?php endif; ?>
										<div style="font-weight: 700;"><?php echo esc_html( $label ); ?></div>
									</div>
								</td>

								<?php if ( $type === 'track' || $type === 'advanced' ) : ?>
									<td><span style="font-size: 13px; color: #666;"><?php echo esc_html( $item->artist_name ?? $item->artist_names ?? '—' ); ?></span></td>
								<?php endif; ?>

								<?php if ( $type === 'advanced' ) : ?>
									<td>#<?php echo (int) $item->best_rank; ?></td>
									<td><?php echo (int) $item->max_weeks; ?>W / <?php echo (int) $item->appearances; ?> Re</td>
								<?php else : ?>
									<td><code><?php echo esc_html( $item->slug ); ?></code></td>
									<td><span style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( $item->spotify_id ?? '—' ); ?></span></td>
								<?php endif; ?>

								<td style="text-align: right; padding-right: 24px;">
									<div style="display: flex; gap: 5px; justify-content: flex-end;">
										<?php if ( ! empty( $item->slug ) ) : ?>
											<?php 
											$slug_path = ( $type === 'artist' ) ? 'artist' : 'track';
											$view_url = home_url( '/charts/' . $slug_path . '/' . $item->slug );
											?>
											<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" class="charts-badge charts-badge-neutral" style="text-decoration: none;"><?php _e( 'View', 'charts' ); ?></a>
										<?php endif; ?>

										<?php if ( $type !== 'advanced' ) : ?>
											<form method="post" style="display: inline;" onsubmit="return confirm('Really delete entity? Data in entries will NOT be deleted, but relationships will break.');">
												<?php wp_nonce_field( 'charts_admin_action' ); ?>
												<input type="hidden" name="charts_action" value="delete_entity">
												<input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
												<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
												<button type="submit" class="charts-badge charts-badge-danger" style="border: none; cursor: pointer;"><?php _e( 'Delete', 'charts' ); ?></button>
											</form>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
