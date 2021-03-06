<?php
$prev_copytask_id = '';
/*	  <?php if (!empty($prev_copytask_id)): > <span>({L('previoustask')} {$prev_copytask_id})</span> <?php endif; >*/
if (strpos($_SERVER['HTTP_REFERER'], 'copytask&task_id')!==false)
{
	preg_match('/copytask\&task_id=([0-9]+)/', $_SERVER['HTTP_REFERER'], $matches);
	if ($matches)
	{
		$prev_copytask_id = $matches[1];
	}
}
?>
<div id="related" class="tab">
  <table> <?php // table based layout, sorry. if anyone has the desire to face browser bugs, feel free to rewrite it with floats ?>
   <tr><td>
    <form method="post" action="{CreateUrl('details', $task_details['task_id'])}#related" >
        <table id="tasks_related" class="userlist">
        <tr>
          <th>
            <a href="javascript:ToggleSelected('tasks_related')">
              <img title="{L('toggleselected')}" alt="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
            </a>
          </th>
          <th>{L('tasksrelated')} ({count($related)})</th>
        </tr>
        <?php
          foreach ($related as $row):
        ?>
        <tr>
          <td class="ttcolumn">
            <input type="checkbox" name="related_id[]" {!tpl_disableif(!$user->can_edit_task($task_details, true))} value="{$row['related_id']}" /></td>
          <td>{!tpl_tasklink($row)}</td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="2">
            <input type="hidden" name="action" value="remove_related" />
            <input type="hidden" name="task_id" value="{$task_details['task_id']}" />
            <button type="submit">{L('remove')}</button>
          </td>
        </tr>
        </table>
    </form>
    </td><td>
    
    <table id="duplicate_tasks" class="userlist">
        <tr>
          <th>{L('duplicatetasks')} ({count($duplicates)})</th>
        </tr>
        <?php foreach ($duplicates as $row): ?>
        <tr><td>{!tpl_tasklink($row)}</td></tr>
        <?php endforeach; ?>
    </table>
    </td></tr>
  </table>

  <?php if ($user->can_edit_task($task_details, true) /*&& !$task_details['is_closed']*/): ?>
  <form action="{CreateUrl('details', $task_details['task_id'])}#related" method="post" id="formaddrelatedtask">
    <div>
      <input type="hidden" name="action" value="details.add_related" />
      <input type="hidden" name="task_id" value="{$task_details['task_id']}" />
      <label>{L('addnewrelated')}
        <input name="related_task" id="related_task_input" type="text" class="text" size="10" maxlength="10" value="{$prev_copytask_id}" onchange="this.value = this.value.replace(/^\s*[A-Z]+#/, '')" /></label>
      <button type="submit" onclick="return checkok('{$baseurl}javascript/callbacks/checkrelated.php?related_task=' + $('related_task_input').value + '&amp;project={$proj->id}', '{#L('relatedproject')}', 'formaddrelatedtask')">{L('add')}</button>
    </div>
  </form>
  <?php endif; ?>
</div>
