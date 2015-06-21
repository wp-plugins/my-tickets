<?php

/* Functions related to My Tickets Shopping Cart */

add_filter( 'the_content', 'my_tickets_cart', 20, 2 );
/**
 * Display My Tickets shopping cart on purchase page.
 *
 * @param $content
 *
 * @return string
 */
function my_tickets_cart( $content ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$id      = ( $options['mt_purchase_page'] != '' ) ? $options['mt_purchase_page'] : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		// by default, any page content is appended after the cart. This can be changed.
		$content_before = apply_filters( 'mt_content_before_cart', '' );
		$content_after  = apply_filters( 'mt_content_after_cart', $content );
		$cart           = mt_generate_cart();
		$content        = $content_before . $cart . $content_after;
	}

	return $content;
}

add_action( 'init', 'mt_handle_response_message' );
/**
 * Delete cart data if payment response message is 'thank you'.
 *
 */
function mt_handle_response_message() {
	// if we've got a thank you message, we don't need this cart any more.
	if ( isset( $_GET['response_code'] ) && $_GET['response_code'] == 'thanks' ) {
		mt_delete_data( 'cart' );
		mt_delete_data( 'payment' );
	}
}

add_filter( 'mt_content_before_cart', 'mt_response_messages' );
/**
 * Display response messages from cart on purchase page.
 *
 * @return string|void
 */
function mt_response_messages() {
	$message = $response_code = '';
	if ( isset( $_GET['response_code'] ) ) {
		$response_code = $_GET['response_code'];
		if ( $_GET['response_code'] == 'cancel' ) {
			$message = __( "We're sorry you were unable to complete your purchase! Please contact us if you had any issues in the purchase process.", 'my-tickets' );
		}
		if ( $_GET['response_code'] == 'thanks' ) {
			$message = __( "Thanks for your purchase!", 'my-tickets' );
		}
		if ( $_GET['response_code'] == 'required-fields' ) {
			$message = __( "First name, last name, and email are required fields. Please fill in these fields and submit again!", 'my-tickets' );
		}
		if ( !$message ) {
			$message = sanitize_text_field( $_GET['response_reason_text'] );
		}
		return apply_filters( 'mt_response_messages', $message, $response_code );
	}

	return $message;

}

add_filter( 'mt_response_messages', 'mt_wrap_response_messages', 20, 2 );
/**
 * Generate filterable HTML to wrap response messages.
 *
 * @param $message
 * @param $code
 *
 * @return string
 */
function mt_wrap_response_messages( $message, $code ) {
	return "<p class='mt-message error-" . esc_attr( $code ) . "'>$message</p>";
}

/**
 * Check whether cart includes any items that disallow shipping tickets.
 *
 * @param $cart
 *
 * @return bool
 */
function mt_cart_no_postal( $cart ) {
	foreach ( $cart as $event => $data ) {
		$prices       = mt_get_prices( $event );
		if ( is_array( $data ) ) {
			foreach ( $data as $type => $count ) {
				if ( $count > 0 ) {
					if ( isset( $prices[ $type ] ) ) {
						if ( mt_no_postal( $event ) && ! mt_expired( $event ) ) {
							return true;
						}
					}
				}
			}
		}
	}

	return false;
}

/**
 * Incorporated basic required fields into cart data. Name, Email, ticket type selector.
 *
 * @param $cart
 *
 * @return mixed|string|void
 */
function mt_required_fields( $cart ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$output  = mt_render_field( 'name' );
	$output .= mt_render_field( 'email' );
	$output .= ( isset( $options['mt_phone'] ) && $options['mt_phone'] == 'on' ) ? mt_render_field( 'phone' ) : '';
	$opt_types = $options['mt_ticketing'];
	if ( isset( $opt_types['postal'] ) ) {
		$no_postal = mt_cart_no_postal( $cart );
		if ( $no_postal ) {
			unset( $opt_types['postal'] );
		}
	}
	$types = array_keys( $opt_types );
	if ( count( $types ) == 1 ) {
		foreach ( $types as $type ) {
			$output .= mt_render_type( $type );
		}
	} else {
		$output .= mt_render_types( $types );
	}

	return $output;
}

