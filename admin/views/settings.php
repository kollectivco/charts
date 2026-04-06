<?php
/**
 * Settings View - Kontentainment Charts Simplified Tab-Based Layout
 */

// Ensure settings helper functions
if ( ! function_exists( 'kc_get_setting' ) ) {
	function kc_get_setting( $id, $default = '' ) {
		return \Charts\Core\Settings::get( $id, $default );
	}
}

// Data Source for Charts
$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
$chart_options = [];
foreach ( $definitions as $def ) { $chart_options[$def->id] = $def->title; }

// --- 2. Registration Engine Array ---
$charts_panel = [
	'homepage' => [
		'title' => 'Homepage Layout',
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
		'sections' => [
			'appearance' => [
				'title' => 'Theme Appearance',
				'fields' => [
					[ 'id' => 'design_mode', 'type' => 'select', 'label' => 'Charts Content Mode', 'options' => ['light' => 'Light Mode', 'dark' => 'Dark Mode', 'system' => 'Inherit Theme/Browser'], 'default' => 'light' ],
				]
			],
			'brand_colors' => [
				'title' => 'Brand Colors',
				'fields' => [
					[ 'id' => 'color_primary', 'type' => 'color', 'label' => 'Primary Brand Color', 'default' => '#3b82f6' ],
					[ 'id' => 'color_secondary', 'type' => 'color', 'label' => 'Secondary Brand Color', 'default' => '#6366f1' ],
				]
			],
			'labels' => [
				'title' => 'Label Customization',
				'fields' => [
					[ 'id' => 'label_breakdown', 'type' => 'text', 'label' => 'Breakdown CTA Label', 'default' => 'More Details' ],
				]
			]
		]
	],
	'apis' => [
		'title' => 'APIs',
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
		'sections' => [
			'danger' => [
				'title' => 'Danger Zone',
				'fields' => [
					[ 'id' => 'danger_zone_custom', 'type' => 'custom', 'html' => '<div style="background:#fee2e2; border:1px solid #fca5a5; padding:20px; border-radius:12px;"><h4 style="color:#b91c1c; margin-top:0;">Wipe Plugin Data</h4><p style="color:#7f1d1d; margin-bottom:15px; font-size:13px;">Permanently delete all custom tables and optionally reset settings. Type RESET CHARTS below.</p><input type="text" id="reset_confirm_input" placeholder="Type RESET CHARTS" class="regular-text"><br><br><label><input type="checkbox" name="wipe_settings" value="1"> Also wipe settings entirely</label><br><br><button type="button" class="button button-link-delete" id="reset_plugin_btn" disabled>RESET PLUGIN NOW</button></div>' ]
				]
			]
		]
	],
];

$registered_keys = [];

