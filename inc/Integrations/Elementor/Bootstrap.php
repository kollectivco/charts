<?php

namespace Charts\Integrations\Elementor;

/**
 * Handle Elementor integration and widget registration.
 */
class Bootstrap {

	/**
	 * Initialize Elementor integration.
	 */
	public static function init() {
		// Only run if Elementor is active
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/frontend/after_register_scripts', array( self::class, 'register_frontend_assets' ) );
		add_action( 'elementor/elements/categories_registered', array( self::class, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( self::class, 'register_widgets' ) );
	}

	/**
	 * Register specialized frontend assets for Elementor.
	 */
	public static function register_frontend_assets() {
		wp_register_script( 
			'kc-public', 
			CHARTS_URL . 'public/assets/js/public.js', 
			array( 'jquery' ), 
			CHARTS_VERSION, 
			true 
		);
		
		wp_register_style( 
			'kc-public-style', 
			CHARTS_URL . 'public/assets/css/public.css', 
			array(), 
			CHARTS_VERSION 
		);
	}

	/**
	 * Register a custom category for Charts widgets.
	 */
	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'charts',
			array(
				'title' => __( 'Charts Intelligence', 'charts' ),
				'icon'  => 'fa fa-chart-bar',
			)
		);
	}

	/**
	 * Register all custom widgets.
	 */
	public static function register_widgets( $widgets_manager ) {
		// Include widget files
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartGrid.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartCarousel.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/FeaturedChart.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartTable.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartLeader.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartList.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/PremiumHeroSlider.php';
		
		// Register widget instances
		$widgets_manager->register( new Widgets\ChartGrid() );
		$widgets_manager->register( new Widgets\ChartCarousel() );
		$widgets_manager->register( new Widgets\FeaturedChart() );
		$widgets_manager->register( new Widgets\ChartTable() );
		$widgets_manager->register( new Widgets\ChartLeader() );
		$widgets_manager->register( new Widgets\ChartList() );
		$widgets_manager->register( new Widgets\PremiumHeroSlider() );
	}
}
