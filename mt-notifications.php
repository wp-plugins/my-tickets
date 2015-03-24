<?php

// if a post is trashed, return the tickets to pool.
// Trashing a payment is *not* a refund; no notifications are sent.
add_action( 'wp_trash_post', 'mt_return_tickets_action' );
/**
 * Return reserved tickets to pool if a payment is trashed.
 *
 * @param $id
 */
function mt_return_tickets_action( $id ) {
	$type = get_post_type( $id );
	if ( $type == 'mt-payments' ) {
		mt_return_tickets( $id );
	}
}


add_action( 'save_post', 'mt_generate_notifications' );
/**
 * Send payment notifications to admin and purchaser when a payment is transitioned to published.
 *
 * @param $id
 */
function mt_generate_notifications( $id ) {
	$type   = get_post_type( $id );
	$status = 'quo';
	if ( $type == 'mt-payments' ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $id ) ) {
			return;
		}
		$post = get_post( $id );
		if ( $post->post_status != 'publish' ) {
			return;
		}
		$email_sent = get_post_meta( $id, '_notified', true );
		if ( ! $email_sent || isset( $_POST['_send_email'] ) ) {
			$paid             = get_post_meta( $id, '_is_paid', true );
			$details['email'] = get_post_meta( $id, '_email', true );
			$details['name']  = get_the_title( $id );
			$details['id']    = $id;
			// only send this if email is provided; otherwise send notice to admin			
			if ( ! ( is_email( $details['email'] ) ) ) {
				$details['email'] = get_option( 'admin_email' );
				$status           = 'invalid_email';
			}
			mt_send_notifications( $paid, $details, $status );
		}
	}

	return;
}

add_filter( 'mt_format_array', 'mt_format_array', 10, 3 );
/**
 * Format array data for use in email notifications.
 *
 * @param $output
 * @param $type
 * @param $data
 *
 * @return string
 */
function mt_format_array( $output, $type, $data ) {
	if ( is_array( $data ) ) {
		switch ( $type ) {
			case 'purchase' :
				$output = mt_format_purchase( $data );
				break;
			case 'address' :
				$output = mt_format_address( $data );
				break;
			case 'tickets' :
				$output = mt_format_tickets( $data );
				break;
		}
	}

	return $output;
}

/**
 * Format purchase data for use in email notifications. (Basically, simplified version of cart output.)
 *
 * @param $purchase
 * @param bool $format
 *
 * @return string
 */
function mt_format_purchase( $purchase, $format = false ) {
	$output  = '';
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	// format purchase
	$is_html = ( $options['mt_html_email'] == 'true' || $format == 'html' ) ? true : false;
	$sep     = ( $is_html ) ? "<br />" : "\n";
	if ( !$purchase ) {
		$output = __( 'Your ticket information will be available once your payment is completed.', 'my-tickets' );
	} else {
		$total   = 0;
		foreach ( $purchase as $event ) {
			foreach ( $event as $id => $tickets ) {
				$title = ( $is_html ) ? "<strong>" . get_the_title( $id ) . "</strong>" : get_the_title( $id );
				$event = get_post_meta( $id, '_mc_event_data', true );
				$date  = date_i18n( get_option( 'date_format' ), strtotime( $event['event_begin'] ) );
				$time  = date_i18n( get_option( 'time_format' ), strtotime( $event['event_time'] ) );
				$output .= $title . $sep . $date . ' @ ' . $time . $sep . $sep;
				foreach ( $tickets as $type => $ticket ) {
					$total = $total + $ticket['price'] * $ticket['count'];
					$type  = ucfirst( str_replace( '-', ' ', $type ) );
					if ( $is_html ) {
						$output .= sprintf(
							           _n( '%1$s: 1 ticket at %2$s', '%1$s: %3$d tickets at %2$s', $ticket['count'], 'my-tickets' ),
							           "<strong>" . $type . "</strong>",
							           strip_tags( apply_filters( 'mt_money_format', $ticket['price'] ) ),
							           $ticket['count']
						           ) . $sep;
					} else {
						$output .= sprintf(
							           _n( '%1$s: 1 ticket at %2$s', '%1$s: %3$d tickets at %2$s', $ticket['count'], 'my-tickets' ),
							           $type,
							           strip_tags( apply_filters( 'mt_money_format', $ticket['price'] ) ),
							           $ticket['count']
						           ) . $sep;
					}
				}
				$output .= $sep . __( 'Total', 'my-tickets' ) . ': ' . strip_tags( apply_filters( 'mt_money_format', $total ) ) . $sep;
			}
		}
	}
	return $output;
}

