<?php
require_once('../../../wp-load.php');
global $wpdb;
$cols = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}charts_entries");
print_r($cols);
