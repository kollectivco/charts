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
			'label' => __( 'Carousel Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'coverflow' => __( 'Coverflow', 'charts' ),
				'stacked' => __( 'Stacked Cards', 'charts' ),
				'minimal' => __( 'Minimal Motion', 'charts' ),
			],
			'default' => 'coverflow',
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

		$this->end_controls_section();

		$this->start_controls_section( 'section_carousel_settings', [ 'label' => __( 'Motion Settings', 'charts' ) ] );

		$this->add_control( 'slides_count', [
			'label' => __( 'Max Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->add_control( 'visible_slides', [
			'label' => __( 'Visible Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 3,
		] );

		$this->add_control( 'animation_speed', [
			'label' => __( 'Animation Speed (ms)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 600,
		] );

		$this->add_control( 'easing', [
			'label' => __( 'Easing', 'charts' ),
			'type' => Controls_Manager::TEXT,
			'default' => 'cubic-bezier(0.25, 1, 0.5, 1)',
		] );

		$this->add_control( 'rotation_angle', [
			'label' => __( 'Rotation Angle (deg)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 45,
		] );

		$this->add_control( 'depth', [
			'label' => __( 'Depth (translateZ)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 150,
		] );

		$this->add_control( 'spacing', [
			'label' => __( 'Card Spacing (px)', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 50,
		] );

		$this->add_control( 'autoplay', [
			'label' => __( 'Autoplay', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'loop', [
			'label' => __( 'Loop', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'center_mode', [
			'label' => __( 'Center Mode', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
		] );

		$this->add_control( 'side_opacity', [
			'label' => __( 'Side Card Opacity', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.6,
		] );

		$this->add_control( 'side_scale', [
			'label' => __( 'Side Card Scale', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.8,
		] );

		$this->add_control( 'shadow_intensity', [
			'label' => __( 'Shadow Intensity', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1, 'step' => 0.1,
			'default' => 0.3,
		] );

		$this->add_control( 'active_glow', [
			'label' => __( 'Active Card Glow', 'charts' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
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
		$this->render_slider_html( $slides, $settings );
	}

	private function render_slider_html( $slides, $settings ) {
		$style = $settings['slider_style'];
		$opts = json_encode([
			'speed' => $settings['animation_speed'],
			'easing' => $settings['easing'],
			'rotation' => $settings['rotation_angle'],
			'depth' => $settings['depth'],
			'spacing' => $settings['spacing'],
			'visible' => $settings['visible_slides'],
			'autoplay' => $settings['autoplay'] === 'yes',
			'loop' => $settings['loop'] === 'yes',
			'center' => $settings['center_mode'] === 'yes',
			'opacity' => $settings['side_opacity'],
			'scale' => $settings['side_scale'],
			'shadow' => $settings['shadow_intensity'],
			'glow' => $settings['active_glow'] === 'yes'
		]);
		?>
		<div class="kc-motion-carousel-wrap kc-style-<?php echo esc_attr($style); ?>" data-carousel-style="<?php echo esc_attr($style); ?>" data-carousel-options='<?php echo esc_attr($opts); ?>'>
			<div class="kc-motion-carousel">
				<?php 
				if ( $style === 'coverflow' ) {
					// COVERFLOW STRUCTURE: Glassmorphism text over an image
					foreach ( $slides as $index => $slide ) : ?>
						<div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
							<div class="kc-cf-card">
								<img class="kc-cf-bg" src="<?php echo esc_url($slide['image']); ?>" alt="">
								<div class="kc-cf-overlay"></div>
								<div class="kc-cf-content">
									<span class="kc-badge"><?php echo esc_html($slide['platform']); ?></span>
									<h2 class="kc-cf-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
									<div class="kc-cf-artist">by <?php echo esc_html($slide['leader_artist']); ?></div>
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-cf-btn">VIEW</a>
								</div>
							</div>
						</div>
					<?php endforeach;
				} elseif ( $style === 'stacked' ) {
					// STACKED STRUCTURE: Image on left, text on right in a unified card
					foreach ( $slides as $index => $slide ) : ?>
						<div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
							<div class="kc-st-card">
								<div class="kc-st-visual">
									<img src="<?php echo esc_url($slide['image']); ?>" alt="">
								</div>
								<div class="kc-st-info">
									<h3 class="kc-st-title"><?php echo esc_html($slide['title']); ?></h3>
									<h2 class="kc-st-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
									<div class="kc-st-artist">by <?php echo esc_html($slide['leader_artist']); ?></div>
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-st-link">Explore Chart</a>
								</div>
							</div>
						</div>
					<?php endforeach;
				} else {
					// MINIMAL STRUCTURE: Clean centered art frame with minimal text
					foreach ( $slides as $index => $slide ) : ?>
						<div class="kc-motion-slide" style="--slide-accent: <?php echo esc_attr($slide['accent']); ?>;">
							<div class="kc-min-wrapper">
								<div class="kc-min-card">
									<img src="<?php echo esc_url($slide['image']); ?>" alt="">
									<a href="<?php echo esc_url($slide['url']); ?>" class="kc-min-overlay"></a>
								</div>
								<div class="kc-min-text">
									<h2 class="kc-min-leader"><?php echo esc_html($slide['leader_name']); ?></h2>
									<div class="kc-min-artist"><?php echo esc_html($slide['leader_artist']); ?></div>
								</div>
							</div>
						</div>
					<?php endforeach;
				}
				?>
			</div>

			<div class="kc-motion-controls">
				<button class="kc-motion-prev"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
				<button class="kc-motion-next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
			</div>
			
			<div class="kc-motion-pagination">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<span class="kc-motion-dot <?php echo $index === 0 ? 'is-active' : ''; ?>"></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