/**
 * Format shipping address data for use in email notifications.
 *
 * @param $address
 *
 * @return string
 */
function mt_format_address( $address ) {
	// format address
	$output = '';
	if ( $address ) {
		$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$sep     = ( $options['mt_html_email'] == 'true' ) ? "<br />" : "\n";
		foreach ( $address as $value ) {
			$output .= $value . $sep;
		}

		return $output;
	}

	return $output;
}

/**
 * Format ticket data for use in email notifications.
 *
 * @param $tickets
 * @param string $type
 *
 * @return string
 */
function mt_format_tickets( $tickets, $type = 'text' ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$output  = '';
	$is_html = ( $options['mt_html_email'] == 'true' || $type == 'html' ) ? true : false;
	$sep     = ( $is_html ) ? "<br />" : "\n";
	$total   = count( $tickets );
	$i       = 1;
	foreach ( $tickets as $ticket ) {
		$ticket = ( $is_html ) ? "<a href='$ticket' target='_blank'>" . __( 'View Ticket', 'my-tickets' ) . " ($i/$total)</a>" : $ticket;
		$output .= "$i/$total: " . $ticket . $sep;
		$i ++;
	}

	return $output;
}

add_filter( 'mt_format_receipt', 'mt_format_receipt' );
/**
 * Generate link to receipt for email notifications.
 *
 * @param $receipt
 *
 * @return string
 */
function mt_format_receipt( $receipt ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	if ( $options['mt_html_email'] == 'true' ) {
		$receipt = "<a href='$receipt'>" . __( 'View your receipt for this purchase', 'my-tickets' ) . "</a>";
	}

	return $receipt;
}

/**
 * Send notifications to purchaser and admin.
 *
 * @param string $status
 * @param array $details
 * @param bool $error
 */
