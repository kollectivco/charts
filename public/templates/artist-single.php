<?php
/**
 * Kontentainment Charts — Artist Intelligence Profile
 * Matches Reference #1
 */

global $wpdb;

$slug = get_query_var( 'charts_artist_slug' );
$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s", $slug ) );

if ( ! $artist ) {
	\Charts\Core\PublicIntegration::get_header();
	echo '<div class="kc-root"><h1>Artist Not Found</h1></div>';
	\Charts\Core\PublicIntegration::get_footer();
	return;
}

// Metadata decoding
$metadata = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
$debug_notes = array();
$needs_sync = false;

// 1. Resolve Spotify ID if missing
if ( empty( $artist->spotify_id ) ) {
	$sp_api = new \Charts\Services\SpotifyApiClient();
	if ( ! get_option('charts_spotify_client_id') ) {
		$debug_notes[] = 'spotify: api credentials missing';
	} else {
		$search = $sp_api->search_artist( $artist->display_name );
		if ( is_wp_error($search) ) {
			$debug_notes[] = 'spotify: search error - ' . $search->get_error_message();
		} elseif ( empty($search) ) {
			$debug_notes[] = 'spotify: no match found';
		} else {
			$best_match = null;
			foreach ( $search as $res ) {
				if ( strtolower($res['name']) === strtolower($artist->display_name) ) {
					$best_match = $res;
					break;
				}
			}
			if ( ! $best_match && !empty($search[0]) ) {
				$best_match = $search[0];
			}

			if ( $best_match ) {
				$wpdb->update( $wpdb->prefix . 'charts_artists', array('spotify_id' => $best_match['id']), array('id' => $artist->id) );
				$artist->spotify_id = $best_match['id'];
				$debug_notes[] = 'spotify: resolved ' . $best_match['id'];
				$needs_sync = true;
			} else {
				$debug_notes[] = 'spotify: low-confidence result rejected';
			}
		}
	}
}

// 2. Resolve YouTube Channel ID if missing
$youtube_channel_id = $metadata['youtube_channel_id'] ?? null;
if ( empty( $youtube_channel_id ) ) {
	$yt_api = new \Charts\Services\YouTubeApiClient();
	if ( ! $yt_api->is_configured() ) {
		$debug_notes[] = 'youtube: api credentials missing';
	} else {
		$search = $yt_api->search_channels( $artist->display_name );
		if ( is_wp_error($search) ) {
			$debug_notes[] = 'youtube: search error - ' . $search->get_error_message();
		} elseif ( empty($search) ) {
			$debug_notes[] = 'youtube: no channel match found';
		} else {
			$best_match = $search[0];
			$youtube_channel_id = $best_match['snippet']['channelId'];
			
			$metadata['youtube_channel_id'] = $youtube_channel_id;
			$wpdb->update( $wpdb->prefix . 'charts_artists', array('metadata_json' => json_encode($metadata)), array('id' => $artist->id) );
			$debug_notes[] = 'youtube: resolved ' . $youtube_channel_id;
			$needs_sync = true;
		}
	}
}

// 3. Stale sync strategy
if ( ! empty( $artist->spotify_id ) ) {
	$last_sync = $metadata['last_sync'] ?? '1970-01-01 00:00:00';
	if ( empty($metadata['followers']) || ( time() - strtotime( $last_sync ) > HOUR_IN_SECONDS * 48 ) ) {
		$needs_sync = true;
	}
}
if ( ! empty( $youtube_channel_id ) ) {
    $yt_last_sync = $metadata['youtube_last_sync'] ?? '1970-01-01 00:00:00';
    if ( empty($metadata['youtube_subscribers']) || ( time() - strtotime( $yt_last_sync ) > HOUR_IN_SECONDS * 48 ) ) {
        $needs_sync = true;
    }
}

