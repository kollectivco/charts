<?php
/**
 * Single Chart Template
 */
get_header();

global $wpdb;

$platform  = get_query_var( 'charts_platform' );
$country   = get_query_var( 'charts_country' );
$frequency = get_query_var( 'charts_frequency' );
$type      = get_query_var( 'charts_type' );

// 1. Find Source
$source_table = $wpdb->prefix . 'charts_sources';
$source = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM $source_table WHERE platform = %s AND country_code = %s AND frequency = %s AND chart_type = %s",
	$platform, $country, $frequency, $type
) );

if ( ! $source ) {
	echo '<div class="charts-container"><div class="chart-no-data"><h1>' . __( 'Chart Not Found', 'charts' ) . '</h1><p>' . esc_html("$platform / $country / $frequency / $type") . '</p></div></div>';
	get_footer();
	return;
}

// 2. Find Latest Period for this source
$period_table   = $wpdb->prefix . 'charts_periods';
$entries_table  = $wpdb->prefix . 'charts_entries';

$period = $wpdb->get_row( $wpdb->prepare(
	"SELECT p.* FROM $period_table p
	 INNER JOIN $entries_table e ON e.period_id = p.id
	 WHERE e.source_id = %d
	 ORDER BY p.id DESC LIMIT 1",
	$source->id
) );

if ( ! $period ) {
	echo '<div class="charts-container"><div class="chart-no-data"><h1>' . esc_html( $source->source_name ) . '</h1><p>' . __( 'No chart data is available yet. Import a CSV or run an import to begin.', 'charts' ) . '</p></div></div>';
	get_footer();
	return;
}

// 3. Get Entries directly (no join to non-existent tracks table)
$entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $entries_table WHERE source_id = %d AND period_id = %d ORDER BY rank_position ASC LIMIT 200",
	$source->id, $period->id
) );

?>
<div class="charts-container">
	<header class="charts-header">
		<div class="header-meta-bar">
			<span class="bento-tag"><?php echo esc_html( strtoupper( $source->platform ) ); ?></span>
			<span class="bento-tag bento-tag-country"><?php echo esc_html( strtoupper( $source->country_code ) ); ?></span>
			<span class="bento-tag bento-tag-freq"><?php echo esc_html( strtoupper( $source->frequency ) ); ?></span>
			<span class="bento-tag bento-tag-type"><?php echo esc_html( str_replace('-', ' ', strtoupper( $source->chart_type ) ) ); ?></span>
		</div>
		<h1><?php echo esc_html( $source->source_name ); ?></h1>
		<p class="subtitle">
			<?php echo sprintf( __( 'Week of %s', 'charts' ), date( 'M j, Y', strtotime( $period->period_start ) ) ); ?>
			&nbsp;&middot;&nbsp;
			<span><?php echo count( $entries ); ?> <?php _e( 'tracks', 'charts' ); ?></span>
		</p>
	</header>

	<div class="chart-list">
		<?php if ( empty( $entries ) ) : ?>
			<div class="chart-row chart-empty-state">
				<p><?php _e( 'No chart entries found for this period.', 'charts' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $entries as $i => $entry ) :
				$movement_dir = $entry->movement_direction ?? 'same';
				$movement_val = (int) ( $entry->movement_value ?? 0 );
				$raw          = $entry->raw_payload_json ? json_decode( $entry->raw_payload_json, true ) : array();
				$streams      = !empty( $entry->streams ) ? number_format( $entry->streams ) : ( $raw['streams'] ?? null );
				$cover        = $entry->cover_image ?? null;
				$spotify_id   = $entry->spotify_id ?? null;
				$yt_id        = $entry->youtube_id ?? null;
				$weeks        = !empty( $entry->weeks_on_chart ) ? (int) $entry->weeks_on_chart : 1;
				$peak         = !empty( $entry->peak_rank ) ? (int) $entry->peak_rank : (int) $entry->rank_position;
				$artist_str   = $entry->artist_names ?? '';
				$track_name   = $entry->track_name ?? 'Unknown Track';
			?>
				<details class="chart-row" id="chart-entry-<?php echo $i + 1; ?>">
					<summary class="chart-row-summary">
						<div class="chart-rank-block">
							<span class="chart-rank"><?php echo (int) $entry->rank_position; ?></span>
							<?php if ( $movement_dir === 'up' ) : ?>
								<span class="movement-up" title="Up <?php echo $movement_val; ?>">&#x25B2;</span>
							<?php elseif ( $movement_dir === 'down' ) : ?>
								<span class="movement-down" title="Down <?php echo $movement_val; ?>">&#x25BC;</span>
							<?php elseif ( $movement_dir === 'new' ) : ?>
								<span class="movement-new">NEW</span>
							<?php else : ?>
								<span class="movement-same">&bull;</span>
							<?php endif; ?>
						</div>

						<?php if ( $cover ) : ?>
							<img src="<?php echo esc_url( $cover ); ?>" class="chart-cover" loading="lazy" alt="<?php echo esc_attr( $track_name ); ?>">
						<?php else : ?>
							<div class="chart-cover chart-cover-placeholder">
								<span><?php echo esc_html( strtoupper( substr( $track_name, 0, 1 ) ) ); ?></span>
							</div>
						<?php endif; ?>

						<div class="chart-info">
							<div class="chart-title"><?php echo esc_html( $track_name ); ?></div>
							<div class="chart-artist"><?php echo esc_html( $artist_str ); ?></div>
						</div>

						<div class="chart-stat-block">
							<?php if ( $streams ) : ?>
								<div class="chart-streams"><?php echo esc_html( $streams ); ?></div>
								<div class="chart-streams-label"><?php _e( 'streams', 'charts' ); ?></div>
							<?php endif; ?>
							<div class="chart-weeks"><?php echo $weeks; ?>W</div>
						</div>
					</summary>

					<div class="chart-row-detail">
						<div class="detail-grid">
							<div class="detail-item">
								<span class="detail-label"><?php _e( 'Peak Rank', 'charts' ); ?></span>
								<span class="detail-value">#<?php echo $peak; ?></span>
							</div>
							<div class="detail-item">
								<span class="detail-label"><?php _e( 'Weeks on Chart', 'charts' ); ?></span>
								<span class="detail-value"><?php echo $weeks; ?></span>
							</div>
							<div class="detail-item">
								<span class="detail-label"><?php _e( 'Previous Rank', 'charts' ); ?></span>
								<span class="detail-value"><?php echo !empty($entry->previous_rank) ? '#' . (int) $entry->previous_rank : '–'; ?></span>
							</div>
							<?php if ( $streams ) : ?>
							<div class="detail-item">
								<span class="detail-label"><?php _e( 'Streams', 'charts' ); ?></span>
								<span class="detail-value"><?php echo esc_html( $streams ); ?></span>
							</div>
							<?php endif; ?>
						</div>
						<div class="detail-links">
							<?php if ( $spotify_id ) : ?>
								<a href="https://open.spotify.com/track/<?php echo esc_attr( $spotify_id ); ?>" target="_blank" rel="noopener" class="detail-link detail-link-spotify">&#9654; Spotify</a>
							<?php endif; ?>
							<?php if ( $yt_id ) : ?>
								<a href="https://www.youtube.com/watch?v=<?php echo esc_attr( $yt_id ); ?>" target="_blank" rel="noopener" class="detail-link detail-link-youtube">&#9654; YouTube</a>
							<?php endif; ?>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
