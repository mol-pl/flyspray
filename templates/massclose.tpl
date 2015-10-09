<div id="taskdetails">
	<!--
	<h2 class="summary severity{$task_details['task_severity']}">{$task_details['project_title']} - {FS_PREFIX_CODE}#{$task_details['task_id']} {$task_details['item_summary']}</h2>
	-->

	<h2 class="summary">Zamykanie zgłoszeń z {$task_details['project_title']} o poniższych danych</h2>
	<small>Na podstawie: {FS_PREFIX_CODE}#{$task_details['task_id']} {$task_details['item_summary']}</small>
	<p><strong>Uwaga!</strong> Zanim zamkniesz wiele zgłoszeń lepiej <a href="{$baseurl}/index.php?do=admin&area=prefs">wyłącz powiadomienia</a>. Pamiętaj, żeby je potem przywrócić.</p>
	<table>
		<tr>
			<th id="tasktype">{L('tasktype')}</th>
			<td headers="tasktype">{$task_details['tasktype_name']}</td>
		</tr>
		<tr>
			<th id="status">{L('status')}</th>
			<td headers="status">
				<?php if ($task_details['is_closed']): ?>
					{L('closed')}
				<?php else: ?>
					{$task_details['status_name']}
					<?php if ($reopened): ?>
						&nbsp; <strong class="reopened">{L('reopened')}</strong>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
		  <th id="dueversion">{L('dueinversion')}</th>
		  <td headers="dueversion">
			 <?php if ($task_details['due_in_version_name']): ?>
			 {$task_details['due_in_version_name']}
			 <?php else: ?>
			 {L('undecided')}
			 <?php endif; ?>
		  </td>
		</tr>
	</table>

	<?php if (Req::val('actiontmp')!='mass.close'): ?>
	<div id="closeform">
		<form action="{$baseurl}" method="post" id="formclosetask">
			<div>
				<!--
				<input type="hidden" name="do" value="massclose" />
				-->
				<input type="hidden" name="action" value="mass.close" />

				<input type="hidden" name="task_id" value="{$task_details['task_id']}" />
				<input type="hidden" name="task_type" value="{$task_details['task_type']}" />
				<input type="hidden" name="item_status" value="{$task_details['item_status']}" />
				<input type="hidden" name="closedby_version" value="{$task_details['closedby_version']}" />
				<input type="hidden" name="project_id" value="{$task_details['cproj']}" />

				<label class="default text" for="closure_comment" style="display:block">{L('closurecomment')}<br /><small>{L('closurecomment_duplicate')}</small></label>
				
				<textarea class="text" id="closure_comment" name="closure_comment" rows="3" cols="25">{Req::val('closure_comment')}</textarea>
				<?php if($task_details['percent_complete'] != '100'): ?>
					<label>{!tpl_checkbox('mark100', Req::val('mark100', !(Req::val('action') == 'details.close')))}&nbsp;&nbsp;{L('mark100')}</label>
				<?php endif; ?>
				<select class="adminlist" name="resolution_reason">
					<option value="0">{L('selectareason')}</option>
					{!tpl_options($proj->listResolutions(), Req::val('resolution_reason'))}
				</select>
				<button type="submit">{L('closetask')}</button>
			</div>
		</form>
	</div>
	<?php endif; ?>
</div>
