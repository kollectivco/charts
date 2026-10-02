<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class DynamicChartGrid extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_dynamic_chart_grid'; }
	public function get_title() { return __( 'Charts: Dynamic Chart Grid', 'charts' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Query Settings', 'charts' ),
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
			'default' => 8,
		] );

		$this->add_control( 'layout_style', [
			'label' => __( 'Design Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'bento'     => __( 'Bento Matrix', 'charts' ),
				'carousel'  => __( 'Slider / Carousel', 'charts' ),
				'accordion' => __( 'Expanding Accordion', 'charts' ),
			],
			'default' => 'bento',
		] );
		$this->end_controls_section();

		$this->add_premium_badge_controls();

		$this->start_controls_section( 'carousel_section', [
			'label' => __( 'Carousel Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
			'condition' => [ 'layout_style' => 'carousel' ],
		] );
		$this->add_control( 'carousel_effect', [
			'label' => __( 'Effect', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ 'coverflow' => '3D Coverflow', 'cards' => 'Stacked Cards', 'slide' => 'Standard Slide' ],
			'default' => 'coverflow',
		] );
		$this->add_control( 'carousel_autoplay', [
			'label' => __( 'Autoplay', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );
		$this->add_control( 'carousel_nav', [
			'label' => __( 'Show Pagination Dots', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );
		$this->end_controls_section();

		$this->add_premium_layout_controls();
		$this->add_granular_style_controls(['card', 'image', 'title', 'meta', 'counter']);
		$this->add_advanced_image_controls();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_id = $settings['chart_id'];
		
		if (empty($chart_id)) {
			echo '<div style="padding:20px; background:#f8fafc; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">Please select a chart.</div>';
			return;
		}

		$limit = $settings['limit'];
		$layout = $settings['layout_style'];
		$uid = 'kc-dyn-' . $this->get_id();
		$uid_safe = str_replace("-", "_", $uid);
		
		$def = (new \Charts\Admin\SourceManager())->get_definition($chart_id);
		$entries = $def ? \Charts\Core\PublicIntegration::get_preview_entries($def, $limit) : [];
		if (empty($entries)) {
			echo '<div style="padding:32px 20px; text-align:center; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1; color:#64748b; font-size:14px; line-height:1.6;">'
				. '<div style="font-size:36px; margin-bottom:12px;">🎵</div>'
				. '<strong style="display:block; color:#334155; font-size:15px; margin-bottom:6px;">Dynamic Chart Grid</strong>'
				. ( $def ? 'No chart entries found for the selected chart. Make sure the chart has been processed and has entries.' : 'Selected chart definition could not be loaded.' )
				. '</div>';
			return;
		}

		$hover_anim = $settings['hover_animation'] ?? 'zoom';

		echo '<style>
		.' . $uid . '-wrap { width: 100%; position: relative; }
		.' . $uid . '-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; z-index: 2; transition: all 0.3s; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.2) 100%); }
		.' . $uid . '-counter { position: absolute; top: 16px; left: 16px; font-size: 18px; font-weight: 900; color: #fff; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; z-index: 3; }
		.' . $uid . '-title { color: #fff; font-size: 18px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
		.' . $uid . '-artist { color: #cbd5e1; font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . '-meta { display: flex; gap: 8px; margin-top: 12px; }
		.' . $uid . '-badge { background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); color: #fff; font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 4px; }
		.' . $uid . '-img { position: absolute; inset: 0; background-size: cover; background-position: center; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); z-index: 1; }
		.' . $uid . '-card { background: #0f172a; border-radius: 16px; position: relative; overflow: hidden; cursor: pointer; transition: all 0.4s; }
		';

		if ($hover_anim === 'zoom') {
			echo '.' . $uid . '-card:hover .' . $uid . '-img { transform: scale(1.1); }';
		} elseif ($hover_anim === 'lift') {
			echo '.' . $uid . '-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }';
		} elseif ($hover_anim === 'glow') {
			echo '.' . $uid . '-card:hover { box-shadow: 0 0 30px rgba(99,102,241,0.5); }';
		}

		if ($layout === 'bento') {
			echo '
			.' . $uid . '-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; grid-auto-rows: 200px; }
			.' . $uid . '-card { height: 100%; width: 100%; }
			.' . $uid . '-card:nth-child(1) { grid-column: 1 / span 2; grid-row: 1 / span 2; }
			.' . $uid . '-card:nth-child(2) { grid-column: 3 / span 2; grid-row: 1 / span 1; }
			.' . $uid . '-card:nth-child(3) { grid-column: 3 / span 2; grid-row: 2 / span 1; }
			@media (max-width: 1024px) {
				.' . $uid . '-grid { grid-template-columns: repeat(2, 1fr); }
				.' . $uid . '-card:nth-child(1) { grid-column: 1 / span 2; grid-row: 1 / span 2; }
				.' . $uid . '-card:nth-child(2) { grid-column: 1 / span 2; grid-row: 3 / span 1; }
				.' . $uid . '-card:nth-child(3) { grid-column: 1 / span 2; grid-row: 4 / span 1; }
			}
			@media (max-width: 768px) { 
				.' . $uid . '-grid { grid-template-columns: 1fr; grid-auto-rows: 250px; }
				.' . $uid . '-card:nth-child(1), .' . $uid . '-card:nth-child(2), .' . $uid . '-card:nth-child(3) { grid-column: 1 / span 1; grid-row: auto; } 
			}
			';
		} elseif ($layout === 'accordion') {
			echo '.' . $uid . '-grid { display: flex; height: 500px; } .' . $uid . '-card { flex: 1; } .' . $uid . '-card:hover { flex: 4; } @media (max-width:768px) { .' . $uid . '-grid { flex-direction: column; height: 800px; } }';
		} elseif ($layout === 'carousel') {
			echo '.' . $uid . '-grid { width: 100%; padding: 40px 0; overflow: hidden; } .' . $uid . '-card { width: 300px; height: 400px; }';
		}
		echo '</style>';

		echo '<div class="' . $uid . '-wrap">';
		
		$render_item = function($entry, $def) use ($uid, $layout, $settings) {
			$img = (!empty($entry->resolved_image) ? $entry->resolved_image : $entry->cover_image) ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($entry, $def);
			$rank = \Charts\Core\Transliteration::to_arabic_numerals($entry->rank_position);
			$is_new = ($entry->movement_direction === 'new');
			
			$slide_class = ($layout === 'carousel') ? 'swiper-slide' : '';
			
			echo '<div class="' . $uid . '-card kc-elm-card ' . $slide_class . '">';
			echo '<div class="' . $uid . '-img kc-elm-img" style="background-image:url(\'' . esc_url($img) . '\');"></div>';
			echo '<div class="' . $uid . '-counter kc-elm-counter">' . $rank . '</div>';
			echo '<div class="' . $uid . '-overlay kc-elm-overlay">';
			
			if ($settings['show_badges'] === 'yes' && $is_new) {
				echo '<span style="background:#ef4444; color:#fff; font-size:10px; font-weight:900; padding:4px 8px; border-radius:4px; display:inline-block; margin-bottom:8px; width:max-content;">' . esc_html($settings['badge_new_text']) . '</span>';
			}

			echo '<div class="' . $uid . '-title kc-elm-title">' . esc_html($resolved['title']) . '</div>';
			echo '<div class="' . $uid . '-artist kc-elm-artist">' . esc_html($resolved['subtitle']) . '</div>';
			echo '<div class="' . $uid . '-meta kc-elm-meta">';
			echo '<span class="' . $uid . '-badge kc-elm-badge">أعلى مركز #' . \Charts\Core\Transliteration::to_arabic_numerals($entry->peak_rank ?: $entry->rank_position) . '</span>';
			echo '<span class="' . $uid . '-badge kc-elm-badge">' . ($entry->weeks_on_chart ?? 1) . ' أسابيع</span>';
			echo '</div></div></div>';
		};

		if ($layout === 'bento' || $layout === 'accordion') {
			echo '<div class="' . $uid . '-grid kc-grid-root">';
			foreach ($entries as $e) $render_item($e, $def);
			echo '</div>';
		} elseif ($layout === 'carousel') {
			echo '<div class="swiper ' . $uid . '-grid kc-grid-root" id="' . $uid . '-swiper">';
			echo '<div class="swiper-wrapper">';
			foreach ($entries as $e) $render_item($e, $def);
			echo '</div>';
			if ($settings['carousel_nav'] === 'yes') echo '<div class="swiper-pagination"></div>';
			echo '</div>';
			
			$effect = $settings['carousel_effect'] ?? 'coverflow';
			echo '<script>
			function initCarousel_' . $uid_safe . '() {
				if(typeof Swiper !== "undefined") {
					new Swiper("#' . $uid . '-swiper", {
						effect: "' . $effect . '",
						grabCursor: true, centeredSlides: true, slidesPerView: "auto", loop: true,
						' . ($effect === 'coverflow' ? 'coverflowEffect: { rotate: 30, stretch: 0, depth: 100, modifier: 1, slideShadows: true },' : '') . '
						' . ($settings['carousel_autoplay'] === 'yes' ? 'autoplay: { delay: 3000 },' : '') . '
						' . ($settings['carousel_nav'] === 'yes' ? 'pagination: { el: ".swiper-pagination", clickable: true }' : '') . '
					});
				}
			}
			setTimeout(initCarousel_' . $uid_safe . ', 100);
			</script>';
		}
		echo '</div>';
	}
}
