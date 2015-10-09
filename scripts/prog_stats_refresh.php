<?php // ś
  /*********************************************************\
  | Show simple stats                                       |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

/* permission stuff */
if ($user->isAnon())
{
	die('Musisz się najpierw zalogować!');
}
if (!$user->perms('open_new_tasks'))
{
	die('Brak uprawnień!');
}

// title
$page->setTitle($fs->prefs['page_title'] . 'Statystyki');


require_once(dirname(__FILE__).'/../includes/_moje_fun.php');

//
// test query
//
// tylko błędy  AND wersje pokazywane na liście (w domyśle - te nie pokazywane nie są wydawane) AND niezamknięte w tej samej wersji
// task_type=1  AND v.show_in_list=1 AND v.version_tense = 1                                    AND t.product_version!=t.closedby_version
// version_tense = 1-przeszła, 2-obecna
$result = $db->Query('SELECT project_title as "Projekt", version_name as "Wersja", COUNT(*) as "L. błędów"
FROM
	flyspray_tasks t
	LEFT JOIN flyspray_list_version v ON t.product_version=v.version_id
	LEFT JOIN flyspray_projects p ON t.project_id=p.project_id
WHERE task_type=1 AND v.show_in_list=1 AND v.version_tense IN (1,2) AND t.product_version!=t.closedby_version
GROUP BY project_title, version_name, list_position
ORDER BY project_title, list_position');
$stats = $db->FetchAllArray($result);

/**/
$cachefile = sprintf('%s/%s', FS_CACHE_DIR, 'prog_stats_cache.php');
file_put_contents($cachefile, "<?php\n\$stats = ".var_export($stats, true).";\n?>");
/**/

/**/
$page->pushTpl('stats_refresh.smp.tpl');
/**/


?>