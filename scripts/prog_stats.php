<?php // ś
  /*********************************************************\
  | Show simple stats                                       |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}


/* permission stuff *
if (!$user->perms('modify_all_tasks'))
{
	die('Brak uprawnień');
}
/**/

// title
$page->setTitle($fs->prefs['page_title'] . 'Statystyki');


require_once(dirname(__FILE__).'/../includes/_moje_fun.php');

//
// get stats
//
$stats = array();
$cachefile = sprintf('%s/%s', FS_CACHE_DIR, 'prog_stats_cache.php');
if (file_exists($cachefile))
{
	include_once($cachefile);	// $tasksOurTime [<task_id>] = duration
}

//
// Show
//
//$page->uses('user');
$page->assign('stats', $stats);
$page->pushTpl('stats.smp.tpl');

?>