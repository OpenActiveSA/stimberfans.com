# OA TFP Product Mods Plugin

A WordPress plugin for WooCommerce that adds custom product banner functionality, gallery display, and product details sections with accordion-style information display.

## Improvements Made

### 1. Naming Conventions
- **Plugin Name**: Changed from `oa-timberfans-product-mods` to `oa-tfp-product-mods`
- **CSS Classes**: Updated from `oa-timberfans-*` to `oa-tfp-*`
- **JavaScript Files**: Renamed with consistent `oa-tfp-` prefix
- **PHP Classes**: Organized into `OA_TFP_*` class structure
- **Shortcodes**: Updated to use `oa_tfp_*` naming convention

### 2. Code Organization
- **Modular Structure**: Split functionality into separate class files
  - `OA_TFP_Core`: Main plugin initialization and core functionality
  - `OA_TFP_Admin`: Admin meta boxes and media uploaders
  - `OA_TFP_Shortcodes`: All shortcode functionality
  - `OA_TFP_Variations`: Custom variation UI
  - `OA_TFP_Assets`: CSS and JS enqueuing
  - `OA_TFP_Gravity_Forms`: Gravity Forms email styling

### 3. Code Style Improvements
- **Indentation**: Consistent 4-space indentation throughout
- **Documentation**: Added comprehensive PHPDoc comments
- **Structure**: Organized code into logical methods and properties
- **Constants**: Defined plugin constants for version and paths

### 4. File Structure
```
oa-tfp-product-mods/
├── oa-tfp-product-mods.php              # Main plugin file
├── includes/
│   ├── class-oa-tfp-core.php            # Core functionality
│   ├── class-oa-tfp-admin.php           # Admin functionality
│   ├── class-oa-tfp-shortcodes.php      # Shortcode handlers
│   ├── class-oa-tfp-variations.php      # Variation UI
│   ├── class-oa-tfp-assets.php          # Asset management
│   └── class-oa-tfp-gravity-forms.php   # Gravity Forms email styling
├── assets/
│   ├── oa-tfp-styles.css                # Main stylesheet
│   ├── oa-tfp-variations.js             # Variation JavaScript
│   ├── oa-tfp-term-image-admin.js       # Admin JavaScript
│   └── oa-tfp-gravity-forms-email.css   # Email notification styling
├── preview-email-styling.html           # Email styling preview
├── GRAVITY-FORMS-EMAIL-STYLING.md       # Email styling documentation
└── README.md                             # This file
```

## Available Shortcodes

- `[oa_tfp_product_banner]` - Displays the product banner section
- `[oa_tfp_product_gallery]` - Displays the product gallery
- `[oa_tfp_product_details]` - Displays the product details accordion
- `[oa_tfp_timber_options_catalog]` - Displays timber options catalog
- `[oa_tfp_metal_finishes_catalog]` - Displays metal finishes catalog

## Features

### Product Banner
- Custom banner image upload
- Custom title override
- Configurable banner height
- Responsive design

### Product Gallery
- Grid layout for product images
- Responsive design with breakpoints
- Optimized for mobile devices

### Product Details Accordion
- Description section
- Dimensions & information
- Fan size guide
- Down rod length guide
- Timber options catalog
- Metal finishes catalog

### Custom Variation UI
- Visual timber finish selection
- Custom button-based variation selection
- Accessibility features
- Reset functionality
- Quote button for out-of-stock products



### Admin Features
- Product options meta box
- Banner and brochure upload
- Timber finish image management
- Settings page for global options
- Quote page configuration for out-of-stock products

### Gravity Forms Email Styling (NEW!)
- **Modern Design**: Beautiful gradient headers and clean typography
- **Automatic**: Styles are automatically applied to all Gravity Forms notifications
- **Responsive**: Adapts perfectly to mobile and desktop email clients
- **Professional**: Clean, modern appearance with proper spacing and visual hierarchy
- **Compatible**: Tested with Gmail, Outlook, Apple Mail, and more
- **Customizable**: Easy to modify colors, fonts, and spacing

📖 **Full Documentation**: See `GRAVITY-FORMS-EMAIL-STYLING.md` for details  
👁️ **Preview**: Open `preview-email-styling.html` in your browser to see the styling

## Installation

1. Upload the plugin files to `/wp-content/plugins/oa-tfp-product-mods/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in 'Settings > OA TFP Product Mods'
4. Use shortcodes in your product pages or templates

## Compatibility

- WordPress 5.0+
- WooCommerce 3.0+
- Gravity Forms 2.0+ (optional, for email styling)
- PHP 7.4+

## Migration Notes

The original plugin files are preserved for reference. The new structure maintains all functionality while providing better organization and maintainability.

## Support

For support or feature requests, please contact the development team. 