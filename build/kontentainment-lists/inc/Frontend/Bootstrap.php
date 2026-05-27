<?php

namespace Charts\Frontend;

/**
 * Handle frontend initialization.
 */
class Bootstrap {

	/**
	 * Initialize the frontend module.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'pre_get_document_title', array( self::class, 'filter_document_title' ) );
		add_action( 'wp_head', array( self::class, 'render_head_metadata' ), 1 );
		add_action( 'template_redirect', array( self::class, 'record_page_view' ), 20 );
		add_action( 'wp_ajax_charts_track_click', array( self::class, 'handle_track_click' ) );
		add_action( 'wp_ajax_nopriv_charts_track_click', array( self::class, 'handle_track_click' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets() {
		// Only load if on a charts page
		if ( get_query_var( 'charts_page' ) || get_query_var( 'charts_platform' ) ) {
			wp_enqueue_style( 'charts-public', CHARTS_URL . 'public/assets/css/public.css', array(), CHARTS_VERSION );
			wp_enqueue_script( 'lists-frontend', CHARTS_URL . 'public/assets/js/frontend.js', array(), CHARTS_VERSION, true );
			wp_localize_script(
				'lists-frontend',
				'kontentainmentLists',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'charts_frontend' ),
				)
			);
		}
	}

	public static function filter_document_title( $title ) {
		$context = ( new \Charts\Services\EditorialService() )->get_current_context();
		return ! empty( $context['title'] ) ? $context['title'] : $title;
	}

	public static function render_head_metadata() {
		$context = ( new \Charts\Services\EditorialService() )->get_current_context();
		if ( empty( $context ) ) {
			return;
		}

		if ( ! empty( $context['description'] ) ) {
			echo '<meta name="description" content="' . esc_attr( $context['description'] ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $context['description'] ) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $context['description'] ) . '">' . "\n";
		}

		if ( ! empty( $context['title'] ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $context['title'] ) . '">' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $context['title'] ) . '">' . "\n";
		}

		if ( ! empty( $context['canonical'] ) ) {
			echo '<link rel="canonical" href="' . esc_url( $context['canonical'] ) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url( $context['canonical'] ) . '">' . "\n";
		}

		if ( ! empty( $context['image'] ) ) {
			echo '<meta property="og:image" content="' . esc_url( $context['image'] ) . '">' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $context['image'] ) . '">' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		}

		echo '<meta property="og:type" content="website">' . "\n";

		if ( ! empty( $context['schema'] ) ) {
			foreach ( $context['schema'] as $schema ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
			}
		}
	}

	public static function record_page_view() {
		\Charts\Services\AnalyticsService::record_view();
	}

	public static function handle_track_click() {
		check_ajax_referer( 'charts_frontend', 'nonce' );
		\Charts\Services\AnalyticsService::record_click( $_POST );
		wp_send_json_success();
	}
}
