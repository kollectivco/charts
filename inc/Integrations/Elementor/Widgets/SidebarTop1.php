<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class SidebarTop1 extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_sidebar_top1'; }
	public function get_title() { return __( 'Charts: Sidebar Top 1 Cards', 'charts' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
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

		$this->add_control( 'chart_ids', [
			'label' => __( 'Select Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $chart_options,
			'default' => !empty($chart_options) ? array_keys(array_slice($chart_options, 0, 3, true)) : [],
		] );

		$this->add_control( 'layout_style', [
			'label' => __( 'Design Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'crown_stack' => __( 'Classic Dark Gradient', 'charts' ),
				'glass_stack' => __( 'Glassmorphism Overlay', 'charts' ),
				'flip_card'   => __( '3D Flip Cards', 'charts' ),
			],
			'default' => 'crown_stack',
		] );
		
		
		$this->add_control( 'show_bignum', [
			'label' => __( 'Show Background Number', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => [ 'layout_style' => ['crown_stack', 'glass_stack'] ],
		]);
		$this->add_control( 'chart_icon', [
			'label' => __( 'Chart Name Icon', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'star' => 'Star',
				'crown' => 'Crown',
				'fire' => 'Fire',
				'none' => 'None',
			],
			'default' => 'star',
		] );
		$this->add_responsive_control( 'text_align', [
			'label' => __( 'Text Alignment', 'charts' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'left' => [ 'title' => 'Left', 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ],
				'right' => [ 'title' => 'Right', 'icon' => 'eicon-text-align-right' ],
			],
			'default' => 'right',
			'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'text-align: {{VALUE}}; align-items: {{VALUE}};' ],
		] );
		
$this->add_control( 'show_stats', [
			'label' => __( 'Show Weeks Stats', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => [ 'layout_style' => 'crown_stack' ],
		]);
		$this->end_controls_section();

		// --- SPECIFIC LAYOUT CONTROLS FOR SIDEBARTOP1 ---
		$this->start_controls_section( 'layout_sizing', [
			'label' => __( 'Card Sizing & Layout', 'charts' ),
			'tab' => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		
		$this->add_responsive_control( 'card_height', [
			'label' => __( 'Card Height', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh', 'em' ],
			'range' => [ 'px' => [ 'min' => 80, 'max' => 500, 'step' => 1 ] ],
			'default' => [ 'size' => 130, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .kc-elm-card' => 'height: {{SIZE}}{{UNIT}};' ],
		]);
		
		$this->add_responsive_control( 'card_gap', [
			'label' => __( 'Spacing Between Cards', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-grid-root' => 'gap: {{SIZE}}{{UNIT}};' ],
		]);
		
		$this->add_responsive_control( 'card_padding', [
			'label' => __( 'Content Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ 
				'{{WRAPPER}} .kc-elm-overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				'{{WRAPPER}} .kc-front' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' 
			],
		]);
		
		$this->add_control( 'overlay_gradient', [
			'label' => __( 'Overlay Gradient (Bottom)', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-overlay' => 'background: linear-gradient(to top, {{VALUE}} 0%, transparent 100%);' ],
		]);
		
		$this->add_control( 'hover_animation', [
			'label' => __( 'Hover Effect', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ 'none' => 'None', 'zoom' => 'Image Zoom', 'lift' => 'Lift Up' ],
			'default' => 'zoom',
		] );
		
		$this->end_controls_section();

		// --- TYPOGRAPHY SPECIFIC TO SIDEBARTOP1 ---
		$this->start_controls_section( 'style_chart_name', [ 'label' => __( 'Chart Name Tag', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'cname_color', [
			'label' => __( 'Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ch-name' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'cname_typo', 'selector' => '{{WRAPPER}} .kc-ch-name',
		]);
		$this->end_controls_section();

		
		$this->start_controls_section( 'style_icon', [ 'label' => __( 'Icon Style', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'chart_icon!' => 'none' ] ] );
		$this->add_control( 'icon_color', [
			'label' => __( 'Icon Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ch-name svg' => 'stroke: {{VALUE}}; fill: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'icon_size', [
			'label' => __( 'Icon Size', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 10, 'max' => 50, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-ch-name svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'icon_gap', [
			'label' => __( 'Gap between Icon and Text', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-ch-name' => 'gap: {{SIZE}}{{UNIT}};' ],
		]);
		$this->end_controls_section();
$this->start_controls_section( 'style_artist', [ 'label' => __( 'Artist Name', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'artist_color', [
			'label' => __( 'Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-artist' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'artist_typo', 'selector' => '{{WRAPPER}} .kc-elm-artist',
		]);
		$this->end_controls_section();

		$this->start_controls_section( 'style_big_number', [ 'label' => __( 'Background Number (1)', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'layout_style' => 'crown_stack' ] ] );
		$this->add_control( 'bignum_color', [
			'label' => __( 'Number Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-num' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'bignum_hover', [
			'label' => __( 'Number Hover Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card:hover .kc-num' => 'color: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'bignum_size', [
			'label' => __( 'Size', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 20, 'max' => 200, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-num' => 'font-size: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'bignum_pos_right', [
			'label' => __( 'Position Right', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => -50, 'max' => 100, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-num' => 'right: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'bignum_pos_top', [
			'label' => __( 'Position Top', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => -50, 'max' => 100, 'step' => 1 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-num' => 'top: {{SIZE}}{{UNIT}};' ],
		]);
		$this->end_controls_section();

		// Add Granular controls for the rest (Title, Card bg)
		$this->add_granular_style_controls(['card', 'title', 'meta']);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_ids = $settings['chart_ids'];
		
		echo '<div class="kc-widget-wrap">';
		if (empty($chart_ids)) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">Please select at least one chart.</div></div>';
			return;
		}

		$layout = $settings['layout_style'];
		$uid = 'kc-st1-' . $this->get_id();
		$hover_anim = $settings['hover_animation'] ?? 'zoom';
		
		$manager = new \Charts\Admin\SourceManager();
		$charts_data = [];
		foreach ($chart_ids as $cid) {
			$def = (empty($cid) || $cid === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($cid);
			if (!$def) continue;
			$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 1);
			if (empty($entries)) continue;
			$top = $entries[0];
			
			$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$top]);
			if (empty($img) && !empty((!empty($top->resolved_image) ? $top->resolved_image : $top->cover_image))) $img = (!empty($top->resolved_image) ? $top->resolved_image : $top->cover_image);
			if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
			
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name($top, $def);
			
			$charts_data[] = [
				'chart_name' => $def->title,
				'track'      => $resolved['title'],
				'artist'     => $resolved['subtitle'],
				'image'      => $img,
				'weeks'      => \Charts\Core\Transliteration::to_arabic_numerals($top->weeks_on_chart ?? 1)
			];
		}

		echo '<style>
		.' . $uid . '-stack { display: flex; flex-direction: column; }
		.' . $uid . '-card { position: relative; overflow: hidden; background-color: #0f172a; cursor: pointer; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
		.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.7; transition: all 0.5s; }
		.' . $uid . '-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; }
		.' . $uid . '-num { position: absolute; top: -10px; right: 0px; font-size: 80px; font-weight: 900; line-height: 1; color: rgba(251,191,36,0.15); font-style: italic; transition: color 0.3s; }
		.' . $uid . '-card:hover .' . $uid . '-num { color: rgba(251,191,36,0.5); }
		.' . $uid . '-ch-name { color: #fbbf24; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; display: inline-flex; align-items: center; gap: 4px; }
		.' . $uid . '-title { color: #fff; font-size: 16px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
		.' . $uid . '-artist { color: #cbd5e1; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.' . $uid . '-stats { position: absolute; top: 12px; left: 12px; opacity: 0; transform: translateY(-10px); transition: all 0.3s; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); padding: 4px 8px; border-radius: 6px; font-size: 10px; color: #fff; font-weight: 700; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 4px; }
		.' . $uid . '-card:hover .' . $uid . '-stats { opacity: 1; transform: translateY(0); }
		
		.' . $uid . '-flip-grid { display: flex; flex-direction: column; perspective: 1000px; }
		.' . $uid . '-flip-card { width: 100%; position: relative; transform-style: preserve-3d; transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1); cursor: pointer; }
		.' . $uid . '-flip-card:hover { transform: rotateY(180deg); }
		.' . $uid . '-face { position: absolute; inset: 0; backface-visibility: hidden; overflow: hidden; display: flex; flex-direction: column; justify-content: center; padding: 16px; }
		.' . $uid . '-front { background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid #334155; align-items: center; text-align: center; }
		.' . $uid . '-front-icon { font-size: 28px; }
		.' . $uid . '-back { background: #000; transform: rotateY(180deg); justify-content: flex-end; }
		.' . $uid . '-back-badge { display: inline-block; background: #fbbf24; color: #78350f; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px; margin-bottom: 6px; }
		';
		
		if ($hover_anim === 'zoom') {
			echo '.' . $uid . '-card:hover .' . $uid . '-bg { opacity: 0.4; transform: scale(1.1); }';
		} elseif ($hover_anim === 'lift') {
			echo '.' . $uid . '-card:hover { transform: translateY(-4px) scale(1.02); z-index: 2; box-shadow: 0 12px 24px rgba(0,0,0,0.2); }';
		}

		echo '</style>';

		$ic = $settings['chart_icon'];
		$icon_html = '';
		if ($ic === 'star') $icon_html = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
		if ($ic === 'crown') $icon_html = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="2,22 22,22 22,20 2,20"></polygon><polygon points="2,18 22,18 19,8 15,14 12,6 9,14 5,8"></polygon></svg>';
		if ($ic === 'fire') $icon_html = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0011 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 11-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 002.5 2.5z"></path></svg>';

		if ($layout === 'crown_stack' || $layout === 'glass_stack') {
			echo '<div class="' . $uid . '-stack kc-grid-root ' . ($layout === 'glass_stack' ? 'is-glass' : '') . '">';
			foreach ($charts_data as $c) {
				echo '<div class="' . $uid . '-card kc-elm-card">';
				echo '<div class="' . $uid . '-bg kc-elm-img" style="background-image:url(\'' . esc_url($c['image']) . '\');"></div>';
				
				if ($settings['show_bignum'] === 'yes') {
					$one = \Charts\Core\Transliteration::to_arabic_numerals(1);
					echo '<div class="' . $uid . '-num kc-num">' . $one . '</div>';
				}
				
				if ($settings['show_stats'] === 'yes') {
					echo '<div class="' . $uid . '-stats kc-elm-meta"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="12 6 12 12 16 14"/><circle cx="12" cy="12" r="10"/></svg> ' . \Charts\Core\Transliteration::to_arabic_numerals($c['weeks']) . ' أسابيع</div>';
				}
				
				if ($layout === 'glass_stack') {
					echo '<div class="' . $uid . '-overlay kc-elm-overlay ' . $uid . '-glass">';
				} else {
					echo '<div class="' . $uid . '-overlay kc-elm-overlay">';
				}
				echo '<div class="' . $uid . '-ch-name kc-ch-name">' . $icon_html . esc_html($c['chart_name']) . '</div>';
				echo '<div class="' . $uid . '-title kc-elm-title">' . esc_html($c['track']) . '</div>';
				echo '<div class="' . $uid . '-artist kc-elm-artist">' . esc_html($c['artist']) . '</div>';
				echo '</div></div>';
			}
			echo '</div>';
		} else {
			echo '<div class="' . $uid . '-flip-grid kc-grid-root">';
			foreach ($charts_data as $c) {
				echo '<div class="' . $uid . '-flip-card kc-elm-card">';
				echo '<div class="' . $uid . '-face ' . $uid . '-front kc-front">';
				echo '<div class="' . $uid . '-front-icon">👑</div>';
				echo '<div class="' . $uid . '-front-title kc-ch-name">' . esc_html($c['chart_name']) . '</div>';
				echo '</div>';
				echo '<div class="' . $uid . '-face ' . $uid . '-back kc-elm-overlay">';
				echo '<div class="' . $uid . '-bg kc-elm-img" style="background-image:url(\'' . esc_url($c['image']) . '\');"></div>';
				echo '<div style="position:relative; z-index:2;">';
				$one = \Charts\Core\Transliteration::to_arabic_numerals(1);
				echo '<div class="' . $uid . '-back-badge kc-num">#' . $one . '</div>';
				echo '<div class="' . $uid . '-title kc-elm-title">' . esc_html($c['track']) . '</div>';
				echo '<div class="' . $uid . '-artist kc-elm-artist">' . esc_html($c['artist']) . '</div>';
				echo '</div></div></div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
}
