<?php if (!$task_details['is_closed']): ?>
  <div id="remind" class="tab">
  
  <form method="post" action="{CreateUrl('details', $task_details['task_id'])}#remind" >
    <table id="reminders" class="userlist">
    <tr>
      <th>
        <a href="javascript:ToggleSelected('reminders')">
          <img title="{L('toggleselected')}" alt="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
        </a>
      </th>
      <th>{L('user')}</th>
      <th>{L('startat')}</th>
      <th>{L('frequency')}</th>
      <th>{L('message')}</th>
    </tr>

    <?php foreach ($reminders as $row): ?>
    <tr>
      <td class="ttcolumn">
        <input type="checkbox" name="reminder_id[]" {!tpl_disableif(!$user->can_edit_task($task_details))} value="{$row['reminder_id']}" />
      </td>
     <td>{!tpl_userlink($row['user_id'])}</td>
     <td>{formatDate($row['start_time'])}</td>
     <?php
	  function formatLocalHowOften($number) {
	    global $proj;
		$decimals = 2;
		$decimal_separator = $proj->prefs['lang_code'] == 'en' ? '.' : ',';
		return number_format($number, $decimals, $decimal_separator, '');
	  }
      // Work out the unit of time to display
      if ($row['how_often'] < 86400) {
          $how_often = formatLocalHowOften($row['how_often'] / 3600) . ' ' . L('hours');
      } elseif ($row['how_often'] < 7 * 86400) {
          $how_often = formatLocalHowOften($row['how_often'] / 86400) . ' ' . L('days');
      } else {
          $how_often = formatLocalHowOften($row['how_often'] / 604800) . ' ' . L('weeks');
      }
     ?>
     <td>{$how_often}</td>
     <td>{!TextFormatter::render($row['reminder_message'], true)}</td>
  </tr>
    <?php endforeach; ?>
    <tr><td colspan="4">
      <input type="hidden" name="action" value="deletereminder" />
      <input type="hidden" name="task_id" value="{$task_details['task_id']}" />
      <button type="submit">{L('remove')}</button></td></tr>
    </table>
  </form>

  <fieldset><legend>{L('addreminder')}</legend>
  <form action="{CreateUrl('details', $task_details['task_id'])}#remind" method="post" id="formaddreminder">
    <div>
      <input type="hidden" name="action" value="details.addreminder" />
      <input type="hidden" name="task_id" value="{$task_details['task_id']}" />

        <label class="default multisel" for="to_user_id">{L('remindthisuser')}</label>
        {!tpl_userselect('to_user_id', Req::val('to_user_id', $user->infos['user_name']), 'to_user_id', array('required' => 'true'))}
      <br />

      <label for="timeamount1">{L('thisoften')}</label>
      <input class="text" type="number" value="{Req::val('timeamount1')}" id="timeamount1" name="timeamount1" size="4" min="1" max="999" />
      <select class="adminlist" name="timetype1">
        {!tpl_options(array(3600 => L('hours'), 86400 => L('days'), 604800 => L('weeks')), Req::val('timetype1', 86400))}
      </select>

      <br />

      {!tpl_datepicker('timeamount2', L('startat'), Req::val('timeamount2', date('Y-m-d', strtotime('+2 day'))), array('required' => 'true', 'pattern' => '\d+-\d\d-\d\d'))}

      <br />
      <textarea class="text" name="reminder_message"
        rows="10" cols="72">{Req::val('reminder_message', L('defaultreminder') 
		. "\n\n" 
		. $task_details['item_summary']
		. " (".$task_details['due_in_version_name'].")"
		. "\n" 
		. CreateURL('details', $task_details['task_id'])
		. "\n\n"
		. L('duedate') . ": "
		. formatDate($task_details['due_date'], false, L('undecided'))
		)
		}</textarea>
      <br />
      <button type="submit">{L('addreminder')}</button>
    </div>
  </form>
  </fieldset>
</div>
<?php endif; ?>
