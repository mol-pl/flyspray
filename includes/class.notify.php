<?php

/*
	---------------------------------------------------
	| This script contains the notification functions |
	---------------------------------------------------
*/

/**
 * Notifications
 *
 * @package
 * @version $Id: class.notify.php 1420 2007-08-19 06:35:41Z judas_iscariote $
 * @copyright 2006 Flyspray.org
 * @notes: This is a mess and should be replaced for 1.0
 */

if (empty($conf['general']['notifications_use_phpmailer']))
{
	require_once dirname(__FILE__) . '/external/swift-mailer/Swift.php';
}
else
{
	require_once dirname(__FILE__) . '/external/phpmailer/mail.class.php';
}

class Notifications {

	// {{{ Wrapper function for all others
	function Create ($type, $task_id, $info = null, $to = null, $ntype = NOTIFY_BOTH)
	{
		global $conf;
		
		if (is_null($to)) {
			$to = $this->Address($task_id, $type);
		}
		
		if(!is_array($to)) {
			settype($to, 'array');
		}

		// Nux start - extra notifications for anons
		if (!empty($conf['general']['extra_anon_notify']))
		{
			$task_details = Flyspray::GetTaskDetails($task_id);
			$arr_anon_email = $task_details['anon_email'];
			if (!empty($arr_anon_email))
			{
				// allow CSV anon e-mails
				$arr_anon_email = preg_replace('/\s*,\s*/', ',', $arr_anon_email);
				$arr_anon_email = explode(',', $arr_anon_email);
				// send extra notifications
				switch ($type)
				{
					case NOTIFY_COMMENT_ADDED:
						$this->Create(NOTIFY_ANON_COMMENT_ADDED, $task_id, $info, $arr_anon_email, NOTIFY_EMAIL);
					break;
					case NOTIFY_TASK_CLOSED:
						$this->Create(NOTIFY_ANON_TASK_CLOSED, $task_id, $info, $arr_anon_email, NOTIFY_EMAIL);
					break;
				}
			}
		}
		// Nux end

		if (!count($to)) {
			return false;
		}
		$msg = $this->GenerateMsg($type, $task_id, $info);
		// Nux start - correct urls for anons and others...
		if (!empty($conf['mol']) && !empty($conf['mol']['mail_replace_url_regexp']))
		{
			switch ($type)
			{
				case NOTIFY_ANON_COMMENT_ADDED:
				case NOTIFY_ANON_TASK_CLOSED:
				case NOTIFY_ANON_TASK:
					$mol_url_replacement = $conf['mol']['bibz_base_url'];
				break;
				default:
					$mol_url_replacement = $conf['mol']['bibz_admins_base_url'];
				break;
			}
			$msg = preg_replace($conf['mol']['mail_replace_url_regexp'], $mol_url_replacement, $msg);
		}
		// Nux end
		$result = true;
		if ($ntype == NOTIFY_EMAIL || $ntype == NOTIFY_BOTH) {
			if(!$this->SendEmail((is_array($to[0]) ? $to[0] : $to), $msg[0], $msg[1], $task_id)) {
				$result = false;
			}
		}
		if ($ntype == NOTIFY_JABBER || $ntype == NOTIFY_BOTH) {
			if(!$this->StoreJabber((is_array($to[1]) ? $to[1] : $to), $msg[0], $msg[1])) {
				$result = false;
			}
		}

		return $result;

	// End of Create() function
	} // }}}
	// {{{ Store Jabber messages for sending later
	function StoreJabber( $to, $subject, $body )
	{
		global $db, $fs;

		if (empty($fs->prefs['jabber_server'])
			|| empty($fs->prefs['jabber_port'])
			|| empty($fs->prefs['jabber_username'])
			|| empty($fs->prefs['jabber_password'])) {
				return false;
		}

		if (empty($to)) {
			return false;
		}

		$date = time();

		// store notification in table
		$db->Query("INSERT INTO {notification_messages}
						(message_subject, message_body, time_created)
						VALUES (?, ?, ?)",
						array($subject, $body, $date)
					);

		// grab notification id
		$result = $db->Query("SELECT message_id FROM {notification_messages}
									WHERE time_created = ? ORDER BY message_id DESC",
									array($date), 1);

		$row = $db->FetchRow($result);
		$message_id = $row['message_id'];

		// If message could not be inserted for
		// whatever reason...
		if (!$message_id) {
			return false;
		}

		// make sure every email address is only added once
		settype($to, 'array');
		$to = array_unique($to);

		foreach ($to as $jid)
		{
			// store each recipient in table
			$db->Query("INSERT INTO {notification_recipients}
							(notify_method, message_id, notify_address)
							VALUES (?, ?, ?)",
							array('j', $message_id, $jid)
						);

		}

		return true;
	} // }}}
	// {{{ Send Jabber messages that were stored earlier
	function SendJabber()
	{
		global $db, $fs;

		include_once BASEDIR . '/includes/class.jabber2.php';


		if (empty($fs->prefs['jabber_server'])
			|| empty($fs->prefs['jabber_port'])
			|| empty($fs->prefs['jabber_username'])
			|| empty($fs->prefs['jabber_password'])) {
				return false;
		}

		$JABBER = new Jabber($fs->prefs['jabber_username'] . '@' . $fs->prefs['jabber_server'],
									$fs->prefs['jabber_password'],
									$fs->prefs['jabber_ssl'],
									$fs->prefs['jabber_port']);
		$JABBER->login();


		// get listing of all pending jabber notifications
		$result = $db->Query("SELECT DISTINCT message_id
									FROM {notification_recipients}
									WHERE notify_method='j'");

		if (!$db->CountRows($result))
		{
			$JABBER->log("No notifications to send");
			return false;
		}

		// we have notifications to process - connect
		$JABBER->log("We have notifications to process...");
		$JABBER->log("Starting Jabber session:");

		$ids = array();

		while ( $row = $db->FetchRow($result) )
		{
			$ids[] = $row['message_id'];
		}

		$desired = join(",", array_map('intval', $ids));
		$JABBER->log("message ids to send = {" . $desired . "}");

		// removed array usage as it's messing up the select
		// I suspect this is due to the variable being comma separated
		// Jamin W. Collins 20050328
		$notifications = $db->Query("SELECT * FROM {notification_messages}
										WHERE message_id IN ($desired)
										ORDER BY time_created ASC"
									);

		$JABBER->log("number of notifications {" . $db->CountRows($notifications) . "}");

		// loop through notifications
		while ( $notification = $db->FetchRow($notifications) )
		{
			$subject	= $notification['message_subject'];
			$body		= $notification['message_body'];

			$JABBER->log("Processing notification {" . $notification['message_id'] . "}");

				$recipients = $db->Query("SELECT * FROM {notification_recipients}
												WHERE message_id = ?
												AND notify_method = 'j'",
												array($notification['message_id'])
											);

				// loop through recipients
				while ($recipient = $db->FetchRow($recipients) )
				{
					$jid = $recipient['notify_address'];
					$JABBER->log("- attempting send to {" . $jid . "}");

					// send notification
					if ($JABBER->send_message($jid, $body, $subject, 'normal'))
					{
						// delete entry from notification_recipients
						$result = $db->Query("DELETE FROM {notification_recipients}
													WHERE message_id = ?
													AND notify_method = 'j'
													AND notify_address = ?",
													array($notification['message_id'], $jid)
												);
						$JABBER->log("- notification sent");
					} else {
						$JABBER->log("- notification not sent");
					}
				}
				// check to see if there are still recipients for this notification
				$result = $db->Query("SELECT * FROM {notification_recipients}
											WHERE message_id = ?",
											array($notification['message_id'])
										);

				if ( $db->CountRows($result) == 0 )
				{
					$JABBER->log("No further recipients for message id {" . $notification['message_id'] . "}");
					// remove notification no more recipients
					$result = $db->Query("DELETE FROM {notification_messages}
												WHERE message_id = ?",
												array($notification['message_id'])
											);
					$JABBER->log("- Notification deleted");
				}
			}

			// disconnect from server
			$JABBER->disconnect();
			$JABBER->log("Disconnected from Jabber server");

		return true;
	} // }}}
	// {{{ Send email
	function SendEmail($to, $subject, $body, $task_id)
	{
		global $fs, $proj, $user, $conf;
		
		if (empty($to) || empty($to[0])) {
			return;
		}

		// Swift mailer setup
		if (empty($conf['general']['notifications_use_phpmailer']))
		{
			// Do we want to use a remote mail server?
			if (!empty($fs->prefs['smtp_server'])) {

				Swift_ClassLoader::load('Swift_Connection_SMTP');
				$swiftconn =& new Swift_Connection_SMTP($fs->prefs['smtp_server']);

				if ($fs->prefs['smtp_user']) {
					$swiftconn->setUsername($fs->prefs['smtp_user']);
					$swiftconn->setPassword($fs->prefs['smtp_pass']);
				}
				if(defined('FS_SMTP_TIMEOUT')) {
					$swiftconn->setTimeout(FS_SMTP_TIMEOUT);
				}
			// Use php's built-in mail() function
			} else {
				Swift_ClassLoader::load('Swift_Connection_NativeMail');
				$swiftconn =& new Swift_Connection_NativeMail();
			}

			if(defined( 'FS_MAIL_LOGFILE')) {
				$log =& Swift_LogContainer::getLog();
				$log->setLogLevel(SWIFT_LOG_EVERYTHING);
			}

			$swift =& new Swift($swiftconn);

			Swift_CacheFactory::setClassName("Swift_Cache_Disk");
			Swift_Cache_Disk::setSavePath(Flyspray::get_tmp_dir());

			$message =& new Swift_Message($subject, $body);
			$message->headers->setCharset('utf-8');
			$message->headers->set('Precedence', 'list');
			$message->headers->set('X-Mailer', 'Flyspray');

			if ($proj->prefs['notify_reply']) {
				$message->setReplyTo($proj->prefs['notify_reply']);
			}
		}
		// PHPmailer setup
		else
		{
			$nmail = new smpMail();
			$nmail->ClearPrevious();
		}

		// threading
		if($task_id) {
			$hostdata = parse_url($GLOBALS['baseurl']);
			$inreplyto = sprintf('<'.FS_PREFIX_CODE.'%d@%s>', $task_id, $hostdata['host']);
			// see http://cr.yp.to/immhf/thread.html this does not seems to work though :(
			if (empty($conf['general']['notifications_use_phpmailer']))
			{
				$message->headers->set('In-Reply-To', $inreplyto);
				$message->headers->set('References', $inreplyto);
			}
			else
			{
				$nmail->AddCustomHeader('In-Reply-To', $inreplyto);
				$nmail->AddCustomHeader('References', $inreplyto);
			}
		}

		// send with Swift
		if (empty($conf['general']['notifications_use_phpmailer']))
		{
			$recipients =& new Swift_RecipientList();
			// now accepts string , array or Swift_Address.
			$recipients->addTo($to);
			$message->build();
			$retval = (bool) $swift->batchsend($message, $recipients,
						new Swift_Address($fs->prefs['admin_email'], '['.$proj->prefs['project_title'].']'));

			if(defined('FS_MAIL_LOGFILE')) {
				if(is_writable(dirname(FS_MAIL_LOGFILE))) {
					if($fh = fopen(FS_MAIL_LOGFILE, 'ab')) {
						fwrite($fh, $log->dump(true));
						fwrite($fh, php_uname());
						fclose($fh);
					}
				}

			}
			$swift->disconnect();
		}
		// send with PHP mailer
		else
		{
			// remove duplicates - e.g. when someone is assigned and has notifications for the task set.
			$to = array_unique($to);

			// send one by one (this is needed because some might be external and some might be internal...)
			// TODO: make batch send and filter mails on $nmail side
			$retval = true;
			foreach ($to as $toaddr)
			{
				$retval &= $nmail->send("[{$proj->prefs['project_title']}] <{$fs->prefs['admin_email']}>", $toaddr, $subject, $body, false);
			}

		}

		// debug log on error
		if (!$retval)
		{
			error_log (
				sprintf(''
					."\n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-"
					."\nTime:".date('c')
					."\nIP:{$_SERVER['REMOTE_ADDR']}"
					."\nMailer:".(empty($conf['general']['notifications_use_phpmailer']) ? 'Swift' : 'PHPmailer')
					."\n---------------------------------------------"
					."\nSendEmail"
					."\n \$task_id, '\$to', '\$subject', '\$body':\n"
					."\n $task_id, '%s', '$subject', '$body'\n"
					,strtr(var_export($to, true), "\r\n", '  ')
				)
				,3
				,FS_CACHE_DIR.'/err.log'
			);
		}

		return $retval;

	} //}}}
	// {{{ Create a message for any occasion
	function GenerateMsg($type, $task_id, $arg1='0')
	{
		global $db, $fs, $user, $proj, $conf;

		// Get the task details
		$task_details = Flyspray::getTaskDetails($task_id);
		if ($task_id) {
			$proj = new Project($task_details['project_id']);
		}

		// Set the due date correctly
		if ($task_details['due_date'] == '0') {
			$due_date = L('undecided');
		} else {
			$due_date = formatDate($task_details['due_date']);
		}

		// Set the due version correctly
		if ($task_details['closedby_version'] == '0') {
			$task_details['due_in_version_name'] = L('undecided');
		}

		// Get the string of modification
		$notify_type_msg = array(
			0 => L('none'),
			NOTIFY_TASK_OPENED		=> L('taskopened'),
			NOTIFY_TASK_CHANGED		=> L('pm.taskchanged'),
			NOTIFY_TASK_CLOSED		=> L('taskclosed'),
			NOTIFY_TASK_REOPENED	=> L('pm.taskreopened'),
			NOTIFY_DEP_ADDED		=> L('pm.depadded'),
			NOTIFY_DEP_REMOVED		=> L('pm.depremoved'),
			NOTIFY_COMMENT_ADDED	=> L('commentadded'),
			NOTIFY_ATT_ADDED		=> L('attachmentadded'),
			NOTIFY_REL_ADDED		=> L('relatedadded'),
			NOTIFY_OWNERSHIP		=> L('ownershiptaken'),
			NOTIFY_PM_REQUEST		=> L('pmrequest'),
			NOTIFY_PM_DENY_REQUEST 	=> L('pmrequestdenied'),
			NOTIFY_NEW_ASSIGNEE		=> L('newassignee'),
			NOTIFY_REV_DEP			=> L('revdepadded'),
			NOTIFY_REV_DEP_REMOVED	=> L('revdepaddedremoved'),
			NOTIFY_ADDED_ASSIGNEES	=> L('assigneeadded'),
		);

		// Generate the nofication subject
		$subject_format_string = empty($proj->prefs['notify_subject']) ? '[%p][#%t] %s' : $proj->prefs['notify_subject'];
		
		// Nux start - remove some tags from task_details
		if (!empty($conf['general']['notify_remove_tags']))
		{
			$arrTags = explode(",", $conf['general']['notify_remove_tags']);
			foreach($arrTags as $strTagName)
			{
				if (is_array($task_details))
				{
					foreach ($task_details as $key=>$vTaskVal)
					{
						if (Flyspray::removeTag($vTaskVal, $strTagName))
						{
							$task_details[$key] = $vTaskVal; 
						}
					}
				}
			}
		}
		// Nux end
		
		// Nux - different annon notify
		if ($type == NOTIFY_ANON_TASK
			// Nux start - extra notifications for anons
			|| $type == NOTIFY_ANON_COMMENT_ADDED
			|| $type == NOTIFY_ANON_TASK_CLOSED
			// Nux end
		)
		{
			$subject_format_string = empty($conf['general']['anon_notify_subject']) ?
				L('notifyfromfs')
				:
				$conf['general']['anon_notify_subject']
			;
		}
		
		if ($type == NOTIFY_CONFIRMATION || $type == NOTIFY_PW_CHANGE || $type == NOTIFY_NEW_USER
		) {
			$subject = L('notifyfromfs');
		} else {
			// more subject options (by Nux)
			if (preg_match('#[0-9]#', $task_details['due_in_version_name']))
			{
				$version_short = preg_replace('#^[^0-9]+#', '', $task_details['due_in_version_name']);
			}
			else
			{
				$version_short = '-';
			}
			$subject = strtr($subject_format_string,
						array(
							'%dvs' => $version_short,
							'%dv' => $task_details['due_in_version_name'],
							'%st' => $task_details['status_name'],
							'%ct' => $task_details['category_name'],
							'%pri' => $task_details['priority_name'],
							'%sev' => $task_details['severity_name'],
							'%s' => $task_details['item_summary'],
							'%t' => $task_id,
							'%a' => empty($notify_type_msg[$type]) ? '-' : $notify_type_msg[$type],
							'%p' => $proj->prefs['project_title'],
							'%u' => $user->infos['user_name'],
						)
					);
		}

		$subject = strtr($subject, "\n", '');


		/* ---------------------------------
			| List of notification types: 	|
			| 1. Task opened				|
			| 2. Task details changed		|
			| 3. Task closed				|
			| 4. Task re-opened				|
			| 5. Dependency added			|
			| 6. Dependency removed			|
			| 7. Comment added				|
			| 8. Attachment added			|
			| 9. Related task added			|
			|10. Taken ownership			|
			|11. Confirmation code			|
			|12. PM request					|
			|13. PM denied request			|
			|14. New assignee				|
			|15. Reversed dep				|
			|16. Reversed dep removed		|
			|17. Added to assignees list  	|
			|18. Anon-task opened			|
			|19. Password change			|
			|20. New user					|
			--------------------------------
		*/

		$body = L('donotreply') . "\n";
		$body .= CreateURL('details', $task_id) . "\n\n";
		// {{{ New task opened
		if ($type == NOTIFY_TASK_OPENED)
		{
			$body .=  L('newtaskopened') . " \n\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ") \n\n";
			$body .= L('attachedtoproject') . ' - ' .  $task_details['project_title'] . "\n";
			$body .= L('summary') . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('tasktype') . ' - ' . $task_details['tasktype_name'] . "\n";
			$body .= L('category') . ' - ' . $task_details['category_name'] . "\n";
			$body .= L('status') . ' - ' . $task_details['status_name'] . "\n";
			$body .= L('assignedto') . ' - ' . implode(', ', $task_details['assigned_to_name']) . "\n";
			$body .= L('operatingsystem') . ' - ' . $task_details['os_name'] . "\n";
			$body .= L('severity') . ' - ' . $task_details['severity_name'] . "\n";
			$body .= L('priority') . ' - ' . $task_details['priority_name'] . "\n";
			$body .= L('reportedversion') . ' - ' . $task_details['reported_version_name'] . "\n";
			$body .= L('dueinversion') . ' - ' . $task_details['due_in_version_name'] . "\n";
			$body .= L('duedate') . ' - ' . $due_date . "\n";
			$body .= L('details') . ' - ' . $task_details['detailed_desc'] . "\n\n";

			if ($arg1 == 'files') {
				$body .= L('fileaddedtoo') . "\n\n";
				$subject .= ' (' . L('attachmentadded') . ')';
			}

			$body .= L('moreinfo') . "\n";

			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Task details changed
		if ($type == NOTIFY_TASK_CHANGED)
		{
			$translation = array('priority_name' => L('priority'),
										'severity_name' => L('severity'),
										'status_name'	=> L('status'),
										'assigned_to_name' => L('assignedto'),
										'due_in_version_name' => L('dueinversion'),
										'reported_version_name' => L('reportedversion'),
										'tasktype_name' => L('tasktype'),
										'os_name' => L('operatingsystem'),
										'category_name' => L('category'),
										'due_date' => L('duedate'),
										'percent_complete' => L('percentcomplete'),
										'mark_private' => L('visibility'),
										'item_summary' => L('summary'),
										'detailed_desc' => L('taskedited'),
										'project_title' => L('attachedtoproject'));

			$body .= L('taskchanged') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ': ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";

			foreach($arg1 as $change)
			{
				if($change[0] == 'assigned_to_name') {
					$change[1] = implode(', ', $change[1]);
					$change[2] = implode(', ', $change[2]);
				}

				if($change[0] == 'detailed_desc') {
					$body .= $translation[$change[0]] . ":\n-------\n" . $change[2] . "\n-------\n";
				} else {
					$body .= $translation[$change[0]] . ': ' . ( ($change[1]) ? $change[1] : '[-]' ) . ' -> ' . ( ($change[2]) ? $change[2] : '[-]' ) . "\n";
				}
			}
			$body .= "\n" . L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Task closed
		if ($type == NOTIFY_TASK_CLOSED)
		{
			$body .=  L('notify.taskclosed') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= L('reasonforclosing') . ' ' . $task_details['resolution_name'] . "\n";

			if (!empty($task_details['closure_comment']))
			{
				$body .= L('closurecomment') . ' ' . $task_details['closure_comment'] . "\n\n";
			}

			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Task re-opened
		if ($type == NOTIFY_TASK_REOPENED)
		{
			$body .=  L('notify.taskreopened') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] .  ")\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Dependency added
		if ($type == NOTIFY_DEP_ADDED)
		{
			$depend_task = Flyspray::getTaskDetails($arg1);

			$body .=  L('newdep') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
			$body .= L('newdepis') . ':' . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\n";
			$body .= CreateURL('details', $depend_task['task_id']) . "\n\n";
		} // }}}
		// {{{ Dependency removed
		if ($type == NOTIFY_DEP_REMOVED)
		{
			$depend_task = Flyspray::getTaskDetails($arg1);

			$body .= L('notify.depremoved') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
			$body .= L('removeddepis') . ':' . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\n";
			$body .= CreateURL('details', $depend_task['task_id']) . "\n\n";
		} // }}}
		// {{{ Comment added
		if ($type == NOTIFY_COMMENT_ADDED)
		{
			// Get the comment information
			$result = $db->Query("SELECT comment_id, comment_text
										FROM {comments}
										WHERE user_id = ?
										AND task_id = ?
										ORDER BY comment_id DESC",
										array($user->id, $task_id), '1');
			$comment = $db->FetchRow($result);

			$body .= L('notify.commentadded') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= "----------\n";
			$body .= $comment['comment_text'] . "\n";
			$body .= "----------\n\n";

			if ($arg1 == 'files') {
				$body .= L('fileaddedtoo') . "\n\n";
				$subject .= ' (' . L('attachmentadded') . ')';
			}

			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . '#comment' . $comment['comment_id'] . "\n\n";
		} // }}}
		// {{{ Attachment added
		if ($type == NOTIFY_ATT_ADDED)
		{
			$body .= L('newattachment') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Related task added
		if ($type == NOTIFY_REL_ADDED)
		{
			$related_task = Flyspray::getTaskDetails($arg1);

			$body .= L('notify.relatedadded') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
			$body .= L('relatedis') . ':' . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $related_task['task_id'] . ' - ' . $related_task['item_summary'] . "\n";
			$body .= CreateURL('details', $related_task['task_id']) . "\n\n";
		} // }}}
		// {{{ Ownership taken
		if ($type == NOTIFY_OWNERSHIP)
		{
			$body .= implode(', ', $task_details['assigned_to_name']) . ' ' . L('takenownership') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Confirmation code
		if ($type == NOTIFY_CONFIRMATION)
		{
			$body .= L('noticefrom') . " {$proj->prefs['project_title']}\n\n"
					. L('addressused') . "\n\n"
					. " {$arg1[0]}index.php?do=register&magic_url={$arg1[1]} \n\n"
					// In case that spaces in the username have been removed
					. L('username') . ': '. $arg1[2] . "\n"
					. L('confirmcodeis') . " $arg1[3] \n\n";
		} // }}}
		// {{{ Pending PM request
		if ($type == NOTIFY_PM_REQUEST)
		{
			$body .= L('requiresaction') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ PM request denied
		if ($type == NOTIFY_PM_DENY_REQUEST)
		{
			$body .= L('pmdeny') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= L('denialreason') . ':' . "\n";
			$body .= $arg1 . "\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ New assignee
		if ($type == NOTIFY_NEW_ASSIGNEE)
		{
			$body .= L('assignedtoyou') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n\n";
			$body .= L('moreinfo') . "\n";
			$body .= CreateURL('details', $task_id) . "\n\n";
		} // }}}
		// {{{ Reversed dep
		if ($type == NOTIFY_REV_DEP)
		{
			$depend_task = Flyspray::getTaskDetails($arg1);

			$body .= L('taskwatching') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
			$body .= L('isdepfor') . ':' . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\n";
			$body .= CreateURL('details', $depend_task['task_id']) . "\n\n";
		} // }}}
		// {{{ Reversed dep - removed
		if ($type == NOTIFY_REV_DEP_REMOVED)
		{
			$depend_task = Flyspray::getTaskDetails($arg1);

			$body .= L('taskwatching') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
			$body .= L('isnodepfor') . ':' . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\n";
			$body .= CreateURL('details', $depend_task['task_id']) . "\n\n";
		} // }}}
		// {{{ User added to assignees list
		if ($type == NOTIFY_ADDED_ASSIGNEES)
		{
			$body .= L('useraddedtoassignees') . "\n\n";
			$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
			$body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\n";
			$body .= CreateURL('details', $task_id) . "\n\n\n";
		} // }}}
		// {{{ Anon-task has been opened
		if ($type == NOTIFY_ANON_TASK)
		{
			$body .= L('thankyouforbug') . "\n\n";
			$body .= CreateURL('details', $task_id, null, array('task_token' => $arg1)) . "\n\n";
		} // }}}
		// {{{ Nux start - extra notifications for anons
		if ($type == NOTIFY_ANON_COMMENT_ADDED || $type == NOTIFY_ANON_TASK_CLOSED)
		{
			if ($type == NOTIFY_ANON_COMMENT_ADDED)
			{
				//$body .= L('heyanonCommentwasadded') . "\n\n";
				// Get the comment information
				$result = $db->Query("SELECT comment_id, comment_text
											FROM {comments}
											WHERE user_id = ?
											AND task_id = ?
											ORDER BY comment_id DESC",
											array($user->id, $task_id), '1');
				$comment = $db->FetchRow($result);

				$body .= L('notify.commentadded') . "\n\n";
				$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
				$body .= L('userwho') . ' - ' . $user->infos['real_name'] . "\n\n";
				$body .= "----------\n";
				$body .= $comment['comment_text'] . "\n";
				$body .= "----------\n\n";

				if ($arg1 == 'files') {
					$body .= L('fileaddedtoo') . "\n\n";
					$subject .= ' (' . L('attachmentadded') . ')';
				}

				$body .= L('moreinfo') . "\n";
				$body .= CreateURL('details', $task_id) . '#comment' . $comment['comment_id'] . "\n\n";
			}
			else //$type == NOTIFY_ANON_TASK_CLOSED
			{
				$body .=  L('notify.taskclosed') . "\n\n";
				$body .= FS_PREFIX_CODE.'#' . $task_id . ' - ' . $task_details['item_summary'] . "\n\n";
				$body .= L('reasonforclosing') . ' ' . $task_details['resolution_name'] . "\n";

				if (!empty($task_details['closure_comment']))
				{
					$body .= L('closurecomment') . ' ' . $task_details['closure_comment'] . "\n";
				}

				$body .= "\n". L('moreinfo') . "\n";
				$body .= CreateURL('details', $task_id) . "\n\n";
			}
		} // }}}
		// {{{ Password change 
		if ($type == NOTIFY_PW_CHANGE)
		{
			$body = L('messagefrom'). $arg1[0] . "\n\n"
						. L('magicurlmessage')." \n"
						. "{$arg1[0]}index.php?do=lostpw&magic_url=$arg1[1]\n";
		} // } }}
		// {{{ New user
		if ($type == NOTIFY_NEW_USER)
		{
			$body = L('messagefrom'). $arg1[0] . "\n\n"
						. L('newuserregistered')." \n\n"
						. L('username') . ': ' . $arg1[1] . "\n" .
						L('realname') . ': ' . $arg1[2] . "\n";
			if ($arg1[6]) {
				$body .= L('password') . ': ' . $arg1[5] . "\n";
			}
			$body .= L('emailaddress') . ': ' . $arg1[3] . "\n" .
					L('jabberid') . ':' . $arg1[4] . "\n\n";
		} // }}}

		$body .= L('disclaimer');

		
		// Nux start - remove some tags that migh have got tangled in from e.g. comments...
		if (!empty($conf['general']['notify_remove_tags']))
		{
			$arrTags = explode(",", $conf['general']['notify_remove_tags']);
			foreach($arrTags as $strTagName)
			{
				Flyspray::removeTag($body, $strTagName);
			}
		}
		// Nux end

		return array(Notifications::fixMsgData($subject), Notifications::fixMsgData($body));

	} // }}}
	// {{{ Create an address list for specific users
	function SpecificAddresses($users, $ignoretype = false)
	{
		global $db, $fs, $user;

		$jabber_users = array();
		$email_users = array();

		if(!is_array($users)) {
			settype($users, 'array');
		}

		if (count($users) < 1) {
			return array();
		}

		$sql = $db->Query('SELECT user_id, notify_type, email_address, jabber_id
									FROM {users}
									WHERE' . substr(str_repeat(' user_id = ? OR ', count($users)), 0, -3),
									array_values($users));

		while ($user_details = $db->FetchRow($sql))
		{
				if ($user_details['user_id'] == $user->id && !$user->infos['notify_own']) {
					continue;
				}

				if ( ($fs->prefs['user_notify'] == '1' && ($user_details['notify_type'] == NOTIFY_EMAIL || $user_details['notify_type'] == NOTIFY_BOTH) )
					|| $fs->prefs['user_notify'] == '2' || $ignoretype)
				{
					array_push($email_users, $user_details['email_address']);

				}

				if ( ($fs->prefs['user_notify'] == '1' && ($user_details['notify_type'] == NOTIFY_JABBER || $user_details['notify_type'] == NOTIFY_BOTH) )
					|| $fs->prefs['user_notify'] == '3' || $ignoretype)
				{
					array_push($jabber_users, $user_details['jabber_id']);
				}
		}

		return array($email_users, array_unique($jabber_users));

	} // }}}
	// {{{ Create a standard address list of users (assignees, notif tab and proj addresses)
	function Address($task_id, $type)
	{
		global $db, $fs, $proj, $user;

		$users = array();

		$jabber_users = array();
		$email_users = array();

		$task_details = Flyspray::GetTaskDetails($task_id);

		/*
		// Nux start - extra notifications for anons
		if ($type==NOTIFY_ANON_COMMENT_ADDED || $type==NOTIFY_ANON_TASK_CLOSED)
		{
			if (!empty($task_details['anon_email']))
			{
				array_push($email_users, $task_details['anon_email']);
			}
		}
		// Nux end
		*/

		// Get list of users from the notification tab
		$get_users = $db->Query('SELECT * FROM {users} WHERE user_id IN (
				SELECT user_id
					FROM {notifications}
					WHERE task_id = ?
				UNION
				SELECT user_id
					FROM {assigned}
					WHERE task_id = ?
			)
			',
			array($task_id, $task_id)
		);

		while ($row = $db->FetchRow($get_users))
		{
			if ($row['user_id'] == $user->id && !$user->infos['notify_own']) {
				continue;
			}

			if ( ($fs->prefs['user_notify'] == '1' && ($row['notify_type'] == NOTIFY_EMAIL || $row['notify_type'] == NOTIFY_BOTH) )
				|| $fs->prefs['user_notify'] == '2')
			{
					array_push($email_users, $row['email_address']);

			}

			if ( ($fs->prefs['user_notify'] == '1' && ($row['notify_type'] == NOTIFY_JABBER || $row['notify_type'] == NOTIFY_BOTH) )
				|| $fs->prefs['user_notify'] == '3')
			{
					array_push($jabber_users, $row['jabber_id']);
			}
		}
		
		// Now, we add the project contact addresses...
		// ...but only if the task is public
		if ($task_details['mark_private'] != '1' && in_array($type, Flyspray::int_explode(' ', $proj->prefs['notify_types'])))
		{
			$proj_emails = preg_split('/[\s,;]+/', $proj->prefs['notify_email'], -1, PREG_SPLIT_NO_EMPTY);
			$proj_jids = explode(',', $proj->prefs['notify_jabber']);

			foreach ($proj_emails as $key => $val)
			{
				if (!empty($val) && !in_array($val, $email_users))
					array_push($email_users, $val);
			}

			foreach ($proj_jids as $key => $val)
			{
				if (!empty($val) && !in_array($val, $jabber_users))
					array_push($jabber_users, $val);
			}

		// End of checking if a task is private
		}
		// Send back two arrays containing the notification addresses
		return array($email_users, array_unique($jabber_users));

	} // }}}
	// {{{ Fix the message data 
	/**
		* fixMsgData 
		* a 0.9.9.x ONLY workaround for the "truncated email problem"
		* based on code Henri Sivonen (http://hsivonen.iki.fi) 
		* @param mixed $data
		* @access public
		* @return void
		*/
	function fixMsgData($data)
	{
		// at the first step, remove all NUL bytes
		//users with broken databases  encoding  can give us this :(
		$data = str_replace(chr(0), '', $data);

		//then remove all invalid utf8 secuences
		$UTF8_BAD =
			'([\x00-\x7F]'.								# ASCII (including control chars)
			'|[\xC2-\xDF][\x80-\xBF]'.					# non-overlong 2-byte
			'|\xE0[\xA0-\xBF][\x80-\xBF]'.				# excluding overlongs
			'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.		# straight 3-byte
			'|\xED[\x80-\x9F][\x80-\xBF]'.				# excluding surrogates
			'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.			# planes 1-3
			'|[\xF1-\xF3][\x80-\xBF]{3}'.				# planes 4-15
			'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.			# plane 16
			'|(.{1}))';									# invalid byte

		$valid_data = '';

		while (preg_match('/'.$UTF8_BAD.'/S', $data, $matches)) {
			if ( !isset($matches[2])) {
				$valid_data .= $matches[0];
			} else {
				$valid_data .= '?';
			}
			$data = substr($data, strlen($matches[0]));
		}
		return $valid_data;
	} //}}}

// End of Notify class
}

?>
