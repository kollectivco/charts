<?php

namespace Charts\Services;

/**
 * Lightweight internal analytics for page views and click activity.
 */
class AnalyticsService {

	public static function record_view() {
		$context = ( new EditorialService() )->get_current_context();
		if ( empty( $context['type'] ) ) {
			return;
		}

		self::record_metric(
			'view',
			$context['type'],
			self::resolve_object_type( $context ),
			self::resolve_object_id( $context ),
			self::resolve_slug( $context )
		);
	}

	public static function record_click( $payload ) {
		self::record_metric(
			'click',
			sanitize_key( $payload['page_type'] ?? 'unknown' ),
			sanitize_key( $payload['object_type'] ?? 'unknown' ),
			(int) ( $payload['object_id'] ?? 0 ),
			sanitize_title( $payload['slug'] ?? '' )
		);
	}

	private static function record_metric( $event_type, $page_type, $object_type, $object_id, $slug ) {
		global $wpdb;

		$table = $wpdb->prefix . 'charts_page_metrics';
		$date  = current_time( 'Y-m-d' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (event_date, event_type, page_type, object_type, object_id, object_slug, total_count, updated_at)
				 VALUES (%s, %s, %s, %s, %d, %s, 1, %s)
				 ON DUPLICATE KEY UPDATE total_count = total_count + 1, updated_at = VALUES(updated_at)",
				$date,
				$event_type,
				$page_type,
				$object_type,
				$object_id,
				$slug,
				current_time( 'mysql' )
			)
		);
	}

	private static function resolve_object_type( $context ) {
		if ( ! empty( $context['type'] ) && in_array( $context['type'], array( 'list', 'track', 'artist', 'trending', 'index' ), true ) ) {
			return $context['type'];
		}
		return 'unknown';
	}

	private static function resolve_object_id( $context ) {
		if ( ! empty( $context['definition']->id ) ) {
			return (int) $context['definition']->id;
		}
		if ( ! empty( $context['artist']->id ) ) {
			return (int) $context['artist']->id;
		}
		if ( ! empty( $context['item']->id ) ) {
			return (int) $context['item']->id;
		}
		return 0;
	}

	private static function resolve_slug( $context ) {
		if ( ! empty( $context['definition']->slug ) ) {
			return (string) $context['definition']->slug;
		}
		if ( ! empty( $context['artist']->slug ) ) {
			return (string) $context['artist']->slug;
		}
		if ( ! empty( $context['item']->slug ) ) {
			return (string) $context['item']->slug;
		}
		return '';
	}
}
