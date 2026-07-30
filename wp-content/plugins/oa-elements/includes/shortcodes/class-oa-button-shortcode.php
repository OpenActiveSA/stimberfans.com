<?php
/**
 * Button Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Button Shortcode Class
 */
class OA_Button_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_button_shortcode' ) ) {
            add_shortcode( 'oa_button', array( $this, 'render_button' ) );
        }
    }
    
    /**
     * Render button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_button( $atts ) {
        $defaults = array(
            'link'         => '',
            'target-blank' => 'no',
            'outline'      => 'no',
            'text'         => __( 'Click here', 'open-agency-elements' ),
            'size'         => 'medium',
            'color'        => 'primary',
            'class'        => '',
            'id'           => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_button' );
        
        // Build CSS classes
        $classes = $this->build_classes( $atts );
        
        // Build attributes
        $attributes = $this->build_attributes( $atts );
        
        // Generate button HTML
        if ( ! empty( $atts['link'] ) ) {
            return $this->render_link_button( $atts, $classes, $attributes );
        } else {
            return $this->render_button_element( $atts, $classes, $attributes );
        }
    }
    
    /**
     * Build CSS classes
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_classes( $atts ) {
        $classes = array(
            'oa-button',
            'button',
        );
        
        // Add outline class
        if ( $atts['outline'] === 'yes' ) {
            $classes[] = 'oa-button-outline';
        }
        
        // Add size class
        if ( in_array( $atts['size'], array( 'small', 'medium', 'large' ) ) ) {
            $classes[] = 'oa-button-' . $atts['size'];
        }
        
        // Add color class
        if ( in_array( $atts['color'], array( 'primary', 'secondary', 'success', 'danger', 'warning', 'info' ) ) ) {
            $classes[] = 'oa-button-' . $atts['color'];
        }
        
        // Add custom class
        if ( ! empty( $atts['class'] ) ) {
            $classes[] = sanitize_html_class( $atts['class'] );
        }
        
        return implode( ' ', $classes );
    }
    
    /**
     * Build HTML attributes
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_attributes( $atts ) {
        $attributes = array();
        
        // Add ID
        if ( ! empty( $atts['id'] ) ) {
            $attributes[] = 'id="' . esc_attr( $atts['id'] ) . '"';
        }
        
        return implode( ' ', $attributes );
    }
    
    /**
     * Render link button
     *
     * @param array  $atts Attributes
     * @param string $classes CSS classes
     * @param string $attributes HTML attributes
     * @return string
     */
    private function render_link_button( $atts, $classes, $attributes ) {
        $url = esc_url( $atts['link'] );
        $text = esc_html( $atts['text'] );
        $target = $atts['target-blank'] === 'yes' ? ' target="_blank" rel="noopener"' : '';
        
        return sprintf(
            '<a href="%1$s"%2$s class="%3$s" %4$s>%5$s</a>',
            $url,
            $target,
            $classes,
            $attributes,
            $text
        );
    }
    
    /**
     * Render button element
     *
     * @param array  $atts Attributes
     * @param string $classes CSS classes
     * @param string $attributes HTML attributes
     * @return string
     */
    private function render_button_element( $atts, $classes, $attributes ) {
        $text = esc_html( $atts['text'] );
        
        return sprintf(
            '<button type="button" class="%1$s" %2$s>%3$s</button>',
            $classes,
            $attributes,
            $text
        );
    }
    
    /**
     * Get button examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_button text="Click here" link="https://example.com"]',
                'description' => __( 'Basic button with link', 'open-agency-elements' ),
            ),
            'outline' => array(
                'shortcode' => '[oa_button text="Outline Button" outline="yes" color="primary"]',
                'description' => __( 'Outline button style', 'open-agency-elements' ),
            ),
            'external' => array(
                'shortcode' => '[oa_button text="External Link" link="https://example.com" target-blank="yes"]',
                'description' => __( 'Button that opens in new tab', 'open-agency-elements' ),
            ),
            'sizes' => array(
                'shortcode' => '[oa_button text="Large Button" size="large" color="success"]',
                'description' => __( 'Large button with custom color', 'open-agency-elements' ),
            ),
        );
    }
} 