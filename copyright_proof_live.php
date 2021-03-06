<?php
// FUNCTIONS CALLED WHEN SERVING PAGES
include_once('copyright_proof_integrity.php');						// Functions for Integrity Checking
function dprv_log_event()
{
    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) )
	{
		$severity = $_REQUEST['severity'];
		$message = $_REQUEST['message'];
		$url = urldecode($_REQUEST['url']);
		// Now we'll return it to the javascript function
		// Anything outputted will be returned in the response
		echo "got message " . $message . " page=" . $url;

		// React ansynchronously to event
		// Log it
		$rslt = dprv_log_writeline($severity, $message, $url);
		// If so configured by user, send an email
		if (strpos(get_option('dprv_record_IP'), "Email") !== false)
		{
			global $dprv_blog_host;
			$mail_message = __("Digiprove's Copyright Proof plugin detected the following event:", "dprv_cp") . "\r\n\r\n";
			$mail_message .= $message . __(", page=", "dprv_cp") .$url . "\r\n\r\n";
			$mail_message .= __("IP address: ", "dprv_cp") . $_SERVER['REMOTE_ADDR'] . "\r\n\r\n";
			$mail_message .= __("You are receiving this email because of your Wordpress Copyright Proof settings for the site ", "dprv_cp") . $dprv_blog_host . "\r\n\r\n";
			$mail_message .= __("You can visit your Digiprove account by logging in at https://www.digiprove.com/secure/login.aspx (your user id is ", "dprv_cp") . get_option('dprv_user_id') . ").";
			wp_mail(get_option('admin_email'), __('Warning - possible attempt to copy content from ', 'dprv_cp') . get_option('blogname'), $mail_message); 
		}
    }
    // Always die in functions echoing ajax content
   die();
}
if (get_option('dprv_record_IP') != "off")
{
	add_action( 'wp_ajax_dprv_log_event', 'dprv_log_event' );
	add_action( 'wp_ajax_nopriv_dprv_log_event', 'dprv_log_event' );
}

