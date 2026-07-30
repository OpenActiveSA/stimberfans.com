<?php
/**
 * AJAX Handler Class
 *
 * Handles AJAX requests for dynamic field filtering
 *
 * @package OA_TimberFans_GF_Mod
 * @since 1.0.0
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * OA_TF_Ajax_Handler class
 */
class OA_TF_Ajax_Handler {
    
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
        // AJAX endpoints
        add_action( 'wp_ajax_oa_tf_get_available_sizes', array( $this, 'get_available_sizes' ) );
        add_action( 'wp_ajax_nopriv_oa_tf_get_available_sizes', array( $this, 'get_available_sizes' ) );
        
        add_action( 'wp_ajax_oa_tf_get_available_finishes', array( $this, 'get_available_finishes' ) );
        add_action( 'wp_ajax_nopriv_oa_tf_get_available_finishes', array( $this, 'get_available_finishes' ) );
        
        add_action( 'wp_ajax_oa_tf_get_available_metal_finishes', array( $this, 'get_available_metal_finishes' ) );
        add_action( 'wp_ajax_nopriv_oa_tf_get_available_metal_finishes', array( $this, 'get_available_metal_finishes' ) );
        
        add_action( 'wp_ajax_oa_tf_get_available_speed_regulators', array( $this, 'get_available_speed_regulators' ) );
        add_action( 'wp_ajax_nopriv_oa_tf_get_available_speed_regulators', array( $this, 'get_available_speed_regulators' ) );
    }
    
    /**
     * Get available sizes for a product
     */
    public function get_available_sizes() {
        $this->log_ajax_request( 'oa_tf_get_available_sizes' );
        
        $product_id = absint( $_POST['product_id'] );
        $available_sizes = $this->get_product_attributes( $product_id, 'size', array( 'size', 'fan' ) );
        
        $this->log_ajax_response( 'available_sizes', $available_sizes );
        wp_send_json( $available_sizes );
    }
    
    /**
     * Get available finishes for a product.
     * Uses actual variation attribute values (same as product page) so form and product always match.
     */
    public function get_available_finishes() {
        $this->log_ajax_request( 'oa_tf_get_available_finishes' );
        
        $product_id = absint( $_POST['product_id'] );
        $product    = wc_get_product( $product_id );
        
        if ( $product && $product->is_type( 'variable' ) ) {
            $available_finishes = $this->get_actual_variation_attribute_values( $product, 'pa_timber-finish' );
            $this->log_info( 'Timber finish from variation meta (same as product page)', array( 'slugs' => $available_finishes ) );
        } else {
            $available_finishes = $this->get_product_attributes( $product_id, 'finish', array( 'finish', 'timber' ) );
        }
        
        $this->log_ajax_response( 'available_finishes', $available_finishes );
        wp_send_json( $available_finishes );
    }
    
    /**
     * Get available metal finishes for a product.
     * Uses actual variation attribute values (same as product page) so form and product always match.
     */
    public function get_available_metal_finishes() {
        $this->log_ajax_request( 'oa_tf_get_available_metal_finishes' );
        
        $product_id = absint( $_POST['product_id'] );
        $product    = wc_get_product( $product_id );
        
        if ( $product && $product->is_type( 'variable' ) ) {
            $available_metal_finishes = $this->get_actual_variation_attribute_values( $product, 'pa_metal-finish' );
            $this->log_info( 'Metal finish from variation meta (same as product page)', array( 'slugs' => $available_metal_finishes ) );
        } else {
            $available_metal_finishes = $this->get_product_attributes( $product_id, 'metal', array( 'metal', 'finish' ) );
        }
        
        $this->log_ajax_response( 'available_metal_finishes', $available_metal_finishes );
        wp_send_json( $available_metal_finishes );
    }
    
    /**
     * Get available speed regulators for a product
     */
    public function get_available_speed_regulators() {
        $this->log_ajax_request( 'oa_tf_get_available_speed_regulators' );
        
        $product_id = absint( $_POST['product_id'] );
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            $this->log_error( 'Product not found', array( 'product_id' => $product_id ) );
            wp_send_json( array() );
        }
        
        // First try to get from product attributes (including variation attributes)
        $available_speed_regulators = $this->get_product_attributes( $product_id, 'speed-regulator', array( 'speed', 'regulator', 'speed-regulator', 'speed_regulator' ) );
        
        // If no attributes found, check product-specific terms
        if ( empty( $available_speed_regulators ) ) {
            $this->log_info( 'No speed regulator attributes found, checking product-specific terms' );
            
            // Only fetch terms assigned to this specific product
            $speed_slugs = wc_get_product_terms( $product_id, 'pa_speed-regulator', array(
                'fields' => 'slugs',
            ) );
            
            if ( ! is_wp_error( $speed_slugs ) && ! empty( $speed_slugs ) ) {
                $available_speed_regulators = $speed_slugs;
                $this->log_info( 'Found product-specific speed regulator terms', array( 'slugs' => $speed_slugs ) );
            } else {
                $this->log_info( 'No product-specific speed regulator terms found, checking variations directly' );
                
                // If product is variable, check variation attributes directly
                if ( $product->is_type( 'variable' ) ) {
                    $available_speed_regulators = $this->get_speed_regulators_from_variations( $product );
                    
                    if ( empty( $available_speed_regulators ) ) {
                        $this->log_info( 'No speed regulators found in variations, using fallback' );
                        // Fallback: get all available speed regulator terms from taxonomy
                        $available_speed_regulators = $this->get_fallback_speed_regulators();
                    }
                } else {
                    // For simple products, use fallback
                    $available_speed_regulators = $this->get_fallback_speed_regulators();
                }
            }
        }
        
        $this->log_ajax_response( 'available_speed_regulators', $available_speed_regulators );
        wp_send_json( $available_speed_regulators );
    }
    
    /**
     * Get speed regulators directly from product variations
     *
     * @param WC_Product_Variable $product Product object
     * @return array Array of speed regulator slugs
     */
    private function get_speed_regulators_from_variations( $product ) {
        $speed_regulators = array();
        $variations = $product->get_available_variations();
        
        $this->log_info( 'Checking variations for speed regulators', array( 'variations_count' => count( $variations ) ) );
        
        foreach ( $variations as $variation ) {
            if ( isset( $variation['attributes'] ) ) {
                foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
                    $attr_key_lower = strtolower( $attr_key );
                    
                    // Check if this is a speed regulator attribute
                    if ( strpos( $attr_key_lower, 'speed' ) !== false && strpos( $attr_key_lower, 'regulator' ) !== false ) {
                        if ( ! empty( $attr_value ) && ! in_array( $attr_value, $speed_regulators ) ) {
                            $speed_regulators[] = $attr_value;
                            $this->log_info( 'Found speed regulator in variation', array( 'value' => $attr_value ) );
                        }
                    }
                }
            }
        }
        
        return $speed_regulators;
    }
    
    /**
     * Get fallback speed regulators from taxonomy
     *
     * @return array Array of speed regulator slugs
     */
    private function get_fallback_speed_regulators() {
        $speed_terms = get_terms( array(
            'taxonomy'   => 'pa_speed-regulator',
            'hide_empty' => false,
        ) );
        
        $speed_regulators = array();
        
        if ( ! is_wp_error( $speed_terms ) && ! empty( $speed_terms ) ) {
            foreach ( $speed_terms as $term ) {
                $speed_regulators[] = $term->slug;
            }
            $this->log_info( 'Using fallback speed regulators from taxonomy', array( 'count' => count( $speed_regulators ) ) );
        }
        
        return $speed_regulators;
    }
    
    /**
     * Get product attributes
     *
     * @param int    $product_id Product ID
     * @param string $attribute_name Primary attribute name
     * @param array  $search_terms Additional search terms
     * @return array Array of available attributes
     */
    private function get_product_attributes( $product_id, $attribute_name, $search_terms = array() ) {
        $product = wc_get_product( $product_id );
        $available_attributes = array();
        
        if ( ! $product ) {
            $this->log_error( 'Product not found', array( 'product_id' => $product_id ) );
            return $this->get_fallback_attributes( $attribute_name, $search_terms );
        }
        
        $this->log_product_info( $product );
        
        // First, check for product-level attributes (not used for variations)
        $available_attributes = $this->get_product_level_attributes( $product, $attribute_name, $search_terms );
        
        // If no product-level attributes found, check variation attributes
        if ( empty( $available_attributes ) ) {
            if ( $product->is_type( 'variable' ) ) {
                $available_attributes = $this->get_variable_product_attributes( $product, $attribute_name, $search_terms );
            } else {
                $available_attributes = $this->get_simple_product_attributes( $product, $attribute_name );
            }
        }
        
        // If no attributes found, return empty array (let the calling method handle fallback)
        if ( empty( $available_attributes ) ) {
            $this->log_info( 'No attributes found for product, returning empty array' );
        }
        
        return $available_attributes;
    }
    
    /**
     * Get product-level attributes (not used for variations)
     *
     * @param WC_Product $product Product object
     * @param string     $attribute_name Attribute name
     * @param array      $search_terms Search terms
     * @return array Array of attributes
     */
    private function get_product_level_attributes( $product, $attribute_name, $search_terms ) {
        $attributes = $product->get_attributes();
        $available_attributes = array();
        
        $this->log_info( 'Checking product-level attributes', array( 
            'total_attributes' => count( $attributes ),
            'searching_for' => $attribute_name,
            'search_terms' => $search_terms
        ) );
        
        foreach ( $attributes as $attr ) {
            $attr_name = $attr->get_name();
            $this->log_attribute_check( $attr_name );
            
            // Check if this attribute matches our search
            $is_match = $this->is_attribute_match( $attr_name, $attribute_name, $search_terms );
            
            $this->log_info( 'Attribute match check', array(
                'attr_name' => $attr_name,
                'attribute_name' => $attribute_name,
                'is_match' => $is_match
            ) );
            
            if ( $is_match ) {
                $this->log_info( 'Found matching product-level attribute', array( 'attribute' => $attr_name ) );
                
                // Check if this attribute is used for variations
                $is_used_for_variations = $attr->get_variation();
                
                $this->log_info( 'Variation check', array(
                    'attr_name' => $attr_name,
                    'is_used_for_variations' => $is_used_for_variations
                ) );
                
                if ( ! $is_used_for_variations ) {
                    $this->log_info( 'Attribute is not used for variations, extracting options', array( 'attribute' => $attr_name ) );
                    $available_attributes = $this->extract_attribute_options( $attr );
                    break;
                } else {
                    $this->log_info( 'Attribute is used for variations, skipping', array( 'attribute' => $attr_name ) );
                }
            }
        }
        
        $this->log_info( 'Product-level attributes result', array(
            'found_attributes' => $available_attributes,
            'count' => count( $available_attributes )
        ) );
        
        return $available_attributes;
    }
    
    /**
     * Get attributes from variable product
     *
     * @param WC_Product_Variable $product Product object
     * @param string              $attribute_name Attribute name
     * @param array               $search_terms Search terms
     * @return array Array of attributes
     */
    private function get_variable_product_attributes( $product, $attribute_name, $search_terms ) {
        $attributes = $product->get_attributes();
        $available_attributes = array();
        
        $this->log_product_attributes( $attributes );
        
        foreach ( $attributes as $attr ) {
            $attr_name = $attr->get_name();
            $this->log_attribute_check( $attr_name );
            
            // Check if this attribute matches our search
            $is_match = $this->is_attribute_match( $attr_name, $attribute_name, $search_terms );
            
            if ( $is_match ) {
                $this->log_info( 'Found matching attribute', array( 'attribute' => $attr_name ) );
                $available_attributes = $this->extract_attribute_options( $attr );
                break;
            }
        }
        
        // If no attributes found in product attributes, try variations
        if ( empty( $available_attributes ) ) {
            $this->log_info( 'No attributes found in product attributes, checking variations' );
            $available_attributes = $this->get_attributes_from_variations( $product, $attribute_name, $search_terms );
        }
        
        return $available_attributes;
    }
    
    /**
     * Get attributes from simple product
     *
     * @param WC_Product_Simple $product Product object
     * @param string            $attribute_name Attribute name
     * @return array Array of attributes
     */
    private function get_simple_product_attributes( $product, $attribute_name ) {
        $available_attributes = array();
        
        // Check for meta-based attributes
        $meta_key = '_' . $attribute_name;
        $meta_value = get_post_meta( $product->get_id(), $meta_key, true );
        
        if ( ! empty( $meta_value ) ) {
            $available_attributes[] = $meta_value;
            $this->log_info( 'Added attribute from meta', array( 'meta_key' => $meta_key, 'value' => $meta_value ) );
        }
        
        return $available_attributes;
    }
    
    /**
     * Check if attribute matches search criteria
     *
     * @param string $attr_name Attribute name
     * @param string $primary_term Primary search term
     * @param array  $search_terms Additional search terms
     * @return bool Whether attribute matches
     */
    private function is_attribute_match( $attr_name, $primary_term, $search_terms ) {
        $attr_name_lower = strtolower( $attr_name );
        
        // Special handling for speed regulator
        if ( $primary_term === 'speed-regulator' ) {
            // Check for exact matches first
            if ( $attr_name_lower === 'speed-regulator' || $attr_name_lower === 'speed_regulator' || $attr_name_lower === 'pa_speed-regulator' ) {
                return true;
            }
            
            // Check for partial matches
            if ( strpos( $attr_name_lower, 'speed' ) !== false && strpos( $attr_name_lower, 'regulator' ) !== false ) {
                return true;
            }
        }
        
        // Check primary term
        if ( strpos( $attr_name_lower, $primary_term ) !== false ) {
            return true;
        }
        
        // Check additional search terms
        foreach ( $search_terms as $term ) {
            if ( strpos( $attr_name_lower, $term ) !== false ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract options from attribute
     *
     * @param WC_Product_Attribute $attr Attribute object
     * @return array Array of options
     */
    private function extract_attribute_options( $attr ) {
        $options = array();
        $attr_options = $attr->get_options();
        
        $this->log_info( 'Attribute options', array( 'options' => $attr_options ) );
        
        foreach ( $attr_options as $option ) {
            if ( $attr->is_taxonomy() ) {
                $term = get_term_by( 'slug', $option, $attr->get_name() );
                if ( $term ) {
                    $options[] = $term->slug;
                    $this->log_info( 'Added taxonomy option', array( 'slug' => $term->slug ) );
                } else {
                    $this->log_error( 'Term not found', array( 'slug' => $option ) );
                }
            } else {
                $options[] = $option;
                $this->log_info( 'Added custom option', array( 'option' => $option ) );
            }
        }
        
        return $options;
    }
    
    /**
     * Get actual attribute values from product variation meta (same source as product page).
     * Ensures form options match exactly what the product shows.
     *
     * @param WC_Product_Variable $product  Product object
     * @param string              $taxonomy Taxonomy name (e.g. 'pa_timber-finish', 'pa_metal-finish')
     * @return array Array of term slugs used in variations
     */
    private function get_actual_variation_attribute_values( $product, $taxonomy ) {
        global $wpdb;
        $variation_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'product_variation' AND post_status = 'publish' ORDER BY menu_order ASC, ID ASC",
            $product->get_id()
        ) );
        if ( empty( $variation_ids ) ) {
            return array();
        }
        $attribute_meta_key = 'attribute_' . $taxonomy;
        $variation_ids_safe = array_map( 'intval', $variation_ids );
        $ids_placeholder   = implode( ',', $variation_ids_safe );
        $meta_values        = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($ids_placeholder) AND meta_key = %s AND meta_value != '' AND meta_value IS NOT NULL",
            $attribute_meta_key
        ) );
        $used_options = array();
        foreach ( (array) $meta_values as $value ) {
            $value = trim( $value );
            if ( $value !== '' && ! in_array( $value, $used_options, true ) ) {
                $used_options[] = $value;
            }
        }
        return $used_options;
    }

    /**
     * Get attributes from product variations
     *
     * @param WC_Product_Variable $product Product object
     * @param string              $attribute_name Attribute name
     * @param array               $search_terms Search terms
     * @return array Array of attributes
     */
    private function get_attributes_from_variations( $product, $attribute_name, $search_terms ) {
        $variations = $product->get_available_variations();
        $available_attributes = array();
        
        $this->log_info( 'Checking variations', array( 'variations_count' => count( $variations ) ) );
        
        foreach ( $variations as $variation ) {
            if ( isset( $variation['attributes'] ) ) {
                foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
                    $attr_key_lower = strtolower( $attr_key );
                    
                    if ( $this->is_attribute_match( $attr_key_lower, $attribute_name, $search_terms ) ) {
                        $available_attributes[] = $attr_value;
                        $this->log_info( 'Added variation attribute', array( 'key' => $attr_key, 'value' => $attr_value ) );
                    }
                }
            }
        }
        
        return $available_attributes;
    }
    
    /**
     * Get fallback attributes
     *
     * @param string $attribute_name Attribute name
     * @param array  $search_terms Search terms
     * @return array Array of fallback attributes
     */
    private function get_fallback_attributes( $attribute_name, $search_terms ) {
        $this->log_info( 'Getting fallback attributes', array( 'attribute_name' => $attribute_name ) );
        
        // Try taxonomy first
        $taxonomy_name = 'pa_' . str_replace( '-', '-', $attribute_name );
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy_name,
            'hide_empty' => false,
        ) );
        
        $fallback_attributes = array();
        
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $fallback_attributes[] = $term->slug;
                $this->log_info( 'Added fallback taxonomy term', array( 'slug' => $term->slug ) );
            }
        } else {
            // Get from all variable products
            $fallback_attributes = $this->get_all_product_attributes( $attribute_name, $search_terms );
        }
        
        return $fallback_attributes;
    }
    
    /**
     * Get all product attributes
     *
     * @param string $attribute_name Attribute name
     * @param array  $search_terms Search terms
     * @return array Array of attributes
     */
    private function get_all_product_attributes( $attribute_name, $search_terms ) {
        $products = wc_get_products( array(
            'status' => 'publish',
            'limit'  => -1,
        ) );
        
        $all_attributes = array();
        
        foreach ( $products as $product ) {
            $attributes = $product->get_attributes();
            
            foreach ( $attributes as $attr ) {
                $attr_name = strtolower( $attr->get_name() );
                
                if ( $this->is_attribute_match( $attr_name, $attribute_name, $search_terms ) ) {
                    // Check if this attribute is used for variations
                    $is_used_for_variations = $attr->get_variation();
                    
                    // For speed regulator, prefer attributes that are NOT used for variations
                    if ( $attribute_name === 'speed-regulator' && $is_used_for_variations ) {
                        continue; // Skip variation attributes for speed regulator
                    }
                    
                    $options = $attr->get_options();
                    
                    foreach ( $options as $option ) {
                        if ( $attr->is_taxonomy() ) {
                            $term = get_term_by( 'slug', $option, $attr->get_name() );
                            if ( $term ) {
                                $all_attributes[ $term->slug ] = $term->name;
                            }
                        } else {
                            $all_attributes[ $option ] = $option;
                        }
                    }
                }
            }
        }
        
        $attributes_array = array_keys( $all_attributes );
        
        foreach ( $attributes_array as $attr ) {
            $this->log_info( 'Added fallback attribute from all products', array( 'attribute' => $attr ) );
        }
        
        return $attributes_array;
    }
    
    /**
     * Log AJAX request
     *
     * @param string $action Action name
     */
    private function log_ajax_request( $action ) {
        error_log( 'OA TimberFans: AJAX request received - ' . $action );
        error_log( 'OA TimberFans: Request parameters - ' . print_r( array(
            'action'     => $action,
            'post_data'  => $_POST,
        ), true ) );
    }
    
    /**
     * Log AJAX response
     *
     * @param string $type Response type
     * @param array  $data Response data
     */
    private function log_ajax_response( $type, $data ) {
        error_log( 'OA TimberFans: Sending ' . $type . ' - ' . print_r( array(
            $type => $data,
            'count' => count( $data ),
        ), true ) );
    }
    
    /**
     * Log product information
     *
     * @param WC_Product $product Product object
     */
    private function log_product_info( $product ) {
        error_log( 'OA TimberFans: Product found - ' . print_r( array(
            'product_id'   => $product->get_id(),
            'product_name'  => $product->get_name(),
            'product_type'  => $product->get_type(),
            'is_variable'  => $product->is_type( 'variable' ),
        ), true ) );
    }
    
    /**
     * Log product attributes
     *
     * @param array $attributes Product attributes
     */
    private function log_product_attributes( $attributes ) {
        error_log( 'OA TimberFans: Product attributes - ' . print_r( array(
            'total_attributes' => count( $attributes ),
            'attribute_names'  => array_keys( $attributes ),
        ), true ) );
    }
    
    /**
     * Log attribute check
     *
     * @param string $attr_name Attribute name
     */
    private function log_attribute_check( $attr_name ) {
        error_log( 'OA TimberFans: Checking attribute - ' . $attr_name );
    }
    
    /**
     * Log information message
     *
     * @param string $message Message
     * @param array  $data Additional data
     */
    private function log_info( $message, $data = array() ) {
        error_log( 'OA TimberFans: ' . $message . ' - ' . print_r( $data, true ) );
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     * @param array  $data Additional data
     */
    private function log_error( $message, $data = array() ) {
        error_log( 'OA TimberFans: Error - ' . $message . ' - ' . print_r( $data, true ) );
    }
} 