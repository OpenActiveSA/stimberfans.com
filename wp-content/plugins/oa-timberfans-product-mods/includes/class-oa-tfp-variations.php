<?php
/**
 * OA TFP Variations Class
 * 
 * Handles custom variation UI functionality
 */
class OA_TFP_Variations {
    
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
        // Render custom variation buttons
        add_action('woocommerce_before_variations_form', array($this, 'render_variation_buttons'), 10);
    }
    
    /**
     * Render custom variation buttons
     */
    public function render_variation_buttons() {
        global $product;
        
        if (!$product instanceof WC_Product || !$product->is_type('variable')) {
            return;
        }
        
        $attributes = $product->get_attributes();
        if (empty($attributes)) {
            return;
        }
        
        echo '<div id="oa-tfp-variation-wrapper">';
        
        foreach ($attributes as $attribute) {
            if (!$attribute->is_taxonomy()) {
                continue;
            }
            
            $taxonomy = $attribute->get_name();
            $label    = wc_attribute_label($taxonomy);
            $terms    = get_terms(array(
                'taxonomy'   => $taxonomy,
                'orderby'    => 'menu_order',
                'hide_empty' => false,
            ));
            
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            
            $variation_attributes = $product->get_variation_attributes();
            $used_options = isset($variation_attributes[$taxonomy]) ? $variation_attributes[$taxonomy] : array();
            
            // Only render attributes that are used for variations
            if (!empty($used_options)) {
                if ($taxonomy === 'pa_timber-finish' || $taxonomy === 'pa_metal-finish') {
                    $this->render_image_grid_options($taxonomy, $label, $terms, $used_options);
                } else {
                    $this->render_row_options($taxonomy, $label, $terms, $used_options);
                }
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Render image grid options (shared for timber and metal)
     */
    private function render_image_grid_options($taxonomy, $label, $terms, $used_options) {
        $group_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-finish-group' : 'oa-tfp-metal-finish-group';
        $option_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-option' : 'oa-tfp-metal-option';
        $item_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-item' : 'oa-tfp-metal-item';
        $label_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-label' : 'oa-tfp-metal-label';
        $label_text_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-label-text' : 'oa-tfp-metal-label-text';
        $grid_class = $taxonomy === 'pa_timber-finish' ? 'oa-tfp-timber-options-grid' : 'oa-tfp-metal-options-grid';
        echo '<div class="oa-tfp-variation-group ' . $group_class . '" data-attribute_name="' . esc_attr($taxonomy) . '">';
        echo '<span class="oa-tfp-label ' . $label_class . '" style="display:block;margin-bottom:0.5rem;">' . esc_html($label) . '</span>';
        echo '<div class="oa-tfp-options-grid ' . $grid_class . '">';
        foreach ($terms as $term) {
            if (!in_array($term->slug, $used_options, true)) {
                continue;
            }
            $image_id = get_term_meta($term->term_id, 'tfp_term_image', true);
            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
            echo '<div class="oa-tfp-item ' . $item_class . ' oa-tfp-' . esc_attr($term->slug) . '">';
            $style = $image_url ? ' style="background-image:url(' . esc_url($image_url) . ');"' : '';
            $placeholder_class = $image_url ? '' : ($taxonomy === 'pa_timber-finish' ? ' oa-tfp-timber-img-placeholder' : ' oa-tfp-metal-img-placeholder');
            echo '<button type="button" class="oa-tfp-option ' . $option_class . $placeholder_class . ' oa-tfp-' . esc_attr($term->slug) . '" data-attribute_name="' . esc_attr($taxonomy) . '" data-value="' . esc_attr($term->slug) . '"' . $style . '>';
            echo '<span class="oa-tfp-checkmark-wrap"><svg class="oa-tfp-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320.95 320.95"><path d="M320.95,160.47c0,88.63-71.85,160.47-160.47,160.47S0,249.1,0,160.47,71.85,0,160.47,0s160.47,71.85,160.47,160.47ZM231.07,88.44c-2.66.55-5.59,2.64-7.7,4.35l-89.33,88.98c-1.67.62-2.56-.28-3.81-1.12-10.44-7.02-28.54-34.18-40.2-36.18-17.13-2.93-31.11,13.51-22.79,29.48,8.53,16.38,42.52,39.16,54.41,55.45,8,4.87,14.51,4.43,22.3-.35,31.72-34.58,73.78-67.16,103.61-102.72,3.71-4.42,9.03-11.19,8.93-17.22-.21-12.09-13.12-23.21-25.41-20.69Z"/></svg></span>';
            echo '</button>';
            echo '<div class="' . $label_text_class . '">' . esc_html($term->name) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render row options
     */
    private function render_row_options($taxonomy, $label, $terms, $used_options) {
        echo '<div class="oa-tfp-variation-group oa-tfp-row-group" data-attribute_name="' . esc_attr($taxonomy) . '">';
        echo '<span class="oa-tfp-label oa-tfp-row-label">' . esc_html($label) . '</span>';
        echo '<div class="oa-tfp-row-options">';
        
        foreach ($terms as $term) {
            if (!in_array($term->slug, $used_options, true)) {
                continue;
            }
            
            echo '<button type="button" class="oa-tfp-option oa-tfp-' . esc_attr($term->slug) . '" data-attribute_name="' . esc_attr($taxonomy) . '" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
        }
        
        echo '</div>';
        echo '</div>';
    }
} 