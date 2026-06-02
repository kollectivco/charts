<?php
/**
 * Deduplication & Merge Center View
 */
global $wpdb;

$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'artists';
$artists_table = $wpdb->prefix . 'charts_artists';
$tracks_table  = $wpdb->prefix . 'charts_tracks';

// Fetch duplicates based on tab
$duplicates = array();

if ( $tab === 'artists' ) {
	$groups = $wpdb->get_results("
		SELECT normalized_name, COUNT(*) as count 
		FROM $artists_table 
		GROUP BY normalized_name 
		HAVING COUNT(*) > 1 
		ORDER BY count DESC 
		LIMIT 50
	");
	
	foreach ( $groups as $group ) {
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $artists_table WHERE normalized_name = %s ORDER BY id ASC", $group->normalized_name ) );
		if ( count($items) > 1 ) {
			$duplicates[] = array(
				'label' => $items[0]->display_name,
				'items' => $items
			);
		}
	}
} else {
	$groups = $wpdb->get_results("
		SELECT normalized_title, primary_artist_id, COUNT(*) as count 
		FROM $tracks_table 
		GROUP BY normalized_title, primary_artist_id 
		HAVING COUNT(*) > 1 
		ORDER BY count DESC 
		LIMIT 50
	");
	
	foreach ( $groups as $group ) {
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, a.display_name as artist_name FROM $tracks_table t LEFT JOIN $artists_table a ON a.id = t.primary_artist_id WHERE t.normalized_title = %s AND t.primary_artist_id = %d ORDER BY t.id ASC", $group->normalized_title, $group->primary_artist_id ) );
		if ( count($items) > 1 ) {
			$artist_name = $items[0]->artist_name ?? 'Unknown Artist';
			$duplicates[] = array(
				'label' => $items[0]->title . ' — ' . $artist_name,
				'items' => $items
			);
		}
	}
}

?>
<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Merge Center', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Identify and merge duplicated entities to consolidate chart history and statistics.', 'charts' ); ?></p>
		</div>
	</header>

	<div style="background: #fff; border-radius: 12px; box-shadow: var(--k-shadow-sm); overflow: hidden; margin-top: 24px;">
		<div style="display: flex; border-bottom: 1px solid #eee;">
			<a href="<?php echo add_query_arg('tab', 'artists'); ?>" style="padding: 16px 24px; text-decoration: none; color: <?php echo $tab === 'artists' ? '#6366f1' : '#6b7280'; ?>; font-weight: 600; border-bottom: 2px solid <?php echo $tab === 'artists' ? '#6366f1' : 'transparent'; ?>;">
				<?php _e( 'Duplicate Artists', 'charts' ); ?>
			</a>
			<a href="<?php echo add_query_arg('tab', 'tracks'); ?>" style="padding: 16px 24px; text-decoration: none; color: <?php echo $tab === 'tracks' ? '#6366f1' : '#6b7280'; ?>; font-weight: 600; border-bottom: 2px solid <?php echo $tab === 'tracks' ? '#6366f1' : 'transparent'; ?>;">
				<?php _e( 'Duplicate Tracks', 'charts' ); ?>
			</a>
		</div>

		<div style="padding: 24px;">
			<?php if ( empty( $duplicates ) ) : ?>
				<div style="text-align: center; padding: 60px; color: #6b7280;">
					<span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #22c55e;"></span>
					<h3 style="margin-top: 20px;"><?php _e( 'No duplicates found!', 'charts' ); ?></h3>
					<p><?php _e( 'Your database looks clean.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<p style="margin-bottom: 20px; color: #6b7280;">
					<?php printf( __( 'Found %d duplicated groups. Please select the primary (master) item in each group. All other items will be merged into it and deleted.', 'charts' ), count( $duplicates ) ); ?>
				</p>

				<div style="display: flex; flex-direction: column; gap: 20px;">
					<?php foreach ( $duplicates as $index => $group ) : ?>
						<div class="merge-group-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; background: #fafafa;">
							<h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;">
								<span class="dashicons dashicons-warning" style="color: #f59e0b; margin-right: 8px;"></span>
								<?php echo esc_html( $group['label'] ); ?>
							</h3>
							
							<form class="merge-form" style="display: flex; flex-direction: column; gap: 10px;">
								<?php wp_nonce_field( 'charts_admin_action' ); ?>
								<input type="hidden" name="action" value="charts_process_merge">
								<input type="hidden" name="type" value="<?php echo esc_attr( $tab ); ?>">
								
								<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
									<?php foreach ( $group['items'] as $item ) : ?>
										<label style="display: flex; gap: 15px; padding: 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; align-items: flex-start; transition: border-color 0.2s;">
											<input type="radio" name="master_id" value="<?php echo (int) $item->id; ?>" required style="margin-top: 5px;">
											<input type="hidden" name="duplicate_ids[]" value="<?php echo (int) $item->id; ?>">
											
											<div style="flex: 1;">
												<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
													<?php 
													$img = $tab === 'artists' ? ($item->image ?? '') : ($item->cover_image ?? '');
													if ( $img ) : ?>
														<img src="<?php echo esc_url( $img ); ?>" style="width: 40px; height: 40px; border-radius: <?php echo $tab === 'artists' ? '50%' : '4px'; ?>; object-fit: cover;">
													<?php else : ?>
														<div style="width: 40px; height: 40px; border-radius: <?php echo $tab === 'artists' ? '50%' : '4px'; ?>; background: #eee; display: flex; align-items: center; justify-content: center; color: #999;">
															<span class="dashicons dashicons-format-image"></span>
														</div>
													<?php endif; ?>
													<div>
														<div style="font-weight: bold;"><?php echo esc_html( $tab === 'artists' ? $item->display_name : $item->title ); ?></div>
														<div style="font-size: 11px; color: #9ca3af;">ID: <?php echo (int) $item->id; ?></div>
													</div>
												</div>
												
												<div style="font-size: 12px; color: #666; display: flex; flex-direction: column; gap: 4px;">
													<?php if ( $tab === 'artists' && !empty($item->display_name_en) ) : ?>
														<div><strong>EN:</strong> <?php echo esc_html( $item->display_name_en ); ?></div>
													<?php endif; ?>
													<?php if ( $tab === 'tracks' && !empty($item->title_en) ) : ?>
														<div><strong>EN:</strong> <?php echo esc_html( $item->title_en ); ?></div>
													<?php endif; ?>
													<?php if ( !empty($item->spotify_id) ) : ?>
														<div style="color: #1DB954;"><span class="dashicons dashicons-spotify" style="font-size: 14px; width: 14px; height: 14px;"></span> Linked</div>
													<?php endif; ?>
													<?php if ( !empty($item->youtube_id) ) : ?>
														<div style="color: #FF0000;"><span class="dashicons dashicons-youtube" style="font-size: 14px; width: 14px; height: 14px;"></span> Linked</div>
													<?php endif; ?>
												</div>
											</div>
										</label>
									<?php endforeach; ?>
								</div>

								<div style="margin-top: 15px; text-align: right;">
									<button type="submit" class="charts-btn-primary merge-submit-btn"><?php _e( 'Merge into Selected', 'charts' ); ?></button>
									<span class="merge-status" style="margin-left: 10px; font-size: 13px; font-weight: 500;"></span>
								</div>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
.merge-form input[type="radio"]:checked + input[type="hidden"] + div {
	opacity: 1;
}
.merge-form input[type="radio"]:checked {
	accent-color: #6366f1;
}
.merge-form label:has(input[type="radio"]:checked) {
	border-color: #6366f1;
	box-shadow: 0 0 0 1px #6366f1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const forms = document.querySelectorAll('.merge-form');
	
	forms.forEach(form => {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const submitBtn = form.querySelector('.merge-submit-btn');
			const statusMsg = form.querySelector('.merge-status');
			
			const formData = new FormData(form);
			
			if (!confirm('<?php _e('Are you sure you want to merge these items? All unselected items will be permanently deleted.', 'charts'); ?>')) {
				return;
			}
			
			submitBtn.disabled = true;
			submitBtn.innerText = '<?php _e('Merging...', 'charts'); ?>';
			statusMsg.innerText = '';
			
			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(res => res.json())
			.then(res => {
				if (res.success) {
					statusMsg.style.color = '#22c55e';
					statusMsg.innerText = res.data.message;
					submitBtn.style.display = 'none';
					
					// Visually hide the group
					setTimeout(() => {
						form.closest('.merge-group-card').style.opacity = '0.5';
						form.closest('.merge-group-card').style.pointerEvents = 'none';
					}, 1000);
				} else {
					statusMsg.style.color = '#ef4444';
					statusMsg.innerText = res.data.message || 'Error occurred';
					submitBtn.disabled = false;
					submitBtn.innerText = '<?php _e('Merge into Selected', 'charts'); ?>';
				}
			})
			.catch(err => {
				statusMsg.style.color = '#ef4444';
				statusMsg.innerText = 'Network error.';
				submitBtn.disabled = false;
				submitBtn.innerText = '<?php _e('Merge into Selected', 'charts'); ?>';
			});
		});
	});
});
</script>
