<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class SparklinesTable extends Widget_Base {

	public function get_name() { return 'kc_sparklines_table'; }
	public function get_title() { return __( 'Trajectory Sparklines Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Query Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		global $wpdb;
		$defs = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}charts_definitions ORDER BY id ASC");
		$chart_options = [];
		if ($defs) {
			foreach ($defs as $d) {
				$chart_options[$d->id] = $d->title;
			}
		}

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
			'min' => 5,
			'max' => 100,
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_id = $settings['chart_id'];
		
		if (empty($chart_id)) {
			echo '<div class="kc-empty">Please select a chart.</div>';
			return;
		}

		$limit = $settings['limit'];
		$uid = 'kc-spt-' . $this->get_id();
		
		$manager = new \Charts\Admin\SourceManager();
		$def = $manager->get_definition($chart_id);
		if (!$def) return;
		
		$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
		if (empty($entries)) return;

		global $wpdb;
		$entries_table = $wpdb->prefix . 'charts_entries';

		echo '<style>
		.' . $uid . '-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
		.' . $uid . '-table { width: 100%; border-collapse: collapse; text-align: left; }
		.' . $uid . '-table th { background: #f8fafc; padding: 16px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid #e2e8f0; }
		.' . $uid . '-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
		.' . $uid . '-table tr:last-child td { border-bottom: none; }
		.' . $uid . '-table tr:hover { background: #f8fafc; }
		.' . $uid . '-rank-col { width: 60px; text-align: center; font-size: 18px; font-weight: 900; color: #0f172a; }
		.' . $uid . '-track-col { display: flex; align-items: center; gap: 16px; }
		.' . $uid . '-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; }
		.' . $uid . '-title { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
		.' . $uid . '-artist { font-size: 13px; color: #64748b; }
		.' . $uid . '-spark-col { width: 120px; text-align: right; }
		.' . $uid . '-spark-svg { width: 100px; height: 30px; overflow: visible; }
		.' . $uid . '-spark-line { fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
		.' . $uid . '-spark-area { opacity: 0.1; }
		.' . $uid . '-up { stroke: #10b981; }
		.' . $uid . '-up-fill { fill: #10b981; }
		.' . $uid . '-down { stroke: #ef4444; }
		.' . $uid . '-down-fill { fill: #ef4444; }
		.' . $uid . '-neutral { stroke: #94a3b8; }
		.' . $uid . '-neutral-fill { fill: #94a3b8; }
		.' . $uid . '-move-col { width: 80px; text-align: center; }
		.' . $uid . '-badge-up { background: #dcfce7; color: #166534; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-down { background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-new { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		</style>';

		echo '<div class="' . $uid . '-wrap">';
		echo '<table class="' . $uid . '-table">';
		echo '<thead><tr>';
		echo '<th class="' . $uid . '-rank-col">#</th>';
		echo '<th>Track Details</th>';
		echo '<th class="' . $uid . '-spark-col">4-Week Trend</th>';
		echo '<th class="' . $uid . '-move-col">Move</th>';
		echo '</tr></thead><tbody>';

		foreach ($entries as $e) {
			$img = $e->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
			
			// Fetch last 4 weeks of data for sparkline
			$history = $wpdb->get_col($wpdb->prepare("SELECT rank_position FROM $entries_table WHERE item_id = %d AND source_id = %d ORDER BY created_at DESC LIMIT 5", $e->item_id, $e->source_id));
			$history = array_reverse($history); // chronological
			
			if (empty($history)) $history = [$e->rank_position];
			if (count($history) == 1) array_unshift($history, 100); // fake previous if new

			// Calculate SVG path
			// Y goes from 0 (top of graph = rank 1) to 30 (bottom of graph = rank 100)
			$points = [];
			$max_rank = 100;
			$width = 100;
			$height = 30;
			$step = count($history) > 1 ? $width / (count($history) - 1) : $width;
			
			foreach ($history as $idx => $r) {
				$x = $idx * $step;
				// Invert Y: rank 1 = 0px, rank 100 = 30px
				$y = ($r / $max_rank) * $height;
				$points[] = "$x,$y";
			}
			$poly_pts = implode(' ', $points);
			$area_pts = "0,$height $poly_pts $width,$height";
			
			$first_rank = $history[0];
			$last_rank = end($history);
			
			if ($last_rank < $first_rank) {
				$color_class = $uid . '-up';
				$fill_class = $uid . '-up-fill';
			} elseif ($last_rank > $first_rank) {
				$color_class = $uid . '-down';
				$fill_class = $uid . '-down-fill';
			} else {
				$color_class = $uid . '-neutral';
				$fill_class = $uid . '-neutral-fill';
			}

			echo '<tr>';
			echo '<td class="' . $uid . '-rank-col">' . $e->rank_position . '</td>';
			echo '<td>';
			echo '<div class="' . $uid . '-track-col">';
			echo '<img src="' . esc_url($img) . '" class="' . $uid . '-img">';
			echo '<div>';
			echo '<div class="' . $uid . '-title">' . esc_html($resolved['title']) . '</div>';
			echo '<div class="' . $uid . '-artist">' . esc_html($resolved['subtitle']) . '</div>';
			echo '</div></div>';
			echo '</td>';
			
			echo '<td class="' . $uid . '-spark-col">';
			echo '<svg class="' . $uid . '-spark-svg" viewBox="0 0 100 30" preserveAspectRatio="none">';
			echo '<polygon points="' . $area_pts . '" class="' . $uid . '-spark-area ' . $fill_class . '"></polygon>';
			echo '<polyline points="' . $poly_pts . '" class="' . $uid . '-spark-line ' . $color_class . '"></polyline>';
			echo '</svg>';
			echo '</td>';
			
			echo '<td class="' . $uid . '-move-col">';
			$move = $e->movement_value;
			$dir = $e->movement_direction;
			if ($dir === 'up') {
				echo '<span class="' . $uid . '-badge-up">▲ ' . intval($move) . '</span>';
			} elseif ($dir === 'down') {
				echo '<span class="' . $uid . '-badge-down">▼ ' . intval($move) . '</span>';
			} else {
				echo '<span class="' . $uid . '-badge-new">NEW</span>';
			}
			echo '</td>';
			
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}
}
