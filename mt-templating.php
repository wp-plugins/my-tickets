<?php
/*
*  Register custom fields for event registration forms
*
*/
require_once( 'includes/phpqrcode/qrlib.php' );

/**
 * Get logo for display on receipts and tickets.
 * @param array $args
 *
 * @return string
 */
function mt_get_logo( $args = array() ) {
	$args = array_merge( array( 'alt' => 'My Tickets', 'class' => 'default', 'width' => '', 'height' => '' ), $args );
	$atts = '';
	foreach ( $args as $att => $value ) {
		if ( $value != '' ) {
			$atts .= ' ' . esc_attr( $att ) . '=' . '"' . esc_attr( $value ) . '"';
		}
	}
	$img = "<img src='" . plugins_url( '/images/logo.png', __FILE__ ) . "' $atts />";

	return $img;
}

function mt_logo( $args = array() ) {
	echo mt_get_logo( $args );
}

/* Template Functions for Receipts */
/**
 * Return formatted order data for receipt template.
 *
 * @return string
 */
function mt_get_cart_order() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchase = get_post_meta( $receipt->ID, '_purchased' );
		$data     = mt_format_purchase( $purchase, 'html' );

		return $data;
	}

	return '';
}

function mt_cart_order() {
	echo mt_get_cart_order();
}

/**
 * Return receipt ID.
 *
 * @return string|void
 */
function mt_get_receipt_id() {
	$receipt_id = esc_attr( $_GET['receipt_id'] );

	return $receipt_id;
}

function mt_receipt_id() {
	echo mt_get_receipt_id();
}

/**
 * Get provided purchaser name from payment.
 *
 * @return string
 */
function mt_get_cart_purchaser() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchaser = get_the_title( $receipt->ID );

		return $purchaser;
	}

	return '';
}

function mt_cart_purchaser() {
	echo mt_get_cart_purchaser();
}

/**
 * Get formatted date/time of purchase.
 *
 * @return string
 */
function mt_get_cart_purchase_date() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$date = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $receipt->post_date ) );

		return $date;
	}

	return '';
}

function mt_cart_purchase_date() {
	echo mt_get_cart_purchase_date();
}

/**
 * Get payment gateway data from payment.
 *
 * @return string|void
 */
function mt_get_payment_details() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$paid = get_post_meta( $receipt->ID, '_is_paid', true );
		if ( $paid == 'Completed' ) {
			$gateway       = get_post_meta( $receipt->ID, '_gateway', true );
			$gateways      = mt_setup_gateways();
			$gateway_label = $gateways[ $gateway ]['label'];
			$transaction   = get_post_meta( $receipt->ID, '_transaction_id', true );
			$return        = __( "This receipt is paid in full.", 'my-tickets' );
			$return .= "
		<ul>
			<li>" . __( 'Payment through:', 'my-tickets' ) . " $gateway_label</li>
			<li>" . __( 'Transaction ID:', 'my-tickets' ) . " <code>$transaction</code></li>
		</ul>";

			return $return;
		} else {
			return __( 'Payment on this purchase is not completed. The receipt will be updated with payment details when payment is completed.', 'my-tickets' );
		}
	}

	return '';
}

function mt_payment_details() {
	echo mt_get_payment_details();
}

/**
 * Get ticket ID (must be used in ticket template.)
 *
 * @return string|void
 */
function mt_get_ticket_id() {
	$ticket_id = esc_attr( $_GET['ticket_id'] );

	return $ticket_id;
}

function mt_ticket_id() {
	echo mt_get_ticket_id();
}

/**
 * Get ticket method (willcall, postal, eticket, printable)
 *
 * @return mixed|string
 */
function mt_get_ticket_method() {
	$purchase    = get_post_meta( mt_get_ticket()->ID, '_'.mt_get_ticket_id(), true );
	$purchase_id = $purchase['purchase_id'];
	$ticket_type = get_post_meta( $purchase_id, '_ticketing_method', true );
	$ticket_type = ( $ticket_type ) ? $ticket_type : 'willcall';

	return $ticket_type;
}

function mt_ticket_method() {
	echo mt_get_ticket_method();
}

/**
 * Get custom field data; all by default, or only a specific field. Display in tickets.
 *
 * @param bool $custom_field
 *
 * @return string
 */
