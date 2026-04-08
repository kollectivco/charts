<?php
/**
 * Kontentainment Charts — Intelligence Dashboard
 * Focus: Live Signals, Momentum, and Operational Recalculation.
 */
global $wpdb;

$intel_table    = $wpdb->prefix . 'charts_intelligence';
$entries_table  = $wpdb->prefix . 'charts_entries';
$artists_table  = $wpdb->prefix . 'charts_artists';
$tracks_table   = $wpdb->prefix . 'charts_tracks';
$definitions    = $wpdb->prefix . 'charts_definitions';

// 1. Capture Filters
$filter_type   = sanitize_text_field($_GET['intel_type'] ?? 'all');
$filter_market = sanitize_text_field($_GET['intel_market'] ?? 'all');
$filter_period = sanitize_text_field($_GET['intel_period'] ?? 'current');

// 2. Build Structural Filter Clauses
$market_where = "WHERE 1=1";

if ($filter_period === 'current') {
    $latest_period_id = $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}charts_periods");
    if ($latest_period_id) {
        $market_where .= $wpdb->prepare(" AND i.entity_id IN (
            SELECT DISTINCT item_id FROM $entries_table WHERE period_id = %d AND item_type = i.entity_type
        )", $latest_period_id);
    }
}

if ($filter_type !== 'all') {
    $market_where .= $wpdb->prepare(" AND i.entity_type = %s", $filter_type);
}

if ($filter_market !== 'all') {
    $market_where .= $wpdb->prepare(" AND i.entity_id IN (
        SELECT DISTINCT e.item_id FROM $entries_table e 
        JOIN {$wpdb->prefix}charts_sources s ON s.id = e.source_id 
        WHERE s.country_code = %s AND e.item_type = i.entity_type
    )", $filter_market);
}

// 3. Audit Check & KPIs (Filter-Aware)
$stats = [
    'total_intel' => $wpdb->get_var("SELECT COUNT(*) FROM $intel_table i $market_where"),
    'charts'      => $wpdb->get_var($filter_market === 'all' ? "SELECT COUNT(*) FROM $definitions" : $wpdb->prepare("SELECT COUNT(*) FROM $definitions WHERE country_code = %s", $filter_market)),
    'artists'     => $wpdb->get_var("SELECT COUNT(DISTINCT entity_id) FROM $intel_table i $market_where AND entity_type = 'artist'"),
    'tracks'      => $wpdb->get_var("SELECT COUNT(DISTINCT entity_id) FROM $intel_table i $market_where AND entity_type = 'track'"),
    'unmatched'   => $wpdb->get_var("SELECT COUNT(DISTINCT track_name) FROM $entries_table WHERE item_id = 0"),
    'last_sync'   => $wpdb->get_var("SELECT MAX(last_calculated_at) FROM $intel_table"),
];

$has_data = ($stats['total_intel'] > 0);

// Data retrieval
$trending_tracks = [];
$fastest_risers  = [];
$hot_artists     = [];
$stable_tracks   = [];
$long_runners    = [];

