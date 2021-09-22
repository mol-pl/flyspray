/*
	Simple Frame Postman
	
	version 1.1.0
	
	Usage:
	var oPostman = new nuxPostman({
		strDestFrameId : 'iframe_id',			// id of a frame (or iframe) element that will receive messages
		strDestFrameDomain : "nux:8081",		// domain of the frame e.g. 'www.example.com', 'www.example.com:8080'...
		
		strMsgSourceDomain : "localhost:8081"	// domain of the sender (the window that is sending the message) formated as above
		or
		// domain regexp match (only for advanced users!)
		// use :// match at the begining of your regexp and $ at the end to avoid to broad matches
		reMsgSourceBaseUrls : /:\/\/(localhost(:[0-9]+)?|nux(:[0-9]+)?|nuxlap7(:[0-9]+)?)$/
	});
*/
function nuxPostman(oParams)
{
	this.strDestFrameId = oParams.strDestFrameId;
	this.strDestFrameBaseUrl = "http://"+oParams.strDestFrameDomain;
	if (typeof(oParams.strMsgSourceDomain)!='undefined')
	{
		this.strMsgSourceBaseUrl = "http://"+oParams.strMsgSourceDomain;
	}
	if (typeof(oParams.reMsgSourceBaseUrls)!='undefined')
	{
		this.reMsgSourceBaseUrls = oParams.reMsgSourceBaseUrls;
	}
	
	function hasConsole()
	{
		return (typeof(window.console)=="object" && typeof(window.console.log)=="function");
	}
	
	/*
		Send given message (strMsg) - should be run outside the frame
	*/
	this.send = function(strMsg)
	{
		var httpsUrl = this.strDestFrameBaseUrl.replace(/^http:/, 'https:');
		// debug
		if (hasConsole())
		{
			console.log ('[Postman] send to:'+this.strDestFrameBaseUrl);
			console.log ('[Postman] send to(2):'+httpsUrl);
		}
		
		var winFrame = document.getElementById(this.strDestFrameId).contentWindow;
		winFrame.postMessage(
			strMsg,
			this.strDestFrameBaseUrl
		);
		// post to https too...
		winFrame.postMessage(
			strMsg,
			httpsUrl
		);
	}

	/*
		Inits reciever - should be run inside the frame
		
		funMsgReceiver is a function that will be called upon receiving the message
	*/
	this.initReceiver = function(funMsgReceiver)
	{
		var _this = this;
		window.addEventListener("message", function(e)
		{
			// debug
			if (hasConsole())
			{
				console.log ('[Postman] received from:'+e.origin);
				//debugger;
			}
			
			if (typeof(_this.reMsgSourceBaseUrls)!='undefined')
			{
				if (e.origin.search(_this.reMsgSourceBaseUrls)<0)
				{
					if (hasConsole())
					{
						console.warn('[Postman] Origin not matched', _this.reMsgSourceBaseUrls);
					}
					return;
				}
			}
			else if (e.origin !== _this.strMsgSourceBaseUrl)
			{
				if (hasConsole())
				{
					console.warn('[Postman] Origin not matched', _this.strMsgSourceBaseUrl);
				}
				return;
			}
			funMsgReceiver(e.data);
		}, false);
	}
}


/*
	
*
framemsg.initSender = function ()
{
	document.getElementById("form").onsubmit = function(e)
	{
		oPostman.send(document.getElementById("msg").value);
		e.preventDefault();
	};
}

/*
	
*
framemsg.initReceiver = function ()
{
	oPostman.initReceiver(function(strMsg)
	{
		document.getElementById("test").textContent = "parent said: " + strMsg;
	});
}
/**/