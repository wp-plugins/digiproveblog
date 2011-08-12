<?php
// FUNCTIONS CALLED WHEN CREATING, OR EDITING POSTS OR PAGES

function dprv_postbox()
{
	$dprv_post_types = explode(',',get_option('dprv_post_types'));
	foreach ($dprv_post_types as $dprv_post_type)
	{
	    add_meta_box('dprv_post_box', __('Copyright / Ownership / Licensing', 'dprv_cp'), 'dprv_show_postbox', $dprv_post_type);
	}
}

function dprv_show_postbox($post_info)
{
	echo ('<script type="text/javascript">
			//<![CDATA[
				window.onerror = function(msg, url, linenumber)
				{
					var alert_string = "Javascript error " + msg + "\nin " + url + "\nat line " + linenumber + "; Please report this error to support@digiprove.com";
					alert(alert_string);
					return true;
				}
			//-->
		</script>');

	global $wpdb, $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs, $post_id;
 	$log = new Logging();  
	$log->lwrite("dprv_show_postbox starts");  
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}

	// SET START VALUES

	// First, set up default values (which will apply if new post or nothing recorded already)
	$dprv_digiprove_this_post = "Yes";
	$dprv_this_all_original = "Yes";
	$dprv_this_attributions = "";
	$dprv_this_default_license = "Yes";
	$dprv_this_license = get_option('dprv_license');
	$dprv_this_license_type = "";
	$dprv_this_license_caption = "";
	$dprv_this_license_abstract = "";
	$dprv_this_license_url = "";
	$dprv_this_custom_license = "No";
	if ($dprv_this_license != 0)
	{
		for ($i=0; $i<count($dprv_licenseIds); $i++)
		{
			if (($dprv_this_license) == $dprv_licenseIds[$i])
			{
				$dprv_this_license_type = $dprv_licenseTypes[$i];
				$dprv_this_license_caption = $dprv_licenseCaptions[$i];
				$dprv_this_license_abstract = $dprv_licenseAbstracts[$i];
				$dprv_this_license_url =  $dprv_licenseURLs[$i];
			}
		}
	}
	$dprv_last_digiprove_info = "Last Digiproved: Never";
	if (stripos($post_info->post_content, "Digiprove_Start") !== false)
	{
		$dprv_last_digiprove_info = "";		// Don't display anything rather than go parsing the notice
	}

	// Secondly, get values previously assigned to this post (if set)
	if ($script_name == "post" || $script_name == "page")	// "post" (or "page" in earlier versions of wp) means we are displaying form for editing an existing post/page ("post-new" is for a new post)
	{
		if (!isset($post_id))
		{
			$log->lwrite("post id not set, using post_info");
			$post_id = $post_info->ID;
		}
		$log->lwrite("not new post, global post_id = " . $post_id);
		
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $post_id;
		$wpdb->show_errors();
		$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
		if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
		{
			if ($dprv_post_info["digiprove_this_post"] == true)
			{
				$dprv_digiprove_this_post = "Yes";
			}
			else
			{
				$dprv_digiprove_this_post = "No";
			}
			if ($dprv_post_info["this_all_original"] == true)
			{
				$dprv_this_all_original = "Yes";
			}
			else
			{
				$dprv_this_all_original = "No";
			}
			$dprv_this_attributions = $dprv_post_info["attributions"];
			if ($dprv_post_info["using_default_license"] == true)
			{
				$dprv_this_default_license = "Yes";
			}
			else
			{
				$dprv_this_default_license = "No";
			}

			if ($dprv_this_default_license != "Yes")										// Default license set to Yes trumps individual settings
			{
				$dprv_this_license = $dprv_post_info["license"];
			}
			$dprv_this_license_type = "";
			$dprv_this_license_caption = __("Some Rights Reserved", "dprv_cp");
			$dprv_this_license_abstract = "";
			$dprv_this_license_url = "";

			$dprv_number = "" . intval($dprv_this_license);									// If dprv_this_license == a non-numeric value 
			if ($dprv_number != $dprv_this_license && $dprv_this_default_license != "Yes")	// and it is not trumped by default license being set to yes, this must be a custom license
			{
				$dprv_this_custom_license = "Yes";
				$dprv_this_license_type = $dprv_this_license; 
				$dprv_this_license_caption = $dprv_post_info["custom_license_caption"];
				$dprv_this_license_abstract = $dprv_post_info["custom_license_abstract"];
				$dprv_this_license_url = $dprv_post_info["custom_license_url"];
			}
			else
			{
				for ($i=0; $i<count($dprv_licenseIds); $i++)
				{
					if (($dprv_this_license) == $dprv_licenseIds[$i])
					{
						$dprv_this_license_type = $dprv_licenseTypes[$i]; 
						$dprv_this_license_caption = $dprv_licenseCaptions[$i];
						$dprv_this_license_abstract = $dprv_licenseAbstracts[$i];
						$dprv_this_license_url =  $dprv_licenseURLs[$i];
					}
				}
			}
			if ($dprv_post_info["certificate_id"] != null && $dprv_post_info["certificate_id"] != "")
			{
				$dprv_last_digiprove_info = "Last Digiproved on " . $dprv_post_info["cert_utc_date_and_time"] . ", certificate " . $dprv_post_info["certificate_id"];
			}
		}
	}
	else
	{
		$log->lwrite("script not = post, post_id = " . $post_id . "; expect this is post-new");
	}

	// OK Values have been set, start preparing HTML
	$dprv_this_yes_checked = " checked='checked'";
	$dprv_this_no_checked = "";
	if ($dprv_digiprove_this_post == 'No')
	{
		$dprv_this_yes_checked = " checked='checked'";
		$dprv_this_no_checked = " checked='checked'";
	}
	$dprv_all_original_yes_checked = " checked='checked'";
	$dprv_all_original_no_checked = "";
	$dprv_attributionDisplay = "none";
	if ($dprv_this_all_original == 'No')
	{
		$dprv_all_original_yes_checked = "";
		$dprv_all_original_no_checked = " checked='checked'";
		$dprv_attributionDisplay = "";
	}
	$default_checked = "";
	$inputDisplay = "none";
	$labelDisplay = "none";
	$selectDisplay = "";
	$custom_checked = "";
	$other_labelDisplay = "";
	if ($dprv_this_default_license == "Yes")
	{
		$default_checked = " checked='checked'";
		$inputDisplay = "none";
		$labelDisplay = "";
		$other_labelDisplay = "";
		$selectDisplay = "none";
	}
	if ($dprv_this_custom_license == "Yes")
	{
		$custom_checked = " checked='checked'";
		$inputDisplay = "";
		$other_labelDisplay = "none";
		$labelDisplay = "none";
		$selectDisplay = "none";
	}
	$post_type_label = $post_info->post_type;
	if ($post_info->post_type != "post" && $post_info->post_type != "page")
	{
		if (function_exists("get_post_types"))
		{
			$all_post_types = get_post_types('','objects');
			$post_type_label = $all_post_types[$post_info->post_type]->labels->singular_name;
		}
	}
	$a = "<table style='padding:6px;  padding-top:0px; width:100%'><tbody>";
	$a .= "<tr><td style='width:160px; height:30px'>Digiprove this " . $post_type_label . "</td><td style='width:300px'><input type='radio' id='dprv_this_yes' name='dprv_this' value='Yes'" . $dprv_this_yes_checked . " onclick='TogglePanel()'/>" . __("Yes", "dprv_cp") . "&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' id='dprv_this_no' name='dprv_this' value='No'" . $dprv_this_no_checked . " onclick='TogglePanel()'/>" . __("No", "dprv_cp") . "</td><td colspan='2'>" . $dprv_last_digiprove_info . "</td></tr>";
	$a .= "</tbody></table>";

	$a .= "<table id='dprv_copyright_panel_body' style='padding:6px; padding-top:0px; width:100%'><tbody>";
	$a .= "<tr><td style='width:160px;'>Is content all yours?</td><td style='width:300px'><input type='radio' id='dprv_all_original_yes' name='dprv_all_original' value='Yes'" . $dprv_all_original_yes_checked . " onclick='ToggleAttributions()'/>" . __("Yes", "dprv_cp") . "&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' id='dprv_all_original_no' name='dprv_all_original' value='No'" . $dprv_all_original_no_checked . " onclick='ToggleAttributions()'/>" . __("No", "dprv_cp") . "</td><td style='width:110px'></td><td style='min-width:110px'></td></tr>";
	$a .= "<tr id='dprv_attributions_0' style='display:" . $dprv_attributionDisplay . "'><td style='height:6px'></td></tr>";
	$a .= "<tr id='dprv_attributions_1' style='display:" . $dprv_attributionDisplay . "'><td valign='top'>" . __("Acknowledgements / Attributions", "dprv_cp") . "</td><td colspan='3'><textarea id='dprv_attributions' name='dprv_attributions' rows='1' style='width:100%'>" . htmlspecialchars(stripslashes($dprv_this_attributions), ENT_QUOTES, 'UTF-8') . "</textarea></td></tr>";
	
	$a .= "<tr><td style='height:6px'></td></tr>";
	$a .= "<tr><td style='height:25px'>" . __("License Type", "dprv_cp") . "</td><td>";
	$a .= "<span id='dprv_this_license_label' style='display:" . $labelDisplay . "'>" . htmlspecialchars(stripslashes($dprv_this_license_type), ENT_QUOTES, 'UTF-8') . "</span>";
	$a .= "<select id='dprv_license_type' name='dprv_license_type' style='width:280px;display:" . $selectDisplay . "' onchange='LicenseChanged();'>";
	$selected="";
	if ($dprv_this_license =="0")
	{
			$selected=" selected='selected'";
	}
	$a .= "<option value='0'" . $selected . ">None</option>";
	for ($i=0;$i<count($dprv_licenseIds);$i++)
	{
		$selected="";
		if ($dprv_this_license == $dprv_licenseIds[$i])
		{
			$selected=" selected='selected'";
		}
		$a .= "<option value='" . htmlspecialchars($dprv_licenseIds[$i], ENT_QUOTES, 'UTF-8') . "'" . $selected . ">" . htmlspecialchars(stripslashes($dprv_licenseTypes[$i]), ENT_QUOTES, 'UTF-8') . "</option>";
	}
	$a .= "</select>";
	$a .= "<input type='text' id='dprv_license_input' name='dprv_license_input' value='" .  htmlspecialchars(stripslashes($dprv_this_license_type), ENT_QUOTES, 'UTF-8') . "' style='width:280px; display:" . $inputDisplay . "' /></td>";
	$a .= "<td>";
	$a .= "<input type='checkbox' id='dprv_default_license' name='dprv_default_license' onclick='ToggleDefault();'" . $default_checked . "/>&nbsp;" . __("Use Default", "dprv_cp") . "</td><td>";
	$a .= "<input type='checkbox' id='dprv_custom_license' name='dprv_custom_license' onclick='ToggleCustom();'" . $custom_checked . "/>&nbsp;" . __("Custom&nbsp;for&nbsp;this&nbsp;post", "dprv_cp");
	$a .= "</td></tr>";
	$a .= "<tr><td style='height:6px'></td></tr>";
	$a .= "<tr><td>" . __("License Caption", "dprv_cp") . "</td>";
	$a .= "<td colspan='3'>";
	$a .= "<select id='dprv_license_caption' name='dprv_license_caption' style='width:200px; display:" . $inputDisplay . "'>";
	$selected = "";
	if ($dprv_this_license_caption ==  __("Some Rights Reserved", "dprv_cp"))
	{
		$selected = " selected='selected'";
	}
	$a .= "<option value='" . __("Some Rights Reserved", "dprv_cp") . "'" . $selected . ">" .  __("Some Rights Reserved", "dprv_cp") . "</option>";
	$selected = "";
	if ($dprv_this_license_caption ==  __("All Rights Reserved", "dprv_cp"))
	{
		$selected = " selected='selected'";
	}
	$a .= "<option value='" . __("All Rights Reserved", "dprv_cp") . "'" . $selected . ">" . __("All Rights Reserved", "dprv_cp") . "</option>";
	$a .= "</select>";
	$a .= "<span id='dprv_license_caption_label' style='width:100%; display:" . $other_labelDisplay . "'>" . htmlspecialchars($dprv_this_license_caption, ENT_QUOTES, 'UTF-8') . "</span>";
	$a .= "</td></tr>";
	
	
	$a .= "<tr><td style='height:6px'></td></tr>";
	$a .= "<tr><td valign='top'>" . __("License Abstract", "dprv_cp") . "</td>";
	$a .= "<td colspan='3'>";
	$a .= "<textarea id='dprv_license_abstract' name='dprv_license_abstract' rows='2' style='width:100%; display:" . $inputDisplay . "'>" . htmlspecialchars(stripslashes($dprv_this_license_abstract), ENT_QUOTES, 'UTF-8') . "</textarea>"; 	
	$a .= "<span id='dprv_license_abstract_label' style='width:100%; display:" . $other_labelDisplay . "'>" . htmlspecialchars(stripslashes($dprv_this_license_abstract), ENT_QUOTES, 'UTF-8') . "</span>";
	$a .= "</td></tr>";
	$a .= "<tr><td style='height:6px'></td></tr>";
	$a .= "<tr><td>" . __("License URL", "dprv_cp") . "</td><td colspan='3'>";
	$a .= "<input id='dprv_license_url_input' name='dprv_license_url_input' value='" . htmlspecialchars(stripslashes($dprv_this_license_url), ENT_QUOTES, 'UTF-8') . "' style='width:100%;display:" . $inputDisplay . "'/>";
	$a .= "<a id='dprv_license_url_link' name='dprv_license_url_link' href='" . $dprv_this_license_url . "' target='_blank' style='display:" . $other_labelDisplay . "'>" . htmlspecialchars(stripslashes($dprv_this_license_url), ENT_QUOTES, 'UTF-8') . "</a>";
	$a .= "</td></tr>";
	$a .= "</tbody></table>";
	echo $a;
	
	$dprv_home = get_settings('siteurl');
	$jsfile = $dprv_home.'/wp-content/plugins/digiproveblog/copyright_proof_cr_panel.js?v='.DPRV_VERSION;
	echo('<script type="text/javascript" src="' . $jsfile . '"></script>');

	// Following required for correct management of F5 refresh
	echo ("<script type='text/javascript'>
				<!--
				// These functions all contained in copyright_proof_cr_panel.js (loaded just above)
				TogglePanel();
				ToggleAttributions();
				ToggleDefault();
				SetLicense();
				ToggleCustom();
				//-->
			</script>");
	return $post_info;
}

function dprv_add_digiprove_submit_button()
{
	global $post;

	$dprv_post_types = explode(',',get_option('dprv_post_types'));

	if (array_search($post->post_type, $dprv_post_types) === false)
	{
		return;
	}
	if ($post->post_type == "page")
	{
		$can_publish = current_user_can("publish_pages");
	}
	else
	{
		$can_publish = current_user_can("publish_posts");
	}


	echo ('<input name="dprv_publish_dp_action" type="hidden" id="dprv_publish_dp_action" value="No" />');
	$dprv_publish_text = "default";
	if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID )
	{
		if ($can_publish)
		{
			if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) )
			{
				$dprv_publish_text = __('Schedule & Digiprove', 'dprv_cp');
			}
			else
			{
				$dprv_publish_text = __('Publish &amp; Digiprove', 'dprv_cp');
			}
		}
		else
		{
			$dprv_publish_text = __('Digiprove &amp; Submit for Review', 'dprv_cp');
		}
	}
	else
	{
		$dprv_publish_text = __('Update &amp; Digiprove', 'dprv_cp');
	}
	
	if ($dprv_publish_text != __('Digiprove &amp; Submit for Review', 'dprv_cp'))    // At least for now, don't have Digiproving of contributor submissions
	{
		echo ('<input name="save" type="submit" class="button-primary" id="publish_dp" tabindex="5" onclick="return set_dprv_action()" value="' . $dprv_publish_text . '" style="float:right"/>');
		echo ('<script type="text/javascript">
				function set_dprv_action()
				{
					document.getElementById("dprv_publish_dp_action").value = "Yes";
					document.getElementById("publish").click();
					return false;
				}
				function renameButton(newval)
				{
					if (newval == "Schedule")
					{
						document.getElementById("publish_dp").value = "' . __('Digiprove & Schedule', 'dprv_cp') . '";
					}
					else
					{
						document.getElementById("publish_dp").value = newval + "' . __(' & Digiprove', 'dprv_cp') . '";
					}
				}
				// TODO: Find a less clunky way to do this
				// Trace JS code which changes value of publish element (i.e. when user chooses a future publication
				if (navigator.userAgent.indexOf("MSIE") == -1)
				{
					if (window.watch)
					{
						function dprv_timestamp_changed(property, oldval, newval)
						{
							renameButton(newval);
							return newval;
						}
						setTimeout(\'document.getElementById("publish").watch("value", dprv_timestamp_changed)\',1500);
					}
					else
					{
						function dprv_value_might_be_changed()
						{
							var newval = document.getElementById("publish").value;
							if (newval != dprv_oldval)
							{
								renameButton(newval);
							}
							dprv_oldval = newval;
							return true;
						}
						var dprv_oldval = "unknown"; 
						var dprv_i = setInterval("dprv_value_might_be_changed()", 1000);
					}
				}
				else
				{
					function AddOnchangeEvent()
					{
						document.getElementById("publish").onpropertychange = function onpropertychange(){dprv_property_changed()};
					}
					function dprv_property_changed()
					{
						renameButton(document.getElementById("publish").value);
						return true;
					}
					setTimeout(\'AddOnchangeEvent();\',500);
				}
				</script>');
	}
}

// Write Copyright Panel details
// Examine post content prior to Digiproving
// Remove old notice if there, and write information from it into custom post fields 
function dprv_parse_post ($data, $raw_data)
{
	//global $wpdb, $dprv_digiprove_this_post, $post_id, $post_ID;
	global $wpdb, $dprv_digiprove_this_post;
	$log = new Logging();  
	$log->lwrite("dprv_parse_post starts"); 
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}
	if ($script_name == "admin-ajax")
	{
		$log->lwrite("parse_post not starting because this is ajax");
		return $data;
	}
	//$log->lwrite("data[post_status] = " . $data['post_status']);
	if ($data['post_status'] != "publish" && $data['post_status'] != "private" && $data['post_status'] != "future")
	{
		if ($data['post_status'] == "draft" && $_POST['dprv_publish_dp_action'] == "Yes")
		{
			$log->lwrite("draft but user has explicitly requested Digiproving, then we can proceed beyond this point");
			// If the status is set to draft but user has explicitly requested Digiproving, then we can proceed beyond this point
		}
		else
		{
			$log->lwrite("dprv_parse_post not starting because status (" . $data['post_status'] . ") is not publish, private or future");
			return $data;
		}
	}
	//$log->lwrite("data['post_type'] = " . $data['post_type']);
	//if ($data['post_type'] != "post" && $data['post_type'] != "page")	
	$dprv_post_types = explode(',',get_option('dprv_post_types'));
	if (array_search($data['post_type'], $dprv_post_types) === false)
	{
		$log->lwrite("dprv_parse_post not starting because type (" . $data['post_type'] . ") is not " . get_option('dprv_post_types'));
		return $data;
	}

	if (get_option('dprv_enrolled') != "Yes")
	{
		$log->lwrite("dprv_parse_post not starting because user not registered yet");
		return $data;
	}
	$content = trim($data['post_content']);
	if (strlen(trim($content)) == 0)
	{
		$log->lwrite("dprv_parse_post not starting because content is empty");
		return $data;
	}

	$dprv_post_id = $raw_data['ID'];
	if (intval($dprv_post_id) == 0)
	{
		$log->lwrite("dprv_post_id no good, = " . $dprv_post_id);  
		$dprv_post_id = -1;
	}
	
	$dprv_digiprove_this_post = $_POST['dprv_this'];
	if ($dprv_digiprove_this_post == "No")
	{
		$log->lwrite("dprv_parse_post not starting - digiprove_this_post set to No for post Id " . $dprv_post_id);
		if ($dprv_post_id != -1)
		{
			$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
			//$wpdb->show_errors();
			$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
			$log->lwrite("count(dprv_post_info) = " . count($dprv_post_info));
			if (count($dprv_post_info) == 0)
			{
				// create record
				if (false === $wpdb->insert(get_option('dprv_prefix') . "dprv_posts", array('digiprove_this_post'=>false, 'this_all_original'=>true, 'using_default_license'=>true, 'id'=>$dprv_post_id), array('%f','%f')))
				{
					update_option('dprv_event', 'error ' . $wpdb->last_error . ' inserting no-digiprove for ' . $dprv_post_id);
				}
			}
			else
			{
				//$wpdb->show_errors();
				if (false === $wpdb->update(get_option('dprv_prefix') . "dprv_posts", array('digiprove_this_post'=>false), array('id'=>$dprv_post_id), '%f', '%f'))
				{
					update_option('dprv_event', 'error ' . $wpdb->last_error . ' updating no-digiprove for ' . $dprv_post_id);
				}

			}
		}
		return $data;
	}

	update_option('dprv_last_action', 'Digiprove id=' . $dprv_post_id);     // Why?
	$dprv_title = $data['post_title'];
	//$log->lwrite("title=" . $dprv_title . ", id=" . $dprv_post_id);  
	$log->lwrite("");  
	$log->lwrite("dprv_parse_post STARTS");  
	// if post_id = -1, means new post coming from xmlrpc (or postie) - in either case there is no copyright panel so no problem that this function does not get executed
	if ($dprv_post_id != -1  && isset($_POST['dprv_this']))
	{
		// Didn't bother combining this with operation below (of writing old notice onto record) as it really only occurs once for each old post
		dprv_record_copyright_details($dprv_post_id);
	}

	$dprv_publish_dp_action = $_POST['dprv_publish_dp_action'];
	if ($dprv_publish_dp_action == "No")
	{
		$log->lwrite("dprv_parse_post ending after writing copyright panel details but before the Digiproving bit - user selected publish/update without Digiprove for post Id " . $dprv_post_id);
		update_option('dprv_last_result', '');
		return $data;
	}

	// Remove old-style notice (if there is one there) and return the core information from it 
	dprv_strip_old_notice($content, $dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, $dprv_first_year);

	// If there was an old-style notice, transfer it to db
	if ($dprv_post_id != -1 && $dprv_certificate_id !== false && $dprv_certificate_id != "" )
	{
		$dprv_new_certificate_id = false;
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
		//$wpdb->show_errors();
		$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
		if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
		{
			$dprv_new_certificate_id = $dprv_post_info["certificate_id"];
			$log->lwrite("a row already exists for this post");				
		}

		// If (as expected) nothing yet on db, record the information from the notice into the db 
		if ($dprv_new_certificate_id == false || $dprv_new_certificate_id == "")
		{
			$dprv_event = get_option('dprv_event');
			if (count($dprv_post_info) > 0)
			{
				if ($dprv_post_info["first_year"] == null)
				{
					$log->lwrite("first year is null");
				}
				else
				{
					$dprv_first_year = intval($dprv_post_info["first_year"]);
				}
				//$wpdb->show_errors();
				if (false === $wpdb->update(get_option('dprv_prefix') . "dprv_posts", array('certificate_id'=>$dprv_certificate_id, 'certificate_url'=>$dprv_certificate_url, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>intval($dprv_first_year)), array('id'=>$dprv_post_id)))
				{
					update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' writing old notice ' . $dprv_post_id . ' to db');
				}
			}
			else
			{	
				//$wpdb->show_errors();
				if (false === $wpdb->insert(get_option('dprv_prefix') . 'dprv_posts', array('id'=>$dprv_post_id, 'digiprove_this_post'=>true, 'certificate_id'=>$dprv_certificate_id, 'certificate_url'=>$dprv_certificate_url, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>intval($dprv_first_year))))
				{
					update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' inserting old notice to db');
				}
			}
		}
	}

	$data['post_content'] = trim($content);
	return $data;
}


