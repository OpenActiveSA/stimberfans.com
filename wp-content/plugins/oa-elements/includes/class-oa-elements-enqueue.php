<?php
/**
 * Enqueue class for Open Agency Elements
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Elements Enqueue Class
 */
class OA_Elements_Enqueue {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize enqueue
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Enqueue main styles
        wp_enqueue_style(
            'oa-elements-styles',
            OA_ELEMENTS_PLUGIN_URL . 'assets/css/open-agency-elements.css',
            array(),
            OA_ELEMENTS_VERSION
        );
        
        // Enqueue smooth scroll if enabled
        if ( oa_is_feature_enabled( 'oa_enable_smooth_scroll' ) ) {
            self::enqueue_smooth_scroll();
        }
        
        // Enqueue mini cart script if needed
        if ( oa_is_woocommerce_active() && oa_is_feature_enabled( 'oa_links_show_mini_cart' ) ) {
            self::enqueue_mini_cart_script();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets() {
        $screen = get_current_screen();
        
        // Only enqueue on our settings page
        if ( $screen && $screen->id === 'settings_page_oa_elements' ) {
            wp_enqueue_style(
                'oa-elements-admin',
                OA_ELEMENTS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                OA_ELEMENTS_VERSION
            );
            
            wp_enqueue_script(
                'oa-elements-admin',
                OA_ELEMENTS_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                OA_ELEMENTS_VERSION,
                true
            );
        }
    }
    
    /**
     * Enqueue Owl Carousel
     */
    public static function enqueue_owl_carousel() {
        static $enqueued = false;
        
        if ( $enqueued ) {
            return;
        }
        
        wp_enqueue_script(
            'oa-owl-carousel',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
            array( 'jquery' ),
            '2.3.4',
            true
        );
        
        wp_enqueue_style(
            'oa-owl-carousel-style',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',
            array(),
            '2.3.4'
        );
        
        wp_enqueue_style(
            'oa-owl-carousel-theme',
            'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css',
            array(),
            '2.3.4'
        );
        
        $enqueued = true;
    }
    
    /**
     * Enqueue smooth scroll script
     */
    private static function enqueue_smooth_scroll() {
        wp_register_script(
            'oa-smooth-scroll',
            '',
            array( 'jquery' ),
            OA_ELEMENTS_VERSION,
            true
        );
        
        wp_enqueue_script( 'oa-smooth-scroll' );
        
        wp_add_inline_script(
            'oa-smooth-scroll',
            self::get_smooth_scroll_script()
        );
    }
    
    /**
     * Enqueue mini cart script
     */
    private static function enqueue_mini_cart_script() {
        wp_add_inline_script(
            'jquery',
            self::get_mini_cart_script()
        );
    }
    
    /**
     * Get smooth scroll script
     *
     * @return string
     */
    private static function get_smooth_scroll_script() {
        return "
        jQuery(function($) {
            $(document).on('click', 'a[href*=\"#\"]:not([href=\"#\"])', function(e) {
                var link = this;
                var hash = link.hash;
                var linkPath = link.pathname.replace(/^\\//, '');
                var pagePath = location.pathname.replace(/^\\//, '');
                
                // Only intercept if the link is for the current page
                if (hash && hash.length > 1 && linkPath === pagePath) {
                    var target = $(hash);
                    if (target.length) {
                        e.preventDefault();
                        $('html, body').animate({
                            scrollTop: target.offset().top - 100
                        }, 600);
                        
                        if (history.pushState) {
                            history.pushState(null, null, hash);
                        } else {
                            location.hash = hash;
                        }
                    }
                }
            });
        });
        ";
    }
    
    /**
     * Get mini cart script
     *
     * @return string
     */
    private static function get_mini_cart_script() {
        return "
        jQuery(document).on('click', '.oa-mini-cart-btn', function(e) {
            e.preventDefault();
            var btn = document.querySelector('.wc-block-mini-cart__button');
            if (btn) {
                btn.click();
            }
        });
        ";
    }
    
    /**
     * Enqueue carousel script for specific shortcode
     *
     * @param string $selector Carousel selector
     * @param array  $settings Carousel settings
     */
    public static function enqueue_carousel_script( $selector, $settings = array() ) {
        // Enqueue Owl Carousel if not already enqueued
        self::enqueue_owl_carousel();
        
        // Generate unique script handle
        $handle = 'oa-carousel-' . sanitize_title( $selector );
        
        wp_register_script(
            $handle,
            '',
            array( 'jquery', 'oa-owl-carousel' ),
            OA_ELEMENTS_VERSION,
            true
        );
        
        wp_enqueue_script( $handle );
        
        // Add inline script
        wp_add_inline_script(
            $handle,
            self::get_carousel_script( $selector, $settings )
        );
    }
    
    /**
     * Get carousel initialization script
     *
     * @param string $selector Carousel selector
     * @param array  $settings Carousel settings
     * @return string
     */
    private static function get_carousel_script( $selector, $settings = array() ) {
        $default_settings = OA_Elements_Utilities::get_carousel_settings();
        $settings = wp_parse_args( $settings, $default_settings );
        
        return "
        jQuery(function($) {
            var \$carousel = $('" . esc_js( $selector ) . "');
            var interactionDelay = 3000; // 3 seconds delay after interaction
            var interactionTimer = null;
            
            // Initialize carousel
            \$carousel.owlCarousel(" . wp_json_encode( $settings ) . ");
            
            // Override image widths after carousel initialization
            \$carousel.on('initialized.owl.carousel', function() {
                setTimeout(function() {
                    $('.oa-slide-inner img[width]').each(function() {
                        var width = $(this).attr('width');
                        if (width) {
                            $(this).css({
                                'width': width + 'px !important',
                                'max-width': width + 'px !important'
                            });
                        }
                    });
                }, 100);
            });
            
            // Add interaction delay functionality
            \$carousel.on('mouseenter touchstart', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                \$carousel.trigger('stop.owl.autoplay');
            });
            
            \$carousel.on('mouseleave touchend', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    \$carousel.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
            
            // Also pause on navigation clicks
            \$carousel.on('click', '.owl-nav .owl-prev, .owl-nav .owl-next, .owl-dots .owl-dot', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                \$carousel.trigger('stop.owl.autoplay');
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    \$carousel.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
        });
        ";
    }
    
    /**
     * Enqueue conditional assets based on enabled features
     */
    public static function enqueue_conditional_assets() {
        // Check if any carousel features are enabled
        $carousel_features = array(
            'oa_enable_slider_shortcode',
            'oa_enable_logo_carousel',
            'oa_enable_brand_logo_carousel',
            'oa_enable_testimonial_carousel',
            'oa_enable_featured_products_carousel',
        );
        
        foreach ( $carousel_features as $feature ) {
            if ( oa_is_feature_enabled( $feature ) ) {
                self::enqueue_owl_carousel();
                break; // Only enqueue once
            }
        }
    }
    
    /**
     * Dequeue unnecessary assets
     */
    public static function dequeue_unnecessary_assets() {
        // Remove unnecessary styles/scripts if needed
        if ( ! oa_is_feature_enabled( 'oa_enable_smooth_scroll' ) ) {
            wp_dequeue_script( 'oa-smooth-scroll' );
        }
    }
    
    /**
     * Add preload hints for critical assets
     */
    public static function add_preload_hints() {
        if ( oa_is_feature_enabled( 'oa_enable_slider_shortcode' ) || 
             oa_is_feature_enabled( 'oa_enable_logo_carousel' ) ||
             oa_is_feature_enabled( 'oa_enable_logo_grid' ) ) {
            
            add_action( 'wp_head', function() {
                echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" as="script">';
                echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" as="style">';
            });
        }
    }
    
    /**
     * Get image width override script
     *
     * @return string
     */
    private static function get_image_width_override_script() {
        return "
        // Override Owl Carousel image width styles
        function overrideOwlImageWidths() {
            jQuery('.oa-slide-inner img[width]').each(function() {
                var width = jQuery(this).attr('width');
                if (width) {
                    jQuery(this).css({
                        'width': width + 'px !important',
                        'max-width': width + 'px !important'
                    });
                }
            });
        }
        
        // Run on document ready
        jQuery(document).ready(function() {
            overrideOwlImageWidths();
        });
        
        // Run after Owl Carousel initialization
        jQuery(document).on('initialized.owl.carousel', function() {
            setTimeout(overrideOwlImageWidths, 100);
        });
        ";
    }
} 