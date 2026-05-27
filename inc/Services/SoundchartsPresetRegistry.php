<?php

namespace Charts\Services;

/**
 * Structured preset registry for Soundcharts endpoint mapping.
 */
class SoundchartsPresetRegistry {

	/**
	 * Return available presets.
	 */
	public static function all() {
		return array(
			'top_tracks_egypt' => array(
				'label' => 'Top Tracks Egypt',
				'endpoint_path' => '/chart/tracks',
				'entity_type' => 'track',
				'default_country' => 'eg',
				'default_chart_type' => 'top-songs',
				'normalization_rules' => array( 'track', 'artist', 'album', 'rank' ),
			),
			'top_artists_egypt' => array(
				'label' => 'Top Artists Egypt',
				'endpoint_path' => '/chart/artists',
				'entity_type' => 'artist',
				'default_country' => 'eg',
				'default_chart_type' => 'top-artists',
				'normalization_rules' => array( 'artist', 'rank' ),
			),
			'viral_tracks' => array(
				'label' => 'Viral Tracks',
				'endpoint_path' => '/chart/viral',
				'entity_type' => 'track',
				'default_country' => 'eg',
				'default_chart_type' => 'viral',
				'normalization_rules' => array( 'track', 'artist', 'rank', 'trend' ),
			),
			'top_videos_egypt' => array(
				'label' => 'Top Videos Egypt',
				'endpoint_path' => '/chart/videos',
				'entity_type' => 'video',
				'default_country' => 'eg',
				'default_chart_type' => 'top-videos',
				'normalization_rules' => array( 'track', 'artist', 'rank', 'engagement' ),
			),
		);
	}

	/**
	 * Resolve a preset by key.
	 */
	public static function get( $key ) {
		$presets = self::all();
		return isset( $presets[ $key ] ) ? $presets[ $key ] : null;
	}
}
