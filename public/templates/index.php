<?php
/**
 * Charts Index Template - Bento Grid
 */
get_header();

global $wpdb;
$table = $wpdb->prefix . 'charts_sources';
$sources = $wpdb->get_results( "SELECT * FROM $table WHERE is_active = 1" );
?>

<div class="charts-container">
	<header class="charts-header">
		<h1 class="premium-heading"><?php _e( 'Music Intelligence', 'charts' ); ?></h1>
		<p class="subtitle"><?php _e( 'The definitive pulse of Egyptian and Arabian streaming charts.', 'charts' ); ?></p>
	</header>

	<div class="charts-bento-grid">
		<?php if ( empty( $sources ) ) : ?>
			<div class="bento-card bento-card-wide" style="display: flex; align-items: center; justify-content: center; color: var(--charts-muted);">
				<p><?php _e( 'Connecting to the pulse... Please check back later.', 'charts' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $sources as $index => $source ) : 
				$class = 'bento-card';
				if ( $index === 0 ) $class .= ' bento-card-large';
				if ( $index === 1 || $index === 4 ) $class .= ' bento-card-tall';
				if ( $index === 3 ) $class .= ' bento-card-wide';
				
				$url = home_url( '/charts/' . $source->platform . '/' . $source->country_code . '/' . $source->frequency . '/' . $source->chart_type . '/' );
			?>
				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
					<div class="bento-tag"><?php echo esc_html( strtoupper( $source->platform ) ); ?> &middot; <?php echo esc_html( strtoupper( $source->country_code ) ); ?></div>
					<div class="bento-title"><?php echo esc_html( $source->source_name ); ?></div>
					
					<div class="bento-footer">
						<div class="bento-meta"><?php echo $source->last_run_at ? sprintf( __( 'Updated %s ago', 'charts' ), human_time_diff( strtotime( $source->last_run_at ) ) ) : __( 'Updating...', 'charts' ); ?></div>
						<div class="bento-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
    <?php include CHARTS_PATH . 'public/assets/css/public.css'; ?>
</style>

<?php get_footer(); ?>
