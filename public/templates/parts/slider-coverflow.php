<?php
// slider-coverflow.php
?>
<div class="kc-motion-carousel">
    <?php foreach ( $slides as $index => $slide ) : ?>
        <div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
            <div class="kc-cf-card">
                <img class="kc-cf-bg" src="<?php echo esc_url($slide['image']); ?>" alt="">
                <div class="kc-cf-overlay" style="opacity: <?php echo esc_attr($settings['slider_overlay'] ?? 0.5); ?>;"></div>
                <div class="kc-cf-content">
                    <?php if (!empty($settings['slider_show_label'])): ?>
                    <span class="kc-badge"><?php echo esc_html($slide['platform']); ?></span>
                    <?php endif; ?>
                    
                    <h2 class="kc-cf-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
                    
                    <?php if (!empty($settings['slider_show_meta'])): ?>
                    <div class="kc-cf-artist">by <?php echo esc_html($slide['leader_artist']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['slider_show_cta'])): ?>
                    <a href="<?php echo esc_url($slide['url']); ?>" class="kc-cf-btn"><?php echo esc_html($settings['slider_cta_text'] ?? 'VIEW'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/slider-controls.php'; ?>