function dprv_digiprove_post($dprv_post_id)
{
	// This function executes after the post has been created/updated and we have a post id
	// So we can read copyright options (or default if none there) and Digiprove content if appropriate
	// Also record details of Digiprove action
	
	$log = new Logging();  
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}
	$post_action = "";
	if (isset($_POST["action"]))
	{
		$post_action = $_POST["action"];
	}
	$post_record = get_post($dprv_post_id);
	$log->lwrite("starting dprv_digiprove_post (" . $post_action . ") " . $dprv_post_id . ", status=" . $post_record->post_status);
	// Values seen for POST[action]:	empty					when (script = wp-cron)
	//															or   (script = post-new    && status = auto-draft)
	//															or   (script = xmlrpc-post && status = ? publish?)
	//															or   (script = xmlrpc-post && status = inherit)
	//									autosave				when (script = admin-ajax  && status = draft)
	//									editpost				when (script = post        && status = inherit)
	//															or	 (script = post        && status = publish)
	//															or	 (script = post        && status = future)
	//															or	 (script = post        && status = draft)  (only seen so far on Opera on future-dated posts)
	//									editpost				when (script = page        && status = inherit)     on WP 2.7
	//															or	 (script = page        && status = publish)     on WP 2.7
	//                                  runpostie				when (script = options-general  && status = draft)
	//															or   (script = options-general  && status = ? publish?)
	//									post-quickpress-save	when (script = post				&& status = auto-draft)
	//
	// and with combinations of status = private

	if ($script_name == "wp-cron")
	{
		$log->lwrite("dprv_digiprove_post not starting because script is wp-cron - any digiproving has been done already");
		return;
	}

	if ($post_record->post_status != "publish" && $post_record->post_status != "private"  && $post_record->post_status != "future")
	{
		$log->lwrite("dprv_digiprove_post not starting because status (" . $post_record->post_status . ") is not publish, private or future");
		return;
	}

	//if ($post_record->post_type != "post" && $post_record->post_type != "page")  // TODO: && $post_record->post_type != "xxxxx"
	$dprv_post_types = explode(',',get_option('dprv_post_types'));
	if (array_search($post_record->post_type, $dprv_post_types) === false)
	{
		$log->lwrite("dprv_digiprove_post not starting because type (" . $post_record->post_type . ") is not " . get_option('dprv_post_types'));  // Does this ever occur?
		return;
	}

	if (get_option('dprv_enrolled') != "Yes")
	{
		$log->lwrite("dprv_digiprove_post not starting because user not registered yet");
		return;
	}
	$content = trim($post_record->post_content);
	if (strlen(trim($content)) == 0)
	{
		$log->lwrite("dprv_digiprove_post not starting because content is empty");
		return;
	}

	$dprv_publish_dp_action = $_POST['dprv_publish_dp_action'];
	if ($dprv_publish_dp_action == "No")
	{
		$log->lwrite("dprv_digiprove_post not starting - user selected publish/update without Digiprove for post Id " . $dprv_post_id);
		update_option('dprv_last_result', '');
		return;
	}


	$dprv_digiprove_this_post = $_POST['dprv_this'];
	if ($dprv_digiprove_this_post == "No")
	{
		$log->lwrite("dprv_digiprove_post not starting - digiprove_this_post set to No for post Id " . $dprv_post_id);
		update_option('dprv_last_result', '');
		return;
	}

	if ($dprv_digiprove_this_post != "Yes")
	{
		$log->lwrite("dprv_digiprove_this_post = " . $dprv_digiprove_this_post);
		global $wpdb;
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
		//$wpdb->show_errors();
		$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
		if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
		{
			if ($dprv_post_info["digiprove_this_post"] == false)
			{
				$log->lwrite("dprv_digiprove_post not starting - recorded value for digiprove_this_post set to false for post Id " . $dprv_post_id);
				update_option('dprv_last_result', '');
				return;
			}
		}

	}

	$today_count = 0;	// default value
	if (get_option('dprv_last_date') != date("Ymd"))
	{
		update_option('dprv_last_date', date("Ymd"));
	}
	else
	{
		$today_count = intval(get_option('dprv_last_date_count'));
	}

	$today_count += 1;
	update_option('dprv_last_date_count', $today_count);

	$dprv_subscription_expiry = get_option('dprv_subscription_expiry');
	$dprv_subscription_type = get_option('dprv_subscription_type');
	if ($today_count > 30)
	{
		$today_limit = 30;
		switch ($dprv_subscription_type)
		{
			case "Personal":
			{
				$today_limit = 60;
				break;
			}
			case "Professional":
			{
				$today_limit = 250;
				break;
			}
			case "Corporate Light":
			{
				$today_limit = 1000;
				break;
			}
			case "Corporate":
			{
				$today_limit = -1;
				break;
			}
			default:
			{
				break;
			}
		}

		if ($today_count > $today_limit && $today_limit != -1)
		{
			// NOTE - if changing the "Digiprove daily limit" text, also modify  dprv_admin_footer() which relies on this exact text
			update_option('dprv_last_result', "Digiprove daily limit (" . $today_limit . ") for " . get_option('dprv_subscription_type') . " accounts already reached, you can <a href='" . 	createUpgradeLink() . "&Action=Upgrade' target='_blank'>upgrade to increase this limit</a>.");
			return;
		}
		//$dprv_subscription_expiry = get_option('dprv_subscription_expiry');
		$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 86400;					// Add 24 hour grace period (also handles any unforeseen timezone issues)
		if ($dprv_expiry_timestamp != false && $dprv_expiry_timestamp != -1 && time() > $dprv_expiry_timestamp)
		{
			// NOTE - if changing the "Digiprove free daily limit" text, also modify  dprv_admin_footer() which relies on this exact text
			update_option('dprv_last_result', "Digiprove free daily limit (30) already reached, and your Digiprove account expired on " . $dprv_subscription_expiry . ". You can <a href='" . 	createUpgradeLink() . "&Action=Renew' target='_blank'>renew your account here</a>");
			return;
		}
	}


	$log->lwrite("dprv_digiprove_post STARTS");

	//update_option('dprv_last_result', '');
	$newContent = stripslashes($content);
	$notice = "";
	$certifyResponse = dprv_certify($dprv_post_id, $post_record->post_title, $newContent, $raw_content_hash, $dprv_subscription_type, $dprv_subscription_expiry, $dprv_last_time, $notice);
	$log->lwrite("response: $certifyResponse");
	if (strpos($certifyResponse, "Hashes are identical") === false)
	{
		if (strpos($certifyResponse, "Raw content is empty") === false)
		{
			$pos = stripos($certifyResponse, "<result_code>0");
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
				if ($dprv_api_key != false  && $dprv_api_key != "")
				{
					update_option('dprv_api_key', $dprv_api_key);
					delete_option('dprv_password');
				}
				else
				{
					// If there was a working api key, delete any recorded password from the options db
					$dprv_api_key = get_option('dprv_api_key');
					if ($dprv_api_key != false && $dprv_api_key != "")
					{
						delete_option('dprv_password');
					}
				}
				// End of API key code


				$dprv_subscription_type = dprv_getTag($certifyResponse, "subscription_type");
				if ($dprv_subscription_type != null && $dprv_subscription_type != false && $dprv_subscription_type != "")
				{
					update_option('dprv_subscription_type', $dprv_subscription_type);
					$dprv_subscription_expiry = dprv_getTag($certifyResponse, "subscription_expiry");
					if ($dprv_subscription_expiry != null && $dprv_subscription_expiry != false && $dprv_subscription_expiry != "")
					{
						update_option('dprv_subscription_expiry', $dprv_subscription_expiry);
					}
					else
					{
						update_option('dprv_subscription_expiry', '');
					}
				}

				dprv_record_dp_action($dprv_post_id, $certifyResponse, $post_record->post_status, $raw_content_hash);

				// Surely the following bit is unnecessary?  Check and delete:			
				if (get_option('dprv_enrolled') != "Yes")
				{
					update_option('dprv_enrolled', 'Yes');
				}
				// end of unnecessary bit

				$log->lwrite("Digiproving completed successfully");

				update_option('dprv_last_result', __('Digiprove certificate id:', 'dprv_cp') . ' ' . dprv_getTag($certifyResponse, "certificate_id") . ' ' . $notice);
			}
		}
		else
		{
			// The only real content was the last Digiprove certificate; remove it
			update_option('dprv_last_result', __('Content is empty', 'dprv_cp'));
			return;		
		}
	}
	else
	{
		// Doing this check because of weird situation where if an attachment is referred to within the content,
		// wp_insert_post gets fired twice with apparently identical variables.  Thus the 2nd time (correctly) does
		// not Digiprove becauses hashes are same, however, user does not see Digiprove Certificate ID message
		// TODO: Check that this works across timezones (should do because time() gives utc)
		$this_time = time();
		$time_since_last = $this_time - $dprv_last_time;
		if ($time_since_last > 2)		// only if last one occurred more than 2 seconds ago (arguably make this a bit longer)
		{
			update_option('dprv_last_result', __('Content unchanged since last edit', 'dprv_cp'));
		}
	}

	$log->lwrite("finishing dprv_digiprove_post " . $dprv_post_id);
	return;
}

