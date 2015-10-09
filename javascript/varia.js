/* ============================================== *\
	Varia (+show/hide)
	
	version:	1.8.3.2
	copyright:	(C) 2006-2010 Maciej Jaros
	license:	GNU General Public License v2,
				http://opensource.org/licenses/gpl-license.php
\* ============================================== */

/* ===================================================== *\
	Array.indexOf() dla kompatybilnoœci ró¿nych skryptów z IE
\* ===================================================== */
// (skrypt z Mozilli)
if (!Array.prototype.indexOf)
{
	Array.prototype.indexOf = function(elt /*, from*/)
	{
		var len = this.length;

		var from = Number(arguments[1]) || 0;
		from = (from < 0) ? Math.ceil(from) : Math.floor(from);
		if (from < 0)
			from += len;

		for (; from < len; from++)
		{
			if (from in this && this[from] === elt)
				return from;
		}
		return -1;
	};
}
/* ===================================================== *\
	Dodawnie funkcji (tak¿e wielu) na start
\* ===================================================== */
if (typeof addOnloadHook != 'function')
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

/* ===================================================== *\
	Dodawnie funkcji (tak¿e wielu) do eventów
\* ===================================================== */
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


/* ===================================================== *\
	Function: keyPressedGetter
	
	Pobiera naciœniêty klawisz
	
	Przyk³ad u¿ycia:
	document.onkeyup = function(e) { var pressed = keyPressedGetter(e); if (pressed.ch=='p') { do_blah_when_P_pressed() } };
	Zwrot
	------
	{
		'keynum' : numer wciœniêtego klawisz (np. 13 = ENTER),
		'ch': wciœniêta literka zmieniona zawsze na ma³y znak
	}

	Params
	------
	 e - event dla funkcji
\* ===================================================== */
function keyPressedGetter(e)
{
	var keynum;
	var character;

	if(window.event) // IE
	{
		e = window.event;
	}
	keynum = e.keyCode;
	character = String.fromCharCode(keynum).toLowerCase();

	return {
		'keynum' : keynum,
		'ch': character
	}
}

/* ===================================================== *\
	Function: jsHtmlEntityDecode
	
	Dekodowanie encji HTML

	Params
	------
	 str - ci¹g znaków z encjami
\* ===================================================== */
function jsHtmlEntityDecode(str)
{
	try
	{
		var nel=document.createElement('textarea');
		nel.innerHTML = str;
		str = nel.value;
	}
	// IE shi...
	catch(e)
	{
		var nel;
		nel = document.getElementById('my_html_ent_deco_for_fantastic_IE');
		if (!nel)
		{
			nel = document.createElement('div');
			nel.style.display = 'none';
			nel.id = 'my_html_ent_deco_for_fantastic_IE';
			document.body.appendChild(nel);
		}
		nel.innerHTML = '<textarea>'+str+'</textarea>';
		str = nel.firstChild.value;
	}
	return str
}

/* ===================================================== *\
	Function: getJSCookie
	
	Pobieranie wartoœci ciasteczka

	Params
	------
	 name - nazwa ciach
\* ===================================================== */
function getJSCookie(name)
{
	// tworzymy RegExp szukaj¹cy wartoœci wystêpuj¹cej po nazwie
	var reCiacho = new RegExp("(?:^|;[ ]?)" + escape(name) + "=(.+?)(;|$)");
	var arrZnajdy = reCiacho.exec(document.cookie)
	// jeœli nie ma ciastka, to null, w przeciwnym wypadku usuñ escapowanie ze znaleziska
	return (!arrZnajdy) ? null : unescape(arrZnajdy[1]);
}
/* ===================================================== *\
	Function: setJSCookie
	
	Ustawianie ciasteczka
	// typical usage (expires in 1 hour):
	setJSCookie({name:"testini", expires:3600}, 1234);
	// ciacho sesyjne
	setJSCookie("testini", 1234);

	Params
	------
	ciacho = {
	 name - nazwa ciacha
	 path - œcie¿ka widocznoœci ciacha
	 expires - JS Date() set apropriatly or num of seconds from now (+/-)
	 domain - domain for the cookie
	 secure - true/false
	}
	value - wartoœæ do ustawienia
\* ===================================================== */
function setJSCookie(ciacho, value)
{
	var strCiacho = "";
	// ciacho is not obj
	if (typeof(ciacho)=='string')
	{
		strCiacho = escape(ciacho)+"="+escape(value)+";";
		document.cookie = strCiacho;
		return;
	}
	// name not given
	if (!typeof(ciacho)=='object' || typeof(ciacho.name)=='undefined')
	{
		return;
	}
	// name given in ciacho obj
	strCiacho = escape(ciacho.name)+"="+escape(value)+";";
	
	// expires - JS Date() set apropriatly or num of seconds from now (+/-)
	if (typeof(ciacho.expires)!='undefined')
	{
		var dt;
		// sekundy
		if (typeof(ciacho.expires)=='number')
		{
			dt = new Date()
			dt = new Date(dt.getTime()+ciacho.expires*1000);
		}
		// dt
		else
		{
			dt = ciacho.expires;
		}
		strCiacho += ' expires='+dt.toGMTString()+';';
	}
	
	// the rest
	if (typeof(ciacho.path)!='undefined')
	{
		strCiacho += ' path='+ciacho.path+';';
	}
	if (typeof(ciacho.domain)!='undefined')
	{
		strCiacho += ' domain='+ciacho.domain+';';
	}
	if (typeof(ciacho.secure)!='undefined' && ciacho.secure)
	{
		strCiacho += ' secure';
	}
	// debug
	if (typeof(window.console)=="object" && typeof(window.console.log)=="function")
	{
		console.log(strCiacho);
	}
	// set
	document.cookie = strCiacho;
}

