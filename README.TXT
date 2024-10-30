=== Monkey Trapped Login ===
Contributors: konnun
Tags: brute force, bruteforce, login, ip, lockout, protection, security, shield, wp-login, attack, Auth Cookie
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Monkey Trapped Login is a WordPress Plugin created to protect your site from Brute Force Login Attacks. Monkey Trapped Login protects against automated attacks on the login form as well as Auth Cookie. Admin can configure the number of failed logins to trigger a lockout, the length of a lockout, and a message displayed to a user who exceeds the maximum allowed failed login attempts.

== Description ==

Monkey Trapped Login is a WordPress Plugin created to protect your site from Brute Force Login Attacks. Automated attacks will be shut down before using up your resources and won't be able to continue guessing your admin user name and password.

Features include:
Configurable maximum failed login attempts to trigger a lockout
Configurable length of lockout
Limits the number of allowed login attempts using the login form
Limits the number of allowed login attempts using Auth Cookies
Manually whitelist trusted IP addresses (never blocked)
Manually block known bad IP addresses (always blocked unless also on the whitelist)
Auto-whitelists the IP of the installer (can be removed after install)
Admin can view a list of all locked out IP addresses (active and historical)
Resets failed login attempts on a successful login
Configurable message to show to blocked users
Replaces standard login error with "Login Failed" so hackers won't know if the user name is right or not

Optional Features (all free)
Admin notifications by email on any lockout
Notify Monkey Trapped server of hacking attempts on your site.

If you select “Participate” on the Settings->Monkey Login page, Monkey Trapped Login will send a message to the Monkey Trapped server to help build a comprehensive brute force attacker black list.

NOTE: No plugin is a replacement for strong passwords. Also, never use "admin" as the administrator user name. "admin" is the most common user name used in brute force attacks.


== Installation ==


    Download the plugin to your computer
    Log in to your WP admin area and go to Plugins > Add New
    Click upload
    Browse to the plugin .zip file on your computer and select it
    Click Install Now and the plugin will be installed shortly
    Click Activate Plugin

    For optional features go to Settings->Monkey Login in your admin panel


== Frequently Asked Questions ==


== Changelog ==

= 1.1.0 =
* Added admin notification by email.
* Several bug fixes.

= 1.0.0 =
* Made participation (sending data to the server) opt-in.
* Added filter for login error messages

= 0.9.0 =
* Final public beta

== Upgrade Notice ==

= 1.1.0 =
First stable public release.
