<?php
/**
 * Elementor Widget: Hero Slider Configuration
 * Implements 3 distinct visual styles: Floating, Gallery, Layered.
 */

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class HeroSlider extends Widget_Base {

	public function get_name() { return 'hero_slider'; }
	public function get_title() { return __( 'Hero Slider System', 'charts' ); }
	public function get_icon() { return 'eicon-post-slider'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_global', [ 'label' => __( 'Global Connectivity', 'charts' ) ] );

		$this->add_control( 'use_global_settings', [
			'label' => __( 'Inherit Global Settings', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'description' => __( 'Use the settings defined in the Kontentainment Charts -> Settings -> Homepage panel.', 'charts' ),
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_content', [ 
			'label' => __( 'Slider Configuration', 'charts' ),
			'condition' => [ 'use_global_settings' => '' ]
		] );

		// Style Selector
		$this->add_control( 'slider_style', [
			'label' => __( 'Carousel Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'coverflow' => __( 'Coverflow 3D', 'charts' ),
				'stacked' => __( 'Stacked Cards', 'charts' ),
				'minimal' => __( 'Minimal Motion', 'charts' ),
			],
			'default' => 'coverflow',
		] );

		// Data Source
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_ids', [
			'label' => __( 'Featured Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'label_block' => true,
			'multiple' => true,
			'options' => $options,
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_carousel_settings', [ 
			'label' => __( 'Motion Settings', 'charts' ),
			'condition' => [ 'use_global_settings' => '' ]
		] );

		$this->add_control( 'slider_count', [
			'label' => __( 'Max Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->add_control( 'slider_speed', [
			'label' => __( 'Animation Speed (ms)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 600,
		] );

		$this->add_control( 'slider_easing', [
			'label' => __( 'Easing', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'cubic-bezier(0.25, 1, 0.5, 1)',
		] );

		$this->add_control( 'slider_rotation', [
			'label' => __( 'Rotation Angle (deg)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 45,
		] );

		$this->add_control( 'slider_depth', [
			'label' => __( 'Depth (translateZ)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 150,
		] );

		$this->add_control( 'slider_spacing', [
			'label' => __( 'Card Spacing (px)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 50,
		] );

		$this->add_control( 'slider_autoplay', [
			'label' => __( 'Autoplay', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'slider_loop', [
			'label' => __( 'Loop', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'slider_center', [
			'label' => __( 'Center Mode', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'slider_opacity', [
			'label' => __( 'Side Card Opacity', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.6,
		] );

		$this->add_control( 'slider_scale', [
			'label' => __( 'Side Card Scale', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.8,
		] );

		$this->add_control( 'slider_shadow', [
			'label' => __( 'Shadow Intensity', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.3,
		] );

		$this->add_control( 'slider_glow', [
			'label' => __( 'Active Card Glow', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->end_controls_section();

		\Charts\Integrations\Elementor\ControlHelper::add_style_controls( $this );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( $settings['use_global_settings'] === 'yes' ) {
			$global_settings = \Charts\Core\HomepageSlider::get_global_settings();
			$settings = array_merge( $settings, $global_settings );
		}

		// Normalize boolean values from Elementor switcher if not using global
		if ( $settings['use_global_settings'] !== 'yes' ) {
			foreach ( ['slider_autoplay', 'slider_loop', 'slider_center', 'slider_glow'] as $k ) {
				$settings[$k] = ($settings[$k] === 'yes') ? 1 : 0;
			}
		}

		$chart_ids = [];
		// Note: the widget does not save 'chart_ids' globally as that varies. If using global, it might just use all, or whatever user checked. 
		// Elementor could still override chart_ids if you wanted, but for now we fallback to global logic.
		if ( !empty($settings['chart_ids']) && $settings['use_global_settings'] !== 'yes' ) {
			$chart_ids = $settings['chart_ids'];
		}

		$count = intval($settings['slider_count'] ?? 5);

		$slides = \Charts\Core\HomepageSlider::get_slides_data($chart_ids, $count);

		\Charts\Core\HomepageSlider::render($slides, $settings, 'widget');
	}
}
