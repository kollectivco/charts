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
	 * Register Chart, Artist, Track, Video, and Location CPTs.
	 */
	public static function register_cpts() {
		$common_args = array(
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'charts-dashboard',
			'show_in_rest'        => true,
			'query_var'           => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		);

		// 1. Charts
		register_post_type( 'chart', array_merge( $common_args, array(
			'labels' => array(
				'name'               => __( 'Charts', 'charts' ),
				'singular_name'      => __( 'Chart', 'charts' ),
				'add_new'            => __( 'Add New Chart', 'charts' ),
				'add_new_item'       => __( 'Add New Chart', 'charts' ),
				'edit_item'          => __( 'Edit Chart', 'charts' ),
				'new_item'           => __( 'New Chart', 'charts' ),
				'view_item'          => __( 'View Chart', 'charts' ),
				'search_items'       => __( 'Search Charts', 'charts' ),
				'not_found'          => __( 'No charts found', 'charts' ),
				'item_published'     => __( 'Chart published', 'charts' ),
			),
			'has_archive'         => 'charts',
			'rewrite'             => array( 'slug' => 'charts', 'with_front' => false ),
			'menu_icon'           => 'dashicons-chart-bar',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'custom-fields' ),
		) ) );

		// 2. Artists
		register_post_type( 'artist', array_merge( $common_args, array(
			'labels' => array(
				'name'          => __( 'Artists', 'charts' ),
				'singular_name' => __( 'Artist', 'charts' ),
				'add_new'       => __( 'Add New Artist', 'charts' ),
				'edit_item'     => __( 'Edit Artist', 'charts' ),
				'search_items'  => __( 'Search Artists', 'charts' ),
				'view_item'     => __( 'View Artist', 'charts' ),
			),
			'has_archive'         => 'charts/artists',
			'rewrite'             => array( 'slug' => 'charts/artist', 'with_front' => false ),
			'menu_icon'           => 'dashicons-groups',
		) ) );

		// 3. Tracks
		register_post_type( 'track', array_merge( $common_args, array(
			'labels' => array(
				'name'          => __( 'Tracks', 'charts' ),
				'singular_name' => __( 'Track', 'charts' ),
				'add_new'       => __( 'Add New Track', 'charts' ),
				'edit_item'     => __( 'Edit Track', 'charts' ),
				'search_items'  => __( 'Search Tracks', 'charts' ),
				'view_item'     => __( 'View Track', 'charts' ),
			),
			'has_archive'         => 'charts/tracks',
			'rewrite'             => array( 'slug' => 'charts/track', 'with_front' => false ),
			'menu_icon'           => 'dashicons-playlist-audio',
		) ) );

		// 4. Clips / Videos
		register_post_type( 'video', array_merge( $common_args, array(
			'labels' => array(
				'name'          => __( 'Music Clips', 'charts' ),
				'singular_name' => __( 'Clip', 'charts' ),
				'add_new'       => __( 'Add New Clip', 'charts' ),
				'edit_item'     => __( 'Edit Clip', 'charts' ),
				'search_items'  => __( 'Search Clips', 'charts' ),
				'view_item'     => __( 'View Clip', 'charts' ),
			),
			'has_archive'         => 'charts/clips',
			'rewrite'             => array( 'slug' => 'charts/video', 'with_front' => false ),
			'menu_icon'           => 'dashicons-video-alt3',
		) ) );

	}
}

