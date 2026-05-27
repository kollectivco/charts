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
            title: 'Select List Cover Image',
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

    function updateImportSummary() {
        const platform = $('input[name="platform"]:checked').val() || 'soundcharts';
        const chartLabel = $('#chart_id option:selected').text() || 'No chart selected yet';
        $('#charts-import-summary-target').text(chartLabel);
        $('#charts-import-summary-source').text(
            platform === 'soundcharts' ? 'Soundcharts API'
                : (platform === 'youtube' ? 'YouTube CSV' : 'Spotify CSV')
        );
        $('#csv-upload-panel').toggle(platform !== 'soundcharts');
        $('#charts-preview-btn').toggle(platform === 'soundcharts');
        $('#import_file').prop('required', platform !== 'soundcharts');
    }

    $(document).on('change', 'input[name="platform"]', updateImportSummary);
    $(document).on('change', '#chart_id', function() {
        const selected = $(this).find('option:selected');
        $('#country').val(selected.data('country') || $('#country').val());
        $('#chart_type').val(selected.data('chart-type') || $('#chart_type').val());
        $('#frequency').val(selected.data('frequency') || $('#frequency').val());
        $('#preset_key').val(selected.data('preset') || $('#preset_key').val());
        updateImportSummary();
    });

    $(document).on('change', '#import_file', function() {
        const file = this.files && this.files[0];
        if (!file) {
            $('.file-preview').hide();
            $('.upload-content').show();
            return;
        }
        $('.file-preview .filename').text(file.name);
        $('.file-preview').css('display', 'inline-flex');
        $('.upload-content').hide();
    });

    $(document).on('click', '.remove-file', function(e) {
        e.preventDefault();
        $('#import_file').val('');
        $('.file-preview').hide();
        $('.upload-content').show();
    });

    $('#charts-preview-btn').on('click', function(e) {
        e.preventDefault();

        const payload = {
            action: 'charts_soundcharts_preview_import',
            nonce: charts_admin.nonce,
            chart_id: $('#chart_id').val(),
            country: $('#country').val(),
            chart_type: $('#chart_type').val(),
            frequency: $('#frequency').val(),
            period_date: $('#period_date').val(),
            preset_key: $('#preset_key').val()
        };

        const $btn = $(this);
        const $results = $('#charts-preview-results');
        $btn.prop('disabled', true).text('Loading Preview...');
        $results.hide().empty();

        $.post(charts_admin.ajax_url, payload).done(function(response) {
            if (!response.success) {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Preview failed.') + '</p></div>').show();
                return;
            }

            const rows = response.data.rows || [];
            let html = '<div class="charts-preview-meta">';
            html += '<p><strong>Total rows:</strong> ' + response.data.total_rows + '</p>';
            html += '<p><strong>Requests:</strong> ' + response.data.request_count + '</p>';
            html += '<p><strong>Endpoint:</strong> ' + response.data.endpoint_path + '</p>';
            html += '<p><strong>Mode:</strong> Preview / Dry-run safe</p>';
            html += '</div>';

            if (!rows.length) {
                html += '<p>No rows returned.</p>';
            } else {
                html += '<table class="charts-table"><thead><tr><th>Rank</th><th>Artist</th><th>Track</th><th>Existing</th><th>Incoming</th><th>Country</th></tr></thead><tbody>';
                rows.forEach(function(row) {
                    const existing = row.existing ? ('#' + (row.existing.rank_position || '—') + ' / ' + (row.existing.weeks_on_chart || '—') + 'w') : 'New item';
                    const incoming = '#' + (row.rank_position || '—') + ' / ' + (row.album_title || 'No album');
                    html += '<tr><td>' + (row.rank_position || '') + '</td><td>' + (row.artist_name || '') + '</td><td>' + (row.track_title || '') + '</td><td>' + existing + '</td><td>' + incoming + '</td><td>' + (row.country_code || '') + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            $results.html(html).show();
        }).fail(function() {
            $results.html('<div class="notice notice-error inline"><p>Preview request failed.</p></div>').show();
        }).always(function() {
            $btn.prop('disabled', false).text('Preview Soundcharts Rows');
        });
    });

    $(document).on('click', '.charts-run-now', function(e) {
        e.preventDefault();
        const chartId = $(this).data('chart-id');
        const $btn = $(this);
        $btn.prop('disabled', true);
        $.post(charts_admin.ajax_url, {
            action: 'charts_soundcharts_run_now',
            nonce: charts_admin.nonce,
            chart_id: chartId
        }).done(function(response) {
            alert(response.success ? (response.data.message || 'Sync completed.') : ((response.data && response.data.message) || 'Sync failed.'));
            if (response.success) {
                location.reload();
            }
        }).fail(function() {
            alert('Run now request failed.');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    updateImportSummary();
});
