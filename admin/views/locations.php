<?php
/**
 * Locations Management View
 */
global $wpdb;
$table = $wpdb->prefix . 'charts_locations';
$locations = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
?>
<div class="wrap charts-admin-wrap premium-light">
    <header class="charts-admin-header">
        <div>
            <h1 class="charts-admin-title"><?php esc_html_e( 'Territories & Locations', 'charts' ); ?></h1>
            <p class="charts-admin-subtitle"><?php _e( 'Manage imported market intelligence and geographical chart hotspots.', 'charts' ); ?></p>
        </div>
        <div class="charts-admin-actions">
            <a href="<?php echo admin_url( 'admin.php?page=charts-import' ); ?>" class="charts-btn-create">
                <span class="dashicons dashicons-plus" style="margin-right:8px;"></span>
                <?php _e( 'Import New Location', 'charts' ); ?>
            </a>
        </div>
    </header>

    <?php settings_errors( 'charts' ); ?>

    <div class="charts-table-card">
        <table class="charts-table">
            <thead>
                <tr>
                    <th><?php _e( 'Location', 'charts' ); ?></th>
                    <th><?php _e( 'Source', 'charts' ); ?></th>
                    <th><?php _e( 'Intelligence', 'charts' ); ?></th>
                    <th><?php _e( 'Last Updated', 'charts' ); ?></th>
                    <th><?php _e( 'Actions', 'charts' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $locations ) ) : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 60px 20px; color: var(--charts-text-dim);">
                            <div class="dashicons dashicons-location" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.15;"></div>
                            <div style="font-weight: 600; font-size:15px;"><?php _e( 'No intelligence locations found.', 'charts' ); ?></div>
                            <p style="margin:8px 0 0; font-size:13px;"><?php _e( 'Import a YouTube Charts location URL to populate this space.', 'charts' ); ?></p>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $locations as $loc ) : 
                        $artists = json_decode($loc->artist_rankings_json, true) ?: [];
                        $tracks  = json_decode($loc->track_rankings_json, true) ?: [];
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <?php if ($loc->image): ?>
                                        <img src="<?php echo esc_url($loc->image); ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:8px; background:var(--charts-bg); display:flex; align-items:center; justify-content:center; color:var(--charts-text-dim);">
                                            <span class="dashicons dashicons-location"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:800; color:var(--charts-primary);"><?php echo esc_html( $loc->name ); ?></div>
                                        <div style="font-size:10px; color:var(--charts-text-dim);">ID: <?php echo esc_html( $loc->external_id ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:12px; font-weight:700; color:var(--charts-text);"><?php echo esc_html( ucfirst(str_replace('_', ' ', $loc->source_platform)) ); ?></div>
                                <a href="<?php echo esc_url($loc->source_url); ?>" target="_blank" style="font-size:10px; color:var(--charts-accent); text-decoration:none;">View Source &rarr;</a>
                            </td>
                            <td>
                                <div style="font-size:12px; font-weight:700;">
                                    <span style="color:var(--charts-primary);"><?php echo count($artists); ?> Artists</span> • 
                                    <span style="color:var(--charts-accent);"><?php echo count($tracks); ?> Songs</span>
                                </div>
                                <div style="font-size:10px; color:var(--charts-text-dim);"><?php echo esc_html($loc->timeframe_label); ?> <?php echo esc_html($loc->date_range); ?></div>
                            </td>
                            <td>
                                <div style="font-size:12px; font-weight:600;"><?php echo date('M j, Y', strtotime($loc->last_scraped_at)); ?></div>
                                <div style="font-size:10px; color:var(--charts-text-dim);"><?php echo date('H:i', strtotime($loc->last_scraped_at)); ?></div>
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <button type="button" class="charts-btn-back small" onclick="alert('Feature coming soon: Visual Insight Map')">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <form method="post" action="" onsubmit="return confirm('Delete this location intelligence? This will not affect canonical artists/tracks.')">
                                        <?php wp_nonce_field( 'charts_admin_action' ); ?>
                                        <input type="hidden" name="charts_action" value="delete_location">
                                        <input type="hidden" name="id" value="<?php echo $loc->id; ?>">
                                        <button type="submit" class="charts-btn-back small" style="color:#ef4444;">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
