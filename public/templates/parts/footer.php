<?php
/**
 * Standalone Charts Footer - Cinematic Architecture
 */
// Force standalone for these templates
global $wpdb;
$definitions = $wpdb->get_results( "SELECT title, slug FROM {$wpdb->prefix}charts_definitions LIMIT 6" );

$description = get_option( 'charts_footer_description', 'The definitive source for music chart runtimes, powered by real streaming intelligence data.' );
$copyright   = get_option( 'charts_footer_copyright', 'Kontentainment Charts.' );
?>

</main><!-- /.charts-product-main -->

<footer class="charts-product-footer">
	<div class="kc-container">
		
		<div class="footer-inner">
			
			<!-- Brand Column -->
			<div class="footer-brand">
				<a href="<?php echo home_url('/charts'); ?>" class="charts-wordmark">KCharts</a>
				<p><?php echo wp_kses_post($description); ?></p>
			</div>

			<!-- Charts Column -->
			<div class="footer-col">
				<h5>Charts</h5>
				<ul>
					<?php foreach ( $definitions as $def ) : ?>
						<li><a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>"><?php echo esc_html($def->title); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Discover Column -->
			<div class="footer-col">
				<h5>Discover</h5>
				<ul>
					<li><a href="/charts">All Charts</a></li>
					<li><a href="/charts">Top Tracks</a></li>
					<li><a href="/charts">Top Artists</a></li>
					<li><a href="/charts">Top Albums</a></li>
					<li><a href="/charts">Hot 100</a></li>
				</ul>
			</div>

			<!-- Data Sources Column -->
			<div class="footer-col">
				<h5>Data Sources</h5>
				<ul>
					<li><a href="#">Spotify Streaming</a></li>
					<li><a href="#">YouTube Music</a></li>
					<li><a href="#">TikTok Plays</a></li>
					<li><a href="#">Radio Display</a></li>
					<li><a href="#">Digital Sales</a></li>
				</ul>
			</div>

		</div>

		<!-- Bottom Strip -->
		<div class="footer-bottom-strip">
			<div class="bottom-left">
				&copy; <?php echo date('Y'); ?> <?php echo esc_html($copyright); ?> All rights reserved.
			</div>
			<div class="bottom-center">
				<span class="muted">Updated weekly &middot; Charts based on multi-platform streaming data</span>
			</div>
			<div class="bottom-right">
				<a href="<?php echo admin_url('admin.php?page=charts-dashboard'); ?>" style="color: inherit; text-decoration: none;">DASHBOARD</a>
			</div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
