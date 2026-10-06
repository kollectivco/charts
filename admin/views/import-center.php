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
			<p class="charts-admin-subtitle"><?php _e( 'Unified workflow for CSV imports and live chart providers.', 'charts' ); ?></p>
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
			LEFT JOIN {$wpdb->prefix}charts_definitions d ON ((d.chart_type = s.chart_type AND d.country_code = s.country_code) OR s.chart_type = CONCAT('cid-', d.id))
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
						if (empty($markets) || !is_array($markets)) {
							$markets = [
								['code' => 'eg', 'name' => 'Egypt'],
								['code' => 'sa', 'name' => 'Saudi Arabia'],
								['code' => 'ae', 'name' => 'UAE'],
								['code' => 'kw', 'name' => 'Kuwait'],
								['code' => 'qa', 'name' => 'Qatar'],
								['code' => 'global', 'name' => 'Global']
							];
						}
						?>
						<div class="market-selector-wrap">
							<label class="premium-label"><?php _e( 'Target Market / Region', 'charts' ); ?></label>
							<div class="market-dropdown-custom">
								<select name="country" id="import-country" class="premium-select" required>
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

						<div class="platform-grid">
							<label class="platform-option">
								<input type="radio" name="platform" value="spotify" <?php checked($pre_source, 'spotify'); ?>>
								<div class="platform-box">
									<div class="platform-icon sp">
										<span class="dashicons dashicons-spotify"></span>
									</div>
									<div class="platform-text">
										<strong>Spotify</strong>
										<span>Live Charts, Playlists & CSV</span>
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
										<span>Live Charts & CSV</span>
									</div>
									<div class="platform-check">
										<span class="dashicons dashicons-yes-alt"></span>
									</div>
								</div>
							</label>
							<label class="platform-option">
								<input type="radio" name="platform" value="kontent" <?php checked($pre_source, 'kontent'); ?>>
								<div class="platform-box">
									<div class="platform-icon" style="background:#5B21B6; color:#fff;">
										<span class="dashicons dashicons-analytics"></span>
									</div>
									<div class="platform-text">
										<strong>Kontent</strong>
										<span>Internal Metrics CSV</span>
									</div>
									<div class="platform-check">
										<span class="dashicons dashicons-yes-alt"></span>
									</div>
								</div>
							</label>
							<label class="platform-option">
								<input type="radio" name="platform" value="soundcharts" <?php checked($pre_source, 'soundcharts'); ?>>
								<div class="platform-box">
									<div class="platform-icon soundcharts-icon"><span class="dashicons dashicons-chart-line"></span></div>
									<div class="platform-text"><strong>Soundcharts</strong><span>Live API Import</span></div>
									<div class="platform-check"><span class="dashicons dashicons-yes-alt"></span></div>
								</div>
							</label>
							<label class="platform-option">
								<input type="radio" name="platform" value="billboard" <?php checked($pre_source, 'billboard'); ?>>
								<div class="platform-box">
									<div class="platform-icon" style="background:#000; color:#fff;">
										<span style="font-weight:900; font-family:serif; font-size:24px;">B</span>
									</div>
									<div class="platform-text">
										<strong>Billboard</strong>
										<span>Live API & CSV</span>
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
							<h3 class="import-source-step-title"><?php esc_html_e( 'Upload Chart Data', 'charts' ); ?></h3>
							<p class="import-source-step-description"><?php _e( 'Provide the raw export file for intelligence parsing.', 'charts' ); ?></p>
						</div>
					</div>
					<div class="stage-body">
						<div class="soundcharts-import-controls" style="display:none;">
							<div class="soundcharts-controls-grid">
								<div class="form-group">
									<label class="premium-label" for="soundcharts_entity_type"><?php esc_html_e( 'Content Type', 'charts' ); ?></label>
									<select name="soundcharts_entity_type" id="soundcharts_entity_type" class="premium-select">
										<option value="song"><?php esc_html_e( 'Songs', 'charts' ); ?></option>
										<option value="album"><?php esc_html_e( 'Albums', 'charts' ); ?></option>
									</select>
								</div>
								<div class="form-group">
									<label class="premium-label" for="soundcharts_platform"><?php esc_html_e( 'Soundcharts Platform', 'charts' ); ?></label>
									<select name="soundcharts_platform" id="soundcharts_platform" class="premium-select" disabled>
										<option value=""><?php esc_html_e( 'Select a market first', 'charts' ); ?></option>
									</select>
								</div>
							</div>
							<div class="form-group soundcharts-chart-picker">
								<label class="premium-label" for="soundcharts_chart_slug"><?php esc_html_e( 'Chart to Import', 'charts' ); ?></label>
								<div class="soundcharts-chart-picker-row">
									<select name="soundcharts_chart_slug" id="soundcharts_chart_slug" class="premium-select" disabled>
										<option value=""><?php esc_html_e( 'Choose a platform first', 'charts' ); ?></option>
									</select>
									<button type="button" class="charts-btn-back soundcharts-load-charts" disabled><?php esc_html_e( 'Load Charts', 'charts' ); ?></button>
								</div>
								<p class="soundcharts-catalog-status" aria-live="polite"><?php esc_html_e( 'Charts are loaded directly from Soundcharts.', 'charts' ); ?></p>
							</div>
						</div>
						
						<div class="billboard-import-controls" style="display:none;">
							<?php $bb_catalog = \Charts\Services\BillboardService::get_chart_catalog(); ?>
							<div class="form-group">
								<label class="premium-label" for="billboard_chart_id"><?php esc_html_e( 'Billboard Arabia Chart', 'charts' ); ?></label>
								<select name="billboard_chart_id" id="billboard_chart_id" class="premium-select">
									<option value=""><?php esc_html_e( 'Select a chart...', 'charts' ); ?></option>
									<?php foreach ( $bb_catalog as $bb_id => $bb_chart ) : ?>
										<option value="<?php echo (int) $bb_id; ?>" data-item-type="<?php echo esc_attr( $bb_chart['item_type'] ?? 'track' ); ?>"><?php echo esc_html( $bb_chart['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group soundcharts-chart-picker">
								<label class="premium-label" for="billboard_week_id"><?php esc_html_e( 'Billboard Week', 'charts' ); ?></label>
								<div class="soundcharts-chart-picker-row">
									<select name="billboard_week_id" id="billboard_week_id" class="premium-select" disabled>
										<option value=""><?php esc_html_e( 'Select a chart first', 'charts' ); ?></option>
									</select>
								</div>
								<p class="billboard-catalog-status" aria-live="polite" style="margin-top:8px;font-size:12px;color:#64748b;"><?php esc_html_e( 'Fetching directly from Billboard Arabia API.', 'charts' ); ?></p>
							</div>
						</div>

						<div class="youtube-import-controls" style="display:none;">
							<?php $yt_catalog = \Charts\Services\YouTubeChartsService::get_chart_catalog(); ?>
							<div class="form-group">
								<label class="premium-label" for="youtube_chart_key"><?php esc_html_e( 'Official YouTube Chart (مصر)', 'charts' ); ?></label>
								<select name="youtube_chart_key" id="youtube_chart_key" class="premium-select">
									<?php foreach ( $yt_catalog as $yt_k => $yt_c ) : ?>
										<option value="<?php echo esc_attr( $yt_k ); ?>" data-country="<?php echo esc_attr( $yt_c['country'] ?? 'eg' ); ?>" data-item-type="<?php echo esc_attr( $yt_c['item_type'] ); ?>" data-frequency="<?php echo esc_attr( $yt_c['frequency'] ); ?>" data-target-slug="<?php echo esc_attr( $yt_c['target_slug'] ); ?>" data-alt-slugs="<?php echo esc_attr( implode( ',', $yt_c['alt_slugs'] ?? array() ) ); ?>" data-url="<?php echo esc_attr( $yt_c['url'] ); ?>">
											<?php echo esc_html( $yt_c['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<div class="youtube-catalog-status" aria-live="polite" style="margin-top:8px;font-size:12px;display:flex;align-items:center;gap:8px;">
									<span class="youtube-market-badge" style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;">
										🇪🇬 مصر (EG Market)
									</span>
									<span id="youtube-selected-link" style="color:#ef4444;font-weight:600;word-break:break-all;">
										<?php echo esc_html( $yt_catalog['top-videos-daily']['url'] ); ?>
									</span>
								</div>
								<p style="margin-top:6px;font-size:11px;color:#64748b;">
									<?php esc_html_e( 'Sync directly from official YouTube Charts Egypt without uploading a CSV file.', 'charts' ); ?>
								</p>
							</div>
						</div>

						<div class="spotify-import-controls" style="display:none;">
							<?php 
							$spotify_catalog = \Charts\Services\SpotifySyncService::get_catalog(); 
							$spotify_charts = array_filter( $spotify_catalog, function($item) { return $item['category'] === 'charts'; } );
							$spotify_playlists = array_filter( $spotify_catalog, function($item) { return $item['category'] === 'playlist'; } );
							?>
							<div class="form-group">
								<label class="premium-label" for="spotify_item_key"><?php esc_html_e( 'Spotify Source (Official Chart or Curated Playlist)', 'charts' ); ?></label>
								<select name="spotify_item_key" id="spotify_item_key" class="premium-select">
									<optgroup label="<?php esc_attr_e( 'Official Spotify Charts (قوائم سبوتيفاي الرسمية)', 'charts' ); ?>">
										<?php foreach ( $spotify_charts as $sp_k => $sp_c ) : ?>
											<option value="<?php echo esc_attr( $sp_k ); ?>" data-category="charts" data-item-type="<?php echo esc_attr( $sp_c['item_type'] ); ?>" data-frequency="<?php echo esc_attr( $sp_c['frequency'] ); ?>" data-target-slug="<?php echo esc_attr( $sp_c['target_slug'] ); ?>" data-url="<?php echo esc_attr( $sp_c['url'] ); ?>">
												<?php echo esc_html( $sp_c['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
									<optgroup label="<?php esc_attr_e( 'Curated Spotify Playlists (قوائم التشغيل المختارة)', 'charts' ); ?>">
										<?php foreach ( $spotify_playlists as $sp_k => $sp_c ) : ?>
											<option value="<?php echo esc_attr( $sp_k ); ?>" data-category="playlist" data-item-type="<?php echo esc_attr( $sp_c['item_type'] ); ?>" data-frequency="<?php echo esc_attr( $sp_c['frequency'] ); ?>" data-target-slug="<?php echo esc_attr( $sp_c['target_slug'] ); ?>" data-url="<?php echo esc_attr( $sp_c['url'] ); ?>">
												<?php echo esc_html( $sp_c['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								</select>
								<div class="spotify-catalog-status" aria-live="polite" style="margin-top:8px;font-size:12px;display:flex;align-items:center;gap:8px;">
									<span class="spotify-category-badge" id="spotify-category-badge" style="background:#1DB954;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;">
										<?php esc_html_e( 'Official Chart', 'charts' ); ?>
									</span>
									<span id="spotify-selected-link" style="color:#1DB954;font-weight:600;word-break:break-all;">
										<?php echo esc_html( $spotify_catalog['regional-eg-weekly']['url'] ); ?>
									</span>
								</div>
								<p style="margin-top:6px;font-size:11px;color:#64748b;">
									<?php esc_html_e( 'Sync directly from Spotify link without uploading a CSV file, or optionally drop a CSV file below.', 'charts' ); ?>
								</p>
							</div>
						</div>
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
												data-platform="<?php echo esc_attr( $definition->platform ?: 'all' ); ?>"
												data-chart-type="<?php echo esc_attr( $definition->chart_type ?: 'top-songs' ); ?>"
												data-country="<?php echo esc_attr( $definition->country_code ?: 'eg' ); ?>"
												data-slug="<?php echo esc_attr( $definition->slug ); ?>"
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
									<option value="album"><?php esc_html_e( 'Albums', 'charts' ); ?></option>
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
							<div class="form-group full-width" style="margin-top: 15px;">
								<label class="premium-label" style="display:flex;align-items:center;gap:8px;">
									<?php esc_html_e( 'Import Mode', 'charts' ); ?>
									<span class="dashicons dashicons-info" title="Merge: Updates overlapping ranks, keeps remaining old entries. Replace: Wipes the entire existing chart for this date before inserting the new one." style="color:#64748b;font-size:16px;width:16px;height:16px;"></span>
								</label>
								<div style="display:flex; gap:20px; margin-top:8px;">
									<label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
										<input type="radio" name="import_mode" value="merge" checked style="accent-color:#5B21B6; width:16px; height:16px;">
										<span style="font-weight:600; color:#1e293b;">Merge (Update & Add)</span>
									</label>
									<label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
										<input type="radio" name="import_mode" value="replace" style="accent-color:#ef4444; width:16px; height:16px;">
										<span style="font-weight:600; color:#1e293b;">Replace (Wipe & Insert)</span>
									</label>
								</div>
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
	color: #0f172a;
}

select.premium-select {
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	padding-right: 44px;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-position: right 14px center;
	background-size: 16px;
}

select.premium-select:disabled {
	background-color: #f8fafc;
	color: #94a3b8;
	opacity: 0.85;
	cursor: not-allowed;
}

.market-dropdown-custom { position: relative; }
.select-affordance {
	display: none;
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
