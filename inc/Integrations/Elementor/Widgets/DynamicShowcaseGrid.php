<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor Widget: Dynamic Showcase Grid (Shows Chart Definitions, not tracks)
 */
class DynamicShowcaseGrid extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_dynamic_showcase_grid'; }
	public function get_title() { return __( 'Charts: Dynamic Showcase Grid', 'charts' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {
		// ─── QUERY SETTINGS ───────────────────────────────────────
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Query Settings', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'query_mode', [
			'label'   => __( 'Query Mode', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'all'    => __( 'All Active Charts', 'charts' ),
				'manual' => __( 'Manual Selection', 'charts' ),
			],
			'default' => 'all',
		] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions( true );
		$chart_options = [];
		if ( $defs ) {
			foreach ( $defs as $d ) {
				$chart_options[ $d->id ] = $d->title . ( ! empty( $d->platform ) ? " ({$d->platform})" : '' );
			}
		}

		$this->add_control( 'selected_charts', [
			'label'       => __( 'Select Specific Charts', 'charts' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $chart_options,
			'default'     => ! empty( $chart_options ) ? array_slice( array_keys( $chart_options ), 0, 4 ) : [],
			'condition'   => [ 'query_mode' => 'manual' ],
			'label_block' => true,
		] );

		$this->add_control( 'filter_platform', [
			'label'     => __( 'Filter Platform', 'charts' ),
			'type'      => Controls_Manager::SELECT,
			'options'   => [
				'all'       => __( 'All Platforms', 'charts' ),
				'spotify'   => 'Spotify',
				'youtube'   => 'YouTube',
				'billboard' => 'Billboard',
				'anghami'   => 'Anghami',
				'apple'     => 'Apple Music',
				'shazam'    => 'Shazam',
			],
			'default'   => 'all',
			'condition' => [ 'query_mode' => 'all' ],
		] );

		$this->add_control( 'limit', [
			'label'     => __( 'Number of Charts', 'charts' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 6,
			'min'       => 1,
			'max'       => 50,
			'condition' => [ 'query_mode' => 'all' ],
		] );

		$this->add_control( 'layout_style', [
			'label'   => __( 'Design Style', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'bento'     => __( 'Bento Matrix', 'charts' ),
				'carousel'  => __( 'Slider / Carousel', 'charts' ),
				'accordion' => __( 'Expanding Accordion', 'charts' ),
				'grid'      => __( 'Standard Card Grid', 'charts' ),
			],
			'default' => 'bento',
		] );

		$this->add_responsive_control( 'columns', [
			'label'     => __( 'Grid Columns', 'charts' ),
			'type'      => Controls_Manager::SELECT,
			'options'   => [
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
			],
			'default'   => '3',
			'condition' => [ 'layout_style' => 'grid' ],
			'selectors' => [
				'{{WRAPPER}} .kc-dsg-standard-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
			],
		] );

		$this->end_controls_section();

		// ─── CARD ELEMENTS & BADGES ──────────────────────────────
		$this->start_controls_section( 'card_elements_section', [
			'label' => __( 'Card Elements', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'show_platform_badge', [
			'label'   => __( 'Show Platform Badge', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'show_leader_meta', [
			'label'   => __( 'Show Current #1 Leader', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'show_view_button', [
			'label'   => __( 'Show Explore Button', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'button_text', [
			'label'     => __( 'Button Text', 'charts' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'استعرض القائمة', 'charts' ),
			'condition' => [ 'show_view_button' => 'yes' ],
		] );

		$this->add_control( 'hover_animation', [
			'label'   => __( 'Hover Animation', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'zoom' => __( 'Zoom Cover', 'charts' ),
				'lift' => __( 'Elevate Card', 'charts' ),
				'glow' => __( 'Glow Border', 'charts' ),
				'none' => __( 'None', 'charts' ),
			],
			'default' => 'zoom',
		] );

		$this->end_controls_section();

		// ─── CAROUSEL SETTINGS ───────────────────────────────────
		$this->start_controls_section( 'carousel_section', [
			'label'     => __( 'Carousel Settings', 'charts' ),
			'tab'       => Controls_Manager::TAB_CONTENT,
			'condition' => [ 'layout_style' => 'carousel' ],
		] );

		$this->add_control( 'carousel_effect', [
			'label'   => __( 'Carousel 3D Effect', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'coverflow' => '3D Coverflow',
				'cards'     => 'Stacked Cards',
				'slide'     => 'Standard Slide',
			],
			'default' => 'coverflow',
		] );

		$this->add_control( 'carousel_autoplay', [
			'label'   => __( 'Autoplay', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'carousel_nav', [
			'label'   => __( 'Show Pagination Dots', 'charts' ),
			'type'    => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->end_controls_section();

		// ─── STYLING ─────────────────────────────────────────────
		$this->start_controls_section( 'styling_section', [
			'label' => __( 'Card Styling', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent_color', [
			'label'     => __( 'Accent Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#8b5cf6',
			'selectors' => [
				'{{WRAPPER}} .kc-dsg-card' => '--dsg-accent: {{VALUE}};',
			],
		] );

		$this->add_control( 'border_radius', [
			'label'      => __( 'Border Radius', 'charts' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'    => [ 'unit' => 'px', 'size' => 16 ],
			'selectors'  => [
				'{{WRAPPER}} .kc-dsg-card' => 'border-radius: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .kc-dsg-card',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager  = new \Charts\Admin\SourceManager();
		$all_defs = $manager->get_definitions( true );

		if ( empty( $all_defs ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055;">No charts found.</div>';
			}
			return;
		}

		// Filter charts based on query settings
		$charts = [];
		if ( $settings['query_mode'] === 'manual' ) {
			$selected = (array) ( $settings['selected_charts'] ?? [] );
			foreach ( $all_defs as $d ) {
				if ( in_array( (string) $d->id, $selected, true ) || in_array( (int) $d->id, $selected, true ) ) {
					$charts[] = $d;
				}
			}
		} else {
			$plat_filter = $settings['filter_platform'] ?? 'all';
			$limit       = intval( $settings['limit'] ?? 6 );
			foreach ( $all_defs as $d ) {
				if ( $plat_filter !== 'all' && strtolower( $d->platform ?? '' ) !== $plat_filter ) {
					continue;
				}
				$charts[] = $d;
				if ( count( $charts ) >= $limit ) {
					break;
				}
			}
		}

		if ( empty( $charts ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308;">No charts match the current filter.</div>';
			}
			return;
		}

		$layout     = $settings['layout_style'] ?? 'bento';
		$hover_anim = $settings['hover_animation'] ?? 'zoom';
		$uid        = 'kc-dsg-' . $this->get_id();
		$uid_safe   = str_replace( '-', '_', $uid );

		// Preload top entries for charts to show the #1 leader
		global $wpdb;
		$chart_leaders = [];
		if ( $settings['show_leader_meta'] === 'yes' ) {
			foreach ( $charts as $c ) {
				$entries = \Charts\Core\PublicIntegration::get_preview_entries( $c, 1 );
				if ( ! empty( $entries[0] ) ) {
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name( $entries[0], $c );
					$chart_leaders[ $c->id ] = [
						'title'  => $resolved['title'] ?? '',
						'artist' => $resolved['subtitle'] ?? '',
						'image'  => (!empty($entries[0]->resolved_image) ? $entries[0]->resolved_image : $entries[0]->cover_image) ?: '',
					];
				}
			}
		}

		?>
		<style>
		.<?php echo $uid; ?>-wrap { width: 100%; position: relative; direction: rtl; font-family: "Cairo", "Inter", sans-serif; }
		.<?php echo $uid; ?>-card {
			position: relative;
			background: #0f172a;
			border-radius: 16px;
			overflow: hidden;
			cursor: pointer;
			display: block;
			text-decoration: none;
			color: #fff;
			transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.<?php echo $uid; ?>-img {
			position: absolute;
			inset: 0;
			background-size: cover;
			background-position: top center;
			transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
			z-index: 1;
		}
		.<?php echo $uid; ?>-overlay {
			position: absolute;
			inset: 0;
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			padding: 24px;
			z-index: 2;
			background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.5) 50%, rgba(15,23,42,0.15) 100%);
			transition: all 0.3s;
		}
		.<?php echo $uid; ?>-top-meta {
			position: absolute;
			top: 16px;
			right: 16px;
			left: 16px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			z-index: 3;
		}
		.<?php echo $uid; ?>-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(255,255,255,0.15);
			backdrop-filter: blur(10px);
			padding: 4px 10px;
			border-radius: 20px;
			font-size: 11px;
			font-weight: 800;
			letter-spacing: 0.5px;
			color: #fff;
			text-transform: uppercase;
			border: 1px solid rgba(255,255,255,0.1);
		}
		.<?php echo $uid; ?>-title {
			font-size: 22px;
			font-weight: 900;
			margin: 0 0 6px 0;
			line-height: 1.25;
			color: #fff;
			text-shadow: 0 2px 10px rgba(0,0,0,0.5);
		}
		.<?php echo $uid; ?>-leader {
			font-size: 13px;
			color: #cbd5e1;
			display: flex;
			align-items: center;
			gap: 6px;
			margin-bottom: 12px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.<?php echo $uid; ?>-leader strong {
			color: #facc15;
		}
		.<?php echo $uid; ?>-btn {
			align-self: flex-start;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12px;
			font-weight: 800;
			padding: 6px 14px;
			border-radius: 8px;
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(8px);
			color: #fff;
			transition: background 0.2s, transform 0.2s;
			margin-top: 4px;
		}
		.<?php echo $uid; ?>-card:hover .<?php echo $uid; ?>-btn {
			background: #8b5cf6;
			transform: translateX(-4px);
		}

		/* Hover animations */
		<?php if ( $hover_anim === 'zoom' ) : ?>
		.<?php echo $uid; ?>-card:hover .<?php echo $uid; ?>-img { transform: scale(1.08); }
		<?php elseif ( $hover_anim === 'lift' ) : ?>
		.<?php echo $uid; ?>-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
		<?php elseif ( $hover_anim === 'glow' ) : ?>
		.<?php echo $uid; ?>-card:hover { box-shadow: 0 0 30px rgba(139,92,246,0.5); border: 1px solid rgba(139,92,246,0.8); }
		<?php endif; ?>

		/* ── BENTO MATRIX LAYOUT ── */
		<?php if ( $layout === 'bento' ) : ?>
		.<?php echo $uid; ?>-bento-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 20px;
			grid-auto-rows: 240px;
		}
		.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card { height: 100%; width: 100%; }
		.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(1) { grid-column: 1 / span 2; grid-row: 1 / span 2; }
		.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(1) .<?php echo $uid; ?>-title { font-size: 30px; }
		.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(2) { grid-column: 3 / span 2; grid-row: 1 / span 1; }
		.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(3) { grid-column: 3 / span 2; grid-row: 2 / span 1; }
		@media (max-width: 1024px) {
			.<?php echo $uid; ?>-bento-grid { grid-template-columns: repeat(2, 1fr); }
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(1) { grid-column: 1 / span 2; grid-row: 1 / span 2; }
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(2) { grid-column: 1 / span 2; grid-row: 3 / span 1; }
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(3) { grid-column: 1 / span 2; grid-row: 4 / span 1; }
		}
		@media (max-width: 768px) {
			.<?php echo $uid; ?>-bento-grid { grid-template-columns: 1fr; grid-auto-rows: 260px; }
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(1),
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(2),
			.<?php echo $uid; ?>-bento-grid .<?php echo $uid; ?>-card:nth-child(3) { grid-column: 1 / span 1; grid-row: auto; }
		}
		<?php endif; ?>

		/* ── ACCORDION LAYOUT ── */
		<?php if ( $layout === 'accordion' ) : ?>
		.<?php echo $uid; ?>-accordion-grid { display: flex; height: 500px; gap: 14px; }
		.<?php echo $uid; ?>-accordion-grid .<?php echo $uid; ?>-card { flex: 1; height: 100%; }
		.<?php echo $uid; ?>-accordion-grid .<?php echo $uid; ?>-card:hover { flex: 3.5; }
		@media (max-width: 768px) {
			.<?php echo $uid; ?>-accordion-grid { flex-direction: column; height: 800px; }
		}
		<?php endif; ?>

		/* ── CAROUSEL LAYOUT ── */
		<?php if ( $layout === 'carousel' ) : ?>
		.<?php echo $uid; ?>-carousel-wrap { width: 100%; padding: 30px 0; overflow: hidden; }
		.<?php echo $uid; ?>-carousel-wrap .<?php echo $uid; ?>-card { width: 320px; height: 420px; }
		<?php endif; ?>

		/* ── STANDARD GRID ── */
		<?php if ( $layout === 'grid' ) : ?>
		.<?php echo $uid; ?>-standard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
		.<?php echo $uid; ?>-standard-grid .<?php echo $uid; ?>-card { height: 320px; }
		@media (max-width: 1024px) { .<?php echo $uid; ?>-standard-grid { grid-template-columns: repeat(2, 1fr); } }
		@media (max-width: 640px) { .<?php echo $uid; ?>-standard-grid { grid-template-columns: 1fr; } }
		<?php endif; ?>
		</style>

		<div class="<?php echo $uid; ?>-wrap">
		<?php
		$render_card = function( $chart ) use ( $uid, $settings, $layout, $chart_leaders ) {
			$chart_url = home_url( '/charts/' . $chart->slug );
			$image = ! empty( $chart->image ) ? $chart->image : '';
			if ( empty( $image ) && ! empty( $chart_leaders[ $chart->id ]['image'] ) ) {
				$image = $chart_leaders[ $chart->id ]['image'];
			}
			if ( empty( $image ) ) {
				$image = CHARTS_URL . 'public/assets/img/placeholder.png';
			}

			$slide_class = ( $layout === 'carousel' ) ? 'swiper-slide' : '';
			$platform    = strtoupper( $chart->platform ?? 'GLOBAL' );
			$country     = strtoupper( $chart->country_code ?? '' );
			$badge_label = $platform . ( $country ? " • {$country}" : '' );
			$leader      = $chart_leaders[ $chart->id ] ?? null;
			?>
			<a href="<?php echo esc_url( $chart_url ); ?>" class="<?php echo $uid; ?>-card <?php echo $slide_class; ?>">
				<div class="<?php echo $uid; ?>-img" style="background-image: url('<?php echo esc_url( $image ); ?>');"></div>
				
				<?php if ( $settings['show_platform_badge'] === 'yes' ) : ?>
				<div class="<?php echo $uid; ?>-top-meta">
					<span class="<?php echo $uid; ?>-badge"><?php echo esc_html( $badge_label ); ?></span>
				</div>
				<?php endif; ?>

				<div class="<?php echo $uid; ?>-overlay">
					<h3 class="<?php echo $uid; ?>-title"><?php echo esc_html( $chart->title ); ?></h3>

					<?php if ( $settings['show_leader_meta'] === 'yes' && $leader && ! empty( $leader['title'] ) ) : ?>
					<div class="<?php echo $uid; ?>-leader">
						<span>#1 المتصدر: <strong><?php echo esc_html( $leader['title'] ); ?></strong> — <?php echo esc_html( $leader['artist'] ); ?></span>
					</div>
					<?php endif; ?>

					<?php if ( $settings['show_view_button'] === 'yes' ) : ?>
					<span class="<?php echo $uid; ?>-btn">
						<?php echo esc_html( $settings['button_text'] ); ?>
						<span style="font-size:14px; line-height:1;">←</span>
					</span>
					<?php endif; ?>
				</div>
			</a>
			<?php
		};

		if ( $layout === 'bento' ) {
			echo '<div class="' . $uid . '-bento-grid">';
			foreach ( $charts as $c ) {
				$render_card( $c );
			}
			echo '</div>';
		} elseif ( $layout === 'accordion' ) {
			echo '<div class="' . $uid . '-accordion-grid">';
			foreach ( $charts as $c ) {
				$render_card( $c );
			}
			echo '</div>';
		} elseif ( $layout === 'grid' ) {
			echo '<div class="' . $uid . '-standard-grid">';
			foreach ( $charts as $c ) {
				$render_card( $c );
			}
			echo '</div>';
		} elseif ( $layout === 'carousel' ) {
			echo '<div class="swiper ' . $uid . '-carousel-wrap" id="' . $uid . '-swiper">';
			echo '<div class="swiper-wrapper">';
			foreach ( $charts as $c ) {
				$render_card( $c );
			}
			echo '</div>';
			if ( $settings['carousel_nav'] === 'yes' ) {
				echo '<div class="swiper-pagination"></div>';
			}
			echo '</div>';

			$effect = $settings['carousel_effect'] ?? 'coverflow';
			?>
			<script>
			function initShowcase_<?php echo $uid_safe; ?>() {
				if ( typeof Swiper !== "undefined" ) {
					new Swiper("#<?php echo $uid; ?>-swiper", {
						effect: "<?php echo esc_js( $effect ); ?>",
						grabCursor: true,
						centeredSlides: true,
						slidesPerView: "auto",
						loop: true,
						<?php if ( $effect === 'coverflow' ) : ?>
						coverflowEffect: { rotate: 25, stretch: 0, depth: 100, modifier: 1, slideShadows: true },
						<?php endif; ?>
						<?php if ( $settings['carousel_autoplay'] === 'yes' ) : ?>
						autoplay: { delay: 3500, disableOnInteraction: false },
						<?php endif; ?>
						<?php if ( $settings['carousel_nav'] === 'yes' ) : ?>
						pagination: { el: ".swiper-pagination", clickable: true }
						<?php endif; ?>
					});
				}
			}
			if ( document.readyState === "complete" || document.readyState === "interactive" ) {
				setTimeout(initShowcase_<?php echo $uid_safe; ?>, 100);
			} else {
				document.addEventListener("DOMContentLoaded", initShowcase_<?php echo $uid_safe; ?>);
			}
			</script>
			<?php
		}
		?>
		</div>
		<?php
	}
}
