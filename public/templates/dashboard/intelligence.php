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

<div class="kc-terminal-wrap">

<style>
/* ═══════════════════════════════════════════════════════
   INTELLIGENCE NEXUS — Bloomberg Terminal Design
   ═══════════════════════════════════════════════════════ */
.kc-terminal-wrap {
    background: #f1f5f9;
    color: #334155;
    font-family: 'Courier New', Courier, monospace, -apple-system, sans-serif;
    padding: 24px;
    min-height: 100vh;
    box-sizing: border-box;
}
.kc-terminal-wrap * { box-sizing: border-box; }

/* Header */
.kc-terminal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    margin-bottom: 20px;
}
.kc-terminal-header h1 {
    font-family: 'Courier New', Courier, monospace;
    font-size: 20px;
    font-weight: 700;
    color: #059669;
    margin: 0;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.kc-terminal-header .kc-terminal-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.kc-terminal-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.kc-terminal-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #059669;
    padding: 8px 16px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.kc-terminal-btn:hover { background: #141518; border-color: #059669; color: #059669; }
.kc-terminal-btn:active { background: #10b981; color: #000; }
.kc-terminal-btn.btn-red { color: #e11d48; }
.kc-terminal-btn.btn-red:hover { border-color: #e11d48; }
.kc-terminal-btn.btn-amber { color: #d97706; }
.kc-terminal-btn.btn-amber:hover { border-color: #d97706; }
.kc-terminal-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.kc-terminal-sync {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-right: 12px;
}
.kc-terminal-sync strong { color: #059669; }

/* Filter Bar */
.kc-terminal-filters {
    display: flex;
    gap: 20px;
    padding: 14px 24px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    margin-bottom: 20px;
    align-items: center;
}
.kc-terminal-filters label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.1em;
    display: block;
    margin-bottom: 4px;
}
.kc-terminal-filters select {
    background: #000;
    border: 1px solid #cbd5e1;
    color: #059669;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 10px;
    border-radius: 2px;
    cursor: pointer;
    outline: none;
    min-width: 160px;
}
.kc-terminal-filters select:focus { border-color: #059669; }

/* KPI Cards Grid */
.kc-terminal-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.kc-terminal-kpi {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
}
.kc-terminal-kpi::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 100%;
}
.kc-terminal-kpi.kpi-green::before { background: #10b981; }
.kc-terminal-kpi.kpi-red::before { background: #f43f5e; }
.kc-terminal-kpi.kpi-amber::before { background: #f59e0b; }
.kc-terminal-kpi.kpi-cyan::before { background: #0ea5e9; }
.kc-terminal-kpi-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #64748b;
    margin-bottom: 8px;
}
.kc-terminal-kpi-value {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-terminal-kpi-name {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Trend Radar */
.kc-terminal-radar {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    margin-bottom: 20px;
    overflow: hidden;
}
.kc-terminal-radar-header {
    padding: 14px 24px;
    border-bottom: 1px solid #cbd5e1;
}
.kc-terminal-radar-header h2 {
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
    color: #059669;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.kc-terminal-radar-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
.kc-terminal-radar-col {
    padding: 16px 20px;
    border-right: 1px solid #cbd5e1;
}
.kc-terminal-radar-col:last-child { border-right: none; }
.kc-terminal-radar-col-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #cbd5e1;
}
.kc-terminal-radar-col-title.col-exploding { color: #e11d48; }
.kc-terminal-radar-col-title.col-rising { color: #059669; }
.kc-terminal-radar-col-title.col-stable { color: #d97706; }
.kc-terminal-radar-col-title.col-falling { color: #64748b; }
.kc-terminal-radar-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    font-size: 11px;
}
.kc-terminal-radar-item-name {
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
}
.kc-terminal-radar-item-score {
    font-weight: 700;
    font-size: 10px;
    flex-shrink: 0;
    margin-left: 8px;
}
.kc-terminal-radar-empty {
    color: #3a3d42;
    font-size: 10px;
    font-style: italic;
    padding: 8px 0;
}

/* Data Table */
.kc-terminal-panel {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    margin-bottom: 20px;
    overflow: hidden;
}
.kc-terminal-panel-header {
    padding: 14px 24px;
    border-bottom: 1px solid #cbd5e1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kc-terminal-panel-header h2 {
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
    color: #059669;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.kc-terminal-table {
    width: 100%;
    border-collapse: collapse;
}
.kc-terminal-table thead th {
    padding: 10px 16px;
    text-align: left;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #64748b;
    border-bottom: 1px solid #cbd5e1;
    background: #f8fafc;
    white-space: nowrap;
}
.kc-terminal-table tbody td {
    padding: 10px 16px;
    font-size: 12px;
    border-bottom: 1px solid #0e1012;
    vertical-align: middle;
}
.kc-terminal-table tbody tr:hover { background: #0e1012; }
.kc-terminal-table .td-name {
    color: #0f172a;
    font-weight: 700;
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-terminal-table .td-type {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 2px 6px;
    border-radius: 2px;
    display: inline-block;
}
.kc-terminal-table .td-type.type-track { background: #0d1f17; color: #059669; }
.kc-terminal-table .td-type.type-artist { background: #1f0d1a; color: #e11d48; }
.kc-terminal-table .td-right { text-align: right; }

/* Mini Bar Graphs */
.kc-terminal-bar-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}
.kc-terminal-bar-val {
    font-size: 11px;
    font-weight: 700;
    color: #334155;
    min-width: 32px;
    text-align: right;
}
.kc-terminal-bar-track {
    width: 60px;
    height: 5px;
    background: #e2e8f0;
    border-radius: 1px;
    overflow: hidden;
    flex-shrink: 0;
}
.kc-terminal-bar-fill {
    height: 100%;
    border-radius: 1px;
    transition: width 0.4s ease;
}
.bar-green .kc-terminal-bar-fill { background: #10b981; }
.bar-red .kc-terminal-bar-fill { background: #f43f5e; }
.bar-amber .kc-terminal-bar-fill { background: #f59e0b; }
.bar-cyan .kc-terminal-bar-fill { background: #0ea5e9; }

/* Market Health Gauges */
.kc-terminal-health-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    padding: 20px 24px;
}
.kc-terminal-gauge {
    text-align: center;
}
.kc-terminal-gauge-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #64748b;
    margin-bottom: 10px;
}
.kc-terminal-gauge-bar-wrap {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 8px;
}
.kc-terminal-gauge-bar {
    height: 100%;
    border-radius: 2px;
    transition: width 0.6s ease;
}
.kc-terminal-gauge-value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
}
.kc-terminal-gauge-unit {
    font-size: 10px;
    color: #64748b;
    margin-left: 2px;
}

/* Empty State */
.kc-terminal-empty {
    text-align: center;
    padding: 80px 40px;
    color: #64748b;
}
.kc-terminal-empty h2 {
    color: #059669;
    font-family: 'Courier New', Courier, monospace;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 12px;
}
.kc-terminal-empty p {
    font-size: 12px;
    margin-bottom: 24px;
}

/* Utilities */
.text-green { color: #00ff66 !important; }
.text-red { color: #ff3366 !important; }
.text-amber { color: #ffaa00 !important; }
.text-cyan { color: #00ccff !important; }
.text-dim { color: #7c8087 !important; }

@keyframes kc-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1200px) {
    .kc-terminal-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .kc-terminal-radar-grid { grid-template-columns: repeat(2, 1fr); }
    .kc-terminal-health-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .kc-terminal-kpi-grid { grid-template-columns: 1fr; }
    .kc-terminal-radar-grid { grid-template-columns: 1fr; }
    .kc-terminal-header { flex-direction: column; gap: 16px; align-items: flex-start; }
    .kc-terminal-health-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- ═══════════════════════════════════════════════════════
     HEADER
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-header">
    <div>
        <h1>&gt; INTELLIGENCE NEXUS</h1>
        <div class="kc-terminal-sub"><?php _e('Real-time momentum signals, velocity vectors, and market health diagnostics', 'charts'); ?></div>
    </div>
    <div class="kc-terminal-actions">
        <span class="kc-terminal-sync">
            <?php _e('LAST SYNC:', 'charts'); ?>
            <strong><?php echo $last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : 'NEVER'; ?></strong>
        </span>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=charts_export_intelligence'), 'charts_export_intelligence')); ?>" class="kc-terminal-btn btn-amber">
            ↓ <?php _e('EXPORT CSV', 'charts'); ?>
        </a>
        <button class="kc-terminal-btn" onclick="recalculateIntelligence()" id="intel-recalc-btn">
            ⟳ <?php _e('RECALCULATE', 'charts'); ?>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FILTER BAR
     ═══════════════════════════════════════════════════════ -->
<form method="get" action="" class="kc-terminal-filters">
    <input type="hidden" name="page" value="charts-intelligence">
    <div>
        <label><?php _e('Entity Type', 'charts'); ?></label>
        <select name="intel_type" onchange="this.form.submit()">
            <option value="all" <?php selected($filter_type, 'all'); ?>><?php _e('ALL ENTITIES', 'charts'); ?></option>
            <option value="track" <?php selected($filter_type, 'track'); ?>><?php _e('TRACKS', 'charts'); ?></option>
            <option value="artist" <?php selected($filter_type, 'artist'); ?>><?php _e('ARTISTS', 'charts'); ?></option>
        </select>
    </div>
    <div>
        <label><?php _e('Market / Country', 'charts'); ?></label>
        <select name="intel_market" onchange="this.form.submit()">
            <option value="all"><?php _e('GLOBAL', 'charts'); ?></option>
            <?php foreach ($markets as $m) : ?>
                <option value="<?php echo esc_attr($m['code']); ?>" <?php selected($filter_market, $m['code']); ?>>
                    <?php echo esc_html(strtoupper($m['name'])); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!$has_data) : ?>
<!-- Empty State -->
<div class="kc-terminal-panel">
    <div class="kc-terminal-empty">
        <h2>&gt; NEXUS OFFLINE</h2>
        <p><?php _e('No intelligence data available. Initialize signal recalculation to populate the nexus.', 'charts'); ?></p>
        <button onclick="recalculateIntelligence()" class="kc-terminal-btn" id="intel-recalc-btn-empty">
            ⟳ <?php _e('INITIALIZE RECALCULATION', 'charts'); ?>
        </button>
    </div>
</div>
<?php else : ?>

<!-- ═══════════════════════════════════════════════════════
     KPI STAT CARDS (8 cards)
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-kpi-grid">
    <!-- 1. Fastest Rising Song -->
    <div class="kc-terminal-kpi kpi-green">
        <div class="kc-terminal-kpi-label"><?php _e('Fastest Rising Song', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-green">
            <?php echo $fastest_rising_song ? '+' . number_format($fastest_rising_song->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($fastest_rising_song->entity_name ?? '—'); ?></div>
    </div>

    <!-- 2. Fastest Rising Artist -->
    <div class="kc-terminal-kpi kpi-green">
        <div class="kc-terminal-kpi-label"><?php _e('Fastest Rising Artist', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-green">
            <?php echo $fastest_rising_artist ? '+' . number_format($fastest_rising_artist->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($fastest_rising_artist->entity_name ?? '—'); ?></div>
    </div>

    <!-- 3. Most Stable Song -->
    <div class="kc-terminal-kpi kpi-cyan">
        <div class="kc-terminal-kpi-label"><?php _e('Most Stable Song', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-cyan">
            <?php echo $most_stable_song ? number_format($most_stable_song->stability_score, 1) : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($most_stable_song->entity_name ?? '—'); ?></div>
    </div>

    <!-- 4. Most Stable Artist -->
    <div class="kc-terminal-kpi kpi-cyan">
        <div class="kc-terminal-kpi-label"><?php _e('Most Stable Artist', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-cyan">
            <?php echo $most_stable_artist ? number_format($most_stable_artist->stability_score, 1) : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($most_stable_artist->entity_name ?? '—'); ?></div>
    </div>

    <!-- 5. Largest Gain -->
    <div class="kc-terminal-kpi kpi-green">
        <div class="kc-terminal-kpi-label"><?php _e('Largest Gain', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-green">
            <?php echo $largest_gain ? '+' . number_format($largest_gain->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($largest_gain->entity_name ?? '—'); ?></div>
    </div>

    <!-- 6. Largest Drop -->
    <div class="kc-terminal-kpi kpi-red">
        <div class="kc-terminal-kpi-label"><?php _e('Largest Drop', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-red">
            <?php echo $largest_drop ? number_format($largest_drop->growth_rate, 1) . '%' : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($largest_drop->entity_name ?? '—'); ?></div>
    </div>

    <!-- 7. Max Momentum -->
    <div class="kc-terminal-kpi kpi-amber">
        <div class="kc-terminal-kpi-label"><?php _e('Max Momentum', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-amber">
            <?php echo $max_momentum ? number_format($max_momentum->momentum_score, 1) : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($max_momentum->entity_name ?? '—'); ?></div>
    </div>

    <!-- 8. Max Viral -->
    <div class="kc-terminal-kpi kpi-red">
        <div class="kc-terminal-kpi-label"><?php _e('Max Viral', 'charts'); ?></div>
        <div class="kc-terminal-kpi-value text-red">
            <?php echo $max_viral ? number_format($max_viral->viral_score, 1) : '—'; ?>
        </div>
        <div class="kc-terminal-kpi-name"><?php echo esc_html($max_viral->entity_name ?? '—'); ?></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     TREND RADAR
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-radar">
    <div class="kc-terminal-radar-header">
        <h2>&gt; <?php _e('TREND RADAR', 'charts'); ?></h2>
    </div>
    <div class="kc-terminal-radar-grid">
        <!-- Exploding -->
        <div class="kc-terminal-radar-col">
            <div class="kc-terminal-radar-col-title col-exploding">▲▲ <?php _e('EXPLODING', 'charts'); ?> (80+)</div>
            <?php if (empty($radar_exploding)) : ?>
                <div class="kc-terminal-radar-empty"><?php _e('No signals', 'charts'); ?></div>
            <?php else : ?>
                <?php foreach ($radar_exploding as $r) : ?>
                    <div class="kc-terminal-radar-item">
                        <span class="kc-terminal-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                        <span class="kc-terminal-radar-item-score text-red"><?php echo number_format($r->momentum_score, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Rising -->
        <div class="kc-terminal-radar-col">
            <div class="kc-terminal-radar-col-title col-rising">▲ <?php _e('RISING', 'charts'); ?> (60-80)</div>
            <?php if (empty($radar_rising)) : ?>
                <div class="kc-terminal-radar-empty"><?php _e('No signals', 'charts'); ?></div>
            <?php else : ?>
                <?php foreach ($radar_rising as $r) : ?>
                    <div class="kc-terminal-radar-item">
                        <span class="kc-terminal-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                        <span class="kc-terminal-radar-item-score text-green"><?php echo number_format($r->momentum_score, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Stable -->
        <div class="kc-terminal-radar-col">
            <div class="kc-terminal-radar-col-title col-stable">— <?php _e('STABLE', 'charts'); ?> (40-60)</div>
            <?php if (empty($radar_stable)) : ?>
                <div class="kc-terminal-radar-empty"><?php _e('No signals', 'charts'); ?></div>
            <?php else : ?>
                <?php foreach ($radar_stable as $r) : ?>
                    <div class="kc-terminal-radar-item">
                        <span class="kc-terminal-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                        <span class="kc-terminal-radar-item-score text-amber"><?php echo number_format($r->momentum_score, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Falling -->
        <div class="kc-terminal-radar-col">
            <div class="kc-terminal-radar-col-title col-falling">▼ <?php _e('FALLING', 'charts'); ?> (&lt;40)</div>
            <?php if (empty($radar_falling)) : ?>
                <div class="kc-terminal-radar-empty"><?php _e('No signals', 'charts'); ?></div>
            <?php else : ?>
                <?php foreach ($radar_falling as $r) : ?>
                    <div class="kc-terminal-radar-item">
                        <span class="kc-terminal-radar-item-name"><?php echo esc_html($r->entity_name); ?></span>
                        <span class="kc-terminal-radar-item-score text-dim"><?php echo number_format($r->momentum_score, 1); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     TRACK & ENTITY DETAILS TABLE
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-panel">
    <div class="kc-terminal-panel-header">
        <h2>&gt; <?php _e('ENTITY METRICS GRID', 'charts'); ?></h2>
        <span class="text-dim" style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em;">
            <?php echo count($entity_rows); ?> <?php _e('RECORDS', 'charts'); ?>
        </span>
    </div>
    <div style="overflow-x: auto;">
    <table class="kc-terminal-table">
        <thead>
            <tr>
                <th><?php _e('Entity Name', 'charts'); ?></th>
                <th><?php _e('Type', 'charts'); ?></th>
                <th><?php _e('Momentum', 'charts'); ?></th>
                <th><?php _e('Velocity', 'charts'); ?></th>
                <th><?php _e('Acceleration', 'charts'); ?></th>
                <th><?php _e('Retention', 'charts'); ?></th>
                <th><?php _e('Volatility', 'charts'); ?></th>
                <th><?php _e('Engagement', 'charts'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entity_rows)) : ?>
                <tr><td colspan="8" style="text-align:center; padding:40px; color: #64748b;"><?php _e('No entity data found for current filters.', 'charts'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($entity_rows as $row) :
                    $meta = !empty($row->metadata_json) ? json_decode($row->metadata_json, true) : [];
                    $velocity     = $meta['velocity'] ?? 0;
                    $acceleration = $meta['acceleration'] ?? 0;
                    $retention    = $meta['retention'] ?? 0;
                    $engagement   = $meta['engagement'] ?? 0;
                    $volatility   = $row->volatility_score ?? 0;
                    $momentum     = $row->momentum_score ?? 0;

                    // Normalize for bar display (0-100 scale)
                    $mom_pct  = min(100, max(0, $momentum));
                    $vel_pct  = min(100, max(0, abs($velocity) * 5));
                    $acc_pct  = min(100, max(0, abs($acceleration) * 5));
                    $ret_pct  = min(100, max(0, $retention));
                    $vol_pct  = min(100, max(0, $volatility * 2));
                    $eng_pct  = min(100, max(0, $engagement));
                ?>
                <tr>
                    <td class="td-name"><?php echo esc_html($row->entity_name ?: '—'); ?></td>
                    <td>
                        <span class="td-type type-<?php echo esc_attr($row->entity_type); ?>">
                            <?php echo esc_html(strtoupper($row->entity_type)); ?>
                        </span>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-green">
                            <span class="kc-terminal-bar-val"><?php echo number_format($momentum, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $mom_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell <?php echo $velocity >= 0 ? 'bar-green' : 'bar-red'; ?>">
                            <span class="kc-terminal-bar-val"><?php echo ($velocity >= 0 ? '+' : '') . number_format($velocity, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $vel_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell <?php echo $acceleration >= 0 ? 'bar-cyan' : 'bar-red'; ?>">
                            <span class="kc-terminal-bar-val"><?php echo ($acceleration >= 0 ? '+' : '') . number_format($acceleration, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $acc_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-amber">
                            <span class="kc-terminal-bar-val"><?php echo number_format($retention, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $ret_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-red">
                            <span class="kc-terminal-bar-val"><?php echo number_format($volatility, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $vol_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-cyan">
                            <span class="kc-terminal-bar-val"><?php echo number_format($engagement, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $eng_pct; ?>%"></div></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     ARTIST METRICS TABLE
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-panel">
    <div class="kc-terminal-panel-header">
        <h2>&gt; <?php _e('ARTIST AUTHORITY INDEX', 'charts'); ?></h2>
        <span class="text-dim" style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em;">
            <?php echo count($artist_rows); ?> <?php _e('ARTISTS', 'charts'); ?>
        </span>
    </div>
    <div style="overflow-x: auto;">
    <table class="kc-terminal-table">
        <thead>
            <tr>
                <th><?php _e('Artist', 'charts'); ?></th>
                <th><?php _e('Power Score', 'charts'); ?></th>
                <th><?php _e('Market Share', 'charts'); ?></th>
                <th><?php _e('Authority', 'charts'); ?></th>
                <th><?php _e('Momentum', 'charts'); ?></th>
                <th><?php _e('Growth Rate', 'charts'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($artist_rows)) : ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; color: #64748b;"><?php _e('No artist intelligence data.', 'charts'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($artist_rows as $art) :
                    $meta = !empty($art->metadata_json) ? json_decode($art->metadata_json, true) : [];
                    $market_share = $meta['market_share'] ?? 0;
                    $authority    = $meta['authority'] ?? 0;
                    $power        = $art->artist_power_score ?? 0;
                    $mom          = $art->momentum_score ?? 0;
                    $growth       = $art->growth_rate ?? 0;

                    $pwr_pct = min(100, max(0, $power));
                    $ms_pct  = min(100, max(0, $market_share * 50));
                    $auth_pct = min(100, max(0, $authority));
                    $mom_pct = min(100, max(0, $mom));
                ?>
                <tr>
                    <td class="td-name"><?php echo esc_html($art->entity_name); ?></td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-green">
                            <span class="kc-terminal-bar-val"><?php echo number_format($power, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $pwr_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-amber">
                            <span class="kc-terminal-bar-val"><?php echo number_format($market_share, 2); ?>%</span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $ms_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-cyan">
                            <span class="kc-terminal-bar-val"><?php echo number_format($authority, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $auth_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <div class="kc-terminal-bar-cell bar-green">
                            <span class="kc-terminal-bar-val"><?php echo number_format($mom, 1); ?></span>
                            <div class="kc-terminal-bar-track"><div class="kc-terminal-bar-fill" style="width:<?php echo $mom_pct; ?>%"></div></div>
                        </div>
                    </td>
                    <td>
                        <span class="<?php echo $growth >= 0 ? 'text-green' : 'text-red'; ?>" style="font-weight:700;">
                            <?php echo ($growth >= 0 ? '+' : '') . number_format($growth, 1); ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MARKET HEALTH PANEL
     ═══════════════════════════════════════════════════════ -->
<div class="kc-terminal-panel">
    <div class="kc-terminal-panel-header">
        <h2>&gt; <?php _e('MARKET HEALTH INDICES', 'charts'); ?></h2>
        <?php if (!empty($market_health['calculated_at'])) : ?>
            <span class="text-dim" style="font-size:10px; text-transform:uppercase; letter-spacing:0.08em;">
                <?php _e('COMPUTED', 'charts'); ?>: <?php echo esc_html($market_health['calculated_at']); ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="kc-terminal-health-grid">
        <?php
        $health_metrics = [
            'competition' => ['label' => __('Competition', 'charts'), 'color' => '#00ff66', 'unit' => '%'],
            'volatility'  => ['label' => __('Volatility', 'charts'),  'color' => '#ff3366', 'unit' => ''],
            'retention'   => ['label' => __('Retention', 'charts'),   'color' => '#00ccff', 'unit' => '%'],
            'discovery'   => ['label' => __('Discovery', 'charts'),   'color' => '#ffaa00', 'unit' => '%'],
            'growth'      => ['label' => __('Growth', 'charts'),      'color' => '#00ff66', 'unit' => '%'],
        ];
        foreach ($health_metrics as $key => $cfg) :
            $val = $market_health[$key] ?? 0;
            // For volatility, normalize differently (it's not a percentage)
            $bar_pct = ($key === 'volatility') ? min(100, $val * 5) : min(100, max(0, abs($val)));
        ?>
        <div class="kc-terminal-gauge">
            <div class="kc-terminal-gauge-label"><?php echo $cfg['label']; ?></div>
            <div class="kc-terminal-gauge-bar-wrap">
                <div class="kc-terminal-gauge-bar" style="width:<?php echo $bar_pct; ?>%; background:<?php echo $cfg['color']; ?>;"></div>
            </div>
            <div class="kc-terminal-gauge-value" style="color:<?php echo $cfg['color']; ?>;">
                <?php echo number_format($val, 1); ?><span class="kc-terminal-gauge-unit"><?php echo $cfg['unit']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════ -->
<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
function recalculateIntelligence() {
    var btn = document.getElementById('intel-recalc-btn') || document.getElementById('intel-recalc-btn-empty');
    if (!btn) return;

    btn.disabled = true;
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '⟳ RECALCULATING...';
    btn.style.color = '#ffaa00';

    jQuery.post(ajaxurl, {
        action: 'charts_recalculate_intel',
        nonce: '<?php echo esc_js($nonce); ?>'
    }, function(res) {
        if (res.success) {
            btn.innerHTML = '✓ COMPLETE';
            btn.style.color = '#00ff66';
            if (window.ChartsToast) {
                window.ChartsToast.show('success', 'Intelligence Nexus recalculated successfully.', 'Nexus Sync Complete');
            }
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            btn.style.color = '#ff3366';
            var msg = (res.data && res.data.message) ? res.data.message : 'Recalculation failed.';
            if (window.ChartsToast) {
                window.ChartsToast.show('error', msg, 'Nexus Error');
            } else {
                alert(msg);
            }
        }
    }).fail(function() {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        btn.style.color = '#ff3366';
        if (window.ChartsToast) {
            window.ChartsToast.show('error', 'Connection lost during nexus sync.', 'Critical Link Failure');
        } else {
            alert('Connection error during recalculation.');
        }
    });
}
</script>

</div>