function mt_get_ticket_custom_fields( $custom_field = false ) {
	$purchase    = get_post_meta( mt_get_ticket()->ID, '_'.mt_get_ticket_id(), true );
	$purchase_id = $purchase['purchase_id'];
	return mt_show_custom_data( $purchase_id, $custom_field );
}

function mt_ticket_custom_fields( $custom_field = false ) {
	echo mt_get_ticket_custom_fields( $custom_field );
}

/**
 * Get date of event this ticket is for.
 *
 * @return string
 */
function mt_get_event_date() {
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$event = get_post_meta( $ticket->ID, '_mc_event_data', true );
		$date  = $event['event_begin'];
		$date  = date_i18n( get_option( 'date_format' ), strtotime( $date ) );

		return $date;
	}

	return '';
}

function mt_event_date() {
	echo mt_get_event_date();
}

/**
 * Get title of event this ticket is for.
 *
 * @return string
 */
function mt_get_event_title() {
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$title = apply_filters( 'the_title', $ticket->post_title );

		return $title;
	}

	return '';
}

function mt_event_title() {
	echo mt_get_event_title();
}

/**
 * Get time of event this ticket is for.
 *
 * @return string
 */
function mt_get_event_time() {
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$event = get_post_meta( $ticket->ID, '_mc_event_data', true );
		$time  = $event['event_time'];
		$time  = date_i18n( get_option( 'time_format' ), strtotime( $time ) );

		return $time;
	}

	return '';
}

function mt_event_time() {
	echo mt_get_event_time();
}

/**
 * Get type of ticket. (Adult, child, section 1, section 2, etc.)
 *
 * @return mixed|string
 */
function mt_get_ticket_type() {
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$type = get_post_meta( $ticket->ID, '_'.mt_get_ticket_id(), true );
		$type = $type['type'];

		return $type;
	}

	return '';
}

function mt_ticket_type() {
	echo mt_get_ticket_type();
}

/**
 * Get price paid for ticket.
 *
 * @return string
 */
function mt_get_ticket_price() {
	$append = ': <em>' . __( 'Paid', 'my-tickets' ) . '</em>';
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$data    = get_post_meta( $ticket->ID, '_'.mt_get_ticket_id(), true );
		$receipt = $data['purchase_id'];
		$paid    = get_post_meta( $receipt, '_is_paid', true );
		if ( $paid != 'Completed' ) {
			$append = ': <em>' . __( 'Payment Due', 'my-tickets' ) . '</em>';
		}
		$type = apply_filters( 'mt_money_format', $data['price'] );

		return $type . $append;
	}

	return '';
}

function mt_ticket_price() {
	echo mt_get_ticket_price();
}

// no getter for qrcodes; produces an image directly.
/**
 * Return image URL for printable/eticket QR codes.
 */
function mt_ticket_qrcode() {
	$text = mt_get_ticket_id();
	echo plugins_url( "my-tickets/includes/qrcode.php?mt=$text" );
}

/**
 * Get ticket venue location data.
 *
 * @uses filter mt_create_location_object
 *
 * @return string
 */
function mt_get_ticket_venue() {
	$ticket = mt_get_ticket();
	if ( $ticket ) {
		$location_id = get_post_meta( $ticket->ID, '_mc_event_location', true );
		$html        = false;
		if ( $location_id ) {
			$location = apply_filters( 'mt_create_location_object', false, $location_id );
			if ( ! $location ) {
				return '';
			} else {
				$html = mt_hcard( $location, true );
			}
		}
		$html = apply_filters( 'mt_hcard', $html, $location_id, $ticket );
		if ( $html ) {
			return $html;
		}
	}

	return '';
}

add_filter( 'mt_create_location_object', 'mt_get_mc_location', 10, 2 );
/**
 * If My Calendar installed, return My Calendar location object.
 *
 * @param $location
 * @param $location_id
 *
 * @return mixed
 */
function mt_get_mc_location( $location, $location_id ) {
	if ( function_exists( 'mc_hcard' ) ) {
		global $wpdb;
		$location = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . MY_CALENDAR_LOCATIONS_TABLE . " WHERE location_id = %d", $location_id ) );
	}

	return $location;
}

