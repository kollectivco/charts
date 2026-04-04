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

		\Charts\Integrations\Elementor\ControlHelper::add_layout_controls( $this, [
			'featured' => 'Show #1 Featured',
			'compact' => 'Compact Clean List'
		]);

		\Charts\Integrations\Elementor\ControlHelper::add_visibility_controls( $this, [
			'show_cover', 'show_artist', 'show_movement'
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
		$limit = !empty($settings['limit']) ? intval($settings['limit']) : 20;

		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT %d
		", $def->chart_type, $def->country_code, $limit ) );
		
		if ( empty($rows) ) return;

		$style_variant = $settings['style_variant'] ?? 'featured';
		$show_cover    = $settings['show_cover'] !== 'no';
		$show_artist   = $settings['show_artist'] !== 'no';
		$show_movement = $settings['show_movement'] !== 'no';

?>
		<div class="kc-root">
			<div class="kc-chart-table kc-variant-<?php echo esc_attr($style_variant); ?> kc-widget-card" style="background:var(--k-surface); border:1px solid var(--k-border); border-radius:var(--k-radius-lg); overflow:hidden; box-shadow:var(--k-shadow-sm);">
				<?php foreach ( $rows as $idx => $row ) : 
					$is_featured = ($style_variant === 'featured' && $idx === 0);
				?>
					<div class="kc-row-item kc-rank-row <?php echo $is_featured ? 'kc-row-featured' : ''; ?>" style="display:flex; align-items:center; border-bottom:1px solid var(--k-divider); <?php echo $is_featured ? 'padding:32px 40px; background:var(--k-surface-alt);' : 'padding:16px 24px;'; ?>">
						
						<div class="kc-row-rank" style="font-weight:900; color:var(--k-text); width: <?php echo $is_featured ? '60px' : '40px'; ?>; <?php echo $is_featured ? 'font-size:2.5rem;' : 'font-size:1.25rem;'; ?>">
							<?php echo $row->rank_position; ?>
						</div>
						
						<?php if ( $show_cover ) : ?>
						<div class="kc-row-img-wrap" style="margin-right:24px; flex-shrink:0;">
							<img src="<?php echo esc_url($row->cover_image); ?>" class="kc-row-art" alt="<?php echo esc_attr($row->track_name); ?>" style="border-radius:var(--k-radius-sm); object-fit:cover; <?php echo $is_featured ? 'width:80px; height:80px;' : 'width:48px; height:48px;'; ?>">
						</div>
						<?php endif; ?>

						<div class="kc-row-info" style="flex-grow:1; min-width:0;">
							<h4 class="kc-row-title kc-title" style="margin:0; font-weight:800; color:var(--k-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; <?php echo $is_featured ? 'font-size:1.5rem;' : 'font-size:14px;'; ?>"><?php echo esc_html($row->track_name); ?></h4>
							<?php if ( $show_artist ) : ?>
								<p class="kc-row-subtitle kc-meta" style="margin:4px 0 0; font-weight:600; color:var(--k-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; <?php echo $is_featured ? 'font-size:1.1rem;' : 'font-size:12px;'; ?>"><?php echo esc_html($row->artist_names); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( $show_movement ) : ?>
						<div class="kc-row-movement stat-opt" style="flex-shrink:0; margin-left:24px; text-align:right; font-size:12px;">
							<?php if ($row->movement_direction === 'up'): ?>
								<span class="kc-move-up" style="color:var(--k-success, #2ecc71); font-weight:850; letter-spacing:0.05em;">▲ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'down'): ?>
								<span class="kc-move-down" style="color:var(--k-error, #e74c3c); font-weight:850; letter-spacing:0.05em;">▼ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'new'): ?>
								<span class="kc-move-new" style="background:#f1c40f; color:#000; padding:4px 8px; border-radius:4px; font-weight:900; font-size:10px; letter-spacing:0.1em;">NEW</span>
							<?php endif; ?>
						</div>
						<?php endif; ?>

					</div>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	}
}
