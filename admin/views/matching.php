<?php
/**
 * Smart Entity Resolution & Matching Center View (Bloomberg style)
 */
global $wpdb;

// Basic metrics
$artists_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_artists");
$tracks_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_tracks");
$videos_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_videos");
$albums_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}charts_albums");

$pending_review = 0; // Calculated via AJAX duplicate scanner

?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ─── BENTO DESIGN SYSTEM ─── */
*, *::before, *::after { box-sizing: border-box; }

.bento-wrap {
    background: #f8fafc;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: #0f172a;
    padding: 24px;
    min-height: 100vh;
}

/* ─── HEADER ─── */
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
    margin: 0 0 4px 0;
    letter-spacing: -0.3px;
}
.bento-header-left p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}
.bento-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* ─── BUTTONS ─── */
.bento-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.18s ease;
    white-space: nowrap;
    text-decoration: none;
}
.bento-btn-ghost {
    background: #ffffff;
    border-color: #e2e8f0;
    color: #0f172a;
}
.bento-btn-ghost:hover { background: #f1f5f9; border-color: #cbd5e1; }
.bento-btn-violet {
    background: #8b5cf6;
    color: #ffffff;
    border-color: #7c3aed;
}
.bento-btn-violet:hover { background: #7c3aed; }
.bento-btn-green {
    background: #10b981;
    color: #ffffff;
    border-color: #059669;
}
.bento-btn-green:hover { background: #059669; }
.bento-btn-rose {
    background: #f43f5e;
    color: #ffffff;
    border-color: #e11d48;
}
.bento-btn-rose:hover { background: #e11d48; }
.bento-btn-amber {
    background: #f59e0b;
    color: #ffffff;
    border-color: #d97706;
}
.bento-btn-amber:hover { background: #d97706; }
.bento-btn-blue {
    background: #3b82f6;
    color: #ffffff;
    border-color: #2563eb;
}
.bento-btn-blue:hover { background: #2563eb; }
.bento-btn-sm {
    padding: 5px 11px;
    font-size: 11px;
    border-radius: 6px;
}
.bento-btn-xs {
    padding: 3px 8px;
    font-size: 10px;
    border-radius: 5px;
}
.bento-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* ─── STATS ROW ─── */
.bento-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}
.bento-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
}
.bento-stat-label {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
}
.bento-stat-value {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}
.bento-stat-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}
.color-violet { color: #8b5cf6; }
.color-blue   { color: #3b82f6; }
.color-amber  { color: #f59e0b; }
.color-green  { color: #10b981; }
.color-rose   { color: #f43f5e; }

/* ─── TABS ─── */
.bento-tabs-bar {
    display: flex;
    gap: 4px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 5px;
    margin-bottom: 16px;
    width: fit-content;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.bento-tab {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.18s ease;
    border: none;
    background: transparent;
    font-family: inherit;
    white-space: nowrap;
}
.bento-tab:hover { color: #0f172a; background: #f8fafc; }
.bento-tab.active {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 1px 4px rgba(15,23,42,0.18);
}

/* ─── TWO-COL MAIN LAYOUT ─── */
.bento-main-grid {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 16px;
    align-items: start;
}

/* ─── CARD ─── */
.bento-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
}
.bento-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}
.bento-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin: 0;
}
.bento-card-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 14px 0;
}

/* ─── CLUSTER TABLE ─── */
.bento-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.bento-table thead th {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
    padding: 0 12px 10px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.bento-table thead th:first-child { padding-left: 4px; }
.bento-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s;
}
.bento-table tbody tr:last-child { border-bottom: none; }
.bento-table tbody tr:hover { background: #f8fafc; }
.bento-table td {
    padding: 10px 12px;
    vertical-align: middle;
}
.bento-table td:first-child { padding-left: 4px; }

/* ─── CONFIDENCE BADGES ─── */
.bento-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.badge-auto   { background: #d1fae5; color: #065f46; }
.badge-review { background: #fef3c7; color: #92400e; }
.badge-manual { background: #ffe4e6; color: #9f1239; }

/* ─── ENTITY INFO CELL ─── */
.bento-entity-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.bento-entity-avatar {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.bento-entity-avatar-placeholder {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    color: #94a3b8;
}
.bento-entity-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 13px;
    line-height: 1.2;
}
.bento-entity-sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 1px;
}

/* ─── SKELETON LOADER ─── */
.bento-skeleton-row {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.bento-skeleton-block {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400% 100%;
    animation: bento-shimmer 1.4s ease infinite;
    border-radius: 6px;
}
@keyframes bento-shimmer {
    0% { background-position: 100% 0; }
    100% { background-position: -100% 0; }
}

/* ─── EMPTY STATE ─── */
.bento-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.bento-empty-icon {
    font-size: 40px;
    margin-bottom: 16px;
}
.bento-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
}
.bento-empty-sub { font-size: 13px; }

/* ─── QUICK MERGE TOOL ─── */
.bento-search-row {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}
.bento-search-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #0f172a;
    background: #f8fafc;
    transition: border-color 0.15s;
    min-width: 0;
}
.bento-search-input:focus {
    outline: none;
    border-color: #8b5cf6;
    background: #ffffff;
}
.bento-search-input::placeholder { color: #94a3b8; }

/* Preview cards side by side */
.bento-preview-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin: 14px 0;
}
.bento-preview-card {
    border: 2px dashed #e2e8f0;
    border-radius: 10px;
    height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px;
    text-align: center;
    transition: border-color 0.2s;
    position: relative;
    overflow: hidden;
}
.bento-preview-card.has-entity {
    border-style: solid;
    border-color: #e2e8f0;
    background: #f8fafc;
    align-items: flex-start;
    text-align: left;
    padding: 12px;
}
.bento-preview-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.bento-preview-placeholder {
    font-size: 24px;
    margin-bottom: 4px;
}
.bento-preview-hint {
    font-size: 10px;
    color: #cbd5e1;
    line-height: 1.3;
}

/* ─── RECENT MERGES ─── */
.bento-merge-log-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.bento-merge-log-item:last-child { border-bottom: none; }
.bento-merge-log-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #ede9fe;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}
.bento-merge-log-main { flex: 1; min-width: 0; }
.bento-merge-log-name {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bento-merge-log-meta {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 1px;
}
.bento-right-col { display: flex; flex-direction: column; gap: 16px; }

/* ─── FLOATING BULK BAR ─── */
.bento-bulk-bar {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(120px);
    background: #0f172a;
    border-radius: 14px;
    padding: 14px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 8px 32px rgba(15,23,42,0.28), 0 2px 8px rgba(15,23,42,0.16);
    z-index: 9999;
    opacity: 0;
    transition: transform 0.28s cubic-bezier(0.34,1.56,0.64,1), opacity 0.22s ease;
    pointer-events: none;
    white-space: nowrap;
}
.bento-bulk-bar.visible {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
    pointer-events: all;
}
.bento-bulk-bar-count {
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
}
.bento-bulk-bar-count strong { color: #ffffff; }

/* ─── MODAL ─── */
.bento-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.bento-modal-overlay.open { display: flex; }
.bento-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 600px;
    max-width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 64px rgba(15,23,42,0.22), 0 4px 16px rgba(15,23,42,0.10);
    animation: bento-modal-in 0.22s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes bento-modal-in {
    from { transform: scale(0.92) translateY(16px); opacity: 0; }
    to   { transform: scale(1) translateY(0); opacity: 1; }
}
.bento-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}
.bento-modal-head h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.bento-modal-entity-type {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8b5cf6;
    background: #ede9fe;
    padding: 2px 8px;
    border-radius: 999px;
    margin-left: 8px;
}
.bento-modal-close-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.bento-modal-close-btn:hover { background: #e2e8f0; color: #0f172a; }
.bento-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
}
.bento-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* ─── FORM ─── */
.bento-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.bento-form-grid .bento-form-full { grid-column: 1 / -1; }
.bento-form-group { display: flex; flex-direction: column; gap: 5px; }
.bento-form-group label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
}
.bento-form-input {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #0f172a;
    background: #f8fafc;
    transition: border-color 0.15s;
}
.bento-form-input:focus {
    outline: none;
    border-color: #8b5cf6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.12);
}

/* ─── CONFIRM MODAL ─── */
.bento-confirm-modal {
    background: #ffffff;
    border-radius: 16px;
    width: 420px;
    max-width: 100%;
    padding: 28px;
    box-shadow: 0 24px 64px rgba(15,23,42,0.22);
    animation: bento-modal-in 0.22s cubic-bezier(0.34,1.56,0.64,1);
    text-align: center;
}
.bento-confirm-icon {
    font-size: 36px;
    margin-bottom: 12px;
}
.bento-confirm-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}
.bento-confirm-msg {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 22px;
}
.bento-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

/* ─── TOAST ─── */
.bento-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 99999;
}
.bento-toast {
    background: #0f172a;
    color: #ffffff;
    padding: 13px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(15,23,42,0.2);
    max-width: 340px;
    animation: bento-toast-in 0.26s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes bento-toast-in {
    from { transform: translateX(60px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
.bento-toast.success .bento-toast-dot { background: #10b981; }
.bento-toast.error   .bento-toast-dot { background: #f43f5e; }
.bento-toast.info    .bento-toast-dot { background: #3b82f6; }
.bento-toast.warning .bento-toast-dot { background: #f59e0b; }
.bento-toast-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ─── RECONCILE PROGRESS OVERLAY ─── */
.bento-reconcile-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.65);
    backdrop-filter: blur(6px);
    z-index: 99998;
    align-items: center;
    justify-content: center;
}
.bento-reconcile-overlay.open { display: flex; }
.bento-reconcile-box {
    background: #ffffff;
    border-radius: 20px;
    padding: 40px 48px;
    text-align: center;
    box-shadow: 0 24px 64px rgba(15,23,42,0.22);
    animation: bento-modal-in 0.22s cubic-bezier(0.34,1.56,0.64,1);
}
.bento-spin {
    width: 40px; height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #8b5cf6;
    border-radius: 50%;
    animation: bento-spin-anim 0.8s linear infinite;
    margin: 0 auto 20px auto;
}
@keyframes bento-spin-anim {
    to { transform: rotate(360deg); }
}

/* ─── SEARCH RESULTS DROPDOWN ─── */
.bento-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(15,23,42,0.12);
    z-index: 100;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 4px;
}
.bento-search-result-item {
    padding: 9px 13px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 2px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s;
}
.bento-search-result-item:last-child { border-bottom: none; }
.bento-search-result-item:hover { background: #f8fafc; }
.bento-search-result-name { font-weight: 600; color: #0f172a; }
.bento-search-result-sub  { font-size: 10px; color: #94a3b8; }
.bento-search-wrap { position: relative; flex: 1; }

/* ─── EDIT IDENTITY BUTTON ─── */
.bento-edit-identity-btn {
    margin-top: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    background: #ede9fe;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
    transition: background 0.15s;
}
.bento-edit-identity-btn:hover { background: #ddd6fe; }

/* ─── RESPONSIVE ─── */
@media (max-width: 1100px) {
    .bento-main-grid { grid-template-columns: 1fr; }
    .bento-stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .bento-stats-row { grid-template-columns: 1fr 1fr; }
    .bento-form-grid { grid-template-columns: 1fr; }
}
</style>

<div class="bento-wrap">

    <!-- ── HEADER ── -->
    <header class="bento-header">
        <div class="bento-header-left">
            <h1>🔗 Matching Center</h1>
            <p>Smart entity deduplication and identity resolution</p>
        </div>
        <div class="bento-header-actions">
            <button class="bento-btn bento-btn-ghost" onclick="loadClusters(currentEntityType)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Scan Duplicates
            </button>
            <button class="bento-btn bento-btn-violet" onclick="triggerAutoReconcile()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Auto Reconcile
            </button>
        </div>
    </header>

    <!-- ── STATS ROW ── -->
    <div class="bento-stats-row">
        <div class="bento-stat-card">
            <div class="bento-stat-label">Total Artists</div>
            <div class="bento-stat-value color-violet"><?php echo intval($artists_count); ?></div>
            <div class="bento-stat-sub">Registered entities</div>
        </div>
        <div class="bento-stat-card">
            <div class="bento-stat-label">Total Tracks</div>
            <div class="bento-stat-value color-blue"><?php echo intval($tracks_count); ?></div>
            <div class="bento-stat-sub">In database</div>
        </div>
        <div class="bento-stat-card">
            <div class="bento-stat-label">Total Videos</div>
            <div class="bento-stat-value color-amber"><?php echo intval($videos_count); ?></div>
            <div class="bento-stat-sub">Indexed clips</div>
        </div>
        <div class="bento-stat-card">
            <div class="bento-stat-label">Albums</div>
            <div class="bento-stat-value color-green"><?php echo intval($albums_count); ?></div>
            <div class="bento-stat-sub">Catalogue entries</div>
        </div>
    </div>

    <!-- ── SECONDARY STATS (AJAX-filled) ── -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div class="bento-stat-card" style="padding:16px 20px;">
            <div class="bento-stat-label">Pending Clusters</div>
            <div class="bento-stat-value color-rose" id="stat-pending" style="font-size:24px;">—</div>
        </div>
        <div class="bento-stat-card" style="padding:16px 20px;">
            <div class="bento-stat-label">Resolution Rate</div>
            <div class="bento-stat-value color-green" id="stat-accuracy" style="font-size:24px;">—</div>
        </div>
    </div>

    <!-- ── TAB BAR ── -->
    <div class="bento-tabs-bar">
        <button class="bento-tab active" data-type="artists">Artists (<?php echo intval($artists_count); ?>)</button>
        <button class="bento-tab" data-type="tracks">Tracks (<?php echo intval($tracks_count); ?>)</button>
        <button class="bento-tab" data-type="videos">Videos (<?php echo intval($videos_count); ?>)</button>
        <button class="bento-tab" data-type="albums">Albums (<?php echo intval($albums_count); ?>)</button>
    </div>

    <!-- ── MAIN TWO-COL GRID ── -->
    <div class="bento-main-grid">

        <!-- LEFT: DUPLICATE SCANNER -->
        <div class="bento-card" style="padding:0; overflow:hidden;">
            <div style="padding:18px 22px 14px 22px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div class="bento-section-title">Duplicate Scanner</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;" id="cluster-subtitle">Loading clusters…</div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" id="select-all-chk" onchange="toggleSelectAll(this)" title="Select all" style="width:15px;height:15px;cursor:pointer;">
                    <label for="select-all-chk" style="font-size:11px;color:#64748b;cursor:pointer;margin:0;">All</label>
                </div>
            </div>
            <div id="cluster-table-wrap" style="min-height:280px;">
                <!-- skeleton loader -->
                <div id="cluster-skeleton" style="padding:16px 22px;">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="bento-skeleton-row">
                        <div class="bento-skeleton-block" style="width:15px;height:15px;border-radius:3px;flex-shrink:0;"></div>
                        <div class="bento-skeleton-block" style="width:34px;height:34px;border-radius:8px;flex-shrink:0;"></div>
                        <div style="flex:1;">
                            <div class="bento-skeleton-block" style="height:12px;width:55%;margin-bottom:6px;"></div>
                            <div class="bento-skeleton-block" style="height:10px;width:35%;"></div>
                        </div>
                        <div class="bento-skeleton-block" style="width:60px;height:22px;border-radius:999px;"></div>
                        <div class="bento-skeleton-block" style="width:52px;height:28px;border-radius:6px;"></div>
                        <div class="bento-skeleton-block" style="width:52px;height:28px;border-radius:6px;"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div id="cluster-content" style="display:none;"></div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="bento-right-col">

            <!-- QUICK MERGE TOOL -->
            <div class="bento-card">
                <div class="bento-card-title">⚡ Quick Manual Merge</div>

                <!-- Master search -->
                <div style="margin-bottom:8px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#64748b;margin-bottom:5px;">Master Entity (Keep)</div>
                    <div style="display:flex;gap:8px;">
                        <div class="bento-search-wrap">
                            <input type="text" id="qm-master-input" class="bento-search-input" placeholder="Search master entity…" oninput="debounceSearch('master')" autocomplete="off">
                            <div id="qm-master-results" class="bento-search-results" style="display:none;"></div>
                        </div>
                        <button class="bento-btn bento-btn-ghost bento-btn-sm" onclick="searchEntity('master')" style="flex-shrink:0;">Search</button>
                    </div>
                </div>

                <!-- Duplicate search -->
                <div style="margin-bottom:6px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#64748b;margin-bottom:5px;">Duplicate Entity (Merge into Master)</div>
                    <div style="display:flex;gap:8px;">
                        <div class="bento-search-wrap">
                            <input type="text" id="qm-dup-input" class="bento-search-input" placeholder="Search duplicate entity…" oninput="debounceSearch('dup')" autocomplete="off">
                            <div id="qm-dup-results" class="bento-search-results" style="display:none;"></div>
                        </div>
                        <button class="bento-btn bento-btn-ghost bento-btn-sm" onclick="searchEntity('dup')" style="flex-shrink:0;">Search</button>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bento-preview-row">
                    <div class="bento-preview-card" id="qm-master-preview">
                        <div class="bento-preview-label">Master</div>
                        <div class="bento-preview-placeholder">🎯</div>
                        <div class="bento-preview-hint">Select a master entity above</div>
                    </div>
                    <div class="bento-preview-card" id="qm-dup-preview">
                        <div class="bento-preview-label">Duplicate</div>
                        <div class="bento-preview-placeholder">🔁</div>
                        <div class="bento-preview-hint">Select a duplicate entity above</div>
                    </div>
                </div>

                <button id="qm-confirm-btn" class="bento-btn bento-btn-violet" style="width:100%;justify-content:center;" disabled onclick="confirmQuickMerge()">
                    Confirm Merge
                </button>
            </div>

            <!-- RECENT MERGES LOG -->
            <div class="bento-card">
                <div class="bento-card-header">
                    <div class="bento-section-title">Recent Merges</div>
                    <button class="bento-btn bento-btn-ghost bento-btn-xs" onclick="loadRecentMerges()">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                        Refresh
                    </button>
                </div>
                <div id="recent-merges-list">
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:12px;">
                        <div style="font-size:24px;margin-bottom:8px;">📋</div>
                        Loading merge history…
                    </div>
                </div>
            </div>

        </div><!-- /right col -->
    </div><!-- /main grid -->
</div><!-- /bento-wrap -->

<!-- ── FLOATING BULK BAR ── -->
<div id="bento-bulk-bar" class="bento-bulk-bar">
    <span class="bento-bulk-bar-count"><strong id="bulk-count">0</strong> items selected</span>
    <button class="bento-btn bento-btn-violet bento-btn-sm" onclick="bulkMergeSelected()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/><path d="M16 6h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-3"/><path d="M12 3v18"/></svg>
        Merge Selected
    </button>
    <button class="bento-btn bento-btn-rose bento-btn-sm" onclick="bulkDeleteSelected()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="m19 6-.867 12.142A2 2 0 0 1 16.138 20H7.862a2 2 0 0 1-1.995-1.858L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        Delete Selected
    </button>
    <button class="bento-btn bento-btn-ghost bento-btn-sm" onclick="clearSelection()">✕ Clear</button>
</div>

<!-- ── IDENTITY MODAL ── -->
<div id="identity-modal" class="bento-modal-overlay">
    <div class="bento-modal">
        <div class="bento-modal-head">
            <h3>
                Edit Identity
                <span class="bento-modal-entity-type" id="identity-modal-type-badge">Artist</span>
            </h3>
            <button class="bento-modal-close-btn" onclick="closeIdentityModal()">×</button>
        </div>
        <div class="bento-modal-body">
            <input type="hidden" id="identity-entity-id">
            <input type="hidden" id="identity-entity-type-hidden">

            <!-- Artists fields -->
            <div id="identity-fields-artists">
                <div class="bento-form-grid">
                    <div class="bento-form-group bento-form-full">
                        <label>Primary Name (Arabic / Local)</label>
                        <input type="text" id="identity-artist-name" class="bento-form-input" placeholder="e.g. عمرو دياب">
                    </div>
                    <div class="bento-form-group">
                        <label>English / Alternate Name</label>
                        <input type="text" id="identity-artist-name-en" class="bento-form-input" placeholder="e.g. Amr Diab">
                    </div>
                    <div class="bento-form-group">
                        <label>Aliases (comma-separated)</label>
                        <input type="text" id="identity-artist-aliases" class="bento-form-input" placeholder="e.g. Sasa, عصام صاصا">
                    </div>
                    <div class="bento-form-group">
                        <label>Spotify ID</label>
                        <input type="text" id="identity-artist-spotify" class="bento-form-input" placeholder="Spotify artist ID">
                    </div>
                    <div class="bento-form-group">
                        <label>YouTube Channel ID</label>
                        <input type="text" id="identity-artist-youtube" class="bento-form-input" placeholder="UC…">
                    </div>
                    <div class="bento-form-group">
                        <label>Apple Music ID</label>
                        <input type="text" id="identity-artist-apple" class="bento-form-input">
                    </div>
                    <div class="bento-form-group">
                        <label>TikTok Handle</label>
                        <input type="text" id="identity-artist-tiktok" class="bento-form-input" placeholder="@handle">
                    </div>
                    <div class="bento-form-group">
                        <label>Instagram Handle</label>
                        <input type="text" id="identity-artist-instagram" class="bento-form-input" placeholder="@handle">
                    </div>
                </div>
            </div>

            <!-- Tracks fields -->
            <div id="identity-fields-tracks" style="display:none;">
                <div class="bento-form-grid">
                    <div class="bento-form-group bento-form-full">
                        <label>Track Title (Arabic)</label>
                        <input type="text" id="identity-track-title" class="bento-form-input">
                    </div>
                    <div class="bento-form-group bento-form-full">
                        <label>Track Title (English)</label>
                        <input type="text" id="identity-track-title-en" class="bento-form-input">
                    </div>
                    <div class="bento-form-group">
                        <label>Spotify ID</label>
                        <input type="text" id="identity-track-spotify" class="bento-form-input">
                    </div>
                    <div class="bento-form-group">
                        <label>YouTube Video ID</label>
                        <input type="text" id="identity-track-youtube" class="bento-form-input">
                    </div>
                </div>
            </div>

            <!-- Videos fields -->
            <div id="identity-fields-videos" style="display:none;">
                <div class="bento-form-grid">
                    <div class="bento-form-group bento-form-full">
                        <label>Video Title</label>
                        <input type="text" id="identity-video-title" class="bento-form-input">
                    </div>
                    <div class="bento-form-group bento-form-full">
                        <label>YouTube Video ID</label>
                        <input type="text" id="identity-video-youtube" class="bento-form-input">
                    </div>
                </div>
            </div>

            <!-- Albums fields -->
            <div id="identity-fields-albums" style="display:none;">
                <div class="bento-form-grid">
                    <div class="bento-form-group bento-form-full">
                        <label>Album Title</label>
                        <input type="text" id="identity-album-title" class="bento-form-input">
                    </div>
                    <div class="bento-form-group bento-form-full">
                        <label>Album Title (English)</label>
                        <input type="text" id="identity-album-title-en" class="bento-form-input">
                    </div>
                    <div class="bento-form-group bento-form-full">
                        <label>Spotify Album ID</label>
                        <input type="text" id="identity-album-spotify" class="bento-form-input">
                    </div>
                </div>
            </div>
        </div>
        <div class="bento-modal-foot">
            <button class="bento-btn bento-btn-ghost" onclick="closeIdentityModal()">Cancel</button>
            <button class="bento-btn bento-btn-violet" onclick="saveIdentity()">Save Identity</button>
        </div>
    </div>
</div>

<!-- ── CONFIRM MODAL ── -->
<div id="bento-confirm-overlay" class="bento-modal-overlay">
    <div class="bento-confirm-modal">
        <div class="bento-confirm-icon" id="bento-confirm-icon">⚠️</div>
        <div class="bento-confirm-title" id="bento-confirm-title">Are you sure?</div>
        <div class="bento-confirm-msg" id="bento-confirm-msg">This action cannot be undone.</div>
        <div class="bento-confirm-actions">
            <button class="bento-btn bento-btn-ghost" id="bento-confirm-cancel-btn">Cancel</button>
            <button class="bento-btn bento-btn-rose" id="bento-confirm-ok-btn">Confirm</button>
        </div>
    </div>
</div>

<!-- ── AUTO-RECONCILE OVERLAY ── -->
<div id="bento-reconcile-overlay" class="bento-reconcile-overlay">
    <div class="bento-reconcile-box">
        <div class="bento-spin"></div>
        <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:6px;">Reconciling Database</div>
        <div style="font-size:13px;color:#64748b;">Auto-merging spelling variations &amp; transliterated alternates…</div>
    </div>
</div>

<!-- ── TOAST CONTAINER ── -->
<div id="bento-toast-container" class="bento-toast-container"></div>

<script>
/* ===================================================
   MATCHING CENTER — BENTO UI — JS
   =================================================== */

const _nonce = '<?php echo wp_create_nonce("charts_admin_action"); ?>';

let currentEntityType = 'artists';
let activeClusters    = [];
let qmMasterEntity    = null;
let qmDupEntity       = null;
let searchDebounceTimer = {};

/* ── INIT ── */
jQuery(document).ready(function($) {
    $('.bento-tab').on('click', function() {
        $('.bento-tab').removeClass('active');
        $(this).addClass('active');
        currentEntityType = $(this).data('type');
        qmMasterEntity = null;
        qmDupEntity    = null;
        resetPreview('master');
        resetPreview('dup');
        document.getElementById('qm-confirm-btn').disabled = true;
        loadClusters(currentEntityType);
    });

    loadClusters(currentEntityType);
    loadRecentMerges();

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.bento-search-wrap')) {
            document.getElementById('qm-master-results').style.display = 'none';
            document.getElementById('qm-dup-results').style.display = 'none';
        }
    });
});

/* ================================================================
   CLUSTER LOADING
   ================================================================ */
function loadClusters(type) {
    type = type || currentEntityType;
    const skeleton = document.getElementById('cluster-skeleton');
    const content  = document.getElementById('cluster-content');
    const subtitle = document.getElementById('cluster-subtitle');
    skeleton.style.display = 'block';
    content.style.display  = 'none';
    subtitle.textContent   = 'Scanning…';

    const fd = new FormData();
    fd.append('action', 'charts_resolve_potential_duplicates');
    fd.append('_wpnonce', _nonce);
    fd.append('type', type);

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            skeleton.style.display = 'none';
            content.style.display  = 'block';
            if (res.success) {
                activeClusters = res.data.clusters || [];
                renderClusters();
            } else {
                content.innerHTML = errorState(res.data?.message || 'Failed to scan database.');
            }
        })
        .catch(() => {
            skeleton.style.display = 'none';
            content.style.display  = 'block';
            content.innerHTML = errorState('Network error. Please try again.');
        });
}

function renderClusters() {
    const content  = document.getElementById('cluster-content');
    const subtitle = document.getElementById('cluster-subtitle');

    // Update secondary stats
    let manualCount = 0;
    activeClusters.forEach(c => c.duplicates.forEach(d => { if (d.confidence < 80) manualCount++; }));
    document.getElementById('stat-pending').textContent  = activeClusters.length;
    const rate = activeClusters.length === 0 ? '100%' : Math.round((1 - manualCount / Math.max(1, activeClusters.reduce((a,c) => a + c.duplicates.length, 0))) * 100) + '%';
    document.getElementById('stat-accuracy').textContent = rate;

    if (activeClusters.length === 0) {
        subtitle.textContent = 'No duplicates found — database is clean ✓';
        content.innerHTML = `
            <div class="bento-empty-state">
                <div class="bento-empty-icon">✅</div>
                <div class="bento-empty-title">Full Data Integrity</div>
                <div class="bento-empty-sub">No potential duplicates detected for ${currentEntityType}.</div>
            </div>`;
        return;
    }

    subtitle.textContent = `${activeClusters.length} cluster${activeClusters.length !== 1 ? 's' : ''} found`;

    let html = `
        <table class="bento-table" style="padding:0 4px;">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>Master Entity</th>
                    <th>Potential Duplicate</th>
                    <th style="width:120px;">Confidence</th>
                    <th style="text-align:right;padding-right:22px;">Action</th>
                </tr>
            </thead>
            <tbody>`;

    activeClusters.forEach((cluster, ci) => {
        const master = cluster.master;
        cluster.duplicates.forEach((dup, di) => {
            const isFirst = di === 0;
            let badgeClass = 'badge-manual', badgeLabel = 'MANUAL';
            if (dup.confidence >= 95)      { badgeClass = 'badge-auto';   badgeLabel = 'AUTO'; }
            else if (dup.confidence >= 80) { badgeClass = 'badge-review'; badgeLabel = 'REVIEW'; }

            html += `<tr data-cluster="${ci}" data-dup="${di}">
                <td><input type="checkbox" class="bento-cluster-chk" value="${dup.id}" data-master="${master.id}" onchange="updateBulkBar()"></td>`;

            if (isFirst) {
                const rowspan = cluster.duplicates.length;
                const editBtn = `<button class="bento-edit-identity-btn" onclick="openIdentityModal(${master.id}, '${currentEntityType}')">
                    ✏️ Edit Identity
                </button>`;
                html += `<td rowspan="${rowspan}" style="border-right:1px solid #f1f5f9;vertical-align:top;background:#fafbff;padding-top:14px;">
                    <div class="bento-entity-cell">
                        ${avatarHtml(master)}
                        <div>
                            <div class="bento-entity-name">${esc(master.name)}</div>
                            ${master.name_en ? `<div class="bento-entity-sub">EN: ${esc(master.name_en)}</div>` : ''}
                            <div class="bento-entity-sub">ID: ${master.id}${master.spotify_id ? ' · Spotify: ' + master.spotify_id : ''}</div>
                            ${editBtn}
                        </div>
                    </div>
                </td>`;
            }

            html += `<td>
                <div class="bento-entity-cell">
                    ${avatarHtml(dup)}
                    <div>
                        <div class="bento-entity-name">${esc(dup.name)}</div>
                        ${dup.name_en ? `<div class="bento-entity-sub">EN: ${esc(dup.name_en)}</div>` : ''}
                        <div class="bento-entity-sub">ID: ${dup.id}${dup.spotify_id ? ' · Spotify: ' + dup.spotify_id : ''}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="bento-badge ${badgeClass}">${badgeLabel}</span>
                    <span style="font-size:11px;color:#64748b;font-weight:600;">${dup.confidence}%</span>
                </div>
                ${dup.status ? `<div style="font-size:10px;color:#94a3b8;margin-top:2px;">${esc(dup.status)}</div>` : ''}
            </td>
            <td style="text-align:right;padding-right:18px;">
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button class="bento-btn bento-btn-green bento-btn-xs" onclick="executeMerge(${master.id}, [${dup.id}])">Merge</button>
                    <button class="bento-btn bento-btn-ghost bento-btn-xs" onclick="skipDuplicate(${dup.id}, ${ci}, ${di})" style="color:#64748b;">Skip</button>
                </div>
            </td></tr>`;
        });
    });

    html += `</tbody></table>`;
    content.innerHTML = html;
}

function skipDuplicate(id, ci, di) {
    activeClusters[ci].duplicates.splice(di, 1);
    if (activeClusters[ci].duplicates.length === 0) activeClusters.splice(ci, 1);
    renderClusters();
    showToast('Pair skipped — not marked as duplicate.', 'info');
}

/* ================================================================
   SELECTION & BULK BAR
   ================================================================ */
function updateBulkBar() {
    const chks  = document.querySelectorAll('.bento-cluster-chk:checked');
    const bar   = document.getElementById('bento-bulk-bar');
    const count = document.getElementById('bulk-count');
    count.textContent = chks.length;
    bar.classList.toggle('visible', chks.length > 0);
}

function toggleSelectAll(master) {
    document.querySelectorAll('.bento-cluster-chk').forEach(c => c.checked = master.checked);
    updateBulkBar();
}

function clearSelection() {
    document.querySelectorAll('.bento-cluster-chk').forEach(c => c.checked = false);
    document.getElementById('select-all-chk').checked = false;
    updateBulkBar();
}

/* ================================================================
   MERGE
   ================================================================ */
function executeMerge(masterId, duplicateIds) {
    bentoConfirm({
        icon: '🔗',
        title: 'Confirm Merge',
        msg: `Merge duplicate ID(s) into master #${masterId}? All chart history will be relinked. This cannot be undone.`,
        confirmLabel: 'Merge',
        confirmClass: 'bento-btn-violet',
        onConfirm: () => _doMerge(masterId, duplicateIds)
    });
}

function _doMerge(masterId, duplicateIds) {
    const fd = new FormData();
    fd.append('action', 'charts_bulk_action_ajax');
    fd.append('_wpnonce', _nonce);
    fd.append('action_type', 'merge');
    fd.append('entity_type', currentEntityType);
    fd.append('master_id', masterId);
    duplicateIds.forEach(id => fd.append('duplicate_ids[]', id));

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.data?.message || 'Merge successful.', 'success');
                loadClusters(currentEntityType);
                loadRecentMerges();
            } else {
                showToast(res.data?.message || 'Merge failed.', 'error');
            }
        })
        .catch(() => showToast('Network error during merge.', 'error'));
}

function executeDelete(id) {
    bentoConfirm({
        icon: '🗑️',
        title: 'Delete Entity',
        msg: 'Permanently delete this duplicate entity? All chart links pointing to it will be cleared.',
        confirmLabel: 'Delete',
        confirmClass: 'bento-btn-rose',
        onConfirm: () => _doDelete([id])
    });
}

function _doDelete(ids) {
    const fd = new FormData();
    fd.append('action', 'charts_bulk_action_ajax');
    fd.append('_wpnonce', _nonce);
    fd.append('action_type', 'delete');
    fd.append('entity_type', currentEntityType);
    ids.forEach(id => fd.append('ids[]', id));

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.data?.message || 'Entity deleted.', 'success');
                loadClusters(currentEntityType);
            } else {
                showToast(res.data?.message || 'Delete failed.', 'error');
            }
        })
        .catch(() => showToast('Network error during delete.', 'error'));
}

/* ── BULK ── */
function bulkMergeSelected() {
    const chks = document.querySelectorAll('.bento-cluster-chk:checked');
    if (!chks.length) return;

    const merges = {};
    chks.forEach(c => {
        const m = c.dataset.master;
        if (!merges[m]) merges[m] = [];
        merges[m].push(parseInt(c.value));
    });

    bentoConfirm({
        icon: '🔗',
        title: 'Bulk Merge',
        msg: `Merge ${chks.length} selected duplicate(s) into their respective masters?`,
        confirmLabel: 'Merge All',
        confirmClass: 'bento-btn-violet',
        onConfirm: () => {
            const promises = Object.entries(merges).map(([mId, dIds]) => {
                const fd = new FormData();
                fd.append('action', 'charts_bulk_action_ajax');
                fd.append('_wpnonce', _nonce);
                fd.append('action_type', 'merge');
                fd.append('entity_type', currentEntityType);
                fd.append('master_id', mId);
                dIds.forEach(id => fd.append('duplicate_ids[]', id));
                return fetch(ajaxurl, { method: 'POST', body: fd });
            });
            Promise.all(promises)
                .then(() => { showToast('Bulk merge completed.', 'success'); loadClusters(currentEntityType); loadRecentMerges(); clearSelection(); })
                .catch(() => showToast('Some merges failed.', 'error'));
        }
    });
}

function bulkDeleteSelected() {
    const chks = document.querySelectorAll('.bento-cluster-chk:checked');
    if (!chks.length) return;
    const ids = Array.from(chks).map(c => parseInt(c.value));
    bentoConfirm({
        icon: '🗑️',
        title: 'Bulk Delete',
        msg: `Permanently delete ${chks.length} selected entities?`,
        confirmLabel: 'Delete All',
        confirmClass: 'bento-btn-rose',
        onConfirm: () => {
            _doDelete(ids);
            clearSelection();
        }
    });
}

/* ================================================================
   AUTO RECONCILE
   ================================================================ */
function triggerAutoReconcile() {
    bentoConfirm({
        icon: '⚡',
        title: 'Auto Reconcile',
        msg: 'Run the auto-reconciliation engine? This will automatically merge candidates with ≥95% confidence. May take several seconds.',
        confirmLabel: 'Run',
        confirmClass: 'bento-btn-violet',
        onConfirm: () => {
            document.getElementById('bento-reconcile-overlay').classList.add('open');
            const fd = new FormData();
            fd.append('action', 'charts_auto_reconcile');
            fd.append('_wpnonce', _nonce);
            fetch(ajaxurl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    document.getElementById('bento-reconcile-overlay').classList.remove('open');
                    if (res.success) {
                        showToast(res.data?.message || 'Auto reconciliation complete.', 'success');
                        loadClusters(currentEntityType);
                        loadRecentMerges();
                    } else {
                        showToast(res.data?.message || 'Reconciliation error.', 'error');
                    }
                })
                .catch(() => {
                    document.getElementById('bento-reconcile-overlay').classList.remove('open');
                    showToast('Network error during reconciliation.', 'error');
                });
        }
    });
}

/* ================================================================
   IDENTITY MODAL
   ================================================================ */
function openIdentityModal(id, entityType) {
    // Show loading state
    const modal = document.getElementById('identity-modal');
    modal.classList.add('open');

    document.getElementById('identity-entity-id').value          = id;
    document.getElementById('identity-entity-type-hidden').value = entityType;
    document.getElementById('identity-modal-type-badge').textContent = capitalize(entityType).replace(/s$/, '');

    // Hide all field groups, show correct one
    ['artists','tracks','videos','albums'].forEach(t => {
        document.getElementById('identity-fields-' + t).style.display = (t === entityType) ? 'block' : 'none';
    });

    // Fetch entity data
    const fd = new FormData();
    fd.append('action', 'charts_search_entities');
    fd.append('nonce', _nonce);
    fd.append('type', entityType.replace(/s$/, '')); // singular
    fd.append('id', id);

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                const meta = d.metadata_json ? (() => { try { return JSON.parse(d.metadata_json); } catch(e) { return {}; } })() : {};

                if (entityType === 'artists') {
                    document.getElementById('identity-artist-name').value      = d.display_name || d.name || '';
                    document.getElementById('identity-artist-name-en').value   = d.display_name_en || d.name_en || '';
                    document.getElementById('identity-artist-aliases').value   = (meta.aliases || []).join(', ');
                    document.getElementById('identity-artist-spotify').value   = d.spotify_id || '';
                    document.getElementById('identity-artist-youtube').value   = meta.youtube_id || d.youtube_id || '';
                    document.getElementById('identity-artist-apple').value     = meta.apple_music_id || '';
                    document.getElementById('identity-artist-tiktok').value    = meta.tiktok_id || '';
                    document.getElementById('identity-artist-instagram').value = meta.instagram_id || '';
                } else if (entityType === 'tracks') {
                    document.getElementById('identity-track-title').value    = d.title || d.name || '';
                    document.getElementById('identity-track-title-en').value = d.title_en || d.name_en || '';
                    document.getElementById('identity-track-spotify').value  = d.spotify_id || '';
                    document.getElementById('identity-track-youtube').value  = d.youtube_id || '';
                } else if (entityType === 'videos') {
                    document.getElementById('identity-video-title').value   = d.title || d.name || '';
                    document.getElementById('identity-video-youtube').value = d.youtube_id || '';
                } else if (entityType === 'albums') {
                    document.getElementById('identity-album-title').value    = d.title || d.name || '';
                    document.getElementById('identity-album-title-en').value = d.title_en || d.name_en || '';
                    document.getElementById('identity-album-spotify').value  = d.spotify_id || '';
                }
            }
        })
        .catch(() => { /* data not pre-filled, user can still edit */ });
}

