<?php
/**
 * Kontentainment Charts — Premium Light Mode Footer
 */
use Charts\Core\Settings;

$wordmark     = Settings::get( 'labels.footer_wordmark', 'KCharts' ); 
$footer_left  = Settings::get( 'labels.footer_left' );
$footer_right = Settings::get( 'labels.footer_right' );
?>
</main> <!-- .charts-product-main -->

<footer class="charts-product-footer">
	<div class="kc-container">
		<div class="footer-inner">
			
			<div class="footer-brand">
				<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="charts-wordmark">
					<?php echo esc_html( $wordmark ); ?>
				</a>
			</div>

			<?php if ( is_active_sidebar( 'charts-footer-widgets' ) ) : ?>
				<?php dynamic_sidebar( 'charts-footer-widgets' ); ?>
			<?php else : ?>
				<div class="footer-col">
					<h5>Charts</h5>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/charts' ) ); ?>">All Charts</a></li>
						<li><a href="<?php echo esc_url( home_url( '/charts/tracks' ) ); ?>">Top Tracks</a></li>
						<li><a href="<?php echo esc_url( home_url( '/charts/artists' ) ); ?>">Top Artists</a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h5>Discover</h5>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/charts' ) ); ?>">Weekly Charts</a></li>
						<li><a href="<?php echo esc_url( home_url( '/charts/tracks' ) ); ?>">Track Exploration</a></li>
						<li><a href="<?php echo esc_url( home_url( '/charts/artists' ) ); ?>">Artist Profiles</a></li>
					</ul>
				</div>
			<?php endif; ?>

		</div>
		
		<?php if ( $footer_left || $footer_right ) : ?>
		<div class="footer-bottom-strip">
			<div class="footer-left-content"><?php echo do_shortcode( wp_kses_post( $footer_left ) ); ?></div>
			<div class="footer-right-content"><?php echo do_shortcode( wp_kses_post( $footer_right ) ); ?></div>
		</div>
		<?php endif; ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
