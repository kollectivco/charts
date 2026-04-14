<?php

namespace Charts\Admin;

/**
 * Handle chart sources administration and default seeding.
 */
class SourceManager {

	/**
	 * Seed default sources if they don't exist.
	 */
	public function seed_defaults() {
		// Seeding disabled - maintain for API compatibility if called elsewhere
	}

	/**
	 * Seed default sources.
	 */
	private function seed_sources() {
		// Seeding disabled
	}

	/**
	 * Seed default chart definitions.
	 */
	public function seed_definitions() {
		// Seeding disabled
	}

	/**
	 * Get all sources.
	 */
	public function get_sources() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}charts_sources ORDER BY platform ASC, country_code ASC" );
	}

	/**
	 * Get a single source.
	 */
	public function get_source( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_sources WHERE id = %d", $id ) );
	}

	/**
	 * Delete a source.
	 */
	public function delete_source( $id ) {
		global $wpdb;
		return $wpdb->delete( "{$wpdb->prefix}charts_sources", array( 'id' => $id ) );
	}

	/**
	 * Save or update a source.
	 */
	public function save_source( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_sources';

		$fields = array(
			'source_name'  => sanitize_text_field( $data['source_name'] ),
			'platform'     => sanitize_text_field( $data['platform'] ),
			'source_type'  => sanitize_text_field( $data['source_type'] ),
			'source_url'   => esc_url_raw( $data['source_url'] ),
			'country_code' => strtolower( sanitize_text_field( $data['country_code'] ) ),
			'frequency'    => sanitize_text_field( $data['frequency'] ),
			'chart_type'   => sanitize_text_field( $data['chart_type'] ),
			'parser_key'   => sanitize_text_field( $data['parser_key'] ),
			'is_active'    => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
		);

		// Special case for manual imports
		if ( $fields['source_type'] === 'manual_import' && empty( $fields['source_url'] ) ) {
			$fields['source_url'] = 'manual';
		}

		if ( ! empty( $data['id'] ) ) {
			$id = intval( $data['id'] );
			$wpdb->update( $table, $fields, array( 'id' => $id ) );
			return $id;
		}

		$wpdb->insert( $table, $fields );
		return $wpdb->insert_id;
	}

	/**
	 * Toggle source status.
	 */
	public function toggle_status( $id ) {
		global $wpdb;
		$source = $this->get_source( $id );
		if ( ! $source ) return false;
		
		$new_status = $source->is_active ? 0 : 1;
		return $wpdb->update( "{$wpdb->prefix}charts_sources", array( 'is_active' => $new_status ), array( 'id' => $id ) );
	}

	// ─────────────────────────────────────────────
	//  Chart Definitions (Dynamic Charts)
	// ─────────────────────────────────────────────

	public function get_definitions( $only_active = false ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';
		$where = $only_active ? "WHERE is_public = 1" : "WHERE 1=1";
		
		// Phase 1 Baseline: SQL First
		$rows = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY menu_order ASC" );
		
		foreach ( $rows as $row ) {
			// Find native bridge
			$row->native_post_id = \Charts\Core\EntityManager::get_post_id_by_legacy_id( 'chart', $row->id );
		}

		return $rows;
	}

	public function get_definition( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';
		
		// 1. Direct Post ID check (Promoted charts)
		$post = get_post( $id );
		if ( $post && $post->post_type === 'chart' ) {
			return $this->map_post_to_definition( $post );
		}

		// 2. Legacy SQL Table lookup
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
		if ( ! $row ) return null;

		// 3. Bridge Check: If this SQL row has been promoted, return the Post-Master version
		$native_post_id = \Charts\Core\EntityManager::get_post_id_by_legacy_id( 'chart', $row->id );
		if ( $native_post_id ) {
			return $this->get_definition( $native_post_id );
		}

		return $row;
	}

	public function get_definition_by_slug( $slug ) {
		// 1. Try CPT lookup by name
		$posts = get_posts( array(
			'post_type'  => 'chart',
			'name'       => $slug,
			'posts_per_page' => 1,
			'post_status' => 'any'
		) );
		if ( ! empty( $posts ) ) {
			return $this->map_post_to_definition( $posts[0] );
		}

		// 2. Fallback to SQL Table
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
		
		if ( $row ) {
			$post_id = $this->get_post_id_by_definition_id( $row->id );
			if ( $post_id ) {
				return $this->map_post_to_definition( get_post( $post_id ) );
			}
			return $row;
		}

		return null;
	}

	public function delete_definition( $id ) {
		global $wpdb;
		// Delete from CPT
		wp_delete_post( $id, true );
		// Legacy cleanup
		return $wpdb->delete( "{$wpdb->prefix}charts_definitions", array( 'id' => $id ) );
	}

	public function save_definition( $data ) {
		global $wpdb;
		$id = ! empty( $data['id'] ) ? intval( $data['id'] ) : 0;
		$is_cpt = false;

		if ( $id ) {
			$post = get_post( $id );
			if ( $post && $post->post_type === 'chart' ) {
				$is_cpt = true;
			}
		}

		if ( $is_cpt ) {
			// 1. Update CPT Master
			$post_data = array(
				'ID'           => $id,
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_name'    => sanitize_title( $data['slug'] ),
				'post_content' => sanitize_textarea_field( $data['chart_summary'] ),
				'post_status'  => isset( $data['is_public'] ) && $data['is_public'] ? 'publish' : 'draft',
				'menu_order'   => isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0,
			);
			wp_update_post( $post_data );

			update_post_meta( $id, '_title_ar', sanitize_text_field( $data['title_ar'] ?? '' ) );
			update_post_meta( $id, '_chart_type', sanitize_text_field( $data['chart_type'] ) );
			update_post_meta( $id, '_item_type', sanitize_text_field( $data['item_type'] ) );
			update_post_meta( $id, '_country_code', strtolower( sanitize_text_field( $data['country_code'] ) ) );
			update_post_meta( $id, '_frequency', sanitize_text_field( $data['frequency'] ) );
			update_post_meta( $id, '_platform', sanitize_text_field( $data['platform'] ?? 'all' ) );
			update_post_meta( $id, '_cover_image_url', esc_url_raw( $data['cover_image_url'] ?? '' ) );
			
			$color = !empty($data['accent_color']) ? (strpos($data['accent_color'], '#') === 0 ? sanitize_text_field($data['accent_color']) : '#' . sanitize_text_field($data['accent_color'])) : '#6366f1';
			update_post_meta( $id, '_accent_color', $color );
			
			update_post_meta( $id, '_is_featured', isset( $data['is_featured'] ) ? (int) $data['is_featured'] : 0 );
			update_post_meta( $id, '_ordering_mode', sanitize_text_field( $data['ordering_mode'] ?? 'import' ) );
			update_post_meta( $id, '_franco_mode', sanitize_text_field( $data['franco_mode'] ?? 'original' ) );
			update_post_meta( $id, '_archive_enabled', isset( $data['archive_enabled'] ) ? (int) $data['archive_enabled'] : 1 );

			// Sync to SQL
			$this->sync_definition_to_table( $id );
			return $id;
		} else {
			// 2. Update SQL Table Master (Legacy)
			$table = $wpdb->prefix . 'charts_definitions';
			$fields = array(
				'title'           => sanitize_text_field( $data['title'] ),
				'title_ar'        => sanitize_text_field( $data['title_ar'] ?? '' ),
				'slug'            => sanitize_title( $data['slug'] ),
				'chart_summary'   => sanitize_textarea_field( $data['chart_summary'] ),
				'chart_type'      => sanitize_text_field( $data['chart_type'] ),
				'item_type'       => sanitize_text_field( $data['item_type'] ),
				'country_code'    => strtolower( sanitize_text_field( $data['country_code'] ) ),
				'frequency'       => sanitize_text_field( $data['frequency'] ),
				'platform'        => sanitize_text_field( $data['platform'] ?? 'all' ),
				'cover_image_url' => esc_url_raw( $data['cover_image_url'] ?? '' ),
				'accent_color'    => !empty($data['accent_color']) ? (strpos($data['accent_color'], '#') === 0 ? sanitize_text_field($data['accent_color']) : '#' . sanitize_text_field($data['accent_color'])) : '#6366f1',
				'is_public'       => isset( $data['is_public'] ) && $data['is_public'] ? 1 : 0,
				'is_featured'     => isset( $data['is_featured'] ) ? (int) $data['is_featured'] : 0,
				'ordering_mode'   => sanitize_text_field( $data['ordering_mode'] ?? 'import' ),
				'franco_mode'     => sanitize_text_field( $data['franco_mode'] ?? 'original' ),
				'archive_enabled' => isset( $data['archive_enabled'] ) ? (int) $data['archive_enabled'] : 1,
				'menu_order'      => isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0,
				'updated_at'      => current_time( 'mysql' ),
			);

		if ( $id ) {
			// Update existing record
			$wpdb->update( $table, $fields, array( 'id' => $id ) );

			// CRITICAL: If this definition has been promoted, we MUST update the Post meta too 
			// otherwise the next 'get_definition' (which prefers posts) will return stale data.
			$native_post_id = \Charts\Core\EntityManager::get_post_id_by_legacy_id( 'chart', $id );
			if ( $native_post_id ) {
				update_post_meta( $native_post_id, '_ordering_mode', $fields['ordering_mode'] );
				update_post_meta( $native_post_id, '_franco_mode', $fields['franco_mode'] );
				update_post_meta( $native_post_id, '_item_type', $fields['item_type'] );
				update_post_meta( $native_post_id, '_chart_type', $fields['chart_type'] );
				update_post_meta( $native_post_id, '_platform', $fields['platform'] );
				update_post_meta( $native_post_id, '_country_code', $fields['country_code'] );
				update_post_meta( $native_post_id, '_frequency', $fields['frequency'] );
				
				// Sync back title/slug to post object
				wp_update_post( array(
					'ID'         => $native_post_id,
					'post_title' => $fields['title'],
					'post_name'  => $fields['slug'],
					'post_status' => $fields['is_public'] ? 'publish' : 'draft',
				) );
			}

			return $id;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $table, $fields );
			return $wpdb->insert_id;
		}
		}
	}

	/**
	 * Map a post object to a standard definition object for compatibility.
	 */
	private function map_post_to_definition( $post ) {
		if ( ! $post ) return null;
		
		$obj = new \stdClass();
		$obj->id              = $post->ID;
		$obj->title           = $post->post_title;
		$obj->title_ar        = get_post_meta( $post->ID, '_title_ar', true );
		$obj->slug            = $post->post_name;
		$obj->chart_summary   = $post->post_content;
		$obj->chart_type      = get_post_meta( $post->ID, '_chart_type', true );
		$obj->item_type       = get_post_meta( $post->ID, '_item_type', true );
		$obj->country_code    = get_post_meta( $post->ID, '_country_code', true );
		$obj->frequency       = get_post_meta( $post->ID, '_frequency', true );
		$obj->platform        = get_post_meta( $post->ID, '_platform', true );
		$obj->cover_image_url = get_post_meta( $post->ID, '_cover_image_url', true );
		$obj->accent_color    = get_post_meta( $post->ID, '_accent_color', true );
		$obj->is_public       = $post->post_status === 'publish' ? 1 : 0;
		$obj->is_featured     = (int) get_post_meta( $post->ID, '_is_featured', true );
		$obj->ordering_mode   = get_post_meta( $post->ID, '_ordering_mode', true ) ?: 'import';
		$obj->franco_mode     = get_post_meta( $post->ID, '_franco_mode', true ) ?: 'original';
		$obj->archive_enabled = (int) get_post_meta( $post->ID, '_archive_enabled', true );
		$obj->menu_order      = $post->menu_order;
		$obj->created_at      = $post->post_date;
		$obj->updated_at      = $post->post_modified;
		$obj->legacy_id       = get_post_meta( $post->ID, '_kcharts_legacy_id', true );

		return $obj;
	}

	/**
	 * Create a Chart CPT post from a legacy table record.
	 */
	public function promote_to_native( $def_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'charts_definitions';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $def_id ) );
		
		if ( ! $row ) return false;

		// Check if already promoted
		$existing_post_id = $this->get_post_id_by_definition_id( $def_id );
		if ( $existing_post_id ) return $existing_post_id;

		$post_data = array(
			'post_title'   => $row->title,
			'post_name'    => $row->slug,
			'post_content' => $row->chart_summary,
			'post_type'    => 'chart',
			'post_status'  => $row->is_public ? 'publish' : 'draft',
			'menu_order'   => $row->menu_order,
		);

		$post_id = wp_insert_post( $post_data );
		
		if ( $post_id ) {
			update_post_meta( $post_id, '_kcharts_definition_id', $def_id );
			update_post_meta( $post_id, '_title_ar', $row->title_ar );
			update_post_meta( $post_id, '_chart_type', $row->chart_type );
			update_post_meta( $post_id, '_item_type', $row->item_type );
			update_post_meta( $post_id, '_country_code', $row->country_code );
			update_post_meta( $post_id, '_frequency', $row->frequency );
			update_post_meta( $post_id, '_platform', $row->platform );
			update_post_meta( $post_id, '_cover_image_url', $row->cover_image_url );
			update_post_meta( $post_id, '_accent_color', $row->accent_color );
			update_post_meta( $post_id, '_is_featured', $row->is_featured );
			update_post_meta( $post_id, '_ordering_mode', $row->ordering_mode ?: 'import' );
			update_post_meta( $post_id, '_franco_mode', $row->franco_mode ?: 'original' );
			update_post_meta( $post_id, '_archive_enabled', $row->archive_enabled );
			
			// Optional: Try to set featured image from URL if possible (AssetManager can do this later)
		}

		return $post_id;
	}

	/**
	 * Helper to find CPT post ID linked to a legacy definition ID.
	 */
	public function get_post_id_by_definition_id( $def_id ) {
		$posts = get_posts( array(
			'post_type'  => 'chart',
			'meta_key'   => '_kcharts_definition_id',
			'meta_value' => $def_id,
			'posts_per_page' => 1,
			'post_status' => 'any',
			'fields'      => 'ids'
		) );
		return ! empty( $posts ) ? $posts[0] : false;
	}

	/**
	 * Sync CPT data back to custom table for compatibility.
	 */
	private function sync_definition_to_table( $post_id ) {
		global $wpdb;
		$post = get_post( $post_id );
		$def = $this->map_post_to_definition( $post );
		if ( ! $def ) return;

		$def_id = get_post_meta( $post_id, '_kcharts_definition_id', true );
		
		$table = $wpdb->prefix . 'charts_definitions';
		$data = array(
			'title'           => $def->title,
			'title_ar'        => $def->title_ar,
			'slug'            => $def->slug,
			'chart_summary'   => $def->chart_summary,
			'chart_type'      => $def->chart_type,
			'item_type'       => $def->item_type,
			'country_code'    => $def->country_code,
			'frequency'       => $def->frequency,
			'platform'        => $def->platform,
			'cover_image_url' => $def->cover_image_url,
			'accent_color'    => $def->accent_color,
			'ordering_mode'   => $def->ordering_mode,
			'franco_mode'     => $def->franco_mode,
			'is_public'       => $def->is_public,
			'is_featured'     => $def->is_featured,
			'archive_enabled' => $def->archive_enabled,
			'menu_order'      => $def->menu_order,
			'created_at'      => $def->created_at,
			'updated_at'      => $def->updated_at,
		);

		if ( $def_id ) {
			$wpdb->update( $table, $data, array( 'id' => $def_id ) );
		} else {
			$wpdb->insert( $table, $data );
			update_post_meta( $post_id, '_kcharts_definition_id', $wpdb->insert_id );
		}
	}
	/**
	 * Fetch manual entries assigned to this chart.
	 */
	public function get_manual_entries( $chart_id ) {
		global $wpdb;
		$definition = $this->get_definition( $chart_id );
		if ( ! $definition ) return array();

		// Use PublicIntegration to fetch current entries for this chart's cid- source
		// We pass a large limit (500) for the admin view to ensure visibility even if front-end limit is lower
		$entries = \Charts\Core\PublicIntegration::get_preview_entries( $definition, 500 );
		return $entries;
	}

	/**
	 * Save manual entry list to a chart.
	 * Replaces existing ranking data with the provided manual set.
	 */
	public function save_manual_entries( $chart_id, $entries_data ) {
		global $wpdb;
		$definition = $this->get_definition( $chart_id );
		if ( ! $definition ) return false;

		// 1. Ensure Source
		$lookup_type = "cid-{$chart_id}";
		$source_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s", $lookup_type ) );
		
		if ( ! $source_id ) {
			$wpdb->insert( "{$wpdb->prefix}charts_sources", array(
				'source_name'  => "Manual Curator → " . $definition->title,
				'platform'     => 'all',
				'source_type'  => 'manual_import',
				'country_code' => $definition->country_code,
				'chart_type'   => $lookup_type,
				'frequency'    => $definition->frequency,
				'source_url'   => 'manual',
				'parser_key'   => 'manual',
				'is_active'    => 1,
				'created_at'   => current_time( 'mysql' )
			) );
			$source_id = $wpdb->insert_id;
		}

		// 2. Ensure "Master" Period for Manual Charts
		// We use a fixed date far in the future or a specific key to represent "Current Manual"
		$period_start = '2099-01-01'; 
		$period_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}charts_periods WHERE frequency = %s AND period_start = %s", $definition->frequency, $period_start ) );
		
		if ( ! $period_id ) {
			$wpdb->insert( "{$wpdb->prefix}charts_periods", array(
				'frequency'    => $definition->frequency,
				'period_start' => $period_start,
				'period_end'   => '2099-12-31',
				'label'        => 'Manual Curation (Master)',
				'created_at'   => current_time( 'mysql' )
			) );
			$period_id = $wpdb->insert_id;
		}

		// 3. Purge existing entries for THIS specific source/period
		$wpdb->delete( "{$wpdb->prefix}charts_entries", array( 'source_id' => $source_id, 'period_id' => $period_id ) );

		// 4. Batch Insert new entries
		$saved = 0;
		foreach ( $entries_data as $idx => $item ) {
			$rank = $idx + 1;
			$item_id = intval( $item['id'] );
			$item_type = sanitize_text_field( $item['type'] ?? $definition->item_type );

			// Fetch metadata for flat record
			$table = ( $item_type === 'artist' ) ? 'artists' : ( ( $item_type === 'video' ) ? 'videos' : 'tracks' );
			$meta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_{$table} WHERE id = %d", $item_id ) );
			
			if ( ! $meta ) continue;

			$m_data = !empty($meta->metadata_json) ? json_decode($meta->metadata_json, true) : [];
			$source_track_en  = $m_data['title_en'] ?? ($m_data['name_en'] ?? '');
			$source_artist_en = $m_data['artist_en'] ?? ($m_data['artist_name_en'] ?? '');

			$wpdb->insert( "{$wpdb->prefix}charts_entries", array(
				'source_id'                 => $source_id,
				'period_id'                 => $period_id,
				'item_type'                 => $item_type,
				'item_id'                   => $item_id,
				'rank_position'             => $rank,
				'is_new_entry'              => 0,
				'track_name'                => $meta->title ?? ( $meta->display_name ?? '' ),
				'track_name_en'             => sanitize_text_field( $item['title_en'] ?: ( ($meta->title_en ?? '') ?: $source_track_en ) ),
				'artist_names'              => $meta->display_name ?? '',
				'artist_names_en'           => sanitize_text_field( $item['artist_en'] ?: ( ($meta->display_name_en ?? '') ?: $source_artist_en ) ),
				'item_slug'                 => $meta->slug,
				'cover_image'               => $meta->cover_image ?? ( $meta->thumbnail ?? ( $meta->image ?? '' ) ),
				'created_at'                => current_time( 'mysql' )
			) );
			$saved++;
		}

		return $saved;
	}

	/**
	 * Safely remove legacy seeded records that match known demo signatures.
	 * This protects user-created charts while cleaning up the DP 'Egypt' presets.
	 */
	public function cleanup_mock_data() {
		global $wpdb;

		// 1. Target legacy definitions
		$mock_slugs = array( 'top-songs', 'top-videos', 'top-artists', 'viral' );
		$mock_titles = array( 
			'Top Songs Egypt', 
			'Top Music Videos Egypt', 
			'Top Artists Egypt', 
			'Viral 50 Egypt' 
		);

		foreach ( $mock_slugs as $idx => $slug ) {
			$title = $mock_titles[$idx];
			// Delete ONLY if BOTH slug and title match the legacy seed exactly
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}charts_definitions WHERE slug = %s AND title = %s", 
				$slug, $title 
			) );
		}

		// 2. Target legacy sources
		$mock_sources = array(
			'Spotify Egypt Weekly Top Songs',
			'Spotify Egypt Daily Top Songs',
			'YouTube Egypt Weekly Top Songs',
			'YouTube Egypt Daily Top Music Videos'
		);

		foreach ( $mock_sources as $source_name ) {
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}charts_sources WHERE source_name = %s", 
				$source_name 
			) );
		}
	}
}
