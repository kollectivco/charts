<?php
/**
 * Admin View: Premium Chart Editor (Light Mode)
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
$platform        = $def ? $def->platform : 'all';
$cover_image_url = $def ? $def->cover_image_url : '';
$accent_color    = ($def && !empty($def->accent_color)) ? $def->accent_color : '#6366f1';
$is_public       = $def ? (int)$def->is_public : 1;
$is_featured     = $def ? (int)$def->is_featured : 0;
$archive_enabled = $def ? (int)$def->archive_enabled : 1;
$menu_order      = $def ? (int)$def->menu_order : 0;
?>

<div class="charts-admin-wrap premium-light">
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
					<?php echo $def_id ? 'Update Chart' : 'Create Chart'; ?>
				</button>
			</div>
		</header>

		<?php settings_errors( 'charts' ); ?>

		<div class="premium-form-card">
			<div class="premium-form-grid">
				
				<!-- Row 1: Name & Slug -->
				<div class="form-group">
					<label for="title">Chart Name (EN) <span class="required">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="form-control" placeholder="Hot 100" required>
				</div>
                <div class="form-group">
					<label for="title_ar">Chart Name (AR)</label>
					<input type="text" id="title_ar" name="title_ar" value="<?php echo esc_attr($title_ar); ?>" class="form-control" placeholder="أفضل ١٠٠ أغنية" dir="rtl">
				</div>
				<div class="form-group">
					<label for="slug">URL Slug <span class="required">*</span></label>
					<input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="form-control" placeholder="hot-100" required>
					<span class="input-helper">URL: /charts/<?php echo $slug ?: 'your-slug'; ?></span>
				</div>

				<!-- Row 2: Types & Model -->
				<div class="form-group">
					<label for="item_type">Entity Type <span class="required">*</span></label>
					<select name="item_type" id="item_type" class="form-control">
						<option value="track" <?php selected($item_type, 'track'); ?>>Tracks (Audio)</option>
						<option value="artist" <?php selected($item_type, 'artist'); ?>>Artists</option>
						<option value="video" <?php selected($item_type, 'video'); ?>>Clips & Videos</option>
					</select>
					<span class="input-helper">Core data model (e.g. Song vs Artist).</span>
				</div>
				<div class="form-group">
					<label for="chart_type">Ranking Logic <span class="required">*</span></label>
					<select name="chart_type" id="chart_type" class="form-control">
						<optgroup label="Audio Logics" data-entity="track">
							<option value="top-songs" <?php selected($chart_type, 'top-songs'); ?>>Top Songs (Official)</option>
							<option value="viral" <?php selected($chart_type, 'viral'); ?>>Viral Trends & TikTok</option>
						</optgroup>
						<optgroup label="Visual Logics" data-entity="video">
							<option value="top-videos" <?php selected($chart_type, 'top-videos'); ?>>Top Videos / Clips</option>
						</optgroup>
						<optgroup label="Professional Logics" data-entity="artist">
							<option value="top-artists" <?php selected($chart_type, 'top-artists'); ?>>Top Artists</option>
						</optgroup>
					</select>
					<span class="input-helper">The specific ranking channel to filter from sources.</span>
				</div>

				<!-- Row 3: Meta Configuration -->
				<div class="form-group">
					<label for="country_code">Market / Country <span class="required">*</span></label>
					<div style="display: flex; gap: 10px;">
						<input type="text" id="country_code" name="country_code" value="<?php echo esc_attr($country); ?>" class="form-control" style="width: 80px; text-align: center; text-transform: uppercase;" placeholder="EG" maxlength="2" required>
						<select id="platform" name="platform" class="form-control" style="flex: 1;">
							<option value="all" <?php selected($platform, 'all'); ?>>Omni-Platform (Mixed)</option>
							<option value="spotify" <?php selected($platform, 'spotify'); ?>>Spotify Only</option>
							<option value="youtube" <?php selected($platform, 'youtube'); ?>>YouTube Only</option>
						</select>
					</div>
					<span class="input-helper">ISO code (e.g. EG, US) and primary data source platform.</span>
				</div>
				<div class="form-group">
					<label for="frequency">Frequency / Interval</label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="daily" <?php selected($frequency, 'daily'); ?>>Daily Charts</option>
						<option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly Charts</option>
						<option value="monthly" <?php selected($frequency, 'monthly'); ?>>Monthly Charts</option>
					</select>
				</div>

				<!-- Row 3: Description -->
				<div class="form-group form-group-full">
					<label for="chart_summary">Summary / Description</label>
					<textarea id="chart_summary" name="chart_summary" class="form-control" placeholder="A brief description of this chart product..."><?php echo esc_textarea($summary); ?></textarea>
				</div>

				<!-- Row 4: Cover Image & Branding -->
				<div class="form-group">
					<label>Cover Image</label>
					<div class="image-uploader-field">
						<img id="cover_preview" src="<?php echo esc_url($cover_image_url); ?>" class="image-preview <?php echo $cover_image_url ? 'has-image' : ''; ?>" alt="Preview">
						<input type="hidden" id="cover_image_url" name="cover_image_url" value="<?php echo esc_attr($cover_image_url); ?>">
						<div class="uploader-actions">
							<button type="button" class="charts-btn-back charts-upload-trigger">
								<span class="dashicons dashicons-upload" style="margin-top:2px;"></span> Select Image
							</button>
							<button type="button" class="charts-btn-back charts-remove-image" style="color: #ef4444; border-color: #fee2e2;">
								Remove
							</button>
						</div>
					</div>
					<span class="input-helper">High resolution square artwork (1:1) recommended.</span>
				</div>
				<div class="form-group">
					<label for="accent_color">Brand Accent Color</label>
					<div class="color-picker-wrap">
						<div class="color-swatch" style="background: <?php echo esc_attr($accent_color); ?>;"></div>
						<input type="text" id="accent_color" name="accent_color" value="<?php echo esc_attr($accent_color); ?>" class="form-control" style="font-family:monospace; text-transform:uppercase;" placeholder="#6366F1">
					</div>
					<span class="input-helper">Used for dots, links, and UI accents on the frontend.</span>
					
					<div style="margin-top: 32px;">
						<label for="menu_order">Display Priority</label>
						<input type="number" id="menu_order" name="menu_order" value="<?php echo $menu_order; ?>" class="form-control" style="width: 120px;">
						<span class="input-helper">Position in rankings and grids (lower = first).</span>
					</div>
				</div>

				<!-- Row 5: Visibility Toggles -->
				<div class="toggle-row">
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_public" value="1" <?php checked($is_public, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Published & Publicly Visible</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_featured" value="1" <?php checked($is_featured, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Featured Discovery Spot</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="archive_enabled" value="1" <?php checked($archive_enabled, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Historical Archive Enabled</label>
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

		// Intelligent Field Sync: Entity Type -> Ranking Logic
		function syncRankingLogic() {
			const entity = $('#item_type').val();
			const $logicSelect = $('#chart_type');
			
			// 1. Filter optgroups
			$logicSelect.find('optgroup').each(function() {
				const groupEntity = $(this).data('entity');
				if (groupEntity === entity) {
					$(this).show().prop('disabled', false);
				} else {
					$(this).hide().prop('disabled', true);
				}
			});

			// 2. Auto-select first visible option if current is hidden
			const $currentOption = $logicSelect.find('option:selected');
			if ($currentOption.parent().is(':disabled')) {
				const $firstVisible = $logicSelect.find('optgroup:not(:disabled) option').first();
				if ($firstVisible.length) {
					$logicSelect.val($firstVisible.val());
				}
			}
		}

		$('#item_type').on('change', syncRankingLogic);
		syncRankingLogic(); // Init on load
	});
	</script>
</div>
