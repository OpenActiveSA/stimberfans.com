<?php
/**
 * Feature Post Type Class
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Feature Post Type Class
 */
class OA_Feature_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ), 0 );
        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_filter( 'manage_oa_feature_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_oa_feature_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
    }
    
    /**
     * Register feature post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Features', 'open-agency-elements' ),
            'singular_name'      => __( 'Feature', 'open-agency-elements' ),
            'menu_name'          => __( 'Features', 'open-agency-elements' ),
            'add_new'            => __( 'Add New', 'open-agency-elements' ),
            'add_new_item'       => __( 'Add New Feature', 'open-agency-elements' ),
            'edit_item'          => __( 'Edit Feature', 'open-agency-elements' ),
            'new_item'           => __( 'New Feature', 'open-agency-elements' ),
            'view_item'          => __( 'View Feature', 'open-agency-elements' ),
            'search_items'       => __( 'Search Features', 'open-agency-elements' ),
            'not_found'          => __( 'No features found', 'open-agency-elements' ),
            'not_found_in_trash' => __( 'No features found in trash', 'open-agency-elements' ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 23,
            'menu_icon'           => 'dashicons-star-filled',
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
            'rewrite'             => false,
        );
        
        register_post_type( 'oa_feature', $args );
    }
    
    /**
     * Register feature taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Feature Categories', 'open-agency-elements' ),
            'singular_name'     => __( 'Feature Category', 'open-agency-elements' ),
            'search_items'      => __( 'Search Feature Categories', 'open-agency-elements' ),
            'all_items'         => __( 'All Feature Categories', 'open-agency-elements' ),
            'parent_item'       => __( 'Parent Feature Category', 'open-agency-elements' ),
            'parent_item_colon' => __( 'Parent Feature Category:', 'open-agency-elements' ),
            'edit_item'         => __( 'Edit Feature Category', 'open-agency-elements' ),
            'update_item'       => __( 'Update Feature Category', 'open-agency-elements' ),
            'add_new_item'      => __( 'Add New Feature Category', 'open-agency-elements' ),
            'new_item_name'     => __( 'New Feature Category Name', 'open-agency-elements' ),
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
        
        register_taxonomy( 'feature_category', array( 'oa_feature' ), $args );
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'oa_feature_icon',
            __( 'Feature Icon', 'open-agency-elements' ),
            array( $this, 'render_meta_box' ),
            'oa_feature',
            'normal',
            'high'
        );
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'oa_feature_meta_box', 'oa_feature_meta_box_nonce' );
        
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
                    <label><?php _e( 'Feature Icon', 'open-agency-elements' ); ?></label>
                </th>
                <td>
                    <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
                        <div class="oa-feature-preview">
                            <?php the_post_thumbnail( 'medium' ); ?>
                            <?php if ( $is_svg ) : ?>
                                <p class="description">
                                    <strong><?php _e( 'SVG detected!', 'open-agency-elements' ); ?></strong> 
                                    <?php _e( 'This icon will support dynamic colors on light/dark backgrounds.', 'open-agency-elements' ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <p class="description">
                            <?php _e( 'Set the featured image to display the feature icon. SVG files will support dynamic colors.', 'open-agency-elements' ); ?>
                        </p>
                    <?php endif; ?>
                    <p class="description">
                        <strong><?php _e( 'Tip:', 'open-agency-elements' ); ?></strong> 
                        <?php _e( 'Use the "Set featured image" option above to add your feature icon. SVG files will automatically support color changes.', 'open-agency-elements' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta boxes
     *
     * @param int $post_id Post ID
     */
    public function save_meta_boxes( $post_id ) {
        // Check if nonce is valid
        if ( ! isset( $_POST['oa_feature_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['oa_feature_meta_box_nonce'], 'oa_feature_meta_box' ) ) {
            return;
        }
        
        // Check if user has permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Check if not an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Extract SVG code if an SVG image is uploaded
        $svg_code = '';
        $icon_type = 'image';
        $icon_image = '';
        
        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $file_path = get_attached_file( $thumbnail_id );
            $icon_image = wp_get_attachment_url( $thumbnail_id );
            
            if ( $file_path && pathinfo( $file_path, PATHINFO_EXTENSION ) === 'svg' ) {
                $svg_code = $this->extract_svg_code( $file_path );
                $icon_type = 'svg';
            }
        }
        
        update_post_meta( $post_id, '_oa_feature_icon_type', $icon_type );
        update_post_meta( $post_id, '_oa_feature_icon_svg', $svg_code );
        update_post_meta( $post_id, '_oa_feature_icon_image', $icon_image );
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
        
        // Remove any script tags for security
        $svg_content = preg_replace( '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $svg_content );
        
        // Remove any onclick attributes
        $svg_content = preg_replace( '/onclick\s*=\s*["\'][^"\']*["\']/i', '', $svg_content );
        
        // Remove any onload attributes
        $svg_content = preg_replace( '/onload\s*=\s*["\'][^"\']*["\']/i', '', $svg_content );
        
        // Remove any external references
        $svg_content = preg_replace( '/xlink:href\s*=\s*["\'][^"\']*["\']/i', '', $svg_content );
        
        return $svg_content;
    }
    
    /**
     * Add custom columns to the features list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_custom_columns( $columns ) {
        $new_columns = array();
        
        // Add icon column after title
        foreach ( $columns as $key => $value ) {
            $new_columns[$key] = $value;
            if ( $key === 'title' ) {
                $new_columns['feature_icon'] = __( 'Icon', 'open-agency-elements' );
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_custom_columns( $column, $post_id ) {
        // Prevent duplicate rendering
        static $rendered_columns = array();
        $column_key = $post_id . '_' . $column;
        
        if ( isset( $rendered_columns[ $column_key ] ) ) {
            return;
        }
        
        $rendered_columns[ $column_key ] = true;
        
        if ( $column === 'feature_icon' ) {
            $icon_type = get_post_meta( $post_id, '_oa_feature_icon_type', true );
            $icon_svg = get_post_meta( $post_id, '_oa_feature_icon_svg', true );
            $icon_image = get_post_meta( $post_id, '_oa_feature_icon_image', true );
            
            if ( $icon_type === 'svg' && ! empty( $icon_svg ) ) {
                // Display SVG icon
                echo '<div class="oa-feature-icon-preview" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">';
                echo '<div style="width: 24px; height: 24px; color: #666; display: flex; align-items: center; justify-content: center;">';
                // Add style to force SVG to fit container
                $svg_with_style = preg_replace('/<svg/', '<svg style="width: 100%; height: 100%; max-width: 24px; max-height: 24px;"', $icon_svg);
                echo $svg_with_style;
                echo '</div>';
                echo '</div>';
            } elseif ( ! empty( $icon_image ) ) {
                // Display image icon
                echo '<div class="oa-feature-icon-preview" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">';
                echo '<img src="' . esc_url( $icon_image ) . '" alt="Feature Icon" style="max-width: 24px; max-height: 24px; object-fit: contain;" />';
                echo '</div>';
            } else {
                // No icon
                echo '<span style="color: #999; font-style: italic;">' . __( 'No icon', 'open-agency-elements' ) . '</span>';
            }
        }
    }
} 