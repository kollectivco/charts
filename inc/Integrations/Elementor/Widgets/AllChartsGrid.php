<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class AllChartsGrid extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name()       { return 'kc_all_charts_grid'; }
	public function get_title()      { return __( 'Charts: All Charts Grid & Carousel', 'charts' ); }
	public function get_icon()       { return 'eicon-gallery-grid'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {

		// ─── 1. CONTENT SETTINGS ────────────────────────────────
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Display & Layout', 'charts' ) ] );

		$this->add_control( 'display_mode', [
			'label'   => __( 'Display Mode (طريقة العرض)', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'grid'     => __( 'Grid (شبكة بطاقات ثابتة)', 'charts' ),
				'carousel' => __( 'Carousel (سلايدر متحرك)', 'charts' ),
			],
			'default' => 'grid',
		] );

		$this->add_control( 'layout_style', [
			'label'   => __( 'Card Style', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'cover_list'   => 'Cover + List (Reference Design)',
				'cover_only'   => 'Cover Cards Only',
				'minimal_list' => 'Minimal List Cards',
				'dark_pro'     => 'Dark Pro Cards',
			],
			'default' => 'cover_list',
		] );

		$this->add_control( 'tracks_layout', [
			'label'       => __( 'Card Tracks Layout (تنسيق صفوف الأغاني بالكارد)', 'charts' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => [
				'1_col' => __( '1 Column (صف واحد عمودي)', 'charts' ),
				'2_col' => __( '2 Columns (صفين قدام بعض / عمودين متوازيين)', 'charts' ),
			],
			'default'     => '1_col',
			'condition'   => [ 'layout_style!' => 'cover_only' ],
			'description' => __( 'يعرض الأغاني داخل الكارت في عمودين متجاورين لتوفير المساحة وعرض تراكات أكثر.', 'charts' ),
		] );

		$this->add_control( 'numeral_format', [
			'label'   => __( 'Rank Numbers Format (تنسيق الأرقام)', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'standard' => __( 'Standard (1, 2, 3...) بخط الموقع Cairo', 'charts' ),
				'eastern'  => __( 'Eastern Arabic (١، ٢، ٣...) بخط Cairo', 'charts' ),
			],
			'default' => 'standard',
			'condition' => [ 'layout_style!' => 'cover_only' ],
		] );

		$this->add_control( 'tracks_per_chart', [
			'label'   => __( 'Tracks per Chart', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 4,
			'min'     => 1,
			'max'     => 12,
		] );

		$this->add_responsive_control( 'columns', [
			'label'   => __( 'Grid Columns', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
			],
			'default'        => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
			'condition'      => [ 'display_mode' => 'grid' ],
			'selectors' => [
				'{{WRAPPER}} .kc-acg-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
			],
		] );

		$this->add_control( 'show_header', [
			'label'   => __( 'Show Section Header', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'header_title', [
			'label'       => __( 'Header Title', 'charts' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'كل السباقات',
			'condition'   => [ 'show_header' => 'yes' ],
		] );

		$this->add_control( 'view_all_text', [
			'label'   => __( 'View All Text', 'charts' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'عرض كل القوائم',
		] );

		$this->add_control( 'view_all_url', [
			'label' => __( 'View All URL', 'charts' ),
			'type'  => Controls_Manager::URL,
		] );

		$this->add_control( 'card_view_text', [
			'label'   => __( 'Per Card View Text', 'charts' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'عرض السباق كاملاً',
		] );

		$this->add_control( 'show_cover_label', [
			'label'   => __( 'Cover Label Text', 'charts' ),
			'type'    => Controls_Manager::TEXT,
			'default' => 'قائمة الأسبوع',
		] );

		$this->end_controls_section();


		// ─── 2. CAROUSEL SETTINGS ───────────────────────────────
		$this->start_controls_section( 'section_carousel_opts', [
			'label'     => __( 'Carousel Settings', 'charts' ),
			'tab'       => Controls_Manager::TAB_CONTENT,
			'condition' => [ 'display_mode' => 'carousel' ],
		] );

		$this->add_responsive_control( 'slides_per_view', [
			'label'          => __( 'Slides to Show', 'charts' ),
			'type'           => Controls_Manager::SELECT,
			'options'        => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ],
			'default'        => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
		] );

		$this->add_control( 'autoplay', [
			'label'        => __( 'Autoplay', 'charts' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->add_control( 'autoplay_speed', [
			'label'     => __( 'Autoplay Delay (ms)', 'charts' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 4000,
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
			'default'      => 'yes',
			'return_value' => 'yes',
		] );

		$this->end_controls_section();


		// ─── STYLE: HEADER ─────────────────────────────────────
		$this->start_controls_section( 'style_header', [ 'label' => __( 'Header', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_header' => 'yes' ] ] );
		$this->add_control( 'header_color', [
			'label'     => __( 'Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'header_typo', 'selector' => '{{WRAPPER}} .kc-acg-title' ] );
		$this->end_controls_section();

		// ─── STYLE: CARD ───────────────────────────────────────
		$this->start_controls_section( 'style_card', [ 'label' => __( 'Card', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'card_bg', [
			'label'     => __( 'Card Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-card' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'card_radius', [
			'label'      => __( 'Border Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'max' => 40 ] ],
			'default'    => [ 'size' => 20 ],
			'selectors'  => [ '{{WRAPPER}} .kc-acg-card' => 'border-radius: {{SIZE}}px;' ],
		] );
		$this->add_responsive_control( 'cover_height', [
			'label'      => __( 'Cover Image Height', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 100, 'max' => 500 ] ],
			'default'    => [ 'size' => 200 ],
			'selectors'  => [ '{{WRAPPER}} .kc-acg-cover' => 'height: {{SIZE}}px;' ],
		] );
		$this->add_control( 'overlay_opacity', [
			'label'     => __( 'Cover Overlay Opacity', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
			'default'   => [ 'size' => 0.5 ],
			'selectors' => [ '{{WRAPPER}} .kc-acg-cover-overlay' => 'opacity: {{SIZE}};' ],
		] );
		$this->add_control( 'accent_color', [
			'label'     => __( 'Accent / Link Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#6c63ff',
			'selectors' => [ '{{WRAPPER}}' => '--acg-accent: {{VALUE}};' ],
		] );
		$this->end_controls_section();

		// ─── STYLE: TRACK ROW ──────────────────────────────────
		$this->start_controls_section( 'style_track', [ 'label' => __( 'Track Row & Numerals', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'track_title_color', [
			'label'     => __( 'Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-track-title' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'track_title_typo', 'selector' => '{{WRAPPER}} .kc-acg-track-title' ] );
		$this->add_control( 'track_sub_color', [
			'label'     => __( 'Subtitle Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-track-sub' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'rank_color', [
			'label'     => __( 'Rank Number Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#6c63ff',
			'selectors' => [ '{{WRAPPER}} .kc-acg-rank' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'     => 'rank_typography',
			'label'    => __( 'Rank Number Typography (فونت وتنسيق الأرقام)', 'charts' ),
			'selector' => '{{WRAPPER}} .kc-acg-rank',
		] );
		$this->add_control( 'divider_color', [
			'label'     => __( 'Row Divider Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-track-row' => 'border-bottom-color: {{VALUE}};' ],
		] );
		$this->end_controls_section();

		// ─── STYLE: CAROUSEL CONTROLS ──────────────────────────
		$this->start_controls_section( 'style_carousel_nav', [
			'label'     => __( 'Carousel Navigation & Arrows', 'charts' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'display_mode' => 'carousel' ],
		] );
		$this->add_control( 'arrow_color', [
			'label'     => __( 'Arrow Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-arrow' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'arrow_bg', [
			'label'     => __( 'Arrow Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-acg-arrow' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_section();

		$this->add_advanced_image_controls('{{WRAPPER}} .kc-acg-cover', '{{WRAPPER}} .kc-acg-cover img');
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager  = new \Charts\Admin\SourceManager();
		$defs     = $manager->get_definitions( true );

		if ( empty( $defs ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px;text-align:center;background:#0f172a;color:#fff;border-radius:20px;border:2px dashed #ff0055">No chart definitions found.</div>';
			}
			return;
		}

		$uid          = 'kc-acg-' . $this->get_id();
		$uid_safe     = str_replace( '-', '_', $uid );
		$display_mode = $settings['display_mode']     ?? 'grid';
		$style        = $settings['layout_style']     ?? 'cover_list';
		$tracks_layout= $settings['tracks_layout']    ?? '1_col';
		$num_format   = $settings['numeral_format']   ?? 'standard';
		$limit        = intval( $settings['tracks_per_chart'] ?? 4 );
		$accent       = $settings['accent_color']     ?? '#6c63ff';
		$view_text    = $settings['card_view_text']   ?? 'عرض السباق كاملاً';
		$cover_label  = $settings['show_cover_label']  ?? 'قائمة الأسبوع';

		// Carousel options
		$autoplay_opt = ( ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' );
		$loop_opt     = ( ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' );
		$spv_desktop  = intval( $settings['slides_per_view'] ?? 3 );
		$spv_tablet   = intval( $settings['slides_per_view_tablet'] ?? 2 );
		$spv_mobile   = intval( $settings['slides_per_view_mobile'] ?? 1 );
		?>
		<style>
		.<?php echo $uid; ?> { direction: rtl; font-family: "Cairo", "Inter", sans-serif; --acg-accent: <?php echo esc_attr($accent); ?>; position: relative; width: 100%; box-sizing: border-box; }
		.<?php echo $uid; ?>-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
		.<?php echo $uid; ?> .kc-acg-title { font-size: 32px; font-weight: 900; margin: 0; display: flex; align-items: center; gap: 10px; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?> .kc-acg-view-all { font-size: 15px; font-weight: 700; color: var(--acg-accent); text-decoration: none; display: flex; align-items: center; gap: 6px; transition: opacity 0.2s; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?> .kc-acg-view-all:hover { opacity: 0.7; }

		/* GRID MODE */
		.<?php echo $uid; ?> .kc-acg-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

		/* CAROUSEL MODE */
		.<?php echo $uid; ?> .kc-acg-carousel { width: 100%; overflow: hidden; position: relative; padding-bottom: 45px; }
		.<?php echo $uid; ?> .kc-acg-carousel .swiper-wrapper { display: flex; align-items: stretch; }
		.<?php echo $uid; ?> .kc-acg-carousel .swiper-slide { height: auto; display: flex; flex-direction: column; }
		.<?php echo $uid; ?> .kc-acg-carousel .kc-acg-card { height: 100%; flex: 1; }

		/* CARD BASE */
		.<?php echo $uid; ?> .kc-acg-card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; }
		.<?php echo $uid; ?> .kc-acg-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }

		/* COVER */
		.<?php echo $uid; ?> .kc-acg-cover { position: relative; height: 200px; overflow: hidden; }
		.<?php echo $uid; ?> .kc-acg-cover img { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; transition: transform 0.6s ease; }
		.<?php echo $uid; ?> .kc-acg-card:hover .kc-acg-cover img { transform: scale(1.05); }
		.<?php echo $uid; ?> .kc-acg-cover-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.78) 0%, rgba(0,0,0,0.15) 60%, rgba(0,0,0,0) 100%); }
		.<?php echo $uid; ?> .kc-acg-cover-meta { position: absolute; bottom: 16px; right: 16px; left: 16px; }
		.<?php echo $uid; ?> .kc-acg-cover-label { display: inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(6px); color: #fff; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; margin-bottom: 6px; letter-spacing: 0.5px; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?> .kc-acg-cover-title { color: #fff; font-size: 26px; font-weight: 900; margin: 0; line-height: 1.2; text-shadow: 0 2px 10px rgba(0,0,0,0.5); font-family: "Cairo", sans-serif; }

		/* TRACK LIST (1 COLUMN DEFAULT) */
		.<?php echo $uid; ?> .kc-acg-tracks { padding: 16px 20px 4px; flex: 1; }
		.<?php echo $uid; ?> .kc-acg-track-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; box-sizing: border-box; }
		.<?php echo $uid; ?> .kc-acg-track-row:last-child { border-bottom: none; }
		.<?php echo $uid; ?> .kc-acg-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
		.<?php echo $uid; ?> .kc-acg-track-info { flex: 1; min-width: 0; text-align: right; }
		.<?php echo $uid; ?> .kc-acg-track-title { font-size: 15px; font-weight: 800; margin: 0 0 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #0f172a; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?> .kc-acg-track-sub { font-size: 12px; font-weight: 600; color: #94a3b8; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: "Cairo", sans-serif; }

		/* RANK NUMBERS - SITE FONT (CAIRO) */
		.<?php echo $uid; ?> .kc-acg-rank {
			font-size: 18px;
			font-weight: 900;
			width: 26px;
			text-align: center;
			flex-shrink: 0;
			color: var(--acg-accent);
			font-family: "Cairo", -apple-system, sans-serif !important;
			line-height: 1;
			font-feature-settings: "tnum";
		}

		/* ── TRACKS 2 COLUMNS LAYOUT (صفين قدام بعض داخل الكارد) ── */
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 8px 14px;
			padding: 14px 16px 4px;
		}
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col .kc-acg-track-row {
			padding: 8px 0;
			gap: 8px;
		}
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col .kc-acg-thumb {
			width: 36px;
			height: 36px;
			border-radius: 6px;
		}
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col .kc-acg-track-title {
			font-size: 13px;
		}
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col .kc-acg-track-sub {
			font-size: 11px;
		}
		.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col .kc-acg-rank {
			font-size: 15px;
			width: 20px;
		}

		/* CARD FOOTER */
		.<?php echo $uid; ?> .kc-acg-footer { padding: 16px 20px; text-align: center; border-top: 1px solid #f1f5f9; margin-top: auto; }
		.<?php echo $uid; ?> .kc-acg-footer a { color: var(--acg-accent); font-size: 14px; font-weight: 800; text-decoration: none; transition: opacity 0.2s; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?> .kc-acg-footer a:hover { opacity: 0.7; }

		/* ── VARIANT: COVER ONLY ───────────────────────────── */
		.<?php echo $uid; ?>.style-cover_only .kc-acg-cover { height: 300px; }
		.<?php echo $uid; ?>.style-cover_only .kc-acg-cover-title { font-size: 34px; }
		.<?php echo $uid; ?>.style-cover_only .kc-acg-tracks { display: none; }

		/* ── VARIANT: MINIMAL LIST ─────────────────────────── */
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-card { box-shadow: none; border: 1px solid #e2e8f0; }
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-cover { height: 120px; }
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-cover-title { font-size: 22px; }
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-thumb { width: 36px; height: 36px; border-radius: 50%; }
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-rank { font-size: 15px; color: #94a3b8; font-weight: 700; }
		.<?php echo $uid; ?>.style-minimal_list .kc-acg-track-title { font-size: 14px; }

		/* ── VARIANT: DARK PRO ─────────────────────────────── */
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-card { background: #0f172a; border: 1px solid rgba(255,255,255,0.06); }
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-track-row { border-bottom-color: rgba(255,255,255,0.05); }
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-track-title { color: #f1f5f9; }
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-track-sub { color: #64748b; }
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-footer { border-top-color: rgba(255,255,255,0.06); }
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-cover-label { background: rgba(255,255,255,0.1); }

		/* CAROUSEL CONTROLS */
		.<?php echo $uid; ?>-nav-wrap { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 15px; }
		.<?php echo $uid; ?> .kc-acg-arrow {
			width: 42px;
			height: 42px;
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
		.<?php echo $uid; ?>.style-dark_pro .kc-acg-arrow {
			background: rgba(255, 255, 255, 0.08);
			color: #ffffff;
			border-color: rgba(255, 255, 255, 0.12);
		}
		.<?php echo $uid; ?> .kc-acg-arrow:hover {
			background: var(--acg-accent);
			color: #ffffff;
			border-color: var(--acg-accent);
			transform: scale(1.08);
		}
		.<?php echo $uid; ?> .swiper-pagination { position: static !important; width: auto !important; display: inline-flex; gap: 6px; }
		.<?php echo $uid; ?> .swiper-pagination-bullet { width: 8px; height: 8px; border-radius: 4px; background: #cbd5e1; opacity: 0.6; transition: all 0.3s; }
		.<?php echo $uid; ?> .swiper-pagination-bullet-active { width: 22px; background: var(--acg-accent) !important; opacity: 1; }

		@media (max-width: 900px) {
			.<?php echo $uid; ?> .kc-acg-grid { grid-template-columns: repeat(2, 1fr); }
		}
		@media (max-width: 600px) {
			.<?php echo $uid; ?> .kc-acg-grid { grid-template-columns: 1fr; }
			.<?php echo $uid; ?> .kc-acg-tracks.layout-2-col { grid-template-columns: 1fr; }
		}
		</style>

		<div class="<?php echo $uid; ?> kc-widget-wrap style-<?php echo esc_attr($style); ?>">

			<?php if ( $settings['show_header'] === 'yes' ) : ?>
			<div class="<?php echo $uid; ?>-header">
				<h2 class="kc-acg-title">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
					<?php echo esc_html( $settings['header_title'] ); ?>
				</h2>
				<?php if ( !empty($settings['view_all_url']['url']) ) : ?>
				<a href="<?php echo esc_url($settings['view_all_url']['url']); ?>" class="kc-acg-view-all">
					← <?php echo esc_html($settings['view_all_text']); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ( $display_mode === 'carousel' ) : ?>
				<!-- CAROUSEL WRAPPER -->
				<div class="swiper <?php echo $uid; ?>-carousel" id="<?php echo $uid; ?>-carousel" dir="rtl">
					<div class="swiper-wrapper">
			<?php else : ?>
				<!-- GRID WRAPPER -->
				<div class="kc-acg-grid">
			<?php endif; ?>

				<?php foreach ( $defs as $def ) :
					$entries = \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit );
					if ( empty($entries) ) continue;

					$cover = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $entries );
					if ( empty($cover) ) $cover = CHARTS_URL . 'public/assets/img/placeholder.png';

					$chart_url = home_url( '/charts/' . $def->slug . '/' );
				?>

				<?php if ( $display_mode === 'carousel' ) : ?>
				<div class="swiper-slide <?php echo $uid; ?>-slide">
				<?php endif; ?>

					<div class="kc-acg-card">

						<!-- Cover -->
						<a href="<?php echo esc_url($chart_url); ?>" class="kc-acg-cover" style="display:block;">
							<img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($def->title); ?>">
							<div class="kc-acg-cover-overlay"></div>
							<div class="kc-acg-cover-meta">
								<span class="kc-acg-cover-label"><?php echo esc_html($cover_label); ?></span>
								<h3 class="kc-acg-cover-title"><?php echo esc_html($def->title); ?></h3>
							</div>
						</a>

						<!-- Tracks List -->
						<?php if ( $style !== 'cover_only' ) : ?>
						<div class="kc-acg-tracks <?php echo ( $tracks_layout === '2_col' ) ? 'layout-2-col' : ''; ?>">
							<?php foreach ( $entries as $e ) :
								$resolved = \Charts\Core\PublicIntegration::resolve_display_name( $e, $def );
								$thumb    = \Charts\Core\PublicIntegration::resolve_chart_image( $def, [$e] );
								if ( empty($thumb) ) $thumb = CHARTS_URL . 'public/assets/img/placeholder.png';

								// Number format using site font Cairo
								if ( $num_format === 'eastern' ) {
									$rank_display = \Charts\Core\Transliteration::to_arabic_numerals( $e->rank_position );
								} else {
									$rank_display = intval( $e->rank_position );
								}
							?>
							<div class="kc-acg-track-row">
								<span class="kc-acg-rank"><?php echo $rank_display; ?></span>
								<img src="<?php echo esc_url($thumb); ?>" class="kc-acg-thumb" alt="" loading="lazy">
								<div class="kc-acg-track-info">
									<p class="kc-acg-track-title"><?php echo esc_html($resolved['title']); ?></p>
									<p class="kc-acg-track-sub"><?php echo esc_html($resolved['subtitle']); ?></p>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>

						<!-- Footer -->
						<div class="kc-acg-footer">
							<a href="<?php echo esc_url($chart_url); ?>">
								<?php echo esc_html($view_text); ?> ←
							</a>
						</div>

					</div>

				<?php if ( $display_mode === 'carousel' ) : ?>
				</div>
				<?php endif; ?>

				<?php endforeach; ?>

			<?php if ( $display_mode === 'carousel' ) : ?>
					</div>
				</div>

				<!-- Carousel Navigation Controls -->
				<div class="<?php echo $uid; ?>-nav-wrap">
					<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
					<button type="button" class="kc-acg-arrow <?php echo $uid; ?>-prev" aria-label="السابق">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					<?php endif; ?>

					<?php if ( $settings['show_dots'] === 'yes' ) : ?>
					<div class="swiper-pagination <?php echo $uid; ?>-pagination"></div>
					<?php endif; ?>

					<?php if ( $settings['show_arrows'] === 'yes' ) : ?>
					<button type="button" class="kc-acg-arrow <?php echo $uid; ?>-next" aria-label="التالي">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<?php endif; ?>
				</div>

				<script>
				(function() {
					function initAcgCarousel_<?php echo $uid_safe; ?>() {
						var el = document.getElementById("<?php echo $uid; ?>-carousel");
						if (!el) return;

						if (typeof Swiper === "undefined") {
							if (!document.getElementById("kc-swiper-bundle-js")) {
								var s = document.createElement("script");
								s.id = "kc-swiper-bundle-js";
								s.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
								s.onload = function() { initAcgCarousel_<?php echo $uid_safe; ?>(); };
								document.head.appendChild(s);
								var c = document.createElement("link");
								c.id = "kc-swiper-bundle-css";
								c.rel = "stylesheet";
								c.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
								document.head.appendChild(c);
							} else {
								setTimeout(initAcgCarousel_<?php echo $uid_safe; ?>, 100);
							}
							return;
						}

						if (el.swiper) {
							try { el.swiper.destroy(true, true); } catch(e) {}
						}

						var cfg = {
							slidesPerView: <?php echo $spv_mobile; ?>,
							spaceBetween: 20,
							grabCursor: true,
							watchSlidesProgress: true,
							loop: <?php echo $loop_opt ? 'true' : 'false'; ?>,
							breakpoints: {
								640: {
									slidesPerView: <?php echo $spv_tablet; ?>,
									spaceBetween: 20
								},
								1024: {
									slidesPerView: <?php echo $spv_desktop; ?>,
									spaceBetween: 24
								}
							},
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
						cfg.autoplay = {
							delay: <?php echo intval( $settings['autoplay_speed'] ?? 4000 ); ?>,
							disableOnInteraction: false,
							pauseOnMouseEnter: true
						};
						<?php endif; ?>

						new Swiper(el, cfg);
					}

					if (document.readyState === "loading") {
						document.addEventListener("DOMContentLoaded", initAcgCarousel_<?php echo $uid_safe; ?>);
					} else {
						setTimeout(initAcgCarousel_<?php echo $uid_safe; ?>, 50);
					}

					if (window.elementorFrontend && window.elementorFrontend.hooks) {
						window.elementorFrontend.hooks.addAction("frontend/element_ready/kc_all_charts_grid.default", function() {
							setTimeout(initAcgCarousel_<?php echo $uid_safe; ?>, 100);
						});
					}
				})();
				</script>
			<?php else : ?>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
