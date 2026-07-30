<?php
/**
 * Testimonial Post Type Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Testimonial Post Type Class
 */
class OA_Testimonial_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        
        // Add custom columns for testimonials admin
        add_filter( 'manage_testimonial_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_testimonial_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        add_filter( 'manage_edit-testimonial_sortable_columns', array( $this, 'make_columns_sortable' ) );
    }
    
    /**
     * Register testimonial post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Testimonials', 'open-agency-elements' ),
            'singular_name'      => __( 'Testimonial', 'open-agency-elements' ),
            'menu_name'          => __( 'Testimonials', 'open-agency-elements' ),
            'add_new'            => __( 'Add New', 'open-agency-elements' ),
            'add_new_item'       => __( 'Add New Testimonial', 'open-agency-elements' ),
            'edit_item'          => __( 'Edit Testimonial', 'open-agency-elements' ),
            'new_item'           => __( 'New Testimonial', 'open-agency-elements' ),
            'view_item'          => __( 'View Testimonial', 'open-agency-elements' ),
            'search_items'       => __( 'Search Testimonials', 'open-agency-elements' ),
            'not_found'          => __( 'No testimonials found', 'open-agency-elements' ),
            'not_found_in_trash' => __( 'No testimonials found in trash', 'open-agency-elements' ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-format-quote',
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'rewrite'             => false,
        );
        
        register_post_type( 'testimonial', $args );
    }
    
    /**
     * Register testimonial taxonomy
     */
    public function register_taxonomy() {

        
        $labels = array(
            'name'              => __( 'Testimonial Categories', 'open-agency-elements' ),
            'singular_name'     => __( 'Testimonial Category', 'open-agency-elements' ),
            'search_items'      => __( 'Search Testimonial Categories', 'open-agency-elements' ),
            'all_items'         => __( 'All Testimonial Categories', 'open-agency-elements' ),
            'parent_item'       => __( 'Parent Testimonial Category', 'open-agency-elements' ),
            'parent_item_colon' => __( 'Parent Testimonial Category:', 'open-agency-elements' ),
            'edit_item'         => __( 'Edit Testimonial Category', 'open-agency-elements' ),
            'update_item'       => __( 'Update Testimonial Category', 'open-agency-elements' ),
            'add_new_item'      => __( 'Add New Testimonial Category', 'open-agency-elements' ),
            'new_item_name'     => __( 'New Testimonial Category Name', 'open-agency-elements' ),
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
        
        register_taxonomy( 'testimonial_category', array( 'testimonial' ), $args );
    }
    
    /**
     * Add meta boxes for testimonial post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'oa_testimonial_fields',
            __( 'Testimonial Settings', 'open-agency-elements' ),
            array( $this, 'render_meta_box' ),
            'testimonial',
            'normal',
            'default'
        );
    }
    
    /**
     * Render meta box content
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'oa_testimonial_fields', 'oa_testimonial_fields_nonce' );
        
        $testimonial_rating = get_post_meta( $post->ID, 'testimonial_rating', true );
        $testimonial_company = get_post_meta( $post->ID, 'testimonial_company', true );
        $testimonial_alignment = get_post_meta( $post->ID, 'testimonial_alignment', true );
        
        // Set defaults
        if ( empty( $testimonial_rating ) ) $testimonial_rating = 5;
        if ( empty( $testimonial_alignment ) ) $testimonial_alignment = 'center';
        
        ?>
        <p>
            <label for="oa_testimonial_rating"><?php _e( 'Rating', 'open-agency-elements' ); ?></label><br>
            <select name="oa_testimonial_rating" id="oa_testimonial_rating" class="widefat">
                <option value="1" <?php selected( $testimonial_rating, '1' ); ?>>1 <?php _e( 'Star', 'open-agency-elements' ); ?></option>
                <option value="2" <?php selected( $testimonial_rating, '2' ); ?>>2 <?php _e( 'Stars', 'open-agency-elements' ); ?></option>
                <option value="3" <?php selected( $testimonial_rating, '3' ); ?>>3 <?php _e( 'Stars', 'open-agency-elements' ); ?></option>
                <option value="4" <?php selected( $testimonial_rating, '4' ); ?>>4 <?php _e( 'Stars', 'open-agency-elements' ); ?></option>
                <option value="5" <?php selected( $testimonial_rating, '5' ); ?>>5 <?php _e( 'Stars', 'open-agency-elements' ); ?></option>
            </select>
        </p>
        
        <p>
            <label for="oa_testimonial_company"><?php _e( 'Company', 'open-agency-elements' ); ?></label><br>
            <input type="text" name="oa_testimonial_company" id="oa_testimonial_company" value="<?php echo esc_attr( $testimonial_company ); ?>" class="widefat">
        </p>
        
        <p>
            <label for="oa_testimonial_alignment"><?php _e( 'Content Alignment', 'open-agency-elements' ); ?></label><br>
            <select name="oa_testimonial_alignment" id="oa_testimonial_alignment" class="widefat">
                <option value="left" <?php selected( $testimonial_alignment, 'left' ); ?>><?php _e( 'Left', 'open-agency-elements' ); ?></option>
                <option value="center" <?php selected( $testimonial_alignment, 'center' ); ?>><?php _e( 'Center', 'open-agency-elements' ); ?></option>
                <option value="right" <?php selected( $testimonial_alignment, 'right' ); ?>><?php _e( 'Right', 'open-agency-elements' ); ?></option>
            </select>
        </p>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['oa_testimonial_fields_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['oa_testimonial_fields_nonce'], 'oa_testimonial_fields' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $testimonial_rating = isset( $_POST['oa_testimonial_rating'] ) ? absint( $_POST['oa_testimonial_rating'] ) : 5;
        $testimonial_company = isset( $_POST['oa_testimonial_company'] ) ? sanitize_text_field( $_POST['oa_testimonial_company'] ) : '';
        $testimonial_alignment = isset( $_POST['oa_testimonial_alignment'] ) ? sanitize_text_field( $_POST['oa_testimonial_alignment'] ) : 'center';
        
        update_post_meta( $post_id, 'testimonial_rating', $testimonial_rating );
        update_post_meta( $post_id, 'testimonial_company', $testimonial_company );
        update_post_meta( $post_id, 'testimonial_alignment', $testimonial_alignment );
    }
    
    /**
     * Add custom columns for testimonials admin
     */
    public function add_custom_columns( $columns ) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['testimonial_preview'] = __( 'Preview', 'open-agency-elements' );
        $new_columns['testimonial_rating'] = __( 'Rating', 'open-agency-elements' );
        $new_columns['testimonial_company'] = __( 'Company', 'open-agency-elements' );
        $new_columns['testimonial_category'] = __( 'Category', 'open-agency-elements' );
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Render custom columns for testimonials admin
     */
    public function render_custom_columns( $column, $post_id ) {
        // Prevent duplicate rendering
        static $rendered_columns = array();
        $column_key = $post_id . '_' . $column;
        
        if ( isset( $rendered_columns[ $column_key ] ) ) {
            return;
        }
        
        $rendered_columns[ $column_key ] = true;
        
        switch ( $column ) {
            case 'testimonial_preview':
                $testimonial_content = get_the_excerpt( $post_id );
                if ( empty( $testimonial_content ) ) {
                    $testimonial_content = get_post_field( 'post_content', $post_id );
                }
                $testimonial_content = wp_strip_all_tags( $testimonial_content );
                $testimonial_content = wp_trim_words( $testimonial_content, 35, '...' );
                echo esc_html( $testimonial_content );
                break;
                
            case 'testimonial_rating':
                $testimonial_rating = get_post_meta( $post_id, 'testimonial_rating', true );
                if ( ! empty( $testimonial_rating ) ) {
                    echo esc_html( $testimonial_rating ) . ' ' . __( 'Stars', 'open-agency-elements' );
                } else {
                    echo '&mdash;';
                }
                break;
                
            case 'testimonial_company':
                $testimonial_company = get_post_meta( $post_id, 'testimonial_company', true );
                if ( ! empty( $testimonial_company ) ) {
                    echo esc_html( $testimonial_company );
                } else {
                    echo '&mdash;';
                }
                break;
                
            case 'testimonial_category':
                $terms = get_the_terms( $post_id, 'testimonial_category' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $output = array();
                    foreach ( $terms as $term ) {
                        $output[] = esc_html( $term->name );
                    }
                    echo implode( ', ', $output );
                } else {
                    echo '&mdash;';
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable( $columns ) {
        $columns['testimonial_rating'] = 'testimonial_rating';
        $columns['testimonial_company'] = 'testimonial_company';
        return $columns;
    }
} 