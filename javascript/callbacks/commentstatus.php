<?php
/*
    This script changes status of comments.
    returns OK if a change was done.
    Checks `add_comments` permissions.
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

// Initialise user
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->check_account_ok();
}

// Init basic user variables
$task_id = Get::num('task_id');
$project_id = Get::num('project_id');
$done = Get::num('done');

// Check permissions
if (!($user->perms('add_comments', $project_id))) {
    die();
}

$sql = get_events(Get::num('task_id'), $details);
$db->Query('UPDATE {comments} SET done = ? WHERE task_id = ?', array($done, $task_id));
if ($db->affectedRows()) {
	die('OK');
}


/*
$page = new FSTpl;
$page->uses('histories', 'details');
if ($details) {
    event_description($histories[0]); // modifies global variables
    $page->assign('details_previous', $GLOBALS['details_previous']);
    $page->assign('details_new', $GLOBALS['details_new']);
}
$page->display('details.tabs.history.callback.tpl');
*/

?>
