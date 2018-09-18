/*
	Printview JS side...
*
if (location.href.search(/[?&]printview=1([?&]|$)/)>=0)
{
	Event.observe(window,'load',printviewInit);
}
function printviewInit()
{
	var elsCss = document.getElementsByTagName('link')
	for (var i=0; i<elsCss.length; i++)
	{
		var el = elsCss[i];
		var strMedia = el.getAttribute('media') || '';
		if (strMedia.indexOf('print')<0)
		{
			el.setAttribute('media', 'none');
		}
		else
		{
			el.setAttribute('media', 'screen');
		}
	}
}

/*
	Escapeframemode JS side...
	
	Add target for links that does not keep previewmode.
	This should be all links beside those for srting and pages.
*/
if (location.href.search(/[?&]escapeframemode=1([?&]|$)/)>=0)
{
	Event.observe(window,'load',escapeframemodeInit);
}
function escapeframemodeInit()
{
	var elsA = document.getElementsByTagName('a')
	for (var i=0; i<elsA.length; i++)
	{
		var el = elsA[i];
		if (el.href.search(/[?&]do=index([?&]|$)/)<0)	// not an index
		{
			el.href = el.href.replace(/[?&]printview=1(?:[?&]|$)/, '');
		}
		if (el.href.search(/[?&]printview=1([?&]|$)/)<0)	// not in preview mode
		{
			el.setAttribute('target', '_top');
			//el.appendChild(document.createTextNode('[topped]'));
		}
	}
}

//
// Added by Nux - toogle Show/Hide and remeber with a cookie
//
function toogleCookieHideShow(boxid)
{
	//debugger;
	var state_cookie_name = '_h_state_'+boxid;
	var state = Cookie.getVar(state_cookie_name);
	var img_el = $(boxid+'_hider');
	if ('1' == state || '' == state)
	{
		hidestuff(boxid);
		img_el.src = jsglobal_theme_url + 'edit_add.png';
		img_el.parentNode.style.height = '20px';
		Cookie.setVar(state_cookie_name,'0');
	}
	else
	{
		img_el.src = jsglobal_theme_url + 'edit_remove.png';
		img_el.parentNode.style.height = '0px';
		showstuff(boxid);
		Cookie.setVar(state_cookie_name,'1');
	}
}
function setUpCookieHideShow()
{
	//debugger;
	var cookies = Cookie.getVars('_h_state_');
	for (var boxid in cookies)
	{
		if ($(boxid))
		{
			var state = cookies[boxid];
			var img_el = $(boxid+'_hider');
			if ('0' == state)
			{
				img_el.src = jsglobal_theme_url + 'edit_add.png';
				img_el.parentNode.style.height = '20px';
				hidestuff(boxid);
			}
		}
	}
}
addEvent(window,'load',setUpCookieHideShow);

// Set up the task list onclick handler
addEvent(window,'load',setUpTasklistTable);
function Disable(formid)
{
	document.formid.buSubmit.disabled = true;
	document.formid.submit();
}

function showstuff(boxid, type){
	if (!type) type = 'block';
	$(boxid).style.display= type;
	$(boxid).style.visibility='visible';
}

function hidestuff(boxid) {
	$(boxid).style.display = 'none';
}

/**
 * Hide stuff shown by `showhidestuff`.
 * 
 * @param {Event} e Event.
 * @param {String} boxid Parent box shown with `showhidestuff`.
 */
function hidestuff_e(e, boxid) {
	e = e || window.event;

	//console.log('hidestuff_e; boxid:', boxid)

	var eventElement = Event.element(e);
	var box = document.getElementById(boxid);
	// skip if descendant element
	if (box && box.contains(eventElement)) {
		return;
	}

	if (eventElement.id !== 'lastsearchlink' ||
		(eventElement.id === 'lastsearchlink' && $('lastsearchlink').className == 'inactive')) {
		if (!Position.within($(boxid), Event.pointerX(e), Event.pointerY(e))) {
			//Event.stop(e);
			if (boxid === 'mysearches') {
				activelink('lastsearchlink');
			}
			$(boxid).style.visibility = 'hidden';
			$(boxid).style.display = 'none';
			document.onmouseup = null;
		}
	}
}

/**
 * Shows a popup that will be closed after clicking outside of the box.
 * 
 * @note There is some old magic here (i.e. undocument stuff). There might be dragons.
 * 
 * @param {String} boxid Parent box to be shown.
 */
