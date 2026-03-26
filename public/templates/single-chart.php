<?php
/**
 * Single Chart Template — Premium Editorial
 */
get_header();
global $wpdb;

$platform  = get_query_var( 'charts_platform' );
$country   = get_query_var( 'charts_country' );
$frequency = get_query_var( 'charts_frequency' );
$type      = get_query_var( 'charts_type' );

$sources_table = $wpdb->prefix . 'charts_sources';
$periods_table = $wpdb->prefix . 'charts_periods';
$entries_table = $wpdb->prefix . 'charts_entries';

// 1. Find source
$source = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM $sources_table WHERE platform = %s AND country_code = %s AND frequency = %s AND chart_type = %s",
	$platform, $country, $frequency, $type
) );

if ( ! $source ) {
	echo '<div style="max-width:800px;margin:80px auto;text-align:center;font-family:Inter,sans-serif;">';
	echo '<h1 style="font-size:2rem;font-weight:900;color:#111;">Chart Not Found</h1>';
	echo '<p style="color:#6b7280;margin-top:10px;">' . esc_html( "$platform / $country / $frequency / $type" ) . '</p>';
	echo '<p style="margin-top:20px;"><a href="' . esc_url( home_url('/charts/') ) . '" style="color:#6366f1;font-weight:700;">← Back to Charts</a></p>';
	echo '</div>';
	get_footer();
	return;
}

// 2. Find latest period that has entries for this source
$period = $wpdb->get_row( $wpdb->prepare(
	"SELECT p.* FROM $periods_table p
	 INNER JOIN $entries_table e ON e.period_id = p.id AND e.source_id = %d
	 ORDER BY p.period_start DESC LIMIT 1",
	$source->id
) );

if ( ! $period ) {
	echo '<div style="max-width:800px;margin:80px auto;text-align:center;font-family:Inter,sans-serif;">';
	echo '<h1 style="font-size:2rem;font-weight:900;color:#111;">' . esc_html( $source->source_name ) . '</h1>';
	echo '<p style="color:#6b7280;margin-top:16px;">No chart data yet. <a href="' . admin_url('admin.php?page=charts-spotify-import') . '" style="color:#6366f1;font-weight:700;">Import a CSV →</a></p>';
	echo '</div>';
	get_footer();
	return;
}

// 3. Get entries
$entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $entries_table WHERE source_id = %d AND period_id = %d ORDER BY rank_position ASC LIMIT 200",
	$source->id, $period->id
) );

