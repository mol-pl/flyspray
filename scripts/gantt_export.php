<?php
  /*********************************************************\
  | Show export for Gantt								   |
  | ~~~~~~~~~~~~~~~~~~~~~								   |
  \*********************************************************/

if (!defined('IN_FS')) {
	die('Do not access this file directly.');
}

$page->setTitle($fs->prefs['page_title'] . "Gantt");

require_once('includes/gantt_export.inc.php');

//
// Setup ids list
// @warning $ids are used all over the place!

//
// parse freedays
//$freedays = Get::val('freedays');

//
// parse task ids list for SQL safety
$ids = ParseIdsCSVForSafety(Get::val('ids'));
$orderType = Get::val('order_type');

//
// tasks closed by version
$closedby_version = Get::val('due');
if (!empty($closedby_version))
{
	// allow array (due[]=123&due[]=567)
	$closedby_versions = !is_array($closedby_version) ? intval($closedby_version) : ParseIdsCSVForSafety(implode(",", $closedby_version));
	// just getting ids
	$extra_ids = $db->Query(
					'SELECT task_id
						FROM {tasks} t
						WHERE t.closedby_version IN ('.$closedby_versions.')'
					);
	$extra_ids = $db->fetchAllArray($extra_ids);
	foreach ($extra_ids as $r)
	{
		$ids .= ',' . $r['task_id'];
	}
	$ids = trim($ids, ',');
	unset ($extra_ids);
}

//
// exclude some tasks as requested
$excluded_ids = ParseIdsCSVForSafety(Get::val('exclude_ids'));
if (!empty($excluded_ids))
{
	$excluded_ids = explode(',', $excluded_ids);
	$ids = explode(',', $ids);
	$ids = array_diff($ids, $excluded_ids);
	$ids = implode(',', $ids);
}

