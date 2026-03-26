<?php
/**
 * Kontentainment Charts — Artist Archive
 */
\Charts\Core\StandaloneLayout::get_header();
global $wpdb;

$artists_table = $wpdb->prefix . 'charts_artists';
$entries_table = $wpdb->prefix . 'charts_entries';
$ta_table      = $wpdb->prefix . 'charts_track_artists';

// Fetch all artists with a count of their chart appearances
$artists = $wpdb->get_results( "
	SELECT a.*, 
	       (SELECT COUNT(*) FROM $entries_table e 
	        WHERE (e.item_id = a.id AND e.item_type = 'artist') 
	           OR (e.item_type = 'track' AND e.item_id IN (SELECT track_id FROM $ta_table WHERE artist_id = a.id))
	       ) as appearance_count
	FROM $artists_table a
	GROUP BY a.id
	HAVING appearance_count > 0
	ORDER BY appearance_count DESC, a.display_name ASC
" );
?>

<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/public.css'; ?>">

<div class="kc-root <?php echo is_admin_bar_showing() ? 'has-admin-bar' : ''; ?>">
	<header class="kc-hero">
		<div class="kc-container">
			<div class="kc-brand-name animate-fade-in-up">Kontentainment</div>
			<h1 class="kc-hero-title animate-fade-in-up" style="animation-delay: 0.1s;">Discovery <em>Artists</em></h1>
			<p class="animate-fade-in-up" style="color: var(--k-text-dim); max-width: 600px; animation-delay: 0.2s; line-height: 1.6;">
				Browse the most influential voices currently shaping the regional music charts.
			</p>
		</div>
	</header>

	<main class="kc-container" style="padding-bottom: 120px;">
		<div style="margin-top: -40px; margin-bottom: 40px;" class="animate-fade-in-up">
			<a href="<?php echo home_url('/charts/'); ?>" class="kc-view-btn" style="text-decoration: none;">&larr; Back to Charts</a>
		</div>

		<?php if ( empty( $artists ) ) : ?>
			<div class="kc-empty">
				<h3 style="font-size: 1.5rem; font-weight: 900; color: #fff; margin-bottom: 16px;">No artists found</h3>
				<p>Import some chart data to populate the artist discovery section.</p>
			</div>
		<?php else : ?>
			<div class="kc-artist-grid animate-fade-in-up" style="animation-delay: 0.3s;">
				<?php foreach ( $artists as $artist ) : 
					$slug = $artist->slug;
					$url  = home_url( '/charts/artist/' . $slug . '/' );
					$img  = !empty($artist->image) ? $artist->image : 'https://www.gravatar.com/avatar/' . md5($artist->display_name) . '?d=mp&s=300';
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="kc-artist-card">
					<img src="<?php echo esc_url( $img ); ?>" class="kc-artist-thumb" alt="<?php echo esc_attr( $artist->display_name ); ?>">
					<h3 class="kc-artist-name"><?php echo esc_html( $artist->display_name ); ?></h3>
					<span class="kc-artist-count"><?php echo number_format( $artist->appearance_count ); ?> Appearances</span>
				</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</main>
</div>

<?php \Charts\Core\StandaloneLayout::get_footer(); ?>
