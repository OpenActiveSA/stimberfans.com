<?php
/**
 * Links Shortcode Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Links Shortcode Class
 */
class OA_Links_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_links_element' ) ) {
            add_shortcode( 'oa_links', array( $this, 'render_links' ) );
        }
    }
    
    /**
     * Render links shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_links( $atts ) {
        $defaults = array(
            'icon_color' => '',
            'icon_hover_color' => '',
            'socials_only' => 'no',
            'alignment' => 'space-between',
            'gap' => '25px',
            'class' => '',
        );
        
        $atts = oa_sanitize_shortcode_atts( $atts, $defaults, 'oa_links' );
        
        // Build inline styles
        $styles = $this->build_styles( $atts );
        
        // Build CSS classes
        $classes = $this->build_classes( $atts );
        
        // Get social links
        $social_links = $this->get_social_links();
        
        // Get other links
        $other_links = $this->get_other_links();
        
        // Start output buffering
        ob_start();
        ?>
        <div class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php if ( ! empty( $social_links ) ) : ?>
                <div class="oa-links-socials">
                    <?php echo $social_links; ?>
                </div>
            <?php endif; ?>
            
            <?php if ( $atts['socials_only'] !== 'yes' && ! empty( $other_links ) ) : ?>
                <div class="oa-links-others">
                    <?php echo $other_links; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build inline styles
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_styles( $atts ) {
        $styles = array();
        
        // Add icon colors
        if ( ! empty( $atts['icon_color'] ) ) {
            $styles[] = '--oa-links-icon-color:' . esc_attr( $atts['icon_color'] );
        }
        
        if ( ! empty( $atts['icon_hover_color'] ) ) {
            $styles[] = '--oa-links-icon-hover-color:' . esc_attr( $atts['icon_hover_color'] );
        }
        
        // Add alignment
        if ( in_array( $atts['alignment'], array( 'flex-start', 'center', 'flex-end', 'space-between' ) ) ) {
            $styles[] = 'justify-content:' . $atts['alignment'];
        }
        
        // Add gap
        if ( ! empty( $atts['gap'] ) ) {
            $styles[] = 'gap:' . esc_attr( $atts['gap'] );
        }
        
        return implode( ';', $styles );
    }
    
    /**
     * Build CSS classes
     *
     * @param array $atts Attributes
     * @return string
     */
    private function build_classes( $atts ) {
        $classes = array( 'oa-links-element' );
        
        // Add custom class
        if ( ! empty( $atts['class'] ) ) {
            $classes[] = sanitize_html_class( $atts['class'] );
        }
        
        return implode( ' ', $classes );
    }
    
    /**
     * Get social links
     *
     * @return string
     */
    private function get_social_links() {
        $enabled = (array) get_option( 'oa_links_social_enabled', array() );
        $urls = (array) get_option( 'oa_links_social_urls', array() );
        $platforms = oa_get_social_platforms();
        
        $links = array();
        
        foreach ( $platforms as $key => $label ) {
            if ( in_array( $key, $enabled, true ) && ! empty( $urls[ $key ] ) ) {
                $links[] = $this->render_social_link( $key, $urls[ $key ], $label );
            }
        }
        
        return implode( '', $links );
    }
    
    /**
     * Render social link
     *
     * @param string $platform Platform key
     * @param string $url URL
     * @param string $label Platform label
     * @return string
     */
    private function render_social_link( $platform, $url, $label ) {
        $svg_icon = oa_get_svg_icon( $platform );
        
        if ( empty( $svg_icon ) ) {
            return '';
        }
        
        return sprintf(
            '<a href="%1$s" target="_blank" rel="noopener" aria-label="%2$s">%3$s</a>',
            esc_url( $url ),
            esc_attr( $label ),
            $svg_icon
        );
    }
    
    /**
     * Get other links (account, cart, mini cart)
     *
     * @return string
     */
    private function get_other_links() {
        $links = array();
        
        // Account link
        if ( oa_is_feature_enabled( 'oa_links_show_account' ) && oa_is_woocommerce_active() ) {
            $links[] = $this->render_account_link();
        }
        
        // Cart link
        if ( oa_is_feature_enabled( 'oa_links_show_cart' ) && oa_is_woocommerce_active() ) {
            $links[] = $this->render_cart_link();
        }
        
        // Mini cart
        if ( oa_is_feature_enabled( 'oa_links_show_mini_cart' ) && oa_is_woocommerce_active() ) {
            $links[] = $this->render_mini_cart();
        }
        
        return implode( '', $links );
    }
    
    /**
     * Render account link
     *
     * @return string
     */
    private function render_account_link() {
        $account_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
        $svg_icon = oa_get_svg_icon( 'account' );
        
        if ( empty( $svg_icon ) ) {
            return '';
        }
        
        return sprintf(
            '<a href="%1$s" aria-label="%2$s">%3$s</a>',
            esc_url( $account_url ),
            esc_attr__( 'My Account', 'open-agency-elements' ),
            $svg_icon
        );
    }
    
    /**
     * Render cart link
     *
     * @return string
     */
    private function render_cart_link() {
        $cart_url = wc_get_cart_url();
        $svg_icon = oa_get_svg_icon( 'cart' );
        
        if ( empty( $svg_icon ) ) {
            return '';
        }
        
        return sprintf(
            '<a href="%1$s" aria-label="%2$s">%3$s</a>',
            esc_url( $cart_url ),
            esc_attr__( 'Cart', 'open-agency-elements' ),
            $svg_icon
        );
    }
    
    /**
     * Render mini cart
     *
     * @return string
     */
    private function render_mini_cart() {
        if ( ! function_exists( 'do_blocks' ) ) {
            return '';
        }
        
        return do_blocks( '<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"open_drawer"} /-->' );
    }
    
    /**
     * Get links examples
     *
     * @return array
     */
    public static function get_examples() {
        return array(
            'basic' => array(
                'shortcode' => '[oa_links]',
                'description' => __( 'Basic links element with all enabled features', 'open-agency-elements' ),
            ),
            'socials_only' => array(
                'shortcode' => '[oa_links socials_only="yes"]',
                'description' => __( 'Show only social media links', 'open-agency-elements' ),
            ),
            'custom_colors' => array(
                'shortcode' => '[oa_links icon_color="#333" icon_hover_color="#666"]',
                'description' => __( 'Custom icon colors', 'open-agency-elements' ),
            ),
            'centered' => array(
                'shortcode' => '[oa_links alignment="center" gap="20px"]',
                'description' => __( 'Centered alignment with custom gap', 'open-agency-elements' ),
            ),
        );
    }
} 