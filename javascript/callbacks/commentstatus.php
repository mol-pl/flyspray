<?php
/*
    This script changes status of comments.
    returns OK if a change was done.
    Checks `add_comments` permissions.
*/

define('IN_FS', true);

header('Content-Type: application/json; charset=utf-8');

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

// Initialise user
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->check_account_ok();
}

// Init basic user variables
$comment_id = Get::num('comment_id');
$project_id = Get::num('project_id');
$done = Get::num('done');

// Check permissions
if (!($user->perms('add_comments', $project_id))) {
	http_response_code(403);
	die(json_encode(['error' => 'Forbidden']));
}

// Update
$result = $db->Query('UPDATE {comments} SET done = ? WHERE comment_id = ?', array($done, $comment_id));
if ($result === false) {
	http_response_code(500);
	die(json_encode(['error' => 'Database error']));
}
if ($db->affectedRows()) {
	http_response_code(200);
	die(json_encode(['status' => 'OK']));
}

// Check what is wrong
$result = $db->Query('SELECT COUNT(*) FROM {comments} WHERE comment_id = ?', array($comment_id));
list($count) = $db->fetchRow($result);
if ($count == 0) {
	http_response_code(404);
	die(json_encode(['error' => 'Comment not found']));
}

http_response_code(200);
die(json_encode(['status' => 'OK', 'info'=>'already set']));

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
