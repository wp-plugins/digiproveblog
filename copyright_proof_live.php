<?php
// FUNCTIONS CALLED WHEN SERVING PAGES
function dprv_head()
{
	$log = new Logging();  
	$log->lwrite("dprv_wp_head starts");
	populate_licenses();

	$dprv_frustrate_copy = get_option('dprv_frustrate_copy');
	$dprv_right_click_message = get_option('dprv_right_click_message');
	echo ("<script type='text/javascript'>
				//<![CDATA[
				function DisplayAttributions(attribution_text)
				{
					document.getElementById(\"dprv_attribution\").innerHTML = attribution_text;
					document.getElementById(\"dprv_attribution\").title = \"Attributions - owner(s) of some content\";
					document.getElementById(\"dprv_attribution\").onmouseover = \"\";
				}
				function DisplayLicense()
				{
					document.getElementById('license_panel').style.display='block';
					document.getElementById('license_panel').style.zIndex='2';
				}
				function HideLicense()
				{
					document.getElementById('license_panel').style.display='none';
					//document.getElementById('license_panel').style.zIndex='0';
				}");
	
	// Then, create Javascript to do copy-protect functions if necessary
	if ($dprv_frustrate_copy == "Yes")
	{
		echo ("var noRightClickMessage='" . $dprv_right_click_message . "';");
	}
	echo (" //]]>
			</script>");

	$dprv_home = get_settings('siteurl');
	if ($dprv_frustrate_copy == "Yes")
	{
		echo ("<script type='text/javascript' src='" . $dprv_home . "/wp-content/plugins/digiproveblog/frustrate_copy.js?v=".DPRV_VERSION."'></script>");
	}
			
}


function dprv_display_content($content)
{
	global $wpdb, $table_prefix, $dprv_licenseIds, $dprv_licenseTypes, $dprv_licenseCaptions, $dprv_licenseAbstracts, $dprv_licenseURLs;
	$log = new Logging();  
	$dprv_post_id = get_the_ID();
	$log->lwrite("dprv_display_content starts for post/page " . $dprv_post_id);
	
	$in_excerpt = false;

	// Determine whether being called in the_excerpt:
	// If so, HTML tags will be stripped out and notice will look funny
	// Maybe we should just return at this point ?
	$my_arrays = debug_backtrace();
	foreach ($my_arrays as $my_array)
	{
		$search_result = array_search("the_excerpt", $my_array);
		if ($search_result == "function")
		{
			$in_excerpt = true;
		}
	}

	$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);

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

	// If stuff is recorded specifically for this post, use that
	$sql="SELECT * FROM " . $table_prefix . "dprv_posts WHERE id = " . $dprv_post_id;
	$dprv_status_info = "";
	$dprv_post_info = $wpdb->get_row($sql, ARRAY_A);

	if (is_null($dprv_post_info))
	{
		$dprv_status_info = "<span style='display:none'>Null return selecting " . $dprv_post_id;
		if (trim($wpdb->last_error) != "")
		{
			$dprv_status_info .= "; last SQL error is " . $wpdb->last_error;
		}
		$dprv_status_info .= "; dprv_event=" . get_option('dprv_event') . "</span>";
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
		
		$log->lwrite("dprv_this_license=" . $dprv_this_license);

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
		if ($dprv_this_license != 0)
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

	if (count($dprv_post_info) > 0 && $dprv_post_info["digiprove_this_post"] == true && $dprv_post_info["certificate_id"] != null && $dprv_post_info["certificate_id"] != "" && $dprv_post_info["certificate_id"] != false)
	{
		$log->lwrite("there is a Digiprove cert in the meta-data");

		$dprv_certificate_id = $dprv_post_info["certificate_id"];
		$dprv_utc_date_and_time = $dprv_post_info["cert_utc_date_and_time"];
		$dprv_digital_fingerprint = $dprv_post_info["digital_fingerprint"];
		$dprv_certificate_url = $dprv_post_info["certificate_url"];
		$dprv_first_year = $dprv_post_info["first_year"];

		$dprv_notice = dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, false, $dprv_first_year, $dprv_this_license, $dprv_this_license_caption, $dprv_this_license_abstract, $dprv_this_license_url, $dprv_this_all_original, $dprv_this_attributions, $dprv_license_html);
		$content .=  $dprv_notice;
	}
	else
	{
		$log->lwrite("there is no Digiprove cert in the meta-data");
		if ($dprv_certificate_id != false && $dprv_certificate_id != "")
		{
			$log->lwrite("but there was an old notice - will make a new one with variables from that");
			$dprv_notice = dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, false, $dprv_first_year, $dprv_this_license, $dprv_this_license_caption, $dprv_this_license_abstract, $dprv_this_license_url, $dprv_this_all_original, $dprv_this_attributions, $dprv_license_html);
			$content .= $dprv_notice;
		}
		else
		{
			// can probably remove this
			$dprv_status_info .= "<span style='display:none'>No Digiprove cert recorded for " . $dprv_post_id . "; dprv_event=" . get_option('dprv_event') . "</span>";
		}
	}
	$content .= $dprv_status_info;
	$content .= $dprv_license_html;
	$log->lwrite("content to be displayed:" . $content);
	return $content;
}

function dprv_footer()
{
	$log = new Logging();  
	$log->lwrite("dprv_footer starts");
	
	// Create Javascript to do copy-protect functions if necessary
	// Needs to be here, unless it gets added to existing onload functionality in which case it could go in head section
	$dprv_frustrate_copy = get_option('dprv_frustrate_copy');
	$dprv_right_click_message = get_option('dprv_right_click_message');
	$dprv_record_IP = get_option('dprv_record_IP');
	if ($dprv_record_IP == "Yes")
	{
		$content_prefix .= "<script src='record_IP.js' type='text/javascript'></script>";
		$dprv_wp_url = parse_url(get_option('siteurl'));
		$dprv_wp_host = $dprv_wp_url[host];
		$log->lwrite("dprv_wp_host = " . $dprv_wp_host);  
		$content_prefix .= "<form action='http://" . $dprv_wp_host . "/copyright_proof_handler.php' method='post' id='IPAddress'><input type='hidden' value='" . @$REMOTE_ADDR . "' /></form>";
	}

	if ($dprv_frustrate_copy == "Yes")
	{
		echo ("<script type='text/javascript'>
					//<![CDATA[
					// MAINLINE CODE

					// Prevent Right-Clicking (entire page)
					disableRightClick();
					var justDisplayed = 0;

					// Prevent Control Key combinations (like CTRL A, CTRL U)
					disableCtrlKeys();

					disableSelection(document.body);

					//]]>
				</script>");
	}
}


function dprv_composeNotice($dprv_certificate_id, $dprv_utc_date_and_time, $dprv_digital_fingerprint, $dprv_certificate_url, $preview, $dprv_first_year, $licenseType, $licenseCaption, $licenseAbstract, $licenseURL, $all_original, $attributions, &$dprv_license_html)
{
	$log = new Logging(); 
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
		$dprv_boxmodel = "display:inline;";
		$dprv_container_pad_top = "2px";

		if (($attributions != false && $attributions != "" && $all_original != "Yes") || ($licenseType != false && $licenseType != "" && $licenseType != "Not Specified"))
		{
			$dprv_container = "div";
			$dprv_boxmodel = "display:inline-block;";
			$dprv_container_pad_top = "3px";
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
				$dprv_border_css .= 'border:1px solid ' . strtolower($dprv_notice_border) . ';';
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
		$dprv_leftpad = "24px";
		$dprv_leftpad0 = "8px";
		$notice_size = get_option('dprv_notice_size');
		$extra_style = "";
		if ($dprv_container == "div")
		{
			$extra_style = "padding-bottom:1px;";
		}
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
			$dprv_leftpad = "18px";
			$dprv_leftpad0 = "6px";
			$extra_style = "padding-top:" . $dprv_container_pad_top . ";padding-bottom:0px;";
		}
		$container_style = 'vertical-align:baseline; padding:3px; margin-top:2px; margin-bottom:2px; line-height:' . $dprv_line_height . ';float:none; font-family: Tahoma, MS Sans Serif; font-size:' . $dprv_outside_font_size . ';' . $dprv_border_css . $background_css . $dprv_boxmodel . $extra_style;
		$DigiproveNotice = '<' . $dprv_container . ' id="dprv_cp_V' . DPRV_VERSION . '" lang="en" xml:lang="en" valign="top" class="notranslate" style="' . $container_style . '" title="certified ' . $dprv_utc_date_and_time . ' by Digiprove certificate ' . $dprv_certificate_id . '" >';

		$DigiproveNotice .= '<a href="' . $dprv_certificate_url . '" target="_blank" rel="copyright" style="height:' . $dprv_a_height . '; line-height: ' . $dprv_a_height . '; border:0px; padding:0px; margin:0px; float:none; display:inline; text-decoration: none; background:transparent none; line-height:normal; font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:' . $dprv_font_size . ';">';
		

		$dprv_home = get_settings('siteurl');
		$DigiproveNotice .= '<img src="' . $dprv_home. '/wp-content/plugins/digiproveblog/dp_seal_trans_16x16.png" style="' . $dprv_image_scale . 'vertical-align:' . $dprv_img_valign . '; display:inline; border:0px; margin:0px; padding:0px; float:none; background:transparent none" border="0" alt=""/>';

		$DigiproveNotice .= '<span style="font-family: Tahoma, MS Sans Serif; font-style:normal; font-size:' . $dprv_font_size . '; font-weight:normal; color:' . $dprv_notice_color . '; border:0px; float:none; display:inline; text-decoration:none; letter-spacing:normal; padding:0px; padding-left:' . $dprv_leftpad0 . '; vertical-align:' . $dprv_txt_valign . ';margin-bottom:' . $dprv_line_margin . '" ';

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
			$posB = stripos($utc_date_and_time, " UTC");
			if ($posB != false && $posB > 13)
			{
				$dprv_year = substr($utc_date_and_time, $posB-13, 4);  // This should work if HH:MM:SS always has length of 8
			}
			if (trim($dprv_first_year) != "" && $dprv_year != $dprv_first_year)
			{
				$dprv_year = $dprv_first_year . "-" . $dprv_year;
			}
			$DigiproveNotice .= '&nbsp;&copy;&nbsp;' . $dprv_year;
			if ($dprv_c_notice == "DisplayAll" && $dprv_full_name != "")
			{
				$DigiproveNotice .= '&nbsp;' . 	str_replace(" ", "&nbsp;", htmlspecialchars(stripslashes($dprv_full_name), ENT_QUOTES, 'UTF-8'));
			}
		}
		$DigiproveNotice .= '</span></a>';

		$span_style = "font-family: Tahoma, MS Sans Serif; font-style:normal; display:block; font-size:" . $dprv_font_size . "; font-weight:normal; color:" . $dprv_notice_color . "; border:0px; float:none; text-decoration:none; letter-spacing:normal; line-height:" . $dprv_a_height . "; vertical-align:" . $dprv_txt_valign . "; padding:0px; padding-left:" . $dprv_leftpad . ";margin-bottom:" . $dprv_line_margin . ";";
		$mouseover = "";
		if ($preview != true)
		{
			$mouseover = 'onmouseover="this.style.color=\'' . $dprv_hover_color . '\';" onmouseout="this.style.color=\'' . $dprv_notice_color . '\';"';
		}
		if ($attributions != false && $attributions != "" && $all_original != "Yes")
		{
			$DigiproveNotice .= "<div id=\"dprv_attribution\" style=\"" . $span_style . "\" ";
			if (strlen($attributions) < 45)
			{
				$DigiproveNotice .= "title=\"" . __("Attributions - owner(s) of some content", "dprv_cp") . "\">";
				$DigiproveNotice .=  "Acknowledgements:&nbsp;" . htmlspecialchars(stripslashes($attributions), ENT_QUOTES, 'UTF-8') . "</div>";
			}
			else
			{
				$DigiproveNotice .= "title=\"" . __("Attributions - owner(s) of some content - click to see full text", "dprv_cp") . "\" onclick=\"DisplayAttributions('Acknowledgements:&nbsp;" . htmlspecialchars($attributions, ENT_QUOTES, 'UTF-8') . "')\" " . $mouseover . ">";
				$DigiproveNotice .=  "Acknowledgements:&nbsp;" . htmlspecialchars(stripslashes(substr($attributions, 0, 37)), ENT_QUOTES, 'UTF-8') . " more...</div>";
			}
		}
		$log->lwrite("licenseType = " . $licenseType . ", licenseCaption=" . $licenseCaption);
		if ($licenseType != false && $licenseType != "" && $licenseType != "Not Specified")
		{
			$DigiproveNotice .= "<a title='" . __("Click to see details of license", "dprv_cp") . "' href=\"javascript:DisplayLicense()\" style=\"" . $span_style . "\" " . $mouseover . ">";
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
			$dprv_license_html = "<div id='license_panel' style='position: absolute; display:none ; font-family: Tahoma, MS Sans Serif; font-style:normal; font-size:" . $dprv_font_size . "; font-weight:normal; color:" . $dprv_notice_color . $dprv_border_css . " float:none; max-width:640px; text-decoration:none; letter-spacing:normal; line-height:" . $dprv_line_height . "; vertical-align:" . $dprv_txt_valign . "; padding:0px;" . $background_css . "'>";
			$dprv_license_html .= "<table cellpadding='0' cellspacing='0' border='0' style='line-height:17px;margin:0px;padding:0px;background-color:transparent;font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:" . $dprv_font_size . "; color:" . $dprv_notice_color . "'><tbody>";
			$dprv_license_html .= "<tr><td colspan='2' style='background-color:transparent;border:0px;font-weight:bold;padding:0px;padding-left:6px'>Original content here is published under these license terms:</td><td style='width:20px;background-color:transparent;border:0px;padding:0px'><span style='float:right; background-color:black; color:white; width:20px; text-align:center; cursor:pointer' onclick='HideLicense()'>&nbsp;X&nbsp;</span></td></tr>";
			$dprv_license_html .= "<tr><td colspan='3' style='height:4px;padding:0px;background-color:transparent;border:0px'></td></tr>";
			$dprv_license_html .= "<tr><td style='width:130px;background-color:transparent;padding:0px;padding-left:4px;border:0px'>License Type:</td><td style='width:300px;background-color:transparent;border:0px;padding:0px'>" . htmlspecialchars(stripslashes($licenseType), ENT_QUOTES, 'UTF-8') . "</td><td style='border:0px; background-color:transparent'></td></tr>";
			$dprv_license_html .= "<tr><td colspan='3' style='height:4px;background-color:transparent;padding:0px;border:0px'></td></tr>";
			$dprv_license_html .= "<tr><td style='background-color:transparent;padding:0px;padding-left:4px;border:0px'>License Summary:</td><td colspan='2' style='background-color:transparent;border:0px;padding:0px'>" . htmlspecialchars(stripslashes($licenseAbstract), ENT_QUOTES, 'UTF-8') . "</td></tr>";
			if ($licenseURL != "")
			{
				$dprv_license_html .= "<tr><td colspan='3' style='height:4px;background-color:transparent;padding:0px;border:0px'></td></tr>";
				$dprv_license_html .= "<tr><td style='background-color:transparent;padding:0px;padding-left:4px;border:0px'>License URL:</td><td colspan='2' style='background-color:transparent;border:0px;padding:0px'><a href='" . htmlspecialchars(stripslashes($licenseURL), ENT_QUOTES, 'UTF-8') . "' target='_blank' rel='license'>" . htmlspecialchars(stripslashes($licenseURL), ENT_QUOTES, 'UTF-8') . "</a></td></tr>";
			}

			$dprv_license_html .= "</tbody></table></div>";
		}
		$DigiproveNotice .= '<!--' . $dprv_digital_fingerprint . '-->';
		$DigiproveNotice .= '</' . $dprv_container . '>';
	}
	return $DigiproveNotice;
}

?>