/* ===================================================== *\
	Function: markAllChkBoxes
	
	Zaznacza wszystkie elementy typu "checkbox" na stronie

	Params
	------
	 state - opcjonalny stan jaki nale¿y ustawiæ (true - zaznaczony, false - niezaznaczony)
	 prefix - opcjonalny prefix nazwy checkboksa
\* ===================================================== */
function markAllChkBoxes(state, prefix)
{
	if (typeof state == 'undefined')
	{
		state = true;
	}
	if (typeof prefix == 'undefined')
	{
		prefix = '';
	}
	
	var inpt = document.getElementsByTagName('input');
	for (var i=inpt.length-1; i>=0; i--)
	{
		if (inpt[i].type=='checkbox' && inpt[i].name.indexOf(prefix)===0)
		{
			inpt[i].checked = state;
		}
	}
}

/* ===================================================== *\
	Function: getAllChkBoxes
	
	Zwraca tablicê z wartoœciami checkboksów, które s¹ zaznaczone.

	Params
	------
	 prefix - [opcjonalny] prefix nazwy checkboksa
	 uniqueOnly - [opcjonalny] jeœli true wywalane s¹ powtórzone wyst¹piwnie wartoœci
\* ===================================================== */
function getAllChkBoxes(prefix, uniqueOnly)
{
	if (typeof prefix == 'undefined')
	{
		prefix = '';
	}
	if (typeof uniqueOnly == 'undefined')
	{
		uniqueOnly = false;
	}
	
	var ret_arr = new Array();
	var inpt = document.getElementsByTagName('input');
	for (var i=inpt.length-1; i>=0; i--)
	{
		if (inpt[i].type=='checkbox' && inpt[i].checked && inpt[i].name.indexOf(prefix)===0)
		{
			if (uniqueOnly && ret_arr.indexOf(inpt[i].value)!=-1)
			{
				continue;
			}
			ret_arr[ret_arr.length] = inpt[i].value;
		}
	}
	
	return ret_arr;
}

/* ===================================================== *\
	Function: getAllRadioBtns
	
	Zwraca tablicê z wartoœciami radiobuttonów, które s¹ zaznaczone.

	Params
	------
	 prefix - opcjonalny prefix nazwy radio buttona
	 top_el - opcjonalny element nadrzêdny
\* ===================================================== */
function getAllRadioBtns(prefix, top_el)
{
	if (typeof prefix == 'undefined')
	{
		prefix = '';
	}
	if (typeof top_el == 'undefined')
	{
		top_el = document;
	}
	
	var ret_arr = new Array();
	var inpt = top_el.getElementsByTagName('input');
	for (var i=inpt.length-1; i>=0; i--)
	{
		if (inpt[i].type=='radio' && inpt[i].checked && inpt[i].name.indexOf(prefix)===0)
		{
			ret_arr[ret_arr.length] = inpt[i].value;
		}
	}
	
	return ret_arr;
}

/* ===================================================== *\
	Function: SetOpacity
\* ===================================================== */
function setOpacity(element_id, value)
{
	var obj_style;
	if (document.getElementById(element_id)) {
		obj_style = document.getElementById(element_id).style;

		obj_style.opacity = value/100;						// mozilla
		obj_style.filter = 'alpha(opacity=' + value + ')';	// exploder
	}	
}

