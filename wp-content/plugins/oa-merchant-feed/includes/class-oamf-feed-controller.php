<?php
/**
 * Public REST feed endpoint.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-items.php';
require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-feed-builder.php';

/**
 * Registers REST route and serves feed.
 */
final class OAMF_Feed_Controller {

	/**
	 * @var OAMF_Feed_Controller|null
	 */
	private static $instance = null;

	/**
	 * @return OAMF_Feed_Controller
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'maybe_serve_raw_body' ), 10, 4 );
	}

	public function register_routes() {
		register_rest_route(
			'oamf/v1',
			'/merchant-feed',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'serve' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param string $secret_key Current key.
	 */
	public static function get_feed_url( string $secret_key ): string {
		$url = rest_url( 'oamf/v1/merchant-feed' );
		return add_query_arg(
			array(
				'key' => $secret_key,
			),
			$url
		);
	}

	/**
	 * Output XML/TSV directly instead of JSON-encoding the response body.
	 *
	 * @param bool                  $served Whether the request was served.
	 * @param WP_HTTP_Response|null $result  Response object.
	 * @param WP_REST_Request       $request Request.
	 * @param WP_REST_Server        $server  Server.
	 */
	public function maybe_serve_raw_body( $served, $result, $request, $server ) {
		unset( $server );
		if ( ! $result instanceof WP_REST_Response || ! $request instanceof WP_REST_Request ) {
			return $served;
		}
		if ( '/oamf/v1/merchant-feed' !== $request->get_route() ) {
			return $served;
		}
		if ( 200 !== $result->get_status() ) {
			return $served;
		}
		$data = $result->get_data();
		if ( ! is_string( $data ) ) {
			return $served;
		}

		status_header( 200 );
		nocache_headers();

		$headers = $result->get_headers();
		if ( is_array( $headers ) ) {
			foreach ( $headers as $name => $value ) {
				if ( is_array( $value ) ) {
					$value = reset( $value );
				}
				if ( ! is_string( $name ) || ! is_string( $value ) ) {
					continue;
				}
				header( $name . ': ' . $value, false );
			}
		}
		echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw feed bytes.
		return true;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|void
	 */
	public function serve( WP_REST_Request $request ) {
		$key = (string) $request->get_param( 'key' );
		$s   = oamf_get_settings();
		if ( '' === $key || ! hash_equals( (string) $s['secret_key'], $key ) ) {
			return new WP_REST_Response(
				array( 'error' => 'invalid_key' ),
				401
			);
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_REST_Response(
				array( 'error' => 'woocommerce_inactive' ),
				503
			);
		}

		$items = new OAMF_Items( $s );
		$rows  = $items->get_rows();

		$builder = new OAMF_Feed_Builder( $s );
		if ( 'tsv' === $s['feed_format'] ) {
			$body = $builder->build_tsv( $rows );
			$type = 'text/tab-separated-values; charset=utf-8';
			$name = 'merchant-feed.tsv';
		} else {
			$body = $builder->build_xml( $rows );
			$type = 'application/xml; charset=utf-8';
			$name = 'merchant-feed.xml';
		}

		$response = new WP_REST_Response( $body, 200 );
		$response->header( 'Content-Type', $type );
		$response->header( 'Content-Disposition', 'inline; filename="' . $name . '"' );

		return $response;
	}
}
