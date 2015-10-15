<?php
	include('tags.data.groupped.php');
	
	$old_tags = array(65, 66);
	
	var_export($_POST);
?>

<form action="" method="post">
	<input type="hidden_" name="old_tags" value="<?=implode(' ', $old_tags)?>">
	<table class="taskdetails">
		<?php foreach ($tags as $tag_group): ?>
			<tr>
				<td><label><?=$tag_group['name']?></label></td>
				<td>
					<?php foreach ($tag_group['tags'] as $tag): ?>
						<label><input type="checkbox" name="tags[]" value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></label>
					<?php endforeach; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	
	<input type="submit" value="Zapisz" name="submit" />
</form>