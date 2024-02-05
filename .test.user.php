<?php
/**
 * Test if user is working.
*/
// cmd only
if(php_sapi_name() !== 'cli') {
	die('');
}
// load classes
define('IN_FS', true);
require_once(dirname(__FILE__).'/header.php');

// test with user 1 (probably admin)
$user = new User(1, $proj);

// check default project
echo "\n\n";
echo "\nproject: ". $proj->id;
echo "\ncreate_attachments: ";
if (!$user->perms('create_attachments')) {
	echo "nope (error?).";
} else {
	echo "yes, it has!";
}

// all projects
echo "\n\n";
foreach ($user->perms as $pid => $perms) {
	echo "\nproject: ". $pid;
	echo "\ncreate_attachments: ";
	if (!$user->perms('create_attachments', $pid)) {
		echo "nope (error?).";
	} else {
		echo "yes, it has!";
	}
}
