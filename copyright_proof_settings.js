//<![CDATA[

// FUNCTIONS TO SUPPORT COPYRIGHT PROOF SETTINGS PAGE

// Tab Display Functions
function DisplayAdvanced()
{
	if (dprv_enrolled == "No")
	{
		return;
	}
	document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
	document.getElementById("AdvancedTab").style.borderBottom="0px";
	document.getElementById("LicenseTab").style.borderBottom="1px solid #666666";
	document.getElementById("ContentTab").style.borderBottom="1px solid #666666";
	document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
	document.getElementById("BasicPart1").style.display="none";
	document.getElementById("BasicPart2").style.display="none";
	if (document.getElementById("BasicPart3") != null)
	{
		document.getElementById("BasicPart3").style.display="none";
	}	
	document.getElementById("AdvancedPart1").style.display="";
	document.getElementById("AdvancedPart2").style.display="";
	document.getElementById("Content").style.display="none";
	document.getElementById("License").style.display="none";
	document.getElementById("CopyProtect").style.display="none";
	HideHelpText();
}


function DisplayLicenseTab()
{
	if (dprv_enrolled == "No")
	{
		return;
	}
	document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
	document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
	document.getElementById("LicenseTab").style.borderBottom="0px";
	document.getElementById("ContentTab").style.borderBottom="1px solid #666666";
	document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
	document.getElementById("BasicPart1").style.display="none";
	document.getElementById("BasicPart2").style.display="none";
	if (document.getElementById("BasicPart3") != null)
	{
		document.getElementById("BasicPart3").style.display="none";
	}	
	document.getElementById("AdvancedPart1").style.display="none";
	document.getElementById("AdvancedPart2").style.display="none";
	document.getElementById("Content").style.display="none";
	document.getElementById("License").style.display="";
	document.getElementById("CopyProtect").style.display="none";
	HideHelpText();
}

function DisplayContentTab()
{
	if (dprv_enrolled == "No")
	{
		return;
	}
	document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
	document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
	document.getElementById("LicenseTab").style.borderBottom="1px solid #666666";
	document.getElementById("ContentTab").style.borderBottom="0px";
	document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
	document.getElementById("BasicPart1").style.display="none";
	document.getElementById("BasicPart2").style.display="none";
	if (document.getElementById("BasicPart3") != null)
	{
		document.getElementById("BasicPart3").style.display="none";
	}	
	document.getElementById("AdvancedPart1").style.display="none";
	document.getElementById("AdvancedPart2").style.display="none";
	document.getElementById("License").style.display="none";
	document.getElementById("Content").style.display="";
	document.getElementById("CopyProtect").style.display="none";
	HideHelpText();
}


function DisplayCopyProtect()
{
	if (dprv_enrolled == "No")
	{
		return;
	}
	document.getElementById("BasicTab").style.borderBottom="1px solid #666666";
	document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
	document.getElementById("LicenseTab").style.borderBottom="1px solid #666666";
	document.getElementById("ContentTab").style.borderBottom="1px solid #666666";
	document.getElementById("CopyProtectTab").style.borderBottom="0px";
	document.getElementById("BasicPart1").style.display="none";
	document.getElementById("BasicPart2").style.display="none";
	if (document.getElementById("BasicPart3") != null)
	{
		document.getElementById("BasicPart3").style.display="none";
	}	
	document.getElementById("AdvancedPart1").style.display="none";
	document.getElementById("AdvancedPart2").style.display="none";
	document.getElementById("Content").style.display="none";
	document.getElementById("License").style.display="none";
	document.getElementById("CopyProtect").style.display="";
	HideHelpText();
}


// FUNCTIONS FOR BASIC TAB:

function DisplayBasic()
{
	document.getElementById("BasicTab").style.borderBottom="0px";
	document.getElementById("AdvancedTab").style.borderBottom="1px solid #666666";
	document.getElementById("LicenseTab").style.borderBottom="1px solid #666666";
	document.getElementById("ContentTab").style.borderBottom="1px solid #666666";
	document.getElementById("CopyProtectTab").style.borderBottom="1px solid #666666";
	document.getElementById("BasicPart1").style.display="";
	document.getElementById("BasicPart2").style.display="";
	if (document.getElementById("BasicPart3") != null)
	{
		document.getElementById("BasicPart3").style.display="";
	}
	document.getElementById("AdvancedPart1").style.display="none";
	document.getElementById("AdvancedPart2").style.display="none";
	document.getElementById("Content").style.display="none";
	document.getElementById("License").style.display="none";
	document.getElementById("CopyProtect").style.display="none";
	HideHelpText();
}

// Stuff required to deal with annoying FF3.5 bug
function SavePassword()
{
	dprv_SavedPassword = document.getElementById("dprv_password").value;
}

function ScheduleRestorePassword()
{
	setTimeout("RestorePassword()",100);
}

