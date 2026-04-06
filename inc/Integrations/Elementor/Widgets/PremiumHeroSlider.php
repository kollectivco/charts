<?php
/**
 * Elementor Widget: Premium Hero Slider
 * Implementation of the unified and only homepage slider.
 */

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class PremiumHeroSlider extends Widget_Base {

	public function get_name() { return 'premium_hero_slider'; }
	public function get_title() { return __( 'KCharts Premium Hero Slider', 'charts' ); }
	public function get_icon() { return 'eicon-slideshow'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// Content Tab
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Slides', 'charts' ) ] );

		$this->add_control( 'source_mode', [
			'label' => __( 'Source Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'auto' => __( 'Auto (Latest Charts)', 'charts' ),
				'manual' => __( 'Manual Selection', 'charts' ),
			],
			'default' => 'auto',
		] );

		$this->add_control( 'slide_count', [
			'label' => __( 'Number of Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 1, 'max' => 10, 'step' => 1,
			'default' => 5,
			'condition' => [ 'source_mode' => 'auto' ],
		] );

		$repeater = new Repeater();

		$repeater->add_control( 'title', [
			'label' => __( 'Title', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'GLOBAL TOP 100', 'charts' ),
		] );

		$repeater->add_control( 'badge', [
			'label' => __( 'Badge Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( '#1 TRENDING', 'charts' ),
		] );

		$repeater->add_control( 'desc', [
			'label' => __( 'Description', 'charts' ),
			'type' => Controls_Manager::TEXTAREA,
			'default' => __( 'Experience the definitive ranking of music popularity across the world.', 'charts' ),
		] );

		$repeater->add_control( 'image', [
			'label' => __( 'Background Image', 'charts' ),
			'type' => Controls_Manager::MEDIA,
		] );

		$repeater->add_control( 'btn1_text', [
			'label' => __( 'Primary Button Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'View Chart', 'charts' ),
		] );

		$repeater->add_control( 'btn1_link', [
			'label' => __( 'Primary Button Link', 'charts' ),
			'type' => Controls_Manager::URL,
		] );

		$repeater->add_control( 'btn2_text', [
			'label' => __( 'Secondary Button Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => __( 'Add to Library', 'charts' ),
		] );

		$repeater->add_control( 'btn2_link', [
			'label' => __( 'Secondary Button Link', 'charts' ),
			'type' => Controls_Manager::URL,
		] );

		$this->add_control( 'manual_slides', [
			'label' => __( 'Manual Slides', 'charts' ),
			'type' => Controls_Manager::REPEATER,
			'fields' => $repeater->get_controls(),
			'condition' => [ 'source_mode' => 'manual' ],
			'title_field' => '{{{ title }}}',
		] );

		$this->end_controls_section();

		// Layout Tab
		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'charts' ) ] );

		$this->add_responsive_control( 'hero_height', [
			'label' => __( 'Hero Height (vh)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'vh' ],
			'range' => [ 'vh' => [ 'min' => 20, 'max' => 100 ] ],
			'default' => [ 'unit' => 'vh', 'size' => 70 ],
			'selectors' => [ '{{WRAPPER}} .kc-premium-slider' => 'height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'border_radius', [
			'label' => __( 'Border Radius (px)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 28 ],
			'selectors' => [ '{{WRAPPER}} .kc-premium-slider' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'content_max_width', [
			'label' => __( 'Content Max Width (px)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1200 ] ],
			'default' => [ 'size' => 800 ],
			'selectors' => [ '{{WRAPPER}} .kc-ps-content' => 'max-width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'overlay_opacity', [
			'label' => __( 'Overlay Opacity (%)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 75 ],
			'selectors' => [ '{{WRAPPER}} .kc-ps-overlay' => '--ps-overlay-op: {{SIZE}}%;' ],
		] );

		$this->add_control( 'btn_spacing', [
			'label' => __( 'Buttons Spacing (px)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'default' => [ 'size' => 16 ],
			'selectors' => [ '{{WRAPPER}} .kc-ps-actions' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'hide_secondary_mobile', [
			'label' => __( 'Hide Secondary CTA on Mobile', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style', 'charts' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

		$this->add_control( 'overlay_color', [
			'label' => __( 'Overlay Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#000000',
			'selectors' => [ '{{WRAPPER}} .kc-ps-overlay' => '--ps-overlay-color: {{VALUE}};' ],
		] );

		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .kc-ps-title' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'desc_color', [
			'label' => __( 'Description Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => 'rgba(255,255,255,0.7)',
			'selectors' => [ '{{WRAPPER}} .kc-ps-desc' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'badge_bg', [
			'label' => __( 'Badge Background', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#fe025b',
			'selectors' => [ '{{WRAPPER}} .kc-ps-badge' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'primary_btn_color', [
			'label' => __( 'Primary Button Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#fe025b',
			'selectors' => [ '{{WRAPPER}} .kc-ps-btn-p' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'secondary_btn_border', [
			'label' => __( 'Secondary Button Border/Text', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .kc-ps-btn-s' => 'color: {{VALUE}}; border-color: {{VALUE}};' ],
		] );

		$this->add_control( 'pagination_active', [
			'label' => __( 'Pagination Active Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => '#fe025b',
			'selectors' => [ '{{WRAPPER}} .kc-ps-dot.is-active' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'pagination_inactive', [
			'label' => __( 'Pagination Inactive Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => 'rgba(255,255,255,0.3)',
			'selectors' => [ '{{WRAPPER}} .kc-ps-dot' => 'background: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// Behavior Tab
		$this->start_controls_section( 'section_behavior', [ 'label' => __( 'Behavior', 'charts' ) ] );

		$this->add_control( 'autoplay', [
			'label' => __( 'Autoplay', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'autoplay_delay', [
			'label' => __( 'Autoplay Delay (ms)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5000,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'transition_speed', [
			'label' => __( 'Transition Speed (ms)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 800,
		] );

		$this->add_control( 'loop', [
			'label' => __( 'Loop', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'show_arrows', [
			'label' => __( 'Show Arrows', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'show_dots', [
			'label' => __( 'Show Dots', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides = [];

		if ( $settings['source_mode'] === 'auto' ) {
			// Auto fetch logic using existing HomepageSlider helper
			$count = intval($settings['slide_count'] ?? 5);
			$slides_data = \Charts\Core\HomepageSlider::get_slides_data([], $count);
			foreach ( $slides_data as $s ) {
				$slides[] = [
					'title' => $s['title'],
					'badge' => __( '#1 TRENDING', 'charts' ),
					'desc' => $s['subtitle'] ?: __( 'Top performing tracks and videos on the global stage.', 'charts' ),
					'image_url' => $s['image'],
					'btn1_text' => __( 'View Chart', 'charts' ),
					'btn1_link' => $s['url'],
					'btn2_text' => __( 'Add to Library', 'charts' ),
					'btn2_link' => '#',
				];
			}
		} else {
			// Manual slides
			foreach ( $settings['manual_slides'] as $s ) {
				$slides[] = [
					'title' => $s['title'],
					'badge' => $s['badge'],
					'desc' => $s['desc'],
					'image_url' => $s['image']['url'] ?? CHARTS_URL . 'public/assets/img/placeholder.png',
					'btn1_text' => $s['btn1_text'],
					'btn1_link' => $s['btn1_link']['url'] ?? '#',
					'btn2_text' => $s['btn2_text'],
					'btn2_link' => $s['btn2_link']['url'] ?? '#',
				];
			}
		}

		if ( empty($slides) ) return;

		$config = [
			'autoplay' => $settings['autoplay'] === 'yes',
			'delay' => intval($settings['autoplay_delay']),
			'speed' => intval($settings['transition_speed']),
			'loop' => $settings['loop'] === 'yes',
			'show_arrows' => $settings['show_arrows'] === 'yes',
			'show_dots' => $settings['show_dots'] === 'yes',
		];

		include CHARTS_PATH . 'public/templates/parts/premium-slider.php';
	}
}
