<?php
/**
 * Settings View - Kontentainment Charts Premium Modular Control Panel
 * Expanded with Homepage & Slider Manager
 */

// --- 1. Fetch Dependencies & Helpers ---
// --- 1. Fetch Dependencies & Helpers ---
// Menus and Branding Shell logic removed.

// Ensure option fallback securely
if ( ! function_exists( 'kc_get_setting' ) ) {
	function kc_get_setting( $id, $default = '' ) {
		return \Charts\Core\Settings::get( $id, $default );
	}
}

// Diagnostic helper
if ( isset($_GET['diag']) ) {
    echo '<div style="background:#000; color:#0f0; padding:20px; font-family:monospace; margin-bottom:20px; border-radius:10px; border:2px solid #0f0; overflow:auto; max-height:400px;">';
    echo '<h3>--- CHARTS DIAGNOSTICS ---</h3>';
    echo 'DB Version: ' . get_option('kcharts_db_version') . '<br>';
    echo 'Slider Style: ' . kc_get_setting('slider_style') . '<br>';
    echo 'Registered Keys: ' . count($registered_keys) . '<br>';
    echo '--- POST DATA ---<br>';
    print_r($_POST);
    echo '</div>';
}

// Data Source for Charts
$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
$chart_options = [];
foreach ( $definitions as $def ) { $chart_options[$def->id] = $def->title; }

