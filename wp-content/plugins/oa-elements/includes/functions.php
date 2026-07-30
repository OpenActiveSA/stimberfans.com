<?php
/**
 * Core functions for Open Agency Elements
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if a feature is enabled
 *
 * @param string $feature Feature name
 * @return bool
 */
function oa_is_feature_enabled( $feature ) {
    // Safety check - ensure WordPress is ready
    if ( ! function_exists( 'get_option' ) ) {
        return false;
    }
    
    // For fresh installations, if the option doesn't exist, it's disabled by default
    $option_value = get_option( $feature );
    
    // If option doesn't exist, it means it's a fresh installation and should be disabled
    if ( $option_value === false ) {
        return false;
    }
    
    return (bool) $option_value;
}

/**
 * Get plugin option with default
 *
 * @param string $option Option name
 * @param mixed  $default Default value
 * @return mixed
 */
function oa_get_option( $option, $default = false ) {
    return get_option( $option, $default );
}

/**
 * Check if this is a fresh installation
 *
 * @return bool
 */
function oa_is_fresh_installation() {
    return ! get_option( 'oa_elements_version' );
}

/**
 * Get all available features
 *
 * @return array
 */
function oa_get_all_features() {
    return array(
        'oa_enable_smooth_scroll',
        'oa_enable_button_shortcode',
        'oa_enable_slider_shortcode',
        'oa_enable_logo_carousel',
        'oa_enable_brand_logo_carousel',
        'oa_enable_testimonial_carousel',
        'oa_enable_featured_products_carousel',
        'oa_enable_links_element',
        'oa_enable_title_area_element',
        'oa_enable_features_shortcode',
        'oa_enable_faq_shortcode',
        'oa_enable_site_loader',
        'oa_links_show_account',
        'oa_links_show_cart',
        'oa_links_show_mini_cart',
    );
}

/**
 * Get installation status for debugging
 *
 * @return array
 */
function oa_get_installation_status() {
    return array(
        'is_fresh_installation' => oa_is_fresh_installation(),
        'plugin_version' => get_option( 'oa_elements_version' ),
        'previous_version' => get_option( 'oa_elements_previous_version' ),
        'features_status' => array_map( 'oa_is_feature_enabled', oa_get_all_features() ),
    );
}

/**
 * Validate and sanitize hex color
 *
 * @param string $color Color to validate
 * @return string|false Valid hex color or false
 */
function oa_sanitize_hex_color( $color ) {
    // Remove any whitespace
    $color = trim( $color );
    
    // Check if it's a valid hex color
    if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
        return $color;
    }
    
    return false;
}

/**
 * Check if WooCommerce is active
 *
 * @return bool
 */
function oa_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
}

/**
 * Check if ACF is active
 *
 * @return bool
 */
function oa_is_acf_active() {
    return function_exists( 'get_field' );
}

/**
 * Get social media platforms
 *
 * @return array
 */
function oa_get_social_platforms() {
    return array(
        'facebook'  => 'Facebook',
        'twitter'   => 'Twitter',
        'instagram' => 'Instagram',
        'linkedin'  => 'LinkedIn',
        'youtube'   => 'YouTube',
    );
}

/**
 * Sanitize shortcode attributes
 *
 * @param array  $atts Attributes
 * @param array  $defaults Default attributes
 * @param string $shortcode Shortcode name
 * @return array
 */
function oa_sanitize_shortcode_atts( $atts, $defaults, $shortcode ) {
    $atts = shortcode_atts( $defaults, $atts, $shortcode );
    
    // Sanitize each attribute
    foreach ( $atts as $key => $value ) {
        if ( is_string( $value ) ) {
            $atts[ $key ] = sanitize_text_field( $value );
        }
    }
    
    return $atts;
}



/**
 * Get SVG icon content
 *
 * @param string $icon_name Icon name
 * @param array  $attributes Additional attributes
 * @return string
 */
