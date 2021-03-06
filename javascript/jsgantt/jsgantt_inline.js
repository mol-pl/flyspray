﻿//
// Global gant object (needed for jsgantt)
//
var oJSGant;

//
// Global gant loader object
//
var oJSGantInline = {
	//! @note Some settings also in this.init
	conf : {
		elGantDivID : 'GanttChartInline',	// gant element (a link should be added to it)
		intNamesWidth : 500,			// names width
		strDefaultViewFormat : 'day',		// ("day","week","month","quarter")
		strDateInputFormat : 'Y-m-d',		// date format of the input
		strDateDisplayFormat : 'Y-m-d',		// basic date format
		oDateDisplayFormatCaptions : {		// headers date formats
			'day' : {
				'from' : 'd.m',
				'to' : ' - d.m.y'
			},
			'week' : {
				'upper' : 'Y',
				'lower' : 'm/d'
			},
			'month' : {
				'upper' : 'Y',
				'lower' : 'M'
			},
			'quarter' : {
				'upper' : 'Y',
				'lower' : '"'+JSGantt.lang['quarter-short']+'" q'
			}
		},
		'':''
	},
	lang : JSGantt.lang
};

//
// Standard error handling
//
oJSGantInline.displayError = function(strMsg)
{
	var nel = document.createElement('p');
	nel.className = "gantt_error";
	nel.appendChild(document.createTextNode(strMsg));
	this.elGantDiv.appendChild(nel);
}

//
// Init gantt
//
oJSGantInline.init = function()
{
	var elGantDiv = document.getElementById(this.conf.elGantDivID);
	if (!elGantDiv)
	{
		return;
	}
	this.elGantDiv = elGantDiv;
	
	// setup
	oJSGant = new JSGantt.GanttChart('oJSGant', elGantDiv, this.conf.strDefaultViewFormat);
	oJSGant.setDateInputFormat (this.conf.strDateInputFormat);
	oJSGant.setDateDisplayFormat (this.conf.strDateDisplayFormat);
	oJSGant.setDateDisplayFormatCaptions (this.conf.oDateDisplayFormatCaptions);
	
	oJSGant.setShowRes(0); // Show/Hide Responsible (0/1)
	oJSGant.setShowDur(0); // Show/Hide Duration (0/1)
	oJSGant.setShowComp(0); // Show/Hide % Complete(0/1)
	oJSGant.setShowStartDate(0);
	oJSGant.setShowEndDate(0);
	oJSGant.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
}

//
// Draw diagram (call after tasks are added)
//
oJSGantInline.draw = function()
{
	if (oJSGant)
	{
		oJSGant.Draw(this.conf.intNamesWidth);	
		oJSGant.DrawDependencies();
	}
	else
	{
		this.displayError(this.lang['unexpected-error']);
	}
}

//
// Re-draw Gantt with given code
//
oJSGantInline.redraw = function(strTasksXML)
{
	oJSGant.ClearTasksData();                    // clear
	JSGantt.parseXMLCode (strTasksXML, oJSGant); // push new data
	oJSGant.Draw(this.conf.intNamesWidth);       // draw tasks
	oJSGant.DrawDependencies();                  // draw dependencies lines
}

//
// Loader init
//
//oJSGantInline.init();
//addOnloadHook(function() {oJSGantInline.load()});