# FAQ Shortcode Setup Guide

## Overview

The FAQ shortcode provides an accordion-style FAQ system that automatically detects the current product and displays relevant FAQs. This allows you to use the same shortcode in a shared footer while showing different FAQs for each product.

## How It Works

1. **Auto-Detection**: The shortcode automatically detects the current post/product using its slug
2. **Category Matching**: It looks for FAQ categories that match the post slug
3. **Fallback Logic**: If no post-specific FAQs are found, it tries fallback categories
4. **Accordion Layout**: Uses the same accordion structure as the Timberfans product mods plugin

## Setup Instructions

### 1. Enable the FAQ Feature

1. Go to **Settings > Open Agency Elements**
2. Find the **FAQ Shortcode** option in the Shortcodes section
3. Check the box to enable it
4. Save changes

### 2. Create FAQ Categories

For each post/product, create an FAQ category with the post's slug:

**Option A: Manual Creation**
1. Go to **FAQs > Categories**
2. Create a new category with the slug matching your post slug
   - Example: If your post slug is `ceiling-fan-model-xyz`, create a category with slug `ceiling-fan-model-xyz`

**Option B: Using Helper Function**
```php
// In your theme's functions.php or a custom plugin
$category_id = OA_Elements_Utilities::create_product_faq_category('ceiling-fan-model-xyz');
```

### 3. Add FAQs to Post Categories

1. Go to **FAQs > Add New**
2. Create your FAQ with:
   - **Title**: The question (will be the accordion header)
   - **Content**: The answer (will be the accordion content)
   - **FAQ Settings** (sidebar):
     - **Expanded by default**: Check if this FAQ should be open by default
     - **Order**: Set the display order (lower numbers appear first)
     - **Content Source**: Choose between:
       - **Direct content**: Use the content from the editor below
       - **From selected page**: Use content from a specific page
3. Assign it to the appropriate FAQ category
4. Repeat for all FAQs for that post

### 4. Use the Shortcode

Simply add the shortcode to your shared footer or any page:

```php
[oa_faq]
```

The shortcode will automatically:
- Detect the current post/product
- Find FAQs in the matching category
- Display them with accordion styling
- Handle expand/collapse functionality
- Respect the "expanded by default" setting

## Category Naming Patterns

The shortcode tries these patterns in order:

1. **Exact post slug**: `ceiling-fan-model-xyz`
2. **Product prefix**: `product-ceiling-fan-model-xyz`
3. **FAQ prefix**: `faq-ceiling-fan-model-xyz`
4. **Default fallback**: `default-faqs`

## Advanced Usage

### Content Source Options

The FAQ system supports two content source options:

1. **Direct Content**: Write content directly in the FAQ editor
2. **Page Content**: Pull content from an existing page

When using "From selected page":
- The FAQ will display the content from the selected page
- If the page is unpublished or deleted, it falls back to direct content
- You can edit the source page content and the FAQ will automatically update
- Useful for reusing content across multiple FAQs or maintaining content in one place

### Manual Category Override
```php
[oa_faq category="my-custom-category"]
```

### Disable Auto-Detection
```php
[oa_faq auto_detect="no" category="default-faqs"]
```

### Custom Classes
```php
[oa_faq class="custom-faq-style"]
```

### Multiple Categories
```php
[oa_faq category="general,product,shipping"]
```

## Styling

The FAQ accordion uses the same styling as the Timberfans product mods plugin:

- **Header**: Uppercase text with + icon
- **Active State**: - icon when expanded
- **Content**: Clean, readable typography
- **Responsive**: Adapts to mobile devices
- **Accessibility**: Keyboard navigation support

## Troubleshooting

### No FAQs Showing
1. Check that the product slug matches the category slug exactly
2. Verify FAQs are published and assigned to the correct category
3. Try creating a `default-faqs` category as fallback

### Wrong FAQs Showing
1. Check category assignments in the FAQs admin
2. Verify product slug detection by temporarily adding `auto_detect="no"`

### Debug Product Detection
Add this to your theme's functions.php to see what product is detected:
```php
add_action('wp_footer', function() {
    if (is_singular()) {
        $product_slug = get_post_field('post_name', get_the_ID());
        echo "<!-- Debug: Product slug = $product_slug -->";
    }
});
```

## Examples

### Basic Usage
```php
[oa_faq]
```

### With Custom Styling
```php
[oa_faq class="custom-faq-style"]
```

### Specific Category
```php
[oa_faq category="general"]
```

### Multiple Categories
```php
[oa_faq category="product-a,product-b"]
```

### Disable Auto-Detection
```php
[oa_faq auto_detect="no" category="general-faqs"]
```

## Migration from Timberfans Plugin

If you're migrating from the Timberfans product mods plugin:

1. **Content Migration**: FAQs are stored as custom post types, so they should be preserved
2. **Styling**: The accordion styles are identical to the Timberfans plugin
3. **Functionality**: All accordion behavior is maintained
4. **Categories**: FAQ categories are preserved and work the same way

## Support

For support or feature requests, please contact the development team.
