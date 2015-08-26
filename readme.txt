=== My Tickets ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: events, ticket sales, tickets, ticketing, registration, reservations, event tickets, sell tickets, event registration, box office
Requires at least: 4.0
Tested up to: 4.3.0
License: GPLv2 or later
Stable tag: 1.2.5

My Tickets is an easy-to-use, flexible platform for selling event tickets with WordPress.

== Description ==

My Tickets integrates with <a href="http://wordpress.org/plugins/my-calendar/">My Calendar</a> or operates as a stand-alone ticket sales platform. Sell tickets for box office pick up, shipping, or accept print-at-home and e-tickets for an easy experience for your ticket holders!

The default My Tickets plug-in integrates with PayPal Standard payments, so you can sell tickets immediately. You can also take offline payments, to use My Tickets as a reservation platform.

More premium add-ons are being developed, but you can purchase the <a href="https://www.joedolson.com/my-tickets/add-ons/">Authorize.net payment gateway</a> plug-in today!

= Basic Features: =

For the purchaser:

* Create an account to save your address and shipping preferences
* Automatically converts shopping carts to your account if you log-in after adding purchases
* Buy tickets for multiple events, with multiple ticket types.

For the seller:

* Reports on ticket sales by time or event
* Easily add new ticket sales from the box office, when somebody pays by phone or mail.
* Use your mobile phone and a standard QRCode reader to verify print-at-home tickets or e-tickets
* Send email to a single purchaser with questions about their ticket purchase, or mass email all purchasers for an event.
* Define either continuous (Adult, Student, Child) or discrete (Section 1, Section 2, Section 3) ticket classes for any event
* Offer member-only discounts for your registered members

My Tickets is hugely flexible, and a library of add-ons to add new gateways and features is in development!

Check out the <a href="http://docs.joedolson.com/my-tickets/">online documentation</a>.

= Translations =

Dutch, Norwegian (Bokmal)

== Installation ==

1. Upload the `/my-tickets/` directory into your WordPress plugins directory.

2. Activate the plugin on your WordPress plugins page

3. Configure My Tickets using the following pages in the admin panel:

   My Tickets -> Settings
   My Tickets -> Payment Settings
   My Tickets -> Ticket Settings
   My Tickets -> Reports
   My Tickets -> Ticketing Help

4. With My Calendar, add ticket parameters to an event. Without My Calendar, choose what post types will support tickets from My Tickets -> Settings, and add ticket parameters to any post or Page!

== Changelog ==

= Future =

* Improve options when there are multiple dates available for a specific event. Multiple ticket patterns w/separate pricing & availability options, etc.? Note accessibility features?
* Add 'Reservation' as payment status (admin-only by default)
* Add 'Waiting List' as payment status (admin-only by default)
* Add option to use radio buttons instead of checkboxes

= 1.2.5 =

* Bug fix: Switch esc_url to esc_url_raw on QR Code URL so parameters will be followable. [Broken in 1.2.2]

= 1.2.4 =

* There was an incomplete SVN commit on 1.2.3 that caused update issues. This enables people with "this isn't really 1.2.3" to update.

= 1.2.3 =

* Moved logic that switched to offline gateway if total = 0
* Only display login link if public registration is enabled
* Fallback function for money_format(), since that function is not supported on Windows
* Clearly label complementary tickets as admin only
* Delete My Tickets pages on uninstall
* Move focus to Cart link when Add to Cart

= 1.2.2 =

* Allow tickets to be sold on posts with status 'private'
* Show event date in reports drop down
* Revised HTML so it's easy to hide remaining tickets notices
* Prevent user from increasing number of tickets in cart to more than available.
* Add filter to enable max ticket limit per purchase/type for an event. 'mt_max_sale_per_event'
* Add fees field to reports output in addition to sales values.
* Add filter on cart total used to determine whether or not to show payment gateway form.

= 1.2.1 =

* Re-send email and ticket delivery status checkboxes would not uncheck.
* Invalid Ticket URLs sent in notification messages.

= 1.2.0 =

* Modify reports to include notes field & payment ID.
* Modify default report view to only show maximum of most recent 50 payments.
* Modify reports to remove payment status, since status reports are displayed based on status.
* Added delivery checkbox to Payment to indicate whether tickets on that payment have been delivered.
* Added option to copy front-end cart into admin payment when logged-in as user with appropriate permissions.
* Added ability to view processing errors on payments
* Added ability to add complementary tickets through admin.
* Added template tag for ticket_ids
* Added function to get purchaser name for ticket templates.
* Added date to title output in default template for [tickets] shortcode.
* Added view of [ticket] shortcode after editing event details in registration form panel.
* Improved handling of PayPal IPN errors.
* Bug fix: Some broken currency symbols.
* Bug fix: Reports list was limited to 10 events.
* Translation: Polish

