<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap kc-admin-page">
	<div class="kc-page-header">
		<h1 class="wp-heading-inline"><?php _e( 'CPT Migration Verification', 'charts' ); ?></h1>
		<p class="description"><?php _e( 'Compare legacy SQL tables with native WordPress CPTs to ensure 1:1 parity.', 'charts' ); ?></p>
	</div>

	<div class="kc-dashboard-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
		
		<?php foreach ( $report as $type => $data ): ?>
		<div class="kc-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
			<h2 style="margin-top: 0; text-transform: capitalize;"><?php echo esc_html($type); ?>s</h2>
			<table class="widefat striped" style="border: none; box-shadow: none;">
				<tbody>
					<tr>
						<td><strong><?php _e( 'Legacy SQL Records', 'charts' ); ?></strong></td>
						<td><?php echo number_format($data['sql']); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e( 'Native CPT Posts', 'charts' ); ?></strong></td>
						<td><?php echo number_format($data['cpt']); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e( 'Linked (Meta _old_id)', 'charts' ); ?></strong></td>
						<td>
							<?php if ($data['mapped'] == $data['sql']): ?>
								<span style="color: #059669; font-weight: bold;">(<?php echo number_format($data['mapped']); ?>) &#10003;</span>
							<?php else: ?>
								<span style="color: #d97706; font-weight: bold;"><?php echo number_format($data['mapped']); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e( 'Remaining to Migrate', 'charts' ); ?></strong></td>
						<td>
							<?php if ($data['remaining'] == 0): ?>
								<span style="color: #059669;">None</span>
							<?php else: ?>
								<span style="color: #dc2626; font-weight: bold;"><?php echo number_format($data['remaining']); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e( 'Duplicates Found', 'charts' ); ?></strong></td>
						<td>
							<?php if ($data['duplicates'] == 0): ?>
								<span style="color: #059669;">0</span>
							<?php else: ?>
								<span style="color: #dc2626; font-weight: bold;"><?php echo number_format($data['duplicates']); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>

		<div class="kc-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; grid-column: span 2;">
			<h2 style="margin-top: 0;"><?php _e( 'Artist Relationships Mapping', 'charts' ); ?></h2>
			<?php foreach ( $rel_report as $type => $data ): ?>
				<div style="margin-bottom: 15px;">
					<p><strong><?php echo ucfirst($type); ?>-Artist Links:</strong> <?php echo number_format($data['migrated']); ?> / <?php echo number_format($data['total']); ?></p>
					<div style="height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden;">
						<div style="height: 100%; background: #6366f1; width: <?php echo ($data['total'] > 0) ? ($data['migrated'] / $data['total'] * 100) : 100; ?>%;"></div>
					</div>
					<?php if ($data['pending'] > 0): ?>
						<p style="font-size: 12px; color: #64748b;"><?php printf( __( '%s mappings remaining.', 'charts' ), number_format($data['pending']) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

	</div>

	<div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
		<h3><?php _e( 'Final Production Verification Status', 'charts' ); ?></h3>
		<?php 
		$is_safe = true;
		foreach($report as $r) if($r['remaining'] > 0 || $r['duplicates'] > 0) $is_safe = false;
		foreach($rel_report as $r) if($r['pending'] > 0) $is_safe = false;
		?>
		
		<?php if ($is_safe): ?>
			<div style="display: flex; align-items: center; color: #059669;">
				<span style="font-size: 32px; margin-right: 15px;">&#10004;</span>
				<div>
					<h4 style="margin: 0;"><?php _e( 'All Checks Passed!', 'charts' ); ?></h4>
					<p style="margin: 5px 0 0;"><?php _e( 'Data parity is 100%. The system is certified Production-Safe.', 'charts' ); ?></p>
				</div>
			</div>
		<?php else: ?>
			<div style="display: flex; align-items: center; color: #d97706;">
				<span style="font-size: 32px; margin-right: 15px;">&#9888;</span>
				<div>
					<h4 style="margin: 0;"><?php _e( 'Migration in Progress', 'charts' ); ?></h4>
					<p style="margin: 5px 0 0;"><?php _e( 'Some data is still being processed or mapping conflicts exist. Refresh to update progress.', 'charts' ); ?></p>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
.kc-admin-page { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.kc-card h2 { color: #1e293b; font-size: 18px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
.kc-card table td { padding: 12px 5px !important; }
</style>
