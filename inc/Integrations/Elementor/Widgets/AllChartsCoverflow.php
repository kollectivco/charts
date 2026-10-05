<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 3D Coverflow & Interactive Modern Cards Showcase for All Charts.
 * Built with hardware-accelerated 3D physics, responsive mobile drag,
 * transparent adaptive background, and rich interactive music card data.
 */
class AllChartsCoverflow extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name()       { return 'kc_all_charts_coverflow'; }
	public function get_title()      { return __( 'Charts: 3D Coverflow All Charts', 'charts' ); }
	public function get_icon()       { return 'eicon-media-carousel'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {

		// ─── 1. CONTENT SETTINGS ───────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content Settings', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'theme_preset', [
			'label'   => __( 'Design Theme / Color Preset', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'transparent',
			'options' => [
				'transparent'  => __( 'Transparent Adaptive (شفاف يندمج مع الصفحة - مستحسن)', 'charts' ),
				'light_modern' => __( 'Clean Light Modern (أبيض عصري ناصع)', 'charts' ),
				'dark_cinema'  => __( 'Dark Cinema (داكن سينمائي فخم)', 'charts' ),
				'glass_blur'   => __( 'Glassmorphism (زجاجي شفاف فاخر)', 'charts' ),
				'luxury_gold'  => __( 'Luxury Gold (ذهبي ملكي)', 'charts' ),
			],
		] );

		$this->add_control( 'show_header', [
			'label'        => __( 'Show Header', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'header_title', [
			'label'       => __( 'Header Title', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'قوائم الموسيقى وتوب الشارتس',
			'condition'   => [ 'show_header' => 'yes' ],
			'label_block' => true,
		] );

		$this->add_control( 'header_subtitle', [
			'label'       => __( 'Header Subtitle', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'تصفح أحدث سباقات الأغاني والفنانين الأكثر استماعاً',
			'condition'   => [ 'show_header' => 'yes' ],
			'label_block' => true,
		] );

		$this->add_control( 'header_alignment', [
			'label'   => __( 'Header Alignment', 'charts' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => [
				'right'  => [ 'title' => __( 'Right', 'charts' ), 'icon' => 'eicon-text-align-right' ],
				'center' => [ 'title' => __( 'Center', 'charts' ), 'icon' => 'eicon-text-align-center' ],
				'left'   => [ 'title' => __( 'Left', 'charts' ), 'icon' => 'eicon-text-align-left' ],
			],
			'default'   => 'center',
			'condition' => [ 'show_header' => 'yes' ],
		] );

		$this->add_control( 'show_top_song', [
			'label'        => __( 'Show #1 Top Song Teaser', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
			'description'  => __( 'Display the #1 track and artist inside each chart card', 'charts' ),
		] );

		$this->add_control( 'max_charts', [
			'label'   => __( 'Max Charts to Display', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 3,
			'max'     => 40,
			'default' => 15,
		] );

		$this->add_control( 'link_target', [
			'label'   => __( 'Open in New Window', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'no',
		] );

		$this->end_controls_section();


		// ─── 2. 3D ANIMATION & PHYSICS ─────────────────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( '3D Animation & Physics', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'animation_preset', [
			'label'   => __( 'Animation Style', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'real_coverflow',
			'options' => [
				'real_coverflow' => __( 'True 3D Coverflow (دوران وعمق ثلاثي الأبعاد)', 'charts' ),
				'card_depth'     => __( 'Cinema Depth & Scale (تكبير وبروز وسطي)', 'charts' ),
				'smooth_slide'   => __( 'Modern Smooth Carousel (سلايدر بطاقات انسيابي)', 'charts' ),
			],
		] );

		$this->add_control( 'rotate_angle', [
			'label'       => __( '3D Rotation Angle (درجة الدوران)', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 0, 'max' => 50, 'step' => 2 ],
			],
			'default'     => [ 'size' => 22 ],
			'condition'   => [ 'animation_preset' => 'real_coverflow' ],
		] );

		$this->add_control( 'depth_amount', [
			'label'       => __( '3D Depth (عمق الكروت الجانبية)', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 20, 'max' => 300, 'step' => 10 ],
			],
			'default'     => [ 'size' => 120 ],
			'condition'   => [ 'animation_preset' => 'real_coverflow' ],
		] );

		$this->add_control( 'autoplay', [
			'label'        => __( 'Autoplay', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'autoplay_speed', [
			'label'     => __( 'Autoplay Interval (ms)', 'charts' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 4000,
			'min'       => 1500,
			'max'       => 10000,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'transition_speed', [
			'label'       => __( 'Transition Speed (ms)', 'charts' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 650,
			'min'         => 300,
			'max'         => 2000,
		] );

		$this->add_control( 'pause_on_hover', [
			'label'        => __( 'Pause on Hover', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'condition'    => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'loop', [
			'label'        => __( 'Infinite Loop', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'show_arrows', [
			'label'        => __( 'Show Navigation Arrows', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'show_dots', [
			'label'        => __( 'Show Pagination Dots', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->end_controls_section();


		// ─── 3. STYLE: GENERAL & BACKGROUND ───────────────────────────
		$this->start_controls_section( 'style_general', [
			'label' => __( 'General & Background', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'bg_type', [
			'label'   => __( 'Background Type', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'transparent',
			'options' => [
				'transparent' => __( 'Transparent (شفاف بدون خلفية - مستحسن)', 'charts' ),
				'preset'      => __( 'Follow Theme Preset', 'charts' ),
				'custom'      => __( 'Custom Color', 'charts' ),
			],
		] );

		$this->add_control( 'custom_bg_color', [
			'label'     => __( 'Custom Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'transparent',
			'condition' => [ 'bg_type' => 'custom' ],
			'selectors' => [
				'{{WRAPPER}} .kc-cov-wrap' => 'background: {{VALUE}} !important;',
			],
		] );

		$this->add_responsive_control( 'section_padding', [
			'label'      => __( 'Padding', 'charts' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', '%' ],
			'default'    => [
				'top'      => 30,
				'right'    => 10,
				'bottom'   => 40,
				'left'     => 10,
				'unit'     => 'px',
				'isLinked' => false,
			],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();


		// ─── 4. STYLE: HEADER ─────────────────────────────────────────
		$this->start_controls_section( 'style_header', [
			'label'     => __( 'Header', 'charts' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_header' => 'yes' ],
		] );

		$this->add_control( 'header_color', [
			'label'     => __( 'Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-title' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'header_typography',
			'selector' => '{{WRAPPER}} .kc-cov-title',
		] );

		$this->add_control( 'subtitle_color', [
			'label'     => __( 'Subtitle Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-subtitle' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->end_controls_section();


		// ─── 5. STYLE: CARDS & ARTWORK ────────────────────────────────
		$this->start_controls_section( 'style_cards', [
			'label' => __( 'Cards & Artwork', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'card_size', [
			'label'          => __( 'Card Width (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 200, 'max' => 450, 'step' => 5 ],
			],
			'default'        => [ 'size' => 300 ],
			'tablet_default' => [ 'size' => 260 ],
			'mobile_default' => [ 'size' => 240 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cov-slide' => 'width: {{SIZE}}px; max-width: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Card Corner Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 8, 'max' => 36 ] ],
			'default'    => [ 'size' => 20 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-card'         => 'border-radius: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cov-art-frame'    => 'border-top-left-radius: {{SIZE}}px; border-top-right-radius: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_bg', [
			'label'     => __( 'Card Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-card' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->add_control( 'active_accent_color', [
			'label'       => __( 'Active Card Border Glow Accent', 'charts' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#fe025b',
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-card' => 'border-color: {{VALUE}} !important; box-shadow: 0 25px 60px rgba(0,0,0,0.15), 0 0 25px {{VALUE}}30 !important;',
				'{{WRAPPER}} .kc-cov-rank-pill'                              => 'background: {{VALUE}} !important;',
			],
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Chart Name Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-card-title'   => 'color: {{VALUE}} !important;',
				'{{WRAPPER}} .kc-cov-card-title a' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typography',
			'selector' => '{{WRAPPER}} .kc-cov-card-title',
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .kc-cov-card',
		] );

		$this->end_controls_section();


		// ─── 6. STYLE: NAVIGATION & CONTROLS ─────────────────────────
		$this->start_controls_section( 'style_nav', [
			'label' => __( 'Navigation & Dots', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'nav_arrow_color', [
			'label'     => __( 'Arrows Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-nav-btn' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->add_control( 'nav_arrow_bg', [
			'label'     => __( 'Arrows Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-nav-btn' => 'background: {{VALUE}} !important;',
			],
		] );

		$this->add_control( 'dots_active_color', [
			'label'     => __( 'Active Dot Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#fe025b',
			'selectors' => [
				'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$uid = 'kc-cov-' . $this->get_id();
		$uid_safe = str_replace( '-', '_', $uid );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions( true );

		if ( empty( $defs ) ) {
			echo '<div style="padding: 24px; text-align: center; color: #888;">' . esc_html__( 'No public charts available.', 'charts' ) . '</div>';
			return;
		}

		$max_charts = ! empty( $settings['max_charts'] ) ? intval( $settings['max_charts'] ) : 15;
		$charts = array_slice( $defs, 0, $max_charts );

		$theme = $settings['theme_preset'] ?? 'transparent';
		$anim_preset = $settings['animation_preset'] ?? 'real_coverflow';
		$target_attr = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$show_top_song = ( ! empty( $settings['show_top_song'] ) && $settings['show_top_song'] === 'yes' );
		?>

		<style>
		/* ─── STANDALONE SWIPER ESSENTIALS (Ensures Swiper runs even if Elementor does not enqueue) ─── */
		.<?php echo $uid; ?>-wrap .swiper { margin-left: auto; margin-right: auto; position: relative; overflow: hidden; list-style: none; padding: 0; z-index: 1; }
		.<?php echo $uid; ?>-wrap .swiper-wrapper { position: relative; width: 100%; height: 100%; z-index: 1; display: flex; transition-property: transform; box-sizing: content-box; }
		.<?php echo $uid; ?>-wrap .swiper-slide { flex-shrink: 0; width: 100%; height: 100%; position: relative; transition-property: transform; }

		/* ─── BASE CONTAINER ─── */
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			box-sizing: border-box;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Cairo", "DIN Next LT Arabic", sans-serif;
			user-select: none;
			width: 100%;
			overflow: hidden;
			background: transparent;
		}

		/* ─── THEME PRESETS ─── */
		/* 1. Transparent (Default - blends seamlessly into any page) */
		.<?php echo $uid; ?>-wrap.theme-transparent {
			background: transparent !important;
		}
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-card {
			background: #ffffff;
			border: 1px solid rgba(226, 232, 240, 0.9);
			box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.07), 0 4px 12px rgba(0, 0, 0, 0.03);
		}
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-card-title a { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-leader-teaser { background: #f8fafc; border: 1px solid #e2e8f0; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-leader-song { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-leader-artist { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-nav-btn {
			background: #ffffff;
			color: #0f172a;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
			border: 1px solid #e2e8f0;
		}

		/* 2. Light Modern */
		.<?php echo $uid; ?>-wrap.theme-light_modern {
			background: #f8fafc;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-card-title a { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-leader-teaser { background: #f1f5f9; border: 1px solid #e2e8f0; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-leader-song { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-leader-artist { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-nav-btn {
			background: #ffffff;
			color: #0f172a;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
			border: 1px solid #e2e8f0;
		}

		/* 3. Dark Cinema */
		.<?php echo $uid; ?>-wrap.theme-dark_cinema {
			background: transparent !important;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-title { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-subtitle { color: #94a3b8; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-card {
			background: #13151b;
			border: 1px solid rgba(255, 255, 255, 0.09);
			box-shadow: 0 16px 40px rgba(0, 0, 0, 0.65);
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-card-title a { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-leader-teaser { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-leader-song { color: #f1f5f9; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-leader-artist { color: #94a3b8; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-nav-btn {
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
			border: 1px solid rgba(255, 255, 255, 0.15);
		}

		/* 4. Glassmorphism */
		.<?php echo $uid; ?>-wrap.theme-glass_blur {
			background: transparent !important;
		}
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-title { color: inherit; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-card {
			background: rgba(255, 255, 255, 0.72);
			backdrop-filter: blur(18px);
			-webkit-backdrop-filter: blur(18px);
			border: 1px solid rgba(255, 255, 255, 0.5);
			box-shadow: 0 16px 36px rgba(0, 0, 0, 0.08);
		}
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-card-title a { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-leader-teaser { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255, 255, 255, 0.4); }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-leader-song { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-leader-artist { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-nav-btn {
			background: rgba(255, 255, 255, 0.85);
			backdrop-filter: blur(10px);
			color: #0f172a;
			box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
		}

		/* 5. Luxury Gold */
		.<?php echo $uid; ?>-wrap.theme-luxury_gold {
			background: transparent !important;
		}
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-title { color: #d4af37; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-subtitle { color: #b89758; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-card {
			background: #181512;
			border: 1px solid rgba(212, 175, 55, 0.3);
			box-shadow: 0 18px 45px rgba(0, 0, 0, 0.75);
		}
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-card-title a { color: #f7e7ce; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-leader-teaser { background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-leader-song { color: #ffd700; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-leader-artist { color: #d4af37; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-nav-btn {
			background: rgba(212, 175, 55, 0.12);
			border: 1px solid rgba(212, 175, 55, 0.35);
			color: #ffd700;
		}

		/* ─── HEADER ─── */
		.<?php echo $uid; ?>-header {
			margin-bottom: 24px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'center' ); ?>;
		}
		.<?php echo $uid; ?>-title {
			font-size: 30px;
			font-weight: 900;
			margin: 0 0 6px 0;
			letter-spacing: -0.5px;
			line-height: 1.25;
		}
		.<?php echo $uid; ?>-subtitle {
			font-size: 14px;
			margin: 0;
			font-weight: 500;
			line-height: 1.5;
		}

		/* ─── SWIPER CONTAINER ─── */
		.<?php echo $uid; ?>-container {
			width: 100%;
			padding: 30px 0 45px;
			overflow: visible !important;
			perspective: 1200px;
		}

		.<?php echo $uid; ?> .swiper-wrapper {
			align-items: center;
			display: flex;
			transform-style: preserve-3d;
		}

		/* ─── SLIDE & CARD ─── */
		.<?php echo $uid; ?> .kc-cov-slide {
			width: 300px;
			max-width: 300px;
			flex-shrink: 0;
			cursor: pointer;
			text-decoration: none;
			box-sizing: border-box;
			transition: transform 0.6s cubic-bezier(0.2, 0.9, 0.3, 1), opacity 0.6s ease;
			will-change: transform, opacity;
			transform-style: preserve-3d;
			padding: 10px;
		}

		/* Card Shell */
		.<?php echo $uid; ?> .kc-cov-card {
			position: relative;
			border-radius: 20px;
			overflow: hidden;
			transition: transform 0.4s cubic-bezier(0.2, 0.9, 0.3, 1), box-shadow 0.4s ease, border-color 0.4s ease;
			display: flex;
			flex-direction: column;
			box-sizing: border-box;
			width: 100%;
		}

		/* Active Center Slide Elevation */
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card {
			transform: scale(1.05) translateZ(25px);
			border-color: rgba(254, 2, 91, 0.5) !important;
			box-shadow: 0 25px 60px rgba(0, 0, 0, 0.16), 0 0 25px rgba(254, 2, 91, 0.22) !important;
			z-index: 10;
		}
		.<?php echo $uid; ?> .kc-cov-slide:not(.swiper-slide-active) {
			opacity: 0.75;
		}
		.<?php echo $uid; ?> .kc-cov-slide:not(.swiper-slide-active):hover {
			opacity: 0.95;
		}

		/* Media / Artwork Frame */
		.<?php echo $uid; ?> .kc-cov-art-frame {
			position: relative;
			width: 100%;
			aspect-ratio: 1 / 1;
			overflow: hidden;
			background: #0f172a;
			display: block;
		}
		.<?php echo $uid; ?> .kc-cov-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform 0.6s cubic-bezier(0.2, 0.9, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-cov-card:hover .kc-cov-img {
			transform: scale(1.06);
		}
		.<?php echo $uid; ?> .kc-cov-art-gradient {
			position: absolute;
			inset: 0;
			background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0) 50%, rgba(0, 0, 0, 0.4) 100%);
			pointer-events: none;
		}

		/* Floating Badges */
		.<?php echo $uid; ?> .kc-cov-badge-cluster {
			position: absolute;
			top: 12px;
			right: 12px;
			display: flex;
			align-items: center;
			gap: 6px;
			z-index: 4;
		}
		.<?php echo $uid; ?> .kc-cov-badge-platform {
			background: rgba(15, 23, 42, 0.85);
			backdrop-filter: blur(8px);
			-webkit-backdrop-filter: blur(8px);
			color: #ffffff;
			font-size: 10px;
			font-weight: 800;
			padding: 4px 10px;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.18);
			letter-spacing: 0.3px;
		}
		.<?php echo $uid; ?> .kc-cov-badge-count {
			background: #fe025b;
			color: #ffffff;
			font-size: 10px;
			font-weight: 900;
			padding: 4px 8px;
			border-radius: 20px;
			letter-spacing: 0.5px;
			box-shadow: 0 2px 8px rgba(254, 2, 91, 0.4);
		}

		/* Hover Play Cue */
		.<?php echo $uid; ?> .kc-cov-play-cue {
			position: absolute;
			inset: 0;
			margin: auto;
			width: 52px;
			height: 52px;
			border-radius: 50%;
			background: rgba(254, 2, 91, 0.95);
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			transform: scale(0.7);
			transition: all 0.35s cubic-bezier(0.2, 0.9, 0.3, 1);
			box-shadow: 0 8px 24px rgba(254, 2, 91, 0.5);
			z-index: 5;
		}
		.<?php echo $uid; ?> .kc-cov-play-cue svg {
			margin-left: 2px;
		}
		.<?php echo $uid; ?> .kc-cov-card:hover .kc-cov-play-cue {
			opacity: 1;
			transform: scale(1);
		}

		/* Card Content */
		.<?php echo $uid; ?> .kc-cov-card-content {
			padding: 18px 18px 16px;
			display: flex;
			flex-direction: column;
			gap: 12px;
			text-align: right;
			box-sizing: border-box;
		}

		.<?php echo $uid; ?> .kc-cov-card-title {
			font-size: 18px;
			font-weight: 900;
			margin: 0;
			line-height: 1.3;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-cov-card-title a {
			text-decoration: none;
			transition: color 0.2s;
		}
		.<?php echo $uid; ?> .kc-cov-card-title a:hover {
			color: #fe025b !important;
		}

		/* #1 Song Leader Teaser */
		.<?php echo $uid; ?> .kc-cov-leader-teaser {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 8px 12px;
			border-radius: 12px;
			transition: background 0.2s;
		}
		.<?php echo $uid; ?> .kc-cov-rank-pill {
			width: 24px;
			height: 24px;
			border-radius: 6px;
			background: #fe025b;
			color: #ffffff;
			font-size: 11px;
			font-weight: 900;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			box-shadow: 0 2px 6px rgba(254, 2, 91, 0.3);
		}
		.<?php echo $uid; ?> .kc-cov-leader-text {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-cov-leader-song {
			font-size: 12px;
			font-weight: 800;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			line-height: 1.3;
		}
		.<?php echo $uid; ?> .kc-cov-leader-artist {
			font-size: 11px;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			line-height: 1.2;
		}

		.<?php echo $uid; ?> .kc-cov-meta-row {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 12px;
			color: #94a3b8;
			font-weight: 600;
		}
		.<?php echo $uid; ?> .kc-cov-live-dot {
			width: 7px;
			height: 7px;
			border-radius: 50%;
			background: #10b981;
			box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
		}

		/* Action CTA Button */
		.<?php echo $uid; ?> .kc-cov-card-action {
			margin-top: 2px;
			width: 100%;
		}
		.<?php echo $uid; ?> .kc-cov-action-btn {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			padding: 9px 14px;
			border-radius: 10px;
			background: rgba(254, 2, 91, 0.08);
			color: #fe025b;
			font-size: 12px;
			font-weight: 800;
			text-decoration: none;
			transition: all 0.25s ease;
			box-sizing: border-box;
		}
		.<?php echo $uid; ?> .kc-cov-card:hover .kc-cov-action-btn,
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-action-btn {
			background: #fe025b;
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(254, 2, 91, 0.35);
		}
		.<?php echo $uid; ?> .kc-cov-action-btn svg {
			transition: transform 0.25s ease;
		}
		.<?php echo $uid; ?> .kc-cov-card:hover .kc-cov-action-btn svg {
			transform: translateX(-4px);
		}

		/* Controls & Navigation */
		.<?php echo $uid; ?> .kc-cov-controls {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 16px;
			margin-top: 20px;
			z-index: 10;
			position: relative;
		}

		.<?php echo $uid; ?> .kc-cov-nav-btn {
			width: 44px;
			height: 44px;
			border-radius: 50%;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.25s cubic-bezier(0.2, 0.9, 0.3, 1);
			outline: none;
			border: none;
		}
		.<?php echo $uid; ?> .kc-cov-nav-btn:hover {
			transform: scale(1.1);
		}
		.<?php echo $uid; ?> .kc-cov-nav-btn:active {
			transform: scale(0.95);
		}

		/* Pagination Dots */
		.<?php echo $uid; ?> .swiper-pagination {
			position: static !important;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
		}
		.<?php echo $uid; ?> .swiper-pagination-bullet {
			width: 8px;
			height: 8px;
			border-radius: 4px;
			transition: all 0.35s ease;
			margin: 0 !important;
			background: #cbd5e1;
			opacity: 1;
		}
		.<?php echo $uid; ?> .swiper-pagination-bullet-active {
			width: 26px !important;
			border-radius: 6px !important;
			background: #fe025b !important;
		}

		/* Mobile & Tablet Optimizations */
		@media (max-width: 1024px) {
			.<?php echo $uid; ?>-title { font-size: 26px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 260px; max-width: 260px; }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 16px; }
		}

		@media (max-width: 640px) {
			.<?php echo $uid; ?>-wrap { padding: 20px 8px 30px; }
			.<?php echo $uid; ?>-title { font-size: 22px; }
			.<?php echo $uid; ?>-subtitle { font-size: 13px; }
			.<?php echo $uid; ?>-container { padding: 15px 0 25px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 235px; max-width: 235px; padding: 6px; }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 15px; }
			.<?php echo $uid; ?> .kc-cov-nav-btn { width: 38px; height: 38px; }
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-widget-wrap theme-<?php echo esc_attr( $theme ); ?>">
			<?php if ( $settings['show_header'] === 'yes' && ! empty( $settings['header_title'] ) ) : ?>
			<div class="<?php echo $uid; ?>-header">
				<h2 class="<?php echo $uid; ?>-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
				<?php if ( ! empty( $settings['header_subtitle'] ) ) : ?>
				<p class="<?php echo $uid; ?>-subtitle"><?php echo esc_html( $settings['header_subtitle'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="<?php echo $uid; ?>-container swiper" id="<?php echo $uid; ?>-swiper" dir="ltr">
				<div class="swiper-wrapper">
					<?php foreach ( $charts as $def ) :
						// Fetch preview entries to resolve high-res cover and #1 leader
						$entries = \Charts\Core\PublicIntegration::get_preview_entries( $def, 1 );
						$cover = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $entries );
						if ( empty( $cover ) ) {
							$cover = CHARTS_URL . 'public/assets/img/placeholder.png';
						}

						// Top #1 Song Details
						$top_track = ! empty( $entries[0] ) ? $entries[0] : null;
						$track_title = ! empty( $top_track->track_name ) ? $top_track->track_name : ( ! empty( $top_track->item_name ) ? $top_track->item_name : '' );
						$track_artist = ! empty( $top_track->artist_names ) ? $top_track->artist_names : '';

						$chart_url = home_url( '/charts/' . $def->slug . '/' );
					?>
					<div class="swiper-slide kc-cov-slide" data-url="<?php echo esc_url( $chart_url ); ?>">
						<div class="kc-cov-card">
							<!-- Media / Cover Link -->
							<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-media-link" style="text-decoration:none; display:block;">
								<div class="kc-cov-art-frame">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-cov-img" loading="lazy">
									<div class="kc-cov-art-gradient"></div>
									
									<!-- Floating Badges -->
									<div class="kc-cov-badge-cluster">
										<span class="kc-cov-badge-platform"><?php echo esc_html( $def->platform_display ?? 'شارت أسبوعي' ); ?></span>
										<?php if ( ! empty( $def->item_count ) && $def->item_count > 0 ) : ?>
										<span class="kc-cov-badge-count">TOP <?php echo intval( $def->item_count ); ?></span>
										<?php endif; ?>
									</div>

									<!-- Hover Play Cue -->
									<div class="kc-cov-play-cue">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
									</div>
								</div>
							</a>

							<!-- Interactive Body (RTL text) -->
							<div class="kc-cov-card-content" dir="rtl">
								<h3 class="kc-cov-card-title">
									<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
								</h3>

								<?php if ( $show_top_song && $top_track && $track_title ) : ?>
								<div class="kc-cov-leader-teaser">
									<div class="kc-cov-rank-pill">#1</div>
									<div class="kc-cov-leader-text">
										<span class="kc-cov-leader-song" title="<?php echo esc_attr( $track_title ); ?>"><?php echo esc_html( $track_title ); ?></span>
										<?php if ( $track_artist ) : ?>
										<span class="kc-cov-leader-artist" title="<?php echo esc_attr( $track_artist ); ?>"><?php echo esc_html( $track_artist ); ?></span>
										<?php endif; ?>
									</div>
								</div>
								<?php else : ?>
								<div class="kc-cov-meta-row">
									<span class="kc-cov-live-dot"></span>
									<span><?php esc_html_e( 'محدث ببيانات مباشرة', 'charts' ); ?></span>
								</div>
								<?php endif; ?>

								<div class="kc-cov-card-action">
									<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-action-btn">
										<span><?php esc_html_e( 'عرض الشارت كامل', 'charts' ); ?></span>
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
									</a>
								</div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Controls -->
			<div class="kc-cov-controls">
				<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
				<button type="button" class="kc-cov-nav-btn <?php echo $uid; ?>-prev" aria-label="<?php esc_attr_e( 'Previous', 'charts' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
				<?php endif; ?>

				<?php if ( $settings['show_dots'] === 'yes' ) : ?>
				<div class="swiper-pagination <?php echo $uid; ?>-pagination"></div>
				<?php endif; ?>

				<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
				<button type="button" class="kc-cov-nav-btn <?php echo $uid; ?>-next" aria-label="<?php esc_attr_e( 'Next', 'charts' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<?php endif; ?>
			</div>
		</div>

		<script>
		(function() {
			function loadSwiperAssets(callback) {
				// Inject Swiper CSS if not already loaded on the page
				if (!document.getElementById("kc-swiper-bundle-css")) {
					var link = document.createElement("link");
					link.id = "kc-swiper-bundle-css";
					link.rel = "stylesheet";
					link.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
					document.head.appendChild(link);
				}

				if (typeof Swiper !== "undefined") {
					callback();
					return;
				}

				// Inject Swiper JS if not already on page
				if (!document.getElementById("kc-swiper-bundle-js")) {
					var script = document.createElement("script");
					script.id = "kc-swiper-bundle-js";
					script.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
					script.onload = function() { callback(); };
					document.head.appendChild(script);
				} else {
					var checkInterval = setInterval(function() {
						if (typeof Swiper !== "undefined") {
							clearInterval(checkInterval);
							callback();
						}
					}, 60);
				}
			}

			function initCoverflow_<?php echo $uid_safe; ?>() {
				loadSwiperAssets(function() {
					var container = document.getElementById("<?php echo $uid; ?>-swiper");
					if (!container) return;

					if (container.swiper) {
						try { container.swiper.destroy(true, true); } catch(e) {}
					}

					var animPreset = "<?php echo esc_js( $anim_preset ); ?>";
					var swiperConfig = {
						grabCursor: true,
						centeredSlides: true,
						slidesPerView: "auto",
						slideToClickedSlide: true,
						watchSlidesProgress: true,
						speed: <?php echo intval( $settings['transition_speed'] ?? 650 ); ?>,
						loop: <?php echo $loop_opt ? 'true' : 'false'; ?>,
						navigation: {
							nextEl: ".<?php echo $uid; ?>-next",
							prevEl: ".<?php echo $uid; ?>-prev"
						},
						pagination: {
							el: ".<?php echo $uid; ?>-pagination",
							clickable: true
						}
					};

					<?php if ( $autoplay_opt ) : ?>
					swiperConfig.autoplay = {
						delay: <?php echo intval( $settings['autoplay_speed'] ?? 4000 ); ?>,
						disableOnInteraction: false,
						pauseOnMouseEnter: <?php echo ( ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
					};
					<?php endif; ?>

					if (animPreset === "real_coverflow") {
						swiperConfig.effect = "coverflow";
						swiperConfig.coverflowEffect = {
							rotate: <?php echo intval( $settings['rotate_angle']['size'] ?? 22 ); ?>,
							stretch: 0,
							depth: <?php echo intval( $settings['depth_amount']['size'] ?? 120 ); ?>,
							modifier: 1,
							slideShadows: false
						};
						swiperConfig.spaceBetween = 20;
					} else {
						swiperConfig.effect = "slide";
						swiperConfig.spaceBetween = 28;
					}

					new Swiper(container, swiperConfig);
				});
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initCoverflow_<?php echo $uid_safe; ?>);
			} else {
				initCoverflow_<?php echo $uid_safe; ?>();
			}

			// Elementor Live Preview Hook
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction("frontend/element_ready/kc_all_charts_coverflow.default", function() {
					initCoverflow_<?php echo $uid_safe; ?>();
				});
			}
		})();
		</script>
		<?php
	}
}
