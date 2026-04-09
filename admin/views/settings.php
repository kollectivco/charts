<?php
/**
 * Settings UI - Production-Grade Bento Settings Center.
 * Driven by the canonical Settings architecture (Settings::get, Settings::update).
 */

use Charts\Core\Settings;

$settings = Settings::get_all();

// 1. Panel Definition (The UI Schema)
$panel = [
    'homepage' => [
        'title' => 'Charts Home',
        'sections' => [
            'layout' => [
                'title' => 'Structural Strategy',
                'fields' => [
                    [ 'id' => 'homepage.layout', 'type' => 'select', 'label' => 'Display Mode', 'options' => ['standard' => 'Full Cinematic Billboard', 'minimal' => 'Focused Grid Minimalist'] ],
                    [ 'id' => 'homepage.show_featured_row', 'type' => 'switch', 'label' => 'Show Featured Insight Section' ],
                    [ 'id' => 'homepage.show_artists_row', 'type' => 'switch', 'label' => 'Show Trending Artists Row' ],
                    [ 'id' => 'homepage.show_charts_grid', 'type' => 'switch', 'label' => 'Show Public Charts Browse Grid' ],
                ]
            ],
            'design' => [
                'title' => 'Aesthetic Spacing',
                'fields' => [
                    [ 'id' => 'homepage.padding_top', 'type' => 'number', 'label' => 'Top Buffer (px)', 'max' => 200 ],
                    [ 'id' => 'homepage.section_spacing', 'type' => 'number', 'label' => 'Gap Between Blocks (px)', 'max' => 200 ],
                    [ 'id' => 'homepage.section_order', 'type' => 'text', 'label' => 'Stack Arrangement', 'desc' => 'Comma separated: slider, artists, charts' ],
                ]
            ]
        ]
    ],
    'billboard' => [
        'title' => 'Hero Billboard',
        'sections' => [
            'engine' => [
                'title' => 'Billboard Engine',
                'fields' => [
                    [ 'id' => 'slider.enable', 'type' => 'switch', 'label' => 'Activate Hero Billboard' ],
                    [ 'id' => 'slider.source_mode', 'type' => 'select', 'label' => 'Content Strategy', 'options' => [
                        'latest'         => 'Automatic: Latest Chart Updates',
                        'selected'       => 'Curated: Selected Charts',
                        'selection_top'  => 'Trending: #1 From Selections',
                        'artists'        => 'Focus: Selected Artists',
                        'tracks'         => 'Focus: Selected Tracks',
                        'manual'         => 'Mixed: Manual Slide Builder'
                    ]],
                    [ 'id' => 'slider.selected_charts', 'type' => 'multiselect', 'label' => 'Choose Target Charts', 'options' => 'charts', 'show_on' => 'slider.source_mode:selected,selection_top' ],
                    [ 'id' => 'slider.selected_artists', 'type' => 'multiselect', 'label' => 'Choose Featured Artists', 'options' => 'artists', 'show_on' => 'slider.source_mode:artists' ],
                    [ 'id' => 'slider.selected_tracks', 'type' => 'multiselect', 'label' => 'Choose Featured Tracks', 'options' => 'tracks', 'show_on' => 'slider.source_mode:tracks' ],
                ]
            ],
            'manual' => [
                'title' => 'Manual Slide Builder',
                'fields' => [
                    [ 'id' => 'slider.manual_slides_json', 'type' => 'builder', 'label' => 'Slides Manifest', 'show_on' => 'slider.source_mode:manual' ],
                ]
            ],
            'visuals' => [
                'title' => 'Billboard Style',
                'fields' => [
                    [ 'id' => 'slider.height_vh', 'type' => 'number', 'label' => 'Desktop Height (vh)', 'min' => 30, 'max' => 100 ],
                    [ 'id' => 'slider.mobile_height_vh', 'type' => 'number', 'label' => 'Mobile Height (vh)', 'min' => 30, 'max' => 100 ],
                    [ 'id' => 'slider.max_width_px', 'type' => 'number', 'label' => 'Edge Limitation (px)', 'min' => 800, 'max' => 2400 ],
                    [ 'id' => 'slider.border_radius_px', 'type' => 'number', 'label' => 'Card Curvature (px)', 'min' => 0, 'max' => 60 ],
                    [ 'id' => 'slider.overlay_opacity_pct', 'type' => 'range', 'label' => 'Gradient Stealth Intensity (%)', 'min' => 0, 'max' => 100 ],
                ]
            ],
            'motion' => [
                'title' => 'Transitions & Timing',
                'fields' => [
                    [ 'id' => 'slider.autoplay', 'type' => 'switch', 'label' => 'Automated Carousel Motion' ],
                    [ 'id' => 'slider.loop', 'type' => 'switch', 'label' => 'Infinite Loop Mode' ],
                    [ 'id' => 'slider.pause_on_hover', 'type' => 'switch', 'label' => 'Pause Motion on Hover' ],
                    [ 'id' => 'slider.delay_ms', 'type' => 'number', 'label' => 'Slide Dwell Time (ms)', 'min' => 1000, 'max' => 30000 ],
                    [ 'id' => 'slider.speed_ms', 'type' => 'number', 'label' => 'Transition Velocity (ms)', 'min' => 200, 'max' => 2000 ],
                ]
            ]
        ]
    ],
    'design' => [
        'title' => 'Design System',
        'sections' => [
            'branding' => [
                'title' => 'Core Signature',
                'fields' => [
                    [ 'id' => 'design.mode', 'type' => 'select', 'label' => 'Environment Mode', 'options' => ['light' => 'Pristine Light', 'dark' => 'Deep Onyx', 'system' => 'OS Adaptive Mode'] ],
                    [ 'id' => 'design.primary_color', 'type' => 'color', 'label' => 'Brand Accent (Primary)' ],
                    [ 'id' => 'design.accent_color', 'type' => 'color', 'label' => 'Detail/Link Accent (Secondary)' ],
                    [ 'id' => 'branding.show_logo', 'type' => 'switch', 'label' => 'Display Brand Logo' ],
                    [ 'id' => 'branding.show_nav', 'type' => 'switch', 'label' => 'Display Primary Navigation' ],
                    [ 'id' => 'branding.show_search', 'type' => 'switch', 'label' => 'Activate Global Search' ],
                ]
            ],
            'labels' => [
                'title' => 'Copy & Labels',
                'fields' => [
                    [ 'id' => 'labels.header_wordmark', 'type' => 'text', 'label' => 'Header Brand Text' ],
                    [ 'id' => 'labels.chart_cta_text', 'type' => 'text', 'label' => 'Chart CTA Button Copy' ],
                    [ 'id' => 'labels.footer_wordmark', 'type' => 'text', 'label' => 'Footer Wordmark' ],
                    [ 'id' => 'labels.footer_left', 'type' => 'text', 'label' => 'Footer Copyright/Left' ],
                    [ 'id' => 'labels.footer_right', 'type' => 'text', 'label' => 'Footer Attribution/Right' ],
                ]
            ],
            'typography' => [
                'title' => 'Global UI',
                'fields' => [
                    [ 'id' => 'design.card_radius_px', 'type' => 'number', 'label' => 'Standard Card Radius (px)', 'max' => 60 ],
                ]
            ]
        ]
    ],
    'integrations' => [
        'title' => 'Service Nexus',
        'sections' => [
            'spotify' => [
                'title' => 'Spotify Ecosystem',
                'fields' => [
                    [ 'id' => 'api.spotify_client_id', 'type' => 'text', 'label' => 'Spotify Client ID' ],
                    [ 'id' => 'api.spotify_client_secret', 'type' => 'password', 'label' => 'Spotify Client Secret' ],
                ]
            ],
            'youtube' => [
                'title' => 'YouTube Analytics',
                'fields' => [
                    [ 'id' => 'api.youtube_api_key', 'type' => 'password', 'label' => 'Google Cloud API Key' ],
                ]
            ]
        ]
    ],
    'operations' => [
        'title' => 'Operations',
        'sections' => [
            'tools' => [
                'title' => 'System Maintenance',
                'fields' => [
                    [ 'id' => 'maint_backfill', 'type' => 'custom', 'html' => '<button type="button" class="kb-btn kb-btn-outline" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=backfill_media_v2&_wpnonce='.wp_create_nonce('kcharts_save_v2')).'\'">Backfill Missing Media</button>' ],
                    [ 'id' => 'maint_integrity', 'type' => 'custom', 'html' => '<button type="button" class="kb-btn kb-btn-outline" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=run_integrity_check_v2&_wpnonce='.wp_create_nonce('kcharts_save_v2')).'\'">Reconcile Entity Links</button>' ],
                ]
            ],
            'danger' => [
                'title' => 'Structural Reset',
                'fields' => [
                    [ 'id' => 'maint_danger', 'type' => 'custom', 'html' => '
                        <div class="kb-danger-zone">
                            <p>To destroy all charts, entries, and data, type <strong>RESET CHARTS</strong> below.</p>
                            <input type="text" name="confirm_reset" id="reset_confirm_input" placeholder="Type confirmation here..." class="kb-input" style="border-color:#fecaca;">
                            <label style="margin-top:16px; display:block;"><input type="checkbox" name="wipe_settings" value="1"> Also purge configuration logic</label>
                            <button type="submit" name="charts_action" value="reset_plugin_v2" id="reset_plugin_btn" class="kb-btn" style="margin-top:20px; background:#ef4444; color:#fff; width:100%; opacity:0.5; pointer-events:none;">PURGE SYSTEM DATA</button>
                        </div>
                    ' ],
                ]
            ]
        ]
    ]
];

