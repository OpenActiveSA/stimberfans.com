<?php
/**
 * Logo Post Type Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Logo Post Type Class
 */
class OA_Logo_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }
    
    /**
     * Register logo post type
     */
    public function register_post_type() {

        
        $labels = array(
            'name'               => __( 'Logos', 'open-agency-elements' ),
            'singular_name'      => __( 'Logo', 'open-agency-elements' ),
            'menu_name'          => __( 'Logos', 'open-agency-elements' ),
            'add_new'            => __( 'Add New', 'open-agency-elements' ),
            'add_new_item'       => __( 'Add New Logo', 'open-agency-elements' ),
            'edit_item'          => __( 'Edit Logo', 'open-agency-elements' ),
            'new_item'           => __( 'New Logo', 'open-agency-elements' ),
            'view_item'          => __( 'View Logo', 'open-agency-elements' ),
            'search_items'       => __( 'Search Logos', 'open-agency-elements' ),
            'not_found'          => __( 'No logos found', 'open-agency-elements' ),
            'not_found_in_trash' => __( 'No logos found in trash', 'open-agency-elements' ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-format-image',
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
            'rewrite'             => false,
        );
        
        register_post_type( 'logo', $args );
    }
    
    /**
     * Register logo taxonomy
     */
    public function register_taxonomy() {

        
        $labels = array(
            'name'              => __( 'Logo Categories', 'open-agency-elements' ),
            'singular_name'     => __( 'Logo Category', 'open-agency-elements' ),
            'search_items'      => __( 'Search Logo Categories', 'open-agency-elements' ),
            'all_items'         => __( 'All Logo Categories', 'open-agency-elements' ),
            'parent_item'       => __( 'Parent Logo Category', 'open-agency-elements' ),
            'parent_item_colon' => __( 'Parent Logo Category:', 'open-agency-elements' ),
            'edit_item'         => __( 'Edit Logo Category', 'open-agency-elements' ),
            'update_item'       => __( 'Update Logo Category', 'open-agency-elements' ),
            'add_new_item'      => __( 'Add New Logo Category', 'open-agency-elements' ),
            'new_item_name'     => __( 'New Logo Category Name', 'open-agency-elements' ),
            'menu_name'         => __( 'Categories', 'open-agency-elements' ),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
        );
        
        register_taxonomy( 'logo_category', array( 'logo' ), $args );
    }
    
    /**
     * Add meta boxes for logo post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'oa_logo_fields',
            __( 'Logo Settings', 'open-agency-elements' ),
            array( $this, 'render_meta_box' ),
            'logo',
            'normal',
            'default'
        );
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'oa_logo_fields', 'oa_logo_fields_nonce' );
        
        $logo_url = get_post_meta( $post->ID, 'logo_url', true );
        $logo_target = get_post_meta( $post->ID, 'logo_target', true );
        $logo_svg_code = get_post_meta( $post->ID, 'logo_svg_code', true );
        $logo_subheading = get_post_meta( $post->ID, 'logo_subheading', true );
        $logo_button_text = get_post_meta( $post->ID, 'logo_button_text', true );
        $logo_button_url = get_post_meta( $post->ID, 'logo_button_url', true );
        $logo_button_target = get_post_meta( $post->ID, 'logo_button_target', true );
        
        // Check if featured image is SVG
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        $is_svg = false;
        if ( $thumbnail_id ) {
            $file_path = get_attached_file( $thumbnail_id );
            if ( $file_path && pathinfo( $file_path, PATHINFO_EXTENSION ) === 'svg' ) {
                $is_svg = true;
            }
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="oa_logo_url"><?php _e( 'Logo URL', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="oa_logo_url" 
                           name="oa_logo_url" 
                           value="<?php echo esc_attr( $logo_url ); ?>" 
                           class="regular-text" 
                           placeholder="https://example.com" />
                    <p class="description"><?php _e( 'Optional: Link the logo to a website', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="oa_logo_target"><?php _e( 'Link Target', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <select id="oa_logo_target" name="oa_logo_target">
                        <option value="same" <?php selected( $logo_target, 'same' ); ?>><?php _e( 'Same Window', 'open-agency-elements' ); ?></option>
                        <option value="blank" <?php selected( $logo_target, 'blank' ); ?>><?php _e( 'New Window', 'open-agency-elements' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Choose how the logo link opens', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="oa_logo_subheading"><?php _e( 'Subheading', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="oa_logo_subheading" 
                           name="oa_logo_subheading" 
                           value="<?php echo esc_attr( $logo_subheading ); ?>" 
                           class="regular-text" 
                           placeholder="<?php _e( 'Optional subheading text', 'open-agency-elements' ); ?>" />
                    <p class="description"><?php _e( 'Optional: Add a subheading below the logo', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="oa_logo_button_text"><?php _e( 'Button Text', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="oa_logo_button_text" 
                           name="oa_logo_button_text" 
                           value="<?php echo esc_attr( $logo_button_text ); ?>" 
                           class="regular-text" 
                           placeholder="<?php _e( 'Learn More', 'open-agency-elements' ); ?>" />
                    <p class="description"><?php _e( 'Optional: Add button text to display below the subheading', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="oa_logo_button_url"><?php _e( 'Button URL', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="oa_logo_button_url" 
                           name="oa_logo_button_url" 
                           value="<?php echo esc_attr( $logo_button_url ); ?>" 
                           class="regular-text" 
                           placeholder="https://example.com" />
                    <p class="description"><?php _e( 'Required if button text is set: URL for the button link', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="oa_logo_button_target"><?php _e( 'Button Target', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <select id="oa_logo_button_target" name="oa_logo_button_target">
                        <option value="same" <?php selected( $logo_button_target, 'same' ); ?>><?php _e( 'Same Window', 'open-agency-elements' ); ?></option>
                        <option value="blank" <?php selected( $logo_button_target, 'blank' ); ?>><?php _e( 'New Window', 'open-agency-elements' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Choose how the button link opens', 'open-agency-elements' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e( 'Logo Image', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
                        <div class="oa-logo-preview">
                            <?php the_post_thumbnail( 'medium' ); ?>
                            <?php if ( $is_svg ) : ?>
                                <p class="description">
                                    <strong><?php _e( 'SVG detected!', 'open-agency-elements' ); ?></strong> 
                                    <?php _e( 'This logo will support dynamic colors on light/dark backgrounds.', 'open-agency-elements' ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <p class="description">
                            <?php _e( 'Set the featured image to display the logo. SVG files will support dynamic colors.', 'open-agency-elements' ); ?>
                        </p>
                    <?php endif; ?>
                    <p class="description">
                        <strong><?php _e( 'Tip:', 'open-agency-elements' ); ?></strong> 
                        <?php _e( 'Use the "Set featured image" option above to add your logo. SVG files will automatically support color changes.', 'open-agency-elements' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['oa_logo_fields_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['oa_logo_fields_nonce'], 'oa_logo_fields' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $logo_url = isset( $_POST['oa_logo_url'] ) ? esc_url_raw( $_POST['oa_logo_url'] ) : '';
        $logo_target = isset( $_POST['oa_logo_target'] ) ? sanitize_text_field( $_POST['oa_logo_target'] ) : 'same';
        $logo_subheading = isset( $_POST['oa_logo_subheading'] ) ? sanitize_text_field( $_POST['oa_logo_subheading'] ) : '';
        $logo_button_text = isset( $_POST['oa_logo_button_text'] ) ? sanitize_text_field( $_POST['oa_logo_button_text'] ) : '';
        $logo_button_url = isset( $_POST['oa_logo_button_url'] ) ? esc_url_raw( $_POST['oa_logo_button_url'] ) : '';
        $logo_button_target = isset( $_POST['oa_logo_button_target'] ) ? sanitize_text_field( $_POST['oa_logo_button_target'] ) : 'same';
        
        // Extract SVG code if an SVG image is uploaded
        $svg_code = '';
        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $file_path = get_attached_file( $thumbnail_id );
            if ( $file_path && pathinfo( $file_path, PATHINFO_EXTENSION ) === 'svg' ) {
                $svg_code = $this->extract_svg_code( $file_path );
            }
        }
        
        update_post_meta( $post_id, 'logo_url', $logo_url );
        update_post_meta( $post_id, 'logo_target', $logo_target );
        update_post_meta( $post_id, 'logo_subheading', $logo_subheading );
        update_post_meta( $post_id, 'logo_button_text', $logo_button_text );
        update_post_meta( $post_id, 'logo_button_url', $logo_button_url );
        update_post_meta( $post_id, 'logo_button_target', $logo_button_target );
        update_post_meta( $post_id, 'logo_svg_code', $svg_code );
    }
    
    /**
     * Extract SVG code from uploaded SVG file
     *
     * @param string $file_path Path to the SVG file
     * @return string SVG code or empty string if failed
     */
    private function extract_svg_code( $file_path ) {
        // Check if file exists and is readable
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return '';
        }
        
        // Read SVG content
        $svg_content = file_get_contents( $file_path );
        
        if ( ! $svg_content ) {
            return '';
        }
        
        // Clean and optimize SVG code
        return $this->clean_svg_code( $svg_content );
    }
    
    /**
     * Clean and optimize SVG code
     *
     * @param string $svg_content Raw SVG content
     * @return string Cleaned SVG code
     */
    private function clean_svg_code( $svg_content ) {
        // Remove XML declaration and comments
        $svg_content = preg_replace( '/<\?xml[^>]*\?>/', '', $svg_content );
        $svg_content = preg_replace( '/<!--.*?-->/s', '', $svg_content );
        
        // Remove DOCTYPE if present
        $svg_content = preg_replace( '/<!DOCTYPE[^>]*>/', '', $svg_content );
        
        // Remove unnecessary whitespace
        $svg_content = preg_replace( '/\s+/', ' ', $svg_content );
        $svg_content = trim( $svg_content );
        
        // Ensure it's a valid SVG
        if ( ! preg_match( '/<svg[^>]*>.*<\/svg>/s', $svg_content ) ) {
            return '';
        }
        
        // Add CSS variables for color control - DISABLED to prevent unwanted stroke styles
        // $svg_content = $this->add_color_control_to_svg( $svg_content );
        
        return $svg_content;
    }
    
    /**
     * Add color control CSS variables to SVG - DISABLED
     *
     * @param string $svg_content SVG content
     * @return string Modified SVG content
     */
    private function add_color_control_to_svg( $svg_content ) {
        // DISABLED: This method was causing unwanted stroke styles on all SVGs
        // Return the SVG content unchanged
        return $svg_content;
    }
} 