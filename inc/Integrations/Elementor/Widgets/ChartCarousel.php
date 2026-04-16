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

	public function get_style_depends() {
		return [ 'kc-public-style' ];
	}

	protected function register_controls() {
		// 1. Display Strategy
		$this->start_controls_section( 'section_display', [ 'label' => __( 'Display Strategy', 'charts' ) ] );

		$this->add_control( 'style_variant', [
			'label' => __( 'Display Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'standard' => 'Cards Carousel (Data Rich)',
				'overlay' => 'Overlay Carousel (Visual Bold)',
			],
			'default' => 'standard'
		]);

		$this->add_control( 'limit', [
			'label' => __( 'Maximum Charts', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 6
		] );

		$this->add_control( 'chart_selection_mode', [
			'label' => __( 'Selection Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'latest' => 'Automated: Latest Updated',
				'manual' => 'Curated: Specific Selection',
			],
			'default' => 'latest'
		]);

		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'selected_charts', [
			'label' => __( 'Select Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $options,
			'condition' => [ 'chart_selection_mode' => 'manual' ]
		] );

		$this->end_controls_section();

		// 2. Content Controls
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Content Configuration', 'charts' ) ] );

		$this->add_control( 'preview_rows', [
			'label' => __( 'Preview Rows', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0,
			'max' => 4,
			'default' => 3,
			'condition' => [ 'style_variant' => 'standard' ]
		]);

		$this->add_control( 'show_label', [
			'label' => __( 'Show Badge Label', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_cta', [
			'label' => __( 'Show CTA Button', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'cta_text', [
			'label' => __( 'CTA Text', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'EXPLORE CHART',
			'condition' => [ 'show_cta' => 'yes' ]
		]);

		$this->end_controls_section();

		// 3. Carousel Motion
		$this->start_controls_section( 'section_carousel', [ 'label' => __( 'Carousel Motion', 'charts' ) ] );

		$this->add_responsive_control( 'slides_to_show', [
			'label' => __( 'Slides to Show', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5' ],
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
			'default' => 'no',
		]);

		$this->add_control( 'loop', [
			'label' => __( 'Infinite Loop', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'arrows', [
			'label' => __( 'Navigation Arrows', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'dots', [
			'label' => __( 'Pagination Dots', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->end_controls_section();

		// 4. Style Nexus
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style Nexus', 'charts' ) ] );

		$this->add_control( 'card_radius', [
			'label' => __( 'Border Radius (px)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 24,
		]);

		$this->add_control( 'card_shadow', [
			'label' => __( 'Shadow Depth', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'none' => 'Flat',
				'sm' => 'Subtle',
				'md' => 'Medium Focus',
				'lg' => 'Deep Editorial'
			],
			'default' => 'sm'
		]);

		$this->add_control( 'overlay_gradient', [
			'label' => __( 'Overlay Strength (%)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ '%' ],
			'range' => [ '%' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'unit' => '%', 'size' => 80 ],
			'condition' => [ 'style_variant' => 'overlay' ]
		]);

		$this->add_control( 'accent_color', [
			'label' => __( 'Override Accent Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
		]);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		// Determine Definitions
		if ( $settings['chart_selection_mode'] === 'manual' && !empty($settings['selected_charts']) ) {
			$definitions = array();
			foreach($settings['selected_charts'] as $cid) {
				$def = $manager->get_definition($cid);
				if($def) $definitions[] = $def;
			}
		} else {
			$limit = !empty($settings['limit']) ? intval($settings['limit']) : 6;
			$definitions = \Charts\Core\PublicIntegration::get_eligible_definitions($limit);
		}

		if ( empty( $definitions ) ) {
			echo '<div class="kc-empty">No active intelligence charts found.</div>';
			return;
		}

		$instance_id = 'kc-carousel-' . $this->get_id();
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
		$shadow_class = "kc-shadow-" . ($settings['card_shadow'] ?? 'sm');
?>
		<div class="kc-widget-root <?php echo esc_attr($instance_id); ?>">
			<div class="kc-widget-carousel-wrap" data-carousel-config='<?php echo esc_attr(json_encode($carousel_config)); ?>'>
				<div class="kc-widget-carousel swiper-container" style="overflow: hidden; position: relative; width: 100%;">
					<div class="swiper-wrapper" style="display: flex; flex-wrap: nowrap; margin: 0 -<?php echo intval($settings['slides_spacing'] / 2); ?>px; transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);">
						<?php foreach ( $definitions as $def ) : ?>
							<div class="swiper-slide" style="flex: 0 0 <?php echo (100 / $carousel_config['slidesPerView']['desktop']); ?>%; box-sizing: border-box; padding: 0 <?php echo intval($settings['slides_spacing'] / 2); ?>px;">
								<div class="kc-slide-inner-box" style="height: 100%;">
									<?php 
										if ( $style_variant === 'overlay' ) {
											$this->render_overlay_card( $def, $settings, $shadow_class );
										} else {
											$this->render_standard_card( $def, $settings, $shadow_class );
										}
									?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<?php if ( $settings['arrows'] === 'yes' ) : ?>
						<button class="kc-carousel-nav kc-prev" style="position: absolute; top: 50%; left: 0; z-index: 20; transform: translateY(-50%); border: none; background: #fff; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
						<button class="kc-carousel-nav kc-next" style="position: absolute; top: 50%; right: 0; z-index: 20; transform: translateY(-50%); border: none; background: #fff; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
					<?php endif; ?>
				</div>
			</div>
		</div>
<?php
	}

	private function render_standard_card( $def, $settings, $shadow_class ) {
		$rows_limit = !empty($settings['preview_rows']) ? intval($settings['preview_rows']) : 3;
		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, $rows_limit );
		$accent = !empty($settings['accent_color']) ? $settings['accent_color'] : (!empty($def->accent_color) ? $def->accent_color : 'var(--k-accent)');
		$radius = intval($settings['card_radius'] ?? 24);
		$card_image = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $rows );
?>
		<article class="kc-chart-card <?php echo $shadow_class; ?>" style="background: #fff; border: 1px solid var(--k-border); border-radius: <?php echo $radius; ?>px; overflow: hidden; height: 100%; display: flex; flex-direction: column;">
			<div class="kc-card-hero" style="position: relative; height: 180px; overflow: hidden; background: <?php echo $accent; ?>; display: flex; flex-direction: column; justify-content: flex-end; padding: 24px;">
				<img src="<?php echo esc_url($card_image); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.8;">
				<div class="kc-hero-overlay" style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.4) 0%, transparent 100%);"></div>
				
				<div style="position: relative; z-index: 2;">
					<?php if ( $settings['show_label'] === 'yes' ) : ?>
						<span style="display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; color: #fff; letter-spacing: 0.1em; opacity: 0.8; margin-bottom: 4px;">Weekly Intelligence</span>
					<?php endif; ?>
					<h3 style="margin: 0; font-size: 24px; font-weight: 950; color: #fff; letter-spacing: -0.02em; line-height: 1.1;"><?php echo esc_html($def->title); ?></h3>
				</div>
			</div>

			<div class="kc-card-rows" style="padding: 12px 0; flex-grow: 1;">
				<?php foreach ( $rows as $e ) : 
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
				?>
					<div class="kc-preview-row" style="display: flex; align-items: center; gap: 12px; padding: 12px 24px; border-bottom: 1px solid var(--k-divider);">
						<span style="font-size: 12px; font-weight: 900; color: <?php echo $accent; ?>; width: 16px;"><?php echo $e->rank_position; ?></span>
						<img src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 36px; height: 36px; border-radius: 6px; object-fit: cover;">
						<div style="overflow: hidden;">
							<span style="display: block; font-size: 13px; font-weight: 800; color: var(--k-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($resolved['title']); ?></span>
							<span style="display: block; font-size: 10px; font-weight: 600; color: var(--k-text-muted);"><?php echo esc_html($resolved['subtitle']); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $settings['show_cta'] === 'yes' ) : ?>
				<div style="padding: 20px 24px; border-top: 1px solid var(--k-divider); text-align: center;">
					<a href="<?php echo home_url('/charts/' . $def->slug); ?>" style="font-size: 11px; font-weight: 900; color: <?php echo $accent; ?>; text-decoration: none; letter-spacing: 0.05em;"><?php echo strtoupper(esc_html($settings['cta_text'])); ?> &rarr;</a>
				</div>
			<?php endif; ?>
		</article>
<?php
	}

	private function render_overlay_card( $def, $settings, $shadow_class ) {
		$top_rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, 1 );
		$top = !empty($top_rows) ? $top_rows[0] : null;
		$accent = !empty($settings['accent_color']) ? $settings['accent_color'] : (!empty($def->accent_color) ? $def->accent_color : 'var(--k-accent)');
		$radius = intval($settings['card_radius'] ?? 24);
		$opacity = intval($settings['overlay_gradient']['size'] ?? 80) / 100;
		$card_image = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $top_rows );
?>
		<article class="kc-chart-card <?php echo $shadow_class; ?>" style="position: relative; height: 420px; border-radius: <?php echo $radius; ?>px; overflow: hidden; background: #000; group;">
			<img src="<?php echo esc_url($card_image); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.7; transition: transform 0.4s ease;">
			<div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,<?php echo $opacity; ?>) 0%, transparent 100%);"></div>

			<div style="position: absolute; inset: 0; padding: 32px; display: flex; flex-direction: column; justify-content: flex-end; z-index: 2;">
				<?php if ( $settings['show_label'] === 'yes' ) : ?>
					<span style="display: inline-block; padding: 4px 10px; background: <?php echo $accent; ?>; color: #fff; font-size: 8px; font-weight: 900; border-radius: 4px; text-transform: uppercase; margin-bottom: 12px; width: fit-content;"><?php echo strtoupper($def->chart_type); ?></span>
				<?php endif; ?>
				
				<h3 style="margin: 0; color: #fff; font-size: 28px; font-weight: 950; letter-spacing: -0.04em; line-height: 1;"><?php echo esc_html($def->title); ?></h3>
				
				<?php if ( $top ) : 
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($top, $def);
				?>
					<div style="margin-top: 16px; display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;">
						<span style="font-size: 16px; font-weight: 950; color: #fff; opacity: 0.6;">#1</span>
						<div style="min-width: 0;">
							<span style="display: block; font-size: 14px; font-weight: 850; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($resolved['title']); ?></span>
							<span style="display: block; font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.6);"><?php echo esc_html($resolved['subtitle']); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<a href="<?php echo home_url('/charts/' . $def->slug); ?>" style="margin-top: 24px; display: flex; align-items: center; gap: 8px; color: #fff; font-size: 11px; font-weight: 900; text-decoration: none; opacity: 0.8;"><?php echo strtoupper(esc_html($settings['cta_text'])); ?> &rarr;</a>
			</div>
		</article>
<?php
	}
}
