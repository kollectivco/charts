<?php
/**
 * Standalone Lists Footer - Cinematic Architecture
 */
// Force standalone for these templates
global $wpdb;
$definitions = $wpdb->get_results( "SELECT title, slug FROM {$wpdb->prefix}charts_definitions LIMIT 6" );

$description = charts_tr(get_option( 'charts_footer_description', 'The definitive source for weekly music lists, powered by real streaming intelligence data.' ));
$copyright   = charts_tr(get_option( 'charts_footer_copyright', 'Kontentainment Lists.' ));
?>

</main><!-- /.charts-product-main -->

<footer class="charts-product-footer">
	<div class="kc-container">
		
		<div class="footer-inner">
			
			<!-- Brand Column -->
			<div class="footer-brand">
				<a href="<?php echo home_url('/lists'); ?>" class="charts-wordmark">KLists</a>
				<p><?php echo wp_kses_post($description); ?></p>
			</div>

			<!-- Lists Column -->
			<div class="footer-col">
				<h5><?php echo charts_tr('Lists'); ?></h5>
				<ul>
					<?php foreach ( $definitions as $def ) : ?>
						<li><a href="<?php echo home_url('/lists/' . $def->slug . '/'); ?>"><?php echo esc_html($def->title); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Discover Column -->
			<div class="footer-col">
				<h5><?php echo charts_tr('Discover'); ?></h5>
				<ul>
					<li><a href="/lists"><?php echo charts_tr('All Lists'); ?></a></li>
					<li><a href="/lists"><?php echo charts_tr('Top Track Lists'); ?></a></li>
					<li><a href="/lists"><?php echo charts_tr('Top Artist Lists'); ?></a></li>
					<li><a href="/lists"><?php echo charts_tr('Top Album Lists'); ?></a></li>
					<li><a href="/lists"><?php echo charts_tr('Hot 100'); ?></a></li>
				</ul>
			</div>

			<!-- Data Sources Column -->
			<div class="footer-col">
				<h5><?php echo charts_tr('Data Sources'); ?></h5>
				<ul>
					<li><a href="#"><?php echo charts_tr('Spotify Streaming'); ?></a></li>
					<li><a href="#"><?php echo charts_tr('YouTube Music'); ?></a></li>
					<li><a href="#"><?php echo charts_tr('TikTok Plays'); ?></a></li>
					<li><a href="#"><?php echo charts_tr('Radio Display'); ?></a></li>
					<li><a href="#"><?php echo charts_tr('Digital Sales'); ?></a></li>
				</ul>
			</div>

		</div>

		<!-- Bottom Strip -->
		<div class="footer-bottom-strip">
			<div class="bottom-left">
				&copy; <?php echo date('Y'); ?> <?php echo esc_html($copyright); ?> <?php echo charts_tr('All rights reserved.'); ?>
			</div>
			<div class="bottom-center">
				<span class="muted"><?php echo charts_tr('Updated weekly &middot; Lists based on multi-platform streaming data'); ?></span>
			</div>
			<div class="bottom-right">
				<a href="<?php echo admin_url('admin.php?page=charts-dashboard'); ?>" style="color: inherit; text-decoration: none;"><?php echo charts_tr('DASHBOARD'); ?></a>
			</div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
