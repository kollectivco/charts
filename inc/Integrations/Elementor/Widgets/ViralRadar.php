<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class ViralRadar extends Widget_Base {

	public function get_name() { return 'kc_viral_radar'; }
	public function get_title() { return __( 'Viral Radar Heatmap', 'charts' ); }
	public function get_icon() { return 'eicon-wifi'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Radar Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'limit_per_col', [
			'label' => __( 'Tracks Per Column', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 4,
			'min' => 1,
			'max' => 10,
		] );

		$this->end_controls_section();
	}

	protected function render() {
		global $wpdb;
		$limit = $this->get_settings_for_display('limit_per_col') ?: 4;
		
		$intel = $wpdb->prefix . 'charts_intelligence';
		$tracks = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		// Query Exploding (momentum >= 80)
		$exploding = $wpdb->get_results($wpdb->prepare("SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score >= 80 ORDER BY i.momentum_score DESC LIMIT %d", $limit));
		
		// Query Rising (60-79)
		$rising = $wpdb->get_results($wpdb->prepare("SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score >= 60 AND i.momentum_score < 80 ORDER BY i.momentum_score DESC LIMIT %d", $limit));
		
		// Query Emerging (40-59)
		$emerging = $wpdb->get_results($wpdb->prepare("SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score >= 40 AND i.momentum_score < 60 ORDER BY i.momentum_score DESC LIMIT %d", $limit));

		$uid = 'kc-vr-' . $this->get_id();

		echo '<style>
		.' . $uid . '-wrap { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
		@media (max-width: 900px) { .' . $uid . '-wrap { grid-template-columns: 1fr; } }
		.' . $uid . '-col { background: #f8fafc; border-radius: 20px; padding: 24px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
		.' . $uid . '-col::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
		.' . $uid . '-col.col-exp::before { background: #ef4444; }
		.' . $uid . '-col.col-ris::before { background: #f59e0b; }
		.' . $uid . '-col.col-emg::before { background: #10b981; }
		.' . $uid . '-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
		.' . $uid . '-title { font-size: 16px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px; margin: 0; }
		.' . $uid . '-icon { font-size: 20px; }
		.' . $uid . '-count { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; }
		.' . $uid . '-list { display: flex; flex-direction: column; gap: 12px; }
		.' . $uid . '-item { display: flex; align-items: center; gap: 12px; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
		.' . $uid . '-item:hover { transform: translateX(4px); box-shadow: 0 4px 8px rgba(0,0,0,0.04); border-color: #e2e8f0; }
		.' . $uid . '-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; }
		.' . $uid . '-text { display: flex; flex-direction: column; flex: 1; overflow: hidden; }
		.' . $uid . '-trk { font-size: 14px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
		.' . $uid . '-art { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . '-score { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; }
		.' . $uid . '-val { font-size: 16px; font-weight: 900; line-height: 1; }
		.col-exp .' . $uid . '-val { color: #ef4444; }
		.col-ris .' . $uid . '-val { color: #f59e0b; }
		.col-emg .' . $uid . '-val { color: #10b981; }
		.' . $uid . '-lbl { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px; }
		.' . $uid . '-empty { text-align: center; color: #94a3b8; font-size: 13px; padding: 24px 0; }
		</style>';

		$render_col = function($title, $icon, $class, $data) use ($uid) {
			echo '<div class="' . $uid . '-col ' . $class . '">';
			echo '<div class="' . $uid . '-header">';
			echo '<h3 class="' . $uid . '-title"><span class="' . $uid . '-icon">' . $icon . '</span> ' . $title . '</h3>';
			echo '<span class="' . $uid . '-count">' . count($data) . '</span>';
			echo '</div>';
			echo '<div class="' . $uid . '-list">';
			if (empty($data)) {
				echo '<div class="' . $uid . '-empty">No tracks in this zone.</div>';
			} else {
				foreach ($data as $d) {
					$img = $d->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
					echo '<div class="' . $uid . '-item">';
					echo '<img src="' . esc_url($img) . '" class="' . $uid . '-img">';
					echo '<div class="' . $uid . '-text">';
					echo '<span class="' . $uid . '-trk">' . esc_html($d->track) . '</span>';
					echo '<span class="' . $uid . '-art">' . esc_html($d->artist) . '</span>';
					echo '</div>';
					echo '<div class="' . $uid . '-score">';
					echo '<span class="' . $uid . '-val">' . intval($d->momentum_score) . '</span>';
					echo '<span class="' . $uid . '-lbl">MOMENTUM</span>';
					echo '</div>';
					echo '</div>';
				}
			}
			echo '</div></div>';
		};

		echo '<div class="' . $uid . '-wrap">';
		$render_col('Exploding', '🔥', 'col-exp', $exploding);
		$render_col('Rising', '📈', 'col-ris', $rising);
		$render_col('Emerging', '🌱', 'col-emg', $emerging);
		echo '</div>';
	}
}