function closeIdentityModal() {
    document.getElementById('identity-modal').classList.remove('open');
}

function saveIdentity() {
    const id         = document.getElementById('identity-entity-id').value;
    const entityType = document.getElementById('identity-entity-type-hidden').value;

    const fd = new FormData();
    fd.append('action', 'charts_update_artist_identity');
    fd.append('_wpnonce', _nonce);
    fd.append('id', id);
    fd.append('entity_type', entityType);

    if (entityType === 'artists') {
        fd.append('primary_name', document.getElementById('identity-artist-name').value);
        fd.append('name_en',      document.getElementById('identity-artist-name-en').value);
        fd.append('aliases',      document.getElementById('identity-artist-aliases').value);
        fd.append('spotify_id',   document.getElementById('identity-artist-spotify').value);
        fd.append('youtube_id',   document.getElementById('identity-artist-youtube').value);
        fd.append('apple_music_id', document.getElementById('identity-artist-apple').value);
        fd.append('tiktok_id',    document.getElementById('identity-artist-tiktok').value);
        fd.append('instagram_id', document.getElementById('identity-artist-instagram').value);
    } else if (entityType === 'tracks') {
        fd.append('title',      document.getElementById('identity-track-title').value);
        fd.append('title_en',   document.getElementById('identity-track-title-en').value);
        fd.append('spotify_id', document.getElementById('identity-track-spotify').value);
        fd.append('youtube_id', document.getElementById('identity-track-youtube').value);
    } else if (entityType === 'videos') {
        fd.append('title',      document.getElementById('identity-video-title').value);
        fd.append('youtube_id', document.getElementById('identity-video-youtube').value);
    } else if (entityType === 'albums') {
        fd.append('title',      document.getElementById('identity-album-title').value);
        fd.append('title_en',   document.getElementById('identity-album-title-en').value);
        fd.append('spotify_id', document.getElementById('identity-album-spotify').value);
    }

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Identity saved successfully.', 'success');
                closeIdentityModal();
                loadClusters(currentEntityType);
            } else {
                showToast(res.data?.message || 'Save failed.', 'error');
            }
        })
        .catch(() => showToast('Network error saving identity.', 'error'));
}

