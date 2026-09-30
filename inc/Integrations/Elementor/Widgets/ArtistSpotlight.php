<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class ArtistSpotlight extends Widget_Base {

	public function get_name() { return 'kc_artist_spotlight'; }
	public function get_title() { return __( 'Artist Power Spotlight', 'charts' ); }
	public function get_icon() { return 'eicon-person'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Spotlight Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => __( 'This widget automatically displays the #1 Artist based on the Intelligence Engine Power Score.', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style_section', [
			'label' => __( 'Styling', 'charts' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'card_bg', [
			'label' => __( 'Background Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-as-wrap' => 'background: {{VALUE}};' ],
		] );

		$this->add_control( 'card_radius', [
			'label' => __( 'Border Radius', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-as-wrap' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		global $wpdb;
		$intel_table = $wpdb->prefix . 'charts_intelligence';
		$artists_table = $wpdb->prefix . 'charts_artists';
		
		$artist = $wpdb->get_row("SELECT i.*, a.display_name, a.image FROM $intel_table i JOIN $artists_table a ON a.id = i.entity_id WHERE i.entity_type = 'artist' AND i.artist_power_score > 0 ORDER BY i.artist_power_score DESC LIMIT 1");

		echo '<div class="kc-widget-wrap">';
		if (!$artist) {
			echo '<div style="padding:20px; text-align:center; background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1; color:#64748b; font-size:13px;">No artist intelligence data available.</div>';
			echo '</div>';
			return;
		}

		$uid = 'kc-as-' . $this->get_id();
		$img = $artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
		$score = intval($artist->artist_power_score);
		$max_score = 1000;
		$pct = min(100, max(0, ($score / $max_score) * 100));
		$dasharray = 283;
		$dashoffset = $dasharray - ($dasharray * $pct / 100);

		echo '<style>
		.' . $uid . '-wrap { background: linear-gradient(145deg, #ffffff, #f8fafc); border: 1px solid #e2e8f0; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; position: relative; overflow: hidden; }
		.' . $uid . '-wrap::before { content:""; position:absolute; top:0; left:0; right:0; height:4px; background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899); }
		.' . $uid . '-gauge-box { position: relative; width: 160px; height: 160px; margin: 0 auto 24px auto; }
		.' . $uid . '-img { position: absolute; top: 15px; left: 15px; width: 130px; height: 130px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
		.' . $uid . '-svg { transform: rotate(-90deg); width: 160px; height: 160px; }
		.' . $uid . '-circle-bg { fill: none; stroke: #f1f5f9; stroke-width: 6; }
		.' . $uid . '-circle-fill { fill: none; stroke: url(#grad-' . $uid . '); stroke-width: 6; stroke-linecap: round; stroke-dasharray: ' . $dasharray . '; stroke-dashoffset: ' . $dashoffset . '; animation: kc-fill-gauge-' . $uid . ' 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
		@keyframes kc-fill-gauge-' . $uid . ' { from { stroke-dashoffset: ' . $dasharray . '; } }
		.' . $uid . '-badge { display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #ef4444; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; padding: 6px 12px; border-radius: 20px; margin-bottom: 16px; }
		.' . $uid . '-name { font-size: 28px; font-weight: 900; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.02em; }
		.' . $uid . '-sub { font-size: 14px; color: #64748b; margin: 0 0 24px 0; }
		.' . $uid . '-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
		.' . $uid . '-stat-card { background: #fff; border: 1px solid #f1f5f9; padding: 16px; border-radius: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
		.' . $uid . '-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); border-color: #e2e8f0; }
		.' . $uid . '-stat-val { font-size: 22px; font-weight: 900; color: #0f172a; line-height: 1; margin-bottom: 6px; }
		.' . $uid . '-stat-lbl { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
		</style>';

		echo '<div class="' . $uid . '-wrap kc-as-wrap">';
		echo '<div class="' . $uid . '-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> Top Artist Authority</div>';
		echo '<div class="' . $uid . '-gauge-box">';
		echo '<img src="' . esc_url($img) . '" class="' . $uid . '-img">';
		echo '<svg class="' . $uid . '-svg" viewBox="0 0 100 100">';
		echo '<defs><linearGradient id="grad-' . $uid . '" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#6366f1" /><stop offset="100%" stop-color="#ec4899" /></linearGradient></defs>';
		echo '<circle class="' . $uid . '-circle-bg" cx="50" cy="50" r="45"></circle>';
		echo '<circle class="' . $uid . '-circle-fill" cx="50" cy="50" r="45"></circle>';
		echo '</svg></div>';
		echo '<h2 class="' . $uid . '-name">' . esc_html($artist->display_name) . '</h2>';
		echo '<p class="' . $uid . '-sub">Composite Power Score: <strong style="color:#6366f1;">' . number_format($score) . '</strong></p>';
		echo '<div class="' . $uid . '-stats">';
		echo '<div class="' . $uid . '-stat-card"><div class="' . $uid . '-stat-val">' . intval($artist->weeks_on_chart) . '</div><div class="' . $uid . '-stat-lbl">Chart Wks</div></div>';
		echo '<div class="' . $uid . '-stat-card"><div class="' . $uid . '-stat-val">#' . intval($artist->peaks_count) . '</div><div class="' . $uid . '-stat-lbl">Peak Rank</div></div>';
		$streams = intval($artist->total_streams);
		$streams_fmt = $streams > 1000000 ? round($streams/1000000, 1) . 'M' : ($streams > 1000 ? round($streams/1000, 1) . 'K' : $streams);
		echo '<div class="' . $uid . '-stat-card"><div class="' . $uid . '-stat-val">' . $streams_fmt . '</div><div class="' . $uid . '-stat-lbl">Index Vol</div></div>';
		echo '</div></div></div>';
	}
}