//
// Get tasks data
//
$tasks = array();
//define('DEBUG_ALL_SQL', 1);
if (!empty($ids))
{
	// std
	$columns = 'task_id, item_summary, item_status, detailed_desc, task_severity, task_type, is_closed, percent_complete';
	// for auth
	$columns .= ', mark_private, opened_by, task_token, t.project_id';
	// assigned (Postgre SQL only!)
	$columns .= ', array_to_string (array(select cast(u.user_id as varchar) || \'.\' || u.real_name from flyspray_assigned ass LEFT JOIN flyspray_users u ON ass.user_id = u.user_id WHERE t.task_id = ass.task_id), \',\') AS assigned_to_name';
	// status text
	$columns .= ', (SELECT lst.status_name FROM {list_status} lst WHERE t.item_status = lst.status_id) AS status_name';
	
	if ($orderType == 'severity-first')
	{
		$order = 't.task_severity DESC, t.task_priority DESC, t.task_id ASC';
	}
	else
	{
		$order = 't.task_priority DESC, t.task_severity DESC, t.task_id ASC';
	}

	// get data
	$tasks = $db->Query(
					'SELECT '.$columns.'
						FROM {tasks} t
						LEFT JOIN {cache} ca ON (cast(t.task_id as varchar) = ca.topic AND ca.type = \'rota\' AND t.last_edited_time <= ca.last_updated)
						WHERE t.task_id  IN ('.$ids.')
						ORDER BY '.$order.'
					'
					);
					// without t.item_status DESC because some will be marked New and some Assigned
	$tasks = $db->fetchAllArray($tasks);
}

//
// Set single asignee if forced by parameter
//
$forcedDeveloper= Get::val('forced_dev');
if (!empty($forcedDeveloper))
{
	for ($i=0; $i < count($tasks); $i++)
	{
		if (intval($tasks[$i]['percent_complete']) > 0 
			&& intval($tasks[$i]['item_status']) !== STATUS_UNCONFIRMED) {
			$tasks[$i]['assigned_to_name'] = "0." . $forcedDeveloper;
		}
	}
}


//
// Group tasks by asigned users
//
function addTaskToGroup($gr_name, $task)
{
	global $tasks_in_groups, $task_groups;
	
	if (!isset($tasks_in_groups[$gr_name]))
	{
		$tasks_in_groups[$gr_name] = array();
		$task_groups[] = $gr_name;
	}
	$tasks_in_groups[$gr_name][] = $task;
}

$tasks_in_groups = array();
$task_groups = array();
if (!empty($tasks))
{
	foreach($tasks as &$t)
	{
		$assigned_name = empty($t['assigned_to_name']) ? array('0.?') : explode(',', $t['assigned_to_name']);
		$t['assigned_count'] = count($assigned_name);
		foreach($assigned_name as $i=>$name)
		{
			$fake_task = array_merge(array(), $t);
			$name = explode(".", $name);
			$fake_task['GUID'] = $fake_task['task_id'].'.'.$name[0];	// unique ID for task + assignee
			$fake_task['assigned_to_name'] = $name[1];
			addTaskToGroup($name[1], $fake_task);
		}
	}
	unset($tasks);
}

//
// Set base gantt date for tasks (from user input)
//
$gantt_base_dt = Get::val('gantt_base_date');
if (empty($gantt_base_dt))
{
	$gantt_base_dt = strtotime(date("Y-m-d"));
}
else
{
	$gantt_base_dt = strtotime($gantt_base_dt);
}

//
// Prepare additional values
//
foreach ($tasks_in_groups as $k=>&$tasks_)
{
	$task_start_dt = $gantt_base_dt;	// auto order
	foreach ($tasks_ as &$task)
	{
		//
		// dependcies (only for marked)
		$check_deps = $db->Query('SELECT t.task_id, t.is_closed, d.depend_id
									  FROM  {dependencies} d
								 LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
									 WHERE  d.task_id = ? AND (t.task_id  IN ('.$ids.') OR t.is_closed=0)', array($task['task_id']));
		$check_deps = $db->fetchAllArray($check_deps);
		$task['depend'] = '';
		if (!empty($check_deps))
		{
			$task['depend'] = array();
			foreach($check_deps as $dep)
			{
				$task['depend'][] = $dep['task_id'];
			}
			$task['depend'] = implode(',', $task['depend']);
		}
		
		//
		// open?
		$task['is_open'] = $task['is_closed'] ? '0' : '1';

		//
		// calculate intervals
		$intervals = GetIntervalsFromTask($task);

		//
		// add interval info to desc
		if (!empty($intervals['std']))
		{
			$task['item_summary'] .= " ({$intervals['raw']})";
		}
		else
		{
			$task['item_summary'] = "(?) {$task['item_summary']}";
		}
		
		//
		// calculate end date (might change start date)
		if (empty($intervals['std']))
		{
			$default_days = $conf['gantt']['default_days'];
			$intervals['std'] = ' +'.$default_days.' day';	// default interval
		}
		$task_end_dt = WorkTaskEndDT ($task_start_dt, $intervals['std'], $task['assigned_to_name']);

		//
		// add start and end date
		$task['start_date'] = date("Y-m-d", $task_start_dt);
		$task['end_date'] = date("Y-m-d", $task_end_dt);
		
		//
		// next task start dt
		$task_start_dt = WorkTaskNextStartDT ($task_end_dt);
	}
}

//
// Flatten task groups
//
$tasks = array();
foreach($tasks_in_groups as &$ts)
{
	foreach($ts as &$t)
	{
		$tasks[] = $t;
	}
}
unset($tasks_in_groups);

//
// Setup template
//
$page = new FSTpl;
//$gantt_xml_url = $baseurl.'index.php?do=gantt_export&amp;mode=text&amp;addlinks=1&amp;ids='.$ids.'&amp;gantt_base_date='.Get::val('gantt_base_date').'&amp;freedays='.Get::val('freedays');
$gantt_xml_url = $baseurl.'index.php?mode=text&amp;addlinks=1&amp;'.$_SERVER["QUERY_STRING"];


$gantt_addlinks = Get::val('addlinks', 0);

$page->uses('tasks', 'page', 'gantt_xml_url', 'gantt_addlinks', 'task_groups');
if (Get::val('mode')=='text')
{
	header('Content-Type: text/plain; charset=UTF-8');
	$page->display('gantt.text.tpl');
}
else
{
	$page->display('gantt.view.tpl');
}
exit;

?>
