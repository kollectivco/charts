<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class Top5HeroShowcase extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_top5_hero_showcase'; }
	public function get_title() { return __( 'Charts: Top 5 Hero Showcase', 'charts' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [ 'label' => __( 'Content Settings', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = ['0' => __( 'Current Chart (Dynamic)', 'charts' )];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => '0',
		] );

		$this->add_control( 'title', [
			'label' => __( 'Main Title', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'أعلى الأغاني',
		] );
		$this->add_control( 'subtitle', [
			'label' => __( 'Subtitle', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'مصر و العالم العربي',
		] );
		$this->add_control( 'bg_text', [
			'label' => __( 'Background Giant Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'TOP SONGS',
		] );
		$this->add_control( 'link_text', [
			'label' => __( 'Link Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'القائمة الكاملة',
		] );
		$this->add_control( 'link_url', [
			'label' => __( 'Link URL', 'charts' ),
			'type' => Controls_Manager::URL,
			'default' => [ 'url' => '' ],
		] );

		$this->end_controls_section();

		// 1. General Style
		$this->start_controls_section( 'style_general', [ 'label' => __( 'General & Background', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'accent_color', [
			'label' => __( 'Accent Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#ff0055',
			'selectors' => [ '{{WRAPPER}}' => '--t5-accent: {{VALUE}};' ],
		]);
		$this->add_control( 'bg_color', [
			'label' => __( 'Widget Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-wrap' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'widget_padding', [
			'label' => __( 'Widget Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .kc-t5-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->end_controls_section();

		// 2. Header Style
		$this->start_controls_section( 'style_header', [ 'label' => __( 'Header & Title', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-titles h2' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'title_typo', 'selector' => '{{WRAPPER}} .kc-t5-titles h2',
		]);
		$this->add_control( 'subtitle_color', [
			'label' => __( 'Subtitle Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-titles p' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'subtitle_typo', 'selector' => '{{WRAPPER}} .kc-t5-titles p',
		]);
		$this->add_control( 'link_color', [
			'label' => __( 'Link Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#00ffaa',
			'selectors' => [ '{{WRAPPER}}' => '--t5-link: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'link_typo', 'selector' => '{{WRAPPER}} .kc-t5-link',
		]);
		$this->add_control( 'bgtext_color', [
			'label' => __( 'Giant Background Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-bg-text' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'bgtext_typo', 'selector' => '{{WRAPPER}} .kc-t5-bg-text',
		]);
		$this->end_controls_section();

		// 3. Hero Style (#1)
		$this->start_controls_section( 'style_hero', [ 'label' => __( 'Hero Card (#1)', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'hero_title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-hero-text h3' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'hero_title_typo', 'selector' => '{{WRAPPER}} .kc-t5-hero-text h3',
		]);
		$this->add_control( 'hero_artist_color', [
			'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-hero-text p' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'hero_artist_typo', 'selector' => '{{WRAPPER}} .kc-t5-hero-text p',
		]);
		$this->add_control( 'hero_rank_color', [
			'label' => __( 'Giant Rank Number Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-rank-big' => 'color: {{VALUE}};' ],
		]);
		$this->end_controls_section();

		// 4. List Style (#2-5)
		$this->start_controls_section( 'style_list', [ 'label' => __( 'List Cards (#2-#5)', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'row_bg_color', [
			'label' => __( 'Row Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-row' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'row_hover_color', [
			'label' => __( 'Row Hover Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-row:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'list_title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-r-info h4' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'list_title_typo', 'selector' => '{{WRAPPER}} .kc-t5-r-info h4',
		]);
		$this->add_control( 'list_artist_color', [
			'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-r-info p' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'list_artist_typo', 'selector' => '{{WRAPPER}} .kc-t5-r-info p',
		]);
		$this->add_control( 'list_rank_color', [
			'label' => __( 'Rank Number Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-r-rank' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'up_color', [
			'label' => __( 'Up (▲) Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-move-up' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'down_color', [
			'label' => __( 'Down (▼) Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-t5-move-down' => 'color: {{VALUE}};' ],
		]);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		
		$manager = new \Charts\Admin\SourceManager();
		$def = (!isset($settings["chart_id"]) || $settings["chart_id"] === "0" || empty($settings["chart_id"])) ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($settings["chart_id"]);
		if ( ! $def ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">Widget Data Missing</h3>';
				echo '<p style="color:#94a3b8; margin:0;">Please select a specific chart from the Content settings. (Dynamic mode only works on Single Chart templates).</p>';
				echo '</div>';
			}
			return;
		}

		$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 5);
		if ( empty($entries) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">No Chart Data Found</h3>';
				echo '<p style="color:#94a3b8; margin:0;">The selected chart does not have any active entries to display.</p>';
				echo '</div>';
			}
			return;
		}

		$uid = 'kc-t5-' . $this->get_id();
		
		echo '<style>
		.' . $uid . '-wrap {
			position: relative;
			width: 100%;
			background: #000;
			overflow: hidden;
			border-radius: 20px;
			padding: 40px;
			font-family: "Cairo", sans-serif;
			direction: rtl;
			color: #fff;
			--t5-accent: ' . ($settings['accent_color'] ?: '#ff0055') . ';
			--t5-link: ' . ($settings['link_color'] ?: '#00ffaa') . ';
		}
		.' . $uid . '-bg-text {
			position: absolute;
			top: 20%;
			left: 5%;
			font-size: 160px;
			font-weight: 950;
			color: rgba(255,255,255,0.03);
			text-transform: uppercase;
			white-space: nowrap;
			pointer-events: none;
			line-height: 1;
			font-family: "Inter", sans-serif;
			letter-spacing: -2px;
		}
		.' . $uid . '-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
			margin-bottom: 30px;
			position: relative;
			z-index: 10;
		}
		.' . $uid . '-title-group {
			display: flex;
			align-items: center;
			gap: 16px;
		}
		.' . $uid . '-eq-icon {
			color: var(--t5-accent);
		}
		.' . $uid . '-titles h2 {
			font-size: 56px;
			font-weight: 900;
			margin: 0;
			line-height: 1;
			color: #fff;
		}
		.' . $uid . '-titles p {
			font-size: 16px;
			font-weight: 600;
			color: #a1a1aa;
			margin: 4px 0 0 0;
			letter-spacing: 1px;
		}
		.' . $uid . '-link {
			color: var(--t5-link);
			text-decoration: none;
			font-size: 18px;
			font-weight: 700;
			display: flex;
			align-items: center;
			gap: 8px;
			transition: opacity 0.2s;
		}
		.' . $uid . '-link:hover { opacity: 0.8; }
		.' . $uid . '-link svg { width: 20px; height: 20px; }
		
		.' . $uid . '-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 24px;
			position: relative;
			z-index: 10;
		}
		
		/* HERO CARD (Right) */
		.' . $uid . '-hero {
			position: relative;
			border-radius: 20px;
			overflow: hidden;
			background: #111;
			min-height: 400px;
		}
		.' . $uid . '-hero-bg {
			position: absolute;
			inset: 0;
			background-size: cover;
			background-position: center;
			background-image: var(--bg-img);
		}
		.' . $uid . '-hero-overlay {
			position: absolute;
			inset: 0;
			background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.1) 50%, transparent 100%);
		}
		.' . $uid . '-badge {
			position: absolute;
			top: 20px;
			right: 20px;
			background: var(--t5-accent);
			color: #fff;
			font-size: 24px;
			font-weight: 900;
			padding: 8px 16px;
			border-radius: 12px;
			font-family: "Inter", sans-serif;
		}
		.' . $uid . '-rank-big {
			position: absolute;
			top: -10px;
			left: 20px;
			font-size: 140px;
			font-weight: 900;
			color: #fff;
			line-height: 1;
			text-shadow: 0 10px 30px rgba(0,0,0,0.5);
		}
		.' . $uid . '-hero-content {
			position: absolute;
			bottom: 24px;
			right: 24px;
			left: 24px;
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
		}
		.' . $uid . '-hero-text h3 {
			font-size: 40px;
			font-weight: 900;
			color: #fff;
			margin: 0 0 4px 0;
			line-height: 1.1;
		}
		.' . $uid . '-hero-text p {
			font-size: 20px;
			font-weight: 600;
			color: #e2e8f0;
			margin: 0;
		}
		.' . $uid . '-play-btn {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			background: #000;
			border: 2px solid var(--t5-accent);
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			cursor: pointer;
			transition: transform 0.2s, box-shadow 0.2s;
			box-shadow: 0 0 15px rgba(255,0,85,0.4);
		}
		.' . $uid . '-play-btn:hover {
			transform: scale(1.1);
			box-shadow: 0 0 25px rgba(255,0,85,0.6);
		}
		
		/* LIST CARDS (Left) */
		.' . $uid . '-list {
			display: flex;
			flex-direction: column;
			gap: 12px;
			justify-content: space-between;
		}
		.' . $uid . '-row {
			background: rgba(255,255,255,0.03);
			border: 1px solid rgba(255,255,255,0.05);
			border-radius: 16px;
			padding: 12px 20px;
			display: flex;
			align-items: center;
			gap: 16px;
			transition: background 0.2s;
			backdrop-filter: blur(10px);
		}
		.' . $uid . '-row:hover {
			background: rgba(255,255,255,0.06);
		}
		.' . $uid . '-r-rank {
			font-size: 40px;
			font-weight: 900;
			color: #fff;
			width: 40px;
			text-align: center;
			line-height: 1;
		}
		.' . $uid . '-r-move {
			display: flex;
			flex-direction: column;
			align-items: center;
			width: 30px;
			font-family: "Inter", sans-serif;
			font-weight: 800;
			font-size: 14px;
		}
		.' . $uid . '-move-up { color: #10b981; }
		.' . $uid . '-move-down { color: #f43f5e; }
		.' . $uid . '-move-new { color: #64748b; }
		
		.' . $uid . '-r-img {
			width: 64px;
			height: 64px;
			border-radius: 8px;
			object-fit: cover;
		}
		.' . $uid . '-r-info {
			flex: 1;
		}
		.' . $uid . '-r-info h4 {
			font-size: 20px;
			font-weight: 800;
			color: #fff;
			margin: 0 0 4px 0;
		}
		.' . $uid . '-r-info p {
			font-size: 14px;
			color: #94a3b8;
			margin: 0;
		}
		.' . $uid . '-r-play {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: rgba(255,255,255,0.1);
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			cursor: pointer;
			transition: background 0.2s;
		}
		.' . $uid . '-row:hover .' . $uid . '-r-play {
			background: #fff;
			color: #000;
		}
		
		@media (max-width: 991px) {
			.' . $uid . '-grid { grid-template-columns: 1fr; }
			.' . $uid . '-header { flex-direction: column; align-items: flex-start; gap: 16px; }
			.' . $uid . '-hero { min-height: 350px; }
		}
		</style>';

		echo '<div class="' . $uid . '-wrap kc-t5-wrap">';
		if (!empty($settings['bg_text'])) {
			echo '<div class="' . $uid . '-bg-text kc-t5-bg-text">' . esc_html($settings['bg_text']) . '</div>';
		}
		
		// Header
		echo '<div class="' . $uid . '-header kc-t5-header">';
		echo '<div class="' . $uid . '-title-group kc-t5-title-group">';
		echo '<svg class="' . $uid . '-eq-icon kc-t5-eq-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10h3v4H4v-4zm5-4h3v12H9V6zm5 6h3v6h-3v-6zm5-2h3v4h-3v-4z"/></svg>';
		echo '<div class="' . $uid . '-titles kc-t5-titles">';
		echo '<h2>' . esc_html($settings['title']) . '</h2>';
		if (!empty($settings['subtitle'])) {
			echo '<p>' . esc_html($settings['subtitle']) . '</p>';
		}
		echo '</div></div>';
		
		if (!empty($settings['link_text'])) {
			$url = !empty($settings['link_url']['url']) ? $settings['link_url']['url'] : home_url('/charts/' . $def->slug);
			echo '<a href="' . esc_url($url) . '" class="' . $uid . '-link kc-t5-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> ' . esc_html($settings['link_text']) . '</a>';
		}
		echo '</div>'; // End Header

		echo '<div class="' . $uid . '-grid kc-t5-grid">';
		
		// RIGHT COLUMN (HERO #1)
		$hero = $entries[0];
		$h_img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$hero]);
		if (empty($h_img) && !empty($hero->resolved_image)) $h_img = $hero->resolved_image;
		if (empty($h_img)) $h_img = CHARTS_URL . 'public/assets/img/placeholder.png';
		$h_res = \Charts\Core\PublicIntegration::resolve_display_name($hero, $def);
		
		echo '<div class="' . $uid . '-hero kc-t5-hero" style="--bg-img: url(\'' . esc_url($h_img) . '\');">';
		echo '<div class="' . $uid . '-hero-bg kc-t5-hero-bg"></div>';
		echo '<div class="' . $uid . '-hero-overlay kc-t5-hero-overlay"></div>';
		echo '<div class="' . $uid . '-hero-scribble kc-t5-hero-scribble" style="position:absolute; inset:0; z-index:2; mix-blend-mode: overlay; opacity: 0.6; background: radial-gradient(circle at top left, var(--t5-accent) 0%, transparent 50%), radial-gradient(circle at bottom right, #0000ff 0%, transparent 50%);"></div>';
		
		echo '<div class="' . $uid . '-badge kc-t5-badge">#1</div>';
		echo '<div class="' . $uid . '-rank-big kc-t5-rank-big">' . \Charts\Core\Transliteration::to_arabic_numerals(1) . '</div>';
		
		echo '<div class="' . $uid . '-hero-content kc-t5-hero-content">';
		echo '<div class="' . $uid . '-hero-text kc-t5-hero-text">';
		echo '<h3>' . esc_html($h_res['title']) . '</h3>';
		echo '<p>' . esc_html($h_res['subtitle']) . '</p>';
		echo '</div>';
		echo '<div class="' . $uid . '-play-btn kc-t5-play-btn"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>';
		echo '</div>';
		echo '</div>'; // End Hero
		
		// LEFT COLUMN (LIST #2-#5)
		echo '<div class="' . $uid . '-list kc-t5-list">';
		for ($i = 1; $i < count($entries); $i++) {
			$e = $entries[$i];
			$r_img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$e]);
			if (empty($r_img) && !empty($e->resolved_image)) $r_img = $e->resolved_image;
			if (empty($r_img)) $r_img = CHARTS_URL . 'public/assets/img/placeholder.png';
			$r_res = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
			
			$move = $e->movement_value;
			$dir = $e->movement_direction;
			$move_html = '';
			if ($dir === 'up') $move_html = '<span class="' . $uid . '-move-up kc-t5-move-up">↑<br>+' . \Charts\Core\Transliteration::to_arabic_numerals($move) . '</span>';
			elseif ($dir === 'down') $move_html = '<span class="' . $uid . '-move-down kc-t5-move-down">↓<br>-' . \Charts\Core\Transliteration::to_arabic_numerals($move) . '</span>';
			else $move_html = '<span class="' . $uid . '-move-new kc-t5-move-new">→<br>-</span>';
			
			echo '<div class="' . $uid . '-row kc-t5-row">';
			echo '<div class="' . $uid . '-r-rank kc-t5-r-rank">' . \Charts\Core\Transliteration::to_arabic_numerals($i + 1) . '</div>';
			echo '<div class="' . $uid . '-r-move kc-t5-r-move">' . $move_html . '</div>';
			echo '<img src="' . esc_url($r_img) . '" class="' . $uid . '-r-img kc-t5-r-img">';
			echo '<div class="' . $uid . '-r-info kc-t5-r-info">';
			echo '<h4>' . esc_html($r_res['title']) . '</h4>';
			echo '<p>' . esc_html($r_res['subtitle']) . '</p>';
			echo '</div>';
			echo '<div class="' . $uid . '-r-play kc-t5-r-play"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>';
			echo '</div>';
		}
		echo '</div>'; // End List
		
		echo '</div>'; // End Grid
		echo '</div>'; // End Wrap
	}
}
