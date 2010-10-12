function disableSelection(target)
{
	//var noRightClickMessage = "";
	if (typeof target.onselectstart!='undefined') //For IE, Chrome, or Safari
	{
		target.onselectstart=function(){return false}
	}
	else
	{
		if (typeof target.style.MozUserSelect!='undefined')    // Firefox
		{
			target.style.MozUserSelect='none';
		}
		else //All other route (For Opera)
		{
			target.onmousedown=function(){return false}
		}
	}
	disableCtrlKeys();
}


function disableText(){return false;}
function reEnable(){return true;}


function disableCtrlKeys()
{
	// onkeypress is not triggered in Safari with a ctrl/key combination
	// onkeypress is not triggered in IE or Chrome with a function-linked ctrl/key combination (e.g ctrl/a, ctrl/c)
	// onkeypress in IE gives different keyCode values (seems to be low numbers for ctrl/key and +32 for others) than onkeydown
	// Set code to suppress ctrl/a and ctrl/u
	if ((navigator.userAgent.indexOf('Safari') != -1) || navigator.userAgent.indexOf('MSIE') != -1)
	{
		document.onkeydown=trapCtrlKeyCombination;	// IE or Safari or Chrome
	}
	else
	{
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

function clickNS(e)
{
	
	if	(document.layers||(document.getElementById&&!document.all))
	{
		if (e.which==2||e.which==3)    // Was it a right-click? 
		{
			displayMessage();
			return false;
		}
	}
	return true;
}


//alert("body class = " + document.body.className);
var target = document.body;

if (typeof(document.body.className)!="undefined")
{
	var className = document.body.className;

	if (className != null && className != "" && className.indexOf('single') == 0)
	{
		var start_post_id = className.indexOf('postid-');
		if (start_post_id != -1)
		{
			var end_post_id = className.substr(start_post_id + 7).indexOf(' ');
			//alert(start_post_id + ", " + end_post_id);
			if (end_post_id!= -1 && end_post_id!= 0)
			{
				var post_id = className.substr(start_post_id + 7, end_post_id);
				//alert ("required id = " + post_id);
				if (document.getElementById("post-" + post_id))
				{

					target = document.getElementById("post-" + post_id);
					if (target == null)
					{
						//alert("target is null");
						terget = document.body;
					}
				}
			}
		}
	}
}

//alert ("will now protect " + target.nodeName + " id " + target.id + " class " + target.className);

// Prevent Right Click
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

//For browser NS6
if (window.sidebar){target.onmousedown = disableText;target.onclick = reEnable;}

// Prevent Selection and CTRL keys
disableSelection(target);