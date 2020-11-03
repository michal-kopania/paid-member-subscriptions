<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *  PayU IPN Listener
 *
 *  A class to listen for and handle Instant Payment Notifications (IPN) from
 *  the PayU server.
 *
 *  https://developers.payu.com/pl/restapi.html#notifications
 *
 *  @package    PHP-PayU-IPN
 *  @author     Michał Kopania
 *
 */
class PMS_PayUIpnListener {


    /**
     *  If true, the payU sandbox URI  is used. Default false.
     *
     *  @var boolean
     */
    public $use_sandbox = false;

    /**
     *  The amount of time, in seconds, to wait for the PayPal server to respond
     *  before timing out. Default 30 seconds.
     *
     *  @var int
     */
    public $timeout = 30;

    private $post_data = array();
    private $post_uri = '';
    private $response_status = '';
    private $response = '';

    const PAYU_HOST = 'https://secure.payu.com';
    const SANDBOX_HOST = 'https://secure.snd.payu.com';

    private function getPayUHost() {
        if ($this->use_sandbox) return self::SANDBOX_HOST;
        else return self::PAYPAL_HOST;
    }

    /**
     *  Get POST URI
     *
     *  Returns the URI that was used to send the post back to PayPal. This can
     *  be useful for troubleshooting connection problems. The default URI
     *  would be "ssl://www.sandbox.paypal.com:443/cgi-bin/webscr"
     *
     *  @return string
     */
    public function getPostUri() {
        return $this->post_uri;
    }

    /**
     *  Get Response
     *
     *  Returns the entire response from PayPal as a string including all the
     *  HTTP headers.
     *
     *  @return string
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     *  Get Response Status
     *
     *  Returns the HTTP response status code from PayPal. This should be "200"
     *  if the post back was successful.
     *
     *  @return string
     */
    public function getResponseStatus() {
        return $this->response_status;
    }

}
