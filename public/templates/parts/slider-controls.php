<?php
// slider-controls.php
if (!empty($settings['slider_arrows'])) : ?>
<div class="kc-motion-controls">
    <button class="kc-motion-prev"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
    <button class="kc-motion-next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
</div>
<?php endif; ?>

<?php if (!empty($settings['slider_pagination'])) : ?>
<div class="kc-motion-pagination">
    <?php foreach ( $slides as $index => $slide ) : ?>
        <span class="kc-motion-dot <?php echo $index === 0 ? 'is-active' : ''; ?>"></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>
