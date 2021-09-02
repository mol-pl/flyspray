<?php
  /*********************************************************\
  | Show the roadmap                                        |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$proj->id) {
    Flyspray::show_error(25);
}

$page->setTitle($fs->prefs['page_title'] . L('roadmap'));

// Get milestones
if (Get::val('ver_id'))
{
	$milestones = $db->Query('SELECT   version_id, version_name
							  FROM     {list_version}
							  WHERE    project_id = ? AND version_id = ?',
							  array($proj->id, intval(Get::val('ver_id'))));
}
else
{
	$milestones = $db->Query('SELECT   version_id, version_name
							  FROM     {list_version}
							  WHERE    project_id = ? AND version_tense = 3
							  ORDER BY list_position ASC',
							  array($proj->id));
}
$data = array();

while ($row = $db->FetchRow($milestones)) {
    // Get all tasks related to a milestone
    $all_tasks = $db->Query('SELECT  percent_complete, is_closed
                             FROM    {tasks}
                             WHERE   closedby_version = ? AND project_id = ?',
                             array($row['version_id'], $proj->id));
    $all_tasks = $db->fetchAllArray($all_tasks);
    
    $percent_complete = 0;
    foreach($all_tasks as $task) {
        if($task['is_closed']) {
            $percent_complete += 100;
        } else {
            $percent_complete += $task['percent_complete'];
        }
    }
    $percent_complete = round($percent_complete/max(count($all_tasks), 1));
	
	// std
	$columns = 'task_id, item_summary, detailed_desc, task_severity, mark_private, opened_by, content, task_token, t.project_id, task_type, is_closed';
	// assigned (Postgre SQL only!)
	$columns .= ', array_to_string (array(select u.real_name from {assigned} ass LEFT JOIN {users} u ON ass.user_id = u.user_id WHERE t.task_id = ass.task_id), \', \') AS assigned_to_name';
	// status text
	$columns .= ', (SELECT lst.status_name FROM {list_status} lst WHERE t.item_status = lst.status_id) AS status_name';
	// category text
	if (Get::val('smp_htm')) {
		$columns .= ', (SELECT lst.category_name FROM {list_category} lst WHERE t.product_category = lst.category_id) AS category_name';
	}
	
	// order
	$order = '';
	if (Get::val('smp_htm')) {
		$order = 'ORDER BY  t.product_category';
	}
                         
	if (Get::val('status')=='any')
	{
		$tasks = $db->Query('SELECT '.$columns.'
							   FROM {tasks} t
						  LEFT JOIN {cache} ca ON (cast(t.task_id as varchar) = ca.topic AND ca.type = \'rota\' AND t.last_edited_time <= ca.last_updated)
							  WHERE closedby_version = ? AND t.project_id = ?
							  '.$order,
							 array($row['version_id'], $proj->id));
	}
	else if (Get::val('status')=='closed')
	{
		$tasks = $db->Query('SELECT '.$columns.'
							   FROM {tasks} t
						  LEFT JOIN {cache} ca ON (cast(t.task_id as varchar) = ca.topic AND ca.type = \'rota\' AND t.last_edited_time <= ca.last_updated)
							  WHERE closedby_version = ? AND t.project_id = ? AND is_closed != 0
							  '.$order,
							 array($row['version_id'], $proj->id));
	}
	else
	{
		$tasks = $db->Query('SELECT '.$columns.'
							   FROM {tasks} t
						  LEFT JOIN {cache} ca ON (cast(t.task_id as varchar) = ca.topic AND ca.type = \'rota\' AND t.last_edited_time <= ca.last_updated)
							  WHERE closedby_version = ? AND t.project_id = ? AND is_closed = 0
							  '.$order,
							 array($row['version_id'], $proj->id));
	}
    $tasks = $db->fetchAllArray($tasks);
    
    $data[] = array('id' => $row['version_id'], 'open_tasks' => $tasks, 'percent_complete' => $percent_complete,
                    'all_tasks' => $all_tasks, 'name' => $row['version_name']);
}

if (Get::val('txt')) {
    $page = new FSTpl;
    header('Content-Type: text/plain; charset=UTF-8');
    $page->uses('data', 'page');
    $page->display('roadmap.text.tpl');
    exit();
} else if (Get::val('smp_htm')) {
	// simple render
	function smp_render($txt) {
		$patterns = array (
			array(
				'from' => '/(^|\n)((?:  )+)[*-](.*)/',	// listy
				'to'   => function($matches){
					$level = substr($matches[2], 2);
					$level = str_replace("  ", '&nbsp;&nbsp;', $level);
					$content = $matches[3];
					return $matches[1].$level.'&bull;'.$content;
				},
			),
			array(
				'from' => '/(((^|\n) [ ]+.+)+)/',	// pre
				'to'   => function($matches){
					return '<pre>'.str_replace("\n", '', $matches[1]).'</pre>';
				},
			),
			array(
				'from' => '/\n/',	// nl2br
				'to'   => "<br/>\n",
			),
		);
		foreach ($patterns as $pattern) {
			$from = $pattern['from'];
			$to = $pattern['to'];
			if (is_callable($to)) {
				$txt = preg_replace_callback($from, $to, $txt);
			} else {
				$txt = preg_replace($from, $to, $txt);
			}
		}
		return $txt;
	}
	// preapre data
	function severity_sort($a, $b)
	{
		if ($a['task_severity'] == $b['task_severity'])
		{
			return ($a['task_id'] < $b['task_id']) ? -1 : 1;
		}
		return ($a['task_severity'] > $b['task_severity']) ? -1 : 1;
	}
	foreach($data as &$mile)
	{
		usort($mile['open_tasks'], "severity_sort");
		foreach($mile['open_tasks'] as $k=>&$task)
		{
			$comments = $db->Query('SELECT comment_text
								   FROM {comments} c
								  WHERE task_id = ?',
								 array($task['task_id']));
			$comments = $db->fetchAllArray($comments);
			$task['comments'] = $comments;
		}
	}

    $page = new FSTpl;
    $page->uses('data', 'page');
    $page->display('roadmap.smp.tpl');
    exit();
} else {
    $page->uses('data', 'page');
    $page->pushTpl('roadmap.tpl');
}
?>
