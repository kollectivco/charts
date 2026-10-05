<?php
/**
 * Name Sync Tool
 * Upload a CSV with Arabic + English names for artists & tracks and sync them to the DB.
 */

global $wpdb;

// ─── Handle results display ───────────────────────────────────────────────────
$result = null;
if ( isset( $_GET['sync_run_id'] ) ) {
    $run_id = intval( $_GET['sync_run_id'] );
    $result = get_transient( 'charts_name_sync_result_' . $run_id );
}
?>

<div class="wrap kc-name-sync-wrap premium-bento" style="max-width:1100px;">

    <div class="kc-settings-header" style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e2e8f0;">
        <div class="kc-branding">
            <h1 class="kc-title" style="margin:0;font-size:28px;font-weight:900;letter-spacing:-0.04em;color:#1e293b;display:flex;align-items:center;gap:10px;">
                <span class="dashicons dashicons-update-alt" style="font-size:32px;width:32px;height:32px;color:#7c3aed;"></span>
                Name Sync
            </h1>
            <p class="kc-subtitle" style="margin:8px 0 0;color:#64748b;font-size:15px;">
                ارفع CSV يحتوي على الأسماء العربية والإنجليزية للفنانين والأغاني وسيقوم النظام بتحديث السجلات الموجودة.
            </p>
        </div>
    </div>

    <?php if ( $result ) : ?>
    <!-- Results Panel -->
    <div class="sync-result-card" style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;margin-bottom:32px;box-shadow:0 4px 20px rgba(0,0,0,0.04);">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
            <div style="width:48px;height:48px;background:#dcfce7;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <span class="dashicons dashicons-yes-alt" style="font-size:28px;width:28px;height:28px;color:#16a34a;"></span>
            </div>
            <div>
                <h2 style="margin:0;font-size:20px;font-weight:800;color:#1e293b;">Sync Completed</h2>
                <p style="margin:4px 0 0;color:#64748b;font-size:14px;"><?php echo esc_html( date('M j, Y — H:i') ); ?></p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
            <?php
            $stats = [
                [ 'label' => 'Total Rows',        'val' => $result['total'],           'color' => '#1e293b' ],
                [ 'label' => 'Artists Updated',   'val' => $result['artists_updated'], 'color' => '#7c3aed' ],
                [ 'label' => 'Tracks Updated',    'val' => $result['tracks_updated'],  'color' => '#0ea5e9' ],
                [ 'label' => 'Clips Updated',     'val' => $result['videos_updated'],  'color' => '#f43f5e' ],
                [ 'label' => 'Albums Updated',    'val' => $result['albums_updated'],  'color' => '#8b5cf6' ],
                [ 'label' => 'Slugs Refreshed',   'val' => $result['slugs_updated'],   'color' => '#10b981' ],
                [ 'label' => 'Not Found',         'val' => $result['not_found'],       'color' => '#f59e0b' ],
            ];
            foreach ( $stats as $s ) : ?>
            <div style="background:#f8fafc;border-radius:12px;padding:20px;text-align:center;">
                <div style="font-size:32px;font-weight:900;color:<?php echo $s['color']; ?>;"><?php echo number_format($s['val']); ?></div>
                <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-top:4px;"><?php echo $s['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ( ! empty( $result['log'] ) ) : ?>
        <details style="margin-top:16px;">
            <summary style="cursor:pointer;font-size:14px;font-weight:700;color:#475569;padding:12px 0;">
                📋 Detailed Log (<?php echo count($result['log']); ?> entries)
            </summary>
            <div style="background:#0f172a;border-radius:8px;padding:16px;margin-top:12px;max-height:300px;overflow-y:auto;">
                <?php foreach ( $result['log'] as $line ) : ?>
                <div style="font-family:monospace;font-size:12px;color:#94a3b8;padding:2px 0;"><?php echo esc_html($line); ?></div>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>

        <a href="<?php echo admin_url('admin.php?page=charts-name-sync'); ?>" class="kb-btn kb-btn-outline" style="display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:10px 20px;border:2px solid #e2e8f0;border-radius:8px;font-weight:700;color:#475569;text-decoration:none;">
            <span class="dashicons dashicons-update" style="font-size:16px;width:16px;height:16px;"></span>
            Run Another Sync
        </a>
    </div>
    <?php endif; ?>

    <!-- CSV Format Guide -->
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:20px 24px;margin-bottom:28px;display:flex;gap:14px;">
        <span class="dashicons dashicons-info" style="color:#0ea5e9;font-size:20px;width:20px;height:20px;flex-shrink:0;margin-top:2px;"></span>
        <div style="font-size:14px;color:#0369a1;">
            <strong>تنسيق الملف المطلوب:</strong><br>
            الملف يجب أن يكون <strong>CSV</strong> ويحتوي على الأعمدة التالية:
            <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;">
                <?php
                $cols = [
                    ['arabic_artist',  '#7c3aed', 'اسم الفنان عربي'],
                    ['english_artist', '#0ea5e9', 'اسم الفنان إنجليزي'],
                    ['arabic_title',   '#7c3aed', 'اسم الأغنية عربي'],
                    ['english_title',  '#0ea5e9', 'اسم الأغنية إنجليزي'],
                ];
                foreach ( $cols as $c ) : ?>
                <span style="background:#fff;border:1px solid <?php echo $c[1]; ?>;color:<?php echo $c[1]; ?>;padding:4px 10px;border-radius:6px;font-family:monospace;font-size:13px;font-weight:700;" title="<?php echo esc_attr($c[2]); ?>"><?php echo $c[0]; ?></span>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;font-size:13px;color:#0369a1;">
                ✅ الأعمدة <strong>اختيارية</strong> — يمكنك تحديث الفنانين فقط أو الأغاني فقط أو كليهما.<br>
                ✅ إذا لم يُعثر على الفنان أو الأغنية <strong>لن يتم إنشاؤهم</strong>، فقط تحديث الموجود.<br>
                ✅ يتم تحديث الـ <strong>slug</strong> تلقائياً بناءً على الاسم الإنجليزي الجديد.
            </div>
        </div>
    </div>

    <!-- Export Current Data -->
    <div class="kb-table-card" style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;margin-bottom:28px;box-shadow:0 4px 20px rgba(0,0,0,0.04);">
        <h2 style="margin:0 0 16px;font-size:18px;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-download" style="font-size:20px;width:20px;height:20px;color:#10b981;"></span>
            تصدير الداتا (Export)
        </h2>
        <p style="color:#64748b;font-size:14px;margin:0 0 20px;">حمل الداتا الحالية بصيغة CSV جاهزة للتعديل وإعادة الرفع مرة أخرى.</p>
        
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <?php foreach(['artists' => 'Artists', 'tracks' => 'Tracks', 'videos' => 'Clips', 'albums' => 'Albums'] as $key => $label): ?>
            <form method="post" action="" style="margin:0;">
                <?php wp_nonce_field( 'charts_admin_action', '_wpnonce' ); ?>
                <input type="hidden" name="charts_action" value="export_name_sync">
                <input type="hidden" name="export_type" value="<?php echo esc_attr($key); ?>">
                <button type="submit" class="kb-btn kb-btn-outline" style="background:#fff;border:2px solid #e2e8f0;border-radius:8px;padding:10px 20px;font-weight:700;color:#475569;cursor:pointer;display:flex;align-items:center;gap:8px;transition:0.2s;">
                    <span class="dashicons dashicons-media-spreadsheet" style="color:#10b981;"></span>
                    Export <?php echo esc_html($label); ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Gemini AI Auto-Translate -->
    <div class="kb-table-card" style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border-radius:16px;border:1px solid #cbd5e1;padding:32px;margin-bottom:28px;box-shadow:0 4px 20px rgba(0,0,0,0.04);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
            <div>
                <h2 style="margin:0 0 10px;font-size:18px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;">
                    <span style="font-size:22px;">✨</span>
                    الترجمة الذكية عبر Google Gemini AI
                </h2>
                <p style="color:#64748b;font-size:14px;margin:0;max-width:640px;">
                    يقوم Gemini بالتعرف التلقائي على أسماء الفنانين والأغاني العربية الناقصة وتوليد الاسم الإنجليزي الصوتي المعتمد (بدون فرانكو) والـ Slug تلقائياً بدقة عالية.
                </p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <select id="gemini-translate-type" style="padding:10px 14px;border-radius:8px;border:1px solid #cbd5e1;font-weight:700;color:#334155;">
                    <option value="all">كل السجلات الناقصة (All)</option>
                    <option value="artists">الفنانين فقط (Artists)</option>
                    <option value="tracks">الأغاني فقط (Tracks)</option>
                </select>
                <button type="button" id="btn-gemini-translate" class="kb-btn" style="background:#4338ca;color:#fff;border:none;border-radius:8px;padding:11px 22px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(67,56,202,0.25);" onclick="runGeminiTranslate()">
                    <span>✨ ترجمة وتوليد الآن</span>
                </button>
            </div>
        </div>
        <div id="gemini-translate-status" style="margin-top:16px;font-size:13px;font-weight:700;display:none;"></div>
        <script>
        function runGeminiTranslate() {
            var btn = jQuery('#btn-gemini-translate');
            var status = jQuery('#gemini-translate-status');
            var type = jQuery('#gemini-translate-type').val();
            btn.prop('disabled', true).find('span').text('جاري المعالجة بواسطة Gemini...');
            status.show().css('color', '#6366f1').text('جاري استدعاء نموذج Gemini وترجمة الكيانات...');

            jQuery.post(ajaxurl, {
                action: 'charts_gemini_translate_missing',
                entity_type: type,
                batch_size: 25,
                _wpnonce: '<?php echo wp_create_nonce("charts_admin_action"); ?>'
            }).done(function(r) {
                if (r && r.success) {
                    status.css('color', '#10b981').text('✓ ' + (r.data.message || 'تمت الترجمة بنجاح!'));
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    status.css('color', '#ef4444').text('✕ ' + (r.data ? r.data.message : 'حدث خطأ أثناء الترجمة.'));
                    btn.prop('disabled', false).find('span').text('✨ ترجمة وتوليد الآن');
                }
            }).fail(function() {
                status.css('color', '#ef4444').text('✕ تعذر الاتصال بالخادم.');
                btn.prop('disabled', false).find('span').text('✨ ترجمة وتوليد الآن');
            });
        }
        </script>
    </div>

    <!-- Upload Form -->
    <div class="kb-table-card" style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,0.04);">
        <h2 style="margin:0 0 24px;font-size:18px;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-upload" style="font-size:20px;width:20px;height:20px;color:#7c3aed;"></span>
            Upload Name Sheet
        </h2>

        <form method="post" enctype="multipart/form-data" action="" id="name-sync-form">
            <?php wp_nonce_field( 'charts_admin_action', '_wpnonce' ); ?>
            <input type="hidden" name="charts_action" value="name_sync_upload">

            <!-- Drop Zone -->
            <div id="ns-dropzone" style="border:2px dashed #c7d2fe;border-radius:12px;padding:48px 32px;text-align:center;cursor:pointer;background:#fafafe;transition:all .2s;" onclick="document.getElementById('ns-file-input').click();">
                <div id="ns-idle">
                    <span class="dashicons dashicons-media-spreadsheet" style="font-size:48px;width:48px;height:48px;color:#7c3aed;display:block;margin:0 auto 16px;"></span>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#1e293b;">اسحب الملف هنا أو انقر للاختيار</p>
                    <p style="margin:8px 0 0;font-size:13px;color:#64748b;">CSV files only</p>
                </div>
                <div id="ns-staged" style="display:none;">
                    <span class="dashicons dashicons-yes-alt" style="font-size:40px;width:40px;height:40px;color:#10b981;display:block;margin:0 auto 12px;"></span>
                    <p id="ns-file-name" style="margin:0;font-size:16px;font-weight:700;color:#1e293b;"></p>
                    <p id="ns-file-size" style="margin:6px 0 0;font-size:13px;color:#64748b;"></p>
                </div>
            </div>
            <input type="file" id="ns-file-input" name="name_sync_file" accept=".csv" style="display:none;">

            <!-- Options -->
            <div style="margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="sync_artists" value="1" checked style="margin-top:3px;width:16px;height:16px;accent-color:#7c3aed;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">تحديث الفنانين</div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يحدث <code>display_name_en</code> و <code>slug</code> للفنانين</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="sync_tracks" value="1" checked style="margin-top:3px;width:16px;height:16px;accent-color:#7c3aed;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">تحديث الأغاني</div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يحدث <code>title_en</code> و <code>slug</code> للأغاني</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="sync_videos" value="1" checked style="margin-top:3px;width:16px;height:16px;accent-color:#7c3aed;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">تحديث الكليبات</div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يحدث <code>title_en</code> و <code>slug</code> للكليبات</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="sync_albums" value="1" checked style="margin-top:3px;width:16px;height:16px;accent-color:#7c3aed;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">تحديث الألبومات</div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يحدث <code>title_en</code> و <code>slug</code> للألبومات</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="overwrite_existing" value="1" style="margin-top:3px;width:16px;height:16px;accent-color:#ef4444;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">Overwrite Existing <span style="color:#ef4444;">⚠</span></div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يكتب فوق الأسماء الإنجليزية الموجودة مسبقاً إذا كانت فارقة</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f8fafc;border-radius:10px;padding:16px;">
                    <input type="checkbox" name="refresh_slugs" value="1" checked style="margin-top:3px;width:16px;height:16px;accent-color:#10b981;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;">Refresh Slugs</div>
                        <div style="font-size:12px;color:#64748b;margin-top:2px;">يعيد توليد الروابط (slugs) بناءً على الاسم الإنجليزي الجديد</div>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div style="margin-top:28px;display:flex;align-items:center;gap:16px;">
                <button type="submit" id="ns-submit-btn" class="kb-btn kb-btn-primary" disabled style="background:#7c3aed;color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:15px;font-weight:800;cursor:not-allowed;opacity:.5;display:flex;align-items:center;gap:10px;transition:all .2s;">
                    <span class="dashicons dashicons-update" style="font-size:18px;width:18px;height:18px;"></span>
                    Start Name Sync
                </button>
                <button type="button" id="ns-remove-btn" style="display:none;background:none;border:1px solid #e2e8f0;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:700;color:#64748b;cursor:pointer;">Remove File</button>
                <span id="ns-progress" style="display:none;font-size:14px;color:#7c3aed;font-weight:600;">⏳ جاري المعالجة...</span>
            </div>
        </form>
    </div>