function showhidestuff(boxid) {
	//console.log('showhidestuff; boxid:', boxid)

	if (boxid === 'mysearches') {
		activelink('lastsearchlink');
	}
	switch ($(boxid).style.visibility) {
		case '':
			$(boxid).style.visibility = 'visible';
			break;
		case 'hidden':
			$(boxid).style.visibility = 'visible';
			break;
		case 'visible':
			$(boxid).style.visibility = 'hidden';
			break;
	}
	switch ($(boxid).style.display) {
		case '':
			$(boxid).style.display = 'block';
			document.onmouseup = function (e) {
				hidestuff_e(e, boxid);
			};
			break;
		case 'none':
			$(boxid).style.display = 'block';
			document.onmouseup = function (e) {
				hidestuff_e(e, boxid);
			};
			break;
		case 'block':
			$(boxid).style.display = 'none';
			document.onmouseup = null;
			break;
		case 'inline':
			$(boxid).style.display = 'none';
			document.onmouseup = null;
			break;
	}
}


function setUpTasklistTable() {
  if (!$('tasklist_table')) {
    // No tasklist on the page
    return;
  }
  var table = $('tasklist_table');
  addEvent(table,'click',tasklistTableClick);
}
function tasklistTableClick(e) {
  var src = eventGetSrc(e);
  if (src.nodeName != 'TD') {
    return;
  }
  if (src.hasChildNodes()) {
    var checkBoxes = src.getElementsByTagName('input');
    if (checkBoxes.length > 0) {
      // User clicked the cell where the task select checkbox is
      if (checkBoxes[0].checked) {
        checkBoxes[0].checked = false;
      } else {
        checkBoxes[0].checked = true;
      }
      return;
    }
  }
  var row = src.parentNode;
  var aElements = row.getElementsByTagName('A');
  if (aElements.length > 0) {
    window.location = aElements[0].href;
  } else {
    // If both the task id and the task summary columns are non-visible
    // just use the good old way to get to the task
    window.location = '?do=details&task_id=' + row.id.substr(4);
  }
}

function eventGetSrc(e) {
  if (e.target) {
    return e.target;
  } else if (window.event) {
    return window.event.srcElement;
  } else {
    return;
  }
}

function ToggleSelected(id) {
  var inputs = $(id).getElementsByTagName('input');
  for (var i = 0; i < inputs.length; i++) {
    if(inputs[i].type == 'checkbox'){
      inputs[i].checked = !(inputs[i].checked);
    }
  }
}

function addUploadFields(id) {
  if (!id) {
    id = 'uploadfilebox';
  }
  var el = $(id);
  var span = el.getElementsByTagName('span')[0];
  if ('none' == span.style.display) {
    // Show the file upload box
    span.style.display = 'inline';
    // Switch the buttons
    $(id + '_attachafile').style.display = 'none';
    $(id + '_attachanotherfile').style.display = 'inline';

  } else {
    // Copy the first file upload box and clear it's value
    var newBox = span.cloneNode(true);
    newBox.getElementsByTagName('input')[0].value = '';
    el.appendChild(newBox);
  }
}
function adduserselect(url, user, selectid, error, skipUpdateValueField)
{
    var myAjax = new Ajax.Request(url, {method: 'post', parameters: 'id=' + user, onComplete:function(originalRequest)
	{
        if(originalRequest.responseText && originalRequest.status==200) {
            var user_info = originalRequest.responseText.split('|');
            // Check if user does not yet exist
            for (i = 0; i < $('r' + selectid).options.length; i++) {
                if ($('r' + selectid).options[i].value == user_info[1]) {
                    return;
                }
            }

            opt = new Option(user_info[0], user_info[1]);
            try {
                $('r' + selectid).options[$('r' + selectid).options.length]=opt;
				// no need to update when doing initial filling
				if (!skipUpdateValueField) {
					updateDualSelectValue(selectid);
				}
            } catch(ex) {
                return;
            }
        } else if (typeof error == 'string' && error.length) {
            alert(error);
        } else {
			console.error(originalRequest);
		}
	}});
}
function checkok(url, message, form) {

    var myAjax = new Ajax.Request(url, {method: 'get', onComplete:function(originalRequest)
	{
        if(originalRequest.responseText == 'ok' || confirm(message)) {
            $(form).submit();
        }
	}});
    return false;
}
function removeUploadField(element, id) {
  if (!id) {
    id = 'uploadfilebox';
  }
  var el = $(id);
  var span = el.getElementsByTagName('span');
  if (1 == span.length) {
    // Clear and hide the box
    span[0].style.display='none';
    span[0].getElementsByTagName('input')[0].value = '';
    // Switch the buttons
    $(id + '_attachafile').style.display = 'inline';
    $(id + '_attachanotherfile').style.display = 'none';
  } else {
    el.removeChild(element.parentNode);
  }
}

