<?php
define('WP_USE_THEMES', false);
require_once(dirname(__FILE__) . '/../../../wp-load.php');
$_POST['type'] = 'artists';
$_POST['_wpnonce'] = wp_create_nonce('charts_admin_action');
$_POST['charts_action'] = 'fake';
require_once('inc/Admin/Bootstrap.php');
try {
    \Charts\Admin\Bootstrap::handle_resolve_potential_duplicates();
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage();
}
