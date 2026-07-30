<?php
/**
 * FAQ Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA FAQ Shortcode Class
 */
class OA_FAQ_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_faq_shortcode' ) ) {
            add_shortcode( 'oa_faq', array( $this, 'render_faq' ) );
        }
    }
    
    /**
     * Render FAQ shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_faq( $atts ) {
        $defaults = array(
            'category'    => '',
            'class'       => '',
            'id'          => '',
            'auto_detect' => 'yes', // Auto-detect current product
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_faq' );
        
        // Auto-detect product category if enabled and no category specified
        if ( $atts['auto_detect'] === 'yes' && empty( $atts['category'] ) ) {
            $atts['category'] = $this->get_current_product_category();
        }
        
        // Get FAQs
        $faqs = $this->get_faqs( $atts );
        
        // Debug: Log if no FAQs found (only in debug mode)
        if ( empty( $faqs ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'OA FAQ Shortcode: No FAQs found for category: ' . $atts['category'] );
        }
        
        if ( empty( $faqs ) ) {
            // Return a debug message in debug mode
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                return '<!-- OA FAQ Debug: No FAQs found for category: ' . esc_html( $atts['category'] ) . ' -->';
            }
            return '';
        }
        
        // Build CSS classes
        $classes = $this->build_classes( $atts );
        
        // Build attributes
        $attributes = $this->build_attributes( $atts );
        
        // Generate FAQ HTML
        return $this->render_faq_html( $faqs, $atts, $classes, $attributes );
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
        
        return $product_slug;
    }
    
    /**
     * Get FAQs
     *
     * @param array $atts Shortcode attributes
     * @return array
     */
    private function get_faqs( $atts ) {
        $args = array(
            'post_type'      => 'oa_faq',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        // Add category filter if specified
        if ( ! empty( $atts['category'] ) ) {
            // Clean and validate category slugs
            $categories = array_map( 'trim', explode( ',', $atts['category'] ) );
            $categories = array_filter( $categories ); // Remove empty values
            
            if ( ! empty( $categories ) ) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'faq_category',
                        'field'    => 'slug',
                        'terms'    => $categories,
                        'operator' => 'IN',
                    ),
                );
            }
        }
        
        $query = new WP_Query( $args );
        
        // Debug: Log query details (only in debug mode)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'OA FAQ Query: ' . print_r( $args, true ) );
            error_log( 'OA FAQ Found posts: ' . $query->found_posts );
        }
        
        if ( ! $query->have_posts() ) {
            return array();
        }
        
        $faqs = array();
        while ( $query->have_posts() ) {
            $query->the_post();
            $faqs[] = $this->get_faq_data( get_the_ID() );
        }
        wp_reset_postdata();
        
        return $faqs;
    }
    
    /**
     * Get FAQ data
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_faq_data( $post_id ) {
        $content_source = get_post_meta( $post_id, '_oa_faq_content_source', true );
        $content = '';
        
        // Determine content source
        if ( $content_source === 'page' ) {
            $selected_page_id = get_post_meta( $post_id, '_oa_faq_selected_page', true );
            if ( ! empty( $selected_page_id ) ) {
                $page = get_post( $selected_page_id );
                if ( $page && $page->post_status === 'publish' ) {
                    $content = $page->post_content;
                }
            }
        }
        
        // Fallback to direct content if page content is empty or not set
        if ( empty( $content ) ) {
            $content = get_post_field( 'post_content', $post_id );
        }
        
        return array(
            'id'        => $post_id,
            'title'     => get_the_title( $post_id ),
            'content'   => $content,
            'expanded'  => get_post_meta( $post_id, '_oa_faq_expanded', true ),
            'order'     => get_post_field( 'menu_order', $post_id ),
            'content_source' => $content_source,
            'selected_page_id' => get_post_meta( $post_id, '_oa_faq_selected_page', true ),
        );
    }
    
    /**
     * Build CSS classes
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function build_classes( $atts ) {
        $classes = array( 'oa-faq-accordion' );
        
        if ( ! empty( $atts['class'] ) ) {
            $classes[] = $atts['class'];
        }
        
        return implode( ' ', $classes );
    }
    
    /**
     * Build attributes
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function build_attributes( $atts ) {
        $attributes = array();
        
        // Note: ID is now handled in render_faq_html method
        // This method is kept for backward compatibility and other attributes
        
        return implode( ' ', $attributes );
    }
    
    /**
     * Render FAQ HTML
     *
     * @param array  $faqs       FAQ data
     * @param array  $atts       Shortcode attributes
     * @param string $classes    CSS classes
     * @param string $attributes HTML attributes
     * @return string
     */
    private function render_faq_html( $faqs, $atts, $classes, $attributes ) {
        // Generate unique ID for this FAQ instance if not provided
        if ( empty( $atts['id'] ) ) {
            $atts['id'] = 'oa-faq-' . uniqid();
        }
        
        $output = '<div class="' . esc_attr( $classes ) . '" id="' . esc_attr( $atts['id'] ) . '" ' . $attributes . '>';
        
        // Debug: Add comment with FAQ count
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $output .= '<!-- OA FAQ Debug: Rendering ' . count( $faqs ) . ' FAQs for category: ' . esc_html( $atts['category'] ) . ' -->';
        }
        
        foreach ( $faqs as $faq ) {
            $output .= $this->render_faq_item( $faq );
        }
        
        $output .= '</div>';
        
        // Add JavaScript for accordion functionality
        $output .= $this->get_accordion_script();
        
        return $output;
    }
    
    /**
     * Render FAQ item
     *
     * @param array $faq FAQ data
     * @return string
     */
    private function render_faq_item( $faq ) {
        $is_expanded = $faq['expanded'] === '1';
        $panel_style = $is_expanded ? 'display: block;' : 'display: none;';
        $toggle_class = $is_expanded ? 'oa-faq-accordion-toggle active' : 'oa-faq-accordion-toggle';
        
        $output = '<div class="oa-faq-accordion-item" id="oa-faq-' . esc_attr( $faq['id'] ) . '">';
        $output .= '<button class="' . esc_attr( $toggle_class ) . '">' . esc_html( $faq['title'] ) . '</button>';
        $output .= '<div class="oa-faq-accordion-panel" style="' . esc_attr( $panel_style ) . '">';
        $output .= $this->process_content( $faq['content'] );
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Process content to handle Gutenberg blocks properly
     *
     * @param string $content Raw content
     * @return string Processed content
     */
    private function process_content( $content ) {
        // Remove Gutenberg block comments that might be wrapped in p tags
        $content = preg_replace( '/<p>\s*<!--\s*\/wp:([^>]+) -->\s*<\/p>/', '<!-- /wp:$1 -->', $content );
        $content = preg_replace( '/<p>\s*<!--\s*wp:([^>]+) -->\s*<\/p>/', '<!-- wp:$1 -->', $content );
        
        // Apply wpautop to the cleaned content
        $content = wpautop( $content );
        
        // Clean up any remaining issues with block comments
        $content = preg_replace( '/<p>\s*<!--\s*\/wp:([^>]+) -->\s*<\/p>/', '<!-- /wp:$1 -->', $content );
        $content = preg_replace( '/<p>\s*<!--\s*wp:([^>]+) -->\s*<\/p>/', '<!-- wp:$1 -->', $content );
        
        return $content;
    }
    
    /**
     * Get accordion JavaScript
     *
     * @return string
     */
    private function get_accordion_script() {
        static $script_added = false;
        
        // Only add the script once per page
        if ( $script_added ) {
            return '';
        }
        
        $script_added = true;
        
        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize FAQ accordions
            initializeFAQAccordions();
        });
        
        // Function to initialize FAQ accordions
        function initializeFAQAccordions() {
            // Use event delegation to handle all FAQ accordions on the page
            document.addEventListener("click", function(e) {
                if (e.target && e.target.classList.contains("oa-faq-accordion-toggle")) {
                    var btn = e.target;
                    if (btn.hasAttribute("disabled")) return;
                    
                    var panel = btn.nextElementSibling;
                    var accordion = btn.closest(".oa-faq-accordion");
                    var open = btn.classList.contains("active");
                    
                    // Close all other items in the same accordion
                    if (accordion) {
                        accordion.querySelectorAll(".oa-faq-accordion-toggle.active").forEach(function(b) {
                            if (b !== btn) {
                                b.classList.remove("active");
                                if (b.nextElementSibling) b.nextElementSibling.style.display = "none";
                            }
                        });
                    }
                    
                    // Toggle current item
                    if (!open) {
                        btn.classList.add("active");
                        panel.style.display = "block";
                    } else {
                        btn.classList.remove("active");
                        panel.style.display = "none";
                    }
                }
            });
            
            // Also handle clicks on any child elements within the toggle button
            document.addEventListener("click", function(e) {
                var toggleBtn = e.target.closest(".oa-faq-accordion-toggle");
                if (toggleBtn && !e.target.classList.contains("oa-faq-accordion-toggle")) {
                    if (toggleBtn.hasAttribute("disabled")) return;
                    
                    var panel = toggleBtn.nextElementSibling;
                    var accordion = toggleBtn.closest(".oa-faq-accordion");
                    var open = toggleBtn.classList.contains("active");
                    
                    // Close all other items in the same accordion
                    if (accordion) {
                        accordion.querySelectorAll(".oa-faq-accordion-toggle.active").forEach(function(b) {
                            if (b !== toggleBtn) {
                                b.classList.remove("active");
                                if (b.nextElementSibling) b.nextElementSibling.style.display = "none";
                            }
                        });
                    }
                    
                    // Toggle current item
                    if (!open) {
                        toggleBtn.classList.add("active");
                        panel.style.display = "block";
                    } else {
                        toggleBtn.classList.remove("active");
                        panel.style.display = "none";
                    }
                }
            });
        }
        </script>';
    }
    
    /**
     * Get shortcode examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            array(
                'title'   => __( 'Basic FAQ', 'open-agency-elements' ),
                'code'    => '[oa_faq]',
                'desc'    => __( 'Display all FAQs with auto-detection of current product category.', 'open-agency-elements' ),
            ),
            array(
                'title'   => __( 'Specific Category', 'open-agency-elements' ),
                'code'    => '[oa_faq category="general"]',
                'desc'    => __( 'Display FAQs from a specific category.', 'open-agency-elements' ),
            ),
            array(
                'title'   => __( 'Multiple Categories', 'open-agency-elements' ),
                'code'    => '[oa_faq category="general,product,shipping"]',
                'desc'    => __( 'Display FAQs from multiple categories.', 'open-agency-elements' ),
            ),
            array(
                'title'   => __( 'Custom Classes', 'open-agency-elements' ),
                'code'    => '[oa_faq class="custom-faq-style"]',
                'desc'    => __( 'Add custom CSS classes to the FAQ accordion.', 'open-agency-elements' ),
            ),
            array(
                'title'   => __( 'Disable Auto-Detection', 'open-agency-elements' ),
                'code'    => '[oa_faq auto_detect="no" category="general"]',
                'desc'    => __( 'Disable auto-detection and use a specific category.', 'open-agency-elements' ),
            ),
        );
    }
}

