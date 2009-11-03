<?php
/*
Plugin Name: Digiprove
Plugin URI: http://www.digiprove.com/digiproveblog.aspx
Description: Secure copyright of your blog post by Digiproving it. <a href="options-general.php?page=DigiproveBlog.php">Register and configure here.</a>
Version: 0.64
Author: Digiprove
Author URI: http://www.digiprove.com/
License: GPL
*/
/*  Copyright 2008-2009  Digiprove (email : cian.kinsella@digiprove.com)
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
// Acknowledgements to BTE, Akismet, and After the Deadline, some of whose
// code was used in the development of this plug-in 

// Declare and initialise global variables:
global $dprv_log_is_on, $dprv_host, $dprv_port, $dprv_ssl, $start_Digiprove, $end_Digiprove, $dprv_soap_count;
$dprv_log_is_on = false;				// Set this to true to generate local log-file (needs write permissions)
$dprv_host = "www.digiprove.com";		// -> normally set to "www.digiprove.com"
$dprv_port = 443;						// -> normally set to 443 (usually 80 for http, 443 for https)
$dprv_ssl = "Yes";						// -> normally set to "Yes"
$start_Digiprove = false;
$end_Digiprove = false;
$dprv_soap_count=0;

// Register hooks
register_activation_hook(__FILE__, 'dprv_activate');
register_deactivation_hook(__FILE__, 'dprv_deactivate');
add_action('admin_menu', 'dprv_settings_menu');
add_action('admin_head', 'dprv_admin_head');
add_action('admin_footer', 'dprv_admin_footer');
add_filter('content_save_pre', 'dprv_digiprove_post');

function dprv_activate()
{
	$log = new Logging();  
	$log->lwrite("VERSION 0.64 ACTIVATED");  
	add_option('dprv_email_address', '');
	add_option('dprv_first_name', '');
	add_option('dprv_last_name', '');
	add_option('dprv_content_type', '');
	add_option('dprv_notice', '');
	add_option('dprv_c_notice', '');
	add_option('dprv_notice_border', '');
	add_option('dprv_obscure_url','Obscure');
	add_option('dprv_linkback','Linkback');
	add_option('dprv_body_or_footer', 'Body');
	add_option('dprv_enrolled', 'No');
	add_option('dprv_user_id', '');
	add_option('dprv_password', '');
	add_option('dprv_last_result', '');
	if (get_option('dprv_enrolled') == "No")
	{
		update_option('dprv_last_result', 'Select \'Settings\' - \'Digiprove\' to set up for use');
	}
}

function dprv_deactivate()
{
	$log = new Logging();  
	$log->lwrite("VERSION 0.64 DEACTIVATED");  
	delete_option('dprv_last_result');	// keep other options for future install
}

function dprv_settings_menu()	// Runs after the basic admin panel menu structure is in place - add Digiprove Settings option.
{	
	$log = new Logging();  
	$pagename = add_options_page('DigiproveBlog', 'Digiprove', 10, basename(__FILE__), 'dprv_settings');
}

function dprv_admin_head()	// runs between <HEAD> tags of admin settings page - include css gile
{
	$log = new Logging();  
	$log->lwrite("dprv_admin_head starts");  
	$home = get_settings('siteurl');
	$base="DigiproveBlog";
	$stylesheet = $home.'/wp-content/plugins' . $base . '/css/DigiproveBlog.css';
	echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
}

function dprv_admin_footer()	// runs in admin panel inside body tags - add Digiprove message to message bar
{
	$log = new Logging();  
	$log->lwrite("dprv_admin_footer starts");
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
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
					var pos = existing_message.indexOf("</p>");
					var pub = existing_message.indexOf("Post published");
					var upd = existing_message.indexOf("Post updated");
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
		$dprv_last_result = htmlentities(get_option('dprv_last_result'), ENT_QUOTES, 'UTF-8');
		if ($dprv_last_result != "")
		{
			$log->lwrite("writing javascript to display reminder to set up");
			echo('<script type="text/javascript">
				if (document.getElementById("message") && document.getElementById("message") != null)
				{
					var existing_message = document.getElementById("message").innerHTML;
					var pos = existing_message.indexOf("</p>");
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
	$log->lwrite("exiting drp_admin_footer");
}

function dprv_digiprove_post ($content)	// Core Digiprove-this-post function
{
	$log = new Logging();  
	global $dprv_soap_count, $dprv_host;
	$dprv_soap_count = $dprv_soap_count + 1;
	if ($dprv_soap_count > 1)
	{
		$log->lwrite("dprv_digiprove_post not starting because dprv_soap_count=" . $dprv_soap_count);
		return ($content);
	}
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);  
	if ($script_name != "post")
	{
		$log->lwrite("dprv_digiprove_post not starting because this hook not triggered by post");
		return ($content);
	}
	if (get_option('dprv_enrolled') != "Yes")
	{
		$log->lwrite("dprv_digiprove_post not starting because user not registered yet");
		return ($content);
	}
	if (strlen(trim($content)) == 0)
	{
		$log->lwrite("dprv_digiprove_post not starting because content is empty");
		return ($content);
	}
	update_option('dprv_last_result', '');
	if ($GLOBALS["GLOBALS"]["_POST"]["post_status"] != "publish")
	{
		$log->lwrite("dprv_digiprove_post not starting because status is " . $GLOBALS["GLOBALS"]["_POST"]["post_status"] . ", not published");
		return ($content);
	}
	$log->lwrite("dprv_digiprove_post STARTS");  
	
	//echo dprv_http_post($postText, $dprv_host, "/secure/service.asmx/", "HelloWorld");

	$dprv_title = $GLOBALS["GLOBALS"]["_POST"]["post_title"];
	$dprv_post_id = $GLOBALS["GLOBALS"]["_POST"]["post_ID"];

	$newContent = stripslashes($content);
	$certifyResponse = dprv_certify($dprv_post_id, $dprv_title, $newContent);
	if (strpos($certifyResponse, "Hashes are identical") === false)
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
				$admin_message = 'Error: ' . $admin_message;
			}
			update_option('dprv_last_result', $admin_message);
		}
		else
		{
			$dprv_certificate_id = dprv_getTag($certifyResponse, "certificate_id");
			$dprv_certificate_url = dprv_getTag($certifyResponse, "certificate_url");
			$dprv_utc_date_and_time = dprv_getTag($certifyResponse, "utc_date_and_time");
			$dprv_digital_fingerprint = dprv_getTag($certifyResponse, "digital_fingerprint");
			$dprv_notice = get_option('dprv_notice');
			if (trim($dprv_notice) == "")
			{
				$dprv_notice = "This content has been Digiproved";
			}
			if ($dprv_certificate_id === false || $dprv_certificate_url === false)
			{
				$DigiproveNotice = "\r\n&copy; " . Date("Y") . " and certified by Digiprove.";
			}
			else
			{
				$DigiproveNotice = '<br /><span style="vertical-align:8px; float:left; padding:2px;';
				$dprv_notice_border = get_option('dprv_notice_border');
				if ($dprv_notice_border == false || $dprv_notice_border == "Gray")
				{
					$DigiproveNotice .= 'border:1px solid #BBBBBB"';
				}
				if ($dprv_notice_border == "Red")
				{
					$DigiproveNotice .= 'border:1px solid red"';
				}
				if ($dprv_notice_border == "Black")
				{
					$DigiproveNotice .= 'border:1px solid #000000"';
				}
				if ($dprv_notice_border == "Blue")
				{
					$DigiproveNotice .= 'border:1px solid blue"';
				}
				if ($dprv_notice_border == "Green")
				{
					$DigiproveNotice .= 'border:1px solid darkgreen"';
				}
				if ($dprv_notice_border == "None")
				{
					$DigiproveNotice .= 'border:0px"';
				}
				$DigiproveNotice .= ' title="certified ' . $dprv_utc_date_and_time . ' by Digiprove certificate ' . $dprv_certificate_id . '" >';
				$DigiproveNotice .= '<a href="' . $dprv_certificate_url . '" target="_blank" ';
				$DigiproveNotice .= 'style="border:0px; background-color:#FFFFFF; text-decoration: none;" >';
				$DigiproveNotice .= '<img src="http://www.digiprove.com/images/dp_seal_trans_16x16.png" style="vertical-align:middle; display:inline; border:0px" border="0">';
				$DigiproveNotice .= ' <span  style="font-family: Tahoma, MS Sans Serif; font-size:11px; color:#636363; letter-spacing:normal" ';
				$DigiproveNotice .= 'onmouseover="this.style.color=\'#A35353\';" onmouseout="this.style.color=\'#636363\';">';
				$DigiproveNotice .= $dprv_notice;
				if (get_option('dprv_c_notice') != "NoDisplay")
				{
					$DigiproveNotice .= '&nbsp;&copy; ' . Date('Y');
				}
				$DigiproveNotice .= '</span></a>';
				$DigiproveNotice .= '<!--' . $dprv_digital_fingerprint . '-->';
				$DigiproveNotice .= '</span><br />';
			}
			$newContent = dprv_insertNotice($newContent, $DigiproveNotice);
			if (get_option('dprv_enrolled') != "Yes")
			{
				update_option('dprv_enrolled', 'Yes');
			}
			$log->lwrite("Digiproving completed successfully");
			update_option('dprv_last_result',"Digiprove certificate id: " . $dprv_certificate_id);
		}
	}
	else
	{
		update_option('dprv_last_result', 'Content unchanged since last edit');
	}
	return $newContent;
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
	$rawContent = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');
	$title = htmlspecialchars(stripslashes($title), ENT_QUOTES, 'UTF-8');		// Note this may not be necessary if using SOAP
	$dprv_content_type = get_option('dprv_content_type');
	if (trim($dprv_content_type) == "")
	{
		$dprv_content_type = "Blog post";
	}
	$postText = "<digiprove_content_request>";
	$postText .= "<user_id>" . get_option('dprv_user_id') . "</user_id>";
	$postText .= '<password>' . get_option('dprv_password') . '</password>';
	$postText .= '<user_agent>Wordpress ' . $wp_version . ' / Digiprove plugin 0.64</user_agent>';
    $postText .= '<content_type>' . $dprv_content_type . '</content_type>';
    $postText .= '<content_title>' . $title . '</content_title>';
    $postText .= '<content_data>' . $rawContent . '</content_data>';
    if (get_option('dprv_linkback') == "Linkback")
	{
		$postText .= '<content_url>' . get_bloginfo('url') ."/?p=" . $post_id . '</content_url>';
	}
	if (get_option('dprv_obscure_url') == "Clear")
	{
		$postText .= '<obscure_certificate_url>' . 'No' . '</obscure_certificate_url>';
	}
	else
	{
		$postText .= '<obscure_certificate_url>' . 'Yes' . '</obscure_certificate_url>';
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

function dprv_soap_post($request, $method) 
{
	global $dprv_host, $dprv_port, $dprv_ssl;
	$log = new Logging();  
	try
	{
		$log->lwrite("dprv_soap_post starts, method=" . $method);
		$dprv_WSDL_url = "";
		if ($dprv_ssl == "Yes")
		{
			$dprv_WSDL_url = "https://" . $dprv_host;
			if ($dprv_port != 443)
			{
				$dprv_WSDL_url .= ":" . $dprv_port;
			}
		}
		else
		{
			$dprv_WSDL_url = "http://" . $dprv_host;
			if ($dprv_port != 80)
			{
				$dprv_WSDL_url .= ":" . $dprv_port;
			}
		}
		$dprv_WSDL_url .= "/secure/Service.asmx?WSDL";
		//$client = new SoapClient("https://www.digiprove.com/secure/Service.asmx?WSDL",  
		$client = new SoapClient($dprv_WSDL_url,  
			array(	'soap_version' => SOAP_1_2,
					'trace' => true,
					'encoding'=>'utf-8'));
		$dprv_result = $client->$method(array('xml_string' => $request));
		$result_vars    = get_object_vars($dprv_result);
		return $result_vars[$method . "Result"];
	}
	catch(SoapFault $ex)
	{
		$dprv_error = $ex->getMessage();
		$log->lwrite("Soap error in dprv_soap_post: " . $dprv_error);
		$return_value = $dprv_error;
		$pos = strpos($dprv_error, "\n");
		if ($pos != false && $pos > 0)
		{
			$return_value = substr($dprv_error, 0, $pos);
		}
		return "Error: " . $return_value;
	}
}

/* this function based on that from akismet.php by Matt Mullenweg.  *props* */
function dprv_http_post($request, $host, $path, $service, $ip=null) 
{
	global $dprv_port, $dprv_ssl;
	$log = new Logging();  
	$request = "xml_string=" . urlencode($request);
	$http_request  = "POST " . $path . $service . " HTTP/1.1\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
	$http_request .= "Content-Length: " . strlen($request) . "\r\n";
	$http_request .= "Connection: close\r\n\r\n";
	$http_request .= $request;  

	// use a specific IP if provided
	if ( $ip && long2ip(ip2long($ip)) )
	{
		$http_host = $ip;
	}
	else
	{
		//$http_host = akismet_get_host($host);  //TODO: implement this akismet resilience code
		$http_host = $host;
	}
	$log->lwrite("http_post of " . $http_request);

	$response = '';                 
	try
	{
		if ($dprv_ssl == "Yes")
		{
			$http_host = "ssl://" . $http_host;
		}
		if( false != ( $fs = @fsockopen($http_host, $dprv_port, $errno, $errstr, 10) ) ) 
		{                 
			$log->lwrite("socket open, errno = " . $errno);
			if ($errno == 0)
			{
				fwrite($fs, $http_request);
				$log->lwrite("fwrite done, now get response when it comes");
				stream_set_timeout($fs, 20);
				$get_count = 0;
				while ( !feof($fs) )
				{
					error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);	// Suppress Warning errors just for this due to intermittent bug (in IIS?)
					$temp = fgets($fs);
					error_reporting(E_ALL & ~E_NOTICE);					// Reset error reporting back to default
					$info = stream_get_meta_data($fs);
					if ($info['timed_out'])
					{
						$log->lwrite("timed out waiting for response");
						return "Error: connection timed out";
					}
					else
					{
						$log->lwrite("got this: " . $temp);
						$response .= $temp;
						$log->lwrite("get " . $get_count . " done, response length = " . strlen($response));
						$get_count = $get_count + 1;
						//TODO - remove this workaround when bug fixed 
						//if (strpos($temp, "&lt;/string&gt;") !== false)   // &lt;/result&gt;
						//{
						//	$log->lwrite("breaking because we got string end tag");
						//	break;
						//}
					}
				}
				$log->lwrite("finished getting, about to close socket");
				fclose($fs);
				//TODO: check that response is complete (ends with </string>)
				$response = htmlspecialchars_decode($response, ENT_QUOTES);
			}
			else
			{
				$log->lwrite("Socket may be open, but error code = " . $errno);
				return "Error: Could not open socket to " . $http_host . ".  Error code = " . $errno;
			}
		}
		else
		{
			if ($errno ==0)
			{
				$log->lwrite("Could not initialise socket");
				return "Error: Could not initialise socket to " . $http_host;
			}
			$log->lwrite("Could not open socket, error = " . $errno);
			return "Error: Could not open socket to " . $http_host . ".  Error code = " . $errno;
		}
		$log->lwrite("Got response ok: " . $response);
		return $response;
	}
	catch (Exception $e)
	{
		$log->lwrite("Exception : " . $e->getMessage());
		return 'Error: ' . $e->getMessage();
	}
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
	$log->lwrite("getRawContent starts, content=" . $contentString);  
	$start_Digiprove = strpos($contentString, "<!--Digiprove_Start-->");
	$end_Digiprove = false;
	if ($start_Digiprove === false)
	{
		$log->lwrite("no Digiprove Start marker");
		$raw_content = htmlspecialchars_decode($contentString, ENT_QUOTES);  		// decode any encoded XML-incompatible characters now to ensure match with post-xml decoded string on server
		return trim($raw_content);
	}
	$end_Digiprove = strpos($contentString, "<!--Digiprove_End-->");
	if ($start_Digiprove === false || $end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		$log->lwrite("no Digiprove_End marker or not greater than start");  
		$raw_content = htmlspecialchars_decode($contentString, ENT_QUOTES);  		// decode any encoded XML-incompatible characters now to ensure match with post-xml decoded string on server
		return trim($raw_content);
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
	$raw_content = htmlspecialchars_decode($raw_content, ENT_QUOTES);  		// decode any encoded XML-incompatible characters now to ensure match with post-xml decoded string on server
	try
	{
		$raw_content_hash = strtoupper(hash("sha256", $raw_content));
	}
	catch (Exception $e)
	{
		$log->lwrite("Exception " . $e.getMessage() . " in getRawContent");  
		return $raw_content;	// if error, probably older version of PHP, what to do?
	}

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
	return $raw_content;
}


