<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Chart Grid
 */
class ChartGrid extends Widget_Base {

	public function get_name() { return 'charts_grid'; }
	public function get_title() { return __( 'Intelligence Grid', 'charts' ); }
	public function get_icon() { return 'eicon-apps'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Query Configuration', 'charts' ) ] );

		$this->add_control( 'chart_type', [
			'label' => __( 'Chart Type', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'all' => 'All',
				'top-songs' => 'Top Songs',
				'top-artists' => 'Top Artists',
				'top-videos' => 'Top Videos',
				'viral' => 'Viral / Trending'
			],
			'default' => 'all'
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Limit', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 6
		] );

		$this->add_control( 'columns', [
			'label' => __( 'Columns', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'12' => 'Full Width (1)',
				'6'  => 'Half (2)',
				'4'  => 'Third (3)',
				'3'  => 'Quarter (4)'
			],
			'default' => '4'
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		$definitions = $manager->get_definitions( true );

		if ( $settings['chart_type'] !== 'all' ) {
			$definitions = array_filter( $definitions, function($d) use ($settings) {
				return $d->chart_type === $settings['chart_type'];
			});
		}

		$definitions = array_slice( $definitions, 0, $settings['limit'] );
		
		if ( empty( $definitions ) ) {
			echo 'No charts found matching criteria.';
			return;
		}

		echo '<div class="kc-root"><div class="kc-bento-grid">';
		foreach ( $definitions as $def ) {
			$this->render_card( $def, $settings['columns'] );
		}
		echo '</div></div>';
	}

	private function render_card( $def, $cols ) {
		global $wpdb;
		// Fetch preview rows (top 3)
		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 3
		", $def->chart_type, $def->country_code ) );
?>
		<div class="kc-card" style="grid-column: span <?php echo $cols; ?>; padding: 0;">
			<div class="kc-list-header" style="padding: 24px;">
				<div class="kc-list-title">
					<span class="kc-brand-name" style="font-size: 9px;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></span>
					<h3 style="font-size: 1.1rem; margin-top: 4px;"><?php echo esc_html($def->title); ?></h3>
				</div>
			</div>
			<div class="kc-list-content" style="padding: 0 0 16px;">
				<?php foreach ( $rows as $row ) : ?>
					<div class="kc-preview-row" style="padding: 10px 24px;">
						<span class="kc-preview-rank" style="font-size: 12px;"><?php echo $row->rank_position; ?></span>
						<div class="kc-preview-info">
							<span class="kc-preview-name" style="font-size: 13px;"><?php echo esc_html($row->track_name); ?></span>
							<span class="kc-preview-artist" style="font-size: 11px;"><?php echo esc_html($row->artist_names); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div style="padding: 16px 24px; border-top: 1px solid var(--k-border); margin-top: auto;">
				<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" style="font-size: 12px; font-weight: 800; text-decoration: none; color: var(--k-accent);">
					VIEW FULL CHART &rarr;
				</a>
			</div>
		</div>
<?php
	}
}
