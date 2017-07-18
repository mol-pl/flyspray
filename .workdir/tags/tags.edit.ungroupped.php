<?php
	include('tags.data.all.php');
	
	$old_tags = array(65, 66);
	
	var_export($_POST);
?>

<form action="" method="post">
	<table class="taskdetails">
	<?php $current_group = ''; ?>
	<?php foreach ($tags as $tag): ?>
		<?php if ($current_group != $tag['tag_group']): ?>
			<?php if (!empty($current_group)): ?>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<td><label><?=$tag['tag_group']?></label>
					<?php if (empty($current_group)): ?>
						<input type="hidden_" name="old_tags" value="<?=implode(' ', $old_tags)?>">
					<?php endif; ?>
				</td>
				<td>
		<?php endif; ?>
		<label><input type="checkbox" name="tags[]" value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></label>
		<?php $current_group = $tag['tag_group']; ?>
	<?php endforeach; ?>
			</td>
		</tr>
	</table>
	
	<input type="submit" value="Zapisz" name="submit" />
</form>