<?php
/**
 * Elementor Widget: Hero Slider Configuration
 * Implements 3 distinct visual styles: Floating, Gallery, Layered.
 */

namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class HeroSlider extends Widget_Base {

	public function get_name() { return 'hero_slider'; }
	public function get_title() { return __( 'Hero Slider System', 'charts' ); }
	public function get_icon() { return 'eicon-post-slider'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Slider Configuration', 'charts' ) ] );

		// Style Selector
		$this->add_control( 'slider_style', [
			'label' => __( 'Visual Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'style-1' => __( 'Style 1: Editorial Hero', 'charts' ),
				'style-2' => __( 'Style 2: Bento Rail', 'charts' ),
				'style-3' => __( 'Style 3: Minimal Coverflow', 'charts' ),
			],
			'default' => 'style-1',
		] );

		// Data Source
		$definitions = (new \Charts\Admin\SourceManager())->get_definitions( true );
		$options = [];
		foreach ( $definitions as $def ) { $options[$def->id] = $def->title; }

		$this->add_control( 'chart_ids', [
			'label' => __( 'Featured Charts', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'label_block' => true,
			'multiple' => true,
			'options' => $options,
		] );

		$this->add_control( 'slides_count', [
			'label' => __( 'Max Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$style = $settings['slider_style'];
		if ( $style === 'default' ) {
			$style = get_option( 'charts_homepage_slider_style', 'style-1' );
		}

		$chart_ids = $settings['chart_ids'];
		if ( empty( $chart_ids ) ) {
			// Fallback to all definitions if none selected
			$manager = new \Charts\Admin\SourceManager();
			$defs = $manager->get_definitions( true );
			$chart_ids = array_map( function($d){ return $d->id; }, $defs );
		}

		// Fetch Data for each slide
		$slides = [];
		$manager = new \Charts\Admin\SourceManager();
		global $wpdb;

		foreach ( array_slice($chart_ids, 0, $settings['slides_count']) as $id ) {
			$def = $manager->get_definition( $id );
			if ( ! $def ) continue;

			// Get #1 leader for this chart as the "visual" for the slide
			$row = $wpdb->get_row( $wpdb->prepare( "
				SELECT e.* FROM {$wpdb->prefix}charts_entries e
				JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
				WHERE s.chart_type = %s AND s.country_code = %s AND s.is_active = 1
				ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1
			", $def->chart_type, $def->country_code ) );

			$slides[] = [
				'title'         => $def->title,
				'subtitle'      => $def->chart_summary,
				'leader_name'   => $row->track_name ?? 'Trending Now',
				'leader_artist' => $row->artist_names ?? 'Global Charts',
				'image'         => $row->cover_image ?? $def->cover_image_url ?? CHARTS_URL . 'public/assets/img/placeholder.png',
				'url'           => home_url('/charts/' . $def->slug . '/'),
				'accent'        => $def->accent_color ?: '#fe025b',
				'platform'      => $def->platform ?? 'Global',
				'region'        => $def->country_name ?? 'Global'
			];
		}

		if ( empty( $slides ) ) {
			echo '<div style="padding:40px; text-align:center; border:2px dashed #eee;">No chart data found for slider.</div>';
			return;
		}

		// Pass to the frontend renderer
		$this->render_slider_html( $slides, $style );
	}

	private function render_slider_html( $slides, $style ) {
		?>
		<div class="kc-hero-slider-wrap kc-slider-<?php echo esc_attr($style); ?>">
			<div class="kc-hero-slider">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<div class="kc-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" style="--slide-accent: <?php echo $slide['accent']; ?>;">
						
						<?php if ( $style === 'style-1' || true ) : ?>
							<div class="kc-slide-layout">
								<div class="kc-slide-content">
									<div class="kc-chart-badge">
										<span></span>
										<?php echo esc_html(strtoupper($slide['platform'])); ?> <?php echo esc_html(strtoupper($slide['region'])); ?>
									</div>
									<h1 class="kc-hero-leader"><?php echo esc_html($slide['leader_name']); ?></h1>
									<div class="kc-hero-meta">
										<span>by <b><?php echo esc_html($slide['leader_artist']); ?></b></span>
										<span class="kc-meta-divider">•</span>
										<span><?php echo esc_html($slide['title']); ?></span>
									</div>
									<div class="kc-hero-actions">
										<a href="<?php echo esc_url($slide['url']); ?>" class="kc-hero-cta">
											VIEW FULL CHART
											<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
										</a>
									</div>
								</div>
								<div class="kc-slide-visual">
									<img src="<?php echo esc_url($slide['image']); ?>" alt="">
								</div>
							</div>
						<?php endif; ?>

					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count($slides) > 1 ) : ?>
				<div class="kc-slider-nav">
					<div class="kc-nav-arrows">
						<button class="kc-nav-btn kc-prev"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
						<button class="kc-nav-btn kc-next"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
					</div>
					<div class="kc-slider-progress">
						<div class="kc-progress-bar"></div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
