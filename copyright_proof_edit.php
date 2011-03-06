<?php
// FUNCTIONS CALLED WHEN CREATING, OR EDITING POSTS OR PAGES

/* Adds a box to the main column on the Post and Page edit screens */
function dprv_postbox()
{
    add_meta_box( 'dprv_post_box', __( 'Copyright / Ownership / Licensing', 'dprv_cp' ), 
                'dprv_show_postbox', 'post' );
    add_meta_box( 'dprv_post_box', __( 'Copyright / Ownership / Licensing', 'dprv_cp' ), 
                'dprv_show_postbox', 'page' );
}

/* Prints the box content */
function dprv_show_postbox($post_info)
{
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
	if ($script_name == "post")	// "post" means we are displaying form for editing an existing post ("post-new" is for a new post)
	{
		$log->lwrite("not new post, global post_id = " . $post_id);
		
		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_posts WHERE id = " . $post_id;
		//$wpdb->show_errors();
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
			$dprv_this_license_caption = __("Some Rights Reserved", "dprv_cp");;
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
						$log->lwrite("just set dprv_this_license_abstract = " . $dprv_this_license_abstract);
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


	$a = "<table style='padding:6px;  padding-top:0px; width:100%'><tbody>";
	$a .= "<tr><td style='width:160px; height:30px'>Digiprove this " . $post_info->post_type . "</td><td style='width:300px'><input type='radio' id='dprv_this_yes' name='dprv_this' value='Yes'" . $dprv_this_yes_checked . " onclick='TogglePanel()'/>" . __("Yes", "dprv_cp") . "&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' id='dprv_this_no' name='dprv_this' value='No'" . $dprv_this_no_checked . " onclick='TogglePanel()'/>" . __("No", "dprv_cp") . "</td><td colspan='2'>" . $dprv_last_digiprove_info . "</td></tr>";
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
	$a .= "<select id='dprv_license_caption' name='dprv_license_caption' style='width:200px; display:" . $inputDisplay . "'>";   // rows='2' removed
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
	$a .= "<a id='dprv_license_url_link' name='dprv_license_url_link' href='" . $dprv_this_license_url . "' target='_blank' style='display:" . $other_labeldisplay . "'>" . htmlspecialchars(stripslashes($dprv_this_license_url), ENT_QUOTES, 'UTF-8') . "</a>";
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
	$post_type = $post->post_type;
	//$post_type_object = get_post_type_object($post_type);
	//$can_publish = current_user_can($post_type_object->cap->publish_posts);
 	$can_publish = current_user_can("publish_" . $post_type. "s");

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
	global $wpdb, $dprv_digiprove_this_post, $post_id, $post_ID;
	$log = new Logging();  
	$log->lwrite("dprv_parse_post starts"); 
	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
	$posDot = strrpos($script_name,'.');
	if ($posDot != false)
	{
		$script_name = substr($script_name, 0, $posDot);
	}

	$dprv_publish_dp_action = $_POST['dprv_publish_dp_action'];
	if ($data['post_status'] != "publish" && $data['post_status'] != "future")
	{
		if ($dprv_publish_dp_action == "Yes" && $data['post_status'] == "draft")
		{
			// If the status is set to draft but user has explicitly requested Digiproving, then we can proceed beyond this point
		}
		else
		{
			$log->lwrite("dprv_parse_post not starting because status (" . $data['post_status'] . ") is not publish or future");
			return $data;
		}
	}
	if ($data['post_type'] != "post" && $data['post_type'] != "page")
	{
		$log->lwrite("dprv_parse_post not starting because type (" . $data['post_type'] . ") is not post or page");  // Does this ever occur?
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
				else
				{
					//update_option('dprv_event', 'inserted no-digiprove for ' . $dprv_post_id);
				}
			}
			else
			{
				//$wpdb->show_errors();
				if (false === $wpdb->update(get_option('dprv_prefix') . "dprv_posts", array('digiprove_this_post'=>false), array('id'=>$dprv_post_id), '%f', '%f'))
				{
					update_option('dprv_event', 'error ' . $wpdb->last_error . ' updating no-digiprove for ' . $dprv_post_id);
				}
				else
				{
					//update_option('dprv_event', 'updated no-digiprove for ' . $dprv_post_id);
				}

			}
		}
		return $data;
	}

	update_option('dprv_last_action', 'Digiprove id=' . $dprv_post_id);
	$dprv_title = $data['post_title'];
	$log->lwrite("title=" . $dprv_title . ", id=" . $dprv_post_id);  
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
				else
				{
					//update_option('dprv_event', 'wrote old notice ' . $dprv_post_id . ' to db');
				}
			}
			else
			{	
				//$wpdb->show_errors();
				if (false === $wpdb->insert(get_option('dprv_prefix') . 'dprv_posts', array('id'=>$dprv_post_id, 'digiprove_this_post'=>true, 'certificate_id'=>$dprv_certificate_id, 'certificate_url'=>$dprv_certificate_url, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>intval($dprv_first_year))))
				{
					update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' inserting old notice to db');
				}
				else
				{
					//update_option('dprv_event', 'inserted old notice ' . $wpdb->insert_id . ' to db');
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
	$log->lwrite("starting dprv_digiprove_post (" . $_POST["action"] . ")" . $dprv_post_id);
	// Values seen for POST[action]:	empty					when (script = wp-cron)
	//															or   (script = post-new    && status = auto-draft)
	//															or   (script = xmlrpc-post && status = ? publish?)
	//															or   (script = xmlrpc-post && status = inherit)
	//									autosave				when (script = admin-ajax  && status = draft)
	//									editpost				when (script = post        && status = inherit)
	//															or	 (script = post        && status = future)
	//															or	 (script = post        && status = draft)  (only seen so far on Opera on future-dated posts)
	//                                  runpostie				when (script = options-general  && status = draft)
	//															or   (script = options-general  && status = ? publish?)
	//									post-quickpress-save	when (script = post				&& status = auto-draft)

	if ($script_name == "wp-cron")
	{
		$log->lwrite("dprv_digiprove_post not starting because script is wp-cron - any digiproving has been done already");
		return;
	}

	$post_record = get_post($dprv_post_id); 
	if ($post_record->post_status != "publish" && $post_record->post_status != "future")
	{
		$log->lwrite("dprv_digiprove_post not starting because status (" . $post_record->post_status . ") is not publish or future");
		return;
	}
	if ($post_record->post_type != "post" && $post_record->post_type != "page")
	{
		$log->lwrite("dprv_digiprove_post not starting because type (" . $post_record->post_type . ") is not post or page");  // Does this ever occur?
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

	if ($today_count > 30)
	{
		$today_limit = 30;
		switch (get_option('dprv_subscription_type'))
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
		$dprv_subscription_expiry = get_option('dprv_subscription_expiry');
		$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 86400;					// Add 24 hour grace period (also handles any unforeseen timezone issues)
		if ($dprv_expiry_timestamp != false && $dprv_expiry_timestamp != -1 && time() > $dprv_expiry_timestamp)
		{
			// NOTE - if changing the "Digiprove free daily limit" text, also modify  dprv_admin_footer() which relies on this exact text
			update_option('dprv_last_result', "Digiprove free daily limit (30) already reached, and your Digiprove account expired on " . $dprv_subscription_expiry . ". You can <a href='" . 	createUpgradeLink() . "&Action=Renew' target='_blank'>renew your account here</a>");
			return;
		}
	}


	$log->lwrite("dprv_digiprove_post STARTS");

	update_option('dprv_last_result', '');
	$newContent = stripslashes($content);
	$certifyResponse = dprv_certify($dprv_post_id, $post_record->post_title, $newContent);
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

				dprv_record_dp_action($dprv_post_id, $certifyResponse, $post_record->post_status);

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
			return;		
		}
	}
	else
	{
		update_option('dprv_last_result', __('Content unchanged since last edit', 'dprv_cp'));
	}

	$log->lwrite("finishing dprv_digiprove_post " . $dprv_post_id);
	return;
}

