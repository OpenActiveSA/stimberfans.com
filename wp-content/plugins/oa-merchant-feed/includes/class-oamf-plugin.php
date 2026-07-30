<?php
/**
 * Main plugin loader.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-admin.php';
require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-feed-controller.php';

/**
 * Singleton.
 */
final class OAMF_Plugin {

	/**
	 * @var OAMF_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @return OAMF_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( OAMF_PLUGIN_FILE, array( $this, 'activate' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'oa-merchant-feed', false, dirname( plugin_basename( OAMF_PLUGIN_FILE ) ) . '/languages' );
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		OAMF_Admin::instance();
		OAMF_Feed_Controller::instance();
	}

	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Open Active Merchant Feed requires WooCommerce to be installed and active.', 'oa-merchant-feed' );
		echo '</p></div>';
	}

	public function activate() {
		$settings = get_option( OAMF_OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		if ( empty( $settings['secret_key'] ) ) {
			$settings['secret_key'] = wp_generate_password( 32, false, false );
		}
		update_option( OAMF_OPTION_KEY, array_merge( oamf_default_settings(), $settings ), false );
	}
}

/**
 * Default settings merged on save and activation.
 *
 * @return array<string, mixed>
 */
function oamf_default_settings() {
	return array(
		'secret_key'                => '',
		'feed_format'               => 'xml',
		'variable_mode'             => 'variation',
		'variation_merge_color_material' => 0,
		'variation_group_attributes' => array(),
		'include_type_simple'     => 1,
		'include_type_variable'   => 1,
		'include_type_grouped'    => 1,
		'include_type_external'   => 1,
		'include_type_other'      => 1,
		'include_virtual'         => 1,
		'include_downloadable'    => 1,
		'include_only_instock'    => 1,
		'exclude_product_ids'     => array(),
		'include_only_product_ids' => array(),
		'exclude_product_category_ids' => array(),
		'include_only_product_category_ids' => array(),
		'title_mode'              => 'name_attrs',
		'title_prefix'            => '',
		'title_suffix'            => '',
		'title_separator'         => ' — ',
		'title_template'          => '{product_name}',
		'description_mode'        => 'content',
		'brand'                   => '',
		'google_product_category' => '',
		'map_color'               => '',
		'map_size'                => '',
		'map_material'            => '',
		'map_pattern'             => '',
		'gtin_meta_key'           => '_global_unique_id',
		'mpn_meta_key'            => '',
		'product_type_source'     => 'category',
		'additional_images'       => 1,
		'condition'               => 'new',
	);
}

/**
 * True when this product or variation must not appear in the feed for inventory reasons.
 *
 * When WooCommerce “Manage stock” is enabled store-wide: each SKU must track stock (variation/parent
 * “Manage stock?” or equivalent) and have a quantity greater than zero, except sellable backorders.
 * Status-only “In stock” without a quantity is omitted. Variable parent rows skip the quantity rule;
 * listable children are enforced in OAMF_Items::build_parent_variable_row.
 *
 * When store-wide stock management is off, only WooCommerce stock status / is_in_stock() apply.
 *
 * @param WC_Product $product Product or variation.
 */
function oamf_product_excluded_from_feed_stock( WC_Product $product ): bool {
	$site_tracks = ( 'yes' === get_option( 'woocommerce_manage_stock' ) );

	if ( $site_tracks && ! $product->is_type( 'variable' ) ) {
		if ( ! $product->managing_stock() ) {
			return true;
		}
		$qty = $product->get_stock_quantity();
		$has_positive_qty = is_numeric( $qty ) && (float) $qty > 0;
		if ( ! $has_positive_qty ) {
			if ( 'onbackorder' === $product->get_stock_status() && $product->is_in_stock() ) {
				return false;
			}
			return true;
		}
	}

	if ( 'outofstock' === $product->get_stock_status() ) {
		return true;
	}
	if ( ! $product->is_in_stock() ) {
		return true;
	}
	if ( $site_tracks && $product->managing_stock() && ! $product->is_type( 'variable' ) ) {
		$qty = $product->get_stock_quantity();
		if ( null !== $qty && is_numeric( $qty ) && (float) $qty <= 0 && 'onbackorder' !== $product->get_stock_status() ) {
			return true;
		}
	}

	return false;
}

/**
 * @return array<string, mixed>
 */
function oamf_get_settings() {
	$stored = get_option( OAMF_OPTION_KEY, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$s = array_merge( oamf_default_settings(), $stored );
	// Legacy: merged rows used map_color + map_material when the old checkbox was on.
	if ( empty( $s['variation_group_attributes'] ) && ! empty( $s['variation_merge_color_material'] ) ) {
		$c = isset( $s['map_color'] ) ? (string) $s['map_color'] : '';
		$m = isset( $s['map_material'] ) ? (string) $s['map_material'] : '';
		if ( '' !== $c && '' !== $m ) {
			$s['variation_group_attributes'] = array( $c, $m );
		}
	}
	return $s;
}
