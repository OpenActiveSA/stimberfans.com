<?php
/**
 * Debug Variation Data
 * 
 * This script checks if variation price data is being included correctly.
 * Access via: yourdomain.com/wp-content/plugins/oa-timberfans-product-mods/debug-variation-data.php?product_id=XXX
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if (!$product_id) {
    echo '<h1>Debug Variation Data</h1>';
    echo '<p>Please provide a product ID in the URL: ?product_id=XXX</p>';
    
    // Show available variable products
    $products = wc_get_products(array(
        'type' => 'variable',
        'limit' => 20,
        'status' => 'publish'
    ));
    
    if ($products) {
        echo '<h2>Available Variable Products:</h2>';
        echo '<ul>';
        foreach ($products as $product) {
            echo '<li>';
            echo '<a href="?product_id=' . $product->get_id() . '">';
            echo $product->get_name() . ' (ID: ' . $product->get_id() . ')';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
    exit;
}

// Get the product
$product = wc_get_product($product_id);

if (!$product || !$product->is_type('variable')) {
    wp_die('Invalid product ID or not a variable product.');
}

echo '<h1>Variation Data for: ' . $product->get_name() . '</h1>';
echo '<p><strong>Product ID:</strong> ' . $product_id . '</p>';

// Clear product transients
wc_delete_product_transients($product_id);
echo '<p style="color: green;">✓ Product transients cleared</p>';

// Get available variations
$variations = $product->get_available_variations();

echo '<h2>Available Variations (' . count($variations) . ' total)</h2>';

if (empty($variations)) {
    echo '<p style="color: red;">No variations found!</p>';
} else {
    foreach ($variations as $variation_data) {
        $variation_id = $variation_data['variation_id'];
        $variation_obj = wc_get_product($variation_id);
        
        echo '<div style="border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9;">';
        echo '<h3>Variation ID: ' . $variation_id . '</h3>';
        
        // Attributes
        echo '<p><strong>Attributes:</strong> ';
        foreach ($variation_data['attributes'] as $attr => $value) {
            echo $attr . ' = ' . $value . '; ';
        }
        echo '</p>';
        
        // Stock status
        echo '<p><strong>In Stock:</strong> ' . ($variation_obj->is_in_stock() ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Stock Quantity:</strong> ' . ($variation_obj->get_stock_quantity() ?? 'N/A') . '</p>';
        
        // Price from object
        echo '<p><strong>Price (from object):</strong> R' . $variation_obj->get_price() . '</p>';
        
        // Price from variation data
        echo '<p><strong>display_price (in JS data):</strong> ';
        if (isset($variation_data['display_price'])) {
            echo '<span style="color: green; font-weight: bold;">R' . $variation_data['display_price'] . '</span>';
        } else {
            echo '<span style="color: red; font-weight: bold;">NOT SET (This is the problem!)</span>';
        }
        echo '</p>';
        
        echo '<p><strong>is_purchasable:</strong> ' . ($variation_data['is_purchasable'] ? 'true' : 'false') . '</p>';
        echo '<p><strong>is_in_stock (in JS data):</strong> ' . ($variation_data['is_in_stock'] ? 'true' : 'false') . '</p>';
        
        echo '</div>';
    }
}

echo '<hr>';
echo '<p><a href="' . get_permalink($product_id) . '" target="_blank">View Product Page</a></p>';
echo '<p><strong>IMPORTANT:</strong> Please delete this file after debugging.</p>';