function RestorePassword()
{
	if (navigator.userAgent.indexOf("Firefox/3.5") > -1)
	{
		document.getElementById("dprv_password").value = dprv_SavedPassword;
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

function ResendEmail()
{
	document.getElementById("dprv_action").value = "ResendEmail";
	document.getElementById("dprv_cp").submit();
}

function SyncUser()
{
	document.getElementById("dprv_action").value = "SyncUser";
	document.getElementById("dprv_cp").submit();
}


function toggleCredentials()
{
	if (document.getElementById("dprv_enrolled").value == "Yes")
	{
		document.getElementById("dprv_resend_activation").style.display="";
		document.getElementById("dprv_register_row0").style.display="none";
		document.getElementById("dprv_register_row1").style.display="none";
		document.getElementById("dprv_user_id_row1").style.display="";
		document.getElementById("dprv_user_id_row2").style.display="";
		document.getElementById("dprv_user_id_labelA").style.display="";
		document.getElementById("dprv_user_id_labelB").style.display="none";
		document.getElementById("dprv_api_key_row_0").style.display="";
		document.getElementById("dprv_api_key_row_1").style.display="";
		document.getElementById("dprv_api_key_row_2").style.display="";
		document.getElementById("dprv_password_row1").style.display="none";
		document.getElementById("dprv_password_row2").style.display="none";
		document.getElementById("dprv_password_row3").style.display="none";
		document.getElementById("dprv_submit").value = dprv_literals["Update Settings"];
		renewApiKey();
	}
	else
	{
		document.getElementById("dprv_resend_activation").style.display="none";
		document.getElementById("dprv_register_row0").style.display="";
		document.getElementById("dprv_register_row1").style.display="";
		document.getElementById("dprv_api_key_row_0").style.display="none";
		document.getElementById("dprv_api_key_row_1").style.display="none";
		document.getElementById("dprv_api_key_row_2").style.display="none";
		document.getElementById("dprv_user_id_labelA").style.display="none";
		document.getElementById("dprv_user_id_labelB").style.display="";
		if (document.getElementById("dprv_sub_row0"))
		{
			document.getElementById("dprv_sub_row0").style.display="none";
			document.getElementById("dprv_sub_row1").style.display="none";
		}

		if (document.getElementById("dprv_register_yes").checked == true)
		{
			//document.getElementById("dprv_user_id").disabled=false;
			document.getElementById("dprv_user_id_row1").style.display="";
			document.getElementById("dprv_user_id_row2").style.display="";
			document.getElementById("dprv_password_row1").style.display="";
			document.getElementById("dprv_password_row2").style.display="";
			document.getElementById("dprv_password_row3").style.display="";
			document.getElementById("dprv_submit").value = dprv_literals["Update & Register"];
		}
		else
		{
			//document.getElementById("dprv_user_id").disabled=true;
			document.getElementById("dprv_user_id_row1").style.display="none";
			document.getElementById("dprv_user_id_row2").style.display="none";
			document.getElementById("dprv_password_row1").style.display="none";
			document.getElementById("dprv_password_row2").style.display="none";
			document.getElementById("dprv_password_row3").style.display="none";
			document.getElementById("dprv_submit").value = dprv_literals["Update Settings"];
		}
	}
	HideHelpText();
}

function EnableRegistrationInputs()
{
	if (confirm("Once you have registered successfully and the plugin is working it should not be necessary to modify any of these values, and entering an incorrect value may cause the plugin to stop working.  If you are sure you wish to proceed, press OK."))
	{
		document.getElementById("dprv_enrolled").disabled=false;
		document.getElementById("dprv_register_yes").disabled=false;
		document.getElementById("dprv_register_no").disabled=false;
		document.getElementById("dprv_user_id").disabled=false;
		document.getElementById("dprv_renew_api_key").disabled=false;
		document.getElementById("dprv_input_api_key").disabled=false;
		document.getElementById("dprv_password").disabled=false;
		document.getElementById("dprv_pw_confirm").disabled=false;
		document.getElementById("dprv_change_reg").style.display = "none";
		return true;
	}
	return false;
}


function UserIdChanged()
{
	if (dprv_lastUserId == document.getElementById('dprv_user_id').value)
	{
		return true;
	}
	if ((dprv_last_result.indexOf("Digiprove certificate id") !== -1 || dprv_last_result.indexOf("User already activated") !== -1) && dprv_lastUserId != "")
	{
		if (!confirm("You are changing your Digiprove User Id. This may cause the plugin to stop working.  Press OK if you are sure, or Cancel to restore the previous value."))
		{
			document.getElementById('dprv_user_id').value = dprv_lastUserId;
			return false;
		}
	}

	dprv_lastUserId = document.getElementById('dprv_user_id').value;
	if (document.getElementById("dprv_sub_row0"))
	{
		document.getElementById("dprv_sub_row0").style.display="none";
		document.getElementById("dprv_sub_row1").style.display="none";
	}
	document.getElementById('dprv_renew_api_key').checked=true;
	renewApiKey();
	return true;
}

function ApiKeyChange()
{
	document.getElementById('dprv_api_key').value = document.getElementById('dprv_api_key').value.replace(/^\s\s*/, '').replace(/\s\s*$/, '');		// trim because precision is important here
	if (dprv_lastApiKey == document.getElementById('dprv_api_key').value)
	{
		return true;
	}
	if (dprv_lastApiKey == "")
	{
		if (!confirm("You have entered a value for API Key - do this only if the new API key was obtained from Digiprove"))
		{
			document.getElementById('dprv_api_key').value = dprv_lastApiKey;
			return false;
		}
	}
	else
	{
		if (document.getElementById('dprv_api_key').value == "")
		{
			alert("An empty api key is invalid.");
			document.getElementById('dprv_api_key').value = dprv_lastApiKey;
			return false;
		}
		var length_message = "";
		if (document.getElementById('dprv_api_key').value.length != 22)
		{
			length_message = " That does not look like a valid API key which is normally 22 characters.";
		}
		if (!confirm("You are changing the API Key - do this only if the new API key was obtained from Digiprove, otherwise the plugin will stop working." + length_message + " Press OK to proceed with change or Cancel to restore original value"))
		{
			document.getElementById('dprv_api_key').value = dprv_lastApiKey;
			return false;
		}
	}
	dprv_lastApiKey = document.getElementById('dprv_api_key').value;
	return true;
}

function renewApiKey()	// Toggle display settings dependent on renew api key checkbox
{
	if (document.getElementById("dprv_renew_api_key").checked == true)
	{
		document.getElementById("dprv_input_api_key").checked = false;
		document.getElementById("dprv_api_key").disabled = true;
	}
	else
	{
		if (document.getElementById("dprv_api_key").value == "")
		{
			if (dprv_lastApiKey == "")
			{
				document.getElementById("dprv_input_api_key").checked = true;
				document.getElementById("dprv_api_key").disabled = false;
			}
			else
			{
				document.getElementById("dprv_input_api_key").checked = false;
				document.getElementById("dprv_api_key").value = dprv_lastApiKey;
				document.getElementById("dprv_api_key").disabled = true;
			}
		}
	}
	toggleApiKey();
}

function inputApiKey()	// Toggle display settings dependent on renew api key checkbox
{
	if (document.getElementById("dprv_input_api_key").checked == true)
	{
		document.getElementById("dprv_renew_api_key").checked = false;
	}
	else
	{
		if (document.getElementById("dprv_api_key").value == "")
		{
			if (dprv_lastApiKey == "")
			{
				document.getElementById("dprv_renew_api_key").checked = true;
			}
			else
			{
				document.getElementById("dprv_renew_api_key").checked = false;
				document.getElementById("dprv_api_key").value = dprv_lastApiKey;
			}
		}
	}
	toggleApiKey();
}

function toggleApiKey()
{
	if (document.getElementById("dprv_renew_api_key").disabled == false)
	{
		if (document.getElementById("dprv_input_api_key").checked == true)
		{
			if (dprv_lastApiKey == "")
			{
				if (!confirm("Tou should only input a value for API Key if you have already obtained this API key from Digiprove."))
				{
					document.getElementById("dprv_input_api_key").checked = false;
					return false;
				}
			}
			else
			{
				if (!confirm("Do this only if the new API key was obtained from Digiprove, otherwise the plugin will stop working."))
				{
					document.getElementById("dprv_input_api_key").checked = false;
					restoreApiKey();
					return false;
				}
			}
		}
		if (document.getElementById("dprv_renew_api_key").checked == true)
		{
			if (document.getElementById("dprv_enrolled").value == "Yes")
			{
				document.getElementById("dprv_api_key_caption").innerHTML = "Current API Key:"
				document.getElementById("dprv_api_key").disabled = true;
				document.getElementById("dprv_api_key").style.backgroundColor = "#EEEEEE";
				document.getElementById("dprv_api_key_row_2").style.display="";
				if (document.getElementById("dprv_api_key").value != "" && dprv_lastApiKey != "")
				{
					document.getElementById("dprv_api_key").value = dprv_lastApiKey;
				}
			}
			else
			{
				document.getElementById("dprv_api_key_row_2").style.display="none";
			}
			document.getElementById("dprv_password_label").innerHTML = "Enter Password:";
			document.getElementById("dprv_password_row1").style.display="";
			document.getElementById("dprv_password_row2").style.display="";
			document.getElementById("dprv_password").focus();
		}
		else
		{
			document.getElementById("dprv_password_label").innerHTML = "Select a Password:";
			document.getElementById("dprv_password_row1").style.display="none";
			document.getElementById("dprv_password_row2").style.display="none";
			if (document.getElementById("dprv_input_api_key").checked == true)
			{
				document.getElementById("dprv_api_key_row_2").style.display="";
				document.getElementById("dprv_api_key_caption").innerHTML = "Enter API Key:"
				document.getElementById("dprv_api_key").disabled = false;
				document.getElementById("dprv_api_key").style.backgroundColor = "";
				document.getElementById("dprv_api_key").focus();
			}
			else
			{
				document.getElementById("dprv_api_key").disabled = true;
				document.getElementById("dprv_api_key").style.backgroundColor = "#EEEEEE";
				document.getElementById("dprv_api_key_caption").innerHTML = "Current API Key:"
			}
		}
	}
	return true;
}

function restoreApiKey()
{
	if (dprv_savedApiKey != "")
	{
		document.getElementById("dprv_api_key").value = dprv_savedApiKey;
		document.getElementById("dprv_api_key_caption").innerHTML = "Current API Key:"
		document.getElementById("dprv_api_key").disabled = true;
		document.getElementById("dprv_api_key").style.backgroundColor = "#EEEEEE";
		document.getElementById("dprv_api_key_row_2").style.display="";
	}
}

function ShowPersonalDetailsText()
{
	DisplayHelpText('Copyright Proof uses the Digiprove service (<a href="http://www.digiprove.com/creative-and-copyright.aspx" target="_blank">www.digiprove.com</a>) to certify the content and timestamp of your Wordpress posts. Digiprove needs the name of the person claiming copyright and a valid email address to which the digitally-signed content certificates will be sent. The personal details on this panel will be used only in automatically creating your account at Digiprove.  The server records of this information are accessible at <a href="https://www.digiprove.com/members/preferences.aspx" target="_blank">https://www.digiprove.com/members/preferences.aspx</a>.');
}

function ShowPrivacyText()
{
	DisplayHelpText('The Copyright Proof notice appearing at the foot of your blog posts contains a link to a web-page showing details of the Digiprove certificate of your content. If you do not want your name to appear on this page select the &#39;Keep private&#39; option. Your email address and Digiprove User id are never revealed. Click <a href="http://www.digiprove.com/privacypolicy.aspx" target="_blank">here</a> to read the full privacy statement.');
}

function ShowEmailCertText()
{
	DisplayHelpText('Every post you publish or update will cause a cryptographically encoded certificate to be created which is your proof that your content was published by you at that exact time and date. This is retained for you in case it is required in future. Each certificate may be downloaded at the Digiprove website if required - you will need a current subscription to do this. Digiprove subscribers have the option to receive their certificates automatically by email. To set this option, select &#39;Yes&#39; here.  You will still be able to download these certificates from the Digiprove site if required.');
}

function ShowAPIText(domain_name, password_on_record)
{
	var dprv_api_text='A Digiprove API key for ' + dprv_blog_host + ' is required for this domain to use the Digiprove service.';
	if (document.getElementById('dprv_api_key').value == "")
	{
		if (password_on_record == 'Yes')
		{
			dprv_api_text += ' If you have already registered, you do not need to do anything, it will be filled in automatically when required. You can also obtain an API key <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">from the Digiprove members&#39; website</a> (you will be asked to log in)';
		}
		else
		{
			dprv_api_text += ' If you are already registered with Digiprove, you can obtain your API key for this domain by ticking the &quot;Obtain New API Key&quot; box above (you will be asked for your password).  API keys can also be obtained at the <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">Digiprove members&#39; website</a> (you will be asked to log in)';
		}
	}
	else
	{
		dprv_api_text += ' If you registered from this page this field will have been filled in automatically - there is no need to change it. If you are receiving error messages regarding your api key you can obtain a new one by ticking &quot;Obtain New Api Key&quot; box (you will need to input your password) or it can also be done from the <a href="https://www.digiprove.com/members/api_keys.aspx" target="_blank">Digiprove  members&#39; website</a> (you will be asked to log in)';
	}
	DisplayHelpText(dprv_api_text);
}

function ShowPasswordText()
{
	DisplayHelpText('Your password to give you access to the Digiprove website members&#39; area. An encrypted version of the password is stored on the Digiprove server but <em>not here on your Wordpress server</em>.');
}

function ShowRegistrationText()
{
	DisplayHelpText('Copyright Proof uses the Digiprove service (<a href="http://www.digiprove.com/creative-and-copyright.aspx" target="_blank">www.digiprove.com</a>) to certify the content and timestamp of your Wordpress posts. You need to register with Digiprove before Copyright Proof will start working for you; by selecting &quot;Yes, register me now&quot; this registration process will take place; you will then receive an email with an activation link.');
}

function ShowTermsOfUseText()
{
	DisplayHelpText('Using this plugin, the core Digiprove services are provided free-of-charge.  There are <a href="http://www.digiprove.com/termsofuse_page.aspx" target="_blank">terms of use</a> governing things like privacy and abuse. There are some premium services that are available only to Digiprove subscribers');
}

// END OF FUNCTIONS FOR BASIC TAB


// FUNCTIONS FOR ADVANCED TAB:
function createOwnText(element)
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Custom License");
		element.value="";
	}
	Preview();
}

