<?php
/**
 * OA Currency Converter Core Class
 * 
 * Main plugin class that handles currency conversion functionality
 */
class OA_CC_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add meta boxes for products
        add_action('add_meta_boxes', array($this, 'add_price_meta_box'));
        
        // Use WooCommerce hook to save after WooCommerce processes the product
        add_action('woocommerce_process_product_meta', array($this, 'save_zar_price'), 20, 1);
        
        // Also hook into update_product to ensure price is saved after all processing
        add_action('woocommerce_update_product', array($this, 'update_product_price'), 20, 1);
        
        // Filter product prices to show converted values
        add_filter('woocommerce_product_get_price', array($this, 'convert_price_display'), 10, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'convert_price_display'), 10, 2);
        add_filter('woocommerce_product_get_sale_price', array($this, 'convert_price_display'), 10, 2);
        
        // Handle variation prices
        add_filter('woocommerce_product_variation_get_price', array($this, 'convert_price_display'), 10, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'convert_price_display'), 10, 2);
        add_filter('woocommerce_product_variation_get_sale_price', array($this, 'convert_price_display'), 10, 2);
        
        // Add USD price field to variation edit form
        add_action('woocommerce_variation_options_pricing', array($this, 'add_variation_usd_price_field'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_usd_price'), 10, 2);
    }
    
    /**
     * Add meta box for ZAR price input
     */
    public function add_price_meta_box() {
        add_meta_box(
            'oa_cc_zar_price',
            'ZAR Price Input',
            array($this, 'render_price_meta_box'),
            'product',
            'side',
            'high'
        );
    }
    
    /**
     * Render the ZAR price meta box
     */
    public function render_price_meta_box($post) {
        wp_nonce_field('oa_cc_save_zar_price', 'oa_cc_zar_price_nonce');
        
        $zar_price = get_post_meta($post->ID, '_oa_cc_zar_price', true);
        $exchange_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        $last_updated = get_option('oa_cc_exchange_rate_last_updated', '');
        
        ?>
        <div class="oa-cc-price-input">
            <p>
                <label for="oa_cc_zar_price">Enter Price in ZAR:</label>
                <input type="number" 
                       id="oa_cc_zar_price" 
                       name="oa_cc_zar_price" 
                       value="<?php echo esc_attr($zar_price); ?>" 
                       step="0.01" 
                       min="0"
                       style="width: 100%; margin-top: 5px;">
            </p>
            <?php if (!empty($zar_price)): ?>
                <p style="font-size: 12px; color: #666;">
                    <strong>USD Price:</strong> $<?php echo number_format($this->convert_zar_to_usd($zar_price), 2); ?><br>
                    <strong>Exchange Rate:</strong> 1 USD = <?php echo number_format($exchange_rate, 4); ?> ZAR
                    <?php if ($last_updated): ?>
                        <br><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($last_updated)); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save ZAR price and convert to USD
     */
    public function save_zar_price($post_id) {
        // Check nonce
        if (!isset($_POST['oa_cc_zar_price_nonce']) || !wp_verify_nonce($_POST['oa_cc_zar_price_nonce'], 'oa_cc_save_zar_price')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save ZAR price if provided
        if (isset($_POST['oa_cc_zar_price']) && $_POST['oa_cc_zar_price'] !== '') {
            $zar_price = floatval($_POST['oa_cc_zar_price']);
            update_post_meta($post_id, '_oa_cc_zar_price', $zar_price);
        } else {
            // If ZAR price is removed, delete the meta
            delete_post_meta($post_id, '_oa_cc_zar_price');
        }
    }
    
    /**
     * Update product price after WooCommerce has processed everything
     */
    public function update_product_price($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
        
        // Prevent infinite loops
        static $updating = array();
        $post_id = $product->get_id();
        
        if (isset($updating[$post_id])) {
            return;
        }
        
        $zar_price = get_post_meta($post_id, '_oa_cc_zar_price', true);
        
        // Only update if we have a ZAR price stored
        if (!empty($zar_price)) {
            // Convert to USD
            $usd_price = $this->convert_zar_to_usd($zar_price);
            
            // Check if price needs updating (avoid unnecessary saves)
            $current_price = $product->get_regular_price();
            if (abs(floatval($current_price) - floatval($usd_price)) > 0.01) {
                $updating[$post_id] = true;
                
                // Set the price using product object methods
                $product->set_regular_price($usd_price);
                $product->set_price($usd_price);
                
                // Save the product
                $product->save();
                
                unset($updating[$post_id]);
            }
        }
    }
    
    /**
     * Convert USD to ZAR
     */
    public function convert_usd_to_zar($usd_price) {
        $exchange_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        return floatval($usd_price) * floatval($exchange_rate);
    }
    
    /**
     * Convert ZAR to USD (for display purposes)
     */
    public function convert_zar_to_usd($zar_price) {
        $exchange_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        return floatval($zar_price) / floatval($exchange_rate);
    }
    
    /**
     * Convert price for display (if product has ZAR price stored, show converted USD value)
     */
    public function convert_price_display($price, $product) {
        // Only convert if we have a stored ZAR price
        $product_id = $product->get_id();
        $zar_price = get_post_meta($product_id, '_oa_cc_zar_price', true);
        
        if (!empty($zar_price)) {
            // Return the converted USD price
            return $this->convert_zar_to_usd($zar_price);
        }
        
        // Otherwise return original price
        return $price;
    }
    
    /**
     * Add ZAR price field to variation edit form
     */
    public function add_variation_usd_price_field($loop, $variation_data, $variation) {
        $zar_price = get_post_meta($variation->ID, '_oa_cc_zar_price', true);
        $exchange_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        
        ?>
        <div class="form-row form-row-first">
            <label>
                <?php _e('ZAR Price', 'woocommerce'); ?>
                <a class="tips" data-tip="<?php esc_attr_e('Enter price in ZAR. Will be converted to USD automatically.', 'woocommerce'); ?>">[?]</a>
            </label>
            <input type="number" 
                   name="variable_zar_price[<?php echo esc_attr($loop); ?>]" 
                   value="<?php echo esc_attr($zar_price); ?>" 
                   placeholder="<?php esc_attr_e('ZAR price', 'woocommerce'); ?>" 
                   step="0.01" 
                   min="0"
                   class="wc_input_price">
            <?php if (!empty($zar_price)): ?>
                <span style="display: block; font-size: 11px; color: #666; margin-top: 3px;">
                    USD: $<?php echo number_format($this->convert_zar_to_usd($zar_price), 2); ?> 
                    (Rate: <?php echo number_format($exchange_rate, 4); ?>)
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save variation ZAR price
     */
    public function save_variation_usd_price($variation_id, $loop) {
        // Check if this is an autosave or bulk edit
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (isset($_REQUEST['bulk_edit'])) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $variation_id)) {
            return;
        }
        
        // Get the variation product object
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            return;
        }
        
        if (isset($_POST['variable_zar_price'][$loop])) {
            $zar_price = floatval($_POST['variable_zar_price'][$loop]);
            
            if ($zar_price > 0) {
                update_post_meta($variation_id, '_oa_cc_zar_price', $zar_price);
                
                // Convert to USD and save using WooCommerce product methods
                $usd_price = $this->convert_zar_to_usd($zar_price);
                $variation->set_regular_price($usd_price);
                $variation->set_price($usd_price);
                $variation->save();
            } else {
                delete_post_meta($variation_id, '_oa_cc_zar_price');
            }
        } else {
            // If field is not set, check if we should clear it
            // Only clear if explicitly empty (not on initial load)
            if (isset($_POST['variable_zar_price']) && array_key_exists($loop, $_POST['variable_zar_price']) && $_POST['variable_zar_price'][$loop] === '') {
                delete_post_meta($variation_id, '_oa_cc_zar_price');
            }
        }
    }
}
