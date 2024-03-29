<?php

class Project
{
    var $id = 0;
    var $prefs = array();

    function __construct($id)
    {
        global $db, $fs;

        if (is_numeric($id)) {
            $sql = $db->Query("SELECT p.*, c.content AS pm_instructions, c.last_updated AS cache_update
                                 FROM {projects} p
                            LEFT JOIN {cache} c ON c.topic = cast(p.project_id as varchar) AND c.type = 'msg'
                                WHERE p.project_id = ?", array($id));
            if ($db->countRows($sql)) {
                $this->prefs = $db->FetchRow($sql);
                $this->id    = (int) $this->prefs['project_id'];
                return;
            }
        }

        $this->id = 0;
        $this->prefs['project_title'] = L('allprojects');
        $this->prefs['feed_description']  = L('feedforall');
        $this->prefs['theme_style']   = $fs->prefs['global_theme'];
        $this->prefs['lang_code']   = $fs->prefs['lang_code'];
        $this->prefs['project_is_active'] = 1;
        $this->prefs['others_view'] = 1;
        $this->prefs['intro_message'] = '';
        $this->prefs['anon_open'] = 0;
        $this->prefs['feed_img_url'] = '';
        $this->prefs['default_entry'] = 'index';
        $this->prefs['notify_reply'] = '';
    }

    function setCookie()
    {
        Flyspray::setCookie('flyspray_project', $this->id);
    }

    /* cached list functions {{{ */

    // helpers {{{

    function _pm_list_sql($type, $join)
    {
        global $db;

        // deny the possibility of shooting ourselves in the foot.
        // although there is no risky usage atm, the api should never do unexpected things.
        if(preg_match('![^A-Za-z0-9_]!', $type)) {
            return '';
        }
        //Get the column names of list tables for the group by statement
        $groupby = $db->GetColumnNames('{list_' . $type . '}',  'l.' . $type . '_id', 'l.');

        $join = 't.'.join(" = l.{$type}_id OR t.", $join)." = l.{$type}_id";

        return "SELECT  l.*, count(t.task_id) AS used_in_tasks
                  FROM  {list_{$type}} l
             LEFT JOIN  {tasks}        t  ON ($join)
                            AND t.project_id = l.project_id
                 WHERE  l.project_id = ?
              GROUP BY  $groupby
              ORDER BY  list_position";
    }

    /**
     * _list_sql
     *
     * @param mixed $type
     * @param mixed $where
     * @access protected
     * @return string
     * @notes The $where parameter is dangerous, think twice what you pass there..
     */

    function _list_sql($type, $where = null)
    {
        // sanity check.
        if(preg_match('![^A-Za-z0-9_]!', $type)) {
            return '';
        }

		$columns = "{$type}_id, {$type}_name";
		// Nux: Added order by project id (this makes common categories/systems land on top of lists)
		$order = "project_id DESC, list_position";
		// tags are more complicated...
		if ($type === 'tag') {
			$columns .= ", {$type}_group";
			$order = "{$type}_group, $order";
		}

        return "SELECT  $columns
                  FROM  {list_{$type}}
                 WHERE  show_in_list = 1 AND ( project_id = ? OR project_id = 0 )
                        $where
              ORDER BY  $order";
    }

    // }}}
    // PM dependant functions {{{

	/**
	 * Standard tag list.
	 * 
	 * @global Database $db
	 * @param bool $pm If true then a list for project management is returned.
	 * @return array
	 */
    function listTags($pm = false)
    {
        global $db;
        if ($pm) {
			// must NOT use _pm_list_sql, as it doesn't work for many to many assoc.
            return $db->cached_query(
					'pm_tag',
					"SELECT  l.*, count(a.tag_id) AS used_in_tasks
						FROM  {list_tag} l
						LEFT JOIN  {tag_assignment} a ON (a.tag_id = l.tag_id)
						WHERE  l.project_id = ?
					GROUP BY  l.tag_id, l.tag_group, l.tag_name, l.project_id, l.list_position, l.show_in_list
					ORDER BY  tag_group, list_position",
					array($this->id));
        } else {
            return $db->cached_query(
                    'tag', $this->_list_sql('tag'), array($this->id));
        }
    }

	/**
	 * Tags in arrays of groups array.
	 *
	 * This for dysplay in task forms (search, edit, add).
	 *
	 * @param bool $pm If true then a list for project management is returned.
	 * @return array
	 */
	function listGrouppedTags($pm = false)
	{
		$tags_flat = $this->listTags($pm);

		$current_group = '';
		$tags = array();
		foreach ($tags_flat as $tag) {
			if ($current_group != $tag['tag_group']) {
				$current_group = $tag['tag_group'];
				$tags[$current_group] = array(
					'code' => $this->_getTagGroupCode($tag['tag_group']),
					'name' => $tag['tag_group'],
					'tags' => array(),
				);
			}
			$tags[$current_group]['tags'][] = $tag;
		}

		return $tags;
	}

	/**
	 * Group identification code.
	 *
	 * Should be safe to use in HTML id attributes.
	 *
	 * @param string $group_name
	 * @return string
	 */
	function _getTagGroupCode($group_name) {
		return preg_replace('#[^a-zA-Z]#', '-', $group_name);
	}

    function listTaskTypes($pm = false)
    {
        global $db;
        if ($pm) {
            return $db->cached_query(
                    'pm_task_types',
                    $this->_pm_list_sql('tasktype', array('task_type')),
                    array($this->id));
        } else {
            return $db->cached_query(
                    'task_types', $this->_list_sql('tasktype'), array($this->id));
        }
    }

    function listOs($pm = false)
    {
        global $db;
        if ($pm) {
            return $db->cached_query(
                    'pm_os',
                    $this->_pm_list_sql('os', array('operating_system')),
                    array($this->id));
        } else {
            return $db->cached_query('os', $this->_list_sql('os'),
                    array($this->id));
        }
    }

    function listVersions($pm = false, $tense = null, $reported_version = null)
    {
        global $db;

        $params = array($this->id);

        if (is_null($tense)) {
            $where = '';
        } else if (strpos($tense, ',')!==false) {
            $where = 'AND version_tense IN ('.$tense.')';
//            $params[] = implode(',', $tense);
        } else {
            $where = 'AND version_tense = ?';
            $params[] = $tense;
        }

        if ($pm) {
            return $db->cached_query(
                    'pm_version',
                    $this->_pm_list_sql('version', array('product_version', 'closedby_version')),
                    array($params[0]));
        } elseif (is_null($reported_version)) {
            return $db->cached_query(
                    'version_'.$tense,
                    $this->_list_sql('version', $where),
                    $params);
        } else {
            $params[] = $reported_version;
            return $db->cached_query(
                    'version_'.$tense,
                    $this->_list_sql('version', $where . ' OR version_id = ?'),
                    $params);
        }
    }

	/**
	 * Folded list categories tree.
	 * 
	 * To be used in select.
	 * Note that tpl_options() uses [0] as value and [1] as label.
	 * 
	 * @param bool $just_parent If true then only a parent name will be added to label ([1]).
	 * 	Defaults to adding all ancestors as label.
	 */
    function listCategoriesFoldedTree($project_id = null, $hide_hidden = true, $remove_root = true, $just_parent=false)
    {
		$tree = $this->listCategoriesTree($project_id, $hide_hidden, $remove_root);
		foreach ($tree as &$cat) {
			$cat[0] = $cat['category_id'];
			// sub-cats
			if (!empty($cat['parents'])) {

				if ($just_parent) {
					$cat[1] = $cat['parents'][0]['name'] .' → '. $cat['category_name'];
					if ($cat['depth'] > 1) {
						$cat[1] = str_repeat('... ', $cat['depth']-1) . $cat[1];
					}
				} else {
					$cat[1] = $cat['category_name'];
					$subcat = $cat['parents'];
					while (!empty($subcat)) {
						$cat[1] = $subcat[0]['name'] .' → '. $cat[1];
						$subcat = $subcat[0]['parents'];
					}
				}

				$cat[1] = '↳ ' . $cat[1];
			// main cats
			} else {
				$cat[1] = '** '. $cat['category_name'] . ' **';
			}
		}
		return $tree;
	}

	/**
	 * List categories tree.
	 * 
	 * Mostly the same as `listCategories`.
	 * But it adds `parents` key.
	 * 
	 * Note that this is a memory heavy version.
	 * 
	 * Example row/node (note that id will be a first level key here):
		91 => array (
			'category_id' => '91',
			'category_name' => 'GUI components',
			'project_id' => '15',
			'category_owner' => '0',
			'used_in_tasks' => '5',
			'depth' => 2,
			'parents' => array (
				array (
					'id' => '66',
					'name' => 'GUI',
					'depth' => 1,
					'parents' => array (
						array (
							'id' => '65',
							'name' => 'Generic (other)',
							'depth' => 0,
							'parents' => array (),
						),
					),
				),
			),
		),
	 */
    function listCategoriesTree($project_id = null, $hide_hidden = true, $remove_root = true)
    {
		$depth = false;
		$cats = $this->listCategories($project_id, $hide_hidden, $remove_root, $depth);

		$tree = array();
		$prev = null;
		$prevByDepth = array();
		foreach($cats as $cat) {
			$r = array(
				'category_id' => $cat['category_id'],
				'category_name' => $cat['category_name'],
				'project_id' => $cat['project_id'],
				'category_owner' => $cat['category_owner'],
				'used_in_tasks' => $cat['used_in_tasks'],
				'depth' => $cat['depth'],
				'parents' => array(),
			);
			if (!is_null($prev)) {
				if ($prev['depth'] > $r['depth']) {
					if (isset($prevByDepth[$r['depth']])) {
						$prev = $prevByDepth[$r['depth']];
					}
				}

				if ($prev['depth'] < $r['depth']) {
					$r['parents'][] = $prev;
				} else if ($prev['depth'] == $r['depth'] && !empty($prev['parents'])) {
					$r['parents'] = $prev['parents'];
				}
			}
			$tree[$r['category_id']] = $r;
	
			// lite version of the row/node
			$prev = array(
				'id' => $cat['category_id'],
				'name' => $cat['category_name'],
				'depth' => $cat['depth'],
				'parents' => $r['parents'],
			);
			$prevByDepth[$prev['depth']] = $prev;
		}
		return $tree;
	}

    function listCategories($project_id = null, $hide_hidden = true, $remove_root = true, $depth = true)
    {
        global $db, $conf;

        // start with a empty arrays
        $right = array();
        $cats = array();
        $g_cats = array();

        // null = categories of current project + global project, int = categories of specific project
        if (is_null($project_id)) {
            $project_id = $this->id;
            if ($this->id != 0) {
                $g_cats = $this->listCategories(0);
            }
        }

        // retrieve the left and right value of the root node
        $result = $db->Query("SELECT lft, rgt
                                FROM {list_category}
                               WHERE category_name = 'root' AND lft = 1 AND project_id = ?",
                             array($project_id));
        $row = $db->FetchRow($result);

        $groupby = $db->GetColumnNames('{list_category}', 'c.category_id', 'c.');

        // now, retrieve all descendants of the root node
        $result = $db->Query('SELECT c.category_id, c.category_name, c.*, count(t.task_id) AS used_in_tasks
                                FROM {list_category} c
                           LEFT JOIN {tasks} t ON (t.product_category = c.category_id)
                               WHERE c.project_id = ? AND lft BETWEEN ? AND ?
                            GROUP BY ' . $groupby . '
                            ORDER BY lft ASC',
                             array($project_id, intval($row['lft']), intval($row['rgt'])));

        while ($row = $db->FetchRow($result)) {
            if ($hide_hidden && !$row['show_in_list'] && $row['lft'] != 1) {
                continue;
            }

           // check if we should remove a node from the stack
           while (count($right) > 0 && $right[count($right)-1] < $row['rgt']) {
               array_pop($right);
           }
           $cats[] = $row + array('depth' => count($right)-1);

           // add this node to the stack
           $right[] = $row['rgt'];
        }

        // Adjust output for select boxes
        if ($depth) {
            foreach ($cats as $key => $cat) {
                if ($cat['depth'] > 0) {
                    $cats[$key]['category_name'] = str_repeat('...', $cat['depth']) . $cat['category_name'];
                    $cats[$key]['1'] = str_repeat('...', $cat['depth']) . $cat['1'];
                }
            }
        }

        if ($remove_root) {
            unset($cats[0]);
        }

        return array_merge($cats, $g_cats);
    }

    function listResolutions($pm = false)
    {
        global $db;
        if ($pm) {
            return $db->cached_query(
                    'pm_resolutions',
                    $this->_pm_list_sql('resolution', array('resolution_reason')),
                    array($this->id));
        } else {
            return $db->cached_query('resolution',
                    $this->_list_sql('resolution'), array($this->id));
        }
    }

    function listTaskStatuses($pm = false)
    {
        global $db;
        if ($pm) {
            return $db->cached_query(
                    'pm_statuses',
                    $this->_pm_list_sql('status', array('item_status')),
                    array($this->id));
        } else {
            return $db->cached_query('status',
                    $this->_list_sql('status'), array($this->id));
        }
    }

    // }}}

    function listUsersIn($group_id = null)
    {
        global $db;
        return $db->cached_query(
                'users_in'.(is_null($group_id) ? $group_id : intval($group_id)),
                "SELECT  u.*
                   FROM  {users}           u
             INNER JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
             INNER JOIN  {groups}          g   ON uig.group_id = g.group_id
                  WHERE  g.group_id = ?
               ORDER BY  u.user_name ASC",
                array($group_id));
    }

    function listAttachments($cid)
    {
        global $db;
        return $db->cached_query(
                'attach_'.intval($cid),
                "SELECT  *
                   FROM  {attachments}
                  WHERE  comment_id = ?
               ORDER BY  attachment_id ASC",
               array($cid));
    }

    function listTaskAttachments($tid)
    {
        global $db;
        return $db->cached_query(
                'attach_'.intval($tid),
                "SELECT  *
                   FROM  {attachments}
                  WHERE  task_id = ? AND comment_id = 0
               ORDER BY  attachment_id ASC",
               array($tid));
    }
    /* }}} */
}

?>
