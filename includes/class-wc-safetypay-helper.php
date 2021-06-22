<?php
defined( 'ABSPATH' ) || exit;

/**
 * Provides static methods as helpers
 */
class WC_Safetypay_Helper {

    /**
     * Check if current request is webhook
     */
    public static function is_webhook( $log = false) {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_GET['wc-api'] ) && $_GET['wc-api'] === 'wc_safetypay' ) {
            return true;
        } else {
            if ( $log ) {
                WC_Safetypay_Logger::log( 'Webhook checking error' );
            }
            return false;
        }
    }

    /**
     * Get amount in cents
     */
    public static function get_amount_redonded( $amount ) {
        return (int) ( Round($amount) );
    }

    /**
     * Add title of the store 
     */
    public static function set_format_order($order_id){
        $title =  strtolower(str_replace(' ', '', get_bloginfo()));
        return  $title.'-'.$order_id;
    }

    /**
     * Remove title of the store in order_id 
     */
    public static function get_order_id($order_id){
        $parts = explode("-",$order_id);

        if( !isset($parts[1]) ){
            WC_Safetypay_Logger::log( 'The trouble with the order_id : '. $order_id );
        }

        return array('title' => $parts[0], 'id' => $parts[1]);
    }

    /**
     * Get all pages id
     */
    public static function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title)
            $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix .= ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }

    /**
     * Get signature key
     */

    public static function getSignatureKey(){
        return WC_Safetypay::$settings['testmode'] === 'yes' ? WC_Safetypay::$settings['test_private_key'] : WC_Safetypay::$settings['private_key'];
    }


    /**
     * Check signature if is valid
     */
    public static function checkSignature( $string, $requestSignature ){
        $signatureKey = self::getSignatureKey();
        $signature = strtoupper(hash('sha256', $string.$signatureKey));
        WC_Safetypay_Logger::log( 'Signature valid: '.$signature );
        if( $signature === $requestSignature ){
          return true;
        }else{
          return false;
        }
    }

    /**
     * Return csv for the response
     */
    public static function set_csv($response){
        $date = date('Y-m-d\TH:i:s');
        
        $signatureKey = self::getSignatureKey();
    
        $signature = strtoupper(hash('sha256', $date.$response->MerchantSalesID.$response->ReferenceNo.$response->CreationDateTime.$response->Amount.$response->CurrencyID.$response->PaymentReferenceNo.$response->Status.$response->MerchantSalesID.$signatureKey));
    
        return '0,'.$date.','.$response->MerchantSalesID.','.$response->ReferenceNo.','.$response->CreationDateTime.','.$response->Amount.','.$response->CurrencyID.','.$response->PaymentReferenceNo.','.$response->Status.','.$response->MerchantSalesID.','.$signature;
    }
}