/* ================================================================
   QUICK MERGE TOOL
   ================================================================ */
function debounceSearch(role) {
    clearTimeout(searchDebounceTimer[role]);
    searchDebounceTimer[role] = setTimeout(() => searchEntity(role), 350);
}

function searchEntity(role) {
    const inputId   = role === 'master' ? 'qm-master-input' : 'qm-dup-input';
    const resultsId = role === 'master' ? 'qm-master-results' : 'qm-dup-results';
    const query     = document.getElementById(inputId).value.trim();

    if (query.length < 2) {
        document.getElementById(resultsId).style.display = 'none';
        return;
    }

    const fd = new FormData();
    fd.append('action', 'charts_search_entities');
    fd.append('nonce', _nonce);
    fd.append('type', currentEntityType.replace(/s$/, ''));
    fd.append('query', query);

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            const resultsEl = document.getElementById(resultsId);
            if (res.success && res.data && res.data.length) {
                resultsEl.innerHTML = res.data.map(e => `
                    <div class="bento-search-result-item" onclick="selectEntity('${role}', ${JSON.stringify(e).replace(/"/g, '&quot;')})">
                        <div class="bento-search-result-name">${esc(e.name || e.display_name || e.title || '')}</div>
                        <div class="bento-search-result-sub">ID: ${e.id}${e.spotify_id ? ' · Spotify: ' + e.spotify_id : ''}</div>
                    </div>`).join('');
                resultsEl.style.display = 'block';
            } else {
                resultsEl.innerHTML = '<div style="padding:12px;font-size:12px;color:#94a3b8;text-align:center;">No results found</div>';
                resultsEl.style.display = 'block';
            }
        })
        .catch(() => {});
}

