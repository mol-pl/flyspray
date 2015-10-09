/* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- *\
	Some helper functions
\* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- */

/**
	Prepare form
*/
$(function() {
	$( "input.datepicker" ).datepicker({ dateFormat: 'yy-mm-dd' });
});

/**
	Append dates from \a parent to element described in "data-for"
	
	@param parent Parent of at least two inputs with start and end date to be added
*/
function appendDates(parent)
{
	var id = parent.getAttribute('data-for');
	var el = document.getElementById(id);
	var inputs = parent.getElementsByTagName('input');
	if (el && inputs.length>=2)
	{
		if (el.value.length>0)
		{
			el.value += ',';
		}
		el.value += inputs[0].value;
		if (inputs[1].value.length>0 && inputs[1].value!=inputs[0].value)
		{
			el.value += '::'+inputs[1].value;
		}
	}
}

/**
	Opens date add dialog and sets it's "data-for"
	
	@param dialogElementId Id of a dialog element
	@param forElementId Id of an element for which to open dialog
*/
function dateaddOpen(dialogElementId, forElementId)
{
	var elDialog = document.getElementById(dialogElementId);
	var el = document.getElementById(forElementId);
	if (elDialog && el)
	{
		var prevElementId = elDialog.getAttribute('data-for');
		elDialog.setAttribute('data-for', forElementId);
		el.parentNode.appendChild(elDialog);
		var prevDisplay = elDialog.style.display;
		elDialog.style.display = (prevDisplay=="none"||prevElementId!=forElementId) ? "block" : "none";
	}
}