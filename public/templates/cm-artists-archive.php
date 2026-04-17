<?php
/**
 * CM Mobile Experience: Artist Archive
 */

global $wpdb;

// 1. DATA LAYER
$artists = $wpdb->get_results( "
	SELECT a.id, a.display_name, a.slug, a.image,
	       i.weeks_on_chart, i.momentum_score,
	       (SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = a.id AND e.item_type = 'artist') as appearance_count,
	       (SELECT MIN(rank_position) FROM {$wpdb->prefix}charts_entries e WHERE e.item_id = a.id AND e.item_type = 'artist') as peak_rank
	FROM {$wpdb->prefix}charts_artists a
	LEFT JOIN {$wpdb->prefix}charts_intelligence i ON i.entity_id = a.id AND i.entity_type = 'artist'
	GROUP BY a.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, a.display_name ASC
" );

$site_title = get_bloginfo('name');
?>
<!DOCTYPE html>
<!-- CM_RENDERER: public/templates/cm-artists-archive.php -->
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Top Artists — CM</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/cm.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
</head>
<body <?php body_class('cm-body-mobile cm-view-artists-archive'); ?>>

<div class="cm-app-shell">
    <header class="cm-header" style="display:flex; align-items:center; gap:16px; padding: 16px 20px; border-bottom:1px solid var(--cm-divider); position:sticky; top:0; z-index:100; background:var(--cm-surface);">
        <a href="<?php echo home_url('/cm'); ?>" style="color:var(--cm-text); text-decoration:none;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
        <h1 style="font-size: 18px; margin:0; line-height:1;">Top Artists</h1>
    </header>

    <main class="cm-content">
        <div class="cm-charts-list" style="padding: 10px 0;">
            <?php if ( empty($artists) ) : ?>
                <div style="padding: 40px; text-align: center; color: var(--cm-text-muted);">No artists found.</div>
            <?php else : ?>
                <?php foreach ( $artists as $a ) : 
                    $img = !empty($a->image) ? $a->image : CHARTS_URL . 'public/assets/img/placeholder.png';
                ?>
                    <div class="cm-row-item kc-rank-row">
                        <div class="cm-row" style="padding: 14px 20px;">
                            <img src="<?php echo esc_url($img); ?>" class="cm-row-img" style="width:48px; height:48px; border-radius: 50%;">
                            <div class="cm-row-info">
                                <span class="cm-row-title"><?php echo esc_html($a->display_name); ?></span>
                                <span class="cm-row-sub"><?php echo number_format($a->appearance_count); ?> Chart Entries</span>
                            </div>
                            <div class="cm-chevron">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </div>
                        </div>

                        <div class="cm-details-wrapper">
                            <div class="cm-details-inner">
                                <div class="cm-detail-item">
                                    <label>Peak Rank</label>
                                    <span>#<?php echo $a->peak_rank ?: '—'; ?></span>
                                </div>
                                <div class="cm-detail-item">
                                    <label>Weeks On</label>
                                    <span><?php echo intval($a->weeks_on_chart ?: 1); ?></span>
                                </div>
                                <div class="cm-detail-cta" style="grid-column: span 2; padding-top: 12px; border-top: 1px solid var(--cm-divider); margin-top: 4px;">
                                    <a href="<?php echo home_url('/cm/artist/' . $a->slug . '/'); ?>" style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 800; color: var(--cm-primary); text-transform: uppercase; letter-spacing: 0.05em;">
                                        <span>View Artist Profile</span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="<?php echo CHARTS_URL . 'public/assets/js/public.js'; ?>?v=<?php echo CHARTS_VERSION; ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
