<?php
/**
 * Plugin Name: Open Agency: WooCommerce Stock
 * Plugin URI: https://openagency.com
 * Description: Match accounting CSV rows to WooCommerce variations and import stock. Export stock CSV.
 * Version: 1.0.53
 * Author: Open Agency
 * Author URI: https://openagency.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oa-woo-stock
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OA_WOO_STOCK_VERSION', '1.0.53' );
define( 'OA_WOO_STOCK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OA_WOO_STOCK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OA_WOO_STOCK_PLUGIN_FILE', __FILE__ );

/**
 * Bootstrap.
 */
class OA_Woo_Stock {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		require_once OA_WOO_STOCK_PLUGIN_DIR . 'includes/class-stock-importer.php';
		require_once OA_WOO_STOCK_PLUGIN_DIR . 'includes/class-stock-exporter.php';
		require_once OA_WOO_STOCK_PLUGIN_DIR . 'includes/class-api-handler.php';

		if ( is_admin() ) {
			require_once OA_WOO_STOCK_PLUGIN_DIR . 'admin/class-admin.php';
			OA_Woo_Stock_Admin::get_instance();
		}

		OA_Woo_Stock_API::get_instance();
	}

	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Open Agency: WooCommerce Stock requires WooCommerce to be installed and active.', 'oa-woo-stock' ); ?></p>
		</div>
		<?php
	}
}

OA_Woo_Stock::get_instance();
