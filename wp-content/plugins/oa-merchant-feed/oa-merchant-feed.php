<?php
/**
 * Plugin Name: Open Active Merchant Feed
 * Description: Google Merchant Center product feeds (XML or TSV) with configurable fields and variable-product handling. No Google Ads.
 * Version: 1.0.0
 * Author: Open Active
 * Text Domain: oa-merchant-feed
 * Requires Plugins: woocommerce
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OAMF_VERSION', '1.0.0' );
define( 'OAMF_PLUGIN_FILE', __FILE__ );
define( 'OAMF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OAMF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OAMF_OPTION_KEY', 'oamf_settings' );

require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-plugin.php';

/**
 * Bootstrap.
 *
 * @return OAMF_Plugin
 */
function oamf_plugin() {
	return OAMF_Plugin::instance();
}

oamf_plugin();
