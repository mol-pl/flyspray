<?php
/**
 * Basic constants/variables required for flyspray operation
 *
 * @notes be a real paranoid here.
 * @version $Id: constants.inc.php 1254 2007-05-29 11:13:19Z judas_iscariote $
 */

define('BASEDIR', dirname(dirname(__FILE__)));

// Change this line if you move flyspray.conf.php elsewhere
$conf = @parse_ini_file(Flyspray::get_config_path(), true);

// Nux: Flyspray task prefix code
define('FS_PREFIX_CODE', empty($conf['general']['fs_prefix_code']) ? 'FS' : $conf['general']['fs_prefix_code']);

// Nux start - test configuration for some sections
if(!empty($conf['general']['test_sections'])
		&& !empty($conf['general']['test_server_name'])
		&& strtolower($conf['general']['test_server_name']) == strtolower(php_uname("n")))
{
	$arrSec = explode(",", $conf['general']['test_sections']);
	foreach($arrSec as $strTestSecName)
	{
		$strSecName = preg_replace("#_test$#", '', $strTestSecName);
		$conf[$strSecName] =  $conf[$strTestSecName];
		//unset($conf[$strTestSecName]);
	}
}
// Nux end

// Gantt settings
if (empty($conf['gantt'])) $conf['gantt'] = array();
if (!isset($conf['gantt']['default_days'])) $conf['gantt']['default_days'] = 3;

// $baseurl
// htmlspecialchars because PHP_SELF is user submitted data, and can be used as an XSS vector.
if (isset($conf['general']['force_baseurl']) && $conf['general']['force_baseurl'] != '') {
    $baseurl = $conf['general']['force_baseurl'];
} else {
    if (!isset($webdir)) {
        $webdir = dirname(htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8'));
        if (substr($webdir, -9) == 'index.php') {
            $webdir = dirname($webdir);
        }
    }

    $baseurl = rtrim(Flyspray::absoluteURI($webdir),'/\\') . '/' ;
}

if(isset($conf['general']['syntax_plugin']) && preg_match('/^[a-z0-9_]+$/iD', $conf['general']['syntax_plugin'])) {

$path_to_plugin = sprintf('%s/plugins/%s/%s_constants.inc.php', BASEDIR, $conf['general']['syntax_plugin'], $conf['general']['syntax_plugin']);

    if (is_readable($path_to_plugin)) {
        include($path_to_plugin);
    }
}

define('NOTIFY_TASK_OPENED',      1);
define('NOTIFY_TASK_CHANGED',     2);
define('NOTIFY_TASK_CLOSED',      3);
define('NOTIFY_TASK_REOPENED',    4);
define('NOTIFY_DEP_ADDED',        5);
define('NOTIFY_DEP_REMOVED',      6);
define('NOTIFY_COMMENT_ADDED',    7);
define('NOTIFY_ATT_ADDED',        8);
define('NOTIFY_REL_ADDED',        9);
define('NOTIFY_OWNERSHIP',       10);
define('NOTIFY_CONFIRMATION',    11);
define('NOTIFY_PM_REQUEST',      12);
define('NOTIFY_PM_DENY_REQUEST', 13);
define('NOTIFY_NEW_ASSIGNEE',    14);
define('NOTIFY_REV_DEP',         15);
define('NOTIFY_REV_DEP_REMOVED', 16);
define('NOTIFY_ADDED_ASSIGNEES', 17);
define('NOTIFY_ANON_TASK',       18);
define('NOTIFY_PW_CHANGE',       19);
define('NOTIFY_NEW_USER',        20);

// Nux start - extra notifications for anons
define('NOTIFY_ANON_COMMENT_ADDED',	30);
define('NOTIFY_ANON_TASK_CLOSED',	31);
// Nux end

define('NOTIFY_EMAIL',            1);
define('NOTIFY_JABBER',           2);
define('NOTIFY_BOTH',             3);

define('STATUS_UNCONFIRMED',      1);
define('STATUS_NEW',              2);
define('STATUS_ASSIGNED',         3);
define('STATUS_DONE_OK',          6);

define('GET_CONTENTS', true);

// Others
define('MIN_PW_LENGTH', 5);
define('LOGIN_ATTEMPTS', 5);

define('FS_DOMAIN_HASH', md5($_SERVER['SERVER_NAME'] . BASEDIR));
//define('FS_CACHE_DIR', Flyspray::get_tmp_dir() . DIRECTORY_SEPARATOR . FS_DOMAIN_HASH);
// Nux fix - real cache dir with cookie salt hash subdir for a bit of added security
$cookie_hash = md5($conf['general']['cookiesalt']);
define('FS_CACHE_DIR', BASEDIR. DIRECTORY_SEPARATOR .'cache'. DIRECTORY_SEPARATOR . $cookie_hash);
// Nux fix:END

is_dir(FS_CACHE_DIR) || @mkdir(FS_CACHE_DIR, 0700);

// developers or advanced users only
//define('DEBUG_SQL',true);
//define('JABBER_DEBUG', true);
//define('JABBER_DEBUG_FILE''/path/to/my/debug/file');
//define('FS_MAIL_LOGFILE', BASEDIR . '/logs/maillog.txt');
?>
