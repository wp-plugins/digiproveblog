//<![CDATA[

// FUNCTIONS TO SUPPORT THE COPYRIGHT/OWNERSHIP PANEL IN EDIT/NEW POST
function TogglePanel()
{
	if (document.getElementById('dprv_this_yes').checked == true)
	{
		//document.getElementById('dprv_notice_preview').style.display = "";
		document.getElementById('dprv_copyright_panel_body').style.display = "";
		if (document.getElementById('publish_dp'))
		{
			document.getElementById('publish_dp').style.display="";
		}
	}
	else
	{
		//document.getElementById('dprv_notice_preview').style.display = "none";
		document.getElementById('dprv_copyright_panel_body').style.display = "none";
		if (document.getElementById('publish_dp'))
		{
			document.getElementById('publish_dp').style.display="none";
		}
	}
}

function ToggleAttributions()
{
	if (document.getElementById('dprv_all_original_yes').checked == true)
	{
		document.getElementById('dprv_attributions_0').style.display = "none";
		document.getElementById('dprv_attributions_1').style.display = "none";
	}
	else
	{
		document.getElementById('dprv_attributions_0').style.display = "";
		document.getElementById('dprv_attributions_1').style.display = "";
	}
}

function ToggleDefault()
{
	if (document.getElementById('dprv_default_license').checked == true)
	{
		document.getElementById('dprv_custom_license').checked = false;
		document.getElementById('dprv_this_license_label').style.display="";
		document.getElementById('dprv_license_type').style.display="none";
		document.getElementById('dprv_license_type').value = dprv_defaultLicenseId;
		document.getElementById('dprv_license_input').style.display="none";
		document.getElementById('dprv_this_license_label').innerHTML = dprv_default_licenseType;
		ToggleCustom();
		SetLicense();
	}
	else
	{
		document.getElementById('dprv_this_license_label').style.display="none";
		document.getElementById('dprv_license_type').style.display="";
	}
}

function ToggleCustom()
{
	if (document.getElementById('dprv_custom_license').checked == true)
	{
		document.getElementById('dprv_default_license').checked = false;
		document.getElementById('dprv_this_license_label').style.display="none";
		document.getElementById('dprv_license_type').style.display="none";
		document.getElementById('dprv_license_input').style.display="";
		document.getElementById('dprv_license_caption_label').style.display="none";
		document.getElementById('dprv_license_abstract_label').style.display="none";
		document.getElementById('dprv_license_caption').style.display="";
		document.getElementById('dprv_license_abstract').style.display="";
		document.getElementById('dprv_license_url_link').style.display="none";
		document.getElementById('dprv_license_url_input').style.display="";
		document.getElementById('dprv_license_input').focus();
	}
	else
	{
		document.getElementById('dprv_license_input').style.display="none";
		document.getElementById('dprv_license_caption_label').style.display="";
		document.getElementById('dprv_license_abstract_label').style.display="";
		document.getElementById('dprv_license_caption').style.display="none";
		document.getElementById('dprv_license_abstract').style.display="none";
		document.getElementById('dprv_license_url_link').style.display="";
		document.getElementById('dprv_license_url_input').style.display="none";
		if (document.getElementById('dprv_default_license').checked == true)
		{
			document.getElementById('dprv_this_license_label').style.display="";
			document.getElementById('dprv_license_type').style.display="none";
		}
		else
		{
			document.getElementById('dprv_this_license_label').style.display="none";
			document.getElementById('dprv_license_type').style.display="";
			document.getElementById('dprv_license_type').focus();
		}
	}
}

function LicenseChanged()
{
	if (document.getElementById('dprv_license_type').value != dprv_defaultLicenseId)
	{
		document.getElementById('dprv_default_license').checked = false;
	}
	SetLicense();
}

function SetLicense()
{
	if (document.getElementById('dprv_license_type').value == 0)
	{
		document.getElementById('dprv_this_license_label').innerHTML = "None";
		document.getElementById('dprv_license_caption_label').innerHTML = "";
		document.getElementById('dprv_license_abstract_label').innerHTML = "";
		document.getElementById('dprv_license_url_link').href = "";
		document.getElementById('dprv_license_url_link').innerHTML = "";
	}
	else
	{
		for (i=0; i<dprv_licenseIds.length; i++)
		{
			if (dprv_licenseIds[i] == document.getElementById('dprv_license_type').value)
			{
				document.getElementById('dprv_license_caption_label').innerHTML = dprv_licenseCaptions[i];
				document.getElementById('dprv_license_abstract_label').innerHTML = dprv_licenseAbstracts[i];
				document.getElementById('dprv_license_url_link').href = dprv_licenseURLs[i];
				document.getElementById('dprv_license_url_link').innerHTML = dprv_licenseURLs[i];
				document.getElementById('dprv_license_caption').style.display = "none";
				document.getElementById('dprv_license_abstract').style.display = "none";
				document.getElementById('dprv_license_caption_label').style.display = "";
				document.getElementById('dprv_license_abstract_label').style.display = "";
				document.getElementById('dprv_license_url_input').style.display = "none";
				document.getElementById('dprv_license_url_link').style.display = "";
				break;
			}
		}
	}
}
//]]>