if ( $needs_sync ) {
	if ( ! empty( $artist->spotify_id ) ) {
		( new \Charts\Services\SpotifyEnrichmentService() )->enrich_artist( $artist->id );
	}
	if ( ! empty( $youtube_channel_id ) ) {
		( new \Charts\Services\YouTubeEnrichmentService() )->enrich_artist( $artist->id );
	}
	// Refresh object
	$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $artist->id ) );
	$metadata = ! empty( $artist->metadata_json ) ? json_decode( $artist->metadata_json, true ) : array();
}

$genres        = $metadata['genres'] ?? array();
$followers     = $metadata['followers'] ?? 0;
$popularity    = $metadata['popularity'] ?? 0;
$sp_url        = $metadata['external_url'] ?? '';
$yt_subscribers= $metadata['youtube_subscribers'] ?? 0;
$yt_views      = $metadata['youtube_video_count'] ?? 0;
$yt_url        = $metadata['youtube_url'] ?? '';
$sp_top_tracks = $metadata['spotify_top_tracks'] ?? array();

// Centralized image resolution
$display_image = \Charts\Core\PublicIntegration::resolve_artwork($artist, 'artist');

// Charting tracks - Enriched with canonical data (including collaborations)
$charting_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, 
	       COALESCE(t.cover_image, v.thumbnail) as canonical_image,
	       t.cover_image as track_cover,
	       v.thumbnail as video_thumb
	FROM {$wpdb->prefix}charts_entries e
	LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
	LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
	WHERE e.item_type IN ('track', 'video') 
	  AND (
	  	(e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d))
	  	OR 
	  	(e.item_type = 'video' AND e.item_id IN (SELECT video_id FROM {$wpdb->prefix}charts_video_artists WHERE artist_id = %d))
	  )
	GROUP BY e.item_type, e.item_id
	ORDER BY e.rank_position ASC LIMIT 4
", $artist->id, $artist->id ) );

// Popular tracks - Enriched with canonical data (including collaborations)
$popular_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, 
	       t.cover_image as track_cover,
	       v.thumbnail as video_thumb
	FROM {$wpdb->prefix}charts_entries e
	LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
	LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
	WHERE e.item_type IN ('track', 'video') 
	  AND (
	  	(e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d))
	  	OR 
	  	(e.item_type = 'video' AND e.item_id IN (SELECT video_id FROM {$wpdb->prefix}charts_video_artists WHERE artist_id = %d))
	  )
	GROUP BY e.item_type, e.item_id
	ORDER BY e.rank_position ASC LIMIT 5
", $artist->id, $artist->id ) );

// Chart Rankings
$chart_rankings = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, d.title as definition_title 
	FROM {$wpdb->prefix}charts_entries e
	JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
	LEFT JOIN {$wpdb->prefix}charts_definitions d ON d.chart_type = s.chart_type AND d.country_code = s.country_code
	WHERE (e.item_id = %d AND e.item_type = 'artist')
	ORDER BY e.rank_position ASC LIMIT 2
", $artist->id ) );

\Charts\Core\PublicIntegration::get_header();
?>

