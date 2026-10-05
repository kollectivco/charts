<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Billboard Cover Showcase Carousel.
 * Minimalist, high-fashion cover carousel with ambient animated background effects.
 */
class AllChartsCoverflow extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name()       { return 'kc_all_charts_coverflow'; }
	public function get_title()      { return __( 'Charts: Billboard Cover Showcase', 'charts' ); }
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
			'default'   => 'right',
			'condition' => [ 'show_header' => 'yes' ],
		] );

		$this->add_control( 'hover_animation_style', [
			'label'   => __( 'Hover Background Animation (أنيميشن الخلفية حول الكارد)', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'billboard_aura',
			'options' => [
				'billboard_aura' => __( 'Billboard Multi-Color Aura (هالة بيلبورد الملونة المتحركة - مثل الصورة)', 'charts' ),
				'glow_pulse'     => __( 'Vibrant Gradient Glow (توهج لوني متدرج ناعم)', 'charts' ),
				'electric_frame' => __( 'Animated Dynamic Border (إطار لوني متحرك)', 'charts' ),
				'subtle_shadow'  => __( 'Minimal Lift Shadow (ظل ناعم فقط بدون ألوان)', 'charts' ),
			],
		] );

		$this->add_responsive_control( 'card_size', [
			'label'          => __( 'Cover Size (px)', 'charts' ),
			'type'           => Controls_Manager::SLIDER,
			'range'          => [
				'px' => [ 'min' => 180, 'max' => 450, 'step' => 5 ],
			],
			'default'        => [ 'size' => 280 ],
			'tablet_default' => [ 'size' => 240 ],
			'mobile_default' => [ 'size' => 210 ],
			'selectors'      => [
				'{{WRAPPER}} .kc-bbc-slide' => 'width: {{SIZE}}px !important; max-width: {{SIZE}}px !important;',
				'{{WRAPPER}} .kc-bbc-cover-box' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'cover_radius', [
			'label'      => __( 'Cover Border Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'    => [ 'size' => 18 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-bbc-cover-box' => 'border-radius: {{SIZE}}px;',
				'{{WRAPPER}} .kc-bbc-aura-layer' => 'border-radius: calc({{SIZE}}px + 6px);',
			],
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


		// ─── 2. CAROUSEL & MOTION SETTINGS ─────────────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( 'Carousel Motion & Behavior', 'charts' ),
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
			'default'   => 4000,
			'min'       => 1500,
			'max'       => 10000,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'transition_speed', [
			'label'       => __( 'Slide Speed (ms)', 'charts' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 650,
			'min'         => 300,
			'max'         => 1500,
		] );

		$this->add_control( 'loop', [
			'label'        => __( 'Infinite Loop', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'pause_on_hover', [
			'label'        => __( 'Pause on Hover', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'condition'    => [ 'autoplay' => 'yes' ],
		] );

		$this->add_control( 'show_arrows', [
			'label'        => __( 'Show Navigation Arrows', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->end_controls_section();


		// ─── 3. STYLE: COLORS & TYPOGRAPHY ─────────────────────────────
		$this->start_controls_section( 'style_typography', [
			'label' => __( 'Colors & Typography', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'header_color', [
			'label'     => __( 'Header Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [ '{{WRAPPER}} .kc-bbc-header-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'header_typo',
			'selector' => '{{WRAPPER}} .kc-bbc-header-title',
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Chart Title Color (تحت الكارت)', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#0f172a',
			'selectors' => [ '{{WRAPPER}} .kc-bbc-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typo',
			'selector' => '{{WRAPPER}} .kc-bbc-title',
		] );

		$this->add_control( 'active_title_color', [
			'label'     => __( 'Active / Hover Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#000000',
			'selectors' => [
				'{{WRAPPER}} .kc-bbc-slide.swiper-slide-active .kc-bbc-title' => 'color: {{VALUE}};',
				'{{WRAPPER}} .kc-bbc-card:hover .kc-bbc-title'               => 'color: {{VALUE}};',
			],
		] );

		$this->add_control( 'arrow_color', [
			'label'     => __( 'Navigation Arrows Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-bbc-nav-arrow' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'arrow_bg', [
			'label'     => __( 'Navigation Arrows Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-bbc-nav-arrow' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$uid = 'kc-bbc-' . $this->get_id();
		$uid_safe = str_replace( '-', '_', $uid );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions( true );

		if ( empty( $defs ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding: 30px; text-align: center; color: #64748b; background: transparent;">' . esc_html__( 'لا توجد سباقات أو قوائم منشورة حالياً.', 'charts' ) . '</div>';
			}
			return;
		}

		$max_charts   = ! empty( $settings['max_charts'] ) ? intval( $settings['max_charts'] ) : 15;
		$charts       = array_slice( $defs, 0, $max_charts );
		$anim_style   = $settings['hover_animation_style'] ?? 'billboard_aura';
		$target_attr  = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt     = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		?>

		<style>
		/* ─── BASE CAROUSEL RESET ─── */
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			width: 100%;
			box-sizing: border-box;
			font-family: "Cairo", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			user-select: none;
			background: transparent !important;
			padding: 10px 0 30px;
			overflow: visible;
		}

		/* ─── HEADER ─── */
		.<?php echo $uid; ?>-header {
			margin-bottom: 24px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'right' ); ?>;
			padding: 0 10px;
		}
		.<?php echo $uid; ?> .kc-bbc-header-title {
			font-size: 32px;
			font-weight: 900;
			margin: 0;
			color: #0f172a;
			letter-spacing: -0.5px;
			line-height: 1.25;
			font-family: "Cairo", sans-serif;
		}

		/* ─── SWIPER CONTAINER ─── */
		.<?php echo $uid; ?>-slider {
			width: 100%;
			padding: 35px 0 35px !important;
			overflow: visible !important;
		}
		.<?php echo $uid; ?>-slider .swiper-wrapper {
			align-items: center;
			display: flex;
			transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-bbc-slide {
			flex-shrink: 0;
			width: 280px;
			max-width: 280px;
			box-sizing: border-box;
			cursor: pointer;
			text-decoration: none;
			transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
			will-change: transform, opacity;
			padding: 15px 10px;
		}

		/* Active Center Elevation */
		.<?php echo $uid; ?> .kc-bbc-slide.swiper-slide-active {
			transform: scale(1.08);
			z-index: 10;
			opacity: 1;
		}
		.<?php echo $uid; ?> .kc-bbc-slide:not(.swiper-slide-active) {
			opacity: 0.72;
			transform: scale(0.90);
		}
		.<?php echo $uid; ?> .kc-bbc-slide:not(.swiper-slide-active):hover {
			opacity: 0.95;
			transform: scale(0.95);
		}

		/* ─── CARD STRUCTURE ─── */
		.<?php echo $uid; ?> .kc-bbc-card {
			position: relative;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-decoration: none;
			outline: none;
		}

		/* The Frame around the Cover */
		.<?php echo $uid; ?> .kc-bbc-frame {
			position: relative;
			display: inline-block;
			border-radius: 20px;
		}

		/* The Artwork Container */
		.<?php echo $uid; ?> .kc-bbc-cover-box {
			position: relative;
			width: 280px;
			height: 280px;
			aspect-ratio: 1 / 1;
			border-radius: 18px;
			overflow: hidden;
			background: #f1f5f9;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
			z-index: 2;
			transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
		}
		.<?php echo $uid; ?> .kc-bbc-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
			transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?> .kc-bbc-card:hover .kc-bbc-img {
			transform: scale(1.05);
		}

		/* ─────────────────────────────────────────────────────────────
		   HOVER BACKGROUND ANIMATION AROUND THE CARD
		   ───────────────────────────────────────────────────────────── */

		/* 1. BILLBOARD MULTI-COLOR AURA (Matching Reference Image) */
		.<?php echo $uid; ?> .anim-billboard_aura .kc-bbc-aura-layer {
			position: absolute;
			inset: -12px;
			border-radius: 26px;
			z-index: 1;
			opacity: 0;
			transform: scale(0.92);
			transition: opacity 0.45s ease, transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
			pointer-events: none;
		}
		/* Geometric colorful backdrop blocks matching Billboard */
		.<?php echo $uid; ?> .anim-billboard_aura .kc-bbc-aura-layer::before {
			content: '';
			position: absolute;
			inset: 0;
			border-radius: inherit;
			background: conic-gradient(from 45deg at 50% 50%, #2563eb 0deg, #ec4899 72deg, #f59e0b 144deg, #8b5cf6 216deg, #06b6d4 288deg, #2563eb 360deg);
			filter: blur(14px);
			opacity: 0.75;
			animation: kcBbcAuraSpin 8s linear infinite;
		}
		.<?php echo $uid; ?> .anim-billboard_aura .kc-bbc-aura-layer::after {
			content: '';
			position: absolute;
			inset: -4px;
			border-radius: inherit;
			background: conic-gradient(from 180deg at 50% 50%, #3b82f6 0deg, #f43f5e 90deg, #eab308 180deg, #a855f7 270deg, #3b82f6 360deg);
			opacity: 0.95;
			clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
		}

		/* 2. GLOW PULSE ANIMATION */
		.<?php echo $uid; ?> .anim-glow_pulse .kc-bbc-aura-layer {
			position: absolute;
			inset: -14px;
			border-radius: 28px;
			z-index: 1;
			opacity: 0;
			transform: scale(0.94);
			transition: all 0.4s ease;
			pointer-events: none;
			background: radial-gradient(circle, rgba(236, 72, 153, 0.5) 0%, rgba(59, 130, 246, 0.5) 50%, transparent 75%);
			filter: blur(16px);
			animation: kcBbcGlowPulse 2.8s ease-in-out infinite alternate;
		}

		/* 3. ELECTRIC DYNAMIC FRAME */
		.<?php echo $uid; ?> .anim-electric_frame .kc-bbc-aura-layer {
			position: absolute;
			inset: -6px;
			border-radius: 22px;
			z-index: 1;
			opacity: 0;
			transition: all 0.35s ease;
			pointer-events: none;
			background: linear-gradient(90deg, #3b82f6, #ec4899, #f59e0b, #10b981);
			background-size: 300% 300%;
			animation: kcBbcGradientFlow 3s ease infinite;
		}

		/* 4. SUBTLE SHADOW */
		.<?php echo $uid; ?> .anim-subtle_shadow .kc-bbc-aura-layer {
			position: absolute;
			inset: -8px;
			border-radius: 24px;
			z-index: 1;
			opacity: 0;
			transition: all 0.4s ease;
			pointer-events: none;
			box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
		}

		/* TRIGGER ANIMATION ON HOVER & ON ACTIVE SLIDE */
		.<?php echo $uid; ?> .kc-bbc-card:hover .kc-bbc-aura-layer,
		.<?php echo $uid; ?> .kc-bbc-slide.swiper-slide-active .kc-bbc-aura-layer {
			opacity: 1 !important;
			transform: scale(1) !important;
		}

		@keyframes kcBbcAuraSpin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		@keyframes kcBbcGlowPulse {
			0% { transform: scale(0.95); opacity: 0.6; }
			100% { transform: scale(1.06); opacity: 0.95; }
		}
		@keyframes kcBbcGradientFlow {
			0% { background-position: 0% 50%; }
			50% { background-position: 100% 50%; }
			100% { background-position: 0% 50%; }
		}

		/* ─── TITLE UNDER THE CARD ─── */
		.<?php echo $uid; ?> .kc-bbc-title {
			font-family: "Cairo", sans-serif;
			font-size: 22px;
			font-weight: 900;
			color: #0f172a;
			text-align: center;
			margin: 16px 0 0;
			line-height: 1.3;
			transition: color 0.3s ease, transform 0.3s ease;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			max-width: 100%;
		}
		.<?php echo $uid; ?> .kc-bbc-slide.swiper-slide-active .kc-bbc-title {
			font-size: 26px;
			font-weight: 900;
			color: #000000;
		}
		.<?php echo $uid; ?> .kc-bbc-card:hover .kc-bbc-title {
			color: #000000;
		}

		/* ─── NAVIGATION ARROWS ─── */
		.<?php echo $uid; ?>-nav-wrap {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 20px;
			margin-top: 15px;
		}
		.<?php echo $uid; ?> .kc-bbc-nav-arrow {
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
		.<?php echo $uid; ?> .kc-bbc-nav-arrow:hover {
			background: #0f172a;
			color: #ffffff;
			border-color: #0f172a;
			transform: scale(1.08);
			box-shadow: 0 8px 22px rgba(0, 0, 0, 0.15);
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap anim-<?php echo esc_attr( $anim_style ); ?>">

			<?php if ( $settings['show_header'] === 'yes' && ! empty( $settings['header_title'] ) ) : ?>
			<div class="<?php echo $uid; ?>-header">
				<h2 class="kc-bbc-header-title"><?php echo esc_html( $settings['header_title'] ); ?></h2>
			</div>
			<?php endif; ?>

			<div class="swiper <?php echo $uid; ?>-slider" id="<?php echo $uid; ?>-slider" dir="ltr">
				<div class="swiper-wrapper">
					<?php foreach ( $charts as $def ) :
						$entries = \Charts\Core\PublicIntegration::get_preview_entries( $def, 1 );
						$cover   = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $entries );
						if ( empty( $cover ) ) {
							$cover = CHARTS_URL . 'public/assets/img/placeholder.png';
						}
						$chart_url = home_url( '/charts/' . $def->slug . '/' );
					?>
					<div class="swiper-slide kc-bbc-slide">
						<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> class="kc-bbc-card" dir="rtl">
							<div class="kc-bbc-frame">
								<div class="kc-bbc-aura-layer"></div>
								<div class="kc-bbc-cover-box">
									<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" class="kc-bbc-img" loading="lazy">
								</div>
							</div>
							<h3 class="kc-bbc-title"><?php echo esc_html( $def->title ); ?></h3>
						</a>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
			<div class="<?php echo $uid; ?>-nav-wrap">
				<button type="button" class="kc-bbc-nav-arrow <?php echo $uid; ?>-prev" aria-label="السابق">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
				<button type="button" class="kc-bbc-nav-arrow <?php echo $uid; ?>-next" aria-label="التالي">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
			</div>
			<?php endif; ?>

		</div>

		<script>
		(function() {
			function initBbcSlider_<?php echo $uid_safe; ?>() {
				var container = document.getElementById("<?php echo $uid; ?>-slider");
				if (!container) return;

				if (typeof Swiper === "undefined") {
					if (!document.getElementById("kc-swiper-bundle-js")) {
						var s = document.createElement("script");
						s.id = "kc-swiper-bundle-js";
						s.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
						s.onload = function() { initBbcSlider_<?php echo $uid_safe; ?>(); };
						document.head.appendChild(s);
						var c = document.createElement("link");
						c.id = "kc-swiper-bundle-css";
						c.rel = "stylesheet";
						c.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
						document.head.appendChild(c);
					} else {
						setTimeout(initBbcSlider_<?php echo $uid_safe; ?>, 100);
					}
					return;
				}

				if (container.swiper) {
					try { container.swiper.destroy(true, true); } catch(e) {}
				}

				var swiperConfig = {
					grabCursor: true,
					centeredSlides: true,
					slidesPerView: "auto",
					slideToClickedSlide: true,
					watchSlidesProgress: true,
					speed: <?php echo intval( $settings['transition_speed'] ?? 650 ); ?>,
					loop: <?php echo $loop_opt ? 'true' : 'false'; ?>,
					spaceBetween: 20,
					navigation: {
						nextEl: ".<?php echo $uid; ?>-next",
						prevEl: ".<?php echo $uid; ?>-prev"
					}
				};

				<?php if ( $autoplay_opt ) : ?>
				swiperConfig.autoplay = {
					delay: <?php echo intval( $settings['autoplay_speed'] ?? 4000 ); ?>,
					disableOnInteraction: false,
					pauseOnMouseEnter: <?php echo ( ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
				};
				<?php endif; ?>

				new Swiper(container, swiperConfig);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initBbcSlider_<?php echo $uid_safe; ?>);
			} else {
				setTimeout(initBbcSlider_<?php echo $uid_safe; ?>, 50);
			}

			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction("frontend/element_ready/kc_all_charts_coverflow.default", function() {
					setTimeout(initBbcSlider_<?php echo $uid_safe; ?>, 100);
				});
			}
		})();
		</script>
		<?php
	}
}
