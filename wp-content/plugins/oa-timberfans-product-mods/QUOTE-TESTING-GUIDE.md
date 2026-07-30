# TimberFans Quote Button Testing Guide

## System Overview

The quote button functionality consists of two main parts:

1. **Product Page (oa-timberfans-product-mods plugin)**
   - Shows quote button when product variation is out of stock
   - Collects product selection data
   - Stores data in browser localStorage
   - Redirects to quote page

2. **Quote Page (both plugins)**
   - Reads data from localStorage
   - Auto-populates Gravity Form #3 with product data

## Prerequisites Checklist

Before testing, verify these settings:

### 1. Plugin Settings
- [ ] Go to **Settings → OA TFP Product Mods**
- [ ] Verify "Quote Page" is selected (should point to your quote page)
- [ ] Note the page slug (e.g., `/quote/`)

### 2. Gravity Form Setup
- [ ] Form ID must be **3** (hardcoded in the system)
- [ ] Required field IDs:
  - **Field 1**: Fan Range (radio buttons)
  - **Field 2**: Product Name (text - optional)
  - **Field 3**: Quantity (text - optional)
  - **Field 4**: Fan Size (radio buttons)
  - **Field 5**: Timber Finish (radio buttons)
  - **Field 6**: Metal Finish (radio buttons)
  - **Field 7**: Speed Regulator (radio buttons)
  - **Field 9**: Add-ons (textarea - optional)

### 3. Product Setup
- [ ] Product must be a **Variable Product**
- [ ] Product must have variations
- [ ] At least one variation must be **Out of Stock**

## Step-by-Step Testing

### Test 1: Verify Quote Button Appears

1. Go to a product page with out-of-stock variations
2. Open browser Developer Tools (F12)
3. Go to Console tab
4. Select variations until you find one that's out of stock
5. **Expected**: Quote button should appear, Add to Cart button should hide

**Troubleshooting**:
- If button doesn't appear, check console for errors
- Verify the product is a variable product
- Check that the variation is actually marked as out of stock

### Test 2: Verify Data Collection

1. On product page with quote button visible:
2. Open Console (F12)
3. Select your desired options:
   - Fan size
   - Timber finish
   - Metal finish
   - Quantity
   - Any add-ons
4. Click the "Request Quote" button
5. In Console, you should see:
   ```
   === QUOTE DATA BEING SENT ===
   Product Name: [product name]
   Fan Range: [product name]
   Fan Size: [selected size]
   Timber Finish: [selected timber]
   Metal Finish: [selected metal]
   ...
   === END QUOTE DATA ===
   ```

**Troubleshooting**:
- If no console output, JavaScript isn't loading
- Check Network tab for failed script loads
- Verify oa-tfp-product-mods plugin is active

### Test 3: Verify Data Transfer to Quote Page

1. After clicking quote button, you should be redirected to quote page
2. Open Console (F12) IMMEDIATELY
3. Look for console output:
   ```
   === QUOTE DATA RECEIVED ON QUOTE PAGE ===
   Product Name: [product name]
   ...
   === ATTEMPTING TO POPULATE FORM ===
   ```

**Troubleshooting**:
- If no data received, check localStorage:
  - In Console, type: `localStorage.getItem('timberfans_quote_data')`
  - If null, data wasn't stored on product page
- If data is there but form isn't populating:
  - Check that Gravity Form is actually on the page
  - Verify Form ID is 3
  - Verify field IDs match expected values

### Test 4: Verify Form Population

1. On quote page with console open
2. After form loads, check each field:
   - [ ] Fan Range (Field 1) - should be selected
   - [ ] Product Name (Field 2) - should be filled
   - [ ] Quantity (Field 3) - should be filled
   - [ ] Fan Size (Field 4) - should be selected
   - [ ] Timber Finish (Field 5) - should be selected
   - [ ] Metal Finish (Field 6) - should be selected
   - [ ] Speed Regulator (Field 7) - should auto-select first option
   - [ ] Add-ons (Field 9) - should list selected add-ons

**Troubleshooting**:
- If form doesn't populate, check field IDs in console:
  ```javascript
  // Type this in console to see all input names:
  jQuery('input[name^="input_"]').each(function() { 
      console.log(jQuery(this).attr('name'), jQuery(this).attr('type')); 
  });
  ```
- Verify field IDs match what the code expects

