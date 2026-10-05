<?php
/**
 * Universal Entity Explorer View
 * Handles Artists, Tracks, Clips, and Advanced Entities
 * Optimized with Premium KPI Dashboards
 */
global $wpdb;

$page = sanitize_key( wp_unslash( $_GET['page'] ?? 'charts-artists' ) );
$type = ( $page === 'charts-artists' ) ? 'artist' : ( ( $page === 'charts-tracks' ) ? 'track' : ( ( $page === 'charts-clips' ) ? 'video' : ( ( $page === 'charts-albums' ) ? 'album' : 'advanced' ) ) );

$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$search_placeholder = $type === 'track'
	? __( 'Search songs, artists, Spotify ID, or YouTube ID…', 'charts' )
	: ( $type === 'video' ? __( 'Search clips, artists, or YouTube ID…', 'charts' ) : ( $type === 'album' ? __( 'Search albums, artists, or Spotify ID…', 'charts' ) : __( 'Search by name, English name, or ID…', 'charts' ) ) );

// 1. Initialize Tables
$artists_table = $wpdb->prefix . 'charts_artists';
$tracks_table  = $wpdb->prefix . 'charts_tracks';
$videos_table  = $wpdb->prefix . 'charts_videos';
$albums_table  = $wpdb->prefix . 'charts_albums';
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
} elseif ( $type === 'video' ) {
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
} elseif ( $type === 'album' ) {
	$stats['total']        = $wpdb->get_var( "SELECT COUNT(*) FROM $albums_table" );
	$stats['with_cover']    = $wpdb->get_var( "SELECT COUNT(*) FROM $albums_table WHERE cover_image IS NOT NULL AND cover_image != ''" );
	$stats['with_spotify']  = $wpdb->get_var( "SELECT COUNT(*) FROM $albums_table WHERE spotify_id IS NOT NULL AND spotify_id != ''" );
	$stats['active_items']  = $wpdb->get_var( "SELECT COUNT(DISTINCT item_id) FROM $entries_table WHERE item_type = 'album' AND item_id > 0" );

	$kpis = array(
		array( 'label' => __( 'Total Albums', 'charts' ), 'value' => $stats['total'], 'icon' => 'dashicons-album', 'color' => '#8b5cf6' ),
		array( 'label' => __( 'Cover Artwork', 'charts' ), 'value' => $stats['with_cover'], 'icon' => 'dashicons-format-image', 'color' => '#22c55e' ),
		array( 'label' => __( 'Spotify Linked', 'charts' ), 'value' => $stats['with_spotify'], 'icon' => 'dashicons-spotify', 'color' => '#1DB954' ),
		array( 'label' => __( 'Chart Presence', 'charts' ), 'value' => $stats['active_items'], 'icon' => 'dashicons-chart-bar', 'color' => '#f59e0b' ),
	);
}

// 3. Pagination Settings
$per_page = 100;
$current_page = max( 1, isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1 );
$offset = ( $current_page - 1 ) * $per_page;

// 4. Filters & Search
$filter_spotify = in_array( $_GET['spotify_linked'] ?? '', array( 'yes', 'no' ), true ) ? sanitize_key( wp_unslash( $_GET['spotify_linked'] ) ) : '';
$filter_image   = in_array( $_GET['has_image'] ?? '', array( 'yes', 'no' ), true ) ? sanitize_key( wp_unslash( $_GET['has_image'] ) ) : '';
$filter_en      = in_array( $_GET['missing_en'] ?? '', array( 'yes', 'no' ), true ) ? sanitize_key( wp_unslash( $_GET['missing_en'] ) ) : '';
$filter_artist  = $type === 'track' ? absint( $_GET['artist_id'] ?? 0 ) : 0;
$artists = $type === 'track' ? $wpdb->get_results( "SELECT id, display_name FROM $artists_table ORDER BY display_name ASC" ) : array();

$items = array();
$total = 0;

