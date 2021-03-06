<?php
/*
    This script is the AJAX callback that deletes a user's saved search
*/

define('IN_FS', true);

require_once('../../header.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->check_account_ok();
    $user->save_search();
}

?>
