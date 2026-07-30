# Quick Fix Checklist

## ⚡ If Quote Button Suddenly Stopped Working

Try these fixes in order:

### Fix #1: Clear Browser Cache (Most Common)
1. Press `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac) to hard refresh
2. Or open incognito/private window and test there
3. **Why:** Cached JavaScript might be interfering

### Fix #2: Verify Quote Page Setting
1. Go to WordPress Admin → **Settings → OA TFP Product Mods**
2. Check that "Quote Page" dropdown has a page selected
3. If it says "-- Select a page --", select your quote page
4. Click **Save Changes**
5. **Why:** This setting was likely reset when you copied from live site

### Fix #3: Check Plugin Status
1. Go to WordPress Admin → **Plugins**
2. Verify these are **Active**:
   - ✓ Open Agency: Timberfans Product Mods
   - ✓ Open Agency: TimberFans GF Mod
   - ✓ WooCommerce
   - ✓ Gravity Forms
3. If any are inactive, click **Activate**

### Fix #4: Verify Gravity Form Exists
1. Go to **Forms** in WordPress admin
2. Look for a form with ID **3**
3. Open it and verify it has these field IDs:
   - Field 1, 2, 3, 4, 5, 6, 7, 9
4. **Why:** The code is hardcoded to use Form #3

### Fix #5: Test with Browser Console
1. Go to a product page
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Look for any red error messages
5. If you see errors, they'll tell you what's wrong

### Fix #6: Clear localStorage
1. On the quote page, press **F12**
2. In Console, type: `localStorage.clear()`
3. Press Enter
4. Go back to product page and try again
5. **Why:** Old data might be causing conflicts

## 🎯 Quick Test

**To verify it's working:**

1. Visit: `your-site.com/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php`
2. If you see green checkmarks ✓ for everything, the system is configured
3. If you see red ✗, fix those issues first

## 🔧 If You Copied from Live Site

When you copy files from the live site, you might lose:
- WordPress settings (like the Quote Page setting)
- Plugin activation status
- Database options

**What to check:**
1. ✓ Re-configure quote page setting (Fix #2 above)
2. ✓ Re-activate plugins if needed (Fix #3 above)
3. ✓ Clear browser cache (Fix #1 above)

## 📞 Still Not Working?

If none of these fix it:

1. Run the diagnostic tool: `test-quote-system.php`
2. Add debug shortcode to quote page: `[oa_tfp_quote_debug]`
3. Check browser Console for errors
4. See `TESTING-README.md` for detailed debugging steps

## 💡 Pro Tip

Add the debug shortcode to your quote page temporarily:

```
[oa_tfp_quote_debug]
```

This will show you exactly what's happening with the data transfer. Remove it once everything is working.

---

**Most likely fix:** Clear browser cache + Re-configure quote page setting


