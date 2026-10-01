<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartShowcaseSlider extends Widget_Base {

	public function get_name() { return 'kc_chart_showcase_slider'; }
	public function get_title() { return __( 'Premium Showcase Slider', 'charts' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [ 'label' => __( 'Chart Settings', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = [];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => !empty($chart_options) ? array_key_first($chart_options) : '',
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Number of Cards', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 8,
		] );
		
		$this->add_control( 'header_title', [
			'label' => __( 'Custom Header Title', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => '',
			'description' => 'Leave empty to use the Chart Name.'
		] );
		
		$this->add_control( 'more_link', [
			'label' => __( 'More Button URL', 'charts' ),
			'type' => Controls_Manager::URL,
			'placeholder' => __( 'https://your-link.com', 'charts' ),
			'default' => [ 'url' => '' ],
		] );
		
		$this->add_control( 'more_text', [
			'label' => __( 'More Button Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'المزيد',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style_backgrounds', [ 'label' => __( 'Exact Backgrounds', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'bg_desc', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => __( 'Upload the exact background images for the cards to match your reference exactly. They will cycle in order.', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );
		
		for ($i = 1; $i <= 4; $i++) {
			$this->add_control( "bg_$i", [
				'label' => __( "Card Background $i", 'charts' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [ 'url' => Utils::get_placeholder_image_src() ],
			] );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'style_cards', [ 'label' => __( 'Cards Layout', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_responsive_control( 'card_height', [
			'label' => __( 'Card Height', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 200, 'max' => 600, 'step' => 1 ] ],
			'default' => [ 'size' => 380, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-sc-card' => 'height: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'card_width', [
			'label' => __( 'Card Width', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 150, 'max' => 400, 'step' => 1 ] ],
			'default' => [ 'size' => 280, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-sc-card' => 'flex: 0 0 {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'card_radius', [
			'label' => __( 'Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS,
			'selectors' => [ '{{WRAPPER}} .kc-sc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'crown_color', [
			'label' => __( 'Crown Scribble Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-sc-crown path' => 'stroke: {{VALUE}};' ],
			'default' => '#fff',
		] );
		$this->end_controls_section();
		
		$this->start_controls_section( 'style_typography', [ 'label' => __( 'Typography & Colors', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'header_color', [ 'label' => 'Header Title Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-sc-header-title' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'label' => 'Header Title', 'selector' => '{{WRAPPER}} .kc-sc-header-title' ] );
		$this->add_group_control( Group_Control_Text_Shadow::get_type(), [ 'name' => 'title_shadow', 'selector' => '{{WRAPPER}} .kc-sc-header-title' ] );
		
		$this->add_control( 'num_color', [ 'label' => 'Big Number Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-sc-num' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'num_typo', 'label' => 'Big Number', 'selector' => '{{WRAPPER}} .kc-sc-num' ] );
		
		$this->add_control( 'artist_color', [ 'label' => 'Artist Name Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-sc-artist' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'artist_typo', 'label' => 'Artist Name', 'selector' => '{{WRAPPER}} .kc-sc-artist' ] );
		
		$this->add_control( 'nav_color', [ 'label' => 'Next Arrow Color', 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-sc-nav-btn.kc-next' => 'border-color: {{VALUE}}; color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if (empty($settings['chart_id'])) return;

		$manager = new \Charts\Admin\SourceManager();
		$def = $manager->get_definition($settings['chart_id']);
		if (!$def) return;

		$limit = $settings['limit'] ?? 8;
		$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
		if (empty($entries)) return;

		$uid = 'kc-sc-' . $this->get_id();
		$title = !empty($settings['header_title']) ? $settings['header_title'] : $def->title;
		$more_url = !empty($settings['more_link']['url']) ? $settings['more_link']['url'] : home_url('/charts/' . $def->slug);
		
		$bgs = [];
		for ($i = 1; $i <= 4; $i++) {
			$bgs[] = !empty($settings["bg_$i"]['url']) ? $settings["bg_$i"]['url'] : '';
		}

		echo '<style>
		.' . $uid . ' { width: 100%; overflow: hidden; position: relative; font-family: "Inter", -apple-system, sans-serif; direction: rtl; }
		.' . $uid . ' .kc-sc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; padding: 0 8px; }
		.' . $uid . ' .kc-sc-header-right { position: relative; }
		.' . $uid . ' .kc-sc-header-title { margin: 0; color: #e11d48; font-size: 50px; font-weight: 900; line-height: 1; letter-spacing: -1px; }
		.' . $uid . ' .kc-sc-header-crown { position: absolute; top: -30px; left: 10px; width: 40px; transform: rotate(-15deg); }
		.' . $uid . ' .kc-sc-more { color: #cbd5e1; text-decoration: none; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; transition: color 0.3s; padding-bottom: 8px; }
		.' . $uid . ' .kc-sc-more:hover { color: #fff; }
		
		.' . $uid . ' .kc-sc-track-wrap { position: relative; width: 100%; }
		.' . $uid . ' .kc-sc-track { display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 16px; }
		.' . $uid . ' .kc-sc-track::-webkit-scrollbar { display: none; }
		
		.' . $uid . ' .kc-sc-card { 
			position: relative; 
			border-radius: 12px; 
			overflow: hidden; 
			scroll-snap-align: start; 
			cursor: pointer;
			background-color: #111;
			background-size: cover;
			background-position: center;
			transition: transform 0.4s ease;
		}
		.' . $uid . ' .kc-sc-card:hover { transform: translateY(-8px); }
		
		.' . $uid . ' .kc-sc-num { position: absolute; top: 16px; left: 16px; font-size: 80px; font-weight: 900; color: #fff; line-height: 1; z-index: 3; }
		.' . $uid . ' .kc-sc-crown { position: absolute; top: 20px; right: 20px; width: 45px; height: 45px; opacity: 0.9; z-index: 3; }
		
		.' . $uid . ' .kc-sc-img-wrap { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; justify-content: flex-end; }
		.' . $uid . ' .kc-sc-img { width: 100%; height: 90%; object-fit: contain; object-position: bottom; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.6)); transition: transform 0.5s ease; }
		.' . $uid . ' .kc-sc-card:hover .kc-sc-img { transform: scale(1.05); }
		
		.' . $uid . ' .kc-sc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.1) 40%, transparent 100%); z-index: 3; pointer-events: none; }
		
		.' . $uid . ' .kc-sc-info { position: absolute; bottom: 20px; right: 20px; left: 20px; z-index: 4; display: flex; flex-direction: column; align-items: flex-end; }
		.' . $uid . ' .kc-sc-artist { color: #fff; font-size: 32px; font-weight: 900; line-height: 1; margin: 0; text-shadow: 0 4px 10px rgba(0,0,0,0.5); text-align: right; word-wrap: break-word; }
		
		/* Nav Arrows (Exact Square Style) */
		.' . $uid . ' .kc-sc-nav { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; padding: 0 8px; }
		.' . $uid . ' .kc-sc-nav-btn { width: 48px; height: 48px; border: 1px solid #334155; background: transparent; color: #94a3b8; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; }
		.' . $uid . ' .kc-sc-nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
		.' . $uid . ' .kc-sc-nav-btn.kc-next { border-color: #e11d48; color: #e11d48; }
		.' . $uid . ' .kc-sc-nav-btn.kc-next:hover { background: rgba(225, 29, 72, 0.1); }
		</style>';

		// Scribble Crown SVG
		$crown_svg = '<svg class="kc-sc-crown" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M15,60 C25,45 35,30 40,35 C42,45 45,60 50,70 C55,45 60,20 65,15 C68,35 70,55 75,65 C80,45 85,30 90,35" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		
		$header_crown_svg = '<svg class="kc-sc-header-crown" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M15,60 C25,45 35,30 40,35 C42,45 45,60 50,70 C55,45 60,20 65,15 C68,35 70,55 75,65 C80,45 85,30 90,35" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		echo '<div class="' . $uid . ' kc-widget-wrap">';
		
		echo '<div class="kc-sc-header">';
		echo '<div class="kc-sc-header-right">';
		echo $header_crown_svg;
		echo '<h2 class="kc-sc-header-title">' . esc_html($title) . '</h2>';
		echo '</div>';
		echo '<a href="' . esc_url($more_url) . '" class="kc-sc-more"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> ' . esc_html($settings['more_text']) . '</a>';
		echo '</div>';
		
		echo '<div class="kc-sc-track-wrap">';
		echo '<div class="kc-sc-track" id="' . $uid . '-track">';
		
		foreach ($entries as $index => $e) {
			$bg_idx = $index % 4;
			$bg_url = $bgs[$bg_idx];
			$bg_style = $bg_url ? 'background-image: url(\'' . esc_url($bg_url) . '\');' : '';
			
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
			$img = $e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$num = \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position);
			
			$display_text = ($def->entity_type === 'track' && !empty($resolved['subtitle'])) ? $resolved['subtitle'] : $resolved['title'];
			
			// Try to break artist name onto two lines if it contains space (to match "عمرو دياب" stacked look)
			$words = explode(' ', $display_text);
			if (count($words) == 2) {
				$display_text = $words[0] . '<br>' . $words[1];
			}
			
			echo '<div class="kc-sc-card" style="' . $bg_style . '">';
			echo '<div class="kc-sc-num">' . $num . '</div>';
			echo $crown_svg;
			
			echo '<div class="kc-sc-img-wrap">';
			echo '<img src="' . esc_url($img) . '" class="kc-sc-img">';
			echo '</div>';
			
			echo '<div class="kc-sc-overlay"></div>';
			echo '<div class="kc-sc-info">';
			echo '<h3 class="kc-sc-artist">' . wp_kses_post($display_text) . '</h3>';
			echo '</div>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>'; // End Track Wrap
		
		// Nav Arrows exactly like image
		echo '<div class="kc-sc-nav">';
		echo '<button class="kc-sc-nav-btn kc-next" onclick="document.getElementById(\'' . $uid . '-track\').scrollBy({left: -320, behavior: \'smooth\'})"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></button>';
		echo '<button class="kc-sc-nav-btn kc-prev" onclick="document.getElementById(\'' . $uid . '-track\').scrollBy({left: 320, behavior: \'smooth\'})"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg></button>';
		echo '</div>';
		
		echo '</div>';
	}
}
