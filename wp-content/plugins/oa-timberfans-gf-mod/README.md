# OA TimberFans GF Mod

A WordPress plugin that dynamically populates Gravity Forms with WooCommerce products and fan size options for TimberFans.

## Description

This plugin enhances Gravity Forms by providing dynamic field population and filtering based on WooCommerce product attributes. It's specifically designed for TimberFans to manage fan ranges, sizes, and finishes.

## Features

- **Dynamic Product Population**: Automatically populates form fields with WooCommerce products
- **Term Image Support**: Displays term images for timber and metal finishes in the same style as fan range products
- **Real-time Filtering**: Filters available options based on selected products
- **Responsive Design**: Mobile-friendly interface with grid layouts
- **Loading States**: Visual feedback during AJAX operations
- **Accessibility**: WCAG compliant with proper focus states
- **Error Handling**: Graceful fallbacks when products or attributes are not found

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce 3.0 or higher
- Gravity Forms 2.5 or higher

## Installation

1. Upload the plugin files to `/wp-content/plugins/oa-timberfans-gf-mod/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce and Gravity Forms are installed and activated

## Configuration

### Form Setup

The plugin is configured for Gravity Form #3 with the following field structure:

- **Field 1**: Fan Range (Product Selection)
- **Field 4**: Fan Size
- **Field 5**: Timber Finish
- **Field 6**: Metal Finish
- **Field 7**: Speed Regulator

### WooCommerce Setup

Ensure your WooCommerce products have the following attributes:

- `pa_size` - for fan sizes
- `pa_timber-finish` - for timber finishes
- `pa_metal-finish` - for metal finishes
- `pa_speed-regulator` - for speed regulators

**Important**: For proper filtering, the Speed Regulator attribute should be set up in one of two ways:

1. **Product-level attribute** (recommended): 
   - Create the "Speed Regulator" attribute in WooCommerce → Products → Attributes
   - Uncheck "Used for variations"
   - Assign speed regulator terms directly to products

2. **Variation attribute**:
   - Check "Used for variations" in the attribute settings
   - Assign speed regulator terms to product variations
   - Regenerate variations after assignment

The plugin will automatically detect and filter based on the setup you choose.

## File Structure

```
oa-timberfans-gf-mod/
├── assets/
│   └── icon-check.svg
├── includes/
│   ├── class-oa-tf-form-populator.php
│   ├── class-oa-tf-ajax-handler.php
│   └── class-oa-tf-assets.php
├── oa-timberfans-gf-mod.php
├── oa-timberfans-gf-mod.css
├── oa-timberfans-gf-mod.js
└── README.md
```

## Classes

### OA_TimberFans_GF_Mod (Main Class)
- Handles plugin initialization
- Checks for required dependencies
- Manages plugin lifecycle

### OA_TF_Form_Populator
- Populates Gravity Forms fields with WooCommerce data
- Handles product, size, and finish choices
- Manages fallback options

### OA_TF_Ajax_Handler
- Processes AJAX requests for dynamic filtering
- Retrieves product attributes
- Provides fallback mechanisms

### OA_TF_Assets
- Manages CSS and JavaScript enqueuing
- Handles script localization
- Controls asset loading conditions

## Hooks and Filters

### Form Population Filters
- `gform_pre_render_3`
- `gform_pre_validation_3`
- `gform_admin_pre_render_3`

### AJAX Actions
- `wp_ajax_oa_tf_get_available_sizes`
- `wp_ajax_oa_tf_get_available_finishes`
- `wp_ajax_oa_tf_get_available_metal_finishes`
- `wp_ajax_oa_tf_get_available_speed_regulators`

## JavaScript API

The plugin provides a JavaScript object `OATF` with the following properties:

```javascript
OATF = {
    ajax_url: 'admin-ajax.php',
    form_id: 3,
    field_product: 1,
    field_size: 4,
    field_speed_regulator: 7,
    nonce: 'security_nonce'
}
```

## CSS Classes

### Loading States
- `.field-loading` - Applied to fields during AJAX operations
- `.form-loading` - Applied to entire form during processing

### Disabled States
- `.disabled-size` - Applied to unavailable size options
- `.disabled-finish` - Applied to unavailable finish options
- `.disabled-metal-finish` - Applied to unavailable metal finish options
- `.disabled-speed-regulator` - Applied to unavailable speed regulator options

### Messages
- `.no-sizes-available` - Message when no sizes are available
- `.no-finishes-available` - Message when no finishes are available
- `.no-metal-finishes-available` - Message when no metal finishes are available
- `.no-speed-regulators-available` - Message when no speed regulators are available

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Development

### Code Standards
- PHP: PSR-12 coding standards
- JavaScript: ES6+ with jQuery
- CSS: BEM methodology
- 4-space indentation throughout

### Debugging

Enable WordPress debug logging to see detailed plugin logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Log entries are prefixed with "OA TimberFans:" for easy filtering.

## Changelog

### 1.0.3
- Fixed Speed Regulator filtering issue
- Improved fallback logic to scope speed regulator terms to selected products
- Added extensive debugging and logging for troubleshooting
- Added test file for verifying WooCommerce configuration
- Updated documentation with troubleshooting guide

### 1.0.2
- Added speed regulator field (Field 7) with same appearance as Fan Size field
- Added AJAX support for speed regulator filtering
- Added support for product-level attributes (not used for variations)
- Updated CSS classes and JavaScript to handle new field

### 1.0.1
- Added term image support for timber and metal finishes
- Extended fan-range styling to timber and metal finish fields
- Improved visual consistency across form fields

### 1.0.0
- Initial release
- Dynamic form field population
- AJAX-based filtering
- Responsive design
- Accessibility improvements

## Troubleshooting

### Speed Regulator Not Filtering

If the Speed Regulator field is not filtering properly:

1. **Check your setup**: Use the test file `test-speed-regulator.php` to verify your WooCommerce configuration
2. **Verify field ID**: Ensure the Speed Regulator field in your Gravity Form has ID 7
3. **Check browser console**: Look for JavaScript errors or debugging messages
4. **Enable debug logging**: Add `define('WP_DEBUG', true);` to wp-config.php to see detailed logs

### Common Issues

- **No speed regulator terms found**: Create the "Speed Regulator" attribute and assign terms to products
- **All options showing**: Check that speed regulator terms are properly assigned to specific products
- **Field not updating**: Verify the field ID matches the configuration in `class-oa-tf-assets.php`

### Debug Information

The plugin includes extensive logging. Check your WordPress debug log for entries prefixed with "OA TimberFans:" to troubleshoot issues.

## Support

For support and feature requests, please contact Open Agency.

## License

GPL v2 or later

## Credits

Developed by Open Agency for TimberFans.