//
// Szablony i inne udoskonalenia bara opisów
//
// szablony spraw w javascript\frmextra.js
function cNuxbar(objName)
{
	var _this = this;

	/*!
		@brief Form Templates definition object
	*/
	this.tpls =
	{
		//! buttons/tabs
		buttons : ''
	};
	
	/*!
		@brief Docuwiki link prefixes array with names and types
		
		@note
			- wiki type is special in it's formating rules
			- SVNrev type will be used in insertSVNURL (with "display" in drop down menu)

		@example
		{
			display:'MyBugz',
			prefix:'fs',
			type:'general',
			url:'http://your.url.com/path/'
		},
	*/
	this.arrPrefixes = new Array();

	/*!
		@brief An array of templates containing text/dokuwiki code to be inserted
		
		@example
		{
			strLabel	:'Short button label',
			strHint		:'A bit more information about this',
			strText		:'Some text to insert use $|$ to place cursor (by default at the end).'
		},
	*/
	this.arrInsertTpls = new Array();
	
	/*!
		@brief An array of replacements for extrernal URLs
		
		@note all regexpes are created with case-insenstive and global flags
		
		@example
		{
			reInter: '(//Serv/|//Serv/Apache/htdocs/|W:/)http/',
			strExt : 'http://www.mol.pl/http/',
		},
	*/
	this.arrInternalToExternal = new Array();

	/*!
		@brief Init to be run onload/docready
	*/
	this.init = function ()
	{
		_this.msg = new sftJSmsg();
		_this.msg.styleTop = undefined;	// = auto-top = don't scroll
		
		var win_size = qmGetWindowSize();
		poz_top = Math.floor(win_size[1]/2)-200;	// ~middle
		if (poz_top<0)
		{
			poz_top = 20;
		}
		_this.msg.pozFromTop = poz_top;
		
		_this.msg.styleWidth = 300;
		_this.msg.showCancel = true;
	}
	
	/*!
		@brief Shows basic URL insert form (message)
	*/
	this.insertURL = function (txtarea_id)
	{
		var txtarea = document.getElementById(txtarea_id)
		var txt = sel_t.getSelStr (txtarea, false);

		var url, name;
		// spr, czy link
		if (txt.search(/https?:\/\//)!=-1)
		{
			url = txt;
			name = '';
		}
		else
		{
			url = 'http://';
			name = txt;
		}
		_this.msg.show(''
			+ _this.tpls.buttons.replace(/%txtarea_id%/g, txtarea_id)
			+'<div style="text-align:center">'
				+'<p>URL: <input style="width:200px" type="text" name="'+objName+'_url" value="'+url+'" onkeypress="event.which == 13 && '+objName+'.msg.msgBtns.ok.onclick()" /></p>'
				+'<p>Opis: <input style="width:200px" type="text" name="'+objName+'_url_name" value="'+name+'" onkeypress="event.which == 13 && '+objName+'.msg.msgBtns.ok.onclick()" /></p>'
				+'<p style="font-size:90%">Przy wstawianiu linków do artykułów Wiki nie trzeba dodawać opisu.</p>'
			+'</div>',
			objName+'.parseAndInsertURL("'+txtarea_id+'")'
		);
		
		// select first field
		var els = _this.msg.elMain.getElementsByTagName('input');
		els[0].focus();
	}
	
	/**
	 * Get matching url prefix
	 * 
	 * @return
	 *	\li empty string if not matched
	 *	\li url or alternative url from the prefix object
	 */
	this.matchURL = function(url, prefixObject)
	{
		if (url.indexOf(prefixObject.url)===0) {
			return prefixObject.url;
		}
		if ('alt_urls' in prefixObject) {
			for (var i=0; i<prefixObject.alt_urls.length; i++) {
				if (url.indexOf(prefixObject.alt_urls[i])===0) {
					return prefixObject.alt_urls[i];
				}
			}
		}
		return "";
	};

	/*!
		@brief Parse form fields and inset URL code
		
		@note uses two form fileds named:
			- objName+'_url'
			- objName+'_url_name'
	*/
	this.parseAndInsertURL = function(txtarea_id)
	{
		// get url+name
		var url = _this.getMsgField(objName+'_url').value;
		var name = _this.getMsgField(objName+'_url_name').value;
		
		// change prefixes
		for (var i=0; i<_this.arrPrefixes.length; i++)
		{
			var cur_pref = _this.arrPrefixes[i];
			if (!('prefix' in cur_pref) || cur_pref.prefix.length<=0) {
				continue;
			}
			var matched_url = _this.matchURL(url, cur_pref);
			if (matched_url.length>0)
			{
				url = url.replace(matched_url, cur_pref.prefix+'>');

				// need to get hash out of url as it requires different parsing method
				var hash = '';
				url = url.replace(/(.*?)(#.+)/, function(a, pre, post){
					hash = post;
					return pre;
				});
				
				// extra parsing for wiki
				if (cur_pref.type==='wiki')
				{
					url = url.replace (/_/g, ' ');
				}
				
				url = decodeURIComponent(url) + hash;	// dokuwiki to koduje
				break;
			}
		}
		
		// insert link
		if (name.length)
		{
			replaceText('[['+url+'|'+name+']]', txtarea_id);
		}
		else
		{
			replaceText('[['+url+']]', txtarea_id);
		}
	}
	
	/*!
		@brief Shows SVN form (message)
	*/
	this.insertSVNURL = function (txtarea_id)
	{
		var txtarea = document.getElementById(txtarea_id)
		var txt = sel_t.getSelStr (txtarea, false);

		var url, name;
		// spr, czy link
		if (txt.search(/https?:\/\//)!=-1)
		{
			url = txt;
			name = '';
		}
		else
		{
			url = 'http://';
			name = txt;
		}
		// gen prefixes select
		var strHTMLOpt = ''
		for (var i=0; i<_this.arrPrefixes.length; i++)
		{
			var cur_pref = _this.arrPrefixes[i];
			if (cur_pref.prefix.length>0 && cur_pref.type=='SVNrev')
			{
				strHTMLOpt += '<option value="'+cur_pref.prefix+'">'+cur_pref.display+'</option>';
			}
		}
		_this.msg.show(''
			+ _this.tpls.buttons.replace(/%txtarea_id%/g, txtarea_id)
			+'<div style="text-align:center">'
				+'<p title="Nr wersji widoczny w logu SVN po wykonaniu Commita">Revision: '
					+'<input style="width:200px" type="text" name="'+objName+'_query" value="" onkeypress="event.which == 13 && '+objName+'.msg.msgBtns.ok.onclick()" />'
				+'</p>'
				+'<p title="Główny projekt w SVN">Projekt: '
					+'<select style="width:200px" name="'+objName+'_prefix">'+strHTMLOpt+'</select>'
				+'</p>'
			+'</div>',
			objName+'.parseAndInsertByPrefix("'+txtarea_id+'")'
		);

		// select first field
		var els = _this.msg.elMain.getElementsByTagName('input');
		els[0].focus();
	}
	
	/*!
		@brief Parse form fields and insert URL code with choosen prefix
		
		@note uses two form fileds named:
			- objName+'_query'
			- objName+'_prefix'
	*/
	this.parseAndInsertByPrefix = function(txtarea_id)
	{
		// get values
		var query_string = _this.getMsgField(objName+'_query').value;
		var prefix = _this.getMsgField(objName+'_prefix').value;
		
		replaceText('[['+prefix+'>'+query_string+']]', txtarea_id);
		/*
		// insert link
		if (name.length)
		{
			replaceText('[['+url+'|'+name+']]', txtarea_id);
		}
		else
		{
			replaceText('[['+url+']]', txtarea_id);
		}
		*/
	}
	
	/*!
		@brief Gets a field for current message/form
		
		getElementsByName but for current message (form).
	*/
	this.getMsgField = function(field_name)
	{
		var arrInputNames = ['input', 'select', 'textarea'];
		for (var ei in arrInputNames)
		{
			var els = _this.msg.elMain.getElementsByTagName(arrInputNames[ei]);
			for (var i=0; i<els.length; i++)
			{
				if (els[i].getAttribute('name')==field_name)
				{
					return els[i];
				}
			}
		}
	}
	
	/*!
		@brief Shows simple e-mail form (message)
	*/
	this.insertEmail = function (txtarea_id)
	{
		var txtarea = document.getElementById(txtarea_id)
		var txt = sel_t.getSelStr (txtarea, false);

		// show form
		_this.msg.show(''
			+'<div style="text-align:center">'
				+'<p>Adres e-mail: <input type="text" name="'+objName+'_mail" value="'+txt+'" onkeypress="event.which == 13 && '+objName+'.msg.msgBtns.ok.onclick()" />'
			+'</div>',
			'replaceText("[["+'+objName+'.getMsgField("'+objName+'_mail").value+"]]", "'+txtarea_id+'")'
		);
		// select first field
		var els = _this.msg.elMain.getElementsByTagName('input');
		els[0].focus();
	}

	/*!
		@brief Inserts insert templates in given parent element
	*/
	this.insertTemplates = function(elTplsParent, txtarea_id)
	{
		var strHtml = '';
		for (var i=0; i<_this.arrInsertTpls.length; i++)
		{
			var tpl = _this.arrInsertTpls[i];

			// insert HTML straightaway (if availble)
			if (typeof(tpl.strHtml)!='undefined')
			{
				strHtml += tpl.strHtml;
				continue;
			}

			// ignore empty
			if (typeof(tpl.strText)=='undefined')
			{
				continue;
			}

			// prepare standard
			var strPreText = '';
			var strPostText = '';
			
			var reCursor = /^(.*?)\$\|\$(.+)/;
			if (tpl.strText.search(reCursor)>=0)
			{
				strPreText = tpl.strText.replace(reCursor, '$1');
				strPostText = tpl.strText.replace(reCursor, '$2');
			}
			else
			{
				strPreText = tpl.strText;
			}
			
			strHtml += ''
				+'<a title="'+tpl.strHint+'" tabindex="-1" href="javascript:surroundText(\''+strPreText+'\', \''+strPostText+'\', \''+txtarea_id+'\')">'
					+tpl.strLabel
				+'</a>'
			;
		}
		
		elTplsParent.innerHTML = strHtml;	// replaces content (intentional!)
	}

	/*!
		@brief Map (parse) internal paths to external URLs
	*/
	this.mapInternalToExternal = function (txtarea_id)
	{
		var txtarea = document.getElementById(txtarea_id)
		var txt = sel_t.getSelStr (txtarea, false);
		
		if (txt.length<1)
		{
			alert('Zaznacz tekst z wewnętrzną ścieżką (z Serv)');
		}
		
		// flip slashes
		txt = txt.replace(/\\/g, '/');

		for (var i=0; i<_this.arrInternalToExternal.length; i++)
		{
			var tpl = _this.arrInternalToExternal[i];
			if (typeof(tpl.reInter)=='undefined' ||  typeof(tpl.strExt)=='undefined')
			{
				continue;
			}
			
			// pattern found?
			var re = new RegExp(tpl.reInter, 'ig');
			if (txt.search(re)>=0)
			{
				txt = txt.replace(re, tpl.strExt);
				if (txt.search(/\nhttps?:\/\//)>5)	// more then one link?
				{
					txt = txt.replace(/(^|\n)(https?):\/\//g, '\n  * $1://');
				}
				// escape links
				txt = txt
					.replace(/(https?:\/\/)(.+)/g, function(a, pre, body)
					{
						body = encodeURIComponent(body)
							// unescape URI parts
							.replace(/%3A/g, ':')
							.replace(/%2F/g, '/')
							//.replace(/\+/g, '%20')
							// escape some extra breakable URI parts
							.replace(/[\(\)\[\]\{\},]/ig, function(a) {return '%'+a.charCodeAt(0).toString(16)})
						;
						return pre + body;
					})
				;
				replaceText(txt, txtarea_id);
				break;
			}
		}
	}
}

/*
	Init obj
*/
var nuxbar = new cNuxbar('nuxbar');
smpAddEvent(window, 'load', function()
{
	nuxbar.init();
});
