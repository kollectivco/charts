<?php
/** Bulk workspace for matching clips to their canonical tracks. */
global $wpdb;

$clips_table = $wpdb->prefix . 'charts_videos';
$tracks_table = $wpdb->prefix . 'charts_tracks';
$artists_table = $wpdb->prefix . 'charts_artists';
$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$status = in_array( $_GET['status'] ?? '', array( 'all', 'linked', 'unlinked' ), true ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'unlinked';
$per_page = 50;
$current_page = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset = ( $current_page - 1 ) * $per_page;

$where = 'WHERE 1=1';
if ( $search !== '' ) {
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$where .= $wpdb->prepare( ' AND (v.title LIKE %s OR v.slug LIKE %s OR v.youtube_id LIKE %s OR t.title LIKE %s OR a.display_name LIKE %s OR a.display_name_en LIKE %s)', $like, $like, $like, $like, $like, $like );
}
if ( $status === 'linked' ) $where .= ' AND v.related_track_id IS NOT NULL AND v.related_track_id > 0';
if ( $status === 'unlinked' ) $where .= ' AND (v.related_track_id IS NULL OR v.related_track_id = 0)';

$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $clips_table v LEFT JOIN $tracks_table t ON t.id = v.related_track_id LEFT JOIN $artists_table a ON a.id = v.primary_artist_id $where" );
$num_pages = max( 1, (int) ceil( $total / $per_page ) );
$auto_match_count = \Charts\Admin\Bootstrap::count_auto_clip_track_matches();
if ( $current_page > $num_pages ) {
	$current_page = $num_pages;
	$offset = ( $current_page - 1 ) * $per_page;
}
$clips = $wpdb->get_results( "SELECT v.id, v.title, v.slug, v.youtube_id, v.related_track_id, t.title AS track_title, a.display_name AS artist_name FROM $clips_table v LEFT JOIN $tracks_table t ON t.id = v.related_track_id LEFT JOIN $artists_table a ON a.id = v.primary_artist_id $where ORDER BY v.title ASC LIMIT $per_page OFFSET $offset" );
$base_url = admin_url( 'admin.php?page=charts-clip-track-linker' );
?>
<div class="wrap charts-admin-wrap premium-light ctm-page">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Clip-Track Linking', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php esc_html_e( 'Automatically link confident matches, then review only the clips that still need attention.', 'charts' ); ?></p>
		</div>
		<a class="charts-btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=charts-clips' ) ); ?>">&larr; <?php esc_html_e( 'Back to Clips', 'charts' ); ?></a>
	</header>

	<div class="ctm-toolbar">
		<form method="get" class="ctm-filter-form">
			<input type="hidden" name="page" value="charts-clip-track-linker">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" class="charts-input ctm-search" placeholder="<?php esc_attr_e( 'Search clips, tracks, artists, or YouTube ID…', 'charts' ); ?>">
			<select name="status" class="charts-input" aria-label="<?php esc_attr_e( 'Link status', 'charts' ); ?>">
				<option value="all" <?php selected( $status, 'all' ); ?>><?php esc_html_e( 'All clips', 'charts' ); ?></option>
				<option value="unlinked" <?php selected( $status, 'unlinked' ); ?>><?php esc_html_e( 'Unlinked clips', 'charts' ); ?></option>
				<option value="linked" <?php selected( $status, 'linked' ); ?>><?php esc_html_e( 'Linked clips', 'charts' ); ?></option>
			</select>
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Search', 'charts' ); ?></button>
			<?php if ( $search || $status !== 'unlinked' ) : ?><a href="<?php echo esc_url( $base_url ); ?>" class="ctm-clear"><?php esc_html_e( 'Clear filters', 'charts' ); ?></a><?php endif; ?>
		</form>
		<div class="ctm-count">
			<?php if ( $total ) : ?><?php printf( esc_html__( 'Showing %1$d–%2$d of %3$d clips', 'charts' ), $offset + 1, min( $offset + $per_page, $total ), $total ); ?><?php else : ?><?php esc_html_e( 'Showing 0 of 0 clips', 'charts' ); ?><?php endif; ?>
		</div>
	</div>

	<div class="ctm-auto-match">
		<div>
			<h2><?php esc_html_e( 'Automatic matching', 'charts' ); ?></h2>
			<p><?php esc_html_e( 'One click links unlinked clips when a unique track matches by YouTube ID, or by exact normalized title and primary artist. Existing links and ambiguous matches are left untouched.', 'charts' ); ?></p>
		</div>
		<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Automatically link every clip with a unique match?', 'charts' ) ); ?>');">
			<?php wp_nonce_field( 'charts_admin_action' ); ?>
			<input type="hidden" name="charts_action" value="auto_link_clips_to_tracks">
			<button type="submit" class="button button-primary" <?php disabled( $auto_match_count < 1 ); ?>><?php printf( esc_html__( 'Auto-link %d confident matches', 'charts' ), $auto_match_count ); ?></button>
		</form>
	</div>

	<?php if ( $clips ) : ?>
	<form method="post" class="ctm-mapping-form">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_clip_track_mappings">
		<div class="ctm-table-wrap">
			<table class="widefat striped ctm-table">
				<thead><tr><th><?php esc_html_e( 'Clip', 'charts' ); ?></th><th><?php esc_html_e( 'Artist', 'charts' ); ?></th><th><?php esc_html_e( 'Track to link', 'charts' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $clips as $clip ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $clip->title ); ?></strong>
							<div class="ctm-meta"><?php echo esc_html( $clip->youtube_id ? 'YouTube: ' . $clip->youtube_id : $clip->slug ); ?> <span>#<?php echo (int) $clip->id; ?></span></div>
						</td>
						<td><?php echo esc_html( $clip->artist_name ?: '—' ); ?></td>
						<td>
							<div class="ctm-track-picker">
								<div class="ctm-track-input-row">
									<input type="search" class="charts-input ctm-track-search" value="<?php echo esc_attr( $clip->track_title ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Type a track title or artist…', 'charts' ); ?>" autocomplete="off" aria-label="<?php echo esc_attr( sprintf( __( 'Track for clip %s', 'charts' ), $clip->title ) ); ?>">
									<input type="hidden" name="clip_track_ids[<?php echo (int) $clip->id; ?>]" value="<?php echo (int) ( $clip->related_track_id ?? 0 ); ?>" class="ctm-track-id">
									<button type="button" class="button ctm-unlink"><?php esc_html_e( 'Unlink', 'charts' ); ?></button>
								</div>
								<div class="ctm-suggestions" role="listbox" hidden></div>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="ctm-savebar">
			<p><?php esc_html_e( 'Blank track fields will leave those clips unlinked. Save applies every row shown on this page.', 'charts' ); ?></p>
			<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save links for these clips', 'charts' ); ?></button>
		</div>
	</form>
	<?php else : ?>
		<div class="ctm-empty"><h2><?php esc_html_e( 'No clips found', 'charts' ); ?></h2><p><?php esc_html_e( 'Try changing your search or link-status filter.', 'charts' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $num_pages > 1 ) : ?>
		<nav class="ctm-pagination" aria-label="<?php esc_attr_e( 'Clip pages', 'charts' ); ?>">
			<?php if ( $current_page > 1 ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 's' => $search, 'status' => $status, 'paged' => $current_page - 1 ), $base_url ) ); ?>">&larr; <?php esc_html_e( 'Previous', 'charts' ); ?></a><?php endif; ?>
			<span><?php printf( esc_html__( 'Page %1$d of %2$d', 'charts' ), $current_page, $num_pages ); ?></span>
			<?php if ( $current_page < $num_pages ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( array( 's' => $search, 'status' => $status, 'paged' => $current_page + 1 ), $base_url ) ); ?>"><?php esc_html_e( 'Next', 'charts' ); ?> &rarr;</a><?php endif; ?>
		</nav>
	<?php endif; ?>
