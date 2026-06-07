<?php
/**
 * Kontentainment Charts — Forecast Engine
 * Bento Grid Dashboard — Predictive analytics, viral radar, and probability projections.
 */

global $wpdb;

$intel_table   = $wpdb->prefix . 'charts_intelligence';
$entries_table = $wpdb->prefix . 'charts_entries';
$tracks_table  = $wpdb->prefix . 'charts_tracks';
$artists_table = $wpdb->prefix . 'charts_artists';

// ─── SUMMARY METRICS ───────────────────────────────────────────────
$expected_risers = (int) $wpdb->get_var("
	SELECT COUNT(*) FROM $intel_table i
	WHERE i.entity_type = 'track'
	AND i.predicted_next_week < (
		SELECT rank_position FROM $entries_table
		WHERE item_id = i.entity_id AND item_type = 'track'
		ORDER BY id DESC LIMIT 1
	)
");

$potential_no1 = (int) $wpdb->get_var("
	SELECT COUNT(*) FROM $intel_table i
	WHERE i.entity_type = 'track'
	AND i.predicted_next_week = 1
	AND i.predicted_next_week < (
		SELECT rank_position FROM $entries_table
		WHERE item_id = i.entity_id AND item_type = 'track'
		ORDER BY id DESC LIMIT 1
	)
");

$viral_candidates_count = (int) $wpdb->get_var("
	SELECT COUNT(*) FROM $intel_table
	WHERE entity_type = 'track' AND viral_score >= 65
");

$forecast_confidence_avg = (float) $wpdb->get_var("
	SELECT AVG(confidence_score) FROM $intel_table
	WHERE entity_type = 'track' AND confidence_score > 0
");

// ─── SONGS TO WATCH ────────────────────────────────────────────────
$songs_to_watch = $wpdb->get_results("
	SELECT i.*, t.title AS track_name, art.display_name AS artist_name,
	       t.cover_image, i.momentum_score, i.confidence_score,
	       i.predicted_next_week, i.predicted_peak, i.metadata_json,
	(
		SELECT rank_position FROM $entries_table
		WHERE item_id = i.entity_id AND item_type = 'track'
		ORDER BY id DESC LIMIT 1
	) AS current_rank
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track'
	AND i.predicted_next_week < (
		SELECT rank_position FROM $entries_table
		WHERE item_id = i.entity_id AND item_type = 'track'
		ORDER BY id DESC LIMIT 1
	)
	ORDER BY i.confidence_score DESC, (
		(SELECT rank_position FROM $entries_table WHERE item_id = i.entity_id AND item_type = 'track' ORDER BY id DESC LIMIT 1)
		- i.predicted_next_week
	) DESC
	LIMIT 10
");

// ─── FUTURE TOP 10 PROJECTIONS ─────────────────────────────────────
$future_top10 = $wpdb->get_results("
	SELECT i.*, t.title AS track_name, art.display_name AS artist_name,
	       t.cover_image, i.predicted_next_week, i.predicted_next_month,
	       i.confidence_score
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track'
	AND i.predicted_next_week <= 10
	ORDER BY i.predicted_next_week ASC
	LIMIT 10
");

// ─── VIRAL CANDIDATES ──────────────────────────────────────────────
$viral_all = $wpdb->get_results("
	SELECT i.*, t.title AS track_name, art.display_name AS artist_name,
	       t.cover_image, i.viral_score, i.metadata_json
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track' AND i.viral_score >= 65
	ORDER BY i.viral_score DESC
	LIMIT 18
");

$viral_emerging = [];
$viral_rising   = [];
$viral_exploding = [];
foreach ($viral_all as $v) {
	if ($v->viral_score >= 88)      $viral_exploding[] = $v;
	elseif ($v->viral_score >= 78)  $viral_rising[]    = $v;
	else                            $viral_emerging[]   = $v;
}

// ─── PROBABILITY DATA (TOP 5) ──────────────────────────────────────
$probability_tracks = $wpdb->get_results("
	SELECT i.*, t.title AS track_name, art.display_name AS artist_name,
	       t.cover_image, i.metadata_json, i.confidence_score
	FROM $intel_table i
	JOIN $tracks_table t ON t.id = i.entity_id
	LEFT JOIN $artists_table art ON art.id = t.primary_artist_id
	WHERE i.entity_type = 'track'
	AND i.metadata_json IS NOT NULL
	AND i.metadata_json != ''
	ORDER BY i.confidence_score DESC
	LIMIT 5
");
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════════════
   FORECAST ENGINE — BENTO GRID UI
   Design tokens: #f8fafc bg | #ffffff card | #e2e8f0 border
   ═══════════════════════════════════════════════════════════════════ */

.bento-wrap {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	background: #f8fafc;
	padding: 28px 32px 48px;
	min-height: 100vh;
	color: #0f172a;
	-webkit-font-smoothing: antialiased;
}
.bento-wrap * { box-sizing: border-box; }

/* ── HEADER BAR ─────────────────────────────────────────── */
.bento-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 24px;
}
.bento-header-left {}
.bento-title {
	font-size: 20px;
	font-weight: 800;
	color: #0f172a;
	margin: 0 0 4px;
	letter-spacing: -0.02em;
	display: flex;
	align-items: center;
	gap: 8px;
}
.bento-title-emoji { font-size: 22px; }
.bento-timestamp {
	font-size: 11px;
	color: #64748b;
	font-weight: 500;
	letter-spacing: 0.02em;
}

.bento-btn {
	background: #0f172a;
	color: #ffffff;
	border: none;
	border-radius: 8px;
	font-family: 'Inter', sans-serif;
	font-size: 13px;
	font-weight: 600;
	padding: 9px 20px;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	transition: background 0.18s, transform 0.12s;
}
.bento-btn:hover { background: #1e293b; transform: translateY(-1px); }
.bento-btn:active { transform: translateY(0); }
.bento-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
.bento-btn-icon { font-size: 15px; }

/* ── SECTION LABEL ──────────────────────────────────────── */
.bento-section-label {
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #64748b;
	margin: 0 0 12px;
	display: flex;
	align-items: center;
	gap: 8px;
}
.bento-section-label .bento-count-pill {
	background: #f1f5f9;
	border: 1px solid #e2e8f0;
	color: #64748b;
	font-size: 10px;
	font-weight: 600;
	padding: 2px 8px;
	border-radius: 20px;
}

/* ── BASE CARD ──────────────────────────────────────────── */
.bento-card {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	padding: 24px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
}

/* ══════════════════════════════════════════════════════════
   STATS ROW — 4 cards
   ══════════════════════════════════════════════════════════ */
.bento-stats-grid {
	display: grid;
	grid-template-columns: 1.4fr 1fr 1fr 1.3fr;
	gap: 16px;
	margin-bottom: 24px;
}
.bento-stat-card {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	padding: 22px 24px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
	position: relative;
	overflow: hidden;
}
.bento-stat-accent {
	position: absolute;
	top: 0; left: 0; right: 0;
	height: 3px;
	border-radius: 16px 16px 0 0;
}
.bento-stat-label {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #64748b;
	margin-bottom: 10px;
}
.bento-stat-value {
	font-size: 36px;
	font-weight: 800;
	line-height: 1;
	letter-spacing: -0.03em;
	margin-bottom: 4px;
}
.bento-stat-sublabel {
	font-size: 11px;
	color: #94a3b8;
	font-weight: 500;
}
.bento-stat-green  .bento-stat-accent { background: #10b981; }
.bento-stat-violet .bento-stat-accent { background: #8b5cf6; }
.bento-stat-amber  .bento-stat-accent { background: #f59e0b; }
.bento-stat-blue   .bento-stat-accent { background: #3b82f6; }
.bento-stat-green  .bento-stat-value  { color: #10b981; }
.bento-stat-violet .bento-stat-value  { color: #8b5cf6; }
.bento-stat-amber  .bento-stat-value  { color: #f59e0b; }
.bento-stat-blue   .bento-stat-value  { color: #3b82f6; }

/* Confidence ring card special layout */
.bento-stat-ring-card {
	display: flex;
	align-items: center;
	gap: 18px;
}
.bento-stat-ring-info { flex: 1; }
.bento-ring-svg-wrap { flex-shrink: 0; }
.conf-ring-fill {
	transition: stroke-dashoffset 1.1s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ══════════════════════════════════════════════════════════
   SONGS TO WATCH — horizontal scroll
   ══════════════════════════════════════════════════════════ */
.bento-songs-section { margin-bottom: 24px; }
.bento-songs-scroll-wrap {
	overflow-x: auto;
	padding-bottom: 8px;
	-webkit-overflow-scrolling: touch;
}
.bento-songs-scroll-wrap::-webkit-scrollbar { height: 4px; }
.bento-songs-scroll-wrap::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
.bento-songs-scroll-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.bento-songs-scroll-wrap::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.bento-songs-row {
	display: flex;
	gap: 16px;
	width: max-content;
}

.bento-song-card {
	width: 200px;
	flex-shrink: 0;
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	padding: 18px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
	transition: border-color 0.18s, transform 0.18s, box-shadow 0.18s;
}
.bento-song-card:hover {
	border-color: #cbd5e1;
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 12px 32px rgba(0,0,0,0.06);
}
.bento-song-cover {
	width: 60px;
	height: 60px;
	border-radius: 10px;
	object-fit: cover;
	border: 1px solid #e2e8f0;
	margin-bottom: 12px;
	display: block;
}
.bento-song-name {
	font-size: 13px;
	font-weight: 700;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	margin-bottom: 3px;
}
.bento-song-artist {
	font-size: 11px;
	color: #64748b;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	margin-bottom: 12px;
	font-weight: 500;
}
/* Rank flow */
.bento-rank-flow {
	display: flex;
	align-items: center;
	gap: 6px;
	margin-bottom: 10px;
}
.bento-rank-current {
	font-size: 13px;
	font-weight: 700;
	color: #94a3b8;
	background: #f8fafc;
	border: 1px solid #e2e8f0;
	border-radius: 6px;
	padding: 2px 7px;
}
.bento-rank-arrow {
	font-size: 15px;
	font-weight: 800;
}
.bento-rank-arrow.arrow-up   { color: #10b981; }
.bento-rank-arrow.arrow-down { color: #f43f5e; }
.bento-rank-arrow.arrow-flat { color: #94a3b8; }
.bento-rank-predicted {
	font-size: 13px;
	font-weight: 700;
	color: #10b981;
	background: #f0fdf4;
	border: 1px solid #bbf7d0;
	border-radius: 6px;
	padding: 2px 7px;
}
.bento-rank-peak {
	margin-left: auto;
	font-size: 9px;
	color: #f59e0b;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.04em;
}
/* Confidence bar */
.bento-conf-bar-wrap { margin-top: 2px; }
.bento-conf-bar-label {
	font-size: 9px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	color: #94a3b8;
	margin-bottom: 4px;
}
.bento-conf-bar-track {
	height: 5px;
	background: #f1f5f9;
	border-radius: 99px;
	overflow: hidden;
}
.bento-conf-bar-fill {
	height: 100%;
	border-radius: 99px;
	transition: width 0.7s ease;
}

/* Empty state */
.bento-empty-state {
	text-align: center;
	padding: 52px 24px;
	color: #94a3b8;
}
.bento-empty-icon {
	font-size: 40px;
	margin-bottom: 12px;
	display: block;
	opacity: 0.5;
}
.bento-empty-text {
	font-size: 13px;
	font-weight: 500;
	color: #94a3b8;
}

/* ══════════════════════════════════════════════════════════
   TWO-COL BENTO ROW
   ══════════════════════════════════════════════════════════ */
.bento-two-col {
	display: grid;
	grid-template-columns: 1.1fr 0.9fr;
	gap: 16px;
	margin-bottom: 24px;
}

/* ── LEFT: Future Top 10 ─────────────────────────────────── */
.bento-top10-card {}
.bento-top10-cols {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 0;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	overflow: hidden;
	margin-top: 14px;
}
.bento-top10-col + .bento-top10-col { border-left: 1px solid #e2e8f0; }
.bento-top10-col-head {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #64748b;
	padding: 12px 16px;
	background: #f8fafc;
	border-bottom: 1px solid #e2e8f0;
	display: flex;
	align-items: center;
	gap: 6px;
}
.bento-proj-table {
	width: 100%;
	border-collapse: collapse;
}
.bento-proj-table th {
	font-size: 9px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	color: #94a3b8;
	padding: 8px 16px;
	text-align: left;
	border-bottom: 1px solid #e2e8f0;
}
.bento-proj-table td {
	font-size: 12px;
	padding: 9px 16px;
	border-bottom: 1px solid #f1f5f9;
	color: #334155;
	vertical-align: middle;
}
.bento-proj-table tr:last-child td { border-bottom: none; }
.bento-proj-table tr:hover td { background: #fafbfc; }
.bento-proj-rank {
	font-weight: 800;
	font-size: 13px;
	width: 32px;
}
.bento-proj-name {
	font-weight: 600;
	color: #0f172a;
	max-width: 110px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.bento-proj-artist {
	color: #94a3b8;
	font-size: 11px;
	max-width: 90px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.bento-proj-conf {
	text-align: right;
	font-weight: 700;
	font-size: 11px;
	white-space: nowrap;
}

/* ── RIGHT: Viral Radar ──────────────────────────────────── */
.bento-viral-card {}
.bento-viral-sections { display: flex; flex-direction: column; gap: 14px; margin-top: 14px; }
.bento-viral-tier {
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	overflow: hidden;
}
.bento-viral-tier-head {
	font-size: 10px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.07em;
	padding: 10px 14px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	border-bottom: 1px solid #e2e8f0;
}
.bento-viral-tier-head.tier-emerging  { background: rgba(245,158,11,0.08); color: #92400e; border-bottom-color: rgba(245,158,11,0.2); }
.bento-viral-tier-head.tier-rising    { background: rgba(16,185,129,0.08); color: #065f46; border-bottom-color: rgba(16,185,129,0.2); }
.bento-viral-tier-head.tier-exploding { background: rgba(244,63,94,0.08);  color: #9f1239; border-bottom-color: rgba(244,63,94,0.2); }
.bento-viral-tier-count {
	font-size: 10px;
	font-weight: 600;
	background: rgba(255,255,255,0.7);
	padding: 1px 7px;
	border-radius: 20px;
	color: #64748b;
}
.bento-viral-list {}
.bento-viral-row {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 14px;
	border-bottom: 1px solid #f1f5f9;
	transition: background 0.15s;
}
.bento-viral-row:last-child { border-bottom: none; }
.bento-viral-row:hover { background: #fafbfc; }
.bento-viral-img {
	width: 32px;
	height: 32px;
	border-radius: 7px;
	object-fit: cover;
	border: 1px solid #e2e8f0;
	flex-shrink: 0;
}
.bento-viral-meta { flex: 1; overflow: hidden; }
.bento-viral-name {
	font-size: 12px;
	font-weight: 600;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}
.bento-viral-artist {
	font-size: 10px;
	color: #94a3b8;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}
.bento-score-pill {
	font-size: 11px;
	font-weight: 700;
	padding: 3px 9px;
	border-radius: 20px;
	flex-shrink: 0;
	letter-spacing: 0.02em;
}
.bento-score-pill.pill-emerging  { background: rgba(245,158,11,0.1); color: #b45309; border: 1px solid rgba(245,158,11,0.25); }
.bento-score-pill.pill-rising    { background: rgba(16,185,129,0.1); color: #047857; border: 1px solid rgba(16,185,129,0.25); }
.bento-score-pill.pill-exploding { background: rgba(244,63,94,0.1);  color: #be123c; border: 1px solid rgba(244,63,94,0.25); }
.bento-viral-empty {
	padding: 14px;
	font-size: 11px;
	color: #94a3b8;
	text-align: center;
}

/* ══════════════════════════════════════════════════════════
   PROBABILITY MATRIX — full width
   ══════════════════════════════════════════════════════════ */
.bento-prob-section { margin-bottom: 24px; }
.bento-prob-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 16px;
	margin-top: 12px;
}
.bento-prob-card {
	background: #ffffff;
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	padding: 20px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
	transition: transform 0.18s, box-shadow 0.18s;
}
.bento-prob-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 12px 32px rgba(0,0,0,0.06);
}
.bento-prob-header {
	display: flex;
	align-items: center;
	gap: 14px;
	margin-bottom: 16px;
	padding-bottom: 14px;
	border-bottom: 1px solid #f1f5f9;
}
.bento-prob-img {
	width: 52px;
	height: 52px;
	border-radius: 10px;
	object-fit: cover;
	border: 1px solid #e2e8f0;
	flex-shrink: 0;
}
.bento-prob-info { flex: 1; overflow: hidden; }
.bento-prob-name {
	font-size: 13px;
	font-weight: 700;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	margin-bottom: 3px;
}
.bento-prob-artist {
	font-size: 11px;
	color: #64748b;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	margin-bottom: 6px;
}
.bento-prob-conf-badge {
	display: inline-block;
	font-size: 10px;
	font-weight: 700;
	background: #f0fdf4;
	color: #059669;
	border: 1px solid #bbf7d0;
	border-radius: 20px;
	padding: 2px 8px;
}
.bento-prob-gauges { display: flex; flex-direction: column; gap: 9px; }
.bento-prob-gauge-row {
	display: flex;
	align-items: center;
	gap: 10px;
}
.bento-prob-gauge-label {
	font-size: 9px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.07em;
	color: #94a3b8;
	width: 56px;
	flex-shrink: 0;
}
.bento-prob-gauge-track {
	flex: 1;
	height: 6px;
	background: #f1f5f9;
	border-radius: 99px;
	overflow: hidden;
}
.bento-prob-gauge-fill {
	height: 100%;
	border-radius: 99px;
	transition: width 0.8s ease;
	min-width: 2px;
}
.bento-prob-gauge-fill.gauge-top10 { background: #3b82f6; }
.bento-prob-gauge-fill.gauge-top5  { background: #8b5cf6; }
.bento-prob-gauge-fill.gauge-no1   { background: #10b981; }
.bento-prob-gauge-val {
	font-size: 11px;
	font-weight: 700;
	color: #0f172a;
	width: 40px;
	text-align: right;
	flex-shrink: 0;
}
.bento-prob-gauge-val.is-zero {
	color: #cbd5e1;
	cursor: help;
}

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 1100px) {
	.bento-stats-grid { grid-template-columns: repeat(2, 1fr); }
	.bento-two-col    { grid-template-columns: 1fr; }
}
@media (max-width: 680px) {
	.bento-wrap { padding: 16px; }
	.bento-stats-grid { grid-template-columns: 1fr 1fr; }
	.bento-header { flex-direction: column; align-items: flex-start; gap: 12px; }
	.bento-prob-grid { grid-template-columns: 1fr; }
	.bento-top10-cols { grid-template-columns: 1fr; }
	.bento-top10-col + .bento-top10-col { border-left: none; border-top: 1px solid #e2e8f0; }
}
@media (max-width: 440px) {
	.bento-stats-grid { grid-template-columns: 1fr; }
}
</style>

<?php
// Pre-compute SVG ring offset
$ring_conf    = min(100, max(0, round($forecast_confidence_avg, 1)));
$circumference = 213.628; // 2 * PI * 34
$offset        = $circumference * (1 - $ring_conf / 100);
?>

<div class="bento-wrap">

	<!-- ══════════ HEADER BAR ══════════ -->
	<div class="bento-header">
		<div class="bento-header-left">
			<h1 class="bento-title">
				<span class="bento-title-emoji">🔮</span>
				FORECAST ENGINE
			</h1>
			<div class="bento-timestamp">
				<?php echo esc_html(date('D, M j Y · H:i T')); ?>
				&nbsp;·&nbsp; Predictive Analytics Module
			</div>
		</div>
		<button class="bento-btn" id="bento-recalc-btn" onclick="recalculateForecast()">
			<span class="bento-btn-icon">⟳</span>
			Re-Calculate
		</button>
	</div>

	<!-- ══════════ STATS ROW ══════════ -->
	<div class="bento-stats-grid">

		<!-- Expected Risers -->
		<div class="bento-stat-card bento-stat-green">
			<div class="bento-stat-accent"></div>
			<div class="bento-stat-label">Expected Risers</div>
			<div class="bento-stat-value"><?php echo esc_html($expected_risers); ?></div>
			<div class="bento-stat-sublabel">Tracks predicted to move up</div>
		</div>

		<!-- Potential #1 -->
		<div class="bento-stat-card bento-stat-violet">
			<div class="bento-stat-accent"></div>
			<div class="bento-stat-label">Potential #1 Shifts</div>
			<div class="bento-stat-value"><?php echo esc_html($potential_no1); ?></div>
			<div class="bento-stat-sublabel">Predicted to reach #1</div>
		</div>

		<!-- Viral Signals -->
		<div class="bento-stat-card bento-stat-amber">
			<div class="bento-stat-accent"></div>
			<div class="bento-stat-label">Viral Signals</div>
			<div class="bento-stat-value"><?php echo esc_html($viral_candidates_count); ?></div>
			<div class="bento-stat-sublabel">Score ≥ 65 threshold</div>
		</div>

		<!-- Forecast Confidence — SVG Ring -->
		<div class="bento-stat-card bento-stat-blue bento-stat-ring-card">
			<div class="bento-stat-accent"></div>
			<div class="bento-stat-ring-info">
				<div class="bento-stat-label">Forecast Confidence</div>
				<div class="bento-stat-sublabel">Average model accuracy</div>
			</div>
			<div class="bento-ring-svg-wrap">
				<svg viewBox="0 0 80 80" width="80" height="80" role="img" aria-label="<?php echo esc_attr($ring_conf); ?>% confidence">
					<!-- Background track -->
					<circle cx="40" cy="40" r="34" fill="none" stroke="#e2e8f0" stroke-width="8"/>
					<!-- Filled arc -->
					<circle cx="40" cy="40" r="34" fill="none" stroke="#3b82f6" stroke-width="8"
					        stroke-dasharray="<?php echo $circumference; ?>"
					        stroke-dashoffset="<?php echo round($offset, 2); ?>"
					        stroke-linecap="round"
					        transform="rotate(-90 40 40)"
					        class="conf-ring-fill"
					        data-target-offset="<?php echo round($offset, 2); ?>"/>
					<!-- Center label -->
					<text x="40" y="37" text-anchor="middle" font-size="14" font-weight="800" fill="#0f172a" font-family="Inter, sans-serif"><?php echo $ring_conf; ?></text>
					<text x="40" y="51" text-anchor="middle" font-size="9" font-weight="600" fill="#94a3b8" font-family="Inter, sans-serif">%</text>
				</svg>
			</div>
		</div>

	</div><!-- /.bento-stats-grid -->


	<!-- ══════════ SONGS TO WATCH ══════════ -->
	<div class="bento-songs-section">
		<div class="bento-section-label">
			🎵 Songs to Watch
			<span class="bento-count-pill"><?php echo count($songs_to_watch); ?> signals</span>
		</div>

		<?php if (empty($songs_to_watch)) : ?>
			<div class="bento-card">
				<div class="bento-empty-state">
					<span class="bento-empty-icon">📡</span>
					<div class="bento-empty-text">No rising signals detected. Run Intelligence Calculations to populate.</div>
				</div>
			</div>
		<?php else : ?>
			<div class="bento-songs-scroll-wrap">
				<div class="bento-songs-row">
					<?php foreach ($songs_to_watch as $song) :
						$conf      = round($song->confidence_score);
						$predicted = intval($song->predicted_next_week);
						$current   = intval($song->current_rank);
						$peak      = intval($song->predicted_peak);

						if ($predicted < $current) {
							$arrow_class = 'arrow-up';
							$arrow_char  = '↑';
						} elseif ($predicted > $current) {
							$arrow_class = 'arrow-down';
							$arrow_char  = '↓';
						} else {
							$arrow_class = 'arrow-flat';
							$arrow_char  = '→';
						}

						// Confidence bar colour
						if ($conf >= 70)      $bar_color = '#10b981';
						elseif ($conf >= 40)  $bar_color = '#f59e0b';
						else                  $bar_color = '#f43f5e';
					?>
						<div class="bento-song-card">
							<img class="bento-song-cover"
							     src="<?php echo esc_url($song->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>"
							     alt="<?php echo esc_attr($song->track_name); ?>"
							     onerror="this.src='<?php echo esc_url(CHARTS_URL . 'public/assets/img/placeholder.png'); ?>'">

							<span class="bento-song-name"><?php echo esc_html($song->track_name); ?></span>
							<span class="bento-song-artist"><?php echo esc_html($song->artist_name ?: '—'); ?></span>

							<div class="bento-rank-flow">
								<span class="bento-rank-current">#<?php echo $current ?: '?'; ?></span>
								<span class="bento-rank-arrow <?php echo esc_attr($arrow_class); ?>"><?php echo $arrow_char; ?></span>
								<span class="bento-rank-predicted">#<?php echo $predicted; ?></span>
								<?php if ($peak) : ?>
									<span class="bento-rank-peak">Peak #<?php echo $peak; ?></span>
								<?php endif; ?>
							</div>

							<div class="bento-conf-bar-wrap">
								<div class="bento-conf-bar-label">Confidence</div>
								<div class="bento-conf-bar-track">
									<div class="bento-conf-bar-fill"
									     style="width: <?php echo min(100, $conf); ?>%; background: <?php echo $bar_color; ?>;"></div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div><!-- /.bento-songs-section -->


	<!-- ══════════ TWO-COL: Top 10 + Viral Radar ══════════ -->
	<div class="bento-two-col">

		<!-- LEFT — Future Top 10 Projections -->
		<div class="bento-card bento-top10-card">
			<div class="bento-section-label">
				📈 Future Top 10 Projections
				<span class="bento-count-pill"><?php echo count($future_top10); ?> entries</span>
			</div>

			<?php if (empty($future_top10)) : ?>
				<div class="bento-empty-state">
					<span class="bento-empty-icon">📊</span>
					<div class="bento-empty-text">No top 10 projections available. Awaiting sufficient data.</div>
				</div>
			<?php else : ?>
				<div class="bento-top10-cols">

					<!-- Next Week -->
					<div class="bento-top10-col">
						<div class="bento-top10-col-head">📅 Next Week</div>
						<table class="bento-proj-table">
							<thead>
								<tr>
									<th>Rnk</th>
									<th>Track</th>
									<th>Artist</th>
									<th style="text-align:right;">Conf</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($future_top10 as $ft) :
									$rnk  = intval($ft->predicted_next_week);
									$conf = round($ft->confidence_score);
									if      ($conf >= 70) $ccolor = '#10b981';
									elseif  ($conf >= 40) $ccolor = '#f59e0b';
									else                  $ccolor = '#f43f5e';
								?>
									<tr>
										<td class="bento-proj-rank" style="color:#10b981;">#<?php echo $rnk; ?></td>
										<td class="bento-proj-name"><?php echo esc_html($ft->track_name); ?></td>
										<td class="bento-proj-artist"><?php echo esc_html($ft->artist_name ?: '—'); ?></td>
										<td class="bento-proj-conf" style="color:<?php echo $ccolor; ?>;"><?php echo $conf; ?>%</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<!-- Next Month -->
					<div class="bento-top10-col">
						<div class="bento-top10-col-head">🗓 Next Month</div>
						<table class="bento-proj-table">
							<thead>
								<tr>
									<th>Rnk</th>
									<th>Track</th>
									<th>Artist</th>
									<th style="text-align:right;">Conf</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($future_top10 as $ft) :
									$mrnk  = intval($ft->predicted_next_month);
									$mconf = round($ft->confidence_score * 0.85);
									if      ($mrnk  <= 10) $rcolor = '#10b981';
									else                   $rcolor = '#f59e0b';
									if      ($mconf >= 70) $ccolor = '#10b981';
									elseif  ($mconf >= 40) $ccolor = '#f59e0b';
									else                   $ccolor = '#f43f5e';
								?>
									<tr>
										<td class="bento-proj-rank" style="color:<?php echo $rcolor; ?>;">#<?php echo $mrnk ?: '?'; ?></td>
										<td class="bento-proj-name"><?php echo esc_html($ft->track_name); ?></td>
										<td class="bento-proj-artist"><?php echo esc_html($ft->artist_name ?: '—'); ?></td>
										<td class="bento-proj-conf" style="color:<?php echo $ccolor; ?>;"><?php echo $mconf; ?>%</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

				</div><!-- /.bento-top10-cols -->
			<?php endif; ?>
		</div><!-- /top10-card -->

		<!-- RIGHT — Viral Candidates Radar -->
		<div class="bento-card bento-viral-card">
			<div class="bento-section-label">
				📡 Viral Candidates Radar
				<span class="bento-count-pill"><?php echo count($viral_all); ?> detected</span>
			</div>

			<div class="bento-viral-sections">

				<!-- EXPLODING 🔥 -->
				<div class="bento-viral-tier">
					<div class="bento-viral-tier-head tier-exploding">
						🔥 Exploding
						<span class="bento-viral-tier-count"><?php echo count($viral_exploding); ?></span>
					</div>
					<div class="bento-viral-list">
						<?php if (empty($viral_exploding)) : ?>
							<div class="bento-viral-empty">No tracks at this level</div>
						<?php else : ?>
							<?php foreach ($viral_exploding as $v) : ?>
								<div class="bento-viral-row">
									<img class="bento-viral-img"
									     src="<?php echo esc_url($v->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>"
									     alt=""
									     onerror="this.src='<?php echo esc_url(CHARTS_URL . 'public/assets/img/placeholder.png'); ?>'">
									<div class="bento-viral-meta">
										<span class="bento-viral-name"><?php echo esc_html($v->track_name); ?></span>
										<span class="bento-viral-artist"><?php echo esc_html($v->artist_name ?: '—'); ?></span>
									</div>
									<span class="bento-score-pill pill-exploding"><?php echo round($v->viral_score); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<!-- RISING 🚀 -->
				<div class="bento-viral-tier">
					<div class="bento-viral-tier-head tier-rising">
						🚀 Rising
						<span class="bento-viral-tier-count"><?php echo count($viral_rising); ?></span>
					</div>
					<div class="bento-viral-list">
						<?php if (empty($viral_rising)) : ?>
							<div class="bento-viral-empty">No tracks at this level</div>
						<?php else : ?>
							<?php foreach ($viral_rising as $v) : ?>
								<div class="bento-viral-row">
									<img class="bento-viral-img"
									     src="<?php echo esc_url($v->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>"
									     alt=""
									     onerror="this.src='<?php echo esc_url(CHARTS_URL . 'public/assets/img/placeholder.png'); ?>'">
									<div class="bento-viral-meta">
										<span class="bento-viral-name"><?php echo esc_html($v->track_name); ?></span>
										<span class="bento-viral-artist"><?php echo esc_html($v->artist_name ?: '—'); ?></span>
									</div>
									<span class="bento-score-pill pill-rising"><?php echo round($v->viral_score); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

				<!-- EMERGING 🌱 -->
				<div class="bento-viral-tier">
					<div class="bento-viral-tier-head tier-emerging">
						🌱 Emerging
						<span class="bento-viral-tier-count"><?php echo count($viral_emerging); ?></span>
					</div>
					<div class="bento-viral-list">
						<?php if (empty($viral_emerging)) : ?>
							<div class="bento-viral-empty">No tracks at this level</div>
						<?php else : ?>
							<?php foreach ($viral_emerging as $v) : ?>
								<div class="bento-viral-row">
									<img class="bento-viral-img"
									     src="<?php echo esc_url($v->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>"
									     alt=""
									     onerror="this.src='<?php echo esc_url(CHARTS_URL . 'public/assets/img/placeholder.png'); ?>'">
									<div class="bento-viral-meta">
										<span class="bento-viral-name"><?php echo esc_html($v->track_name); ?></span>
										<span class="bento-viral-artist"><?php echo esc_html($v->artist_name ?: '—'); ?></span>
									</div>
									<span class="bento-score-pill pill-emerging"><?php echo round($v->viral_score); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>

			</div><!-- /.bento-viral-sections -->
		</div><!-- /viral-card -->

	</div><!-- /.bento-two-col -->


	<!-- ══════════ PREDICTION PROBABILITY MATRIX ══════════ -->
	<div class="bento-prob-section">
		<div class="bento-section-label">
			🎯 Prediction Probability Matrix
			<span class="bento-count-pill"><?php echo count($probability_tracks); ?> tracks</span>
		</div>

		<?php if (empty($probability_tracks)) : ?>
			<div class="bento-card">
				<div class="bento-empty-state">
					<span class="bento-empty-icon">🎯</span>
					<div class="bento-empty-text">No probability data. Run Re-Calculate to generate predictions.</div>
				</div>
			</div>
		<?php else : ?>
			<div class="bento-prob-grid">
				<?php foreach ($probability_tracks as $pt) :
					$meta = [];
					if (!empty($pt->metadata_json)) {
						$decoded = json_decode($pt->metadata_json, true);
						if (is_array($decoded)) $meta = $decoded;
					}
					$top10_prob = isset($meta['top_10_prob']) ? floatval($meta['top_10_prob']) : 0;
					$top5_prob  = isset($meta['top_5_prob'])  ? floatval($meta['top_5_prob'])  : 0;
					$no1_prob   = isset($meta['no_1_prob'])   ? floatval($meta['no_1_prob'])   : 0;
					$conf       = round($pt->confidence_score);
					$zero_tip   = 'Run Recalculate to generate';
				?>
					<div class="bento-prob-card">
						<div class="bento-prob-header">
							<img class="bento-prob-img"
							     src="<?php echo esc_url($pt->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>"
							     alt="<?php echo esc_attr($pt->track_name); ?>"
							     onerror="this.src='<?php echo esc_url(CHARTS_URL . 'public/assets/img/placeholder.png'); ?>'">
							<div class="bento-prob-info">
								<span class="bento-prob-name"><?php echo esc_html($pt->track_name); ?></span>
								<span class="bento-prob-artist"><?php echo esc_html($pt->artist_name ?: '—'); ?></span>
								<span class="bento-prob-conf-badge">⚡ <?php echo $conf; ?>% confidence</span>
							</div>
						</div>

						<div class="bento-prob-gauges">

							<!-- TOP 10 -->
							<div class="bento-prob-gauge-row">
								<span class="bento-prob-gauge-label">Top 10</span>
								<div class="bento-prob-gauge-track">
									<div class="bento-prob-gauge-fill gauge-top10"
									     style="width: <?php echo min(100, $top10_prob); ?>%;"></div>
								</div>
								<?php if ($top10_prob > 0) : ?>
									<span class="bento-prob-gauge-val"><?php echo round($top10_prob); ?>%</span>
								<?php else : ?>
									<span class="bento-prob-gauge-val is-zero" title="<?php echo esc_attr($zero_tip); ?>">—</span>
								<?php endif; ?>
							</div>

							<!-- TOP 5 -->
							<div class="bento-prob-gauge-row">
								<span class="bento-prob-gauge-label">Top 5</span>
								<div class="bento-prob-gauge-track">
									<div class="bento-prob-gauge-fill gauge-top5"
									     style="width: <?php echo min(100, $top5_prob); ?>%;"></div>
								</div>
								<?php if ($top5_prob > 0) : ?>
									<span class="bento-prob-gauge-val"><?php echo round($top5_prob); ?>%</span>
								<?php else : ?>
									<span class="bento-prob-gauge-val is-zero" title="<?php echo esc_attr($zero_tip); ?>">—</span>
								<?php endif; ?>
							</div>

							<!-- #1 ODDS -->
							<div class="bento-prob-gauge-row">
								<span class="bento-prob-gauge-label">#1 Odds</span>
								<div class="bento-prob-gauge-track">
									<div class="bento-prob-gauge-fill gauge-no1"
									     style="width: <?php echo min(100, $no1_prob); ?>%;"></div>
								</div>
								<?php if ($no1_prob > 0) : ?>
									<span class="bento-prob-gauge-val"><?php echo round($no1_prob); ?>%</span>
								<?php else : ?>
									<span class="bento-prob-gauge-val is-zero" title="<?php echo esc_attr($zero_tip); ?>">—</span>
								<?php endif; ?>
							</div>

						</div><!-- /.bento-prob-gauges -->
					</div><!-- /.bento-prob-card -->
				<?php endforeach; ?>
			</div><!-- /.bento-prob-grid -->
		<?php endif; ?>
	</div><!-- /.bento-prob-section -->

</div><!-- /.bento-wrap -->

<script>
(function () {
	'use strict';

	/* ── Animate SVG confidence ring on load ── */
	document.addEventListener('DOMContentLoaded', function () {
		var ring = document.querySelector('.conf-ring-fill');
		if (!ring) return;
		var targetOffset = parseFloat(ring.getAttribute('data-target-offset') || 0);
		var circumference = 213.628;
		// Start fully hidden, then animate to target
		ring.style.strokeDashoffset = circumference;
		requestAnimationFrame(function () {
			setTimeout(function () {
				ring.style.strokeDashoffset = targetOffset;
			}, 120);
		});
	});

	/* ── Re-Calculate forecast ── */
	window.recalculateForecast = function () {
		var btn = document.getElementById('bento-recalc-btn');
		if (!btn || btn.disabled) return;
		btn.disabled = true;
		btn.innerHTML = '<span class="bento-btn-icon">⟳</span> Calculating…';

		var data = new FormData();
		data.append('action', 'kc_recalculate_forecast');
		data.append('nonce',  (typeof kc_ajax !== 'undefined' ? kc_ajax.nonce : ''));

		fetch((typeof kc_ajax !== 'undefined' ? kc_ajax.url : ajaxurl), {
			method : 'POST',
			body   : data,
			credentials: 'same-origin'
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (res && res.success) {
				btn.innerHTML = '<span class="bento-btn-icon">✓</span> Done!';
				setTimeout(function () { location.reload(); }, 900);
			} else {
				var msg = res?.data?.message || res?.data || 'Recalculation failed. Please try again.';
				alert('⚠ ' + msg);
				btn.disabled = false;
				btn.innerHTML = '<span class="bento-btn-icon">⟳</span> Re-Calculate';
			}
		})
		.catch(function (err) {
			console.error('Forecast recalculate error:', err);
			alert('⚠ Network error. Please try again.');
			btn.disabled = false;
			btn.innerHTML = '<span class="bento-btn-icon">⟳</span> Re-Calculate';
		});
	};
})();
</script>
