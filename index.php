<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/
define('IN_FS', true);

// Nux: Fix links like: index.php?do=details&amp;task_id=1555
if (strpos($_SERVER['QUERY_STRING'], '&amp;')) {
	$url = $_SERVER['SCRIPT_NAME'] . '?';
	$url .= preg_replace('#(&amp;|&)+#', '&', $_SERVER['QUERY_STRING']);
	header("Location: $url");
	die();
}

require_once(dirname(__FILE__).'/header.php');

// Background daemon that does scheduled reminders
if ($conf['general']['reminder_daemon'] == '2') {
    Flyspray::startReminderDaemon();
}

// Get available do-modes
$modes = str_replace('.php', '', array_map('basename', glob_compat(BASEDIR ."/scripts/*.php")));

$do = Req::enum('do', $modes, $proj->prefs['default_entry']);

if ($do == 'admin' && Req::has('switch') && Req::val('project') != '0') {
    $do = 'pm';
} elseif ($do == 'pm' && Req::has('switch') && Req::val('project') == '0') {
    $do = 'admin';
} elseif (Req::has('show') || (Req::has('switch') && ($do == 'details'))) {
    $do = 'index';
}



/* permission stuff */
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'), $proj);
    $user->check_account_ok();
    $user->save_search($do);
} else {
    $user = new User(0, $proj);
}
// Nux: lock for anons -- init
$anon_lock_active = false;
if ($user->isAnon()) {
	if (!isset($conf['general']['anon_lock'])
		|| $conf['general']['anon_lock']=='1')	// Nux: allow disabling duplicate check
	{
		$anon_lock_active = true;
	}
}

// Nux: domain-only auth -- init
$domain_auth_only = false;
if (isset($conf['general']['domain_auth_only']) && $conf['general']['domain_auth_only']=='1') {
	$domain_auth_only = true;
}

if (Get::val('getfile')) {
	// Nux: lock for anons
	if ($anon_lock_active) {
        Flyspray::show_error(1);
        exit();
	}
	
    // If a file was requested, deliver it
    $result = $db->Query("SELECT  t.project_id,
                                  a.orig_name, a.file_name, a.file_type, t.*
                            FROM  {attachments} a
                      INNER JOIN  {tasks}       t ON a.task_id = t.task_id
                           WHERE  attachment_id = ?", array(Get::val('getfile')));
    $task = $db->FetchRow($result);
    list($proj_id, $orig_name, $file_name, $file_type) = $task;

    // Check if file exists, and user permission to access it!
    if (!is_file(BASEDIR . "/attachments/$file_name")) {
        header('HTTP/1.1 410 Gone');
        echo 'File does not exist anymore.';
        exit();
    }

    if ($user->can_view_task($task))
    {
        output_reset_rewrite_vars();
        $path = BASEDIR . "/attachments/$file_name";

		// seems to happen on some occasions and IE doesn't like empty types...
		if (empty($file_type))
		{
			$file_type = 'application/octet-stream';
		}

        header('Pragma: public');
        header("Content-type: $file_type");
		if (strpos($file_type, 'application/')===0)
		{
			header('Content-Disposition: attachment; filename="'.$orig_name.'"');
		}
		else
		{
			header('Content-Disposition: filename="'.$orig_name.'"');
		}
        header('Content-transfer-encoding: binary');
        header('Content-length: ' . filesize($path));

        readfile($path);
        exit();
    }
    else {
        Flyspray::show_error(1);
    }
    exit;
}

/*******************************************************************************/
/* Here begins the deep flyspray : html rendering                              */
/*******************************************************************************/

// make browsers back button work
header('Expires: -1');
header('Pragma: no-cache');
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');

// see http://www.w3.org/TR/html401/present/styles.html#h-14.2.1
header('Content-Style-Type: text/css');
header('Content-type: text/html; charset=utf-8');

if ($conf['general']['output_buffering'] == 'gzip' && extension_loaded('zlib'))
{
    // Start Output Buffering and gzip encoding if setting is present.
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

$page = new FSTpl();

if ($show_task = Get::val('show_task')) {
    // If someone used the 'show task' form, redirect them
    if (is_numeric($show_task)) {
        Flyspray::Redirect( CreateURL('details', $show_task) );
    } else {
        Flyspray::Redirect( $baseurl . '?string=' .  $show_task);
    }
}

if (!isset($conf['general']['check_for_duplicated_requests'])
	|| $conf['general']['check_for_duplicated_requests']=='1')	// Nux: allow disabling duplicate check
{
	if (Flyspray::requestDuplicated()) {
		// Check that this page isn't being submitted twice
		Flyspray::show_error(3);
	}
}

if ($proj->id && $user->perms('manage_project')) {
    // Find out if there are any PM requests wanting attention
    $sql = $db->Query(
            "SELECT COUNT(*) FROM {admin_requests} WHERE project_id = ? AND resolved_by = '0'",
            array($proj->id));
    list($count) = $db->fetchRow($sql);

    $page->assign('pm_pendingreq_num', $count);
}

// project list sorter by activity
// (with an activity divider)
$sql = $db->Query(
        'SELECT  project_id, project_title, project_is_active, others_view,
				concat(
				 case when project_is_active=1 then \'1.\'
				 else \'3.\' end
				,
                 upper(project_title)
				)  AS sort_names
           FROM  {projects}
	UNION SELECT  -1, \'——\', 0, 1, \'2.inactive\'
       ORDER BY  sort_names');

$fs->projects = array_filter($db->FetchAllArray($sql), array($user, 'can_view_project'));

// Get e-mail addresses of the admins
if ($user->isAnon() && !$fs->prefs['user_notify']) {
    $sql = $db->Query('SELECT email_address
                         FROM {users} u
                    LEFT JOIN {users_in_groups} g ON u.user_id = g.user_id
                        WHERE g.group_id = 1');
    $page->assign('admin_emails', $db->FetchAllArray($sql));
}

// default title
$page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title']);

$page->assign('do', $do);
$page->assign('anon_lock_active', $anon_lock_active);
$page->assign('domain_auth_only', $domain_auth_only);
if (!Req::val('printview', 0)) {
	$page->pushTpl('header.tpl');
} else {
	$page->pushTpl('header.printview.tpl');
}
// Nux: domain-only auth -- lock user ops
if ($domain_auth_only && in_array($do, array('authenticate', 'lostpw', 'register'))) {
	Flyspray::show_error(22);
	exit();
}

if (!$anon_lock_active || in_array($do, array('lostpw', 'register'))) {	// Nux: lock for anons -- allow lost password and registration (Note! You can disable registration in settings)
	// DB modifications?
	if (Req::has('action')) {
		require_once(BASEDIR . '/includes/modify.inc.php');
	}
}
if (!$anon_lock_active || in_array($do, array('authenticate', 'lostpw', 'register'))) {	// Nux: lock for anons -- allow login and lost password and registration (Note! You can disable registration in settings)
	if (!defined('NO_DO')) {
		require_once(BASEDIR . "/scripts/$do.php");
	}
}

$page->pushTpl('footer.tpl');
$page->setTheme($proj->prefs['theme_style']);
$page->render();

if(isset($_SESSION)) {
// remove dupe data on error, since no submission happened
    if (isset($_SESSION['ERROR']) && isset($_SESSION['requests_hash'])) {
        $currentrequest = md5(serialize($_POST));
        unset($_SESSION['requests_hash'][$currentrequest]);
    }
    unset($_SESSION['ERROR'], $_SESSION['SUCCESS']);
}

?>