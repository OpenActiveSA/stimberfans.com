<?php
/*
Plugin Name: Open Agency: Currency Converter
Description: Allows entering pricing in USD with automatic conversion from ZAR. Includes daily exchange rate updates via cronjob.
Version: 1.0
Author: Open Agency
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OA_CC_PLUGIN_VERSION', '1.0');
define('OA_CC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OA_CC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once OA_CC_PLUGIN_PATH . 'includes/class-oa-cc-core.php';
require_once OA_CC_PLUGIN_PATH . 'includes/class-oa-cc-admin.php';
require_once OA_CC_PLUGIN_PATH . 'includes/class-oa-cc-api.php';
require_once OA_CC_PLUGIN_PATH . 'includes/class-oa-cc-cron.php';

// Initialize the plugin
function oa_cc_init() {
    new OA_CC_Core();
    new OA_CC_Admin();
    new OA_CC_Cron();
}
add_action('plugins_loaded', 'oa_cc_init');

// Activation hook - set up cron schedule
register_activation_hook(__FILE__, 'oa_cc_activate');
function oa_cc_activate() {
    // Schedule daily exchange rate update
    if (!wp_next_scheduled('oa_cc_daily_exchange_rate_update')) {
        wp_schedule_event(time(), 'daily', 'oa_cc_daily_exchange_rate_update');
    }
    
    // Set default exchange rate if not set
    $rate = get_option('oa_cc_usd_to_zar_rate');
    if (empty($rate)) {
        // Default fallback rate (can be updated by cron)
        update_option('oa_cc_usd_to_zar_rate', 18.5);
    }
}

// Deactivation hook - clean up cron
register_deactivation_hook(__FILE__, 'oa_cc_deactivate');
function oa_cc_deactivate() {
    wp_clear_scheduled_hook('oa_cc_daily_exchange_rate_update');
}
