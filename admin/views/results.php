<?php
/**
 * Import Runs View
 */
global $wpdb;
$runs_table   = $wpdb->prefix . 'charts_import_runs';
$sources_table = $wpdb->prefix . 'charts_sources';

$runs = $wpdb->get_results( "
	SELECT r.*, s.source_name, s.platform, s.country_code, s.chart_type, s.frequency
	FROM {$runs_table} r
	LEFT JOIN {$sources_table} s ON s.id = r.source_id
	ORDER BY r.started_at DESC
	LIMIT 100
" );
?>
<div class="charts-admin-wrap">
	<header class="charts-header">
		<div>
			<h1><?php _e( 'Import Runs', 'charts' ); ?></h1>
			<p class="subtitle"><?php printf( __( '%d total runs recorded', 'charts' ), count( $runs ) ); ?></p>
		</div>
	</header>

	<div class="charts-grid">
		<div class="charts-card" style="grid-column: span 12; padding: 0; overflow: hidden;">
			<?php if ( empty( $runs ) ) : ?>
				<div style="padding: 60px; text-align: center; color: #6b7280;">
					<h3><?php _e( 'No import runs yet.', 'charts' ); ?></h3>
					<p><?php _e( 'Upload a Spotify CSV from the Spotify Import page to record the first run.', 'charts' ); ?></p>
				</div>
			<?php else : ?>
				<table class="charts-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;"><?php _e( 'Source', 'charts' ); ?></th>
							<th><?php _e( 'Type', 'charts' ); ?></th>
							<th><?php _e( 'Status', 'charts' ); ?></th>
							<th><?php _e( 'Parsed', 'charts' ); ?></th>
							<th><?php _e( 'Saved', 'charts' ); ?></th>
							<th><?php _e( 'Enriched', 'charts' ); ?></th>
							<th><?php _e( 'Started', 'charts' ); ?></th>
							<th style="padding-right: 24px; text-align: right;"><?php _e( 'Actions', 'charts' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $runs as $run ) :
						$status_badge = $run->status === 'completed' ? 'charts-badge-success'
							: ( $run->status === 'failed' ? 'charts-badge-error' : 'charts-badge-neutral' );
						$chart_url = home_url( '/charts/spotify/' . rawurlencode( $run->country_code ?? '' ) . '/' . rawurlencode( $run->frequency ?? '' ) . '/' . rawurlencode( $run->chart_type ?? '' ) . '/' );
					?>
						<tr>
							<td style="padding-left: 24px;">
								<div style="font-weight: 700;"><?php echo esc_html( $run->source_name ?? 'Unknown Source' ); ?></div>
								<div style="font-size: 11px; color: #9ca3af;"><?php echo esc_html( strtoupper( $run->platform ?? '' ) . ' · ' . strtoupper( $run->country_code ?? '' ) . ' · ' . strtoupper( $run->frequency ?? '' ) ); ?></div>
							</td>
							<td><span class="charts-badge charts-badge-neutral" style="font-size: 9px;"><?php echo esc_html( strtoupper( $run->run_type ?? 'csv' ) ); ?></span></td>
							<td>
								<span class="charts-badge <?php echo $status_badge; ?>" style="font-size: 9px;"><?php echo esc_html( strtoupper( $run->status ?? '' ) ); ?></span>
								<?php if ( $run->status === 'failed' && $run->error_message ) : ?>
									<div style="font-size: 10px; color: #ef4444; margin-top: 4px;" title="<?php echo esc_attr( $run->error_message ); ?>"><?php echo esc_html( mb_substr( $run->error_message, 0, 60 ) ); ?><?php echo strlen($run->error_message)>60 ? '…' : ''; ?></div>
								<?php endif; ?>
							</td>
							<td style="font-weight: 700;"><?php echo number_format( $run->parsed_rows ?? 0 ); ?></td>
							<td style="font-weight: 700; color: <?php echo ($run->matched_items > 0) ? '#22c55e' : '#9ca3af'; ?>;"><?php echo number_format( $run->matched_items ?? 0 ); ?></td>
							<td><?php echo number_format( ($run->enrichment_attempts ?? 0) - ($run->enrichment_failures ?? 0) ); ?></td>
							<td style="font-size: 12px; color: #6b7280;"><?php echo $run->started_at ? date( 'M j, Y H:i', strtotime( $run->started_at ) ) : '–'; ?></td>
							<td style="text-align: right; padding-right: 24px;">
								<?php if ( $run->status === 'completed' && $run->platform ) : ?>
									<a href="<?php echo esc_url( $chart_url ); ?>" target="_blank" class="charts-btn charts-btn-secondary" style="padding: 5px 10px; font-size: 11px; text-decoration: none;"><?php _e( 'View Chart', 'charts' ); ?></a>
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
