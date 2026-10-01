<?php

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Elementor Widget: Featured Chart (Single List)
 */
class FeaturedChart extends Widget_Base {

	public function get_name() { return 'featured_chart'; }
	public function get_title() { return __( 'Charts: Featured Chart List', 'charts' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Chart Config', 'charts' ) ] );
		
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = ["0" => __("Current Chart (Dynamic)", "charts")];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_id', [
			'label' => __( 'Select Chart', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => $options,
			'default' => !empty($options) ? array_key_first($options) : ''
		] );

		$this->add_control( 'preview_rows', [
			'label' => __( 'No. of Tracks', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5
		] );

		$this->end_controls_section();

		\Charts\Integrations\Elementor\ControlHelper::add_layout_controls( $this, [
			'featured' => 'Show #1 Featured',
			'compact' => 'Compact Clean List'
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
		$def = (empty($settings["chart_id"]) || $settings["chart_id"] === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($settings["chart_id"]);
		if ( ! $def ) return;

		global $wpdb;
		$limit = !empty($settings['preview_rows']) ? intval($settings['preview_rows']) : 5;

		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit );

		if ( empty($rows) ) return;

		$style_variant = $settings['style_variant'] ?? 'featured';
		$show_artist   = $settings['show_artist'] !== 'no';
		$show_movement = $settings['show_movement'] !== 'no';
		$show_cta      = $settings['show_cta'] === 'yes';

		$uid = 'kc-fc-' . $this->get_id();
?>
		<div class="kc-root <?php echo $uid; ?>">
			<style>
			:where(.<?php echo $uid; ?>) .kc-widget-card {
				padding: 0;
				min-width: 100%;
				background-color: var(--k-surface, #ffffff);
				border-radius: var(--k-radius-lg, 16px);
				border: 1px solid var(--k-border, #e2e8f0);
				overflow: hidden;
				box-shadow: var(--k-shadow-md, 0 4px 6px rgba(0,0,0,0.1));
			}
			:where(.<?php echo $uid; ?>) .kc-list-header {
				padding: <?php echo $style_variant === 'compact' ? '24px' : '40px'; ?>;
				background-color: var(--k-surface-alt, #f8fafc);
				border-bottom: 1px solid var(--k-divider, #f1f5f9);
				display: flex;
				justify-content: space-between;
				align-items: flex-end;
			}
			:where(.<?php echo $uid; ?>) .kc-title {
				font-size: 1.8rem;
				font-weight: 850;
				letter-spacing: -0.02em;
				color: var(--k-text, #0f172a);
				margin: 0;
			}
			:where(.<?php echo $uid; ?>) .kc-meta {
				margin-bottom: 8px;
				font-size: 10px;
				display: block;
				letter-spacing: 0.1em;
				color: var(--k-text-muted, #64748b);
			}
			</style>
			
			<div class="kc-widget-card kc-card kc-variant-<?php echo esc_attr($style_variant); ?>">
				<div class="kc-list-header">
					<div>
						<span class="kc-brand-name kc-meta">FEATURED MARKET • <?php echo strtoupper($def->country_code); ?></span>
						<h3 class="kc-title"><?php echo esc_html($def->title); ?></h3>
						<p style="color: var(--k-text-dim); font-size: 14px; margin-top: 8px; font-weight: 500; font-family: Inter, sans-serif;">
							Powered by Kontentainment Intelligence
						</p>
					</div>
					<?php if ( $show_cta ) : ?>
					<div style="text-align: right; flex-shrink: 0; margin-left: 24px;">
						<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-btn" style="padding: 10px 24px; font-size: 13px; text-decoration: none; font-weight: 800; color: var(--k-text); border: 1px solid var(--k-border); border-radius: 40px; background-color: var(--k-surface); transition: background 0.2s;">
							<?php echo esc_html($settings['card_cta_text'] ?? 'View Full Chart'); ?> &rarr;
						</a>
					</div>
					<?php endif; ?>
				</div>

				<div class="kc-list-content" style="padding: 16px 0 32px; background-color: var(--k-surface);">
					<?php foreach ( $rows as $idx => $row ) : 
						$resolved = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
					?>
						<div class="kc-preview-row" style="<?php echo $style_variant === 'compact' ? 'padding: 12px 24px;' : 'padding: 16px 40px;'; ?> display: flex; align-items: center; border-bottom: 1px solid var(--k-divider);">
							<span class="kc-preview-rank" style="font-size: 1.1rem; font-weight: 900; width: 30px; <?php echo ($idx === 0) ? 'color: var(--k-accent); font-size: 1.5rem;' : 'color: var(--k-text);'; ?>"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->rank_position); ?></span>
							<div class="kc-preview-info" style="flex-grow: 1; padding: 0 16px;">
								<span class="kc-preview-name kc-title" style="font-size: 15px; display: block; <?php echo ($idx === 0) ? 'font-weight: 850;' : 'font-weight: 700;'; ?>"><?php echo esc_html($resolved['title']); ?></span>
								<?php if ( $show_artist ) : ?>
									<span class="kc-preview-artist kc-meta" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 0;"><?php echo esc_html($resolved['subtitle']); ?></span>
								<?php endif; ?>
							</div>
							
							<?php if ( $show_movement ) : ?>
							<div style="text-align: right; flex-shrink: 0;">
								<?php if ($row->movement_direction === 'up'): ?>
									<span style="color: var(--k-success, #2ecc71); font-weight: 800; font-size: 12px;">▲ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->movement_value); ?></span>
								<?php elseif ($row->movement_direction === 'down'): ?>
									<span style="color: var(--k-error, #e74c3c); font-weight: 800; font-size: 12px;">▼ <?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->movement_value); ?></span>
								<?php elseif ($row->movement_direction === 'new'): ?>
									<span class="kc-badge kc-badge-accent" style="font-size: 9px; padding: 3px 8px; background: #f1c40f; color: #000; border-radius: 4px; font-weight: 800;">جديد</span>
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
