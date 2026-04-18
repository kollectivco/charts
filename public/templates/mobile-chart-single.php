<?php
/**
 * Mobile Experience: Single Chart View (App/WebView Mode)
 */

$slug = get_query_var('charts_definition_slug');
$manager = new \Charts\Admin\SourceManager();
$current_def = $manager->get_definition_by_slug($slug);

if ( ! $current_def ) {
    wp_redirect(add_query_arg('mobile_view', '1', home_url('/charts')));
    exit;
}

$entries = \Charts\Core\PublicIntegration::get_preview_entries($current_def, 100);

// Helpers
$link = function($path) {
    $url = home_url($path);
    return add_query_arg('mobile_view', '1', $url);
};

$resolve_name = function($e, $def) {
    return \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
};

$resolve_art = function($e) {
    return $e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
};

$site_title = get_bloginfo('name');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo esc_html($current_def->title); ?> — Mobile Mode</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <?php wp_head(); ?>

    <style>
        :root { --cm-primary-override: <?php echo $current_def->accent_color ?: \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>; }
        .cm-rank, .cm-accent-color { color: var(--cm-primary-override) !important; }
    </style>
</head>
<body <?php body_class('kc-body-mobile kc-view-chart-single'); ?>>

<div class="cm-app-shell">
    
    <header class="cm-header" style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(--cm-divider); position:sticky; top:0; z-index:100; background:var(--cm-surface);">
        <a href="<?php echo esc_url($link('/charts')); ?>" style="color:var(--cm-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
        <h1 style="font-size: 18px; margin:0; line-height:1;"><?php echo esc_html($current_def->title); ?></h1>
    </header>

    <main class="cm-content">
        <div class="cm-charts-list" style="padding: 10px 0;">
            <?php foreach ( $entries as $e ) : 
                $resolved = $resolve_name($e, $current_def);
            ?>
                <div class="cm-row-item kc-rank-row">
                    <div class="cm-row" style="padding: 14px 20px;">
                        <span class="cm-rank"><?php echo $e->rank_position; ?></span>
                        <img src="<?php echo esc_url($resolve_art($e)); ?>" class="cm-row-img" style="width:48px; height:48px;">
                        <div class="cm-row-info">
                            <span class="cm-row-title"><?php echo esc_html($resolved['title']); ?></span>
                            <span class="cm-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                        </div>
                        
                        <div style="text-align:right; margin-right: 12px;">
                            <?php if ( $e->rank_position < $e->previous_rank ) : ?>
                                <span style="color:#2ecc71; font-size:10px; font-weight:900;">▲ <?php echo ($e->previous_rank - $e->rank_position); ?></span>
                            <?php elseif ( $e->rank_position > $e->previous_rank && $e->previous_rank > 0 ) : ?>
                                <span style="color:#e74c3c; font-size:10px; font-weight:900;">▼ <?php echo ($e->rank_position - $e->previous_rank); ?></span>
                            <?php elseif ( ! empty($e->previous_rank) && $e->previous_rank == 0 ) : ?>
                                <span style="color:var(--cm-primary); font-size:9px; font-weight:900;">NEW</span>
                            <?php endif; ?>
                        </div>

                        <div class="cm-chevron">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <div class="cm-details-wrapper">
                        <div class="cm-details-inner">
                            <div class="cm-detail-item">
                                <label>Peak</label>
                                <span>#<?php echo $e->peak_rank ?: $e->rank_position; ?></span>
                            </div>
                            <div class="cm-detail-item">
                                <label>Last Wk</label>
                                <span>#<?php echo $e->previous_rank ?: '—'; ?></span>
                            </div>
                            <div class="cm-detail-item">
                                <label>Weeks On</label>
                                <span><?php echo $e->weeks_on_chart ?: 1; ?> wks</span>
                            </div>
                            <div class="cm-detail-cta" style="grid-column: span 2; padding-top: 12px; border-top: 1px solid var(--cm-divider); margin-top: 4px;">
                                <?php 
                                    $entity_type = $e->item_type ?: $current_def->item_type ?: 'track';
                                    $details_url = $link('/charts/' . $entity_type . '/' . $e->item_slug . '/');
                                ?>
                                <a href="<?php echo esc_url($details_url); ?>" style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 800; color: var(--cm-primary); text-transform: uppercase; letter-spacing: 0.05em;">
                                    <span>Full Analysis Breakdown</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