function oa_get_svg_icon( $icon_name, $attributes = array() ) {
    $svg_path = OA_ELEMENTS_PLUGIN_DIR . 'assets/icons/' . $icon_name . '.svg';
    
    if ( ! file_exists( $svg_path ) ) {
        return '';
    }
    
    $svg_content = file_get_contents( $svg_path );
    
    if ( ! $svg_content ) {
        return '';
    }
    
    // Add default attributes
    $default_attrs = array(
        'width'  => '20',
        'height' => '20',
        'style'  => 'display:block;',
    );
    
    $attributes = wp_parse_args( $attributes, $default_attrs );
    
    // Add attributes to SVG tag
    $attr_string = '';
    foreach ( $attributes as $key => $value ) {
        $attr_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
    }
    
    $svg_content = preg_replace( '/<svg(\s+)/', '<svg$1' . $attr_string . ' ', $svg_content, 1 );
    
    return $svg_content;
}

/**
 * Generate carousel navigation HTML
 *
 * @param string $prev_text Previous button text
 * @param string $next_text Next button text
 * @return array
 */
function oa_get_carousel_nav_html( $prev_text = '', $next_text = '' ) {
    $prev_text = $prev_text ?: '<svg viewBox="0 0 24 40"><path d="M22 1 L2 20 L22 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    $next_text = $next_text ?: '<svg viewBox="0 0 24 40"><path d="M2 1 L22 20 L2 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    
    return array(
        'prev' => $prev_text,
        'next' => $next_text,
    );
}

/**
 * Get GeneratePress theme compatibility settings
 *
 * @return array
 */
function oa_get_generatepress_settings() {
    return array(
        'container_width' => get_option( 'generate_settings', array() ),
        'is_generatepress' => function_exists( 'generate_get_defaults' ),
    );
}





/**
 * Disable content area on slider pages
 */
function oa_disable_content_on_slider_pages() {
    // Safety check - ensure we're in the right context
    if ( ! function_exists( 'is_singular' ) ) {
        return;
    }
    
    // Only run on single slider pages
    if ( is_singular( 'oa_slider' ) ) {
        // Remove content from the main query
        add_filter( 'the_content', '__return_empty_string' );
        
        // Hide the content area via CSS
        add_action( 'wp_head', function() {
            echo '<style>
                .entry-content,
                .post-content,
                .content-area,
                .site-content,
                .main-content {
                    display: none !important;
                }
            </style>';
        });
        
        // Remove breadcrumbs if they exist
        add_filter( 'woocommerce_breadcrumb_defaults', '__return_false' );
        add_filter( 'generate_breadcrumb', '__return_false' );
        
        // Remove page title if it exists
        add_filter( 'generate_show_title', '__return_false' );
        add_filter( 'woocommerce_show_page_title', '__return_false' );
    }
}
add_action( 'wp', 'oa_disable_content_on_slider_pages' );

/**
 * Add custom body class for slider pages
 */
function oa_add_slider_body_class( $classes ) {
    // Safety check - ensure we're in the right context
    if ( ! function_exists( 'is_singular' ) ) {
        return $classes;
    }
    
    if ( is_singular( 'oa_slider' ) ) {
        $classes[] = 'oa-slider-page';
        $classes[] = 'no-content-area';
    }
    return $classes;
}
add_filter( 'body_class', 'oa_add_slider_body_class' );

/**
 * Auto-detect page background and apply logo colors
 */
function oa_auto_detect_logo_colors() {
    // Run on all frontend pages
    if ( ! is_admin() ) {
        add_action( 'wp_head', function() {
            ?>
            <style>
                /* Make SVG logos inherit the container's font color */
                .oa-logo-grid-svg,
                .oa-logo-svg {
                    color: inherit !important;
                }
                
                /* Ensure SVG elements inherit the color */
                .oa-logo-grid-svg svg,
                .oa-logo-svg svg {
                    fill: currentColor !important;
                    /* Removed stroke to prevent unwanted outlines */
                }
                
                /* Override any hardcoded colors in SVG */
                .oa-logo-grid-svg svg *,
                .oa-logo-svg svg * {
                    fill: currentColor !important;
                    /* Removed stroke to prevent unwanted outlines */
                }
            </style>
            <?php
        });
    }
}
add_action( 'wp', 'oa_auto_detect_logo_colors' );

// Add debug hook for development
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    // Debug functions removed to prevent errors
    // These functions were not defined and causing fatal errors
} 