/**
 * Test whether user is logged in and whether user registration is allowed. Return invitation to log-in or register.
 *
 * @return string|void
 */
function mt_invite_login_or_register() {
	if ( ! is_user_logged_in() ) {
		$login = apply_filters( 'mt_login_html', "<a href='" . wp_login_url() . "'>" . __( 'Log in', 'my-tickets' ) . "</a>" );
		if ( get_option( 'users_can_register' ) == '1' ) {
			$register = apply_filters( 'mt_register_html', "<a href='" . wp_registration_url() . "'>" . __( 'Create an account', 'my-tickets' ) . "</a>" );
		} else {
			$register = '';
		}
		if ( $register != '' ) {
			$text = wpautop( sprintf( __( '%1$s or %2$s', 'my-tickets' ), $login, $register ) );
		} else {
			$text = wpautop( sprintf( __( '%1$s now!', 'my-tickets' ), $login, $register ) );
		}

		return apply_filters( 'mt_invite_login_or_register', "<div class='mt-invite-login-or-register'>$text</div>" );
	}

	return '';
}

/**
 * if multiple types are available, allow to choose whether or not to use an e-ticket. Notify of multiple methods that are available.
 *
 * @param $types
 *
 * @return string
 */
function mt_render_types( $types ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$ticketing = $options['mt_ticketing'];
	$output    = '<p><label for="ticketing_method">' . __( 'Ticket Type', 'my-tickets' ) . '</label> <select name="ticketing_method" id="ticketing_method">';
	foreach ( $ticketing as $key => $method ) {
		if ( in_array( $key, $types ) ) {
			$output .= "<option value='$key'>$method</option>";
		}
	}
	$output .= "</select></p>";

	return $output;
}

/**
 * Display notice informing purchaser of the format that their ticket will be delivered in.
 *
 * @param $type
 *
 * @return string
 */
function mt_render_type( $type ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	switch ( $type ) {
		case 'eticket' :
			$return = __( 'Your ticket will be delivered as an e-ticket. You will receive a link in your email.', 'my-tickets' );
			break;
		case 'printable' :
			$return = __( 'Your ticket will be provided for printing after purchase. You will receive a link to the ticket in your email. Please print your ticket and bring it with you to the event.', 'my-tickets' );
			break;
		case 'postal' :
			$return = sprintf( __( 'Your ticket will be sent to you by mail. You should receive your ticket within %s days, after payment is completed.', 'my-tickets' ), $options['mt_shipping_time'] );
			break;
		case 'willcall' :
			$return = __( 'Your ticket will be under your name at the box office. Please arrive early to allow time to pick up your ticket.', 'my-tickets' );
			break;
		default:
			$return = '';
	}

	return "<input type='hidden' name='ticketing_method' value='$type' />" . apply_filters( 'mt_render_ticket_type_message', "<p class='ticket-type-message'>" . $return . "</p>", $type );
}

/**
 * Display input field on cart screen. (Pre Confirmation)
 *
 * @param $field
 * @param bool $argument
 *
 * @return mixed|void
 */
