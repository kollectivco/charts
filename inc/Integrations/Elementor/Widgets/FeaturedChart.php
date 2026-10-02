<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class FeaturedChart extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'featured_chart'; }
	public function get_title() { return __( 'Charts: Featured Chart List', 'charts' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Chart Config', 'charts' ) ] );
		
		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$options = ['0' => __( 'Current Chart (Dynamic)', 'charts' )];
		if ($defs) { foreach ($defs as $d) { $options[$d->id] = $d->title; } }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $options,
			'default' => '0',
		] );

		$this->add_control( 'preview_rows', [
			'label' => __( 'No. of Tracks', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5
		] );

		$this->end_controls_section();

				$this->start_controls_section( 'style_general', [ 'label' => __( 'General Styles', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'bg_color', [
			'label' => __( 'Background Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .kc-fc-wrap' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'accent_color', [
			'label' => __( 'Accent Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#ff0055',
			'selectors' => [ '{{WRAPPER}}' => '--fc-accent: {{VALUE}};' ],
		]);
		$this->add_responsive_control( 'wrap_padding', [
			'label' => __( 'Padding', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-fc-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_responsive_control( 'wrap_border_radius', [
			'label' => __( 'Border Radius', 'charts' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => [ 'px', '%', 'em' ],
			'selectors' => [ '{{WRAPPER}} .kc-fc-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
			'name' => 'wrap_box_shadow', 'selector' => '{{WRAPPER}} .kc-fc-wrap',
		]);
		$this->end_controls_section();

		$this->start_controls_section( 'style_typography', [ 'label' => __( 'Typography & Colors', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		
		$this->add_control( 'heading_title', [ 'label' => __( 'Title', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-title' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'title_typography', 'selector' => '{{WRAPPER}} .kc-fc-title',
		]);

		$this->add_control( 'heading_desc', [ 'label' => __( 'Description', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'desc_color', [
			'label' => __( 'Description Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-desc' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'desc_typography', 'selector' => '{{WRAPPER}} .kc-fc-desc',
		]);

		$this->add_control( 'heading_meta', [ 'label' => __( 'Meta', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'meta_color', [
			'label' => __( 'Meta Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-meta' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'meta_typography', 'selector' => '{{WRAPPER}} .kc-fc-meta',
		]);

		$this->add_control( 'heading_song', [ 'label' => __( 'Song Title', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'song_color', [
			'label' => __( 'Song Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-song' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'song_typography', 'selector' => '{{WRAPPER}} .kc-fc-song',
		]);

		$this->add_control( 'heading_artist', [ 'label' => __( 'Artist', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'artist_color', [
			'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-artist' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'artist_typography', 'selector' => '{{WRAPPER}} .kc-fc-artist',
		]);

		$this->add_control( 'heading_rank', [ 'label' => __( 'Rank', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->add_control( 'rank_color', [
			'label' => __( 'Rank Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-rank' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name' => 'rank_typography', 'selector' => '{{WRAPPER}} .kc-fc-rank',
		]);

		$this->add_control( 'heading_row', [ 'label' => __( 'Row Item', 'charts' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ] );
		$this->start_controls_tabs( 'row_tabs' );
		$this->start_controls_tab( 'row_normal', [ 'label' => __( 'Normal', 'charts' ) ] );
		$this->add_control( 'row_bg_color', [
			'label' => __( 'Row Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-row' => 'background-color: {{VALUE}};' ],
		]);
		$this->end_controls_tab();
		$this->start_controls_tab( 'row_hover', [ 'label' => __( 'Hover', 'charts' ) ] );
		$this->add_control( 'row_bg_color_hover', [
			'label' => __( 'Row Background Hover', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-fc-row:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( ! isset($settings['chart_id']) || $settings['chart_id'] === '' ) return;

		$manager = new \Charts\Admin\SourceManager();
		$def = ($settings["chart_id"] === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($settings["chart_id"]);
		if ( ! $def ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">Widget Data Missing</h3>';
				echo '<p style="color:#94a3b8; margin:0;">Please select a specific chart from the Content settings. (Dynamic mode only works on Single Chart templates).</p>';
				echo '</div>';
			}
			return;
		}

		$limit = intval($settings['preview_rows'] ?: 5);
		$rows = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
		if ( empty($entries) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">No Chart Data Found</h3>';
				echo '<p style="color:#94a3b8; margin:0;">The selected chart does not have any active entries to display.</p>';
				echo '</div>';
			}
			return;
		}

		$uid = 'kc-fc-' . $this->get_id();
?>
		<style>
		.<?php echo $uid; ?>-wrap { background: var(--bg-color, #fff); color: var(--text-color, #0f172a); border-radius: 24px; overflow: hidden; --fc-accent: <?php echo $settings['accent_color'] ?: '#ff0055'; ?>; direction: rtl; font-family: "Cairo", sans-serif; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); }
		.<?php echo $uid; ?>-header { padding: 32px; border-bottom: 1px solid rgba(0,0,0,0.05); }
		.<?php echo $uid; ?>-meta { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px; }
		.<?php echo $uid; ?>-title { font-size: 28px; font-weight: 900; margin: 0 0 4px 0; }
		.<?php echo $uid; ?>-desc { font-size: 14px; font-weight: 600; color: #94a3b8; margin: 0; }
		
		.<?php echo $uid; ?>-list { padding: 16px 0; }
		.<?php echo $uid; ?>-row { display: flex; align-items: center; padding: 16px 32px; transition: background 0.2s; border-bottom: 1px solid rgba(0,0,0,0.03); }
		.<?php echo $uid; ?>-row:last-child { border-bottom: none; }
		.<?php echo $uid; ?>-row:hover { background: rgba(0,0,0,0.02); }
		
		.<?php echo $uid; ?>-rank { font-size: 20px; font-weight: 900; width: 40px; text-align: center; color: var(--fc-accent); flex-shrink: 0; }
		.<?php echo $uid; ?>-info { flex: 1; padding: 0 16px; }
		.<?php echo $uid; ?>-song { font-size: 16px; font-weight: 800; margin: 0 0 4px 0; }
		.<?php echo $uid; ?>-artist { font-size: 13px; font-weight: 600; color: #64748b; margin: 0; }
		.<?php echo $uid; ?>-move { font-size: 13px; font-weight: 800; font-family: "Inter", sans-serif; }
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-fc-wrap kc-widget-wrap">
			<div class="<?php echo $uid; ?>-header kc-fc-header">
				<span class="<?php echo $uid; ?>-meta kc-fc-meta">القائمة المميزة • <?php echo esc_html(strtoupper($def->country_code)); ?></span>
				<h3 class="<?php echo $uid; ?>-title kc-fc-title"><?php echo esc_html($def->title); ?></h3>
				<p class="<?php echo $uid; ?>-desc kc-fc-desc">تحديث أسبوعي حصري</p>
			</div>
			<div class="<?php echo $uid; ?>-list kc-fc-list">
				<?php foreach ($rows as $idx => $row) : 
					$res = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
				?>
				<div class="<?php echo $uid; ?>-row kc-fc-row">
					<div class="<?php echo $uid; ?>-rank kc-fc-rank"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->rank_position); ?></div>
					<div class="<?php echo $uid; ?>-info kc-fc-info">
						<h4 class="<?php echo $uid; ?>-song kc-fc-song"><?php echo esc_html($res['title']); ?></h4>
						<p class="<?php echo $uid; ?>-artist kc-fc-artist"><?php echo esc_html($res['subtitle']); ?></p>
					</div>
					<div class="<?php echo $uid; ?>-move kc-fc-move">
						<?php if ($row->movement_direction === 'up'): ?>
							<span style="color: #10b981;">▲ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->movement_value); ?></span>
						<?php elseif ($row->movement_direction === 'down'): ?>
							<span style="color: #f43f5e;">▼ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->movement_value); ?></span>
						<?php else: ?>
							<span style="background: #f59e0b; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px;">جديد</span>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	}
}