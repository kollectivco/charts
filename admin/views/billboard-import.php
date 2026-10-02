<?php
/** Standalone Billboard Arabia import workflows. */
$manager = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( false );
?>
<div class="wrap charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Billboard Arabia Import', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php esc_html_e( 'Sync directly from Billboard Arabia or import a Billboard CSV export.', 'charts' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charts-imports' ) ); ?>" class="charts-btn-back"><?php esc_html_e( 'View Import History', 'charts' ); ?></a>
	</header>
	<?php settings_errors( 'charts' ); ?>
	<!-- 🌟 BILLBOARD ARABIA 1-CLICK NEXUS CARD -->
	<?php
	$bb_weeks = \Charts\Services\BillboardService::get_weeks();
	$bb_nonce = wp_create_nonce( 'charts_admin_action' );
	?>
	<div class="bb-nexus-card" style="background: linear-gradient(135deg, #0b1120 0%, #15203b 100%); color: #fff; padding: 28px 32px; border-radius: 16px; margin-bottom: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.08); position: relative;">
		<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
			<div>
				<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
					<span style="background: #1A48C4; color: #fff; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; letter-spacing: 0.5px; text-transform: uppercase;">Direct API Sync</span>
					<h2 style="color: #fff; margin: 0; font-size: 22px; font-weight: 800;">بيلبورد عربية هوت 100 (Billboard Arabia)</h2>
				</div>
				<p style="color: #94a3b8; margin: 0; font-size: 14px;">جلب البيانات الرسمية والصور عالية الدقة (HD) مباشرة من سيرفر بيلبورد وتحديث الشارت في ثوانٍ بدون رفع شيتات يدوي.</p>
			</div>
			<div style="background: rgba(255,255,255,0.06); padding: 6px 14px; border-radius: 30px; font-size: 12px; color: #38bdf8; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(56,189,248,0.2);">
				<span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
				<span>متصل بـ API بيلبورد المباشر</span>
			</div>
		</div>

		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 22px;">
			<div>
				<label style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">📅 أسبوع بيلبورد (Billboard Week)</label>
				<select id="bb-select-week" style="width: 100%; height: 42px; background: #1e293b; color: #fff; border: 1px solid #334155; border-radius: 8px; padding: 0 12px; font-size: 14px; outline: none;">
					<?php if ( ! empty($bb_weeks) ) : ?>
						<?php foreach ( $bb_weeks as $idx => $w ) : ?>
							<option value="<?php echo esc_attr($w['week_id']); ?>">
								<?php echo esc_html($w['label']); ?> <?php echo ($idx === 0) ? '🔥 (أحدث أسبوع)' : ''; ?>
							</option>
						<?php endforeach; ?>
					<?php else : ?>
						<option value="202639">01 أكتوبر 2026 (أسبوع 202639)</option>
					<?php endif; ?>
				</select>
			</div>

			<div>
				<label style="display: block; font-size: 12px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">🎯 الشارت الهدف في موقعك (Target Chart)</label>
				<select id="bb-select-chart" style="width: 100%; height: 42px; background: #1e293b; color: #fff; border: 1px solid #334155; border-radius: 8px; padding: 0 12px; font-size: 14px; outline: none;">
					<option value="0">افتراضي / شارت بيلبورد عربية الافتراضي</option>
					<?php foreach ( $definitions as $d ) : ?>
						<option value="<?php echo intval($d->id); ?>"><?php echo esc_html($d->title); ?> (<?php echo esc_html($d->slug); ?>)</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 14px;">
			<button type="button" id="bb-btn-sync" style="background: #1A48C4; color: #fff; border: none; padding: 12px 26px; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(26,72,196,0.4);">
				<span class="dashicons dashicons-update-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
				<span id="bb-btn-sync-text">استيراد وتحديث الشارت فوراً (1-Click Sync)</span>
			</button>

			<button type="button" id="bb-btn-csv" style="background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.18); padding: 12px 22px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
				<span class="dashicons dashicons-media-spreadsheet" style="font-size: 18px; width: 18px; height: 18px;"></span>
				<span>تحميل كشيت إكسيل (CSV جاهز بالصور)</span>
			</button>

			<span id="bb-spinner" style="display: none; color: #38bdf8; font-size: 14px; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear;"></span>
				<span>جاري الاتصال وسحب الـ 100 أغنية والصور الأصلية...</span>
			</span>
		</div>

		<div id="bb-feedback-box" style="display: none; margin-top: 18px; padding: 14px 18px; border-radius: 8px; font-size: 14px;"></div>
	</div>

	<style>
	@keyframes spin { 100% { transform: rotate(360deg); } }
	#bb-btn-sync:hover { background: #2557df !important; transform: translateY(-1px); }
	#bb-btn-csv:hover { background: rgba(255,255,255,0.16) !important; color: #fff !important; }
	</style>

	<script>
	jQuery(document).ready(function($) {
		var bbNonce = '<?php echo esc_js($bb_nonce); ?>';
		var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

		// 1. Direct Sync Handler
		$('#bb-btn-sync').on('click', function(e) {
			e.preventDefault();
			var weekId = $('#bb-select-week').val();
			var chartId = $('#bb-select-chart').val();
			var $btn = $(this);
			var $box = $('#bb-feedback-box');
			var $spinner = $('#bb-spinner');

			$btn.prop('disabled', true).css('opacity', '0.6');
			$spinner.css('display', 'inline-flex');
			$box.hide();

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'charts_billboard_sync',
					week_id: weekId,
					chart_id: chartId,
					_wpnonce: bbNonce
				},
				success: function(res) {
					$btn.prop('disabled', false).css('opacity', '1');
					$spinner.hide();
					if (res.success) {
						$box.css({
							'display': 'block',
							'background': 'rgba(16, 185, 129, 0.15)',
							'color': '#34d399',
							'border': '1px solid rgba(16, 185, 129, 0.3)'
						}).html('<strong>✅ نجاح:</strong> ' + res.data.message + ' <a href="' + '<?php echo esc_js(home_url('/charts/')); ?>' + '" target="_blank" style="color:#6ee7b7; text-decoration:underline; margin-right:8px;">معاينة الشارت في الموقع &rarr;</a>');
					} else {
						$box.css({
							'display': 'block',
							'background': 'rgba(239, 68, 68, 0.15)',
							'color': '#f87171',
							'border': '1px solid rgba(239, 68, 68, 0.3)'
						}).html('<strong>❌ خطأ:</strong> ' + (res.data ? res.data.message : 'فشلت عملية المزامنة'));
					}
				},
				error: function(xhr, status, err) {
					$btn.prop('disabled', false).css('opacity', '1');
					$spinner.hide();
					$box.css({
						'display': 'block',
						'background': 'rgba(239, 68, 68, 0.15)',
						'color': '#f87171',
						'border': '1px solid rgba(239, 68, 68, 0.3)'
					}).html('<strong>❌ خطأ في الاتصال:</strong> ' + err);
				}
			});
		});

		// 2. Download CSV Handler
		$('#bb-btn-csv').on('click', function(e) {
			e.preventDefault();
			var weekId = $('#bb-select-week').val();
			var downloadUrl = ajaxUrl + '?action=charts_billboard_download_csv&week_id=' + encodeURIComponent(weekId) + '&nonce=' + encodeURIComponent(bbNonce);
			window.location.href = downloadUrl;
		});
	});
	</script>


	<div class="premium-form-card" style="margin-top:24px; padding:24px;">
		<h2><?php esc_html_e( 'Import Billboard CSV', 'charts' ); ?></h2>
		<p><?php esc_html_e( 'Upload a Billboard CSV export with rank, track name, and artist columns.', 'charts' ); ?></p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="unified_import">
			<input type="hidden" name="platform" value="billboard">
			<p><label for="billboard-target"><strong><?php esc_html_e( 'Target chart', 'charts' ); ?></strong></label><br>
			<select name="chart_id" id="billboard-target" required>
				<option value=""><?php esc_html_e( 'Select a chart', 'charts' ); ?></option>
				<?php foreach ( $definitions as $definition ) : ?>
					<option value="<?php echo (int) $definition->id; ?>"><?php echo esc_html( $definition->title ); ?></option>
				<?php endforeach; ?>
			</select></p>
			<p><label for="billboard-period"><strong><?php esc_html_e( 'Chart week date', 'charts' ); ?></strong></label><br>
			<input type="date" name="period_date" id="billboard-period" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></p>
			<p><label for="billboard-csv"><strong><?php esc_html_e( 'CSV file', 'charts' ); ?></strong></label><br>
			<input type="file" name="import_file" id="billboard-csv" accept=".csv,text/csv" required></p>
			<button type="submit" class="charts-btn-create"><?php esc_html_e( 'Import Billboard CSV', 'charts' ); ?></button>
		</form>
	</div>
</div>
