<?php
/**
 * Builds feed rows from WooCommerce products.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects product rows according to settings.
 */
final class OAMF_Items {

	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function get_rows(): array {
		$rows = array();

		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => -1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'objects',
			)
		);

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			if ( $product->get_catalog_visibility() === 'hidden' ) {
				continue;
			}

			$type = $product->get_type();
			if ( ! $this->is_wc_product_type_included( $type ) ) {
				continue;
			}

			if ( ! $this->passes_product_pick_exclude( $product, $product ) ) {
				continue;
			}

			if ( 'variable' === $type && 'variation' === $this->settings['variable_mode'] ) {
				if ( $this->should_merge_variation_groups() && $product instanceof WC_Product_Variable ) {
					foreach ( $this->rows_for_merged_variable( $product ) as $row ) {
						$rows[] = $row;
					}
				} else {
					foreach ( $product->get_children() as $child_id ) {
						$v = wc_get_product( $child_id );
						if ( $v && $v->is_type( 'variation' ) && ! $this->should_exclude_for_stock( $v ) && $this->is_offering_included( $v ) && $this->passes_product_pick_exclude( $v, $product ) ) {
							$row = $this->build_variation_row( $product, $v );
							if ( $row ) {
								$rows[] = $row;
							}
						}
					}
				}
				continue;
			}

			if ( 'variable' === $type && 'parent' === $this->settings['variable_mode'] ) {
				$row = $this->build_parent_variable_row( $product );
				if ( $row ) {
					$rows[] = $row;
				}
				continue;
			}

			$row = $this->build_simple_row( $product );
			if ( $row ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * WooCommerce product types (simple, variable, …) allowed in the feed.
	 */
	private function is_wc_product_type_included( string $type ): bool {
		switch ( $type ) {
			case 'simple':
				return ! empty( $this->settings['include_type_simple'] );
			case 'variable':
				return ! empty( $this->settings['include_type_variable'] );
			case 'grouped':
				return ! empty( $this->settings['include_type_grouped'] );
			case 'external':
				return ! empty( $this->settings['include_type_external'] );
			default:
				return ! empty( $this->settings['include_type_other'] );
		}
	}

	/**
	 * Virtual / downloadable filters for simple products, parent products, and variations.
	 */
	private function is_offering_included( WC_Product $product ): bool {
		if ( empty( $this->settings['include_virtual'] ) && $product->get_virtual() ) {
			return false;
		}
		if ( empty( $this->settings['include_downloadable'] ) && $product->is_downloadable() ) {
			return false;
		}
		return true;
	}

	/**
	 * Whether this SKU should be omitted from the feed due to inventory (always applied).
	 */
	private function should_exclude_for_stock( WC_Product $product ): bool {
		return oamf_product_excluded_from_feed_stock( $product );
	}

	/**
	 * @param WC_Product $product Row product (variation, simple, variable parent, …).
	 */
	private function should_skip_out_of_stock( WC_Product $product ): bool {
		return $this->should_exclude_for_stock( $product );
	}

	/**
	 * Include-only lists / category allowlist, then ID and category exclusions.
	 *
	 * @param WC_Product $target  Row product (variation, simple, variable parent, …).
	 * @param WC_Product $context Parent for variations; same as target for simple/parent rows.
	 */
	private function passes_product_pick_exclude( WC_Product $target, WC_Product $context ): bool {
		if ( ! $this->passes_include_only_rules( $target ) ) {
			return false;
		}
		if ( $this->is_excluded_by_product_id( $target ) ) {
			return false;
		}
		$post_id_for_cats = $target->is_type( 'variation' ) ? $target->get_parent_id() : $target->get_id();
		if ( $this->has_excluded_product_category( $post_id_for_cats ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @return array<int, true>
	 */
	private function get_product_id_set( string $setting_key ): array {
		$raw = isset( $this->settings[ $setting_key ] ) && is_array( $this->settings[ $setting_key ] )
			? $this->settings[ $setting_key ]
			: array();
		$out = array();
		foreach ( $raw as $id ) {
			$id = (int) $id;
			if ( $id > 0 ) {
				$out[ $id ] = true;
			}
		}
		return $out;
	}

	private function passes_include_only_rules( WC_Product $target ): bool {
		$include_ids = $this->get_product_id_set( 'include_only_product_ids' );
		if ( ! empty( $include_ids ) ) {
			$tid = $target->get_id();
			$pid = $target->is_type( 'variation' ) ? $target->get_parent_id() : 0;
			if ( isset( $include_ids[ $tid ] ) ) {
				return true;
			}
			if ( $pid && isset( $include_ids[ $pid ] ) ) {
				return true;
			}
			return false;
		}

		$cats = isset( $this->settings['include_only_product_category_ids'] ) && is_array( $this->settings['include_only_product_category_ids'] )
			? array_filter( array_map( 'intval', $this->settings['include_only_product_category_ids'] ) )
			: array();
		if ( empty( $cats ) ) {
			return true;
		}

		$post_id = $target->is_type( 'variation' ) ? $target->get_parent_id() : $target->get_id();
		$terms    = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}
		return count( array_intersect( $terms, $cats ) ) > 0;
	}

	private function is_excluded_by_product_id( WC_Product $target ): bool {
		$exclude = $this->get_product_id_set( 'exclude_product_ids' );
		if ( empty( $exclude ) ) {
			return false;
		}
		$tid = $target->get_id();
		$pid = $target->is_type( 'variation' ) ? $target->get_parent_id() : 0;
		if ( isset( $exclude[ $tid ] ) ) {
			return true;
		}
		if ( $pid && isset( $exclude[ $pid ] ) ) {
			return true;
		}
		return false;
	}

	private function has_excluded_product_category( int $post_id ): bool {
		$terms = isset( $this->settings['exclude_product_category_ids'] ) && is_array( $this->settings['exclude_product_category_ids'] )
			? array_filter( array_map( 'intval', $this->settings['exclude_product_category_ids'] ) )
			: array();
		if ( empty( $terms ) ) {
			return false;
		}
		foreach ( $terms as $term_id ) {
			if ( has_term( $term_id, 'product_cat', $post_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Taxonomies used together to merge variable variations into one feed row.
	 *
	 * @return list<string>
	 */
	private function get_variation_group_taxonomies(): array {
		$raw = isset( $this->settings['variation_group_attributes'] ) && is_array( $this->settings['variation_group_attributes'] )
			? $this->settings['variation_group_attributes']
			: array();
		$list = array_values( array_filter( array_map( 'strval', $raw ) ) );
		if ( ! empty( $list ) ) {
			sort( $list );
			return $list;
		}
		if ( ! empty( $this->settings['variation_merge_color_material'] ) ) {
			$c = isset( $this->settings['map_color'] ) ? (string) $this->settings['map_color'] : '';
			$m = isset( $this->settings['map_material'] ) ? (string) $this->settings['map_material'] : '';
			if ( '' !== $c && '' !== $m ) {
				return array( $c, $m );
			}
		}
		return array();
	}

	/**
	 * Merge variable rows when two or more group attributes are configured (or legacy color+material).
	 */
	private function should_merge_variation_groups(): bool {
		return count( $this->get_variation_group_taxonomies() ) >= 2;
	}

	/**
	 * @param WC_Product_Variable $parent Parent variable product.
	 * @return list<array<string, mixed>>
	 */
	private function rows_for_merged_variable( WC_Product_Variable $parent ): array {
		$groups = array();
		foreach ( $parent->get_children() as $child_id ) {
			$v = wc_get_product( $child_id );
			if ( ! $v || ! $v->is_type( 'variation' ) ) {
				continue;
			}
			if ( $this->should_exclude_for_stock( $v ) ) {
				continue;
			}
			if ( ! $this->is_offering_included( $v ) || ! $this->passes_product_pick_exclude( $v, $parent ) ) {
				continue;
			}
			$key = $this->variation_merge_group_key( $v );
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array();
			}
			$groups[ $key ][] = $v;
		}

		$out = array();
		foreach ( $groups as $group_key => $variations ) {
			if ( empty( $variations ) ) {
				continue;
			}
			$row = $this->compose_merged_variations_row( $parent, $variations, (string) $group_key );
			if ( $row ) {
				$out[] = $row;
			}
		}
		return $out;
	}

	private function variation_merge_group_key( WC_Product_Variation $v ): string {
		$attrs = $v->get_attributes();
		$parts = array();
		foreach ( $this->get_variation_group_taxonomies() as $tax ) {
			$tax = (string) $tax;
			if ( '' === $tax ) {
				continue;
			}
			$slug = isset( $attrs[ $tax ] ) ? (string) $attrs[ $tax ] : '';
			$parts[] = $tax . ':' . $slug;
		}
		return implode( '|', $parts );
	}

	/**
	 * @param WC_Product_Variable            $parent     Parent.
	 * @param list<WC_Product_Variation>     $variations Variations sharing color+material.
	 * @param string                         $group_key  Stable merge key.
	 * @return array<string, mixed>|null
	 */
	private function compose_merged_variations_row( WC_Product_Variable $parent, array $variations, string $group_key ): ?array {
		usort(
			$variations,
			static function ( $a, $b ) {
				return $a->get_id() <=> $b->get_id();
			}
		);

		$rep = null;
		foreach ( $variations as $v ) {
			if ( $v->is_in_stock() ) {
				$rep = $v;
				break;
			}
		}
		if ( ! $rep instanceof WC_Product_Variation ) {
			$rep = $variations[0];
		}

		$currency = get_woocommerce_currency();

		$min_regular = PHP_FLOAT_MAX;
		foreach ( $variations as $v ) {
			$r = (float) $v->get_regular_price();
			if ( $r > 0 && $r < $min_regular ) {
				$min_regular = $r;
			}
		}
		if ( $min_regular === PHP_FLOAT_MAX || $min_regular <= 0 ) {
			$min_regular = (float) $rep->get_regular_price();
			if ( $min_regular <= 0 ) {
				$min_regular = (float) $rep->get_price();
			}
		}
		if ( $min_regular <= 0 ) {
			return null;
		}

		$min_sale = null;
		foreach ( $variations as $v ) {
			$s = $v->get_sale_price();
			if ( '' === $s || null === $s ) {
				continue;
			}
			$sf = (float) $s;
			$rf = (float) $v->get_regular_price();
			if ( $sf > 0 && $rf > 0 && $sf < $rf && ( null === $min_sale || $sf < $min_sale ) ) {
				$min_sale = $sf;
			}
		}

		$link = $parent->get_permalink();
		if ( ! $link ) {
			return null;
		}

		$title = $this->build_title_merged( $parent, $rep );
		$desc  = $this->build_description( $parent );
		$extra = $this->summarize_non_group_variation_attributes( $variations, $this->get_variation_group_taxonomies() );
		if ( '' !== $extra ) {
			$desc = trim( $desc . "\n\n" . $extra );
		}

		$image_id = $rep->get_image_id();
		if ( ! $image_id ) {
			$image_id = $parent->get_image_id();
		}
		$image_link = $image_id ? wp_get_attachment_url( (int) $image_id ) : '';
		if ( ! $image_link ) {
			$image_link = wc_placeholder_img_src( 'full' );
		}

		$row = array(
			'id'                  => $this->merged_feed_id( $parent, $group_key ),
			'title'               => $title,
			'description'         => $desc,
			'link'                => $link,
			'image_link'          => $image_link,
			'availability'        => $this->availability_merged( $variations ),
			'price'               => oamf_format_price_amount( $min_regular, $currency ),
			'condition'           => (string) $this->settings['condition'],
			'additional_images'   => $this->additional_images( $rep, $parent ),
			'item_group_id'       => (string) $parent->get_id(),
		);

		if ( null !== $min_sale && $min_sale > 0 && $min_sale < $min_regular ) {
			$row['sale_price'] = oamf_format_price_amount( $min_sale, $currency );
		}

		$brand = trim( (string) $this->settings['brand'] );
		if ( '' !== $brand ) {
			$row['brand'] = $brand;
		}

		$gpc = trim( (string) $this->settings['google_product_category'] );
		if ( '' !== $gpc ) {
			$row['google_product_category'] = $gpc;
		}

		if ( 'category' === $this->settings['product_type_source'] ) {
			$pt = $this->product_type_string( $parent );
			if ( '' !== $pt ) {
				$row['product_type'] = $pt;
			}
		}

		$this->append_identifiers( $row, $rep );
		$this->apply_attribute_maps_merged( $row, $rep, $this->get_variation_group_taxonomies() );

		if ( isset( $row['availability'] ) && 'out_of_stock' === $row['availability'] ) {
			return null;
		}

		return $row;
	}

	/**
	 * @param WC_Product_Variable $parent Parent.
	 * @param string              $group_key Merge key.
	 */
	private function merged_feed_id( WC_Product_Variable $parent, string $group_key ): string {
		return (string) $parent->get_id() . '-m-' . substr( md5( $group_key ), 0, 16 );
	}

	/**
	 * @param list<WC_Product_Variation> $variations Variations.
	 */
	private function availability_merged( array $variations ): string {
		$any_in_stock = false;
		$any_back     = false;
		foreach ( $variations as $v ) {
			$st = $v->get_stock_status();
			if ( 'instock' === $st ) {
				$any_in_stock = true;
				break;
			}
			if ( 'onbackorder' === $st ) {
				$any_back = true;
			}
		}
		if ( $any_in_stock ) {
			return 'in_stock';
		}
		if ( $any_back ) {
			return 'backorder';
		}
		return 'out_of_stock';
	}

	/**
	 * @param WC_Product_Variable      $parent Parent.
	 * @param WC_Product_Variation   $rep    Representative variation.
	 */
	private function build_title_merged( WC_Product_Variable $parent, WC_Product_Variation $rep ): string {
		if ( 'template' === ( $this->settings['title_mode'] ?? '' ) ) {
			return $this->finalize_feed_title( $this->apply_title_template( $rep, $parent ) );
		}
		$name = $parent->get_name();
		if ( 'name' === $this->settings['title_mode'] ) {
			return $this->finalize_feed_title( $name );
		}
		$parts = array( $name );
		$attrs = $rep->get_attributes();
		foreach ( $this->get_variation_group_taxonomies() as $tax ) {
			$tax = (string) $tax;
			if ( '' === $tax ) {
				continue;
			}
			$slug = isset( $attrs[ $tax ] ) ? (string) $attrs[ $tax ] : '';
			if ( '' === $slug ) {
				continue;
			}
			$label   = wc_attribute_label( $tax, $parent );
			$text    = $this->variation_attribute_display( $tax, $slug );
			$parts[] = $label . ': ' . $text;
		}
		$core = implode( $this->get_title_separator(), array_filter( array_map( 'trim', $parts ) ) );
		return $this->finalize_feed_title( $core );
	}

	/**
	 * Between product name and each attribute segment in the title.
	 */
	private function get_title_separator(): string {
		$s = isset( $this->settings['title_separator'] ) ? (string) $this->settings['title_separator'] : '';
		$s = trim( $s );
		return '' !== $s ? $s : ' — ';
	}

	/**
	 * Optional prefix/suffix around the computed title (g:title).
	 */
	private function finalize_feed_title( string $core ): string {
		$core = trim( $core );
		$pre  = isset( $this->settings['title_prefix'] ) ? (string) $this->settings['title_prefix'] : '';
		$suf  = isset( $this->settings['title_suffix'] ) ? (string) $this->settings['title_suffix'] : '';
		return trim( $pre . $core . $suf );
	}

	/**
	 * Lowercase attribute label → taxonomy (pa_*).
	 *
	 * @return array<string, string>
	 */
	private function get_attribute_taxonomy_label_map(): array {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}
		$map = array();
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $map;
		}
		foreach ( wc_get_attribute_taxonomies() as $row ) {
			$label = strtolower( trim( (string) $row->attribute_label ) );
			if ( '' === $label ) {
				continue;
			}
			$map[ $label ] = wc_attribute_taxonomy_name( $row->attribute_name );
		}
		return $map;
	}

	private function find_attribute_taxonomy_by_label( string $label ): string {
		$key = strtolower( trim( $label ) );
		if ( '' === $key ) {
			return '';
		}
		$map = $this->get_attribute_taxonomy_label_map();
		return isset( $map[ $key ] ) ? (string) $map[ $key ] : '';
	}

	private function normalize_attr_taxonomy_from_token( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( taxonomy_exists( $raw ) ) {
			return $raw;
		}
		if ( 0 === stripos( $raw, 'pa_' ) ) {
			$candidate = 'pa_' . sanitize_title( substr( $raw, 3 ) );
			return taxonomy_exists( $candidate ) ? $candidate : '';
		}
		$candidate = wc_attribute_taxonomy_name( sanitize_title( $raw ) );
		return taxonomy_exists( $candidate ) ? $candidate : '';
	}

	/**
	 * Readable attribute value for title template (variation slug → term name).
	 */
	private function taxonomy_attribute_value_for_feed_title( WC_Product $target, string $taxonomy ): string {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}
		if ( $target->is_type( 'variation' ) ) {
			$attrs = $target->get_attributes();
			$slug  = isset( $attrs[ $taxonomy ] ) ? (string) $attrs[ $taxonomy ] : '';
			if ( '' === $slug ) {
				return '';
			}
			return $this->variation_attribute_display( $taxonomy, $slug );
		}
		$raw = $target->get_attribute( $taxonomy );
		return trim( wp_strip_all_tags( (string) $raw ) );
	}

	/**
	 * Replace {placeholders} in title_template for the current feed row.
	 */
	private function apply_title_template( WC_Product $target, WC_Product $context ): string {
		$tpl = isset( $this->settings['title_template'] ) ? (string) $this->settings['title_template'] : '';
		$tpl = trim( $tpl );
		if ( '' === $tpl ) {
			$tpl = '{product_name}';
		}

		$product_name = trim( (string) $context->get_name() );
		$brand        = trim( isset( $this->settings['brand'] ) ? (string) $this->settings['brand'] : '' );
		$sku          = trim( (string) $target->get_sku() );
		$context_id   = (int) $context->get_id();

		$out = (string) preg_replace_callback(
			'/\{([^}]+)\}/',
			function ( array $m ) use ( $target, $context_id, $product_name, $brand, $sku ): string {
				$inner = trim( (string) $m[1] );
				if ( '' === $inner ) {
					return '';
				}
				$il = strtolower( $inner );
				if ( 'product_name' === $il || 'name' === $il ) {
					return $product_name;
				}
				if ( 'brand' === $il ) {
					return $brand;
				}
				if ( 'sku' === $il ) {
					return $sku;
				}
				if ( preg_match( '/^attr:\s*(.+)$/i', $inner, $am ) ) {
					$tax = $this->normalize_attr_taxonomy_from_token( trim( (string) $am[1] ) );
					if ( '' === $tax ) {
						return '';
					}
					return $this->taxonomy_attribute_value_for_feed_title( $target, $tax );
				}
				if ( preg_match( '/^cat:\s*(.+)$/i', $inner, $cm ) ) {
					$slug = sanitize_title( trim( (string) $cm[1] ) );
					if ( '' === $slug || $context_id <= 0 ) {
						return '';
					}
					if ( ! has_term( $slug, 'product_cat', $context_id ) ) {
						return '';
					}
					$term = get_term_by( 'slug', $slug, 'product_cat' );
					if ( ! $term || is_wp_error( $term ) ) {
						return '';
					}
					return trim( (string) $term->name );
				}
				$tax = $this->find_attribute_taxonomy_by_label( $inner );
				if ( '' !== $tax ) {
					return $this->taxonomy_attribute_value_for_feed_title( $target, $tax );
				}
				return '';
			},
			$tpl
		);

		$out = trim( preg_replace( '/\s+/u', ' ', $out ) );
		return $out;
	}

	/**
	 * @param list<WC_Product_Variation> $variations Variations.
	 * @param list<string>               $exclude_taxonomies Taxonomy names to skip.
	 */
	private function summarize_non_group_variation_attributes( array $variations, array $exclude_taxonomies ): string {
		$exclude = array_flip( array_filter( array_map( 'strval', $exclude_taxonomies ) ) );
		$by_tax  = array();

		foreach ( $variations as $v ) {
			foreach ( $v->get_attributes() as $tax => $slug ) {
				if ( ! is_string( $tax ) || ! is_string( $slug ) || '' === $slug ) {
					continue;
				}
				if ( isset( $exclude[ $tax ] ) ) {
					continue;
				}
				$display = $this->variation_attribute_display( $tax, $slug );
				if ( '' === $display ) {
					continue;
				}
				if ( ! isset( $by_tax[ $tax ] ) ) {
					$by_tax[ $tax ] = array();
				}
				$by_tax[ $tax ][ $display ] = true;
			}
		}

		ksort( $by_tax );
		$lines = array();
		foreach ( $by_tax as $tax => $names ) {
			ksort( $names );
			$label = wc_attribute_label( $tax, $variations[0] );
			$list  = implode( ', ', array_keys( $names ) );
			if ( '' !== $list ) {
				$lines[] = $label . ': ' . $list;
			}
		}

		return implode( "\n", $lines );
	}

	private function variation_attribute_display( string $taxonomy, string $slug ): string {
		if ( taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return trim( (string) $term->name );
			}
		}
		return trim( wp_strip_all_tags( $slug ) );
	}

	/**
	 * For merged variable rows: output g:color / g:size / … only when that map’s taxonomy is a “group” dimension.
	 *
	 * @param array<string, mixed> $row Row.
	 * @param list<string>         $group_taxonomies Taxonomies that define the row.
	 */
	private function apply_attribute_maps_merged( array &$row, WC_Product $target, array $group_taxonomies ): void {
		$flip = array_flip( $group_taxonomies );
		$maps = array(
			'map_color'    => 'color',
			'map_size'     => 'size',
			'map_material' => 'material',
			'map_pattern'  => 'pattern',
		);
		foreach ( $maps as $setting_key => $field ) {
			$tax = isset( $this->settings[ $setting_key ] ) ? (string) $this->settings[ $setting_key ] : '';
			if ( '' === $tax || ! isset( $flip[ $tax ] ) ) {
				continue;
			}
			$val = $target->get_attribute( $tax );
			if ( ! is_string( $val ) || '' === trim( $val ) ) {
				continue;
			}
			$val = trim( wp_strip_all_tags( $val ) );
			if ( '' !== $val ) {
				$row[ $field ] = $val;
			}
		}
	}

	/**
	 * @param array<string, mixed> $row Row.
	 */
	private function append_identifiers( array &$row, WC_Product $target ): void {
		$gtin_key = trim( (string) $this->settings['gtin_meta_key'] );
		if ( '' !== $gtin_key ) {
			$gtin = get_post_meta( $target->get_id(), $gtin_key, true );
			if ( is_string( $gtin ) || is_numeric( $gtin ) ) {
				$gtin = trim( (string) $gtin );
				if ( '' !== $gtin ) {
					$row['gtin'] = $gtin;
				}
			}
		}

		$mpn_key = trim( (string) $this->settings['mpn_meta_key'] );
		if ( '' !== $mpn_key ) {
			$mpn = get_post_meta( $target->get_id(), $mpn_key, true );
			if ( is_string( $mpn ) || is_numeric( $mpn ) ) {
				$mpn = trim( (string) $mpn );
				if ( '' !== $mpn ) {
					$row['mpn'] = $mpn;
				}
			}
		} else {
			$sku = $target->get_sku();
			if ( $sku ) {
				$row['mpn'] = $sku;
			}
		}
	}

	private function build_variation_row( WC_Product_Variable $parent, WC_Product_Variation $variation ): ?array {
		if ( ! $this->is_offering_included( $variation ) ) {
			return null;
		}
		return $this->compose_row( $variation, $parent );
	}

	/**
	 * @param WC_Product_Variable $product Parent variable product.
	 * @return array<string, mixed>|null
	 */
	private function build_parent_variable_row( WC_Product_Variable $product ): ?array {
		if ( ! $this->is_offering_included( $product ) ) {
			return null;
		}
		$listable = false;
		foreach ( $product->get_children() as $child_id ) {
			$v = wc_get_product( $child_id );
			if ( $v && $v->is_type( 'variation' ) && $this->is_offering_included( $v ) && ! $this->should_exclude_for_stock( $v ) ) {
				$listable = true;
				break;
			}
		}
		if ( ! $listable ) {
			return null;
		}
		return $this->compose_row( $product, $product );
	}

	/**
	 * @param WC_Product $product Simple or other single-SKU types.
	 * @return array<string, mixed>|null
	 */
	private function build_simple_row( WC_Product $product ): ?array {
		if ( $product->is_type( 'variation' ) ) {
			return null;
		}
		if ( $product->is_type( 'variable' ) ) {
			return null;
		}
		if ( ! $this->is_offering_included( $product ) ) {
			return null;
		}
		return $this->compose_row( $product, $product );
	}

	/**
	 * @param WC_Product $target Row product (variation or simple).
	 * @param WC_Product $context Parent for descriptions/links when variation.
	 * @return array<string, mixed>|null
	 */
	private function compose_row( WC_Product $target, WC_Product $context ): ?array {
		if ( $this->should_skip_out_of_stock( $target ) ) {
			return null;
		}
		if ( ! $this->passes_product_pick_exclude( $target, $context ) ) {
			return null;
		}

		$link = $target->get_permalink();
		if ( ! $link ) {
			$link = $context->get_permalink();
		}

		$title = $this->build_title( $target, $context );
		$desc  = $this->build_description( $context );

		$image_id = $target->get_image_id();
		if ( ! $image_id && $context->get_id() !== $target->get_id() ) {
			$image_id = $context->get_image_id();
		}
		$image_link = $image_id ? wp_get_attachment_url( (int) $image_id ) : '';
		if ( ! $image_link ) {
			$image_link = wc_placeholder_img_src( 'full' );
		}

		$currency = get_woocommerce_currency();

		if ( $target->is_type( 'variable' ) ) {
			$regular = (float) $target->get_variation_regular_price( 'min', true );
			$sale_s  = $target->get_variation_sale_price( 'min', true );
			$sale_f  = ( '' !== $sale_s && null !== $sale_s ) ? (float) $sale_s : null;
			if ( $regular <= 0 ) {
				$regular = (float) $target->get_variation_price( 'min', true );
			}
		} else {
			$regular = (float) $target->get_regular_price();
			$sale    = $target->get_sale_price();
			$sale_f  = ( $sale !== '' && null !== $sale ) ? (float) $sale : null;
			if ( $regular <= 0 ) {
				$regular = (float) $target->get_price();
			}
		}

		$price_amount = null !== $sale_f && $sale_f > 0 ? $sale_f : $regular;
		if ( $price_amount <= 0 ) {
			$price_amount = (float) $target->get_price();
		}
		if ( $target->is_type( 'variable' ) && $price_amount <= 0 ) {
			$price_amount = (float) $target->get_variation_price( 'min', true );
		}
		if ( $price_amount <= 0 ) {
			return null;
		}

		$row = array(
			'id'                 => $this->feed_id( $target ),
			'title'              => $title,
			'description'        => $desc,
			'link'               => $link,
			'image_link'         => $image_link,
			'availability'       => $this->availability( $target ),
			'price'              => oamf_format_price_amount( $regular, $currency ),
			'condition'          => (string) $this->settings['condition'],
			'additional_images' => $this->additional_images( $target, $context ),
		);

		if ( null !== $sale_f && $sale_f > 0 && $sale_f < $regular ) {
			$row['sale_price'] = oamf_format_price_amount( $sale_f, $currency );
		}

		$brand = trim( (string) $this->settings['brand'] );
		if ( '' !== $brand ) {
			$row['brand'] = $brand;
		}

		$gpc = trim( (string) $this->settings['google_product_category'] );
		if ( '' !== $gpc ) {
			$row['google_product_category'] = $gpc;
		}

		if ( 'category' === $this->settings['product_type_source'] ) {
			$pt = $this->product_type_string( $context );
			if ( '' !== $pt ) {
				$row['product_type'] = $pt;
			}
		}

		$this->append_identifiers( $row, $target );

		if ( $target->is_type( 'variation' ) && $context->get_id() !== $target->get_id() ) {
			$row['item_group_id'] = (string) $context->get_id();
		}

		$this->apply_attribute_maps( $row, $target );

		return $row;
	}

	/**
	 * @param array<string, mixed> $row Row (modified in place).
	 */
	private function apply_attribute_maps( array &$row, WC_Product $target ): void {
		$maps = array(
			'map_color'    => 'color',
			'map_size'     => 'size',
			'map_material' => 'material',
			'map_pattern'  => 'pattern',
		);
		foreach ( $maps as $setting_key => $field ) {
			$tax = isset( $this->settings[ $setting_key ] ) ? (string) $this->settings[ $setting_key ] : '';
			if ( '' === $tax ) {
				continue;
			}
			$val = $target->get_attribute( $tax );
			if ( ! is_string( $val ) || '' === trim( $val ) ) {
				continue;
			}
			$val = trim( wp_strip_all_tags( $val ) );
			if ( '' !== $val ) {
				$row[ $field ] = $val;
			}
		}
	}

	private function feed_id( WC_Product $target ): string {
		$sku = $target->get_sku();
		if ( $sku ) {
			return (string) $sku;
		}
		return (string) $target->get_id();
	}

	private function availability( WC_Product $product ): string {
		$status = $product->get_stock_status();
		if ( 'instock' === $status ) {
			return 'in_stock';
		}
		if ( 'onbackorder' === $status ) {
			return 'backorder';
		}
		return 'out_of_stock';
	}

	/**
	 * @return list<string>
	 */
	private function additional_images( WC_Product $target, WC_Product $context ): array {
		$max = isset( $this->settings['additional_images'] ) ? (int) $this->settings['additional_images'] : 0;
		$max = max( 0, min( 10, $max ) );
		if ( $max <= 0 ) {
			return array();
		}

		$ids = $target->get_gallery_image_ids();
		if ( empty( $ids ) && $context->get_id() !== $target->get_id() ) {
			$ids = $context->get_gallery_image_ids();
		}

		$urls = array();
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_url( (int) $id );
			if ( $url ) {
				$urls[] = $url;
			}
			if ( count( $urls ) >= $max ) {
				break;
			}
		}
		return $urls;
	}

	private function build_title( WC_Product $target, WC_Product $context ): string {
		if ( 'template' === ( $this->settings['title_mode'] ?? '' ) ) {
			return $this->finalize_feed_title( $this->apply_title_template( $target, $context ) );
		}
		$name = $context->get_name();
		if ( 'name_attrs' !== $this->settings['title_mode'] ) {
			return $this->finalize_feed_title( $name );
		}

		$parts = array( $name );
		if ( $target->is_type( 'variation' ) ) {
			$attrs = wc_get_product_variation_attributes( $target->get_id() );
			if ( is_array( $attrs ) ) {
				foreach ( $attrs as $taxonomy => $slug ) {
					if ( ! $slug ) {
						continue;
					}
					$label = wc_attribute_label( str_replace( 'attribute_', '', $taxonomy ), $target );
					$term  = get_term_by( 'slug', $slug, str_replace( 'attribute_', '', $taxonomy ) );
					$text  = $term && ! is_wp_error( $term ) ? $term->name : $slug;
					$parts[] = $label . ': ' . $text;
				}
			}
		}

		$core = implode( $this->get_title_separator(), array_filter( array_map( 'trim', $parts ) ) );
		return $this->finalize_feed_title( $core );
	}

	private function build_description( WC_Product $context ): string {
		$post = get_post( $context->get_id() );
		if ( ! $post ) {
			return '';
		}
		if ( 'excerpt' === $this->settings['description_mode'] ) {
			$raw = $post->post_excerpt;
		} else {
			$raw = $post->post_content;
		}
		$raw = (string) $raw;
		if ( 'content' === $this->settings['description_mode'] ) {
			$raw = (string) do_shortcode( $raw );
		}
		return trim( wp_strip_all_tags( $raw ) );
	}

	private function product_type_string( WC_Product $product ): string {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		$deepest = array();
		foreach ( $terms as $term ) {
			$chain   = array();
			$current = $term;
			while ( $current && ! is_wp_error( $current ) ) {
				array_unshift( $chain, $current->name );
				if ( ! $current->parent ) {
					break;
				}
				$current = get_term( (int) $current->parent, 'product_cat' );
			}
			$deepest[] = implode( ' > ', $chain );
		}
		if ( empty( $deepest ) ) {
			return '';
		}
		usort(
			$deepest,
			static function ( $a, $b ) {
				return strlen( (string) $b ) <=> strlen( (string) $a );
			}
		);
		return (string) $deepest[0];
	}
}

/**
 * @param float  $amount Amount.
 * @param string $currency Currency code.
 */
function oamf_format_price_amount( float $amount, string $currency ): string {
	return wc_format_decimal( $amount, wc_get_price_decimals() ) . ' ' . strtoupper( $currency );
}
