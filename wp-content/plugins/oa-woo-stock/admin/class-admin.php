<?php
/**
 * Admin: WooCommerce submenu, Import / Export tabs, assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OA_Woo_Stock_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Stock Import / Export', 'oa-woo-stock' ),
			__( 'Stock Import / Export', 'oa-woo-stock' ),
			'manage_woocommerce',
			'oa-woo-stock',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( 'woocommerce_page_oa-woo-stock' !== $hook ) {
			return;
		}

		$css_path = OA_WOO_STOCK_PLUGIN_DIR . 'admin/css/admin.css';
		$js_path  = OA_WOO_STOCK_PLUGIN_DIR . 'admin/js/admin.js';
		$css_ver  = is_readable( $css_path ) ? (string) filemtime( $css_path ) : OA_WOO_STOCK_VERSION;
		$js_ver   = is_readable( $js_path ) ? (string) filemtime( $js_path ) : OA_WOO_STOCK_VERSION;

		wp_enqueue_style(
			'oa-woo-stock-admin',
			OA_WOO_STOCK_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			$css_ver
		);

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_script(
			'oa-woo-stock-admin',
			OA_WOO_STOCK_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			$js_ver,
			true
		);

		wp_localize_script(
			'oa-woo-stock-admin',
			'oaWooStockAdmin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'importNonce' => wp_create_nonce( 'oa_woo_stock_import' ),
				'exportNonce' => wp_create_nonce( 'oa_woo_stock_export' ),
				'strings'     => array(
					'uploading'          => __( 'Uploading…', 'oa-woo-stock' ),
					'uploadPreview'      => __( 'Upload & load matching screen', 'oa-woo-stock' ),
					'importing'          => __( 'Applying stock…', 'oa-woo-stock' ),
					'importBtn'          => __( 'Apply stock to matched variations', 'oa-woo-stock' ),
					'importSuccess'      => __( 'Stock update finished.', 'oa-woo-stock' ),
					'importError'        => __( 'Import failed.', 'oa-woo-stock' ),
					'statVariations'     => __( 'WooCommerce variations', 'oa-woo-stock' ),
					'statCsvRows'        => __( 'CSV rows', 'oa-woo-stock' ),
					'statStoreAboveFile' => __( 'Store stock above file', 'oa-woo-stock' ),
					/* translators: %d: number of stock review findings (unlinked with stock, store vs file mismatch). */
					'importItemsCount'   => __( '%d Items', 'oa-woo-stock' ),
					'warningsHeading'    => __( 'Warnings', 'oa-woo-stock' ),
					'resultsUpdated'     => __( 'Updated', 'oa-woo-stock' ),
					'resultsFailed'      => __( 'Failed', 'oa-woo-stock' ),
					'resultsSkipped'     => __( 'Skipped', 'oa-woo-stock' ),
					'resultsErrors'      => __( 'Errors', 'oa-woo-stock' ),
					'labelImportCol'     => __( 'Import (from file)', 'oa-woo-stock' ),
					'labelWcCol'         => __( 'WooCommerce variation', 'oa-woo-stock' ),
					'thReconcileStore'   => __( 'Product name', 'oa-woo-stock' ),
					'thReconcileSku'     => __( 'SKU', 'oa-woo-stock' ),
					'thReconcileStock'   => __( 'Stock', 'oa-woo-stock' ),
					'thReconcileActions' => __( 'Actions', 'oa-woo-stock' ),
					'sectionSkuMatched'  => __( 'Matched by SKU', 'oa-woo-stock' ),
					'sectionOtherPairs'  => __( 'Other linked pairs', 'oa-woo-stock' ),
					'sectionVarOnly'     => __( 'Variations — not in file', 'oa-woo-stock' ),
					'cardImportMeta'     => __( 'Import line', 'oa-woo-stock' ),
					'cardWcMeta'         => __( 'Product name', 'oa-woo-stock' ),
					'cardStockColMeta'   => __( 'Current stock and quantity to apply', 'oa-woo-stock' ),
					'cardActionsColMeta' => __( 'View product and row update', 'oa-woo-stock' ),
					'cardSkuColMeta'     => __( 'SKU', 'oa-woo-stock' ),
					'viewProductLink'    => __( 'View product', 'oa-woo-stock' ),
					'editVariationLink'  => __( 'Edit in admin', 'oa-woo-stock' ),
					'sheetQtysHeading'   => __( 'Quantities in file', 'oa-woo-stock' ),
					'sheetQtyOnHand'     => __( 'On hand', 'oa-woo-stock' ),
					'sheetQtyOnHandEmpty' => _x( 'Empty', 'Qty on Hand cell is blank in the import file', 'oa-woo-stock' ),
					'sheetQtyReserved'   => __( 'Reserved', 'oa-woo-stock' ),
					'sheetQtyAvailableCol' => __( 'Available', 'oa-woo-stock' ),
					'sheetQtysNone'      => __( 'No quantity columns were found for this row. Check that the CSV includes headers such as Qty on Hand or Qty Available.', 'oa-woo-stock' ),
					'codeLabel'          => __( 'Code', 'oa-woo-stock' ),
					'descLabel'          => __( 'Description', 'oa-woo-stock' ),
					'variationNameLabel' => __( 'Variation', 'oa-woo-stock' ),
					'skuLabel'           => __( 'SKU', 'oa-woo-stock' ),
					'skuPlaceholderCode' => __( 'e.g. match import Code', 'oa-woo-stock' ),
					'updateRowBtn'       => __( 'Update', 'oa-woo-stock' ),
					'rowUpdating'        => __( 'Updating…', 'oa-woo-stock' ),
					'rowUpdateOk'        => __( 'Saved.', 'oa-woo-stock' ),
					'rowUpdateNeedQtyOrSku' => __( 'Enter a stock quantity and/or a SKU in the product name row to save.', 'oa-woo-stock' ),
					'rowUpdateFail'      => __( 'Update failed.', 'oa-woo-stock' ),
					'rowUpdateSkipped'   => __( 'Nothing was saved (row skipped). Check quantity and/or SKU, or reload the page if the variation list is out of date.', 'oa-woo-stock' ),
					'stockLabel'         => __( 'Current stock', 'oa-woo-stock' ),
					'wcStockToSetLabel'  => __( 'Stock to set in WooCommerce', 'oa-woo-stock' ),
					'wcStockToSetHint'   => __( 'Saved to WooCommerce when you click Update or bulk apply. Current stock (circle) is highlighted green or orange against the file.', 'oa-woo-stock' ),
					'stockVsSheetMatch'  => __( 'Matches quantity from file', 'oa-woo-stock' ),
					'stockVsSheetDiff'   => __( 'Differs from quantity in file', 'oa-woo-stock' ),
					'stockVsSheetStoreHigher' => __( 'Store stock is higher than the import file (applying will reduce stock).', 'oa-woo-stock' ),
					'stockVsSheetFileHigher'  => __( 'Store stock is lower than the import file (applying will increase stock).', 'oa-woo-stock' ),
					'matchSelectVar'     => __( 'Match to variation…', 'oa-woo-stock' ),
					'matchSelectCsv'     => __( 'Match to import row…', 'oa-woo-stock' ),
					'chooseCsvAlert'     => __( 'Please choose a CSV file.', 'oa-woo-stock' ),
					'uploadFailAlert'    => __( 'Upload failed.', 'oa-woo-stock' ),
					'importColumnHintVarOnly' => __( 'Link this variation to a CSV line using the dropdown next to the name.', 'oa-woo-stock' ),
					'noImportRows'       => __( 'No rows to apply. Link each file line to a variation (dropdown in the Product name column), set stock in the Stock column, and check bulk scope (all vs selected products) if you limited it.', 'oa-woo-stock' ),
					'sessionRestored'    => __( 'Your last import matching session was restored in this browser (links and edits you had made). Upload a new CSV to replace it.', 'oa-woo-stock' ),
					'renderListFailed'   => __( 'Could not draw the matching list. Try Start over or re-upload the CSV.', 'oa-woo-stock' ),
					'stockWarnUnlinked'  => __( '[variation_name] (variation #[variation_id]): the store shows [wc_stock] in stock, but this variation is not linked to any import row.', 'oa-woo-stock' ),
					'stockWarnWooHigherThanFile' => __( '[variation_name] (variation #[variation_id]): the store shows [wc_stock] in stock; the import file quantity used for comparison is [file_qty] (lower or zero).', 'oa-woo-stock' ),
					'stockWarnFileHigherThanWoo' => __( '[variation_name] (variation #[variation_id]): the import file shows [file_qty] but the store shows [wc_stock] (store is lower than the file).', 'oa-woo-stock' ),
				),
			)
		);
	}

	public function render_page() {
		?>
		<div class="wrap oa-woo-stock-wrap">
			<form id="oa-woo-stock-controls-form" class="oa-woo-stock-detached-form screen-reader-text" aria-hidden="true" onsubmit="return false;"></form>
			<h1><?php esc_html_e( 'Stock Import / Export', 'oa-woo-stock' ); ?></h1>

			<div class="oa-woo-stock-tabs">
				<nav class="nav-tab-wrapper">
					<a href="#oa-woo-stock-import" class="nav-tab nav-tab-active"><?php esc_html_e( 'Import', 'oa-woo-stock' ); ?></a>
					<a href="#oa-woo-stock-export" class="nav-tab"><?php esc_html_e( 'Export', 'oa-woo-stock' ); ?></a>
				</nav>

				<div id="oa-woo-stock-import" class="oa-woo-stock-tab-panel is-active">
					<div class="oa-woo-stock-section">
						<p class="oa-woo-stock-import-items-line" id="oa-woo-stock-import-items-line">
							<span id="oa-woo-stock-import-items-text"><?php echo esc_html( sprintf( /* translators: %d: stock review count */ __( '%d Items', 'oa-woo-stock' ), 0 ) ); ?></span>
						</p>
						<h2><?php esc_html_e( 'Import stock from CSV', 'oa-woo-stock' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Choose bulk apply scope (all or selected variable products) before uploading if you need it. Each row is a table: SKU, Product name (name and link dropdown), Stock, and Actions. Rows that share the same SKU with the file appear first; link any remaining rows manually.', 'oa-woo-stock' ); ?>
						</p>

						<div id="oa-woo-stock-product-scope" class="oa-woo-stock-product-scope oa-woo-stock-product-scope--preupload">
							<h3 class="oa-woo-stock-product-scope__heading"><?php esc_html_e( 'Bulk apply: which products?', 'oa-woo-stock' ); ?></h3>
							<p class="description oa-woo-stock-product-scope__intro"><?php esc_html_e( 'Limits the primary “Apply stock” button. Per-row Update is not affected.', 'oa-woo-stock' ); ?></p>
							<div class="oa-woo-stock-product-scope__radios">
								<label class="oa-woo-stock-product-scope__radio-label">
									<input type="radio" name="oa-product-apply-scope" value="all" form="oa-woo-stock-controls-form" checked>
									<?php esc_html_e( 'All variable products', 'oa-woo-stock' ); ?>
								</label>
								<label class="oa-woo-stock-product-scope__radio-label">
									<input type="radio" name="oa-product-apply-scope" value="selected" form="oa-woo-stock-controls-form">
									<?php esc_html_e( 'Only selected variable products', 'oa-woo-stock' ); ?>
								</label>
							</div>
							<div id="oa-woo-stock-parent-scope-box" class="oa-woo-stock-parent-scope-box" hidden>
								<p class="oa-woo-stock-parent-scope-actions">
									<button type="button" class="button button-small" id="oa-woo-stock-parent-scope-all"><?php esc_html_e( 'Select all', 'oa-woo-stock' ); ?></button>
									<button type="button" class="button button-small" id="oa-woo-stock-parent-scope-none"><?php esc_html_e( 'Select none', 'oa-woo-stock' ); ?></button>
								</p>
								<div id="oa-woo-stock-parent-checkboxes" class="oa-woo-stock-parent-checkboxes" role="group" aria-label="<?php echo esc_attr__( 'Variable products to include in bulk apply', 'oa-woo-stock' ); ?>"></div>
							</div>
						</div>

						<form id="oa-woo-stock-upload-form" enctype="multipart/form-data">
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="oa_woo_stock_csv"><?php esc_html_e( 'CSV file', 'oa-woo-stock' ); ?></label>
									</th>
									<td>
										<input type="file" id="oa_woo_stock_csv" name="csv_file" accept=".csv" required>
									</td>
								</tr>
							</table>
							<p class="submit">
								<button type="submit" class="button button-primary button-large" id="oa-woo-stock-upload-btn">
									<?php esc_html_e( 'Upload & load matching screen', 'oa-woo-stock' ); ?>
								</button>
							</p>
						</form>

						<div id="oa-woo-stock-match-ui" class="oa-woo-stock-match-ui" hidden>
							<div id="oa-woo-stock-stats" class="oa-woo-stock-stats" aria-live="polite"></div>
							<div id="oa-woo-stock-warnings" class="oa-woo-stock-warnings" hidden aria-live="polite"></div>

							<div class="oa-woo-stock-match-toolbar">
								<button type="button" class="button" id="oa-woo-stock-clear-links-btn">
									<?php esc_html_e( 'Clear all links', 'oa-woo-stock' ); ?>
								</button>
								<button type="button" class="button" id="oa-woo-stock-reset-ui-btn">
									<?php esc_html_e( 'Start over', 'oa-woo-stock' ); ?>
								</button>
								<div class="oa-woo-stock-sort-toolbar">
									<label for="oa-woo-stock-list-sort"><?php esc_html_e( 'Sort', 'oa-woo-stock' ); ?></label>
									<select id="oa-woo-stock-list-sort" class="oa-woo-stock-list-sort" form="oa-woo-stock-controls-form" aria-label="<?php esc_attr_e( 'Sort reconcile rows', 'oa-woo-stock' ); ?>">
										<option value="sheet"><?php esc_html_e( 'File order (CSV)', 'oa-woo-stock' ); ?></option>
										<option value="name"><?php esc_html_e( 'Product name (A–Z)', 'oa-woo-stock' ); ?></option>
									</select>
								</div>
							</div>

							<div id="oa-woo-stock-reconcile-list" class="oa-reconcile-list"></div>

							<p class="submit">
								<button type="button" class="button button-primary button-large" id="oa-woo-stock-apply-btn" disabled>
									<?php esc_html_e( 'Apply stock to matched variations', 'oa-woo-stock' ); ?>
								</button>
							</p>
						</div>

						<div id="oa-woo-stock-import-results" class="oa-woo-stock-results" hidden></div>
					</div>
				</div>

				<div id="oa-woo-stock-export" class="oa-woo-stock-tab-panel">
					<div class="oa-woo-stock-section">
						<h2><?php esc_html_e( 'Export stock & price CSV', 'oa-woo-stock' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Download a CSV in the same shape as your accounting import (Code, Description, quantities, retail price).', 'oa-woo-stock' ); ?>
						</p>
						<p>
							<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'oa_woo_stock_export', 'nonce' => wp_create_nonce( 'oa_woo_stock_export' ) ), admin_url( 'admin-ajax.php' ) ) ); ?>" class="button button-primary button-large">
								<?php esc_html_e( 'Download stock & price CSV', 'oa-woo-stock' ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
