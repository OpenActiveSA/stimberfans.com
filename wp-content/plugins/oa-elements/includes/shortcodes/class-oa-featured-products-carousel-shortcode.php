<?php
/**
 * Featured Products Carousel Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Featured Products Carousel Shortcode Class
 */
class OA_Featured_Products_Carousel_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_featured_products_carousel' ) ) {
            add_shortcode( 'oa_featured_products_carousel', array( $this, 'render_featured_products_carousel' ) );
        }
    }
    
    /**
     * Render featured products carousel shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_featured_products_carousel( $atts ) {
        // Check WooCommerce dependency
        if ( ! oa_is_woocommerce_active() ) {
            return '<p>' . __( 'Featured products carousel requires WooCommerce to be active.', 'open-agency-elements' ) . '</p>';
        }
        
        $defaults = array(
            'columns' => 3,
            'class'   => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_featured_products_carousel' );
        
        // Get featured products
        $featured_products = $this->get_featured_products();
        
        if ( empty( $featured_products ) ) {
            return '<p>' . __( 'No featured products found.', 'open-agency-elements' ) . '</p>';
        }
        

        
        // Build carousel HTML
        return $this->build_carousel_html( $featured_products, $atts );
    }
    
    /**
     * Get featured products
     *
     * @return array
     */
    private function get_featured_products() {
        $featured_ids = wc_get_featured_product_ids();
        
        if ( empty( $featured_ids ) ) {
            return array();
        }
        
        $args = array(
            'post_type'      => 'product',
            'post__in'       => $featured_ids,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        $query = new WP_Query( $args );
        
        return $query->posts;
    }
    
    /**
     * Build carousel HTML
     *
     * @param array $products Product posts
     * @param array $atts Attributes
     * @return string
     */
    private function build_carousel_html( $products, $atts ) {
        $columns = max( 1, intval( $atts['columns'] ) );
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        ob_start();
        ?>
        <div class="oa-featured-carousel-wrap">
        <ul class="products columns-1 oa-featured-carousel owl-carousel<?php echo esc_attr( $custom_class ); ?>">
            <?php foreach ( $products as $product ) : ?>
                <?php
                global $post;
                $post = $product;
                setup_postdata( $post );
                wc_get_template_part( 'content', 'product' );
                ?>
            <?php endforeach; ?>
        </ul>
        </div>
        
        <script>
        ( function() {
            var WRAP = '.oa-featured-carousel-wrap';
            var CAROUSEL = '.oa-featured-carousel';

            function initCarousel( el ) {
                if ( el.dataset.oaInit ) { return; }
                el.dataset.oaInit = '1';
                var $ = window.jQuery;
                $( 'body' ).addClass( 'woocommerce' );
                $( el ).owlCarousel( {
                    loop: true,
                    margin: 40,
                    autoplay: false,
                    dots: false,
                    nav: true,
                    navText: [
                        '<svg viewBox="0 0 24 40"><path d="M22 1 L2 20 L22 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
                        '<svg viewBox="0 0 24 40"><path d="M2 1 L22 20 L2 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
                    ],
                    items: <?php echo $columns; ?>,
                    responsive: {
                        0:    { items: 1 },
                        600:  { items: 2 },
                        1200: { items: <?php echo $columns; ?> }
                    }
                } );
            }

            // Wait until jQuery + Owl are available (they may be delayed by the cache plugin).
            function whenReady( el ) {
                if ( window.jQuery && jQuery.fn && jQuery.fn.owlCarousel ) {
                    initCarousel( el );
                } else {
                    setTimeout( function() { whenReady( el ); }, 200 );
                }
            }

            // Init the carousel inside a wrapper (the carousel itself is display:none
            // pre-init, so we observe the always-visible wrapper instead).
            function activate( wrap ) {
                var el = wrap.querySelector( CAROUSEL );
                if ( el ) { whenReady( el ); }
            }

            function setup() {
                var wraps = document.querySelectorAll( WRAP );
                if ( ! wraps.length ) { return; }

                // Old browsers: just initialize immediately.
                if ( ! ( 'IntersectionObserver' in window ) ) {
                    Array.prototype.forEach.call( wraps, activate );
                    return;
                }

                var io = new IntersectionObserver( function( entries, obs ) {
                    entries.forEach( function( entry ) {
                        if ( entry.isIntersecting ) {
                            obs.unobserve( entry.target );
                            activate( entry.target );
                        }
                    } );
                }, { rootMargin: '200px 0px' } );

                Array.prototype.forEach.call( wraps, function( w ) { io.observe( w ); } );
            }

            if ( document.readyState === 'loading' ) {
                document.addEventListener( 'DOMContentLoaded', setup );
            } else {
                setup();
            }
        } )();
        </script>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    /**
     * Get featured products carousel examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_featured_products_carousel]',
                'description' => __( 'Basic featured products carousel', 'open-agency-elements' ),
            ),
            'columns' => array(
                'shortcode' => '[oa_featured_products_carousel columns="4"]',
                'description' => __( 'Featured products carousel with 4 columns', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_featured_products_carousel class="custom-products"]',
                'description' => __( 'Featured products carousel with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 