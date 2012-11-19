<?php
function dprv_settings_menu()	// Runs after the basic admin panel menu structure is in place - add Copyright Proof Settings option.
{	
	$pagename = add_options_page('DigiproveBlog', 'Copyright Proof', 10, basename(__FILE__), 'dprv_settings');
}

function dprv_admin_head()	// runs between <HEAD> tags of admin settings page - include js file
{
	global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs;
	$log = new DPLog();  
	$log->lwrite("dprv_admin_head starts");

	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}

	if ($script_name != "post" && $script_name != "page" && $script_name != "post-new" && $script_name != "page-new" && ($script_name != "options-general" || strpos($_SERVER['QUERY_STRING'], "copyright_proof_admin.php") === false))
	{
		$log->lwrite("dprv_admin_head returning early, no need for license or other info");
		return;
	}
	dprv_populate_licenses();
	dprv_populate_licenses_js();
}

/*
function dprv_admin_footer()	// runs in admin panel inside body tags - previously used to add Digiprove message to message bar
{
	$log = new DPLog();  
	$log->lwrite("dprv_admin_footer starts");
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}
}
*/


function dprv_settings()		// Run when Digiprove selected from Settings menu
{		
	global $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $wpdb, $dprv_mime_types;
	$log = new DPLog();  
	$log->lwrite("dprv_settings starting");

	$message = "";
	$result_message="";

	// Populate variables and record default values if necessary
	$dprv_subscription_type = get_option('dprv_subscription_type');
	$dprv_subscription_expiry = get_option('dprv_subscription_expiry');

	// STUFF FOR BASIC TAB (PERSONAL DETAILS):
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
		$dprv_last_name = $user_info->last_name;
	}

	$dprv_display_name = get_option('dprv_display_name');
	if ($dprv_display_name == false)
	{
		$dprv_display_name = 'Yes';
	}
	
	$dprv_email_certs = get_option('dprv_email_certs');
	if ($dprv_email_certs == false || $dprv_subscription_type == "Basic" || empty($dprv_subscription_type))
	{
		$dprv_email_certs = 'No';
	}

	// STUFF FOR BASIC TAB (REGISTRATION DETAILS):
	$dprv_enrolled = get_option('dprv_enrolled');
	$dprv_register_option = "No";
	if ($dprv_enrolled == false)
	{
		$dprv_enrolled = 'No';
	}
	if ($dprv_enrolled == "No")
	{
		$dprv_register_option = "Yes";
	}

	$dprv_user_id = get_option('dprv_user_id');
	if ($dprv_user_id == false && strlen($dprv_email_address) < 41)
	{
		$dprv_user_id = $dprv_email_address;
	}
	$dprv_api_key = get_option('dprv_api_key');
	$dprv_password = get_option('dprv_password');			// This is retained simply to know if a password is still on record (affects some help text)
	// TODO - Check if the variable below is required
	$dprv_pw_confirm = $dprv_password;
	$dprv_can_contact = true;

	// STUFF FOR ADVANCED TAB:
	$dprv_content_type = get_option('dprv_content_type');
	if ($dprv_content_type == false)
	{
		$dprv_content_type = __("Blog post", "dprv_cp");
	}
	$dprv_notice = get_option('dprv_notice');
	if ($dprv_notice == false)
	{
		$dprv_notice = __('Copyright secured by Digiprove', 'dprv_cp');
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
	if ($dprv_linkback == false || $dprv_subscription_type == "Basic")
	{
		$dprv_linkback = 'Nolink';
	}
	$dprv_save_content = get_option('dprv_save_content');
	if ($dprv_save_content == false || $dprv_subscription_type == "Basic" ||  $dprv_subscription_type == "Personal")
	{
		$dprv_save_content = 'Nosave';
	}

	$dprv_footer = get_option('dprv_footer');
	if ($dprv_footer == false)
	{
		$dprv__footer = 'No';
	}

	$dprv_multi_post = get_option('dprv_multi_post');
	if ($dprv_multi_post == false)
	{
		$dprv_multi_post = 'Yes';
	}


	// STUFF FOR CONTENT TAB:
	$dprv_post_types = get_option('dprv_post_types');
	if ($dprv_post_types == false)
	{
		$dprv_post_types = 'post,page';
	}

	$dprv_html_tags = get_option('dprv_html_tags');
	if (!is_array($dprv_html_tags))
	{
		$log->lwrite("dprv_html_tags not an array");
		$dprv_html_tags = false;
	}

	$dprv_outside_media = get_option('dprv_outside_media');
	if ($dprv_outside_media == false || $dprv_subscription_type == "Basic")
	{
		$dprv_outside_media = 'NoOutside';
	}


	// STUFF FOR LICENSE TAB:
	$dprv_license = get_option('dprv_license');
	if ($dprv_license == false || $dprv_license == '')
	{
		$dprv_license = 0;  //$dprv_licenseTypes[0];
	}
	$dprv_custom_license_caption = "";			// Just a default value
	$dprv_custom_license_abstract = "";			// Just a default value
	$dprv_custom_license_url = "";				// Just a default value



	// STUFF FOR DATA INTEGRITY TAB:
	$dprv_html_integrity = get_option('dprv_html_integrity');
	if ($dprv_html_integrity == false)
	{
		$dprv_html_integrity = 'No';
	}
	$dprv_files_integrity = get_option('dprv_files_integrity');
	if ($dprv_files_integrity == false)
	{
		$dprv_files_integrity = 'No';
	}
	
	// STUFF FOR COPY_PROTECT TAB:
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

	$dprv_last_result = get_option('dprv_last_result');
	// FINISHED SETTING DEFAULT SETTINGS

	$registration_error = false;

	if (!empty($_POST['dprv_cp_action']))		// Is POSTBACK, do necessary validation and take action
	{
		$log->lwrite("dprv_settings Postback");

		// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
		if(!isset($HTTP_POST_VARS) && isset($_POST))
		{
			$HTTP_POST_VARS = $_POST;
		}

		$dprv_action = $_POST['dprv_action'];
		$dprv_renew_api_key = "off";					
		if (isset($_POST['dprv_renew_api_key']) && $_POST['dprv_renew_api_key'] == "on")
		{
			$dprv_renew_api_key = $_POST['dprv_renew_api_key'];
		}

		$log->lwrite("dprv_action=".$dprv_action);
		//$result_message = "";
		$message = "";
		$dprv_custom_license = $_POST['dprv_custom_license'];
		$dprv_custom_license_caption = $_POST['dprv_custom_license_caption'];
		$dprv_custom_license_abstract = $_POST['dprv_custom_license_abstract'];
		$dprv_custom_license_url = $_POST['dprv_custom_license_url'];
		switch ($dprv_action)
		{
			case "ResendEmail":
			{
				$log->lwrite("about to call dprv_resend_activation_email");
				$dprv_resend_response = dprv_resend_activation_email($dprv_user_id, $dprv_email_address);
				$pos = stripos($dprv_resend_response, "<result_code>0");
				if ($pos === false)
				{
					$failure_message = dprv_getTag($dprv_resend_response,"result");
					if ($failure_message == false)
					{
						$failure_message = $dprv_resend_response;
					}
					$result_message = __('Activation email was not resent:', 'dprv_cp') . ' '  . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
					$log->lwrite("Activation email re-send failed, response:");
					$log->lwrite($dprv_resend_response);
				}
				else
				{
					$result_message = dprv_getTag($dprv_resend_response,"result");
					if ($result_message == false)
					{
						$result_message = __('Sorry, there was a problem in resending the activation email', 'dprv_cp');
					}
				}

				update_option('dprv_last_result', $result_message);
				$dprv_last_result = $result_message;

				break;
			}
			case "SyncUser":
			{
				$log->lwrite("about to call dprv_sync_user");
				$dprv_sync_response = dprv_sync_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_renew_api_key);

				$pos = stripos($dprv_sync_response, "<result_code>0");
				if ($pos === false)
				{
					$failure_message = dprv_getTag($dprv_sync_response,"result");
					if ($failure_message == false)
					{
						$failure_message = $dprv_sync_response;
					}
					$result_message = __('Did not refresh subscription data:', 'dprv_cp') . ' '  . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
					$log->lwrite("Sync user failed, response:");
					$log->lwrite($dprv_sync_response);
				}
				else
				{
					$result_message = __("Subscription information refreshed OK", "dprv");
					$dprv_subscription_type = dprv_getTag($dprv_sync_response, "subscription_type");
					if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
					{
						update_option('dprv_subscription_type', $dprv_subscription_type);
						$dprv_subscription_expiry = dprv_getTag($dprv_sync_response, "subscription_expiry");
						if ($dprv_subscription_expiry != null && $dprv_subscription_expiry != false && $dprv_subscription_expiry != "")
						{
							update_option('dprv_subscription_expiry', $dprv_subscription_expiry);
						}
						else
						{
							update_option('dprv_subscription_expiry', '');
						}
					}
				}

				update_option('dprv_last_result', $result_message);
				$dprv_last_result = $result_message;
				break;
			}

			case "ClearHTMLTags":
			{
				$log->lwrite("about to clear dprv_html_tags");
				$dprv_html_tags = set_default_html_tags();
				foreach ($dprv_html_tags as $key=>$value)
				{
					$log->lwrite("key=$key");
					$log->lwrite("value=$value");
					$dprv_html_tags[$key]["selected"] = "False";
				}

				$result_message = __('Content settings cleared', 'dprv_cp');
				break;
			}
			case "DefaultHTMLTags":
			{
				$log->lwrite("about to reset dprv_html_tags to default values");
				$dprv_html_tags = set_default_html_tags();
				$result_message = __('Content settings reset to default', 'dprv_cp');
				break;
			}

			case "UpdateLicense":
			{
				$log->lwrite("about to update license " . $_POST['dprv_license']); 
				$dbquery = 'SELECT * FROM ' . get_option('dprv_prefix') . 'dprv_licenses WHERE id = ' .  $_POST['dprv_license'];
				$license_info = $wpdb->get_row($dbquery, ARRAY_A);
				if (!empty ($license_info))
				{
					$license_info["license_type"] = $dprv_custom_license;
					$license_info["license_caption"] = $dprv_custom_license_caption;
					$license_info["license_abstract"] = $dprv_custom_license_abstract;
					$license_info["license_url"] = $dprv_custom_license_url;
					$wpdb->update(get_option('dprv_prefix') . 'dprv_licenses', $license_info, array('id'=>$_POST['dprv_license']));
					dprv_populate_licenses();			// Rebuild dprv_license table in php
					dprv_populate_licenses_js();			// and in javascript
				}
				$dprv_license = get_option('dprv_license');
				$result_message = __('License updated', 'dprv_cp');
				break;
			}
			case "AddLicense":
			{
				$log->lwrite("about to add license " . $dprv_custom_license);

				$dprv_licenses = get_option('dprv_prefix') . "dprv_licenses";
				$rows_affected = $wpdb->insert($dprv_licenses, array('license_type'=>$dprv_custom_license, 'license_caption'=>$dprv_custom_license_caption, 'license_abstract'=>$dprv_custom_license_abstract, 'license_url'=>$dprv_custom_license_url));
				dprv_populate_licenses();			// Rebuild dprv_license table in php
				dprv_populate_licenses_js();			// and in javascript
				$result_message =  __('License added', 'dprv_cp');

				break;
			}
			case "RemoveLicense":
			{
				$log->lwrite("about to remove license " .  $_POST['dprv_license']);
				//$wpdb->show_errors();
				$dbquery = 'DELETE FROM ' . get_option('dprv_prefix') . 'dprv_licenses WHERE id = ' .  $_POST['dprv_license'];
				//$dbquery = 'DELETE FROM ' . $wpdb->prefix . 'dprv_licenses WHERE id = ' .  $_POST['dprv_license'];
				$wpdb->query($dbquery);
				if ($_POST['dprv_license'] == get_option('dprv_license'))
				{
					update_option('dprv_license', '0');
					$dprv_license = '0';
				}
				else
				{
					$dprv_license = get_option('dprv_license');
				}
				dprv_populate_licenses();			// Rebuild dprv_license table in php
				dprv_populate_licenses_js();			// and in javascript
				$result_message = __('License removed', 'dprv_cp');
				break;
			}

			default:
			{
				// VALIDATE

				// Problem here - if invalid settings, error message is displayed but contents of $__POST is lost
				$result_message = dprv_ValidateRegistration();
				$log->lwrite("result_message=$result_message");
				if ($result_message == "")
				{
					$log->lwrite("dprv_settings continuing");
					$dprv_update_user = false;
					
					// NOTE: Need to check if set each field as some may be disabled
					
					// PERSONAL DETAILS:
					if (isset($_POST['dprv_email_address']) && $_POST['dprv_email_address'] != get_option('dprv_email_address'))
					{
						update_option('dprv_email_address',$_POST['dprv_email_address']);
						$dprv_email_address = $_POST['dprv_email_address'];
						$dprv_update_user = true;
					}

					if (isset($_POST['dprv_first_name']) && $_POST['dprv_first_name'] != get_option('dprv_first_name'))
					{
						update_option('dprv_first_name',$_POST['dprv_first_name']);
						$dprv_first_name = $_POST['dprv_first_name'];
						$dprv_update_user = true;
					}
					if (isset($_POST['dprv_last_name']) && $_POST['dprv_last_name'] != get_option('dprv_last_name'))
					{
						update_option('dprv_last_name',$_POST['dprv_last_name']);
						$dprv_last_name = $_POST['dprv_last_name'];
						$dprv_update_user = true;
					}
					if (isset($_POST['dprv_display_name']) && $_POST['dprv_display_name'] != get_option('dprv_display_name'))
					{
						update_option('dprv_display_name',$_POST['dprv_display_name']);
						$dprv_display_name = $_POST['dprv_display_name'];
						$dprv_update_user = true;
					}
					if (isset($_POST['dprv_email_certs']) && $_POST['dprv_email_certs'] != get_option('dprv_email_certs'))
					{
						update_option('dprv_email_certs',$_POST['dprv_email_certs']);
						$dprv_email_certs = $_POST['dprv_email_certs'];
						$dprv_update_user = true;
					}

					// REGISTRATION STUFF:
					if (isset($_POST['dprv_enrolled']))
					{
						$dprv_enrolled = $_POST['dprv_enrolled'];
						update_option('dprv_enrolled',$_POST['dprv_enrolled']);
					}
					$dprv_register_option="unknown";
					if (isset($_POST['dprv_register']))
					{
						$dprv_register_option=$_POST['dprv_register'];
					}
					if (isset($_POST['dprv_user_id']) && $dprv_user_id != $_POST['dprv_user_id'])   // Change of User Id?
					{
						$dprv_user_id = $_POST['dprv_user_id'];
						$dprv_update_user = true;
						//update_option('dprv_user_id',$_POST['dprv_user_id']);		// Do later if synchronisation or registration successful
					}
					if (isset($_POST['dprv_api_key']))
					{
						$dprv_api_key = $_POST['dprv_api_key'];
						update_option('dprv_api_key',$_POST['dprv_api_key']);
					}
					if (isset($_POST['dprv_renew_api_key']) && $_POST['dprv_renew_api_key'] == "on")
					{
						$dprv_renew_api_key = $_POST['dprv_renew_api_key'];
						$dprv_update_user = true;
					}
					if (isset($_POST['dprv_password']) && $_POST['dprv_password'] != "")
					{
						$dprv_password = $_POST['dprv_password'];
					}
					if (isset($_POST['dprv_pw_confirm']) && $_POST['dprv_pw_confirm'] != "")
					{
						$dprv_pw_confirm = $_POST['dprv_pw_confirm'];
					}

					$dprv_can_contact = false;
					if (isset($_POST['dprv_contact']) && $_POST['dprv_contact'] == "on")
					{
						$dprv_can_contact = true;
					}

	
					// ADVANCED TAB INFO:
					if (isset($_POST['dprv_content_type']))
					{
						$dprv_content_type = $_POST['dprv_content_type'];
						update_option('dprv_content_type',$_POST['dprv_content_type']);
					}
					if (isset($_POST['dprv_notice']))
					{
						$dprv_notice = $_POST['dprv_notice'];
						update_option('dprv_notice',$_POST['dprv_notice']);
					}
					if (isset($_POST['dprv_custom_notice']) && $_POST['dprv_custom_notice'] != "")
					{
						$dprv_notice = $_POST['dprv_custom_notice'];
						update_option('dprv_notice',$_POST['dprv_custom_notice']);
					}
					if (isset($_POST['dprv_c_notice']))
					{
						$dprv_c_notice = $_POST['dprv_c_notice'];
						update_option('dprv_c_notice',$_POST['dprv_c_notice']);
					}
					if (isset($_POST['dprv_notice_size']))
					{
						$dprv_notice_size = $_POST['dprv_notice_size'];
						update_option('dprv_notice_size',$_POST['dprv_notice_size']);
					}
					if (isset($_POST['dprv_notice_border']))
					{
						$dprv_notice_border = $_POST['dprv_notice_border'];
						update_option('dprv_notice_border', $_POST['dprv_notice_border']);
					}
					if (isset($_POST['dprv_notice_background']))
					{
						$dprv_notice_background = $_POST['dprv_notice_background'];
						update_option('dprv_notice_background', $_POST['dprv_notice_background']);
					}
					if (isset($_POST['dprv_notice_color']))
					{
						$dprv_notice_color = $_POST['dprv_notice_color'];
						update_option('dprv_notice_color', $_POST['dprv_notice_color']);
					}
					if (isset($_POST['dprv_hover_color']))
					{
						$dprv_hover_color = $_POST['dprv_hover_color'];
						update_option('dprv_hover_color', $_POST['dprv_hover_color']);
					}

					if (isset($_POST['dprv_obscure_url']))
					{
						$dprv_obscure_url = $_POST['dprv_obscure_url'];
						update_option('dprv_obscure_url',$_POST['dprv_obscure_url']);
					}

					if (isset($_POST['dprv_linkback']))
					{
						$dprv_linkback = $_POST['dprv_linkback'];
						update_option('dprv_linkback',$_POST['dprv_linkback']);
					}

					if (isset($_POST['dprv_save_content']))
					{
						$dprv_save_content = $_POST['dprv_save_content'];
						update_option('dprv_save_content',$_POST['dprv_save_content']);
					}
					
					if (isset($_POST['dprv_footer']) && $_POST['dprv_footer'] =="on")
					{
						$dprv_footer = "Yes";
					}
					else
					{
						$dprv_footer = "No";
					}
					update_option('dprv_footer', $dprv_footer);

					if (isset($_POST['dprv_multi_post']) && $_POST['dprv_multi_post'] =="on")
					{
						$dprv_multi_post = "Yes";
					}
					else
					{
						$dprv_multi_post = "No";
					}
					update_option('dprv_multi_post', $dprv_multi_post);

					// CONTENT TAB INFO:
					$new_post_types = "";
					foreach ($_POST as $key => $value)
					{
						if (strpos($key, "dprv_post_type_") !== false)
						{
							if ($value == "on")
							{
								$new_post_types .= substr($key, 15) . ",";
							}
						}
					}
					if ($new_post_types != "")
					{
						if (substr($new_post_types, strlen($new_post_types)-1) == ",")
						{
							$new_post_types = substr($new_post_types,0,strlen($new_post_types)-1);
						}
						$dprv_post_types = $new_post_types;
						update_option('dprv_post_types', $dprv_post_types);
					}

					//  key = tag, value = array("selected"=>True/False, "incl_excl"=>Include/Exclude,	"notag" or "tag....tag": True/False)
					foreach ($dprv_html_tags as $tag=>$value)
					{
						$include = false;
						$ignore_rest = false;
						foreach ($value as $tag_key=>$key_value)
						{
							if ($tag_key != "name")
							{
								if ($tag_key == "incl_excl")
								{
									$dprv_html_tags[$tag][$tag_key] = $_POST["dprv_html_tag_" . $tag . "_ie"];
									if ($_POST["dprv_html_tag_" . $tag . "_ie"] == "Include")
									{
										$include=true;
									}
								}
								else
								{
									$post_key = "dprv_html_tag_" . $tag;
									if ($tag_key != "selected")
									{
										$post_key = "dprv_html_tag_" . $tag . "_types_" . str_replace(" ", "_", $tag_key);
									}
									if (isset($_POST[$post_key]) && $_POST[$post_key] == "on")
									{
										if ($tag_key=="All" && $include == true)
										{
											$ignore_rest = true;
										}
										$dprv_html_tags[$tag][$tag_key] = "True";
									}
									else
									{
										if ($ignore_rest == false)
										{
											$dprv_html_tags[$tag][$tag_key] = "False";
										}
									}
								}
							}
						}
					}
					update_option('dprv_html_tags', $dprv_html_tags);

					if (isset($_POST['dprv_outside_media']))
					{
						$dprv_outside_media = $_POST['dprv_outside_media'];
						update_option('dprv_outside_media',$_POST['dprv_outside_media']);
					}

					// LICENSE TAB INFO:
					if (isset($_POST['dprv_license']))
					{
						$dprv_license = $_POST['dprv_license'];
						update_option('dprv_license',$_POST['dprv_license']);
					}
					// TODO: Maybe we don't need to test whether these custom license fields are set?
					if (isset($_POST['dprv_custom_license']))
					{
						$dprv_custom_license = $_POST['dprv_custom_license'];
					}
					if (isset($_POST['dprv_custom_license_caption']))
					{
						$dprv_custom_license_caption = $_POST['dprv_custom_license_caption'];
					}
					if (isset($_POST['dprv_custom_license_abstract']))
					{
						$dprv_custom_license_abstract = $_POST['dprv_custom_license_abstract'];
					}
					if (isset($_POST['dprv_custom_license_url']))
					{
						$dprv_custom_license_url = $_POST['dprv_custom_license_url'];
					}

					// DATA INTEGRITY TAB
					if (isset($_POST['dprv_html_integrity']))
					{
						$dprv_html_integrity = $_POST['dprv_html_integrity'];
						update_option('dprv_html_integrity',$_POST['dprv_html_integrity']);
					}
					if (isset($_POST['dprv_files_integrity']))
					{
						$dprv_files_integrity = $_POST['dprv_files_integrity'];
						update_option('dprv_files_integrity',$_POST['dprv_files_integrity']);
					}
					
					// COPY-PROTECT TAB
					if (isset($_POST['dprv_frustrate_copy']))
					{
						$dprv_frustrate_copy = $_POST['dprv_frustrate_copy'];
						update_option('dprv_frustrate_copy',$_POST['dprv_frustrate_copy']);
					}
					$dprv_right_click_message = "";
					if (isset($_POST['dprv_right_click_box']) && $_POST['dprv_right_click_box'] =="on")
					{
						if (isset($_POST['dprv_right_click_message']))
						{
							$dprv_right_click_message = htmlspecialchars(stripslashes($_POST['dprv_right_click_message']), ENT_QUOTES);
							update_option('dprv_right_click_message', $dprv_right_click_message);
						}
					}
					else
					{
						update_option('dprv_right_click_message', "");
					}
					if (isset($_POST['dprv_record_IP']))
					{
						$dprv_record_IP = $_POST['dprv_record_IP'];
						update_option('dprv_record_IP',$_POST['dprv_record_IP']);
					}


					$message = __("Digiprove Settings Updated.", 'dprv_cp');
					$log->lwrite("dprv_enrolled = $dprv_enrolled, dprv_register_option = $dprv_register_option");
					if ($dprv_enrolled == "No" && $dprv_register_option == "Yes")
					{
						$register_response = dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs, $dprv_can_contact);
						$pos = stripos($register_response, "<result_code>0");
						if ($pos === false)
						{
							$failure_message = dprv_getTag($register_response,"result");
							if ($failure_message == false)
							{
								$failure_message = $register_response;
							}
							$result_message = "<font color='orangered'>" . __("Registration did not complete:", "dprv_cp") . " " . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
							if (strpos($failure_message, "already registered") === false)
							{
								$result_message .= ", " . __("please try later", "dprv_cp");
							}
							$result_message .= "</font>";
							$log->lwrite("Registration failed, response:");
							$log->lwrite($register_response);
						}
						else
						{
							$result_message = __('Digiprove user registration was successful, check your email for the activation link', 'dprv_cp');
							if ($dprv_user_id != get_option('dprv_user_id'))
							{
								update_option('dprv_user_id', $dprv_user_id);
							}
							$dprv_api_key = dprv_getTag($register_response, "api_key");
							update_option('dprv_api_key',$dprv_api_key);
							update_option('dprv_enrolled',"Yes");
							$dprv_subscription_type = dprv_getTag($register_response, "subscription_type");
							if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
							{
								update_option('dprv_subscription_type', $dprv_subscription_type);
							}
							$dprv_enrolled = "Yes";
							print('<script type="text/javascript">
										//<![CDATA[
										if(document.getElementById("dprv_reminder"))
										{
											document.getElementById("dprv_reminder").innerHTML = "";
											document.getElementById("dprv_reminder").style.display = "none";
										}
										//]]>
									</script>');
						}
					}
					else
					{
						if ($dprv_enrolled == "Yes" && $dprv_update_user == true)
						{
							$register_response = dprv_update_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs, $dprv_renew_api_key);
							$dprv_renew_api_key = "";	// unset this, user will have to retick if required

							$dprv_subscription_type = dprv_getTag($register_response, "subscription_type");
							if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
							{
								update_option('dprv_subscription_type', $dprv_subscription_type);
								$dprv_subscription_expiry = dprv_getTag($register_response, "subscription_expiry");
								if ($dprv_subscription_expiry != null && $dprv_subscription_expiry != false && $dprv_subscription_expiry != "")
								{
									update_option('dprv_subscription_expiry', $dprv_subscription_expiry);
								}
								else
								{
									update_option('dprv_subscription_expiry', '');
								}
							}

							$pos = stripos($register_response, "<result_code>0");
							if ($pos === false)
							{
								$failure_message = dprv_getTag($register_response,"result");
								if ($failure_message == false)
								{
									$failure_message = $register_response;
								}
								// If a new user id was valid but the domain or api key was not, let the user id stick
								if ($dprv_user_id != get_option('dprv_user_id') && strpos($failure_message, "invalid domain or API key") !== false)
								{
									update_option('dprv_user_id',$_POST['dprv_user_id']);
								}
								$result_message = __('Server synchronisation did not complete:', 'dprv_cp') . ' '  . htmlentities($failure_message, ENT_QUOTES, 'UTF-8');
								$log->lwrite("Update failed, response:");
								$log->lwrite($register_response);
							}
							else
							{
								// If a new user id was input, make it stick
								if ($dprv_user_id != get_option('dprv_user_id'))
								{
									update_option('dprv_user_id',$_POST['dprv_user_id']);
								}

								$result_message = __('Server data has also been synchronised', 'dprv_cp');
								$dprv_new_api_key = dprv_getTag($register_response, "api_key");
								if ($dprv_new_api_key != null && $dprv_new_api_key != false && $dprv_new_api_key != "")
								{
									update_option('dprv_api_key',$dprv_new_api_key);
									$dprv_api_key = $dprv_new_api_key;
									delete_option('dprv_password');
								}

								print('<script type="text/javascript">
											//<![CDATA[
											if(document.getElementById("dprv_reminder"))
											{
												document.getElementById("dprv_reminder").innerHTML = "";
												document.getElementById("dprv_reminder").style.display = "none";
											}
											//]]>
										</script>');
							}
							update_option('dprv_last_result',$result_message);
							$dprv_last_result = $result_message;
						}
						else
						{
							if($dprv_enrolled == "Yes")
							{
								print('<script type="text/javascript">
											//<![CDATA[
											if(document.getElementById("dprv_reminder"))
											{
												document.getElementById("dprv_reminder").innerHTML = "";
												document.getElementById("dprv_reminder").style.display = "none";
											}
											//]]>
										</script>');
							}
						}
					}
				}
				else
				{
					// Error in registration details input:
					$log->lwrite("error $result_message");
					$registration_error = true;
					$message = "<font color='orangered'>" . __("Digiprove Settings not Updated.", 'dprv_cp') . "</font>";

					// Refresh variables with Postback values (if given) so that error value is shown:
					$dprv_email_address = $_POST['dprv_email_address'];
					$dprv_first_name = $_POST['dprv_first_name'];
					$dprv_last_name = $_POST['dprv_last_name'];
					$dprv_display_name = $_POST['dprv_display_name'];
					$dprv_email_certs = $_POST['dprv_email_certs'];

					// Registration variables - note can be disabled thus do isset checks
					if (isset($_POST['dprv_enrolled']))
					{
						$dprv_enrolled = $_POST['dprv_enrolled'];
					}
					if (isset($_POST['dprv_register']))
					{
						$dprv_register_option=$_POST['dprv_register'];
					}
					if (isset($_POST['dprv_user_id']))
					{
						$dprv_user_id = $_POST['dprv_user_id'];
					}
					if (isset($_POST['dprv_api_key']))
					{
						$dprv_api_key = $_POST['dprv_api_key'];
					}
					$dprv_renew_api_key = "off";
					if (isset($_POST['dprv_renew_api_key']) && $_POST['dprv_renew_api_key'] == "on")
					{
						$dprv_renew_api_key = $_POST['dprv_renew_api_key'];
					}
					$dprv_password = $_POST['dprv_password'];
					$dprv_pw_confirm = $_POST['dprv_pw_confirm'];
					// End of Registration details

					$dprv_notice = $_POST['dprv_notice'];
					if (isset($_POST['dprv_custom_notice']) && $_POST['dprv_custom_notice'] != "")
					{
						$dprv_notice = $_POST['dprv_custom_notice'];
					}
					$dprv_c_notice = $_POST['dprv_c_notice'];
					$dprv_notice_size = $_POST['dprv_notice_size'];
					$dprv_notice_border = $_POST['dprv_notice_border'];
					$dprv_notice_background = $_POST['dprv_notice_background'];
					$dprv_notice_color = $_POST['dprv_notice_color'];
					$dprv_hover_color = $_POST['dprv_hover_color'];

					$dprv_content_type = $_POST['dprv_content_type'];
					$dprv_obscure_url = $_POST['dprv_obscure_url'];
					$dprv_linkback = $_POST['dprv_linkback'];
					$dprv_save_content = $_POST['dprv_save_content'];

					// TODO 
					$new_post_types = "";
					foreach ($_POST as $key => $value)
					{
						if (strpos($key, "dprv_post_type_") !== false)
						{
							$log->lwrite($key . " is set to " . $value);
							if ($value == "on")
							{
								$new_post_types .= substr($key, 15) . ",";
							}
						}
					}
					if ($new_post_types != "")
					{
						if (substr($new_post_types, strlen($new_post_types)-1) == ",")
						{
							$new_post_types = substr($new_post_types,0,strlen($new_post_types)-1);
						}
						$dprv_post_types = $new_post_types;
					}

					
					foreach ($dprv_html_tags as $tag=>$value)
					{
						$include = false;
						$ignore_rest = false;
						foreach ($value as $tag_key=>$key_value)
						{
							if ($tag_key != "name")
							{
								if ($tag_key == "incl_excl")
								{
									$dprv_html_tags[$tag][$tag_key] = $_POST["dprv_html_tag_" . $tag . "_ie"];
									if ($_POST["dprv_html_tag_" . $tag . "_ie"] == "Include")
									{
										$include=true;
									}
								}
								else
								{
									$post_key = "dprv_html_tag_" . $tag;
									if ($tag_key != "selected")
									{
										$post_key = "dprv_html_tag_" . $tag . "_types_" . str_replace(" ", "_", $tag_key);
									}
									if (isset($_POST[$post_key]) && $_POST[$post_key] == "on")
									{
										if ($tag_key=="All" && $include == true)
										{
											$ignore_rest = true;
										}
										$dprv_html_tags[$tag][$tag_key] = "True";
									}
									else
									{
										if ($ignore_rest == false)
										{
											$dprv_html_tags[$tag][$tag_key] = "False";
										}
									}
								}
							}
						}
					}


					$dprv_outside_media = $_POST['dprv_outside_media'];

					if (isset($_POST['dprv_footer']) && $_POST['dprv_footer'] =="on")
					{
						$dprv_footer = "Yes";
					}
					else
					{
						$dprv_footer = "No";
					}
					if (isset($_POST['dprv_multi_post']) && $_POST['dprv_multi_post'] =="on")
					{
						$dprv_multi_post = "Yes";
					}
					else
					{
						$dprv_multi_post = "No";
					}

					$dprv_license = $_POST['dprv_license'];
					$log->lwrite("dprv_license=".$dprv_license);
					$dprv_custom_license = $_POST['dprv_custom_license'];
					$dprv_custom_license_caption = $_POST['dprv_custom_license_caption'];
					$dprv_custom_license_abstract = $_POST['dprv_custom_license_abstract'];
					$dprv_custom_license_url = $_POST['dprv_custom_license_url'];

					$dprv_html_integrity = $_POST['dprv_html_integrity'];
					$dprv_files_integrity = $_POST['dprv_files_integrity'];

					$dprv_frustrate_copy = $_POST['dprv_frustrate_copy'];
					$dprv_right_click_message = "";
					if (isset($_POST['dprv_right_click_message']))
					{
						$dprv_right_click_message = htmlspecialchars(stripslashes($_POST['dprv_right_click_message']), ENT_QUOTES);
					}
					$dprv_record_IP = $_POST['dprv_record_IP'];

					$dprv_last_result = get_option('dprv_last_result');
				}
			}
		}

		
		$log->lwrite("About to display $message");
		print('
			<div id="message" class="updated fade">
				<p>' . $message . '&nbsp;&nbsp;' . $result_message .
				'</p>
			</div>
			');
	}

	$log->lwrite("dprv_settings about to display");
	
	// Prepare HTML to represent DB values for drop-down and radio buttons
	// BASIC PART 1 TAB:

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

	// BASIC PART 2 (REGISTRATION) TAB:
	$dprv_reg_button_display = 'none';
	$dprv_reg_disabled = '';
	if	(	$dprv_api_key != "" && $dprv_user_id == get_option('dprv_user_id')
			&&
			(	$dprv_enrolled == 'Yes'
				&&
				$registration_error == false
				&&
				(strpos($dprv_last_result, __('Digiprove certificate id', 'dprv_cp')) !== false || strpos($dprv_last_result, "User already activated") !== false || strpos($dprv_last_result, __('Server data has also been synchronised', 'dprv_cp')) !== false || strpos($dprv_last_result, __("Subscription information refreshed OK", "dprv")) !== false)
			)
		)
	{
		$dprv_reg_button_display = '';
		$dprv_reg_disabled = ' disabled="disabled"';
	}

	$dprv_not_enrolled_selected = ' selected="selected"';
	$dprv_enrolled_selected = '';
	$dprv_display_register_row = '';
	$dprv_display_password_rows = '';
	$dprv_display_api_rows = ' style="display:none"';
	$dprv_display_api_row2 = ' style="display:none"';
	if ($dprv_enrolled == 'Yes')
	{
		$dprv_not_enrolled_selected = '';
		$dprv_enrolled_selected = ' selected="selected"';
		$dprv_display_register_row = 'style="display:none"';
		$dprv_display_api_rows = '';
		$dprv_display_password_rows = ' style="display:none"';
	}
	
	$dprv_register_now_checked = ' checked="checked"';
	$dprv_register_later_checked = '';
	$dprv_display_contact_row = 'style="display:none"';
	if ($dprv_register_option == "Yes")
	{
		$dprv_register_now_checked = ' checked="checked"';
		$dprv_display_contact_row = $dprv_display_register_row;
	}
	if ($dprv_register_option == "No")
	{
		$dprv_register_later_checked = ' checked="checked"';
		$dprv_display_contact_row = 'style="display:none"';
		$dprv_display_password_rows = ' style="display:none"';
	}
	$dprv_upgrade_link = dprv_createUpgradeLink();

	$dprv_renew_api_key_checked = '';
	$dprv_input_api_key_checked = '';
	if ($dprv_api_key == "" || $dprv_user_id != get_option('dprv_user_id'))
	{
		$dprv_renew_api_key_checked = ' checked="checked"';
		if ($dprv_renew_api_key == "off")
		{
			$dprv_renew_api_key_checked = '';
			$dprv_input_api_key_checked = ' checked="checked"';
			$dprv_display_api_row2 = '';
		}
		$obtain_api_caption = __("Obtain API key automatically (default)", "dprv_cp");
		$input_api_caption = __("I already have an API key for this domain", "dprv_cp"); 
	}
	else
	{
		$obtain_api_caption = __("Obtain new API key automatically", "dprv_cp");
		$input_api_caption = __("Let me input a new API key for this domain", "dprv_cp"); 
	}

	$dprv_contact_checked = '';
	if ($dprv_can_contact == true)
	{
		$dprv_contact_checked = ' checked="checked"';
	}
	global $dprv_blog_host, $dprv_wp_host;

	$dprv_password_on_record = "No";
	if ($dprv_password != null && $dprv_password != "")
	{
		$dprv_password_on_record = "Yes";
	}


	// ADVANCED TAB:
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


	$dprv_multi_post_checked = '';
	if ($dprv_multi_post != 'No')
	{
		$dprv_multi_post_checked = ' checked="checked"';
	}

	$dprv_footer_checked = '';
	if ($dprv_footer != 'No')
	{
		$dprv_footer_checked = ' checked="checked"';
	}

	// ADVANCED PART 1 TAB:
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

	$dprv_save_content_selected = ' selected="selected"';
	$dprv_no_save_content_selected = '';
	if ($dprv_save_content != 'SaveContent')
	{
		$dprv_save_content_selected = '';
		$dprv_no_save_content_selected = ' selected="selected"';
	}

	// CONTENT TAB:
	$current_dprv_post_types = explode(",",$dprv_post_types);
	$dprv_eligible_post_types = array();
	$dprv_post_type_labels = array();
	if (function_exists("get_post_types"))
	{
		$all_post_types = get_post_types('','objects');
		foreach ($all_post_types as $post_type_key=>$post_type_value)
		{
			if ($post_type_key != "attachment" && $post_type_key != "revision" && $post_type_key != "nav_menu_item")
			{
				$dprv_post_type_labels[$post_type_key] = $all_post_types[$post_type_key]->labels->name;
				if (array_search($post_type_key, $current_dprv_post_types) === false)
				{
					$dprv_eligible_post_types[$post_type_key] = "No";
				}
				else
				{
					$dprv_eligible_post_types[$post_type_key] = "Yes";
				}
			}
		}
	}
	else
	{
		$dprv_post_type_labels["post"] = "Posts";
		$dprv_post_type_labels["page"] = "Pages";
		$dprv_eligible_post_types["post"] = "Yes";
		$dprv_eligible_post_types["page"] = "Yes";
		if (array_search("post", $current_dprv_post_types) === false)
		{
			$dprv_eligible_post_types["post"] = "No";
		}
		if (array_search("page", $current_dprv_post_types) === false)
		{
			$dprv_eligible_post_types["page"] = "No";
		}
	}
	$hash_supported = 'Yes';
	if (!function_exists('hash'))
	{
		$hash_supported = 'No';
	}


	$dprv_outside_media_selected = '';
	$dprv_not_outside_media_selected = ' selected="selected"';
	if ($dprv_outside_media == 'Outside')
	{
		$dprv_outside_media_selected = ' selected="selected"';
		$dprv_not_outside_media_selected = '';
	}

	// LICENSE TAB:
	$dprv_all_rights_selected = ' selected="selected"';
	$dprv_some_rights__selected = '';
	if ($dprv_custom_license_caption != __('All Rights Reserved', 'dprv_cp'))	// This will only be set if POSTBACK, otherwise is academic
	{
		$dprv_all_rights_selected = '';
		$dprv_some_rights_selected = ' selected="selected"';
	}
	
	// DATA INTEGRITY TAB:
	$dprv_html_integrity_yes_checked = '';
	$dprv_html_integrity_no_checked = ' checked="checked"';
	if ($dprv_html_integrity == 'Yes')
	{
		$dprv_html_integrity_no_checked = '';
		$dprv_html_integrity_yes_checked = ' checked="checked"';
	}
	$log->lwrite('dprv_html_integrity=' . $dprv_html_integrity);
	$dprv_files_integrity_yes_checked = '';
	$dprv_files_integrity_no_checked = ' checked="checked"';
	if ($dprv_files_integrity == 'Yes')
	{
		$dprv_files_integrity_no_checked = '';
		$dprv_files_integrity_yes_checked = ' checked="checked"';
	}

	// COPY-PROTECT TAB:
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


	// APPLIES ACROSS TABS:
	$dprv_subscription_expired = "No";
	$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 864000;			// add 10-day grace period (Also handles any unforeseen timezone issues)
	if ($dprv_expiry_timestamp != false && $dprv_expiry_timestamp != -1 && time() > $dprv_expiry_timestamp)
	{
		$dprv_subscription_expired = "Yes";
	}
	$dprv_days_to_expiry = floor((strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') - time())/86400);
	$log->lwrite("dprv_days_to_expiry = " . $dprv_days_to_expiry);

	// Default Values
	$subscription_enabled_se = ' onclick="return false" onchange="this.selectedIndex=0;"';
	$subscription_enabled_tb = ' onclick="return false"';
	$subscription_enabled_cb = ' onclick="return false" onchange="this.checked = false;"';
	$sub_enabled_title = ' title="This option is available to current subscribers only"';
	$sub_enabled_onclick = ' onclick="SubscribersOnly(this.id);"';
	$sub_enabled_color = ' style="color:#CCCCCC;"';
	$sub_enabled_style = 'color:#CCCCCC;';
	$sub_bg_style = ' style="background-color:#CCCCCC;"';
	$premium_enabled_cb = ' onclick="return false" onchange="this.selectedIndex=0;"';
	$prem_enabled_title = ' title="This option is available to premium subscribers only"';
	$prem_enabled_onclick = ' onclick="PremiumOnly(this.id);"';
	$prem_enabled_style = 'color:#CCCCCC;';

	if ($dprv_subscription_type == "Basic" || $dprv_subscription_type == "" || $dprv_subscription_expired == "Yes")
	{
		$log->lwrite("disallowing sub");
	}
	else
	{
		$log->lwrite("allowing sub");
		$subscription_enabled_se = '';
		$subscription_enabled_tb = '';
		$subscription_enabled_cb = '';
		$sub_enabled_title = '';
		$sub_enabled_onclick = '';
		$sub_enabled_color = '';
		$sub_enabled_style = '';
		$sub_bg_style = '';
		if ($dprv_subscription_type != "Personal")
		{
			$premium_enabled = '';
			$prem_enabled_title = '';
			$prem_enabled_onclick = '';
			$prem_enabled_style = '';
		}
	}

	$dprv_tabs_enabled = '';
	$dprv_tab_title = '';
	if ($dprv_enrolled == 'No')
	{
		$dprv_tabs_enabled = '; color:#AAAAAA';
		$dprv_tab_title = ' title="You need to register before using this tab"';
	}
	//$dprv_home = get_settings('siteurl');

	print('
			<div class="wrap">
				<h2 style="vertical-align:8px;"><a href="http://www.digiprove.com"><img src="' . WP_PLUGIN_URL. '/digiproveblog/digiprove_logo_278x69.png" alt="Digiprove"/></a><span style="vertical-align:22px; padding-left:40px">'.__('Copyright Proof Settings', 'dprv_cp').'</span></h2>  
				<form id="dprv_cp" name="dprv_AnyOldThing" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=copyright_proof_admin.php" method="post" onsubmit="return SubmitSelected();">
					<input type="hidden" name="dprv_cp_action" value="dprv_cp_update_settings" />
						<fieldset class="options">
							<div class="option">
								<table cellpadding="0" cellspacing="0" border="0">
									<tbody>
										<tr>
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="width:796px">
													<tr>
														<td id="BasicTab" style="height:30px; width:120px; border:1px solid #666666;border-top-right-radius: 5px; border-bottom:0px; background-color:#EEFFEE; cursor:pointer" align="center" onclick="DisplayBasic()"><em>' . __('Basic', 'dprv_cp') . '</em></td>
														<td id="AdvancedTab" style="height:30px; width:120px; border:1px solid #666666; border-top-right-radius: 5px; background-color:#EEEEFF; cursor:pointer' . $dprv_tabs_enabled . '" align="center" onclick="DisplayAdvanced()"' . $dprv_tab_title . '><em>' . __('Advanced', 'dprv_cp') . '</em></td>
														<td id="ContentTab" style="height:30px; width:160px; border:1px solid #666666; border-top-right-radius: 5px; background-color:#CCEEDD; cursor:pointer' . $dprv_tabs_enabled . '" align="center" onclick="DisplayContentTab()"' . $dprv_tab_title . '><em>' . __('Certifying Content', 'dprv_cp') . '</em></td>
														<td id="LicenseTab" style="height:30px; width:120px; border:1px solid #666666; border-top-right-radius: 5px; background-color:#FFFFDD; cursor:pointer' . $dprv_tabs_enabled . '" align="center" onclick="DisplayLicenseTab()"' . $dprv_tab_title . '><em>' . __('License', 'dprv_cp') . '</em></td>
														<td id="CopyProtectTab" style="height:30px; width:120px; border:1px solid #666666; border-top-right-radius: 5px; background-color:#FFEEEE; cursor:pointer' . $dprv_tabs_enabled . '" align="center" onclick="DisplayCopyProtect()"' . $dprv_tab_title . '><em>' . __('Copy Protect', 'dprv_cp') . '</em></td>
														<td style="border:0px; border-bottom:1px solid #666666;"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart1">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEFFEE; border:1px solid #666666; border-top:0px; border-bottom:0px; width:796px">
													<tr><td style="height:6px; width:235px"></td></tr>
													<tr><td colspan="2"><b>' . __('Personal details and preferences', 'dprv_cp').'</b></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowPersonalDetailsText()">' .__('How these details are used', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Email address', 'dprv_cp') . '</td>
														<td style="width:340px"><input name="dprv_email_address" id="dprv_email_address" type="text" value="'.htmlspecialchars(stripslashes($dprv_email_address)).'" style="margin-left:0px;width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('First name:', 'dprv_cp') . '</td>
														<td><input name="dprv_first_name" id="dprv_first_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_first_name)).'" style="margin-left:0px;width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Last name:', 'dprv_cp') . '</td>
														<td><input name="dprv_last_name" id="dprv_last_name" type="text" value="'.htmlspecialchars(stripslashes($dprv_last_name)).'" style="margin-left:0px;width:290px"/></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Display user name:', 'dprv_cp') . '</td>
														<td><select name="dprv_display_name" style="margin-left:0px;width:300px" onchange="DisplayNameChanged(this);">
																<option value="Yes"' . $dprv_display_name_selected . '>' . __('Yes, display my name', 'dprv_cp') . '</option>
																<option value="No"' . $dprv_no_display_name_selected . '>' . __('No, keep my name private', 'dprv_cp') . '</option>
															</select></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowPrivacyText()">' .__('Note on privacy', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="Email_Digiprove_Certificates"' .  $sub_enabled_title . $sub_enabled_onclick . '>
														<td>' . __('Email my certificates to me:', 'dprv_cp') . '</td>
														<td><select name="dprv_email_certs"' . $subscription_enabled_se . ' style="margin-left:0px;width:300px;' . $sub_enabled_style . '">
																<option value="No"' . $dprv_no_email_certs_selected . '>' . __('No, don\'t bother me with emails', 'dprv_cp') . '</option>
																<option value="Yes"' . $dprv_email_certs_selected . '>' . __('Yes, send my Digiprove certs by email', 'dprv_cp') . '</option>
															</select></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowEmailCertText()">' .__('Note on certificates', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="BasicPart2">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFEEEE; border:1px solid #666666; border-top:0px; width:796px">
													<tr><td style="height:6px; width:235px"></td></tr>
													<tr style="height:30px;">
														<td style="font-weight:bold">' . __('Digiprove registration details', 'dprv_cp').'</td>
														<td style="width:340px"><input type="button" class="button" id="dprv_change_reg" value="' . __('Let me change Registration Info', 'dprv_cp') . '" onclick="return EnableRegistrationInputs()" style="margin-left:0px;display:' . $dprv_reg_button_display . '"/></td>
														<td style="padding-top:0px; padding-bottom:0px" class="submit" ><input type="button" id="dprv_resend_activation" name="dprv_resend_activation" onclick="ResendEmail();" value="' . __('Re-send activation email', 'dprv_cp') . '" /></td>
													</tr>
													<tr><td style="height:10px"></td></tr>
													<tr>
														<td>' . __('Registered Digiprove user?:', 'dprv_cp') . '</td>
														<td><select name="dprv_enrolled" id="dprv_enrolled" onchange="toggleCredentials()" style="margin-left:0px;width:290px"' . $dprv_reg_disabled . '>
																<option value="Yes" ' . $dprv_enrolled_selected . '>' . __('I am already registered with Digiprove', 'dprv_cp') . '</option>
																<option value="No" ' . $dprv_not_enrolled_selected . '>' . __('I have not yet registered with Digiprove', 'dprv_cp') . '</option>
															</select>
														</td>
														<td style="padding-left:5px" class="description"><a href="javascript:ShowRegistrationText()">' .__('What&#39;s this about?', 'dprv_cp') . '</a></td>
													</tr>
													<tr id="dprv_register_row0" ' . $dprv_display_register_row . '><td style="height:6px"></td></tr>
													<tr id="dprv_register_row1" ' . $dprv_display_register_row . '>
														<td>' . __('Do you want to register now?:', 'dprv_cp') . '</td>
														<td><input type="radio" name="dprv_register" id="dprv_register_yes" onclick="toggleCredentials()" value="Yes" ' . $dprv_register_now_checked . $dprv_reg_disabled . '/>' . __('Yes, register me now', 'dprv_cp') . '&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_register" id="dprv_register_no" onclick="toggleCredentials()" value="No" ' . $dprv_register_later_checked . $dprv_reg_disabled . '/>' . __('No, do it later', 'dprv_cp') . '</td>
															<td style="padding-left:5px" class="description" ><a href="javascript:ShowTermsOfUseText()">' .__('Terms of use.', 'dprv_cp') . '</a></td>
													</tr>');
											if ($dprv_subscription_type != '')
											{
												print(' <tr id="dprv_sub_row0"><td style="height:6px"></td></tr>
														<tr id="dprv_sub_row1"><td>'.__('Subscription Type:', 'dprv_cp').'</td>
															<td>' . $dprv_subscription_type);
															if ($dprv_subscription_type != "Basic" && $dprv_subscription_expiry != "")
															{
																print(' (' . sprintf(__('valid until %s', 'dprv_cp'), $dprv_subscription_expiry) . ')');
															}
												print('		&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:SyncUser();" style="vertical-align:-3px;width:16px;height:16px"><img src="' . WP_PLUGIN_URL. '/digiproveblog/Refresh-16.png" alt=""></a></td>
															<td style="padding-left:5px"><a class="button" href="' . $dprv_upgrade_link . '&amp;Action=Upgrade" target="_blank" style="font-weight:bold">' . __('Upgrade', 'dprv_cp') . '</a>');
															if ($dprv_subscription_type != "Basic" && $dprv_days_to_expiry < 15)
															{
																print(' / <a class="button" href="' . $dprv_upgrade_link . '&amp;Action=Renew" target="_blank" style="font-weight:bold">' . __('Renew', 'dprv_cp') . '</a>');
															}
												print('		</td>
														</tr>');
											}
											print('	<tr><td style="height:6px"></td></tr>
													<tr id="dprv_user_id_row1">
														<td style="vertical-align:top"><label for="dprv_user_id" id="dprv_user_id_labelA">'.__('Digiprove User Id:', 'dprv_cp').'</label><label for="dprv_user_id" id="dprv_user_id_labelB" style="display:none">'.__('Desired Digiprove User Id:', 'dprv_cp').'</label></td>
														<td><input name="dprv_user_id" id="dprv_user_id" type="text" autocomplete="off" value="'.htmlspecialchars(stripslashes($dprv_user_id)).'" onblur="javascript:ScheduleRestorePassword()" onchange="return UserIdChanged();" style="margin-left:0px;width:290px"' . $dprv_reg_disabled . '/></td>
														<td class="description" id="dprv_email_warning"></td>
													</tr>
													<tr id="dprv_user_id_row2"><td style="height:6px"></td></tr>
													<tr id="dprv_api_key_row_0"' . $dprv_display_api_rows . '>
														<td style="vertical-align:top"><label for="dprv_api_key" id="dprv_api_key_label" title="' . sprintf(__('Digiprove API key for %s', 'dprv_cp'), $dprv_blog_host) .'">' . __(' Digiprove API Key:', 'dprv_cp').'</label></td>
														<td><input type="checkbox" id="dprv_renew_api_key" name="dprv_renew_api_key" onclick="renewApiKey()" ' . $dprv_renew_api_key_checked . $dprv_reg_disabled . '/><label for="dprv_renew_api_key">&nbsp;' . $obtain_api_caption . '</label></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowAPIText(\'' . $dprv_blog_host. '\',\'' . $dprv_password_on_record . '\')">' .__('What&#39;s this?', 'dprv_cp') . '</a></td>
													</tr>
													<tr id="dprv_api_key_row_1"' . $dprv_display_api_rows . '>
														<td></td>
														<td><input type="checkbox" id="dprv_input_api_key" name="dprv_input_api_key" title="' . sprintf(__('Select this option only if you already have obtained a Digiprove API key for %s', 'dprv_cp'), $dprv_blog_host) . '" onclick="inputApiKey()" ' . $dprv_input_api_key_checked . $dprv_reg_disabled . '/><label for="dprv_input_api_key">&nbsp;' . $input_api_caption . '</label></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowAPIFaqText(\'' . $dprv_blog_host. '\',\'' . $dprv_password_on_record . '\')">' .__('More about API keys', 'dprv_cp') . '</a></td>

													</tr>
													<tr id="dprv_api_key_row_2"' . $dprv_display_api_row2 . '>
														<td id="dprv_api_key_caption"></td>
														<td><input name="dprv_api_key" id="dprv_api_key" type="text" autocomplete="off" value="'.htmlspecialchars(stripslashes($dprv_api_key)).'" style="margin-left:0px;width:190px"' . $dprv_reg_disabled . '/></td>
														<td></td>
													</tr>
													<tr id="dprv_password_row1"' . $dprv_display_password_rows . '>
														<td><label for="dprv_password" id="dprv_password_label">'.__('Select a password:', 'dprv_cp').'</label></td>
														<td><input name="dprv_password" id="dprv_password" type="password" value="'.htmlspecialchars(stripslashes($dprv_password)).'" onchange="javascript:SavePassword()" style="margin-left:0px;width:290px"' . $dprv_reg_disabled . '/></td>
														<td style="padding-left:5px" class="description" ><a href="javascript:ShowPasswordText()">' .__('Security note', 'dprv_cp') . '</a></td>
													</tr>
													<tr id="dprv_password_row2"' . $dprv_display_password_rows . '><td style="height:6px"></td></tr>
													<tr id="dprv_password_row3"' . $dprv_display_password_rows . '>
														<td></td>
														<td><input name="dprv_pw_confirm" id="dprv_pw_confirm" type="password" value="'.htmlspecialchars(stripslashes($dprv_pw_confirm)).'" style="margin-left:0px;width:290px"' . $dprv_reg_disabled . '/></td>
														<td class="description">'.__('type the password again.', 'dprv_cp').'</td>
													</tr>
													<tr id="dprv_contact_row0" ' . $dprv_display_contact_row . '><td style="height:6px"></td></tr>
													<tr id="dprv_contact_row1" ' . $dprv_display_contact_row . '>
														<td></td>
														<td colspan="2"><input type="checkbox" name="dprv_contact" id="dprv_contact" ' . $dprv_contact_checked . '/>&nbsp;' . __('I would like to learn about special offers and news from Digiprove', 'dprv_cp') . '</td>
													</tr>
													<tr><td style="height:6px"></td>
													</tr>
												</table>
											</td>
										</tr>
										<tr id="AdvancedPart1" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#EEEEFF; border:1px solid #666666; border-top:0px; border-bottom:0px; width:796px">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr><td colspan="2"><b>' . __('The Digiprove notice', 'dprv_cp') . '</b> ' . __('(at foot of each post)', 'dprv_cp') . '</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' .__('Preview:', 'dprv_cp') . '</td>
														<td id="dprv_notice_preview"></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Digiprove Notice Text:', 'dprv_cp') . '</td>
														<td><select name="dprv_notice" id="dprv_notice" onchange="Preview()" style="width:290px">'
																	. dprv_options_html(array(__("This content has been Digiproved", "dprv_cp"),
																							__("This article has been Digiproved", "dprv_cp"),
																							__("This blog post has been Digiproved", "dprv_cp"),
																							__("Copyright protected by Digiprove", "dprv_cp"),
																							__('Copyright secured by Digiprove', 'dprv_cp')),
																						array(__("This content has been Digiproved", "dprv_cp"),
																							__("This article has been Digiproved", "dprv_cp"),
																							__("This blog post has been Digiproved", "dprv_cp"),
																							__('Copyright protected by Digiprove', 'dprv_cp'),
																							__('Copyright secured by Digiprove', 'dprv_cp')),
																						sprintf(__("This %s has been Digiproved", "dprv_cp"), strtolower(htmlentities(stripslashes($dprv_content_type)))), 
																						$dprv_notice,
																						null,
																						null,
																						$currentMatch) .
													'</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="Custom_notice"' . $sub_enabled_title . $sub_enabled_onclick . '>
														<td>' . __('Or create your own text:', 'dprv_cp') . '</td>
														<td><input type="text" name="dprv_custom_notice" id="dprv_custom_notice"' . $subscription_enabled_tb . ' style="width:300px;' . $sub_enabled_style . '" onchange="createOwnText(this);"');
														if ($currentMatch == 0)
														{
															print (' value="' . $dprv_notice . '"');
														}
														print ('/>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Include a copyright notice:', 'dprv_cp') . '</td>
														<td><select name="dprv_c_notice" id="dprv_c_notice" onchange="Preview()" style="width:290px">
																<option value="DisplayAll"' . $dprv_c_all_selected . '>' . __('Display', 'dprv_cp') . '</option>
																<option value="Display"' . $dprv_c_selected . '>' . __('Display but leave out my name', 'dprv_cp') . '</option>
																<option value="NoDisplay"' . $dprv_no_c_selected . '>' . __('Do not display', 'dprv_cp') . '</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('How big should it be:', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_notice_size" id="dprv_notice_medium" value="Medium" ' . $dprv_notice_medium_checked . ' onclick="Preview()"/>' . __('Medium', 'dprv_cp') . '&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_notice_size" id="dprv_notice_small" value="Small" ' . $dprv_notice_small_checked . ' onclick="Preview()"/>' . __('Small', 'dprv_cp') . '&nbsp;&nbsp;&nbsp;&nbsp;
															<input type="radio" name="dprv_notice_size" id="dprv_notice_smaller" value="Smaller" ' . $dprv_notice_smaller_checked . ' onclick="Preview()"/>' . __('Smaller', 'dprv_cp') . '
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>'.__('Select colors:', 'dprv_cp').'</td>
														<td colspan="2">
															<table cellpadding="0" cellspacing="0" border="0">
																<tr>
																	<th align="left">' . __('Text', 'dprv_cp') . '</th>
																	<th align="left">' . __('Mouse-over', 'dprv_cp') . '</th>
																	<th align="left">' . __('Background', 'dprv_cp') . '</th>
																	<th align="left">' . __('Border', 'dprv_cp') . '</th>
																</tr>
																<tr>
																	<td width="120" align="left"><input style="width:70px;" value="' . $dprv_notice_color . '" id="dprv_notice_color" name="dprv_notice_color" onchange="Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;" value="' . $dprv_hover_color . '" id="dprv_hover_color" name="dprv_hover_color" onchange="Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;"  value="' . $dprv_notice_background . '" id="dprv_notice_background" name="dprv_notice_background" onchange="setCheckboxes();Preview()" /></td>
																	<td width="120" align="left"><input style="width:70px;background-color:' . $dprv_notice_border . '"  value="' . $dprv_notice_border . '" id="dprv_notice_border" name="dprv_notice_border" onchange="setCheckboxes();Preview()" /></td>
																</tr>
																<tr>
																	<td colspan="2"></td>
																	<td  align="left" style="font-size:11px"><input type="checkbox" id="dprv_no_background" name="dprv_no_background" ' . $no_background_checktext . ' onclick="noBackgroundChanged(this);" />' . __('Transparent', 'dprv_cp') . '</td>
																	<td  align="left" style="font-size:11px"><input type="checkbox" id="dprv_no_border" name="dprv_no_border" ' . $no_border_checktext . ' onclick="noBorderChanged(this);" />' . __('No Border', 'dprv_cp') . '</td>
																</tr>
															</table>
														</td>
													</tr>
													<tr><td style="height:4px"></td></tr>
													<tr>
														<td>' . __('Show notice on multi-post web-pages:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td><input type="checkbox" id="dprv_multi_post" name="dprv_multi_post" ' . $dprv_multi_post_checked . ' />
														&nbsp;&nbsp;<a href="javascript:ShowMultiPostText()">' . __('Note - for search pages, archive pages etc.', 'dprv_cp') . '</a>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Show generic Digiprove notice in footer:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td><input type="checkbox" id="dprv_footer" name="dprv_footer" ' . $dprv_footer_checked . ' onclick="ToggleFooterWarning()" />
															&nbsp;&nbsp;<a id="footer_warning_link" href="javascript:ShowFooterText()">' . __('Note - appearance depends on theme', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="AdvancedPart2" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#CCCCCC; border:1px solid #666666; border-top:0px; width:796px">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr><td colspan="2"><b>' . __('The certificate (on Digiprove web-page)', 'dprv_cp').'</b></td></tr>
													<tr><td style="height:6px"></td></tr>
									
													<tr>
														<td>' . __('How your content should be described:', 'dprv_cp') . '</td>
														<td><input name="dprv_content_type" type="text" value="'.htmlspecialchars(stripslashes($dprv_content_type)).'" style="width:290px" onchange="Preview()" /><span class="description">' . __('e.g. &quot;Blog post&quot;, &quot;News article&quot;, &quot;Opinion&quot;', 'dprv_cp') . '</span></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('Obscure Digiprove certificate link:', 'dprv_cp') . '</td>
														<td><select name="dprv_obscure_url" style="width:440px">
																<option value="Obscure"' . $dprv_obscure_selected . '>' . __('Obscure the link (for privacy)', 'dprv_cp') . '</option>
																<option value="Clear"' . $dprv_clear_selected . '>' . __('Do not obscure the link (for search engine optimisation)', 'dprv_cp') . '</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="Linkback"' . $sub_enabled_title . $sub_enabled_onclick . '>
														<td>' . __('Certificate page to link back to post?:', 'dprv_cp') . '</td>
														<td><select name="dprv_linkback" id="dprv_linkback"' . $subscription_enabled_se . ' style="width:440px;' . $sub_enabled_style . '">
																<option value="Nolink"' . $dprv_no_linkback_selected . '>' . __('Do not link back to my web pages', 'dprv_cp') . '</option>
																<option value="Linkback"' . $dprv_linkback_selected . '>' . __('Digiprove certificate web-page should have a link to my web page here', 'dprv_cp') . '</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="Save_Content"' . $prem_enabled_title . $prem_enabled_onclick . '>
														<td>' . __('Save content at www.digiprove.com:', 'dprv_cp') . '</td>
														<td><select name="dprv_save_content" id="dprv_save_content"' . $premium_enabled_cb . ' style="width:440px;' . $prem_enabled_style . '">
																<option value="Nosave"' . $dprv_no_save_content_selected . '>' . __('Do not save content', 'dprv_cp') . '</option>
																<option value="SaveContent"' . $dprv_save_content_selected . '>' . __('Save a copy of Digiproved content at www.digiprove.com', 'dprv_cp') . '</option>
															</select></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="Content" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#CCEEDD; border:1px solid #666666; border-top:0px; border-bottom:0px; width:798px">
													<tr><td style="height:6px; width:200px"></td><td style="width:50px"></td><td style="width:530px"></td></tr>
													<tr>
														<td id="dprv_post_type_caption" colspan="3"><b><em>' . __('Select Post Types  to be Digiproved:', 'dprv_cp') . '</em></b></td>
													</tr>
													<tr><td style="height:6px" colspan="3"></td></tr>');
													foreach ($dprv_eligible_post_types as $key => $value)
													{
														$type_checked = '';
														if ($value == "Yes")
														{
															$type_checked = ' checked="checked"';
														}
														print('<tr><td style="padding-left:10px">' . $dprv_post_type_labels[$key] . '</td><td><input id="dprv_post_type_' . $key . '" name="dprv_post_type_' . $key . '" type="checkbox" ' . $type_checked . '/></td><td></td></tr>');
													}
											print ('<tr><td style="height:6px" colspan="3"></td></tr>
													<tr><td style="height:20px" colspan="3"><hr/></td></tr>
													<tr>
														<td colspan="3"><b><em>' . __('Individually fingerprint files used in your content:', 'dprv_cp') . '</em></b>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:ShowFingerprintText(\''.$hash_supported . '\')"><em>' . __('What&#039;s this for?', 'dprv_cp') . '</em></a><div style="float:right"><a href="javascript:ShowBetaText()"><em>' . __('Note - Beta functionality', 'dprv_cp') . '</em></a>&nbsp;&nbsp;&nbsp;&nbsp;</div></td>
													</tr><tr><td style="height:12px" colspan="3"></td></tr>
													<tr>
														<td id="dprv_html_tags_caption" colspan="2"><em>' . __('Files referenced in HTML tags:', 'dprv_cp') . '</em></td>
														<td>&nbsp;&nbsp;<em>' . __('Media types', 'dprv_cp') . '</em></td>
													</tr>
													<tr><td style="height:6px"></td></tr>');
													$i = 0;
													foreach ($dprv_html_tags as $key => $value)
													{
														$row_style_statement = ' style="background-color:#DDFFEE"';
														$modulus = $i % 2;
														$i++;
														if ($modulus == 0)
														{
															$row_style_statement = '';
														}
														$type_checked = '';
														$media_style_statement = ' style="visibility:hidden"';
														if ($value["selected"] == "True" && $dprv_subscription_type != "Basic" && $dprv_subscription_type != "" && $dprv_subscription_expired != "Yes")
														{
															$type_checked = ' checked="checked"';
															$media_style_statement = '';
														}
														$exclude_selected = '';
														$include_selected = '';
														if ($value["incl_excl"] == "Exclude")
														{
															$exclude_selected = ' selected="selected"';
														}
														else
														{
															$include_selected = ' selected="selected"';
														}
														$descriptor = "";
														if ($key != "notag")
														{
															$descriptor = "&lt;" . $key . "&gt;";
														}
														print ('<tr' .  $row_style_statement . $sub_enabled_title . $sub_enabled_onclick .'>
																	<td style="padding-left:10px">' . htmlspecialchars($value["name"]) . '<span style="font-size:10px">&nbsp;' . $descriptor . '</span></td>
																	<td><input id="dprv_html_tag_' . $key . '" name="dprv_html_tag_' . $key . '" type="checkbox"' . $type_checked . $sub_bg_style . ' onclick="return toggleMedia(this);"/></td>
																	<td' . $media_style_statement . ' id="dprv_html_tag_' . $key . '_ie_col">
																		<select name="dprv_html_tag_' . $key . '_ie" style="width:70px" onchange="toggleInclExcl(this);">
																			<option id="dprv_html_tag_' . $key . '_i" ' . $include_selected . ' value="Include">Include</option>
																			<option id="dprv_html_tag_' . $key . '_e" ' . $exclude_selected . ' value="Exclude">Exclude</option>
																		</select>');
														$option_counter=0;
														$disabled = "";
														foreach ($value as $tag_key=>$tag_value)
														{
															if($tag_key != "name" && $tag_key != "selected" && $tag_key != "incl_excl")
															{
																$onclick='';
																$mime_style = '';
																$mime_style_modifier = '';
																if ($tag_key == "All")
																{
																	$onclick=' onclick="toggleMimeTypes(this)"';
																	if ($value["incl_excl"] == "Exclude")
																	{
																		$mime_style = ' style="visibility:hidden"';
																		$mime_style_modifier = '; visibility:hidden';
																	}
																}
																$type_checked = '';
																if ($tag_value == "True")
																{
																	$type_checked = ' checked="checked"';
																}
																$tooltip = __("File extensions:", "dprv_cp") . " ";
																$separator = "";
																foreach ($dprv_mime_types[$tag_key] as $ext)
																{
																	if ($ext == "")
																	{
																		$ext = "None";
																	}
																	else
																	{
																		$ext = "." . $ext;
																	}
																	$tooltip .= $separator . $ext;
																	$separator = ",";
																}
																print	(  '&nbsp;<label id="dprv_html_tag_'.$key.'_labels_'.$option_counter.'" style="font-size:10px' . $mime_style_modifier . '" for="dprv_html_tag_'.$key.'_types_'.$option_counter.' " title="'.$tooltip.'">'.$tag_key.'</label><input id="dprv_html_tag_'.$key.'_types_'.$option_counter.'" name="dprv_html_tag_'.$key.'_types_'.str_replace(' ', '_', $tag_key).'"' . $mime_style . ' title="'.$tooltip.'" type="checkbox"'.$onclick.$type_checked.$disabled.'/>');
																$option_counter++;
																if ($tag_key == "All" && $tag_value == "True" && $value["incl_excl"] == "Include")
																{
																	$disabled = ' disabled="disabled"';
																}
															}
														}
														print ('</td>
															</tr>');
													}
											print ('
													<tr style="display:none"><td style="height:6px"></td></tr>
													<tr style="display:none" id="OutsideMedia"' . $sub_enabled_title . $sub_enabled_onclick . '>
														<td>' . __('Media files hosted at other websites?:', 'dprv_cp') . '</td>
														<td><select name="dprv_outside_media" id="dprv_outside_media"' . $subscription_enabled_se . ' style="width:280px;' . $sub_enabled_style . '">
																<option value="NoOutside"' . $dprv_not_outside_media_selected . '>' . __('Only Digiprove media hosted on this site', 'dprv_cp') . '</option>
																<option value="Outside"' . $dprv_outside_media_selected . '>' . __('Digiprove media wherever it is hosted', 'dprv_cp') . '</option>
															</select>
														</td>
														<td></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr><td colspan="3" align="right"><input class="button" type="button" onclick="clear_html_tags();" value="' . __('Clear all', 'dprv_cp') . '"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button" type="button" onclick="default_html_tags();" value="' . __('Reset to default values', 'dprv_cp') . '"/></td></tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="ContentPart2" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFDDCC; border:1px solid #666666; border-top:0px; width:798px">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr>
														<td colspan="2" style="font-weight:bold; font-style:italic">' . __('Perform Data Integrity verification on your content', 'dprv_cp') . '</td>
														<td class="description" ><a href="javascript:ShowDataIntegrityText()">' .__('Note', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px;"></td></tr>
													<tr>
														<td valign="top">' . __('HTML Integrity:', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_html_integrity" id="dprv_html_integrity_yes" value="Yes" ' . $dprv_html_integrity_yes_checked . '/>&nbsp;' . __('Check Integrity of HTML', 'dprv_cp') . '<br/>
															<input type="radio" name="dprv_html_integrity" id="dprv_html_integrity_no" value="No" ' . $dprv_html_integrity_no_checked . '/>&nbsp;' . __('Don&#39;t Check HTML Integrity', 'dprv_cp') . '</td>
													</tr>
													<tr><td style="height:12px"></td></tr>
													<tr>
														<td valign="top">' . __('Check Embedded File Integrity:', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_files_integrity" id="dprv_files_integrity_yes" value="Yes" ' . $dprv_files_integrity_yes_checked . '/>&nbsp;' . __('Check Integrity of files used in your content', 'dprv_cp') . '<br/>
															<input type="radio" name="dprv_files_integrity" id="dprv_files_integrity_no" value="No" ' . $dprv_files_integrity_no_checked . '/>&nbsp;' . __('Don&#39;t Check Integrity of files used in your content', 'dprv_cp') . '
														</td>
														<td><a href="javascript:FileIntegrityNote()">' . __('Important note', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>
										<tr id="License" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFFFDD; border:1px solid #666666; border-top:0px; width:796px">
													<tr><td style="height:6px; width:180px"></td></tr>
													<tr><td colspan="2"><b id="dprv_license_heading">' . __('Default License Statement', 'dprv_cp') . '</b></td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td id="dprv_license_type_caption">' . __('Select License Type:', 'dprv_cp') . '</td>
														<td><select name="dprv_license" id="dprv_license" onchange="PreviewLicense()" style="width:300px">'
																	. dprv_options_html($dprv_licenseIds, $dprv_licenseTypes, "", $dprv_license, "0", __("None","dprv_cp"), $currentMatch) .
															'</select>
															<input type="text" id="dprv_custom_license" name="dprv_custom_license" autocomplete="off" style="display:none; width:300px" />
														</td>
														<td id="License_customization"' . $sub_enabled_title . '>
															<input type="button"' . $sub_enabled_color . ' value="' . __('Add', 'dprv_cp') . '" onclick="AddLicense();" />&nbsp;&nbsp;
															<input type="button"' . $sub_enabled_color . ' id="dprv_amend_license_button" value="' . __('Amend', 'dprv_cp') . '" onclick="AmendLicense();" />&nbsp;&nbsp;
															<input type="button"' . $sub_enabled_color . ' id="dprv_remove_license_button" value="' . __('Remove', 'dprv_cp') . '" onclick="RemoveLicense();" />
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td>' . __('License Caption', 'dprv_cp') . ':</td>
														<td colspan="2" style="width:500px">
															<span id="dprv_license_caption"></span>
															<select name="dprv_custom_license_caption" id="dprv_custom_license_caption" >
																<option value="' . __("All Rights Reserved", "dprv_cp") . '"' . $dprv_all_rights_selected . '>' . __("All Rights Reserved", "dprv_cp") . '</option>
																<option value="' . __("Some Rights Reserved", "dprv_cp") . '"' . $dprv_some_rights_selected . '>' . __("Some Rights Reserved", "dprv_cp") . '</option>
															</select>														
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td valign="top">' . __('License Abstract', 'dprv_cp') . ':</td>
														<td colspan="2" style="width:500px">
															<span id="dprv_license_abstract"></span>
															<textarea name="dprv_custom_license_abstract" id="dprv_custom_license_abstract" cols="50" rows="6">' . $dprv_custom_license_abstract . '</textarea>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr>
														<td valign="top">' . __('Link to full license text', 'dprv_cp') . ':</td>
														<td colspan="2">
															<a href="" target="_blank" id="dprv_license_url"></a>
															<input type="text" style="width:100%" name="dprv_custom_license_url" id="dprv_custom_license_url" value="' . $dprv_custom_license_url . '"/>
														</td>
													</tr>
													<tr><td style="height:6px"></td></tr>
													<tr id="dprv_license_commit_0" style="display:none">
														<td><input type="button" id="dprv_license_commit" value ="' . __("Add this license", "dprv_cp") . '" onclick="LicenseActionCommit()"/></td>
														<td>
															<input type="button" value ="' . __('Cancel', 'dprv_cp') . '" onclick="LicenseActionAbandon();" />
														</td></tr>
													<tr id="dprv_license_commit_1" style="display:none"><td style="height:6px"></td></tr>
												</table>
											</td>
										</tr>

										<tr id="CopyProtect" style="display:none">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; padding-top:7px; padding-right:5px; background-color:#FFEEEE; border:1px solid #666666; border-top:0px; width:796px">
													<tr><td style="height:6px; width:280px"></td></tr>
													<tr>
														<td></td>
														<td class="description" ><a href="javascript:ShowFrustrateCopyText()">' .__('Important Note', 'dprv_cp') . '</a></td>
													</tr>
													<tr><td style="height:6px;"></td></tr>
													<tr>
														<td valign="top">' . __('Frustrate copying attempts:&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td>
															<input type="radio" name="dprv_frustrate_copy" id="dprv_frustrate_yes" value="Yes" ' . $dprv_frustrate_yes_checked . ' onclick="toggle_r_c_checkbox()" />&nbsp;' . __('Prevent right-click,&nbsp;select,&nbsp;drag,&nbsp;&amp;&nbsp;Control key combinations', 'dprv_cp') . '<br/>
															<input type="radio" name="dprv_frustrate_copy" id="dprv_frustrate_no" value="No" ' . $dprv_frustrate_no_checked . ' onclick="toggle_r_c_checkbox()" />&nbsp;' . __('Allow right-click,&nbsp;select,&nbsp;drag,&nbsp;&amp;&nbsp;Control key combinations', 'dprv_cp') . '</td>
													</tr>
													<tr><td style="height:12px"></td></tr>
													<tr>
														<td>' . __('Display warning note on right-click? :&nbsp;&nbsp;', 'dprv_cp') . '</td>
														<td colspan="2">
															<input type="checkbox" ' . $right_click_checktext . ' id="dprv_right_click_box" name="dprv_right_click_box" onclick="toggle_r_c_text(this);" />
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
	//if ($dprv_last_result != '' && strpos($dprv_last_result, "Configure Copyright Proof") === false)
	if ($dprv_last_result != '')
	{
		print ('
										<tr id="BasicPart3">
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="padding-left:10px; background-color:#DDDDE4; border:1px solid #666666; border-top:0px; width:796px">
													<tr><td style="height:6px;width:235px"></td></tr>
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
											<tr>
											<td colspan="2">
												<table cellpadding="0" cellspacing="0" border="0" style="width:796px; border: 0px none; padding-top: 8px;">
													<tr>
														<td class="submit" style="width: 125px; padding-top: 8px; padding-bottom: 8px;">
															<input name="dprv_submit" id="dprv_submit" value="'.__('Update Settings', 'dprv_cp').'" type="submit"/>
															<input id="dprv_action" name="dprv_action" value="Update" type="hidden"/>
														</td>
														<td id="HelpTextContainer" style="border: 1px solid black; background-color:#FFFFFF; padding: 3px; display:none">
															<span id="HelpText" style="border: 0px none;"></span>
															<br style="line-height: 4px;"/>
															<a href="javascript:HideHelpText()" style="float:right;text-align:right">' . __('Close this window', 'dprv_cp') . '</a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</fieldset>
				</form>' );

	
	$jsfile = WP_PLUGIN_URL . '/digiproveblog/jscolor.js?v='.DPRV_VERSION;
	print('<script type="text/javascript" src="' . $jsfile . '"></script>');
	$jsfile = WP_PLUGIN_URL . '/digiproveblog/copyright_proof_settings.js?v='.DPRV_VERSION;
	print('<script type="text/javascript" src="' . $jsfile . '"></script>');
	$creative_hyperlink = "<a href=\"http://www.digiprove.com/creative-and-copyright.aspx\" target=\"_blank\">";
	$upgrade_hyperlink = "<a href=\"" . $dprv_upgrade_link . "&Action=Upgrade\" target=\"_blank\">";
	$renew_hyperlink = "<a href=\"" . $dprv_upgrade_link . "&Action=Renew\" target=\"_blank\">";
	$close_link = "</a>";
	print('<script type="text/javascript">
			//<![CDATA[
			');


	print ('
			var dprv_literals = new Array();
			dprv_literals["Update_settings"] = \'' . __("Update Settings", "dprv_cp") . '\';
			dprv_literals["Update_&_register"] = \'' . __("Update & Register", "dprv_cp") . '\';
			dprv_literals["No_touch_warning0"] = \'' . __("Once you have registered successfully and the plugin is working it should not be necessary to modify any of these values, and entering an incorrect value may cause the plugin to stop working.  If you are sure you wish to proceed, press OK.", "dprv_cp") . '\';
			dprv_literals["No_special_chars"] = \'' . __("Sorry, User Id cannot contain any special characters (\", ?, :, *, |, <, >, /, \\\\)", "dprv_cp") . '\';
			dprv_literals["No_touch_warning1"] = \'' . __("You are changing your Digiprove User Id. This may cause the plugin to stop working.  Press OK if you are sure, or Cancel to restore the previous value.", "dprv_cp") . '\';
			dprv_literals["No_touch_warning2"] = \'' . __("You have entered a value for API Key - do this only if the new API key was obtained from Digiprove", "dprv_cp") . '\';

			dprv_literals["API_key_empty"] = \'' . __("An empty api key is invalid.", "dprv_cp") . '\';
			dprv_literals["API_key_warning0"] = \'' . __("You are changing the API Key - do this only if the new API key was obtained from Digiprove, otherwise the plugin will stop working.", "dprv_cp") . '\';
			dprv_literals["API_key_warning1"] = \'' . __(" That does not look like a valid API key which is normally 22 characters.", "dprv_cp") . '\';
			dprv_literals["API_key_warning2"] = \'' . __(" Press OK to proceed with change or Cancel to restore original value", "dprv_cp") . '\';
			dprv_literals["API_key_warning3"] = \'' . __("You should only input a value for API Key if you have already obtained this API key from Digiprove.", "dprv_cp") . '\';
			dprv_literals["API_key_warning4"] = \'' . __("Do this only if the new API key was obtained from Digiprove, otherwise the plugin will stop working.", "dprv_cp") . '\';

			dprv_literals["Current_API_key"] = \'' . __("Current API Key:", "dprv_cp") . '\';
			dprv_literals["Enter_password"] = \'' . __("Enter Password:", "dprv_cp") . '\';
			dprv_literals["Select_password"] = \'' . __("Select a Password:", "dprv_cp") . '\';
			dprv_literals["Enter_API_key"] = \'' . __("Enter API Key:", "dprv_cp") . '\';

			dprv_literals["Personal_help"] = \'' . __("Copyright Proof uses the Digiprove service (<a href=\"http://www.digiprove.com/creative-and-copyright.aspx\" target=\"_blank\">www.digiprove.com</a>) to certify the content and timestamp of your Wordpress posts. Digiprove needs the name of the person claiming copyright and a valid email address. The personal details on this panel will be used only in automatically creating your account at Digiprove.  The server records of this information are accessible at <a href=\"https://www.digiprove.com/members/preferences.aspx\" target=\"_blank\">https://www.digiprove.com/members/preferences.aspx</a>.", "dprv_cp") . '\';

			dprv_literals["Privacy_help"] = \'' . __("The Copyright Proof notice appearing at the foot of your blog posts contains a link to a web-page showing details of the Digiprove certificate of your content. If you do not want your name to appear on this page select the &#39;Keep private&#39; option. Your email address and Digiprove User id are never revealed. Click <a href=\"http://www.digiprove.com/privacypolicy.aspx\" target=\"_blank\">here</a> to read the full privacy statement.", "dprv_cp") . '\';

			dprv_literals["Email_cert_help"] = \'' . __("Every post you publish or update will cause a cryptographically encoded certificate to be created which is your proof that your content was published by you at that exact time and date. This is retained for you in case it is required in future. Each certificate may be downloaded at the Digiprove website if required - you will need a current subscription to do this. Digiprove subscribers have the option to receive their certificates automatically by email. To set this option, select &#39;Yes&#39; here.  You will still be able to download these certificates from the Digiprove site if required.", "dprv_cp") . '\';

			dprv_literals["API_key_required0"] = \'' . sprintf(__("A Digiprove API key for %s is required for this domain to use the Digiprove service.", "dprv_cp"), $dprv_blog_host) . '\';

			dprv_literals["API_key_required1"] = \'' . sprintf(__(" If you have already registered, you do not need to do anything, it will be filled in automatically when required. You can also obtain an API key %s from the Digiprove members&#39; website %s (you will be asked to log in)", "dprv_cp"), "<a href=\"https://www.digiprove.com/members/api_keys.aspx\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["API_key_required2"] = \'' . sprintf(__(" If you are already registered with Digiprove, you can obtain your API key for this domain by ticking the &quot;Obtain New API Key&quot; box above (you will be asked for your password).  API keys can also be obtained at the %s Digiprove members&#39; website %s (you will be asked to log in)", "dprv_cp"), "<a href=\"https://www.digiprove.com/members/api_keys.aspx\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["API_key_required3"] = \'' . sprintf(__(" If you registered from this page the API key will have been filled in automatically - there is no need to change it. If you are receiving error messages regarding your api key you can obtain a new one by ticking &quot;Obtain New Api Key&quot; box (you will need to input your password) or it can also be done from the  %sDigiprove  members&#39; website%s (you will be asked to log in)", "dprv_cp"), "<a href=\"https://www.digiprove.com/members/api_keys.aspx\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["API_key_FAQ"] = \'' . sprintf(__("<p>Digiprove requires a user id and a domain name/API key pair to authenticate transactions.  Copyright Proof&#39;s activation and registration process is designed to set up the required domain name/API key pair automatically when you install for the first time in Wordpress. When setting up the api key, by default it will use the domain name of the Site address URL as set in Wordpress / Settings / General (in your case &#39;%s&#39;); if this is blank the domain will be taken from the Wordpress address URL (&#39;%s&#39;).</p>There are certain situations where you might need to do something:<br/><table cellpadding=\"3\"><tr><td valign=\"top\"><em>Error message &#39;Invalid domain or api key&#39;</em></td><td>Either Digiprove has no record of the domain (%s or %s) being supplied or the api key does not match.  This can happen if your domain name has changed recently or if the api key was changed in error. It is usually resolved by ticking the box above entitled &#39;Obtain new api key automatically&#39;, and pressing &#39;Update Settings&#39;.</td></tr><tr><td valign=\"top\"><em>Error message &#39;Max number of API keys reached for this subscription type&#39;</em></td><td>The number of permitted domains depends on the subscription level of your Digiprove account; the default free Basic account allows you to protect one website, a Personal account permits 5, and so on. You can either<ul style=\"padding-left:20px;list-style:disc\"><li>remove an existing domain/API key pair (from the <a href=\"https://www.digiprove.com/members/members_area.aspx?content=api_keys.aspx\" target=\"_blank\">Digiprove Member&#39;s area</a> (you will be asked for your user id and password) or</li><li>upgrade to a plan that allows the desired number of websites (<a href=\"%s\" target=\"_blank\" style=\"font-weight:bold\">Upgrade</a>)</li></ul>Once you have taken one of these steps, tick the box above entitled &#39;Obtain new api key automatically&#39;, then press &#39;Update Settings&#39;.</td></tr></table>", "dprv_cp"), $dprv_blog_host, $dprv_wp_host, $dprv_blog_host, $dprv_wp_host, $dprv_upgrade_link . "&amp;Action=Upgrade") . '\';

			dprv_literals["Password_help"] = \'' . __("Your password to give you access to the Digiprove website members&#39; area. An encrypted version of the password is stored on the Digiprove server but <em>not here on your Wordpress server</em>.", "dprv_cp") . '\';

			dprv_literals["Register_help"] = \'' . sprintf(__("Copyright Proof uses the Digiprove service ( %s www.digiprove.com %s ) to certify the content and timestamp of your Wordpress posts. You need to register with Digiprove before Copyright Proof will start working for you; by selecting &quot;Yes, register me now&quot; this registration process will take place; you will then receive an email with an activation link.", "dprv_cp"), "<a href=\"http://www.digiprove.com/creative-and-copyright.aspx\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["Commercial_help"] = \'' . sprintf(__("Using this plugin, the core Digiprove services are provided free-of-charge.  There are %sterms of use%s governing things like privacy and abuse. There are some premium services that are available only to Digiprove subscribers", "dprv_cp"), "<a href=\"http://www.digiprove.com/termsofuse_page.aspx\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["Multipost_help"] = \'' . __("Tick this to allow the Digiprove notice to be included in post excerpts that appear on multi-post pages such as search results, archive pages etc.", "dprv_cp") . '\';
			
			dprv_literals["Footer_text_help"] = \'' . __("The message &quot;All original content on these pages is fingerprinted and certified by Digiprove&quot; will appear in the footer of all your web-pages. Please check how this looks on your site.  Whether and where the notice appears is determined by your theme.  To change this, look for wp_footer() in the footer.php file of your theme.", "dprv_cp") . '\';

			dprv_literals["PHP_warning0"] = \'' . __("Your version of PHP does not support this function.", "dprv_cp") . '\';
			dprv_literals["PHP_warning1"] = \'' . __("Ask your provider to upgrade you to PHP 5.1.2 or later.", "dprv_cp") . '\';

			dprv_literals["DataIntegrity_help"]	= \'' . __("This plugin can check the data integrity of your posts - thus identifying any unauthorised alteration of the content of your posts and pages, whether through deliberate hacking or through accidental corruption", "dprv_cp") . '\';	
			
			dprv_literals["FileIntegrityNote"] = \'' . __("Only files that are individually fingerprinted and Digiproved can be checked for integrity - this will depend on the settings you chose for Digiproving files above.", "dprv_cp") . '\';

			dprv_literals["Fingerprint_media_help"] = \'' . __("If you include media files (such as images, sounds, video, pdf etc.) within your posts or pages, you may instruct Digiprove to certify the digital fingerprints of the individual files, as well the html and text of the post itself.  Note this will only operate on files that are contained within your own web-site not hyperlinks to other sites.", "dprv_cp") . '\';
			
			dprv_literals["Beta_warning"] = \'' . __("Note the media file Digiproving and Data Integrity functions are in Beta form. We have tested them in a number of environments, but we are anxious for your feedback.  If you experience problems, firstly please advise us at support@digiprove.com, so we can fix the underlying problem. To get rid of problems, simply untick all of these boxes, or press &quot;Clear all&quot;.", "dprv_cp") . '\';

			dprv_literals["New_license_type"] = \'' . __("New License Type", "dprv_cp") . '\';
			dprv_literals["License_type_name"] = \'' . __("License Type Name", "dprv_cp") . '\';
			dprv_literals["Amend_license_type"] = \'' . __("Amend License Type", "dprv_cp") . '\';
			dprv_literals["Add_this_license"] = \'' . __("Add this license", "dprv_cp") . '\';
			dprv_literals["Update_this_license"] = \'' . __("Update this license", "dprv_cp") . '\';
			dprv_literals["Remove_this_license"] = \'' . __("Remove License Type %s?  (This operation cannot be undone)", "dprv_cp") . '\';
			dprv_literals["License_summary_empty"] = \'' . __("You have not entered a value for License Summary.  Press OK if this is intentional", "dprv_cp") . '\';
			dprv_literals["Default_license_statement"] = \'' . __("Default License Statement", "dprv_cp") . '\';
			dprv_literals["License_type"] = \'' . __("License Type:", "dprv_cp") . '\';
			dprv_literals["License_Type_Missing"]= \'' . __("Please enter a value for License Type", "dprv_cp") . '\';

			dprv_literals["Default_right_click_alert"] = \'' . __("Sorry, right-clicking is disabled; please respect copyrights", "dprv_cp") . '\';
			dprv_literals["Frustrate_copy_help"] = \'' . __("Selecting this option will prevent a user from right-clicking on your web-page (in order to view the source), selecting content (in order to copy to clipboard), or pressing CTRL/U (to view the source) in most browsers.  This may prevent the unauthorised use of your content by unsophisticated users, but will be a small nuisance to a determined content thief. This is as good as it gets on the web - DO NOT BELIEVE the claims of some other plugin authors that your content cannot be stolen...", "dprv_cp") . '\';

			dprv_literals["Digiprove certificate id"] = \'' . __("Digiprove certificate id", "dprv_cp") . '\';

			//dprv_literals["Registration_confirm"] = \'' . __("Do you want to proceed with registration at www.digiprove.com?  You will receive an email with an activation link. If you do not want to do this now, press Cancel", "dprv_cp") . '\';
			dprv_literals["Processing..."] = \'' . __("Processing...", "dprv_cp") . '\';

			dprv_literals["Subscribers_only_basic"] = \'' . sprintf(__("The %s function is available only to Digiprove subscribers.  %sSelect a subscription plan%s.", "dprv_cp"), "%1\$s", "<a href=\"" . $dprv_upgrade_link . "&Action=Upgrade\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["Subscribers_only_none"] = \'' . __("The %s function is available only to Digiprove subscribers. Please complete registration first.", "dprv_cp") . '\';

			dprv_literals["Subscribers_only_expired"] = \'' . sprintf(__("The %s function is available only to current Digiprove subscribers. Your %s account expired on %s. %sRenew your subscription plan%s.", "dprv_cp"), "%1\$s", $dprv_subscription_type, $dprv_subscription_expiry, "<a href=\"" . $dprv_upgrade_link . "&Action=Renew\" target=\"_blank\">", "</a>") . '\';

			dprv_literals["Premium_only_personal"] = \'' . sprintf(__("The %s function is not available under your current plan (%s).  %sUpgrade your subscription plan%s.", "dprv_cp"), "%1\$s", $dprv_subscription_type, "<a href=\"" . $dprv_upgrade_link . "&Action=Upgrade\" target=\"_blank\">", "</a>") . '\';
			dprv_literals["Premium_only_none"] = \'' . __("The %s function is available only to Digiprove subscribers at Professional level or above. Please complete registration first.", "dprv_cp") . '\';
			dprv_literals["Premium_only_else"] = \'' . sprintf(__("The %s function is available to subscribers at Professional level and above - your current plan is &quot;%s&quot;.  %sUpgrade your subscription plan%s.", "dprv_cp"), "%1\$s", $dprv_subscription_type, "<a href=\"" . $dprv_upgrade_link . "&Action=Renew\" target=\"_blank\">", "</a>") . '\';


			var dprv_enrolled = "' . $dprv_enrolled . '";
			var dprv_subscription_type = "' . $dprv_subscription_type . '";
			var dprv_subscription_expiry = "' . $dprv_subscription_expiry . '";
			var dprv_subscription_expired = "' . $dprv_subscription_expired . '";
			var dprv_upgrade_link = "' . $dprv_upgrade_link . '";
			var dprv_plugin_url = "' . WP_PLUGIN_URL . '";
			var dprv_last_result = "' . $dprv_last_result . '";
			var dprv_lastUserId = document.getElementById(\'dprv_user_id\').value;
			var dprv_lastApiKey = document.getElementById(\'dprv_api_key\').value;
			var dprv_savedApiKey = document.getElementById(\'dprv_api_key\').value;
			var dprv_blog_host = "' . $dprv_blog_host . '";


			// Stuff required to deal with annoying FF3.5 bug
			var dprv_SavedPassword = document.getElementById("dprv_password").value;
			// End of Stuff

			var myPickerText = new jscolor.color(document.getElementById("dprv_notice_color"), {hash:true,pickerPosition:\'left\'});
			myPickerText.fromString("' . $dprv_notice_color . '");  // now you can access API via myPicker variable
			var myPickerHover = new jscolor.color(document.getElementById("dprv_hover_color"), {hash:true,pickerPosition:\'left\'});
			myPickerHover.fromString("' . $dprv_hover_color . '");
			var myPickerBackground = new jscolor.color(document.getElementById("dprv_notice_background"), {hash:true,adjust:false,pickerPosition:\'left\'});
			myPickerBackground.fromString("' . $dprv_notice_background . '");
			var myPickerBorder = new jscolor.color(document.getElementById("dprv_notice_border"), {hash:true,adjust:false,pickerPosition:\'left\'});
			myPickerBorder.fromString("' . $dprv_notice_border . '");
			var dprv_result_message = "' . $result_message . '";

			if (dprv_result_message.indexOf("License") > -1)
			{
				DisplayLicenseTab();
			}

			if (dprv_result_message.indexOf("' . __("Content settings", "dprv_cp") . '") > -1)
			{
				DisplayContentTab();
			}
			Preview();
			PreviewLicense();
			var lastBackgroundColor="";
			var lastBackgroundTextColor="";			
			var lastBorderColor="";
			var lastBorderTextColor="";

			ToggleFooterWarning();
			toggle_r_c_checkbox();
			toggleCredentials();
			if (document.getElementById("dprv_password_row1").style.display == "")
			{
				document.getElementById("dprv_password").focus();
			}
			//]]>
			</script>
		</div>
		');
}

function dprv_options_html($values, $options, $specialOption, $currentValue, $noneValue, $noneText, &$currentMatch)
{
	$log = new DPLog();  
	//$log->lwrite("dprv_options_html starts");

	$optionsHTML = "";
	$currentMatch = 0;
	$specialMatch = 0;

	// Insert a "none-selected" at start of list if requested
	if ($noneText !== false && !empty ($noneText))
	{
		$optionsHTML .= '<option value="' . $noneValue . '"';
		if ($currentValue === false || empty($currentValue) || $currentValue == $noneValue)
		{
			$optionsHTML .= ' selected="selected"';
			$currentMatch = 1;
		}
		$optionsHTML .= '>' . $noneText . '</option>';
	}

	for ($i=0; $i<count($options); $i++)
	{
		$option = $options[$i];
		$option_value = $values[$i];

		$optionsHTML .= '<option value="' . $option_value . '"';
		if ($currentValue == $option_value)
		{
			$optionsHTML .= ' selected="selected"';
			$currentMatch = 1;
		}
		$optionsHTML .= '>' . htmlspecialchars(stripslashes($option), ENT_QUOTES, 'UTF-8') . '</option>';
		if ($specialOption == $option)
		{
			$specialMatch = 1;
		}
	}
	if ($specialMatch == 0 && $specialOption != "")
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
	return $optionsHTML;
}

function dprv_ValidateRegistration()
{
	$log = new DPLog();  
	$log->lwrite("dprv_ValidateRegistration starts");
	if (isset($_POST['dprv_enrolled']) && $_POST['dprv_enrolled'] == "No" && $_POST['dprv_register'] == "Yes")
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
		if (isset($_POST['dprv_enrolled']) && $_POST['dprv_enrolled'] == 'Yes' && (!isset($_POST['dprv_user_id']) || $_POST['dprv_user_id'] == ""))
		{
			return __('Please input your Digiprove User ID', 'dprv_cp');
		}
		if (isset($_POST['dprv_enrolled']) && $_POST['dprv_enrolled'] == "Yes" && isset($_POST['dprv_renew_api_key']) && $_POST['dprv_renew_api_key'] == "on" && (!isset($_POST['dprv_password']) || trim($_POST['dprv_password']) == ""))
		{
			return __('Obtaining an Api key requires you to enter your password', 'dprv_cp');
		}

	}
	if (isset($_POST['dprv_email_address']) && $_POST['dprv_email_address'] != get_option('dprv_email_address'))
	{
		return checkEmail(trim($_POST['dprv_email_address']));
	}
	return "";
}

function checkEmail($email)
{
	if(is_email($email))
	{
		list($username,$domain)=split('@',$email);
		if(!checkdnsrr($domain,'MX'))
		{
			return __("Invalid email address", "dprv_cp");
		}
		return "";
	}
	return __("Invalid email address format", "dprv_cp");
}

function dprv_register_user($dprv_user_id, $dprv_password, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name,$dprv_email_certs, $dprv_can_contact)
{
	global $wp_version, $dprv_blog_host;
	$log = new DPLog();  
	$log->lwrite("register_user starts");  
	
	if ($dprv_user_id == "") return __('Please specify a desired Digiprove user id','dprv_cp');
	if ($dprv_password == "") return __('You need to input a password', 'dprv_cp');
	if (strlen($dprv_password) < 6) return __('Password must be at least 6 characters', 'dprv_cp');
	if ($dprv_email_address == "") return __('Please input your email address (to which the activation link will be sent)', 'dprv_cp');
	if ($dprv_first_name == "" && $dprv_last_name == "") return __('You need to complete either first or last name', 'dprv_cp');

	if (trim($dprv_blog_host) == "")
	{
		return __('Cannot find the URL of your blog - please check Wordpress Settings (General)', 'dprv_cp');
	}

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
	if (isset($dprv_can_contact))
	{
		$postText .= '<can_contact>';
		if ($dprv_can_contact == true)
		{
			$postText .= 'Yes';
		}
		else
		{
			$postText .= 'No';
		}
		$postText .= '</can_contact>';
	}
    $postText .= '<subscription_plan>' . 'Basic' . '</subscription_plan>';
	$dprv_event = get_option('dprv_event');
	if ($dprv_event !== false && $dprv_event != "")
	{
		$postText .= "<dprv_event>" . $dprv_event . "</dprv_event>";
		update_option('dprv_event', '');								// Clear it down
	}
	$postText .= '</digiprove_register_user>';
	$log->lwrite("xml string = " . $postText);

	//TODO try soap_post first, and on exception use http_post
	//$data = dprv_soap_post($postText, "RegisterUser");
	$data = Digiprove_HTTP::post($postText, DPRV_HOST, "/secure/service.asmx/", "RegisterUser");

	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_register_user");
	}
	return $data;  // return;
}
function dprv_update_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_email_address, $dprv_first_name, $dprv_last_name, $dprv_display_name, $dprv_email_certs,$dprv_renew_api_key)
{
	global $wp_version, $dprv_blog_host, $dprv_wp_host;
	$log = new DPLog();
	$log->lwrite("update_user starts"); 

	if ($dprv_user_id == "") return __('Please input your Digiprove User ID','dprv_cp');
	if ($dprv_api_key == null || $dprv_api_key == "")
	{
		if ($dprv_password == "") return __('No password or API key', 'dprv_cp');
		if (strlen($dprv_password) < 6) return __('Password must be at least 6 characters', 'dprv_cp');
	}

	$postText = "<digiprove_update_user>";
	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';
	if ($dprv_blog_host != $dprv_wp_host)
	{
		$postText .= '<alt_domain_name>' . $dprv_wp_host . '</alt_domain_name>';
	}

	$dprv_api_key = get_option('dprv_api_key');
	if ($dprv_api_key != null && $dprv_api_key != "" && $dprv_renew_api_key != "on")
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
	$dprv_event = get_option('dprv_event');
	if ($dprv_event !== false && $dprv_event != "")
	{
		$postText .= "<dprv_event>" . $dprv_event . "</dprv_event>";
		update_option('dprv_event', '');								// Clear it down
	}

	$postText .= '</digiprove_update_user>';

	$log->lwrite("xml string = " . $postText);
	$data = Digiprove_HTTP::post($postText, DPRV_HOST, "/secure/service.asmx/", "UpdateUser");

	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_update_user");
	}
	return $data;
}

function dprv_sync_user($dprv_user_id, $dprv_password, $dprv_api_key, $dprv_renew_api_key)
{
	global $wp_version, $dprv_blog_host, $dprv_wp_host;
	$log = new DPLog();  
	$log->lwrite("sync_user starts");  
	if ($dprv_user_id == "") return __('Please input your Digiprove User ID','dprv_cp');
	if ($dprv_api_key == null || $dprv_api_key == "")
	{
		if ($dprv_password == "") return __('No password or API key', 'dprv_cp');
		if (strlen($dprv_password) < 6) return __('Password must be at least 6 characters', 'dprv_cp');;
	}

	$postText = "<digiprove_sync_user>";
	$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Wordpress ' . $wp_version . ' / Copyright Proof ' . DPRV_VERSION . '</user_agent>';
	$postText .= "<user_id>" . $dprv_user_id . "</user_id>";
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';
	if ($dprv_blog_host != $dprv_wp_host)
	{
		$postText .= '<alt_domain_name>' . $dprv_wp_host . '</alt_domain_name>';
	}

	//$dprv_api_key = get_option('dprv_api_key');
	if ($dprv_api_key != null && $dprv_api_key != "" && $dprv_renew_api_key != "on")
	{
		$postText .= '<api_key>' . $dprv_api_key . '</api_key>';
	}
	else
	{
		$postText .= '<password>' . htmlspecialchars(stripslashes($dprv_password), ENT_QUOTES, 'UTF-8') . '</password>';  // encode password if necessary
		$postText .= '<request_api_key>Yes</request_api_key>';
	}

	$dprv_event = get_option('dprv_event');
	if ($dprv_event !== false && $dprv_event != "")
	{
		$postText .= "<dprv_event>" . $dprv_event . "</dprv_event>";
		update_option('dprv_event', '');								// Clear it down
	}

	$postText .= '</digiprove_sync_user>';

	$log->lwrite("xml string = " . $postText);
	$data = Digiprove_HTTP::post($postText, DPRV_HOST, "/secure/service.asmx/", "SyncUser");

	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_sync_user");
	}
	return $data;  // return;
}


function dprv_resend_activation_email($dprv_user_id, $dprv_email_address)
{
	//global $wp_version, $dprv_host;
	global $wp_version;
	$log = new DPLog();  
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
	$dprv_event = get_option('dprv_event');
	if ($dprv_event !== false && $dprv_event != "")
	{
		$postText .= "<dprv_event>" . $dprv_event . "</dprv_event>";
		update_option('dprv_event', '');								// Clear it down
	}
	$postText .= '</send_activation_email>';

	//$log->lwrite("xml string = " . $postText);
	$data = Digiprove_HTTP::post($postText, DPRV_HOST, "/secure/service.asmx/", "RequestActivationEmail");

	$pos = strpos($data, "Error");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_resend_activation_email");
		return $data;  // return;
	}
	return substr($data, $pos);
}


?>