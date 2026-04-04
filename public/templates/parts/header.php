<?php
/**
 * Kontentainment Charts — Premium Light Mode Header
 */
$theme_mode   = get_option( 'charts_theme_mode', 'light' );
$logo_id_main = ( $theme_mode === 'dark' ) ? get_option( 'charts_logo_id_dark' ) : get_option( 'charts_logo_id_light' );
$logo_id_fall = ( $theme_mode === 'dark' ) ? get_option( 'charts_logo_id_light' ) : get_option( 'charts_logo_id_dark' );
$logo_id      = $logo_id_main ?: $logo_id_fall;

$logo_alt     = get_option( 'charts_logo_alt' );
$wordmark     = get_option( 'charts_wordmark', 'KCharts' ); 
$show_logo    = get_option( 'charts_show_logo', 1 );
$show_nav     = get_option( 'charts_show_nav', 1 );
$show_search  = get_option( 'charts_show_search', 1 );
$menu_id      = get_option( 'charts_header_menu_id' );
$week_date    = date('F j, Y'); 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr( $theme_mode ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	<style>
		:root {
			--k-primary: <?php echo esc_attr( get_option('charts_color_primary', '#3b82f6') ); ?>;
			--k-bg-light: <?php echo esc_attr( get_option('charts_color_bg_light', '#ffffff') ); ?>;
			--k-bg-dark: <?php echo esc_attr( get_option('charts_color_bg_dark', '#0f172a') ); ?>;
			--k-font-heading: <?php echo esc_attr( get_option('charts_font_heading', 'Inter, sans-serif') ); ?>;
			--k-font-body: <?php echo esc_attr( get_option('charts_font_body', 'Inter, sans-serif') ); ?>;
		}
		
		html[data-theme="light"] {
			background-color: var(--k-bg-light);
		}
		html[data-theme="dark"] {
			background-color: var(--k-bg-dark);
		}
		.charts-product-header, .kc-root {
			font-family: var(--k-font-body);
		}
		h1, h2, h3, h4, h5, h6, .charts-wordmark {
			font-family: var(--k-font-heading);
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'kc-root' ); ?>>
<?php wp_body_open(); ?>

<header class="charts-product-header">
	<div class="kc-container">
		<div class="header-inner">
			
			<div style="display: flex; align-items: center; gap: 40px;">
				<!-- Branding -->
				<div class="charts-branding">
					<div class="kc-mobile-trigger">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
					</div>
					<?php if ( $show_logo ) : ?>
					<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="charts-branding-link">
						<?php if ( $logo_id ) : ?>
							<?php echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'charts-logo', 'alt' => $logo_alt ?: $wordmark ) ); ?>
						<?php else : ?>
							<span class="charts-wordmark"><?php echo esc_html( $wordmark ); ?></span>
						<?php endif; ?>
					</a>
					<?php endif; ?>
				</div>

				<!-- Navigation -->
				<?php if ( $show_nav ) : ?>
				<nav class="charts-nav">
					<?php if ( $menu_id ) : ?>
						<?php
						wp_nav_menu( array(
							'menu'           => $menu_id,
							'container'      => false,
							'menu_class'     => 'charts-menu',
							'fallback_cb'    => false,
							'depth'          => 1,
						) );
						?>
					<?php else : ?>
						<ul class="charts-menu">
							<li><a href="<?php echo esc_url( home_url( '/charts' ) ); ?>">Home</a></li>
							<li><a href="<?php echo esc_url( home_url( '/charts/tracks' ) ); ?>">Tracks</a></li>
							<li><a href="<?php echo esc_url( home_url( '/charts/artists' ) ); ?>">Artists</a></li>
						</ul>
					<?php endif; ?>
				</nav>
				<?php endif; ?>
			</div>

			<div class="charts-header-actions">
				<?php if ( $show_search ) : ?>
				<div class="kc-icon-search">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</div>
				<?php endif; ?>
				<?php if (current_user_can('manage_options')): ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charts' ) ); ?>" class="kc-btn-admin">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
						Admin
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>
</header>

<main class="charts-product-main">
