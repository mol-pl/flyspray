<?php
/**
	Remove old attachments (to be run via cron).

	This script checks closed tasks and removes some attachments in them.

	Criteria for removal can include:
	<li>task type
	<li>task category
	<li>file size
	<li>file type
*/
define('IN_FS', true);

require_once 'header.php';
require_once BASEDIR . '/includes/class.AttachmentRemoval.php';

$del_config = include BASEDIR . '/includes/attachment_delete.config.php';

$sch_helper = new AttachmentRemoval($del_config);

// echo "\n".$sch_helper->filter->letter_to_byte('1M');
// echo "\n".$sch_helper->filter->letter_to_byte('1MB');
// die();

$is_enabled = isset($conf['attach_del']) && isset($conf['attach_del']['enabled']) ? intval(isset($conf['attach_del']['enabled'])) : 0;

if($is_enabled) {
	if(php_sapi_name() === 'cli') {
		$sch_helper->run_schedule($conf['attach_del']);
	} else {
		die("[WARNING] you are not authorized to start the schedule.\n");
	}
} else {
	die("[WARNING] schedule is disabled... not running.\n");
}