## Common Issues & Solutions

### Issue 1: Quote button never appears
**Cause**: Quote page not configured
**Solution**: Go to Settings → OA TFP Product Mods → Select Quote Page

### Issue 2: Button appears but doesn't redirect
**Cause**: JavaScript error or missing quote URL
**Solution**: Check console for errors, verify quote page is published

### Issue 3: Redirects but form is empty
**Cause**: Form field IDs don't match OR form loads too slowly
**Solution**: 
- Verify Form ID is 3
- Check field IDs match expected values
- The code retries after 3 seconds, wait to see if it populates

### Issue 4: Only some fields populate
**Cause**: Field values don't match exactly
**Solution**: 
- Check console logs to see what values are being sent
- Verify Gravity Form field values match product attribute slugs
- Example: If product has "1200mm" but form has "1200", they won't match

### Issue 5: Data was working, now it's not
**Cause**: Likely copied files from live site and lost custom code
**Solution**: Compare plugin files with working version, especially:
- `class-oa-tfp-core.php` (lines 593-679 for data collection)
- `class-oa-tfp-core.php` (lines 727-857 for form population)

## Manual Testing Commands

Open Console (F12) and run these commands to test:

### Check if jQuery is loaded
```javascript
typeof jQuery
// Should return "function"
```

### Check if variations data exists
```javascript
jQuery('form.variations_form').data('product_variations')
// Should show array of variations
```

### Check stored quote data
```javascript
localStorage.getItem('timberfans_quote_data')
// Should show JSON string or null
```

### Manually trigger form population (on quote page)
```javascript
var data = JSON.parse(localStorage.getItem('timberfans_quote_data'));
console.log(data);
// Inspect the data structure
```

### Check if Gravity Form exists
```javascript
jQuery('form').each(function() { 
    console.log('Form ID:', jQuery(this).attr('id'), 'Class:', jQuery(this).attr('class')); 
});
```

## Quick Debug Script

Add this to your browser console on the product page to see what's happening:

```javascript
// Monitor variation changes
jQuery('form.variations_form').on('found_variation', function(e, variation) {
    console.log('Variation found:', variation.variation_id);
    console.log('In stock?', variation.is_in_stock);
    console.log('Purchasable?', variation.is_purchasable);
});

// Monitor quote button clicks
jQuery(document).on('click', '.oa-tfp-quote-button', function(e) {
    console.log('Quote button clicked!');
    console.log('Current data:', localStorage.getItem('timberfans_quote_data'));
});
```

## Expected Behavior Summary

**When variation is IN STOCK:**
- ✓ Add to Cart button visible
- ✓ Quantity field enabled
- ✓ Quote button hidden
- ✓ Price subtotal shown

**When variation is OUT OF STOCK:**
- ✓ Quote button visible
- ✓ Add to Cart button hidden
- ✓ Quantity field disabled
- ✓ Price subtotal shown (but can't add to cart)

**When clicking Quote button:**
- ✓ Console logs data being collected
- ✓ Data stored in localStorage
- ✓ Redirects to quote page
- ✓ On quote page, console logs data received
- ✓ Form auto-populates within 3 seconds
- ✓ localStorage cleared after population

## File Locations

If you need to inspect or modify the code:

**Product Page (Quote Button)**:
- Main file: `wp-content/plugins/oa-timberfans-product-mods/includes/class-oa-tfp-core.php`
- Button HTML: Lines 95-109 (`add_quote_button()`)
- Toggle JS: Lines 136-722 (`add_button_toggle_js()`)
- Data collection: Lines 593-679 (inside `collectAndSubmitQuote()`)

**Quote Page (Form Population)**:
- Same file: `class-oa-tfp-core.php`
- Population JS: Lines 727-857 (`add_quote_form_js()`)
- Population attempts: Lines 756-837 (`attemptFormPopulation()`)

**Form Field Population (Server-side)**:
- File: `wp-content/plugins/oa-timberfans-gf-mod/includes/class-oa-tf-form-populator.php`
- Populates dropdown options from WooCommerce data
- Runs when form renders (server-side)

## Next Steps

After testing, if issues persist:
1. Document which test step fails
2. Copy console error messages
3. Note which fields populate and which don't
4. Check if localStorage has data
5. Verify Gravity Form field IDs

The debugging output is very verbose, so all information needed should be in the console logs.


