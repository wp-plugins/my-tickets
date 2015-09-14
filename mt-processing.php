<?php
// Get & Save data about event registration options - set up the ticketing options.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


// begin add boxes
add_action( 'admin_menu', 'mt_add_ticket_box' );
/**
 * Add purchase data meta box to enabled post types.
 */
function mt_add_ticket_box() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	foreach ( $options['mt_post_types'] as $name ) {
		if ( $name != 'mc-events' ) {
			add_meta_box( 'mt_custom_div', __( 'My Tickets Purchase Data', 'my-tickets' ), 'mt_add_ticket_form', $name, 'normal', 'high' );
		}
	}
}

/**
 * Add ticket form to enabled post types meta boxes.
 */
function mt_add_ticket_form() {
	global $post_id;
	$format   = sprintf(
		'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
		'mt-tickets-nonce', wp_create_nonce( 'mt-tickets-nonce' )
	);
	$data     = get_post_meta( $post_id, '_mc_event_data', true );
	$location = get_post_meta( $post_id, '_mc_event_location', true );
	// add fields for event time and event date
	if ( isset( $data['event_begin'] ) ) {
		$event_begin = $data['event_begin'];
		$event_time  = $data['event_time'];
		$checked     = ' checked="checked"';
	} else {
		$event_begin = $event_time = $checked = '';
	}

	$format .= "<p>
					<input type='checkbox' class='mt-trigger' name='mt-trigger' id='mt-trigger'$checked /> <label for='mt-trigger'>" . __( 'Sell tickets on this post.', 'my-tickets' ) . "</label>
				</p>";
	if ( function_exists( 'mc_location_select' ) ) {
		$selector = "
		<label for='mt-event-location'>" . __( 'Select a location', 'my-tickets' ) . "
		<select name='mt-event-location' id='mt-event-location'>
			<option value=''> -- </option>
			" . mc_location_select( $location ) . "
		</select>";
	} else {
		$selector = sprintf( __( 'Install <a href="%s">My Calendar</a> to manage and choose locations for your events', 'my-tickets' ), admin_url( 'plugin-install.php?tab=search&s=my-calendar' ) );
	}
	$form =
		"<div class='mt-ticket-form'>
			<div class='mt-ticket-dates'>
					<p>
						<label for='event_begin'>" . __( 'Event Date', 'my-tickets' ) . "</label> <input type='date' name='event_begin' id='event_begin' value='$event_begin' /> <label for='event_time'>" . __( 'Event Time', 'my-tickets' ) . "</label> <input type='time' name='event_time' id='event_time' value='$event_time' />
					</p>
					<p>
						$selector
					</p>
			</div>" .
		apply_filters( 'mc_event_registration', '', $post_id, $data, 'admin' ) . "
		</div>";
	echo '<div class="mt_post_fields">' . $format . $form . '</div>';
}

add_action( 'save_post', 'mt_ticket_meta', 10 );
/**
 * Save ticket meta data when enabled post is saved.
 *
 * @param $post_id
 */
function mt_ticket_meta( $post_id ) {
	if ( isset( $_POST['mt-tickets-nonce'] ) && isset( $_POST['mt-trigger'] ) ) {
		$nonce = $_POST['mt-tickets-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'mt-tickets-nonce' ) ) {
			wp_die( "Invalid nonce" );
		}
		$event_begin = date( 'Y-m-d', strtotime( $_POST['event_begin'] ) );
		$event_time  = date( 'H:i:s', strtotime( $_POST['event_time'] ) );
		$data        = array( 'event_begin' => $event_begin, 'event_time' => $event_time, 'event_post' => $post_id );
		if ( isset( $_POST['mt-event-location'] ) && is_numeric( $_POST['mt-event-location'] ) ) {
			update_post_meta( $post_id, '_mc_event_location', $_POST['mt-event-location'] );
		}
		update_post_meta( $post_id, '_mc_event_data', $data );
		update_post_meta( $post_id, '_mc_event_date', strtotime( $_POST['event_begin'] ) );
		mt_save_registration_data( $post_id, $_POST );
	}

	return;
}

/*
 * Gets array of ticket types and prices for an event
 *
 * @param $event_id int
 * @uses mt_calculate_discount()
*/
function mt_get_prices( $event_id ) {
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	if ( isset( $registration['prices'] ) ) {
		$prices = $registration['prices'];
		// logged-in users ordering from the front-end, only; in admin, no discount applied.
		if ( is_user_logged_in() && ! is_admin() && is_array( $prices ) ) { // cycle only if pricing is being modified
			foreach ( $prices as $label => $options ) {
				$price                     = $prices[ $label ]['price'];
				$prices[ $label ]['price'] = mt_calculate_discount( $price );
			}
		}

		return $prices;
	}

	return false;
}