</div>

<style>
.ctm-page { max-width: 1320px; margin: 20px auto; }
.ctm-toolbar { margin: 22px 0 14px; padding: 16px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
.ctm-filter-form { display:flex; align-items:center; gap:10px; flex:1; flex-wrap:wrap; }
.ctm-search { flex:1 1 320px; min-width:240px; }
.ctm-count,.ctm-meta { color:#64748b; font-size:12px; }
.ctm-auto-match { margin:0 0 14px; padding:18px 20px; display:flex; align-items:center; justify-content:space-between; gap:18px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; }
.ctm-auto-match h2 { margin:0 0 5px; color:#166534; font-size:15px; }
.ctm-auto-match p { margin:0; max-width:850px; color:#475569; }
.ctm-table-wrap { overflow:visible; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
.ctm-table { border:0; border-radius:12px; }
.ctm-table th,.ctm-table td { padding:14px 16px; vertical-align:middle; }
.ctm-table th { white-space:nowrap; }
.ctm-meta { margin-top:5px; }
.ctm-meta span { color:#94a3b8; margin-left:6px; }
.ctm-track-picker { position:relative; max-width:720px; }
.ctm-track-input-row { display:flex; align-items:center; gap:8px; }
.ctm-track-search { flex:1; min-width:200px; }
.ctm-suggestions { position:absolute; z-index:10000; left:0; right:56px; top:calc(100% + 3px); max-height:260px; overflow:auto; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 12px 28px rgba(15,23,42,.16); }
.ctm-suggestion { display:block; width:100%; border:0; border-bottom:1px solid #f1f5f9; background:#fff; padding:10px 12px; text-align:left; cursor:pointer; }
.ctm-suggestion:hover,.ctm-suggestion:focus { background:#f8fafc; }
.ctm-suggestion strong,.ctm-suggestion small { display:block; }
.ctm-suggestion small { margin-top:3px; color:#64748b; }
.ctm-savebar { margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:16px; padding:14px 18px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
.ctm-savebar p { margin:0; color:#64748b; }
.ctm-pagination { display:flex; justify-content:center; align-items:center; gap:16px; margin:18px 0; }
.ctm-empty { margin-top:20px; padding:48px; text-align:center; background:#fff; border:1px solid #e2e8f0; border-radius:12px; }
@media (max-width:782px) { .ctm-auto-match { align-items:stretch; flex-direction:column; } .ctm-savebar { align-items:stretch; flex-direction:column; } .ctm-track-input-row { align-items:stretch; flex-direction:column; } .ctm-unlink { align-self:flex-start; } .ctm-suggestions { right:0; } }
</style>

<script>
(function() {
	const nonce = <?php echo wp_json_encode( wp_create_nonce( 'charts_admin_action' ) ); ?>;
	const endpoint = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const form = document.querySelector('.ctm-mapping-form');
	document.querySelectorAll('.ctm-track-picker').forEach(function(picker) {
		const input = picker.querySelector('.ctm-track-search');
		const idField = picker.querySelector('.ctm-track-id');
		const results = picker.querySelector('.ctm-suggestions');
		let timer;

		function closeResults() { results.hidden = true; results.replaceChildren(); }
		function chooseTrack(track) {
			input.value = track.title + ' (#' + track.id + ')';
			idField.value = track.id;
			closeResults();
		}

		input.addEventListener('input', function() {
			idField.value = '';
			window.clearTimeout(timer);
			const query = input.value.trim();
			if (query.length < 2) { closeResults(); return; }
			timer = window.setTimeout(function() {
				const data = new FormData();
				data.append('action', 'charts_search_entities');
				data.append('nonce', nonce);
				data.append('type', 'track');
				data.append('query', query);
				fetch(endpoint, { method:'POST', body:data, credentials:'same-origin' })
					.then(function(response) { return response.json(); })
					.then(function(response) {
						if (input.value.trim() !== query) return;
						results.replaceChildren();
						const matches = response && response.success && Array.isArray(response.data) ? response.data : [];
						matches.forEach(function(track) {
							const option = document.createElement('button');
							option.type = 'button';
							option.className = 'ctm-suggestion';
							const title = document.createElement('strong');
							title.textContent = track.title || '';
							const subtitle = document.createElement('small');
							subtitle.textContent = [track.subtitle || '', track.name_en || '', '#' + track.id].filter(Boolean).join(' · ');
							option.append(title, subtitle);
							option.addEventListener('click', function() { chooseTrack(track); });
							results.appendChild(option);
						});
						if (!matches.length) {
							const empty = document.createElement('div');
							empty.className = 'ctm-suggestion';
							empty.textContent = <?php echo wp_json_encode( __( 'No tracks found. Try another title or artist.', 'charts' ) ); ?>;
							results.appendChild(empty);
						}
						results.hidden = false;
					})
					.catch(closeResults);
			}, 250);
		});

		picker.querySelector('.ctm-unlink').addEventListener('click', function() {
			input.value = '';
			idField.value = '';
			closeResults();
		});
		document.addEventListener('click', function(event) { if (!picker.contains(event.target)) closeResults(); });
	});
	if (form) {
		form.addEventListener('submit', function(event) {
			const unfinished = Array.from(form.querySelectorAll('.ctm-track-picker')).find(function(picker) {
				return picker.querySelector('.ctm-track-search').value.trim() && !picker.querySelector('.ctm-track-id').value;
			});
			if (unfinished) {
				event.preventDefault();
				window.alert(<?php echo wp_json_encode( __( 'Choose a track from the search results, or clear the field before saving.', 'charts' ) ); ?>);
				unfinished.querySelector('.ctm-track-search').focus();
			}
		});
	}
})();
</script>