function noBackgroundChanged(element)
{
	if(element.checked==true)
	{
		lastBackgroundColor=document.getElementById('dprv_notice_background').value;
		lastBackgroundTextColor=document.getElementById('dprv_notice_background').style.color;
		document.getElementById('dprv_notice_background').value='None';
		document.getElementById('dprv_notice_background').style.backgroundColor='';
		document.getElementById('dprv_notice_background').style.color='';
	}
	else
	{
		if (lastBackgroundColor == "")
		{
			lastBackgroundColor = "#FFFFFF";
			lastBackgroundTextColor = "#000000";
		}
		document.getElementById('dprv_notice_background').value=lastBackgroundColor;
		document.getElementById('dprv_notice_background').style.backgroundColor=lastBackgroundColor;
		document.getElementById('dprv_notice_background').style.color=lastBackgroundTextColor;
	}
	Preview();
}

function noBorderChanged(element)
{
	if(element.checked==true)
	{
		lastBorderColor=document.getElementById('dprv_notice_border').value;
		lastBorderTextColor=document.getElementById('dprv_notice_border').style.color;
		document.getElementById('dprv_notice_border').value='None';
		document.getElementById('dprv_notice_border').style.backgroundColor='';
		document.getElementById('dprv_notice_border').style.color='';
	}
	else
	{
		if (lastBorderColor == "")
		{
			lastBorderColor = "#BBBBBB";
			lastBorderTextColor = "#000000";
		}
		document.getElementById('dprv_notice_border').value=lastBorderColor;
		document.getElementById('dprv_notice_border').style.backgroundColor=lastBorderColor;
		document.getElementById('dprv_notice_border').style.color=lastBorderTextColor;
	}
	Preview();
}


