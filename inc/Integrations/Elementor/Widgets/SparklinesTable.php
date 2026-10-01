<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class SparklinesTable extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_sparklines_table'; }
	public function get_title() { return __( 'Trajectory Sparklines Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = [];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => !empty($chart_options) ? array_keys($chart_options)[0] : '',
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Number of Tracks', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 10,
		] );
		$this->end_controls_section();

		$this->add_premium_badge_controls();

		// Custom layout for tables
		$this->start_controls_section( 'table_layout', [
			'label' => __( 'Table Layout', 'charts' ),
			'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		$this->add_responsive_control( 'cell_padding', [
			'label' => __( 'Cell Padding', 'charts' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-elm-card th, {{WRAPPER}} .kc-elm-card td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->end_controls_section();

		$this->add_granular_style_controls(['card', 'image', 'title', 'meta', 'counter']);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_id = $settings['chart_id'];
		
		echo '<div class="kc-widget-wrap">';
		if (empty($chart_id)) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">Please select a chart.</div></div>';
			return;
		}

		$limit = $settings['limit'];
		$uid = 'kc-spt-' . $this->get_id();
		
		$def = (new \Charts\Admin\SourceManager())->get_definition($chart_id);
		$entries = $def ? \Charts\Core\PublicIntegration::get_preview_entries($def, $limit) : [];
		if (empty($entries)) return;

		global $wpdb;
		$entries_table = $wpdb->prefix . 'charts_entries';

		echo '<style>
		.' . $uid . '-wrap { background: #fff; overflow-x: auto; transition: all 0.3s; }
		.' . $uid . '-table { width: 100%; min-width: 600px; border-collapse: collapse; text-align: left; }
		.' . $uid . '-table th { background: #f8fafc; padding: 16px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid #e2e8f0; }
		.' . $uid . '-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
		.' . $uid . '-table tr:last-child td { border-bottom: none; }
		.' . $uid . '-table tr:hover { background: #f8fafc; }
		.' . $uid . '-track-col { display: flex; align-items: center; gap: 16px; }
		.' . $uid . '-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; }
		.' . $uid . '-title { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
		.' . $uid . '-artist { font-size: 13px; color: #64748b; }
		.' . $uid . '-spark-col { width: 120px; text-align: right; }
		.' . $uid . '-spark-svg { width: 100px; height: 30px; overflow: visible; }
		.' . $uid . '-spark-line { fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
		.' . $uid . '-spark-area { opacity: 0.1; }
		.' . $uid . '-up { stroke: #10b981; } .' . $uid . '-up-fill { fill: #10b981; }
		.' . $uid . '-down { stroke: #ef4444; } .' . $uid . '-down-fill { fill: #ef4444; }
		.' . $uid . '-neutral { stroke: #94a3b8; } .' . $uid . '-neutral-fill { fill: #94a3b8; }
		.' . $uid . '-move-col { width: 80px; text-align: center; }
		.' . $uid . '-badge-up { background: #dcfce7; color: #166534; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-down { background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-new { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		</style>';

		echo '<div class="' . $uid . '-wrap kc-elm-card">';
		echo '<table class="' . $uid . '-table">';
		echo '<thead><tr>';
		echo '<th class="kc-elm-counter">#</th>';
		echo '<th>Track Details</th>';
		echo '<th class="' . $uid . '-spark-col">4-Week Trend</th>';
		echo '<th class="' . $uid . '-move-col">Move</th>';
		echo '</tr></thead><tbody>';

		foreach ($entries as $e) {
			$img = $e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
			$is_new = ($e->movement_direction === 'new');
			
			$history = $wpdb->get_col($wpdb->prepare("SELECT rank_position FROM $entries_table WHERE item_id = %d AND source_id = %d ORDER BY created_at DESC LIMIT 5", $e->item_id, $e->source_id));
			$history = array_reverse($history);
			
			if (empty($history)) $history = [$e->rank_position];
			if (count($history) == 1) array_unshift($history, 100);

			$points = [];
			$max_rank = 100;
			$width = 100;
			$height = 30;
			$step = count($history) > 1 ? $width / (count($history) - 1) : $width;
			
			foreach ($history as $idx => $r) { $points[] = ($idx * $step) . "," . (($r / $max_rank) * $height); }
			$poly_pts = implode(' ', $points);
			$area_pts = "0,$height $poly_pts $width,$height";
			
			$first_rank = $history[0];
			$last_rank = end($history);
			
			if ($last_rank < $first_rank) {
				$color_class = $uid . '-up'; $fill_class = $uid . '-up-fill';
			} elseif ($last_rank > $first_rank) {
				$color_class = $uid . '-down'; $fill_class = $uid . '-down-fill';
			} else {
				$color_class = $uid . '-neutral'; $fill_class = $uid . '-neutral-fill';
			}

			echo '<tr>';
			echo '<td class="kc-elm-counter" style="font-weight:900;font-size:18px;">' . $e->rank_position . '</td>';
			echo '<td>';
			echo '<div class="' . $uid . '-track-col">';
			echo '<img src="' . esc_url($img) . '" class="' . $uid . '-img kc-elm-img">';
			echo '<div>';
			echo '<div class="' . $uid . '-title kc-elm-title">' . esc_html($resolved['title']) . '</div>';
			echo '<div class="' . $uid . '-artist kc-elm-artist">' . esc_html($resolved['subtitle']) . '</div>';
			echo '</div></div>';
			echo '</td>';
			
			echo '<td class="' . $uid . '-spark-col">';
			echo '<svg class="' . $uid . '-spark-svg" viewBox="0 0 100 30" preserveAspectRatio="none">';
			echo '<polygon points="' . $area_pts . '" class="' . $uid . '-spark-area ' . $fill_class . '"></polygon>';
			echo '<polyline points="' . $poly_pts . '" class="' . $uid . '-spark-line ' . $color_class . '"></polyline>';
			echo '</svg>';
			echo '</td>';
			
			echo '<td class="' . $uid . '-move-col kc-elm-meta">';
			if ($settings['show_badges'] === 'yes' && $is_new) {
				echo '<span class="' . $uid . '-badge-new" style="background:#ef4444;color:#fff;">' . esc_html($settings['badge_new_text']) . '</span>';
			} else {
				$move = $e->movement_value;
				if ($e->movement_direction === 'up') echo '<span class="' . $uid . '-badge-up">▲ ' . intval($move) . '</span>';
				elseif ($e->movement_direction === 'down') echo '<span class="' . $uid . '-badge-down">▼ ' . intval($move) . '</span>';
				else echo '<span class="' . $uid . '-badge-new">NEW</span>';
			}
			echo '</td>';
			
			echo '</tr>';
		}
		echo '</tbody></table></div></div>';
	}
}
