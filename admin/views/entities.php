<?php
/**
 * Universal Entity Explorer View
 * Handles Artists, Tracks, Clips, and Advanced Entities
 * Optimized with Premium KPI Dashboards
 */
global $wpdb;

$page = $_GET['page'] ?? 'charts-entities';
$type = ( $page === 'charts-artists' ) ? 'artist' : ( ( $page === 'charts-tracks' ) ? 'track' : ( ( $page === 'charts-clips' ) ? 'clip' : 'advanced' ) );

$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// 1. Initialize Tables
$artists_table = $wpdb->prefix . 'charts_artists';
$tracks_table  = $wpdb->prefix . 'charts_tracks';
$videos_table  = $wpdb->prefix . 'charts_videos';
$entries_table = $wpdb->prefix . 'charts_entries';

// 2. Fetch KPI Metrics (Data Integrity Audit)
$stats = array();
if ( $type === 'artist' ) {
	$stats['total']        = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table" );
	$stats['with_image']    = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table WHERE image IS NOT NULL AND image != ''" );
	$stats['with_spotify']  = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table WHERE spotify_id IS NOT NULL AND spotify_id != ''" );
	$stats['active_items']  = $wpdb->get_var( "SELECT COUNT(DISTINCT item_id) FROM $entries_table WHERE item_type = 'artist' AND item_id > 0" );
	
	$kpis = array(
		array( 'label' => __( 'Total Artists', 'charts' ), 'value' => $stats['total'], 'icon' => 'dashicons-groups', 'color' => '#6366f1' ),
		array( 'label' => __( 'Visual Maturity', 'charts' ), 'value' => $stats['with_image'], 'icon' => 'dashicons-format-image', 'color' => '#22c55e' ),
		array( 'label' => __( 'Spotify Linked', 'charts' ), 'value' => $stats['with_spotify'], 'icon' => 'dashicons-external', 'color' => '#1DB954' ),
		array( 'label' => __( 'Active Presence', 'charts' ), 'value' => $stats['active_items'], 'icon' => 'dashicons-chart-line', 'color' => '#f59e0b' ),
	);
} elseif ( $type === 'track' ) {
	$stats['total']        = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table" );
	$stats['with_cover']    = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table WHERE cover_image IS NOT NULL AND cover_image != ''" );
	$stats['with_spotify']  = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table WHERE spotify_id IS NOT NULL AND spotify_id != ''" );
	$stats['active_items']  = $wpdb->get_var( "SELECT COUNT(DISTINCT item_id) FROM $entries_table WHERE item_type = 'track' AND item_id > 0" );

	$kpis = array(
		array( 'label' => __( 'Total Tracks', 'charts' ), 'value' => $stats['total'], 'icon' => 'dashicons-playlist-audio', 'color' => '#6366f1' ),
		array( 'label' => __( 'Cover Coverage', 'charts' ), 'value' => $stats['with_cover'], 'icon' => 'dashicons-image-filter', 'color' => '#22c55e' ),
		array( 'label' => __( 'Spotify Ready', 'charts' ), 'value' => $stats['with_spotify'], 'icon' => 'dashicons-spotify', 'color' => '#1DB954' ),
		array( 'label' => __( 'Chart Presence', 'charts' ), 'value' => $stats['active_items'], 'icon' => 'dashicons-chart-bar', 'color' => '#f59e0b' ),
	);
} elseif ( $type === 'clip' ) {
	$stats['total']        = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table" );
	$stats['with_thumb']    = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table WHERE thumbnail IS NOT NULL AND thumbnail != ''" );
	$stats['with_youtube']  = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table WHERE youtube_id IS NOT NULL AND youtube_id != ''" );
	$stats['active_items']  = $wpdb->get_var( "SELECT COUNT(DISTINCT item_id) FROM $entries_table WHERE item_type = 'video' AND item_id > 0" );

	$kpis = array(
		array( 'label' => __( 'Music Clips', 'charts' ), 'value' => $stats['total'], 'icon' => 'dashicons-video-alt3', 'color' => '#ef4444' ),
		array( 'label' => __( 'Visual Thumbs', 'charts' ), 'value' => $stats['with_thumb'], 'icon' => 'dashicons-format-video', 'color' => '#22c55e' ),
		array( 'label' => __( 'YouTube Linked', 'charts' ), 'value' => $stats['with_youtube'], 'icon' => 'dashicons-youtube', 'color' => '#FF0000' ),
		array( 'label' => __( 'Active Views', 'charts' ), 'value' => $stats['active_items'], 'icon' => 'dashicons-visibility', 'color' => '#f59e0b' ),
	);
}

