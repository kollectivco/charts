<?php
/**
 * Admin View: Premium Chart Editor
 * 1:1 Reference Match - High-Fidelity Redesign
 */
$manager = new \Charts\Admin\SourceManager();
$def_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$def = $def_id ? $manager->get_definition( $def_id ) : null;

// Field Mapping
$title           = $def ? $def->title : '';
$title_ar        = $def ? $def->title_ar : '';
$slug            = $def ? $def->slug : '';
$summary         = $def ? $def->chart_summary : '';
$chart_type      = $def ? $def->chart_type : 'top-songs';
$item_type       = $def ? $def->item_type : 'track';
$country         = $def ? $def->country_code : 'eg';
$frequency       = $def ? $def->frequency : 'weekly';
$cover_image_url = $def ? $def->cover_image_url : '';
$accent_color    = $def ? $def->accent_color : '#6366f1';
$is_public       = $def ? (int)$def->is_public : 1;
$is_featured     = $def ? (int)$def->is_featured : 0;
$archive_enabled = $def ? (int)$def->archive_enabled : 1;
$menu_order      = $def ? (int)$def->menu_order : 1;
?>

<div class="charts-admin-wrap premium-dark">
	<form method="post">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_definition">
		<?php if ( $def_id ) : ?>
			<input type="hidden" name="id" value="<?php echo $def_id; ?>">
		<?php endif; ?>

		<header class="charts-admin-header">
			<div class="charts-admin-title-group">
				<div style="display:flex; align-items:center; gap:10px; font-size:12px; font-weight:700; color:var(--charts-text-dim); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">
					<span>Charts</span>
					<span style="opacity:0.3;">&rsaquo;</span>
					<span><?php echo $def_id ? 'Edit Chart' : 'New Chart'; ?></span>
				</div>
				<h1 class="charts-admin-title"><?php echo $def_id ? 'Edit Chart' : 'New Chart'; ?></h1>
				<p class="charts-admin-subtitle">Configure chart display settings and metadata.</p>
			</div>
			<div class="charts-admin-actions" style="display:flex; gap:12px;">
				<a href="<?php echo admin_url( 'admin.php?page=charts-definitions' ); ?>" class="charts-btn-back">
					&larr; Back
				</a>
				<button type="submit" class="charts-btn-create">
					<?php if (!$def_id): ?>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px; vertical-align:middle;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
					<?php endif; ?>
					<?php echo $def_id ? 'Update' : 'Create'; ?>
				</button>
			</div>
		</header>

		<?php settings_errors( 'charts' ); ?>

		<div class="premium-form-card">
			<div class="premium-form-grid">
				
				<!-- Row 1 -->
				<div class="form-group">
					<label for="title">Chart Name (EN) <span class="required">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="form-control" placeholder="Hot 100" required>
				</div>
				<div class="form-group">
					<label for="title_ar">Chart Name (AR)</label>
					<input type="text" id="title_ar" name="title_ar" value="<?php echo esc_attr($title_ar); ?>" class="form-control" placeholder="أفضل ١٠٠" dir="rtl">
				</div>

				<!-- Row 2 -->
				<div class="form-group">
					<label for="slug">Slug <span class="required">*</span></label>
					<input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="form-control" placeholder="hot-100" required>
					<span class="input-helper">URL: /charts/<?php echo $slug ?: 'your-slug'; ?></span>
				</div>
				<div class="form-group">
					<label for="item_type">Chart Type <span class="required">*</span></label>
					<select name="item_type" id="item_type" class="form-control">
						<option value="track" <?php selected($item_type, 'track'); ?>>Tracks</option>
						<option value="artist" <?php selected($item_type, 'artist'); ?>>Artists</option>
						<option value="album" <?php selected($item_type, 'album'); ?>>Albums</option>
						<option value="video" <?php selected($item_type, 'video'); ?>>Videos</option>
					</select>
				</div>

				<!-- Row 3 -->
				<div class="form-group form-group-full">
					<label for="chart_summary">Description</label>
					<textarea id="chart_summary" name="chart_summary" class="form-control" placeholder="Chart description..."><?php echo esc_textarea($summary); ?></textarea>
				</div>

				<!-- Row 4 -->
				<div class="form-group">
					<label for="cover_image_url">Cover Image URL</label>
					<input type="text" id="cover_image_url" name="cover_image_url" value="<?php echo esc_attr($cover_image_url); ?>" class="form-control" placeholder="https://...">
				</div>
				<div class="form-group">
					<label for="accent_color">Accent Color</label>
					<div class="color-picker-wrap">
						<div class="color-swatch" style="background: <?php echo esc_attr($accent_color); ?>;"></div>
						<input type="text" id="accent_color" name="accent_color" value="<?php echo esc_attr($accent_color); ?>" class="form-control" style="font-family:monospace; text-transform:uppercase;">
					</div>
				</div>

				<!-- Row 5 -->
				<div class="form-group">
					<label for="menu_order">Display Order</label>
					<input type="number" id="menu_order" name="menu_order" value="<?php echo $menu_order; ?>" class="form-control">
				</div>
				<div class="form-group">
					<label for="frequency">Frequency</label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
						<option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly</option>
						<option value="monthly" <?php selected($frequency, 'monthly'); ?>>Monthly</option>
					</select>
				</div>

				<!-- Row 6: Toggles -->
				<div class="toggle-row">
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_public" value="1" <?php checked($is_public, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Active / Visible</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_featured" value="1" <?php checked($is_featured, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Featured on Homepage</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="archive_enabled" value="1" <?php checked($archive_enabled, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Archive Enabled</label>
					</div>
				</div>

			</div>
		</div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		// Sync color swatch
		$('#accent_color').on('input', function() {
			$('.color-swatch').css('background', $(this).val());
		});
		
		// Update slug helper live
		$('#slug').on('input', function() {
			const slug = $(this).val() || 'your-slug';
			$('.input-helper').text('URL: /charts/' + slug);
		});
	});
	</script>
</div>