function mt_render_field( $field, $argument = false ) {
	global $current_user;
	get_currentuserinfo();
	$output   = '';
	$defaults = array(
		'street'  => '',
		'street2' => '',
		'city'    => '',
		'state'   => '',
		'country' => '',
		'code'    => ''
	);
	switch ( $field ) {
		case 'address' :
			// only show shipping fields if postal ticketing in use.
			if ( isset( $_POST['ticketing_method'] ) && $_POST['ticketing_method'] == 'postal' ) {
				$user_address = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, '_mt_shipping_address', true ) : $defaults;
				if ( get_user_meta( $current_user->ID, '_mt_shipping_address', true ) ) {
					$save_address_label = __( 'Update Address', 'my-tickets' );
				} else {
					$save_address_label = __( 'Save Address', 'my-tickets' );
				}
				$save_address = ( is_user_logged_in() ) ? '<p><a href="#" class="mt_save_shipping">' . $save_address_label . "<span class='mt-processing'><img src='" . admin_url( 'images/spinner-2x.gif' ) . "' alt='" . __( 'Working', 'my-tickets' ) . "' /></span></a></p>" : '';
				$address      = ( isset( $_POST['mt_shipping']['address'] ) ) ? $_POST['mt_shipping']['address'] : (array) $user_address;
				$address      = array_merge( $defaults, $address );
				$output       = '
				<fieldset class="mt-shipping-address">
					<legend>' . __( 'Shipping Address', 'my-tickets' ) . '</legend>
					<p>
						<label for="mt_address_street">' . __( 'Street', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_street" id="mt_address_street" class="mt_street" value="' . esc_attr( stripslashes( $address['street'] ) ) . '" />
					</p>
					<p>
						<label for="mt_address_street2">' . __( 'Street (2)', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_street2" id="mt_address_street2" class="mt_street2" value="' . esc_attr( stripslashes( $address['street2'] ) ) . '" />
					</p>
					<p>
						<label for="mt_address_city">' . __( 'City', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_city" id="mt_address_city" class="mt_city" value="' . esc_attr( stripslashes( $address['city'] ) ) . '" />
					</p>
					<p>
						<label for="mt_address_state">' . __( 'State', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_state" id="mt_address_state" class="mt_state" value="' . esc_attr( stripslashes( $address['state'] ) ) . '" />
					</p>
					<p>
						<label for="mt_address_code">' . __( 'Postal Code', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_code" size="10" id="mt_address_code" class="mt_code" value="' . esc_attr( stripslashes( $address['code'] ) ) . '" />
					</p>					
					<p>
						<label for="mt_address_country">' . __( 'Country', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_country" id="mt_address_country" class="mt_country" value="' . esc_attr( stripslashes( $address['country'] ) ) . '" />
					</p>' .
				                $save_address . '
					<div class="mt-response" aria-live="assertive"></div>
				</fieldset>';
			}
			$output = apply_filters( 'mt_shipping_fields', $output, $argument );
			break;
		case 'name' :
			$user_fname = ( is_user_logged_in() ) ? $current_user->user_firstname : '';
			$user_lname = ( is_user_logged_in() ) ? $current_user->user_lastname : '';
			$fname      = ( isset( $_POST['mt_fname'] ) ) ? $_POST['mt_fname'] : $user_fname;
			$lname      = ( isset( $_POST['mt_lname'] ) ) ? $_POST['mt_lname'] : $user_lname;
			$output     = '<p><label for="mt_fname">' . __( 'First Name (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_fname" id="mt_fname" value="' . esc_attr( stripslashes( $fname ) ) . '" required aria-required="true" /> <label for="mt_lname">' . __( 'Last Name (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_lname" id="mt_lname" value="' . esc_attr( stripslashes( $lname ) ) . '" required aria-required="true" /></p>';
			break;
		case 'email' :
			$user_email = ( is_user_logged_in() ) ? $current_user->user_email : '';
			$email      = ( isset( $_POST['mt_email'] ) ) ? $_POST['mt_email'] : $user_email;
			$output     = '<p><label for="mt_email">' . __( 'E-mail (required)', 'my-tickets' ) . '</label> <input type="email" name="mt_email" id="mt_email" value="' . esc_attr( stripslashes( $email ) ) . '" required aria-required="true"  /></p>';
			break;
		case 'phone' :
			$user_phone = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, 'mt_phone', true ) : '';
			$phone      = ( isset( $_POST['mt_phone'] ) ) ? $_POST['mt_phone'] : $user_phone;
			$output     = '<p><label for="mt_phone">' . __( 'Phone (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_phone" id="mt_phone" value="' . esc_attr( stripslashes( $phone ) ) . '" required aria-required="true"  /></p>';
			break;
	}

	return apply_filters( 'mt_render_field', $output, $field );
}

/**
 * Generate selector for choosing payment gateway.
 *
 * @return string
 */
function mt_gateways() {
	$selector = '';
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$enabled  = $options['mt_gateway'];
	$url      = get_permalink( $options['mt_purchase_page'] );
	if ( count( $enabled ) == 1 ) {
		return '';
	} else {
		$labels = mt_setup_gateways();
		foreach ( $enabled as $gate ) {
			$current_gate = ( isset( $_GET['mt_gateway'] ) && in_array( $_GET['mt_gateway'], $enabled ) ) ? $_GET['mt_gateway'] : $options['mt_default_gateway'];
			if ( isset( $labels[ $gate ] ) ) {
				$checked      = ( $gate == $current_gate ) ? ' class="active"' : '';
				$label        = $labels[ $gate ]['label'];
				$selector .= "<li$checked><a href='$url?mt_gateway=$gate' data-assign='$gate'>$label</a></li>";
			}
		}

		return "<div class='gateway-selector'><ul><li>" . __( 'Payment Gateway', 'my-tickets' ) . ": $selector</ul></div>";
	}
}

/**
 * Generate breadcrumb path for cart purchase process
 *
 * @param string|boolean $gateway Has a value if we're on payment process; false if not set
 * @param string|boolean $receipt Receipt ID for completed purchases.
 * @return string
 */
function mt_generate_path( $gateway ) {
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$path  = '<span class="active"><a href="'.home_url().'">' . __( 'Home', 'my-tickets' ) . '</a></span>';
	if ( $gateway == false ) {
		$path .= '<span class="inactive"><strong>'. __( 'Cart', 'my-tickets' ) . '</strong></span>';
	} else {
		$path .= '<span class="active"><a href="' . get_permalink( $options['mt_purchase_page'] ) . '">' . __( 'Cart', 'my-tickets' ) . '</a></span>';
	}
	if ( $gateway == false ) {
		$path .= '<span class="inactive">' . __( 'Payment', 'my-tickets' ) . '</span>';
	} else {
		$path .= '<span class="inactive"><strong>' . __( 'Payment', 'my-tickets' ) . '</strong></span>';
	}
	return "<div class='mt_purchase_path'>".$path."</div>";
}

/**
 * Generate cart. Show all events with tickets in cart unless event is already past the time when it can be ordered.
 * TODO: Display notice if item has been removed from cart.
 *
 * @param bool $user_ID
 *
 * @return mixed|string|void
 */
function mt_generate_cart( $user_ID = false ) {
	// if submitted successfully & payment required, toggle to payment form
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$gateway = isset( $_POST['mt_gateway'] ) ? $_POST['mt_gateway'] : false;
	$breadcrumbs = mt_generate_path( $gateway );
	if ( $gateway && $gateway != 'offline' ) {
		$response = mt_update_cart( $_POST['mt_cart_order'] );
		$cart     = $response['cart'];
		$output   = mt_generate_gateway( $cart );
	} else {
		$cart         = mt_get_cart( $user_ID );
		$total        = mt_total_cart( $cart );
		$count        = mt_count_cart( $cart );
		$handling_total = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$handling       = apply_filters( 'mt_money_format', $handling_total );
		$nonce          = wp_nonce_field( 'mt_cart_nonce', '_wpnonce', true, false );
		$enabled        = $options['mt_gateway'];
		$current_gate   = ( isset( $_GET['mt_gateway'] ) && in_array( $_GET['mt_gateway'], $enabled ) ) ? $_GET['mt_gateway'] : $options['mt_default_gateway'];
		if ( $total == 0 ) {
			$current_gate = 'offline';
		}
		$gateway   = "<input type='hidden' name='mt_gateway' value='" . esc_attr( $current_gate ) . "' />";
		$cart_page = get_permalink( $options['mt_purchase_page'] );
		if ( is_array( $cart ) && ! empty( $cart ) && $count > 0 ) {
			$output = '
		<div class="mt_cart">
			<div class="mt-response" aria-live="assertive"></div>
			<form action="' . esc_url( $cart_page ) . '" method="POST">' . "
			<input class='screen-reader-text' type='submit' name='mt_submit' value='" . apply_filters( 'mt_submit_button_text', __( 'Place Order', 'my-tickets' ), $current_gate ) . "' />" . '
				' . $nonce . '
				' . $gateway;
			$output .= mt_generate_cart_table( $cart );
			if ( $handling_total != 0 ) {
				$output .= "<div class='mt_cart_handling'>" . apply_filters( 'mt_cart_handling_text', sprintf( __( 'A handling fee of %s will be applied to this purchase.', 'my-tickets' ), $handling ), $current_gate ) . "</div>";
			}
			if ( mt_handling_notice() ) {
				$output .= "<div class='mt_ticket_handling'>" . mt_handling_notice() . "</div>";
			}
			$custom_fields = apply_filters( 'mt_cart_custom_fields', array(), $cart, $gateway );
			$custom_output = '';
			foreach ( $custom_fields as $key => $field ) {
				$custom_output .= $field;
			}
			$output .= "<div class='mt_cart_total' aria-live='assertive'>" . apply_filters( 'mt_cart_ticket_total_text', __( 'Ticket Total:', 'my-tickets' ), $current_gate ) . " <span class='mt_total_number'>" . apply_filters( 'mt_money_format', $total ) . "</span></div>\n" .
			           mt_invite_login_or_register() . "\n" .
			           mt_required_fields( $cart ) . "\n" .
			           $custom_output . "\n
				<p class='mt_submit'><input type='submit' name='mt_submit' value='" . apply_filters( 'mt_submit_button_text', __( 'Place Order', 'my-tickets' ), $current_gate ) . "' /></p>\n
				<input type='hidden' name='my-tickets' value='true' />
			</form>" .
			           mt_gateways() .
			           mt_copy_cart() . "
		</div>";
		} else {
			do_action( 'mt_cart_is_empty' );
			$output = apply_filters( 'mt_cart_is_empty_text', "<p class='cart-empty'>" . __( 'Your cart is currently empty.', 'my-tickets' ) . '</p>' );
		}
	}

	return $breadcrumbs . $output;
}

function mt_copy_cart() {
	if ( current_user_can( 'mt-copy-cart' ) || current_user_can( 'manage_options' ) ) {
		$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? $_COOKIE['mt_unique_id'] : false;
		if ( $unique_id ) {
			$cart = esc_attr( $unique_id );
			return "<p><a href='" . admin_url( "post-new.php?post_type=mt-payments&amp;cart=$cart" ) . "'>" . __( 'Create new admin payment with this cart', 'my-tickets' ) . "</a></p>";
		}
	}
}

add_filter( 'mt_submit_button_text', 'mt_submit_button_text', 10, 2 );
/**
 * Filter button text based on gateway selected.
 *
 * @param $text
 * @param $gateway
 *
 * @return string|void
 */
function mt_submit_button_text( $text, $gateway ) {
	if ( $gateway != 'offline' ) {
		return __( 'Review cart and make payment', 'my-tickets' );
	}

	return $text;
}

add_filter( 'mt_link_title', 'mt_core_link_title', 10, 2 );
/**
 * Filter event titles to display as linked in cart when a link is available. Occurrence IDs are not available, so details link can't be provided.
 *
 * @param $event_title Title of event
 * @param $event Event post object
 *
 * @return string linked title if a link is available (any event post or event with a link)
 */
function mt_core_link_title( $event_title, $event ) {
	$event_id = get_post_meta( $event->ID, '_mc_event_id', true );
	if ( $event_id && function_exists( 'mc_get_details_link' ) ) {
		$event =  mc_get_event_core( $event_id );
		$link  = mc_event_link( $event );
	} else {
		$link = get_permalink( $event->ID );
	}
	if ( $link ) {
		return "<a href='$link'>$event_title</a>";
	} else {
		return $event_title;
	}
}

/**
 * Generate tabular data for cart. Include custom fields if defined.
 *
 * @param $cart
 * @param string $format
 *
 * @return string
 */
function mt_generate_cart_table( $cart, $format = 'cart' ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	if ( ! is_admin() ) {
		$caption = ( $format == 'confirmation' ) ? __( 'Review and Purchase', 'my-tickets' ) : __( 'Shopping Cart', 'my-tickets' );
		$class   = ' mt_cart';
	} else {
		$caption = __( 'Ticket Order', 'my-tickets' );
		$class   = '';
	}
	$output = '
	<table class="widefat' . $class . '"><caption>' . $caption . '</caption>
			<thead>
				<tr>
					<th scope="col">' . __( 'Event', 'my-tickets' ) . '</th><th scope="col">' . __( 'Price', 'my-tickets' ) . '</th><th scope="col">' . __( 'Tickets', 'my-tickets' ) . '</th>';
	if ( $format == 'cart' ) {
		$output .= '<th scope="col" class="mt-update-column">' . __( 'Update', 'my-tickets' ) . '</th>';
	}
	$output .= '</tr>
			</thead>
			<tbody>';
	$total = 0;
	if ( is_array( $cart ) && ! empty( $cart ) ) {
		foreach ( $cart as $event_id => $order ) {
			$expired = mt_expired( $event_id );
			if ( ! $expired ) {
				$prices       = mt_get_prices( $event_id );
				$currency     = $options['mt_currency'];
				$event        = get_post( $event_id );
				$title        = apply_filters( 'mt_link_title', $event->post_title, $event );
				$image        = ( has_post_thumbnail( $event_id ) ) ? get_the_post_thumbnail( $event_id, array( 80, 80 ) ) : '';
				$data         = get_post_meta( $event_id, '_mc_event_data', true );
				$registration = get_post_meta( $event_id, '_mt_registration_options', true );
				$date         = $data['event_begin'] . ' ' . $data['event_time'];
				$datetime     = "<span class='mt-datetime'>" . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $date ) ) . "</span>";
				if ( is_array( $order ) ) {
					foreach ( $order as $type => $count ) {
						$comps = ( current_user_can( 'manage_options' ) ) ? true : false;
						if ( $type == 'complementary' && $comps == false ) {
							continue;
						}
						if ( $count > 0 ) {
							if ( isset( $prices[ $type ] ) ) {
								$price = mt_handling_price( $prices[ $type ]['price'], $event_id, $type );
								$label = $prices[ $type ]['label'];
								if ( $format == 'cart' || is_admin() ) {
									$hidden = "
											<input type='hidden' class='mt_count' name='mt_cart_order[$event_id][$type][count]' value='$count' />
											<input type='hidden' name='mt_cart_order[$event_id][$type][price]' value='$price' />";
								} else {
									$hidden = '';
								}
								$total  = $total + ( $price * $count );
								$custom = apply_filters( 'mt_show_in_cart_fields', '', $event_id );
								$output .= "
											<tr id='mt_cart_order_$event_id" . '_' . "$type'>
												<th scope='row'>$image$title: <em>$label</em><br />$datetime$hidden$custom</th>
												<td>$currency " . apply_filters( 'mt_money_format', $price ) . "</td>
												<td aria-live='assertive'><span class='count'>$count</span></td>";
								if ( $format == 'cart' && apply_filters( 'mt_include_update_column', true ) ) {
									if ( $registration['multiple'] == 'true' ) {
										$output .= "<td class='mt-update-column'><button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='more'>+<span class='screen-reader-text'> " . __( 'More', 'my-tickets' ) . "</span></button> <button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='less'>-<span class='screen-reader-text'> " . __( 'Less', 'my-tickets' ) . "</span></button> <button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='remove'>x<span class='screen-reader-text'> " . __( 'Remove from cart', 'my-tickets' ) . "</span></button></td>";
									} else {
										$output .= "<td class='mt-update-column'><button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='remove'>x<span class='screen-reader-text'> " . __( 'Remove from cart', 'my-tickets' ) . "</span></button>" . apply_filters( 'mt_no_multiple_registration', '' ) . "</td>";
									}
								}
								$output .= "</tr>";
							}
						}
					}
				}
			}
		}
	}
	$output .= '
			</tbody>		
		</table>';

	return $output;
}