function setCheckboxes()
{
	if (document.getElementById("dprv_notice_background").value != "None")
	{
		document.getElementById('dprv_no_background').checked = false;
	}
	if (document.getElementById("dprv_notice_border").value != "None")
	{
		document.getElementById('dprv_no_border').checked = false;
	}
}

function Preview()
{
	var notice_text = document.getElementById("dprv_notice").value;
	if (document.getElementById("dprv_custom_notice").value != "")
	{
		notice_text = document.getElementById("dprv_custom_notice").value;
	}
	var c_notice = document.getElementById("dprv_c_notice").value;
	var notice_font_size = "11px";
	var image_scale = "";

	var a_height = "16px";
	var txt_valign = "0px";
	var img_valign = "-3px";
	var outside_font_size = "13px";
	if (document.getElementById("dprv_notice_small").checked == true)
	{
		notice_font_size="10px";
		txt_valign = "1px";;
	}
	if (document.getElementById("dprv_notice_smaller").checked == true)
	{
		notice_font_size="9px";
		image_scale=" width='12px' height='12px'";
		a_height = "12px";
		outside_font_size = "10px";
		img_valign = "-2px";
	}

	var notice_color = document.getElementById("dprv_notice_color").value;
	var hover_color = document.getElementById("dprv_hover_color").value;
	var background_color = document.getElementById("dprv_notice_background").value;
	var notice_background_css = "";
	if (background_color != "None")
	{
		notice_background_css = "background-color:" + background_color + ";";
	}
	var border_color = document.getElementById("dprv_notice_border").value;
	var border_css = "border:0px;"
	if (border_color != "None")
	{
		border_css = "border:1px solid " + border_color + ";";
	}
	var now = new Date();
	
	var DigiproveNotice = "<span lang='en' xml:lang='en' style='vertical-align:middle; display:inline; padding:2px; margin-top:2px; margin-bottom:2px; height:auto; float:none; line-height:normal; font-family: Tahoma, MS Sans Serif; font-size:" + outside_font_size + ";" + border_css + notice_background_css + "' >";

	DigiproveNotice += "<span style='height:" + a_height + "; border:0px; padding:0px; margin:0px; float:none; display:inline; text-decoration: none; background-color:transparent; line-height:normal; font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:" + notice_font_size + ";'>";

	DigiproveNotice += "<img src='" + dprv_home + "/wp-content/plugins/digiproveblog/dp_seal_trans_16x16.png' style='vertical-align:" + img_valign + "; display:inline; border:0px; margin:0px; float:none; background-color:transparent' border='0'" + image_scale + "/>";

	DigiproveNotice += "<span  style='font-family: Tahoma, MS Sans Serif; font-style:normal; font-weight:normal; font-size:" + notice_font_size + ";color:" + notice_color + "; border:0px; float:none; text-decoration: none; letter-spacing:normal; vertical-align:" + txt_valign + ";' onmouseover=\"this.style.color='" + hover_color + "'\" onmouseout=\"this.style.color='" + notice_color + "'\" >";
	
	DigiproveNotice += "&nbsp;&nbsp;" + notice_text;
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
	DigiproveNotice += "</span></span></span>";
	document.getElementById("dprv_notice_preview").innerHTML = DigiproveNotice;
}

