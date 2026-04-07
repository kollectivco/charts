<?php
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

echo "kcharts_settings:\n";
print_r(get_option('kcharts_settings'));
echo "\n\nkcharts_settings_v2:\n";
print_r(get_option('kcharts_settings_v2'));
echo "\n\nSlider Slides:\n";
print_r(\Charts\Core\HomepageSlider::get_slides_data());
echo "\n\nSlider Config:\n";
print_r(\Charts\Core\HomepageSlider::get_premium_settings());
