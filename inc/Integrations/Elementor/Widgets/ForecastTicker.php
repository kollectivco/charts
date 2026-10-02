<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ForecastTicker extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_forecast_ticker'; }
	public function get_title() { return __( 'Charts: Forecast Ticker', 'charts' ); }
	public function get_icon() { return 'eicon-marquee'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// ── Content Tab ────────────────────────────────────────────────
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Ticker Settings', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'notice', [
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => __( 'Shows the top 10 songs with highest momentum.', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );

		$this->add_control( 'ticker_speed', [
			'label'   => __( 'Ticker Speed (seconds)', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 30,
			'min'     => 5,
			'max'     => 120,
		] );

		$this->end_controls_section();

		$this->add_premium_badge_controls();

		// ── Style Tab ─────────────────────────────────────────────────
		// Ticker BG Color — targets the static wrapper class .kc-ft-wrap
		$this->start_controls_section( 'style_ticker', [
			'label' => __( 'Ticker Style', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'ticker_bg_color', [
			'label'     => __( 'Ticker Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-wrap' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'ticker_text_color', [
			'label'     => __( 'Item Text Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-item' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'ticker_title_color', [
			'label'     => __( 'Title Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-title' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'ticker_artist_color', [
			'label'     => __( 'Artist Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-artist' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'ticker_score_color', [
			'label'     => __( 'Score Badge Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-score' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'ticker_score_bg', [
			'label'     => __( 'Score Badge BG', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ft-score' => 'background: {{VALUE}};' ],
		] );

		$this->end_controls_section();
		$this->add_advanced_image_controls();
	}

	protected function render() {
		global $wpdb;
		$intel   = $wpdb->prefix . 'charts_intelligence';
		$tracks  = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		$settings = $this->get_settings_for_display();
		$speed    = absint( $settings['ticker_speed'] ) ?: 30;
		$uid      = 'kc-ft-' . $this->get_id();

		$results = $wpdb->get_results( "SELECT i.momentum_score, t.title, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' ORDER BY i.momentum_score DESC LIMIT 10" );

		echo '<div class="kc-widget-wrap">';

		if ( empty( $results ) ) {
			echo '<div style="padding:20px; background:#f8fafc; text-align:center; border:1px dashed #cbd5e1; border-radius:8px; color:#64748b;">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"><path d="M9 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-4m-4 4v-4"/></svg>';
			echo '<p style="margin:0;">' . __( 'No forecast data available yet. Add intelligence data to see the ticker.', 'charts' ) . '</p>';
			echo '</div>';
			echo '</div>';
			return;
		}

		// Inline animation speed uses the user-controlled value.
		// Static class names (kc-ft-wrap, kc-ft-item, etc.) allow Elementor
		// style controls to override colours without specificity conflicts.
		echo '<style>
		.' . $uid . '-track { animation: kc-ticker-' . $uid . ' ' . $speed . 's linear infinite; }
		.' . $uid . '-track:hover { animation-play-state: paused; }
		@keyframes kc-ticker-' . $uid . ' { 0% { transform: translate3d(0,0,0); } 100% { transform: translate3d(-50%,0,0); } }
		</style>';

		$items_html = '';
		foreach ( $results as $r ) {
			$hot_badge = ( $settings['show_badges'] === 'yes' && $r->momentum_score >= 80 )
				? '<span style="color:#ef4444;margin-right:4px;">' . esc_html( $settings['badge_hot_text'] ) . '</span>'
				: '';

			$items_html .= '<div class="kc-ft-item">'
				. '<span class="kc-ft-title">' . $hot_badge . esc_html( $r->title ) . '</span>'
				. '<span class="kc-ft-artist">بواسطة ' . esc_html( $r->artist ) . '</span>'
				. '<span class="kc-ft-score">'
				. '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>'
				. intval( $r->momentum_score )
				. '</span>'
				. '</div>';
		}

		echo '<div class="kc-ft-wrap">';
		echo '<div class="' . $uid . '-track" style="display:flex;white-space:nowrap;">';
		echo $items_html . $items_html; // Duplicate for infinite scroll effect
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
}
