<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Intelligence Grid
 */
class ChartGrid extends Widget_Base {

	public function get_name() { return 'charts_grid'; }
	public function get_title() { return __( 'Intelligence Grid', 'charts' ); }
	public function get_icon() { return 'eicon-apps'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// 1. Data Source
		$this->start_controls_section( 'section_display', [ 'label' => __( 'Intelligence Query', 'charts' ) ] );

		$this->add_control( 'style_variant', [
			'label' => __( 'Display Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'standard' => 'Intelligence Cards (Data Rich)',
				'overlay' => 'Intelligence Overlay (Visual Bold)',
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

		// 2. Grid Layout
		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Grid Layout', 'charts' ) ] );

		$this->add_responsive_control( 'columns', [
			'label' => __( 'Columns', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ '1' => '1', '2' => '2', '3' => '3', '4' => '4' ],
			'default' => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
		]);

		$this->add_control( 'gap', [
			'label' => __( 'Grid Gap (px)', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 30 ],
		]);

		$this->end_controls_section();

		// 3. Content Visibility
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Content Configuration', 'charts' ) ] );

		$this->add_control( 'preview_rows', [
			'label' => __( 'Preview Rows', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 1,
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
		]);

		$this->end_controls_section();

		// 4. Premium Style
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Premium Aesthetic', 'charts' ) ] );

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

		$this->add_control( 'accent_color', [
			'label' => __( 'Custom Accent Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
		]);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		// 1. Data Hydration
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
			echo '<div class="kc-empty">No intelligence charts found.</div>';
			return;
		}

		// 2. Structural Classes
		$style_variant = $settings['style_variant'] ?? 'standard';
		$shadow_class = "kc-shadow-" . ($settings['card_shadow'] ?? 'sm');
		$gap = $settings['gap']['size'] ?? 30;

		$cols = [
			'd' => $settings['columns'] ?: 3,
			't' => $settings['columns_tablet'] ?: 2,
			'm' => $settings['columns_mobile'] ?: 1,
		];
?>
		<div class="kc-widget-root">
			<style>
				.kc-intelligence-grid { 
					display: grid; 
					grid-template-columns: repeat(<?php echo $cols['d']; ?>, 1fr); 
					gap: <?php echo $gap; ?>px; 
				}
				@media (max-width: 1024px) { .kc-intelligence-grid { grid-template-columns: repeat(<?php echo $cols['t']; ?>, 1fr); } }
				@media (max-width: 768px) { .kc-intelligence-grid { grid-template-columns: repeat(<?php echo $cols['m']; ?>, 1fr); } }
			</style>

			<div class="kc-intelligence-grid">
				<?php foreach ( $definitions as $def ) : ?>
					<div class="kc-grid-item">
						<?php 
							if ( $style_variant === 'overlay' ) {
								$this->render_overlay_card( $def, $settings, $shadow_class );
							} else {
								$this->render_standard_card( $def, $settings, $shadow_class );
							}
						?>
					</div>
				<?php endforeach; ?>
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
		$card_image = \Charts\Core\PublicIntegration::resolve_chart_image( $def, $top_rows );
?>
		<article class="kc-chart-card <?php echo $shadow_class; ?>" style="position: relative; height: 420px; border-radius: <?php echo $radius; ?>px; overflow: hidden; background: #000;">
			<img src="<?php echo esc_url($card_image); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.7;">
			<div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);"></div>

			<div style="position: absolute; inset: 0; padding: 32px; display: flex; flex-direction: column; justify-content: flex-end; z-index: 2;">
				<?php if ( $settings['show_label'] === 'yes' ) : ?>
					<span style="display: inline-block; padding: 4px 10px; background: <?php echo $accent; ?>; color: #fff; font-size: 8px; font-weight: 900; border-radius: 4px; text-transform: uppercase; margin-bottom: 12px; width: fit-content;">WEEKLY ANALYSIS</span>
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
