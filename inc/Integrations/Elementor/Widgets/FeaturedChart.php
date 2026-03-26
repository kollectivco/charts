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
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		$def = $manager->get_definition( $settings['chart_id'] );
		
		if ( ! $def ) return;

		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT %d
		", $def->chart_type, $def->country_code, $settings['preview_rows'] ) );
?>
		<div class="kc-root">
			<div class="kc-card kc-card-medium" style="padding: 0; min-width: 100%;">
				<div class="kc-list-header" style="padding: 40px; background: linear-gradient(135deg, white, #f8fafc);">
					<div class="kc-list-title">
						<span class="kc-brand-name" style="margin-bottom: 8px; font-size: 10px; display: block;">FEATURED MARKET • <?php echo strtoupper($def->country_code); ?></span>
						<h3 style="font-size: 1.8rem; font-weight: 850; letter-spacing: -0.02em;"><?php echo esc_html($def->title); ?></h3>
						<p style="color: var(--k-text-dim); font-size: 14px; margin-top: 8px; font-weight: 500; font-family: Inter, sans-serif;">
							<?php echo esc_html($def->chart_summary); ?>
						</p>
					</div>
					<div style="text-align: right;">
						<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-btn" style="padding: 10px 24px; font-size: 13px;">VIEW FULL CHART &rarr;</a>
					</div>
				</div>
				<div class="kc-list-content" style="padding: 16px 0 32px;">
					<?php foreach ( $rows as $idx => $row ) : ?>
						<div class="kc-preview-row" style="padding: 16px 40px; border-bottom: 1px solid rgba(0,0,0,0.03);">
							<span class="kc-preview-rank" style="font-size: 1.1rem; <?php echo ($idx === 0) ? 'color: var(--k-accent);' : ''; ?>"><?php echo $row->rank_position; ?></span>
							<div class="kc-preview-info">
								<span class="kc-preview-name" style="font-size: 15px; <?php echo ($idx === 0) ? 'font-weight: 850;' : ''; ?>"><?php echo esc_html($row->track_name); ?></span>
								<span class="kc-preview-artist" style="font-size: 12px; font-weight: 600;"><?php echo esc_html($row->artist_names); ?></span>
							</div>
							<div style="text-align: right;">
								<?php if ( $row->movement_direction === 'up' ) : ?>
									<span style="color: var(--k-success); font-weight: 800; font-size: 12px;">▲ <?php echo $row->movement_value; ?></span>
								<?php elseif ( $row->movement_direction === 'down' ) : ?>
									<span style="color: var(--k-error); font-weight: 800; font-size: 12px;">▼ <?php echo $row->movement_value; ?></span>
								<?php elseif ( $row->movement_direction === 'new' ) : ?>
									<span class="kc-badge kc-badge-accent" style="font-size: 9px; padding: 3px 8px;">NEW</span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
<?php
	}
}
