<?php
/**
 * Admin View: Premium Chart Editor (Light Mode)
 * 1:1 Reference Match - High-Fidelity Redesign
 */
$manager = new \Charts\Admin\SourceManager();
$def_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$def = $def_id ? $manager->get_definition( $def_id ) : null;

// Field Mapping
$title           = $def ? $def->title : '';
$title_ar        = $def ? $def->title_ar : '';
$slug            = $def ? $def->slug : '';
$summary         = $def ? $def->chart_summary : '';
$chart_type      = $def ? $def->chart_type : 'top-songs';
$item_type       = $def ? $def->item_type : 'track';
$country         = $def ? $def->country_code : 'eg';
$frequency       = $def ? $def->frequency : 'weekly';
$platform        = $def ? $def->platform : 'all';
$cover_image_url = $def ? $def->cover_image_url : '';
$accent_color    = ($def && !empty($def->accent_color)) ? $def->accent_color : '#6366f1';
$is_public       = $def ? (int)$def->is_public : 1;
$is_featured     = $def ? (int)$def->is_featured : 0;
$archive_enabled = $def ? (int)$def->archive_enabled : 1;
$menu_order      = $def ? (int)$def->menu_order : 0;
$ordering_mode   = $def ? $def->ordering_mode : 'import';
$max_rows        = $def ? (int)$def->max_rows : 100;
?>

