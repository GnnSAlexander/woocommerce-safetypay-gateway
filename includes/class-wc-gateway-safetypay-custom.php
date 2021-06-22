<?php
defined( 'ABSPATH' ) || exit;

/**
 * Extend Payment Gateway class
 */
class WC_Gateway_Safetypay_Custom extends WC_Payment_Gateway {

    /**
     * Vars
     */
    const MINIMUM_ORDER_AMOUNT = 10;
    public $testmode;
    public $public_key;
    public $private_key;
    public static $supported_currency = false;

    /**
     * Init hooks
     */
    public function init_hooks() {
        add_action( 'woocommerce_receipt_safetypay', array( $this, 'generate_button_widget' ) );
    }

    /**
     * Returns all supported currencies for this payment method
     */
    public static function get_supported_currency() {
        if ( self::$supported_currency === false ) {
            self::$supported_currency = apply_filters( 'wc_safetypay_supported_currencies', array('PEN','USD') );
        }

        return self::$supported_currency;
    }

    /**
     * Generate safetypay widget on "Pay for order" page
     */
    public function generate_button_widget( $order_id ) {
        $order = new WC_Order( $order_id );
        //var_dump($order);
        $order->add_order_note( 'Safetypay Iniciado' );
        do_action( 'woocommerce_thankyou', $order->get_id() );

        $merchant_sales_id = WC_Safetypay_Helper::set_format_order( $order_id );

        $url_success = WC_Safetypay::$settings['redirect_page_success_id'] ?   get_the_permalink(WC_Safetypay::$settings['redirect_page_success_id']) : get_site_url() ;
        $url_error =  WC_Safetypay::$settings['redirect_page_error_id'] ?   get_the_permalink(WC_Safetypay::$settings['redirect_page_error_id']) : get_site_url() ;
   
        $params = array(
            'custom_merchant_name' => get_bloginfo(),
            'application_id' => 1,
            'requested_payment_type' => 'StandardorBoleto',
            'merchant_sales_id' => $merchant_sales_id,
            'expiration_time_minutes' => 120,
            'language_code' => "ES",
            'merchant_set_pay_amount' => false,
            "payment_error_url" => $url_error,
            "payment_ok_url"=> $url_success,
            "sales_amount"=> array(
                "currency_code"=> get_woocommerce_currency(),
                "value" => $order->get_total()
            ),
            "send_email_shopper" => true,
            "transaction_email"=>  $order->get_billing_email()
        );

        //echo wp_json_encode($params);
        $response = WC_Safetypay_API::instance()->get_payment_requests($params);
        if( isset($response->gateway_token_url) && !is_null($response->gateway_token_url) ){
            $out = '';
            $out .= '<div class="button-holder">';
            $out .= '<a href="'.$response->gateway_token_url.'" class="button alt"> Pay with Safetypay</a>';
            $out .= '</div>';
    
            echo $out;
        }
        
    }

    /**
     * Billing details fields on the checkout page
     */
    public static function billing_fields() {
        return array(); // return empty, means hide
    }

    /**
     * Before checkout billing form
     */
    public static function before_checkout_billing_form() {
        echo '<p>' . __('Billing details will need to be entered in the safetypage widget', 'woocommerce-gateway-payment') . '</p>';
    }

    /**
     * Generate order key on thank you page
     */
    public static function thankyou_order_key( $order_key ) {
        if ( empty( $_GET['key'] ) ) {
            global $wp;
            $order = wc_get_order( $wp->query_vars['order-received'] );
            $order_key = $order->get_order_key();
        }

        return $order_key;
    }

    /**
     * Inform user if status of received order is failed on the thank you page
     */
    public static function thankyou_order_received_text( $text ) {
        global $wp;
        $order = wc_get_order( $wp->query_vars['order-received'] );
        $status = $order->get_status();
        if ( in_array( $status, array( 'cancelled', 'failed', 'refunded', 'voided' ) ) ) {
            return '<div class="woocommerce-error">' . sprintf( __( 'This order changed to status &ldquo;%s&rdquo;. Please contact us if you need assistance.', 'woocommerce-gateway-payment' ), $status ) . '</div>';
        } else {
            return $text;
        }
    }

    /**
     * Validation on checkout page
     */
    public static function checkout_validation( $fields, $errors ){
        $amount = floatval( WC()->cart->total );
        if (  !self::validate_minimum_order_amount( $amount ) ) {
            $errors->add( 'validation', sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-payment' ), self::MINIMUM_ORDER_AMOUNT ) );
        }
    }

    /**
     * Validates that the order meets the minimum order amount
     */
    public static function validate_minimum_order_amount( $amount ) {
        if ( WC_Safetypay_Helper::get_amount_redonded( $amount ) < self::MINIMUM_ORDER_AMOUNT ) {
            return true;
        } else {
            return true;
        }
    }

    /**
     * Output payment method type on order admin page
     */
    public static function admin_order_data_after_order_details( $order ) {
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
        echo '<p class="form-field form-field-wide safetypay-payment-method-type"><strong>' . __( 'Payment method type', 'woocommerce-gateway-payment' ) . ':</strong> ' . get_post_meta( $order_id, WC_Safetypay::FIELD_PAYMENT_METHOD_TYPE, true ) . '</p>';
    }
}