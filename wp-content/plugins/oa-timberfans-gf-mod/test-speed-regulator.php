<?php
/**
 * Test file for Speed Regulator filtering
 * 
 * This file can be used to test the speed regulator functionality
 * Place this in your WordPress root directory and access it via browser
 */

// Load WordPress
require_once('wp-load.php');

// Check if WooCommerce is active
if (!class_exists('WooCommerce')) {
    die('WooCommerce is not active');
}

// Check if Gravity Forms is active
if (!class_exists('GFCommon')) {
    die('Gravity Forms is not active');
}

echo '<h1>Speed Regulator Test</h1>';

// Test 1: Check if speed regulator taxonomy exists
$speed_terms = get_terms(array(
    'taxonomy' => 'pa_speed-regulator',
    'hide_empty' => false,
));

echo '<h2>Test 1: Speed Regulator Taxonomy</h2>';
if (is_wp_error($speed_terms)) {
    echo '<p style="color: red;">Error: ' . $speed_terms->get_error_message() . '</p>';
} else {
    echo '<p>Found ' . count($speed_terms) . ' speed regulator terms:</p>';
    echo '<ul>';
    foreach ($speed_terms as $term) {
        echo '<li>' . $term->name . ' (slug: ' . $term->slug . ')</li>';
    }
    echo '</ul>';
}

// Test 2: Check products with speed regulator attributes
echo '<h2>Test 2: Products with Speed Regulator</h2>';
$products = wc_get_products(array(
    'status' => 'publish',
    'limit' => 10,
));

$products_with_speed_regulator = 0;
foreach ($products as $product) {
    $attributes = $product->get_attributes();
    $has_speed_regulator = false;
    
    foreach ($attributes as $attr) {
        $attr_name = strtolower($attr->get_name());
        if (strpos($attr_name, 'speed') !== false && strpos($attr_name, 'regulator') !== false) {
            $has_speed_regulator = true;
            break;
        }
    }
    
    if ($has_speed_regulator) {
        $products_with_speed_regulator++;
        echo '<p><strong>' . $product->get_name() . '</strong> (ID: ' . $product->get_id() . ') has speed regulator attribute</p>';
        
        // Check if it's used for variations
        foreach ($attributes as $attr) {
            $attr_name = strtolower($attr->get_name());
            if (strpos($attr_name, 'speed') !== false && strpos($attr_name, 'regulator') !== false) {
                $is_used_for_variations = $attr->get_variation();
                echo '<p>Speed regulator attribute: ' . $attr_name . ' (Used for variations: ' . ($is_used_for_variations ? 'Yes' : 'No') . ')</p>';
            }
        }
    }
}

if ($products_with_speed_regulator === 0) {
    echo '<p style="color: orange;">No products found with speed regulator attributes</p>';
}

// Test 3: Check products with speed regulator terms
echo '<h2>Test 3: Products with Speed Regulator Terms</h2>';
$products_with_terms = 0;
foreach ($products as $product) {
    $speed_terms = wc_get_product_terms($product->get_id(), 'pa_speed-regulator', array(
        'fields' => 'slugs',
    ));
    
    if (!is_wp_error($speed_terms) && !empty($speed_terms)) {
        $products_with_terms++;
        echo '<p><strong>' . $product->get_name() . '</strong> (ID: ' . $product->get_id() . ') has speed regulator terms: ' . implode(', ', $speed_terms) . '</p>';
    }
}

if ($products_with_terms === 0) {
    echo '<p style="color: orange;">No products found with speed regulator terms</p>';
}

// Test 4: Simulate AJAX call
echo '<h2>Test 4: Simulate AJAX Call</h2>';
if (!empty($products)) {
    $test_product = $products[0];
    $product_id = $test_product->get_id();
    
    echo '<p>Testing with product: ' . $test_product->get_name() . ' (ID: ' . $product_id . ')</p>';
    
    // Simulate the AJAX handler logic
    $speed_slugs = wc_get_product_terms($product_id, 'pa_speed-regulator', array(
        'fields' => 'slugs',
    ));
    
    if (!is_wp_error($speed_slugs) && !empty($speed_slugs)) {
        echo '<p style="color: green;">Success: Found speed regulator terms for this product: ' . implode(', ', $speed_slugs) . '</p>';
    } else {
        echo '<p style="color: red;">No speed regulator terms found for this product</p>';
    }
}

echo '<h2>Recommendations</h2>';
echo '<ol>';
echo '<li>Make sure you have created the "Speed Regulator" attribute in WooCommerce → Products → Attributes</li>';
echo '<li>Assign speed regulator terms to your products</li>';
echo '<li>For proper filtering, either:</li>';
echo '<ul>';
echo '<li>Make Speed Regulator a variation attribute and assign terms to variations, OR</li>';
echo '<li>Keep it as a product-level attribute and assign terms directly to products</li>';
echo '</ul>';
echo '</ol>';
?> 