<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
require_once 'openpayu.php'; //PayU SDK

/**
 * Extends the payment gateway base class for payu Standard
 *
 */
Class PMS_Payment_Gateway_PayU_Standard extends PMS_Payment_Gateway_PayU {

    public function init() {
        $this->recurring  = 0;
        $this->supports = apply_filters( 'pms_payment_gateway_payu_supports', array( 'gateway_scheduled_payments' ) );
        $this->pos_id = pms_get_payu_pos_id();
        $this->client_id = pms_get_payu_client_id();
        $this->client_secret = pms_get_payu_client_secret();
        $this->continue_url = pms_get_continue_url();
        $this->signature_key = pms_get_signature_key();
        OpenPayU_Configuration::setOauthTokenCache(new OauthCacheMemcached());
        $this->set_OpenPayU_Configuration();
    }

}
