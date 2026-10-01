<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class PremiumHeroSlider extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'premium_hero_slider'; }
	public function get_title() { return __( 'Charts: Premium Hero Slider', 'charts' ); }
	public function get_icon() { return 'eicon-slideshow'; }
	public function get_categories() { return [ 'charts' ]; }
	public function get_script_depends() { return [ 'swiper' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Slides Source', 'charts' ) ] );

		$this->add_control( 'source_mode', [
			'label' => __( 'Source Mode', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'auto' => __( 'Auto (Latest Charts)', 'charts' ),
			],
			'default' => 'auto',
		] );

		$this->add_control( 'slide_count', [
			'label' => __( 'Number of Slides', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_slider', [ 'label' => __( 'Slider Settings', 'charts' ) ] );
		$this->add_control( 'autoplay', [ 'label' => 'Autoplay', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'autoplay_speed', [ 'label' => 'Autoplay Speed (ms)', 'type' => Controls_Manager::NUMBER, 'default' => 4000 ] );
		$this->add_control( 'show_arrows', [ 'label' => 'Show Arrows', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_dots', [ 'label' => 'Show Dots', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'style_general', [ 'label' => __( 'Colors & Style', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_responsive_control( 'slider_height', [
			'label' => __( 'Slider Height', 'charts' ), 'type' => Controls_Manager::SLIDER, 'size_units' => [ 'px', 'vh' ],
			'range' => [ 'px' => [ 'min' => 200, 'max' => 1000 ] ],
			'selectors' => [ '{{WRAPPER}} .kc-phs-wrap' => 'height: {{SIZE}}{{UNIT}};' ],
		]);
		$this->add_control( 'accent_color', [
			'label' => __( 'Accent Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#ff0055',
			'selectors' => [ '{{WRAPPER}}' => '--phs-accent: {{VALUE}};' ],
		]);
		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-phs-title' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .kc-phs-title' ] );
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$count = intval($settings['slide_count'] ?: 5);
		$slides_data = \Charts\Core\HomepageSlider::get_slides_data($count);

		// Force maximum quality images by removing thumbnail sizes and requesting highest CDN resolution
		$maximize_image = function($url) {
			if (empty($url)) return $url;
			// Spotify: replace small/medium hashes with large (b273)
			if (strpos($url, 'i.scdn.co') !== false) {
				$url = str_replace(['1e02', '4851'], 'b273', $url);
			}
			// Apple Music: force 1000x1000
			elseif (strpos($url, 'mzstatic.com') !== false) {
				$url = preg_replace('/[0-9]+x[0-9]+([a-zA-Z]*)\.(jpg|png|webp)/i', '1000x1000$1.$2', $url);
			}
			// WordPress Media Library: remove thumbnail dimensions
			elseif (strpos($url, 'wp-content/uploads') !== false) {
				$url = preg_replace('/-[0-9]{2,4}x[0-9]{2,4}\.(jpg|jpeg|png|webp)$/i', '.$1', $url);
			}
			return $url;
		};
		
		foreach ($slides_data as &$sd) {
			$sd['image_url'] = $maximize_image($sd['image_url'] ?? '');
		}
		
		if ( empty($slides_data) ) return;
		$uid = 'kc-phs-' . $this->get_id();
		$uid_safe = str_replace('-', '_', $uid);
?>
		<style>
		.<?php echo $uid; ?>-wrap { position: relative; width: 100%; height: 500px; border-radius: 24px; overflow: hidden; --phs-accent: <?php echo $settings['accent_color'] ?: '#ff0055'; ?>; direction: rtl; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?>-wrap .swiper { width: 100%; height: 100%; }
		.<?php echo $uid; ?>-slide { position: relative; width: 100%; height: 100%; display: flex; align-items: flex-end; padding: 60px; }
		.<?php echo $uid; ?>-bg { position: absolute; inset: 0; background-size: cover; background-position: center; z-index: 1; transform: scale(1.05); transition: transform 6s linear; }
		.<?php echo $uid; ?>-wrap .swiper-slide-active .<?php echo $uid; ?>-bg { transform: scale(1); }
		.<?php echo $uid; ?>-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 40%, rgba(0,0,0,0.1) 100%); z-index: 2; }
		.<?php echo $uid; ?>-content { position: relative; z-index: 3; color: #fff; max-width: 800px; }
		.<?php echo $uid; ?>-badge { display: inline-block; background: var(--phs-accent); color: #fff; font-weight: 900; padding: 6px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; letter-spacing: 1px; }
		.<?php echo $uid; ?>-title { font-size: 64px; font-weight: 900; line-height: 1.1; margin: 0 0 16px 0; color: #fff; text-shadow: 0 4px 20px rgba(0,0,0,0.5); }
		.<?php echo $uid; ?>-desc { font-size: 18px; font-weight: 600; color: #e2e8f0; margin: 0 0 32px 0; max-width: 600px; }
		.<?php echo $uid; ?>-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #000; padding: 14px 32px; border-radius: 40px; font-weight: 800; text-decoration: none; font-size: 16px; transition: all 0.3s; }
		.<?php echo $uid; ?>-btn:hover { background: var(--phs-accent); color: #fff; transform: translateY(-2px); }
		
		/* Swiper Navigation */
		.<?php echo $uid; ?>-next, .<?php echo $uid; ?>-prev { position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; width: 50px; height: 50px; background: rgba(255,255,255,0.1); backdrop-filter: blur(8px); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; cursor: pointer; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.2); }
		.<?php echo $uid; ?>-next:hover, .<?php echo $uid; ?>-prev:hover { background: #fff; color: #000; }
		.<?php echo $uid; ?>-next { left: 24px; }
		.<?php echo $uid; ?>-prev { right: 24px; }
		.<?php echo $uid; ?>-pagination { position: absolute; bottom: 24px; left: 0; right: 0; z-index: 10; display: flex; justify-content: center; gap: 8px; }
		.<?php echo $uid; ?>-pagination .swiper-pagination-bullet { width: 10px; height: 10px; background: rgba(255,255,255,0.5); opacity: 1; border-radius: 5px; transition: all 0.3s; }
		.<?php echo $uid; ?>-pagination .swiper-pagination-bullet-active { width: 30px; background: var(--phs-accent); }
		
		@media (max-width: 768px) {
			.<?php echo $uid; ?>-wrap { height: 400px; border-radius: 16px; }
			.<?php echo $uid; ?>-slide { padding: 30px 20px; }
			.<?php echo $uid; ?>-title { font-size: 32px; }
			.<?php echo $uid; ?>-desc { font-size: 14px; margin-bottom: 24px; }
			.<?php echo $uid; ?>-next, .<?php echo $uid; ?>-prev { display: none; }
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-widget-wrap">
			<div class="swiper" id="<?php echo $uid; ?>-swiper">
				<div class="swiper-wrapper">
					<?php foreach ($slides_data as $s) : ?>
					<div class="swiper-slide">
						<div class="<?php echo $uid; ?>-slide">
							<div class="<?php echo $uid; ?>-bg" style="background-image: url('<?php echo esc_url($s['image_url'] ?? CHARTS_URL . 'public/assets/img/placeholder.png'); ?>');"></div>
							<div class="<?php echo $uid; ?>-overlay"></div>
							<div class="<?php echo $uid; ?>-content">
								<div class="<?php echo $uid; ?>-badge"><?php echo __('#1 TRENDING', 'charts'); ?></div>
								<h2 class="<?php echo $uid; ?>-title"><?php echo esc_html($s['title']); ?></h2>
								<p class="<?php echo $uid; ?>-desc"><?php echo esc_html(!empty($s['desc']) ? $s['desc'] : 'أقوى الإصدارات المتصدرة للسباق هذا الأسبوع.'); ?></p>
								<a href="<?php echo esc_url($s['btn1_link'] ?? '#'); ?>" class="<?php echo $uid; ?>-btn">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
									<?php echo esc_html($s['btn1_text'] ?? __('استكشف الشارت', 'charts')); ?>
								</a>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ($settings['show_arrows'] === 'yes') : ?>
					<div class="<?php echo $uid; ?>-next"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></div>
					<div class="<?php echo $uid; ?>-prev"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
				<?php endif; ?>
				<?php if ($settings['show_dots'] === 'yes') : ?>
					<div class="<?php echo $uid; ?>-pagination swiper-pagination"></div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		function initPHS_<?php echo $uid_safe; ?>() {
			if (typeof Swiper !== "undefined") {
				new Swiper("#<?php echo $uid; ?>-swiper", {
					loop: true,
					effect: 'fade',
					fadeEffect: { crossFade: true },
					<?php if ($settings['autoplay'] === 'yes') : ?>
					autoplay: { delay: <?php echo intval($settings['autoplay_speed']); ?>, disableOnInteraction: false },
					<?php endif; ?>
					navigation: { nextEl: '.<?php echo $uid; ?>-next', prevEl: '.<?php echo $uid; ?>-prev' },
					pagination: { el: '.<?php echo $uid; ?>-pagination', clickable: true }
				});
			}
		}
		setTimeout(initPHS_<?php echo $uid_safe; ?>, 100);
		</script>
<?php
	}
}