<?php
/**
 * Standalone Charts Header
 */
$custom_header_enabled = get_option( 'charts_custom_header' );
if ( ! $custom_header_enabled ) {
	get_header(); // Fallback if setting is off
	return;
}

$logo_id      = get_option( 'charts_logo_id' );
$logo_alt     = get_option( 'charts_logo_alt' );
$wordmark     = get_option( 'charts_wordmark', 'Kontentainment' );
$show_logo    = get_option( 'charts_show_logo', 1 );
$show_nav     = get_option( 'charts_show_nav', 1 );
$show_country = get_option( 'charts_show_country_selector', 1 );
$show_search  = get_option( 'charts_show_search', 1 );
$menu_id      = get_option( 'charts_header_menu_id' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'charts-standalone' ); ?>>
<?php wp_body_open(); ?>

<header class="charts-product-header">
	<div class="charts-container">
		<div class="header-inner">
			
			<!-- Logo / Wordmark -->
			<?php if ( $show_logo ) : ?>
			<div class="charts-branding">
				<a href="<?php echo esc_url( home_url( '/charts' ) ); ?>" class="branding-link">
					<?php if ( $logo_id ) : ?>
						<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'alt' => $logo_alt, 'class' => 'charts-logo' ) ); ?>
					<?php else : ?>
						<span class="charts-wordmark"><?php echo esc_html( $wordmark ?: 'Kontentainment' ); ?></span>
					<?php endif; ?>
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
			<?php endif; ?>

			<!-- Header Actions -->
			<div class="charts-header-actions">
				<?php if ( $show_country ) : ?>
					<div class="country-switcher">
						<span class="current-country">Global</span>
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
					</div>
				<?php endif; ?>

				<?php if ( $show_search ) : ?>
					<button class="search-trigger" aria-label="Search">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					</button>
				<?php endif; ?>
			</div>

		</div>
	</div>
</header>

<main class="charts-product-main">