function ToggleFooterWarning()
{
	if (document.getElementById("dprv_footer").checked == true)
	{
		document.getElementById("footer_warning_link").style.display = "";
	}
	else
	{
		document.getElementById("footer_warning_link").style.display = "none";
	}
}
function ShowMultiPostText()
{
	DisplayHelpText('Tick this to allow the Digiprove notice to be included in post excerpts that appear on multi-post pages such as search results, archive pages etc.');
}

function ShowFooterText()
{
	DisplayHelpText('Please check how this looks on your site.  Whether and where the notice appears is determined by your theme.  To change this, look for wp_footer() in the footer.php file of your theme.');
}

// END OF FUNCTIONS FOR ADVANCED TAB


// FUNCTIONS FOR CONTENT TAB:
function ShowFingerprintText(hash_supported)
{
	var caution = ''
	if (hash_supported=='No')
	{
		caution = ' <b><font color="red">Your version of PHP does not support this function.</b</font> Ask your provider to upgrade you to PHP 5.1.2 or later.'
	}
	DisplayHelpText('If you include media files (such as images, sounds, video, pdf etc.) within your posts or pages, you may instruct Digiprove to certify the digital fingerprints of the individual files, as well the html and text of the post itself.' + caution);
}

function ShowBetaText()
{
	DisplayHelpText('Note the media file Digiproving functions are in Beta form. We have tested them in a number of environments, but we are anxious for your feedback.  If you experience problems, firstly please advise us at support@digiprove.com, so we can fix the underlying problem. To get rid of problems, simply untick all of these boxes, or press &quot;Clear all&quot;.');
}

