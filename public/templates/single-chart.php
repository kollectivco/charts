<?php
/**
 * Single Chart Template - Detailed List
 */
get_header();

global $wpdb;

$platform  = get_query_var( 'charts_platform' );
$country   = get_query_var( 'charts_country' );
$frequency = get_query_var( 'charts_frequency' );
$type      = get_query_var( 'charts_type' );

// 1. Find Source
$source_table = $wpdb->prefix . 'charts_sources';
$source = $wpdb->get_row( $wpdb->prepare( 
	"SELECT * FROM $source_table WHERE platform = %s AND country_code = %s AND frequency = %s AND chart_type = %s", 
	$platform, $country, $frequency, $type
) );

if ( ! $source ) {
	echo '<div class="charts-container"><h1>' . __( 'Chart Not Found', 'charts' ) . '</h1><p>' . esc_html("$platform / $country / $frequency / $type") . '</p></div>';
	get_footer();
	return;
}

// 2. Find Latest Period
$period_table = $wpdb->prefix . 'charts_periods';
$period = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $period_table WHERE frequency = %s ORDER BY id DESC LIMIT 1", $source->frequency ) );

if ( ! $period ) {
	echo '<div class="charts-container"><h1>' . __( 'No Chart Data Available', 'charts' ) . '</h1></div>';
	get_footer();
	return;
}

// 3. Get Entries
$entries_table = $wpdb->prefix . 'charts_entries';
$tracks_table  = $wpdb->prefix . 'charts_tracks';
$artists_table = $wpdb->prefix . 'charts_artists';

$query = $wpdb->prepare( "
	SELECT e.*, t.title, a.display_name as artist_name, t.cover_image
	FROM $entries_table e
	JOIN $tracks_table t ON e.item_id = t.id
	JOIN $artists_table a ON t.primary_artist_id = a.id
	WHERE e.source_id = %d AND e.period_id = %d
	ORDER BY e.rank_position ASC
	LIMIT 100
", $source->id, $period->id );

$entries = $wpdb->get_results( $query );

?>
<div class="charts-container">
	<header class="charts-header">
		<div class="bento-tag"><?php echo esc_html( strtoupper( $source->platform ) ); ?> &middot; <?php echo esc_html( strtoupper( $source->country_code ) ); ?> &middot; <?php echo esc_html( strtoupper( $period->frequency ) ); ?></div>
		<h1><?php echo esc_html( $source->source_name ); ?></h1>
		<p class="subtitle"><?php echo sprintf( __( 'Latest results as of %s', 'charts' ), date('M j, Y', strtotime($period->period_start)) ); ?></p>
	</header>

	<div class="chart-list">
		<?php if ( empty( $entries ) ) : ?>
			<div class="chart-row">
				<p><?php _e( 'The signal is currently being processed. Please refresh in a moment.', 'charts' ); ?></p>
			</div>
		<?php else : ?>
			<?php foreach ( $entries as $entry ) : ?>
				<div class="chart-row">
					<div class="chart-rank">
						<?php echo (int) $entry->rank_position; ?>
					</div>
					
					<?php if ( $entry->cover_image ) : ?>
						<img src="<?php echo esc_url( $entry->cover_image ); ?>" class="chart-cover" loading="lazy">
					<?php else : ?>
						<div class="chart-cover"></div>
					<?php endif; ?>

					<div class="chart-info">
						<div class="chart-title"><?php echo esc_html( $entry->title ); ?></div>
						<div class="chart-artist"><?php echo esc_html( $entry->artist_name ); ?></div>
					</div>

					<div class="chart-meta">
						<div class="<?php echo 'movement-' . $entry->movement_direction; ?>">
							<?php if ( $entry->movement_direction === 'up' ) : ?>
								&uarr; <?php echo (int) $entry->movement_value; ?>
							<?php elseif ( $entry->movement_direction === 'down' ) : ?>
								&darr; <?php echo (int) $entry->movement_value; ?>
							<?php elseif ( $entry->movement_direction === 'new' ) : ?>
								NEW
							<?php else : ?>
								&ndash;
							<?php endif; ?>
						</div>
						<div style="font-size: 10px; color: var(--charts-muted); margin-top: 4px;">
							Peak #<?php echo (int) $entry->peak_rank; ?> &middot; <?php echo (int) $entry->weeks_on_chart; ?> Weeks
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');
    <?php include CHARTS_PATH . 'public/assets/css/public.css'; ?>
</style>

<?php get_footer(); ?>
