<?php
/**
 * Admin View: List Definitions List
 */
$manager = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions();
?>

<div class="charts-admin-wrap">
	<header class="charts-admin-header">
		<div class="charts-admin-title-group">
			<h1 class="charts-admin-title"><?php _e( 'Lists Intelligence', 'kontentainment-lists' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Manage your dynamic list products and definitions.', 'kontentainment-lists' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo admin_url( 'admin.php?page=charts-definitions&action=edit' ); ?>" class="charts-btn charts-btn-primary">
				<span class="dashicons dashicons-plus"></span>
				<?php _e( 'Create New List', 'kontentainment-lists' ); ?>
			</a>
		</div>
	</header>

	<?php settings_errors( 'kontentainment-lists' ); ?>

	<div class="charts-bento-grid">
		<?php if ( empty( $definitions ) ) : ?>
			<div class="charts-bento-card full-width empty-state">
				<div class="empty-icon"><span class="dashicons dashicons-chart-bar"></span></div>
				<h3><?php _e( 'No Lists Defined', 'kontentainment-lists' ); ?></h3>
				<p><?php _e( 'Start by creating your first music or video list definition.', 'kontentainment-lists' ); ?></p>
				<a href="<?php echo admin_url( 'admin.php?page=charts-definitions&action=edit' ); ?>" class="charts-btn charts-btn-outline">
					<?php _e( 'Create My First List', 'kontentainment-lists' ); ?>
				</a>
			</div>
		<?php else : ?>
			<?php foreach ( $definitions as $def ) : 
				$platform_label = ($def->platform === 'all') ? __('Mixed platforms', 'kontentainment-lists') : ucfirst($def->platform);
				$status_class = $def->is_public ? 'status-public' : 'status-private';
				$display_settings = ! empty( $def->display_settings_json ) ? json_decode( $def->display_settings_json, true ) : array();
				$preset_label = '';
				if ( ! empty( $display_settings['soundcharts_preset'] ) ) {
					$preset = \Charts\Services\SoundchartsPresetRegistry::get( $display_settings['soundcharts_preset'] );
					$preset_label = $preset['label'] ?? '';
				}
				global $wpdb;
				$entry_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries WHERE chart_type = %s AND country_code = %s", $def->chart_type, $def->country_code ) );
			?>
				<div class="charts-bento-card chart-definition-card">
					<div class="card-header">
						<span class="badge <?php echo $status_class; ?>">
							<?php echo $def->is_public ? __('Public', 'kontentainment-lists') : __('Draft', 'kontentainment-lists'); ?>
						</span>
						<?php if ($def->is_featured) : ?>
							<span class="badge badge-featured"><?php _e( 'Featured', 'kontentainment-lists' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="card-body">
						<h3 class="card-title"><?php echo esc_html( $def->title ); ?></h3>
						<div class="card-meta">
							<span class="meta-item">
								<span class="dashicons dashicons-location"></span>
								<?php echo strtoupper($def->country_code); ?>
							</span>
							<span class="meta-item">
								<span class="dashicons dashicons-calendar-alt"></span>
								<?php echo ucfirst($def->frequency); ?>
							</span>
							<span class="meta-item">
								<span class="dashicons dashicons-tag"></span>
								<?php echo ucfirst($def->item_type); ?>
							</span>
							<?php if ( $preset_label ) : ?>
								<span class="meta-item">
									<span class="dashicons dashicons-database-import"></span>
									<?php echo esc_html( $preset_label ); ?>
								</span>
							<?php endif; ?>
						</div>
						<p class="card-summary"><?php echo esc_html( wp_trim_words($def->chart_summary, 15) ?: __('No summary provided.', 'kontentainment-lists') ); ?></p>
						<p class="card-summary" style="margin-top:8px; color:#64748b;"><?php echo esc_html( sprintf( __( '%d stored list entries', 'kontentainment-lists' ), $entry_count ) ); ?></p>
						<?php if ( ! empty( $def->auto_sync_enabled ) ) : ?>
							<p class="card-summary" style="margin-top:8px; color:#0f172a; font-weight:700;">
								<?php echo esc_html__( 'Auto Sync Enabled', 'kontentainment-lists' ); ?>
								<?php if ( ! empty( $def->next_sync_at ) ) : ?>
									<br><span style="font-weight:500; color:#64748b;"><?php echo esc_html__( 'Next sync:', 'kontentainment-lists' ) . ' ' . esc_html( $def->next_sync_at ); ?></span>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="card-footer">
						<div class="card-slug">/lists/<?php echo esc_html($def->slug); ?></div>
						<div class="card-actions">
							<?php if ( ! empty( $def->auto_sync_enabled ) ) : ?>
								<button type="button" class="action-icon charts-run-now" data-chart-id="<?php echo (int) $def->id; ?>" title="<?php _e('Run Now', 'kontentainment-lists'); ?>">
									<span class="dashicons dashicons-update"></span>
								</button>
							<?php endif; ?>
							<a href="<?php echo admin_url( 'admin.php?page=charts-definitions&action=edit&id=' . $def->id ); ?>" class="action-icon" title="<?php _e('Edit', 'kontentainment-lists'); ?>">
								<span class="dashicons dashicons-edit"></span>
							</a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this list?', 'kontentainment-lists'); ?>');">
								<?php wp_nonce_field( 'charts_admin_action' ); ?>
								<input type="hidden" name="charts_action" value="delete_definition">
								<input type="hidden" name="id" value="<?php echo $def->id; ?>">
								<button type="submit" class="action-icon delete" title="<?php _e('Delete', 'kontentainment-lists'); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</form>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
