<?php
/**
 * Charts Index — Premium Editorial Design
 */
get_header();
global $wpdb;

$sources_table = $wpdb->prefix . 'charts_sources';
$entries_table = $wpdb->prefix . 'charts_entries';
$periods_table = $wpdb->prefix . 'charts_periods';

// Fetch active sources with latest period & entry count
$sources = $wpdb->get_results( "
	SELECT s.*,
	       MAX(p.id)          AS latest_period_id,
	       MAX(p.period_start) AS latest_period_date,
	       COUNT(DISTINCT e.id) AS entry_count
	FROM {$sources_table} s
	LEFT JOIN {$entries_table} e ON e.source_id = s.id
	LEFT JOIN {$periods_table} p ON p.id = e.period_id
	WHERE s.is_active = 1
	GROUP BY s.id
	ORDER BY entry_count DESC, s.platform ASC
" );

// For each source, grab top 3 entries for preview
foreach ( $sources as &$source ) {
	$source->top_tracks = array();
	if ( $source->entry_count > 0 && $source->latest_period_id ) {
		$source->top_tracks = $wpdb->get_results( $wpdb->prepare(
			"SELECT track_name, artist_names, cover_image, rank_position, streams
			 FROM {$entries_table}
			 WHERE source_id = %d AND period_id = %d AND rank_position <= 3
			 ORDER BY rank_position ASC",
			$source->id, $source->latest_period_id
		) );
	}
}
unset( $source );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Charts — Music Intelligence</title>
<meta name="description" content="The definitive pulse of Egyptian and Arabian streaming charts. Real rankings, real data.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f5f5f5;color:#111;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}

/* ── Layout ── */
.ch-wrap{max-width:1280px;margin:0 auto;padding:0 24px}
.ch-hero{padding:72px 0 48px;text-align:center}
.ch-hero h1{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;letter-spacing:-0.03em;line-height:1.05;color:#000}
.ch-hero h1 em{font-style:normal;color:#6366f1}
.ch-hero p{margin-top:14px;font-size:1.05rem;color:#6b7280;font-weight:500}

/* ── Grid ── */
.ch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;padding-bottom:80px}

/* ── Card ── */
.ch-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 20px rgba(0,0,0,.05);transition:box-shadow .2s,transform .18s}
.ch-card:hover{box-shadow:0 4px 24px rgba(0,0,0,.12);transform:translateY(-3px)}
.ch-card-header{padding:20px 20px 16px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:12px}
.ch-platform-badge{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 8px;border-radius:6px;background:#f3f4f6;color:#374151}
.ch-platform-badge.spotify{background:#e6f7ee;color:#14532d}
.ch-platform-badge.youtube{background:#fef2f2;color:#991b1b}
.ch-card-meta{font-size:11px;font-weight:600;color:#9ca3af;text-align:right;line-height:1.4}
.ch-card-title{padding:16px 20px 4px;font-size:1.05rem;font-weight:800;color:#000;line-height:1.2}
.ch-card-sub{padding:2px 20px 16px;font-size:12px;color:#9ca3af;font-weight:500}

/* ── Track rows ── */
.ch-track-list{border-top:1px solid #f3f4f6}
.ch-track{display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f3f4f6;font-size:13px}
.ch-track:last-child{border-bottom:none}
.ch-rank{font-size:22px;font-weight:900;color:#e5e7eb;min-width:32px;text-align:center;line-height:1}
.ch-thumb{width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;background:#f3f4f6}
.ch-thumb-ph{width:40px;height:40px;border-radius:6px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;color:#9ca3af;flex-shrink:0}
.ch-track-info{flex:1;min-width:0}
.ch-track-name{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#111}
.ch-track-artist{font-size:11px;color:#9ca3af;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ── Card Footer ── */
.ch-card-footer{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f3f4f6}
.ch-entry-count{font-size:12px;font-weight:700;color:#6b7280}
.ch-view-btn{display:inline-flex;align-items:center;gap:6px;background:#111;color:#fff;font-size:12px;font-weight:700;padding:8px 16px;border-radius:8px;transition:background .15s}
.ch-view-btn:hover{background:#374151}
.ch-no-data{font-size:13px;color:#d1d5db;padding:20px;text-align:center;font-weight:600}

/* ── Empty state ── */
.ch-empty{text-align:center;padding:120px 20px;color:#9ca3af}
.ch-empty h2{font-size:1.6rem;font-weight:800;color:#374151;margin-bottom:12px}
.ch-empty p{font-size:1rem;max-width:400px;margin:0 auto 24px}
.ch-empty a{background:#6366f1;color:#fff;padding:12px 28px;border-radius:10px;font-weight:700;font-size:14px}

@media(max-width:600px){.ch-grid{grid-template-columns:1fr}.ch-hero{padding:40px 0 28px}}
</style>
</head>
<body>
<?php // Use WordPress header
get_header(); ?>

<div class="ch-wrap">

	<div class="ch-hero">
		<h1>Music <em>Intelligence</em></h1>
		<p>The definitive pulse of Egyptian & regional streaming charts.</p>
	</div>

	<?php if ( empty( $sources ) ) : ?>
		<div class="ch-empty">
			<h2>No charts yet</h2>
			<p>Import a Spotify CSV from the admin to populate the chart engine.</p>
			<a href="<?php echo admin_url('admin.php?page=charts-spotify-import'); ?>">Import First Chart</a>
		</div>
	<?php else : ?>
		<div class="ch-grid">
			<?php foreach ( $sources as $source ) :
				$chart_url = home_url( '/charts/' . $source->platform . '/' . $source->country_code . '/' . $source->frequency . '/' . $source->chart_type . '/' );
				$platform_class = strtolower( $source->platform );
				$has_data = ! empty( $source->top_tracks );
				$period_label = $source->latest_period_date ? date( 'M j, Y', strtotime( $source->latest_period_date ) ) : null;
			?>
				<a href="<?php echo esc_url( $chart_url ); ?>" class="ch-card">
					<div class="ch-card-header">
						<span class="ch-platform-badge <?php echo esc_attr( $platform_class ); ?>">
							<?php echo esc_html( strtoupper( $source->platform ) ); ?>
							&nbsp;·&nbsp;<?php echo esc_html( strtoupper( $source->country_code ) ); ?>
						</span>
						<div class="ch-card-meta">
							<?php echo esc_html( strtoupper( $source->frequency ) ); ?><br>
							<?php if ( $period_label ) echo esc_html( $period_label ); ?>
						</div>
					</div>

					<div class="ch-card-title"><?php echo esc_html( $source->source_name ); ?></div>
					<div class="ch-card-sub"><?php echo esc_html( str_replace( '-', ' ', ucfirst( $source->chart_type ) ) ); ?></div>

					<?php if ( $has_data ) : ?>
						<div class="ch-track-list">
							<?php foreach ( $source->top_tracks as $track ) : ?>
								<div class="ch-track">
									<span class="ch-rank"><?php echo (int) $track->rank_position; ?></span>
									<?php if ( $track->cover_image ) : ?>
										<img src="<?php echo esc_url( $track->cover_image ); ?>" class="ch-thumb" loading="lazy" alt="">
									<?php else : ?>
										<div class="ch-thumb-ph"><?php echo esc_html( strtoupper( mb_substr( $track->track_name, 0, 1 ) ) ); ?></div>
									<?php endif; ?>
									<div class="ch-track-info">
										<div class="ch-track-name"><?php echo esc_html( $track->track_name ); ?></div>
										<div class="ch-track-artist"><?php echo esc_html( $track->artist_names ); ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="ch-no-data">No data yet — import CSV to begin</div>
					<?php endif; ?>

					<div class="ch-card-footer">
						<span class="ch-entry-count">
							<?php if ( $source->entry_count > 0 ) echo number_format( $source->entry_count ) . ' tracks'; else echo 'No data yet'; ?>
						</span>
						<span class="ch-view-btn">View Chart &#8594;</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>

<?php get_footer(); ?>
