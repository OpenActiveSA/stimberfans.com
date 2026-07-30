<?php
/**
 * Serializes rows to XML (RSS + Google) or TSV.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed output.
 */
final class OAMF_Feed_Builder {

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
	 * @param list<array<string, mixed>> $rows Rows.
	 */
	public function build_xml( array $rows ): string {
		$shop_name = get_bloginfo( 'name' );
		$shop_link = home_url( '/' );

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
		$xml .= "  <channel>\n";
		$xml .= '    <title>' . $this->xml_text( $shop_name ) . "</title>\n";
		$xml .= '    <link>' . $this->xml_text( $shop_link ) . "</link>\n";
		$xml .= '    <description>' . $this->xml_text( get_bloginfo( 'description' ) ) . "</description>\n";

		foreach ( $rows as $row ) {
			$xml .= $this->item_xml_block( $row );
		}

		$xml .= "  </channel>\n</rss>\n";
		return $xml;
	}

	/**
	 * One RSS <item> block (Google fields) for previews or custom output.
	 *
	 * @param array<string, mixed> $row Row.
	 */
	public function item_xml_block( array $row ): string {
		$xml = "    <item>\n";
		$xml .= $this->xml_item_line( 'g:id', (string) $row['id'] );
		$xml .= $this->xml_item_line( 'g:title', (string) $row['title'] );
		$xml .= $this->xml_item_line( 'g:description', (string) $row['description'] );
		$xml .= $this->xml_item_line( 'g:link', (string) $row['link'] );
		$xml .= $this->xml_item_line( 'g:image_link', (string) $row['image_link'] );

		if ( ! empty( $row['additional_images'] ) && is_array( $row['additional_images'] ) ) {
			foreach ( $row['additional_images'] as $url ) {
				$xml .= $this->xml_item_line( 'g:additional_image_link', (string) $url );
			}
		}

		$xml .= $this->xml_item_line( 'g:availability', (string) $row['availability'] );
		$xml .= $this->xml_item_line( 'g:price', (string) $row['price'] );

		if ( ! empty( $row['sale_price'] ) ) {
			$xml .= $this->xml_item_line( 'g:sale_price', (string) $row['sale_price'] );
		}

		$xml .= $this->xml_item_line( 'g:condition', (string) $row['condition'] );

		if ( ! empty( $row['brand'] ) ) {
			$xml .= $this->xml_item_line( 'g:brand', (string) $row['brand'] );
		}
		if ( ! empty( $row['gtin'] ) ) {
			$xml .= $this->xml_item_line( 'g:gtin', (string) $row['gtin'] );
		}
		if ( ! empty( $row['mpn'] ) ) {
			$xml .= $this->xml_item_line( 'g:mpn', (string) $row['mpn'] );
		}
		if ( ! empty( $row['google_product_category'] ) ) {
			$xml .= $this->xml_item_line( 'g:google_product_category', (string) $row['google_product_category'] );
		}
		if ( ! empty( $row['product_type'] ) ) {
			$xml .= $this->xml_item_line( 'g:product_type', (string) $row['product_type'] );
		}
		if ( ! empty( $row['item_group_id'] ) ) {
			$xml .= $this->xml_item_line( 'g:item_group_id', (string) $row['item_group_id'] );
		}
		foreach ( array( 'color', 'size', 'material', 'pattern' ) as $f ) {
			if ( ! empty( $row[ $f ] ) ) {
				$xml .= $this->xml_item_line( 'g:' . $f, (string) $row[ $f ] );
			}
		}

		$xml .= "    </item>\n";
		return $xml;
	}

	private function xml_item_line( string $tag, string $value ): string {
		return '      <' . $tag . '>' . $this->xml_text( $value ) . '</' . $tag . ">\n";
	}

	private function xml_text( string $value ): string {
		return htmlspecialchars( $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * @param list<array<string, mixed>> $rows Rows.
	 */
	public function build_tsv( array $rows ): string {
		$headers = array(
			'id',
			'title',
			'description',
			'link',
			'image_link',
			'additional_image_link',
			'availability',
			'price',
			'sale_price',
			'condition',
			'brand',
			'gtin',
			'mpn',
			'google_product_category',
			'product_type',
			'item_group_id',
			'color',
			'size',
			'material',
			'pattern',
		);

		$lines   = array();
		$lines[] = implode( "\t", $headers );

		foreach ( $rows as $row ) {
			$add = '';
			if ( ! empty( $row['additional_images'] ) && is_array( $row['additional_images'] ) ) {
				$add = implode( ',', array_map( array( $this, 'tsv_cell' ), $row['additional_images'] ) );
			}

			$cells = array(
				(string) $row['id'],
				(string) $row['title'],
				(string) $row['description'],
				(string) $row['link'],
				(string) $row['image_link'],
				$add,
				(string) $row['availability'],
				(string) $row['price'],
				isset( $row['sale_price'] ) ? (string) $row['sale_price'] : '',
				(string) $row['condition'],
				isset( $row['brand'] ) ? (string) $row['brand'] : '',
				isset( $row['gtin'] ) ? (string) $row['gtin'] : '',
				isset( $row['mpn'] ) ? (string) $row['mpn'] : '',
				isset( $row['google_product_category'] ) ? (string) $row['google_product_category'] : '',
				isset( $row['product_type'] ) ? (string) $row['product_type'] : '',
				isset( $row['item_group_id'] ) ? (string) $row['item_group_id'] : '',
				isset( $row['color'] ) ? (string) $row['color'] : '',
				isset( $row['size'] ) ? (string) $row['size'] : '',
				isset( $row['material'] ) ? (string) $row['material'] : '',
				isset( $row['pattern'] ) ? (string) $row['pattern'] : '',
			);

			$lines[] = implode( "\t", array_map( array( $this, 'tsv_cell' ), $cells ) );
		}

		return implode( "\n", $lines ) . "\n";
	}

	private function tsv_cell( string $value ): string {
		$value = str_replace( array( "\r\n", "\r", "\n" ), ' ', $value );
		$value = str_replace( "\t", ' ', $value );
		return $value;
	}
}
