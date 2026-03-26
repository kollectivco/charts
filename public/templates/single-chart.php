<?php
/**
 * Kontentainment Charts — Single Chart
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
if ( $platform && $country && $frequency && $type ) {
	$source = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $sources_table WHERE platform = %s AND country_code = %s AND frequency = %s AND chart_type = %s",
		$platform, $country, $frequency, $type
	) );
} else {
	// Short URL: /charts/{type}
	// Fetch the latest source for this type (prioritize Egypt if multiple exist)
	$source = $wpdb->get_row( $wpdb->prepare( "
		SELECT s.* FROM $sources_table s
		JOIN $entries_table e ON e.source_id = s.id
		WHERE s.chart_type = %s AND s.is_active = 1
		ORDER BY s.country_code = 'eg' DESC, e.created_at DESC
		LIMIT 1
	", $type ?: 'top-songs' ) );
}

if ( ! $source ) {
	echo '<div style="max-width:800px;margin:80px auto;text-align:center;font-family:Inter,sans-serif;color:#fff;">';
	echo '<h1 style="font-size:2rem;font-weight:900;">Chart Not Found</h1>';
	echo '<p style="color:#6b7280;margin-top:10px;">' . esc_html( "Type: " . ($type ?: 'Unknown') ) . '</p>';
	echo '<p style="margin-top:20px;"><a href="' . esc_url( home_url('/charts/') ) . '" style="color:#6366f1;font-weight:700;">← Back to Charts</a></p>';
	echo '</div>';
	get_footer();
	return;
}

// Re-populate vars if they were missing (for breadcrumbs/etc)
$platform  = $source->platform;
$country   = $source->country_code;
$frequency = $source->frequency;
$type      = $source->chart_type;

// 2. Find latest period
$period = $wpdb->get_row( $wpdb->prepare(
	"SELECT p.* FROM $periods_table p
	 INNER JOIN $entries_table e ON e.period_id = p.id AND e.source_id = %d
	 ORDER BY p.period_start DESC LIMIT 1",
	$source->id
) );

if ( ! $period ) {
	echo '<div style="max-width:800px;margin:80px auto;text-align:center;font-family:Inter,sans-serif;color:#fff;">';
	echo '<h2 style="font-size:2rem;font-weight:900;">' . esc_html( strtoupper($type) ) . '</h2>';
	echo '<p style="color:#6b7280;margin-top:16px;">The intelligence pass for this chart is currently processing.</p>';
	echo '<p style="margin-top:20px;"><a href="' . esc_url( home_url('/charts/') ) . '" style="color:#6366f1;font-weight:700;">← Back to Home</a></p>';
	echo '</div>';
	get_footer();
	return;
}

// 3. Get entries
$entries = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $entries_table WHERE source_id = %d AND period_id = %d ORDER BY rank_position ASC LIMIT 200",
	$source->id, $period->id
) );

// Clean Title Mapping
$display_title = 'Top Chart';
if ( strpos( strtolower($type), 'songs' ) !== false || $type === 'top-tracks' ) $display_title = 'Top Songs';
elseif ( strpos( strtolower($type), 'artists' ) !== false ) $display_title = 'Top Artists';
elseif ( strpos( strtolower($type), 'videos' ) !== false ) $display_title = 'Top Videos';
elseif ( strpos( strtolower($type), 'viral' ) !== false ) $display_title = 'Viral 50';
?>

<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>">

<div class="kc-root <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
	
	<div class="kc-container" style="padding-top: 60px;">
		<div class="sc-header" style="margin-bottom: 60px; border-bottom: 2px solid #222; padding-bottom: 40px;">
			<nav class="sc-breadcrumb" style="margin-bottom: 24px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--k-text-dim);">
				<a href="<?php echo esc_url( home_url('/charts/') ); ?>" style="color: var(--k-accent);">Intelligence</a>
				<span style="margin: 0 10px; opacity: 0.3;">/</span>
				<span><?php echo esc_html( strtoupper( $country ) ); ?></span>
				<span style="margin: 0 10px; opacity: 0.3;">/</span>
				<span style="color: #fff;"><?php echo esc_html( $display_title ); ?></span>
			</nav>

			<div class="sc-meta-row" style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
				<span class="kc-badge" style="background: #111; color: #fff; border: 1px solid #222;"><?php echo esc_html(strtoupper($country)); ?></span>
				<span class="kc-badge"><?php echo esc_html(strtoupper($frequency)); ?></span>
				<span class="kc-badge" style="background: rgba(99, 102, 241, 0.1); color: var(--k-accent);"><?php echo esc_html(str_replace('-', ' ', strtoupper($type))); ?></span>
			</div>

			<h1 class="kc-hero-title" style="margin-bottom: 12px; font-size: 4rem;"><?php echo esc_html( $display_title ); ?></h1>
			
			<div class="sc-period-box" style="display: flex; align-items: center; gap: 20px; font-size: 14px; font-weight: 700; color: var(--k-text-dim);">
				<span style="color: #fff;"><?php echo date( 'F j, Y', strtotime( $period->period_start ) ); ?></span>
				<span style="width: 1px; height: 14px; background: #333;"></span>
				<span><?php echo number_format( count( $entries ) ); ?> data points tracked</span>
			</div>
		</div>

		<div class="sc-list-wrap" style="max-width: 1000px; margin: 0 auto;">
			<?php if ( empty( $entries ) ) : ?>
				<p>No entries found.</p>
			<?php else : ?>
				<?php foreach ( $entries as $i => $e ) :
					$mv_dir = $e->movement_direction ?? 'same';
					$mv_val = (int) ( $e->movement_value ?? 0 );
					
					$val_display = null;
					$val_label   = null;

					if ( $e->streams > 0 ) {
						$val_display = number_format( $e->streams );
						$val_label   = 'streams';
					} elseif ( $e->views_count > 0 ) {
						$val_display = number_format( $e->views_count );
						$val_label   = 'views';
					}

					$weeks  = max( 1, (int) $e->weeks_on_chart );
					$peak   = $e->peak_rank > 0 ? (int) $e->peak_rank : (int) $e->rank_position;
					$prev   = $e->previous_rank > 0 ? '#' . (int) $e->previous_rank : '–';

					$primary   = $e->track_name;
					$secondary = $e->artist_names;
					if ( $type === 'top-artists' ) {
						$primary = $e->track_name ?: $e->artist_names;
						$secondary = '';
					}
					if ( empty( $primary ) ) $primary = 'Unknown Item';
				?>
				<details class="sc-row-item" style="border-bottom: 1px solid #111; outline: none;">
					<summary style="display: flex; align-items: center; padding: 20px 0; cursor: pointer; list-style: none;">
						<div class="sc-rank-box" style="width: 60px; text-align: center; flex-shrink: 0;">
							<div style="font-size: 1.8rem; font-weight: 900; line-height: 1;"><?php echo (int) $e->rank_position; ?></div>
							<div style="font-size: 9px; font-weight: 800; margin-top: 4px; color: <?php 
								echo ($mv_dir === 'up') ? '#22c55e' : (($mv_dir === 'down') ? '#ef4444' : (($mv_dir === 'new') ? 'var(--k-accent)' : '#555')); 
							?>">
								<?php 
								if ($mv_dir === 'up') echo '▲' . $mv_val;
								elseif ($mv_dir === 'down') echo '▼' . $mv_val;
								elseif ($mv_dir === 'new') echo 'NEW';
								else echo '—';
								?>
							</div>
						</div>

						<?php if ( $e->cover_image ) : ?>
							<img src="<?php echo esc_url( $e->cover_image ); ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; margin-right: 20px; background: #111;">
						<?php else : ?>
							<div style="width: 60px; height: 60px; border-radius: 8px; background: #222; margin-right: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 900; color: #444;">
								<?php echo esc_html(strtoupper(mb_substr($primary, 0, 1))); ?>
							</div>
						<?php endif; ?>

						<div class="sc-info-box" style="flex: 1; min-width: 0;">
							<div style="font-size: 1.1rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($primary); ?></div>
							<?php if ( $secondary ) : ?>
								<div style="font-size: 13px; color: var(--k-text-dim); margin-top: 4px; font-weight: 600;">
									<?php 
									$artists_arr = array_map('trim', explode(',', $secondary));
									$links = array();
									foreach ($artists_arr as $a_name) {
										$a_slug = $wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}charts_artists WHERE display_name = %s", $a_name));
										if ($a_slug) {
											$links[] = '<a href="' . home_url('/charts/artist/' . $a_slug . '/') . '" style="color:inherit;text-decoration:none;border-bottom:1px solid transparent;transition:border-color 0.2s;" onmouseover="this.style.borderColor=\'var(--k-accent)\'" onmouseout="this.style.borderColor=\'transparent\'">' . esc_html($a_name) . '</a>';
										} else {
											$links[] = esc_html($a_name);
										}
									}
									echo implode(', ', $links);
									?>
								</div>
							<?php endif; ?>
						</div>

						<div class="sc-stats-box" style="text-align: right; width: 120px; flex-shrink: 0; padding-right: 10px;">
							<?php if ( $val_display ) : ?>
								<div style="font-size: 14px; font-weight: 900;"><?php echo esc_html($val_display); ?></div>
								<div style="font-size: 9px; font-weight: 800; color: var(--k-text-muted); text-transform: uppercase; margin-top: 2px;"><?php echo esc_html($val_label); ?></div>
							<?php endif; ?>
							<div style="font-size: 11px; font-weight: 700; color: var(--k-text-dim); margin-top: 6px;"><?php echo $weeks; ?> WEEKS</div>
						</div>
					</summary>

					<div class="sc-expanded" style="padding: 24px 80px 40px; background: #080808; border-top: 1px solid #111; animation: fadeInUp 0.3s ease;">
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 30px; margin-bottom: 32px;">
							<div class="sc-pop-stat">
								<div style="font-size: 10px; font-weight: 800; color: var(--k-text-muted); text-transform: uppercase;">Peak Position</div>
								<div style="font-size: 1.5rem; font-weight: 900;">#<?php echo $peak; ?></div>
							</div>
							<div class="sc-pop-stat">
								<div style="font-size: 10px; font-weight: 800; color: var(--k-text-muted); text-transform: uppercase;">Previous Week</div>
								<div style="font-size: 1.5rem; font-weight: 900;"><?php echo $prev; ?></div>
							</div>
							<div class="sc-pop-stat">
								<div style="font-size: 10px; font-weight: 800; color: var(--k-text-muted); text-transform: uppercase;">Longevity</div>
								<div style="font-size: 1.5rem; font-weight: 900;"><?php echo $weeks; ?> <span style="font-size: 14px; color: var(--k-text-dim);">W</span></div>
							</div>
						</div>

						<div class="sc-actions" style="display: flex; gap: 12px;">
							<?php if ( $e->spotify_id ) : ?>
								<a href="https://open.spotify.com/track/<?php echo esc_attr($e->spotify_id); ?>" target="_blank" class="sc-btn platform-spotify" style="text-decoration: none; background: var(--k-spotify); color: #fff; padding: 10px 20px; border-radius: 40px; font-size: 12px; font-weight: 800;">Open Spotify</a>
							<?php endif; ?>
							<?php if ( $e->youtube_id ) : ?>
								<a href="https://www.youtube.com/watch?v=<?php echo esc_attr($e->youtube_id); ?>" target="_blank" class="sc-btn platform-youtube" style="text-decoration: none; background: var(--k-youtube); color: #fff; padding: 10px 20px; border-radius: 40px; font-size: 12px; font-weight: 800;">Watch YouTube</a>
							<?php endif; ?>
						</div>
					</div>
				</details>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

</div>

<style>
.sc-row-item summary::-webkit-details-marker { display: none; }
.has-admin-bar .kc-container { padding-top: 100px; }
@media (max-width: 600px) {
	.sc-rank-box { width: 44px; }
	.sc-rank-box div:first-child { font-size: 1.3rem; }
	.sc-info-box div:first-child { font-size: 0.95rem; }
	.sc-stats-box { width: 90px; }
	.sc-expanded { padding: 20px; }
	.kc-hero-title { font-size: 2.2rem; }
}
</style>

<?php get_footer(); ?>
