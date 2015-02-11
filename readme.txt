=== My Tickets ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: events, ticket sales, tickets, ticketing, registration, reservations, event tickets, sell tickets, event registration, box office
Requires at least: 4.0
Tested up to: 4.1
License: GPLv2 or later
Stable tag: 1.0.3

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

None yet!

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

None yet!