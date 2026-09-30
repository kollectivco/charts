<?php
namespace Charts\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class BentoIntelligence extends Widget_Base {

	public function get_name() { return 'kc_bento_intelligence'; }
	public function get_title() { return __( 'Bento Intelligence Hub', 'charts' ); }
	public function get_icon() { return 'eicon-dashboard'; }
	public function get_categories() { return [ 'charts' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Bento Settings', 'charts' ),
			'tab' => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => __( 'This widget automatically builds a Bento grid from the Intelligence Engine (Top Song, Top Artist, Top Breakout).', 'charts' ),
			'content_classes' => 'elementor-descriptor',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		global $wpdb;
		$intel = $wpdb->prefix . 'charts_intelligence';
		$tracks = $wpdb->prefix . 'charts_tracks';
		$artists = $wpdb->prefix . 'charts_artists';

		// Top Song (Highest overall score / max momentum)
		$top_song = $wpdb->get_row("SELECT i.momentum_score, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' ORDER BY i.momentum_score DESC LIMIT 1");
		
		// Top Artist (Highest power score)
		$top_artist = $wpdb->get_row("SELECT i.artist_power_score, a.display_name as artist, a.image FROM $intel i JOIN $artists a ON a.id = i.entity_id WHERE i.entity_type = 'artist' ORDER BY i.artist_power_score DESC LIMIT 1");
		
		// Top Breakout (Highest growth rate)
		$breakout = $wpdb->get_row("SELECT i.growth_rate, t.title, t.cover_image, a.display_name as artist FROM $intel i JOIN $tracks t ON t.id = i.entity_id LEFT JOIN $artists a ON a.id = t.primary_artist_id WHERE i.entity_type = 'track' AND i.momentum_score < 80 ORDER BY i.growth_rate DESC LIMIT 1");

		$uid = 'kc-bh-' . $this->get_id();

		echo '<style>
		.' . $uid . '-grid { display: grid; grid-template-columns: repeat(3, 1fr); grid-template-rows: 240px 160px; gap: 16px; font-family: "Inter", sans-serif; }
		@media (max-width: 768px) { .' . $uid . '-grid { grid-template-columns: 1fr; grid-template-rows: auto auto auto; } }
		.' . $uid . '-card { position: relative; border-radius: 20px; overflow: hidden; background: #0f172a; display: flex; flex-direction: column; justify-content: flex-end; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
		.' . $uid . '-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0.6; transition: transform 0.6s; }
		.' . $uid . '-card:hover .' . $uid . '-bg { transform: scale(1.05); }
		.' . $uid . '-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.2) 100%); }
		.' . $uid . '-content { position: relative; z-index: 2; }
		
		/* Grid placements */
		.' . $uid . '-main { grid-column: span 2; grid-row: span 2; }
		.' . $uid . '-side1 { grid-column: span 1; grid-row: span 1; }
		.' . $uid . '-side2 { grid-column: span 1; grid-row: span 1; }
		@media (max-width: 768px) {
			.' . $uid . '-main { grid-column: span 1; grid-row: span 1; height: 300px; }
		}
		
		/* Typography */
		.' . $uid . '-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); color: #fff; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; padding: 4px 10px; border-radius: 12px; margin-bottom: 12px; }
		.' . $uid . '-title { font-size: 32px; font-weight: 900; color: #fff; line-height: 1.1; margin: 0 0 4px 0; letter-spacing: -0.02em; }
		.' . $uid . '-artist { font-size: 16px; font-weight: 500; color: #cbd5e1; margin: 0; }
		
		.' . $uid . '-side1 .' . $uid . '-title { font-size: 20px; }
		.' . $uid . '-side2 .' . $uid . '-title { font-size: 20px; }
		
		/* Accent colors */
		.' . $uid . '-main-badge { background: rgba(99, 102, 241, 0.3) !important; color: #818cf8 !important; border: 1px solid rgba(99, 102, 241, 0.4); }
		.' . $uid . '-side1-badge { background: rgba(236, 72, 153, 0.3) !important; color: #f472b6 !important; border: 1px solid rgba(236, 72, 153, 0.4); }
		.' . $uid . '-side2-badge { background: rgba(16, 185, 129, 0.3) !important; color: #34d399 !important; border: 1px solid rgba(16, 185, 129, 0.4); }
		</style>';

		echo '<div class="' . $uid . '-grid">';
		
		// 1. Top Song
		if ($top_song) {
			$img = $top_song->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			echo '<div class="' . $uid . '-card ' . $uid . '-main">';
			echo '<div class="' . $uid . '-bg" style="background-image:url(\'' . esc_url($img) . '\');"></div>';
			echo '<div class="' . $uid . '-overlay"></div>';
			echo '<div class="' . $uid . '-content">';
			echo '<div class="' . $uid . '-badge ' . $uid . '-main-badge">👑 Track of the Week</div>';
			echo '<h3 class="' . $uid . '-title">' . esc_html($top_song->title) . '</h3>';
			echo '<p class="' . $uid . '-artist">' . esc_html($top_song->artist) . '</p>';
			echo '</div></div>';
		}

		// 2. Top Artist
		if ($top_artist) {
			$img = $top_artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			echo '<div class="' . $uid . '-card ' . $uid . '-side1">';
			echo '<div class="' . $uid . '-bg" style="background-image:url(\'' . esc_url($img) . '\');"></div>';
			echo '<div class="' . $uid . '-overlay"></div>';
			echo '<div class="' . $uid . '-content">';
			echo '<div class="' . $uid . '-badge ' . $uid . '-side1-badge">⭐ Top Authority</div>';
			echo '<h3 class="' . $uid . '-title">' . esc_html($top_artist->artist) . '</h3>';
			echo '<p class="' . $uid . '-artist">Score: ' . intval($top_artist->artist_power_score) . '</p>';
			echo '</div></div>';
		}

		// 3. Top Breakout
		if ($breakout) {
			$img = $breakout->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png';
			echo '<div class="' . $uid . '-card ' . $uid . '-side2">';
			echo '<div class="' . $uid . '-bg" style="background-image:url(\'' . esc_url($img) . '\');"></div>';
			echo '<div class="' . $uid . '-overlay"></div>';
			echo '<div class="' . $uid . '-content">';
			echo '<div class="' . $uid . '-badge ' . $uid . '-side2-badge">🚀 Fastest Riser</div>';
			echo '<h3 class="' . $uid . '-title">' . esc_html($breakout->title) . '</h3>';
			echo '<p class="' . $uid . '-artist">+' . intval($breakout->growth_rate) . '% Growth</p>';
			echo '</div></div>';
		}

		echo '</div>';
	}
}