function dprv_strip_old_notice(&$content, &$dprv_certificate_id, &$dprv_utc_date_and_time, &$dprv_digital_fingerprint, &$dprv_certificate_url, &$dprv_copyright_year)
{
	$log = new Logging();  
	//$log->lwrite("dprv_strip_old_notice starts");
	$certificate_info = "";
	$start_Digiprove = strpos($content, "<!--Digiprove_Start-->");
	$end_Digiprove = false;
	if ($start_Digiprove === false)
	{
		//$log->lwrite("did not find start_Digiprove marker");
		return;
	}
	$end_Digiprove = strpos($content, "<!--Digiprove_End-->");
	if ($end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		//$log->lwrite("did not find end_Digiprove marker");
		return;
	}
	$posA = stripos($content, "<span", $start_Digiprove + 22);
	$posA2 = stripos($content, "<div", $start_Digiprove + 22);

	if ($posA === false && $posA2 === false)
	{
		//$log->lwrite("no span or div marker found");
		return;
	}
	if ($posA === false || ($posA2 !== false && $posA2 < $posA))
	{
		$posA = $posA2;
	}
	$Digiprove_notice = stripslashes(substr($content, $posA, ($end_Digiprove - $posA)));
	$log->lwrite("notice=" . $Digiprove_notice);

	// Find Date of Certification
	$posA = stripos($Digiprove_notice, "title=\"certified");
	$log->lwrite("just did another stripos, posA = " . $posA);
	$posB = stripos($Digiprove_notice, " UTC by Digiprove certificate ");

	if ($posA === false || $posB === false || $posA >= $posB)
	{
		$log->lwrite("No text UTC by Digiprove certificate");
		return;
	}
	$dprv_utc_date_and_time = substr($Digiprove_notice, $posA + 16, ($posB-$posA-12));

	// Find Certificate ID
	$posA = $posB + 30;
	$remainder = substr($Digiprove_notice, $posA);
	$posB = strpos($remainder, "\"");
	if ($posB === false)
	{
		$log->lwrite("couldnt find qm in remainder");
		return;
	}
	$dprv_certificate_id = substr($remainder, 0, $posB);
	
	// Find Certificate Url
	$posA = stripos($Digiprove_notice, "href=");
	if ($posA === false)
	{
		$log->lwrite("couldnt find href= in notice");
		return;
	}
	$remainder = substr($Digiprove_notice, $posA);
	$log->lwrite("about to call strpbrk");
	$remainder = strpbrk($remainder, '\'\"');
	if ($remainder === false)
	{
		$log->lwrite("could not find starting delimiter");
		return;
	}

	$delimiter = substr($remainder,0,1);
	$remainder = substr($remainder,1);
	$posB = strpos($remainder, $delimiter);

	if ($posB === false)
	{
		$log->lwrite("could not find finishing delimiter");
		return;
	}
	$dprv_certificate_url = substr($remainder, 0, $posB);

	// Find year of certification
	$posA = stripos($Digiprove_notice, "&copy;");
	if ($posA === false)
	{
		$posA = strpos($Digiprove_notice, chr(169));
	}
	if ($posA !== false)
	{
		$remainder = substr($Digiprove_notice, $posA + 2);
		$posA = strpos($remainder, "20");		// creating y2.1k problem (for someone else)
		if ($posA !== false)
		{
			$dprv_copyright_year = substr($remainder, $posA, 4);

			// Find Name
			$posB = stripos($remainder, "</");	// was "</span>"
			if ($posB !== false && $posB > ($posA + 4))
			{
				$name = substr($remainder, $posA+5, $posB-$posA-5);
			}
			else
			{
				$log->lwrite("could not find name");
			}
		}
		else
		{
			$log->lwrite("could not find year, didn't look for name");	// Bit odd
		}
	}
	else
	{
		$log->lwrite("could not find c symbol, did not search for year or name");
	}


	// Find Digital Fingerprint
	$posA = strpos($remainder, "<!--");
	if ($posA !== false)
	{
		$remainder = substr($remainder, $posA + 4);
		$posB = strpos($remainder, "-->");
		if ($posB !== false)
		{
			$dprv_digital_fingerprint = substr($remainder, 0, $posB);
		}
		else
		{
			$log->lwrite("could not find end marker for digital fingerprint");
		}
	}
	else
	{
		$log->lwrite("could not find start marker for digital fingerprint");
	}

	// Remove old notice
	if ($start_Digiprove != 0)
	{
		$content = substr($content, 0, $start_Digiprove) . substr($content, $end_Digiprove + 20);
	}
	else
	{
		$content = substr($content, $end_Digiprove + 20);
	}
	$log->lwrite("successfully parsed old notice and removed it");
	return;
}

