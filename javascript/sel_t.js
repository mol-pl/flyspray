/* ------------------------------------------------------------------------ *\
	copyright:  (C) 2008-2009 Maciej Jaros (pl:User:Nux, en:User:EcceNux)
	licence:    GNU General Public License v2,
                http://opensource.org/licenses/gpl-license.php

	version:    (see below) = sel_t.version
\* ------------------------------------------------------------------------ */
var tmp_sel_t_VERSION = '1.1.0';

/* ------------------------------------------------------------------------ *\
	Class: Selection tools

	sel_t = {
		----------------------------------------------------
		 attributes
		----------------------------------------------------
		.version = version of the script
		
		----------------------------------------------------
		.getSelStr(input,nonempty)
		----------------------------------------------------
			basically returns selected string in the given input
			
			additionally if nothing is selected and nonempty is true 
			then it returns full contents of the input
			otherwise it returns an empty string
		
		----------------------------------------------------
		.setSelStr(input, str, nonempty)
		----------------------------------------------------
			basically replaces current selection in the given input with str
			
			additionally if nothing is selected and nonempty is true 
			then it replaces all of the contents of the input
			otherwise it inserts the string at the end

		----------------------------------------------------
		.qsetSelStr(input, str, nonempty)
		----------------------------------------------------
			basically replaces selection previously found with getSelStr
			
			works the same as setSelStr but is quicker
			and must be used only after calling getSelStr
			
		----------------------------------------------------
		.setSelRange(input, sel_start, sel_end)
		----------------------------------------------------
			selects the given range of text in the given input

		----------------------------------------------------
		.ScrollIntoView(input, sel_start, sel_end)
		----------------------------------------------------
			this method is used interally for Firefox to scroll selection into View
			
			for IE (if you are sure that selection is in the wanted input) you can use:
			document.selection.createRange().scrollIntoView(true)
			
	}
\* ------------------------------------------------------------------------ */
if (document.cookie.indexOf("js_sel_t_critical=1")==-1 && sel_t!=undefined && (typeof sel_t.version)!='string')
{
	alert('Błąd krytyczny - konflikt nazw!'+
		'\n\n'+
		'Jeden ze skryptów używa już nazwy "sel_t" jako nazwę zmiennej globalnej.');
	document.cookie = "js_sel_t_critical=1; path=/";
	if (document.cookie.indexOf("js_sel_t_critical=1")!=-1)
	{
		alert('Poprzedni komunikat jest wyświetlany tylko raz w ciągu sesji.'+
		'\n\n'+
		'Musisz rozwiązać konflikt nazw lub usunąć jeden ze skryptów w całości.');
	}

}
var sel_t = new Object();
sel_t.version = tmp_sel_t_VERSION;

// sel_t.focus_end=true;

sel_t.getSelStr = function (input, nonempty)
{
	sel_t.noSelection = true;
	sel_t.isWikEdOn = false;

	// check for wikEd (always choose whole area)
	if (typeof wikEdUseWikEd != 'undefined' && wikEdUseWikEd)
	{
		sel_t.isWikEdOn = true;
		WikEdUpdateTextarea();	// update before get
		return input.value;
	}
	else if (input.selectionStart != undefined)
	{
		sel_t.sel_s = input.selectionStart;
		sel_t.sel_e = input.selectionEnd;
		if (sel_t.sel_s!=sel_t.sel_e)
		{
			sel_t.noSelection = false;
			return input.value.substring(sel_t.sel_s, sel_t.sel_e);
		}
	}
	// IE...
	else if (document.selection)
	{
		sel_t.range = document.selection.createRange();
		if (sel_t.range.parentElement()==input && sel_t.range.text!='')
		{
			sel_t.noSelection = false;
			return sel_t.range.text;
		}
	}

	// other cases then above
	return (nonempty) ? input.value : '';
}

