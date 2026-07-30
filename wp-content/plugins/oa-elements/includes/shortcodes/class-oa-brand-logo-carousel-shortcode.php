<?php
/**
 * Brand Logo Carousel Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Brand Logo Carousel Shortcode Class
 */
class OA_Brand_Logo_Carousel_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_brand_logo_carousel' ) ) {
            add_shortcode( 'oa_brand_logo_carousel', array( $this, 'render_brand_logo_carousel' ) );
        }
    }
    
    /**
     * Render brand logo carousel shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_brand_logo_carousel( $atts ) {
        // Check WooCommerce dependency
        if ( ! oa_is_woocommerce_active() ) {
            return '<p>' . __( 'Brand logo carousel requires WooCommerce to be active.', 'open-agency-elements' ) . '</p>';
        }
        
        $defaults = array(
            'class' => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_brand_logo_carousel' );
        
        // Get brand logos
        $brands = $this->get_brand_logos();
        
        if ( empty( $brands ) ) {
            return '<p>' . __( 'No brand logos found.', 'open-agency-elements' ) . '</p>';
        }
        

        
        // Build carousel HTML
        return $this->build_carousel_html( $brands, $atts );
    }
    
    /**
     * Get brand logos from WooCommerce product brands
     *
     * @return array
     */
    private function get_brand_logos() {
        $brands = get_terms( array(
            'taxonomy'   => 'product_brand',
            'hide_empty' => false,
        ) );
        
        if ( is_wp_error( $brands ) || empty( $brands ) ) {
            return array();
        }
        
        $shop_url = wc_get_page_permalink( 'shop' );
        $items = array();
        
        foreach ( $brands as $brand ) {
            $thumb_id = get_term_meta( $brand->term_id, 'thumbnail_id', true );
            if ( ! $thumb_id ) {
                continue;
            }
            
            $image_url = wp_get_attachment_image_url( $thumb_id, 'medium' );
            if ( $image_url ) {
                $items[] = array(
                    'src'  => $image_url,
                    'link' => add_query_arg( 'filter_product_brand', $brand->slug, $shop_url ),
                    'alt'  => $brand->name,
                );
            }
        }
        
        return $items;
    }
    
    /**
     * Build carousel HTML
     *
     * @param array $brands Brand logos
     * @param array $atts Attributes
     * @return string
     */
    private function build_carousel_html( $brands, $atts ) {
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        ob_start();
        ?>
        <div class="owl-carousel oa-brand-carousel<?php echo esc_attr( $custom_class ); ?>">
            <?php foreach ( $brands as $brand ) : ?>
                <div class="oa-logo-item">
                    <a href="<?php echo esc_url( $brand['link'] ); ?>">
                        <img src="<?php echo esc_url( $brand['src'] ); ?>" alt="<?php echo esc_attr( $brand['alt'] ); ?>" class="oa-logo" />
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery( function( $ ) {
            var $carousel = $( '.oa-brand-carousel' );
            var interactionDelay = 3000; // 3 seconds delay after interaction
            var interactionTimer = null;
            
            // Initialize carousel
            $carousel.owlCarousel( {
                loop: true,
                autoplay: true,
                autoplayTimeout: 2000, // 2 seconds between slides (reduced from default 5s)
                autoplaySpeed: 800,    // 0.8 seconds transition speed
                smartSpeed: 600,      // 0.6 seconds smooth transition
                dots: false,
                nav: true,
                navText: [
                    '<svg viewBox="0 0 24 40"><path d="M22 1 L2 20 L22 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
                    '<svg viewBox="0 0 24 40"><path d="M2 1 L22 20 L2 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
                ],
                responsive: {
                    0:    { items: 1, margin: 0 },
                    600:  { items: 3, margin: 40 },
                    1200: { items: 5, margin: 80 }
                }
            } );
            
            // Add interaction delay functionality
            $carousel.on('mouseenter touchstart', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                $carousel.trigger('stop.owl.autoplay');
            });
            
            $carousel.on('mouseleave touchend', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    $carousel.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
            
            // Also pause on navigation clicks
            $carousel.on('click', '.owl-nav .owl-prev, .owl-nav .owl-next, .owl-dots .owl-dot', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                $carousel.trigger('stop.owl.autoplay');
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    $carousel.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
        } );
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get brand logo carousel examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_brand_logo_carousel]',
                'description' => __( 'Basic brand logo carousel', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_brand_logo_carousel class="custom-brands"]',
                'description' => __( 'Brand logo carousel with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 