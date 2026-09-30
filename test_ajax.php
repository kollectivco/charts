<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');
$_POST['type'] = 'artists';
$_POST['_wpnonce'] = wp_create_nonce('charts_admin_action');
$_POST['charts_action'] = 'fake'; // just in case
if ( ! current_user_can( 'manage_options' ) ) {
    $user = get_user_by('login', 'admin');
    if (!$user) { $users = get_users(); $user = $users[0]; }
    wp_set_current_user($user->ID);
}
require_once('inc/Admin/Bootstrap.php');
try {
    \Charts\Admin\Bootstrap::handle_resolve_potential_duplicates();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
