<?php
/**
 * AJAX handlers for stock import preview/process and export.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OA_Woo_Stock_API {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_oa_woo_stock_import_preview', array( $this, 'handle_stock_import_preview' ) );
		add_action( 'wp_ajax_oa_woo_stock_import_process', array( $this, 'handle_stock_import_process' ) );
		add_action( 'wp_ajax_oa_woo_stock_export', array( $this, 'handle_stock_export' ) );
		add_action( 'wp_ajax_oa_woo_stock_variable_parents', array( $this, 'handle_variable_parents' ) );
	}

	/**
	 * List published variable products for bulk-apply scope (before CSV upload).
	 */
	public function handle_variable_parents() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'oa-woo-stock' ),
				)
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'oa_woo_stock_import' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'oa-woo-stock' ),
				)
			);
		}

		if ( ! function_exists( 'wc_get_products' ) ) {
			wp_send_json_success( array( 'parents' => array() ) );
		}

		$ids = wc_get_products(
			array(
				'type'   => 'variable',
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$parents = array();
		foreach ( $ids as $pid ) {
			$p = wc_get_product( $pid );
			if ( ! $p || ! $p->is_type( 'variable' ) ) {
				continue;
			}
			$parents[] = array(
				'parent_id' => (string) $p->get_id(),
				'title'     => $p->get_name(),
			);
		}

		usort(
			$parents,
			function ( $a, $b ) {
				return strcasecmp( $a['title'], $b['title'] );
			}
		);

		wp_send_json_success( array( 'parents' => $parents ) );
	}

	public function handle_stock_import_preview() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'oa-woo-stock' ),
				)
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'oa_woo_stock_import' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'oa-woo-stock' ),
				)
			);
		}

		if ( ! isset( $_FILES['csv_file'] ) || UPLOAD_ERR_OK !== $_FILES['csv_file']['error'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'File upload failed. Please try again.', 'oa-woo-stock' ),
				)
			);
		}

		$file_type = wp_check_filetype( isset( $_FILES['csv_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv_file']['name'] ) ) : '' );
		if ( 'csv' !== $file_type['ext'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid file type. Please upload a CSV file.', 'oa-woo-stock' ),
				)
			);
		}

		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['path'] . '/oa-woo-stock-import-' . time() . '.csv';

		if ( ! isset( $_FILES['csv_file']['tmp_name'] ) || ! move_uploaded_file( $_FILES['csv_file']['tmp_name'], $upload_path ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to save uploaded file.', 'oa-woo-stock' ),
				)
			);
		}

		$importer = OA_Woo_Stock_Importer::get_instance();
		$parsed   = $importer->parse_csv( $upload_path );

		if ( isset( $parsed['error'] ) ) {
			@unlink( $upload_path );
			wp_send_json_error( array( 'message' => $parsed['error'] ) );
		}

		$variations = $importer->get_variation_catalog();

		wp_send_json_success(
			array(
				'csv_rows'   => $parsed['data'],
				'errors'     => $parsed['errors'],
				'total_rows' => $parsed['total_rows'],
				'variations' => $variations,
			)
		);
	}

	public function handle_stock_import_process() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'oa-woo-stock' ),
				)
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'oa_woo_stock_import' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'oa-woo-stock' ),
				)
			);
		}

		$import_data = null;
		if ( isset( $_POST['import_data_json'] ) && is_string( $_POST['import_data_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['import_data_json'] ), true );
			if ( is_array( $decoded ) ) {
				$import_data = $decoded;
			}
		}
		if ( null === $import_data && isset( $_POST['import_data'] ) && is_array( $_POST['import_data'] ) ) {
			$import_data = wp_unslash( $_POST['import_data'] );
		}
		if ( ! is_array( $import_data ) || empty( $import_data ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No import data provided.', 'oa-woo-stock' ),
				)
			);
		}

		$import_data = array_map(
			function ( $row ) {
				$pid = isset( $row['product_id'] ) ? (int) $row['product_id'] : 0;
				if ( $pid < 1 ) {
					$pid = 0;
				}

				$qty_raw = isset( $row['qty_available'] ) ? $row['qty_available'] : '';
				$qty_str = ( is_int( $qty_raw ) || is_float( $qty_raw ) || is_string( $qty_raw ) )
					? sanitize_text_field( (string) $qty_raw )
					: '';

				$sku_raw = isset( $row['sku'] ) ? $row['sku'] : '';
				$sku_str = ( is_int( $sku_raw ) || is_float( $sku_raw ) || is_string( $sku_raw ) )
					? sanitize_text_field( (string) $sku_raw )
					: '';

				$code_raw = isset( $row['code'] ) ? $row['code'] : '';
				$code_str = ( is_int( $code_raw ) || is_float( $code_raw ) || is_string( $code_raw ) )
					? sanitize_text_field( (string) $code_raw )
					: '';

				return array(
					'product_id'    => $pid,
					'code'          => $code_str,
					'qty_available' => $qty_str,
					'sku'           => $sku_str,
				);
			},
			$import_data
		);

		$importer = OA_Woo_Stock_Importer::get_instance();
		$results  = $importer->process_import( $import_data );

		wp_send_json_success( $results );
	}

	public function handle_stock_export() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'oa-woo-stock' ) );
		}

		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'oa_woo_stock_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'oa-woo-stock' ) );
		}

		$exporter = OA_Woo_Stock_Exporter::get_instance();
		$exporter->export_stock_csv();
	}
}