// set up hCard formatted address.
/**
 * Produce HTML for My Tickets hCard.
 *
 * @param $location
 *
 * @return string
 */
function mt_hcard( $location ) {
	$url     = $location->location_url;
	$label   = stripslashes( $location->location_label );
	$street  = stripslashes( $location->location_street );
	$street2 = stripslashes( $location->location_street2 );
	$city    = stripslashes( $location->location_city );
	$state   = stripslashes( $location->location_state );
	$zip     = stripslashes( $location->location_postcode );
	$country = stripslashes( $location->location_country );
	$phone   = stripslashes( $location->location_phone );
	if ( ! $url && ! $label && ! $street && ! $street2 && ! $city && ! $state && ! $zip && ! $country && ! $phone ) {
		return '';
	}
	$link  = ( $url != '' ) ? "<a href='$url' class='location-link external'>$label</a>" : $label;
	$hcard = "<div class=\"address vcard\">";
	$hcard .= "<div class=\"adr\">";
	$hcard .= ( $label != '' ) ? "<strong class=\"org\">" . $link . "</strong><br />" : '';
	$hcard .= ( $street . $street2 . $city . $state . $zip . $country . $phone == '' ) ? '' : "<div class='sub-address'>";
	$hcard .= ( $street != "" ) ? "<div class=\"street-address\">" . $street . "</div>" : '';
	$hcard .= ( $street2 != "" ) ? "<div class=\"street-address\">" . $street2 . "</div>" : '';
	$hcard .= ( $city . $state . $zip != '' ) ? "<div>" : '';
	$hcard .= ( $city != "" ) ? "<span class=\"locality\">" . $city . "</span><span class='sep'>, </span>" : '';
	$hcard .= ( $state != "" ) ? "<span class=\"region\">" . $state . "</span> " : '';
	$hcard .= ( $zip != "" ) ? " <span class=\"postal-code\">" . $zip . "</span>" : '';
	$hcard .= ( $city . $state . $zip != '' ) ? "</div>" : '';
	$hcard .= ( $country != "" ) ? "<div class=\"country-name\">" . $country . "</div>" : '';
	$hcard .= ( $phone != "" ) ? "<div class=\"tel\">" . $phone . "</div>" : '';
	$hcard .= ( $street . $street2 . $city . $state . $zip . $country . $phone == '' ) ? '' : "</div>";
	$hcard .= "</div>";
	$hcard .= "</div>";

	return $hcard;
}

function mt_ticket_venue() {
	echo mt_get_ticket_venue();
}

// verification
/**
 * Verify that a ticket is valid, paid for, and which event it's for.
 *
 * @return string
 */
function mt_get_verification() {
	$ticket_id = mt_get_ticket_id();
	$verified  = mt_verify_ticket( $ticket_id );
	$ticket    = mt_get_ticket();
	if ( $ticket ) {
		$data        = get_post_meta( $ticket->ID, '_'.$ticket_id, true );
		$purchase_id = $data['purchase_id'];
		$status      = get_post_meta( $purchase_id, '_is_paid', true );
		$due         = get_post_meta( $purchase_id, '_total_paid', true );
		$due         = apply_filters( 'mt_money_format', $due );
		$text        = ( $verified ) ? __( 'Ticket Verified', 'my-tickets' ) : __( 'Invalid Ticket ID', 'my-tickets' );
		$text .= ( $status == 'Pending' ) ? ' - ' . sprintf( __( 'Payment pending: %s', 'my-tickets' ), $due ) : '';
		$status_class = sanitize_title( $status );
		$used         = get_post_meta( $purchase_id, '_tickets_used' );
		if ( !is_array( $used ) ) { $used = array(); }
		$is_used      = false;
		if ( in_array( $ticket_id, $used ) ) {
			$is_used = true;
			$text .= ' (' . __( "Ticket has been used.", 'my-tickets' ) . ')';
		}

		if ( current_user_can( 'mt-verify-ticket' ) || current_user_can( 'manage_options' ) && ! $is_used ) {
			add_post_meta( $purchase_id, '_tickets_used', $ticket_id );
		}

		return "<div class='$status_class'>" . $text . "</div>";
	}

	return '<div class="invalid">' . __( 'Not a valid ticket ID', 'my-tickets' ) . '</div>';
}

function mt_verification() {
	echo mt_get_verification();
}