<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Chart Leader Hero
 */
class ChartLeader extends Widget_Base {

	public function get_name() { return 'chart_leader'; }
	public function get_title() { return __( 'Chart Leader Hero', 'charts' ); }
	public function get_icon() { return 'eicon-info-box'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Hero Config', 'charts' ) ] );
		
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $options,
			'default' => array_key_first($options)
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		$def = $manager->get_definition( $settings['chart_id'] );
		
		if ( ! $def ) return;

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
		", $def->chart_type, $def->country_code ) );
		
		if ( ! $row ) return;
?>
		<div class="kc-root">
			<div class="kc-card kc-card-wide" style="border: none; padding: 0; background: linear-gradient(to right, #ffffff, #f1f5f9); overflow: hidden; box-shadow: var(--k-shadow-premium);">
				<div style="display: grid; grid-template-columns: 400px 1fr; gap: 48px; align-items: center;">
					<div class="hero-art" style="position: relative;">
						<span class="kc-row-rank" style="position: absolute; top: 12px; left: 12px; font-size: 8rem; line-height: 1; color: white; text-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10;">1</span>
						<img src="<?php echo esc_url($row->cover_image); ?>" alt="<?php echo esc_attr($row->track_name); ?>" style="width: 100%; height: 400px; object-fit: cover;">
					</div>
					<div class="hero-info" style="padding: 48px 48px 48px 0;">
						<span class="kc-brand-name" style="margin-bottom: 12px; display: block; letter-spacing: 0.2em;">WEEKLY LEADER • <?php echo esc_html($def->title); ?></span>
						<h1 style="font-size: clamp(2rem, 5vw, 4rem); font-weight: 900; letter-spacing: -0.05em; line-height: 0.95; margin-bottom: 16px;">
							<?php echo esc_html($row->track_name); ?>
						</h1>
						<p style="font-size: 1.5rem; font-weight: 700; color: var(--k-accent); margin-bottom: 32px;">
							<?php echo esc_html($row->artist_names); ?>
						</p>
						<div class="kc-stats-bar" style="margin-top: 0; gap: 48px;">
							<div class="kc-stat-item">
								<span class="kc-stat-lbl">WEEKS ON CHART</span>
								<span class="kc-stat-val" style="font-size: 2rem;"><?php echo $row->weeks_on_chart ?: 1; ?></span>
							</div>
							<div class="kc-stat-item">
								<span class="kc-stat-lbl">PEAK</span>
								<span class="kc-stat-val" style="font-size: 2rem;">#<?php echo $row->peak_rank ?: 1; ?></span>
							</div>
							<div class="kc-stat-item">
								<span class="kc-stat-lbl">TREND</span>
								<span class="kc-stat-val" style="font-size: 1rem; padding-top: 10px;"><?php echo strtoupper($row->movement_direction); ?></span>
							</div>
						</div>
						<div style="margin-top: 48px;">
							<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-btn large">
								<?php _e( 'Explore Market Intelligence', 'charts' ); ?> &rarr;
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php
	}
}