// 2. Specialized Field Renderers
function kc_render_bento_field( $field ) {
    $id    = $field['id'];
    $val   = \Charts\Core\Settings::get($id);
    $name  = "kc_opt[" . str_replace('.', '][', $id) . "]"; // Nested name mapping
    $show  = isset($field['show_on']) ? ' data-show-on="'.esc_attr($field['show_on']).'"' : '';

    echo '<div class="kb-field-wrap"'. $show .'>';
    if ( !empty($field['label']) && $field['type'] !== 'switch' ) {
        echo '<label>' . esc_html($field['label']) . '</label>';
    }

    switch ( $field['type'] ) {
        case 'custom':
            echo $field['html'];
            break;
        case 'text':
        case 'password':
            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" class="kb-input">';
            break;
            
        case 'number':
            echo '<input type="number" name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" min="' . esc_attr($field['min'] ?? 0) . '" max="' . esc_attr($field['max'] ?? 1000) . '" class="kb-input">';
            break;

        case 'select':
            echo '<select name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="kb-input">';
            foreach ( $field['options'] as $v => $l ) {
                echo '<option value="' . esc_attr($v) . '" ' . selected($val, $v, false) . '>' . esc_html($l) . '</option>';
            }
            echo '</select>';
            break;

        case 'multiselect':
            global $wpdb;
            $options = [];
            if ( $field['options'] === 'charts' ) {
                $results = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}charts_definitions ORDER BY title ASC");
                foreach($results as $r) $options[$r->id] = $r->title;
            } elseif ( $field['options'] === 'artists' ) {
                $results = $wpdb->get_results("SELECT id, display_name as title FROM {$wpdb->prefix}charts_artists ORDER BY display_name ASC LIMIT 100");
                foreach($results as $r) $options[$r->id] = $r->title;
            } elseif ( $field['options'] === 'tracks' ) {
                $results = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}charts_tracks ORDER BY title ASC LIMIT 100");
                foreach($results as $r) $options[$r->id] = $r->title;
            }
            
            $val = is_array($val) ? $val : [];
            echo '<select name="' . esc_attr($name) . '[]" class="kb-input kb-multiselect" multiple style="height: 120px; padding: 10px;">';
            foreach ( $options as $v => $l ) {
                echo '<option value="' . esc_attr($v) . '" ' . (in_array($v, $val) ? 'selected' : '') . '>' . esc_html($l) . '</option>';
            }
            echo '</select>';
            echo '<p class="kb-field-desc">Hold Ctrl/Cmd to select multiple.</p>';
            break;

        case 'builder':
            echo '<div class="kb-builder-wrap">';
            echo '<textarea name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" class="kb-input" style="height: 180px; font-family: monospace; font-size: 12px; padding: 15px;">' . esc_textarea($val) . '</textarea>';
            echo '<p class="kb-field-desc">Input a JSON array of slide objects. Example: [{"title":"Hot Release","subtitle":"Artist Name","image":"URL","url":"#"}]</p>';
            echo '</div>';
            break;

        case 'switch':
            echo '<label class="kb-toggle">';
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
            echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(1, $val, false) . '>';
            echo '<span class="kb-toggle-slider"></span>';
            echo '<span class="kb-toggle-label">' . esc_html($field['label']) . '</span>';
            echo '</label>';
            break;

        case 'color':
            echo '<div class="kb-color-group">';
            echo '<input type="color" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '">';
            echo '<input type="text" value="' . esc_attr($val) . '" class="kb-input kb-color-hex" onchange="this.previousElementSibling.value=this.value">';
            echo '</div>';
            break;

        case 'range':
            echo '<div class="kb-range-group">';
            echo '<input type="range" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '" min="' . esc_attr($field['min'] ?? 0) . '" max="' . esc_attr($field['max'] ?? 100) . '" oninput="this.nextElementSibling.innerText=this.value">';
            echo '<span class="kb-range-badge">' . esc_html($val) . '%</span>';
            echo '</div>';
            break;
    }

    if ( !empty($field['desc']) ) {
        echo '<p class="kb-field-desc">' . esc_html($field['desc']) . '</p>';
    }
    echo '</div>';
}
?>

