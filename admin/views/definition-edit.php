<?php
/** Admin view: create or edit a chart definition. */
$manager = new \Charts\Admin\SourceManager();
$def_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$def     = $def_id ? $manager->get_definition( $def_id ) : null;

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
$accent_color    = ( $def && ! empty( $def->accent_color ) ) ? $def->accent_color : '#6366f1';
$is_public       = $def ? (int) $def->is_public : 1;
$is_featured     = $def ? (int) $def->is_featured : 0;
$archive_enabled = $def ? (int) $def->archive_enabled : 1;
$menu_order      = $def ? (int) $def->menu_order : 0;
$back_url        = \Charts\Core\Router::get_dashboard_url( 'definitions' );
?>
<div class="charts-admin-wrap premium-light chart-definition-editor">
	<form method="post" class="chart-editor-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_definition">
		<?php if ( $def_id ) : ?><input type="hidden" name="id" value="<?php echo (int) $def_id; ?>"><?php endif; ?>

		<header class="chart-editor-header">
			<div class="chart-editor-heading">
				<nav class="chart-editor-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'charts' ); ?>">
					<a href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Charts', 'charts' ); ?></a>
					<span aria-hidden="true">/</span>
					<span><?php echo $def_id ? esc_html__( 'Edit Chart', 'charts' ) : esc_html__( 'New Chart', 'charts' ); ?></span>
				</nav>
				<h1 class="charts-admin-title"><?php echo $def_id ? esc_html__( 'Edit Chart', 'charts' ) : esc_html__( 'New Chart', 'charts' ); ?></h1>
				<p class="charts-admin-subtitle"><?php esc_html_e( 'Configure chart display settings and metadata.', 'charts' ); ?></p>
			</div>
			<div class="chart-editor-header-actions">
				<a href="<?php echo esc_url( $back_url ); ?>" class="charts-btn-back"><span aria-hidden="true">&larr;</span> <?php esc_html_e( 'Back', 'charts' ); ?></a>
				<button type="submit" class="charts-btn-create"><?php echo $def_id ? esc_html__( 'Update Chart', 'charts' ) : esc_html__( 'Create Chart', 'charts' ); ?></button>
			</div>
		</header>

		<?php settings_errors( 'charts' ); ?>

		<section class="chart-editor-card" aria-label="<?php esc_attr_e( 'Chart settings', 'charts' ); ?>">
			<div class="chart-editor-grid">
				<div class="form-group">
					<label for="title"><?php esc_html_e( 'Chart Name (EN)', 'charts' ); ?> <span class="required">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo esc_attr( $title ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'e.g. Hot 100', 'charts' ); ?>" required>
				</div>
				<div class="form-group">
					<label for="title_ar"><?php esc_html_e( 'Chart Name (AR)', 'charts' ); ?></label>
					<input type="text" id="title_ar" name="title_ar" value="<?php echo esc_attr( $title_ar ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'اكتب اسم الشارت بالعربية', 'charts' ); ?>" dir="rtl">
				</div>

				<div class="form-group">
					<label for="slug"><?php esc_html_e( 'URL Slug', 'charts' ); ?> <span class="required">*</span></label>
					<input type="text" id="slug" name="slug" value="<?php echo esc_attr( $slug ); ?>" class="form-control" placeholder="hot-100" required>
					<span class="input-helper chart-slug-preview"><?php esc_html_e( 'Public URL:', 'charts' ); ?> <code>/charts/<span><?php echo esc_html( $slug ?: 'your-slug' ); ?></span></code></span>
				</div>
				<div class="form-group">
					<label for="item_type"><?php esc_html_e( 'Entity Type', 'charts' ); ?> <span class="required">*</span></label>
					<select name="item_type" id="item_type" class="form-control" required>
						<option value="track" <?php selected( $item_type, 'track' ); ?>><?php esc_html_e( 'Tracks (Audio)', 'charts' ); ?></option>
						<option value="artist" <?php selected( $item_type, 'artist' ); ?>><?php esc_html_e( 'Artists', 'charts' ); ?></option>
						<option value="video" <?php selected( $item_type, 'video' ); ?>><?php esc_html_e( 'Clips & Videos', 'charts' ); ?></option>
						<option value="album" <?php selected( $item_type, 'album' ); ?>><?php esc_html_e( 'Albums & EPs', 'charts' ); ?></option>
					</select>
					<span class="input-helper"><?php esc_html_e( 'Choose the type of content ranked in this chart.', 'charts' ); ?></span>
				</div>

				<div class="form-group">
					<label for="chart_type"><?php esc_html_e( 'Content Category', 'charts' ); ?> <span class="required">*</span></label>
					<select name="chart_type" id="chart_type" class="form-control" required>
						<optgroup label="<?php esc_attr_e( 'Audio Charts', 'charts' ); ?>" data-entity="track">
							<option value="top-songs" <?php selected( $chart_type, 'top-songs' ); ?>><?php esc_html_e( 'Top Songs (Official)', 'charts' ); ?></option>
							<option value="viral" <?php selected( $chart_type, 'viral' ); ?>><?php esc_html_e( 'Viral Trends & TikTok', 'charts' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Video Charts', 'charts' ); ?>" data-entity="video">
							<option value="top-videos" <?php selected( $chart_type, 'top-videos' ); ?>><?php esc_html_e( 'Top Videos / Clips', 'charts' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Artist Charts', 'charts' ); ?>" data-entity="artist">
							<option value="top-artists" <?php selected( $chart_type, 'top-artists' ); ?>><?php esc_html_e( 'Top Artists', 'charts' ); ?></option>
						</optgroup>
						<optgroup label="<?php esc_attr_e( 'Album Charts', 'charts' ); ?>" data-entity="album">
							<option value="top-albums" <?php selected( $chart_type, 'top-albums' ); ?>><?php esc_html_e( 'Top Albums', 'charts' ); ?></option>
						</optgroup>
					</select>
					<span class="input-helper"><?php esc_html_e( 'Sets the chart’s ranking and display template.', 'charts' ); ?></span>
				</div>
				<div class="form-group">
					<label for="country_code"><?php esc_html_e( 'Market / Country', 'charts' ); ?> <span class="required">*</span></label>
					<div class="chart-market-fields">
						<input type="text" id="country_code" name="country_code" value="<?php echo esc_attr( strtoupper( $country ) ); ?>" class="form-control chart-country-code" placeholder="EG" maxlength="2" required>
						<select id="platform" name="platform" class="form-control">
							<option value="all" <?php selected( $platform, 'all' ); ?>><?php esc_html_e( 'Omni-Platform (Mixed)', 'charts' ); ?></option>
							<option value="spotify" <?php selected( $platform, 'spotify' ); ?>><?php esc_html_e( 'Spotify Only', 'charts' ); ?></option>
							<option value="youtube" <?php selected( $platform, 'youtube' ); ?>><?php esc_html_e( 'YouTube Only', 'charts' ); ?></option>
							<option value="billboard" <?php selected( $platform, 'billboard' ); ?>><?php esc_html_e( 'Billboard Arabia', 'charts' ); ?></option>
							<option value="kontent" <?php selected( $platform, 'kontent' ); ?>><?php esc_html_e( 'Kontent Analytics', 'charts' ); ?></option>
						</select>
					</div>
					<span class="input-helper"><?php esc_html_e( 'Use an ISO country code and choose the primary data source.', 'charts' ); ?></span>
				</div>
				<div class="form-group">
					<label for="frequency"><?php esc_html_e( 'Frequency / Interval', 'charts' ); ?></label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="daily" <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Daily Charts', 'charts' ); ?></option>
						<option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly Charts', 'charts' ); ?></option>
						<option value="monthly" <?php selected( $frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly Charts', 'charts' ); ?></option>
					</select>
				</div>

				<div class="form-group chart-editor-full">
					<label for="chart_summary"><?php esc_html_e( 'Summary / Description', 'charts' ); ?></label>
					<textarea id="chart_summary" name="chart_summary" class="form-control" rows="4" placeholder="<?php esc_attr_e( 'Add a short description for this chart…', 'charts' ); ?>"><?php echo esc_textarea( $summary ); ?></textarea>
				</div>

				<div class="form-group">
					<label><?php esc_html_e( 'Cover Image', 'charts' ); ?></label>
					<div class="image-uploader-field chart-cover-uploader">
						<img id="cover_preview" src="<?php echo esc_url( $cover_image_url ); ?>" class="image-preview <?php echo $cover_image_url ? 'has-image' : ''; ?>" alt="<?php esc_attr_e( 'Cover image preview', 'charts' ); ?>">
						<input type="hidden" id="cover_image_url" name="cover_image_url" value="<?php echo esc_attr( $cover_image_url ); ?>">
						<div class="uploader-actions">
							<button type="button" class="charts-btn-back charts-upload-trigger"><span class="dashicons dashicons-upload" aria-hidden="true"></span> <?php esc_html_e( 'Select Image', 'charts' ); ?></button>
							<button type="button" class="charts-btn-back charts-remove-image"><?php esc_html_e( 'Remove', 'charts' ); ?></button>
						</div>
					</div>
					<span class="input-helper"><?php esc_html_e( 'A high-resolution square image works best.', 'charts' ); ?></span>
				</div>
				<div class="form-group chart-branding-fields">
					<label for="accent_color"><?php esc_html_e( 'Brand Accent Color', 'charts' ); ?></label>
					<div class="chart-color-control">
						<input type="color" id="accent_color_picker" value="<?php echo esc_attr( $accent_color ); ?>" aria-label="<?php esc_attr_e( 'Choose accent color', 'charts' ); ?>">
						<input type="text" id="accent_color" name="accent_color" value="<?php echo esc_attr( $accent_color ); ?>" class="form-control" placeholder="#6366F1">
					</div>
					<span class="input-helper"><?php esc_html_e( 'Used for links and accents on the public chart page.', 'charts' ); ?></span>
					<div class="chart-priority-field">
						<label for="menu_order"><?php esc_html_e( 'Display Priority', 'charts' ); ?></label>
						<input type="number" id="menu_order" name="menu_order" value="<?php echo (int) $menu_order; ?>" class="form-control" min="0" step="1">
						<span class="input-helper"><?php esc_html_e( 'Lower numbers appear first.', 'charts' ); ?></span>
					</div>
				</div>

				<div class="chart-editor-toggles chart-editor-full">
					<label class="chart-toggle-option"><span class="switch"><input type="checkbox" name="is_public" value="1" <?php checked( $is_public, 1 ); ?>><span class="slider"></span></span><span><?php esc_html_e( 'Published & Publicly Visible', 'charts' ); ?></span></label>
					<label class="chart-toggle-option"><span class="switch"><input type="checkbox" name="is_featured" value="1" <?php checked( $is_featured, 1 ); ?>><span class="slider"></span></span><span><?php esc_html_e( 'Featured Discovery Spot', 'charts' ); ?></span></label>
					<label class="chart-toggle-option"><span class="switch"><input type="checkbox" name="archive_enabled" value="1" <?php checked( $archive_enabled, 1 ); ?>><span class="slider"></span></span><span><?php esc_html_e( 'Historical Archive Enabled', 'charts' ); ?></span></label>
				</div>
			</div>
		</section>
	</form>
</div>

<script>
jQuery(function ($) {
	const $slug = $('#slug');
	const $slugPreview = $('.chart-slug-preview code span');
	const $color = $('#accent_color');
	const $colorPicker = $('#accent_color_picker');

	$slug.on('input', function () {
		$slugPreview.text($(this).val() || 'your-slug');
	});

	function normalizeHex(value) {
		return /^#[0-9a-f]{6}$/i.test(value) ? value : null;
	}
	$color.on('input change', function () {
		const color = normalizeHex($(this).val());
		if (color) $colorPicker.val(color);
	});
	$colorPicker.on('input change', function () {
		$color.val($(this).val().toUpperCase());
	});

	function syncChartCategories() {
		const entity = $('#item_type').val();
		const $select = $('#chart_type');
		const $groups = $select.find('optgroup');
		$groups.prop('disabled', true).hide();
		const $group = $groups.filter(function () { return $(this).data('entity') === entity; });
		$group.prop('disabled', false).show();
		if (!$group.find('option').filter(function () { return this.value === $select.val(); }).length) {
			$select.val($group.find('option').first().val());
		}
	}
	$('#item_type').on('change', syncChartCategories);
	syncChartCategories();
});
</script>
