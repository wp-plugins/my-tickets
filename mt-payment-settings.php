<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function mt_update_payment_settings( $post ) {
	if ( isset( $post['mt-payment-settings'] ) ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_use_sandbox      = ( isset( $post['mt_use_sandbox'] ) ) ? 'true' : 'false'; // Using sandbox?
		$mt_ssl              = ( isset( $post['mt_ssl'] ) ) ? 'true' : 'false'; // Using sandbox?
		$mt_members_discount = (int) preg_replace( '/\D/', '', $post['mt_members_discount'] ); // discount for members (percentage)
		$mt_currency         = $post['mt_currency'];
		$mt_phone            = ( isset( $post['mt_phone'] ) ) ? 'on' : 'off';

		$mt_default_gateway = ( isset( $post['mt_default_gateway'] ) ) ? $post['mt_default_gateway'] : 'offline'; // set default gateway
		$mt_gateway         = ( isset( $post['mt_gateway'] ) ) ? $post['mt_gateway'] : array( 'offline' ); // set enabled gateways
		// if a gateway is set as default that isn't enabled, enable it.
		if ( !( in_array( $mt_default_gateway, $mt_gateway ) ) ) {
			$mt_gateway[] = $mt_default_gateway;
		}
		$mt_gateways        = ( isset( $post['mt_gateways'] ) ) ? $post['mt_gateways'] : array();

		$mt_purchase_page = (int) $post['mt_purchase_page'];
		$mt_receipt_page  = (int) $post['mt_receipt_page'];
		$mt_tickets_page  = (int) $post['mt_tickets_page'];

		$settings = apply_filters( 'mt_settings', array(
			'mt_use_sandbox'      => $mt_use_sandbox,
			'mt_members_discount' => $mt_members_discount,
			'mt_currency'         => $mt_currency,
			'mt_phone'            => $mt_phone,
			'mt_gateway'          => $mt_gateway,
			'mt_default_gateway'  => $mt_default_gateway,
			'mt_gateways'         => $mt_gateways,
			'mt_ssl'              => $mt_ssl,
			'mt_purchase_page'    => $mt_purchase_page,
			'mt_receipt_page'     => $mt_receipt_page,
			'mt_tickets_page'     => $mt_tickets_page,
		), $_POST );
		$settings = array_merge( get_option( 'mt_settings' ), $settings );
		update_option( 'mt_settings', $settings );
		$messages = apply_filters( 'mt_payment_update_settings', '', $post );

		return "<div class=\"updated\"><p><strong>" . __( 'My Tickets Payment Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return '';
}

function mt_payment_settings() {
	$response = mt_update_payment_settings( $_POST );
	$options  = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults = mt_default_settings();
	$options  = array_merge( $defaults, $options );
	?>
	<div class="wrap my-tickets" id="mt_settings">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Event Registrations', 'my-tickets' ); ?></h2>
		<?php echo $response; ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<form method="post" action="<?php echo admin_url( "admin.php?page=mt-payment" ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets' ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h3><?php _e( 'Registration Payment Settings', 'my-tickets' ); ?></h3>

							<div class="inside">
								<ul>
									<li><label for="mt_currency"><?php _e( 'Currency:', 'my-tickets' ); ?></label>
										<?php $mt_currency_codes = array(
											"USD" => __( 'U.S. Dollars ($)', 'my-tickets' ),
											"EUR" => __( 'Euros (€)', 'my-tickets' ),
											"AUD" => __( 'Australian Dollars (A $)', 'my-tickets' ),
											"CAD" => __( 'Canadian Dollars (C $)', 'my-tickets' ),
											"GBP" => __( 'Pounds Sterling (£)', 'my-tickets' ),
											"JPY" => __( 'Yen (¥)', 'my-tickets' ),
											"NZD" => __( 'New Zealand Dollar ($)', 'my-tickets' ),
											"CHF" => __( 'Swiss Franc', 'my-tickets' ),
											"HKD" => __( 'Hong Kong Dollar ($)', 'my-tickets' ),
											"SGD" => __( 'Singapore Dollar ($)', 'my-tickets' ),
											"SEK" => __( 'Swedish Krona', 'my-tickets' ),
											"DKK" => __( 'Danish Krone', 'my-tickets' ),
											"PLN" => __( 'Polish Zloty', 'my-tickets' ),
											"NOK" => __( 'Norwegian Krone', 'my-tickets' ),
											"HUF" => __( 'Hungarian Forint', 'my-tickets' ),
											"ILS" => __( 'Israeli Shekel', 'my-tickets' ),
											"MXN" => __( 'Mexican Peso', 'my-tickets' ),
											"BRL" => __( 'Brazilian Real', 'my-tickets' ),
											"MYR" => __( 'Malaysian Ringgits', 'my-tickets' ),
											"PHP" => __( 'Philippine Pesos', 'my-tickets' ),
											"TWD" => __( 'Taiwan New Dollars', 'my-tickets' ),
											"THB" => __( 'Thai Baht', 'my-tickets' ),
											"TRY" => __( 'Turkish Lira', 'my-tickets' )
										);
										echo "<select name='mt_currency' id='mt_currency'>";
										foreach ( $mt_currency_codes as $code => $currency ) {
											$selected = ( $options['mt_currency'] == $code ) ? " selected='selected'" : "";
											echo "<option value='$code'$selected>$currency</option>";
										}
										echo "</select>";
										?>
									</li>
									<li>
										<label
											for="mt_members_discount"><?php _e( 'Member discount (%)', 'my-tickets' ); ?></label>
										<input type="number" name="mt_members_discount" id="mt_members_discount"
										       size="3" min='0' max='100'
										       value="<?php echo esc_attr( $options['mt_members_discount'] ); ?>"/>
									</li>
									<li>
										<label
											for="mt_phone"><?php _e( 'Require phone number on purchases', 'my-tickets' ); ?></label>
										<input type="checkbox" name="mt_phone" id="mt_phone" value="on" <?php echo checked( $options['mt_phone'], 'on' ); ?> />
									</li>
									<?php
										echo apply_filters( 'mt_payment_settings_fields', '', $options );
									?>
								</ul>
							</div>
						</div>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h3><?php _e( 'Payment Gateways', 'my-tickets' ); ?></h3>

							<div class="inside">
								<ul>
									<?php
									$default_selector = $pg_tabs = $payment_gateways = '';
									$mt_gateways      = mt_setup_gateways();
									foreach ( $mt_gateways as $gateway => $fields ) {
										$pg_settings     = '';
										$gateway_enabled = ( in_array( $gateway, $options['mt_gateway'] ) ) ? ' checked="checked"' : '';
										$default_selector .= "
				<li>
					<input type='checkbox' id='mt_gateway_$gateway' name='mt_gateway[]' value='$gateway'" . $gateway_enabled . " /> <label for='mt_gateway_$gateway'>$fields[label]</label>
				</li>";
										$settings = $fields['fields'];
										foreach ( $settings as $key => $label ) {
											$pg_settings .= "<li><label for='mt_$gateway-$key'>$label</label><br /> <input type='text' name='$mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . esc_attr( $options['mt_gateways'][ $gateway ][ $key ] ) . "' /></li>";
										}
										$pg_tabs .= "<li><a href='#$gateway'>" . sprintf( __( '%s settings', 'my-tickets' ), $fields['label'] ) . "</a></li>";
										$payment_gateways .= "
					<div class='wptab mt_$gateway' id='$gateway' aria-live='assertive'>
					<fieldset>
						<legend>$fields[label]</legend>
						<p><input type='radio' name='mt_default_gateway' id='mt_default_gateway_$gateway' value='$gateway'" . mt_is_checked( 'mt_default_gateway', $gateway, $options, true ) . " /> <label for='mt_default_gateway_$gateway'>" . __( 'Default gateway', 'my-tickets' ) . "</label></p>
							$pg_settings
					</fieldset>
					</div>";
									}
									echo "<li><fieldset><legend>" . __( 'Enabled Payment Gateways', 'my-tickets' ) . "</legend> $default_selector</fieldset>
			<div class='mt-tabs'>
				<ul class='tabs'>
					$pg_tabs
				</ul>
				$payment_gateways
			</div></li>";
									?>
								</ul>
								<ul>
									<li>
										<input type="checkbox" id="mt_use_sandbox"
										       name="mt_use_sandbox" <?php mt_is_checked( 'mt_use_sandbox', 'true', $options ); ?> />
										<label for="mt_use_sandbox"><?php _e( 'Testing mode (no payments will be processed)', 'my-tickets' ); ?></label>
									</li>
									<li>
										<input type="checkbox" id="mt_ssl" name="mt_ssl" value="true" <?php mt_is_checked( 'mt_ssl', 'true', $options ); ?> />
										<label for="mt_ssl"><?php _e( 'Use SSL for Payment pages.', 'my-tickets' ); ?></label><br/>
									</li>
								</ul>
								<fieldset>
									<legend><?php _e( 'My Tickets Payment and Ticket Handling Pages', 'my-tickets' ); ?></legend>
									<?php
									$current_purchase_page = ( is_numeric( $options['mt_purchase_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_purchase_page'] ) . "'>" . get_the_title( $options['mt_purchase_page'] ) . "</a>" ) : __( 'Not defined', 'my-tickets' );
									$current_receipt_page  = ( is_numeric( $options['mt_receipt_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_receipt_page'] ) . "'>" . get_the_title( $options['mt_receipt_page'] ) . "</a>" ) : __( 'Not defined', 'my-tickets' );
									$current_tickets_page  = ( is_numeric( $options['mt_tickets_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_tickets_page'] ) . "'>" . get_the_title( $options['mt_tickets_page'] ) . "</a>" ) : __( 'Not defined', 'my-tickets' );
									?>
									<ul>
										<li>
											<input type="text" size='6' class='suggest' id="mt_purchase_page"
											       name="mt_purchase_page"
											       value="<?php echo esc_attr( $options['mt_purchase_page'] ); ?>"
											       required aria-required="true"/> <label
												for="mt_purchase_page"><?php _e( 'Shopping cart', 'my-tickets' ); ?>
												<span class='new' aria-live="assertive"></span> <em
													class='current'><?php echo $current_purchase_page; ?></em></label><br/>
										</li>
										<li>
											<input type="text" size='6' class='suggest' id="mt_receipt_page"
											       name="mt_receipt_page"
											       value="<?php echo esc_attr( $options['mt_receipt_page'] ); ?>"
											       required aria-required="true"/> <label
												for="mt_receipt_page"><?php _e( 'Receipt page', 'my-tickets' ); ?> <span
													class='new' aria-live="assertive"></span> <em
													class='current'><?php echo $current_receipt_page; ?></em></label><br/>
										</li>
										<li>
											<input type="text" size='6' class='suggest' id="mt_tickets_page"
											       name="mt_tickets_page"
											       value="<?php echo esc_attr( $options['mt_tickets_page'] ); ?>"
											       required aria-required="true"/> <label
												for="mt_tickets_page"><?php _e( 'Tickets page', 'my-tickets' ); ?> <span
													class='new' aria-live="assertive"></span> <em
													class='current'><?php echo $current_tickets_page; ?></em></label><br/>
										</li>
									</ul>
								</fieldset>
							</div>
						</div>
					</div>
					<p><input type="submit" name="mt-payment-settings" class="button-primary"
					          value="<?php _e( 'Save Payment Settings', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets
}