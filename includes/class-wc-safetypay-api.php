<?php
defined( 'ABSPATH' ) || exit;

/**
 * Communicates with Safetypay API
 */
class WC_Safetypay_API {

    /**
     * Define API constants
     */
    const ENDPOINT = 'https://mws.safetypay.com/mpi/api/V1';
    const ENDPOINT_TEST = 'https://sandbox-mws.safetypay.com/mpi/api/v1';
    const EVENT_TRANSACTION_UPDATED = '102';
    const STATUS_APPROVED = '102';
    const STATUS_EXPIRED = '100';
    const STATUS_PENDING = '101';
    const PAYMENT_TYPE_CARD = 'CARD';

    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * API endpoint
     */
    private $endpoint = '';

    /**
     * Public API Key
     */
    private $public_key = '';

	/**
	 * Private API Key
	 */
	private $private_key = '';

    /**
     * Instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {

        $options = WC_Safetypay::$settings;

        if ( 'yes' === $options['testmode'] ) {
            $this->endpoint = self::ENDPOINT_TEST;
            $this->public_key = $options['test_public_key'];
            $this->private_key = $options['test_private_key'];
        } else {
            $this->endpoint = self::ENDPOINT;
            $this->public_key = $options['public_key'];
            $this->private_key = $options['private_key'];
        }
    }

    /**
     * Getter
     */
    public function __get( $name ) {
        if ( property_exists( $this, $name ) ) {
            return $this->$name;
        }
    }

	/**
	 * Generates the headers to pass to API request
	 */
    private function get_headers( $use_secret ) {
        $headers = array();

        if ( $use_secret ) {
            $headers['X-Api-Key'] = $this->public_key;
            $headers['X-Version'] = "20200803";
            $headers['Content-Type'] = "application/json";
        }

		return $headers;
	}

	/**
	 * Send the request to Wompi's API
	 */
	public function request( $method, $request, $data = null, $use_secret = false ) {
		WC_Safetypay_Logger::log( "==== REQUEST ============================== Start Log ==== \n REQUEST URL: " . $method . ' ' . $this->endpoint . $request . "\n", false );
		if ( ! is_null( $data ) ) {
      WC_Safetypay_Logger::log( 'REQUEST DATA: ' . print_r( $data, true ), false );
        }

        $headers = $this->get_headers( $use_secret );

		$params = array(
            'method'  => $method,
            'headers' => $headers,
            'body'    => $data,
        );

        // Exclude private key from logs
        if ( 'yes' === WC_Safetypay::$settings['logging'] && ! empty( $headers ) ) {
            $strlen = strlen( $this->private_key );
            $headers['Authorization'] = 'Bearer ' . ( ! empty( $strlen ) ? str_repeat( 'X', $strlen ) : '' );
            WC_Safetypay_Logger::log( 'REQUEST HEADERS: ' . print_r( $headers, true ), false );
        }

		$response = wp_safe_remote_post( $this->endpoint . $request, $params );
        WC_Safetypay_Logger::log( 'REQUEST RESPONSE: ' . print_r( $response, true ), false );

		if ( is_wp_error( $response ) ) {
			return false;
		}

        return json_decode( $response['body'] );
	}

    /**
     * Transaction void
     */
	public function transaction_void( $transaction_id ) {
        $response = $this->request( 'POST', '/transactions/' . $transaction_id . '/void', null, true );
        return $response->data->status == self::STATUS_APPROVED ? true : false;
    }


    /**
     * Get url 
     */
    public function get_payment_requests( $params ) {
      $response = $this->request( 'POST', '/online-payment-requests', wp_json_encode($params), true );
      return $response;
  }
}

WC_Safetypay_API::instance();
