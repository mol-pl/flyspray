/* ------------------------------------------------------------------------ *\
    Klasa wielokrotnego użytku do "łagodnych" komunikatów Javascriptowych
	
    Copyright:  ©2008-2009 Maciej Jaros (pl:User:Nux, en:User:Nux)
     Licencja:  GNU General Public License v2
                http://opensource.org/licenses/gpl-license.php

	Note:
		For best results in IE add this to your CSS:
		html {height:100%}
\* ------------------------------------------------------------------------ */
//  wersja:
	var tmp_VERSION = '0.1.2';  // = sftJSmsg.version = sftJSmsg.ver
// ------------------------------------------------------------------------ //

//
// Object
//
function sftJSmsg()
{
	//
	// Constructor
	this.ver = this.version = tmp_VERSION;
	
	this.msgEls = new Array();

	// settings
	this.showCancel = false;	// show cancel button
	this.noButtons = false;		// no buttons - NOTE: you'll have to close message for yourself if you use it
	this.createRegularForm = false;	// instead of a simple popup use popup to submit a form created in it
	this.RegularForm = {	// settings for a form (if createRegularForm==ture)
		'method' : 'POST',			// default method
		'action' : location.href	// default actions
	};
	this.autoOKClose = true;	// add close action for OK button
	
	this.styleZbase = 50000;	// base z-index for msg (note that there can be more z-indexes used)
	this.styleTop = 100;
	this.styleWidth = 330;
	this.pozFromTop = 40;
	
	// lang
	this.lang = {
		'OK' : 'OK',
		'Cancel' : 'Anuluj'
	};
	
	//
	// .init()
	//
	this.init = function()
	{
		var glob = document.body;
		
		var nel;
		
		//
		// document shade
		nel = document.createElement('div');
		nel.style.cssText = 'background:white;filter:alpha(opacity=75);opacity:0.75;position:absolute;left:0px;top:0px;';
		nel.style.width = document.documentElement.scrollWidth+'px';
		nel.style.height= document.documentElement.scrollHeight+'px';
		nel.style.display = 'none';
		glob.appendChild(nel);
		this.msgEls[this.msgEls.length] = nel;

		//
		// main message element
		nel = document.createElement('div');
		nel.style.cssText = 'text-align:center;background:white;padding:5px 10px;border:1px solid black;position:absolute;';
		// przy ustawionym min-height
		// if (nel.style.maxHeight==undefined)	nel.style.height='300px'; // IE blah...
		nel.style.display = 'none';
		glob.appendChild(nel);
		var elAppender = this.elMain = this.msgEls[this.msgEls.length] = nel;

		//
		// form element
		if (this.createRegularForm)
		{
			nel = document.createElement('form');
			/*
			nel.setAttribute('action', this.RegularForm.action);
			nel.setAttribute('method', this.RegularForm.method);
			*/
			for (var key in this.RegularForm)
			{
				nel.setAttribute(key, this.RegularForm[key]);
			}
			elAppender.appendChild(nel);
			elAppender = nel;	// podmiana, żeby zawartość i przyciski były w elemencie FORM
		}

		//
		// message content
		nel = document.createElement('div');
		nel.style.margin = '1em .5em';
		elAppender.appendChild(nel);
		this.elContent = nel;

		if (!this.noButtons)
		{
			//
			// message buttons elements
			nel = document.createElement('div');
			nel.style.marginBottom = '1em';
			elAppender.appendChild(nel);
			this.msgBtns = new Object();
			this.msgBtns.parent = nel;
			this.msgBtns.parent.sftJSmsg = this;
			
			// OK (always)
			nel = document.createElement('input');
			nel.setAttribute('type', (!this.createRegularForm) ? 'button' : 'submit');
			nel.setAttribute('name', 'submit');
			nel.setAttribute('value', this.lang['OK']);
			nel.style.padding = '0 1em';
			this.msgBtns.parent.appendChild(nel);
			this.msgBtns.ok = nel;
			// Cancel (if asked for by the user)
			if (this.showCancel)
			{
				nel = document.createElement('input');
				nel.setAttribute('type', 'button');
				nel.setAttribute('value', this.lang['Cancel']);
				nel.onclick = this.close;
				nel.style.marginLeft = '1em';
				this.msgBtns.parent.appendChild(nel);
				this.msgBtns.cancel = nel;
			}
		}
		
		// setup user changable styles
		this.reInit();
		
		// enable resize
		/*
		smpAddEvent(window, 'resize', function() {
			this.reInit()
		});
		*/
		window.sftJSmsgs[window.sftJSmsgs.length] = this;
	}

	//
	// .reInit()
	//
	// reInit so that changed styles will work
	this.reInit = function()
	{
		//
		// ew. korekta wielkości cienia z tyłu
		var shade_el = this.msgEls[0];
		shade_el.style.width = document.documentElement.scrollWidth+'px';
		shade_el.style.height= document.documentElement.scrollHeight+'px';

		//
		// z-index
		for (var i=0; i<this.msgEls.length; i++)
		{
			this.msgEls[i].style.zIndex = this.styleZbase+i;
		}
		
		//
		// top
		if (this.styleTop==undefined)	// auto-top
		{
			var cur_scroll = qmGetPageScroll();
			this.styleTop = cur_scroll[1]+this.pozFromTop;
		}
		this.elMain.style.top = this.styleTop+'px';

		//
		// width + left
		var left=undefined;
		if (this.styleWidth==undefined)	// auto-width
		{
			this.elMain.style.width = '';
			if (this.styleLeft==undefined)
			{
				left = 100;	// if both undefined then left cannot be computed
			}
		}
		else
		{
			this.elMain.style.width = this.styleWidth+'px';
		}
		// final left setup
		var glob = document.body;
		if (left==undefined && this.styleLeft==undefined)	// if not yet set
		{
			left = Math.floor(glob.clientWidth/2 - this.styleWidth/2);
		}
		else
		{
			left = this.styleLeft;
		}
		this.elMain.style.left	= ((left<10) ? 10 : left)+'px';	// including padding
	}

	//
	// .show(html, strOKclick)
	//
	this.show = function(html, strOKclick)
	{
		// init / reInit
		if (this.msgEls.length==0)
		{
			this.init();
		}
		else
		{
			this.reInit();
		}
		
		//
		// wiadomosc
		if (!this.prevHTML || html!=this.prevHTML)
		{
			this.prevHTML = html;
			this.elContent.innerHTML = html;
		}
		
		if (!this.noButtons)
		{
			//
			// akcja
			if (typeof strOKclick =='string' && strOKclick.length>0)
			{
				if (this.autoOKClose)
				{
					this.msgBtns.ok.akcja = this.close;
					this.msgBtns.ok.onclick = new Function(strOKclick +'; this.akcja()');
				}
				else
				{
					this.msgBtns.ok.onclick = new Function(strOKclick);
				}
			}
			else //if (!this.createRegularForm)
			{
				this.msgBtns.ok.onclick = this.close;
			}
		}
		
		//
		// pokaż
		for (var i=0; i<this.msgEls.length; i++)
		{
			this.msgEls[i].style.display = 'block';
		}
		
		// scroll
		var cur_scroll = qmGetPageScroll();
		window.scroll(cur_scroll[0], this.styleTop-this.pozFromTop);
	}

	//
	// .close()
	//
	this.close = function()
	{
		var _this = this;
		// when hooked to a button...
		if (typeof this.msgEls == 'undefined')
		{
			// get object from button div
			_this = this.parentNode.sftJSmsg;
		}
		
		for (var i=0; i<_this.msgEls.length; i++)
		{
			_this.msgEls[i].style.display = 'none';
		}
	}

	//
	// .setOKdisabled(disable)
	//
	this.setOKdisabled = function(disable)
	{
		this.msgBtns.ok.disabled = disable;
	}
}