function dprv_head()
{
	$log = new DPLog();  
	$log->lwrite("dprv_wp_head starts");
	$dprv_frustrate_copy = get_option('dprv_frustrate_copy');
	$dprv_right_click_message = get_option('dprv_right_click_message');

	if ($dprv_frustrate_copy == "Yes")
	{
		echo ("<script type='text/javascript' src='" . plugins_url("frustrate_copy.js", __FILE__ ) . "?v=" . DPRV_VERSION. "'></script>");
	}

	echo ("
	<script type='text/javascript'>
		//<![CDATA[");
	
	// Then, create Javascript to do copy-protect functions if necessary
	$dprv_record_IP = get_option('dprv_record_IP');
	global $dprv_wp_host;
	if ($dprv_frustrate_copy == "Yes")
	{
		echo ("
		var dprv_record_IP = '" . $dprv_record_IP . "';
		var dprv_site_url = '" . site_url() . "';
		var dprv_ajax_url = '" . admin_url( 'admin-ajax.php' ) . "';
		var dprv_noRightClickMessage='" . addslashes($dprv_right_click_message) . "';
		var dprv_justDisplayed = 0;

		function dprv_addLoadEvent(func)
		{ 
			if (typeof window.onload != 'function')
			{ 
				window.onload = func; 
			}
			else
			{ 
				var oldonload = window.onload;
				window.onload = function()
				{ 
					if (oldonload)
					{ 
						oldonload(); 
					}
					func(); 
				} 
			} 
		} 
		function dprv_copy_frustrate()
		{
			// Prevent Right-Clicking (entire page)
			dprv_disableRightClick();
			// Prevent Drag (entire page)
			dprv_disableDrag(document.body);
			// Prevent Control Key combinations (like CTRL A, CTRL U)
			dprv_disableCtrlKeys();
			dprv_disableSelection(document.body);
		}

		// Set up dprv_copy_frustrate to run after load
		if (window.addEventListener)
		{
			window.addEventListener('load', dprv_copy_frustrate, false);	// For modern browsers
		}
		else
		{
			if (window.attachEvent)
			{
				window.attachEvent('onload', dprv_copy_frustrate);			// For older versions of IE
			}
			else
			{
				dprv_addLoadEvent(dprv_copy_frustrate);						// Do it the old way (should never get here)
			}
		}");
	}

	echo ("
		function dprv_DisplayAttributions(attribution_text)
		{
			document.getElementById(\"dprv_attribution\").innerHTML = attribution_text;
			document.getElementById(\"dprv_attribution\").title = \"" . __('Attributions - owner(s) of some content', 'dprv_cp') . "\";
			document.getElementById(\"dprv_attribution\").onmouseover = \"\";
		}
		function dprv_DisplayLicense(post_id)
		{
			document.getElementById('license_panel' + post_id).style.display='block';
			document.getElementById('license_panel' + post_id).style.zIndex='2';
		}
		function dprv_HideLicense(post_id)
		{
			document.getElementById('license_panel' + post_id).style.display='none';
		}");



	echo ("
	//]]>
	</script>");
	echo ('	<style type="text/css">	.dprv{border-collapse:collapse;border-spacing:0px;border:0px;border-style:solid;padding:0px;}
									.dprv tr td{border:0px;padding:0px;}
			</style>');
	dprv_populate_licenses();
}


function dprv_display_content($content)
{
	global $wpdb, $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs;
	$log = new DPLog();  
	$dprv_post_id = get_the_ID();
	$log->lwrite("dprv_display_content starts for post/page " . $dprv_post_id);

	// Find out context:
	//		In Excerpt (search pages, archive pages without manual excerpt, auto-generated 55-character excerpt text with HTML tags removed)
	//		Note manual excerpts do not get filtered through the_content()
	//		From post-template.php means in standard web-page display
	//		From feed.php - In a Feed 
	//
	$in_auto_excerpt = false;
	$normal_display = false;
	$counter = 0;
	$bt = debug_backtrace();
	foreach ($bt as $caller)
	{
		$search_result = array_search("wp_trim_excerpt", $caller);
		if ($search_result == "function")
		{
			$in_auto_excerpt = true;
		}
		if ($normal_display == false && is_array($caller) && isset($caller["file"]) && (strpos($caller["file"], "post-template.php") != false || strpos($caller["file"], "feed.php") != false))
		{
			$normal_display = true;
		}
		$counter++;
		if ($counter > 6)
		{
			break;
		}
	}
	if ($normal_display == false && $in_auto_excerpt == false)	// if not called from post-template or feed, probably called from some other plugin, or not in auto_excerpt don't add any copyright stuff
	{
		return $content;
	}

	// Do Data Integrity Check and get statement for the Digiprove notice
	$dprv_integrity_headline="";
	$dprv_integrity_message="";
	// TODO - Reinstate instruction below when implementing integrity checking
	//	dprv_integrity_statement($dprv_post_id, $dprv_integrity_headline, $dprv_integrity_message);

	if (($in_auto_excerpt == true || !is_singular()) && get_option('dprv_multi_post') == "No")
	{
		return $content;
	}

	// Remove old-style notice (if there is one there) and return the core information from it 
	dprv_strip_old_notice($content, $dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, $dprv_first_year);


	// a Digiprove Notice is required to append to content
	$dprv_notice = "";

	// Establish Copyright / ownership details 
	// Set Default Values to start with:
	$dprv_this_all_original = "Yes";
	$dprv_this_attributions = "";
	$dprv_this_license = get_option('dprv_license');
	$dprv_this_default_license = "Yes";
	$dprv_this_license_caption = "";
	$dprv_this_license_abstract = "";
	$dprv_this_license_url = "";
	$dprv_post_info = null;
	$dprv_status_info = "";
	if (trim($dprv_post_id == ""))
	{
		return $content;
	}
	else
	{
		// If stuff is recorded specifically for this post, use that
		//$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
		//$dprv_post_info = dprv_wpdb("get_row", $sql);
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = %d";
		$dprv_post_info = dprv_wpdb("get_row", $sql, $dprv_post_id);

	}		
	if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
	{
		$dprv_this_all_original = "No";
		if ($dprv_post_info["this_all_original"] == true)
		{
			$dprv_this_all_original = "Yes";
		}
		$dprv_this_attributions = $dprv_post_info["attributions"];

		$dprv_this_default_license = "Yes";
		if ($dprv_post_info["using_default_license"] == false)		// Default license set to Yes trumps individual settings
		{
			$dprv_this_default_license = "No";
			$dprv_this_license = $dprv_post_info["license"];
		}
		

		$dprv_number = "" . intval($dprv_this_license);
		if ($dprv_number != $dprv_this_license && $dprv_post_info["using_default_license"] == false)	// Default license set to Yes trumps individual settings
		{
			$log->lwrite("custom license");
			$dprv_this_license_caption = $dprv_post_info["custom_license_caption"];
			$dprv_this_license_abstract = $dprv_post_info["custom_license_abstract"];
			$dprv_this_license_url = $dprv_post_info["custom_license_url"];
		}
		else
		{
			for ($i=0; $i<count($dprv_licenseIds); $i++)
			{
				if ($dprv_this_license == $dprv_licenseIds[$i])
				{
					$dprv_this_license = $dprv_licenseTypes[$i];
					$dprv_this_license_caption = $dprv_licenseCaptions[$i];
					$dprv_this_license_abstract = $dprv_licenseAbstracts[$i];
					$dprv_this_license_url =  $dprv_licenseURLs[$i];
				}
			}
		}
	}
	else  // nothing recorded specifically for this post, fill out other license values unless license is None
	{
		if ($dprv_this_license != 0 && $dprv_this_license != '0')
		{
			for ($i=0; $i<count($dprv_licenseIds); $i++)
			{
				if ($dprv_this_license == $dprv_licenseIds[$i])
				{
					$dprv_this_license = $dprv_licenseTypes[$i];
					$dprv_this_license_caption = $dprv_licenseCaptions[$i];
					$dprv_this_license_abstract = $dprv_licenseAbstracts[$i];
					$dprv_this_license_url =  $dprv_licenseURLs[$i];
				}
			}
		}
	}
	$dprv_license_html = "";
	if (!is_null($dprv_post_info) && count($dprv_post_info) > 0 && $dprv_post_info["digiprove_this_post"] == true && $dprv_post_info["certificate_id"] != null && $dprv_post_info["certificate_id"] != "" && $dprv_post_info["certificate_id"] != false)
	{
		$log->lwrite("Digiprove certification has been recorded in dprv_posts table");

		$dprv_certificate_id = $dprv_post_info["certificate_id"];
		$dprv_utc_date_and_time = $dprv_post_info["cert_utc_date_and_time"];
		$dprv_digital_fingerprint = $dprv_post_info["digital_fingerprint"];
		$dprv_certificate_url = $dprv_post_info["certificate_url"];
		$dprv_first_year = $dprv_post_info["first_year"];

		$dprv_notice = dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, false, $dprv_first_year, $dprv_this_license, $dprv_this_license_caption, $dprv_this_license_abstract, $dprv_this_license_url, $dprv_this_all_original, $dprv_this_attributions, $dprv_post_id, $dprv_license_html, $dprv_integrity_headline, $dprv_integrity_message);
		$content .=  $dprv_notice;
	}
	else
	{
		$log->lwrite("there is no Digiprove cert in dprv_posts table");
		if ($dprv_certificate_id != false && $dprv_certificate_id != "")
		{
			$log->lwrite("but there was an old notice - will make a new one with variables from that");
			$dprv_notice = dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, false, $dprv_first_year, $dprv_this_license, $dprv_this_license_caption, $dprv_this_license_abstract, $dprv_this_license_url, $dprv_this_all_original, $dprv_this_attributions, $dprv_post_id, $dprv_license_html, $dprv_integrity_headline, $dprv_integrity_message);
			$content .= $dprv_notice;
		}
	}
	$content .= $dprv_status_info;
	$content .= $dprv_license_html;
	//$log->lwrite("content to be displayed:" . $content);
	return $content;
}

function dprv_footer()
{
	$log = new DPLog();  
	$log->lwrite("dprv_footer starts");
	
	if (get_option('dprv_footer') == "Yes")
	{
		echo (sprintf(__('All original content on these pages is fingerprinted and certified by %s', 'dprv_cp'), "<a href='http://www.digiprove.com' target='_blank'>Digiprove</a>"));
	}
}
function dprv_integrity_statement($dprv_post_id, &$dprv_integrity_headline,  &$dprv_integrity_message)
{
	global $wpdb, $post;
	$log = new DPLog();
	$dprv_integrity_notice = "";
	$dprv_post_types = explode(',',get_option('dprv_post_types'));
	if (array_search($post->post_type, $dprv_post_types) === false)  // Is this a post type that is selected for Digiproving
	{
		return;
	}
	$post_type_label = $post->post_type; //default value

	$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
	$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
	if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
	{
		if (get_option('dprv_html_integrity') == "Yes" || get_option('dprv_files_integrity') == "Yes")
		{
			if (($dprv_post_info["certificate_id"] != null && $dprv_post_info["certificate_id"] != "") || ($dprv_post_info["last_time_updated"] != null && $dprv_post_info["last_fingerprint"] != ""))
			{
				$dprv_last_update_timestamp = "";
				$dprv_last_digital_fingerprint = "";
				if ($dprv_post_info["last_time_updated"] != null)
				{
					$log->lwrite("dprv_post_info[last_time_updated] = " . $dprv_post_info["last_time_updated"]);
					$dprv_last_digital_fingerprint = $dprv_post_info["last_fingerprint"];
					$dprv_last_update_timestamp = $dprv_post_info["last_time_updated"];
				}
				else
				{
					$log->lwrite("dprv_post_info[last_time_updated] == null");
					$dprv_last_digital_fingerprint = $dprv_post_info["digital_fingerprint"];
					if ($dprv_post_info["last_time_digiproved"] != null)
					{
						$dprv_last_update_timestamp = $dprv_post_info["last_time_digiproved"];
					}
					else
					{
						$dprv_last_update_timestamp = strtotime($dprv_post_info["cert_utc_date_and_time"]);
					}
				}

				$digital_fingerprint = "";
				// Might need to get from db, but try this:
				$check_content = $post->post_content;
				//$log->lwrite("post->post_content=" . $post->post_content);
				$check_content = dprv_getRawContent($check_content, $digital_fingerprint);
				$dprv_html_integrity_headline = "";
				$dprv_html_integrity_message = "";
				$dprv_files_integrity_headline = "";
				$dprv_files_integrity_message = "";
				$integrity = true;
				$html_integrity = 0;
				if ($digital_fingerprint != "")
				{
					if (get_option('dprv_html_integrity') == "Yes")
					{
						if ($digital_fingerprint == $dprv_post_info["digital_fingerprint"])
						{
							$html_integrity = 1;
							$dprv_html_integrity_headline = __('HTML Certified &amp; Verified', 'dprv_cp');
							$dprv_html_integrity_message = sprintf(__('The HTML in this %s matches last Digiprove certification', 'dprv_cp'), $post_type_label);
						}
						else
						{
							if ($digital_fingerprint == $dprv_post_info["last_fingerprint"])
							{
								$html_integrity = 2;
								$dprv_html_integrity_headline = __('HTML Verified', 'dprv_cp');
								$dprv_html_integrity_message = sprintf(__('The HTML in this %s matches last recorded update', 'dprv_cp'), $post_type_label);
							}
							else
							{
								$integrity = false;
								$html_integrity = -1;
								$dprv_html_integrity_headline = __('HTML Tamper Alert', 'dprv_cp');
								$dprv_html_integrity_message = sprintf(__('The HTML in this %1$s (id %2$s) appears to have been changed from outside Wordpress. ', 'dprv_cp'), $post_type_label, $dprv_post_id);

								// if last wp modified time was more than 5 seconds after last update noted by this plugin
								// make the discrepancy a warning rather than a Red Tamper Alert (could be that this plugin was deactivated for a period)
								$dprv_wp_last_modified_time = strtotime($post->post_modified_gmt . " GMT");
								if (($dprv_wp_last_modified_time - $dprv_last_update_timestamp) > 5)
								{
									$html_integrity = -2;
									$dprv_html_integrity_headline = __('Check HTML', 'dprv_cp');
									$dprv_html_integrity_message = sprintf(__('The HTML in this %1$s (id %2$s) has been changed without Digiprove integrity checking - check whether it is correct. ', 'dprv_cp'), $post_type_label, $dprv_post_id);
								}
							}
						}
					}
				}

				$files_integrity = 0;

				if (function_exists("hash") && get_option('dprv_files_integrity') == "Yes")
					//&& $dprv_post_info["last_time_digiproved"] != null
					//&& $dprv_post_info["last_time_digiproved"] == $dprv_post_info["last_time_updated"] )
				{
					//global $dprv_blog_host;  // check maybe this is not required

					dprv_getContentFiles($dprv_post_id, $check_content, $content_files, $content_file_names, 50, $file_count, $total_url_count, false);
					if ($file_count > 0)
					{
						$log->lwrite("file count = " . $file_count . ", count(content_files) = " . count($content_files) . ", count(content_file_names) = " . count($content_file_names));
						if (Digiprove::parseContentFiles($error_message, $content_files, $content_file_table))
						{
							$log->lwrite("file count = " . $file_count . ", count(content_file_table) = " . count($content_file_table));
							if (dprv_verifyContentFiles($error_message, $dprv_post_id, $content_file_table, $match_results))
							{
								$log->lwrite("file count = " . $file_count . ", count(match_results) = " . count($match_results));
								$files_integrity = 2;
								if ($dprv_post_info["last_time_digiproved"] != null && $dprv_post_info["last_time_digiproved"] == $dprv_post_info["last_time_updated"])
								{
									$files_integrity = 1;
								}
								$dprv_files_integrity_headline = sprintf(__("%s files Verified", "dprv_cp"), $file_count);
							}
							else
							{
								$integrity = false;
								$files_integrity = ($file_count * -1);
								$dprv_integrity_headline = sprintf(__("%s files - Tamper Warning", "dprv_cp"), $file_count);
							}
							if (is_array($match_results))
							{
								
								$comma = "";
								foreach ($match_results as $filename=>$status)
								{
									$dprv_files_integrity_message .= $comma . $filename . ": " . $status;
									$comma = ", \n";
								}
							}
						}
					}
				}
				$dprv_integrity_headline = "";
				$dprv_integrity_message = "";
				if (get_option('dprv_html_integrity') == "Yes")
				{

					$dprv_integrity_headline .= $dprv_html_integrity_headline;
					$dprv_integrity_message .= $dprv_html_integrity_message;
					if (get_option('dprv_files_integrity') == "Yes")
					{
						$dprv_integrity_headline .= "; ";
						$dprv_integrity_message .= "; \n";
					}
				}
				if (get_option('dprv_files_integrity') == "Yes")
				{
					$dprv_integrity_headline .= $dprv_files_integrity_headline;
					$dprv_integrity_message .= $dprv_files_integrity_message;
					if ($files_integrity > 0 && $html_integrity > 0)	// Ensure a nice neat headline if everything good for display in notice
					{
						if ($html_integrity == 1 && $files_integrity == 1)
						{
							$dprv_integrity_headline = __('All content certified &amp; verified', 'dprv_cp');
						}
						else
						{
							$dprv_integrity_headline =  __('All content verified', 'dprv_cp');
						}
					}
				}
				if ($integrity == false)
				{
					// Do something here so that the same message is not sent out a thousand times a day, just once would be enough
					wp_mail(get_option('dprv_email_address'), $dprv_integrity_headline, $dprv_integrity_message);
					update_option('dprv_pending_message', $dprv_integrity_headline . ": " . $dprv_integrity_message);
					$dprv_integrity_headline = "";	// blank out, we don't want to state a negative in the notice
					$dprv_integrity_message = "";
				}
			}
		}
	}
}


function dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, $preview, $dprv_first_year, $licenseType, $licenseCaption, $licenseAbstract, $licenseURL, $all_original, $attributions, $dprv_post_id, &$dprv_license_html, $dprv_integrity_headline, $dprv_integrity_message)
{
	$log = new DPLog(); 
	$log->lwrite("composeNotice starts, licenseType = " . $licenseType);
	$DigiproveNotice = "";
	$dprv_full_name = trim(get_option('dprv_first_name') . " " . get_option('dprv_last_name'));
	$dprv_notice = get_option('dprv_notice');
	if (trim($dprv_notice) == "")
	{
		$dprv_notice = __('This content has been Digiproved', 'dprv_cp');
	}
	$dprv_notice = str_replace(" ", "&nbsp;", $dprv_notice);
	if ($dprv_certificate_id === false || $dprv_certificate_url === false)
	{
		$DigiproveNotice = "\r\n&copy; " . Date("Y") . ' ' . __('and certified by Digiprove', 'dprv_cp');
	}
	else
	{
		$dprv_container = "span";
		$dprv_boxmodel = "display:inline-block;";	// minimise width, enforce upper/lower margins, no line break
		$dprv_box_pad_top = " 3px";
		$dprv_box_pad_right = " 3px";
		$dprv_box_pad_bottom = " 3px";
		$dprv_box_pad_left = " 3px";

		if (($attributions != false && $attributions != "" && $all_original != "Yes") || ($licenseType != false && $licenseType != "" && $licenseType != "Not Specified"))
		{
			$dprv_container = "div";
			$dprv_boxmodel = "display:table;";		// minimise width, enforce upper/lower margins, line break
			$dprv_box_pad_top = " 3px";
			$dprv_box_pad_bottom = " 3px";
		}

		$dprv_notice_background = get_option('dprv_notice_background');
		$background_css = "background:transparent none;";
		if ($dprv_notice_background != "None")
		{
			$background_css = 'background:' . $dprv_notice_background . ' none;';
		}
		$dprv_notice_color = get_option('dprv_notice_color');
		if ($dprv_notice_color == false || $dprv_notice_color == "")
		{
			$dprv_notice_color = "#636363";
		}
		$dprv_hover_color = get_option('dprv_hover_color');
		if ($dprv_hover_color == false || $dprv_hover_color == "")
		{
			$dprv_hover_color = "#A35353";
		}
		
		$dprv_border_css = 'border:1px solid #BBBBBB;';
		$dprv_notice_border = get_option('dprv_notice_border');
		if ($dprv_notice_border == "None")
		{
			$dprv_border_css = 'border:0px;';
		}
		else
		{
			if ($dprv_notice_border != false || $dprv_notice_border != "Gray")
			{
				$dprv_border_css = 'border:1px solid ' . strtolower($dprv_notice_border) . ';';
			}
		}

		$dprv_font_size="11px";
		$dprv_image_scale = "";
		$dprv_a_height = "16px";
		$dprv_line_height = "16px";
		$dprv_line_margin = "2px";
		$dprv_img_valign = "-3px";
		$dprv_txt_valign = "1px";
		$dprv_outside_font_size = "13px";
		$dprv_notice_pad_left = "24px";
		$dprv_notice_pad_left0 = "8px";
		$notice_size = get_option('dprv_notice_size');
		if ($notice_size == "Small")
		{
			$dprv_font_size="10px";
			$dprv_txt_valign = "2px";
		}
		if ($notice_size == "Smaller")
		{
			$dprv_font_size="9px";
			$dprv_image_scale = 'width:12px;height:12px;';
			$dprv_a_height = "12px";
			$dprv_line_height = "12px";
			$dprv_line_margin = "3px";
			$dprv_img_valign = "0px";
			$dprv_txt_valign = "3px";
			$dprv_notice_pad_left = "18px";
			$dprv_notice_pad_left0 = "6px";
			$dprv_box_pad_top = " 2px";
			$dprv_box_pad_bottom = " 2px";
		}
		if ($dprv_container == "div")
		{
			$dprv_box_pad_bottom = " 1px";
		}
		$container_style = 'vertical-align:baseline; padding:' . $dprv_box_pad_top . $dprv_box_pad_right . $dprv_box_pad_bottom . $dprv_box_pad_left . '; margin-top:2px; margin-bottom:2px; border-collapse:separate; line-height:' . $dprv_line_height . ';float:none; font-family: Tahoma, MS Sans Serif; font-size:' . $dprv_outside_font_size . ';' . $dprv_border_css . $background_css . $dprv_boxmodel;

		// TODO - put date and time into locale of user
		/* translators: the language code that will be used for the lang attribute of the Digiprove notice - http://www.w3.org/TR/html4/struct/dirlang.html#adef-lang */
		$lang = __('en', 'dprv_cp');
		$DigiproveNotice = '<' . $dprv_container . ' id="dprv_cp-v' . DPRV_VERSION . '" lang="' . $lang . '" xml:lang="' . $lang . '" class="notranslate" style="' . $container_style . '" title="' . sprintf(__('certified %1$s by Digiprove certificate %2$s', 'dprv_cp'),  $dprv_utc_date_and_time, $dprv_certificate_id) . '" >';

		$DigiproveNotice .= '<a href="' . $dprv_certificate_url . '" target="_blank" rel="copyright" style="height:' . $dprv_a_height . '; line-height: ' . $dprv_a_height . '; border:0px; padding:0px; margin:0px; float:none; display:inline; text-decoration: none; background:transparent none; line-height:normal; font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:' . $dprv_font_size . ';">';
		
		$DigiproveNotice .= '<img src="' . plugins_url("dp_seal_trans_16x16.png", __FILE__ ) . '" style="max-width:none !important;' . $dprv_image_scale . 'vertical-align:' . $dprv_img_valign . '; display:inline; border:0px; margin:0px; padding:0px; float:none; background:transparent none" alt="Digiprove seal"/>';

		$DigiproveNotice .= '<span style="font-family: Tahoma, MS Sans Serif; font-style:normal; font-size:' . $dprv_font_size . '; font-weight:normal; color:' . $dprv_notice_color . '; border:0px; float:none; display:inline; text-decoration:none; letter-spacing:normal; padding:0px; padding-left:' . $dprv_notice_pad_left0 . '; vertical-align:' . $dprv_txt_valign . ';margin-bottom:' . $dprv_line_margin . '" ';

		if ($preview != true)
		{
			$DigiproveNotice .= 'onmouseover="this.style.color=\'' . $dprv_hover_color . '\';" onmouseout="this.style.color=\'' . $dprv_notice_color . '\';"';
		}
		$DigiproveNotice .= '>' . $dprv_notice;
		$dprv_c_notice = get_option('dprv_c_notice');
		if ($dprv_c_notice != "NoDisplay")
		{
			$dprv_year = Date('Y');   // default is this year
			// Extract year from date_and_time
			$posB = stripos($dprv_utc_date_and_time, " UTC");
			if ($posB != false && $posB > 13)
			{
				$dprv_year = substr($dprv_utc_date_and_time, $posB-13, 4);  // This should work if HH:MM:SS always has length of 8
			}
			if (trim($dprv_first_year) != "" && $dprv_year != $dprv_first_year)
			{
				$dprv_year = $dprv_first_year . "-" . $dprv_year;
			}
			$DigiproveNotice .= '&nbsp;&copy;&nbsp;' . $dprv_year;
			//if ($dprv_c_notice == "DisplayAll" && $dprv_full_name != "")
			//{
			//	$DigiproveNotice .= '&nbsp;' . 	str_replace(" ", "&nbsp;", htmlspecialchars(stripslashes($dprv_full_name), ENT_QUOTES, 'UTF-8'));
			//}
			if ($dprv_c_notice == "DisplayAll")
			{
				$dprv_copyright_owner = $dprv_full_name;
				if (get_option('dprv_submitter_has_copyright') == "Yes")
				{
					$dprv_post_object = get_post($dprv_post_id);
					if (is_object($dprv_post_object) && isset($dprv_post_object->post_author))

					{
						$dprv_post_author = $dprv_post_object->post_author;
						$dprv_author_object = get_user_by('id', $dprv_post_author);
						$dprv_copyright_owner = trim($dprv_author_object->first_name . ' ' . $dprv_author_object->last_name);
					}
				}
				$DigiproveNotice .= '&nbsp;' . 	str_replace(" ", "&nbsp;", htmlspecialchars(stripslashes($dprv_copyright_owner), ENT_QUOTES, 'UTF-8'));
			}
		}
		$DigiproveNotice .= '</span></a>';

		$span_style = "font-family: Tahoma, MS Sans Serif; font-style:normal; display:block; font-size:" . $dprv_font_size . "; font-weight:normal; color:" . $dprv_notice_color . "; border:0px; float:none; text-align:left; text-decoration:none; letter-spacing:normal; line-height:" . $dprv_a_height . "; vertical-align:" . $dprv_txt_valign . "; padding:0px; padding-left:" . $dprv_notice_pad_left . ";margin-bottom:" . $dprv_line_margin . ";";
		$mouseover = "";
		if ($preview != true)
		{
			$mouseover = 'onmouseover="this.style.color=\'' . $dprv_hover_color . '\';" onmouseout="this.style.color=\'' . $dprv_notice_color . '\';"';
		}
		if ($dprv_integrity_headline != false && $dprv_integrity_headline != "")
		{
			$DigiproveNotice .= "<div id=\"dprv_integrity\" style=\"" . $span_style . "\" ";
			$DigiproveNotice .= "title=\"" . $dprv_integrity_message . "\">";
			$DigiproveNotice .=  __("Content integrity", "dprv_cp") . ":&nbsp;" . $dprv_integrity_headline . "</div>";
		}
		if ($attributions != false && $attributions != "" && $all_original != "Yes")
		{
			$DigiproveNotice .= "<div id=\"dprv_attribution\" style=\"" . $span_style . "\" ";
			if (strlen($attributions) < 45)
			{
				$DigiproveNotice .= "title=\"" . __("Attributions - owner(s) of some content", "dprv_cp") . "\">";
				$DigiproveNotice .=  __("Acknowledgements", "dprv_cp") . ":&nbsp;" . htmlspecialchars(stripslashes($attributions), ENT_QUOTES, 'UTF-8') . "</div>";
			}
			else
			{
				$DigiproveNotice .= "title=\"" . __("Attributions - owner(s) of some content - click to see full text", "dprv_cp") . "\" onclick=\"dprv_DisplayAttributions('" . __("Acknowledgements", "dprv_cp") . ":&nbsp;" . htmlspecialchars($attributions, ENT_QUOTES, 'UTF-8') . "')\" " . $mouseover . ">";
				$DigiproveNotice .=  __("Acknowledgements", "dprv_cp") . ":&nbsp;" . htmlspecialchars(stripslashes(substr($attributions, 0, 37)), ENT_QUOTES, 'UTF-8') . __(" more...", "dprv_cp") . "</div>";
			}
		}
		//$log->lwrite("licenseType = " . $licenseType . ", licenseCaption=" . $licenseCaption);
		if ($licenseType != false && $licenseType != "" && $licenseType != "Not Specified")
		{
			$DigiproveNotice .= "<a title='" . __("Click to see details of license", "dprv_cp") . "' href=\"javascript:dprv_DisplayLicense('" . $dprv_post_id . "')\" style=\"" . $span_style . "\" " . $mouseover . " target=\"_self\">";
			$DigiproveNotice .= $licenseCaption;
			$DigiproveNotice .= "</a>";
			// Need to replace transparency with inversion of text color (as license_panel is a layer):
			if (strpos($background_css, "transparent") != false)
			{
				$t1 = '0123456789ABCDEF#';
				$t2 = '89ABCDEF01234567#';
				$w_color = strtoupper($dprv_notice_color);
				$background_color = "";
				for ($i=0; $i<strlen($w_color); $i++)
				{
					$pos = strpos($t1, substr($w_color, $i,1));
					$background_color .= substr($t2, $pos,1);
				}
				$background_css = 'background:' . $background_color . ' none; opacity:0.8; filter:alpha(opacity=80);';
				//$log->lwrite("calculated background color of " . $background_color . " from foreground " . $dprv_notice_color);
			}
			$dprv_license_html = '<div id="license_panel' . $dprv_post_id . '" style="position: absolute; display:none ; font-family: Tahoma, MS Sans Serif; font-style:normal; font-size:' . $dprv_font_size . '; font-weight:normal; color:' . $dprv_notice_color . ';' . $dprv_border_css . ' float:none; max-width:640px; text-decoration:none; letter-spacing:normal; line-height:' . $dprv_line_height . '; vertical-align:' . $dprv_txt_valign . '; padding:0px;' . $background_css . '">';
			$dprv_license_html .= '<table class="dprv" style="line-height:17px;margin:0px;background-color:transparent;font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:' . $dprv_font_size . '; color:' . $dprv_notice_color . '"><tbody>';
			$dprv_license_html .= '<tr><td colspan="2" style="background-color:transparent;border:0px;font-weight:bold;padding:0px;padding-left:6px; text-align:left">' . __("Original content here is published under these license terms", "dprv_cp") . ':</td><td style="width:20px;background-color:transparent;border:0px;padding:0px"><span style="float:right; background-color:black; color:white; width:20px; text-align:center; cursor:pointer" onclick="dprv_HideLicense(\'' . $dprv_post_id . '\')">&nbsp;X&nbsp;</span></td></tr>';
			$dprv_license_html .= '<tr><td colspan="3" style="height:4px;padding:0px;background-color:transparent;border:0px"></td></tr>';
			$dprv_license_html .= '<tr><td style="width:130px;background-color:transparent;padding:0px;padding-left:4px;border:0px; text-align:left">' . __('License Type', 'dprv_cp') . ':</td><td style="width:300px;background-color:transparent;border:0px;padding:0px; text-align:left">' . htmlspecialchars(stripslashes($licenseType), ENT_QUOTES, "UTF-8") . '</td><td style="border:0px; background-color:transparent"></td></tr>';
			$dprv_license_html .= '<tr><td colspan="3" style="height:4px;background-color:transparent;padding:0px;border:0px"></td></tr>';
			$dprv_license_html .= '<tr><td style="background-color:transparent;padding:0px;padding-left:4px;border:0px; vertical-align:top; text-align:left">' . __('License Summary', 'dprv_cp') . ':</td><td colspan="2" style="background-color:transparent;border:0px;padding:0px; vertical-align:top; text-align:left">' . htmlspecialchars(stripslashes($licenseAbstract), ENT_QUOTES, "UTF-8") . '</td></tr>';
			if ($licenseURL != '')
			{
				$dprv_license_html .= '<tr><td colspan="3" style="height:4px;background-color:transparent;padding:0px;border:0px"></td></tr>';
				$dprv_license_html .= '<tr><td style="background-color:transparent;padding:0px;padding-left:4px;border:0px; text-align:left">' . __('License URL', 'dprv_cp') . ':</td><td colspan="2" style="background-color:transparent;border:0px;padding:0px; text-align:left"><a href="' . htmlspecialchars(stripslashes($licenseURL), ENT_QUOTES, "UTF-8") . '" target="_blank" rel="license">' . htmlspecialchars(stripslashes($licenseURL), ENT_QUOTES, "UTF-8") . '</a></td></tr>';
			}

			$dprv_license_html .= '</tbody></table></div>';
		}
		$DigiproveNotice .= '<!--' . $dprv_digital_fingerprint . '-->';
		$DigiproveNotice .= '</' . $dprv_container . '>';
	}
	return $DigiproveNotice;
}

?>