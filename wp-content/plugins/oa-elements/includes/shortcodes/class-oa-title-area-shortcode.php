<?php
/**
 * Title Area Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Title Area Shortcode Class
 */
class OA_Title_Area_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_title_area_element' ) ) {
            add_shortcode( 'oa_title_area', array( $this, 'render_title_area' ) );
        }
    }
    
    /**
     * Render title area shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_title_area( $atts ) {
        $defaults = array(
            'class' => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_title_area' );
        
        global $post;
        if ( ! $post ) {
            return '';
        }
        
        // Get custom fields
        $heading = get_post_meta( $post->ID, 'title_heading', true );
        $subheading = get_post_meta( $post->ID, 'title_subheading', true );
        $alignment = get_post_meta( $post->ID, 'title_alignment', true );
        
        // Set defaults
        if ( empty( $alignment ) ) $alignment = 'center';
        
        // If no custom heading, use post title
        if ( empty( $heading ) ) {
            $heading = get_the_title( $post->ID );
        }
        
        // If no heading or subheading, return empty
        if ( empty( $heading ) && empty( $subheading ) ) {
            return '';
        }
        
        // Build title area HTML
        return $this->build_title_area_html( $heading, $subheading, $alignment, $atts );
    }
    
    /**
     * Build title area HTML
     *
     * @param string $heading Heading text
     * @param string $subheading Subheading text
     * @param string $alignment Text alignment
     * @param array  $atts Attributes
     * @return string
     */
    private function build_title_area_html( $heading, $subheading, $alignment, $atts ) {
        $custom_class = ! empty( $atts['class'] ) ? ' ' . sanitize_html_class( $atts['class'] ) : '';
        
        ob_start();
        ?>
        <div class="oa-title-area oa-align-<?php echo esc_attr( $alignment ); ?><?php echo esc_attr( $custom_class ); ?>">
            <?php if ( $heading ) : ?>
                <h1 class="oa-title-heading"><?php echo esc_html( $heading ); ?></h1>
            <?php endif; ?>
            
            <?php if ( $subheading ) : ?>
                <div class="oa-title-subheading"><?php echo esc_html( $subheading ); ?></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get title area examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_title_area]',
                'description' => __( 'Basic title area using ACF fields', 'open-agency-elements' ),
            ),
            'custom_class' => array(
                'shortcode' => '[oa_title_area class="custom-title"]',
                'description' => __( 'Title area with custom CSS class', 'open-agency-elements' ),
            ),
        );
    }
} 