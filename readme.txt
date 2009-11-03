=== Digiprove Blog ===
Contributors: Cian Kinsella
Donate link: http://www.digiprove.com/
Tags: copyright, protect ip, admin, plugin, link
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: 0.5

Protect copyright in your Wordpress post prior to publishing.

== Description ==

Submits content to www.digiprove.com to obtain digitally signed certificate of content (for proof of copyright).  Inserts notice of (and link to) Digiprove certificate at end of post.

[Digiprove Blog](http://www.digiprove.com/digiproveblog.aspx) by [Digiprove](http://www.digiprove.com/ "Digiprove")


== Installation ==

1. Upload DigiproveBlog directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings page ('Settings', 'Digiprove')
4. Check the settings are to your preference
5. Answer the question "Registered Digiprove User?".
     - if you are already a registered Digiprove user, input your log-in credentials
     - if you are not yet a Digiprove user, answer "Yes" to the question "Do you want to register now?" and enter your desired credentials
6. To activate your registration you must click on the activation link that you will receive by email

== Frequently Asked Questions ==

= I uploaded the plug-in and nothing seems to happen when I publish a new or edited post? =

Have you registered with Digiprove yet (see installation steps above)?
Have you activated your Digiprove registration by clicking on the link received by email?
Note that if the content of your post has not changed since the last Digiprove action, it will not be repeated as it is unnecessary.


= What is the plugin page?  =

[Digiprove Blog](http://www.digiprove.com/digiproveblog.aspx) by [Digiprove](http://www.digiprove.com/ "Digiprove")


= What's with the registration process?  =

The Digiprove service needs to have the name of the person claiming copyright, and valid email address to which Digiprove content certificates will be sent.  There is an "activation" step whereby you click on the link in an email we send you before your registration becomes active.

Digiprove does not make use of these details except to deliver the service.  Please read the terms of use (including privacy policy) at http://www.digiprove.com/termsofuse_page.aspx

= Your web-site appears to be commercial.  Is this going to cost me anything?  =

There is no cost for personal use (or use by an educational establishment) of the Digiprove service via this plug-in.  It is also free for commercial use via this beta version of the plug-in.


= Can I review my history of Digiproving online? =

Yes, you can review your history online (and perform other functions) by visiting https://www.digiprove.com/members/my_digiprove.aspx - you will need to log in using your Digiprove user id and password.


= How do I link directly to Digiprove API?  =

You are free to link to Digiprove's API from other applications (or write a better plug-in).  Details of the (Soap) API are found at www.digiprove.com/resources.aspx.

== Screenshots ==

1. Digiprove Settings
2. A Digiprove notice


== Changelog ==

= 0.63 =
* Better preserves intended appearance of Digiprove notice
* Handles special characters in names and post titles

= 0.62 =
* Handles incorrect SSL closure by IIS

= 0.61 =
* Uses http post instead of Soap (to deal with soapclient not installed)
* Minor cosmetic improvements
* Minor bug-fixes

= 0.6 =
* Settings page split into Basic/Advanced tabs

= 0.59 =
* Supplies user agent string to Digiprove API

= 0.58 =
* Does not Digiprove draft posts (doh!)
* Minor bug corrections

= 0.57 =
* User-selected Digiprove notice (with preview)
* Minor bug corrections

= 0.56 =
* Better error-handling of log-file exceptions
* Logging (for debug purposes) now turned off

