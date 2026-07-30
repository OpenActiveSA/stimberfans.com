<?php
/**
 * Form Populator Class
 *
 * Handles population of Gravity Forms fields with WooCommerce data
 *
 * @package OA_TimberFans_GF_Mod
 * @since 1.0.0
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * OA_TF_Form_Populator class
 */
class OA_TF_Form_Populator {
    
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
        // Form field population hooks
        add_filter( 'gform_pre_render_3', array( $this, 'populate_fan_range' ), 10, 1 );
        add_filter( 'gform_pre_validation_3', array( $this, 'populate_fan_range' ), 10, 1 );
        add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_fan_range' ), 10, 1 );
        
        add_filter( 'gform_pre_render_3', array( $this, 'populate_fan_size' ), 10, 1 );
        add_filter( 'gform_pre_validation_3', array( $this, 'populate_fan_size' ), 10, 1 );
        add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_fan_size' ), 10, 1 );
        
        add_filter( 'gform_pre_render_3', array( $this, 'populate_timber_finish' ), 10, 1 );
        add_filter( 'gform_pre_validation_3', array( $this, 'populate_timber_finish' ), 10, 1 );
        add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_timber_finish' ), 10, 1 );
        
        add_filter( 'gform_pre_render_3', array( $this, 'populate_metal_finish' ), 10, 1 );
        add_filter( 'gform_pre_validation_3', array( $this, 'populate_metal_finish' ), 10, 1 );
        add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_metal_finish' ), 10, 1 );
        
