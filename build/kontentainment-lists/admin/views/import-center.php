<?php
/**
 * Unified Import Center View
 */
$manager        = new \Charts\Admin\SourceManager();
$definitions    = $manager->get_definitions( false );
$pre_source     = $_GET['source'] ?? 'soundcharts';
$presets        = \Charts\Services\SoundchartsPresetRegistry::all();
?>
<div class="wrap charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div class="charts-admin-title-group">
			<div style="display:flex; align-items:center; gap:10px; font-size:12px; font-weight:700; color:var(--charts-text-dim); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">
				<span>Lists</span>
				<span style="opacity:0.3;">&rsaquo;</span>
				<span>Import Center</span>
			</div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Unified Ingestion Center', 'kontentainment-lists' ); ?></h1>
			<p class="charts-admin-subtitle">Run Soundcharts-powered list refreshes or fall back to manual CSV uploads without leaving the existing workflow.</p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-imports' ); ?>" class="charts-btn-back">
				<?php esc_html_e( 'View Processing Logs', 'kontentainment-lists' ); ?>
			</a>
		</div>
	</header>

	<?php settings_errors( 'kontentainment-lists' ); ?>

	<form method="post" action="" enctype="multipart/form-data" id="unified-import-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="unified_import">

		<div class="premium-form-card">
			<div class="card-header">
				<h3><?php esc_html_e( 'Source Selection', 'kontentainment-lists' ); ?></h3>
				<p><?php esc_html_e( 'Pick the ingestion source, target list, week/date, and then preview or import.', 'kontentainment-lists' ); ?></p>
			</div>

			<div class="platform-selector">
				<label class="platform-card">
					<input type="radio" name="platform" value="soundcharts" <?php checked( $pre_source, 'soundcharts' ); ?>>
					<div class="platform-inner">
						<div class="platform-icon soundcharts"><span class="dashicons dashicons-chart-area"></span></div>
						<div class="platform-info">
							<strong>Soundcharts API</strong>
							<span>Primary live ingestion source</span>
						</div>
					</div>
				</label>
				<label class="platform-card">
					<input type="radio" name="platform" value="spotify" <?php checked( $pre_source, 'spotify' ); ?>>
					<div class="platform-inner">
						<div class="platform-icon spotify"><span class="dashicons dashicons-format-audio"></span></div>
						<div class="platform-info">
							<strong>CSV Upload</strong>
							<span>Spotify fallback / manual backfill</span>
						</div>
					</div>
				</label>
				<label class="platform-card">
					<input type="radio" name="platform" value="youtube" <?php checked( $pre_source, 'youtube' ); ?>>
					<div class="platform-inner">
						<div class="platform-icon youtube"><span class="dashicons dashicons-video-alt3"></span></div>
						<div class="platform-info">
							<strong>YouTube CSV</strong>
							<span>Manual supplemental import</span>
						</div>
					</div>
				</label>
			</div>

			<div class="premium-form-grid" style="margin-top:28px;">
				<div class="form-group">
					<label for="chart_id"><?php esc_html_e( 'List Target', 'kontentainment-lists' ); ?></label>
					<select name="chart_id" id="chart_id" class="form-control" required>
						<option value=""><?php esc_html_e( '— Select List Definition —', 'kontentainment-lists' ); ?></option>
						<?php foreach ( $definitions as $definition ) : ?>
							<?php $settings = ! empty( $definition->display_settings_json ) ? json_decode( $definition->display_settings_json, true ) : array(); ?>
							<option
								value="<?php echo (int) $definition->id; ?>"
								data-item-type="<?php echo esc_attr( $definition->item_type ); ?>"
								data-chart-type="<?php echo esc_attr( $definition->chart_type ); ?>"
								data-country="<?php echo esc_attr( $definition->country_code ); ?>"
								data-frequency="<?php echo esc_attr( $definition->frequency ); ?>"
								data-preset="<?php echo esc_attr( $settings['soundcharts_preset'] ?? 'top_tracks_egypt' ); ?>"
							>
								<?php echo esc_html( $definition->title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="country"><?php esc_html_e( 'Country / Territory', 'kontentainment-lists' ); ?></label>
					<input type="text" name="country" id="country" value="eg" class="form-control" maxlength="10">
					<span class="input-helper"><?php esc_html_e( 'ISO territory code used for Soundcharts and CSV context.', 'kontentainment-lists' ); ?></span>
				</div>

				<div class="form-group">
					<label for="chart_type"><?php esc_html_e( 'List Type', 'kontentainment-lists' ); ?></label>
					<select name="chart_type" id="chart_type" class="form-control">
						<option value="top-songs"><?php esc_html_e( 'Top Songs', 'kontentainment-lists' ); ?></option>
						<option value="top-artists"><?php esc_html_e( 'Top Artists', 'kontentainment-lists' ); ?></option>
						<option value="top-videos"><?php esc_html_e( 'Top Videos', 'kontentainment-lists' ); ?></option>
						<option value="viral"><?php esc_html_e( 'Viral', 'kontentainment-lists' ); ?></option>
					</select>
				</div>

				<div class="form-group">
					<label for="frequency"><?php esc_html_e( 'Frequency', 'kontentainment-lists' ); ?></label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="weekly"><?php esc_html_e( 'Weekly', 'kontentainment-lists' ); ?></option>
						<option value="daily"><?php esc_html_e( 'Daily', 'kontentainment-lists' ); ?></option>
						<option value="monthly"><?php esc_html_e( 'Monthly', 'kontentainment-lists' ); ?></option>
					</select>
				</div>

				<div class="form-group">
					<label for="period_date"><?php esc_html_e( 'Week / Date', 'kontentainment-lists' ); ?></label>
					<input type="date" name="period_date" id="period_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" class="form-control">
				</div>

				<div class="form-group">
					<label for="preset_key"><?php esc_html_e( 'Endpoint Preset', 'kontentainment-lists' ); ?></label>
					<select name="preset_key" id="preset_key" class="form-control">
						<?php foreach ( $presets as $key => $preset ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $preset['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="input-helper"><?php esc_html_e( 'Presets replace manual endpoint entry and control normalization behavior.', 'kontentainment-lists' ); ?></span>
				</div>

				<div class="form-group form-group-full">
					<label for="source_name"><?php esc_html_e( 'Import Label', 'kontentainment-lists' ); ?></label>
					<input type="text" name="source_name" id="source_name" class="form-control" placeholder="<?php esc_html_e( 'Optional source label for logs and attribution', 'kontentainment-lists' ); ?>">
				</div>
				<div class="form-group">
					<label>Dry Run</label>
					<div class="toggle-item" style="margin-top:10px;">
						<label class="switch">
							<input type="checkbox" name="dry_run" id="dry_run" value="1">
							<span class="slider"></span>
						</label>
						<label><?php esc_html_e( 'Preview without writing to the database', 'kontentainment-lists' ); ?></label>
					</div>
				</div>
			</div>
		</div>

		<div class="premium-form-card" style="margin-top:32px;" id="csv-upload-panel">
			<div class="card-header">
				<h3><?php esc_html_e( 'CSV Fallback Upload', 'kontentainment-lists' ); ?></h3>
				<p><?php esc_html_e( 'Used only for Spotify / YouTube CSV imports. Not required for Soundcharts API runs.', 'kontentainment-lists' ); ?></p>
			</div>
			<div class="file-upload-zone" id="drop-zone">
				<div class="upload-content">
					<span class="dashicons dashicons-upload"></span>
					<p><?php esc_html_e( 'Drop a CSV file here or click to browse', 'kontentainment-lists' ); ?></p>
					<input type="file" name="import_file" id="import_file" accept=".csv">
				</div>
				<div class="file-preview" style="display:none;">
					<span class="dashicons dashicons-media-spreadsheet"></span>
					<span class="filename"></span>
					<button type="button" class="remove-file">&times;</button>
				</div>
			</div>
		</div>

		<div class="premium-form-card" style="margin-top:32px;">
			<div class="card-header">
				<h3><?php esc_html_e( 'Preview & Execution', 'kontentainment-lists' ); ?></h3>
				<p><?php esc_html_e( 'Preview is available for Soundcharts API requests before saving. Imports update existing weekly list entries instead of duplicating them.', 'kontentainment-lists' ); ?></p>
			</div>
			<div class="import-submit-bar">
				<div class="summary-info">
					<p><strong><?php esc_html_e( 'Target:', 'kontentainment-lists' ); ?></strong> <span id="charts-import-summary-target"><?php esc_html_e( 'No list selected yet', 'kontentainment-lists' ); ?></span></p>
					<p><strong><?php esc_html_e( 'Source:', 'kontentainment-lists' ); ?></strong> <span id="charts-import-summary-source"><?php esc_html_e( 'Soundcharts API', 'kontentainment-lists' ); ?></span></p>
				</div>
				<div style="display:flex; gap:12px;">
					<button type="button" class="charts-btn-back" id="charts-preview-btn"><?php esc_html_e( 'Preview Soundcharts Rows', 'kontentainment-lists' ); ?></button>
					<button type="submit" class="charts-btn-create" id="run-import-btn"><?php esc_html_e( 'Run Import', 'kontentainment-lists' ); ?></button>
				</div>
			</div>
			<div id="charts-preview-results" class="charts-preview-results" style="display:none;"></div>
		</div>
	</form>
</div>
