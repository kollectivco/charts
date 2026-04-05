<?php
/**
 * Kontentainment Charts — Premium Light Mode Header
 */
use Charts\Core\Settings;

$theme_mode   = Settings::get( 'theme_mode' );
$logo_id      = Settings::get_active_logo_id();
$logo_alt     = Settings::get( 'logo_alt' );
$wordmark     = Settings::get( 'wordmark' ); 
$show_logo    = Settings::get( 'show_logo' );
$show_nav     = Settings::get( 'show_nav' );
$show_search  = Settings::get( 'show_search' );
$menu_id      = Settings::get( 'header_menu_id' );
$week_date    = date('F j, Y'); 
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr( $theme_mode ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		:root {
			--k-primary: <?php echo esc_attr( Settings::get('color_primary') ); ?>;
			--k-bg-light: <?php echo esc_attr( Settings::get('color_bg_light') ); ?>;
			--k-bg-light: <?php echo esc_attr( Settings::get('color_bg_light') ); ?>;
			--k-bg-dark: <?php echo esc_attr( Settings::get('color_bg_dark') ); ?>;
		}
		
		html[data-theme="light"] {
			background-color: var(--k-bg-light);
		}
		html[data-theme="dark"] {
			background-color: var(--k-bg-dark);
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
