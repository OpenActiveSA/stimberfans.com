<?php
/**
 * Export stock (and retail price) CSV — same shape as accounting import.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OA_Woo_Stock_Exporter {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function export_stock_csv() {
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => -1,
				'orderby' => 'menu_order title',
				'order'   => 'ASC',
			)
		);

		$csv_content = "sep=,\n";
		$csv_content .= '"Code","Description","Category","Active","Qty on Hand","Qty Reserved","Qty Available","Retail price list"' . "\n";

		foreach ( $products as $product ) {
			$sku           = $product->get_sku();
			$name          = $product->get_name();
			$categories    = $this->get_product_categories( $product );
			$active        = 'publish' === $product->get_status() ? 'Yes' : 'No';
			$stock_qty     = $product->get_stock_quantity();
			$qty_reserved  = 0;
			$qty_available = null !== $stock_qty ? $stock_qty : 0;

			$regular_price = $product->get_regular_price();
			$retail_price  = '' !== $regular_price ? $regular_price : '';

			$row = array(
				$this->escape_csv_value( $sku ? $sku : '' ),
				$this->escape_csv_value( $name ),
				$this->escape_csv_value( $categories ),
				$this->escape_csv_value( $active ),
				$qty_available,
				$qty_reserved,
				$qty_available,
				'' !== $retail_price ? $retail_price : '',
			);

			$csv_content .= implode( ',', $row ) . "\n";

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
				foreach ( $variations as $variation_data ) {
					$variation = wc_get_product( $variation_data['variation_id'] );
					if ( ! $variation ) {
						continue;
					}

					$var_sku           = $variation->get_sku();
					$var_name          = $variation->get_name();
					$var_stock_qty     = $variation->get_stock_quantity();
					$var_qty_available = null !== $var_stock_qty ? $var_stock_qty : 0;

					$var_regular_price = $variation->get_regular_price();
					$var_retail_price  = '' !== $var_regular_price ? $var_regular_price : '';

					$var_row = array(
						$this->escape_csv_value( $var_sku ? $var_sku : '' ),
						$this->escape_csv_value( $var_name ),
						$this->escape_csv_value( $categories ),
						$this->escape_csv_value( $active ),
						$var_qty_available,
						$qty_reserved,
						$var_qty_available,
						'' !== $var_retail_price ? $var_retail_price : '',
					);

					$csv_content .= implode( ',', $var_row ) . "\n";
				}
			}
		}

		$site_name       = get_bloginfo( 'name' );
		$clean_site_name = sanitize_file_name( $site_name );
		$filename        = $clean_site_name . '-stock-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );

		echo "\xEF\xBB\xBF";
		echo $csv_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private function escape_csv_value( $value ) {
		$value = str_replace( '"', '""', $value );
		return '"' . $value . '"';
	}

	private function get_product_categories( $product ) {
		$category_ids = $product->get_category_ids();
		$categories   = array();

		foreach ( $category_ids as $cat_id ) {
			$category = get_term( $cat_id, 'product_cat' );
			if ( $category && ! is_wp_error( $category ) ) {
				$categories[] = $category->name;
			}
		}

		return implode( ', ', $categories );
	}
}
