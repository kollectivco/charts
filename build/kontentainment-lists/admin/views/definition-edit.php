<?php
/**
 * Admin View: Premium List Editor (Light Mode)
 * 1:1 Reference Match - High-Fidelity Redesign
 */
$manager = new \Charts\Admin\SourceManager();
$def_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$def = $def_id ? $manager->get_definition( $def_id ) : null;

// Field Mapping
$title           = $def ? $def->title : '';
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
$display_settings = $def && ! empty( $def->display_settings_json ) ? json_decode( $def->display_settings_json, true ) : array();
$preset_key = $display_settings['soundcharts_preset'] ?? 'top_tracks_egypt';
$soundcharts_rank_weight = $display_settings['soundcharts_rank_weight'] ?? 0.65;
$popularity_weight = $display_settings['popularity_weight'] ?? 0.15;
$trend_weight = $display_settings['trend_weight'] ?? 0.10;
$engagement_weight = $display_settings['engagement_weight'] ?? 0.10;
$decay_factor = $display_settings['decay_factor'] ?? 0.02;
$momentum_weight = $display_settings['momentum_weight'] ?? 1.5;
$movement_weight = $display_settings['movement_weight'] ?? 1.0;
$freshness_weight = $display_settings['freshness_weight'] ?? 4.0;
$auto_sync_day = $display_settings['auto_sync_day'] ?? 'MONDAY';
$auto_sync_time = $display_settings['auto_sync_time'] ?? '08:00';
$presets = \Charts\Services\SoundchartsPresetRegistry::all();
$auto_sync_enabled = $def ? (int) $def->auto_sync_enabled : 0;
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
					<span>Lists</span>
					<span style="opacity:0.3;">&rsaquo;</span>
					<span><?php echo $def_id ? 'Edit List' : 'New List'; ?></span>
				</div>
				<h1 class="charts-admin-title"><?php echo $def_id ? 'Edit List' : 'New List'; ?></h1>
				<p class="charts-admin-subtitle">Configure list display settings and metadata.</p>
			</div>
			<div class="charts-admin-actions" style="display:flex; gap:12px;">
				<a href="<?php echo admin_url( 'admin.php?page=charts-definitions' ); ?>" class="charts-btn-back">
					&larr; Back
				</a>
				<button type="submit" class="charts-btn-create">
					<?php echo $def_id ? 'Update List' : 'Create List'; ?>
				</button>
			</div>
		</header>

		<?php settings_errors( 'kontentainment-lists' ); ?>

		<div class="premium-form-card">
			<div class="premium-form-grid">
				
				<!-- Row 1: Name & Slug -->
				<div class="form-group">
					<label for="title">List Name <span class="required">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="form-control" placeholder="Hot 100" required>
				</div>
				<div class="form-group">
					<label for="slug">URL Slug <span class="required">*</span></label>
					<input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="form-control" placeholder="hot-100" required>
					<span class="input-helper">URL: /lists/<?php echo $slug ?: 'your-slug'; ?></span>
				</div>

				<!-- Row 2: Types -->
				<div class="form-group">
					<label for="item_type">Entity Type <span class="required">*</span></label>
					<select name="item_type" id="item_type" class="form-control">
						<option value="track" <?php selected($item_type, 'track'); ?>>Tracks</option>
						<option value="artist" <?php selected($item_type, 'artist'); ?>>Artists</option>
						<option value="album" <?php selected($item_type, 'album'); ?>>Albums</option>
						<option value="video" <?php selected($item_type, 'video'); ?>>Videos</option>
					</select>
				</div>
				<div class="form-group">
					<label for="frequency">Frequency</label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
						<option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly</option>
						<option value="monthly" <?php selected($frequency, 'monthly'); ?>>Monthly</option>
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
						<input type="text" id="accent_color" name="accent_color" value="<?php echo esc_attr($accent_color); ?>" class="form-control" style="font-family:monospace; text-transform:uppercase;">
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

				<div class="form-group form-group-full" style="margin-top:20px;">
					<label>Soundcharts Preset & Automation</label>
					<div class="premium-form-grid">
						<div class="form-group">
							<label for="soundcharts_preset">Endpoint Preset</label>
							<select id="soundcharts_preset" name="soundcharts_preset" class="form-control">
								<?php foreach ( $presets as $key => $preset ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $preset_key, $key ); ?>>
										<?php echo esc_html( $preset['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="input-helper">Preset controls the endpoint path, entity type, default country, chart type, and normalization profile.</span>
						</div>
						<div class="form-group">
							<label for="soundcharts_rank_weight">Rank Weight</label>
							<input type="number" step="0.01" min="0" id="soundcharts_rank_weight" name="soundcharts_rank_weight" value="<?php echo esc_attr( $soundcharts_rank_weight ); ?>" class="form-control">
							<span class="input-helper">Inverse rank baseline used when only chart positions are available.</span>
						</div>
						<div class="form-group">
							<label for="popularity_weight">Popularity Weight</label>
							<input type="number" step="0.01" min="0" id="popularity_weight" name="popularity_weight" value="<?php echo esc_attr( $popularity_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="trend_weight">Trend Weight</label>
							<input type="number" step="0.01" min="0" id="trend_weight" name="trend_weight" value="<?php echo esc_attr( $trend_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="engagement_weight">Engagement Weight</label>
							<input type="number" step="0.01" min="0" id="engagement_weight" name="engagement_weight" value="<?php echo esc_attr( $engagement_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="decay_factor">Decay Factor</label>
							<input type="number" step="0.01" min="0" id="decay_factor" name="decay_factor" value="<?php echo esc_attr( $decay_factor ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="momentum_weight">Momentum Weight</label>
							<input type="number" step="0.01" min="0" id="momentum_weight" name="momentum_weight" value="<?php echo esc_attr( $momentum_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="movement_weight">Movement Weight</label>
							<input type="number" step="0.01" min="0" id="movement_weight" name="movement_weight" value="<?php echo esc_attr( $movement_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label for="freshness_weight">Freshness Weight</label>
							<input type="number" step="0.01" min="0" id="freshness_weight" name="freshness_weight" value="<?php echo esc_attr( $freshness_weight ); ?>" class="form-control">
						</div>
						<div class="form-group">
							<label>Auto Sync</label>
							<div class="toggle-item" style="margin-top:10px;">
								<label class="switch">
									<input type="checkbox" name="auto_sync_enabled" value="1" <?php checked( $auto_sync_enabled, 1 ); ?>>
									<span class="slider"></span>
								</label>
								<label>Enable weekly auto sync</label>
							</div>
						</div>
						<div class="form-group">
							<label for="auto_sync_day">Sync Day</label>
							<select id="auto_sync_day" name="auto_sync_day" class="form-control">
								<?php foreach ( array( 'MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY','SUNDAY' ) as $day ) : ?>
									<option value="<?php echo esc_attr( $day ); ?>" <?php selected( $auto_sync_day, $day ); ?>><?php echo esc_html( ucfirst( strtolower( $day ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group">
							<label for="auto_sync_time">Sync Time</label>
							<input type="time" id="auto_sync_time" name="auto_sync_time" value="<?php echo esc_attr( $auto_sync_time ); ?>" class="form-control">
						</div>
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
			$('.input-helper').text('URL: /lists/' + slug);
		});
	});
	</script>
</div>