function dprv_record_copyright_details($dprv_post_id)
{
	global $wpdb;
	$log = new Logging();
	$log->lwrite("dprv_record_copyright_details starts");

	if ($_POST['dprv_this'] == "Yes")
	{
		$digiprove_this_post = true;
	}
	else
	{
		$digiprove_this_post = false;
	}

	if ($_POST['dprv_all_original'] == "Yes")
	{
		$this_all_original = true;
	}
	else
	{
		$this_all_original = false;
	}
	$attributions = $_POST['dprv_attributions'];

	$using_default_license = true;
	if (!isset($_POST['dprv_default_license']) || $_POST['dprv_default_license'] != "on")
	{
		$using_default_license = false;
	}

	//if ($_POST['dprv_custom_license'] != "on")
	if (!isset($_POST['dprv_custom_license']) || $_POST['dprv_custom_license'] != "on")
	{
		$license = $_POST['dprv_license_type'];
		$custom_license_caption = null;
		$custom_license_abstract = null;
		$custom_license_url = null;
	}
	else
	{
		$license = $_POST['dprv_license_input'];
		$custom_license_caption = $_POST['dprv_license_caption'];
		$custom_license_abstract = $_POST['dprv_license_abstract'];
		$custom_license_url = $_POST['dprv_license_url_input'];
	}

	$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
	//$wpdb->show_errors();
	$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
	if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
	{
		//$wpdb->show_errors();
		if (false === $wpdb->update(get_option('dprv_prefix') . "dprv_posts", array('digiprove_this_post'=>$digiprove_this_post, 'this_all_original'=>$this_all_original, 'attributions'=>$attributions, 'using_default_license'=>$using_default_license, 'license'=>$license, 'custom_license_caption'=>$custom_license_caption, 'custom_license_abstract'=>$custom_license_abstract, 'custom_license_url'=>$custom_license_url), array('id'=>$dprv_post_id)))
		{
			update_option('dprv_event', 'error ' . $wpdb->last_error . ' updating copyright details for ' . $dprv_post_id);
		}
	}
	else
	{
		//$wpdb->show_errors();
		if (false === $wpdb->insert(get_option('dprv_prefix') . "dprv_posts", array('id'=>$dprv_post_id, 'digiprove_this_post'=>$digiprove_this_post, 'this_all_original'=>$this_all_original, 'attributions'=>$attributions, 'using_default_license'=>$using_default_license,  'license'=>$license, 'custom_license_caption'=>$custom_license_caption, 'custom_license_abstract'=>$custom_license_abstract, 'custom_license_url'=>$custom_license_url)))
		{
			update_option('dprv_event', 'error ' . $wpdb->last_error . ' inserting copyright details for ' . $dprv_post_id);
		}

	}
}