/**
 * Get total $ value of saved cart.
 *
 * @param $cart
 *
 * @return int
 */
function mt_total_cart( $cart ) {
	$total = 0;
	if ( is_array( $cart ) ) {
		foreach ( $cart as $event => $order ) {
			$expired = mt_expired( $event );
			if ( ! $expired ) {
				$prices = mt_get_prices( $event );
				if ( is_array( $order ) ) {
					foreach ( $order as $type => $count ) {
						if ( $count > 0 ) {
							$price = ( isset( $prices[ $type ] ) ) ? $prices[ $type ]['price'] : '';
							if ( $price ) {
								$price = mt_handling_price( $price, $event );
							}
							$total = $total + ( $price * $count );
						}
					}
				}
			}
		}
	}

	return $total;
}

/**
 * Get number of tickets in current cart.
 *
 * @param $cart
 *
 * @return int
 */
function mt_count_cart( $cart ) {
	$total = 0;
	if ( is_array( $cart ) ) {
		foreach ( $cart as $event => $order ) {
			$expired = mt_expired( $event );
			if ( ! $expired ) {
				if ( is_array( $order ) ) {
					foreach ( $order as $type => $count ) {
						$total = $total + $count;
					}
				}
			}
		}
	}

	return $total;
}

/**
 * Generate payment gateway code from selected gateway.
 *
 * array $cart cart data
 *
 * uses: filter mt_gateway (pull gateway form)
 * uses: filter mt_form_wrapper (html wrapper around gateway)
*/
function mt_generate_gateway( $cart ) {
	$options        = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$return_url     = get_permalink( $options['mt_purchase_page'] );
	$link           = apply_filters( 'mt_return_link', "<p class='return-to-cart'>" . sprintf( __( '<a href="%s">Return to cart</a>', 'my-tickets' ), $return_url ) . "</p>" );
	$confirmation   = mt_generate_cart_table( $cart, 'confirmation' );
	$total          = mt_total_cart( $cart );
	$count          = mt_count_cart( $cart );
	if ( $count > 0 ) {
		$payment        = mt_get_data( 'payment' );
		$ticket_method  = ( isset( $_POST['ticketing_method'] ) ) ? $_POST['ticketing_method'] : 'willcall';
		$mt_gateway     = ( isset( $_POST['mt_gateway'] ) ) ? $_POST['mt_gateway'] : 'offline';
		$shipping_total = ( $ticket_method == 'postal' ) ? $options['mt_shipping'] : 0;
		$handling_total = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$shipping       = ( $shipping_total ) ? "<div class='mt_cart_shipping mt_cart_label'>" . __( 'Shipping:', 'my-tickets' ) . " <span class='mt_shipping_number mt_cart_value'>" . apply_filters( 'mt_money_format', $shipping_total ) . "</span></div>" : '';
		$handling       = ( $handling_total ) ? "<div class='mt_cart_handling mt_cart_label'>" . __( 'Handling:', 'my-tickets' ) . " <span class='mt_handling_number mt_cart_value'>" . apply_filters( 'mt_money_format', $handling_total ) . "</span></div>" : '';
		$tick_handling  = mt_handling_notice();
		$other_charges  = apply_filters( 'mt_custom_charges', 0, $cart, $mt_gateway );
		$other_notices  = apply_filters( 'mt_custom_notices', '', $cart, $mt_gateway );
		$report_total   = "<div class='mt_cart_total'>" . apply_filters( 'mt_cart_total_text', __( 'Total:', 'my-tickets' ), $mt_gateway ) . " <span class='mt_total_number'>" . apply_filters( 'mt_money_format', $total + $shipping_total + $handling_total + $other_charges ) . "</span></div>";
		$args           = apply_filters( 'mt_payment_form_args', array( 'cart'    => $cart,
		                                                                'total'   => $total,
		                                                                'payment' => $payment,
		                                                                'method'  => $ticket_method
		) );
		$form           = apply_filters( 'mt_gateway', '', $mt_gateway, $args );
		$form           = apply_filters( 'mt_form_wrapper', $form );

		return $link . $confirmation . "<div class='mt-after-cart'>" . $tick_handling . $shipping . $handling . $other_notices . $report_total . '</div>' . $form;
	} else {
		do_action( 'mt_cart_is_empty' );
		return apply_filters( 'mt_cart_is_empty_text', "<p class='cart-empty'>" . __( 'Your cart is currently empty.', 'my-tickets' ) . '</p>' );
	}
}

