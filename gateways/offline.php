<?php

add_filter( 'mt_shipping_fields', 'mt_offline_shipping_fields', 10, 2 );
function mt_offline_shipping_fields( $form, $gateway ) {
	if ( $gateway == 'offline' ) {
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

add_filter( 'mt_format_transaction', 'mt_format_offline_transaction', 10, 2 );
function mt_format_offline_transaction( $transaction, $gateway ) {
	if ( $gateway == 'offline' ) {
		// alter return value if desired.
	}

	return $transaction;
}

add_filter( 'mt_gateway', 'mt_gateway_offline', 10, 3 );
function mt_gateway_offline( $form, $gateway, $args ) {
	if ( $gateway == 'offline' ) {
		$options        = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$payment_id     = $args['payment'];
		$handling       = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$total          = $args['total'] + $handling;
		$shipping_price = ( $args['method'] == 'postal' ) ? money_format( '%i', $options['mt_shipping'] ) : 0;
		$currency       = $options['mt_currency'];
		$form           = "
		<form action='" . add_query_arg( 'mt_offline_payment', 'true', get_permalink( $options['mt_purchase_page'] ) ) . "' method='POST'>
		<input type='hidden' name='mt_purchase' value='" . sprintf( __( '%s Order', 'my-tickets' ), get_option( 'blogname' ) ) . "' />
		<input type='hidden' name='mt_item' value='" . esc_attr( $payment_id ) . "' />
		<input type='hidden' name='mt_amount' value='" . esc_attr( $total ) . "' />
		<input type='hidden' name='mt_shipping' value='" . esc_attr( $shipping_price ) . "' />
		<input type='hidden' name='mt_currency' value='" . esc_attr( $currency ) . "' />";
		$form .= mt_render_field( 'address', 'offline' );
		$form .= "<input type='submit' name='submit' class='button' value='" . esc_attr( apply_filters( 'mt_gateway_button_text', __( 'Complete Reservation', 'my-tickets' ), $gateway ) ) . "' />";
		$form .= apply_filters( 'mt_offline_form', '', $gateway, $args );
		$form .= "</form>";
	}

	return $form;
}


add_action( 'mt_receive_ipn', 'mt_offline' );
function mt_offline() {
	if ( isset( $_REQUEST['mt_offline_payment'] ) && $_REQUEST['mt_offline_payment'] == 'true' ) {
		$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$response = 'VERIFIED';
		$response_code = 200;

		// transaction variables to store
		$item_number      = $_POST['mt_item'];
		$price            = 0;
		$payment_currency = $_POST['mt_currency'];
		$txn_id           = 'offline';

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
			'status'         => 'Completed',
			'purchase_id'    => $item_number,
			'shipping'       => $address
		);

		mt_handle_payment( $response, $response_code, $data, $_POST );
		// Everything's all right.
		wp_redirect(  add_query_arg( 'response_code', 'thanks', get_permalink( $options['mt_purchase_page'] ) ) );
	}

	return;
}
