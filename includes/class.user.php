<?php

class User
{
    var $id = -1;
	var $domainAccount = '';	// only non-empty when logged in with domain auth
    var $perms = array();
    var $infos = array();
    var $searches = array();
	// JS: copy(location.search.split('&').map(param => param.replace(/(^\?|=.*|\[\]|%5B%5D)/g, '')))
    var $search_keys = array('string', 'type', 'sev', 'pri', 'due', 'dev', 'cat', 'status', 'order', 'sort', 'percent', 'changedfrom', 'closedfrom',
                             'opened', 'closed', 'search_in_comments', 'search_for_all', 'reported', 'only_primary', 'only_watched', 'closedto',
                             'changedto', 'duedatefrom', 'duedateto', 'openedfrom', 'openedto', 'has_attachment',
                             'search_in_details',
                             'order2', 'sort2',
                             'unchangedwithindays', 'unchangedwithinhours', 
                             'tags', 'projects',
	);
    function __construct($uid = 0)
    {
        global $db;

		// Nux: domain auth : START
		// Note! Some changes also in links.tpl
		/**/
        if ($uid <= 0 
			&& !empty($_SERVER["HTTPS"])  && $_SERVER["HTTPS"]=='on'
			&& !empty($_SERVER["AUTHENTICATE_SAMACCOUNTNAME"])
		) {
			$result = $db->Query('SELECT user_id FROM {users} u WHERE u.SAMACCOUNTNAME = ?',
                                array($_SERVER["AUTHENTICATE_SAMACCOUNTNAME"]));
	        if ($db->countRows($result)) {
				$row = $db->FetchRow($result);
				$uid = intval($row['user_id']);
		        if ($uid > 0) {
					$this->domainAccount = $_SERVER["AUTHENTICATE_SAMACCOUNTNAME"];
					
					// trick for this to work: Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')
					Flyspray::setcookie('flyspray_userid', -1, 0);
					Flyspray::setcookie('flyspray_passhash', 'X', 0);
				}
				unset($row);
			}
			unset($result);
		}
		/**/
		// Nux: domain auth : END

        if ($uid > 0) {
            $sql = $db->Query('SELECT *, g.group_id AS global_group, uig.record_id AS global_record_id
                                 FROM {users} u, {users_in_groups} uig, {groups} g
                                WHERE u.user_id = ? AND uig.user_id = ? AND g.project_id = 0
                                      AND uig.group_id = g.group_id',
                                array($uid, $uid));
        }

        if ($uid > 0 && $db->countRows($sql)) {
            $this->infos = $db->FetchRow($sql);
            $this->id = intval($uid);
        } else {
            $this->infos['real_name'] = L('anonuser');
            $this->infos['user_name'] = '';
        }

        $this->get_perms();
    }

    /**
     * save_search
     *
     * @param string $do
     * @access public
     * @return void
     * @notes FIXME: must return something, should not merge _GET and _REQUEST with other stuff.
     */
    function save_search($do = 'index')
    {
        global $db;

        if($this->isAnon()) {
            return;
        }

        // Only logged in users get to use the 'last search' functionality
        if ($do == 'index') {
            $arr = array();
            foreach ($this->search_keys as $key) {
                $arr[$key] = Get::val($key);
            }

            if (Get::val('search_name')) {
                $fields = array('search_string'=> serialize($arr), 'time'=> time(),
                                'user_id'=> $this->id , 'name'=> Get::val('search_name'));

                $keys = array('name','user_id');

                $db->Replace('{searches}', $fields, $keys);
            }
        }

        $sql = $db->Query('SELECT * FROM {searches} WHERE user_id = ? ORDER BY name ASC', array($this->id));
        $this->searches = $db->FetchAllArray($sql);
    }

    function perms($name, $project = null) {
        if (is_null($project)) {
            global $proj;
            $project = $proj->id;
        }

        if (isset($this->perms[$project][$name])) {
            return $this->perms[$project][$name];
        } else {
            return 0;
        }
    }

