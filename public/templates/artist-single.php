<?php
/**
 * Kontentainment Charts — Artist Profile
 */
\Charts\Core\StandaloneLayout::get_header();
global $wpdb;

$artist_slug = get_query_var( 'charts_artist_slug' );
$artist = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s",
	$artist_slug
) );

if ( ! $artist ) {
	echo '<div class="kc-empty"><h1>Artist Not Found</h1></div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// 1. Fetch Chart Appearances (All entries for this artist, including track/video relationships)
$entries = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, s.platform, s.source_name, p.period_start, p.period_end
	FROM {$wpdb->prefix}charts_entries e
	JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
	JOIN {$wpdb->prefix}charts_periods p ON p.id = e.period_id
	WHERE (e.item_id = %d AND e.item_type = 'artist')
	   OR (e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d))
	   OR (e.item_type = 'video' AND e.item_id IN (SELECT video_id FROM {$wpdb->prefix}charts_video_artists WHERE artist_id = %d))
	ORDER BY p.period_start DESC, e.rank_position ASC
", $artist->id, $artist->id, $artist->id ) );

// 2. Fetch Unique Tracks / Videos
$track_ids = array_filter( array_unique( array_column( $entries, 'item_id' ) ) );
$items = array();
if ( ! empty( $track_ids ) ) {
	// Group items by their name/title
	foreach ( $entries as $e ) {
		$key = $e->track_name . '|' . $e->item_type;
		if ( ! isset( $items[ $key ] ) ) {
			$items[ $key ] = (object) array(
				'title'     => $e->track_name,
				'type'      => $e->item_type,
				'image'     => $e->cover_image,
				'best_rank' => $e->rank_position,
				'history'   => array(),
			);
		}
		$items[ $key ]->best_rank = min( $items[ $key ]->best_rank, $e->rank_position );
		$items[ $key ]->history[] = $e;
	}
}

// Stats
$total_appearances = count( $entries );
$best_ever_rank = ! empty( $entries ) ? min( array_column( $entries, 'rank_position' ) ) : 'N/A';
$num_unique_items = count( $items );

// Hero image
$hero_img = !empty($artist->image) ? $artist->image : 'https://www.gravatar.com/avatar/' . md5($artist->display_name) . '?d=mp&s=400';
?>

<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>">

<div class="kc-root <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
	
	<!-- Premium Artist Header -->
	<header class="kc-artist-header">
		<div class="kc-container" style="display: flex; align-items: center; gap: 48px;">
			<img src="<?php echo esc_url( $hero_img ); ?>" class="kc-artist-header-img animate-fade-in-up" alt="<?php echo esc_attr( $artist->display_name ); ?>">
			
			<div class="kc-artist-header-info animate-fade-in-up" style="animation-delay: 0.1s;">
				<div class="kc-brand-name" style="margin-bottom: 8px;">Featured Artist</div>
				<h1><?php echo esc_html( $artist->display_name ); ?></h1>
				
				<div class="kc-stats-bar">
					<div class="kc-stat-item">
						<span class="kc-stat-val">#<?php echo $best_ever_rank; ?></span>
						<span class="kc-stat-lbl">Best Rank</span>
					</div>
					<div class="kc-stat-item">
						<span class="kc-stat-val"><?php echo $num_unique_items; ?></span>
						<span class="kc-stat-lbl">Unique Items</span>
					</div>
					<div class="kc-stat-item">
						<span class="kc-stat-val"><?php echo $total_appearances; ?></span>
						<span class="kc-stat-lbl">Appearances</span>
					</div>
				</div>
			</div>
		</div>
	</header>

	<main class="kc-container" style="padding: 60px 0 120px;">
		
		<div class="animate-fade-in-up" style="animation-delay: 0.2s;">
			<a href="<?php echo home_url('/charts/artists/'); ?>" class="kc-view-btn" style="text-decoration: none;">&larr; Back to Artists</a>
		</div>

		<!-- Tracks & Videos Grid -->
		<?php if ( ! empty( $items ) ) : ?>
		<section class="kc-table-section animate-fade-in-up" style="animation-delay: 0.3s;">
			<h2>Portfolio</h2>
			<div class="kc-chart-list">
				<?php foreach ( $items as $item ) : ?>
					<div class="kc-row">
						<div class="kc-row-main" style="grid-template-columns: 60px 1fr 120px 120px;">
							<div class="kc-row-img-wrap">
								<?php if ( $item->image ) : ?>
									<img src="<?php echo esc_url( $item->image ); ?>" class="kc-row-img">
								<?php else: ?>
									<div class="kc-row-img-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="kc-row-info">
								<div class="kc-row-title"><?php echo esc_html( $item->title ); ?></div>
								<div class="kc-row-subtitle"><?php echo ucfirst($item->type); ?></div>
							</div>
							<div class="kc-stat-item">
								<span class="kc-stat-val" style="font-size: 1.1rem;">#<?php echo $item->best_rank; ?></span>
								<span class="kc-stat-lbl">Best</span>
							</div>
							<div class="kc-stat-item">
								<span class="kc-stat-val" style="font-size: 1.1rem;"><?php echo count($item->history); ?></span>
								<span class="kc-stat-lbl">Weeks</span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Complete History Table -->
		<section class="kc-table-section animate-fade-in-up" style="animation-delay: 0.4s;">
			<h2>Complete Chart History</h2>
			<div class="kc-chart-list">
				<div class="kc-row header" style="border: none; opacity: 0.4; font-size: 10px; font-weight: 800; text-transform: uppercase;">
					<div class="kc-row-main" style="grid-template-columns: 60px 1fr 120px 120px;">
						<div>Rank</div>
						<div>Date / Source</div>
						<div>Position</div>
						<div>Movement</div>
					</div>
				</div>
				<?php foreach ( $entries as $e ) : ?>
					<div class="kc-row">
						<div class="kc-row-main" style="grid-template-columns: 60px 1fr 120px 120px;">
							<div class="kc-row-rank">#<?php echo $e->rank_position; ?></div>
							<div class="kc-row-info">
								<div class="kc-row-title" style="font-size: 13px;"><?php echo date('M j, Y', strtotime($e->period_start)); ?></div>
								<div class="kc-row-subtitle"><?php echo esc_html( $e->source_name ); ?></div>
							</div>
							<div>
								<span class="kc-badge platform-<?php echo esc_attr(strtolower($e->platform)); ?>">
									<?php echo esc_html($e->platform); ?>
								</span>
							</div>
							<div class="kc-row-movement <?php echo $e->movement_direction; ?>">
								<?php if ($e->movement_direction === 'up'): ?>
									&uarr; <?php echo $e->movement_value; ?>
								<?php elseif ($e->movement_direction === 'down'): ?>
									&darr; <?php echo $e->movement_value; ?>
								<?php elseif ($e->movement_direction === 'new'): ?>
									<span class="kc-badge" style="background:#6366f1">NEW</span>
								<?php else: ?>
									&minus;
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