if ( ! function_exists( 'kc_render_field' ) ) {
	function kc_render_field( $field ) {
		global $registered_keys;
		$id = $field['id'];
		$val = kc_get_setting( $id, $field['default'] ?? '' );
		
		$prefix = '';
		if ( $field['type'] === 'switch' ) $prefix = 'chk:';
		elseif ( $field['type'] === 'number' ) $prefix = 'int:';
		elseif ( $field['type'] === 'range' || $field['type'] === 'float' ) $prefix = 'flt:';
		elseif ( $field['type'] === 'textarea' ) $prefix = 'raw:';
		elseif ( $field['type'] === 'media' ) $prefix = 'med:';
		elseif ( $field['type'] === 'slides_manager' || $field['type'] === 'premium_slides_manager' ) $prefix = 'slides:';
		
		if ( $field['type'] !== 'custom' ) {
			$registered_keys[] = $prefix . $id;
		}
		
		echo '<div class="kc-tab-field-row">';
		if ( isset($field['label']) && !in_array($field['type'], ['switch','custom']) ) {
			echo '<label>' . esc_html($field['label']) . '</label>';
		}
		
		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="' . esc_attr($val) . '" class="regular-text">';
				break;
			case 'range':
				echo '<input type="range" name="' . esc_attr($id) . '" value="' . esc_attr($val) . '" step="' . esc_attr($field['step'] ?? 1) . '" min="' . esc_attr($field['min'] ?? 0) . '" max="' . esc_attr($field['max'] ?? 100) . '" oninput="this.nextElementSibling.innerText=this.value">';
				echo ' <span class="kc-badge">' . esc_html($val) . '</span>';
				break;
			case 'color':
				echo '<input type="color" name="' . esc_attr($id) . '" value="' . esc_attr($val) . '"> ';
				echo '<input type="text" value="' . esc_attr($val) . '" class="small-text" onchange="this.previousElementSibling.value=this.value">';
				break;
			case 'select':
				echo '<select name="' . esc_attr($id) . '">';
				foreach ( $field['options'] as $v => $l ) {
					echo '<option value="' . esc_attr($v) . '" ' . selected( $val, $v, false ) . '>' . esc_html($l) . '</option>';
				}
				echo '</select>';
				break;
			case 'switch':
				echo '<label><input type="checkbox" name="' . esc_attr($id) . '" value="1" ' . checked( 1, $val, false ) . '> ' . esc_html($field['label']) . '</label>';
				break;
			case 'premium_slides_manager':
				$val = kc_get_setting( $field['id'], '[]' );
				$slides = json_decode($val, true) ?: [];
				echo '<div class="kc-slides-manager kc-premium-slides-manager" id="manager-'.esc_attr($field['id']).'">';
				echo '<input type="hidden" name="'.esc_attr($field['id']).'" value="'.esc_attr($val).'" class="kc-slides-json">';
				echo '<div class="kc-slides-list">';
				foreach($slides as $slide) {
					$img_url = !empty($slide['image']) ? (is_numeric($slide['image']) ? wp_get_attachment_image_url($slide['image'], 'thumbnail') : $slide['image']) : '';
					echo '<div class="kc-slide-item">';
					echo '<img src="'.esc_url($img_url).'" width="40" height="40" style="object-fit:cover; border-radius:4px; '.($img_url?'':'display:none').'">';
					echo '<input type="text" placeholder="Title" class="kc-p-title" value="'.esc_attr($slide['title']??'').'">';
					echo '<input type="hidden" class="kc-p-image" value="'.esc_attr($slide['image']??'').'">';
					echo '<button type="button" class="kc-p-upload button-secondary">Img</button>';
					echo '<button type="button" class="kc-slide-remove button-link-delete">&times;</button>';
					echo '</div>';
				}
				echo '</div>';
				echo '<button type="button" class="kc-add-premium-slide-btn button">Add Slide</button>';
				echo '</div>';
				break;
			case 'custom':
				echo $field['html'];
				break;
		}
		
		if ( !empty($field['desc']) ) {
			echo '<p class="description">' . wp_kses_post($field['desc']) . '</p>';
		}
		echo '</div>';
	}
}
?>

