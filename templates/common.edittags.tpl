<?php /* Note. When creating a new task `$task_details` is empty */ ?>

<?php foreach ($tags as $tag_group): ?>
	<tr>
		<td><label>{$tag_group['name']}</label></td>
		<td class='tag-container'>
			<?php foreach ($tag_group['tags'] as $tag): ?>
				<?php if(!empty($task_details) && in_array($tag['tag_id'], $task_details['tags'])): ?>
					<?php $tag_selected_attribute = 'checked="checked"'; ?>
				<?php else: ?>
					<?php $tag_selected_attribute = ''; ?>
				<?php endif; ?>
				<span class="tag-box">
					<input type="checkbox" name="tags[]" {!$tag_selected_attribute} value="{$tag['tag_id']}" id="task-tag-{$tag['tag_id']}">
					<label for="task-tag-{$tag['tag_id']}">{$tag['tag_name']}</label>
				</span>
			<?php endforeach; ?>
		</td>
	</tr>
<?php endforeach; ?>
