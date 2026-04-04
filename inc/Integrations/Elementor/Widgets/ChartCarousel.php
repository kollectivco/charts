<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Chart Carousel
 */
class ChartCarousel extends Widget_Base {

	public function get_name() { return 'charts_carousel'; }
	public function get_title() { return __( 'Intelligence Carousel', 'charts' ); }
	public function get_icon() { return 'eicon-post-slider'; }
	public function get_categories() { return [ 'charts' ]; }

	public function get_script_depends() {
		return [ 'kc-public' ];
	}

	protected function register_controls() {
		// 1. Query Config
		\Charts\Integrations\Elementor\ControlHelper::add_query_controls( $this, false );

		// 2. Carousel Layout Configuration
		$this->start_controls_section(
			'section_carousel_layout',
			[ 'label' => __( 'Carousel Configuration', 'charts' ) ]
		);

		$this->add_control( 'style_variant', [
			'label' => __( 'Card Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'standard' => 'Standard Cards',
				'overlay' => 'Premium Overlay',
				'minimal' => 'Minimal / Compact',
				'editorial' => 'Editorial Highlight'
			],
			'default' => 'standard'
		]);

		$this->add_responsive_control( 'slides_to_show', [
			'label' => __( 'Slides to Show', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
			],
			'devices' => [ 'desktop', 'tablet', 'mobile' ],
			'desktop_default' => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
		]);

		$this->add_control( 'slides_spacing', [
			'label' => __( 'Space Between (px)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 30,
		]);

		$this->add_control( 'autoplay', [
			'label' => __( 'Autoplay', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'loop', [
			'label' => __( 'Infinite Loop', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'arrows', [
			'label' => __( 'Show Navigation Arrows', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'dots', [
			'label' => __( 'Show Pagination Dots', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->end_controls_section();

		// 3. Visibility Toggles
		\Charts\Integrations\Elementor\ControlHelper::add_visibility_controls( $this );

		// 4. Styling options
		\Charts\Integrations\Elementor\ControlHelper::add_style_controls( $this );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		$definitions = $manager->get_definitions( true );

		// Apply Filters from Query Controls
		if ( $settings['chart_type'] !== 'all' ) {
			$definitions = array_filter( $definitions, function($d) use ($settings) {
				return $d->chart_type === $settings['chart_type'];
			});
		}
		if ( !empty($settings['market_filter']) && $settings['market_filter'] !== 'all' ) {
			$definitions = array_filter( $definitions, function($d) use ($settings) {
				return $d->country_code === $settings['market_filter'];
			});
		}

		$definitions = array_slice( $definitions, 0, $settings['limit'] );

		if ( empty( $definitions ) ) {
			echo '<div class="kc-empty">No charts found matching criteria.</div>';
			return;
		}

		// Swiper (Carousel) Configuration Object
		$carousel_config = [
			'slidesPerView' => [
				'desktop' => intval($settings['slides_to_show'] ?: 3),
				'tablet' => intval($settings['slides_to_show_tablet'] ?: 2),
				'mobile' => intval($settings['slides_to_show_mobile'] ?: 1),
			],
			'spaceBetween' => intval($settings['slides_spacing'] ?: 30),
			'autoplay' => ($settings['autoplay'] === 'yes'),
			'loop' => ($settings['loop'] === 'yes'),
			'arrows' => ($settings['arrows'] === 'yes'),
			'dots' => ($settings['dots'] === 'yes'),
		];

		$style_variant = $settings['style_variant'] ?? 'standard';
?>
		<div class="kc-root">
			<div class="kc-widget-carousel-wrap" data-carousel-config='<?php echo esc_attr(json_encode($carousel_config)); ?>'>
				<div class="kc-widget-carousel swiper-container">
					<div class="swiper-wrapper" style="display: flex; gap: <?php echo intval($settings['slides_spacing']); ?>px; overflow: hidden;">
						<?php foreach ( $definitions as $def ) : ?>
							<div class="swiper-slide" style="flex-shrink: 0; width: calc( (100% / <?php echo $carousel_config['slidesPerView']['desktop']; ?>) - <?php echo $carousel_config['spaceBetween']; ?>px );">
								<?php $this->render_card( $def, $settings ); ?>
							</div>
						<?php endforeach; ?>
					</div>
					
					<?php if ( $settings['arrows'] === 'yes' ) : ?>
						<div class="kc-carousel-nav kc-prev" style="position: absolute; top: 50%; left: -20px; z-index: 10; transform: translateY(-50%); cursor: pointer; background: var(--k-surface); border: 1px solid var(--k-border); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--k-shadow-md);">&larr;</div>
						<div class="kc-carousel-nav kc-next" style="position: absolute; top: 50%; right: -20px; z-index: 10; transform: translateY(-50%); cursor: pointer; background: var(--k-surface); border: 1px solid var(--k-border); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: var(--k-shadow-md);">&rarr;</div>
					<?php endif; ?>

					<?php if ( $settings['dots'] === 'yes' ) : ?>
						<div class="kc-carousel-dots" style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;"></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
<?php
	}

	private function render_card( $def, $settings ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3
		", $def->chart_type, $def->country_code ) );

		$style       = $settings['style_variant'];
		$show_cover  = $settings['show_cover'] === 'yes';
		$show_meta   = $settings['show_meta'] === 'yes';
		$show_artist = $settings['show_artist'] === 'yes';
		$show_cta    = $settings['show_cta'] === 'yes';
		$accent      = !empty($def->accent_color) ? $def->accent_color : 'var(--k-accent)';
?>
		<article class="kc-chart-card kc-widget-card style-<?php echo esc_attr($style); ?>" style="display:flex; flex-direction:column; background:var(--k-surface); border:1px solid var(--k-border); border-radius:var(--k-radius-lg); overflow:hidden; transition:transform 0.2s, box-shadow 0.2s; height: 100%;">
			
			<?php if ( $style === 'overlay' ) : ?>
				<div class="kc-card-header" style="height:220px; background:var(--k-chart-bg); position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:flex-end; padding: 24px;">
					<img src="<?php echo esc_url($def->cover_image_url ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;">
					<div class="kc-card-overlay" style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 60%);"></div>
					<div style="position: relative; z-index: 2; color: #fff;">
						<div class="kc-card-label kc-meta" style="font-size:8px; font-weight:850; text-transform:uppercase; letter-spacing:0.1em; color:rgba(255,255,255,0.7); margin-bottom:4px;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></div>
						<div class="kc-card-title kc-title" style="font-size:18px; font-weight:900; letter-spacing:-0.01em; margin:0;"><?php echo esc_html($def->title); ?></div>
					</div>
				</div>
			<?php else : ?>
				<?php if ( $show_cover ) : ?>
				<div class="kc-card-header" style="height:120px; background:<?php echo $accent; ?>; padding:24px; position: relative;">
					<?php if ( !empty($def->cover_image_url) ) : ?>
						<img src="<?php echo esc_url($def->cover_image_url); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.3;">
					<?php endif; ?>
					<div class="kc-card-title kc-title" style="font-size:18px; font-weight:900; letter-spacing:-0.02em; margin:0; position:relative; z-index:2; color:#fff;"><?php echo esc_html($def->title); ?></div>
					<?php if ( $show_meta ) : ?>
						<div class="kc-card-label kc-meta" style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:rgba(255,255,255,0.8); margin-bottom:4px; position:relative; z-index:2;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></div>
					<?php endif; ?>
				</div>
				<?php else : ?>
				<div style="padding:24px 24px 0;">
					<?php if ( $show_meta ) : ?>
						<div class="kc-card-label kc-meta" style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:var(--k-text-muted); margin-bottom:4px;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></div>
					<?php endif; ?>
					<div class="kc-card-title kc-title" style="font-size:18px; font-weight:900; letter-spacing:-0.02em; margin:0; color:var(--k-text);"><?php echo esc_html($def->title); ?></div>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="kc-card-list" style="padding:12px 0; flex-grow:1;">
				<?php if ( $style !== 'minimal' ) : ?>
				<?php foreach ( $rows as $row ) : ?>
					<div class="kc-card-entry" style="display:flex; align-items:center; gap:10px; padding:8px 20px; border-bottom:1px solid var(--k-divider);">
						<div class="kc-entry-rank" style="font-size:11px; font-weight:900; width:12px; color: <?php echo $accent; ?>;"><?php echo $row->rank_position; ?></div>
						<div class="kc-entry-info" style="min-width:0; flex-grow:1;">
							<div class="kc-entry-name" style="font-size:12px; font-weight:750; color:var(--k-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($row->track_name); ?></div>
							<?php if ( $show_artist ) : ?>
								<div class="kc-entry-artist" style="font-size:10px; font-weight:500; color:var(--k-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($row->artist_names); ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if ( $show_cta ) : ?>
			<div class="kc-card-footer" style="padding:16px 20px; border-top:1px solid var(--k-divider); display:flex; justify-content:center; align-items:center; margin-top:auto;">
				<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta" style="font-size:10px; font-weight:900; color:var(--k-accent-purple); text-decoration:none; display:flex; align-items:center; gap:4px; letter-spacing: 0.05em;">
					<?php echo strtoupper(esc_html($settings['card_cta_text'] ?? 'EXPLORE CHART')); ?> &rarr;
				</a>
			</div>
			<?php endif; ?>
		</article>
<?php
	}
}
