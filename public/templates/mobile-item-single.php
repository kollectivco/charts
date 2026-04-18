<?php
/**
 * Mobile Mode: Single Track/Video View
 */

global $wpdb;

$type = get_query_var( 'charts_item_type' ) ?: 'track';
$slug = get_query_var( 'charts_item_slug' );

$item = \Charts\Core\EntityManager::get_entity_by_slug( $type, $slug );

if ( ! $item ) {
    wp_redirect(add_query_arg('mobile_view', '1', home_url('/charts')));
    exit;
}

if ( $type === 'video' ) {
    $item->cover_image = $item->thumbnail;
}

// Helpers
$link = function($path) {
    $url = home_url($path);
    return add_query_arg('mobile_view', '1', $url);
};

// Fetch Appearances
$entries_table = $wpdb->prefix . 'charts_entries';
$sources_table = $wpdb->prefix . 'charts_sources';
$periods_table = $wpdb->prefix . 'charts_periods';

$title_escaped = '%' . $wpdb->esc_like( $item->title ) . '%';
$appearances = $wpdb->get_results( $wpdb->prepare( "
	SELECT e.*, s.chart_type, s.country_code, s.source_name, p.period_start
	FROM $entries_table e
	INNER JOIN (
		SELECT MAX(e2.id) as max_id 
		FROM $entries_table e2
		WHERE (e2.item_id = %d AND e2.item_type = %s)
		   OR (e2.track_name LIKE %s AND e2.item_type = %s)
		GROUP BY e2.source_id
	) latest ON latest.max_id = e.id
	JOIN $sources_table s ON s.id = e.source_id
	JOIN $periods_table p ON p.id = e.period_id
	ORDER BY p.period_start DESC
", $item->id, $type, $title_escaped, $type ) );

foreach($appearances as $app) {
	if ( strpos($app->chart_type, 'cid-') === 0 ) {
		$def_id = (int) str_replace('cid-', '', $app->chart_type);
		$def = $wpdb->get_row($wpdb->prepare("SELECT title, accent_color FROM {$wpdb->prefix}charts_definitions WHERE id = %d", $def_id));
	} else {
		$def = $wpdb->get_row($wpdb->prepare("SELECT title, accent_color FROM {$wpdb->prefix}charts_definitions WHERE chart_type = %s AND country_code = %s", $app->chart_type, $app->country_code));
	}
	if ($def) {
		$app->definition_title = $def->title;
		$app->accent_color = $def->accent_color;
	}
}

$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item->primary_artist_id ) );
$site_title = get_bloginfo('name');
$resolved = \Charts\Core\PublicIntegration::resolve_display_name($item); 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo esc_html($resolved['title']); ?></title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <?php wp_head(); ?>
</head>
<body <?php body_class('kc-mode-mobile kc-view-item-single'); ?>>

<div class="kc-app-shell">
    
    <header class="kc-header" style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(---kc-divider); position:sticky; top:0; z-index:100; background:var(---kc-surface);">
        <a href="javascript:history.back()" style="color:var(---kc-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
        <h1 style="font-size: 16px; margin:0; line-height:1; font-weight:800;"><?php echo strtoupper($type); ?> Analysis</h1>
    </header>

    <main class="kc-content">
        <!-- Hero -->
        <section class="kc-section" style="padding: 24px 20px;">
            <div style="display: flex; gap: 20px; align-items: center;">
                <img src="<?php echo esc_url($item->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover; box-shadow: var(---kc-shadow-sm);">
                <div style="flex: 1;">
                    <h2 style="font-size: 24px; font-weight: 900; margin: 0; line-height: 1.1;"><?php echo esc_html($resolved['title']); ?></h2>
                    <?php if ( $artist ) : ?>
                        <a href="<?php echo esc_url($link('/charts/artist/' . $artist->slug)); ?>" style="display: block; font-size: 14px; font-weight: 700; color: var(---kc-primary); margin-top: 8px; text-decoration: none;"><?php echo esc_html($artist->display_name); ?> &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Appearances -->
        <section class="kc-section" style="padding: 0 20px 40px;">
            <h3 style="font-size: 11px; font-weight: 900; text-transform: uppercase; color: var(---kc-text-muted); margin-bottom: 16px;">Chart History</h3>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if ( empty($appearances) ) : ?>
                    <div class="kc-card" style="padding: 20px; text-align: center; color: var(---kc-text-muted); font-size: 13px;">No chart history recorded yet.</div>
                <?php else : ?>
                    <?php foreach ( $appearances as $app ) : ?>
                        <div class="kc-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: <?php echo !empty($app->accent_color) ? $app->accent_color : 'var(---kc-primary)'; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center; color:#fff;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
                                </div>
                                <div>
                                    <h4 style="font-size: 14px; font-weight: 800; margin: 0;"><?php echo esc_html($app->definition_title ?: 'Standard Chart'); ?></h4>
                                    <span style="font-size: 10px; color: var(---kc-text-muted);"><?php echo date('M j, Y', strtotime($app->period_start)); ?></span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 20px; font-weight: 950; color: var(---kc-text);">#<?php echo $app->rank_position; ?></div>
                                <?php if ( ! empty($app->peak_rank) ) : ?>
                                    <div style="font-size: 9px; font-weight: 800; color: var(---kc-text-muted);">PEAK #<?php echo $app->peak_rank; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
