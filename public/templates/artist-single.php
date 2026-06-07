<?php
/**
 * Kontentainment Charts — Artist Intelligence Profile
 * Matches Reference #1
 */

global $wpdb;

// 1. DATA LOOKUP
$slug = get_query_var( 'charts_artist_slug' );
$artist = \Charts\Core\EntityManager::get_entity_by_slug( 'artist', $slug );

// 2. MOBILE BRANCH (Unified Architecture)
$is_mobile = get_query_var('mobile_view') || isset($_GET['mobile_view']);
if ( $is_mobile ) {
    include CHARTS_PATH . 'public/templates/mobile-artist-single.php'; exit;
    return;
}

if ( ! $artist ) {
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
	echo '<div class="kc-root"><h1>Artist Not Found</h1></div>';
	if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); }
	return;
}

$artist->id = (int) $artist->id;
$metadata = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : array();
$debug_notes = array();
$needs_sync = false;

// 1. Resolve Spotify ID if missing
if ( empty( $artist->spotify_id ) ) {
	$sp_api = new \Charts\Services\SpotifyApiClient();
	if ( ! \Charts\Core\Settings::get('api.spotify_client_id') ) {
		$debug_notes[] = 'spotify: api credentials missing';
	} else {
		$search = $sp_api->search_artist( $artist->display_name );
		if ( ! is_wp_error($search) && ! empty($search) ) {
			$best_match = null;
			foreach ( $search as $res ) {
				if ( strcasecmp($res['name'], $artist->display_name) === 0 ) {
					$best_match = $res;
					break;
				}
			}
			if ( ! $best_match ) $best_match = $search[0];

			if ( $best_match ) {
				update_post_meta( $artist->id, '_spotify_id', $best_match['id'] );
				$artist->spotify_id = $best_match['id'];
				$debug_notes[] = 'spotify: resolved ' . $best_match['id'];
				$needs_sync = true;
			}
		}
	}
}

// 2. Resolve YouTube Channel ID if missing
$youtube_channel_id = get_post_meta( $artist->id, '_artist_youtube_channel_id', true );
if ( empty( $youtube_channel_id ) ) {
	$yt_api = new \Charts\Services\YouTubeApiClient();
	if ( $yt_api->is_configured() ) {
		$search = $yt_api->search_channels( $artist->display_name );
		if ( ! is_wp_error($search) && ! empty($search) ) {
			$youtube_channel_id = $search[0]['snippet']['channelId'];
			update_post_meta( $artist->id, '_artist_youtube_channel_id', $youtube_channel_id );
			$debug_notes[] = 'youtube: resolved ' . $youtube_channel_id;
			$needs_sync = true;
		}
	}
}

// 3. Sync Strategy (Enrichment)
$last_sync = get_post_meta( $artist->id, '_artist_last_sync', true ) ?: '1970-01-01 00:00:00';
if ( ( time() - strtotime( $last_sync ) > HOUR_IN_SECONDS * 48 ) ) {
	$needs_sync = true;
}

if ( $needs_sync ) {
	if ( ! empty( $artist->spotify_id ) ) {
		( new \Charts\Services\SpotifyEnrichmentService() )->enrich_artist( $artist->id );
	}
	if ( ! empty( $youtube_channel_id ) ) {
		( new \Charts\Services\YouTubeEnrichmentService() )->enrich_artist( $artist->id );
	}
}

// Re-fetch meta keys
$followers      = get_post_meta( $artist->id, '_artist_followers', true ) ?: 0;
$popularity     = get_post_meta( $artist->id, '_artist_popularity', true ) ?: 0;
$sp_url         = get_post_meta( $artist->id, '_artist_external_url', true ) ?: '';
$yt_subscribers = get_post_meta( $artist->id, '_artist_youtube_subscribers', true ) ?: 0;
$yt_views       = get_post_meta( $artist->id, '_artist_youtube_video_count', true ) ?: 0;
$yt_url         = get_post_meta( $artist->id, '_artist_youtube_url', true ) ?: '';
$genres         = (array) get_post_meta( $artist->id, '_artist_genres', true );
$sp_top_tracks  = get_post_meta( $artist->id, '_artist_spotify_top_tracks', true );
if ( ! is_array($sp_top_tracks) ) $sp_top_tracks = array();

