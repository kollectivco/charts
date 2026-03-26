<?php
/**
 * Entities Explorer View
 */
global $wpdb;
$entries_table = $wpdb->prefix . 'charts_entries';
$sources_table = $wpdb->prefix . 'charts_sources';
$periods_table = $wpdb->prefix . 'charts_periods';

$items = $wpdb->get_results( "
	SELECT e.track_name, e.artist_names, e.spotify_id, e.cover_image,
	       MIN(e.rank_position) AS best_rank,
	       MAX(e.weeks_on_chart) AS max_weeks,
	       COUNT(*) AS appearances,
	       s.platform,
	       MAX(p.period_start) AS last_seen
	FROM {$entries_table} e
	LEFT JOIN {$sources_table} s ON s.id = e.source_id
	LEFT JOIN {$periods_table} p ON p.id = e.period_id
	WHERE e.track_name != '' AND e.track_name IS NOT NULL
	GROUP BY e.track_name, e.artist_names, s.platform
	ORDER BY best_rank ASC, appearances DESC
	LIMIT 300
" );

$total = $wpdb->get_var( "SELECT COUNT(DISTINCT track_name) FROM {$entries_table} WHERE track_name != '' AND track_name IS NOT NULL" );
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Registered Entities', 'charts' ); ?></h1>
			<p class="subtitle"><?php printf( _n( '%s unique track in database', '%s unique tracks in database', $total, 'charts' ), number_format( $total ) ); ?></p>
		</div>
	</header>

	<div class="charts-grid">
		<div class="charts-card" style="grid-column: span 12; padding: 0; overflow: hidden;">
			<?php if ( empty( $items ) ) : ?>
				<div style="padding: 60px; text-align: center; color: #6b7280;">
					<span class="dashicons dashicons-id-alt" style="font-size: 48px; width: 48px; height: 48px; color: #d1d5db;"></span>
					<h3 style="margin-top: 20px;"><?php _e( 'No entities yet', 'charts' ); ?></h3>
					<p><?php _e( 'Import a Spotify CSV or run a YouTube scrape to begin populating the entity database.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px; width: 48px;">#</th>
							<th><?php _e( 'Track', 'charts' ); ?></th>
							<th><?php _e( 'Artist', 'charts' ); ?></th>
							<th><?php _e( 'Platform', 'charts' ); ?></th>
							<th><?php _e( 'Best Rank', 'charts' ); ?></th>
							<th><?php _e( 'Max Weeks', 'charts' ); ?></th>
							<th><?php _e( 'Appearances', 'charts' ); ?></th>
							<th><?php _e( 'Last Seen', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $i => $item ) : ?>
							<tr>
								<td style="padding-left: 24px; color: #9ca3af; font-size: 12px;"><?php echo $i + 1; ?></td>
								<td>
									<div style="display: flex; align-items: center; gap: 10px;">
										<?php if ( $item->cover_image ) : ?>
											<img src="<?php echo esc_url( $item->cover_image ); ?>" style="width: 36px; height: 36px; border-radius: 4px; object-fit: cover; flex-shrink: 0;" loading="lazy">
										<?php else : ?>
											<div style="width: 36px; height: 36px; border-radius: 4px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #d1d5db;"><?php echo esc_html( strtoupper( substr( $item->track_name, 0, 1 ) ) ); ?></div>
										<?php endif; ?>
										<div>
											<div style="font-weight: 700; font-size: 13px;"><?php echo esc_html( $item->track_name ); ?></div>
											<?php if ( $item->spotify_id ) : ?>
												<a href="https://open.spotify.com/track/<?php echo esc_attr( $item->spotify_id ); ?>" target="_blank" style="font-size: 10px; color: #1DB954; text-decoration: none;">&#9654; Spotify</a>
											<?php endif; ?>
										</div>
									</div>
								</td>
								<td style="color: #374151; font-size: 13px;"><?php echo esc_html( $item->artist_names ); ?></td>
								<td>
									<span class="charts-badge <?php echo $item->platform === 'spotify' ? 'charts-badge-success' : 'charts-badge-neutral'; ?>" style="font-size: 9px; text-transform: uppercase;"><?php echo esc_html( $item->platform ); ?></span>
								</td>
								<td><span style="font-weight: 800; font-size: 15px;">#<?php echo (int) $item->best_rank; ?></span></td>
								<td><?php echo (int) $item->max_weeks; ?>W</td>
								<td style="font-size: 12px; color: #6b7280;"><?php echo (int) $item->appearances; ?></td>
								<td style="font-size: 12px; color: #6b7280;"><?php echo $item->last_seen ? date( 'M j, Y', strtotime( $item->last_seen ) ) : '–'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
