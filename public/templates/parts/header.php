<?php
/**
 * Standalone Charts Header - Cinematic Dark Dashboard
 */
// Force standalone for these templates
$logo_id      = get_option( 'charts_logo_id' );
$logo_alt     = get_option( 'charts_logo_alt' );
$wordmark     = get_option( 'charts_wordmark', 'KCharts' ); // Use KCharts for cinematic feel per ref
$show_logo    = get_option( 'charts_show_logo', 1 );
$show_nav     = get_option( 'charts_show_nav', 1 );
$show_search  = get_option( 'charts_show_search', 1 );
$menu_id      = get_option( 'charts_header_menu_id' );

// Calculate dynamic "Week of" date
$week_date = date('F j, Y'); 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'charts-standalone kc-root' ); ?>>
<?php wp_body_open(); ?>

<!-- 1. TOP MICRO BAR -->
<div class="kc-micro-bar">
	<div class="kc-container">
		<div class="kc-micro-inner">
			<div class="micro-left">
				KONTENTAINMENT CHARTS &middot; WEEK OF <?php echo strtoupper($week_date); ?>
			</div>
			<div class="micro-right">
				Powered by streaming data from Spotify &middot; YouTube Music &middot; TikTok
			</div>
		</div>
	</div>
</div>

<!-- 2. MAIN HEADER -->
<header class="charts-product-header">
	<div class="kc-container">
		<div class="header-inner">
			
			<div style="display: flex; align-items: center; gap: 60px;">
				<!-- Branding -->
				<div class="charts-branding">
					<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="charts-wordmark">
						<?php echo esc_html( $wordmark ?: 'KCharts' ); ?>
					</a>
				</div>

				<!-- Navigation -->
				<?php if ( $show_nav && $menu_id ) : ?>
				<nav class="charts-nav">
					<?php
					wp_nav_menu( array(
						'menu'           => $menu_id,
						'container'      => false,
						'menu_class'     => 'charts-menu',
						'fallback_cb'    => false,
						'depth'          => 1,
					) );
					?>
				</nav>
				<?php else: ?>
				<!-- Hardcoded Fallback per Reference -->
				<nav class="charts-nav">
					<ul class="charts-menu">
						<li class="current-menu-item"><a href="/charts">Home</a></li>
						<li><a href="/charts">Charts</a></li>
						<li><a href="/charts">Tracks</a></li>
						<li><a href="/charts">Artists</a></li>
						<li><a href="/charts">Albums</a></li>
					</ul>
				</nav>
				<?php endif; ?>
			</div>

			<!-- Actions -->
			<div class="charts-header-actions">
				<?php if ( $show_search ) : ?>
					<button class="search-trigger" style="background: none; border: none; color: white; opacity: 0.6; cursor: pointer;">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</button>
				<?php endif; ?>

				<a href="<?php echo admin_url('admin.php?page=charts-dashboard'); ?>" class="kc-btn-dashboard">
					DASHBOARD
				</a>
			</div>

		</div>
	</div>
</header>

<main class="charts-product-main">