<div class="wrap kc-settings-tabs-wrap">
	<h1>Kontentainment Charts Settings</h1>
	
	<h2 class="nav-tab-wrapper">
		<?php $first = true; foreach ( $charts_panel as $id => $tab ) : ?>
			<a href="#tab-<?php echo $id; ?>" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-tab="<?php echo $id; ?>"><?php echo esc_html($tab['title']); ?></a>
		<?php $first = false; endforeach; ?>
	</h2>

	<form method="post" action="" id="charts-settings-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<?php settings_errors( 'charts' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">

		<div class="kc-tabs-content">
			<?php $first = true; foreach ( $charts_panel as $tab_id => $tab ) : ?>
				<div id="tab-<?php echo $tab_id; ?>" class="kc-tab-content-panel <?php echo $first ? 'active' : ''; ?>">
					<?php foreach ( $tab['sections'] as $sec ) : ?>
						<div class="kc-settings-section">
							<h3><?php echo esc_html($sec['title']); ?></h3>
							<table class="form-table">
								<?php foreach ( $sec['fields'] as $field ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html($field['label'] ?? ''); ?></th>
										<td><?php kc_render_field($field); ?></td>
									</tr>
								<?php endforeach; ?>
							</table>
						</div>
					<?php endforeach; ?>
				</div>
			<?php $first = false; endforeach; ?>
		</div>

		<input type="hidden" name="charts_registered_fields" value="<?php echo esc_attr( implode( ',', $registered_keys ) ); ?>">
		
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
		</p>
	</form>
</div>

<style>
.kc-tab-content-panel { display: none; }
.kc-tab-content-panel.active { display: block; margin-top:20px; }
.kc-settings-section { margin-bottom: 30px; background:#fff; padding: 20px; border-radius:8px; border:1px solid #ccd0d4; }
.kc-settings-section h3 { margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px; }
.kc-settings-tabs-wrap .nav-tab { cursor:pointer; }
.kc-badge { background:#0073aa; color:#fff; padding:2px 6px; border-radius:4px; font-size:11px; }
.kc-slide-item { display:flex; align-items:center; gap:10px; margin-bottom:8px; background:#f9f9f9; padding:8px; border-radius:4px; border:1px solid #eee; }
.kc-slide-item input { flex:1; }
</style>

<script>
jQuery(document).ready(function($) {
	// Tab Switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.kc-tab-content-panel').removeClass('active');
		var target = $(this).data('tab');
		$('#tab-' + target).addClass('active');
		window.location.hash = target;
	});

	// Hash Handling
	var hash = window.location.hash.substring(1);
	if (hash && $('.nav-tab[data-tab="'+hash+'"]').length) {
		$('.nav-tab[data-tab="'+hash+'"]').trigger('click');
	}

	// Premium Slide Manager
	$('.kc-add-premium-slide-btn').on('click', function() {
		var $list = $(this).siblings('.kc-slides-list');
		var html = `
			<div class="kc-slide-item">
				<img src="" width="40" height="40" style="object-fit:cover; border-radius:4px; display:none">
				<input type="text" placeholder="Title" class="kc-p-title">
				<input type="hidden" class="kc-p-image">
				<button type="button" class="kc-p-upload button-secondary">Img</button>
				<button type="button" class="kc-slide-remove button-link-delete">&times;</button>
			</div>
		`;
		$list.append(html);
	});

	$(document).on('click', '.kc-slide-remove', function() {
		var $manager = $(this).closest('.kc-slides-manager');
		$(this).closest('.kc-slide-item').remove();
		updateJson($manager);
	});

	$(document).on('click', '.kc-p-upload', function() {
		var $item = $(this).closest('.kc-slide-item');
		var frame = wp.media({ title: 'Select Image', button: { text: 'Use Image' }, multiple: false });
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			$item.find('.kc-p-image').val(attachment.id || attachment.url);
			$item.find('img').attr('src', attachment.url).show();
			updateJson($item.closest('.kc-slides-manager'));
		});
		frame.open();
	});

	$(document).on('input', '.kc-p-title', function() {
		updateJson($(this).closest('.kc-slides-manager'));
	});

	function updateJson($manager) {
		var slides = [];
		$manager.find('.kc-slide-item').each(function() {
			slides.push({
				title: $(this).find('.kc-p-title').val(),
				image: $(this).find('.kc-p-image').val()
			});
		});
		$manager.find('.kc-slides-json').val(JSON.stringify(slides));
	}

	// Maintenance
	$('#reset_confirm_input').on('input', function() {
		$('#reset_plugin_btn').prop('disabled', $(this).val() !== 'RESET CHARTS');
	});

	$('#reset_plugin_btn').on('click', function() {
		if (confirm('Are you absolutely sure? This will wipe all data!')) {
			$('<input>').attr({type: 'hidden', name: 'charts_action', value: 'reset_plugin'}).appendTo('#charts-settings-form');
			$('#charts-settings-form').submit();
		}
	});
});
</script>
