<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
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
		$this->end_controls_section();

		// --- TABS STYLING ---
		$this->start_controls_section( 'tabs_style_section', [ 'label' => __( 'Modern Tabs Styling', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'tab_wrapper_bg', [
			'label' => __( 'Tabs Container BG', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tabs-wrapper' => 'background: {{VALUE}};' ], 'default' => 'rgba(241, 245, 249, 0.8)',
		] );
		$this->add_control( 'tab_bg_active', [
			'label' => __( 'Active Tab BG', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn.active' => 'background: {{VALUE}};' ], 'default' => '#ffffff',
		] );
		$this->add_control( 'tab_text_active', [
			'label' => __( 'Active Tab Text', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn.active' => 'color: {{VALUE}};' ], 'default' => '#0f172a',
		] );
		$this->add_control( 'tab_text_inactive', [
			'label' => __( 'Inactive Tab Text', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn' => 'color: {{VALUE}};' ], 'default' => '#64748b',
		] );
		$this->end_controls_section();

		// --- RING & IMAGE STYLING ---
		$this->start_controls_section( 'ring_style_section', [ 'label' => __( 'Image & Gradient Ring', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'ring_color_1', [
			'label' => __( 'Ring Gradient Color 1', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}}' => '--kc-ring-1: {{VALUE}};' ], 'default' => '#FF2E93',
		] );
		$this->add_control( 'ring_color_2', [
			'label' => __( 'Ring Gradient Color 2', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}}' => '--kc-ring-2: {{VALUE}};' ], 'default' => '#FF8000',
		] );
		$this->add_responsive_control( 'image_size', [
			'label' => __( 'Image Size', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 80, 'max' => 250, 'step' => 1 ] ],
			'default' => [ 'size' => 160, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-item-img-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		] );
		
		$this->add_responsive_control( 'image_radius', [
			'label' => __( 'Image Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-item-img-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', '{{WRAPPER}} .kc-item-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
$this->end_controls_section();

		// --- RANK BADGE ---
		$this->start_controls_section( 'badge_style_section', [ 'label' => __( 'Rank Badge', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'badge_bg', [
			'label' => __( 'Badge Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-rank-badge' => 'background: {{VALUE}};' ], 'default' => '#0f172a',
		] );
		$this->add_control( 'badge_color', [
			'label' => __( 'Badge Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-rank-badge' => 'color: {{VALUE}};' ], 'default' => '#ffffff',
		] );
				$this->add_responsive_control( 'badge_pos_top', [
			'label' => __( 'Badge Offset Top/Bottom', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => -50, 'max' => 100, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-rank-badge' => 'top: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'badge_pos_right', [
			'label' => __( 'Badge Offset Left/Right', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => -50, 'max' => 100, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-rank-badge' => 'right: {{SIZE}}{{UNIT}};' ],
		] );
$this->end_controls_section();

		$this->add_premium_badge_controls();

		// --- GRID LAYOUT ---
		$this->start_controls_section( 'premium_layout', [ 'label' => __( 'Grid Layout', 'charts' ), 'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT ] );
		$this->add_responsive_control( 'grid_columns', [
			'label' => __( 'Columns', 'charts' ), 'type' => Controls_Manager::NUMBER,
			'min' => 1, 'max' => 10, 'default' => 5, 'tablet_default' => 3, 'mobile_default' => 2,
			'selectors' => [ '{{WRAPPER}} .kc-panel-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );
		$this->add_responsive_control( 'grid_gap', [
			'label' => __( 'Spacing (Gap)', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-panel-grid' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->end_controls_section();

		$this->add_granular_style_controls(['title', 'meta']);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_ids = $settings['chart_ids'];
		
		echo '<div class="kc-widget-wrap">';
		if (empty($chart_ids)) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">Please select at least one chart.</div></div>';
			return;
		}

		$limit = $settings['limit'];
		$uid = 'kc-cts-' . $this->get_id();
		
		$manager = new \Charts\Admin\SourceManager();
		$charts_data = [];
		foreach ($chart_ids as $cid) {
			$def = $manager->get_definition($cid);
			if (!$def) continue;
			$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
			if (empty($entries)) continue;
			
			$tracks = [];
			foreach($entries as $e) {
				$img = $e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
				$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
				$tracks[] = [
					'rank' => $e->rank_position,
					'title' => $resolved['title'],
					'artist' => $resolved['subtitle'],
					'image' => $img,
					'is_new' => ($e->movement_direction === 'new')
				];
			}
			$charts_data[] = [ 'id' => $cid, 'title' => $def->title, 'tracks' => $tracks ];
		}

		if (empty($charts_data)) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; color:#64748b;">No tracks found for selected charts.</div></div>';
			return;
		}

		echo '<style>
		:where(.' . $uid . '-wrap) { 
			width: 100%; 
			font-family: "Inter", -apple-system, sans-serif; 
			--kc-ring-1: #FF2E93;
			--kc-ring-2: #FF8000;
		}
		
		/* iOS Style Pill Tabs */
		:where(.' . $uid . '-nav-container) { display: flex; justify-content: center; width: 100%; margin-bottom: 48px; }
		.' . $uid . '-tabs-wrapper {
			background: rgba(241, 245, 249, 0.8);
			backdrop-filter: blur(12px);
			border-radius: 100px;
			padding: 6px;
			display: inline-flex;
			gap: 8px;
			flex-wrap: wrap;
			justify-content: center;
			box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
			border: 1px solid rgba(0,0,0,0.05);
		}
		.' . $uid . '-tab-btn { 
			background: transparent; 
			border: none; 
			padding: 12px 28px; 
			font-weight: 800; 
			font-size: 15px; 
			color: #64748b; 
			border-radius: 100px;
			cursor: pointer; 
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
			text-transform: capitalize;
			position: relative;
		}
		.' . $uid . '-tab-btn:hover { color: #0f172a; }
		.' . $uid . '-tab-btn.active { 
			background: #fff; 
			color: #0f172a; 
			box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04); 
		}
		
		/* Panels */
		.' . $uid . '-panel { display: none; animation: kc-fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(15px); }
		.' . $uid . '-panel.active { display: block; }
		@keyframes kc-fade-up { to { opacity: 1; transform: translateY(0); } }
		
		/* Grid */
		.' . $uid . '-grid { display: grid; gap: 40px 24px; justify-items: center; width: 100%; }
		.' . $uid . '-item { display: flex; flex-direction: column; align-items: center; text-align: center; cursor: pointer; width: 100%; }
		
		/* Gorgeous Ring Image */
		.' . $uid . '-item-img-wrap { 
			position: relative; 
			display: flex;
			align-items: center;
			justify-content: center; 
			width: 160px; 
			aspect-ratio: 1/1; box-sizing: border-box; 
			border-radius: 50% !important; 
			padding: 5px; 
			background: linear-gradient(135deg, var(--kc-ring-1) 0%, var(--kc-ring-2) 100%);
			margin-bottom: 24px;
			box-shadow: 0 12px 28px rgba(0,0,0,0.1);
			transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.' . $uid . '-item-img { 
			display: block !important;
			width: 100% !important; 
			height: 100% !important; 
			aspect-ratio: 1/1 !important;
			box-sizing: border-box !important; 
			border-radius: 50% !important; 
			border: 4px solid #fff !important; 
			object-fit: cover !important; 
			transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1); 
		}
		.' . $uid . '-item:hover .' . $uid . '-item-img-wrap { 
			transform: translateY(-10px) scale(1.02); 
			box-shadow: 0 20px 40px rgba(0,0,0,0.15); 
		}
		.' . $uid . '-item:hover .' . $uid . '-item-img { 
			transform: scale(1.05); 
		}

		/* Premium Circular Badge */
		.' . $uid . '-rank-badge { 
			position: absolute; 
			top: -4px; 
			right: -4px; 
			width: 38px; 
			height: 38px; 
			background: #0f172a; 
			color: #fff; 
			font-size: 16px; 
			font-weight: 900; 
			display: flex; 
			align-items: center; 
			justify-content: center; 
			border-radius: 50% !important; 
			border: 3px solid #fff; 
			box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
			z-index: 2;
		}
		
		/* New Badge styling (Top Left instead) */
		.' . $uid . '-new-badge {
			position: absolute;
			bottom: -8px;
			left: 50%;
			transform: translateX(-50%);
			background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%);
			color: #fff;
			font-size: 11px;
			font-weight: 900;
			padding: 4px 12px;
			border-radius: 100px;
			border: 2px solid #fff;
			box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
			z-index: 3;
			white-space: nowrap;
			letter-spacing: 0.05em;
		}
		
		/* Typography */
		.' . $uid . '-title { 
			font-size: 18px; 
			font-weight: 800; 
			color: #0f172a; 
			margin: 0 0 6px 0; 
			max-width: 100%; 
			display: -webkit-box; 
			-webkit-line-clamp: 2; 
			-webkit-box-orient: vertical; 
			overflow: hidden; 
			line-height: 1.3; 
		}
		.' . $uid . '-artist { 
			font-size: 13px; 
			font-weight: 700; 
			color: #64748b; 
			text-transform: uppercase; 
			letter-spacing: 0.05em; 
			margin: 0; 
			max-width: 100%; 
			white-space: nowrap; 
			overflow: hidden; 
			text-overflow: ellipsis; 
		}
		</style>';

		echo '<div class="' . $uid . '-wrap">';
		
		// Tabs Nav
		echo '<div class="' . $uid . '-nav-container">';
		echo '<div class="' . $uid . '-tabs-wrapper kc-tabs-wrapper">';
		foreach ($charts_data as $i => $cd) {
			$active = ($i === 0) ? ' active' : '';
			echo '<button class="' . $uid . '-tab-btn kc-tab-btn' . $active . '" data-target="panel-' . $uid . '-' . $i . '">' . esc_html($cd['title']) . '</button>';
		}
		echo '</div></div>';
		
		// Tabs Content
		echo '<div class="' . $uid . '-content">';
		foreach ($charts_data as $i => $cd) {
			$active = ($i === 0) ? ' active' : '';
			echo '<div class="' . $uid . '-panel' . $active . '" id="panel-' . $uid . '-' . $i . '">';
			echo '<div class="' . $uid . '-grid kc-panel-grid">';
			
			foreach ($cd['tracks'] as $trk) {
				echo '<div class="' . $uid . '-item">';
				
				echo '<div class="' . $uid . '-item-img-wrap kc-item-img-wrap">';
				echo '<img src="' . esc_url($trk['image']) . '" class="' . $uid . '-item-img kc-elm-img">';
				
				$rank = \Charts\Core\Transliteration::to_arabic_numerals($trk['rank']);
				echo '<div class="' . $uid . '-rank-badge kc-rank-badge">' . $rank . '</div>';
				
				if ($settings['show_badges'] === 'yes' && $trk['is_new']) {
					echo '<div class="' . $uid . '-new-badge">' . esc_html($settings['badge_new_text']) . '</div>';
				}
				echo '</div>';
				
				echo '<h4 class="' . $uid . '-title kc-elm-title">' . esc_html($trk['title']) . '</h4>';
				echo '<p class="' . $uid . '-artist kc-elm-meta">' . esc_html($trk['artist']) . '</p>';
				echo '</div>';
			}
			
			echo '</div></div>';
		}
		echo '</div>'; // End Content
		
		echo '</div>'; // End Wrap

		// Inline JS
		echo '<script>
		(function() {
			var init = function() {
				var wrap = document.querySelector(".' . $uid . '-wrap");
				if(!wrap) return;
				if (wrap.dataset.initialized) return;
				wrap.dataset.initialized = "true";
				var btns = wrap.querySelectorAll(".' . $uid . '-tab-btn");
				var panels = wrap.querySelectorAll(".' . $uid . '-panel");
				btns.forEach(function(btn) {
					btn.addEventListener("click", function() {
						btns.forEach(function(b){ b.classList.remove("active"); });
						panels.forEach(function(p){ p.classList.remove("active"); });
						btn.classList.add("active");
						var targetId = btn.getAttribute("data-target");
						var targetPanel = wrap.querySelector("#" + targetId);
						if (targetPanel) targetPanel.classList.add("active");
					});
				});
			};
			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", init);
			} else {
				init();
			}
			setTimeout(init, 500);
		})();
		</script>';
		echo '</div>';
	}
}