<?php include CHARTS_PATH . 'public/assets/css/public.css'; ?>
.chart-row { border-bottom: 1px solid var(--charts-border, #eee); list-style: none; }
.chart-row-summary { display: flex; align-items: center; gap: 16px; padding: 14px 0; cursor: pointer; list-style: none; }
.chart-row-summary::-webkit-details-marker { display: none; }
.chart-rank-block { display: flex; flex-direction: column; align-items: center; min-width: 40px; }
.chart-rank { font-size: 1.4rem; font-weight: 900; }
.movement-up { color: #22c55e; font-size: 10px; }
.movement-down { color: #ef4444; font-size: 10px; }
.movement-new { font-size: 9px; font-weight: 700; color: #6366f1; background: #eef2ff; padding: 2px 5px; border-radius: 4px; }
.movement-same { color: #9ca3af; font-size: 16px; line-height: 1; }
.chart-cover { width: 52px; height: 52px; border-radius: 6px; object-fit: cover; flex-shrink: 0; background: #f3f4f6; }
.chart-cover-placeholder { display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem; color: #d1d5db; }
.chart-info { flex: 1; min-width: 0; }
.chart-title { font-weight: 800; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chart-artist { font-size: 0.8rem; color: #6b7280; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chart-stat-block { text-align: right; min-width: 60px; }
.chart-streams { font-weight: 700; font-size: 0.85rem; }
.chart-streams-label { font-size: 0.65rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
.chart-weeks { font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-top: 4px; }
.chart-row-detail { padding: 16px 0 20px 56px; background: #fafafa; border-top: 1px solid #f3f4f6; }
.detail-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 12px; }
.detail-item { display: flex; flex-direction: column; gap: 2px; }
.detail-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; font-weight: 600; }
.detail-value { font-size: 0.95rem; font-weight: 800; }
.detail-links { display: flex; gap: 10px; margin-top: 4px; }
.detail-link { font-size: 0.75rem; font-weight: 600; padding: 5px 12px; border-radius: 6px; text-decoration: none; }
.detail-link-spotify { background: #1DB954; color: #fff; }
.detail-link-youtube { background: #FF0000; color: #fff; }
.chart-no-data { padding: 60px 20px; text-align: center; }
.header-meta-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.bento-tag-country { background: #1a1a1a; color: #fff; }
.bento-tag-freq { background: #374151; color: #fff; }
.bento-tag-type { background: #4f46e5; color: #fff; }
</style>

<?php get_footer(); ?>
