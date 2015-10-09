<!-- Nux: Allow status change e.g. upon adding comments (only for opened tasks) -->
<div>
<label for="status">{L('status')}</label>
<?php if (!$task_details['is_closed']): ?>
	<select id="status" name="item_status">
		{!tpl_options($proj->listTaskStatuses(), Req::val('item_status', $task_details['item_status']))}
	</select>
<?php else: ?>
	<select id="status" name="item_status" disabled="disabled"><option>{L('closed')}</option></select>
<?php endif; ?>

<?php if (!$task_details['is_closed']): ?>
	<label for="percent">{L('progress')}</label>
	<select id="percent" name="percent_complete">
		<?php $arr = array(); for ($i = 0; $i<=100; $i+=10) $arr[$i] = $i.'%'; ?>
		{!tpl_options($arr, Req::val('percent_complete', $task_details['percent_complete']))}
	</select>
<?php endif; ?>

<!-- Nux: Also allow changing assigments -->
<?php if (!$task_details['is_closed'] && $user->perms('edit_assignments')): ?>
<button type="button" onclick="showstuff('comment_add_edit_assignments');this.style.display='none';">{L('editassignments')}</button>
<div id="comment_add_edit_assignments" class="hide">
	<label>{L('assignedto')}</label>
	<input type="hidden" name="old_assigned" value="{$old_assigned}" />
	<?php $this->display('common.multiuserselect.tpl'); ?>
</div>
<?php endif; ?>

</div>