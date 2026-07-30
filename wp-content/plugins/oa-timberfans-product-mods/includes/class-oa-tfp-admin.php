<?php
/**
 * OA TFP Admin Class
 * 
 * Handles all admin-related functionality including meta boxes and media uploaders
 */
class OA_TFP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Taxonomy image fields
        add_action('pa_timber-finish_add_form_fields', array($this, 'add_timber_finish_image_field'), 10, 2);
        add_action('pa_timber-finish_edit_form_fields', array($this, 'edit_timber_finish_image_field'), 10, 2);
        add_action('created_pa_timber-finish', array($this, 'save_timber_finish_image'), 10, 2);
        add_action('edited_pa_timber-finish', array($this, 'save_timber_finish_image'), 10, 2);
        
        // Metal finish image fields
        add_action('pa_metal-finish_add_form_fields', array($this, 'add_metal_finish_image_field'), 10, 2);
        add_action('pa_metal-finish_edit_form_fields', array($this, 'edit_metal_finish_image_field'), 10, 2);
        add_action('created_pa_metal-finish', array($this, 'save_metal_finish_image'), 10, 2);
        add_action('edited_pa_metal-finish', array($this, 'save_metal_finish_image'), 10, 2);
        
        // Increase variations per page in admin
        add_filter('woocommerce_admin_meta_boxes_variations_per_page', array($this, 'increase_variations_per_page'), 10, 1);

    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Product Options Meta Box
        add_meta_box(
            'oa_tfp_product_options_meta',
            __('Product Options', 'oa-tfp'),
            array($this, 'render_product_options_metabox'),
            'product',
            'side',
            'default'
        );
        
    }
    
    /**
     * Render product options meta box
     */
    public function render_product_options_metabox($post) {
        wp_nonce_field('oa_tfp_save_product_options', 'oa_tfp_product_options_nonce');
        
        $banner_id = get_post_meta($post->ID, 'product_banner', true);
        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
        $custom_title = get_post_meta($post->ID, 'product_new_title', true);
        $banner_height = get_post_meta($post->ID, 'product_banner_height', true);
        $brochure_id = get_post_meta($post->ID, 'product_brochure_pdf', true);
        $brochure_url = $brochure_id ? wp_get_attachment_url($brochure_id) : '';
        $static_images = get_post_meta($post->ID, 'oa_tfp_static_images_mode', true);
        
        echo '<div class="oa-tfp-options-container">';
        
        // Banner
        if ($banner_url) {
            echo '<img src="' . esc_url($banner_url) . '" style="max-width:100%; margin-bottom:10px;" />';
        }
        echo '<input type="hidden" id="oa-tfp-product-banner" name="product_banner" value="' . esc_attr($banner_id) . '" />';
        echo '<p><button type="button" class="button" id="oa-tfp-product-banner-upload">' . __('Select Banner', 'oa-tfp') . '</button>
        <button type="button" class="button" id="oa-tfp-product-banner-remove">' . __('Remove Banner', 'oa-tfp') . '</button></p>';
        echo '<hr style="margin:10px 0;">';
        
        // Product Diagram
        $diagram_id = get_post_meta($post->ID, 'product_diagram', true);
        $diagram_url = $diagram_id ? wp_get_attachment_url($diagram_id) : '';
        if ($diagram_url) {
            echo '<img src="' . esc_url($diagram_url) . '" style="max-width:100%; margin-bottom:10px;" />';
        }
        echo '<input type="hidden" id="oa-tfp-product-diagram" name="product_diagram" value="' . esc_attr($diagram_id) . '" />';
        echo '<p><button type="button" class="button" id="oa-tfp-product-diagram-upload">' . __('Select Diagram', 'oa-tfp') . '</button>
        <button type="button" class="button" id="oa-tfp-product-diagram-remove">' . __('Remove Diagram', 'oa-tfp') . '</button></p>';
        echo '<hr style="margin:10px 0;">';
        
        // Custom Title
        echo '<label for="product_new_title"><strong>' . __('Banner Custom Title', 'oa-tfp') . '</strong></label>';
        echo '<textarea style="width:100%;min-height:60px;" name="product_new_title" id="product_new_title">' . esc_textarea($custom_title) . '</textarea>';
        echo '<p class="description">If set, this will replace the product title in the banner. HTML allowed.</p>';
        echo '<hr style="margin:10px 0;">';
        
        // Banner Height
        echo '<label for="product_banner_height"><strong>' . __('Banner Height', 'oa-tfp') . '</strong></label>';
        echo '<input type="text" style="width:100%;" name="product_banner_height" id="product_banner_height" value="' . esc_attr($banner_height) . '" placeholder="500px or 50%" />';
        echo '<p class="description">Set the banner height (e.g., 500px or 50%). Default is 500px if left empty.</p>';
        echo '<hr style="margin:10px 0;">';
        
        // Brochure
        if ($brochure_url) {
            echo '<a href="' . esc_url($brochure_url) . '" target="_blank" style="display:inline-flex;align-items:center;gap:5px;text-decoration:none;color:#0073aa;margin-bottom:10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill:currentColor;">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                </svg>
                Download current brochure
            </a><br />';
        }
        echo '<input type="hidden" id="oa-tfp-product-brochure" name="product_brochure_pdf" value="' . esc_attr($brochure_id) . '" />';
        echo '<p><button type="button" class="button" id="oa-tfp-product-brochure-upload">' . __('Upload Brochure', 'oa-tfp') . '</button>
        <button type="button" class="button" id="oa-tfp-product-brochure-remove">' . __('Remove Brochure', 'oa-tfp') . '</button></p>';
        echo '<hr style="margin:10px 0;">';
        
        // Secondary Gallery
        $secondary_gallery_ids = get_post_meta($post->ID, 'oa_tfp_secondary_gallery', true);
        $secondary_gallery_ids = $secondary_gallery_ids ? explode(',', $secondary_gallery_ids) : array();
        echo '<hr style="margin:10px 0;">';
        echo '<label><strong>' . __('Secondary Gallery', 'oa-tfp') . '</strong></label>';
        echo '<p class="description">Add a secondary gallery of images separate from the default product gallery.</p>';
        echo '<input type="hidden" id="oa-tfp-secondary-gallery" name="oa_tfp_secondary_gallery" value="' . esc_attr(implode(',', $secondary_gallery_ids)) . '" />';
        echo '<div id="oa-tfp-secondary-gallery-preview" class="oa-tfp-sortable-gallery" style="margin:10px 0;display:flex;flex-wrap:wrap;gap:10px;min-height:80px;">';
        if (!empty($secondary_gallery_ids)) {
            foreach ($secondary_gallery_ids as $img_id) {
                $img_id = intval($img_id);
                if ($img_id) {
                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                    if ($img_url) {
                        echo '<div class="oa-tfp-gallery-item" data-id="' . esc_attr($img_id) . '" style="position:relative;width:80px;height:80px;border:1px solid #ddd;border-radius:4px;overflow:hidden;cursor:move;">';
                        echo '<img src="' . esc_url($img_url) . '" style="width:100%;height:100%;object-fit:cover;pointer-events:none;" />';
                        echo '<button type="button" class="oa-tfp-remove-gallery-item" style="position:absolute;top:2px;right:2px;background:rgba(255,0,0,0.8);color:white;border:none;border-radius:2px;cursor:pointer;width:20px;height:20px;line-height:1;font-size:12px;z-index:10;">×</button>';
                        echo '</div>';
                    }
                }
            }
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="oa-tfp-secondary-gallery-upload">' . __('Add Images to Secondary Gallery', 'oa-tfp') . '</button>';
        if (!empty($secondary_gallery_ids)) {
            echo ' <button type="button" class="button" id="oa-tfp-secondary-gallery-clear">' . __('Clear All', 'oa-tfp') . '</button>';
        }
        echo '</p>';
        echo '<hr style="margin:10px 0;">';
        
        // Image Layout Mode (only for variable products)
        $product = wc_get_product($post->ID);
        if ($product && $product->is_type('variable')) {
            $thumbnail_gallery_mode = get_post_meta($post->ID, 'oa_tfp_thumbnail_gallery_mode', true);
            $static_with_gallery = get_post_meta($post->ID, 'oa_tfp_static_with_gallery_mode', true);
            
            // Determine current mode
            $current_mode = 'dynamic';
            if ($static_with_gallery === '1') {
                $current_mode = 'static_with_gallery';
            } elseif ($static_images === '1') {
                $current_mode = 'static';
            } elseif ($thumbnail_gallery_mode === '1') {
                $current_mode = 'thumbnails';
            }
            
            echo '<label for="oa_tfp_image_layout_mode"><strong>' . __('Image Layout', 'oa-tfp') . '</strong></label>';
            echo '<p>';
            echo '<label><input type="radio" name="oa_tfp_image_layout_mode" value="dynamic" ' . checked($current_mode, 'dynamic', false) . ' /> ';
            echo __('Dynamic (images change when variations are selected)', 'oa-tfp') . '</label><br>';
            echo '<label><input type="radio" name="oa_tfp_image_layout_mode" value="static" ' . checked($current_mode, 'static', false) . ' /> ';
            echo __('Static (images don\'t change, use arrows to navigate)', 'oa-tfp') . '</label><br>';
            echo '<label><input type="radio" name="oa_tfp_image_layout_mode" value="static_with_gallery" ' . checked($current_mode, 'static_with_gallery', false) . ' /> ';
            echo __('Static with Gallery (images don\'t change, includes gallery images, use arrows to navigate)', 'oa-tfp') . '</label><br>';
            echo '<label><input type="radio" name="oa_tfp_image_layout_mode" value="thumbnails" ' . checked($current_mode, 'thumbnails', false) . ' /> ';
            echo __('Thumbnail Gallery (show thumbnails below main image)', 'oa-tfp') . '</label>';
            echo '</p>';
            echo '<p class="description">Choose how product images are displayed. Dynamic: images update with variation selection. Static: images stay fixed with navigation arrows (variation images only). Static with Gallery: same as Static but includes gallery images. Thumbnail Gallery: shows all variation and gallery images as clickable thumbnails below the main image.</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Product Options
        if (isset($_POST['oa_tfp_product_options_nonce']) && wp_verify_nonce($_POST['oa_tfp_product_options_nonce'], 'oa_tfp_save_product_options')) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if ('product' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) return;
            
            $banner_id = isset($_POST['product_banner']) ? intval($_POST['product_banner']) : '';
            update_post_meta($post_id, 'product_banner', $banner_id);
            
            $custom_title = isset($_POST['product_new_title']) ? $_POST['product_new_title'] : '';
            update_post_meta($post_id, 'product_new_title', $custom_title);
            
            $banner_height = isset($_POST['product_banner_height']) ? $_POST['product_banner_height'] : '';
            update_post_meta($post_id, 'product_banner_height', $banner_height);
            
            $brochure_id = isset($_POST['product_brochure_pdf']) ? intval($_POST['product_brochure_pdf']) : '';
            update_post_meta($post_id, 'product_brochure_pdf', $brochure_id);
            
            $diagram_id = isset($_POST['product_diagram']) ? intval($_POST['product_diagram']) : '';
            update_post_meta($post_id, 'product_diagram', $diagram_id);
            
            // Handle image layout mode (radio buttons)
            $image_layout_mode = isset($_POST['oa_tfp_image_layout_mode']) ? $_POST['oa_tfp_image_layout_mode'] : 'dynamic';
            
            // Update static images mode
            $static_images = ($image_layout_mode === 'static' || $image_layout_mode === 'static_with_gallery') ? '1' : '0';
            update_post_meta($post_id, 'oa_tfp_static_images_mode', $static_images);
            
            // Update static with gallery mode
            $static_with_gallery = ($image_layout_mode === 'static_with_gallery') ? '1' : '0';
            update_post_meta($post_id, 'oa_tfp_static_with_gallery_mode', $static_with_gallery);
            
            // Update thumbnail gallery mode
            $thumbnail_gallery = ($image_layout_mode === 'thumbnails') ? '1' : '0';
            update_post_meta($post_id, 'oa_tfp_thumbnail_gallery_mode', $thumbnail_gallery);
            
            // Save secondary gallery
            $secondary_gallery = isset($_POST['oa_tfp_secondary_gallery']) ? sanitize_text_field($_POST['oa_tfp_secondary_gallery']) : '';
            update_post_meta($post_id, 'oa_tfp_secondary_gallery', $secondary_gallery);
        }
        
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        // Product edit page
        if ('post.php' === $hook && isset($post->post_type) && 'product' === $post->post_type) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
            add_action('admin_footer', array($this, 'render_product_admin_script'));
        }
        
        // Taxonomy edit pages
        if ((strpos($hook, 'edit-tags.php') !== false || strpos($hook, 'term.php') !== false) && 
            isset($_GET['taxonomy']) && ($_GET['taxonomy'] === 'pa_timber-finish' || $_GET['taxonomy'] === 'pa_metal-finish')) {
            wp_enqueue_media();
            wp_enqueue_script('oa-tfp-term-image-admin', OA_TFP_PLUGIN_URL . 'assets/oa-tfp-term-image-admin.js', array('jquery'), OA_TFP_PLUGIN_VERSION, true);
        }
    }
    
    /**
     * Render product admin script
     */
    public function render_product_admin_script() {
        ?>
        <style>
        .oa-tfp-sortable-gallery .oa-tfp-gallery-item {
            transition: opacity 0.2s;
        }
        .oa-tfp-sortable-gallery .oa-tfp-gallery-item.ui-sortable-helper {
            opacity: 0.6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .oa-tfp-gallery-placeholder {
            width: 80px !important;
            height: 80px !important;
            border: 2px dashed #0073aa !important;
            border-radius: 4px !important;
            background: #f0f0f0 !important;
            display: inline-block;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // Banner uploader
            var frame;
            $('#oa-tfp-product-banner-upload').on('click', function(e) {
                e.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({
                    title: 'Select or Upload Product Banner',
                    button: { text: 'Use this banner' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#oa-tfp-product-banner').val(attachment.id);
                    $('.oa-tfp-options-container img').remove();
                    $('.oa-tfp-options-container').prepend('<img src="' + attachment.url + '" style="max-width:100%; margin-bottom:10px;" />');
                });
                frame.open();
            });
            $('#oa-tfp-product-banner-remove').on('click', function(e) {
                e.preventDefault();
                $('#oa-tfp-product-banner').val('');
                $('.oa-tfp-options-container img').remove();
            });
            
            // Brochure uploader
            var frame2;
            $('#oa-tfp-product-brochure-upload').on('click', function(e) {
                e.preventDefault();
                if (frame2) {
                    frame2.open();
                    return;
                }
                frame2 = wp.media({
                    title: 'Select or Upload Brochure PDF',
                    button: { text: 'Upload Brochure' },
                    multiple: false
                });
                frame2.on('select', function() {
                    var attachment = frame2.state().get('selection').first().toJSON();
                    $('#oa-tfp-product-brochure').val(attachment.id);
                    $('.oa-tfp-options-container a').remove();
                    $('.oa-tfp-options-container').append('<a href="' + attachment.url + '" target="_blank" style="display:inline-flex;align-items:center;gap:5px;text-decoration:none;color:#0073aa;margin-bottom:10px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill:currentColor;"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>Download current brochure</a><br />');
                });
                frame2.open();
            });
            $('#oa-tfp-product-brochure-remove').on('click', function(e) {
                e.preventDefault();
                $('#oa-tfp-product-brochure').val('');
                $('.oa-tfp-options-container a').remove();
            });
            // Product Diagram uploader
            var frame3;
            $('#oa-tfp-product-diagram-upload').on('click', function(e) {
                e.preventDefault();
                if (frame3) {
                    frame3.open();
                    return;
                }
                frame3 = wp.media({
                    title: 'Select or Upload Product Diagram',
                    button: { text: 'Select Diagram' },
                    multiple: false
                });
                frame3.on('select', function() {
                    var attachment = frame3.state().get('selection').first().toJSON();
                    $('#oa-tfp-product-diagram').val(attachment.id);
                    // Remove any previous diagram image
                    $('.oa-tfp-options-container img[data-diagram]').remove();
                    // Insert new diagram image
                    $('#oa-tfp-product-diagram').before('<img src="' + attachment.url + '" data-diagram style="max-width:100%; margin-bottom:10px;" />');
                });
                frame3.open();
            });
            $('#oa-tfp-product-diagram-remove').on('click', function(e) {
                e.preventDefault();
                $('#oa-tfp-product-diagram').val('');
                $('.oa-tfp-options-container img[data-diagram]').remove();
            });
            
            // Initialize sortable for secondary gallery
            function initGallerySortable() {
                $('#oa-tfp-secondary-gallery-preview').sortable({
                    items: '.oa-tfp-gallery-item',
                    cursor: 'move',
                    opacity: 0.6,
                    tolerance: 'pointer',
                    placeholder: 'oa-tfp-gallery-placeholder',
                    start: function(e, ui) {
                        ui.placeholder.css({
                            'width': '80px',
                            'height': '80px',
                            'border': '2px dashed #0073aa',
                            'border-radius': '4px',
                            'background': '#f0f0f0'
                        });
                    },
                    update: function(e, ui) {
                        updateGalleryOrder();
                    }
                });
            }
            
            // Update gallery order in hidden field
            function updateGalleryOrder() {
                var idsArray = [];
                $('#oa-tfp-secondary-gallery-preview .oa-tfp-gallery-item').each(function() {
                    idsArray.push($(this).data('id').toString());
                });
                $('#oa-tfp-secondary-gallery').val(idsArray.join(','));
            }
            
            // Initialize sortable on page load
            initGallerySortable();
            
            // Secondary Gallery uploader
            var galleryFrame;
            $('#oa-tfp-secondary-gallery-upload').on('click', function(e) {
                e.preventDefault();
                
                // Create or reuse frame
                if (galleryFrame) {
                    galleryFrame.open();
                    return;
                }
                
                galleryFrame = wp.media({
                    title: 'Select Images for Secondary Gallery',
                    button: { text: 'Add to Gallery' },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                galleryFrame.on('select', function() {
                    var selection = galleryFrame.state().get('selection');
                    var currentIds = $('#oa-tfp-secondary-gallery').val();
                    var idsArray = currentIds ? currentIds.split(',') : [];
                    var $preview = $('#oa-tfp-secondary-gallery-preview');
                    var added = false;
                    
                    selection.each(function(attachment) {
                        var id = attachment.id.toString();
                        if (idsArray.indexOf(id) === -1) {
                            idsArray.push(id);
                            added = true;
                            var url = attachment.attributes.url;
                            if (attachment.attributes.sizes && attachment.attributes.sizes.thumbnail) {
                                url = attachment.attributes.sizes.thumbnail.url;
                            }
                            $preview.append(
                                '<div class="oa-tfp-gallery-item" data-id="' + id + '" style="position:relative;width:80px;height:80px;border:1px solid #ddd;border-radius:4px;overflow:hidden;cursor:move;">' +
                                '<img src="' + url + '" style="width:100%;height:100%;object-fit:cover;pointer-events:none;" />' +
                                '<button type="button" class="oa-tfp-remove-gallery-item" style="position:absolute;top:2px;right:2px;background:rgba(255,0,0,0.8);color:white;border:none;border-radius:2px;cursor:pointer;width:20px;height:20px;line-height:1;font-size:12px;z-index:10;">×</button>' +
                                '</div>'
                            );
                        }
                    });
                    
                    if (added) {
                        $('#oa-tfp-secondary-gallery').val(idsArray.join(','));
                        // Re-initialize sortable to include new items
                        initGallerySortable();
                        if (idsArray.length > 0 && $('#oa-tfp-secondary-gallery-clear').length === 0) {
                            $('#oa-tfp-secondary-gallery-upload').after(' <button type="button" class="button" id="oa-tfp-secondary-gallery-clear">Clear All</button>');
                        }
                    }
                });
                
                galleryFrame.open();
            });
            
            // Remove individual gallery item
            $(document).on('click', '.oa-tfp-remove-gallery-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $item = $(this).closest('.oa-tfp-gallery-item');
                $item.fadeOut(200, function() {
                    $item.remove();
                    updateGalleryOrder();
                    if ($('#oa-tfp-secondary-gallery-preview .oa-tfp-gallery-item').length === 0) {
                        $('#oa-tfp-secondary-gallery-clear').remove();
                    }
                });
            });
            
            // Clear all gallery items
            $(document).on('click', '#oa-tfp-secondary-gallery-clear', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to remove all images from the secondary gallery?')) {
                    $('#oa-tfp-secondary-gallery').val('');
                    $('#oa-tfp-secondary-gallery-preview').empty();
                    $(this).remove();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add timber finish image field
     */
    public function add_timber_finish_image_field($taxonomy) {
        if ($taxonomy !== 'pa_timber-finish') return;
        ?>
        <div class="form-field oa-tfp-term-image-wrap">
            <label for="oa-tfp_term_image">Image</label>
            <input type="hidden" id="oa-tfp_term_image" name="tfp_term_image" value="" />
            <div id="oa-tfp_term_image_preview"></div>
            <button type="button" class="button oa-tfp-upload-term-image">Upload/Add image</button>
            <button type="button" class="button oa-tfp-remove-term-image" style="display:none;">Remove image</button>
        </div>
        <?php
    }
    
    /**
     * Edit timber finish image field
     */
    public function edit_timber_finish_image_field($term, $taxonomy) {
        if ($taxonomy !== 'pa_timber-finish') return;
        $image_id = get_term_meta($term->term_id, 'tfp_term_image', true);
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        ?>
        <tr class="form-field oa-tfp-term-image-wrap">
            <th scope="row"><label for="oa-tfp_term_image">Image</label></th>
            <td>
                <input type="hidden" id="oa-tfp_term_image" name="tfp_term_image" value="<?php echo esc_attr($image_id); ?>" />
                <div id="oa-tfp_term_image_preview">
                    <?php if ($image_url) echo '<img src="' . esc_url($image_url) . '" style="max-width:100px;" />'; ?>
                </div>
                <button type="button" class="button oa-tfp-upload-term-image">Upload/Add image</button>
                <button type="button" class="button oa-tfp-remove-term-image" style="display:<?php echo $image_id ? 'inline-block' : 'none'; ?>;">Remove image</button>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save timber finish image
     */
    public function save_timber_finish_image($term_id, $tt_id) {
        if (isset($_POST['tfp_term_image'])) {
            update_term_meta($term_id, 'tfp_term_image', absint($_POST['tfp_term_image']));
        }
    }
    
    /**
     * Add metal finish image field
     */
    public function add_metal_finish_image_field($taxonomy) {
        if ($taxonomy !== 'pa_metal-finish') return;
        ?>
        <div class="form-field oa-tfp-term-image-wrap">
            <label for="oa-tfp_term_image">Image</label>
            <input type="hidden" id="oa-tfp_term_image" name="tfp_term_image" value="" />
            <div id="oa-tfp_term_image_preview"></div>
            <button type="button" class="button oa-tfp-upload-term-image">Upload/Add image</button>
            <button type="button" class="button oa-tfp-remove-term-image" style="display:none;">Remove image</button>
        </div>
        <?php
    }
    
    /**
     * Edit metal finish image field
     */
    public function edit_metal_finish_image_field($term, $taxonomy) {
        if ($taxonomy !== 'pa_metal-finish') return;
        $image_id = get_term_meta($term->term_id, 'tfp_term_image', true);
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        ?>
        <tr class="form-field oa-tfp-term-image-wrap">
            <th scope="row"><label for="oa-tfp_term_image">Image</label></th>
            <td>
                <input type="hidden" id="oa-tfp_term_image" name="tfp_term_image" value="<?php echo esc_attr($image_id); ?>" />
                <div id="oa-tfp_term_image_preview">
                    <?php if ($image_url) echo '<img src="' . esc_url($image_url) . '" style="max-width:100px;" />'; ?>
                </div>
                <button type="button" class="button oa-tfp-upload-term-image">Upload/Add image</button>
                <button type="button" class="button oa-tfp-remove-term-image" style="display:<?php echo $image_id ? 'inline-block' : 'none'; ?>;">Remove image</button>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save metal finish image
     */
    public function save_metal_finish_image($term_id, $tt_id) {
        if (isset($_POST['tfp_term_image'])) {
            update_term_meta($term_id, 'tfp_term_image', absint($_POST['tfp_term_image']));
        }
    }
    
    /**
     * Increase variations per page in admin
     * 
     * @param int $per_page Current variations per page (default 15)
     * @return int Number of variations to show per page
     */
    public function increase_variations_per_page($per_page) {
        // Show 50 variations per page instead of default 15
        return 50;
    }

} 