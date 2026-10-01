<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartLeader extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'chart_leader'; }
	public function get_title() { return __( 'Charts: Leader Hero', 'charts' ); }
	public function get_icon() { return 'eicon-info-box'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => __( 'Hero Config', 'charts' ) ] );
		
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

		$this->add_control( 'style_variant', [
			'label' => __( 'Layout Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'standard' => 'Standard Split Hero',
				'minimal' => 'Minimal Centered Hero'
			],
			'default' => 'standard',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_visibility', [ 'label' => __( 'Visibility', 'charts' ) ] );
		$this->add_control( 'show_cover', [ 'label' => 'Show Image', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_artist', [ 'label' => 'Show Artist', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->add_control( 'show_meta', [ 'label' => 'Show Stats/Meta', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'style_general', [ 'label' => __( 'Colors', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'bg_color', [
			'label' => __( 'Background Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-cl-wrap' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-cl-title' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'accent_color', [
			'label' => __( 'Accent Color', 'charts' ), 'type' => Controls_Manager::COLOR, 'default' => '#ff0055',
			'selectors' => [ '{{WRAPPER}}' => '--cl-accent: {{VALUE}};' ],
		]);
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

		$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, 1);
		if ( empty($entries) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3 style="color:#fff; margin-bottom:8px;">No Chart Data Found</h3>';
				echo '<p style="color:#94a3b8; margin:0;">The selected chart does not have any active entries to display.</p>';
				echo '</div>';
			}
			return;
		}
		
		$row = $entries[0];
		$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$row]);
		if (empty($img) && !empty($row->resolved_image)) $img = $row->resolved_image;
		if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
		$res = \Charts\Core\PublicIntegration::resolve_display_name($row, $def);

		$uid = 'kc-cl-' . $this->get_id();
		$variant = $settings['style_variant'];
