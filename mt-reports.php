<?php
/* Payments Page; display payment history */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Display reports screen.
 */
function mt_reports_page() {
	?>
	<div class='wrap my-tickets'>
	<h2><?php _e( 'My Tickets Reporting', 'my-tickets' ); ?></h2>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><?php _e( 'Reports on Ticket Sales and Registrations', 'my-tickets' ); ?></h3>

					<div class="inside">
						<?php
						if ( isset( $_POST['event_id'] ) && is_numeric( $_POST['event_id'] ) ) {
							if ( ! ( strip_tags( $_POST['mt_subject'] ) == '' || strip_tags( $_POST['mt_body'] == '' ) ) ) {
								mt_mass_email();
							}
						}
						if ( ! isset( $_GET['event_id'] ) ) {
							mt_generate_report_by_time();
						} else {
							if ( isset( $_GET['mt-event-report'] ) && $_GET['mt-event-report'] == 'tickets' ) {
								mt_generate_tickets_by_event();
							} else {
								mt_generate_report_by_event();
							}
							$event_id = (int) $_GET['event_id'];
							$report_type = ( isset( $_GET['mt-event-report'] ) && $_GET['mt-event-report'] == 'tickets' ) ? 'tickets' : 'purchases';
							$print_report_url = admin_url( 'admin.php?page=mt-reports&event_id=' . $event_id . '&mt-event-report=' . $report_type . '&format=view&mt_print=true' );
							echo '<p><a class="button" href="' . $print_report_url . '">' . __( 'Print this report', 'my-tickets' ) . '</a></p>';
						}
						?>
						<div class="mt-report-selector">
							<?php mt_choose_report_by_date(); ?>
							<?php mt_choose_report_by_event(); ?>
						</div>
						<div class='mt-email-purchasers'>
							<?php mt_email_purchasers(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php mt_show_support_box(); ?>
	</div><?php
}


/**
 * Generate a report of tickets on a single event.
 *
 * @param bool $event_id
 */
function mt_generate_tickets_by_event( $event_id = false, $return = false ) {
	if ( current_user_can( 'mt-view-reports' ) || current_user_can( 'manage_options' ) ) {
		$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : $event_id;
		if ( $event_id ) {
			$title        = get_the_title( $event_id );

			$output = "";

			$data           = mt_get_tickets( $event_id );
			$report         = $data['html'];
			$total_tickets  = count( $report );

			$table_top    = "<table class='widefat'><caption>" . sprintf( __( 'Tickets Purchased for &ldquo;%s&rdquo;', 'my-tickets' ), $title ) . "</caption>
						<thead>
							<tr>
								<th scope='col'>" . __( 'Ticket ID', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Ticket Type', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Purchase ID', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Price', 'my-tickets' ) . "</th>
							</tr>
						</thead>
						<tbody>";
			$table_bottom = "</tbody></table>";

			foreach ( $report as $row ) {
				$table_top .= $row;
			}
			$table = $table_top . $table_bottom;
			$output .= $table;
			if ( $return ) {
				return "<p class='totals'>" . sprintf( __( '%1$s tickets sold.', 'my-tickets' ), "<strong>$total_tickets</strong>" ) . "</p>" . $output;
			} else {
				echo "<p class='totals'>" . sprintf( __( '%1$s tickets sold.', 'my-tickets' ), "<strong>$total_tickets</strong>" ) . "</p>" . $output;
			}
		}
	} else {
		if ( $return ) {
			return false;
		} else {
			echo "<div class='updated error'><p>" . __( 'You do not have sufficient permissions to view ticketing reports.', 'my-tickets' ) . "</p></div>";
		}
	}
}


/**
 * Generate a report of payments on a single event.
 *
 * @param bool $event_id
 */
function mt_generate_report_by_event( $event_id = false, $return = false ) {
	if ( current_user_can( 'mt-view-reports' ) || current_user_can( 'manage_options' ) ) {
		$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : $event_id;
		if ( $event_id ) {
			$title        = get_the_title( $event_id );
			$tabs         = $out = '';
			$options      = ( isset( $_GET['options'] ) ) ? $_GET['options'] : array(
				'type'           => 'html',
				'output'         => 'payments',
				'include_failed' => true
			);
			$status_types = array(
				'completed' => __( 'Completed (%Completed)', 'my-tickets' ),
				'failed'    => __( 'Failed (%Failed)', 'my-tickets' ),
				'refunded'  => __( 'Refunded (%Refunded)', 'my-tickets' ),
				'pending'   => __( 'Pending (%Pending)', 'my-tickets' )
			);
			foreach ( $status_types as $type => $status_type ) {
				$tabs .= "<li><a href='#mt_$type'>$status_type</a></li>";
			}
			$output = "
				<div class='mt-tabs'>
					<ul class='tabs'>
						$tabs
					</ul>";

			$data           = mt_purchases( $event_id, $options );
			$report         = $data['report']['html'];
			$total_tickets  = $data['tickets'];
			$total_sales    = count( $data['report']['html']['Completed'] ) + count( $data['report']['html']['Pending'] );
			$total_income   = $data['income'];
			$custom_fields  = apply_filters( 'mt_custom_fields', array(), 'reports' );
			$custom_headers = '';
			foreach ( $custom_fields as $name => $field ) {
				$custom_headers .= "<th scope='col' class='mt_" . sanitize_title( $name ) . "'>" . $field['title'] . "</th>\n";
			}
			$table_top    = "<table class='widefat'><caption>" . sprintf( __( 'Purchase Records for &ldquo;%s&rdquo;', 'my-tickets' ), $title ) . "</caption>
						<thead>
							<tr>
								<th scope='col'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Notes', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Type', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Tickets', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Price', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Paid', 'my-tickets' ) . "</th>
								<th scope='col' id='mt_method' class='mt_method'>" . __( 'Ticket Method', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'Date', 'my-tickets' ) . "</th>
								<th scope='col'>" . __( 'ID', 'my-tickets' ) . "</th>
								$custom_headers
							</tr>
						</thead>
						<tbody>";
			$table_bottom = "</tbody></table>";

			foreach ( $report as $status => $rows ) {
				${$status} = '';
				$count     = count( $rows );
				$output    = str_replace( "%$status", $count, $output );
				foreach ( $rows as $type => $row ) {
					${$status} .= $row;
				}
				$out .= "<div class='wptab wp_" . strtolower( $status ) . "' id='mt_" . strtolower( $status ) . "' aria-live='assertive'>" . $table_top . ${$status} . $table_bottom . "</div>";
			}

			$output .= $out . "</div>";
			$total_line = "<p class='totals'>" . sprintf( __( '%1$s tickets sold in %3$s purchases. Total sales: %2$s', 'my-tickets' ), "<strong>$total_tickets</strong>", "<strong>" . apply_filters( 'mt_money_format', $total_income ) . "</strong>", "<strong>$total_sales</strong>" ) . "</p>";
			$custom_line = apply_filters( 'mt_custom_total_line_event', '', $event_id );
			if ( $return ) {
				return  $total_line . $custom_line . $output;
			} else {
				echo $total_line . $custom_line . $output;			}
		}
	} else {
		if ( $return ) {
			return false;
		} else {
			echo "<div class='updated error'><p>" . __( 'You do not have sufficient permissions to view sales reports.', 'my-tickets' ) . "</p></div>";
		}
	}
}

/**
 * Produce selector to choose report by event.
 *
 * @return void
 */
function mt_choose_report_by_event() {
	$selector = mt_select_events();
	$selected = ( isset( $_GET['format'] ) && $_GET['format'] == 'csv' ) ? " selected='selected'" : '';
	$form     = "
			<div class='report-by-event'>
				<h4>" . __( 'Report by Event', 'my-tickets' ) . "</h4>
				<form method='GET' action='" . admin_url( "admin.php?page=mt-reports" ) . "'>
					<div>
						<input type='hidden' name='page' value='mt-reports' />
					</div>
					<p>
					<label for='mt_select_event'>" . __( 'Select Event', 'my-tickets' ) . "</label>
					<select name='event_id' id='mt_select_event'>
						$selector
					</select>
					</p>
					<p>
					<label for='mt_select_event'>" . __( 'Select Report Type', 'my-tickets' ) . "</label>
					<select name='mt-event-report' id='mt_select_event'>
						<option value='tickets'>" . __( 'List of Tickets', 'my-tickets' ) . "</option>
						<option value='purchases'>" . __( 'List of Purchases', 'my-tickets' ) . "</option>
					</select>
					</p>
					<p>
					<label for='mt_select_format'>" . __( 'Report Format', 'my-tickets' ) . "</label>
					<select name='format' id='mt_select_format'>
						<option value='view'>" . __( 'View Report', 'my-tickets' ) . "</option>
						<option value='csv'$selected>" . __( 'Download CSV', 'my-tickets' ) . "</option>
					</select>
					</p>
					<p><input type='submit' name='mt-display-report' class='button-primary' value='" . __( 'Get Report by Event', 'my-tickets' ) . "' /></p>
				</form>
			</div>";
	echo $form;
}

/**
 * Display selector to choose report by date.
 *
 * @return void
 */
function mt_choose_report_by_date() {
	$selected = ( isset( $_GET['format'] ) && $_GET['format'] == 'csv' ) ? " selected='selected'" : '';
	$start    = ( isset( $_GET['mt_start'] ) ) ? $_GET['mt_start'] : date( 'Y-m-d', strtotime( '-1 month' ) );
	$end      = ( isset( $_GET['mt_end'] ) ) ? $_GET['mt_end'] : date( 'Y-m-d' );
	$form     = "
			<div class='report-by-date'>
				<h4>" . __( 'Sales Report by Date', 'my-tickets' ) . "</h4>
				<form method='GET' action='" . admin_url( "admin.php?page=mt-reports" ) . "'>
					<div>
						<input type='hidden' name='page' value='mt-reports' />
					</div>
					<p>
						<label for='mt_start'>" . __( 'Report Start Date', 'my-tickets' ) . "</label>
						<input type='date' name='mt_start' id='mt_start' value='$start' />
					</p>
					<p>
						<label for='mt_end'>" . __( 'Report End Date', 'my-tickets' ) . "</label>
						<input type='date' name='mt_end' id='mt_end' value='$end' />
					</p>
					<p>
						<label for='mt_select_format'>" . __( 'Report Format', 'my-tickets' ) . "</label>
						<select name='format' id='mt_select_format'>
							<option value='view'>" . __( 'View Report', 'my-tickets' ) . "</option>
							<option value='csv'$selected>" . __( 'Download CSV', 'my-tickets' ) . "</option>
						</select>
					</p>
					<p><input type='submit' name='mt-display-report' class='button-primary' value='" . __( 'Get Report by Date', 'my-tickets' ) . "' /></p>
				</form>
			</div>";
	echo $form;
}

/**
 * Produce form to choose event for mass emailing purchasers.
 */
function mt_email_purchasers() {
	$selector = mt_select_events();
	$form     = "
		<h4>" . __( 'Email Purchasers of Tickets by Event', 'my-tickets' ) . "</h4>
		<form method='POST' action='" . admin_url( "admin.php?page=mt-reports" ) . "'>
			<p>
			<label for='mt_select_event_for_email'>" . __( 'Select Event', 'my-tickets' ) . "</label>
			<select name='event_id' id='mt_select_event_for_email'>
				$selector
			</select>
			</p>
			<p>
			<label for='mt_subject'>" . __( 'Email Subject', 'my-tickets' ) . "</label><br />
			<input type='text' name='mt_subject' id='mt_subject' size='40' />
			</p>
			<p>
			<label for='mt_body' id='body_label'>" . __( 'Email Body', 'my-tickets' ) . "</label><br />
			<textarea name='mt_body' id='mt_body' cols='60' rows='12' aria-labelledby='body_label body_description'></textarea><br />
			<span id='body_description'>" . __( 'Use <code>{name}</code> to insert the recipient\'s name', 'my-tickets' ) . "</span>
			</p>
			<p><input type='submit' name='mt-email-purchasers' class='button-primary' value='" . __( 'Send Email', 'my-tickets' ) . "' /></p>
		</form>";
	echo $form;
}

/**
 * Select events with event sales data. (If no sales, not returned.)
 *
 * @return string
 */
function mt_select_events() {
	// fetch posts with meta data for event sales
	$settings = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	// add time query to this query after timestamp field has been in place for a few months.
	// only show limit of 50 events.
	$args    =
		array(
			'post_type'   => $settings['mt_post_types'],
			'posts_per_page'  => apply_filters( 'mt_select_events_count', 50 ),
			'post_status' => array( 'publish', 'draft', 'private' ),
			'meta_query'  => array(
				'relation' => 'AND',
				'queries'  => array(
					'key'     => '_ticket',
					'compare' => 'EXISTS'
				)
			)
		);
	$query   = new WP_Query( $args );
	$posts   = $query->posts;
	$options = '<option value="false"> --- </option>';
	foreach ( $posts as $post ) {
		$tickets  = get_post_meta( $post->ID, '_ticket' );
		$count    = count( $tickets );
		$selected = ( isset( $_GET['event_id'] ) && $_GET['event_id'] == $post->ID ) ? ' selected="selected"' : '';
		$event_data = get_post_meta( $post->ID, '_mc_event_data', true );
		$event_date = strtotime( $event_data['event_begin'] );
		$display_date = date_i18n( get_option( 'date_format' ), $event_date );
		// if this event happened more than a month ago, don't show in list *unless* it's the currently selected report.
		$report_age_limit = apply_filters( 'mt_reports_age_limit', current_time( 'timestamp' ) - 31*24*60*60 );
		if ( $event_date > $report_age_limit || $selected == ' selected="selected"' ) {
			$options .= "<option value='$post->ID'$selected>$post->post_title ($count); $display_date</option>\n";
		}
	}

	return $options;
}

/**
 * Return array of formatted purchase data for use in reports by event ID.
 *
 * @param $event_id
 * @param array $options
 *
 * @return array
 */
function mt_purchases( $event_id, $options = array( 'include_failed' => false ) ) {
	if ( $event_id == 'false' ) exit;
	$query        = get_post_meta( $event_id, '_purchase' );
	$report       = array(
		'html' => array( 'Completed' => array(), 'Pending' => array(), 'Refunded' => array(), 'Failed' => array() ),
		'csv'  => array( 'Completed' => array(), 'Pending' => array(), 'Refunded' => array(), 'Failed' => array() )
	);
	$total_income = $total_tickets = 0;
	$alternate    = 'even';
	foreach ( $query as $payment ) {
		foreach ( $payment as $purchase_id => $details ) {
			if ( get_post_status( $purchase_id ) != 'publish' ) {
				continue;
			}
			$status      = get_post_meta( $purchase_id, '_is_paid', true );
			$ticket_type = get_post_meta( $purchase_id, '_ticketing_method', true );
			$notes       = esc_html( get_post_meta( $purchase_id, '_notes', true ) );
			if ( $options['include_failed'] == false && ( $status == 'Failed' || $status == 'Refunded' ) ) {
				continue;
			}
			foreach ( $details as $type => $tickets ) {
				$count         = $details[ $type ]['count'];
				if ( $count > 0 ) {
					$purchaser  = get_the_title( $purchase_id );
					$first_name = get_post_meta( $purchase_id, '_first_name', true );
					$last_name  = get_post_meta( $purchase_id, '_last_name', true );
					$email      = get_post_meta( $purchase_id, '_email', true );
					if ( ! $first_name || ! $last_name ) {
						$name       = explode( ' ', $purchaser );
						$first_name = $name[0];
						$last_name  = end( $name );
					}
					$date          = get_the_time( 'Y-m-d', $purchase_id );
					$time          = get_the_time( get_option( 'time_format' ), $purchase_id );
					$transaction   = get_post_meta( $purchase_id, '_transaction_data', true );
					$address       = ( isset( $transaction['shipping'] ) ) ? $transaction['shipping'] : false;
					$phone         = get_post_meta( $purchase_id, '_phone', true );
					$fee           = ( isset( $transaction['fee'] ) ) ? $transaction['fee'] : false;

					$street        = ( isset( $address['street'] ) ) ? $address['street'] : '';
					$street2       = ( isset( $address['street2'] ) ) ? $address['street2'] : '';
					$city          = ( isset( $address['city'] ) ) ? $address['city'] : '';
					$state         = ( isset( $address['state'] ) ) ? $address['state'] : '';
					$code          = ( isset( $address['code'] ) ) ? $address['code'] : '';
					$country       = ( isset( $address['country'] ) ) ? $address['country'] : '';
					$datetime      = "$date<br />$time";
					$price         = $details[ $type ]['price'];
					$subtotal      = $count * $price;
					$paid          = ( $status == 'Completed' ) ? $subtotal : 0;
					$total_income  = $total_income + $paid;
					$total_tickets = $total_tickets + $count;
					$class         = esc_attr( strtolower( $ticket_type ) );
					$custom_fields = apply_filters( 'mt_custom_fields', array(), 'reports' );
					$custom_cells  = $custom_csv = '';
					foreach ( $custom_fields as $name => $field ) {
						$value = get_post_meta( $purchase_id, $name, true );
						if ( is_array( $value ) ) {
							$value = implode( ';', $value );
						}
						$value = apply_filters( 'mt_format_report_field', $value, get_post_meta( $purchase_id, $name, true ), $purchase_id, $name );

						$custom_cells .= "<td class='mt_" . sanitize_title( $name ) . "'>$value</td>\n";
						$custom_csv .= ",\"$value\"";
					}
					$alternate = ( $alternate == 'alternate' ) ? 'even' : 'alternate';
					$row       = "<tr class='$alternate'><th scope='row'>$purchaser</th><td>$notes</td><td>$type</td><td>$count</td><td>" . apply_filters( 'mt_money_format', $price ) . "</td><td>" . apply_filters( 'mt_money_format', $paid ) . "</td><td class='mt_ticket_type'><span class='mt $class'>$ticket_type</span></td><td>$datetime</td><td>$purchase_id</td>$custom_cells</tr>";
					// add split field to csv headers
					$csv                         = "\"$last_name\",\"$first_name\",\"$email\",\"$type\",\"$count\",\"$price\",\"$paid\",\"$fee\",\"$ticket_type\",\"$date\",\"$time\",\"$phone\",\"$street\",\"$street2\",\"$city\",\"$state\",\"$code\",\"$country\"$custom_csv" . PHP_EOL;
					$report['html'][ $status ][] = $row;
					$report['csv'][ $status ][]  = $csv;
				}
			}
		}
	}

	return array( 'report' => $report, 'income' => $total_income, 'tickets' => $total_tickets );
}

/**
 * Function to produce a list of tickets for a given event.
 *
 * @param $event_id  integer
 *
 * @return array
 */
function mt_get_tickets( $event_id ) {
	$query = get_post_meta( $event_id, '_ticket' );
	$report = array( 'html'=>array(), 'csv'=>array() );
	$alternate = 'even';
	foreach ( $query as $ticket_id ) {
		$ticket                      = get_post_meta( $event_id, '_'.$ticket_id, true );
		$purchase_id                 = $ticket['purchase_id'];
		$type                        = $ticket['type'];
		$price                       = $ticket['price'];
		$purchaser                   = get_the_title( $purchase_id );
		$first_name                  = get_post_meta( $purchase_id, '_first_name', true );
		$last_name                   = get_post_meta( $purchase_id, '_last_name', true );
		if ( !$first_name || !$last_name ) {
			$name = explode( ' ', $purchaser );
			$first_name = $name[0];
			$last_name = end( $name );
		}
		$alternate                   = ( $alternate == 'alternate' ) ? 'even' : 'alternate';
		$row                         = "<tr class='$alternate'><th scope='row'>$ticket_id</th><td>$type</td><td>$purchaser</td><td><a href='" . get_edit_post_link( $purchase_id ) . "'>$purchase_id</a></td><td>" . apply_filters( 'mt_money_format', $price ) . "</td></tr>";
		// add split field to csv headers
		$csv                         = "\"$ticket_id\",\"$last_name\",\"$first_name\",\"$type\",\"$purchase_id\",\"$price\"".PHP_EOL;
		$report['html'][] = $row;
		$report['csv'][]  = $csv;
	}

	return $report;
}

add_action( 'admin_init', 'mt_printable_report' );
/**
 * View printable version of table report.
 */
function mt_printable_report() {
	if ( isset( $_GET['mt_print'] ) ) {
		$report = apply_filters( 'mt_printable_report', false );
		$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : false;
		if ( !$event_id && !$report ) {
			exit;
		}
		if ( !$report ) {
			if ( isset( $_GET['mt-event-report'] ) && $_GET['mt-event-report'] == 'tickets' ) {
				$report = mt_generate_tickets_by_event( $event_id, true );
			} else {
				$report = mt_generate_report_by_event( $event_id, true );
			}
		}
		$stylesheet_path = apply_filters( 'mt_printable_report_css', plugins_url( 'css/report.css', __FILE__ ) );
		$back_url = admin_url( apply_filters( 'mt_printable_report_back', 'admin.php?page=mt-reports' ) );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
		<meta name="viewport" content="width=device-width" />
		<title><?php _e( 'Printable Sales Report', 'my-tickets' ); ?></title>
	    <link href="<?php echo $stylesheet_path; ?>" type="text/css" media="print,screen" rel="stylesheet" />
	</head>
	<body>
		<a class='mt-back' href="<?php echo $back_url; ?>"><?php _e( 'Return to My Tickets Reports', 'my-tickets' ); ?></a>
		<?php echo $report; ?>
	</body>
</html>
<?php
		exit;
	}
}

add_action( 'admin_init', 'mt_download_csv_event' );
/**
 * Download report of event data as CSV
 */
function mt_download_csv_event() {
	if (
		isset( $_GET['format'] ) && $_GET['format'] == 'csv' &&
		isset( $_GET['page'] ) && $_GET['page'] == 'mt-reports' &&
		isset( $_GET['event_id'] ) &&
		isset( $_GET['mt-event-report'] ) && $_GET['mt-event-report'] == 'purchases'
	) {
		$event_id        = intval( $_GET['event_id'] );
		$title           = get_the_title( $event_id );
		$purchases       = mt_purchases( $event_id );
		$report          = $purchases['report']['csv'];
		$custom_headings = '';
		$custom_fields   = apply_filters( 'mt_custom_fields', array(), 'reports' );
		foreach ( $custom_fields as $name => $field ) {
			$custom_headings .= ",\"$name\"";
		}
		$csv = __( 'First Name', 'my-tickets' ) . ","
		       . __( 'Last Name', 'my-tickets' ) . ","
		       . __( 'Email', 'my-tickets' ) . ","
		       . __( 'Ticket Type', 'my-tickets' ) . ","
		       . __( 'Purchased', 'my-tickets' ) . ","
		       . __( 'Price', 'my-tickets' ) . ","
		       . __( 'Paid', 'my-tickets' ) . ","
		       . __( 'Fees', 'my-tickets' ) . ","
 		       . __( 'Ticket Method', 'my-tickets' ) . ","
		       . __( 'Date', 'my-tickets' ) . ","
		       . __( 'Time', 'my-tickets' ) . ","
		       . __( 'Phone', 'my-tickets' ) . ","
		       . __( 'Street', 'my-tickets' ) . ","
		       . __( 'Street (2)', 'my-tickets' ) . ","
		       . __( 'City', 'my-tickets' ) . ","
		       . __( 'State', 'my-tickets' ) . ","
		       . __( 'Postal Code', 'my-tickets' ) . ","
		       . __( 'Country', 'my-tickets' )
		       . $custom_headings . PHP_EOL;
		foreach ( $report as $status => $rows ) {
			foreach ( $rows as $type => $row ) {
				$csv .= $row;
			}
		}
		$title = sanitize_title( $title ) . '-' . date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo $csv;
		exit;
	}
}

add_action( 'admin_init', 'mt_download_csv_tickets' );
/**
 * Download report of ticket data for an event as CSV
 */
function mt_download_csv_tickets() {
	if (
			isset( $_GET['format'] ) && $_GET['format'] == 'csv' &&
			isset( $_GET['page'] ) && $_GET['page'] == 'mt-reports' &&
			isset( $_GET['event_id'] ) &&
			isset( $_GET['mt-event-report'] ) && $_GET['mt-event-report'] == 'tickets'
	) {
		$event_id        = intval( $_GET['event_id'] );
		$title           = get_the_title( $event_id ). ' tickets';
		$tickets         = mt_get_tickets( $event_id );
		$report          = $tickets['csv'];
		$csv = __( 'Ticket ID', 'my-tickets' ) . "," . __( 'Ticket Type', 'my-tickets' ) . "," . __( 'First Name', 'my-tickets' ) . "," . __( 'Last Name', 'my-tickets' ) . "," . __( 'Purchase ID', 'my-tickets' ) . "," . __( 'Price', 'my-tickets' ) . PHP_EOL;
		foreach ( $report as $row ) {
			$csv .= "$row";
		}
		$title = sanitize_title( $title ) . '-' . date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo $csv;
		exit;
	}
}


add_action( 'admin_init', 'mt_download_csv_time' );
/**
 * Download report by sales period as CSV.
 */
function mt_download_csv_time() {
	$output = '';
	if ( isset( $_GET['format'] ) && $_GET['format'] == 'csv' && isset( $_GET['page'] ) && $_GET['page'] == 'mt-reports' && isset( $_GET['mt_start'] ) ) {
		$report = mt_get_report_data_by_time();
		$csv    = $report['csv'];
		$start  = $report['start'];
		$end    = $report['end'];
		foreach ( $csv as $row ) {
			$output .= "$row";
		}
		$title = sanitize_title( $start . '_' . $end ) . '-' . date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo $output;
		exit;
	}
}

/**
 * Get report data for reports by time period.
 *
 * @param $start
 * @param $end
 *
 * @return array
 */
function mt_get_report_by_time( $start, $end ) {
	$posts_per_page = -1;
	if ( $start == date( 'Y-m-d', strtotime( apply_filters( 'mt_default_report_start_date', '-1 week' ) ) ) && $end == date( 'Y-m-d' ) ) {
		$posts_per_page = 50;
	}

	$args  =
		array(
			'post_type'      => 'mt-payments',
			'post_status'    => array( 'publish' ),
			'date_query'     => array(
				'after'     => $start,
				'before'    => $end,
				'inclusive' => true
			),
			'posts_per_page' => $posts_per_page
		);
	$query = new WP_Query( $args );
	$posts = $query->posts;

	return $posts;
}

/**
 * Return data from report by time.
 *
 * @return mixed
 */
function mt_get_report_data_by_time() {
	$start     = ( isset( $_GET['mt_start'] ) ) ? $_GET['mt_start'] : date( 'Y-m-d', strtotime( apply_filters( 'mt_default_report_start_date', '-1 week' ) ) );
	$end       = ( isset( $_GET['mt_end'] ) ) ? $_GET['mt_end'] : date( 'Y-m-d' );
	$posts     = mt_get_report_by_time( $start, $end );
	$total     = 0;
	$alternate = 'even';
	$html = $csv = array();
	foreach ( $posts as $post ) {
		$purchaser    = get_the_title( $post->ID );
		$first_name    = get_post_meta( $post->ID, '_first_name', true );
		$last_name     = get_post_meta( $post->ID, '_last_name', true );
		if ( !$first_name && !$last_name ) {
			$name = explode( ' ', $purchaser );
			$first_name = $name[0];
			$last_name = end( $name );
		}
		$value        = get_post_meta( $post->ID, '_total_paid', true );
		$format_value = apply_filters( 'mt_money_format', $value );
		$total        = $total + $value;
		$status       = get_post_meta( $post->ID, '_is_paid', true );
		$purchased    = get_post_meta( $post->ID, '_purchased' );
		$titles       = array();
		foreach ( $purchased as $purchase ) {
			foreach ( $purchase as $event => $purch ) {
				$post_type = get_post_type( $event );
				if ( $post_type == 'mc-events' ) {
					$mc_event = get_post_meta( $event, '_mc_event_id', true );
					$url      = admin_url( 'admin.php?page=my-calendar&amp;mode=edit&amp;event_id=' . $mc_event );
				} else {
					$url = admin_url( "post.php?post=$event&amp;action=edit" );
				}
				$titles[]     = "<a href='$url'>" . get_the_title( $event ) . "</a>";
				$raw_titles[] = get_the_title( $event );
			}
		}
		$events           = implode( ', ', $titles );
		$raw_events       = implode( ', ', $titles );
		$alternate        = ( $alternate == 'alternate' ) ? 'even' : 'alternate';
		$html[] = "<tr class='$alternate'><td>$purchaser</td><td>$format_value</td><td>$status</td><td>$events</td></tr>\n";
		$csv[]  = "\"$first_name\",\"$last_name\",\"$value\",\"$status\",\"$raw_events\"" . PHP_EOL;
	}
	$report['html']  = $html;
	$report['csv']   = $csv;
	$report['total'] = $total;
	$report['start'] = $start;
	$report['end']   = $end;

	return $report;
}

/**
 * Print report by time to screen.
 */
function mt_generate_report_by_time() {
	$report    = mt_get_report_data_by_time();
	if ( is_array( $report ) && ! empty( $report ) ) {
		$purchases = $report['html'];
		$total     = $report['total'];
		$start     = $report['start'];
		$end       = $report['end'];

		echo "<h4>" . sprintf( __( 'Sales from %1$s to %2$s', 'my-tickets' ), $start, $end ) . "</h4>";
		echo "<table class='widefat'>
			<thead>
				<tr>
					<th scope='col'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
					<th scope='col'>" . __( 'Purchase Value', 'my-tickets' ) . "</th>
					<th scope='col'>" . __( 'Status', 'my-tickets' ) . "</th>
					<th scope='col'>" . __( 'Events', 'my-tickets' ) . "</th>
				</tr>
			</thead>
			<tbody>";
		if ( is_array( $purchases ) && ! empty( $purchases ) ) {
			foreach ( $purchases as $row ) {
				echo $row;
			}
		}
		echo "</tbody>
		</table>";
		printf( "<p>" . __( 'Total sales in period: %s', 'my-tickets' ) . "</p>", "<strong>" . apply_filters( 'mt_money_format', $total ) . "</strong>" );
		$custom_line = apply_filters( 'mt_custom_total_line_time', '', $start, $end );
		echo $custom_line;
	} else {
		echo "<p>" . __( 'No sales in period.', 'my-tickets' ) . "</p>";
	}
}

/**
 * Return a list of purchasers names/emails for use in mass emailing
 *
 * @param $event_id
 *
 * @return array
 */
function mt_get_purchasers( $event_id ) {
	$query    = get_post_meta( $event_id, '_purchase' );
	$contacts = array();
	if ( is_array( $query ) ) {
		foreach ( $query as $payment ) {
			foreach ( $payment as $purchase_id => $details ) {
				if ( get_post_status( $purchase_id ) != 'publish' ) {
					continue;
				}
				$status = get_post_meta( $purchase_id, '_is_paid', true );
				// only send email to Completed payments
				if ( $status == 'Failed' || $status == 'Refunded' || $status == 'Pending' ) {
					continue;
				}
				foreach ( $details as $type => $tickets ) {
					$purchaser  = get_the_title( $purchase_id );
					$email      = get_post_meta( $purchase_id, '_email', true );
					$opt_out    = get_post_meta( $purchase_id, '_opt_out', true );
					$contacts[] = array(
						'purchase_id' => $purchase_id,
						'opt_out'     => $opt_out,
						'name'        => $purchaser,
						'email'       => $email
					);
				}
			}
		}
	}

	return $contacts;
}

/**
 * Send mass email to purchasers of event.
 *
 * @param bool $event_id
 */
function mt_mass_email( $event_id = false ) {
	if ( ! $event_id ) {
		$event_id = ( isset( $_POST['event_id'] ) ) ? (int) $_POST['event_id'] : false;
	}
	if ( $event_id ) {
		$event       = get_the_title( $event_id );
		$options     = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$purchasers  = mt_get_purchasers( $event_id );
		$count       = count( $purchasers );
		$emails_sent = $opt_outs = 0;
		$blogname    = get_option( 'blogname' );
		$headers[]   = "From: $blogname Events <" . $options['mt_from'] . ">";
		$headers[]   = "Reply-to: $options[mt_from]";
		$body        = stripslashes( $_POST['mt_body'] );
		$subject     = stripslashes( $_POST['mt_subject'] );
		foreach ( $purchasers as $purchaser ) {
			if ( $purchaser['opt_out'] != 'true' ) {
				$purchase_id = $purchaser['purchase_id'];
				$opt_out_url = add_query_arg( 'opt_out', $purchase_id, home_url() );
				$opt_out     = PHP_EOL . PHP_EOL . "<p><small>" . sprintf( __( "Don't want to receive email from us? Follow this link: %s", 'my-tickets' ), $opt_out_url ) . "</small></p>";
				$to          = $purchaser['email'];
				$subject     = str_replace( '{name}', $purchaser['name'], $subject );
				$body        = str_replace( '{name}', $purchaser['name'], $body );
				if ( $options['mt_html_email'] == 'true' ) {
					add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
					$body = wpautop( $body . $opt_out );
				} else {
					$body = strip_tags( $body . $opt_out );
				}
				$body = apply_filters( 'mt_modify_email_body', $body, 'purchaser' );

				wp_mail( $to, $subject, $body, $headers );
				$emails_sent ++;

				if ( $options['mt_html_email'] == 'true' ) {
					remove_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
				}
			} else {
				$opt_outs ++;
			}
		}
		echo "<div class='updated'><p>" . sprintf( __( '%1$d/%2$d purchasers of tickets for "%4$s" have been emailed. %3$d/%2$d purchasers have opted out.', 'my-tickets' ), $emails_sent, $count, $opt_outs, $event ) . "</p></div>";
	}
}

add_action( 'template_include', 'mt_opt_out' );
/**
 * Receive opt-out data so purchasers can opt out of receiving email.
 *
 * @param $template
 *
 * @return string
 */
function mt_opt_out( $template ) {
	if ( isset( $_GET['opt_out'] ) && is_numeric( $_GET['opt_out'] ) ) {
		$post_id = (int) $_GET['opt_out'];
		update_post_meta( $post_id, '_opt_out', 'true' );
		if ( $template = locate_template( 'opt_out.php' ) ) {
			return $template;
		} else {
			return dirname( __FILE__ ) . '/templates/opt_out.php';
		}
	}

	return $template;
}