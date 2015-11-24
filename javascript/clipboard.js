var clipboardHelper = {
	/**
		Copy HTML from any element to system clipboard.
		
		Should work on Firefox 41+ and possibly Chrome and IE.
		
		@param source Selector string or an Element object that contains HTML to be copied.
	*/
	copyHtml : function (source) {
		if (typeof source === 'string') {
			source = document.querySelector(source);
		}
		// create an editable DIV and append the HTML content you want copied
		var editableDiv = document.createElement("div");
		editableDiv.contentEditable = true;
		editableDiv.style.cssText = 'position: absolute; top: 0px; left: 0';
		editableDiv.innerHTML = source.innerHTML;
		document.body.appendChild(editableDiv);
		
		// select the editable content and copy it to the clipboard
		this._selectElementContent(editableDiv);
		document.execCommand("copy");
		
		// remove element
		document.body.removeChild(editableDiv);
	},
	_selectElementContent : function (element) {
		var range, selection;
		if (document.body.createTextRange) {
			range = document.body.createTextRange();
			range.moveToElementText(element);
			range.select();
		} else if (window.getSelection) {
			selection = window.getSelection();
			range = document.createRange();
			range.selectNodeContents(element);
			selection.removeAllRanges();
			selection.addRange(range);
		}
		return range;
	},
}