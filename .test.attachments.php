<?php
/**
 * Test if attachments check is working.
*/
// cmd only
if(php_sapi_name() !== 'cli') {
	die('');
}
// load classes
define('IN_FS', true);
// require_once(dirname(__FILE__).'/header.php');
define('BASEDIR', dirname(__FILE__));
require_once dirname(__FILE__) . '/includes/class.flyspray.php';

$inis = array('120M', '80M', '40M');
$sizes = Flyspray::checkIniFileSize($inis);
$php_size_limit = (int)round((min($sizes)/1024/1024), 1);
assert($php_size_limit === 40, "$php_size_limit should be minimum so: 40 [MB]");
echo "\ncheckIniFileSize: OK";

$files_ok = Flyspray::checkDirLock(BASEDIR . '/attachments');
assert($files_ok === true, "$files_ok shoul be true");
echo "\ncheckDirLock: OK";
