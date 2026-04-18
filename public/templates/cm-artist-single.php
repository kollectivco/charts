<?php
/**
 * CM Mobile Experience: Artist Profile View
 */

global $wpdb;

$slug = get_query_var( 'charts_artist_slug' );
$artist = \Charts\Core\EntityManager::get_entity_by_slug( 'artist', $slug );

if ( ! $artist ) {
    wp_redirect(add_query_arg('mobile_view', '1', home_url('/charts')));
    exit;
}

$metadata = !empty($artist->metadata_json) ? json_decode($artist->metadata_json, true) : array();
$display_image = \Charts\Core\PublicIntegration::resolve_artwork($artist, 'artist');
$artist_name_escaped = '%' . $wpdb->esc_like( $artist->display_name ) . '%';

// Helpers
$link = function($path) {
    $url = home_url($path);
    return add_query_arg('mobile_view', '1', $url);
};

// Popular tracks
$popular_tracks = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*
	FROM {$wpdb->prefix}charts_entries e
	WHERE e.item_type != 'artist' AND (
		(e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = %d) AND e.item_type = 'track')
		OR (e.item_id IN (SELECT video_id FROM {$wpdb->prefix}charts_video_artists WHERE artist_id = %d) AND e.item_type = 'video')
		OR (e.artist_names LIKE %s AND e.item_type IN ('track', 'video'))
	)
	GROUP BY e.item_type, e.item_id
	ORDER BY e.rank_position ASC LIMIT 10
", $artist->id, $artist->id, $artist_name_escaped ) );

// Chart Rankings
$chart_rankings = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*
	FROM {$wpdb->prefix}charts_entries e
	WHERE (e.item_id = %d AND e.item_type = 'artist')
	   OR (e.artist_names LIKE %s AND e.item_type = 'artist')
	ORDER BY e.rank_position ASC LIMIT 5
", $artist->id, $artist_name_escaped ) );

foreach($chart_rankings as $cr) {
	$row = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}charts_definitions d JOIN {$wpdb->prefix}charts_sources s ON (s.chart_type = CONCAT('cid-', d.id)) WHERE s.id = %d LIMIT 1", $cr->source_id));
	if (!$row) $row = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}charts_definitions d JOIN {$wpdb->prefix}charts_sources s ON (s.chart_type = d.chart_type AND s.country_code = d.country_code) WHERE s.id = %d LIMIT 1", $cr->source_id));
	$cr->definition_title = $row ? $row->title : 'Top Artists';
}

$site_title = get_bloginfo('name');
$resolved = \Charts\Core\PublicIntegration::resolve_display_name($artist);
?>
<!DOCTYPE html>
<!-- CM_RENDERER: public/templates/cm-artist-single.php -->
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo esc_html($resolved['title']); ?> — CM</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/cm.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
</head>
<body <?php body_class('cm-body-mobile cm-view-artist-single'); ?>>

<div class="cm-app-shell">
    
    <header class="cm-header" style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(--cm-divider); position:sticky; top:0; z-index:100; background:var(--cm-surface);">
        <a href="javascript:history.back()" style="color:var(--cm-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
        <h1 style="font-size: 16px; margin:0; line-height:1; font-weight:800;">Artist Profile</h1>
    </header>

    <main class="cm-content">
        <!-- Hero -->
        <section class="cm-section" style="padding: 32px 20px; text-align: center;">
            <img src="<?php echo esc_url($display_image); ?>" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; box-shadow: var(--cm-shadow-sm); margin: 0 auto 16px;">
            <h2 style="font-size: 32px; font-weight: 950; margin: 0; line-height: 1;"><?php echo esc_html($resolved['title']); ?></h2>
            <div style="display: flex; gap: 8px; justify-content: center; margin-top: 16px;">
                <span style="background: var(--cm-primary); color: #fff; font-size: 9px; font-weight: 900; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">Billboard Artist</span>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="cm-section" style="padding: 0 20px 32px;">
             <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="cm-card" style="padding: 16px; text-align: center;">
                    <span style="display:block; font-size: 9px; font-weight:800; color:var(--cm-text-muted); text-transform:uppercase; margin-bottom:4px;">Rankings</span>
                    <span style="font-size: 18px; font-weight:900;"><?php echo count($chart_rankings); ?></span>
                </div>
                <div class="cm-card" style="padding: 16px; text-align: center;">
                    <span style="display:block; font-size: 9px; font-weight:800; color:var(--cm-text-muted); text-transform:uppercase; margin-bottom:4px;">Popular Works</span>
                    <span style="font-size: 18px; font-weight:900;"><?php echo count($popular_tracks); ?></span>
                </div>
             </div>
        </section>

        <!-- Popular Tracks -->
        <section class="cm-section" style="padding: 0 20px 40px;">
            <h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(--cm-text-muted); margin-bottom: 16px;">Top Analysis</h3>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ( $popular_tracks as $pt ) : 
                    $pt_resolved = \Charts\Core\PublicIntegration::resolve_display_name($pt);
                    $pt_url = $link('/charts/' . ($pt->item_type==='video' ? 'video' : 'track') . '/' . $pt->item_slug);
                ?>
                    <a href="<?php echo esc_url($pt_url); ?>" class="cm-row" style="padding: 12px 16px; background:var(--cm-surface); border-radius:12px; border:1px solid var(--cm-divider); text-decoration:none;">
                        <img src="<?php echo esc_url(\Charts\Core\PublicIntegration::resolve_artwork($pt, $pt->item_type)); ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                        <div class="cm-row-info">
                            <span class="cm-row-title" style="font-size:14px;"><?php echo esc_html($pt_resolved['title']); ?></span>
                            <span class="cm-row-sub" style="font-size:10px;">Explorer Insights &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
