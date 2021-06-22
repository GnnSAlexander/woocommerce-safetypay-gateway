<?php
defined( 'ABSPATH' ) || exit;

return apply_filters(
	'wc_payment_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-payment-method' ),
			'label'       => __( 'Enable Payment Method', 'woocommerce-payment-method' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-payment-method' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-payment-method' ),
			'default'     => 'Safetypay',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-payment-method' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-payment-method' ),
			'default'     => __( 'Pay via Payment gateway.', 'woocommerce-payment-method' ),
			'desc_tip'    => true,
		),
		'webhook' => array(
			'title'       => __( 'Webhook Endpoints', 'woocommerce-payment-method' ),
			'type'        => 'title',
			'description' => sprintf( __( 'You must add the following webhook endpoint <strong class="safetypay-webhook-link">&nbsp;%s&nbsp;</strong> to your <a href="https://sandbox-secure.safetypay.com/Merchants/Login.aspx?ReturnUrl=fmerchants" target="_blank">payment account settings</a> for both Production and Sandbox environments.', 'woocommerce-payment-method' ), add_query_arg( 'wc-api', 'wc_safetypay', trailingslashit( get_home_url() ) ) ),
		),
		'testmode' => array(
			'title'       => __( 'Test mode', 'woocommerce-payment-method' ),
			'label'       => __( 'Enable Test Mode', 'woocommerce-payment-method' ),
			'type'        => 'checkbox',
			'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce-payment-method' ),
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'test_public_key' => array(
			'title'       => __( 'Test Api Key', 'woocommerce-payment-method' ),
			'type'        => 'text',
			'description' => __( 'Get your API keys from your Safetypay account.', 'woocommerce-payment-method' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'test_private_key' => array(
			'title'       => __( 'Test Signature Key', 'woocommerce-payment-method' ),
			'type'        => 'password',
			'description' => __( 'Get your API keys from your Safetypay account.', 'woocommerce-payment-method' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'public_key' => array(
			'title'       => __( 'Live Api Key', 'woocommerce-payment-method' ),
			'type'        => 'text',
			'description' => __( 'Get your API keys from your Safetypay account.', 'woocommerce-payment-method' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'private_key' => array(
			'title'       => __( 'Live Signature Key', 'woocommerce-payment-method' ),
			'type'        => 'password',
			'description' => __( 'Get your API keys from your Safetypay account.', 'woocommerce-payment-method' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'return_pages' => array(
			'title'       => __( 'Return pages', 'woocommerce-payment-method' ),
			'type'        => 'title',
			'description' => __( 'you must select the return pages.' ),
		),
    'redirect_page_success_id' => array(
        'title' => __('Return Success Page', 'woocommerce-payment-method'),
        'type' => 'select',
        'options' => WC_Safetypay_Helper::get_pages(__('Select Page', 'woocommerce-payment-method')),
        'description' => __('URL of success page', 'woocommerce-payment-method'),
        'desc_tip' => true
    ),
		'redirect_page_error_id' => array(
			'title' => __('Return Error Page', 'woocommerce-payment-method'),
			'type' => 'select',
			'options' => WC_Safetypay_Helper::get_pages(__('Select Page', 'woocommerce-payment-method')),
			'description' => __('URL of error page', 'woocommerce-payment-method'),
			'desc_tip' => true
	),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-payment-method' ),
			'label'       => __( 'Log debug messages', 'woocommerce-payment-method' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-payment-method' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