// Centralized image resolution
$display_image = \Charts\Core\PublicIntegration::resolve_artwork($artist, 'artist');

// Safe Escaping for SQL
$artist_name_escaped = '%' . $wpdb->esc_like( $artist->display_name ) . '%';


// Popular tracks (Improved with string fallback & type enforcement)
$popular_tracks_raw = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*
	FROM {$wpdb->prefix}charts_entries e
	WHERE e.item_type != 'artist' AND (
		(e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d) AND e.item_type = 'track')
		OR (e.item_id IN (SELECT video_id FROM {$wpdb->prefix}charts_video_artists WHERE artist_id = %d) AND e.item_type = 'video')
		OR (e.artist_names LIKE %s AND e.item_type IN ('track', 'video'))
	)
	ORDER BY e.rank_position ASC LIMIT 50
", $artist->id, $artist->id, $artist_name_escaped ) );

$unique_tracks = array();
foreach($popular_tracks_raw as $pt) {
    $pt_resolved = \Charts\Core\PublicIntegration::resolve_display_name($pt);
    $title_key = strtolower(trim($pt_resolved['title']));
    
    if ( ! isset($unique_tracks[$title_key]) ) {
        $table = ($pt->item_type === 'video') ? 'charts_videos' : 'charts_tracks';
        $col   = ($pt->item_type === 'video') ? 'thumbnail' : 'cover_image';
        $pt->resolved_image = $wpdb->get_var($wpdb->prepare("SELECT $col FROM {$wpdb->prefix}{$table} WHERE id = %d", $pt->item_id));
        $unique_tracks[$title_key] = $pt;
    } else {
        // Merge views if missing
        if ( empty($unique_tracks[$title_key]->views_count) && !empty($pt->views_count) ) {
            $unique_tracks[$title_key]->views_count = $pt->views_count;
        }
        if ( empty($unique_tracks[$title_key]->streams_count) && !empty($pt->streams_count) ) {
            $unique_tracks[$title_key]->streams_count = $pt->streams_count;
        }
        // Keep the best rank
        if ( $pt->rank_position < $unique_tracks[$title_key]->rank_position ) {
            $unique_tracks[$title_key]->rank_position = $pt->rank_position;
        }
    }
}
$popular_tracks = array_slice(array_values($unique_tracks), 0, 5);

// Chart Rankings for the Artist Profile itself
$chart_rankings_raw = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*
	FROM {$wpdb->prefix}charts_entries e
	INNER JOIN (
		SELECT MAX(e2.id) as max_id 
		FROM {$wpdb->prefix}charts_entries e2
		WHERE (e2.item_id = %d AND e2.item_type = 'artist')
		   OR (e2.artist_names LIKE %s AND e2.item_type = 'artist')
		GROUP BY e2.source_id
	) latest ON latest.max_id = e.id
	ORDER BY e.rank_position ASC LIMIT 20
", $artist->id, $artist_name_escaped ) );

