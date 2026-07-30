<?php
/**
 * Logo Grid Shortcode
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Logo Grid Shortcode Class
 */
class OA_Logo_Grid_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Temporarily remove feature check to test functionality
        add_shortcode( 'oa_logo_grid', array( $this, 'render_logo_grid' ) );
        
        // Debug: Log that shortcode is registered

    }
    
    /**
     * Render logo grid
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_logo_grid( $atts ) {
        // Debug: Log that shortcode is being called

        
        // Parse attributes
        $atts = shortcode_atts( array(
            'category'     => '',
            'column_width' => '3',
            'gap'          => '20',
            'alignment'    => 'center',
            'class'        => '',
        ), $atts, 'oa_logo_grid' );
        
        // Sanitize attributes
        $category = sanitize_text_field( $atts['category'] );
        $column_width = absint( $atts['column_width'] );
        $gap = absint( $atts['gap'] );
        $alignment = sanitize_text_field( $atts['alignment'] );
        $class = sanitize_text_field( $atts['class'] );
        
        // Validate column width (1-6 columns)
        if ( $column_width < 1 || $column_width > 6 ) {
            $column_width = 3;
        }
        
        // Get logos
        $logos = $this->get_logo_posts( $category );
        
        // Debug: Log number of logos found

        
        if ( empty( $logos ) ) {
            return $this->get_no_logos_message( $category );
        }
        
        // Build grid HTML
        return $this->build_grid_html( $logos, $atts );
    }
    
    /**
     * Get logo posts
     *
     * @param string $category Category slug
     * @return array
     */
    private function get_logo_posts( $category ) {
        $args = array(
            'post_type'      => 'logo',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        // Add category filter if specified
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'logo_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }
        
        // Debug: Log the query arguments
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'OA Elements - Logo query args: ' . print_r( $args, true ) );
        }
        
        $logos = get_posts( $args );
        

        
        return $logos;
    }
    
    /**
     * Get no logos message
     *
     * @param string $category Category slug
     * @return string
     */
    private function get_no_logos_message( $category ) {
        $message = __( 'No logos found.', 'open-agency-elements' );
        
        if ( ! empty( $category ) ) {
            $message = sprintf(
                __( 'No logos found in category "%s".', 'open-agency-elements' ),
                esc_html( $category )
            );
        }
        
        return '<div class="oa-logo-grid-empty">' . esc_html( $message ) . '</div>';
    }
    
    /**
     * Build grid HTML
     *
     * @param array $logos Logo posts
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function build_grid_html( $logos, $atts ) {
        $column_width = absint( $atts['column_width'] );
        $gap = absint( $atts['gap'] );
        $alignment = sanitize_text_field( $atts['alignment'] );
        $class = sanitize_text_field( $atts['class'] );
        
        // Calculate responsive breakpoints
        $mobile_cols = min( $column_width, 2 );
        $tablet_cols = min( $column_width, 3 );
        $desktop_cols = $column_width;
        
        // Build CSS classes
        $grid_classes = array(
            'oa-logo-grid',
            'oa-logo-grid--cols-' . $desktop_cols,
            'oa-logo-grid--gap-' . $gap,
        );
        
        if ( ! empty( $alignment ) ) {
            $grid_classes[] = 'oa-logo-grid--align-' . $alignment;
        }
        
        if ( ! empty( $class ) ) {
            $grid_classes[] = $class;
        }
        
        $grid_classes = array_filter( $grid_classes );
        
        // Build inline styles
        $grid_styles = array(
            'display: grid',
            'grid-template-columns: repeat(' . $desktop_cols . ', 1fr)',
            'gap: ' . $gap . 'px',
        );
        
        if ( ! empty( $alignment ) ) {
            $grid_styles[] = 'justify-items: ' . $alignment;
        }
        
        $grid_style = implode( '; ', $grid_styles );
        
        // Build responsive CSS
        $responsive_css = "
            @media (max-width: 768px) {
                .oa-logo-grid--cols-{$desktop_cols} {
                    grid-template-columns: repeat({$tablet_cols}, 1fr);
                }
            }
            @media (max-width: 480px) {
                .oa-logo-grid--cols-{$desktop_cols} {
                    grid-template-columns: repeat({$mobile_cols}, 1fr);
                }
            }
        ";
        
        // Add responsive CSS to head
        wp_add_inline_style( 'oa-elements-styles', $responsive_css );
        
        // Build HTML
        $html = '<div class="' . esc_attr( implode( ' ', $grid_classes ) ) . '" style="' . esc_attr( $grid_style ) . '">';
        
        foreach ( $logos as $logo ) {
            $html .= $this->render_logo_item( $logo );
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render logo item
     *
     * @param WP_Post $logo Logo post
     * @return string
     */
    private function render_logo_item( $logo ) {
        // Get custom fields
        $logo_url = get_post_meta( $logo->ID, 'logo_url', true );
        $logo_target = get_post_meta( $logo->ID, 'logo_target', true );
        $logo_svg_code = get_post_meta( $logo->ID, 'logo_svg_code', true );
        $logo_subheading = get_post_meta( $logo->ID, 'logo_subheading', true );
        $logo_button_text = get_post_meta( $logo->ID, 'logo_button_text', true );
        $logo_button_url = get_post_meta( $logo->ID, 'logo_button_url', true );
        $logo_button_target = get_post_meta( $logo->ID, 'logo_button_target', true );
        
        $image_url = '';
        $image_alt = $logo->post_title;
        $is_svg = false;
        
        // Get featured image
        $thumbnail_id = get_post_thumbnail_id( $logo->ID );
        if ( $thumbnail_id ) {
            $image_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
            $image_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
            if ( empty( $image_alt ) ) {
                $image_alt = $logo->post_title;
            }
            
            // Check if it's an SVG file
            $file_path = get_attached_file( $thumbnail_id );
            if ( $file_path && pathinfo( $file_path, PATHINFO_EXTENSION ) === 'svg' ) {
                $is_svg = true;
            }
        }
        
        if ( empty( $image_url ) ) {
            return '';
        }
        
        $html = '<div class="oa-logo-grid-item">';
        
        // If a URL exists, wrap image and subheading together so both are clickable.
        // Prefer logo_url; if empty, fall back to legacy button URL.
        $primary_url = ! empty( $logo_url ) ? $logo_url : $logo_button_url;
        $primary_target_value = ! empty( $logo_url ) ? $logo_target : $logo_button_target;

        $link_open  = '';
        $link_close = '';
        if ( ! empty( $primary_url ) ) {
            $target    = ( $primary_target_value === 'blank' ) ? ' target="_blank" rel="noopener"' : '';
            $link_open = '<a href="' . esc_url( $primary_url ) . '"' . $target . '>';
            $link_close = '</a>';
        }
        
        $html .= $link_open;
        
        if ( $is_svg && ! empty( $logo_svg_code ) ) {
            // Render SVG code directly
            $html .= '<div class="oa-logo-grid-svg">' . $logo_svg_code . '</div>';
        } else {
            // Render regular image
            $html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_alt ) . '" class="oa-logo-grid-image" />';
        }
        
        if ( ! empty( $logo_subheading ) ) {
            $html .= '<h4 class="oa-logo-subheading">' . esc_html( $logo_subheading ) . '</h4>';
        }
        
        $html .= $link_close;
        
        // Remove legacy CTA button: logos and text are now the link
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get shortcode examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'title' => __( 'Basic Grid', 'open-agency-elements' ),
                'shortcode' => '[oa_logo_grid]',
                'description' => __( 'Display all logos in a 3-column grid', 'open-agency-elements' ),
            ),
            'category' => array(
                'title' => __( 'Category Grid', 'open-agency-elements' ),
                'shortcode' => '[oa_logo_grid category="partners"]',
                'description' => __( 'Display logos from specific category', 'open-agency-elements' ),
            ),
            'columns' => array(
                'title' => __( 'Custom Columns', 'open-agency-elements' ),
                'shortcode' => '[oa_logo_grid column_width="4" gap="30"]',
                'description' => __( '4-column grid with 30px gap', 'open-agency-elements' ),
            ),
            'alignment' => array(
                'title' => __( 'Alignment', 'open-agency-elements' ),
                'shortcode' => '[oa_logo_grid alignment="left" column_width="2"]',
                'description' => __( '2-column grid with left alignment', 'open-agency-elements' ),
            ),
        );
    }
} 