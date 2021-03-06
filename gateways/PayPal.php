<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_action( 'mt_receive_ipn', 'mt_paypal_ipn' );
function mt_paypal_ipn() {
	if ( isset( $_REQUEST['mt_paypal_ipn'] ) && $_REQUEST['mt_paypal_ipn'] == 'true' ) {
		if ( isset( $_POST['payment_status'] ) ) {
			$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
			$receiver = ( isset( $options['mt_paypal_email'] ) ) ? strtolower( $options['mt_paypal_email'] ) : false;
			$url      = ( $options['mt_use_sandbox'] == 'true' ) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

			$req = 'cmd=_notify-validate';
			foreach ( $_POST as $key => $value ) {
				$value = urlencode( stripslashes( $value ) );
				$req .= "&$key=$value";
			}

			$args   = wp_parse_args( $req, array() );
			$params = array(
				'body'      => $args,
				'sslverify' => false,
				'timeout'   => 30,
				'user-agent' => 'WordPress/My Tickets'
			);

			// transaction variables to store
			$payment_status   = $_POST['payment_status'];
			$item_number      = $_POST['item_number'];
			$price            = $_POST['mc_gross'];
			$payment_currency = $_POST['mc_currency'];
			$receiver_email   = $_POST['receiver_email'];
			$payer_email      = $_POST['payer_email'];
			$payer_first_name = $_POST['first_name'];
			$payer_last_name  = $_POST['last_name'];
			$mc_fee           = $_POST['mc_fee'];
			$txn_id           = $_POST['txn_id'];
			$parent           = isset( $_POST['parent_txn_id'] ) ? $_POST['parent_txn_id'] : '';
			// paypal IPN data
			$ipn           = wp_remote_post( $url, $params );

			if ( is_wp_error( $ipn ) ) {
				status_header( 503 ); die;
			}
			$response      = $ipn['body'];
			$response_code = $ipn['response']['code'];

			// map paypal IPN format of address to MT format
			// All gateways must map shipping addresses to this format.
			$address = array(
				'street'  => isset( $_POST['address_street'] ) ? $_POST['address_street'] : '',
				'street2' => isset( $_POST['address2'] ) ? $_POST['address2'] : '',
				'city'    => isset( $_POST['address_city'] ) ? $_POST['address_city'] : '',
				'state'   => isset( $_POST['address_state'] ) ? $_POST['address_state'] : '',
				'country' => isset( $_POST['address_country_code'] ) ? $_POST['address_country_code'] : '',
				'code'    => isset( $_POST['address_zip'] ) ? $_POST['address_zip'] : ''
			);

			$data = array(
				'transaction_id' => $txn_id,
				'price'          => $price,
				'currency'       => $payment_currency,
				'email'          => $payer_email,
				'first_name'     => $payer_first_name,
				'last_name'      => $payer_last_name,
				'fee'            => $mc_fee,
				'parent'         => $parent,
				'status'         => $payment_status,
				'purchase_id'    => $item_number,
				'shipping'       => $address
			);
			// die conditions for PayPal
			// if receiver email or currency are wrong, this is probably a fraudulent transaction.
			// if no receiver email provided, that check will be skipped.
			$value_match = mt_check_payment_amount( $price, $item_number );
			if ( ( $receiver && ( strtolower( $receiver_email ) != $receiver ) ) || $payment_currency != $options['mt_currency'] || !$value_match ) {
				wp_mail( $options['mt_to'], __( 'Payment Conditions Error', 'my-tickets' ), __( "PayPal receiver email did not match account or payment currency did not match payment on $item_number", 'my-tickets' ) . "\n" . print_r( $post, 1 ) . "\n" . print_r( $data, 1 ) );
				status_header( 200 ); // why 200? Because that's the only way to stop PayPal.
				die;
			}
			mt_handle_payment( $response, $response_code, $data, $_POST );
			// Everything's all right.
			status_header( 200 );
		} else {

			if ( isset( $_POST['txn_type'] ) ) {
				// this is a transaction other than a purchase.
				if ( $_POST['case_type'] == 'dispute' ) {
					$posts = get_posts( array( 'post_type'=> 'mt-payments', 'meta_key'=>'_transaction_id', 'meta_value'=>$_POST['txn_id'] ) );
					if ( ! empty ( $posts ) ) {
						$post = $posts[0];
						update_post_meta( $post->ID, '_dispute_reason', $_POST['reason_code'] );
						update_post_meta( $post->ID, '_dispute_message', $_POST['buyer_additional_information'] );
					}

				}
				status_header( 200 );
			}
			status_header( 503 ); die;
		}
	}

	return;
}