<div class="wrap kc-settings-wrap premium-bento">
    <form method="post" action="" id="kc-main-settings-form">
        <?php wp_nonce_field( 'kcharts_save_v2' ); ?>
        <div class="kc-settings-header">
            <div class="kc-branding">
                <h1 class="kc-title"><?php _e( 'Settings Nexus', 'charts' ); ?></h1>
                <p class="kc-subtitle"><?php _e( 'Product Logic & Experience Orchestration', 'charts' ); ?></p>
            </div>
            <div class="kc-header-actions">
                <button type="submit" name="charts_action" value="save_settings_v2" class="kb-btn kb-btn-primary"><?php _e( 'Synchronize Changes', 'charts' ); ?></button>
            </div>
        </div>


        <div class="kc-settings-layout">
            <!-- Sidebar Nav -->
            <nav class="kc-settings-nav">
                <?php $first = true; foreach ( $panel as $id => $tab ) : ?>
                    <button type="button" class="kb-nav-item <?php echo $first ? 'is-active' : ''; ?>" data-tab="<?php echo $id; ?>">
                        <span class="kb-nav-indicator"></span>
                        <?php echo esc_html($tab['title']); ?>
                    </button>
                <?php $first = false; endforeach; ?>
            </nav>

            <!-- Panels Section -->
            <main class="kc-settings-panels">
                <?php $first = true; foreach ( $panel as $tab_id => $tab ) : ?>
                    <div id="panel-<?php echo $tab_id; ?>" class="kb-panel <?php echo $first ? 'is-active' : ''; ?>">
                        <div class="kb-grid">
                            <?php foreach ( $tab['sections'] as $sec_id => $sec ) : ?>
                                <div class="kb-card">
                                    <h3 class="kb-card-title"><?php echo esc_html($sec['title']); ?></h3>
                                    <div class="kb-card-body">
                                        <?php foreach ( $sec['fields'] as $field ) : ?>
                                            <?php kc_render_bento_field($field); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $first = false; endforeach; ?>
            </main>
        </div>
    </form>