/* ------------------------------------------------------------------------ *\
	Simple messages
	
	TODO
	* add a way to stop quee messages (in the second message comming from quee)
\* ------------------------------------------------------------------------ */
if (typeof(addOnloadHook) != 'function')
{
	function addOnloadHook(fun)
	{
		if (window.addEventListener)
		{
			window.addEventListener('load', fun, false);
		}
		else if (window.attachEvent)
		{
			window.attachEvent('onload', fun);
		}
		else
		{
			window.onload=fun;
		}
	}
}

var jsAlert_sftJSmsg = null;
var jsAlert_sftJSmsg_quee = [];

//
// Init
//
addOnloadHook (function jsAlert_init()
{
	var msg = new sftJSmsg();
	msg.styleTop = undefined;	// = auto-top = don't scroll
	var win_size = qmGetWindowSize();
	poz_top = Math.floor(win_size[1]/2)-100;	// ~middle
	if (poz_top<0)
	{
		poz_top = 20;
	}
	msg.pozFromTop = poz_top;
	msg.styleWidth = 400;
	msg.showCancel = false;
	msg.autoOKClose = false;
	msg.createRegularForm = false;
	
	jsAlert_sftJSmsg = msg;
});

//
// Quee
//
function jsAlert_quee (i)
{
	//debugger
	if (i<jsAlert_sftJSmsg_quee.length)
	{
		if (i<jsAlert_sftJSmsg_quee.length-1)
		{
			jsAlert(jsAlert_sftJSmsg_quee[i], 'jsAlert_quee('+(i+1)+')')
		}
		// last message
		else
		{
			jsAlert(jsAlert_sftJSmsg_quee[i])
		}
	}
}

