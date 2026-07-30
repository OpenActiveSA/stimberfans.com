<?php
/**
 * FAQ Post Type Class
 *
 * @package OpenAgencyElements
 * @since 1.1.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA FAQ Post Type Class
 */
class OA_FAQ_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_filter( 'manage_oa_faq_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_oa_faq_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
    }
    
    /**
     * Register FAQ post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'FAQs', 'open-agency-elements' ),
            'singular_name'      => __( 'FAQ', 'open-agency-elements' ),
            'menu_name'          => __( 'FAQs', 'open-agency-elements' ),
            'add_new'            => __( 'Add New', 'open-agency-elements' ),
            'add_new_item'       => __( 'Add New FAQ', 'open-agency-elements' ),
            'edit_item'          => __( 'Edit FAQ', 'open-agency-elements' ),
            'new_item'           => __( 'New FAQ', 'open-agency-elements' ),
            'view_item'          => __( 'View FAQ', 'open-agency-elements' ),
            'search_items'       => __( 'Search FAQs', 'open-agency-elements' ),
            'not_found'          => __( 'No FAQs found', 'open-agency-elements' ),
            'not_found_in_trash' => __( 'No FAQs found in trash', 'open-agency-elements' ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 24,
            'menu_icon'           => 'dashicons-format-chat',
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'supports'            => array( 
                'title', 
                'editor', 
                'page-attributes',
                'custom-fields',
                'revisions',
                'excerpt'
            ),
            'rewrite'             => false,
            'show_in_rest'        => true, // Enable Gutenberg/REST API support
        );
        
        register_post_type( 'oa_faq', $args );
    }
    
    /**
     * Register FAQ taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'FAQ Categories', 'open-agency-elements' ),
            'singular_name'     => __( 'FAQ Category', 'open-agency-elements' ),
            'search_items'      => __( 'Search FAQ Categories', 'open-agency-elements' ),
            'all_items'         => __( 'All FAQ Categories', 'open-agency-elements' ),
            'parent_item'       => __( 'Parent FAQ Category', 'open-agency-elements' ),
            'parent_item_colon' => __( 'Parent FAQ Category:', 'open-agency-elements' ),
            'edit_item'         => __( 'Edit FAQ Category', 'open-agency-elements' ),
            'update_item'       => __( 'Update FAQ Category', 'open-agency-elements' ),
            'add_new_item'      => __( 'Add New FAQ Category', 'open-agency-elements' ),
            'new_item_name'     => __( 'New FAQ Category Name', 'open-agency-elements' ),
            'menu_name'         => __( 'Categories', 'open-agency-elements' ),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'      => true, // Enable Gutenberg/REST API support
        );
        
        register_taxonomy( 'faq_category', array( 'oa_faq' ), $args );
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'oa_faq_meta_box',
            __( 'FAQ Settings', 'open-agency-elements' ),
            array( $this, 'render_meta_box' ),
            'oa_faq',
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'oa_faq_meta_box', 'oa_faq_meta_box_nonce' );
        
        $is_expanded = get_post_meta( $post->ID, '_oa_faq_expanded', true );
        $content_source = get_post_meta( $post->ID, '_oa_faq_content_source', true );
        $selected_page_id = get_post_meta( $post->ID, '_oa_faq_selected_page', true );
        
        // Default to 'direct' if not set
        if ( empty( $content_source ) ) {
            $content_source = 'direct';
        }
        
        ?>
        <p>
            <label for="oa_faq_expanded">
                <input type="checkbox" id="oa_faq_expanded" name="oa_faq_expanded" value="1" <?php checked( $is_expanded, '1' ); ?> />
                <?php _e( 'Expanded by default', 'open-agency-elements' ); ?>
            </label>
        </p>
        
        <hr style="margin: 20px 0;">
        
        <p>
            <label for="oa_faq_content_source"><?php _e( 'Content Source:', 'open-agency-elements' ); ?></label>
            <select id="oa_faq_content_source" name="oa_faq_content_source" style="width: 100%; margin-top: 5px;">
                <option value="direct" <?php selected( $content_source, 'direct' ); ?>><?php _e( 'Direct content (use editor below)', 'open-agency-elements' ); ?></option>
                <option value="page" <?php selected( $content_source, 'page' ); ?>><?php _e( 'From selected page', 'open-agency-elements' ); ?></option>
            </select>
        </p>
        
        <div id="oa_faq_page_selection" style="<?php echo ( $content_source === 'page' ) ? 'display: block;' : 'display: none;'; ?> margin-top: 10px;">
            <p>
                <label for="oa_faq_selected_page"><?php _e( 'Select Page:', 'open-agency-elements' ); ?></label>
                <?php
                wp_dropdown_pages( array(
                    'name'              => 'oa_faq_selected_page',
                    'id'                => 'oa_faq_selected_page',
                    'selected'          => $selected_page_id,
                    'show_option_none'  => __( '— Select a page —', 'open-agency-elements' ),
                    'option_none_value' => '',
                    'style'             => 'width: 100%; margin-top: 5px;',
                ) );
                ?>
            </p>
            <?php if ( ! empty( $selected_page_id ) ) : ?>
                <p style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #0073aa;">
                    <strong><?php _e( 'Selected Page:', 'open-agency-elements' ); ?></strong><br>
                    <?php echo esc_html( get_the_title( $selected_page_id ) ); ?><br>
                    <a href="<?php echo esc_url( get_edit_post_link( $selected_page_id ) ); ?>" target="_blank" style="font-size: 12px;">
                        <?php _e( 'Edit this page', 'open-agency-elements' ); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#oa_faq_content_source').on('change', function() {
                var source = $(this).val();
                if (source === 'page') {
                    $('#oa_faq_page_selection').show();
                } else {
                    $('#oa_faq_page_selection').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes( $post_id ) {
        // Check if nonce is valid
        if ( ! isset( $_POST['oa_faq_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['oa_faq_meta_box_nonce'], 'oa_faq_meta_box' ) ) {
            return;
        }
        
        // Check if user has permissions to save data
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Check if not an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Save expanded state
        $is_expanded = isset( $_POST['oa_faq_expanded'] ) ? '1' : '0';
        update_post_meta( $post_id, '_oa_faq_expanded', $is_expanded );
        
        // Save content source
        if ( isset( $_POST['oa_faq_content_source'] ) ) {
            $content_source = sanitize_text_field( $_POST['oa_faq_content_source'] );
            update_post_meta( $post_id, '_oa_faq_content_source', $content_source );
        }
        
        // Save selected page
        if ( isset( $_POST['oa_faq_selected_page'] ) ) {
            $selected_page_id = intval( $_POST['oa_faq_selected_page'] );
            if ( $selected_page_id > 0 ) {
                update_post_meta( $post_id, '_oa_faq_selected_page', $selected_page_id );
            } else {
                delete_post_meta( $post_id, '_oa_faq_selected_page' );
            }
        }
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns( $columns ) {
        $new_columns = array();
        
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            
            if ( $key === 'title' ) {
                $new_columns['faq_preview'] = __( 'Preview', 'open-agency-elements' );
                $new_columns['faq_order'] = __( 'Order', 'open-agency-elements' );
                $new_columns['faq_content_source'] = __( 'Content Source', 'open-agency-elements' );
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns( $column, $post_id ) {
        // Get the post object to access menu_order
        $post = get_post( $post_id );
        
        // Prevent duplicate rendering
        static $rendered_columns = array();
        $column_key = $post_id . '_' . $column;
        
        if ( isset( $rendered_columns[ $column_key ] ) ) {
            return;
        }
        
        $rendered_columns[ $column_key ] = true;
        
        switch ( $column ) {
            case 'faq_preview':
                $content = get_post_field( 'post_content', $post_id );
                if ( ! empty( $content ) ) {
                    // Strip HTML tags and get clean text
                    $clean_content = wp_strip_all_tags( $content );
                    $preview = wp_trim_words( $clean_content, 20, '...' );
                    echo '<div style="font-size: 13px; line-height: 1.4; color: #666;">' . esc_html( $preview ) . '</div>';
                } else {
                    echo '<span style="color: #999; font-style: italic;">' . esc_html__( 'No content', 'open-agency-elements' ) . '</span>';
                }
                break;
                
            case 'faq_order':
                $order = $post->menu_order;
                echo esc_html( $order ? $order : '0' );
                break;
                
            case 'faq_content_source':
                $content_source = get_post_meta( $post_id, '_oa_faq_content_source', true );
                if ( empty( $content_source ) ) {
                    $content_source = 'direct';
                }
                
                if ( $content_source === 'page' ) {
                    $selected_page_id = get_post_meta( $post_id, '_oa_faq_selected_page', true );
                    if ( ! empty( $selected_page_id ) ) {
                        $page_title = get_the_title( $selected_page_id );
                        if ( ! empty( $page_title ) ) {
                            echo '<span style="color: #0073aa;">' . esc_html__( 'Page:', 'open-agency-elements' ) . '</span><br>';
                            echo '<span style="font-size: 12px;">' . esc_html( $page_title ) . '</span>';
                        } else {
                            echo '<span style="color: #d63638;">' . esc_html__( 'Page: Invalid page', 'open-agency-elements' ) . '</span>';
                        }
                    } else {
                        echo '<span style="color: #d63638;">' . esc_html__( 'Page: No page selected', 'open-agency-elements' ) . '</span>';
                    }
                } else {
                    echo '<span style="color: #00a32a;">' . esc_html__( 'Direct Content', 'open-agency-elements' ) . '</span>';
                }
                break;
        }
    }
}

