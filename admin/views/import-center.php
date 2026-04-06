<?php
/**
 * Unified Import Center View
 */
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( false );
$pre_source  = $_GET['source'] ?? 'spotify';
?>
<div class="wrap charts-admin-wrap premium-light">
<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Data Intelligence Import', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Unified workflow for Spotify and YouTube chart synchronization.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-imports' ); ?>" class="charts-btn-back">
				<span class="dashicons dashicons-backup" style="margin-right:8px;"></span>
				<?php _e( 'View Import History', 'charts' ); ?>
			</a>
		</div>
	</header>

	<?php settings_errors( 'charts' ); ?>

	<!-- 0. Sync Result Summary -->
	<?php if ( isset( $_GET['sync_complete'] ) && isset( $_GET['run_id'] ) ) : 
		global $wpdb;
		$run_id = intval( $_GET['run_id'] );
		$run = $wpdb->get_row( $wpdb->prepare( "
			SELECT r.*, s.source_name, s.platform
			FROM {$wpdb->prefix}charts_import_runs r
			JOIN {$wpdb->prefix}charts_sources s ON s.id = r.source_id
			WHERE r.id = %d
		", $run_id ) );
		
		if ( $run ) :
			$chart_url = ( $run->platform === 'youtube' ) ? home_url('/charts/') : home_url('/charts/spotify/');
	?>
		<?php 
			$is_success = ($run->status === 'completed' && ($run->matched_items > 0 || $run->created_items > 0));
			$result_class = $is_success ? 'is-success' : 'is-error';
		?>
		<div class="result-summary-card <?php echo $result_class; ?>">
			<div class="result-header">
				<div class="result-badge">
					<span class="dashicons dashicons-<?php echo $is_success ? 'saved' : 'warning'; ?>"></span>
				</div>
				<div class="result-meta">
					<h2><?php echo $is_success ? __( 'Sync Successful', 'charts' ) : __( 'Sync Attention Required', 'charts' ); ?></h2>
					<p><?php echo esc_html( $run->source_name ); ?> • <?php echo date('M j, H:i', strtotime($run->started_at)); ?></p>
				</div>
				<div class="result-actions">
					<a href="<?php echo esc_url($chart_url); ?>" target="_blank" class="charts-btn-create small">View Charts</a>
					<a href="<?php echo admin_url('admin.php?page=charts-imports'); ?>" class="charts-btn-back">History</a>
				</div>
			</div>
			
			<div class="result-stats-grid">
				<div class="res-stat">
					<span class="stat-val"><?php echo number_format($run->parsed_rows); ?></span>
					<span class="stat-lab">Total Rows</span>
				</div>
				<div class="res-stat">
					<span class="stat-val"><?php echo number_format($run->matched_items); ?></span>
					<span class="stat-lab">Matched</span>
				</div>
				<div class="res-stat">
					<span class="stat-val" style="color:var(--charts-primary);"><?php echo number_format($run->created_items); ?></span>
					<span class="stat-lab">New Created</span>
				</div>
				<div class="res-stat">
					<span class="stat-val" style="color:<?php echo $run->status === 'completed' ? '#10b981' : '#ef4444'; ?>;">
						<?php 
						$efficiency = ($run->parsed_rows > 0) ? round(($run->matched_items / $run->parsed_rows) * 100) : 0;
						echo $efficiency . '%';
						?>
					</span>
					<span class="stat-lab">Intelligence Match</span>
				</div>
			</div>

			<?php if ( ! empty($run->error_message) ) : ?>
				<div class="result-diagnosis">
					<strong>Result Diagnosis:</strong>
					<code><?php echo esc_html($run->error_message); ?></code>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; endif; ?>

	<div class="premium-form-card">
		<form method="post" action="" enctype="multipart/form-data" id="unified-import-form">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="unified_import">

			<div class="import-steps">
				
				<!-- Step 1: Source & Platform -->
				<div class="import-step" data-step="1">
					<div class="step-header">
						<span class="step-number">01</span>
						<h3><?php esc_html_e( 'Territory & Source', 'charts' ); ?></h3>
					</div>

					<!-- Market Selection -->
					<?php 
					$markets = get_option('charts_markets', []);
					if (empty($markets)) : ?>
						<div class="market-warning" style="padding: 20px; background: #fffcf0; border: 1px solid #ffecb3; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
							<span class="dashicons dashicons-warning" style="color:#d97706;"></span>
							<span style="font-size:13px; font-weight:600; color:#92400e;">No Markets defined. <a href="<?php echo admin_url('admin.php?page=charts-settings#markets'); ?>">Configure Territories &rarr;</a></span>
						</div>
					<?php else : ?>
						<div class="market-selector-wrap" style="margin-bottom: 32px;">
							<label style="display:block; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:12px;">Target Market / Region</label>
							<div class="market-dropdown-custom" style="position:relative;">
								<select name="country" class="premium-select" required style="width:100%; height:56px; padding:0 50px 0 20px; appearance:none; border-radius:12px; border:1px solid #e2e8f0; background:#fff; font-size:15px; font-weight:700; cursor:pointer;">
									<?php foreach ($markets as $m) : ?>
										<option value="<?php echo esc_attr(strtolower($m['code'])); ?>">
											🌍 <?php echo esc_html($m['name']); ?> (<?php echo esc_html(strtoupper($m['code'])); ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<div class="select-affordance" style="position:absolute; right:20px; top:50%; transform:translateY(-50%); pointer-events:none; color:#94a3b8;">
									<span class="dashicons dashicons-arrow-down-alt2"></span>
								</div>
								<div class="select-icon" style="position:absolute; left:20px; top:50%; transform:translateY(-50%); pointer-events:none; display:none;">
									<span class="dashicons dashicons-location" style="color:var(--charts-primary);"></span>
								</div>
							</div>
						</div>
					<?php endif; ?>

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
								<?php foreach ( $definitions as $definition ) : 
									$type_label = ($definition->item_type === 'video') ? 'Clips' : ucfirst($definition->item_type) . 's';
								?>
									<option value="<?php echo (int) $definition->id; ?>" 
											data-type="<?php echo esc_attr( $definition->item_type ?: 'track' ); ?>" 
											data-chart-type="<?php echo esc_attr( $definition->chart_type ?: 'top-songs' ); ?>"
											data-country="<?php echo esc_attr( $definition->country_code ?: 'eg' ); ?>"
											data-frequency="<?php echo esc_attr( $definition->frequency ?: 'weekly' ); ?>">
										<?php echo esc_html( $definition->title ); ?> — Target: <?php echo esc_html( $type_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="input-helper"><?php esc_html_e( 'Select the dynamic chart record to sync data into.', 'charts' ); ?></span>
						</div>

						<!-- Hidden fields to maintain backward compatibility with the import processor -->
						<input type="hidden" name="chart_type" id="hidden_chart_type" value="top-songs">
						<input type="hidden" name="country" id="country" value="eg">
						
						<div class="form-group">
							<label for="item_type"><?php esc_html_e( 'Chart Entity Type', 'charts' ); ?></label>
							<select name="item_type" id="item_type" class="form-control">
								<option value="track"><?php esc_html_e( 'Tracks (Audio)', 'charts' ); ?></option>
								<option value="artist"><?php esc_html_e( 'Artists', 'charts' ); ?></option>
								<option value="video"><?php esc_html_e( 'Clips & Videos', 'charts' ); ?></option>
							</select>
							<span class="input-helper"><?php esc_html_e( 'The underlying data model for this sync.', 'charts' ); ?></span>
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
						<!-- Retired Source Label Override field per UI cleanup request -->
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

	<!-- New: Direct Artist by URL Import -->
	<div class="premium-form-card" style="margin-top: 40px;">
		<header class="step-header">
			<span class="step-number" style="background:var(--charts-primary); color:#fff; border-radius:50%; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; margin-right:10px;">URL</span>
			<h3><?php esc_html_e( 'Direct Artist Intelligence Import', 'charts' ); ?></h3>
		</header>
		
		<form method="post" action="" style="margin-top:20px;">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="import_artist_url">
			
			</div>
		</form>
	</div>

	<!-- New: Direct Location by URL Import -->
	<div class="premium-form-card" style="margin-top: 40px;">
		<header class="step-header">
			<span class="step-number" style="background:#ef4444; color:#fff; border-radius:50%; width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; font-size:10px; margin-right:10px;">LOC</span>
			<h3><?php esc_html_e( 'Location Intelligence Import', 'charts' ); ?></h3>
		</header>
		
		<form method="post" action="" style="margin-top:20px;">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="import_location_url">
			
			<div class="form-group" style="margin-bottom:20px;">
				<label for="location_url" style="display:block; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:12px;"><?php esc_html_e( 'YouTube Charts Location URL', 'charts' ); ?></label>
				<div style="display:flex; gap:12px;">
					<input type="url" name="location_url" id="location_url" class="premium-input" placeholder="https://charts.youtube.com/location/..." required style="flex-grow:1; height:56px; border-radius:12px; border:1px solid #e2e8f0; padding:0 20px; font-size:15px; background:#f8fafc;">
					<button type="submit" class="charts-btn-create" style="height:56px; padding:0 30px; border-radius:12px; font-weight:800; background:#ef4444; border-color:#ef4444;"><?php esc_html_e( 'Import Location', 'charts' ); ?></button>
				</div>
				<span class="input-helper" style="display:block; margin-top:8px; font-size:12px; color:#64748b;"><?php esc_html_e( 'Paste a YouTube Charts location URL (e.g. City or Region) to scrape metadata and associated chart rankings.', 'charts' ); ?></span>
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

/* Result Card Styling */
.result-summary-card {
	background: #fff;
	border-radius: 16px;
	border: 1px solid var(--charts-border);
	padding: 30px;
	margin-bottom: 40px;
	box-shadow: 0 10px 30px rgba(0,0,0,0.05);
	animation: slideIn 0.5s ease;
}
@keyframes slideIn { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }

.result-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
.result-badge {
	width: 48px; height: 48px; border-radius: 12px;
	display: flex; align-items: center; justify-content: center;
	background: #ecfdf5; color: #10b981;
}
.is-error .result-badge { background: #fef2f2; color: #ef4444; }
.result-badge .dashicons { font-size: 24px; width: 24px; height: 24px; }
.result-meta h2 { margin: 0; font-size: 20px; font-weight: 950; letter-spacing: -0.02em; }
.result-meta p { margin: 2px 0 0; color: var(--charts-text-dim); font-size: 13px; font-weight:600; }
.result-actions { margin-left: auto; display: flex; gap: 10px; }

.result-stats-grid {
	display: grid; grid-template-columns: repeat(4, 1fr);
	gap: 1px; background: var(--charts-border);
	border-radius: 12px; overflow: hidden; border: 1px solid var(--charts-border);
}
.res-stat { background: #fff; padding: 20px; text-align: center; }
.stat-val { display: block; font-size: 24px; font-weight: 900; letter-spacing: -0.04em; }
.stat-lab { font-size: 11px; font-weight: 700; color: var(--charts-text-dim); text-transform: uppercase; margin-top: 4px; }

.result-diagnosis {
	margin-top: 24px; padding: 15px 20px;
	background: var(--charts-bg); border-radius: 8px;
	font-size: 12px; color: var(--charts-text-dim);
}
.result-diagnosis code { background: none; color: var(--charts-primary); font-weight: 800; margin-left: 8px; }
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