function updateDualSelectValue(id)
{
    var rt  = $('r'+id);
    var val = $('v'+id);
    val.value = '';

    var i;
    for (i=0; i < rt.options.length; i++) {
        val.value += (i > 0 ? ' ' : '') + rt.options[i].value;
    }
}
function remove_0val(id) {
    el = $(id);
    for (i = 0; i < el.options.length; i++) {
        if (el.options[i].value == '0') {
	    el.removeChild(el.options[i]);
        }
    }
}
function fill_userselect(url, id) {
    var users = $('v' + id).value.split(' ');
    for (i = 0; i < users.length; i++) {
        if(users[i]) adduserselect(url, users[i], id, '', true);
    }
}

function dualSelect(from, to, id) {
    if (typeof(from) == 'string') {
	from = $(from+id);
    }
    if (typeof(to) == 'string') {
        var to_el = $(to+id);
	// if (!to_el) alert("no element with id '" + (to+id) + "'");
	to = to_el;
    }

    var i;
    var len = from.options.length;
    for(i=0;i<len;++i) {
	if (!from.options[i].selected) continue;
	if (to && to.options)
	    to.appendChild(from.options[i]);
	else
	    from.removeChild(from.options[i]);
	// make the option that is slid down selected (if any)
	if (len > 1)
	    from.options[i == len - 1 ? len - 2 : i].selected = true;
	break;
    }

    updateDualSelectValue(id);
}

function selectMove(id, step) {
    var sel = $('r'+id);

    var i = 0;

    while (i < sel.options.length) {
        if (sel.options[i].selected) {
            if (i+step < 0 || i+step >= sel.options.length) {
                return;
            }
	    if (i + step == sel.options.length - 1)
		sel.appendChild(sel.options[i]);
	    else if (step < 0)
		sel.insertBefore(sel.options[i], sel.options[i+step]);
	    else
		sel.insertBefore(sel.options[i], sel.options[i+step+1]);
            updateDualSelectValue(id);
            return;
        }
        i++;
    }
}
var Cookie = {
  getVars: function(prefix) {
    var cookie = document.cookie;
    if (cookie.length > 0) {
      cookie += ';';
    }
	else {
		return {};
	}
	ret_obj = {};
	var re = (prefix) ? new RegExp(prefix + '(.+?)\=(.*?);', 'g') : /(.+?)\=(.*?);/g;
    cookie.replace(re, function(a, name, val) {
		ret_obj[name] = val;
	});
    return ret_obj;
  },
  getVar: function(name) {
    var cookie = document.cookie;
    if (cookie.length > 0) {
      cookie += ';';
    }
    re = new RegExp(name + '\=(.*?);' );
    if (cookie.match(re)) {
      return RegExp.$1;
    } else {
      return '';
    }
  },
  setVar: function(name,value,expire,path) {
    document.cookie = name + '=' + value;
  },
  removeVar: function(name) {
    var date = new Date(12);
    document.cookie = name + '=;expires=' + date.toUTCString();
  }
};
function setUpSearchBox() {
  if ($('advancedsearch')) {
    var state = Cookie.getVar('advancedsearch');
    if ('1' == state) {
      var showState = $('advancedsearchstate');
      showState.replaceChild(document.createTextNode('+'),showState.firstChild);
      $('sc2').style.display = 'block';
    }
  }
}
function toggleSearchBox(themeurl) {
  var state = Cookie.getVar('advancedsearch');
  if ('1' == state) {
      $('advancedsearchstateimg').src = themeurl + 'edit_add.png';
      hidestuff('sc2');
      Cookie.setVar('advancedsearch','0');
  } else {
      $('advancedsearchstateimg').src = themeurl + 'edit_remove.png';
      showstuff('sc2');
      Cookie.setVar('advancedsearch','1');
  }
}
function deletesearch(id, url) {
    var img = $('rs' + id).getElementsByTagName('img')[0].src = url + 'themes/Bluey/ajax_load.gif';
    url = url + 'javascript/callbacks/deletesearches.php';
    var myAjax = new Ajax.Request(url, {method: 'get', parameters: 'id=' + id,
                     onSuccess:function()
                     {
                        var oNodeToRemove = $('rs' + id);
                        oNodeToRemove.parentNode.removeChild(oNodeToRemove);
                        var table = $('mysearchestable');
                        if(table.rows.length > 0) {
                            table.getElementsByTagName('tr')[table.rows.length-1].style.borderBottom = '0';
                        } else {
                            showstuff('nosearches');
                        }
                     }
                });
}
function savesearch(query, baseurl, savetext) {
    url = baseurl + 'javascript/callbacks/savesearches.php?' + query + '&search_name=' + encodeURIComponent($('save_search').value);
    if($('save_search').value != '') {
        var old_text = $('lblsaveas').firstChild.nodeValue;
        $('lblsaveas').firstChild.nodeValue = savetext;
        var myAjax = new Ajax.Request(url, {method: 'get',
                     onComplete:function()
                     {
                        $('lblsaveas').firstChild.nodeValue=old_text;
                        var myAjax2 = new Ajax.Updater('mysearches', baseurl + 'javascript/callbacks/getsearches.php', { method: 'get'});
                     }
                     });
    }
}
function activelink(id) {
    if($(id).className == 'active') {
        $(id).className = 'inactive';
    } else {
        $(id).className = 'active';
    }
}
var useAltForKeyboardNavigation = false;  // Set this to true if you don't want to kill
                                         // Firefox's find as you type