function mt_send_notifications( $status = 'Completed', $details = array(), $error = false ) {
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$blogname = get_option( 'blogname' );
	$subject  = $body = $subject2 = $body2 = '';
	$send     = true;
	$id       = $details['id'];
	$gateway  = get_post_meta( $id, '_gateway', true );
	$phone    = get_post_meta( $id, '_phone', true );

	// restructure post meta array to match cart array
	if ( $status == 'Completed' || ( $status == 'Pending' && $gateway == 'offline' ) ) {
		mt_create_tickets( $id );
	}
	$purchased    = get_post_meta( $id, '_purchased' );
	$ticket_array = mt_setup_tickets( $purchased, $id );

	$handling       = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;

	$total = mt_calculate_cart_cost( $purchased ) + $handling;
	$hash  = md5( add_query_arg( array( 'post_type' => 'mt-payments', 'p' => $id ), home_url() ) );

	$receipt        = add_query_arg( 'receipt_id', $hash, get_permalink( $options['mt_receipt_page'] ) );
	$transaction_id = get_post_meta( $id, '_transaction_id', true );


	if ( $status == 'Completed' ) {
		$amount_due = '0.00';
	} else {
		$amount_due = $total;
	}
	$amount_due       = strip_tags( apply_filters( 'mt_money_format', $amount_due ) );
	$total            = strip_tags( apply_filters( 'mt_money_format', $total ) );
	$transaction_data = get_post_meta( $id, '_transaction_data', true );
	$address          = ( isset( $transaction_data['shipping'] ) ) ? $transaction_data['shipping'] : false;
	$ticketing_method = get_post_meta( $id, '_ticketing_method', true );
	if ( $ticketing_method == 'eticket' || $ticketing_method == 'printable' ) {
		$tickets = apply_filters( 'mt_format_array', '', 'tickets', $ticket_array );
	} else {
		$tickets = ( $ticketing_method == 'willcall' ) ? __( 'Your tickets will be available at the box office.', 'my-tickets' ) : __( 'Your tickets will be mailed to you at the address provided.', 'my-tickets' );
		$tickets = ( $options['mt_html_email'] == 'true' ) ? "<p>" . $tickets . "</p>" : $tickets;
	}
	$data = array(
		'receipt'        => apply_filters( 'mt_format_receipt', $receipt ),
		'tickets'        => $tickets,
		'name'           => $details['name'],
		'blogname'       => $blogname,
		'total'          => $total,
		'key'            => $hash,
		'purchase'       => apply_filters( 'mt_format_array', '', 'purchase', $purchased ),
		'address'        => apply_filters( 'mt_format_array', '', 'address', $address ),
		'transaction'    => apply_filters( 'mt_format_array', '', 'transaction', $transaction_data ),
		'transaction_id' => $transaction_id,
		'amount_due'     => $amount_due,
		'handling'       => apply_filters( 'mt_money_format', $handling ),
		'method'         => ucfirst( $ticketing_method ),
		'phone'          => $phone
	);
	$custom_fields = apply_filters( 'mt_custom_fields', array() );
	foreach ( $custom_fields as $name => $field ) {
		$data[$name] = call_user_func( $field['display_callback'], get_post_meta( $id, '_'.$name, true ) );
	}
	apply_filters( 'mt_notifications_data', $data, $details );
	$email     = $details['email'];
	$headers[] = "From: $blogname Events <" . $options['mt_from'] . ">";
	$headers[] = "Reply-to: $options[mt_from]";

	if ( $status == 'Completed' || ( $status == 'Pending' && $gateway == 'offline' ) ) {
		$append = '';
		if ( $error == 'invalid_email' ) {
			$append = __( 'Purchaser did not provide valid email', 'my-tickets' );
		}
		$subject  = mt_draw_template( $data, $options['messages']['completed']['purchaser']['subject'] );
		$subject2 = mt_draw_template( $data, $options['messages']['completed']['admin']['subject'] );

		$body  = mt_draw_template( $data, $append . $options['messages']['completed']['purchaser']['body'] );
		$body2 = mt_draw_template( $data, $options['messages']['completed']['admin']['body'] );
	}

	if ( $status == 'Refunded' ) {

		$subject  = mt_draw_template( $data, $options['messages']['refunded']['purchaser']['subject'] );
		$subject2 = mt_draw_template( $data, $options['messages']['refunded']['admin']['subject'] );

		$body  = mt_draw_template( $data, $options['messages']['refunded']['purchaser']['body'] );
		$body2 = mt_draw_template( $data, $options['messages']['refunded']['admin']['body'] );

		// put tickets purchased on this registration back on event.
		mt_return_tickets( $id );
	}

	if ( $status == 'Failed' ) {

		$subject  = mt_draw_template( $data, $options['messages']['failed']['purchaser']['subject'] );
		$subject2 = mt_draw_template( $data, $options['messages']['failed']['admin']['subject'] );

		$body  = mt_draw_template( $data, $options['messages']['failed']['purchaser']['body'] );
		$body2 = mt_draw_template( $data, $options['messages']['failed']['admin']['body'] );
	}

	if ( $status == 'Pending' || ( strpos( $status, 'Other' ) !== false ) ) {
		if ( $status == 'Pending' && $gateway == 'offline' ) {
			// For offline payments, we do send notifications.
			$send = true;
		} else {
			// No messages sent while status is pending or for 'Other' statuses.
			$send = false;
		}
	}

	if ( $send ) {
		if ( $options['mt_html_email'] == 'true' ) {
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
		}

		// message to purchaser
		$body = apply_filters( 'mt_modify_email_body', $body, 'purchaser' );
		wp_mail( $email, $subject, $body, $headers );
		// message to admin
		$body2 = apply_filters( 'mt_modify_email_body', $body2, 'admin' );
		wp_mail( $options['mt_to'], $subject2, $body2, $headers );
		if ( $options['mt_html_email'] == 'true' ) {
			remove_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
		}
		update_post_meta( $id, '_notified', 'true' );
	}
}

/**
 * Draw template from event data. If My Calendar is installed, use the My Calendar template engine.
 *
 * @param $data
 * @param $template
 *
 * @return string
 */
