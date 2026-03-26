<?php

namespace Charts\Database;

/**
 * Handle custom database table creation and migrations.
 */
class Schema {

	/**
	 * Run the installation / upgrade process.
	 * Idempotent — safe to call on every version bump.
	 */
	public function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// ── dbDelta CREATE TABLE statements ─────────────────────────────────
		$queries = array(

			// 1. Sources
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_sources` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_name` VARCHAR(255) NOT NULL,
				`platform` VARCHAR(50) NOT NULL,
				`source_type` VARCHAR(20) NOT NULL DEFAULT 'manual_import',
				`country_code` VARCHAR(10) NOT NULL,
				`chart_type` VARCHAR(50) NOT NULL,
				`frequency` ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'weekly',
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

			// 2. Periods
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_periods` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`frequency` ENUM('daily','weekly','monthly') NOT NULL,
				`period_start` DATE NOT NULL,
				`period_end` DATE NOT NULL,
				`label` VARCHAR(100) NOT NULL,
				`source_date_key` VARCHAR(50) DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `period_unique` (`frequency`,`period_start`),
				KEY `period_start` (`period_start`),
				KEY `period_end` (`period_end`)
			) $charset_collate;",

			// 3. Artists
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`display_name` VARCHAR(255) NOT NULL,
				`normalized_name` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`image` TEXT DEFAULT NULL,
				`metadata_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `normalized_name` (`normalized_name`)
			) $charset_collate;",

