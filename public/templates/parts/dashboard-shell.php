<?php
/**
 * Kontentainment Charts — Bento Dashboard Shell
 */

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_redirect( wp_login_url( home_url( '/charts-dashboard' ) ) );
	exit;
}

$current_module = get_query_var( 'charts_module', 'overview' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo CHARTS_URL . 'public/assets/css/dashboard.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
	<link rel="stylesheet" href="<?php echo CHARTS_URL . 'admin/assets/css/admin.css'; ?>?v=<?php echo CHARTS_VERSION; ?>">
	<link rel="stylesheet" href="<?php echo includes_url( 'css/dashicons.min.css' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'kc-db-root' ); ?>>
<?php wp_body_open(); ?>

<!-- Sidebar -->
<aside class="kc-db-sidebar">
	<a href="<?php echo home_url('/charts'); ?>" class="kc-db-logo">K<span>Charts</span></a>

	<nav class="kc-db-nav">
		<a href="<?php echo home_url('/charts-dashboard/overview'); ?>" class="kc-db-nav-item <?php echo $current_module === 'overview' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
			Dashboard
		</a>
		<a href="<?php echo home_url('/charts-dashboard/charts'); ?>" class="kc-db-nav-item <?php echo $current_module === 'charts' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
			Charts
		</a>
		<a href="<?php echo home_url('/charts-dashboard/sources'); ?>" class="kc-db-nav-item <?php echo $current_module === 'sources' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
			Sources
		</a>
		<a href="<?php echo home_url('/charts-dashboard/import'); ?>" class="kc-db-nav-item <?php echo $current_module === 'import' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
			Import Center
		</a>
		<a href="<?php echo home_url('/charts-dashboard/matching'); ?>" class="kc-db-nav-item <?php echo $current_module === 'matching' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
			Matching Center
		</a>
		<a href="<?php echo home_url('/charts-dashboard/entities'); ?>" class="kc-db-nav-item <?php echo in_array($current_module, ['entities','artists','tracks','clips']) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
			Metadata Center
		</a>
		<a href="<?php echo home_url('/charts-dashboard/insights'); ?>" class="kc-db-nav-item <?php echo in_array($current_module, ['insights','intelligence']) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
			Insights
		</a>
		<a href="<?php echo home_url('/charts-dashboard/settings'); ?>" class="kc-db-nav-item <?php echo $current_module === 'settings' ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
			Settings
		</a>
		<a href="<?php echo wp_logout_url( home_url( '/charts' ) ); ?>" class="kc-db-nav-item">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
			Logout
		</a>
	</nav>

	<div class="theme-switch">
		<button class="theme-btn" id="kc-theme-toggle">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
			<span>Switch to Dark Mode</span>
		</button>
	</div>
</aside>

<main class="kc-db-main">
