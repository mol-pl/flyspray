/*
	Form xero
	
	Only input elements with strCopyClassName will be copied.
	
	Note! Source frame must be in the same domain as the main frame.
	This is due to security issues and for more convient parsing.

	Libraries:
	* nuxPostman must be available for both the parent window and the destination frame.
	* JSON & varia is needed for the paren window
	* this script is needed for both the parent window and the destination frame.
	
	TODO:
	* Copy checkbox and radio by labels instead of underlying values
*/
/*
	Setup
*/
var frmcp = {
	strSrcFrameId : 'source_frame',
	strDestFrameId : 'dest_frame',
	strCopyClassName : 'frmcopyme',
	strCopyErrorClassName : 'frmcopyme_error',
	strCopyDoneClassName : 'frmcopyme_ok',
	/*
	strDestFrameDomain : "nux",
	strDestFrameDomain : "nuxlap7",
	strDestFrameDomain : "localhost",

	strMsgSourceDomain : "nux:8081"
	strMsgSourceDomain : "localhost"
	reMsgSourceBaseUrls : /:\/\/(localhost(:[0-9]+)?|nux(:[0-9]+)?|nuxlap7(:[0-9]+)?)$/
	*/
	strDestFrameDomain : "nux",
	reMsgSourceBaseUrls : /:\/\/(localhost(:[0-9]+)?|nux(:[0-9]+)?|nuxlap7(:[0-9]+)?)$/
}

//
// init frame postman (if needed)
//
if (typeof(nuxPostman)!='undefined')
{
	var oPostman = new nuxPostman({
		strDestFrameId : frmcp.strDestFrameId,			// id of a frame (or iframe) element that will receive messages
		strDestFrameDomain : frmcp.strDestFrameDomain,		// domain of the frame e.g. 'www.example.com', 'www.example.com:8080'...
		strMsgSourceDomain : frmcp.strMsgSourceDomain,		// domain of the sender (the window that is sending the message) formated as above
		reMsgSourceBaseUrls : frmcp.reMsgSourceBaseUrls		// regexp sender match
	});
}

/*
	Show/hide frames
	
	oStatus [optional] = {
		el : element on which text/value should be changed
		shown : 'text when frame is shown'
		hidden : 'text when frame is hidden'
	}
*/
frmcp.showHideFrame = function (strFrameId, oStatus)
{
	var el = document.getElementById(strFrameId);
	el.style.display = (el.style.display!='none') ? 'none' : 'block';
	if (typeof(oStatus!=undefined))
	{
		var txt  = (el.style.display=='none') ? oStatus.hidden : oStatus.shown;
		if (oStatus.el.nodeName.toLowerCase()=='input')
		{
			oStatus.el.value = txt;
		}
		else
		{
			oStatus.el.innerHTML = txt;
		}
	}
	
	// pokazuje pojedyncze?
	var els = new Array();
	els.push(document.getElementById(this.strSrcFrameId));
	els.push(document.getElementById(this.strDestFrameId));
	var numShown = 0;
	for (var i=0; i<els.length; i++)
	{
		if (els[i].style.display == 'none')
		{
			numShown++;
		}
	}
	el.parentNode.className = 'num_shown_frames_'+numShown;
}

/*
	Close copy frames (and open source frame)
*/
frmcp.close = function ()
{
	location.href = document.getElementById(this.strSrcFrameId).contentWindow.location.href
}


/*
	Run copy between two frames with given ids
*/
frmcp.run = function ()
{
	var arrData = this.getSourceData()
	var data = JSON.stringify(arrData)//DEBUG: .replace("[{", "[\n{").replace("}]", "}\n]").replace(/\},\{/g, "},\n{");
	oPostman.send(data);
}

/*
	Getting form values
*/
frmcp.getSourceData = function ()
{
	//
	// gather data
	var _this = this;
	var arrData = new Array();
	/*
	// cross browser
	var winFrame = document.getElementById(this.strSrcFrameId).contentWindow;
	winFrame.jQuery('.'+this.strCopyClassName).each(function (index)
	{
		arrData.push(
		{
			name : this.name,
			data : _this.getInputData(this)
		});
	});
	*/
	// skip shi...
	if (typeof(document.getElementsByClassName)=='undefined')
	{
		alert('Your browser is lame - get another one');
		return [];
	}
	var docFrame = getFrameDocObj(this.strSrcFrameId);
	var els = docFrame.getElementsByClassName(this.strCopyClassName);
	for (var i=0; i<els.length; i++)
	{
		arrData.push(
		{
			name : els[i].name,
			data : _this.getInputData(els[i])
		});
	}
	
	//
	// integrate duplicates
	// 1st sort by names
	arrData.sort(function(a, b)
	{
		if (a.name < b.name)
			return -1
		;
		if (a.name > b.name)
			return 1
		;
		return 0;
	});
	// integrate
	var oPrev = {name:null, index:-1};
	for (var i=0; i<arrData.length; )
	{
		if (oPrev.name == arrData[i].name)
		{
			if (!isArray(arrData[oPrev.index].data))
			{
				var tmp = arrData[oPrev.index].data;
				arrData[oPrev.index].data = new Array();
				arrData[oPrev.index].data.push(tmp);
			}
			arrData[oPrev.index].data.push(arrData[i].data);
			arrData.splice(i,1);
		}
		else
		{
			oPrev = {
				name : arrData[i].name,
				index : i
			};
			i++;
		}
	}

	return arrData;
}


