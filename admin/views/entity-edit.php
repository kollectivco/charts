<?php
/** Create or edit an artist, track, or clip record. */
global $wpdb;
$page = sanitize_key( wp_unslash( $_GET['page'] ?? 'charts-artists' ) );
$type = sanitize_key( wp_unslash( $_GET['type'] ?? ( $page === 'charts-tracks' ? 'track' : ( $page === 'charts-clips' ? 'video' : ( $page === 'charts-albums' ? 'album' : 'artist' ) ) ) ) );
$types = array( 'artist' => 'artists', 'track' => 'tracks', 'video' => 'videos', 'album' => 'albums' );
if ( ! isset( $types[ $type ] ) ) $type = 'artist';
$page = $type === 'artist' ? 'charts-artists' : ( $type === 'track' ? 'charts-tracks' : ( $type === 'album' ? 'charts-albums' : 'charts-clips' ) );
$id = absint( $_GET['id'] ?? 0 );
$table = $wpdb->prefix . 'charts_' . $types[ $type ];
$entity = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) ) : null;
$artists = $type !== 'artist' ? $wpdb->get_results( "SELECT id, display_name FROM {$wpdb->prefix}charts_artists ORDER BY display_name ASC LIMIT 5000" ) : array();
$tracks = $type === 'video' ? $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}charts_tracks ORDER BY title ASC LIMIT 5000" ) : array();
$related_clips = $type === 'track' ? $wpdb->get_results( $wpdb->prepare(
	"SELECT id, title, related_track_id FROM {$wpdb->prefix}charts_videos ORDER BY (related_track_id = %d) DESC, title ASC LIMIT 5000",
	$id
) ) : array();
$kind = $type === 'artist' ? __( 'Artist', 'charts' ) : ( $type === 'track' ? __( 'Track', 'charts' ) : ( $type === 'album' ? __( 'Album', 'charts' ) : __( 'Clip', 'charts' ) ) );
$name = $entity ? ( $type === 'artist' ? $entity->display_name : $entity->title ) : '';
$image = $entity ? ( $type === 'artist' ? $entity->image : ( ( $type === 'track' || $type === 'album' ) ? $entity->cover_image : $entity->thumbnail ) ) : '';
?>
<div class="wrap charts-admin-wrap premium-light entity-edit-page">
	<header class="charts-admin-header"><div><p class="entity-edit-eyebrow"><?php esc_html_e( 'CANONICAL LIBRARY', 'charts' ); ?></p><h1 class="charts-admin-title"><?php echo esc_html( $id ? sprintf( __( 'Edit %s', 'charts' ), $kind ) : sprintf( __( 'Add %s', 'charts' ), $kind ) ); ?></h1><p class="charts-admin-subtitle"><?php esc_html_e( 'Update this record as it appears in charts and entity matching.', 'charts' ); ?></p></div><a class="charts-btn-back" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page ) ); ?>">&larr; <?php esc_html_e( 'Back to library', 'charts' ); ?></a></header>
	<?php if ( $id && ! $entity ) : ?><div class="notice notice-error"><p><?php esc_html_e( 'This record could not be found.', 'charts' ); ?></p></div><?php else : ?>
	<form method="post" class="entity-edit-card">
		<?php wp_nonce_field( 'charts_admin_action' ); ?><input type="hidden" name="charts_action" value="save_entity"><input type="hidden" name="entity_type" value="<?php echo esc_attr( $type ); ?>"><input type="hidden" name="entity_id" value="<?php echo (int) $id; ?>">
		
		<div class="entity-edit-card-head">
			<div class="dashicons <?php echo esc_attr( $type === 'artist' ? 'dashicons-groups' : ( $type === 'track' ? 'dashicons-playlist-audio' : ( $type === 'album' ? 'dashicons-album' : 'dashicons-video-alt3' ) ) ); ?>"></div>
			<div>
				<h2><?php echo esc_html( $kind ); ?> <?php esc_html_e( 'details', 'charts' ); ?></h2>
				<p><?php echo $id ? esc_html( sprintf( __( 'Record ID #%d', 'charts' ), $id ) ) : esc_html__( 'New library record', 'charts' ); ?></p>
			</div>
		</div>
		<div class="entity-edit-fields">
			<div class="entity-edit-field wide"><label for="entity-name"><?php echo esc_html( $type === 'artist' ? __( 'Arabic display name', 'charts' ) : ( $type === 'album' ? __( 'Arabic album title', 'charts' ) : __( 'Arabic title', 'charts' ) ) ); ?></label><input id="entity-name" name="entity_name" type="text" value="<?php echo esc_attr( $name ); ?>" required></div>
			<div class="entity-edit-field"><label for="entity-slug"><?php esc_html_e( 'English slug', 'charts' ); ?></label><input id="entity-slug" name="slug" type="text" value="<?php echo esc_attr( $entity->slug ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Generated from the name if blank', 'charts' ); ?>" dir="ltr"></div>
			<?php if ( $type === 'artist' ) : ?>
				<div class="entity-edit-field"><label for="entity-name-en"><?php esc_html_e( 'English artist name (optional)', 'charts' ); ?></label><input id="entity-name-en" name="name_en" type="text" value="<?php echo esc_attr( $entity->display_name_en ?? '' ); ?>" dir="ltr"></div>
				<div class="entity-edit-field"><label for="entity-spotify"><?php esc_html_e( 'Spotify ID or URL', 'charts' ); ?></label><input id="entity-spotify" name="spotify_id" type="text" value="<?php echo esc_attr( $entity->spotify_id ?? '' ); ?>" placeholder="e.g. 0cC0RG32ZJeRWEUoOQFXO8" dir="ltr"></div>
			<?php elseif ( $type === 'album' ) : ?>
				<div class="entity-edit-field"><label for="entity-title-en"><?php esc_html_e( 'English album title (optional)', 'charts' ); ?></label><input id="entity-title-en" name="name_en" type="text" value="<?php echo esc_attr( $entity->title_en ?? '' ); ?>" dir="ltr"></div>
				<div class="entity-edit-field"><label for="entity-artist"><?php esc_html_e( 'Primary artist (optional)', 'charts' ); ?></label><select id="entity-artist" name="primary_artist_id"><option value=""><?php esc_html_e( 'Select artist (optional)', 'charts' ); ?></option><?php foreach ( $artists as $artist ) : ?><option value="<?php echo (int) $artist->id; ?>" <?php selected( (int) ( $entity->primary_artist_id ?? 0 ), (int) $artist->id ); ?>><?php echo esc_html( $artist->display_name ); ?> (#<?php echo (int) $artist->id; ?>)</option><?php endforeach; ?></select></div>
				<div class="entity-edit-field"><label for="entity-spotify"><?php esc_html_e( 'Spotify Album ID or URL', 'charts' ); ?></label><input id="entity-spotify" name="spotify_id" type="text" value="<?php echo esc_attr( $entity->spotify_id ?? '' ); ?>" placeholder="https://open.spotify.com/album/... or ID" dir="ltr"></div>
				<div class="entity-edit-field"><label for="entity-release-date"><?php esc_html_e( 'Release date (YYYY-MM-DD)', 'charts' ); ?></label><input id="entity-release-date" name="release_date" type="date" value="<?php echo esc_attr( $entity->release_date ?? '' ); ?>" dir="ltr"></div>
				<?php 
				$album_meta = ! empty( $entity->metadata_json ) ? json_decode( $entity->metadata_json, true ) : array();
				if ( ! empty( $album_meta['tracks'] ) ) : ?>
					<div class="entity-edit-field wide">
						<label><?php esc_html_e( 'Album Tracklist (Synced from Spotify)', 'charts' ); ?> (<?php echo count( $album_meta['tracks'] ); ?> <?php esc_html_e( 'tracks', 'charts' ); ?>)</label>
						<div style="background:#f1f5f9; border-radius:8px; padding:14px 18px; max-height:220px; overflow-y:auto; font-size:13px; border:1px solid #e2e8f0;">
							<ol style="margin:0; padding-left:20px;">
								<?php foreach ( $album_meta['tracks'] as $t ) : ?>
									<li style="margin-bottom:6px; color:#1e293b;">
										<strong><?php echo esc_html( $t['name'] ?? '' ); ?></strong> 
										<span style="color:#64748b; font-size:11px; margin-left:8px;"><?php echo ! empty( $t['duration_ms'] ) ? gmdate( 'i:s', intval( $t['duration_ms'] / 1000 ) ) : ''; ?></span>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="entity-edit-field"><label for="entity-artist"><?php esc_html_e( 'Primary artist', 'charts' ); ?></label><select id="entity-artist" name="primary_artist_id" required><option value=""><?php esc_html_e( 'Select artist', 'charts' ); ?></option><?php foreach ( $artists as $artist ) : ?><option value="<?php echo (int) $artist->id; ?>" <?php selected( (int) ( $entity->primary_artist_id ?? 0 ), (int) $artist->id ); ?>><?php echo esc_html( $artist->display_name ); ?> (#<?php echo (int) $artist->id; ?>)</option><?php endforeach; ?></select></div>
				<?php if ( $type === 'track' ) : ?><div class="entity-edit-field"><label for="entity-spotify"><?php esc_html_e( 'Spotify ID', 'charts' ); ?></label><input id="entity-spotify" name="spotify_id" type="text" value="<?php echo esc_attr( $entity->spotify_id ?? '' ); ?>" placeholder="e.g. 0cC0RG32ZJeRWEUoOQFXO8" dir="ltr"></div><?php endif; ?>
				<div class="entity-edit-field"><label for="entity-youtube"><?php esc_html_e( 'YouTube ID', 'charts' ); ?></label><input id="entity-youtube" name="youtube_id" type="text" value="<?php echo esc_attr( $entity->youtube_id ?? '' ); ?>" dir="ltr"></div>
				<?php if ( $type === 'track' ) : ?><div class="entity-edit-field wide"><label for="entity-related-clips"><?php esc_html_e( 'Related clips', 'charts' ); ?></label><select id="entity-related-clips" name="related_video_ids[]" multiple size="8" aria-describedby="entity-related-clips-help"><?php foreach ( $related_clips as $clip ) : ?><option value="<?php echo (int) $clip->id; ?>" <?php selected( (int) $clip->related_track_id, $id ); ?>><?php echo esc_html( $clip->title ); ?> (#<?php echo (int) $clip->id; ?>)<?php if ( $clip->related_track_id && (int) $clip->related_track_id !== $id ) : ?> — <?php esc_html_e( 'linked to another track; selecting moves it here', 'charts' ); ?><?php endif; ?></option><?php endforeach; ?></select><small id="entity-related-clips-help"><?php esc_html_e( 'Hold Ctrl (Windows) or Command (Mac) to select multiple clips. Each clip can be linked to one track.', 'charts' ); ?></small></div><?php endif; ?>
				<?php if ( $type === 'video' ) : ?><div class="entity-edit-field"><label for="entity-video-url"><?php esc_html_e( 'Video URL', 'charts' ); ?></label><input id="entity-video-url" name="video_url" type="url" value="<?php echo esc_attr( $entity->video_url ?? '' ); ?>" dir="ltr"></div><div class="entity-edit-field"><label for="entity-related-track"><?php esc_html_e( 'Related track (optional)', 'charts' ); ?></label><select id="entity-related-track" name="related_track_id"><option value="0"><?php esc_html_e( 'No related track', 'charts' ); ?></option><?php foreach ( $tracks as $track ) : ?><option value="<?php echo (int) $track->id; ?>" <?php selected( (int) ( $entity->related_track_id ?? 0 ), (int) $track->id ); ?>><?php echo esc_html( $track->title ); ?> (#<?php echo (int) $track->id; ?>)</option><?php endforeach; ?></select></div><?php endif; ?>
			<?php endif; ?>
			<div class="entity-edit-field wide"><label for="entity-image"><?php echo esc_html( $type === 'artist' ? __( 'Artist image URL', 'charts' ) : ( ( $type === 'track' || $type === 'album' ) ? __( 'Cover image URL', 'charts' ) : __( 'Thumbnail URL', 'charts' ) ) ); ?></label><input id="entity-image" name="image" type="url" value="<?php echo esc_attr( $image ); ?>" dir="ltr"><?php if ( $image ) : ?><img class="entity-edit-preview" src="<?php echo esc_url( $image ); ?>" alt=""><?php endif; ?></div>
		</div>
		
		<footer class="entity-edit-footer">
			<a class="charts-btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page ) ); ?>"><?php esc_html_e( 'Cancel', 'charts' ); ?></a>
			<button type="submit" class="charts-btn-primary"><span class="dashicons dashicons-saved" aria-hidden="true" style="margin-top:2px;"></span> <?php esc_html_e( 'Save changes', 'charts' ); ?></button>
		</footer>

	</form><?php endif; ?>
</div>

<style>
.entity-edit-page { max-width: 900px; margin: 20px auto; }
.entity-edit-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); overflow: hidden; margin-top: 24px; }
.entity-edit-card-head { background: #f8fafc; padding: 24px 32px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 16px; }
.entity-edit-card-head .dashicons { font-size: 28px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: #e0e7ff; color: #4f46e5; border-radius: 12px; }
.entity-edit-card-head h2 { margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; }
.entity-edit-card-head p { margin: 4px 0 0; font-size: 13px; color: #64748b; }
.entity-edit-fields { padding: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.entity-edit-field { display: flex; flex-direction: column; gap: 8px; }
.entity-edit-field.wide { grid-column: 1 / -1; }
.entity-edit-field label { font-size: 13px; font-weight: 600; color: #334155; }
.entity-edit-field input, .entity-edit-field select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; transition: all 0.2s; background: #f8fafc; }
.entity-edit-field input:focus, .entity-edit-field select:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); outline: none; }
.entity-edit-field select[multiple] { min-height: 180px; }
.entity-edit-field small { color: #64748b; font-size: 12px; }
.entity-edit-preview { width: 96px; height: 96px; border-radius: 12px; object-fit: cover; border: 1px solid #e2e8f0; margin-top: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.entity-edit-footer { padding: 20px 32px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
.charts-btn-primary { background: #0f172a; color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: background 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.charts-btn-primary:hover { background: #1e293b; color: #fff; }
.charts-btn-secondary { background: #fff; color: #475569; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; }
.charts-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
</style>