if ($has_data) {
    $t_val = ($filter_type === 'all') ? 'track' : $filter_type;

    // 1. Momentum Analysis (Universal Type)
    $trending_assets = $wpdb->get_results("
        SELECT i.*, 
               CASE 
                 WHEN i.entity_type = 'artist' THEN a.display_name 
                 WHEN i.entity_type = 'video' THEN v.title
                 ELSE t.title 
               END as asset_name,
               CASE 
                 WHEN i.entity_type = 'artist' THEN '' 
                 WHEN i.entity_type = 'video' THEN art_v.display_name
                 ELSE art_t.display_name 
               END as asset_sub,
               CASE 
                 WHEN i.entity_type = 'artist' THEN a.image 
                 WHEN i.entity_type = 'video' THEN v.thumbnail
                 ELSE t.cover_image 
               END as asset_img
        FROM $intel_table i
        LEFT JOIN $artists_table a ON i.entity_type = 'artist' AND a.id = i.entity_id
        LEFT JOIN $tracks_table t ON i.entity_type = 'track' AND t.id = i.entity_id
        LEFT JOIN $artists_table art_t ON i.entity_type = 'track' AND art_t.id = t.primary_artist_id
        LEFT JOIN {$wpdb->prefix}charts_videos v ON i.entity_type = 'video' AND v.id = i.entity_id
        LEFT JOIN $artists_table art_v ON i.entity_type = 'video' AND art_v.id = v.primary_artist_id
        $market_where 
        ORDER BY i.momentum_score DESC LIMIT 6
    ");

    // 2. Fastest Risers (Velocity - Restricted to items with growth)
    $fastest_risers = $wpdb->get_results("
        SELECT i.*, 
               CASE WHEN i.entity_type = 'artist' THEN a.display_name ELSE t.title END as asset_name,
               CASE WHEN i.entity_type = 'artist' THEN a.image ELSE t.cover_image END as asset_img
        FROM $intel_table i
        LEFT JOIN $artists_table a ON i.entity_type = 'artist' AND a.id = i.entity_id
        LEFT JOIN $tracks_table t ON (i.entity_type = 'track' OR i.entity_type = 'video') AND t.id = i.entity_id
        $market_where AND (i.entity_type = 'track' OR i.entity_type = 'video' OR i.entity_type = 'artist')
        ORDER BY i.growth_rate DESC LIMIT 6
    ");

    // 3. Hot Artists (Market Authority - ONLY if type is all or artist)
    $hot_artists = [];
    if ($filter_type === 'all' || $filter_type === 'artist') {
        $hot_artists = $wpdb->get_results("
            SELECT i.*, a.display_name, a.image, (
                SELECT COUNT(DISTINCT track_name) FROM $entries_table WHERE artist_names LIKE CONCAT('%', a.display_name, '%')
            ) as unique_entries
            FROM $intel_table i
            JOIN $artists_table a ON a.id = i.entity_id
            $market_where AND i.entity_type = 'artist'
            ORDER BY i.momentum_score DESC LIMIT 6
        ");
    }

    // 4. Stable Entries (High Resilience)
    $stable_assets = $wpdb->get_results("
        SELECT i.*, 
               CASE WHEN i.entity_type = 'artist' THEN a.display_name ELSE t.title END as asset_name,
               CASE WHEN i.entity_type = 'artist' THEN a.image ELSE t.cover_image END as asset_img
        FROM $intel_table i
        LEFT JOIN $artists_table a ON i.entity_type = 'artist' AND a.id = i.entity_id
        LEFT JOIN $tracks_table t ON (i.entity_type = 'track' OR i.entity_type = 'video') AND t.id = i.entity_id
        $market_where AND i.weeks_on_chart > 3
        ORDER BY i.momentum_score DESC LIMIT 4
    ");

    // 5. Longest Running (Archive Legacy)
    $long_runners = $wpdb->get_results("
        SELECT i.*, 
               CASE WHEN i.entity_type = 'artist' THEN a.display_name ELSE t.title END as asset_name,
               CASE WHEN i.entity_type = 'artist' THEN a.image ELSE t.cover_image END as asset_img
        FROM $intel_table i
        LEFT JOIN $artists_table a ON i.entity_type = 'artist' AND a.id = i.entity_id
        LEFT JOIN $tracks_table t ON (i.entity_type = 'track' OR i.entity_type = 'video') AND t.id = i.entity_id
        $market_where
        ORDER BY i.weeks_on_chart DESC LIMIT 4
    ");
}
?>

<div class="charts-admin-wrap premium-light">
    <header class="charts-admin-header intel-nexus-header">
        <div class="header-main">
            <div class="intel-badge"><?php _e( 'Intelligence 5.1', 'charts' ); ?></div>
            <h1 class="charts-admin-title"><?php _e( 'Intelligence Nexus', 'charts' ); ?></h1>
            <p class="charts-admin-subtitle"><?php _e( 'High-fidelity momentum signals, item velocity, and deep catalog analytics.', 'charts' ); ?></p>
        </div>
        <div class="charts-admin-actions">
            <div class="sync-context">
                <span class="sync-label"><?php _e( 'Last Pulse:', 'charts' ); ?></span>
                <span class="sync-time"><?php echo $stats['last_sync'] ? human_time_diff(strtotime($stats['last_sync'])) . ' ago' : 'Never'; ?></span>
            </div>
            <button class="charts-btn-secondary premium-pulse" onclick="recalculateIntelligence()" id="intel-recalc-btn">
                <span class="dashicons dashicons-update"></span>
                <?php _e( 'Re-polarize Signals', 'charts' ); ?>
            </button>
        </div>
    </header>

    <!-- KPI COMMAND CENTER -->
    <div class="intel-kpi-grid">
        <div class="kpi-block">
            <div class="kpi-icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?php echo number_format($stats['charts']); ?></span>
                <span class="kpi-label"><?php _e( 'Active Charts', 'charts' ); ?></span>
            </div>
        </div>
        <div class="kpi-block">
            <div class="kpi-icon"><span class="dashicons dashicons-admin-users"></span></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?php echo number_format($stats['artists']); ?></span>
                <span class="kpi-label"><?php _e( 'Verified Artists', 'charts' ); ?></span>
            </div>
        </div>
        <div class="kpi-block">
            <div class="kpi-icon"><span class="dashicons dashicons-media-audio"></span></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?php echo number_format($stats['tracks']); ?></span>
                <span class="kpi-label"><?php _e( 'Audio Entities', 'charts' ); ?></span>
            </div>
        </div>
        <div class="kpi-block is-warning <?php echo $stats['unmatched'] > 0 ? 'has-pulse' : ''; ?>">
            <div class="kpi-icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="kpi-meta">
                <span class="kpi-value"><?php echo number_format($stats['unmatched']); ?></span>
                <span class="kpi-label"><?php _e( 'Unmatched Strings', 'charts' ); ?></span>
            </div>
            <?php if ($stats['unmatched'] > 0) : ?>
                <a href="<?php echo admin_url('admin.php?page=charts-matching'); ?>" class="kpi-action"><?php _e( 'Resolve', 'charts' ); ?> &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="intel-filter-nexus">
        <form method="get" action="" class="filter-nexus-form">
            <input type="hidden" name="page" value="charts-intelligence">
            
            <div class="nexus-filter-group">
                <label><?php _e( 'Intelligence Market', 'charts' ); ?></label>
                <select name="intel_market" onchange="this.form.submit()">
                    <option value="all"><?php _e( 'Global Signal', 'charts' ); ?></option>
                    <?php 
                    $markets = get_option('charts_markets', []);
                    foreach ($markets as $m) : ?>
                        <option value="<?php echo esc_attr($m['code']); ?>" <?php selected($filter_market, $m['code']); ?>>
                            <?php echo esc_html($m['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nexus-filter-group">
                <label><?php _e( 'Entity Tier', 'charts' ); ?></label>
                <select name="intel_type" onchange="this.form.submit()">
                    <option value="all" <?php selected($filter_type, 'all'); ?>><?php _e( 'Combined Dataset', 'charts' ); ?></option>
                    <option value="track" <?php selected($filter_type, 'track'); ?>><?php _e( 'Tracks Only', 'charts' ); ?></option>
                    <option value="artist" <?php selected($filter_type, 'artist'); ?>><?php _e( 'Artists Only', 'charts' ); ?></option>
                    <option value="video" <?php selected($filter_type, 'video'); ?>><?php _e( 'Videos Only', 'charts' ); ?></option>
                </select>
            </div>

            <div class="nexus-filter-group">
                <label><?php _e( 'Period Range', 'charts' ); ?></label>
                <select name="intel_period" onchange="this.form.submit()">
                    <option value="current" <?php selected($filter_period, 'current'); ?>><?php _e( 'Current Sync Window', 'charts' ); ?></option>
                    <option value="all" <?php selected($filter_period, 'all'); ?>><?php _e( 'Historical Average', 'charts' ); ?></option>
                </select>
            </div>
        </form>
    </div>

    <?php if (!$has_data) : ?>
        <div class="charts-card intel-empty-state">
            <div class="nexus-core-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <h2><?php _e( 'Intelligence Nexus Offline', 'charts' ); ?></h2>
            <p><?php _e( 'The analytical engine requires a data sweep to populate the nexus.', 'charts' ); ?></p>
            <button onclick="recalculateIntelligence()" class="charts-btn-primary large-cta">
                <?php _e( 'Initialize Signal Recalculation', 'charts' ); ?>
            </button>
        </div>
    <?php else : ?>
        <div class="intel-workspace">
            
            <div class="intel-main-grid">
                <!-- Momentum Column -->
                <div class="intel-block-card span-6">
                    <header class="block-header">
                        <div class="block-title-wrap">
                            <span class="block-tag"><?php _e( 'Momentum', 'charts' ); ?></span>
                            <h3><?php _e( 'Deep Trend Analysis', 'charts' ); ?></h3>
                        </div>
                        <span class="signal-dot pulsing"></span>
                    </header>
                    <div class="block-body no-padding">
                        <table class="intel-mini-table">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Asset', 'charts' ); ?></th>
                                    <th><?php _e( 'Momentum', 'charts' ); ?></th>
                                    <th class="text-right"><?php _e( 'Signal', 'charts' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($trending_assets)) : ?>
                                    <tr><td colspan="3" class="text-center" style="padding: 40px; color: #94a3b8;"><?php _e( 'No momentum signals detected for this segment.', 'charts' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ($trending_assets as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="asset-flex">
                                                <img src="<?php echo esc_url($t->asset_img ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="asset-thumb">
                                                <div class="asset-meta">
                                                    <span class="asset-name"><?php echo esc_html($t->asset_name); ?></span>
                                                    <?php if ($t->asset_sub): ?>
                                                        <span class="asset-sub"><?php echo esc_html($t->asset_sub); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="momentum-val"><?php echo number_format($t->momentum_score, 1); ?></td>
                                        <td class="text-right">
                                            <span class="intel-status-tag is-<?php echo esc_attr($t->trend_status); ?>">
                                                <?php echo esc_html(strtoupper($t->trend_status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Velocity Column -->
                <div class="intel-block-card span-6">
                    <header class="block-header">
                        <div class="block-title-wrap">
                            <span class="block-tag purple"><?php _e( 'Velocity', 'charts' ); ?></span>
                            <h3><?php _e( 'Fastest Rising Assets', 'charts' ); ?></h3>
                        </div>
                    </header>
                    <div class="block-body no-padding">
                        <table class="intel-mini-table">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Asset', 'charts' ); ?></th>
                                    <th><?php _e( 'Velocity', 'charts' ); ?></th>
                                    <th class="text-right"><?php _e( 'Acceleration', 'charts' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fastest_risers)) : ?>
                                    <tr><td colspan="3" class="text-center" style="padding: 40px; color: #94a3b8;"><?php _e( 'No rising signals in the current window.', 'charts' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ($fastest_risers as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="asset-flex">
                                                <img src="<?php echo esc_url($t->asset_img ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="asset-thumb">
                                                <div class="asset-meta">
                                                    <span class="asset-name"><?php echo esc_html($t->asset_name); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="growth-val">+<?php echo number_format($t->growth_rate, 1); ?>%</td>
                                        <td class="text-right">
                                            <div class="v-track-bg">
                                                <div class="v-track-fill" style="width: <?php echo min(100, $t->growth_rate * 4); ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Artist Authority -->
                <?php if ($filter_type === 'all' || $filter_type === 'artist') : ?>
                <div class="intel-block-card span-12 no-margin">
                    <header class="block-header">
                        <div class="block-title-wrap">
                            <span class="block-tag success"><?php _e( 'Authority', 'charts' ); ?></span>
                            <h3><?php _e( 'Artist Market Share & Dominance', 'charts' ); ?></h3>
                        </div>
                    </header>
                    <div class="block-body no-padding">
                        <table class="intel-mini-table is-wide">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Master Artist', 'charts' ); ?></th>
                                    <th><?php _e( 'Market Authority', 'charts' ); ?></th>
                                    <th><?php _e( 'Unique Entries', 'charts' ); ?></th>
                                    <th class="text-right"><?php _e( 'Authority Tier', 'charts' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hot_artists)) : ?>
                                    <tr><td colspan="4" class="text-center" style="padding: 60px; color: #94a3b8;"><?php _e( 'No authority signals found for this market.', 'charts' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ($hot_artists as $a): ?>
                                    <tr>
                                        <td>
                                            <div class="asset-flex large">
                                                <div class="avatar-wrap">
                                                    <img src="<?php echo esc_url($a->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="asset-avatar">
                                                    <?php if($a->momentum_score > 70): ?>
                                                        <span class="authority-dot"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="asset-name"><?php echo esc_html($a->display_name); ?></span>
                                            </div>
                                        </td>
                                        <td class="score-val"><?php echo number_format($a->momentum_score, 1); ?></td>
                                        <td class="entries-val"><?php echo (int)$a->unique_entries; ?></td>
                                        <td class="text-right">
                                            <span class="tier-badge <?php echo $a->momentum_score > 60 ? 'is-elite' : 'is-rising'; ?>">
                                                <?php echo $a->momentum_score > 60 ? 'Elite Authority' : 'Rising Star'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- NEW: Stability & Archive -->
                <div class="intel-block-card span-12">
                    <div class="intel-subgrid">
                        <div class="subgrid-col">
                            <h4 class="subgrid-title"><?php _e( 'Chart Resilience (Stable)', 'charts' ); ?></h4>
                            <div class="mini-asset-list">
                                <?php if (empty($stable_assets)) : ?>
                                    <div style="grid-column: span 2; padding: 20px; color: #94a3b8; font-size: 12px;"><?php _e( 'No stable assets in this segment.', 'charts' ); ?></div>
                                <?php else : ?>
                                    <?php foreach ($stable_assets as $s): ?>
                                        <div class="mini-asset-item">
                                            <img src="<?php echo esc_url($s->asset_img ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="mini-thumb">
                                            <div class="mini-meta">
                                                <strong><?php echo esc_html($s->asset_name); ?></strong>
                                                <span><?php echo (int)$s->weeks_on_chart; ?> <?php _e( 'Weeks Constant', 'charts' ); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="subgrid-col border-left">
                            <h4 class="subgrid-title"><?php _e( 'Chart Legacy (Longest)', 'charts' ); ?></h4>
                            <div class="mini-asset-list">
                                <?php if (empty($long_runners)) : ?>
                                    <div style="grid-column: span 2; padding: 20px; color: #94a3b8; font-size: 12px;"><?php _e( 'Empty legacy archives.', 'charts' ); ?></div>
                                <?php else : ?>
                                    <?php foreach ($long_runners as $l): ?>
                                        <div class="mini-asset-item">
                                            <img src="<?php echo esc_url($l->asset_img ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="mini-thumb">
                                            <div class="mini-meta">
                                                <strong><?php echo esc_html($l->asset_name); ?></strong>
                                                <span><?php echo (int)$l->weeks_on_chart; ?> <?php _e( 'Weeks on Archive', 'charts' ); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <style>
        /* Intelligence Nexus: Advanced Analytics UI */
        .intel-nexus-header {
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 40px;
            background: #fff;
            border-radius: 24px;
            border: 1px solid var(--charts-border);
            box-shadow: 0 10px 40px rgba(0,0,0,0.02);
        }
        .intel-badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.1);
            color: var(--charts-primary);
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
        }
        .sync-context { margin-bottom: 12px; text-align: right; }
        .sync-label { font-size: 11px; font-weight: 700; color: var(--charts-text-dim); }
        .sync-time { font-size: 13px; font-weight: 800; color: var(--charts-primary); }
        
        .premium-pulse .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* KPI Ribbon */
        .intel-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        .kpi-block {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--charts-border);
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        .kpi-block:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.05); }
        .kpi-icon {
            width: 52px; height: 52px; border-radius: 12px;
            background: #f8fafc; color: var(--charts-primary);
            display: flex; align-items: center; justify-content: center;
        }
        .kpi-icon .dashicons { font-size: 24px; width: 24px; height: 24px; }
        .kpi-value { display: block; font-size: 28px; font-weight: 950; letter-spacing: -0.04em; line-height: 1; }
        .kpi-label { font-size: 12px; font-weight: 700; color: var(--charts-text-dim); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; display: block; }

        .kpi-block.is-warning { background: #fffcf0; border-color: #ffecb3; }
        .kpi-block.is-warning .kpi-icon { background: #fef3c7; color: #d97706; }
        .kpi-block.is-warning .kpi-value { color: #92400e; }
        .kpi-action { position: absolute; top: 12px; right: 16px; font-size: 11px; font-weight: 800; color: #6366f1; text-decoration: none; border-bottom: 1px solid transparent; }
        .kpi-action:hover { border-color: initial; }

        /* Filter Nexus */
        .intel-filter-nexus {
            background: #f1f5f9;
            padding: 20px 32px;
            border-radius: 16px;
            margin-bottom: 32px;
        }
        .filter-nexus-form { display: flex; gap: 40px; align-items: center; }
        .nexus-filter-group { display: flex; flex-direction: column; gap: 6px; }
        .nexus-filter-group label { font-size: 10px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; }
        .nexus-filter-group select {
            background: transparent; border: none; padding: 0; font-size: 14px; font-weight: 800; color: var(--charts-primary); cursor: pointer;
            box-shadow: none !important;
        }

        /* Workspace Grid */
        .intel-main-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 32px; }
        .span-6 { grid-column: span 6; }
        .span-12 { grid-column: span 12; }
        .no-margin { margin-bottom: 0; }

        .intel-block-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--charts-border);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .intel-block-card:hover { box-shadow: 0 20px 40px rgba(0,0,0,0.03); }
        .block-header {
            padding: 28px 32px;
            background: #fafafa;
            border-bottom: 1px solid var(--charts-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .block-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--charts-primary);
            margin-bottom: 6px;
        }
        .block-tag.purple { color: #6366f1; }
        .block-tag.success { color: #10b981; }
        .block-header h3 { margin: 0; font-size: 16px; font-weight: 900; letter-spacing: -0.01em; }

        .signal-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }
        .signal-dot.pulsing { animation: intel-pulse 2s infinite; }
        @keyframes intel-pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Mini Tables */
        .intel-mini-table { width: 100%; border-collapse: collapse; }
        .intel-mini-table th { padding: 16px 32px; text-align: left; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        .intel-mini-table td { padding: 16px 32px; border-bottom: 1px solid #f8fafc; }
        .asset-flex { display: flex; align-items: center; gap: 12px; }
        .asset-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; }
        .asset-name { display: block; font-size: 14px; font-weight: 800; color: var(--charts-primary); line-height: 1.2; }
        .asset-sub { display: block; font-size: 11px; font-weight: 600; color: #94a3b8; margin-top: 2px; }
        .momentum-val { font-size: 18px; font-weight: 950; color: var(--charts-primary); letter-spacing: -0.02em; }
        .growth-val { font-size: 16px; font-weight: 900; color: #10b981; }

        .intel-status-tag { font-size: 9px; font-weight: 900; padding: 4px 8px; border-radius: 6px; }
        .intel-status-tag.is-rising { background: #ecfdf5; color: #059669; }
        .intel-status-tag.is-falling { background: #fef2f2; color: #dc2626; }
        .intel-status-tag.is-new { background: #eff6ff; color: #2563eb; }
        .intel-status-tag.is-stable { background: #f8fafc; color: #64748b; }

        .v-track-bg { width: 60px; height: 6px; background: #f1f5f9; border-radius: 3px; position: relative; }
        .v-track-fill { height: 100%; background: #10b981; border-radius: 3px; }

        .asset-flex.large .asset-avatar { width: 56px; height: 56px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .avatar-wrap { position: relative; }
        .authority-dot { position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #6366f1; border-radius: 50%; border: 2px solid #fff; }
        .score-val { font-size: 24px; font-weight: 950; color: #6366f1; }
        .entries-val { font-size: 16px; font-weight: 800; }
        .tier-badge { font-size: 10px; font-weight: 800; padding: 6px 14px; border-radius: 99px; }
        .tier-badge.is-elite { background: #e0e7ff; color: #4338ca; }
        .tier-badge.is-rising { background: #f1f5f9; color: #64748b; }

        /* Subgrid (Stability/Legacy) */
        .intel-subgrid { display: flex; }
        .subgrid-col { flex: 1; padding: 32px; }
        .subgrid-col.border-left { border-left: 1px solid #f1f5f9; }
        .subgrid-title { margin: 0 0 20px; font-size: 13px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; }
        .mini-asset-list { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .mini-asset-item { display: flex; align-items: center; gap: 12px; }
        .mini-thumb { width: 36px; height: 36px; border-radius: 6px; }
        .mini-meta strong { display: block; font-size: 12px; font-weight: 800; color: var(--charts-primary); line-height: 1.1; }
        .mini-meta span { font-size: 10px; color: #94a3b8; font-weight: 600; }

        .text-right { text-align: right; }
        
        .intel-empty-state { padding: 100px; text-align: center; }
        .nexus-core-icon { font-size: 72px; color: #e2e8f0; margin-bottom: 24px; }
        .intel-empty-state h2 { font-weight: 950; letter-spacing: -0.02em; }
        .large-cta { padding: 16px 40px; font-size: 15px; font-weight: 900; border-radius: 30px; margin-top: 32px; }

        @keyframes charts-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <script>
        function recalculateIntelligence() {
            const btn = document.getElementById('intel-recalc-btn');
            if (!btn) return;
            
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 5px; vertical-align: middle; animation: charts-spin 1s linear infinite;"></span> Calibrating Nexus...';
            
            jQuery.post(ajaxurl, {
                action: 'charts_recalculate_intel',
                nonce: '<?php echo wp_create_nonce("charts_admin_action"); ?>'
            }, function(res) {
                if (res.success) {
                    window.ChartsToast.show('success', 'Intelligence Nexus successfully re-polarized.', 'Sync Matrix Complete');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.ChartsToast.show('error', res.data.message || 'Calibration failure.', 'Nexus Error');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }).fail(function() {
                window.ChartsToast.show('error', 'Connection lost during nexus sync.', 'Critical Link Failure');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    </script>
</div>
