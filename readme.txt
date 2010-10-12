=== Copyright Proof ===
Contributors: Digiprove
Donate link: http://www.digiprove.com/
Tags: copyright, protect ip, copy protect, plagiarism, splogging, proof of ownership
Requires at least: 2.7
Tested up to: 3.0.1
Stable tag: 0.83

Digitally certify your blog posts - proving authorship, deterring plagiarists, and protecting copyright.

== Description ==

A copyright notice with teeth!  Prove ownership, protect your copyright, and copy protect.  Obtain a digitally signed and time-stamped certificate of content of each wordpress post (for proof of copyright).  Inserts notice of Digiprove certificate at end of post. At your option, there will be a link back from digiprove.com to your post. Optional anti-theft feature to copy protect your content.

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

= I uploaded the plug-in and nothing seems to happen when I publish a new or edited post? =

- Have you registered with Digiprove yet (see Installation)?
- Have you activated your Digiprove registration by clicking on the link received by email?
- If you have not received the activation mail, check your junk mail folder, then contact us at support@digiprove.com


= What is the plugin page?  =

[Copyright Proof](http://www.digiprove.com/copyright_proof_wordpress_plugin.aspx)

= When I try to upgrade the plugin, it fails (often with the message "Could not remove the old plugin") - what to do? =

This is a frustrating thing that doesn't happen to everybody. It can happen with any plugin. Basically your host server operating system is preventing Wordpress from
deleting the old plugin files or the directory in which they are contained.  The short-term solution is to access your site via FTP and delete the plugin folder/directory.
In the case of Copyright Proof the folder is (within your wordpress root directory) "/wp-content/plugins/digiproveblog". Then install the Copyright Proof plugin afresh -
your existing settings will all be retained.

The long term solution is to find the source of the problem - usually some permission settings somewhere on the server, maybe with the aid of your hosting company. Unfortunately
because of the wide variety of environments that Wordpress can find itself in, there is no "one-size-fits-all" solution.


= What's with the registration process?  =

Copyright Proof uses the Digiprove service, which needs to have the name of the person claiming copyright, and a valid email address to which Digiprove content certificates will be sent.
Without this, there is no proof of ownership, so you might as well just have a simple copyright notice, there are a few plugins that will do this for you.
To give effect to this, there is an "activation" step whereby you click on the link in an email we send you before your registration (and the plugin) become active.

Digiprove does not make use of these details except to deliver the service.  Please read the terms of use (including privacy policy) at http://www.digiprove.com/termsofuse_page.aspx

= Your web-site appears to be commercial.  Is this going to cost me anything?  =

This is a Beta version of the Copyright Proof plug-in - it is free for all to use.  For the future our intention is that there will be no cost for personal use (or use by an educational establishment or a registered charity) of the Digiprove service via this plug-in.


= How does this protect copyright? =

Note that in most countries, you are already the copyright owner of any original work (literary or otherwise) that you have created, as soon as you record or publish it (whether or not you subsequently go through a formal registration process).  By Digiproving your work, you are creating time-stamped evidence that you are the possessor of that content, which is the critical factor in ensuring you can prove your ownership (or someone else's plagiarism).

In some countries, you can go a step further by formally registering your copyright in your work as it is created/published or subsequently.  There is usually a fee for this, and the benefits vary from country to country - in the U.S. for instance this should be done prior to instituting any legal proceedings for breach of copyright.

Digiproving your work is something that is done conveniently and will provide proof of ownership pre-dating any official copyright registration.
Learn more at http://www.digiprove.com/creative-and-copyright.aspx

= I think I've found a bug, what can I do? =

This is a beta version of the plug-in, and we actively seek information about problems or criticisms you may have.  So please let us know at support@digiprove.com - we will address the problem as soon as possible. 


= I like your plugin, but it would be much better if ... =

We actively seek your suggestions at suggestions@digiprove.com.  We will respond to every suggestion and if we like it we'll act on it.


= Can I review my history of Digiproving online? =

Yes, you can review your history online (and perform other functions) by visiting https://www.digiprove.com/members/my_digiprove.aspx - you will need to log in using your Digiprove user id and password.


= You say you are migrating to API Keys for better security - as an existing user what do I have to do? =

From release 0.71 onwards as part of our preparation to exit Beta mode, we no longer store user Digiprove passwords in Settings, using an api key instead.  Existing users do not need to do anything,
the plug-in will automatically request and install a new api key for you. For new users registering via the plug-in, issuance of an api key is also done automatically.

= I get an error message: "invalid user id, domain, or api key" =

Check your user id first; if that is correct, there is a problem with the API key and/or domain. Each API key is associated with a domain (e.g. myinterestingblog.com, or www.myinterestingblog.com).
Copyright Proof will automatically assign one to you using the domain name recorded in the Wordpress "General" settings of your blog. If you change this domain name, or you want to set up the
plugin to run in another blog, you can request a new api key for the new domain. Do this by logging into Digiprove at https://www.digiprove.com/secure/login.aspx, then choosing "Preferences" and 
"Issue/Renew API Keys".  This will allow you to obtain multiple API keys corresponding to the domains you wish to work with, which can then be input in the Copyright Proof Settings page in Wordpress.

If the API key value gets messed up you can get a new one by ticking the "Renew API Key" box, entering your password, and pressing Update.

= I get an error message: "daily limit of API requests exceeded - please upgrade Digiprove account" =

If you are getting this message, it means that you are attempting to do more than 50 Digiprove transactions in any given day, which is a maximum we have introduced for free users. Some users are going
into the thousands.  This limit does not apply to subscription accounts.

= I'm a developer - how do I link directly to Digiprove API?  =

Use of the Digiprove API from other applications is free for personal (non-commercial) or use by registered educational establishments or charities.  Details of the (Soap) API are found at www.digiprove.com/resources.aspx

== Screenshots ==

1. Settings (Basic)
2. Settings (Advanced)
3. Settings (Copy Protect)
4. A Digiprove notice


== Changelog ==

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