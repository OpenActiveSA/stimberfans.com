<?php
/**
 * Logo Carousel Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Logo Carousel Shortcode Class
 */
class OA_Logo_Carousel_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_logo_carousel' ) ) {
            add_shortcode( 'oa_logo_carousel', array( $this, 'render_logo_carousel' ) );
        }
    }
    
    /**
     * Render logo carousel shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_logo_carousel( $atts ) {
        $defaults = array(
            'category' => '',
            'class'    => '',
            'hide_subheading' => 'false',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_logo_carousel' );
        
        // Get logo posts
        $logos = $this->get_logo_posts( $atts['category'] );
        
        if ( empty( $logos ) ) {
            return $this->get_no_logos_message( $atts['category'] );
        }
        

        
        // Build carousel HTML
        return $this->build_carousel_html( $logos, $atts );
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
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'logo_category',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $category ),
                ),
            );
        }
        
        $query = new WP_Query( $args );
        
        return $query->posts;
    }
    
    /**
     * Get no logos message
     *
     * @param string $category Category slug
     * @return string
     */
    private function get_no_logos_message( $category ) {
        if ( ! empty( $category ) ) {
            return sprintf(
                '<p>%s</p>',
                sprintf(
                    __( 'No logos found in category "%s".', 'open-agency-elements' ),
                    esc_html( $category )
                )
            );
        }
        
        return '<p>' . __( 'No logos available.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Build carousel HTML
     *
     * @param array $logos Logo posts
     * @param array $atts Attributes
     * @return string
     */
    private function build_carousel_html( $logos, $atts ) {
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        ob_start();
        ?>
        <div class="owl-carousel oa-logo-carousel<?php echo esc_attr( $custom_class ); ?>">
            <?php foreach ( $logos as $logo ) : ?>
                <?php echo $this->render_logo_item( $logo, $atts ); ?>
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery( function( $ ) {
            var $carousel = $( '.oa-logo-carousel' );
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
     * Render individual logo item
     *
     * @param WP_Post $logo Logo post object
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_logo_item( $logo, $atts = array() ) {
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
            $image_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
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
        
        ob_start();
        ?>
        <div class="oa-logo-item">
            <?php if ( $logo_url ) : ?>
                <a href="<?php echo esc_url( $logo_url ); ?>" <?php echo ( $logo_target === 'blank' ) ? 'target="_blank" rel="noopener"' : ''; ?>>
            <?php endif; ?>
                
                <?php if ( $is_svg && ! empty( $logo_svg_code ) ) : ?>
                    <div class="oa-logo-svg"><?php echo $logo_svg_code; ?></div>
                <?php else : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="oa-logo" />
                <?php endif; ?>
                
            <?php if ( $logo_url ) : ?>
                </a>
            <?php endif; ?>
            
            <?php if ( ! empty( $logo_subheading ) && ( ! isset( $atts['hide_subheading'] ) || $atts['hide_subheading'] !== 'true' ) ) : ?>
                <h4 class="oa-logo-subheading"><?php echo esc_html( $logo_subheading ); ?></h4>
            <?php endif; ?>
            
            <?php if ( ! empty( $logo_button_text ) && ! empty( $logo_button_url ) ) : ?>
                <a href="<?php echo esc_url( $logo_button_url ); ?>" 
                   class="oa-logo-button button" 
                   <?php echo ( $logo_button_target === 'blank' ) ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html( $logo_button_text ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get logo carousel examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_logo_carousel]',
                'description' => __( 'Basic logo carousel with all logos', 'open-agency-elements' ),
            ),
            'category' => array(
                'shortcode' => '[oa_logo_carousel category="partners"]',
                'description' => __( 'Logo carousel with specific category', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_logo_carousel class="custom-logos"]',
                'description' => __( 'Logo carousel with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 