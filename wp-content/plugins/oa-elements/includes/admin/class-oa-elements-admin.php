<?php
/**
 * Admin class for Open Agency Elements
 *
 * @package OpenAgencyElements
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OA Elements Admin Class
 */
class OA_Elements_Admin {
    
    /**
     * Settings groups for rendering
     */
    private $settings_groups = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 100 );
        add_filter( 'plugin_action_links_' . OA_ELEMENTS_PLUGIN_BASENAME, array( $this, 'add_plugin_links' ) );
        add_action( 'admin_head', array( $this, 'admin_styles' ) );
        add_action( 'admin_footer', array( $this, 'admin_scripts' ) );
    }
    

    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Open Agency Elements', 'open-agency-elements' ),
            __( 'OA Elements', 'open-agency-elements' ),
            'manage_options',
            'oa_elements',
            array( $this, 'render_settings_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting( 'oa_elements_options', 'oa_elements_options', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );
        
        // Register individual settings
        $this->register_feature_settings();
        $this->register_links_settings();
        
        // Add settings section
        add_settings_section(
            'oa_elements_section',
            __( 'Feature Settings', 'open-agency-elements' ),
            array( $this, 'section_description' ),
            'oa_elements'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Register feature settings
     */
    private function register_feature_settings() {
        $features = array(
            'oa_enable_site_loader',
            'oa_enable_smooth_scroll',
            'oa_enable_button_shortcode',
            'oa_enable_slider_shortcode',
            'oa_enable_logo_carousel',
            'oa_enable_logo_grid',
            'oa_enable_brand_logo_carousel',
            'oa_enable_testimonial_carousel',
            'oa_enable_featured_products_carousel',
            'oa_enable_links_element',
            'oa_enable_features_shortcode',
            'oa_enable_faq_shortcode',
        );
        
        foreach ( $features as $feature ) {
            register_setting( 'oa_elements_options', $feature );
        }
        
        // Register site loader specific settings
        register_setting( 'oa_elements_options', 'oa_site_loader_bg_color' );
        register_setting( 'oa_elements_options', 'oa_site_loader_icon' );
        register_setting( 'oa_elements_options', 'oa_site_loader_spin' );
    }
    
    /**
     * Register links settings
     */
    private function register_links_settings() {
        register_setting( 'oa_elements_options', 'oa_links_social_enabled' );
        register_setting( 'oa_elements_options', 'oa_links_social_urls' );
        register_setting( 'oa_elements_options', 'oa_links_show_account' );
        register_setting( 'oa_elements_options', 'oa_links_show_cart' );
        register_setting( 'oa_elements_options', 'oa_links_show_mini_cart' );
        
        // Site loader settings
        register_setting( 'oa_elements_options', 'oa_site_loader_bg_color' );
        register_setting( 'oa_elements_options', 'oa_site_loader_icon' );
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // Group settings by category
        $settings_groups = array(
            'site_loader' => array(
                'title' => __( 'Site Loader', 'open-agency-elements' ),
                'description' => __( 'Full-screen loading animation', 'open-agency-elements' ),
                'fields' => array(
                    'oa_enable_site_loader' => array(
                        'label' => __( 'Site Loader', 'open-agency-elements' ),
                        'description' => __( 'Enable full-screen site loader', 'open-agency-elements' ),
                        'type' => 'checkbox',
                    ),
                ),
            ),
            'navigation' => array(
                'title' => __( 'Navigation & Scrolling', 'open-agency-elements' ),
                'description' => __( 'Navigation and scroll behavior settings', 'open-agency-elements' ),
                'fields' => array(
                    'oa_enable_smooth_scroll' => array(
                        'label' => __( 'Smooth Scroll', 'open-agency-elements' ),
                        'description' => __( 'Enable smooth scrolling to anchor links', 'open-agency-elements' ),
                        'type' => 'checkbox',
                    ),
                ),
            ),
            'shortcodes' => array(
                'title' => __( 'Shortcodes', 'open-agency-elements' ),
                'description' => __( 'Enable individual shortcode features', 'open-agency-elements' ),
                'fields' => array(
                    'oa_enable_button_shortcode' => array(
                        'label' => __( 'Button Shortcode', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_button] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_button link="https://example.com" target-blank="yes" outline="no" text="Click here"]',
                    ),
                    'oa_enable_slider_shortcode' => array(
                        'label' => __( 'Slider Shortcode', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_slider] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_slider category="your-category" height="100"]',
                        'post_type' => __( 'Sliders', 'open-agency-elements' ),
                    ),
                    'oa_enable_logo_carousel' => array(
                        'label' => __( 'Logo Carousel', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_logo_carousel] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_logo_carousel category="your-category"]',
                        'post_type' => __( 'Logos', 'open-agency-elements' ),
                    ),
                    'oa_enable_logo_grid' => array(
                        'label' => __( 'Logo Grid', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_logo_grid] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_logo_grid category="your-category" column_width="3"]',
                    ),
                    'oa_enable_brand_logo_carousel' => array(
                        'label' => __( 'Brand Logo Carousel', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_brand_logo_carousel] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_brand_logo_carousel]',
                    ),
                    'oa_enable_testimonial_carousel' => array(
                        'label' => __( 'Testimonial Carousel', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_testimonial_carousel] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_testimonial_carousel category="your-category" alignment="center"]',
                        'post_type' => __( 'Testimonials', 'open-agency-elements' ),
                    ),
                    'oa_enable_featured_products_carousel' => array(
                        'label' => __( 'Featured Products Carousel', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_featured_products_carousel] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_featured_products_carousel columns="3"]',
                    ),
                    'oa_enable_links_element' => array(
                        'label' => __( 'Links Element', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_links] shortcode', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_links icon_color="#333" icon_hover_color="#666"]',
                    ),
                    'oa_enable_features_shortcode' => array(
                        'label' => __( 'Features Shortcode', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_features] shortcode (auto-detects current post)', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_features] (auto-detects post) or [oa_features category="your-category"]',
                        'post_type' => __( 'Features', 'open-agency-elements' ),
                    ),
                    'oa_enable_faq_shortcode' => array(
                        'label' => __( 'FAQ Shortcode', 'open-agency-elements' ),
                        'description' => __( 'Enable [oa_faq] shortcode with accordion layout (auto-detects current post)', 'open-agency-elements' ),
                        'type' => 'checkbox',
                        'shortcode' => '[oa_faq] (auto-detects post) or [oa_faq category="your-category"]',
                        'post_type' => __( 'FAQs', 'open-agency-elements' ),
                    ),
                ),
            ),
        );
        
        // Store groups for rendering
        $this->settings_groups = $settings_groups;
        
        // Add all fields to the main section
        foreach ( $settings_groups as $group_key => $group ) {
            foreach ( $group['fields'] as $field_id => $field ) {
                add_settings_field(
                    $field_id,
                    $field['label'],
                    array( $this, 'render_field' ),
                    'oa_elements',
                    'oa_elements_section',
                    array(
                        'field_id' => $field_id,
                        'field' => $field,
                        'group' => $group_key,
                    )
                );
            }
        }
        
        // Add links settings section
        if ( oa_is_feature_enabled( 'oa_enable_links_element' ) ) {
            add_settings_section(
                'oa_links_section',
                __( 'Links Element Settings', 'open-agency-elements' ),
                array( $this, 'links_section_description' ),
                'oa_elements'
            );
            
            $this->add_links_settings_fields();
        }
        
        // Add site loader settings section
        if ( oa_is_feature_enabled( 'oa_enable_site_loader' ) ) {
            add_settings_section(
                'oa_site_loader_section',
                __( 'Site Loader Settings', 'open-agency-elements' ),
                array( $this, 'site_loader_section_description' ),
                'oa_elements'
            );
            
            $this->add_site_loader_settings_fields();
        }
    }
    
    /**
     * Add links settings fields
     */
    private function add_links_settings_fields() {
        // Social links field
        add_settings_field(
            'oa_links_social',
            __( 'Social Media Links', 'open-agency-elements' ),
            array( $this, 'render_social_links_field' ),
            'oa_elements',
            'oa_links_section'
        );
        
        // Account link field
        add_settings_field(
            'oa_links_show_account',
            __( 'Show Account Link', 'open-agency-elements' ),
            array( $this, 'render_checkbox_field' ),
            'oa_elements',
            'oa_links_section',
            array(
                'field_id' => 'oa_links_show_account',
                'description' => __( 'Show My Account link (requires WooCommerce)', 'open-agency-elements' ),
            )
        );
        
        // Cart link field
        add_settings_field(
            'oa_links_show_cart',
            __( 'Show Cart Link', 'open-agency-elements' ),
            array( $this, 'render_checkbox_field' ),
            'oa_elements',
            'oa_links_section',
            array(
                'field_id' => 'oa_links_show_cart',
                'description' => __( 'Show Cart link (requires WooCommerce)', 'open-agency-elements' ),
            )
        );
        
        // Mini cart field
        add_settings_field(
            'oa_links_show_mini_cart',
            __( 'Show Mini Cart', 'open-agency-elements' ),
            array( $this, 'render_checkbox_field' ),
            'oa_elements',
            'oa_links_section',
            array(
                'field_id' => 'oa_links_show_mini_cart',
                'description' => __( 'Show Mini Cart button (requires WooCommerce)', 'open-agency-elements' ),
            )
        );
    }
    
    /**
     * Add site loader settings fields
     */
    private function add_site_loader_settings_fields() {
        // Background color field
        add_settings_field(
            'oa_site_loader_bg_color',
            __( 'Background Color', 'open-agency-elements' ),
            array( $this, 'render_color_field' ),
            'oa_elements',
            'oa_site_loader_section',
            array(
                'field_id' => 'oa_site_loader_bg_color',
                'description' => __( 'Choose the background color for the site loader', 'open-agency-elements' ),
                'default' => '#ffffff',
            )
        );
        
        // Loader icon field
        add_settings_field(
            'oa_site_loader_icon',
            __( 'Loader Icon', 'open-agency-elements' ),
            array( $this, 'render_media_field' ),
            'oa_elements',
            'oa_site_loader_section',
            array(
                'field_id' => 'oa_site_loader_icon',
                'description' => __( 'Upload a custom loader icon (optional - will use default spinner if not set)', 'open-agency-elements' ),
            )
        );
        
        // Spin loader checkbox
        add_settings_field(
            'oa_site_loader_spin',
            __( 'Spin Loader', 'open-agency-elements' ),
            array( $this, 'render_checkbox_field' ),
            'oa_elements',
            'oa_site_loader_section',
            array(
                'field_id' => 'oa_site_loader_spin',
                'description' => __( 'Enable spinning animation for the uploaded loader icon', 'open-agency-elements' ),
            )
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="oa-admin-header">
                <p><?php _e( 'Configure which elements to enable for your website. All features are disabled by default for optimal performance. Disabled features will not load any scripts or styles.', 'open-agency-elements' ); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'oa_elements_options' );
                ?>
                
                <div class="oa-settings-groups">
                    <?php foreach ( $this->settings_groups as $group_key => $group ) : ?>
                        <div class="oa-settings-group">
                            <div class="oa-settings-group-header">
                                <h3><?php echo esc_html( $group['title'] ); ?></h3>
                                <p><?php echo esc_html( $group['description'] ); ?></p>
                            </div>
                            <div class="oa-settings-group-content">
                                <?php
                                foreach ( $group['fields'] as $field_id => $field ) {
                                    $this->render_grouped_field( $field_id, $field );
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Site Loader Settings -->
                <?php if ( oa_is_feature_enabled( 'oa_enable_site_loader' ) ) : ?>
                    <div class="oa-settings-group">
                        <div class="oa-settings-group-header">
                            <h3><?php _e( 'Site Loader Settings', 'open-agency-elements' ); ?></h3>
                            <p><?php _e( 'Configure the site loader appearance and behavior.', 'open-agency-elements' ); ?></p>
                        </div>
                        <div class="oa-settings-group-content">
                            <?php $this->render_site_loader_settings(); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Links Element Settings -->
                <?php if ( oa_is_feature_enabled( 'oa_enable_links_element' ) ) : ?>
                    <div class="oa-settings-group">
                        <div class="oa-settings-group-header">
                            <h3><?php _e( 'Links Element Settings', 'open-agency-elements' ); ?></h3>
                            <p><?php _e( 'Configure the Links Element shortcode settings.', 'open-agency-elements' ); ?></p>
                        </div>
                        <div class="oa-settings-group-content">
                            <?php $this->render_links_settings(); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="oa-admin-footer">
                <h3><?php _e( 'Shortcode Examples', 'open-agency-elements' ); ?></h3>
                <p><?php _e( 'Use these shortcodes in your posts, pages, or widgets:', 'open-agency-elements' ); ?></p>
                <div class="oa-shortcode-examples">
                    <div class="oa-example">
                        <h4><?php _e( 'Button', 'open-agency-elements' ); ?></h4>
                        <code>[oa_button link="https://example.com" target-blank="yes" outline="no" text="Click here"]</code>
                    </div>
                    <div class="oa-example">
                        <h4><?php _e( 'Slider', 'open-agency-elements' ); ?></h4>
                        <code>[oa_slider category="homepage" height="100"]</code>
                    </div>
                    <div class="oa-example">
                        <h4><?php _e( 'Logo Carousel', 'open-agency-elements' ); ?></h4>
                        <code>[oa_logo_carousel category="partners"]</code>
                    </div>
                    <div class="oa-example">
                        <h4><?php _e( 'Logo Grid', 'open-agency-elements' ); ?></h4>
                        <code>[oa_logo_grid category="partners" column_width="4"]</code>
                    </div>
                    <div class="oa-example">
                        <h4><?php _e( 'Testimonials', 'open-agency-elements' ); ?></h4>
                        <code>[oa_testimonial_carousel category="reviews" alignment="center"]</code>
                    </div>
                    <div class="oa-example">
                        <h4><?php _e( 'Features', 'open-agency-elements' ); ?></h4>
                        <code>[oa_features]</code>
                        <p class="oa-example-description"><?php _e( 'Auto-detects current post and displays features. Also supports:', 'open-agency-elements' ); ?></p>
                        <ul class="oa-example-list">
                            <li><code>[oa_features align="center"]</code> - <?php _e( 'Center alignment', 'open-agency-elements' ); ?></li>
                            <li><code>[oa_features category="product-highlights"]</code> - <?php _e( 'Specific category', 'open-agency-elements' ); ?></li>
                            <li><code>[oa_features columns="4"]</code> - <?php _e( '4 columns layout', 'open-agency-elements' ); ?></li>
                            <li><code>[oa_features columns="4" max_width="300px"]</code> - <?php _e( '4 columns with max width', 'open-agency-elements' ); ?></li>
                            <li><code>[oa_features max_width=""]</code> - <?php _e( 'Auto sizing', 'open-agency-elements' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section description
     */
    public function section_description() {
        echo '<p>' . __( 'Select which elements to enable. All features are disabled by default for optimal performance. Each feature can be toggled on/off as needed.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Links section description
     */
    public function links_section_description() {
        echo '<p>' . __( 'Configure the Links Element shortcode settings.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Site loader section description
     */
    public function site_loader_section_description() {
        echo '<p>' . __( 'Configure the site loader appearance and behavior.', 'open-agency-elements' ) . '</p>';
    }
    
    /**
     * Render field
     */
    public function render_field( $args ) {
        $field_id = $args['field_id'];
        $field = $args['field'];
        $value = get_option( $field_id, false );
        
        ?>
        <label>
            <input type="checkbox" 
                   id="<?php echo esc_attr( $field_id ); ?>" 
                   name="<?php echo esc_attr( $field_id ); ?>" 
                   value="1" 
                   <?php checked( 1, $value ); ?> />
            <?php echo esc_html( $field['description'] ); ?>
        </label>
        
        <?php if ( isset( $field['shortcode'] ) ) : ?>
            <div class="oa-shortcode-example">
                <input type="text" readonly value="<?php echo esc_attr( $field['shortcode'] ); ?>" />
                <button type="button" class="oa-copy-btn"><?php _e( 'Copy', 'open-agency-elements' ); ?></button>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render grouped field
     */
    public function render_grouped_field( $field_id, $field ) {
        $value = get_option( $field_id, false );
        
        ?>
        <div class="oa-settings-field">
            <label>
                <input type="checkbox" 
                       id="<?php echo esc_attr( $field_id ); ?>" 
                       name="<?php echo esc_attr( $field_id ); ?>" 
                       value="1" 
                       <?php checked( 1, $value ); ?> />
                <?php echo esc_html( $field['description'] ); ?>
                <?php if ( isset( $field['post_type'] ) ) : ?>
                    <span class="oa-post-type-indicator">(<?php printf( __( 'Adds %s post type', 'open-agency-elements' ), esc_html( $field['post_type'] ) ); ?>)</span>
                <?php endif; ?>
            </label>
            
            <?php if ( isset( $field['shortcode'] ) ) : ?>
                <div class="oa-shortcode-example">
                    <input type="text" readonly value="<?php echo esc_attr( $field['shortcode'] ); ?>" />
                    <button type="button" class="oa-copy-btn"><?php _e( 'Copy', 'open-agency-elements' ); ?></button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render site loader settings
     */
    public function render_site_loader_settings() {
        $bg_color = get_option( 'oa_site_loader_bg_color', '#ffffff' );
        $loader_icon = get_option( 'oa_site_loader_icon', '' );
        $spin_enabled = get_option( 'oa_site_loader_spin', false );
        
        ?>
        <div class="oa-settings-field">
            <label for="oa_site_loader_bg_color"><?php _e( 'Background Color:', 'open-agency-elements' ); ?></label>
            <input type="color" 
                   id="oa_site_loader_bg_color" 
                   name="oa_site_loader_bg_color" 
                   value="<?php echo esc_attr( $bg_color ); ?>" />
            <p class="description"><?php _e( 'Choose the background color for the site loader', 'open-agency-elements' ); ?></p>
        </div>
        
        <div class="oa-settings-field">
            <label for="oa_site_loader_icon"><?php _e( 'Loader Icon:', 'open-agency-elements' ); ?></label>
            <div class="oa-media-field">
                <input type="hidden" 
                       id="oa_site_loader_icon" 
                       name="oa_site_loader_icon" 
                       value="<?php echo esc_attr( $loader_icon ); ?>" />
                
                <div class="oa-media-preview">
                    <?php if ( ! empty( $loader_icon ) ) : ?>
                        <img src="<?php echo esc_url( $loader_icon ); ?>" alt="Preview" style="max-width: 100px; height: auto;" />
                    <?php endif; ?>
                </div>
                
                <button type="button" class="button oa-media-upload-btn">
                    <?php echo empty( $loader_icon ) ? __( 'Upload Image', 'open-agency-elements' ) : __( 'Change Image', 'open-agency-elements' ); ?>
                </button>
                
                <?php if ( ! empty( $loader_icon ) ) : ?>
                    <button type="button" class="button oa-media-remove-btn">
                        <?php _e( 'Remove', 'open-agency-elements' ); ?>
                    </button>
                <?php endif; ?>
                
                <p class="description"><?php _e( 'Upload a custom loader icon (optional - will use default spinner if not set)', 'open-agency-elements' ); ?></p>
            </div>
        </div>
        
        <div class="oa-settings-field">
            <label>
                <input type="checkbox" 
                       id="oa_site_loader_spin" 
                       name="oa_site_loader_spin" 
                       value="1" 
                       <?php checked( 1, $spin_enabled ); ?> />
                <?php _e( 'Spin Loader', 'open-agency-elements' ); ?>
            </label>
            <p class="description"><?php _e( 'Enable spinning animation for the uploaded loader icon', 'open-agency-elements' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render links settings
     */
    public function render_links_settings() {
        $enabled = (array) get_option( 'oa_links_social_enabled', array() );
        $urls = (array) get_option( 'oa_links_social_urls', array() );
        $platforms = oa_get_social_platforms();
        $show_account = get_option( 'oa_links_show_account', false );
        $show_cart = get_option( 'oa_links_show_cart', false );
        $show_mini_cart = get_option( 'oa_links_show_mini_cart', false );
        
        ?>
        <div class="oa-settings-field">
            <label><?php _e( 'Social Media Links:', 'open-agency-elements' ); ?></label>
            <p class="description"><?php _e( 'Check to enable then enter URL:', 'open-agency-elements' ); ?></p>
            
            <table class="form-table oa-social-links-table">
                <?php foreach ( $platforms as $key => $label ) : ?>
                    <?php 
                    $checked = in_array( $key, $enabled, true ) ? ' checked' : '';
                    $url = isset( $urls[ $key ] ) ? esc_attr( $urls[ $key ] ) : '';
                    ?>
                    <tr>
                        <th scope="row">
                            <label>
                                <input type="checkbox" name="oa_links_social_enabled[]" value="<?php echo esc_attr( $key ); ?>"<?php echo $checked; ?> />
                                <?php echo esc_html( $label ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" name="oa_links_social_urls[<?php echo esc_attr( $key ); ?>]" value="<?php echo $url; ?>" placeholder="https://" class="regular-text" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="oa-settings-field">
            <label>
                <input type="checkbox" 
                       id="oa_links_show_account" 
                       name="oa_links_show_account" 
                       value="1" 
                       <?php checked( 1, $show_account ); ?> />
                <?php _e( 'Show Account Link', 'open-agency-elements' ); ?>
            </label>
            <p class="description"><?php _e( 'Show My Account link (requires WooCommerce)', 'open-agency-elements' ); ?></p>
        </div>
        
        <div class="oa-settings-field">
            <label>
                <input type="checkbox" 
                       id="oa_links_show_cart" 
                       name="oa_links_show_cart" 
                       value="1" 
                       <?php checked( 1, $show_cart ); ?> />
                <?php _e( 'Show Cart Link', 'open-agency-elements' ); ?>
            </label>
            <p class="description"><?php _e( 'Show Cart link (requires WooCommerce)', 'open-agency-elements' ); ?></p>
        </div>
        
        <div class="oa-settings-field">
            <label>
                <input type="checkbox" 
                       id="oa_links_show_mini_cart" 
                       name="oa_links_show_mini_cart" 
                       value="1" 
                       <?php checked( 1, $show_mini_cart ); ?> />
                <?php _e( 'Show Mini Cart', 'open-agency-elements' ); ?>
            </label>
            <p class="description"><?php _e( 'Show Mini Cart button (requires WooCommerce)', 'open-agency-elements' ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field( $args ) {
        $field_id = $args['field_id'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $value = get_option( $field_id, false );
        
        ?>
        <label>
            <input type="checkbox" 
                   id="<?php echo esc_attr( $field_id ); ?>" 
                   name="<?php echo esc_attr( $field_id ); ?>" 
                   value="1" 
                   <?php checked( 1, $value ); ?> />
            <?php echo esc_html( $description ); ?>
        </label>
        <?php
    }
    
    /**
     * Render social links field
     */
    public function render_social_links_field() {
        $enabled = (array) get_option( 'oa_links_social_enabled', array() );
        $urls = (array) get_option( 'oa_links_social_urls', array() );
        $platforms = oa_get_social_platforms();
        
        echo '<p>' . __( 'Check to enable then enter URL:', 'open-agency-elements' ) . '</p>';
        echo '<table class="form-table">';
        
        foreach ( $platforms as $key => $label ) {
            $checked = in_array( $key, $enabled, true ) ? ' checked' : '';
            $url = isset( $urls[ $key ] ) ? esc_attr( $urls[ $key ] ) : '';
            
            echo '<tr>';
            echo '<th scope="row">';
            echo '<label>';
            echo '<input type="checkbox" name="oa_links_social_enabled[]" value="' . esc_attr( $key ) . '"' . $checked . '/> ';
            echo esc_html( $label );
            echo '</label>';
            echo '</th>';
            echo '<td>';
            echo '<input type="url" name="oa_links_social_urls[' . esc_attr( $key ) . ']" value="' . $url . '" placeholder="https://" class="regular-text" />';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Render color field
     */
    public function render_color_field( $args ) {
        $field_id = $args['field_id'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $default = isset( $args['default'] ) ? $args['default'] : '#ffffff';
        $value = get_option( $field_id, $default );
        
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
                <?php echo esc_html( $description ); ?>
                <br>
                <small><?php _e( 'Use the color picker, enter a hex color, or click a preset color', 'open-agency-elements' ); ?></small>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render media field
     */
    public function render_media_field( $args ) {
        $field_id = $args['field_id'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $value = get_option( $field_id, '' );
        
        ?>
        <div class="oa-media-field">
            <input type="hidden" 
                   id="<?php echo esc_attr( $field_id ); ?>" 
                   name="<?php echo esc_attr( $field_id ); ?>" 
                   value="<?php echo esc_attr( $value ); ?>" />
            
            <div class="oa-media-preview">
                <?php if ( ! empty( $value ) ) : ?>
                    <img src="<?php echo esc_url( $value ); ?>" alt="Preview" style="max-width: 100px; height: auto;" />
                <?php endif; ?>
            </div>
            
            <button type="button" class="button oa-media-upload-btn">
                <?php echo empty( $value ) ? __( 'Upload Image', 'open-agency-elements' ) : __( 'Change Image', 'open-agency-elements' ); ?>
            </button>
            
            <?php if ( ! empty( $value ) ) : ?>
                <button type="button" class="button oa-media-remove-btn">
                    <?php _e( 'Remove', 'open-agency-elements' ); ?>
                </button>
            <?php endif; ?>
            
            <p class="description"><?php echo esc_html( $description ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        // Sanitize checkboxes
        $checkbox_fields = array(
            'oa_enable_smooth_scroll',
            'oa_enable_button_shortcode',
            'oa_enable_slider_shortcode',
            'oa_enable_logo_carousel',
            'oa_enable_logo_grid',
            'oa_enable_brand_logo_carousel',
            'oa_enable_testimonial_carousel',
            'oa_enable_featured_products_carousel',
            'oa_enable_links_element',
            'oa_links_show_account',
            'oa_links_show_cart',
            'oa_links_show_mini_cart',
        );
        
        foreach ( $checkbox_fields as $field ) {
            $sanitized[ $field ] = isset( $input[ $field ] ) ? 1 : 0;
        }
        
        // Sanitize color fields
        $color_fields = array(
            'oa_site_loader_bg_color',
        );
        
        foreach ( $color_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $color = oa_sanitize_hex_color( $input[ $field ] );
                if ( $color ) {
                    $sanitized[ $field ] = $color;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Admin styles
     */
    public function admin_styles() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->id === 'settings_page_oa_elements' ) {
            // Enqueue media uploader
            wp_enqueue_media();
            ?>
            <style>
                .oa-admin-footer {
                    background: #fff;
                    padding: 20px;
                    margin: 20px 0;
                    border: 1px solid #ddd;
                }
                
                /* Settings Groups Styling */
                .oa-settings-groups {
                    margin: 20px 0;
                }
                
                .oa-settings-group {
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin-bottom: 20px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                
                .oa-settings-group-header {
                    background: #f9f9f9;
                    padding: 15px 20px;
                    border-bottom: 1px solid #ddd;
                    border-radius: 4px 4px 0 0;
                }
                
                .oa-settings-group-header h3 {
                    margin: 0 0 5px 0;
                    font-size: 16px;
                    font-weight: 600;
                    color: #333;
                }
                
                .oa-settings-group-header p {
                    margin: 0;
                    font-size: 13px;
                    color: #666;
                }
                
                .oa-settings-group-content {
                    padding: 20px;
                }
                
                .oa-settings-field {
                    margin-bottom: 15px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #f0f0f0;
                }
                
                .oa-settings-field:last-child {
                    margin-bottom: 0;
                    padding-bottom: 0;
                    border-bottom: none;
                }
                
                .oa-settings-field label {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    font-weight: 500;
                    cursor: pointer;
                }
                
                .oa-settings-field input[type="checkbox"] {
                    margin-top: 2px;
                }
                
                .oa-shortcode-example {
                    margin-top: 10px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .oa-shortcode-example input {
                    flex: 1;
                    max-width: 400px;
                    padding: 8px;
                    font-family: monospace;
                    font-size: 12px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                }
                
                .oa-copy-btn {
                    padding: 8px 12px;
                    background: #0073aa;
                    color: #fff;
                    border: none;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 12px;
                    transition: background-color 0.2s ease;
                }
                
                .oa-copy-btn:hover {
                    background: #005a87;
                }
                
                .oa-shortcode-examples {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                
                .oa-example {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 3px;
                    border: 1px solid #e5e5e5;
                }
                
                .oa-example h4 {
                    margin-top: 0;
                    margin-bottom: 10px;
                    color: #333;
                }
                
                .oa-example code {
                    display: block;
                    background: #fff;
                    padding: 10px;
                    border-radius: 3px;
                    font-size: 12px;
                    word-break: break-all;
                    border: 1px solid #ddd;
                }
                
                .oa-example-description {
                    margin: 10px 0 5px 0;
                    font-size: 13px;
                    color: #666;
                }
                
                .oa-example-list {
                    margin: 5px 0 0 0;
                    padding-left: 20px;
                }
                
                .oa-example-list li {
                    margin-bottom: 5px;
                    font-size: 12px;
                    color: #666;
                }
                
                .oa-example-list code {
                    display: inline;
                    background: #f0f0f0;
                    padding: 2px 4px;
                    border-radius: 2px;
                    font-size: 11px;
                    border: 1px solid #ddd;
                }
                
                .form-table th {
                    width: 200px;
                    padding: 15px 10px 15px 0;
                }
                
                .form-table td {
                    padding: 15px 10px;
                }
                
                .form-table label {
                    font-weight: 500;
                }
                
                /* Social links table */
                .oa-social-links-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                
                .oa-social-links-table th,
                .oa-social-links-table td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                
                .oa-social-links-table th {
                    background: #f9f9f9;
                    font-weight: 500;
                }
                
                .oa-social-links-table input[type="url"] {
                    width: 100%;
                    padding: 6px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                }
                
                /* Media field styling */
                .oa-media-field {
                    margin-top: 10px;
                }
                
                .oa-media-preview {
                    margin-bottom: 10px;
                }
                
                .oa-media-upload-btn,
                .oa-media-remove-btn {
                    margin-right: 10px;
                }
                
                /* Responsive design */
                @media (max-width: 768px) {
                    .oa-shortcode-example {
                        flex-direction: column;
                        align-items: stretch;
                    }
                    
                    .oa-shortcode-example input {
                        max-width: none;
                    }
                    
                    .oa-shortcode-examples {
                        grid-template-columns: 1fr;
                    }
                    
                    .form-table th {
                        width: auto;
                        display: block;
                        padding-bottom: 5px;
                    }
                    
                    .form-table td {
                        display: block;
                        padding-top: 5px;
                    }
                }
                
                /* Testimonial admin columns */
                .column-testimonial_preview {
                    width: 25%;
                    max-width: 300px;
                }
                
                .column-testimonial_preview {
                    font-size: 13px;
                    line-height: 1.4;
                    color: #666;
                }
                
                .column-testimonial_rating {
                    width: 10%;
                }
                
                .column-testimonial_company {
                    width: 15%;
                }
                
                .column-testimonial_category {
                    width: 15%;
                }
                
                /* Responsive adjustments for testimonial columns */
                @media (max-width: 1200px) {
                    .column-testimonial_preview {
                        width: 20%;
                        max-width: 200px;
                    }
                    
                    .column-testimonial_company,
                    .column-testimonial_category {
                        width: 12%;
                    }
                }
                
                @media (max-width: 782px) {
                    .column-testimonial_preview,
                    .column-testimonial_rating,
                    .column-testimonial_company,
                    .column-testimonial_category {
                        display: none;
                    }
                }
            </style>
            <?php
        }
    }
    
    /**
     * Admin scripts
     */
    public function admin_scripts() {
        $screen = get_current_screen();
        
        if ( $screen && $screen->id === 'settings_page_oa_elements' ) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Copy button functionality
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('oa-copy-btn')) {
                        var input = e.target.previousElementSibling;
                        input.select();
                        document.execCommand('copy');
                        
                        var originalText = e.target.textContent;
                        e.target.textContent = '<?php _e( 'Copied!', 'open-agency-elements' ); ?>';
                        
                        setTimeout(function() {
                            e.target.textContent = originalText;
                        }, 2000);
                    }
                });
                
                // Media uploader functionality
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('oa-media-upload-btn')) {
                        e.preventDefault();
                        
                        var field = e.target.closest('.oa-media-field');
                        var input = field.querySelector('input[type="hidden"]');
                        var preview = field.querySelector('.oa-media-preview');
                        var removeBtn = field.querySelector('.oa-media-remove-btn');
                        
                        var frame = wp.media({
                            title: '<?php _e( 'Select Image', 'open-agency-elements' ); ?>',
                            button: {
                                text: '<?php _e( 'Use this image', 'open-agency-elements' ); ?>'
                            },
                            multiple: false
                        });
                        
                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            input.value = attachment.url;
                            
                            if (preview) {
                                preview.innerHTML = '<img src="' + attachment.url + '" alt="Preview" style="max-width: 100px; height: auto;" />';
                            }
                            
                            e.target.textContent = '<?php _e( 'Change Image', 'open-agency-elements' ); ?>';
                            
                            if (!removeBtn) {
                                var newRemoveBtn = document.createElement('button');
                                newRemoveBtn.type = 'button';
                                newRemoveBtn.className = 'button oa-media-remove-btn';
                                newRemoveBtn.textContent = '<?php _e( 'Remove', 'open-agency-elements' ); ?>';
                                field.appendChild(newRemoveBtn);
                            }
                        });
                        
                        frame.open();
                    }
                    
                    if (e.target.classList.contains('oa-media-remove-btn')) {
                        e.preventDefault();
                        
                        var field = e.target.closest('.oa-media-field');
                        var input = field.querySelector('input[type="hidden"]');
                        var preview = field.querySelector('.oa-media-preview');
                        var uploadBtn = field.querySelector('.oa-media-upload-btn');
                        
                        input.value = '';
                        if (preview) {
                            preview.innerHTML = '';
                        }
                        uploadBtn.textContent = '<?php _e( 'Upload Image', 'open-agency-elements' ); ?>';
                        e.target.remove();
                    }
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=oa_elements' ) . '">' . __( 'Settings', 'open-agency-elements' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
    
    /**
     * Add admin bar link
     */
    public function add_admin_bar_link( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $wp_admin_bar->add_node( array(
            'id'    => 'oa-elements-settings',
            'title' => __( 'OA Elements', 'open-agency-elements' ),
            'href'  => admin_url( 'options-general.php?page=oa_elements' ),
            'meta'  => array( 'class' => 'oa-elements-settings-link' )
        ) );
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Post type registration success notice
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'oa_elements' && 
             ( get_post_type_object( 'logo' ) || get_post_type_object( 'testimonial' ) || get_post_type_object( 'oa_slider' ) ) ) {
            ?>
            <div class="notice notice-success">
                <p>
                    <strong><?php _e( 'Open Agency Elements:', 'open-agency-elements' ); ?></strong>
                    <?php _e( 'Custom post types have been successfully registered. You can now create Logos, Testimonials, and Sliders.', 'open-agency-elements' ); ?>
                </p>
            </div>
            <?php
        }
        
        // Fresh installation notice
        if ( oa_is_fresh_installation() && isset( $_GET['page'] ) && $_GET['page'] === 'oa_elements' ) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php _e( 'Open Agency Elements:', 'open-agency-elements' ); ?></strong>
                    <?php _e( 'Welcome! All features are disabled by default for optimal performance. Please enable the features you need in the settings below.', 'open-agency-elements' ); ?>
                </p>
            </div>
            <?php
        }
        
        // Post type notice
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'oa_elements' && isset( $_GET['settings-updated'] ) ) {
            $enabled_post_types = array();
            
            if ( oa_is_feature_enabled( 'oa_enable_logo_carousel' ) ) {
                $enabled_post_types[] = __( 'Logos', 'open-agency-elements' );
            }
            if ( oa_is_feature_enabled( 'oa_enable_testimonial_carousel' ) ) {
                $enabled_post_types[] = __( 'Testimonials', 'open-agency-elements' );
            }
            if ( oa_is_feature_enabled( 'oa_enable_slider_shortcode' ) ) {
                $enabled_post_types[] = __( 'Sliders', 'open-agency-elements' );
            }
            if ( oa_is_feature_enabled( 'oa_enable_features_shortcode' ) ) {
                $enabled_post_types[] = __( 'Features', 'open-agency-elements' );
            }
            
            if ( ! empty( $enabled_post_types ) ) {
                ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e( 'Open Agency Elements:', 'open-agency-elements' ); ?></strong>
                        <?php 
                        printf( 
                            __( 'The following post types are now available in your admin menu: %s', 'open-agency-elements' ),
                            implode( ', ', $enabled_post_types )
                        ); 
                        ?>
                    </p>
                </div>
                <?php
            }
        }
        

        
        // WooCommerce dependency notice
        if ( ( oa_is_feature_enabled( 'oa_enable_featured_products_carousel' ) || 
               oa_is_feature_enabled( 'oa_enable_brand_logo_carousel' ) ||
               oa_is_feature_enabled( 'oa_links_show_account' ) ||
               oa_is_feature_enabled( 'oa_links_show_cart' ) ||
               oa_is_feature_enabled( 'oa_links_show_mini_cart' ) ) && 
             ! oa_is_woocommerce_active() ) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e( 'Open Agency Elements:', 'open-agency-elements' ); ?></strong>
                    <?php _e( 'Some features require WooCommerce to be active.', 'open-agency-elements' ); ?>
                </p>
            </div>
            <?php
        }
    }
} 