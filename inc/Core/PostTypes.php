<?php

namespace Charts\Core;

/**
 * Handle Custom Post Type registration for core entities.
 */
class PostTypes {

	/**
	 * Register all core CPTs.
	 */
	public static function init() {
		add_action( 'init', array( self::class, 'register_cpts' ) );
	}

	/**
	 * Register Chart CPT.
	 * Artists, Tracks, and Videos are omitted in Phase 1 for safety.
	 */
	public static function register_cpts() {
		register_post_type( 'chart', array(
			'labels' => array(
				'name'               => __( 'Charts (Native)', 'charts' ),
				'singular_name'      => __( 'Chart', 'charts' ),
				'add_new'            => __( 'Add New Chart', 'charts' ),
				'add_new_item'       => __( 'Add New Chart', 'charts' ),
				'edit_item'          => __( 'Edit Chart', 'charts' ),
				'new_item'           => __( 'New Chart', 'charts' ),
				'view_item'          => __( 'View Chart', 'charts' ),
				'search_items'       => __( 'Search Charts', 'charts' ),
				'not_found'          => __( 'No charts found', 'charts' ),
				'not_found_in_trash' => __( 'No charts found in trash', 'charts' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'has_archive'         => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'custom-fields' ),
			'hierarchical'        => false,
			'rewrite'             => false,
		) );

	}
}