function mt_draw_template( $data, $template ) {
	if ( function_exists( 'jd_draw_template' ) ) {
		return jd_draw_template( $data, $template );
	} else {
		$template = stripcslashes( $template );
		foreach ( $data as $key => $value ) {
			if ( is_object( $value ) && ! empty( $value ) ) {
				// null values return false...
			} else {
				if ( strpos( $template, "{" . $key ) !== false ) {
					if ( strpos( $template, "{" . $key . " " ) !== false ) { // only do preg_match if appropriate
						preg_match_all( '/{' . $key . '\b(?>\s+(?:before="([^"]*)"|after="([^"]*)"|format="([^"]*)")|[^\s]+|\s+){0,2}}/', $template, $matches, PREG_PATTERN_ORDER );
						if ( $matches ) {
							$before = @$matches[1][0];
							$after  = @$matches[2][0];
							$format = @$matches[3][0];
							if ( $format != '' ) {
								$value = date_i18n( stripslashes( $format ), strtotime( stripslashes( $value ) ) );
							}
							$value    = ( $value == '' ) ? '' : $before . $value . $after;
							$search   = @$matches[0][0];
							$template = str_replace( $search, $value, $template );
						}
					} else { // don't do preg match (never required for RSS)
						$template = stripcslashes( str_replace( "{" . $key . "}", $value, $template ) );
					}
				} // end {$key check				
			}
		}

		return stripslashes( trim( $template ) );
	}
}

/**
 * Return tickets to available ticket pool if Payment is refunded or trashed.
 *
 * @param $payment_id
 */
function mt_return_tickets( $payment_id ) {
	$purchases = get_post_meta( $payment_id, '_purchased' );
	if ( is_array( $purchases ) ) {
		foreach ( $purchases as $key => $value ) {
			foreach ( $value as $event_id => $purchase ) {
				$registration = get_post_meta( $event_id, '_mt_registration_options', true );
				foreach ( $purchase as $type => $ticket ) {
					// add ticket hash for each ticket
					$count                                   = $ticket['count'];
					$price                                   = $ticket['price'];
					$sold                                    = $registration['prices'][ $type ]['sold'];
					$new_sold                                = $sold - $count;
					$registration['prices'][ $type ]['sold'] = $new_sold;
					update_post_meta( $event_id, '_mt_registration_options', $registration );
					for ( $i = 0; $i < $count; $i ++ ) {
						// delete tickets from system.
						$ticket_id = mt_generate_ticket_id( $payment_id, $type, $i, $price );
						delete_post_meta( $event_id, '_ticket', $ticket_id );
						delete_post_meta( $event_id, '_' . $ticket_id );
					}
				}
			}
		}
		update_post_meta( $payment_id, '_returned', 'true' );
	}
}

add_action( 'mt_ticket_sales_closed', 'mt_notify_admin', 10, 1 );
/**
 * Send notification to admin when ticket sales are closed.
 *
 * @param $event
 */
function mt_notify_admin( $event ) {
	$event     = (int) $event;
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$email     = $options['mt_to'];
	$blogname  = get_option( 'blogname' );
	$headers[] = "From: $blogname Events <" . $options['mt_from'] . ">";
	$headers[] = "Reply-to: $options[mt_from]";
	apply_filters( 'mt_filter_email_headers', $headers, $event );
	$title    = get_the_title( $event );
	$download = admin_url( "admin.php?page=mt-reports&amp;event_id=$event&amp;format=csv&amp;mt-event-report=purchases" );
	$subject  = apply_filters( 'mt_closure_subject', sprintf( __( 'Ticket sales for %s are now closed', 'my-tickets' ), $title ), $event );
	$body     = apply_filters( 'mt_closure_body', sprintf( __( 'Online ticket sales for %1$s are now closed. <a href="%2$s">Download the ticket sales list now</a>', 'my-tickets' ), $title, $download ), $event );
	$to       = apply_filters( 'mt_closure_recipient', $email );
	if ( $options['mt_html_email'] == 'true' ) {
		add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
	}
	$body = apply_filters( 'mt_modify_email_body', $body, 'admin' );
	wp_mail( $to, $subject, $body, $headers );
	if ( $options['mt_html_email'] == 'true' ) {
		remove_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
	}
}