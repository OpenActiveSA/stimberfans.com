<?php
/**
 * Cached feed preview for the admin screen.
 *
 * @package OA_Merchant_Feed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds preview payload (total rows + sample).
 */
final class OAMF_Preview {

	public const TRANSIENT = 'oamf_feed_preview_v1';

	public static function invalidate(): void {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * @param array<string, mixed> $settings Current settings.
	 * @return array{
	 *   total:int,
	 *   sample:?array<string,mixed>,
	 *   sample_truncated:?array<string,mixed>,
	 *   error:?string,
	 *   built:int,
	 *   xml_sample:?string,
	 *   tsv_sample:?string
	 * }
	 */
	public static function get_payload( array $settings ): array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['built'], $cached['total'] ) ) {
			return $cached;
		}

		$empty = array(
			'total'             => 0,
			'sample'            => null,
			'sample_truncated'  => null,
			'error'             => null,
			'built'             => time(),
			'xml_sample'        => null,
			'tsv_sample'        => null,
		);

		if ( ! class_exists( 'WooCommerce' ) ) {
			$empty['error'] = __( 'WooCommerce is not active.', 'oa-merchant-feed' );
			set_transient( self::TRANSIENT, $empty, MINUTE_IN_SECONDS * 5 );
			return $empty;
		}

		try {
			$items = new OAMF_Items( $settings );
			$rows  = $items->get_rows();
		} catch ( Throwable $e ) {
			$empty['error'] = $e->getMessage();
			set_transient( self::TRANSIENT, $empty, MINUTE_IN_SECONDS * 5 );
			return $empty;
		}

		$total  = count( $rows );
		$sample = $total > 0 ? $rows[0] : null;

		$trunc = null;
		$xml   = null;
		$tsv   = null;

		if ( is_array( $sample ) ) {
			$trunc = $sample;
			if ( isset( $trunc['description'] ) && is_string( $trunc['description'] ) && strlen( $trunc['description'] ) > 420 ) {
				$trunc['description'] = self::truncate_text( $trunc['description'], 420 ) . '…';
			}

			$builder = new OAMF_Feed_Builder( $settings );
			$xml     = $builder->item_xml_block( $trunc );
			$tsv     = $builder->build_tsv( array( $trunc ) );
		}

		$out = array(
			'total'            => $total,
			'sample'           => $sample,
			'sample_truncated' => $trunc,
			'error'            => null,
			'built'            => time(),
			'xml_sample'       => $xml,
			'tsv_sample'       => $tsv,
		);

		set_transient( self::TRANSIENT, $out, 15 * MINUTE_IN_SECONDS );
		return $out;
	}

	private static function truncate_text( string $text, int $max ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return (string) mb_substr( $text, 0, $max, 'UTF-8' );
		}
		return substr( $text, 0, $max );
	}
}
