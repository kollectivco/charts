<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class SidebarTop1 extends Widget_Base {

	public function get_name() { return 'kc_sidebar_top1'; }
	public function get_title() { return __( 'Sidebar Top 1 Cards', 'charts' ); }
	public function get_icon() { return 'eicon-rating'; }
	public function get_categories() { return [ 'charts' ]; }

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

		$this->add_control( 'chart_ids', [
			'label' => __( 'Select Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $chart_options,
			'default' => array_keys(array_slice($chart_options, 0, 3, true)),
		] );

		$this->add_control( 'layout_style', [
			'label' => __( 'Design Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'crown_stack' => __( 'Crown Stack (Vertical)', 'charts' ),
				'flip_card'   => __( '3D Flip Cards', 'charts' ),
			],
			'default' => 'crown_stack',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_ids = $settings['chart_ids'];
		
		if (empty($chart_ids)) {
			echo '<div class="kc-empty">Please select at least one chart.</div>';
			return;
		}

		$layout = $settings['layout_style'];
		$uid = 'kc-st1-' . $this->get_id();
		
		$manager = new \Charts\Admin\SourceManager();
		$charts_data = [];
		foreach ($chart_ids as $cid) {
			$def = $manager->get_definition($cid);
			if (!$def) continue;
			$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 1);
			if (empty($entries)) continue;
			$top = $entries[0];
			
			$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$top]);
			if (empty($img) && !empty($top->cover_image)) $img = $top->cover_image;
			if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
			
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($top, $def);
			
			$charts_data[] = [
				'chart_name' => $def->title,
				'track'      => $resolved['title'],
				'artist'     => $resolved['subtitle'],
				'image'      => $img,
				'weeks'      => $top->weeks_on_chart ?? 1
			];
		}

		echo '<style>';
		if ($layout === 'crown_stack') {
			echo '
			.' . $uid . '-stack { display: flex; flex-direction: column; gap: 16px; }
			.' . $uid . '-card { position: relative; height: 130px; border-radius: 16px; overflow: hidden; background: #0f172a; cursor: pointer; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
			.' . $uid . '-card:hover { transform: translateY(-4px) scale(1.02); z-index: 2; box-shadow: 0 12px 24px rgba(0,0,0,0.2); }
			.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.7; transition: opacity 0.5s, transform 0.5s; }
			.' . $uid . '-card:hover .' . $uid . '-bg { opacity: 0.4; transform: scale(1.1); }
			.' . $uid . '-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.2) 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; }
			.' . $uid . '-num { position: absolute; top: -10px; right: 0px; font-size: 80px; font-weight: 900; line-height: 1; color: rgba(251,191,36,0.15); font-style: italic; transition: color 0.3s; }
			.' . $uid . '-card:hover .' . $uid . '-num { color: rgba(251,191,36,0.5); }
			.' . $uid . '-ch-name { color: #fbbf24; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; display: inline-flex; align-items: center; gap: 4px; }
			.' . $uid . '-title { color: #fff; font-size: 16px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
			.' . $uid . '-artist { color: #cbd5e1; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.' . $uid . '-stats { position: absolute; top: 12px; left: 12px; opacity: 0; transform: translateY(-10px); transition: all 0.3s; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); padding: 4px 8px; border-radius: 6px; font-size: 10px; color: #fff; font-weight: 700; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 4px; }
			.' . $uid . '-card:hover .' . $uid . '-stats { opacity: 1; transform: translateY(0); }
			';
		} else {
			echo '
			.' . $uid . '-flip-grid { display: flex; flex-direction: column; gap: 16px; perspective: 1000px; }
			.' . $uid . '-flip-card { width: 100%; height: 110px; position: relative; transform-style: preserve-3d; transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1); cursor: pointer; }
			.' . $uid . '-flip-card:hover { transform: rotateY(180deg); }
			.' . $uid . '-face { position: absolute; inset: 0; backface-visibility: hidden; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; justify-content: center; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
			.' . $uid . '-front { background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid #334155; align-items: center; text-align: center; }
			.' . $uid . '-front-title { color: #fff; font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 8px; }
			.' . $uid . '-front-icon { font-size: 28px; }
			.' . $uid . '-back { background: #000; transform: rotateY(180deg); justify-content: flex-end; }
			.' . $uid . '-back-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.5; }
			.' . $uid . '-back-content { position: relative; z-index: 2; }
			.' . $uid . '-back-badge { display: inline-block; background: #fbbf24; color: #78350f; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px; margin-bottom: 6px; }
			.' . $uid . '-back-title { color: #fff; font-size: 14px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
			.' . $uid . '-back-artist { color: #cbd5e1; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			';
		}
		echo '</style>';

		if ($layout === 'crown_stack') {
			echo '<div class="' . $uid . '-stack">';
			foreach ($charts_data as $c) {
				echo '<div class="' . $uid . '-card">';
				echo '<div class="' . $uid . '-bg" style="background-image:url(\'' . esc_url($c['image']) . '\');"></div>';
				echo '<div class="' . $uid . '-num">1</div>';
				echo '<div class="' . $uid . '-stats"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="12 6 12 12 16 14"/><circle cx="12" cy="12" r="10"/></svg> ' . $c['weeks'] . ' Wks</div>';
				echo '<div class="' . $uid . '-overlay">';
				echo '<div class="' . $uid . '-ch-name"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> ' . esc_html($c['chart_name']) . '</div>';
				echo '<div class="' . $uid . '-title">' . esc_html($c['track']) . '</div>';
				echo '<div class="' . $uid . '-artist">' . esc_html($c['artist']) . '</div>';
				echo '</div></div>';
			}
			echo '</div>';
		} else {
			echo '<div class="' . $uid . '-flip-grid">';
			foreach ($charts_data as $c) {
				echo '<div class="' . $uid . '-flip-card">';
				echo '<div class="' . $uid . '-face ' . $uid . '-front">';
				echo '<div class="' . $uid . '-front-icon">👑</div>';
				echo '<div class="' . $uid . '-front-title">' . esc_html($c['chart_name']) . '</div>';
				echo '</div>';
				echo '<div class="' . $uid . '-face ' . $uid . '-back">';
				echo '<div class="' . $uid . '-back-bg" style="background-image:url(\'' . esc_url($c['image']) . '\');"></div>';
				echo '<div class="' . $uid . '-back-content">';
				echo '<div class="' . $uid . '-back-badge">#1</div>';
				echo '<div class="' . $uid . '-back-title">' . esc_html($c['track']) . '</div>';
				echo '<div class="' . $uid . '-back-artist">' . esc_html($c['artist']) . '</div>';
				echo '</div></div></div>';
			}
			echo '</div>';
		}
	}
}
