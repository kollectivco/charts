<?php
/**
 * Kontentainment Charts — Bento Metadata Module (External)
 */
global $wpdb;

$type = get_query_var('charts_type', 'artist');
if (!in_array($type, ['artist', 'track', 'video'])) $type = 'artist';

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

$table = $wpdb->prefix . 'charts_' . ($type === 'artist' ? 'artists' : ($type === 'track' ? 'tracks' : 'videos'));
$where = "WHERE 1=1";
if ($search) {
    $field = ($type === 'artist') ? 'display_name' : 'title';
    $where .= $wpdb->prepare(" AND ($field LIKE %s OR slug LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
}

$items = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC LIMIT 100");
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

$stats = [
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
    'linked' => ($type === 'video') ? $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE youtube_id != ''") : $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE spotify_id != ''"),
];
?>

<div class="bento-grid">
    <!-- 1. EXPLORER HEADER (SPAN 4) -->
    <div class="bento-card span-4" style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; gap:24px; align-items:center;">
            <h3 style="margin:0; font-size:20px; font-weight:900;">Metadata Explorer</h3>
            <div style="display:flex; background:var(--db-bg); padding:4px; border-radius:8px;">
                <a href="?charts_type=artist" style="padding:6px 16px; border-radius:6px; font-size:12px; font-weight:800; text-decoration:none; <?php echo $type === 'artist' ? 'background:white; box-shadow:0 2px 4px rgba(0,0,0,0.05); color:var(--db-accent);' : 'color:var(--db-text-dim);'; ?>">Artists</a>
                <a href="?charts_type=track" style="padding:6px 16px; border-radius:6px; font-size:12px; font-weight:800; text-decoration:none; <?php echo $type === 'track' ? 'background:white; box-shadow:0 2px 4px rgba(0,0,0,0.05); color:var(--db-accent);' : 'color:var(--db-text-dim);'; ?>">Tracks</a>
                <a href="?charts_type=video" style="padding:6px 16px; border-radius:6px; font-size:12px; font-weight:800; text-decoration:none; <?php echo $type === 'video' ? 'background:white; box-shadow:0 2px 4px rgba(0,0,0,0.05); color:var(--db-accent);' : 'color:var(--db-text-dim);'; ?>">Videos</a>
            </div>
        </div>
        <form method="get" action="" style="width:300px; position:relative;">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Filter canonicals..." style="width:100%; border-radius:99px; border:1px solid var(--db-border); padding:10px 20px; background:var(--db-bg); font-size:13px; font-weight:600;">
        </form>
    </div>

    <!-- 2. KPI: COVERAGE -->
    <div class="bento-card">
        <label class="kpi-title"><?php echo ucfirst($type); ?> Coverage</label>
        <span class="kpi-val"><?php echo number_format($stats['total']); ?></span>
        <span class="kpi-trend" style="color:var(--db-accent);"><?php echo number_format($stats['linked']); ?> Verified Canonicals</span>
    </div>

    <!-- 3. TABLE EXPLORER (SPAN 3 / ROW 2) -->
    <div class="bento-card span-3 row-2">
        <form method="post" id="entities-bulk-form">
            <?php wp_nonce_field( 'charts_admin_action' ); ?>
            <input type="hidden" name="charts_action" value="bulk_action">
            <input type="hidden" name="entity_type" value="<?php echo esc_attr( $type ); ?>">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <select name="bulk_action_type" style="padding:8px 12px; border-radius:8px; border:1px solid var(--db-border); background:var(--db-bg); color:var(--db-text); font-size:12px; font-weight:700;">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="db-btn" style="padding:8px 16px; font-size:12px;" onclick="return confirm('Apply bulk action to selected items?');">Apply</button>
                </div>
                <span class="status-pill status-active"><?php echo number_format($total); ?> Records</span>
            </div>

            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="select-all-entities"></th>
                            <th>Entity Identity</th>
                            <th>Reference ID</th>
                            <th>Slug</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:60px;">No entities found.</td></tr>
                        <?php else: ?>
                            <?php foreach($items as $item): 
                                $label = ($type === 'artist') ? $item->display_name : ($item->title ?? '—');
                                $ref = ($type === 'video') ? ($item->youtube_id ?? '') : ($item->spotify_id ?? '');
                                $image = ($type === 'artist') ? ($item->image ?? '') : (($type === 'video') ? ($item->thumbnail ?? '') : ($item->cover_image ?? ''));
                                $edit_slug = ($type === 'artist') ? 'artist' : (($type === 'video') ? 'video' : 'track');
                            ?>
                                <tr>
                                    <td><input type="checkbox" name="item_ids[]" value="<?php echo (int) $item->id; ?>" class="entity-checkbox"></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <img src="<?php echo esc_url($image ?: CHARTS_URL.'public/assets/img/placeholder.png'); ?>" style="width:32px; height:32px; border-radius:<?php echo $type === 'artist' ? '50%' : '4px'; ?>; object-fit:cover;">
                                            <span style="font-weight:700;"><?php echo esc_html($label); ?></span>
                                        </div>
                                    </td>
                                    <td><code style="font-size:10px; opacity:0.6;"><?php echo esc_html($ref ?: '—'); ?></code></td>
                                    <td><code style="font-size:10px; opacity:0.6;"><?php echo esc_html($item->slug); ?></code></td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                                            <a href="<?php echo home_url('/charts/'.$edit_slug.'/'.$item->slug); ?>" target="_blank" class="db-btn" style="padding:4px 10px; font-size:10px;">View</a>
                                            <button type="button" class="db-btn" style="color:#e74c3c; border-color:rgba(231,76,60,0.1); padding:4px 10px; font-size:10px;" onclick="if(confirm('Really delete entity?')) { document.getElementById('single-delete-id').value = <?php echo (int) $item->id; ?>; document.getElementById('single-delete-form').submit(); }">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- 4. QUALITY AUDIT -->
    <div class="bento-card">
        <label class="kpi-title">Data Integrity</label>
        <div style="margin-top:20px;">
            <div style="height:8px; background:var(--db-bg); border-radius:4px; overflow:hidden; margin-bottom:12px;">
                <div style="width:<?php echo ($stats['total'] > 0) ? ($stats['linked'] / $stats['total'] * 100) : 0; ?>%; height:100%; background:var(--db-accent);"></div>
            </div>
            <span style="font-size:11px; font-weight:850; text-transform:uppercase; opacity:0.6;">Canonical Match Rate</span>
        </div>
    </div>

</div>

<!-- Hidden form for individual deletes -->
<form method="post" id="single-delete-form" style="display:none;">
    <?php wp_nonce_field( 'charts_admin_action' ); ?>
    <input type="hidden" name="charts_action" value="delete_entity">
    <input type="hidden" name="id" id="single-delete-id" value="">
    <input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all-entities');
    const checkboxes = document.querySelectorAll('.entity-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>