<div class="charts-admin-wrap premium-light">
	<form method="post">
		<?php wp_nonce_field( 'charts_admin_action' ); ?>
		<input type="hidden" name="charts_action" value="save_definition">
		<?php if ( $def_id ) : ?>
			<input type="hidden" name="id" value="<?php echo $def_id; ?>">
		<?php endif; ?>

		<header class="charts-admin-header">
			<div class="charts-admin-title-group">
				<div style="display:flex; align-items:center; gap:10px; font-size:12px; font-weight:700; color:var(--charts-text-dim); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">
					<span>Charts</span>
					<span style="opacity:0.3;">&rsaquo;</span>
					<span><?php echo $def_id ? 'Edit Chart' : 'New Chart'; ?></span>
				</div>
				<h1 class="charts-admin-title"><?php echo $def_id ? 'Edit Chart' : 'New Chart'; ?></h1>
				<p class="charts-admin-subtitle">Configure chart display settings and metadata.</p>
			</div>
			<div class="charts-admin-actions" style="display:flex; gap:12px;">
				<a href="<?php echo admin_url( 'admin.php?page=charts-definitions' ); ?>" class="charts-btn-back">
					&larr; Back
				</a>
				<button type="submit" class="charts-btn-create">
					<?php echo $def_id ? 'Update Chart' : 'Create Chart'; ?>
				</button>
			</div>
		</header>

		<?php settings_errors( 'charts' ); ?>

		<div class="premium-form-card">
			<div class="premium-form-grid">
				
				<!-- Row 1: Name & Slug -->
				<div class="form-group">
					<label for="title">Chart Name (EN) <span class="required">*</span></label>
					<input type="text" id="title" name="title" value="<?php echo esc_attr($title); ?>" class="form-control" placeholder="Hot 100" required>
				</div>
                <div class="form-group">
					<label for="title_ar">Chart Name (AR)</label>
					<input type="text" id="title_ar" name="title_ar" value="<?php echo esc_attr($title_ar); ?>" class="form-control" placeholder="أفضل ١٠٠ أغنية" dir="rtl">
				</div>
				<div class="form-group">
					<label for="slug">URL Slug <span class="required">*</span></label>
					<input type="text" id="slug" name="slug" value="<?php echo esc_attr($slug); ?>" class="form-control" placeholder="hot-100" required>
					<span class="input-helper">URL: /charts/<?php echo $slug ?: 'your-slug'; ?></span>
				</div>

				<!-- Row 2: Types & Model -->
				<div class="form-group">
					<label for="item_type">Entity Type <span class="required">*</span></label>
					<select name="item_type" id="item_type" class="form-control">
						<option value="track" <?php selected($item_type, 'track'); ?>>Tracks (Audio)</option>
						<option value="artist" <?php selected($item_type, 'artist'); ?>>Artists</option>
						<option value="video" <?php selected($item_type, 'video'); ?>>Clips & Videos</option>
					</select>
					<span class="input-helper">Core data model (e.g. Song vs Artist).</span>
				</div>
				<div class="form-group">
					<label for="chart_type">Content Category <span class="required">*</span></label>
					<select name="chart_type" id="chart_type" class="form-control">
						<optgroup label="Audio Logics" data-entity="track">
							<option value="top-songs" <?php selected($chart_type, 'top-songs'); ?>>Top Songs (Official)</option>
							<option value="viral" <?php selected($chart_type, 'viral'); ?>>Viral Trends & TikTok</option>
						</optgroup>
						<optgroup label="Visual Logics" data-entity="video">
							<option value="top-videos" <?php selected($chart_type, 'top-videos'); ?>>Top Videos / Clips</option>
						</optgroup>
						<optgroup label="Professional Logics" data-entity="artist">
							<option value="top-artists" <?php selected($chart_type, 'top-artists'); ?>>Top Artists</option>
						</optgroup>
					</select>
					<span class="input-helper">Assigns the appropriate display template for this chart.</span>
				</div>

				<!-- Row 3: Meta Configuration -->
				<div class="form-group">
					<label for="country_code">Market / Country <span class="required">*</span></label>
					<div style="display: flex; gap: 10px;">
						<input type="text" id="country_code" name="country_code" value="<?php echo esc_attr($country); ?>" class="form-control" style="width: 80px; text-align: center; text-transform: uppercase;" placeholder="EG" maxlength="2" required>
						<select id="platform" name="platform" class="form-control" style="flex: 1;">
							<option value="all" <?php selected($platform, 'all'); ?>>Omni-Platform (Mixed)</option>
							<option value="spotify" <?php selected($platform, 'spotify'); ?>>Spotify Only</option>
							<option value="youtube" <?php selected($platform, 'youtube'); ?>>YouTube Only</option>
						</select>
					</div>
					<span class="input-helper">ISO code (e.g. EG, US) and primary data source platform.</span>
				</div>
				<div class="form-group">
					<label for="frequency">Frequency / Interval</label>
					<select name="frequency" id="frequency" class="form-control">
						<option value="daily" <?php selected($frequency, 'daily'); ?>>Daily Charts</option>
						<option value="weekly" <?php selected($frequency, 'weekly'); ?>>Weekly Charts</option>
						<option value="monthly" <?php selected($frequency, 'monthly'); ?>>Monthly Charts</option>
					</select>
				</div>

				<!-- Row 3: Description -->
				<div class="form-group form-group-full">
					<label for="chart_summary">Summary / Description</label>
					<textarea id="chart_summary" name="chart_summary" class="form-control" placeholder="A brief description of this chart product..."><?php echo esc_textarea($summary); ?></textarea>
				</div>

				<!-- Row 4: Cover Image & Branding -->
				<div class="form-group">
					<label>Cover Image</label>
					<div class="image-uploader-field">
						<img id="cover_preview" src="<?php echo esc_url($cover_image_url); ?>" class="image-preview <?php echo $cover_image_url ? 'has-image' : ''; ?>" alt="Preview">
						<input type="hidden" id="cover_image_url" name="cover_image_url" value="<?php echo esc_attr($cover_image_url); ?>">
						<div class="uploader-actions">
							<button type="button" class="charts-btn-back charts-upload-trigger">
								<span class="dashicons dashicons-upload" style="margin-top:2px;"></span> Select Image
							</button>
							<button type="button" class="charts-btn-back charts-remove-image" style="color: #ef4444; border-color: #fee2e2;">
								Remove
							</button>
						</div>
					</div>
					<span class="input-helper">High resolution square artwork (1:1) recommended.</span>
				</div>
				<div class="form-group">
					<label for="accent_color">Brand Accent Color</label>
					<div class="color-picker-wrap">
						<div class="color-swatch" style="background: <?php echo esc_attr($accent_color); ?>;"></div>
						<input type="text" id="accent_color" name="accent_color" value="<?php echo esc_attr($accent_color); ?>" class="form-control" style="font-family:monospace; text-transform:uppercase;" placeholder="#6366F1">
					</div>
					<span class="input-helper">Used for dots, links, and UI accents on the frontend.</span>
					
					<div style="margin-top: 32px;">
						<label for="menu_order">Display Priority</label>
						<input type="number" id="menu_order" name="menu_order" value="<?php echo $menu_order; ?>" class="form-control" style="width: 120px;">
						<span class="input-helper">Position in rankings and grids (lower = first).</span>
					</div>
				</div>

				<!-- Row 6: Structural Sovereignty -->
				<div class="form-group form-group-full" style="padding-top: 32px; border-top: 1px solid var(--charts-border);">
					<label style="font-size: 14px; font-weight: 800; color: var(--charts-text-dim); margin-bottom: 24px; display: block;">Ranking Model & Independent Control</label>
					
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px;">
						<div class="form-group">
							<label for="ordering_mode">Ordering Model <span class="required">*</span></label>
							<select name="ordering_mode" id="ordering_mode" class="form-control">
								<option value="import" <?php selected($ordering_mode, 'import'); ?>>Automatic (Import Center & API)</option>
								<option value="manual" <?php selected($ordering_mode, 'manual'); ?>>Manual (Hand-picked & Custom Sort)</option>
							</select>
							<span class="input-helper">Determine if this chart is fed by automated systems or curated by hand.</span>
						</div>
						
						<!-- Removed Ranking Pipeline Depth -->
					</div>
				</div>

				<?php if ( $def_id ) : 
					$entries = $manager->get_manual_entries( $def_id );
				?>
					<!-- Sub-section: Live Ranking Management -->
					<div class="form-group form-group-full" style="padding-top: 32px; border-top: 1px solid var(--charts-border);">
						<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
							<div>
								<h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--charts-text);">Chart Rows & Live Rankings</h3>
								<p style="font-size: 13px; color: var(--charts-text-dim); margin-top: 4px;">
									<span class="mode-notice mode-notice-manual" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?>">
										You are in <strong>Manual Mode</strong>. Use up/down arrows or drag rows to reorder.
									</span>
									<span class="mode-notice mode-notice-import" style="<?php echo $ordering_mode === 'manual' ? 'display:none;' : ''; ?>">
										You are in <strong>Automatic Mode</strong>. Rankings are controlled by Import Center.
									</span>
								</p>
							</div>
							
							<div class="manual-search-wrap" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> position: relative; width: 340px;">
								<div style="position: relative;">
									<span class="dashicons dashicons-search" style="position: absolute; left: 12px; top: 10px; color: var(--charts-text-dim); opacity: 0.5;"></span>
									<input type="text" id="manual_row_search" class="form-control" style="padding-left: 36px;" placeholder="Search for <?php echo esc_attr($item_type); ?>s to add...">
								</div>
								<div id="search_results_bubble" style="display: none; position: absolute; top: 48px; left: 0; right: 0; background: #fff; border: 1px solid var(--charts-border); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 100; max-height: 400px; overflow-y: auto;">
									<!-- Dynamic Results -->
								</div>
							</div>
						</div>

						<div id="chart_rows_table_wrap" style="border: 1px solid var(--charts-border); border-radius: 12px; overflow: hidden; background: #fff;">
							<table class="charts-admin-table" style="width: 100%; border-collapse: collapse;">
								<thead style="background: var(--charts-bg); border-bottom: 1px solid var(--charts-border);">
									<tr>
										<th class="manual-col" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> width: 44px; text-align: center; padding: 12px; border-right: 1px solid var(--charts-border);">
											<input type="checkbox" disabled style="margin: 0;">
										</th>
										<th style="width: 50px; text-align: center; padding: 12px;">#</th>
										<th style="padding: 12px;">Entity</th>
										<th style="padding: 12px; width: 140px;">ID / Slug</th>
										<th class="manual-col" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> padding: 12px; width: 44px;"></th>
									</tr>
								</thead>
								<tbody id="chart_rows_sortable" data-chart-id="<?php echo $def_id; ?>" data-ordering-mode="<?php echo $ordering_mode; ?>">
									<?php if ( ! empty($entries) ) : foreach ( $entries as $e ) : ?>
										<tr class="chart-row-item" data-id="<?php echo $e->item_id; ?>" data-type="<?php echo $e->item_type; ?>" style="border-bottom: 1px solid var(--charts-border);">
											<td class="manual-col" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> text-align: center; padding: 8px 4px; border-right: 1px solid var(--charts-border); background: #fafafa;">
												<div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
													<input type="checkbox" style="margin-bottom: 6px;">
													<div class="reorder-stack" style="display: flex; flex-direction: column; gap: -4px;">
														<span class="dashicons dashicons-arrow-up-alt2 row-move-up" title="Move Up" style="color: #bbb; cursor: pointer; font-size: 16px; width: 16px; height: 16px; transition: color 0.2s;"></span>
														<span class="dashicons dashicons-arrow-down-alt2 row-move-down" title="Move Down" style="color: #bbb; cursor: pointer; font-size: 16px; width: 16px; height: 16px; transition: color 0.2s;"></span>
													</div>
												</div>
											</td>
											<td style="text-align: center; font-weight: 800; color: var(--charts-accent); padding: 14px;">
												<div class="row-rank-static" style="<?php echo $ordering_mode === 'manual' ? 'display:none;' : ''; ?>"><?php echo $e->rank_position; ?></div>
												<input type="number" class="row-rank-input manual-col" value="<?php echo $e->rank_position; ?>" min="1" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> width: 50px; text-align: center; font-weight: 800; border: 1px solid var(--charts-border); border-radius: 4px; padding: 4px;">
											</td>
											<td style="padding: 14px;">
												<div style="display: flex; align-items: center; gap: 12px;">
													<img src="<?php echo esc_url($e->resolved_image ?: CHARTS_URL . 'public/assets/img/placeholder.png'); ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #f8f8f8;">
													<div>
														<div style="font-weight: 700; color: var(--charts-text);"><?php echo esc_html($e->track_name ?: $e->artist_names); ?></div>
														<?php if ( !empty($e->artist_names) && $e->item_type !== 'artist' ) : ?>
															<div style="font-size: 11px; font-weight: 500; color: var(--charts-text-dim);"><?php echo esc_html($e->artist_names); ?></div>
														<?php endif; ?>
													</div>
												</div>
											</td>
											<td style="padding: 14px; font-family: monospace; font-size: 11px; color: var(--charts-text-dim);">
												<?php echo esc_html($e->item_slug); ?>
											</td>
											<td class="manual-col" style="<?php echo $ordering_mode !== 'manual' ? 'display:none;' : ''; ?> padding: 14px; text-align: right;">
												<div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
													<span class="dashicons dashicons-move row-handle" style="color: #ddd; cursor: grab; padding: 4px; transition: color 0.2s;"></span>
													<span class="dashicons dashicons-dismiss row-delete" title="Remove" style="color: #fca5a5; cursor: pointer; padding: 4px;" data-id="<?php echo $e->item_id; ?>" data-type="<?php echo $e->item_type; ?>"></span>
												</div>
											</td>
										</tr>
									<?php endforeach; else : ?>
											<td colspan="5" class="empty-notice" style="padding: 60px; text-align: center;">
												<div style="opacity: 0.3; margin-bottom: 12px;">
													<span class="dashicons dashicons-list-view" style="font-size: 32px; width: 32px; height: 32px;"></span>
												</div>
												<p style="margin: 0; font-size: 13px; font-weight: 700; color: var(--charts-text-dim);">No active rows found for this chart.</p>
											</td>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endif; ?>

				<!-- Row 5: Visibility Toggles -->
				<div class="toggle-row">
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_public" value="1" <?php checked($is_public, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Published & Publicly Visible</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="is_featured" value="1" <?php checked($is_featured, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Featured Discovery Spot</label>
					</div>
					<div class="toggle-item">
						<label class="switch">
							<input type="checkbox" name="archive_enabled" value="1" <?php checked($archive_enabled, 1); ?>>
							<span class="slider"></span>
						</label>
						<label>Historical Archive Enabled</label>
					</div>
				</div>

			</div>
		</div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		const chart_id = $('#chart_rows_sortable').data('chart-id');
		const item_type = $('#item_type').val();

		// Sync color swatch
		$('#accent_color').on('input', function() {
			$('.color-swatch').css('background', $(this).val());
		});
		
		// Update slug helper live
		$('#slug').on('input', function() {
			const slug = $(this).val() || 'your-slug';
			$('.input-helper').text('URL: /charts/' + slug);
		});

		// Intelligent Field Sync
		function syncRankingLogic() {
			const entity = $('#item_type').val();
			const $logicSelect = $('#chart_type');
			$logicSelect.find('optgroup').each(function() {
				const groupEntity = $(this).data('entity');
				if (groupEntity === entity) { $(this).show().prop('disabled', false); } 
				else { $(this).hide().prop('disabled', true); }
			});
			const $currentOption = $logicSelect.find('option:selected');
			if ($currentOption.parent().is(':disabled')) {
				const $firstVisible = $logicSelect.find('optgroup:not(:disabled) option').first();
				if ($firstVisible.length) { $logicSelect.val($firstVisible.val()); }
			}
		}
		$('#item_type').on('change', syncRankingLogic);
		syncRankingLogic();

		// Manual Management Core
		function initManualOrdering() {
			const isManual = $('#ordering_mode').val() === 'manual';
			
			// Toggle UI visibility
			$('.manual-col').toggle(isManual);
			$('.row-rank-static').toggle(!isManual);
			$('.manual-search-wrap').toggle(isManual);
			$('.mode-notice-manual').toggle(isManual);
			$('.mode-notice-import').toggle(!isManual);

			if (isManual) {
				if (!$("#chart_rows_sortable").data('ui-sortable')) {
					$("#chart_rows_sortable").sortable({
						handle: ".row-handle",
						placeholder: "ui-state-highlight",
						helper: function(e, tr) {
							var $originals = tr.children();
							var $helper = tr.clone();
							$helper.children().each(function(index) {
								$(this).width($originals.eq(index).width());
							});
							return $helper;
						},
						update: function(event, ui) {
							saveManualOrder();
							updateRanksUI();
						}
					});
				} else {
					$("#chart_rows_sortable").sortable("enable");
				}
				updateRanksUI();
			} else {
				if ($("#chart_rows_sortable").data('ui-sortable')) {
					$("#chart_rows_sortable").sortable("disable");
				}
			}
		}

		$('#ordering_mode').on('change', initManualOrdering);
		initManualOrdering();

		function updateRanksUI() {
			const $rows = $('#chart_rows_sortable tr.chart-row-item');
			$rows.each(function(idx) {
				const is_first = idx === 0;
				const is_last = idx === $rows.length - 1;
				
				$(this).find('.row-rank-static').text(idx + 1);
				$(this).find('.row-rank-input').val(idx + 1);
				
				// Handle Arrow Visibility
				$(this).find('.row-move-up').css('visibility', is_first ? 'hidden' : 'visible');
				$(this).find('.row-move-down').css('visibility', is_last ? 'hidden' : 'visible');
			});
		}

		// Manual Management: NUMERIC DIRECT EDIT
		$(document).on('change', '.row-rank-input', function() {
			const $row = $(this).closest('tr');
			const $rows = $('#chart_rows_sortable tr.chart-row-item');
			let newIdx = parseInt($(this).val()) - 1;
			
			if (isNaN(newIdx) || newIdx < 0) newIdx = 0;
			if (newIdx >= $rows.length) newIdx = $rows.length - 1;

			if (newIdx === $row.index()) {
				$(this).val(newIdx + 1);
				return;
			}

			// Move row
			if (newIdx === 0) {
				$row.prependTo('#chart_rows_sortable');
			} else if (newIdx >= $rows.length - 1) {
				$row.appendTo('#chart_rows_sortable');
			} else {
				const $target = $rows.eq(newIdx);
				if (newIdx > $row.index()) {
					$row.insertAfter($target);
				} else {
					$row.insertBefore($target);
				}
			}

			updateRanksUI();
			saveManualOrder();
		});

		// Manual Management: ARROW REORDERING
		$(document).on('click', '.row-move-up', function() {
			const $row = $(this).closest('tr');
			const $prev = $row.prev('.chart-row-item');
			if ($prev.length) {
				$row.insertBefore($prev);
				updateRanksUI();
				saveManualOrder();
			}
		});

		$(document).on('click', '.row-move-down', function() {
			const $row = $(this).closest('tr');
			const $next = $row.next('.chart-row-item');
			if ($next.length) {
				$row.insertAfter($next);
				updateRanksUI();
				saveManualOrder();
			}
		});

		function saveManualOrder() {
			const order = [];
			$('#chart_rows_sortable tr.chart-row-item').each(function() {
				order.push({ id: $(this).data('id'), type: $(this).data('type') });
			});

			$.post(charts_admin.ajax_url, {
				action: 'charts_save_manual_order',
				nonce: charts_admin.nonce,
				chart_id: chart_id,
				order: order
			}, function(response) {
				if (!response.success) alert(response.data.message || 'Failed to save order');
			});
		}

		// Manual Management: SEARCH & ADD
		let searchTimer;
		$('#manual_row_search').on('input', function() {
			clearTimeout(searchTimer);
			const query = $(this).val().trim();
			if (query.length < 2) { $('#search_results_bubble').hide(); return; }

			searchTimer = setTimeout(function() {
				$.post(charts_admin.ajax_url, {
					action: 'charts_search_entities',
					nonce: charts_admin.nonce,
					type: item_type,
					query: query
				}, function(response) {
					if (response.success) {
						let html = '';
						if (response.data.length === 0) {
							html = '<div style="padding: 16px; text-align: center; font-size: 13px; color: #999;">No matches found.</div>';
						} else {
							response.data.forEach(function(item) {
								html += `
									<div class="search-result-row" data-id="${item.id}" data-type="${item_type}" style="padding: 12px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s;">
										<img src="${item.image || '<?php echo CHARTS_URL . "public/assets/img/placeholder.png"; ?>'}" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
										<div style="flex:1;">
											<div style="font-weight: 700; font-size: 13px;">${item.title}</div>
											<div style="font-size: 11px; color: #999;">${item.subtitle || item.slug}</div>
										</div>
										<span class="dashicons dashicons-plus" style="color: var(--charts-accent);"></span>
									</div>
								`;
							});
						}
						$('#search_results_bubble').html(html).show();
					}
				});
			}, 300);
		});

		$(document).on('click', '.search-result-row', function() {
			const item_id = $(this).data('id');
			const type = $(this).data('type');
			
			$.post(charts_admin.ajax_url, {
				action: 'charts_manage_manual_row',
				nonce: charts_admin.nonce,
				chart_id: chart_id,
				item_id: item_id,
				type: type,
				mode: 'add'
			}, function(response) {
				if (response.success) {
					location.reload(); // Refresh to show new row and updated rank
				} else {
					alert(response.data.message || 'Failed to add row');
				}
			});
		});

		// Manual Management: DELETE
		$(document).on('click', '.row-delete', function() {
			if (!confirm('Remove this item from the chart?')) return;
			const item_id = $(this).data('id');
			const type = $(this).data('type');
			const $row = $(this).closest('tr');

			$.post(charts_admin.ajax_url, {
				action: 'charts_manage_manual_row',
				nonce: charts_admin.nonce,
				chart_id: chart_id,
				item_id: item_id,
				type: type,
				mode: 'delete'
			}, function(response) {
				if (response.success) {
					$row.fadeOut(300, function() { 
						$(this).remove(); 
						updateRanksUI();
					});
				} else {
					alert(response.data.message || 'Failed to remove row');
				}
			});
		});

		// Hide search bubble on outside click
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.manual-search-wrap').length) {
				$('#search_results_bubble').hide();
			}
		});
	});
	</script>
	<style>
		.search-result-row:hover { background: #f8f8fb; }
		.ui-state-highlight { height: 60px; background: rgba(99, 102, 241, 0.05); border: 1px dashed var(--charts-accent); }
		.charts-admin-table th { font-weight: 700; font-size: 12px; color: var(--charts-text-dim); text-transform: uppercase; letter-spacing: 0.03em; }
		.row-move-up:hover, .row-move-down:hover { color: var(--charts-accent) !important; }
		.row-handle:hover { color: var(--charts-text) !important; }
		.row-delete:hover { color: #ef4444 !important; }
	</style>
</div>
