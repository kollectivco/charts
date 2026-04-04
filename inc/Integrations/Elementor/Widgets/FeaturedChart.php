<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Featured Chart Card
 */
class FeaturedChart extends Widget_Base {

	public function get_name() { return 'featured_chart'; }
	public function get_title() { return __( 'Featured Chart Spot', 'charts' ); }
	public function get_icon() { return 'eicon-featured-item'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Spotlight Config', 'charts' ) ] );
		
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $options,
			'default' => array_key_first($options)
		] );

		$this->add_control( 'preview_rows', [
			'label' => __( 'Preview Rows', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5
		] );

		$this->end_controls_section();

		\Charts\Integrations\Elementor\ControlHelper::add_layout_controls( $this, [
			'featured' => 'Spotlight Main',
			'compact' => 'Compact Spotlight'
		]);

		\Charts\Integrations\Elementor\ControlHelper::add_visibility_controls( $this, [
			'show_artist', 'show_movement', 'show_cta'
		]);

		\Charts\Integrations\Elementor\ControlHelper::add_style_controls( $this );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		if ( empty($settings['chart_id']) ) return;
		$def = $manager->get_definition( $settings['chart_id'] );
		if ( ! $def ) return;

		global $wpdb;
		$limit = !empty($settings['preview_rows']) ? intval($settings['preview_rows']) : 5;

		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT %d
		", $def->chart_type, $def->country_code, $limit ) );

		if ( empty($rows) ) return;

		$style_variant = $settings['style_variant'] ?? 'featured';
		$show_artist   = $settings['show_artist'] !== 'no';
		$show_movement = $settings['show_movement'] !== 'no';
		$show_cta      = $settings['show_cta'] !== 'no';
?>
		<div class="kc-root">
			<div class="kc-widget-card kc-card kc-variant-<?php echo esc_attr($style_variant); ?>" style="padding: 0; min-width: 100%; background: var(--k-surface); border-radius: var(--k-radius-lg); border: 1px solid var(--k-border); overflow: hidden; box-shadow: var(--k-shadow-md);">
				<div class="kc-list-header" style="<?php echo $style_variant === 'compact' ? 'padding: 24px;' : 'padding: 40px;'; ?> background: var(--k-surface-alt); border-bottom: 1px solid var(--k-divider); display: flex; justify-content: space-between; align-items: flex-end;">
					<div class="kc-list-title">
						<span class="kc-brand-name kc-meta" style="margin-bottom: 8px; font-size: 10px; display: block; letter-spacing: 0.1em; color: var(--k-text-muted);">FEATURED MARKET • <?php echo strtoupper($def->country_code); ?></span>
						<h3 class="kc-title" style="font-size: 1.8rem; font-weight: 850; letter-spacing: -0.02em; color: var(--k-text); margin: 0;"><?php echo esc_html($def->title); ?></h3>
						<?php if ( $style_variant === 'featured' ) : ?>
						<p style="color: var(--k-text-dim); font-size: 14px; margin-top: 8px; font-weight: 500; font-family: Inter, sans-serif;">
							<?php echo esc_html($def->chart_summary); ?>
						</p>
						<?php endif; ?>
					</div>
					<?php if ( $show_cta ) : ?>
					<div style="text-align: right; flex-shrink: 0; margin-left: 24px;">
						<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-btn" style="padding: 10px 24px; font-size: 13px; text-decoration: none; font-weight: 800; color: var(--k-text); border: 1px solid var(--k-border); border-radius: 40px; background: var(--k-surface); transition: background 0.2s;">
							<?php echo esc_html($settings['card_cta_text'] ?? 'VIEW FULL CHART'); ?> &rarr;
						</a>
					</div>
					<?php endif; ?>
				</div>
				<div class="kc-list-content" style="padding: 16px 0 32px; background: var(--k-surface);">
					<?php foreach ( $rows as $idx => $row ) : ?>
						<div class="kc-preview-row" style="<?php echo $style_variant === 'compact' ? 'padding: 12px 24px;' : 'padding: 16px 40px;'; ?> display: flex; align-items: center; border-bottom: 1px solid var(--k-divider);">
							<span class="kc-preview-rank" style="font-size: 1.1rem; font-weight: 900; width: 30px; <?php echo ($idx === 0) ? 'color: var(--k-accent); font-size: 1.5rem;' : 'color: var(--k-text);'; ?>"><?php echo $row->rank_position; ?></span>
							<div class="kc-preview-info" style="flex-grow: 1; padding: 0 16px;">
								<span class="kc-preview-name" style="font-size: 15px; display: block; color: var(--k-text); <?php echo ($idx === 0) ? 'font-weight: 850;' : 'font-weight: 700;'; ?>"><?php echo esc_html($row->track_name); ?></span>
								<?php if ( $show_artist ) : ?>
									<span class="kc-preview-artist" style="font-size: 12px; font-weight: 600; color: var(--k-text-muted); display: block;"><?php echo esc_html($row->artist_names); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $show_movement ) : ?>
							<div style="text-align: right; flex-shrink: 0;">
								<?php if ( $row->movement_direction === 'up' ) : ?>
									<span style="color: var(--k-success, #2ecc71); font-weight: 800; font-size: 12px;">▲ <?php echo $row->movement_value; ?></span>
								<?php elseif ( $row->movement_direction === 'down' ) : ?>
									<span style="color: var(--k-error, #e74c3c); font-weight: 800; font-size: 12px;">▼ <?php echo $row->movement_value; ?></span>
								<?php elseif ( $row->movement_direction === 'new' ) : ?>
									<span class="kc-badge kc-badge-accent" style="font-size: 9px; padding: 3px 8px; background: #f1c40f; color: #000; border-radius: 4px; font-weight: 800;">NEW</span>
								<?php endif; ?>
							</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
<?php
	}
}