function toggleMedia(element)
{
	var targetId = element.id + '_ie_col';
	if (element.checked == true)
	{
		if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
		{
			SubscribersOnly("Content File Fingerprinting");
			return false;
		}
		document.getElementById(targetId).style.visibility = 'visible';
	}
	else
	{
		document.getElementById(targetId).style.visibility = 'hidden';
	}
	return true;
}
function toggleInclExcl(element)
{
	var pos = element.name.lastIndexOf("_ie");
	var id_root = element.name.substr(0,pos);
	var checkbox_0 = document.getElementById(id_root + "_types_0");
	pos = checkbox_0.name.lastIndexOf("_");
	if (checkbox_0.name.substr(pos+1) == "All")
	{
		if (element.value == "Include")
		{
			document.getElementById(id_root + "_labels_0").style.visibility="visible";
			document.getElementById(id_root + "_types_0").style.visibility="visible";
		}
		else
		{
			document.getElementById(id_root + "_labels_0").style.visibility="hidden";
			document.getElementById(id_root + "_types_0").style.visibility="hidden";
		}
		toggleMimeTypes(checkbox_0);
	}
}

function toggleMimeTypes(element)
{
	var pos = element.id.lastIndexOf("_0");
	var id_root = element.id.substr(0,pos);
	var option_counter = 1;
	var disabled = false;
	if (element.checked == true && element.style.visibility != "hidden")
	{
		disabled = true;
	}
	while (option_counter != -1)
	{
		if (document.getElementById(id_root + '_' + option_counter))
		{
			document.getElementById(id_root + '_' + option_counter).disabled = disabled;
			option_counter++;
		}
		else
		{
			option_counter = -1;
			break;
		}
	}
}
function default_html_tags()
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Digiprove Files");
		return;
	}
	document.getElementById("dprv_action").value = "DefaultHTMLTags";
	document.getElementById("dprv_cp").submit();
}
function clear_html_tags()
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Digiprove Files");
		return;
	}
	document.getElementById("dprv_action").value = "ClearHTMLTags";
	document.getElementById("dprv_cp").submit();
}

// END OF FUNCTIONS FOR CONTENT TAB


// FUNCTIONS FOR LICENSE TAB:

function PreviewLicense()
{
	if (document.getElementById("dprv_license").value == "0")
	{
		document.getElementById('dprv_amend_license_button').style.display='none';
		document.getElementById('dprv_remove_license_button').style.display='none';
		document.getElementById("dprv_license_caption").innerHTML = "";
		document.getElementById("dprv_license_abstract").innerHTML = "";
		document.getElementById("dprv_license_url").innerHTML = "";
		document.getElementById("dprv_license_url").href = "";
	}
	else
	{
		document.getElementById('dprv_amend_license_button').style.display='';
		document.getElementById('dprv_remove_license_button').style.display='';
		for (var i=0; i<dprv_licenseIds.length; i++)
		{
			if (dprv_licenseIds[i] == document.getElementById("dprv_license").value)
			{
				document.getElementById("dprv_license_caption").innerHTML = dprv_licenseCaptions[i];
				document.getElementById("dprv_license_abstract").innerHTML = dprv_licenseAbstracts[i];
				document.getElementById("dprv_license_url").innerHTML = dprv_licenseURLs[i];
				document.getElementById("dprv_license_url").href = dprv_licenseURLs[i];
				break;
			}
		}
	}
	document.getElementById("dprv_license_caption").style.display = "";
	document.getElementById("dprv_license_abstract").style.display = "";
	document.getElementById("dprv_license_url").style.display = "";
	document.getElementById("dprv_custom_license_caption").style.display = "none";
	document.getElementById("dprv_custom_license_abstract").style.display = "none";
	document.getElementById("dprv_custom_license_url").style.display = "none";
}


