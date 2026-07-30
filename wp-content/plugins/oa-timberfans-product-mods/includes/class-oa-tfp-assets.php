<?php
/**
 * OA TFP Assets Class
 * 
 * Handles all CSS and JS enqueuing
 */
class OA_TFP_Assets {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Always enqueue CSS
        wp_enqueue_style(
            'oa-tfp-styles',
            OA_TFP_PLUGIN_URL . 'assets/oa-tfp-styles.css',
            array(),
            OA_TFP_PLUGIN_VERSION
        );
        
        // Only enqueue JS on product pages
        if (function_exists('is_product') && is_product()) {
            // Use minimal version that doesn't interfere with WooCommerce gallery
            wp_enqueue_script(
                'oa-tfp-variations',
                OA_TFP_PLUGIN_URL . 'assets/oa-tfp-variations-minimal.js',
                array('jquery', 'wc-add-to-cart-variation'),
                OA_TFP_PLUGIN_VERSION . '-minimal',
                true
            );
            
            // Ensure Product Add-Ons scripts are loaded properly
            $this->ensure_product_addons_scripts();
        }
    }
    
    /**
     * Ensure Product Add-Ons scripts are properly loaded
     */
    private function ensure_product_addons_scripts() {
        // Force Product Add-Ons scripts to load in correct order
        if (class_exists('WC_Product_Addons')) {
            // Ensure add-to-cart-variation.js is loaded
            if (!wp_script_is('wc-add-to-cart-variation', 'enqueued')) {
                wp_enqueue_script('wc-add-to-cart-variation');
            }
            
            // Ensure Product Add-Ons scripts are loaded
            if (!wp_script_is('woocommerce-addons', 'enqueued')) {
                wp_enqueue_script('woocommerce-addons');
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post;
        
        // Product edit page
        if ('post.php' === $hook && isset($post->post_type) && 'product' === $post->post_type) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
        }
        
        // Taxonomy edit pages
        if ((strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) && 
            isset($_GET['taxonomy']) && ($_GET['taxonomy'] === 'pa_timber-finish' || $_GET['taxonomy'] === 'pa_metal-finish')) {
            wp_enqueue_media();
            wp_enqueue_script(
                'oa-tfp-term-image-admin', 
                OA_TFP_PLUGIN_URL . 'assets/oa-tfp-term-image-admin.js', 
                array('jquery'), 
                OA_TFP_PLUGIN_VERSION, 
                true
            );
        }
    }
} 