<div id="taskdetails">
  <form action="#" id="taskeditform" enctype="multipart/form-data" method="post">
	 <div>
		<h2 class="summary severity{Req::val('task_severity', $task_details['task_severity'])}">
		  <a href="{CreateUrl('details', $task_details['task_id'])}">{FS_PREFIX_CODE}#{$task_details['task_id']}</a>
		  <input class="text frmcopyme severity{Req::val('task_severity', $task_details['task_severity'])}" type="text"
			name="item_summary" size="80" 
			value="{Req::val('item_summary', $task_details['item_summary'])}" />
		  <?php if (!empty($conf['general']['extra_anon_notify'])): ?>
		  <label>e-mail klienta:</label>
		  <input class="text" type="text"
			name="anon_email" size="80" 
			value="{Req::val('anon_email', $task_details['anon_email'])}"
			title="{L('anon_email_info')}" />
		  <input type="hidden" name="anon_email_is_to_be_mod" value="1" />
		  <?php endif; ?>
		</h2>
		<input type="hidden" name="action" value="details.update" />
        <input type="hidden" name="edit" value="1" />
		<input type="hidden" name="task_id" value="{$task_details['task_id']}" />
		<input type="hidden" name="edit_start_time" value="{Req::val('edit_start_time', time())}" />

		<div id="fineprint">
		  {L('attachedtoproject')}:
		  <select name="project_id">
			{!tpl_options($fs->projects, Req::val('project_id', $proj->id))}
		  </select>
		  <br />
		  {L('openedby')} {!tpl_userlink($task_details['opened_by'])}
		  - {!formatDate($task_details['date_opened'], true)}
		  <?php if ($task_details['last_edited_by']): ?>
		  <br />
		  {L('editedby')}  {!tpl_userlink($task_details['last_edited_by'])}
		  - {formatDate($task_details['last_edited_time'], true)}
		  <?php endif; ?>
		</div>

        <table><tr><td id="taskfieldscell"><?php // small layout table ?>

		<div id="taskfields">
		  <table class="taskdetails">
			<tr>
			 <td><label for="tasktype">{L('tasktype')}</label></td>
			 <td>
				<select id="tasktype" name="task_type" class="frmcopyme">
				 {!tpl_options($proj->listTaskTypes(), Req::val('task_type', $task_details['task_type']))}
				</select>
			 </td>
			</tr>

			<?php $this->display('common.edittags.tpl'); ?>

			<tr>
			 <td><label for="category">{L('category')}</label></td>
			 <td>
				<select id="category" name="product_category" class="frmcopyme">
				 {!tpl_options($proj->listCategories(), Req::val('product_category', $task_details['product_category']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="status">{L('status')}</label></td>
			 <td>
				<select id="status" name="item_status">
				 {!tpl_options($proj->listTaskStatuses(), Req::val('item_status', $task_details['item_status']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label>{L('assignedto')}</label></td>
			 <td>
                <?php if ($user->perms('edit_assignments')): ?>

				<input type="hidden" name="old_assigned" value="{$old_assigned}" />
                <?php $this->display('common.multiuserselect.tpl'); ?>
                <?php else: ?>
                    <?php if (empty($assigned_users)): ?>
                     {L('noone')}
                     <?php else:
                     foreach ($assigned_users as $userid):
                     ?>
                     {!tpl_userlink($userid)}<br />
                     <?php endforeach;
                     endif; ?>
                <?php endif; ?>
			 </td>
			</tr>
			<tr>
			 <td><label for="os">{L('operatingsystem')}</label></td>
			 <td>
				<select id="os" name="operating_system" class="frmcopyme">
				 {!tpl_options($proj->listOs(), Req::val('operating_system', $task_details['operating_system']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="severity">{L('severity')}</label></td>
             <td>
				<select id="severity" name="task_severity" class="frmcopyme">
				 {!tpl_options($fs->severities, Req::val('task_severity', $task_details['task_severity']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="priority">{L('priority')}</label></td>
			 <td>
				<select id="priority" name="task_priority" class="frmcopyme">
				 {!tpl_options($fs->priorities, Req::val('task_priority', $task_details['task_priority']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="reportedver">{L('reportedversion')}</label></td>
			 <td>
				<select id="reportedver" name="reportedver" class="frmcopyme">
				{!tpl_options($proj->listVersions(false, '1,2,3', $task_details['product_version']), Req::val('reportedver', $task_details['product_version']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="dueversion">{L('dueinversion')}</label></td>
			 <td>
				<?php if ($task_details['task_type']==2 && !$user->perms('modify_all_tasks')): ?>
					<input type="hidden" name="closedby_version" value="{$task_details['closedby_version']}" />
					<select id="dueversion" name="closedby_version" disabled="disabled">
				<?php else: ?>
					<select id="dueversion" name="closedby_version">
				<?php endif; ?>
				 <option value="0">{L('undecided')}</option>
				 {!tpl_options($proj->listVersions(false, '1,2,3'), Req::val('closedby_version', $task_details['closedby_version']))}
				</select>
			 </td>
			</tr>
			<tr>
			 <td><label for="duedate">{L('duedate')}</label></td>
			 <td id="duedate">
                {!tpl_datepicker('due_date', '', Req::val('due_date', $task_details['due_date']))}
			 </td>
			</tr>
			<tr>
			 <td><label for="percent">{L('percentcomplete')}</label></td>
			 <td>
				<select id="percent" name="percent_complete">
				 <?php $arr = array(); for ($i = 0; $i<=100; $i+=10) $arr[$i] = $i.'%'; ?>
				 {!tpl_options($arr, Req::val('percent_complete', $task_details['percent_complete']))}
				</select>
			 </td>
			</tr>
            <?php if ($user->can_change_private($task_details)): ?>
            <tr>
              <td><label for="private">{L('private')}</label></td>
              <td>
                {!tpl_checkbox('mark_private', Req::val('mark_private', $task_details['mark_private']), 'private')}
              </td>
            </tr>
            <?php endif; ?>
		  </table>
		</div>

        </td><td style="width:100%">

		<div id="taskdetailsfull">
          <h3 class="taskdesc">{L('details')}</h3>
        <?php $attachments = $proj->listTaskAttachments($task_details['task_id']);
          $this->display('common.editattachments.tpl', 'attachments', $attachments); ?>

          <?php if (false && $user->perms('create_attachments')): ?>
          <div id="uploadfilebox">
            <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
              <input tabindex="5" class="file" type="file" size="55" name="usertaskfile[]" />
                <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
            </span>
            <noscript>
                <span>
                  <input tabindex="5" class="file" type="file" size="55" name="usertaskfile[]" />
                    <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
                </span>
            </noscript>
          </div>
          <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields()">
            {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
          </button>
          <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields()">
             {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
          </button>
          <?php endif; ?>
          <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
          <div class="hide preview" id="preview"></div>
          <?php endif; ?>
          {!TextFormatter::textarea('detailed_desc', 15, 70, array('id' => 'details', 'class'=>"frmcopyme"), Req::val('detailed_desc', $task_details['detailed_desc']))}
          <br />
		  <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
		  <button tabindex="10" type="button" onclick="showPreview('details', '{$baseurl}', 'preview')">{L('preview')}</button>
		  <?php endif; ?>
          <?php if (false && $user->perms('add_comments') && (!$task_details['is_closed'] || $proj->prefs['comment_closed'])): ?>
              <button type="button" onclick="showstuff('edit_add_comment');this.style.display='none';">{L('addcomment')}</button>
              <div id="edit_add_comment" class="hide">
              <label for="comment_text">{L('comment')}</label>

              <?php if (false && $user->perms('create_attachments')): ?>
              <div id="uploadfilebox_c">
                <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
                  <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
                    <a href="javascript://" tabindex="6" onclick="removeUploadField(this, 'uploadfilebox_c');">{L('remove')}</a><br />
                </span>
              </div>
              <button id="uploadfilebox_c_attachafile" tabindex="7" type="button" onclick="addUploadFields('uploadfilebox_c')">
                {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
              </button>
              <button id="uploadfilebox_c_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields('uploadfilebox_c')">
                 {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
              </button>
              <?php endif; ?>

            <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
            <div class="hide preview" id="preview_comment"></div>
            <?php endif; ?>
            {!TextFormatter::textarea('comment_text', 10, 50, array('accesskey' => 'r', 'tabindex' => 8, 'id' => 'comment_text'),'')}
            <br />
            <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
            <button tabindex="9" type="button" onclick="showPreview('comment_text', '{$baseurl}', 'preview_comment')">{L('preview')}</button>
            <?php endif; ?>
              </div>
          <?php endif; ?>
		  <!--
		  <p class="buttons">
              <button type="submit" accesskey="s" onclick="return checkok('{$baseurl}javascript/callbacks/checksave.php?time={time()}&amp;taskid={$task_details['task_id']}', '{#L('alreadyedited')}', 'taskeditform')">{L('savedetails')}</button>
              <button type="reset">{L('reset')}</button>
          </p>
		  -->
		</div>

        </td></tr></table>

	 </div>
     <div class="clear"></div>
  </form>
</div>