= 1.1.0 =
* New option: pull ticket image from event featured image instead of ticket page featured image.
* Feature: Include email address in CSV report format
* Security: Double-verify that the price paid by gateway matches price expected.
* Added link to receipt in Payment record
* New filter: customize text displayed for ticket prices.
* New filter: add custom template tags for ticketing form output.
* New filter: alter default number of tickets
* New template tag: return purchase ID in notifications.
* New template tag: return purchase edit URL in notifications.
* Bug fix: Prevent notices on invalid events
* Bug fix: If a user submitted two payments in a row, 2nd payment might not be recorded.
* Bug fix: Ticket IDs were generated using purchase ID only, so multiple tickets on same purchase had same ID.
* Bug fix: QR Code URL is incorrect if site not rendered at domain root
* Minor CSS change in default cart CSS.
* Translation: Norwegian (Bokmal)

= 1.0.7 =

* Bug fix: Unassigned variable after filter.
* Bug fix: Don't throw errors if invalid event IDs passed to shortcode
* Translation: Dutch
* Removed license key string from Help page

= 1.0.6 =

* Bug fix: Prevent submitting ticket order form if there are no tickets in the form.
* Feature: add filter to receipt template so plug-ins can add custom data to template
* Feature: make printable report view filterable so plug-ins can add print views

= 1.0.5 =

* Feature: Add per-ticket handling fee
* Feature: Shut off online ticket sales when 'x' tickets or 'x' percentage of total tickets are left.
* Feature: Print This Report button (table version only)
* Change: Save timestamp in custom field in order to create lists of tickets by date.
* Change: Only display last month of events in reports dropdown.
* Change: Added more filters to further ability to extend My Tickets.
* Change: Text change for clarity in what "total" is.
* Bug fix: re-sending email could create new tickets.
* Bug fix: Use COOKIEPATH instead of SITECOOKIEPATH to support WP installed in a separate directory.

= 1.0.4 =

* Bug fix: Invalid argument error on user profiles
* Bug fix: Don't attempt to use default payment gateway if that gateway has been deactivated.
* Bug fix: When total updated, currency was changed to $.
* Bug fix: Plus/minus buttons in cart could take number of tickets below 0
* Bug fix: Cart total calculation included deleted cart items
* Bug fix: Cart total value could go negative without disabling cart submission.
* Bug fix: Add a couple missing textdomains.
* Bug fix: Handling fee not shown to offline payments.
* Bug fix: Amount due pulled from wrong data on offline payments.
* Bug fix: Updating posts with tickets could modify the count of sold tickets.
* Bug fix: If cart submitted with 0 tickets on a ticket type, do not display those values in reports/admin.
* Include address fields in purchase reports
* Include phone number in purchase reports.
* Add note that 'x' tickets are still available for sale after sales are closed.

= 1.0.3 =

* Add documentation of ticket shortcodes on Help screen.
* Add administrative/handling charge for tickets.
* Add option to require phone number from purchasers.
* Bug fix: Payments search didn't work.
* Two new template tags: {handling} and {phone}

= 1.0.2 =

* Add lang directory and translation .pot
* Fix issue: not asked to enter address with offline payment/postal mail combination.

= 1.0.1 =

* Bug fix: If an expired event was in cart, Postal Mail would not show as an option for ticket methods.
* Bug fix: If an event had was not supposed to sell tickets and user was logged-in, 'Sold out' notice would display.

= 1.0.0 =

* Initial launch!

== Frequently Asked Questions ==

= I'm trying to sell tickets with My Calendar, but can't see how to add sales information =

The My Tickets sales information is entered in the 'Registration Information' panel of the My Calendar add event screen. This may be turned off in your installation. There are two places to look to enable it. First, go to My Calendar > Settings and go to the Input Settings section. If the 'registration' option isn't checked, check it and save settings. Second, go to the Add Event screen. If the registration options still aren't visible, you may need to enable them in your personal Screen Options. Open the Screen Options panel and check the option there - these are settings that apply only to your account.

= If I visit the 'Tickets' or 'Receipts' pages, I end up on the Purchase page. What's happening? =

The Tickets and Receipts pages are only for displaying purchased tickets or purchase receipts. If no valid ID for one of those resources is included, then they'll redirect to the shopping cart.

= How do I scan QR Codes for events? =

You can use any QR Code scanning app for a mobile phone or other mobile device with a camera. In order to get the ticket status confirmation, you'll need to be connected to a network.

= Is the "number of tickets available" field required? =

Yes. My Tickets won't sell an unlimited number of tickets for an event; in order for My Tickets to sell anything, you need to specify how many tickets it's allowed to sell.

== Screenshots ==

1. Add to Cart Form
2. Shopping Cart
3. Payment Admin

== Upgrade Notice ==

1.2.5: Minor bug fix to QR code generation