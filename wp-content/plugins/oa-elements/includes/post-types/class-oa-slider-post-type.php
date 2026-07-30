<?php
/**
 * Slider Post Type Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Slider Post Type Class
 */
class OA_Slider_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * Register slider post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Sliders', 'open-agency-elements' ),
            'singular_name'      => __( 'Slider', 'open-agency-elements' ),
            'menu_name'          => __( 'Sliders', 'open-agency-elements' ),
            'add_new'            => __( 'Add New', 'open-agency-elements' ),
            'add_new_item'       => __( 'Add New Slider', 'open-agency-elements' ),
            'edit_item'          => __( 'Edit Slider', 'open-agency-elements' ),
            'new_item'           => __( 'New Slider', 'open-agency-elements' ),
            'view_item'          => __( 'View Slider', 'open-agency-elements' ),
            'search_items'       => __( 'Search Sliders', 'open-agency-elements' ),
            'not_found'          => __( 'No sliders found', 'open-agency-elements' ),
            'not_found_in_trash' => __( 'No sliders found in trash', 'open-agency-elements' ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-images-alt2',
            'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
            'taxonomies'          => array( 'slider_category' ),
            'show_in_rest'        => true,
            'rewrite'             => array( 'slug' => 'sliders' ),
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_position'       => 20,
        );
        
        register_post_type( 'oa_slider', $args );
    }
    
    /**
     * Register slider taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Slider Categories', 'open-agency-elements' ),
            'singular_name'     => __( 'Slider Category', 'open-agency-elements' ),
            'search_items'      => __( 'Search Slider Categories', 'open-agency-elements' ),
            'all_items'         => __( 'All Slider Categories', 'open-agency-elements' ),
            'parent_item'       => __( 'Parent Slider Category', 'open-agency-elements' ),
            'parent_item_colon' => __( 'Parent Slider Category:', 'open-agency-elements' ),
            'edit_item'         => __( 'Edit Slider Category', 'open-agency-elements' ),
            'update_item'       => __( 'Update Slider Category', 'open-agency-elements' ),
            'add_new_item'      => __( 'Add New Slider Category', 'open-agency-elements' ),
            'new_item_name'     => __( 'New Slider Category Name', 'open-agency-elements' ),
            'menu_name'         => __( 'Categories', 'open-agency-elements' ),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'slider-category' ),
            'show_in_rest'      => true,
        );
        
        register_taxonomy( 'slider_category', array( 'oa_slider' ), $args );
    }
    
    /**
     * Add meta boxes for slider post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'oa_slider_fields',
            __( 'Slider Settings', 'open-agency-elements' ),
            array( $this, 'render_meta_box' ),
            'oa_slider',
            'normal',
            'default'
        );
    }
    
    /**
     * Render meta box content
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'oa_slider_fields', 'oa_slider_fields_nonce' );
        
        $background_type = get_post_meta( $post->ID, 'slider_background_type', true );
        $video_url = get_post_meta( $post->ID, 'slider_video_url', true );
        $heading = get_post_meta( $post->ID, 'slider_heading', true );
        $button_text = get_post_meta( $post->ID, 'slider_button_text', true );
        $button_link = get_post_meta( $post->ID, 'slider_button_link', true );
        $button_target = get_post_meta( $post->ID, 'slider_button_target', true );
        $text_align = get_post_meta( $post->ID, 'slider_text_align', true );
        $vertical_align = get_post_meta( $post->ID, 'slider_vertical_align', true );
        $text_colour = get_post_meta( $post->ID, 'slider_text_colour', true );
        $background_color = get_post_meta( $post->ID, 'slider_background_color', true );
        
        // Set defaults
        if ( empty( $background_type ) ) $background_type = 'image';
        if ( empty( $text_align ) ) $text_align = 'center';
        if ( empty( $vertical_align ) ) $vertical_align = 'center';
        if ( empty( $text_colour ) ) $text_colour = 'black';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="oa_slider_background_type"><?php _e( 'Background Type', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="radio" name="oa_slider_background_type" value="image" <?php checked( $background_type, 'image' ); ?>>
                            <?php _e( 'Image', 'open-agency-elements' ); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="oa_slider_background_type" value="video" <?php checked( $background_type, 'video' ); ?>>
                            <?php _e( 'Video', 'open-agency-elements' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr class="oa-video-field" style="<?php echo ( $background_type === 'video' ) ? '' : 'display: none;'; ?>">
                <th scope="row">
                    <label for="oa_slider_video_url"><?php _e( 'Video URL', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="url" name="oa_slider_video_url" id="oa_slider_video_url" value="<?php echo esc_attr( $video_url ); ?>" class="regular-text">
                    <p class="description"><?php _e( 'Only used if Background Type is Video', 'open-agency-elements' ); ?></p>
                </td>
            </tr>

            <tr class="oa-video-field" style="<?php echo ( $background_type === 'video' ) ? '' : 'display: none;'; ?>">
                <th scope="row">
                    <label for="oa_slider_video_poster_id"><?php _e( 'Video Poster Image', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <?php
                    $poster_id  = get_post_meta( $post->ID, 'slider_video_poster_id', true );
                    $poster_src = $poster_id ? wp_get_attachment_image_url( $poster_id, 'large' ) : '';
                    ?>
                    <div class="oa-video-poster-field">
                        <input type="hidden" name="oa_slider_video_poster_id" id="oa_slider_video_poster_id" value="<?php echo esc_attr( $poster_id ); ?>">
                        <div class="oa-video-poster-preview" style="<?php echo $poster_src ? '' : 'display:none;'; ?>margin-bottom:8px;">
                            <img src="<?php echo esc_url( $poster_src ); ?>" alt="" style="max-width:240px;height:auto;display:block;border:1px solid #ddd;" />
                        </div>
                        <button type="button" class="button oa-video-poster-upload"><?php _e( 'Select Poster Image', 'open-agency-elements' ); ?></button>
                        <button type="button" class="button oa-video-poster-remove" style="<?php echo $poster_src ? '' : 'display:none;'; ?>"><?php _e( 'Remove', 'open-agency-elements' ); ?></button>
                        <p class="description"><?php _e( 'Shown instantly while the video loads. Greatly improves page load speed. Use a still frame from the video for a seamless look.', 'open-agency-elements' ); ?></p>
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_heading"><?php _e( 'Heading (HTML allowed)', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <textarea name="oa_slider_heading" id="oa_slider_heading" rows="4" class="large-text"><?php echo esc_textarea( $heading ); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_button_text"><?php _e( 'Button Text', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="text" name="oa_slider_button_text" id="oa_slider_button_text" value="<?php echo esc_attr( $button_text ); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_button_link"><?php _e( 'Button Link', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <input type="url" name="oa_slider_button_link" id="oa_slider_button_link" value="<?php echo esc_attr( $button_link ); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_button_target"><?php _e( 'Open in new tab?', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="oa_slider_button_target" value="1" <?php checked( $button_target, '1' ); ?>>
                        <?php _e( 'Open in new tab/window', 'open-agency-elements' ); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_text_align"><?php _e( 'Text Align', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <select name="oa_slider_text_align" id="oa_slider_text_align" class="regular-text">
                        <option value="left" <?php selected( $text_align, 'left' ); ?>><?php _e( 'Left', 'open-agency-elements' ); ?></option>
                        <option value="center" <?php selected( $text_align, 'center' ); ?>><?php _e( 'Center', 'open-agency-elements' ); ?></option>
                        <option value="right" <?php selected( $text_align, 'right' ); ?>><?php _e( 'Right', 'open-agency-elements' ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_vertical_align"><?php _e( 'Text Vertical Align', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <select name="oa_slider_vertical_align" id="oa_slider_vertical_align" class="regular-text">
                        <option value="top" <?php selected( $vertical_align, 'top' ); ?>><?php _e( 'Top', 'open-agency-elements' ); ?></option>
                        <option value="center" <?php selected( $vertical_align, 'center' ); ?>><?php _e( 'Center', 'open-agency-elements' ); ?></option>
                        <option value="bottom" <?php selected( $vertical_align, 'bottom' ); ?>><?php _e( 'Bottom', 'open-agency-elements' ); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_text_colour"><?php _e( 'Text Colour', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="radio" name="oa_slider_text_colour" value="white" <?php checked( $text_colour, 'white' ); ?>>
                            <?php _e( 'White', 'open-agency-elements' ); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="oa_slider_text_colour" value="black" <?php checked( $text_colour, 'black' ); ?>>
                            <?php _e( 'Black', 'open-agency-elements' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="oa_slider_background_color"><?php _e( 'Background Colour', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <?php $this->render_enhanced_color_field( 'oa_slider_background_color', $background_color, '#000000' ); ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['oa_slider_fields_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['oa_slider_fields_nonce'], 'oa_slider_fields' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $background_type = isset( $_POST['oa_slider_background_type'] ) ? sanitize_text_field( $_POST['oa_slider_background_type'] ) : 'image';
        $video_url = isset( $_POST['oa_slider_video_url'] ) ? esc_url_raw( $_POST['oa_slider_video_url'] ) : '';
        $video_poster_id = isset( $_POST['oa_slider_video_poster_id'] ) ? absint( $_POST['oa_slider_video_poster_id'] ) : 0;
        $heading = isset( $_POST['oa_slider_heading'] ) ? wp_kses_post( $_POST['oa_slider_heading'] ) : '';
        $button_text = isset( $_POST['oa_slider_button_text'] ) ? sanitize_text_field( $_POST['oa_slider_button_text'] ) : '';
        $button_link = isset( $_POST['oa_slider_button_link'] ) ? esc_url_raw( $_POST['oa_slider_button_link'] ) : '';
        $button_target = isset( $_POST['oa_slider_button_target'] ) ? '1' : '';
        $text_align = isset( $_POST['oa_slider_text_align'] ) ? sanitize_text_field( $_POST['oa_slider_text_align'] ) : 'center';
        $vertical_align = isset( $_POST['oa_slider_vertical_align'] ) ? sanitize_text_field( $_POST['oa_slider_vertical_align'] ) : 'center';
        $text_colour = isset( $_POST['oa_slider_text_colour'] ) ? sanitize_text_field( $_POST['oa_slider_text_colour'] ) : 'black';
        $background_color = isset( $_POST['oa_slider_background_color'] ) ? oa_sanitize_hex_color( $_POST['oa_slider_background_color'] ) : '';
        
        update_post_meta( $post_id, 'slider_background_type', $background_type );
        update_post_meta( $post_id, 'slider_video_url', $video_url );
        update_post_meta( $post_id, 'slider_video_poster_id', $video_poster_id );
        update_post_meta( $post_id, 'slider_heading', $heading );
        update_post_meta( $post_id, 'slider_button_text', $button_text );
        update_post_meta( $post_id, 'slider_button_link', $button_link );
        update_post_meta( $post_id, 'slider_button_target', $button_target );
        update_post_meta( $post_id, 'slider_text_align', $text_align );
        update_post_meta( $post_id, 'slider_vertical_align', $vertical_align );
        update_post_meta( $post_id, 'slider_text_colour', $text_colour );
        update_post_meta( $post_id, 'slider_background_color', $background_color );
    }
    
    /**
     * Render enhanced color field
     */
    private function render_enhanced_color_field( $field_id, $value, $default = '#ffffff' ) {
        // Common color presets
        $presets = array(
            '#ffffff' => __( 'White', 'open-agency-elements' ),
            '#000000' => __( 'Black', 'open-agency-elements' ),
            '#0073aa' => __( 'WordPress Blue', 'open-agency-elements' ),
            '#f7f7f7' => __( 'Light Gray', 'open-agency-elements' ),
            '#333333' => __( 'Dark Gray', 'open-agency-elements' ),
            '#dc3232' => __( 'Red', 'open-agency-elements' ),
            '#46b450' => __( 'Green', 'open-agency-elements' ),
        );
        
        ?>
        <div class="oa-color-field">
            <div class="oa-color-inputs">
                <input type="color" 
                       id="<?php echo esc_attr( $field_id ); ?>_picker" 
                       value="<?php echo esc_attr( $value ); ?>" 
                       class="oa-color-picker" />
                
                <input type="text" 
                       id="<?php echo esc_attr( $field_id ); ?>" 
                       name="<?php echo esc_attr( $field_id ); ?>" 
                       value="<?php echo esc_attr( $value ); ?>" 
                       class="oa-color-text" 
                       placeholder="#ffffff" 
                       pattern="^#[0-9A-Fa-f]{6}$" 
                       maxlength="7" />
                
                <button type="button" class="button oa-color-reset" data-default="<?php echo esc_attr( $default ); ?>">
                    <?php _e( 'Reset', 'open-agency-elements' ); ?>
                </button>
            </div>
            
            <div class="oa-color-preview" style="background-color: <?php echo esc_attr( $value ); ?>;"></div>
            
            <div class="oa-color-presets">
                <span class="oa-color-presets-label"><?php _e( 'Quick colors:', 'open-agency-elements' ); ?></span>
                <?php foreach ( $presets as $color => $label ) : ?>
                    <button type="button" 
                            class="oa-color-preset" 
                            data-color="<?php echo esc_attr( $color ); ?>" 
                            style="background-color: <?php echo esc_attr( $color ); ?>;"
                            title="<?php echo esc_attr( $label ); ?>">
                        <span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <p class="description">
                <small><?php _e( 'Use the color picker, enter a hex color, or click a preset color', 'open-agency-elements' ); ?></small>
            </p>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        global $post_type;
        
        if ( 'oa_slider' !== $post_type ) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script( 'oa-slider-admin', OA_ELEMENTS_PLUGIN_URL . 'assets/js/slider-admin.js', array( 'jquery' ), '1.0.1', true );
        
        // Enqueue the enhanced color field styles and scripts
        wp_enqueue_style( 'oa-elements-admin', OA_ELEMENTS_PLUGIN_URL . 'assets/css/admin.css', array(), OA_ELEMENTS_VERSION );
        wp_enqueue_script( 'oa-elements-admin', OA_ELEMENTS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), OA_ELEMENTS_VERSION, true );
    }
} 