<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

/**
 * Elementor Widget: Chart List (Editorial Rankings)
 * Displays a list of charts highlighting the #1 item of each.
 */
class ChartList extends Widget_Base {

	public function get_name() { return 'chart_list'; }
	public function get_title() { return __( 'Chart Intelligence List', 'charts' ); }
	public function get_icon() { return 'eicon-editor-list-ul'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// 1. Data Configuration
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Chart Selection', 'charts' ) ] );
		
		$this->add_control( 'limit', [
			'label' => __( 'Number of Charts', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->add_control( 'selection_mode', [
			'label' => __( 'Selection Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'latest' => 'Latest Updated',
				'manual' => 'Manual Selection'
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
			'condition' => [ 'selection_mode' => 'manual' ]
		] );

		$this->add_control( 'start_index', [
			'label' => __( 'Start Index', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 1,
		]);

		$this->end_controls_section();

		// 2. Visibility Toggles
		$this->start_controls_section( 'section_visibility', [ 'label' => __( 'Visibility', 'charts' ) ] );

		$this->add_control( 'show_badge', [
			'label' => __( 'Show Chart Badge', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_subtitle', [
			'label' => __( 'Show Artist/Subtitle', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_index', [
			'label' => __( 'Show Large Index Number', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->add_control( 'show_separator', [
			'label' => __( 'Show Separators', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		]);

		$this->end_controls_section();

		// 3. Style Options
		$this->start_controls_section( 'section_style', [ 'label' => __( 'Style Configuration', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );

		$this->add_responsive_control( 'item_spacing', [
			'label' => __( 'Vertical Spacing', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 40 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-item' => 'margin-bottom: {{SIZE}}{{UNIT}};' ]
		]);

		$this->add_control( 'accent_color', [
			'label' => __( 'Accent Color', 'charts' ),
			'type' => Controls_Manager::COLOR,
			'default' => 'var(--k-accent)',
			'selectors' => [ '{{WRAPPER}} .kc-list-badge' => 'background: {{VALUE}};' ]
		]);

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'title_typography',
			'label' => __( 'Title Typography', 'charts' ),
			'selector' => '{{WRAPPER}} .kc-list-title',
		] );

		$this->add_control( 'index_opacity', [
			'label' => __( 'Index Number Opacity', 'charts' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.1 ] ],
			'default' => [ 'size' => 0.05 ],
			'selectors' => [ '{{WRAPPER}} .kc-list-index' => 'opacity: {{SIZE}};' ]
		]);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		
		if ( $settings['selection_mode'] === 'manual' && !empty($settings['selected_charts']) ) {
			$definitions = array();
			foreach($settings['selected_charts'] as $cid) {
				$def = $manager->get_definition($cid);
				if($def) $definitions[] = $def;
			}
		} else {
			$limit = intval($settings['limit'] ?: 5);
			$definitions = \Charts\Core\PublicIntegration::get_eligible_definitions($limit);
		}

		if ( empty($definitions) ) return;

		$start_index = intval($settings['start_index'] ?: 1);
?>
		<div class="kc-root">
			<div class="kc-chart-list-widget" style="display: flex; flex-direction: column;">
				<?php foreach ( $definitions as $i => $def ) : 
					$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 1);
					$top = !empty($entries) ? $entries[0] : null;
					if (!$top) continue;
					
					$idx_str = str_pad($i + $start_index, 2, '0', STR_PAD_LEFT);
					$has_sep = $settings['show_separator'] === 'yes' && ($i < count($definitions) - 1);
				?>
					<div class="kc-list-item" style="position: relative; display: flex; align-items: center; justify-content: space-between; padding-bottom: <?php echo intval($settings['item_spacing']['size']); ?>px; <?php echo $has_sep ? 'border-bottom: 1px solid var(--k-divider);' : ''; ?> transition: opacity 0.3s; margin-bottom: <?php echo intval($settings['item_spacing']['size']); ?>px;">
						
						<div class="kc-list-main" style="position: relative; z-index: 2; flex-grow: 1; padding-right: 60px;">
							<?php if ( $settings['show_badge'] === 'yes' ) : ?>
								<span class="kc-list-badge" style="display: inline-block; padding: 4px 10px; background: var(--k-accent); color: #fff; font-size: 8px; font-weight: 900; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;"><?php echo esc_html($def->title); ?></span>
							<?php endif; ?>

							<h3 class="kc-list-title" style="margin: 0; font-size: 32px; font-weight: 950; color: var(--k-text); letter-spacing: -0.04em; line-height: 1;"><?php echo esc_html($top->track_name); ?></h3>
							
							<?php if ( $settings['show_subtitle'] === 'yes' ) : ?>
								<span class="kc-list-subtitle" style="display: block; margin-top: 8px; font-size: 14px; font-weight: 700; color: var(--k-accent-purple); opacity: 0.8;"><?php echo esc_html($top->artist_names); ?></span>
							<?php endif; ?>

							<a href="<?php echo home_url('/charts/' . $def->slug); ?>" style="position: absolute; inset: 0; z-index: 5;"></a>
						</div>

						<?php if ( $settings['show_index'] === 'yes' ) : ?>
							<div class="kc-list-index" style="position: absolute; right: 0; font-size: 100px; font-weight: 950; color: var(--k-text); opacity: 0.05; pointer-events: none; line-height: 1;"><?php echo $idx_str; ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	}
}