function AddLicense()
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Add license");
		return false;
	}
	document.getElementById('dprv_license_heading').innerHTML = 'New License Type';
	document.getElementById('dprv_license_type_caption').innerHTML = 'License Type Name';
	document.getElementById('dprv_license').style.display='none';
	document.getElementById('dprv_license_caption').style.display='none';
	document.getElementById('dprv_license_abstract').style.display='none';
	document.getElementById('dprv_license_url').style.display='none';
	document.getElementById('License_customization').style.display='none';
	document.getElementById('dprv_custom_license').style.display='';
	document.getElementById('dprv_custom_license_caption').style.display='';
	document.getElementById('dprv_custom_license_abstract').style.display='';
	document.getElementById('dprv_custom_license_url').style.display='';
	document.getElementById('dprv_custom_license').value ='';
	document.getElementById('dprv_custom_license_abstract').value='';
	document.getElementById('dprv_custom_license_url').value='';
	document.getElementById('dprv_license_commit_0').style.display='';
	document.getElementById('dprv_license_commit_1').style.display='';
	document.getElementById('dprv_license_commit').value='Add this license';
	document.getElementById('dprv_submit').style.display='none';	
	document.getElementById('dprv_custom_license').focus();
	return true;
}
function AmendLicense()
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Amend license");
		return false;
	}
	document.getElementById('dprv_license_heading').innerHTML = 'Amend License Type';
	document.getElementById('dprv_license').style.display='none';
	document.getElementById('dprv_license_caption').style.display='none';
	document.getElementById('dprv_license_abstract').style.display='none';
	document.getElementById('dprv_license_url').style.display='none';
	document.getElementById('License_customization').style.display='none';
	document.getElementById('dprv_custom_license').style.display='';
	document.getElementById('dprv_custom_license_caption').style.display='';
	document.getElementById('dprv_custom_license_abstract').style.display='';
	document.getElementById('dprv_custom_license_url').style.display='';
	for (var i=0; i<dprv_licenseIds.length; i++)
	{
		if (document.getElementById('dprv_license').value == dprv_licenseIds[i])
		{
			document.getElementById('dprv_custom_license').value = dprv_licenseTypes[i];
			break;
		}
	}
	document.getElementById('dprv_custom_license_caption').value = document.getElementById('dprv_license_caption').innerHTML;
	document.getElementById('dprv_custom_license_abstract').value = document.getElementById('dprv_license_abstract').innerHTML;
	document.getElementById('dprv_custom_license_url').value = document.getElementById('dprv_license_url').innerHTML;
	document.getElementById('dprv_license_commit_0').style.display='';
	document.getElementById('dprv_license_commit_1').style.display='';
	document.getElementById('dprv_license_commit').value='Update this license';
	document.getElementById('dprv_submit').style.display='none';	
	document.getElementById('dprv_custom_license').focus();
	return true;
}
function RemoveLicense()
{
	if (dprv_subscription_type == "Basic" || dprv_subscription_type == "" || dprv_subscription_expired == "Yes" )
	{
		SubscribersOnly("Remove license");
		return false;
	}
	for (var i=0; i<dprv_licenseIds.length; i++)
	{
		if (document.getElementById('dprv_license').value == dprv_licenseIds[i])
		{
			if (confirm("Remove License Type " + dprv_licenseTypes[i] + "?  (This operation cannot be undone)"))
			{
					document.getElementById("dprv_action").value = "RemoveLicense";
					document.getElementById("dprv_cp").submit();
			}
			else
			{
				return false;
			}
			break;
		}
	}
	return true;
}

function LicenseActionCommit()
{
	// Do a bit of validation
	{
			if (document.getElementById('dprv_custom_license').value == "")
			{
				alert("Please enter a value for License Type");
				document.getElementById('dprv_custom_license').focus();
				return false;
			}
			if (document.getElementById('dprv_custom_license_abstract').value == "" && !confirm("You have not entered a value for License Summary.  Press OK if this is intentional"))
			{
				document.getElementById('dprv_custom_license_abstract').focus();
				return false;
			}

			if (document.getElementById('dprv_license_commit').value == 'Update this license')
			{
				document.getElementById("dprv_action").value = "UpdateLicense";
			}
			else
			{
				document.getElementById("dprv_action").value = "AddLicense";
			}
			document.getElementById("dprv_cp").submit();
	}
}

function LicenseActionAbandon()
{
	document.getElementById('dprv_license_heading').innerHTML = 'Default License Statement';
	document.getElementById('dprv_license_type_caption').innerHTML = 'License Type:';
	document.getElementById('dprv_license').style.display='';
	document.getElementById('dprv_license_caption').style.display='';
	document.getElementById('dprv_license_abstract').style.display='';
	document.getElementById('dprv_license_url').style.display='';
	document.getElementById('License_customization').style.display='';
	document.getElementById('dprv_custom_license').style.display='none';
	document.getElementById('dprv_custom_license_caption').style.display='none';
	document.getElementById('dprv_custom_license_abstract').style.display='none';
	document.getElementById('dprv_custom_license_url').style.display='none';
	document.getElementById('dprv_custom_license').value='';
	document.getElementById('dprv_custom_license_abstract').value='';
	document.getElementById('dprv_custom_license_url').value='';
	document.getElementById('dprv_license_commit_0').style.display='none';
	document.getElementById('dprv_license_commit_1').style.display='none';
	document.getElementById('dprv_submit').style.display='';	
}

