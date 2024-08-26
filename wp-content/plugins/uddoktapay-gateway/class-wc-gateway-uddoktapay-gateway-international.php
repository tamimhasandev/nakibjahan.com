<?php

// If this file is called firectly, abort!!!
defined( 'ABSPATH' ) or die( 'Direct access is not allowed.' );

/**
 * WC_Gateway_UddoktaPay_International_Class
 */
class WC_Gateway_UddoktaPay_International extends WC_Payment_Gateway
{

    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;

    // webhook url
    public $webhook_url;

    //debug
    public $debug;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'uddoktapayinternational';
        $this->has_fields = false;
        $this->order_button_text = __( 'Proceed to Checkout', 'uddoktapay-gateway-international' );
        $this->method_title = __( 'UddoktaPay International', 'uddoktapay-gateway-international' );
        $this->webhook_url = add_query_arg( 'wc-api', 'WC_Gateway_UddoktaPay', home_url( '/' ) );
        $this->method_description = '<p>' .
        // translators: Introduction text at top of UddoktaPay settings page.
        __( 'A payment gateway that sends your customers to UddoktaPay to pay with Paypal, Stripe, Paddle, Perfect Money.', 'uddoktapay-gateway-international' )
        . '</p><p>' .
        sprintf(
            // translators: Introduction text at top of UddoktaPay settings page. Includes external URL.
            __( 'If you do not currently have a UddoktaPay account, you can create an account from: %s', 'uddoktapay-gateway-international' ),
            '<a target="_blank" href="https://uddoktapay.com">Here</a>'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->debug = 'yes' === $this->get_option( 'debug', 'no' );

        self::$log_enabled = $this->debug;

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options'] );
        add_action( 'woocommerce_api_wc_gateway_' . $this->id, [$this, 'handle_webhook'] );
        //add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'admin_order_data'));
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level   Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     */
    public static function log( $message, $level = 'info' )
    {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = wc_get_logger();
            }
            self::$log->log( $level, $message, ['source' => 'uddoktapay'] );
        }
    }

    /**
     * Initialise Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled'         => [
                'title'   => __( 'Enable/Disable', 'uddoktapay-gateway-international' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable UddoktaPay International Payment', 'uddoktapay-gateway-international' ),
                'default' => 'yes',
            ],
            'title'           => [
                'title'       => __( 'Title', 'uddoktapay-gateway-international' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'uddoktapay-gateway-international' ),
                'default'     => __( 'Paypal, Stripe, Paddle, Perfect Money', 'uddoktapay-gateway-international' ),
                'desc_tip'    => true,
            ],
            'description'     => [
                'title'       => __( 'Description', 'uddoktapay-gateway-international' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'This controls the description which the user sees during checkout.', 'uddoktapay-gateway-international' ),
                'default'     => __( 'Pay with Paypal, Stripe, Paddle, Perfect Money.', 'uddoktapay-gateway-international' ),
            ],
            'api_key'         => [
                'title'       => __( 'API Key', 'uddoktapay-gateway-international' ),
                'type'        => 'text',
                'default'     => '',
                'description' => sprintf(
                    // translators: Description field for API on settings page. Includes external link.
                    __(
                        'You can manage your API keys within the UddoktaPay Panel Brand Settings Page.',
                        'uddoktapay-gateway-international'
                    )
                ),
            ],
            'api_url'         => [
                'title'       => __( 'API URL', 'uddoktapay-gateway-international' ),
                'type'        => 'text',
                'default'     => '',
                'description' => sprintf(
                    // translators: Description field for API on settings page. Includes external link.
                    __(
                        'You will find your API URL in the UddoktaPay Payment Panel Brand Settings Page.',
                        'uddoktapay-gateway-international'
                    )
                ),
            ],
            'exchange_rate'   => [
                'title'       => __( 'BD to USD Exchange Rate', 'uddoktapay-gateway-international' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'This rate will be apply to the total amount of the cart', 'uddoktapay-gateway-international' ),
                'default'     => 0,
            ],
            'digital_product' => [
                'title'   => __( 'Digital Product', 'uddoktapay-gateway-international' ),
                'type'    => 'checkbox',
                'label'   => __( 'If you are providing digital product then you can use this option. It will mark order as complete as soon as user paid.', 'uddoktapay-gateway-international' ),
                'default' => 'no',
            ],
            'debug'           => [
                'title'       => __( 'Debug log', 'uddoktapay-gateway-international' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable logging', 'uddoktapay-gateway-international' ),
                'default'     => 'no',
                // translators: Description for 'Debug log' section of settings page.
                'description' => sprintf( __( 'Log UddoktaPay API events inside %s', 'uddoktapay-gateway-international' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'uddoktapay' ) . '</code>' ),
            ],
        ];
    }

    /**
     * Process the payment and return the result.
     * @param  int $order_id
     * @return array
     */

    public function process_payment( $order_id )
    {
        global $woocommerce;
        $order = new WC_Order( $order_id );

        $this->init_api();

        // Create a new charge.
        $metadata = [
            'order_id'     => $order->get_id(),
            'order_key'    => $order->get_order_key(),
            'source'       => 'woocommerce',
            'redirect_url' => $this->get_return_url( $order ),
        ];
        $full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
        $email = $order->get_billing_email();

        $result = UddoktaPay_Gateway_API_Handler::create_payment_international(
            $order->get_total(),
            get_woocommerce_currency(),
            $full_name,
            $email,
            $metadata,
            $this->webhook_url,
            $this->get_cancel_url( $order ),
            $this->webhook_url,
            $this->get_option( 'exchange_rate' )
        );

        if ( isset( $result ) && empty( $result->payment_url ) ) {
            wc_add_notice( esc_html__( $result->message, 'uddoktapay-gateway' ), 'error' );
            return;
        }

        if ( $order->get_status() != 'completed' ) {
            // Mark as pending
            $order->update_status( 'pending', __( 'Customer is being redirected to UddoktaPay', 'uddoktapay-gateway-international' ) );
        }

        // Remove cart
        $woocommerce->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $result->payment_url,
        ];
    }

    /**
     * Get the cancel url.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    public function get_cancel_url( $order )
    {
        $return_url = $order->get_cancel_order_url();

        if ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) == 'yes' ) {
            $return_url = str_replace( 'http:', 'https:', $return_url );
        }

        return apply_filters( 'woocommerce_get_cancel_url', $return_url, $order );
    }

    /**
     * Handle requests sent to webhook.
     */
    public function handle_webhook()
    {
        $invoice_id = sanitize_text_field( $_GET['invoice_id'] );
        if ( !empty( $invoice_id ) ) {
            $this->init_api();
            $result = UddoktaPay_Gateway_API_Handler::verify_payment( $invoice_id );
            if ( isset( $result ) && isset( $result->status ) ) {
                self::log( 'POST received event: ' . print_r( $result, true ) );
                if ( !isset( $result->metadata->order_id ) ) {
                    // Probably a charge not created by us.
                    exit;
                }
                $order_id = $result->metadata->order_id;
                $redirect_url = $result->metadata->redirect_url;
                $this->_update_order_status( wc_get_order( $order_id ), $result );
                // Redirect
                wp_redirect( $redirect_url );
                exit;
            }
        } else {
            $payload = file_get_contents( 'php://input' );
            if ( !empty( $payload ) && $this->validate_webhook( $payload ) ) {
                $data = json_decode( $payload );

                self::log( 'Webhook received event: ' . print_r( $data, true ) );

                if ( !isset( $data->metadata->order_id ) ) {
                    // Probably a charge not created by us.
                    exit;
                }

                $order_id = $data->metadata->order_id;

                $this->_update_order_status( wc_get_order( $order_id ), $data );
            }
            exit();
        }
    }

    /**
     * Check UddoktaPay webhook request is valid.
     * @param  string $data
     */
    public function validate_webhook( $data )
    {
        self::log( 'Checking Webhook response is valid' );

        $key = 'HTTP_' . strtoupper( str_replace( '-', '_', 'RT-UDDOKTAPAY-API-KEY' ) );
        if ( !isset( $_SERVER[$key] ) ) {
            return false;
        }

        $api = sanitize_text_field( $_SERVER[$key] );

        $api_key = sanitize_text_field( $this->get_option( 'api_key' ) );

        if ( $api_key === $api ) {
            self::log( 'Valid response' );
            return true;
        }

        return false;
    }

    /**
     * Init the API class and set the API key etc.
     */
    protected function init_api()
    {
        include_once dirname( __FILE__ ) . '/includes/class-uddoktapay-api-handler.php';

        UddoktaPay_Gateway_API_Handler::$log = get_class( $this ) . '::log';
        UddoktaPay_Gateway_API_Handler::$api_url = sanitize_text_field( $this->get_option( 'api_url' ) );
        UddoktaPay_Gateway_API_Handler::$api_key = sanitize_text_field( $this->get_option( 'api_key' ) );
    }

    /**
     * Update the status of an order from a given timeline.
     * @param  WC_Order $order
     * @param  array    $timeline
     */
    public function _update_order_status( $order, $data )
    {
        $order->update_meta_data( 'uddoktapay_international_payment_data', $data );

        if ( $order->get_status() != 'completed' ) {
            if ( $data->status === 'COMPLETED' ) {
                $transaction_id = $data->transaction_id;
                $amount = $data->amount;
                $sender_number = $data->sender_number;
                $payment_method = $data->payment_method;
                if ( $this->get_option( 'digital_product' ) === 'yes' ) {
                    $order->update_status( 'completed', __( "UddoktaPay payment was successfully completed. Payment Method: {$payment_method}, Amount: {$amount}, Transaction ID: {$transaction_id}", 'uddoktapay-gateway-international' ) );
                    // Reduce stock levels
                    $order->reduce_order_stock();
                    $order->payment_complete();
                } else {
                    $order->update_status( 'processing', __( "UddoktaPay payment was successfully processed. Payment Method: {$payment_method}, Amount: {$amount}, Transaction ID: {$transaction_id}", 'uddoktapay-gateway-international' ) );
                    // Reduce stock levels
                    $order->reduce_order_stock();
                    $order->payment_complete();
                }
                return true;
            } else {
                $order->update_status( 'on-hold', __( 'UddoktaPay payment was successfully on-hold. Transaction id not found. Please check it manually.', 'uddoktapay-gateway-international' ) );
                return true;
            }
        }
    }

    /**
     * Display bKash data in admin page.
     *
     * @param Object $order Order.
     */
    public function admin_order_data( $order )
    {
        if ( 'uddoktapayinternational' !== $order->get_payment_method() ) {
            return;
        }

        // payment data
        $data = ( get_post_meta( sanitize_text_field( $_GET['post'] ), 'uddoktapay_international_payment_data', true ) ) ? get_post_meta( sanitize_text_field( $_GET['post'] ), 'uddoktapay_international_payment_data', true ) : '';

        ?>
		<?php if ( isset( $data ) && isset( $data->payment_method ) ): ?>
			<div class="form-field form-field-wide bdpg-admin-data">
				<img src="<?php echo esc_url( $img_url ); ?> " alt="<?php echo esc_attr( isset( $data->payment_method ) ? $data->payment_method : "" ); ?>">
				<table class="wp-list-table widefat striped posts">
					<tbody>
						<tr>
							<th>
								<strong>
									<?php echo __( 'Payment Method', 'uddoktapay-gateway-international' ); ?>
								</strong>
							</th>
							<td>
								<?php echo esc_attr( ucfirst( isset( $data->payment_method ) ? $data->payment_method : "" ) ); ?>
							</td>
						</tr>
						<tr>
							<th>
								<strong>
									<?php echo __( 'Transaction ID', 'uddoktapay-gateway-international' ); ?>
								</strong>
							</th>
							<td>
								<?php echo esc_attr( isset( $data->transaction_id ) ? $data->transaction_id : "" ); ?>
							</td>
						</tr>
						<tr>
							<th>
								<strong>
									<?php echo __( 'Amount', 'uddoktapay-gateway-international' ); ?>
								</strong>
							</th>
							<td>
								<?php echo esc_attr( isset( $data->amount ) ? $data->amount : "" ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php endif?>
<?php
}
}
