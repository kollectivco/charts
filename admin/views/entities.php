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

// 3. Pagination Settings
$per_page = 100;
$current_page = max( 1, isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 );
$offset = ( $current_page - 1 ) * $per_page;

// 4. Filters & Search
$filter_spotify = isset( $_GET['spotify_linked'] ) ? $_GET['spotify_linked'] : '';
$filter_image   = isset( $_GET['has_image'] ) ? $_GET['has_image'] : '';

$items = array();
$total = 0;

if ( $type === 'artist' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (display_name LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND spotify_id IS NOT NULL AND spotify_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (spotify_id IS NULL OR spotify_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND image IS NOT NULL AND image != ''";
	if ( $filter_image === 'no' ) $where .= " AND (image IS NULL OR image = '')";

	$items = $wpdb->get_results( "SELECT * FROM $artists_table {$where} ORDER BY display_name ASC LIMIT $per_page OFFSET $offset" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table {$where}" );
	$title = __( 'Artists', 'charts' );
} elseif ( $type === 'track' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (t.title LIKE %s OR t.slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND t.spotify_id IS NOT NULL AND t.spotify_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (t.spotify_id IS NULL OR t.spotify_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND t.cover_image IS NOT NULL AND t.cover_image != ''";
	if ( $filter_image === 'no' ) $where .= " AND (t.cover_image IS NULL OR t.cover_image = '')";

	$items = $wpdb->get_results( "
		SELECT t.*, a.display_name AS artist_name 
		FROM $tracks_table t 
		LEFT JOIN $artists_table a ON a.id = t.primary_artist_id
		{$where} 
		ORDER BY t.title ASC LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table t {$where}" );
	$title = __( 'Tracks', 'charts' );
} elseif ( $type === 'clip' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$where .= $wpdb->prepare( " AND (v.title LIKE %s OR v.slug LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
	}
	$items = $wpdb->get_results( "
		SELECT v.*, a.display_name AS artist_name 
		FROM $videos_table v 
		LEFT JOIN $artists_table a ON a.id = v.primary_artist_id
		{$where} 
		ORDER BY v.title ASC LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table v {$where}" );
	$title = __( 'Music Clips', 'charts' );
} else {
	// Advanced Explorer
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
		LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(DISTINCT track_name) FROM $entries_table {$where}" );
	$title = __( 'Entities (Advanced Explorer)', 'charts' );
}

$num_pages = ceil( $total / $per_page );
$page_title = $title;
$total_items = $total;
$entity_type = $type;

?>
<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php echo esc_html( $page_title ); ?></h1>
			<p class="charts-admin-subtitle"><?php printf( __( 'Canonical library containing %d indexed %s entities.', 'charts' ), $total_items, strtolower($page_title) ); ?></p>
		</div>
		<div class="charts-admin-actions" style="display: flex; gap: 10px; align-items: center;">
			<?php if ($type !== 'advanced'): ?>
				<!-- Premium Logic Hub -->
				<div class="kc-logic-hub">
					<button type="button" class="kc-hub-btn" id="sync-selected-trigger">
						<span class="dashicons dashicons-forms"></span>
						<?php _e( 'Sync Selected', 'charts' ); ?>
					</button>
					<div class="kc-hub-divider"></div>
					<button type="button" class="kc-hub-btn featured" id="sync-entities-trigger">
						<span class="dashicons dashicons-update"></span>
						<?php _e( 'Sync Missing', 'charts' ); ?>
					</button>
					<div class="kc-hub-divider"></div>
					<button type="button" class="kc-hub-btn" id="sync-all-trigger">
						<span class="dashicons dashicons-database-export"></span>
						<?php _e( 'Sync All', 'charts' ); ?>
					</button>
				</div>
			<?php endif; ?>
			
			<a href="<?php echo admin_url( 'admin.php?page=charts-entities&action=edit&type=' . $entity_type ); ?>" class="charts-btn-create">
				<span class="dashicons dashicons-plus" style="margin-right:8px; vertical-align: middle;"></span>
				<?php printf( __( 'Add New %s', 'charts' ), rtrim($page_title, 's') ); ?>
			</a>
		</div>
	</header>

	<!-- Filters & Pagination Bar -->
	<div style="background: #fff; padding: 16px 24px; border-radius: 12px; box-shadow: var(--k-shadow-sm); margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
		<form method="get" style="display: flex; gap: 12px; align-items: center;">
			<input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
			
			<input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e( 'Search by name...', 'charts' ); ?>" class="charts-input" style="width: 250px; margin: 0;">
			
			<select name="spotify_linked" class="charts-input" style="margin: 0;">
				<option value=""><?php _e( 'Spotify Sync Status', 'charts' ); ?></option>
				<option value="yes" <?php selected($filter_spotify, 'yes'); ?>><?php _e( 'Linked Only', 'charts' ); ?></option>
				<option value="no" <?php selected($filter_spotify, 'no'); ?>><?php _e( 'Missing Only', 'charts' ); ?></option>
			</select>

			<select name="has_image" class="charts-input" style="margin: 0;">
				<option value=""><?php _e( 'Visual Maturity', 'charts' ); ?></option>
				<option value="yes" <?php selected($filter_image, 'yes'); ?>><?php _e( 'Has Artwork', 'charts' ); ?></option>
				<option value="no" <?php selected($filter_image, 'no'); ?>><?php _e( 'Missing Artwork', 'charts' ); ?></option>
			</select>

			<button type="submit" class="charts-btn-secondary" style="margin: 0; padding: 8px 20px;"><?php _e( 'Filter', 'charts' ); ?></button>
			<?php if($search || $filter_spotify || $filter_image): ?>
				<a href="<?php echo admin_url('admin.php?page='.$page); ?>" style="font-size: 11px; text-decoration: none; color: #666;"><?php _e( 'Clear All', 'charts' ); ?></a>
			<?php endif; ?>
		</form>

		<!-- Pagination Navigation -->
		<div class="kc-pagination" style="display: flex; align-items: center; gap: 10px;">
			<span style="font-size: 13px; font-weight: 700; color: #666;">
				<?php printf( __( 'Showing %d - %d of %d', 'charts' ), $offset + 1, min($offset + $per_page, $total), $total ); ?>
			</span>
			<div style="display: flex; gap: 4px;">
				<?php if($current_page > 1): ?>
					<a href="<?php echo add_query_arg('paged', $current_page - 1); ?>" class="charts-btn-secondary" style="padding: 4px 10px; margin: 0;"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
				<?php endif; ?>
				
				<?php if($current_page < $num_pages): ?>
					<a href="<?php echo add_query_arg('paged', $current_page + 1); ?>" class="charts-btn-secondary" style="padding: 4px 10px; margin: 0;"><span class="dashicons dashicons-arrow-right-alt2"></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- KPI Analytics Bar -->
	<?php if ( ! empty( $kpis ) ) : ?>
		<div class="kc-cards-grid">
			<?php foreach ( $kpis as $kpi ) : ?>
				<div class="kc-card">
					<div class="kc-label"><?php echo esc_html( $kpi['label'] ); ?></div>
					<div class="kc-value"><?php echo number_format( $kpi['value'] ); ?></div>
					<div class="kc-card-icon" style="background: <?php echo $kpi['color']; ?>15; color: <?php echo $kpi['color']; ?>;">
						<span class="dashicons <?php echo $kpi['icon']; ?>"></span>
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

	<!-- Sync Modal -->
	<div id="sync-progress-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
		<div class="charts-card" style="width: 500px; padding: 40px; text-align: center;">
			<h2 id="sync-status-title"><?php printf( __( 'Syncing %s...', 'charts' ), $type === 'artist' ? 'Artist Profiles' : 'Track Metadata' ); ?></h2>
			<div style="margin: 30px 0;">
				<div style="height: 10px; background: #eee; border-radius: 5px; overflow: hidden;">
					<div id="sync-progress-bar" style="width: 0%; height: 100%; background: #6366f1; transition: width 0.3s;"></div>
				</div>
				<p id="sync-status-text" style="font-size: 13px; color: #666; margin-top: 15px;"><?php _e( 'Initializing batch processing...', 'charts' ); ?></p>
			</div>
			<div id="sync-results" style="display:none; text-align: left; background: #f9f9f9; padding: 20px; border-radius: 8px; font-size: 12px; margin-bottom: 20px;">
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
					<div>Processed: <b id="res-processed">0</b></div>
					<div>Updated: <b id="res-updated">0</b></div>
					<div>Spotify Linked: <b id="res-spotify">0</b></div>
					<div id="res-platform-label"><?php echo $type === 'artist' ? 'YouTube Linked' : 'Covers Updated'; ?>: <b id="res-platform">0</b></div>
				</div>
			</div>
			<button id="close-sync-modal" class="charts-btn-primary" style="display:none;"><?php _e( 'Close & Reload', 'charts' ); ?></button>
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

	const syncAllTrigger = document.getElementById('sync-all-trigger');
	const syncSelectedTrigger = document.getElementById('sync-selected-trigger');
	const syncEntitiesTrigger = document.getElementById('sync-entities-trigger');
	
	const syncModal = document.getElementById('sync-progress-modal');
	const syncBar = document.getElementById('sync-progress-bar');
	const syncStatus = document.getElementById('sync-status-text');
	const syncResults = document.getElementById('sync-results');
	const closeBtn = document.getElementById('close-sync-modal');

	let totalProcessed = 0;
	let totalUpdated = 0;
	let totalSpotify = 0;
	let totalPlatform = 0;
	let syncMode = 'missing'; // 'missing', 'all', 'selected'
	let selectedIds = [];

	if (syncEntitiesTrigger) syncEntitiesTrigger.addEventListener('click', () => startSync('missing'));
	if (syncAllTrigger) syncAllTrigger.addEventListener('click', () => startSync('all'));
	if (syncSelectedTrigger) {
		syncSelectedTrigger.addEventListener('click', function() {
			selectedIds = Array.from(document.querySelectorAll('.entity-checkbox:checked')).map(cb => cb.value);
			if (selectedIds.length === 0) {
				alert('Please select at least one item.');
				return;
			}
			startSync('selected');
		});
	}

	function startSync(mode) {
		syncMode = mode;
		totalProcessed = 0;
		totalUpdated = 0;
		totalSpotify = 0;
		totalPlatform = 0;
		
		syncModal.style.display = 'flex';
		syncBar.style.width = '0%';
		syncBar.style.background = '#6366f1';
		syncResults.style.display = 'none';
		closeBtn.style.display = 'none';
		
		runBatch(0);
	}

	function runBatch(offset) {
		const type = '<?php echo $type; ?>';
		const formData = new FormData();
		formData.append('action', type === 'artist' ? 'charts_sync_artists' : 'charts_sync_tracks');
		formData.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
		formData.append('offset', offset);
		formData.append('mode', syncMode);
		
		if (syncMode === 'selected') {
			formData.append('ids', selectedIds.slice(offset, offset + 20).join(','));
		}

		fetch(ajaxurl, {
			method: 'POST',
			body: formData
		})
		.then(res => res.json())
		.then(res => {
			if (res.success) {
				if (res.data.complete || (syncMode === 'selected' && offset + 20 >= selectedIds.length)) {
					finishSync();
				} else {
					totalProcessed += res.data.processed;
					totalUpdated += res.data.updated;
					totalSpotify += res.data.spotify_linked;
					totalPlatform += (type === 'artist') ? res.data.youtube_linked : res.data.covers_updated;

					updateStats();
					runBatch(offset + 20);
				}
			} else {
				alert('Error: ' + res.data.message);
				syncModal.style.display = 'none';
			}
		});
	}

	function updateStats() {
		syncStatus.innerText = 'Processed ' + totalProcessed + ' items...';
		syncResults.style.display = 'block';
		document.getElementById('res-processed').innerText = totalProcessed;
		document.getElementById('res-updated').innerText = totalUpdated;
		document.getElementById('res-spotify').innerText = totalSpotify;
		document.getElementById('res-platform').innerText = totalPlatform;
		
		let totalToSync = syncMode === 'selected' ? selectedIds.length : (<?php echo $total; ?> || 500);
		let progress = Math.min(98, (totalProcessed / totalToSync) * 100); 
		syncBar.style.width = progress + '%';
	}

	function finishSync() {
		syncBar.style.width = '100%';
		syncBar.style.background = '#22c55e';
		document.getElementById('sync-status-title').innerText = 'Sync Complete!';
		syncStatus.innerText = 'Finished processing queue.';
		closeBtn.style.display = 'inline-block';
	}

	if (closeBtn) {
		closeBtn.addEventListener('click', () => window.location.reload());
	}
});
</script>