// --- 2. Registration Engine Array ---
// --- 2. Registration Engine Array ---
$charts_panel = [
	'homepage' => [
		'title' => 'Homepage Layout',
		'icon'  => 'dashicons-admin-home',
		'sections' => [
			'structure' => [
				'title' => 'Homepage Structure',
				'fields' => [
					[ 'id' => 'homepage_layout', 'type' => 'select', 'label' => 'Homepage Style', 'options' => ['standard' => 'Standard Billboard', 'minimal' => 'Minimal Focus'], 'default' => 'standard' ],
					[ 'id' => 'homepage_show_featured', 'type' => 'switch', 'label' => 'Show Featured Section', 'default' => 1 ],
					[ 'id' => 'homepage_show_artists', 'type' => 'switch', 'label' => 'Show Artists Row', 'default' => 1 ],
					[ 'id' => 'homepage_show_more', 'type' => 'switch', 'label' => 'Show "More Charts" Grid', 'default' => 1 ],
					[ 'id' => 'homepage_section_order', 'type' => 'text', 'label' => 'Section Sorting', 'default' => 'slider,artists,charts', 'desc' => 'Comma separated keys: slider, artists, charts' ],
				]
			]
		]
	],
	'slider' => [
		'title' => 'Slider System',
		'icon'  => 'dashicons-images-alt2',
		'sections' => [
			'source' => [
				'title' => 'Content Source',
				'fields' => [
					[ 'id' => 'slider_enable', 'type' => 'switch', 'label' => 'Enable Hero Slider', 'default' => 1 ],
					[ 'id' => 'slider_source_mode', 'type' => 'select', 'label' => 'Slide Selection Mode', 'options' => ['dynamic' => 'Dynamic (Latest Entries)', 'manual' => 'Manual (Curated Slides)'], 'default' => 'dynamic' ],
                ]
            ],
            'manual_manager' => [
                'title' => 'Manual Slide Manager',
                'desc' => 'Define custom slides when Manual Source Mode is active.',
                'fields' => [
                    [ 'id' => 'slider_manual_slides', 'type' => 'slides_manager', 'label' => 'Slides' ],
                ]
            ],
            'style' => [
                'title' => 'Visual Style',
                'fields' => [
					[ 'id' => 'slider_style', 'type' => 'select', 'label' => 'Slider Style', 'options' => ['coverflow'=>'Coverflow 3D','stacked'=>'Stacked Cards','minimal'=>'Minimal Motion'], 'default' => 'coverflow' ],
					[ 'id' => 'slider_radius', 'type' => 'text', 'label' => 'Card Border Radius', 'default' => '16px' ],
					[ 'id' => 'slider_overlay', 'type' => 'range', 'label' => 'Slide Overlay Darken', 'default' => 0.5, 'min' => 0, 'max' => 1, 'step' => 0.1 ],
                ]
            ],
			'playback' => [
				'title' => 'Playback Controls',
				'fields' => [
					[ 'id' => 'slider_count', 'type' => 'number', 'label' => 'Max Slides Count', 'default' => 5 ],
					[ 'id' => 'slider_autoplay', 'type' => 'switch', 'label' => 'Enable Autoplay', 'default' => 1 ],
					[ 'id' => 'slider_delay', 'type' => 'number', 'label' => 'Autoplay Delay (ms)', 'default' => 3000 ],
					[ 'id' => 'slider_speed', 'type' => 'number', 'label' => 'Transition Speed (ms)', 'default' => 600 ],
				]
			],
			'motion' => [
				'title' => 'Motion Engine',
				'fields' => [
					[ 'id' => 'slider_depth', 'type' => 'number', 'label' => '3D Depth Strength', 'default' => 150 ],
					[ 'id' => 'slider_rotation', 'type' => 'number', 'label' => '3D Rotation Angle', 'default' => 45 ],
					[ 'id' => 'slider_opacity', 'type' => 'range', 'label' => 'Inactive Slide Opacity', 'default' => 0.6, 'min' => 0, 'max' => 1, 'step' => 0.1 ],
					[ 'id' => 'slider_scale', 'type' => 'range', 'label' => 'Inactive Slide Scale', 'default' => 0.8, 'min' => 0, 'max' => 1, 'step' => 0.1 ],
					[ 'id' => 'slider_shadow', 'type' => 'range', 'label' => 'Box Margin Shadow', 'default' => 0.3, 'min' => 0, 'max' => 1, 'step' => 0.1 ],
					[ 'id' => 'slider_glow', 'type' => 'switch', 'label' => 'Enable Glow Effect', 'default' => 1 ],
				]
			],
			'layout_specs' => [
				'title' => 'Layout & Framing',
				'fields' => [
					[ 'id' => 'slider_max_width', 'type' => 'text', 'label' => 'Hero Max Width', 'default' => '1440px' ],
					[ 'id' => 'slider_min_height', 'type' => 'text', 'label' => 'Hero Min Height', 'default' => '500px' ],
					[ 'id' => 'slider_aspect_ratio', 'type' => 'text', 'label' => 'Slide Aspect Ratio', 'default' => '16/9' ],
					[ 'id' => 'slider_align', 'type' => 'select', 'label' => 'Vertical Alignment', 'options' => ['center'=>'Center','flex-start'=>'Top Alignment'], 'default' => 'center' ],
					[ 'id' => 'slider_mobile_mode', 'type' => 'select', 'label' => 'Mobile Transition', 'options' => ['stack'=>'Stack View','hidden'=>'Hide Hero on Mobile'], 'default' => 'stack' ],
				]
			],
			'content_viz' => [
				'title' => 'Content Visibility',
				'fields' => [
					[ 'id' => 'slider_cta_text', 'type' => 'text', 'label' => 'Button Text', 'default' => 'VIEW CHART' ],
				]
			]
		]
	],
	'premium_slider' => [
		'title' => 'Premium Hero Slider',
		'icon'  => 'dashicons-video-alt3',
		'sections' => [
			'p_toggle' => [
				'title' => 'Billboard Engine',
				'fields' => [
					[ 'id' => 'slider_premium_enable', 'type' => 'switch', 'label' => 'Enable Premium Billboard Slider', 'default' => 0 ],
				]
			],
			'p_manager' => [
				'title' => 'Slides Content',
				'fields' => [
					[ 'id' => 'slider_premium_slides', 'type' => 'premium_slides_manager', 'label' => 'Manage Billboard Slides' ],
				]
			],
			'p_behavior' => [
				'title' => 'Slider Behavior',
				'fields' => [
					[ 'id' => 'slider_premium_autoplay', 'type' => 'switch', 'label' => 'Autoplay Slides', 'default' => 1 ],
					[ 'id' => 'slider_premium_delay', 'type' => 'number', 'label' => 'Autoplay Delay (ms)', 'default' => 5000 ],
					[ 'id' => 'slider_premium_speed', 'type' => 'number', 'label' => 'Transition Speed (ms)', 'default' => 800 ],
					[ 'id' => 'slider_premium_loop', 'type' => 'switch', 'label' => 'Infinite Loop', 'default' => 1 ],
					[ 'id' => 'slider_premium_pause', 'type' => 'switch', 'label' => 'Pause on Hover', 'default' => 1 ],
				]
			],
			'p_style' => [
				'title' => 'Cinematic Styling',
				'fields' => [
					[ 'id' => 'slider_premium_height', 'type' => 'number', 'label' => 'Desktop Height (vh)', 'default' => 70 ],
					[ 'id' => 'slider_premium_radius', 'type' => 'number', 'label' => 'Border Radius (px)', 'default' => 20 ],
					[ 'id' => 'slider_premium_overlay', 'type' => 'range', 'label' => 'Dark Overlay Intensity (%)', 'default' => 75, 'min' => 0, 'max' => 100 ],
					[ 'id' => 'slider_premium_alignment', 'type' => 'select', 'label' => 'Content Alignment', 'options' => ['left'=>'Left Aligned','center'=>'Centered'], 'default' => 'left' ],
					[ 'id' => 'slider_premium_btn_style', 'type' => 'select', 'label' => 'Button Shape', 'options' => ['pill'=>'Pill Shape','rounded'=>'Rounded Corners','square'=>'Sharp Edges'], 'default' => 'pill' ],
				]
			],
			'p_mobile' => [
				'title' => 'Mobile Optimization',
				'fields' => [
					[ 'id' => 'slider_premium_mobile_height', 'type' => 'number', 'label' => 'Mobile Height (vh)', 'default' => 50 ],
					[ 'id' => 'slider_premium_font_scale', 'type' => 'range', 'label' => 'Mobile Font Scale (%)', 'default' => 100, 'min' => 50, 'max' => 150 ],
					[ 'id' => 'slider_premium_hide_secondary_mobile', 'type' => 'switch', 'label' => 'Hide Secondary Button on Mobile', 'default' => 1 ],
				]
			]
		]
	],
	'design' => [
		'title' => 'Design System',
		'icon'  => 'dashicons-palmtree',
		'sections' => [
			'appearance' => [
				'title' => 'Theme Appearance',
				'fields' => [
					[ 'id' => 'design_mode', 'type' => 'select', 'label' => 'Charts Content Mode', 'options' => ['light' => 'Light Mode', 'dark' => 'Dark Mode', 'system' => 'Inherit Theme/Browser'], 'default' => 'light', 'desc' => 'Control the internal background/text of charts components.' ],
				]
			],
			'brand_colors' => [
				'title' => 'Brand Colors',
				'desc'  => 'Configure primary and secondary brand accents.',
				'fields' => [
					[ 'id' => 'color_primary', 'type' => 'color', 'label' => 'Primary Brand Color', 'default' => '#3b82f6' ],
					[ 'id' => 'color_secondary', 'type' => 'color', 'label' => 'Secondary Brand Color', 'default' => '#6366f1' ],
				]
			],
			'ui_surfaces' => [
				'title' => 'Surfaces & UI Styling',
				'desc'  => 'Advanced adjustments for light/dark mode components.',
				'fields' => [
					[ 'id' => 'color_bg_light', 'type' => 'color', 'label' => 'Light: Main Background', 'default' => '#f6f6f6' ],
					[ 'id' => 'color_surface_light', 'type' => 'color', 'label' => 'Light: Card Surface', 'default' => '#ffffff' ],
					[ 'id' => 'color_bg_dark', 'type' => 'color', 'label' => 'Dark: Main Background', 'default' => '#0f0f0f' ],
					[ 'id' => 'color_surface_dark', 'type' => 'color', 'label' => 'Dark: Card Surface', 'default' => '#141414' ],
				]
			],
			'labels' => [
				'title' => 'Label Customization',
				'fields' => [
					[ 'id' => 'label_breakdown', 'type' => 'text', 'label' => 'Breakdown CTA Label', 'default' => 'More Details', 'desc' => 'Text for the "Full Breakdown" links (arrows are added automatically).' ],
				]
			]
		]
	],
	'apis' => [
		'title' => 'APIs',
		'icon'  => 'dashicons-rest-api',
		'sections' => [
			'keys' => [
				'title' => 'Service Credentials',
				'fields' => [
					[ 'id' => 'spotify_client_id', 'type' => 'text', 'label' => 'Spotify Client ID' ],
					[ 'id' => 'spotify_client_secret', 'type' => 'password', 'label' => 'Spotify Client Secret' ],
					[ 'id' => 'youtube_api_key', 'type' => 'password', 'label' => 'YouTube Data API v3 Key' ],
				]
			]
		]
	],
	'maintenance' => [
		'title' => 'Maintenance',
		'icon'  => 'dashicons-shield',
		'sections' => [
			'danger' => [
				'title' => 'Danger Zone',
				'desc'  => 'Extreme caution. These actions cannot be undone.',
				'fields' => [
					[ 'id' => 'danger_zone_custom', 'type' => 'custom', 'html' => '<div style="background:#fee2e2; border:1px solid #fca5a5; padding:20px; border-radius:12px;"><h4 style="color:#b91c1c; margin-top:0;">Wipe Plugin Data</h4><p style="color:#7f1d1d; margin-bottom:15px; font-size:13px;">Permanently delete all custom tables, transients, mocked data, and optionally reset settings.</p><input type="text" id="reset_confirm_input" placeholder="Type RESET CHARTS" style="padding:10px; border:1px solid #fca5a5; border-radius:6px; background:#fff; width:200px; margin-right:10px; font-weight:600;"><label style="font-size:13px;display:inline-flex;align-items:center;color:#b91c1c;font-weight:600"><input type="checkbox" name="wipe_settings" value="1" style="margin-right:8px;"> Also wipe settings entirely</label><br><button type="button" class="charts-btn-create" id="reset_plugin_btn" style="background:#ef4444; color:#fff; border:none; margin-top:20px; opacity:0.3; cursor:not-allowed;" disabled>RESET PLUGIN NOW</button></div>' ]
				]
			]
		]
	],
];

