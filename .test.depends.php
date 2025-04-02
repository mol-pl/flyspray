<?php
/**
 * Test scripts\depends.php via CLI.
 */
// cmd only
if(php_sapi_name() !== 'cli') {
	die('');
}
$_SERVER['WINDIR'] = "C:\Windows";
// load classes
define('IN_FS', true);
require_once(dirname(__FILE__) . '/header.php');
// define('BASEDIR', dirname(__FILE__));
// require_once(BASEDIR . '/includes/class.flyspray.php');

// test with user 1 (probably admin)
$user = new User(1, $proj);

// fake params
$_REQUEST['task_id'] = 2469;
$do = 'depends';

// setup page
$page = new FSTpl();
$page->setTitle('Test');
$page->assign('do', $do);
//$page->pushTpl('header.printview.tpl');

// do
require_once(BASEDIR . "/scripts/$do.php");

// finalize
// $page->pushTpl('footer.tpl');
$page->setTheme($proj->prefs['theme_style']);
$page->render();