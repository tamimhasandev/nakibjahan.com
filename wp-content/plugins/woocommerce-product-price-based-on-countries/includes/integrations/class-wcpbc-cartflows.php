<?php
/**
 * Handle integration with CartFlows by CartFlows Inc.
 *
 * @see https://cartflows.com/
 *
 * @since 3.1.1
 * @package WCPBC/Integrations
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCPBC_CartFlows class.
 */
class WCPBC_CartFlows {

	/**
	 * Admin notice.
	 *
	 * @var string
	 */
	private static $notice = '';

	/**
	 * Checks the environment for compatibility problems.
	 *
	 * @return boolean
	 */
	public static function check_environment() {

		$compatible     = true;
		$plugin_version = defined( 'CARTFLOWS_PRO_VER' ) ? CARTFLOWS_PRO_VER : 'unknown';
		$min_version    = '1.11.9';

		if ( 'unknown' === $plugin_version || version_compare( $plugin_version, $min_version, '<' ) ) {
			// translators: 1: HTML tag, 2: HTML tag, 3: Google Listings and Ads.
			self::$notice = sprintf( __( '%1$sPrice Based on Country Pro & CartFlows%2$s compatibility %1$srequires%2$s CartFlows %1$s+%4$s%2$s. You are running CartFlows %3$s.', 'woocommerce-product-price-based-on-countries' ), '<strong>', '</strong>', $plugin_version, $min_version );
			add_action( 'admin_notices', array( __CLASS__, 'min_version_notice' ) );

			$compatible = false;
		}

		return $compatible;
	}

	/**
	 * Display admin minimun version required
	 */
	public static function min_version_notice() {
		echo '<div id="message" class="error"><p>' . wp_kses_post( self::$notice ) . '</p></div>';
	}

	/**
	 * Hook actions and filters
	 */
	public static function init() {
		if ( ! self::check_environment() ) {
			return;
		}

		add_filter( 'cartflows_filter_display_price', [ __CLASS__, 'filter_display_price' ], 10, 2 );
	}

	/**
	 * Returns the product price.
	 *
	 * @param float $price price.
	 * @param int   $product_id current product ID.
	 */
	public static function filter_display_price( $price, $product_id ) {
		if ( $product_id && wcpbc_the_zone() ) {
			return wcpbc_the_zone()->get_post_price( $product_id, '_price' );
		}
		return $price;
	}
}

WCPBC_CartFlows::init();
