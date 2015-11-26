<?php
/**
 * Flyspray
 *
 * Backend class
 *
 * This script contains reusable functions we use to modify
 * various things in the Flyspray database tables.
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Tony Collins, Florian Schmitz
 */

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class Backend
{
    /**
     * Adds the user $user_id to the notifications list of $tasks
     * @param integer $user_id
     * @param array $tasks
     * @param bool $do Force execution independent of user permissions
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_notification($user_id, $tasks, $do = false)
    {
        global $db, $user;

        settype($tasks, 'array');

        $user_id = Flyspray::username_to_id($user_id);

        if (!$user_id || !count($tasks)) {
            return false;
        }

        $sql = $db->Query(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          $tasks);

        while ($row = $db->FetchRow($sql)) {
            // -> user adds himself
            if ($user->id == $user_id) {
                if (!$user->can_view_task($row) && !$do) {
                    continue;
                }
            // -> user is added by someone else
            } else  {
                if (!$user->perms('manage_project', $row['project_id']) && !$do) {
                    continue;
                }
            }

            $notif = $db->Query('SELECT notify_id
                                   FROM {notifications}
                                  WHERE task_id = ? and user_id = ?',
                              array($row['task_id'], $user_id));

            if (!$db->CountRows($notif)) {
                $db->Query('INSERT INTO {notifications} (task_id, user_id)
                                 VALUES  (?,?)', array($row['task_id'], $user_id));
                Flyspray::logEvent($row['task_id'], 9, $user_id);
            }
        }

        return (bool) $db->CountRows($sql);
    }


    /**
     * Removes a user $user_id from the notifications list of $tasks
     * @param integer $user_id
     * @param array $tasks
     * @access public
     * @return void
     * @version 1.0
     */

    function remove_notification($user_id, $tasks)
    {
        global $db, $user;

        settype($tasks, 'array');

        if (!count($tasks)) {
            return;
        }

        $sql = $db->Query(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          $tasks);

        while ($row = $db->FetchRow($sql)) {
            // -> user removes himself
            if ($user->id == $user_id) {
                if (!$user->can_view_task($row)) {
                    continue;
                }
            // -> user is removed by someone else
            } else  {
                if (!$user->perms('manage_project', $row['project_id'])) {
                    continue;
                }
            }

            $db->Query('DELETE FROM  {notifications}
                              WHERE  task_id = ? AND user_id = ?',
                        array($row['task_id'], $user_id));
            if ($db->affectedRows()) {
                Flyspray::logEvent($row['task_id'], 10, $user_id);
            }
        }
    }


    /**
     * Assigns one or more $tasks only to a user $user_id
     * @param integer $user_id
     * @param array $tasks
     * @access public
     * @return void
     * @version 1.0
     */
    function assign_to_me($user_id, $tasks)
    {
        global $db, $notify;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        settype($tasks, 'array');
        if (!count($tasks)) {
            return;
        }

        $sql = $db->Query(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          $tasks);

        while ($row = $db->FetchRow($sql)) {
            if (!$user->can_take_ownership($row)) {
                continue;
            }

            $db->Query('DELETE FROM {assigned}
                              WHERE task_id = ?',
                        array($row['task_id']));

            $db->Query('INSERT INTO {assigned}
                                    (task_id, user_id)
                             VALUES (?,?)',
                        array($row['task_id'], $user->id));

            if ($db->affectedRows()) {
                Flyspray::logEvent($row['task_id'], 19, $user->id, implode(' ', Flyspray::GetAssignees($row['task_id'])));
                $notify->Create(NOTIFY_OWNERSHIP, $row['task_id']);
            }

            if ($row['item_status'] == STATUS_UNCONFIRMED || $row['item_status'] == STATUS_NEW) {
                $db->Query('UPDATE {tasks} SET item_status = 3 WHERE task_id = ?', array($row['task_id']));
                Flyspray::logEvent($row['task_id'], 3, 3, 1, 'item_status');
            }
        }
    }

    /**
     * Adds a user $user_id to the assignees of one or more $tasks
     * @param integer $user_id
     * @param array $tasks
     * @param bool $do Force execution independent of user permissions
     * @access public
     * @return void
     * @version 1.0
     */
    function add_to_assignees($user_id, $tasks, $do = false)
    {
        global $db, $notify;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        settype($tasks, 'array');
        if (!count($tasks)) {
            return;
        }

        $sql = $db->Query(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          array($tasks));

        while ($row = $db->FetchRow($sql)) {
            if (!$user->can_add_to_assignees($row) && !$do) {
                continue;
            }

            $db->Replace('{assigned}', array('user_id'=> $user->id, 'task_id'=> $row['task_id']), array('user_id','task_id'));

            if ($db->affectedRows()) {
                Flyspray::logEvent($row['task_id'], 29, $user->id, implode(' ', Flyspray::GetAssignees($row['task_id'])));
                $notify->Create(NOTIFY_ADDED_ASSIGNEES, $row['task_id']);
            }

            if ($row['item_status'] == STATUS_UNCONFIRMED || $row['item_status'] == STATUS_NEW) {
                $db->Query('UPDATE {tasks} SET item_status = 3 WHERE task_id = ?', array($row['task_id']));
                Flyspray::logEvent($row['task_id'], 3, 3, 1, 'item_status');
            }
        }
    }

    /**
     * Adds a vote from $user_id to the task $task_id
     * @param integer $user_id
     * @param integer $task_id
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_vote($user_id, $task_id)
    {
        global $db;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        $task = Flyspray::GetTaskDetails($task_id);

        if ($user->can_vote($task) > 0) {

            if($db->Query("INSERT INTO {votes} (user_id, task_id, date_time)
                           VALUES (?,?,?)", array($user->id, $task_id, time()))) {
                return true;
            }
        }
        return false;
    }

	/**
	 * Compare list of old (previous) ids with new ones.
	 *
	 * @param string $old Space separated list of old assignees.
	 * @param string $new Space separated list of new assignees.
	 * @return boolean|array
	 *	True if the lists are the same.
	 *  Array of integers created with `Flyspray::int_explode` from $new.
	 */
	function _equal_old_new_ids($old, $new) {
		$old = trim($old);
		$new = trim($new);
		// check if strings are equal
		if ($old != $new) {
			// make sure lists are not simply re-ordered
			$old_array = Flyspray::int_explode(' ', $old);
			$new_array = Flyspray::int_explode(' ', $new);
			sort ($old_array, SORT_NUMERIC);
			sort ($new_array, SORT_NUMERIC);
			if (implode(',', $old_array) !== implode(',', $new_array)) {
				return $new_array;
			}
		}
		return true;
	}

	/**
	 * Edit task details (either full or partial edit).
	 *
	 * @note It's assumed permissions for changing task details were already checked.
	 * @note If `$old_assigned_to` and `$new_assigned_to` are left out then assigments will not be changed.
	 *
	 * @global type $db
	 * @global type $user
	 * @global type $notify
	 *
	 * @param array $task Task array before changes.
	 * @param string $sql_fields_set
	 *		Comma-seprated list of columns wita a question mark for value;
	 *		the format needs to be appropriate for SET command (i.e. '... , anon_email = ?, ...').
	 * @param array $sql_fields_values Array of changed values (in the same order as in \a $sql_fileds_set).
	 * @param string $old_assigned_to Space separated list of old assignees.
	 * @param string $new_assigned_to Space separated list of new assignees.
	 * @param string $old_tags Space separated list of old tags.
	 * @param string $new_tags Space separated list of new tags.
	 * @param int $time Time of change (as from `time()` function). Defaults to current time.
	 */
    function edit_task($task, $sql_fields_set, $sql_fields_values, $old_assigned_to = '', $new_assigned_to = ''
			, $old_tags = '', $new_tags = ''
			,  $time = null)
    {
        global $db, $user, $notify;

		// default time
		if (is_null($time)) {
			$time = time();
		}

		// add task id to vals
		$sql_fields_values[] = $task['task_id'];

        $db->Query('UPDATE  {tasks}
			SET '.$sql_fields_set.'
			WHERE  task_id = ?',
			$sql_fields_values
		);

		$old_assigned_to = trim($old_assigned_to);
		$new_assigned_to = trim($new_assigned_to);
		$assignees_changed = false;
		// Update the list of users assigned this task
		if ($user->perms('edit_assignments')) {
			$new_assigned_to_array = self::_equal_old_new_ids($old_assigned_to, $new_assigned_to);
			if ($new_assigned_to_array !== true) {
				$assignees_changed = true;

				// Delete the current assignees for this task
				$db->Query('DELETE FROM {assigned}
								  WHERE task_id = ?',
							array($task['task_id']));

				// Convert assigned_to and store them in the 'assigned' table
				foreach ($new_assigned_to_array as $key => $val)
				{
					$db->Replace('{assigned}', array('user_id'=> $val, 'task_id'=> $task['task_id']), array('user_id','task_id'));
				}
			}
		}

		// Update the list of tags assigned to this task
		$old_tags = trim($old_tags);
		$new_tags = trim($new_tags);
		$new_tags_array = self::_equal_old_new_ids($old_tags, $new_tags);
		if ($new_tags_array !== true) {
			// Log to task history
			Flyspray::logEvent($task['task_id'], 40, $new_tags, $old_tags, '', $time);

			// Delete the current assignees for this task
			$db->Query('DELETE FROM {tag_assignment}
							  WHERE task_id = ?',
						array($task['task_id']));

			// Convert tags and store them in the 'assigned' table
			foreach ($new_tags_array as $key => $val)
			{
				$db->Replace('{tag_assignment}', array('tag_id'=> $val, 'task_id'=> $task['task_id']), array('tag_id','task_id'));
			}
		}

        // Get the details of the task we just updated
        // To generate the changed-task message
        $new_details_full = Flyspray::GetTaskDetails($task['task_id']);
        // Not very nice...maybe combine compare_tasks() and logEvent() ?
        $result = $db->Query("SELECT * FROM {tasks} WHERE task_id = ?",
                             array($task['task_id']));
        $new_details = $db->FetchRow($result);

        foreach ($new_details as $key => $val) {
            if (strstr($key, 'last_edited_') || $key == 'assigned_to' || $key == 'tags'
                    || is_numeric($key))
            {
                continue;
            }

            if ($val != $task[$key]) {
                // Log the changed fields in the task history
                Flyspray::logEvent($task['task_id'], 3, $val, $task[$key], $key, $time);
            }
        }

        $changes = Flyspray::compare_tasks($task, $new_details_full);
        if (count($changes) > 0) {
            $notify->Create(NOTIFY_TASK_CHANGED, $task['task_id'], $changes);
        }

        if ($assignees_changed) {
            // Log to task history
            Flyspray::logEvent($task['task_id'], 14, $new_assigned_to, $old_assigned_to, '', $time);

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            if ($new_assigned_to != '') {
                $new_assignees = array_diff(Flyspray::int_explode(' ', $new_assigned_to), Flyspray::int_explode(' ', $old_assigned_to));
                // Remove current user from notification list
                if (!$user->infos['notify_own']) {
                    $new_assignees = array_filter($new_assignees, create_function('$u', 'global $user; return $user->id != $u;'));
                }
                if(count($new_assignees)) {
                    $notify->Create(NOTIFY_NEW_ASSIGNEE, $task['task_id'], null, $notify->SpecificAddresses($new_assignees));
                }
            }
        }
	}

	/**
	 * Edit some parts of the task.
	 *
	 * @note Anons are only allowed to change status.
	 *
	 * @param array $task Task array before changes.
	 * @param array $values
	 *	Assoc. array with that may contain:
	 *	\li item_status Integer that is associated with status ID.
	 *  \li percent_complete Integer from 0 to 100.
	 *  \li item_status_anon Text status (used for anonimous edits). Any other then 'new' or 'end' is treated as if no change was made.
	 *  \li old_assigned Space separated list of old assignees.
	 *  \li assigned_to Space separated list of new assignees.
	 * @param int $time Time of change (as from `time()` function). Defaults to current time.
	 * @return type
	 */
	function incomment_task_edit($task, $values, $time = null) {
        global $user;
		
		if ($task['is_closed'])
		{
			return;
		}

		//
		// Prepare data
		//
		if (!$user->isAnon())
		{
			$item_status = intval(empty($values['item_status']) ? -1 : $values['item_status']);
			$percent_complete = intval(empty($values['percent_complete']) ? -1 : $values['percent_complete']);
			$old_assigned_to = trim(empty($values['old_assigned']) ? '' : $values['old_assigned']);
			$new_assigned_to = trim(empty($values['assigned_to']) ? '' : $values['assigned_to']);
		}
		// for anons only some statuses allowed
		else
		{
			// not allowed for anons
			$percent_complete = -1;
			$old_assigned_to = $new_assigned_to = '';
			// OK for anons
			switch (Post::val('item_status_anon')) {
				default:
				case 'as_is': $item_status = -1;             break;	// leave as-is (was)
				case 'new'  : $item_status = STATUS_NEW;     break;
				case 'end'  : $item_status = STATUS_DONE_OK; break;	// ~resolved
			}
		}
		
		// unauthorized or something went wrong
		if ($item_status < 0)
		{
			return;
		}

		//
		// Prepare update
		//
		$sql_fileds['set'] = ' item_status = ? ';
		$sql_fileds['vals'] = array($item_status);

		// add percent_complete if changed was authorized and has correct boundry
		if ($percent_complete >= 0 && $percent_complete <= 100)
		{
			$sql_fileds['set'] .= ' , percent_complete = ? ';
			$sql_fileds['vals'][] = $percent_complete;
		}

		Backend::edit_task($task, $sql_fileds['set'], $sql_fileds['vals'],
				$old_assigned_to, $new_assigned_to,
				'', '',
				$time);
	}

    /**
     * Adds a comment to $task
     * @param array $task
     * @param string $comment_text
     * @param integer $time for synchronisation with other functions
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_comment($task, $comment_text, $time = null)
    {
        global $db, $user, $notify;

        if (!($user->perms('add_comments', $task['project_id']) && (!$task['is_closed'] || $user->perms('comment_closed', $task['project_id'])))) {
            return false;
        }

        if (!is_string($comment_text) || !strlen($comment_text)) {
            return false;
        }

        $time =  !is_numeric($time) ? time() : $time ;

        $db->Query('INSERT INTO  {comments}
                                 (task_id, date_added, last_edited_time, user_id, comment_text)
                         VALUES  ( ?, ?, ?, ?, ? )',
                    array($task['task_id'], $time, $time, $user->id, $comment_text));

        $result = $db->Query('SELECT  comment_id
                                FROM  {comments}
                               WHERE  task_id = ?
                            ORDER BY  comment_id DESC',
                            array($task['task_id']), 1);
        $cid = $db->FetchOne($result);

        Flyspray::logEvent($task['task_id'], 4, $cid);

        if (Backend::upload_files($task['task_id'], $cid)) {
            $notify->Create(NOTIFY_COMMENT_ADDED, $task['task_id'], 'files');
        } else {
            $notify->Create(NOTIFY_COMMENT_ADDED, $task['task_id']);
        }

        return true;
    }

    /**
     * Upload files for a comment or a task
     * @param integer $task_id
     * @param integer $comment_id if it is 0, the files will be attached to the task itself
     * @param string $source name of the file input
     * @access public
     * @return bool
     * @version 1.0
     */
    function upload_files($task_id, $comment_id = 0, $source = 'userfile')
    {
        global $db, $notify, $conf, $user;

        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->perms('create_attachments', $task['project_id'])) {
            return false;
        }

        $res = false;

		if (!isset($_FILES[$source]['error'])) {
			return false;
		}

        foreach ($_FILES[$source]['error'] as $key => $error) {
            if ($error != UPLOAD_ERR_OK) {
                continue;
            }


            $fname = substr($task_id . '_' . md5(uniqid(mt_rand(), true)), 0, 30);
            $path = BASEDIR .'/attachments/'. $fname ;

            $tmp_name = $_FILES[$source]['tmp_name'][$key];

            // Then move the uploaded file and remove exe permissions
            if(!@move_uploaded_file($tmp_name, $path)) {
                //upload failed. continue
                continue;
            }

            @chmod($path, 0644);
            $res = true;

            // Use a different MIME type
			$mime = $_FILES[$source]['type'][$key];
            $fileparts = explode( '.', $_FILES[$source]['name'][$key]);
            $extension = end($fileparts);
            if (isset($conf['attachments'][$extension])) {
               $mime = $conf['attachments'][$extension];
            //actually, try really hard to get the real filetype, not what the browser reports.
            } elseif($type = Flyspray::check_mime_type($path)) {
				$mime = $type;
            }// we can try even more, however, far too much code is needed.
			
			// Nux: finally avoid weird types
			if (strlen($mime) > 40 || empty($mime)) {
				$mime = 'application/octet-stream';
			}
			$_FILES[$source]['type'][$key] = $mime;

            $db->Query("INSERT INTO  {attachments}
                                     ( task_id, comment_id, file_name,
                                       file_type, file_size, orig_name,
                                       added_by, date_added)
                             VALUES  (?, ?, ?, ?, ?, ?, ?, ?)",
                    array($task_id, $comment_id, $fname,
                        $_FILES[$source]['type'][$key],
                        $_FILES[$source]['size'][$key],
                        $_FILES[$source]['name'][$key],
                        $user->id, time()));

            // Fetch the attachment id for the history log
            $result = $db->Query('SELECT  attachment_id
                                    FROM  {attachments}
                                   WHERE  task_id = ?
                                ORDER BY  attachment_id DESC',
                    array($task_id), 1);
            Flyspray::logEvent($task_id, 7, $db->fetchOne($result), $_FILES[$source]['name'][$key]);
        }

        return $res;
    }

    /**
     * Delete one or more attachments of a task or comment
     * @param array $attachments
     * @access public
     * @return void
     * @version 1.0
     */
    function delete_files($attachments)
    {
        global $db, $user;

        settype($attachments, 'array');
        if (!count($attachments)) {
            return;
        }

        $sql = $db->Query(' SELECT t.*, a.*
                              FROM {attachments} a
                         LEFT JOIN {tasks} t ON t.task_id = a.task_id
                             WHERE ' . substr(str_repeat(' attachment_id = ? OR ', count($attachments)), 0, -3),
                          $attachments);

        while ($task = $db->FetchRow($sql)) {
            if (!$user->perms('delete_attachments', $task['project_id'])) {
                continue;
            }

            $db->Query('DELETE FROM {attachments} WHERE attachment_id = ?',
                       array($task['attachment_id']));
            @unlink(BASEDIR . '/attachments/' . $task['file_name']);
            Flyspray::logEvent($task['task_id'], 8, $task['orig_name']);
        }
    }

    /**
     * Cleans a username (length, special chars, spaces)
     * @param string $user_name
     * @access public
     * @return string
     */
    function clean_username($user_name)
    {
        // Limit length
        $user_name = substr(trim($user_name), 0, 32);
        // Remove doubled up spaces and control chars
        $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
        // Strip special chars
        return utf8_keepalphanum($user_name);
    }

    /**
     * Creates a new user
     * @param string $user_name
     * @param string $password
     * @param string $real_name
     * @param string $jabber_id
     * @param string $email
     * @param integer $notify_type
     * @param integer $time_zone
     * @param integer $group_in
     * @access public
     * @return bool false if username is already taken
     * @version 1.0
     * @notes This function does not have any permission checks (checked elsewhere)
     */
    function create_user($user_name, $password, $real_name, $jabber_id, $email, $notify_type, $time_zone, $group_in)
    {
        global $fs, $db, $notify, $baseurl;

        $user_name = Backend::clean_username($user_name);

        // Limit length
        $real_name = substr(trim($real_name), 0, 100);
        // Remove doubled up spaces and control chars
        $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);

        // Check to see if the username is available
        $sql = $db->Query('SELECT COUNT(*) FROM {users} WHERE user_name = ?', array($user_name));

        if ($db->fetchOne($sql)) {
            return false;
        }

        $auto = false;
        // Autogenerate a password
        if (!$password) {
            $auto = true;
            $password = substr(md5(uniqid(mt_rand(), true)), 0, mt_rand(8, 12));
        }

        $db->Query("INSERT INTO  {users}
                             ( user_name, user_pass, real_name, jabber_id, magic_url,
                               email_address, notify_type, account_enabled,
                               tasks_perpage, register_date, time_zone)
                     VALUES  ( ?, ?, ?, ?, ?, ?, ?, 1, 25, ?, ?)",
            array($user_name, Flyspray::cryptPassword($password), $real_name, strtolower($jabber_id), '', strtolower($email), $notify_type, time(), $time_zone));

        // Get this user's id for the record
        $uid = Flyspray::username_to_id($user_name);

        // Now, create a new record in the users_in_groups table
        $db->Query('INSERT INTO  {users_in_groups} (user_id, group_id)
                         VALUES  (?, ?)', array($uid, $group_in));

        Flyspray::logEvent(0, 30, serialize(Flyspray::getUserDetails($uid)));

        $varnames = array('iwatch','atome','iopened');

        $toserialize = array('string' => NULL,
                        'type' => array (''),
                        'sev' => array (''),
                        'due' => array (''),
                        'dev' => NULL,
                        'cat' => array (''),
                        'status' => array ('open'),
                        'order' => NULL,
                        'sort' => NULL,
                        'percent' => array (''),
                        'opened' => NULL,
                        'search_in_comments' => NULL,
                        'search_for_all' => NULL,
                        'reported' => array (''),
                        'only_primary' => NULL,
                        'only_watched' => NULL);


                foreach($varnames as $tmpname) {

                    if($tmpname == 'iwatch') {

                        $tmparr = array('only_watched' => '1');

                    } elseif ($tmpname == 'atome') {

                        $tmparr = array('dev'=> $uid);

                    } elseif($tmpname == 'iopened') {

                        $tmparr = array('opened'=> $uid);
                    }

                    $$tmpname = $tmparr + $toserialize;
                }

        // Now give him his default searches
        $db->Query('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('taskswatched'), serialize($iwatch), time()));
        $db->Query('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('assignedtome'), serialize($atome), time()));
        $db->Query('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('tasksireported'), serialize($iopened), time()));

        // Send a user his details (his username might be altered, password auto-generated)
        if ($fs->prefs['notify_registration']) {
            $sql = $db->Query('SELECT DISTINCT email_address
                                 FROM {users} u
                            LEFT JOIN {users_in_groups} g ON u.user_id = g.user_id
                                WHERE g.group_id = 1');
            $notify->Create(NOTIFY_NEW_USER, null,
                            array($baseurl, $user_name, $real_name, $email, $jabber_id, $password, $auto),
                            $db->FetchCol($sql), NOTIFY_EMAIL);
        }

        return true;
    }

    /**
     * Deletes a user
     * @param integer $uid
     * @access public
     * @return bool
     * @version 1.0
     */
    function delete_user($uid)
    {
        global $db, $user;

        if (!$user->perms('is_admin')) {
            return false;
        }

        $user_data = serialize(Flyspray::getUserDetails($uid));
        $tables = array('users', 'users_in_groups', 'searches',
                        'notifications', 'assigned');

        foreach ($tables as $table) {
            if (!$db->Query('DELETE FROM ' .'{' . $table .'}' . ' WHERE user_id = ?', array($uid))) {
                return false;
            }
        }

        // for the unusual situuation that a user ID is re-used, make sure that the new user doesn't
        // get permissions for a task automatically
        $db->Query('UPDATE {tasks} SET opened_by = 0 WHERE opened_by = ?', array($uid));

        Flyspray::logEvent(0, 31, $user_data);

        return true;
    }

    /**
     * Deletes a project
     * @param integer $pid
     * @param integer $move_to to which project contents of the project are moved
     * @access public
     * @return bool
     * @version 1.0
     */
    function delete_project($pid, $move_to = 0)
    {
        global $db, $user;

        if (!$user->perms('manage_project', $pid)) {
            return false;
        }

        // unset category of tasks because we don't move categories
        if ($move_to) {
            $db->Query('UPDATE {tasks} SET product_category = 0 WHERE project_id = ?', array($pid));
        }

        $tables = array('list_category', 'list_os', 'list_resolution', 'list_tasktype',
                        'list_status', 'list_version', 'admin_requests',
                        'cache', 'projects', 'tasks');

        foreach ($tables as $table) {
            if ($move_to && $table !== 'projects' && $table !== 'list_category') {
                $base_sql = 'UPDATE {' . $table . '} SET project_id = ?';
                $sql_params = array($move_to, $pid);
            } else {
                $base_sql = 'DELETE FROM {' . $table . '}';
                $sql_params = array($pid);
            }

            if (!$db->Query($base_sql . ' WHERE project_id = ?', $sql_params)) {
                return false;
            }
        }

        // groups are only deleted, not moved (it is likely
        // that the destination project already has all kinds
        // of groups which are also used by the old project)
        $sql = $db->Query('SELECT group_id FROM {groups} WHERE project_id = ?', array($pid));
        while ($row = $db->FetchRow($sql)) {
            $db->Query('DELETE FROM {users_in_groups} WHERE group_id = ?', array($row['group_id']));
        }
        $sql = $db->Query('DELETE FROM {groups} WHERE project_id = ?', array($pid));

        //we have enough reasons ..  the process is OK.
        return true;
    }

    /**
     * Adds a reminder to a task
     * @param integer $task_id
     * @param string $message
     * @param integer $how_often send a reminder every ~ seconds
     * @param integer $start_time time when the reminder starts
     * @param $user_id the user who is reminded. by default (null) all users assigned to the task are reminded.
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_reminder($task_id, $message, $how_often, $start_time, $user_id = null)
    {
        global $user, $db;
        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->perms('edit_assignments') && !$user->perms('manage_project', $task['project_id'])) {
            return false;
        }

        if (is_null($user_id)) {
            // Get all users assigned to a task
            $user_id = Flyspray::GetAssignees($task_id);
        } else {
            $user_id = array(Flyspray::username_to_id($user_id));
            if (!reset($user_id)) {
                return false;
            }
        }

        foreach ($user_id as $id) {
            $sql = $db->Replace('{reminders}',
                                array('task_id'=> $task_id, 'to_user_id'=> $id,
                                     'from_user_id' => $user->id, 'start_time' => $start_time,
                                     'how_often' => $how_often, 'reminder_message' => $message),
                                array('task_id', 'to_user_id', 'how_often', 'reminder_message'));
            if(!$sql) {
                // query has failed :(
                return false;
            }
        }
        // 2 = no record has found and was INSERT'ed correclty
        if (isset($sql) && $sql == 2) {
            Flyspray::logEvent($task_id, 17, $task_id);
        }
        return true;
    }

    /**
     * Adds a new task
     * @param array $args array containing all task properties. unknown properties will be ignored
     * @access public
     * @return integer the task ID on success
     * @version 1.0
     * @notes $args is POST data, bad..bad user..
     */
    function create_task($args)
    {
        global $db, $user, $proj;
        $notify = new Notifications();
        if ($proj->id !=  $args['project_id']) {
            $proj = new Project($args['project_id']);
        }

        if (!$user->can_open_task($proj) || count($args) < 3) {
            return 0;
        }

        if (!(($item_summary = $args['item_summary']) && ($detailed_desc = $args['detailed_desc']))) {
            return 0;
        }

        // Some fields can have default values set
        if (!$user->perms('modify_all_tasks')) {
            $args['closedby_version'] = 0;
            $args['task_priority'] = 2;
            $args['due_date'] = 0;
            $args['item_status'] = STATUS_UNCONFIRMED;
        }

        $param_names = array('task_type', 'item_status',
                'product_category', 'product_version', 'closedby_version',
                'operating_system', 'task_severity', 'task_priority');

        $sql_values = array(time(), time(), $args['project_id'], $item_summary,
                $detailed_desc, intval($user->id), 0);

        $sql_params = array();
        foreach ($param_names as $param_name) {
            if (isset($args[$param_name])) {
                $sql_params[] = $param_name;
                $sql_values[] = $args[$param_name];
            }
        }

        // Process the due_date
        if ( ($due_date = $args['due_date']) || ($due_date = 0) ) {
            $due_date = Flyspray::strtotime($due_date);
        }

        $sql_params[] = 'mark_private';
        $sql_values[] = intval($user->perms('manage_project') && isset($args['mark_private']) && $args['mark_private'] == '1');

        $sql_params[] = 'due_date';
        $sql_values[] = $due_date;

        $sql_params[] = 'closure_comment';
        $sql_values[] = '';

        // Token for anonymous users
        $token = '';
        if ($user->isAnon()) {
            $token = md5(uniqid(mt_rand(), true));
            $sql_params[] = 'task_token';
            $sql_values[] = $token;
        }
		// Nux start - allow setting anon_email for all users (not just anonymous)
		if (isset($args['anon_email']))
		{
            $sql_params[] = 'anon_email';
            $sql_values[] = $args['anon_email'];
		}
		// Nux end

        $sql_params = join(', ', $sql_params);
        // +1 for the task_id column;
        $sql_placeholder = $db->fill_placeholders($sql_values, 1);

        $result = $db->Query('SELECT  MAX(task_id)+1
                                FROM  {tasks}');
        $task_id = $db->FetchOne($result);
        $task_id = $task_id ? $task_id : 1;
        //now, $task_id is always the first element of $sql_values
        array_unshift($sql_values, $task_id);

        $result = $db->Query("INSERT INTO  {tasks}
                                 ( task_id, date_opened, last_edited_time,
                                   project_id, item_summary,
                                   detailed_desc, opened_by,
                                   percent_complete, $sql_params )
                         VALUES  ($sql_placeholder)", $sql_values);

        // Log the assignments and send notifications to the assignees
        if (isset($args['assigned_to']) && trim($args['assigned_to']))
        {
            // Convert assigned_to and store them in the 'assigned' table
            foreach (Flyspray::int_explode(' ', trim($args['assigned_to'])) as $key => $val)
            {
                $db->Replace('{assigned}', array('user_id'=> $val, 'task_id'=> $task_id), array('user_id','task_id'));
            }
            // Log to task history
            Flyspray::logEvent($task_id, 14, trim($args['assigned_to']));

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            $notify->Create(NOTIFY_NEW_ASSIGNEE, $task_id, null,
                            $notify->SpecificAddresses(Flyspray::int_explode(' ', $args['assigned_to'])));
        }

        // Log that the task was opened
        Flyspray::logEvent($task_id, 1);

        $result = $db->Query('SELECT  *
                                FROM  {list_category}
                               WHERE  category_id = ?',
                               array($args['product_category']));
        $cat_details = $db->FetchRow($result);

        // We need to figure out who is the category owner for this task
        if (!empty($cat_details['category_owner'])) {
            $owner = $cat_details['category_owner'];
        }
        else {
            // check parent categories
            $result = $db->Query('SELECT  *
                                    FROM  {list_category}
                                   WHERE  lft < ? AND rgt > ? AND project_id  = ?
                                ORDER BY  lft DESC',
                                   array($cat_details['lft'], $cat_details['rgt'], $cat_details['project_id']));
            while ($row = $db->FetchRow($result)) {
                // If there's a parent category owner, send to them
                if (!empty($row['category_owner'])) {
                    $owner = $row['category_owner'];
                    break;
                }
            }
        }

        if (!isset($owner)) {
            $owner = $proj->prefs['default_cat_owner'];
        }

        if ($owner) {
            if ($proj->prefs['auto_assign'] && ($args['item_status'] == STATUS_UNCONFIRMED || $args['item_status'] == STATUS_NEW)) {
                Backend::add_to_assignees($owner, $task_id, true);
            }
            Backend::add_notification($owner, $task_id, true);
        }

        // Reminder for due_date field
        if ($due_date) {
            Backend::add_reminder($task_id, L('defaultreminder') . "\n\n" . CreateURL('details', $task_id), 2*24*60*60, time());
        }

        // Create the Notification
        if (Backend::upload_files($task_id)) {
            $notify->Create(NOTIFY_TASK_OPENED, $task_id, 'files');
        } else {
            $notify->Create(NOTIFY_TASK_OPENED, $task_id);
        }

        // If the reporter wanted to be added to the notification list
        if (isset($args['notifyme']) && $args['notifyme'] == '1' && $user->id != $owner) {
            Backend::add_notification($user->id, $task_id, true);
        }

        if ($user->isAnon()) {
            $notify->Create(NOTIFY_ANON_TASK, $task_id, $token, $args['anon_email']);
        }

        return array($task_id, $token);
    }

    /**
     * Closes a task
     * @param integer $task_id
     * @param integer $reason
     * @param string $comment
     * @param bool $mark100
     * @access public
     * @return bool
     * @version 1.0
     */
    function close_task($task_id, $reason, $comment, $mark100 = true)
    {
        global $db, $notify, $user;
        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->can_close_task($task)) {
            return false;
        }

        if ($task['is_closed']) {
            return false;
        }

        $db->Query('UPDATE  {tasks}
                       SET  date_closed = ?, closed_by = ?, closure_comment = ?,
                            is_closed = 1, resolution_reason = ?, last_edited_time = ?,
                            last_edited_by = ?
                     WHERE  task_id = ?',
                    array(time(), $user->id, $comment, $reason, time(), $user->id, $task_id));

        if ($mark100) {
            $db->Query('UPDATE {tasks} SET percent_complete = 100 WHERE task_id = ?',
                       array($task_id));

            Flyspray::logEvent($task_id, 3, 100, $task['percent_complete'], 'percent_complete');
        }

        $notify->Create(NOTIFY_TASK_CLOSED, $task_id);
        Flyspray::logEvent($task_id, 2, $reason, $comment);

        // If there's an admin request related to this, close it
        $db->Query('UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?
                     WHERE  task_id = ? AND request_type = ?',
                    array($user->id, time(), $task_id, 1));

        // duplicate
        if ($reason == 6) {
            preg_match("/\b(?:".FS_PREFIX_CODE."#|bug )(\d+)\b/", $comment, $dupe_of);
            if (count($dupe_of) >= 2) {
                $existing = $db->Query('SELECT * FROM {related} WHERE this_task = ? AND related_task = ? AND is_duplicate = 1',
                                        array($task_id, $dupe_of[1]));

                if ($existing && $db->CountRows($existing) == 0) {
                    $db->Query('INSERT INTO {related} (this_task, related_task, is_duplicate) VALUES(?, ?, 1)',
                                array($task_id, $dupe_of[1]));
                }
                Backend::add_vote($task['opened_by'], $dupe_of[1]);
            }
        }

        return true;
    }

	/**
	 * Get assigned users list.
	 * 
	 * @param int $task_id Id of the task.
	 * @return array User list which can by used for generating select options in a template.
	 */
	function get_user_list($task_id)
	{
		global $proj, $db;
		$result = $db->Query('SELECT u.user_id, u.user_name, u.real_name, g.group_name
								FROM {assigned} a, {users} u
						   LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
						   LEFT JOIN {groups} g ON g.group_id = uig.group_id
							   WHERE a.user_id = u.user_id AND task_id = ? AND (g.project_id = 0 OR g.project_id = ?)
							ORDER BY g.project_id DESC',
							  array($task_id, $proj->id));
		$result = $db->GroupBy($result, 'user_id');
		$userlist = array();
		foreach ($result as $row) {
			$userlist[] = array(0 => $row['user_id'], 
								1 => sprintf('[%s] %s (%s)', $row['group_name'], $row['user_name'], $row['real_name']));
		}
		
		return $userlist;
	}



    /**
     * Returns an array of tasks (respecting pagination) and an ID list (all tasks)
     * @param array $args
     * @param array $visible
     * @param integer $offset
     * @param integer $comment
     * @param bool $perpage
     * @access public
     * @return array
     * @version 1.0
     */
    function get_task_list($args, $visible, $offset = 0, $perpage = 20)
    {
        global $proj, $db, $user, $conf;
        /* build SQL statement {{{ */
        // Original SQL courtesy of Lance Conry http://www.rhinosw.com/
        $where  = $sql_params = array();

        $select = '';
        $groupby = 't.task_id, ';
        $from   = '             {tasks}         t
                     LEFT JOIN  {projects}      p   ON t.project_id = p.project_id
                     LEFT JOIN  {list_tasktype} lt  ON t.task_type = lt.tasktype_id
                     LEFT JOIN  {list_status}   lst ON t.item_status = lst.status_id
                     LEFT JOIN  {list_resolution} lr ON t.resolution_reason = lr.resolution_id ';
        // Only join tables which are really necessary to speed up the db-query
        if (array_get($args, 'cat') || in_array('category', $visible)) {
            $from   .= ' LEFT JOIN  {list_category} lc  ON t.product_category = lc.category_id ';
            $select .= ' lc.category_name               AS category_name, ';
            $groupby .= 'lc.category_name, ';
        }
        if (in_array('votes', $visible)) {
            $from   .= ' LEFT JOIN  {votes} vot         ON t.task_id = vot.task_id ';
            $select .= ' COUNT(DISTINCT vot.vote_id)    AS num_votes, ';
        }
		
		/*
        $search_for_changes = in_array('lastedit', $visible) || array_get($args, 'changedto') || array_get($args, 'changedfrom');
		
		$max_date_definition = 'CASE WHEN max(c.date_added)>t.date_closed THEN
                                CASE WHEN max(c.date_added)>t.date_opened THEN CASE WHEN max(c.date_added) > t.last_edited_time THEN max(c.date_added) ELSE t.last_edited_time END ELSE
                                    CASE WHEN t.date_opened > t.last_edited_time THEN t.date_opened ELSE t.last_edited_time END END ELSE
                                CASE WHEN t.date_closed>t.date_opened THEN CASE WHEN t.date_closed > t.last_edited_time THEN t.date_closed ELSE t.last_edited_time END ELSE
                                    CASE WHEN t.date_opened > t.last_edited_time THEN t.date_opened ELSE t.last_edited_time END END END';
        if (array_get($args, 'search_in_comments') || in_array('comments', $visible) || $search_for_changes) {
            $from   .= ' LEFT JOIN  {comments} c        ON t.task_id = c.task_id ';
            $select .= ' COUNT(DISTINCT c.comment_id)   AS num_comments, ';
            // in other words: max(max(c.date_added), t.date_closed, t.date_opened, t.last_edited_time)
            if ($search_for_changes) {
                $select .= " $max_date_definition AS max_date, ";
            }
            $groupby .= 'c.date_added, ';	// Nux: umh... why? Might as well be c.comment_id
        }
		*/
		// @todo: unchangedwithin -> changedto date
		if (!empty($args['unchangedwithindays']) || !empty($args['unchangedwithinhours'])) {
			$withinhours = !empty($args['unchangedwithinhours']) ? floatval($args['unchangedwithinhours']) : 0;
			$withinhours += !empty($args['unchangedwithindays']) ? floatval($args['unchangedwithindays'])*24 : 0;
			$withinhours = intval($withinhours);	// "-0.5 hours" not workin with strtotime
			
			//$args['changedto'] = date("Y-m-d", strtotime("-{$withinhours} hours"));
			$args['changedto'] = date("c", strtotime("-{$withinhours} hours"));
			//echo "now - {$withinhours} hours = {$args['changedto']}";
		}

        $maxdatesql = ' GREATEST((SELECT max(c.date_added) FROM {comments} c WHERE c.task_id = t.task_id), t.date_opened, t.date_closed, t.last_edited_time) ';
        $search_for_changes = in_array('lastedit', $visible) || array_get($args, 'changedto') || array_get($args, 'changedfrom');
        if ($search_for_changes) {
            $select .= ' GREATEST((SELECT max(c.date_added) FROM {comments} c WHERE c.task_id = t.task_id), t.date_opened, t.date_closed, t.last_edited_time) AS max_date, ';
        }
        if (array_get($args, 'search_in_comments')) {
            $from   .= ' LEFT JOIN  {comments} c          ON t.task_id = c.task_id ';
        }
        if (in_array('comments', $visible)) {
            $select .= ' (SELECT COUNT(cc.comment_id) FROM {comments} cc WHERE cc.task_id = t.task_id)  AS num_comments, ';
        }
        if (in_array('reportedin', $visible)) {
            $from   .= ' LEFT JOIN  {list_version} lv   ON t.product_version = lv.version_id ';
            $select .= ' lv.version_name                AS product_version, ';
            $groupby .= 'lv.version_name, ';
        }
        if (array_get($args, 'opened') || in_array('openedby', $visible)) {
            $from   .= ' LEFT JOIN  {users} uo          ON t.opened_by = uo.user_id ';
            $select .= ' uo.real_name                   AS opened_by_name, ';
            $groupby .= 'uo.real_name, ';
        }
        if (array_get($args, 'closed')) {
            $from   .= ' LEFT JOIN  {users} uc          ON t.closed_by = uc.user_id ';
            $select .= ' uc.real_name                   AS closed_by_name, ';
            $groupby .= 'uc.real_name, ';
        }
        if (array_get($args, 'due') || in_array('dueversion', $visible)) {
            $from   .= ' LEFT JOIN  {list_version} lvc  ON t.closedby_version = lvc.version_id ';
            $select .= ' lvc.version_name               AS closedby_version, ';
            $groupby .= 'lvc.version_name, ';
        }
        if (in_array('os', $visible)) {
            $from   .= ' LEFT JOIN  {list_os} los       ON t.operating_system = los.os_id ';
            $select .= ' los.os_name                    AS os_name, ';
            $groupby .= 'los.os_name, ';
        }
        if (in_array('attachments', $visible) || array_get($args, 'has_attachment')) {
            $from   .= ' LEFT JOIN  {attachments} att   ON t.task_id = att.task_id ';
            $select .= ' COUNT(DISTINCT att.attachment_id) AS num_attachments, ';
        }
		/**
		// Nux: Buggy - doubled tasks
        $from   .= ' LEFT JOIN  {assigned} ass      ON t.task_id = ass.task_id ';
        $from   .= ' LEFT JOIN  {users} u           ON ass.user_id = u.user_id ';
        if (array_get($args, 'dev') || in_array('assignedto', $visible)) {
            $select .= ' MIN(u.real_name)               AS assigned_to_name, ';
            $select .= ' COUNT(DISTINCT ass.user_id)    AS num_assigned, ';
        }
		/**/
		// Nux: PostgreSQL version
        if (array_get($args, 'dev') || in_array('assignedto', $visible)) {
            $select .= ' array_to_string (array(select u.real_name from {assigned} ass LEFT JOIN {users} u ON ass.user_id = u.user_id WHERE t.task_id = ass.task_id), \', \')  AS assigned_to_name, ';
            //$select .= ' (select COUNT(*) from {assigned} ass WHERE t.task_id = ass.task_id)    AS num_assigned, ';
			// to avoid +1 thingies
            $select .= ' 1    AS num_assigned, ';
        }
		
        if (array_get($args, 'only_primary')) {
            $from   .= ' LEFT JOIN  {dependencies} dep  ON dep.dep_task_id = t.task_id ';
            $where[] = 'dep.depend_id IS NULL';
        }
        if (array_get($args, 'has_attachment')) {
            $where[] = 'att.attachment_id IS NOT NULL';
        }

        if (array_get($args, 'only_primary')) {
            $from   .= ' LEFT JOIN  {dependencies} dep  ON dep.dep_task_id = t.task_id ';
            $where[] = 'dep.depend_id IS NULL';
        }

		// Nux: do not show tasks that were fixed in the same version as reported
        if (!empty($args['ignore_same_version_fixed'])) {
            $where[] = 't.product_version!=t.closedby_version';
        }

        if ($proj->id) {
            $where[]       = 't.project_id = ?';
            $sql_params[]  = $proj->id;
        }

        $order_keys = array (
                'id'           => 't.task_id',
                'project'      => 'project_title',
                'tasktype'     => 'tasktype_name',
                'dateopened'   => 'date_opened',
                'summary'      => 'item_summary',
                'severity'     => 'task_severity',
                'category'     => 'lc.category_name',
                'status'       => 'is_closed, item_status',
                'dueversion'   => 'lvc.list_position',
                'duedate'      => 'due_date',
                'progress'     => 'percent_complete',
                'lastedit'     => 'max_date',
                'priority'     => 'task_priority',
                'openedby'     => 'uo.real_name',
                'reportedin'   => 't.product_version',
                //'assignedto'   => 'u.real_name',
				// Nux: after PGSQL fix
                'assignedto'   => 'assigned_to_name',
                'dateclosed'   => 't.date_closed',
                'os'           => 'los.os_name',
                'votes'        => 'num_votes',
                'attachments'  => 'num_attachments',
                'comments'     => 'num_comments',
        );

        // make sure that only columns can be sorted that are visible (and task severity, since it is always loaded)
        $order_keys = array_intersect_key($order_keys, array_merge(array_flip($visible), array('severity' => 'task_severity')));

		$default_order = array('priority', 'severity', 'severity');
		/*
        $order_column[0] = $order_keys[Filters::enum(array_get($args, 'order', $default_order[0]), array_keys($order_keys))];
        $order_column[1] = $order_keys[Filters::enum(array_get($args, 'order2', $default_order[1]), array_keys($order_keys))];
        $order_column[2] = $order_keys[Filters::enum(array_get($args, 'order3', $default_order[2]), array_keys($order_keys))];
		*/
		// array_get działa nie tak jak trzeba przy pustych wartościach
		function nux_array_get(&$array, $key, $default = null)
		{
			return (!empty($array[$key])) ? $array[$key] : $default;
		}
		$default_order_i = 0;
        $order_column[0] = $order_keys[Filters::enum(nux_array_get($args, 'order', $default_order[$default_order_i]), array_keys($order_keys))];
		// zwiększ licznik, jeśli wartość wykorzystana (argument był pusty)
		if (nux_array_get($args, 'order')==null)
		{
			$default_order_i++;
		}
        $order_column[1] = $order_keys[Filters::enum(nux_array_get($args, 'order2', $default_order[$default_order_i]), array_keys($order_keys))];
		// zwiększ licznik, jeśli wartość wykorzystana (argument był pusty)
		if (nux_array_get($args, 'order2')==null)
		{
			$default_order_i++;
		}
        $order_column[2] = $order_keys[Filters::enum(nux_array_get($args, 'order3', $default_order[$default_order_i]), array_keys($order_keys))];

        $sortorder  = sprintf('%s %s, %s %s, %s %s, t.task_id ASC'
			, $order_column[0], Filters::enum(nux_array_get($args, 'sort', 'desc'), array('asc', 'desc'))
			, $order_column[1], Filters::enum(nux_array_get($args, 'sort2', 'desc'), array('asc', 'desc'))
			, $order_column[2], Filters::enum(nux_array_get($args, 'sort3', 'desc'), array('asc', 'desc'))
		);

        /// process search-conditions {{{
        $submits = array('type' => 'task_type', 'sev' => 'task_severity', 'due' => 'closedby_version', 'reported' => 'product_version',
                         'cat' => 'product_category', 'status' => 'item_status', 'percent' => 'percent_complete', 'pri' => 'task_priority',
						 // Nux: PG SQL 8.4+ will not allow LIKE for int (hence casting is needed)
                         'dev' => array('cast(a.user_id as varchar)', 'us.user_name', 'us.real_name'),
                         'opened' => array('cast(opened_by as varchar)', 'uo.user_name', 'uo.real_name'),
                         'closed' => array('cast(closed_by as varchar)', 'uc.user_name', 'uc.real_name'));
        foreach ($submits as $key => $db_key) {
            $type = array_get($args, $key, ($key == 'status') ? 'open' : '');
            settype($type, 'array');

            if (in_array('', $type)) continue;

            if ($key == 'dev') {
                $from .= 'LEFT JOIN {assigned} a  ON t.task_id = a.task_id ';
                $from .= 'LEFT JOIN {users} us  ON a.user_id = us.user_id ';
            }

            $temp = '';
            $condition = '';
            foreach ($type as $val) {
                // add conditions for the status selection
                if ($key == 'status' && $val == 'closed' && !in_array('open', $type)) {
                    $temp  .= " is_closed = '1' AND";
                } elseif ($key == 'status' && !in_array('closed', $type)) {
                    $temp .= " is_closed <> '1' AND";
                }
                if (is_numeric($val) && !is_array($db_key) && !($key == 'status' && $val == 'closed')) {
                    $temp .= ' ' . $db_key . ' = ?  OR';
                    $sql_params[] = $val;
                } elseif (is_array($db_key)) {
                    if ($key == 'dev' && ($val == 'notassigned' || $val == '0' || $val == '-1')) {
                        $temp .= ' a.user_id is NULL  OR';
                    } else {
                        if (is_numeric($val)) {
                            $condition = ' = ? OR';
                        } else {
                           $val = '%' . $val . '%';
                           $condition = ' LIKE ? OR';
                        }
                        foreach ($db_key as $value) {
                            $temp .= ' ' . $value . $condition;
                            $sql_params[] = $val;
                        }
                    }
                }

                // Add the subcategories to the query
                if ($key == 'cat') {
                    $result = $db->Query('SELECT  *
                                            FROM  {list_category}
                                           WHERE  category_id = ?',
                                          array($val));
                    $cat_details = $db->FetchRow($result);

                    $result = $db->Query('SELECT  *
                                            FROM  {list_category}
                                           WHERE  lft > ? AND rgt < ? AND project_id  = ?',
                                           array($cat_details['lft'], $cat_details['rgt'], $cat_details['project_id']));
                    while ($row = $db->FetchRow($result)) {
                        $temp  .= ' product_category = ?  OR';
                        $sql_params[] = $row['category_id'];
                    }
                }
            }

            if ($temp) $where[] = '(' . substr($temp, 0, -3) . ')';
        }
        /// }}}
        $having = array();
        $dates = array('duedate' => 'due_date', 'changed' => $maxdatesql,
                       'opened' => 'date_opened', 'closed' => 'date_closed');
        foreach ($dates as $post => $db_key) {
            $var = ($post == 'changed') ? 'having' : 'where';
            if ($date = array_get($args, $post . 'from')) {
                ${$var}[]      = '(' . $db_key . ' >= ' . Flyspray::strtotime($date) . ')';
            }
            if ($date = array_get($args, $post . 'to')) {
                ${$var}[]      = '(' . $db_key . ' <= ' . Flyspray::strtotime($date) . ' AND ' . $db_key . ' > 0)';
            }
        }

        if (array_get($args, 'string')) {
            $words = explode(' ', strtr(array_get($args, 'string'), '()', '  '));
            $comments = '';
            $where_temp = array();

            if (array_get($args, 'search_in_comments')) {
                $comments .= 'OR c.comment_text ILIKE ?';
            }
            if (array_get($args, 'search_in_details')) {
                $comments .= 'OR t.detailed_desc ILIKE ?';
            }

            foreach ($words as $word) {
                $word = '%' . str_replace('+', ' ', trim($word)) . '%';
                $where_temp[] = "(t.item_summary ILIKE ? OR CAST(t.task_id as varchar) LIKE ? $comments)";
                array_push($sql_params, $word, $word);
                if (array_get($args, 'search_in_comments')) {
                    array_push($sql_params, $word);
                }
                if (array_get($args, 'search_in_details')) {
                    array_push($sql_params, $word);
                }
            }

            $where[] = '(' . implode( (array_get($args, 'search_for_all') ? ' AND ' : ' OR '), $where_temp) . ')';
        }

        if (array_get($args, 'only_watched')) {
            //join the notification table to get watched tasks
			// Nux: also add {assigned} because effectively they also watch tasks
            $from        .= "\n LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id  LEFT JOIN {assigned} ass2 ON t.task_id = ass2.task_id ";
            $where[]      = ' (fsn.user_id = ? or ass2.user_id = ?) ';
            $sql_params[] = $user->id;
            $sql_params[] = $user->id;
        }

        $where = (count($where)) ? 'WHERE '. join(' AND ', $where) : '';

        // Get the column names of table tasks for the group by statement
        if (!strcasecmp($conf['database']['dbtype'], 'pgsql')) {
             $groupby .= "p.project_title, p.project_is_active, lst.status_name, lt.tasktype_name, lr.resolution_name, ";
			 for ($pv_i=0; $pv_i<count($order_column); $pv_i++)
			 {
				if ($order_column[$pv_i]!='max_date')
				{
					$groupby .= "{$order_column[$pv_i]}, ";
				}
			 }
             $groupby .= $db->GetColumnNames('{tasks}', 't.task_id', 't.');
			 // echo $groupby;
        } else {
            $groupby = 't.task_id';
        }

        $having = (count($having)) ? 'HAVING '. join(' AND ', $having) : '';

        $sql = $db->Query("
                          SELECT   t.*, $select
                                   p.project_title, p.project_is_active,
                                   lst.status_name AS status_name,
                                   lt.tasktype_name AS task_type,
                                   lr.resolution_name
                          FROM     $from
                          $where
                          GROUP BY $groupby
                          $having
                          ORDER BY $sortorder", $sql_params);
        /**
		// Nux test
		if ($user->perms('is_admin')) {
			echo "<textarea>
                          SELECT   t.*, $select
                                   p.project_title, p.project_is_active,
                                   lst.status_name AS status_name,
                                   lt.tasktype_name AS task_type,
                                   lr.resolution_name
                          FROM     $from
                          $where
                          GROUP BY $groupby
                          $having
  ORDER BY $sortorder
  
  ---------------------------
  ".var_export($sql_params, true)."
				</textarea>";
		}
		/**/
		
        $tasks = $db->fetchAllArray($sql);
        $id_list = array();
        $limit = array_get($args, 'limit', -1);
        $task_count = 0;
        foreach ($tasks as $key => $task) {
			/**
			// old
			$id_list[] = $task['task_id'];
			if (!$user->can_view_task($task)) {
				unset($tasks[$key]);
				array_pop($id_list);
				--$task_count;
			} elseif (!is_null($perpage) && ($task_count < $offset || ($task_count > $offset - 1 + $perpage) || ($limit > 0 && $task_count >= $limit))) {
				unset($tasks[$key]);
			}

			++$task_count;		
			/**/
			// Nux: double tasks fix, shorter algorithm
            if (in_array($task['task_id'], $id_list) || (!$user->can_view_task($task)))
			{
				// integrate rows (shouldn't be needed any more)
				/*
				if (in_array($task['task_id'], $id_list))
				{
					$prev_key = array_search($task['task_id'], $id_list);
					$tasks[$prev_key]['assigned_to_name'] .= ', '. $task['assigned_to_name'];
					//$tasks[$prev_key]['num_assigned'] += $task['num_assigned'];
				}
				*/
				// del
				unset($tasks[$key]);
			}
			else
			{
				$id_list[$key] = $task['task_id'];
				//++$task_count;
				if (!is_null($perpage) && ($task_count < $offset || ($task_count > $offset - 1 + $perpage) || ($limit > 0 && $task_count >= $limit)))
				{
					unset($tasks[$key]);
				}
				++$task_count; // musi byc po bo inaczej zle zlicza dla pierwszej strony 
			}
			/**/
        }

        return array($tasks, $id_list);
    }

}
?>
