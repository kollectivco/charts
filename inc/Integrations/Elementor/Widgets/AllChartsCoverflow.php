<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 3D Coverflow animated All-Charts carousel widget with mobile optimization.
 * Highly configurable themes (Dark Cinema, Clean Light, Glassmorphism, Luxury Gold, Transparent).
 * Real 3D Coverflow rotation and depth physics with hardware-accelerated animations.
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
			'default' => 'light_modern',
			'options' => [
				'light_modern' => __( 'Clean Light Modern (أبيض عصري)', 'charts' ),
				'dark_cinema'  => __( 'Dark Cinema (داكن سينمائي)', 'charts' ),
				'glass_blur'   => __( 'Glassmorphism (زجاجي شفاف)', 'charts' ),
				'luxury_gold'  => __( 'Luxury Gold (ذهبي فاخر)', 'charts' ),
				'custom'       => __( 'Custom Styling (تخصيص كامل)', 'charts' ),
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


		// ─── 2. 3D COVERFLOW & ANIMATION SETTINGS ─────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( '3D Animation & Physics', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'animation_preset', [
			'label'   => __( '3D Effect Style', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'real_coverflow',
			'options' => [
				'real_coverflow' => __( 'True 3D Coverflow (دوران وعمق ثلاثي الأبعاد)', 'charts' ),
				'card_zoom'      => __( 'Cinema Depth & Scale (تكبير وبروز وسطي)', 'charts' ),
				'flat_carousel'  => __( 'Modern Smooth Slide (سلايدر مسطح ناعم)', 'charts' ),
			],
		] );

		$this->add_control( 'rotate_angle', [
			'label'       => __( '3D Rotation Angle (درجة الدوران)', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 0, 'max' => 60, 'step' => 2 ],
			],
			'default'     => [ 'size' => 28 ],
			'condition'   => [ 'animation_preset' => 'real_coverflow' ],
		] );

		$this->add_control( 'depth_amount', [
			'label'       => __( '3D Depth (عمق الكروت الجانبية)', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 20, 'max' => 400, 'step' => 10 ],
			],
			'default'     => [ 'size' => 160 ],
			'condition'   => [ 'animation_preset' => 'real_coverflow' ],
		] );

		$this->add_control( 'slide_shadows', [
			'label'        => __( '3D Realistic Slide Shadows', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
			'condition'    => [ 'animation_preset' => 'real_coverflow' ],
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
			'default'   => 3500,
			'min'       => 1500,
			'max'       => 10000,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'transition_speed', [
			'label'       => __( 'Transition Smoothness (ms)', 'charts' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 700,
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
			'default' => 'preset',
			'options' => [
				'preset'      => __( 'Follow Theme Preset', 'charts' ),
				'transparent' => __( 'Transparent (بدون خلفية)', 'charts' ),
				'custom'      => __( 'Custom Color', 'charts' ),
			],
		] );

		$this->add_control( 'custom_bg_color', [
			'label'     => __( 'Custom Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#f8f9fa',
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
				'top'      => 40,
				'right'    => 16,
				'bottom'   => 48,
				'left'     => 16,
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
			'default'        => [ 'size' => 280 ],
			'tablet_default' => [ 'size' => 240 ],
			'mobile_default' => [ 'size' => 210 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cov-slide' => 'width: {{SIZE}}px; max-width: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Artwork Corner Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 36 ] ],
			'default'    => [ 'size' => 18 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-card-art'     => 'border-radius: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cov-card-art img' => 'border-radius: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'show_neon_corners', [
			'label'        => __( 'Show Animated Frame Corners', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'active_frame_color', [
			'label'       => __( 'Active Frame Accent Color', 'charts' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#fe025b',
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-frame-accent' => 'border-color: {{VALUE}};',
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-corner'       => 'border-color: {{VALUE}};',
			],
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Chart Name Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-card-title' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typography',
			'selector' => '{{WRAPPER}} .kc-cov-card-title',
		] );

		$this->add_control( 'card_meta_color', [
			'label'     => __( 'Subtitle / Meta Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .kc-cov-card-meta' => 'color: {{VALUE}} !important;',
			],
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

		$theme = $settings['theme_preset'] ?? 'light_modern';
		$anim_preset = $settings['animation_preset'] ?? 'real_coverflow';
		$target_attr = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$show_neon = ( ! empty( $settings['show_neon_corners'] ) && $settings['show_neon_corners'] === 'yes' );
		?>

		<style>
		/* ─── BASE CONTAINER ─── */
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			overflow: hidden;
			box-sizing: border-box;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Cairo", "DIN Next LT Arabic", sans-serif;
			user-select: none;
			transition: background 0.3s ease;
		}

		/* ─── THEME PRESETS ─── */
		/* Light Modern (Default) */
		.<?php echo $uid; ?>-wrap.theme-light_modern {
			background: #f8fafc;
			color: #0f172a;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-card-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-card-meta { color: #94a3b8; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-nav-btn {
			background: #ffffff;
			color: #0f172a;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
			border: 1px solid #e2e8f0;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-nav-btn:hover {
			background: #0f172a;
			color: #ffffff;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .swiper-pagination-bullet { background: #cbd5e1; opacity: 1; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-card-art {
			background: #ffffff;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-slide.swiper-slide-active .kc-cov-card-art {
			box-shadow: 0 20px 45px rgba(0, 0, 0, 0.16), 0 0 0 1px rgba(0, 0, 0, 0.04);
		}

		/* Dark Cinema */
		.<?php echo $uid; ?>-wrap.theme-dark_cinema {
			background: #090a0f;
			color: #ffffff;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-title { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-subtitle { color: #94a3b8; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-card-title { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-card-meta { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-nav-btn {
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
			border: 1px solid rgba(255, 255, 255, 0.12);
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-nav-btn:hover {
			background: rgba(255, 255, 255, 0.22);
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .swiper-pagination-bullet { background: rgba(255, 255, 255, 0.25); }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-card-art {
			background: #18181b;
			box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-slide.swiper-slide-active .kc-cov-card-art {
			box-shadow: 0 22px 55px rgba(0, 0, 0, 0.85);
		}

		/* Glassmorphism */
		.<?php echo $uid; ?>-wrap.theme-glass_blur {
			background: transparent;
			color: inherit;
		}
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-card-art {
			background: rgba(255, 255, 255, 0.65);
			backdrop-filter: blur(16px);
			-webkit-backdrop-filter: blur(16px);
			border: 1px solid rgba(255, 255, 255, 0.4);
			box-shadow: 0 12px 32px rgba(0, 0, 0, 0.07);
		}
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-nav-btn {
			background: rgba(255, 255, 255, 0.8);
			backdrop-filter: blur(10px);
			color: #0f172a;
			box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
		}

		/* Luxury Gold */
		.<?php echo $uid; ?>-wrap.theme-luxury_gold {
			background: radial-gradient(circle at 50% 20%, #1a1610 0%, #0d0b08 100%);
			color: #f7e7ce;
		}
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-title { color: #ffd700; text-shadow: 0 2px 14px rgba(255, 215, 0, 0.25); }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-subtitle { color: #d4af37; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-card-title { color: #f7e7ce; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-card-meta { color: #b89758; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-nav-btn {
			background: rgba(212, 175, 55, 0.12);
			border: 1px solid rgba(212, 175, 55, 0.35);
			color: #ffd700;
		}
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-slide.swiper-slide-active .kc-cov-card-art {
			box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 35px rgba(212, 175, 55, 0.35);
		}

		/* Transparent option */
		<?php if ( ( $settings['bg_type'] ?? 'preset' ) === 'transparent' ) : ?>
		.<?php echo $uid; ?>-wrap { background: transparent !important; }
		<?php endif; ?>

		/* ─── HEADER ─── */
		.<?php echo $uid; ?>-header {
			margin-bottom: 28px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'center' ); ?>;
		}
		.<?php echo $uid; ?>-title {
			font-size: 32px;
			font-weight: 900;
			margin: 0 0 8px 0;
			letter-spacing: -0.5px;
			line-height: 1.25;
		}
		.<?php echo $uid; ?>-subtitle {
			font-size: 15px;
			margin: 0;
			font-weight: 500;
			line-height: 1.5;
		}

		/* ─── SWIPER CONTAINER ─── */
		.<?php echo $uid; ?>-container {
			width: 100%;
			padding: 40px 0 55px;
			overflow: visible !important;
			perspective: 1200px;
		}

		.<?php echo $uid; ?> .swiper-wrapper {
			align-items: center;
			display: flex;
			transform-style: preserve-3d;
		}

		/* ─── SLIDE CARD ─── */
		.<?php echo $uid; ?> .kc-cov-slide {
			width: 280px;
			max-width: 280px;
			flex-shrink: 0;
			cursor: pointer;
			text-decoration: none;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-align: center;
			box-sizing: border-box;
			transition: transform 0.6s cubic-bezier(0.2, 0.9, 0.3, 1), opacity 0.6s ease, filter 0.6s ease;
			will-change: transform, opacity;
			transform-style: preserve-3d;
		}

		/* Slide scale when not using real coverflow */
		<?php if ( $anim_preset !== 'real_coverflow' ) : ?>
		.<?php echo $uid; ?> .kc-cov-slide {
			opacity: 0.65;
			transform: scale(0.85);
			filter: grayscale(15%) brightness(0.9);
		}
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active {
			opacity: 1;
			transform: scale(1.15) translateZ(30px);
			filter: none;
			z-index: 10;
		}
		<?php endif; ?>

		/* Artwork Card Frame */
		.<?php echo $uid; ?> .kc-cov-card-art {
			position: relative;
			width: 100%;
			aspect-ratio: 1 / 1;
			border-radius: 18px;
			transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.3, 1), box-shadow 0.5s ease;
			overflow: hidden;
			transform: translateZ(0);
		}

		.<?php echo $uid; ?> .kc-cov-card-art img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			border-radius: 18px;
			display: block;
			image-rendering: -webkit-optimize-contrast;
			image-rendering: crisp-edges;
			backface-visibility: hidden;
			-webkit-backface-visibility: hidden;
			transform: translateZ(0);
			transition: transform 0.6s ease;
		}

		.<?php echo $uid; ?> .kc-cov-slide:hover .kc-cov-card-art img {
			transform: scale(1.05) translateZ(0);
		}

		/* ─── ANIMATED NEON FRAME CORNERS ─── */
		<?php if ( $show_neon ) : ?>
		.<?php echo $uid; ?> .kc-cov-corners {
			position: absolute;
			inset: -5px;
			pointer-events: none;
			opacity: 0;
			transform: scale(0.96);
			transition: opacity 0.4s ease, transform 0.4s ease;
			z-index: 5;
		}
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-corners {
			opacity: 1;
			transform: scale(1);
		}
		.<?php echo $uid; ?> .kc-cov-corner {
			position: absolute;
			width: 22px;
			height: 22px;
			border-style: solid;
			border-width: 0;
			transition: border-color 0.3s;
		}
		.<?php echo $uid; ?> .kc-cov-corner.top-left {
			top: 0; left: 0;
			border-top-width: 3.5px; border-left-width: 3.5px;
			border-top-left-radius: 10px;
		}
		.<?php echo $uid; ?> .kc-cov-corner.top-right {
			top: 0; right: 0;
			border-top-width: 3.5px; border-right-width: 3.5px;
			border-top-right-radius: 10px;
		}
		.<?php echo $uid; ?> .kc-cov-corner.bottom-left {
			bottom: 0; left: 0;
			border-bottom-width: 3.5px; border-left-width: 3.5px;
			border-bottom-left-radius: 10px;
		}
		.<?php echo $uid; ?> .kc-cov-corner.bottom-right {
			bottom: 0; right: 0;
			border-bottom-width: 3.5px; border-right-width: 3.5px;
			border-bottom-right-radius: 10px;
		}
		.<?php echo $uid; ?> .kc-cov-frame-accent {
			position: absolute;
			inset: 0;
			border-radius: 18px;
			border: 2px solid transparent;
			pointer-events: none;
			transition: border-color 0.35s ease;
			z-index: 4;
		}
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-frame-accent {
			border-color: rgba(254, 2, 91, 0.45);
		}
		<?php else : ?>
		.<?php echo $uid; ?> .kc-cov-corners,
		.<?php echo $uid; ?> .kc-cov-frame-accent { display: none; }
		<?php endif; ?>

		/* Badge / Top Indicator */
		.<?php echo $uid; ?> .kc-cov-badge {
			position: absolute;
			top: 12px;
			right: 12px;
			background: rgba(15, 23, 42, 0.85);
			backdrop-filter: blur(8px);
			-webkit-backdrop-filter: blur(8px);
			color: #ffffff;
			font-size: 11px;
			font-weight: 800;
			padding: 4px 10px;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.15);
			z-index: 6;
			letter-spacing: 0.5px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}

		/* Chart Info */
		.<?php echo $uid; ?> .kc-cov-card-info {
			margin-top: 16px;
			width: 100%;
			padding: 0 6px;
			transition: transform 0.4s ease;
		}
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card-info {
			transform: translateY(2px);
		}
		.<?php echo $uid; ?> .kc-cov-card-title {
			font-size: 18px;
			font-weight: 800;
			margin: 0;
			line-height: 1.35;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			transition: color 0.3s;
		}
		.<?php echo $uid; ?> .kc-cov-card-meta {
			font-size: 13px;
			margin-top: 4px;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Controls & Navigation */
		.<?php echo $uid; ?> .kc-cov-controls {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 18px;
			margin-top: 28px;
			z-index: 10;
			position: relative;
		}

		.<?php echo $uid; ?> .kc-cov-nav-btn {
			width: 46px;
			height: 46px;
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
		}
		.<?php echo $uid; ?> .swiper-pagination-bullet-active {
			width: 26px !important;
			border-radius: 6px !important;
		}

		/* Mobile & Tablet Optimizations */
		@media (max-width: 1024px) {
			.<?php echo $uid; ?>-title { font-size: 26px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 240px; max-width: 240px; }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 16px; }
		}

		@media (max-width: 640px) {
			.<?php echo $uid; ?>-wrap { padding: 24px 12px 32px; }
			.<?php echo $uid; ?>-title { font-size: 22px; }
			.<?php echo $uid; ?>-subtitle { font-size: 13px; }
			.<?php echo $uid; ?>-container { padding: 20px 0 35px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 205px; max-width: 205px; }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 15px; }
			.<?php echo $uid; ?> .kc-cov-nav-btn { width: 40px; height: 40px; }
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

			<div class="<?php echo $uid; ?>-container swiper" id="<?php echo $uid; ?>-swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $charts as $def ) :
						// Resolve Artwork with HD upscaler:
						$entries = \Charts\Core\PublicIntegration::get_preview_entries( $def, 1 );
						$cover = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $entries );
						if ( empty( $cover ) ) {
							$cover = CHARTS_URL . 'public/assets/img/placeholder.png';
						}

						$chart_url = home_url( '/charts/' . $def->slug . '/' );
						$accent = ! empty( $def->accent_color ) ? $def->accent_color : ( $settings['active_frame_color'] ?? '#fe025b' );
					?>
					<div class="swiper-slide kc-cov-slide" data-url="<?php echo esc_url( $chart_url ); ?>">
						<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> style="text-decoration:none; color:inherit; width:100%; display:flex; flex-direction:column; align-items:center;">
							<div class="kc-cov-card-art">
								<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" loading="lazy">
								
								<?php if ( $show_neon ) : ?>
								<div class="kc-cov-corners">
									<span class="kc-cov-corner top-left" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner top-right" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner bottom-left" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner bottom-right" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
								</div>
								<div class="kc-cov-frame-accent" style="border-color: <?php echo esc_attr( $accent ); ?>;"></div>
								<?php endif; ?>

								<div class="kc-cov-badge">
									<?php echo ( ! empty( $def->item_count ) && $def->item_count > 0 ) ? 'TOP ' . intval( $def->item_count ) : 'CHART'; ?>
								</div>
							</div>

							<div class="kc-cov-card-info">
								<h3 class="kc-cov-card-title"><?php echo esc_html( $def->title ); ?></h3>
								<div class="kc-cov-card-meta">
									<?php echo esc_html( $def->platform_display ?? 'قائمة أسبوعية' ); ?>
								</div>
							</div>
						</a>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Controls -->
			<div class="kc-cov-controls">
				<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
				<button type="button" class="kc-cov-nav-btn <?php echo $uid; ?>-prev" aria-label="<?php esc_attr_e( 'Previous', 'charts' ); ?>">
					<!-- RTL: Right arrow points forward/prev -->
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
				<?php endif; ?>

				<?php if ( $settings['show_dots'] === 'yes' ) : ?>
				<div class="swiper-pagination <?php echo $uid; ?>-pagination"></div>
				<?php endif; ?>

				<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
				<button type="button" class="kc-cov-nav-btn <?php echo $uid; ?>-next" aria-label="<?php esc_attr_e( 'Next', 'charts' ); ?>">
					<!-- RTL: Left arrow points backwards/next -->
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<?php endif; ?>
			</div>
		</div>

		<script>
		(function() {
			function initCoverflow_<?php echo $uid_safe; ?>() {
				if (typeof Swiper === "undefined") {
					setTimeout(initCoverflow_<?php echo $uid_safe; ?>, 120);
					return;
				}

				var container = document.getElementById("<?php echo $uid; ?>-swiper");
				if (!container) return;

				// Destroy existing instance if re-initializing in Elementor preview
				if (container.swiper) {
					try { container.swiper.destroy(true, true); } catch(e) {}
				}

				var animPreset = "<?php echo esc_js( $anim_preset ); ?>";
				var swiperConfig = {
					grabCursor: true,
					centeredSlides: true,
					slidesPerView: "auto",
					speed: <?php echo intval( $settings['transition_speed'] ?? 700 ); ?>,
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
					delay: <?php echo intval( $settings['autoplay_speed'] ?? 3500 ); ?>,
					disableOnInteraction: false,
					pauseOnMouseEnter: <?php echo ( ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
				};
				<?php endif; ?>

				if (animPreset === "real_coverflow") {
					swiperConfig.effect = "coverflow";
					swiperConfig.coverflowEffect = {
						rotate: <?php echo intval( $settings['rotate_angle']['size'] ?? 28 ); ?>,
						stretch: 0,
						depth: <?php echo intval( $settings['depth_amount']['size'] ?? 160 ); ?>,
						modifier: 1,
						slideShadows: <?php echo ( ( $settings['slide_shadows'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
					};
					swiperConfig.spaceBetween = 20;
				} else {
					swiperConfig.effect = "slide";
					swiperConfig.spaceBetween = 34;
					swiperConfig.breakpoints = {
						320: { spaceBetween: 16 },
						640: { spaceBetween: 24 },
						1024: { spaceBetween: 34 }
					};
				}

				swiperConfig.on = {
					click: function(s) {
						if (s.clickedIndex !== undefined && s.clickedIndex !== s.activeIndex) {
							s.slideTo(s.clickedIndex);
						}
					}
				};

				new Swiper(container, swiperConfig);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initCoverflow_<?php echo $uid_safe; ?>);
			} else {
				initCoverflow_<?php echo $uid_safe; ?>();
			}

			// Elementor Editor Live Preview Hook
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
