<?php
require_once 'wp-load.php';
$results = \Charts\Core\EntityManager::search_entities('track', 'معايا');
echo json_encode($results);
