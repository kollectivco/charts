<?php
/**
 * Kontentainment Charts — Forecast Engine
 * Bloomberg Terminal Monochrome Dashboard
 * Predictive analytics, viral radar, and probability projections.
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

<style>
/* ═══════════════════════════════════════════════════════════════════
   FORECAST ENGINE — BLOOMBERG TERMINAL MONOCHROME
   ═══════════════════════════════════════════════════════════════════ */
.kc-terminal-wrap {
	background: #f1f5f9;
	color: #334155;
	font-family: 'Courier New', Courier, monospace, -apple-system;
	padding: 32px;
	min-height: 100vh;
	-webkit-font-smoothing: antialiased;
}
.kc-terminal-wrap * { box-sizing: border-box; }

/* Header */
.kc-terminal-header {
	border-bottom: 1px solid #cbd5e1;
	padding-bottom: 20px;
	margin-bottom: 28px;
	display: flex;
	justify-content: space-between;
	align-items: flex-end;
}
.kc-terminal-header h1 {
	font-family: 'Courier New', Courier, monospace;
	color: #059669;
	font-size: 22px;
	font-weight: 700;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	margin: 0;
	text-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
}
.kc-terminal-header .kc-terminal-ts {
	font-size: 11px;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

/* Summary Bar */
.kc-terminal-summary {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 16px;
	margin-bottom: 32px;
}
.kc-terminal-stat {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	border-radius: 3px;
	padding: 20px 22px;
	position: relative;
	overflow: hidden;
}
.kc-terminal-stat::before {
	content: '';
	position: absolute;
	top: 0; left: 0;
	width: 100%; height: 2px;
	background: #e2e8f0;
}
.kc-terminal-stat.stat-green::before { background: #10b981; }
.kc-terminal-stat.stat-amber::before { background: #f59e0b; }
.kc-terminal-stat.stat-red::before   { background: #f43f5e; }
.kc-terminal-stat-label {
	font-size: 9px;
	text-transform: uppercase;
	letter-spacing: 0.12em;
	color: #64748b;
	margin-bottom: 10px;
	font-weight: 700;
}
.kc-terminal-stat-value {
	font-size: 32px;
	font-weight: 700;
	color: #0f172a;
	line-height: 1;
	font-family: 'Courier New', Courier, monospace;
}
.kc-terminal-stat-value.val-green { color: #059669; }
.kc-terminal-stat-value.val-amber { color: #d97706; }

/* Section Headers */
.kc-terminal-section {
	margin-bottom: 32px;
}
.kc-terminal-section-title {
	font-size: 11px;
	text-transform: uppercase;
	letter-spacing: 0.15em;
	color: #059669;
	margin: 0 0 18px 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #cbd5e1;
	font-weight: 700;
	font-family: 'Courier New', Courier, monospace;
	display: flex;
	align-items: center;
	gap: 10px;
}
.kc-terminal-section-title .kc-section-count {
	color: #64748b;
	font-size: 9px;
}

/* Cards Grid */
.kc-terminal-cards {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
	gap: 14px;
}
.kc-terminal-card {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	border-radius: 3px;
	padding: 18px;
	transition: border-color 0.2s;
}
.kc-terminal-card:hover { border-color: #94a3b8; }

.kc-card-top {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 14px;
}
.kc-card-thumb {
	width: 44px;
	height: 44px;
	border-radius: 3px;
	object-fit: cover;
	border: 1px solid #cbd5e1;
	flex-shrink: 0;
}
.kc-card-info { overflow: hidden; flex: 1; }
.kc-card-title {
	font-size: 13px;
	font-weight: 700;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
	margin-bottom: 2px;
}
.kc-card-artist {
	font-size: 10px;
	color: #64748b;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}

/* Rank Flow */
.kc-rank-flow {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 12px;
	font-family: 'Courier New', Courier, monospace;
}
.kc-rank-current {
	font-size: 16px;
	font-weight: 700;
	color: #64748b;
}
.kc-rank-arrow {
	font-size: 14px;
	font-weight: 700;
}
.kc-rank-arrow.arrow-up   { color: #059669; }
.kc-rank-arrow.arrow-down { color: #e11d48; }
.kc-rank-arrow.arrow-flat { color: #0f172a; }
.kc-rank-predicted {
	font-size: 16px;
	font-weight: 700;
	color: #059669;
}
.kc-rank-peak {
	margin-left: auto;
	font-size: 10px;
	color: #d97706;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

/* Confidence Gauge */
.kc-gauge-row {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 6px;
}
.kc-gauge-label {
	font-size: 9px;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #64748b;
	width: 80px;
	flex-shrink: 0;
	font-weight: 700;
}
.kc-gauge-track {
	flex: 1;
	height: 6px;
	background: #e2e8f0;
	border-radius: 2px;
	overflow: hidden;
}
.kc-gauge-fill {
	height: 100%;
	border-radius: 2px;
	transition: width 0.6s ease;
}
.kc-gauge-fill.gauge-confidence {
	background: linear-gradient(90deg, #ff3366 0%, #ffaa00 40%, #00ff66 80%);
}
.kc-gauge-fill.gauge-momentum {
	background: #10b981;
}
.kc-gauge-fill.gauge-viral {
	background: #f43f5e;
}
.kc-gauge-fill.gauge-top10 {
	background: #10b981;
}
.kc-gauge-fill.gauge-top5 {
	background: #f59e0b;
}
.kc-gauge-fill.gauge-no1 {
	background: #f43f5e;
}
.kc-gauge-value {
	font-size: 11px;
	font-weight: 700;
	color: #0f172a;
	width: 36px;
	text-align: right;
	font-family: 'Courier New', Courier, monospace;
	flex-shrink: 0;
}

/* Future Top 10 Table */
.kc-terminal-table-wrap {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	border-radius: 3px;
	overflow: hidden;
}
.kc-terminal-dual {
	display: grid;
	grid-template-columns: 1fr 1fr;
}
.kc-terminal-dual-col {
	padding: 0;
}
.kc-terminal-dual-col + .kc-terminal-dual-col {
	border-left: 1px solid #cbd5e1;
}
.kc-terminal-col-header {
	font-size: 10px;
	text-transform: uppercase;
	letter-spacing: 0.12em;
	color: #059669;
	padding: 14px 18px;
	border-bottom: 1px solid #cbd5e1;
	font-weight: 700;
	font-family: 'Courier New', Courier, monospace;
	background: #f8fafc;
}
.kc-terminal-table {
	width: 100%;
	border-collapse: collapse;
}
.kc-terminal-table th {
	font-size: 9px;
	text-transform: uppercase;
	letter-spacing: 0.1em;
	color: #64748b;
	padding: 10px 18px;
	text-align: left;
	border-bottom: 1px solid #cbd5e1;
	font-weight: 700;
}
.kc-terminal-table td {
	font-size: 12px;
	padding: 10px 18px;
	border-bottom: 1px solid #e2e8f0;
	color: #334155;
	font-family: 'Courier New', Courier, monospace;
}
.kc-terminal-table tr:hover td {
	background: #f8fafc;
}
.kc-terminal-table .td-rank {
	font-weight: 700;
	color: #059669;
	font-size: 13px;
}
.kc-terminal-table .td-name {
	max-width: 160px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	color: #0f172a;
	font-weight: 600;
}
.kc-terminal-table .td-artist {
	color: #64748b;
	font-size: 11px;
	max-width: 120px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.kc-terminal-table .td-confidence {
	text-align: right;
	font-weight: 700;
}

/* Viral Radar */
.kc-viral-radar {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 16px;
}
.kc-viral-column {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	border-radius: 3px;
	overflow: hidden;
}
.kc-viral-col-header {
	font-size: 10px;
	text-transform: uppercase;
	letter-spacing: 0.12em;
	padding: 12px 16px;
	border-bottom: 1px solid #cbd5e1;
	font-weight: 700;
	font-family: 'Courier New', Courier, monospace;
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.kc-viral-col-header.tier-emerging { color: #d97706; background: rgba(245, 158, 11, 0.1); }
.kc-viral-col-header.tier-rising   { color: #059669; background: rgba(16, 185, 129, 0.1); }
.kc-viral-col-header.tier-exploding { color: #e11d48; background: rgba(244, 63, 94, 0.1); }
.kc-viral-col-header .tier-count {
	font-size: 9px;
	color: #64748b;
}
.kc-viral-item {
	padding: 14px 16px;
	border-bottom: 1px solid #e2e8f0;
	transition: background 0.2s;
}
.kc-viral-item:hover { background: #f8fafc; }
.kc-viral-item:last-child { border-bottom: none; }
.kc-viral-item-top {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 10px;
}
.kc-viral-thumb {
	width: 36px;
	height: 36px;
	border-radius: 3px;
	object-fit: cover;
	border: 1px solid #cbd5e1;
	flex-shrink: 0;
}
.kc-viral-meta { overflow: hidden; flex: 1; }
.kc-viral-name {
	font-size: 12px;
	font-weight: 700;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}
.kc-viral-artist {
	font-size: 9px;
	color: #64748b;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}
.kc-viral-score-badge {
	font-family: 'Courier New', Courier, monospace;
	font-size: 13px;
	font-weight: 700;
	flex-shrink: 0;
}
.kc-viral-score-badge.score-emerging { color: #d97706; }
.kc-viral-score-badge.score-rising   { color: #059669; }
.kc-viral-score-badge.score-exploding { color: #e11d48; }
.kc-viral-trend {
	font-size: 9px;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	margin-top: 2px;
}

/* Probability Section */
.kc-probability-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
	gap: 14px;
}
.kc-prob-card {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	border-radius: 3px;
	padding: 18px;
}
.kc-prob-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 16px;
	padding-bottom: 12px;
	border-bottom: 1px solid #cbd5e1;
}
.kc-prob-thumb {
	width: 40px;
	height: 40px;
	border-radius: 3px;
	object-fit: cover;
	border: 1px solid #cbd5e1;
}
.kc-prob-title {
	font-size: 13px;
	font-weight: 700;
	color: #0f172a;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}
.kc-prob-artist {
	font-size: 10px;
	color: #64748b;
	display: block;
}
.kc-prob-gauges { display: flex; flex-direction: column; gap: 8px; }

/* Empty State */
.kc-terminal-empty {
	padding: 40px;
	text-align: center;
	color: #64748b;
	font-size: 12px;
	font-family: 'Courier New', Courier, monospace;
	letter-spacing: 0.05em;
}

/* Recalc Button */
.kc-terminal-btn {
	background: #ffffff;
	border: 1px solid #cbd5e1;
	color: #059669;
	font-family: 'Courier New', Courier, monospace;
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	padding: 8px 18px;
	border-radius: 2px;
	cursor: pointer;
	transition: all 0.2s;
}
.kc-terminal-btn:hover {
	background: #10b981;
	color: #000000;
	border-color: #059669;
}
.kc-terminal-btn:disabled {
	opacity: 0.4;
	cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1200px) {
	.kc-terminal-summary { grid-template-columns: repeat(2, 1fr); }
	.kc-viral-radar { grid-template-columns: 1fr; }
	.kc-terminal-dual { grid-template-columns: 1fr; }
	.kc-terminal-dual-col + .kc-terminal-dual-col { border-left: none; border-top: 1px solid #cbd5e1; }
}
@media (max-width: 768px) {
	.kc-terminal-summary { grid-template-columns: 1fr; }
	.kc-terminal-cards { grid-template-columns: 1fr; }
	.kc-terminal-header { flex-direction: column; align-items: flex-start; gap: 12px; }
	.kc-probability-grid { grid-template-columns: 1fr; }
}
</style>

<div class="kc-terminal-wrap">

	<!-- ═══ HEADER ═══ -->
	<div class="kc-terminal-header">
		<div>
			<h1>> FORECAST ENGINE</h1>
			<div class="kc-terminal-ts"><?php echo date('Y-m-d H:i:s T'); ?> &mdash; PREDICTIVE ANALYTICS MODULE</div>
		</div>
		<button class="kc-terminal-btn" onclick="recalculateForecast()" id="kc-recalc-btn">
			&#x21bb; RE-CALCULATE
		</button>
	</div>

	<!-- ═══ SUMMARY BAR ═══ -->
	<div class="kc-terminal-summary">
		<div class="kc-terminal-stat stat-green">
			<div class="kc-terminal-stat-label">EXPECTED RISERS</div>
			<div class="kc-terminal-stat-value val-green"><?php echo $expected_risers; ?></div>
		</div>
		<div class="kc-terminal-stat stat-amber">
			<div class="kc-terminal-stat-label">POTENTIAL #1 SHIFTS</div>
			<div class="kc-terminal-stat-value val-amber"><?php echo $potential_no1; ?></div>
		</div>
		<div class="kc-terminal-stat stat-red">
			<div class="kc-terminal-stat-label">VIRAL CANDIDATES</div>
			<div class="kc-terminal-stat-value"><?php echo $viral_candidates_count; ?></div>
		</div>
		<div class="kc-terminal-stat">
			<div class="kc-terminal-stat-label">FORECAST CONFIDENCE</div>
			<div class="kc-terminal-stat-value"><?php echo round($forecast_confidence_avg, 1); ?>%</div>
		</div>
	</div>

	<!-- ═══ SONGS TO WATCH ═══ -->
	<div class="kc-terminal-section">
		<div class="kc-terminal-section-title">
			&#x25B6; SONGS TO WATCH
			<span class="kc-section-count">[<?php echo count($songs_to_watch); ?> SIGNALS]</span>
		</div>

		<?php if (empty($songs_to_watch)) : ?>
			<div class="kc-terminal-empty">NO RISING SIGNALS DETECTED. RUN INTELLIGENCE CALCULATIONS TO POPULATE.</div>
		<?php else : ?>
			<div class="kc-terminal-cards">
				<?php foreach ($songs_to_watch as $song) :
					$conf = round($song->confidence_score);
					$mom  = round($song->momentum_score);
					$predicted = intval($song->predicted_next_week);
					$current   = intval($song->current_rank);
					$peak      = intval($song->predicted_peak);

					if ($predicted < $current) {
						$arrow_class = 'arrow-up';
						$arrow_char  = '&#x2191;';
					} elseif ($predicted > $current) {
						$arrow_class = 'arrow-down';
						$arrow_char  = '&#x2193;';
					} else {
						$arrow_class = 'arrow-flat';
						$arrow_char  = '&#x2192;';
					}
				?>
					<div class="kc-terminal-card">
						<div class="kc-card-top">
							<img class="kc-card-thumb" src="<?php echo esc_url($song->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="">
							<div class="kc-card-info">
								<span class="kc-card-title"><?php echo esc_html($song->track_name); ?></span>
								<span class="kc-card-artist"><?php echo esc_html($song->artist_name); ?></span>
							</div>
						</div>

						<div class="kc-rank-flow">
							<span class="kc-rank-current">#<?php echo $current; ?></span>
							<span class="kc-rank-arrow <?php echo $arrow_class; ?>"><?php echo $arrow_char; ?></span>
							<span class="kc-rank-predicted">#<?php echo $predicted; ?></span>
							<span class="kc-rank-peak">PEAK #<?php echo $peak; ?></span>
						</div>

						<div class="kc-gauge-row">
							<span class="kc-gauge-label">CONFIDENCE</span>
							<div class="kc-gauge-track">
								<div class="kc-gauge-fill gauge-confidence" style="width: <?php echo min(100, $conf); ?>%;"></div>
							</div>
							<span class="kc-gauge-value"><?php echo $conf; ?>%</span>
						</div>
						<div class="kc-gauge-row">
							<span class="kc-gauge-label">MOMENTUM</span>
							<div class="kc-gauge-track">
								<div class="kc-gauge-fill gauge-momentum" style="width: <?php echo min(100, $mom); ?>%;"></div>
							</div>
							<span class="kc-gauge-value"><?php echo $mom; ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- ═══ FUTURE TOP 10 PANEL ═══ -->
	<div class="kc-terminal-section">
		<div class="kc-terminal-section-title">
			&#x25B6; FUTURE TOP 10 PROJECTIONS
			<span class="kc-section-count">[<?php echo count($future_top10); ?> ENTRIES]</span>
		</div>

		<?php if (empty($future_top10)) : ?>
			<div class="kc-terminal-table-wrap">
				<div class="kc-terminal-empty">NO TOP 10 PROJECTIONS AVAILABLE. AWAITING SUFFICIENT DATA.</div>
			</div>
		<?php else : ?>
			<div class="kc-terminal-table-wrap">
				<div class="kc-terminal-dual">
					<!-- Next Week -->
					<div class="kc-terminal-dual-col">
						<div class="kc-terminal-col-header">NEXT WEEK PROJECTION</div>
						<table class="kc-terminal-table">
							<thead>
								<tr>
									<th>RNK</th>
									<th>TRACK</th>
									<th>ARTIST</th>
									<th style="text-align:right;">CONF %</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($future_top10 as $ft) : ?>
									<tr>
										<td class="td-rank">#<?php echo intval($ft->predicted_next_week); ?></td>
										<td class="td-name"><?php echo esc_html($ft->track_name); ?></td>
										<td class="td-artist"><?php echo esc_html($ft->artist_name); ?></td>
										<td class="td-confidence" style="color: <?php echo $ft->confidence_score >= 70 ? '#00ff66' : ($ft->confidence_score >= 40 ? '#ffaa00' : '#ff3366'); ?>;">
											<?php echo round($ft->confidence_score); ?>%
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<!-- Next Month -->
					<div class="kc-terminal-dual-col">
						<div class="kc-terminal-col-header">NEXT MONTH PROJECTION</div>
						<table class="kc-terminal-table">
							<thead>
								<tr>
									<th>RNK</th>
									<th>TRACK</th>
									<th>ARTIST</th>
									<th style="text-align:right;">CONF %</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($future_top10 as $ft) : ?>
									<tr>
										<td class="td-rank" style="color: <?php echo intval($ft->predicted_next_month) <= 10 ? '#00ff66' : '#ffaa00'; ?>;">
											#<?php echo intval($ft->predicted_next_month); ?>
										</td>
										<td class="td-name"><?php echo esc_html($ft->track_name); ?></td>
										<td class="td-artist"><?php echo esc_html($ft->artist_name); ?></td>
										<td class="td-confidence" style="color: <?php echo $ft->confidence_score >= 70 ? '#00ff66' : ($ft->confidence_score >= 40 ? '#ffaa00' : '#ff3366'); ?>;">
											<?php echo round($ft->confidence_score * 0.85); ?>%
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- ═══ VIRAL CANDIDATES RADAR ═══ -->
	<div class="kc-terminal-section">
		<div class="kc-terminal-section-title">
			&#x25B6; VIRAL CANDIDATES RADAR
			<span class="kc-section-count">[<?php echo count($viral_all); ?> DETECTED]</span>
		</div>

		<?php if (empty($viral_all)) : ?>
			<div class="kc-terminal-table-wrap">
				<div class="kc-terminal-empty">NO VIRAL SIGNALS IN CURRENT DATASET.</div>
			</div>
		<?php else : ?>
			<div class="kc-viral-radar">
				<!-- EMERGING 65-78 -->
				<div class="kc-viral-column">
					<div class="kc-viral-col-header tier-emerging">
						EMERGING (65-78)
						<span class="tier-count"><?php echo count($viral_emerging); ?></span>
					</div>
					<?php if (empty($viral_emerging)) : ?>
						<div class="kc-terminal-empty">NO EMERGING SIGNALS</div>
					<?php else : ?>
						<?php foreach ($viral_emerging as $ve) :
							$meta = !empty($ve->metadata_json) ? json_decode($ve->metadata_json, true) : [];
							$trend_dir = $meta['viral_status'] ?? $meta['trend_direction'] ?? '—';
						?>
							<div class="kc-viral-item">
								<div class="kc-viral-item-top">
									<img class="kc-viral-thumb" src="<?php echo esc_url($ve->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="">
									<div class="kc-viral-meta">
										<span class="kc-viral-name"><?php echo esc_html($ve->track_name); ?></span>
										<span class="kc-viral-artist"><?php echo esc_html($ve->artist_name); ?></span>
									</div>
									<span class="kc-viral-score-badge score-emerging"><?php echo round($ve->viral_score); ?></span>
								</div>
								<div class="kc-gauge-row">
									<span class="kc-gauge-label" style="width:50px;">VIRAL</span>
									<div class="kc-gauge-track">
										<div class="kc-gauge-fill" style="width: <?php echo min(100, $ve->viral_score); ?>%; background: #f59e0b;"></div>
									</div>
								</div>
								<div class="kc-viral-trend"><?php echo esc_html(strtoupper($trend_dir)); ?></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<!-- RISING 78-88 -->
				<div class="kc-viral-column">
					<div class="kc-viral-col-header tier-rising">
						RISING (78-88)
						<span class="tier-count"><?php echo count($viral_rising); ?></span>
					</div>
					<?php if (empty($viral_rising)) : ?>
						<div class="kc-terminal-empty">NO RISING SIGNALS</div>
					<?php else : ?>
						<?php foreach ($viral_rising as $vr) :
							$meta = !empty($vr->metadata_json) ? json_decode($vr->metadata_json, true) : [];
							$trend_dir = $meta['viral_status'] ?? $meta['trend_direction'] ?? '—';
						?>
							<div class="kc-viral-item">
								<div class="kc-viral-item-top">
									<img class="kc-viral-thumb" src="<?php echo esc_url($vr->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="">
									<div class="kc-viral-meta">
										<span class="kc-viral-name"><?php echo esc_html($vr->track_name); ?></span>
										<span class="kc-viral-artist"><?php echo esc_html($vr->artist_name); ?></span>
									</div>
									<span class="kc-viral-score-badge score-rising"><?php echo round($vr->viral_score); ?></span>
								</div>
								<div class="kc-gauge-row">
									<span class="kc-gauge-label" style="width:50px;">VIRAL</span>
									<div class="kc-gauge-track">
										<div class="kc-gauge-fill" style="width: <?php echo min(100, $vr->viral_score); ?>%; background: #10b981;"></div>
									</div>
								</div>
								<div class="kc-viral-trend"><?php echo esc_html(strtoupper($trend_dir)); ?></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<!-- EXPLODING 88+ -->
				<div class="kc-viral-column">
					<div class="kc-viral-col-header tier-exploding">
						EXPLODING (88+)
						<span class="tier-count"><?php echo count($viral_exploding); ?></span>
					</div>
					<?php if (empty($viral_exploding)) : ?>
						<div class="kc-terminal-empty">NO EXPLODING SIGNALS</div>
					<?php else : ?>
						<?php foreach ($viral_exploding as $vx) :
							$meta = !empty($vx->metadata_json) ? json_decode($vx->metadata_json, true) : [];
							$trend_dir = $meta['viral_status'] ?? $meta['trend_direction'] ?? '—';
						?>
							<div class="kc-viral-item">
								<div class="kc-viral-item-top">
									<img class="kc-viral-thumb" src="<?php echo esc_url($vx->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="">
									<div class="kc-viral-meta">
										<span class="kc-viral-name"><?php echo esc_html($vx->track_name); ?></span>
										<span class="kc-viral-artist"><?php echo esc_html($vx->artist_name); ?></span>
									</div>
									<span class="kc-viral-score-badge score-exploding"><?php echo round($vx->viral_score); ?></span>
								</div>
								<div class="kc-gauge-row">
									<span class="kc-gauge-label" style="width:50px;">VIRAL</span>
									<div class="kc-gauge-track">
										<div class="kc-gauge-fill" style="width: <?php echo min(100, $vx->viral_score); ?>%; background: #f43f5e;"></div>
									</div>
								</div>
								<div class="kc-viral-trend"><?php echo esc_html(strtoupper($trend_dir)); ?></div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- ═══ PREDICTION PROBABILITY ═══ -->
	<div class="kc-terminal-section">
		<div class="kc-terminal-section-title">
			&#x25B6; PREDICTION PROBABILITY MATRIX
			<span class="kc-section-count">[TOP <?php echo count($probability_tracks); ?> TRACKS]</span>
		</div>

		<?php if (empty($probability_tracks)) : ?>
			<div class="kc-terminal-table-wrap">
				<div class="kc-terminal-empty">NO PROBABILITY DATA AVAILABLE. METADATA CALCULATIONS REQUIRED.</div>
			</div>
		<?php else : ?>
			<div class="kc-probability-grid">
				<?php foreach ($probability_tracks as $pt) :
					$meta = !empty($pt->metadata_json) ? json_decode($pt->metadata_json, true) : [];
					$top10_prob = round($meta['top_10_prob'] ?? 0);
					$top5_prob  = round($meta['top_5_prob']  ?? 0);
					$no1_prob   = round($meta['no_1_prob']   ?? 0);
				?>
					<div class="kc-prob-card">
						<div class="kc-prob-header">
							<img class="kc-prob-thumb" src="<?php echo esc_url($pt->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" alt="">
							<div style="overflow:hidden; flex:1;">
								<span class="kc-prob-title"><?php echo esc_html($pt->track_name); ?></span>
								<span class="kc-prob-artist"><?php echo esc_html($pt->artist_name); ?></span>
							</div>
							<span style="font-size:11px; color: #64748b; font-weight:700; font-family:'Courier New',monospace;"><?php echo round($pt->confidence_score); ?>% CONF</span>
						</div>
						<div class="kc-prob-gauges">
							<div class="kc-gauge-row">
								<span class="kc-gauge-label">TOP 10 %</span>
								<div class="kc-gauge-track">
									<div class="kc-gauge-fill gauge-top10" style="width: <?php echo min(100, $top10_prob); ?>%;"></div>
								</div>
								<span class="kc-gauge-value" style="color: #059669;"><?php echo $top10_prob; ?>%</span>
							</div>
							<div class="kc-gauge-row">
								<span class="kc-gauge-label">TOP 5 %</span>
								<div class="kc-gauge-track">
									<div class="kc-gauge-fill gauge-top5" style="width: <?php echo min(100, $top5_prob); ?>%;"></div>
								</div>
								<span class="kc-gauge-value" style="color: #d97706;"><?php echo $top5_prob; ?>%</span>
							</div>
							<div class="kc-gauge-row">
								<span class="kc-gauge-label">#1 %</span>
								<div class="kc-gauge-track">
									<div class="kc-gauge-fill gauge-no1" style="width: <?php echo min(100, $no1_prob); ?>%;"></div>
								</div>
								<span class="kc-gauge-value" style="color: #e11d48;"><?php echo $no1_prob; ?>%</span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

</div>

<script>
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
function recalculateForecast() {
	const btn = document.getElementById('kc-recalc-btn');
	if (!btn) return;

	btn.disabled = true;
	const orig = btn.innerHTML;
	btn.innerHTML = '&#x21bb; CALCULATING...';

	jQuery.post(ajaxurl, {
		action: 'charts_recalculate_intel',
		nonce: '<?php echo wp_create_nonce("charts_admin_action"); ?>'
	}, function(res) {
		if (res.success) {
			if (window.ChartsToast) {
				window.ChartsToast.show('success', 'Forecast engine recalibrated successfully.', 'Forecast Engine');
			}
			setTimeout(() => location.reload(), 800);
		} else {
			if (window.ChartsToast) {
				window.ChartsToast.show('error', res.data.message || 'Recalculation failed.', 'Forecast Error');
			}
			btn.disabled = false;
			btn.innerHTML = orig;
		}
	}).fail(function() {
		if (window.ChartsToast) {
			window.ChartsToast.show('error', 'Connection lost during forecast sync.', 'Link Failure');
		}
		btn.disabled = false;
		btn.innerHTML = orig;
	});
}
</script>