$registered_keys = [];

// --- 3. Render Field Engine Helper ---
if ( ! function_exists( 'kc_render_field' ) ) {
	function kc_render_field( $field ) {
		global $registered_keys;
		$id = $field['id'];
		$val = kc_get_setting( $id, $field['default'] ?? '' );
		
		// Map prefix
		$prefix = '';
		if ( $field['type'] === 'switch' ) $prefix = 'chk:';
		elseif ( $field['type'] === 'number' ) $prefix = 'int:';
		elseif ( $field['type'] === 'range' || $field['type'] === 'float' ) $prefix = 'flt:';
		elseif ( $field['type'] === 'textarea' ) $prefix = 'raw:';
		elseif ( $field['type'] === 'media' ) $prefix = 'med:';
		elseif ( $field['type'] === 'slides_manager' || $field['type'] === 'premium_slides_manager' || $field['type'] === 'font_manager' ) $prefix = 'slides:';
		
		if ( $field['type'] !== 'custom' ) {
			$registered_keys[] = $prefix . $id;
		}
		
		echo '<div class="kc-form-group kc-field-' . esc_attr($field['type']) . '" data-search="'.esc_attr(strtolower($field['label'])).'">';
		
		if ( isset($field['label']) && !in_array($field['type'], ['switch','custom','slides_manager']) ) {
			echo '<label for="' . esc_attr($id) . '">' . esc_html($field['label']) . '</label>';
		}
		
		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" class="form-control">';
				break;
			case 'range':
				echo '<div class="kc-range-wrapper" style="display:flex; align-items:center; gap:15px; max-width:400px;">';
				echo '<input type="range" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" step="' . esc_attr($field['step'] ?? 1) . '" min="' . esc_attr($field['min'] ?? 0) . '" max="' . esc_attr($field['max'] ?? 100) . '" oninput="this.nextElementSibling.innerText=this.value" style="flex:1;">';
				echo '<span class="kc-range-val" style="font-family:monospace; font-weight:600; font-size:14px; color:#3b82f6; background:#eff6ff; padding:4px 8px; border-radius:6px; min-width:48px; text-align:center;">' . esc_html($val) . '</span>';
				echo '</div>';
				break;
			case 'color':
				echo '<div style="display:flex; align-items:center; gap:10px;">';
				echo '<input type="color" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" style="padding:0; border:none; width:45px; height:45px; border-radius:8px; cursor:pointer;">';
				echo '<input type="text" value="' . esc_attr($val) . '" class="form-control" style="width:120px; font-family:monospace;" onchange="this.previousElementSibling.value=this.value">';
				echo '</div>';
				break;
			case 'textarea':
				echo '<textarea name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" class="form-control" rows="'.esc_attr($field['rows']??4).'">' . esc_textarea($val) . '</textarea>';
				break;
			case 'select':
				echo '<select name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" class="form-control" style="max-width:400px;">';
				foreach ( $field['options'] as $v => $l ) {
					echo '<option value="' . esc_attr($v) . '" ' . selected( $val, $v, false ) . '>' . esc_html($l) . '</option>';
				}
				echo '</select>';
				break;
			case 'switch':
				echo '<div style="display:flex; align-items:center; gap:15px;">';
				echo '<label class="switch" style="margin:0;">';
				echo '<input type="checkbox" name="' . esc_attr($id) . '" value="1" ' . checked( 1, $val, false ) . '>';
				echo '<span class="slider"></span>';
				echo '</label>';
				if(isset($field['label'])) echo '<strong style="font-size:14px; color:#1e293b;">' . esc_html($field['label']) . '</strong>';
				echo '</div>';
				break;
			case 'media':
				$img_url = $val ? (is_numeric($val) ? wp_get_attachment_image_url( $val, 'medium' ) : $val) : '';
				echo '<div class="kc-media-uploader" style="max-width:400px; margin-bottom:10px;">';
				echo '<div class="logo-preview-wrapper" style="margin-bottom: 15px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; padding:20px; display:flex; align-items:center; justify-content:center; min-height:100px;">';
				echo '<img id="logo-preview-' . esc_attr($id) . '" src="' . esc_url( $img_url ) . '" style="max-height: 60px; display: ' . ($img_url ? 'block' : 'none') . ';">';
				if(!$img_url) echo '<span style="color:#94a3b8; font-size:12px; font-weight:600;">No Image Selected.</span>';
				echo '</div>';
				echo '<input type="hidden" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="' . esc_attr( $val ) . '">';
				echo '<div style="display:flex; gap:10px;">';
				echo '<button type="button" class="charts-btn-back upload-logo-btn" data-target="' . esc_attr($id) . '" style="flex:1; font-size:13px; font-weight:600; padding:10px;">Select Media</button>';
				echo '<button type="button" class="charts-btn-back remove-logo-btn" data-target="' . esc_attr($id) . '" style="color:#ef4444; border-color:#fee2e2; background:#fee2e2; font-size:13px; font-weight:600; padding:10px;">Remove</button>';
				echo '</div>';
				echo '</div>';
				break;
            case 'slides_manager':
                echo '</div>';
                echo '<button type="button" class="kc-add-slide-btn"><span class="dashicons dashicons-plus-alt2"></span> Add Custom Slide</button>';
                echo '</div>';
                break;
            case 'premium_slides_manager':
                $val = kc_get_setting( $field['id'], '[]' );
                $slides = json_decode($val, true) ?: [];
                echo '<div class="kc-premium-slides-manager" id="kc-premium-slides-manager-'.esc_attr($field['id']).'">';
                echo '<input type="hidden" name="'.esc_attr($field['id']).'" value="'.esc_attr($val).'" class="kc-slides-json">';
                echo '<div class="kc-slides-list">';
                foreach($slides as $index => $slide) {
                    $img_url = !empty($slide['image']) ? (is_numeric($slide['image']) ? wp_get_attachment_image_url($slide['image'], 'thumbnail') : $slide['image']) : '';
                    echo '<div class="kc-slide-item kc-premium-slide-item">';
                    echo '<div class="kc-slide-handle"><span class="dashicons dashicons-move"></span></div>';
                    echo '<div class="kc-premium-slide-thumb"><img src="'.esc_url($img_url).'" style="'.($img_url ? '' : 'display:none;').'">'.(!$img_url ? '<span class="dashicons dashicons-format-image"></span>':'').'</div>';
                    echo '<div class="kc-slide-main">';
                    echo '<div class="kc-field-row"><input type="text" placeholder="Title" class="kc-p-title" value="'.esc_attr($slide['title']??'').'"><input type="text" placeholder="Description" class="kc-p-desc" value="'.esc_attr($slide['desc']??'').'"></div>';
                    echo '<div class="kc-field-row"><input type="text" placeholder="Badge" class="kc-p-badge" value="'.esc_attr($slide['badge']??'').'"><input type="hidden" class="kc-p-image" value="'.esc_attr($slide['image']??'').'"><button type="button" class="kc-p-upload btn-mini">Image</button></div>';
                    echo '<div class="kc-field-row"><input type="text" placeholder="Btn 1 Text" class="kc-p-b1-t" value="'.esc_attr($slide['btn1_text']??'').'"><input type="text" placeholder="Btn 1 Link" class="kc-p-b1-l" value="'.esc_attr($slide['btn1_link']??'').'"></div>';
                    echo '<div class="kc-field-row"><input type="text" placeholder="Btn 2 Text" class="kc-p-b2-t" value="'.esc_attr($slide['btn2_text']??'').'"><input type="text" placeholder="Btn 2 Link" class="kc-p-b2-l" value="'.esc_attr($slide['btn2_link']??'').'"></div>';
                    echo '</div>';
                    echo '<div class="kc-slide-actions"><button type="button" class="kc-slide-remove">&times;</button></div>';
                    echo '</div>';
                }
                echo '</div>';
                echo '<button type="button" class="kc-add-premium-slide-btn"><span class="dashicons dashicons-plus-alt2"></span> Add Billboard Slide</button>';
                echo '</div>';
                break;
			case 'custom':
				echo $field['html'];
				break;
		}
		
		if ( !empty($field['desc']) ) {
			echo '<span class="input-helper">' . wp_kses_post($field['desc']) . '</span>';
		}
		echo '</div>';
	}
}
?>