/* ===================================================== *\
	Function: winPop

	Simple window popup maker.
	DO NOT USE THIS IN HREF! HREF is for simple URL.
	Use this in onclick e.g. like this:
		onclick="winPop(this.href)"

	Params
	------
	 url - url to be opened in popuped up window
	 nazwa [optional] - a name of the window
\* ===================================================== */
function winPop(url, nazwa)
{
	if (!nazwa)	{nazwa = 'nowe_okienko'}
	window.open(url, nazwa, 'resizable=yes,scrollbars=yes');
}

/* ===================================================== *\
	Function: maximizeBlock

	Maximizing width of some block element.

	Params
	------
	 element_id - text id of some block element 
		(like thingy in <div id="thingy">)
\* ===================================================== */
function maximizeBlock(element_id)
{
	var objBlock = document.getElementById(element_id);
	if (objBlock)
	{
		//
		// If tagged
		// 	then bring back saved width
		//
		if (objBlock.tag)
		{
			objBlock.tag = 0;
			objBlock.style.width = objBlock.defWidth + 'px';
		}
		//
		// If untagged
		// 	then tag, save width and set maximum width
		//
		else
		{
			objBlock.tag = 1;
			objBlock.defWidth = objBlock.clientWidth;

			var documentWidth;
			var objDocumentElem = document.documentElement;
			if (!objDocumentElem)
			{
				objDocumentElem = document.body;
			}
			documentWidth = objDocumentElem.offsetWidth - 2;

			objBlock.style.width = documentWidth + 'px';
		}
	}
	return false;
}

/* ===================================================== *\
	Function: minimizeBlock

	Minimizing some block element. This includes hidding
	all child elements so to preserve some (like the bring back
	element) tag it with class="preserve_me"

	Params
	------
	 element_id - text id of some block element 
		(like thingy in <div id="thingy">)
\* ===================================================== */
function minimizeBlock(element_id)
{
	var objBlock = document.getElementById(element_id);
	if (objBlock)
	{
		//
		// If tagged by minimize
		// 	then bring back saved width and hidden elements
		//
		if (objBlock.tag==2)
		{
			objBlock.tag = 0;
			objBlock.style.width = objBlock.defWidth + 'px';
			
			for (var i=objBlock.childNodes.length-1; i>=0; i--)
			{
				if (objBlock.childNodes.item(i).nodeType == 1 && objBlock.childNodes.item(i).className!='preserve_me')
				{
					if (objBlock.childNodes.item(i).nodeName=='DIV')
					{
						objBlock.childNodes.item(i).style.display = 'block';
					}
					else
					{
						objBlock.childNodes.item(i).style.display = 'inline';
					}
				}
			}
		}
		//
		// If untagged
		// 	then save width, tag and do the rest
		// If tagged by maximize
		// 	then just tag and hide child elements 
		// 	which class is different then preserve_me
		//
		else
		{
			if (!objBlock.tag)
			{
				objBlock.defWidth = objBlock.clientWidth;
			}
			objBlock.tag = 2;
			for (var i=objBlock.childNodes.length-1; i>=0; i--)
			{
				if (objBlock.childNodes.item(i).nodeType == 1 && objBlock.childNodes.item(i).className!='preserve_me')
				{
					objBlock.childNodes.item(i).style.display = 'none';
				}
			}
			
			objBlock.style.width = 16 + 'px';
		}
	}
	return false;
}

/* ===================================================== *\
	Function: dbgJS

	Debuging of JS by displaying some text.

	Params
	------
	 txt - some text to display
\* ===================================================== */
function dbgJS(txt)
{
	document.getElementById('JSdebug').innerHTML += txt;
	return false;
}

/* ===================================================== *\
	Function: dbgJSinit
	
	Initialization of debuging.
\* ===================================================== */
function dbgJSinit()
{
	if (document.getElementById('debug_msgtext'))
	{
		var newEl = document.createElement('div');	
		newEl.id = 'JSdebug';
		document.getElementById('debug_msgtext').appendChild(newEl);
	}
}
addOnloadHook(dbgJSinit);

