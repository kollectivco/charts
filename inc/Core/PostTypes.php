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
			'public'              => true,
			'has_archive'         => true,
			'show_in_menu'        => false, // Handled manually in Bootstrap to avoid duplication
			'show_in_rest'        => true,  // Gutenberg & Elementor support
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-chart-bar',
			'rewrite'             => array( 'slug' => 'chart-native' ), // Avoid conflict with legacy /charts/ route
		) );

		register_post_type( 'artist', array(
			'labels' => array(
				'name'          => __( 'Artists (Native)', 'charts' ),
				'singular_name' => __( 'Artist', 'charts' ),
				'add_new'       => __( 'Add New Artist', 'charts' ),
				'edit_item'     => __( 'Edit Artist', 'charts' ),
				'view_item'     => __( 'View Artist', 'charts' ),
				'search_items'  => __( 'Search Artists', 'charts' ),
			),
			'public'       => true,
			'show_in_menu' => false, // Unified menu only
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'artist' ),
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-admin-users',
		) );

		register_post_type( 'track', array(
			'labels' => array(
				'name'          => __( 'Tracks (Native)', 'charts' ),
				'singular_name' => __( 'Track', 'charts' ),
				'add_new'       => __( 'Add New Track', 'charts' ),
				'edit_item'     => __( 'Edit Track', 'charts' ),
				'view_item'     => __( 'View Track', 'charts' ),
				'search_items'  => __( 'Search Tracks', 'charts' ),
			),
			'public'       => true,
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'track' ),
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-format-audio',
		) );

		register_post_type( 'video', array(
			'labels' => array(
				'name'          => __( 'Clips (Native)', 'charts' ),
				'singular_name' => __( 'Clip', 'charts' ),
				'add_new'       => __( 'Add New Clip', 'charts' ),
				'edit_item'     => __( 'Edit Clip', 'charts' ),
				'view_item'     => __( 'View Clip', 'charts' ),
				'search_items'  => __( 'Search Clips', 'charts' ),
			),
			'public'       => true,
			'show_in_menu' => false,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'clip' ),
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-video-alt3',
		) );
	}
}

