<?php
/**
 * Handle integration with the Stipe UPE payment method (WooCommerce Stripe Payment Gateway)
 *
 * @see https://wordpress.org/plugins/woocommerce-gateway-stripe/
 *
 * @since 3.4.7
 * @package WCPBC/Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCPBC_Stripe_UPE' ) ) :

	/**
	 * WCPBC_Stripe_UPE class.
	 */
	class WCPBC_Stripe_UPE {

		/**
		 * Init integration
		 */
		public static function init() {
			$main_gateway = null;
			$stripe       = woocommerce_gateway_stripe();

			if ( is_callable( [ $stripe, 'get_main_stripe_gateway' ] ) ) {
				$main_gateway = woocommerce_gateway_stripe()->get_main_stripe_gateway();
			}

			if ( is_a( $main_gateway, 'WC_Stripe_UPE_Payment_Gateway' ) ) {
				self::init_hooks();
			}
		}

		/**
		 * Hook actions and filters
		 */
		public static function init_hooks() {
			add_action( 'admin_notices', [ __CLASS__, 'add_supported_currencies_filter' ], 0 );
			add_action( 'admin_notices', [ __CLASS__, 'remove_supported_currencies_filter' ], 20 );
			add_action( 'wp_footer', [ __CLASS__, 'enqueue_scripts' ], 0 );
			add_filter( 'wc_stripe_upe_params', [ __CLASS__, 'stripe_upe_params' ] );
			add_filter( 'woocommerce_update_order_review_fragments', [ __CLASS__, 'update_order_review_fragments' ] );
		}

		/**
		 * Do not display the "it requires store currency" if the required currency is in a pricing zone.
		 */
		public static function add_supported_currencies_filter() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			self::supported_currencies_filter( 'add' );
		}

		/**
		 * Removes the supported currencies filter.
		 */
		public static function remove_supported_currencies_filter() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			self::supported_currencies_filter( 'remove' );
		}

		/**
		 * Add/remove supported currencies filter.
		 *
		 * @param string $add_or_remove Add or remove filters flag.
		 */
		private static function supported_currencies_filter( $add_or_remove = 'add' ) {

			$main_gateway = woocommerce_gateway_stripe()->get_main_stripe_gateway();
			$callback     = 'add' === $add_or_remove ? 'add_filter' : 'remove_filter';

			foreach ( $main_gateway->get_upe_enabled_payment_method_ids() as $payment_method_id ) {

				if ( 'card' === $payment_method_id ) {
					continue;
				}
				call_user_func( $callback, "wc_stripe_{$payment_method_id}_upe_supported_currencies", [ __CLASS__, 'supported_currencies' ], 9999 );
			}

			call_user_func( $callback, 'wc_stripe_multibanco_supported_currencies', [ __CLASS__, 'supported_currencies' ], 9999 );
		}

		/**
		 * Include the base currency in the supported currencies to do not display the alert if the supported currency is in a pricing zone.
		 *
		 * @param array $supported_currencies Supported currencies.
		 */
		public static function supported_currencies( $supported_currencies ) {
			if ( empty( $supported_currencies ) || in_array( wcpbc_get_base_currency(), $supported_currencies, true ) ) {
				return $supported_currencies;
			}

			static $all_currencies = false;

			if ( false === $all_currencies ) {
				foreach ( WCPBC_Pricing_Zones::get_zones() as $zone ) {
					if ( ! $zone->get_enabled() ) {
						continue;
					}
					$all_currencies[] = $zone->get_currency();
				}

				$all_currencies[] = wcpbc_get_base_currency();
				$all_currencies   = array_unique( $all_currencies );
			}

			if ( count( array_intersect( $supported_currencies, $all_currencies ) ) ) {
				$supported_currencies[] = wcpbc_get_base_currency();
			}

			return $supported_currencies;
		}

		/**
		 * Enqueue scripts
		 */
		public static function enqueue_scripts() {
			if ( ! is_checkout() ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script(
				'wcpbc-stripe-upe-compatibility',
				WCPBC()->plugin_url() . 'assets/js/stripe-upe-compatibility' . $suffix . '.js',
				[ 'jquery' ],
				WCPBC()->version,
				true
			);
		}

		/**
		 * Update the fragments with the payment method config.
		 *
		 * @param array $fragments Array of fragments to return in the AJAX call update_order_review.
		 * @return array
		 */
		public static function update_order_review_fragments( $fragments ) {

			if ( ! is_callable( [ 'WC_Stripe_Helper', 'get_stripe_amount' ] ) ) {
				return $fragments;
			}

			if ( ! is_array( $fragments ) ) {
				$fragments = array();
			}

			$cart_total = ( WC()->cart ? WC()->cart->get_total( '' ) : 0 );
			$currency   = get_woocommerce_currency();

			$fragments['wcpbc_stripe_upe'] = [
				'currency'  => $currency,
				'cartTotal' => WC_Stripe_Helper::get_stripe_amount( $cart_total, strtolower( $currency ) ),
			];

			return $fragments;
		}

		/**
		 * Add all enabled payment methods to the JavaScript configuration object.
		 *
		 * @param array $params JavaScript configuration object.
		 */
		public static function stripe_upe_params( $params ) {
			if ( ! is_checkout() ) {
				return $params;
			}

			$params['paymentMethodsConfig'] = self::get_enabled_payment_method_config();

			return $params;
		}

		/**
		 * Returns the enabled payment method settings.
		 *
		 * @return array
		 */
		private static function get_enabled_payment_method_config() {
			$settings                = [];
			$main_gateway            = woocommerce_gateway_stripe()->get_main_stripe_gateway();
			$enabled_payment_methods = $main_gateway->get_upe_enabled_payment_method_ids();

			foreach ( $enabled_payment_methods as $payment_method_id ) {

				$payment_method = isset( $main_gateway->payment_methods[ $payment_method_id ] ) ? $main_gateway->payment_methods[ $payment_method_id ] : null;

				if ( is_null( $payment_method ) ) {
					continue;
				}

				$settings[ $payment_method_id ] = [
					'isReusable'          => $payment_method->is_reusable(),
					'title'               => $payment_method->get_title(),
					'testingInstructions' => $payment_method->get_testing_instructions(),
					'showSaveOption'      => $payment_method->is_reusable() && $main_gateway->is_saved_cards_enabled() && ! $main_gateway->is_subscription_item_in_cart() && ! $main_gateway->is_pre_order_charged_upon_release_in_cart(),
					'countries'           => [], // No countries to prevent "is null" error on hide/show the payment method. Delegate to update_order_review.
				];
			}

			return $settings;
		}
	}

	WCPBC_Stripe_UPE::init();

endif;
