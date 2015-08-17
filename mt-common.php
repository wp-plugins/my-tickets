<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// PHP functions common to all pro packages
function mt_check_license( $key = false ) {
	global $mt_version;
	if ( ! $key ) {
		return false;
	} else {
		//
		define( 'TICKETS_PLUGIN_LICENSE_URL', "https://www.joedolson.com/wp-content/plugins/files/license.php" );
		$response = wp_remote_post( TICKETS_PLUGIN_LICENSE_URL,
			array(
				'user-agent' => 'WordPress/My Tickets' . $mt_version . '; ' . get_bloginfo( 'url' ),
				'body'       => array( 'key' => $key, 'site' => urlencode( home_url() ) ),
				'timeout'    => 30
			) );
		if ( ! is_wp_error( $response ) || is_array( $response ) ) {
			$data = $response['body'];
			if ( ! in_array( $data, array( 'false', 'inuse', 'true', 'unconfirmed' ) ) ) {
				$data = @gzinflate( substr( $response['body'], 2 ) );
			}

			return $data;
		}
	}

	return false;
}

function mt_verify_key( $option, $name ) {
	$message = '';

	$key = strip_tags( $_POST[ $option ] );
	update_option( $option, $key );

	if ( $key != '' ) {
		$confirmation = mt_check_license( $key );
	} else {
		$confirmation = 'deleted';
	}
	$previously = get_option( $option . '_valid' );
	update_option( $option . '_valid', $confirmation );
	if ( $confirmation == 'false' ) {
		$message = sprintf( __( "That %s key is not valid.", 'my-tickets' ), $name );
	} else if ( $confirmation == 'expired' ) {
		$message = sprintf( __( 'Your %1$s key has expired. <a href="%2$s">Log in to renew</a>.', 'my-tickets' ), $name, 'https://www.joedolson.com/account/' );
	} else if ( $confirmation == 'inuse' ) {
		$message = sprintf( __( "%s license key already registered.", 'my-tickets' ), $name );
	} else if ( $confirmation == 'unconfirmed' ) {
		$message = sprintf( __( "Your payment for %s has not been confirmed.", 'my-tickets' ), $name );
	} else if ( $confirmation == 'true' ) {
		if ( $previously == 'true' ) {
		} else {
			$message = sprintf( __( "%s key validated. Enjoy!", 'my-tickets' ), $name );
		}
	} else if ( $confirmation == 'deleted' ) {
		$message = sprintf( __( "You have deleted your %s license key.", 'my-tickets' ), $name );
	} else {
		$message = sprintf( __( "%s received an unexpected message from the license server. Try again in a bit.", 'my-tickets' ), $name );
		delete_option( $option );
	}
	$message = ( $message != '' ) ? " $message " : $message; // just add a space
	return $message;
}