/*
	Gets data for the given input element
	
	Note! elInput must be an element not something returned from `form[name-of-input]`
*/
frmcp.getInputData = function(elInput)
{
	switch(elInput.nodeName.toLowerCase())
	{
		// various input
		case 'input':
			switch (elInput.getAttribute('type').toLowerCase())
			{
				case 'hidden':
				case 'text':
				default:
					return elInput.value;
				break;
				case 'checkbox':
				case 'radio':
					return {
						value : elInput.value,
						checked : elInput.checked
						//TODO: index : document.getElementsByName(elInput.name)...
						//TODO: text : elInput.label.textContent
					};
				break;
			}
		break;
		// textarea and other
		default:
		case 'textarea':
			return elInput.value;
		break;
		// select - currently assuming that only one (!) can be selected 
		case 'select':
			return {
				value : elInput.value,
				index : elInput.options.selectedIndex,
				text : elInput.options[elInput.options.selectedIndex].textContent
			};
		break;
	}
	return false;
}

/*
	Data receiver preparation to be run onload of the destination frame/form
*/
frmcp.initReceiver = function ()
{
	var _this = this;
	oPostman.initReceiver(function(strMsg)
	{
		// DEBUG
		//document.getElementById("test").textContent = "parent said: " + strMsg;
		//
		_this.setFormData(JSON.parse(strMsg));
	});
}
addOnloadHook(function(){frmcp.initReceiver()});

/*
	Set form values in the target frame
*/
frmcp.setFormData = function (arrData)
{
	//debugger;
	//console.log(arrData);
	for (var i=0; i<arrData.length; i++)
	{
		var oData = arrData[i];
		var els = document.getElementsByName(oData.name);
		// if not found - try by id (needed for reportedver in FS)
		if (els.length<=0)
		{
			var el = document.getElementById(oData.name);
			els = new Array();
			els.push(el);
		}
		//debugger;
		for (var j=0; j<els.length; j++)
		{
			this.setInputFromData(els[j], oData.data);
		}
	}
}

/*
	Gets data for the given input element
	
	Note! elInput must be an element not something returned from `form[name-of-input]`
*/
frmcp.setInputFromData = function(elInput, data)
{
	var wasCopied = false;
	switch(elInput.nodeName.toLowerCase())
	{
		// various input
		case 'input':
			switch (elInput.getAttribute('type').toLowerCase())
			{
				case 'hidden':
				case 'text':
				default:
					elInput.value = data;
					wasCopied = true;
				break;
				case 'checkbox':
				case 'radio':
					var wasChanged = true;
					if (!isArray(data))
					{
						var tmp = data;
						data = new Array();
						data.push(tmp);
					}
					for (var i=0; i<data.length; i++)
					{
						if (data[i].value == elInput.value)
						{
							elInput.checked = data[i].checked;
						wasCopied = true;
						}
					}
				break;
			}
		break;
		// textarea and other
		default:
		case 'textarea':
			elInput.value = data;
			wasCopied = true;
		break;
		// select - currently assuming that only one (!) can be selected 
		case 'select':
			for (var i=0; i<elInput.options.length; i++)
			{
				var elOpt = elInput.options[i];
				if (elOpt.textContent == data.text)
				{
					//elInput.selected = true;
					elOpt.selected = true;
					wasCopied = true;
				}
			}
		break;
	}
	// add class
	//debugger;
	var reRemover = new RegExp('(^| +)('+this.strCopyDoneClassName+'|'+this.strCopyErrorClassName+')($| +)');
	elInput.className = elInput.className.replace (reRemover, ' ');
	if (wasCopied)
	{
		elInput.className += ' '+this.strCopyDoneClassName;
	}
	else
	{
		elInput.className += ' '+this.strCopyErrorClassName;
	}
	//
	return wasCopied;
}

/*
	parent said: [
		{"name":"checkbox_name","data":
			{
				"value":"chk",
				"checked":true
			}
		},
		{"name":"checkbox_name2","data":
			[
				{"value":"chk1","checked":false},
				{"value":"chk2","checked":false}
			]
		},
		{"name":"hidden_name","data":"hid"},
		{"name":"radio_name","data":
			[
				{"value":"rad1","checked":false},
				{"value":"rad2","checked":false}
			]
		},
		{"name":"select_name","data":
			{
				"value":"opt2",
				"index":1,
				"text":"opt2"
			}
		},
		{"name":"select_name2","data":
			{
				"value":"def",
				"index":3,
				"text":"opt2"
			}
		},
		{"name":"text_name","data":"txt"},
		{"name":"textarea_name","data":"txtar"}
	]
*/

/*
	isArray checker
*/
if (typeof isArray != 'function')
{
	function isArray(obj)
	{
		return Object.prototype.toString.call(obj) == '[object Array]';
	}
}