if ( $type === 'artist' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= $wpdb->prepare( " AND (display_name LIKE %s OR display_name_en LIKE %s OR slug LIKE %s OR spotify_id LIKE %s)", $like, $like, $like, $like );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND spotify_id IS NOT NULL AND spotify_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (spotify_id IS NULL OR spotify_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND image IS NOT NULL AND image != ''";
	if ( $filter_image === 'no' ) $where .= " AND (image IS NULL OR image = '')";

	if ( $filter_en === 'yes' ) $where .= " AND (display_name_en IS NULL OR display_name_en = '')";
	if ( $filter_en === 'no' ) $where .= " AND display_name_en IS NOT NULL AND display_name_en != ''";
	$items = $wpdb->get_results( "SELECT * FROM $artists_table {$where} ORDER BY display_name ASC LIMIT $per_page OFFSET $offset" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $artists_table {$where}" );
	$title = __( 'Artists', 'charts' );
} elseif ( $type === 'track' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= $wpdb->prepare( " AND (t.title LIKE %s OR t.title_en LIKE %s OR t.slug LIKE %s OR t.spotify_id LIKE %s OR t.youtube_id LIKE %s OR EXISTS (SELECT 1 FROM $artists_table sa WHERE (sa.id = t.primary_artist_id OR sa.id IN (SELECT ta.artist_id FROM {$wpdb->prefix}charts_track_artists ta WHERE ta.track_id = t.id)) AND (sa.display_name LIKE %s OR sa.display_name_en LIKE %s)))", $like, $like, $like, $like, $like, $like, $like );
	}
	if ( $filter_artist ) {
		$where .= $wpdb->prepare( " AND (t.primary_artist_id = %d OR EXISTS (SELECT 1 FROM {$wpdb->prefix}charts_track_artists ta WHERE ta.track_id = t.id AND ta.artist_id = %d))", $filter_artist, $filter_artist );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND t.spotify_id IS NOT NULL AND t.spotify_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (t.spotify_id IS NULL OR t.spotify_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND t.cover_image IS NOT NULL AND t.cover_image != ''";
	if ( $filter_image === 'no' ) $where .= " AND (t.cover_image IS NULL OR t.cover_image = '')";

	if ( $filter_en === 'yes' ) $where .= " AND (t.title_en IS NULL OR t.title_en = '')";
	if ( $filter_en === 'no' ) $where .= " AND t.title_en IS NOT NULL AND t.title_en != ''";
	$items = $wpdb->get_results( "
		SELECT t.*,
			(SELECT GROUP_CONCAT(DISTINCT ar.display_name ORDER BY ar.display_name SEPARATOR ', ')
			 FROM $artists_table ar
			 WHERE ar.id = t.primary_artist_id
			    OR ar.id IN (SELECT ta.artist_id FROM {$wpdb->prefix}charts_track_artists ta WHERE ta.track_id = t.id)
			) AS artist_name
		FROM $tracks_table t
		{$where} 
		ORDER BY t.title ASC LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $tracks_table t {$where}" );
	$title = __( 'Tracks', 'charts' );
} elseif ( $type === 'video' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= $wpdb->prepare( " AND (v.title LIKE %s OR v.slug LIKE %s OR v.youtube_id LIKE %s OR EXISTS (SELECT 1 FROM $artists_table sa WHERE (sa.id = v.primary_artist_id OR sa.id IN (SELECT va.artist_id FROM {$wpdb->prefix}charts_video_artists va WHERE va.video_id = v.id)) AND (sa.display_name LIKE %s OR sa.display_name_en LIKE %s)))", $like, $like, $like, $like, $like );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND v.youtube_id IS NOT NULL AND v.youtube_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (v.youtube_id IS NULL OR v.youtube_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND v.thumbnail IS NOT NULL AND v.thumbnail != ''";
	if ( $filter_image === 'no' ) $where .= " AND (v.thumbnail IS NULL OR v.thumbnail = '')";
	$items = $wpdb->get_results( "
		SELECT v.*, a.display_name AS artist_name 
		FROM $videos_table v 
		LEFT JOIN $artists_table a ON a.id = v.primary_artist_id
		{$where} 
		ORDER BY v.title ASC LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $videos_table v {$where}" );
	$title = __( 'Music Clips', 'charts' );
} elseif ( $type === 'album' ) {
	$where = "WHERE 1=1";
	if ( $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where .= $wpdb->prepare( " AND (al.title LIKE %s OR al.slug LIKE %s OR al.spotify_id LIKE %s OR EXISTS (SELECT 1 FROM $artists_table sa WHERE sa.id = al.primary_artist_id AND (sa.display_name LIKE %s OR sa.display_name_en LIKE %s)))", $like, $like, $like, $like, $like );
	}
	if ( $filter_spotify === 'yes' ) $where .= " AND al.spotify_id IS NOT NULL AND al.spotify_id != ''";
	if ( $filter_spotify === 'no' ) $where .= " AND (al.spotify_id IS NULL OR al.spotify_id = '')";
	if ( $filter_image === 'yes' ) $where .= " AND al.cover_image IS NOT NULL AND al.cover_image != ''";
	if ( $filter_image === 'no' ) $where .= " AND (al.cover_image IS NULL OR al.cover_image = '')";
	if ( $filter_en === 'yes' ) $where .= " AND (al.title_en IS NULL OR al.title_en = '')";
	if ( $filter_en === 'no' ) $where .= " AND al.title_en IS NOT NULL AND al.title_en != ''";

	$items = $wpdb->get_results( "
		SELECT al.*, a.display_name AS artist_name 
		FROM $albums_table al 
		LEFT JOIN $artists_table a ON a.id = al.primary_artist_id
		{$where} 
		ORDER BY al.title ASC LIMIT $per_page OFFSET $offset
	" );
	$total = $wpdb->get_var( "SELECT COUNT(*) FROM $albums_table al {$where}" );
	$title = __( 'Albums', 'charts' );
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
					<div class="kc-hub-divider"></div>
					<button type="button" class="kc-hub-btn" onclick="openSmartDeduplicatorModal()" style="color: #d946ef;">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php _e( 'Smart Deduplicator', 'charts' ); ?>
					</button>
				</div>
			<?php endif; ?>
			
			<?php if ( $type === 'video' ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=charts-clip-track-linker' ) ); ?>" class="charts-btn-secondary" style="display:inline-flex; align-items:center; gap:7px; padding:9px 14px; text-decoration:none;">
					<span class="dashicons dashicons-randomize" aria-hidden="true"></span><?php esc_html_e( 'Link Clips to Tracks', 'charts' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo admin_url( 'admin.php?page=' . esc_attr($page) . '&action=edit&type=' . $entity_type ); ?>" class="charts-btn-create">
				<span class="dashicons dashicons-plus" style="margin-right:8px; vertical-align: middle;"></span>
				<?php printf( __( 'Add New %s', 'charts' ), rtrim($page_title, 's') ); ?>
			</a>
		</div>
	</header>

	<!-- Filters & Pagination Bar -->
	<div style="background: #fff; padding: 16px 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
		<form method="get" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; width: 100%;">
			<!-- Search -->
			<div style="flex: 1 1 320px; min-width: 250px;">
			<input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
			
			<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" class="charts-input" style="width: 100%; margin: 0;" aria-label="<?php esc_attr_e( 'Search entities', 'charts' ); ?>">
			</div>
			<!-- Filters -->
			<div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
			<?php if ( $type === 'track' ) : ?>
				<select name="artist_id" class="charts-input" style="margin: 0; min-width: 190px;" aria-label="<?php esc_attr_e( 'Filter tracks by artist', 'charts' ); ?>">
					<option value=""><?php esc_html_e( 'All Artists', 'charts' ); ?></option>
					<?php foreach ( $artists as $artist ) : ?>
						<option value="<?php echo (int) $artist->id; ?>" <?php selected( $filter_artist, (int) $artist->id ); ?>><?php echo esc_html( $artist->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
				<select name="spotify_linked" class="charts-input" style="margin: 0;">
				<option value=""><?php echo $type === 'video' ? __( 'YouTube Status', 'charts' ) : __( 'Spotify Sync Status', 'charts' ); ?></option>
				<option value="yes" <?php selected($filter_spotify, 'yes'); ?>><?php _e( 'Linked Only', 'charts' ); ?></option>
				<option value="no" <?php selected($filter_spotify, 'no'); ?>><?php _e( 'Missing Only', 'charts' ); ?></option>
			</select>

			<select name="has_image" class="charts-input" style="margin: 0;">
				<option value=""><?php _e( 'Visual Maturity', 'charts' ); ?></option>
				<option value="yes" <?php selected($filter_image, 'yes'); ?>><?php _e( 'Has Artwork', 'charts' ); ?></option>
				<option value="no" <?php selected($filter_image, 'no'); ?>><?php _e( 'Missing Artwork', 'charts' ); ?></option>
			</select>

			<?php if ( $type === 'artist' || $type === 'track' ) : ?>
				<select name="missing_en" class="charts-input" style="margin: 0;">
					<option value=""><?php esc_html_e( 'English Name Status', 'charts' ); ?></option>
					<option value="yes" <?php selected($filter_en, 'yes'); ?>><?php esc_html_e( 'Missing English Name', 'charts' ); ?></option>
					<option value="no" <?php selected($filter_en, 'no'); ?>><?php esc_html_e( 'Has English Name', 'charts' ); ?></option>
				</select>
			<?php endif; ?>
			<button type="submit" class="charts-btn-secondary" style="margin: 0; padding: 8px 20px;"><?php _e( 'Filter', 'charts' ); ?></button>
			<?php if($search || $filter_spotify || $filter_image || $filter_en || $filter_artist): ?>
				<a href="<?php echo admin_url('admin.php?page='.$page); ?>" style="font-size: 11px; text-decoration: none; color: #666;"><?php _e( 'Clear All', 'charts' ); ?></a>
			<?php endif; ?>
		</form>

		<!-- Pagination Navigation -->
		<div class="kc-pagination" style="display: flex; align-items: center; gap: 10px;">
			<span style="font-size: 13px; font-weight: 700; color: #666;">
				<?php if ( $total > 0 ) : ?>
					<?php printf( __( 'Showing %d - %d of %d', 'charts' ), $offset + 1, min($offset + $per_page, $total), $total ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Showing 0 of 0', 'charts' ); ?>
				<?php endif; ?>
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

	<div style="margin-top: 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
			<form method="post" id="entities-bulk-form">
				<?php wp_nonce_field( 'charts_admin_action' ); ?>
				<input type="hidden" name="charts_action" value="bulk_action">
				<input type="hidden" name="entity_type" value="<?php echo esc_attr( $type ); ?>">

				<!-- Bulk Actions Header -->
				<?php if ( ! empty( $items ) && $type !== 'advanced' ) : ?>
					<div style="padding: 15px 24px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; background: #fafafa;">
						<select name="bulk_action_type" id="bulk_action_type" class="charts-input" style="width: 200px; margin: 0;">
							<option value=""><?php _e( 'Bulk Actions', 'charts' ); ?></option>
							<option value="bulk_find_duplicates"><?php _e( 'Search for Duplicates', 'charts' ); ?></option>
							<option value="bulk_sync_selected"><?php _e( 'Sync Selected', 'charts' ); ?></option>
							<option value="bulk_promote"><?php _e( 'Migrate to Native', 'charts' ); ?></option>
							<option value="bulk_merge"><?php _e( 'Merge Selected', 'charts' ); ?></option>
							<option value="delete" style="color:red;"><?php _e( 'Delete Permanently', 'charts' ); ?></option>
						</select>
						<button type="button" class="charts-btn-secondary" style="margin: 0; height: 36px;" onclick="handleBulkActionSubmit(event)">
							<?php _e( 'Apply Bulk Action', 'charts' ); ?>
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
								<?php if ( $type === 'track' || $type === 'video' || $type === 'album' || $type === 'advanced' ) : ?>
									<th><?php _e( 'Artist', 'charts' ); ?></th>
								<?php endif; ?>
								<?php if ( $type === 'advanced' ) : ?>
									<th><?php _e( 'Best Rank', 'charts' ); ?></th>
									<th><?php _e( 'Longevity', 'charts' ); ?></th>
								<?php else : ?>
									<th><?php _e( 'Slug', 'charts' ); ?></th>
									<th><?php echo $type === 'video' ? __( 'Reference', 'charts' ) : __( 'Spotify ID', 'charts' ); ?></th>
								<?php endif; ?>
								<th style="text-align: right; padding-right: 24px;"><?php _e( 'Actions', 'charts' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $item ) : ?>
								<?php try { ?>
									<tr>
									<?php if ( $type !== 'advanced' ) : ?>
										<td style="padding-left: 24px;">
											<input type="checkbox" name="item_ids[]" value="<?php echo (int) $item->id; ?>" class="entity-checkbox">
										</td>
									<?php endif; ?>
									<td style="<?php echo $type === 'advanced' ? 'padding-left: 24px;' : ''; ?>">
										<div style="display: flex; align-items: center; gap: 10px;">
												<?php 
												$img = ( $type === 'artist' ) ? ($item->image ?? '') : ( ($type === 'video') ? ($item->thumbnail ?? '') : ($item->cover_image ?? '') );
												$label = ( $type === 'artist' ) ? ($item->display_name ?? '—') : ($item->title ?? $item->track_name ?? '—');
												?>
											<?php if ( $img ) : ?>
												<img src="<?php echo esc_url( $img ); ?>" style="width: 38px; height: 38px; border-radius: <?php echo $type === 'artist' ? '50%' : '8px'; ?>; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
											<?php else : ?>
												<div style="width: 38px; height: 38px; border-radius: <?php echo $type === 'artist' ? '50%' : '8px'; ?>; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; color: #94a3b8;"><?php echo esc_html( strtoupper( mb_substr( $label, 0, 1 ) ) ); ?></div>
											<?php endif; ?>
												<div style="display: flex; flex-direction: column; justify-content: center;">
													<div class="charts-primary" style="font-weight: 700; color: #0f172a; font-size: 14px;"><?php echo esc_html( $label ); ?></div>
													<div style="font-size: 11px; color: #64748b; margin-top: 2px;">
														<?php 
														$en_name = ($type === 'artist') ? ($item->display_name_en ?? '') : (($type === 'track' || $type === 'video' || $type === 'album') ? ($item->title_en ?? '') : '');
														echo esc_html( $en_name ?: urldecode( $item->slug ?? '' ) ); 
														?>
													</div>
												</div>
										</div>
									</td>

									<?php if ( $type === 'track' || $type === 'video' || $type === 'album' || $type === 'advanced' ) : ?>
										<td><span style="font-size: 13px; color: #666;"><?php echo esc_html( $item->artist_name ?? $item->artist_names ?? '—' ); ?></span></td>
									<?php endif; ?>

										<?php if ( $type === 'advanced' ) : ?>
											<td>#<?php echo (int) ($item->best_rank ?? 0); ?></td>
											<td><?php echo (int) ($item->max_weeks ?? 0); ?>W / <?php echo (int) ($item->appearances ?? 0); ?> Re</td>
										<?php else : ?>
											<td><code><?php echo esc_html( urldecode( $item->slug ?? '—' ) ); ?></code></td>
											<td><span style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( $item->spotify_id ?? $item->youtube_id ?? '—' ); ?></span></td>
										<?php endif; ?>

									<?php
									// 5. Compute status & resolution
									$meta = ! empty( $item->metadata_json ) ? json_decode( $item->metadata_json, true ) : array();
									$sync_status = $meta['sync_status'] ?? 'pending';
									$status_labels = array(
										'synced'             => array( 'label' => __( 'Synced', 'charts' ), 'color' => '#22c55e', 'bg' => '#f0fdf4' ),
										'pending'            => array( 'label' => __( 'Pending Sync', 'charts' ), 'color' => '#64748b', 'bg' => '#f8fafc' ),
										'missing_spotify_id' => array( 'label' => __( 'Missing ID', 'charts' ), 'color' => '#f59e0b', 'bg' => '#fffbeb' ),
										'spotify_not_found'  => array( 'label' => __( 'Spotify 404', 'charts' ), 'color' => '#ef4444', 'bg' => '#fef2f2' ),
										'api_error'          => array( 'label' => __( 'API Error', 'charts' ), 'color' => '#ef4444', 'bg' => '#fef2f2' ),
									);
									$s_cfg = $status_labels[ $sync_status ] ?? $status_labels['pending'];

									// Resolution helpers
									$native_post_id = ( ! empty( $item->slug ) && isset($item->id) ) ? \Charts\Core\EntityManager::get_post_id_by_legacy_id( $type, $item->id ) : 0;
									$slug_path = ( $type === 'artist' ) ? 'artist' : ( ($type === 'video') ? 'clip' : 'track' );
									$view_url  = ( ! empty( $item->slug ) ) ? home_url( '/charts/' . $slug_path . '/' . $item->slug ) : '#';
									?>
									<td style="text-align: right; padding-right: 24px;">
										<div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
											<span class="charts-badge" style="background: <?php echo $s_cfg['bg']; ?>; color: <?php echo $s_cfg['color']; ?>; border: 1px solid <?php echo $s_cfg['color']; ?>30; font-size: 11px; padding: 4px 10px; font-weight: 600; border-radius: 20px;" title="<?php echo esc_attr($meta['sync_error'] ?? ''); ?>">
												<?php echo esc_html( $s_cfg['label'] ); ?>
											</span>

											<?php if ( $type === 'chart' || $type === 'advanced' ) : ?>
												<?php if ( ! empty( $item->slug ) ) : ?>
													<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" class="charts-badge charts-badge-neutral" style="text-decoration: none;"><?php _e( 'View', 'charts' ); ?></a>
												<?php endif; ?>

												<?php if ( $type === 'chart' ) : ?>
													<?php if ( $native_post_id ) : ?>
														<a href="<?php echo esc_url( get_edit_post_link( $native_post_id ) ); ?>" class="charts-badge" style="background:rgba(99,102,241,0.1); color:#6366f1; border:1px solid rgba(99,102,241,0.2); text-decoration:none;" title="<?php esc_attr_e( 'Edit native WordPress profile', 'charts' ); ?>"><?php _e( 'Native ✎', 'charts' ); ?></a>
													<?php else : ?>
														<button type="button" class="charts-badge charts-badge-neutral" style="border:none; cursor:pointer; background:#f3f4f6; color:#6b7280;" title="<?php esc_attr_e( 'Promote to Native CPT', 'charts' ); ?>" onclick="if(confirm('<?php echo esc_js( __( 'Promote this entity to a native WordPress CPT?', 'charts' ) ); ?>')) { document.getElementById('promote-entity-id').value = <?php echo (int) $item->id; ?>; document.getElementById('promote-entity-form').submit(); }">
															<span class="dashicons dashicons-upload" style="font-size:14px; width:14px; height:14px; margin-top:-2px;"></span> <?php _e( 'Promote', 'charts' ); ?>
														</button>
													<?php endif; ?>
												<?php endif; ?>

												<?php if ( isset($item->id) ) : ?>
													<button type="button" class="charts-badge charts-badge-danger" style="border:none; cursor:pointer;" onclick="if(confirm('<?php echo esc_js( __( 'Really delete this entity?', 'charts' ) ); ?>')) { document.getElementById('single-delete-id').value = <?php echo (int) $item->id; ?>; document.getElementById('single-delete-form').submit(); }">
														<?php _e( 'Delete', 'charts' ); ?>
													</button>
												<?php endif; ?>
							<?php else : ?>
								<?php if ( ! empty( $item->slug ) ) : ?>
									<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener" class="entity-row-action entity-view-action"><?php esc_html_e( 'View', 'charts' ); ?></a>
								<?php endif; ?>
								<?php if ( isset( $item->id ) ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => $page, 'action' => 'edit', 'type' => $type, 'id' => (int) $item->id ), admin_url( 'admin.php' ) ) ); ?>" class="entity-row-action entity-edit-action"><span class="dashicons dashicons-edit" aria-hidden="true"></span><?php esc_html_e( 'Edit', 'charts' ); ?></a>
									<details class="entity-merge-menu">
										<summary class="entity-row-action entity-merge-trigger"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span><?php esc_html_e( 'Merge', 'charts' ); ?><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></summary>
										<div class="entity-merge-options">
											<button type="button" data-entity-merge="choose" data-id="<?php echo (int) $item->id; ?>" data-name="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Choose merge target', 'charts' ); ?></button>
											<button type="button" data-entity-merge="search" data-id="<?php echo (int) $item->id; ?>" data-name="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Search for a match', 'charts' ); ?></button>
										</div>
									</details>
								<?php endif; ?>
								<?php if ( isset($item->id) ) : ?>
									<button type="button" class="entity-row-action entity-delete-action" onclick="if(confirm('<?php echo esc_js( __( 'Really delete this entity?', 'charts' ) ); ?>')) { document.getElementById('single-delete-id').value = <?php echo (int) $item->id; ?>; document.getElementById('single-delete-form').submit(); }">
										<?php _e( 'Delete', 'charts' ); ?>
									</button>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							<?php } catch ( \Throwable $e ) { 
								error_log( 'Charts Row Render Failure: ' . $e->getMessage() );
							?>
								<tr><td colspan="7" style="padding: 10px; font-size: 11px; background: #fff5f5; color: #ef4444;"><?php printf( __( 'Row resolution failure for %s: %s', 'charts' ), esc_html( $item->slug ?? 'unknown' ), esc_html( $e->getMessage() ) ); ?></td></tr>
							<?php } ?>
						<?php endforeach; ?>
					</tbody>
					</table>
				<?php endif; ?>
			</form>
		</div>

	<!-- Sync Modal -->
	<div id="sync-progress-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
		<div class="charts-card" style="width: 500px; padding: 40px; text-align: center;">
			<h2 id="sync-status-title"><?php printf( __( 'Syncing %s...', 'charts' ), $type === 'artist' ? 'Artist Profiles' : ( $type === 'video' ? 'Clip Metadata' : 'Track Metadata' ) ); ?></h2>
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
					<div><span id="res-linked-label"><?php echo $type === 'video' ? 'YouTube Linked' : 'Spotify Linked'; ?></span>: <b id="res-spotify">0</b></div>
					<div id="res-platform-label"><?php echo $type === 'artist' ? 'YouTube Enriched' : ( $type === 'video' ? 'Thumbnails Updated' : 'Covers Updated' ); ?>: <b id="res-platform">0</b></div>
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

<!-- Hidden form for promotion -->
<form method="post" id="promote-entity-form" style="display:none;">
	<?php wp_nonce_field( 'charts_admin_action' ); ?>
	<input type="hidden" name="charts_action" value="promote_entity">
	<input type="hidden" name="id" id="promote-entity-id" value="">
	<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
</form>

<div id="entity-merge-modal" class="entity-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="entity-merge-title">
	<div class="entity-modal-panel">
		<div class="entity-modal-header">
			<div><span class="dashicons dashicons-admin-links" aria-hidden="true"></span><h2 id="entity-merge-title"><?php esc_html_e( 'Merge entities', 'charts' ); ?></h2></div>
			<button type="button" class="entity-modal-close" aria-label="<?php esc_attr_e( 'Close', 'charts' ); ?>">&times;</button>
		</div>
		<p class="entity-merge-source"><?php esc_html_e( 'Current record:', 'charts' ); ?> <strong id="entity-merge-source-name"></strong></p>
		<label class="entity-merge-search-label" for="entity-merge-search"><?php esc_html_e( 'Search for the record to keep', 'charts' ); ?></label>
		<div class="entity-merge-searchbox"><span class="dashicons dashicons-search" aria-hidden="true"></span><input type="search" id="entity-merge-search" placeholder="<?php esc_attr_e( 'Type at least 2 characters…', 'charts' ); ?>" autocomplete="off"><span id="entity-merge-search-spinner" class="dashicons dashicons-update" style="display:none" aria-hidden="true"></span></div>
		<div id="entity-merge-results" class="entity-merge-results"><p class="entity-merge-empty"><?php esc_html_e( 'Search the library and choose the master record.', 'charts' ); ?></p></div>
		<div class="entity-modal-footer"><span id="entity-merge-selection-label"><?php esc_html_e( 'No merge target selected', 'charts' ); ?></span><div><button type="button" class="button entity-modal-cancel"><?php esc_html_e( 'Cancel', 'charts' ); ?></button><button type="button" class="button button-primary" id="entity-merge-confirm" disabled><?php esc_html_e( 'Merge records', 'charts' ); ?></button></div></div>
	</div>
</div>

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
		formData.append('action', type === 'artist' ? 'charts_sync_artists' : (type === 'video' ? 'charts_sync_videos' : 'charts_sync_tracks'));
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
				totalSpotify += (type === 'video') ? (res.data.youtube_linked || 0) : (res.data.spotify_linked || 0);
				totalPlatform += (type === 'artist') ? (res.data.youtube_linked || 0) : (res.data.covers_updated || 0);

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

// Bulk Merge Feature
let bulkMergeType = '<?php echo esc_js($type); ?>s'; // artists or tracks

let mergeSourceEntity = null;
let mergeMasterEntity = null;
let mergeSearchTimer = null;

function closeEntityMergeModal() {
	const modal = document.getElementById('entity-merge-modal');
	if (modal) modal.style.display = 'none';
}

function renderEntityMergeResults(entities) {
	const results = document.getElementById('entity-merge-results');
	results.innerHTML = '';
	if (!entities.length) {
		const empty = document.createElement('p');
		empty.className = 'entity-merge-empty';
		empty.textContent = '<?php echo esc_js( __( 'No matching records found.', 'charts' ) ); ?>';
		results.appendChild(empty);
		return;
	}
	entities.forEach(function(entity) {
		const id = parseInt(entity.id, 10);
		if (!id || (mergeSourceEntity && id === mergeSourceEntity.id)) return;
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'entity-merge-result';
		button.dataset.id = String(id);
		button.dataset.name = entity.title || entity.name || '';
		if (entity.image) {
			const image = document.createElement('img');
			image.src = entity.image;
			image.alt = '';
			button.appendChild(image);
		}
		const copy = document.createElement('span');
		copy.className = 'entity-merge-result-copy';
		const name = document.createElement('strong');
		name.textContent = entity.title || entity.name || ('#' + id);
		copy.appendChild(name);
		if (entity.subtitle) {
			const subtitle = document.createElement('small');
			subtitle.textContent = entity.subtitle;
			copy.appendChild(subtitle);
		}
		const badge = document.createElement('span');
		badge.className = 'entity-merge-id';
		badge.textContent = '#' + id;
		button.append(copy, badge);
		button.addEventListener('click', function() {
			mergeMasterEntity = { id:id, name:entity.title || entity.name || ('#' + id) };
			results.querySelectorAll('.entity-merge-result').forEach(function(row) { row.classList.remove('is-selected'); });
			button.classList.add('is-selected');
			document.getElementById('entity-merge-selection-label').textContent = '<?php echo esc_js( __( 'Keep:', 'charts' ) ); ?> ' + mergeMasterEntity.name;
			document.getElementById('entity-merge-confirm').disabled = false;
		});
		results.appendChild(button);
	});
	if (!results.children.length) {
		const empty = document.createElement('p');
		empty.className = 'entity-merge-empty';
		empty.textContent = '<?php echo esc_js( __( 'No other records found.', 'charts' ) ); ?>';
		results.appendChild(empty);
	}
}

function searchEntityMergeTargets(query) {
	const results = document.getElementById('entity-merge-results');
	const spinner = document.getElementById('entity-merge-search-spinner');
	if (!query || query.trim().length < 2) {
		results.innerHTML = '<p class="entity-merge-empty"><?php echo esc_js( __( 'Type at least 2 characters to search.', 'charts' ) ); ?></p>';
		return;
	}
	spinner.style.display = 'inline-block';
	const data = new FormData();
	data.append('action', 'charts_search_entities');
	data.append('nonce', '<?php echo wp_create_nonce( 'charts_admin_action' ); ?>');
	data.append('type', '<?php echo esc_js( $type ); ?>');
	data.append('query', query.trim());
	fetch(ajaxurl, { method:'POST', body:data })
		.then(function(response) { return response.json(); })
		.then(function(response) {
			if (response.success) renderEntityMergeResults(Array.isArray(response.data) ? response.data : []);
			else results.innerHTML = '<p class="entity-merge-error"><?php echo esc_js( __( 'Search failed. Try again.', 'charts' ) ); ?></p>';
		})
		.catch(function() { results.innerHTML = '<p class="entity-merge-error"><?php echo esc_js( __( 'Could not reach the server.', 'charts' ) ); ?></p>'; })
		.finally(function() { spinner.style.display = 'none'; });
}

document.addEventListener('click', function(event) {
	const option = event.target.closest('[data-entity-merge]');
	if (option) {
		event.preventDefault();
		const details = option.closest('details');
		if (details) details.open = false;
		mergeSourceEntity = { id:parseInt(option.dataset.id, 10), name:option.dataset.name || '' };
		mergeMasterEntity = null;
		document.getElementById('entity-merge-source-name').textContent = mergeSourceEntity.name;
		document.getElementById('entity-merge-search').value = '';
		document.getElementById('entity-merge-selection-label').textContent = '<?php echo esc_js( __( 'No merge target selected', 'charts' ) ); ?>';
		document.getElementById('entity-merge-confirm').disabled = true;
		document.getElementById('entity-merge-results').innerHTML = '<p class="entity-merge-empty"><?php echo esc_js( __( 'Search the library and choose the master record.', 'charts' ) ); ?></p>';
		document.getElementById('entity-merge-modal').style.display = 'flex';
		if (option.dataset.entityMerge === 'search') {
			document.getElementById('entity-merge-search').value = mergeSourceEntity.name;
			searchEntityMergeTargets(mergeSourceEntity.name);
		} else {
			document.getElementById('entity-merge-search').focus();
		}
	}
	if (event.target.closest('.entity-modal-close, .entity-modal-cancel')) closeEntityMergeModal();
	if (event.target.id === 'entity-merge-modal') closeEntityMergeModal();
});

document.getElementById('entity-merge-search').addEventListener('input', function() {
	window.clearTimeout(mergeSearchTimer);
	const query = this.value;
	mergeSearchTimer = window.setTimeout(function() { searchEntityMergeTargets(query); }, 250);
});

document.getElementById('entity-merge-confirm').addEventListener('click', function() {
	if (!mergeSourceEntity || !mergeMasterEntity || mergeSourceEntity.id === mergeMasterEntity.id) return;
	const button = this;
	if (!window.confirm('<?php echo esc_js( __( 'Merge the current record into the selected master? The current record will be removed after its chart history is moved.', 'charts' ) ); ?>')) return;
	button.disabled = true;
	button.textContent = '<?php echo esc_js( __( 'Merging…', 'charts' ) ); ?>';
	const data = new FormData();
	data.append('action', 'charts_process_merge');
	data.append('_wpnonce', '<?php echo wp_create_nonce( 'charts_admin_action' ); ?>');
	data.append('type', bulkMergeType);
	data.append('master_id', mergeMasterEntity.id);
	data.append('duplicate_ids[]', mergeSourceEntity.id);
	fetch(ajaxurl, { method:'POST', body:data })
		.then(function(response) { return response.json(); })
		.then(function(response) {
			if (response.success) window.location.reload();
			else { window.alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Merge failed.', 'charts' ) ); ?>'); button.disabled = false; button.textContent = '<?php echo esc_js( __( 'Merge records', 'charts' ) ); ?>'; }
		})
		.catch(function() { window.alert('<?php echo esc_js( __( 'Could not reach the server.', 'charts' ) ); ?>'); button.disabled = false; button.textContent = '<?php echo esc_js( __( 'Merge records', 'charts' ) ); ?>'; });
});

document.addEventListener('keydown', function(event) { if (event.key === 'Escape') closeEntityMergeModal(); });

window.handleBulkActionSubmit = function(e) {
	const select = document.getElementById('bulk_action_type');
	const action = select.value;
	
	if (action === 'bulk_merge') {
		e.preventDefault();
		openBulkMergeModal();
		return false;
	} else if (action === 'bulk_find_duplicates') {
		e.preventDefault();
		openSmartDeduplicatorModal();
		return false;
	} else if (action === 'bulk_sync_selected') {
		e.preventDefault();
		const checked = document.querySelectorAll('.entity-checkbox:checked');
		if (!checked.length) {
			alert('<?php echo esc_js( __( 'Select at least one item to sync.', 'charts' ) ); ?>');
			return false;
		}
		document.getElementById('sync-selected-trigger').click();
		return false;
	} else if (action !== '') {
		if (confirm('<?php _e( "Are you sure you want to apply this action to all selected items?", "charts" ); ?>')) {
			document.getElementById('entities-bulk-form').submit();
		}
	} else {
		alert('Please select a bulk action first.');
	}
};

window.openBulkMergeModal = function() {
	// Gather all checked items
	const checkboxes = document.querySelectorAll('.entity-checkbox:checked');
	if (checkboxes.length < 2) {
		alert('You must select at least TWO entities to merge.');
		return;
	}

	const candidates = [];
	checkboxes.forEach(cb => {
		const row = cb.closest('tr');
		const id = cb.value;
		const name = row.querySelector('.charts-primary').innerText.trim();
		candidates.push({ id, name });
	});

	// Populate the modal
	const listDiv = document.getElementById('bulk-merge-candidates-list');
	listDiv.innerHTML = '';
	
	candidates.forEach((c, index) => {
		listDiv.innerHTML += `
			<label style="display:flex; align-items:center; gap:10px; padding:12px; border-bottom:1px solid #eee; cursor:pointer;">
				<input type="radio" name="bulk_merge_master" value="${c.id}" ${index === 0 ? 'checked' : ''} style="margin:0;">
				<div style="font-weight:bold; color:#333;">${c.name} <span style="font-size:11px; color:#999; font-weight:normal;">(ID: ${c.id})</span></div>
			</label>
		`;
	});

	document.getElementById('bulk-merge-modal').style.display = 'flex';
};

window.closeBulkMergeModal = function() {
	document.getElementById('bulk-merge-modal').style.display = 'none';
};

window.confirmBulkMerge = function() {
	const masterRadio = document.querySelector('input[name="bulk_merge_master"]:checked');
	if (!masterRadio) return;

	const masterId = masterRadio.value;
	const checkboxes = document.querySelectorAll('.entity-checkbox:checked');
	
	const duplicateIds = [];
	checkboxes.forEach(cb => {
		if (cb.value !== masterId) {
			duplicateIds.push(cb.value);
		}
	});

	if (duplicateIds.length === 0) {
		alert('No duplicates selected to merge into the master.');
		return;
	}

	if (!confirm('Are you sure? All duplicate entities will be permanently merged into the selected master.')) return;

	const btn = document.getElementById('bulk-merge-confirm-btn');
	btn.disabled = true;
	btn.innerText = 'Merging...';

	const formData = new FormData();
	formData.append('action', 'charts_process_merge');
	formData.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	formData.append('type', bulkMergeType);
	formData.append('master_id', masterId);
	
	duplicateIds.forEach(id => {
		formData.append('duplicate_ids[]', id);
	});

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(res => res.json())
	.then(res => {
		if (res.success) {
			alert('Bulk Merge successful!');
			window.location.reload();
		} else {
			alert('Merge failed: ' + res.data.message);
			btn.disabled = false;
			btn.innerText = 'Confirm Merge';
		}
	});
};

window.openSmartDeduplicatorModal = function() {
	document.getElementById('smart-dedup-modal').style.display = 'flex';
	const resultsDiv = document.getElementById('smart-dedup-results');
	resultsDiv.innerHTML = '<div style="padding: 40px; text-align: center; color: #666;"><span class="dashicons dashicons-update" style="animation: spin 2s linear infinite;"></span><br>Scanning database for duplicates...</div>';

	const formData = new FormData();
	formData.append('action', 'charts_resolve_potential_duplicates');
	formData.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	formData.append('type', bulkMergeType);

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(res => res.json())
	.then(res => {
		if (res.success && res.data.clusters) {
			const clusters = res.data.clusters.map(cluster => ({
				normalized_name: cluster.master.name,
				entities: [
					Object.assign({ is_master: true }, cluster.master),
					...(cluster.duplicates || []).map(entity => Object.assign({ is_master: false }, entity))
				]
			}));
			if (clusters.length === 0) {
				resultsDiv.innerHTML = '<div style="padding: 40px; text-align: center; color: #166534; background: #f0fdf4;">No duplicates found! Your database is clean.</div>';
				return;
			}

			const mergeAllBtnFooter = document.getElementById('btn-smart-merge-all');
			if (mergeAllBtnFooter) {
				mergeAllBtnFooter.style.display = clusters.length > 0 ? 'inline-flex' : 'none';
				mergeAllBtnFooter.disabled = false;
				mergeAllBtnFooter.innerHTML = `<span class="dashicons dashicons-admin-links" style="margin-top:2px;"></span> Merge All (${clusters.length} Clusters)`;
			}

			let html = `
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:12px; border-bottom:1px solid #e5e7eb;">
					<p style="margin:0; font-size: 13px; color: #475569;">
						We found <strong>${clusters.length}</strong> clusters of duplicate entities.
					</p>
					<button class="charts-btn-primary" onclick="mergeAllSmartClusters()" id="btn-smart-merge-all-top" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none; color: #fff; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; border-radius: 6px; cursor: pointer;">
						<span class="dashicons dashicons-admin-links" style="margin-top:1px;"></span> Merge All Clusters
					</button>
				</div>
			`;
			
			clusters.forEach((cluster, idx) => {
				html += `<div id="cluster-card-${idx}" style="background: #fafafa; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
					<h4 style="margin: 0 0 10px 0; font-size: 14px;">Cluster ${idx + 1}: <span style="color:#d946ef;">${cluster.normalized_name}</span></h4>
					<div style="display:flex; flex-direction:column; gap:8px; margin-bottom: 15px;">`;
				
				cluster.entities.forEach(ent => {
					const idMatches = [
						ent.spotify_id ? 'Spotify ID: ' + ent.spotify_id : '',
						ent.youtube_id ? 'YouTube ID: ' + ent.youtube_id : ''
					].filter(Boolean).join(' · ');
					html += `<div style="display:flex; align-items:center; gap:10px; font-size: 12px; background: #fff; padding: 8px; border: 1px solid #e5e7eb; border-radius: 4px;">
						<span style="color: #999;">ID: ${ent.id}</span>
						<strong>${ent.name}</strong>
						${idMatches ? '<span class="charts-badge charts-badge-neutral">' + idMatches + '</span>' : ''}
						${ent.confidence ? '<span class="charts-badge charts-badge-neutral">' + ent.confidence + '% match</span>' : ''}
						${ent.is_master ? '<span class="charts-badge" style="background:#d946ef; color:#fff;">Suggested Master</span>' : ''}
					</div>`;
				});

				html += `</div>
					<button class="charts-btn-primary" onclick="processSmartMerge(${idx})" id="btn-smart-merge-${idx}" style="background: #111; border-color: #111;">
						<span class="dashicons dashicons-admin-links" style="margin-top:2px;"></span> Auto-Merge Cluster ${idx + 1}
					</button>
				</div>`;
			});

			// Store clusters for processing
			window.smartClusters = clusters;
			resultsDiv.innerHTML = html;

		} else {
			resultsDiv.innerHTML = '<div style="padding: 20px; color: red;">Failed to scan: ' + (res.data?.message || 'Unknown error') + '</div>';
		}
	})
	.catch(err => {
		resultsDiv.innerHTML = '<div style="padding: 20px; color: red;">Network error: ' + err.message + '</div>';
	});
};

window.closeSmartDeduplicatorModal = function() {
	document.getElementById('smart-dedup-modal').style.display = 'none';
};

window.mergeAllSmartClusters = async function() {
	if (!window.smartClusters || !window.smartClusters.length) {
		alert('No clusters available to merge.');
		return;
	}

	const batch = [];
	window.smartClusters.forEach((cluster, idx) => {
		const master = cluster.entities.find(e => e.is_master);
		const duplicates = cluster.entities.filter(e => !e.is_master).map(e => e.id);
		if (master && duplicates.length > 0) {
			batch.push({
				cluster_idx: idx,
				master_id: master.id,
				duplicate_ids: duplicates
			});
		}
	});

	if (batch.length === 0) {
		alert('No duplicates found in clusters.');
		return;
	}

	if (!confirm(`Merge all ${batch.length} duplicate clusters into their suggested masters? This cannot be undone.`)) {
		return;
	}

	const topBtn = document.getElementById('btn-smart-merge-all-top');
	const footerBtn = document.getElementById('btn-smart-merge-all');
	if (topBtn) { topBtn.disabled = true; topBtn.innerHTML = 'Merging All...'; }
	if (footerBtn) { footerBtn.disabled = true; footerBtn.innerHTML = 'Merging All...'; }

	// Set each cluster button to pending
	batch.forEach(item => {
		const b = document.getElementById('btn-smart-merge-' + item.cluster_idx);
		if (b) { b.disabled = true; b.innerHTML = 'Merging...'; }
	});

	const formData = new FormData();
	formData.append('action', 'charts_process_merge');
	formData.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	formData.append('type', bulkMergeType);
	formData.append('batch', JSON.stringify(batch));

	try {
		const response = await fetch(ajaxurl, { method: 'POST', body: formData });
		const res = await response.json();

		if (res.success) {
			batch.forEach(item => {
				const b = document.getElementById('btn-smart-merge-' + item.cluster_idx);
				if (b) {
					b.innerHTML = 'Merged!';
					b.style.background = '#166534';
					b.style.borderColor = '#166534';
				}
				const card = document.getElementById('cluster-card-' + item.cluster_idx);
				if (card) {
					card.style.opacity = '0.7';
					card.style.background = '#f0fdf4';
				}
			});

			if (topBtn) {
				topBtn.innerHTML = 'All Merged!';
				topBtn.style.background = '#166534';
			}
			if (footerBtn) {
				footerBtn.innerHTML = 'All Merged!';
				footerBtn.style.background = '#166534';
			}

			alert(res.data?.message || 'All duplicate clusters merged successfully!');
		} else {
			alert('Merge failed: ' + (res.data?.message || 'Unknown error'));
			if (topBtn) { topBtn.disabled = false; topBtn.innerHTML = 'Merge All Clusters'; }
			if (footerBtn) { footerBtn.disabled = false; footerBtn.innerHTML = 'Merge All Clusters'; }
			batch.forEach(item => {
				const b = document.getElementById('btn-smart-merge-' + item.cluster_idx);
				if (b) { b.disabled = false; b.innerHTML = 'Retry'; }
			});
		}
	} catch (err) {
		alert('Network error while merging all: ' + err.message);
		if (topBtn) { topBtn.disabled = false; topBtn.innerHTML = 'Merge All Clusters'; }
		if (footerBtn) { footerBtn.disabled = false; footerBtn.innerHTML = 'Merge All Clusters'; }
	}
};

window.processSmartMerge = function(clusterIndex) {
	const cluster = window.smartClusters[clusterIndex];
	const master = cluster.entities.find(e => e.is_master);
	const duplicates = cluster.entities.filter(e => !e.is_master).map(e => e.id);

	if (!master || duplicates.length === 0) return;

	if (!confirm('Merge these ' + duplicates.length + ' duplicates into ID ' + master.id + '?')) return;

	const btn = document.getElementById('btn-smart-merge-' + clusterIndex);
	btn.disabled = true;
	btn.innerHTML = 'Merging...';

	const formData = new FormData();
	formData.append('action', 'charts_process_merge');
	formData.append('_wpnonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');
	formData.append('type', bulkMergeType);
	formData.append('master_id', master.id);
	
	duplicates.forEach(id => {
		formData.append('duplicate_ids[]', id);
	});

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(res => res.json())
	.then(res => {
		if (res.success) {
			btn.innerHTML = 'Merged!';
			btn.style.background = '#166534';
			btn.style.borderColor = '#166534';
			const card = document.getElementById('cluster-card-' + clusterIndex);
			if (card) {
				card.style.opacity = '0.7';
				card.style.background = '#f0fdf4';
			}
		} else {
			alert('Merge failed: ' + res.data.message);
			btn.disabled = false;
			btn.innerHTML = 'Retry';
		}
	})
	.catch(err => {
		alert('Network error: ' + err.message);
		btn.disabled = false;
		btn.innerHTML = 'Retry';
	});
};

</script>


<style>

.kc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); display: flex; flex-direction: column; position: relative; overflow: hidden; }
.kc-card .kc-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.kc-card .kc-value { font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 8px; }
.kc-card .kc-card-icon { position: absolute; top: 20px; right: 20px; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.kc-card .kc-card-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }

.charts-input { background: #fdfdfd; border: 1px solid #dcdfe6; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #334155; transition: border-color 0.2s, box-shadow 0.2s; }
.charts-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); outline: none; }
.charts-btn-secondary { background: #fff; border: 1px solid #dcdfe6; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; color: #475569; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
.charts-btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }
.charts-btn-create { background: #0f172a; color: #fff; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; text-decoration: none; transition: background 0.2s; display: inline-flex; align-items: center; }
.charts-btn-create:hover { background: #1e293b; color: #fff; }
.charts-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.charts-table th { background: #f8fafc; color: #64748b; font-weight: 600; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; }
.charts-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
.charts-table tr:hover td { background: #f8fafc; }
.entity-row-action { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 32px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
.entity-row-action:hover { border-color: #cbd5e1; color: #0f172a; background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
.entity-row-action .dashicons { width: 14px; height: 14px; font-size: 14px; color: #64748b; }
.entity-row-action:hover .dashicons { color: #334155; }
.entity-edit-action { color: #2563eb; }
.entity-edit-action:hover { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; }
.entity-edit-action .dashicons { color: #3b82f6; }
.entity-delete-action { color: #dc2626; }
.entity-delete-action:hover { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
.entity-delete-action .dashicons { color: #ef4444; }
.entity-merge-trigger { color: #7c3aed; }
.entity-merge-trigger:hover { color: #6d28d9; background: #f5f3ff; border-color: #ddd6fe; }
.entity-merge-trigger .dashicons { color: #8b5cf6; }
.entity-merge-options { position: absolute; z-index: 10020; top: calc(100% + 5px); right: 0; min-width: 200px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }
.entity-merge-options button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px 12px; border: 0; border-radius: 8px; background: transparent; color: #475569; text-align: left; font-size: 13px; font-weight: 500; cursor: pointer; transition: background 0.15s, color 0.15s; }
.entity-merge-options button:hover { background: #f1f5f9; color: #0f172a; }
</style>
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }

.entity-row-action .dashicons{width:14px;height:14px;font-size:14px}

.entity-merge-menu{position:relative;display:inline-block}.entity-merge-menu>summary{list-style:none}.entity-merge-menu>summary::-webkit-details-marker{display:none}
.entity-modal{position:fixed;inset:0;z-index:100100;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58)}.entity-modal-panel{width:min(560px,100%);max-height:min(740px,90vh);overflow:auto;border:1px solid #e4e8f0;border-radius:14px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.24)}.entity-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #edf0f5}.entity-modal-header>div{display:flex;align-items:center;gap:10px;color:#5b42b5}.entity-modal-header h2{margin:0;color:#1e293b;font-size:17px}.entity-modal-close{width:32px;height:32px;border:0;border-radius:7px;background:#f3f5f8;color:#667085;font-size:22px;cursor:pointer}.entity-merge-source{margin:16px 22px;padding:10px 12px;border-radius:8px;background:#f7f8fc;color:#667085;font-size:12px}.entity-merge-source strong{color:#25314a}.entity-merge-search-label{display:block;margin:0 22px 7px;color:#344054;font-size:12px;font-weight:700}.entity-merge-searchbox{display:flex;align-items:center;gap:8px;margin:0 22px 12px;padding:0 11px;border:1px solid #d9deea;border-radius:8px;color:#98a2b3}.entity-merge-searchbox:focus-within{border-color:#6d5bd0;box-shadow:0 0 0 3px rgba(109,91,208,.12)}.entity-merge-searchbox input{width:100%;height:42px;border:0!important;box-shadow:none!important;outline:0!important}.entity-merge-searchbox #entity-merge-search-spinner{animation:spin 1s linear infinite}.entity-merge-results{max-height:330px;min-height:70px;overflow:auto;margin:0 22px;border:1px solid #edf0f5;border-radius:8px}.entity-merge-empty,.entity-merge-error{margin:0;padding:22px;color:#8490a2;text-align:center;font-size:12px}.entity-merge-error{color:#b4232f}.entity-merge-result{display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border:0;border-bottom:1px solid #f0f2f6;background:#fff;text-align:left;cursor:pointer}.entity-merge-result:last-child{border-bottom:0}.entity-merge-result:hover,.entity-merge-result.is-selected{background:#f5f3ff}.entity-merge-result.is-selected{box-shadow:inset 3px 0 #6d5bd0}.entity-merge-result img{width:34px;height:34px;border-radius:50%;object-fit:cover}.entity-merge-result-copy{display:flex;flex:1;flex-direction:column;gap:3px;min-width:0}.entity-merge-result-copy strong{overflow:hidden;color:#263248;text-overflow:ellipsis;white-space:nowrap;font-size:12px}.entity-merge-result-copy small{color:#8993a4;font-size:10px}.entity-merge-id{color:#8a93a3;font:11px monospace}.entity-modal-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:15px;padding:14px 22px;border-top:1px solid #edf0f5;color:#7a8496;font-size:11px}.entity-modal-footer>div{display:flex;gap:8px}.entity-modal-footer .button-primary{background:#5541b5;border-color:#5541b5}.entity-modal-footer .button-primary:disabled{opacity:.45;cursor:not-allowed}
@media(max-width:782px){.entity-modal-footer{align-items:flex-start;flex-direction:column}}
</style>

<!-- Bulk Merge Modal UI -->
<div id="bulk-merge-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
	<div style="background: #fff; width: 450px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;">
		<div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fdf4ff;">
			<h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #d946ef;">Select Master Entity</h3>
			<button onclick="closeBulkMergeModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #999;">&times;</button>
		</div>
		<div style="padding: 20px;">
			<p style="margin-top: 0; font-size: 13px; color: #666; margin-bottom: 15px;">
				You have selected multiple entities to merge. Please select which one should be the <strong>Master Record</strong>. The others will be merged into it and deleted.
			</p>
			<div id="bulk-merge-candidates-list" style="max-height: 250px; overflow-y: auto; background: #fafafa; border-radius: 6px; border: 1px solid #eee;">
				<!-- Dynamic content here -->
			</div>
		</div>
		<div style="padding: 15px 20px; background: #f9fafb; border-top: 1px solid #eee; text-align: right;">
			<button class="charts-btn-secondary" onclick="closeBulkMergeModal()">Cancel</button>
			<button id="bulk-merge-confirm-btn" class="charts-btn-primary" onclick="confirmBulkMerge()" style="margin-left: 10px; background: #d946ef; border-color: #d946ef;">Confirm Merge</button>
		</div>
	</div>
</div>

<!-- Smart Deduplicator Modal UI -->
<div id="smart-dedup-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
	<div style="background: #fff; width: 600px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;">
		<div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #111;">
			<h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #fff;">
				<span class="dashicons dashicons-admin-generic" style="color: #d946ef;"></span> Smart Deduplicator
			</h3>
			<button onclick="closeSmartDeduplicatorModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #fff;">&times;</button>
		</div>
		<div id="smart-dedup-results" style="padding: 20px; max-height: 60vh; overflow-y: auto;">
			<!-- Results dynamically injected here -->
		</div>
		<div style="padding: 15px 20px; background: #f9fafb; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
			<div>
				<button id="btn-smart-merge-all" class="charts-btn-primary" onclick="mergeAllSmartClusters()" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none; color: #fff; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-admin-links" style="margin-top:2px;"></span> Merge All Clusters
				</button>
			</div>
			<div>
				<button class="charts-btn-secondary" onclick="window.location.reload()">Done</button>
			</div>
		</div>
	</div>
</div>
