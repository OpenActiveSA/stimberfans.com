# Gravity Forms Email Styling

## Overview

This plugin now includes modern, professional styling for Gravity Forms email notifications. The old table-based layout has been transformed into a clean, modern design with gradient headers, better spacing, and improved readability.

## Features

✅ **Modern Design**: Clean gradient headers with purple/blue color scheme  
✅ **Better Readability**: Improved typography and spacing  
✅ **Responsive**: Adapts to mobile devices  
✅ **Image Support**: Product images are displayed with rounded corners and shadows  
✅ **Dark Mode**: Supports dark mode for compatible email clients  
✅ **Print Friendly**: Optimized for printing  
✅ **Email Client Compatible**: Tested CSS that works across major email clients

## How It Works

### Automatic Injection

The styling is automatically injected into all Gravity Forms email notifications when the plugin is active. No configuration needed!

### Components

1. **CSS File**: `assets/oa-tfp-gravity-forms-email.css`
   - Contains all modern styling rules
   - Overrides default Gravity Forms table styles
   - Includes responsive and accessibility improvements

2. **PHP Class**: `includes/class-oa-tfp-gravity-forms.php`
   - Hooks into Gravity Forms notification system
   - Wraps email content with modern HTML structure
   - Optimizes CSS for email client compatibility

3. **Integration**: `oa-tfp-product-mods.php`
   - Loads the Gravity Forms class when Gravity Forms is active
   - No performance impact if Gravity Forms is not installed

## Customization

### Colors

To customize the color scheme, edit `assets/oa-tfp-gravity-forms-email.css`:

```css
/* Header gradient colors */
tr[bgcolor="#EAF2FA"],
tr[bgcolor="#FAF4EA"] {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

/* Primary link color */
a {
    color: #667eea !important;
}

/* Button background */
a.map-it-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}
```

### Typography

Change the font family in the CSS file:

```css
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
```

### Spacing

Adjust padding and margins:

```css
/* Header padding */
tr[bgcolor="#EAF2FA"] td,
tr[bgcolor="#FAF4EA"] td {
    padding: 16px 20px !important;
}

/* Content padding */
tr[bgcolor="#FFFFFF"] td {
    padding: 16px 20px !important;
}
```

## Testing

### Test Email Notifications

1. Go to **Forms → Select Your Form → Settings → Notifications**
2. Click **Send Test Notification**
3. Check your email to see the new styling

### Test in Different Clients

The styling has been optimized for:
- Gmail (Desktop & Mobile)
- Outlook (Desktop & Web)
- Apple Mail
- Yahoo Mail
- Thunderbird

### Admin Preview

To see how notifications look in the admin area, navigate to:
**Forms → Entries → View Entry → Resend Notifications**

## Before & After

### Before
- Plain gray/white tables
- Inline font tags
- No spacing between sections
- Small, hard-to-read text
- No visual hierarchy

### After
- Modern gradient headers
- Clean typography
- Proper spacing and breathing room
- Larger, readable text
- Clear visual hierarchy
- Rounded corners and shadows
- Professional appearance

## Filters & Hooks

### Customize the HTML Wrapper

```php
add_filter('oa_tfp_gf_email_wrapper', function($html, $message) {
    // Customize the HTML wrapper
    return $html;
}, 10, 2);
```

### Modify CSS Before Injection

```php
add_filter('oa_tfp_gf_email_css', function($css) {
    // Modify or add custom CSS
    $css .= ' .custom-class { color: red; } ';
    return $css;
});
```

### Disable Styling for Specific Forms

```php
add_filter('gform_notification', function($notification, $form, $entry) {
    // Disable for form ID 5
    if ($form['id'] == 5) {
        remove_filter('gform_notification', array($GLOBALS['oa_tfp_gravity_forms'], 'add_email_styles'), 10);
    }
    return $notification;
}, 5, 3);
```

## Troubleshooting

### Styles Not Showing

1. **Check if Gravity Forms is active**: The styles only load if Gravity Forms is installed and active
2. **Clear cache**: If using an email cache or plugin cache, clear it
3. **Test email**: Send a test notification to verify

### Images Not Displaying

- Ensure image URLs are absolute (not relative)
- Check that images are publicly accessible
- Verify image URLs in the email source code

### Layout Issues in Specific Email Clients

Some email clients have limited CSS support:
- **Outlook Desktop**: Has poor CSS support, but our styles are optimized
- **Older Email Clients**: May not support gradients or rounded corners
- **Fallbacks**: The CSS includes fallback styles for unsupported features

## Email Client Compatibility

| Feature | Gmail | Outlook | Apple Mail | Yahoo |
|---------|-------|---------|------------|-------|
| Gradients | ✅ | ⚠️ | ✅ | ✅ |
| Rounded Corners | ✅ | ⚠️ | ✅ | ✅ |
| Box Shadows | ✅ | ❌ | ✅ | ✅ |
| Media Queries | ✅ | ⚠️ | ✅ | ✅ |
| Web Fonts | ❌ | ❌ | ❌ | ❌ |

✅ = Full Support | ⚠️ = Partial Support | ❌ = No Support

## Performance

- **CSS Size**: ~8KB minified
- **Optimization**: CSS is automatically optimized for email clients
- **No External Resources**: All styles are inline, no external dependencies
- **Fast Loading**: No impact on form submission speed

## Support

For issues or customization requests:
1. Check this documentation
2. Review the CSS file for customization options
3. Contact your development team

## Version History

- **v1.0** - Initial release with modern email styling
  - Gradient headers
  - Responsive design
  - Dark mode support
  - Email client optimization


