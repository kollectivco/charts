<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class BentoIntelligence extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_bento_intelligence'; }
	public function get_title() { return __( 'Charts: Bento Intelligence Hub', 'charts' ); }
	public function get_icon() { return 'eicon-dashboard'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// ── Content Tab ─────────────────────────────────────────────────
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content Settings', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );
		$this->add_control( 'notice', [
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => __( 'Automatically builds a Bento grid from the Intelligence Engine.', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );
		$this->end_controls_section();

		$this->add_premium_badge_controls();
		$this->add_premium_layout_controls();

		// ── Bento Heights ────────────────────────────────────────────────
		$this->start_controls_section( 'bento_heights', [
			'label' => __( 'Bento Heights', 'charts' ),
			'tab'   => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		$this->add_responsive_control( 'card_height', [
			'label'     => __( 'Main Card Height', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 200, 'max' => 800 ] ],
			// .kc-bh-main is applied to the main card in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-bh-main' => 'min-height: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'side_card_height', [
			'label'     => __( 'Side Card Height', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 100, 'max' => 600 ] ],
			// kc-bh-side added to side cards in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-bh-side' => 'min-height: {{SIZE}}{{UNIT}};' ],
		] );
		$this->end_controls_section();

		// ── Granular Style Controls ──────────────────────────────────────
		// Selectors generated:
		//  .kc-elm-card  → card wrap divs in render() ✅
		//  .kc-elm-img   → bg divs with class kc-elm-img in render() ✅
		//  .kc-elm-title → h3 elements in render() ✅
		//  .kc-elm-artist→ p elements in render() ✅
		//  .kc-elm-meta  → used by meta_color control (via kc-elm-meta) —
		//                   not rendered, harmless extra control.
		$this->add_granular_style_controls( ['card', 'image', 'title', 'meta'] );

		// ── Bento Card Style ─────────────────────────────────────────────
		$this->start_controls_section( 'style_bento_card', [
			'label' => __( 'Bento Card Details', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'card_default_bg', [
			'label'     => __( 'Card Default Background', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// .kc-elm-card is on every card ✅
			'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'card_border_radius', [
			'label'      => __( 'Card Border Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'selectors'  => [ '{{WRAPPER}} .kc-elm-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'card_border_color', [
			'label'     => __( 'Card Border Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'border-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Overlay & Badge ───────────────────────────────────────────────
		$this->start_controls_section( 'style_overlay_badge', [
			'label' => __( 'Overlay & Badge', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'overlay_color', [
			'label'     => __( 'Overlay Start Color (Bottom)', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// .kc-elm-overlay is on the overlay divs in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'background: linear-gradient(to top, {{VALUE}} 0%, rgba(0,0,0,0) 100%);' ],
		] );

		$this->add_control( 'badge_bg_color', [
			'label'     => __( 'Badge Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// .kc-bh-badge is added to all badge divs in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-bh-badge' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'badge_text_color', [
			'label'     => __( 'Badge Text Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-bh-badge' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'badge_border_radius', [
			'label'     => __( 'Badge Border Radius', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-bh-badge' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Artist / Subtitle Text ────────────────────────────────────────
		$this->start_controls_section( 'style_artist_text', [
			'label' => __( 'Artist / Subtitle Text', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'artist_font_size', [
			'label'     => __( 'Font Size', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 10, 'max' => 36 ] ],
			// .kc-elm-artist is on the artist <p> tags in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-elm-artist' => 'font-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'artist_color_override', [
			'label'     => __( 'Artist Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-artist' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'title_font_size', [
			'label'     => __( 'Title Font Size', 'charts' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 14, 'max' => 72 ] ],
			// .kc-elm-title on h3 tags in render() ✅
			'selectors' => [ '{{WRAPPER}} .kc-elm-card .kc-elm-title' => 'font-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
		$this->add_advanced_image_controls('{{WRAPPER}} .kc-elm-img', '{{WRAPPER}} .kc-elm-img');
	}

	protected function render() {
		global $wpdb;
		$intel   = $wpdb->prefix . 'charts_intelligence';
		$tracks  = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		$top_song  = $wpdb->get_row( "SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' ORDER BY i.momentum_score DESC LIMIT 1" );
		$top_artist = $wpdb->get_row( "SELECT i.artist_power_score, a.display_name as artist, a.image FROM $intel i JOIN $artists a ON a.id = i.entity_id WHERE i.entity_type = 'artist' ORDER BY i.artist_power_score DESC LIMIT 1" );
		$breakout  = $wpdb->get_row( "SELECT i.growth_rate, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score < 80 ORDER BY i.growth_rate DESC LIMIT 1" );

		$uid        = 'kc-bh-' . $this->get_id();
		$settings   = $this->get_settings_for_display();
		$hover_anim = $settings['hover_animation'] ?? 'zoom';

		echo '<div class="kc-widget-wrap">';

		// Editor placeholder — shown when Intelligence Engine has no data
		if ( ! $top_song && ! $top_artist && ! $breakout ) {
			echo '<div style="padding:40px 20px; text-align:center; border:1px dashed #cbd5e1; border-radius:16px; color:#64748b; background:#f8fafc;">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
			echo '<strong style="display:block;margin-bottom:6px;font-size:15px;color:#475569;">' . __( 'Bento Intelligence Hub', 'charts' ) . '</strong>';
			echo '<p style="margin:0;font-size:13px;">' . __( 'No intelligence data found. Run the Intelligence Engine to populate this widget.', 'charts' ) . '</p>';
			echo '</div>';
			echo '</div>';
			return;
		}

		echo '<style>
		.' . $uid . '-grid { display: grid; grid-auto-rows: minmax(160px, auto); font-family: "Inter", sans-serif; }
		.' . $uid . '-card { position: relative; overflow: hidden; background: #0f172a; display: flex; flex-direction: column; justify-content: flex-end; padding: 24px; transition: all 0.4s; border-radius: 20px; }
		.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.6; transition: transform 0.6s; z-index: 1; }
		.' . $uid . '-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.2) 100%); z-index: 2; }
		.' . $uid . '-content { position: relative; z-index: 3; }
		.' . $uid . '-main { grid-column: span 2; grid-row: span 2; min-height: 400px; }
		.' . $uid . '-side { grid-column: span 1; grid-row: span 1; min-height: 192px; }

		@media (max-width: 768px) {
			.' . $uid . '-main { grid-column: span 1; grid-row: span 1; min-height: 300px; }
			.' . $uid . '-side { grid-column: span 1; min-height: 180px; }
		}

		.' . $uid . '-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); color: #fff; font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 12px; margin-bottom: 12px; }
		.' . $uid . '-title { font-size: 32px; font-weight: 900; color: #fff; line-height: 1.1; margin: 0 0 4px 0; }
		.' . $uid . '-artist { font-size: 16px; font-weight: 500; color: #cbd5e1; margin: 0; }
		.' . $uid . '-side .' . $uid . '-title { font-size: 20px; }
		';

		if ( $hover_anim === 'zoom' ) {
			echo '.' . $uid . '-card:hover .' . $uid . '-bg { transform: scale(1.1); }';
		} elseif ( $hover_anim === 'lift' ) {
			echo '.' . $uid . '-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }';
		}

		echo '</style>';

		// .kc-grid-root → targeted by add_premium_layout_controls grid_columns / grid_gap ✅
		echo '<div class="' . $uid . '-grid kc-grid-root">';

		if ( $top_song ) {
			$img = ( ! empty( $top_song->resolved_image ) ? $top_song->resolved_image : $top_song->cover_image ) ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			// Classes on card: kc-elm-card ✅ | kc-bh-main (card_height selector) ✅
			// Classes on bg div: kc-elm-img ✅ | kc-elm-overlay ✅
			// Badge: kc-bh-badge ✅ | Title: kc-elm-title ✅ | Artist: kc-elm-artist ✅
			echo '<div class="' . $uid . '-card kc-elm-card ' . $uid . '-main kc-bh-main">'
				. '<div class="' . $uid . '-bg kc-elm-img" style="background-image:url(\'' . esc_url( $img ) . '\');"></div>'
				. '<div class="' . $uid . '-overlay kc-elm-overlay"></div>'
				. '<div class="' . $uid . '-content">'
				. '<div class="' . $uid . '-badge kc-bh-badge">👑 أغنية الأسبوع</div>'
				. '<h3 class="' . $uid . '-title kc-elm-title">' . esc_html( $top_song->title ) . '</h3>'
				. '<p class="' . $uid . '-artist kc-elm-artist">' . esc_html( $top_song->artist ) . '</p>'
				. '</div></div>';
		}

		if ( $top_artist ) {
			$img = $top_artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			echo '<div class="' . $uid . '-card kc-elm-card ' . $uid . '-side kc-bh-side">'
				. '<div class="' . $uid . '-bg kc-elm-img" style="background-image:url(\'' . esc_url( $img ) . '\');"></div>'
				. '<div class="' . $uid . '-overlay kc-elm-overlay"></div>'
				. '<div class="' . $uid . '-content">'
				. '<div class="' . $uid . '-badge kc-bh-badge" style="background:rgba(236,72,153,0.3);color:#f472b6;">⭐ صدارة الفنانين</div>'
				. '<h3 class="' . $uid . '-title kc-elm-title">' . esc_html( $top_artist->artist ) . '</h3>'
				. '<p class="' . $uid . '-artist kc-elm-artist">النقاط: ' . intval( $top_artist->artist_power_score ) . '</p>'
				. '</div></div>';
		}

		if ( $breakout ) {
			$img = ( ! empty( $breakout->resolved_image ) ? $breakout->resolved_image : $breakout->cover_image ) ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			echo '<div class="' . $uid . '-card kc-elm-card ' . $uid . '-side kc-bh-side">'
				. '<div class="' . $uid . '-bg kc-elm-img" style="background-image:url(\'' . esc_url( $img ) . '\');"></div>'
				. '<div class="' . $uid . '-overlay kc-elm-overlay"></div>'
				. '<div class="' . $uid . '-content">'
				. '<div class="' . $uid . '-badge kc-bh-badge" style="background:rgba(16,185,129,0.3);color:#34d399;">🚀 الأسرع صعوداً</div>'
				. '<h3 class="' . $uid . '-title kc-elm-title">' . esc_html( $breakout->title ) . '</h3>'
				. '<p class="' . $uid . '-artist kc-elm-artist">+' . intval( $breakout->growth_rate ) . '% نمو</p>'
				. '</div></div>';
		}

		echo '</div></div>';
	}
}
