<?php
/**
 * Admin View: Chart Definitions List
 */
$manager = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions();
?>

<div class="charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div>
			<h1 class="charts-admin-title"><?php _e( 'Charts Intelligence', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php _e( 'Manage your dynamic chart products and definitions.', 'charts' ); ?></p>
		</div>
		<div class="charts-admin-actions">
			<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'definitions', array( 'action' => 'edit' ) ); ?>" class="charts-btn-create">
				<span class="dashicons dashicons-plus" style="margin-right:8px;"></span>
				<?php _e( 'Create New Chart', 'charts' ); ?>
			</a>
		</div>
	</header>


	<div class="charts-bento-grid">
		<?php if ( empty( $definitions ) ) : ?>
			<div class="charts-bento-card full-width empty-state">
				<div class="empty-icon"><span class="dashicons dashicons-chart-bar"></span></div>
				<h3><?php _e( 'No Charts Defined', 'charts' ); ?></h3>
				<p><?php _e( 'Start by creating your first music or video chart definition.', 'charts' ); ?></p>
				<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'definitions', array( 'action' => 'edit' ) ); ?>" class="charts-btn charts-btn-outline">
					<?php _e( 'Create My First Chart', 'charts' ); ?>
				</a>
			</div>
		<?php else : ?>
			<?php foreach ( $definitions as $def ) : 
				$platform_label = ($def->platform === 'all') ? __('Mixed platforms', 'charts') : ucfirst($def->platform);
				$status_class = $def->is_public ? 'status-public' : 'status-private';
			?>
				<div class="charts-bento-card chart-definition-card">
					<div class="card-header">
						<span class="badge <?php echo $status_class; ?>">
							<?php echo $def->is_public ? __('Public', 'charts') : __('Draft', 'charts'); ?>
						</span>
						<?php if ($def->is_featured) : ?>
							<span class="badge badge-featured"><?php _e( 'Featured', 'charts' ); ?></span>
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
						</div>
						<p class="card-summary"><?php echo esc_html( wp_trim_words($def->chart_summary, 15) ?: __('No summary provided.', 'charts') ); ?></p>
					</div>

					<div class="card-footer">
						<div class="card-slug">/charts/<?php echo esc_html($def->slug); ?></div>
						<div class="card-actions">
							<a href="<?php echo \Charts\Core\Router::get_dashboard_url( 'definitions', array( 'action' => 'edit', 'id' => $def->id ) ); ?>" class="action-icon" title="<?php _e('Edit', 'charts'); ?>">
								<span class="dashicons dashicons-edit"></span>
							</a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this chart?', 'charts'); ?>');">
								<?php wp_nonce_field( 'charts_admin_action' ); ?>
								<input type="hidden" name="charts_action" value="delete_definition">
								<input type="hidden" name="id" value="<?php echo $def->id; ?>">
								<button type="submit" class="action-icon delete" title="<?php _e('Delete', 'charts'); ?>">
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
