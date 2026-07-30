# Price Display Changes Summary

## Overview
Modified the plugin to ensure product prices display correctly even when stock is zero.

## Changes Made

### 1. Shop/Archive Pages - "From X" Format
**File:** `includes/class-oa-tfp-core.php`

Added filters to:
- Display "From [lowest price]" instead of price range (e.g., "R1,200 - R2,500")
- Show prices even when all variations are out of stock
- Include all variations in price calculations

**Filters Added:**
- `woocommerce_variable_sale_price_html`
- `woocommerce_variable_price_html`
- `woocommerce_get_children`
- `woocommerce_product_is_visible`

### 2. Single Product Pages - Price Display for Zero Stock
**File:** `includes/class-oa-tfp-core.php`

Modified variation data to ensure prices display when stock is zero:
- Forces `display_price` to always be included in variation data
- Sets `is_purchasable` to true for out-of-stock items
- Preserves `is_in_stock` status so quote button logic works correctly

**Filter Added:**
- `woocommerce_available_variation`

### 3. CSS Updates
**File:** `assets/oa-tfp-styles.css`

Changed price visibility rules:
- Hide prices only on single product pages (where custom subtotal displays)
- Show prices on shop/archive pages

## How It Works

### Shop Pages
1. Plugin gets all variations (including out-of-stock)
2. Finds the minimum price across all variations
3. Displays as "From R[price]"

### Single Product Pages
1. When a variation is selected, the plugin ensures price data is available
2. JavaScript calculates the custom subtotal including base price + add-ons
3. Custom subtotal displays with "Request Quote" button for out-of-stock items
4. Custom subtotal displays with "Add to Cart" button for in-stock items

## Testing
1. **Clear cache:** Run `/wp-content/plugins/oa-timberfans-product-mods/clear-price-cache.php`
2. **Shop page:** Prices should show as "From R[price]" even for zero-stock products
3. **Product page:** Select any variation (in or out of stock) and price should display

## Note
Delete `clear-price-cache.php` after use for security.

