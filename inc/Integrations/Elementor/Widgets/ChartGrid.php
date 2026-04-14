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
		// Use generalized helper for query controls
		\Charts\Integrations\Elementor\ControlHelper::add_query_controls( $this, false );

		// Use generalized helper for Layout controls, adding real variants!
		\Charts\Integrations\Elementor\ControlHelper::add_layout_controls( $this, [
			'grid' => 'Standard Grid',
			'bento' => 'Bento Box Layout',
			'minimal' => 'Minimal / Compact',
			'editorial' => 'Editorial List'
		]);

		// Visibility Toggles
		\Charts\Integrations\Elementor\ControlHelper::add_visibility_controls( $this, [
			'show_cover', 'show_artist', 'show_meta', 'show_cta'
		]);

		// Styling options
		\Charts\Integrations\Elementor\ControlHelper::add_style_controls( $this );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$manager = new \Charts\Admin\SourceManager();
		$definitions = $manager->get_definitions( true );

		$style_variant = $settings['style_variant'] ?? 'grid';
		$cols = $settings['grid_columns'] ?? '3';

		if ( $settings['chart_type'] !== 'all' ) {
			$definitions = array_filter( $definitions, function($d) use ($settings) {
				return $d->chart_type === $settings['chart_type'];
			});
		}
		
		if ( !empty($settings['market_filter']) && $settings['market_filter'] !== 'all' ) {
			$definitions = array_filter( $definitions, function($d) use ($settings) {
				return $d->country_code === $settings['market_filter'];
			});
		}

		$definitions = array_slice( $definitions, 0, $settings['limit'] );
		
		if ( empty( $definitions ) ) {
			echo '<div class="kc-empty">No charts found matching criteria.</div>';
			return;
		}

		// Calculate explicit grid CSS classes based on the generalized columns setting
		$grid_class = "kc-grid kc-widget-grid kc-variant-{$style_variant}";
		$cols_desktop = $settings['grid_columns'] ?: 3;
		$cols_tablet = $settings['grid_columns_tablet'] ?: 2;
		$cols_mobile = $settings['grid_columns_mobile'] ?: 1;

		echo '<div class="kc-root">';
		echo '<style>';
		echo "{{WRAPPER}} .kc-widget-grid { grid-template-columns: repeat({$cols_desktop}, 1fr); }";
		echo "@media (max-width: 1024px) { {{WRAPPER}} .kc-widget-grid { grid-template-columns: repeat({$cols_tablet}, 1fr); } }";
		echo "@media (max-width: 768px) { {{WRAPPER}} .kc-widget-grid { grid-template-columns: repeat({$cols_mobile}, 1fr); } }";
		echo '</style>';
		echo '<div class="' . esc_attr($grid_class) . '">';
		foreach ( $definitions as $def ) {
			$this->render_card( $def, $settings );
		}
		echo '</div></div>';
	}

	private function render_card( $def, $settings ) {
		global $wpdb;
		// Fetch preview rows (top 3)
		$rows = \Charts\Core\PublicIntegration::get_preview_entries( $def, 3 );

		$style = $settings['style_variant'];
		$show_cover = $settings['show_cover'] === 'yes';
		$show_meta = $settings['show_meta'] === 'yes';
		$show_artist = $settings['show_artist'] === 'yes';
		$show_cta = $settings['show_cta'] === 'yes';
?>
		<article class="kc-chart-card kc-widget-card style-<?php echo esc_attr($style); ?>" style="display:flex; flex-direction:column; background:var(--k-surface); border:1px solid var(--k-border); border-radius:var(--k-radius-lg); overflow:hidden; transition:transform 0.2s, box-shadow 0.2s; height: 100%;">
			<?php if ( $show_cover ) : ?>
			<div class="kc-card-header" style="height:140px; background:var(--k-chart-bg); padding:24px; display:flex; flex-direction:column; justify-content:flex-end; position: relative;">
				<?php if ( !empty($def->cover_image_url) ) : ?>
					<img src="<?php echo esc_url($def->cover_image_url); ?>" style="position: absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.6;">
				<?php endif; ?>
				<div class="kc-card-title kc-title" style="font-size:20px; font-weight:900; letter-spacing:-0.02em; margin:0; position:relative; z-index:2; color:#fff;"><?php echo esc_html($def->title); ?></div>
				<?php if ( $show_meta ) : ?>
					<div class="kc-card-label kc-meta" style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:#fff; margin-bottom:4px; position:relative; z-index:2; opacity:0.8;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></div>
				<?php endif; ?>
			</div>
			<?php else : ?>
			<div style="padding:24px 24px 0;">
				<?php if ( $show_meta ) : ?>
					<div class="kc-card-label kc-meta" style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:var(--k-text-muted); margin-bottom:4px;"><?php echo strtoupper($def->country_code); ?> • <?php echo strtoupper($def->chart_type); ?></div>
				<?php endif; ?>
				<div class="kc-card-title kc-title" style="font-size:20px; font-weight:900; letter-spacing:-0.02em; margin:0; color:var(--k-text);"><?php echo esc_html($def->title); ?></div>
			</div>
			<?php endif; ?>

			<div class="kc-card-list" style="padding:16px 0; flex-grow:1;">
				<?php foreach ( $rows as $row ) : 
					$resolved = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);
				?>
					<div class="kc-card-entry" style="display:flex; align-items:center; gap:12px; padding:12px 24px; border-bottom:1px solid var(--k-divider);">
						<div class="kc-entry-rank" style="font-size:12px; font-weight:800; width:14px;"><?php echo $row->rank_position; ?></div>
						<div class="kc-entry-info" style="min-width:0; flex-grow:1;">
							<div class="kc-entry-name" style="font-size:13px; font-weight:700; color:var(--k-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($resolved['title']); ?></div>
							<?php if ( $show_artist ) : ?>
								<div class="kc-entry-artist" style="font-size:11px; font-weight:500; color:var(--k-text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($resolved['subtitle']); ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_cta ) : ?>
			<div class="kc-card-footer" style="padding:20px 24px; border-top:1px solid var(--k-divider); display:flex; justify-content:center; align-items:center; margin-top:auto;">
				<a href="<?php echo home_url('/charts/' . $def->slug . '/'); ?>" class="kc-card-cta" style="font-size:10px; font-weight:800; color:var(--k-accent-purple); text-decoration:none; display:flex; align-items:center; gap:4px;">
					<?php echo strtoupper(esc_html($settings['card_cta_text'] ?? 'VIEW CHART')); ?> &rarr;
				</a>
			</div>
			<?php endif; ?>
		</article>
<?php
	}
}
