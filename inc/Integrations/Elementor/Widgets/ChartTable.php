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
			'default' => !empty($options) ? array_key_first($options) : ''
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

		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit );
		
		if ( empty($rows) ) return;

		$style_variant = $settings['style_variant'] ?? 'featured';
		$show_cover    = $settings['show_cover'] !== 'no';
		$show_artist   = $settings['show_artist'] !== 'no';
		$show_movement = $settings['show_movement'] !== 'no';
		
		$uid = 'kc-tbl-' . $this->get_id();

?>
		<div class="kc-root <?php echo $uid; ?>">
			<style>
			:where(.<?php echo $uid; ?>) .kc-widget-card {
				background-color: var(--k-surface, #ffffff);
				border: 1px solid var(--k-border, #e2e8f0);
				border-radius: var(--k-radius-lg, 16px);
				overflow: hidden;
				box-shadow: var(--k-shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
			}
			:where(.<?php echo $uid; ?>) .kc-row-item {
				display: flex;
				align-items: center;
				border-bottom: 1px solid var(--k-divider, #f1f5f9);
				padding: 16px 24px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-item:last-child {
				border-bottom: none;
			}
			:where(.<?php echo $uid; ?>) .kc-row-featured {
				padding: 32px 40px;
				background-color: var(--k-surface-alt, #f8fafc);
			}
			:where(.<?php echo $uid; ?>) .kc-row-rank {
				font-weight: 900;
				color: var(--k-text, #0f172a);
				width: 40px;
				font-size: 1.25rem;
			}
			:where(.<?php echo $uid; ?>) .kc-row-featured .kc-row-rank {
				width: 60px;
				font-size: 2.5rem;
			}
			:where(.<?php echo $uid; ?>) .kc-row-img-wrap {
				margin-right: 24px;
				flex-shrink: 0;
			}
			:where(.<?php echo $uid; ?>) .kc-row-art {
				border-radius: var(--k-radius-sm, 8px);
				object-fit: cover;
				width: 48px;
				height: 48px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-featured .kc-row-art {
				width: 80px;
				height: 80px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-info {
				flex-grow: 1;
				min-width: 0;
			}
			:where(.<?php echo $uid; ?>) .kc-row-title {
				margin: 0;
				font-weight: 800;
				color: var(--k-text, #0f172a);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				font-size: 14px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-featured .kc-row-title {
				font-size: 1.5rem;
			}
			:where(.<?php echo $uid; ?>) .kc-row-subtitle {
				margin: 4px 0 0;
				font-weight: 600;
				color: var(--k-text-muted, #64748b);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				font-size: 12px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-featured .kc-row-subtitle {
				font-size: 1.1rem;
			}
			:where(.<?php echo $uid; ?>) .kc-row-movement {
				flex-shrink: 0;
				margin-left: 24px;
				text-align: right;
				font-size: 12px;
			}
			</style>

			<div class="kc-chart-table kc-variant-<?php echo esc_attr($style_variant); ?> kc-widget-card">
				<?php foreach ( $rows as $idx => $row ) : 
					$is_featured = ($style_variant === 'featured' && $idx === 0);
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
				?>
					<div class="kc-row-item kc-rank-row <?php echo $is_featured ? 'kc-row-featured' : ''; ?>">
						
						<div class="kc-row-rank">
							<?php echo $row->rank_position; ?>
						</div>
						
						<?php if ( $show_cover ) : ?>
						<div class="kc-row-img-wrap">
							<img src="<?php echo esc_url($row->cover_image); ?>" class="kc-row-art" alt="<?php echo esc_attr($resolved['title']); ?>">
						</div>
						<?php endif; ?>

						<div class="kc-row-info">
							<h4 class="kc-row-title kc-title"><?php echo esc_html($resolved['title']); ?></h4>
							<?php if ( $show_artist ) : ?>
								<p class="kc-row-subtitle kc-meta"><?php echo esc_html($resolved['subtitle']); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( $show_movement ) : ?>
						<div class="kc-row-movement stat-opt">
							<?php if ($row->movement_direction === 'up'): ?>
								<span class="kc-move-up" style="color:var(--k-success, #10b981); font-weight:850; letter-spacing:0.05em;">▲ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'down'): ?>
								<span class="kc-move-down" style="color:var(--k-error, #ef4444); font-weight:850; letter-spacing:0.05em;">▼ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'new'): ?>
								<span class="kc-move-new" style="background:#f59e0b; color:#fff; padding:4px 8px; border-radius:4px; font-weight:900; font-size:10px; letter-spacing:0.1em;">NEW</span>
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
