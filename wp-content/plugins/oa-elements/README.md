# Open Agency Elements

A comprehensive WordPress plugin providing essential shortcodes and elements for building beautiful layouts with the GeneratePress theme.

## Features

### 🎯 Core Elements
- **Button Shortcode** - Flexible button component with multiple styles
- **Slider Shortcode** - Full-featured image/video slider with custom fields
- **Logo Carousel** - Brand logo showcase with custom post types
- **Testimonial Carousel** - Customer testimonials with star ratings
- **Featured Products Carousel** - WooCommerce product showcase
- **Links Element** - Social media and account/cart links
- **Title Area** - Custom page titles with custom fields
- **FAQ Shortcode** - Accordion-style FAQ system with auto-detection

### 🚀 Performance Features
- **Modular Loading** - Only loads assets for enabled features
- **GeneratePress Optimized** - Built specifically for GeneratePress theme compatibility
- **Responsive Design** - Mobile-first approach with breakpoint optimization
- **Accessibility** - WCAG compliant with proper ARIA labels and focus states

### 🎨 Design Features
- **Multiple Button Styles** - Outline, solid, and custom color options
- **Flexible Alignment** - Left, center, right alignment options
- **Custom Colors** - CSS custom properties for easy theming
- **Smooth Animations** - Hardware-accelerated transitions

## Installation

1. Upload the plugin files to `/wp-content/plugins/oa-elements/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure settings under 'Settings > OAE Settings'

## Configuration

### Settings Page
Navigate to **Settings > OAE Settings** to configure:

- **Feature Toggles** - Enable/disable individual elements
- **Social Media Links** - Configure Facebook, Twitter, Instagram, LinkedIn, YouTube
- **WooCommerce Integration** - Account, cart, and mini-cart options
- **Smooth Scroll** - Enable smooth scrolling to anchor links

### Shortcode Examples

#### Button Shortcode
```php
[oa_button link="https://example.com" text="Click here" outline="yes" size="large" color="primary"]
```

#### Slider Shortcode
```php
[oa_slider category="homepage" height="100"]
```

#### Logo Carousel
```php
[oa_logo_carousel category="partners"]
```

**Logo Features:**
- **Subheading Support** - Add optional subheadings below each logo
- **SVG Support** - Dynamic color changes for SVG logos
- **Link Integration** - Clickable logos with custom URLs
- **Responsive Design** - Adapts to different screen sizes

#### Logo Grid
```php
[oa_logo_grid category="partners" column_width="4"]
```

#### Testimonial Carousel
```php
[oa_testimonial_carousel category="reviews"]
```

#### Links Element
```php
[oa_links icon_color="#333" icon_hover_color="#666" socials_only="no"]
```

#### FAQ Shortcode
```php
[oa_faq]  # Auto-detects current post
[oa_faq category="general"]  # Specific category
```

## File Structure

```
oa-elements/
├── assets/
│   ├── css/
│   │   └── open-agency-elements.css
│   └── icons/
│       ├── account.svg
│       ├── cart.svg
│       ├── facebook.svg
│       ├── instagram.svg
│       ├── linkedin.svg
│       ├── search.svg
│       └── twitter.svg
├── includes/
│   ├── admin/
│   │   └── class-oa-elements-admin.php
│   ├── shortcodes/
│   │   ├── class-oa-button-shortcode.php
│   │   ├── class-oa-links-shortcode.php
│   │   ├── class-oa-faq-shortcode.php
│   │   └── ...
│   ├── post-types/
│   │   ├── class-oa-faq-post-type.php
│   │   └── ...
│   ├── functions.php
│   ├── class-oa-elements-utilities.php
│   └── class-oa-elements-enqueue.php

├── open-agency-elements.php
└── README.md
```

## Development

### Code Standards
- **WordPress Coding Standards** - Follows WordPress PHP coding standards
- **4-Space Indentation** - Consistent indentation throughout
- **Proper Documentation** - PHPDoc comments for all functions and classes
- **Security First** - Proper sanitization and escaping

### Naming Conventions
- **Files**: `class-oa-element-name.php`
- **Classes**: `OA_Element_Name`
- **Functions**: `oa_element_name()`
- **Constants**: `OA_ELEMENTS_CONSTANT`

### Adding New Features

1. **Create Shortcode Class**
```php
class OA_New_Shortcode {
    public function __construct() {
        if ( oa_is_feature_enabled( 'oa_enable_new_feature' ) ) {
            add_shortcode( 'oa_new', array( $this, 'render' ) );
        }
    }
    
    public function render( $atts ) {
        // Implementation
    }
}
```

2. **Add to Settings**
```php
// In class-oa-elements-admin.php
'oa_enable_new_feature' => array(
    'label' => __( 'New Feature', 'open-agency-elements' ),
    'description' => __( 'Enable [oa_new] shortcode', 'open-agency-elements' ),
    'type' => 'checkbox',
    'shortcode' => '[oa_new]',
),
```

3. **Register in Main Plugin**
```php
// In open-agency-elements.php
$shortcodes = array(
    'new',
    // ... other shortcodes
);
```

## Dependencies

### Required
- WordPress 5.0+
- PHP 7.4+

### Optional
- **WooCommerce** - Required for product carousels and cart features

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Performance

### Optimizations
- **Conditional Loading** - Assets only load when features are enabled
- **Minified CSS** - Optimized stylesheet with proper organization
- **Lazy Loading** - Carousel images load on demand
- **Hardware Acceleration** - CSS transforms for smooth animations

### Best Practices
- **No jQuery Dependencies** - Modern JavaScript where possible
- **CSS Custom Properties** - Easy theming and customization
- **Responsive Images** - Proper image sizing and optimization
- **Accessibility** - ARIA labels and keyboard navigation

## Troubleshooting

### Common Issues

1. **Slider not working**
   - Check that slider posts exist in the admin
   - Verify slider settings are configured properly

2. **WooCommerce features not showing**
   - Verify WooCommerce is installed and activated
   - Check WooCommerce settings and permissions

3. **Styles not loading**
   - Clear cache and refresh
   - Check browser console for errors
   - Verify plugin is activated

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Changelog

### Version 1.1.4
- **Gutenberg Content Fix**: Fixed issue where Gutenberg block comments were being wrapped in p tags in FAQ content
- **Improved Content Processing**: Added proper handling of Gutenberg block comments in FAQ accordion content

### Version 1.1.3
- **FAQ Content Source Feature**: Added ability to select a page as content source for FAQs
- **Enhanced FAQ Management**: New content source dropdown with page selection
- **Improved Admin Interface**: Better visual feedback for content source selection
- **Content Reusability**: FAQs can now pull content from existing pages

### Version 1.1.2
- **FAQ Shortcode**: Added comprehensive FAQ system with accordion functionality
- **Auto-detection**: FAQs automatically detect current product/post
- **Category System**: FAQ categories with fallback logic
- **Accordion Styling**: Uses Timberfans plugin accordion styles

### Version 1.1.0
- **Complete code reorganization** with proper class structure
- **Enhanced admin interface** with better UX and copy functionality
- **Improved CSS organization** with better comments and sections
- **GeneratePress compatibility** improvements
- **Accessibility enhancements** with proper ARIA labels
- **Performance optimizations** with conditional loading
- **Better error handling** and dependency checks
- **Enhanced documentation** and code comments

### Version 1.0.10
- Initial release with basic functionality

## Support

For support and feature requests, please contact Open Agency.

## License

GPL v2 or later

## Credits

Developed by Open Agency for GeneratePress theme compatibility. 