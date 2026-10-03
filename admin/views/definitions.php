<?php
/** Admin view: chart definitions library. */
$manager = new \Charts\Admin\SourceManager();
$definitions = $manager->get_definitions();
$new_chart_url = \Charts\Core\Router::get_dashboard_url( 'definitions', array( 'action' => 'edit' ) );
?>
<div class="charts-admin-wrap premium-light charts-definitions-page">
	<header class="charts-admin-header charts-definitions-header">
		<div>
			<h1 class="charts-admin-title"><?php esc_html_e( 'Charts Intelligence', 'charts' ); ?></h1>
			<p class="charts-admin-subtitle"><?php esc_html_e( 'Manage your dynamic chart products and definitions.', 'charts' ); ?></p>
		</div>
		<a href="<?php echo esc_url( $new_chart_url ); ?>" class="charts-btn-create charts-new-chart-button">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<?php esc_html_e( 'Create New Chart', 'charts' ); ?>
		</a>
	</header>

	<?php if ( empty( $definitions ) ) : ?>
		<section class="charts-definitions-empty">
			<div class="charts-empty-icon"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span></div>
			<h2><?php esc_html_e( 'No charts yet', 'charts' ); ?></h2>
			<p><?php esc_html_e( 'Create your first chart to start organizing chart data and public pages.', 'charts' ); ?></p>
			<a href="<?php echo esc_url( $new_chart_url ); ?>" class="charts-btn-create"><?php esc_html_e( 'Create your first chart', 'charts' ); ?></a>
		</section>
	<?php else : ?>
		<div class="charts-definition-grid">
			<?php foreach ( $definitions as $definition ) :
				$native_id = ! empty( $definition->native_post_id ) ? (int) $definition->native_post_id : (int) $manager->get_post_id_by_definition_id( $definition->id );
				$edit_url = $native_id ? get_edit_post_link( $native_id, 'raw' ) : \Charts\Core\Router::get_dashboard_url( 'definitions', array( 'action' => 'edit', 'id' => $definition->id ) );
				$chart_name = ! empty( $definition->title_ar ) ? $definition->title_ar : $definition->title;
				$entity_labels = array( 'track' => __( 'Track', 'charts' ), 'artist' => __( 'Artist', 'charts' ), 'video' => __( 'Video', 'charts' ), 'album' => __( 'Album', 'charts' ) );
				$frequency_labels = array( 'daily' => __( 'Daily', 'charts' ), 'weekly' => __( 'Weekly', 'charts' ), 'monthly' => __( 'Monthly', 'charts' ) );
				$platform_label = ( $definition->platform === 'all' ) ? __( 'Mixed platforms', 'charts' ) : ucfirst( $definition->platform );
			?>
				<article class="chart-definition-card">
					<div class="chart-definition-badges">
						<span class="chart-status-badge <?php echo $definition->is_public ? 'is-public' : 'is-draft'; ?>"><?php echo $definition->is_public ? esc_html__( 'Public', 'charts' ) : esc_html__( 'Draft', 'charts' ); ?></span>
						<span class="chart-status-badge <?php echo $native_id ? 'is-native' : 'is-legacy'; ?>"><?php echo $native_id ? esc_html__( 'Native', 'charts' ) : esc_html__( 'Legacy', 'charts' ); ?></span>
						<?php if ( ! empty( $definition->is_featured ) ) : ?><span class="chart-status-badge is-featured"><?php esc_html_e( 'Featured', 'charts' ); ?></span><?php endif; ?>
					</div>

					<div class="chart-definition-main">
						<h2 class="chart-definition-title"><?php echo esc_html( $chart_name ); ?></h2>
						<?php if ( ! empty( $definition->title_ar ) && $definition->title_ar !== $definition->title ) : ?><p class="chart-definition-title-en"><?php echo esc_html( $definition->title ); ?></p><?php endif; ?>
						<div class="chart-definition-meta">
							<span><span class="dashicons dashicons-location" aria-hidden="true"></span><?php echo esc_html( strtoupper( $definition->country_code ) ); ?></span>
							<span><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><?php echo esc_html( $frequency_labels[ $definition->frequency ] ?? ucfirst( $definition->frequency ) ); ?></span>
							<span><span class="dashicons dashicons-tag" aria-hidden="true"></span><?php echo esc_html( $entity_labels[ $definition->item_type ] ?? ucfirst( $definition->item_type ) ); ?></span>
						</div>
						<?php if ( $platform_label !== 'Mixed platforms' ) : ?><span class="chart-definition-platform"><?php echo esc_html( $platform_label ); ?></span><?php endif; ?>
						<p class="chart-definition-summary"><?php echo esc_html( wp_trim_words( $definition->chart_summary, 15 ) ?: __( 'No summary provided.', 'charts' ) ); ?></p>
					</div>

					<footer class="chart-definition-footer">
						<a class="chart-definition-slug" href="<?php echo esc_url( home_url( '/charts/' . $definition->slug ) ); ?>" target="_blank" rel="noopener noreferrer">/charts/<?php echo esc_html( $definition->slug ); ?></a>
						<div class="chart-definition-actions">
							<a href="<?php echo esc_url( $edit_url ); ?>" class="chart-icon-action" title="<?php esc_attr_e( 'Edit chart', 'charts' ); ?>" aria-label="<?php esc_attr_e( 'Edit chart', 'charts' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
							<?php if ( ! $native_id ) : ?>
								<form method="post">
									<?php wp_nonce_field( 'kcharts_save_v2' ); ?>
									<input type="hidden" name="charts_action" value="promote_chart">
									<input type="hidden" name="id" value="<?php echo (int) $definition->id; ?>">
									<button type="submit" class="chart-icon-action is-promote" title="<?php esc_attr_e( 'Promote to native chart', 'charts' ); ?>" aria-label="<?php esc_attr_e( 'Promote to native chart', 'charts' ); ?>"><span class="dashicons dashicons-upload" aria-hidden="true"></span></button>
								</form>
							<?php endif; ?>
							<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this chart?', 'charts' ) ); ?>');">
								<?php wp_nonce_field( 'charts_admin_action' ); ?>
								<input type="hidden" name="charts_action" value="delete_definition">
								<input type="hidden" name="id" value="<?php echo (int) $definition->id; ?>">
								<button type="submit" class="chart-icon-action is-delete" title="<?php esc_attr_e( 'Delete chart', 'charts' ); ?>" aria-label="<?php esc_attr_e( 'Delete chart', 'charts' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
							</form>
						</div>
					</footer>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