// for quick set after getSelStr (same selection)
sel_t.qsetSelStr = function (input, str, nonempty)
{
	if (sel_t.noSelection)
	{
		if (nonempty)
			input.value = str;
		else
		{
			input.value += str;
			input.scrollTop = input.scrollHeight;
		}
	}
	else if (input.selectionStart!=undefined && sel_t.sel_s!=undefined)
	{
		input.value = input.value.substring(0, sel_t.sel_s) + str + input.value.substring(sel_t.sel_e)
	}
	// IE...
	else if (document.selection && sel_t.range!=undefined)
	{
		sel_t.range.text = str;
		sel_t.range.scrollIntoView(false);// at bottom
	}
	// WikEd frame update
	if (sel_t.isWikEdOn)
	{
		WikEdUpdateFrame();
	}
}

// javascript:sel_t.setSelStr(sr$t, 'testin123', false)
// tested: fox2, fox3, IE6 wired behaviour when nothing is selected...
sel_t.setSelStr = function (input, str, nonempty)
{
	// IE
	if (document.selection)
	{
		input.focus();
		var range = document.selection.createRange();
		if (range.parentElement()==input)
		{
			if (range.text!='' || nonempty==false)
			{
				range.text = str;
				range.scrollIntoView(true); // at top
				return;
			}
		}
	}
	// fox/opera
	else if (input.selectionStart!=undefined)
	{
		var sTop=input.scrollTop;
		var sel_s = input.selectionStart;
		var sel_e = input.selectionEnd;
		if (sel_s!=sel_e)
		{
			input.value = input.value.substring(0, sel_s) + str + input.value.substring(sel_e);
			input.selectionStart=sel_s;
			input.selectionEnd=sel_s + str.length;
			input.scrollTop=sTop;
			return;
		}
		else if (sel_s==sel_e && !nonempty)
		{
			input.value = input.value.substring(0, sel_s) + str + input.value.substring(sel_e);
			input.selectionEnd=sel_s + str.length;
			input.selectionStart=input.selectionEnd;
			input.scrollTop=sTop;
			sel_t.ScrollIntoView(input, sel_s, sel_e);
			return;
		}
	}

	// other cases then above
	if (nonempty)
		input.value = str;
	else
	{
		input.value += str;
		input.scrollTop = input.scrollHeight;
	}
}

// for FX/FF
sel_t.ScrollIntoView = function(input, sel_start, sel_end)
{
	//
	// quick checks
	//
	if (sel_start<20)
	{
		input.scrollTop = 0;
		return;
	}
	var text_len = input.value.length;	// in chars
	var text_height = input.scrollHeight;	// in pixels
	if (text_len - sel_start<20)
	{
		input.scrollTop = text_height;
		return;
	}
	
	// stuff needed for scroll calculation
	var sel_row_num = 0;
	var rows_cnt = 0;

	//
	// initial calculations
	//
	var i;
	var av_cols = input.cols-5; // aproximate average number of cols for fully spanned lines (word-wrapping makes them usally less then availble)
	var lines = input.value.split("\n");
	var cur_len = 0;
	
	for (i=0; i<lines.length; i++)
	{
		var len=lines[i].length+1;	//+\n
		cur_len += len;
		//cur_len += len;
		if (!sel_row_num && sel_start<cur_len)
		{
			sel_row_num=i;
			sel_row_num+=Math.ceil((len-cur_len+sel_start)/av_cols)-1; // there might be some more rows
		}
		rows_cnt+=Math.ceil(len/av_cols); // number of rows for this (wrapped)line
	}

	//
	// scroll calculations
	//
//	sr_msg('row:'+sel_row_num);
	input.scrollTop = text_height * (sel_row_num-5)/rows_cnt;
}

//
// parts borrowed from PD code by Fred Jounters and Martin Honnen
sel_t.setSelRange = function(input, sel_start, sel_end)
{
	if (input.setSelectionRange)
	{
		input.focus();
		input.setSelectionRange(sel_start, sel_end);
		sel_t.ScrollIntoView(input, sel_start, sel_end);
	}
	else if (input.createTextRange)
	{
		var range = input.createTextRange();
		range.collapse(true);
		range.moveEnd('character', sel_end);
		range.moveStart('character', sel_start);
		range.select();
		range.scrollIntoView(true); // at top
		input.focus();
	}
}