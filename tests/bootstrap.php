<?php

const ABSPATH = __DIR__ . '/../';

if (!function_exists('wc_get_price_decimals')) {
    function wc_get_price_decimals(): int {
        return 2; // WooCommerce's own default
    }
}
// Simplified stand-in for WordPress's sanitize_text_field(); the integration suite exercises the real one.
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags($str)));
    }
}
