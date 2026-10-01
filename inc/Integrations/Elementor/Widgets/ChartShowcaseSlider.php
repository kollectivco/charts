<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

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
			'default' => [
				'url' => '',
			],
		] );
		
		$this->add_control( 'more_text', [
			'label' => __( 'More Button Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'المزيد',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style_cards', [ 'label' => __( 'Cards Styling', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		
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
		
		$this->add_control( 'color_1', [ 'label' => 'Card Color 1 (Red)', 'type' => Controls_Manager::COLOR, 'default' => '#dc2626' ] );
		$this->add_control( 'color_2', [ 'label' => 'Card Color 2 (Orange)', 'type' => Controls_Manager::COLOR, 'default' => '#f97316' ] );
		$this->add_control( 'color_3', [ 'label' => 'Card Color 3 (Purple)', 'type' => Controls_Manager::COLOR, 'default' => '#7c3aed' ] );
		$this->add_control( 'color_4', [ 'label' => 'Card Color 4 (Teal)', 'type' => Controls_Manager::COLOR, 'default' => '#0d9488' ] );
		
		$this->end_controls_section();
		
		$this->start_controls_section( 'style_typography', [ 'label' => __( 'Typography', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'label' => 'Header Title', 'selector' => '{{WRAPPER}} .kc-sc-header h2' ] );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'card_title_typo', 'label' => 'Artist Name', 'selector' => '{{WRAPPER}} .kc-sc-artist' ] );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [ 'name' => 'num_typo', 'label' => 'Big Number', 'selector' => '{{WRAPPER}} .kc-sc-num' ] );
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
		
		$c1 = $settings['color_1']; $c2 = $settings['color_2']; $c3 = $settings['color_3']; $c4 = $settings['color_4'];

		echo '<style>
		.' . $uid . ' { width: 100%; overflow: hidden; position: relative; font-family: "Inter", -apple-system, sans-serif; }
		.' . $uid . ' .kc-sc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 0 16px; }
		.' . $uid . ' .kc-sc-header h2 { margin: 0; color: #fff; font-size: 32px; font-weight: 900; display: flex; align-items: center; gap: 8px; flex-direction: row-reverse; }
		.' . $uid . ' .kc-sc-header .kc-more { color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: color 0.3s; }
		.' . $uid . ' .kc-sc-header .kc-more:hover { color: #fff; }
		
		.' . $uid . ' .kc-sc-track-wrap { position: relative; width: 100%; }
		.' . $uid . ' .kc-sc-track { display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; padding: 16px; -ms-overflow-style: none; }
		.' . $uid . ' .kc-sc-track::-webkit-scrollbar { display: none; }
		
		.' . $uid . ' .kc-sc-card { 
			position: relative; 
			border-radius: 16px; 
			overflow: hidden; 
			scroll-snap-align: start; 
			cursor: pointer;
			background: #111;
			transition: transform 0.4s ease;
			box-shadow: 0 10px 30px rgba(0,0,0,0.5);
		}
		.' . $uid . ' .kc-sc-card:hover { transform: translateY(-10px); }
		
		.' . $uid . ' .kc-sc-card-0 { background: linear-gradient(135deg, #000 0%, ' . $c1 . ' 100%); }
		.' . $uid . ' .kc-sc-card-1 { background: linear-gradient(135deg, #000 0%, ' . $c2 . ' 100%); }
		.' . $uid . ' .kc-sc-card-2 { background: linear-gradient(135deg, #000 0%, ' . $c3 . ' 100%); }
		.' . $uid . ' .kc-sc-card-3 { background: linear-gradient(135deg, #000 0%, ' . $c4 . ' 100%); }
		
		/* Abstract background pattern overlay */
		.' . $uid . ' .kc-sc-bg-pattern { position: absolute; inset: 0; opacity: 0.3; background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px; mix-blend-mode: overlay; pointer-events: none; }
		
		.' . $uid . ' .kc-sc-num { position: absolute; top: 16px; left: 16px; font-size: 80px; font-weight: 900; color: #fff; line-height: 0.8; text-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 3; }
		.' . $uid . ' .kc-sc-crown { position: absolute; top: 20px; right: 20px; width: 32px; height: 32px; opacity: 0.8; z-index: 3; }
		
		.' . $uid . ' .kc-sc-img-wrap { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; justify-content: flex-end; }
		.' . $uid . ' .kc-sc-img { width: 100%; height: 90%; object-fit: contain; object-position: bottom; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5)); transition: transform 0.5s ease; }
		.' . $uid . ' .kc-sc-card:hover .kc-sc-img { transform: scale(1.05); }
		
		.' . $uid . ' .kc-sc-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.2) 40%, transparent 100%); z-index: 3; }
		
		.' . $uid . ' .kc-sc-info { position: absolute; bottom: 24px; left: 24px; right: 24px; z-index: 4; }
		.' . $uid . ' .kc-sc-artist { color: #fff; font-size: 28px; font-weight: 900; line-height: 1.1; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-shadow: 0 4px 12px rgba(0,0,0,0.8); }
		
		/* Nav Arrows */
		.' . $uid . ' .kc-sc-nav { display: flex; gap: 8px; justify-content: flex-end; padding-right: 16px; margin-top: 16px; }
		.' . $uid . ' .kc-sc-nav-btn { width: 44px; height: 44px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; }
		.' . $uid . ' .kc-sc-nav-btn:hover { border-color: #e11d48; color: #e11d48; }
		</style>';

		echo '<div class="' . $uid . ' kc-widget-wrap">';
		
		echo '<div class="kc-sc-header">';
		echo '<a href="' . esc_url($more_url) . '" class="kc-more"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> ' . esc_html($settings['more_text']) . '</a>';
		echo '<h2>' . esc_html($title) . ' <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg></h2>';
		echo '</div>';
		
		echo '<div class="kc-sc-track-wrap">';
		echo '<div class="kc-sc-track" id="' . $uid . '-track">';
		
		foreach ($entries as $index => $e) {
			$color_idx = $index % 4;
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
			$img = $e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$num = \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position);
			
			// If it\'s a track, title is track, subtitle is artist. If it\'s artist, title is artist.
			$display_text = ($def->entity_type === 'track' && !empty($resolved['subtitle'])) ? $resolved['subtitle'] : $resolved['title'];
			
			echo '<div class="kc-sc-card kc-sc-card-' . $color_idx . '">';
			echo '<div class="kc-sc-bg-pattern"></div>';
			echo '<div class="kc-sc-num">' . $num . '</div>';
			echo '<svg class="kc-sc-crown" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>';
			
			echo '<div class="kc-sc-img-wrap">';
			echo '<img src="' . esc_url($img) . '" class="kc-sc-img">';
			echo '</div>';
			
			echo '<div class="kc-sc-overlay"></div>';
			echo '<div class="kc-sc-info">';
			echo '<h3 class="kc-sc-artist">' . esc_html($display_text) . '</h3>';
			echo '</div>';
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>'; // End Track Wrap
		
				echo '<div class="kc-sc-nav">';
		echo '<button class="kc-sc-nav-btn kc-prev" onclick="document.getElementById(\'' . $uid . '-track\').scrollBy({left: (document.dir === \'rtl\' || document.body.classList.contains(\'rtl\') ? 320 : -320), behavior: \'smooth\'})"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>';
		echo '<button class="kc-sc-nav-btn kc-next" onclick="document.getElementById(\'' . $uid . '-track\').scrollBy({left: (document.dir === \'rtl\' || document.body.classList.contains(\'rtl\') ? -320 : 320), behavior: \'smooth\'})"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>';
		echo '</div>';
	}
}