add_filter( 'mt_shipping_fields', 'mt_paypal_shipping_fields', 10, 2 );
function mt_paypal_shipping_fields( $form, $gateway ) {
	if ( $gateway == 'paypal' ) {
		$search  = array(
			'mt_shipping_street',
			'mt_shipping_street2',
			'mt_shipping_city',
			'mt_shipping_state',
			'mt_shipping_country',
			'mt_shipping_code'
		);
		$replace = array( 'address1', 'address2', 'city', 'state', 'mt_shipping_country', 'zip' );

		return str_replace( $search, $replace, $form );
	}

	return $form;
}

add_filter( 'mt_format_transaction', 'mt_paypal_transaction', 10, 2 );
function mt_paypal_transaction( $transaction, $gateway ) {
	if ( $gateway == 'paypal' ) {
		// alter return value if desired.
	}

	return $transaction;
}

add_filter( 'mt_setup_gateways', 'mt_setup_paypal', 10, 1 );
function mt_setup_paypal( $gateways ) {
	$gateways['paypal'] = array(
		'label'  => __( 'PayPal', 'my-tickets' ),
		'fields' => array(
			'email'       => __( "PayPal email (primary)", 'my-tickets' ),
			'merchant_id' => __( 'PayPal Merchant ID', 'my-tickets' )
		),
		'note'  => __( 'You need to verify that IPN is enabled in your PayPal account for payments to be handled.', 'my-tickets' )
	);

	return $gateways;
}

add_filter( 'mt_gateway', 'mt_gateway_paypal', 10, 3 );
function mt_gateway_paypal( $form, $gateway, $args ) {
	if ( $gateway == 'paypal' ) {
		$options        = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$payment_id     = $args['payment'];
		$handling       = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$total          = $args['total'] + $handling;
		$shipping       = ( $args['method'] == 'postal' ) ? 2 : 1;
		$shipping_price = ( $args['method'] == 'postal' ) ? money_format( '%i', $options['mt_shipping'] ) : 0;
		$use_sandbox    = $options['mt_use_sandbox'];
		$currency       = $options['mt_currency'];
		$merchant       = $options['mt_gateways']['paypal']['merchant_id'];
		$purchaser      = get_the_title( $payment_id );
		$form           = "
		<form action='" . ( $use_sandbox != 'true' ? "https://www.paypal.com/cgi-bin/webscr" : "https://www.sandbox.paypal.com/cgi-bin/webscr" ) . "' method='POST'>
		<input type='hidden' name='cmd' value='_xclick' />
		<input type='hidden' name='business' value='" . esc_attr( $merchant ) . "' />
		<input type='hidden' name='item_name' value='" . esc_attr( sprintf( __( '%s Order from %s', 'my-tickets' ), get_option( 'blogname' ), $purchaser ) ) . "' />
		<input type='hidden' name='item_number' value='" . esc_attr( $payment_id ) . "' />
		<input type='hidden' name='amount' value='" . esc_attr( $total ) . "' />
		<input type='hidden' name='no_shipping' value='" . esc_attr( $shipping ) . "' />
		<input type='hidden' name='shipping' value='" . esc_attr( $shipping_price ) . "' />
		<input type='hidden' name='no_note' value='1' />
		<input type='hidden' name='currency_code' value='" . esc_attr( $currency ) . "' />";
		$form .= "
		<input type='hidden' name='notify_url' value='" . mt_replace_http( add_query_arg( 'mt_paypal_ipn', 'true', esc_url( home_url() ) . '/' ) ) . "' />
		<input type='hidden' name='return' value='" . mt_replace_http( esc_url( add_query_arg( array(
						'response_code' => 'thanks',
						'gateway'       => 'paypal',
						'payment'       => $payment_id
					), get_permalink( $options['mt_purchase_page'] ) ) ) ) . "' />
		<input type='hidden' name='cancel_return' value='" . mt_replace_http( add_query_arg( 'response_code', 'cancel', esc_url( get_permalink( $options['mt_purchase_page'] ) ) ) ) . "' />";
		/* This might be part of handling discount codes.
		if ( $discount == true && $discount_rate > 0 ) {
			$form .= "
			<input type='hidden' name='discount_rate' value='$discount_rate' />";
			if ( $quantity == 'true' ) {
				$form .= "
				<input type='hidden' name='discount_rate2' value='$discount_rate' />";	
			}
		}
		*/
		$form .= mt_render_field( 'address', 'paypal' );
		$form .= "<input type='submit' name='submit' class='button' value='" . esc_attr( apply_filters( 'mt_gateway_button_text', __( 'Make Payment through PayPal', 'my-tickets' ), $gateway ) ) . "' />";
		$form .= apply_filters( 'mt_paypal_form', '', $gateway, $args );
		$form .= "</form>";
	}

	return $form;
}