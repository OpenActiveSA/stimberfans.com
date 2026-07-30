<?php
/**
 * Slider Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Slider Shortcode Class
 */
class OA_Slider_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_slider_shortcode' ) ) {
            add_shortcode( 'oa_slider', array( $this, 'render_slider' ) );
        }
    }
    
    /**
     * Render slider shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_slider( $atts ) {
        $defaults = array(
            'category' => '',
            'height'   => '100',
            'class'    => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_slider' );
        
        // Get slider posts
        $sliders = $this->get_slider_posts( $atts['category'] );
        
        if ( empty( $sliders ) ) {
            return $this->get_no_sliders_message( $atts['category'] );
        }
        

        
        // Build slider HTML
        return $this->build_slider_html( $sliders, $atts );
    }
    
    /**
     * Get slider posts
     *
     * @param string $category Category slug
     * @return array
     */
    private function get_slider_posts( $category ) {
        $args = array(
            'post_type'      => 'oa_slider',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );
        
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'slider_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $category ),
                ),
            );
        }
        
        $query = new WP_Query( $args );
        
        return $query->posts;
    }
    
    /**
     * Get no sliders message
     *
     * @param string $category Category slug
     * @return string
     */
    private function get_no_sliders_message( $category ) {
        if ( ! empty( $category ) ) {
            return sprintf(
                '<p>%s</p>',
                sprintf(
                    __( 'No sliders found in category "%s".', 'open-agency-elements' ),
                    esc_html( $category )
                )
            );
        }
        
        return '<p>' . __( 'No sliders available.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Build slider HTML
     *
     * @param array $sliders Slider posts
     * @param array $atts Attributes
     * @return string
     */
    private function build_slider_html( $sliders, $atts ) {
        $height_class = 'oa-slider-height-' . intval( $atts['height'] );
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        // Get the text color from the first slide for initial styling
        $initial_text_color = 'oa-text-black'; // default
        if ( ! empty( $sliders ) ) {
            $first_slider = $sliders[0];
            $text_colour = get_post_meta( $first_slider->ID, 'slider_text_colour', true );
            $initial_text_color = ( $text_colour === 'white' ) ? 'oa-text-white' : 'oa-text-black';
        }
        
        ob_start();
        ?>
        <div class="oa-slider owl-carousel inset-arrows <?php echo esc_attr( $height_class . $custom_class . ' ' . $initial_text_color ); ?>">
            <?php foreach ( $sliders as $slider ) : ?>
                <?php echo $this->render_slide( $slider ); ?>
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery( function( $ ) {
            var $slider = $( '.oa-slider' );
            var interactionDelay = 3000; // 3 seconds delay after interaction
            var interactionTimer = null;
            
            // Initialize carousel
            $slider.owlCarousel( {
                items: 1,
                loop: false,
                dots: true,
                autoplay: true,
                autoplayHoverPause: false, // Disable built-in hover pause to use our custom delay
                navText: [
                    '<svg viewBox="0 0 24 40"><path d="M22 1 L2 20 L22 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
                    '<svg viewBox="0 0 24 40"><path d="M2 1 L22 20 L2 39" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
                ],
            } ).on( 'initialized.owl.carousel changed.owl.carousel', function( e ) {
                // Get the current active slide
                var $activeSlide = $( e.target ).find( '.owl-item.active .oa-slide' );
                var col = $activeSlide.data( 'col' ) || 'oa-text-black';
                $slider.removeClass( 'oa-text-white oa-text-black' ).addClass( col );
            } );
            
            // Add interaction delay functionality
            $slider.on('mouseenter touchstart', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                $slider.trigger('stop.owl.autoplay');
            });
            
            $slider.on('mouseleave touchend', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    $slider.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
            
            // Also pause on navigation clicks
            $slider.on('click', '.owl-nav .owl-prev, .owl-nav .owl-next, .owl-dots .owl-dot', function() {
                // Clear any existing timer
                if (interactionTimer) {
                    clearTimeout(interactionTimer);
                }
                
                // Pause autoplay
                $slider.trigger('stop.owl.autoplay');
                
                // Set timer to resume autoplay after delay
                interactionTimer = setTimeout(function() {
                    $slider.trigger('play.owl.autoplay');
                }, interactionDelay);
            });
        } );
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render individual slide
     *
     * @param WP_Post $slider Slider post object
     * @return string
     */
    private function render_slide( $slider ) {
        // Get custom fields
        $background_type = get_post_meta( $slider->ID, 'slider_background_type', true );
        $video_url = get_post_meta( $slider->ID, 'slider_video_url', true );
        $video_poster_id = get_post_meta( $slider->ID, 'slider_video_poster_id', true );
        $video_poster_url = $video_poster_id ? wp_get_attachment_image_url( $video_poster_id, 'full' ) : '';
        $image_url = get_the_post_thumbnail_url( $slider->ID, 'full' );
        $heading = get_post_meta( $slider->ID, 'slider_heading', true );
        $button_text = get_post_meta( $slider->ID, 'slider_button_text', true );
        $button_link = get_post_meta( $slider->ID, 'slider_button_link', true );
        $button_target = get_post_meta( $slider->ID, 'slider_button_target', true );
        $text_align = get_post_meta( $slider->ID, 'slider_text_align', true );
        $vertical_align = get_post_meta( $slider->ID, 'slider_vertical_align', true );
        $text_colour = get_post_meta( $slider->ID, 'slider_text_colour', true );
        $background_color = get_post_meta( $slider->ID, 'slider_background_color', true );
        
        // Set defaults
        if ( empty( $background_type ) ) $background_type = 'image';
        if ( empty( $text_align ) ) $text_align = 'center';
        if ( empty( $vertical_align ) ) $vertical_align = 'center';
        if ( empty( $text_colour ) ) $text_colour = 'black';
        
        $text_color = ( $text_colour === 'white' ) ? 'oa-text-white' : 'oa-text-black';
        
        // Build inline styles
        $inline_styles = '';
        if ( $background_color ) {
            $inline_styles = ' style="background-color:' . esc_attr( $background_color ) . ';"';
        }
        
        // Build alignment classes
        $alignment_classes = OA_Elements_Utilities::get_alignment_classes( $text_align, $vertical_align );
        
        ob_start();
        ?>
        <div class="oa-slide <?php echo esc_attr( $text_color ); ?>" data-col="<?php echo esc_attr( $text_color ); ?>"<?php echo $inline_styles; ?>>
            <?php if ( $background_type === 'video' && $video_url ) : ?>
                <video class="oa-slide-bg-video" autoplay muted loop playsinline preload="<?php echo $video_poster_url ? 'metadata' : 'auto'; ?>"<?php echo $video_poster_url ? ' poster="' . esc_url( $video_poster_url ) . '"' : ''; ?>>
                    <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4" />
                </video>
            <?php elseif ( $image_url ) : ?>
                <div class="oa-slide-bg-image" style="background-image:url(<?php echo esc_url( $image_url ); ?>);"></div>
            <?php endif; ?>
            
            <div class="oa-slide-content <?php echo esc_attr( $alignment_classes ); ?>">
                <div class="oa-slide-inner">
                    <?php if ( $heading ) : ?>
                        <?php echo OA_Elements_Utilities::sanitize_html( $heading ); ?>
                    <?php endif; ?>
                    
                    <?php if ( $button_text && $button_link ) : ?>
                        <a href="<?php echo esc_url( $button_link ); ?>" class="oa-button button"<?php echo ( $button_target === '1' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
                            <?php echo esc_html( $button_text ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get slider examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_slider]',
                'description' => __( 'Basic slider with all slides', 'open-agency-elements' ),
            ),
            'category' => array(
                'shortcode' => '[oa_slider category="homepage"]',
                'description' => __( 'Slider with specific category', 'open-agency-elements' ),
            ),
            'height' => array(
                'shortcode' => '[oa_slider height="80"]',
                'description' => __( 'Slider with custom height (80vh)', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_slider class="custom-slider"]',
                'description' => __( 'Slider with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 