    function get_perms()
    {
        global $db, $fs;

        $fields = array('is_admin', 'manage_project', 'view_tasks', 'edit_own_comments',
                'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                'view_comments', 'add_comments', 'edit_comments', 'edit_assignments',
                'delete_comments', 'create_attachments',
                'delete_attachments', 'view_history', 'close_own_tasks',
                'close_other_tasks', 'assign_to_self', 'assign_others_to_self',
                'add_to_assignees', 'view_reports', 'add_votes', 'group_open');

        $this->perms = array(0 => array());
        // Get project settings which are important for permissions
        $sql = $db->Query('SELECT project_id, others_view, project_is_active, anon_open, comment_closed
                             FROM {projects}');
        while ($row = $db->FetchRow($sql)) {
            $this->perms[$row['project_id']] = $row;
        }
        // Fill permissions for global project
        $this->perms[0] = array_map(create_function('$x', 'return 1;'), end($this->perms));

        if (!$this->isAnon()) {
            // Get the global group permissions for the current user
            $sql = $db->Query("SELECT  ".join(', ', $fields).", g.project_id, uig.record_id,
                                       g.group_open, g.group_id AS project_group
                                 FROM  {groups} g
                            LEFT JOIN  {users_in_groups} uig ON g.group_id = uig.group_id
                            LEFT JOIN  {projects} p ON g.project_id = p.project_id
                                WHERE  uig.user_id = ?
                             ORDER BY  g.project_id, g.group_id ASC",
                                array($this->id));

            while ($row = $db->FetchRow($sql)) {
                if (!isset($this->perms[$row['project_id']])) {
                    // should not happen, so clean up the DB
                    $db->Query('DELETE FROM {users_in_groups} WHERE record_id = ?', array($row['record_id']));
                    continue;
                }

                $this->perms[$row['project_id']] = array_merge($this->perms[$row['project_id']], $row);
            }

            // Set missing permissions and attachments
            foreach ($this->perms as $proj_id => $value) {
                foreach ($fields as $key) {
                    if ($key == 'project_group') {
                        continue;
                    }

                    $this->perms[$proj_id][$key] = max($this->perms[0]['is_admin'], @$this->perms[$proj_id][$key], $this->perms[0][$key]);
                }

                // nobody can upload files if uploads are disabled at the system level..
                if (!$fs->max_file_size || !is_writable(BASEDIR .'/attachments')) {
                    $this->perms[$proj_id]['create_attachments'] = 0;
                }
            }
        }
		// Nux: allow anon to upload files
		else
		{
            foreach ($this->perms as $proj_id => $value) {
				$this->perms[$proj_id]['create_attachments'] = 1;
				// nobody can upload files if uploads are disabled at the system level..
				if (!$fs->max_file_size || !is_writable(BASEDIR .'/attachments')) {
					$this->perms[$proj_id]['create_attachments'] = 0;
				}
			}
		}
    }

    function check_account_ok()
    {
        global $conf;
        // Anon users are always OK
        if ($this->isAnon()) {
            return;
        }
		// Nux: domain always OK too
		if (!empty($this->domainAccount)) {
			return;
		}
		//
        $saltedpass = md5($this->infos['user_pass'] . $conf['general']['cookiesalt']);
		
        if (Cookie::val('flyspray_passhash') !== $saltedpass || !$this->infos['account_enabled']
                || !$this->perms('group_open', 0))
        {
            $this->logout();
            Flyspray::Redirect(CreateURL('index'));
        }
    }

    function isAnon()
    {
        return $this->id < 0;
    }

    /* }}} */
    /* permission related {{{ */

    function can_edit_comment($comment)
    {
        return $this->perms('edit_comments')
               || ($comment['user_id'] == $this->id && $this->perms('edit_own_comments'));
    }

    function can_view_project($proj)
    {
        if (is_array($proj) && isset($proj['project_id'])) {
            $proj = $proj['project_id'];
        }

		// allow fake projects in the list (divider)
		if ($proj == -1) {
			return true;
		}

        return $this->perms('view_tasks', $proj)
          || ($this->perms('project_is_active', $proj)
              && ($this->perms('others_view', $proj) || $this->perms('project_group', $proj)));
    }

    function can_view_task($task)
    {
        if ($task['task_token'] && Get::val('task_token') == $task['task_token']) {
            return true;
        }

        if ($task['opened_by'] == $this->id && !$this->isAnon()
            || (!$task['mark_private'] && ($this->perms('view_tasks', $task['project_id']) || $this->perms('others_view', $task['project_id'])))
            || $this->perms('manage_project', $task['project_id'])) {
            return true;
        }

        return !$this->isAnon() && in_array($this->id, Flyspray::GetAssignees($task['task_id']));
    }

    function can_edit_task($task, $ignore_closed_status=false)
    {
        return (!$task['is_closed'] || $ignore_closed_status)
            && ($this->perms('modify_all_tasks', $task['project_id']) ||
                    ($this->perms('modify_own_tasks', $task['project_id'])
                     && in_array($this->id, Flyspray::GetAssignees($task['task_id']))));
    }

    function can_take_ownership($task)
    {
        $assignees = Flyspray::GetAssignees($task['task_id']);

        return ($this->perms('assign_to_self', $task['project_id']) && empty($assignees))
               || ($this->perms('assign_others_to_self', $task['project_id']) && !in_array($this->id, $assignees));
    }

    function can_add_to_assignees($task)
	 {
        return ($this->perms('add_to_assignees', $task['project_id']) && !in_array($this->id, Flyspray::GetAssignees($task['task_id'])));
    }

    function can_close_task($task)
    {
        return ($this->perms('close_own_tasks', $task['project_id']) && in_array($this->id, $task['assigned_to']))
                || $this->perms('close_other_tasks', $task['project_id']);
    }

    function can_self_register()
    {
        global $fs;
        return $this->isAnon() && !$fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_register()
    {
        global $fs;
        return $this->isAnon() && $fs->prefs['spam_proof'] && $fs->prefs['anon_reg'];
    }

    function can_open_task($proj)
    {
        return $proj->id && ($this->perms('manage_project') ||
                 $this->perms('project_is_active', $proj->id) && ($this->perms('open_new_tasks') || $this->perms('anon_open', $proj->id)));
    }

    function can_change_private($task)
    {
        return !$task['is_closed'] && ($this->perms('manage_project', $task['project_id']) || in_array($this->id, Flyspray::GetAssignees($task['task_id'])));
    }

    function can_vote($task)
    {
        global $db;

        if (!$this->perms('add_votes', $task['project_id'])) {
            return -1;
        }

        // Check that the user hasn't already voted this task
        $check = $db->Query('SELECT vote_id
                               FROM {votes}
                              WHERE user_id = ? AND task_id = ?',
                             array($this->id, $task['task_id']));
        if ($db->CountRows($check)) {
            return -2;
        }

        // Check that the user hasn't voted more than twice this day
        $check = $db->Query('SELECT vote_id
                               FROM {votes}
                              WHERE user_id = ? AND date_time > ?',
                             array($this->id, time() - 86400));
        if ($db->CountRows($check) > 2) {
            return -3;
        }

        return 1;
    }

    function logout()
    {
        // Set cookie expiry time to the past, thus removing them
        Flyspray::setcookie('flyspray_userid',   '', time()-60, true);
        Flyspray::setcookie('flyspray_passhash', '', time()-60, true);
        Flyspray::setcookie('flyspray_project',  '', time()-60);
        if (Cookie::has(session_name())) {
            Flyspray::setcookie(session_name(), '', time()-60);
        }

        // Unset all of the session variables.
        $_SESSION = array();
        session_destroy();

        return !$this->isAnon();
    }

    /* }}} */
}

?>