function emptyElement(el) {
    while(el.firstChild) {
        emptyElement(el.firstChild);
        var oNodeToRemove = el.firstChild;
        oNodeToRemove.parentNode.removeChild(oNodeToRemove);
    }
}
function showPreview(textfield, baseurl, field)
{
    var preview = $(field);
    emptyElement(preview);

    var img = document.createElement('img');
    img.src = baseurl + 'themes/Bluey/ajax_load.gif';
    img.id = 'temp_img';
    img.alt = 'Loading...';
    preview.appendChild(img);

    var text = $(textfield).value;
    text = encodeURIComponent(text);
    var url = baseurl + 'javascript/callbacks/getpreview.php';
    var myAjax = new Ajax.Updater(field, url, {parameters:'text=' + text, method: 'post'});

    if (text == '') {
        hidestuff(field);
    } else {
        showstuff(field);
    }
}
function checkname(value){
    new Ajax.Request('javascript/callbacks/searchnames.php?name='+value, {onSuccess: function(t){ allow(t.responseText); } });
}
function allow(booler){
    if(booler.indexOf('false') > -1) {
        $('username').style.color ='red';
        $('buSubmit').style.visibility = 'hidden';
        $('errormessage').innerHTML = booler.substring(6,booler.length);
    }
    else {
        $('username').style.color ='green';
        $('buSubmit').style.visibility = 'visible';
        $('errormessage').innerHTML = '';
    }
}
function getHistory(task_id, baseurl, field, details)
{
    var url = baseurl + 'javascript/callbacks/gethistory.php?task_id=' + task_id;
    if (details) {
        url += '&details=' + details;
    }
    var myAjax = new Ajax.Updater(field, url, { method: 'get'});
}

/*********  Permissions popup  ***********/

function createClosure(obj, method) {
    return (function() { obj[method](); });
}

function Perms(id) {
    this.div = $(id);
}

Perms.prototype.timeout = null;
Perms.prototype.div     = null;

Perms.prototype.clearTimeout = function() {
    if (this.timeout) {
        clearTimeout(this.timeout);
        this.timeout = null;
    }
}

Perms.prototype.do_later = function(action) {
    this.clearTimeout();
    closure = createClosure(this, action);
    this.timeout = setTimeout(closure, 400);
}

Perms.prototype.show = function() {
    this.clearTimeout();
    this.div.style.display = 'block';
    this.div.style.visibility = 'visible';
}

Perms.prototype.hide = function() {
    this.clearTimeout();
    this.div.style.display = 'none';
}

// Replaces the currently selected text with the passed text.
function replaceText(text, textarea)
{
	if (typeof textarea === 'string') {
		textarea = document.getElementById( textarea );
	}
	textarea.focus();
	
	var newText = text;
	// attempting to paste to preserver undo functionality
	var pasted = true;
	try {
		//textarea.focus();
		if (!document.execCommand("insertText", false, newText)) {
			pasted = false;
		}
	} catch (e) {
		pasted = false;
	}
	// fallback
	if (!pasted) {
		console.warn('paste unsuccessful, fallback to standard paste');
		sel_t.setSelStr(textarea, newText);
	}
}


// Surrounds the selected text with text1 and text2.
function surroundText(text1, text2, textarea)
{
	textarea = document.getElementById( textarea );
	
	var selectedText = sel_t.getSelStr(textarea);
	var newText = text1 + selectedText + text2;
	
	replaceText(newText, textarea)
}

// Replace `search` with `replacement` in the `textarea`
// `search` can be RegExp
// When nothig is selected `insert` will be used.
function replaceInSelectedText(insert, search, replacement, textarea)
{
	textarea = document.getElementById( textarea );
	
	var selectedText = sel_t.getSelStr(textarea);
	
	// empty selection -- just insert text
	if (selectedText.length === 0) {
		replaceText(insert, textarea);
		return;
	}
	
	var newText = selectedText.replace(search, replacement);
	
	replaceText(newText, textarea)
}

function stopBubble(e) {
	if (!e) { var e = window.event; }
	e.cancelBubble = true;
	if (e.stopPropagation) { e.stopPropagation(); }
}



