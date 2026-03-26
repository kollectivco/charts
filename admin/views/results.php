<?php
/**
 * Import Runs View
 */
global $wpdb;

$table = $wpdb->prefix . 'charts_import_runs';
$sources_table = $wpdb->prefix . 'charts_sources';

$runs = $wpdb->get_results( "
	SELECT r.*, s.source_name 
	FROM $table r 
	JOIN $sources_table s ON r.source_id = s.id 
	ORDER BY r.id DESC 
	LIMIT 50
" );
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Import Runs', 'charts' ); ?></h1>
			<p class="subtitle"><?php _e( 'History of data fetching and parsing operations', 'charts' ); ?></p>
		</div>
	</header>

	<div class="charts-grid">
		<div class="charts-card" style="grid-column: span 12; padding: 0;">
			<table class="charts-table">
				<thead>
					<tr>
						<th style="padding-left: 24px;"><?php _e( 'Run ID', 'charts' ); ?></th>
						<th><?php _e( 'Source', 'charts' ); ?></th>
						<th><?php _e( 'Status', 'charts' ); ?></th>
						<th><?php _e( 'Fetched', 'charts' ); ?></th>
						<th><?php _e( 'Parsed', 'charts' ); ?></th>
						<th><?php _e( 'Matched', 'charts' ); ?></th>
						<th><?php _e( 'Started', 'charts' ); ?></th>
						<th><?php _e( 'Finished', 'charts' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $runs ) ) : ?>
						<tr>
							<td colspan="8" style="text-align: center; color: var(--charts-gray-500); padding: 40px;">
								<?php _e( 'No import runs recorded yet.', 'charts' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $runs as $run ) : ?>
							<tr>
								<td style="padding-left: 24px;">#<?php echo esc_html( $run->id ); ?></td>
								<td style="font-weight: 600;"><?php echo esc_html( $run->source_name ); ?></td>
								<td>
									<span class="charts-badge charts-badge-<?php echo ($run->status === 'completed') ? 'success' : (($run->status === 'failed') ? 'error' : 'pending'); ?>">
										<?php echo strtoupper( esc_html( $run->status ) ); ?>
									</span>
									<?php if ( $run->error_message ) : ?>
										<div style="font-size: 10px; color: red; margin-top: 4px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
											<?php echo esc_html( $run->error_message ); ?>
										</div>
									<?php endif; ?>
								</td>
								<td><?php echo (int) $run->fetched_rows; ?></td>
								<td><?php echo (int) $run->parsed_rows; ?></td>
								<td><?php echo (int) $run->matched_items; ?></td>
								<td><?php echo esc_html( $run->started_at ); ?></td>
								<td><?php echo $run->finished_at ? esc_html( $run->finished_at ) : '–'; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