function dprv_record_dp_action($dprv_post_id, $certifyResponse, $dprv_post_status, $raw_content_hash)
{
	global $wpdb;
	$log = new Logging();  
	$log->lwrite("dprv_record_dp_action starts. post id = " . $dprv_post_id);
	$dprv_certificate_id = dprv_getTag($certifyResponse, "certificate_id");
	$dprv_certificate_url = dprv_getTag($certifyResponse, "certificate_url");
	$dprv_utc_date_and_time = dprv_getTag($certifyResponse, "utc_date_and_time");

	$dprv_digital_fingerprint = dprv_getTag($certifyResponse, "digital_fingerprint");
	// If this is PHP 5.1.2 or later, we already have digital fingerprint, use this if not supplied by server
	if ($dprv_digital_fingerprint === false || $dprv_digital_fingerprint == "")
	{
		$dprv_digital_fingerprint = $raw_content_hash;
	}
	$posA = strpos($dprv_utc_date_and_time, " 20");	// crude way of finding year
	$dprv_year_certified = 2010;  // default
	if ($posA != false)
	{
		$dprv_year_certified = intval(substr($dprv_utc_date_and_time, $posA+1, 4));
	}

	$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $dprv_post_id;
	$dprv_event = get_option('dprv_event');
	//$wpdb->show_errors();
	$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
	if (!is_null($dprv_post_info) && count($dprv_post_info) > 0)
	{
		if ($dprv_post_info["first_year"] == null)
		{
			$log->lwrite("first year is null");
		}
		else
		{
			$dprv_year_certified = intval($dprv_post_info["first_year"]);
		}
		//$wpdb->show_errors();
		if (false === $wpdb->update(get_option('dprv_prefix') . "dprv_posts", array('certificate_id'=>$dprv_certificate_id, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'certificate_url'=>$dprv_certificate_url, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>$dprv_year_certified), array('id'=>$dprv_post_id)))
		{
			update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' updating dp action for ' . $dprv_post_id . ', status ' . $dprv_post_status);
		}
	}
	else
	{
		//$wpdb->show_errors();
		if (false === $wpdb->insert(get_option('dprv_prefix') . "dprv_posts", array('id'=>$dprv_post_id, 'digiprove_this_post'=>true, 'this_all_original'=>true,'using_default_license'=>true, 'certificate_id'=>$dprv_certificate_id, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'certificate_url'=>$dprv_certificate_url, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>$dprv_year_certified)))
		{
			update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' inserting dp action for ' . $dprv_post_id . ', status ' . $dprv_post_status);
		}
	}
}