			// 4. Albums
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_albums` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`cover_image` TEXT DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`release_date` DATE DEFAULT NULL,
				`metadata_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 5. Tracks
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_tracks` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`youtube_id` VARCHAR(100) DEFAULT NULL,
				`cover_image` TEXT DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`album_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`release_date` DATE DEFAULT NULL,
				`metadata_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `spotify_id` (`spotify_id`),
				KEY `youtube_id` (`youtube_id`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `album_id` (`album_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 6. Videos
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_videos` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`normalized_title` VARCHAR(255) NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`thumbnail` TEXT DEFAULT NULL,
				`related_track_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`primary_artist_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`youtube_id` VARCHAR(100) DEFAULT NULL,
				`video_url` TEXT DEFAULT NULL,
				`metadata_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `primary_artist_id` (`primary_artist_id`),
				KEY `related_track_id` (`related_track_id`),
				KEY `youtube_id` (`youtube_id`),
				KEY `normalized_title` (`normalized_title`)
			) $charset_collate;",

			// 7. Track Artists
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_track_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`track_id` BIGINT(20) UNSIGNED NOT NULL,
				`artist_id` BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				KEY `track_id` (`track_id`),
				KEY `artist_id` (`artist_id`),
				UNIQUE KEY `track_artist` (`track_id`,`artist_id`)
			) $charset_collate;",

			// 8. Video Artists
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_video_artists` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`video_id` BIGINT(20) UNSIGNED NOT NULL,
				`artist_id` BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				KEY `video_id` (`video_id`),
				KEY `artist_id` (`artist_id`),
				UNIQUE KEY `video_artist` (`video_id`,`artist_id`)
			) $charset_collate;",

			// 9. Entries — includes denormalized flat display columns
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_entries` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_id` BIGINT(20) UNSIGNED NOT NULL,
				`period_id` BIGINT(20) UNSIGNED NOT NULL,
				`item_type` VARCHAR(20) NOT NULL DEFAULT 'track',
				`item_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				`rank_position` INT(11) NOT NULL,
				`previous_rank` INT(11) DEFAULT NULL,
				`peak_rank` INT(11) DEFAULT NULL,
				`weeks_on_chart` INT(11) DEFAULT 1,
				`streak` INT(11) DEFAULT 1,
				`movement_value` INT(11) DEFAULT 0,
				`movement_direction` ENUM('up','down','same','new','re-entry') DEFAULT 'new',
				`is_new_entry` TINYINT(1) DEFAULT 1,
				`is_reentry` TINYINT(1) DEFAULT 0,
				`streams` BIGINT(20) DEFAULT 0,
				`streams_count` BIGINT(20) DEFAULT 0,
				`views_count` BIGINT(20) DEFAULT 0,
				`score` DECIMAL(10,2) DEFAULT 0,
				`track_name` VARCHAR(500) DEFAULT NULL,
				`artist_names` TEXT DEFAULT NULL,
				`cover_image` TEXT DEFAULT NULL,
				`spotify_id` VARCHAR(100) DEFAULT NULL,
				`youtube_id` VARCHAR(100) DEFAULT NULL,
				`source_url` TEXT DEFAULT NULL,
				`raw_payload_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `entry_rank_unique` (`source_id`,`period_id`,`rank_position`),
				KEY `source_id` (`source_id`),
				KEY `period_id` (`period_id`),
				KEY `item_type` (`item_type`),
				KEY `item_id` (`item_id`),
				KEY `rank_position` (`rank_position`),
				KEY `spotify_id` (`spotify_id`),
				KEY `youtube_id` (`youtube_id`)
			) $charset_collate;",

			// 10. Aliases
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
				KEY `entity_lookup` (`entity_type`,`entity_id`)
			) $charset_collate;",

			// 11. Import Runs
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_import_runs` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`source_id` BIGINT(20) UNSIGNED NOT NULL,
				`run_type` ENUM('manual','cron','csv','youtube_csv') NOT NULL DEFAULT 'csv',
				`status` ENUM('started','processing','completed','failed') NOT NULL DEFAULT 'started',
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

			// 12. Insights
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_insights` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`period_id` BIGINT(20) UNSIGNED NOT NULL,
				`source_id` BIGINT(20) UNSIGNED DEFAULT NULL,
				`country_code` VARCHAR(10) DEFAULT NULL,
				`chart_type` VARCHAR(50) DEFAULT NULL,
				`insight_type` VARCHAR(50) NOT NULL,
				`title` VARCHAR(255) NOT NULL,
				`summary` TEXT NOT NULL,
				`payload_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `period_id` (`period_id`),
				KEY `source_id` (`source_id`),
				KEY `country_code` (`country_code`),
				KEY `chart_type` (`chart_type`),
				KEY `insight_type` (`insight_type`)
			) $charset_collate;",

			// 13. Chart Definitions (Dynamic Charts)
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_definitions` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`title_ar` VARCHAR(255) DEFAULT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`chart_summary` TEXT DEFAULT NULL,
				`chart_type` VARCHAR(50) NOT NULL,
				`item_type` VARCHAR(20) NOT NULL DEFAULT 'track',
				`country_code` VARCHAR(10) NOT NULL,
				`frequency` ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'weekly',
				`platform` VARCHAR(50) DEFAULT 'all',
				`cover_image_url` TEXT DEFAULT NULL,
				`accent_color` VARCHAR(20) DEFAULT '#6366f1',
				`is_public` TINYINT(1) NOT NULL DEFAULT 1,
				`is_featured` TINYINT(1) NOT NULL DEFAULT 0,
				`archive_enabled` TINYINT(1) NOT NULL DEFAULT 1,
				`menu_order` INT(11) NOT NULL DEFAULT 0,
				`display_settings_json` LONGTEXT DEFAULT NULL,
				`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`),
				KEY `is_public` (`is_public`),
				KEY `is_featured` (`is_featured`),
				KEY `menu_order` (`menu_order`)
			) $charset_collate;",

			// 14. Intelligence Layer
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}charts_intelligence` (
				`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`entity_type` ENUM('track','video','artist','chart') NOT NULL,
				`entity_id` BIGINT(20) UNSIGNED NOT NULL,
				`momentum_score` DECIMAL(10,2) DEFAULT 0,
				`growth_rate` DECIMAL(10,2) DEFAULT 0,
				`trend_status` VARCHAR(20) DEFAULT 'stable',
				`total_streams` BIGINT(20) DEFAULT 0,
				`avg_rank` DECIMAL(10,2) DEFAULT 0,
				`peaks_count` INT(11) DEFAULT 0,
				`weeks_on_chart` INT(11) DEFAULT 0,
				`volatility_score` DECIMAL(10,2) DEFAULT 0,
				`metadata_json` LONGTEXT DEFAULT NULL,
				`last_calculated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `entity_unique` (`entity_type`,`entity_id`),
				KEY `momentum` (`momentum_score`),
				KEY `trend` (`trend_status`)
			) $charset_collate;",
		);

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}

		// ── Live ALTER TABLE upgrades for existing installs ──────────────────
		$this->run_column_upgrades( $wpdb );
	}

	/**
	 * Add missing columns to existing tables without wiping data.
	 * Safe to run repeatedly — checks column existence before ALTER.
	 */
	private function run_column_upgrades( $wpdb ) {
		$entries = $wpdb->prefix . 'charts_entries';

		$needed = array(
			'track_name'    => "VARCHAR(500) DEFAULT NULL",
			'artist_names'  => "TEXT DEFAULT NULL",
			'cover_image'   => "TEXT DEFAULT NULL",
			'spotify_id'    => "VARCHAR(100) DEFAULT NULL",
			'youtube_id'    => "VARCHAR(100) DEFAULT NULL",
			'source_url'    => "TEXT DEFAULT NULL",
			'streams'       => "BIGINT(20) DEFAULT 0",
			'streams_count' => "BIGINT(20) DEFAULT 0",
			'views_count'   => "BIGINT(20) DEFAULT 0",
		);

		$existing_cols = $wpdb->get_col( "DESCRIBE $entries", 0 );

		foreach ( $needed as $col => $definition ) {
			if ( ! in_array( $col, $existing_cols, true ) ) {
				$wpdb->query( "ALTER TABLE `$entries` ADD COLUMN `$col` $definition" );
			}
		}

		// Fix the UNIQUE KEY — old installs have entry_unique(source_id,period_id,item_type,item_id)
		// New canonical key is entry_rank_unique(source_id,period_id,rank_position)
		$old_key_exists = $wpdb->get_var(
			"SELECT COUNT(*) FROM information_schema.STATISTICS
			 WHERE table_schema = DATABASE()
			   AND table_name = '$entries'
			   AND index_name = 'entry_unique'"
		);
		if ( $old_key_exists ) {
			$wpdb->query( "ALTER TABLE `$entries` DROP INDEX `entry_unique`" );
		}
		$new_key_exists = $wpdb->get_var(
			"SELECT COUNT(*) FROM information_schema.STATISTICS
			 WHERE table_schema = DATABASE()
			   AND table_name = '$entries'
			   AND index_name = 'entry_rank_unique'"
		);
		if ( ! $new_key_exists ) {
			// Ignore duplicate-key error if rows already conflict — best-effort
			$wpdb->query( "ALTER IGNORE TABLE `$entries` ADD UNIQUE KEY `entry_rank_unique` (`source_id`,`period_id`,`rank_position`)" );
		}

		// Also add youtube_id column to tracks and videos tables
		foreach ( array( $wpdb->prefix . 'charts_tracks', $wpdb->prefix . 'charts_videos' ) as $tbl ) {
			$cols = $wpdb->get_col( "DESCRIBE $tbl", 0 );
			if ( ! in_array( 'youtube_id', $cols, true ) ) {
				$wpdb->query( "ALTER TABLE `$tbl` ADD COLUMN `youtube_id` VARCHAR(100) DEFAULT NULL" );
			}
		}
		// Upgrade definitions table
		$definitions_tbl = $wpdb->prefix . 'charts_definitions';
		$def_cols = $wpdb->get_col( "DESCRIBE $definitions_tbl", 0 );
		$def_needed = array(
			'title_ar'        => "VARCHAR(255) DEFAULT NULL",
			'cover_image_url' => "TEXT DEFAULT NULL",
			'accent_color'    => "VARCHAR(20) DEFAULT '#6366f1'",
			'archive_enabled' => "TINYINT(1) NOT NULL DEFAULT 1",
		);
		foreach ( $def_needed as $col => $definition ) {
			if ( ! in_array( $col, $def_cols, true ) ) {
				$wpdb->query( "ALTER TABLE `$definitions_tbl` ADD COLUMN `$col` $definition" );
			}
		}
	}
}