/* ===================================================== *\
	Function: Show block

	 To be more exact it shows or hides (on second call)
	 given block of HTML.

	 Note that the block should be hidden by it's default.

	 returns true if shown, false otherwise

	Params
	------
	 block - an id of a HTML element or the element itself (object)
\* ===================================================== */
function showBlock(block)
{
	var s = (typeof block=='object') ? block : document.getElementById(block);
	
	if (s.style.display != 'block')
	{
		s.style.display = 'block';
		return true;
	}
	else
	{
		s.style.display = 'none';
		return false;
	}
}
// same as above but changes src of the given img element from plus. to minus.
function showBlockPMimg(block, plus_minus_img)
{
	var s = (typeof block=='object') ? block : document.getElementById(block);
	if (s.style.display != 'block')
	{
		s.style.display = 'block';
		plus_minus_img.src = plus_minus_img.src.replace('plus.', 'minus.');
	}
	else
	{
		s.style.display = 'none';
		plus_minus_img.src = plus_minus_img.src.replace('minus.', 'plus.');
	}
}

/* ===================================================== *\
	Function: showSubBlocksPMimg

	 Shows or hides (on second call) all sub blocks of HTML
	 that match the rules given in params.
	 
	Params
	------
	 parentBlock - an id of a HTML element or the element itself (object)
			the element must be a parent to all elements that must be shown.
	 // specification of elements to be shown (currently all attributes are expected to be set)
	 elsToShow
	 {
		jv_tag - name of a tag of elements
		jv_idprefix - prefix of ids of elements
	 }
	 // specification of an element triggering the change (all attributes are expected to be set)
	 triggerEl
	 {
		jv_el - should always be set to "this" (object of the trigger element)
		jv_hidden - text (or HTML) to set when blocks are hidden (like 'show all')
		jv_shown - text (or HTML) to set when blocks are show (like 'hide all')
	 }
	 // specification of img elements that trigger changes in sub elements (attributes in [] are optional)
	 subElsImgs
	 {
		[jv_class] - class of plus-minus image elements (may be one of classes)
		jv_plus_img - url to "plus" image (to be shown image)
		jv_minus_img - url to "minus" image (to be hidden image)
	 }
\* ===================================================== */
function showSubBlocksPMimg(parentBlock, elsToShow, triggerEl, subElsImgs)
{
	parentBlock = (typeof parentBlock=='object') ? parentBlock : document.getElementById(parentBlock);
	
	// set up current state
	var cur_state = 'hidden';
	if (triggerEl.jv_el.innerHTML == triggerEl.jv_shown)
	{
		cur_state = 'shown'
	}
	
	// change text on trigger
	if (cur_state == 'hidden')
	{
		triggerEl.jv_el.innerHTML = triggerEl.jv_shown;
	}
	else
	{
		triggerEl.jv_el.innerHTML = triggerEl.jv_hidden;
	}
	
	// move through sub elements and change their state...
	var els = parentBlock.getElementsByTagName(elsToShow.jv_tag);
	for (var i=0; i<els.length; i++)
	{
		var el = els[i];
		// check if this is the one
		if (el.id.indexOf(elsToShow.jv_idprefix)===0)
		{
			// if state is "hidden" then show
			if (cur_state=='hidden')
			{
				el.style.display = 'block';
			}
			// hide
			else
			{
				el.style.display = 'none';
			}
		}
	}
	// move through sub elements images and change their state...
	var els = parentBlock.getElementsByTagName('img');
	var class_re = '';
	if (typeof (subElsImgs.jv_class)!='undefined')
	{
		class_re = new RegExp('(^| )'+subElsImgs.jv_class+'( |$)');
	}
	for (var i=0; i<els.length; i++)
	{
		var el = els[i];
		// check if this is the one
		if (el.className.search(class_re)!=-1)
		{
			// if state is "hidden" then show
			if (cur_state=='hidden')
			{
				el.src = subElsImgs.jv_minus_img;
			}
			// hide
			else
			{
				el.src = subElsImgs.jv_plus_img;
			}
		}
	}
}

/* ===================================================== *\
    Function: Hides block

	 To be more exact it hides or shows (on second call)
	 given block of HTML.

	 Note that the block should be shown by it's default.


	Params
	------
	 block - an id of a HTML element or the element itself (object)
\* ===================================================== */
function hideBlock(block)
{
	s = (typeof block=='object') ? block : document.getElementById(block);

	if (s.style.display != 'none')
		s.style.display = 'none';
	else
		s.style.display = 'block';
}


/* ===================================================== *\
	Function: Forced show/hide block

	 This just shows/hides given block of HTML.

	 Note that the behavior is always the same here.


	Params
	------
	 block - an id of a HTML element or the element itself (object)
\* ===================================================== */
function forcedShowBlock(block)
{
	s = (typeof block=='object') ? block : document.getElementById(block);

	s.style.display = 'block';
}