function dprv_certify($post_id, $title, $content, &$raw_content_hash, $dprv_subscription_type, $dprv_subscription_expiry, &$dprv_last_time,&$notice)
{
	$log = new Logging();  
	global $wp_version, $wpdb;

	$log->lwrite("dprv_certify starts");

	$rawContent = dprv_getRawContent($post_id, $content, $raw_content_hash);

	if ($raw_content_hash != "")
	{
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $post_id;
		//$wpdb->show_errors();
		$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);
		if (!is_null($dprv_post_info) && count($dprv_post_info) > 0 && $raw_content_hash == $dprv_post_info["digital_fingerprint"])
		{
			$dprv_last_time = strtotime($dprv_post_info["cert_utc_date_and_time"]);
			return " Hashes are identical";	// Content has not changed, do not Digiprove again - stick with earlier certification
		}
	}
	if ($rawContent == "")
	{
		return " Raw content is empty";
	}

	$dprv_subscription_expired = "No";
	//$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 86400;			// add 24-hour grace period (also allows for any unforeseen timezone issues)
	$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 345600;			// add 4-day grace period (also allows for any unforeseen timezone issues)
	if ($dprv_expiry_timestamp != false && $dprv_expiry_timestamp != -1 && time() > $dprv_expiry_timestamp)
	{
		$dprv_subscription_expired = "Yes";
	}

	// code below changed at 0.78 to avoid empty domain
	$dprv_blog_url = parse_url(get_option('home'));
	$dprv_blog_host = $dprv_blog_url['host'];
	$dprv_wp_host = "";		// default

	$dprv_wp_url = parse_url(get_option('siteurl'));
	$dprv_wp_host = $dprv_wp_url['host'];
	if (trim($dprv_blog_host) == "")
	{
		$dprv_blog_host = $dprv_wp_host;
	}
	$content_file_names = array();
	$content_file_fingerprints = array();
	if (function_exists("hash"))
	{
		getContentFiles($rawContent, $dprv_blog_host, $content_file_names, $content_file_fingerprints);
	}
	$rawContent = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');
	// Statement below inserted at 0.75 as vertical tabs not converted and cause a problem in XML .net server process
	// TODO: there are probably other characters that will trip it up - review whole XML-encoding to create more systemic solution
	$rawContent = str_replace("\v", " ", $rawContent);			// vertical tab           11 0013 0x0b
	$rawContent = str_replace(chr(1), '&#x1;', $rawContent);	// soh - start of header   1 0001 0x01
	$rawContent = str_replace(chr(22), ' ', $rawContent);		// SYN - Synchronous Idle 22  026 0x16

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
	
	$postText = "<digiprove_content_request>";
	$postText .= "<user_id>" . get_option('dprv_user_id') . "</user_id>";
	$postText .= '<domain_name>' . $dprv_blog_host . '</domain_name>';
	if ($dprv_blog_host != $dprv_wp_host)
	{
		$postText .= '<alt_domain_name>' . $dprv_wp_host . '</alt_domain_name>';
	}
	$dprv_api_key = get_option('dprv_api_key');
	if ($dprv_api_key != null && $dprv_api_key != "")
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
    $postText .= '<content_title>' . $title . '</content_title>';

	if (count($content_file_names) > 0)
	{
		$postText .= "<content_wrapper>";
	}
	$postText .= '<content_type>' . $dprv_content_type . '</content_type>';

	// if digital fingerprint could not be calculated (PHP4) or if SaveContent selected (is subject to subscription type) and subscription not expired, send content as well as fingerprint
	if ($raw_content_hash == "" || (get_option('save_content') == "SaveContent" && $dprv_subscription_expired != "Yes"))
	{
		$postText .= '<content_data>' . $rawContent . '</content_data>';
	}
	else
	{
		$postText .= '<content_fingerprint>' . $raw_content_hash . '</content_fingerprint>';
	}

	$postText .= '<content_url>';
	$permalink = get_bloginfo('url');
	if ($post_id != -1)									// will be set to -1 if the value is unknown
	{
		// Note - Decided not to implement soft permalinks as can end up with broken links if user changes scheme
		$permalink = get_bloginfo('url') . "/?p=" . $post_id;
	}
	$postText .= $permalink;
	$postText .= '</content_url>';

	if (count($content_file_names) > 0)
	{
		$postText .= "</content_wrapper>";
	}

	$file_count = count($content_file_names);
	if ($dprv_subscription_expired == "Yes")
	{
		$notice = " (This post/page contained references to $file_count media files that according to your settings should be Digiproved, but your subscription expired on " . $dprv_subscription_expiry . ".)";
		$file_count = 0;
	}
	else
	{
		if ($dprv_subscription_type == "Personal" && $file_count > 5)
		{
			$notice = " (This content contained references to $file_count media files that according to your settings should be Digiproved, but the Personal plan limits this to 5 - the first 5 were processed.)";
			$file_count = 5;
		}
		if ($dprv_subscription_type == "Professional" && $file_count > 20)
		{
			$notice = " (This post/page contained references to $file_count media files that according to your settings should be Digiproved, but the Personal plan limits this to 20 - the first 20 were processed.)";
			$file_count = 20;
		}
	}
	for ($t = 0; $t < $file_count; $t++)
	{
		$log->lwrite("doing xml for file " . $t . ": " .  $content_file_names[$t]);
		$postText .= "<content_wrapper>";
		$postText .= '<content_type>File</content_type>';
		$postText .= '<content_filename>' . $content_file_names[$t] . '</content_filename>';
		$postText .= '<content_fingerprint>' . $content_file_fingerprints[$t] . '</content_fingerprint>';
		$postText .= "</content_wrapper>";
	}

	$postText .= "<linkback>" . get_option('dprv_linkback') . "</linkback>";
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

	$data = dprv_http_post($postText, DPRV_HOST, "/secure/service.asmx/", "DigiproveContent");
	$pos = strpos($data, "Error:");
	if ($pos === false)
	{
		$log->lwrite("Returning successfully from dprv_certify");
	}
	return $data;
}

