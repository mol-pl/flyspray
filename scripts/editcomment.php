<?php

  /************************************\
  | Edit comment                       |
  | ~~~~~~~~~~~~                       |
  | This script allows users           |
  | to edit comments attached to tasks |
  \************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$sql = $db->Query("SELECT  c.*, u.real_name
                     FROM  {comments} c
               LEFT JOIN  {users}    u ON c.user_id = u.user_id
                    WHERE  comment_id = ? AND task_id = ?",
                    array(Get::num('id', 0), Get::num('task_id', 0)));

$page->assign('comment', $comment = $db->FetchRow($sql));

if (!$user->can_edit_comment($comment)) {
    Flyspray::show_error(11);
}

// Nux: Allow status and assigment changes upon editing comments
$task_id = Req::num('task_id');

if ( !($task_details = Flyspray::GetTaskDetails($task_id)) ) {
    Flyspray::show_error(10);
}
if (!$user->can_view_task($task_details)) {
    Flyspray::show_error( $user->isAnon() ? 102 : 101);
}

$page->uses('task_details');

// Send user variables to the template
$page->assign('assigned_users', $task_details['assigned_to']);
$page->assign('old_assigned', implode(' ', $task_details['assigned_to']));

$userlist = Backend::get_user_list($task_id);
$page->assign('userlist', $userlist);
// Nux: END

$page->pushTpl('editcomment.tpl');

?>
