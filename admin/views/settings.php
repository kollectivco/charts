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
		'title' => 'Charts Homepage',
		'sections' => [
			'structure' => [
				'title' => 'Homepage Layout',
				'fields' => [
					[ 'id' => 'homepage_layout', 'type' => 'select', 'label' => 'Homepage Mode', 'options' => ['standard' => 'Full Billboard (Premium)', 'minimal' => 'Minimal Grid Only'], 'default' => 'standard' ],
					[ 'id' => 'homepage_show_artists', 'type' => 'switch', 'label' => 'Show Trending Artists Row', 'default' => 1 ],
					[ 'id' => 'homepage_show_more', 'type' => 'switch', 'label' => 'Show "All Charts" Grid', 'default' => 1 ],
					[ 'id' => 'homepage_section_order', 'type' => 'text', 'label' => 'Section Arrangement', 'default' => 'slider,artists,charts', 'desc' => 'Order for: slider, artists, charts' ],
				]
			],
			'design' => [
				'title' => 'Aesthetic Controls',
				'fields' => [
					[ 'id' => 'homepage_padding_top', 'type' => 'number', 'label' => 'Top Spacing (px)', 'default' => 40 ],
					[ 'id' => 'homepage_section_spacing', 'type' => 'number', 'label' => 'Section Gap (px)', 'default' => 80 ],
				]
			]
		]
	],
	'slider' => [
		'title' => 'Hero Billboard',
		'sections' => [
			'p_toggle' => [
				'title' => 'Slider Engine',
				'fields' => [
					[ 'id' => 'slider_premium_enable', 'type' => 'switch', 'label' => 'Activate Cinematic Slider', 'default' => 1 ],
				]
			],
			'p_source' => [
				'title' => 'Dynamic Content',
				'fields' => [
					[ 'id' => 'slider_premium_source', 'type' => 'select', 'label' => 'Source Logic', 'options' => [
						'latest' => 'Automated (Latest Charts)',
						'selected' => 'Manual (Selected Charts)',
						'selection_top' => 'Trending (#1 from Selected)'
					], 'default' => 'latest' ],
					[ 'id' => 'slider_premium_charts', 'type' => 'chart_select', 'label' => 'Source Selection', 'desc' => 'Choose individual charts for manual/trending modes' ],
				]
			],
			'p_behavior' => [
				'title' => 'Motion & Timing',
				'fields' => [
					[ 'id' => 'slider_premium_autoplay', 'type' => 'switch', 'label' => 'Enable Autoplay', 'default' => 1 ],
					[ 'id' => 'slider_premium_delay', 'type' => 'number', 'label' => 'Hold Time (ms)', 'default' => 5000 ],
					[ 'id' => 'slider_premium_speed', 'type' => 'number', 'label' => 'Transition Time (ms)', 'default' => 800 ],
					[ 'id' => 'slider_premium_pause', 'type' => 'switch', 'label' => 'Pause on Interaction', 'default' => 1 ],
				]
			],
			'p_style' => [
				'title' => 'Visual Presence',
				'fields' => [
					[ 'id' => 'slider_premium_height', 'type' => 'number', 'label' => 'Desktop Height (vh)', 'default' => 60 ],
					[ 'id' => 'slider_premium_mobile_height', 'type' => 'number', 'label' => 'Mobile Height (vh)', 'default' => 50 ],
					[ 'id' => 'slider_premium_width', 'type' => 'number', 'label' => 'Max Content Width (px)', 'default' => 1400 ],
					[ 'id' => 'slider_premium_radius', 'type' => 'number', 'label' => 'Card Rounded Corners (px)', 'default' => 28 ],
					[ 'id' => 'slider_premium_overlay', 'type' => 'range', 'label' => 'Shadow Intensity (%)', 'default' => 75, 'min' => 0, 'max' => 100 ],
				]
			]
		]
	],
	'design' => [
		'title' => 'The Design System',
		'sections' => [
			'core_ui' => [
				'title' => 'Branding & Colors',
				'fields' => [
					[ 'id' => 'design_mode', 'type' => 'select', 'label' => 'Appearance Mode', 'options' => ['light' => 'Pristine Light', 'dark' => 'Deep Midnight', 'system' => 'Auto (OS Adaptive)'], 'default' => 'light' ],
					[ 'id' => 'color_primary', 'type' => 'color', 'label' => 'Primary Brand Color', 'default' => '#3b82f6' ],
					[ 'id' => 'color_secondary', 'type' => 'color', 'label' => 'Accent/Detail Color', 'default' => '#6366f1' ],
				]
			],
			'surfaces' => [
				'title' => 'Surface Control',
				'fields' => [
					[ 'id' => 'color_bg_light', 'type' => 'color', 'label' => 'Page Background (Light)', 'default' => '#f6f6f6' ],
					[ 'id' => 'color_surface_light', 'type' => 'color', 'label' => 'Card Surface (Light)', 'default' => '#ffffff' ],
					[ 'id' => 'color_bg_dark', 'type' => 'color', 'label' => 'Page Background (Dark)', 'default' => '#0f0f0f' ],
					[ 'id' => 'color_surface_dark', 'type' => 'color', 'label' => 'Card Surface (Dark)', 'default' => '#141414' ],
				]
			],
			'customization' => [
				'title' => 'Global UI Settings',
				'fields' => [
					[ 'id' => 'ui_card_radius', 'type' => 'number', 'label' => 'Standard Card Radius (px)', 'default' => 24 ],
					[ 'id' => 'label_breakdown', 'type' => 'text', 'label' => 'Chart CTA Text', 'default' => 'View Chart' ],
				]
			]
		]
	],
	'apis' => [
		'title' => 'Service Nexus',
		'sections' => [
			'spotify' => [
				'title' => 'Spotify Web API',
				'fields' => [
					[ 'id' => 'spotify_client_id', 'type' => 'text', 'label' => 'Client ID' ],
					[ 'id' => 'spotify_client_secret', 'type' => 'password', 'label' => 'Client Secret' ],
					[ 'id' => 'spotify_test', 'type' => 'custom', 'html' => '<button type="button" class="charts-btn charts-btn-outline" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=test_spotify_api&_wpnonce='.wp_create_nonce('charts_admin_action')).'\'">Test Connection</button>' ]
				]
			],
			'youtube' => [
				'title' => 'YouTube Data API',
				'fields' => [
					[ 'id' => 'youtube_api_key', 'type' => 'password', 'label' => 'Data API v3 Key' ],
					[ 'id' => 'youtube_test', 'type' => 'custom', 'html' => '<button type="button" class="charts-btn charts-btn-outline" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=test_youtube_api&_wpnonce='.wp_create_nonce('charts_admin_action')).'\'">Test Connection</button>' ]
				]
			]
		]
	],
	'maintenance' => [
		'title' => 'Operations',
		'sections' => [
			'tools' => [
				'title' => 'Data Management',
				'fields' => [
					[ 'id' => 'asset_backfill', 'type' => 'custom', 'html' => '<button type="button" class="charts-btn charts-btn-primary" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=backfill_media&_wpnonce='.wp_create_nonce('charts_admin_action')).'\'">Backfill Missing Images</button>' ],
					[ 'id' => 'integrity_check', 'type' => 'custom', 'html' => '<button type="button" class="charts-btn charts-btn-outline" onclick="location.href=\''.admin_url('admin.php?page=charts-settings&charts_action=run_integrity_check&_wpnonce='.wp_create_nonce('charts_admin_action')).'\'">Re-link Broken Entries</button>' ],
				]
			],
			'danger' => [
				'title' => 'Danger Zone',
				'fields' => [
					[ 'id' => 'danger_zone_custom', 'type' => 'custom', 'html' => '<div class="kc-danger-bento"><p>Type <strong>RESET CHARTS</strong> to destroy all data.</p><input type="text" id="reset_confirm_input" placeholder="Confirmation text..." class="regular-text"><br><br><label><input type="checkbox" name="wipe_settings" value="1"> Also wipe settings</label><br><br><button type="button" class="charts-btn charts-btn-outline" id="reset_plugin_btn" disabled style="color:#ef4444; border-color:#fca5a5;">PURGE ALL DATA</button></div>' ]
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
			case 'chart_select':
				$charts = (new \Charts\Admin\SourceManager())->get_definitions( true );
				$val = is_array($val) ? $val : [];
				echo '<select name="' . esc_attr($id) . '[]" multiple style="height:120px; width:100%; max-width:400px;">';
				foreach ( $charts as $c ) {
					echo '<option value="' . esc_attr($c->id) . '" ' . (in_array($c->id, $val) ? 'selected' : '') . '>' . esc_html($c->title) . '</option>';
				}
				echo '</select>';
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

<<div class="wrap charts-admin-wrap premium-light">
	<div class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title">Charts Suite</h1>
			<p class="charts-admin-subtitle">Product Configuration & Experience Engine</p>
		</div>
		<div class="charts-header-actions">
			<button type="submit" form="charts-settings-form" class="charts-btn charts-btn-primary large">Keep Changes</button>
		</div>
	</div>
	
	<div class="kc-bento-navigation">
		<?php $first = true; foreach ( $charts_panel as $id => $tab ) : ?>
			<button type="button" class="kc-bento-nav-item <?php echo $first ? 'is-active' : ''; ?>" data-tab="<?php echo $id; ?>">
				<?php echo esc_html($tab['title']); ?>
			</button>
		<?php $first = false; endforeach; ?>
	</div>

	<form method="post" action="" id="charts-settings-form" class="kc-bento-form-engine">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<?php settings_errors( 'charts' ); ?>
		<input type="hidden" name="charts_action" value="save_settings">

		<div class="kc-bento-tabs-wrapper">
			<?php $first = true; foreach ( $charts_panel as $tab_id => $tab ) : ?>
				<div id="tab-<?php echo $tab_id; ?>" class="kc-bento-tab-panel <?php echo $first ? 'is-active' : ''; ?>">
					<div class="charts-bento-grid">
						<?php foreach ( $tab['sections'] as $sec_id => $sec ) : ?>
							<div class="charts-bento-card <?php echo in_array($sec_id, ['p_manager','danger']) ? 'full-width' : ''; ?>">
								<h3 class="card-title"><?php echo esc_html($sec['title']); ?></h3>
								<div class="card-body">
									<?php foreach ( $sec['fields'] as $field ) : ?>
										<div class="kc-bento-field-wrap">
											<div class="kc-bento-field-label"><?php echo esc_html($field['label'] ?? ''); ?></div>
											<div class="kc-bento-field-input"><?php kc_render_field($field); ?></div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php $first = false; endforeach; ?>
		</div>

		<input type="hidden" name="charts_registered_fields" value="<?php echo esc_attr( implode( ',', $registered_keys ) ); ?>">
	</form>
</div>

<style>
.charts-admin-wrap.premium-light { background: #f0f2f5; padding: 40px; }
.kc-bento-navigation { display: flex; gap: 12px; margin-bottom: 32px; background: #fff; padding: 8px; border-radius: 16px; border: 1px solid #e2e8f0; width: fit-content; }
.kc-bento-nav-item { border: none; background: transparent; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 14px; color: #64748b; cursor: pointer; transition: all 0.2s; }
.kc-bento-nav-item.is-active { background: #0f172a; color: #fff; }

.kc-bento-tab-panel { display: none; }
.kc-bento-tab-panel.is-active { display: block; animation: kc-fade-in 0.3s ease-out; }

.kc-bento-field-wrap { margin-bottom: 24px; }
.kc-bento-field-label { font-size: 12px; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px; letter-spacing: 0.05em; }
.kc-bento-field-input select, .kc-bento-field-input input[type="text"], .kc-bento-field-input input[type="number"], .kc-bento-field-input input[type="password"] { 
    width: 100%; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; font-weight: 600; color: #1e293b;
}

.kc-danger-bento { background: #fff1f2; border: 1px solid #fecaca; padding: 24px; border-radius: 16px; color: #991b1b; }

@keyframes kc-fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
jQuery(document).ready(function($) {
	// Tab Switching
	$('.kc-bento-nav-item').on('click', function(e) {
		e.preventDefault();
		$('.kc-bento-nav-item').removeClass('is-active');
		$(this).addClass('is-active');
		
		$('.kc-bento-tab-panel').removeClass('is-active');
		var target = $(this).data('tab');
		$('#tab-' + target).addClass('is-active');
		window.location.hash = target;
	});

	// Hash Handling
	var hash = window.location.hash.substring(1);
	if (hash && $('.kc-bento-nav-item[data-tab="'+hash+'"]').length) {
		$('.kc-bento-nav-item[data-tab="'+hash+'"]').trigger('click');
	}
});
</script>
