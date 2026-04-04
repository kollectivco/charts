<?php
// slider-stacked.php
?>
<div class="kc-motion-carousel">
    <?php foreach ( $slides as $index => $slide ) : ?>
        <div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
            <div class="kc-st-card">
                <div class="kc-st-visual">
                    <img src="<?php echo esc_url($slide['image']); ?>" alt="">
                </div>
                <div class="kc-st-info" style="text-align: <?php echo esc_attr($settings['slider_align'] ?? 'center'); ?>;">
                    <?php if (!empty($settings['slider_show_label'])): ?>
                    <h3 class="kc-st-title"><?php echo esc_html($slide['title']); ?></h3>
                    <?php endif; ?>
                    
                    <h2 class="kc-st-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
                    
                    <?php if (!empty($settings['slider_show_meta'])): ?>
                    <div class="kc-st-artist">by <?php echo esc_html($slide['leader_artist']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['slider_show_cta'])): ?>
                    <a href="<?php echo esc_url($slide['url']); ?>" class="kc-st-link"><?php echo esc_html($settings['slider_cta_text'] ?? 'Explore Chart'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/slider-controls.php'; ?>
