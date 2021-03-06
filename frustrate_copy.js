//<![CDATA[ 
// FUNCTIONS

var inputTags = new Array("input", "textarea", "select", "option", "optgroup", "button", "canvas");
function dprv_disableSelection(target)
{
	// At present, this function only used in Safari or Chrome:
	function enableInputElements(target)
	{
		for (var t=0; i<inputTags.length; t++)
		{
			var inputElements = target.getElementsByTagName(inputTags[t]);
			for (var i=0; i<inputElements.length; i++)
			{
				enableSelection(inputElements[i]);
			}
		}
		function enableSelection(target)
		{
			if (typeof target.style.WebkitUserSelect!='undefined')    // Safari
			{
				target.style.WebkitUserSelect='text';
			}
		}
	}

	// this function is used for Firefix and Opera
	function trapMouseDown(e)
	{
		var element
		if (!e) var e = window.event
		if (e.target) element = e.target
		else if (e.srcElement) element = e.srcElement
		if (element.nodeType == 3) // defeat Safari bug
		element = element.parentNode
		var tagname=element.tagName.toLowerCase();
		if (inputTags.indexOf(tagname) != -1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

/*	// MozUserSelect has a bug - Bug 561691 : sub-elements cannot be excepted from the rule
	if (typeof target.style.MozUserSelect!='undefined')    // Firefox
	{
		target.style.MozUserSelect='none';
		enableInputElements(target);
	}
*/
	if (typeof target.style.WebkitUserSelect != 'undefined')    // Safari or Chrome
	{
		target.style.WebkitUserSelect='none';
		enableInputElements(target);
	}
	else
	{
		if (typeof target.onselectstart != 'undefined')		// IE  (and Chrome or Safari but they are already picked up above)
		{
			target.onselectstart=function()
			{
				if (inputTags.indexOf(event.srcElement.tagName.ToLowerCase()) == -1)
				{return false;}
				else
				{return true;}
			}
		}
		else
		{
			target.onmousedown=trapMouseDown;	// Firefox, Opera and Netscape (and others but they all picked up elsewhere)
		}
	}
}



// FUNCTIONS TO PREVENT RIGHT-CLICK:

function dprv_manage_right_click(e)
{
	function htmlspecialchars_decode(encodedString)
	{
		var decodedString = encodedString.replace('&amp;', '&');
		return decodedString.replace('&quot;', '"').replace('&#039;', '\'').replace('&lt;', '<').replace('&gt;','>');
	}
	if (dprv_justDisplayed == 0)
	{
		if (dprv_noRightClickMessage != "")
		{
			alert(htmlspecialchars_decode(dprv_noRightClickMessage));
			dprv_justDisplayed = 1;
			setTimeout("dprv_justDisplayed = 0;", 50);
		}
		if (dprv_record_IP != "off")
		{
			var message = "A user right-clicked";
			if (typeof e != "undefined" && typeof e.target != "undefined")
			{
				if (typeof e.target.id != "undefined" && e.target.id != null && e.target.id != "")
				{
					message += " on " + e.target.tagName + "/" + e.target.id;
				}
				if (typeof e.target.src != "undefined" && e.target.src != null && e.target.src != "")
				{
					var url = e.target.src.replace(dprv_site_url, "").replace("http://","").replace("https://","");
					if (url.substr(0,1) == "/")
					{
						url = url.substr(1);
					}
					message += ", src=" + url;
				}
			}
			dprv_error_log("Low", message);
		}
	}
}


function dprv_disableRightClick()
{
	function clickCheck(e)
	{
		if (e.button && e.button == 2 || e.which && e.which == 3)	// Was it a right-click? 
		{
			dprv_manage_right_click(e);
			return false;
		}
		return true;
	}
	if (typeof document.oncontextmenu != "undefined")
    {
        // modern browsers
        document.oncontextmenu = new Function("dprv_manage_right_click();return false");	// works in modern versions of Safari, Chrome, Opera, IE, and FF
    }
    else
    {
        // legacy browsers
        document.onmousedown = clickCheck;		// Works in FF, Chrome, IE, but in Safari, Opera still shows context menu
    }

}

function dprv_disableDrag(target)
{
	// This doesn't work as it looks like Safari/Chrome default value for this property at element level is auto (would have expected inherit)
	//if (typeof target.style.WebkitUserDrag != 'undefined')    // Safari or Chrome
	//{
	//	target.style.WebkitUserDrag='none';
	//	return;
	//}

	if (typeof target.ondragstart != 'undefined') //	Seems to exist ok for up-to-date versions of IE, Opera, FF, Safari, Chrome
	{
		target.ondragstart=function()
		{
			if (inputTags.indexOf(event.srcElement.tagName.toLowerCase()) == -1)
			{
				if (dprv_record_IP != "off")
				{
					dprv_error_log("Low", "User tried to drag");
				}
				return false;
			}
			else
			{return true;}
		}
	}
	else
	{
		if (typeof target.ondrag != "undefined")
		{
			target.ondrag=new Function("if(dprv_record_IP!='off'){dprv_error_log('Low','User tried to drag');}return false;");		// Doesn't stop the dragging in Chrome or Safari
		}
		else
		{
			// dragging will probably be stopped by the disable selection code
		}
	}
}

// FUNCTIONS TO DISABLE CERTAIN CTRL KEY COMBINATIONS:
function dprv_disableCtrlKeys()
{
    // Set code to suppress ctrl/a and ctrl/u
    function trapCtrlKeyCombination(ev)
    {
        var key;
        if (typeof window.event != "undefined")	 // This is true in IE and Safari  - Note ev.which also exists in Safari, Opera and Chrome
        {
            ev = window.event;
            key = ev.keyCode;
        }
        else
        {
            key = ev.which;			// Firefox
        }
        if (key == 16 || key == 17 || key == 18)    // pressing shift, ctrl, or alt without (or before) pressing another key	
        {
            return true;
        }
        if (
				(navigator.userAgent.indexOf('Macintosh') != -1 && ev.metaKey && ev.altKey && String.fromCharCode(key).toLowerCase() == 'u')	// Show source code on Mac/Safari
			|| (navigator.userAgent.indexOf('Macintosh') == -1 && ev.ctrlKey && !ev.altKey && String.fromCharCode(key).toLowerCase() == 'u')	// Show source code on Windows
			|| (navigator.userAgent.indexOf('Firefox') != -1 && ev.altKey && ev.shiftKey)							// Show source code on Firefox
			|| (navigator.userAgent.indexOf('Macintosh') != -1 && ev.metaKey && !ev.altKey && String.fromCharCode(key).toLowerCase() == 'a')	// Select all on Mac
			|| (navigator.userAgent.indexOf('Macintosh') == -1 && ev.ctrlKey && !ev.altKey && String.fromCharCode(key).toLowerCase() == 'a')	// Select all on Windows
			)
        {
            void (0);  // CANCEL LAST EVENT
            if (dprv_record_IP != "off")
            {
                dprv_error_log("Low", "Forbidden CTRL Key combination");
            }
            return false;
        }
        return true;
    }
    if (typeof document.onkeypress == 'undefined' || navigator.userAgent.indexOf('Safari') != -1 || navigator.userAgent.indexOf('MSIE') != -1 || navigator.userAgent.indexOf('Trident') != -1)
    {
        document.onkeydown = trapCtrlKeyCombination;	// IE or Safari or Chrome or Opera
    }
    else
    {
        document.onkeypress = trapCtrlKeyCombination;	// Others (just Firefox, only one that fires onkeypress with CTRL key combinations)
    }
}


function dprv_error_log(severity, message)
{
	if (severity == null)
	{
		severity = "Low";
	}
	var url = document.URL.replace(dprv_site_url, "").replace("http://","").replace("https://","");
	if (url.substr(0,1) == "/")
	{
		url = url.substr(1);
	}
	url = encodeURIComponent(url);
	jQuery(document).ready(function($) 
	{
		// This does the ajax request
		$.ajax({
			url: dprv_ajax_url,
			data:
			{
				'action':'dprv_log_event',
				'severity':severity,
				'message' : message,
				'url':url
			},
			success:function(data)
			{
				// This outputs the result of the ajax request
				//alert(data);	(not interested in response, hopefully logged ok
			},
			error: function(errorThrown){}
		});  
				  
	});
}
//]]>