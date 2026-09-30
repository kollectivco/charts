<style>
/* BENTO UI SYSTEM */
.bento-wrap { background: #f8fafc; padding: 24px; min-height: 100vh; font-family: 'Inter', -apple-system, sans-serif; box-sizing: border-box; }
.bento-wrap * { box-sizing: border-box; }
.bento-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.bento-title-group h1 { margin: 0 0 8px 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.bento-title-group p { margin: 0; color: #64748b; font-size: 14px; }
.bento-header-meta { display: flex; gap: 16px; align-items: center; font-size: 13px; color: #64748b; font-weight: 500; }
.bento-btn { appearance: none; border: none; background: #e2e8f0; color: #334155; font-weight: 600; font-size: 13px; padding: 10px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.bento-btn:hover:not(:disabled) { background: #cbd5e1; }
.bento-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Grid Layouts */
.bento-grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
.bento-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
.bento-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
@media (max-width: 1100px) {
    .bento-grid-2-1, .bento-grid-3, .bento-grid-2 { grid-template-columns: 1fr; }
}

/* Cards */
.bento-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; }
.bento-card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
.bento-card-title { font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
.bento-card-icon { color: #6366f1; }

/* Editorial List */
.bento-editorial-list { display: flex; flex-direction: column; gap: 16px; }
.bento-editorial-item { display: flex; gap: 16px; background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #6366f1; }
.bento-editorial-time { font-family: 'Fira Code', monospace; font-size: 11px; color: #94a3b8; white-space: nowrap; margin-top: 2px; }
.bento-editorial-text { font-size: 14px; color: #334155; line-height: 1.5; font-weight: 500; }

/* Progress Bars (Market Health) */
.bento-progress-item { margin-bottom: 16px; }
.bento-progress-item:last-child { margin-bottom: 0; }
.bento-progress-label-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px; }
.bento-progress-label { font-size: 12px; font-weight: 600; color: #475569; }
.bento-progress-val { font-size: 13px; font-weight: 700; color: #0f172a; }
.bento-progress-desc { font-size: 11px; color: #94a3b8; display: block; margin-top: 4px; }
.bento-progress-track { height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; margin-top: 8px; }
.bento-progress-fill { height: 100%; border-radius: 3px; }

/* Spotlight Card */
.bento-spotlight { text-align: center; }
.bento-spotlight-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; margin: 0 auto 16px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.bento-spotlight-name { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
.bento-spotlight-sub { font-size: 12px; color: #64748b; margin: 0 0 20px 0; }
.bento-spotlight-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; border-radius: 12px; padding: 16px; }
.bento-spotlight-stat { display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
.bento-stat-val { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 4px; }
.bento-stat-lbl { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: center; }

/* Lists / Tables */
.bento-list { display: flex; flex-direction: column; gap: 12px; flex: 1; }
.bento-list-row { display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid transparent; transition: border-color 0.2s; }
.bento-list-row:hover { border-color: #e2e8f0; background: #fff; }
.bento-row-main { display: flex; align-items: center; gap: 12px; overflow: hidden; }
.bento-row-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #e2e8f0; flex-shrink: 0; }
.bento-row-text { display: flex; flex-direction: column; overflow: hidden; }
.bento-row-title { font-size: 14px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bento-row-sub { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bento-row-meta { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; flex-shrink: 0; }
.bento-badge-up { background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
.bento-badge-down { background: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
.bento-badge-neutral { background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 700; padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
.bento-rank-num { font-size: 15px; font-weight: 900; color: #0f172a; width: 24px; text-align: center; }

.bento-empty { text-align: center; color: #94a3b8; font-size: 13px; padding: 32px 16px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; }
</style>

<div class="bento-wrap">
	<header class="bento-header">
		<div class="bento-title-group">
			<h1>💡 Insights Engine</h1>
			<p>Weekly Intelligence Brief & Editorial Context</p>
			<div class="bento-header-meta" style="margin-top:12px;">
				<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Week: <?php echo esc_html($week_start . ' — ' . $week_end); ?></span>
				<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Generated: <?php echo esc_html($report_ts); ?></span>
				<span style="color:#22c55e; font-weight:700;"><div style="display:inline-block;width:8px;height:8px;background:#22c55e;border-radius:50%;margin-right:4px;box-shadow:0 0 6px #22c55e;"></div> LIVE</span>
			</div>
		</div>
		<button id="insights-refresh-btn" class="bento-btn" onclick="recalculateInsights()">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.92-10.44l5.67-5.67"/></svg>
			Re-Sync Intelligence
		</button>
	</header>

	<?php if (!$has_data): ?>
		<div class="bento-empty" style="padding: 64px; font-size: 16px;">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom: 16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><br>
			No intelligence data available. Please run calculations in the Intelligence Nexus first.
		</div>
	<?php else: ?>

	<!-- ROW 1 -->
	<div class="bento-grid-2-1">
		<!-- Editorial -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
				<h2 class="bento-card-title">Auto-Generated Editorial</h2>
			</div>
			
			<div class="bento-editorial-list">
				<?php if (!empty($editorial_insights)): ?>
					<?php foreach ($editorial_insights as $idx => $insight): ?>
						<div class="bento-editorial-item">
							<div class="bento-editorial-time">BULLETIN <?php echo str_pad($idx+1, 3, '0', STR_PAD_LEFT); ?></div>
							<div class="bento-editorial-text"><?php echo esc_html($insight); ?></div>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="bento-empty">No editorial insights generated for this period.</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Market Health -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
				<h2 class="bento-card-title">Market Health Indices</h2>
			</div>
			
			<div style="display: flex; flex-direction: column; gap: 20px; flex: 1; justify-content: center;">
				<?php 
				$metrics = [
					['lbl' => 'Competition', 'val' => $market_health['competition'], 'desc' => 'Ratio of unique tracks to chart slots', 'color' => '#10b981'],
					['lbl' => 'Volatility', 'val' => $market_health['volatility'], 'desc' => 'Mean absolute rank movement', 'color' => '#f59e0b'],
					['lbl' => 'Retention', 'val' => $market_health['retention'], 'desc' => 'Percentage of recurring songs', 'color' => '#3b82f6'],
					['lbl' => 'Discovery', 'val' => $market_health['discovery'], 'desc' => 'Rate of new entries', 'color' => '#8b5cf6'],
					['lbl' => 'Growth', 'val' => $market_health['growth'], 'desc' => 'Week-over-week consumption', 'color' => '#ec4899']
				];
				foreach ($metrics as $m): 
					$pct = floatval($m['val']);
					if ($m['lbl'] === 'Volatility') $pct = min(100, $pct * 10); // arbitrary scaling for demo
				?>
				<div class="bento-progress-item">
					<div class="bento-progress-label-row">
						<div>
							<div class="bento-progress-label"><?php echo $m['lbl']; ?></div>
							<span class="bento-progress-desc"><?php echo $m['desc']; ?></span>
						</div>
						<div class="bento-progress-val"><?php echo number_format($m['val'], 1); ?><?php echo $m['lbl'] === 'Volatility' ? 'pts' : '%'; ?></div>
					</div>
					<div class="bento-progress-track">
						<div class="bento-progress-fill" style="width: <?php echo max(5, min(100, $pct)); ?>%; background: <?php echo $m['color']; ?>;"></div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- ROW 2 -->
	<div class="bento-grid-3">
		<!-- Artist Spotlight -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				<h2 class="bento-card-title">Artist Spotlight</h2>
			</div>
			
			<?php if ($spotlight_artist): ?>
			<div class="bento-spotlight">
				<img src="<?php echo esc_url($spotlight_artist->image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="bento-spotlight-img">
				<h3 class="bento-spotlight-name"><?php echo esc_html($spotlight_artist->display_name); ?></h3>
				<p class="bento-spotlight-sub">Highest-Ranked Artist by Composite Authority Index</p>
				
				<div class="bento-spotlight-stats">
					<div class="bento-spotlight-stat">
						<div class="bento-stat-val" style="color:#6366f1;"><?php echo number_format($spotlight_artist->artist_power_score); ?></div>
						<div class="bento-stat-lbl">Power Score</div>
					</div>
					<div class="bento-spotlight-stat">
						<div class="bento-stat-val"><?php echo number_format($spotlight_artist->weeks_on_chart); ?></div>
						<div class="bento-stat-lbl">Chart Weeks</div>
					</div>
					<div class="bento-spotlight-stat">
						<div class="bento-stat-val">#<?php echo number_format($spotlight_artist->peaks_count); ?></div>
						<div class="bento-stat-lbl">Peak Rank</div>
					</div>
					<div class="bento-spotlight-stat">
						<div class="bento-stat-val"><?php echo number_format($spotlight_artist->charting_songs); ?></div>
						<div class="bento-stat-lbl">Charting Songs</div>
					</div>
				</div>
			</div>
			<?php else: ?>
				<div class="bento-empty">No spotlight data available.</div>
			<?php endif; ?>
		</div>

		<!-- Weekly Highlights -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
				<h2 class="bento-card-title">Weekly Highlights</h2>
			</div>
			
			<div class="bento-list">
				<?php if (!empty($weekly_highlights)): ?>
					<?php foreach ($weekly_highlights as $idx => $h): ?>
					<div class="bento-list-row">
						<div class="bento-row-main">
							<span class="bento-rank-num"><?php echo $idx+1; ?></span>
							<img src="<?php echo esc_url($h->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="bento-row-img">
							<div class="bento-row-text">
								<span class="bento-row-title"><?php echo esc_html($h->track_name); ?></span>
								<span class="bento-row-sub"><?php echo esc_html($h->artist_names); ?></span>
							</div>
						</div>
						<div class="bento-row-meta">
							<span class="bento-badge-up">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
								<?php echo intval($h->movement_value); ?>
							</span>
						</div>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="bento-empty" style="margin-top:auto;margin-bottom:auto;">No significant positive movements detected this week.</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Fastest Movers -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
				<h2 class="bento-card-title">Fastest Movers</h2>
			</div>
			
			<div class="bento-list">
				<?php if (!empty($fastest_movers)): ?>
					<?php foreach ($fastest_movers as $idx => $m): 
						$growth = floatval($m->growth_rate);
						$is_up = $growth > 0;
					?>
					<div class="bento-list-row">
						<div class="bento-row-main">
							<span class="bento-rank-num"><?php echo $idx+1; ?></span>
							<img src="<?php echo esc_url($m->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" class="bento-row-img">
							<div class="bento-row-text">
								<span class="bento-row-title"><?php echo esc_html($m->track_name); ?></span>
								<span class="bento-row-sub"><?php echo esc_html($m->artist_name); ?></span>
							</div>
						</div>
						<div class="bento-row-meta">
							<span class="<?php echo $is_up ? 'bento-badge-up' : 'bento-badge-down'; ?>">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
									<?php if ($is_up): ?><polyline points="18 15 12 9 6 15"/><?php else: ?><polyline points="6 9 12 15 18 9"/><?php endif; ?>
								</svg>
								<?php echo intval(abs($growth)); ?>%
							</span>
						</div>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="bento-empty" style="margin-top:auto;margin-bottom:auto;">No mover data available.</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- ROW 3 -->
	<div class="bento-grid-2">
		<!-- Longest Running -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<h2 class="bento-card-title">Longest Running</h2>
			</div>
			<div class="bento-list">
				<?php if (!empty($longest_running)): ?>
					<?php foreach ($longest_running as $idx => $lr): ?>
					<div class="bento-list-row">
						<div class="bento-row-main">
							<span class="bento-rank-num"><?php echo $idx+1; ?></span>
							<div class="bento-row-text">
								<span class="bento-row-title"><?php echo esc_html($lr->track_name); ?></span>
								<span class="bento-row-sub"><?php echo esc_html($lr->artist_name); ?></span>
							</div>
						</div>
						<div class="bento-row-meta">
							<span class="bento-badge-neutral"><?php echo intval($lr->weeks_on_chart); ?> wks</span>
						</div>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="bento-empty">No longevity data yet.</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Highest Peaks -->
		<div class="bento-card">
			<div class="bento-card-header">
				<svg class="bento-card-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 20h20"/><path d="m4 20 2-12 4 4 4-8 4 8 2 8"/></svg>
				<h2 class="bento-card-title">Highest Peaks (#1 Hits)</h2>
			</div>
			<div class="bento-list">
				<?php if (!empty($highest_peaks)): ?>
					<?php foreach ($highest_peaks as $idx => $hp): ?>
					<div class="bento-list-row">
						<div class="bento-row-main">
							<span class="bento-rank-num"><?php echo $idx+1; ?></span>
							<div class="bento-row-text">
								<span class="bento-row-title"><?php echo esc_html($hp->track_name); ?></span>
								<span class="bento-row-sub"><?php echo esc_html($hp->artist_name); ?></span>
							</div>
						</div>
						<div class="bento-row-meta">
							<span class="bento-badge-neutral" style="background:#fef3c7; color:#92400e;">#1 Peak</span>
						</div>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="bento-empty">No #1 hits recorded yet.</div>
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
    btn.innerHTML = '<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> SYNCING...';

    const formData = new FormData();
    formData.append('action', 'charts_recalculate_intel');
    formData.append('nonce', '<?php echo wp_create_nonce("charts_admin_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            setTimeout(() => location.reload(), 800);
        } else {
            alert('Error: ' + (res?.data?.message || 'Re-sync failed.'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(() => {
        alert('Connection error during re-sync.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
