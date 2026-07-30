# TimberFans Quote Button - Testing & Debugging Guide

## 🎯 Quick Start

Your quote button system consists of two main plugins working together:
1. **oa-timberfans-product-mods** - Handles the product page and quote button
2. **oa-timberfans-gf-mod** - Populates the Gravity Form on the quote page

## 📋 What You Have Now

I've created several tools to help you test and debug the quote system:

### 1. **Diagnostic Test Page** 
   - **File:** `test-quote-system.php`
   - **URL:** Your site URL + `/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php`
   - **What it does:** Checks if all plugins are active, settings are configured, and lists your variable products
   - **Use this first** to verify everything is set up correctly

### 2. **Testing Guide** 
   - **File:** `QUOTE-TESTING-GUIDE.md`
   - **What it does:** Comprehensive step-by-step testing instructions
   - **Includes:** Browser console commands, troubleshooting tips, common issues

### 3. **Debug Shortcode**
   - **Shortcode:** `[oa_tfp_quote_debug]`
   - **Where to use:** Add this to your quote page or any test page
   - **What it shows:** Real-time view of localStorage data and form fields
   - **Helpful for:** Seeing exactly what data is being transferred

## 🚀 Testing Steps

### Step 1: Verify Configuration

1. Visit the diagnostic page (see URL above)
2. Check that all plugins show as "Active"
3. Verify Quote Page is configured
4. Confirm Gravity Form #3 exists

If any of these fail, the diagnostic page will tell you what to fix.

### Step 2: Configure Quote Page

1. Go to **Settings → OA TFP Product Mods** in WordPress admin
2. Under "Quote Page", select the page that has your Gravity Form
3. Save settings

### Step 3: Test on a Product Page

1. Find a product with out-of-stock variations (diagnostic page lists these)
2. Open browser Developer Tools (Press F12)
3. Go to the Console tab
4. Select product variations until you find one that's out of stock
5. **Expected Result:** Quote button appears, Add to Cart button hides

### Step 4: Test Data Collection

1. With Console still open, make your product selections
2. Click the "Request Quote" button
3. **Expected Console Output:**
   ```
   === QUOTE DATA BEING SENT ===
   Product Name: [your product]
   Fan Range: [product name]
   ...
   === END QUOTE DATA ===
   ```
4. You should be redirected to the quote page

### Step 5: Test Form Population

1. On quote page, Console should immediately show:
   ```
   === QUOTE DATA RECEIVED ON QUOTE PAGE ===
   ...
   === ATTEMPTING TO POPULATE FORM ===
   ```
2. Wait 3 seconds for the form to populate
3. Check that form fields are filled in with your selections

## 🔍 Using the Debug Shortcode

Add `[oa_tfp_quote_debug]` to your quote page:

1. Edit your quote page in WordPress
2. Add the shortcode anywhere on the page
3. View the page
4. You'll see a debug panel showing:
   - Current localStorage data
   - Available form fields
   - Real-time updates every 5 seconds

This is especially helpful to:
- Verify data is being stored
- See exactly what's in localStorage
- Check if data format matches what the form expects

## 🐛 Common Issues & Solutions

### Issue: Quote button never appears
**Cause:** Quote page not configured  
**Fix:** Go to Settings → OA TFP Product Mods → Select Quote Page

### Issue: Button appears but clicking does nothing
**Cause:** JavaScript error  
**Fix:** Open Console (F12), look for red error messages, share them for help

### Issue: Redirects but form is empty
**Possible causes:**
1. **Form field IDs don't match** - The system expects Form ID 3 with specific field IDs
2. **Form loads too slowly** - Wait 3 seconds, the code retries
3. **Field values don't match** - Product attributes must match Gravity Form option values exactly

**Fix:** Use the debug shortcode to see what data is available, then check your form field IDs

### Issue: Only some fields populate
**Cause:** Field values don't match exactly  
**Fix:** 
- In Console, check what values are being sent
- In Gravity Forms, check your field option values
- They must match exactly (including case and special characters)

## 📊 System Architecture

### How It Works

**On Product Page:**
1. User selects out-of-stock variation
2. JavaScript detects stock status
3. Shows quote button, hides Add to Cart
4. When clicked, collects all product data
5. Stores in browser localStorage
6. Redirects to quote page

**On Quote Page:**
1. JavaScript reads localStorage
2. Finds Gravity Form fields
3. Matches data to field IDs
4. Populates form fields
5. Clears localStorage

### Important Field Mappings

The system expects these Gravity Form field IDs:
- **Field 1:** Fan Range (radio buttons)
- **Field 2:** Product Name (text)
- **Field 3:** Quantity (text)
- **Field 4:** Fan Size (radio buttons)
- **Field 5:** Timber Finish (radio buttons)
- **Field 6:** Metal Finish (radio buttons)
- **Field 7:** Speed Regulator (radio buttons)
- **Field 9:** Add-ons (textarea)

If your form uses different field IDs, you'll need to update the code in:
`class-oa-tfp-core.php` lines 774-832

## 🔧 Files You Can Modify

If you need to customize the system:

### Quote Button Display Logic
**File:** `includes/class-oa-tfp-core.php`
**Function:** `add_button_toggle_js()` (lines 136-722)
**What it controls:** When quote button shows/hides

### Data Collection
**File:** `includes/class-oa-tfp-core.php`
**Function:** Inside `add_button_toggle_js()` (lines 593-679)
**What it controls:** What product data gets collected

### Form Population
**File:** `includes/class-oa-tfp-core.php`
**Function:** `add_quote_form_js()` (lines 727-857)
**What it controls:** How form fields get populated

### Quote Button HTML
**File:** `includes/class-oa-tfp-core.php`
**Function:** `add_quote_button()` (lines 95-109)
**What it controls:** Quote button HTML and styling

## 📝 Testing Checklist

Before reporting issues, please verify:

- [ ] Both plugins are active (check diagnostic page)
- [ ] Quote page is configured and published
- [ ] Gravity Form #3 exists
- [ ] Form has fields with correct IDs
- [ ] Product is a variable product
- [ ] Product has out-of-stock variations
- [ ] Browser console shows no JavaScript errors
- [ ] localStorage is enabled in browser
- [ ] You're testing in a modern browser (Chrome, Firefox, Edge, Safari)

## 💡 Tips for Success

1. **Use Chrome or Firefox for testing** - Better developer tools
2. **Keep Console open** - All debug info is logged there
3. **Test with a fresh browser tab** - Clears any cached data
4. **Clear localStorage between tests** - Use the debug shortcode's "Clear" button
5. **Check one issue at a time** - Makes debugging easier

## 🆘 Getting Help

If you're still stuck after trying these steps:

1. Visit the diagnostic page and screenshot the results
2. Open Console on product page and screenshot any errors
3. Share the console output from when you click quote button
4. Share the console output from the quote page
5. Confirm your Gravity Form ID and field IDs

## 📚 Additional Resources

- **Full Testing Guide:** See `QUOTE-TESTING-GUIDE.md` for detailed instructions
- **Diagnostic Tool:** Run `test-quote-system.php` for system status
- **Debug Shortcode:** Add `[oa_tfp_quote_debug]` to any page
- **Console Test Scripts:** See testing guide for ready-to-use console commands

---

## 🎉 Quick Win Test

Want to see if it's working right now?

1. Visit: `your-site.com/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php`
2. Look for the summary at the bottom
3. If it says "System appears to be configured correctly!" - proceed to test on a product page
4. If not, fix the issues shown on the diagnostic page first

Good luck with your testing! The quote system should work smoothly once configured correctly.