function getContentFiles($content, $dprv_blog_host, &$content_file_names, &$content_file_fingerprints)
{
	global $dprv_mime_types;
	$log = new Logging();  
	$log->lwrite("getContentFiles starts");
	$blog_url = site_url();
	$blog_url_info = parse_url($blog_url);
	$blog_host = $blog_url_info["host"];
	$blog_path = $blog_url_info["path"];
	$root_path = ABSPATH;
	if ($blog_path != "" && strlen($root_path) > strlen($blog_path))
	{
		// strip off blog path to give rootpath corresponding to domain root
		if ((strripos($root_path, $blog_path) + 1) == strlen($root_path) - strlen($blog_path))
		{
			$root_path = substr($root_path, 0, strlen($root_path) - strlen($blog_path));
		}
		else
		{
			$log->lwrite("What to do, ABSPATH and blog path don't have same endings");
			$log->lwrite(strpos($root_path, $blog_path) . "!=" . strlen($root_path) . "-" .strlen($blog_path));
		}
	}
	//$log->lwrite("root_path=$root_path");
	//$log->lwrite("ABSPATH=" . ABSPATH);
	//$log->lwrite("blog_url = $blog_url");
	//$log->lwrite("blog_url_info[host] = " . $blog_url_info["host"]);
	//$log->lwrite("blog_url_info[path] = " . $blog_url_info["path"]);

	//$dprv_html_tags = unserialize(get_option('dprv_html_tags'));
	$dprv_html_tags = get_option('dprv_html_tags');
	if (!is_array($dprv_html_tags))
	{
		$log->lwrite("dprv_html_tags is not an array");
		$dprv_html_tags = false;
	}
	$dprv_outside_media = get_option('dprv_outside_media');
	$t = 0;
	$pos = 0;
	$delimit_chars = " \t\r\f\v\n/>";	//Space, tab, carriage-return, formfeed, vertical-tab, newline, / or >
	$parse_all = false;
	if ($dprv_html_tags["notag"]["selected"] == "True")
	{
		$parse_all = true;
	}
	$w=0;
	while ($pos !== false)
	{
		//$log->lwrite("at start of big while loop, content begins with " . substr($content,0,30));
		$w++;
		if ($w>500)
		{
			$log->lwrite("breaking because of suspected endless loop (a)");
			break;
		}

		if ($parse_all == true)
		{
			// scan the bit from here to next tag:
			$pos = strpos($content, "<");
			if ($pos === false)
			{
				$no_tag_content = $content;
			}
			else
			{
				if ($pos == 0)
				{
					$no_tag_content = "";
				}
				else
				{
					$no_tag_content = substr($content,0,$pos);
				}
			}
			$r=0;
			while ($no_tag_content != "")
			{
				//$log->lwrite("at start of notag while loop, no_tag_content begins with " . substr($no_tag_content,0,30));
				$r++;
				if ($r>300)
				{
					$log->lwrite("breaking because of suspected endless loop (b)");
					break;
				}
				$pos2 = stripos($no_tag_content, "http://");
				$pos3 = stripos($no_tag_content, "https://");
				if ($pos2 !== false || $pos3 !== false)
				{
					if ($pos2 === false)
					{
						$pos1 = $pos3;
					}
					else
					{
						if ($pos3 === false)
						{
							$pos1 = $pos2;
						}
						else
						{
							$pos1 = min($pos2, $pos3);
						}
					}
					if ($pos1 > 0 && ($no_tag_content[$pos1-1] == '"' || $no_tag_content[$pos1-1] == "'"))
					{
						$pos2 = strpos($no_tag_content, $no_tag_content[$pos1-1], $pos1+1);
					}
					else
					{
						$pos2 = str_findAny($no_tag_content, array(" ","\t","\r","\f","\v","\n","<",">"), $pos1+1);
					}
					if ($pos2 !== false)
					{
						$url = substr($no_tag_content,$pos1,$pos2-$pos1);
						$no_tag_content = substr($no_tag_content,$pos2);
					}
					else
					{
						$url = substr($no_tag_content,$pos1);
						$no_tag_content = "";
					}

					processUrl($url, $dprv_html_tags, "notag", $content_file_names, $content_file_fingerprints, $t, $root_path, $blog_url, $blog_host, $blog_path);
				}
				else
				{
					$no_tag_content = "";
					break;
				}
			}
		}
		$tag = "";
		$pos = strpos($content, "<");
		if ($pos === false || $pos == strlen($content)-1)
		{
			break;
		}
		for ($pos2=$pos+1; $pos2<strlen($content); $pos2++)
		{
			if (strpos($delimit_chars, $content[$pos2]) !== false)
			{
				break;
			}
		}
		if ($pos2 > strlen($content)-4)
		{
			break;
		}
		$tag = strtolower(substr($content, $pos+1,3));  // just for log message
		$end_of_tag_pos = strpos($content, ">",$pos+1);				// end of this tag
		//$log->lwrite("tag = $tag, starts at $pos and ends at $end_of_tag_pos");
		if ($pos2 > 0 && $content[$pos2] != "/" && $content[$pos2] != ">" && $end_of_tag_pos !== false && $end_of_tag_pos > ($pos2+1))   // Only carry on with this tag if there is a modifier and the tag is closed
		{
			$tag = strtolower(substr($content, $pos+1, $pos2-$pos-1));

			if (isset($dprv_html_tags[$tag]) && $dprv_html_tags[$tag]["selected"] != "False")	// If this tag is not on list of media types to be digiproved, skip
			{
				$modifiers = ltrim(substr($content, $pos2 + 1,$end_of_tag_pos-$pos2-1));
				//$log->lwrite("modifiers = $modifiers");
				$src_attribute = "src";
				if ($tag == "a")
				{
					$src_attribute = "href";
				}
				$pos1 = stripos($modifiers, $src_attribute);
				if ($pos1 !== false && $pos1 < (strlen($modifiers)-6))  
				{
					$modifiers = ltrim(substr($modifiers, $pos1+strlen($src_attribute)));
					if (strpos($modifiers, "=") == 0 && strlen($modifiers) > 2)
					{
						$modifiers = ltrim(substr($modifiers,1));
						if (strlen($modifiers) > 2 && ($modifiers[0] == "'" || $modifiers[0] == '"'))
						{
							$delimiter = $modifiers[0];
							$modifiers = ltrim(substr($modifiers,1));
							$pos1 = strpos($modifiers, $delimiter);
							if ($pos1 !== false && $pos1 != 0)
							{
								$url = substr($modifiers, 0, $pos1);

								processUrl($url, $dprv_html_tags, $tag, $content_file_names, $content_file_fingerprints, $t, $root_path, $blog_url, $blog_host, $blog_path);
							}
						}
						else
						{
							$log->lwrite("ignoring - " . $tag . " tag - could not parse src/href part (2)");
						}
					}
					else
					{
						$log->lwrite("ignoring - " . $tag . " tag - could not parse src/href part (1)");
					}
				}
				else
				{
					$log->lwrite("ignoring - " . $tag . " tag - no src/href attribute");
				}

			}
			else
			{
				$log->lwrite("ignoring - " . $tag . " tag - not selected");
			}
		}
		else
		{
			$log->lwrite("ignoring $tag tag - no modifier");
		}

		if ($end_of_tag_pos !== false && $end_of_tag_pos < (strlen($content)-3))
		{
			$content = ltrim(substr($content,$end_of_tag_pos+1));
		}
		else
		{
			break;
		}
	}
}