        add_filter( 'gform_pre_render_3', array( $this, 'populate_speed_regulator' ), 10, 1 );
        add_filter( 'gform_pre_validation_3', array( $this, 'populate_speed_regulator' ), 10, 1 );
        add_filter( 'gform_admin_pre_render_3', array( $this, 'populate_speed_regulator' ), 10, 1 );
    }
    
    /**
     * Populate fan range field (Field 1)
     *
     * @param array $form Gravity Forms form object
     * @return array Modified form object
     */
    public function populate_fan_range( $form ) {
        foreach ( $form['fields'] as &$field ) {
            if ( intval( $field->id ) === 1 && $field->type === 'radio' ) {
                $field->choices = $this->get_product_choices();
            }
        }
        return $form;
    }
    
    /**
     * Populate fan size field (Field 4)
     *
     * @param array $form Gravity Forms form object
     * @return array Modified form object
     */
    public function populate_fan_size( $form ) {
        foreach ( $form['fields'] as &$field ) {
            if ( intval( $field->id ) === 4 && $field->type === 'radio' ) {
                $field->choices = $this->get_size_choices();
            }
        }
        return $form;
    }
    
    /**
     * Populate timber finish field (Field 5)
     *
     * @param array $form Gravity Forms form object
     * @return array Modified form object
     */
    public function populate_timber_finish( $form ) {
        foreach ( $form['fields'] as &$field ) {
            if ( intval( $field->id ) === 5 && $field->type === 'radio' ) {
                $field->choices = $this->get_timber_finish_choices();
            }
        }
        return $form;
    }
    
    /**
     * Populate metal finish field (Field 6)
     *
     * @param array $form Gravity Forms form object
     * @return array Modified form object
     */
    public function populate_metal_finish( $form ) {
        foreach ( $form['fields'] as &$field ) {
            if ( intval( $field->id ) === 6 && $field->type === 'radio' ) {
                $field->choices = $this->get_metal_finish_choices();
            }
        }
        return $form;
    }
    
    /**
     * Populate speed regulator field (Field 7)
     *
     * @param array $form Gravity Forms form object
     * @return array Modified form object
     */
    public function populate_speed_regulator( $form ) {
        foreach ( $form['fields'] as &$field ) {
            if ( intval( $field->id ) === 7 && $field->type === 'radio' ) {
                $field->choices = $this->get_speed_regulator_choices();
            }
        }
        return $form;
    }
    
    /**
     * Get product choices for fan range
     *
     * @return array Array of choices
     */
    private function get_product_choices() {
        // Use get_posts to match shop page ordering (menu_order title)
        $product_ids = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ) );
        
        $choices = array();
        
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }
            
            $image_html = $this->get_product_image_html( $product );
            $choice_text = $image_html . '<span>' . esc_html( $product->get_name() ) . '</span>';
            $choices[] = array(
                'text'  => $choice_text,
                'value' => $product->get_id(),
            );
        }
        
        return $choices;
    }
    
    /**
     * Get size choices
     *
     * @return array Array of choices
     */
    private function get_size_choices() {
        $choices = array();
        
        // Try to get from taxonomy first
        $size_terms = get_terms( array(
            'taxonomy'   => 'pa_size',
            'hide_empty' => false,
        ) );
        
        if ( ! is_wp_error( $size_terms ) && ! empty( $size_terms ) ) {
            foreach ( $size_terms as $term ) {
                $choices[] = array(
                    'text'  => $term->name,
                    'value' => $term->slug,
                );
            }
        } else {
            // Fallback to getting from all variable products
            $choices = $this->get_attribute_choices_from_products( 'size' );
        }
        
        return $choices;
    }
    
    /**
     * Get timber finish choices
     *
     * @return array Array of choices
     */
    private function get_timber_finish_choices() {
        $choices = array();
        
        // Try to get from taxonomy first
        $finish_terms = get_terms( array(
            'taxonomy'   => 'pa_timber-finish',
            'hide_empty' => false,
        ) );
        
        error_log( 'OA TimberFans: Found ' . count( $finish_terms ) . ' timber finish terms' );
        
        if ( ! is_wp_error( $finish_terms ) && ! empty( $finish_terms ) ) {
            foreach ( $finish_terms as $term ) {
                error_log( 'OA TimberFans: Processing timber finish term: ' . $term->name );
                $image_html = $this->get_term_image_html( $term );
                $choice_text = $image_html . '<span>' . esc_html( $term->name ) . '</span>';
                $choices[] = array(
                    'text'  => $choice_text,
                    'value' => $term->slug,
                );
            }
        } else {
            error_log( 'OA TimberFans: No timber finish terms found, using fallback' );
            // Fallback to getting from all variable products
            $choices = $this->get_attribute_choices_from_products( 'finish', array( 'finish', 'timber' ) );
        }
        
        error_log( 'OA TimberFans: Returning ' . count( $choices ) . ' timber finish choices' );
        return $choices;
    }
    
    /**
     * Get metal finish choices
     *
     * @return array Array of choices
     */
    private function get_metal_finish_choices() {
        $choices = array();
        
        // Try to get from taxonomy first
        $metal_finish_terms = get_terms( array(
            'taxonomy'   => 'pa_metal-finish',
            'hide_empty' => false,
        ) );
        
        error_log( 'OA TimberFans: Found ' . count( $metal_finish_terms ) . ' metal finish terms' );
        
        if ( ! is_wp_error( $metal_finish_terms ) && ! empty( $metal_finish_terms ) ) {
            foreach ( $metal_finish_terms as $term ) {
                error_log( 'OA TimberFans: Processing metal finish term: ' . $term->name );
                $image_html = $this->get_term_image_html( $term );
                $choice_text = $image_html . '<span>' . esc_html( $term->name ) . '</span>';
                $choices[] = array(
                    'text'  => $choice_text,
                    'value' => $term->slug,
                );
            }
        } else {
            error_log( 'OA TimberFans: No metal finish terms found, using fallback' );
            // Fallback to getting from all variable products
            $choices = $this->get_attribute_choices_from_products( 'metal', array( 'metal', 'finish' ) );
        }
        
        error_log( 'OA TimberFans: Returning ' . count( $choices ) . ' metal finish choices' );
        return $choices;
    }
    
    /**
     * Get speed regulator choices
     *
     * @return array Array of choices
     */
    private function get_speed_regulator_choices() {
        $choices = array();
        
        // Try to get from taxonomy first
        $speed_terms = get_terms( array(
            'taxonomy'   => 'pa_speed-regulator',
            'hide_empty' => false,
        ) );
        
        error_log( 'OA TimberFans: Found ' . count( $speed_terms ) . ' speed regulator terms' );
        
        if ( ! is_wp_error( $speed_terms ) && ! empty( $speed_terms ) ) {
            foreach ( $speed_terms as $term ) {
                error_log( 'OA TimberFans: Processing speed regulator term: ' . $term->name );
                $image_html = $this->get_term_image_html( $term );
                $choice_text = $image_html . '<span>' . esc_html( $term->name ) . '</span>';
                $choices[] = array(
                    'text'  => $choice_text,
                    'value' => $term->slug,
                );
            }
        } else {
            error_log( 'OA TimberFans: No speed regulator terms found, using fallback' );
            // Fallback to getting from all products (including product-level attributes)
            $choices = $this->get_attribute_choices_from_products( 'speed-regulator', array( 'speed', 'regulator', 'speed-regulator', 'speed_regulator' ) );
        }
        
        error_log( 'OA TimberFans: Returning ' . count( $choices ) . ' speed regulator choices' );
        return $choices;
    }
    
    /**
     * Get attribute choices from products
     *
     * @param string $attribute_name Attribute name to search for
     * @param array  $search_terms Additional search terms
     * @return array Array of choices
     */
    private function get_attribute_choices_from_products( $attribute_name, $search_terms = array() ) {
        $choices = array();
        $all_options = array();
        
        $products = wc_get_products( array(
            'status' => 'publish',
            'limit'  => -1,
        ) );
        
        foreach ( $products as $product ) {
            $attributes = $product->get_attributes();
            
            foreach ( $attributes as $attr ) {
                $attr_name = strtolower( $attr->get_name() );
                
                // Check if this attribute matches our search
                $is_match = strpos( $attr_name, $attribute_name ) !== false;
                
                if ( ! $is_match && ! empty( $search_terms ) ) {
                    foreach ( $search_terms as $term ) {
                        if ( strpos( $attr_name, $term ) !== false ) {
                            $is_match = true;
                            break;
                        }
                    }
                }
                
                if ( $is_match ) {
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
                                $all_options[ $term->slug ] = $term->name;
                            }
                        } else {
                            $all_options[ $option ] = $option;
                        }
                    }
                }
            }
        }
        
        foreach ( $all_options as $slug => $name ) {
            $choices[] = array(
                'text'  => $name,
                'value' => $slug,
            );
        }
        
        return $choices;
    }
    
    /**
     * Get product image HTML
     *
     * @param WC_Product $product Product object
     * @return string Image HTML
     */
    private function get_product_image_html( $product ) {
        $image_id = $product->get_image_id();
        $image_html = '';
        
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            if ( $image_url ) {
                $image_html = sprintf(
                    '<img src="%s" alt="%s">',
                    esc_url( $image_url ),
                    esc_attr( $product->get_name() )
                );
            }
        }
        
        return $image_html;
    }
    
    /**
     * Get term image HTML
     *
     * @param WP_Term $term Term object
     * @return string Image HTML
     */
    private function get_term_image_html( $term ) {
        $image_id = get_term_meta( $term->term_id, 'tfp_term_image', true );
        $image_html = '';
        
        // Debug logging
        error_log( 'OA TimberFans: Getting term image for ' . $term->name . ' (ID: ' . $term->term_id . ')' );
        error_log( 'OA TimberFans: Term image ID: ' . $image_id );
        
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
            error_log( 'OA TimberFans: Term image URL: ' . $image_url );
            
            if ( $image_url ) {
                $image_html = sprintf(
                    '<img src="%s" alt="%s">',
                    esc_url( $image_url ),
                    esc_attr( $term->name )
                );
            }
        } else {
            error_log( 'OA TimberFans: No image found for term ' . $term->name );
        }
        
        return $image_html;
    }
} 