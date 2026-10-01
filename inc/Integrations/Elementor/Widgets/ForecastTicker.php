<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ForecastTicker extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_forecast_ticker'; }
	public function get_title() { return __( 'Forecast Ticker', 'charts' ); }
	public function get_icon() { return 'eicon-marquee'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Ticker Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );
		$this->add_control( 'notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => __( 'Shows the top 10 songs with highest momentum.', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );
		$this->end_controls_section();

		$this->add_premium_badge_controls();
		$this->add_granular_style_controls(['card', 'title', 'meta']);
	}

	protected function render() {
		global $wpdb;
		$intel = $wpdb->prefix . 'charts_intelligence';
		$tracks = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		$results = $wpdb->get_results("SELECT i.momentum_score, t.title, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' ORDER BY i.momentum_score DESC LIMIT 10");

		$uid = 'kc-ft-' . $this->get_id();
		$settings = $this->get_settings_for_display();

		echo '<div class="kc-widget-wrap">';
		if (empty($results)) {
			echo '<div style="padding:10px; background:#f8fafc; text-align:center; border:1px dashed #cbd5e1; border-radius:8px; color:#64748b;">No forecast data.</div></div>';
			return;
		}

		echo '<style>
		.' . $uid . '-ticker-wrap { width: 100%; overflow: hidden; background: #0f172a; padding: 12px 0; border-radius: 12px; display: flex; align-items: center; }
		.' . $uid . '-ticker { display: flex; white-space: nowrap; animation: kc-ticker-' . $uid . ' 30s linear infinite; }
		.' . $uid . '-ticker:hover { animation-play-state: paused; }
		@keyframes kc-ticker-' . $uid . ' { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-50%, 0, 0); } }
		.' . $uid . '-item { display: inline-flex; align-items: center; gap: 8px; padding: 0 24px; border-right: 1px solid rgba(255,255,255,0.1); }
		.' . $uid . '-title { font-size: 14px; font-weight: 800; color: #fff; }
		.' . $uid . '-artist { font-size: 13px; color: #94a3b8; font-weight: 500; }
		.' . $uid . '-score { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 900; color: #10b981; margin-left: 8px; background: rgba(16,185,129,0.15); padding: 2px 6px; border-radius: 4px; }
		</style>';

		$items_html = '';
		foreach ($results as $r) {
			$hot_badge = ($settings['show_badges'] === 'yes' && $r->momentum_score >= 80) ? '<span style="color:#ef4444;margin-right:4px;">' . esc_html($settings['badge_hot_text']) . '</span>' : '';
			$items_html .= '<div class="' . $uid . '-item"><span class="' . $uid . '-title kc-elm-title">' . $hot_badge . esc_html($r->title) . '</span><span class="' . $uid . '-artist kc-elm-artist">بواسطة ' . esc_html($r->artist) . '</span><span class="' . $uid . '-score kc-elm-meta"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>' . intval($r->momentum_score) . '</span></div>';
		}

		echo '<div class="' . $uid . '-ticker-wrap kc-elm-card">';
		echo '<div class="' . $uid . '-ticker">';
		echo $items_html . $items_html; // Duplicate for infinite scroll effect
		echo '</div></div></div>';
	}
}
