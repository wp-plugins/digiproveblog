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

function clickIE()
{
	if (document.all)
	{
		displayMessage();
		return false;
	}
	return true;
}

function clickNS(e)
{
	
	if	(document.layers||(document.getElementById&&!document.all))
	{
		if (e.which==2||e.which==3) 

		{
			displayMessage();
			return false;
		}
	}
	return true;
}

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
var justDisplayed = 0;

if (document.layers)
{
	document.captureEvents(Event.MOUSEDOWN);
	document.onmousedown=clickNS;
}
else
{
	document.onmouseup=clickNS;
}


function disableText(e){return false;}
function reEnable(){return true;}
//For browser IE4+
document.onselectstart = new Function ('return false;')
//For browser NS6
if (window.sidebar){document.onmousedown = disableText;document.onclick = reEnable;}


function disableSelection()
{
	//alert("disableSelection starts");
	var target = document.body;
	var noRightClickMessage = "";
	if (typeof target.onselectstart!='undefined') //For IE, Chrome, or Safari
	{
		//alert("IE, Chrome, or Safari");
		target.onselectstart=function(){return false}
	}
	else
	{
		if (typeof target.style.MozUserSelect!='undefined')
		{
			//For Firefox
			target.style.MozUserSelect='none';
		}
		else //All other route (For Opera)
		{
			//alert("some other browser!");
			target.onmousedown=function(){return false}
		}
	}
	disableCtrlKeys();
}

window.onload = disableSelection;

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
        if(window.event)	// This is true in IE and Safari  - Note ev.which also exists in Safari, Opera and Chrome
        {
			key = window.event.keyCode;
			if (key == 17)    // this bit can be removed after testing
			{
				return true;
			}
			//alert("IE, Safari or Opera key=" + key + ", ctrlKey = " + window.event.ctrlKey);
			//alert("ev.which = " + ev.which);
			if(window.event.ctrlKey)
					isCtrl = true;
			else
					isCtrl = false;

		}
        else
        {
			key = ev.which;     //firefox
			//alert("FF key=" + key);
			if(ev.ctrlKey)
					isCtrl = true;
			else
					isCtrl = false;
        }

        if(isCtrl)
        {
				//alert ("you pressed ctrl / " + String.fromCharCode(key).toLowerCase());
				if (String.fromCharCode(key).toLowerCase() == 'a' || String.fromCharCode(key).toLowerCase() == 'u')
				{
					//alert("returning false");
					void(0);  // CANCEL LAST EVENT
					return false;
				}
		}
        return true;
}
function htmlspecialchars_decode(encodedString)
{
	var decodedString = encodedString.replace('&amp;', '&');
	return decodedString.replace('&quot;', '"').replace('&#039;', '\'').replace('&lt;', '<').replace('&gt;','>');
}