$unique_charts = array();
foreach($chart_rankings_raw as $cr) {
	// Resolve parent chart title for this source.
	$row = $wpdb->get_row($wpdb->prepare("
		SELECT d.title FROM {$wpdb->prefix}charts_definitions d
		JOIN {$wpdb->prefix}charts_sources s ON (s.chart_type = CONCAT('cid-', d.id))
		WHERE s.id = %d LIMIT 1
	", $cr->source_id));

	if ( ! $row ) {
		// Fallback for legacy generic sources
		$row = $wpdb->get_row($wpdb->prepare("
			SELECT d.title FROM {$wpdb->prefix}charts_definitions d
			JOIN {$wpdb->prefix}charts_sources s ON (s.chart_type = d.chart_type AND s.country_code = d.country_code)
			WHERE s.id = %d LIMIT 1
		", $cr->source_id));
	}
	$cr->definition_title = $row ? $row->title : 'أفضل ' . \Charts\Core\Translation::get('Artist') . 'ين';
	
	$title_key = strtolower(trim($cr->definition_title));
	if ( ! isset($unique_charts[$title_key]) ) {
	    $unique_charts[$title_key] = $cr;
	} else {
	    if ( $cr->rank_position < $unique_charts[$title_key]->rank_position ) {
	        $unique_charts[$title_key] = $cr;
	    }
	}
}

// Sort by rank position and limit to 4
usort($unique_charts, function($a, $b) {
    return $a->rank_position <=> $b->rank_position;
});
$chart_rankings = array_slice($unique_charts, 0, 4);

if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_header(); }
?>

<div class="kc-root kc-artist-profile-root" style="background: var(--k-bg); color: var(--k-text);">
	<div class="kc-container">
		
		<!-- ARTIST HEADER -->
		<header class="kc-profile-header" style="margin-top: 60px; display: flex; align-items: center; gap: 40px;">
			<img src="<?php echo esc_url($display_image); ?>" class="kc-profile-avatar" style="width: 180px; height: 180px; border-radius: 50%; object-fit: cover; box-shadow: var(--k-shadow-lg);">
			<div class="kc-profile-info">
				<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
					<span class="kc-eyebrow" style="margin: 0; background: var(--k-accent); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 9px; font-weight: 900; text-transform: uppercase;">فنان</span>
				</div>
				<?php 
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($artist);
				?>
				<h1 class="kc-page-title <?php echo \Charts\Core\Typography::get_font_class($resolved['title']); ?>" style="margin: 0; line-height: 1;"><?php echo esc_html($resolved['title']); ?></h1>

				<?php if ( ! empty($genres) ) : ?>
					<div style="display: flex; gap: 8px; margin-top: 20px; flex-wrap: wrap;">
						<?php foreach ( array_slice($genres, 0, 3) as $genre ) : ?>
							<span style="background: var(--k-surface-alt); color: var(--k-text-dim); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: capitalize;"><?php echo esc_html($genre); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

		<?php 
			$artist_stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}charts_intelligence WHERE entity_type = 'artist' AND entity_id = %d", $artist->id));
			if ($artist_stats) :
				$art_meta = !empty($artist_stats->metadata_json) ? json_decode($artist_stats->metadata_json, true) : [];
				$pred_rank   = intval($art_meta['predicted_artist_rank'] ?? $artist_stats->predicted_peak ?? 0);
				$power_score = round(floatval($artist_stats->artist_power_score ?? 0));
				$pred_next   = intval($artist_stats->predicted_next_week ?? 0);
				$exp_growth  = round(floatval($artist_stats->growth_rate ?? $artist_stats->predicted_next_month ?? 0), 1);

				// Only show the strip if there's at least one meaningful value
				$has_stats = ($power_score > 0 || $pred_rank > 0 || $pred_next > 0 || $exp_growth != 0);
			?>
				<?php if ($has_stats) : ?>
				<div class="artist-power-strip" dir="ltr" style="display:flex; align-items:center; gap: 0; margin-top: 25px; background: #fff; border: 1px solid var(--k-border); padding: 0; border-radius: 14px; overflow:hidden;">

					<?php if ($power_score > 0) : ?>
					<div style="padding: 16px 24px; flex:1; text-align:center;">
						<span style="font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; display:block; margin-bottom: 6px; letter-spacing:0.05em;">قوة الفنان</span>
						<div style="display:flex; align-items:center; justify-content:center; gap: 10px;">
							<span style="font-size: 26px; font-weight:900; color:#6366f1; line-height:1;"><?php echo $power_score; ?></span>
							<div style="width: 60px; height: 5px; background:#f1f5f9; border-radius:2px; position:relative; overflow:hidden;">
								<div style="width: <?php echo min(100, $power_score); ?>%; height:100%; background:linear-gradient(90deg, #6366f1, #fe025b); border-radius:2px; position:absolute; top:0; left:0;"></div>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<?php if ($pred_rank > 0) : ?>
					<div style="padding: 16px 24px; flex:1; text-align:center; border-right: 1px solid var(--k-border);">
						<span style="font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; display:block; margin-bottom: 6px; letter-spacing:0.05em;">توقع المركز</span>
						<span style="font-size:24px; font-weight:900; color:var(--k-text); line-height:1; display:block;">#<?php echo $pred_rank; ?></span>
					</div>
					<?php endif; ?>

					<?php if ($exp_growth != 0) : ?>
					<div style="padding: 16px 24px; flex:1; text-align:center; border-right: 1px solid var(--k-border);">
						<span style="font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; display:block; margin-bottom: 6px; letter-spacing:0.05em;">النمو المتوقع</span>
						<span style="font-size:24px; font-weight:900; color:<?php echo $exp_growth >= 0 ? '#10b981' : '#fe025b'; ?>; line-height:1; display:block;">
							<?php echo ($exp_growth >= 0 ? '+' : '') . $exp_growth; ?>%
						</span>
					</div>
					<?php endif; ?>

					<?php if ($pred_next > 0) : ?>
					<div style="padding: 16px 24px; flex:1; text-align:center; border-right: 1px solid var(--k-border);">
						<span style="font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; display:block; margin-bottom: 6px; letter-spacing:0.05em;">توقع الأسبوع</span>
						<span style="font-size:24px; font-weight:900; color:#10b981; line-height:1; display:block;">#<?php echo $pred_next; ?></span>
					</div>
					<?php endif; ?>

				</div>
				<?php endif; ?>
			<?php endif; ?>
			</div>
		</header>



		<!-- ABOUT (Conditional) -->
		<?php 
		$bio = $metadata['bio'] ?? $metadata['description'] ?? '';
		if ( ! empty($bio) ) : 
		?>
		<section class="kc-card" style="margin-bottom: 60px; padding: 40px;">
			<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 20px;"><?php echo \Charts\Core\Translation::get('About'); ?></h3>
			<p style="font-size: 15px; line-height: 1.7; color: var(--k-text-dim);">
				<?php echo wp_kses_post($bio); ?>
			</p>
		</section>
		<?php endif; ?>

		<!-- MAIN GRID -->
		<div class="kc-artist-grid">
			
			<!-- COL 1 -->
			<div>

				<!-- POPULAR TRACKS -->
				<section>
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;"><?php echo \Charts\Core\Translation::get('Popular Tracks'); ?></h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php if ( !empty($popular_tracks) ) : ?>
							<?php foreach ( $popular_tracks as $pt ) : 
								if ( empty($pt->item_slug) || empty($pt->track_name) ) continue;
							?>
								<a href="<?php echo home_url('/charts/' . ($pt->item_type === 'video' ? 'video' : 'track') . '/' . $pt->item_slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($pt->rank_position); ?></span>
										<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($pt, $pt->item_type)); ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover;">
										<?php 
											$pt_resolved = \Charts\Core\PublicIntegration::resolve_display_name($pt);
											$a_resolved  = \Charts\Core\PublicIntegration::resolve_display_name($artist);
										?>
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($pt_resolved['title']); ?>"><?php echo esc_html($pt_resolved['title']); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);" class="<?php echo \Charts\Core\Typography::get_font_class($a_resolved['title']); ?>"><?php echo esc_html($a_resolved['title']); ?></span>
										</div>
									</div>
									<div style="display: flex; align-items: center; gap: 20px;">
										<?php if ( ! empty($pt->views_count) ) : ?>
										<span style="font-size: 12px; font-weight: 700; color: var(--k-text-muted);"><?php echo number_format($pt->views_count / 1000000, 1); ?>M</span>
										<?php endif; ?>
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><polyline points="9 18 15 12 9 6"></polyline></svg>
									</div>
								</a>
							<?php endforeach; ?>
						<?php elseif ( !empty($sp_top_tracks) ) : ?>
							<?php $rk=1; foreach ( array_slice($sp_top_tracks, 0, 3) as $spt ) : 
								if ( ! is_array($spt) || empty($spt['name']) ) continue;
							?>
								<div class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($rk++); ?></span>
										<img src="<?php echo esc_url(($spt['image'] ?? '') ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);"><?php echo esc_html($spt['name']); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);"><?php echo esc_html(\Charts\Core\Translation::get($artist->display_name)); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);"><?php echo \Charts\Core\Translation::get('No popular tracks data.'); ?></p
						<?php endif; ?>
					</div>
				</section>
			</div>

			<!-- COL 2 (WIDGETS) -->
			<div>
				<!-- CHART RANKINGS -->
				<section style="margin-bottom: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;"><?php echo \Charts\Core\Translation::get('Chart Rankings'); ?></h3>
					<div style="display: flex; flex-direction: column; gap: 16px;">
						<?php if ( empty($chart_rankings) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);"><?php echo \Charts\Core\Translation::get('No current rankings found.'); ?></p
						<?php else : ?>
							<?php foreach ( $chart_rankings as $cr ) : ?>
								<div class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px;">
									<div style="display: flex; align-items: center; gap: 12px;">
										<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($artist, 'artist')); ?>" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
										<span style="font-size: 13px; font-weight: 800;" class="<?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($cr->definition_title ?: 'Top Artists')); ?>"><?php echo esc_html(\Charts\Core\Translation::get($cr->definition_title ?: 'Top Artists')); ?></span>
									</div>
									<div style="text-align: right;">
										<div style="font-size: 24px; font-weight: 950; color: var(--k-text);">#<?php echo \Charts\Core\Transliteration::to_arabic_numerals($cr->rank_position); ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>

				<!-- ALBUMS (Conditional) -->
				<?php 
				$albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_albums WHERE primary_artist_id = %d LIMIT 2", $artist->id ) );
				if ( ! empty($albums) ) : 
				?>
				<section>
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;"><?php echo \Charts\Core\Translation::get('Albums'); ?></h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php foreach ( $albums as $album ) : ?>
						<div class="kc-card" style="display: flex; align-items: center; gap: 16px;">
							<img src="<?php echo esc_url($album->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover;">
							<div>
								<h4 style="font-size: 14px; font-weight: 900; margin: 0;"><?php echo esc_html($album->title); ?></h4>
								<?php if ( ! empty($album->release_date) ) : ?>
								<span style="display: block; font-size: 11px; color: var(--k-text-muted); margin-top: 4px;"><?php echo date('Y', strtotime($album->release_date)); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>
			</div>

		</div>

		<!-- LATEST NEWS -->
		<?php 
		$news_args = array(
			'post_type' => 'post',
			'posts_per_page' => 4,
			'tax_query' => array(
				array(
					'taxonomy' => 'artists', // Standard post taxonomy mapped to this
					'field'    => 'name',
					'terms'    => $artist->display_name,
				),
			),
		);
		$news_query = new \WP_Query( $news_args );

		if ( $news_query->have_posts() ) :
		?>
		<section class="kc-section" style="padding-top: 80px;" dir="rtl">
			<div class="kc-section-header" style="justify-content: flex-start; margin-bottom: 32px;">
				<h2 class="kc-section-title" style="font-size: 32px; font-weight: 900; color: #fff; letter-spacing: -0.02em;">آخر أخبار <?php echo esc_html(\Charts\Core\Translation::get($artist->display_name)); ?></h2>
			</div>
			
			<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
				<?php while ( $news_query->have_posts() ) : $news_query->the_post(); 
					$cats = get_the_category();
					$cat_name = !empty($cats) ? $cats[0]->name : 'موسيقى';
					$thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: CHARTS_URL . 'public/assets/img/placeholder.png';
					$author_name = get_the_author();
					if ( empty($author_name) ) $author_name = 'بيلبورد عربية';
				?>
					<a href="<?php the_permalink(); ?>" style="text-decoration: none; display: flex; background: #272732; border-radius: 4px; padding: 24px; gap: 24px; transition: transform 0.2s;">
						<!-- Image (First child in RTL = Right) -->
						<div style="width: 180px; height: 120px; flex-shrink: 0;">
							<img src="<?php echo esc_url($thumb); ?>" style="width: 100%; height: 100%; object-fit: cover;">
						</div>
						
						<!-- Content (Left) -->
						<div style="flex: 1; display: flex; flex-direction: column;">
							<div style="display: flex; justify-content: flex-start; margin-bottom: 12px;">
								<span style="font-size: 13px; color: #cbd5e1; border-bottom: 2px solid #e11d48; padding-bottom: 6px; font-weight: 700;"><?php echo esc_html($cat_name); ?></span>
							</div>
							<h3 style="font-size: 18px; font-weight: 800; color: #fff; margin: 0 0 16px; line-height: 1.5; text-align: right;"><?php the_title(); ?></h3>
							
							<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto; color: #94a3b8; font-size: 12px; font-weight: 500;">
								<span><?php echo esc_html($author_name); ?></span>
								<span style="display: flex; align-items: center; gap: 8px;">
									<?php echo get_the_date('d F Y'); ?>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
								</span>
							</div>
						</div>
					</a>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- MORE CHARTS -->
		<section class="kc-section" style="padding-top: 100px;">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> <?php echo \Charts\Core\Translation::get('More Charts'); ?></h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all"><?php echo \Charts\Core\Translation::get('View All Charts'); ?> &larr;</a>
			</div>
			
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php 
				$mdefs = \Charts\Core\PublicIntegration::get_eligible_definitions( 4 );
				foreach ( $mdefs as $mdef ) : 
					$mentries = \Charts\Core\PublicIntegration::get_preview_entries( $mdef, 4 );
				?>
					<article class="kc-chart-card">
						<div class="kc-card-accent-dot" style="background: <?php echo $mdef->accent_color ?: '#fe025b'; ?>;"></div>
						<div class="kc-card-header">
							<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_chart_image($mdef, $mentries)); ?>">
							<div class="kc-card-header-overlay"></div>
							<span class="kc-card-label">قائمة الأسبوع</span>
							<h3 class="kc-card-title"><?php echo esc_html(\Charts\Core\Translation::get($mdef->title)); ?></h3>
						</div>
						<div class="kc-card-list">
							<?php foreach ( $mentries as $me ) : ?>
								<div class="kc-card-entry">
									<span class="kc-entry-rank"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($me->rank_position); ?></span>
									<img class="kc-entry-art" src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($me, $me->item_type)); ?>">
									<div class="kc-entry-info">
										<span class="kc-entry-name <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($me->track_name)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($me->track_name)); ?></span>
										<span class="kc-entry-artist <?php echo \Charts\Core\Typography::get_font_class(\Charts\Core\Translation::get($me->artist_names)); ?>"><?php echo esc_html(\Charts\Core\Translation::get($me->artist_names)); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="kc-card-footer" style="justify-content: center;">
							<a href="<?php echo home_url('/charts/'.$mdef->slug.'/'); ?>" class="kc-card-cta">عرض القائمة كاملة</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php if ( ! $is_mobile ) { \Charts\Core\PublicIntegration::get_footer(); } ?>
