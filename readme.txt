=== Copyright Proof ===
Contributors: Digiprove
Donate link: http://www.digiprove.com/
Tags: copyright, protect ip, copy protect, plagiarism, splogging, proof of ownership
Requires at least: 2.7
Tested up to: 3.5
Stable tag: 2.12

Digitally certify your original content - proving authorship and protecting copyright. Inserts a combined copyright/licensing notice in your posts. 

== Description ==

A copyright notice with teeth!  Prove ownership, protect your copyright, and copy protect.  Obtain a digitally signed and time-stamped certificate of content of each wordpress post (for proof of copyright).  Inserts combined certification. copyright, licensing, and attribution notice at end of post. At your option, your post's url will be shown on digiprove.com (will be a hyperlink for Digiprove subscribers) to your post. Optional anti-theft feature to copy protect your content.

[Copyright Proof](http://www.digiprove.com/copyright_proof_wordpress_plugin.aspx)

== Installation ==

1. Upload digiproveblog directory to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings page ('Settings', 'Copyright Proof')
4. Check the settings are to your preference
5. Answer the question 'Registered Digiprove User?'.
     - if you are already a registered Digiprove user, input your log-in credentials.
     - if you are not yet a Digiprove user, answer "Yes" to the question "Do you want to register now?" and enter your desired credentials (Registration is free).
6. To activate your registration you must click on the activation link that you will receive by email.


== Frequently Asked Questions ==

= How does this protect copyright? =

Note that in most countries, you are already the copyright owner of any original work (literary or otherwise) that you have created, as soon as you record or publish it (whether or not you subsequently go through a formal registration process).  By Digiproving your work, you are creating time-stamped evidence that you are the possessor of that content, which is the critical factor in ensuring you can prove your ownership (or someone else's plagiarism).

In some countries, you can go a step further by formally registering your copyright in your work as it is created/published or subsequently.  There is usually a fee for this, and the benefits vary from country to country - in the U.S. for instance this should be done prior to instituting any legal proceedings for breach of copyright.

Digiproving your work is something that is done conveniently and will provide proof of ownership pre-dating any official copyright registration.
Learn more at http://www.digiprove.com/creative-and-copyright.aspx

= Is my content uploaded to Digiprove?
Unless you (or your hosting provider) are using an old version of PHP (earlier than PHP 5.1.2) or you have specified otherwise, only the digital fingerprint of your content is uploaded, not the 
content itself.  This allows you to protect private content on a private network using Wordpress.  On the other hand, premium users have the option to upload content, which
preserves your content independently of Wordpress.


= I installed the plug-in and nothing seems to happen when I publish a new or edited post? =

- Have you registered with Digiprove yet (see Installation)?
- Have you activated your Digiprove registration by clicking on the link received by email?
- If you have not received the activation mail, check your junk mail folder, then contact us at support@digiprove.com
- Note: To get the Digiprove notice to appear on existing posts, you should open each one and press Update & Digiprove


= What's with the registration process?  =

Copyright Proof uses the Digiprove service, which needs to have the name of the person claiming copyright, and a valid email address to which Digiprove content certificates will be sent.
Without this, there is no proof of ownership, so you might as well just have a simple copyright notice, there are a few plugins that will do this for you.
To give effect to this, there is an "activation" step whereby you click on the link in an email we send you before your registration (and the plugin) become active.

Digiprove does not make use of these details except to deliver the service.  Please read the terms of use (including privacy policy) at http://www.digiprove.com/termsofuse_page.aspx

= Your web-site appears to be commercial.  Is this going to cost me anything?  =

Some of the features of the plug-in are totally free for use on a single domain.  This includes the key function of certifying the textual content of your posts and pages.
If you want to use the more advanced features, such as protecting the copyright of pictures or other embedded media, you will need a valid subscription with Digiprove.
It is free for use by registered educational establishments or charities, just write to us at support@digiprove.com with details including evidence of your status to obtain this free subscription.


= What are the benefits of becoming a Digiprove subscriber?  =

From Wordpress:
- You can publish content from multiple domains
- You can protect individual files and media as well as the blog post text
- You can Digiprove more pages and posts per day
- You can elect to have your valuable Digiprove Content Certificates emailed to you automatically
- You can create a custom text to display in your Digiprove notice rather than using one of the standard texts
- You can have a hyperlink from your certificate page on www.digiprove.com back to your Wordpress post (or page)
- You can save content at digiprove.com
- You can compose your own license caption and statement rather than selecting from one of the standard ones

At www.digiprove.com:
- You can protect the IP of all types of content
- You can create tamper-proof audit trails within applications
- You can send certified email
- You can create authentication methods for your content files


= I think I've found a bug, what can I do? =

We actively seek information about problems or criticisms you may have.  So please let us know at support@digiprove.com - we will address the problem as soon as possible. 


= I like your plugin, but it would be much better if ... =

We actively seek your suggestions at suggestions@digiprove.com.  We will respond to every suggestion and if we like it we'll act on it.


= Can I review my history of Digiproving online? =

Yes, you can review your history online (and perform other functions) by visiting https://www.digiprove.com/members/my_digiprove.aspx - you will need to log in using your Digiprove user id and password.

= How can I style the Digiprove Notice? =

There are various colors, font-sizes etc. you can choose, these are found on the "Advanced" tab of the Settings panel.  To make the message appear consistently it is quite heavily styled at the element
level.  However you can influence some aspects of the appearance using CSS style code such as:
*[id|="dprv_cp"]
{
	margin-left:40%;
	margin-right:40%;
	background-color:red;
}

= I get an error message: "invalid user id, domain, or api key" =

Check your user id first; if that is correct, there is a problem with the API key and/or domain. Each API key is associated with a domain (e.g. myinterestingblog.com, or www.myinterestingblog.com).
On initial installation, Copyright Proof will automatically assign one to you using the domain name recorded in the Wordpress "General" settings of your blog. If you change this domain name, or you
want to set up the plugin to run in another blog, you can request a new api key for the new domain. The quickest way to do this is to go to Copyright Proof Settings page and tick the box entitled
"Obtain new API key automatically" and press "Update Settings" (you will be asked for your password).  Note that multiple domains under one user id is only permissible to subscribers.  Free users
are limited to one domain.

= I get an error message: "daily limit of API requests exceeded - please upgrade Digiprove account" =

If you are a free user getting this message, it means that you are attempting to do more than 5 Digiprove transactions in any given day, which is a maximum we have introduced for free users. Some
users were going into the thousands.  Higher limits apply to subscription accounts.


= I'm a developer - how do I link directly to Digiprove API?  =

Use of the Digiprove API from other applications is free.  The same limitations apply for the premium services as if you were using this plugin.  Contact us at affiliates@digiprove.com if you would
like to know more. Details of the (Soap) API are found at www.digiprove.com/resources.aspx

== Screenshots ==

1. A Digiprove notice
2. Settings (Basic)
3. Settings (Advanced)
4. Settings (Content)
5. Settings (License)
6. Settings (Copy Protect)


== Changelog ==

= 2.12 =
* Removes spurious copy-protect messages on iPad and iPhone
* Upgraded from Digiprove SDK 0.97 to 0.98
  - Uses host api.digiprove.com rather than www.digiprove.com

= 2.11 =
* Upgraded from Digiprove SDK 0.95 to 0.97
  - removes deprecated call-time pass-by-reference
  - removes strict syntax offences
  - Now traps a blank api key before communication with host

= 2.10 =
* Removed unnecessary error diagnostic
* Now informs user of (important) MySQL errors that have occurred
* Compatible with Wordpress 3.5 (and change to wpdb->prepare)

= 2.09 =
* Changed id of Digiprove Notice container element to make it easily selectable in CSS
* Enhance copy-protect function to explicitly prevent dragging (e.g. of images etc.) - should now prevent this in all browsers
* Removed bug where copy-protect was disabling dropdown menus in Firefox

= 2.08 =
* Fixed problem preventing new registrations
* Minor performance improvements on serving pages
* Fixed minor bug re license text
* Removed incident reporting in html comments

= 2.07 =
* Fixed small HTML error on license display link
* Minor Performance and stability improvements

= 2.06 =
* Minor bugfixes

= 2.05 =
* Better validation of user id to prevent occasional problems in activation
* Changes to user id value now always validated at server to prevent bad user id being recorded
* Deals with consequences of WP bug related to upper-case characters in db prefix
* Further improvements to error and exception-handling on plugin-specific db tables

= 2.04 =
* Added some more help text about API keys
* Upgraded to latest version of Digiprove SDK (special character-encodes event info)
* Improved exception-handling on plugin-specific db tables
* Set SSL to Off by default to reduce client-side connection problems
* Fixed small bugs
* Removed wpdb reporting toggle

= 2.03 =
* Added better error handling and reporting around http functions
* Checks for required db columns and adds if not already set up
* Checks for wpdb reporting set to true and toggles it

= 2.02 =
* Does not require Mysql ascii character set to be installed
* Works around dbdelta bug when altering tables
* Fixed rare bug involving html comment end (-->) characters buried inside comment

= 2.01 =
* Removed spurious error/event reports
* Better error detection

= 2.00 =
* Refactored to use Digiprove official SDK
* Bit faster on publish or update - removes some unnecessary content parsing
* Prevents collapsed borders in digiprove notice
* Is more resistant to incompatibilities with other plugins and themes
* Is language-independent
* Incorporates Dutch version (Thanks to Eddy and Fran Vijfeijken)
* Eliminated duplicate code generating dprv_blog_host and dprv_wp_host variables
* Now copes with database prefix change
* Improved error detection
* Bug with quotes and doublequotes in right-click message is fixed
* Detects and handles change of Wordpress db prefix

= 1.16 =
* Copy-protect functions now work even with themes that use the body onload event
* Handles situation where plugins are installed in a directory other than wp-content
* Removed confusing scroll-down of main post-edit page
* Prevents inappropriate browser auto-completes (was leading to api key problems)

= 1.15 =
* Performance improvement in finding and fingerprinting files
* Now shows counter of remaining transaction allowance for today
* Implements revised daily transaction allowances (also implemented at server)
* Settings link now appears on Plugin administration page

= 1.14 =
* Fixed bug introduced by 1.13 - http failure

= 1.13 =
* Fixed issue where right-click copy protection was failing in combination with Facebook plugin

= 1.12 =
* Fixed issue where right-click copy protection was failing when another plugin made use of addLoadEvent function

= 1.11 =
* Fixed button alignment problem in WP 3.2.1 
* Introduced subscription expiry grace period of 10 days
* Improved event logging
* Now skips Digiprove API call if no api key or password
* Minor improvement to processing speed of posts with attachments
* Fixed IE alignment problem with themes that apply max-width to images
* Removed spurious error message about media files for expired accounts

= 1.10 =
* Removed error reporting statement which flagged notices and warnings from other plugins

= 1.09 =
* Removed error-handling code for javascript errors - was being triggered by bugs in other plugins and themes

= 1.08 =
* Added error-handling code for javascript errors
* Handles privately published posts and pages (i.e. for logged-in users only)

= 1.07 =
* New option to include digital fingerprints of all or selected uploaded media files in Digiprove process (NOTE does not yet support [gallery] shortcode)
* Now supports custom post types
* Improved alignment of Digiprove notice in some circumstances
* Fixed bug where xml in some cases omitted api key
* Removed bug where old api key could be used for Upgrade instead of new one just created this session
* Improved help text when api key is lost or deleted.
* Removed situations giving rise to Notice-level PHP messages
* Fixed incorrect calculation of expiry date
* Fixed some compatability issues with older version of Wordpress
* Various bug-fixes

= 1.06 =
* Removed non-standard valign attribute on notice
* License display panel now works with themes and plugins that cause default target (for anchor tag) to be new tab/window
* Copy-protect function now works even with themes that do not call wp_footer()
* Optional footer message "Original content on these pages is fingerprinted and certified by Digiprove"
* Option whether or not to display Digiprove notice only on single-post-pages
* Small optimisation of http traffic (PHP5.1.2 or later) - retains calculated digital fingerprint - no longer repeated in http response
* Improved alignment of license text within Digiprove notice
* Check for invalid api key will not appear until Update is pressed (to avoid annoying pop-up messages)
* Will now prevent user from assigning a blank/empty api key

= 1.05 =
* Fixed bug where always the first license box on a webpage was popped up instead of the desired one
* Removed dependency on PHP 5 functions (introduced at 1.00) - now works with PHP 4 (again)

= 1.04 =
* Changed display mode to use margins above and below notice
* Code tidy-up; removed diagnostic/debug code
* Removed dependency on get_post_type_object (for compatibility with pre 3.0 WP installations)

= 1.03 =
* Did not re-activate properly on upgrade in a Wordpress 3.1 environment

= 1.02 =
* Default values were sometimes not being applied - fixed
* License display aligned better

= 1.01 =
* Minor bug-fixes

= 1.00 =
* Results of Digiprove actions now recorded in Wordpress database (as custom post meta fields) instead of embedded in notice at end of content 
* Digiprove Notice now generated dynamically when content is displayed (meaning changes to appearance etc. have immediate effect)
* Earliest date of Digiproving content now remembered and shown where necessary in notice (e.g. "copyright 2010-2011")
* Now handles future-dated posts (will Digiprove now, publish later)
* Revised styling of Digiprove notice to make it work better with some themes
* New option whether or not to Digiprove individual posts or pages
* New button "publish and Digiprove" (or "Update and Digiprove") to eliminate unnecessary Digiproving actions
* New option at individual post/page level to display attributions/acknowledgements to other author(s) 
* New option to display licensing details - both overall default settings and post/page specific settings (incorporates Creative Commons, GPL, and others)
* CSS change to override unwanted background images
* A number of changes to improve performance
* Removed bug which could permit an empty api key to be recorded
* Introduced better validation of email address
* Specify custom text for Digiprove notice (*)
* Save content (incl versions) in cloud (*)
* Specify custom licensing (*)
* (*) - dependent on account type
* Known error: Strange positioning in Copyright Panel on IE8

= 0.87 =
* Fixed bug to do with synchronous idle control character (0x16) in XML
* Installed workaround for phantom api key appearing in PHP4
* PHP4 compatibility restored (was using stream_get_transports which is only php5)

= 0.86 =
* More validation and better help text in registration processes
* Small change to minimise file permission problems when upgrading this plug-in in future
* Google "notranslate" class now applied to Digiprove notice
* Now detects if SSL not installed on Wordpress Server and drops back to http on port 80
* Fixed bug to do with start of header control character (0x01) in XML

= 0.85 =
* Fixed bug where input fields within a post or page were disabled on Firefox when copy-protect feature was on

= 0.84 =
* Fixed silly and annoying bug (introduced at 0.82) where Digiprove notice of an updated post was corrupted
* To fix existing notices, please make a small change to post content and press Update

= 0.83 =
* Fixed bug where copy-protect mechanism was interfering with ability to post comments on post page

= 0.82 =
* Fixed bug where backslashes were erroneously stripped from content

= 0.81 =
* Fixed situation where copy-protect function interfered with any plugin or theme that uses the "window.onload" javascript function (e.g. atahualpa theme)
* Digiprove notice now contains language attribute to work better with Google translation

= 0.80 =
* Fixed some bugs with copy-protect:
* - copy-protect functions interfered with text editing
* - copy-protect settings now affect all posts and pages and changes to settings take immediate effect
* - remove right-click message now works ok

= 0.79 =
* Can now request re-send of activation email
* Pages (as well as posts) now Digiproved

= 0.78 =
* Tested with Wordpress 3.0.1
* New option - whether to receive certificates by email
* Was using Wordpress installation url rather than blog url as domain - corrected
* Traps error situation where no blog url can be found
* Linked privacy setting for name display to Copyright notice text as well as certificate display
* Improved help text for getting started (registration etc.)
* Added facility to obtain new Digiprove api key from within Wordpress
* Cosmetic improvements to behaviour & appearance on IE
* Fixed bug dealing with long email addresses
* rel="copyright" now included in copyright notice link


= 0.77 =
* Fixed javascript bug that caused Copy-Protect settings problems in IE
* Digiprove information messages now displayed in IE
* Digiprove notice now displayed in 'inline' mode - should be better in most browsers

= 0.76 =
* Fixed javascript bug (Settings did not work properly on IE)

= 0.75 =
* New Copy-Protect feature to prevent right-clicking and text selection
* Tested with 3.0
* Fixed bug to do with vertical tab character (0x0B) in XML
* Fixed bug to do with unescaped ampersand in content type description

= 0.74 =
* Now works with Postie plugin (and hopefully all other sources of published posts)
* Tested with Wordpress 3.0 RC1

= 0.73 =
* Fixed fatal error for older PHP versions (pre 5.1.2)
* Now working on PHP4
* Minor code tidy-up

= 0.72 =
* Fixed fatal error for older PHP versions (pre 5.2)
* More graceful handling of PHP 4 attempted activations
* Fixed minor bugs in parsing of content from xml-rpc

= 0.71 =
* Migrating to API keys for improved security (no user action required)
* Digiprove server synchronised with amended profile data
* Display of user name on certificate page is now optional
* Tested with Wordpress 2.9.2
* Minor code improvements
* Improved and more help text
* Fixed XML non-compliance bug
* Better tracking of inter-server communication
* (acknowledgements to Alexander Gieg and other users for some great suggestions)

= 0.70 =
* Tested with Wordpress 2.9.1
* Improved help text
* Fixed bug where last action message sometimes displayed unnecessarily
* User id no longer shown in certificate display for privacy reasons
* User id now defaults to email address (again)
* Moved jscolor files into main directory for ease of updating
* Fixed bug where title was not picked up on XML-RPC posts

= 0.69 =
* Tested with Wordpress 2.9
* Now supports posts submitted by Windows Live Writer
* (Other xml-rpc clients may work - not tested - please let us know)
* Changed default user id to firstname . lastname
* Added proper explanatory/help text about registering with Digiprove
* Fixed minor validation bug in registration
* Digiprove notice format more resistant to css inheritance
* More flexibility in composing Digiprove notice text
* Copyright notice may now include name

= 0.68 =
* Digiprove notice size can be adjusted
* Digiprove notice format more resistant to css inheritance
* Digiprove notice format more consistent across browsers
* User agent string in user registration
* Better handling of title-only posts
* Tested with Wordpress 2.8.6
* increased timeout to 40 seconds for slow connections

= 0.67 =
* Digiprove notice preview bug fixed

= 0.66 =
* User has more control over Digiprove notice appearance
* Better W3 XHTML standards-compliance

= 0.65 =
* Minor bug-fixes
* Fixed broken link in readme
* Title change to Copyright Proof (bit more meaningful than Digiprove Blog)

= 0.64 =
* First public beta release
* Minor bug-fixes

== Upgrade Notice ==

= 2.10 = 
Strongly recommended, required to support Wordpress 3.5

= 2.08 =
Strongly recommended - removes serious error for new users and performs better

= 1.2 =
Significant functional and stability enhancements

= 1.15 =
Recommended

= 1.14 =
CRITICAL upgrade if you are on 1.13 - fixes http problem

= 1.13 =
Upgrade if right-click copy protect function is not working for you

= 1.12 =
Upgrade if right-click copy protect function is not working for you

= 1.11 =
Upgrade is recommended if the Digiprove/Copyright notice on your posts is badly aligned in Internet Explorer.
There are some minor bug-fixes as well.

= 1.10 =
Strongly recommended upgrade - removes spurious and annoying error messages.
