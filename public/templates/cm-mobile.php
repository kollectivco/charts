<?php
/**
 * CM Mobile Experience Template
 * Dedicated for Flutter WebView & Mobile Deep-Links
 */

// 1. DATA LAYER
$slug = get_query_var('charts_definition_slug');
$manager = new \Charts\Admin\SourceManager();

if ( ! empty($slug) ) {
    $current_def = $manager->get_definition_by_slug($slug);
    $definitions = array();
    $slides      = array();
} else {
    $current_def = null;
    $definitions = \Charts\Core\PublicIntegration::get_eligible_definitions( 15 );
    $slides      = \Charts\Core\HomepageSlider::get_slides_data( 5 );
}

// Resolution Logic
$resolve_name = function($e, $def) {
    return \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
};

$resolve_art = function($e) {
    return $e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
};

// Site Config
$site_title = get_bloginfo('name');
?>
<!DOCTYPE html>
<!-- ACTIVE_RENDERER: public/templates/cm-mobile.php -->
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo esc_html($site_title); ?> Charts CM</title>
    
    <!-- Mobile Optimized Assets -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/cm.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    
    <style>
        :root {
            --cm-primary-override: <?php echo \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>;
        }
        .cm-rank, .cm-accent-color { color: var(--cm-primary-override) !important; }
    </style>
</head>
<body <?php body_class('cm-body-mobile'); ?>>

<div class="cm-app-shell">
    
    <!-- Mobile Header -->
    <header class="cm-header" <?php echo $current_def ? 'style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(--cm-divider); position:sticky; top:0; z-index:100; background:var(--cm-surface);"' : ''; ?>>
        <?php if ( $current_def ) : ?>
            <a href="<?php echo home_url('/cm'); ?>" style="color:var(--cm-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
            <h1 style="font-size: 18px; margin:0; line-height:1;"><?php echo esc_html($current_def->title); ?></h1>
        <?php else : ?>
            <span class="cm-meta"><?php echo date('l, F d'); ?></span>
            <h1 style="margin-top:4px;">Charts</h1>
        <?php endif; ?>
    </header>

    <main class="cm-content">

        <?php if ( $current_def ) : 
            $entries = \Charts\Core\PublicIntegration::get_preview_entries($current_def, 100);
        ?>
            <!-- 1. Single Chart Page View -->
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
                                <div class="cm-detail-item">
                                    <label>Entity</label>
                                    <span><?php echo ucfirst($current_def->item_type ?: 'track'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <!-- 2. Main Discover Feed -->
            
            <!-- A. Featured Billboard Slider -->
            <?php if ( ! empty($slides) ) : ?>
            <section class="cm-section" style="padding-top:10px;">
                <div class="cm-slider">
                    <div class="cm-slider-track">
                        <?php foreach ( $slides as $s ) : ?>
                            <a href="<?php echo esc_url($s['btn1_link']); ?>" class="cm-hero-slide">
                                <img src="<?php echo esc_url($s['image_url']); ?>" class="cm-hero-img">
                                <div class="cm-hero-overlay"></div>
                                <div class="cm-hero-info">
                                    <span class="cm-meta" style="color:#fff; opacity:0.8;"><?php echo esc_html($s['badge'] ?: 'Special Feed'); ?></span>
                                    <h3 style="color:#fff; font-size: 24px; margin-top:4px;"><?php echo esc_html($s['title']); ?></h3>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- B. The Charts Horizontal Carousel -->
            <section class="cm-section">
                <div class="cm-section-head">
                    <h2 class="cm-section-title">Global Analysis</h2>
                    <span class="cm-meta cm-accent-color">Filter</span>
                </div>

                <div class="cm-charts-carousel">
                    <?php foreach ( $definitions as $def ) : 
                        $entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 3);
                        $chart_image = \Charts\Core\PublicIntegration::resolve_chart_image($def, $entries);
                    ?>
                        <div class="cm-chart-card">
                            <a href="<?php echo home_url('/cm/' . $def->slug . '/'); ?>" class="cm-card-hero">
                                <img src="<?php echo esc_url($chart_image); ?>" class="cm-card-img">
                                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent);"></div>
                                <h3 class="cm-card-title"><?php echo esc_html($def->title); ?></h3>
                            </a>

                            <div class="cm-card-content">
                                <?php foreach ( $entries as $index => $e ) : 
                                    $resolved = $resolve_name($e, $def);
                                ?>
                                    <a href="<?php echo home_url('/cm/' . $def->slug . '/'); ?>" class="cm-row" style="border-bottom: <?php echo $index === 2 ? 'none' : '1px solid var(--cm-divider)'; ?>">
                                        <span class="cm-rank"><?php echo $e->rank_position; ?></span>
                                        <div class="cm-row-info">
                                            <span class="cm-row-title"><?php echo esc_html($resolved['title']); ?></span>
                                            <span class="cm-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <a href="<?php echo home_url('/cm/' . $def->slug . '/'); ?>" class="cm-view-more">
                                View Full Analysis &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php endif; ?>

    </main>

</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