//
// Show
//
function jsAlert(txt, strOKclick)
{
	// not loaded? add to quee...
	if (jsAlert_sftJSmsg == null)
	{
		//addOnloadHook (new Function('jsAlert("'+txt+'")'));
		jsAlert_sftJSmsg_quee[jsAlert_sftJSmsg_quee.length] = txt;
		if (jsAlert_sftJSmsg_quee.length==1)
		{
			addOnloadHook (function(){jsAlert_quee(0)});
		}
		return;
	}
	strOKclick = (strOKclick==undefined) ? '' : strOKclick;
	
	// show alert
	var msg = jsAlert_sftJSmsg;
	msg.styleTop = undefined;	// = auto-top = don't scroll
	var win_size = qmGetWindowSize();
	poz_top = Math.floor(win_size[1]/2)-200;	// ~middle
	if (poz_top<0)
	{
		poz_top = 20;
	}
	msg.pozFromTop = poz_top;
	msg.styleWidth = 400;
	msg.show(''
		+'<div class="jsAlert">'
			+txt
		+'</div>'
		,strOKclick
	);	
}

/* ------------------------------------------------------------------------ *\
	Various functions based on info and scripts from
	www.quirksmode.org
	
    Copyright:  ©2008 Maciej Jaros (pl:User:Nux, en:User:Nux), Peter-Paul Koch
     Licencja:  Public domain
\* ------------------------------------------------------------------------ */
// element [left, top]
function qmFindPos(obj)
{
	if (typeof obj != 'object' || obj==null)
	{
		return [0,0];
	}
	
	var curleft = curtop = 0;
	if (obj.offsetParent)
	{
		do
		{
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		}
		while (obj = obj.offsetParent);
	}
	return [curleft, curtop];
}
// page X, Y scroll [width, height]
function qmGetPageScroll()
{
	var retArray;

	if (self.pageYOffset)	// FF, Opera (probably all except IE)
	{
		retArray = [self.pageXOffset, self.pageYOffset];
	}
	else if (document.documentElement && document.documentElement.scrollTop) // IE 6 Strict
	{
		retArray = [document.documentElement.scrollLeft, document.documentElement.scrollTop];
	}
	else if (document.body)	// IE
	{
		retArray = [document.body.scrollLeft, document.body.scrollTop];
	}

	return retArray;
}
// window [width, height]
function qmGetWindowSize()
{
	var retArray;
	
	if (typeof(window.innerWidth) == 'number')	// FF, Opera (probably all except IE)
	{
		retArray = [window.innerWidth, window.innerHeight]
	}
	else if (document.documentElement && document.documentElement.clientWidth) //IE 6 strict
	{
		retArray = [document.documentElement.clientWidth, document.documentElement.clientHeight];
	}
	else if (document.body && document.body.clientWidth) //IE 4 compatible
	{
		retArray = [document.body.clientWidth, document.body.clientHeight];
	}

	return retArray;
}

if (typeof smpAddEvent != 'function')
{
	function smpAddEvent(obj, onwhat, fun)
	{
		if (obj.addEventListener)
		{
			obj.addEventListener(onwhat, fun, false);
		}
		else if (obj.attachEvent)
		{
			obj.attachEvent('on'+onwhat, fun);
		}
		else
		{
			// error
		}
	}
}

//
// Correct size of the shade element(s) on window resize
//
window.sftJSmsgs = new Array();
smpAddEvent(window, 'resize', function()
{
	if (window.sftJSmsgs.length<1)
	{
		return;
	}
	
	for (var i=window.sftJSmsgs.length-1; i>=0; i--)
	{
		var msg = window.sftJSmsgs[i];
		if (msg.msgEls[0].style.display == 'block')
		{
			msg.reInit();
		}
	}
});
