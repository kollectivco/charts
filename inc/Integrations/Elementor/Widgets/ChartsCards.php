<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Charts : Cards
 * Ultra-clean, modern cover artwork carousel with interactive animated backing graphics on hover.
 * Exactly matches the Billboard Arabia reference design.
 * Default theme is Light / Clean (Not dark).
 */
class ChartsCards extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name()       { return 'kc_charts_cards'; }
	public function get_title()      { return __( 'Charts : Cards', 'charts' ); }
	public function get_icon()       { return 'eicon-media-carousel'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {

		// ─── 1. CONTENT SETTINGS ───────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content & Layout', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
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
			'default'     => 'المزيد من قوائم بيلبورد عربية',
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

		$this->add_control( 'animation_style', [
			'label'   => __( 'Card Hover Background FX (أنيميشن الخلفية)', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'billboard_shapes',
			'options' => [
				'billboard_shapes' => __( 'Billboard Graphic Shapes (أشكال بيلبورد الهندسية الملونة - كما بالصورة)', 'charts' ),
				'glowing_aura'     => __( 'Animated Gradient Glow (توهج لوني متدرج ناعم يلف حول الكارت)', 'charts' ),
				'neon_frame'       => __( 'Kinetic Neon Frame (إطار نيون لوني متحرك)', 'charts' ),
				'smooth_lift'      => __( 'Minimal Lift & Shadow (بروز هادئ مع ظل سينمائي)', 'charts' ),
			],
		] );

		$this->add_control( 'max_charts', [
			'label'   => __( 'Max Charts to Display', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 3,
			'max'     => 40,
			'default' => 12,
		] );

		$this->add_control( 'link_target', [
			'label'   => __( 'Open in New Window', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'no',
		] );

		$this->end_controls_section();


		// ─── 2. CAROUSEL SETTINGS ──────────────────────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( 'Carousel Motion', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
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
			'default'      => 'no',
			'return_value' => 'yes',
		] );

		$this->end_controls_section();


		// ─── 3. STYLE: GENERAL & SIZING ────────────────────────────────
		$this->start_controls_section( 'style_general', [
			'label' => __( 'Cards & Sizing', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'card_size', [
			'label'          => __( 'Cover Artwork Size (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 180, 'max' => 450, 'step' => 5 ],
			],
			'default'        => [ 'size' => 280 ],
			'tablet_default' => [ 'size' => 240 ],
			'mobile_default' => [ 'size' => 210 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cd-slide' => 'width: {{SIZE}}px; max-width: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cd-art-box' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Artwork Corner Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 36 ] ],
			'default'    => [ 'size' => 14 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cd-art-box' => 'border-radius: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cd-img'     => 'border-radius: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_bg_type', [
			'label'   => __( 'Container Background Mode', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'transparent',
			'options' => [
				'transparent' => __( 'Adaptive Transparent (شفاف تماماً)', 'charts' ),
				'light'       => __( 'Light Modern (أبيض ناصع)', 'charts' ),
				'custom'      => __( 'Custom Color (لون مخصص)', 'charts' ),
			],
		] );

		$this->add_control( 'custom_container_bg', [
			'label'     => __( 'Custom Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => [ 'card_bg_type' => 'custom' ],
			'selectors' => [ '{{WRAPPER}} .kc-cd-wrap' => 'background-color: {{VALUE}} !important;' ],
		] );

		$this->add_responsive_control( 'section_padding', [
			'label'      => __( 'Section Padding', 'charts' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', '%' ],
			'default'    => [
				'top'      => 30,
				'right'    => 0,
				'bottom'   => 40,
				'left'     => 0,
				'unit'     => 'px',
				'isLinked' => false,
			],
			'selectors'  => [
				'{{WRAPPER}} .kc-cd-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();


		// ─── 4. STYLE: TYPOGRAPHY ──────────────────────────────────────
		$this->start_controls_section( 'style_typography', [
			'label' => __( 'Typography', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'header_title_color', [
			'label'     => __( 'Header Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [ '{{WRAPPER}} .kc-cd-header-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'header_title_typo',
			'selector' => '{{WRAPPER}} .kc-cd-header-title',
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Chart Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [
				'{{WRAPPER}} .kc-cd-name' => 'color: {{VALUE}};',
				'{{WRAPPER}} .kc-cd-name a' => 'color: {{VALUE}};',
			],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typo',
			'selector' => '{{WRAPPER}} .kc-cd-name',
		] );

		$this->end_controls_section();


		// ─── 5. STYLE: ARROWS & CONTROLS ───────────────────────────────
		$this->start_controls_section( 'style_arrows', [
			'label'     => __( 'Navigation Arrows', 'charts' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_arrows' => 'yes' ],
		] );

		$this->add_control( 'arrow_color', [
			'label'     => __( 'Arrow Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [ '{{WRAPPER}} .kc-cd-arrow' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'arrow_bg', [
			'label'     => __( 'Arrow Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .kc-cd-arrow' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'arrow_hover_bg', [
			'label'     => __( 'Arrow Hover Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [
				'{{WRAPPER}} .kc-cd-arrow:hover' => 'background-color: {{VALUE}}; color: #ffffff;',
			],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$uid = 'kc-cd-' . $this->get_id();
		$uid_safe = str_replace( '-', '_', $uid );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions( true );

		if ( empty( $defs ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding: 30px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 16px;">' . esc_html__( 'لا توجد سباقات أو قوائم منشورة حالياً.', 'charts' ) . '</div>';
			}
			return;
		}

		$max_charts = ! empty( $settings['max_charts'] ) ? intval( $settings['max_charts'] ) : 12;
		$charts = array_slice( $defs, 0, $max_charts );

		$anim_style    = $settings['animation_style'] ?? 'billboard_shapes';
		$target_attr   = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt  = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt      = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$bg_mode       = $settings['card_bg_type'] ?? 'transparent';

		// Multi-color palette for the Billboard graphic frame
		$palette_sets = [
			[ 'c1' => '#2563eb', 'c2' => '#10b981', 'c3' => '#f59e0b', 'c4' => '#8b5cf6', 'c5' => '#ef4444' ],
			[ 'c1' => '#ec4899', 'c2' => '#3b82f6', 'c3' => '#14b8a6', 'c4' => '#eab308', 'c5' => '#6366f1' ],
			[ 'c1' => '#06b6d4', 'c2' => '#f97316', 'c3' => '#84cc16', 'c4' => '#d946ef', 'c5' => '#3b82f6' ],
		];

		// Pre-resolve artwork
		$items = [];
		foreach ( $charts as $idx => $c ) {
			$entries = \Charts\Core\PublicIntegration::get_preview_entries( $c, 1 );
			$image = \Charts\Core\PublicIntegration::resolve_chart_image( $c, $entries );
			if ( empty( $image ) ) {
				$image = CHARTS_URL . 'public/assets/img/placeholder.png';
			}
			$palette = $palette_sets[ $idx % count( $palette_sets ) ];
			$items[] = [
				'def'     => $c,
				'url'     => home_url( '/charts/' . $c->slug . '/' ),
				'image'   => $image,
				'palette' => $palette,
			];
		}
		?>

		<style>
		/* ─── BASE CONTAINER (NOT DARK) ─── */
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			width: 100%;
			box-sizing: border-box;
			font-family: "Cairo", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			user-select: none;
			background: transparent;
			padding: 25px 0 40px;
			overflow: visible;
		}
		.<?php echo $uid; ?>-wrap.bg-light {
			background: #ffffff !important;
		}

		/* ─── HEADER ─── */
		.<?php echo $uid; ?>-header {
			margin-bottom: 30px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'center' ); ?>;
			padding: 0 16px;
		}
		.<?php echo $uid; ?>-header-title {
			font-size: 34px;
			font-weight: 900;
			margin: 0;
			line-height: 1.25;
			color: #0f172a;
			letter-spacing: -0.5px;
			font-family: "Cairo", sans-serif;
		}

		/* ─── SWIPER SLIDER SYSTEM ─── */
		.<?php echo $uid; ?>-slider-outer {
			position: relative;
			width: 100%;
			overflow: visible;
		}
		.<?php echo $uid; ?>-swiper {
			width: 100%;
			padding: 35px 0 35px !important;
			overflow: visible !important;
		}
		.<?php echo $uid; ?>-swiper .swiper-wrapper {
			align-items: center;
			display: flex;
			transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?>-slide {
			flex-shrink: 0;
			width: 280px;
			max-width: 280px;
			box-sizing: border-box;
			cursor: pointer;
			text-decoration: none;
			transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
			will-change: transform, opacity;
			padding: 15px 12px;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		/* Non-active slides subtle focus */
		.<?php echo $uid; ?>-slide:not(.swiper-slide-active) {
			opacity: 0.85;
			transform: scale(0.94);
		}
		.<?php echo $uid; ?>-slide:not(.swiper-slide-active):hover {
			opacity: 1;
			transform: scale(0.98);
		}

		/* ─── CARD STRUCTURE ─── */
		.<?php echo $uid; ?> .kc-cd-card {
			position: relative;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-decoration: none;
			width: 100%;
		}

		/* ─── ARTWORK STAGE & ANIMATED BACKGROUND ─── */
		.<?php echo $uid; ?> .kc-cd-stage {
			position: relative;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		/* Foreground Cover Box */
		.<?php echo $uid; ?> .kc-cd-art-box {
			position: relative;
			width: 280px;
			height: 280px;
			border-radius: 14px;
			overflow: hidden;
			z-index: 3;
			box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.15), 0 4px 10px rgba(0, 0, 0, 0.05);
			transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
			background: #e2e8f0;
		}
		.<?php echo $uid; ?> .kc-cd-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			border-radius: 14px;
			transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
		}

		/* ─── ANIMATION EFFECT 1: BILLBOARD GRAPHIC SHAPES (EXACT REFERENCE) ─── */
		.<?php echo $uid; ?> .kc-cd-fx-billboard {
			position: absolute;
			inset: -14px;
			z-index: 1;
			opacity: 0;
			transform: scale(0.88);
			transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
			pointer-events: none;
		}
		/* Shapes pads */
		.<?php echo $uid; ?> .kc-cd-pad-top-left {
			position: absolute;
			top: 0;
			left: 0;
			width: 32px;
			height: 48px;
			border-radius: 6px;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-cd-pad-top-mid {
			position: absolute;
			top: 0;
			left: 36px;
			right: 36px;
			height: 18px;
			border-radius: 4px;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-cd-pad-top-right {
			position: absolute;
			top: 0;
			right: 0;
			width: 38px;
			height: 38px;
			border-radius: 6px;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-cd-pad-bot-left {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 48px;
			height: 28px;
			border-radius: 6px;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-cd-pad-bot-right {
			position: absolute;
			bottom: 0;
			right: 0;
			width: 36px;
			height: 44px;
			border-radius: 6px;
			transition: transform 0.5s ease;
		}

		/* Hover & Active Center Slide Activation */
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cd-fx-billboard,
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-fx-billboard {
			opacity: 1;
			transform: scale(1);
			animation: kcBillboardFloat 3.5s ease-in-out infinite alternate;
		}
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cd-art-box,
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-art-box {
			transform: scale(1.05);
			box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
		}
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-img {
			transform: scale(1.04);
		}

		@keyframes kcBillboardFloat {
			0% {
				transform: scale(1) translateY(0);
			}
			50% {
				transform: scale(1.02) translateY(-3px);
			}
			100% {
				transform: scale(1) translateY(0);
			}
		}

		/* ─── ANIMATION EFFECT 2: GLOWING AURA ─── */
		.<?php echo $uid; ?> .kc-cd-fx-aura {
			position: absolute;
			inset: -16px;
			border-radius: 24px;
			background: conic-gradient(from 0deg, #2563eb, #10b981, #f59e0b, #ec4899, #8b5cf6, #2563eb);
			filter: blur(14px);
			opacity: 0;
			transform: scale(0.85);
			transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
			z-index: 1;
			pointer-events: none;
		}
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cd-fx-aura,
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-fx-aura {
			opacity: 0.85;
			transform: scale(1);
			animation: kcAuraRotate 5s linear infinite;
		}
		@keyframes kcAuraRotate {
			0% { filter: blur(14px) hue-rotate(0deg); }
			100% { filter: blur(14px) hue-rotate(360deg); }
		}

		/* ─── ANIMATION EFFECT 3: NEON FRAME ─── */
		.<?php echo $uid; ?> .kc-cd-fx-neon {
			position: absolute;
			inset: -8px;
			border-radius: 18px;
			border: 3px solid transparent;
			background: linear-gradient(135deg, #2563eb, #10b981, #f59e0b) border-box;
			-webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
			-webkit-mask-composite: xor;
			mask-composite: exclude;
			opacity: 0;
			transform: scale(0.92);
			transition: all 0.4s ease;
			z-index: 2;
			pointer-events: none;
		}
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cd-fx-neon,
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-fx-neon {
			opacity: 1;
			transform: scale(1);
		}

		/* ─── CHART TITLE BELOW COVER ─── */
		.<?php echo $uid; ?> .kc-cd-name {
			margin-top: 18px;
			font-size: 21px;
			font-weight: 900;
			color: #0f172a;
			text-align: center;
			line-height: 1.3;
			font-family: "Cairo", sans-serif;
			letter-spacing: -0.3px;
			width: 100%;
			transition: color 0.25s ease;
		}
		.<?php echo $uid; ?> .kc-cd-name a {
			color: inherit;
			text-decoration: none;
		}
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cd-name,
		.<?php echo $uid; ?> .kc-cd-card:hover .kc-cd-name {
			color: #000000;
		}

		/* ─── CONTROLS: CIRCULAR ARROWS ─── */
		.<?php echo $uid; ?>-controls-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 20px;
			margin-top: 15px;
		}
		.<?php echo $uid; ?> .kc-cd-arrow {
			width: 44px;
			height: 44px;
			border-radius: 50%;
			background: #ffffff;
			color: #0f172a;
			display: flex;
			align-items: center;
			justify-content: center;
			border: 1px solid #e2e8f0;
			cursor: pointer;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-cd-arrow:hover {
			background: #0f172a;
			color: #ffffff;
			border-color: #0f172a;
			transform: scale(1.08);
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
		}
		</style>

		<div class="<?php echo $uid; ?> <?php echo $uid; ?>-wrap kc-cd-wrap <?php echo ( $bg_mode === 'light' ) ? 'bg-light' : ''; ?>">

			<?php if ( $settings['show_header'] === 'yes' && ! empty( $settings['header_title'] ) ) : ?>
			<div class="<?php echo $uid; ?>-header">
				<h2 class="<?php echo $uid; ?>-header-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
			</div>
			<?php endif; ?>

			<div class="<?php echo $uid; ?>-slider-outer">
				<div class="swiper <?php echo $uid; ?>-swiper" id="<?php echo $uid; ?>-swiper" dir="ltr">
					<div class="swiper-wrapper">
						<?php foreach ( $items as $item ) :
							$def     = $item['def'];
							$url     = $item['url'];
							$image   = $item['image'];
							$palette = $item['palette'];
						?>
						<div class="swiper-slide <?php echo $uid; ?>-slide">
							<a href="<?php echo esc_url( $url ); ?>"<?php echo $target_attr; ?> class="kc-cd-card" dir="rtl">
								<div class="kc-cd-stage">

									<?php if ( $anim_style === 'billboard_shapes' ) : ?>
										<!-- Billboard Animated Graphic Background (Exact Reference Image) -->
										<div class="kc-cd-fx-billboard">
											<div class="kc-cd-pad-top-left" style="background-color: <?php echo esc_attr( $palette['c1'] ); ?>;"></div>
											<div class="kc-cd-pad-top-mid" style="background-color: <?php echo esc_attr( $palette['c5'] ); ?>;"></div>
											<div class="kc-cd-pad-top-right" style="background-color: <?php echo esc_attr( $palette['c2'] ); ?>;"></div>
											<div class="kc-cd-pad-bot-left" style="background-color: <?php echo esc_attr( $palette['c3'] ); ?>;"></div>
											<div class="kc-cd-pad-bot-right" style="background-color: <?php echo esc_attr( $palette['c4'] ); ?>;"></div>
										</div>
									<?php elseif ( $anim_style === 'glowing_aura' ) : ?>
										<!-- Animated Glowing Aura -->
										<div class="kc-cd-fx-aura"></div>
									<?php elseif ( $anim_style === 'neon_frame' ) : ?>
										<!-- Kinetic Neon Border Frame -->
										<div class="kc-cd-fx-neon"></div>
									<?php endif; ?>

									<!-- Foreground Artwork Box -->
									<div class="kc-cd-art-box">
										<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-cd-img" loading="lazy">
									</div>
								</div>

								<!-- Chart Title Below Artwork -->
								<div class="kc-cd-name">
									<?php echo esc_html( $def->title ); ?>
								</div>
							</a>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
				<div class="<?php echo $uid; ?>-controls-wrap">
					<button type="button" class="kc-cd-arrow <?php echo $uid; ?>-prev" aria-label="السابق">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					<button type="button" class="kc-cd-arrow <?php echo $uid; ?>-next" aria-label="التالي">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		(function() {
			function initCardsSlider_<?php echo $uid_safe; ?>() {
				var container = document.getElementById("<?php echo $uid; ?>-swiper");
				if (!container) return;

				if (typeof Swiper === "undefined") {
					if (!document.getElementById("kc-swiper-bundle-js")) {
						var s = document.createElement("script");
						s.id = "kc-swiper-bundle-js";
						s.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
						s.onload = function() { initCardsSlider_<?php echo $uid_safe; ?>(); };
						document.head.appendChild(s);
						var c = document.createElement("link");
						c.id = "kc-swiper-bundle-css";
						c.rel = "stylesheet";
						c.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
						document.head.appendChild(c);
					} else {
						setTimeout(initCardsSlider_<?php echo $uid_safe; ?>, 100);
					}
					return;
				}

				if (container.swiper) {
					try { container.swiper.destroy(true, true); } catch(e) {}
				}

				var config = {
					grabCursor: true,
					centeredSlides: true,
					slidesPerView: "auto",
					slideToClickedSlide: true,
					watchSlidesProgress: true,
					speed: 550,
					spaceBetween: 28,
					loop: <?php echo $loop_opt ? 'true' : 'false'; ?>,
					navigation: {
						nextEl: ".<?php echo $uid; ?>-next",
						prevEl: ".<?php echo $uid; ?>-prev"
					}
				};

				<?php if ( $autoplay_opt ) : ?>
				config.autoplay = {
					delay: <?php echo intval( $settings['autoplay_speed'] ?? 3500 ); ?>,
					disableOnInteraction: false,
					pauseOnMouseEnter: true
				};
				<?php endif; ?>

				new Swiper(container, config);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initCardsSlider_<?php echo $uid_safe; ?>);
			} else {
				setTimeout(initCardsSlider_<?php echo $uid_safe; ?>, 50);
			}

			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction("frontend/element_ready/kc_charts_cards.default", function() {
					setTimeout(initCardsSlider_<?php echo $uid_safe; ?>, 100);
				});
			}
		})();
		</script>
		<?php
	}
}
