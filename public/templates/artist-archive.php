<?php
/**
 * Kontentainment Charts — Artist Archive (Light Mode)
 */

global $wpdb;

// Fetch all artists with a count of their chart appearances
$artists = $wpdb->get_results( "
	SELECT a.*, 
	       (SELECT COUNT(*) FROM {$wpdb->prefix}charts_entries e 
	        WHERE (e.item_id = a.id AND e.item_type = 'artist') 
	           OR (e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM {$wpdb->prefix}charts_track_artists WHERE artist_id = a.id))
	       ) as appearance_count
	FROM {$wpdb->prefix}charts_artists a
	GROUP BY a.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, a.display_name ASC
" );

\Charts\Core\StandaloneLayout::get_header();
?>

<div class="kc-root">
	
	<div class="kc-container">
		
		<div class="kc-breadcrumb">
			<a href="<?php echo home_url('/charts'); ?>">Home</a> <span>/</span> Artists
		</div>

		<header class="kc-page-hero" style="padding-bottom: 20px;">
			<div class="kc-eyebrow">Discovery</div>
			<h1 class="kc-page-title">Top Artists</h1>
			<p style="font-size: 13px; color: var(--k-text-dim); max-width: 600px; font-weight: 500;">
				Browse the most influential voices currently shaping the regional music charts.
			</p>
		</header>

		<main class="kc-section" style="padding-top: 40px; padding-bottom: 120px;">
			
			<?php if ( empty( $artists ) ) : ?>
				<div style="padding: 80px; text-align: center; border: 2px dashed var(--k-border); border-radius: 24px;">
					<p style="font-weight: 800; color: var(--k-text-muted);">No artists found with active rankings.</p>
				</div>
			<?php else : ?>
				<div class="kc-grid kc-grid-4">
					<?php foreach ( $artists as $artist ) : 
						$url  = home_url( '/charts/artist/' . $artist->slug . '/' );
						$img  = !empty($artist->image) ? $artist->image : CHARTS_URL . 'public/assets/img/placeholder.png';
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="kc-card" style="text-decoration: none; text-align: center; padding: 32px 24px;">
						<img src="<?php echo esc_url( $img ); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 20px; box-shadow: var(--k-shadow-sm);">
						<h3 style="font-size: 18px; font-weight: 900; color: var(--k-text); margin: 0;"><?php echo esc_html( $artist->display_name ); ?></h3>
						<span style="display: block; font-size: 11px; font-weight: 700; color: var(--k-accent); margin-top: 8px; text-transform: uppercase;"><?php echo number_format( $artist->appearance_count ); ?> Appearances</span>
					</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</main>

	</div>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
