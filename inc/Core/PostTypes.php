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
		// CPT architecture disabled by user request. Returning to legacy SQL model.
	}
}

