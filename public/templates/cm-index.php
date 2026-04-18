<?php
/**
 * CM Mobile Experience: Main Index
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
<!-- CM_RENDERER: public/templates/cm-index.php -->
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo esc_html($site_title); ?> Charts CM</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/cm.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    
    <style>
        :root { --cm-primary-override: <?php echo \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>; }
        .cm-rank, .cm-accent-color { color: var(--cm-primary-override) !important; }
    </style>
</head>
<body <?php body_class('cm-body-mobile cm-view-index'); ?>>

<div class="cm-app-shell">
    
    <header class="cm-header">
        <span class="cm-meta"><?php echo date('l, F d'); ?></span>
        <h1 style="margin-top:4px;">Charts</h1>
    </header>

    <main class="cm-content">

        <!-- Billboard Slider -->
        <?php if ( ! empty($slides) ) : ?>
        <section class="cm-section" style="padding-top:10px;">
            <div class="cm-slider">
                <div class="cm-slider-track">
                    <?php foreach ( $slides as $s ) : ?>
                        <a href="<?php echo esc_url($link($s['btn1_link'])); ?>" class="cm-hero-slide">
                            <img src="<?php echo esc_url($s['image_url']); ?>" class="cm-hero-img">
                            <div class="cm-hero-overlay"></div>
                            <div class="cm-hero-info">
                                <span class="cm-meta" style="color:#fff; opacity:0.8;"><?php echo esc_html($s['badge'] ?: 'Intelligence'); ?></span>
                                <h3 style="color:#fff; font-size: 24px; margin-top:4px;"><?php echo esc_html($s['title']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Active Charts Carousel -->
        <section class="cm-section">
            <div class="cm-section-head">
                <h2 class="cm-section-title">All Charts</h2>
                <span class="cm-meta cm-accent-color">Global Feed</span>
            </div>

            <div class="cm-charts-carousel">
                <?php foreach ( $definitions as $def ) : 
                    $entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 3);
                    $chart_image = \Charts\Core\PublicIntegration::resolve_chart_image($def, $entries);
                    $chart_url   = $link('/charts/' . $def->slug . '/');
                ?>
                    <div class="cm-chart-card">
                        <a href="<?php echo esc_url($chart_url); ?>" class="cm-card-hero">
                            <img src="<?php echo esc_url($chart_image); ?>" class="cm-card-img">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent);"></div>
                            <h3 class="cm-card-title"><?php echo esc_html($def->title); ?></h3>
                        </a>

                        <div class="cm-card-content">
                            <?php foreach ( $entries as $index => $e ) : 
                                $resolved = $resolve_name($e, $def);
                            ?>
                                <a href="<?php echo esc_url($chart_url); ?>" class="cm-row" style="border-bottom: <?php echo $index === 2 ? 'none' : '1px solid var(--cm-divider)'; ?>">
                                    <span class="cm-rank"><?php echo $e->rank_position; ?></span>
                                    <div class="cm-row-info">
                                        <span class="cm-row-title"><?php echo esc_html($resolved['title']); ?></span>
                                        <span class="cm-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <a href="<?php echo esc_url($chart_url); ?>" class="cm-view-more">
                            Full Analysis &rarr;
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