add_filter( 'mt_form_wrapper', 'mt_wrap_payment_button' );
/**
 * Generate HTML to wrap gateway form.
 *
 * @param $form
 *
 * @return string
 */
function mt_wrap_payment_button( $form ) {
	return "<div class='mt-payment-form'>" . $form . "</div>";
}

/**
 * If SSL is enabled, replace HTTP in URL.
 *
 * @param $url
 *
 * @return mixed
 */
function mt_replace_http( $url ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	if ( $options['mt_ssl'] == 'true' ) {
		$url = preg_replace( '|^http://|', 'https://', $url );
	}

	return $url;
}

/**
 * Test whether an event is no longer available for purchase. If user has capability to order expired events, allow.
 *
 * @param $event
 *
 * @return bool
 */
function mt_expired( $event ) {
	if ( current_user_can( 'mt-order-expired' ) || current_user_can( 'manage_options' ) ) {
		return false;
	}
	$expired = get_post_meta( $event, '_mt_event_expired', true );
	if ( $expired === 'true' ) {
		return true;
	} else {
		$options    = get_post_meta( $event, '_mt_registration_options', true );
		$data       = get_post_meta( $event, '_mc_event_data', true );
		if ( is_array( $data ) && is_array( $options ) && !empty( $options ) ) {
			$expires    = ( isset( $options['reg_expires'] ) ) ? $options['reg_expires'] : 0;
			$expiration = $expires * 60 * 60;
			$begin      = strtotime( $data['event_begin'] . ' ' . $data['event_time'] ) - $expiration;
			if ( mt_date_comp( date( 'Y-m-d H:i:s', $begin ), date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) ) ) {
				update_post_meta( $event, '_mt_event_expired', 'true' );
				do_action( 'mt_ticket_sales_closed', $event );

				return true;
			}
		}

		return false;
	}
}

