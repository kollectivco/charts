<?php
namespace Charts\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Css_Filter;

trait PremiumWidgetTrait {

	protected function add_premium_layout_controls($uid = '') {
		$this->start_controls_section( 'premium_layout', [
			'label' => __( 'Advanced Layout', 'charts' ),
			'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );

		$this->add_responsive_control( 'grid_columns', [
			'label' => __( 'Columns', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 1, 'max' => 6,
			'default' => 3, 'tablet_default' => 2, 'mobile_default' => 1,
			'selectors' => [ '{{WRAPPER}} .kc-grid-root' => 'grid-template-columns: repeat({{VALUE}}, 1fr);' ],
		] );

		$this->add_responsive_control( 'grid_gap', [
			'label' => __( 'Spacing (Gap)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-grid-root' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'hover_animation', [
			'label' => __( 'Card Hover Effect', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'none' => 'None',
				'lift' => 'Lift Up (Float)',
				'zoom' => 'Image Zoom',
				'glow' => 'Glow Shadow',
				'tilt' => '3D Tilt (Requires JS)'
			],
			'default' => 'zoom',
		] );

		$this->end_controls_section();
	}

	protected function add_premium_badge_controls() {
		$this->start_controls_section( 'premium_badges', [
			'label' => __( 'Smart Badges', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'show_badges', [
			'label' => __( 'Enable Smart Badges', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default' => 'yes',
		] );

		$this->add_control( 'badge_new_text', [
			'label' => __( 'New Entry Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => '🌟 NEW',
			'condition' => [ 'show_badges' => 'yes' ],
		] );

		$this->add_control( 'badge_hot_text', [
			'label' => __( 'Hot Trend Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => '🔥 HOT',
			'condition' => [ 'show_badges' => 'yes' ],
		] );

		$this->end_controls_section();
	}

	protected function add_granular_style_controls($elements = ['card', 'image', 'title', 'meta', 'counter']) {
		
		// 1. CARD BOX
		if (in_array('card', $elements)) {
			$this->start_controls_section( 'style_card_box', [
				'label' => __( 'Card Box', 'charts' ),
				'tab' => Controls_Manager::TAB_STYLE,
			] );

			$this->start_controls_tabs( 'card_style_tabs' );
			$this->start_controls_tab( 'card_normal', [ 'label' => __( 'Normal', 'charts' ) ] );
			$this->add_group_control( Group_Control_Background::get_type(), [
				'name' => 'card_bg',
				'selector' => '{{WRAPPER}} .kc-elm-card',
			] );
			$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
				'name' => 'card_shadow',
				'selector' => '{{WRAPPER}} .kc-elm-card',
			] );
			$this->end_controls_tab();

			$this->start_controls_tab( 'card_hover', [ 'label' => __( 'Hover', 'charts' ) ] );
			$this->add_group_control( Group_Control_Background::get_type(), [
				'name' => 'card_bg_hover',
				'selector' => '{{WRAPPER}} .kc-elm-card:hover',
			] );
			$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
				'name' => 'card_shadow_hover',
				'selector' => '{{WRAPPER}} .kc-elm-card:hover',
			] );
			$this->end_controls_tab();
			$this->end_controls_tabs();

			$this->add_responsive_control( 'card_radius', [
				'label' => __( 'Border Radius', 'charts' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
				'separator' => 'before'
			] );
			
			$this->add_responsive_control( 'card_padding', [
				'label' => __( 'Padding', 'charts' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );

			$this->end_controls_section();
		}

		// 2. FEATURED IMAGE
		if (in_array('image', $elements)) {
			$this->start_controls_section( 'style_featured_image', [
				'label' => __( 'Featured Image', 'charts' ),
				'tab' => Controls_Manager::TAB_STYLE,
			] );

			$this->start_controls_tabs( 'img_style_tabs' );
			$this->start_controls_tab( 'img_normal', [ 'label' => __( 'Normal', 'charts' ) ] );
			$this->add_group_control( Group_Control_Css_Filter::get_type(), [
				'name' => 'img_filters',
				'selector' => '{{WRAPPER}} .kc-elm-img',
			] );
			$this->end_controls_tab();

			$this->start_controls_tab( 'img_hover', [ 'label' => __( 'Hover', 'charts' ) ] );
			$this->add_group_control( Group_Control_Css_Filter::get_type(), [
				'name' => 'img_filters_hover',
				'selector' => '{{WRAPPER}} .kc-elm-card:hover .kc-elm-img',
			] );
			$this->end_controls_tab();
			$this->end_controls_tabs();

			$this->add_control( 'img_overlay', [
				'label' => __( 'Overlay Gradient', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'background: linear-gradient(to top, {{VALUE}} 0%, rgba(0,0,0,0) 100%);' ],
				'separator' => 'before'
			] );

			$this->end_controls_section();
		}

		// 3. TRACK TITLE
		if (in_array('title', $elements)) {
			$this->start_controls_section( 'style_track_title', [
				'label' => __( 'Track Title', 'charts' ),
				'tab' => Controls_Manager::TAB_STYLE,
			] );

			$this->start_controls_tabs( 'title_tabs' );
			$this->start_controls_tab( 'title_normal', [ 'label' => __( 'Normal', 'charts' ) ] );
			$this->add_control( 'title_color', [
				'label' => __( 'Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-title' => 'color: {{VALUE}};' ],
			] );
			$this->end_controls_tab();
			$this->start_controls_tab( 'title_hover', [ 'label' => __( 'Hover', 'charts' ) ] );
			$this->add_control( 'title_color_hover', [
				'label' => __( 'Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-card:hover .kc-elm-title' => 'color: {{VALUE}};' ],
			] );
			$this->end_controls_tab();
			$this->end_controls_tabs();

			$this->add_group_control( Group_Control_Typography::get_type(), [
				'name' => 'title_typo',
				'selector' => '{{WRAPPER}} .kc-elm-title',
			] );

			$this->add_responsive_control( 'title_margin', [
				'label' => __( 'Margin', 'charts' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors' => [ '{{WRAPPER}} .kc-elm-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );

			$this->end_controls_section();
		}

		// 4. ARTIST & META
		if (in_array('meta', $elements)) {
			$this->start_controls_section( 'style_artist_meta', [
				'label' => __( 'Artist & Meta Data', 'charts' ),
				'tab' => Controls_Manager::TAB_STYLE,
			] );

			$this->add_control( 'artist_color', [
				'label' => __( 'Artist Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-artist' => 'color: {{VALUE}};' ],
			] );
			$this->add_group_control( Group_Control_Typography::get_type(), [
				'name' => 'artist_typo',
				'label' => __( 'Artist Typography', 'charts' ),
				'selector' => '{{WRAPPER}} .kc-elm-artist',
			] );
			
			$this->add_control( 'meta_color', [
				'label' => __( 'Meta Text Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-meta' => 'color: {{VALUE}};' ],
				'separator' => 'before'
			] );
			$this->add_group_control( Group_Control_Typography::get_type(), [
				'name' => 'meta_typo',
				'label' => __( 'Meta Typography', 'charts' ),
				'selector' => '{{WRAPPER}} .kc-elm-meta',
			] );

			$this->end_controls_section();
		}

		// 5. INDEX COUNTER
		if (in_array('counter', $elements)) {
			$this->start_controls_section( 'style_index_counter', [
				'label' => __( 'Index Counter', 'charts' ),
				'tab' => Controls_Manager::TAB_STYLE,
			] );

			$this->add_control( 'counter_bg', [
				'label' => __( 'Background Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-counter' => 'background: {{VALUE}};' ],
			] );
			$this->add_control( 'counter_color', [
				'label' => __( 'Text Color', 'charts' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .kc-elm-counter' => 'color: {{VALUE}};' ],
			] );
			$this->add_group_control( Group_Control_Typography::get_type(), [
				'name' => 'counter_typo',
				'selector' => '{{WRAPPER}} .kc-elm-counter',
			] );
			$this->add_responsive_control( 'counter_size', [
				'label' => __( 'Box Size', 'charts' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 20, 'max' => 100 ] ],
				'selectors' => [ '{{WRAPPER}} .kc-elm-counter' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
			] );
			$this->add_responsive_control( 'counter_radius', [
				'label' => __( 'Border Radius', 'charts' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [ '{{WRAPPER}} .kc-elm-counter' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );

			$this->end_controls_section();
		}
	}
}
