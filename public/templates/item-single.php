<?php
/**
 * Kontentainment Charts — Item Intelligence (Track/Video)
 */
\Charts\Core\StandaloneLayout::get_header();
global $wpdb;

$type = get_query_var( 'charts_item_type' );
$slug = get_query_var( 'charts_item_slug' );

// Find the item by slugifying track_name in entries (temporary approach)
$entries_table = $wpdb->prefix . 'charts_entries';
$sources_table = $wpdb->prefix . 'charts_sources';
$periods_table = $wpdb->prefix . 'charts_periods';

// In a real system, we'd have a 'tracks' table. For now, we find entries matching the name.
// We'll use a hacky slug match for this demo level.
$item_name = str_replace('-', ' ', $slug);

$entry = $wpdb->get_row( $wpdb->prepare( "
	SELECT e.*, s.platform, s.source_name, p.period_start 
	FROM $entries_table e
	JOIN $sources_table s ON s.id = e.source_id
	JOIN $periods_table p ON p.id = e.period_id
	WHERE LOWER(e.track_name) = %s 
	   OR REPLACE(LOWER(e.track_name), ' ', '-') = %s
	ORDER BY e.rank_position ASC, p.period_start DESC
	LIMIT 1
", strtolower($item_name), strtolower($slug) ) );

if ( ! $entry ) {
	echo '<div class="kc-container" style="padding: 120px 0; text-align: center;">';
	echo '<h1 class="kc-hero-title">Item Not Found</h1>';
	echo '<p style="color: var(--k-text-dim);">The intelligence system has no record of "' . esc_html($slug) . '".</p>';
	echo '<a href="' . home_url('/charts/') . '" class="kc-view-btn" style="margin-top: 40px; display: inline-block;">← Back to Charts</a>';
	echo '</div>';
	\Charts\Core\StandaloneLayout::get_footer();
	exit;
}

// Fetch history for this item
$history = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, p.period_start, s.source_name
	FROM $entries_table e
	JOIN $periods_table p ON p.id = e.period_id
	JOIN $sources_table s ON s.id = e.source_id
	WHERE e.track_name = %s
	ORDER BY p.period_start DESC
", $entry->track_name ) );

$best_rank = min( array_column( $history, 'rank_position' ) );
$total_weeks = count( $history );
?>

<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>">

<div class="kc-root <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
	
	<header class="kc-hero" style="padding-bottom: 60px; background: linear-gradient(to bottom, #111, var(--k-bg));">
		<div class="kc-container">
			<nav class="sc-breadcrumb" style="margin-bottom: 32px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
				<a href="<?php echo home_url('/charts/'); ?>" style="color: var(--k-accent);">Intelligence</a> / 
				<span><?php echo esc_html(strtoupper($type)); ?></span>
			</nav>

			<div style="display: flex; gap: 40px; align-items: flex-end;">
				<?php if ( $entry->cover_image ) : ?>
					<img src="<?php echo esc_url( $entry->cover_image ); ?>" style="width: 240px; height: 240px; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.8);">
				<?php else : ?>
					<div style="width: 240px; height: 240px; border-radius: 20px; background: #222; display: flex; align-items: center; justify-content: center; font-size: 5rem; font-weight: 900; color: #333;">
						?
					</div>
				<?php endif; ?>

				<div style="flex: 1;">
					<div class="kc-brand-name" style="margin-bottom: 12px;"><?php echo esc_html(strtoupper($entry->platform)); ?> Intelligence</div>
					<h1 class="kc-hero-title" style="margin-bottom: 24px; font-size: 3.5rem;"><?php echo esc_html( $entry->track_name ); ?></h1>
					<p style="font-size: 1.2rem; font-weight: 700; color: var(--k-text-dim); margin-bottom: 32px;">
						by <?php echo esc_html( $entry->artist_names ); ?>
					</p>

					<div class="kc-stats-bar" style="margin-top: 0;">
						<div class="kc-stat-item">
							<span class="kc-stat-val">#<?php echo $best_rank; ?></span>
							<span class="kc-stat-lbl">Peak</span>
						</div>
						<div class="kc-stat-item">
							<span class="kc-stat-val"><?php echo $total_weeks; ?></span>
							<span class="kc-stat-lbl">Weeks</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>

	<main class="kc-container" style="padding: 80px 0 120px;">
		<section class="kc-table-section">
			<h2>Performance History</h2>
			<div class="kc-chart-list">
				<?php foreach ( $history as $h ) : ?>
					<div class="kc-row">
						<div class="kc-row-main" style="grid-template-columns: 80px 1fr 120px 100px;">
							<div class="kc-row-rank" style="font-size: 1.5rem;">#<?php echo $h->rank_position; ?></div>
							<div class="kc-row-info">
								<div class="kc-row-title"><?php echo date('M j, Y', strtotime($h->period_start)); ?></div>
								<div class="kc-row-subtitle"><?php echo esc_html($h->source_name); ?></div>
							</div>
							<div class="kc-row-movement <?php echo $h->movement_direction; ?>">
								<?php if ($h->movement_direction === 'up'): ?>▲ <?php echo $h->movement_value; ?>
								<?php elseif ($h->movement_direction === 'down'): ?>▼ <?php echo $h->movement_value; ?>
								<?php elseif ($h->movement_direction === 'new'): ?><span class="kc-badge" style="background:var(--k-accent)">NEW</span>
								<?php else: ?>—<?php endif; ?>
							</div>
							<div>
								<?php if ( $h->spotify_id ) : ?>
									<a href="https://open.spotify.com/track/<?php echo esc_attr($h->spotify_id); ?>" target="_blank" style="color: var(--k-spotify);"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.5 17.3c-.2.3-.6.4-.9.2-2.8-1.7-6.2-2.1-10.3-1.1-.3.1-.7-.1-.8-.4s.1-.7.4-.8c4.5-1 8.3-.6 11.4 1.3.3.1.4.5.2.8zm1.5-3.3c-.3.4-.8.5-1.1.3-3.2-1.9-8-2.5-11.8-1.4-.4.1-.9-.1-1-.5s.1-.9.5-1c4.3-1.3 9.6-.6 13.3 1.6.4.3.4.8.1 1.0zm.1-3.4C15.2 8.3 8.8 8.1 5.1 9.2c-.5.1-1.1-.1-1.2-.7-.1-.5.1-1.1.7-1.2 4.3-1.3 11.4-1.1 16.1 1.7.5.3.6.9.3 1.4-.3.5-.9.6-1.4.3z"/></svg></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	</main>

</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
