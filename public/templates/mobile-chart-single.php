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
        :root { --kc-primary-override: <?php echo $current_def->accent_color ?: \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>; }
        .kc-rank, .kc-accent-color { color: var(--kc-primary-override) !important; }
    </style>
</head>
<body <?php body_class('kc-mode-mobile kc-view-chart-single'); ?>>

<div class="kc-app-shell">
    
    <header class="kc-header" style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(--kc-divider); position:sticky; top:0; z-index:100; background:var(--kc-surface);">
        <a href="<?php echo esc_url($link('/charts')); ?>" style="color:var(--kc-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
        <h1 style="font-size: 18px; margin:0; line-height:1;"><?php echo esc_html($current_def->title); ?></h1>
    </header>

    <main class="kc-content">
        
        <!-- #1 FEATURED ITEM (Hero) -->
        <?php if ( ! empty($entries[0]) ) : $top = $entries[0]; 
            $resolved_top = $resolve_name($top, $current_def);
            $top_image = $resolve_art($top);
            $accent = $current_def->accent_color ?: 'var(--kc-primary)';
        ?>
        <section class="kc-section kc-hero" style="padding: 24px 20px; position:relative; overflow:hidden; min-height: 280px; display:flex; align-items:center;">
            <div style="position:absolute; inset:0; background: url('<?php echo esc_url($top_image); ?>'); background-size:cover; background-position:center; filter: blur(40px) saturate(1.5); opacity:0.1; transform: scale(1.1);"></div>
            <div style="position:absolute; inset:0; background: linear-gradient(to bottom, transparent, var(--kc-bg));"></div>

            <div style="position:relative; z-index:2; display:flex; flex-direction:column; width:100%; gap:20px;">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div style="position:relative; flex-shrink:0;">
                         <img src="<?php echo esc_url($top_image); ?>" style="width:120px; height:120px; border-radius:12px; object-fit:cover; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                         <div style="position:absolute; top:-10px; left:-10px; width:44px; height:44px; background:<?php echo $accent; ?>; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:900; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border:3px solid var(--kc-surface);">1</div>
                    </div>
                    
                    <div style="flex:1;">
                        <span style="display:inline-block; background:<?php echo $accent; ?>15; color:<?php echo $accent; ?>; font-size:10px; font-weight:900; padding:4px 10px; border-radius:6px; text-transform:uppercase; margin-bottom:8px;">Featured Number One</span>
                        <h2 style="font-size:24px; font-weight:950; margin:0; line-height:1.1; color:var(--kc-text);"><?php echo esc_html($resolved_top['title']); ?></h2>
                        <span style="display:block; font-size:14px; color:var(--kc-text-muted); margin-top:4px; font-weight:700;"><?php echo esc_html($resolved_top['subtitle']); ?></span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; background:var(--kc-surface); padding:12px; border-radius:12px; border:1px solid var(--kc-divider);">
                    <div style="text-align:center;">
                        <span style="display:block; font-size:9px; color:var(--kc-text-muted); text-transform:uppercase; font-weight:800; margin-bottom:2px;">Peak</span>
                        <span style="font-size:14px; font-weight:900; color:var(--kc-text);">#<?php echo $top->peak_rank ?: 1; ?></span>
                    </div>
                    <div style="text-align:center; border-left:1px solid var(--kc-divider); border-right:1px solid var(--kc-divider);">
                        <span style="display:block; font-size:9px; color:var(--kc-text-muted); text-transform:uppercase; font-weight:800; margin-bottom:2px;">Last Wk</span>
                        <span style="font-size:14px; font-weight:900; color:var(--kc-text);">#<?php echo $top->previous_rank ?: '—'; ?></span>
                    </div>
                    <div style="text-align:center;">
                        <span style="display:block; font-size:9px; color:var(--kc-text-muted); text-transform:uppercase; font-weight:800; margin-bottom:2px;">Weeks On</span>
                        <span style="font-size:14px; font-weight:900; color:var(--kc-text);"><?php echo $top->weeks_on_chart ?: 1; ?></span>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="kc-charts-list" style="padding: 10px 0;">
            <?php foreach ( $entries as $e ) : 
                $resolved = $resolve_name($e, $current_def);
            ?>
                <div class="kc-row-item kc-rank-row">
                    <div class="kc-row" style="padding: 14px 20px;">
                        <span class="kc-rank"><?php echo $e->rank_position; ?></span>
                        <img src="<?php echo esc_url($resolve_art($e)); ?>" class="kc-row-img" style="width:48px; height:48px;">
                        <div class="kc-row-info">
                            <span class="kc-row-title"><?php echo esc_html($resolved['title']); ?></span>
                            <span class="kc-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                        </div>
                        
                        <div style="text-align:right; margin-right: 12px;">
                            <?php if ( $e->rank_position < $e->previous_rank ) : ?>
                                <span style="color:#2ecc71; font-size:10px; font-weight:900;">▲ <?php echo ($e->previous_rank - $e->rank_position); ?></span>
                            <?php elseif ( $e->rank_position > $e->previous_rank && $e->previous_rank > 0 ) : ?>
                                <span style="color:#e74c3c; font-size:10px; font-weight:900;">▼ <?php echo ($e->rank_position - $e->previous_rank); ?></span>
                            <?php elseif ( ! empty($e->previous_rank) && $e->previous_rank == 0 ) : ?>
                                <span style="color:var(--kc-primary); font-size:9px; font-weight:900;">NEW</span>
                            <?php endif; ?>
                        </div>

                        <div class="kc-chevron">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <div class="kc-details-wrapper">
                        <div class="kc-details-inner">
                            <div class="kc-detail-item">
                                <label>Peak</label>
                                <span>#<?php echo $e->peak_rank ?: $e->rank_position; ?></span>
                            </div>
                            <div class="kc-detail-item">
                                <label>Last Wk</label>
                                <span>#<?php echo $e->previous_rank ?: '—'; ?></span>
                            </div>
                            <div class="kc-detail-item">
                                <label>Weeks On</label>
                                <span><?php echo $e->weeks_on_chart ?: 1; ?> wks</span>
                            </div>
                            <div class="kc-detail-cta" style="grid-column: span 2; padding-top: 12px; border-top: 1px solid var(--kc-divider); margin-top: 4px;">
                                <?php 
                                    $entity_type = $e->item_type ?: $current_def->item_type ?: 'track';
                                    $details_url = $link('/charts/' . $entity_type . '/' . $e->item_slug . '/');
                                ?>
                                <a href="<?php echo esc_url($details_url); ?>" style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 800; color: var(--kc-primary); text-transform: uppercase; letter-spacing: 0.05em;">
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
