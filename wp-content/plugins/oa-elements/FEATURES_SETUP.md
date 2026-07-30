# Features Shortcode Setup Guide

## Overview

The Features shortcode now automatically detects the current product and displays features specific to that product. This allows you to use the same shortcode in a shared footer while showing different features for each product.

## How It Works

1. **Auto-Detection**: The shortcode automatically detects the current post/product using its slug
2. **Category Matching**: It looks for feature categories that match the post slug
3. **Fallback Logic**: If no post-specific features are found, it tries fallback categories
4. **Flexbox Layout**: Uses flexbox for better centering and alignment control

## Setup Instructions

### 1. Create Post-Specific Feature Categories

For each post/product, create a feature category with the post's slug:

**Option A: Manual Creation**
1. Go to **Features > Categories**
2. Create a new category with the slug matching your post slug
   - Example: If your post slug is `ceiling-fan-model-xyz`, create a category with slug `ceiling-fan-model-xyz`

**Option B: Using Helper Function**
```php
// In your theme's functions.php or a custom plugin
$category_id = OA_Elements_Utilities::create_product_feature_category('ceiling-fan-model-xyz');
```

### 2. Add Features to Post Categories

1. Go to **Features > Add New**
2. Create your feature with title, content, and icon
3. Assign it to the appropriate post category
4. Repeat for all features for that post

### 3. Use the Shortcode

Simply add the shortcode to your shared footer:

```php
[oa_features]
```

The shortcode will automatically:
- Detect the current post/product
- Find features in the matching category
- Display them with the default styling (left-aligned by default)
- Use flexbox layout for responsive design
- Apply the specified maximum width to each feature item (or auto-size if empty)
- Center remaining items when fewer items than columns

## Category Naming Patterns

The shortcode tries these patterns in order:

1. **Exact post slug**: `ceiling-fan-model-xyz`
2. **Product prefix**: `product-ceiling-fan-model-xyz`
3. **Features prefix**: `features-ceiling-fan-model-xyz`
4. **Default fallback**: `default-features`

## Advanced Usage

### Layout Attributes
- **`columns`**: Number of columns to display (default: `3`, priority over width constraints)
- **`max_width`**: Maximum width of each feature item (default: `400px`)
- **Empty max_width value**: Use `max_width=""` for auto-sizing based on content

### Manual Category Override
```php
[oa_features category="my-custom-category"]
```

### Disable Auto-Detection
```php
[oa_features auto_detect="no" category="default-features"]
```

### Custom Layout
```php
[oa_features columns="4" align="left" max_width="500px"]
```

### Column-Based Layout
```php
[oa_features columns="2"]  # 2 columns, auto width
[oa_features columns="4" max_width="300px"]  # 4 columns, max 300px per item
[oa_features columns="6"]  # 6 columns, auto width
```

## Troubleshooting

### No Features Showing
1. Check that the product slug matches the category slug exactly
2. Verify features are published and assigned to the correct category
3. Try creating a `default-features` category as fallback

### Wrong Features Showing
1. Check category assignments in the Features admin
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

## Helper Functions

### Create Category Programmatically
```php
$category_id = OA_Elements_Utilities::create_product_feature_category('product-slug', 'Category Name');
```

### Get All Feature Categories
```php
$categories = OA_Elements_Utilities::get_product_feature_categories();
```

## Examples

### Basic Usage
```php
[oa_features]
```

### With Custom Styling
```php
[oa_features columns="3" align="center" max_width="400px"]
```

### Default Left Alignment
```php
[oa_features]
```

### Multiple Categories
```php
[oa_features category="product-a,product-b"]
```

### Auto Sizing
```php
[oa_features max_width=""]
```

### Disable Auto-Detection
```php
[oa_features auto_detect="no" category="general-features"]
``` 