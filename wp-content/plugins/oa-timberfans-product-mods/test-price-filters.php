<?php
/**
 * Test Price Filters - Simple Version
 * 
 * Access via: yourdomain.com/wp-content/plugins/oa-timberfans-product-mods/test-price-filters.php?product_id=XXX
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Price Filter Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        table td, table th { padding: 8px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f0f0f0; }
    </style>
</head>
<body>

<h1>🔍 Price Filter Test</h1>

<?php
// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if (!$product_id) {
    echo '<div class="box">';
    echo '<p>Please select a product to test:</p>';
    
    $products = wc_get_products(array(
        'type' => 'variable',
        'limit' => 10,
        'status' => 'publish'
    ));
    
    if ($products) {
        echo '<ul>';
        foreach ($products as $product) {
            echo '<li><a href="?product_id=' . $product->get_id() . '">' . 
                 esc_html($product->get_name()) . ' (ID: ' . $product->get_id() . ')</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="error">No variable products found.</p>';
    }
    echo '</div>';
    echo '</body></html>';
    exit;
}

// Get product
$product = wc_get_product($product_id);
if (!$product || !$product->is_type('variable')) {
    die('<div class="box"><p class="error">Invalid product ID or not a variable product.</p></div></body></html>');
}

echo '<div class="box">';
echo '<h2>Product: ' . esc_html($product->get_name()) . '</h2>';
echo '<p><strong>Product ID:</strong> ' . $product_id . '</p>';
echo '</div>';

// Test 1: Check plugin loaded
echo '<div class="box">';
echo '<h3>Test 1: Plugin Status</h3>';
if (class_exists('OA_TFP_Core')) {
    echo '<p class="success">✓ Plugin class loaded</p>';
} else {
    echo '<p class="error">✗ Plugin class NOT loaded</p>';
}
echo '</div>';

// Test 2: Check filter
echo '<div class="box">';
echo '<h3>Test 2: Filter Registration</h3>';
$priority = has_filter('woocommerce_available_variation', array($GLOBALS['oa_tfp_core'] ?? null, 'add_price_to_out_of_stock_variations'));
if ($priority !== false) {
    echo '<p class="success">✓ Filter is registered (priority: ' . $priority . ')</p>';
} else {
    echo '<p class="warning">⚠ Could not verify filter registration (this is ok)</p>';
}
echo '</div>';

// Test 3: Get variation data
echo '<div class="box">';
echo '<h3>Test 3: Variation Data</h3>';

// Clear cache
wc_delete_product_transients($product_id);
echo '<p>Cache cleared...</p>';

$variations = $product->get_available_variations();
echo '<p><strong>Total variations:</strong> ' . count($variations) . '</p>';

if (empty($variations)) {
    echo '<p class="error">No variations found!</p>';
} else {
    echo '<table>';
    echo '<tr><th>Variation ID</th><th>Stock</th><th>Price (DB)</th><th>display_price (JS)</th><th>Status</th></tr>';
    
    foreach ($variations as $variation_data) {
        $variation_id = $variation_data['variation_id'];
        $variation_obj = wc_get_product($variation_id);
        
        $in_stock = $variation_obj->is_in_stock();
        $price = $variation_obj->get_price();
        $display_price = isset($variation_data['display_price']) ? $variation_data['display_price'] : null;
        
        echo '<tr>';
        echo '<td>' . $variation_id . '</td>';
        echo '<td>' . ($in_stock ? '✓ In Stock' : '✗ Out of Stock') . '</td>';
        echo '<td>R' . ($price ? number_format($price, 2) : '0.00') . '</td>';
        echo '<td>';
        if ($display_price && $display_price > 0) {
            echo '<span class="success">R' . number_format($display_price, 2) . ' ✓</span>';
        } else {
            echo '<span class="error">NOT SET ✗</span>';
        }
        echo '</td>';
        echo '<td>';
        if (!$in_stock && $display_price && $display_price > 0) {
            echo '<span class="success">WORKING!</span>';
        } elseif (!$in_stock && (!$display_price || $display_price == 0)) {
            echo '<span class="error">BROKEN!</span>';
        } else {
            echo 'N/A';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '</div>';

// Summary
echo '<div class="box">';
echo '<h3>Summary</h3>';

$out_of_stock_count = 0;
$working_count = 0;

foreach ($variations as $variation_data) {
    $variation_obj = wc_get_product($variation_data['variation_id']);
    if (!$variation_obj->is_in_stock()) {
        $out_of_stock_count++;
        if (isset($variation_data['display_price']) && $variation_data['display_price'] > 0) {
            $working_count++;
        }
    }
}

if ($out_of_stock_count == 0) {
    echo '<p class="warning">⚠ No out-of-stock variations found. Cannot test.</p>';
} elseif ($working_count == $out_of_stock_count) {
    echo '<p class="success">✓ ALL out-of-stock variations have price data! Fix is working!</p>';
} elseif ($working_count > 0) {
    echo '<p class="warning">⚠ SOME out-of-stock variations have prices (' . $working_count . ' of ' . $out_of_stock_count . ')</p>';
} else {
    echo '<p class="error">✗ Out-of-stock variations DO NOT have price data. Fix is NOT working.</p>';
    echo '<p>Possible causes:</p>';
    echo '<ul>';
    echo '<li>Filter not running at the right time</li>';
    echo '<li>Prices not set in the database</li>';
    echo '<li>Another plugin interfering</li>';
    echo '</ul>';
}

echo '</div>';

echo '<div class="box">';
echo '<p><a href="' . get_permalink($product_id) . '" target="_blank">View Product Page</a></p>';
echo '<p><strong>DELETE THIS FILE after testing!</strong></p>';
echo '</div>';

?>

</body>
</html>
