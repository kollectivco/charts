<?php
/**
 * Kontentainment Charts — Index
 * Premium Spotify-style landing page for music intelligence.
 */
\Charts\Core\StandaloneLayout::get_header();
global $wpdb;

$sources_table  = $wpdb->prefix . 'charts_sources';
$entries_table  = $wpdb->prefix . 'charts_entries';
$periods_table  = $wpdb->prefix . 'charts_periods';
$insights_table = $wpdb->prefix . 'charts_insights';

$analyzer = new \Charts\Services\Analyzer();

// 1. Fetch active sources with metadata
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

foreach ( $sources as &$source ) {
	$source->top_tracks = array();
	if ( $source->entry_count > 0 && $source->latest_period_id ) {
		$source->top_tracks = $wpdb->get_results( $wpdb->prepare(
			"SELECT track_name, artist_names, cover_image, rank_position, movement_direction, movement_value
			 FROM {$entries_table}
			 WHERE source_id = %d AND period_id = %d AND rank_position <= 3
			 ORDER BY rank_position ASC",
			$source->id, $source->latest_period_id
		) );
	}
}
unset( $source );

// 2. Fetch latest insights
$insights = $analyzer->get_latest_insights( 3 );
?>

<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>">

<div class="kc-root <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
	
	<!-- Hero Section -->
	<header class="kc-hero">
		<div class="kc-container">
			<div class="kc-brand-name animate-fade-in-up" style="margin-bottom: 32px;">Kontentainment</div>
			<h1 class="kc-hero-title animate-fade-in-up">The Pulse of <em>Regional Music</em></h1>
			<p class="animate-fade-in-up" style="color: var(--k-text-dim); font-size: 1.1rem; max-width: 600px; line-height: 1.6; animation-delay: 0.1s;">
				Definitive streaming intelligence from Egypt, Saudi Arabia, and the MENA region. Real data. Real rankings. Zero bias.
			</p>
		</div>
	</header>

	<!-- Intelligence Pass (Insights) -->
	<?php if ( ! empty( $insights ) ) : ?>
	<section class="kc-container" style="margin-bottom: 80px;">
		<h2 class="kc-section-title animate-fade-in-up" style="animation-delay: 0.2s;">
			<span>Weekly Intelligence</span>
			<span class="kc-badge" style="background: var(--k-accent); color: #fff; border: none;">Live</span>
		</h2>
		<div class="kc-insight-grid animate-fade-in-up" style="animation-delay: 0.3s;">
			<?php foreach ( $insights as $ins ) : 
				$payload = json_decode( $ins->payload_json );
				$img = !empty($payload->cover_image) ? $payload->cover_image : (!empty($payload->image) ? $payload->image : '');
			?>
				<div class="kc-insight-card">
					<div class="kc-insight-type"><?php echo esc_html( $ins->title ); ?></div>
					<div class="kc-insight-body">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" class="kc-insight-img">
						<?php endif; ?>
						<p><?php echo esc_html( $ins->summary ); ?></p>
					</div>
					<div class="kc-insight-meta">
						<?php echo esc_html( strtoupper( $ins->platform ) ); ?> · <?php echo date('M Y', strtotime($ins->period_start)); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- Charts Grid -->
	<main class="kc-container" style="padding-bottom: 120px;">
		<div class="animate-fade-in-up" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; animation-delay: 0.4s;">
			<h2 class="kc-section-title" style="margin-bottom: 0;">Active Charts</h2>
			<a href="<?php echo home_url('/charts/artists/'); ?>" class="kc-view-btn" style="text-decoration: none; padding-bottom: 5px; border-bottom: 2px solid var(--k-accent);">Explore Artists &rarr;</a>
		</div>
		
		<?php if ( empty( $sources ) ) : ?>
			<div class="kc-empty">
				<h3>No active charts available</h3>
				<p>The intelligence engine is warming up. Please check back shortly.</p>
			</div>
		<?php else : ?>
			<div class="kc-grid animate-fade-in-up" style="animation-delay: 0.5s;">
				<?php foreach ( $sources as $source ) : 
					$platform = strtolower($source->platform);
					
					// Clean Title Logic
					$display_title = 'Top Chart';
					if ( strpos( strtolower($source->chart_type), 'songs' ) !== false || $source->chart_type === 'top-tracks' ) $display_title = 'Top Songs';
					elseif ( strpos( strtolower($source->chart_type), 'artists' ) !== false ) $display_title = 'Top Artists';
					elseif ( strpos( strtolower($source->chart_type), 'videos' ) !== false ) $display_title = 'Top Videos';
					elseif ( strpos( strtolower($source->chart_type), 'viral' ) !== false ) $display_title = 'Viral 50';

					// Try clean route first
					$chart_url = home_url( '/charts/' . $source->chart_type . '/' );
					// Fallback if country or frequency is needed to disambiguate
					// For now, these are primary public faces
					
					$date_label = $source->latest_period_date ? date('M j', strtotime($source->latest_period_date)) : 'N/A';
				?>
				<a href="<?php echo esc_url( $chart_url ); ?>" class="kc-card <?php echo (count($source->top_tracks) >= 3) ? 'kc-card-wide' : ''; ?>">
					<div class="kc-card-header">
						<span class="kc-badge" style="background: #1a1a1a; color: #888;">
							<?php echo esc_html( strtoupper($source->country_code) ); ?> · <?php echo esc_html( ucfirst($source->frequency) ); ?>
						</span>
						<span class="kc-card-date"><?php echo esc_html($date_label); ?></span>
					</div>

					<div class="kc-card-content">
						<h3 class="kc-card-title"><?php echo esc_html($display_title); ?></h3>
						<div class="kc-card-meta">
							<?php echo esc_html(number_format($source->entry_count)); ?> Entries
							&nbsp;·&nbsp;
							<span style="color: var(--k-accent); opacity: 0.8;"><?php echo esc_html( strtoupper($platform) ); ?> Data</span>
						</div>

						<?php if ( ! empty( $source->top_tracks ) ) : ?>
						<div class="kc-card-preview">
							<?php foreach ( $source->top_tracks as $idx => $track ) : 
								$is_top = ($idx === 0);
							?>
								<div class="kc-preview-row" style="<?php echo $is_top ? 'font-weight: 800; color: #fff;' : ''; ?>">
									<span class="kc-preview-rank" style="<?php echo $is_top ? 'color: var(--k-accent);' : ''; ?>"><?php echo $idx + 1; ?></span>
									<span class="kc-preview-name"><?php echo esc_html($track->track_name); ?></span>
									<span class="kc-preview-artist"><?php echo esc_html($track->artist_names); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>

					<div class="kc-card-footer">
						<span class="kc-view-btn">View Intelligence &rarr;</span>
					</div>
				</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</main>