function dprv_strip_old_notice(&$content, &$dprv_certificate_id, &$dprv_utc_date_and_time, &$dprv_digital_fingerprint, &$dprv_certificate_url, &$dprv_copyright_year)
{
	$log = new Logging();  
	$log->lwrite("dprv_strip_old_notice starts");
	$certificate_info = "";
	$start_Digiprove = strpos($content, "<!--Digiprove_Start-->");
	$end_Digiprove = false;
	if ($start_Digiprove === false)
	{
		$log->lwrite("did not find start_Digiprove marker");
		return;
	}
	$end_Digiprove = strpos($content, "<!--Digiprove_End-->");
	if ($end_Digiprove === false || $end_Digiprove <= $start_Digiprove)
	{
		$log->lwrite("did not find end_Digiprove marker");
		return;
	}
	$posA = stripos($content, "<span", $start_Digiprove + 22);
	$posA2 = stripos($content, "<div", $start_Digiprove + 22);
	if ($posA === false && $posA2 === false)
	{
		$log->lwrite("no span or div marker found");
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
	$remainder = strpbrk($remainder, '\'\"');
	$delimiter = substr($remainder,0,1);
	$remainder = substr($remainder,1);
	$posB = strpos($remainder, $delimiter);

	if ($posB === false)
	{
		$log->lwrite("could not find delimiter");
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
	if ($_POST['dprv_default_license'] != "on")
	{
		$using_default_license = false;
	}

	if ($_POST['dprv_custom_license'] != "on")
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
		else
		{
			//update_option('dprv_event', 'updated copyright details for ' . $dprv_post_id);
		}
	}
	else
	{
		//$wpdb->show_errors();
		if (false === $wpdb->insert(get_option('dprv_prefix') . "dprv_posts", array('id'=>$dprv_post_id, 'digiprove_this_post'=>$digiprove_this_post, 'this_all_original'=>$this_all_original, 'attributions'=>$attributions, 'using_default_license'=>$using_default_license,  'license'=>$license, 'custom_license_caption'=>$custom_license_caption, 'custom_license_abstract'=>$custom_license_abstract, 'custom_license_url'=>$custom_license_url)))
		{
			update_option('dprv_event', 'error ' . $wpdb->last_error . ' inserting copyright details for ' . $dprv_post_id);
		}
		else
		{
			//update_option('dprv_event', 'inserted copyright details for ' . $dprv_post_id);
		}

	}
}

function dprv_record_dp_action($dprv_post_id, $certifyResponse, $dprv_post_status)
{
	global $wpdb;
	$log = new Logging();  
	$log->lwrite("dprv_record_dp_action starts. post id = " . $dprv_post_id);
	$dprv_certificate_id = dprv_getTag($certifyResponse, "certificate_id");
	$dprv_certificate_url = dprv_getTag($certifyResponse, "certificate_url");
	$dprv_utc_date_and_time = dprv_getTag($certifyResponse, "utc_date_and_time");

	$dprv_digital_fingerprint = dprv_getTag($certifyResponse, "digital_fingerprint");
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
		else
		{
			//update_option('dprv_event', $dprv_event . '; updated dp action for ' . $dprv_post_id . ', status ' . $dprv_post_status);
		}
	}
	else
	{
		//$wpdb->show_errors();
		if (false === $wpdb->insert(get_option('dprv_prefix') . "dprv_posts", array('id'=>$dprv_post_id, 'digiprove_this_post'=>true, 'this_all_original'=>true,'using_default_license'=>true, 'certificate_id'=>$dprv_certificate_id, 'digital_fingerprint'=>$dprv_digital_fingerprint, 'certificate_url'=>$dprv_certificate_url, 'cert_utc_date_and_time'=>$dprv_utc_date_and_time, 'first_year'=>$dprv_year_certified)))
		{
			update_option('dprv_event', $dprv_event . '; error ' . $wpdb->last_error . ' inserting dp action for ' . $dprv_post_id . ', status ' . $dprv_post_status);
		}
		else
		{
			//update_option('dprv_event', $dprv_event . '; inserted dp action for ' . $dprv_post_id . ', status ' . $dprv_post_status);
		}
	}
}

