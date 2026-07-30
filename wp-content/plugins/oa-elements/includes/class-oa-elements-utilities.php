<?php
/**
 * Utilities class for Open Agency Elements
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Elements Utilities Class
 */
class OA_Elements_Utilities {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize utilities
    }
    
    /**
     * Sanitize HTML content
     *
     * @param string $content Content to sanitize
     * @param array  $allowed_tags Allowed HTML tags
     * @return string
     */
    public static function sanitize_html( $content, $allowed_tags = array() ) {
        if ( empty( $allowed_tags ) ) {
            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'target' => array(),
                    'rel' => array(),
                    'class' => array(),
                ),
                'br' => array(),
                'strong' => array(),
                'b' => array(),
                'em' => array(),
                'i' => array(),
                'img' => array(
                    'src' => array(),
                    'alt' => array(),
                    'title' => array(),
                    'class' => array(),
                    'style' => array(),
                    'width' => array(),
                    'height' => array(),
                ),
                'span' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'div' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'p' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h1' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h2' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h3' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h4' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h5' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'h6' => array(
                    'class' => array(),
                    'style' => array(),
                ),
            );
        }
        
        return wp_kses( $content, $allowed_tags );
    }
    
    /**
     * Get responsive breakpoints
     *
     * @return array
     */
    public static function get_responsive_breakpoints() {
        return array(
            'mobile'  => 0,
            'tablet'  => 768,
            'desktop' => 1024,
            'large'   => 1200,
        );
    }
    
    /**
     * Generate CSS classes for alignment
     *
     * @param string $horizontal Horizontal alignment
     * @param string $vertical Vertical alignment
     * @return string
     */
    public static function get_alignment_classes( $horizontal = 'center', $vertical = 'center' ) {
        $classes = array();
        
        // Horizontal alignment
        if ( in_array( $horizontal, array( 'left', 'center', 'right' ) ) ) {
            $classes[] = 'oa-align-' . $horizontal;
        }
        
        // Vertical alignment
        if ( in_array( $vertical, array( 'top', 'center', 'bottom' ) ) ) {
            $classes[] = 'oa-valign-' . $vertical;
        }
        
        return implode( ' ', $classes );
    }
    
    /**
     * Get color classes
     *
     * @param bool $is_white Whether text should be white
     * @return string
     */
    public static function get_color_class( $is_white = false ) {
        return $is_white ? 'oa-text-white' : 'oa-text-black';
    }
    
    /**
     * Generate inline styles
     *
     * @param array $styles Array of CSS properties
     * @return string
     */
    public static function generate_inline_styles( $styles ) {
        if ( empty( $styles ) || ! is_array( $styles ) ) {
            return '';
        }
        
        $css = array();
        foreach ( $styles as $property => $value ) {
            if ( ! empty( $value ) ) {
                $css[] = $property . ':' . $value;
            }
        }
        
        return ! empty( $css ) ? ' style="' . esc_attr( implode( ';', $css ) ) . '"' : '';
    }
    
    /**
     * Get carousel settings
     *
     * @param array $custom_settings Custom settings to override defaults
     * @return array
     */
    public static function get_carousel_settings( $custom_settings = array() ) {
        $default_settings = array(
            'loop'              => true,
            'autoplay'          => true,
            'autoplayTimeout'   => 5000,
            'autoplayHoverPause' => true,
            'dots'              => true,
            'nav'               => true,
            'margin'            => 0,
            'stagePadding'      => 0,
            'responsive'        => array(
                0 => array(
                    'items' => 1,
                    'margin' => 0,
                ),
                768 => array(
                    'items' => 2,
                    'margin' => 20,
                ),
                1024 => array(
                    'items' => 3,
                    'margin' => 30,
                ),
            ),
        );
        
        return wp_parse_args( $custom_settings, $default_settings );
    }
    
    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @return bool
     */
    public static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }
    
    /**
     * Get image dimensions
     *
     * @param int    $attachment_id Attachment ID
     * @param string $size Image size
     * @return array
     */
    public static function get_image_dimensions( $attachment_id, $size = 'full' ) {
        $image_data = wp_get_attachment_image_src( $attachment_id, $size );
        
        if ( ! $image_data ) {
            return array( 'width' => 0, 'height' => 0 );
        }
        
        return array(
            'width'  => $image_data[1],
            'height' => $image_data[2],
        );
    }
    
    /**
     * Get GeneratePress container class
     *
     * @return string
     */
    public static function get_generatepress_container_class() {
        if ( ! function_exists( 'generate_get_defaults' ) ) {
            return 'container';
        }
        
        $generate_settings = get_option( 'generate_settings', array() );
        $container_width = isset( $generate_settings['container_width'] ) ? $generate_settings['container_width'] : '1100';
        
        return 'container-' . $container_width;
    }
    
    /**
     * Check if current theme is GeneratePress
     *
     * @return bool
     */
    public static function is_generatepress_theme() {
        $theme = wp_get_theme();
        return strpos( strtolower( $theme->get( 'Name' ) ), 'generatepress' ) !== false;
    }
    
    /**
     * Get safe CSS class name
     *
     * @param string $string String to convert to CSS class
     * @return string
     */
    public static function get_safe_css_class( $string ) {
        // Remove special characters and replace spaces with hyphens
        $class = preg_replace( '/[^a-zA-Z0-9\s-]/', '', $string );
        $class = preg_replace( '/\s+/', '-', $class );
        $class = strtolower( trim( $class, '-' ) );
        
        return 'oa-' . $class;
    }
    
    /**
     * Minify CSS
     *
     * @param string $css CSS to minify
     * @return string
     */
    public static function minify_css( $css ) {
        // Remove comments
        $css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
        
        // Remove unnecessary whitespace
        $css = preg_replace( '/\s+/', ' ', $css );
        $css = preg_replace( '/;\s*/', ';', $css );
        $css = preg_replace( '/:\s*/', ':', $css );
        $css = preg_replace( '/\s*{\s*/', '{', $css );
        $css = preg_replace( '/\s*}\s*/', '}', $css );
        
        return trim( $css );
    }
    
    /**
     * Get plugin asset URL
     *
     * @param string $path Asset path
     * @return string
     */
    public static function get_asset_url( $path ) {
        return OA_ELEMENTS_PLUGIN_URL . 'assets/' . ltrim( $path, '/' );
    }
    
    /**
     * Get plugin asset path
     *
     * @param string $path Asset path
     * @return string
     */
    public static function get_asset_path( $path ) {
        return OA_ELEMENTS_PLUGIN_DIR . 'assets/' . ltrim( $path, '/' );
    }
    
    /**
     * Create feature category for product
     *
     * @param string $product_slug Product slug
     * @param string $category_name Category name (optional)
     * @return int|WP_Error Category ID or error
     */
    public static function create_product_feature_category( $product_slug, $category_name = '' ) {
        if ( empty( $category_name ) ) {
            $category_name = sprintf( __( 'Features for %s', 'open-agency-elements' ), ucfirst( str_replace( '-', ' ', $product_slug ) ) );
        }
        
        $category_slug = sanitize_title( $product_slug );
        
        // Check if category already exists
        $existing_term = get_term_by( 'slug', $category_slug, 'feature_category' );
        if ( $existing_term ) {
            return $existing_term->term_id;
        }
        
        // Create new category
        $result = wp_insert_term( $category_name, 'feature_category', array(
            'slug' => $category_slug,
            'description' => sprintf( __( 'Features specific to product: %s', 'open-agency-elements' ), $product_slug ),
        ) );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        return $result['term_id'];
    }
    
    /**
     * Get product feature categories
     *
     * @return array
     */
    public static function get_product_feature_categories() {
        $categories = get_terms( array(
            'taxonomy' => 'feature_category',
            'hide_empty' => false,
        ) );
        
        if ( is_wp_error( $categories ) ) {
            return array();
        }
        
        return $categories;
    }
} 