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

	
	<!-- 0. Sync Result Summary -->
	<?php if ( isset( $_GET['sync_complete'] ) && isset( $_GET['run_id'] ) ) : 
		global $wpdb;
		$run_id = intval( $_GET['run_id'] );
		$run = $wpdb->get_row( $wpdb->prepare( "
			SELECT r.*, s.source_name, s.platform, d.slug as chart_slug
			FROM {$wpdb->prefix}charts_import_runs r
			JOIN {$wpdb->prefix}charts_sources s ON s.id = r.source_id
			LEFT JOIN {$wpdb->prefix}charts_definitions d ON (d.chart_type = s.chart_type AND d.country_code = s.country_code)
			WHERE r.id = %d
		", $run_id ) );
		
		if ( $run ) :
			$chart_url = !empty($run->chart_slug) ? home_url('/charts/' . $run->chart_slug . '/') : admin_url('admin.php?page=charts-definitions');
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

	<div class="premium-form-card import-journey-wrap">
		<form method="post" action="" enctype="multipart/form-data" id="unified-import-form">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="unified_import">

			<div class="import-steps-container">
				
				<!-- Step 1: Territory & Platform -->
				<div class="import-stage active" data-step="1">
					<div class="stage-header">
						<div class="stage-number">01</div>
						<div class="stage-title">
							<h3><?php esc_html_e( 'Territory & Source', 'charts' ); ?></h3>
							<p><?php _e( 'Define the geographic scope and data provider.', 'charts' ); ?></p>
						</div>
					</div>

					<div class="stage-body">
						<!-- Market Selection -->
						<?php 
						$markets = get_option('charts_markets', []);
						if (empty($markets)) : ?>
							<div class="market-warning">
								<span class="dashicons dashicons-warning"></span>
								<span>No Markets defined. <a href="<?php echo admin_url('admin.php?page=charts-settings#markets'); ?>">Configure Territories &rarr;</a></span>
							</div>
						<?php else : ?>
							<div class="market-selector-wrap">
								<label class="premium-label"><?php _e( 'Target Market / Region', 'charts' ); ?></label>
								<div class="market-dropdown-custom">
									<select name="country" class="premium-select" required>
										<option value=""><?php _e( '— Select Market —', 'charts' ); ?></option>
										<?php foreach ($markets as $m) : ?>
											<option value="<?php echo esc_attr(strtolower($m['code'])); ?>">
												🌍 <?php echo esc_html($m['name']); ?> (<?php echo esc_html(strtoupper($m['code'])); ?>)
											</option>
										<?php endforeach; ?>
									</select>
									<div class="select-affordance">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<div class="platform-grid">
							<label class="platform-option">
								<input type="radio" name="platform" value="spotify" <?php checked($pre_source, 'spotify'); ?>>
								<div class="platform-box">
									<div class="platform-icon sp">
										<span class="dashicons dashicons-spotify"></span>
									</div>
									<div class="platform-text">
										<strong>Spotify</strong>
										<span>CSV Intelligence</span>
									</div>
									<div class="platform-check">
										<span class="dashicons dashicons-yes-alt"></span>
									</div>
								</div>
							</label>
							<label class="platform-option">
								<input type="radio" name="platform" value="youtube" <?php checked($pre_source, 'youtube'); ?>>
								<div class="platform-box">
									<div class="platform-icon yt">
										<span class="dashicons dashicons-video-alt3"></span>
									</div>
									<div class="platform-text">
										<strong>YouTube</strong>
										<span>Channel & Video CSV</span>
									</div>
									<div class="platform-check">
										<span class="dashicons dashicons-yes-alt"></span>
									</div>
								</div>
							</label>
						</div>
					</div>
				</div>

				<!-- Step 2: File Upload -->
				<div class="import-stage" data-step="2">
					<div class="stage-header">
						<div class="stage-number">02</div>
						<div class="stage-title">
							<h3><?php esc_html_e( 'Upload Chart Data', 'charts' ); ?></h3>
							<p><?php _e( 'Provide the raw export file for intelligence parsing.', 'charts' ); ?></p>
						</div>
					</div>
					<div class="stage-body">
						<div class="file-nexus-zone" id="drop-zone">
							<div class="nexus-idle">
								<div class="nexus-icon">
									<span class="dashicons dashicons-upload"></span>
								</div>
								<h4><?php esc_html_e( 'Drop CSV File Here', 'charts' ); ?></h4>
								<p><?php esc_html_e( 'or click to browse your computer', 'charts' ); ?></p>
								<div class="nexus-limit"><?php _e( 'Supports .csv files only', 'charts' ); ?></div>
							</div>
							<div class="nexus-staged" style="display:none;">
								<div class="nexus-file-info">
									<div class="file-icon">
										<span class="dashicons dashicons-media-spreadsheet"></span>
									</div>
									<div class="file-details">
										<span class="file-name">filename.csv</span>
										<span class="file-meta">0 KB • application/csv</span>
									</div>
								</div>
								<button type="button" class="nexus-remove" id="remove-file">
									<span class="dashicons dashicons-no-alt"></span>
								</button>
							</div>
							<input type="file" name="import_file" id="import_file" accept=".csv" class="nexus-input">
						</div>
					</div>
				</div>

				<!-- Step 3: Mapping & Context -->
				<div class="import-stage" data-step="3">
					<div class="stage-header">
						<div class="stage-number">03</div>
						<div class="stage-title">
							<h3><?php esc_html_e( 'Configuration & Target', 'charts' ); ?></h3>
							<p><?php _e( 'Map the incoming data to the correct library collection.', 'charts' ); ?></p>
						</div>
					</div>
					<div class="stage-body">
						<div class="config-grid">
							<div class="form-group full-width">
								<label class="premium-label"><?php esc_html_e( 'Target Chart Profile', 'charts' ); ?></label>
								<select name="chart_id" id="chart_id" class="premium-select" required>
									<option value=""><?php esc_html_e( '— Select Chart Definition —', 'charts' ); ?></option>
									<?php foreach ( $definitions as $definition ) : 
										$type_label = ($definition->item_type === 'video') ? 'Clips' : ucfirst($definition->item_type) . 's';
									?>
										<option value="<?php echo (int) $definition->id; ?>" 
												data-type="<?php echo esc_attr( $definition->item_type ?: 'track' ); ?>" 
												data-chart-type="<?php echo esc_attr( $definition->chart_type ?: 'top-songs' ); ?>"
												data-country="<?php echo esc_attr( $definition->country_code ?: 'eg' ); ?>"
												data-frequency="<?php echo esc_attr( $definition->frequency ?: 'weekly' ); ?>">
											<?php echo esc_html( $definition->title ); ?> (Syncing to <?php echo esc_html( $type_label ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="form-group half">
								<label class="premium-label"><?php esc_html_e( 'Entity Role', 'charts' ); ?></label>
								<select name="item_type" id="item_type" class="premium-select">
									<option value="track"><?php esc_html_e( 'Tracks (Audio)', 'charts' ); ?></option>
									<option value="artist"><?php esc_html_e( 'Artists', 'charts' ); ?></option>
									<option value="video"><?php esc_html_e( 'Clips & Videos', 'charts' ); ?></option>
								</select>
							</div>
							<div class="form-group half">
								<label class="premium-label"><?php esc_html_e( 'Reporting Window', 'charts' ); ?></label>
								<select name="frequency" id="frequency" class="premium-select">
									<option value="weekly"><?php esc_html_e( 'Weekly', 'charts' ); ?></option>
									<option value="daily"><?php esc_html_e( 'Daily', 'charts' ); ?></option>
									<option value="monthly"><?php esc_html_e( 'Monthly', 'charts' ); ?></option>
								</select>
							</div>
							<div class="form-group full-width">
								<label class="premium-label"><?php esc_html_e( 'Base Sync Date', 'charts' ); ?></label>
								<input type="date" name="period_date" id="period_date" value="<?php echo date('Y-m-d'); ?>" class="premium-input">
							</div>
							
							<input type="hidden" name="chart_type" id="hidden_chart_type" value="top-songs">
						</div>
					</div>
				</div>

				<!-- Step 4: Run -->
				<div class="import-stage" data-step="4">
					<div class="stage-header">
						<div class="stage-number">04</div>
						<div class="stage-title">
							<h3><?php esc_html_e( 'Run Sync', 'charts' ); ?></h3>
							<p><?php _e( 'Execute the intelligence pipeline.', 'charts' ); ?></p>
						</div>
					</div>
					<div class="stage-body">
						<div class="sync-action-box">
							<div class="sync-readiness">
								<p id="readiness-msg"><?php _e( 'Please complete all steps to begin sync.', 'charts' ); ?></p>
							</div>
							<button type="submit" class="charts-btn-create large-cta" id="run-import-btn" disabled>
								<span><?php esc_html_e( 'Execute Intelligent Sync', 'charts' ); ?></span>
								<div class="spinner-loader" style="display:none;"></div>
							</button>
						</div>
					</div>
				</div>

			</div>
		</form>
	</div>


</div>


<style>
/* Modern Import Journey Styles */
/* Results Card */
.result-summary-card {
	background: #fff;
	border: 1px solid var(--charts-border);
	border-radius: 20px;
	margin-bottom: 40px;
	padding: 32px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.result-summary-card.is-success { border-top: 4px solid var(--charts-success); }
.result-summary-card.is-error { border-top: 4px solid var(--charts-error); }

.result-header {
	display: flex;
	align-items: center;
	gap: 24px;
	margin-bottom: 32px;
	position: relative;
}
.result-badge {
	width: 56px;
	height: 56px;
	border-radius: 16px;
	background: #f0fdf4;
	color: #10b981;
	display: flex;
	align-items: center;
	justify-content: center;
}
.is-error .result-badge { background: #fef2f2; color: #ef4444; }
.result-badge .dashicons { font-size: 28px; width: 28px; height: 28px; }

.result-meta { flex-grow: 1; }
.result-meta h2 { margin: 0; font-size: 22px; font-weight: 850; letter-spacing: -0.02em; }
.result-meta p { margin: 4px 0 0; font-size: 14px; color: var(--charts-text-dim); font-weight: 500; }

.result-actions { 
	display: flex; 
	gap: 12px; 
}

.result-stats-grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 24px;
	padding-top: 24px;
	border-top: 1px solid var(--charts-border);
}

.res-stat { display: flex; flex-direction: column; gap: 4px; }
.stat-val { font-size: 24px; font-weight: 900; color: var(--charts-primary); }
.stat-lab { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--charts-text-dim); }

.result-diagnosis {
	margin-top: 24px;
	padding: 16px 20px;
	background: #f8fafc;
	border-radius: 12px;
	font-size: 13px;
}
.result-diagnosis strong { display: block; margin-bottom: 4px; font-weight: 800; }
.result-diagnosis code { background: transparent; padding: 0; color: #475569; }

.import-journey-wrap {
	max-width: 900px;
	margin: 0 auto;
	padding: 0;
	background: transparent;
	box-shadow: none;
}

.import-steps-container {
	display: flex;
	flex-direction: column;
	gap: 24px;
}

.import-stage {
	background: #fff;
	border-radius: 20px;
	border: 1px solid var(--charts-border);
	overflow: hidden;
	transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
	opacity: 0.8;
}

.import-stage.active {
	opacity: 1;
	box-shadow: 0 10px 40px rgba(0,0,0,0.04);
	border-color: var(--charts-primary);
}

.stage-header {
	padding: 24px 32px;
	display: flex;
	align-items: center;
	gap: 20px;
	background: #fafafa;
	border-bottom: 1px solid var(--charts-border);
}

.import-stage.active .stage-header {
	background: rgba(99, 102, 241, 0.03);
}

.stage-number {
	width: 44px;
	height: 44px;
	background: #fff;
	border: 1px solid var(--charts-border);
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 16px;
	font-weight: 900;
	color: var(--charts-text-dim);
	transition: all 0.3s ease;
}

.import-stage.active .stage-number {
	background: var(--charts-primary);
	border-color: var(--charts-primary);
	color: #fff;
	transform: scale(1.1);
}

.stage-title h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 800;
	letter-spacing: -0.02em;
}

.stage-title p {
	margin: 2px 0 0;
	font-size: 13px;
	color: var(--charts-text-dim);
	font-weight: 500;
}

.stage-body {
	padding: 32px;
}

/* Platform Alignment */
.platform-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}

.platform-option {
	cursor: pointer;
}

.platform-option input {
	position: absolute;
	opacity: 0;
}

.platform-box {
	background: #fff;
	border: 2px solid var(--charts-border);
	border-radius: 16px;
	padding: 24px;
	display: flex;
	align-items: center;
	gap: 20px;
	position: relative;
	transition: all 0.3s ease;
}

.platform-option input:checked + .platform-box {
	border-color: var(--charts-primary);
	background: rgba(99, 102, 241, 0.05);
	transform: translateY(-2px);
}

.platform-icon {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}

.platform-icon.sp { color: #1DB954; background: rgba(29, 185, 84, 0.1); }
.platform-icon.yt { color: #FF0000; background: rgba(255, 0, 0, 0.1); }
.platform-icon .dashicons { font-size: 24px; width: 24px; height: 24px; }

.platform-text strong { display: block; font-size: 16px; }
.platform-text span { font-size: 12px; color: var(--charts-text-dim); }

.platform-check {
	position: absolute;
	top: 15px;
	right: 15px;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	background: var(--charts-primary);
	color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	opacity: 0;
	transform: scale(0);
	transition: all 0.3s ease;
}

.platform-option input:checked + .platform-box .platform-check {
	opacity: 1;
	transform: scale(1);
}

/* File Nexus (Upload Area) */
.file-nexus-zone {
	border: 3px dashed var(--charts-border);
	border-radius: 20px;
	padding: 50px 20px;
	text-align: center;
	background: #fafafa;
	transition: all 0.3s ease;
	position: relative;
	cursor: pointer;
}

.file-nexus-zone:hover, .file-nexus-zone.is-dragover {
	border-color: var(--charts-primary);
	background: rgba(99, 102, 241, 0.04);
}

.nexus-icon {
	font-size: 40px;
	color: var(--charts-primary);
	margin-bottom: 16px;
}

.nexus-idle h4 { margin: 0 0 4px; font-size: 16px; font-weight: 800; }
.nexus-idle p { margin: 0; color: var(--charts-text-dim); font-size: 14px; }
.nexus-limit { margin-top: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; }

.nexus-staged {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 16px 24px;
	background: #fff;
	border: 1px solid var(--charts-border);
	border-radius: 12px;
	max-width: 400px;
	margin: 0 auto;
	box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.nexus-file-info { display: flex; align-items: center; gap: 16px; text-align: left; }
.file-icon { color: var(--charts-primary); }
.file-details .file-name { display: block; font-weight: 800; font-size: 14px; margin-bottom: 2px; }
.file-details .file-meta { font-size: 11px; color: var(--charts-text-dim); font-weight: 600; }

.nexus-remove {
	background: #fef2f2;
	color: #ef4444;
	border: none;
	width: 28px;
	height: 28px;
	border-radius: 50%;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
}

.nexus-remove:hover { background: #ef4444; color: #fff; }

.nexus-input {
	position: absolute;
	top: 0; left: 0; width: 100%; height: 100%;
	opacity: 0; cursor: pointer;
}

/* Sync Action Box */
.sync-action-box {
	text-align: center;
	padding: 20px;
	background: var(--charts-bg);
	border: 1px solid var(--charts-border);
	border-radius: 16px;
}

.sync-readiness {
	margin-bottom: 20px;
	font-size: 14px;
	font-weight: 600;
	color: var(--charts-text-dim);
}

.large-cta {
	height: 60px;
	padding: 0 60px;
	font-size: 16px;
	font-weight: 900;
	letter-spacing: -0.01em;
	border-radius: 30px;
	box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
}

.large-cta:disabled {
	opacity: 0.4;
	filter: grayscale(1);
	box-shadow: none;
	cursor: not-allowed;
}

/* Config Grid */
.config-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 24px;
}

.full-width { grid-column: span 2; }
.half { grid-column: span 1; }

.premium-label {
	display: block;
	font-size: 11px;
	font-weight: 800;
	text-transform: uppercase;
	letter-spacing: 0.1em;
	color: #64748b;
	margin-bottom: 8px;
}

.premium-select, .premium-input {
	width: 100%;
	height: 48px;
	border-radius: 10px;
	border: 1px solid var(--charts-border);
	padding: 0 16px;
	font-size: 14px;
	font-weight: 600;
	background: #fff;
}

.market-dropdown-custom { position: relative; }
.select-affordance {
	position: absolute;
	right: 15px;
	top: 50%;
	transform: translateY(-50%);
	pointer-events: none;
	color: #94a3b8;
}

.market-warning {
	padding: 16px 20px;
	background: #fffcf0;
	border: 1px solid #ffecb3;
	border-radius: 12px;
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 24px;
	font-size: 13px;
	font-weight: 600;
	color: #92400e;
}

.market-warning .dashicons { color: #d97706; }

</style>


</div>
<?php
// End of file. Logic unified in admin.js
