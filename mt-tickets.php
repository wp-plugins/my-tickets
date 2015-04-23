<?php

/* Functions related to Tickets printing/e-tickets */

add_filter( 'template_redirect', 'mt_ticket', 10, 1 );
/**
 * If ticket_id is set and valid, load ticket template. Else, redirect to purchase page.
 */
function mt_ticket() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$id      = ( $options['mt_tickets_page'] != '' ) ? $options['mt_tickets_page'] : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		if ( isset( $_GET['ticket_id'] ) && mt_verify_ticket( $_GET['ticket_id'] ) ) {
			if ( $template = locate_template( 'tickets.php' ) ) {
				load_template( $template );
			} else {
				load_template( dirname( __FILE__ ) . '/templates/tickets.php' );
			}
		} else {
			wp_safe_redirect( get_permalink( $options['mt_purchase_page'] ) );
		}
		exit;
	}
}

/**
 * Verify that ticket is valid. (Does not check whether ticket is for current or future event.)
 *
 * @param $ticket_id
 * @param string $return
 *
 * @return array|bool
 */
function mt_verify_ticket( $ticket_id = false, $return = 'boolean' ) {
	if ( $ticket_id ) {
		$ticket = mt_get_ticket( $ticket_id );
	} else {
		$ticket = mt_get_ticket();
	}
	if ( $ticket ) {
		$data        = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$purchase_id = $data['purchase_id'];
		$status      = get_post_meta( $purchase_id, '_is_paid', true );
		$gateway     = get_post_meta( $purchase_id, '_gateway', true );
		if ( $status == 'Completed' || ( $status == 'Pending' && $gateway == 'offline' ) ) {
			return ( $return == 'full' ) ? array( 'status' => true, 'ticket' => $ticket ) : true;
		}
	}

	return ( $return == 'full' ) ? array( 'status' => false, 'ticket' => false ) : false;
}

/**
 * Get ticket object for use in ticket template if ticket ID is set and valid.
 *
 * @param bool $ticket_id
 *
 * @return bool
 */
function mt_get_ticket( $ticket_id = false ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$ticket_id = isset( $_GET['ticket_id'] ) ? $_GET['ticket_id'] : $ticket_id;
	// sanitize ticket id
	$ticket_id = strtolower( preg_replace( "/[^a-z0-9\-]+/i", "", $ticket_id ) );
	$ticket    = false;
	if ( $ticket_id ) {
		$posts  = get_posts( array(
				'post_type'  => $options['mt_post_types'],
				'meta_key'   => '_ticket',
				'meta_value' => $ticket_id
			) );
		$ticket = ( isset( $posts[0] ) ) ? $posts[0] : false;
	}

	return $ticket;
}