<?php
/*
Plugin Name: Copyright Proof
Plugin URI: http://www.digiprove.com/copyright_proof_wordpress_plugin.aspx
Description: Digitally certify your Wordpress posts to prove copyright ownership.
Version: 0.79
Author: Digiprove
Author URI: http://www.digiprove.com/
License: GPL
*/
/*  Copyright 2008-2010  Digiprove (email : cian.kinsella@digiprove.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
// NOTE THIS IS THE PHP4-FRIENDLY VARIANT OF COPYRIGHT PROOF
// Acknowledgement to Honza Odvarko, whose jscolor color-picker is used in this plug-in 
//  under the GNU Lesser General Public License (LGPL): www.gnu.org/copyleft/lesser.html

// Acknowledgements to BTE, Akismet, and After the Deadline, some of whose code was used
// in the development of this plug-in

if (intval(substr(PHP_VERSION,0,1)) > 4)
{
	include_once('copyright_proof_http_functions.php');
}
else
{
	//die("Your version of PHP (" . PHP_VERSION . ") does not support this plug-in. Ask your provider to upgrade your website to PHP 5.0 or later");
	include_once('copyright_proof_http_functions_basic.php');
}
// Declare and initialise global variables:
global $dprv_log_is_on, $dprv_host, $dprv_port, $dprv_ssl, $start_Digiprove, $end_Digiprove, $dprv_soap_count;
$dprv_log_is_on = false;             // Set this to true to generate local log-file (needs write permissions)
$dprv_host = "www.digiprove.com";    // -> should be set to "www.digiprove.com"
$dprv_port = 443;                    // -> should be set to 443 (standard settings 80 for http, 443 for https)
$dprv_ssl = "Yes";                   // -> should be set to "Yes"
$start_Digiprove = false;
$end_Digiprove = false;
$dprv_soap_count=0;
define("DPRV_VERSION", "0.79");

// Register hooks
register_activation_hook(__FILE__, 'dprv_activate');
register_deactivation_hook(__FILE__, 'dprv_deactivate');
add_action('init', 'dprv_init');
add_action('admin_menu', 'dprv_settings_menu');
add_action('admin_head', 'dprv_admin_head');
add_action('admin_footer', 'dprv_admin_footer');
add_filter('wp_insert_post_data', 'dprv_digiprove_post_new', 99, 2);
add_filter('wp_insert_page_data', 'dprv_digiprove_post_new', 99, 2);
// Instruction below part of new strategy for storing/displaying Digiprove certificate data
//add_filter( "the_content", "dprv_display_notice" );


function dprv_activate()
{
	$log = new Logging();  
	$log->lwrite("VERSION " . DPRV_VERSION . " ACTIVATED");  
//	$log->lwrite("PHP Version = " . PHP_VERSION);  
	add_option('dprv_email_address', '');
	add_option('dprv_first_name', '');
	add_option('dprv_last_name', '');
	add_option('dprv_subscription_type', '');
	add_option('dprv_content_type', '');
	add_option('dprv_notice', '');
	add_option('dprv_c_notice', 'DisplayAll');
	add_option('dprv_notice_size', '');
	add_option('dprv_frustrate_copy', '');
	add_option('dprv_right_click_message', '');
	add_option('dprv_record_IP', '');
	add_option('dprv_notice_border', '');
	add_option('dprv_notice_background', '');
	add_option('dprv_notice_color', '');
	add_option('dprv_hover_color', '');
	add_option('dprv_obscure_url','Obscure');
	add_option('dprv_display_name','Yes');
	add_option('dprv_email_certs','Yes');
	add_option('dprv_linkback','Linkback');
	add_option('dprv_body_or_footer', 'Body');
	add_option('dprv_enrolled', 'No');
	add_option('dprv_user_id', '');
	add_option('dprv_api_key', '');
	add_option('dprv_last_result', '');
}

function dprv_deactivate()
{
	$log = new Logging();  
	$log->lwrite("VERSION " . DPRV_VERSION . " DEACTIVATED");  
	delete_option('dprv_last_result');	// keep other options for future install
}

function dprv_init()
{
	if ( get_option('dprv_enrolled') != "Yes" )
	{
		function dprv_reminder()
		{
			echo "<div id='dprv_reminder' class='updated fade'><p><strong>".__("Copyright Proof is almost ready.")."</strong> ".__("You must <a href=\"options-general.php?page=CopyrightProof.php\"><b>Configure Copyright Proof and Register</b></a> to get it working</p></div>");
		}
		add_action('admin_notices', 'dprv_reminder');
	}
}


function dprv_settings_menu()	// Runs after the basic admin panel menu structure is in place - add Copyright Proof Settings option.
{	
	$pagename = add_options_page('DigiproveBlog', 'Copyright Proof', 10, basename(__FILE__), 'dprv_settings');
}

function dprv_admin_head()	// runs between <HEAD> tags of admin settings page - include js file
{
	$log = new Logging();  
	$log->lwrite("dprv_admin_head starts");  
	$home = get_settings('siteurl');
	$base="digiproveblog";
	$jsfile = $home.'/wp-content/plugins/' . $base . '/jscolor.js';
	echo('<script type="text/javascript" src="' . $jsfile . '"></script>');
}

function dprv_admin_footer()	// runs in admin panel inside body tags - add Digiprove message to message bar
{
	$log = new Logging();  
	$log->lwrite("dprv_admin_footer starts");
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}

	$dprv_last_result = "";
	if ($script_name == "post")
	{
		$dprv_last_result = htmlentities(get_option('dprv_last_result'), ENT_QUOTES, 'UTF-8');
		$log->lwrite("dprv_last_result=$dprv_last_result");
		if (strpos($dprv_last_result, 'Error:') !== false)
		{
			$dprv_last_result = '<font color=orangered>' . $dprv_last_result . '</font>';
		}

		if ($dprv_last_result != "")
		{
			$log->lwrite("writing javascript to display dprv_last_result as a message");
			echo('<script type="text/javascript">
				if (document.getElementById("message") && document.getElementById("message") != null)
				{
					var existing_message = document.getElementById("message").innerHTML;
					var pos = existing_message.toLowerCase().indexOf("</p>");
					var pub = existing_message.indexOf("published");
					var upd = existing_message.indexOf("updated");
					if (pos > 0 && (pub != -1 || upd != -1))
					{
						document.getElementById("message").innerHTML = existing_message.substr(0,pos) + "&nbsp;&nbsp;&nbsp;&nbsp;Digiprove message: ' . $dprv_last_result . '" + existing_message.substr(pos);
					}
				}
				</script>');
		}
	}
	if ($script_name == "plugins")
	{
		$dprv_last_result = get_option('dprv_last_result');
		if ($dprv_last_result != "" && strpos($dprv_last_result, "Configure") != false)
		{
			$log->lwrite("writing javascript to display " . $dprv_last_result);
			echo('<script type="text/javascript">
			if (document.getElementById("message") && document.getElementById("message") != null)
				{
					var existing_message = document.getElementById("message").innerHTML;
					var pos = existing_message.toLowerCase().indexOf("</p>");
					var act = existing_message.indexOf("activated");
					if (pos > 0 && act != -1)
					{
						document.getElementById("message").innerHTML = existing_message.substr(0,pos) + "&nbsp;&nbsp;&nbsp;&nbsp;Digiprove message: ' . $dprv_last_result . '" + existing_message.substr(pos);
					}
				}
				</script>');
			update_option('dprv_last_result', '');
		}
	}
	//$log->lwrite("exiting drp_admin_footer");
}


function dprv_digiprove_post_new ($data, $raw_data)	// Core Digiprove-this-post function
{
	$log = new Logging();  
	$log->lwrite("dprv_digiprove_post_new starts");

	if ($data['post_status'] != "publish")
	{
		$log->lwrite("dprv_digiprove_post not starting because status (" . $data['post_status'] . ") is not publish");
		return $data;
	}
	if ($data['post_type'] != "post" && $data['post_type'] != "page")
	{
		$log->lwrite("dprv_digiprove_post not starting because type (" . $data['post_type'] . ") is not post or page");  // Does this ever occur?
		return $data;
	}

	if (get_option('dprv_enrolled') != "Yes")
	{
		$log->lwrite("dprv_digiprove_post not starting because user not registered yet");
		return $data;
	}
	$content = trim($data['post_content']);
	if (strlen(trim($content)) == 0)
	{
		$log->lwrite("dprv_digiprove_post not starting because content is empty");
		return $data;
	}

	update_option('dprv_last_result', '');
	$dprv_post_id = $raw_data['ID'];
	if (intval($dprv_post_id) == 0)
	{
		$dprv_post_id = -1;
	}
	$dprv_title = $data['post_title'];
	$log->lwrite("title=" . $dprv_title . ", id=" . $dprv_post_id);  
	$log->lwrite("dprv_digiprove_post STARTS");  
	
	$newContent = stripslashes($content);
	$certifyResponse = dprv_certify($dprv_post_id, $dprv_title, $newContent);
	if (strpos($certifyResponse, "Hashes are identical") === false)
	{
		if (strpos($certifyResponse, "Raw content is empty") === false)
		{
			$pos = strpos($certifyResponse, "<result_code>0");
			if ($pos === false)
			{
				$log->lwrite("Digiproving failed, response:");
				$log->lwrite($certifyResponse);
				$admin_message = dprv_getTag($certifyResponse,"result");
				if ($admin_message == false)
				{
					$admin_message = $certifyResponse;
				}
				else
				{
					$admin_message = 'Note: ' . $admin_message;
				}
				update_option('dprv_last_result', $admin_message);
			}
			else
			{
				// This code is to replace password with a new API key, eventually we'll eliminate passwords from db
				$dprv_api_key = dprv_getTag($certifyResponse, "api_key");
				if ($dprv_api_key != false)
				{
					update_option('dprv_api_key',$dprv_api_key);
					delete_option('dprv_password');
				}
				else
				{
					// If there was a working api key, delete any recorded password from the options db
					$dprv_api_key = get_option('dprv_api_key');
					if ($dprv_api_key != null & $dprv_api_key != "")
					{
						delete_option('dprv_password');
					}
				}
				// End of API key code

				$dprv_subscription_type = dprv_getTag($certifyResponse, "subscription_type");
				if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
				{
					update_option('dprv_subscription_type', $dprv_subscription_type);
				}

				$log->lwrite("data[post_content] = ");
				$log->lwrite($data['post_content']);

				// New strategy:
				// Instructions below write certificate information as post/page metadata
				// and remove any previously embedded Digiprove notice from the content
				// Note this also requires the instruction "add_filter( "the_content", "dprv_display_notice" );" in Register hooks section
				// TODO before implementation - find neat way to give access to Digiproving history
				//dprv_record_dp_action($dprv_post_id, $certifyResponse);
				//$data['post_content'] = dprv_strip_old_notice($newContent);


				// Old Strategy
				// Instructions below create a formatted Digiprove notice from the certificate information
				// and embed it directly in the content (replacing any earlier notice)
				// If using this method remove the instruction "add_filter( "the_content", "dprv_display_notice" );" in Register hooks section
				$DigiproveNotice = dprv_composeNotice($certifyResponse);
				$data['post_content'] = dprv_insertNotice($newContent, $DigiproveNotice);

				// Surely the following bit is unnecessary?  Check and delete:			
				if (get_option('dprv_enrolled') != "Yes")
				{
					update_option('dprv_enrolled', 'Yes');
				}
				// end of unnecessary bit

				$log->lwrite("Digiproving completed successfully");
				update_option('dprv_last_result', __('Digiprove certificate id:', 'dprv_cp') . ' ' . dprv_getTag($certifyResponse, "certificate_id"));
			}
		}
		else
		{
			// The only real content was the last Digiprove certificate; remove it
			update_option('dprv_last_result', __('Content is empty', 'dprv_cp'));
			$data['post_content'] = "";
			return $data;		
		}
	}
	else
	{
		update_option('dprv_last_result', __('Content unchanged since last edit', 'dprv_cp'));
	}

	return $data;
}

function dprv_strip_old_notice($content)
{
	$start_Digiprove = strpos($content, "<!--Digiprove_Start-->");
	$end_Digiprove = false;
	if ($start_Digiprove === false)
	{
		return $content;
	}
	$end_Digiprove = strpos($content, "<!--Digiprove_End-->");
	if ($end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		return $content;

	}
	if ($start_Digiprove != 0)
	{
		$raw_content = substr($content, 0, $start_Digiprove) . substr($content, $end_Digiprove + 20);
	}
	else
	{
		$raw_content = substr($content, $end_Digiprove + 20);
	}
	return $raw_content;
}

function dprv_display_notice($content)
{
	$log = new Logging();  
	$post_id = get_the_ID();
	$log->lwrite("dprv_display_notice starts. post id = " . $post_id);
	$dprv_notice = "";
	$high_cert_no = 0;
	$high_cert_key = "";
	$custom_field_keys = get_post_custom_keys($post_id);
	foreach ( $custom_field_keys as $key => $value )
	{
		if (strlen($value) > 22 && substr_compare($value, "digiprove_certificate_", 0, 22) == 0)
		{
			$this_cert_no = intval(substr($value,23));
			if ($this_cert_no > $high_cert_no)
			{
				$high_cert_no = $this_cert_no;
				$high_cert_key = $value;
			}
		}
	}
	if ($high_cert_no > 0)
	{
		$certificate_info = get_post_meta($post_id, $high_cert_key, true);
		$dprv_notice = dprv_composeNotice($certificate_info);
		$content .=  "\r\n\r\n<!--Digiprove_Start-->" . $dprv_notice . "<!--Digiprove_End-->";
	}
	return $content;
}

function dprv_record_dp_action($post_id, $certifyResponse)
{
	$log = new Logging();  
	$log->lwrite("dprv_record_dp_action starts. post id = " . $post_id);
	$dprv_certificate_id = dprv_getTag($certifyResponse, "certificate_id");
	$posA = strpos($certifyResponse, "<digiprove_content_response>");
	$posB = strpos($certifyResponse, "</digiprove_content_response>") + 29;
	add_post_meta($post_id, "digiprove_certificate_" . $dprv_certificate_id, substr($certifyResponse, $posA, $posB-$posA), true);
}

function dprv_composeNotice($certifyResponse)
{
	$log = new Logging();  
	$DigiproveNotice = "";
	$dprv_certificate_id = dprv_getTag($certifyResponse, "certificate_id");
	$dprv_certificate_url = dprv_getTag($certifyResponse, "certificate_url");
	$dprv_utc_date_and_time = dprv_getTag($certifyResponse, "utc_date_and_time");
	$dprv_digital_fingerprint = dprv_getTag($certifyResponse, "digital_fingerprint");
	$dprv_full_name = trim(get_option('dprv_first_name') . " " . get_option('dprv_last_name'));
	$dprv_notice = get_option('dprv_notice');
	if (trim($dprv_notice) == "")
	{
		$dprv_notice = __('This content has been Digiproved', 'dprv_cp');
	}
	if ($dprv_certificate_id === false || $dprv_certificate_url === false)
	{
		$DigiproveNotice = "\r\n&copy; " . Date("Y") . ' ' . __('and certified by Digiprove', 'dprv_cp');
	}
	else
	{
		$dprv_notice_background = get_option('dprv_notice_background');
		$backgroundStyle = "";
		if ($dprv_notice_background != "None")
		{
			$backgroundStyle = 'background-color:' . $dprv_notice_background . ';';
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

		$dprv_frustrate_copy = get_option('dprv_frustrate_copy');
		$dprv_right_click_message = get_option('dprv_right_click_message');
		$dprv_record_IP = get_option('dprv_record_IP');

		if ($dprv_record_IP == "Yes")
		{
			$DigiproveNotice .= "<script src='record_IP.js' type='text/javascript'></script>";
			$dprv_wp_url = parse_url(get_option('siteurl'));
			$dprv_wp_host = $dprv_wp_url[host];
			$log->lwrite("dprv_wp_host = " . $dprv_wp_host);  
			$DigiproveNotice .= "<form action='http://" . $dprv_wp_host . "/copyright_proof_handler.php' method='post' id='IPAddress'><input type='hidden' value='" . @$REMOTE_ADDR . "' /></form>";
		}

		if ($dprv_frustrate_copy == "Yes")
		{
			$home = get_settings('siteurl');
			$DigiproveNotice .= "<style type='text/css'>body{-moz-user-select: none;}</style><script type='text/javascript'>var noRightClickMessage='" . $dprv_right_click_message . "';</script><script type='text/javascript' src='" . $home . "/wp-content/plugins/digiproveblog/frustrate_copy.js'></script>";
		}
		$DigiproveNotice .= '<span style="vertical-align:middle; display:inline; padding:3px; line-height:normal;';
		$dprv_notice_border = get_option('dprv_notice_border');
		if ($dprv_notice_border == "None")
		{
			$DigiproveNotice .= 'border:0px;';
		}
		else
		{
			if ($dprv_notice_border == false || $dprv_notice_border == "Gray")
			{
				$DigiproveNotice .= 'border:1px solid #BBBBBB;';
			}
			else
			{
				$DigiproveNotice .= 'border:1px solid ' . strtolower($dprv_notice_border) . ';';
			}
		}
		$dprv_font_size="11px";
		$dprv_image_scale = "";
		$notice_size = get_option('dprv_notice_size');
		if ($notice_size == "Small")
		{
			$dprv_font_size="10px";
		}
		if ($notice_size == "Smaller")
		{
			$dprv_font_size="9px";
			$dprv_image_scale = ' width="12px" height="12px"';
		}
		$DigiproveNotice .= $backgroundStyle . '" '; 
		$DigiproveNotice .= 'title="certified ' . $dprv_utc_date_and_time . ' by Digiprove certificate ' . $dprv_certificate_id . '" >';
		$DigiproveNotice .= '<a href="' . $dprv_certificate_url . '" target="_blank" rel="copyright" ';
		$DigiproveNotice .= 'style="border:0px; float:none; display:inline; text-decoration: none;' . $backgroundStyle . '">';
		$DigiproveNotice .= '<img src="http://www.digiprove.com/images/dp_seal_trans_16x16.png" style="vertical-align:middle; display:inline; border:0px; margin:0px; float:none; background-color:transparent" border="0"' . $dprv_image_scale . ' alt=""/>';
		$DigiproveNotice .= '<span style="font-family: Tahoma, MS Sans Serif; font-size:' . $dprv_font_size . '; color:' . $dprv_notice_color . '; border:0px; float:none; display:inline; text-decoration:none; letter-spacing:normal" ';
		$DigiproveNotice .= 'onmouseover="this.style.color=\'' . $dprv_hover_color . '\';" onmouseout="this.style.color=\'' . $dprv_notice_color . '\';">';
		$DigiproveNotice .= '&nbsp;&nbsp;' . $dprv_notice;
		$dprv_c_notice = get_option('dprv_c_notice');
		if ($dprv_c_notice != "NoDisplay")
		{
			$DigiproveNotice .= '&nbsp;&copy; ' . Date('Y');
			if ($dprv_c_notice == "DisplayAll" && $dprv_full_name != "")
			{
				$DigiproveNotice .= ' ' . $dprv_full_name;
			}
		}
		$DigiproveNotice .= '</span></a>';
		$DigiproveNotice .= '<!--' . $dprv_digital_fingerprint . '-->';
		$DigiproveNotice .= '</span>';
	}
	return $DigiproveNotice;
}

function dprv_insertNotice($content, $DigiproveNotice)
{
	$log = new Logging();  
	$log->lwrite("dprv_insertNotice starts");  
	global $start_Digiprove, $end_Digiprove;
	if ($start_Digiprove === false || $end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		if ($end_Digiprove !== false)
		{
			if ($end_Digiprove <= $start_Digiprove)
			{
				$content = substr($content, 0, $end_Digiprove) . substr ($content, $end_Digiprove + 20);	// strip out confusing and extraneous end_Digiprove tag
			}
		}
		if ($start_Digiprove > -1)
		{
			$newContent = substr($content, 0, $start_Digiprove + 22) . $DigiproveNotice . "<!--Digiprove_End-->";
		}
		else
		{
			$newContent = $content . "\r\n\r\n<!--Digiprove_Start-->" . $DigiproveNotice . "<!--Digiprove_End-->";
		}
	}
	else
	{
		$newContent = substr($content, 0, $start_Digiprove + 22) . $DigiproveNotice . substr($content, $end_Digiprove);
	}
	return $newContent;
}

function dprv_certify($post_id, $title, $content)
{
	$log = new Logging();  
	global $wp_version, $dprv_host;

	$log->lwrite("dprv_certify starts");

	$rawContent = dprv_getRawContent($content);
	if ($rawContent === false)	// Content has not changed, do not Digiprove again - stick with earliest certification
	{
		return " Hashes are identical";
	}
	if ($rawContent == "")
	{
		return " Raw content is empty";
	}
	$rawContent = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');
	// Statement below inserted at 0.75 as vertical tabs not converted and cause a problem in XML .net server process
	// TODO: there are probably other characters that will trip it up - review whole XML-encoding to create more systemic solution
	$rawContent = str_replace("\v", " ", $rawContent);

	// Prepare title for XML transmission
	if (intval(substr(PHP_VERSION,0,1)) > 4)	// Skip this step if before PHP 5 as PHP4 cannot cope with it - not the end of the world in this case
	{
		$title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');   // first go back to basic string (have seen WLW-sourced titles with html-encoding embedded)
	}
	$title = htmlspecialchars(stripslashes($title), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)

	$dprv_content_type = get_option('dprv_content_type');
	// following instruction inserted at 0.75 to prevent problems with unescaped character '&' causing server-side XML parsing error
	$dprv_content_type = htmlspecialchars(stripslashes($dprv_content_type), ENT_QUOTES, 'UTF-8');
	if (trim($dprv_content_type) == "")
	{
		$dprv_content_type = "Blog post";
	}
	
	// code below changed at 0.78 to avoid empty domain
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url[host];
	$dprv_wp_host = "";		// default

	$dprv_wp_url = parse_url(get_option('siteurl'));
	$dprv_wp_host = $dprv_wp_url[host];
	if (trim($dprv_blog_host) == "")
	{
		$dprv_blog_host = $dprv_wp_host;
	}

	$postText = "<digiprove_content_request>";
	$postText .= "<user_id>" . get_option('dprv_user_id') . "</user_id>";
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';
	if ($dprv_blog_host != $dprv_wp_host)
	{
		$postText .= '<alt_domain_name>' . $dprv_wp_host . '</alt_domain_name>';
	}
	$dprv_api_key = get_option('dprv_api_key');
	if ($dprv_api_key != null & $dprv_api_key != "")
	{
		$postText .= '<api_key>' . $dprv_api_key . '</api_key>';
	}
	else
	{
		$dprv_password = htmlspecialchars(stripslashes(get_option('dprv_password')), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)
		$postText .= '<password>' . $dprv_password . '</password>';
		$postText .= '<request_api_key>Yes</request_api_key>';
	}

	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
    $postText .= '<content_type>' . $dprv_content_type . '</content_type>';
    $postText .= '<content_title>' . $title . '</content_title>';
    $postText .= '<content_data>' . $rawContent . '</content_data>';
    if (get_option('dprv_linkback') == "Linkback")
	{
		$postText .= '<content_url>';
		$permalink = get_bloginfo('url');
		if ($post_id != -1)									// will be set to -1 if the value is unknown
		{
			// Decided not to implement soft permalinks as can end up with broken links if user changes scheme
			//$permalink = get_permalink($post_id);
			//if ($permalink == false || $permalink == "")
			//{
				$permalink = get_bloginfo('url') . "/?p=" . $post_id;
			//}
		}
		$postText .= $permalink;
		$postText .= '</content_url>';
	}
	if (get_option('dprv_obscure_url') == "Clear")
	{
		$postText .= '<obscure_certificate_url>No</obscure_certificate_url>';
	}
	else
	{
		$postText .= '<obscure_certificate_url>Yes</obscure_certificate_url>';
	}
	$postText .= '</digiprove_content_request>';
	$log->lwrite("xml string = " . $postText);

	//TODO: detect whether soap installed and if so use soap_post, otherwise use http_post
	//$data = dprv_soap_post($postText, "DigiproveContent");
	$data = dprv_http_post($postText, $dprv_host, "/secure/service.asmx/", "DigiproveContent");
	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_certify");
	}
	return $data;
}

function dprv_getTag($xmlString, $tagName)
{
	$start_contents = strpos($xmlString, "<" . $tagName . ">") + strlen($tagName) + 2;
	$end_tag = strpos($xmlString, "</" . $tagName . ">");
	if ($start_contents === false || $end_tag === false || $end_tag <= $start_contents)
	{
		return false;
	}
	return substr($xmlString, $start_contents, $end_tag - $start_contents);
}

function dprv_getRawContent($contentString)		// Extract raw content to be Digiproved, ignoring previous Digiprove embedded certs and rationalise to ignore effects of Wordpress formatting
{
	global $start_Digiprove, $end_Digiprove;
	$log = new Logging();  
	//$log->lwrite("getRawContent starts, content=" . $contentString);
	// TODO - Find last hash from metadata to compare with last digiproving
	$start_Digiprove = strpos($contentString, "<!--Digiprove_Start-->");
	$end_Digiprove = false;
	if ($start_Digiprove === false)
	{
		$log->lwrite("no Digiprove Start marker");
		return dprv_normaliseContent($contentString);
	}
	$end_Digiprove = strpos($contentString, "<!--Digiprove_End-->");
	if ($start_Digiprove === false || $end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		$log->lwrite("no Digiprove_End marker or not greater than start");  
		return dprv_normaliseContent($contentString);

	}
	$log->lwrite("Previous Digiprove notice exists");
	$existing_notices = "";
	if ($start_Digiprove != 0)
	{
		$raw_content = substr($contentString, 0, $start_Digiprove) . substr($contentString, $end_Digiprove + 20);
		$existing_notices = substr($contentString, $start_Digiprove + 22, $end_Digiprove - ($start_Digiprove + 22));
	}
	else
	{
		$raw_content = substr($contentString, $end_Digiprove + 20);
	}
	$raw_content = trim($raw_content);

	$raw_content = dprv_normaliseContent($raw_content);

	if (function_exists("hash"))					// Before 5.1.2, the hash() function did not exist, calling it gives a fatal error
	{
		$raw_content_hash = strtoupper(hash("sha256", $raw_content));
		//$log->lwrite("Content fingerprinted = " . $raw_content);
		$log->lwrite("Digital fingerprint = " . $raw_content_hash);

		if (strpos($existing_notices, $raw_content_hash) != false)		// if SHA256 hash of raw content already exists in notices, the content is unchanged since last Digiprove, so we will abandon from here
		{
			if (strpos($existing_notices, '<a ') > 0 && strpos($existing_notices, '</a>') > 0)	// basic check that Digiprove certificate details are intact 
			{
				$log->lwrite("Content fingerprint same as before and notice is intact, no need to Digiprove");  
				return false;
			}
			else
			{
				$log->lwrite("notice not intact, " . strpos($existing_notices, '<a href=') . ", " . strpos($existing_notices, '</a>'));
			}
		}
	}
	return $raw_content;
}

function dprv_normaliseContent($contentString)
{
	$log = new Logging();  
	$contentString = htmlspecialchars_decode($contentString, ENT_QUOTES);  		// decode any encoded XML-incompatible characters now to ensure match with post-xml decoded string on server

	// Below is code inserted (at .70) after discovery that extra <p> and </p> tags are inserted when post is coming from WLW - maybe this is generated by wp.getPage or within WLW itself
	// Not strictly necessary, but improves chances of detecting unchanged content (which ideally should not be Digiproved)
	// TODO: improve normalisation to get around all this dickying with html that wp seems to do

	$pos = strlen($contentString) -7;
	if ($pos > 0 && substr($contentString, $pos) == "<p></p>")
	{
		$contentString = trim(substr($contentString, 0, $pos));
	}
	$pos = strlen($contentString);

	if ($pos > 7 && substr($contentString, 0, 3) == "<p>" && substr($contentString, $pos -4) == "</p>")
	{
		$contentString = trim(substr($contentString, 3, $pos-7));
	}
	// end of 0.70 inserted code
	return trim($contentString);
}


class Logging
{
	// write message to the log file  
	function lwrite($message)
	{  
		global $dprv_log_is_on;
		if ($dprv_log_is_on == true)
		{
			// if file pointer doesn't exist, then open log file  
			if (!$this->fp) $this->lopen(); 
			if (!$this->fp) return;														// if cannot open/create logfile, just return

			$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
			$posDot = strrpos($script_name,'.');
			if ($posDot != false)
			{
				$script_name = substr($script_name, 0, $posDot);
			}

			$time = date('H:i:s');  
			// write current time, script name and message to the log file  
			@fwrite($this->fp, "$time ($script_name) $message\n") or $this->logwarning('(note - could not write to log-file)');
		}
	}  

	// open log file  
	function lopen()
	{  
		// define log file path and name  
		$lfile='';
		$current_dir = getcwd();
		$pos = strpos($current_dir, "wp-");
		if ($pos != false)
		{
			$lfile = substr($current_dir, 0, $pos);
		}
		$lfile .= 'digiprove_log' . date('Y-m-d') . '.log';  

		// open log file for writing only; place the file pointer at the end of the file  
		// if the file does not exist, attempt to create it  
		$this->fp = @fopen($lfile, 'a') or  $this->logwarning('(note - could not open or create log-file ' . $lfile . ')');  
	}  

	function logwarning($dprv_warning)
	{  
		$dprv_temp = get_option('dprv_last_result');
		$pos = strpos($dprv_temp, $dprv_warning);
		if ($pos === false)
		{
			update_option('dprv_last_result', $dprv_temp . ' ' . $dprv_warning);
		}
	}  
}


function dprv_settings()		// Run when Digiprove selected from Settings menu
{		
	$log = new Logging();  
	$log->lwrite("dprv_settings starting");
	$message = "";
	$result_message="";

	if (empty($_POST['dprv_cp_action']))									// if this is not postback
	{
		$log->lwrite("dprv_settings selected");

		// Populate variables and record default values if necessary
		$user_info = get_userdata(1);
		$dprv_email_address = get_option('dprv_email_address');
		if ($dprv_email_address == false)
		{
			$dprv_email_address = $user_info->user_email;
		}
		$dprv_first_name = get_option('dprv_first_name');
		if ($dprv_first_name == false)
		{
			$dprv_first_name = $user_info->first_name;
		}
		$dprv_last_name = get_option('dprv_last_name');
		if ($dprv_last_name == false)
		{
			$dprv_last_name = $user_info->last_name;;
		}

		$dprv_display_name = get_option('dprv_display_name');
		if ($dprv_display_name == false)
		{
			$dprv_display_name = 'Yes';
		}
		
		$dprv_email_certs = get_option('dprv_email_certs');
		if ($dprv_email_certs == false)
		{
			$dprv_email_certs = 'Yes';
		}

		$dprv_content_type = get_option('dprv_content_type');
		if ($dprv_content_type == false)
		{
			$dprv_content_type = "Blog post";
		}
		$dprv_notice = get_option('dprv_notice');
		if ($dprv_notice == false)
		{
			$dprv_notice = 'Copyright secured by Digiprove';
		}
		$dprv_c_notice = get_option('dprv_c_notice');
		if ($dprv_c_notice == false)
		{
			$dprv_c_notice = 'Display';
		}
		$dprv_notice_size = get_option('dprv_notice_size');
		if ($dprv_notice_size == false)
		{
			$dprv_notice_size = 'Medium';
		}
		$dprv_frustrate_copy = get_option('dprv_frustrate_copy');
		if ($dprv_frustrate_copy == false)
		{
			$dprv_frustrate_copy = 'No';
		}

		$dprv_right_click_message = get_option('dprv_right_click_message');
		if ($dprv_right_click_message == false)
		{
			$dprv_right_click_message = '';
		}
		
		$dprv_record_IP = get_option('dprv_record_IP');
		if ($dprv_record_IP == false)
		{
			$dprv_record_IP = 'No';
		}
		$dprv_notice_border = get_option('dprv_notice_border');

		if ($dprv_notice_border == false || $dprv_notice_border == "Gray")
		{
			$dprv_notice_border = '#BBBBBB';
		}
		$dprv_notice_background = get_option('dprv_notice_background');

		if ($dprv_notice_background == false)
		{
			$dprv_notice_background = '#FFFFFF';
		}
		$dprv_notice_color = get_option('dprv_notice_color');

		if ($dprv_notice_color == false)
		{
			$dprv_notice_color = '#636363';
		}
		$dprv_hover_color = get_option('dprv_hover_color');

		if ($dprv_hover_color == false)
		{
			$dprv_hover_color = '#A35353';
		}

		$dprv_obscure_url = get_option('dprv_obscure_url');
		if ($dprv_obscure_url == false)
		{
			$dprv_obscure_url = 'Obscure';
		}

		$dprv_linkback = get_option('dprv_linkback');
		if ($dprv_linkback == false)
		{
			$dprv_linkback = 'Linkback';
		}
		$dprv_body_or_footer = get_option('dprv_body_or_footer');
		if ($dprv_body_or_footer == false)
		{
			$dprv_body_or_footer = 'Body';
		}
		$dprv_enrolled = get_option('dprv_enrolled');
		if ($dprv_enrolled == false)
		{
			$dprv_enrolled = 'No';
		}
		$dprv_user_id = get_option('dprv_user_id');
		if ($dprv_user_id == false && strlen($dprv_email_address) < 41)
		{
			$dprv_user_id = $dprv_email_address;
		}
		$dprv_api_key = get_option('dprv_api_key');
		$dprv_password = get_option('dprv_password');			// This is retained simply to know if a password is still on record (affects some help text)
		//$dprv_pw_confirm = get_option('dprv_password');
		$dprv_pw_confirm = $dprv_password;

		$dprv_last_result = get_option('dprv_last_result');
	}
	else		// Is POSTBACK, do necessary validation and take action
	{
		$log->lwrite("dprv_settings Postback");


		// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
		if(!isset($HTTP_POST_VARS) && isset($_POST))
		{
			$HTTP_POST_VARS = $_POST;
		}

		// Populate variables
		$dprv_email_address = $_POST['dprv_email_address'];
		$dprv_first_name = $_POST['dprv_first_name'];
		$dprv_last_name = $_POST['dprv_last_name'];
		$dprv_display_name = $_POST['dprv_display_name'];
		$dprv_email_certs = $_POST['dprv_email_certs'];
		$dprv_content_type = $_POST['dprv_content_type'];
		$dprv_notice = $_POST['dprv_notice'];
		$dprv_c_notice = $_POST['dprv_c_notice'];
		$dprv_notice_size = $_POST['dprv_notice_size'];
		$dprv_frustrate_copy = $_POST['dprv_frustrate_copy'];
		$dprv_right_click_message = htmlspecialchars(stripslashes($_POST['dprv_right_click_message']), ENT_QUOTES);
		$dprv_record_IP = $_POST['dprv_record_IP'];
		$dprv_notice_border = $_POST['dprv_notice_border'];
		$dprv_notice_background = $_POST['dprv_notice_background'];
		$dprv_notice_color = $_POST['dprv_notice_color'];
		$dprv_hover_color = $_POST['dprv_hover_color'];
		$dprv_obscure_url = $_POST['dprv_obscure_url'];
		$dprv_linkback = $_POST['dprv_linkback'];
		$dprv_body_or_footer = $_POST['dprv_body_or_footer'];
		$dprv_enrolled = $_POST['dprv_enrolled'];
		$dprv_register_option=$_POST['dprv_register'];
		$dprv_user_id = $_POST['dprv_user_id'];
		$dprv_api_key = $_POST['dprv_api_key'];
		$log->lwrite("dprv_api_key=".$dprv_api_key);
		$dprv_renew_api_key = $_POST['dprv_renew_api_key'];
		$log->lwrite("dprv_renew_api_key=".$dprv_renew_api_key);
		$dprv_password = $_POST['dprv_password'];
		$dprv_pw_confirm = $_POST['dprv_pw_confirm'];
		$dprv_last_result = get_option('dprv_last_result');
		$dprv_action = $_POST['dprv_action'];

		$log->lwrite("dprv_action=".$dprv_action);
		//$result_message = "";
		$message = "";
		if ($dprv_action == "ResendEmail")
		{
			$log->lwrite("about to call dprv_resend_activation_email");
			$dprv_resend_response = dprv_resend_activation_email($dprv_user_id, $dprv_email_address);
			$pos = strpos($dprv_resend_response, "<result_code>0");
			if ($pos === false)
			{
				$failure_message = dprv_getTag($dprv_resend_response,"result");
				if ($failure_message == false)
				{
					$failure_message = $dprv_resend_response;
				}
				$result_message = 'Activation email was not resent: '  . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
				$log->lwrite("Activation email re-send failed, response:");
				$log->lwrite($dprv_resend_response);
			}
			else
			{
				$result_message = dprv_getTag($dprv_resend_response,"result");
				if ($result_message == false)
				{
					$result_message = "Sorry, there was a problem in resending the activation email";
				}
			}

			update_option('dprv_last_result', $result_message);
			$message = $dprv_temp;
		}
		else
		{
			// VALIDATE

			$result_message = dprv_ValidSettings();
			if ($result_message == "")
			{
				$log->lwrite("dprv_settings starting");
				$dprv_update_user = false;
				if (isset($_POST['dprv_email_address']) && $dprv_email_address != get_option('dprv_email_address'))
				{
					update_option('dprv_email_address',$_POST['dprv_email_address']);
					$dprv_update_user = true;
				}
				if (isset($_POST['dprv_first_name']) && $dprv_first_name != get_option('dprv_first_name'))
				{
					update_option('dprv_first_name',$_POST['dprv_first_name']);
					$dprv_update_user = true;
				}
				if (isset($_POST['dprv_last_name']) && $dprv_last_name != get_option('dprv_last_name'))
				{
					update_option('dprv_last_name',$_POST['dprv_last_name']);
					$dprv_update_user = true;
				}
				if (isset($_POST['dprv_content_type']))
				{
					update_option('dprv_content_type',$_POST['dprv_content_type']);
				}
				if (isset($_POST['dprv_notice']))
				{
					update_option('dprv_notice',$_POST['dprv_notice']);
				}
				if (isset($_POST['dprv_c_notice']))
				{
					update_option('dprv_c_notice',$_POST['dprv_c_notice']);
				}
				if (isset($_POST['dprv_notice_size']))
				{
					update_option('dprv_notice_size',$_POST['dprv_notice_size']);
				}
				if (isset($_POST['dprv_frustrate_copy']))
				{
					update_option('dprv_frustrate_copy',$_POST['dprv_frustrate_copy']);
				}
				if (isset($_POST['dprv_right_click_message']))
				{
					update_option('dprv_right_click_message',htmlspecialchars(stripslashes($_POST['dprv_right_click_message']), ENT_QUOTES));
				}
				
				if (isset($_POST['dprv_record_IP']))
				{
					update_option('dprv_record_IP',$_POST['dprv_record_IP']);
				}
				if (isset($_POST['dprv_notice_border']))
				{
					update_option('dprv_notice_border', $_POST['dprv_notice_border']);
				}
				if (isset($_POST['dprv_notice_background']))
				{
					update_option('dprv_notice_background', $_POST['dprv_notice_background']);
				}
				if (isset($_POST['dprv_notice_color']))
				{
					update_option('dprv_notice_color', $_POST['dprv_notice_color']);
				}
				if (isset($_POST['dprv_hover_color']))
				{
					update_option('dprv_hover_color', $_POST['dprv_hover_color']);
				}
				if (isset($_POST['dprv_obscure_url']))
				{
					update_option('dprv_obscure_url',$_POST['dprv_obscure_url']);
				}
				if (isset($_POST['dprv_display_name']) && $dprv_display_name != get_option('dprv_display_name'))
				{
					update_option('dprv_display_name',$_POST['dprv_display_name']);
					$dprv_update_user = true;
				}
				if (isset($_POST['dprv_email_certs']) && $dprv_email_certs != get_option('dprv_email_certs'))
				{
					update_option('dprv_email_certs',$_POST['dprv_email_certs']);
					$dprv_update_user = true;
				}

				if (isset($_POST['dprv_renew_api_key']) && $_POST['dprv_renew_api_key'] == "on")
				{
					$dprv_update_user = true;
				}

				if (isset($_POST['dprv_linkback']))
				{
					update_option('dprv_linkback',$_POST['dprv_linkback']);
				}
				if (isset($_POST['dprv_body_or_footer']))
				{
					update_option('dprv_body_or_footer',$_POST['dprv_body_or_footer']);
				}
				if (isset($_POST['dprv_enrolled']))
				{
					update_option('dprv_enrolled',$_POST['dprv_enrolled']);
	//				if (trim($_POST['dprv_enrolled']) == "Yes")
	//				{
	//					print('<script type="text/javascript">if(document.getElementById("dprv_reminder")){document.getElementById("dprv_reminder").innerHTML = "";document.getElementById("dprv_reminder").style.display = "none";}</script>');
	//				}
				}
				if (isset($_POST['dprv_user_id']))
				{
					update_option('dprv_user_id',$_POST['dprv_user_id']);
				}
				if (isset($_POST['dprv_api_key']))
				{
					update_option('dprv_api_key',$_POST['dprv_api_key']);
				}

				$message = __("Digiprove Settings Updated.", 'dprv_cp');

				$dprv_register_option=$_POST['dprv_register'];
				if ($dprv_enrolled == "No" && $dprv_register_option == "Yes")
				{
					$register_response = dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs);
					$pos = strpos($register_response, "<result_code>0");
					if ($pos === false)
					{
						$failure_message = dprv_getTag($register_response,"result");
						if ($failure_message == false)
						{
							$failure_message = $register_response;
						}
						$result_message = '<font color="orangered">Registration did not complete: ' . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
						if (strpos($failure_message, "already registered") === false)
						{
							$result_message .= ', please try later';
						}
						$result_message .= '</font>';
						$log->lwrite("Registration failed, response:");
						$log->lwrite($register_response);
					}
					else
					{
						$result_message = __('Digiprove user registration was successful, check your email for the activation link', 'dprv_cp');
						$dprv_api_key = dprv_getTag($register_response, "api_key");
						update_option('dprv_api_key',$dprv_api_key);
						update_option('dprv_enrolled',"Yes");
						$dprv_subscription_type = dprv_getTag($register_response, "subscription_type");
						if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
						{
							update_option('dprv_subscription_type', $dprv_subscription_type);
						}
						$dprv_enrolled = "Yes";
						print('<script type="text/javascript">if(document.getElementById("dprv_reminder")){document.getElementById("dprv_reminder").innerHTML = "";document.getElementById("dprv_reminder").style.display = "none";}</script>');
					}
				}
				else
				{
					if ($dprv_enrolled == "Yes" && $dprv_update_user == true)
					{
						$register_response = dprv_update_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs, $dprv_renew_api_key);
						$pos = strpos($register_response, "<result_code>0");
						if ($pos === false)
						{
							$failure_message = dprv_getTag($register_response,"result");
							if ($failure_message == false)
							{
								$failure_message = $register_response;
							}
							$result_message = 'Server synchronisation did not complete: '  . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
							$log->lwrite("Update failed, response:");
							$log->lwrite($register_response);
						}
						else
						{
							$result_message = __('Server data has also been synchronised', 'dprv_cp');
							$dprv_new_api_key = dprv_getTag($register_response, "api_key");
							if ($dprv_new_api_key != null && $dprv_new_api_key != false && $dprv_new_api_key != "")
							{
								update_option('dprv_api_key',$dprv_new_api_key);
								$dprv_api_key = $dprv_new_api_key;
								delete_option('dprv_password');
							}
							$dprv_subscription_type = dprv_getTag($register_response, "subscription_type");
							if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
							{
								update_option('dprv_subscription_type', $dprv_subscription_type);
							}
							print('<script type="text/javascript">if(document.getElementById("dprv_reminder")){document.getElementById("dprv_reminder").innerHTML = "";document.getElementById("dprv_reminder").style.display = "none";}</script>');
						}
					}
					else
					{
						if($dprv_enrolled == "Yes")
						{
							print('<script type="text/javascript">if(document.getElementById("dprv_reminder")){document.getElementById("dprv_reminder").innerHTML = "";document.getElementById("dprv_reminder").style.display = "none";}</script>');
						}
					}
				}
			}
			else
			{
				$message = "<font color='orangered'>" . __("Digiprove Settings not Updated.", 'dprv_cp') . "</font>";
			}
		}
		$log->lwrite("About to display $message");
		print('
			<div id="message" class="updated fade">
				<p>' . $message . '&nbsp;&nbsp;' . $result_message .
				'</p>
			</div>
			');
//		if (strpos($result_message, "check your email for the activation link") != false)
//		{
//			print('<script type="text/javascript">if(document.getElementById("dprv_reminder")){document.getElementById("dprv_reminder").innerHTML = "";document.getElementById("dprv_reminder").style.display = "none";}</script>');
//		}
	}

	$log->lwrite("dprv_settings about to display");
	
	// Prepare HTML to represent DB values for drop-down and radio buttons

	$dprv_c_selected = ' selected="selected"';
	$dprv_c_all_selected = '';
	$dprv_no_c_selected = '';
	if ($dprv_c_notice == 'NoDisplay')
	{
		$dprv_c_selected = '';
		$dprv_no_c_selected = ' selected="selected"';
	}
	if ($dprv_c_notice == 'DisplayAll')
	{
		$dprv_c_selected = '';
		$dprv_c_all_selected = ' selected="selected"';
	}

	$dprv_notice_medium_checked = ' checked="checked"';
	$dprv_notice_small_checked = '';
	$dprv_notice_smaller_checked = '';
	if ($dprv_notice_size == 'Small')
	{
		$dprv_notice_medium_checked = '';
		$dprv_notice_small_checked = ' checked="checked"';
	}
	if ($dprv_notice_size == 'Smaller')
	{
		$dprv_notice_medium_checked = '';
		$dprv_notice_smaller_checked = ' checked="checked"';
	}

	$dprv_frustrate_yes_checked = '';
	$dprv_frustrate_no_checked = ' checked="checked"';
	if ($dprv_frustrate_copy == 'Yes')
	{
		$dprv_frustrate_no_checked = '';
		$dprv_frustrate_yes_checked = ' checked="checked"';
	}
	$right_click_message_styletext = ' style="width:400px;"';
	$right_click_checktext = ' checked="checked"';
	if ($dprv_right_click_message == '')
	{
		$right_click_checktext = '';
		$right_click_message_styletext = ' disabled="disabled" style="width:400px; background-color:#CCCCCC"';
	}

	$dprv_record_IP_yes_checked = '';
	$dprv_record_IP_no_checked = ' checked="checked"';
	if ($dprv_record_IP == 'Yes')
	{
		$dprv_record_IP_no_checked = '';
		$dprv_record_IP_yes_checked = ' checked="checked"';
	}

	$no_background_checktext = '';
	if ($dprv_notice_background == "None")
	{
		$no_background_checktext = 'checked="checked"';
	}

	$no_border_checktext = '';
	if ($dprv_notice_border == "None")
	{
		$no_border_checktext = 'checked="checked"';
	}

	$dprv_obscure_selected = ' selected="selected"';
	$dprv_clear_selected = '';
	if ($dprv_obscure_url != 'Obscure')
	{
		$dprv_obscure_selected = '';
		$dprv_clear_selected = ' selected="selected"';
	}

	$dprv_display_name_selected = ' selected="selected"';
	$dprv_no_display_name_selected = '';
	if ($dprv_display_name != 'Yes')
	{
		$dprv_display_name_selected = '';
		$dprv_no_display_name_selected = ' selected="selected"';
	}

	$dprv_email_certs_selected = ' selected="selected"';
	$dprv_no_email_certs_selected = '';
	if ($dprv_email_certs != 'Yes')
	{
		$dprv_email_certs_selected = '';
		$dprv_no_email_certs_selected = ' selected="selected"';
	}

	$dprv_linkback_selected = ' selected="selected"';
	$dprv_no_linkback_selected = '';
	if ($dprv_linkback != 'Linkback')
	{
		$dprv_linkback_selected = '';
		$dprv_no_linkback_selected = ' selected="selected"';
	}

	$dprv_body_selected = ' selected="selected"';
	$dprv_footer_selected = '';
	if ($dprv_body_or_footer != 'Body')
	{
		$dprv_body_selected = '';
		$dprv_footer_selected = ' selected="selected"';
	}
	
	$dprv_not_enrolled_selected = ' selected="selected"';
	$dprv_enrolled_selected = '';
	$dprv_display_register_row = '';
	if ($dprv_enrolled == 'Yes')
	{
		$dprv_not_enrolled_selected = '';
		$dprv_enrolled_selected = ' selected="selected"';
		$dprv_display_register_row = 'style="display:none"';
	}
	
		$dprv_register_now_checked = ' checked="checked"';
		$dprv_register_later_checked = '';
	
	if ($dprv_register_option == "Yes")
	{
		$dprv_register_now_checked = ' checked="checked"';
	}
	if ($dprv_register_option == "No")
	{
		$dprv_register_later_checked = ' checked="checked"';
	}

	// changes at 0.78
	//$dprv_site_url = parse_url(get_option('siteurl'));
	//$dprv_site_host = $dprv_site_url[host];
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url[host];
	// end of 0.78 changes

	$dprv_password_on_record = "No";

	if ($dprv_password != null && $dprv_password != "")
	{
		$dprv_password_on_record = "Yes";
	}
	print('
			<div class="wrap" style="padding-top:4px">
				<h2 style="vertical-align:8px;"><a href="http://www.digiprove.com"><img src="http://www.digiprove.com/images/digiprove_logo_278x69.png" alt="Digiprove"/></a><span style="vertical-align:22px; padding-left:40px">'.__('Copyright Proof Settings', 'dprv_cp').'</span></h2>  
				<form id="dprv_cp" name="dprv_AnyOldThing" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=CopyrightProof.php" method="post" onsubmit="return SubmitSelected();">
					<input type="hidden" name="dprv_cp_action" value="dprv_cp_update_settings" />
						<fieldset class="options">
							<div class="option">
								<table cellpadding="0" cellspacing="0" border="0">
									<tbody>
										<tr>
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="width:100%">
													<tr>
														<td id="BasicTab" style="height:30px; width:90px; border:1px solid #666666; -moz-border-radius-topleft: 5px; -webkit-border-top-left-radius: 5px; -moz-border-radius-topright: 5px; -webkit-border-top-right-radius: 5px; border-bottom:0px; background-color:#EEFFEE; cursor:pointer" align="center" onclick="DisplayBasic()"><em>Basic</em></td>
														<td id="AdvancedTab" style="height:30px; width:90px; border:1px solid #666666; -moz-border-radius-topleft: 5px; -webkit-border-top-left-radius: 5px; -moz-border-radius-topright: 5px; -webkit-border-top-right-radius: 5px; background-color:#EEEEFF; cursor:pointer" align="center" onclick="DisplayAdvanced()"><em>Advanced</em></td>
														<td id="CopyProtectTab" style="height:30px; width:90px; border:1px solid #666666; -moz-border-radius-topleft: 5px; -webkit-border-top-left-radius: 5px; -moz-border-radius-topright: 5px; -webkit-border-top-right-radius: 5px; background-color:#FFEEEE; cursor:pointer" align="center" onclick="DisplayCopyProtect()"><em>Copy Protect</em></td>
														<td style="border:1px solid #666666; border-top:0px; border-left:0px; border-right:0px"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart1">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEFFEE; border:1px solid #666666; border-top:0px; border-bottom:0px; width:100%">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr><td><b>' . __('Personal details and preferences', 'dprv_cp').'</b></td>
														<td></td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowPersonalDetailsText()">' .__('How these details are used', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Email address', 'dprv_cp') . '</td>
														<td style="width:320px"><input name="dprv_email_address" id="dprv_email_address" type="text" value="'.htmlspecialchars(stripslashes($dprv_email_address)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('First name: ', 'dprv_cp') . '</td>
														<td><input name="dprv_first_name" id="dprv_first_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_first_name)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Last name: ', 'dprv_cp') . '</td>
														<td><input name="dprv_last_name" id="dprv_last_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_last_name)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Display user name: ', 'dprv_cp') . '</td>
														<td><select name="dprv_display_name" style="width:300px" onchange="DisplayNameChanged(this);">
																<option value="Yes"' . $dprv_display_name_selected . '>' . __('Yes, display my name', 'dprv_cp') . '</option>
																<option value="No"' . $dprv_no_display_name_selected . '>' . __('No, keep my name private', 'dprv_cp') . '</option>
															</select></td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowPrivacyText()">' .__('Note on privacy', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Email my certificates to me: ', 'dprv_cp') . '</td>
														<td><select name="dprv_email_certs" style="width:300px">
																<option value="Yes"' . $dprv_email_certs_selected . '>' . __('Yes, send my Digiprove certs by email', 'dprv_cp') . '</option>
																<option value="No"' . $dprv_no_email_certs_selected . '>' . __('No, don\'t bother me with emails', 'dprv_cp') . '</option>
															</select></td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowEmailCertText()">' .__('Note on certificates', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart2">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFEEEE; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr>
														<td colspan="2" style="font-weight:bold">' . __('Digiprove registration details', 'dprv_cp').'</td>
													</tr>
													<tr><td style="height:12px"></td></tr>
													<tr>
														<td>' . __('Registered Digiprove user?: ', 'dprv_cp') . '</td>
														<td style="width:320px"><select name="dprv_enrolled" id="dprv_enrolled" onchange="toggleCredentials()" style="width:290px">
																<option value="Yes" ' . $dprv_enrolled_selected . '>I am already registered with Digiprove</option>
																<option value="No" ' . $dprv_not_enrolled_selected . '>I have not yet registered with Digiprove</option>
															</select>
														</td>
														<td class="submit" style="padding-top:0px; padding-bottom:0px">&nbsp;<input type="button" id="dprv_resend_activation" name="dprv_resend_activation" onclick="ResendEmail();" value="Re-send activation email" /></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="dprv_register_row" ' . $dprv_display_register_row . '>
														<td>' . __('Do you want to register now?: ', 'dprv_cp') . '</td>
														<td><input type="radio" name="dprv_register" id="dprv_register_yes" onclick="toggleCredentials()" value="Yes" ' . $dprv_register_now_checked . '/>Yes, register me now&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_register" onclick="toggleCredentials()" value="No" ' . $dprv_register_later_checked . '/>No, do it later</td>
															<td style="padding-left:10px" class="description" ><a href="javascript:ShowRegistrationText()">' .__('What&#39;s this about?', 'dprv_cp') . '</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:ShowTermsOfUseText()">' .__('Terms of use.', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="dprv_user_id_row1">
														<td style="vertical-align:top"><label for="dprv_user_id" id="dprv_user_id_labelA">'.__('Digiprove User Id: ', 'dprv_cp').'</label><label for="dprv_user_id" id="dprv_user_id_labelB" style="display:none">'.__('Desired Digiprove User Id: ', 'dprv_cp').'</label></td>
														<td><input name="dprv_user_id" id="dprv_user_id" type="text" value="'.htmlspecialchars(stripslashes($dprv_user_id)).'" onblur="javascript:ScheduleRestorePassword()" style="width:290px"/></td>
														<td class="description" id="dprv_email_warning"></td>
													</tr>
													<tr id="dprv_user_id_row2"><td style="height:6px"></td></tr>
													<tr id="dprv_api_key_row">
														<td style="vertical-align:top"><label for="dprv_api_key" id="dprv_api_key_label" title="Digiprove API key for $dprv_blog_host">' . __(' Digiprove API Key: ', 'dprv_cp').'</label></td>
														<td><input name="dprv_api_key" id="dprv_api_key" type="text" value="'.htmlspecialchars(stripslashes($dprv_api_key)).'" onchange="ApiKeyChange();" style="width:190px"/>&nbsp;<input type="checkbox" id="dprv_renew_api_key" name="dprv_renew_api_key" onclick="renewApiKey(this)">Renew Api key</td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowAPIText(\'' . $dprv_blog_host. '\',\'' . $dprv_password_on_record . '\')">' .__('What&#39;s this?', 'dprv_cp') . '</a></td>
													</tr>
													<tr id="dprv_password_row1">
														<td><label for="dprv_password" id="dprv_password_label">'.__('Select a password: ', 'dprv_cp').'</label></td>
														<td><input name="dprv_password" id="dprv_password" type="password" value="'.htmlspecialchars(stripslashes($dprv_password)).'" onchange="javascript:SavePassword()" style="width:290px" /></td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowPasswordText()">' .__('Security note', 'dprv_cp') . '</a></td>
													</tr>
													<tr id="dprv_password_row2"><td style="height:6px"></td></tr>
													<tr id="dprv_password_row3">
														<td></td>
														<td><input name="dprv_pw_confirm" type="password" value="'.htmlspecialchars(stripslashes($dprv_pw_confirm)).'" style="width:290px" /></td>
														<td class="description">'.__('type the password again.', 'dprv_cp').'</td>
													</tr>
													<tr><td style="height:6px"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="AdvancedPart1" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEEEFF; border:1px solid #666666; border-top:0px; border-bottom:0px; width:100%">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr><td colspan="2"><b>' . __('The Digiprove notice', 'dprv_cp') . '</b>' . __(' (at foot of each post)', 'dprv_cp') . '</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' .__('Preview: ', 'dprv_cp') . '</td>
														<td id="dprv_notice_preview"></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Digiprove Notice Text: ', 'dprv_cp') . '</td>
														<td><select name="dprv_notice" id="dprv_notice" onchange="Preview()" style="width:290px">'
																	. dprv_options_html(array("This content has been Digiproved",
																							"This article has been Digiproved",
																							"This blog post has been Digiproved",
																							"Copyright protected by Digiprove",
																							"Copyright secured by Digiprove"),
																							"This " . strtolower(htmlentities(stripslashes($dprv_content_type))) . " has been Digiproved", $dprv_notice) .
													'</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Include a copyright notice: ', 'dprv_cp') . '</td>
														<td><select name="dprv_c_notice" id="dprv_c_notice" onchange="Preview()" style="width:290px">
																<option value="DisplayAll"' . $dprv_c_all_selected . '>Display</option>
																<option value="Display"' . $dprv_c_selected . '>Display but leave out my name</option>
																<option value="NoDisplay"' . $dprv_no_c_selected . '>Do not display</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('How big should it be: ', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_notice_size" id="dprv_notice_medium" value="Medium" ' . $dprv_notice_medium_checked . ' onclick="Preview()"/>Medium&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_notice_size" id="dprv_notice_small" value="Small" ' . $dprv_notice_small_checked . ' onclick="Preview()"/>Small&nbsp;&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_notice_size" id="dprv_notice_smaller" value="Smaller" ' . $dprv_notice_smaller_checked . ' onclick="Preview()"/>Smaller
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>'.__('Select colors: ', 'dprv_cp').'</td>
														<td colspan="2">
															<table cellpadding="0" cellspacing="0" border="0">
																<tr>
																	<th align="left">Text</th>
																	<th align="left">Mouse-over</th>
																	<th align="left">Background</th>
																	<th align="left">Border</th>
																</tr>
																<tr>
																	<td width="120" align="left"><input style="width:70px;" value="' . $dprv_notice_color . '" id="dprv_notice_color" name="dprv_notice_color" onchange="Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;" value="' . $dprv_hover_color . '" id="dprv_hover_color" name="dprv_hover_color" onchange="Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;"  value="' . $dprv_notice_background . '" id="dprv_notice_background" name="dprv_notice_background" onchange="setCheckboxes();Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;background-color:' . $dprv_notice_border . '"  value="' . $dprv_notice_border . '" id="dprv_notice_border" name="dprv_notice_border" onchange="setCheckboxes();Preview()" /></td>
																</tr>
																<tr>
																	<td colspan="2"></td>
																	<td  align="left" style="font-size:11px"><input type="checkbox" id="dprv_no_background" name="dprv_no_background" ' . $no_background_checktext . ' onclick="noBackgroundChanged(this);" />Transparent</td>
																	<td  align="left" style="font-size:11px"><input type="checkbox" id="dprv_no_border" name="dprv_no_border" ' . $no_border_checktext . ' onclick="noBorderChanged(this);" />No Border</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr style="display:none"><td style="height:6px"></td></tr>
													<tr style="display:none">
														<td>' . __('Place Digiprove notice in body or footer:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td><select name="dprv_body_or_footer" style="width:290px">
																<option value="Body"' . $dprv_body_selected . '>Insert at end of blog post</option>
																<option value="Footer"' . $dprv_footer_selected . '>Display in footer*</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="AdvancedPart2" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#CCCCCC; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr><td colspan="2"><b>' . __('The certificate (on Digiprove web-page)', 'dprv_cp').'</b></td></tr>
													<tr><td style="height:6px"></td></tr>
									
													<tr>
														<td>' . __('How your content should be described: ', 'dprv_cp') . '</td>
														<td><input name="dprv_content_type" type="text" value="'.htmlspecialchars(stripslashes($dprv_content_type)).'" style="width:290px" onchange="Preview()" /><span class="description">e.g. &quot;Blog post&quot;, &quot;News article&quot;, &quot;Opinion&quot;</span></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Obscure Digiprove certificate link: ', 'dprv_cp') . '</td>
														<td><select name="dprv_obscure_url" style="width:440px">
																<option value="Obscure"' . $dprv_obscure_selected . '>' . __('Obscure the link (for privacy)', 'dprv_cp') . '</option>
																<option value="Clear"' . $dprv_clear_selected . '>' . __('Do not obscure the link (for search engine optimisation)', 'dprv_cp') . '</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Certificate page to link back to post?: ', 'dprv_cp') . '</td>
														<td><select name="dprv_linkback" style="width:440px">
																<option value="Linkback"' . $dprv_linkback_selected . '>Digiprove certificate web-page should have a link to relevant blog post</option>
																<option value="Nolink"' . $dprv_no_linkback_selected . '>Do not link back to my blog posts</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										
										
										<tr id="CopyProtect" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFEEEE; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr>
														<td>' . __('Frustrate content copying attempts:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_frustrate_copy" id="dprv_frustrate_yes" value="Yes" ' . $dprv_frustrate_yes_checked . ' onclick="toggle_r_c_checkbox()" />Prevent right-click &amp; select&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_frustrate_copy" id="dprv_frustrate_no" value="No" ' . $dprv_frustrate_no_checked . ' onclick="toggle_r_c_checkbox()" />Allow right-click &amp; select&nbsp;&nbsp;&nbsp;&nbsp;
														</td>
														<td style="padding-left:10px" class="description" ><a href="javascript:ShowFrustrateCopyText()">' .__('Note on content protection', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Display warning note on right-click? :&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td colspan="2">
															<input type="checkbox" ' . $right_click_checktext . ' id="dprv_right_click_box" onclick="toggle_r_c_text(this);" />
															&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
															<input type="text" id="dprv_right_click_message" name="dprv_right_click_message" ' . $right_click_message_styletext . ' value="' . $dprv_right_click_message . '"/>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr style="display:none">
														<td>' . __('Record IP Address of right-clickers:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_record_IP" id="dprv_record_IP_yes" value="Yes" ' . $dprv_record_IP_yes_checked . ' />Record IP Address&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_record_IP" id="dprv_record_IP_no" value="No" ' . $dprv_record_IP_no_checked . ' />Don\'t bother&nbsp;&nbsp;&nbsp;&nbsp;
														</td>
													</tr>
												</table>
											</td>
										</tr>
										');
	if ($dprv_last_result != '' && strpos($dprv_last_result, "Configure Copyright Proof") === false)
	{
		print ('
										<tr id="BasicPart3">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; background-color:#DDDDE4; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px;width:280px"></td></tr>
													<tr>
														<td>' . __('Result of last Digiprove action:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td>' . $dprv_last_result . '</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>');
	}										
										
	print ('										
									</tbody>
								</table>
							</div>
						</fieldset>
					<div style="width:840px; border: 0px; padding-top:8px">
						<div class="submit" style="float:left;width:125px;padding-top:8px;padding-bottom:8px"><input type="submit" name="dprv_submit" id="dprv_submit" value="'.__('Update Settings', 'dprv_cp').'" /></div><div><input type="hidden" id="dprv_action" name="dprv_action" value="Update" /></div>
						<div id="HelpTextContainer" style="display: none; width:700px; float:right; border: 1px solid rgb(51, 51, 51);  background-color:#FFFFFF; padding:3px"><span id="HelpText" style="border: 0px none ;"></span>
							<br style="line-height: 4px;"/><a href="javascript:HideHelpText()" style="float:right">Close this window</a>
						</div>
					</div>
				</form>' );
	print ('
			<script type="text/javascript">
			<!--
			function DisplayBasic()
			{
				document.getElementById("BasicTab").style.borderBottom="0px";
				document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
				document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
				document.getElementById("BasicPart1").style.display="";
				document.getElementById("BasicPart2").style.display="";
				if (document.getElementById("BasicPart3") != null)
				{
					document.getElementById("BasicPart3").style.display="";
				}
				document.getElementById("AdvancedPart1").style.display="none";
				document.getElementById("AdvancedPart2").style.display="none";
				document.getElementById("CopyProtect").style.display="none";
			}
			function DisplayAdvanced()
			{
				document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
				document.getElementById("AdvancedTab").style.borderBottom="0px";
				document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
				document.getElementById("BasicPart1").style.display="none";
				document.getElementById("BasicPart2").style.display="none";
				if (document.getElementById("BasicPart3") != null)
				{
					document.getElementById("BasicPart3").style.display="none";
				}	
				document.getElementById("AdvancedPart1").style.display="";
				document.getElementById("AdvancedPart2").style.display="";
				document.getElementById("CopyProtect").style.display="none";
			}

			function DisplayCopyProtect()
			{
				document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
				document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
				document.getElementById("CopyProtectTab").style.borderBottom="0px";
				document.getElementById("BasicPart1").style.display="none";
				document.getElementById("BasicPart2").style.display="none";
				if (document.getElementById("BasicPart3") != null)
				{
					document.getElementById("BasicPart3").style.display="none";
				}	
				document.getElementById("AdvancedPart1").style.display="none";
				document.getElementById("AdvancedPart2").style.display="none";
				document.getElementById("CopyProtect").style.display="";
			}

			// Stuff required to deal with annoying FF3.5 bug
			var SavedPassword = document.getElementById("dprv_password").value;
			function SavePassword()
			{
				SavedPassword = document.getElementById("dprv_password").value;
			}

			function ScheduleRestorePassword()
			{
				setTimeout("RestorePassword()",100);
			}

			function RestorePassword()
			{
				if (navigator.userAgent.indexOf("Firefox/3.5") > -1)
				{
					document.getElementById("dprv_password").value = SavedPassword;
				}
			}
			// End of Stuff

			function DisplayNameChanged(element)
			{
				if (element.value == "No" && document.getElementById("dprv_c_notice").value == "DisplayAll")
				{
					document.getElementById("dprv_c_notice").value = "Display";
					Preview();
				}
			}

			var myPickerText = new jscolor.color(document.getElementById("dprv_notice_color"), {hash:true,pickerPosition:\'left\'})
			myPickerText.fromString("' . $dprv_notice_color . '")  // now you can access API via myPicker variable
			var myPickerHover = new jscolor.color(document.getElementById("dprv_hover_color"), {hash:true,pickerPosition:\'left\'})
			myPickerHover.fromString("' . $dprv_hover_color . '")
			var myPickerBackground = new jscolor.color(document.getElementById("dprv_notice_background"), {hash:true,adjust:false,pickerPosition:\'left\'})
			myPickerBackground.fromString("' . $dprv_notice_background . '")
			var myPickerBorder = new jscolor.color(document.getElementById("dprv_notice_border"), {hash:true,adjust:false,pickerPosition:\'left\'})
			myPickerBorder.fromString("' . $dprv_notice_border . '")
	
			Preview();
			var lastBackgroundColor="";
			var lastBackgroundTextColor="";			
			var lastBorderColor="";
			var lastBorderTextColor="";

			function noBackgroundChanged(element)
			{
				if(element.checked==true)
				{
					lastBackgroundColor=document.getElementById(\'dprv_notice_background\').value;
					lastBackgroundTextColor=document.getElementById(\'dprv_notice_background\').style.color;
					document.getElementById(\'dprv_notice_background\').value=\'None\';
					document.getElementById(\'dprv_notice_background\').style.backgroundColor=\'\';
					document.getElementById(\'dprv_notice_background\').style.color=\'\';
				}
				else
				{
					if (lastBackgroundColor == "")
					{
						lastBackgroundColor = "#FFFFFF";
						lastBackgroundTextColor = "#000000";
					}
					document.getElementById(\'dprv_notice_background\').value=lastBackgroundColor;
					document.getElementById(\'dprv_notice_background\').style.backgroundColor=lastBackgroundColor;
					document.getElementById(\'dprv_notice_background\').style.color=lastBackgroundTextColor;
				}
				Preview();
			}

			function noBorderChanged(element)
			{
				if(element.checked==true)
				{
					lastBorderColor=document.getElementById(\'dprv_notice_border\').value;
					lastBorderTextColor=document.getElementById(\'dprv_notice_border\').style.color;
					document.getElementById(\'dprv_notice_border\').value=\'None\';
					document.getElementById(\'dprv_notice_border\').style.backgroundColor=\'\';
					document.getElementById(\'dprv_notice_border\').style.color=\'\';
				}
				else
				{
					if (lastBorderColor == "")
					{
						lastBorderColor = "#BBBBBB";
						lastBorderTextColor = "#000000";
					}
					document.getElementById(\'dprv_notice_border\').value=lastBorderColor;
					document.getElementById(\'dprv_notice_border\').style.backgroundColor=lastBorderColor;
					document.getElementById(\'dprv_notice_border\').style.color=lastBorderTextColor;
				}
				Preview();
			}


			function setCheckboxes()
			{
				if (document.getElementById("dprv_notice_background").value != "None")
				{
					document.getElementById(\'dprv_no_background\').checked = false;
				}
				if (document.getElementById("dprv_notice_border").value != "None")
				{
					document.getElementById(\'dprv_no_border\').checked = false;
				}
			}

			function ResendEmail()
			{
				document.getElementById("dprv_action").value = "ResendEmail";
				document.getElementById("dprv_cp").submit();
			}

			function Preview()
			{
				var notice_text = document.getElementById("dprv_notice").value;
				var c_notice = document.getElementById("dprv_c_notice").value;
				var notice_font_size = "11px";
				var image_scale = "";
				if (document.getElementById("dprv_notice_small").checked == true)
				{
					notice_font_size="10px";
				}
				if (document.getElementById("dprv_notice_smaller").checked == true)
				{
					notice_font_size="9px";
					image_scale=" width=\"12px\" height=\"12px\"";
				}
				var notice_color = document.getElementById("dprv_notice_color").value;
				var hover_color = document.getElementById("dprv_hover_color").value;
				var background_color = document.getElementById("dprv_notice_background").value;
				var border_color = document.getElementById("dprv_notice_border").value;
				var now = new Date();
				var DigiproveNotice = "<span style=\"vertical-align:middle; display:inline-table; height:auto; float:none; padding:3px; line-height:normal;";
				if (background_color != "None")
				{
					DigiproveNotice += "background-color:" + background_color + ";";
				}
				if (border_color == "None")
				{
					DigiproveNotice += "border:0px\"";
				}
				else
				{
					DigiproveNotice += "border:1px solid " + border_color + "\"";
				}
				DigiproveNotice += "><img src=\"http://www.digiprove.com/images/dp_seal_trans_16x16.png\" style=\"vertical-align:middle; display:inline; border:0px; margin:0px; float:none; background-color:transparent\" border=\"0\"" + image_scale + "/>";
				DigiproveNotice += "<span  style=\"font-family: Tahoma, MS Sans Serif; font-size:" + notice_font_size;
				DigiproveNotice += ";color:" + notice_color + "; border:0px; float:none; text-decoration: none; letter-spacing:normal\" onmouseover=\"this.style.color=\'" + hover_color + "\'\" onmouseout=\"this.style.color=\'" + notice_color + "\'\" >&nbsp;&nbsp;" + notice_text;
				if (c_notice != "NoDisplay")
				{
					
					var year = now.getFullYear();
					var cName = document.getElementById("dprv_first_name").value + " " + document.getElementById("dprv_last_name").value;
					DigiproveNotice += "&nbsp;&copy; " + year;
					if (c_notice == "DisplayAll")
					{
						DigiproveNotice += " " + cName.replace(/^\s\s*/, "").replace(/\s\s*$/, "");
					}
				}
				DigiproveNotice += "</span></span>";
				document.getElementById("dprv_notice_preview").innerHTML = DigiproveNotice;
			}
			function toggle_r_c_checkbox()
			{
				if (document.getElementById(\'dprv_frustrate_no\').checked == false)
				{
					document.getElementById(\'dprv_right_click_box\').disabled = false;
					document.getElementById(\'dprv_right_click_box\').style.backgroundColor = \'#FFFFFF\';
					if (document.getElementById(\'dprv_right_click_box\').checked == true)
					{
						document.getElementById(\'dprv_right_click_message\').disabled = false;
						document.getElementById(\'dprv_right_click_message\').style.backgroundColor = \'#FFFFFF\';
					}
					else
					{
						document.getElementById(\'dprv_right_click_message\').disabled = true;
						document.getElementById(\'dprv_right_click_message\').style.backgroundColor = \'#CCCCCC\';
					}
				}
				else
				{
					document.getElementById(\'dprv_right_click_box\').disabled = true;
					document.getElementById(\'dprv_right_click_box\').style.backgroundColor = \'#CCCCCC\';
					document.getElementById(\'dprv_right_click_message\').disabled = true;
					document.getElementById(\'dprv_right_click_message\').style.backgroundColor = \'#CCCCCC\';
				}

			}
			toggle_r_c_checkbox();
			function toggle_r_c_text(element)
			{
				if (element.checked == true)
				{
					document.getElementById(\'dprv_right_click_message\').disabled = false;
					document.getElementById(\'dprv_right_click_message\').style.backgroundColor = \'#FFFFFF\';
					if (document.getElementById(\'dprv_right_click_message\').value == "")
					{
						document.getElementById(\'dprv_right_click_message\').value = "Sorry, right-clicking is disabled; please respect copyrights";
					}
				}
				else
				{
					document.getElementById(\'dprv_right_click_message\').value = "";
					document.getElementById(\'dprv_right_click_message\').disabled = true;
					document.getElementById(\'dprv_right_click_message\').style.backgroundColor = \'#CCCCCC\';
				}
			}
			toggleCredentials();
			function toggleCredentials()
			{
				if (document.getElementById("dprv_enrolled").value == "Yes")
				{
					document.getElementById("dprv_resend_activation").style.display="";
					document.getElementById("dprv_register_row").style.display="none";
					document.getElementById("dprv_user_id_row1").style.display="";
					document.getElementById("dprv_user_id_row2").style.display="";
					//document.getElementById("dprv_user_id").disabled=false;
					document.getElementById("dprv_user_id_labelA").style.display="";
					document.getElementById("dprv_user_id_labelB").style.display="none";
					document.getElementById("dprv_api_key_row").style.display="";
					document.getElementById("dprv_password_row1").style.display="none";
					document.getElementById("dprv_password_row2").style.display="none";
					document.getElementById("dprv_password_row3").style.display="none";
					document.getElementById("dprv_submit").value = "' . __('Update Settings') . '";
				}
				else
				{
					document.getElementById("dprv_resend_activation").style.display="none";
					document.getElementById("dprv_register_row").style.display="";
					document.getElementById("dprv_api_key_row").style.display="none";
					document.getElementById("dprv_user_id_labelA").style.display="none";
					document.getElementById("dprv_user_id_labelB").style.display="";

					if (document.getElementById("dprv_register_yes").checked == true)
					{
						//document.getElementById("dprv_user_id").disabled=false;
						document.getElementById("dprv_user_id_row1").style.display="";
						document.getElementById("dprv_user_id_row2").style.display="";
						document.getElementById("dprv_password_row1").style.display="";
						document.getElementById("dprv_password_row2").style.display="";
						document.getElementById("dprv_password_row3").style.display="";
						document.getElementById("dprv_submit").value = "' . __('Update & Register') . '";
					}
					else
					{
						//document.getElementById("dprv_user_id").disabled=true;
						document.getElementById("dprv_user_id_row1").style.display="none";
						document.getElementById("dprv_user_id_row2").style.display="none";
						document.getElementById("dprv_password_row1").style.display="none";
						document.getElementById("dprv_password_row2").style.display="none";
						document.getElementById("dprv_password_row3").style.display="none";
						document.getElementById("dprv_submit").value = "' . __('Update Settings') . '";
					}
				}
			}

			var lastApiKey = document.getElementById(\'dprv_api_key\').value;
			function ApiKeyChange()
			{
				if (lastApiKey == document.getElementById(\'dprv_api_key\').value)
				{
					return true;
				}
				if (lastApiKey == "")
				{
					if (confirm("You have entered a value for API Key - do this only if the new API key was obtained from Digiprove"))
					{
						lastApiKey = document.getElementById(\'dprv_api_key\').value;
						return true;
					}
					else
					{
						document.getElementById(\'dprv_api_key\').value = lastApiKey;
						return false;
					}
				}
				else
				{
					if (confirm("You are changing the API Key - do this only if the new API key was obtained from Digiprove, otherwise the plugin will stop working. Press OK to proceed with change or Cancel to restore original value"))
					{
						lastApiKey = document.getElementById(\'dprv_api_key\').value;
						return true;
					}
					else
					{
						document.getElementById(\'dprv_api_key\').value = lastApiKey;
						return false;
					}
				}
			}
			function renewApiKey(element)
			{
				if (element.checked == true)
				{
					document.getElementById("dprv_password_label").innerHTML = "' . __("Enter Password:") . '";
					document.getElementById("dprv_password_row1").style.display="";
					document.getElementById("dprv_password_row2").style.display="";
				}
				else
				{
					document.getElementById("dprv_password_label").innerHTML = "' . __("Select a Password:") . '";
					document.getElementById("dprv_password_row1").style.display="none";
					document.getElementById("dprv_password_row2").style.display="none";
				}

			}

			function SubmitSelected()
			{
				if (document.getElementById("message") != null)
				{
					document.getElementById("message").innerHTML = "<p>Processing...</p>";
				}
				if (document.getElementById("dprv_enrolled").value == "No" && document.getElementById("dprv_register_yes").checked == true)
				{
					if (!confirm("Do you want to proceed with registration at www.digiprove.com?  You will receive an email with an activation link. If you do not want to do this now, press Cancel"))
					{
						document.getElementById("dprv_register_yes").checked = false;
						toggleCredentials();
						return false;
					}
				}
				return ApiKeyChange();
			}
			function ShowPersonalDetailsText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Copyright Proof uses the Digiprove service (<a href="http://www.digiprove.com/creative-and-copyright.aspx" target="_blank">www.digiprove.com</a>) to certify the content and timestamp of your Wordpress posts. Digiprove needs the name of the person claiming copyright and a valid email address to which the digitally-signed content certificates will be sent. The personal details on this panel will be used only in automatically creating your account at Digiprove.  The server records of this information are accessible at <a href="https://www.digiprove.com/members/preferences.aspx" target="_blank">https://www.digiprove.com/members/preferences.aspx</a>.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			function ShowPrivacyText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'The Copyright Proof notice appearing at the foot of your blog posts contains a link to a web-page showing details of the Digiprove certificate of your content. If you do not want your name to appear on this page select the &#39;Keep private&#39; option. Your email address and Digiprove User id are never revealed. Click <a href="http://www.digiprove.com/privacypolicy.aspx" target="_blank">here</a> to read the full privacy statement.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			
			function ShowEmailCertText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Every post you publish or update will cause a cryptographically encoded certificate to be created which is your proof that your content was published by you at that exact time and date. By default, we send every one of these by email to you, for you to keep securely. If you would prefer not to receive these emails, select &#39;No&#39; here.  You will still be able to download these certificates from the Digiprove site if required. You will need a current subscription to do this.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			
			function ShowFrustrateCopyText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Selecting this option will prevent a user from right-clicking on your web-page (in order to view the source) or selecting content (in order to copy to clipboard) in most browsers.  The settings you choose will apply to all posts published or updated from then on.  This may prevent the unauthorised use of your content by unsophisticated users, but will be a small nuisance to a determined content thief. This is as good as it gets on the web - DO NOT BELIEVE the claims of other plugin authors that your content cannot be stolen...\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}

			
			function HideHelpText()
			{
				document.getElementById(\'HelpTextContainer\').style.display=\'none\';
			}
			function ShowAPIText(domain_name, password_on_record)
			{
				document.getElementById(\'HelpText\').innerHTML = \'This is a key that permits ' . $dprv_blog_host . ' to access the Digiprove service.\';
				if (document.getElementById(\'dprv_api_key\').value == "")
				{
					if (password_on_record == \'Yes\')
					{
						document.getElementById(\'HelpText\').innerHTML += \' If you have already registered, you do not need to do anything, it will be filled in automatically when required. You can also obtain an API key <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">from the Digiprove website members&#39; area</a> (you will be asked to log in)\';
					}
					else
					{
						document.getElementById(\'HelpText\').innerHTML += \' If you have already registered via the Digiprove site (or intend to), you can obtain your API key (or get a new one) <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">from the Digiprove website members&#39; area</a> (you will be asked to log in)\';
					}
				}
				else
				{
					document.getElementById(\'HelpText\').innerHTML += \' If you registered from this page this field will have been filled in automatically - there is no need to change it. You can however obtain a new API key <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">from the Digiprove website members&#39; area</a> (you will be asked to log in)\';
				}
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			function ShowPasswordText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Your password to give you access to the Digiprove website members&#39; area. An encrypted version of the password is stored on the Digiprove server but <em>not here on your blog server</em>.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			function ShowRegistrationText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Copyright Proof uses the Digiprove service (<a href="http://www.digiprove.com/creative-and-copyright.aspx" target="_blank">www.digiprove.com</a>) to certify the content and timestamp of your Wordpress posts. You need to register with Digiprove before Copyright Proof will start working for you; by selecting &quot;Yes, register me now&quot; this registration process will take place; you will then receive an email with an activation link.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			function ShowTermsOfUseText()
			{
				document.getElementById(\'HelpText\').innerHTML = \'Digiprove won&#39;t be asking for money or credit card details for this service, but as usual there are terms of use governing things like privacy and abuse. Click <a href="http://www.digiprove.com/termsofuse_page.aspx" target="_blank">here</a> to review them in detail.\';
				document.getElementById(\'HelpTextContainer\').style.display=\'inline\';
			}
			//-->
			</script>
		</div>
		');
}

function dprv_options_html($options, $specialOption, $currentValue)
{
	$log = new Logging();  
	$log->lwrite("dprv_options_html starts");  
	$optionsHTML = "";
	$currentMatch = 0;
	$specialMatch = 0;
	foreach ($options as $option)
	{
		$optionsHTML .= '<option value="' . $option . '"';
		if ($currentValue == $option)
		{
			$optionsHTML .= ' selected="selected"';
			$currentMatch = 1;
		}
		$optionsHTML .= '>' . $option . '</option>';
		if ($specialOption == $option)
		{
			$specialMatch = 1;
		}
	}
	if ($specialMatch == 0)
	{
		$optionsHTML .= '<option value="' . $specialOption . '"';
		if ($currentValue == $specialOption)
		{
			$optionsHTML .= 'selected="selected">' . $specialOption . '</option>';
			return $optionsHTML;
		}
		else
		{
			$optionsHTML .= '>' . $specialOption . '</option>';
		}
	}
	if ($currentMatch == 0)
	{
		$optionsHTML .= '<option value="' . $currentValue . '" selected="selected">' . $currentValue . '</option>';
	}
	return $optionsHTML;
}

function dprv_ValidSettings()
{
	$log = new Logging();  
	$log->lwrite("dprv_ValidSettings starts");
	if ($_POST['dprv_enrolled'] == "No" && $_POST['dprv_register'] == "Yes")
	{
		// Check User Id
		if (isset($_POST['dprv_user_id']))
		{
			if (strlen($_POST['dprv_user_id']) < 1)
			{
				return __('You must specify a User Id', 'dprv_cp');
			}
			if (strlen($_POST['dprv_user_id']) > 40)
			{
				return __('Sorry, User Id cannot exceed 40 characters', 'dprv_cp');
			}
		}
		else
		{
			return __('You must specify a User Id', 'dprv_cp');
		}

		// Check password(s)
		if (isset($_POST['dprv_password']))
		{
			if (isset($_POST['dprv_pw_confirm']))
			{
				if ($_POST['dprv_pw_confirm'] == $_POST['dprv_password'])
				{
					if (strlen($_POST['dprv_password']) < 6)
					{
						return __('Password must be at least 6 characters', 'dprv_cp');
					}
					return "";
				}
			}
			return __('Password values do not match', 'dprv_cp');
		}
		else
		{
			if (isset($_POST['dprv_pw_confirm']))
			{
				return __('Password values do not match', 'dprv_cp');
			}
		}
	}
	else
	{
		if ($_POST['dprv_enrolled'] == 'Yes' && (!isset($_POST['dprv_user_id']) || $_POST['dprv_user_id'] == ""))
		{
			return __('Please input your Digiprove User ID', 'dprv_cp');
		}
		if ($_POST['dprv_renew_api_key'] == "on" && (!isset($_POST['dprv_password']) || trim($_POST['dprv_password']) == ""))
		{
			return __('Renewing Api key requires you to enter your password', 'dprv_cp');
		}
	}
	return "";
}

function dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name,$dprv_email_certs)
{
	global $wp_version, $dprv_host;
	$log = new Logging();  
	$log->lwrite("register_user starts");  
	if ($dprv_user_id == "") return __('Please specify a desired Digiprove user id','dprv_cp');
	if ($dprv_password == "") return __('You need to input a password', 'dprv_cp');
	if (strlen($dprv_password) < 6) return __('Password needs to be at least 6 characters', 'dprv_cp');
	if ($dprv_email_address == "") return __('Please input your email address (to which the activation link will be sent)', 'dprv_cp');
	if ($dprv_first_name == "" && $dprv_last_name == "") return __('You need to complete either first or last name', 'dprv_cp');

	// Following code inserted at 0.78 to ensure there is a domain specified
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url[host];
	if (trim($dprv_blog_host) == "")
	{
		$dprv_blog_url = parse_url(get_option('siteurl'));
		$dprv_blog_host = $dprv_blog_url[host];
		if (trim($dprv_blog_host) == "")
		{
			return __('Cannot find the URL of your blog - please check Wordpress Settings (General)', 'dprv_cp');
		}
	}
	// end of 0.78 change

	$postText = "<digiprove_register_user>";
	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<password>' . htmlspecialchars(stripslashes($dprv_password), ENT_QUOTES, 'UTF-8') . '</password>';  // encode password if necessary
	$postText .= '<email_address>' . $dprv_email_address . '</email_address>';
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';
	$postText .= '<first_name>' . htmlspecialchars(stripslashes($dprv_first_name), ENT_QUOTES, 'UTF-8') . '</first_name>';	// transformation may be unnecessary if using SOAP
	$postText .= '<last_name>' . htmlspecialchars(stripslashes($dprv_last_name), ENT_QUOTES, 'UTF-8') . '</last_name>';		// transformation may be unnecessary if using SOAP
	if ($dprv_display_name == "Yes")
	{
		$postText .= '<display_name>Yes</display_name>';
	}
	else
	{
		$postText .= '<display_name>No</display_name>';
	}
	if ($dprv_email_certs == "No")
	{
		$postText .= '<email_certs>No</email_certs>';
	}
	else
	{
		$postText .= '<email_certs>Yes</email_certs>';
	}
    $postText .= '<subscription_plan>' . 'Basic' . '</subscription_plan>';
	$postText .= '</digiprove_register_user>';
	$log->lwrite("xml string = " . $postText);

	//TODO try soap_post first, and on exception use http_post
	//$data = dprv_soap_post($postText, "RegisterUser");
	$data = dprv_http_post($postText, $dprv_host, "/secure/service.asmx/", "RegisterUser");

	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_register_user");
	}
	return $data;  // return;
}
function dprv_update_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs,$dprv_renew_api_key)
{
	global $wp_version, $dprv_host;
	$log = new Logging();  
	$log->lwrite("update_user starts");  
	if ($dprv_user_id == "") return __('Please input your Digiprove user id','dprv_cp');
	if ($dprv_api_key == null || $dprv_api_key == "")
	{
		if ($dprv_password == "") return __('No password or API key', 'dprv_cp');
		if (strlen($dprv_password) < 6) return __('Password needs to be at least 6 characters', 'dprv_cp');;
	}

	// code below changed at 0.78 to avoid empty domain
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url[host];
	$dprv_wp_host = "";		// default

	$dprv_wp_url = parse_url(get_option('siteurl'));
	$dprv_wp_host = $dprv_wp_url[host];
	if (trim($dprv_blog_host) == "")
	{
		$dprv_blog_host = $dprv_wp_host;
	}

	//$dprv_site_url = parse_url(get_option('siteurl'));
	//$dprv_site_host = $dprv_site_url[host];
	// end of 0.78 changes

	$postText = "<digiprove_update_user>";
	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';

	// inserted at 0.78:
	if ($dprv_blog_host != $dprv_wp_host)
	{
		$postText .= '<alt_domain_name>' . $dprv_wp_host . '</alt_domain_name>';
	}
	// end of insertion

	$dprv_api_key = get_option('dprv_api_key');
	if ($dprv_api_key != null & $dprv_api_key != "" && $dprv_renew_api_key != "on")
	{
		$postText .= '<api_key>' . $dprv_api_key . '</api_key>';
	}
	else
	{
		$postText .= '<password>' . htmlspecialchars(stripslashes($dprv_password), ENT_QUOTES, 'UTF-8') . '</password>';  // encode password if necessary
		$postText .= '<request_api_key>Yes</request_api_key>';
	}
	
	$postText .= '<email_address>' . $dprv_email_address . '</email_address>';
	$postText .= '<first_name>' . htmlspecialchars(stripslashes($dprv_first_name), ENT_QUOTES, 'UTF-8') . '</first_name>';	// transformation may be unnecessary if using SOAP
	$postText .= '<last_name>' . htmlspecialchars(stripslashes($dprv_last_name), ENT_QUOTES, 'UTF-8') . '</last_name>';		// transformation may be unnecessary if using SOAP
	if ($dprv_display_name == "Yes")
	{
		$postText .= '<display_name>Yes</display_name>';
	}
	else
	{
		$postText .= '<display_name>No</display_name>';
	}
	if ($dprv_email_certs == "No")
	{
		$postText .= '<email_certs>No</email_certs>';
	}
	else
	{
		$postText .= '<email_certs>Yes</email_certs>';
	}

	$postText .= '</digiprove_update_user>';

	$log->lwrite("xml string = " . $postText);
	$data = dprv_http_post($postText, $dprv_host, "/secure/service.asmx/", "UpdateUser");

	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_update_user");
	}
	return $data;  // return;
}

function dprv_resend_activation_email($dprv_user_id, $dprv_email_address)
{
	global $wp_version, $dprv_host;
	$log = new Logging();  
	$log->lwrite("dprv_resend_activation starts");  
	if (($dprv_user_id == false || $dprv_user_id == "") && ($dprv_email_address == false || $dprv_email_address == ""))
	{
		$dprv_user_id = get_option('dprv_user_id');
		if ($dprv_user_id == false || $dprv_user_id == "")
		{
			$dprv_email_address = get_option('dprv_email_address');
			if ($dprv_email_address == false || $dprv_email_address == "")
			{
				$dprv_email_address = $user_info->user_email;
				if ($dprv_email_address == false)
				{
					return "Cannot determine user id or email address";
				}
			}
		}
	}
	$postText = '<send_activation_email>';
	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<email_address>' . $dprv_email_address . '</email_address>';
	$postText .= '</send_activation_email>';

	//$log->lwrite("xml string = " . $postText);
	$data = dprv_http_post($postText, $dprv_host, "/secure/service.asmx/", "RequestActivationEmail");

	$pos = strpos($data, "Error");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_resend_activation_email");
		return $data;  // return;
	}
	return substr($data, $pos);
}

?>