<?php
defined( 'ABSPATH' ) || exit;

/**
 * Webhook Handler Class
 */
class WC_Safetpay_Webhook_Handler {


  public $originalResponse = null;

   //const COMMERCE = array('womynation'=> ' http://ricardob20.sg-host.com/?wc-api=wc_safetypay');
   const COMMERCE = array('daysylatin'=> ' http://ricardob19.sg-host.com/?wc-api=wc_safetypay');

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_api_wc_safetypay', array( $this, 'check_for_webhook' ) );
	}

	/**
	 * Check incoming requests for safetypay Webhook data and process them
	 */
	public function check_for_webhook() {

		if ( ! WC_Safetypay_Helper::is_webhook(true) ) {
			return false;
		}
        $this->originalResponse = file_get_contents('php://input');
        $response =  $this->format_data(  file_get_contents('php://input')  );
        
        $string = $response->RequestDateTime.$response->MerchantSalesID.$response->ReferenceNo.$response->CreationDateTime.$response->Amount.$response->CurrencyID.$response->PaymentReferenceNo.$response->Status;

        if ( $this->redirect($response) ){
          status_header( 200 );
          $this->csv($response);
          die();
        }
        
        if ( WC_Safetypay_Helper::checkSignature($string, $response->Signature) ) {
          //WC_Safetypay_Logger::log( 'Signature request: '.$response->Signature );
          //WC_Safetypay_Logger::log( 'Signature: TRUE' );
          WC_Safetypay_Logger::log( 'Webhook DATA: ' . $this->originalResponse );
          WC_Safetypay_Logger::log( 'Webhook start: ' . print_r( $response, true ) );

            $public_key = WC_Safetypay::$settings['testmode'] === 'yes' ? WC_Safetypay::$settings['test_public_key'] : WC_Safetypay::$settings['public_key'];
            if($response->ApiKey == $public_key){
              
              $this->process_webhook( $response );
            }else{
              WC_Safetypay_Logger::log( 'apikey no es igual' );
            }
            
        } else {
          WC_Safetypay_Logger::log( 'Signature: FALSE' );
          status_header( 400 );
          die();
        }
	}

  public function redirect( $response ){
    $data =  WC_Safetypay_Helper::get_order_id($response->MerchantSalesID);

    WC_Safetypay_Logger::log( 'CONFIG COMMERCE '. print_r( self::COMMERCE, true ), false  );

    if( isset(self::COMMERCE[$data['title']]) ){
      WC_Safetypay_Logger::log( 'La respuesta no es para este sitio '.get_bloginfo().' ==> '. $data['title']  );

      $response = wp_remote_post( self::COMMERCE[$data['title']], array('method'  => 'POST','headers' => array(), 'body'    => $this->originalResponse) );
      WC_Safetypay_Logger::log( 'REQUEST RESPONSE: ' . print_r( $response, true ), false );
      return true;
    }else{
      WC_Safetypay_Logger::log( 'La respuesta es para este sitio '.get_bloginfo().' ==> '. $data['title']  );
      return false;
    }
  }

  public function csv($data){
    if($data->Status == WC_Safetypay_API::EVENT_TRANSACTION_UPDATED){
      $resp = WC_Safetypay_Helper::set_csv($data);
      WC_Safetypay_Logger::log( 'Response for the api : '. $resp );
      echo $resp;
    }
  }

  public function format_data($response){
    $data = str_getcsv($response,"&","=");

    $dict = array();
    
    foreach($data as $item){
      $part = explode("=", $item);
      if(!isset($part[1])) return null;
      $dict[$part[0]] = $part[1];
    }

    return (object)$dict;
  }

	/**
	 * Processes the incoming webhook
	 */
	public function process_webhook( $response ) {
        // Check transaction event
		switch ( $response->Status ) {
      case WC_Safetypay_API::EVENT_TRANSACTION_UPDATED:
				$this->process_webhook_payment( $response );
				break;
      default :
          WC_Safetypay_Logger::log( 'TRANSACTION Event Not Found' );
          status_header( 400 );
		}
	}

    /**
     * Process the payment
     */
	public function process_webhook_payment( $response ) {
        // Validate transaction response
        if ( isset( $response->MerchantSalesID ) ) {
            $transaction = $response;
            $data =  WC_Safetypay_Helper::get_order_id($response->MerchantSalesID);
            $order = new WC_Order( $data['id'] );
            if( $this->is_payment_valid( $order, $transaction ) ) {
                // Update order data
                $this->update_order_data( $order, $transaction );
                $this->apply_status( $order, $transaction );
                status_header( 200 );
                $this->csv($response);
                die();
            } else {
                $this->update_transaction_status( $order, __('SAFETYPAY payment validation is invalid. TRANSACTION ID: ', 'woocommerce-payment-method') . ' (' . $transaction->PaymentReferenceNo . ')', 'failed' );
                status_header( 400 );
            }
        } else {
          WC_Safetypay_Logger::log( 'TRANSACTION Response Not Found' );
            status_header( 400 );
        }
    }

    /**
     * Validate transaction response
     */
    protected function is_payment_valid( $order, $transaction ) {
        if ( $order === false ) {
          WC_Safetypay_Logger::log( 'Order Not Found' . ' TRANSACTION ID: ' . $transaction->MerchantSalesID );
            return false;
        }

        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        if ( $order->get_payment_method() != 'safetypay' ) {
          WC_Safetypay_Logger::log( 'Payment method incorrect' . ' TRANSACTION ID: ' . $transaction->PaymentReferenceNo . ' ORDER ID: ' . $order_id . ' PAYMENT METHOD: ' . $order->get_payment_method() );
            return false;
        }

        //$amount = WC_Safetypay_Helper::get_amount_redonded( $order->get_total() );
        $amount = $order->get_total();
        if ( $transaction->Amount != $amount ) {
          WC_Safetypay_Logger::log( 'Amount incorrect' . ' TRANSACTION ID: ' . $transaction->PaymentReferenceNo . ' ORDER ID: ' . $order_id . ' AMOUNT: ' . $amount );
            return false;
        }

        return true;
    }

    /**
     * Apply transaction status
     */
    public function apply_status( $order, $transaction ) {
        switch ( $transaction->Status ) {
            case WC_Safetypay_API::STATUS_APPROVED:
                $order->payment_complete( $transaction->PaymentReferenceNo );
                $this->update_transaction_status( $order, __('Safetypay payment APPROVED. TRANSACTION ID: ', 'woocommerce-payment-method') . ' (' . $transaction->PaymentReferenceNo . ')', 'completed' );
                break;
            case WC_Safetypay_API::STATUS_PENDING:
                WC_Gateway_Safetypay::process_void( $order );
                $this->update_transaction_status( $order, __('Safetypay payment VOIDED. TRANSACTION ID: ', 'woocommerce-payment-method') . ' (' . $transaction->PaymentReferenceNo . ')', 'pending' );
                break;
            case WC_Safetypay_API::STATUS_EXPIRED:
                $this->update_transaction_status( $order, __('Safetypay payment DECLINED. TRANSACTION ID: ', 'woocommerce-payment-method') . ' (' . $transaction->PaymentReferenceNo . ')', 'cancelled' );
                break;
            default : // ERROR
                $this->update_transaction_status( $order, __('Safetypay payment ERROR. TRANSACTION ID: ', 'woocommerce-payment-method') . ' (' . $transaction->PaymentReferenceNo . ')', 'failed' );
        }
    }

    /**
     * Update order data
     */
    public function update_order_data( $order, $transaction ) {

        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

        // Check if order data was set
        if ( ! $order->get_transaction_id() ) {
            // Set transaction id
            update_post_meta( $order_id, '_transaction_id', $transaction->PaymentReferenceNo );
            // Set payment method type
            //update_post_meta( $order_id, WC_Safetypay::FIELD_PAYMENT_METHOD_TYPE, $transaction->payment_method_type );
            update_post_meta( $order_id, WC_Safetypay::FIELD_PAYMENT_METHOD_TYPE, 'SAFETYPAY' );
            // Set customer email
						/*if ( ! $order->get_billing_email() ) {
							update_post_meta( $order_id, '_billing_email', $transaction->customer_email );
							update_post_meta( $order_id, '_billing_address_index', $transaction->customer_email );
						}
            // Set first name
						if ( ! $order->get_billing_first_name() ) {
							update_post_meta( $order_id, '_billing_first_name', $transaction->customer_data->full_name );
						}
            // Set last name
						if ( ! $order->get_billing_last_name() ) {
							update_post_meta( $order_id, '_billing_last_name', '' );
						}
            // Set phone number
						if ( ! $order->get_billing_phone() ) {
							update_post_meta( $order_id, '_billing_phone', $transaction->customer_data->phone_number );
						}*/
        }
    }

    /**
     * Update transaction status
     */
    public function update_transaction_status( $order, $note, $status ) {
        $order->add_order_note( $note );
				$status = apply_filters( 'wc_safetypay_order_status', $status, $order );
				if ( $status ) {
					$order->update_status( $status );
				}
    }
}

new WC_Safetpay_Webhook_Handler();