<style>
/* Kontentainment Premium Option Panel CSS */
.kc-settings-wrapper { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; margin:20px 20px 0 0; }
.kc-settings-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.kc-settings-title { margin:0; font-size: 24px; font-weight: 700; color: #1e293b; display:flex; align-items:center; gap:12px; }
.kc-btn-save-sticky { background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; display:flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(37,99,235,0.2); font-size:14px; }
.kc-btn-save-sticky:hover { background: #1d4ed8; transform:translateY(-1px); box-shadow:0 6px 16px rgba(37,99,235,0.3); }

.kc-panel-layout { display: flex; background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(15,23,42,0.04); border: 1px solid #e2e8f0; min-height: 75vh; overflow:hidden; }
.kc-panel-sidebar { width: 280px; background: #f8fafc; border-right: 1px solid #e2e8f0; flex-shrink: 0; display:flex; flex-direction:column; }
.kc-panel-search { padding: 25px 20px 15px; }
.kc-panel-search input { width: 100%; padding: 12px 18px 12px 42px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 13px; background: #fff url('data:image/svg+xml;utf8,<svg viewBox="0 0 24 24" fill="none" stroke="%2394a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>') no-repeat 14px center; background-size: 16px; }
.kc-panel-search input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }

.kc-nav-list { list-style: none; padding: 0 15px 20px; margin: 0; overflow-y:auto; flex:1; }
.kc-nav-btn { display: flex; align-items: center; width: 100%; padding: 14px 20px; border: none; background: transparent; text-align: left; font-size: 14px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; border-radius: 10px; margin-bottom: 4px; }
.kc-nav-btn:hover { background: #e2e8f0; color: #0f172a; }
.kc-nav-btn.active { background: #2563eb; color: #ffffff; box-shadow: 0 4px 12px rgba(37,99,235,0.25); }
.kc-nav-btn .dashicons { margin-right: 14px; font-size: 20px; width: 20px; height: 20px; color:inherit; }

.kc-panel-content { flex: 1; padding: 40px 50px; background: #fff; overflow-y:auto; max-height:85vh; }
.kc-tab-view { display: none; animation: kcFadeIn 0.3s ease; }
.kc-tab-view.active { display: block; }
@keyframes kcFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.kc-section-card { margin-bottom: 40px; }
.kc-section-card:last-child { margin-bottom: 0; }
.kc-section-header { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:flex-end; }
.kc-section-header h3 { margin: 0 0 6px 0; font-size: 18px; color: #0f172a; font-weight:700; }
.kc-section-header p { margin: 0; font-size: 14px; color: #64748b; line-height:1.5; }

.kc-form-group { margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #f8fafc; }
.kc-form-group:last-child { margin-bottom:0; padding-bottom:0; border-bottom:none; }
.kc-form-group label { display:block; font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 10px; }
.kc-form-group .form-control { width: 100%; max-width: 400px; padding: 12px 16px; border-radius: 8px; border: 1px solid #cbd5e1; font-size:14px; color:#1e293b; background:#fff; transition:0.2s; box-shadow:0 1px 2px rgba(0,0,0,0.02); }
.kc-form-group .form-control:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
.kc-form-group textarea.form-control { max-width: 100%; min-height:80px; resize:vertical; }
.input-helper { display:block; font-size: 13px; color: #64748b; margin-top: 8px; line-height:1.4; max-width:600px; }

/* Slides Manager */
.kc-slides-list { margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 12px; overflow:hidden; }
.kc-slide-item { display: flex; align-items: center; background: #fff; border-bottom: 1px solid #f1f5f9; padding: 12px; gap: 15px; }
.kc-slide-item:last-child { border-bottom: none; }
.kc-slide-handle { cursor: move; color: #cbd5e1; }
.kc-slide-main { flex: 1; display: flex; gap: 10px; }
.kc-slide-main input { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 13px; }
.kc-slide-remove { background: #fee2e2; border: none; color: #ef4444; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
.kc-add-slide-btn { background: #f1f5f9; border: 1px dashed #cbd5e1; color: #475569; width: 100%; padding: 15px; border-radius: 12px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
.kc-add-slide-btn:hover { background: #e2e8f0; color: #1e293b; }

/* Preview Panel */
.kc-preview-trigger { color: #2563eb; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: underline; }
.kc-slider-preview-area { background: #000; border-radius: 16px; margin: 20px 0; aspect-ratio: 21 / 9; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.kc-preview-label { position: absolute; top: 15px; right: 20px; background: rgba(37,99,235,0.8); color: #fff; padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 800; letter-spacing: 0.1em; }
.kc-preview-content { text-align: center; color: #fff; transform: scale(0.9); }
.kc-preview-title { font-size: 32px; font-weight: 900; margin: 0; letter-spacing: -0.04em; }
.kc-preview-meta { font-size: 14px; opacity: 0.6; margin-top: 5px; }

/* Switch Toggle (Foxiz style) */
.switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink:0; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
input:checked + .slider { background-color: #10b981; }
input:checked + .slider:before { transform: translateX(20px); }

/* Premium Slide Internal Styles */
.kc-premium-slide-item { padding: 20px; border-radius: 16px !important; margin-bottom: 15px; border: 1px solid #e2e8f0; background: #f8fafc; }
.kc-premium-slide-thumb { width: 60px; height: 60px; background: #e2e8f0; border-radius: 8px; flex-shrink: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.kc-premium-slide-thumb img { width: 100%; height: 100%; object-fit: cover; }
.kc-premium-slide-thumb .dashicons { font-size: 24px; color: #94a3b8; }
.kc-field-row { display: flex; gap: 10px; margin-bottom: 8px; }
.kc-field-row:last-child { margin-bottom: 0; }
.kc-field-row input { height: 32px !important; font-size: 11px !important; padding: 4px 8px !important; }
.btn-mini { padding: 4px 10px; font-size: 10px; font-weight: 700; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer; }
</style>

<div class="kc-settings-wrapper wrap">
	
	<form method="post" action="" id="charts-settings-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">
		
		<div class="kc-settings-header">
			<h1 class="kc-settings-title">
				<span class="dashicons dashicons-admin-generic" style="font-size:32px; width:32px; height:32px; color:#2563eb;"></span>
				Kontentainment Charts Theme Options
			</h1>
			<div style="display:flex; gap:12px;">
				<button type="submit" class="kc-btn-save-sticky">
					<span class="dashicons dashicons-saved"></span> Save Changes
				</button>
			</div>
		</div>

		<?php settings_errors( 'charts' ); ?>

		<div class="kc-panel-layout">
			
			<!-- Sidebar -->
			<div class="kc-panel-sidebar">
				<div class="kc-panel-search">
					<input type="text" id="kc-search-input" placeholder="Search settings...">
				</div>
				<ul class="kc-nav-list">
					<?php 
					$first = true;
					foreach ( $charts_panel as $tab_id => $tab ) : 
					?>
					<li>
						<button type="button" class="kc-nav-btn <?php echo $first ? 'active' : ''; ?>" data-target="<?php echo esc_attr($tab_id); ?>">
							<span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
							<?php echo esc_html($tab['title']); ?>
						</button>
					</li>
					<?php $first = false; endforeach; ?>
				</ul>
			</div>

			<!-- Content Tabs -->
			<div class="kc-panel-content">
				<?php 
				$first = true;
				foreach ( $charts_panel as $tab_id => $tab ) : 
				?>
				<div id="tab-<?php echo esc_attr($tab_id); ?>" class="kc-tab-view <?php echo $first ? 'active' : ''; ?>">
					
					<?php foreach ( $tab['sections'] as $sec_id => $sec ) : ?>
					<div class="kc-section-card" data-section="<?php echo esc_attr($sec_id); ?>">
						<div class="kc-section-header">
							<div>
								<h3><?php echo esc_html($sec['title']); ?></h3>
								<?php if ( !empty($sec['desc']) ) : ?>
									<p><?php echo wp_kses_post($sec['desc']); ?></p>
								<?php endif; ?>
							</div>
							<?php if ($sec_id === 'style') : ?>
							<a class="kc-preview-trigger" onclick="jQuery('.kc-slider-preview-area').slideToggle()">Toggle Layout Preview</a>
							<?php endif; ?>
						</div>
						
                        <?php if ($sec_id === 'style') : ?>
                        <div class="kc-slider-preview-area" style="display:none;">
                            <span class="kc-preview-label">LIVE PREVIEW</span>
                            <div class="kc-preview-content">
                                <h4 class="kc-preview-title">Preview Billboard</h4>
                                <p class="kc-preview-meta">Dynamic Style Logic Rendering</p>
                            </div>
                        </div>
                        <?php endif; ?>

						<?php foreach ( $sec['fields'] as $field ) : ?>
							<?php kc_render_field( $field ); ?>
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>

				</div>
				<?php $first = false; endforeach; ?>
				
				<!-- Render Field Map JSON for backend processor -->
				<input type="hidden" name="charts_registered_fields" value="<?php echo esc_attr( implode( ',', $registered_keys ) ); ?>">
			</div>

		</div>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	// Sidebar Navigation
	$('.kc-nav-btn').on('click', function() {
		var target = $(this).data('target');
		
		// Unset search block visibility overrides
		$('.kc-section-card, .kc-form-group').show();
		$('#kc-search-input').val('');
		
		$('.kc-nav-btn').removeClass('active');
		$(this).addClass('active');
		
		$('.kc-tab-view').removeClass('active');
		$('#tab-' + target).addClass('active');

		if(history.pushState) {
			history.pushState(null, null, '#' + target);
		} else {
			window.location.hash = '#' + target;
		}
	});

	// Hash Navigation on Load
	var hash = window.location.hash.substring(1);
	if (hash && $('#tab-' + hash).length) {
		$('.kc-nav-btn[data-target="' + hash + '"]').click();
	}

	// Dynamic Search
	$('#kc-search-input').on('input', function() {
		var term = $(this).val().toLowerCase();
		if ( term.length > 1 ) {
			$('.kc-tab-view').addClass('active'); // Expand all tabs
			$('.kc-section-card').hide();
			$('.kc-form-group').hide();
			
			$('.kc-form-group').each(function() {
				var searchData = $(this).data('search') || '';
				var textData = $(this).text().toLowerCase();
				if ( searchData.includes(term) || textData.includes(term) ) {
					$(this).show();
					$(this).closest('.kc-section-card').show();
				}
			});
			$('.kc-nav-btn').removeClass('active');
		} else {
			$('.kc-tab-view').removeClass('active');
			$('.kc-form-group').show();
			$('.kc-section-card').show();
			var firstHash = $('.kc-nav-btn').first().data('target');
			var currentHash = window.location.hash.substring(1) || firstHash;
			$('.kc-nav-btn[data-target="' + currentHash + '"]').click();
		}
	});

	// Media Uploader
	$('.upload-logo-btn').on('click', function(e){
		e.preventDefault();
		var targetType = $(this).data('target');
		var frame = wp.media({
			title: 'Select Media',
			button: { text: 'Use this media' },
			multiple: false
		});
		frame.on('select', function(){
			var attachment = frame.state().get('selection').first().toJSON();
			$('#' + targetType).val(attachment.id || attachment.url);
			$('#logo-preview-' + targetType).attr('src', attachment.url).show();
			$('#logo-preview-' + targetType).siblings('span').hide();
		});
		frame.open();
	});

	$('.remove-logo-btn').on('click', function(e){
		e.preventDefault();
		var targetType = $(this).data('target');
		$('#' + targetType).val('');
		$('#logo-preview-' + targetType).hide();
		$('#logo-preview-' + targetType).siblings('span').show();
	});

    // Slides Manager Logic
    $('.kc-add-slide-btn').on('click', function() {
        var $list = $(this).siblings('.kc-slides-list');
        var html = `
            <div class="kc-slide-item">
                <div class="kc-slide-handle"><span class="dashicons dashicons-move"></span></div>
                <div class="kc-slide-main">
                    <input type="text" placeholder="Slide Title" class="kc-slide-title">
                    <input type="text" placeholder="Subtitle/Artist" class="kc-slide-subtitle">
                </div>
                <div class="kc-slide-actions"><button type="button" class="kc-slide-remove">&times;</button></div>
            </div>
        `;
        $list.append(html);
        updateSlidesJson($(this).closest('.kc-slides-manager'));
    });

    $(document).on('click', '.kc-slide-remove', function() {
        var $manager = $(this).closest('.kc-slides-manager');
        $(this).closest('.kc-slide-item').remove();
        updateSlidesJson($manager);
    });

    $(document).on('input', '.kc-slide-main input', function() {
        updateSlidesJson($(this).closest('.kc-slides-manager'));
    });

    function updateSlidesJson($manager) {
        var slides = [];
        if ($manager.hasClass('kc-premium-slides-manager')) {
            $manager.find('.kc-premium-slide-item').each(function() {
                slides.push({
                    title: $(this).find('.kc-p-title').val(),
                    desc: $(this).find('.kc-p-desc').val(),
                    badge: $(this).find('.kc-p-badge').val(),
                    image: $(this).find('.kc-p-image').val(),
                    btn1_text: $(this).find('.kc-p-b1-t').val(),
                    btn1_link: $(this).find('.kc-p-b1-l').val(),
                    btn2_text: $(this).find('.kc-p-b2-t').val(),
                    btn2_link: $(this).find('.kc-p-b2-l').val()
                });
            });
        } else if ($manager.hasClass('kc-font-manager')) {
            $manager.find('.kc-font-item').each(function() {
                slides.push({
                    family: $(this).find('.kc-f-family').val(),
                    url: $(this).find('.kc-f-url').val()
                });
            });
        } else {
            $manager.find('.kc-slide-item').each(function() {
                slides.push({
                    title: $(this).find('.kc-slide-title').val(),
                    subtitle: $(this).find('.kc-slide-subtitle').val()
                });
            });
        }
        $manager.find('.kc-slides-json, .kc-fonts-json').val(JSON.stringify(slides));
        $manager.find('input[type="hidden"]').first().trigger('change');
    }

    $('.kc-add-premium-slide-btn').on('click', function() {
        var $list = $(this).siblings('.kc-slides-list');
        var html = `
            <div class="kc-slide-item kc-premium-slide-item">
                <div class="kc-slide-handle"><span class="dashicons dashicons-move"></span></div>
                <div class="kc-premium-slide-thumb"><span class="dashicons dashicons-format-image"></span></div>
                <div class="kc-slide-main">
                    <div class="kc-field-row"><input type="text" placeholder="Title" class="kc-p-title"><input type="text" placeholder="Description" class="kc-p-desc"></div>
                    <div class="kc-field-row"><input type="text" placeholder="Badge" class="kc-p-badge"><input type="hidden" class="kc-p-image"><button type="button" class="kc-p-upload btn-mini">Image</button></div>
                    <div class="kc-field-row"><input type="text" placeholder="Btn 1 Text" class="kc-p-b1-t"><input type="text" placeholder="Btn 1 Link" class="kc-p-b1-l"></div>
                    <div class="kc-field-row"><input type="text" placeholder="Btn 2 Text" class="kc-p-b2-t"><input type="text" placeholder="Btn 2 Link" class="kc-p-b2-l"></div>
                </div>
                <div class="kc-slide-actions"><button type="button" class="kc-slide-remove">&times;</button></div>
            </div>
        `;
        $list.append(html);
        updateSlidesJson($(this).closest('.kc-premium-slides-manager'));
    });

    $(document).on('click', '.kc-p-upload', function() {
        var $item = $(this).closest('.kc-slide-item');
        var frame = wp.media({ title: 'Select Slide Image', button: { text: 'Use Image' }, multiple: false });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $item.find('.kc-p-image').val(attachment.id || attachment.url);
            $item.find('.kc-premium-slide-thumb').html('<img src="'+attachment.url+'">');
            updateSlidesJson($item.closest('.kc-premium-slides-manager'));
        });
        frame.open();
    });

    $(document).on('input', '.kc-premium-slide-item input, .kc-font-item input', function() {
        updateSlidesJson($(this).closest('.kc-premium-slides-manager, .kc-font-manager'));
    });

    // Font Manager Logic
    $('.kc-add-font-btn').on('click', function() {
        var $list = $(this).siblings('.kc-fonts-list');
        var html = `
            <div class="kc-font-item kc-card" style="padding:15px; margin-bottom:10px; display:flex; gap:15px; align-items:center;">
                <div style="flex:1;">
                    <input type="text" placeholder="Font Family Name" class="kc-f-family form-control" style="margin-bottom:8px;">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="text" placeholder="Font File URL (.woff2/.ttf)" class="kc-f-url form-control">
                        <button type="button" class="kc-f-upload btn-mini" style="height:38px;">Upload</button>
                    </div>
                </div>
                <button type="button" class="kc-slide-remove">&times;</button>
            </div>
        `;
        $list.append(html);
        updateSlidesJson($(this).closest('.kc-font-manager'));
    });

    $(document).on('click', '.kc-f-upload', function() {
        var $item = $(this).closest('.kc-font-item');
        var frame = wp.media({ title: 'Upload Font File', button: { text: 'Use Font' }, library: { type: 'application/x-font-ttf,application/x-font-woff,application/font-woff2,font/woff2,font/ttf' }, multiple: false });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $item.find('.kc-f-url').val(attachment.url);
            updateSlidesJson($item.closest('.kc-font-manager'));
        });
        frame.open();
    });

    // Live Preview Sync mock
    $('#slider_style, #slider_radius, #slider_overlay').on('change input', function() {
        var style = $('#slider_style').val();
        var rad = $('#slider_radius').val();
        var over = $('#slider_overlay').val();
        $('.kc-preview-title').text(style.charAt(0).toUpperCase() + style.slice(1) + ' Style');
        $('.kc-slider-preview-area').css({
            'border-radius': rad,
            'background': 'rgba(0,0,0,' + over + ')'
        });
    });

	// Danger Zone Visibility Logic
	$('#reset_confirm_input').on('input', function() {
		var val = $(this).val().trim();
		if (val === 'RESET CHARTS') {
			$('#reset_plugin_btn').prop('disabled', false).css({ 'opacity': '1', 'cursor': 'pointer' });
		} else {
			$('#reset_plugin_btn').prop('disabled', true).css({ 'opacity': '0.3', 'cursor': 'not-allowed' });
		}
	});

	$('#reset_plugin_btn').on('click', function(e) {
		if (confirm('EXTREME WARNING: You are about to permanently delete all charts data. This cannot be undone. Are you absolutely sure?')) {
			$('<input>').attr({type: 'hidden', name: 'charts_action', value: 'reset_plugin'}).appendTo('#charts-settings-form');
			$('#charts-settings-form').submit();
		}
	});
});
</script>