function forcedHideBlock(block)
{
	s = (typeof block=='object') ? block : document.getElementById(block);

	s.style.display = 'none';
}

/* ===================================================== *\
	Function: getDocObjHeight

	Gets (or attempts to get) the height of a document.
	Created with documents inside iframe element in mind.

	Params
	------
	 doc_obj - document object (not element)
\* ===================================================== */
function getDocObjHeight(doc_obj)
{
	var content_height=0;
	if (doc_obj)
	{
		if (doc_obj.documentElement)
		{
			var obj = doc_obj.documentElement;
			if (obj.offsetHeight > content_height)
				content_height = obj.offsetHeight
			;
			if (obj.scrollHeight > content_height)
				content_height = obj.scrollHeight
			;
		}
		if (doc_obj.body)
		{
			var obj = doc_obj.body;
			if (obj.offsetHeight > content_height)
				content_height = obj.offsetHeight
			;
			if (obj.scrollHeight > content_height)
				content_height = obj.scrollHeight
			;
		}
	}
	
	return content_height;
}
/* ===================================================== *\
	Function: getDocObjWidth

	Gets (or attempts to get) the width of a document.
	Created with documents inside iframe element in mind.

	Params
	------
	 doc_obj - document object (not element)
\* ===================================================== */
function getDocObjWidth(doc_obj)
{
	var content_width=0;
	if (doc_obj)
	{
		if (doc_obj.documentElement)
		{
			var obj = doc_obj.documentElement;
			if (obj.offsetWidth > content_width)
				content_width = obj.offsetWidth
			;
			if (obj.scrollWidth > content_width)
				content_width = obj.scrollWidth
			;
		}
		if (doc_obj.body)
		{
			var obj = doc_obj.body;
			if (obj.offsetWidth > content_width)
				content_width = obj.offsetWidth
			;
			if (obj.scrollWidth > content_width)
				content_width = obj.scrollWidth
			;
		}
	}
	
	return content_width;
}

/* ===================================================== *\
	Function: getFrameDocObj

	Gets (or attempts to get) the document object/node
	of the given frame (iframe).

	Params
	------
	 frm - frame element or it's ID
\* ===================================================== */
function getFrameDocObj(frm)
{
	el = (typeof frm=='object') ? frm : document.getElementById(frm);
	return (el.contentDocument) ? el.contentDocument : el.Document;
}

/*
*
function alertSize() {
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myWidth = window.innerWidth;
		myHeight = window.innerHeight;
	} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
		myHeight = document.documentElement.clientHeight;
	} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myWidth = document.body.clientWidth;
		myHeight = document.body.clientHeight;
	}
	window.alert( 'Width = ' + myWidth );
	window.alert( 'Height = ' + myHeight );
}

/* ===================================================== *\
	Function: resizeIframeHeight

	Resizes the height of an iframe to height of its
	content. Should be used with onload of iframe.

	Params
	------
	 frame - iframe element object
\* ===================================================== */
function resizeIframeHeight(frame)
{
	var extraHeight = 30;	//extra height in px to add to iframe 
	if (frame)
	{
		frame.style.display="block"
		//var frame_doc = (frame.contentDocument) ? frame.contentDocument : frame.Document;
		var frame_doc = getFrameDocObj(frame);
		frame.height = '';	// inaczej poprzednia wysokoœæ mo¿e zak³óciæ poni¿sz¹ funkcjê
		var new_h = getDocObjHeight(frame_doc) + extraHeight;
		// ustawienie optymalnej, minimalnej wysokoœci
		if (frame.style.position!='absolute')
		{
			if (new_h<400)	// optymalna wysokoœæ
			{
				new_h=400;
			}
		}
		else
		{
			var window_h = (document.documentElement) ? document.documentElement.clientHeight : document.body.clientHeight;
			if (new_h<window_h)	// optymalna wysokoœæ
			{
				new_h=window_h;
			}
		}
		frame.height = new_h;
	}
}

/* ===================================================== *\
	Function: imgsArrPreload

	Preloads an array of images.

	Params
	------
	 imgsArr - array of image file names
\* ===================================================== */
function imgsArrPreload (imgsArr)
{
	preImgs = new Array()
	for(var i=0; i < imgsArr.length; i++)
	{
		if (imgsArr[i] != '')
		{
			preImgs[i] = new Image()
			preImgs[i].src = imgsArr[i];
		}
	}
}