<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Function that adds the HTML for payu Standard in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_add_settings_content_payu( $options ) {
    ?>

    <div class="pms-payment-gateway-wrapper">
        <h4 class="pms-payment-gateway-title"><?php echo apply_filters( 'pms_settings_page_payment_gateway_payu_title', __( 'PayU', 'paid-member-subscriptions' ) ); ?></h4>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="payu-client_id"><?php _e( 'PayU client_id', 'paid-member-subscriptions' ); ?></label>
            <input id="payu-client_id" type="text" name="pms_payments_settings[gateways][payu][client_id]" value="<?php echo isset( $options['gateways']['payu']['client_id' ]) ? $options['gateways']['payu']['client_id'] : ''; ?>" class="widefat" />
            <input type="hidden" name="pms_payments_settings[gateways][payu][name]" value="PayU" />
            <p class="description"><?php _e( 'Enter your PayU client_id', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="payu-client_secret"><?php _e( 'PayU client_secret', 'paid-member-subscriptions' ); ?></label>
            <input id="payu-client_secret" type="text" name="pms_payments_settings[gateways][payu][client_secret]" value="<?php echo isset( $options['gateways']['payu']['client_secret' ]) ? $options['gateways']['payu']['client_secret'] : ''; ?>" class="widefat" />
            <p class="description"><?php _e( 'Enter your PayU client_secret', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="payu-pos_id"><?php _e( 'PayU pos_id', 'paid-member-subscriptions' ); ?></label>
            <input id="payu-pos_id" type="text" name="pms_payments_settings[gateways][payu][pos_id]" value="<?php echo isset( $options['gateways']['payu']['pos_id' ]) ? $options['gateways']['payu']['pos_id'] : ''; ?>" class="widefat" />
            <p class="description"><?php _e( 'Enter your PayU pos_id', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="payu-signature_key"><?php _e( 'PayU signature key - Second key (MD5)', 'paid-member-subscriptions' ); ?></label>
            <input id="payu-pos_id" type="text" name="pms_payments_settings[gateways][payu][signature_key]" value="<?php echo isset( $options['gateways']['payu']['signature_key' ]) ? $options['gateways']['payu']['signature_key'] : ''; ?>" class="widefat" />
            <p class="description"><?php _e( 'Enter your PayU signature key - Second key (MD5)', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="pms-form-field-wrapper">
            <label class="pms-form-field-label" for="payu-continue_url"><?php _e( 'PayU "Thank you" page URL address', 'paid-member-subscriptions' ); ?></label>
            <input id="payu-continue_url" type="text" name="pms_payments_settings[gateways][payu][continue_url]" value="<?php echo isset( $options['gateways']['payu']['continue_url' ]) ? $options['gateways']['payu']['continue_url'] : ''; ?>" class="widefat" />
            <p class="description"><?php _e( 'Enter URL for "Thank you page" where client is redirecte after PayU payment', 'paid-member-subscriptions' ); ?></p>
        </div>

        <?php do_action( 'pms_settings_page_payment_gateway_payu_extra_fields', $options ); ?>


    </div>

    <?php
}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_add_settings_content_payu' );


/**
 * Returns the PayU setting
 *
 */
function pms_get_payu_setting($slug) {
    $settings = get_option( 'pms_payments_settings' );

        return $settings['gateways']['payu'][$slug];
        if ( !empty( $settings['gateways']['payu'][$slug] ) )

    return false;
}

/**
 * Returns the PayU client_id
 *
 */
function pms_get_payu_client_id() {
    return pms_get_payu_setting('client_id');
}

/**
 * Returns the PayU client_secret
 *
 */
function pms_get_payu_client_secret() {
    return pms_get_payu_setting('client_secret');
}

/**
 * Returns the PayU pos_id
 *
 */
function pms_get_payu_pos_id() {
    return pms_get_payu_setting('pos_id');
}

/**
 * Returns the PayU token
 *
 */
function pms_get_payu_token() {
    return pms_get_payu_setting('token');
}


/**
 * Returns the PayU pos_id
 *
 */
function pms_get_signature_key() {
    return pms_get_payu_setting('signature_key');
}

/**
* Returns URL where client should be redirected from PAYU after payment. - Thank you page
**/
function pms_get_continue_url() {
    return pms_get_payu_setting('continue_url');
}

/**
 * Add custom log messages for the payu Standard gateway
 *
 */
function pms_payu_payment_logs_system_error_messages( $message, $log ) {

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch( $log['type'] ) {
        case 'payu_to_checkout':
            $message = __( 'User sent to <strong>payu Checkout</strong> to continue the payment process.', 'paid-member-subscriptions' );
            break;
        case 'payu_ipn_waiting':
            $message = __( 'Waiting to receive Instant Payment Notification (IPN) from <strong>payu</strong>.', 'paid-member-subscriptions' );
            break;
        case 'payu_ipn_received':
            $message = __( 'Instant Payment Notification (IPN) received from payu.', 'paid-member-subscriptions' );
            break;
        case 'payu_ipn_not_received':
            $message = __( 'Instant Payment Notification (IPN) not received from payu.', 'paid-member-subscriptions' );
            break;
    }

    return apply_filters( 'pms_payu_payment_logs_system_error_messages', wp_kses( $message, $kses_args ), $log );

}
add_filter( 'pms_payment_logs_system_error_messages', 'pms_payu_payment_logs_system_error_messages', 10, 2 );
