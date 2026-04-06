<div class="kc-premium-slider <?php echo $settings['source_mode'] === 'auto' ? 'kc-ps-auto' : 'kc-ps-manual'; ?> <?php echo $settings['hide_secondary_mobile'] === 'yes' ? 'ps-hide-s-m' : ''; ?>" data-config='<?php echo esc_attr(json_encode($config)); ?>'>
    <div class="kc-ps-wrapper">
        <?php foreach ( $slides as $index => $slide ) : 
            $active = $index === 0 ? 'is-active' : '';
        ?>
            <div class="kc-ps-slide <?php echo $active; ?>" data-index="<?php echo $index; ?>">
                <img src="<?php echo esc_url($slide['image_url']); ?>" class="kc-ps-bg" alt="">
                <div class="kc-ps-overlay"></div>
                <div class="kc-ps-content">
                    <?php if ( !empty($slide['badge']) ) : ?>
                        <span class="kc-ps-badge"><?php echo esc_html($slide['badge']); ?></span>
                    <?php endif; ?>
                    <h2 class="kc-ps-title"><?php echo esc_html($slide['title']); ?></h2>
                    <?php if ( !empty($slide['desc']) ) : ?>
                        <p class="kc-ps-desc"><?php echo esc_html($slide['desc']); ?></span>
                    <?php endif; ?>
                    <div class="kc-ps-actions">
                        <?php if ( !empty($slide['btn1_text']) ) : ?>
                            <a href="<?php echo esc_url($slide['btn1_link']); ?>" class="kc-ps-btn-p">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                <?php echo esc_html($slide['btn1_text']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( !empty($slide['btn2_text']) ) : ?>
                            <a href="<?php echo esc_url($slide['btn2_link']); ?>" class="kc-ps-btn-s">
                                <?php echo esc_html($slide['btn2_text']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ( $config['show_arrows'] && count($slides) > 1 ) : ?>
        <div class="kc-ps-nav kc-ps-prev" onclick="this.closest('.kc-premium-slider').ChartsPremiumSlider.prev()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </div>
        <div class="kc-ps-nav kc-ps-next" onclick="this.closest('.kc-premium-slider').ChartsPremiumSlider.next()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </div>
    <?php endif; ?>

    <?php if ( $config['show_dots'] && count($slides) > 1 ) : ?>
        <div class="kc-ps-dots">
            <?php foreach ( $slides as $index => $slide ) : ?>
                <div class="kc-ps-dot <?php echo $index === 0 ? 'is-active' : ''; ?>" data-index="<?php echo $index; ?>" onclick="this.closest('.kc-premium-slider').ChartsPremiumSlider.goTo(<?php echo $index; ?>)"></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sliders = document.querySelectorAll('.kc-premium-slider:not(.is-initialized)');
        sliders.forEach(el => {
            const config = JSON.parse(el.getAttribute('data-config') || '{}');
            el.ChartsPremiumSlider = new PremiumSliderEngine(el, config);
            el.classList.add('is-initialized');
        });
    });
</script>
