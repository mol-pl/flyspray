/**
	Simple Gantt code editor
	
	Useses:
		* oLoader
		* oJSGantLoader.redraw
		* addOnloadHook?
	
	@author Maciej Jaros
	
	@see also oJSGanttEditHelper in p:\www_html\wiki\extensions\JSWikiGantt\jsgantt_edit.js
*/

/* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- *\
	The class
\* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- */

/**
	Constructor
*/
function cJSGanttEdit(strInputId)
{
	//! input element for gantt code
	//! @access private
	this.elInput = null;
	
	//! tasks XMLDocument
	//! @access private
	this.docTasks = null;

	//! Configuration
	//! @access private
	this.conf = {
		intNamesWidth : 500,			// names width
		'':''
	},
	
	//! i18n from gantt class
	//! @access private
	this.lang = JSGantt.lang;
	
	//
	// Init variables
	try
	{
		this.elInput = document.getElementById(strInputId);
	}
	catch (e)
	{
		alert(e.message);
	}
}

/**
	Get local XML string code of tasks

	@access private
	@return XML string prepared for parsing
*/
cJSGanttEdit.prototype.getLocalGanttXmlString = function()
{
	var strTasksXML = this.elInput.value;
	// remove any non-gantt code
	strTasksXML = strTasksXML.replace(/[\s\S]*(<jsgantt[^>]*>[\s\S]*?<\/jsgantt>)/, '$1');
	strTasksXML = strTasksXML.replace(/(<jsgantt[^>]*>[\s\S]*?<\/jsgantt>)[\s\S]*/, '$1');
	// fix amps
	strTasksXML = strTasksXML.replace(/&(?![a-z]+;)/g, '&amp;');

	return strTasksXML;
}

/**
	Get local data of tasks

	@access private
	@return false upon error
*/
cJSGanttEdit.prototype.loadTasks = function()
{
	var strTasksXML = this.getLocalGanttXmlString();

	// load as XML doc.
	this.docTasks = oLoader.loadXMLDocFromStr(strTasksXML);
	
	// error handling
	strTasksXML = oLoader.convertXMLDocToStr(this.docLocalTasks);
	if (strTasksXML.search(/<jsgantt[^>]*>[\s\S]*?<\/jsgantt>/)<0)
	{
		this.log (strTasksXML);
		return false;
	}
	return true;
}

/**
	Logging to console

	@access private
*/
cJSGanttEdit.prototype.log = function(strMsg)
{
	if (typeof(console) != 'undefined' && typeof(console.log) == 'function')
	{
		console.log(strMsg)
	}
};


/**
	Update chart from textarea

	@access private
*/
cJSGanttEdit.prototype.chartUpdate = function()
{
	var strTasksXML = this.getLocalGanttXmlString();
	// redraw
	//oJSGantInline.redraw(strTasksXML);
	oJSGant.ClearTasksData();                    // clear
	JSGantt.parseXMLCode (strTasksXML, oJSGant); // push new data
	oJSGant.Draw(this.conf.intNamesWidth);       // draw tasks
	oJSGant.DrawDependencies();                  // draw dependencies lines
}

/**
	Move a task and it's succesors
	
	@access public
	
	@param strBaseTaskId
		The task identification number

	@param strInterval
		The task identification number
*/
cJSGanttEdit.prototype.fuzzyMoveTask = function(strBaseTaskId, strInterval)
{
	/**
		@todo
		# Get current data (from textarea).
		# Find the task to be moved.
		# Remember the task values.
		# Moving from current task down the list do:
		## if not isSuccesors(strCurTaskId, strBaseTaskId) => break
		## else moveTask(strCurTaskId, strInterval)	// moves single task
		# Update data (to textarea).
		
		isSuccesors:
			[]      []      [ ]  
			[]      [ ]     []   
			true    true    false
			
			[]       []      []
			 []     []      [  ]
			true    false   false

			[ ]     [  ]    
			 []      []     
			true    false   

			oCurTask.dtStart = oBaseTask.dtStart && oCurTask.dtEnd = oBaseTask.dtEnd => true
			oCurTask.dtStart = oBaseTask.dtStart && oCurTask.dtEnd = oBaseTask.dtEnd => true
	*/
}


/* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- *\
	Object init
\* -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=- */
jQuery (function()
{
	window.oJSGantEdit = new cJSGanttEdit('txtinput');
});