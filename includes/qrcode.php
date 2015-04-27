<?php
require_once( 'phpqrcode/qrlib.php' );
// get ticket ID from ticket template
$ticket = ( isset( $_GET['mt'] ) ) ? $_GET['mt'] : die( 'Invalid ticket ID' ); // ticket_ID
// sanitize ticket ID
$ticket = strtolower( preg_replace( "/[^a-z0-9\-]+/i", "", $ticket ) );
// get root URL from $_SERVER
$url = MT_HOME_URL . "?ticket_id=$ticket&action=mt-verify";
// generate QRcode
QRcode::png( $url, false, QR_ECLEVEL_H, 12 );