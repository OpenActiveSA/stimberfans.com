<?php
/**
 * Stock import: CSV parsing and applying quantities to variations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OA_Woo_Stock_Importer {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function detect_csv_delimiter( $handle ) {
		$position = ftell( $handle );
		$delimiters       = array( ',', ';' );
		$delimiter_counts   = array( ',' => 0, ';' => 0 );
		$lines_to_check     = 5;
		$line_count         = 0;

		while ( $line_count < $lines_to_check && ( $line = fgets( $handle ) ) !== false ) {
			if ( strpos( strtolower( trim( $line ) ), 'sep=' ) === 0 ) {
				if ( preg_match( '/sep=([^;,\s]+)/i', trim( $line ), $matches ) ) {
					$sep_char = $matches[1];
					if ( $sep_char === ';' || $sep_char === ',' ) {
						fseek( $handle, $position );
						return $sep_char;
					}
				}
				continue;
			}
			foreach ( $delimiters as $delimiter ) {
				$delimiter_counts[ $delimiter ] += substr_count( $line, $delimiter );
			}
			$line_count++;
		}

		fseek( $handle, $position );
		$detected = array_search( max( $delimiter_counts ), $delimiter_counts, true );
		return $detected ? $detected : ',';
	}

	/**
	 * Fill in quantity column indexes when headers differ slightly from the Xero defaults.
	 *
	 * @param array<int, string> $header      CSV header cells.
	 * @param array<string, int> $column_map  Map of logical name => column index.
	 * @return array<string, int>
	 */
	private function augment_quantity_column_map( array $header, array $column_map ) {
		$looks_qtyish = function ( $col ) {
			$l = strtolower( $col );
			return ( strpos( $l, 'qty' ) !== false || strpos( $l, 'quantity' ) !== false || strpos( $l, 'stock' ) !== false || strpos( $l, 'inventory' ) !== false );
		};

		if ( ! isset( $column_map['Qty Available'] ) ) {
			foreach ( $header as $index => $col ) {
				$l = strtolower( $col );
				if ( ! $looks_qtyish( $col ) && strpos( $l, 'avail' ) === false ) {
					continue;
				}
				if ( strpos( $l, 'avail' ) !== false || strpos( $l, 'free' ) !== false ) {
					$column_map['Qty Available'] = $index;
					break;
				}
			}
		}

		if ( ! isset( $column_map['Qty on Hand'] ) ) {
			foreach ( $header as $index => $col ) {
				$l = strtolower( $col );
				if ( ! $looks_qtyish( $col ) ) {
					continue;
				}
				if ( strpos( $l, 'reserved' ) !== false || strpos( $l, 'avail' ) !== false ) {
					continue;
				}
				if ( strpos( $l, 'on hand' ) !== false || strpos( $l, 'onhand' ) !== false || strpos( $l, 'in stock' ) !== false || strpos( $l, 'instock' ) !== false ) {
					$column_map['Qty on Hand'] = $index;
					break;
				}
			}
		}

		if ( ! isset( $column_map['Qty Reserved'] ) ) {
			foreach ( $header as $index => $col ) {
				$l = strtolower( $col );
				if ( strpos( $l, 'reserved' ) !== false && $looks_qtyish( $col ) ) {
					$column_map['Qty Reserved'] = $index;
					break;
				}
			}
		}

		return $column_map;
	}

	/**
	 * Parse CSV rows for matching UI (does not require SKU to exist in WooCommerce).
	 *
	 * @return array{data: array, errors: array, total_rows: int}|array{error: string}
	 */
	public function parse_csv( $file_path ) {
		$data   = array();
		$errors = array();

		if ( ! file_exists( $file_path ) ) {
			return array( 'error' => __( 'File not found.', 'oa-woo-stock' ) );
		}

		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return array( 'error' => __( 'Could not open file.', 'oa-woo-stock' ) );
		}

		$bom = fread( $handle, 3 );
		if ( $bom !== "\xEF\xBB\xBF" ) {
			rewind( $handle );
		}

		$delimiter = $this->detect_csv_delimiter( $handle );
		rewind( $handle );
		if ( $bom === "\xEF\xBB\xBF" ) {
			fread( $handle, 3 );
		}

		$first_line = fgetcsv( $handle, 0, $delimiter );
		if ( $first_line && isset( $first_line[0] ) && strpos( strtolower( trim( $first_line[0] ) ), 'sep=' ) === 0 ) {
			$sep_line = trim( $first_line[0] );
			if ( preg_match( '/sep=([^;,\s]+)/i', $sep_line, $matches ) ) {
				$sep_char = $matches[1];
				if ( $sep_char === ';' || $sep_char === ',' ) {
					$delimiter = $sep_char;
				}
			}
			$header = fgetcsv( $handle, 0, $delimiter );
		} else {
			rewind( $handle );
			if ( $bom === "\xEF\xBB\xBF" ) {
				fread( $handle, 3 );
			}
			$header = fgetcsv( $handle, 0, $delimiter );
		}

		if ( ! $header ) {
			fclose( $handle );
			return array( 'error' => __( 'Could not read CSV header.', 'oa-woo-stock' ) );
		}

		$header = array_map(
			function ( $h ) {
				return trim( str_replace( array( '"', "'" ), '', $h ) );
			},
			$header
		);

		$header_values = array_map( 'strtolower', $header );

		$column_map       = array();
		$expected_columns = array( 'Code', 'Description', 'Category', 'Active', 'Qty on Hand', 'Qty Reserved', 'Qty Available' );

		foreach ( $expected_columns as $expected ) {
			$found = false;
			foreach ( $header as $index => $col ) {
				if ( stripos( $col, $expected ) !== false || stripos( $expected, $col ) !== false ) {
					$column_map[ $expected ] = $index;
					$found                   = true;
					break;
				}
			}
			if ( ! $found && 'Code' === $expected ) {
				foreach ( $header as $index => $col ) {
					if ( stripos( $col, 'sku' ) !== false || stripos( $col, 'code' ) !== false ) {
						$column_map['Code'] = $index;
						break;
					}
				}
			}
		}

		if ( ! isset( $column_map['Code'] ) ) {
			fclose( $handle );
			return array( 'error' => __( 'Could not find "Code" or "SKU" column in CSV.', 'oa-woo-stock' ) );
		}

		$column_map = $this->augment_quantity_column_map( $header, $column_map );
		$has_qty_available_column = isset( $column_map['Qty Available'] );
		$has_qty_on_hand_column = isset( $column_map['Qty on Hand'] );

		$row_num = 1;
		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			$row_num++;

			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			$row_data = array(
				'csv_index'                  => count( $data ),
				'row_num'                    => $row_num,
				'code'                       => isset( $row[ $column_map['Code'] ] ) ? trim( $row[ $column_map['Code'] ] ) : '',
				'description'                => isset( $column_map['Description'] ) && isset( $row[ $column_map['Description'] ] ) ? trim( $row[ $column_map['Description'] ] ) : '',
				'category'                   => isset( $column_map['Category'] ) && isset( $row[ $column_map['Category'] ] ) ? trim( $row[ $column_map['Category'] ] ) : '',
				'active'                     => isset( $column_map['Active'] ) && isset( $row[ $column_map['Active'] ] ) ? trim( $row[ $column_map['Active'] ] ) : '',
				'qty_on_hand'                => isset( $column_map['Qty on Hand'] ) && isset( $row[ $column_map['Qty on Hand'] ] ) ? trim( $row[ $column_map['Qty on Hand'] ] ) : '',
				'qty_reserved'               => isset( $column_map['Qty Reserved'] ) && isset( $row[ $column_map['Qty Reserved'] ] ) ? trim( $row[ $column_map['Qty Reserved'] ] ) : '',
				'qty_available'              => isset( $column_map['Qty Available'] ) && isset( $row[ $column_map['Qty Available'] ] ) ? trim( $row[ $column_map['Qty Available'] ] ) : '',
				'has_qty_available_column'   => $has_qty_available_column,
				'has_qty_on_hand_column'     => $has_qty_on_hand_column,
				'qty_available_in_sheet'     => '',
				'qty_apply_source'           => 'manual',
			);

			if ( in_array( strtolower( $row_data['code'] ), $header_values, true ) ) {
				continue;
			}

			if ( '' === $row_data['code'] ) {
				continue;
			}

			$qty_available = $row_data['qty_available'];
			if ( '' !== $qty_available && ! is_numeric( $qty_available ) ) {
				$errors[] = sprintf(
					/* translators: 1: row number, 2: quantity value, 3: SKU */
					__( 'Row %1$d: Invalid quantity "%2$s" for SKU "%3$s".', 'oa-woo-stock' ),
					$row_num,
					$qty_available,
					$row_data['code']
				);
				$row_data['qty_available']          = '';
				$row_data['qty_available_in_sheet'] = '';
			} else {
				$row_data['qty_available']          = '' !== $qty_available ? intval( $qty_available ) : '';
				$row_data['qty_available_in_sheet'] = $row_data['qty_available'];
			}

			// When the file has no "Qty Available" column, default the apply quantity from on-hand (Xero-style exports).
			if ( '' === $row_data['qty_available'] && ! $has_qty_available_column ) {
				$on_hand = $row_data['qty_on_hand'];
				if ( '' !== $on_hand && is_numeric( $on_hand ) ) {
					$row_data['qty_available'] = intval( $on_hand );
				}
			}

			if ( $row_data['qty_available'] !== '' ) {
				if ( $row_data['qty_available_in_sheet'] !== '' ) {
					$row_data['qty_apply_source'] = 'sheet_available';
				} elseif ( ! $has_qty_available_column ) {
					$row_data['qty_apply_source'] = 'on_hand_fallback';
				} else {
					$row_data['qty_apply_source'] = 'manual';
				}
			}

			$suggested_id                = (int) wc_get_product_id_by_sku( $row_data['code'] );
			$row_data['suggested_variation_id'] = $suggested_id;
			$product                     = $suggested_id ? wc_get_product( $suggested_id ) : null;
			$row_data['sku_match_is_variation'] = ( $product && $product->is_type( 'variation' ) );

			$data[] = $row_data;
		}

		fclose( $handle );

		return array(
			'data'       => $data,
			'errors'     => $errors,
			'total_rows' => count( $data ),
		);
	}

	/**
	 * Build display list of all published variations (for left column).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_variation_catalog() {
		$variation_ids = wc_get_products(
			array(
				'type'   => 'variation',
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		$rows = array();
		foreach ( $variation_ids as $vid ) {
			$product = wc_get_product( $vid );
			if ( ! $product || ! $product->is_type( 'variation' ) ) {
				continue;
			}

			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent && 'publish' !== $parent->get_status() ) {
				continue;
			}

			$parent_id = $product->get_parent_id();
			$parent_title = $parent ? $parent->get_name() : '';
			$admin_edit_raw = get_edit_post_link( $product->get_id(), 'raw' );
			$admin_edit_url = $admin_edit_raw ? esc_url_raw( $admin_edit_raw ) : '';
			// "View product" opens the parent variable product in wp-admin (not the storefront).
			$view_product_url = '';
			if ( $parent ) {
				$parent_edit_raw = get_edit_post_link( $parent->get_id(), 'raw' );
				if ( $parent_edit_raw ) {
					$view_product_url = esc_url_raw( $parent_edit_raw );
				}
			}

			$rows[] = array(
				'variation_id'      => $product->get_id(),
				'parent_id'         => $parent_id,
				'parent_title'      => $parent_title,
				'sku'               => $product->get_sku(),
				'name'              => $this->get_variation_display_name( $product ),
				'current_stock'     => $product->get_stock_quantity(),
				'view_product_url'  => $view_product_url,
				'admin_edit_url'    => $admin_edit_url,
			);
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $rows;
	}

	/**
	 * @param WC_Product_Variation $product Variation product.
	 */
	private function get_variation_display_name( $product ) {
		$parent      = wc_get_product( $product->get_parent_id() );
		$parent_name = $parent ? $parent->get_name() : $product->get_name();

		$attributes        = $product->get_variation_attributes();
		$attribute_values  = array();
		$diameter_value    = '';

		foreach ( $attributes as $key => $value ) {
			$taxonomy = str_replace( 'attribute_', '', $key );
			$taxonomy = str_replace( 'pa_', '', $taxonomy );

			if ( stripos( $taxonomy, 'diameter' ) !== false || stripos( $taxonomy, 'size' ) !== false ) {
				$diameter_value = $value;
			} else {
				$attribute_values[] = $value;
			}
		}

		$ordered_values = array();
		if ( '' !== $diameter_value ) {
			$ordered_values[] = $diameter_value;
		}
		$ordered_values = array_merge( $ordered_values, $attribute_values );

		if ( ! empty( $ordered_values ) ) {
			return $parent_name . ' — ' . implode( ', ', $ordered_values );
		}

		return $parent_name;
	}

	/**
	 * @param array<int, array<string, mixed>> $import_data Rows with product_id, optional qty_available, optional sku.
	 * @return array{success: int, failed: int, skipped: int, errors: string[]}
	 */
	public function process_import( $import_data ) {
		$results = array(
			'success' => 0,
			'failed'  => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		if ( ! is_array( $import_data ) || empty( $import_data ) ) {
			$results['errors'][] = __( 'No data provided for import.', 'oa-woo-stock' );
			return $results;
		}

		$single_row_request = ( 1 === count( $import_data ) );

		foreach ( $import_data as $row ) {
			if ( empty( $row['product_id'] ) || (int) $row['product_id'] < 1 ) {
				$results['skipped']++;
				if ( $single_row_request ) {
					$results['errors'][] = __( 'Missing or invalid variation ID. Reload this page and try again.', 'oa-woo-stock' );
				}
				continue;
			}

			$product_id = intval( $row['product_id'] );
			$product    = wc_get_product( $product_id );

			if ( ! $product || ! $product->is_type( 'variation' ) ) {
				$results['failed']++;
				$results['errors'][] = sprintf(
					/* translators: %d: product ID */
					__( 'Variation ID %d not found or not a variation.', 'oa-woo-stock' ),
					$product_id
				);
				continue;
			}

			$qty_raw   = isset( $row['qty_available'] ) ? $row['qty_available'] : '';
			$qty_str   = is_scalar( $qty_raw ) ? trim( (string) $qty_raw ) : '';
			$has_qty   = '' !== $qty_str && is_numeric( $qty_str );

			$new_sku = isset( $row['sku'] ) ? wc_clean( wp_unslash( $row['sku'] ) ) : '';
			$new_sku = is_string( $new_sku ) ? trim( $new_sku ) : '';

			if ( ! $has_qty && '' === $new_sku ) {
				$results['skipped']++;
				if ( $single_row_request ) {
					$results['errors'][] = __( 'Nothing to save: enter a stock quantity and/or a SKU.', 'oa-woo-stock' );
				}
				continue;
			}

			if ( $has_qty ) {
				$qty_available = (int) $qty_str;
				$product->set_stock_quantity( $qty_available );
				$product->set_manage_stock( true );

				if ( $qty_available > 0 ) {
					$product->set_stock_status( 'instock' );
				} else {
					$product->set_stock_status( 'outofstock' );
				}
			}

			if ( '' !== $new_sku ) {
				$existing_id = wc_get_product_id_by_sku( $new_sku );
				if ( $existing_id && (int) $existing_id !== $product_id ) {
					$results['failed']++;
					$results['errors'][] = sprintf(
						/* translators: 1: SKU string, 2: existing product ID */
						__( 'SKU "%1$s" is already used by another product (ID %2$d).', 'oa-woo-stock' ),
						$new_sku,
						(int) $existing_id
					);
					continue;
				}
				$product->set_sku( $new_sku );
			}

			try {
				$product->save();
				wc_delete_product_transients( $product_id );
				$parent_id = $product->get_parent_id();
				if ( $parent_id ) {
					wc_delete_product_transients( $parent_id );
					clean_post_cache( $parent_id );
				}
				clean_post_cache( $product_id );
				$results['success']++;
			} catch ( \Throwable $e ) {
				$results['failed']++;
				$code = isset( $row['code'] ) && is_scalar( $row['code'] ) ? (string) $row['code'] : '';
				$results['errors'][] = sprintf(
					/* translators: 1: product ID, 2: CSV code, 3: error message */
					__( 'Failed to update variation ID %1$d (CSV code: %2$s): %3$s', 'oa-woo-stock' ),
					$product_id,
					$code,
					$e->getMessage()
				);
			}
		}

		return $results;
	}
}
