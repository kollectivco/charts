<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;

class ChartTable extends Widget_Base {

	public function get_name() { return 'charts_table'; }
	public function get_title() { return __( 'Intelligence Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// --- 1. CONTENT TAB ---
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Intelligence Config', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );
		
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
		$this->add_control( 'style_variant', [
			'label' => __( 'Layout Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [ 'featured' => 'Show #1 Featured', 'compact' => 'Compact Clean List' ],
			'default' => 'featured',
		] );
		$this->end_controls_section();

		$this->start_controls_section( 'section_visibility', [ 'label' => __( 'Visibility', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );
		$this->add_control( 'show_cover', [ 'label' => 'Show Image', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_artist', [ 'label' => 'Show Artist', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_movement', [ 'label' => 'Show Movement', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'badge_new_text', [ 'label' => 'New Entry Text', 'type' => Controls_Manager::TEXT, 'default' => 'جديد', 'condition' => [ 'show_movement' => 'yes' ] ] );
		$this->end_controls_section();

		// --- 2. STYLE TAB ---
		// Container Box
		$this->start_controls_section( 'style_container', [ 'label' => __( 'Container Box', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_group_control( Group_Control_Background::get_type(), [ 'name' => 'container_bg', 'selector' => '{{WRAPPER}} .kc-widget-card' ] );
		$this->add_group_control( Group_Control_Border::get_type(), [ 'name' => 'container_border', 'selector' => '{{WRAPPER}} .kc-widget-card' ] );
		$this->add_control( 'container_radius', [
			'label' => __( 'Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-widget-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), [ 'name' => 'container_shadow', 'selector' => '{{WRAPPER}} .kc-widget-card' ] );
		$this->add_responsive_control( 'container_padding', [
			'label' => __( 'Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-widget-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->end_controls_section();

		// Rows & Dividers
		$this->start_controls_section( 'style_rows', [ 'label' => __( 'Rows & Layout', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'row_bg_hover', [
			'label' => __( 'Row Hover Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-row-item:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'row_divider_color', [
			'label' => __( 'Divider Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-row-item' => 'border-bottom-color: {{VALUE}};' ],
		]);
		$this->add_control( 'featured_bg', [
			'label' => __( 'Featured #1 Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-row-featured' => 'background-color: {{VALUE}};' ],
			'condition' => [ 'style_variant' => 'featured' ]
		]);
		$this->add_responsive_control( 'row_gap', [
			'label' => __( 'Internal Gap (Spacing)', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'selectors' => [ '{{WRAPPER}} .kc-row-item' => 'gap: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'row_padding', [
			'label' => __( 'Row Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-row-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->end_controls_section();

		// Rank Number
		$this->start_controls_section( 'style_rank', [ 'label' => __( 'Rank Number', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'rank_color', [ 'label' => __( 'Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-rank' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'rank_typo', 'selector' => '{{WRAPPER}} .kc-row-rank' ] );
		$this->end_controls_section();

		// Typography (Title & Meta)
		$this->start_controls_section( 'style_typography', [ 'label' => __( 'Track Info', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'title_color', [ 'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-title' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'title_color_hover', [ 'label' => __( 'Title Hover Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-item:hover .kc-row-title' => 'color: {{VALUE}};' ] ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'label' => 'Title Typography', 'selector' => '{{WRAPPER}} .kc-row-title' ] );
		
		$this->add_control( 'meta_color', [ 'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-row-subtitle' => 'color: {{VALUE}};' ], 'separator' => 'before' ] );
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'meta_typo', 'label' => 'Artist Typography', 'selector' => '{{WRAPPER}} .kc-row-subtitle' ] );
		$this->end_controls_section();

		// Image
		$this->start_controls_section( 'style_image', [ 'label' => __( 'Image', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_cover' => 'yes' ] ] );
		$this->add_responsive_control( 'img_size', [
			'label' => __( 'Image Size', 'charts' ), 'type' => Controls_Manager::SLIDER,
			'selectors' => [ '{{WRAPPER}} .kc-row-art' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'img_radius', [
			'label' => __( 'Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-row-art' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->end_controls_section();

		// Movement Badges
		$this->start_controls_section( 'style_movement', [ 'label' => __( 'Movement Badges', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => [ 'show_movement' => 'yes' ] ] );
		$this->add_control( 'up_color', [ 'label' => __( 'Up (▲) Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-up' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'down_color', [ 'label' => __( 'Down (▼) Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-down' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'new_bg', [ 'label' => __( 'New Badge Background', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-new' => 'background-color: {{VALUE}};' ] ] );
		$this->add_control( 'new_color', [ 'label' => __( 'New Badge Text', 'charts' ), 'type' => Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .kc-move-new' => 'color: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		if ( empty($settings['chart_id']) ) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">Please select a chart.</div>';
			return;
		}
		
		$def = $manager->get_definition( $settings['chart_id'] );
		if ( ! $def ) return;

		global $wpdb;
		$limit = !empty($settings['limit']) ? intval($settings['limit']) : 20;
		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit );
		if ( empty($rows) ) return;

		$style_variant = $settings['style_variant'] ?? 'featured';
		$show_cover    = $settings['show_cover'] !== 'yes' && $settings['show_cover'] !== 'no' ? true : $settings['show_cover'] === 'yes';
		$show_artist   = $settings['show_artist'] !== 'yes' && $settings['show_artist'] !== 'no' ? true : $settings['show_artist'] === 'yes';
		$show_movement = $settings['show_movement'] !== 'yes' && $settings['show_movement'] !== 'no' ? true : $settings['show_movement'] === 'yes';
		$badge_new_text = $settings['badge_new_text'] ?? 'جديد';
		
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
				gap: 16px;
				border-bottom: 1px solid var(--k-divider, #f1f5f9);
				padding: 16px 24px;
				transition: background-color 0.2s;
			}
			:where(.<?php echo $uid; ?> .kc-row-item:last-child) {
				border-bottom: none;
			}
			:where(.<?php echo $uid; ?> .kc-row-featured) {
				padding: 32px 40px;
				background-color: var(--k-surface-alt, #f8fafc);
			}
			:where(.<?php echo $uid; ?>) .kc-row-rank {
				font-weight: 900;
				color: var(--k-text, #0f172a);
				width: 40px;
				font-size: 1.5rem;
				flex-shrink: 0;
				text-align: center;
			}
			:where(.<?php echo $uid; ?> .kc-row-featured) .kc-row-rank {
				width: 60px;
				font-size: 3rem;
			}
			:where(.<?php echo $uid; ?>) .kc-row-img-wrap {
				flex-shrink: 0;
			}
			:where(.<?php echo $uid; ?>) .kc-row-art {
				border-radius: var(--k-radius-sm, 8px);
				object-fit: cover;
				width: 48px;
				height: 48px;
			}
			:where(.<?php echo $uid; ?> .kc-row-featured) .kc-row-art {
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
				font-size: 16px;
				line-height: 1.2;
				transition: color 0.2s;
			}
			:where(.<?php echo $uid; ?> .kc-row-featured) .kc-row-title {
				font-size: 24px;
				white-space: normal;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
			:where(.<?php echo $uid; ?>) .kc-row-subtitle {
				margin: 4px 0 0;
				font-weight: 600;
				color: var(--k-text-muted, #64748b);
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				font-size: 13px;
			}
			:where(.<?php echo $uid; ?> .kc-row-featured) .kc-row-subtitle {
				font-size: 15px;
			}
			:where(.<?php echo $uid; ?>) .kc-row-movement {
				flex-shrink: 0;
				margin-inline-start: auto;
				font-size: 12px;
				display: flex;
				align-items: center;
			}
			:where(.<?php echo $uid; ?>) .kc-move-up { color: var(--k-success, #10b981); font-weight: 850; letter-spacing: 0.05em; }
			:where(.<?php echo $uid; ?>) .kc-move-down { color: var(--k-error, #ef4444); font-weight: 850; letter-spacing: 0.05em; }
			:where(.<?php echo $uid; ?>) .kc-move-new { background-color: #f59e0b; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: 900; font-size: 10px; letter-spacing: 0.1em; }
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
							<?php if ( $show_artist && !empty($resolved['subtitle']) ) : ?>
								<p class="kc-row-subtitle kc-meta"><?php echo esc_html($resolved['subtitle']); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( $show_movement ) : ?>
						<div class="kc-row-movement stat-opt">
							<?php if ($row->movement_direction === 'up'): ?>
								<span class="kc-move-up">▲ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'down'): ?>
								<span class="kc-move-down">▼ <?php echo $row->movement_value; ?></span>
							<?php elseif ($row->movement_direction === 'new'): ?>
								<span class="kc-move-new"><?php echo esc_html($badge_new_text); ?></span>
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