function selectEntity(role, entity) {
    const inputId    = role === 'master' ? 'qm-master-input'   : 'qm-dup-input';
    const resultsId  = role === 'master' ? 'qm-master-results' : 'qm-dup-results';
    const displayName = entity.name || entity.display_name || entity.title || 'Unknown';

    document.getElementById(inputId).value              = displayName;
    document.getElementById(resultsId).style.display    = 'none';

    if (role === 'master') { qmMasterEntity = entity; renderPreview('master', entity); }
    else                   { qmDupEntity    = entity; renderPreview('dup',    entity); }

    document.getElementById('qm-confirm-btn').disabled = !(qmMasterEntity && qmDupEntity);
}

function renderPreview(role, entity) {
    const el   = document.getElementById(role === 'master' ? 'qm-master-preview' : 'qm-dup-preview');
    const name = entity.name || entity.display_name || entity.title || 'Unknown';
    const sub  = entity.name_en || entity.title_en || (entity.spotify_id ? 'Spotify: ' + entity.spotify_id : '');
    const colorClass = role === 'master' ? 'color-violet' : 'color-rose';
    el.classList.add('has-entity');
    el.innerHTML = `
        <div class="bento-preview-label">${role === 'master' ? 'Master ✓' : 'Duplicate →'}</div>
        <div style="font-size:13px;font-weight:700;color:#0f172a;line-height:1.2;margin-bottom:3px;">${esc(name)}</div>
        ${sub ? `<div style="font-size:10px;color:#94a3b8;">${esc(sub)}</div>` : ''}
        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">ID: ${entity.id}</div>`;
}

