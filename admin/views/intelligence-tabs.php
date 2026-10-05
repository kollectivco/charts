<?php
/**
 * Shared Tab Bar for Unified Intelligence Hub (Signals, Forecast, Insights)
 */
$current_tab = sanitize_key( $_GET['tab'] ?? 'signals' );
if ( ! in_array( $current_tab, array( 'signals', 'forecast', 'insights' ), true ) ) {
	$current_tab = 'signals';
}

$tabs = array(
	'signals'  => array(
		'label' => __( 'Intelligence Nexus', 'charts' ),
		'icon'  => 'dashicons-chart-line',
	),
	'forecast' => array(
		'label' => __( 'Forecast & Predictions', 'charts' ),
		'icon'  => 'dashicons-visibility',
	),
	'insights' => array(
		'label' => __( 'Weekly Insights', 'charts' ),
		'icon'  => 'dashicons-lightbulb',
	),
);
?>
<div class="bento-tabs-bar" style="display:flex; gap:6px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:6px; margin-bottom:24px; width:fit-content; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
	<?php foreach ( $tabs as $tab_key => $tab ) : 
		$is_active = ( $current_tab === $tab_key );
	?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charts-intelligence&tab=' . $tab_key ) ); ?>" 
		   class="bento-tab <?php echo $is_active ? 'active' : ''; ?>" 
		   style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:700; color:<?php echo $is_active ? '#ffffff' : '#64748b'; ?>; background:<?php echo $is_active ? '#0f172a' : 'transparent'; ?>; transition:all .18s ease;">
			<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" style="font-size:17px; width:17px; height:17px; line-height:17px; color:<?php echo $is_active ? '#ffffff' : '#94a3b8'; ?>;"></span>
			<?php echo esc_html( $tab['label'] ); ?>
		</a>
	<?php endforeach; ?>
</div>
