<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function mt_update_ticketing_settings( $post ) {
	if ( isset( $post['mt-ticketing-settings'] ) ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_shipping         = ( isset( $post['mt_shipping'] ) ) ? $post['mt_shipping'] : 0;
		$mt_ticketing        = ( isset( $post['mt_ticketing'] ) ) ? $post['mt_ticketing'] : array();
		$mt_total_tickets        = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
		$mt_shipping_time    = ( isset( $post['mt_shipping_time'] ) ) ? $post['mt_shipping_time'] : '3-5';
		$defaults            = ( isset( $post['defaults'] ) ) ? $post['defaults'] : array();
		$labels              = ( isset( $post['mt_label'] ) ) ? $post['mt_label'] : array();
		$prices              = ( isset( $post['mt_price'] ) ) ? $post['mt_price'] : array();
		$availability        = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : array();
		$pricing_array       = mt_setup_pricing( $labels, $prices, $availability );
		$defaults['pricing'] = $pricing_array;
		$defaults['tickets'] = $mt_total_tickets;

		$settings = apply_filters( 'mt_settings', array(
			'defaults'         => $defaults,
			'mt_shipping'      => $mt_shipping,
			'mt_ticketing'     => $mt_ticketing,
			'mt_shipping_time' => $mt_shipping_time,
		), $_POST );
		$settings = array_merge( get_option( 'mt_settings' ), $settings );
		update_option( 'mt_settings', $settings );
		$messages = apply_filters( 'mt_ticketing_update_settings', '', $post );

		return "<div class=\"updated\"><p><strong>" . __( 'My Tickets Ticketing Defaults saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return false;
}

function mt_ticketing_settings() {
	$response = mt_update_ticketing_settings( $_POST );
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
				<form method="post" action="<?php echo admin_url( "admin.php?page=mt-ticketing" ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets' ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h3><?php _e( 'Ticketing Options', 'my-tickets' ); ?></h3>

							<div class="inside">
								<?php
									echo apply_filters( 'mt_ticketing_settings_fields', '', $options );
								?>
								<?php
								// array of ticket options. Need to also be registered as ticket action.
								$mt_ticketing = apply_filters( 'mt_registration_tickets_options', array(
										'printable' => __( 'Printable', 'my-tickets' ),
										'eticket'   => __( 'E-tickets', 'my-tickets' ),
										'postal'    => __( 'Postal Mail', 'my-tickets' ),
										'willcall'  => __( 'Pick up at box office', 'my-tickets' )
									) );
								$ticketing    = $options['mt_ticketing'];
								$form         = "<fieldset><legend>" . __( 'Ticketing Options', 'my-calendar' ) . "</legend>
				<ul class='ticket-type checkboxes'>";
								foreach ( $mt_ticketing as $type => $label ) {
									$checked = ( in_array( $type, array_keys( $ticketing ) ) ) ? ' checked="checked"' : '';
									$form .= "<li><label for='mt_tickets_$type'>$label</label> <input name='mt_ticketing[$type]' id='mt_tickets_$type' type='checkbox' value='" . esc_attr( $label ) . "' $checked /></li>";
								}
								$form .= "</ul>
		</fieldset>";
								// only show shipping field is postal mail ticket is selected.
								$shipping = $options['mt_shipping'];
								$form .= "<p class='shipping'>
					<label for='mt_shipping'>" . __( 'Shipping Cost for Postal Mail', 'my-tickets' ) . "</label> <input name='mt_shipping' id='mt_shipping' type='text' size='4' value='$shipping' />
			</p>";
								$shipping_time = $options['mt_shipping_time'];
								$form .= "<p class='shipping'>
					<label for='mt_shipping_time'>" . __( 'Approximate Shipping Time for Postal Mail (days)', 'my-tickets' ) . "</label> <input name='mt_shipping_time' id='mt_shipping_time' type='number' min='1' size='4' value='$shipping_time' />
			</p>
		</fieldset>";
								echo $form;
								?>
								<fieldset>
									<legend><?php _e( 'Default Ticketing Options', 'my-tickets' ); ?></legend>
									<p>
										<label
											for='reg_expires'><?php _e( 'Stop online sales <em>x</em> hours before event', 'my-tickets' ); ?></label>
										<input type='number' name='defaults[reg_expires]' id='reg_expires'
										       value='<?php esc_attr_e( $options['defaults']['reg_expires'] ); ?>'/>
									</p>

									<p>
										<label
											for='multiple'><?php _e( 'Allow multiple tickets/ticket type per purchaser', 'my-tickets' ); ?></label>
										<input type='checkbox' name='defaults[multiple]' id='multiple'
										       value='true' <?php echo ( $options['defaults']['multiple'] == 'true' ) ? ' checked="checked"' : ''; ?> />
									</p>
									<?php
									$type = $options['defaults']['sales_type'];
									if ( ! $type || $type == 'tickets' ) {
										$is_tickets      = ' checked="checked"';
										$is_registration = '';
									} else {
										$is_tickets      = '';
										$is_registration = ' checked="checked"';
									}
									$method = $options['defaults']['counting_method'];
									if ( $method == 'discrete' ) {
										$is_discrete   = ' checked="checked"';
										$is_continuous = '';
									} else {
										$is_discrete   = '';
										$is_continuous = ' checked="checked"';
									}
									echo mt_prices_table();
									?>
									<fieldset>
										<legend><?php _e( 'Type of Sale', 'my-tickets' ); ?></legend>
										<p>
											<input type='radio' name='defaults[sales_type]' id='mt_sales_type_tickets'
											       value='tickets'<?php echo $is_tickets; ?> /> <label
												for='mt_sales_type_tickets'><?php _e( 'Ticket Sales', 'my-tickets' ); ?></label><br/>
											<input type='radio' name='defaults[sales_type]'
											       id='mt_sales_type_registration'
											       value='registration'<?php echo $is_registration; ?> /> <label
												for='mt_sales_type_registration'><?php _e( 'Event Registration', 'my-tickets' ); ?></label>
										</p>
									</fieldset>
									<fieldset>
										<legend><?php _e( 'Ticket Counting Method', 'my-tickets' ); ?></legend>
										<p>
											<input type='radio' name='defaults[counting_method]'
											       id='mt_counting_method_discrete'
											       value='discrete',<?php echo $is_discrete; ?> /> <label
												for='mt_counting_method_discrete'><?php _e( 'Discrete - (Section A, Section B, etc.)', 'my-tickets' ); ?></label><br/>
											<input type='radio' name='defaults[counting_method]'
											       id='mt_counting_method_continuous'
											       value='continuous'<?php echo $is_continuous; ?> /> <label
												for='mt_counting_method_continuous'><?php _e( 'Continuous - (Adult, Child, Senior)', 'my-tickets' ); ?></label>
										</p>
									</fieldset>
							</div>
						</div>
					</div>
					<p><input type="submit" name="mt-ticketing-settings" class="button-primary"
					          value="<?php _e( 'Save Ticket Defaults', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets
}