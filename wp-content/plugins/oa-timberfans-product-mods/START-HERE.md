# 🚀 Start Here - Quote Button Testing

## What I've Created for You

I've analyzed your quote button system and created comprehensive testing tools. Everything you need is in this folder.

## 📁 Files Created

1. **START-HERE.md** ← You are here!
2. **QUICK-FIX.md** - Fast fixes for common issues
3. **TESTING-README.md** - Complete overview and testing guide
4. **QUOTE-TESTING-GUIDE.md** - Detailed step-by-step testing instructions
5. **test-quote-system.php** - Diagnostic tool (run in browser)
6. **[oa_tfp_quote_debug]** - New shortcode for debugging (added to plugin)

## ⚡ Quick Start (5 minutes)

### Step 1: Run the Diagnostic Tool
Open this URL in your browser:
```
https://your-site.com/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php
```

Replace `your-site.com` with your actual domain.

**What it checks:**
- ✓ Are all plugins active?
- ✓ Is the quote page configured?
- ✓ Does Gravity Form #3 exist?
- ✓ Are there products to test with?

### Step 2: Fix Any Issues

If the diagnostic shows problems:
- ❌ Plugin inactive → Activate it in WordPress admin
- ❌ Quote page not set → Go to Settings → OA TFP Product Mods
- ❌ Form missing → Check that you have Gravity Form with ID 3

### Step 3: Test on a Product Page

1. Visit a product page (diagnostic tool lists products with out-of-stock variations)
2. Select variations until you find one that's out of stock
3. **Expected:** Quote button should appear
4. Click it and see if you're redirected to the quote page
5. **Expected:** Form should auto-populate with your selections

## 🎯 If It's Not Working

### Most Common Issue: Quote Page Not Configured

**Fix in 30 seconds:**
1. WordPress Admin → Settings → OA TFP Product Mods
2. Under "Quote Page", select your page with the Gravity Form
3. Save Settings
4. Test again

**Why this happens:** When you copy from live site, WordPress settings are lost.

### Second Most Common: Browser Cache

**Fix:**
- Press `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)
- Or test in incognito/private window

### Need More Help?

See **QUICK-FIX.md** for all common fixes.

## 🔍 Deep Testing

If you want to thoroughly test and understand the system:

1. Read **TESTING-README.md** for overview
2. Follow **QUOTE-TESTING-GUIDE.md** for step-by-step testing
3. Add `[oa_tfp_quote_debug]` shortcode to your quote page to see live data
4. Open browser Console (F12) to see detailed logs

## 🎓 Understanding Your System

### How Quote Button Works

**Product Page:**
1. User selects product variation
2. System checks if in stock
3. If out of stock → Show quote button, hide Add to Cart
4. User clicks quote button
5. JavaScript collects all product selections
6. Stores in browser localStorage
7. Redirects to quote page

**Quote Page:**
1. JavaScript reads localStorage
2. Finds Gravity Form #3
3. Populates fields based on field IDs
4. Clears localStorage

### What Gets Transferred

- Product name
- Fan size selected
- Timber finish selected
- Metal finish selected
- Quantity
- Price
- Selected add-ons

### Required Configuration

- **Quote page must be set** in plugin settings
- **Gravity Form ID must be 3**
- **Form must have specific field IDs** (see TESTING-README.md)
- **Product must be variable** with out-of-stock variations

## 🐛 Debugging Tools You Have

### 1. Diagnostic Page (Browser)
- URL: `your-site.com/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php`
- Shows: System status, active plugins, form configuration
- Use: First thing to check when something's wrong

### 2. Debug Shortcode (WordPress Page)
- Add: `[oa_tfp_quote_debug]` to any page
- Shows: Real-time localStorage data, available form fields
- Use: See exactly what data is being transferred

### 3. Browser Console (F12)
- The system logs EVERYTHING to console
- Look for: "=== QUOTE DATA BEING SENT ===" messages
- Use: See exactly what's happening at each step

## ✅ Success Checklist

Your system is working when:

- [ ] Diagnostic page shows all green checkmarks
- [ ] Quote button appears when selecting out-of-stock variation
- [ ] Clicking quote button shows console log with data
- [ ] Quote page loads and shows console log "QUOTE DATA RECEIVED"
- [ ] Form fields auto-populate within 3 seconds
- [ ] All selected options appear in the form correctly

## 📚 Which Document to Read?

**Choose based on your situation:**

| Your Situation | Read This |
|----------------|-----------|
| Need quick fix | QUICK-FIX.md |
| Want complete overview | TESTING-README.md |
| Need step-by-step testing | QUOTE-TESTING-GUIDE.md |
| Want to understand code | Check file comments in `includes/class-oa-tfp-core.php` |

## 💡 Best Testing Approach

1. **Run diagnostic** (2 min)
2. **Fix any red X issues** (2 min)
3. **Test on product page with F12 console open** (3 min)
4. **If not working, add debug shortcode to quote page** (2 min)
5. **If still stuck, read detailed testing guide** (10 min)

## 🎉 Quick Win

**Try this right now:**

1. Open: `your-site.com/wp-content/plugins/oa-timberfans-product-mods/test-quote-system.php`
2. If you see "System appears to be configured correctly!" at bottom
3. Click a product link from that page
4. Test the quote button
5. 🎊 It should work!

---

## 📞 What to Do If You're Stuck

1. ✓ Check QUICK-FIX.md first
2. ✓ Run test-quote-system.php
3. ✓ Add [oa_tfp_quote_debug] to quote page
4. ✓ Open browser Console (F12) and look for errors
5. ✓ Take screenshots of any errors and the diagnostic page

The system has VERY verbose logging. If something is wrong, the Console will tell you exactly what.

---

**Pro Tip:** Keep the diagnostic page bookmarked. Run it anytime something stops working to quickly see what changed.

Good luck! 🚀


