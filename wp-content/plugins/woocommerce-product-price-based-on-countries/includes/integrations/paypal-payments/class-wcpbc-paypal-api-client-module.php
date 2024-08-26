<?php
/**
 * Overwrites the ApiClient/ApiModule to use the get_woocommerce_currency function to PayPal Payments accepts again multi-currency payments.
 *
 * Thanks WooCommerce PayPal Payments developers: I hate you!
 *
 * If are reading this, you can reuse the code in your currency switcher plugin, but, please add a thanks @oscargare to your changelog.
 *
 * @package WCPBC/Integrations
 */

declare(strict_types=1);

use WooCommerce\PayPalCommerce\ApiClient\ApiModule as ApiModule;
use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Class ApiModule
 */
class WCPBC_PayPal_Api_Client_Module extends ApiModule {

	/**
	 * {@inheritDoc}
	 */
	public function setup(): ServiceProviderInterface {
		$files = $this->wcpbc_get_required_files();
		if ( ! $files ) {
			return parent::setup();
		}

		$services   = require $files['services'];
		$extensions = require $files['extensions'];

		if ( isset( $services['api.shop.currency'] ) ) {
			$services['api.shop.currency'] = static function ( ContainerInterface $container ) : string {
				// Set the real currency.
				$currency = get_woocommerce_currency(); // Important for the plugin to work!!.
				if ( ! $currency ) {
					return 'NO_CURRENCY'; // Unlikely to happen.
				}
				return $currency;
			};
		}

		return new ServiceProvider(
			$services,
			$extensions
		);
	}

	/**
	 * Returns an array with the services and extensions files.
	 *
	 * @return array
	 */
	protected function wcpbc_get_required_files() {
		$files = false;
		$dir   = dirname( WCPBC_PLUGIN_FILE ) . '/../woocommerce-paypal-payments/modules/ppcp-api-client/';
		$files = array(
			'services'   => $dir . 'services.php',
			'extensions' => $dir . 'extensions.php',
		);

		$files = file_exists( $files['services'] ) && file_exists( $files['extensions'] ) ? $files : false;

		return $files;
	}
}