</div>

<style>
:root {
    --kb-primary: <?php echo esc_attr(Settings::get('design.primary_color', '#fe025b')); ?>;
    --kb-bg: #f8fafc;
    --kb-card: #ffffff;
    --kb-border: #e2e8f0;
    --kb-text: #1e293b;
    --kb-text-dim: #64748b;
    --kb-radius: 16px;
    --kb-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.kc-settings-wrap.premium-bento { max-width: 1400px; margin: 40px auto; padding: 0 20px; font-family: -apple-system, system-ui, sans-serif; visibility: hidden; }
.kc-settings-wrap.premium-bento.is-ready { visibility: visible; }

.kc-settings-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--kb-border); }
.kc-title { margin: 0; font-size: 32px; font-weight: 950; letter-spacing: -0.04em; color: var(--kb-text); }
.kc-subtitle { margin: 4px 0 0; color: var(--kb-text-dim); font-size: 14px; font-weight: 600; }

.kc-settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 40px; }

/* Nav */
.kc-settings-nav { display: flex; flex-direction: column; gap: 8px; position: sticky; top: 100px; height: fit-content; }
.kb-nav-item { background: transparent; border: none; padding: 12px 20px; text-align: left; font-weight: 800; font-size: 14px; color: var(--kb-text-dim); cursor: pointer; border-radius: 12px; transition: all 0.2s; position: relative; }
.kb-nav-item:hover { background: rgba(0,0,0,0.02); }
.kb-nav-item.is-active { background: #fff; color: var(--kb-primary); box-shadow: var(--kb-shadow); }
.kb-nav-indicator { position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 0px; height: 16px; background: var(--kb-primary); border-radius: 0 4px 4px 0; transition: width 0.2s; }
.kb-nav-item.is-active .kb-nav-indicator { width: 4px; }

/* Panels */
.kb-panel { display: none; }
.kb-panel.is-active { display: block; animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.kb-danger-zone { background: #fff1f2; border: 1px solid #fecaca; padding: 24px; border-radius: 12px; color: #991b1b; }
.kb-btn-outline { background: transparent; border: 1.5px solid var(--kb-border); color: var(--kb-text); }
.kb-btn-outline:hover { background: var(--kb-bg); border-color: var(--kb-primary); }

@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.kb-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
.kb-card { background: #fff; border-radius: var(--kb-radius); border: 1px solid var(--kb-border); padding: 30px; box-shadow: var(--kb-shadow); }
.kb-card-title { margin: 0 0 24px; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: var(--kb-text-dim); border-bottom: 2px solid var(--kb-bg); padding-bottom: 12px; display: inline-block; }

/* Fields */
.kb-field-wrap { margin-bottom: 24px; }
.kb-field-wrap label { display: block; font-size: 13px; font-weight: 700; color: var(--kb-text); margin-bottom: 8px; }
.kb-input { width: 100%; height: 48px; border: 1.5px solid var(--kb-border); border-radius: 10px; padding: 0 16px; font-size: 14px; color: var(--kb-text); font-weight: 600; background: var(--kb-bg); transition: border-color 0.2s; }
.kb-input:focus { border-color: var(--kb-primary); outline: none; background: #fff; }

.kb-toggle { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.kb-toggle input { display: none; }
.kb-toggle-slider { width: 40px; height: 22px; background: #cbd5e1; border-radius: 100px; position: relative; transition: background 0.2s; }
.kb-toggle-slider::after { content: ''; position: absolute; left: 4px; top: 4px; width: 14px; height: 14px; background: #fff; border-radius: 50%; transition: transform 0.2s; }
.kb-toggle input:checked + .kb-toggle-slider { background: var(--kb-primary); }
.kb-toggle input:checked + .kb-toggle-slider::after { transform: translateX(18px); }
.kb-toggle-label { font-size: 14px; font-weight: 700; color: var(--kb-text); }

.kb-btn { border: none; padding: 0 30px; height: 52px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: all 0.2s; }
.kb-btn-primary { background: var(--kb-primary); color: #fff; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.kb-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }

.kb-field-desc { margin: 8px 0 0; font-size: 11px; font-weight: 600; color: var(--kb-text-dim); }

.kb-color-group { display: flex; gap: 10px; }
.kb-color-group input[type="color"] { width: 48px; height: 48px; padding: 0; border: 1px solid var(--kb-border); border-radius: 8px; cursor: pointer; }
.kb-color-hex { flex: 1; text-transform: uppercase; }

.kb-range-group { display: flex; align-items: center; gap: 15px; }
.kb-range-group input { flex: 1; accent-color: var(--kb-primary); }
.kb-range-badge { font-weight: 800; color: var(--kb-primary); font-size: 14px; min-width: 40px; }
</style>

<script>
jQuery(document).ready(function($) {
    $('.kc-settings-wrap').addClass('is-ready');

    // Nav Switch
    $('.kb-nav-item').on('click', function() {
        $('.kb-nav-item').removeClass('is-active');
        $(this).addClass('is-active');

        $('.kb-panel').removeClass('is-active');
        var target = $(this).data('tab');
        $('#panel-' + target).addClass('is-active');
        
        window.location.hash = target;
    });

    // Hash Logic
    var hash = window.location.hash.substring(1);
    if (hash && $('.kb-nav-item[data-tab="'+hash+'"]').length) {
        $('.kb-nav-item[data-tab="'+hash+'"]').trigger('click');
    }

    // Reset Lock Logic
    $('#reset_confirm_input').on('input', function() {
        var val = $(this).val();
        if (val === 'RESET CHARTS') {
            $('#reset_plugin_btn').css({ 'opacity': 1, 'pointer-events': 'auto' });
        } else {
            $('#reset_plugin_btn').css({ 'opacity': 0.5, 'pointer-events': 'none' });
        }
    });

    // Conditional Visibility Engine
    function syncConditionalVisibility() {
        $('[data-show-on]').each(function() {
            var $el = $(this);
            var condition = $el.data('show-on'); // slider.source_mode:manual
            var parts = condition.split(':');
            var targetId = parts[0];
            var allowedValues = parts[1].split(',');

            var $target = $('[id="'+targetId+'"], [name="kc_opt['+targetId.replace(/\./g, '][')+']"]');
            var currentVal = $target.val();

            if (allowedValues.indexOf(currentVal) !== -1) {
                $el.show();
            } else {
                $el.hide();
            }
        });
    }

    $('select, input').on('change input', syncConditionalVisibility);
    syncConditionalVisibility();

    // Color Field Sync
    $(document).on('input', 'input[type="color"]', function() {
        $(this).next('.kb-color-hex').val($(this).val());
    });
    $(document).on('input', '.kb-color-hex', function() {
        var val = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(val)) {
            $(this).prev('input[type="color"]').val(val);
        }
    });
});
</script>
