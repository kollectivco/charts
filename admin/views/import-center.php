<?php
/**
 * Unified Import Center View
 */
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( false );
$pre_source  = $_GET['source'] ?? 'spotify';
?>
<div class="wrap charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div class="charts-admin-title-group">
			<div style="display:flex; align-items:center; gap:10px; font-size:12px; font-weight:700; color:var(--charts-text-dim); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">
				<span>Charts</span>
				<span style="opacity:0.3;">&rsaquo;</span>
				<span>Import Center</span>
			</div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Data Intelligence Import', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle">Unified workflow for Spotify and YouTube chart synchronization.</p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-imports' ); ?>" class="charts-btn-back">
				View Import History
			</a>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<div class="premium-form-card">
		<form method="post" action="" enctype="multipart/form-data" id="unified-import-form">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="unified_import">

			<div class="import-steps">
				
				<!-- Step 1: Source & Platform -->
				<div class="import-step" data-step="1">
					<div class="step-header">
						<span class="step-number">01</span>
						<h3><?php esc_html_e( 'Choose Data Source', 'charts' ); ?></h3>
					</div>
					<div class="platform-selector">
						<label class="platform-card">
							<input type="radio" name="platform" value="spotify" <?php checked($pre_source, 'spotify'); ?>>
							<div class="platform-inner">
								<div class="platform-icon spotify">
									<span class="dashicons dashicons-spotify"></span>
								</div>
								<div class="platform-info">
									<strong>Spotify Charts</strong>
									<span>Direct CSV Export</span>
								</div>
							</div>
						</label>
						<label class="platform-card">
							<input type="radio" name="platform" value="youtube" <?php checked($pre_source, 'youtube'); ?>>
							<div class="platform-inner">
								<div class="platform-icon youtube">
									<span class="dashicons dashicons-video-alt3"></span>
								</div>
								<div class="platform-info">
									<strong>YouTube Charts</strong>
									<span>Automatic YouTube CSV</span>
								</div>
							</div>
						</label>
					</div>
				</div>

				<!-- Step 2: File Upload -->
				<div class="import-step" data-step="2">
					<div class="step-header">
						<span class="step-number">02</span>
						<h3><?php esc_html_e( 'Upload Chart Data', 'charts' ); ?></h3>
					</div>
					<div class="file-upload-zone" id="drop-zone">
						<div class="upload-content">
							<span class="dashicons dashicons-upload"></span>
							<p><?php esc_html_e( 'Drag and drop your CSV file or click to browse', 'charts' ); ?></p>
							<input type="file" name="import_file" id="import_file" accept=".csv" required>
						</div>
						<div class="file-preview" style="display:none;">
							<span class="dashicons dashicons-text-page"></span>
							<span class="filename"></span>
							<button type="button" class="remove-file">&times;</button>
						</div>
					</div>
				</div>

				<!-- Step 3: Mapping & Context -->
				<div class="import-step" data-step="3">
					<div class="step-header">
						<span class="step-number">03</span>
						<h3><?php esc_html_e( 'Configuration & Target', 'charts' ); ?></h3>
					</div>
					<div class="premium-form-grid" style="padding-top:20px;">
						<div class="form-group">
							<label for="chart_id"><?php esc_html_e( 'Target Chart Profile', 'charts' ); ?></label>
							<select name="chart_id" id="chart_id" class="form-control" required>
								<option value=""><?php esc_html_e( '— Select Chart Definition —', 'charts' ); ?></option>
								<?php foreach ( $definitions as $definition ) : ?>
									<option value="<?php echo (int) $definition->id; ?>" 
											data-type="<?php echo esc_attr( $definition->item_type ); ?>" 
											data-chart-type="<?php echo esc_attr( $definition->chart_type ); ?>"
											data-country="<?php echo esc_attr( $definition->country_code ); ?>"
											data-frequency="<?php echo esc_attr( $definition->frequency ); ?>">
										<?php echo esc_html( $definition->title ); ?> (<?php echo esc_html( ucfirst($definition->item_type) ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<span class="input-helper"><?php esc_html_e( 'Select the dynamic chart record to sync data into.', 'charts' ); ?></span>
						</div>

						<!-- Hidden fields to maintain backward compatibility with the import processor -->
						<input type="hidden" name="chart_type" id="hidden_chart_type" value="top-songs">
						
						<div class="form-group">
							<label for="item_type"><?php esc_html_e( 'Chart Entity Type', 'charts' ); ?></label>
							<select name="item_type" id="item_type" class="form-control" readonly style="background: #fdfdfd; pointer-events: none; opacity: 0.7;">
								<option value="track"><?php esc_html_e( 'Tracks', 'charts' ); ?></option>
								<option value="artist"><?php esc_html_e( 'Artists', 'charts' ); ?></option>
								<option value="video"><?php esc_html_e( 'Videos', 'charts' ); ?></option>
							</select>
						</div>
						<div class="form-group">
							<label for="frequency"><?php esc_html_e( 'Data Frequency', 'charts' ); ?></label>
							<select name="frequency" id="frequency" class="form-control">
								<option value="weekly"><?php esc_html_e( 'Weekly', 'charts' ); ?></option>
								<option value="daily"><?php esc_html_e( 'Daily', 'charts' ); ?></option>
								<option value="monthly"><?php esc_html_e( 'Monthly', 'charts' ); ?></option>
							</select>
						</div>
						<div class="form-group">
							<label for="period_date"><?php esc_html_e( 'Reporting Date', 'charts' ); ?></label>
							<input type="date" name="period_date" id="period_date" value="<?php echo date('Y-m-d'); ?>" class="form-control">
						</div>
						<div class="form-group form-group-full">
							<label for="source_name"><?php esc_html_e( 'Source Label (Optional override)', 'charts' ); ?></label>
							<input type="text" name="source_name" id="source_name" placeholder="<?php esc_html_e( 'e.g. Official Spotify Weekly Egypt', 'charts' ); ?>" class="form-control">
						</div>
					</div>
				</div>

			</div>

			<div class="import-submit-bar">
				<div class="summary-info">
					<p><?php esc_html_e( 'Ready to sync music intelligence data.', 'charts' ); ?></p>
				</div>
				<button type="submit" class="charts-btn-create" id="run-import-btn">
					<span><?php esc_html_e( 'Run Intelligent Sync', 'charts' ); ?></span>
					<div class="spinner-loader" style="display:none;"></div>
				</button>
			</div>
		</form>
	</div>
</div>

<style>
/* Extended styles for the Unified Import Center */
.platform-selector {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20px;
	margin-top: 20px;
}
.platform-card {
	cursor: pointer;
	position: relative;
}
.platform-card input {
	position: absolute;
	opacity: 0;
}
.platform-inner {
	display: flex;
	align-items: center;
	gap: 20px;
	padding: 24px;
	background: var(--charts-bg);
	border: 2px solid var(--charts-border);
	border-radius: 12px;
	transition: all 0.2s ease;
}
.platform-card input:checked + .platform-inner {
	border-color: var(--charts-primary);
	background: rgba(99, 102, 241, 0.05);
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
}
.platform-icon {
	width: 50px;
	height: 50px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #fff;
	box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.platform-icon.spotify { color: #1DB954; }
.platform-icon.youtube { color: #FF0000; }
.platform-icon .dashicons { font-size: 28px; width: 28px; height: 28px; }

.platform-info strong { display: block; font-size: 16px; margin-bottom: 2px; }
.platform-info span { font-size: 12px; color: var(--charts-text-dim); }

.file-upload-zone {
	margin-top: 20px;
	border: 2px dashed var(--charts-border);
	border-radius: 12px;
	padding: 40px;
	text-align: center;
	background: var(--charts-bg);
	transition: all 0.2s ease;
	position: relative;
}
.file-upload-zone.dragover {
	border-color: var(--charts-primary);
	background: rgba(99, 102, 241, 0.05);
}
.upload-content .dashicons { font-size: 40px; width: 40px; height: 40px; color: var(--charts-primary); margin-bottom: 15px; }
.upload-content p { font-weight: 600; font-size: 14px; }
.upload-content input[type="file"] {
	position: absolute;
	top: 0; left: 0; width: 100%; height: 100%;
	opacity: 0;
	cursor: pointer;
}

.file-preview {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12px;
	background: var(--charts-primary);
	color: white;
	padding: 12px 20px;
	border-radius: 99px;
	font-weight: 800;
	font-size: 13px;
	display: inline-flex;
}
.file-preview .remove-file {
	background: rgba(0,0,0,0.2);
	border: none;
	color: white;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	line-height: 1;
	cursor: pointer;
}

.import-submit-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding-top: 40px;
	margin-top: 40px;
	border-top: 1px solid var(--charts-border);
}
.summary-info { font-size: 14px; color: var(--charts-text-dim); font-weight: 500; }

.spinner-loader {
	width: 20px;
	height: 20px;
	border: 3px solid rgba(255,255,255,0.3);
	border-top-color: #fff;
	border-radius: 50%;
	animation: spin 1s linear infinite;
	margin-left: 10px;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
jQuery(document).ready(function($) {
	const fileInput = $('#import_file');
	const dropZone = $('#drop-zone');
	const filePreview = $('.file-preview');
	const uploadContent = $('.upload-content');
	const removeBtn = $('.remove-file');

	// Synchronize target chart metadata
	$('#chart_id').on('change', function() {
		const $opt = $(this).find('option:selected');
		if ($opt.val()) {
			$('#item_type').val($opt.data('type'));
			$('#country').val($opt.data('country'));
			$('#frequency').val($opt.data('frequency'));
			$('#hidden_chart_type').val($opt.data('chart-type'));
		}
	});

	fileInput.on('change', function(e) {
		const file = e.target.files[0];
		if (file) {
			uploadContent.hide();
			filePreview.css('display', 'inline-flex');
			filePreview.find('.filename').text(file.name);
		}
	});

	removeBtn.on('click', function() {
		fileInput.val('');
		filePreview.hide();
		uploadContent.show();
	});

	dropZone.on('dragover', function(e) {
		e.preventDefault();
		$(this).addClass('dragover');
	});
	dropZone.on('dragleave drop', function(e) {
		e.preventDefault();
		$(this).removeClass('dragover');
	});

	$('#unified-import-form').on('submit', function() {
		const $btn = $('#run-import-btn');
		$btn.addClass('processing').find('span').text('Processing Data...');
		$btn.find('.spinner-loader').show();
	});
});
</script>
