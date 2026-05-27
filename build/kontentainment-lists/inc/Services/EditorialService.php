<?php

namespace Charts\Services;

/**
 * SEO, editorial copy, schema, share cards, and editorial picks.
 */
class EditorialService {

	/**
	 * Build SEO context for the current routed page.
	 */
	public function get_current_context() {
		$page = get_query_var( 'charts_page' );

		if ( $page === 'single-chart' ) {
			return $this->get_list_context();
		}

		if ( $page === 'artist-single' ) {
			return $this->get_artist_context();
		}

		if ( $page === 'item-single' ) {
			return $this->get_item_context();
		}

		if ( $page === 'trending' ) {
			return $this->get_trending_context();
		}

		if ( $page === 'index' ) {
			return $this->get_index_context();
		}

		return null;
	}

	/**
	 * Cached editorial block for a list page.
	 */
	public function get_list_editorial_content( $definition, $period, array $entries ) {
		$cache_key = 'lists_editorial_content_' . (int) $definition->id . '_' . (int) $period->id;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$number_one = $entries[0] ?? null;
		$biggest_climber = null;
		$new_entries = array();

		foreach ( $entries as $entry ) {
			if ( ! $biggest_climber || (int) $entry->movement_value > (int) $biggest_climber->movement_value ) {
				$biggest_climber = $entry;
			}

			if ( in_array( $entry->movement_direction, array( 'new', 're-entry' ), true ) ) {
				$new_entries[] = $entry;
			}
		}

		$country = strtoupper( $definition->country_code );
		$week_of = date_i18n( 'F j, Y', strtotime( $period->period_start ) );

		$intro = sprintf(
			'%1$s is the definitive weekly music list for %2$s, updated with fresh ranking intelligence for the week of %3$s. This week, %4$s leads the field, while momentum shifts across the top positions highlight where audience attention is moving next.',
			$definition->title,
			$country,
			$week_of,
			$number_one ? $number_one->track_name . ' by ' . $number_one->artist_names : 'the latest top-ranked release'
		);

		$highlights = array();
		if ( $number_one ) {
			$highlights[] = sprintf(
				'#1 analysis: %1$s by %2$s holds the top spot with a peak rank of #%3$d and %4$d weeks on the list.',
				$number_one->track_name,
				$number_one->artist_names,
				$number_one->peak_rank ?: 1,
				$number_one->weeks_on_chart ?: 1
			);
		}
		if ( $biggest_climber && (int) $biggest_climber->movement_value > 0 ) {
			$highlights[] = sprintf(
				'Biggest climber: %1$s by %2$s jumps %3$d places to #%4$d.',
				$biggest_climber->track_name,
				$biggest_climber->artist_names,
				$biggest_climber->movement_value,
				$biggest_climber->rank_position
			);
		}
		if ( ! empty( $new_entries ) ) {
			$names = array_slice(
				array_map(
					static function ( $entry ) {
						return $entry->track_name . ' by ' . $entry->artist_names;
					},
					$new_entries
				),
				0,
				3
			);
			$highlights[] = 'New entries: ' . implode( ', ', $names ) . '.';
		}

		$content = array(
			'intro'      => $intro,
			'highlights' => $highlights,
		);

		set_transient( $cache_key, $content, DAY_IN_SECONDS );
		return $content;
	}

