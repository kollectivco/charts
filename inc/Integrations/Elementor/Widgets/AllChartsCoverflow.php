<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modern Editorial Carousel & Bento Showcase for All Charts.
 * Built to match the exact design system of DynamicShowcaseGrid & PremiumHeroSlider.
 */
class AllChartsCoverflow extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name()       { return 'kc_all_charts_coverflow'; }
	public function get_title()      { return __( 'Charts: All Charts Modern Carousel', 'charts' ); }
	public function get_icon()       { return 'eicon-media-carousel'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {

		// ─── 1. CONTENT SETTINGS ───────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content & Layout', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'card_layout', [
			'label'   => __( 'Card Style', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'overlaycard',
			'options' => [
				'overlaycard' => __( 'Overlay Poster (بوستر سينمائي كامل)', 'charts' ),
				'normal'      => __( 'Classic Modern (كارد كلاسيكي - غلاف بالأعلى وتفاصيل بالأسفل)', 'charts' ),
				'bento'       => __( 'Bento Box (بينتو عصري - تصميم منظم ببطاقات بيانات)', 'charts' ),
			],
		] );

		$this->add_control( 'theme_preset', [
			'label'   => __( 'Color Theme', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'transparent',
			'options' => [
				'transparent'  => __( 'Adaptive Transparent (شفاف يتكيف مع الصفحة)', 'charts' ),
				'dark_cinema'  => __( 'Dark Cinema (داكن فاخر)', 'charts' ),
				'light_modern' => __( 'Light Clean (أبيض عصري)', 'charts' ),
				'glass_blur'   => __( 'Glassmorphism (زجاجي شفاف)', 'charts' ),
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
			'default'     => 'أبرز سباقات الأغاني',
			'condition'   => [ 'show_header' => 'yes' ],
			'label_block' => true,
		] );

		$this->add_control( 'header_subtitle', [
			'label'       => __( 'Header Subtitle', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'استكشف الترتيب الأسبوعي وأحدث قوائم الموسيقى العربية والعالمية',
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
			'label'        => __( 'Show #1 Top Song Spotlight', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'button_label', [
			'label'       => __( 'Button Label', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'استعراض الترتيب',
			'label_block' => true,
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


		// ─── 2. CAROUSEL & MOTION SETTINGS ─────────────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( 'Carousel & Motion', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'animation_preset', [
			'label'   => __( 'Animation Effect', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'smooth_carousel',
			'options' => [
				'smooth_carousel' => __( 'Modern Smooth Focus (انسيابي مع تركيز وتكبير الكارد النشط)', 'charts' ),
				'coverflow_3d'    => __( 'Subtle 3D Tilt (دوران ثلاثي الأبعاد هادئ)', 'charts' ),
			],
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
			'max'         => 1500,
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


		// ─── 3. STYLE: GENERAL ─────────────────────────────────────────
		$this->start_controls_section( 'style_general', [
			'label' => __( 'General & Colors', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent_color', [
			'label'     => __( 'Accent Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#e11d48',
			'selectors' => [
				'{{WRAPPER}}' => '--kc-cov-accent: {{VALUE}};',
			],
		] );

		$this->add_responsive_control( 'card_width', [
			'label'          => __( 'Card Width (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 240, 'max' => 450, 'step' => 5 ],
			],
			'default'        => [ 'size' => 320 ],
			'tablet_default' => [ 'size' => 280 ],
			'mobile_default' => [ 'size' => 260 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cov-slide' => 'width: {{SIZE}}px; max-width: {{SIZE}}px;',
			],
		] );

		$this->add_responsive_control( 'card_height', [
			'label'          => __( 'Card Height (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 340, 'max' => 560, 'step' => 10 ],
			],
			'default'        => [ 'size' => 440 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-cov-card' => 'min-height: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cov-poster' => 'height: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Card Border Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 10, 'max' => 36 ] ],
			'default'    => [ 'size' => 20 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-card' => 'border-radius: {{SIZE}}px;',
			],
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .kc-cov-card',
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
			'selectors' => [ '{{WRAPPER}} .kc-cov-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'header_title_typo',
			'selector' => '{{WRAPPER}} .kc-cov-title',
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Card Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-card-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typo',
			'selector' => '{{WRAPPER}} .kc-card-title',
		] );

		$this->end_controls_section();


		// ─── 5. STYLE: NAVIGATION CONTROLS ─────────────────────────────
		$this->start_controls_section( 'style_navigation', [
			'label' => __( 'Navigation Arrows & Dots', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'arrow_color', [
			'label'     => __( 'Arrow Icon Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-cov-arrow' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'arrow_bg', [
			'label'     => __( 'Arrow Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-cov-arrow' => 'background-color: {{VALUE}};' ],
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
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding: 30px; text-align: center; color: #94a3b8; background: #0f172a; border-radius: 16px;">' . esc_html__( 'لا توجد سباقات أو قوائم منشورة حالياً.', 'charts' ) . '</div>';
			}
			return;
		}

		$max_charts = ! empty( $settings['max_charts'] ) ? intval( $settings['max_charts'] ) : 12;
		$charts = array_slice( $defs, 0, $max_charts );

		$layout        = $settings['card_layout'] ?? 'overlaycard';
		$theme         = $settings['theme_preset'] ?? 'transparent';
		$anim_preset   = $settings['animation_preset'] ?? 'smooth_carousel';
		$target_attr   = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt  = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt      = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$show_top_song = ( ! empty( $settings['show_top_song'] ) && $settings['show_top_song'] === 'yes' );
		$button_label  = ! empty( $settings['button_label'] ) ? $settings['button_label'] : 'استعراض الترتيب';
		$accent_color  = ! empty( $settings['accent_color'] ) ? $settings['accent_color'] : '#e11d48';

		// Pre-fetch #1 leader metadata for each chart
		$chart_data = [];
		foreach ( $charts as $c ) {
			$entries = \Charts\Core\PublicIntegration::get_preview_entries( $c, 1 );
			$image = \Charts\Core\PublicIntegration::resolve_chart_image( $c, $entries );
			if ( empty( $image ) ) {
				$image = CHARTS_URL . 'public/assets/img/placeholder.png';
			}

			$leader_title  = '';
			$leader_artist = '';
			$leader_image  = '';
			if ( ! empty( $entries[0] ) ) {
				$top = $entries[0];
				$resolved = \Charts\Core\PublicIntegration::resolve_display_name( $top, $c );
				$leader_title  = $resolved['title'] ?? ( $top->track_name ?? ( $top->item_name ?? '' ) );
				$leader_artist = $resolved['subtitle'] ?? ( $top->artist_names ?? '' );
				$leader_image  = ( ! empty( $top->resolved_image ) ? $top->resolved_image : ( $top->cover_image ?? '' ) );
			}

			$chart_data[] = [
				'def'           => $c,
				'url'           => home_url( '/charts/' . $c->slug . '/' ),
				'image'         => $image,
				'leader_title'  => $leader_title,
				'leader_artist' => $leader_artist,
				'leader_image'  => $leader_image,
				'platform'      => strtoupper( $c->platform ?? 'GLOBAL' ),
				'count'         => ( ! empty( $c->item_count ) && $c->item_count > 0 ) ? intval( $c->item_count ) : 50,
			];
		}
		?>

		<style>
		/* ─── BASE CONTAINER & RESET ─── */
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			width: 100%;
			box-sizing: border-box;
			font-family: "Cairo", "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
			user-select: none;
			--kc-cov-accent: <?php echo esc_attr( $accent_color ); ?>;
			background: transparent;
			padding: 20px 0;
		}

		/* ─── THEMES ─── */
		.<?php echo $uid; ?>-wrap.theme-transparent { background: transparent !important; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-transparent .kc-cov-subtitle { color: #64748b; }

		.<?php echo $uid; ?>-wrap.theme-dark_cinema { background: #090a0f !important; border-radius: 24px; padding: 40px 20px; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-title { color: #ffffff; }
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-subtitle { color: #94a3b8; }

		.<?php echo $uid; ?>-wrap.theme-light_modern { background: #f8fafc !important; border-radius: 24px; padding: 40px 20px; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-title { color: #0f172a; }
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-cov-subtitle { color: #64748b; }

		.<?php echo $uid; ?>-wrap.theme-glass_blur { background: rgba(255, 255, 255, 0.04) !important; backdrop-filter: blur(20px); border-radius: 24px; padding: 40px 20px; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-title { color: inherit; }
		.<?php echo $uid; ?>-wrap.theme-glass_blur .kc-cov-subtitle { color: #94a3b8; }

		/* ─── HEADER ─── */
		.<?php echo $uid; ?>-header {
			margin-bottom: 28px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'center' ); ?>;
			padding: 0 16px;
		}
		.<?php echo $uid; ?>-title {
			font-size: 32px;
			font-weight: 900;
			margin: 0 0 6px 0;
			letter-spacing: -0.5px;
			line-height: 1.25;
		}
		.<?php echo $uid; ?>-subtitle {
			font-size: 15px;
			margin: 0;
			font-weight: 500;
			line-height: 1.5;
		}

		/* ─── SWIPER SLIDER SYSTEM ─── */
		.<?php echo $uid; ?>-slider-outer {
			position: relative;
			width: 100%;
			overflow: visible;
		}
		.<?php echo $uid; ?>-swiper {
			width: 100%;
			padding: 25px 0 45px !important;
			overflow: visible !important;
		}
		.<?php echo $uid; ?>-swiper .swiper-wrapper {
			align-items: center;
			display: flex;
			transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?>-slide {
			flex-shrink: 0;
			width: 320px;
			max-width: 320px;
			box-sizing: border-box;
			cursor: pointer;
			text-decoration: none;
			transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
			will-change: transform, opacity;
			padding: 8px;
		}

		/* Smooth Slide Focus State */
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cov-card {
			transform: scale(1.05);
			box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.15) !important;
			z-index: 10;
		}
		.<?php echo $uid; ?>-slide:not(.swiper-slide-active) {
			opacity: 0.75;
			transform: scale(0.93);
		}
		.<?php echo $uid; ?>-slide:not(.swiper-slide-active):hover {
			opacity: 0.95;
			transform: scale(0.96);
		}

		/* ─── CARD BASE SHELL ─── */
		.<?php echo $uid; ?> .kc-cov-card {
			position: relative;
			border-radius: 20px;
			overflow: hidden;
			background: #0f172a;
			color: #ffffff;
			display: flex;
			flex-direction: column;
			box-sizing: border-box;
			width: 100%;
			transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
			box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.15);
		}

		/* ─────────────────────────────────────────────────────────────
		   STYLE 1: OVERLAY POSTER CARD (Full Bleed Artwork)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-overlay {
			height: 440px;
		}
		.<?php echo $uid; ?> .kc-cov-bg-art {
			position: absolute;
			inset: 0;
			background-size: cover;
			background-position: center;
			transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
			z-index: 1;
		}
		.<?php echo $uid; ?> .kc-card-overlay:hover .kc-cov-bg-art {
			transform: scale(1.06);
		}
		.<?php echo $uid; ?> .kc-cov-scrim {
			position: absolute;
			inset: 0;
			background: linear-gradient(to top, rgba(15, 23, 42, 0.96) 0%, rgba(15, 23, 42, 0.6) 42%, rgba(15, 23, 42, 0.15) 80%, transparent 100%);
			z-index: 2;
			pointer-events: none;
		}
		.<?php echo $uid; ?> .kc-cov-top-badges {
			position: absolute;
			top: 16px;
			right: 16px;
			left: 16px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			z-index: 3;
		}
		.<?php echo $uid; ?> .kc-cov-badge-platform {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(15, 23, 42, 0.65);
			backdrop-filter: blur(12px);
			-webkit-backdrop-filter: blur(12px);
			padding: 5px 12px;
			border-radius: 20px;
			font-size: 11px;
			font-weight: 800;
			color: #ffffff;
			letter-spacing: 0.5px;
			border: 1px solid rgba(255, 255, 255, 0.15);
		}
		.<?php echo $uid; ?> .kc-cov-badge-count {
			background: var(--kc-cov-accent);
			color: #ffffff;
			padding: 4px 10px;
			border-radius: 20px;
			font-size: 10px;
			font-weight: 900;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
		}
		.<?php echo $uid; ?> .kc-cov-overlay-body {
			position: absolute;
			bottom: 0;
			right: 0;
			left: 0;
			padding: 24px 20px 20px;
			z-index: 3;
			display: flex;
			flex-direction: column;
			gap: 12px;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-cov-overlay-title {
			font-size: 24px;
			font-weight: 900;
			margin: 0;
			line-height: 1.25;
			color: #ffffff;
			text-shadow: 0 2px 12px rgba(0, 0, 0, 0.6);
		}
		.<?php echo $uid; ?> .kc-cov-overlay-title a {
			color: #ffffff;
			text-decoration: none;
		}

		/* Leader Spotlight Bar */
		.<?php echo $uid; ?> .kc-cov-leader-row {
			display: flex;
			align-items: center;
			gap: 10px;
			background: rgba(255, 255, 255, 0.08);
			backdrop-filter: blur(10px);
			-webkit-backdrop-filter: blur(10px);
			border: 1px solid rgba(255, 255, 255, 0.12);
			border-radius: 12px;
			padding: 8px 12px;
		}
		.<?php echo $uid; ?> .kc-cov-leader-rank {
			width: 22px;
			height: 22px;
			border-radius: 6px;
			background: var(--kc-cov-accent);
			color: #ffffff;
			font-size: 11px;
			font-weight: 900;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
		}
		.<?php echo $uid; ?> .kc-cov-leader-thumb {
			width: 32px;
			height: 32px;
			border-radius: 8px;
			object-fit: cover;
			flex-shrink: 0;
		}
		.<?php echo $uid; ?> .kc-cov-leader-meta {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
		}
		.<?php echo $uid; ?> .kc-cov-leader-song {
			font-size: 13px;
			font-weight: 800;
			color: #ffffff;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			line-height: 1.2;
		}
		.<?php echo $uid; ?> .kc-cov-leader-artist {
			font-size: 11px;
			color: #94a3b8;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			margin-top: 2px;
		}

		/* Sleek Glass CTA Button */
		.<?php echo $uid; ?> .kc-cov-btn {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			padding: 10px 16px;
			border-radius: 12px;
			background: rgba(255, 255, 255, 0.16);
			backdrop-filter: blur(8px);
			-webkit-backdrop-filter: blur(8px);
			border: 1px solid rgba(255, 255, 255, 0.2);
			color: #ffffff;
			font-size: 13px;
			font-weight: 800;
			text-decoration: none;
			box-sizing: border-box;
			transition: all 0.25s ease;
		}
		.<?php echo $uid; ?> .kc-cov-btn:hover,
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-cov-btn {
			background: var(--kc-cov-accent);
			border-color: var(--kc-cov-accent);
			box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
		}

		/* ─────────────────────────────────────────────────────────────
		   STYLE 2: CLASSIC MODERN CARD (Split Image Top + Info Bottom)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-normal {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			color: #0f172a;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal {
			background: #111319;
			border-color: rgba(255, 255, 255, 0.08);
			color: #ffffff;
		}
		.<?php echo $uid; ?> .kc-cov-normal-cover {
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
			transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-card-normal:hover .kc-cov-normal-img {
			transform: scale(1.06);
		}
		.<?php echo $uid; ?> .kc-cov-normal-body {
			padding: 20px 18px 18px;
			display: flex;
			flex-direction: column;
			gap: 14px;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-cov-normal-title {
			font-size: 19px;
			font-weight: 900;
			margin: 0;
			line-height: 1.3;
		}
		.<?php echo $uid; ?> .kc-cov-normal-title a {
			color: inherit;
			text-decoration: none;
		}
		.<?php echo $uid; ?> .kc-card-normal .kc-cov-leader-row {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal .kc-cov-leader-row {
			background: rgba(255, 255, 255, 0.04);
			border-color: rgba(255, 255, 255, 0.08);
		}
		.<?php echo $uid; ?> .kc-card-normal .kc-cov-leader-song {
			color: #0f172a;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal .kc-cov-leader-song {
			color: #ffffff;
		}
		.<?php echo $uid; ?> .kc-card-normal .kc-cov-btn {
			background: #f1f5f9;
			color: #0f172a;
			border: 1px solid #e2e8f0;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-card-normal .kc-cov-btn {
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
			border-color: rgba(255, 255, 255, 0.12);
		}
		.<?php echo $uid; ?> .kc-card-normal .kc-cov-btn:hover,
		.<?php echo $uid; ?>-slide.swiper-slide-active .kc-card-normal .kc-cov-btn {
			background: var(--kc-cov-accent);
			border-color: var(--kc-cov-accent);
			color: #ffffff;
		}

		/* ─────────────────────────────────────────────────────────────
		   STYLE 3: BENTO BOX CARD (Sophisticated Modular Design)
		   ───────────────────────────────────────────────────────────── */
		.<?php echo $uid; ?> .kc-card-bento {
			padding: 20px;
			display: flex;
			flex-direction: column;
			gap: 16px;
			border: 1px solid rgba(255, 255, 255, 0.1);
			background: #11141c;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-card-bento {
			background: #ffffff;
			border-color: #e2e8f0;
			color: #0f172a;
		}
		.<?php echo $uid; ?> .kc-bento-hero {
			display: flex;
			align-items: center;
			gap: 14px;
		}
		.<?php echo $uid; ?> .kc-bento-thumb-frame {
			width: 68px;
			height: 68px;
			border-radius: 16px;
			overflow: hidden;
			flex-shrink: 0;
			box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
			background: #000;
		}
		.<?php echo $uid; ?> .kc-bento-thumb {
			width: 100%;
			height: 100%;
			object-fit: cover;
			transition: transform 0.5s ease;
		}
		.<?php echo $uid; ?> .kc-card-bento:hover .kc-bento-thumb {
			transform: scale(1.08);
		}
		.<?php echo $uid; ?> .kc-bento-meta {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-bento-tag {
			font-size: 11px;
			font-weight: 800;
			color: var(--kc-cov-accent);
			letter-spacing: 0.5px;
			margin-bottom: 2px;
		}
		.<?php echo $uid; ?> .kc-bento-title {
			font-size: 18px;
			font-weight: 900;
			margin: 0;
			line-height: 1.3;
		}
		.<?php echo $uid; ?> .kc-bento-title a {
			color: inherit;
			text-decoration: none;
		}

		/* Bento Pods */
		.<?php echo $uid; ?> .kc-bento-spotlight-pod {
			background: rgba(255, 255, 255, 0.05);
			border: 1px solid rgba(255, 255, 255, 0.08);
			border-radius: 14px;
			padding: 12px 14px;
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.<?php echo $uid; ?>-wrap.theme-light_modern .kc-bento-spotlight-pod {
			background: #f8fafc;
			border-color: #e2e8f0;
		}
		.<?php echo $uid; ?> .kc-bento-pod-label {
			display: flex;
			align-items: center;
			justify-content: space-between;
			font-size: 11px;
			font-weight: 700;
			color: #94a3b8;
		}
		.<?php echo $uid; ?> .kc-bento-pod-track {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.<?php echo $uid; ?> .kc-bento-pod-track-img {
			width: 36px;
			height: 36px;
			border-radius: 8px;
			object-fit: cover;
		}
		.<?php echo $uid; ?> .kc-bento-pod-track-info {
			display: flex;
			flex-direction: column;
			min-width: 0;
			flex: 1;
			text-align: right;
		}
		.<?php echo $uid; ?> .kc-bento-pod-song {
			font-size: 13px;
			font-weight: 800;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			color: inherit;
		}
		.<?php echo $uid; ?> .kc-bento-pod-artist {
			font-size: 11px;
			color: #94a3b8;
			font-weight: 600;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Bento Quick Stats */
		.<?php echo $uid; ?> .kc-bento-stats-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			font-size: 11px;
			color: #94a3b8;
			font-weight: 700;
			padding: 0 4px;
		}
		.<?php echo $uid; ?> .kc-bento-stat-dot {
			width: 6px;
			height: 6px;
			border-radius: 50%;
			background: #22c55e;
			display: inline-block;
			margin-left: 5px;
			box-shadow: 0 0 8px #22c55e;
		}

		/* ─── NAVIGATION CONTROLS ─── */
		.<?php echo $uid; ?>-controls-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 20px;
			margin-top: 10px;
		}
		.<?php echo $uid; ?> .kc-cov-arrow {
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
			transition: all 0.25s ease;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .kc-cov-arrow {
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
			border-color: rgba(255, 255, 255, 0.12);
		}
		.<?php echo $uid; ?> .kc-cov-arrow:hover {
			background: var(--kc-cov-accent);
			color: #ffffff;
			border-color: var(--kc-cov-accent);
			transform: scale(1.08);
			box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
		}
		.<?php echo $uid; ?> .swiper-pagination {
			position: static !important;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			width: auto !important;
		}
		.<?php echo $uid; ?> .swiper-pagination-bullet {
			width: 8px;
			height: 8px;
			border-radius: 4px;
			background: #cbd5e1;
			opacity: 0.6;
			transition: all 0.3s ease;
		}
		.<?php echo $uid; ?>-wrap.theme-dark_cinema .swiper-pagination-bullet {
			background: rgba(255, 255, 255, 0.3);
		}
		.<?php echo $uid; ?> .swiper-pagination-bullet-active {
			width: 24px;
			background: var(--kc-cov-accent) !important;
			opacity: 1;
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap theme-<?php echo esc_attr( $theme ); ?>">

			<?php if ( $settings['show_header'] === 'yes' && ! empty( $settings['header_title'] ) ) : ?>
			<div class="<?php echo $uid; ?>-header">
				<h2 class="<?php echo $uid; ?>-title kc-cov-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
				<?php if ( ! empty( $settings['header_subtitle'] ) ) : ?>
				<p class="<?php echo $uid; ?>-subtitle kc-cov-subtitle"><?php echo esc_html( $settings['header_subtitle'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="<?php echo $uid; ?>-slider-outer">
				<div class="swiper <?php echo $uid; ?>-swiper" id="<?php echo $uid; ?>-swiper" dir="ltr">
					<div class="swiper-wrapper">
						<?php foreach ( $chart_data as $item ) :
							$def          = $item['def'];
							$chart_url    = $item['url'];
							$image        = $item['image'];
							$leader_title = $item['leader_title'];
							$leader_artist= $item['leader_artist'];
							$leader_image = $item['leader_image'];
							$platform     = $item['platform'];
							$count        = $item['count'];
						?>
						<div class="swiper-slide <?php echo $uid; ?>-slide">

							<?php if ( $layout === 'overlaycard' ) : ?>
								<!-- ─── 1. OVERLAY POSTER CARD ─── -->
								<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-card kc-card-overlay">
									<div class="kc-cov-bg-art" style="background-image: url('<?php echo esc_url( $image ); ?>');"></div>
									<div class="kc-cov-scrim"></div>

									<div class="kc-cov-top-badges">
										<span class="kc-cov-badge-platform"><?php echo esc_html( $platform ); ?></span>
										<span class="kc-cov-badge-count">TOP <?php echo intval( $count ); ?></span>
									</div>

									<div class="kc-cov-overlay-body" dir="rtl">
										<h3 class="kc-cov-overlay-title kc-card-title"><?php echo esc_html( $def->title ); ?></h3>

										<?php if ( $show_top_song && $leader_title ) : ?>
										<div class="kc-cov-leader-row">
											<span class="kc-cov-leader-rank">#1</span>
											<?php if ( $leader_image ) : ?>
											<img src="<?php echo esc_url( $leader_image ); ?>" class="kc-cov-leader-thumb" alt="" loading="lazy">
											<?php endif; ?>
											<div class="kc-cov-leader-meta">
												<span class="kc-cov-leader-song"><?php echo esc_html( $leader_title ); ?></span>
												<?php if ( $leader_artist ) : ?>
												<span class="kc-cov-leader-artist"><?php echo esc_html( $leader_artist ); ?></span>
												<?php endif; ?>
											</div>
										</div>
										<?php endif; ?>

										<div class="kc-cov-btn">
											<span><?php echo esc_html( $button_label ); ?></span>
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
										</div>
									</div>
								</a>

							<?php elseif ( $layout === 'bento' ) : ?>
								<!-- ─── 2. BENTO BOX CARD ─── -->
								<div class="kc-cov-card kc-card-bento" dir="rtl">
									<div class="kc-bento-hero">
										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-bento-thumb-frame">
											<img src="<?php echo esc_url( $image ); ?>" class="kc-bento-thumb" alt="" loading="lazy">
										</a>
										<div class="kc-bento-meta">
											<span class="kc-bento-tag"><?php echo esc_html( $platform ); ?> • TOP <?php echo intval( $count ); ?></span>
											<h3 class="kc-bento-title kc-card-title">
												<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
											</h3>
										</div>
									</div>

									<?php if ( $show_top_song && $leader_title ) : ?>
									<div class="kc-bento-spotlight-pod">
										<div class="kc-bento-pod-label">
											<span>متصدر الترتيب الحالي</span>
											<span style="color:var(--kc-cov-accent); font-weight:900;">#1</span>
										</div>
										<div class="kc-bento-pod-track">
											<?php if ( $leader_image ) : ?>
											<img src="<?php echo esc_url( $leader_image ); ?>" class="kc-bento-pod-track-img" alt="" loading="lazy">
											<?php endif; ?>
											<div class="kc-bento-pod-track-info">
												<span class="kc-bento-pod-song"><?php echo esc_html( $leader_title ); ?></span>
												<?php if ( $leader_artist ) : ?>
												<span class="kc-bento-pod-artist"><?php echo esc_html( $leader_artist ); ?></span>
												<?php endif; ?>
											</div>
										</div>
									</div>
									<?php endif; ?>

									<div class="kc-bento-stats-row">
										<span><span class="kc-bento-stat-dot"></span>تحديث مباشر</span>
										<span><?php echo intval( $count ); ?> مركز</span>
									</div>

									<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-btn">
										<span><?php echo esc_html( $button_label ); ?></span>
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
									</a>
								</div>

							<?php else : ?>
								<!-- ─── 3. CLASSIC MODERN CARD ─── -->
								<div class="kc-cov-card kc-card-normal">
									<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-normal-cover">
										<img src="<?php echo esc_url( $image ); ?>" class="kc-cov-normal-img" alt="" loading="lazy">
										<div class="kc-cov-top-badges">
											<span class="kc-cov-badge-platform"><?php echo esc_html( $platform ); ?></span>
											<span class="kc-cov-badge-count">TOP <?php echo intval( $count ); ?></span>
										</div>
									</a>

									<div class="kc-cov-normal-body" dir="rtl">
										<h3 class="kc-cov-normal-title kc-card-title">
											<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?>><?php echo esc_html( $def->title ); ?></a>
										</h3>

										<?php if ( $show_top_song && $leader_title ) : ?>
										<div class="kc-cov-leader-row">
											<span class="kc-cov-leader-rank">#1</span>
											<?php if ( $leader_image ) : ?>
											<img src="<?php echo esc_url( $leader_image ); ?>" class="kc-cov-leader-thumb" alt="" loading="lazy">
											<?php endif; ?>
											<div class="kc-cov-leader-meta">
												<span class="kc-cov-leader-song"><?php echo esc_html( $leader_title ); ?></span>
												<?php if ( $leader_artist ) : ?>
												<span class="kc-cov-leader-artist"><?php echo esc_html( $leader_artist ); ?></span>
												<?php endif; ?>
											</div>
										</div>
										<?php endif; ?>

										<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-cov-btn">
											<span><?php echo esc_html( $button_label ); ?></span>
											<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
										</a>
									</div>
								</div>

							<?php endif; ?>

						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Navigation Controls -->
				<div class="<?php echo $uid; ?>-controls-wrap">
					<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
					<button type="button" class="kc-cov-arrow <?php echo $uid; ?>-prev" aria-label="السابق">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					<?php endif; ?>

					<?php if ( $settings['show_dots'] === 'yes' ) : ?>
					<div class="swiper-pagination <?php echo $uid; ?>-pagination"></div>
					<?php endif; ?>

					<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
					<button type="button" class="kc-cov-arrow <?php echo $uid; ?>-next" aria-label="التالي">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<script>
		(function() {
			function initSlider_<?php echo $uid_safe; ?>() {
				var container = document.getElementById("<?php echo $uid; ?>-swiper");
				if (!container) return;

				if (typeof Swiper === "undefined") {
					// Load Swiper if not available
					if (!document.getElementById("kc-swiper-bundle-js")) {
						var s = document.createElement("script");
						s.id = "kc-swiper-bundle-js";
						s.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
						s.onload = function() { initSlider_<?php echo $uid_safe; ?>(); };
						document.head.appendChild(s);
						var c = document.createElement("link");
						c.id = "kc-swiper-bundle-css";
						c.rel = "stylesheet";
						c.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
						document.head.appendChild(c);
					} else {
						setTimeout(initSlider_<?php echo $uid_safe; ?>, 100);
					}
					return;
				}

				if (container.swiper) {
					try { container.swiper.destroy(true, true); } catch(e) {}
				}

				var animPreset = "<?php echo esc_js( $anim_preset ); ?>";
				var config = {
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
				config.autoplay = {
					delay: <?php echo intval( $settings['autoplay_speed'] ?? 4000 ); ?>,
					disableOnInteraction: false,
					pauseOnMouseEnter: <?php echo ( ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
				};
				<?php endif; ?>

				if (animPreset === "coverflow_3d") {
					config.effect = "coverflow";
					config.coverflowEffect = {
						rotate: 15,
						stretch: 0,
						depth: 80,
						modifier: 1,
						slideShadows: false
					};
					config.spaceBetween = 16;
				} else {
					config.effect = "slide";
					config.spaceBetween = 20;
				}

				new Swiper(container, config);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initSlider_<?php echo $uid_safe; ?>);
			} else {
				setTimeout(initSlider_<?php echo $uid_safe; ?>, 50);
			}

			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction("frontend/element_ready/kc_all_charts_coverflow.default", function() {
					setTimeout(initSlider_<?php echo $uid_safe; ?>, 100);
				});
			}
		})();
		</script>
		<?php
	}
}
