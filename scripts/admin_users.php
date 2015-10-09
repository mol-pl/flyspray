<?php

  /*********************************************************\
  | View a user's profile                                   |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms('is_admin')) {
    Flyspray::show_error(4);
}

// Some possibly interesting information about users
$sql = $db->Query('SELECT
	user_id, user_name, user_pass, email_address
	, notify_type, notify_own
	, tasks_perpage, register_date, time_zone
	FROM {users}
');
$page->assign('usersinfo', $db->fetchAllArray($sql));

$page->setTitle('Admin - users info');
$page->pushTpl('admin.users.info.tpl');


?>