<div class="kc-root" style="background: var(--k-bg); color: var(--k-text);">
	<div class="kc-container">
		
		<!-- ARTIST HEADER -->
		<header class="kc-profile-header" style="margin-top: 60px; display: flex; align-items: center; gap: 40px;">
			<img src="<?php echo esc_url($display_image); ?>" class="kc-profile-avatar" style="width: 180px; height: 180px; border-radius: 50%; object-fit: cover; box-shadow: var(--k-shadow-lg);">
			<div class="kc-profile-info">
				<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
					<span class="kc-eyebrow" style="margin: 0; background: var(--k-accent); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 9px; font-weight: 900; text-transform: uppercase;">Artist</span>
				</div>
				<h1 class="kc-page-title <?php echo \Charts\Core\Typography::get_font_class($artist->display_name); ?>" style="margin: 0; line-height: 1;"><?php echo esc_html($artist->display_name); ?></h1>
				<?php if ( ! empty($artist->display_name_franko) ) : ?>
					<div style="font-size: 20px; font-weight: 700; color: var(--k-text-muted); margin-top: 8px; opacity: 0.4;"><?php echo esc_html($artist->display_name_franko); ?></div>
				<?php endif; ?>

				<?php if ( ! empty($genres) ) : ?>
					<div style="display: flex; gap: 8px; margin-top: 20px; flex-wrap: wrap;">
						<?php foreach ( array_slice($genres, 0, 3) as $genre ) : ?>
							<span style="background: var(--k-surface-alt); color: var(--k-text-dim); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: capitalize;"><?php echo esc_html($genre); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</header>



		<!-- ABOUT (Conditional) -->
		<?php 
		$bio = $metadata['bio'] ?? $metadata['description'] ?? '';
		if ( ! empty($bio) ) : 
		?>
		<section class="kc-card" style="margin-bottom: 60px; padding: 40px;">
			<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 20px;">About</h3>
			<p style="font-size: 15px; line-height: 1.7; color: var(--k-text-dim);">
				<?php echo wp_kses_post($bio); ?>
			</p>
		</section>
		<?php endif; ?>

		<!-- MAIN GRID -->
		<div class="kc-artist-grid">
			
			<!-- COL 1 -->
			<div>
				<!-- CHARTING TRACKS -->
				<section style="margin-bottom: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Charting Tracks</h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php if ( empty($charting_tracks) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No current charting tracks.</p>
						<?php else : ?>
							<?php foreach ( $charting_tracks as $ct ) : ?>
								<a href="<?php echo home_url('/charts/' . ($ct->item_type === 'video' ? 'clip' : 'track') . '/' . $ct->item_slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo $ct->rank_position; ?></span>
										<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($ct, $ct->item_type)); ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($ct->track_name); ?>"><?php echo esc_html($ct->track_name); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-dim);" class="<?php echo \Charts\Core\Typography::get_font_class($artist->display_name); ?>"><?php echo esc_html($artist->display_name); ?></span>
										</div>
									</div>
									<div style="display: flex; align-items: center; gap: 20px;">
										<?php if ( ! empty($ct->peak_rank) ) : ?>
										<div style="text-align: right;">
											<span style="display: block; font-size: 9px; color: var(--k-text-muted);">Peak #<?php echo intval($ct->peak_rank); ?></span>
										</div>
										<?php endif; ?>
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><polyline points="9 18 15 12 9 6"></polyline></svg>
									</div>
								</a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>

				<!-- POPULAR TRACKS -->
				<section>
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Popular Tracks</h3>
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<?php if ( !empty($popular_tracks) ) : ?>
							<?php foreach ( $popular_tracks as $pt ) : ?>
								<a href="<?php echo home_url('/charts/' . ($pt->item_type === 'video' ? 'clip' : 'track') . '/' . $pt->item_slug); ?>" class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo $pt->rank_position; ?></span>
										<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($pt, $pt->item_type)); ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);" class="<?php echo \Charts\Core\Typography::get_font_class($pt->track_name); ?>"><?php echo esc_html($pt->track_name); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);" class="<?php echo \Charts\Core\Typography::get_font_class($artist->display_name); ?>"><?php echo esc_html($artist->display_name); ?></span>
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
							<?php $rk=1; foreach ( array_slice($sp_top_tracks, 0, 3) as $spt ) : ?>
								<div class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; text-decoration: none;">
									<div style="display: flex; align-items: center; gap: 20px;">
										<span style="font-size: 16px; font-weight: 900; color: var(--k-text-muted); width: 24px;"><?php echo $rk++; ?></span>
										<img src="<?php echo esc_url($spt['image'] ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover;">
										<div>
											<span style="display: block; font-size: 14px; font-weight: 800; color: var(--k-text);"><?php echo esc_html($spt['name']); ?></span>
											<span style="display: block; font-size: 11px; color: var(--k-text-muted);"><?php echo esc_html($artist->display_name); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No popular tracks data.</p>
						<?php endif; ?>
					</div>
				</section>
			</div>

			<!-- COL 2 (WIDGETS) -->
			<div>
				<!-- CHART RANKINGS -->
				<section style="margin-bottom: 60px;">
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Chart Rankings</h3>
					<div style="display: flex; flex-direction: column; gap: 16px;">
						<?php if ( empty($chart_rankings) ) : ?>
							<p style="font-size: 13px; font-weight: 600; color: var(--k-text-muted);">No current rankings found.</p>
						<?php else : ?>
							<?php foreach ( $chart_rankings as $cr ) : ?>
								<div class="kc-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px;">
									<div style="display: flex; align-items: center; gap: 12px;">
										<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($artist, 'artist')); ?>" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
										<span style="font-size: 13px; font-weight: 800;" class="<?php echo \Charts\Core\Typography::get_font_class($cr->definition_title); ?>"><?php echo esc_html($cr->definition_title ?: 'Top Artists'); ?></span>
									</div>
									<div style="text-align: right;">
										<div style="font-size: 24px; font-weight: 950; color: var(--k-text);">#<?php echo $cr->rank_position; ?></div>
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
					<h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--k-text-muted); margin-bottom: 32px;">Albums</h3>
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

		<!-- MORE CHARTS -->
		<section class="kc-section" style="padding-top: 100px;">
			<div class="kc-section-header">
				<h2 class="kc-section-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg> More Charts</h2>
				<a href="<?php echo home_url('/charts'); ?>" class="kc-view-all">View All Charts &rarr;</a>
			</div>
			
			<div class="kc-grid kc-grid-4" style="gap: 32px;">
				<?php 
				$mdefs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_definitions WHERE is_public = 1 LIMIT 4" );
				foreach ( $mdefs as $mdef ) : 
					$mentries = $wpdb->get_results( $wpdb->prepare( "
						SELECT e.*, 
						       t.cover_image as track_cover,
						       v.thumbnail as video_thumb,
						       a.image as artist_image
						FROM {$wpdb->prefix}charts_entries e 
						JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id 
						LEFT JOIN {$wpdb->prefix}charts_tracks t ON (e.item_id = t.id AND e.item_type = 'track')
						LEFT JOIN {$wpdb->prefix}charts_videos v ON (e.item_id = v.id AND e.item_type = 'video')
						LEFT JOIN {$wpdb->prefix}charts_artists a ON (e.item_id = a.id AND e.item_type = 'artist')
						WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
						ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 4"
					, $mdef->chart_type, $mdef->country_code ) );
				?>
					<article class="kc-chart-card">
						<div class="kc-card-accent-dot" style="background: <?php echo $mdef->accent_color ?: '#fe025b'; ?>;"></div>
						<div class="kc-card-header">
							<img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($mentries[0], $mentries[0]->item_type)); ?>">
							<div class="kc-card-header-overlay"></div>
							<span class="kc-card-label">Weekly Chart</span>
							<h3 class="kc-card-title"><?php echo esc_html($mdef->title); ?></h3>
						</div>
						<div class="kc-card-list">
							<?php foreach ( $mentries as $me ) : ?>
								<div class="kc-card-entry">
									<span class="kc-entry-rank"><?php echo $me->rank_position; ?></span>
									<img class="kc-entry-art" src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($me, $me->item_type)); ?>">
									<div class="kc-entry-info">
										<span class="kc-entry-name"><?php echo esc_html($me->track_name); ?></span>
										<span class="kc-entry-artist"><?php echo esc_html($me->artist_names); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="kc-card-footer" style="justify-content: center;">
							<a href="<?php echo home_url('/charts/'.$mdef->slug.'/'); ?>" class="kc-card-cta">See Full Chart</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	</div>
</div>

<?php \Charts\Core\PublicIntegration::get_footer(); ?>
