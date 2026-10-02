<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Charts\Integrations\Elementor\PremiumWidgetTrait;

if ( ! defined( 'ABSPATH' ) ) exit;

class SparklinesTable extends Widget_Base {
	use PremiumWidgetTrait;

	public function get_name() { return 'kc_sparklines_table'; }
	public function get_title() { return __( 'Charts: Trajectory Sparklines Table', 'charts' ); }
	public function get_icon() { return 'eicon-table'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		// ── Content Tab ─────────────────────────────────────────────────
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content Settings', 'charts' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$manager      = new \Charts\Admin\SourceManager();
		$defs         = $manager->get_definitions( true );
		$chart_options = [];
		if ( $defs ) {
			foreach ( $defs as $d ) {
				$chart_options[ $d->id ] = $d->title;
			}
		}

		$this->add_control( 'chart_id', [
			'label'   => __( 'Select Chart', 'charts' ),
			'type'    => Controls_Manager::SELECT,
			'options' => $chart_options,
			'default' => ! empty( $chart_options ) ? array_keys( $chart_options )[0] : '',
		] );

		$this->add_control( 'limit', [
			'label'   => __( 'Number of Tracks', 'charts' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 10,
		] );

		$this->end_controls_section();

		$this->add_premium_badge_controls();

		// ── Table Layout Tab ─────────────────────────────────────────────
		$this->start_controls_section( 'table_layout', [
			'label' => __( 'Table Layout', 'charts' ),
			'tab'   => Controls_Manager::TAB_LAYOUT ?? Controls_Manager::TAB_CONTENT,
		] );
		$this->add_responsive_control( 'cell_padding', [
			'label'      => __( 'Cell Padding', 'charts' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			// .kc-elm-card is on the wrapper div that contains the <table>
			'selectors'  => [ '{{WRAPPER}} .kc-elm-card th, {{WRAPPER}} .kc-elm-card td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->end_controls_section();

		// ── Granular Style Controls (card / image / title / meta / counter) ──
		// These generate sections targeting .kc-elm-card, .kc-elm-img,
		// .kc-elm-title, .kc-elm-artist, .kc-elm-meta, .kc-elm-counter —
		// all present in render() HTML. ✅
		$this->add_granular_style_controls( ['card', 'image', 'title', 'meta', 'counter'] );

		// ── Additional Table-Specific Style Controls ─────────────────────
		$this->start_controls_section( 'style_table_rows', [
			'label' => __( 'Table Rows', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'row_bg_color', [
			'label'     => __( 'Row Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card tbody tr' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'row_hover_bg_color', [
			'label'     => __( 'Row Hover Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card tbody tr:hover' => 'background-color: {{VALUE}} !important;' ],
		] );

		$this->add_control( 'rank_color', [
			'label'     => __( 'Rank Number Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// .kc-elm-counter is applied to the rank <td> in render()
			'selectors' => [ '{{WRAPPER}} .kc-elm-counter' => 'color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style_table_header', [
			'label' => __( 'Table Header', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'header_bg_color', [
			'label'     => __( 'Header Background Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// thead > tr > th in render()
			'selectors' => [ '{{WRAPPER}} .kc-elm-card thead th' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'header_text_color', [
			'label'     => __( 'Header Text Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-elm-card thead th' => 'color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'style_sparkline', [
			'label' => __( 'Sparkline', 'charts' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'sparkline_up_color', [
			'label'     => __( 'Rising Line Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			// .kc-spt-{id}-up class sets stroke; target via shared kc-spark-up class added in render
			'selectors' => [ '{{WRAPPER}} .kc-spark-up' => 'stroke: {{VALUE}};' ],
		] );

		$this->add_control( 'sparkline_down_color', [
			'label'     => __( 'Falling Line Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-spark-down' => 'stroke: {{VALUE}};' ],
		] );

		$this->add_control( 'sparkline_neutral_color', [
			'label'     => __( 'Neutral Line Color', 'charts' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .kc-spark-neutral' => 'stroke: {{VALUE}};' ],
		] );

		$this->end_controls_section();
		$this->add_advanced_image_controls('{{WRAPPER}} .kc-elm-img', '{{WRAPPER}} .kc-elm-img');
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$chart_id = $settings['chart_id'];

		echo '<div class="kc-widget-wrap">';

		if ( empty( $chart_id ) ) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>';
			echo '<p style="margin:0;">' . __( 'Please select a chart from the Content settings.', 'charts' ) . '</p>';
			echo '</div>';
			echo '</div>';
			return;
		}

		$limit = $settings['limit'];
		$uid   = 'kc-spt-' . $this->get_id();

		$def     = ( new \Charts\Admin\SourceManager() )->get_definition( $chart_id );
		$entries = $def ? \Charts\Core\PublicIntegration::get_preview_entries( $def, $limit ) : [];

		if ( empty( $entries ) ) {
			echo '<div style="padding:20px; text-align:center; border:1px dashed #cbd5e1; border-radius:12px; color:#64748b;">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" stroke="#94a3b8" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 4-6"/></svg>';
			echo '<p style="margin:0;">' . __( 'No chart entries found. Add entries to this chart to display the table.', 'charts' ) . '</p>';
			echo '</div>';
			echo '</div>';
			return;
		}

		global $wpdb;
		$entries_table = $wpdb->prefix . 'charts_entries';

		echo '<style>
		.' . $uid . '-wrap { background: #fff; overflow-x: auto; transition: all 0.3s; }
		.' . $uid . '-table { width: 100%; min-width: 600px; border-collapse: collapse; text-align: left; }
		.' . $uid . '-table th { background: #f8fafc; padding: 16px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; border-bottom: 1px solid #e2e8f0; }
		.' . $uid . '-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
		.' . $uid . '-table tr:last-child td { border-bottom: none; }
		.' . $uid . '-table tr:hover { background: #f8fafc; }
		.' . $uid . '-track-col { display: flex; align-items: center; gap: 16px; }
		.' . $uid . '-img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; }
		.' . $uid . '-title { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
		.' . $uid . '-artist { font-size: 13px; color: #64748b; }
		.' . $uid . '-spark-col { width: 120px; text-align: right; }
		.' . $uid . '-spark-svg { width: 100px; height: 30px; overflow: visible; }
		.' . $uid . '-spark-line { fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
		.' . $uid . '-spark-area { opacity: 0.1; }
		.kc-spark-up { stroke: #10b981; } .' . $uid . '-up-fill { fill: #10b981; }
		.kc-spark-down { stroke: #ef4444; } .' . $uid . '-down-fill { fill: #ef4444; }
		.kc-spark-neutral { stroke: #94a3b8; } .' . $uid . '-neutral-fill { fill: #94a3b8; }
		.' . $uid . '-move-col { width: 80px; text-align: center; }
		.' . $uid . '-badge-up { background: #dcfce7; color: #166534; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-down { background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		.' . $uid . '-badge-new { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; }
		</style>';

		echo '<div class="' . $uid . '-wrap kc-elm-card">';
		echo '<table class="' . $uid . '-table">';
		echo '<thead><tr>';
		echo '<th class="kc-elm-counter">#</th>';
		echo '<th>تفاصيل الأغنية</th>';
		echo '<th class="' . $uid . '-spark-col">مسار 4 أسابيع</th>';
		echo '<th class="' . $uid . '-move-col">الحركة</th>';
		echo '</tr></thead><tbody>';

		foreach ( $entries as $e ) {
			$img      = ( ! empty( $e->resolved_image ) ? $e->resolved_image : $e->cover_image ) ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			$resolved = \Charts\Core\PublicIntegration::resolve_display_name( $e, $def );
			$is_new   = ( $e->movement_direction === 'new' );

			$history = $wpdb->get_col( $wpdb->prepare(
				"SELECT rank_position FROM $entries_table WHERE item_id = %d AND source_id = %d ORDER BY created_at DESC LIMIT 5",
				$e->item_id, $e->source_id
			) );
			$history = array_reverse( $history );

			if ( empty( $history ) ) $history = [ $e->rank_position ];
			if ( count( $history ) == 1 ) array_unshift( $history, 100 );

			$points   = [];
			$max_rank = 100;
			$width    = 100;
			$height   = 30;
			$step     = count( $history ) > 1 ? $width / ( count( $history ) - 1 ) : $width;

			foreach ( $history as $idx => $r ) {
				$points[] = ( $idx * $step ) . ',' . ( ( $r / $max_rank ) * $height );
			}
			$poly_pts = implode( ' ', $points );
			$area_pts = "0,$height $poly_pts $width,$height";

			$first_rank = $history[0];
			$last_rank  = end( $history );

			// Use both uid-prefixed and shared static class names so Elementor
			// style controls can target .kc-spark-up / .kc-spark-down / .kc-spark-neutral.
			if ( $last_rank < $first_rank ) {
				$line_class = $uid . '-spark-line kc-spark-up';
				$fill_class = $uid . '-up-fill';
			} elseif ( $last_rank > $first_rank ) {
				$line_class = $uid . '-spark-line kc-spark-down';
				$fill_class = $uid . '-down-fill';
			} else {
				$line_class = $uid . '-spark-line kc-spark-neutral';
				$fill_class = $uid . '-neutral-fill';
			}

			echo '<tr>';
			echo '<td class="kc-elm-counter" style="font-weight:900;font-size:18px;">' . \Charts\Core\Transliteration::to_arabic_numerals( $e->rank_position ) . '</td>';
			echo '<td>';
			echo '<div class="' . $uid . '-track-col">';
			echo '<img src="' . esc_url( $img ) . '" class="' . $uid . '-img kc-elm-img" loading="lazy">';
			echo '<div>';
			echo '<div class="' . $uid . '-title kc-elm-title">' . esc_html( $resolved['title'] ) . '</div>';
			echo '<div class="' . $uid . '-artist kc-elm-artist">' . esc_html( $resolved['subtitle'] ) . '</div>';
			echo '</div></div>';
			echo '</td>';

			echo '<td class="' . $uid . '-spark-col">';
			echo '<svg class="' . $uid . '-spark-svg" viewBox="0 0 100 30" preserveAspectRatio="none">';
			echo '<polygon points="' . $area_pts . '" class="' . $uid . '-spark-area ' . $fill_class . '"></polygon>';
			echo '<polyline points="' . $poly_pts . '" class="' . $line_class . '"></polyline>';
			echo '</svg>';
			echo '</td>';

			echo '<td class="' . $uid . '-move-col kc-elm-meta">';
			if ( $settings['show_badges'] === 'yes' && $is_new ) {
				echo '<span class="' . $uid . '-badge-new" style="background:#ef4444;color:#fff;">' . esc_html( $settings['badge_new_text'] ) . '</span>';
			} else {
				$move = $e->movement_value;
				if ( $e->movement_direction === 'up' ) {
					echo '<span class="' . $uid . '-badge-up">▲ ' . \Charts\Core\Transliteration::to_arabic_numerals( $move ) . '</span>';
				} elseif ( $e->movement_direction === 'down' ) {
					echo '<span class="' . $uid . '-badge-down">▼ ' . \Charts\Core\Transliteration::to_arabic_numerals( $move ) . '</span>';
				} else {
					echo '<span class="' . $uid . '-badge-new">جديد</span>';
				}
			}
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div></div>';
	}
}
