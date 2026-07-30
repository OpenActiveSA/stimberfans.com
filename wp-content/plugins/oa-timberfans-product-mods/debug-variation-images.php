<?php
/**
 * Debug script for variation image issues
 * Add ?debug_variations=1 to a product URL to see debug info
 */

// Add debug output to variation data
add_filter('woocommerce_available_variation', function($data, $product, $variation) {
    if (isset($_GET['debug_variations'])) {
        error_log('=== VARIATION DEBUG ===');
        error_log('Variation ID: ' . $variation->get_id());
        error_log('Image ID: ' . $variation->get_image_id());
        error_log('Has image in data: ' . (isset($data['image']) ? 'YES' : 'NO'));
        error_log('Has image_id in data: ' . (isset($data['image_id']) ? 'YES' : 'NO'));
        if (isset($data['image'])) {
            error_log('Image data: ' . print_r($data['image'], true));
        }
        error_log('Data keys: ' . implode(', ', array_keys($data)));
        error_log('======================');
    }
    return $data;
}, 999, 3);

// Add admin notice with debug info
add_action('wp_footer', function() {
    if (!isset($_GET['debug_variations'])) {
        return;
    }
    
    global $product;
    if (!$product || !$product->is_type('variable')) {
        return;
    }
    
    ?>
    <style>
        .debug-variations {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #000;
            color: #0f0;
            padding: 20px;
            border-radius: 5px;
            max-width: 600px;
            max-height: 80vh;
            overflow: auto;
            z-index: 99999;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .debug-variations h3 {
            color: #0f0;
            margin: 0 0 10px 0;
        }
        .debug-variations pre {
            background: #111;
            padding: 10px;
            overflow: auto;
            margin: 10px 0;
        }
        .debug-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f00;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
    <div class="debug-variations">
        <button class="debug-close" onclick="this.parentElement.remove()">✕</button>
        <h3>🔍 Variation Debug Info</h3>
        <?php
        $variations = $product->get_available_variations();
        echo '<p><strong>Total Variations:</strong> ' . count($variations) . '</p>';
        
        foreach ($variations as $index => $var_data) {
            $variation_obj = wc_get_product($var_data['variation_id']);
            echo '<hr style="border-color: #0f0; margin: 15px 0;">';
            echo '<p><strong>Variation #' . ($index + 1) . ' (ID: ' . $var_data['variation_id'] . ')</strong></p>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            echo '<li>Has image key: ' . (isset($var_data['image']) ? '✓ YES' : '✗ NO') . '</li>';
            echo '<li>Has image_id key: ' . (isset($var_data['image_id']) ? '✓ YES' : '✗ NO') . '</li>';
            echo '<li>Image ID value: ' . ($variation_obj->get_image_id() ?: 'NONE') . '</li>';
            
            if (isset($var_data['image']) && is_array($var_data['image'])) {
                echo '<li>Image URL: ' . ($var_data['image']['url'] ?? 'N/A') . '</li>';
                echo '<li>Image src: ' . ($var_data['image']['src'] ?? 'N/A') . '</li>';
                echo '<li>Image srcset: ' . (isset($var_data['image']['srcset']) ? 'Present' : 'N/A') . '</li>';
            }
            
            echo '<li>Attributes: ' . implode(', ', array_map(function($k, $v) {
                return str_replace('attribute_', '', $k) . '=' . $v;
            }, array_keys($var_data['attributes']), $var_data['attributes'])) . '</li>';
            echo '</ul>';
        }
        ?>
        <hr style="border-color: #0f0; margin: 15px 0;">
        <p><strong>JavaScript Check:</strong></p>
        <pre id="js-debug">Loading...</pre>
    </div>
    <script>
        jQuery(document).ready(function($) {
            var form = $('form.variations_form');
            var variations = form.data('product_variations');
            var output = [];
            
            output.push('Variations loaded in JS: ' + (variations ? 'YES' : 'NO'));
            output.push('AJAX mode: ' + (variations === false ? 'YES' : 'NO'));
            
            if (variations && variations.length) {
                output.push('Total variations in JS: ' + variations.length);
                output.push('\nFirst variation sample:');
                var first = variations[0];
                output.push('- Has image: ' + (first.image ? 'YES' : 'NO'));
                output.push('- Has image_id: ' + (first.image_id ? 'YES' : 'NO'));
                if (first.image) {
                    output.push('- Image keys: ' + Object.keys(first.image).join(', '));
                }
            }
            
            $('#js-debug').text(output.join('\n'));
        });
    </script>
    <?php
});


