<?php

  /*************************************************************\
  | Details a task (and edit it)                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~                                |
  | This script displays task details when in view mode,        |
  | and allows the user to edit task details when in edit mode. |
  | It also shows comments, attachments, notifications etc.     |
  \*************************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$task_id = Req::num('task_id');

if ( !($task_details = Flyspray::GetTaskDetails($task_id)) ) {
    Flyspray::show_error(10);
}
if (!$user->can_view_task($task_details)) {
    Flyspray::show_error( $user->isAnon() ? 102 : 101);
}

require_once(BASEDIR . '/includes/events.inc.php');

$page->uses('task_details');

// Nux: prepare versions info [START]
$versions_map = Flyspray::listVersions();
//$page->assign('versions_map', $versions_map);
function versionMap($version_id) {
	global $versions_map;
	$version_tense_map = array(1=>L('past'), 2=>L('present'), 3=>L('future'));
	if (isset($versions_map[$version_id])) {
		$version = $versions_map[$version_id];
		$version['tense_name'] = $version_tense_map[$version['version_tense']];
		return $version;
	}
	return false;
}
$reported_version = versionMap($task_details['product_version']);
$due_in_version = versionMap($task_details['closedby_version']);
$page->uses('reported_version', 'due_in_version');
// Nux: prepare versions info [END]

// Send user variables to the template
$page->assign('assigned_users', $task_details['assigned_to']);
$page->assign('old_assigned', implode(' ', $task_details['assigned_to']));

$page->assign('tags', $proj->listGrouppedTags());

$page->setTitle($task_details['project_title'] . sprintf(' - '.FS_PREFIX_CODE.'#%d : %s', $task_details['task_id'], $task_details['item_summary']));

if (Get::val('edit_readonly')) {
    $userlist = Backend::get_user_list($task_id);
    $page->assign('userlist', $userlist);
    $page->pushTpl('details.edit.readonly.tpl');
}
else if ((Get::val('edit') || (Post::has('item_summary') && !isset($_SESSION['SUCCESS']))) && $user->can_edit_task($task_details)) {
    $userlist = Backend::get_user_list($task_id);
    $page->assign('userlist', $userlist);
    $page->pushTpl('details.edit.tpl');
}
else {
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
    $open_deps    = $db->Query('SELECT  COUNT(*) - SUM(is_closed)
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?', array($task_id));

    $watching     =  $db->Query('SELECT  COUNT(*)
                                   FROM  {notifications}
                                  WHERE  task_id = ?  AND user_id = ?',
                                  array($task_id, $user->id));

    // Check if task has been reopened some time
    $reopened     =  $db->Query('SELECT  COUNT(*)
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

    $page->assign('prev_id',   $prev_id);
    $page->assign('next_id',   $next_id);
    $page->assign('task_text', $task_text);
    $page->assign('deps',      $db->fetchAllArray($check_deps));
    $page->assign('parent',    $db->fetchAllArray($parent));
    $page->assign('blocks',    $db->fetchAllArray($check_blocks));
    $page->assign('votes',    $db->fetchAllArray($get_votes));
    $page->assign('penreqs',   $db->fetchAllArray($get_pending));
    $page->assign('d_open',    $db->fetchOne($open_deps));
    $page->assign('watched',   $db->fetchOne($watching));
    $page->assign('reopened',  $db->fetchOne($reopened));
    $page->pushTpl('details.view.tpl');

    ////////////////////////////
    // tabbed area

    // Comments + cache
    $sql = $db->Query('  SELECT * FROM {comments} c
                      LEFT JOIN {cache} ca ON (cast(c.comment_id as varchar) = ca.topic AND ca.type = ? AND c.last_edited_time <= ca.last_updated)
                          WHERE task_id = ?
                       ORDER BY date_added ASC',
                           array('comm', $task_id));

    $page->assign('comments', $db->fetchAllArray($sql));

    // Comment events
    $sql = get_events($task_id, ' AND (event_type = 3 OR event_type = 14)');
    $comment_changes = array();
    while ($row = $db->FetchRow($sql)) {
        $comment_changes[$row['event_date']][] = $row;
    }
    $page->assign('comment_changes', $comment_changes);

    // Comment attachments
    $attachments = array();
    $sql = $db->Query('SELECT *
                         FROM {attachments} a, {comments} c
                        WHERE c.task_id = ? AND a.comment_id = c.comment_id',
                       array($task_id));
    while ($row = $db->FetchRow($sql)) {
        $attachments[$row['comment_id']][] = $row;
    }
    $page->assign('comment_attachments', $attachments);

    // Relations, notifications and reminders
    $sql = $db->Query('SELECT  t.*, r.*, s.status_name, res.resolution_name
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON (r.related_task = t.task_id AND r.this_task = ? OR r.this_task = t.task_id AND r.related_task = ?)
                    LEFT JOIN  {list_status} s ON t.item_status = s.status_id
                    LEFT JOIN  {list_resolution} res ON t.resolution_reason = res.resolution_id
                        WHERE  t.task_id is NOT NULL AND is_duplicate = 0 AND ( t.mark_private = 0 OR ? = 1 )
                     ORDER BY  t.task_id ASC',
            array($task_id, $task_id, $user->perms('manage_project')));
    $page->assign('related', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  t.*, r.*, s.status_name, res.resolution_name
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                    LEFT JOIN  {list_status} s ON t.item_status = s.status_id
                    LEFT JOIN  {list_resolution} res ON t.resolution_reason = res.resolution_id
                        WHERE  is_duplicate = 1 AND r.related_task = ?
                     ORDER BY  t.task_id ASC',
                      array($task_id));
    $page->assign('duplicates', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  *
                         FROM  {notifications} n
                    LEFT JOIN  {users} u ON n.user_id = u.user_id
                        WHERE  n.task_id = ?', array($task_id));
    $page->assign('notifications', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  *
                         FROM  {reminders} r
                    LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                        WHERE  task_id = ?
                     ORDER BY  reminder_id', array($task_id));
    $page->assign('reminders', $db->fetchAllArray($sql));


    $page->pushTpl('details.tabs.tpl');

    if ($user->perms('view_comments') || $proj->prefs['others_view'] || ($user->isAnon() && $task_details['task_token'] && Get::val('task_token') == $task_details['task_token'])) {
        $page->pushTpl('details.tabs.comment.tpl');
    }

    $page->pushTpl('details.tabs.related.tpl');

    if ($user->perms('manage_project') || $user->perms('edit_assignments')) {
        $page->pushTpl('details.tabs.notifs.tpl');
        $page->pushTpl('details.tabs.remind.tpl');
    }

    $page->pushTpl('details.tabs.history.tpl');
}
?>
