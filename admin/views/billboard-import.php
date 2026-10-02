<?php
/** Standalone Billboard Arabia import workflows. */
$manager     = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions( false );
$catalog     = \Charts\Services\BillboardService::get_chart_catalog();
$weeks       = \Charts\Services\BillboardService::get_weeks( 1 );
$nonce       = wp_create_nonce( 'charts_admin_action' );
?>
<div class="wrap charts-admin-wrap premium-light bb-import-page">
	<header class="charts-admin-header bb-page-header">
		<div>
			<p class="bb-eyebrow"><?php esc_html_e( 'DATA IMPORT', 'charts' ); ?></p>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Billboard Arabia Import', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php esc_html_e( 'استورد قوائم Billboard Arabia مباشرة، أو ارفع ملف CSV للشارت المطلوب.', 'charts' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charts-imports' ) ); ?>" class="charts-btn-back"><?php esc_html_e( 'Import History', 'charts' ); ?> <span aria-hidden="true">↗</span></a>
	</header>
	<?php settings_errors( 'charts' ); ?>

	<section class="bb-sync-card" aria-labelledby="bb-sync-title">
		<div class="bb-sync-heading">
			<div class="bb-brand-mark" aria-hidden="true">B</div>
			<div class="bb-heading-copy">
				<div class="bb-heading-meta"><span class="bb-live-dot"></span><?php esc_html_e( 'BILLBOARD ARABIA · DIRECT API', 'charts' ); ?></div>
				<h2 id="bb-sync-title"><?php esc_html_e( 'اختار القائمة وحدد وجهة الاستيراد', 'charts' ); ?></h2>
				<p><?php esc_html_e( 'القائمة المصدر تحدد بيانات Billboard، والوجهة تحدد الشارت الذي سيظهر فيه الترتيب داخل موقعك.', 'charts' ); ?></p>
			</div>
			<div class="bb-connection-pill" id="bb-connection-status" data-connected="<?php echo ! empty( $weeks ) ? 'true' : 'false'; ?>">
				<span class="bb-status-dot"></span><span><?php echo ! empty( $weeks ) ? esc_html__( 'API متاح', 'charts' ) : esc_html__( 'تعذر الاتصال', 'charts' ); ?></span>
			</div>
		</div>

		<div class="bb-field-grid">
			<div class="bb-field">
				<label for="bb-select-source"><?php esc_html_e( 'قائمة Billboard', 'charts' ); ?></label>
				<select id="bb-select-source">
					<?php foreach ( $catalog as $billboard_id => $chart ) : ?>
						<option value="<?php echo (int) $billboard_id; ?>" data-item-type="<?php echo esc_attr( $chart['item_type'] ); ?>" data-target-slug="<?php echo esc_attr( $chart['target_slug'] ); ?>"><?php echo esc_html( $chart['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<small><?php esc_html_e( 'Hot 100، 100 فنان، إندي، ومهرجانات.', 'charts' ); ?></small>
			</div>
			<div class="bb-field">
				<label for="bb-select-chart"><?php esc_html_e( 'الشارت الوجهة في موقعك', 'charts' ); ?></label>
				<select id="bb-select-chart" required>
					<option value=""><?php esc_html_e( 'اختار الشارت الوجهة', 'charts' ); ?></option>
					<?php foreach ( $definitions as $definition ) : ?>
						<option value="<?php echo (int) $definition->id; ?>" data-item-type="<?php echo esc_attr( $definition->item_type ); ?>" data-slug="<?php echo esc_attr( $definition->slug ); ?>"><?php echo esc_html( $definition->title ); ?></option>
					<?php endforeach; ?>
				</select>
				<small><?php esc_html_e( 'هنعرض هنا الشارتات المتوافقة مع نوع القائمة.', 'charts' ); ?></small>
			</div>
			<div class="bb-field">
				<label for="bb-select-week"><?php esc_html_e( 'أسبوع Billboard', 'charts' ); ?></label>
				<select id="bb-select-week" <?php disabled( empty( $weeks ) ); ?>>
					<?php foreach ( $weeks as $index => $week ) : ?>
						<option value="<?php echo (int) $week['week_id']; ?>"><?php echo esc_html( $week['label'] . ( 0 === $index ? ' · الأحدث' : '' ) ); ?></option>
					<?php endforeach; ?>
					<?php if ( empty( $weeks ) ) : ?><option value=""><?php esc_html_e( 'لا توجد أسابيع متاحة', 'charts' ); ?></option><?php endif; ?>
				</select>
				<small id="bb-week-help"><?php esc_html_e( 'بتتغير الأسابيع حسب القائمة المختارة.', 'charts' ); ?></small>
			</div>
		</div>

		<div class="bb-actions">
			<button type="button" id="bb-btn-sync" class="bb-primary-button"><span class="dashicons dashicons-update-alt" aria-hidden="true"></span><span><?php esc_html_e( 'استيراد القائمة الآن', 'charts' ); ?></span></button>
			<button type="button" id="bb-btn-csv" class="bb-secondary-button"><span class="dashicons dashicons-download" aria-hidden="true"></span><span><?php esc_html_e( 'تحميل CSV', 'charts' ); ?></span></button>
			<span id="bb-spinner" class="bb-loading" style="display:none"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e( 'جاري جلب القائمة وتحديث الشارت…', 'charts' ); ?></span>
		</div>
		<div id="bb-feedback-box" class="bb-feedback" role="status" aria-live="polite" style="display:none"></div>
	</section>

	<section class="bb-csv-card" aria-labelledby="bb-csv-title">
		<div class="bb-csv-icon dashicons dashicons-media-spreadsheet" aria-hidden="true"></div>
		<div class="bb-csv-copy">
			<h2 id="bb-csv-title"><?php esc_html_e( 'استيراد من ملف CSV', 'charts' ); ?></h2>
			<p><?php esc_html_e( 'استخدم نفس الشارت الوجهة وحدد تاريخ الأسبوع الموجود في الملف.', 'charts' ); ?></p>
		</div>
		<form method="post" enctype="multipart/form-data" class="bb-csv-form">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="unified_import">
			<input type="hidden" name="platform" value="billboard">
			<label class="screen-reader-text" for="billboard-period"><?php esc_html_e( 'Chart week date', 'charts' ); ?></label>
			<input type="date" name="period_date" id="billboard-period" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required>
			<label class="bb-file-input" for="billboard-csv"><span class="dashicons dashicons-upload" aria-hidden="true"></span><span><?php esc_html_e( 'اختار ملف CSV', 'charts' ); ?></span><input type="file" name="import_file" id="billboard-csv" accept=".csv,text/csv" required></label>
			<input type="hidden" name="chart_id" id="billboard-target" value="">
			<button type="submit" class="bb-secondary-button"><?php esc_html_e( 'استيراد الملف', 'charts' ); ?></button>
		</form>
	</section>
</div>

<style>
.bb-import-page{--bb-ink:#172033;--bb-muted:#697386;--bb-blue:#2855d9;--bb-border:#e5e9f1;max-width:1280px}
.bb-page-header{align-items:center;margin-bottom:22px}.bb-eyebrow{margin:0 0 5px;color:#5267a6;font-size:11px;font-weight:800;letter-spacing:.13em}.bb-page-header .charts-admin-title{margin:0 0 6px}.bb-page-header .charts-admin-subtitle{margin:0;color:var(--bb-muted)}
.bb-sync-card{padding:30px;border:1px solid #202d49;border-radius:18px;background:radial-gradient(ellipse at 100% 0,rgba(57,91,174,.24),transparent 42%),linear-gradient(135deg,#10182a,#131e35);color:#fff;box-shadow:0 16px 42px rgba(19,30,53,.13)}
.bb-sync-heading{display:flex;align-items:flex-start;gap:16px;margin-bottom:27px}.bb-brand-mark{display:grid;place-items:center;flex:0 0 48px;height:48px;border-radius:14px;background:#244fcd;color:#fff;font-size:27px;font-weight:900;font-family:Arial,sans-serif}.bb-heading-copy{flex:1}.bb-heading-meta{display:flex;align-items:center;gap:8px;color:#8baeff;font-size:10px;font-weight:800;letter-spacing:.12em}.bb-live-dot,.bb-status-dot{width:7px;height:7px;border-radius:50%;background:#21ca91;box-shadow:0 0 0 4px rgba(33,202,145,.13)}.bb-heading-copy h2{margin:7px 0 5px;color:#fff;font-size:21px}.bb-heading-copy p{margin:0;color:#9eaac0;font-size:13px}.bb-connection-pill{display:flex;align-items:center;gap:9px;padding:9px 12px;border:1px solid rgba(255,255,255,.11);border-radius:30px;color:#c3cde0;font-size:12px}.bb-connection-pill[data-connected="false"] .bb-status-dot{background:#f45e62;box-shadow:0 0 0 4px rgba(244,94,98,.14)}
.bb-field-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.bb-field{padding:15px;border:1px solid rgba(255,255,255,.09);border-radius:12px;background:rgba(255,255,255,.045)}.bb-field label{display:block;margin:0 0 9px;color:#d9e0ec;font-size:12px;font-weight:700}.bb-field select{width:100%;height:44px;padding:0 12px;border:1px solid #394660;border-radius:8px;background:#19253b;color:#fff;font-size:13px}.bb-field select:focus{border-color:#7293ff;box-shadow:0 0 0 2px rgba(88,122,255,.22);outline:0}.bb-field small{display:block;margin-top:8px;color:#8694ac;font-size:11px}.bb-actions{display:flex;align-items:center;gap:11px;margin-top:20px}.bb-primary-button,.bb-secondary-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:43px;padding:0 17px;border:0;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer}.bb-primary-button{background:#315fe5;color:#fff;box-shadow:0 5px 14px rgba(35,81,211,.3)}.bb-primary-button:hover{background:#426ff0;color:#fff}.bb-secondary-button{border:1px solid #d9dfeb;background:#fff;color:#28354d}.bb-secondary-button:hover{border-color:#aab8d4;color:#172033}.bb-sync-card .bb-secondary-button{border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.07);color:#e2e8f3}.bb-sync-card .bb-secondary-button:hover{background:rgba(255,255,255,.13);color:#fff}.bb-primary-button:disabled,.bb-secondary-button:disabled{opacity:.55;cursor:wait}.bb-loading{display:inline-flex;align-items:center;gap:8px;color:#aebbd1;font-size:12px}.bb-loading .dashicons{animation:bb-spin 1s linear infinite}@keyframes bb-spin{to{transform:rotate(360deg)}}.bb-feedback{margin-top:16px;padding:12px 14px;border:1px solid rgba(255,255,255,.13);border-radius:9px;font-size:13px}.bb-feedback.is-success{background:rgba(18,160,112,.16);color:#a9f2d5}.bb-feedback.is-error{background:rgba(224,67,79,.14);color:#ffc1c5}
.bb-csv-card{display:flex;align-items:center;gap:16px;margin-top:20px;padding:20px 22px;border:1px solid var(--bb-border);border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(29,42,67,.04)}.bb-csv-icon{display:grid;place-items:center;flex:0 0 44px;height:44px;border-radius:12px;background:#edf3ff;color:#315fe5;font-size:22px}.bb-csv-copy{flex:1}.bb-csv-copy h2{margin:0 0 4px;color:var(--bb-ink);font-size:15px}.bb-csv-copy p{margin:0;color:var(--bb-muted);font-size:12px}.bb-csv-form{display:flex;align-items:center;gap:9px}.bb-csv-form>input[type=date]{height:40px;border:1px solid var(--bb-border);border-radius:7px;padding:0 9px}.bb-file-input{position:relative;display:inline-flex;align-items:center;gap:7px;max-width:175px;height:40px;padding:0 11px;overflow:hidden;border:1px dashed #bdc7d8;border-radius:7px;color:#47536a;font-size:12px;white-space:nowrap;cursor:pointer}.bb-file-input input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.bb-file-input .dashicons{color:#315fe5}.bb-file-input.has-file{border-color:#315fe5;background:#f3f6ff}
@media(max-width:900px){.bb-field-grid{grid-template-columns:1fr 1fr}.bb-csv-card{align-items:flex-start;flex-wrap:wrap}.bb-csv-copy{min-width:70%}.bb-csv-form{width:100%;flex-wrap:wrap}.bb-csv-form>input[type=date]{flex:1}}
@media(max-width:600px){.bb-sync-card{padding:19px}.bb-sync-heading{flex-wrap:wrap}.bb-connection-pill{margin-left:64px}.bb-field-grid{grid-template-columns:1fr}.bb-actions{align-items:stretch;flex-direction:column}.bb-primary-button,.bb-actions>.bb-secondary-button{width:100%;min-height:46px}.bb-csv-card{padding:17px}.bb-csv-copy{min-width:calc(100% - 64px)}.bb-csv-form>input[type=date],.bb-file-input,.bb-csv-form>.bb-secondary-button{width:100%;max-width:none;min-height:42px}}
</style>

<script>
jQuery(function($) {
	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce = <?php echo wp_json_encode( $nonce ); ?>;
	var $source = $('#bb-select-source'), $target = $('#bb-select-chart'), $week = $('#bb-select-week');
	var $sync = $('#bb-btn-sync'), $csv = $('#bb-btn-csv'), $feedback = $('#bb-feedback-box');

	function filterTargets(preferMatch) {
		var $sourceOption = $source.find(':selected');
		var itemType = $sourceOption.data('item-type');
		var matchSlug = $sourceOption.data('target-slug');
		var chosen = '';
		$target.find('option').each(function() {
			var $option = $(this), compatible = !$option.val() || $option.data('item-type') === itemType;
			$option.prop('disabled', !compatible).toggle(compatible);
			if (preferMatch && $option.data('slug') === matchSlug && compatible) chosen = $option.val();
		});
		if (chosen) $target.val(chosen);
		else if ($target.find(':selected').prop('disabled')) $target.val('');
		$('#billboard-target').val($target.val() || '');
	}

	function loadWeeks() {
		var sourceId = $source.val();
		$week.prop('disabled', true).html('<option value=""><?php echo esc_js( __( 'جاري تحميل الأسابيع…', 'charts' ) ); ?></option>');
		$.post(ajaxUrl, {action:'charts_billboard_get_weeks', billboard_chart_id:sourceId, _wpnonce:nonce})
		.done(function(res) {
			$week.empty();
			if (res && res.success && res.data.weeks && res.data.weeks.length) {
				$.each(res.data.weeks, function(i, item) {
					$('<option>').val(item.week_id).text(item.label + (i === 0 ? ' · الأحدث' : '')).appendTo($week);
				});
				$week.prop('disabled', false);
				$('#bb-connection-status').attr('data-connected','true').find('span:last').text('<?php echo esc_js( __( 'API متاح', 'charts' ) ); ?>');
			} else {
				$week.append($('<option>').val('').text('<?php echo esc_js( __( 'لا توجد أسابيع متاحة', 'charts' ) ); ?>'));
				$('#bb-connection-status').attr('data-connected','false').find('span:last').text('<?php echo esc_js( __( 'تعذر الاتصال', 'charts' ) ); ?>');
			}
		}).fail(function() {
			$week.html('<option value=""><?php echo esc_js( __( 'تعذر الاتصال بـ Billboard', 'charts' ) ); ?></option>');
			$('#bb-connection-status').attr('data-connected','false').find('span:last').text('<?php echo esc_js( __( 'تعذر الاتصال', 'charts' ) ); ?>');
		}).always(function() {$week.prop('disabled', false);});
	}

	$source.on('change', function() { filterTargets(true); loadWeeks(); $feedback.hide(); });
	$target.on('change', function() { $('#billboard-target').val($(this).val()); });
	filterTargets(true);
	$('#billboard-csv').on('change', function() {
		var name = this.files && this.files[0] ? this.files[0].name : '<?php echo esc_js( __( 'اختار ملف CSV', 'charts' ) ); ?>';
		$(this).closest('.bb-file-input').toggleClass('has-file', !!this.files.length).find('span').last().text(name);
	});

	$sync.on('click', function() {
		if (!$target.val()) { $feedback.removeClass('is-success').addClass('is-error').text('<?php echo esc_js( __( 'اختار شارت وجهة متوافق الأول.', 'charts' ) ); ?>').show(); return; }
		$sync.prop('disabled', true); $csv.prop('disabled', true); $('#bb-spinner').show(); $feedback.hide();
		$.post(ajaxUrl, {action:'charts_billboard_sync', week_id:$week.val(), chart_id:$target.val(), billboard_chart_id:$source.val(), _wpnonce:nonce})
		.done(function(res) {
			if (res && res.success) $feedback.removeClass('is-error').addClass('is-success').text(res.data.message || '<?php echo esc_js( __( 'تم الاستيراد بنجاح.', 'charts' ) ); ?>');
			else $feedback.removeClass('is-success').addClass('is-error').text(res && res.data ? res.data.message : '<?php echo esc_js( __( 'فشل استيراد القائمة.', 'charts' ) ); ?>');
			$feedback.show();
		}).fail(function(xhr) { $feedback.removeClass('is-success').addClass('is-error').text(xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : '<?php echo esc_js( __( 'تعذر الاتصال بالخادم.', 'charts' ) ); ?>').show(); })
		.always(function() { $sync.prop('disabled', false); $csv.prop('disabled', false); $('#bb-spinner').hide(); });
	});

	$csv.on('click', function() {
		if (!$target.val()) { $feedback.removeClass('is-success').addClass('is-error').text('<?php echo esc_js( __( 'اختار شارت وجهة متوافق الأول.', 'charts' ) ); ?>').show(); return; }
		var query = $.param({action:'charts_billboard_download_csv', week_id:$week.val(), chart_id:$source.val(), nonce:nonce});
		window.location.href = ajaxUrl + '?' + query;
	});

	$('form.bb-csv-form').on('submit', function(e) {
		if (!$target.val()) { e.preventDefault(); $feedback.removeClass('is-success').addClass('is-error').text('<?php echo esc_js( __( 'اختار شارت وجهة متوافق الأول.', 'charts' ) ); ?>').show(); return; }
		$('#billboard-target').val($target.val());
	});
});
</script>
