<?php
$file = 'inc/Integrations/Elementor/Widgets/SidebarTop1.php';
$content = file_get_contents($file);

$new_controls = <<<PHP
		\$this->start_controls_section( 'layout_sizing', [
			'label' => __( 'Card Sizing & Layout', 'charts' ),
			'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		
		\$this->add_responsive_control( 'card_height', [
			'label' => __( 'Card Height', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range' => [ 'px' => [ 'min' => 80, 'max' => 500, 'step' => 1 ] ],
			'default' => [ 'size' => 130, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'height: {{SIZE}}{{UNIT}};' ],
		]);
		
		\$this->add_responsive_control( 'card_gap', [
			'label' => __( 'Spacing Between Cards', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-grid-root' => 'gap: {{SIZE}}{{UNIT}};' ],
		]);
		
		\$this->add_responsive_control( 'card_padding', [
			'label' => __( 'Content Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		
		\$this->add_control( 'overlay_gradient', [
			'label' => __( 'Overlay Gradient', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'background: linear-gradient(to top, {{VALUE}} 0%, transparent 100%);' ],
		]);
		
		\$this->end_controls_section();

		\$this->start_controls_section( 'style_chart_name', [
			'label' => __( 'Chart Name Tag', 'charts' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );
		\$this->add_control( 'cname_color', [
			'label' => __( 'Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-st1-ch-name, {{WRAPPER}} .kc-front-title' => 'color: {{VALUE}};' ],
		]);
		\$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'cname_typo', 'selector' => '{{WRAPPER}} .kc-st1-ch-name, {{WRAPPER}} .kc-front-title',
		]);
		\$this->end_controls_section();

		\$this->start_controls_section( 'style_artist', [
			'label' => __( 'Artist Name', 'charts' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );
		\$this->add_control( 'artist_color', [
			'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-artist' => 'color: {{VALUE}};' ],
		]);
		\$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'artist_typo', 'selector' => '{{WRAPPER}} .kc-elm-artist',
		]);
		\$this->end_controls_section();

		\$this->start_controls_section( 'style_big_number', [
			'label' => __( 'Background Number (1)', 'charts' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );
		\$this->add_control( 'bignum_color', [
			'label' => __( 'Number Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-st1-num' => 'color: {{VALUE}};' ],
		]);
		\$this->add_control( 'bignum_hover', [
			'label' => __( 'Number Hover Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card:hover .kc-st1-num' => 'color: {{VALUE}};' ],
		]);
		\$this->add_responsive_control( 'bignum_size', [
			'label' => __( 'Size', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 20, 'max' => 200, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-st1-num' => 'font-size: {{SIZE}}{{UNIT}};' ],
		]);
		\$this->add_responsive_control( 'bignum_pos', [
			'label' => __( 'Position Right', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => -50, 'max' => 100, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-st1-num' => 'right: {{SIZE}}{{UNIT}};' ],
		]);
		\$this->end_controls_section();
PHP;

$content = str_replace(
    "\$this->add_premium_badge_controls();",
    $new_controls . "\n\t\t\$this->add_premium_badge_controls();",
    $content
);

// We need to inject the CSS for the new classes and remove hardcoded sizes if they conflict.
// In the CSS block:
// Replace `height: 130px;` in `.kc-st1-card`
$content = preg_replace('/height:\s*130px;/', '', $content);
$content = preg_replace('/height:\s*110px;/', '', $content);

// Ensure :where is used to lower specificity so Elementor can override.
$content = preg_replace('/\.' . '\$uid' . '-stack/', ':where(.' . '$uid' . '-stack)', $content);
$content = preg_replace('/\.' . '\$uid' . '-card/', ':where(.' . '$uid' . '-card)', $content);
$content = preg_replace('/\.' . '\$uid' . '-overlay/', ':where(.' . '$uid' . '-overlay)', $content);
$content = preg_replace('/\.' . '\$uid' . '-num/', ':where(.' . '$uid' . '-num)', $content);
$content = preg_replace('/\.' . '\$uid' . '-ch-name/', ':where(.' . '$uid' . '-ch-name)', $content);
$content = preg_replace('/\.' . '\$uid' . '-title/', ':where(.' . '$uid' . '-title)', $content);
$content = preg_replace('/\.' . '\$uid' . '-artist/', ':where(.' . '$uid' . '-artist)', $content);
$content = preg_replace('/\.' . '\$uid' . '-stats/', ':where(.' . '$uid' . '-stats)', $content);
$content = preg_replace('/\.' . '\$uid' . '-flip-grid/', ':where(.' . '$uid' . '-flip-grid)', $content);
$content = preg_replace('/\.' . '\$uid' . '-flip-card/', ':where(.' . '$uid' . '-flip-card)', $content);
$content = preg_replace('/\.' . '\$uid' . '-front/', ':where(.' . '$uid' . '-front)', $content);
$content = preg_replace('/\.' . '\$uid' . '-back/', ':where(.' . '$uid' . '-back)', $content);

// We need to make sure the specific classes we added to the selectors actually match the HTML.
// `kc-st1-ch-name` -> `.' . $uid . '-ch-name` => Wait, $uid is kc-st1-xxxx! 
// Let's just use `kc-st1-ch-name` if it matches, but it doesn't statically.
// I will rewrite the PHP patch to use the static classes like `.kc-elm-ch-name` so Elementor targeting works without knowing the dynamic $uid.

file_put_contents('patch_sidebartop.php', '...');
