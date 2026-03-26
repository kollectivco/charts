<?php

namespace Charts\Database;

/**
 * Handle custom database table creation and migrations.
 */
class Schema {

	/**
	 * Run the installation process.
	 */
	public function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$queries = array(
			// 1. Sources table
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_sources` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_name` VARCHAR(255) NOT NULL,
				`platform` VARCHAR(50) NOT NULL,
				`source_type` VARCHAR(20) NOT NULL DEFAULT 'live_scrape',
				`country_code` VARCHAR(10) NOT NULL,
				`chart_type` VARCHAR(50) NOT NULL,
				`frequency` ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
				`source_url` TEXT NOT NULL,
				`parser_key` VARCHAR(50) NOT NULL,
				`is_active` TINYINT(1) NOT NULL DEFAULT 1,
				`last_run_at` DATETIME DEFAULT NULL,
				`last_success_at` DATETIME DEFAULT NULL,
				`last_error_at` DATETIME DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `platform` (`platform`),
				KEY `source_type` (`source_type`),
				KEY `country_code` (`country_code`),
				KEY `frequency` (`frequency`),
				KEY `is_active` (`is_active`)
			) $charset_collate;",

			// 2. Periods table (unchanged structure)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_periods` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`frequency` ENUM('daily', 'weekly', 'monthly') NOT NULL,
				`period_start` DATE NOT NULL,
				`period_end` DATE NOT NULL,
				`label` VARCHAR(100) NOT NULL,
				`source_date_key` VARCHAR(50) DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `period_unique` (`frequency`, `period_start`),
				KEY `period_start` (`period_start`),
				KEY `period_end` (`period_end`)
			) $charset_collate;",

			// 3. Artists table
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`display_name` VARCHAR(255) NOT NULL,
				`normalized_name` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`image` TEXT DEFAULT NULL,
				`metadata_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `normalized_name` (`normalized_name`)
			) $charset_collate;",

			// 4. Albums table
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_albums` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`cover_image` TEXT DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`release_date` DATE DEFAULT NULL,
				`metadata_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 5. Tracks table
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_tracks` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`cover_image` TEXT DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`album_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`release_date` DATE DEFAULT NULL,
				`metadata_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `album_id` (`album_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 6. Videos table (unchanged structure)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_videos` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`thumbnail` TEXT DEFAULT NULL,
				`related_track_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`video_url` TEXT DEFAULT NULL,
				`metadata_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `related_track_id` (`related_track_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 7. Track Artists (unchanged)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_track_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`track_id` BIGINT(20) UNSIGNED NOT NULL,
				`artist_id` BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				KEY `track_id` (`track_id`),
				KEY `artist_id` (`artist_id`),
				UNIQUE KEY `track_artist` (`track_id`, `artist_id`)
			) $charset_collate;",

			// 8. Video Artists (unchanged)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_video_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`video_id` BIGINT(20) UNSIGNED NOT NULL,
				`artist_id` BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				KEY `video_id` (`video_id`),
				KEY `artist_id` (`artist_id`),
				UNIQUE KEY `video_artist` (`video_id`, `artist_id`)
			) $charset_collate;",

			// 9. Entries table (unchanged)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_entries` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_id` BIGINT(20) UNSIGNED NOT NULL,
				`period_id` BIGINT(20) UNSIGNED NOT NULL,
				`item_type` VARCHAR(20) NOT NULL,
				`item_id` BIGINT(20) UNSIGNED NOT NULL,
				`rank_position` INT(11) NOT NULL,
				`previous_rank` INT(11) DEFAULT NULL,
				`peak_rank` INT(11) DEFAULT NULL,
				`weeks_on_chart` INT(11) DEFAULT 1,
				`streak` INT(11) DEFAULT 1,
				`movement_value` INT(11) DEFAULT 0,
				`movement_direction` ENUM('up', 'down', 'same', 'new', 're-entry') DEFAULT 'new',
				`is_new_entry` TINYINT(1) DEFAULT 1,
				`is_reentry` TINYINT(1) DEFAULT 0,
				`streams_count` BIGINT(20) UNSIGNED DEFAULT 0,
				`views_count` BIGINT(20) UNSIGNED DEFAULT 0,
				`score` DECIMAL(10,2) DEFAULT 0,
				`raw_payload_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `entry_unique` (`source_id`, `period_id`, `item_type`, `item_id`),
				KEY `source_id` (`source_id`),
				KEY `period_id` (`period_id`),
				KEY `item_type` (`item_type`),
				KEY `item_id` (`item_id`),
				KEY `rank_position` (`rank_position`)
			) $charset_collate;",

			// 10. Aliases (unchanged)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_aliases` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`entity_type` VARCHAR(20) NOT NULL,
				`entity_id` BIGINT(20) UNSIGNED NOT NULL,
				`alias` VARCHAR(255) NOT NULL,
				`normalized_alias` VARCHAR(255) NOT NULL,
				`source_platform` VARCHAR(50) DEFAULT NULL,
				`confidence` INT(3) DEFAULT 100,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `alias_lookup` (`normalized_alias`),
				KEY `entity_lookup` (`entity_type`, `entity_id`)
			) $charset_collate;",

			// 11. Import Runs
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_import_runs` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_id` BIGINT(20) UNSIGNED NOT NULL,
				`run_type` ENUM('manual', 'cron', 'csv') NOT NULL DEFAULT 'manual',
				`status` ENUM('started', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'started',
				`fetched_rows` INT(11) DEFAULT 0,
				`parsed_rows` INT(11) DEFAULT 0,
				`created_items` INT(11) DEFAULT 0,
				`matched_items` INT(11) DEFAULT 0,
				`enrichment_attempts` INT(11) DEFAULT 0,
				`enrichment_failures` INT(11) DEFAULT 0,
				`error_message` TEXT DEFAULT NULL,
				`logs_json` LONGTEXT DEFAULT NULL,
				`started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`finished_at` DATETIME DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `source_id` (`source_id`),
				KEY `status` (`status`)
			) $charset_collate;",

			// 12. Insights table
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_insights` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`period_id` BIGINT(20) UNSIGNED NOT NULL,
				`source_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`country_code` VARCHAR(10) DEFAULT NULL,
				`chart_type` VARCHAR(50) DEFAULT NULL,
				`insight_type` VARCHAR(50) NOT NULL,
				`title` VARCHAR(255) NOT NULL,
				`summary` TEXT NOT NULL,
				`payload_json` JSON DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `period_id` (`period_id`),
				KEY `source_id` (`source_id`),
				KEY `country_code` (`country_code`),
				KEY `chart_type` (`chart_type`),
				KEY `insight_type` (`insight_type`)
			) $charset_collate;"
		);

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}
	}
}
