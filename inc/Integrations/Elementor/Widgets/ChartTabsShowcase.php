<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class ChartTabsShowcase extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_chart_tabs_showcase'; }
	public function get_title() { return __( 'Charts: Tabs Showcase', 'charts' ); }
	public function get_icon() { return 'eicon-tabs'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// --- CONTENT ---
		$this->start_controls_section( 'content_section', [ 'label' => __( 'Query Settings', 'charts' ), 'tab' => Controls_Manager::TAB_CONTENT ] );

		$manager = new \Charts\Admin\SourceManager();
		$defs = $manager->get_definitions(true);
		$chart_options = [];
		if ($defs) { foreach ($defs as $d) { $chart_options[$d->id] = $d->title; } }

		$this->add_control( 'chart_ids', [
			'label' => __( 'Select Charts for Tabs', 'charts' ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => $chart_options,
			'default' => !empty($chart_options) ? array_keys(array_slice($chart_options, 0, 4, true)) : [],
		] );

		$this->add_control( 'limit', [
			'label' => __( 'Tracks per Chart', 'charts' ),
			'type' => Controls_Manager::NUMBER,
			'default' => 5,
			'min' => 1,
			'max' => 10,
		] );

		$this->add_control( 'style_variant', [
			'label' => __( 'Layout Style', 'charts' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'pills' => 'iOS Pills + Clean List',
				'minimal' => 'Underline Tabs + Minimal List',
				'glass' => 'Glassmorphism Cards',
				'blocks' => 'Dark Tech Blocks'
			],
			'default' => 'pills',
		] );
		$this->end_controls_section();

		// --- STYLING: TABS ---
		$this->start_controls_section( 'style_tabs', [ 'label' => __( 'Tabs Navigation', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'tab_bg', [
			'label' => __( 'Tabs Container Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-nav-wrap' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_active_bg', [
			'label' => __( 'Active Tab Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab.is-active' => 'background-color: {{VALUE}}; border-color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_text_color', [
			'label' => __( 'Tab Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab' => 'color: {{VALUE}};' ],
		]);
		$this->add_control( 'tab_active_text_color', [
			'label' => __( 'Active Tab Text Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-tab.is-active' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'tab_typo', 'selector' => '{{WRAPPER}} .kc-ts-tab' ] );
		$this->end_controls_section();

		// --- STYLING: LIST ---
		$this->start_controls_section( 'style_list', [ 'label' => __( 'List Items', 'charts' ), 'tab' => Controls_Manager::TAB_STYLE ] );
		$this->add_control( 'list_bg', [
			'label' => __( 'Row Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-row' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'list_hover_bg', [
			'label' => __( 'Row Hover Background', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-row:hover' => 'background-color: {{VALUE}};' ],
		]);
		$this->add_control( 'title_color', [
			'label' => __( 'Title Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-title' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .kc-ts-title' ] );
		$this->add_control( 'artist_color', [
			'label' => __( 'Artist Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-artist' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'artist_typo', 'selector' => '{{WRAPPER}} .kc-ts-artist' ] );
		$this->add_control( 'rank_color', [
			'label' => __( 'Rank Color', 'charts' ), 'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-ts-rank' => 'color: {{VALUE}};' ],
		]);
		$this->add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'rank_typo', 'selector' => '{{WRAPPER}} .kc-ts-rank' ] );
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_ids = $settings['chart_ids'];
		
		echo '<div class="kc-widget-wrap">';
		if (empty($chart_ids)) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #ff0055; direction:ltr;">';
				echo '<h3>Tabs Data Missing</h3><p>Please select at least one chart from the Content settings.</p></div>';
			}
			echo '</div>';
			return;
		}

		$limit = $settings['limit'];
		$uid = 'kc-cts-' . $this->get_id();
		$variant = $settings['style_variant'] ?? 'pills';
		
		$manager = new \Charts\Admin\SourceManager();
		$charts_data = [];
		foreach ($chart_ids as $cid) {
			$def = ($cid === "0") ? \Charts\Core\PublicIntegration::get_current_chart_definition() : $manager->get_definition($cid);
			if (!$def) continue;
			$entries = \Charts\Core\PublicIntegration::get_preview_entries($def, $limit);
			if (empty($entries)) continue;
			
			$tracks = [];
			foreach($entries as $e) {
				$img = \Charts\Core\PublicIntegration::resolve_chart_image($def, [$e]);
				if (empty($img) && !empty($e->resolved_image)) $img = $e->resolved_image;
				if (empty($img)) $img = CHARTS_URL . 'public/assets/img/placeholder.png';
				
				$resolved = \Charts\Core\PublicIntegration::resolve_display_name($e, $def);
				$tracks[] = [
					'rank' => \Charts\Core\Transliteration::to_arabic_numerals($e->rank_position),
					'title' => $resolved['title'],
					'artist' => $resolved['subtitle'],
					'image' => $img,
					'is_new' => ($e->movement_direction === 'new')
				];
			}
			$charts_data[] = [ 'id' => $cid, 'title' => $def->title, 'tracks' => $tracks ];
		}

		if (empty($charts_data)) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:40px; text-align:center; background:#0f172a; color:#fff; border-radius:20px; border:2px dashed #eab308; direction:ltr;">';
				echo '<h3>No Chart Data</h3><p>The selected charts do not have any active entries to display.</p></div>';
			}
			echo '</div>';
			return;
		}

?>
		<style>
		.<?php echo $uid; ?>-wrap { width: 100%; direction: rtl; font-family: "Cairo", sans-serif; }
		
		/* Base Layout */
		.<?php echo $uid; ?>-nav { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 40px; }
		.<?php echo $uid; ?>-tab { cursor: pointer; transition: all 0.3s; font-weight: 800; }
		.<?php echo $uid; ?>-content { display: none; opacity: 0; transition: opacity 0.4s ease; }
		.<?php echo $uid; ?>-content.is-active { display: block; opacity: 1; }
		.<?php echo $uid; ?>-row { display: flex; align-items: center; gap: 20px; transition: all 0.3s; }
		.<?php echo $uid; ?>-img { width: 64px; height: 64px; object-fit: cover; flex-shrink: 0; }
		.<?php echo $uid; ?>-rank { font-size: 24px; font-weight: 900; width: 40px; text-align: center; flex-shrink: 0; }
		.<?php echo $uid; ?>-info { flex: 1; min-width: 0; }
		.<?php echo $uid; ?>-title { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.<?php echo $uid; ?>-artist { font-size: 14px; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

		/* Style 1: Pills */
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-nav-wrap { background: #f1f5f9; padding: 6px; border-radius: 40px; display: inline-flex; margin: 0 auto; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-nav { margin-bottom: 40px; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-tab { padding: 12px 24px; border-radius: 40px; color: #64748b; font-size: 15px; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-tab.is-active { background: #fff; color: #0f172a; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-row { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #fff; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-row:hover { background: #f8fafc; transform: translateX(-4px); }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-img { border-radius: 12px; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-title { color: #0f172a; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-artist { color: #64748b; }
		.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-rank { color: #0f172a; }

		/* Style 2: Minimal */
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-nav { border-bottom: 2px solid #e2e8f0; gap: 32px; justify-content: flex-start; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-tab { padding: 0 0 16px 0; color: #94a3b8; font-size: 18px; border-bottom: 3px solid transparent; margin-bottom: -2px; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-tab.is-active { color: #0f172a; border-bottom-color: #0f172a; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-row { padding: 24px 0; border-bottom: 1px solid #f1f5f9; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-row:hover { opacity: 0.8; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-img { border-radius: 0; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-title { color: #000; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-artist { color: #64748b; }
		.<?php echo $uid; ?>-minimal .<?php echo $uid; ?>-rank { color: #94a3b8; font-family: monospace; font-size: 18px; }

		/* Style 3: Glass */
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-nav { gap: 16px; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-tab { padding: 12px 24px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; color: rgba(255,255,255,0.7); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-tab.is-active { background: rgba(255,255,255,0.2); color: #fff; border-color: rgba(255,255,255,0.3); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-row { padding: 16px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; margin-bottom: 12px; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-row:hover { background: rgba(255,255,255,0.1); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-img { border-radius: 50%; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-title { color: #fff; }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-artist { color: rgba(255,255,255,0.6); }
		.<?php echo $uid; ?>-glass .<?php echo $uid; ?>-rank { color: rgba(255,255,255,0.9); }

		/* Style 4: Blocks */
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-nav { gap: 8px; background: #0f172a; padding: 8px; border-radius: 12px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-tab { padding: 14px 28px; background: transparent; color: #64748b; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-tab.is-active { background: #334155; color: #fff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-row { padding: 20px; background: #0f172a; border-radius: 16px; margin-bottom: 8px; border-left: 4px solid #3b82f6; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-row:hover { transform: scale(1.01); background: #1e293b; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-img { border-radius: 8px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-title { color: #f8fafc; font-family: monospace; font-size: 20px; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-artist { color: #94a3b8; }
		.<?php echo $uid; ?>-blocks .<?php echo $uid; ?>-rank { color: #3b82f6; }
		
		@media (max-width: 768px) {
			.<?php echo $uid; ?>-nav { margin-bottom: 24px; }
			.<?php echo $uid; ?>-img { width: 48px; height: 48px; }
			.<?php echo $uid; ?>-rank { font-size: 20px; width: 30px; }
			.<?php echo $uid; ?>-pills .<?php echo $uid; ?>-tab { padding: 10px 16px; font-size: 13px; }
		}
		</style>

		<div class="<?php echo $uid; ?>-wrap <?php echo $uid; ?>-<?php echo esc_attr($variant); ?>">
			<div style="text-align: center;">
				<div class="<?php echo $uid; ?>-nav-wrap">
					<div class="<?php echo $uid; ?>-nav">
						<?php foreach ($charts_data as $i => $c) : ?>
							<div class="<?php echo $uid; ?>-tab <?php echo $i === 0 ? 'is-active' : ''; ?>" data-target="<?php echo $uid; ?>-c-<?php echo $i; ?>">
								<?php echo esc_html($c['title']); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="<?php echo $uid; ?>-panels">
				<?php foreach ($charts_data as $i => $c) : ?>
					<div id="<?php echo $uid; ?>-c-<?php echo $i; ?>" class="<?php echo $uid; ?>-content <?php echo $i === 0 ? 'is-active' : ''; ?>">
						<?php foreach ($c['tracks'] as $track) : ?>
							<div class="<?php echo $uid; ?>-row">
								<div class="<?php echo $uid; ?>-rank"><?php echo $track['rank']; ?></div>
								<img src="<?php echo esc_url($track['image']); ?>" class="<?php echo $uid; ?>-img" alt="">
								<div class="<?php echo $uid; ?>-info">
									<h4 class="<?php echo $uid; ?>-title"><?php echo esc_html($track['title']); ?></h4>
									<p class="<?php echo $uid; ?>-artist"><?php echo esc_html($track['artist']); ?></p>
								</div>
								<?php if ($track['is_new']): ?>
									<span style="background:#f59e0b; color:#fff; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:800;">جديد</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script>
		document.addEventListener("DOMContentLoaded", function() {
			var tabs = document.querySelectorAll('.<?php echo $uid; ?>-tab');
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function() {
					var parent = this.closest('.<?php echo $uid; ?>-wrap');
					parent.querySelectorAll('.<?php echo $uid; ?>-tab').forEach(function(t) { t.classList.remove('is-active'); });
					parent.querySelectorAll('.<?php echo $uid; ?>-content').forEach(function(c) { c.classList.remove('is-active'); });
					
					this.classList.add('is-active');
					var target = document.getElementById(this.getAttribute('data-target'));
					if(target) target.classList.add('is-active');
				});
			});
		});
		</script>
<?php
		echo '</div>'; // End kc-widget-wrap
	}
}