<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Charts\Integrations\Elementor\PremiumWidgetTrait;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

/**
 * Elementor Widget: Chart List (Editorial Rankings)
 * Displays a list of charts highlighting the #1 item of each.
 */
class ChartList extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'chart_list'; }
	public function get_title() { return __( 'Charts: Intelligence List', 'charts' ); }
	public function get_icon() { return 'eicon-editor-list-ul'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// 1. Data Configuration
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Chart Selection', 'charts' ) ] );
		
		$this->add_control( 'limit', [
			'label' => __( 'Number of Charts', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->add_control( 'selection_mode', [
			'label' => __( 'Selection Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'latest' => 'Latest Updated',
				'manual' => 'Manual Selection'
			],
			'default' => 'latest'
		]);

		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = ["0" => __("Current Chart (Dynamic)", "charts")];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'selected_charts', [
			'label' => __( 'Select Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $options,
			'condition' => [ 'selection_mode' => 'manual' ]
		] );

		$this->add_control( 'start_index', [
			'label' => __( 'Start Index', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 1,
		]);

		$this->end_controls_section();

		// 2. Visibility Toggles
		$this->start_controls_section( 'section_visibility', [ 'label' => __( 'Visibility', 'charts' ) ] );

		$this->add_control( 'show_badge', [
			'label' => __( 'Show Chart Badge', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_subtitle', [
			'label' => __( 'Show Artist/Subtitle', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_index', [
			'label' => __( 'Show Large Index Number', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_separator', [
			'label' => __( 'Show Separators', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->end_controls_section();

		// 3. Style: Layout & Spacing
		$this->start_controls_section( 'section_style_layout', [ 'label' => __( 'Layout & Spacing', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_responsive_control( 'row_height', [
			'label' => __( 'Minimum Row Height', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range' => [ 'px' => [ 'min' => 40, 'max' => 300 ] ],
			'default' => [ 'size' => 120 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'min-height: {{SIZE}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'item_padding', [
			'label' => __( 'Item Padding', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'default' => [ 'top' => '40', 'bottom' => '40', 'left' => '0', 'right' => '0', 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'item_margin', [
			'label' => __( 'Item Margin', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'item_border_radius', [
			'label' => __( 'Border Radius', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ]
		]);

		$this->start_controls_tabs( 'item_bg_tabs' );
		$this->start_controls_tab( 'item_bg_normal', [ 'label' => __( 'Normal', 'charts' ) ] );
		$this->add_control( 'item_bg_color', [
			'label' => __( 'Background Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'background-color: {{VALUE}};' ]
		]);
		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
			'name' => 'item_box_shadow',
			'selector' => '{{WRAPPER}} .kc-list-item',
		]);
		$this->end_controls_tab();

		$this->start_controls_tab( 'item_bg_hover', [ 'label' => __( 'Hover', 'charts' ) ] );
		$this->add_control( 'item_bg_color_hover', [
			'label' => __( 'Background Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-list-item:hover' => 'background-color: {{VALUE}};' ]
		]);
		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
			'name' => 'item_box_shadow_hover',
			'selector' => '{{WRAPPER}} .kc-list-item:hover',
		]);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'content_max_width', [
			'label' => __( 'Content Max Width', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1000 ], '%' => [ 'min' => 10, 'max' => 100 ] ],
			'default' => [ 'size' => 70, 'unit' => '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-list-main' => 'max-width: {{SIZE}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'gap_badge_title', [
			'label' => __( 'Gap: Badge to Title', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range' => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'default' => [ 'size' => 16 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-badge' => 'margin-bottom: {{SIZE}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'gap_title_subtitle', [
			'label' => __( 'Gap: Title to Subtitle', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range' => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'default' => [ 'size' => 6 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-title' => 'margin-bottom: {{SIZE}}{{UNIT}};' ]
		]);

		$this->add_responsive_control( 'index_offset_x', [
			'label' => __( 'Index Horizontal Offset', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range' => [ 'px' => [ 'min' => -100, 'max' => 100 ], '%' => [ 'min' => -50, 'max' => 50 ] ],
			'default' => [ 'size' => 0 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-index' => 'right: {{SIZE}}{{UNIT}};' ]
		]);

		$this->end_controls_section();

		// 4. Style: Typography & Colors
		$this->start_controls_section( 'section_style_content', [ 'label' => __( 'Typography & Colors', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		// Title
		$this->add_control( 'heading_title', [ 'label' => __( 'Title', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'title_typography',
			'selector' => '{{WRAPPER}} .kc-list-title',
			'fields_options' => [
				'font_weight' => [ 'default' => '900' ],
				'font_size' => [ 'default' => [ 'size' => 36, 'unit' => 'px' ] ],
			]
		] );
		$this->add_control( 'title_color', [
			'label' => __( 'Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#111827',
			'selectors' => [ '{{WRAPPER}} .kc-list-title' => 'color: {{VALUE}};' ]
		] );

		// Subtitle
		$this->add_control( 'heading_subtitle', [ 'label' => __( 'Subtitle', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'subtitle_typography',
			'selector' => '{{WRAPPER}} .kc-list-subtitle',
			'fields_options' => [
				'font_size' => [ 'default' => [ 'size' => 16, 'unit' => 'px' ] ],
				'font_weight' => [ 'default' => '600' ],
			]
		] );
		$this->add_control( 'subtitle_color', [
			'label' => __( 'Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#6b7280',
			'selectors' => [ '{{WRAPPER}} .kc-list-subtitle' => 'color: {{VALUE}};' ]
		] );

		// Badge
		$this->add_control( 'heading_badge', [ 'label' => __( 'Badge', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'badge_typography',
			'selector' => '{{WRAPPER}} .kc-list-badge',
			'fields_options' => [
				'font_size' => [ 'default' => [ 'size' => 10, 'unit' => 'px' ] ],
				'font_weight' => [ 'default' => '800' ],
				'text_transform' => [ 'default' => 'uppercase' ],
			]
		] );
		$this->add_control( 'badge_bg_color', [
			'label' => __( 'Background Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#111827',
			'selectors' => [ '{{WRAPPER}} .kc-list-badge' => 'background: {{VALUE}};' ]
		] );
		$this->add_control( 'badge_text_color', [
			'label' => __( 'Text Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .kc-list-badge' => 'color: {{VALUE}};' ]
		] );
		$this->add_control( 'badge_radius', [
			'label' => __( 'Corner Radius', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'default' => [ 'size' => 100 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-badge' => 'border-radius: {{SIZE}}px;' ]
		]);

		// Index
		$this->add_control( 'heading_index', [ 'label' => __( 'Index Number', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'index_size', [
			'label' => __( 'Size', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 40, 'max' => 300 ] ],
			'default' => [ 'size' => 160 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-index' => 'font-size: {{SIZE}}{{UNIT}};' ]
		]);
		$this->add_control( 'index_opacity_val', [
			'label' => __( 'Opacity', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.01 ] ],
			'default' => [ 'size' => 0.05 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-index' => 'opacity: {{SIZE}};' ]
		]);

		// Separator
		$this->add_control( 'heading_separator', [ 'label' => __( 'Separator', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'divider_color', [
			'label' => __( 'Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => 'rgba(0,0,0,0.08)',
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'border-color: {{VALUE}};' ]
		] );

		$this->end_controls_section();
		$this->add_advanced_image_controls('{{WRAPPER}} .kc-cl2-img-wrap', '{{WRAPPER}} .kc-cl2-img-wrap img');
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		if ( $settings['selection_mode'] === 'manual' && !empty($settings['selected_charts']) ) {
			$definitions = array();
			foreach($settings['selected_charts'] as $cid) {
				$def = (empty($cid) || $cid === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($cid);
				if($def) $definitions[] = $def;
			}
		} else {
			$limit = intval($settings['limit'] ?: 5);
			$definitions = \Charts\Core\PublicIntegration::get_eligible_definitions($limit);
		}

		if ( empty($definitions) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px;text-align:center;background:#0f172a;color:#fff;border-radius:20px;border:2px dashed #eab308;direction:ltr;"><h3>Charts: Featured Chart List</h3><p>No chart data found. Check settings.</p></div>';
			}
			return;
		}

		$start_index = intval($settings['start_index'] ?: 1);
?>
		<div class="kc-cl2-root kc-widget-wrap" id="<?php echo $uid; ?>">
			<div class="kc-cl2-widget" style="display: flex; flex-direction: column;">
				<?php foreach ( $definitions as $i => $def ) : 
					$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 1);
					$top = !empty($entries) ? $entries[0] : null;
					if (!$top) continue;
					
					$idx_str = str_pad($i + $start_index, 2, '0', STR_PAD_LEFT);
					$has_sep = $settings['show_separator'] === 'yes' && ($i < count($definitions) - 1);
					
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($top, $def);

					// Core structural styles only (no properties controlled by Elementor selectors)
					$list_item_style = 'position: relative; display: flex; align-items: center; justify-content: space-between; overflow: visible;';
					if ($has_sep) {
						$list_item_style .= ' border-bottom-style: solid; border-bottom-width: 1px;';
					}
				?>
					<div class="kc-list-item" style="<?php echo esc_attr($list_item_style); ?>">
						
						<div class="kc-list-main" style="position: relative; z-index: 2; flex-grow: 1;">
							<?php if ( $settings['show_badge'] === 'yes' ) : ?>
								<span class="kc-list-badge" style="display: inline-block; padding: 4px 10px; letter-spacing: 0.1em; line-height: 1;"><?php echo esc_html($def->title); ?></span>
							<?php endif; ?>

							<h3 class="kc-list-title" style="margin: 0; line-height: 1.1;"><?php echo esc_html($resolved['title']); ?></h3>
							
							<?php if ( $settings['show_subtitle'] === 'yes' ) : ?>
								<span class="kc-list-subtitle" style="display: block; line-height: 1.4;"><?php echo esc_html($resolved['subtitle']); ?></span>
							<?php endif; ?>

							<a href="<?php echo home_url('/charts/' . $def->slug); ?>" style="position: absolute; inset: 0; z-index: 5;"></a>
						</div>

						<?php if ( $settings['show_index'] === 'yes' ) : ?>
							<div class="kc-list-index" style="position: absolute; right: 0; font-weight: 950; pointer-events: none; line-height: 1;"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($idx_str); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	}
}
