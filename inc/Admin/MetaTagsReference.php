<?php

namespace Charts\Admin;

/**
 * Handle the Meta Tags Reference screen in Admin.
 */
class MetaTagsReference {

	public static function init() {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 20 );
	}

	public static function add_menu() {
		add_submenu_page(
			'charts-dashboard',
			__( 'Meta Tags Reference', 'charts' ),
			__( 'Meta Tags', 'charts' ),
			'manage_options',
			'charts-meta-tags',
			array( self::class, 'render_page' )
		);
	}

	public static function render_page() {
		$registry = \Charts\Core\MetaTags::get_registry();
		?>
		<div class="wrap kc-admin-page">
			<div class="kc-page-header" style="margin-bottom: 30px;">
				<h1 class="wp-heading-inline"><?php _e( 'Dynamic Entry Meta Tags', 'charts' ); ?></h1>
				<p class="description"><?php _e( 'Use these tags in Foxiz, Elementor, or theme meta fields to display dynamic entity data.', 'charts' ); ?></p>
			</div>

			<div class="kc-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
				<?php foreach ( array_keys($registry) as $type ): ?>
					<a href="#tags-<?php echo esc_attr($type); ?>" class="kc-tab-link" style="text-decoration: none; padding: 8px 16px; border-radius: 6px; background: #f1f5f9; color: #475569; font-weight: 500;"><?php echo ucfirst($type); ?></a>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $registry as $type => $tags ): ?>
			<div id="tags-<?php echo esc_attr($type); ?>" class="kc-tag-group" style="margin-bottom: 40px;">
				<h2 style="border-left: 4px solid #6366f1; padding-left: 15px; text-transform: capitalize; margin-bottom: 20px;"><?php echo esc_html($type); ?> <?php _e( 'Tags', 'charts' ); ?></h2>
				
				<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
					<?php foreach ( $tags as $tag => $data ): ?>
					<div class="kc-card" style="background: #fff; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s;">
						<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
							<code style="background: #eff6ff; color: #2563eb; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 13px;">{<?php echo esc_html($tag); ?>}</code>
						</div>
						<h4 style="margin: 0 0 5px; color: #1e293b;"><?php echo esc_html($data['label']); ?></h4>
						<p style="margin: 0; color: #64748b; font-size: 12px; line-height: 1.5;"><?php echo esc_html($data['desc']); ?></p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>

			<div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px;">
				<h3><?php _e( 'Usage Example', 'charts' ); ?></h3>
				<p><?php _e( 'In your builder or meta field, you can combine text and tags:', 'charts' ); ?></p>
				<code style="display: block; padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
					<?php _e( 'Artist: {artist_name} | Subscribed: {artist_followers}', 'charts' ); ?>
				</code>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.kc-tab-link').click(function(e) {
				e.preventDefault();
				$('.kc-tag-group').hide();
				$($(this).attr('href')).show();
				$('.kc-tab-link').css('background', '#f1f5f9').css('color', '#475569');
				$(this).css('background', '#6366f1').css('color', '#fff');
			});
			$('.kc-tab-link:first').click();
		});
		</script>
		<?php
	}
}
