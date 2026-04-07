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
                    alert(response.data.message || 'Import successful!');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + (response.data.message || response.data || 'Unknown error'));
                    $btn.removeClass('is-loading').prop('disabled', false).text('Fetch Now');
                }
            },
            error: function() {
                alert('Server error occurred.');
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

    /**
     * Visual Slide Builder Engine
     */
    const initVisualBuilder = () => {
        $('.kb-visual-builder').each(function() {
            const $wrap = $(this);
            const $list = $wrap.find('.kb-slides-list');
            const $json = $wrap.find('.kb-json-textarea');
            const data  = kcharts_builder_data || { charts: [], artists: [], tracks: [] };

            // 1. Initial Load
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

            // Listeners
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

            // Media Picker
            $wrap.on('click', '.kb-builder-upload', function(e) {
                const $btn = $(this);
                const $input = $btn.siblings('input');
                const $card = $btn.closest('.kb-slide-card');
                
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

            // Sortable
            if ($.fn.sortable) {
                $list.sortable({
                    handle: '.kb-slide-head',
                    update: () => syncToJSON()
                });
            }

            // Sync from Raw JSON changes
            $json.on('input', function() {
                // Throttle this or use focusout
            });

            loadFromJSON();
        });
    };

    if ($('.kb-visual-builder').length) {
        initVisualBuilder();
    }
});
