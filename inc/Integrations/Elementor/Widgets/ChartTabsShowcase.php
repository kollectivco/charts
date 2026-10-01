<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartTabsShowcase extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_chart_tabs_showcase'; }
	public function get_title() { return __( 'Chart Tabs Showcase', 'charts' ); }
	public function get_icon() { return 'eicon-tabs'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Query Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

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

		$this->start_controls_section( 'tabs_style_section', [
			'label' => __( 'Tabs Styling', 'charts' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'tab_bg_active', [
			'label' => __( 'Active Tab Background', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn.active' => 'background: {{VALUE}}; border-color: {{VALUE}};' ],
			'default' => '#000000',
		] );
		
		$this->add_control( 'tab_text_active', [
			'label' => __( 'Active Tab Text', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn.active' => 'color: {{VALUE}};' ],
			'default' => '#ffffff',
		] );

		$this->add_control( 'tab_bg_inactive', [
			'label' => __( 'Inactive Tab Background', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn' => 'background: {{VALUE}};' ],
			'default' => '#ffffff',
		] );
		
		$this->add_control( 'tab_text_inactive', [
			'label' => __( 'Inactive Tab Text', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn' => 'color: {{VALUE}};' ],
			'default' => '#000000',
		] );

		$this->add_control( 'tab_border_color', [
			'label' => __( 'Tab Border Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-tab-btn' => 'border-color: {{VALUE}};' ],
			'default' => '#000000',
		] );

		$this->end_controls_section();

		$this->add_premium_badge_controls();

		// Responsive layout specifically for this circular grid
		$this->start_controls_section( 'premium_layout', [
			'label' => __( 'Grid Layout', 'charts' ),
			'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		$this->add_responsive_control( 'grid_columns', [
			'label' => __( 'Columns', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 1, 'max' => 10,
			'default' => 5, 'tablet_default' => 3, 'mobile_default' => 2,
			'selectors' => [ '{{WRAPPER}} .kc-panel-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );
		$this->add_responsive_control( 'grid_gap', [
			'label' => __( 'Spacing (Gap)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-panel-grid' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'hover_animation', [
			'label' => __( 'Hover Effect', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ 'none' => 'None', 'zoom' => 'Image Zoom', 'lift' => 'Lift Up' ],
			'default' => 'zoom',
		] );
		$this->end_controls_section();

		$this->add_granular_style_controls(['image', 'title', 'meta', 'counter']);
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
		$hover_anim = $settings['hover_animation'] ?? 'zoom';
		
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
		.' . $uid . '-wrap { width: 100%; font-family: "Inter", sans-serif; }
		.' . $uid . '-nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; margin-bottom: 40px; }
		.' . $uid . '-tab-btn { border: 2px solid #000; padding: 12px 24px; font-weight: 700; font-size: 15px; cursor: pointer; background: #fff; color: #000; transition: all 0.3s ease; text-transform: capitalize; }
		.' . $uid . '-tab-btn.active { background: #000; color: #fff; border-color: #000; }
		.' . $uid . '-panel { display: none; animation: kc-fade-in 0.4s ease; }
		.' . $uid . '-panel.active { display: block; }
		@keyframes kc-fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
		
		.' . $uid . '-grid { display: grid; gap: 32px; justify-items: center; }
		.' . $uid . '-item { display: flex; flex-direction: column; align-items: center; text-align: center; cursor: pointer; width: 100%; }
		.' . $uid . '-img-wrap { position: relative; width: 100%; max-width: 160px; aspect-ratio: 1/1; margin-bottom: 16px; border-radius: 50%; overflow: visible; }
		.' . $uid . '-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.4s ease; }
		.' . $uid . '-badge { position: absolute; top: 10px; right: 0px; background: #ec4899; color: #fff; font-size: 14px; font-weight: 900; padding: 4px 12px; border-radius: 4px; box-shadow: 0 2px 8px rgba(236, 72, 153, 0.4); z-index: 2; display: flex; align-items: center; gap: 4px; }
		
		.' . $uid . '-title { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; max-width: 100%; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
		.' . $uid . '-artist { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		';
		
		if ($hover_anim === 'zoom') {
			echo '.' . $uid . '-item:hover .' . $uid . '-img { transform: scale(1.08); }';
		} elseif ($hover_anim === 'lift') {
			echo '.' . $uid . '-item:hover .' . $uid . '-img-wrap { transform: translateY(-8px); }';
		}
		echo '</style>';

		echo '<div class="' . $uid . '-wrap">';
		
		// Tabs Nav
		echo '<div class="' . $uid . '-nav">';
		foreach ($charts_data as $i => $cd) {
			$active = ($i === 0) ? ' active' : '';
			echo '<button class="' . $uid . '-tab-btn kc-tab-btn' . $active . '" data-target="panel-' . $uid . '-' . $i . '">' . esc_html($cd['title']) . '</button>';
		}
		echo '</div>';
		
		// Tabs Content
		echo '<div class="' . $uid . '-content">';
		foreach ($charts_data as $i => $cd) {
			$active = ($i === 0) ? ' active' : '';
			echo '<div class="' . $uid . '-panel' . $active . '" id="panel-' . $uid . '-' . $i . '">';
			echo '<div class="' . $uid . '-grid kc-panel-grid">';
			
			foreach ($cd['tracks'] as $trk) {
				echo '<div class="' . $uid . '-item">';
				echo '<div class="' . $uid . '-img-wrap">';
				echo '<img src="' . esc_url($trk['image']) . '" class="' . $uid . '-img kc-elm-img">';
				
				$badge_content = '#' . \Charts\Core\Transliteration::to_arabic_numerals($trk['rank']);
				if ($settings['show_badges'] === 'yes' && $trk['is_new']) {
					$badge_content = '🌟 ' . esc_html($settings['badge_new_text']);
				}
				echo '<div class="' . $uid . '-badge kc-elm-counter">' . $badge_content . '</div>';
				echo '</div>';
				
				echo '<h4 class="' . $uid . '-title kc-elm-title">' . esc_html($trk['title']) . '</h4>';
				echo '<p class="' . $uid . '-artist kc-elm-artist">' . esc_html($trk['artist']) . '</p>';
				echo '</div>';
			}
			
			echo '</div></div>';
		}
		echo '</div>'; // End Content
		
		echo '</div>'; // End Wrap

		// Inline JS for tabs
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
