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

<!-- Top Micro Bar Retired for cleaner UI pass -->

<!-- 2. MAIN HEADER -->
<header class="charts-product-header">
	<div class="kc-container">
		<div class="header-inner">
			
			<div style="display: flex; align-items: center; gap: 60px;">
				<!-- Branding -->
				<?php if ( $show_logo ) : ?>
				<div class="charts-branding">
					<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="charts-brand-link">
						<?php 
						if ( $logo_id ) {
							$logo_html = wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'charts-logo-img', 'alt' => $logo_alt ?: $wordmark ) );
							if ( $logo_html ) {
								echo $logo_html;
							} else {
								echo '<span class="charts-wordmark">' . esc_html( $wordmark ?: 'KCharts' ) . '</span>';
							}
						} else {
							echo '<span class="charts-wordmark">' . esc_html( $wordmark ?: 'KCharts' ) . '</span>';
						}
						?>
					</a>
				</div>
				<?php endif; ?>

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

			<!-- Actions Retired (Search/Dashboard) per UI cleanup request -->

		</div>
	</div>
</header>

<main class="charts-product-main">
