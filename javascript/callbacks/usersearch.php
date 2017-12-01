<?php
/*
	 This script is the AJAX callback that performs a search
	 for users, and returns them in an ordered list.
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

require_once('../../header.php');

$searchterm = '';
if (!empty($_POST))
{
	$searchterm = '%' . reset($_POST) . '%';	// pobiera pierwszy paramet z POST
}
/**/
else if (!empty($_GET['test']))
{
	$searchterm = '%' . reset($_GET) . '%';
}
/**/

// Get the list of users from the global groups above
if (!empty($searchterm))
{
	$get_users = $db->Query('SELECT u.real_name, u.user_name, u.account_enabled
								FROM {users} u
							  WHERE u.user_name ILIKE ? OR u.real_name ILIKE ?
							  order by u.account_enabled DESC, u.user_name, u.real_name',
							 array($searchterm, $searchterm), 20);
}
else
{
	$get_users = $db->Query('SELECT u.real_name, u.user_name, u.account_enabled
								FROM {users} u
								order by u.account_enabled DESC, u.user_name, u.real_name',
							 array(), 20);
}

$html = '<ul class="autocomplete">';

$found_disabled = false;
while ($row = $db->FetchRow($get_users))
{
	$data = array_map(array('Filters','noXSS'), $row);
	
	// gap before first disabled account
	if (!$found_disabled && empty($data['account_enabled'])) {
		$found_disabled = true;
		$html .= '<li title="-">&mdash;<span class="informal"> ('.L('inactive_accounts').')</span></li>';
	}
	
	$html .= '<li title="' . $data['real_name'] . '">' . $data['user_name'] . '<span class="informal"> (' . $data['real_name'] . ')</span></li>';
}

$html .= '</ul>';

echo $html;

?>
