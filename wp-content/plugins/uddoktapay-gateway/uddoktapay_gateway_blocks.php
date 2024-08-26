<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class UddoktaPay_Gateway_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'uddoktapay';
    public function initialize()
    {
        $this->settings = get_option('woocommerce_uddoktapay_settings', []);
        $this->gateway = new WC_Gateway_UddoktaPay();
    }
    public function is_active()
    {
        return $this->gateway->is_available();
    }
    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'uddoktapay-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('uddoktapay-blocks-integration');

        }
        return ['uddoktapay-blocks-integration'];
    }
    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
        ];
    }
}