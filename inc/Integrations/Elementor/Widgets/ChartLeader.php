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

		\Charts\Integrations\Elementor\ControlHelper::add_layout_controls( $this, [
			'standard' => 'Standard Hero',
			'minimal' => 'Minimal Card'
		]);

		\Charts\Integrations\Elementor\ControlHelper::add_visibility_controls( $this, [
			'show_cover', 'show_artist', 'show_meta', 'show_cta'
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
		$row = $wpdb->get_row( $wpdb->prepare( "
			SELECT e.* FROM {$wpdb->prefix}charts_entries e
			JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
			WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
			ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
		", $def->chart_type, $def->country_code ) );
		
		if ( ! $row ) return;

		$style_variant = $settings['style_variant'] ?? 'standard';
		$show_cover    = $settings['show_cover'] !== 'no';
		$show_artist   = $settings['show_artist'] !== 'no';
		$show_cta      = $settings['show_cta'] === 'yes';
		$show_meta     = $settings['show_meta'] !== 'no';
?>
		<div class="kc-root">
			<div class="kc-widget-card kc-hero-card kc-variant-<?php echo esc_attr($style_variant); ?>" style="border:1px solid var(--k-border); padding:0; background:var(--k-surface-alt); overflow:hidden; border-radius:var(--k-radius-lg); box-shadow:var(--k-shadow-md);">
				
				<?php if ( $style_variant === 'standard' ) : ?>
					<div style="display:flex; flex-wrap:wrap; align-items:center;">
						<?php if ( $show_cover ) : ?>
						<div class="hero-art" style="position:relative; flex:1; min-width:300px;">
							<span class="kc-row-rank" style="position:absolute; top:24px; left:24px; font-size:4rem; font-weight:900; line-height:1; color:#fff; text-shadow:0 4px 12px rgba(0,0,0,0.5); z-index:10;">1</span>
							<img src="<?php echo esc_url($row->cover_image); ?>" alt="<?php echo esc_attr($row->track_name); ?>" style="width:100%; height:100%; min-height:400px; object-fit:cover;">
						</div>
						<?php endif; ?>
						<div class="hero-info" style="flex:1.5; padding:48px; min-width:300px;">
							<?php if ( $show_meta ) : ?>
								<span class="kc-meta" style="margin-bottom:12px; display:block; letter-spacing:0.1em; font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase;">WEEKLY LEADER • <?php echo esc_html($def->title); ?></span>
							<?php endif; ?>
							
							<?php 
								$resolved = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
							?>
							<h1 class="kc-title" style="font-size:clamp(2rem, 5vw, 4rem); font-weight:900; letter-spacing:-0.05em; line-height:0.95; margin-bottom:16px; color:var(--k-text);">
								<?php echo esc_html($resolved['title']); ?>
							</h1>
							
							<?php if ( $show_artist ) : ?>
								<p style="font-size:1.5rem; font-weight:700; color:var(--k-accent); margin-bottom:32px;">
									<?php echo esc_html($resolved['subtitle']); ?>
								</p>
							<?php endif; ?>
							
							<?php if ( $show_meta ) : ?>
							<div class="kc-stats-bar" style="display:flex; gap:32px; margin-bottom:32px; flex-wrap:wrap;">
								<div class="kc-stat-item">
									<span style="display:block; font-size:9px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; margin-bottom:4px;">WKS ON CHART</span>
									<span style="font-size:24px; font-weight:900; color:var(--k-text);"><?php echo $row->weeks_on_chart ?: 1; ?></span>
								</div>
								<div class="kc-stat-item">
									<span style="display:block; font-size:9px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; margin-bottom:4px;">PEAK</span>
									<span style="font-size:24px; font-weight:900; color:var(--k-text);">#<?php echo $row->peak_rank ?: 1; ?></span>
								</div>
								<div class="kc-stat-item">
									<span style="display:block; font-size:9px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase; margin-bottom:4px;">TREND</span>
									<span style="font-size:16px; font-weight:900; color:var(--k-text);"><?php echo strtoupper($row->movement_direction); ?></span>
								</div>
							</div>
							<?php endif; ?>

							<?php if ( $show_cta ) : ?>
								<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" style="display:inline-flex; align-items:center; gap:8px; padding:16px 32px; background:var(--k-text); color:var(--k-surface); font-size:12px; font-weight:800; text-decoration:none; border-radius:40px; letter-spacing:0.05em; transition:transform 0.2s;">
									<?php echo esc_html($settings['card_cta_text'] ?? 'Explore Market Intelligence'); ?> &rarr;
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php else : // Minimal Variant ?>
					<div style="padding:48px; text-align:center; position:relative; z-index:2;">
						<?php if ( $show_meta ) : ?>
							<span class="kc-meta" style="margin-bottom:16px; display:block; letter-spacing:0.1em; font-size:10px; font-weight:800; color:var(--k-text-muted); text-transform:uppercase;">WEEKLY LEADER • <?php echo esc_html($def->title); ?></span>
						<?php endif; ?>
						<?php 
							$resolved = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
						?>
						<h2 class="kc-title" style="font-size:3rem; font-weight:950; letter-spacing:-0.03em; margin:0 0 16px; color:var(--k-text);">
							<?php echo esc_html($resolved['title']); ?>
						</h2>
						<?php if ( $show_artist ) : ?>
							<p style="font-size:1.25rem; font-weight:700; color:var(--k-accent); margin:0;">
								<?php echo esc_html($resolved['subtitle']); ?>
							</p>
						<?php endif; ?>
						<?php if ( $show_cta ) : ?>
							<div style="margin-top:32px;">
								<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" style="font-size:12px; font-weight:800; color:var(--k-text); text-decoration:underline;">
									<?php echo esc_html($settings['card_cta_text'] ?? 'View Chart'); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
<?php
	}
}