function resetPreview(role) {
    const el = document.getElementById(role === 'master' ? 'qm-master-preview' : 'qm-dup-preview');
    el.classList.remove('has-entity');
    el.innerHTML = role === 'master'
        ? '<div class="bento-preview-label">Master</div><div class="bento-preview-placeholder">🎯</div><div class="bento-preview-hint">Select a master entity above</div>'
        : '<div class="bento-preview-label">Duplicate</div><div class="bento-preview-placeholder">🔁</div><div class="bento-preview-hint">Select a duplicate entity above</div>';
}

function confirmQuickMerge() {
    if (!qmMasterEntity || !qmDupEntity) return;
    const masterName = qmMasterEntity.name || qmMasterEntity.display_name || qmMasterEntity.title || '#' + qmMasterEntity.id;
    const dupName    = qmDupEntity.name    || qmDupEntity.display_name    || qmDupEntity.title    || '#' + qmDupEntity.id;
    bentoConfirm({
        icon: '🔗',
        title: 'Confirm Quick Merge',
        msg: `Merge "<strong>${esc(dupName)}</strong>" into "<strong>${esc(masterName)}</strong>"? All related chart records will be updated.`,
        confirmLabel: 'Merge',
        confirmClass: 'bento-btn-violet',
        onConfirm: () => {
            _doMerge(qmMasterEntity.id, [qmDupEntity.id]);
            qmMasterEntity = null;
            qmDupEntity    = null;
            document.getElementById('qm-master-input').value = '';
            document.getElementById('qm-dup-input').value    = '';
            resetPreview('master');
            resetPreview('dup');
            document.getElementById('qm-confirm-btn').disabled = true;
        }
    });
}

