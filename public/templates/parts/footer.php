<?php
/**
 * Standalone Charts Footer
 */
$custom_footer_enabled = get_option( 'charts_custom_footer' );
if ( ! $custom_footer_enabled ) {
	get_footer(); // Fallback if setting is off
	return;
}

$description = get_option( 'charts_footer_description' );
$copyright   = get_option( 'charts_footer_copyright' );
?>
</main><!-- /.charts-product-main -->

<footer class="charts-product-footer">
	<div class="charts-container">
		
		<!-- Widget Area -->
		<?php if ( is_active_sidebar( 'charts-footer-widgets' ) ) : ?>
		<div class="footer-widgets">
			<?php dynamic_sidebar( 'charts-footer-widgets' ); ?>
		</div>
		<?php endif; ?>

		<div class="footer-bottom">
			<div class="footer-info">
				<?php if ( $description ) : ?>
					<div class="footer-description"><?php echo wp_kses_post( $description ); ?></div>
				<?php endif; ?>
				
				<?php if ( $copyright ) : ?>
					<div class="footer-copyright">&copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( $copyright ); ?></div>
				<?php else : ?>
					<div class="footer-copyright">&copy; <?php echo date( 'Y' ); ?> Kontentainment. All rights reserved.</div>
				<?php endif; ?>
			</div>

			<div class="footer-powered">
				<a href="https://kollectiv.co" target="_blank" rel="noopener">Powered by Kollectiv</a>
			</div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
