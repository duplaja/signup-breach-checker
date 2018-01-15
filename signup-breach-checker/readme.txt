=== Signup Breach Checker ===
Contributors: duplaja
Donate link: https://www.wptechguides.com/donate/
Tags: Gravity Forms, GF, Stripe, installments, layaway, subscriptions
Requires at least: 4.9.0
Tested up to: 4.9.1
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Checks user e-mails and optionally passwords against breach lists from haveibeenpwned.com on signup.

== Description ==

**Note: This plugin sends e-mail address (and optionally SHA1 hashed passwords) to an external API, at https://haveibeenpwned.com ** 

This plugin is meant to provide a service to your site members by doing the following:

* On user registration, check the haveibeenpwned API to see if their e-mail has been in any known breaches
* Stores (in user_meta) any breaches found, and if the user has been notified (by your site)
* If welcome e-mails are enabled, adds a section sharing information about the breaches, and the suggestion to use a strong password with a link to help. If not, it also lets them know they are clean.
* **Optional** (Disabled by default): Enable password checking against the API's list of known passwords on password reset / new user password set. This only triggers if the user also has had their e-mail leaked in a known breach, and e-mails the user with additional information.

Planned for future updates:

* (Toggleable) Method of checking existing users and notifying them.
* (Toggleable) Method to periodically check all users that haven't had a breach, and notify them if that changes.
* (Toggleable) Method to add admin notifications of new breaches discovered by HaveIBeenPwned.com

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/signup-breach-checker` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Head over to the Signup Breach Checker settings page, found on the Dashboard sidebar on the Tools submenu.

== Frequently Asked Questions ==

= What do I need for this plugin to run? =

You must have at LEAST WordPress 4.9.0 or higher, as it uses the wp_new_user_notification_email filter.

= Is checking passwords secure? =

Passwords are first hashed on-site using sha1, and then sent over https. This is as secure as using the haveibeenpwned password service yourself. This is turned OFF by default, but may be turned on on the settings page.

== Screenshots ==

1. Sample modified Welcome E-mail (if registrating person's e-mail was found in a breach).
1. Sample e-mail if Password Checking is enabled and the password is found on a pw dump list.
1. Signup Breach Checker settings page / control panel.

== Dependencies and Liscencing ==

This plugin relies on the the HaveIBeenPwned APIv2, and has been designed to comply with rate limiting and usage policy.

== Changelog ==

= 1.0 =
* Initial Plugin Release

== Upgrade Notice ==

= 1.0 =
* Initial Plugin Release
