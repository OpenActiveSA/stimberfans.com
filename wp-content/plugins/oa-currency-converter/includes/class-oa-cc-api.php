<?php
/**
 * OA Currency Converter API Class
 * 
 * Handles fetching exchange rates from external APIs
 */
class OA_CC_API {
    
    /**
     * Fetch exchange rate from API
     * 
     * @return float|false Exchange rate or false on failure
     */
    public static function fetch_exchange_rate() {
        // Try multiple API sources for reliability
        $rate = self::fetch_from_exchangerate_api();
        
        if ($rate === false) {
            $rate = self::fetch_from_fixer_io();
        }
        
        if ($rate === false) {
            $rate = self::fetch_from_currencylayer();
        }
        
        return $rate;
    }
    
    /**
     * Fetch from exchangerate-api.com (free, no API key required)
     */
    private static function fetch_from_exchangerate_api() {
        $url = 'https://api.exchangerate-api.com/v4/latest/USD';
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['rates']['ZAR'])) {
            return floatval($data['rates']['ZAR']);
        }
        
        return false;
    }
    
    /**
     * Fetch from fixer.io (requires API key)
     */
    private static function fetch_from_fixer_io() {
        $api_key = get_option('oa_cc_fixer_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        $url = 'https://api.fixer.io/latest?base=USD&symbols=ZAR&access_key=' . urlencode($api_key);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['rates']['ZAR'])) {
            return floatval($data['rates']['ZAR']);
        }
        
        return false;
    }
    
    /**
     * Fetch from currencylayer.com (requires API key)
     */
    private static function fetch_from_currencylayer() {
        $api_key = get_option('oa_cc_currencylayer_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        $url = 'https://api.currencylayer.com/live?access_key=' . urlencode($api_key) . '&currencies=ZAR&source=USD';
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['quotes']['USDZAR'])) {
            return floatval($data['quotes']['USDZAR']);
        }
        
        return false;
    }
    
    /**
     * Update exchange rate manually
     */
    public static function update_exchange_rate() {
        $rate = self::fetch_exchange_rate();
        
        if ($rate !== false && $rate > 0) {
            update_option('oa_cc_usd_to_zar_rate', $rate);
            update_option('oa_cc_exchange_rate_last_updated', current_time('mysql'));
            
            // Update all product prices that have ZAR prices stored
            self::update_all_product_prices();
            
            return $rate;
        }
        
        return false;
    }
    
    /**
     * Update all product prices based on stored ZAR prices
     */
    private static function update_all_product_prices() {
        $exchange_rate = get_option('oa_cc_usd_to_zar_rate', 18.5);
        
        // Get all products with ZAR prices
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_oa_cc_zar_price',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $products = get_posts($args);
        
        foreach ($products as $product_post) {
            $zar_price = get_post_meta($product_post->ID, '_oa_cc_zar_price', true);
            
            if (!empty($zar_price)) {
                // Convert ZAR to USD and save as WooCommerce price
                $usd_price = floatval($zar_price) / floatval($exchange_rate);
                update_post_meta($product_post->ID, '_price', $usd_price);
                update_post_meta($product_post->ID, '_regular_price', $usd_price);
            }
            
            // Handle variations
            $product = wc_get_product($product_post->ID);
            if ($product && $product->is_type('variable')) {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation_zar = get_post_meta($variation_id, '_oa_cc_zar_price', true);
                    if (!empty($variation_zar)) {
                        // Convert ZAR to USD and save as WooCommerce price
                        $variation_usd = floatval($variation_zar) / floatval($exchange_rate);
                        update_post_meta($variation_id, '_price', $variation_usd);
                        update_post_meta($variation_id, '_regular_price', $variation_usd);
                    }
                }
            }
        }
    }
}
