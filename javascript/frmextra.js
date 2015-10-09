//
// Paste functions (note - frmcopy needed)
//
// szablony przy edycji opisów są w plugins\dokuwiki\bar_enhance.js
var nuPaste = {
	Vulcan : function ()
	{
		var arrData = [
			{'name': 'item_summary_tit', 'data': '(skopiuj z maila, pamiętaj o VIDS)'},
			{'name': 'anon_email', 'data': 'serwis@vulcan.pl'},
			{'name': 'product_category', 'data': {text : 'Konwersja'}},
			{'name': 'itemsummary_lic', 'data': '00001'}
		];
		frmcp.setFormData(arrData);
	}
}