	/**
	 * Fetch trending hub sections with cache.
	 */
	public function get_trending_sections() {
		global $wpdb;

		$cache_key = 'lists_trending_hub_data';
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$sections = array(
			'trending' => $wpdb->get_results( "
				SELECT e.*, i.trend_type, i.momentum_score
				FROM {$wpdb->prefix}charts_entries e
				LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = e.item_id AND i.entity_type = e.item_type
				WHERE e.item_type IN ('track','video')
				ORDER BY i.momentum_score DESC, e.rank_position ASC
				LIMIT 8
			" ),
			'rising' => $wpdb->get_results( "
				SELECT e.*, i.trend_type, i.velocity_score
				FROM {$wpdb->prefix}charts_entries e
				LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = e.item_id AND i.entity_type = e.item_type
				WHERE e.item_type IN ('track','video')
				ORDER BY e.movement_value DESC, i.velocity_score DESC
				LIMIT 8
			" ),
			'new_entries' => $wpdb->get_results( "
				SELECT e.*, i.trend_type
				FROM {$wpdb->prefix}charts_entries e
				LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = e.item_id AND i.entity_type = e.item_type
				WHERE e.item_type IN ('track','video') AND e.movement_direction IN ('new','re-entry')
				ORDER BY e.rank_position ASC
				LIMIT 8
			" ),
			'editor_picks' => $this->get_editor_picks(),
		);

		set_transient( $cache_key, $sections, 15 * MINUTE_IN_SECONDS );
		return $sections;
	}

	/**
	 * Resolve editor picks from saved slugs.
	 */
	public function get_editor_picks() {
		global $wpdb;

		$slugs = array_filter( array_map( 'sanitize_title', explode( ',', (string) get_option( 'lists_editor_pick_slugs', '' ) ) ) );
		if ( empty( $slugs ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}charts_definitions WHERE slug IN ($placeholders) ORDER BY menu_order ASC",
				...$slugs
			)
		);
	}

	/**
	 * Get a share-card url for the current entity.
	 */
	public function get_share_card_url( $type, $slug ) {
		return home_url( '/lists/share/' . rawurlencode( $type ) . '/' . rawurlencode( $slug ) . '/' );
	}

	/**
	 * Render a lightweight SVG share card.
	 */
	public function render_share_card( $type, $slug ) {
		$payload = $this->get_share_card_payload( $type, $slug );
		if ( empty( $payload ) ) {
			status_header( 404 );
			exit;
		}

		$title = esc_html( $payload['title'] );
		$subtitle = esc_html( $payload['subtitle'] );
		$badge = esc_html( strtoupper( $payload['badge'] ) );
		$rank = esc_html( $payload['rank'] );

		nocache_headers();
		header( 'Content-Type: image/svg+xml; charset=utf-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">';
		echo '<defs><linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#050816"/><stop offset="100%" stop-color="#171d32"/></linearGradient></defs>';
		echo '<rect width="1200" height="630" fill="url(#bg)"/>';
		echo '<rect x="48" y="48" width="1104" height="534" rx="28" fill="rgba(255,255,255,0.04)" stroke="rgba(255,255,255,0.12)"/>';
		echo '<text x="80" y="120" fill="#f8fafc" font-size="28" font-family="Arial, sans-serif" font-weight="700">Kontentainment Lists</text>';
		if ( $rank !== '' ) {
			echo '<text x="80" y="250" fill="#f59e0b" font-size="120" font-family="Arial, sans-serif" font-weight="900">#' . $rank . '</text>';
		}
		echo '<text x="80" y="350" fill="#ffffff" font-size="58" font-family="Arial, sans-serif" font-weight="900">' . $title . '</text>';
		echo '<text x="80" y="410" fill="#cbd5e1" font-size="34" font-family="Arial, sans-serif" font-weight="600">' . $subtitle . '</text>';
		echo '<rect x="80" y="470" width="220" height="56" rx="28" fill="#ef4444"/>';
		echo '<text x="112" y="507" fill="#ffffff" font-size="24" font-family="Arial, sans-serif" font-weight="800">' . $badge . '</text>';
		echo '</svg>';
		exit;
	}

	/**
	 * Social caption generator.
	 */
	public function get_share_caption( $context ) {
		if ( empty( $context['type'] ) ) {
			return '';
		}

		if ( $context['type'] === 'list' && ! empty( $context['featured'] ) ) {
			return sprintf(
				'#1 this week %1$s %2$s by %3$s leads %4$s.',
				"\xF0\x9F\x94\xA5",
				$context['featured']->track_name,
				$context['featured']->artist_names,
				$context['definition']->title
			);
		}

		if ( $context['type'] === 'track' ) {
			return sprintf( 'Now trending: %s by %s on Kontentainment Lists.', $context['item']->title, $context['item']->artist_names ?? 'featured artists' );
		}

		if ( $context['type'] === 'artist' ) {
			return sprintf( '%s is making major moves across this week\'s music lists.', $context['artist']->display_name );
		}

		return 'Trending now on Kontentainment Lists.';
	}

	private function get_index_context() {
		return array(
			'type'        => 'index',
			'title'       => 'Weekly Music Lists, Trending Artists & Track Rankings',
			'description' => 'Explore weekly music lists, trending tracks, rising artists, and editorial highlights across the Egyptian and regional music market.',
			'canonical'   => home_url( '/lists/' ),
			'image'       => $this->get_share_card_url( 'list', 'homepage' ),
			'schema'      => array(),
		);
	}

	private function get_trending_context() {
		return array(
			'type'        => 'trending',
			'title'       => 'Trending Music Lists: Rising Tracks, New Entries & Editorial Picks',
			'description' => 'Track what is trending now across the biggest music lists, from rising tracks and breakout entries to editor picks and fast-moving records.',
			'canonical'   => home_url( '/lists/trending/' ),
			'image'       => $this->get_share_card_url( 'list', 'trending' ),
			'schema'      => array(),
		);
	}

	private function get_list_context() {
		global $wpdb;

		$definition_id = (int) get_query_var( 'charts_definition_id' );
		$manager = new \Charts\Admin\SourceManager();
		$definition = $definition_id ? $manager->get_definition( $definition_id ) : null;

		if ( ! $definition ) {
			$type      = get_query_var( 'charts_type' );
			$country   = get_query_var( 'charts_country' ) ?: 'eg';
			$frequency = get_query_var( 'charts_frequency' ) ?: 'weekly';

			$definition = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}charts_definitions WHERE chart_type = %s AND country_code = %s AND frequency = %s LIMIT 1",
					$type,
					$country,
					$frequency
				)
			);
		}

		if ( ! $definition ) {
			return null;
		}

		$sources = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}charts_sources WHERE chart_type = %s AND country_code = %s AND frequency = %s AND is_active = 1",
				$definition->chart_type,
				$definition->country_code,
				$definition->frequency
			)
		);

		if ( empty( $sources ) ) {
			return null;
		}

		$placeholders = implode( ',', array_fill( 0, count( $sources ), '%d' ) );
		$period = $wpdb->get_row( $wpdb->prepare( "SELECT p.* FROM {$wpdb->prefix}charts_periods p JOIN {$wpdb->prefix}charts_entries e ON e.period_id = p.id WHERE e.source_id IN ($placeholders) ORDER BY p.period_start DESC LIMIT 1", ...$sources ) );
		if ( ! $period ) {
			return null;
		}

		$entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_entries WHERE source_id IN ($placeholders) AND period_id = %d ORDER BY rank_position ASC", ...$sources, $period->id ) );
		$featured = $entries[0] ?? null;

		$title = sprintf( '%s This Week (Updated %s)', $definition->title, ucfirst( $definition->frequency ) );
		$description = sprintf(
			'Explore %1$s for the week of %2$s, including the #1 track, biggest climbers, new entries, and movement across this week\'s music list.',
			$definition->title,
			date_i18n( 'F j, Y', strtotime( $period->period_start ) )
		);

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'ItemList',
			'name'     => $definition->title,
			'description' => $description,
			'url'      => home_url( '/lists/' . $definition->slug . '/' ),
			'numberOfItems' => count( $entries ),
			'itemListElement' => array_map(
				static function ( $entry ) {
					return array(
						'@type'    => 'ListItem',
						'position' => (int) $entry->rank_position,
						'name'     => $entry->track_name,
					);
				},
				array_slice( $entries, 0, 20 )
			),
		);

		return array(
			'type'       => 'list',
			'title'      => $title,
			'description'=> $description,
			'canonical'  => home_url( '/lists/' . $definition->slug . '/' ),
			'image'      => $this->get_share_card_url( 'list', $definition->slug ),
			'schema'     => array( $schema ),
			'definition' => $definition,
			'period'     => $period,
			'featured'   => $featured,
			'entries'    => $entries,
		);
	}

	private function get_artist_context() {
		global $wpdb;

		$slug = get_query_var( 'charts_artist_slug' );
		if ( ! $slug ) {
			return null;
		}

		$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s", $slug ) );
		if ( ! $artist ) {
			return null;
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Person',
			'name'     => $artist->display_name,
			'url'      => home_url( '/lists/artist/' . $artist->slug . '/' ),
			'image'    => $artist->image ?: '',
		);

		return array(
			'type'       => 'artist',
			'title'      => $artist->display_name . ' Charts, Rankings & Music List Profile',
			'description'=> 'Explore rankings, chart appearances, and editorial intelligence for ' . $artist->display_name . '.',
			'canonical'  => home_url( '/lists/artist/' . $artist->slug . '/' ),
			'image'      => $this->get_share_card_url( 'artist', $artist->slug ),
			'schema'     => array( $schema ),
			'artist'     => $artist,
		);
	}

	private function get_item_context() {
		global $wpdb;

		$type = get_query_var( 'charts_item_type' );
		$slug = get_query_var( 'charts_item_slug' );
		if ( ! $type || ! $slug ) {
			return null;
		}

		$table = $type === 'video' ? 'charts_videos' : 'charts_tracks';
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$table} WHERE slug = %s", $slug ) );
		if ( ! $item ) {
			return null;
		}

		$artist_name = '';
		if ( ! empty( $item->primary_artist_id ) ) {
			$artist_name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item->primary_artist_id ) );
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'MusicRecording',
			'name'     => $item->title,
			'byArtist' => $artist_name ? array(
				'@type' => 'Person',
				'name'  => $artist_name,
			) : null,
			'url'      => home_url( '/lists/' . $type . '/' . $item->slug . '/' ),
		);

		return array(
			'type'       => 'track',
			'title'      => $item->title . ( $artist_name ? ' by ' . $artist_name : '' ) . ' Rankings, Trends & List History',
			'description'=> 'See trend movement, list appearances, and prediction signals for ' . $item->title . '.',
			'canonical'  => home_url( '/lists/' . $type . '/' . $item->slug . '/' ),
			'image'      => $this->get_share_card_url( 'track', $item->slug ),
			'schema'     => array( array_filter( $schema ) ),
			'item'       => (object) array_merge( (array) $item, array( 'artist_names' => $artist_name ) ),
		);
	}

	private function get_share_card_payload( $type, $slug ) {
		global $wpdb;

		if ( in_array( $slug, array( 'homepage', 'trending' ), true ) ) {
			return array(
				'title'    => $slug === 'homepage' ? 'Weekly Music Lists' : 'Trending Now',
				'subtitle' => 'Editorial intelligence powered by weekly rankings',
				'badge'    => $slug === 'homepage' ? 'FEATURED' : 'TRENDING',
				'rank'     => '',
			);
		}

		if ( $type === 'list' ) {
			$definition = ( new \Charts\Admin\SourceManager() )->get_definition_by_slug( $slug );
			if ( ! $definition ) {
				return null;
			}

			$entry = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT e.* FROM {$wpdb->prefix}charts_entries e
					JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id
					WHERE s.chart_type = %s AND s.country_code = %s
					ORDER BY e.created_at DESC, e.rank_position ASC LIMIT 1",
					$definition->chart_type,
					$definition->country_code
				)
			);

			return array(
				'title'    => $definition->title,
				'subtitle' => $entry ? $entry->track_name . ' • ' . $entry->artist_names : 'Updated editorial rankings',
				'badge'    => $entry ? ( $entry->movement_direction ?: 'LIST' ) : 'LIST',
				'rank'     => $entry ? (string) $entry->rank_position : '',
			);
		}

		if ( $type === 'track' ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_tracks WHERE slug = %s", $slug ) );
			if ( ! $item ) {
				return null;
			}

			$artist = $item->primary_artist_id ? $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE id = %d", $item->primary_artist_id ) ) : '';
			return array(
				'title'    => $item->title,
				'subtitle' => $artist ?: 'Track intelligence profile',
				'badge'    => 'TRENDING',
				'rank'     => '',
			);
		}

		if ( $type === 'artist' ) {
			$artist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}charts_artists WHERE slug = %s", $slug ) );
			if ( ! $artist ) {
				return null;
			}

			return array(
				'title'    => $artist->display_name,
				'subtitle' => 'Artist intelligence profile',
				'badge'    => 'ARTIST',
				'rank'     => '',
			);
		}

		return null;
	}
}
