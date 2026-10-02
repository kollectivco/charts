<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ViralRadar extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_viral_radar'; }
	public function get_title() { return __( 'Charts: Viral Radar Heatmap', 'charts' ); }
	public function get_icon() { return 'eicon-wifi'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Radar Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = [ '' => __( '— All Charts —', 'charts' ) ];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Filter by Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => '',
			'description' => __( 'Leave blank to show tracks from all charts.', 'charts' ),
		] );

		$this->add_control( 'limit_per_col', [
			'label' => __( 'Tracks Per Column', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 4,
		] );
		
		$this->end_controls_section();

		$this->add_premium_badge_controls();
		$this->add_premium_layout_controls();
		
		// Map 'card' strictly to the columns or the list items
		$this->add_granular_style_controls(['card', 'image', 'title', 'meta']);
		$this->add_advanced_image_controls('{{WRAPPER}} .kc-elm-img', '{{WRAPPER}} .kc-elm-img');
	}

	protected function render() {
		global $wpdb;
		$limit = $this->get_settings_for_display('limit_per_col') ?: 4;
		$settings = $this->get_settings_for_display();
		$chart_id = $settings['chart_id'] ?? '';
		
		$intel   = $wpdb->prefix . 'charts_intelligence';
		$tracks  = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';
		$entries_tbl = $wpdb->prefix . 'charts_entries';

		// Build optional chart-scoped JOIN + WHERE fragment.
		$chart_join  = '';
		$chart_where = '';
		if ( ! empty( $chart_id ) ) {
			$chart_join  = " JOIN $entries_tbl ce ON ce.track_id = t.id AND ce.chart_id = " . intval($chart_id);
			$chart_where = '';
		}

		$base_select = "SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist
			FROM $intel i
			JOIN $tracks t ON t.id = i.entity_id{$chart_join}
			LEFT JOIN $artists a ON a.id = t.primary_artist_id
			WHERE i.entity_type = 'track'";

		$exploding = $wpdb->get_results($wpdb->prepare("{$base_select} AND i.momentum_score >= 80 ORDER BY i.momentum_score DESC LIMIT %d", $limit));
		$rising    = $wpdb->get_results($wpdb->prepare("{$base_select} AND i.momentum_score >= 60 AND i.momentum_score < 80 ORDER BY i.momentum_score DESC LIMIT %d", $limit));
		$emerging  = $wpdb->get_results($wpdb->prepare("{$base_select} AND i.momentum_score >= 40 AND i.momentum_score < 60 ORDER BY i.momentum_score DESC LIMIT %d", $limit));

		// Editor placeholder when no data exists at all.
		if ( empty($exploding) && empty($rising) && empty($emerging) ) {
			echo '<div style="padding:32px 20px; text-align:center; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1; color:#64748b; font-size:14px; line-height:1.6;">'
				. '<div style="font-size:36px; margin-bottom:12px;">📡</div>'
				. '<strong style="display:block; color:#334155; font-size:15px; margin-bottom:6px;">Viral Radar Heatmap</strong>'
				. 'No momentum data found. Run the intelligence processor or adjust the chart filter.'
				. '</div>';
			return;
		}

		$uid = 'kc-vr-' . $this->get_id();
		$hover_anim = $settings['hover_animation'] ?? 'zoom';

		echo '<div class="kc-widget-wrap">';

		echo '<style>
		.' . $uid . '-wrap { display: grid; }
		.' . $uid . '-col { background: #f8fafc; border-radius: 20px; padding: 24px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
		.' . $uid . '-col::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
		.' . $uid . '-col.col-exp::before { background: #ef4444; }
		.' . $uid . '-col.col-ris::before { background: #f59e0b; }
		.' . $uid . '-col.col-emg::before { background: #10b981; }
		.' . $uid . '-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
		.' . $uid . '-list { display: flex; flex-direction: column; gap: 12px; }
		.' . $uid . '-item { display: flex; align-items: center; gap: 12px; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.3s; }
		.' . $uid . '-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; transition: transform 0.3s; }
		.' . $uid . '-text { display: flex; flex-direction: column; flex: 1; overflow: hidden; }
		.' . $uid . '-trk { font-size: 14px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
		.' . $uid . '-art { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . '-score { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; }
		.' . $uid . '-val { font-size: 16px; font-weight: 900; line-height: 1; }
		.col-exp .' . $uid . '-val { color: #ef4444; }
		.col-ris .' . $uid . '-val { color: #f59e0b; }
		.col-emg .' . $uid . '-val { color: #10b981; }
		.' . $uid . '-lbl { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 4px; }
		';
		
		if ($hover_anim === 'zoom') {
			echo '.' . $uid . '-item:hover .' . $uid . '-img { transform: scale(1.1); }';
		} elseif ($hover_anim === 'lift') {
			echo '.' . $uid . '-item:hover { transform: translateX(8px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-color: #cbd5e1; }';
		}
		
		echo '</style>';

		$render_col = function($title, $icon, $class, $data) use ($uid) {
			echo '<div class="' . $uid . '-col ' . $class . '">';
			echo '<div class="' . $uid . '-header">';
			echo '<h3 style="font-size:16px; font-weight:800; color:#0f172a; text-transform:uppercase; margin:0;"><span style="font-size:20px;">' . $icon . '</span> ' . $title . '</h3>';
			echo '<span style="background:#e2e8f0; color:#475569; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px;">' . count($data) . '</span>';
			echo '</div>';
			echo '<div class="' . $uid . '-list">';
			if (empty($data)) {
				echo '<div style="text-align:center; color:#94a3b8; font-size:13px; padding:24px 0;">No tracks in this zone.</div>';
			} else {
				foreach ($data as $d) {
					$img = (!empty($d->resolved_image) ? $d->resolved_image : $d->cover_image) ?: CHARTS_URL . 'public/assets/img/placeholder.png';
					echo '<div class="' . $uid . '-item kc-elm-card">';
					echo '<img src="' . esc_url($img) . '" class="' . $uid . '-img kc-elm-img">';
					echo '<div class="' . $uid . '-text">';
					echo '<span class="' . $uid . '-trk kc-elm-title">' . esc_html($d->title) . '</span>';
					echo '<span class="' . $uid . '-art kc-elm-artist">' . esc_html($d->artist) . '</span>';
					echo '</div>';
					echo '<div class="' . $uid . '-score kc-elm-meta">';
					echo '<span class="' . $uid . '-val">' . intval($d->momentum_score) . '</span>';
					echo '<span class="' . $uid . '-lbl">الزخم</span>';
					echo '</div>';
					echo '</div>';
				}
			}
			echo '</div></div>';
		};

		echo '<div class="' . $uid . '-wrap kc-grid-root">';
		$render_col('متفجر', '🔥', 'col-exp', $exploding);
		$render_col('صاعد', '📈', 'col-ris', $rising);
		$render_col('مكتشف جديد', '🌱', 'col-emg', $emerging);
		echo '</div></div>';
	}
}
