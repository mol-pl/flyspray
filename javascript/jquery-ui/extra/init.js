/**
 * Init jQuery UI components in Flyspray.
 * 
 * See also: `javascript\jquery-ext.js`.
 */
(function ($) {

	/**
	 * Init task add/edit selects (comboboxes and dropdown menus).
	 */
	$(function(){
		$('#taskfields select')
			.not( "#rassigned_to" )
			.not( "#tasktype" )
			.not( "#category" )
			.not( "#severity" )
			.not( "#priority" )
			.not( "#percent" )
			.combobox()
		;
		$('#taskfields select')
			.filter( "#tasktype, #severity, #priority, #percent" )
			.selectmenu()
		;

		// special formatting for categories
		$('#taskfields select#category')
			.combobox({
				classes: {
					'ui-autocomplete' : 'select-categories',
				},
				formatter: function(item) {
					var html = item.label
						.replace(/\*\*\s*(.+?)\s*\*\*/g, '<div class="topcat"><b>$1</b></div>')
						.replace(/((.+) → (.+))/, '<div class="subcat">$3<br><small>$1</small></div>')
					;
					return html;
				}
			})
		;
	});

})(jQuery);