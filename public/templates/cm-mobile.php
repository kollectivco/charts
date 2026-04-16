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
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo esc_html($site_title); ?> Intelligence CM</title>
    
    <!-- Heavy-Duty Styling for WebView -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/cm.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    
    <style>
        :root {
            --k-primary: <?php echo \Charts\Core\Settings::get('design.primary_color', '#6366f1'); ?>;
            --k-accent: <?php echo \Charts\Core\Settings::get('design.accent_color', '#fe025b'); ?>;
        }
        .cm-rank { color: var(--k-primary) !important; }
        .cm-row-title { font-family: 'Inter', sans-serif; }
    </style>

    <?php 
    // We only call a subset of wp_head to avoid theme assets but keep plugin metadata and essential scripts
    // But since the requirement is "No site header", we'll be surgical.
    wp_print_scripts(); 
    ?>
</head>
<body>

<div class="cm-app-shell">
    
    <!-- Native-like Header -->
    <header class="cm-header" <?php echo $current_def ? 'style="display:flex; align-items:center; gap:16px; padding: 12px 20px; border-bottom:1px solid var(--cm-divider); position:sticky; top:0; z-index:100; backdrop-filter:blur(20px); background:rgba(11,12,16,0.8);"' : ''; ?>>
        <?php if ( $current_def ) : ?>
            <a href="<?php echo home_url('/cm'); ?>" style="color:var(--cm-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
            <h1 style="font-size: 18px;"><?php echo esc_html($current_def->title); ?></h1>
        <?php else : ?>
            <span class="cm-meta"><?php echo date('F d, Y'); ?></span>
            <h1>Intelligence</h1>
        <?php endif; ?>
    </header>

    <main class="cm-content">

        <?php if ( $current_def ) : 
            $entries = \Charts\Core\PublicIntegration::get_preview_entries($current_def, 100);
        ?>
            <!-- Single Chart View (Mobile Optimized) -->
            <div class="cm-charts-feed" style="margin-top: 12px;">
                <div class="cm-chart-card" style="border:none; background:transparent;">
                    <div class="cm-card-content" style="padding:0;">
                        <?php foreach ( $entries as $e ) : 
                            $resolved = $resolve_name($e, $current_def);
                        ?>
                            <div class="cm-row">
                                <span class="cm-rank"><?php echo $e->rank_position; ?></span>
                                <img src="<?php echo esc_url($resolve_art($e)); ?>" class="cm-row-img" style="width:48px; height:48px;">
                                <div class="cm-row-info">
                                    <span class="cm-row-title" style="font-size:15px;"><?php echo esc_html($resolved['title']); ?></span>
                                    <span class="cm-row-sub" style="font-size:12px;"><?php echo esc_html($resolved['subtitle']); ?></span>
                                </div>
                                <div style="text-align:right;">
                                    <?php if ( $e->movement_direction === 'up' ) : ?>
                                        <span style="color:#2ecc71; font-size:10px; font-weight:900;">▲ <?php echo $e->movement_value; ?></span>
                                    <?php elseif ( $e->movement_direction === 'down' ) : ?>
                                        <span style="color:#e74c3c; font-size:10px; font-weight:900;">▼ <?php echo $e->movement_value; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php else : ?>
            <!-- Main Intelligence Feed -->
            
            <!-- 1. Featured Intelligence (Slider) -->
            <?php if ( ! empty($slides) ) : ?>
        <section class="cm-section">
            <div class="cm-hero">
                <div class="cm-hero-track">
                    <?php foreach ( $slides as $s ) : ?>
                        <a href="<?php echo esc_url($s['btn1_link']); ?>" class="cm-hero-slide">
                            <img src="<?php echo esc_url($s['image_url']); ?>" class="cm-hero-img">
                            <div class="cm-hero-overlay"></div>
                            <div class="cm-hero-info">
                                <span class="cm-meta" style="color:#fff; opacity:0.7;"><?php echo esc_html($s['badge'] ?: 'Featured Chart'); ?></span>
                                <h3 style="color:#fff; font-size: 24px;"><?php echo esc_html($s['title']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 2. The Charts Feed -->
        <section class="cm-section">
            <div class="cm-section-head">
                <h2 class="cm-section-title">Trending Now</h2>
                <span class="cm-meta" style="color: var(--k-primary);">See All</span>
            </div>

            <div class="cm-charts-feed">
                <?php foreach ( $definitions as $def ) : 
                    $entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 3);
                    $chart_image = \Charts\Core\PublicIntegration::resolve_chart_image($def, $entries);
                    $accent = !empty($def->accent_color) ? $def->accent_color : 'var(--k-primary)';
                ?>
                    <div class="cm-chart-card">
                        <a href="<?php echo home_url('/cm/' . $def->slug . '/'); ?>" class="cm-card-hero">
                            <img src="<?php echo esc_url($chart_image); ?>" class="cm-card-img">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);"></div>
                            <h3 class="cm-card-title"><?php echo esc_html($def->title); ?></h3>
                        </a>

                        <div class="cm-card-content">
                            <?php foreach ( $entries as $e ) : 
                                $resolved = $resolve_name($e, $def);
                            ?>
                                <a href="<?php echo home_url('/charts/track/' . $e->item_slug . '/'); ?>" class="cm-row">
                                    <span class="cm-rank" style="color: <?php echo $accent; ?>;"><?php echo $e->rank_position; ?></span>
                                    <img src="<?php echo esc_url($resolve_art($e)); ?>" class="cm-row-img">
                                    <div class="cm-row-info">
                                        <span class="cm-row-title"><?php echo esc_html($resolved['title']); ?></span>
                                        <span class="cm-row-sub"><?php echo esc_html($resolved['subtitle']); ?></span>
                                    </div>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity: 0.3;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <a href="<?php echo home_url('/cm/' . $def->slug . '/'); ?>" style="padding: 16px; text-align: center; font-size: 11px; font-weight: 800; color: var(--cm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-top: 1px solid var(--cm-divider);">
                            View Full Analysis &rarr;
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

</div>

</body>
</html>