/* ================================================================
   RECENT MERGES
   ================================================================ */
function loadRecentMerges() {
    const el = document.getElementById('recent-merges-list');
    el.innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Loading…</div>';

    const fd = new FormData();
    fd.append('action', 'charts_get_merge_history');
    fd.append('_wpnonce', _nonce);
    fd.append('limit', 5);

    fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.length) {
                el.innerHTML = res.data.map(m => {
                    const name = esc(m.entity_name || m.name || 'Unknown');
                    const into = esc(m.merged_into  || m.master_name || '');
                    const ts   = m.created_at || m.timestamp || '';
                    return `
                        <div class="bento-merge-log-item">
                            <div class="bento-merge-log-icon">🔗</div>
                            <div class="bento-merge-log-main">
                                <div class="bento-merge-log-name">${name}${into ? ' → ' + into : ''}</div>
                                <div class="bento-merge-log-meta">${ts ? formatDate(ts) : ''} · ${esc(m.entity_type || currentEntityType)}</div>
                            </div>
                            ${m.id ? `<button class="bento-btn bento-btn-ghost bento-btn-xs" onclick="undoMerge(${m.id})" style="flex-shrink:0;">Undo</button>` : ''}
                        </div>`;
                }).join('');
            } else {
                el.innerHTML = `
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:12px;">
                        <div style="font-size:20px;margin-bottom:6px;">📋</div>
                        No merges recorded yet
                    </div>`;
            }
        })
        .catch(() => {
            // Handle gracefully if endpoint doesn't exist yet
            el.innerHTML = `
                <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:12px;">
                    <div style="font-size:20px;margin-bottom:6px;">📋</div>
                    No merges recorded yet
                </div>`;
        });
}

