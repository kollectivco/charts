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
    
    <header class="kc-header" style="padding: 32px 20px 20px;">
        <span class="kc-meta" style="font-size: 11px; color: var(--kc-text-muted); opacity: 0.8; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800;"><?php echo strtoupper(date('l, F d')); ?></span>
        <h1 style="font-size: 44px; margin-top: 6px; font-weight: 950; letter-spacing: -0.05em; line-height: 0.9;">Charts</h1>
    </header>

    <main class="kc-content">

        <!-- Billboard / Hero Slider -->
        <?php if ( ! empty($slides) ) : ?>
        <section class="kc-section" style="padding-top:0;">
            <div class="kc-slider">
                <div class="kc-slider-track">
                    <?php foreach ( $slides as $s ) : ?>
                        <a href="<?php echo esc_url($link($s['btn1_link'])); ?>" class="kc-hero-slide" style="flex: 0 0 90%; aspect-ratio: 16/10;">
                            <img src="<?php echo esc_url($s['image_url']); ?>" class="kc-hero-img">
                            <div class="kc-hero-overlay"></div>
                            <div class="kc-hero-info" style="padding: 32px;">
                                <span class="kc-meta" style="color:#fff; opacity:0.8; font-size: 10px; font-weight: 900; letter-spacing: 0.15em;"><?php echo esc_html($s['badge'] ?: 'INTELLIGENCE'); ?></span>
                                <h3 style="color:#fff; font-size: 32px; margin-top:8px; line-height: 1;"><?php echo esc_html($s['title']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Top Artists Section (Requested Fix: 3 Cards) -->
        <?php 
            // Try to find a Top Artists definition (Check both type and title)
            $artist_defs = array_filter($definitions, function($d) { 
                return ($d->chart_type === 'top-artists') || (strpos(strtolower($d->title), 'artist') !== false); 
            });
            $active_artists_def = !empty($artist_defs) ? reset($artist_defs) : null;
            
            if ( $active_artists_def ) :
                // Fetch top 3 artists specifically
                $top_artists = \Charts\Core\PublicIntegration::get_preview_entries($active_artists_def, 3);
        ?>
        <section class="kc-section" style="padding-top: 40px;">
            <div class="kc-section-head">
                <h2 class="kc-section-title" style="font-size: 24px;">Top Performing</h2>
                <span class="kc-meta kc-accent-color" style="font-weight: 900; letter-spacing: 0.1em; color: var(--kc-accent);">Artist Feed</span>
            </div>

            <div class="kc-charts-carousel" style="padding-bottom: 20px;">
                <?php foreach ( $top_artists as $ta ) : 
                    $resolved_ta = $resolve_name($ta, $active_artists_def);
                ?>
                    <a href="<?php echo esc_url($link('/charts/artist/' . $ta->item_slug)); ?>" class="kc-chart-card" style="flex: 0 0 30%; min-height: 120px; aspect-ratio: 1/1; border-radius: 20px; border: none; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
                        <img src="<?php echo esc_url($resolve_art($ta)); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px;">
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 70%); border-radius: 20px;"></div>
                        <div style="position: absolute; bottom: 12px; left: 12px; right: 12px;">
                             <h4 style="color:#fff; font-size: 13px; font-weight: 950; margin: 0; line-height: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><?php echo esc_html($resolved_ta['title']); ?></h4>
                        </div>
                        <div style="position: absolute; top: 10px; left: 10px; background: var(--kc-accent); color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 950; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                            <?php echo $ta->rank_position; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- All Charts Vertical Feed -->
        <section class="kc-section" style="padding-top: 40px;">
            <div class="kc-section-head">
                <h2 class="kc-section-title" style="font-size: 24px;">All Charts</h2>
                <span class="kc-meta" style="color: #fe025b; font-weight: 900;">Global Feed</span>
            </div>

            <div style="padding: 0 20px; display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ( $definitions as $def ) : 
                    $entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 3);
                    $chart_image = \Charts\Core\PublicIntegration::resolve_chart_image($def, $entries);
                    $chart_url   = $link('/charts/' . $def->slug . '/');
                ?>
                    <div class="kc-chart-card" style="flex: none; min-height: auto; width: 100%; background: #fff; border: 1px solid var(--kc-divider); border-radius: 24px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                        <a href="<?php echo esc_url($chart_url); ?>" class="kc-card-hero" style="height: 180px; position: relative; overflow: hidden;">
                            <img src="<?php echo esc_url($chart_image); ?>" class="kc-card-img" style="transform: scale(1.1); filter: contrast(1.1);">
                            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent 80%);"></div>
                            <div style="position: absolute; bottom: 20px; left: 24px; right: 24px;">
                                <span class="kc-meta" style="color: #fff; font-size: 9px; letter-spacing: 0.15em; font-weight: 900; opacity: 0.8;">KONTENTAINMENT</span>
                                <h3 class="kc-card-title" style="font-size: 28px; color: #fff; line-height: 1; margin-top: 4px;"><?php echo esc_html($def->title); ?></h3>
                            </div>
                        </a>

                        <div class="kc-card-content" style="padding: 8px 0;">
                            <?php foreach ( $entries as $index => $e ) : 
                                $resolved = $resolve_name($e, $def);
                            ?>
                                <a href="<?php echo esc_url($chart_url); ?>" class="kc-row" style="padding: 16px 24px; border-bottom: <?php echo $index === 2 ? 'none' : '1px solid var(--kc-divider)'; ?>; gap: 20px;">
                                    <span class="kc-rank" style="font-size: 18px; color: #fe025b; width: 24px;"><?php echo $e->rank_position; ?></span>
                                    <div class="kc-row-info">
                                        <span class="kc-row-title" style="font-size: 15px; font-weight: 900; color: #000;"><?php echo esc_html($resolved['title']); ?></span>
                                        <span class="kc-row-sub" style="font-size: 12px; font-weight: 700; opacity: 0.6;"><?php echo esc_html($resolved['subtitle']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <a href="<?php echo esc_url($chart_url); ?>" class="kc-view-more" style="background: var(--kc-surface-alt); padding: 18px; font-size: 11px; letter-spacing: 0.08em;">
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
