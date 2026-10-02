<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartTable extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_chart_table'; }
	public function get_title() { return __( 'Charts: Intelligence Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Query Settings', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = ['0' => __( 'Current Chart (Dynamic)', 'charts' )];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => '0',
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Number of Tracks', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 10,
		] );

		$this->add_control( 'style_variant', [
			'label' => __( 'Layout Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ 
				'list' => 'Modern Minimal List', 
				'cards' => 'Floating Separate Cards', 
				'featured' => 'Top #1 Hero + List',
				'glass' => 'Glassmorphism Blurred',
				'terminal' => 'Retro Terminal Data'
			],
			'default' => 'list',
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_visibility', [ 'label' => __( 'Visibility', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );
		$this->add_control( 'show_cover', [ 'label' => 'Show Image', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_artist', [ 'label' => 'Show Artist', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_movement', [ 'label' => 'Show Movement Indicator', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'badge_new_text', [ 'label' => 'New Entry Text', 'type' => Controls_Manager::TEXT, 'default' => 'جديد' ] );
		$this->end_controls_section();

		// Styles
		$this->start_controls_section( 'style_layout', [ 'label' => __( 'Layout Spacing', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_responsive_control( 'row_gap', [
			'label' => __( 'Gap Between Rows (Cards)', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'condition' => [ 'style_variant' => ['cards', 'glass'] ],
			'selectors' => [ '{{WRAPPER}} .kc-table-wrap' => 'gap: {{SIZE}}{{UNIT}}; display: flex; flex-direction: column;' ],
		]);
		$this->add_responsive_control( 'row_padding', [
			'label' => __( 'Row Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-row-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_control( 'bg_color', [
			'label' => __( 'Background Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-row-item, {{WRAPPER}} .kc-table-wrap.is-list' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'border_color', [
			'label' => __( 'Border/Divider Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ 
				'{{WRAPPER}} .kc-table-wrap.is-list .kc-row-item' => 'border-bottom-color: {{VALUE}};',
				'{{WRAPPER}} .kc-table-wrap.is-cards .kc-row-item' => 'border-color: {{VALUE}};',
				'{{WRAPPER}} .kc-table-wrap.is-terminal .kc-row-item' => 'border-bottom-color: {{VALUE}};',
			],
		]);
		
		$this->add_control( 'row_hover_bg_color', [
			'label' => __( 'Row Hover Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-table-wrap.is-list .kc-row-item:hover' => 'background-color: {{VALUE}};', '{{WRAPPER}} .is-cards .kc-row-item:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'table_padding', [
			'label' => __( 'Container Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-table-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'table_border_radius', [
			'label' => __( 'Container Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-table-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'row_border_radius', [
			'label' => __( 'Row Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-row-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name' => 'table_box_shadow', 'selector' => '{{WRAPPER}} .kc-table-wrap',
		]);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name' => 'row_box_shadow', 'selector' => '{{WRAPPER}} .kc-row-item',
		]);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name' => 'row_hover_box_shadow', 'selector' => '{{WRAPPER}} .kc-row-item:hover',
		]);

		$this->end_controls_section();

		$this->start_controls_section( 'style_text', [ 'label' => __( 'Typography', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'title_color', [ 'label' => 'Title Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-title' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .kc-row-title' ] );
		$this->add_control( 'artist_color', [ 'label' => 'Artist Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-artist' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'artist_typo', 'selector' => '{{WRAPPER}} .kc-row-artist' ] );
		$this->add_control( 'rank_color', [ 'label' => 'Rank Number Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-rank' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'rank_typo', 'selector' => '{{WRAPPER}} .kc-row-rank' ] );
		$this->end_controls_section();
		
		$this->start_controls_section( 'style_movement', [ 'label' => __( 'Movement Badges', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_movement' => 'yes' ] ] );
		$this->add_control( 'up_color', [ 'label' => __( 'Up (▲) Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-up' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'down_color', [ 'label' => __( 'Down (▼) Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-down' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'new_bg', [ 'label' => __( 'New Badge Background', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-new' => 'background-color: {{VALUE}};' ] ] );
		$this->add_control( 'new_color', [ 'label' => __( 'New Badge Text', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-new' => 'color: {{VALUE}};' ] ] );
		$this->end_controls_section();
		
		$this->start_controls_section( 'style_image', [ 'label' => __( 'Image', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_responsive_control( 'image_border_radius', [
			'label' => __( 'Image Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-row-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name' => 'image_box_shadow', 'selector' => '{{WRAPPER}} .kc-row-img',
		]);
		$this->end_controls_section();

		$this->add_advanced_image_controls('{{WRAPPER}} .kc-ct-img-wrap', '{{WRAPPER}} .kc-row-img');
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		$def = (!isset($settings["chart_id"]) || $settings["chart_id"] === "0" || empty($settings["chart_id"])) ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($settings["chart_id"]);
		if ( ! $def ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">Widget Data Missing</h3>';
				echo '<p style="color:#94a3b8; margin:0;">Please select a specific chart from the Content settings. (Dynamic mode only works on Single Chart templates).</p>';
				echo '</div>';
			}
			return;
		}

		$limit = !empty($settings['limit']) ? intval($settings['limit']) : 10;
		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit );
		if ( empty($entries) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">No Chart Data Found</h3>';
				echo '<p style="color:#94a3b8; margin:0;">The selected chart does not have any active entries to display.</p>';
				echo '</div>';
			}
			return;
		}

		$variant = $settings['style_variant'] ?? 'list';
		$uid = 'kc-it-' . $this->get_id();
		
		// Map variants to classes
		$wrap_classes = "kc-table-wrap is-{$variant}";
		if ($variant === 'list' || $variant === 'featured') {
			$wrap_classes .= ' kc-has-box-shadow';
		}

		echo '<style>
		.' . $uid . ' { direction: rtl; font-family: "Cairo", "Inter", sans-serif; }
		.' . $uid . ' .kc-table-wrap { width: 100%; border-radius: 16px; overflow: hidden; }
		.' . $uid . ' .kc-has-box-shadow { background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; }
		
		.' . $uid . ' .kc-row-item { display: flex; align-items: center; gap: 20px; padding: 16px 24px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
		.' . $uid . ' .is-list .kc-row-item { border-bottom: 1px solid #f1f5f9; }
		.' . $uid . ' .is-list .kc-row-item:last-child { border-bottom: none; }
		.' . $uid . ' .is-list .kc-row-item:hover { background: rgba(0,0,0,0.02); padding-right: 32px; }
		
		.' . $uid . ' .is-cards .kc-row-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
		.' . $uid . ' .is-cards .kc-row-item:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); border-color: #cbd5e1; }
		
		.' . $uid . ' .is-glass { gap: 12px; display: flex; flex-direction: column; }
		.' . $uid . ' .is-glass .kc-row-item { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(12px); border-radius: 16px; color: #fff; }
		.' . $uid . ' .is-glass .kc-row-item:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
		
		.' . $uid . ' .is-terminal { background: #000; border: 1px solid #333; padding: 12px; }
		.' . $uid . ' .is-terminal .kc-row-item { border-bottom: 1px solid #222; font-family: monospace; color: #00ff66; padding: 12px 16px; }
		.' . $uid . ' .is-terminal .kc-row-item:hover { background: rgba(0,255,102,0.1); }
		
		.' . $uid . ' .kc-row-rank { font-size: 24px; font-weight: 900; width: 40px; text-align: center; flex-shrink: 0; }
		.' . $uid . ' .kc-row-img { width: 56px; height: 56px; border-radius: 10px; object-fit: cover; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
		.' . $uid . ' .is-terminal .kc-row-img { border-radius: 0; filter: grayscale(100%) contrast(150%); }
		
		.' . $uid . ' .kc-row-info { flex: 1; min-width: 0; }
		.' . $uid . ' .kc-row-title { font-size: 17px; font-weight: 800; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . ' .kc-row-artist { font-size: 14px; font-weight: 600; color: #64748b; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . ' .is-glass .kc-row-artist { color: rgba(255,255,255,0.7); }
		.' . $uid . ' .is-terminal .kc-row-title, .' . $uid . ' .is-terminal .kc-row-artist { color: #00ff66; font-weight: normal; }
		
		.' . $uid . ' .kc-row-move { font-size: 14px; font-weight: 800; font-family: "Inter", sans-serif; display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
		.' . $uid . ' .kc-move-up { color: #10b981; }
		.' . $uid . ' .kc-move-down { color: #f43f5e; }
		.' . $uid . ' .kc-move-new { background: #f59e0b; color: #fff; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
		
		/* HERO FEATURED ROW (#1) */
		.' . $uid . ' .kc-row-featured { padding: 40px; background: linear-gradient(135deg, rgba(0,0,0,0.03), transparent); position: relative; border-bottom: 2px solid #e2e8f0; }
		.' . $uid . ' .kc-row-featured .kc-row-rank { font-size: 60px; width: 70px; color: #e11d48; text-shadow: 2px 2px 0 rgba(225,29,72,0.2); }
		.' . $uid . ' .kc-row-featured .kc-row-img { width: 120px; height: 120px; border-radius: 16px; }
		.' . $uid . ' .kc-row-featured .kc-row-title { font-size: 32px; white-space: normal; line-height: 1.2; margin-bottom: 8px; }
		.' . $uid . ' .kc-row-featured .kc-row-artist { font-size: 18px; }
		.' . $uid . ' .kc-row-featured .kc-row-move { font-size: 20px; }
		
		@media (max-width: 768px) {
			.' . $uid . ' .kc-row-item { gap: 12px; padding: 12px 16px; }
			.' . $uid . ' .kc-row-img { width: 48px; height: 48px; }
			.' . $uid . ' .kc-row-rank { font-size: 20px; width: 30px; }
			.' . $uid . ' .kc-row-featured { flex-direction: column; text-align: center; padding: 24px; }
			.' . $uid . ' .kc-row-featured .kc-row-info { text-align: center; }
		}
		</style>';
		
		echo '<div class="' . $uid . ' kc-widget-wrap">';
		echo '<div class="' . $wrap_classes . '">';
		
		foreach ( $rows as $idx => $row ) {
			$is_featured = ($variant === 'featured' && $idx === 0);
			$res = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
			
			$img = '';
			if ($settings['show_cover'] === 'yes') {
				$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$row]);
				if (empty($img) && !empty($row->resolved_image)) $img = $row->resolved_image;
				if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
			}

			echo '<div class="kc-row-item ' . ($is_featured ? 'kc-row-featured' : '') . '">';
			
			// Rank
			echo '<div class="kc-row-rank">' . \Charts\Core\Transliteration::to_arabic_numerals($row->rank_position) . '</div>';
			
			// Movement
			if ($settings['show_movement'] === 'yes') {
				$dir = $row->movement_direction;
				$val = $row->movement_value;
				echo '<div class="kc-row-move">';
				if ($dir === 'up') echo '<span class="kc-move-up">↑ ' . \Charts\Core\Transliteration::to_arabic_numerals($val) . '</span>';
				elseif ($dir === 'down') echo '<span class="kc-move-down">↓ ' . \Charts\Core\Transliteration::to_arabic_numerals($val) . '</span>';
				elseif ($dir === 'new') echo '<span class="kc-move-new">' . esc_html($settings['badge_new_text']) . '</span>';
				else echo '<span style="color:#94a3b8;">-</span>';
				echo '</div>';
			}
			
			// Cover
			if ($settings['show_cover'] === 'yes') {
				echo '<img src="' . esc_url($img) . '" class="kc-row-img" alt="">';
			}
			
			// Info
			echo '<div class="kc-row-info">';
			echo '<h4 class="kc-row-title">' . esc_html($res['title']) . '</h4>';
			if ($settings['show_artist'] === 'yes' && !empty($res['subtitle'])) {
				echo '<p class="kc-row-artist">' . esc_html($res['subtitle']) . '</p>';
			}
			echo '</div>';
			
			echo '</div>'; // End Row
		}
		
		echo '</div></div>';
	}
}