// Page title for SEO
$page_title = $source->source_name . ' — ' . date( 'M j, Y', strtotime( $period->period_start ) );
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box}
.sc-body{font-family:'Inter',sans-serif;background:#f8f8f8;min-height:100vh}
.sc-wrap{max-width:860px;margin:0 auto;padding:0 20px 80px}

/* ── Header ── */
.sc-header{padding:48px 0 32px;border-bottom:2px solid #000}
.sc-breadcrumb{font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px}
.sc-breadcrumb a{color:#9ca3af;text-decoration:none}
.sc-breadcrumb a:hover{color:#111}
.sc-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.sc-tag{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:4px 10px;border-radius:20px;background:#111;color:#fff}
.sc-tag.platform-spotify{background:#1DB954;color:#fff}
.sc-tag.platform-youtube{background:#FF0000;color:#fff}
.sc-tag.plain{background:#f3f4f6;color:#374151}
h1.sc-title{font-size:clamp(1.5rem,4vw,2.5rem);font-weight:900;letter-spacing:-.03em;color:#000;line-height:1.1}
.sc-period{margin-top:10px;font-size:1rem;color:#6b7280;font-weight:600}
.sc-period strong{color:#111}
.sc-count{margin-top:4px;font-size:12px;color:#9ca3af;font-weight:600}

/* ── Chart rows ── */
.sc-list{margin-top:0}
.sc-row{background:#fff;border-radius:0;border-bottom:1px solid #f0f0f0;transition:background .12s}
.sc-row:first-child{border-top:none}
.sc-row[open]{background:#fafafa}
.sc-row summary{display:flex;align-items:center;gap:16px;padding:16px 20px;cursor:pointer;list-style:none;user-select:none}
.sc-row summary::-webkit-details-marker{display:none}

.sc-num{min-width:44px;text-align:center}
.sc-num-rank{font-size:1.8rem;font-weight:900;color:#000;line-height:1}
.sc-num-move{font-size:10px;font-weight:800;margin-top:2px;line-height:1}
.sc-num-move.up{color:#22c55e}
.sc-num-move.down{color:#ef4444}
.sc-num-move.new{display:inline-flex;background:#eef2ff;color:#6366f1;padding:2px 5px;border-radius:4px}
.sc-num-move.same{color:#d1d5db}

.sc-art{width:56px;height:56px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#f3f4f6}
.sc-art-ph{width:56px;height:56px;border-radius:8px;background:linear-gradient(135deg,#e5e7eb,#d1d5db);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:900;color:#9ca3af;flex-shrink:0;user-select:none}

.sc-info{flex:1;min-width:0}
.sc-track-name{font-size:1rem;font-weight:800;color:#000;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sc-artist-name{font-size:.8rem;color:#6b7280;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500}

.sc-stats{text-align:right;flex-shrink:0}
.sc-streams-val{font-size:.9rem;font-weight:800;color:#000}
.sc-streams-lbl{font-size:.65rem;text-transform:uppercase;letter-spacing:.07em;color:#9ca3af;font-weight:700;margin-top:1px}
.sc-weeks{font-size:.75rem;font-weight:600;color:#9ca3af;margin-top:5px}

/* ── Detail panel ── */
.sc-detail{padding:16px 20px 24px 96px;background:#fafafa;border-top:1px solid #f0f0f0;animation:fadeIn .15s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.sc-detail-grid{display:flex;flex-wrap:wrap;gap:20px 32px;margin-bottom:14px}
.sc-detail-item{display:flex;flex-direction:column;gap:3px}
.sc-detail-label{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#9ca3af}
.sc-detail-value{font-size:1rem;font-weight:800;color:#000}
.sc-links{display:flex;gap:10px;flex-wrap:wrap}
.sc-link{display:inline-flex;align-items:center;gap:6px;font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none;transition:opacity .15s}
.sc-link:hover{opacity:.8}
.sc-link-spotify{background:#1DB954;color:#fff}
.sc-link-youtube{background:#FF0000;color:#fff}

/* ── Empty ── */
.sc-empty{padding:80px 20px;text-align:center;color:#6b7280;font-family:Inter,sans-serif}

@media(max-width:580px){
	.sc-row summary{gap:10px;padding:14px 12px}
	.sc-detail{padding:14px 12px 20px}
	.sc-num-rank{font-size:1.3rem}
	.sc-art,.sc-art-ph{width:44px;height:44px}
}
</style>

<div class="sc-body">
<div class="sc-wrap">

	<div class="sc-header">
		<div class="sc-breadcrumb">
			<a href="<?php echo esc_url( home_url('/charts/') ); ?>">Charts</a>
			&nbsp;/&nbsp; <?php echo esc_html( strtoupper( $country ) ); ?>
			&nbsp;/&nbsp; <?php echo esc_html( strtoupper( $platform ) ); ?>
		</div>

		<div class="sc-tags">
			<span class="sc-tag platform-<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( strtoupper( $platform ) ); ?></span>
			<span class="sc-tag plain"><?php echo esc_html( strtoupper( $country ) ); ?></span>
			<span class="sc-tag plain"><?php echo esc_html( strtoupper( $frequency ) ); ?></span>
			<span class="sc-tag plain"><?php echo esc_html( str_replace('-', ' ', strtoupper( $type ) ) ); ?></span>
		</div>

		<h1 class="sc-title"><?php echo esc_html( $source->source_name ); ?></h1>
		<p class="sc-period">
			<strong><?php echo date( 'F j, Y', strtotime( $period->period_start ) ); ?></strong>
			<?php if ( $period->period_end && $period->period_end !== $period->period_start ) : ?>
				&ndash; <?php echo date( 'F j', strtotime( $period->period_end ) ); ?>
			<?php endif; ?>
		</p>
		<p class="sc-count"><?php echo number_format( count( $entries ) ); ?> tracks</p>
	</div>

	<?php if ( empty( $entries ) ) : ?>
		<div class="sc-empty">
			<p style="font-size:1.1rem;font-weight:700;color:#374151;">No chart entries found for this period.</p>
			<p style="margin-top:8px;font-size:.9rem;">Source ID: <?php echo $source->id; ?> · Period ID: <?php echo $period->id; ?></p>
		</div>
	<?php else : ?>
		<div class="sc-list">
			<?php foreach ( $entries as $i => $e ) :
				$mv_dir = $e->movement_direction ?? 'same';
				$mv_val = (int) ( $e->movement_value ?? 0 );
				$raw    = $e->raw_payload_json ? json_decode( $e->raw_payload_json, true ) : array();
				$streams = $e->streams > 0 ? number_format( $e->streams ) : null;
				$weeks  = max( 1, (int) $e->weeks_on_chart );
				$peak   = $e->peak_rank > 0 ? (int) $e->peak_rank : (int) $e->rank_position;
				$prev   = $e->previous_rank > 0 ? '#' . (int) $e->previous_rank : '–';
			?>
			<details class="sc-row" id="row-<?php echo $i+1; ?>">
				<summary>
					<div class="sc-num">
						<div class="sc-num-rank"><?php echo (int) $e->rank_position; ?></div>
						<?php if ( $mv_dir === 'up' ) : ?>
							<div class="sc-num-move up">&#x25B2; <?php echo $mv_val; ?></div>
						<?php elseif ( $mv_dir === 'down' ) : ?>
							<div class="sc-num-move down">&#x25BC; <?php echo $mv_val; ?></div>
						<?php elseif ( $mv_dir === 'new' ) : ?>
							<div class="sc-num-move new">NEW</div>
						<?php else : ?>
							<div class="sc-num-move same">—</div>
						<?php endif; ?>
					</div>

					<?php if ( $e->cover_image ) : ?>
						<img src="<?php echo esc_url( $e->cover_image ); ?>" class="sc-art" loading="lazy" alt="">
					<?php else : ?>
						<div class="sc-art-ph"><?php echo esc_html( mb_strtoupper( mb_substr( $e->track_name ?: '?', 0, 1 ) ) ); ?></div>
					<?php endif; ?>

					<div class="sc-info">
						<div class="sc-track-name"><?php echo esc_html( $e->track_name ?: 'Unknown Track' ); ?></div>
						<div class="sc-artist-name"><?php echo esc_html( $e->artist_names ?: '—' ); ?></div>
					</div>

					<div class="sc-stats">
						<?php if ( $streams ) : ?>
							<div class="sc-streams-val"><?php echo esc_html( $streams ); ?></div>
							<div class="sc-streams-lbl">streams</div>
						<?php endif; ?>
						<div class="sc-weeks"><?php echo $weeks; ?>W</div>
					</div>
				</summary>

				<div class="sc-detail">
					<div class="sc-detail-grid">
						<div class="sc-detail-item">
							<span class="sc-detail-label">Peak Rank</span>
							<span class="sc-detail-value">#<?php echo $peak; ?></span>
						</div>
						<div class="sc-detail-item">
							<span class="sc-detail-label">Weeks on Chart</span>
							<span class="sc-detail-value"><?php echo $weeks; ?></span>
						</div>
						<div class="sc-detail-item">
							<span class="sc-detail-label">Previous Rank</span>
							<span class="sc-detail-value"><?php echo $prev; ?></span>
						</div>
						<?php if ( $streams ) : ?>
						<div class="sc-detail-item">
							<span class="sc-detail-label">Streams</span>
							<span class="sc-detail-value"><?php echo esc_html( $streams ); ?></span>
						</div>
						<?php endif; ?>
					</div>
					<div class="sc-links">
						<?php if ( $e->spotify_id ) : ?>
							<a href="https://open.spotify.com/track/<?php echo esc_attr( $e->spotify_id ); ?>" target="_blank" rel="noopener" class="sc-link sc-link-spotify">
								&#x25B6; Open in Spotify
							</a>
						<?php endif; ?>
						<?php if ( $e->youtube_id ) : ?>
							<a href="https://www.youtube.com/watch?v=<?php echo esc_attr( $e->youtube_id ); ?>" target="_blank" rel="noopener" class="sc-link sc-link-youtube">
								&#x25B6; Watch on YouTube
							</a>
						<?php endif; ?>
					</div>
				</div>
			</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
</div>

<?php get_footer(); ?>