function str_findAny($haystack, $needles, $offset=0)
{
	for ($i=$offset; $i<strlen($haystack); $i++)
	{
		if (array_search($haystack[$i], $needles))
		{
			return $i;
		}
	}
	return false;
}

function processUrl($url, $dprv_html_tags, $tag, &$content_file_names, &$content_file_fingerprints, &$t, $root_path, $blog_url, $blog_host, $blog_path)
{
	$log = new Logging();
	$url = rtrim($url);
	//$log->lwrite("tag is $tag processurl $url");
	global $dprv_mime_types;
	$dprv_outside_media = get_option('dprv_outside_media');
	$url_info = parse_url($url);
	$url_string = $url;
	$blog_url_string = $blog_host . $blog_path;

	if (!isset($url_info["host"]))
	{
		//$log->lwrite("url_info[host] is not set");
		if (strpos($url, "/") == 0)
		{
			$full_path = addPaths($root_path, $url_info["path"]);
		}
		else
		{
			$full_path = addPaths(ABSPATH, $url_info["path"]);
		}
	}
	else
	{
		if ($url_info["host"] != $blog_host)
		{
			// first check for partial matches
			$partial_match = false;
			if	(
					$url_info["host"] == ("www." . $blog_host)
					||
					("www." . $url_info["host"]) == $blog_host
					||
					(strripos($url_info["host"], $blog_host) !== false && strripos($url_info["host"], $blog_host) === strlen($url_info["host"]) - strlen($blog_host))
					||
					(strripos($blog_host, $url_info["host"]) !== false && strripos($blog_host, $url_info["host"]) === strlen($blog_host) - strlen($url_info["host"]))
				)
			{
				$log->lwrite("suspected match between " . $url_info["host"] . " and " . $blog_host);
				$partial_match = true;
				$url_info["host"] = $blog_host;		// use blog host value from now on
			}
			else
			{
				if ($dprv_outside_media != "Outside")
				{
					$log->lwrite("$url is not on this host");
					return;
				}
				else
				{
					// what to doo?
					return;  // don't handle this yet
				}
			}
		}
		$url_string = $url_info["host"] . $url_info["path"];
		if (stripos($url_string, $blog_url_string) !== 0)
		{
			$log->lwrite("$url is not on this wp web-site");
			return;
		}

		if (stripos($url_string, $blog_url_string) === 0 && isset($url_info["query"]) && strpos($url_info["query"], "attachment_id=") === 0)
		{
			$full_path = convert_attachment_to_file(substr($url_info["query"],14));
		}
		else
		{
			$full_path = addPaths($root_path, $url_info["path"]);
		}
	}

	$file_name = basename($full_path);
	$ext = pathinfo($file_name, PATHINFO_EXTENSION);
	$file_selected = false;		// default value
	$selected_reason = "";		// this variable used just for logging / testing
	if ($dprv_html_tags[$tag]["incl_excl"] == "Include")
	{
		if ($dprv_html_tags[$tag]["All"] == "True")
		{
			$selected_reason .= "All filetypes included; ";
			$file_selected = true;
		}
		else
		{
			foreach ($dprv_html_tags[$tag] as $key=>$value)
			{
				if ($key != "All" && $key != "name" && $key != "selected" && $key != "incl_excl")
				{
					if ($value == "True")
					{
						if (array_search($ext, $dprv_mime_types[$key]))
						{
							$selected_reason .= "$ext included in $key list; ";
							$file_selected = true;
						}
					}
				}
			}
		}
	}
	else
	{
		$file_selected = true;		// default value if using Exclude
		$selected_reason .= "$ext not excluded";
		foreach ($dprv_html_tags[$tag] as $key=>$value)
		{
			if ($key != "All" && $key != "name" && $key != "selected" && $key != "incl_excl")
			{
				if ($value == "True")
				{
					if (array_search($ext, $dprv_mime_types[$key]))
					{
						$selected_reason .= "$ext excluded (in $key list)";
						$file_selected = false;
					}
				}
			}
		}
	}
	if ($file_selected == true)
	{
		$file_data = @file_get_contents($full_path);
		if ($file_data != false)
		{
			$file_fingerprint = strtoupper(hash("sha256", $file_data));
			if (array_search($file_name,$content_file_names) === false || array_search($file_fingerprint,$content_file_fingerprints) === false)  // prevent duplicate references
			{
				$content_file_names[$t] = $file_name;
				$content_file_fingerprints[$t] = strtoupper(hash("sha256", $file_data));
				$log->lwrite("content_file_names[" . $t . "]=" . $content_file_names[$t] . " (from " . $tag . " tag), included because " . $selected_reason);
				$t++;
			}
			else
			{
				$log->lwrite("ignoring - " . $file_name . " (from " . $tag . " tag - encountered earlier)");
			}
		}
		else
		{
			$error = error_get_last();
			if ($error !== null)
			{
				$log->lwrite("Error " . $error["type"] . " " . $error["message"] . " at line " . $error["line"] . " trying to read $full_path");
			}
			else
			{
				$log->lwrite("error trying to read $full_path");
			}
		}
	}
	else
	{
		$log->lwrite("ignoring - " . $file_name . " (from " . $tag . " tag) - $ext not a selected media type");
	}

}

function addPaths($root, $path)
{
	if ($path[0] == "/" && $root[strlen($root)-1] == "/")
	{
		$path = substr($path,1);
	}
	return $root . $path;
}

function convert_attachment_to_file($attach_id)
{
	$id = "";
	for ($i=0; $i<strlen($attach_id); $i++)
	{
		if (strpos("0123456789",$attach_id[$i]) !== false)
		{
			$id .= $attach_id[$i];
		}
		else
		{
			break;
		}
	}
	return get_attached_file($id);
}
function dprv_getTag($xmlString, $tagName)
{
	$start_contents = stripos($xmlString, "<" . $tagName . ">") + strlen($tagName) + 2;
	$end_tag = stripos($xmlString, "</" . $tagName . ">");
	if ($start_contents === false || $end_tag === false || $end_tag <= $start_contents)
	{
		return false;
	}
	return substr($xmlString, $start_contents, $end_tag - $start_contents);
}

// Extract raw content to be Digiproved, ignoring previous Digiprove embedded certs and rationalise to ignore effects of Wordpress formatting
function dprv_getRawContent($post_id, $contentString, &$raw_content_hash)
{
	$log = new Logging();
	//$log->lwrite("getRawContent starts, content=" . $contentString);

	$raw_content = trim($contentString);
	$raw_content = dprv_normaliseContent($raw_content);
	$raw_content_hash = "";

	if (function_exists("hash"))					// Before 5.1.2, the hash() function did not exist, calling it gives a fatal error
	{
		$raw_content_hash = strtoupper(hash("sha256", $raw_content));
	}
	$log->lwrite("getRawContent finishes, strlen(raw_content)=" . strlen($raw_content) . ", hash=$raw_content_hash");

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

?>