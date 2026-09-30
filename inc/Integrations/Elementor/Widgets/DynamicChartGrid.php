<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class DynamicChartGrid extends Widget_Base {

	public function get_name() { return 'kc_dynamic_chart_grid'; }
	public function get_title() { return __( 'Dynamic Chart Grid', 'charts' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Query & Layout', 'charts' ),
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
			'min' => 3,
			'max' => 20,
			'step' => 1,
			'default' => 8,
		] );

		$this->add_control( 'layout_style', [
			'label' => __( 'Design Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'bento'     => __( 'Bento Matrix', 'charts' ),
				'coverflow' => __( '3D Coverflow Carousel', 'charts' ),
				'accordion' => __( 'Expanding Accordion', 'charts' ),
			],
			'default' => 'bento',
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
		$layout = $settings['layout_style'];
		$uid = 'kc-dyn-' . $this->get_id();
		
		$manager = new \Charts\Admin\SourceManager();
		$def = $manager->get_definition($chart_id);
		if (!$def) return;
		
		$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
		if (empty($entries)) return;

		// CSS output
		echo '<style>';
		
		// Common Overlay Styles
		echo '
		.' . $uid . '-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0) 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transform: translateY(20px); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
		.' . $uid . '-item:hover .' . $uid . '-overlay { opacity: 1; transform: translateY(0); }
		.' . $uid . '-rank { position: absolute; top: 16px; left: 16px; font-size: 24px; font-weight: 900; color: #fff; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2); z-index: 2; }
		.' . $uid . '-title { color: #fff; font-size: 18px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
		.' . $uid . '-artist { color: #cbd5e1; font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . '-meta { display: flex; gap: 8px; margin-top: 12px; }
		.' . $uid . '-badge { background: rgba(255,255,255,0.15); backdrop-filter: blur(4px); color: #fff; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 4px; }
		';

		if ($layout === 'bento') {
			echo '
			.' . $uid . '-bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 180px; gap: 16px; }
			.' . $uid . '-item { position: relative; border-radius: 16px; overflow: hidden; background: #000; cursor: pointer; }
			.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.8; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
			.' . $uid . '-item:hover .' . $uid . '-bg { transform: scale(1.05); opacity: 0.6; }
			
			/* Item Sizing */
			.' . $uid . '-item:nth-child(1) { grid-column: span 2; grid-row: span 2; }
			.' . $uid . '-item:nth-child(2) { grid-column: span 2; grid-row: span 1; }
			.' . $uid . '-item:nth-child(3) { grid-column: span 2; grid-row: span 1; }
			
			@media (max-width: 768px) {
				.' . $uid . '-bento { grid-template-columns: repeat(2, 1fr); }
				.' . $uid . '-item:nth-child(1), .' . $uid . '-item:nth-child(2), .' . $uid . '-item:nth-child(3) { grid-column: span 2; }
			}
			';
		} elseif ($layout === 'accordion') {
			echo '
			.' . $uid . '-accordion { display: flex; height: 500px; gap: 12px; }
			.' . $uid . '-item { flex: 1; position: relative; border-radius: 16px; overflow: hidden; background: #000; cursor: pointer; transition: flex 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
			.' . $uid . '-item:hover { flex: 4; }
			.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.6; transition: opacity 0.4s; }
			.' . $uid . '-item:hover .' . $uid . '-bg { opacity: 0.8; }
			.' . $uid . '-title { font-size: 24px; }
			.' . $uid . '-artist { font-size: 16px; }
			@media (max-width: 768px) {
				.' . $uid . '-accordion { flex-direction: column; height: 800px; }
			}
			';
		} elseif ($layout === 'coverflow') {
			echo '
			.' . $uid . '-coverflow { width: 100%; padding: 40px 0; overflow: hidden; }
			.' . $uid . '-item { width: 300px; height: 400px; position: relative; border-radius: 20px; overflow: hidden; background: #000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
			.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.8; }
			.' . $uid . '-item:hover .' . $uid . '-bg { opacity: 1; }
			.' . $uid . '-rank { width: 50px; height: 50px; font-size: 28px; }
			';
		}
		echo '</style>';

		$render_item = function($entry, $def) use ($uid) {
			$img = $entry->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($entry, $def);
			$rank = $entry->rank_position;
			$weeks = $entry->weeks_on_chart ?? 1;
			$peak = $entry->peak_rank ?? $rank;
			
			echo '<div class="' . $uid . '-item swiper-slide">';
			echo '<div class="' . $uid . '-bg" style="background-image:url(\'' . esc_url($img) . '\');"></div>';
			echo '<div class="' . $uid . '-rank">' . $rank . '</div>';
			echo '<div class="' . $uid . '-overlay">';
			echo '<div class="' . $uid . '-title">' . esc_html($resolved['title']) . '</div>';
			echo '<div class="' . $uid . '-artist">' . esc_html($resolved['subtitle']) . '</div>';
			echo '<div class="' . $uid . '-meta">';
			echo '<span class="' . $uid . '-badge">Peak #' . $peak . '</span>';
			echo '<span class="' . $uid . '-badge">' . $weeks . ' Wks</span>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		};

		if ($layout === 'bento') {
			echo '<div class="' . $uid . '-bento">';
			foreach ($entries as $e) $render_item($e, $def);
			echo '</div>';
		} elseif ($layout === 'accordion') {
			echo '<div class="' . $uid . '-accordion">';
			foreach ($entries as $e) $render_item($e, $def);
			echo '</div>';
		} elseif ($layout === 'coverflow') {
			echo '<div class="swiper-container ' . $uid . '-coverflow" id="' . $uid . '-swiper">';
			echo '<div class="swiper-wrapper">';
			foreach ($entries as $e) $render_item($e, $def);
			echo '</div>';
			echo '<div class="swiper-pagination"></div>';
			echo '</div>';
			
			// Initialize Swiper Coverflow
			echo '<script>
			document.addEventListener("DOMContentLoaded", function() {
				if(typeof Swiper !== "undefined") {
					new Swiper("#' . $uid . '-swiper", {
						effect: "coverflow",
						grabCursor: true,
						centeredSlides: true,
						slidesPerView: "auto",
						loop: true,
						coverflowEffect: {
							rotate: 30,
							stretch: 0,
							depth: 100,
							modifier: 1,
							slideShadows: true,
						}
					});
				}
			});
			</script>';
		}
	}
}
