<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
require_once 'openpayu.php'; //PayU SDK

/**
 * Extends the payment gateway base class for payu Standard
 *
 */
Class PMS_Payment_Gateway_PayU extends PMS_Payment_Gateway {


    /**
     * The features supported by the payment gateway
     *
     * @access public
     * @var array
     *
     */
    public $supports;
    /**
      * @info Authorization data
    */
    public $client_id;
    public $client_secret;
    public $pos_id;
    public $continue_url;
    public $signature_key;
    /**
     * Fires just after constructor
     *
     */
    public function init() {
        //Do I need $this? Yes I do
        $this->supports = apply_filters( 'pms_payment_gateway_payu_supports', array( 'gateway_scheduled_payments' ) );
        $this->pos_id = pms_get_payu_pos_id();
        $this->client_id = pms_get_payu_client_id();
        $this->client_secret = pms_get_payu_client_secret();
        $this->continue_url = pms_get_continue_url();
        $this->signature_key = pms_get_signature_key();
        OpenPayU_Configuration::setOauthTokenCache(new OauthCacheMemcached());
        $this->set_OpenPayU_Configuration();
    }


    public function set_OpenPayU_Configuration(){
      if( pms_is_payment_test_mode() )
          OpenPayU_Configuration::setEnvironment('sandbox');
      else
          OpenPayU_Configuration::setEnvironment('secure');

      OpenPayU_Configuration::setMerchantPosId($this->pos_id);
      OpenPayU_Configuration::setSignatureKey($this->signature_key);

      //set Oauth Client Id and Oauth Client Secret (from merchant admin panel)
      OpenPayU_Configuration::setOauthClientId($this->client_id);
      OpenPayU_Configuration::setOauthClientSecret($this->client_secret);
    }
    /*
     * Process for all register payments that are not free
     *
     */
    public function process_sign_up() {
      // Set the notify URL
      $notify_url = home_url() . '/?pay_gate_listener=payu_ipn&payment_id='.$this->payment_id;

      $settings = get_option( 'pms_payments_settings' );

      //Update payment type
      $payment = pms_get_payment( $this->payment_id );
      $payment->update( array( 'type' => apply_filters( 'pms_payu_payment_type', 'web_accept_payu', $this, $settings ) ) );


      $order['continueUrl'] = $this->continue_url; //customer will be redirected to this page after successfull payment
      $order['notifyUrl'] = $notify_url;
      $order['customerIp'] = $_SERVER['REMOTE_ADDR'];
      $order['merchantPosId'] = $this->pos_id; //OpenPayU_Configuration::getMerchantPosId();
      $order['description'] = $this->subscription_plan->name." ".$this->user_email;
      $order['currencyCode'] = $this->currency;
      $order['totalAmount'] = $this->amount*100;
      $order['extOrderId'] = $this->payment_id; //must be unique!

      $order['products'][0]['name'] = $this->subscription_plan->name;
      $order['products'][0]['unitPrice'] = $this->amount*100;
      $order['products'][0]['quantity'] = 1;

      //optional section buyer
      $order['buyer']['email'] = $this->user_email;
      $order['buyer']['phone'] = '';
      $order['buyer']['firstName'] = $this->user_data['first_name'];
      $order['buyer']['lastName'] = $this->user_data['last_name'];

      try {
          $response = OpenPayU_Order::create($order);
          $status_desc = OpenPayU_Util::statusDesc($response->getStatus());
          if ($response->getStatus() == 'SUCCESS') {
              // echo '<div class="alert alert-success">SUCCESS: ' . $status_desc;
              // echo '</div>';
              //echo '<a href="'.$array["redirectUri"].'">PayU</a>';
              //do_action( 'pms_before_payu_redirect', $array["redirectUri"], $this, $settings );
              $payment->log_data( 'payu_to_checkout' );

              if ( $payment->status != 'completed' && $payment->amount != 0 )
                  $payment->log_data( 'payu_ipn_waiting' );

              wp_redirect( $response->getResponse()->redirectUri );
              exit;
          } else {
              mail('mkopania@gmail.com', 'OpenPayU order creation failed', print_r($response, true));
              echo '<div class="alert alert-warning">' . $response->getStatus() . ': ' . $status_desc;
              echo '</div>';
          }
      } catch (OpenPayU_Exception $e) {
          mail('mkopania@gmail.com', 'OpenPayU_Exception', print_r($e, true));
          echo '<div class="alert alert-warning">';
          var_dump((string)$e);
          echo '</div>';
      }
    }

    //Save old function without SDK
    public function process_sign_up_old() {

        // Do nothing if the payment id wasn't sent
        if( ! $this->payment_id )
            return;

        $settings = get_option( 'pms_payments_settings' );

        //Update payment type
        $payment = pms_get_payment( $this->payment_id );
        $payment->update( array( 'type' => apply_filters( 'pms_payu_payment_type', 'web_accept_payu', $this, $settings ) ) );


        // Set the notify URL
        $notify_url = home_url() . '/?pay_gate_listener=payu_ipn&payment_id='.$this->payment_id;

        if( pms_is_payment_test_mode() )
            $host = "https://secure.snd.payu.com";
        else
            $host = "https://secure.payu.com";

        $url = "/pl/standard/user/oauth/authorize";

        $fields = "grant_type=client_credentials&client_id=".$this->client_id."&client_secret=".$this->client_secret;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $host.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $array = json_decode( $result, true );

        $access_token = $array["access_token"];

        //ORDER

        $data = array(
            "customerIp" =>  $_SERVER['REMOTE_ADDR'],
            "merchantPosId" => $this->pos_id,
            "description" => $this->subscription_plan->name." ".$this->user_email,
            "currencyCode" => $this->currency,
            "totalAmount" => $this->amount*100,
            "extOrderId" => $this->payment_id,
            "notifyUrl" => $notify_url,
            "currencyCode" => $this->currency,
            "continueUrl" => $this->continue_url,
            "buyer" => array(
                "email" => $this->user_email,
                "phone" => "",
                "firstName" => $this->user_data['first_name'],
                "lastName" => $this->user_data['last_name']
            ),
            "products" => array(
                array(
                    "name" => $this->subscription_plan->name,
                    "unitPrice" => $this->amount*100,
                    "quantity" => "1"
                )
            )
        );
        $postdata = json_encode($data);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        $url = "/api/v2_1/orders";
        curl_setopt($ch, CURLOPT_URL, $host.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            exit;
        }
        curl_close($ch);

        //echo $result;

        $array = json_decode( $result, true );
        //echo "<h1> StatusCode: ".$array["status"]["statusCode"]."</h1>";
        if($array["status"]["statusCode"] == "SUCCESS"){
            //echo '<a href="'.$array["redirectUri"].'">PayU</a>';

            //do_action( 'pms_before_payu_redirect', $array["redirectUri"], $this, $settings );

            $payment->log_data( 'payu_to_checkout' );

            if ( $payment->status != 'completed' && $payment->amount != 0 )
                $payment->log_data( 'payu_ipn_waiting' );

            wp_redirect( $array["redirectUri"] );
            exit;
        }else{
            //Authentication failed
            $payment->log_data( 'payu_autentication_failed '.$result );
            print_r($array);
            exit;
        }

    }


    /*
     * Process IPN sent by PayU
     *
     */
    public function process_webhooks() {
        if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'payu_ipn' )
            return;

        mail("mkopania@gmail.com","PAU IPN process_webhooks",'GET: '.print_r($_GET, true).'POST: '.print_r($_POST,true));

        //PAYU verification
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = trim($body);

            // mail("mkopania@gmail.com","BODY",'body: '.print_r($body, true));

            try {
                if (!empty($data)) {
                    $result = OpenPayU_Order::consumeNotification($data);
                    // mail("mkopania@gmail.com","RESULT",'res: '.print_r($result, true));
                }

                if ($result->getResponse()->order->orderId) {

                    /* Check if OrderId exists in Merchant Service, update Order data by OrderRetrieveRequest */
                    $res_orders = OpenPayU_Order::retrieve($result->getResponse()->order->orderId);
                    mail("mkopania@gmail.com","ORDER!",'orders: '.$res_orders->getStatus().print_r($res_orders, true));
                    $order = $result->getResponse()->order;
                    // mail("mkopania@gmail.com","ORDER2",is_null($order) ? "NULL" : (is_null(print_r($order,true)) ? "null2":print_r($order,true) ));

                    if($res_orders->getStatus() == 'SUCCESS'){
                        //the response should be status 200
                        //header("HTTP/1.1 200 OK");
                    } else{
                      mail("mkopania@gmail.com","getStatus() != 'SUCCESS'",'orders: '.$res_orders->getStatus().print_r($res_orders, true));
                      // return;
                    }

                } else {
                  //something strange
                  mail("mkopania@gmail.com","FALSE $result->getResponse()->order->orderId",'result: '.$print_r($result, true));
                  // return;
                }
            } catch (OpenPayU_Exception $e) {
              mail("mkopania@gmail.com","OpenPayU_Exception",'E: '.print_r($e, true));
              //echo $e->getMessage();
              // return;
            } catch (Exception $e) {
              mail("mkopania@gmail.com","Exception",'E: '.print_r($e, true));
              //echo $e->getMessage();
              // return;
            }
            finally {

              // Get payment id from custom variable sent by IPN
              $payment_id = $_GET['payment_id']; //$order->extOrderId in $order

              // Get the payment
              $payment = pms_get_payment( $payment_id );

              mail("mkopania@gmail.com","Payment",'p: '.print_r($payment, true).print_r($order,true));

              // Get user id from the payment
              $user_id = $payment->user_id;

              if (strtolower($order->status) == 'completed') {
                $payment->log_data( 'payu_ipn_received', array( 'data' => $order, 'desc' => 'completed' ) );

                // Complete payment
                $payment->update( array( 'status' => strtolower($order->status), 'transaction_id' => $order->orderId ) );

                // Get member subscription
                $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id, 'subscription_plan_id' => $payment->subscription_id, 'number' => 1 ) );

                foreach( $member_subscriptions as $member_subscription ) {
                    $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );
                    // If subscription is pending it is a new one
                    if( $member_subscription->status == 'pending' ) {
                        $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                        pms_add_member_subscription_log( $member_subscription->id, 'subscription_activated', array( 'until' => $member_subscription_expiration_date ) );
                    // This is an old subscription
                    } else {
                        if( strtotime( $member_subscription->expiration_date ) < time() || $subscription_plan->duration === 0 )
                            $member_subscription_expiration_date = $subscription_plan->get_expiration_date();
                        else
                            $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );

                        pms_add_member_subscription_log( $member_subscription->id, 'subscription_renewed_manually', array( 'until' => $member_subscription_expiration_date ) );
                    }

                    // Update subscription
                    $member_subscription->update( array( 'expiration_date' => $member_subscription_expiration_date, 'status' => 'active' ) );
                }

                /*
                 * If the subscription plan id sent by the IPN is not found in the members subscriptions
                 * then it could be an update to an existing one
                 *
                 * If one of the member subscriptions is in the same group as the payment subscription id,
                 * the payment subscription id is an upgrade to the member subscription one
                 *
                 */

                 $current_subscription = pms_get_current_subscription_from_tier( $user_id, $payment->subscription_id );

                 if( !empty( $current_subscription ) && $current_subscription->subscription_plan_id != $payment->subscription_id ) {
                     $old_plan_id = $current_subscription->subscription_plan_id;
                     $new_subscription_plan = pms_get_subscription_plan( $payment->subscription_id );
                     $subscription_data = array(
                         'user_id'              => $user_id,
                         'subscription_plan_id' => $new_subscription_plan->id,
                         'start_date'           => date( 'Y-m-d H:i:s' ),
                         'expiration_date'      => $new_subscription_plan->get_expiration_date(),
                         'status'               => 'active'
                     );

                     $current_subscription->update( $subscription_data );

                     pms_add_member_subscription_log( $current_subscription->id, 'subscription_upgrade_success', array( 'old_plan' => $old_plan_id, 'new_plan' => $new_subscription_plan->id ) );

                 }

                // If payment status is not complete, something happened, so log it in the payment
              } elseif(strtolower($order->status) == 'pending') {
                    $payment->log_data( 'payment_pending', array( 'data' => $order, 'desc' => $order->status) );
                    // Add the transaction ID
                    $payment->update( array( 'transaction_id' => $order->orderId, 'status' => 'pending' ) );
                }else{
                  $payment->log_data( 'payment_failed', array( 'data' => $order, 'desc' => $order->status) );
                  // Add the transaction ID
                  $payment->update( array( 'transaction_id' => $order->orderId, 'status' => 'failed' ) );
                }
                //the response should be status 200
                header("HTTP/1.1 200 OK");
              }
              mail("mkopania@gmail.com","po try catch",'ffff: ');

        }
    }


    /*
     * Verify that the payment gateway is setup correctly
     *
     */
    public function validate_credentials() {

        if ( pms_get_payu_pos_id() === false )
            pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayU pos_id is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );
        if ( pms_get_payu_client_id() === false )
            pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayU client_id is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );
        if ( pms_get_payu_client_secret() === false )
            pms_errors()->add( 'form_general', __( 'The selected gateway is not configured correctly: <strong>PayU client_secret is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );
//            $this->continue_url = pms_get_continue_url();
        if ( pms_get_signature_key() === false)
            pms_errors()->add( 'form_general', __( 'The selected signature_key is not configured correctly: <strong>PayU signature_key is missing</strong>. Contact the system administrator.', 'paid-member-subscriptions' ) );

    }

}