/*
 * Calculates actual cost of an event ticket if member discount in effect
 *
 * @param $price float
 *
*/
function mt_calculate_discount( $price ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	if ( is_user_logged_in() && ! is_admin() ) { // members discount
		$discount = (int) $options['mt_members_discount'];
	} else {
		$discount = 0;
	}
	$discount   = apply_filters( 'mt_members_discount', $discount );
	$discounted = ( $discount != 0 ) ? $price - ( $price * ( $discount / 100 ) ) : $price;
	$discounted = sprintf( "%01.2f", $discounted );

	return $discounted;
}

/*
 * Add registration fields for My Calendar events
 *
 * @param $has_data bool
 * @param $data object
 *
 */
function mt_registration_fields( $form, $has_data, $data, $public = 'admin' ) {
	$original_form = $form;
	$options       = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$registration  = $event_id = $description = $hide = false;
	if ( $has_data === true ) {
		$event_id     = (int) $data->event_post;
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$hide         = get_post_meta( $event_id, '_mt_hide_registration_form', true );
		$description  = stripslashes( esc_attr( $data->event_registration ) );
	}
	if ( is_int( $has_data ) ) {
		$event_id     = $has_data;
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$hide         = get_post_meta( $event_id, '_mt_hide_registration_form', true );
		$description  = false;
	}
	$expiration  = ( isset( $registration['reg_expires'] ) ) ? $registration['reg_expires'] : $options['defaults']['reg_expires'];
	$multiple    = ( isset( $registration['multiple'] ) ) ? $registration['multiple'] : $options['defaults']['multiple'];
	$is_multiple = ( $multiple == 'true' ) ? 'checked="checked"' : '';
	$type        = ( isset( $registration['sales_type'] ) ) ? $registration['sales_type'] : $options['defaults']['sales_type'];
	if ( ! $type || $type == 'tickets' ) {
		$is_tickets      = ' checked="checked"';
		$is_registration = '';
	} else {
		$is_tickets      = '';
		$is_registration = ' checked="checked"';
	}
	$method = ( isset( $registration['counting_method'] ) ) ? $registration['counting_method'] : $options['defaults']['counting_method'];
	if ( $method == 'discrete' ) {
		$is_discrete   = ' checked="checked"';
		$is_continuous = '';
	} else {
		$is_discrete   = '';
		$is_continuous = ' checked="checked"';
	}
	if ( $hide == 'true' ) {
		$is_hidden = ' checked="checked"';
	} else {
		$is_hidden = '';
	}
	if ( $registration ) {
		$shortcode = "<label for='shortcode'>" . __( 'Add to Cart Form Shortcode', 'my-tickets' ) . "</label><br /><textarea id='shortcode' readonly='readonly' class='large-text readonly'>[ticket event='$event_id']</textarea>";
	} else {
		$shortcode = '';
	}
	$form = $shortcode . "
	<p>
		<label for='reg_expires'>" . __( 'Allow sales until', 'my-tickets' ) . "</label> <input type='number' name='reg_expires' id='reg_expires' value='$expiration' aria-labelledby='reg_expires reg_expires_label' size='3' /> <strong class='label' id='reg_expires_label'>" . __( 'hours before the event', 'my-tickets' ) . "</strong>
	</p>
	<p>
		<label for='mt_multiple'>" . __( 'Allow multiple tickets/ticket type per purchaser', 'my-tickets' ) . "</label> <input type='checkbox' name='mt_multiple' id='mt_multiple' value='true' $is_multiple />
	</p>";
	$form .= ( $event_id ) ? "<p class='get-report'><a href='" . admin_url( "admin.php?page=mt-reports&amp;event_id=$event_id" ) . "'>" . __( 'View Tickets Purchased for this event', 'my-tickets' ) . '</a></p>' : '';
	$form .= mt_prices_table( $registration );
	$form .= "
		<fieldset><legend>" . __( 'Type of Sale', 'my-tickets' ) . "</legend>
		<p>
			<input type='radio' name='mt_sales_type' id='mt_sales_type_tickets' value='tickets' $is_tickets /> <label for='mt_sales_type_tickets'>" . __( 'Ticket Sales', 'my-tickets' ) . "</label><br />
			<input type='radio' name='mt_sales_type' id='mt_sales_type_registration' value='registration' $is_registration /> <label for='mt_sales_type_registration'>" . __( 'Event Registration', 'my-tickets' ) . "</label>
		</p>
		</fieldset>
		<fieldset><legend>" . __( 'Ticket Counting Method', 'my-tickets' ) . "</legend>
			<p>
				<input type='radio' name='mt_counting_method' id='mt_counting_method_discrete' value='discrete' $is_discrete /> <label for='mt_counting_method_discrete'>" . __( 'Discrete - (Section A, Section B, etc.)', 'my-tickets' ) . "</label><br />
				<input type='radio' name='mt_counting_method' id='mt_counting_method_continuous' value='continuous' $is_continuous /> <label for='mt_counting_method_continuous'>" . __( 'Continuous - (Adult, Child, Senior)', 'my-tickets' ) . "</label>
			</p>
		</fieldset>";
	if ( $description !== false ) {
		$form .= "<p>
				<label for='event_registration'>" . __( 'Registration Information', 'my-tickets' ) . "</label> <textarea name='event_registration' id='event_registration' cols='40' rows='4'/>$description</textarea>
			</p>";
	}
	$form .= "<p>
		<input type='checkbox' name='mt_hide_registration_form' id='mt_hide' $is_hidden /> <label for='mt_hide'>" . __( 'Don\'t display form on event', 'my-tickets' ) . "</label>
	</p>";
	$form .= apply_filters( 'mt_custom_data_fields', '', $registration, $data );

	return apply_filters( 'mc_event_registration_form', $form, $has_data, $data, $public, $original_form );
}