class Logging
{
	// define file pointer  
	private $fp = null; 

	// write message to the log file  
	public function lwrite($message)
	{  
		global $dprv_log_is_on;
		if ($dprv_log_is_on == true)
		{
			// if file pointer doesn't exist, then open log file  
			if (!$this->fp) $this->lopen(); 
			if (!$this->fp) return;														// if cannot open/create logfile, just return
			$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME); 
			$time = date('H:i:s');  
			// write current time, script name and message to the log file  
			@fwrite($this->fp, "$time ($script_name) $message\n") or $this->logwarning('(note - could not write to log-file)');
		}
	}  
	// open log file  
	private function lopen()
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
	private function logwarning($dprv_warning)
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

	if (empty($_POST['dprv_blg_action']))									// if this is not postback
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
		$dprv_notice_border = get_option('dprv_notice_border');

		if ($dprv_notice_border == false)
		{
			$dprv_notice_border = 'Gray';
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
		if ($dprv_user_id == false)
		{
			$dprv_user_id = $user_info->user_email;
		}
		$dprv_password = get_option('dprv_password');
		$dprv_pw_confirm = get_option('dprv_password');
		$dprv_last_result = get_option('dprv_last_result');
	}
	else		// Is POSTBACK
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
		$dprv_content_type = $_POST['dprv_content_type'];
		$dprv_notice = $_POST['dprv_notice'];
		$dprv_c_notice = $_POST['dprv_c_notice'];
		$dprv_notice_border = $_POST['dprv_notice_border'];
		$dprv_obscure_url = $_POST['dprv_obscure_url'];
		$dprv_linkback = $_POST['dprv_linkback'];
		$dprv_body_or_footer = $_POST['dprv_body_or_footer'];
		$dprv_enrolled = $_POST['dprv_enrolled'];
		$dprv_user_id = $_POST['dprv_user_id'];
		$dprv_password = $_POST['dprv_password'];
		$dprv_pw_confirm = $_POST['dprv_pw_confirm'];
		$dprv_last_result = get_option('dprv_last_result');

		// VALIDATE
		$result_message = dprv_ValidSettings();
		if ($result_message == "")
		{
			$log->lwrite("dprv_settings starting");
			if (isset($_POST['dprv_email_address']))
			{
				update_option('dprv_email_address',$_POST['dprv_email_address']);
			}
			if (isset($_POST['dprv_first_name']))
			{
				update_option('dprv_first_name',$_POST['dprv_first_name']);
			}
			if (isset($_POST['dprv_last_name']))
			{
				update_option('dprv_last_name',$_POST['dprv_last_name']);
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
			if (isset($_POST['dprv_notice_border']))
			{
				update_option('dprv_notice_border',$_POST['dprv_notice_border']);
			}
			if (isset($_POST['dprv_obscure_url']))
			{
				update_option('dprv_obscure_url',$_POST['dprv_obscure_url']);
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
			}
			if (isset($_POST['dprv_user_id']))
			{
				update_option('dprv_user_id',$_POST['dprv_user_id']);
			}
			if (isset($_POST['dprv_password']))
			{
				update_option('dprv_password',$_POST['dprv_password']);
			}
			$message = __("Digiprove Settings Updated.", 'dprv_blg');

			$dprv_register_option=$_POST['dprv_register'];
			if ($dprv_enrolled == "No" && $dprv_register_option == "Yes")
			{
				$register_response = dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name);
				$pos = strpos($register_response, "<result_code>0");
				if ($pos === false)
				{
					$failure_message = dprv_getTag($register_response,"result");
					if ($failure_message == false)
					{
						$failure_message = $register_response;
					}
					$result_message = '<font color="orangered">Registration did not complete: ' . htmlentities($failure_message, ENT_QUOTES, 'UTF-8') . '</font>';
					$log->lwrite("Registration failed, response:");
					$log->lwrite($register_response);
				}
				else
				{
					$result_message = "Digiprove user registration was successful, check your email for the activation link";
					update_option('dprv_enrolled',"Yes");
					$dprv_enrolled = "Yes";
				}
			}
		}
		else
		{
			$message = "<font color='orangered'>" . __("Digiprove Settings not Updated.", 'dprv_blg') . "</font>";
		}

		$log->lwrite("About to display $message");
		print('
			<div id="message" class="updated fade">
				<p>'.__($message, 'dprv_blg').'&nbsp;&nbsp;' . $result_message);
		print ('</p>
			</div>');

	}

	$log->lwrite("dprv_settings about to display");
	
	// Prepare HTML to represent DB values for drop-down and radio buttons
	$dprv_notice_content_selected = '';
	$dprv_notice_article_selected = '';
	$dprv_notice_blogpost_selected = '';
	$dprv_notice_protected_selected = '';
	$dprv_notice_secured_selected = ' selected="selected"';

	if ($dprv_notice == 'This content has been Digiproved')
	{
		$dprv_notice_content_selected = ' selected="selected"';
		$dprv_notice_secured_selected = '';
	}

	if ($dprv_notice == 'This article has been Digiproved')
	{
		$dprv_notice_article_selected = ' selected="selected"';
		$dprv_notice_secured_selected = '';
	}
	if ($dprv_notice == 'This blog post has been Digiproved')
	{
		$dprv_notice_blogpost_selected = ' selected="selected"';
		$dprv_notice_secured_selected = '';
	}

	if ($dprv_notice == 'Copyright protected by Digiprove')
	{
		$dprv_notice_protected_selected = ' selected="selected"';
		$dprv_notice_secured_selected = '';
	}

	if ($dprv_notice == 'Copyright secured by Digiprove')
	{
		$dprv_notice_secured_selected = ' selected="selected"';
	}

	$dprv_c_selected = ' selected="selected"';
	$dprv_no_c_selected = '';
	if ($dprv_c_notice != 'Display')
	{
		$dprv_c_selected = '';
		$dprv_no_c_selected = ' selected="selected"';
	}

	$dprv_no_border_selected = '';
	$dprv_gray_border_selected = ' selected="selected"';
	$dprv_red_border_selected = '';
	$dprv_black_border_selected = '';
	$dprv_blue_border_selected = '';
	$dprv_green_border_selected = '';
	if ($dprv_notice_border == 'None')
	{
		$dprv_no_border_selected = ' selected="selected"';
		$dprv_gray_border_selected = '';
	}
	if ($dprv_notice_border == 'Red')
	{
		$dprv_red_border_selected = ' selected="selected"';
		$dprv_gray_border_selected = '';
	}
	if ($dprv_notice_border == 'Black')
	{
		$dprv_black_border_selected = ' selected="selected"';
		$dprv_gray_border_selected = '';
	}
	if ($dprv_notice_border == 'Blue')
	{
		$dprv_blue_border_selected = ' selected="selected"';
		$dprv_gray_border_selected = '';
	}
	if ($dprv_notice_border == 'Green')
	{
		$dprv_green_border_selected = ' selected="selected"';
		$dprv_gray_border_selected = '';
	}
	if ($dprv_notice_border == 'Gray')
	{
		$dprv_gray_border_selected = ' selected="selected"';
	}

	$dprv_obscure_selected = ' selected="selected"';
	$dprv_clear_selected = '';
	if ($dprv_obscure_url != 'Obscure')
	{
		$dprv_obscure_selected = '';
		$dprv_clear_selected = ' selected="selected"';
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
		$dprv_display_register_row = 'style=" display:none"';
	}

	$dprv_register_now_checked = ' checked="checked"';
	$dprv_register_later_checked = '';
	if ($dprv_register_option == "No")
	{
		$dprv_register_now_checked = '';
		$dprv_register_later_checked = ' checked="checked"';
	}

	print('
			<div class="wrap" style="padding-top:4px">
				<h2 style="vertical-align:8px;"><a href="http://www.digiprove.com"><img src="http://www.digiprove.com/images/digiprove_logo_278x69.png" alt="Digiprove"/></a><span style="vertical-align:22px; padding-left:40px">'.__('Plugin Settings', 'DigiproveBlog').'</span></h2> 
				<form id="dprv_blg" name="dprv_DigiproveBlog" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=DigiproveBlog.php" method="post">
					<input type="hidden" name="dprv_blg_action" value="dprv_blg_update_settings" />
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
														<td style="border:1px solid #666666; border-top:0px; border-left:0px; border-right:0px"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart1">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEFFEE; border:1px solid #666666; border-top:0px; border-bottom:0px; width:100%">
													<tr><td style="height:6px; width:300px"></td></tr>
													<tr><td><b>' . __('Your details', 'dprv_blg').'</b></td></tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td style="width:300px"><label for="dprv_email_address">'.__('Email address', 'dprv_blg').'</label></td>
														<td><input name="dprv_email_address" type="text" value="'.htmlspecialchars(stripslashes($dprv_email_address)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_first_name">'.__('First name: ', 'dprv_blg').'</label></td>
														<td><input name="dprv_first_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_first_name)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_last_name">'.__('Last name: ', 'dprv_blg').'</label></td>
														<td><input name="dprv_last_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_last_name)).'" style="width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart2">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFEEEE; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px; width:300px"></td></tr>
													<tr>
														<td colspan="2" style="font-weight:bold">' . __('Digiprove registration details', 'dprv_blg').'</td>
													</tr>
													<tr><td style="height:12px"></td></tr>
													<tr>
														<td><label for="dprv_enrolled">'.__('Registered Digiprove user?: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_enrolled" id="dprv_enrolled" onchange="toggleCredentialsLabels()" style="width:290px">
																<option value="Yes" ' . $dprv_enrolled_selected . '>I am already registered with Digiprove</option>
																<option value="No" ' . $dprv_not_enrolled_selected . '>I have not yet registered with Digiprove</option>
															</select>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="dprv_register_row"' . $dprv_display_register_row . ';">
														<td><label for="dprv_register">'.__('Do you want to register now?: ', 'dprv_blg').'</label></td>
														<td><input type="radio" name="dprv_register" value="Yes" ' . $dprv_register_now_checked . '/>Yes, register me now&nbsp;&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_register" value="No" ' . $dprv_register_later_checked . '/>No, do it later&nbsp;&nbsp;&nbsp;<span class="description" ><a href="javascript:ShowRegistrationText()">' .__('What&#39;s this about?', 'dprv_blg') . '</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://www.digiprove.com/termsofuse_page.aspx" target="_blank">' .__('Terms of use.', 'dprv_blg') . '</a></span></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td style="vertical-align:top"><label for="dprv_user_id" id="dprv_user_id_labelA">'.__('Digiprove User Id: ', 'dprv_blg').'</label><label for="dprv_user_id" id="dprv_user_id_labelB" style="display:none">'.__('Desired Digiprove User Id: ', 'dprv_blg').'</label></td>
														<td><input name="dprv_user_id" type="text" value="'.htmlspecialchars(stripslashes($dprv_user_id)).'" onblur="javascript:ScheduleRestorePassword()" style="width:290px"/>
														<br />
														<span class="description">'.__('Note: user id is shown in the Digiprove certificate; change to something else to keep your email address private.', 'dprv_blg').'</span></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_password" id="dprv_password_labelA">'.__('Digiprove Password: ', 'dprv_blg').'</label>
															<label for="dprv_password" id="dprv_password_labelB" style="display:none">'.__('Registered Digiprove Password: ', 'dprv_blg').'</label></td>
														<td><input name="dprv_password" id="dprv_password" type="password" value="'.htmlspecialchars(stripslashes($dprv_password)).'" onchange="javascript:SavePassword()" style="width:290px" /></td>
													</tr>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td></td>
														<td><input name="dprv_pw_confirm" type="password" value="'.htmlspecialchars(stripslashes($dprv_pw_confirm)).'" style="width:290px" /><span class="description">'.__('type the password again.', 'dprv_blg').'</span></td>
													</tr>
													<tr><td style="height:6px"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="AdvancedPart1" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEEEFF; border:1px solid #666666; border-top:0px; border-bottom:0px; width:100%">
													<tr><td style="height:6px; width:300px"></td></tr>
													<tr><td colspan="2"><b>' . __('The Digiprove notice (at foot of each post)', 'dprv_blg').'</b></td></tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_notice">'.__('Digiprove Notice Text: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_notice" id="dprv_notice" onchange="Preview()" style="width:290px">
																<option value="This content has been Digiproved"' . $dprv_notice_content_selected . '>This content has been Digiproved</option>
																<option value="This article has been Digiproved"' . $dprv_notice_article_selected . '>This article has been Digiproved</option>
																<option value="This blog post has been Digiproved"' . $dprv_notice_blogpost_selected . '>This blog post has been Digiproved</option>
																<option value="Copyright protected by Digiprove"' . $dprv_notice_protected_selected . '>Copyright protected by Digiprove</option>
																<option value="Copyright secured by Digiprove"' . $dprv_notice_secured_selected . '>Copyright secured by Digiprove</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_c_notice">'.__('Display copyright symbol &amp; year: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_c_notice" id="dprv_c_notice" onchange="Preview()" style="width:290px">
																<option value="Display"' . $dprv_c_selected . '>Display (in addition to Digiprove notice)</option>
																<option value="NoDisplay"' . $dprv_no_c_selected . '>Do not display</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_notice_border">'.__('Select border style: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_notice_border" id="dprv_notice_border" onchange="Preview()" style="width:290px">
																<option value="None"' . $dprv_no_border_selected . '>No border</option>
																<option value="Gray" style="color:gray"' . $dprv_gray_border_selected . '>Gray</option>
																<option value="Red" style="color:red"' . $dprv_red_border_selected . '>Red</option>
																<option value="Black" style="color:black"' . $dprv_black_border_selected . '>Black</option>
																<option value="Blue" style="color:blue"' . $dprv_blue_border_selected . '>Blue</option>
																<option value="Green" style="color:darkgreen"' . $dprv_green_border_selected . '>Green</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_notice_preview">'.__('Preview: ', 'dprv_blg').'</label></td>
														<td id="dprv_notice_preview"></td>
													</tr>
													<tr style="display:none"><td style="height:6px"></td></tr>
													<tr style="display:none">
														<td><label for="dprv_body_or_footer">'.__('Place Digiprove notice in body or footer:&nbsp;&nbsp;', 'dprv_blg').'</label></td>
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
													<tr><td style="height:6px; width:300px"></td></tr>
													<tr><td colspan="2"><b>' . __('The certificate (on Digiprove web-page)', 'dprv_blg').'</b></td></tr>
													<tr><td style="height:6px"></td></tr>
									
													<tr>
														<td><label for="dprv_content_type">'.__('How do you want your content described?: ', 'dprv_blg').'</label></td>
														<td><input name="dprv_content_type" type="text" value="'.htmlspecialchars(stripslashes($dprv_content_type)).'"  style="width:290px"/><span class="description">e.g. &quot;Blog post&quot;, &quot;News article&quot;, &quot;Opinion&quot;</span></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_obscure_url">'.__('Obscure Digiprove certificate link: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_obscure_url" style="width:290px">
																<option value="Obscure"' . $dprv_obscure_selected . '>Obscure the link (for privacy)</option>
																<option value="Clear"' . $dprv_clear_selected . '>Do not obscure the link (for search engine optimisation)</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td><label for="dprv_linkback">'.__('Certificate web-page to link back to post?: ', 'dprv_blg').'</label></td>
														<td><select name="dprv_linkback" style="width:290px">
																<option value="Linkback"' . $dprv_linkback_selected . '>Digiprove certificate web-page should have a link to relevant blog post</option>
																<option value="Nolink"' . $dprv_no_linkback_selected . '>Do not link back to my blog posts</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>');
	if ($dprv_last_result != '')
	{
		print ('
										<tr id="BasicPart3">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; background-color:#DDDDE4; border:1px solid #666666; border-top:0px; width:100%">
													<tr><td style="height:6px;width:300px"></td></tr>
													<tr>
														<td><label for="dprv_last_result">'.__('Result of last Digiprove action:&nbsp;&nbsp;', 'dprv_blg').'</label></td>
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
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update Settings', 'dprv_blg').'" />
					</p>
					<div id="RegistrationHelpText" style="display:none; position:absolute; left:492px; top:620px; width:390px; border:1px solid #333333; background-color:white; padding:3px">The Digiprove service needs the name of the person claiming copyright, and a valid email address to which Digiprove content certificates will be sent. Digiprove does not make any other use of these details.<br />
					<br />
					<a href="javascript:HideRegistrationText()" style="float:right">Close this window</a>
					</div>

				</form>' );
	print ('
			<script type="text/javascript">
			<!--
			function DisplayBasic()
			{
				document.getElementById("BasicTab").style.borderBottom="0px";
				document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
				document.getElementById("BasicPart1").style.display="";
				document.getElementById("BasicPart2").style.display="";
				if (document.getElementById("BasicPart3") != null)
				{
					document.getElementById("BasicPart3").style.display="";
				}
				document.getElementById("AdvancedPart1").style.display="none";
				document.getElementById("AdvancedPart2").style.display="none";
			}
			function DisplayAdvanced()
			{
				document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
				document.getElementById("AdvancedTab").style.borderBottom="0px";
				document.getElementById("BasicPart1").style.display="none";
				document.getElementById("BasicPart2").style.display="none";
				if (document.getElementById("BasicPart3") != null)
				{
					document.getElementById("BasicPart3").style.display="none";
				}	
				document.getElementById("AdvancedPart1").style.display="";
				document.getElementById("AdvancedPart2").style.display="";
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

			Preview();
			function Preview()
			{
				var notice_border = document.getElementById("dprv_notice_border").value;
				var notice_text = document.getElementById("dprv_notice").value;
				var c_notice = document.getElementById("dprv_c_notice").value;
				var now = new Date();
				var DigiproveNotice = "<span style=\"float:left; padding:2px;background-color:#FFFFFF;";
				if (notice_border == "" || notice_border == "Gray")
				{
					DigiproveNotice += "border:1px solid #BBBBBB\"";
				}
				if (notice_border == "Red")
				{
					DigiproveNotice += "border:1px solid red\"";
				}
				if (notice_border == "Black")
				{
					DigiproveNotice += "border:1px solid #000000\"";
				}
				if (notice_border == "Blue")
				{
					DigiproveNotice += "border:1px solid blue\"";
				}
				if (notice_border == "Green")
				{
					DigiproveNotice += "border:1px solid darkgreen\"";
				}
				if (notice_border == "None")
				{
					DigiproveNotice += "border:0px\"";
				}
				DigiproveNotice += "><img src=\"http://www.digiprove.com/images/dp_seal_trans_16x16.png\" style=\"vertical-align:middle; display:inline; border:0px\" border=\"0\">";
				DigiproveNotice += " <span  style=\"font-family: Tahoma, MS Sans Serif; font-size:11px; color:#636363; text-decoration: none; letter-spacing:normal\">" + notice_text;
				if (c_notice != "NoDisplay")
				{
					var year = now.getFullYear();
					DigiproveNotice += "&nbsp;&copy; " + year;
				}
				DigiproveNotice += "</span></span>";
				document.getElementById("dprv_notice_preview").innerHTML = DigiproveNotice;
			}

			toggleCredentialsLabels();
			function toggleCredentialsLabels()
			{
				if (document.getElementById("dprv_enrolled").value == "Yes")
				{
					document.getElementById("dprv_register_row").style.display="none";
					document.getElementById("dprv_user_id_labelA").style.display="";
					document.getElementById("dprv_user_id_labelB").style.display="none";
				}
				else
				{
					document.getElementById("dprv_register_row").style.display="";
					document.getElementById("dprv_user_id_labelA").style.display="none";
					document.getElementById("dprv_user_id_labelB").style.display="";
				}
			}

			function ShowRegistrationText()
			{
				document.getElementById(\'RegistrationHelpText\').style.display=\'inline\';
			}
			function HideRegistrationText()
			{
				document.getElementById(\'RegistrationHelpText\').style.display=\'none\';
			}

			//-->
			</script>
			');
}

function dprv_ValidSettings()
{
	$log = new Logging();  
	$log->lwrite("dprv_ValidSettings starts");  
	// Check password(s)
	if (isset($_POST['dprv_password']))
	{
		if (isset($_POST['dprv_pw_confirm']))
		{
			if ($_POST['dprv_pw_confirm'] == $_POST['dprv_password'])
			{
				if (strlen($_POST['dprv_password']) < 6)
				{
					return __('Password must be at least 6 characters', 'dprv_blg');
				}
				return "";
			}
		}
		return __('Password values do not match', 'dprv_blg');
	}
	else
	{
		if (isset($_POST['dprv_pw_confirm']))
		{
			return __('Password values do not match', 'dprv_blg');
		}
	}
	return "";
}

function dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name)
{
	global $dprv_host;
	$log = new Logging();  
	$log->lwrite("register_user starts");  
	if ($dprv_user_id == "") return "Please specify a desired Digiprove user id";
	if ($dprv_password == "") return "You need to input a password";
	if (strlen($dprv_password) < 6) return "Password needs to be at least 6 characters";
	if ($dprv_email_address == "") return "Please input your email address (to which the activation link will be sent)";
	if ($dprv_first_name == "" && $dprv_last_name == "") return "You need to complete either first or last name";

	$postText = "<digiprove_register_user>";
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<password>' . $dprv_password . '</password>';
	$postText .= '<email_address>' . $dprv_email_address . '</email_address>';
	$postText .= '<first_name>' . htmlspecialchars(stripslashes($dprv_first_name), ENT_QUOTES, 'UTF-8') . '</first_name>';	// transformation may be unnecessary is using SOAP
	$postText .= '<last_name>' . htmlspecialchars(stripslashes($dprv_last_name), ENT_QUOTES, 'UTF-8') . '</last_name>';		// transformation may be unnecessary is using SOAP
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

?>