<?php
/**
 * Quote System Diagnostic Test Page
 * 
 * Add this as a WordPress page template or run directly
 * URL: /wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TimberFans Quote System Diagnostics</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            margin: 40px;
            background: #f0f0f1;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        h2 {
            color: #0073aa;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f6f7f7;
            border-left: 4px solid #0073aa;
        }
        .success {
            border-left-color: #46b450;
            background: #ecf7ed;
        }
        .warning {
            border-left-color: #ffb900;
            background: #fff8e5;
        }
        .error {
            border-left-color: #dc3232;
            background: #f8d7da;
        }
        .status-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        pre {
            background: #2c3338;
            color: #50c878;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
        }
        .code {
            font-family: Monaco, Consolas, monospace;
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 TimberFans Quote System Diagnostics</h1>
        <p>This page checks if your quote system is properly configured and working.</p>

        <?php
        // Test 1: Check if plugins are active
        echo '<h2>1. Plugin Status</h2>';
        
        $product_mods_active = class_exists('OA_TFP_Core');
        $gf_mod_active = class_exists('OA_TF_Form_Populator');
        $woocommerce_active = class_exists('WooCommerce');
        $gravity_forms_active = class_exists('GFCommon');
        
        echo '<div class="test-section ' . ($product_mods_active ? 'success' : 'error') . '">';
        echo '<span class="status-icon">' . ($product_mods_active ? '✓' : '✗') . '</span>';
        echo '<strong>OA TFP Product Mods:</strong> ' . ($product_mods_active ? 'Active' : 'INACTIVE');
        echo '</div>';
        
        echo '<div class="test-section ' . ($gf_mod_active ? 'success' : 'error') . '">';
        echo '<span class="status-icon">' . ($gf_mod_active ? '✓' : '✗') . '</span>';
        echo '<strong>OA TimberFans GF Mod:</strong> ' . ($gf_mod_active ? 'Active' : 'INACTIVE');
        echo '</div>';
        
        echo '<div class="test-section ' . ($woocommerce_active ? 'success' : 'error') . '">';
        echo '<span class="status-icon">' . ($woocommerce_active ? '✓' : '✗') . '</span>';
        echo '<strong>WooCommerce:</strong> ' . ($woocommerce_active ? 'Active' : 'INACTIVE');
        echo '</div>';
        
        echo '<div class="test-section ' . ($gravity_forms_active ? 'success' : 'error') . '">';
        echo '<span class="status-icon">' . ($gravity_forms_active ? '✓' : '✗') . '</span>';
        echo '<strong>Gravity Forms:</strong> ' . ($gravity_forms_active ? 'Active' : 'INACTIVE');
        echo '</div>';

        // Test 2: Check quote page configuration
        echo '<h2>2. Quote Page Configuration</h2>';
        
        $quote_page_id = get_option('oa_tfp_quote_page');
        $quote_page = $quote_page_id ? get_post($quote_page_id) : null;
        
        if ($quote_page && $quote_page->post_status === 'publish') {
            $quote_url = get_permalink($quote_page_id);
            echo '<div class="test-section success">';
            echo '<span class="status-icon">✓</span>';
            echo '<strong>Quote Page:</strong> Configured<br>';
            echo '<strong>Page Title:</strong> ' . esc_html($quote_page->post_title) . '<br>';
            echo '<strong>Page ID:</strong> ' . $quote_page_id . '<br>';
            echo '<strong>URL:</strong> <a href="' . esc_url($quote_url) . '" target="_blank">' . esc_html($quote_url) . '</a>';
            echo '</div>';
        } else {
            echo '<div class="test-section error">';
            echo '<span class="status-icon">✗</span>';
            echo '<strong>Quote Page:</strong> NOT CONFIGURED or not published<br>';
            echo '<strong>Action:</strong> Go to Settings → OA TFP Product Mods and select a published page';
            echo '</div>';
        }

        // Test 3: Check for Gravity Form #3
        if ($gravity_forms_active) {
            echo '<h2>3. Gravity Form Configuration</h2>';
            
            $form = GFAPI::get_form(3);
            
            if ($form) {
                echo '<div class="test-section success">';
                echo '<span class="status-icon">✓</span>';
                echo '<strong>Gravity Form #3:</strong> Found<br>';
                echo '<strong>Form Title:</strong> ' . esc_html($form['title']) . '<br>';
                echo '<strong>Total Fields:</strong> ' . count($form['fields']) . '<br><br>';
                
                // Check for expected fields
                $expected_fields = array(
                    1 => 'Fan Range',
                    2 => 'Product Name',
                    3 => 'Quantity',
                    4 => 'Fan Size',
                    5 => 'Timber Finish',
                    6 => 'Metal Finish',
                    7 => 'Speed Regulator',
                    9 => 'Add-ons'
                );
                
                echo '<strong>Field Mapping:</strong><br>';
                echo '<table>';
                echo '<tr><th>Expected Field ID</th><th>Expected Name</th><th>Actual Type</th><th>Status</th></tr>';
                
                foreach ($expected_fields as $field_id => $field_name) {
                    $field = GFAPI::get_field($form, $field_id);
                    if ($field) {
                        echo '<tr>';
                        echo '<td>Field ' . $field_id . '</td>';
                        echo '<td>' . $field_name . '</td>';
                        echo '<td><span class="code">' . $field->type . '</span></td>';
                        echo '<td><span style="color: #46b450;">✓ Found</span></td>';
                        echo '</tr>';
                    } else {
                        echo '<tr>';
                        echo '<td>Field ' . $field_id . '</td>';
                        echo '<td>' . $field_name . '</td>';
                        echo '<td>-</td>';
                        echo '<td><span style="color: #dc3232;">✗ Missing</span></td>';
                        echo '</tr>';
                    }
                }
                
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="test-section error">';
                echo '<span class="status-icon">✗</span>';
                echo '<strong>Gravity Form #3:</strong> NOT FOUND<br>';
                echo '<strong>Action:</strong> Create a Gravity Form with ID 3 or update the code to use your form ID';
                echo '</div>';
            }
        }

        // Test 4: Check for variable products
        if ($woocommerce_active) {
            echo '<h2>4. Variable Products</h2>';
            
            $products = wc_get_products(array(
                'type' => 'variable',
                'limit' => 5,
                'status' => 'publish'
            ));
            
            if (count($products) > 0) {
                echo '<div class="test-section success">';
                echo '<span class="status-icon">✓</span>';
                echo '<strong>Variable Products:</strong> Found ' . count($products) . ' (showing first 5)<br><br>';
                
                echo '<table>';
                echo '<tr><th>Product Name</th><th>ID</th><th>Variations</th><th>Out of Stock</th><th>Test Link</th></tr>';
                
                foreach ($products as $product) {
                    $variations = $product->get_available_variations();
                    $out_of_stock = 0;
                    
                    foreach ($variations as $variation_data) {
                        $variation = wc_get_product($variation_data['variation_id']);
                        if (!$variation->is_in_stock()) {
                            $out_of_stock++;
                        }
                    }
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($product->get_name()) . '</td>';
                    echo '<td>' . $product->get_id() . '</td>';
                    echo '<td>' . count($variations) . '</td>';
                    echo '<td>' . $out_of_stock . '</td>';
                    echo '<td><a href="' . get_permalink($product->get_id()) . '" target="_blank">View</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                echo '</div>';
                
                if ($out_of_stock === 0) {
                    echo '<div class="test-section warning">';
                    echo '<span class="status-icon">⚠</span>';
                    echo '<strong>Warning:</strong> No out-of-stock variations found in the displayed products. The quote button will only appear for out-of-stock variations.';
                    echo '</div>';
                }
            } else {
                echo '<div class="test-section warning">';
                echo '<span class="status-icon">⚠</span>';
                echo '<strong>Variable Products:</strong> None found<br>';
                echo '<strong>Note:</strong> Quote button only works with variable products';
                echo '</div>';
            }
        }

        // Test 5: JavaScript file existence
        echo '<h2>5. Asset Files</h2>';
        
        $plugin_path = WP_PLUGIN_DIR . '/oa-timberfans-product-mods/';
        $assets_path = $plugin_path . 'assets/';
        
        $files_to_check = array(
            'oa-tfp-styles.css' => 'Styles',
            'oa-tfp-variations.js' => 'Variations JavaScript'
        );
        
        foreach ($files_to_check as $file => $name) {
            $exists = file_exists($assets_path . $file);
            echo '<div class="test-section ' . ($exists ? 'success' : 'error') . '">';
            echo '<span class="status-icon">' . ($exists ? '✓' : '✗') . '</span>';
            echo '<strong>' . $name . ':</strong> ' . ($exists ? 'Found' : 'MISSING');
            echo '<br><span class="code">' . $assets_path . $file . '</span>';
            echo '</div>';
        }

        // Test 6: Check core class methods
        echo '<h2>6. Core Functionality</h2>';
        
        if ($product_mods_active) {
            $core = new OA_TFP_Core();
            $methods_to_check = array(
                'add_quote_button' => 'Quote Button Method',
                'add_button_toggle_js' => 'Button Toggle JavaScript',
                'add_quote_form_js' => 'Form Population JavaScript'
            );
            
            foreach ($methods_to_check as $method => $name) {
                $exists = method_exists($core, $method);
                echo '<div class="test-section ' . ($exists ? 'success' : 'error') . '">';
                echo '<span class="status-icon">' . ($exists ? '✓' : '✗') . '</span>';
                echo '<strong>' . $name . ':</strong> ' . ($exists ? 'Found' : 'MISSING');
                echo '</div>';
            }
        }

        // Test 7: Testing instructions
        echo '<h2>7. Manual Testing Steps</h2>';
        echo '<div class="test-section">';
        echo '<ol>';
        echo '<li>Visit a product page with out-of-stock variations</li>';
        echo '<li>Open Browser Console (F12)</li>';
        echo '<li>Select variations until you find an out-of-stock combination</li>';
        echo '<li>The "Request Quote" button should appear</li>';
        echo '<li>Click the button and watch console output</li>';
        echo '<li>You should be redirected to the quote page</li>';
        echo '<li>The form should auto-populate with your selections</li>';
        echo '</ol>';
        echo '<p><strong>For detailed testing instructions, see:</strong> <span class="code">QUOTE-TESTING-GUIDE.md</span></p>';
        echo '</div>';

        // Test 8: Quick debug script
        echo '<h2>8. Browser Console Test Script</h2>';
        echo '<div class="test-section">';
        echo '<p>Copy and paste this into your browser console on a product page to monitor the quote system:</p>';
        echo '<pre>';
        echo htmlspecialchars("// Monitor variation changes
jQuery('form.variations_form').on('found_variation', function(e, variation) {
    console.log('Variation found:', variation.variation_id);
    console.log('In stock?', variation.is_in_stock);
    console.log('Purchasable?', variation.is_purchasable);
});

// Check if quote button exists
console.log('Quote button found:', jQuery('.oa-tfp-quote-button').length);

// Monitor quote button clicks
jQuery(document).on('click', '.oa-tfp-quote-button', function(e) {
    console.log('Quote button clicked!');
    setTimeout(function() {
        console.log('Stored data:', localStorage.getItem('timberfans_quote_data'));
    }, 100);
});

// Check variations data
console.log('Variations data:', jQuery('form.variations_form').data('product_variations'));");
        echo '</pre>';
        echo '</div>';

        ?>

        <h2>Summary</h2>
        <div class="test-section">
            <?php
            $all_good = $product_mods_active && $gf_mod_active && $woocommerce_active && $gravity_forms_active && $quote_page;
            
            if ($all_good) {
                echo '<span class="status-icon" style="font-size: 30px;">✓</span>';
                echo '<h3 style="display: inline; color: #46b450;">System appears to be configured correctly!</h3>';
                echo '<p>Proceed with manual testing on a product page. Use the testing guide for detailed instructions.</p>';
            } else {
                echo '<span class="status-icon" style="font-size: 30px;">✗</span>';
                echo '<h3 style="display: inline; color: #dc3232;">Configuration issues detected</h3>';
                echo '<p>Please resolve the errors shown above before testing the quote functionality.</p>';
            }
            ?>
        </div>

        <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
            <strong>Diagnostic Tool Version:</strong> 1.0<br>
            <strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?><br>
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
        </p>
    </div>
</body>
</html>


