=== Copyright Proof ===
Contributors: Digiprove
Donate link: http://www.digiprove.com/
Tags: copyright, protect ip, plagiarism, splogging, link-building, link, free, proof of ownership
Requires at least: 2.7
Tested up to: 2.8.5
Stable tag: 0.5

Protect copyright in your Wordpress post prior to publishing.

== Description ==

Prove authorship, deter plagiarism, and protect copyright.  Obtain a digitally signed and time-stamped certificate of content of each wordpress post (for proof of copyright).  Inserts notice of Digiprove certificate at end of post. At your option, there will be a link back from digiprove.com to your post.

[Copyright Proof](http://www.digiprove.com/digiproveblog.aspx) by [Digiprove](http://www.digiprove.com/ "Digiprove")


== Installation ==

1. Upload DigiproveBlog directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings page ('Settings', 'Copyright Proof')
4. Check the settings are to your preference
5. Answer the question "Registered Digiprove User?".
     - if you are already a registered Digiprove user, input your log-in credentials.
     - if you are not yet a Digiprove user, answer "Yes" to the question "Do you want to register now?" and enter your desired credentials (Registration is free).
6. To activate your registration you must click on the activation link that you will receive by email.

== Frequently Asked Questions ==

= I uploaded the plug-in and nothing seems to happen when I publish a new or edited post? =

- Have you registered with Digiprove yet (see installation steps above)?
- Have you activated your Digiprove registration by clicking on the link received by email?

Note that if the content of your post has not changed since the last Digiprove action, it will not be repeated as it is unnecessary.


= What is the plugin page?  =

[Copyright Proof](http://www.digiprove.com/digiproveblog.aspx) by [Digiprove](http://www.digiprove.com/ "Digiprove")


= What's with the registration process?  =

The Digiprove service needs to have the name of the person claiming copyright, and valid email address to which Digiprove content certificates will be sent.  There is an "activation" step whereby you click on the link in an email we send you before your registration becomes active.

Digiprove does not make use of these details except to deliver the service.  Please read the terms of use (including privacy policy) at http://www.digiprove.com/termsofuse_page.aspx

= Your web-site appears to be commercial.  Is this going to cost me anything?  =

There is no cost for personal use (or use by an educational establishment) of the Digiprove service via this plug-in.  It is also free for commercial use via this beta version of the plug-in.


= How does this protect copyright? =

Note that in most countries, you are already the copyright owner of any original work (literary or otherwise) that you have created, as soon as you record or publish it (whether or not you subsequently go through a formal registration process).  By Digiproving your work, you are creating time-stamped evidence that you are the possessor of that content, which is the critical factor in ensuring you can prove your ownership (or someone else's plagiarism).

In some countries, you can go a step further by formally registering your copyright in your work as it is created/published or subsequently.  There is usually a fee for this, and the benefits vary from country to country - in the U.S. for instance this should be done prior to instituting any legal proceedings for breach of copyright.

Digiproving your work is something that is done conveniently and will provide proof of ownership pre-dating any official copyright registration.
Learn more at http://www.digiprove.com/creative-and-copyright.aspx


= Can I review my history of Digiproving online? =

Yes, you can review your history online (and perform other functions) by visiting https://www.digiprove.com/members/my_digiprove.aspx - you will need to log in using your Digiprove user id and password.


= I'm a developer - how do I link directly to Digiprove API?  =

Use of the Digiprove API from other applications is free for personal or educational use.  Details of the (Soap) API are found at www.digiprove.com/resources.aspx.

== Screenshots ==

1. Settings (Basic)
2. Settings (Advanced)
3. A Digiprove notice


== Changelog ==

= 0.65 =
* Minor bug-fixes
* Fixed broken link in readme
* Title change to Copyright Proof (bit more meaningful than Digiprove Blog)

= 0.64 =
* First beta release
* Minor bug-fixes

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