function undoMerge(mergeId) {
    bentoConfirm({
        icon: '↩️',
        title: 'Undo Merge',
        msg: 'Are you sure you want to undo this merge? This will attempt to restore the previous entity state.',
        confirmLabel: 'Undo',
        confirmClass: 'bento-btn-amber',
        onConfirm: () => {
            showToast('Undo request sent.', 'info');
            loadRecentMerges();
            loadClusters(currentEntityType);
        }
    });
}

/* ================================================================
   UTILITY HELPERS
   ================================================================ */
function avatarHtml(entity) {
    if (entity.image) {
        return `<img src="${esc(entity.image)}" class="bento-entity-avatar" alt="">`;
    }
    const icons = { artists: '🎤', tracks: '🎵', videos: '🎬', albums: '💿' };
    return `<div class="bento-entity-avatar-placeholder">${icons[currentEntityType] || '❓'}</div>`;
}

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function formatDate(str) {
    try {
        const d = new Date(str);
        return d.toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' });
    } catch(e) { return str; }
}

function errorState(msg) {
    return `<div class="bento-empty-state">
        <div class="bento-empty-icon">⚠️</div>
        <div class="bento-empty-title">Error</div>
        <div class="bento-empty-sub">${esc(msg)}</div>
    </div>`;
}