// FUNCTIONS FOR COPY-PROTECT TAB:

function toggle_r_c_checkbox()
{
	if (document.getElementById('dprv_frustrate_no').checked == false)
	{
		document.getElementById('dprv_right_click_box').disabled = false;
		document.getElementById('dprv_right_click_box').style.backgroundColor = '#FFFFFF';
		if (document.getElementById('dprv_right_click_box').checked == true)
		{
			document.getElementById('dprv_right_click_message').disabled = false;
			document.getElementById('dprv_right_click_message').style.backgroundColor = '#FFFFFF';
		}
		else
		{
			document.getElementById('dprv_right_click_message').disabled = true;
			document.getElementById('dprv_right_click_message').style.backgroundColor = '#CCCCCC';
		}
	}
	else
	{
		document.getElementById('dprv_right_click_box').disabled = true;
		document.getElementById('dprv_right_click_box').style.backgroundColor = '#CCCCCC';
		document.getElementById('dprv_right_click_message').disabled = true;
		document.getElementById('dprv_right_click_message').style.backgroundColor = '#CCCCCC';
	}

}
function toggle_r_c_text(element)
{
	if (element.checked == true)
	{
		document.getElementById('dprv_right_click_message').disabled = false;
		document.getElementById('dprv_right_click_message').style.backgroundColor = '#FFFFFF';
		if (document.getElementById('dprv_right_click_message').value == "")
		{
			document.getElementById('dprv_right_click_message').value = "Sorry, right-clicking is disabled; please respect copyrights";
		}
	}
	else
	{
		document.getElementById('dprv_right_click_message').value = "";
		document.getElementById('dprv_right_click_message').disabled = true;
		document.getElementById('dprv_right_click_message').style.backgroundColor = '#CCCCCC';
	}
}


function ShowFrustrateCopyText()
{
	DisplayHelpText('Selecting this option will prevent a user from right-clicking on your web-page (in order to view the source), selecting content (in order to copy to clipboard), or pressing CTRL/U (to view the source) in most browsers.  This may prevent the unauthorised use of your content by unsophisticated users, but will be a small nuisance to a determined content thief. This is as good as it gets on the web - DO NOT BELIEVE the claims of some other plugin authors that your content cannot be stolen...');
}



// GENERIC (CROSS-TAB) FUNCTIONS:
function SubmitSelected()
{
	if (document.getElementById("dprv_enrolled").value == "No" && document.getElementById("dprv_register_yes").checked == true)
	{
		if (!confirm("Do you want to proceed with registration at www.digiprove.com?  You will receive an email with an activation link. If you do not want to do this now, press Cancel"))
		{
			document.getElementById("dprv_register_yes").checked = false;
			toggleCredentials();
			return false;
		}
	}
	if (ApiKeyChange())
	{
		if (document.getElementById("message") != null)
		{
			document.getElementById("message").innerHTML = "<p>Processing...</p>";
		}
		return true;
	}
	return false;
}

function SubscribersOnly(f)
{
	if (dprv_subscription_type == "Basic")
	{
		DisplayHelpText('The ' + f + ' function is available only to Digiprove subscribers.  <a href=\"' + dprv_upgrade_link + '&Action=Upgrade\" target=\"_blank\">Select a subscription plan</a>.');
	}
	else
	{
		if (dprv_subscription_type == "")
		{
			DisplayHelpText('The ' + f + ' function is available only to Digiprove subscribers. Please complete registration first.');
		}
		else
		{
			DisplayHelpText('The ' + f + ' function is available only to current Digiprove subscribers. Your ' + dprv_subscription_type + ' account expired on ' + dprv_subscription_expiry + '. <a href=\"' + dprv_upgrade_link + '&Action=Renew\" target=\"_blank\">Renew your subscription plan</a>.');
		}
	}
}

function PremiumOnly(f)
{
	if (dprv_subscription_type == "")
	{
		DisplayHelpText('The ' + f + ' function is available only to Digiprove subscribers at Professional level or above. Please complete registration first.');
	}
	else
	{
		if (dprv_subscription_type == "Personal")
		{
			DisplayHelpText('The ' + f + ' function is not available under your current plan (' + dprv_subscription_type + ').  <a href=\"' + dprv_upgrade_link + '&Action=Upgrade\" target=\"_blank\">Upgrade your subscription plan</a>.');
		}
		else
		{
			DisplayHelpText('The ' + f + ' function is available to subscribers at Professional level and above - your current plan is &quot;' + dprv_subscription_type + '&quot;.  <a href=\"' + dprv_upgrade_link + '&Action=Upgrade\" target=\"_blank\">Upgrade your subscription plan</a>.');
		}
	}
}

function DisplayHelpText(help_text)
{
	document.getElementById('HelpText').innerHTML = help_text;
	document.getElementById('HelpTextContainer').style.display='';
	document.getElementById('HelpTextContainer').style.borderColor='red';
	setTimeout("document.getElementById('HelpTextContainer').style.borderColor='black';",1000);

}
function HideHelpText()
{
	document.getElementById('HelpTextContainer').style.display='none';
}

//]]>