function dprv_certify($post_id, $title, $content)
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
			return " Hashes are identical";	// Content has not changed, do not Digiprove again - stick with earlier certification
		}
	}
	if ($rawContent == "")
	{
		return " Raw content is empty";
	}

	$dprv_subscription_expired = "No";
	$dprv_expiry_timestamp = strtotime($dprv_subscription_expiry . ' 23:59:59 +0000') + 86400;			// add 24-hour grace period (also allows for any unforeseen timezone issues)
	if ($dprv_expiry_timestamp != false && $dprv_expiry_timestamp != -1 && time() > $dprv_expiry_timestamp)
	{
		$dprv_subscription_expired = "Yes";
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
	
	// if digital fingerprint could not be calculated (PHP4) or if SaveContent selected (is subject to subscription type) and subscription not expired, send content as well as fingerprint
	if ($raw_content_hash == "" || (get_option('save_content') == "SaveContent" && $dprv_subscription_expired != "Yes"))
	{
		$postText .= '<content_data>' . $rawContent . '</content_data>';
	}
	else
	{
		$postText .= '<content_fingerprint>' . $raw_content_hash . '</content_fingerprint>';
	}
    //if (get_option('dprv_linkback') == "Linkback")
	//{
		$postText .= '<content_url>';
		$permalink = get_bloginfo('url');
		if ($post_id != -1)									// will be set to -1 if the value is unknown
		{
			// Note - Decided not to implement soft permalinks as can end up with broken links if user changes scheme
			$permalink = get_bloginfo('url') . "/?p=" . $post_id;
		}
		$postText .= $permalink;
		$postText .= '</content_url>';
	//}
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
	$log->lwrite("getRawContent starts, content=" . $contentString);

	$raw_content = trim($contentString);
	$raw_content = dprv_normaliseContent($raw_content);
	$raw_content_hash = "";

	if (function_exists("hash"))					// Before 5.1.2, the hash() function did not exist, calling it gives a fatal error
	{
		$raw_content_hash = strtoupper(hash("sha256", $raw_content));
		//$log->lwrite("Content fingerprinted = START" . $raw_content . "END");
		//$log->lwrite("Calculated digital fingerprint = " . $raw_content_hash);
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

?>