/*
 * Generates pricing table from registration array and event ID; uses defaults if no values passed.
 *
 * @param $registration array : array of ticketing and registration data for this event.
 * @pararm $event_id int : post ID
 *
*/
function mt_prices_table( $registration = array() ) {
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$counting = $options['defaults']['counting_method'];
	$pricing  = $options['defaults']['pricing'];
	$available = '';
	$tickets  = ( isset( $options['defaults']['tickets'] ) ) ? $options['defaults']['tickets'] : false;
	$return   = "<table class='widefat mt-pricing'>
					<caption>" . __( 'Ticket Prices and Availability', 'my-tickets' ) . "</caption>
					<thead>
						<tr>
							<th scope='col'>" . __( 'Move', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Label', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Price', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Available', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Sold', 'my-tickets' ) . "</th>
						</tr>
					</thead>
					<tbody>";
	$counting = ( isset( $registration['counting_method'] ) ) ? $registration['counting_method'] : $counting;
	if ( $counting == 'discrete' ) {
		$available_empty = "<input type='text' name='mt_tickets[]' id='mt_tickets' value='' size='8' />";
		$total           = '<input type="hidden" name="mt_tickets_total" value="inherit" />';
	} else {
		$value           = isset( $registration['total'] ) ? $registration['total'] : $tickets;
		$available_empty = "<input type='hidden' name='mt_tickets[]' id='mt_tickets' value='inherit' />";
		$total           = "<label for='mt_tickets_total'>" . __( 'Total Tickets Available', 'my-tickets' ) . ':</label> <input type="text" name="mt_tickets_total" id="mt_tickets_total" value="' . esc_attr( $value ) . '" />';
	}
	$pricing = ( isset( $registration['prices'] ) ) ? $registration['prices'] : $pricing; // array of prices; label => cost/available
	if ( is_array( $pricing ) ) {
		foreach ( $pricing as $label => $options ) {
			if ( $counting == 'discrete' ) {
				$available = "<input type='text' name='mt_tickets[]' id='mt_tickets_$label' value='" . esc_attr( $options['tickets'] ) . "' size='8' />";
			} else {
				$available = "<input type='hidden' name='mt_tickets[]' id='mt_tickets_$label' value='inherit' />";
			}
			if ( $label ) {
				$return .= "
				<tr>
					<td class='controls'><button href='#' class='button up'><span class='dashicons dashicons-arrow-up-alt'></span><span class='screen-reader-text'>Move Up</span></button> <button href='#' class='button down'><span class='dashicons dashicons-arrow-down-alt'></span><span class='screen-reader-text'>Move Down</span></button></td>
					<td><input type='text' name='mt_label[]' id='mt_label_$label' value='" . esc_attr( stripslashes( $options['label'] ) ) . "' /></td>
					<td><input type='text' name='mt_price[]' id='mt_price_$label' value='" . esc_attr( $options['price'] ) . "' size='8' /></td>
					<td>$available</td>
					<td><input type='hidden' name='mt_sold[]' value='$options[sold]' />$options[sold]</td>
				</tr>";
			}
		}
		$has_comps = false;
		$keys = array_keys( $pricing );
		if ( in_array( 'complementary', $keys ) ) {
			$has_comps = true;
		}
		if ( !$has_comps ) {
			$return .= "
				<tr>
					<td class='controls'><button href='#' class='button up'><span class='dashicons dashicons-arrow-up-alt'></span><span class='screen-reader-text'>Move Up</span></button> <button href='#' class='button down'><span class='dashicons dashicons-arrow-down-alt'></span><span class='screen-reader-text'>Move Down</span></button></td>
					<td><input type='text' readonly name='mt_label[]' id='mt_label_complementary' value='Complementary' /><br />" . __( 'Note: complementary tickets can only be added by logged-in administrators.', 'my-tickets' ) . "</td>
					<td><input type='text' readonly name='mt_price[]' id='mt_price_complementary' value='0' size='8' /></td>
					<td>$available</td>
					<td></td>
				</tr>";
		}
	}
	$return .= "
		<tr class='clonedPrice' id='price1'>
			<td></td>
			<td><input type='text' name='mt_label[]' id='mt_label' /></td>
			<td><input type='text' name='mt_price[]' id='mt_price' size='8' /></td>
			<td>$available_empty</td>
			<td></td>
		</tr>";
	$return .= "</tbody></table>";
	$add_field = __( 'Add a price group', 'my-tickets' );
	$del_field = __( 'Remove last price group', 'my-tickets' );
	$return .= '
			<p>
				<input type="button" id="add_price" value="' . $add_field . '" class="button" />
				<input type="button" id="del_price" value="' . $del_field . '" class="button" />
			</p>';

	return $total . $return;
}