// 3. Fetch Items for Listing
$items = array();
$total = 0;

if ( $type === 'artist' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (display_name LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "SELECT * FROM $artists_table {$where} ORDER BY display_name ASC LIMIT 200" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table {$where}" );
	$title = __( 'Artists', 'charts' );
} elseif ( $type === 'track' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (title LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT t.*, a.display_name AS artist_name 
		FROM $tracks_table t 
		LEFT JOIN $artists_table a ON a.id = t.primary_artist_id
		{$where} 
		ORDER BY t.title ASC LIMIT 200
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table {$where}" );
	$title = __( 'Tracks', 'charts' );
} elseif ( $type === 'clip' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (title LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT v.*, a.display_name AS artist_name 
		FROM $videos_table v 
		LEFT JOIN $artists_table a ON a.id = v.primary_artist_id
		{$where} 
		ORDER BY v.title ASC LIMIT 200
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table {$where}" );
	$title = __( 'Music Clips', 'charts' );
} else {
	// Advanced / Denormalized View from charts_entries
	$where = "WHERE track_name != '' AND track_name IS NOT NULL";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (track_name LIKE %s OR artist_names LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT track_name, artist_names, spotify_id, cover_image,
		       MIN(rank_position) AS best_rank,
		       MAX(weeks_on_chart) AS max_weeks,
		       COUNT(*) AS appearances
		FROM $entries_table
		{$where}
		GROUP BY track_name, artist_names
		ORDER BY appearances DESC
		LIMIT 200
	" );
	$total = $wpdb->get_var( "SELECT COUNT(DISTINCT track_name) FROM $entries_table {$where}" );
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

	<!-- KPI Analytics Bar -->
	<?php if ( ! empty( $kpis ) ) : ?>
		<div class="charts-grid" style="margin-top: 24px;">
			<?php foreach ( $kpis as $kpi ) : ?>
				<div class="charts-card stats-card" style="grid-column: span 3; position: relative;">
					<div class="label" style="font-size: 10px; font-weight: 800; letter-spacing: 1px; color: #9ca3af; text-transform: uppercase;">
						<?php echo esc_html( $kpi['label'] ); ?>
					</div>
					<div class="value" style="font-size: 28px; font-weight: 800; margin-top: 5px; color: #1f2937;">
						<?php echo number_format( $kpi['value'] ); ?>
					</div>
					<div style="position: absolute; top: 15px; right: 20px; width: 32px; height: 32px; background: <?php echo $kpi['color']; ?>15; color: <?php echo $kpi['color']; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
						<span class="dashicons <?php echo $kpi['icon']; ?>" style="font-size: 18px; width: 18px; height: 18px;"></span>
					</div>
					<div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: <?php echo $kpi['color']; ?>; border-top-left-radius: 12px; border-bottom-left-radius: 12px; opacity: 0.6;"></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="charts-grid" style="margin-top: 24px;">
		<div class="charts-card" style="grid-column: span 12; padding: 0; overflow: hidden;">
			
			<form method="post" id="entities-bulk-form">
				<?php wp_nonce_field( 'charts_admin_action' ); ?>
				<input type="hidden" name="charts_action" value="bulk_action">
				<input type="hidden" name="entity_type" value="<?php echo esc_attr( $type ); ?>">

				<!-- Bulk Actions Header -->
				<?php if ( ! empty( $items ) && $type !== 'advanced' ) : ?>
					<div style="padding: 15px 24px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; background: #fafafa;">
						<select name="bulk_action_type" class="charts-input" style="width: 200px; margin: 0;">
							<option value=""><?php _e( 'Bulk Actions', 'charts' ); ?></option>
							<option value="delete"><?php _e( 'Delete Selected', 'charts' ); ?></option>
						</select>
						<button type="submit" class="charts-btn-secondary" style="margin: 0;" onclick="return confirm('<?php _e( 'Are you sure you want to apply this action to all selected items?', 'charts' ); ?>');">
							<?php _e( 'Apply', 'charts' ); ?>
						</button>
					</div>
				<?php endif; ?>

				<?php if ( empty( $items ) ) : ?>
					<div style="padding: 60px; text-align: center; color: #6b7280;">
						<span class="dashicons dashicons-database" style="font-size: 48px; width: 48px; height: 48px; color: #d1d5db;"></span>
						<h3 style="margin-top: 20px;"><?php _e( 'No records matching criteria', 'charts' ); ?></h3>
					</div>
				<?php else : ?>
					<table class="charts-table">
						<thead>
							<tr>
								<?php if ( $type !== 'advanced' ) : ?>
									<th style="width: 40px; padding-left: 24px;">
										<input type="checkbox" id="select-all-entities">
									</th>
								<?php endif; ?>
								<th style="<?php echo $type === 'advanced' ? 'padding-left: 24px;' : ''; ?>"><?php _e( 'Title / Name', 'charts' ); ?></th>
								<?php if ( $type === 'track' || $type === 'clip' || $type === 'advanced' ) : ?>
									<th><?php _e( 'Artist', 'charts' ); ?></th>
								<?php endif; ?>
								<?php if ( $type === 'advanced' ) : ?>
									<th><?php _e( 'Best Rank', 'charts' ); ?></th>
									<th><?php _e( 'Longevity', 'charts' ); ?></th>
								<?php else : ?>
									<th><?php _e( 'Slug', 'charts' ); ?></th>
									<th><?php echo $type === 'clip' ? __( 'Reference', 'charts' ) : __( 'Spotify ID', 'charts' ); ?></th>
								<?php endif; ?>
								<th style="text-align: right; padding-right: 24px;"><?php _e( 'Actions', 'charts' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $item ) : ?>
								<tr>
									<?php if ( $type !== 'advanced' ) : ?>
										<td style="padding-left: 24px;">
											<input type="checkbox" name="item_ids[]" value="<?php echo (int) $item->id; ?>" class="entity-checkbox">
										</td>
									<?php endif; ?>
									<td style="<?php echo $type === 'advanced' ? 'padding-left: 24px;' : ''; ?>">
										<div style="display: flex; align-items: center; gap: 10px;">
											<?php 
											$img = ( $type === 'artist' ) ? ($item->image ?? '') : ( ($type === 'clip') ? ($item->thumbnail ?? '') : ($item->cover_image ?? '') );
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

									<?php if ( $type === 'track' || $type === 'clip' || $type === 'advanced' ) : ?>
										<td><span style="font-size: 13px; color: #666;"><?php echo esc_html( $item->artist_name ?? $item->artist_names ?? '—' ); ?></span></td>
									<?php endif; ?>

									<?php if ( $type === 'advanced' ) : ?>
										<td>#<?php echo (int) $item->best_rank; ?></td>
										<td><?php echo (int) $item->max_weeks; ?>W / <?php echo (int) $item->appearances; ?> Re</td>
									<?php else : ?>
										<td><code><?php echo esc_html( $item->slug ); ?></code></td>
										<td><span style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( $item->spotify_id ?? $item->youtube_id ?? '—' ); ?></span></td>
									<?php endif; ?>

									<td style="text-align: right; padding-right: 24px;">
										<div style="display: flex; gap: 5px; justify-content: flex-end;">
											<?php if ( ! empty( $item->slug ) ) : ?>
												<?php 
												$slug_path = ( $type === 'artist' ) ? 'artist' : ( ($type === 'clip') ? 'clip' : 'track' );
												$view_url = home_url( '/charts/' . $slug_path . '/' . $item->slug );
												?>
												<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" class="charts-badge charts-badge-neutral" style="text-decoration: none;"><?php _e( 'View', 'charts' ); ?></a>
											<?php endif; ?>

											<?php if ( $type !== 'advanced' ) : ?>
												<button type="button" class="charts-badge charts-badge-danger" style="border: none; cursor: pointer;" onclick="if(confirm('Really delete entity?')) { document.getElementById('single-delete-id').value = <?php echo (int) $item->id; ?>; document.getElementById('single-delete-form').submit(); }">
													<?php _e( 'Delete', 'charts' ); ?>
												</button>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</form>
		</div>
	</div>
</div>

<!-- Hidden form for individual deletes -->
<form method="post" id="single-delete-form" style="display:none;">
	<?php wp_nonce_field( 'charts_admin_action' ); ?>
	<input type="hidden" name="charts_action" value="delete_entity">
	<input type="hidden" name="id" id="single-delete-id" value="">
	<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const selectAll = document.getElementById('select-all-entities');
	const checkboxes = document.querySelectorAll('.entity-checkbox');
	
	if (selectAll) {
		selectAll.addEventListener('change', function() {
			checkboxes.forEach(cb => cb.checked = selectAll.checked);
		});
	}
});
</script>
