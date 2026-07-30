<?php
/**
 * Features Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Features Shortcode Class
 */
class OA_Features_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_features_shortcode' ) ) {
            add_shortcode( 'oa_features', array( $this, 'render_features' ) );
        }
    }
    
    /**
     * Render features shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_features( $atts ) {
        $defaults = array(
            'align'     => 'left',
            'columns'   => '3',
            'category'  => '',
            'max_width' => '400px',
            'class'     => '',
            'id'        => '',
            'auto_detect' => 'yes', // Auto-detect current product
        );
        
        // Convert empty max_width value to 'auto'
        if ( empty( $atts['max_width'] ) ) {
            $atts['max_width'] = 'auto';
        }
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_features' );
        
        // Auto-detect product category if enabled and no category specified
        if ( $atts['auto_detect'] === 'yes' && empty( $atts['category'] ) ) {
            $atts['category'] = $this->get_current_product_category();
        }
        
        // Get features
        $features = $this->get_features( $atts );
        
        if ( empty( $features ) ) {
            return '';
        }
        
        // Build CSS classes
        $classes = $this->build_classes( $atts );
        
        // Build attributes
        $attributes = $this->build_attributes( $atts );
        
        // Generate features HTML
        return $this->render_features_html( $features, $atts, $classes, $attributes );
    }
    
    /**
     * Get current product category for auto-detection
     *
     * @return string
     */
    private function get_current_product_category() {
        $product_slug = '';
        
        // Try to get current product ID/slug
        if ( is_singular() ) {
            $post_id = get_the_ID();
            $post_type = get_post_type( $post_id );
            
            // Check if it's a WooCommerce product
            if ( $post_type === 'product' ) {
                $product_slug = get_post_field( 'post_name', $post_id );
            }
            // Check if it's a custom product post type
            elseif ( in_array( $post_type, array( 'product', 'page', 'post' ) ) ) {
                $product_slug = get_post_field( 'post_name', $post_id );
            }
        }
        
        // Try to get from WooCommerce global product
        if ( empty( $product_slug ) && function_exists( 'wc_get_product' ) ) {
            global $product;
            if ( $product && is_object( $product ) ) {
                $product_slug = $product->get_slug();
            }
        }
        
        // Try to get from query vars
        if ( empty( $product_slug ) && is_product() ) {
            $product_slug = get_query_var( 'product' );
        }
        
        // Try to get from URL
        if ( empty( $product_slug ) ) {
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            if ( preg_match( '/\/product\/([^\/]+)/', $current_url, $matches ) ) {
                $product_slug = $matches[1];
            }
        }
        
        // Clean the slug
        if ( ! empty( $product_slug ) ) {
            $product_slug = sanitize_title( $product_slug );
        }
        
        return $product_slug;
    }
    
    /**
     * Get features
     *
     * @param array $atts Attributes
     * @return array
     */
    private function get_features( $atts ) {
        $features = array();
        
        // If category is specified, try to get features for that category
        if ( ! empty( $atts['category'] ) ) {
            $features = $this->get_features_by_category( $atts['category'] );
        }
        
        // If no features found and auto-detect is enabled, try fallback categories
        if ( empty( $features ) && $atts['auto_detect'] === 'yes' ) {
            $features = $this->get_features_with_fallback();
        }
        
        return $features;
    }
    
    /**
     * Get features by category
     *
     * @param string $category Category slug(s)
     * @return array
     */
    private function get_features_by_category( $category ) {
        $args = array(
            'post_type'      => 'oa_feature',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        // Filter by category
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'feature_category',
                'field'    => 'slug',
                'terms'    => explode( ',', $category ),
            ),
        );
        
        $query = new WP_Query( $args );
        $features = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $features[] = $this->get_feature_data( get_the_ID() );
            }
        }
        
        wp_reset_postdata();
        
        return $features;
    }
    
    /**
     * Get features with fallback logic
     *
     * @return array
     */
    private function get_features_with_fallback() {
        $product_slug = $this->get_current_product_category();
        
        if ( empty( $product_slug ) ) {
            return array();
        }
        
        // Try different category naming patterns
        $category_patterns = array(
            $product_slug,                           // exact product slug
            'product-' . $product_slug,              // product-{slug}
            'features-' . $product_slug,             // features-{slug}
            'default-features',                      // fallback to default
        );
        
        foreach ( $category_patterns as $category ) {
            $features = $this->get_features_by_category( $category );
            if ( ! empty( $features ) ) {
                return $features;
            }
        }
        
        return array();
    }
    
    /**
     * Get feature data
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_feature_data( $post_id ) {
        $icon_type = get_post_meta( $post_id, '_oa_feature_icon_type', true );
        $icon_svg = get_post_meta( $post_id, '_oa_feature_icon_svg', true );
        $icon_image = get_post_meta( $post_id, '_oa_feature_icon_image', true );
        
        return array(
            'id'          => $post_id,
            'title'       => get_the_title( $post_id ),
            'content'     => get_the_content( $post_id ),
            'icon_type'   => $icon_type,
            'icon_svg'    => $icon_svg,
            'icon_image'  => $icon_image,
        );
    }
    
    /**
     * Build CSS classes
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_classes( $atts ) {
        $classes = array(
            'oa-features',
            'oa-features-align-' . sanitize_html_class( $atts['align'] ),
            'oa-features-columns-' . intval( $atts['columns'] ),
        );
        
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
     * Render features HTML
     *
     * @param array  $features   Features data
     * @param array  $atts       Attributes
     * @param string $classes    CSS classes
     * @param string $attributes HTML attributes
     * @return string
     */
    private function render_features_html( $features, $atts, $classes, $attributes ) {
        $max_width = esc_attr( $atts['max_width'] );
        $align = esc_attr( $atts['align'] );
        $columns = intval( $atts['columns'] );
        $feature_count = count( $features );
        
        // Build flexbox styles based on alignment
        $flex_styles = $this->build_flexbox_styles( $atts );
        
        $html = '<div class="' . esc_attr( $classes ) . '" ' . $attributes . '>';
        $html .= '<div class="oa-features-grid" style="' . $flex_styles . '; --oa-feature-max-width: ' . $max_width . '; --oa-feature-count: ' . $feature_count . ';">';
        
        foreach ( $features as $feature ) {
            $html .= $this->render_feature_item( $feature );
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Build flexbox styles based on shortcode attributes
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_flexbox_styles( $atts ) {
        $align = esc_attr( $atts['align'] );
        $columns = intval( $atts['columns'] );
        $max_width = esc_attr( $atts['max_width'] );
        
        $styles = array(
            'display: flex',
            'flex-wrap: wrap',
            'gap: 2rem',
            'align-items: start',
        );
        
        // Set alignment
        switch ( $align ) {
            case 'center':
                $styles[] = 'justify-content: center';
                break;
            case 'right':
                $styles[] = 'justify-content: flex-end';
                break;
            case 'left':
            default:
                $styles[] = 'justify-content: flex-start';
                break;
        }
        
        // Add column-based flex properties
        $styles[] = '--oa-feature-columns: ' . $columns;
        
        return implode( '; ', $styles ) . ';';
    }
    
    /**
     * Render feature item
     *
     * @param array $feature Feature data
     * @return string
     */
    private function render_feature_item( $feature ) {
        $html = '<div class="oa-feature-item">';
        
        // Icon
        if ( ! empty( $feature['icon_type'] ) ) {
            $html .= '<div class="oa-feature-icon">';
            
            if ( $feature['icon_type'] === 'svg' && ! empty( $feature['icon_svg'] ) ) {
                $html .= $this->render_svg_icon( $feature['icon_svg'] );
            } elseif ( $feature['icon_type'] === 'image' && ! empty( $feature['icon_image'] ) ) {
                $html .= $this->render_image_icon( $feature['icon_image'] );
            }
            
            $html .= '</div>';
        }
        
        // Content
        $html .= '<div class="oa-feature-content">';
        
        // Title
        if ( ! empty( $feature['title'] ) ) {
            $html .= '<h4 class="oa-feature-title">' . esc_html( $feature['title'] ) . '</h4>';
        }
        
        // Content
        if ( ! empty( $feature['content'] ) ) {
            $html .= '<div class="oa-feature-description">' . wp_kses_post( $feature['content'] ) . '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render SVG icon
     *
     * @param string $svg_code SVG code
     * @return string
     */
    private function render_svg_icon( $svg_code ) {
        // Clean and optimize SVG
        $svg_code = $this->optimize_svg( $svg_code );
        
        return '<div class="oa-feature-svg-icon">' . $svg_code . '</div>';
    }
    
    /**
     * Render image icon
     *
     * @param string $image_url Image URL
     * @return string
     */
    private function render_image_icon( $image_url ) {
        return '<img src="' . esc_url( $image_url ) . '" alt="" class="oa-feature-image-icon" />';
    }
    
    /**
     * Optimize SVG
     *
     * @param string $svg_code SVG code
     * @return string
     */
    private function optimize_svg( $svg_code ) {
        // Remove any script tags for security
        $svg_code = preg_replace( '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $svg_code );
        
        // Remove any onclick attributes
        $svg_code = preg_replace( '/onclick\s*=\s*["\'][^"\']*["\']/i', '', $svg_code );
        
        // Remove any onload attributes
        $svg_code = preg_replace( '/onload\s*=\s*["\'][^"\']*["\']/i', '', $svg_code );
        
        // Remove any external references
        $svg_code = preg_replace( '/xlink:href\s*=\s*["\'][^"\']*["\']/i', '', $svg_code );
        
        // Add default styling if no style attribute
        if ( ! preg_match( '/style\s*=/i', $svg_code ) ) {
            $svg_code = preg_replace( '/<svg/i', '<svg style="width: 100%; height: auto; fill: currentColor;"', $svg_code );
        }
        
        return $svg_code;
    }
    
    /**
     * Get shortcode examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'Basic usage (auto-detect post)' => '[oa_features]',
            'With specific category' => '[oa_features category="product-highlights"]',
            'Disable auto-detection' => '[oa_features auto_detect="no" category="default-features"]',
            'Center alignment' => '[oa_features align="center"]',
            'Right alignment' => '[oa_features align="right"]',
            '2 columns layout' => '[oa_features columns="2"]',
            '4 columns layout' => '[oa_features columns="4"]',
            'With maximum width' => '[oa_features columns="4" max_width="300px"]',
            'Auto sizing' => '[oa_features max_width=""]',
            'Multiple categories' => '[oa_features category="product-highlights,quality-promise"]',
            'Custom styling' => '[oa_features align="center" columns="3" max_width="400px"]',
        );
    }
} 