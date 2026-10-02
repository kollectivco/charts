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
			'default' => '🌟 جديد',
			'condition' => [ 'show_badges' => 'yes' ],
		] );

		$this->add_control( 'badge_hot_text', [
			'label' => __( 'Hot Trend Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => '🔥 تريند',
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

	/**
	 * Adds Foxiz-style Featured Image controls (Ratio, Border Radius, Hover Effect, Align, Lazy Load).
	 * The hover effects are implemented using CSS class injection via a custom_css control.
	 *
	 * @param string $wrap_selector CSS selector for the image wrapper element
	 * @param string $img_selector  CSS selector for the image element itself
	 */
	public function add_advanced_image_controls( $wrap_selector = '{{WRAPPER}} .kc-img-wrap', $img_selector = '{{WRAPPER}} .kc-img' ) {
		$this->start_controls_section( 'style_advanced_image', [
			'label' => __( 'Featured Image', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		// 1. Image Size (display-only note — actual URL sizing handled in render)
		$this->add_control( 'img_size', [
			'label'       => __( 'Featured Image Size', 'charts' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => [
				'thumbnail' => 'Thumbnail (150px)',
				'medium'    => 'Medium (300px)',
				'large'     => 'Large (1024px)',
				'full'      => 'Full (Original)',
			],
			'default'     => 'full',
			'description' => __( 'Select a featured image size to optimize performance based on your column count.', 'charts' ),
		] );

		// 2. Aspect Ratio
		$this->add_responsive_control( 'img_ratio', [
			'label'       => __( 'Custom Aspect Ratio (%)', 'charts' ),
			'type'        => Controls_Manager::NUMBER,
			'min'         => 10,
			'max'         => 200,
			'description' => __( 'Set image height as percent of width (height×100÷width). e.g. 50 = landscape 2:1, 100 = square, 150 = portrait.', 'charts' ),
			'selectors'   => [
				$wrap_selector => 'aspect-ratio: unset; padding-bottom: {{VALUE}}%; position: relative; overflow: hidden;',
				$img_selector  => 'position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;',
			],
		] );

		// 3. Border Radius
		$this->add_responsive_control( 'img_advanced_border_radius', [
			'label'      => __( 'Border Radius', 'charts' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'description'=> __( 'Set a border radius for the image wrapper.', 'charts' ),
			'selectors'  => [
				$wrap_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
				$img_selector  => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		// 4. Hover Effect - uses CSS transition + transform directly on img_selector
		$this->add_control( 'img_hover_effect', [
			'label'       => __( 'Hover Effect', 'charts' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => [
				''          => '- Disable -',
				'zoom-in'   => 'Zoom In',
				'zoom-out'  => 'Zoom Out',
				'blur'      => 'Blur',
				'grayscale' => 'Grayscale',
			],
			'default'     => '',
			'description' => __( 'Apply a hover effect on the featured image when the user hovers the card.', 'charts' ),
			'selectors'   => [
				// Always add transition to img
				$img_selector => 'transition: transform 0.4s ease, filter 0.4s ease;',
			],
		] );

		// Zoom In
		$this->add_control( 'img_hover_zoom_in_css', [
			'label'     => __( 'Zoom In Intensity', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'default'   => [ 'size' => 1.1 ],
			'range'     => [ 'px' => [ 'min' => 1.01, 'max' => 1.5, 'step' => 0.01 ] ],
			'selectors' => [
				$wrap_selector . ':hover ' . ltrim( str_replace( '{{WRAPPER}}', '', $img_selector ) ) => 'transform: scale({{SIZE}});',
			],
			'condition' => [ 'img_hover_effect' => 'zoom-in' ],
		] );

		// Zoom Out
		$this->add_control( 'img_hover_zoom_out_css', [
			'label'     => __( 'Zoom Out Scale', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'default'   => [ 'size' => 0.92 ],
			'range'     => [ 'px' => [ 'min' => 0.5, 'max' => 0.99, 'step' => 0.01 ] ],
			'selectors' => [
				$wrap_selector . ':hover ' . ltrim( str_replace( '{{WRAPPER}}', '', $img_selector ) ) => 'transform: scale({{SIZE}});',
			],
			'condition' => [ 'img_hover_effect' => 'zoom-out' ],
		] );

		// Blur
		$this->add_control( 'img_hover_blur_css', [
			'label'     => __( 'Blur Amount (px)', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'default'   => [ 'size' => 4 ],
			'range'     => [ 'px' => [ 'min' => 1, 'max' => 20 ] ],
			'selectors' => [
				$wrap_selector . ':hover ' . ltrim( str_replace( '{{WRAPPER}}', '', $img_selector ) ) => 'filter: blur({{SIZE}}px);',
			],
			'condition' => [ 'img_hover_effect' => 'blur' ],
		] );

		// Grayscale (no extra slider needed — always 100%)
		$this->add_control( 'img_hover_gray_note', [
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => '<small style="color:#aaa;">Grayscale: image turns black & white on hover.</small>',
			'condition'       => [ 'img_hover_effect' => 'grayscale' ],
			'content_classes' => '',
		] );
		$this->add_control( 'img_hover_gray_css', [
			'label'     => __( 'Grayscale %', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'default'   => [ 'size' => 100 ],
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'selectors' => [
				$wrap_selector . ':hover ' . ltrim( str_replace( '{{WRAPPER}}', '', $img_selector ) ) => 'filter: grayscale({{SIZE}}%);',
			],
			'condition' => [ 'img_hover_effect' => 'grayscale' ],
		] );

		// 5. Align
		$this->add_responsive_control( 'img_align', [
			'label'     => __( 'Align', 'charts' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => [
				'flex-start' => [ 'title' => __( 'Left', 'charts' ),   'icon' => 'eicon-text-align-left' ],
				'center'     => [ 'title' => __( 'Center', 'charts' ), 'icon' => 'eicon-text-align-center' ],
				'flex-end'   => [ 'title' => __( 'Right', 'charts' ),  'icon' => 'eicon-text-align-right' ],
			],
			'selectors' => [
				'{{WRAPPER}}' => 'display: flex; justify-content: {{VALUE}};',
			],
			'description' => __( 'Align the featured images inside this widget.', 'charts' ),
		] );

		// 6. Lazy Load (controls the loading attribute in render — also adds a note)
		$this->add_control( 'img_lazy_load', [
			'label'       => __( 'Lazy Load', 'charts' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => [
				'lazy'  => '- Default (Lazy) -',
				'eager' => 'Eager (Disable — above fold)',
			],
			'default'     => 'lazy',
			'description' => __( 'Set "Eager" if this widget is visible above the fold to prevent layout shift (LCP).', 'charts' ),
		] );

		$this->end_controls_section();
	}
}
