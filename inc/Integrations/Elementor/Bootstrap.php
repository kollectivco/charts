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

		add_action( 'elementor/elements/categories_registered', array( self::class, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( self::class, 'register_widgets' ) );
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
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/FeaturedChart.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartTable.php';
		require_once CHARTS_PATH . 'inc/Integrations/Elementor/Widgets/ChartLeader.php';
		
		// Register widget instances
		$widgets_manager->register( new Widgets\ChartGrid() );
		$widgets_manager->register( new Widgets\FeaturedChart() );
		$widgets_manager->register( new Widgets\ChartTable() );
		$widgets_manager->register( new Widgets\ChartLeader() );
	}
}
