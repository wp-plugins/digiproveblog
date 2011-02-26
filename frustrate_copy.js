//<![CDATA[ 
// FUNCTIONS

function disableSelection(target)
{
/*		// MozUserSelect has a bug - Bug 561691 : sub-elements cannot be excepted from the rule
		if (typeof target.style.MozUserSelect!='undefined')    // Firefox
		{
			target.style.MozUserSelect='none';
			enableInputElements(target);
		}
*/

	if (typeof target.style.WebkitUserSelect!='undefined')    // Safari or Chrome
	{
		target.style.WebkitUserSelect='none';
		enableInputElements(target);
	}
	else
	{
		if (typeof target.onselectstart!='undefined') //For IE, Chrome or Safari (but Chrome or Safari already picked up above)
		{
			target.onselectstart=function()
			{
				if (event.srcElement.type != "text" && event.srcElement.type != "textarea" && event.srcElement.type != "password")
				{return false;}
				else
				{return true;}
			}
		}
		else
		{
			if (window.sidebar)	// Firefox IE and Chrome and Safari (and Netscape?): IE, Chrome and Safari already picked up above
			{
				target.onmousedown=trapMouseDown;
			}
			else //All other route (For Opera)
			{
				target.onmousedown=trapMouseDown;
			}
		}
	}
}

function enableInputElements(target)
{
	var inputElements = target.getElementsByTagName("input");
	for (var i=0; i<inputElements.length; i++)
	{
		enableSelection(inputElements[i]);
	}
	inputElements = target.getElementsByTagName("textarea");
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

function trapMouseDown(e)
{
	var element
	if (!e) var e = window.event
	if (e.target) element = e.target
	else if (e.srcElement) element = e.srcElement
	if (element.nodeType == 3) // defeat Safari bug
	element = element.parentNode
	var tagname=element.tagName.toUpperCase();
	if (tagname == "INPUT" || tagname == "TEXTAREA")
	{
		return true;
	}
	else
	{
		return false;
	}
}



// FUNCTIONS TO PREVENT RIGHT-CLICK:

function disableRightClick()
{
	if (document.layers)
	{
		document.captureEvents(Event.MOUSEDOWN);
		document.onmousedown=clickNS;
	}
	else
	{
		document.onmouseup=clickNS;
	}
	document.oncontextmenu=new Function("displayMessage();return false")
}

function clickNS(e)
{
	if	(document.layers||(document.getElementById&&!document.all))
	{
		if (e.which==2||e.which==3)	// Was it a right-click? 
		{
			displayMessage();
			return false;
		}
	}
	return true;
}

function displayMessage()
{
	if (justDisplayed == 0)
	{
		if (noRightClickMessage != "")
		{
			alert(htmlspecialchars_decode(noRightClickMessage));
			justDisplayed = 1;
			setTimeout("justDisplayed = 0;", 50);
		}
	}
}

function htmlspecialchars_decode(encodedString)
{
	var decodedString = encodedString.replace('&amp;', '&');
	return decodedString.replace('&quot;', '"').replace('&#039;', '\'').replace('&lt;', '<').replace('&gt;','>');
}



// FUNCTIONS TO DISABLE CERTAIN CTRL KEY COMBINATIONS:
function disableCtrlKeys()
{
	//alert("disableCtrlKeys starts");
	// onkeypress is not triggered in safari with a ctrl/key combination
	// onkeypress is not triggered in IE or Chrome with a function-linked ctrl/key combination (e.g ctrl/a, ctrl/c)
	// onkeypress in IE gives different keyCode values (seems to be low numbers for ctrl/key and +32 for others) than onkeydown
	// Set code to suppress ctrl/a and ctrl/u
	//alert(navigator.userAgent);
	if ((navigator.userAgent.indexOf('Safari') != -1) || navigator.userAgent.indexOf('MSIE') != -1)
	{
		document.onkeydown=trapCtrlKeyCombination;	// IE or Safari or Chrome
	}
	else
	{
	//alert("onkeypress");
		document.onkeypress=trapCtrlKeyCombination;
	}
}

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

//]]>