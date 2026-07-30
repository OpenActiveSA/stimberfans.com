<?php
/**
 * Page Options Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Page Options Class
 */
class OA_Page_Options {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_page_options_metabox' ) );
        add_action( 'save_post', array( $this, 'save_page_options' ) );
        add_action( 'wp_head', array( $this, 'output_page_padding_styles' ) );
    }
    
    /**
     * Add Page Options metabox
     */
    public function add_page_options_metabox() {
        // Add to pages and posts
        $post_types = array( 'post', 'page' );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'oa_page_options',
                __( 'OAE Page Options', 'open-agency-elements' ),
                array( $this, 'render_page_options_metabox' ),
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render Page Options metabox
     */
    public function render_page_options_metabox( $post ) {
        // Add nonce for security
        wp_nonce_field( 'oa_page_options_nonce', 'oa_page_options_nonce_field' );
        
        // Get current values
        $page_padding_top = get_post_meta( $post->ID, '_oa_page_padding_top', true );
        $page_padding_bottom = get_post_meta( $post->ID, '_oa_page_padding_bottom', true );
        $page_padding_left = get_post_meta( $post->ID, '_oa_page_padding_left', true );
        $page_padding_right = get_post_meta( $post->ID, '_oa_page_padding_right', true );
        $page_padding_unit_top = get_post_meta( $post->ID, '_oa_page_padding_unit_top', true );
        $page_padding_unit_bottom = get_post_meta( $post->ID, '_oa_page_padding_unit_bottom', true );
        $page_padding_unit_left = get_post_meta( $post->ID, '_oa_page_padding_unit_left', true );
        $page_padding_unit_right = get_post_meta( $post->ID, '_oa_page_padding_unit_right', true );
        
        // Default values
        if ( empty( $page_padding_unit_top ) ) {
            $page_padding_unit_top = 'px';
        }
        if ( empty( $page_padding_unit_bottom ) ) {
            $page_padding_unit_bottom = 'px';
        }
        if ( empty( $page_padding_unit_left ) ) {
            $page_padding_unit_left = 'px';
        }
        if ( empty( $page_padding_unit_right ) ) {
            $page_padding_unit_right = 'px';
        }
        
        ?>
        <div class="oa-page-options">
            <h4><?php _e( 'Page Padding Override', 'open-agency-elements' ); ?></h4>
            <p><?php _e( 'Override the default page padding for this specific page.', 'open-agency-elements' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <td>
                        <label for="oa_page_padding_top"><?php _e( 'Top Padding:', 'open-agency-elements' ); ?></label>
                        <input type="number" 
                               id="oa_page_padding_top" 
                               name="oa_page_padding_top" 
                               value="<?php echo esc_attr( $page_padding_top ); ?>" 
                               min="0" 
                               step="1" 
                               style="width: 60px;" />
                        
                        <select name="oa_page_padding_unit_top" id="oa_page_padding_unit_top" style="width: 50px;">
                            <option value="px" <?php selected( $page_padding_unit_top, 'px' ); ?>>px</option>
                            <option value="em" <?php selected( $page_padding_unit_top, 'em' ); ?>>em</option>
                            <option value="rem" <?php selected( $page_padding_unit_top, 'rem' ); ?>>rem</option>
                            <option value="%" <?php selected( $page_padding_unit_top, '%' ); ?>>%</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="oa_page_padding_bottom"><?php _e( 'Bottom Padding:', 'open-agency-elements' ); ?></label>
                        <input type="number" 
                               id="oa_page_padding_bottom" 
                               name="oa_page_padding_bottom" 
                               value="<?php echo esc_attr( $page_padding_bottom ); ?>" 
                               min="0" 
                               step="1" 
                               style="width: 60px;" />
                        
                        <select name="oa_page_padding_unit_bottom" id="oa_page_padding_unit_bottom" style="width: 50px;">
                            <option value="px" <?php selected( $page_padding_unit_bottom, 'px' ); ?>>px</option>
                            <option value="em" <?php selected( $page_padding_unit_bottom, 'em' ); ?>>em</option>
                            <option value="rem" <?php selected( $page_padding_unit_bottom, 'rem' ); ?>>rem</option>
                            <option value="%" <?php selected( $page_padding_unit_bottom, '%' ); ?>>%</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="oa_page_padding_left"><?php _e( 'Left Padding:', 'open-agency-elements' ); ?></label>
                        <input type="number" 
                               id="oa_page_padding_left" 
                               name="oa_page_padding_left" 
                               value="<?php echo esc_attr( $page_padding_left ); ?>" 
                               min="0" 
                               step="1" 
                               style="width: 60px;" />
                        
                        <select name="oa_page_padding_unit_left" id="oa_page_padding_unit_left" style="width: 50px;">
                            <option value="px" <?php selected( $page_padding_unit_left, 'px' ); ?>>px</option>
                            <option value="em" <?php selected( $page_padding_unit_left, 'em' ); ?>>em</option>
                            <option value="rem" <?php selected( $page_padding_unit_left, 'rem' ); ?>>rem</option>
                            <option value="%" <?php selected( $page_padding_unit_left, '%' ); ?>>%</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="oa_page_padding_right"><?php _e( 'Right Padding:', 'open-agency-elements' ); ?></label>
                        <input type="number" 
                               id="oa_page_padding_right" 
                               name="oa_page_padding_right" 
                               value="<?php echo esc_attr( $page_padding_right ); ?>" 
                               min="0" 
                               step="1" 
                               style="width: 60px;" />
                        
                        <select name="oa_page_padding_unit_right" id="oa_page_padding_unit_right" style="width: 50px;">
                            <option value="px" <?php selected( $page_padding_unit_right, 'px' ); ?>>px</option>
                            <option value="em" <?php selected( $page_padding_unit_right, 'em' ); ?>>em</option>
                            <option value="rem" <?php selected( $page_padding_unit_right, 'rem' ); ?>>rem</option>
                            <option value="%" <?php selected( $page_padding_unit_right, '%' ); ?>>%</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="description">
                <?php _e( 'Leave empty to use default theme padding.', 'open-agency-elements' ); ?>
            </p>
        </div>
        
        <style>
        .oa-page-options {
            margin: 10px 0;
        }
        
        .oa-page-options h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .oa-page-options p {
            margin: 0 0 15px 0;
            font-size: 12px;
            color: #666;
        }
        
        .oa-page-options .form-table {
            margin: 0;
        }
        
        .oa-page-options .form-table td {
            padding: 0;
        }
        
        .oa-page-options label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .oa-page-options input[type="number"],
        .oa-page-options select {
            font-size: 12px;
        }
        
        .oa-page-options .description {
            margin-top: 10px;
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        </style>
        <?php
    }
    
    /**
     * Save Page Options
     */
    public function save_page_options( $post_id ) {
        // Check if nonce is valid
        if ( ! isset( $_POST['oa_page_options_nonce_field'] ) || 
             ! wp_verify_nonce( $_POST['oa_page_options_nonce_field'], 'oa_page_options_nonce' ) ) {
            return;
        }
        
        // Check if user has permission
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Save page padding values and units
        $padding_fields = array( 'top', 'bottom', 'left', 'right' );
        
        foreach ( $padding_fields as $field ) {
            $field_name = 'oa_page_padding_' . $field;
            $unit_field_name = 'oa_page_padding_unit_' . $field;
            
            // Save padding value
            if ( isset( $_POST[ $field_name ] ) ) {
                $page_padding_value = sanitize_text_field( $_POST[ $field_name ] );
                
                // Only save if it's a valid number or empty
                if ( $page_padding_value === '' || is_numeric( $page_padding_value ) ) {
                    update_post_meta( $post_id, '_oa_page_padding_' . $field, $page_padding_value );
                }
            }
            
            // Save unit value
            if ( isset( $_POST[ $unit_field_name ] ) ) {
                $page_padding_unit = sanitize_text_field( $_POST[ $unit_field_name ] );
                $allowed_units = array( 'px', 'em', 'rem', '%' );
                
                if ( in_array( $page_padding_unit, $allowed_units ) ) {
                    update_post_meta( $post_id, '_oa_page_padding_unit_' . $field, $page_padding_unit );
                }
            }
        }
    }
    
    /**
     * Output page padding styles
     */
    public function output_page_padding_styles() {
        // Safety check - ensure we're in the right context
        if ( ! function_exists( 'is_singular' ) ) {
            return;
        }
        
        // Only output on single pages/posts
        if ( ! is_singular( array( 'post', 'page' ) ) ) {
            return;
        }
        
        $post_id = get_the_ID();
        $page_padding_top = get_post_meta( $post_id, '_oa_page_padding_top', true );
        $page_padding_bottom = get_post_meta( $post_id, '_oa_page_padding_bottom', true );
        $page_padding_left = get_post_meta( $post_id, '_oa_page_padding_left', true );
        $page_padding_right = get_post_meta( $post_id, '_oa_page_padding_right', true );
        $page_padding_unit_top = get_post_meta( $post_id, '_oa_page_padding_unit_top', true );
        $page_padding_unit_bottom = get_post_meta( $post_id, '_oa_page_padding_unit_bottom', true );
        $page_padding_unit_left = get_post_meta( $post_id, '_oa_page_padding_unit_left', true );
        $page_padding_unit_right = get_post_meta( $post_id, '_oa_page_padding_unit_right', true );
        
        // Default units
        if ( empty( $page_padding_unit_top ) ) {
            $page_padding_unit_top = 'px';
        }
        if ( empty( $page_padding_unit_bottom ) ) {
            $page_padding_unit_bottom = 'px';
        }
        if ( empty( $page_padding_unit_left ) ) {
            $page_padding_unit_left = 'px';
        }
        if ( empty( $page_padding_unit_right ) ) {
            $page_padding_unit_right = 'px';
        }
        
        // Build CSS properties
        $css_properties = array();
        
        if ( is_numeric( $page_padding_top ) && $page_padding_top !== '' ) {
            $css_properties[] = 'padding-top: ' . $page_padding_top . $page_padding_unit_top . ' !important';
        }
        
        if ( is_numeric( $page_padding_bottom ) && $page_padding_bottom !== '' ) {
            $css_properties[] = 'padding-bottom: ' . $page_padding_bottom . $page_padding_unit_bottom . ' !important';
        }
        
        if ( is_numeric( $page_padding_left ) && $page_padding_left !== '' ) {
            $css_properties[] = 'padding-left: ' . $page_padding_left . $page_padding_unit_left . ' !important';
        }
        
        if ( is_numeric( $page_padding_right ) && $page_padding_right !== '' ) {
            $css_properties[] = 'padding-right: ' . $page_padding_right . $page_padding_unit_right . ' !important';
        }
        
        // Only output if there are padding values set
        if ( ! empty( $css_properties ) ) {
            ?>
            <style id="oa-page-padding-override">
            #content {
                <?php echo implode( '; ', $css_properties ); ?>;
            }
            </style>
            <?php
        }
    }
} 