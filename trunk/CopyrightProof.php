<?php
/*
Plugin Name: Copyright Proof
Plugin URI: http://www.digiprove.com/copyright_proof_wordpress_plugin.aspx
Description: Digitally certify your posts to prove copyright ownership, generate copyright notice, and copy-protect text and images. 
Version: 2.09
Author: Digiprove
Author URI: http://www.digiprove.com/
License: GPL
*/
/*  Copyright 2008-2011  Digiprove (email : cian.kinsella@digiprove.com)
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

// Acknowledgement to Honza Odvarko, whose jscolor color-picker is used in this plug-in 
// under the GNU Lesser General Public License (LGPL): www.gnu.org/copyleft/lesser.html

// Acknowledgements to BTE, Akismet, and After the Deadline, some of whose code was used
// in the development of this plug-in
	include_once('copyright_proof_admin.php');						// Functions for Settings panel
	include_once('copyright_proof_edit.php');						// Functions for Creating and Editing content
	include_once('copyright_proof_live.php');						// Functions for Serving pages on web
	if (intval(substr(PHP_VERSION,0,1)) > 4)
	{
		include_once('copyright_proof_error.php');					// Functions for handling errors
	}
	include_once('Digiprove.php');									// Digiprove SDK functions

	// Declare and initialise global variables:
	define("DPRV_VERSION", "2.09");
	//error_reporting(E_ALL);						   // uncomment this for test purposes


	global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $dprv_post_id, $dprv_mime_types, $dprv_blog_host, $dprv_wp_host, $dprv_last_error;

	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = trim($dprv_blog_url['host']);
	$dprv_wp_url = parse_url(get_option('siteurl'));
	$dprv_wp_host = $dprv_wp_url['host'];
	if ($dprv_blog_host == "")
	{
		$dprv_blog_host = $dprv_wp_host;
	}

	// Define arrays of license information - populated in header sections
	$dprv_licenseIds = array();
	$dprv_licenseTypes = array();
	$dprv_licenseCaptions = array();
	$dprv_licenseAbstracts = array();
	$dprv_licenseURLs = array();

	$dprv_mime_types = array(	"All"=>array("All"),
								"Images"=>array("bmp","cgm","djv","djvu","gif","ico","ief","j2","jpe","jpeg","jpg","mac","pbm","pct","pgm","pic","pict","png","pnm","pnt","pntg","ppm","qti","qtif","ras","rgb","svg","tif","tiff","wbmp","xpm"),
								"Audio"=>array("aif","aifc","aiff","au","kar","m3u","m4a","m4b","m4p","mid","midi","mp2","mp3","mpga","mxu","ra","ram","wav"),
								"Video"=>array("avi","dif","dv","m4u","m4v","mov","movie","mp4","mpe","mpeg","mpg","qt","swf"),
								"Web Pages"=>array("","htm","html","php","asp","aspx"),
								"Documents"=>array("csv","doc","docx","odt","ods","odp","pdf","ppt","pptx","rtf","txt","xls","xlsx"),
								"Code"=>array("js","jar"));


	// Register hooks
	register_activation_hook(__FILE__, 'dprv_activate');
	register_deactivation_hook(__FILE__, 'dprv_deactivate');
	add_action('activated_plugin','dprv_activation_error');
	add_action('init', 'dprv_init');
	add_action('delete_post', 'dprv_post_sync', 10);

	add_action('admin_menu', 'dprv_settings_menu');
	add_action('admin_head', 'dprv_admin_head');
	//add_action('admin_footer', 'dprv_admin_footer');		// No longer used, new strategy for displaying messages

	//add_action('post_submitbox_misc_actions', 'dprv_verify_box');
	add_action('post_submitbox_start', 'dprv_add_digiprove_submit_button');
	add_filter('wp_insert_post_data', 'dprv_parse_post', 99, 2);
	add_filter('wp_insert_page_data', 'dprv_parse_post', 99, 2);
	add_action('wp_insert_post', 'dprv_digiprove_post');
	add_action('wp_insert_page', 'dprv_digiprove_post');
	add_filter('plugin_action_links', 'dprv_add_settings_link', 10, 2 );

	if (get_option('dprv_enrolled') == "Yes")
	{
		global $wp_version;
		if (intval($wp_version)>3)
		{
			add_action('add_meta_boxes', 'dprv_postbox');
		}
		else
		{
			add_action( 'admin_init', 'dprv_postbox', 1);
		}
	}

	add_action("wp_head", "dprv_head");
	add_filter( "the_content", "dprv_display_content" );
	add_action( "wp_footer", "dprv_footer" );


	if(!function_exists("strripos"))
	{
		function strripos($haystack, $needle, $offset = 0)
		{
			if(!is_string($needle))
			{
				$needle = chr(intval($needle));
			}
			if ($offset < 0)
			{
				$temp_cut = strrev(substr($haystack, 0, abs($offset)));
			}
			else
			{
				$temp_cut = strrev(substr($haystack, 0, max((strlen($haystack) - $offset),0)));
			}
			if(($found = stripos( $temp_cut, strrev($needle))) === FALSE)return FALSE;
			$pos = (strlen($haystack) - ($found + $offset + strlen( $needle)));
			return $pos;
		}
	}


	if (!function_exists("stripos"))
	{
		function stripos($haystack, $needle, $offset=0)
		{
			return strpos(strtoupper($haystack), strtoupper($needle), $offset);
		}
	}

	if (!function_exists("strpbrk"))
	{
		function strpbrk($haystack, $char_list)
		{
			$strlen = strlen($char_list);
			$found = false;
			for($i=0; $i<$strlen; $i++)
			{
				if(($tmp = strpos($haystack, $char_list{$i})) !== false )
				{
					if(!$found) 
					{
						$pos = $tmp;
						$found = true;
						continue;
					}
					$pos = min($pos, $tmp);
				}
			}
			if(!$found)
			{
				return false;
			}
			return substr($haystack, $pos);
		}
	}


	// FROM HERE DOWN IS ALL FUNCTIONS:

	// ON-ACTIVATION FUNCTIONS:
	function dprv_activation_error($plugin_name)
	{
		if (strpos($plugin_name, "CopyrightProof") !== false)
		{
			$dprv_last_error = trim(ob_get_contents());
			if ($dprv_last_error != "")
			{
				$dprv_last_error = "Error on activation: " . $dprv_last_error;
				dprv_record_event($dprv_last_error);
			}
		}
	}
	function dprv_activate()
	{
		// TODO: set error handler to dprvErrors if in existence for this function
		$log = new DPLog();  
		$log->lwrite("");
		$log->lwrite("VERSION " . DPRV_VERSION . " ACTIVATED");
		update_option('dprv_activated_version', DPRV_VERSION);	// If different to installed, activation steps will take place
		//add_option('dprv_verified_db_version', '');	            // If not up to date, will be checked and updated if necessary
		add_option('dprv_email_address', '');
		add_option('dprv_first_name', '');
		add_option('dprv_last_name', '');
		add_option('dprv_subscription_type', '');               // Will be empty until activation of membership
		add_option('dprv_subscription_expiry', '');
		add_option('dprv_content_type', '');
		add_option('dprv_notice', '');
		add_option('dprv_c_notice', 'DisplayAll');
		add_option('dprv_notice_size', '');
		add_option('dprv_license', '0');
		add_option('dprv_frustrate_copy', '');
		add_option('dprv_right_click_message', '');
		add_option('dprv_record_IP', '');                       // Not used (yet)
		add_option('dprv_notice_border', '');
		add_option('dprv_notice_background', '');
		add_option('dprv_notice_color', '');
		add_option('dprv_hover_color', '');
		add_option('dprv_obscure_url','Obscure');
		add_option('dprv_display_name','Yes');
		add_option('dprv_email_certs','No');
		add_option('dprv_linkback','Nolink');
		add_option('dprv_save_content','Nosave');
		add_option('dprv_post_types','post,page');
		$subscription_type = get_option('dprv_subscription_type');
		$dprv_html_tags = set_default_html_tags();
		if ($subscription_type == 'Basic' || $subscription_type == '')
		{
			foreach ($dprv_html_tags as $key=>$value)
			{
				//$log->lwrite("key=$key");
				//$log->lwrite("value=$value");
				$dprv_html_tags[$key]["selected"] = "False";
			}
		}
		add_option('dprv_html_tags',$dprv_html_tags);
		add_option('dprv_outside_media','NoOutside');
		delete_option('dprv_body_or_footer');		// Not used, discard
		add_option('dprv_footer', 'No');
		add_option('dprv_multi_post', 'Yes');
		add_option('dprv_enrolled', 'No');
		add_option('dprv_user_id', '');
		add_option('dprv_api_key', '');
		add_option('dprv_last_action', '');         // Is set to "Digiprove id=nnnn" at start of a Digiprove action (parse_post) if this is blank dprv_last_result will not be displayed as an admin message
		add_option('dprv_last_result', '');			// Contains result of Digiprove action
		add_option('dprv_pending_message', '');		// A message which has yet to be displayed in message box (usually same as dprv_last_result)
		add_option('dprv_last_date','');
		add_option('dprv_last_date_count','0');
		add_option('dprv_event', '');				// Place for error messages to be recorded, may be notified via API eventually
		add_option('dprv_html_integrity', 'No');	// Whether to check HTML data integrity
		add_option('dprv_files_integrity', 'No');	// Whether to check data integrity of embedded files
		delete_option('dprv_activation_event');		// No longer required
		add_option('dprv_prefix');
		create_dprv_license_table();
		create_dprv_post_table();
		create_dprv_post_content_files_table();
	}

	function set_default_html_tags()
	{
		$dprv_html_tags=array(	"a"=>array("name"=>"Anchor","selected"=>"True","incl_excl"=>"Include", "All"=>"False", "Images"=>"False", "Audio"=>"True", "Video"=>"True", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"False"),
								"img"=>array("name"=>"Images","selected"=>"True","incl_excl"=>"Include", "All"=>"True", "Images"=>"False", "Audio"=>"False", "Video"=>"False", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"False"), 
								"embed"=>array("name"=>"Embedded Media","selected"=>"False","incl_excl"=>"Include", "All"=>"False", "Images"=>"False", "Audio"=>"False", "Video"=>"True", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"False"),
								"applet"=>array("name"=>"Java Applets","selected"=>"False","incl_excl"=>"Include", "All"=>"False", "Images"=>"False", "Audio"=>"False", "Video"=>"False", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"True"), 
								"object"=>array("name"=>"Objects (various)","selected"=>"False","incl_excl"=>"Include", "All"=>"False", "Images"=>"False", "Audio"=>"False", "Video"=>"True", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"True"), 
								"iframe"=>array("name"=>"iFrame","selected"=>"False","incl_excl"=>"Exclude", "All"=>"False","Images"=>"False", "Audio"=>"False", "Video"=>"True", "Web Pages"=>"True", "Documents"=>"False", "Code"=>"True"), 
								"script"=>array("name"=>"Scripts & Code","selected"=>"False","incl_excl"=>"Include", "All"=>"False","Images"=>"False", "Audio"=>"False", "Video"=>"False", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"True"),
								"notag"=>array("name"=>"Elsewhere in HTML","selected"=>"True","incl_excl"=>"Include", "All"=>"False","Images"=>"True", "Audio"=>"True", "Video"=>"False", "Web Pages"=>"False", "Documents"=>"False", "Code"=>"False"));
		return $dprv_html_tags;
	}

	function create_dprv_license_table()
	{
		$log = new DPLog();  
		global $wpdb;

		// First, investigate and set dprv_prefix to allow for various situations such as user changes wp_prefix, dbDelta used to ignore caps in prefix
		$dprv_prefix = get_option('dprv_prefix');
		$dprv_licenses = $dprv_prefix . "dprv_licenses";
		
		if ($dprv_prefix === false)
		{
			// First-time activation of this plugin
			$dprv_prefix = $wpdb->prefix;
			update_option('dprv_prefix', $dprv_prefix);
		}
		else
		{
			$like_dprv_licenses = dprv_wpdb("get_var", "show tables like '$dprv_licenses'");
			$log->lwrite("like_dprv_licenses=$like_dprv_licenses");
			if ($like_dprv_licenses != $dprv_licenses)
			{
				$dprv_licenses = strtolower($dprv_licenses);
				$like_dprv_licenses = dprv_wpdb("get_var", "show tables like '$dprv_licenses'");
				if ($like_dprv_licenses == $dprv_licenses)
				{
					// Due to pre 3.4 db Delta bug, table name was created with all lower case prefix
					$dprv_prefix = strtolower($dprv_prefix);
					$log->lwrite("just set dprv_prefix to $dprv_prefix (lower case)");
				}
				else
				{
					$dprv_prefix = $wpdb->prefix;
					$log->lwrite("just set dprv_prefix to $dprv_prefix");
				}
				update_option('dprv_prefix', $dprv_prefix);
			}
		}

		// Now check to see if the table exists already, if not, then create it
		$dprv_licenses = $dprv_prefix . "dprv_licenses";
		if(dprv_wpdb("get_var", "show tables like '$dprv_licenses'") != $dprv_licenses)
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
			$dprv_sql_error = mysql_error();				
			$dprv_db_error = $wpdb->last_error;
			$log->lwrite("just created (if necessary) license table " . $dprv_licenses . "; error text = " . $dprv_db_error);

			$like_dprv_licenses = dprv_wpdb("get_var", "show tables like '$dprv_licenses'");
			$log->lwrite("like_dprv_licenses=$like_dprv_licenses");
			if ($like_dprv_licenses != $dprv_licenses)
			{
				// Check if created at lower case (would be if pre 3.4 version of WP)
				$like_dprv_licenses = dprv_wpdb("get_var", "show tables like '" . strtolower($dprv_licenses) . "'");
				if ($like_dprv_licenses != strtolower($dprv_licenses))
				{
					$message = "Failure to create table " . $dprv_licenses;
					if (trim($dprv_db_error) != "")
					{
						$message .= " with error " .  $dprv_db_error;
					}
					if (trim($dprv_sql_error) != "")
					{
						$message .= " sql error " .  $dprv_sql_error;
					}
					$temp = sprintf(__("Failed to create db table %s"), $dprv_licenses);
					if (trim($dprv_db_error) != "")
					{
						$temp .= " <a href='javascript:alert(\"" . __("Details:") . " $dprv_db_error" . "\")'>More</a>";
					}
					$log->lwrite($temp);
					update_option("dprv_pending_message", $temp); 
					dprv_record_event($message);
					return false;
				}
				else
				{
					// Looks like dbDelta just created table using lower-case version of prefix, modify dprv_prefix accordingly
					$dprv_prefix = strtolower($dprv_prefix);
					$log->lwrite("dbDelta converted prefix to lower case, just set dprv_prefix to $dprv_prefix");
					update_option('dprv_prefix', $dprv_prefix);
				}
			}
			record_dprv_licenses();
		}
		else
		{
			$log->lwrite("Table " . $dprv_licenses . " exists already");  
		}
		return true;
	}

	function create_dprv_post_table()
	{
		$log = new DPLog();  
		global $wpdb;
		$dprv_prefix = get_option('dprv_prefix');
		$dprv_posts = $dprv_prefix . "dprv_posts";
		$sql =	"CREATE TABLE " . $dprv_posts . " (
				id bigint(20) NOT NULL,
				digiprove_this_post bool NOT NULL,
				this_all_original bool NOT NULL,
				attributions text CHARACTER SET utf8 COLLATE utf8_general_ci,
				using_default_license bool NOT NULL,
				license varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_caption varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_abstract text CHARACTER SET utf8 COLLATE utf8_general_ci,
				custom_license_url varchar(255),
				certificate_id varchar(12),
				digital_fingerprint varchar(64),
				cert_utc_date_and_time varchar(40),
				certificate_url varchar(255),
				first_year smallint,
				last_time_digiproved int,
				last_fingerprint varchar(64),
				last_time_updated int,
				UNIQUE KEY id (id)
				);";

		//We need to include this file so we have access to the dbDelta function below (which is used to create the table)
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);

		// TODO: statements below may be pointless and do not capture dbDelta info.  Test to determine facts
		$dprv_sql_error = mysql_error();				
		$dprv_sql_info = mysql_info();
		$dprv_db_error = $wpdb->last_error;

		$log->lwrite ("Executed create table " . $dprv_posts . ", last_error " . $dprv_db_error);

		$like_dprv_posts = dprv_wpdb("get_var", "show tables like '$dprv_posts'");
		if ($like_dprv_posts !== false && $like_dprv_posts !== $dprv_posts)
		{
			$message = "Failure to create table " . $dprv_posts . "!=(" . $like_dprv_posts . ") with error " .  $dprv_db_error . ", mysql_error " . $dprv_sql_error;
			dprv_record_event($message);
		}
		else
		{
			$like_last_fingerprint = dprv_wpdb("get_var","show columns from $dprv_posts LIKE 'last_fingerprint'");
			if ($like_last_fingerprint != 'last_fingerprint')														// Table existed, but dbDelta failed to add new columns
			{
				$message = "dbDelta did not add last_fingerprint column  (= " . $like_last_fingerprint . ") to " . $dprv_posts;
				if ($dprv_db_error != "")
				{
					$message .= " with error " .  $dprv_db_error;
				}
				if ($dprv_sql_error != "")
				{
					$message .= ", mysql_error " .  $dprv_sql_error;
				}
				if ($dprv_sql_info != "")
				{
					$message .= ", mysql_info " .  $dprv_sql_info;
				}
				
				$message .= ", will try adding with wpdb";
				dprv_record_event($message);
				// TODO: diagnose why adding a varchar column to an existing table defaults to latin but in Create table defaults to utf-8
				dprv_add_column($dprv_posts, 'last_time_digiproved', 'int');
				dprv_add_column($dprv_posts, 'last_fingerprint', 'varchar(64)');
				dprv_add_column($dprv_posts, 'last_time_updated', 'int');
/*
				// Not yet implemented:
				$result1 = dprv_add_column($dprv_posts, 'last_time_digiproved', 'int');
				if (strpos($result1, "Duplicate column name") !== false)
				{
					update_option('dprv_verified_db_version', '2');
				}
				else
				{
					$result2 = dprv_add_column($dprv_posts, 'last_fingerprint', 'varchar(64)');
					$result3 = dprv_add_column($dprv_posts, 'last_time_updated', 'int');
					if ($result1 === true && $result2 === true && $result3 === true)
					{
						update_option('dprv_verified_db_version', '2');
					}
				}
*/
				$like_last_fingerprint = dprv_wpdb("get_var","SHOW COLUMNS FROM $dprv_posts like 'last_fingerprint'");
				if ($like_last_fingerprint != 'last_fingerprint')															// If still does not exist
				{
					$message = "Failed to add last_fingerprint column to " . $dprv_posts;
					dprv_record_event($message);
				}
			}
		}
	}

	function create_dprv_post_content_files_table()
	{
		$log = new DPLog();
		global $wpdb;
		$dprv_prefix = get_option('dprv_prefix');
		$dprv_post_content_files = $dprv_prefix . "dprv_post_content_files";

		// Check to see if the table exists already, if not, then create it
		//if($wpdb->get_var("show tables like '$dprv_post_content_files'") != $dprv_post_content_files)
		if(dprv_wpdb("get_var", "show tables like '$dprv_post_content_files'") != $dprv_post_content_files)
		{
			$log->lwrite("creating table " . $dprv_post_content_files);
			$sql = "CREATE TABLE " . $dprv_post_content_files . " (
					post_id bigint(20) NOT NULL, INDEX (post_id),
					filename varchar(280) CHARACTER SET utf8 COLLATE utf8_general_ci,
					digital_fingerprint varchar(64)
					);";
			//We need to include this file so we have access to the dbDelta function below (which is used to create the table)
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sql);
			$dprv_db_error =$wpdb->last_error;
			$log->lwrite("just created posts table " . $dprv_post_content_files . "; error text = " . $dprv_db_error);
			//if($wpdb->get_var("show tables like '$dprv_post_content_files'") != $dprv_post_content_files)
			if(dprv_wpdb("get_var", "show tables like '$dprv_post_content_files'") != $dprv_post_content_files)
			{
				$message = "Failure to create table " . $dprv_post_content_files . " with error " .  $dprv_db_error;
				dprv_record_event($message);
			}
		}
		else
		{
			$log->lwrite("Table " . $dprv_post_content_files . " exists already");  
		}
	}

	function dprv_add_column($table, $column_name, $qualifier)
	{				
		$log = new DPLog();
		global $wpdb;
		$dprv_db_last_error = $wpdb->last_error;
		if ($wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$column_name'") != $column_name)
		{
			$sql = "ALTER TABLE " . $table . " ADD COLUMN $column_name $qualifier";
			$wpdb->query($sql);
			$dprv_db_error = $wpdb->last_error;
			//$log->lwrite("add col $column_name last error $dprv_db_error");
			$message = "";
			if ($wpdb->get_var("SHOW COLUMNS FROM $table LIKE '$column_name'") != $column_name)
			{
				$message .= "Did not add column $column_name to $table, last db error $dprv_db_error";
				dprv_record_event($message);
				return false;
			}
		}
		return true;
	}

	function record_dprv_licenses()
	{
	$log = new DPLog();  
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

	$licenseCaptions = array(
								__("All Rights Reserved", "dprv_cp"),
								__("All Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"),
								__("Some Rights Reserved", "dprv_cp"));

	$licenseAbstracts = array(
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

		global $wpdb;
		$dprv_licenses = get_option('dprv_prefix') . "dprv_licenses";

		for ($i=0; $i<count($licenseTypes); $i++)
		{
			$rows_affected = $wpdb->insert($dprv_licenses, array('license_type'=>$licenseTypes[$i], 'license_caption'=>$licenseCaptions[$i], 'license_abstract'=>$licenseAbstracts[$i], 'license_url'=>$licenseURLs[$i]));
		}
	}
	// END OF ON-ACTIVATION FUNCTIONS:


	// DE-ACTIVATION FUNCTION:
	function dprv_deactivate()
	{
		$log = new DPLog();  
		$log->lwrite("VERSION " . DPRV_VERSION . " DEACTIVATED");  
	}


	// EVERY TIME WE START UP, CHECK IF ACTIVATION NEEDS TO BE DONE, CHECK FOR DB PREFIX CHANGE AND DISPLAY REMINDER ABOUT CONFIGURATION IF NECESSARY
	function dprv_init()
	{
		$log = new DPLog();  
		if (get_option('dprv_activated_version') !== DPRV_VERSION)
		{
			$log->lwrite("change of plugin version detected, calling activation");
			dprv_activate();
		}
		// Load language file if applicable
		$plugin_dir = basename(dirname(__FILE__)) . '/languages/';
		load_plugin_textdomain( 'dprv_cp', false, $plugin_dir );

		// Check if db prefix changed and adjust if need be
		global $wpdb;
		$dprv_posts = get_option('dprv_prefix') . "dprv_posts";
		if (get_option('dprv_prefix') !== false && get_option('dprv_prefix') != $wpdb->prefix  && get_option('dprv_prefix') != strtolower($wpdb->prefix))
		{
			//if($wpdb->get_var("show tables like '$dprv_posts'") != $dprv_posts)
			if(dprv_wpdb("get_var", "show tables like '$dprv_posts'") != $dprv_posts)
			{
				$dprv_posts = $wpdb->prefix . "dprv_posts";

				//if($wpdb->get_var("show tables like '$dprv_posts'") == $dprv_posts)
				if(dprv_wpdb("get_var", "show tables like '$dprv_posts'") == $dprv_posts)
				{
					// User has changed db prefix of dprv tables (assume all), adjust dprv_prefix accordingly 
					$dprv_this_event = "found table for wpdb-prefix(" . $wpdb->prefix . ") but not dprv_prefix(" . get_option('dprv_prefix') . "), updating dprv_prefix"; 
					dprv_record_event($dprv_this_event);
					update_option('dprv_prefix', $wpdb->prefix);
				}
				else
				{
					$dprv_this_event = "could not find table for wpdb-prefix(" . $wpdb->prefix . ") or dprv_prefix(" . get_option('dprv_prefix') . "), re-doing activation"; 
					dprv_record_event($dprv_this_event);
					dprv_activate();
				}
			}
			else
			{
				// User has changed db prefix but has not changed prefix on dprv tables, that's ok (user might do it later)
				// record event found table for dprv_prefix (dprv_prefix) even though different to wpdb->prefix
			}
		}
		// TODO: Only perform this check if last_fingerprint may be required, e.g. edit or display posts/pages
		// Check whether version 2.n db upgrade has been done successfully:
		$like_last_fingerprint = dprv_wpdb("get_var", "SHOW COLUMNS FROM $dprv_posts LIKE 'last_fingerprint'");
		$log->lwrite ("like_last_fingerprint=" . $like_last_fingerprint);
		if (($like_last_fingerprint === null || $like_last_fingerprint != 'last_fingerprint') && current_user_can("activate_plugins"))
		{
			$more_eval = "";
			if (is_null($like_last_fingerprint))
			{
				$more_eval = " (like_last_fingerprint is null) ";
			}
			else
			{
				if (!is_string($like_last_fingerprint))
				{
					//$more_eval = dprv_eval($like_last_fingerprint);
					$more_eval =  " (like_last_fingerprint is not a string) ";
				}
			}
			$dprv_this_event = "last_fingerprint does not exist in " . $dprv_posts . "(=" . $like_last_fingerprint . ")" . $more_eval . ", repeating activation";
			dprv_record_event($dprv_this_event);
			dprv_activate();
		}

		function dprv_reminder()
		{
			$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
			$posDot = strrpos($script_name,'.');
			if ($posDot != false)
			{
				$script_name = substr($script_name, 0, $posDot);
			}

			if ($script_name != "options-general" || strpos($_SERVER['QUERY_STRING'], "copyright_proof_admin.php") === false)
			{
				echo "<div id='dprv_reminder' class='updated fade'><p>" . DPRV_REMINDER . "</p></div>";
			}
		}

		if (get_option('dprv_enrolled') != "Yes")
		{
			define("DPRV_REMINDER", "<strong>".__("Copyright Proof is almost ready.", "dprv_cp")."</strong> <span style=\"color:red\">".sprintf(__('You must %1$s Configure Copyright Proof and Register %2$s to get it working', 'dprv_cp'), '<a href="options-general.php?page=copyright_proof_admin.php"><b>', '</b></a>') . "</span>");
			add_action('admin_notices', 'dprv_reminder');
			return;
		}
		if (trim(get_option('dprv_api_key')) == '')
		{	
			define("DPRV_REMINDER", "<strong>".__("Copyright Proof needs configuration.", "dprv_cp")."</strong> ".sprintf(__('Please %s obtain a new api key %s to get it working', 'dprv_cp'), '<a href="options-general.php?page=copyright_proof_admin.php"><b>', '</b></a>'));
			add_action('admin_notices', 'dprv_reminder');
			return;
		}
		$dprv_pending_message = trim(get_option('dprv_pending_message'));
		if ($dprv_pending_message != '')
		{	
			define("DPRV_REMINDER", "<strong>" . $dprv_pending_message . "</strong>");
			add_action('admin_notices', 'dprv_reminder');
			update_option('dprv_pending_message', '');
			return;
		}
	}

	function dprv_populate_licenses()
	{
		$log = new DPLog();
		global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $wpdb;
		$dbquery = 'SELECT * FROM ' . get_option('dprv_prefix') . 'dprv_licenses';
		//$dbquery = 'SELECT * FROM ' . $wpdb->prefix . 'dprv_licenses';
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
			$dprv_licenseAbstracts = array(count($license_info));
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

	function dprv_populate_licenses_js()
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

	function dprv_createUpgradeLink()
	{
		global $dprv_blog_host;
		//$protocol = "http://";
		//if (DPRV_SSL == "Yes")
		//{
			$protocol = "https://";
		//}
		$dprv_upgrade_link = WP_PLUGIN_URL . '/digiproveblog/UpgradeRenew.html?FormAction=' . $protocol . DPRV_HOST . '/secure/upgrade.aspx&amp;UserId='  . get_option('dprv_user_id') . '&amp;ApiKey=' . get_option('dprv_api_key') . '&amp;Domain=' . $dprv_blog_host . '&amp;UserAgent=Copyright Proof ' . DPRV_VERSION;
		return $dprv_upgrade_link;
	}

	function dprv_post_sync($pid)
	{
		global $wpdb;

		$log = new DPLog();
		$log->lwrite("post " . $pid . " deleted, checking for dprv_post record with same id");
		$sql='SELECT id FROM ' . get_option('dprv_prefix') . 'dprv_posts WHERE id = ' . $pid;
		if ($wpdb->get_var($wpdb->prepare($sql)))
		{
			$log->lwrite("found a dprv_post " . $pid . ", will now delete it"); 
			return $wpdb->query($wpdb->prepare('DELETE FROM ' . get_option('dprv_prefix') . 'dprv_posts WHERE id = %d', $pid));
		}
		return true;
	}

	function dprv_add_settings_link($links, $file)
	{
		static $this_plugin;
		if (!$this_plugin)
		{
			$this_plugin = plugin_basename(__FILE__);
		}
		if ($file == $this_plugin)
		{
			$settings_link = '<a href="options-general.php?page=copyright_proof_admin.php">'.__("Settings", "dprv_cp").'</a>';
			array_push($links, $settings_link);
		}
		return $links;
	}
	function dprv_wpdb($action, $sql)
	{
		$log = new DPLog();
		global $wpdb;
		$sql = $wpdb->prepare($sql);
		$result = "29 jump street";	// just to establish variable scope
		$prev_db_error = $wpdb->last_error;
		switch ($action)
		{
			case "get_var":
			{
				$result = $wpdb->get_var($sql);
				break;
			}
			case "get_row":
			{
				$result = $wpdb->get_row($sql, ARRAY_A);
				break;
			}
			default:
			{
				return false;
			}
		}
		$dprv_db_error = $wpdb->last_error;
		$dprv_sql_error = mysql_error();
		if (trim($dprv_db_error) != "")
		{
			$bt = debug_backtrace();
			$error_status = "";
			if ($prev_db_error == $dprv_db_error)
			{
				$error_status = "suspected ";
			}
			$dprv_this_event = $error_status . "wpdb SQL error " . $dprv_db_error . " on " . $sql;
			if (trim($dprv_sql_error) != "" && $dprv_sql_error != $dprv_db_error)
			{
				$dprv_this_event .= "; MySQL error " . $dprv_sql_error;
			}
			$more_eval = "";
			if (is_null($result))
			{
				$more_eval = " (result is null)";
			}
			else
			{
				if (!is_string($result))
				{
					$more_eval = " (result not string = " . $result . ")";
				}
				else
				{
					$more_eval = " (result is $result)";
				}
			}
			$dprv_this_event .= $more_eval;
			$counter = 0;
			if (is_array($bt))
			{
				foreach ($bt as $caller)
				{
					if (is_array($caller))
					{
						if (isset($caller["file"]))
						{
							$dprv_this_event .= "called from " . str_replace(ABSPATH, "", $caller["file"]) . " line " . $caller["line"] . "\r\n";
						}
						else
						{
							$dprv_this_event .= "function " . $caller["function"] . "\r\n";
						}
					}
					$counter++;
					if ($counter > 4)
					{
						break;
					}
				}
			}
			dprv_record_event($dprv_this_event);
			$log->lwrite("mysql_info=" . mysql_info());
		}
		return $result;
	}

	function dprv_record_event(&$dprv_this_event, $dprv_event = null)	// Record some event for error reporting purposes (will eventually show up on server log at next successful api transaction)
	{
		$log = new DPLog();
		$log->lwrite($dprv_this_event);
		if ($dprv_event == null)
		{
			$dprv_event = get_option('dprv_event');
		}
		if ($dprv_event != "")
		{
			$dprv_event .= "; ";
		}
		$dprv_this_event =  date("Y/m/d-H:i O") . ': ' . $dprv_this_event;
		$dprv_event .= $dprv_this_event;
		$event_length = strlen($dprv_event);
		if ($event_length > 16000) //  Placing a limit on size
		{
			$prefix = ".";
			for ($pos=0; $pos<$event_length; $pos++)
			{
				if (substr($dprv_event, $pos, 1) == ".")
				{
					$prefix .= ".";
				}
				else
				{
					break;
				}
			}
			$dprv_event = substr($dprv_event, $pos);
			$event_length = strlen($dprv_event);
			if ($event_length > 16000)	// still over the top?
			{
				$dprv_event = $prefix . substr($dprv_event, $event_length - 15999);
			}
			else
			{
				$dprv_event = $prefix . $dprv_event;
			}
		}
		update_option('dprv_event', $dprv_event);
		return $dprv_event;
	}

	function dprv_unrecord_event($dprv_this_event, $dprv_event = null)
	{
		if ($dprv_event == null)
		{
			$dprv_event = get_option('dprv_event');
		}
		$dprv_event = str_replace('; ' . $dprv_this_event, '', $dprv_event);
		$dprv_event = trim(str_replace($dprv_this_event, '', $dprv_event));
		update_option('dprv_event', $dprv_event);
		return $dprv_event;
	}
	function dprv_dbDelta($sql)
	{



	}
function dprv_eval($something, $html = false, $tabs = "")
{
	if (is_null($something))
	{
		return "NULL; ";
	}
	$log = new DPLog();
	$line = "\r\n";
	$tab = "\t";
	$apos = "'";
	$arrow = "=> ";
	if ($html)
	{
		$line = "<br/>";
		$tab = "&nbsp;&nbsp;&nbsp;&nbsp;";
		$apos = "&#039;";
		$arrow = "=&gt;&nbsp;";
	}
	$return = "";
	if (is_object($something))
	{
		$called_class = "";
		if (function_exists("get_called_class"))
		{
			$called_class = get_called_class($something);
			if ($called_class === false)
			{
				$called_class = ", called from outside a class";
			}
			else
			{
				$called_class = ", called in class " . $called_class;
			}
		}
		$return .= "(object) of " . count($something) . " properties, parent class is " . get_parent_class($something) . ", class is " . get_class($something) . $called_class . "; ";
		$return .= "array of class methods is " . dprv_eval(get_class_methods($something), $html, $tabs);
		$return .= "array of class variables is " . dprv_eval(get_class_vars(get_class($something)), $html, $tabs);
		$return .= "array of object variables is " . dprv_eval(get_object_vars($something), $html, $tabs);
	}
	if (is_array($something))
	{
		$return .= "(array) [" . count($something) . "]";
		if (count($something) > 0)
		{
			$return .= ": " . $line . $tabs . "{" . $line;
			foreach ($something as $a_key => $a_value)
			{
				$return .= $tabs . $tab . $apos . $a_key . $apos . $arrow . dprv_eval($a_value, $html, $tabs . $tab) . $line;
			}
			$return .= $tabs . "}" . $line;
		}
	}
	if (is_bool($something))
	{
		$return .= "(bool)";
		if ($something == true)
		{
			$return .= " true; ";
		}
		else
		{
			$return .= " false; ";
		}
	}
	if (is_callable($something))
	{
		$return .= "is callable; ";
	}
	if (is_double($something))
	{
		$return .= "(double)" . "; ";
	}
	if (is_float($something))
	{
		$return .= "(float)" . "; ";
	}
	if (is_int($something))
	{
		$return .= "(int) " . $something . "; ";
	}
	if (is_integer($something))
	{
		$return .= "(integer) " . $something . "; ";
	}
	if (is_long($something))
	{
		$return .= "(long)" . "; ";
	}
	if (is_numeric($something))
	{
		$return .= ", is numeric; ";
	}
	if (is_real($something))
	{
		$return .= ", is real; ";
	}
	if (is_resource($something))
	{
		$return .= ", is resource; ";
	}
	if (is_string($something))
	{
		$return .= "string (" . strlen($something) . ")";
		if (strlen($something) > 0)
		{
			$return .= ": ";
			if (strlen($something) > 200)
			{
				$return .= " begins with " . substr($something, 0, 150) . "; ";
			}
			else
			{
				$return .= $something . "; ";
			}
		}
	}
	else
	{
		if (is_scalar($something))
		{
			$return .= ", is scalar; ";
		}
	}
	return $return;
}

?>