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
 * Supports 3 Card Design Styles:
 *   1. Overlay Card (أوفرلاي سينمائي - الصورة تملأ الكارت بالكامل)
 *   2. Normal Classic (تصميم عادي - الصورة بالأعلى والتفاصيل بالأسفل)
 *   3. Bento Style (بينتو ستايل متطور - بطاقات ذكية بأقسام تفاعلية)
 * Transparent Adaptive Background by Default, Smooth 3D/Carousel Animation,
 * and Live #1 Song Teaser.
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

		$this->add_control( 'card_layout', [
			'label'   => __( 'Card Design Style (ستايل وتصميم الكارت)', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'overlaycard',
			'options' => [
				'overlaycard' => __( 'Overlay Card (أوفرلاي سينمائي - الصورة تملأ الكارت بالكامل)', 'charts' ),
				'normal'      => __( 'Normal Classic (تصميم عادي - الصورة بالأعلى والتفاصيل بالأسفل)', 'charts' ),
				'bento'       => __( 'Bento Style (بينتو ستايل متطور - بطاقات ذكية بأقسام تفاعلية)', 'charts' ),
			],
		] );

		$this->add_control( 'theme_preset', [
			'label'   => __( 'Design Theme / Color Preset', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'transparent',
			'options' => [
				'transparent'  => __( 'Transparent Adaptive (شفاف يندمج مع الصفحة - مستحسن)', 'charts' ),
				'dark_cinema'  => __( 'Dark Cinema (داكن سينمائي فاخر)', 'charts' ),
				'light_modern' => __( 'Clean Light Modern (أبيض عصري ناصع)', 'charts' ),
				'glass_blur'   => __( 'Glassmorphism (زجاجي شفاف)', 'charts' ),
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
			'description'  => __( 'Display the #1 leading track inside each chart card', 'charts' ),
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
			'label' => __( 'Carousel & Animation Physics', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'animation_preset', [
			'label'   => __( 'Animation Preset', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'smooth_carousel',
			'options' => [
				'smooth_carousel' => __( 'Modern Smooth Carousel (سلايدر بطاقات انسيابي حديث - مستحسن)', 'charts' ),
				'bento_depth'     => __( 'Interactive 3D Depth (تكبير وبروز وسطي مع عمق)', 'charts' ),
				'coverflow_3d'    => __( 'Gentle 3D Coverflow (دوران ثلاثي الأبعاد معتدل)', 'charts' ),
			],
		] );

		$this->add_control( 'rotate_angle', [
			'label'       => __( '3D Rotation Angle', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 0, 'max' => 45, 'step' => 2 ],
			],
			'default'     => [ 'size' => 18 ],
			'condition'   => [ 'animation_preset' => 'coverflow_3d' ],
		] );

		$this->add_control( 'depth_amount', [
			'label'       => __( '3D Depth', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 20, 'max' => 300, 'step' => 10 ],
			],
			'default'     => [ 'size' => 100 ],
			'condition'   => [ 'animation_preset' => 'coverflow_3d' ],
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
			'default'     => 600,
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
				'transparent' => __( 'Transparent (شفاف بدون خلفية - يندمج مع الصفحة)', 'charts' ),
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


		// ─── 4. STYLE: CARDS & ARTWORK ────────────────────────────────
		$this->start_controls_section( 'style_cards', [
			'label' => __( 'Cards & Artwork', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'card_size', [
			'label'          => __( 'Card Width (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 220, 'max' => 450, 'step' => 5 ],
			],
			'default'        => [ 'size' => 310 ],
			'tablet_default' => [ 'size' => 270 ],
			'mobile_default' => [ 'size' => 250 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cov-slide' => 'width: {{SIZE}}px; max-width: {{SIZE}}px;',
			],
		] );

		$this->add_responsive_control( 'card_height', [
			'label'          => __( 'Card Height (px) [Overlay / Bento]', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 320, 'max' => 550, 'step' => 10 ],
			],
			'default'        => [ 'size' => 410 ],
			'condition'      => [ 'card_layout' => [ 'overlaycard', 'bento' ] ],
			'selectors'      => [
				'{{WRAPPER}} .kc-card-overlay' => 'height: {{SIZE}}px; min-height: {{SIZE}}px;',
				'{{WRAPPER}} .kc-card-bento'   => 'min-height: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Card Corner Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 12, 'max' => 40 ] ],
			'default'    => [ 'size' => 24 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-card' => 'border-radius: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'accent_color', [
			'label'       => __( 'Active Accent & Button Color', 'charts' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#fe025b',
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-card' => 'border-color: {{VALUE}} !important; box-shadow: 0 25px 60px rgba(0,0,0,0.18), 0 0 25px {{VALUE}}35 !important;',
				'{{WRAPPER}} .kc-cov-overlay-btn'                            => 'background: {{VALUE}} !important;',
				'{{WRAPPER}} .kc-cov-normal-btn'                             => 'background: {{VALUE}} !important; color: #fff !important;',
				'{{WRAPPER}} .kc-bento-btn'                                  => 'background: {{VALUE}} !important;',
				'{{WRAPPER}} .kc-cov-overlay-rank'                           => 'background: {{VALUE}} !important;',
				'{{WRAPPER}} .kc-cov-normal-rank'                            => 'background: {{VALUE}} !important;',
				'{{WRAPPER}} .kc-bento-leader-rank'                          => 'background: {{VALUE}} !important;',
				'{{WRAPPER}} .swiper-pagination-bullet-active'               => 'background: {{VALUE}} !important;',
			],
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .kc-cov-card',
		] );

		$this->end_controls_section();


		// ─── 5. STYLE: NAVIGATION & CONTROLS ─────────────────────────
		$this->start_controls_section( 'style_nav', [
			'label' => __( 'Navigation & Controls', 'charts' ),
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

		$card_layout = $settings['card_layout'] ?? 'overlaycard';
		$theme = $settings['theme_preset'] ?? 'transparent';
		$anim_preset = $settings['animation_preset'] ?? 'smooth_carousel';
		$target_attr = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$show_top_song = ( ! empty( $settings['show_top_song'] ) && $settings['show_top_song'] === 'yes' );
		$card_height = ! empty( $settings['card_height']['size'] ) ? intval( $settings['card_height']['size'] ) : 410;
		?>

		<style>
		/* ─── STANDALONE SWIPER ESSENTIALS ─── */
		.<?php echo $uid; ?>-wrap .swiper { margin: 0 auto; position: relative; overflow: hidden; list-style: none; padding: 0; z-index: 1; }
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

		/* ─── THEME STYLES ─── */
		/* Transparent Adaptive (Default) */
		.<?php echo $uid; ?>-wrap.theme-transparent { background: transparent !important; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-card-normal { background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.9); }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-card-normal .kc-cov-normal-title a { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-card-bento { background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.9); }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-card-bento .kc-bento-title a { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-bento-metric-chip { background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-bento-leader-box { background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-nav-btn { background: #ffffff; color: #0f172a; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; }

		/* Dark Cinema */
		.<?php echo $uid; ?>-wrap.theme-dark_cinema { background: transparent !important; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-title { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-subtitle { color: #94a3b8; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal { background: #13151b; border: 1px solid rgba(255, 255, 255, 0.1); }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal .kc-cov-normal-title a { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-bento { background: #13151b; border: 1px solid rgba(255, 255, 255, 0.1); }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-bento .kc-bento-title a { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-bento-metric-chip { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: #f1f5f9; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-bento-leader-box { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); color: #f1f5f9; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-nav-btn { background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.18); }

		/* Light Modern */
		.<?php echo $uid; ?>-wrap.theme-light_modern { background: #f8fafc; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-card-normal { background: #ffffff; border: 1px solid #e2e8f0; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-card-bento { background: #ffffff; border: 1px solid #e2e8f0; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-bento-metric-chip { background: #f1f5f9; border: 1px solid #e2e8f0; color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-bento-leader-box { background: #f1f5f9; border: 1px solid #e2e8f0; color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-nav-btn { background: #ffffff; color: #0f172a; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; }

		/* Glassmorphism */
		.<?php echo $uid; ?>-wrap.theme-glass_blur { background: transparent !important; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-title { color: inherit; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-subtitle { color: #64748b; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-card-normal { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5); }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-card-bento { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5); }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-bento-metric-chip { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255, 255, 255, 0.4); color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-bento-leader-box { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255, 255, 255, 0.4); color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-nav-btn { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); color: #0f172a; }

		/* Luxury Gold */
		.<?php echo $uid; ?>-wrap.theme-luxury_gold { background: transparent !important; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-title { color: #ffd700; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-subtitle { color: #d4af37; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-card-normal { background: #1a1612; border: 1px solid rgba(212, 175, 55, 0.3); }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-card-normal .kc-cov-normal-title a { color: #f7e7ce; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-card-bento { background: #1a1612; border: 1px solid rgba(212, 175, 55, 0.3); }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-card-bento .kc-bento-title a { color: #f7e7ce; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-bento-metric-chip { background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); color: #ffd700; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-bento-leader-box { background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.2); color: #ffd700; }
		.<?php echo $uid; ?>-wrap.theme-luxury_gold .kc-cov-nav-btn { background: rgba(212, 175, 55, 0.15); border: 1px solid rgba(212, 175, 55, 0.35); color: #ffd700; }

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

		/* ─── SWIPER CONTAINER & SLIDES ─── */
		.<?php echo $uid; ?>-container {
			width: 100%;
			padding: 25px 0 40px;
			overflow: visible !important;
			perspective: 1200px;
		}
		.<?php echo $uid; ?> .swiper-wrapper {
			align-items: center;
			display: flex;
			transform-style: preserve-3d;
		}
		.<?php echo $uid; ?> .kc-cov-slide {
			width: 310px;
			max-width: 310px;
			flex-shrink: 0;
			cursor: pointer;
			text-decoration: none;
			box-sizing: border-box;
			transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.3, 1), opacity 0.5s ease;
			will-change: transform, opacity;
			transform-style: preserve-3d;
			padding: 10px;
		}

		/* Card Base Shell */
		.<?php echo $uid; ?> .kc-cov-card {
			position: relative;
			border-radius: 24px;
			overflow: hidden;
			transition: transform 0.4s cubic-bezier(0.2, 0.9, 0.3, 1), box-shadow 0.4s ease, border-color 0.4s ease;
			display: flex;
			flex-direction: column;
			box-sizing: border-box;
			width: 100%;
			box-shadow: 0 12px 35px -5px rgba(0, 0, 0, 0.08);
		}

		/* Active Center Slide Elevation */
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card {
			transform: scale(1.05) translateZ(25px);
			border-color: rgba(254, 2, 91, 0.5) !important;
			box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18), 0 0 25px rgba(254, 2, 91, 0.25) !important;
			z-index: 10;
		}
		.<?php echo $uid; ?> .kc-cov-slide:not(.swiper-slide-active) {
			opacity: 0.78;
		}
		.<?php echo $uid; ?> .kc-cov-slide:not(.swiper-slide-active):hover {
			opacity: 0.95;
		}

		/* ─────────────────────────────────────────────────────────────
		   LAYOUT 1: OVERLAY CARD (Full Bleed Artwork with Bottom Scrim)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-overlay {
			position: relative;
			height: <?php echo intval( $card_height ); ?>px;
			min-height: <?php echo intval( $card_height ); ?>px;
			background: #0f172a;
			color: #ffffff;
			border: 1px solid rgba(255, 255, 255, 0.14);
		}
		.<?php echo $uid; ?> .kc-cov-overlay-bg {
			position: absolute;
			inset: 0;
			overflow: hidden;
		}
		.<?php echo $uid; ?> .kc-cov-bg-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform 0.7s cubic-bezier(0.2, 0.9, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-card-overlay:hover .kc-cov-bg-img {
			transform: scale(1.07);
		}
		.<?php echo $uid; ?> .kc-cov-gradient-scrim {
			position: absolute;
			inset: 0;
			background: linear-gradient(180deg, rgba(0, 0, 0, 0.35) 0%, rgba(0, 0, 0, 0.05) 35%, rgba(0, 0, 0, 0.75) 70%, rgba(0, 0, 0, 0.95) 100%);
			pointer-events: none;
		}

		/* Floating Top Badges */
		.<?php echo $uid; ?> .kc-cov-top-chips {
			position: absolute;
			top: 14px;
			right: 14px;
			display: flex;
			align-items: center;
			gap: 6px;
			z-index: 5;
		}
		.<?php echo $uid; ?> .kc-cov-chip-platform {
			background: rgba(15, 23, 42, 0.75);
			backdrop-filter: blur(10px);
			-webkit-backdrop-filter: blur(10px);
			color: #ffffff;
			font-size: 11px;
			font-weight: 800;
			padding: 4px 10px;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.2);
		}
		.<?php echo $uid; ?> .kc-cov-chip-count {
			background: #fe025b;
			color: #ffffff;
			font-size: 10px;
			font-weight: 900;
			padding: 4px 8px;
			border-radius: 20px;
			box-shadow: 0 2px 8px rgba(254, 2, 91, 0.4);
		}

		/* Center Play Cue */
		.<?php echo $uid; ?> .kc-cov-center-cue {
			position: absolute;
			inset: 0;
			margin: auto;
			width: 54px;
			height: 54px;
			border-radius: 50%;
			background: rgba(254, 2, 91, 0.95);
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 0;
			transform: scale(0.65);
			transition: all 0.35s cubic-bezier(0.2, 0.9, 0.3, 1);
			box-shadow: 0 8px 25px rgba(254, 2, 91, 0.5);
			z-index: 6;
			pointer-events: none;
		}
		.<?php echo $uid; ?> .kc-cov-center-cue svg { margin-left: 2px; }
		.<?php echo $uid; ?> .kc-card-overlay:hover .kc-cov-center-cue {
			opacity: 1;
			transform: scale(1);
		}

		/* Overlay Content */
		.<?php echo $uid; ?> .kc-cov-overlay-content {
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			padding: 22px 20px 20px;
			z-index: 5;
			display: flex;
			flex-direction: column;
			gap: 12px;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-cov-overlay-title {
			font-size: 21px;
			font-weight: 900;
			margin: 0;
			line-height: 1.25;
			text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
		}
		.<?php echo $uid; ?> .kc-cov-overlay-title a { color: #ffffff; text-decoration: none; }

		/* Overlay #1 Song Pill */
		.<?php echo $uid; ?> .kc-cov-overlay-song-pill {
			display: flex;
			align-items: center;
			gap: 10px;
			background: rgba(255, 255, 255, 0.12);
			backdrop-filter: blur(12px);
			-webkit-backdrop-filter: blur(12px);
			border: 1px solid rgba(255, 255, 255, 0.18);
			border-radius: 12px;
			padding: 8px 12px;
		}
		.<?php echo $uid; ?> .kc-cov-overlay-rank {
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
		}
		.<?php echo $uid; ?> .kc-cov-overlay-song-info {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-cov-overlay-song-name {
			font-size: 12px;
			font-weight: 800;
			color: #ffffff;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-cov-overlay-artist-name {
			font-size: 11px;
			font-weight: 600;
			color: rgba(255, 255, 255, 0.7);
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Overlay Button */
		.<?php echo $uid; ?> .kc-cov-overlay-btn {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			padding: 10px 16px;
			border-radius: 12px;
			background: #fe025b;
			color: #ffffff;
			font-size: 12px;
			font-weight: 800;
			text-decoration: none;
			transition: all 0.25s ease;
			box-shadow: 0 4px 15px rgba(254, 2, 91, 0.4);
		}
		.<?php echo $uid; ?> .kc-cov-overlay-btn:hover {
			background: #e10250;
			transform: translateY(-2px);
		}


		/* ─────────────────────────────────────────────────────────────
		   LAYOUT 2: NORMAL CLASSIC CARD (Artwork Top + Details Bottom)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-normal {
			display: flex;
			flex-direction: column;
			border-radius: 24px;
		}
		.<?php echo $uid; ?> .kc-cov-normal-art {
			position: relative;
			width: 100%;
			aspect-ratio: 16 / 11;
			overflow: hidden;
			background: #0f172a;
			display: block;
		}
		.<?php echo $uid; ?> .kc-cov-normal-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform 0.6s ease;
		}
		.<?php echo $uid; ?> .kc-card-normal:hover .kc-cov-normal-img {
			transform: scale(1.06);
		}
		.<?php echo $uid; ?> .kc-cov-normal-body {
			padding: 18px 18px 16px;
			display: flex;
			flex-direction: column;
			gap: 12px;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-cov-normal-title {
			font-size: 18px;
			font-weight: 900;
			margin: 0;
			line-height: 1.3;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-cov-normal-title a { text-decoration: none; }
		.<?php echo $uid; ?> .kc-cov-normal-teaser {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 8px 12px;
			border-radius: 12px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
		}
		.<?php echo $uid; ?> .kc-cov-normal-rank {
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
		}
		.<?php echo $uid; ?> .kc-cov-normal-teaser-info {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-cov-normal-teaser-song {
			font-size: 12px;
			font-weight: 800;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-cov-normal-teaser-artist {
			font-size: 11px;
			font-weight: 600;
			color: #64748b;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-cov-normal-btn {
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
		}
		.<?php echo $uid; ?> .kc-card-normal:hover .kc-cov-normal-btn,
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-normal-btn {
			background: #fe025b;
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(254, 2, 91, 0.35);
		}


		/* ─────────────────────────────────────────────────────────────
		   LAYOUT 3: BENTO STYLE (Modular Chips, Spotlight, Smart Data)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-bento {
			padding: 16px;
			display: flex;
			flex-direction: column;
			gap: 14px;
			border-radius: 24px;
		}
		.<?php echo $uid; ?> .kc-bento-hero {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.<?php echo $uid; ?> .kc-bento-thumb-frame {
			width: 64px;
			height: 64px;
			border-radius: 16px;
			overflow: hidden;
			flex-shrink: 0;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
			background: #0f172a;
		}
		.<?php echo $uid; ?> .kc-bento-thumb {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-card-bento:hover .kc-bento-thumb { transform: scale(1.08); }
		.<?php echo $uid; ?> .kc-bento-hero-meta {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-bento-tag {
			font-size: 10px;
			font-weight: 800;
			color: #fe025b;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 2px;
		}
		.<?php echo $uid; ?> .kc-bento-title {
			font-size: 17px;
			font-weight: 900;
			margin: 0;
			line-height: 1.25;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-bento-title a { text-decoration: none; }

		/* Bento Metric Chips */
		.<?php echo $uid; ?> .kc-bento-chips-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 8px;
		}
		.<?php echo $uid; ?> .kc-bento-metric-chip {
			padding: 8px 6px;
			border-radius: 12px;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-align: center;
			gap: 2px;
		}
		.<?php echo $uid; ?> .kc-bento-chip-label {
			font-size: 10px;
			color: #94a3b8;
			font-weight: 700;
		}
		.<?php echo $uid; ?> .kc-bento-chip-val {
			font-size: 11px;
			font-weight: 900;
			white-space: nowrap;
		}

		/* Bento Leader Box */
		.<?php echo $uid; ?> .kc-bento-leader-box {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 10px 12px;
			border-radius: 14px;
		}
		.<?php echo $uid; ?> .kc-bento-leader-rank {
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
		}
		.<?php echo $uid; ?> .kc-bento-leader-thumb {
			width: 32px;
			height: 32px;
			border-radius: 8px;
			object-fit: cover;
			flex-shrink: 0;
		}
		.<?php echo $uid; ?> .kc-bento-leader-info {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-bento-leader-name {
			font-size: 12px;
			font-weight: 800;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?> .kc-bento-leader-artist {
			font-size: 11px;
			color: #64748b;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Bento Button */
		.<?php echo $uid; ?> .kc-bento-btn {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			padding: 10px 16px;
			border-radius: 12px;
			background: #fe025b;
			color: #ffffff;
			font-size: 12px;
			font-weight: 800;
			text-decoration: none;
			transition: all 0.25s ease;
			box-shadow: 0 4px 14px rgba(254, 2, 91, 0.35);
			box-sizing: border-box;
		}
		.<?php echo $uid; ?> .kc-bento-btn:hover {
			background: #e10250;
			transform: translateY(-2px);
		}


		/* ─── CONTROLS & NAVIGATION ─── */
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
		.<?php echo $uid; ?> .kc-cov-nav-btn:hover { transform: scale(1.1); }
		.<?php echo $uid; ?> .kc-cov-nav-btn:active { transform: scale(0.95); }

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
			.<?php echo $uid; ?> .kc-cov-slide { width: 270px; max-width: 270px; }
		}
		@media (max-width: 640px) {
			.<?php echo $uid; ?>-wrap { padding: 18px 6px 28px; }
			.<?php echo $uid; ?>-title { font-size: 22px; }
			.<?php echo $uid; ?>-subtitle { font-size: 13px; }
			.<?php echo $uid; ?>-container { padding: 12px 0 20px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 250px; max-width: 250px; padding: 6px; }
			.<?php echo $uid; ?> .kc-card-overlay { height: 380px; min-height: 380px; }
			.<?php echo $uid; ?> .kc-cov-nav-btn { width: 38px; height: 38px; }
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-widget-wrap theme-<?php echo esc_attr( $theme ); ?> layout-<?php echo esc_attr( $card_layout ); ?>">
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
						<?php if ( $card_layout === 'overlaycard' ) : ?>
							<!-- ─── 1. OVERLAY CARD ─── -->
							<div class="kc-cov-card kc-card-overlay">
								<div class="kc-cov-overlay-bg">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-cov-bg-img" loading="lazy">
									<div class="kc-cov-gradient-scrim"></div>
								</div>

								<div class="kc-cov-top-chips">
									<span class="kc-cov-chip-platform"><?php echo esc_html( $def->platform_display ?? 'شارت أسبوعي' ); ?></span>
									<?php if ( ! empty( $def->item_count ) && $def->item_count > 0 ) : ?>
									<span class="kc-cov-chip-count">TOP <?php echo intval( $def->item_count ); ?></span>
									<?php endif; ?>
								</div>

								<div class="kc-cov-center-cue">
									<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
								</div>

								<div class="kc-cov-overlay-content" dir="rtl">
									<h3 class="kc-cov-overlay-title">
										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
									</h3>

									<?php if ( $show_top_song && $top_track && $track_title ) : ?>
									<div class="kc-cov-overlay-song-pill">
										<span class="kc-cov-overlay-rank">#1</span>
										<div class="kc-cov-overlay-song-info">
											<span class="kc-cov-overlay-song-name"><?php echo esc_html( $track_title ); ?></span>
											<?php if ( $track_artist ) : ?><span class="kc-cov-overlay-artist-name"><?php echo esc_html( $track_artist ); ?></span><?php endif; ?>
										</div>
									</div>
									<?php endif; ?>

									<div class="kc-cov-overlay-footer">
										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-overlay-btn">
											<span><?php esc_html_e( 'عرض السباق كاملاً', 'charts' ); ?></span>
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
										</a>
									</div>
								</div>
							</div>

						<?php elseif ( $card_layout === 'bento' ) : ?>
							<!-- ─── 2. BENTO STYLE CARD ─── -->
							<div class="kc-cov-card kc-card-bento">
								<div class="kc-bento-hero" dir="rtl">
									<div class="kc-bento-thumb-frame">
										<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-bento-thumb" loading="lazy">
									</div>
									<div class="kc-bento-hero-meta">
										<div class="kc-bento-tag"><?php echo esc_html( $def->platform_display ?? 'قائمة رسمية' ); ?></div>
										<h3 class="kc-bento-title">
											<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
										</h3>
									</div>
								</div>

								<div class="kc-bento-chips-grid" dir="rtl">
									<div class="kc-bento-metric-chip">
										<span class="kc-bento-chip-label"><?php esc_html_e( 'العمق', 'charts' ); ?></span>
										<span class="kc-bento-chip-val"><?php echo ( ! empty( $def->item_count ) && $def->item_count > 0 ) ? intval( $def->item_count ) . ' مركز' : 'شارت كامل'; ?></span>
									</div>
									<div class="kc-bento-metric-chip">
										<span class="kc-bento-chip-label"><?php esc_html_e( 'التحديث', 'charts' ); ?></span>
										<span class="kc-bento-chip-val"><?php echo esc_html( $def->frequency ?? 'أسبوعي' ); ?></span>
									</div>
									<div class="kc-bento-metric-chip">
										<span class="kc-bento-chip-label"><?php esc_html_e( 'المنصة', 'charts' ); ?></span>
										<span class="kc-bento-chip-val"><?php echo esc_html( ! empty( $def->platform ) && $def->platform !== 'all' ? ucfirst( $def->platform ) : 'Global' ); ?></span>
									</div>
								</div>

								<?php if ( $top_track && $track_title ) : ?>
								<div class="kc-bento-leader-box" dir="rtl">
									<div class="kc-bento-leader-rank">#1</div>
									<?php if ( ! empty( $top_track->resolved_image ) ) : ?>
									<img src="<?php echo esc_url( $top_track->resolved_image ); ?>" class="kc-bento-leader-thumb" alt="" loading="lazy">
									<?php endif; ?>
									<div class="kc-bento-leader-info">
										<div class="kc-bento-leader-name"><?php echo esc_html( $track_title ); ?></div>
										<?php if ( $track_artist ) : ?><div class="kc-bento-leader-artist"><?php echo esc_html( $track_artist ); ?></div><?php endif; ?>
									</div>
								</div>
								<?php endif; ?>

								<div class="kc-bento-foot" dir="rtl">
									<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-bento-btn">
										<span><?php esc_html_e( 'استعراض الترتيب الكامل', 'charts' ); ?></span>
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
									</a>
								</div>
							</div>

						<?php else : ?>
							<!-- ─── 3. NORMAL CLASSIC CARD ─── -->
							<div class="kc-cov-card kc-card-normal">
								<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-normal-art">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-cov-normal-img" loading="lazy">
									<div class="kc-cov-top-chips">
										<span class="kc-cov-chip-platform"><?php echo esc_html( $def->platform_display ?? 'شارت أسبوعي' ); ?></span>
										<?php if ( ! empty( $def->item_count ) && $def->item_count > 0 ) : ?>
										<span class="kc-cov-chip-count">TOP <?php echo intval( $def->item_count ); ?></span>
										<?php endif; ?>
									</div>
								</a>

								<div class="kc-cov-normal-body" dir="rtl">
									<h3 class="kc-cov-normal-title">
										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
									</h3>

									<?php if ( $show_top_song && $top_track && $track_title ) : ?>
									<div class="kc-cov-normal-teaser">
										<span class="kc-cov-normal-rank">#1</span>
										<div class="kc-cov-normal-teaser-info">
											<span class="kc-cov-normal-teaser-song"><?php echo esc_html( $track_title ); ?></span>
											<?php if ( $track_artist ) : ?><span class="kc-cov-normal-teaser-artist"><?php echo esc_html( $track_artist ); ?></span><?php endif; ?>
										</div>
									</div>
									<?php else : ?>
									<div class="kc-cov-normal-meta">
										<span class="kc-cov-dot-live"></span>
										<span><?php esc_html_e( 'محدث ببيانات حية', 'charts' ); ?></span>
									</div>
									<?php endif; ?>

									<div class="kc-cov-normal-action">
										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-normal-btn">
											<span><?php esc_html_e( 'عرض الشارت', 'charts' ); ?></span>
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
										</a>
									</div>
								</div>
							</div>
						<?php endif; ?>
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
					}, 50);
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
						speed: <?php echo intval( $settings['transition_speed'] ?? 600 ); ?>,
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

					if (animPreset === "coverflow_3d") {
						swiperConfig.effect = "coverflow";
						swiperConfig.coverflowEffect = {
							rotate: <?php echo intval( $settings['rotate_angle']['size'] ?? 18 ); ?>,
							stretch: 0,
							depth: <?php echo intval( $settings['depth_amount']['size'] ?? 100 ); ?>,
							modifier: 1,
							slideShadows: false
						};
						swiperConfig.spaceBetween = 20;
					} else {
						swiperConfig.effect = "slide";
						swiperConfig.spaceBetween = 24;
					}

					new Swiper(container, swiperConfig);
				});
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initCoverflow_<?php echo $uid_safe; ?>);
			} else {
				initCoverflow_<?php echo $uid_safe; ?>();
			}

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
