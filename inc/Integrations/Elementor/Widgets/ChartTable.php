<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Chart Table
 */
class ChartTable extends Widget_Base {

	public function get_name() { return 'charts_table'; }
	public function get_title() { return __( 'Intelligence Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Intelligence Config', 'charts' ) ] );
		
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $options,
			'default' => array_key_first($options)
		] );

		$this->add_control( 'limit', [
			'label' => __( 'No. of Items', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 20
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
		", $def->chart_type, $def->country_code, $settings['limit'] ) );
?>
		<div class="kc-root">
			<div class="kc-chart-table">
				<?php foreach ( $rows as $idx => $row ) : 
					$is_featured = ($idx === 0);
				?>
					<div class="kc-row-item <?php echo $is_featured ? 'kc-row-featured' : ''; ?>">
						<div class="kc-row-summary" style="<?php echo $is_featured ? 'padding: 32px;' : 'padding: 16px 24px;'; ?>">
							<div class="kc-row-rank" style="<?php echo $is_featured ? 'font-size: 2.5rem;' : 'font-size: 1.25rem;'; ?>">
								<?php echo $row->rank_position; ?>
							</div>
							
							<div class="kc-row-img-wrap">
								<img src="<?php echo esc_url($row->cover_image); ?>" class="kc-row-art" alt="<?php echo esc_attr($row->track_name); ?>" style="<?php echo $is_featured ? 'width: 80px; height: 80px;' : 'width: 48px; height: 48px;'; ?>">
							</div>

							<div class="kc-row-info">
								<h4 class="kc-row-title" style="<?php echo $is_featured ? 'font-size: 1.5rem;' : 'font-size: 14px;'; ?>"><?php echo esc_html($row->track_name); ?></h4>
								<p class="kc-row-subtitle" style="<?php echo $is_featured ? 'font-size: 1.1rem;' : 'font-size: 12px;'; ?>"><?php echo esc_html($row->artist_names); ?></p>
							</div>

							<div class="kc-row-movement stat-opt">
								<?php if ($row->movement_direction === 'up'): ?>
									<span style="color: var(--k-success); font-weight: 850;">▲ <?php echo $row->movement_value; ?></span>
								<?php elseif ($row->movement_direction === 'down'): ?>
									<span style="color: var(--k-error); font-weight: 850;">▼ <?php echo $row->movement_value; ?></span>
								<?php elseif ($row->movement_direction === 'new'): ?>
									<span class="kc-badge kc-badge-accent">NEW</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	}
}
