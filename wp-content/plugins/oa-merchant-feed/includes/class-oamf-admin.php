<?php
/**
 * Admin settings under WooCommerce.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once OAMF_PLUGIN_DIR . 'includes/class-oamf-preview.php';

/**
 * Settings page.
 */
final class OAMF_Admin {

	/**
	 * @var OAMF_Admin|null
	 */
	private static $instance = null;

	/**
	 * @return OAMF_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_init', array( $this, 'handle_preview_refresh' ) );
	}

	/**
	 * Regenerate preview cache when requested.
	 */
	public function handle_preview_refresh() {
		if ( empty( $_GET['page'] ) || 'oa-merchant-feed' !== $_GET['page'] ) {
			return;
		}
		if ( empty( $_GET['oamf_refresh_preview'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		check_admin_referer( 'oamf_refresh_preview' );
		OAMF_Preview::invalidate();
		wp_safe_redirect( admin_url( 'admin.php?page=oa-merchant-feed' ) );
		exit;
	}

	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Merchant feed', 'oa-merchant-feed' ),
			__( 'Merchant feed', 'oa-merchant-feed' ),
			'manage_woocommerce',
			'oa-merchant-feed',
			array( $this, 'render_page' )
		);
	}

	public function register() {
		register_setting(
			'oamf_settings_group',
			OAMF_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => oamf_default_settings(),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input Raw.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$defaults = oamf_default_settings();
		$prev     = oamf_get_settings();
		if ( ! is_array( $input ) ) {
			return $prev;
		}

		$out = $defaults;

		$out['feed_format'] = isset( $input['feed_format'] ) && 'tsv' === $input['feed_format'] ? 'tsv' : 'xml';

		$out['variable_mode'] = isset( $input['variable_mode'] ) && 'parent' === $input['variable_mode'] ? 'parent' : 'variation';

		if ( 'parent' === $out['variable_mode'] ) {
			$pg = isset( $prev['variation_group_attributes'] ) && is_array( $prev['variation_group_attributes'] ) ? $prev['variation_group_attributes'] : array();
			$out['variation_group_attributes']     = array_values( array_unique( array_filter( array_map( 'strval', $pg ) ) ) );
			$out['variation_merge_color_material'] = count( $out['variation_group_attributes'] ) >= 2 ? 1 : 0;
		} else {
			$valid_attr_slugs = $this->get_valid_global_attribute_slugs();
			$incoming_group   = isset( $input['variation_group_attributes'] ) && is_array( $input['variation_group_attributes'] )
				? $input['variation_group_attributes']
				: array();
			$group_clean      = array();
			foreach ( $incoming_group as $one ) {
				$one = sanitize_text_field( (string) $one );
				if ( in_array( $one, $valid_attr_slugs, true ) ) {
					$group_clean[] = $one;
				}
			}
			$group_clean = array_values( array_unique( $group_clean ) );
			if ( count( $group_clean ) > 4 ) {
				$group_clean = array_slice( $group_clean, 0, 4 );
			}
			if ( 1 === count( $group_clean ) ) {
				$group_clean = array();
				add_settings_error(
					'oamf_settings',
					'oamf_variation_group',
					__( 'Select at least two variation attributes to merge rows, or leave all unchecked for one row per variation.', 'oa-merchant-feed' ),
					'warning'
				);
			}
			$out['variation_group_attributes']     = $group_clean;
			$out['variation_merge_color_material'] = count( $group_clean ) >= 2 ? 1 : 0;
		}

		foreach (
			array(
				'include_type_simple',
				'include_type_variable',
				'include_type_grouped',
				'include_type_external',
				'include_type_other',
				'include_virtual',
				'include_downloadable',
			) as $inc_key
		) {
			$out[ $inc_key ] = ! empty( $input[ $inc_key ] ) ? 1 : 0;
		}

		$out['include_only_instock'] = 1;

		$out['title_mode'] = 'name_attrs';
		if ( isset( $input['title_mode'] ) ) {
			if ( 'name' === $input['title_mode'] ) {
				$out['title_mode'] = 'name';
			} elseif ( 'template' === $input['title_mode'] ) {
				$out['title_mode'] = 'template';
			}
		}

		$tpl = isset( $input['title_template'] ) ? (string) $input['title_template'] : '';
		$tpl = function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $tpl ) : sanitize_text_field( $tpl );
		if ( strlen( $tpl ) > 500 ) {
			$tpl = substr( $tpl, 0, 500 );
		}
		$out['title_template'] = $tpl;

		$out['title_prefix'] = isset( $input['title_prefix'] ) ? sanitize_text_field( (string) $input['title_prefix'] ) : '';
		$out['title_suffix'] = isset( $input['title_suffix'] ) ? sanitize_text_field( (string) $input['title_suffix'] ) : '';
		if ( strlen( $out['title_prefix'] ) > 200 ) {
			$out['title_prefix'] = substr( $out['title_prefix'], 0, 200 );
		}
		if ( strlen( $out['title_suffix'] ) > 200 ) {
			$out['title_suffix'] = substr( $out['title_suffix'], 0, 200 );
		}
		$sep = isset( $input['title_separator'] ) ? sanitize_text_field( (string) $input['title_separator'] ) : ' — ';
		if ( strlen( $sep ) > 40 ) {
			$sep = substr( $sep, 0, 40 );
		}
		$out['title_separator'] = $sep;

		$out['description_mode'] = isset( $input['description_mode'] ) && 'excerpt' === $input['description_mode'] ? 'excerpt' : 'content';

		$out['brand']                   = isset( $input['brand'] ) ? sanitize_text_field( (string) $input['brand'] ) : '';
		$out['google_product_category'] = isset( $input['google_product_category'] ) ? sanitize_text_field( (string) $input['google_product_category'] ) : '';

		foreach ( array( 'map_color', 'map_size', 'map_material', 'map_pattern' ) as $k ) {
			$out[ $k ] = isset( $input[ $k ] ) ? sanitize_title( (string) $input[ $k ] ) : '';
		}

		$out['gtin_meta_key'] = isset( $input['gtin_meta_key'] ) ? sanitize_key( (string) $input['gtin_meta_key'] ) : '';
		$out['mpn_meta_key']  = isset( $input['mpn_meta_key'] ) ? sanitize_key( (string) $input['mpn_meta_key'] ) : '';

		$out['product_type_source'] = isset( $input['product_type_source'] ) && 'none' === $input['product_type_source'] ? 'none' : 'category';

		$out['additional_images'] = isset( $input['additional_images'] ) ? max( 0, min( 10, (int) $input['additional_images'] ) ) : 1;

		$out['exclude_product_ids']      = $this->parse_product_id_list( isset( $input['exclude_product_ids_text'] ) ? $input['exclude_product_ids_text'] : '' );
		$out['include_only_product_ids'] = $this->parse_product_id_list( isset( $input['include_only_product_ids_text'] ) ? $input['include_only_product_ids_text'] : '' );
		$out['exclude_product_category_ids'] = $this->sanitize_product_category_ids( isset( $input['exclude_product_category_ids'] ) ? $input['exclude_product_category_ids'] : array() );
		$out['include_only_product_category_ids'] = $this->sanitize_product_category_ids( isset( $input['include_only_product_category_ids'] ) ? $input['include_only_product_category_ids'] : array() );

		$out['condition'] = isset( $input['condition'] ) && in_array( $input['condition'], array( 'new', 'refurbished', 'used' ), true )
			? $input['condition']
			: 'new';

		if ( ! empty( $input['regenerate_key'] ) ) {
			$out['secret_key'] = wp_generate_password( 32, false, false );
		} else {
			$out['secret_key'] = ! empty( $input['secret_key'] ) ? sanitize_text_field( (string) $input['secret_key'] ) : $prev['secret_key'];
		}

		if ( '' === $out['secret_key'] ) {
			$out['secret_key'] = wp_generate_password( 32, false, false );
		}

		OAMF_Preview::invalidate();

		return $out;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$s        = oamf_get_settings();
		$feed_url = OAMF_Feed_Controller::get_feed_url( $s['secret_key'] );
		$attrs    = $this->get_attribute_options();
		?>
		<div class="wrap">
			<?php settings_errors( 'oamf_settings' ); ?>
			<h1><?php echo esc_html__( 'Open Active Merchant Feed', 'oa-merchant-feed' ); ?></h1>
			<p><?php echo esc_html__( 'Use this URL in Google Merchant Center as a scheduled fetch or primary feed. No Google Ads connection is required.', 'oa-merchant-feed' ); ?></p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Feed URL', 'oa-merchant-feed' ); ?></th>
					<td>
						<code style="word-break:break-all"><?php echo esc_html( $feed_url ); ?></code>
						<p class="description"><?php esc_html_e( 'Keep the key private. Anyone with the URL can download your product list.', 'oa-merchant-feed' ); ?></p>
					</td>
				</tr>
			</table>

			<?php $this->render_feed_preview( $s ); ?>

			<?php $this->render_variable_stock_report(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'oamf_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Secret key', 'oa-merchant-feed' ); ?></th>
						<td>
							<input type="text" class="large-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[secret_key]" value="<?php echo esc_attr( $s['secret_key'] ); ?>" autocomplete="off" />
							<label style="display:block;margin-top:8px">
								<input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[regenerate_key]" value="1" />
								<?php esc_html_e( 'Regenerate key on save', 'oa-merchant-feed' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Feed format', 'oa-merchant-feed' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[feed_format]" value="xml" <?php checked( $s['feed_format'], 'xml' ); ?> /> <?php esc_html_e( 'XML (RSS 2.0 + Google namespace)', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[feed_format]" value="tsv" <?php checked( $s['feed_format'], 'tsv' ); ?> /> <?php esc_html_e( 'TSV (tab-separated)', 'oa-merchant-feed' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Variable products: row strategy', 'oa-merchant-feed' ); ?></th>
						<td>
							<p class="description" style="margin-top:0"><?php esc_html_e( 'This choice only decides how many lines a variable product produces. It is separate from the optional merge settings on the next row.', 'oa-merchant-feed' ); ?></p>
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[variable_mode]" value="variation" <?php checked( $s['variable_mode'], 'variation' ); ?> /> <?php esc_html_e( 'One row per variation (recommended for Merchant Center)', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[variable_mode]" value="parent" <?php checked( $s['variable_mode'], 'parent' ); ?> /> <?php esc_html_e( 'One row per parent product only (one offer for the whole variable product; no per-variation rows)', 'oa-merchant-feed' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Variable products: merge by attributes', 'oa-merchant-feed' ); ?></th>
						<td>
							<?php if ( 'parent' === $s['variable_mode'] ) : ?>
								<p class="description" style="margin-top:0"><?php esc_html_e( 'Not applicable while “One row per parent product only” is selected above. That mode already collapses the whole variable product to a single feed row, so there is nothing to merge by attribute.', 'oa-merchant-feed' ); ?></p>
							<?php else : ?>
								<?php
								$group_sel = isset( $s['variation_group_attributes'] ) && is_array( $s['variation_group_attributes'] ) ? $s['variation_group_attributes'] : array();
								$group_sel = array_values( array_filter( array_map( 'strval', $group_sel ) ) );
								$group_flip = array_flip( $group_sel );
								?>
								<p class="description" style="margin-top:0"><?php esc_html_e( 'Optional. Only used when “One row per variation” is selected. Leave every box unchecked to keep one feed row per WooCommerce variation with no merging.', 'oa-merchant-feed' ); ?></p>
								<fieldset style="border:0;padding:0;margin:0">
									<legend class="screen-reader-text"><?php esc_html_e( 'Which variation attributes define a merged feed row', 'oa-merchant-feed' ); ?></legend>
									<p><strong><?php esc_html_e( 'Which attributes should split the feed into separate rows?', 'oa-merchant-feed' ); ?></strong></p>
									<p class="description"><?php esc_html_e( 'Example: with four variation dimensions, check only the two that should each get their own product line in Google. Variations that differ only on unchecked attributes are combined into one row; those other values are listed in the description.', 'oa-merchant-feed' ); ?></p>
									<?php foreach ( $attrs as $slug => $lab ) : ?>
										<?php if ( '' === $slug ) { continue; } ?>
										<label style="display:block;margin:4px 0">
											<input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[variation_group_attributes][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( isset( $group_flip[ $slug ] ) ); ?> />
											<?php echo esc_html( $lab ); ?> <code><?php echo esc_html( $slug ); ?></code>
										</label>
									<?php endforeach; ?>
									<p class="description"><?php esc_html_e( 'Pick two or more to merge, or none for no merging. Maximum four.', 'oa-merchant-feed' ); ?></p>
								</fieldset>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Product types in feed', 'oa-merchant-feed' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Product types in feed', 'oa-merchant-feed' ); ?></legend>
								<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_type_simple]" value="1" <?php checked( ! empty( $s['include_type_simple'] ) ); ?> /> <?php esc_html_e( 'Simple', 'oa-merchant-feed' ); ?></label><br />
								<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_type_variable]" value="1" <?php checked( ! empty( $s['include_type_variable'] ) ); ?> /> <?php esc_html_e( 'Variable (uses the variation mode above)', 'oa-merchant-feed' ); ?></label><br />
								<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_type_grouped]" value="1" <?php checked( ! empty( $s['include_type_grouped'] ) ); ?> /> <?php esc_html_e( 'Grouped', 'oa-merchant-feed' ); ?></label><br />
								<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_type_external]" value="1" <?php checked( ! empty( $s['include_type_external'] ) ); ?> /> <?php esc_html_e( 'External / affiliate', 'oa-merchant-feed' ); ?></label><br />
								<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_type_other]" value="1" <?php checked( ! empty( $s['include_type_other'] ) ); ?> /> <?php esc_html_e( 'Other types (bundle, subscription, custom, …)', 'oa-merchant-feed' ); ?></label>
								<p class="description"><?php esc_html_e( 'Uncheck to exclude a type from the feed. Defaults match typical shops (all types on). “Other” is bundle, subscription product, or any custom type.', 'oa-merchant-feed' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Virtual & downloadable', 'oa-merchant-feed' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_virtual]" value="1" <?php checked( ! empty( $s['include_virtual'] ) ); ?> /> <?php esc_html_e( 'Include virtual products and variations', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_downloadable]" value="1" <?php checked( ! empty( $s['include_downloadable'] ) ); ?> /> <?php esc_html_e( 'Include downloadable products and variations', 'oa-merchant-feed' ); ?></label>
							<p class="description"><?php esc_html_e( 'Uncheck to omit digital-only offers from the feed (applies to simple products and to each variation).', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stock', 'oa-merchant-feed' ); ?></th>
						<td>
							<p class="description" style="margin-top:0"><?php esc_html_e( 'While WooCommerce inventory is enabled: the feed only includes SKUs that track stock and have a quantity greater than zero (status-only “In stock” without a quantity is omitted). Out-of-stock and non-purchasable lines are omitted; sellable backorders stay. Variable parent rows rely on having at least one listable variation. Merged rows use the same variation rules.', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Include / exclude products', 'oa-merchant-feed' ); ?></th>
						<td>
							<p class="description" style="margin-top:0"><?php esc_html_e( 'Leave everything empty to export all published catalog products (subject to other rules above). Use “include only” to narrow the feed, or exclusions to remove specific items or whole categories.', 'oa-merchant-feed' ); ?></p>

							<p><strong><?php esc_html_e( 'Include only these product IDs', 'oa-merchant-feed' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Optional. Comma-separated WooCommerce product IDs. When set, only matching products appear. For variable products, list the parent product ID to include all variations, or list individual variation IDs.', 'oa-merchant-feed' ); ?></p>
							<textarea class="large-text code" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[include_only_product_ids_text]" rows="3" cols="50" placeholder="<?php echo esc_attr__( 'e.g. 1024, 2050', 'oa-merchant-feed' ); ?>"><?php echo esc_textarea( $this->format_id_list_for_textarea( $s, 'include_only_product_ids' ) ); ?></textarea>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Include only products in these categories', 'oa-merchant-feed' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Optional. Ignored if “Include only these product IDs” is filled. Otherwise the product (or variable parent) must belong to at least one checked category.', 'oa-merchant-feed' ); ?></p>
							<?php $this->render_product_category_checklist( 'include_only_product_category_ids', $s ); ?>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Exclude these product IDs', 'oa-merchant-feed' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Comma-separated IDs. Excludes a simple product, a variable parent (all its variations), or a single variation if you list the variation ID.', 'oa-merchant-feed' ); ?></p>
							<textarea class="large-text code" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[exclude_product_ids_text]" rows="3" cols="50" placeholder="<?php echo esc_attr__( 'e.g. 999', 'oa-merchant-feed' ); ?>"><?php echo esc_textarea( $this->format_id_list_for_textarea( $s, 'exclude_product_ids' ) ); ?></textarea>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Exclude products in these categories', 'oa-merchant-feed' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'If a product is in any checked category (including children of that category, depending on how terms are assigned), it is omitted from the feed.', 'oa-merchant-feed' ); ?></p>
							<?php $this->render_product_category_checklist( 'exclude_product_category_ids', $s ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Product name in feed', 'oa-merchant-feed' ); ?></th>
						<td>
							<p class="description" style="margin-top:0"><?php esc_html_e( 'Controls the g:title value sent to Google Merchant Center (WooCommerce product name plus optional extras).', 'oa-merchant-feed' ); ?></p>
							<p><strong><?php esc_html_e( 'Base title', 'oa-merchant-feed' ); ?></strong></p>
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_mode]" value="name" <?php checked( $s['title_mode'], 'name' ); ?> /> <?php esc_html_e( 'WooCommerce product name only', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_mode]" value="name_attrs" <?php checked( $s['title_mode'], 'name_attrs' ); ?> /> <?php esc_html_e( 'Name plus variation attributes (each part separated with the separator below)', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_mode]" value="template" <?php checked( $s['title_mode'], 'template' ); ?> /> <?php esc_html_e( 'Custom template (placeholders—see below)', 'oa-merchant-feed' ); ?></label>
							<p class="description"><?php esc_html_e( 'For merged variable rows, “Name plus variation attributes” only uses attributes you checked under “merge by attributes”. The template always reads attribute values from the current feed row (variation or simple).', 'oa-merchant-feed' ); ?></p>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Title template', 'oa-merchant-feed' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Used when “Custom template” is selected. You do not need to rename attributes in WooCommerce—use placeholders.', 'oa-merchant-feed' ); ?></p>
							<ul class="description" style="list-style:disc;margin-left:1.25em">
								<li><code>{product_name}</code> <?php esc_html_e( 'or', 'oa-merchant-feed' ); ?> <code>{name}</code> — <?php esc_html_e( 'parent product title (variable) or product title (simple)', 'oa-merchant-feed' ); ?></li>
								<li><code>{brand}</code> — <?php esc_html_e( 'the Brand field from this settings page', 'oa-merchant-feed' ); ?></li>
								<li><code>{sku}</code> — <?php esc_html_e( 'SKU of the feed row (variation or simple)', 'oa-merchant-feed' ); ?></li>
								<li><code>{attr:pa_your-slug}</code> — <?php esc_html_e( 'value of a global attribute, e.g.', 'oa-merchant-feed' ); ?> <code>{attr:pa_timber-finish}</code></li>
								<li><code>{cat:category-slug}</code> — <?php esc_html_e( 'product category name if the product is in that category (checks parent for variations)', 'oa-merchant-feed' ); ?></li>
								<li><?php esc_html_e( 'Any other', 'oa-merchant-feed' ); ?> <code>{Timber Finish}</code> <?php esc_html_e( 'is matched to a global attribute by its admin label (same text as in Products → Attributes).', 'oa-merchant-feed' ); ?></li>
							</ul>
							<textarea id="oamf-title-template" class="large-text code" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_template]" rows="3" maxlength="500" placeholder="{brand} {name} - {attr:pa_timber-finish}"><?php echo esc_textarea( isset( $s['title_template'] ) ? (string) $s['title_template'] : '{product_name}' ); ?></textarea>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Separator between title parts', 'oa-merchant-feed' ); ?></strong></p>
							<input type="text" class="small-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_separator]" value="<?php echo esc_attr( isset( $s['title_separator'] ) ? (string) $s['title_separator'] : ' — ' ); ?>" maxlength="40" />
							<p class="description"><?php esc_html_e( 'Only used for “Name plus variation attributes”. Ignored for the custom template (put spaces and words directly in the template).', 'oa-merchant-feed' ); ?></p>

							<p style="margin-top:16px"><strong><?php esc_html_e( 'Prefix and suffix', 'oa-merchant-feed' ); ?></strong></p>
							<p><label for="oamf-title-prefix"><?php esc_html_e( 'Text before the whole title', 'oa-merchant-feed' ); ?></label><br />
							<input id="oamf-title-prefix" type="text" class="large-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_prefix]" value="<?php echo esc_attr( isset( $s['title_prefix'] ) ? (string) $s['title_prefix'] : '' ); ?>" maxlength="200" placeholder="<?php echo esc_attr__( 'e.g. Brand or empty', 'oa-merchant-feed' ); ?>" /></p>
							<p><label for="oamf-title-suffix"><?php esc_html_e( 'Text after the whole title', 'oa-merchant-feed' ); ?></label><br />
							<input id="oamf-title-suffix" type="text" class="large-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[title_suffix]" value="<?php echo esc_attr( isset( $s['title_suffix'] ) ? (string) $s['title_suffix'] : '' ); ?>" maxlength="200" placeholder="<?php echo esc_attr__( 'e.g. | Free shipping', 'oa-merchant-feed' ); ?>" /></p>
							<p class="description"><?php esc_html_e( 'Prefix and suffix are glued directly to the built title—include any spaces or punctuation you want at the edges (for example a trailing space after the prefix).', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Description', 'oa-merchant-feed' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[description_mode]" value="content" <?php checked( $s['description_mode'], 'content' ); ?> /> <?php esc_html_e( 'Full description', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[description_mode]" value="excerpt" <?php checked( $s['description_mode'], 'excerpt' ); ?> /> <?php esc_html_e( 'Short description', 'oa-merchant-feed' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="oamf-brand"><?php esc_html_e( 'Brand', 'oa-merchant-feed' ); ?></label></th>
						<td>
							<input id="oamf-brand" type="text" class="regular-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[brand]" value="<?php echo esc_attr( $s['brand'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="oamf-gpc"><?php esc_html_e( 'Google product category', 'oa-merchant-feed' ); ?></label></th>
						<td>
							<input id="oamf-gpc" type="text" class="regular-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[google_product_category]" value="<?php echo esc_attr( $s['google_product_category'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional. Use the numeric ID from Google’s taxonomy.', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Condition', 'oa-merchant-feed' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[condition]">
								<option value="new" <?php selected( $s['condition'], 'new' ); ?>><?php esc_html_e( 'new', 'oa-merchant-feed' ); ?></option>
								<option value="refurbished" <?php selected( $s['condition'], 'refurbished' ); ?>><?php esc_html_e( 'refurbished', 'oa-merchant-feed' ); ?></option>
								<option value="used" <?php selected( $s['condition'], 'used' ); ?>><?php esc_html_e( 'used', 'oa-merchant-feed' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Product type', 'oa-merchant-feed' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[product_type_source]" value="category" <?php checked( $s['product_type_source'], 'category' ); ?> /> <?php esc_html_e( 'From WooCommerce categories (breadcrumb)', 'oa-merchant-feed' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[product_type_source]" value="none" <?php checked( $s['product_type_source'], 'none' ); ?> /> <?php esc_html_e( 'Omit', 'oa-merchant-feed' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="oamf-gtin"><?php esc_html_e( 'GTIN meta key', 'oa-merchant-feed' ); ?></label></th>
						<td>
							<input id="oamf-gtin" type="text" class="regular-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[gtin_meta_key]" value="<?php echo esc_attr( $s['gtin_meta_key'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Leave empty to omit GTIN. Default _global_unique_id (WooCommerce GTIN field).', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="oamf-mpn"><?php esc_html_e( 'MPN meta key', 'oa-merchant-feed' ); ?></label></th>
						<td>
							<input id="oamf-mpn" type="text" class="regular-text" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[mpn_meta_key]" value="<?php echo esc_attr( $s['mpn_meta_key'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Optional. Custom post meta key for manufacturer part number.', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="oamf-addimg"><?php esc_html_e( 'Additional images', 'oa-merchant-feed' ); ?></label></th>
						<td>
							<input id="oamf-addimg" type="number" min="0" max="10" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[additional_images]" value="<?php echo esc_attr( (string) (int) $s['additional_images'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Extra gallery images per item (0–10), after the main image.', 'oa-merchant-feed' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Google feed fields from your attributes', 'oa-merchant-feed' ); ?></th>
						<td>
							<p class="description" style="margin-top:0"><?php esc_html_e( 'Google Merchant Center expects specific fields in the product feed (for example a color and a size). Your shop already stores options as WooCommerce attributes such as “Color” or “Blade size”.', 'oa-merchant-feed' ); ?></p>
							<p class="description"><?php esc_html_e( 'Each dropdown answers only: “When we write the Google color line in the feed, which WooCommerce attribute should we read the value from?” It is not choosing one color for everything—it tells the plugin which attribute name to look up on each product or variation.', 'oa-merchant-feed' ); ?></p>
							<p class="description"><?php esc_html_e( 'Leave a row on “None” if you do not want that Google field in the feed. If you merge variation rows above, Google structured fields (g:color, g:size, …) are only output when that attribute is both mapped here and checked in “define a feed row”; other values stay in the description text.', 'oa-merchant-feed' ); ?></p>
							<?php echo $this->attr_select( 'map_color', __( 'Google color (g:color) — take value from WooCommerce attribute', 'oa-merchant-feed' ), $s['map_color'], $attrs ); ?>
							<?php echo $this->attr_select( 'map_size', __( 'Google size (g:size) — take value from WooCommerce attribute', 'oa-merchant-feed' ), $s['map_size'], $attrs ); ?>
							<?php echo $this->attr_select( 'map_material', __( 'Google material (g:material) — take value from WooCommerce attribute', 'oa-merchant-feed' ), $s['map_material'], $attrs ); ?>
							<?php echo $this->attr_select( 'map_pattern', __( 'Google pattern (g:pattern) — take value from WooCommerce attribute', 'oa-merchant-feed' ), $s['map_pattern'], $attrs ); ?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Admin table: each variable product with feed listable count and WooCommerce stock status counts.
	 */
	private function render_variable_stock_report(): void {
		if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'oamf_product_excluded_from_feed_stock' ) ) {
			return;
		}

		$limit = (int) apply_filters( 'oamf_variable_stock_report_limit', 300 );
		$limit = max( 1, min( 500, $limit ) );

		$products = wc_get_products(
			array(
				'type'    => 'variable',
				'status'  => 'publish',
				'limit'   => $limit,
				'orderby' => 'title',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);

		$rows = array();
		foreach ( $products as $parent ) {
			if ( ! $parent instanceof WC_Product_Variable ) {
				continue;
			}
			$listable   = 0;
			$in_status  = 0;
			$out_status = 0;
			$var_total  = 0;
			foreach ( $parent->get_children() as $child_id ) {
				$v = wc_get_product( $child_id );
				if ( ! $v || ! $v->is_type( 'variation' ) ) {
					continue;
				}
				++$var_total;
				if ( ! oamf_product_excluded_from_feed_stock( $v ) ) {
					++$listable;
				}
				$st = $v->get_stock_status();
				if ( 'instock' === $st ) {
					++$in_status;
				} elseif ( 'outofstock' === $st ) {
					++$out_status;
				}
			}
			if ( $var_total < 1 ) {
				continue;
			}
			$rows[] = array(
				'parent'     => $parent,
				'listable'   => $listable,
				'in_status'  => $in_status,
				'out_status' => $out_status,
			);
		}

		$sum_listable   = 0;
		$sum_in_status  = 0;
		$sum_out_status = 0;
		foreach ( $rows as $r ) {
			$sum_listable   += (int) $r['listable'];
			$sum_in_status  += (int) $r['in_status'];
			$sum_out_status += (int) $r['out_status'];
		}
		$product_count = count( $rows );

		?>
		<hr style="margin:24px 0" />
		<h2><?php esc_html_e( 'Variable products: variation stock (feed rules)', 'oa-merchant-feed' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Per published variable product: the first column uses the feed’s stock rules (managed quantity, status, backorders). The next two columns count WooCommerce variation stock status only — “In stock” is the instock value; “Out of stock” is outofstock. Variations on backorder are not counted in those two columns. Virtual/downloadable, hidden catalog, and include/exclude lists are not reflected in the listable column.', 'oa-merchant-feed' ); ?>
		</p>
		<?php if ( count( $products ) >= $limit ) : ?>
			<p class="description">
				<strong><?php esc_html_e( 'Note:', 'oa-merchant-feed' ); ?></strong>
				<?php
				printf(
					/* translators: %d: maximum variable products listed in the report table */
					esc_html__( 'At most %d variable products are listed (by title). There may be more in the catalog. Developers can raise the cap with the oamf_variable_stock_report_limit filter (max 500).', 'oa-merchant-feed' ),
					(int) $limit
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $product_count > 0 ) : ?>
			<p class="description" style="margin-top:12px">
				<strong><?php esc_html_e( 'Totals (listed products only):', 'oa-merchant-feed' ); ?></strong>
				<?php
				printf(
					/* translators: 1: variable products in table, 2: feed-listable variations, 3: WC instock variations, 4: WC outofstock variations */
					esc_html__( '%1$d variable products — %2$d variations listable (stock), %3$d with In stock status, %4$d with Out of stock status.', 'oa-merchant-feed' ),
					(int) $product_count,
					(int) $sum_listable,
					(int) $sum_in_status,
					(int) $sum_out_status
				);
				?>
			</p>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:960px;margin-top:12px">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Product', 'oa-merchant-feed' ); ?></th>
					<th scope="col" style="width:140px"><?php esc_html_e( 'Variations listable (stock)', 'oa-merchant-feed' ); ?></th>
					<th scope="col" style="width:140px"><?php esc_html_e( 'Variations (In stock Status)', 'oa-merchant-feed' ); ?></th>
					<th scope="col" style="width:160px"><?php esc_html_e( 'Variations (Out of stock Status)', 'oa-merchant-feed' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No variable products found.', 'oa-merchant-feed' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $r ) : ?>
						<?php
						/** @var WC_Product_Variable $p */
						$p = $r['parent'];
						$edit = get_edit_post_link( $p->get_id() );
						?>
						<tr>
							<td>
								<?php if ( $edit ) : ?>
									<a href="<?php echo esc_url( $edit ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $p->get_name() ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo (int) $r['listable']; ?></td>
							<td><?php echo (int) $r['in_status']; ?></td>
							<td><?php echo (int) $r['out_status']; ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<?php if ( $product_count > 0 ) : ?>
				<tfoot>
					<tr>
						<th scope="row"><?php esc_html_e( 'Totals', 'oa-merchant-feed' ); ?></th>
						<td><strong><?php echo (int) $sum_listable; ?></strong></td>
						<td><strong><?php echo (int) $sum_in_status; ?></strong></td>
						<td><strong><?php echo (int) $sum_out_status; ?></strong></td>
					</tr>
				</tfoot>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * @param array<string, mixed> $s Settings.
	 */
	private function render_feed_preview( array $s ): void {
		$payload = OAMF_Preview::get_payload( $s );
		$total   = isset( $payload['total'] ) ? (int) $payload['total'] : 0;
		$built   = isset( $payload['built'] ) ? (int) $payload['built'] : 0;
		$error   = isset( $payload['error'] ) ? (string) $payload['error'] : '';
		$sample  = isset( $payload['sample'] ) && is_array( $payload['sample'] ) ? $payload['sample'] : null;
		$xml_s   = isset( $payload['xml_sample'] ) && is_string( $payload['xml_sample'] ) ? $payload['xml_sample'] : '';
		$tsv_s   = isset( $payload['tsv_sample'] ) && is_string( $payload['tsv_sample'] ) ? $payload['tsv_sample'] : '';

		$refresh_url = wp_nonce_url(
			add_query_arg( 'oamf_refresh_preview', '1', admin_url( 'admin.php?page=oa-merchant-feed' ) ),
			'oamf_refresh_preview'
		);
		?>
		<div class="card" style="max-width:920px;margin:20px 0">
			<h2 style="margin-top:0"><?php esc_html_e( 'Feed preview', 'oa-merchant-feed' ); ?></h2>
			<p class="description" style="margin-top:0">
				<?php esc_html_e( 'Based on saved settings. Large catalogs are cached for 15 minutes.', 'oa-merchant-feed' ); ?>
				<a href="<?php echo esc_url( $refresh_url ); ?>"><?php esc_html_e( 'Refresh preview', 'oa-merchant-feed' ); ?></a>
			</p>
			<?php if ( '' !== $error ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>
			<p><strong><?php esc_html_e( 'Total feed items', 'oa-merchant-feed' ); ?>:</strong> <?php echo esc_html( (string) $total ); ?></p>
			<?php if ( $built > 0 ) : ?>
				<p class="description"><?php echo esc_html( sprintf( __( 'Preview generated: %s', 'oa-merchant-feed' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $built ) ) ); ?></p>
			<?php endif; ?>

			<?php if ( null === $sample ) : ?>
				<p><?php esc_html_e( 'No items to preview yet (no published products matched your rules, or prices are missing).', 'oa-merchant-feed' ); ?></p>
			<?php else : ?>
				<h3><?php esc_html_e( 'First item (summary)', 'oa-merchant-feed' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Same order as the live feed (published products, lowest product ID first).', 'oa-merchant-feed' ); ?></p>
				<table class="widefat striped" style="max-width:920px">
					<tbody>
					<?php
					$keys = array(
						'id',
						'title',
						'link',
						'image_link',
						'price',
						'sale_price',
						'availability',
						'condition',
						'brand',
						'item_group_id',
						'color',
						'size',
						'material',
						'pattern',
						'gtin',
						'mpn',
						'google_product_category',
						'product_type',
					);
					foreach ( $keys as $key ) {
						$val = isset( $sample[ $key ] ) ? $sample[ $key ] : '';
						if ( is_array( $val ) ) {
							$val = implode( ', ', $val );
						}
						$val = (string) $val;
						$empty = '' === $val;
						$cell  = '';
						if ( $empty ) {
							$cell = '<span style="color:#787c82">—</span>';
						} elseif ( 'link' === $key || 'image_link' === $key ) {
							$cell = '<a href="' . esc_url( $val ) . '">' . esc_html( $val ) . '</a>';
						} elseif ( strlen( $val ) > 220 ) {
							$cell = esc_html( substr( $val, 0, 220 ) ) . '…';
						} else {
							$cell = esc_html( $val );
						}
						?>
						<tr>
							<th scope="row" style="width:180px"><?php echo esc_html( $key ); ?></th>
							<td><?php echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
						<?php
					}
					$desc = isset( $sample['description'] ) ? (string) $sample['description'] : '';
					if ( '' !== $desc ) {
						$dshow = strlen( $desc ) > 600 ? esc_html( substr( $desc, 0, 600 ) ) . '…' : esc_html( $desc );
						?>
						<tr>
							<th scope="row"><?php esc_html_e( 'description', 'oa-merchant-feed' ); ?></th>
							<td><div style="max-height:220px;overflow:auto"><?php echo $dshow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>

				<h3><?php esc_html_e( 'Example in your selected format', 'oa-merchant-feed' ); ?> (<?php echo esc_html( (string) $s['feed_format'] ); ?>)</h3>
				<p class="description"><?php esc_html_e( 'Long text is shortened in this example only; the live feed uses full values.', 'oa-merchant-feed' ); ?></p>
				<?php if ( 'tsv' === $s['feed_format'] ) : ?>
					<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;background:#f6f7f7;padding:12px;border:1px solid #c3c4c7;border-radius:4px;font-size:12px"><?php echo esc_html( $tsv_s ); ?></pre>
				<?php else : ?>
					<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;background:#f6f7f7;padding:12px;border:1px solid #c3c4c7;border-radius:4px;font-size:12px"><?php echo esc_html( $xml_s ); ?></pre>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Global attribute taxonomy names (pa_*) for sanitizing merge selection.
	 *
	 * @return list<string>
	 */
	private function get_valid_global_attribute_slugs(): array {
		$opts = $this->get_attribute_options();
		unset( $opts[''] );
		return array_keys( $opts );
	}

	/**
	 * @return array<string, string> slug => label
	 */
	private function get_attribute_options() {
		$out   = array( '' => __( '— None —', 'oa-merchant-feed' ) );
		$taxes = wc_get_attribute_taxonomies();
		if ( ! is_array( $taxes ) ) {
			return $out;
		}
		foreach ( $taxes as $tax ) {
			$name = (string) $tax->attribute_name;
			$lab  = (string) $tax->attribute_label;
			$slug = wc_attribute_taxonomy_name( $name );
			$out[ $slug ] = $lab ? $lab : $name;
		}
		return $out;
	}

	/**
	 * @param array<string, string> $options
	 */
	private function attr_select( string $field, string $label, string $value, array $options ): string {
		ob_start();
		?>
		<p>
			<label>
				<?php echo esc_html( $label ); ?><br />
				<select name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[<?php echo esc_attr( $field ); ?>]">
					<?php foreach ( $options as $slug => $opt_label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $value, $slug ); ?>><?php echo esc_html( $opt_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $s   Settings.
	 * @param string               $key Stored array of product IDs.
	 */
	private function format_id_list_for_textarea( array $s, string $key ): string {
		if ( empty( $s[ $key ] ) || ! is_array( $s[ $key ] ) ) {
			return '';
		}
		$ids = array_filter( array_map( 'intval', $s[ $key ] ) );
		return implode( ', ', $ids );
	}

	/**
	 * @param mixed $raw Textarea or list.
	 * @return list<int>
	 */
	private function parse_product_id_list( $raw ): array {
		if ( is_array( $raw ) ) {
			$raw = implode( ',', $raw );
		}
		$raw = (string) $raw;
		$raw = preg_replace( '/[\s\r\n;]+/', ',', $raw );
		$out = array();
		foreach ( explode( ',', $raw ) as $p ) {
			$p = (int) trim( $p );
			if ( $p > 0 ) {
				$out[] = $p;
			}
		}
		return array_slice( array_values( array_unique( $out ) ), 0, 500 );
	}

	/**
	 * @param array<int|string, mixed> $incoming Raw checkbox values.
	 * @return list<int>
	 */
	private function sanitize_product_category_ids( array $incoming ): array {
		$out = array();
		foreach ( $incoming as $tid ) {
			$tid = (int) $tid;
			if ( $tid <= 0 ) {
				continue;
			}
			$term = get_term( $tid, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$out[] = $tid;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string               $field Option sub-key for checkbox array.
	 * @param array<string, mixed> $s     Settings.
	 */
	private function render_product_category_checklist( string $field, array $s ): void {
		$selected = isset( $s[ $field ] ) && is_array( $s[ $field ] ) ? array_map( 'intval', $s[ $field ] ) : array();
		$flip     = array_flip( $selected );
		$terms    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'number'     => 500,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<p class="description">' . esc_html__( 'No product categories found.', 'oa-merchant-feed' ) . '</p>';
			return;
		}
		echo '<div style="max-height:14rem;overflow:auto;border:1px solid #c3c4c7;padding:8px;background:#fff">';
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$depth = count( get_ancestors( (int) $term->term_id, 'product_cat' ) );
			$pad   = str_repeat( '— ', max( 0, $depth ) );
			?>
			<label style="display:block;margin:3px 0">
				<input type="checkbox" name="<?php echo esc_attr( OAMF_OPTION_KEY ); ?>[<?php echo esc_attr( $field ); ?>][]" value="<?php echo (int) $term->term_id; ?>" <?php checked( isset( $flip[ (int) $term->term_id ] ) ); ?> />
				<?php echo esc_html( $pad . $term->name ); ?> <code><?php echo esc_html( (string) $term->slug ); ?></code>
			</label>
			<?php
		}
		echo '</div>';
	}
}