/*
 * Save registration/ticketing info as post meta.
 *
 * @param $post_id int
 * @param $post array $_POST
 * @param $data My Calendar event object
 * @param $event_id post ID
 *
*/
function mt_save_registration_data( $post_id, $post, $data = array(), $event_id = false ) {
	// replace this with handling for post meta data
	$event_begin          = ( isset( $post['event_begin'] ) ) ? $post['event_begin'] : '';
	$event_begin          = ( is_array( $event_begin ) ) ? $event_begin[0] : $event_begin;
	$labels               = ( isset( $post['mt_label'] ) ) ? $post['mt_label'] : array();
	$prices               = ( isset( $post['mt_price'] ) ) ? $post['mt_price'] : array();
	$sold                 = ( isset( $post['mt_sold'] ) ) ? $post['mt_sold'] : array();
	$hide                 = ( isset( $post['mt_hide_registration_form'] ) ) ? 'true' : 'false';
	$availability         = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : 'inherit';
	$total_tickets        = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
	$pricing_array        = mt_setup_pricing( $labels, $prices, $availability, $sold );
	$reg_expires          = ( isset( $post['reg_expires'] ) ) ? (int) $post['reg_expires'] : 0;
	$multiple             = ( isset( $post['mt_multiple'] ) ) ? 'true' : 'false';
	$mt_sales_type        = ( isset( $post['mt_sales_type'] ) ) ? $post['mt_sales_type'] : 'tickets';
	$counting_method      = ( isset( $post['mt_counting_method'] ) ) ? $post['mt_counting_method'] : 'discrete';
	$registration_options = array(
		'reg_expires'     => $reg_expires,
		'sales_type'      => $mt_sales_type,
		'counting_method' => $counting_method,
		'prices'          => $pricing_array,
		'total'           => $total_tickets,
		'multiple'        => $multiple,
	);
	if ( mt_date_comp( date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ), $event_begin ) ) {
		// if the date changes, and is now in the future, re-open ticketing.
		delete_post_meta( $post_id, '_mt_event_expired' );
	}
	$registration_options = apply_filters( 'mt_registration_options', $registration_options, $post, $data );
	update_post_meta( $post_id, '_mt_registration_options', $registration_options );
	update_post_meta( $post_id, '_mt_hide_registration_form', $hide );
}

/*
 * Generates pricing array from POST data
 *
 * @param $labels array
 * @param $prices array
 * @param $availability array
 * @param $sold array (empty when event is created.)
 *
*/
function mt_setup_pricing( $labels, $prices, $availability, $sold = array() ) {
	$return = array();
	if ( is_array( $labels ) ) {
		$i = 0;
		foreach ( $labels as $label ) {
			if ( $label ) {
				$label          = esc_sql( $label );
				$internal_label = sanitize_title( $label );
				$price          = ( is_numeric( $prices[ $i ] ) ) ? $prices[ $i ] : (int) $prices[ $i ];
				if ( $availability[ $i ] !== '' ) {
					$tickets = ( is_numeric( $availability[ $i ] ) ) ? $availability[ $i ] : (int) $availability[ $i ];
				} else {
					$tickets = '';
				}
				$sold_tickets              = ( isset( $sold[ $i ] ) ) ? (int) $sold[ $i ] : '';

				$return[ $internal_label ] = array( 'label'   => $label,
				                                    'price'   => $price,
				                                    'tickets' => $tickets,
				                                    'sold'    => $sold_tickets
				);
				$i ++;
			}
		}
	}

	return $return;
}