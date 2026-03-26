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
});
