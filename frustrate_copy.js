//<![CDATA[ 
// FUNCTIONS

//var inputTags = new Array("input", "textarea", "select", "option", "optgroup", "button");
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

function dprv_no_right_click_message()
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
	}
}

function dprv_disableRightClick()
{
	function clickCheck(e)
	{
		if (e.button && e.button == 2 || e.which && e.which == 3)	// Was it a right-click? 
		{
			dprv_no_right_click_message();
			return false;
		}
		return true;
	}
	document.onmousedown=clickCheck;		// Works in FF, Chrome, IE, but in Safari, Opera still shows context menu
	document.oncontextmenu=new Function("dprv_no_right_click_message();return false");	// fallback, works in modern versions of Safari, Chrome, Opera, IE, and FF
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
			{return false;}
			else
			{return true;}
		}
	}
	else
	{
		if (typeof target.ondrag != "undefined")
		{
			target.ondrag=new Function("return false");		// Doesn't stop the dragging in Chrome or Safari
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
	//alert("disableCtrlKeys starts");
	// onkeypress is not triggered in safari with a ctrl/key combination
	// onkeypress is not triggered in IE or Chrome with a function-linked ctrl/key combination (e.g ctrl/a, ctrl/c)
	// onkeypress in IE gives different keyCode values (seems to be low numbers for ctrl/key and +32 for others) than onkeydown
	// Set code to suppress ctrl/a and ctrl/u
	//alert(navigator.userAgent);
	function trapCtrlKeyCombination(ev)
	{
			var key;
			var isCtrl;
			//ev=ev||event;
			// TODO - change this test to checking whether undefined or not to avoid javascript warning message
			if(window.event)	// This is true in IE and Safari  - Note ev.which also exists in Safari, Opera and Chrome
			{
				key = window.event.keyCode;
				if (key == 17)    // this bit can be removed after testing
				{
					return true;
				}
				if(window.event.ctrlKey)
						isCtrl = true;
				else
						isCtrl = false;

			}
			else
			{
				key = ev.which;     //firefox
				if(ev.ctrlKey)
						isCtrl = true;
				else
						isCtrl = false;
			}

			if(isCtrl)
			{
					if (String.fromCharCode(key).toLowerCase() == 'a' || String.fromCharCode(key).toLowerCase() == 'u')
					{
						void(0);  // CANCEL LAST EVENT
						return false;
					}
			}
			return true;
	}

	if ((navigator.userAgent.indexOf('Safari') != -1) || navigator.userAgent.indexOf('MSIE') != -1)
	{
		document.onkeydown=trapCtrlKeyCombination;	// IE or Safari or Chrome
	}
	else
	{
		document.onkeypress=trapCtrlKeyCombination;
	}
}


//]]>