<?php
/**
 * Kontentainment Charts — Premium Light Mode Footer
 */
$wordmark     = get_option( 'charts_wordmark', 'KCharts' ); 
$footer_desc  = get_option( 'charts_footer_description' );
$footer_copy  = get_option( 'charts_footer_copyright', 'Kontentainment Charts.' );
?>
</main> <!-- .charts-product-main -->

<footer class="charts-product-footer">
	<div class="kc-container">
		<div class="footer-inner">
			
			<div class="footer-brand">
				<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="charts-wordmark">
					<?php echo esc_html( $wordmark ); ?>
				</a>
				<?php if ( $footer_desc ) : ?>
					<p><?php echo esc_html( $footer_desc ); ?></p>
				<?php endif; ?>
				<div class="footer-social">
					<a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
					<a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
					<a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
					<a href="#"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg></a>
				</div>
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
		
		<div class="footer-bottom-strip">
			<div class="copyright">&copy; <?php echo date('Y'); ?> <?php echo esc_html( $footer_copy ); ?> All rights reserved.</div>
			<div class="secondary-links">
				<span class="muted">Updated weekly — Charts based on multi-platform streaming data</span>
				&nbsp; · &nbsp; <a href="<?php echo esc_url( admin_url( 'admin.php?page=charts' ) ); ?>" style="color:inherit; text-decoration:none;">Admin</a>
			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
