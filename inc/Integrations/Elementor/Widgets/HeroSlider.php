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
				'default' => __( 'Use System Default', 'charts' ),
				'style-1' => __( 'Style 1: Floating Cards', 'charts' ),
				'style-2' => __( 'Style 2: Gallery Strip', 'charts' ),
				'style-3' => __( 'Style 3: Layered Stack', 'charts' ),
			],
			'default' => 'default',
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
				'title' => $def->title,
				'subtitle' => $def->chart_summary,
				'leader_name' => $row->track_name ?? '',
				'leader_artist' => $row->artist_names ?? '',
				'image' => $row->cover_image ?? $def->cover_image_url ?? CHARTS_URL . 'public/assets/img/placeholder.png',
				'url' => home_url('/charts/' . $def->slug . '/'),
				'accent' => $def->accent_color ?: '#fe025b'
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
		<div class="kc-hero-slider-wrap kc-slider-<?php echo esc_attr($style); ?>" data-style="<?php echo esc_attr($style); ?>">
			<div class="kc-hero-slider">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<div class="kc-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" style="--slide-accent: <?php echo $slide['accent']; ?>;">
						
						<?php if ( $style === 'style-1' ) : /* Floating Card */ ?>
							<div class="kc-card-floating">
								<div class="kc-card-art">
									<img src="<?php echo esc_url($slide['image']); ?>" alt="">
									<div class="kc-floating-object">
										<img src="<?php echo esc_url($slide['image']); ?>" alt="">
									</div>
								</div>
								<div class="kc-card-body">
									<span class="kc-eyebrow"><?php echo esc_html($slide['title']); ?></span>
									<h2><?php echo esc_html($slide['leader_name']); ?></h2>
									<p><?php echo esc_html($slide['leader_artist']); ?></p>
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-btn small">View Chart &rarr;</a>
								</div>
							</div>

						<?php elseif ( $style === 'style-2' ) : /* Gallery Strip */ ?>
							<div class="kc-gallery-item">
								<img src="<?php echo esc_url($slide['image']); ?>" class="kc-gallery-bg">
								<div class="kc-gallery-overlay"></div>
								<div class="kc-gallery-content">
									<h3 class="kc-gallery-title"><?php echo esc_html($slide['title']); ?></h3>
									<span class="kc-gallery-leader"><?php echo esc_html($slide['leader_name']); ?></span>
									<span class="kc-gallery-artist"><?php echo esc_html($slide['leader_artist']); ?></span>
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-gallery-link"></a>
								</div>
							</div>

						<?php elseif ( $style === 'style-3' ) : /* Layered Stack */ ?>
							<div class="kc-stack-card">
								<div class="kc-stack-visual">
									<img src="<?php echo esc_url($slide['image']); ?>" alt="">
								</div>
								<div class="kc-stack-info">
									<span class="kc-eyebrow"><?php echo esc_html($slide['title']); ?></span>
									<h2><?php echo esc_html($slide['leader_name']); ?></h2>
									<p class="kc-stack-desc"><?php echo esc_html($slide['subtitle']); ?></p>
									<div class="kc-stack-meta">
										<span class="kc-stack-leader">#1 <?php echo esc_html($slide['leader_artist']); ?></span>
									</div>
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-btn">ENTER DATA &rarr;</a>
								</div>
							</div>
						<?php endif; ?>

					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count($slides) > 1 ) : ?>
				<div class="kc-slider-nav">
					<button class="kc-prev"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
					<div class="kc-slider-dots">
						<?php foreach($slides as $i => $s): ?><span class="kc-dot <?php echo $i===0?'active':''; ?>"></span><?php endforeach; ?>
					</div>
					<button class="kc-next"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