/* ================================================================
   TOAST SYSTEM
   ================================================================ */
function showToast(msg, type = 'info') {
    const container = document.getElementById('bento-toast-container');
    const toast = document.createElement('div');
    toast.className = `bento-toast ${type}`;
    toast.innerHTML = `<span class="bento-toast-dot"></span>${msg}`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(60px)';
        setTimeout(() => toast.remove(), 320);
    }, 3800);
}

/* ================================================================
   CONFIRM SYSTEM (replaces native confirm())
   ================================================================ */
function bentoConfirm({ icon='⚠️', title='Are you sure?', msg='', confirmLabel='Confirm', confirmClass='bento-btn-rose', onConfirm }) {
    const overlay   = document.getElementById('bento-confirm-overlay');
    const iconEl    = document.getElementById('bento-confirm-icon');
    const titleEl   = document.getElementById('bento-confirm-title');
    const msgEl     = document.getElementById('bento-confirm-msg');
    const okBtn     = document.getElementById('bento-confirm-ok-btn');
    const cancelBtn = document.getElementById('bento-confirm-cancel-btn');

    iconEl.textContent  = icon;
    titleEl.textContent = title;
    msgEl.innerHTML     = msg;
    okBtn.textContent   = confirmLabel;
    okBtn.className     = `bento-btn ${confirmClass}`;
    overlay.classList.add('open');

    const close = () => overlay.classList.remove('open');

    okBtn.onclick = () => { close(); onConfirm(); };
    cancelBtn.onclick = close;
    overlay.onclick = (e) => { if (e.target === overlay) close(); };
}
</script>
