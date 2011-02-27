<?php
/*
Plugin Name: Copyright Proof
Plugin URI: http://www.digiprove.com/copyright_proof_wordpress_plugin.aspx
Description: Digitally certify your posts to prove copyright ownership, generate copyright notice, and copy-protect text and images.
Version: 1.01
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
// under the GNU Lesser General Public License (LGPL): www.gnu.org/copyleft/lesser.html

// Acknowledgements to BTE, Akismet, and After the Deadline, some of whose code was used
// in the development of this plug-in

include_once('copyright_proof_admin.php');						// Functions for Settings panel
include_once('copyright_proof_edit.php');						// Functions for Creating and Editing content (includes Digiprove content call/response)
include_once('copyright_proof_live.php');						// Functions for Serving pages on web
if (intval(substr(PHP_VERSION,0,1)) > 4)
{
	include_once('copyright_proof_http_functions.php');			// Functions for HTTP
}
else
{
	include_once('copyright_proof_http_functions_basic.php');	// Functions for HTTP
}


// Declare and initialise global variables:
define("DPRV_VERSION", "1.01");
define("DPRV_HOST", "www.digiprove.com");       // -> should be set to "www.digiprove.com"
define("DPRV_SSL", "Yes");                      // -> should be set to "Yes"
define("DPRV_Log", "No");                       // Set this to "Yes" to generate local log-file (needs write permissions)

global $dprv_port, $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $dprv_post_id;

$dprv_port = 443;                               // -> should be set to 443 (standard settings 80 for http, 443 for https)

// Define arrays of license information - populated in header sections
$dprv_licenseIds = array();
$dprv_licenseTypes = array();
$dprv_licenseCaptions = array();
$dprv_licenseAbstracts = Array();
$dprv_licenseURLs = array();



// Register hooks
register_activation_hook(__FILE__, 'dprv_activate');
register_deactivation_hook(__FILE__, 'dprv_deactivate');
add_action('init', 'dprv_init');
add_action('delete_post', 'dprv_post_sync', 10);

add_action('admin_menu', 'dprv_settings_menu');
add_action('admin_head', 'dprv_admin_head');
add_action('admin_footer', 'dprv_admin_footer');

add_action('post_submitbox_start', 'dprv_add_digiprove_submit_button');
add_filter('wp_insert_post_data', 'dprv_parse_post', 99, 2);
add_filter('wp_insert_page_data', 'dprv_parse_post', 99, 2);
add_action('wp_insert_post', 'dprv_digiprove_post');
add_action('wp_insert_page', 'dprv_digiprove_post');

if (get_option('dprv_enrolled') == "Yes")
{
	add_action('add_meta_boxes', 'dprv_postbox');
}

add_filter( "wp_head", "dprv_head" );
add_filter( "the_content", "dprv_display_content" );
add_filter( "wp_footer", "dprv_footer" );


// FROM HERE DOWN IS ALL FUNCTIONS:

// ON-ACTIVATION FUNCTIONS:
function dprv_activate()
{
	$log = new Logging();  
	$log->lwrite("");  
	$log->lwrite("VERSION " . DPRV_VERSION . " ACTIVATED");  
	add_option('dprv_email_address', '');
	add_option('dprv_first_name', '');
	add_option('dprv_last_name', '');
	add_option('dprv_subscription_type', '');
	add_option('dprv_subscription_expiry', '');
	add_option('dprv_content_type', '');
	add_option('dprv_notice', '');
	add_option('dprv_c_notice', 'DisplayAll');
	add_option('dprv_notice_size', '');
	add_option('dprv_license', '0');
	add_option('dprv_frustrate_copy', '');
	add_option('dprv_right_click_message', '');
	add_option('dprv_record_IP', '');
	add_option('dprv_notice_border', '');
	add_option('dprv_notice_background', '');
	add_option('dprv_notice_color', '');
	add_option('dprv_hover_color', '');
	add_option('dprv_obscure_url','Obscure');
	add_option('dprv_display_name','Yes');
	add_option('dprv_email_certs','No');
	add_option('dprv_linkback','Nolink');
	add_option('dprv_body_or_footer', 'Body');
	add_option('dprv_enrolled', 'No');
	add_option('dprv_user_id', '');
	add_option('dprv_api_key', '');
	add_option('dprv_last_action', '');
	add_option('dprv_last_result', '');
	add_option('dprv_last_date','');
	add_option('dprv_last_date_count','0');
	add_option('dprv_event');
	create_dprv_license_table();
	create_dprv_post_table();
}

function create_dprv_license_table()
{
	$log = new Logging();  

	// To allow this to be as extensible as possible, make sure $table_prefix is globalised, we also need the $wpdb class functions too
	global $table_prefix, $wpdb;

	// Create the 'name' of our table which is prefixed by the standard WP table prefix (which you specified when you installed WP)
	$dprv_licenses = $table_prefix . "dprv_licenses";

	// Check to see if the table exists already, if not, then create it
	$wpdb->show_errors();
	if($wpdb->get_var("show tables like '$dprv_licenses'") != $dprv_licenses)
	{
		$log->lwrite("creating license table " . $dprv_licenses);  
		$sql = "CREATE TABLE " . $dprv_licenses . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				license_type varchar(50) NOT NULL,
				license_caption varchar (40) NOT NULL,
				license_abstract text(1000),
				license_url varchar(255),
				UNIQUE KEY id (id)
				);";

		//We need to include this file so we have access to the dbDelta function below (which is used to create the table)
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		dbDelta($sql);
		record_dprv_licenses();
	}
	else
	{
		$log->lwrite("Table " . $dprv_licenses . " exists already");  
	}
}

function create_dprv_post_table()
{
	$log = new Logging();  

	// To allow this to be as extensible as possible, make sure $table_prefix is globalised, we also need the $wpdb class functions too
	global $table_prefix, $wpdb;

	// Create the 'name' of our table which is prefixed by the standard WP table prefix (which you specified when you installed WP)
	$dprv_posts = $table_prefix . "dprv_posts";

	// Check to see if the table exists already, if not, then create it
	$wpdb->show_errors();
	if($wpdb->get_var("show tables like '$dprv_posts'") != $dprv_posts)
	{
		$log->lwrite("creating table " . $dprv_posts);  
		$sql = "CREATE TABLE " . $dprv_posts . " (
				id bigint(20) NOT NULL,
				digiprove_this_post bool NOT NULL,
				this_all_original bool NOT NULL,
				attributions text CHARACTER SET utf8 COLLATE utf8_general_ci,
				using_default_license bool NOT NULL,
				license varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_caption varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_abstract text CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_url varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci,
				certificate_id varchar(12) CHARACTER SET ascii COLLATE ascii_general_ci,
				digital_fingerprint varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci,
				cert_utc_date_and_time varchar(40) CHARACTER SET ascii COLLATE ascii_general_ci,
				certificate_url varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci,
				first_year smallint,
				UNIQUE KEY id (id)
				);";

		//We need to include this file so we have access to the dbDelta function below (which is used to create the table)
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
	else
	{
		$log->lwrite("Table " . $dprv_posts . " exists already");  
	}
}


function record_dprv_licenses()
{
$log = new Logging();  
$licenseTypes = array(
						__("Read Only","dprv_cp"),
						__("Commercial","dprv_cp"),
						__("Attribution","dprv_cp"),
						__("Attribution, No derivative work","dprv_cp"),
						__("Non-commercial, Attribution, no Derivative work","dprv_cp"),
						__("Non-commercial, Attribution","dprv_cp"),
						__("Non-commercial, Attribution, Share Alike","dprv_cp"),
						__("Attribution, Share Alike","dprv_cp"),
						__("General Public License","dprv_cp"));

$licenseCaptions = Array(
							__("All Rights Reserved", "dprv_cp"),
							__("All Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"),
							__("Some Rights Reserved", "dprv_cp"));

$licenseAbstracts = Array(
							__("You may read the original content in the context in which it is published (at this web address). No other copying or use is permitted without written agreement from the author.","dprv_cp"),
							__("You may read the original content in the context in which it is published (at this web address). You may make other uses of the content only with the written permission of the author on payment of a fee.","dprv_cp"),
							__("You may copy this content, create derivative work from it, and re-publish it, provided you include an overt attribution to the author(s).","dprv_cp"),
							__("You may copy this content and re-publish it in unmodified form, provided you include an overt attribution to the author(s). You are not permitted to create derivative works.","dprv_cp"),
							__("You may copy this content, and re-publish it in unmodified form for non-commercial purposes, provided you include an overt attribution to the author(s). You are not permitted to create derivative works.","dprv_cp"),
							__("You may copy this content, create derivative work from it, and re-publish it for non-commercial purposes, provided you include an overt attribution to the author(s).","dprv_cp"),
							__("You may copy this content, create derivative work from it, and re-publish it for non-commercial purposes, provided you include an overt attribution to the author(s) and the re-publication must itself be under the terms of this license or similar.","dprv_cp"),
							__("You may copy this content, create derivative work from it, and re-publish it, provided you include an overt attribution to the author(s) and the re-publication must itself be under the terms of this license or similar.","dprv_cp"),
							__("The GNU General Public License is a free, copyleft license for software and other kinds of works. You may re-publish the content (modified or unmodified) providing the re-publication is itself under the terms of the GPL.","dprv_cp"));

$licenseURLs = array(
						"",
						"",
						"http://creativecommons.org/licenses/by/3.0/",
						"http://creativecommons.org/licenses/by-nd/3.0/",
						"http://creativecommons.org/licenses/by-nc-nd/3.0/",
						"http://creativecommons.org/licenses/by-nc/3.0/",
						"http://creativecommons.org/licenses/by-nc-sa/3.0/",
						"http://creativecommons.org/licenses/by-sa/3.0/",
						"http://www.gnu.org/licenses/gpl.html");

	global $table_prefix, $wpdb;
	// Create the 'name' of our table which is prefixed by the standard WP table prefix (which you specified when you installed WP)
	$dprv_licenses = $table_prefix . "dprv_licenses";

	for ($i=0; $i<count($licenseTypes); $i++)
	{
		$rows_affected = $wpdb->insert($dprv_licenses, array('license_type'=>$licenseTypes[$i], 'license_caption'=>$licenseCaptions[$i], 'license_abstract'=>$licenseAbstracts[$i], 'license_url'=>$licenseURLs[$i]));
	}
}
// END OF ON-ACTIVATION FUNCTIONS:


// DE-ACTIVATION FUNCTION:
function dprv_deactivate()
{
	$log = new Logging();  
	$log->lwrite("VERSION " . DPRV_VERSION . " DEACTIVATED");  
	//delete_option('dprv_last_result');	// keep other options for future install
}


// EVERY TIME WE START UP, DISPLAY REMINDER ABOUT CONFIGURATION IF NECESSARY
function dprv_init()
{
	if (get_option('dprv_enrolled') != "Yes")
	{
		function dprv_reminder()
		{
			echo "<div id='dprv_reminder' class='updated fade'><p><strong>".__("Copyright Proof is almost ready.", "dprv_cp")."</strong> ".__("You must <a href=\"options-general.php?page=copyright_proof_admin.php\"><b>Configure Copyright Proof and Register</b></a> to get it working</p></div>", "dprv_cp");
		}
		add_action('admin_notices', 'dprv_reminder');
	}
}

function populate_licenses()
{
	$log = new Logging();
	global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $table_prefix, $wpdb;
	$dbquery = 'SELECT * FROM ' . $table_prefix . 'dprv_licenses';
	//$wpdb->show_errors();
	$license_info = $wpdb->get_results($dbquery, ARRAY_A);
	if (empty($license_info))
	{
		$log->lwrite("license_info is empty");
	}
	else
	{
		$dprv_licenseIds = array(count($license_info));
		$dprv_licenseTypes = array(count($license_info));
		$dprv_licenseCaptions = array(count($license_info));
		$dprv_licenseAbstracts = Array(count($license_info));
		$dprv_licenseURLs = array(count($license_info));

		for ($i=0; $i<count($license_info); $i++)
		{
			$dprv_licenseIds[$i] = $license_info[$i]["id"];
			$dprv_licenseTypes[$i] = $license_info[$i]["license_type"];
			$dprv_licenseCaptions[$i] = $license_info[$i]["license_caption"];
			$dprv_licenseAbstracts[$i] = $license_info[$i]["license_abstract"];
			$dprv_licenseURLs[$i] = $license_info[$i]["license_url"];
		}
	}
}

function populate_licenses_js()
{
	global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs;
	// Pass variables into Javascript for later use:
	echo('<script type="text/javascript">
			//<![CDATA[
			');
	$licence_count = count($dprv_licenseIds);
	echo('
			var dprv_licenseIds = new Array(' . $licence_count . ');
			var dprv_licenseTypes = new Array(' . $licence_count . ');
			var dprv_licenseCaptions = new Array(' . $licence_count . ');
			var dprv_licenseAbstracts = new Array(' . $licence_count . ');
			var dprv_licenseURLs = new Array(' . $licence_count . ');
		');

	for ($i=0; $i<$licence_count; $i++)
	{
		echo('
			dprv_licenseIds[' . $i . '] = "' . $dprv_licenseIds[$i] .'";
			dprv_licenseTypes[' . $i . '] = "' . $dprv_licenseTypes[$i] .'";
			dprv_licenseCaptions[' . $i . '] = "' . $dprv_licenseCaptions[$i] .'";
			dprv_licenseAbstracts[' . $i . '] = "' . $dprv_licenseAbstracts[$i] .'";
			dprv_licenseURLs[' . $i . '] = "' . $dprv_licenseURLs[$i] .'";
			');
	}

	$dprv_default_licenseType = "";
	$dprv_license = get_option('dprv_license');
	for ($i=0; $i<count($dprv_licenseIds); $i++)
	{
		if ($dprv_licenseIds[$i] == $dprv_license)
		{
			$dprv_default_licenseType = $dprv_licenseTypes[$i];
			break;
		}
	}
	echo ('
			var dprv_default_licenseType = "' . $dprv_default_licenseType . '";
			var dprv_defaultLicenseId = "' . $dprv_license . '";
			//]]>
		</script>');
}

function createUpgradeLink()
{
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url[host];
	$dprv_wp_host = "";		// default

	$dprv_wp_url = parse_url(get_option('siteurl'));
	$dprv_wp_host = $dprv_wp_url[host];
	if (trim($dprv_blog_host) == "")
	{
		$dprv_blog_host = $dprv_wp_host;
	}
	$protocol = "http://";
	if (DPRV_SSL == "Yes")
	{
		$protocol = "https://";
	}
	$dprv_upgrade_link = get_settings('siteurl') . '/wp-content/plugins/digiproveblog/UpgradeRenew.html?FormAction=' . $protocol . DPRV_HOST . '/secure/upgrade.aspx&UserId='  . get_option('dprv_user_id') . '&ApiKey=' . get_option('dprv_api_key') . '&Domain=' . $dprv_blog_host . '&UserAgent=Copyright Proof ' . DPRV_VERSION;
	return $dprv_upgrade_link;
}

function dprv_post_sync($pid)
{
	global $table_prefix, $wpdb;
	$log = new Logging();
	//$wpdb->show_errors();
	$log->lwrite("post " . $pid . " deleted, checking for dprv_post record with same id");
	$sql='SELECT id FROM ' . $table_prefix . 'dprv_posts WHERE id = ' . $pid;
	if ($wpdb->get_var($wpdb->prepare($sql)))
	{
		$log->lwrite("found a dprv_post " . $pid . ", will now delete it");
		return $wpdb->query($wpdb->prepare('DELETE FROM ' . $table_prefix . 'dprv_posts WHERE id = %d', $pid));
		$log->lwrite("last query was " . $last_query);
	}
	return true;
}

class Logging
{
	// write message to the log file  
	function lwrite($message)
	{  
		if (DPRV_Log == "Yes")
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

?>