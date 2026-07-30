# Open Agency: Currency Converter

A WordPress plugin that allows entering product prices in ZAR (South African Rand) with automatic conversion to USD. Includes daily exchange rate updates via cronjob.

## Features

- **ZAR Price Input**: Enter product prices in ZAR through a convenient meta box
- **Automatic Conversion**: Prices are automatically converted to USD using the current exchange rate
- **USD Storage**: USD prices are stored as the WooCommerce product price
- **Daily Updates**: Exchange rates are updated automatically once per day via WordPress cron
- **Manual Updates**: Manually trigger exchange rate updates from the admin settings page
- **Multiple API Support**: Tries multiple free and paid API sources for exchange rates
- **Variation Support**: Works with WooCommerce variable products and their variations
- **Admin Settings**: Easy-to-use settings page for managing exchange rates and API keys

## Installation

1. Upload the `oa-currency-converter` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Currency Converter to configure

## Usage

### Setting Up Exchange Rates

1. Navigate to **Settings > Currency Converter**
2. The plugin will automatically try to fetch exchange rates from free APIs
3. Optionally, add API keys for Fixer.io or Currencylayer for more reliable updates
4. Click "Update Exchange Rate Now" to manually update the rate

### Entering Product Prices

1. Edit any WooCommerce product
2. You'll see a new "ZAR Price Input" meta box on the right side
3. Enter the price in ZAR
4. The plugin automatically converts it to USD and saves it as the product price
5. The converted USD price is displayed below the input field

### Variable Products

For variable products:
1. Edit the product as usual
2. In the Variations section, you'll see a new "ZAR Price" field for each variation
3. Enter the ZAR price for each variation
4. Prices are automatically converted to USD

## API Sources

The plugin tries multiple API sources in this order:

1. **exchangerate-api.com** (Free, no API key required) - Used by default
2. **Fixer.io** (Requires API key) - Optional, more reliable
3. **Currencylayer** (Requires API key) - Optional, backup option

## Cron Schedule

The plugin automatically schedules a daily exchange rate update when activated. The cron job runs once per day and updates all product prices that have ZAR prices stored.

## Settings

- **Current Exchange Rate**: Manually set or view the current USD to ZAR exchange rate
- **Fixer.io API Key**: Optional API key for Fixer.io service
- **Currencylayer API Key**: Optional API key for Currencylayer service

## Technical Details

- Exchange rates are stored in WordPress options
- ZAR prices are stored as post meta (`_oa_cc_zar_price`)
- USD prices are stored as standard WooCommerce price meta (`_price`, `_regular_price`)
- When exchange rates update, all products with ZAR prices are automatically recalculated and converted to USD

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+

## Support

For issues or questions, contact Open Agency.
