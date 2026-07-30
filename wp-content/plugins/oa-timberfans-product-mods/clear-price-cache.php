<?php
/**
 * Clear WooCommerce Price Caches
 * 
 * Run this file once to clear all WooCommerce variable product price caches.
 * Access via: yourdomain.com/wp-content/plugins/oa-timberfans-product-mods/clear-price-cache.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

global $wpdb;

// Clear WooCommerce variable price transients
$count1 = $wpdb->query(
    "DELETE FROM {$wpdb->prefix}options 
    WHERE option_name LIKE '_transient_wc_var_prices_%' 
    OR option_name LIKE '_transient_timeout_wc_var_prices_%'"
);

// Clear WooCommerce product transients
$count2 = $wpdb->query(
    "DELETE FROM {$wpdb->prefix}options 
    WHERE option_name LIKE '_transient_wc_products_%' 
    OR option_name LIKE '_transient_timeout_wc_products_%'"
);

// Clear all product caches
$products = wc_get_products(array(
    'limit' => -1,
    'type' => 'variable',
    'return' => 'ids'
));

$count3 = 0;
foreach ($products as $product_id) {
    wc_delete_product_transients($product_id);
    $count3++;
}

echo '<h1>Cache Cleared Successfully!</h1>';
echo '<p>Cleared ' . $count1 . ' price transient entries.</p>';
echo '<p>Cleared ' . $count2 . ' product transient entries.</p>';
echo '<p>Cleared caches for ' . $count3 . ' variable products.</p>';
echo '<p><a href="' . home_url('/shop/') . '">View Shop Page</a></p>';
echo '<p><strong>IMPORTANT:</strong> Please delete this file after use for security.</p>';