?>
		<style>
		.<?php echo $uid; ?>-wrap { background: #0f172a; border-radius: 24px; overflow: hidden; --cl-accent: <?php echo $settings['accent_color'] ?: '#ff0055'; ?>; color: #fff; direction: rtl; font-family: "Cairo", sans-serif; }
		.<?php echo $uid; ?>-standard { display: flex; flex-wrap: wrap; align-items: stretch; }
		.<?php echo $uid; ?>-img-wrap { position: relative; flex: 1; min-width: 300px; min-height: 400px; }
		.<?php echo $uid; ?>-img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
		.<?php echo $uid; ?>-rank { position: absolute; top: 24px; right: 24px; font-size: 80px; font-weight: 900; line-height: 1; color: #fff; text-shadow: 0 10px 30px rgba(0,0,0,0.5); font-family: "Inter", sans-serif; z-index: 10; }
		.<?php echo $uid; ?>-info { flex: 1.5; padding: 48px; min-width: 300px; display: flex; flex-direction: column; justify-content: center; }
		.<?php echo $uid; ?>-meta-tag { display: inline-block; background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 24px; align-self: flex-start; }
		.<?php echo $uid; ?>-title { font-size: 48px; font-weight: 900; line-height: 1.2; margin: 0 0 8px 0; color: #fff; }
		.<?php echo $uid; ?>-artist { font-size: 20px; font-weight: 700; color: var(--cl-accent); margin: 0 0 32px 0; }
		
		.<?php echo $uid; ?>-stats { display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 32px; background: rgba(0,0,0,0.2); padding: 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
		.<?php echo $uid; ?>-stat { display: flex; flex-direction: column; gap: 4px; }
		.<?php echo $uid; ?>-stat-lbl { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
		.<?php echo $uid; ?>-stat-val { font-size: 24px; font-weight: 900; color: #fff; }
		
		.<?php echo $uid; ?>-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: var(--cl-accent); color: #fff; font-size: 14px; font-weight: 800; text-decoration: none; border-radius: 40px; align-self: flex-start; transition: transform 0.2s; }
		.<?php echo $uid; ?>-btn:hover { transform: translateY(-2px); }

		.<?php echo $uid; ?>-minimal { padding: 60px; text-align: center; position: relative; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-meta-tag { align-self: center; margin: 0 auto 24px auto; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-title { font-size: 56px; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-btn { align-self: center; margin: 32px auto 0 auto; }
		</style>

		<div class="<?php echo $uid; ?>-wrap kc-cl-wrap kc-widget-wrap">
			<?php if ($variant === 'standard') : ?>
				<div class="<?php echo $uid; ?>-standard kc-cl-standard">
					<?php if ($settings['show_cover'] === 'yes') : ?>
					<div class="<?php echo $uid; ?>-img-wrap kc-cl-img-wrap">
						<span class="<?php echo $uid; ?>-rank kc-cl-rank"><?php echo \Charts\Core\Transliteration::to_arabic_numerals(1); ?></span>
						<img src="<?php echo esc_url($img); ?>" class="<?php echo $uid; ?>-img kc-cl-img" alt="">
					</div>
					<?php endif; ?>
					<div class="<?php echo $uid; ?>-info kc-cl-info">
						<span class="<?php echo $uid; ?>-meta-tag kc-cl-meta-tag">👑 متصدر الشارت • <?php echo esc_html($def->title); ?></span>
						<h1 class="<?php echo $uid; ?>-title kc-cl-title"><?php echo esc_html($res['title']); ?></h1>
						<?php if ($settings['show_artist'] === 'yes') : ?>
							<p class="<?php echo $uid; ?>-artist kc-cl-artist"><?php echo esc_html($res['subtitle']); ?></p>
						<?php endif; ?>
						
						<?php if ($settings['show_meta'] === 'yes') : ?>
						<div class="<?php echo $uid; ?>-stats kc-cl-stats">
							<div class="<?php echo $uid; ?>-stat kc-cl-stat">
								<span class="<?php echo $uid; ?>-stat-lbl kc-cl-stat-lbl">أسابيع في الشارت</span>
								<span class="<?php echo $uid; ?>-stat-val kc-cl-stat-val"><?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->weeks_on_chart ?: 1); ?></span>
							</div>
							<div class="<?php echo $uid; ?>-stat kc-cl-stat">
								<span class="<?php echo $uid; ?>-stat-lbl kc-cl-stat-lbl">أعلى مركز</span>
								<span class="<?php echo $uid; ?>-stat-val kc-cl-stat-val">#<?php echo \Charts\Core\Transliteration::to_arabic_numerals($row->peak_rank ?: 1); ?></span>
							</div>
							<div class="<?php echo $uid; ?>-stat kc-cl-stat">
								<span class="<?php echo $uid; ?>-stat-lbl kc-cl-stat-lbl">التريند</span>
								<span class="<?php echo $uid; ?>-stat-val kc-cl-stat-val" style="color: <?php echo ($row->movement_direction === 'up' ? '#10b981' : ($row->movement_direction === 'down' ? '#f43f5e' : '#f59e0b')); ?>; font-size:18px;">
									<?php echo ($row->movement_direction === 'up' ? '▲' : ($row->movement_direction === 'down' ? '▼' : 'جديد')); ?>
								</span>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
			<?php else : ?>
				<div class="<?php echo $uid; ?>-minimal kc-cl-minimal">
					<span class="<?php echo $uid; ?>-meta-tag kc-cl-meta-tag">👑 متصدر الشارت • <?php echo esc_html($def->title); ?></span>
					<h1 class="<?php echo $uid; ?>-title kc-cl-title"><?php echo esc_html($res['title']); ?></h1>
					<?php if ($settings['show_artist'] === 'yes') : ?>
						<p class="<?php echo $uid; ?>-artist kc-cl-artist"><?php echo esc_html($res['subtitle']); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
<?php
	}
}