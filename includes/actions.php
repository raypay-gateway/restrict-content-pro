<?php
/**
 * Actions.
 *
 * @package RCP_RayPay
 * @since 1.0
 */

/**
 * Creates a payment record remotely and redirects
 * the user to the proper page.
 *
 * @param array|object $subscription_data
 * @return void
 */
function rcp_raypay_create_payment( $subscription_data ) {
    global $rcp_options;

//    $new_subscription_id = get_user_meta( $subscription_data['user_id'], 'rcp_subscription_level', true );
//    if ( ! empty( $new_subscription_id ) ) {
//        update_user_meta( $subscription_data['user_id'], 'rcp_subscription_level_new', $new_subscription_id );
//    }
//
//    $old_subscription_id = get_user_meta( $subscription_data['user_id'], 'rcp_subscription_level_old', true );
//    update_user_meta( $subscription_data['user_id'], 'rcp_subscription_level', $old_subscription_id );

    // Start the output buffering.
    ob_start();

    $amount = str_replace( ',', '', $subscription_data['price'] );

    // Check if the currency is in Toman.
    if ( in_array( $rcp_options['currency'], array(
        'irt',
        'IRT',
        'تومان',
        __( 'تومان', 'rcp' ),
    ) ) ) {
        $amount = $amount * 10;
    }

    // Send the request to RayPay.
    $user_id = isset( $rcp_options['raypay_user_id'] ) ? $rcp_options['raypay_user_id'] : wp_die( __( 'RayPay User ID is missing' ) );
    $acceptor_code = isset( $rcp_options['raypay_acceptor_code'] ) ? $rcp_options['raypay_acceptor_code'] : wp_die( __( 'RayPay Acceptor Code is missing' ) );
    $invoice_id = round(microtime(true)*1000) ;
    $callback = add_query_arg( 'gateway', 'raypay-for-rcp', $subscription_data['return_url'] );
    $callback .= "&order_id=" . $subscription_data['payment_id'] . '&';

    $data = array(
        'amount' => strval($amount),
        'invoiceID' => strval($invoice_id),
        'userID' => $user_id,
        'redirectUrl' => $callback,
        'factorNumber' => strval($subscription_data['payment_id']),
        'acceptorCode' => $acceptor_code,
        'email' => $subscription_data['user_email'],
        'fullName' => $subscription_data['user_name'],
        'comment' => "{$subscription_data['subscription_name']} - {$subscription_data['key']}"
    );

    $headers = array(
        'Content-Type' => 'application/json',
    );

    $args = array(
        'body' => json_encode($data),
        'headers' => $headers,
        'timeout' => 15,
    );



    $response = rcp_raypay_call_gateway_endpoint( 'http://185.165.118.211:14000/raypay/api/v1/Payment/getPaymentTokenWithUserID', $args );
    if ( is_wp_error( $response ) ) {
		rcp_errors()->add( 'raypay_error', __( 'An error occurred while creating the transaction.' ) , 'register' );
		return;
	}

    $http_status	= wp_remote_retrieve_response_code( $response );
    $result			= wp_remote_retrieve_body( $response );
    $result			= json_decode( $result );



    if ( $http_status != 200 || empty($result) || empty($result->Data) ) {
		rcp_errors()->add( 'raypay_error', __( 'An error occurred while creating the transaction.' ), 'register' );
		return;
    }

    // Update invoice id into payment
    $rcp_payments = new RCP_Payments();
    $rcp_payments->update( $subscription_data['payment_id'], array( 'invoice_id' => $invoice_id ) );

    $access_token = $result->Data->Accesstoken;
    $terminal_id = $result->Data->TerminalID;

    ob_end_clean();

    ryapay_rcp_send_data_shaparak($access_token , $terminal_id);

    exit;
}

add_action( 'rcp_gateway_raypay', 'rcp_raypay_create_payment' );

/**
 * Verify the payment when returning from the IPG.
 *
 * @return void
 */
