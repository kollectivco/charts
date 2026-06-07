<?php
/**
 * Kontentainment Charts — Intelligence Nexus (Admin)
 * Bloomberg Terminal Monochrome Design
 */
global $wpdb;

$intel_table   = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';
$artists_table = $wpdb->prefix . 'charts_artists';
$tracks_table  = $wpdb->prefix . 'charts_tracks';
$defs_table    = $wpdb->prefix . 'charts_definitions';

// ── Filters ─────────────────────────────────────────────
$filter_type   = sanitize_text_field($_GET['intel_type'] ?? 'all');
$filter_market = sanitize_text_field($_GET['intel_market'] ?? 'all');

$market_join  = '';
$market_where = '';
if ($filter_market !== 'all') {
    $market_join  = " JOIN {$entries_table} ef ON ef.item_id = i.entity_id AND ef.item_type = i.entity_type";
    $market_join .= " JOIN {$wpdb->prefix}charts_sources sf ON sf.id = ef.source_id";
    $market_where = $wpdb->prepare(" AND sf.country_code = %s", $filter_market);
}

$type_where_track  = " AND i.entity_type = 'track'";
$type_where_artist = " AND i.entity_type = 'artist'";

// ── KPI: Fastest Rising Song ────────────────────────────
$fastest_rising_song = $wpdb->get_row("
    SELECT i.growth_rate, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.growth_rate IS NOT NULL $market_where
    ORDER BY i.growth_rate DESC LIMIT 1
");

// ── KPI: Fastest Rising Artist ──────────────────────────
$fastest_rising_artist = $wpdb->get_row("
    SELECT i.growth_rate, a.display_name as entity_name
    FROM $intel_table i
    JOIN $artists_table a ON a.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'artist' AND i.growth_rate IS NOT NULL $market_where
    ORDER BY i.growth_rate DESC LIMIT 1
");

// ── KPI: Most Stable Song ───────────────────────────────
$most_stable_song = $wpdb->get_row("
    SELECT i.stability_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.stability_score IS NOT NULL $market_where
    ORDER BY i.stability_score DESC LIMIT 1
");

// ── KPI: Most Stable Artist ────────────────────────────
$most_stable_artist = $wpdb->get_row("
    SELECT i.stability_score, a.display_name as entity_name
    FROM $intel_table i
    JOIN $artists_table a ON a.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'artist' AND i.stability_score IS NOT NULL $market_where
    ORDER BY i.stability_score DESC LIMIT 1
");

// ── KPI: Largest Gain (highest positive growth_rate track) ──
$largest_gain = $wpdb->get_row("
    SELECT i.growth_rate, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.growth_rate > 0 $market_where
    ORDER BY i.growth_rate DESC LIMIT 1
");

// ── KPI: Largest Drop (lowest negative growth_rate track) ──
$largest_drop = $wpdb->get_row("
    SELECT i.growth_rate, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.growth_rate < 0 $market_where
    ORDER BY i.growth_rate ASC LIMIT 1
");

// ── KPI: Max Momentum ──────────────────────────────────
$max_momentum = $wpdb->get_row("
    SELECT i.momentum_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' $market_where
    ORDER BY i.momentum_score DESC LIMIT 1
");

// ── KPI: Max Viral ─────────────────────────────────────
$max_viral = $wpdb->get_row("
    SELECT i.viral_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' $market_where
    ORDER BY i.viral_score DESC LIMIT 1
");

// ── Trend Radar: Categorize tracks by momentum ─────────
$radar_exploding = $wpdb->get_results("
    SELECT i.momentum_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.momentum_score > 80 $market_where
    ORDER BY i.momentum_score DESC LIMIT 8
");
$radar_rising = $wpdb->get_results("
    SELECT i.momentum_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.momentum_score > 60 AND i.momentum_score <= 80 $market_where
    ORDER BY i.momentum_score DESC LIMIT 8
");
$radar_stable = $wpdb->get_results("
    SELECT i.momentum_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.momentum_score >= 40 AND i.momentum_score <= 60 $market_where
    ORDER BY i.momentum_score DESC LIMIT 8
");
$radar_falling = $wpdb->get_results("
    SELECT i.momentum_score, t.title as entity_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'track' AND i.momentum_score < 40 $market_where
    ORDER BY i.momentum_score DESC LIMIT 8
");

// ── Entity Details Table (filtered) ─────────────────────
$entity_type_clause = '';
if ($filter_type === 'track') {
    $entity_type_clause = " AND i.entity_type = 'track'";
} elseif ($filter_type === 'artist') {
    $entity_type_clause = " AND i.entity_type = 'artist'";
}

$entity_rows = $wpdb->get_results("
    SELECT i.*,
           CASE
             WHEN i.entity_type = 'artist' THEN a.display_name
             ELSE t.title
           END as entity_name
    FROM $intel_table i
    LEFT JOIN $artists_table a ON i.entity_type = 'artist' AND a.id = i.entity_id
    LEFT JOIN $tracks_table t ON i.entity_type = 'track' AND t.id = i.entity_id
    $market_join
    WHERE 1=1 $entity_type_clause $market_where
    ORDER BY i.momentum_score DESC
    LIMIT 30
");

// ── Artist Metrics Table ────────────────────────────────
$artist_rows = $wpdb->get_results("
    SELECT i.*, a.display_name as entity_name
    FROM $intel_table i
    JOIN $artists_table a ON a.id = i.entity_id
    $market_join
    WHERE i.entity_type = 'artist' $market_where
    ORDER BY i.artist_power_score DESC
    LIMIT 15
");

// ── Market Health ───────────────────────────────────────
$first_chart_id = $wpdb->get_var("SELECT MIN(id) FROM $defs_table");
$market_health  = [];
if ($first_chart_id) {
    $market_health = \Charts\Services\IntelligenceEngine::get_market_health($first_chart_id);
}

// ── Misc ────────────────────────────────────────────────
$last_sync = $wpdb->get_var("SELECT MAX(last_calculated_at) FROM $intel_table");
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM $intel_table");
$has_data = ($total_records > 0);

$markets = get_option('charts_markets', []);
$nonce   = wp_create_nonce('charts_admin_action');
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════
   INTELLIGENCE NEXUS — Bento UI Design System
   ═══════════════════════════════════════════════════════ */

.bento-wrap {
    background: #f8fafc;
    padding: 24px;
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    box-sizing: border-box;
    color: #0f172a;
}
.bento-wrap * { box-sizing: border-box; }

/* ── Header ── */
.bento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.bento-header-left h1 {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px;
    letter-spacing: -0.02em;
}
.bento-header-left h1 span {
    color: #10b981;
}
.bento-header-meta {
    font-size: 11px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.bento-header-meta .bento-sync-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    animation: bento-pulse 2s infinite;
}
.bento-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.bento-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.18s ease;
    font-family: 'Inter', -apple-system, sans-serif;
    white-space: nowrap;
}
.bento-btn-ghost {
    background: #fff;
    border-color: #e2e8f0;
    color: #374151;
}
.bento-btn-ghost:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.bento-btn-amber {
    background: #fff;
    border-color: #fcd34d;
    color: #b45309;
}
.bento-btn-amber:hover {
    background: #fffbeb;
    border-color: #f59e0b;
    color: #92400e;
}
.bento-btn-primary {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}
.bento-btn-primary:hover {
    background: #059669;
    border-color: #059669;
    color: #fff;
}
.bento-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* ── Filter Bar ── */
.bento-filter-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.bento-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.bento-filter-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
}
.bento-filter-select {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    font-family: 'Inter', -apple-system, sans-serif;
    font-size: 13px;
    font-weight: 500;
    padding: 7px 32px 7px 12px;
    border-radius: 8px;
    cursor: pointer;
    outline: none;
    min-width: 170px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    transition: border-color 0.15s;
}
.bento-filter-select:focus {
    border-color: #10b981;
    background-color: #fff;
}
.bento-filter-total {
    margin-left: auto;
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    white-space: nowrap;
    padding-bottom: 3px;
}
.bento-filter-total strong {
    color: #0f172a;
    font-weight: 700;
}

/* ── Bento Card Base ── */
.bento-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
}

/* ── KPI Grid ── */
.bento-grid-kpi {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.bento-kpi-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    border-top: 3px solid #e2e8f0;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.18s ease, transform 0.18s ease;
}
.bento-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.10), 0 8px 24px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}
.bento-kpi-card.kpi-green  { border-top-color: #10b981; }
.bento-kpi-card.kpi-blue   { border-top-color: #3b82f6; }
.bento-kpi-card.kpi-rose   { border-top-color: #f43f5e; }
.bento-kpi-card.kpi-violet { border-top-color: #8b5cf6; }
.bento-kpi-card.kpi-amber  { border-top-color: #f59e0b; }

.bento-kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 8px;
}
.bento-kpi-value {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
}
.bento-kpi-value.val-green  { color: #10b981; }
.bento-kpi-value.val-blue   { color: #3b82f6; }
.bento-kpi-value.val-rose   { color: #f43f5e; }
.bento-kpi-value.val-violet { color: #8b5cf6; }
.bento-kpi-value.val-amber  { color: #f59e0b; }

.bento-kpi-name {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}
.bento-kpi-icon {
    position: absolute;
    top: 18px;
    right: 18px;
    font-size: 20px;
    opacity: 0.15;
}

/* ── Two Column Row ── */
.bento-two-col {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

/* ── Section Header ── */
.bento-section-header {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.bento-section-header-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.bento-section-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: 0.04em;
}
.bento-badge-green  { background: #d1fae5; color: #065f46; }
.bento-badge-blue   { background: #dbeafe; color: #1e40af; }
.bento-badge-violet { background: #ede9fe; color: #5b21b6; }
.bento-badge-amber  { background: #fef3c7; color: #92400e; }
.bento-badge-rose   { background: #ffe4e6; color: #9f1239; }
.bento-badge-muted  { background: #f1f5f9; color: #64748b; }

/* ── Trend Radar ── */
.bento-radar-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
}
.bento-radar-col {
    padding: 0 16px 4px;
    border-left: 3px solid #e2e8f0;
}
.bento-radar-col:first-child { padding-left: 0; border-left: none; }
.bento-radar-col.radar-exploding { border-left-color: #f43f5e; }
.bento-radar-col.radar-rising    { border-left-color: #10b981; }
.bento-radar-col.radar-stable    { border-left-color: #3b82f6; }
.bento-radar-col.radar-falling   { border-left-color: #94a3b8; }

.bento-radar-col-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 5px;
}
.bento-radar-col.radar-exploding .bento-radar-col-title { color: #f43f5e; }
.bento-radar-col.radar-rising    .bento-radar-col-title { color: #10b981; }
.bento-radar-col.radar-stable    .bento-radar-col-title { color: #3b82f6; }
.bento-radar-col.radar-falling   .bento-radar-col-title { color: #94a3b8; }

.bento-radar-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    gap: 6px;
    border-bottom: 1px solid #f8fafc;
}
.bento-radar-item:last-child { border-bottom: none; }
.bento-radar-item-name {
    font-size: 12px;
    color: #334155;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    flex: 1;
}
.bento-score-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 99px;
    flex-shrink: 0;
    white-space: nowrap;
}
.pill-rose   { background: #ffe4e6; color: #f43f5e; }
.pill-green  { background: #d1fae5; color: #059669; }
.pill-blue   { background: #dbeafe; color: #3b82f6; }
.pill-muted  { background: #f1f5f9; color: #64748b; }

.bento-radar-empty {
    font-size: 11px;
    color: #94a3b8;
    font-style: italic;
    padding: 8px 0;
}

/* ── Market Health ── */
.bento-health-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.bento-gauge-row {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.bento-gauge-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.bento-gauge-label {
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.bento-gauge-value {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}
.bento-gauge-track {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}
.bento-gauge-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}
.gauge-green  { background: #10b981; }
.gauge-rose   { background: #f43f5e; }
.gauge-blue   { background: #3b82f6; }
.gauge-amber  { background: #f59e0b; }
.gauge-violet { background: #8b5cf6; }

.bento-health-cached-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    background: #fef3c7;
    color: #b45309;
    letter-spacing: 0.04em;
    margin-left: 8px;
}

/* ── Tables ── */
.bento-table-wrap {
    overflow-x: auto;
}
.bento-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.bento-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    border-bottom: 2px solid #f1f5f9;
    background: #f8fafc;
    white-space: nowrap;
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
    position: sticky;
    top: 0;
    z-index: 1;
}
.bento-table thead th:hover { color: #0f172a; }
.bento-table thead th.sorted-asc::after  { content: ' ↑'; color: #10b981; }
.bento-table thead th.sorted-desc::after { content: ' ↓'; color: #f43f5e; }
.bento-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #374151;
}
.bento-table tbody tr:hover td { background: #f8fafc; }
.bento-table tbody tr:last-child td { border-bottom: none; }

.bento-td-name {
    font-weight: 600;
    color: #0f172a !important;
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bento-type-badge {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}
.badge-track  { background: #d1fae5; color: #065f46; }
.badge-artist { background: #ede9fe; color: #5b21b6; }

/* Mini progress bars in table */
.bento-bar-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 100px;
}
.bento-bar-num {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    min-width: 34px;
    text-align: right;
    white-space: nowrap;
}
.bento-bar-track {
    width: 80px;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    flex-shrink: 0;
}
.bento-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.4s ease;
}
.bfill-green  { background: #10b981; }
.bfill-blue   { background: #3b82f6; }
.bfill-violet { background: #8b5cf6; }
.bfill-amber  { background: #f59e0b; }
.bfill-rose   { background: #f43f5e; }

.bento-growth-up   { color: #10b981; font-weight: 700; }
.bento-growth-down { color: #f43f5e; font-weight: 700; }

.bento-trend-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}
.trend-rising  { background: #d1fae5; color: #059669; }
.trend-falling { background: #ffe4e6; color: #f43f5e; }
.trend-stable  { background: #dbeafe; color: #3b82f6; }

.bento-dash { color: #94a3b8; font-weight: 400; }

/* ── Pagination ── */
.bento-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0 0;
    margin-top: 4px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 8px;
}
.bento-page-info {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
}
.bento-page-btns {
    display: flex;
    gap: 6px;
}
.bento-page-btn {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #374151;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: all 0.15s;
}
.bento-page-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.bento-page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

/* ── Empty State ── */
.bento-empty {
    text-align: center;
    padding: 80px 40px;
    color: #64748b;
}
.bento-empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.bento-empty h2 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 8px;
}
.bento-empty p {
    font-size: 13px;
    margin-bottom: 24px;
    color: #64748b;
}

/* ── Toast ── */
.bento-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.bento-toast {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 18px;
    min-width: 280px;
    max-width: 360px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.14);
    display: flex;
    align-items: flex-start;
    gap: 12px;
    pointer-events: all;
    animation: bento-slide-in 0.25s ease;
    border-left: 4px solid #10b981;
}
.bento-toast.toast-error   { border-left-color: #f43f5e; }
.bento-toast.toast-warning { border-left-color: #f59e0b; }
.bento-toast-icon { font-size: 18px; flex-shrink: 0; }
.bento-toast-body { flex: 1; }
.bento-toast-title { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
.bento-toast-msg   { font-size: 12px; color: #64748b; }
.bento-toast-dismiss {
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
    color: #94a3b8;
    padding: 0;
    flex-shrink: 0;
    line-height: 1;
}

@keyframes bento-slide-in {
    from { opacity: 0; transform: translateX(20px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes bento-slide-out {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(20px); }
}
@keyframes bento-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* ── Responsive ── */
@media (max-width: 1200px) {
    .bento-grid-kpi   { grid-template-columns: repeat(2, 1fr); }
    .bento-two-col    { grid-template-columns: 1fr; }
    .bento-radar-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .bento-radar-col  { border-left: none; border-top: 3px solid #e2e8f0; padding-left: 0; padding-top: 12px; }
    .bento-radar-col.radar-exploding { border-top-color: #f43f5e; }
    .bento-radar-col.radar-rising    { border-top-color: #10b981; }
    .bento-radar-col.radar-stable    { border-top-color: #3b82f6; }
    .bento-radar-col.radar-falling   { border-top-color: #94a3b8; }
    .bento-radar-col:first-child     { border-top: 3px solid #f43f5e; padding-top: 12px; }
}
@media (max-width: 768px) {
    .bento-grid-kpi   { grid-template-columns: repeat(2, 1fr); }
    .bento-radar-grid { grid-template-columns: 1fr; }
    .bento-header     { flex-direction: column; align-items: flex-start; }
    .bento-filter-bar { flex-direction: column; align-items: stretch; }
}
</style>

<div class="bento-wrap">

<!-- ═══════════════════════════════════════════════════════
     TOAST CONTAINER
     ═══════════════════════════════════════════════════════ -->
<div class="bento-toast-container" id="bento-toast-container"></div>

<!-- ═══════════════════════════════════════════════════════
     HEADER BAR
     ═══════════════════════════════════════════════════════ -->
<div class="bento-header">
    <div class="bento-header-left">
        <h1>Intelligence <span>Nexus</span></h1>
        <div class="bento-header-meta">
            <span class="bento-sync-dot"></span>
            <?php if ($last_sync) : ?>
                Last sync <strong><?php echo human_time_diff(strtotime($last_sync)); ?> ago</strong>
                &nbsp;·&nbsp; <?php echo number_format($total_records); ?> records indexed
            <?php else : ?>
                <strong>Never synced</strong>
            <?php endif; ?>
        </div>
    </div>
    <div class="bento-header-actions">
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=charts_export_intelligence'), 'charts_export_intelligence')); ?>"
           class="bento-btn bento-btn-amber">
            ↓ Export CSV
        </a>
        <button class="bento-btn bento-btn-primary" onclick="recalculateIntelligence()" id="intel-recalc-btn">
            ⟳ Recalculate
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FILTER BAR
     ═══════════════════════════════════════════════════════ -->
<form method="get" action="" class="bento-filter-bar">
    <input type="hidden" name="page" value="charts-intelligence">
    <div class="bento-filter-group">
        <label class="bento-filter-label"><?php _e('Entity Type', 'charts'); ?></label>
        <select name="intel_type" class="bento-filter-select" onchange="this.form.submit()">
            <option value="all"    <?php selected($filter_type, 'all'); ?>><?php _e('All Entities', 'charts'); ?></option>
            <option value="track"  <?php selected($filter_type, 'track'); ?>><?php _e('Tracks Only', 'charts'); ?></option>
            <option value="artist" <?php selected($filter_type, 'artist'); ?>><?php _e('Artists Only', 'charts'); ?></option>
        </select>
    </div>
    <div class="bento-filter-group">
        <label class="bento-filter-label"><?php _e('Market / Country', 'charts'); ?></label>
        <select name="intel_market" class="bento-filter-select" onchange="this.form.submit()">
            <option value="all"><?php _e('Global', 'charts'); ?></option>
            <?php foreach ($markets as $m) : ?>
                <option value="<?php echo esc_attr($m['code']); ?>" <?php selected($filter_market, $m['code']); ?>>
                    <?php echo esc_html($m['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="bento-filter-total">
        Filter active: <strong><?php echo ucfirst($filter_type); ?></strong>
        &nbsp;·&nbsp; Market: <strong><?php echo $filter_market === 'all' ? 'Global' : esc_html(strtoupper($filter_market)); ?></strong>
    </div>
</form>

<?php if (!$has_data) : ?>
<!-- ─── Empty State ─── -->
<div class="bento-card">
    <div class="bento-empty">
        <div class="bento-empty-icon">📡</div>
        <h2><?php _e('Nexus Offline', 'charts'); ?></h2>
        <p><?php _e('No intelligence data available. Initialize signal recalculation to populate the nexus.', 'charts'); ?></p>
        <button onclick="recalculateIntelligence()" class="bento-btn bento-btn-primary" id="intel-recalc-btn-empty">
            ⟳ <?php _e('Initialize Recalculation', 'charts'); ?>
        </button>
    </div>
</div>
<?php else : ?>

<!-- ═══════════════════════════════════════════════════════
     8 KPI STAT CARDS
     ═══════════════════════════════════════════════════════ -->
<div class="bento-grid-kpi">

    <!-- 1. Fastest Rising Song -->
    <div class="bento-kpi-card kpi-green">
        <div class="bento-kpi-icon">🎵</div>
        <div class="bento-kpi-label"><?php _e('Fastest Rising Song', 'charts'); ?></div>
        <div class="bento-kpi-value val-green">
            <?php echo $fastest_rising_song ? '+' . number_format($fastest_rising_song->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($fastest_rising_song->entity_name ?? '—'); ?></div>
    </div>

    <!-- 2. Fastest Rising Artist -->
    <div class="bento-kpi-card kpi-green">
        <div class="bento-kpi-icon">🎤</div>
        <div class="bento-kpi-label"><?php _e('Fastest Rising Artist', 'charts'); ?></div>
        <div class="bento-kpi-value val-green">
            <?php echo $fastest_rising_artist ? '+' . number_format($fastest_rising_artist->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($fastest_rising_artist->entity_name ?? '—'); ?></div>
    </div>

    <!-- 3. Most Stable Song -->
    <div class="bento-kpi-card kpi-blue">
        <div class="bento-kpi-icon">📊</div>
        <div class="bento-kpi-label"><?php _e('Most Stable Song', 'charts'); ?></div>
        <div class="bento-kpi-value val-blue">
            <?php echo $most_stable_song ? number_format($most_stable_song->stability_score, 1) : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($most_stable_song->entity_name ?? '—'); ?></div>
    </div>

    <!-- 4. Most Stable Artist -->
    <div class="bento-kpi-card kpi-blue">
        <div class="bento-kpi-icon">🏆</div>
        <div class="bento-kpi-label"><?php _e('Most Stable Artist', 'charts'); ?></div>
        <div class="bento-kpi-value val-blue">
            <?php echo $most_stable_artist ? number_format($most_stable_artist->stability_score, 1) : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($most_stable_artist->entity_name ?? '—'); ?></div>
    </div>

    <!-- 5. Largest Gain -->
    <div class="bento-kpi-card kpi-green">
        <div class="bento-kpi-icon">📈</div>
        <div class="bento-kpi-label"><?php _e('Largest Gain', 'charts'); ?></div>
        <div class="bento-kpi-value val-green">
            <?php echo $largest_gain ? '+' . number_format($largest_gain->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($largest_gain->entity_name ?? '—'); ?></div>
    </div>

    <!-- 6. Largest Drop -->
    <div class="bento-kpi-card kpi-rose">
        <div class="bento-kpi-icon">📉</div>
        <div class="bento-kpi-label"><?php _e('Largest Drop', 'charts'); ?></div>
        <div class="bento-kpi-value val-rose">
            <?php echo $largest_drop ? number_format($largest_drop->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($largest_drop->entity_name ?? '—'); ?></div>
    </div>

    <!-- 7. Max Momentum -->
    <div class="bento-kpi-card kpi-violet">
        <div class="bento-kpi-icon">⚡</div>
        <div class="bento-kpi-label"><?php _e('Max Momentum', 'charts'); ?></div>
        <div class="bento-kpi-value val-violet">
            <?php echo $max_momentum ? number_format($max_momentum->momentum_score, 1) : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($max_momentum->entity_name ?? '—'); ?></div>
    </div>

    <!-- 8. Max Viral -->
    <div class="bento-kpi-card kpi-amber">
        <div class="bento-kpi-icon">🔥</div>
        <div class="bento-kpi-label"><?php _e('Max Viral', 'charts'); ?></div>
        <div class="bento-kpi-value val-amber">
            <?php echo $max_viral ? number_format($max_viral->viral_score, 1) : '—'; ?>
        </div>
        <div class="bento-kpi-name"><?php echo esc_html($max_viral->entity_name ?? '—'); ?></div>
    </div>

</div><!-- /.bento-grid-kpi -->

<!-- ═══════════════════════════════════════════════════════
     BENTO ROW: Trend Radar (2/3) + Market Health (1/3)
     ═══════════════════════════════════════════════════════ -->
<div class="bento-two-col">

    <!-- LEFT: Trend Radar -->
    <div class="bento-card">
        <div class="bento-section-header">
            <div class="bento-section-header-left">
                📡 Trend Radar
            </div>
            <span class="bento-section-badge bento-badge-muted">Momentum Signals</span>
        </div>
        <div class="bento-radar-grid">

            <!-- Exploding 🔥 -->
            <div class="bento-radar-col radar-exploding">
                <div class="bento-radar-col-title">🔥 Exploding <span style="font-weight:400;opacity:0.6">(80+)</span></div>
                <?php if (empty($radar_exploding)) : ?>
                    <div class="bento-radar-empty"><?php _e('No signals', 'charts'); ?></div>
                <?php else : ?>
                    <?php foreach ($radar_exploding as $r) : ?>
                        <div class="bento-radar-item">
                            <span class="bento-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                            <span class="bento-score-pill pill-rose"><?php echo number_format($r->momentum_score, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Rising ↑ -->
            <div class="bento-radar-col radar-rising">
                <div class="bento-radar-col-title">↑ Rising <span style="font-weight:400;opacity:0.6">(60–80)</span></div>
                <?php if (empty($radar_rising)) : ?>
                    <div class="bento-radar-empty"><?php _e('No signals', 'charts'); ?></div>
                <?php else : ?>
                    <?php foreach ($radar_rising as $r) : ?>
                        <div class="bento-radar-item">
                            <span class="bento-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                            <span class="bento-score-pill pill-green"><?php echo number_format($r->momentum_score, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Stable — -->
            <div class="bento-radar-col radar-stable">
                <div class="bento-radar-col-title">— Stable <span style="font-weight:400;opacity:0.6">(40–60)</span></div>
                <?php if (empty($radar_stable)) : ?>
                    <div class="bento-radar-empty"><?php _e('No signals', 'charts'); ?></div>
                <?php else : ?>
                    <?php foreach ($radar_stable as $r) : ?>
                        <div class="bento-radar-item">
                            <span class="bento-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                            <span class="bento-score-pill pill-blue"><?php echo number_format($r->momentum_score, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Falling ↓ -->
            <div class="bento-radar-col radar-falling">
                <div class="bento-radar-col-title">↓ Falling <span style="font-weight:400;opacity:0.6">(&lt;40)</span></div>
                <?php if (empty($radar_falling)) : ?>
                    <div class="bento-radar-empty"><?php _e('No signals', 'charts'); ?></div>
                <?php else : ?>
                    <?php foreach ($radar_falling as $r) : ?>
                        <div class="bento-radar-item">
                            <span class="bento-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                            <span class="bento-score-pill pill-muted"><?php echo number_format($r->momentum_score, 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /.bento-radar-grid -->
    </div><!-- /.bento-card (radar) -->

    <!-- RIGHT: Market Health -->
    <div class="bento-card">
        <div class="bento-section-header">
            <div class="bento-section-header-left">
                🏥 Market Health
                <?php if (empty($market_health) || empty($market_health['calculated_at'])) : ?>
                    <span class="bento-health-cached-badge">⚠ CACHED</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($market_health['calculated_at'])) : ?>
                <span style="font-size:10px;color:#94a3b8;font-weight:500;"><?php echo esc_html(substr($market_health['calculated_at'], 0, 10)); ?></span>
            <?php endif; ?>
        </div>

        <?php
        $health_metrics_bento = [
            'competition' => ['label' => 'Competition', 'class' => 'gauge-violet', 'unit' => '%'],
            'volatility'  => ['label' => 'Volatility',  'class' => 'gauge-rose',   'unit' => ''],
            'retention'   => ['label' => 'Retention',   'class' => 'gauge-blue',   'unit' => '%'],
            'discovery'   => ['label' => 'Discovery',   'class' => 'gauge-amber',  'unit' => '%'],
            'growth'      => ['label' => 'Growth',      'class' => 'gauge-green',  'unit' => '%'],
        ];
        ?>
        <div class="bento-health-list">
            <?php foreach ($health_metrics_bento as $hk => $hcfg) :
                $hval = $market_health[$hk] ?? 0;
                $hpct = ($hk === 'volatility')
                    ? min(100, abs($hval) * 5)
                    : min(100, max(0, abs($hval)));
            ?>
            <div class="bento-gauge-row">
                <div class="bento-gauge-meta">
                    <span class="bento-gauge-label"><?php echo esc_html($hcfg['label']); ?></span>
                    <span class="bento-gauge-value"><?php echo number_format($hval, 1); ?><?php echo esc_html($hcfg['unit']); ?></span>
                </div>
                <div class="bento-gauge-track">
                    <div class="bento-gauge-fill <?php echo $hcfg['class']; ?>" style="width:<?php echo $hpct; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div><!-- /.bento-card (health) -->

</div><!-- /.bento-two-col -->

<!-- ═══════════════════════════════════════════════════════
     ENTITY METRICS TABLE
     ═══════════════════════════════════════════════════════ -->
<div class="bento-card" style="margin-bottom: 20px;">
    <div class="bento-section-header" style="margin-bottom:12px;">
        <div class="bento-section-header-left">
            📋 Entity Metrics Grid
            <span class="bento-section-badge bento-badge-muted"><?php echo count($entity_rows); ?> records</span>
        </div>
        <span style="font-size:10px;color:#94a3b8;font-weight:500;">Click headers to sort</span>
    </div>
    <div class="bento-table-wrap">
        <table class="bento-table" id="bento-entity-table">
            <thead>
                <tr>
                    <th data-col="name">Name</th>
                    <th data-col="type">Type</th>
                    <th data-col="momentum">Momentum</th>
                    <th data-col="viral">Viral</th>
                    <th data-col="stability">Stability</th>
                    <th data-col="volatility">Volatility</th>
                    <th data-col="growth">Growth %</th>
                </tr>
            </thead>
            <tbody id="bento-entity-tbody">
                <?php if (empty($entity_rows)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;"><?php _e('No entity data found for current filters.', 'charts'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($entity_rows as $erow) :
                        $emeta       = !empty($erow->metadata_json) ? json_decode($erow->metadata_json, true) : [];
                        $emomentum   = floatval($erow->momentum_score ?? 0);
                        $eviral      = floatval($erow->viral_score ?? 0);
                        $estability  = floatval($erow->stability_score ?? 0);
                        $evolatility = floatval($erow->volatility_score ?? 0);
                        $egrowth     = floatval($erow->growth_rate ?? 0);
                        $emom_pct    = min(100, max(0, $emomentum));
                        $eviral_pct  = min(100, max(0, $eviral));
                        $estab_pct   = min(100, max(0, $estability));
                        $evol_pct    = min(100, max(0, $evolatility * 2));
                    ?>
                    <tr>
                        <td class="bento-td-name" title="<?php echo esc_attr($erow->entity_name ?: '—'); ?>">
                            <?php echo esc_html($erow->entity_name ?: '—'); ?>
                        </td>
                        <td>
                            <span class="bento-type-badge <?php echo $erow->entity_type === 'track' ? 'badge-track' : 'badge-artist'; ?>">
                                <?php echo esc_html(strtoupper($erow->entity_type)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($emomentum, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-violet" style="width:<?php echo $emom_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($eviral, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-amber" style="width:<?php echo $eviral_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($estability, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-blue" style="width:<?php echo $estab_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($evolatility, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-rose" style="width:<?php echo $evol_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <span class="<?php echo $egrowth >= 0 ? 'bento-growth-up' : 'bento-growth-down'; ?>">
                                <?php echo ($egrowth >= 0 ? '+' : '') . number_format($egrowth, 1); ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="bento-pagination" id="bento-entity-pagination">
        <span class="bento-page-info" id="bento-entity-page-info">Showing 1–10 of <?php echo count($entity_rows); ?></span>
        <div class="bento-page-btns">
            <button class="bento-page-btn" id="bento-entity-prev" onclick="bentoPaginate('entity', -1)" disabled>← Prev</button>
            <button class="bento-page-btn" id="bento-entity-next" onclick="bentoPaginate('entity', 1)">Next →</button>
        </div>
    </div>
</div><!-- /.bento-card (entity table) -->

<!-- ═══════════════════════════════════════════════════════
     ARTIST AUTHORITY TABLE
     ═══════════════════════════════════════════════════════ -->
<div class="bento-card" style="margin-bottom: 20px;">
    <div class="bento-section-header" style="margin-bottom:12px;">
        <div class="bento-section-header-left">
            🎤 Artist Authority Index
            <span class="bento-section-badge bento-badge-violet"><?php echo count($artist_rows); ?> artists</span>
        </div>
        <span style="font-size:10px;color:#94a3b8;font-weight:500;">Click headers to sort</span>
    </div>
    <div class="bento-table-wrap">
        <table class="bento-table" id="bento-artist-table">
            <thead>
                <tr>
                    <th data-col="artist">Artist</th>
                    <th data-col="power">Power Score</th>
                    <th data-col="growth">Growth %</th>
                    <th data-col="momentum">Momentum</th>
                    <th data-col="weeks">Weeks on Chart</th>
                    <th data-col="trend">Trend</th>
                </tr>
            </thead>
            <tbody id="bento-artist-tbody">
                <?php if (empty($artist_rows)) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;"><?php _e('No artist intelligence data.', 'charts'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($artist_rows as $art) :
                        $ameta   = !empty($art->metadata_json) ? json_decode($art->metadata_json, true) : [];
                        $apower  = floatval($art->artist_power_score ?? 0);
                        $agrowth = floatval($art->growth_rate ?? 0);
                        $amom    = floatval($art->momentum_score ?? 0);
                        $aweeks  = intval($ameta['weeks_on_chart'] ?? 0);
                        $apwr_pct = min(100, max(0, $apower));
                        $amom_pct = min(100, max(0, $amom));
                        // Trend badge
                        if ($agrowth > 5) {
                            $atclass = 'trend-rising'; $attext = '↑ Rising';
                        } elseif ($agrowth < -5) {
                            $atclass = 'trend-falling'; $attext = '↓ Falling';
                        } else {
                            $atclass = 'trend-stable'; $attext = '— Stable';
                        }
                    ?>
                    <tr>
                        <td class="bento-td-name" title="<?php echo esc_attr($art->entity_name); ?>">
                            <?php echo esc_html($art->entity_name); ?>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($apower, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-green" style="width:<?php echo $apwr_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td>
                            <span class="<?php echo $agrowth >= 0 ? 'bento-growth-up' : 'bento-growth-down'; ?>">
                                <?php echo ($agrowth >= 0 ? '+' : '') . number_format($agrowth, 1); ?>%
                            </span>
                        </td>
                        <td>
                            <div class="bento-bar-cell">
                                <span class="bento-bar-num"><?php echo number_format($amom, 1); ?></span>
                                <div class="bento-bar-track"><div class="bento-bar-fill bfill-violet" style="width:<?php echo $amom_pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td style="color:#374151;font-weight:600;">
                            <?php echo $aweeks > 0 ? $aweeks : '<span class="bento-dash">—</span>'; ?>
                        </td>
                        <td>
                            <span class="bento-trend-badge <?php echo $atclass; ?>"><?php echo $attext; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="bento-pagination" id="bento-artist-pagination">
        <span class="bento-page-info" id="bento-artist-page-info">Showing 1–10 of <?php echo count($artist_rows); ?></span>
        <div class="bento-page-btns">
            <button class="bento-page-btn" id="bento-artist-prev" onclick="bentoPaginate('artist', -1)" disabled>← Prev</button>
            <button class="bento-page-btn" id="bento-artist-next" onclick="bentoPaginate('artist', 1)">Next →</button>
        </div>
    </div>
</div><!-- /.bento-card (artist table) -->

<?php endif; // $has_data ?>

</div><!-- /.bento-wrap -->

<!-- ═══════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════ -->
<script>
/* ── Toast System ── */
var BentoToast = {
    container: null,
    init: function() {
        this.container = document.getElementById('bento-toast-container');
    },
    show: function(type, message, title) {
        if (!this.container) this.init();
        var icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
        var el = document.createElement('div');
        el.className = 'bento-toast' + (type === 'error' ? ' toast-error' : type === 'warning' ? ' toast-warning' : '');
        el.innerHTML =
            '<span class="bento-toast-icon">' + (icons[type] || 'ℹ️') + '</span>' +
            '<div class="bento-toast-body">' +
                '<div class="bento-toast-title">' + (title || '') + '</div>' +
                '<div class="bento-toast-msg">' + (message || '') + '</div>' +
            '</div>' +
            '<button class="bento-toast-dismiss" onclick="this.parentElement.remove()">✕</button>';
        this.container.appendChild(el);
        setTimeout(function() {
            el.style.animation = 'bento-slide-out 0.25s ease forwards';
            setTimeout(function() { if (el.parentElement) el.remove(); }, 280);
        }, 3000);
    }
};
// Backward compat
window.ChartsToast = {
    show: function(type, msg, title) { BentoToast.show(type, msg, title); }
};

/* ── Recalculate ── */
function recalculateIntelligence() {
    var btn = document.getElementById('intel-recalc-btn') || document.getElementById('intel-recalc-btn-empty');
    if (!btn) return;
    btn.disabled = true;
    var origHtml = btn.innerHTML;
    btn.innerHTML = '⟳ Recalculating…';

    jQuery.post(ajaxurl, {
        action: 'charts_recalculate_intel',
        nonce: '<?php echo esc_js($nonce); ?>'
    }, function(res) {
        if (res.success) {
            btn.innerHTML = '✓ Complete';
            BentoToast.show('success', 'Intelligence Nexus recalculated successfully.', 'Nexus Sync Complete');
            setTimeout(function() { location.reload(); }, 1400);
        } else {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            var msg = (res.data && res.data.message) ? res.data.message : 'Recalculation failed.';
            BentoToast.show('error', msg, 'Nexus Error');
        }
    }).fail(function() {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        BentoToast.show('error', 'Connection lost during nexus sync.', 'Critical Link Failure');
    });
}

/* ── Pagination Engine ── */
var bentoPagination = {
    entity: { page: 0, perPage: 10 },
    artist: { page: 0, perPage: 10 }
};

function bentoPaginate(tableId, direction) {
    bentoPagination[tableId].page += direction;
    bentoRenderPage(tableId);
}

function bentoRenderPage(tableId) {
    var state   = bentoPagination[tableId];
    var tbody   = document.getElementById('bento-' + tableId + '-tbody');
    if (!tbody) return;
    var rows    = Array.from(tbody.querySelectorAll('tr'));
    var total   = rows.length;
    var pages   = Math.ceil(total / state.perPage);
    if (state.page < 0) state.page = 0;
    if (state.page >= pages) state.page = Math.max(0, pages - 1);

    var start = state.page * state.perPage;
    var end   = Math.min(start + state.perPage, total);

    rows.forEach(function(r, i) {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // Update info
    var info = document.getElementById('bento-' + tableId + '-page-info');
    if (info) info.textContent = 'Showing ' + (total === 0 ? '0' : (start + 1)) + '–' + end + ' of ' + total;

    // Update buttons
    var prevBtn = document.getElementById('bento-' + tableId + '-prev');
    var nextBtn = document.getElementById('bento-' + tableId + '-next');
    if (prevBtn) prevBtn.disabled = (state.page === 0);
    if (nextBtn) nextBtn.disabled = (state.page >= pages - 1 || total === 0);
}

/* ── Table Sort Engine ── */
function bentoSortTable(tableId, colIndex, colKey) {
    var table  = document.getElementById('bento-' + tableId + '-table');
    if (!table) return;
    var tbody  = table.querySelector('tbody');
    var rows   = Array.from(tbody.querySelectorAll('tr'));
    var ths    = table.querySelectorAll('thead th');
    var th     = ths[colIndex];
    var asc    = !th.classList.contains('sorted-asc');

    // Clear sort classes
    ths.forEach(function(t) { t.classList.remove('sorted-asc', 'sorted-desc'); });
    th.classList.add(asc ? 'sorted-asc' : 'sorted-desc');

    rows.sort(function(a, b) {
        var aCell = a.querySelectorAll('td')[colIndex];
        var bCell = b.querySelectorAll('td')[colIndex];
        var aVal  = aCell ? aCell.innerText.trim().replace(/[+%,]/g, '') : '';
        var bVal  = bCell ? bCell.innerText.trim().replace(/[+%,]/g, '') : '';
        var aNum  = parseFloat(aVal);
        var bNum  = parseFloat(bVal);
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return asc ? aNum - bNum : bNum - aNum;
        }
        return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });

    rows.forEach(function(r) { tbody.appendChild(r); });

    // Reset pagination
    bentoPagination[tableId].page = 0;
    bentoRenderPage(tableId);
}

/* ── Wire up sort headers ── */
document.addEventListener('DOMContentLoaded', function() {
    ['entity', 'artist'].forEach(function(tableId) {
        var table = document.getElementById('bento-' + tableId + '-table');
        if (!table) return;
        var ths = table.querySelectorAll('thead th');
        ths.forEach(function(th, idx) {
            th.addEventListener('click', function() {
                bentoSortTable(tableId, idx, th.getAttribute('data-col'));
            });
        });
        // Init pagination
        bentoRenderPage(tableId);
    });
});
</script>
