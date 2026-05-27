<?php
/**
 * Insights Dashboard
 */
global $wpdb;

$filters = array(
	'chart'   => sanitize_text_field( $_GET['chart'] ?? '' ),
	'country' => sanitize_text_field( $_GET['country'] ?? '' ),
	'week'    => sanitize_text_field( $_GET['week'] ?? '' ),
);
$dashboard = ( new \Charts\Services\InsightsDashboardService() )->get_dashboard_data( $filters );
$definitions = $wpdb->get_results( "SELECT DISTINCT chart_type FROM {$wpdb->prefix}charts_definitions ORDER BY chart_type ASC" );
$countries = $wpdb->get_col( "SELECT DISTINCT country_code FROM {$wpdb->prefix}charts_entries WHERE country_code IS NOT NULL AND country_code != '' ORDER BY country_code ASC" );
$weeks = $wpdb->get_col( "SELECT DISTINCT period_start FROM {$wpdb->prefix}charts_periods ORDER BY period_start DESC LIMIT 20" );

if ( ! function_exists( 'charts_insight_card' ) ) {
function charts_insight_card( $title, $rows ) {
	echo '<div class="charts-bento-card" style="padding:24px;">';
	echo '<h3 style="margin-top:0;">' . esc_html( $title ) . '</h3>';
	if ( empty( $rows ) ) {
		echo '<p style="color:#64748b;">No data yet.</p></div>';
		return;
	}
	echo '<div style="display:grid; gap:14px;">';
	foreach ( $rows as $row ) {
		$item_url = home_url( '/lists/' . ( $row->item_type === 'video' ? 'video' : 'track' ) . '/' . ( $row->item_slug ?: sanitize_title( $row->track_name ) ) );
		echo '<a href="' . esc_url( $item_url ) . '" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit;">';
		echo '<img src="' . esc_url( $row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png' ) . '" style="width:44px; height:44px; border-radius:10px; object-fit:cover;">';
		echo '<div style="min-width:0; flex:1;">';
		echo '<div style="font-weight:700;">' . esc_html( $row->track_name ) . '</div>';
		echo '<div style="font-size:12px; color:#64748b;">' . esc_html( $row->artist_names ) . '</div>';
		echo '</div>';
		echo '<div style="text-align:right; font-size:12px;">';
		echo '<div style="font-weight:800;">#' . esc_html( $row->rank_position ) . '</div>';
		echo '<div style="color:#64748b;">' . esc_html( strtoupper( $row->trend_type ?: $row->movement_direction ) ) . ' ' . esc_html( $row->movement_value ) . '</div>';
		echo '</div>';
		echo '</a>';
	}
	echo '</div></div>';
}
}
?>
<div class="wrap charts-admin-wrap premium-light">
	<header class="charts-admin-header">
		<div class="charts-admin-title-group">
			<h1 class="charts-admin-title"><?php esc_html_e( 'Insights Dashboard', 'kontentainment-lists' ); ?></h1>
			<p class="charts-admin-subtitle"><?php esc_html_e( 'Editorial intelligence view across list movement, momentum, and consistency.', 'kontentainment-lists' ); ?></p>
		</div>
	</header>

	<form method="get" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px;">
		<input type="hidden" name="page" value="charts-insights">
		<select name="chart">
			<option value=""><?php esc_html_e( 'All Lists', 'kontentainment-lists' ); ?></option>
			<?php foreach ( $definitions as $definition ) : ?>
				<option value="<?php echo esc_attr( $definition->chart_type ); ?>" <?php selected( $filters['chart'], $definition->chart_type ); ?>><?php echo esc_html( $definition->chart_type ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="country">
			<option value=""><?php esc_html_e( 'All Countries', 'kontentainment-lists' ); ?></option>
			<?php foreach ( $countries as $country ) : ?>
				<option value="<?php echo esc_attr( $country ); ?>" <?php selected( $filters['country'], $country ); ?>><?php echo esc_html( strtoupper( $country ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="week">
			<option value=""><?php esc_html_e( 'All Weeks', 'kontentainment-lists' ); ?></option>
			<?php foreach ( $weeks as $week ) : ?>
				<option value="<?php echo esc_attr( $week ); ?>" <?php selected( $filters['week'], $week ); ?>><?php echo esc_html( $week ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="charts-btn charts-btn-primary" type="submit"><?php esc_html_e( 'Apply Filters', 'kontentainment-lists' ); ?></button>
	</form>

	<div class="charts-bento-grid">
		<?php charts_insight_card( 'Top Rising Tracks', $dashboard['top_rising'] ); ?>
		<?php charts_insight_card( 'Biggest Drops', $dashboard['biggest_drops'] ); ?>
		<?php charts_insight_card( 'New Entries', $dashboard['new_entries'] ); ?>
		<?php charts_insight_card( 'Re-entries', $dashboard['re_entries'] ); ?>
		<?php charts_insight_card( 'Most Consistent', $dashboard['consistent'] ); ?>
		<?php charts_insight_card( 'Highest Momentum', $dashboard['highest_momentum'] ); ?>
	</div>
</div>
