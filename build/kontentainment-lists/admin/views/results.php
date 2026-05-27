<?php
/**
 * Import Runs View
 */
global $wpdb;
$runs_table   = $wpdb->prefix . 'charts_import_runs';
$sources_table = $wpdb->prefix . 'charts_sources';
$batches_table = $wpdb->prefix . 'charts_import_batches';
$definitions_table = $wpdb->prefix . 'charts_definitions';

$filter_chart  = intval( $_GET['chart_id'] ?? 0 );
$filter_date   = sanitize_text_field( $_GET['target_date'] ?? '' );
$filter_source = sanitize_text_field( $_GET['source'] ?? '' );
$where         = array( '1=1' );

if ( $filter_chart ) {
	$where[] = $wpdb->prepare( 'r.chart_definition_id = %d', $filter_chart );
}
if ( $filter_date ) {
	$where[] = $wpdb->prepare( 'b.target_date = %s', $filter_date );
}
if ( $filter_source ) {
	$where[] = $wpdb->prepare( 'COALESCE(r.import_source, b.provider, r.run_type) = %s', $filter_source );
}
$where_sql = implode( ' AND ', $where );
$definitions = $wpdb->get_results( "SELECT id, title FROM {$definitions_table} ORDER BY title ASC" );

$runs = $wpdb->get_results( "
	SELECT r.*, s.source_name, s.platform, s.country_code, s.chart_type, s.frequency, d.title AS chart_title, b.provider, b.target_date, b.imported_rows, b.updated_entries AS batch_updated_entries, b.created_artists AS batch_created_artists, b.created_tracks AS batch_created_tracks, b.skipped_duplicates AS batch_skipped_duplicates, b.error_count AS batch_error_count
	FROM {$runs_table} r
	LEFT JOIN {$sources_table} s ON s.id = r.source_id
	LEFT JOIN {$definitions_table} d ON d.id = r.chart_definition_id
	LEFT JOIN {$batches_table} b ON b.id = r.batch_id
	WHERE {$where_sql}
	ORDER BY r.started_at DESC
	LIMIT 100
" );
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Import Runs', 'kontentainment-lists' ); ?></h1>
			<p class="subtitle"><?php printf( __( '%d total runs recorded', 'kontentainment-lists' ), count( $runs ) ); ?></p>
		</div>
	</header>

	<form method="get" style="margin-bottom:20px; display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
		<input type="hidden" name="page" value="charts-imports">
		<div>
			<label style="display:block; font-size:12px; margin-bottom:6px;"><?php _e( 'List', 'kontentainment-lists' ); ?></label>
			<select name="chart_id">
				<option value=""><?php _e( 'All Lists', 'kontentainment-lists' ); ?></option>
				<?php foreach ( $definitions as $definition ) : ?>
					<option value="<?php echo (int) $definition->id; ?>" <?php selected( $filter_chart, $definition->id ); ?>><?php echo esc_html( $definition->title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label style="display:block; font-size:12px; margin-bottom:6px;"><?php _e( 'Date', 'kontentainment-lists' ); ?></label>
			<input type="date" name="target_date" value="<?php echo esc_attr( $filter_date ); ?>">
		</div>
		<div>
			<label style="display:block; font-size:12px; margin-bottom:6px;"><?php _e( 'Source', 'kontentainment-lists' ); ?></label>
			<select name="source">
				<option value=""><?php _e( 'All Sources', 'kontentainment-lists' ); ?></option>
				<option value="soundcharts_api" <?php selected( $filter_source, 'soundcharts_api' ); ?>>Soundcharts API</option>
				<option value="csv" <?php selected( $filter_source, 'csv' ); ?>>CSV</option>
				<option value="youtube_csv" <?php selected( $filter_source, 'youtube_csv' ); ?>>YouTube CSV</option>
			</select>
		</div>
		<button type="submit" class="charts-btn charts-btn-secondary"><?php _e( 'Filter', 'kontentainment-lists' ); ?></button>
	</form>

	<div class="charts-grid">
		<div class="charts-card" style="grid-column: span 12; padding: 0; overflow: hidden;">
			<?php if ( empty( $runs ) ) : ?>
				<div style="padding: 60px; text-align: center; color: #6b7280;">
					<h3><?php _e( 'No import runs yet.', 'kontentainment-lists' ); ?></h3>
					<p><?php _e( 'Upload a Spotify CSV from the Spotify Import page to record the first run.', 'kontentainment-lists' ); ?></p>
				</div>
			<?php else : ?>
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;"><?php _e( 'Source', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Source', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Status', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Date', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Fetched', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Imported', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Updated', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Artists', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Tracks', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Skipped', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Errors', 'kontentainment-lists' ); ?></th>
							<th><?php _e( 'Started', 'kontentainment-lists' ); ?></th>
							<th style="padding-right: 24px; text-align: right;"><?php _e( 'Actions', 'kontentainment-lists' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $runs as $run ) :
						$status_badge = $run->status === 'completed' ? 'charts-badge-success'
							: ( $run->status === 'failed' ? 'charts-badge-error' : 'charts-badge-neutral' );
						$chart_url = home_url( '/lists/spotify/' . rawurlencode( $run->country_code ?? '' ) . '/' . rawurlencode( $run->frequency ?? '' ) . '/' . rawurlencode( $run->chart_type ?? '' ) . '/' );
					?>
						<tr>
							<td style="padding-left: 24px;">
								<div style="font-weight: 700;"><?php echo esc_html( $run->source_name ?? 'Unknown Source' ); ?></div>
								<div style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( ($run->chart_title ?: strtoupper( $run->platform ?? '' )) . ' · ' . strtoupper( $run->country_code ?? '' ) . ' · ' . strtoupper( $run->frequency ?? '' ) ); ?></div>
							</td>
							<td><span class="charts-badge charts-badge-neutral" style="font-size: 9px;"><?php echo esc_html( strtoupper( $run->import_source ?: ( $run->provider ?: $run->run_type ?: 'csv' ) ) ); ?></span></td>
							<td>
								<span class="charts-badge <?php echo $status_badge; ?>" style="font-size: 9px;"><?php echo esc_html( strtoupper( $run->status ?? '' ) ); ?></span>
								<?php if ( $run->status === 'failed' && $run->error_message ) : ?>
									<div style="font-size: 10px; color: #ef4444; margin-top: 4px;" title="<?php echo esc_attr( $run->error_message ); ?>"><?php echo esc_html( mb_substr( $run->error_message, 0, 60 ) ); ?><?php echo strlen($run->error_message)>60 ? '…' : ''; ?></div>
								<?php endif; ?>
							</td>
							<td style="font-size: 12px; color: #6b7280;"><?php echo esc_html( $run->target_date ?: '–' ); ?></td>
							<td style="font-weight: 700;"><?php echo number_format( $run->fetched_rows ?? $run->parsed_rows ?? 0 ); ?></td>
							<td style="font-weight: 700; color: <?php echo ( ( $run->imported_rows ?? $run->matched_items ) > 0 ) ? '#22c55e' : '#9ca3af'; ?>;"><?php echo number_format( $run->imported_rows ?? $run->matched_items ?? 0 ); ?></td>
							<td><?php echo number_format( $run->batch_updated_entries ?? $run->updated_entries ?? 0 ); ?></td>
							<td><?php echo number_format( $run->batch_created_artists ?? $run->created_artists ?? 0 ); ?></td>
							<td><?php echo number_format( $run->batch_created_tracks ?? $run->created_tracks ?? 0 ); ?></td>
							<td><?php echo number_format( $run->batch_skipped_duplicates ?? $run->skipped_duplicates ?? 0 ); ?></td>
							<td><?php echo number_format( $run->batch_error_count ?? $run->error_count ?? 0 ); ?></td>
							<td style="font-size: 12px; color: #6b7280;"><?php echo $run->started_at ? date( 'M j, Y H:i', strtotime( $run->started_at ) ) : '–'; ?></td>
							<td style="text-align: right; padding-right: 24px;">
								<?php if ( $run->status === 'completed' && $run->platform ) : ?>
									<a href="<?php echo esc_url( $chart_url ); ?>" target="_blank" class="charts-btn charts-btn-secondary" style="padding: 5px 10px; font-size: 11px; text-decoration: none;"><?php _e( 'View List', 'kontentainment-lists' ); ?></a>
								<?php endif; ?>
								<?php if ( ! empty( $run->logs_json ) ) :
									$logs = json_decode( $run->logs_json, true );
								?>
									<span title="<?php echo esc_attr( json_encode( $logs ) ); ?>" style="font-size: 10px; color: #9ca3af; cursor: help; margin-left: 6px;">&#9432; Logs</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
