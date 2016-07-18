<?php
	/* Note. When creating a new task `$task_details` is empty */
	$pre_selected_tags = array();
	if(!empty($task_details)) {
		$pre_selected_tags = $task_details['tags'];
	} else {
		$pre_selected_tags = Req::val('tags');
	}
	if (!is_array($pre_selected_tags)) {
		$pre_selected_tags = array($pre_selected_tags);
	}
?>

<?php foreach ($tags as $tag_group): ?>
	<tr>
		<td><label>{$tag_group['name']}</label></td>
		<td class='tag-container'>
			<?php foreach ($tag_group['tags'] as $tag): ?>
				<?php if(!empty($pre_selected_tags) && in_array($tag['tag_id'], $pre_selected_tags)): ?>
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