/**
 * Get saved cart data for user.
 *
 * @param bool $user_ID
 *
 * @return array|mixed
 */
function mt_get_cart( $user_ID = false, $cart_id = false ) {
	$cart = array();
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? $_COOKIE['mt_unique_id'] : false;
	if ( $user_ID ) {
		$cart = get_user_meta( $user_ID, '_mt_user_cart', true );
	} else if ( !$user_ID && $cart_id ) {
		$cart = get_transient( "mt_".$cart_id.'_cart' );
	} else {
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$cart = get_user_meta( $current_user->ID, '_mt_user_cart', true );
		} else {
			if ( $unique_id ) {
				$cart = get_transient( "mt_".$unique_id.'_cart' );
			}
		}
	}
	if ( is_user_logged_in() && ! $cart ) {
		if ( $unique_id ) {
			$cart = get_transient( "mt_".$unique_id.'_cart' );
		}
	}
	return $cart;
}

add_action( 'wp_head', 'mt_cart_meta', 1 );
/**
 * Adds the user's cart ID into <head> meta data for admin retrieval for customer assistance.
 *
 * Cart Data does not expose any user-specific information; contains only event ID and tickets selected.
 */
function mt_cart_meta() {
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? $_COOKIE['mt_unique_id'] : false;
	if ( $unique_id ) {
		echo "<meta name='cart_id' value='".esc_attr( $unique_id )."' />\n";
	}
}