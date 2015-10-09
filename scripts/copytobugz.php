<?php

  /********************************************************\
  | Task Creation                                          |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$task_id = Req::num('task_id');

if ( !($task_details = Flyspray::GetTaskDetails($task_id)) ) {
    Flyspray::show_error(10);
}
$read_only_mode = false;	// source frame mode (mostly, but not only, for closed tasks)
if (!$user->can_edit_task($task_details)) {
	$read_only_mode = true;
	if (!$user->can_view_task($task_details)) {
		Flyspray::show_error( $user->isAnon() ? 102 : 101);
	}
}

// project id translation
$src_proj_id = $dest_proj_id = $proj->id;
if (!empty($conf['formcopy']['serialized_project_translate']))
{
	$arr_trans = @unserialize($conf['formcopy']['serialized_project_translate']);
	if (!empty($arr_trans) && isset($arr_trans[$src_proj_id]))
	{
		$dest_proj_id = $arr_trans[$src_proj_id];
	}
}

//
// prepare some data
if ($read_only_mode)
	$source_url = "index.php?do=details&task_id={$task_id}&edit_readonly=yep";
else
	$source_url = "index.php?do=details&task_id={$task_id}&edit=yep";
$dest_url = rtrim($conf['formcopy']['dest_flyspray_base_url'], '/').'/index.php?do=newtask&project='.$dest_proj_id;
$postman['strDestFrameBaseUrl'] = preg_replace('#(http://.+?)/.*#', '$1', $dest_url);

//
// start a new and display page
$page = new FSTpl;
$page->uses('data', 'page');

$page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('copytask'));

$page->assign('source_url', $source_url);
$page->assign('dest_url', $dest_url);
$page->assign('postman', $postman);

$page->display('copytobugz.tpl');
exit;
//$page->pushTpl('copytobugz.tpl');

?>
