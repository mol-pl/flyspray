<?php

  /*********************************************************************\
  | Mass close tasks											        |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~						            	|
  | Zamykanie zgłoszeń na podstawie id zgłoszenia podanego w task_id,	|
  |                                                                     |
  | UWAGA! Najpierw wyświetlany jest formularz z możliwością wyboru     |
  | powodu zamknięcia. Dopiero po zatwierdzeniu zgłoszenia są zamykane. |
  |                                                                     |
  | Zalecane jest wyłączenie powiadomień przed masowym zamykaniem.      |
  | https://pp.mol.com.pl/bugz/index.php?do=admin&area=prefs            |
  \*********************************************************************/

if (!defined('IN_FS')) {
	die('Do not access this file directly.');
}

// Req::val

$task_id = Req::num('task_id');

if ( !($task_details = Flyspray::GetTaskDetails($task_id)) ) {
	Flyspray::show_error(10);
}
// tylko admin
if (!$user->perms('is_admin')) {
	Flyspray::show_error( $user->isAnon() ? 102 : 101);
}

require_once(BASEDIR . '/includes/events.inc.php');

$page->uses('task_details');

// Send user variables to the template
$page->assign('assigned_users', $task_details['assigned_to']);
$page->assign('old_assigned', implode(' ', $task_details['assigned_to']));

$page->setTitle($task_details['project_title'] . sprintf(' - %s#%d : %s', FS_PREFIX_CODE, $task_details['task_id'], $task_details['item_summary']));

	$prev_id = $next_id = 0;

	if (isset($_SESSION['tasklist']) && ($id_list = $_SESSION['tasklist'])
			&& ($i = array_search($task_id, $id_list)) !== false)
	{
		$prev_id = isset($id_list[$i - 1]) ? $id_list[$i - 1] : '';
		$next_id = isset($id_list[$i + 1]) ? $id_list[$i + 1] : '';
	}

	// Parent categories
	$parent = $db->Query('SELECT  *
							FROM  {list_category}
						   WHERE  lft < ? AND rgt > ? AND project_id  = ? AND lft != 1
						ORDER BY  lft ASC',
						array($task_details['lft'], $task_details['rgt'], $task_details['cproj']));

	// Check for task dependencies that block closing this task
	$check_deps   = $db->Query('SELECT  t.*, s.status_name, r.resolution_name, d.depend_id
								  FROM  {dependencies} d
							 LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
							 LEFT JOIN  {list_status} s ON t.item_status = s.status_id
							 LEFT JOIN  {list_resolution} r ON t.resolution_reason = r.resolution_id
								 WHERE  d.task_id = ?', array($task_id));

	// Check for tasks that this task blocks
	$check_blocks = $db->Query('SELECT  t.*, s.status_name, r.resolution_name
								  FROM  {dependencies} d
							 LEFT JOIN  {tasks} t on d.task_id = t.task_id
							 LEFT JOIN  {list_status} s ON t.item_status = s.status_id
							 LEFT JOIN  {list_resolution} r ON t.resolution_reason = r.resolution_id
								 WHERE  d.dep_task_id = ?', array($task_id));

	// Check for pending PM requests
	$get_pending  = $db->Query("SELECT  *
								  FROM  {admin_requests}
								 WHERE  task_id = ?  AND resolved_by = 0",
								 array($task_id));

	// Get info on the dependencies again
	$open_deps	= $db->Query('SELECT  COUNT(*) - SUM(is_closed)
								  FROM  {dependencies} d
							 LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
								 WHERE  d.task_id = ?', array($task_id));

	$watching	 =  $db->Query('SELECT  COUNT(*)
								   FROM  {notifications}
								  WHERE  task_id = ?  AND user_id = ?',
								  array($task_id, $user->id));

	// Check if task has been reopened some time
	$reopened	 =  $db->Query('SELECT  COUNT(*)
								   FROM  {history}
								  WHERE  task_id = ?  AND event_type = 13',
								  array($task_id));

	// Check for cached version
	$cached = $db->Query("SELECT content, last_updated
							FROM {cache}
						   WHERE topic = ? AND type = 'task'",
						   array($task_details['task_id']));
	$cached = $db->FetchRow($cached);

	// List of votes
	$get_votes = $db->Query('SELECT u.user_id, u.user_name, u.real_name, v.date_time
							   FROM {votes} v
						  LEFT JOIN {users} u ON v.user_id = u.user_id
							   WHERE v.task_id = ?
							ORDER BY v.date_time DESC',
							array($task_id));

	if (empty($cached) || $task_details['last_edited_time'] > $cached['last_updated'] || !defined('FLYSPRAY_USE_CACHE')) {
		$task_text = TextFormatter::render($task_details['detailed_desc'], false, 'task', $task_details['task_id']);
	} else {
		$task_text = TextFormatter::render($task_details['detailed_desc'], false, 'task', $task_details['task_id'], $cached['content']);
	}

	// delete -> modify.inc.php
		/*
	if (Req::val('actiontmp')=='mass.close')
	{
		$tasks_to_close = $db->Query('SELECT  task_id, item_summary
			FROM {tasks}
			WHERE project_id=?
			AND item_status=?
			AND closedby_version=?
			AND task_type=?
			', array(
				Req::val('project_id'),
				Req::val('item_status'),
				Req::val('closedby_version'),
				Req::val('task_type'),
			)
		);
		$tasks_to_close_arr = $db->fetchAllArray($tasks_to_close);
		foreach($tasks_to_close_arr as &$task)
		{
			Backend::close_task($task['task_id'], Post::val('resolution_reason'), Post::val('closure_comment', ''), Post::val('mark100', false));
		}
	}
		*/

	//
	// Output
	//
	$page->assign('prev_id',   $prev_id);
	$page->assign('next_id',   $next_id);
	$page->assign('task_text', $task_text);
	$page->assign('deps',	  $db->fetchAllArray($check_deps));
	$page->assign('parent',	$db->fetchAllArray($parent));
	$page->assign('blocks',	$db->fetchAllArray($check_blocks));
	$page->assign('votes',	$db->fetchAllArray($get_votes));
	$page->assign('penreqs',   $db->fetchAllArray($get_pending));
	$page->assign('d_open',	$db->fetchOne($open_deps));
	$page->assign('watched',   $db->fetchOne($watching));
	$page->assign('reopened',  $db->fetchOne($reopened));
	$page->pushTpl('massclose.tpl');
?>
