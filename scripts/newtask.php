<?php

  /********************************************************\
  | Task Creation                                          |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->can_open_task($proj)) {
    Flyspray::show_error(15);
}

$page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('newtask'));
$page->assign('userlist', array());
$page->assign('old_assigned', '');
$page->assign('tags', $proj->listGrouppedTags());
$page->pushTpl('newtask.tpl');

?>