function rcp_raypay_verify() {

    if ( ! isset( $_GET['gateway'] ) )
        return;

    if ( ! class_exists( 'RCP_Payments' ) )
        return;

    if ( 'raypay-for-rcp' !== sanitize_text_field( $_GET['gateway'] ) )
        return;

    global $rcp_options, $wpdb, $rcp_payments_db_name;

    $order_id = sanitize_text_field($_GET['order_id']);
    $invoice_id = sanitize_text_field($_GET['?invoiceID']);

    if( empty($order_id) || empty($invoice_id) ){
        return;
    }

    $rcp_payments = new RCP_Payments();
    $payment_data = $rcp_payments->get_payment($order_id);

    error_log("payment_data");

    if ( empty( $payment_data ) )
        return;

    $user_id = intval( $payment_data->user_id );
    $subscription_name = $payment_data->subscription;

    if ( $payment_data->status != 'pending' ||  $payment_data->gateway != 'raypay'  || $payment_data->id != $order_id ) {
        return;
    }

        rcp_raypay_check_verification( $order_id );
    $data = array(
        'order_id' => $order_id,
    );

    $headers = array(
        'Content-Type' => 'application/json',
    );

    $args = array(
        'body' => json_encode($data),
        'headers' => $headers,
        'timeout' => 15,
    );

        $response = rcp_raypay_call_gateway_endpoint( 'http://185.165.118.211:14000/raypay/api/v1/Payment/checkInvoice?pInvoiceID=' . $invoice_id, $args );
        if ( is_wp_error( $response ) ) {
            wp_die(  __( 'An error occurred while verifying the transaction.' )  );
        }

        $http_status	= wp_remote_retrieve_response_code( $response );
        $result			= wp_remote_retrieve_body( $response );
        $result			= json_decode( $result );

        $fault = '';

        $state = $result->Data->State;
        $verify_amount = $result->Data->Amount;

        if ( 200 !== $http_status ) {
            $status = 'failed';
            $fault = $result->Message;
        }
        else {
            if ( $state === 1 ) {
                $status = 'complete';

                $payment_data = array(
                    'date'				=> date( 'Y-m-d g:i:s' ),
                    'subscription'		=> $subscription_name,
                    'payment_type'		=> 'raypay',
                    'gateway'           => 'raypay',
                    'subscription_key'	=> $payment_data->subscription_key,
                    'amount'			=> $verify_amount,
                    'user_id'			=> $user_id,
                    'transaction_id'	=> $invoice_id,
                );

                $rcp_payments = new RCP_Payments();
                $payment_id = $rcp_payments->insert( $payment_data );
                $rcp_payments->update( $order_id, array( 'status' => 'complete' ) );
                rcp_raypay_set_verification( $payment_id, $invoice_id );

//                $new_subscription_id = get_user_meta( $user_id, 'rcp_subscription_level_new', true );
//                if ( ! empty( $new_subscription_id ) ) {
//                    update_user_meta( $user_id, 'rcp_subscription_level', $new_subscription_id );
//                }

                rcp_set_status( $user_id, 'active' );

                if ( version_compare( RCP_PLUGIN_VERSION, '2.1.0', '<' ) ) {
                    rcp_email_subscription_status( $user_id, 'active' );
                    if ( ! isset( $rcp_options['disable_new_user_notices'] ) ) {
                        wp_new_user_notification( $user_id );
                    }
                }

//                update_user_meta( $user_id, 'rcp_payment_profile_id', $user_id );
//
//                update_user_meta( $user_id, 'rcp_signup_method', 'live' );
//                update_user_meta( $user_id, 'rcp_recurring', 'no' );

                $subscription          = rcp_get_subscription_details( rcp_get_subscription_id( $user_id ) );
                $member_new_expiration = date( 'Y-m-d H:i:s', strtotime( '+' . $subscription->duration . ' ' . $subscription->duration_unit . ' 23:59:59' ) );
                rcp_set_expiration_date( $user_id, $member_new_expiration );
                delete_user_meta( $user_id, '_rcp_expired_email_sent' );

                $log_data = array(
                    'post_title'   => __( 'Payment complete', 'raypay-for-rcp' ),
                    'post_content' => __( 'RayPay Invoice ID: ', 'raypay-for-rcp' ) . $invoice_id,
                    'post_parent'  => 0,
                    'log_type'     => 'gateway_error'
                );

                $log_meta = array(
                    'user_subscription' => $subscription_name,
                    'user_id'           => $user_id
                );

                WP_Logging::insert_log( $log_data, $log_meta );
            }
            else {
                $status = 'failed';
                $fault = __('Payment was unsuccessful.', 'raypay-for-rcp' );
            }
        }


    if ( 'failed' === $status ) {

        $rcp_payments = new RCP_Payments();
        $rcp_payments->update( $order_id, array( 'status' => $status ) );

        $log_data = array(
            'post_title'   => __( 'Payment failed', 'raypay-for-rcp' ),
            'post_content' => __( 'Transaction did not succeed due to following reason:', 'raypay-for-rcp' ) . $fault,
            'post_parent'  => 0,
            'log_type'     => 'gateway_error'
        );

        $log_meta = array(
            'user_subscription' => $subscription_name,
            'user_id'           => $user_id
        );

        WP_Logging::insert_log( $log_data, $log_meta );
    }

    add_filter( 'the_content', function( $content ) use( $status, $fault ) {
        $message = '<style>
            .raypay-rcp-success {
                background: #4CAF50;
                padding: 15px;
                color: #fff;
                text-align: center;
            }
            .raypay-rcp-error {
                background: #F44336;
                padding: 15px;
                color: #fff;
                text-align: center;
            }
        </style>';

        if ( $status == 'complete' ) {
            $message .= '<br><div class="raypay-rcp-success">' . __( 'Payment was successful.', 'raypay-for-rcp' )  . '</div>';
        }
        if ( $status == 'failed' ) {
            $message .= '<br><div class="raypay-rcp-error">' . __( 'Payment failed due to the following reason: ', 'raypay-for-rcp' ) . $fault . '<br>' . '</div>';
        }

        return $message . $content;
    } );
}

add_action( 'init', 'rcp_raypay_verify' );

/**
 * Change a user status to expired instead of cancelled.
 *
 * @param string $status
 * @param int $user_id
 * @return boolean
 */
function rcp_raypay_change_cancelled_to_expired( $status, $user_id ) {
    if ( 'cancelled' == $status ) {
        rcp_set_status( $user_id, 'expired' );
    }

    return true;
}

add_action( 'rcp_set_status', 'rcp_raypay_change_cancelled_to_expired', 10, 2 );


function rcp_raypay_process_registration() {
	// check nonce
	if ( !isset( $_POST["rcp_register_nonce"] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['rcp_register_nonce'], 'rcp-register-nonce' ) ) {
		$error = '<span style="font-size: 1.6rem;color: #f44336;" class="">'.  __( 'Error in form parameters. the page needs to be reloaded.', 'raypay-for-rcp' ) .'  </span><hr>';
		$script = '<script type="text/javascript"> setTimeout(function() { top.location.href = "' . $_SERVER["HTTP_REFERER"] . '" }, 1000); </script>';

		wp_send_json_error( array(
			'success'          => false,
			'errors'           => $error . $script,
		) );
	}
}

add_action( 'wp_ajax_rcp_process_register_form', 'rcp_raypay_process_registration' , 100 );
