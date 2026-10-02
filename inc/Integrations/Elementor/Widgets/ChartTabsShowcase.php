<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartTabsShowcase extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_chart_tabs_showcase'; }
	public function get_title() { return __( 'Charts: Tabs Showcase', 'charts' ); }
	public function get_icon() { return 'eicon-tabs'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// --- CONTENT ---
		$this->start_controls_section( 'content_section', [ 'label' => __( 'Query Settings', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = [];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_ids', [
			'label' => __( 'Select Charts for Tabs', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $chart_options,
			'default' => !empty($chart_options) ? array_keys(array_slice($chart_options, 0, 4, true)) : [],
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Tracks per Chart', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
			'min' => 1,
			'max' => 10,
		] );

		$this->add_control( 'style_variant', [
			'label' => __( 'Layout Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'circles' => 'Circular Avatars (Original)',
				'cards_overlay' => 'Cards Overlay',
				'pills' => 'iOS Pills + Clean List',
				'minimal' => 'Underline Tabs + Minimal List',
				'glass' => 'Glassmorphism List',
				'blocks' => 'Dark Tech Blocks'
			],
			'default' => 'circles',
		] );
		
		$this->add_control( 'show_tabs', [
			'label' => __( 'Show Tabs Navigation', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'description' => 'If hidden, only the first selected chart will be displayed.',
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'style_circles', [ 'label' => __( 'Circle & Card Settings', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => ['style_variant' => ['circles', 'cards_overlay']] ] );
		$this->add_control( 'circle_gradient_1', [ 'label' => 'Gradient Color 1', 'type' => Controls_Manager::COLOR, 'default' => '#FF8000', 'selectors' => [ '{{WRAPPER}}' => '--kc-ring-1: {{VALUE}};' ] ] );
		$this->add_control( 'circle_gradient_2', [ 'label' => 'Gradient Color 2', 'type' => Controls_Manager::COLOR, 'default' => '#FF2E93', 'selectors' => [ '{{WRAPPER}}' => '--kc-ring-2: {{VALUE}};' ] ] );
		$this->add_control( 'badge_bg', [ 'label' => 'Badge Background', 'type' => Controls_Manager::COLOR, 'default' => '#0f172a', 'selectors' => [ '{{WRAPPER}} .kc-ts-rank-badge' => 'background-color: {{VALUE}};' ] ] );
		$this->add_control( 'badge_color', [ 'label' => 'Badge Text', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => [ '{{WRAPPER}} .kc-ts-rank-badge' => 'color: {{VALUE}};' ] ] );
		$this->end_controls_section();

		// --- STYLING: TABS ---
		$this->start_controls_section( 'style_tabs', [ 'label' => __( 'Tabs Navigation', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		
		$this->add_control( 'tab_nav_bg', [
			'label' => __( 'Nav Container Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-nav-wrap' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'tab_nav_padding', [
			'label' => __( 'Nav Container Padding', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-nav-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'tab_nav_border_radius', [
			'label' => __( 'Nav Border Radius', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-nav-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'tab_bg', [
			'label' => __( 'Tab Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_active_bg', [
			'label' => __( 'Active Tab Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab.is-active' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_text_color', [
			'label' => __( 'Tab Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_active_text_color', [
			'label' => __( 'Active Tab Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab.is-active' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'tab_active_shadow',
				'label' => __( 'Active Tab Box Shadow', 'charts' ),
				'selector' => '{{WRAPPER}} .kc-ts-tab.is-active',
			]
		);
		$this->add_responsive_control( 'tab_padding', [
			'label' => __( 'Tab Padding', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'tab_border_radius', [
			'label' => __( 'Tab Border Radius', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'tab_typo', 'selector' => '{{WRAPPER}} .kc-ts-tab' ] );
		$this->end_controls_section();

		// --- STYLING: LIST ---
		$this->start_controls_section( 'style_list', [ 'label' => __( 'List Items / Cards', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'list_bg', [
			'label' => __( 'Item Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-row' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'list_hover_bg', [
			'label' => __( 'Item Hover Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-row:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'list_padding', [
			'label' => __( 'Item Padding', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'list_border_radius', [
			'label' => __( 'Item Border Radius', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .kc-ts-row' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'list_border',
				'selector' => '{{WRAPPER}} .kc-ts-row',
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'list_box_shadow',
				'label' => __( 'Box Shadow', 'charts' ),
				'selector' => '{{WRAPPER}} .kc-ts-row',
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'list_hover_box_shadow',
				'label' => __( 'Hover Box Shadow', 'charts' ),
				'selector' => '{{WRAPPER}} .kc-ts-row:hover',
			]
		);
		$this->add_control( 'list_hover_transform', [
			'label' => __( 'Hover Transform Y (px)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'selectors' => [
				'{{WRAPPER}} .kc-ts-row:hover' => 'transform: translateY({{VALUE}}px);',
			],
		] );

		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-title' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .kc-ts-title' ] );
		$this->add_control( 'artist_color', [
			'label' => __( 'Subtitle/Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-artist' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'artist_typo', 'selector' => '{{WRAPPER}} .kc-ts-artist' ] );
		$this->add_control( 'rank_color', [
			'label' => __( 'Rank Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-rank-list, {{WRAPPER}} .kc-ts-rank-badge' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'rank_typo', 'selector' => '{{WRAPPER}} .kc-ts-rank-list, {{WRAPPER}} .kc-ts-rank-badge' ] );
		$this->end_controls_section();
	}

	
	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_ids = $settings['chart_ids'];
		
		echo '<div class="kc-widget-wrap">';
		if (empty($chart_ids)) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055; direction:ltr;">';
				echo '<h3>Tabs Data Missing</h3><p>Please select at least one chart from the Content settings.</p></div>';
			}
			echo '</div>';
			return;
		}

		$limit = $settings['limit'];
		$uid = 'kc-ts-' . $this->get_id();
		$variant = $settings['style_variant'] ?? 'circles';
		
		$manager = new \Charts\Admin\SourceManager();
		$charts_data = [];
		foreach ($chart_ids as $cid) {
			$def = ($cid === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($cid);
			if (!$def) continue;
			$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
			if (empty($entries)) continue;
			
			$tracks = [];
			foreach($entries as $e) {
				$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$e]);
				if (empty($img) && !empty($e->resolved_image)) $img = $e->resolved_image;
				if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
				
				$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
				$tracks[] = [
					'rank' => \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position),
					'title' => $resolved['title'],
					'artist' => $resolved['subtitle'],
					'image' => $img,
					'is_new' => ($e->movement_direction === 'new')
				];
			}
			$charts_data[] = [ 'id' => $cid, 'title' => $def->title, 'tracks' => $tracks ];
		}

		if (empty($charts_data)) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3>No Chart Data</h3><p>The selected charts do not have any active entries to display.</p></div>';
			}
			echo '</div>';
			return;
		}
?>
		<style>
		.<?php echo $uid; ?>-wrap { width: 100%; direction: rtl; font-family: "Cairo", sans-serif; --kc-ring-1: <?php echo $settings['circle_gradient_1'] ?? '#FF8000'; ?>; --kc-ring-2: <?php echo $settings['circle_gradient_2'] ?? '#FF2E93'; ?>; }
		
		/* Base Layout */
		.<?php echo $uid; ?>-nav { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 40px; }
		.<?php echo $uid; ?>-tab { cursor: pointer; transition: all 0.3s; font-weight: 800; }
		.<?php echo $uid; ?>-content { display: none; opacity: 0; transition: opacity 0.4s ease; }
		.<?php echo $uid; ?>-content.is-active { display: block; opacity: 1; animation: kcTabFade 0.4s forwards; }
		@keyframes kcTabFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
		
		.<?php echo $uid; ?>-rank-badge { display: none; }
		.<?php echo $uid; ?>-overlay { display: none; }
		
		/* List Variants (pills, minimal, glass, blocks) */
		.kc-variant-list .<?php echo $uid; ?>-row { display: flex; align-items: center; gap: 20px; transition: all 0.3s; }
		.kc-variant-list .<?php echo $uid; ?>-img-wrap { width: 64px; height: 64px; flex-shrink: 0; }
		.kc-variant-list .<?php echo $uid; ?>-img { width: 100%; height: 100%; object-fit: cover; }
		.kc-variant-list .<?php echo $uid; ?>-rank-list { font-size: 24px; font-weight: 900; width: 40px; text-align: center; flex-shrink: 0; }
		.kc-variant-list .<?php echo $uid; ?>-info { flex: 1; min-width: 0; }
		.kc-variant-list .<?php echo $uid; ?>-title { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.kc-variant-list .<?php echo $uid; ?>-artist { font-size: 14px; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

		/* Style 1: Pills */
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-nav-wrap { background: #f1f5f9; padding: 6px; border-radius: 40px; display: inline-flex; margin: 0 auto; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-nav { margin-bottom: 40px; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-tab { padding: 12px 24px; border-radius: 40px; color: #64748b; font-size: 15px; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-tab.is-active { background: #fff; color: #0f172a; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-row { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #fff; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-row:hover { background: #f8fafc; transform: translateX(-4px); }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-img { border-radius: 12px; }

		/* Style 2: Minimal */
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-nav { border-bottom: 2px solid #e2e8f0; gap: 32px; justify-content: flex-start; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-tab { padding: 0 0 16px 0; color: #94a3b8; font-size: 18px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-tab.is-active { color: #0f172a; border-bottom-color: #0f172a; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-row { padding: 24px 0; border-bottom: 1px solid #f1f5f9; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-row:hover { opacity: 0.8; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-rank-list { color: #94a3b8; font-family: monospace; font-size: 18px; }

		/* Style 3: Glass */
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-nav { gap: 16px; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-tab { padding: 12px 24px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: rgba(255,255,255,0.7); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-tab.is-active { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.3); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-row { padding: 16px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; margin-bottom: 12px; color: #fff; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-row:hover { background: rgba(255,255,255,0.1); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-img { border-radius: 50%; }

		/* Style 4: Blocks */
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-nav { gap: 8px; background: #0f172a; padding: 8px; border-radius: 12px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-tab { padding: 14px 28px; background: transparent; color: #64748b; border-radius: 8px; font-size: 13px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-tab.is-active { background: #334155; color: #fff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-row { padding: 20px; background: #0f172a; border-radius: 16px; margin-bottom: 8px; border-left: 4px solid #3b82f6; color: #fff; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-row:hover { transform: scale(1.01); background: #1e293b; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-img { border-radius: 8px; }

		/* Style 5: Circles (Original Style) */
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-rank-list { display: none; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-content.is-active { display: flex; justify-content: center; gap: 32px; flex-wrap: wrap; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-row { display: flex; flex-direction: column; align-items: center; text-align: center; width: 140px; cursor: pointer; transition: transform 0.2s; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-row:hover { transform: translateY(-5px); }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-img-wrap { position: relative; width: 130px; height: 130px; border-radius: 50%; padding: 4px; background: linear-gradient(45deg, var(--kc-ring-1), var(--kc-ring-2)); margin-bottom: 16px; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 3px solid #fff; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-rank-badge { display: flex; position: absolute; top: 0; right: 0; width: 32px; height: 32px; font-size: 16px; font-weight: 900; border-radius: 50%; border: 2px solid #fff; align-items: center; justify-content: center; z-index: 2; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-title { font-size: 16px; font-weight: 800; margin: 0 0 4px 0; white-space: normal; line-height: 1.3; }
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-artist { font-size: 13px; font-weight: 600; color: #64748b; margin: 0; white-space: normal; }

		/* Circles: Classic Tab Nav (matching reference image) */
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-nav-wrap {
			background: #ededf3;
			padding: 8px;
			border-radius: 60px;
			display: inline-flex;
			position: relative;
			border-top: 3px solid transparent;
			background-clip: padding-box;
		}
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-nav-wrap::before {
			content: '';
			position: absolute;
			top: -3px; left: 0; right: 0;
			height: 3px;
			border-radius: 60px 60px 0 0;
			background: linear-gradient(to left, var(--kc-ring-1, #FF8000), var(--kc-ring-2, #FF2E93));
		}
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-nav {
			display: flex;
			gap: 4px;
			margin-bottom: 0;
			flex-wrap: nowrap;
		}
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-tab {
			padding: 14px 32px;
			border-radius: 50px;
			font-size: 18px;
			font-weight: 700;
			color: #64748b;
			transition: all 0.25s;
			white-space: nowrap;
		}
		.<?php echo $uid; ?>-circles .<?php echo $uid; ?>-tab.is-active {
			background: #ffffff;
			color: #1e293b;
			font-weight: 900;
			box-shadow: 0 4px 20px rgba(0,0,0,0.08);
		}


		/* Style 6: Cards Overlay */
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-rank-list { display: none; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-content.is-active { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-row { position: relative; border-radius: 20px; overflow: hidden; height: 240px; display: flex; align-items: flex-end; transition: transform 0.3s; cursor: pointer; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-row:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-img-wrap { position: absolute; inset: 0; z-index: 1; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-img { width: 100%; height: 100%; object-fit: cover; border-radius: 0; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-overlay { display: block; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 70%); z-index: 2; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-rank-badge { display: flex; position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; font-size: 15px; font-weight: 900; border-radius: 50%; align-items: center; justify-content: center; z-index: 3; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-info { position: relative; z-index: 3; padding: 20px; width: 100%; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-title { font-size: 16px; font-weight: 800; color: #fff; margin: 0 0 4px 0; white-space: normal; line-height: 1.3; }
		.<?php echo $uid; ?>-cards_overlay .<?php echo $uid; ?>-artist { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.7); margin: 0; white-space: normal; }
		</style>

		<?php
		$is_list_variant = in_array($variant, ['pills', 'minimal', 'glass', 'blocks']);
		$wrap_class = $uid . '-wrap ' . $uid . '-' . $variant . ($is_list_variant ? ' kc-variant-list' : '');
		?>

		<div class="<?php echo esc_attr($wrap_class); ?>">
			<?php if ($settings['show_tabs'] === 'yes' && count($charts_data) > 1) : ?>
			<div style="text-align: center;">
				<div class="<?php echo $uid; ?>-nav-wrap kc-ts-nav-wrap">
					<div class="<?php echo $uid; ?>-nav kc-ts-nav">
						<?php foreach ($charts_data as $i => $c) : ?>
							<div class="<?php echo $uid; ?>-tab kc-ts-tab <?php echo $i === 0 ? 'is-active' : ''; ?>" data-target="<?php echo $uid; ?>-c-<?php echo $i; ?>">
								<?php echo esc_html($c['title']); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<div class="<?php echo $uid; ?>-panels kc-ts-panels">
				<?php foreach ($charts_data as $i => $c) : ?>
					<div id="<?php echo $uid; ?>-c-<?php echo $i; ?>" class="<?php echo $uid; ?>-content kc-ts-content <?php echo $i === 0 ? 'is-active' : ''; ?>">
						<?php foreach ($c['tracks'] as $track) : ?>
							<div class="<?php echo $uid; ?>-row kc-ts-row">
								<div class="<?php echo $uid; ?>-rank-list kc-ts-rank-list"><?php echo $track['rank']; ?></div>
								
								<div class="<?php echo $uid; ?>-img-wrap kc-ts-img-wrap">
									<img src="<?php echo esc_url($track['image']); ?>" class="<?php echo $uid; ?>-img kc-ts-img" alt="">
									<div class="<?php echo $uid; ?>-rank-badge kc-ts-rank-badge kc-ts-rank-badge"><?php echo $track['rank']; ?></div>
									<div class="<?php echo $uid; ?>-overlay kc-ts-overlay"></div>
								</div>
								
								<div class="<?php echo $uid; ?>-info kc-ts-info">
									<h4 class="<?php echo $uid; ?>-title kc-ts-title kc-ts-title"><?php echo esc_html($track['title']); ?></h4>
									<p class="<?php echo $uid; ?>-artist kc-ts-artist kc-ts-artist"><?php echo esc_html($track['artist']); ?></p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script>
		document.addEventListener("DOMContentLoaded", function() {
			var tabs = document.querySelectorAll('.<?php echo $uid; ?>-tab');
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function() {
					var parent = this.closest('.<?php echo $uid; ?>-wrap');
					parent.querySelectorAll('.<?php echo $uid; ?>-tab').forEach(function(t) { t.classList.remove('is-active'); });
					parent.querySelectorAll('.<?php echo $uid; ?>-content').forEach(function(c) { c.classList.remove('is-active'); });
					
					this.classList.add('is-active');
					var target = document.getElementById(this.getAttribute('data-target'));
					if(target) target.classList.add('is-active');
				});
			});
		});
		</script>
<?php
		echo '</div>';
	}
}