<?php
/**
 * Kontentainment Charts — Insights Engine: Weekly Intelligence Brief
 * Bloomberg Terminal / Monochrome AI Newsroom Dashboard
 */
global $wpdb;

$intel_table      = $wpdb->prefix . 'charts_intelligence';
$entries_table    = $wpdb->prefix . 'charts_entries';
$tracks_table     = $wpdb->prefix . 'charts_tracks';
$artists_table    = $wpdb->prefix . 'charts_artists';
$definitions_table = $wpdb->prefix . 'charts_definitions';

// ── 1. EDITORIAL INSIGHTS ────────────────────────────────────────────────────
$editorial_insights = \Charts\Services\PredictionEngine::generate_editorial_insights();

// ── 2. WEEKLY HIGHLIGHTS: Biggest positive movers ─────────────────────────────
$weekly_highlights = $wpdb->get_results("
    SELECT e.track_name, e.artist_names, e.movement_value, e.rank_position,
           CASE WHEN e.cover_image IS NOT NULL AND e.cover_image != '' AND e.cover_image LIKE 'http%' THEN e.cover_image
           ELSE t.cover_image END as cover_image
    FROM $entries_table e
    LEFT JOIN $tracks_table t ON (e.item_id = t.id AND e.item_type = 'track')
    WHERE e.movement_direction = 'up' AND e.movement_value > 0
    ORDER BY e.movement_value DESC
    LIMIT 5
");

// ── 3. MARKET SUMMARY: Load health data for first chart definition ───────────
$first_chart_id = $wpdb->get_var("SELECT id FROM $definitions_table ORDER BY id ASC LIMIT 1");
$market_health = $first_chart_id
    ? \Charts\Services\IntelligenceEngine::get_market_health($first_chart_id)
    : ['competition' => 0, 'volatility' => 0, 'retention' => 0, 'discovery' => 0, 'growth' => 0];

// ── 4. ARTIST SPOTLIGHT: Top rising artist by power score ─────────────────────
$spotlight_artist = $wpdb->get_row("
    SELECT i.*, a.display_name, a.image,
           i.artist_power_score, i.total_streams, i.weeks_on_chart, i.peaks_count, i.avg_rank,
           (SELECT COUNT(DISTINCT track_name) FROM $entries_table WHERE artist_names LIKE CONCAT('%', a.display_name, '%')) as charting_songs
    FROM $intel_table i
    JOIN $artists_table a ON a.id = i.entity_id
    WHERE i.entity_type = 'artist' AND i.artist_power_score > 0
    ORDER BY i.artist_power_score DESC
    LIMIT 1
");

// ── 5. FASTEST MOVERS: Top 5 by absolute growth_rate ──────────────────────────
$fastest_movers = $wpdb->get_results("
    SELECT i.growth_rate, i.trend_status, i.momentum_score,
           t.title as track_name, art.display_name as artist_name, t.cover_image,
           (SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1) as current_rank
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
    LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
    WHERE ABS(i.growth_rate) > 0
    ORDER BY ABS(i.growth_rate) DESC
    LIMIT 5
");

// ── 6. RECORD BREAKERS ───────────────────────────────────────────────────────
// 6a. Longest Running (by weeks_on_chart)
$longest_running = $wpdb->get_results("
    SELECT i.weeks_on_chart, i.peaks_count, i.momentum_score,
           t.title as track_name, art.display_name as artist_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
    LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
    WHERE i.weeks_on_chart > 0
    ORDER BY i.weeks_on_chart DESC
    LIMIT 5
");

// 6b. Highest Peaks (peaked at #1, sorted by weeks_on_chart)
$highest_peaks = $wpdb->get_results("
    SELECT i.weeks_on_chart, i.peaks_count,
           t.title as track_name, art.display_name as artist_name
    FROM $intel_table i
    JOIN $tracks_table t ON t.id = i.entity_id AND i.entity_type = 'track'
    LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
    WHERE i.peaks_count = 1
    ORDER BY i.weeks_on_chart DESC
    LIMIT 5
");

// ── 7. DATE CONTEXT ──────────────────────────────────────────────────────────
$week_start = date('M d', strtotime('monday this week'));
$week_end   = date('M d, Y', strtotime('sunday this week'));
$report_ts  = date('Y-m-d H:i:s');
$has_data   = !empty($editorial_insights) || !empty($weekly_highlights) || !empty($fastest_movers);
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════════
   INSIGHTS ENGINE — BLOOMBERG TERMINAL MONOCHROME DESIGN SYSTEM
   ═══════════════════════════════════════════════════════════════════════════════ */
.kc-terminal-wrap {
    background: #f1f5f9;
    color: #334155;
    font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, sans-serif;
    padding: 0;
    min-height: 100vh;
    margin-left: -20px;
    margin-top: -10px;
    padding: 32px 40px 60px;
}
.kc-terminal-wrap * { box-sizing: border-box; }

/* ── HEADER ─────────────────────────────────────────────────────────────────── */
.kc-terminal-header {
    border-bottom: 1px solid #cbd5e1;
    padding-bottom: 20px;
    margin-bottom: 32px;
}
.kc-terminal-header-title {
    font-size: 15px;
    font-weight: 700;
    color: #059669;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.kc-terminal-header-title .blink-cursor {
    display: inline-block;
    width: 8px;
    height: 15px;
    background: #10b981;
    animation: kc-blink 1s step-end infinite;
}
@keyframes kc-blink { 0%,100%{opacity:1} 50%{opacity:0} }
.kc-terminal-header-meta {
    font-size: 11px;
    color: #64748b;
    letter-spacing: 0.04em;
    display: flex;
    gap: 24px;
    margin-top: 4px;
}
.kc-terminal-header-meta span { white-space: nowrap; }

/* ── SECTION HEADERS ────────────────────────────────────────────────────────── */
.kc-section-header {
    font-size: 12px;
    font-weight: 700;
    color: #059669;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin: 0 0 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #cbd5e1;
    display: flex;
    align-items: center;
    gap: 8px;
}
.kc-section-header .kc-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #10b981;
    animation: kc-pulse-dot 2s infinite;
}
@keyframes kc-pulse-dot {
    0% { box-shadow: 0 0 0 0 rgba(0,255,102,0.5); }
    70% { box-shadow: 0 0 0 6px rgba(0,255,102,0); }
    100% { box-shadow: 0 0 0 0 rgba(0,255,102,0); }
}

/* ── CARDS ──────────────────────────────────────────────────────────────────── */
.kc-terminal-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 20px 24px;
    margin-bottom: 24px;
}

/* ── GRID LAYOUT ────────────────────────────────────────────────────────────── */
.kc-grid { display: grid; gap: 24px; }
.kc-grid-2 { grid-template-columns: 1fr 1fr; }
.kc-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.kc-grid-sidebar { grid-template-columns: 2fr 1fr; }
.kc-grid-sidebar-rev { grid-template-columns: 1fr 2fr; }

/* ── EDITORIAL BULLETINS ────────────────────────────────────────────────────── */
.kc-bulletin-item {
    border-left: 3px solid #00ff66;
    padding: 12px 16px;
    margin-bottom: 12px;
    background: rgba(0,255,102,0.02);
    position: relative;
}
.kc-bulletin-item:last-child { margin-bottom: 0; }
.kc-bulletin-ts {
    font-size: 10px;
    color: #64748b;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 4px;
    font-family: 'Courier New', monospace;
}
.kc-bulletin-text {
    font-size: 13px;
    color: #e0e2e6;
    line-height: 1.55;
    font-weight: 500;
}
.kc-bulletin-item:nth-child(2) { border-left-color: #d97706; background: rgba(255,170,0,0.02); }
.kc-bulletin-item:nth-child(3) { border-left-color: #e11d48; background: rgba(255,51,102,0.02); }

/* ── WEEKLY HIGHLIGHTS ──────────────────────────────────────────────────────── */
.kc-highlight-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #111214;
}
.kc-highlight-row:last-child { border-bottom: none; }
.kc-highlight-rank {
    font-size: 11px;
    font-weight: 700;
    color: #059669;
    min-width: 38px;
    font-family: 'Courier New', monospace;
}
.kc-highlight-arrow {
    font-size: 14px;
    min-width: 16px;
}
.kc-highlight-arrow.up { color: #059669; }
.kc-highlight-arrow.down { color: #e11d48; }
.kc-highlight-info { flex: 1; min-width: 0; }
.kc-highlight-track {
    font-size: 12px;
    font-weight: 700;
    color: #e0e2e6;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-highlight-artist {
    font-size: 10px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-highlight-move {
    font-size: 13px;
    font-weight: 900;
    color: #059669;
    min-width: 45px;
    text-align: right;
    font-family: 'Courier New', monospace;
}

/* ── MARKET HEALTH BARS ─────────────────────────────────────────────────────── */
.kc-health-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #111214;
}
.kc-health-row:last-child { border-bottom: none; }
.kc-health-label {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    min-width: 90px;
}
.kc-health-bar-wrap {
    flex: 1;
    height: 8px;
    background: #111214;
    border-radius: 2px;
    overflow: hidden;
    position: relative;
}
.kc-health-bar {
    height: 100%;
    border-radius: 2px;
    transition: width 1s ease;
}
.kc-health-bar.green { background: linear-gradient(90deg, #005522, #00ff66); }
.kc-health-bar.amber { background: linear-gradient(90deg, #553300, #ffaa00); }
.kc-health-bar.red   { background: linear-gradient(90deg, #550011, #ff3366); }
.kc-health-val {
    font-size: 12px;
    font-weight: 900;
    color: #059669;
    min-width: 48px;
    text-align: right;
    font-family: 'Courier New', monospace;
}
.kc-health-desc {
    font-size: 9px;
    color: #555960;
    margin-top: 2px;
    letter-spacing: 0.02em;
}

/* ── ARTIST SPOTLIGHT ───────────────────────────────────────────────────────── */
.kc-spotlight-wrap {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}
.kc-spotlight-gauge {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    position: relative;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.kc-spotlight-gauge-ring {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    position: absolute;
    top: 0;
    left: 0;
}
.kc-spotlight-gauge-val {
    font-size: 22px;
    font-weight: 900;
    color: #059669;
    font-family: 'Courier New', monospace;
    z-index: 2;
    position: relative;
}
.kc-spotlight-gauge-label {
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 8px;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.1em;
    white-space: nowrap;
}
.kc-spotlight-meta { flex: 1; }
.kc-spotlight-name {
    font-size: 18px;
    font-weight: 900;
    color: #059669;
    margin-bottom: 4px;
    letter-spacing: 0.02em;
}
.kc-spotlight-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 20px;
    margin-top: 14px;
}
.kc-spotlight-stat-label {
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.kc-spotlight-stat-val {
    font-size: 15px;
    font-weight: 900;
    color: #334155;
    font-family: 'Courier New', monospace;
}

/* ── TABLES (MOVERS / RECORDS) ──────────────────────────────────────────────── */
.kc-terminal-table {
    width: 100%;
    border-collapse: collapse;
}
.kc-terminal-table th {
    font-size: 9px;
    font-weight: 700;
    color: #555960;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    text-align: left;
    padding: 8px 10px;
    border-bottom: 1px solid #cbd5e1;
}
.kc-terminal-table td {
    font-size: 12px;
    padding: 9px 10px;
    border-bottom: 1px solid #0e0f11;
    color: #334155;
}
.kc-terminal-table tr:hover td { background: rgba(0,255,102,0.02); }
.kc-terminal-table .td-rank {
    font-weight: 900;
    color: #059669;
    font-family: 'Courier New', monospace;
}
.kc-terminal-table .td-track {
    font-weight: 700;
    color: #e0e2e6;
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-terminal-table .td-artist {
    color: #64748b;
    font-size: 11px;
    max-width: 140px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kc-terminal-table .td-val-up {
    font-weight: 900;
    color: #059669;
    font-family: 'Courier New', monospace;
}
.kc-terminal-table .td-val-down {
    font-weight: 900;
    color: #e11d48;
    font-family: 'Courier New', monospace;
}
.kc-terminal-table .td-weeks {
    font-weight: 700;
    color: #d97706;
    font-family: 'Courier New', monospace;
}
.kc-direction-pill {
    display: inline-block;
    font-size: 8px;
    font-weight: 900;
    padding: 2px 6px;
    border-radius: 2px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.kc-direction-pill.rising { background: rgba(0,255,102,0.12); color: #059669; }
.kc-direction-pill.falling { background: rgba(255,51,102,0.12); color: #e11d48; }
.kc-direction-pill.stable { background: rgba(124,128,135,0.12); color: #64748b; }
.kc-direction-pill.new-entry { background: rgba(255,170,0,0.12); color: #d97706; }

/* ── RECORD BREAKERS ────────────────────────────────────────────────────────── */
.kc-records-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.kc-records-col-title {
    font-size: 10px;
    font-weight: 700;
    color: #d97706;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #cbd5e1;
}

/* ── EMPTY STATE ────────────────────────────────────────────────────────────── */
.kc-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #555960;
    font-size: 12px;
    font-style: italic;
}

/* ── REFRESH BTN ────────────────────────────────────────────────────────────── */
.kc-terminal-btn {
    background: transparent;
    border: 1px solid #cbd5e1;
    color: #059669;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    padding: 6px 14px;
    cursor: pointer;
    border-radius: 2px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    transition: all 0.2s ease;
}
.kc-terminal-btn:hover {
    background: rgba(0,255,102,0.08);
    border-color: #059669;
}
.kc-terminal-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.kc-terminal-btn .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: middle;
    margin-right: 4px;
}

/* ── SVG GAUGE ──────────────────────────────────────────────────────────────── */
.kc-gauge-svg {
    transform: rotate(-90deg);
    position: absolute;
    top: 0;
    left: 0;
}
.kc-gauge-bg { fill: none; stroke: #1c1e22; stroke-width: 6; }
.kc-gauge-fill { fill: none; stroke: #00ff66; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset 1.5s ease; }

/* ── RESPONSIVE ─────────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .kc-grid-2, .kc-grid-sidebar, .kc-grid-sidebar-rev { grid-template-columns: 1fr; }
    .kc-records-split { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .kc-terminal-wrap { padding: 16px 20px 40px; }
    .kc-grid-3 { grid-template-columns: 1fr; }
    .kc-spotlight-wrap { flex-direction: column; align-items: center; text-align: center; }
    .kc-spotlight-stats { grid-template-columns: 1fr 1fr; }
}

@keyframes kc-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="kc-terminal-wrap">

    <!-- ═══════════════ HEADER ═══════════════ -->
    <header class="kc-terminal-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 class="kc-terminal-header-title">
                    &gt; INSIGHTS ENGINE — WEEKLY INTELLIGENCE BRIEF
                    <span class="blink-cursor"></span>
                </h1>
                <div class="kc-terminal-header-meta">
                    <span>WEEK: <?php echo esc_html($week_start . ' — ' . $week_end); ?></span>
                    <span>GENERATED: <?php echo esc_html($report_ts); ?></span>
                    <span>STATUS: <span style="color: #059669;">● LIVE</span></span>
                </div>
            </div>
            <button class="kc-terminal-btn" onclick="recalculateInsights()" id="insights-refresh-btn">
                <span class="dashicons dashicons-update"></span>
                RE-SYNC
            </button>
        </div>
    </header>

    <?php if (!$has_data && empty($spotlight_artist) && empty($longest_running)) : ?>
        <div class="kc-terminal-card">
            <div class="kc-empty-state">
                <div style="font-size: 36px; margin-bottom: 16px; color: #1c1e22;">⌁</div>
                <div style="color: #64748b; font-size: 13px; font-style: normal;">INTELLIGENCE ENGINE OFFLINE</div>
                <div style="margin-top: 6px; font-size: 11px;">Run calculations or import chart data to populate the intelligence brief.</div>
                <div style="margin-top: 20px;">
                    <a href="<?php echo admin_url('admin.php?page=charts-import'); ?>" class="kc-terminal-btn" style="text-decoration:none;">
                        IMPORT CENTER →
                    </a>
                </div>
            </div>
        </div>
    <?php else : ?>

    <!-- ═══════════════ EDITORIAL INSIGHTS ═══════════════ -->
    <div class="kc-terminal-card">
        <h2 class="kc-section-header">
            <span class="kc-dot"></span>
            AUTO-GENERATED EDITORIAL — WIRE SERVICE
        </h2>
        <?php if (!empty($editorial_insights)) : ?>
            <?php foreach ($editorial_insights as $idx => $insight) : ?>
                <div class="kc-bulletin-item">
                    <div class="kc-bulletin-ts"><?php echo date('H:i:s', strtotime("+{$idx} minutes")); ?> UTC — BULLETIN <?php echo str_pad($idx + 1, 3, '0', STR_PAD_LEFT); ?></div>
                    <div class="kc-bulletin-text"><?php echo esc_html($insight); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="kc-empty-state">No editorial insights generated. Run intelligence recalculation to populate.</div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════ HIGHLIGHTS + MARKET HEALTH (2-col) ═══════════════ -->
    <div class="kc-grid kc-grid-sidebar">

        <!-- Weekly Highlights -->
        <div class="kc-terminal-card">
            <h2 class="kc-section-header">
                <span class="kc-dot"></span>
                WEEKLY HIGHLIGHTS — CHART SHIFTS
            </h2>
            <?php if (!empty($weekly_highlights)) : ?>
                <?php foreach ($weekly_highlights as $h) : ?>
                    <div class="kc-highlight-row">
                        <span class="kc-highlight-rank">#<?php echo intval($h->rank_position); ?></span>
                        <span class="kc-highlight-arrow up">▲</span>
                        <div class="kc-highlight-info">
                            <div class="kc-highlight-track"><?php echo esc_html($h->track_name); ?></div>
                            <div class="kc-highlight-artist"><?php echo esc_html($h->artist_names); ?></div>
                        </div>
                        <span class="kc-highlight-move">+<?php echo intval($h->movement_value); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="kc-empty-state">No significant chart movements detected this week.</div>
            <?php endif; ?>
        </div>

        <!-- Market Summary -->
        <div class="kc-terminal-card">
            <h2 class="kc-section-header">
                <span class="kc-dot"></span>
                MARKET HEALTH INDICES
            </h2>
            <?php
            $health_indices = [
                'competition' => [
                    'label' => 'Competition',
                    'desc'  => 'Ratio of unique tracks to total chart slots',
                    'color' => 'green',
                    'max'   => 100,
                    'unit'  => '%',
                ],
                'volatility' => [
                    'label' => 'Volatility',
                    'desc'  => 'Mean absolute rank movement across all songs',
                    'color' => 'amber',
                    'max'   => 20,
                    'unit'  => 'pts',
                ],
                'retention' => [
                    'label' => 'Retention',
                    'desc'  => 'Percentage of recurring songs vs new entries',
                    'color' => 'green',
                    'max'   => 100,
                    'unit'  => '%',
                ],
                'discovery' => [
                    'label' => 'Discovery',
                    'desc'  => 'Rate of new entries entering the Top 50',
                    'color' => 'amber',
                    'max'   => 100,
                    'unit'  => '%',
                ],
                'growth' => [
                    'label' => 'Growth',
                    'desc'  => 'Week-over-week consumption volume change',
                    'color' => $market_health['growth'] >= 0 ? 'green' : 'red',
                    'max'   => 50,
                    'unit'  => '%',
                ],
            ];
            foreach ($health_indices as $key => $cfg) :
                $val = floatval($market_health[$key] ?? 0);
                $bar_pct = min(100, abs($val) / $cfg['max'] * 100);
            ?>
                <div class="kc-health-row">
                    <div>
                        <span class="kc-health-label"><?php echo esc_html($cfg['label']); ?></span>
                        <div class="kc-health-desc"><?php echo esc_html($cfg['desc']); ?></div>
                    </div>
                    <div class="kc-health-bar-wrap">
                        <div class="kc-health-bar <?php echo $cfg['color']; ?>" style="width: <?php echo $bar_pct; ?>%;"></div>
                    </div>
                    <span class="kc-health-val" style="color: <?php echo $cfg['color'] === 'green' ? '#00ff66' : ($cfg['color'] === 'red' ? '#ff3366' : '#ffaa00'); ?>;">
                        <?php echo ($val >= 0 ? '' : '') . number_format($val, 1) . $cfg['unit']; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══════════════ ARTIST SPOTLIGHT ═══════════════ -->
    <?php if ($spotlight_artist) : ?>
    <div class="kc-terminal-card">
        <h2 class="kc-section-header">
            <span class="kc-dot"></span>
            ARTIST SPOTLIGHT — TOP AUTHORITY
        </h2>
        <div class="kc-spotlight-wrap">
            <?php
                $power = round(floatval($spotlight_artist->artist_power_score));
                $circumference = 2 * M_PI * 42;
                $offset = $circumference - ($power / 100) * $circumference;
            ?>
            <div class="kc-spotlight-gauge">
                <svg class="kc-gauge-svg" width="100" height="100" viewBox="0 0 100 100">
                    <circle class="kc-gauge-bg" cx="50" cy="50" r="42" />
                    <circle class="kc-gauge-fill" cx="50" cy="50" r="42"
                            stroke-dasharray="<?php echo round($circumference, 2); ?>"
                            stroke-dashoffset="<?php echo round($offset, 2); ?>" />
                </svg>
                <span class="kc-spotlight-gauge-val"><?php echo $power; ?></span>
                <span class="kc-spotlight-gauge-label">POWER SCORE</span>
            </div>
            <div class="kc-spotlight-meta">
                <div class="kc-spotlight-name"><?php echo esc_html($spotlight_artist->display_name); ?></div>
                <div style="font-size: 10px; color: #555960; text-transform: uppercase; letter-spacing: 0.06em;">Highest-ranked artist by composite authority index</div>
                <div class="kc-spotlight-stats">
                    <div>
                        <div class="kc-spotlight-stat-label">Total Streams</div>
                        <div class="kc-spotlight-stat-val">
                            <?php
                                $streams = floatval($spotlight_artist->total_streams);
                                echo $streams > 1000000 ? number_format($streams / 1000000, 1) . 'M' : ($streams > 1000 ? number_format($streams / 1000, 1) . 'K' : number_format($streams));
                            ?>
                        </div>
                    </div>
                    <div>
                        <div class="kc-spotlight-stat-label">Chart Weeks</div>
                        <div class="kc-spotlight-stat-val"><?php echo intval($spotlight_artist->weeks_on_chart); ?></div>
                    </div>
                    <div>
                        <div class="kc-spotlight-stat-label">Peak Rank</div>
                        <div class="kc-spotlight-stat-val">#<?php echo intval($spotlight_artist->peaks_count); ?></div>
                    </div>
                    <div>
                        <div class="kc-spotlight-stat-label">Charting Songs</div>
                        <div class="kc-spotlight-stat-val"><?php echo intval($spotlight_artist->charting_songs); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════ FASTEST MOVERS ═══════════════ -->
    <div class="kc-terminal-card">
        <h2 class="kc-section-header">
            <span class="kc-dot"></span>
            FASTEST MOVERS &amp; BREAKOUT SONGS
        </h2>
        <?php if (!empty($fastest_movers)) : ?>
        <table class="kc-terminal-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Track</th>
                    <th>Artist</th>
                    <th>Rank</th>
                    <th>Movement</th>
                    <th style="text-align:right;">Direction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fastest_movers as $idx => $m) :
                    $growth = floatval($m->growth_rate);
                    $is_up = $growth > 0;
                    $status = $m->trend_status ?? 'stable';
                    $pill_class = 'stable';
                    if ($status === 'rising' || $status === 'Strong Upward' || $status === 'Upward') $pill_class = 'rising';
                    elseif ($status === 'falling' || $status === 'Declining') $pill_class = 'falling';
                    elseif ($status === 'new') $pill_class = 'new-entry';
                ?>
                    <tr>
                        <td class="td-rank"><?php echo $idx + 1; ?></td>
                        <td class="td-track"><?php echo esc_html($m->track_name); ?></td>
                        <td class="td-artist"><?php echo esc_html($m->artist_name); ?></td>
                        <td class="td-rank"><?php echo $m->current_rank ? '#' . intval($m->current_rank) : '—'; ?></td>
                        <td class="<?php echo $is_up ? 'td-val-up' : 'td-val-down'; ?>">
                            <?php echo ($is_up ? '+' : '') . intval($growth); ?>
                        </td>
                        <td style="text-align:right;">
                            <span class="kc-direction-pill <?php echo $pill_class; ?>">
                                <?php echo $is_up ? '▲' : '▼'; ?> <?php echo esc_html(strtoupper($status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
            <div class="kc-empty-state">No mover data available. Run calculations first.</div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════ RECORD BREAKERS ═══════════════ -->
    <div class="kc-terminal-card">
        <h2 class="kc-section-header">
            <span class="kc-dot"></span>
            RECORD BREAKERS
        </h2>
        <div class="kc-records-split">
            <!-- Longest Running -->
            <div>
                <div class="kc-records-col-title">⏱ LONGEST RUNNING</div>
                <?php if (!empty($longest_running)) : ?>
                <table class="kc-terminal-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Track</th>
                            <th>Artist</th>
                            <th style="text-align:right;">Weeks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($longest_running as $idx => $lr) : ?>
                        <tr>
                            <td class="td-rank"><?php echo $idx + 1; ?></td>
                            <td class="td-track"><?php echo esc_html($lr->track_name); ?></td>
                            <td class="td-artist"><?php echo esc_html($lr->artist_name); ?></td>
                            <td class="td-weeks" style="text-align:right;"><?php echo intval($lr->weeks_on_chart); ?>w</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <div class="kc-empty-state">No longevity data yet.</div>
                <?php endif; ?>
            </div>

            <!-- Highest Peaks -->
            <div>
                <div class="kc-records-col-title">👑 HIGHEST PEAKS (#1 HITS)</div>
                <?php if (!empty($highest_peaks)) : ?>
                <table class="kc-terminal-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Track</th>
                            <th>Artist</th>
                            <th style="text-align:right;">Weeks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($highest_peaks as $idx => $hp) : ?>
                        <tr>
                            <td class="td-rank"><?php echo $idx + 1; ?></td>
                            <td class="td-track"><?php echo esc_html($hp->track_name); ?></td>
                            <td class="td-artist"><?php echo esc_html($hp->artist_name); ?></td>
                            <td class="td-weeks" style="text-align:right;"><?php echo intval($hp->weeks_on_chart); ?>w</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <div class="kc-empty-state">No #1 hits recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
function recalculateInsights() {
    const btn = document.getElementById('insights-refresh-btn');
    if (!btn) return;

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="dashicons dashicons-update" style="animation: kc-spin 1s linear infinite;"></span> SYNCING...';

    jQuery.post(ajaxurl, {
        action: 'charts_recalculate_intel',
        nonce: '<?php echo wp_create_nonce("charts_admin_action"); ?>'
    }, function(res) {
        if (res.success) {
            if (window.ChartsToast) {
                window.ChartsToast.show('success', 'Intelligence brief re-synced successfully.', 'Insights Engine');
            }
            setTimeout(() => location.reload(), 800);
        } else {
            if (window.ChartsToast) {
                window.ChartsToast.show('error', res.data.message || 'Re-sync failed.', 'Insights Engine');
            } else {
                alert('Error: ' + (res.data.message || 'Unknown error'));
            }
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }).fail(function() {
        if (window.ChartsToast) {
            window.ChartsToast.show('error', 'Connection lost during re-sync.', 'Insights Engine');
        } else {
            alert('Connection error during re-sync.');
        }
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
