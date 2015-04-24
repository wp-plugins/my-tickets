=== My Tickets ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: events, ticket sales, tickets, ticketing, registration, reservations, event tickets, sell tickets, event registration, box office
Requires at least: 4.0
Tested up to: 4.2
License: GPLv2 or later
Stable tag: 1.0.6

My Tickets is an easy-to-use, flexible platform for selling event tickets.

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

Dutch

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

= 1.0.7 =

* Bug fix: Unassigned variable after filter.
* Translation: Dutch

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

= Hey! Why don't you have any Frequently Asked Questions here! =

This is a brand new plug-in; but I'm sure you'll have questions in time! Meanwhile, check out the [documentation](http://docs.joedolson.com/my-tickets/].

== Screenshots ==

1. Add to Cart Form
2. Shopping Cart
3. Payment Admin

== Upgrade Notice ==

= 1.0.0 =

1.0.5: Bug fixes & new features.