</div>

<style>
/* Local style overrides for index specific layout parts */
.kc-hero { padding: 100px 0 80px; }
.kc-insight-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.kc-insight-card { background: #111; border: 1px solid #222; border-radius: 20px; padding: 24px; display: flex; flex-direction: column; gap: 12px; }
.kc-insight-type { font-size: 10px; font-weight: 900; color: var(--k-accent); text-transform: uppercase; letter-spacing: 0.1em; }
.kc-insight-body { display: flex; align-items: flex-start; gap: 16px; }
.kc-insight-img { width: 50px; height: 50px; border-radius: 8px; flex-shrink: 0; object-fit: cover; }
.kc-insight-body p { font-size: 14px; font-weight: 600; line-height: 1.4; color: #fff; margin: 0; }
.kc-insight-meta { font-size: 11px; font-weight: 700; color: #555; margin-top: auto; }

.kc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 30px; }
.kc-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.kc-card-date { font-size: 11px; font-weight: 700; color: var(--k-text-muted); }
.kc-card-title { font-size: 1.4rem; font-weight: 900; margin: 0 0 8px; line-height: 1.1; }
.kc-card-meta { font-size: 12px; font-weight: 700; color: var(--k-text-dim); margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.05em; }
.kc-card-preview { border-top: 1px solid var(--k-border); padding-top: 20px; margin-bottom: 24px; }
.kc-preview-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; font-size: 13px; }
.kc-preview-rank { font-weight: 900; color: var(--k-border); width: 14px; }
.kc-preview-name { font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.kc-preview-artist { font-size: 11px; color: var(--k-text-dim); white-space: nowrap; }
.kc-view-btn { font-size: 12px; font-weight: 800; color: var(--k-accent); text-transform: uppercase; letter-spacing: 0.1em; }
.kc-empty { padding: 100px 0; text-align: center; color: var(--k-text-dim); }
.has-admin-bar .kc-hero { padding-top: 140px; }
</style>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
