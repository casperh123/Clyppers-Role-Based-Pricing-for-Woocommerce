<?php

const ABSPATH = __DIR__ . '/../';

if (!function_exists('wc_get_price_decimals')) {
    function wc_get_price_decimals(): int {
        return 2; // WooCommerce's own default
    }
}