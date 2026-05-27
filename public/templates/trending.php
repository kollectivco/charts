<?php
/**
 * Kontentainment Lists — Trending Hub
 */
\Charts\Core\StandaloneLayout::get_header();

$editorial = new \Charts\Services\EditorialService();
$sections  = $editorial->get_trending_sections();
?>

<div class="kc-root">
	<div class="kc-container" style="padding: 40px 0 100px;">
		<nav style="padding: 20px 0 30px; font-size: 11px; font-weight: 850; letter-spacing: 0.1em; color: var(--k-text-muted);">
			<a href="/lists" style="color: inherit; text-decoration: none;"><?php echo charts_tr('HOME'); ?></a> &nbsp; / &nbsp;
			<span style="color: white;"><?php echo charts_tr('TRENDING'); ?></span>
		</nav>
 
		<header class="kc-page-intro" style="margin-block-end:40px;">
			<div class="kc-label-bar"><?php echo charts_tr('LIVE EDITORIAL INTELLIGENCE'); ?></div>
			<h1 class="kc-page-title"><?php echo charts_tr('Trending Music Hub'); ?></h1>
			<p class="kc-page-desc"><?php echo charts_tr('Follow the tracks and artists moving fastest across weekly music lists, from rising breakouts to editor-backed picks.'); ?></p>
		</header>
 
		<?php
		$blocks = array(
			'Trending Now'   => $sections['trending'] ?? array(),
			'Rising Fast'    => $sections['rising'] ?? array(),
			'New This Week'  => $sections['new_entries'] ?? array(),
		);
		foreach ( $blocks as $title => $rows ) :
		?>
			<section style="margin-block-end:34px;">
				<header class="kc-section-header">
					<h2 class="kc-header-title"><?php echo esc_html( charts_tr($title) ); ?></h2>
				</header>
				<div class="kc-bento-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
					<?php foreach ( $rows as $row ) : ?>
						<a href="<?php echo esc_url( home_url( '/lists/' . ( $row->item_type === 'video' ? 'video' : 'track' ) . '/' . ( $row->item_slug ?: sanitize_title( $row->track_name ) ) ) ); ?>" class="kc-chart-card charts-track-click" data-page-type="trending" data-object-type="<?php echo esc_attr( $row->item_type ); ?>" data-object-id="<?php echo (int) $row->item_id; ?>" data-slug="<?php echo esc_attr( $row->item_slug ?: sanitize_title( $row->track_name ) ); ?>" style="text-decoration:none; color:inherit;">
							<div class="kc-card-hero" style="height:140px;">
								<img src="<?php echo esc_url( $row->cover_image ?: CHARTS_URL . 'public/assets/img/placeholder.png' ); ?>" class="kc-card-hero-img" alt="">
								<div style="position:relative; z-index:10;">
									<span class="kc-card-meta"><?php echo esc_html( charts_tr( strtoupper( $row->trend_type ?: $row->movement_direction ?: 'TRENDING' ) ) ); ?></span>
									<h2 class="kc-card-title"><?php echo esc_html( $row->track_name ); ?></h2>
								</div>
							</div>
							<div class="kc-card-footer">
								<span class="kc-card-date"><?php echo esc_html( $row->artist_names ); ?></span>
								<span class="kc-card-cta" style="color:var(--k-accent);"><?php echo charts_tr('Open &rarr;'); ?></span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
 
		<?php if ( ! empty( $sections['editor_picks'] ) ) : ?>
			<section style="margin-top:50px;">
				<header class="kc-section-header">
					<h2 class="kc-header-title"><?php echo charts_tr('Editor Picks'); ?></h2>
				</header>
				<div class="kc-bento-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
					<?php foreach ( $sections['editor_picks'] as $pick ) : ?>
						<a href="<?php echo esc_url( home_url( '/lists/' . $pick->slug . '/' ) ); ?>" class="kc-chart-card" style="text-decoration:none; color:inherit;">
							<div class="kc-card-hero" style="height:140px;">
								<div style="position:relative; z-index:10;">
									<span class="kc-card-meta"><?php echo charts_tr('EDITOR PICK'); ?></span>
									<h2 class="kc-card-title"><?php echo esc_html( $pick->title ); ?></h2>
								</div>
							</div>
							<div class="kc-card-footer">
								<span class="kc-card-date"><?php echo esc_html( charts_tr( strtoupper( $pick->frequency ) ) ); ?> <?php echo charts_tr('LIST'); ?></span>
								<span class="kc-card-cta" style="color:var(--k-accent);"><?php echo charts_tr('Open &rarr;'); ?></span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
