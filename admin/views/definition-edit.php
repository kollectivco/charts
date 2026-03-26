<?php
/**
 * Admin View: Edit Chart Definition
 */
$manager = new \Charts\Admin\SourceManager();
$def_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$def = $def_id ? $manager->get_definition( $def_id ) : null;

$title = $def ? $def->title : '';
$slug = $def ? $def->slug : '';
$summary = $def ? $def->chart_summary : '';
$chart_type = $def ? $def->chart_type : 'top-songs';
$item_type = $def ? $def->item_type : 'track';
$country = $def ? $def->country_code : 'eg';
$frequency = $def ? $def->frequency : 'weekly';
$is_public = $def ? (int)$def->is_public : 1;
$is_featured = $def ? (int)$def->is_featured : 0;
$menu_order = $def ? (int)$def->menu_order : 0;
?>

<div class="charts-admin-wrap">
	<header class="charts-admin-header">
		<div class="charts-admin-title-group">
			<h1 class="charts-admin-title"><?php echo $def_id ? __( 'Edit Chart', 'charts' ) : __( 'Create New Chart', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Configure the parameters for this intelligence product.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-definitions' ); ?>" class="charts-btn charts-btn-outline">
				<?php _e( 'Back to List', 'charts' ); ?>
			</a>
		</div>
	</header>

	<div class="charts-editor-layout">
		<form method="post" class="charts-bento-form">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="save_definition">
			<?php if ( $def_id ) : ?>
				<input type="hidden" name="id" value="<?php echo $def_id; ?>">
			<?php endif; ?>

			<div class="charts-bento-card editor-main">
				<div class="form-section">
					<h3 class="section-title"><?php _e( 'Identity & Routing', 'charts' ); ?></h3>
					
					<div class="form-group">
						<label for="title"><?php _e( 'Chart Title', 'charts' ); ?></label>
						<input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="widefat" placeholder="e.g. Egypt Top Songs" required>
						<p class="description"><?php _e( 'The display name shown on the frontend and in lists.', 'charts' ); ?></p>
					</div>

					<div class="form-group">
						<label for="slug"><?php _e( 'URL Slug', 'charts' ); ?></label>
						<div class="slug-input-group">
							<span class="slug-prefix">/charts/</span>
							<input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="widefat" placeholder="top-songs" required>
						</div>
						<p class="description"><?php _e( 'The unique URL part for this chart. (Letters, numbers, and hyphens only).', 'charts' ); ?></p>
					</div>

					<div class="form-group">
						<label for="chart_summary"><?php _e( 'Summary / Meta Description', 'charts' ); ?></label>
						<textarea id="chart_summary" name="chart_summary" rows="3" class="widefat"><?php echo esc_textarea($summary); ?></textarea>
					</div>
				</div>

				<div class="form-section divider">
					<h3 class="section-title"><?php _e( 'Data Parameters', 'charts' ); ?></h3>
					
					<div class="form-row">
						<div class="form-group half">
							<label for="chart_type"><?php _e( 'Chart Scope', 'charts' ); ?></label>
							<select name="chart_type" id="chart_type">
								<option value="top-songs" <?php selected($chart_type, 'top-songs'); ?>>Top Songs</option>
								<option value="top-artists" <?php selected($chart_type, 'top-artists'); ?>>Top Artists</option>
								<option value="top-videos" <?php selected($chart_type, 'top-videos'); ?>>Top Videos</option>
								<option value="viral" <?php selected($chart_type, 'viral'); ?>>Viral / Trending</option>
							</select>
						</div>
						<div class="form-group half">
							<label for="item_type"><?php _e( 'Primary Item Entity', 'charts' ); ?></label>
							<select name="item_type" id="item_type">
								<option value="track" <?php selected($item_type, 'track'); ?>>Track</option>
								<option value="artist" <?php selected($item_type, 'artist'); ?>>Artist</option>
								<option value="video" <?php selected($item_type, 'video'); ?>>Video</option>
								<option value="album" <?php selected($item_type, 'album'); ?>>Album</option>
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group half">
							<label for="country_code"><?php _e( 'Country Code (ISO)', 'charts' ); ?></label>
							<input type="text" id="country_code" name="country_code" value="<?php echo esc_attr($country); ?>" maxlength="5" placeholder="eg">
						</div>
						<div class="form-group half">
							<label for="frequency"><?php _e( 'Frequency', 'charts' ); ?></label>
							<select name="frequency" id="frequency">
								<option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
								<option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly</option>
								<option value="monthly" <?php selected($frequency, 'monthly'); ?>>Monthly</option>
							</select>
						</div>
					</div>
				</div>

				<div class="form-section divider">
					<h3 class="section-title"><?php _e( 'Visibility & Display', 'charts' ); ?></h3>

					<div class="form-group-checkbox">
						<label class="switch">
							<input type="checkbox" name="is_public" value="1" <?php checked($is_public, 1); ?>>
							<span class="slider round"></span>
						</label>
						<span class="label-text"><?php _e( 'Publicly Visible', 'charts' ); ?></span>
					</div>

					<div class="form-group-checkbox">
						<label class="switch">
							<input type="checkbox" name="is_featured" value="1" <?php checked($is_featured, 1); ?>>
							<span class="slider round"></span>
						</label>
						<span class="label-text"><?php _e( 'Featured on Homepage', 'charts' ); ?></span>
					</div>

					<div class="form-group">
						<label for="menu_order"><?php _e( 'Priority / Order', 'charts' ); ?></label>
						<input type="number" id="menu_order" name="menu_order" value="<?php echo $menu_order; ?>" style="width: 80px;">
						<p class="description"><?php _e( 'Higher priority items appear first in grids (0 = default).', 'charts' ); ?></p>
					</div>
				</div>
			</div>

			<footer class="form-footer">
				<button type="submit" class="charts-btn charts-btn-primary large">
					<?php echo $def_id ? __( 'Update Chart Intelligence', 'charts' ) : __( 'Create Chart Definition', 'charts' ); ?>
				</button>
			</footer>
		</form>
	</div>
</div>
