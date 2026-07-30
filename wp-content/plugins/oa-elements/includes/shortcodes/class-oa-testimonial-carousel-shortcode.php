<?php
/**
 * Testimonial Carousel Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Testimonial Carousel Shortcode Class
 */
class OA_Testimonial_Carousel_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_testimonial_carousel' ) ) {
            add_shortcode( 'oa_testimonial_carousel', array( $this, 'render_testimonial_carousel' ) );
        }
    }
    
    /**
     * Render testimonial carousel shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_testimonial_carousel( $atts ) {
        $defaults = array(
            'category'   => '',
            'class'      => '',
            'alignment'  => '', // Global alignment override
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_testimonial_carousel' );
        
        // Get testimonial posts
        $testimonials = $this->get_testimonial_posts( $atts['category'] );
        
        if ( empty( $testimonials ) ) {
            return $this->get_no_testimonials_message( $atts['category'] );
        }
        

        
        // Build carousel HTML
        return $this->build_carousel_html( $testimonials, $atts );
    }
    
    /**
     * Get testimonial posts
     *
     * @param string $category Category slug
     * @return array
     */
    private function get_testimonial_posts( $category ) {
        $args = array(
            'post_type'      => 'testimonial',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'testimonial_category',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $category ),
                ),
            );
        }
        
        $query = new WP_Query( $args );
        
        return $query->posts;
    }
    
    /**
     * Get no testimonials message
     *
     * @param string $category Category slug
     * @return string
     */
    private function get_no_testimonials_message( $category ) {
        if ( ! empty( $category ) ) {
            return sprintf(
                '<p>%s</p>',
                sprintf(
                    __( 'No testimonials found in category "%s".', 'open-agency-elements' ),
                    esc_html( $category )
                )
            );
        }
        
        return '<p>' . __( 'No testimonials available.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Build carousel HTML
     *
     * @param array $testimonials Testimonial posts
     * @param array $atts Attributes
     * @return string
     */
    private function build_carousel_html( $testimonials, $atts ) {
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        ob_start();
        ?>
        <div class="oa-testimonial-carousel-wrap">
        <div class="owl-carousel oa-testimonial-carousel<?php echo esc_attr( $custom_class ); ?>">
            <?php foreach ( $testimonials as $testimonial ) : ?>
                <?php echo $this->render_testimonial_item( $testimonial, $atts['alignment'] ); ?>
            <?php endforeach; ?>
        </div>
        </div>
        
        <script>
        ( function() {
            var WRAP = '.oa-testimonial-carousel-wrap';
            var CAROUSEL = '.oa-testimonial-carousel';

            function initCarousel( el ) {
                if ( el.dataset.oaInit ) { return; }
                el.dataset.oaInit = '1';

                var $ = window.jQuery;
                var $carousel = $( el );
                var interactionDelay = 3000; // 3 seconds delay after interaction
                var interactionTimer = null;

                // Initialize carousel
                $carousel.owlCarousel( {
                    items: 1,
                    loop: true,
                    autoplay: true,
                    dots: false,
                    nav: true,
                    navText: [
                        '<svg viewBox="0 0 24 40"><path d="M22 1 L2 20 L22 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
                        '<svg viewBox="0 0 24 40"><path d="M2 1 L22 20 L2 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
                    ],
                    margin: 40,
                    stagePadding: 5,
                } );

                // Pause autoplay on interaction, resume after a delay.
                $carousel.on( 'mouseenter touchstart', function() {
                    if ( interactionTimer ) { clearTimeout( interactionTimer ); }
                    $carousel.trigger( 'stop.owl.autoplay' );
                } );

                $carousel.on( 'mouseleave touchend', function() {
                    if ( interactionTimer ) { clearTimeout( interactionTimer ); }
                    interactionTimer = setTimeout( function() {
                        $carousel.trigger( 'play.owl.autoplay' );
                    }, interactionDelay );
                } );

                $carousel.on( 'click', '.owl-nav .owl-prev, .owl-nav .owl-next, .owl-dots .owl-dot', function() {
                    if ( interactionTimer ) { clearTimeout( interactionTimer ); }
                    $carousel.trigger( 'stop.owl.autoplay' );
                    interactionTimer = setTimeout( function() {
                        $carousel.trigger( 'play.owl.autoplay' );
                    }, interactionDelay );
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
        return ob_get_clean();
    }
    
    /**
     * Render individual testimonial item
     *
     * @param WP_Post $testimonial Testimonial post object
     * @param string  $global_alignment Global alignment override
     * @return string
     */
    private function render_testimonial_item( $testimonial, $global_alignment = '' ) {
        // Get alignment from custom field, default to center
        $alignment = get_post_meta( $testimonial->ID, 'testimonial_alignment', true );
        if ( empty( $alignment ) ) {
            $alignment = 'center';
        }
        
        // Use global alignment override if provided
        if ( ! empty( $global_alignment ) && in_array( $global_alignment, array( 'left', 'center', 'right' ) ) ) {
            $alignment = $global_alignment;
        }
        
        // Get rating and company
        $rating = get_post_meta( $testimonial->ID, 'testimonial_rating', true );
        $company = get_post_meta( $testimonial->ID, 'testimonial_company', true );
        
        if ( empty( $rating ) ) {
            $rating = 5;
        }
        
        // Build alignment class
        $alignment_class = 'oa-testimonial-align-' . sanitize_html_class( $alignment );
        
        ob_start();
        ?>
        <div class="oa-testimonial-item <?php echo esc_attr( $alignment_class ); ?>">
            <div class="oa-testimonial-stars">
                <?php echo str_repeat( '<span class="star">&#9733;</span>', intval( $rating ) ); ?>
            </div>
            <blockquote class="oa-testimonial-text large-text">
                <?php echo wp_kses_post( $testimonial->post_content ); ?>
            </blockquote>
            <div class="oa-testimonial-author large-text">
                <?php echo esc_html( $testimonial->post_title ); ?>
                <?php if ( ! empty( $company ) ) : ?>
                    <span class="oa-testimonial-company"> - <?php echo esc_html( $company ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get testimonial carousel examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_testimonial_carousel]',
                'description' => __( 'Basic testimonial carousel with all testimonials', 'open-agency-elements' ),
            ),
            'category' => array(
                'shortcode' => '[oa_testimonial_carousel category="reviews"]',
                'description' => __( 'Testimonial carousel with specific category', 'open-agency-elements' ),
            ),
            'alignment' => array(
                'shortcode' => '[oa_testimonial_carousel alignment="left"]',
                'description' => __( 'Testimonial carousel with left alignment (overrides individual settings)', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_testimonial_carousel class="custom-testimonials"]',
                'description' => __( 'Testimonial carousel with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 