<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class ForecastTicker extends Widget_Base {

	public function get_name() { return 'kc_forecast_ticker'; }
	public function get_title() { return __( 'Forecast & Viral Ticker', 'charts' ); }
	public function get_icon() { return 'eicon-marquee'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Ticker Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'speed', [
			'label' => __( 'Animation Speed (Seconds)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 30,
			'min' => 10,
			'max' => 100,
		] );

		$this->end_controls_section();
	}

	protected function render() {
		global $wpdb;
		$speed = $this->get_settings_for_display('speed') ?: 30;
		$uid = 'kc-tck-' . $this->get_id();

		$intel = $wpdb->prefix . 'charts_intelligence';
		$tracks = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		// Fetch viral tracks (momentum > 70)
		$viral = $wpdb->get_results("SELECT i.momentum_score, t.title, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score >= 70 ORDER BY i.momentum_score DESC LIMIT 5");
		
		// Fetch fastest risers (growth_rate > 50)
		$risers = $wpdb->get_results("SELECT i.growth_rate, t.title, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.growth_rate >= 50 ORDER BY i.growth_rate DESC LIMIT 5");

		$messages = [];
		foreach ($viral as $v) {
			$messages[] = '🔥 <span class="kc-tck-hl">' . esc_html($v->title) . '</span> by ' . esc_html($v->artist) . ' is EXPLODING (Momentum: ' . intval($v->momentum_score) . ')';
		}
		foreach ($risers as $r) {
			$messages[] = '🚀 <span class="kc-tck-hl">' . esc_html($r->title) . '</span> is a FAST RISER (+ ' . intval($r->growth_rate) . '% Growth)';
		}

		if (empty($messages)) {
			$messages[] = '⚡ AI Intelligence Engine is monitoring market shifts...';
			$messages[] = '📊 Waiting for enough data to generate trend forecasts...';
		}

		$marquee_content = implode('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; • &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $messages);

		echo '<style>
		.' . $uid . '-wrap { width: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 12px 0; overflow: hidden; position: relative; color: #cbd5e1; font-family: "Fira Code", monospace, sans-serif; font-size: 13px; font-weight: 500; letter-spacing: 0.05em; display: flex; align-items: center; }
		.' . $uid . '-wrap::before { content: "AI FORECAST"; position: absolute; left: 0; top: 0; bottom: 0; background: #6366f1; color: #fff; font-weight: 900; font-family: "Inter", sans-serif; display: flex; align-items: center; padding: 0 16px; z-index: 2; font-size: 11px; letter-spacing: 0.1em; box-shadow: 4px 0 12px rgba(0,0,0,0.5); }
		.' . $uid . '-ticker { white-space: nowrap; padding-left: 120px; display: inline-block; animation: ' . $uid . '-scroll ' . $speed . 's linear infinite; }
		.' . $uid . '-ticker:hover { animation-play-state: paused; }
		.kc-tck-hl { color: #fff; font-weight: 800; }
		@keyframes ' . $uid . '-scroll {
			0% { transform: translateX(100%); }
			100% { transform: translateX(-100%); }
		}
		</style>';

		echo '<div class="' . $uid . '-wrap">';
		echo '<div class="' . $uid . '-ticker">';
		echo $marquee_content;
		echo '</div>';
		echo '</div>';
	}
}
