<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 3D Coverflow animated All-Charts carousel widget with mobile optimization.
 * Inspired by Billboard Arabia / Top Charts 3D showcase.
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

		$this->add_control( 'header_subtitle', [
			'label'       => __( 'Header Subtitle', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
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

		$this->add_control( 'max_charts', [
			'label'   => __( 'Max Charts to Display', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 3,
			'max'     => 30,
			'default' => 12,
		] );

		$this->add_control( 'link_target', [
			'label'   => __( 'Open in New Window', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'no',
		] );

		$this->end_controls_section();


		// ─── 2. CAROUSEL / 3D SETTINGS ─────────────────────────────────
		$this->start_controls_section( 'section_carousel', [
			'label' => __( 'Carousel & 3D Animation', 'charts' ),
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

		$this->add_control( 'slide_depth', [
			'label'       => __( 'Side Card Scale', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 0.6, 'max' => 0.95, 'step' => 0.05 ],
			],
			'default'     => [ 'size' => 0.82 ],
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide' => 'transform: scale({{SIZE}});',
			],
		] );

		$this->add_control( 'active_scale', [
			'label'       => __( 'Active Center Card Scale', 'charts' ),
			'type'        => Controls_Manager::SLIDER,
			'range'       => [
				'px' => [ 'min' => 1.05, 'max' => 1.35, 'step' => 0.05 ],
			],
			'default'     => [ 'size' => 1.16 ],
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active' => 'transform: scale({{SIZE}});',
			],
		] );

		$this->end_controls_section();


		// ─── 3. STYLE: GENERAL & BACKGROUND ───────────────────────────
		$this->start_controls_section( 'style_general', [
			'label' => __( 'General & Background', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'section_bg', [
			'label'     => __( 'Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#050505',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-wrap' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_responsive_control( 'section_padding', [
			'label'      => __( 'Padding', 'charts' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', '%' ],
			'default'    => [
				'top'      => 48,
				'right'    => 20,
				'bottom'   => 56,
				'left'     => 20,
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
			'default'   => '#ffffff',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-title' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'header_typography',
			'selector' => '{{WRAPPER}} .kc-cov-title',
		] );

		$this->add_control( 'subtitle_color', [
			'label'     => __( 'Subtitle Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#a1a1aa',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-subtitle' => 'color: {{VALUE}};',
			],
			'condition' => [ 'header_subtitle!' => '' ],
		] );

		$this->end_controls_section();


		// ─── 5. STYLE: CARDS & ARTWORK ────────────────────────────────
		$this->start_controls_section( 'style_cards', [
			'label' => __( 'Cards & Artwork', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'card_size', [
			'label'      => __( 'Card Max Width (px)', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [
				'px' => [ 'min' => 220, 'max' => 450, 'step' => 5 ],
			],
			'default'    => [ 'size' => 300 ],
			'tablet_default' => [ 'size' => 260 ],
			'mobile_default' => [ 'size' => 230 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-slide' => 'max-width: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'      => __( 'Artwork Corner Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
			'default'    => [ 'size' => 14 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-cov-card-art' => 'border-radius: {{SIZE}}px;',
				'{{WRAPPER}} .kc-cov-card-art img' => 'border-radius: {{SIZE}}px;',
			],
		] );

		$this->add_control( 'active_frame_color', [
			'label'       => __( 'Active Frame Accent Color', 'charts' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#22c55e',
			'description' => __( 'Vibrant frame border color around the active center card', 'charts' ),
			'selectors'   => [
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-frame-accent' => 'border-color: {{VALUE}};',
				'{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-corner' => 'border-color: {{VALUE}};',
			],
		] );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
			'name'     => 'active_card_shadow',
			'label'    => __( 'Active Card Glow / Shadow', 'charts' ),
			'selector' => '{{WRAPPER}} .kc-cov-slide.swiper-slide-active .kc-cov-card-art',
		] );

		$this->add_control( 'card_title_color', [
			'label'     => __( 'Chart Name Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-card-title' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'card_title_typography',
			'selector' => '{{WRAPPER}} .kc-cov-card-title',
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
			'default'   => '#ffffff',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-nav-btn' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control( 'nav_arrow_bg', [
			'label'     => __( 'Arrows Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(255, 255, 255, 0.12)',
			'selectors' => [
				'{{WRAPPER}} .kc-cov-nav-btn' => 'background: {{VALUE}};',
			],
		] );

		$this->add_control( 'dots_active_color', [
			'label'     => __( 'Active Dot Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#22c55e',
			'selectors' => [
				'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}} !important; width: 24px !important; border-radius: 6px !important;',
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

		$max_charts = ! empty( $settings['max_charts'] ) ? intval( $settings['max_charts'] ) : 12;
		$charts = array_slice( $defs, 0, $max_charts );

		$target_attr = ( ! empty( $settings['link_target'] ) && $settings['link_target'] === 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		?>

		<style>
		.<?php echo $uid; ?>-wrap {
			direction: rtl;
			position: relative;
			overflow: hidden;
			background: #050505;
			padding: 48px 20px 56px;
			box-sizing: border-box;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Cairo", Helvetica, Arial, sans-serif;
			user-select: none;
		}

		.<?php echo $uid; ?>-header {
			margin-bottom: 36px;
			text-align: <?php echo esc_attr( $settings['header_alignment'] ?? 'right' ); ?>;
		}

		.<?php echo $uid; ?>-title {
			font-size: 28px;
			font-weight: 900;
			color: #fff;
			margin: 0 0 6px 0;
			letter-spacing: -0.5px;
			line-height: 1.3;
		}

		.<?php echo $uid; ?>-subtitle {
			font-size: 14px;
			color: #a1a1aa;
			margin: 0;
			font-weight: 500;
		}

		/* Swiper Container with 3D overflow */
		.<?php echo $uid; ?>-container {
			width: 100%;
			padding: 40px 0 50px;
			overflow: visible !important;
		}

		.<?php echo $uid; ?> .swiper-wrapper {
			align-items: center;
			display: flex;
		}

		/* Slide Card */
		.<?php echo $uid; ?> .kc-cov-slide {
			width: 300px;
			max-width: 300px;
			flex-shrink: 0;
			transition: transform 0.45s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.45s ease, filter 0.45s ease;
			opacity: 0.55;
			transform: scale(0.82);
			filter: brightness(0.75);
			cursor: pointer;
			text-decoration: none;
			display: flex;
			flex-direction: column;
			align-items: center;
			text-align: center;
		}

		/* Active Center Slide */
		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active {
			opacity: 1;
			filter: brightness(1);
			transform: scale(1.16);
			z-index: 10;
			cursor: default;
		}

		/* Artwork wrapper */
		.<?php echo $uid; ?> .kc-cov-card-art {
			position: relative;
			width: 100%;
			aspect-ratio: 1 / 1;
			background: #18181b;
			border-radius: 14px;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
			transition: box-shadow 0.45s ease;
		}

		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card-art {
			box-shadow: 0 16px 45px rgba(0, 0, 0, 0.85), 0 0 25px rgba(34, 197, 94, 0.25);
		}

		.<?php echo $uid; ?> .kc-cov-card-art img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			border-radius: 14px;
			display: block;
			image-rendering: -webkit-optimize-contrast;
			image-rendering: crisp-edges;
			backface-visibility: hidden;
			-webkit-backface-visibility: hidden;
			transform: translateZ(0);
		}

		/* Geometric / Neon corner accents on active slide (Reference frame design) */
		.<?php echo $uid; ?> .kc-cov-corners {
			position: absolute;
			inset: -6px;
			pointer-events: none;
			opacity: 0;
			transition: opacity 0.35s ease;
		}

		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-corners {
			opacity: 1;
		}

		.<?php echo $uid; ?> .kc-cov-corner {
			position: absolute;
			width: 18px;
			height: 18px;
			border-color: #22c55e;
			border-style: solid;
			border-width: 0;
		}

		.<?php echo $uid; ?> .kc-cov-corner.top-left {
			top: 0;
			left: 0;
			border-top-width: 3px;
			border-left-width: 3px;
			border-top-left-radius: 8px;
		}

		.<?php echo $uid; ?> .kc-cov-corner.top-right {
			top: 0;
			right: 0;
			border-top-width: 3px;
			border-right-width: 3px;
			border-top-right-radius: 8px;
		}

		.<?php echo $uid; ?> .kc-cov-corner.bottom-left {
			bottom: 0;
			left: 0;
			border-bottom-width: 3px;
			border-left-width: 3px;
			border-bottom-left-radius: 8px;
		}

		.<?php echo $uid; ?> .kc-cov-corner.bottom-right {
			bottom: 0;
			right: 0;
			border-bottom-width: 3px;
			border-right-width: 3px;
			border-bottom-right-radius: 8px;
		}

		/* Active frame subtle border line */
		.<?php echo $uid; ?> .kc-cov-frame-accent {
			position: absolute;
			inset: 0;
			border-radius: 14px;
			border: 2px solid transparent;
			pointer-events: none;
			transition: border-color 0.3s ease;
		}

		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-frame-accent {
			border-color: rgba(34, 197, 94, 0.6);
		}

		/* Badge / Rank indicator (Top 50 / 100) */
		.<?php echo $uid; ?> .kc-cov-badge {
			position: absolute;
			top: 10px;
			right: 10px;
			background: rgba(0, 0, 0, 0.7);
			backdrop-filter: blur(8px);
			-webkit-backdrop-filter: blur(8px);
			color: #fff;
			font-size: 11px;
			font-weight: 800;
			padding: 4px 9px;
			border-radius: 20px;
			border: 1px solid rgba(255, 255, 255, 0.15);
			z-index: 2;
			letter-spacing: 0.5px;
		}

		/* Chart Title below Artwork */
		.<?php echo $uid; ?> .kc-cov-card-info {
			margin-top: 18px;
			width: 100%;
			padding: 0 4px;
		}

		.<?php echo $uid; ?> .kc-cov-card-title {
			font-size: 19px;
			font-weight: 800;
			color: #fff;
			margin: 0;
			line-height: 1.35;
			transition: color 0.3s;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card-title {
			color: #ffffff;
			text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
		}

		.<?php echo $uid; ?> .kc-cov-card-meta {
			font-size: 12px;
			color: #71717a;
			margin-top: 4px;
			font-weight: 600;
		}

		.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active .kc-cov-card-meta {
			color: #a1a1aa;
		}

		/* Navigation Arrows */
		.<?php echo $uid; ?> .kc-cov-controls {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 16px;
			margin-top: 24px;
		}

		.<?php echo $uid; ?> .kc-cov-nav-btn {
			width: 44px;
			height: 44px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.1);
			color: #fff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: all 0.25s ease;
			outline: none;
		}

		.<?php echo $uid; ?> .kc-cov-nav-btn:hover {
			background: rgba(255, 255, 255, 0.22);
			transform: scale(1.08);
			border-color: rgba(255, 255, 255, 0.3);
		}

		.<?php echo $uid; ?> .kc-cov-nav-btn:active {
			transform: scale(0.96);
		}

		/* Pagination Dots */
		.<?php echo $uid; ?> .swiper-pagination {
			position: static !important;
			margin-top: 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
		}

		.<?php echo $uid; ?> .swiper-pagination-bullet {
			width: 8px;
			height: 8px;
			border-radius: 4px;
			background: rgba(255, 255, 255, 0.3);
			opacity: 1;
			transition: all 0.3s ease;
			margin: 0 !important;
		}

		.<?php echo $uid; ?> .swiper-pagination-bullet-active {
			background: #22c55e !important;
			width: 24px !important;
			border-radius: 6px !important;
		}

		/* Mobile & Tablet Optimizations */
		@media (max-width: 1024px) {
			.<?php echo $uid; ?>-wrap { padding: 36px 16px 44px; }
			.<?php echo $uid; ?>-title { font-size: 24px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 260px; max-width: 260px; }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 17px; }
		}

		@media (max-width: 640px) {
			.<?php echo $uid; ?>-wrap { padding: 28px 12px 36px; }
			.<?php echo $uid; ?>-title { font-size: 20px; text-align: center; }
			.<?php echo $uid; ?>-header { text-align: center !important; margin-bottom: 24px; }
			.<?php echo $uid; ?>-container { padding: 25px 0 35px; }
			.<?php echo $uid; ?> .kc-cov-slide { width: 220px; max-width: 220px; }
			.<?php echo $uid; ?> .kc-cov-slide.swiper-slide-active { transform: scale(1.14); }
			.<?php echo $uid; ?> .kc-cov-card-title { font-size: 16px; }
			.<?php echo $uid; ?> .kc-cov-nav-btn { width: 38px; height: 38px; }
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-widget-wrap">
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
						// Resolve Artwork:
						$entries = \Charts\Core\PublicIntegration::get_preview_entries( $def, 1 );
						$cover = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $entries );
						if ( empty( $cover ) ) {
							$cover = CHARTS_URL . 'public/assets/img/placeholder.png';
						}

						$chart_url = home_url( '/charts/' . $def->slug . '/' );
						$accent = ! empty( $def->accent_color ) ? $def->accent_color : '#22c55e';
					?>
					<div class="swiper-slide kc-cov-slide" data-url="<?php echo esc_url( $chart_url ); ?>">
						<a href="<?php echo esc_url( $chart_url ); ?>"<?php echo $target_attr; ?> style="text-decoration:none; color:inherit; width:100%; display:flex; flex-direction:column; align-items:center;">
							<div class="kc-cov-card-art">
								<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $def->title ); ?>" loading="lazy">
								
								<!-- Neon / Geometric frame corners -->
								<div class="kc-cov-corners">
									<span class="kc-cov-corner top-left" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner top-right" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner bottom-left" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
									<span class="kc-cov-corner bottom-right" style="border-color: <?php echo esc_attr( $accent ); ?>;"></span>
								</div>
								
								<div class="kc-cov-frame-accent" style="border-color: <?php echo esc_attr( $accent ); ?>;"></div>

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
					setTimeout(initCoverflow_<?php echo $uid_safe; ?>, 100);
					return;
				}

				var swiperContainer = document.getElementById("<?php echo $uid; ?>-swiper");
				if (!swiperContainer) return;

				var swiper = new Swiper(swiperContainer, {
					effect: "slide",
					grabCursor: true,
					centeredSlides: true,
					slidesPerView: "auto",
					spaceBetween: 36,
					speed: 600,
					loop: <?php echo $loop_opt ? 'true' : 'false'; ?>,
					<?php if ( $autoplay_opt ) : ?>
					autoplay: {
						delay: <?php echo intval( $settings['autoplay_speed'] ?? 3500 ); ?>,
						disableOnInteraction: false,
						pauseOnMouseEnter: <?php echo ( ( $settings['pause_on_hover'] ?? 'yes' ) === 'yes' ) ? 'true' : 'false'; ?>
					},
					<?php endif; ?>
					pagination: {
						el: ".<?php echo $uid; ?>-pagination",
						clickable: true
					},
					navigation: {
						nextEl: ".<?php echo $uid; ?>-next",
						prevEl: ".<?php echo $uid; ?>-prev"
					},
					breakpoints: {
						320: {
							spaceBetween: 18,
							centeredSlides: true
						},
						640: {
							spaceBetween: 28,
							centeredSlides: true
						},
						1024: {
							spaceBetween: 38,
							centeredSlides: true
						}
					},
					on: {
						click: function(s, e) {
							// If a side card is clicked, slide to it smoothly
							if (s.clickedIndex !== undefined && s.clickedIndex !== s.activeIndex) {
								s.slideTo(s.clickedIndex);
							}
						}
					}
				});
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initCoverflow_<?php echo $uid_safe; ?>);
			} else {
				initCoverflow_<?php echo $uid_safe; ?>();
			}

			// Elementor editor hook
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
