<?php
// slider-minimal.php
?>
<div class="kc-motion-carousel">
    <?php foreach ( $slides as $index => $slide ) : ?>
        <div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
            <div class="kc-min-wrapper">
                <div class="kc-min-card">
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="">
                    <a href="<?php echo esc_url($slide['url']); ?>" class="kc-min-overlay" style="opacity: <?php echo esc_attr($settings['slider_overlay'] ?? 0.5); ?>;"></a>
                </div>
                <div class="kc-min-text" style="text-align: <?php echo esc_attr($settings['slider_align'] ?? 'center'); ?>;">
                    <h2 class="kc-min-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
                    
                    <?php if (!empty($settings['slider_show_meta'])): ?>
                    <div class="kc-min-artist"><?php echo esc_html($slide['leader_artist']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['slider_show_cta'])): ?>
                    <a href="<?php echo esc_url($slide['url']); ?>" class="kc-min-btn"><?php echo esc_html($settings['slider_cta_text'] ?? 'VIEW'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/slider-controls.php'; ?>
