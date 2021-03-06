<?php
defined( 'ABSPATH' ) || exit;

/**
 * Payment Gateway class
 */
class WC_Gateway_Safetypay extends WC_Gateway_Safetypay_Custom {

    /**
     * Constructor
     */
    public function __construct() {

        $options = WC_Safetypay::$settings;

        $this->id = 'safetypay';
        $this->method_title = 'SafetyPay';
        $this->method_description = __( 'Safetypay', 'woocommerce-payment-method' );
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $options['enabled'];
        $this->icon = WC_PAYMENT_PLUGIN_URL . '/assets/img/logo.png';
        $this->title = $options['title'];
        $this->description = $options['description'];
        $this->testmode = $options['testmode'];
        $this->supports = array(
            'products'
        );
        $this->public_key  = 'yes' === $options['testmode'] ? $options['test_public_key'] : $options['public_key'];
        $this->private_key = 'yes' === $options['testmode'] ? $options['test_private_key'] : $options['private_key'];

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        if ( 'yes' === $this->enabled ) {
            $this->init_hooks();
        }
    }

    /**
     * Checks to see if all criteria is met before showing payment method
     */
    public function is_available() {
        
				if ( ! parent::is_available() ||
						 ! $this->private_key ||
						 ! $this->public_key ||
						 ! in_array( get_woocommerce_currency(), self::get_supported_currency() )
				) {
						return false;
				}

				return true;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = require( dirname( __FILE__ ) . '/admin/payment-settings.php' );
    }

    /**
     * Gets the transaction URL linked to safetypay dashboard
     */
    public function get_transaction_url( $order ) {
        $this->view_transaction_url = 'https://localhost.co/transactions/%s';

        return parent::get_transaction_url( $order );
    }

    /**
     * Process the payment (after place order)
     */
    public function process_payment( $order_id ) {
        $order = new WC_Order( $order_id );
        if ( version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=') ) {
            /* >= 2.1.0 */
            $checkout_payment_url = $order->get_checkout_payment_url(true);
        } else {
            /* < 2.1.0 */
            $checkout_payment_url = get_permalink( get_option('woocommerce_pay_page_id') );
        }

        // Clear cart
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => add_query_arg( 'order_pay', $order_id, $checkout_payment_url )
        );
    }

    /**
     * Process the payment to void
     */
    public static function process_void( $order ) {

        // Restore stock
        wc_maybe_increase_stock_levels( $order );
    }
}