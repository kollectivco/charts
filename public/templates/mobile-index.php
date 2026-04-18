<?php
/**
 * Mobile Experience: Main Index (App/WebView Mode)
 */

// 1. DATA LAYER
$manager = new \Charts\Admin\SourceManager();
$definitions = \Charts\Core\PublicIntegration::get_eligible_definitions( 15 );
$slides      = \Charts\Core\HomepageSlider::get_slides_data( 5 );

// Resolution Helpers
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
    <title><?php echo esc_html($site_title); ?> — Mobile Mode</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <?php wp_head(); ?>

    <style>
        :root { --kc-primary-override: <?php echo \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>; }
        .kc-rank, .kc-accent-color { color: var(--kc-primary-override) !important; }
    </style>
</head>
<body <?php body_class('kc-mode-mobile kc-view-index'); ?>>

<div class="kc-app-shell">
    
    <header class="kc-header">
        <span class="kc-meta"><?php echo date('l, F d'); ?></span>
        <h1 style="margin-top:4px;">Charts</h1>
    </header>

    <main class="kc-content">

        <!-- Billboard Slider -->
        <?php if ( ! empty($slides) ) : ?>
        <section class="kc-section" style="padding-top:10px;">
            <div class="kc-slider">
                <div class="kc-slider-track">
                    <?php foreach ( $slides as $s ) : ?>
                        <a href="<?php echo esc_url($link($s['btn1_link'])); ?>" class="kc-hero-slide">
                            <img src="<?php echo esc_url($s['image_url']); ?>" class="kc-hero-img">
                            <div class="kc-hero-overlay"></div>
                            <div class="kc-hero-info">
                                <span class="kc-meta" style="color:#fff; opacity:0.8;"><?php echo esc_html($s['badge'] ?: 'Intelligence'); ?></span>
                                <h3 style="color:#fff; font-size: 24px; margin-top:4px;"><?php echo esc_html($s['title']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Active Charts Carousel -->
        <section class="kc-section">
            <div class="kc-section-head">
                <h2 class="kc-section-title">All Charts</h2>
                <span class="kc-meta kc-accent-color">Global Feed</span>
            </div>

            <div class="kc-charts-carousel">
                <?php foreach ( $definitions as $def ) : 
                    $entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 3);
                    $chart_image = \Charts\Core\PublicIntegration::resolve_chart_image($def, $entries);
                    $chart_url   = $link('/charts/' . $def->slug . '/');
                ?>
                    <div class="kc-chart-card">
                        <a href="<?php echo esc_url($chart_url); ?>" class="kc-card-hero">
                            <img src="<?php echo esc_url($chart_image); ?>" class="kc-card-img">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent);"></div>
                            <h3 class="kc-card-title"><?php echo esc_html($def->title); ?></h3>
                        </a>

                        <div class="kc-card-content">
                            <?php foreach ( $entries as $index => $e ) : 
                                $resolved = $resolve_name($e, $def);
                            ?>
                                <a href="<?php echo esc_url($chart_url); ?>" class="kc-row" style="border-bottom: <?php echo $index === 2 ? 'none' : '1px solid var(--kc-divider)'; ?>">
                                    <span class="kc-rank"><?php echo $e->rank_position; ?></span>
                                    <div class="kc-row-info">
                                        <span class="kc-row-title"><?php echo esc_html($resolved['title']); ?></span>
                                        <span class="kc-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <a href="<?php echo esc_url($chart_url); ?>" class="kc-view-more">
                            Full Analysis &rarr;
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
