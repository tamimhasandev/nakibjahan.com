<?php
/*
Plugin Name: UddoktaPay
Plugin URI: https://uddoktapay.com
Description: This plugin allows your customers to pay with Bkash, Rocket, Nagad, Upay via UddoktaPay
Version: 2.3.0
Author: UddoktaPay
Author URI: https://uddoktapay.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: uddoktapay-gateway
*/

// If this file is called firectly, abort!!!
defined( 'ABSPATH' ) or die( 'Direct access is not allowed.' );

add_action( 'plugins_loaded', 'uddoktapay_init_gateway' );

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'uddoktapay_register_payment_method_types' );

// Hook the compatibility declaration function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'uddoktapay_declare_cart_checkout_blocks_compatibility');

/**
 * Custom function to register payment method types
 */
function uddoktapay_register_payment_method_types() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout classes
    require_once plugin_dir_path(__FILE__) . 'uddoktapay_gateway_blocks.php';

    // Register the payment method types
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register instances of payment methods
            $payment_method_registry->register( new UddoktaPay_Gateway_Blocks() );
        }
    );
}

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function uddoktapay_declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

// Init gateway function
function uddoktapay_init_gateway()
{

    /**
     * WooCommerce Required Notice.
     */
    function uddoktapay_woo_required_notice()
    {
        $message = sprintf(
            // translators: %1$s Plugin Name, %2$s wooCommerce.
            esc_html__( ' %1$s requires %2$s to be installed and activated. Please activate %2$s to continue.', 'uddoktapay-gateway' ),
            '<strong>' . esc_html__( 'UddoktaPay Gateway', 'uddoktapay-gateway' ) . '</strong>',
            '<strong>' . esc_html__( 'WooCommerce', 'uddoktapay-gateway' ) . '</strong>'
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    // Class not exist
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        // oops!
        add_action( 'admin_notices', 'uddoktapay_woo_required_notice' );
        return;
    }

    // Require Woocommerce Class
    if ( file_exists( dirname( __FILE__ ) . '/class-wc-gateway-uddoktapay-gateway.php' ) ) {
        require_once dirname( __FILE__ ) . '/class-wc-gateway-uddoktapay-gateway.php';
    }

    // Require Woocommerce International Class
    if ( file_exists( dirname( __FILE__ ) . '/class-wc-gateway-uddoktapay-gateway-international.php' ) ) {
        require_once dirname( __FILE__ ) . '/class-wc-gateway-uddoktapay-gateway-international.php';
    }

    // Load WooCommerce Class
    add_filter( 'woocommerce_payment_gateways', 'uddoktapay_wc_class' );
    // Refresh form
    add_action( 'woocommerce_after_checkout_form', 'uddoktapay_refresh_checkout_on_payment_methods_change' );

    if ( is_admin() ) {

        // Plugin Action Links.
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'uddoktapay_plugin_action_links' );
    }

    // WooCommerce
    function uddoktapay_wc_class( $methods )
    {
        if ( !in_array( 'WC_Gateway_UddoktaPay', $methods ) ) {
            $methods[] = 'WC_Gateway_UddoktaPay';
            $methods[] = 'WC_Gateway_UddoktaPay_International';
        }
        return $methods;
    }

    /**
     * Plugin Action Links
     *
     * @param array $links Links.
     * @return array
     */
    function uddoktapay_plugin_action_links( $links )
    {

        $links[] = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=wc-settings&tab=checkout&section=uddoktapay' ),
            __( 'BD Methods Settings', 'uddoktapay-gateway' )
        );
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=wc-settings&tab=checkout&section=uddoktapayinternational' ),
            __( 'Global Methods Settings', 'uddoktapay-gateway' )
        );
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            'https://uddoktapay.com',
            __( '<b style="color: green">Purchase License</b>', 'uddoktapay-gateway' )
        );

        return $links;
    }

    function uddoktapay_refresh_checkout_on_payment_methods_change()
    {
        wc_enqueue_js( "
           $( 'form.checkout' ).on( 'change', 'input[name^=\'payment_method\']', function() {
               $('body').trigger('update_checkout');
            });
       " );
    }
}