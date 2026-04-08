/**
 * Admin Logic
 */
jQuery(document).ready(function($) {
    // Handle manual import fetch
    $(document).on('click', '.handle-run-import', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const sourceId = $row.data('source-id');
        
        if ($btn.hasClass('is-loading')) return;
        
        $btn.addClass('is-loading').prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: charts_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'charts_run_import',
                nonce: charts_admin.nonce,
                source_id: sourceId
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Success!').css('background', '#10b981');
                    window.ChartsToast.show('success', response.data.message || 'Import successful!', 'Import Nexus');
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                } else {
                    window.ChartsToast.show('error', response.data.message || response.data || 'Unknown error', 'Sync Failure');
                    $btn.removeClass('is-loading').prop('disabled', false).text('Fetch Now');
                }
            },
            error: function() {
                window.ChartsToast.show('error', 'Server error occurred during fetch.', 'Critical Failure');
                $btn.removeClass('is-loading').prop('disabled', false).text('Fetch Now');
            }
        });
    });

    // WP Media Uploader for Chart Cover
    $(document).on('click', '.charts-upload-trigger', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $input = $('#cover_image_url');
        const $preview = $('#cover_preview');

        const frame = wp.media({
            title: 'Select Chart Cover Image',
            button: { text: 'Use Image' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.url);
            $preview.attr('src', attachment.url).addClass('has-image');
        });

        frame.open();
    });

    $(document).on('click', '.charts-remove-image', function(e) {
        e.preventDefault();
        $('#cover_image_url').val('');
        $('#cover_preview').attr('src', '').removeClass('has-image');
    });

    // Import Center Enhancements - Intelligence Nexus Edition
    const $fileInput = $('#import_file');
    const $dropZone = $('#drop-zone');
    const $nexusIdle = $('.nexus-idle');
    const $nexusStaged = $('.nexus-staged');
    const $removeBtn = $('#remove-file');
    const $submitBtn = $('#run-import-btn');
    const $form = $('#unified-import-form');
    const $chartSelect = $('#chart_id');

    if ($fileInput.length) {
        
        // Helper to validate and stage file
        const stageFile = (file) => {
            if (!file) return;
            
            if (!file.name.toLowerCase().endsWith('.csv')) {
                window.ChartsToast.show('error', 'Only CSV files are supported for intelligence sync.', 'Invalid Segment');
                return;
            }

            $nexusIdle.hide();
            $nexusStaged.show();
            
            const size = (file.size / 1024).toFixed(1) + ' KB';
            $nexusStaged.find('.file-name').text(file.name);
            $nexusStaged.find('.file-meta').text(`${size} • ${file.type || 'text/csv'}`);
            
            $dropZone.addClass('has-file');
            window.ChartsToast.show('success', 'Intelligence layer staged: ' + file.name, 'Dataset Ready');
            checkReadiness();
        };

        // Readiness Engine
        const checkReadiness = () => {
            const hasSource = $('[name="country"]').val() !== '';
            const hasPlatform = $('[name="platform"]:checked').length > 0;
            const hasFile = $fileInput[0].files.length > 0;
            const hasTarget = $chartSelect.val() !== '';

            if (hasSource && hasPlatform && hasFile && hasTarget) {
                $submitBtn.prop('disabled', false);
                $('#readiness-msg').text('Intelligence pipeline polarized. Ready for execution.').css('color', '#10b981');
                $('.import-stage[data-step="4"]').addClass('active');
            } else {
                $submitBtn.prop('disabled', true);
                $('#readiness-msg').text('Please complete all previous steps to begin sync.').css('color', '');
                $('.import-stage[data-step="4"]').removeClass('active');
            }

            // Update active stages visually
            if (hasSource && hasPlatform) $('.import-stage[data-step="2"]').addClass('active');
            if (hasFile) $('.import-stage[data-step="3"]').addClass('active');
        };

        // Input Change
        $fileInput.on('change', function(e) {
            stageFile(e.target.files[0]);
        });

        // Drag & Drop Orchestration
        $dropZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('is-dragover');
        });

        $dropZone.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('is-dragover');
            
            if (e.type === 'drop') {
                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $fileInput[0].files = files;
                    stageFile(files[0]);
                }
            }
        });

        // Reset
        $removeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $fileInput.val('');
            $nexusStaged.hide();
            $nexusIdle.show();
            $dropZone.removeClass('has-file');
            window.ChartsToast.show('warning', 'Dataset purged from staging memory.', 'Nexus Reset');
            checkReadiness();
        });

        // Form Logic
        $chartSelect.on('change', function() {
            const $opt = $(this).find('option:selected');
            if ($opt.val()) {
                $('#item_type').val($opt.data('type'));
                $('#frequency').val($opt.data('frequency'));
                $('#hidden_chart_type').val($opt.data('chart-type'));
                window.ChartsToast.show('info', 'Synchronized target parameters for ' + $opt.text().split('(')[0], 'Context Map');
            }
            checkReadiness();
        });

        $('[name="country"], [name="platform"]').on('change', checkReadiness);

        $form.on('submit', function() {
            $submitBtn.addClass('processing').prop('disabled', true);
            $submitBtn.find('span').text('Syncing Intelligence...');
            $submitBtn.find('.spinner-loader').show();
            window.ChartsToast.show('info', 'Executing sync pipeline. Please remain on this page.', 'Live Ingestion');
        });

        // Initial check
        checkReadiness();
    }

    /**
     * Visual Slide Builder Engine
     */
    const initVisualBuilder = () => {
        $('.kb-visual-builder').each(function() {
            const $wrap = $(this);
            const $list = $wrap.find('.kb-slides-list');
            const $json = $wrap.find('.kb-json-textarea');
            const data  = kcharts_builder_data || { charts: [], artists: [], tracks: [] };

            const loadFromJSON = () => {
                $list.empty();
                try {
                    const slides = JSON.parse($json.val() || '[]');
                    if (Array.isArray(slides)) {
                        slides.forEach(s => addSlide(s));
                    }
                } catch (e) {
                    console.error('Invalid JSON for slides builder', e);
                }
            };

            const syncToJSON = () => {
                const slides = [];
                $list.find('.kb-slide-card').each(function() {
                    const $card = $(this);
                    slides.push({
                        type: $card.find('[name="slide_type"]').val(),
                        source_id: $card.find('[name="slide_source"]').val(),
                        title: $card.find('[name="slide_title"]').val(),
                        subtitle: $card.find('[name="slide_subtitle"]').val(),
                        badge: $card.find('[name="slide_badge"]').val(),
                        image: $card.find('[name="slide_image"]').val(),
                        url: $card.find('[name="slide_url"]').val(),
                        btn_text: $card.find('[name="slide_btn_text"]').val() || 'Learn More'
                    });
                });
                $json.val(JSON.stringify(slides));
            };

            const addSlide = (slide = {}) => {
                const s = {
                    type: slide.type || 'custom',
                    source_id: slide.source_id || '',
                    title: slide.title || 'New Slide',
                    subtitle: slide.subtitle || '',
                    badge: slide.badge || '',
                    image: slide.image || '',
                    url: slide.url || '#',
                    btn_text: slide.btn_text || 'Learn More',
                    ...slide
                };

                const $card = $(`
                    <div class="kb-slide-card">
                        <div class="kb-slide-head">
                            <span class="kb-slide-drag">⠿</span>
                            <img src="${s.image || ''}" class="kb-slide-thumb">
                            <div class="kb-slide-info">
                                <span class="kb-slide-label">${s.title}</span>
                                <span class="kb-slide-meta">${s.type}</span>
                            </div>
                            <div class="kb-slide-actions">
                                <button type="button" class="kb-slide-action kb-toggle-card" title="Edit slide"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                                <button type="button" class="kb-slide-action kb-slide-duplicate" title="Duplicate"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg></button>
                                <button type="button" class="kb-slide-action is-delete kb-slide-delete" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg></button>
                            </div>
                        </div>
                        <div class="kb-slide-body">
                            <div class="kb-slide-grid">
                                <div class="kb-sb-field">
                                    <label>Slide Type</label>
                                    <select name="slide_type" class="kb-input">
                                        <option value="custom" ${s.type === 'custom' ? 'selected' : ''}>Custom Entry</option>
                                        <option value="chart" ${s.type === 'chart' ? 'selected' : ''}>Link to Chart</option>
                                        <option value="artist" ${s.type === 'artist' ? 'selected' : ''}>Featured Artist</option>
                                        <option value="track" ${s.type === 'track' ? 'selected' : ''}>Featured Track</option>
                                    </select>
                                </div>
                                <div class="kb-sb-field field-source" style="${s.type === 'custom' ? 'display:none' : ''}">
                                    <label>Pick Source</label>
                                    <select name="slide_source" class="kb-input">${renderSourceOptions(s.type, s.source_id)}</select>
                                </div>
                                <div class="kb-sb-field">
                                    <label>Title</label>
                                    <input type="text" name="slide_title" value="${s.title}" class="kb-input">
                                </div>
                                <div class="kb-sb-field">
                                    <label>Subtitle / Artist</label>
                                    <input type="text" name="slide_subtitle" value="${s.subtitle}" class="kb-input">
                                </div>
                                <div class="kb-sb-field">
                                    <label>Badge (Optional)</label>
                                    <input type="text" name="slide_badge" value="${s.badge}" class="kb-input">
                                </div>
                                <div class="kb-sb-field">
                                    <label>Button Text</label>
                                    <input type="text" name="slide_btn_text" value="${s.btn_text}" class="kb-input">
                                </div>
                                <div class="kb-sb-field" style="grid-column: span 2;">
                                    <label>Target URL</label>
                                    <input type="text" name="slide_url" value="${s.url}" class="kb-input">
                                </div>
                                <div class="kb-sb-field" style="grid-column: span 2;">
                                    <label>Slide Background</label>
                                    <div class="kb-img-picker">
                                        <img src="${s.image || ''}" class="kb-img-prev">
                                        <div class="kb-img-actions">
                                            <input type="text" name="slide_image" value="${s.image}" class="kb-input" placeholder="https://...">
                                            <button type="button" class="kb-btn kb-btn-outline kb-builder-upload" style="height:32px; padding:0 12px; font-size:11px;">Media Library</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                $list.append($card);
                syncToJSON();
            };

            const renderSourceOptions = (type, selectedId) => {
                let options = '<option value="">Select option...</option>';
                const items = data[type + 's'] || [];
                items.forEach(i => {
                    const label = i.artist ? `${i.title} (${i.artist})` : i.title;
                    options += `<option value="${i.id}" ${i.id == selectedId ? 'selected' : ''}>${label}</option>`;
                });
                return options;
            };

            $wrap.on('click', '.kb-add-slide', () => addSlide());
            $wrap.on('click', '.kb-slide-delete', function() {
                if (confirm('Permanently remove this slide?')) {
                    $(this).closest('.kb-slide-card').remove();
                    syncToJSON();
                }
            });
            $wrap.on('click', '.kb-slide-duplicate', function() {
                const $card = $(this).closest('.kb-slide-card');
                const slideData = {
                    type: $card.find('[name="slide_type"]').val(),
                    source_id: $card.find('[name="slide_source"]').val(),
                    title: $card.find('[name="slide_title"]').val() + ' (Copy)',
                    subtitle: $card.find('[name="slide_subtitle"]').val(),
                    badge: $card.find('[name="slide_badge"]').val(),
                    image: $card.find('[name="slide_image"]').val(),
                    url: $card.find('[name="slide_url"]').val(),
                    btn_text: $card.find('[name="slide_btn_text"]').val()
                };
                addSlide(slideData);
            });

            $wrap.on('click', '.kb-toggle-card, .kb-slide-head', function(e) {
                if ($(e.target).closest('.kb-slide-actions').length) return;
                $(this).closest('.kb-slide-card').toggleClass('is-expanded');
            });

            $wrap.on('click', '.kb-toggle-json', function() {
                $wrap.find('.kb-json-editor').slideToggle();
            });

            $wrap.on('change', '[name="slide_type"]', function() {
                const $card = $(this).closest('.kb-slide-card');
                const type = $(this).val();
                const $sourceField = $card.find('.field-source');
                if (type === 'custom') {
                    $sourceField.hide();
                } else {
                    $sourceField.show().find('select').html(renderSourceOptions(type, ''));
                }
                $card.find('.kb-slide-meta').text(type);
                syncToJSON();
            });

            $wrap.on('change', '[name="slide_source"]', function() {
                const $card = $(this).closest('.kb-slide-card');
                const type = $card.find('[name="slide_type"]').val();
                const id = $(this).val();
                const item = (data[type + 's'] || []).find(i => i.id == id);
                if (item) {
                   if (!$card.find('[name="slide_title"]').val() || $card.find('[name="slide_title"]').val() === 'New Slide') {
                        $card.find('[name="slide_title"]').val(item.title);
                        $card.find('.kb-slide-label').text(item.title);
                   }
                   if (item.artist && !$card.find('[name="slide_subtitle"]').val()) {
                        $card.find('[name="slide_subtitle"]').val(item.artist);
                   }
                }
                syncToJSON();
            });

            $wrap.on('input change', 'input, select', function() {
                if ($(this).hasClass('kb-json-textarea')) return;
                const $card = $(this).closest('.kb-slide-card');
                if ($(this).attr('name') === 'slide_title') {
                    $card.find('.kb-slide-label').text($(this).val());
                }
                if ($(this).attr('name') === 'slide_image') {
                    $card.find('.kb-slide-thumb, .kb-img-prev').attr('src', $(this).val());
                }
                syncToJSON();
            });

            $wrap.on('click', '.kb-builder-upload', function(e) {
                const $btn = $(this);
                const $input = $btn.siblings('input');
                const frame = wp.media({
                    title: 'Select Slide Image',
                    button: { text: 'Use Image' },
                    multiple: false
                });
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url).trigger('input');
                });
                frame.open();
            });

            if ($.fn.sortable) {
                $list.sortable({ handle: '.kb-slide-head', update: () => syncToJSON() });
            }

            loadFromJSON();
        });
    };

    if ($('.kb-visual-builder').length) {
        initVisualBuilder();
    }

    /**
     * Toast Engine
     */
    window.ChartsToast = {
        container: null,
        init() {
            if (!$('#charts-toast-container').length) {
                $('body').append('<div id="charts-toast-container"></div>');
            }
            this.container = $('#charts-toast-container');
            if (window.kcharts_toasts && Array.isArray(window.kcharts_toasts)) {
                window.kcharts_toasts.forEach(t => {
                    setTimeout(() => this.show(t.type, t.message, t.title), 200);
                });
            }
        },
        show(type = 'success', message = '', title = '') {
            if (!this.container) this.init();
            const icons = {
                success: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>',
                error: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
                warning: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                info: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            };
            const $toast = $(`
                <div class="charts-toast is-${type}">
                    <div class="toast-icon"><i>${icons[type]}</i></div>
                    <div class="toast-content">
                        ${title ? `<span class="toast-title">${title}</span>` : ''}
                        <span class="toast-msg">${message}</span>
                    </div>
                    <button type="button" class="toast-close"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                    <div class="toast-progress"><div class="toast-progress-bar"></div></div>
                </div>
            `);
            this.container.append($toast);
            const timer = setTimeout(() => this.dismiss($toast), 5000);
            $toast.find('.toast-close').on('click', () => {
                clearTimeout(timer);
                this.dismiss($toast);
            });
        },
        dismiss($toast) {
            $toast.addClass('is-leaving');
            setTimeout(() => $toast.remove(), 400);
        }
    };

    ChartsToast.init();
});