</div>

<script>
(function($){
    const $zone     = $('#ns-dropzone');
    const $input    = $('#ns-file-input');
    const $idle     = $('#ns-idle');
    const $staged   = $('#ns-staged');
    const $fname    = $('#ns-file-name');
    const $fsize    = $('#ns-file-size');
    const $submit   = $('#ns-submit-btn');
    const $remove   = $('#ns-remove-btn');
    const $progress = $('#ns-progress');

    function stageFile(file) {
        if (!file || !file.name.toLowerCase().endsWith('.csv')) {
            alert('الملف يجب أن يكون CSV فقط.');
            return;
        }
        $idle.hide(); $staged.show();
        $fname.text(file.name);
        $fsize.text((file.size / 1024).toFixed(1) + ' KB');
        $submit.prop('disabled', false).css({opacity: 1, cursor: 'pointer'});
        $remove.show();
        $zone.css({borderColor: '#7c3aed', background: '#f5f3ff'});
    }

    $input.on('change', function(){ stageFile(this.files[0]); });

    $zone.on('dragover', function(e){ e.preventDefault(); $(this).css({borderColor:'#7c3aed', background:'#f5f3ff'}); })
         .on('dragleave', function(){ $(this).css({borderColor:'#c7d2fe', background:'#fafafe'}); })
         .on('drop', function(e){ e.preventDefault(); stageFile(e.originalEvent.dataTransfer.files[0]); $input[0].files = e.originalEvent.dataTransfer.files; });

    $remove.on('click', function(){
        $input.val('');
        $idle.show(); $staged.hide();
        $submit.prop('disabled', true).css({opacity: .5, cursor: 'not-allowed'});
        $remove.hide();
        $zone.css({borderColor:'#c7d2fe', background:'#fafafe'});
    });

    $('#name-sync-form').on('submit', function(){
        $submit.prop('disabled', true).css({opacity:.6});
        $progress.show();
    });
})(jQuery);
</script>
