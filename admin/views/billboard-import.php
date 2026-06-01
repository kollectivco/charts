<?php
/**
 * Billboard Arabia Import View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$definitions = \Charts\Core\EntityManager::get_definitions();

// Handle messages
$message = '';
$message_type = '';
if ( isset( $_GET['import'] ) && $_GET['import'] === 'success' ) {
	$count = isset($_GET['count']) ? intval($_GET['count']) : 0;
	$message = "Successfully imported {$count} entries from Billboard Arabia.";
	$message_type = 'success';
} elseif ( isset( $_GET['import'] ) && $_GET['import'] === 'failed' ) {
	$reason = isset($_GET['reason']) ? sanitize_text_field($_GET['reason']) : 'Unknown error';
	$message = "Import failed: {$reason}";
	$message_type = 'error';
}
?>

<div class="wrap kc-dashboard-wrap premium-bento">
	<div class="kc-settings-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
		<div class="kc-branding">
			<h1 class="kc-title" style="margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -0.04em; color: #1e293b; display: flex; align-items: center; gap: 10px;">
				<span class="dashicons dashicons-download" style="font-size: 32px; width: 32px; height: 32px; color: #3b82f6;"></span>
				Billboard Arabia Import Center
			</h1>
			<p class="kc-subtitle" style="margin: 8px 0 0; color: #64748b; font-size: 15px;">Import chart data directly from Billboard Arabia URLs without YouTube or Spotify enrichment.</p>
		</div>
	</div>

	<?php if ( $message ) : ?>
		<div class="kb-warning-notice" style="background: <?php echo $message_type === 'success' ? '#ecfdf5' : '#fef2f2'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#a7f3d0' : '#fecaca'; ?>; color: <?php echo $message_type === 'success' ? '#065f46' : '#991b1b'; ?>; padding: 16px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 30px; display: flex; gap: 10px;">
			<span class="dashicons dashicons-<?php echo $message_type === 'success' ? 'yes-alt' : 'warning'; ?>" style="margin-top: 2px;"></span>
			<div><?php echo esc_html($message); ?></div>
		</div>
	<?php endif; ?>

	<div class="kc-cards-grid" style="display: grid; grid-template-columns: 1fr; gap: 24px;">
		
		<div class="kb-settings-card" style="background: #fff; border-radius: 16px; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
			<h2 style="margin: 0 0 24px; font-size: 18px; font-weight: 800; color: #1e293b;">Import Configuration</h2>
			
			<form method="post" action="" style="display: flex; flex-direction: column; gap: 24px;">
				<input type="hidden" name="charts_action" value="import_billboard_url">
				<?php wp_nonce_field( 'kcharts_save_v2' ); ?>

				<div class="kb-form-group">
					<label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Target Chart Definition</label>
					<select name="definition_id" required style="width: 100%; max-width: 400px; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #f8fafc;">
						<option value="">-- Select Chart --</option>
						<?php foreach ( $definitions as $def ) : ?>
							<option value="<?php echo esc_attr( $def->id ); ?>">
								<?php echo esc_html( $def->title ); ?> (<?php echo esc_html( $def->chart_type ); ?> - <?php echo esc_html( $def->country_code ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="kb-form-group">
					<label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Chart Date</label>
					<input type="date" name="chart_date" required style="width: 100%; max-width: 400px; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #f8fafc;">
					<p style="margin: 6px 0 0; font-size: 12px; color: #94a3b8;">Select the week/period this chart represents.</p>
				</div>

				<div class="kb-form-group">
					<label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Billboard Arabia Chart URL</label>
					<input type="url" name="billboard_url" placeholder="https://www.billboardarabia.com/chart-view/..." required style="width: 100%; max-width: 600px; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #f8fafc;">
					<p style="margin: 6px 0 0; font-size: 12px; color: #94a3b8;">Paste the full URL of the Billboard Arabia chart page.</p>
				</div>

				<div style="margin-top: 12px;">
					<button type="submit" class="kb-btn kb-btn-primary" style="background: #3b82f6; color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-weight: 800; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
						<span class="dashicons dashicons-cloud-saved"></span>
						Start Import Process
					</button>
				</div>
			</form>
		</div>
		
	</div>
</div>
