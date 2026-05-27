<?php
/**
 * Legacy bootstrap shim for backward compatibility.
 *
 * This file intentionally has no plugin header so WordPress only registers
 * the new `kontentainment-lists.php` entry file after the rebrand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/kontentainment-lists.php';
